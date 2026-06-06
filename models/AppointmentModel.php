<?php
// ============================================================
// models/AppointmentModel.php
// Handles all database operations on the 'appointments' table.
// Most queries JOIN users, doctors, and specializations to
// return complete appointment details in a single query.
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class AppointmentModel extends BaseModel
{
    // --------------------------------------------------------
    // baseSelect()  [private]
    // The shared SELECT + FROM + JOIN block used by all queries
    // that need full appointment details.
    //
    // Defined once here to avoid repeating it in every method.
    // --------------------------------------------------------
    private function baseSelect(): string
    {
        return "SELECT
                    a.id,
                    a.patient_id,
                    a.doctor_id,
                    a.appt_date,
                    a.appt_time,
                    a.status,
                    a.reason,
                    a.doctor_notes,
                    a.created_at,
                    p.name          AS patient_name,
                    p.phone         AS patient_phone,
                    du.name         AS doctor_name,
                    s.name          AS specialization_name,
                    d.consultation_fee
                FROM  appointments  a
                JOIN  users         p  ON a.patient_id = p.id
                JOIN  doctors       d  ON a.doctor_id  = d.id
                JOIN  users         du ON d.user_id    = du.id
                JOIN  specializations s ON d.specialization_id = s.id";
    }


    // --------------------------------------------------------
    // hasConflict()
    // Check whether a doctor already has a booking at the same
    // date and time (to prevent double-booking).
    //
    // Called before book() to show a clear error message.
    // The database also enforces this via a UNIQUE constraint,
    // but the DB error message is not user-friendly.
    // --------------------------------------------------------
    public function hasConflict(
        int    $doctorId,
        string $date,
        string $time
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  doctor_id  = ?
             AND    appt_date  = ?
             AND    appt_time  = ?
             AND    status    != 'cancelled'",
            "iss",
            [$doctorId, $date, $time]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // countToday()
    // Count the total number of appointments scheduled for today.
    // Used in the Admin Dashboard statistics.
    // --------------------------------------------------------
    public function countToday(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  appt_date = CURDATE()"
        );
    }


    // --------------------------------------------------------
    // countThisWeekByStatus()
    // Count appointments for the current week, grouped by status.
    // Used in the Admin Dashboard weekly chart.
    // --------------------------------------------------------
    public function countThisWeekByStatus(): array
    {
        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             WHERE    YEARWEEK(appt_date, 1) = YEARWEEK(CURDATE(), 1)
             GROUP BY status"
        );

        return $this->normaliseStatusCounts($rows);
    }


    // --------------------------------------------------------
    // countThisMonthByDoctor()
    // Count a specific doctor's appointments for the current month,
    // grouped by status. Used in the Doctor Dashboard.
    // --------------------------------------------------------
    public function countThisMonthByDoctor(int $doctorId): array
    {
        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             WHERE    doctor_id = ?
             AND      appt_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             AND      appt_date <  DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
             GROUP BY status",
            "i",
            [$doctorId]
        );

        return $this->normaliseStatusCounts($rows);
    }


    // --------------------------------------------------------
    // getNextUpcomingByPatient()
    // Fetch the single next upcoming appointment for a patient.
    // Used in the Patient Dashboard to show the nearest booking.
    // --------------------------------------------------------
    public function getNextUpcomingByPatient(int $patientId): ?array
    {
        return $this->fetchOne(
            $this->baseSelect()
            . " WHERE  a.patient_id = ?
                AND    a.appt_date >= CURDATE()
                AND    a.status    IN ('pending','confirmed')
                ORDER BY a.appt_date ASC, a.appt_time ASC
                LIMIT  1",
            "i",
            [$patientId]
        );
    }


    // --------------------------------------------------------
    // book()
    // Insert a new appointment record into the database.
    //
    // New appointments always start with status = 'pending'.
    //
    // Returns: new appointment ID, or 0 on failure.
    // --------------------------------------------------------
    public function book(array $data): int
    {
        $result = $this->execute(
            "INSERT INTO appointments
                (patient_id, doctor_id, appt_date,
                 appt_time,  status,    reason)
             VALUES
                (?,          ?,         ?,
                 ?,           'pending', ?)",
            "iisss",
            [
                $data['patient_id'],
                $data['doctor_id'],
                $data['appt_date'],
                $data['appt_time'],
                $data['reason'] ?? null
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // findById()
    // Fetch a single appointment with all its related details by ID.
    //
    // Used when:
    //   - Viewing appointment details
    //   - Verifying ownership before any action
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . " WHERE a.id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // buildWhereClause()  [private]
    // Build a dynamic WHERE clause from an array of filter options.
    //
    // This is a private helper used only within this model.
    //
    // Supported filters:
    //   'status'    → filter by appointment status
    //   'date_from' → appointments on or after this date
    //   'date_to'   → appointments on or before this date
    //   'doctor_id' → filter by a specific doctor (Admin only)
    //   'search'    → search by patient name (Admin only)
    //
    // Returns: ['where' => '...', 'types' => '...', 'params' => [...]]
    // --------------------------------------------------------
    private function buildWhereClause(
        array  $filters,
        array  $baseConditions = []
    ): array {
        $conditions = $baseConditions;
        $params     = [];
        $types      = '';

        // Add status filter if provided.
        if (!empty($filters['status'])) {
            $conditions[] = "a.status = ?";
            $params[]     = $filters['status'];
            $types       .= 's';
        }

        // Add start date filter if provided.
        if (!empty($filters['date_from'])) {
            $conditions[] = "a.appt_date >= ?";
            $params[]     = $filters['date_from'];
            $types       .= 's';
        }

        // Add end date filter if provided.
        if (!empty($filters['date_to'])) {
            $conditions[] = "a.appt_date <= ?";
            $params[]     = $filters['date_to'];
            $types       .= 's';
        }

        // Filter by a specific doctor (used by Admin).
        if (!empty($filters['doctor_id'])) {
            $conditions[] = "a.doctor_id = ?";
            $params[]     = (int) $filters['doctor_id'];
            $types       .= 'i';
        }

        // Search by patient name (used by Admin).
        if (!empty($filters['search'])) {
            $conditions[] = "p.name LIKE ?";
            $params[]     = '%' . $filters['search'] . '%';
            $types       .= 's';
        }

        // Assemble the final WHERE clause string.
        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        return [
            'where'  => $where,
            'types'  => $types,
            'params' => $params
        ];
    }


    // --------------------------------------------------------
    // getByPatient()
    // Fetch a paginated list of appointments for a specific patient.
    // Used on the patient's "My Appointments" page.
    // --------------------------------------------------------
    public function getByPatient(
        int   $patientId,
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        // Base condition: only this patient's appointments.
        $baseConditions = ["a.patient_id = ?"];
        $baseParams     = [$patientId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        // Merge base params, filter params, and pagination params in order.
        $allParams = array_merge(
            $baseParams,
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $baseTypes . $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.appt_date DESC, a.appt_time DESC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // getByDoctor()
    // Fetch a paginated list of appointments for a specific doctor.
    // Used on the doctor's appointments page.
    // --------------------------------------------------------
    public function getByDoctor(
        int   $doctorId,
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        $baseConditions = ["a.doctor_id = ?"];
        $baseParams     = [$doctorId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge(
            $baseParams,
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $baseTypes . $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.appt_date ASC, a.appt_time ASC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // getAll()
    // Fetch a paginated list of all appointments for the Admin.
    // --------------------------------------------------------
    public function getAll(
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        $clause = $this->buildWhereClause($filters);

        $allParams = array_merge(
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.created_at DESC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countByPatient()
    // Count the total appointments for a patient (used by Paginator).
    // --------------------------------------------------------
    public function countByPatient(
        int   $patientId,
        array $filters = []
    ): int {
        $baseConditions = ["a.patient_id = ?"];
        $baseParams     = [$patientId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge($baseParams, $clause['params']);
        $allTypes  = $baseTypes . $clause['types'];

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             JOIN   doctors      d  ON a.doctor_id  = d.id
             JOIN   users        p  ON a.patient_id = p.id
             " . $clause['where'],
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countByDoctor()
    // Count the total appointments for a doctor (used by Paginator).
    // --------------------------------------------------------
    public function countByDoctor(
        int   $doctorId,
        array $filters = []
    ): int {
        $baseConditions = ["a.doctor_id = ?"];
        $baseParams     = [$doctorId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge($baseParams, $clause['params']);
        $allTypes  = $baseTypes . $clause['types'];

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             " . $clause['where'],
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countAll()
    // Count all appointments system-wide (used by Admin Paginator).
    // --------------------------------------------------------
    public function countAll(array $filters = []): int
    {
        $clause = $this->buildWhereClause($filters);

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             JOIN   users        p ON a.patient_id = p.id
             " . $clause['where'],
            $clause['types'],
            $clause['params']
        );
    }


    // --------------------------------------------------------
    // updateStatus()
    // Change the status of an appointment.
    //
    // Used by:
    //   - Doctor: confirm, complete, or cancel an appointment
    //   - Admin:  change any appointment status
    //   - Patient: cancel a pending appointment
    //
    // $notes: optional doctor notes — only saved if provided.
    // --------------------------------------------------------
    public function updateStatus(
        int    $id,
        string $status,
        string $notes = ''
    ): bool {
        // If doctor notes are provided, update them along with the status.
        if (!empty($notes)) {
            return (bool) $this->execute(
                "UPDATE appointments
                 SET    status       = ?,
                        doctor_notes = ?
                 WHERE  id           = ?",
                "ssi",
                [$status, $notes, $id]
            );
        }

        // No notes — update only the status column.
        return (bool) $this->execute(
            "UPDATE appointments
             SET    status = ?
             WHERE  id     = ?",
            "si",
            [$status, $id]
        );
    }


    // --------------------------------------------------------
    // getTodayByDoctor()
    // Fetch all of a doctor's non-cancelled appointments for today.
    // Used at the top of the Doctor Dashboard.
    // --------------------------------------------------------
    public function getTodayByDoctor(int $doctorId): array
    {
        return $this->fetchAll(
            $this->baseSelect()
            . " WHERE  a.doctor_id = ?
                AND    a.appt_date = CURDATE()
                AND    a.status   != 'cancelled'
                ORDER BY a.appt_time ASC",
            "i",
            [$doctorId]
        );
    }


    // --------------------------------------------------------
    // getUpcomingByPatient()
    // Fetch a patient's next upcoming appointments (pending or confirmed).
    // Used on the Patient Dashboard.
    // --------------------------------------------------------
    public function getUpcomingByPatient(
        int $patientId,
        int $limit = 5
    ): array {
        return $this->fetchAll(
            $this->baseSelect()
            . " WHERE  a.patient_id = ?
                AND    a.appt_date >= CURDATE()
                AND    a.status    IN ('pending','confirmed')
                ORDER BY a.appt_date ASC, a.appt_time ASC
                LIMIT  ?",
            "ii",
            [$patientId, $limit]
        );
    }


    // --------------------------------------------------------
    // getRecentForAdmin()
    // Fetch the most recently created appointments for the Admin Dashboard.
    // --------------------------------------------------------
    public function getRecentForAdmin(int $limit = 5): array
    {
        return $this->fetchAll(
            $this->baseSelect()
            . " ORDER BY a.created_at DESC
                LIMIT ?",
            "i",
            [$limit]
        );
    }


    // --------------------------------------------------------
    // countByStatus()
    // Count appointments grouped by status.
    //
    // $scope controls whose appointments to count:
    //   'all'     → system-wide (Admin)
    //   'patient' → a specific patient's appointments
    //   'doctor'  → a specific doctor's appointments
    //
    // Returns: ['pending'=>5, 'confirmed'=>3, 'completed'=>20, 'cancelled'=>2]
    // --------------------------------------------------------
    public function countByStatus(
        string $scope   = 'all',
        int    $scopeId = 0
    ): array {
        // Build the WHERE clause based on scope.
        $where  = '';
        $types  = '';
        $params = [];

        if ($scope === 'patient') {
            $where  = "WHERE patient_id = ?";
            $types  = 'i';
            $params = [$scopeId];
        } elseif ($scope === 'doctor') {
            $where  = "WHERE doctor_id = ?";
            $types  = 'i';
            $params = [$scopeId];
        }

        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             {$where}
             GROUP BY status",
            $types,
            $params
        );

        return $this->normaliseStatusCounts($rows);
    }


    // --------------------------------------------------------
    // normaliseStatusCounts()  [private]
    // Convert raw status rows into a predictable status => count array.
    //
    // Ensures all four statuses always exist in the result,
    // defaulting to 0 if no rows exist for that status.
    // --------------------------------------------------------
    private function normaliseStatusCounts(array $rows): array
    {
        $counts = [
            'pending'   => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }


    // --------------------------------------------------------
    // isOwnedByPatient()
    // Check whether an appointment belongs to a specific patient.
    //
    // Used to enforce ownership before allowing a patient to
    // view or cancel an appointment.
    // --------------------------------------------------------
    public function isOwnedByPatient(
        int $appointmentId,
        int $patientId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  id         = ?
             AND    patient_id = ?",
            "ii",
            [$appointmentId, $patientId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByDoctor()
    // Check whether an appointment belongs to a specific doctor.
    //
    // Used to enforce ownership before allowing a doctor to
    // update the status or add a prescription.
    // --------------------------------------------------------
    public function isOwnedByDoctor(
        int $appointmentId,
        int $doctorId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  id        = ?
             AND    doctor_id = ?",
            "ii",
            [$appointmentId, $doctorId]
        );

        return (int) $count > 0;
    }
}
