<?php
// ============================================================
// models/DoctorModel.php
// Handles all database operations related to doctors.
// Most queries JOIN the 'doctors', 'users', and 'specializations' tables.
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class DoctorModel extends BaseModel
{
    // --------------------------------------------------------
    // findById()
    // Fetch a doctor's full profile by their doctor record ID (doctors.id).
    //
    // JOINs users and specializations to return all data in one query.
    // Returns: full doctor data as an array, or null if not found.
    // --------------------------------------------------------
    public function findById(int $doctorId): ?array
    {
        $doctor = $this->fetchOne(
            "SELECT  d.id,
                     d.user_id,
                     d.specialization_id,
                     d.bio,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     u.email,
                     u.phone,
                     u.avatar,
                     u.is_active,
                     s.name  AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   d.id = ?",
            "i",
            [$doctorId]
        );

        if ($doctor) {
            // Convert the available_days CSV string into an array for easier use in views.
            $doctor['available_days_array'] = $this->parseDays(
                $doctor['available_days']
            );
        }

        return $doctor;
    }


    // --------------------------------------------------------
    // findByUserId()
    // Fetch a doctor's profile using their user account ID (users.id).
    //
    // Used when:
    //   - A doctor logs in: Auth::id() → findByUserId()
    //   - A doctor edits their own profile
    // --------------------------------------------------------
    public function findByUserId(int $userId): ?array
    {
        $doctor = $this->fetchOne(
            "SELECT  d.id,
                     d.user_id,
                     d.specialization_id,
                     d.bio,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     u.email,
                     u.phone,
                     u.avatar,
                     u.is_active,
                     s.name  AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   d.user_id = ?",
            "i",
            [$userId]
        );

        if ($doctor) {
            // Parse available_days into an array for easy view rendering.
            $doctor['available_days_array'] = $this->parseDays(
                $doctor['available_days']
            );
        }

        return $doctor;
    }


    // --------------------------------------------------------
    // getAll()
    // Fetch all active doctors (used for dropdown lists).
    //
    // Used when:
    //   - Patient is booking an appointment (doctor dropdown)
    //   - Admin is filtering reports by doctor
    //
    // Returns a simplified list: id, name, specialization, fee.
    // Only returns doctors whose user account is active (is_active = 1).
    // --------------------------------------------------------
    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT  d.id,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     s.name AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   u.is_active = 1
             ORDER BY u.name ASC"
        );
    }


    // --------------------------------------------------------
    // getAllPaginated()
    // Fetch a page of doctors for the Admin management list.
    //
    // Uses LIMIT and OFFSET for pagination.
    // --------------------------------------------------------
    public function getAllPaginated(int $offset, int $limit): array
    {
        return $this->fetchAll(
            "SELECT  d.id,
                     d.consultation_fee,
                     d.available_days,
                     d.specialization_id,
                     u.name,
                     u.email,
                     u.phone,
                     u.is_active,
                     u.avatar,
                     s.name AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             ORDER BY u.name ASC
             LIMIT   ? OFFSET ?",
            "ii",
            [$limit, $offset]
        );
    }


    // --------------------------------------------------------
    // countAll()
    // Return the total number of doctors (used by the Paginator).
    // --------------------------------------------------------
    public function countAll(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM doctors"
        );
    }


    // --------------------------------------------------------
    // create()
    // Insert a new doctor record into the 'doctors' table.
    //
    // Note: the user account must be created first via UserModel::create().
    //       This method then links the doctor profile to that user.
    //
    // $data must contain: user_id, specialization_id, bio,
    //                     consultation_fee, available_days (array or string)
    //
    // Returns: new doctor ID, or 0 on failure.
    // --------------------------------------------------------
    public function create(array $data): int
    {
        // Convert the available_days array to a comma-separated string for storage.
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : $data['available_days'];

        $result = $this->execute(
            "INSERT INTO doctors
                (user_id, specialization_id, bio,
                 consultation_fee, available_days)
             VALUES
                (?,       ?,                 ?,
                 ?,                ?)       ",
            "iisds",
            [
                $data['user_id'],
                $data['specialization_id'],
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update()
    // Update a doctor's profile fields (used by Admin when editing a doctor).
    //
    // Updates specialization, bio, fee, and available days.
    // --------------------------------------------------------
    public function update(int $doctorId, array $data): bool
    {
        // Convert available_days to a CSV string if it's an array.
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : $data['available_days'];

        return (bool) $this->execute(
            "UPDATE doctors
             SET    specialization_id = ?,
                    bio               = ?,
                    consultation_fee  = ?,
                    available_days    = ?
             WHERE  id                = ?",
            "isdsi",
            [
                $data['specialization_id'],
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays,
                $doctorId
            ]
        );
    }


    // --------------------------------------------------------
    // updatePhoto()
    // Save a new profile photo path for a doctor.
    //
    // The photo path is stored in the 'users' table (avatar column),
    // not in the 'doctors' table, so we update 'users' here.
    // --------------------------------------------------------
    public function updatePhoto(int $userId, string $photoPath): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    avatar = ?
             WHERE  id     = ?",
            "si",
            [$photoPath, $userId]
        );
    }


    // --------------------------------------------------------
    // getAvailableDays()
    // Return the list of days a specific doctor is available.
    //
    // Used when:
    //   - Validating the appointment booking day
    //   - Showing available days to a patient
    //
    // Returns: array of short day names e.g. ['Sun', 'Mon', 'Wed']
    // --------------------------------------------------------
    public function getAvailableDays(int $doctorId): array
    {
        $result = $this->fetchColumn(
            "SELECT available_days
             FROM   doctors
             WHERE  id = ?",
            "i",
            [$doctorId]
        );

        if (!$result) {
            return [];
        }

        return $this->parseDays($result);
    }


    // --------------------------------------------------------
    // parseDays()  [private]
    // Convert the available_days CSV string into a PHP array.
    //
    // This is a private helper used only within this class.
    //
    // Input:  "Sun,Mon,Tue,Wed,Thu"
    // Output: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu']
    // --------------------------------------------------------
    private function parseDays(string $days): array
    {
        if (empty($days)) {
            return [];
        }

        // Split on commas, trim whitespace from each value, and remove empty entries.
        return array_filter(
            array_map('trim', explode(',', $days))
        );
    }


    // --------------------------------------------------------
    // isAvailableOnDay()
    // Check whether a doctor is available on a specific day of the week.
    //
    // Used during appointment booking validation.
    //
    // $dayName: short English day name — e.g. 'Sun', 'Mon', 'Fri'
    // --------------------------------------------------------
    public function isAvailableOnDay(int $doctorId, string $dayName): bool
    {
        $availableDays = $this->getAvailableDays($doctorId);
        return in_array($dayName, $availableDays, true);
    }


    // --------------------------------------------------------
    // updateProfile()
    // Update a doctor's own profile (self-edit, not Admin editing).
    //
    // Updates two tables in two separate prepared statements:
    //   1. doctors → bio, consultation_fee, available_days
    //   2. users   → name, email, phone
    //
    // $doctorId: the doctors.id value
    // $userId:   the users.id linked to this doctor
    // $data:     the updated field values
    //
    // Returns: true only if BOTH updates succeed.
    // --------------------------------------------------------
    public function updateProfile(int $doctorId, int $userId, array $data): bool
    {
        // Convert available_days array to a CSV string for storage.
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : ($data['available_days'] ?? '');

        // Update the doctors table with professional profile fields.
        $doctorUpdated = (bool) $this->execute(
            "UPDATE doctors
             SET    bio               = ?,
                    consultation_fee  = ?,
                    available_days    = ?
             WHERE  id                = ?",
            "sdsi",
            [
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays,
                $doctorId
            ]
        );

        // Update the users table with personal contact information.
        $userUpdated = (bool) $this->execute(
            "UPDATE users
             SET    name  = ?,
                    email = ?,
                    phone = ?
             WHERE  id    = ?",
            "sssi",
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $userId
            ]
        );

        // Both updates must succeed for the operation to be considered successful.
        return $doctorUpdated && $userUpdated;
    }
}