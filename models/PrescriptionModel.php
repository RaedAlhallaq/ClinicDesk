<?php
// ============================================================
// models/PrescriptionModel.php
// Handles all database operations on the 'prescriptions' table.
// Most queries JOIN the appointments and users tables to return
// complete prescription details in a single query.
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class PrescriptionModel extends BaseModel
{
    // --------------------------------------------------------
    // findByAppointmentId()
    // Fetch a prescription record by its linked appointment ID.
    //
    // Used when:
    //   - Checking if a prescription already exists for an appointment
    //   - Displaying the prescription on the appointment detail page
    //
    // Returns: prescription data as an array, or null if none exists.
    // --------------------------------------------------------
    public function findByAppointmentId(int $appointmentId): ?array
    {
        return $this->fetchOne(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at
             FROM    prescriptions p
             WHERE   p.appointment_id = ?",
            "i",
            [$appointmentId]
        );
    }


    // --------------------------------------------------------
    // findById()
    // Fetch a prescription by its own ID, along with full details
    // from the linked appointment, patient, doctor, and specialization.
    //
    // Used when:
    //   - Viewing the prescription detail page
    //   - Verifying ownership before allowing a file download
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.patient_id,
                     a.doctor_id,
                     a.appt_date,
                     a.appt_time,
                     u_p.name  AS patient_name,
                     u_d.name  AS doctor_name,
                     s.name    AS specialization_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id  = a.id
             JOIN    users          u_p ON a.patient_id      = u_p.id
             JOIN    doctors        d   ON a.doctor_id       = d.id
             JOIN    users          u_d ON d.user_id         = u_d.id
             JOIN    specializations s  ON d.specialization_id = s.id
             WHERE   p.id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // create()
    // Insert a new prescription record into the database.
    //
    // Used when a doctor adds a prescription to a completed appointment.
    //
    // $data must contain: appointment_id, diagnosis, medications
    //                     notes (optional), file_path (optional PDF)
    //
    // Returns: new prescription ID, or 0 on failure.
    // --------------------------------------------------------
    public function create(array $data): int
    {
        $result = $this->execute(
            "INSERT INTO prescriptions
                (appointment_id, diagnosis, medications,
                 notes,          file_path)
             VALUES
                (?,              ?,         ?,
                 ?,              ?         )",
            "issss",
            [
                $data['appointment_id'],
                $data['diagnosis'],
                $data['medications'],
                $data['notes']     ?? null,
                $data['file_path'] ?? null
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update()
    // Update an existing prescription record.
    //
    // Used when a doctor edits a prescription after creating it.
    // --------------------------------------------------------
    public function update(int $id, array $data): bool
    {
        return (bool) $this->execute(
            "UPDATE prescriptions
             SET    diagnosis   = ?,
                    medications = ?,
                    notes       = ?,
                    file_path   = ?
             WHERE  id          = ?",
            "ssssi",
            [
                $data['diagnosis'],
                $data['medications'],
                $data['notes']     ?? null,
                $data['file_path'] ?? null,
                $id
            ]
        );
    }


    // --------------------------------------------------------
    // updateFilePath()
    // Update only the file_path column of an existing prescription.
    //
    // Used when a new PDF file is uploaded separately from the
    // text fields (diagnosis, medications, notes).
    // --------------------------------------------------------
    public function updateFilePath(int $id, string $filePath): bool
    {
        return (bool) $this->execute(
            "UPDATE prescriptions
             SET    file_path = ?
             WHERE  id        = ?",
            "si",
            [$filePath, $id]
        );
    }


    // --------------------------------------------------------
    // getByPatient()
    // Fetch all prescriptions belonging to a specific patient.
    //
    // Used on the patient's "My Prescriptions" page.
    // JOINs appointments, doctors, and specializations to show
    // the doctor name and appointment date alongside each prescription.
    // --------------------------------------------------------
    public function getByPatient(int $patientId): array
    {
        return $this->fetchAll(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.appt_date,
                     a.appt_time,
                     u_d.name  AS doctor_name,
                     s.name    AS specialization_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id    = a.id
             JOIN    doctors        d   ON a.doctor_id         = d.id
             JOIN    users          u_d ON d.user_id           = u_d.id
             JOIN    specializations s  ON d.specialization_id = s.id
             WHERE   a.patient_id = ?
             ORDER BY p.created_at DESC",
            "i",
            [$patientId]
        );
    }


    // --------------------------------------------------------
    // getByDoctor()
    // Fetch all prescriptions issued by a specific doctor.
    //
    // Used on the doctor's "My Prescriptions" page.
    // JOINs appointments and patients to show patient names.
    // --------------------------------------------------------
    public function getByDoctor(int $doctorId): array
    {
        return $this->fetchAll(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.appt_date,
                     a.appt_time,
                     u_p.name  AS patient_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id = a.id
             JOIN    users          u_p ON a.patient_id     = u_p.id
             WHERE   a.doctor_id = ?
             ORDER BY p.created_at DESC",
            "i",
            [$doctorId]
        );
    }


    // --------------------------------------------------------
    // existsForAppointment()
    // Check whether a prescription already exists for a given appointment.
    //
    // Called before create() to prevent duplicate prescriptions.
    // The database also enforces a UNIQUE constraint on appointment_id,
    // but this check gives a friendlier error message.
    //
    // Returns: true if a prescription already exists, false otherwise.
    // --------------------------------------------------------
    public function existsForAppointment(int $appointmentId): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions
             WHERE  appointment_id = ?",
            "i",
            [$appointmentId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByPatient()
    // Verify that a prescription belongs to a specific patient.
    //
    // Used before allowing a patient to download their prescription PDF.
    // Patients may only access their own files.
    // --------------------------------------------------------
    public function isOwnedByPatient(
        int $prescriptionId,
        int $patientId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  p.id          = ?
             AND    a.patient_id  = ?",
            "ii",
            [$prescriptionId, $patientId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByDoctor()
    // Verify that a prescription was issued by a specific doctor.
    //
    // Used before allowing a doctor to edit or download a prescription.
    // --------------------------------------------------------
    public function isOwnedByDoctor(
        int $prescriptionId,
        int $doctorId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  p.id          = ?
             AND    a.doctor_id   = ?",
            "ii",
            [$prescriptionId, $doctorId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // countByPatient()
    // Count the total number of prescriptions for a specific patient.
    //
    // Used in the Patient Dashboard to display a summary statistic.
    // --------------------------------------------------------
    public function countByPatient(int $patientId): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  a.patient_id = ?",
            "i",
            [$patientId]
        );
    }
}