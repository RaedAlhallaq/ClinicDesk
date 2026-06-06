<?php
// ============================================================
// models/UserModel.php
// Handles all database operations on the 'users' table.
// Inherits from BaseModel: $db, fetchAll, fetchOne, fetchColumn.
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel
{
    // The table name stored as a constant.
    // If the table is ever renamed, only this one line needs to change.
    private const TABLE = 'users';


    // --------------------------------------------------------
    // findById()
    // Find a single user record by their ID.
    //
    // Used when:
    //   - Loading user data for the edit page
    //   - Verifying ownership of a resource
    //
    // Returns: an associative array of user data, or null if not found.
    // Note: password is intentionally excluded here for security.
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, role, phone,
                    avatar, is_active, created_at
             FROM   users
             WHERE  id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // findByEmail()
    // Find a user by their email address.
    //
    // Used when:
    //   - Logging in (AuthController needs the password hash)
    //   - Checking for duplicate emails before creating a user
    //
    // Note: password IS included here because AuthController
    //       needs it for password_verify(). In findById() it is
    //       hidden since no other operation needs it.
    // --------------------------------------------------------
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, password, role,
                    phone, avatar, is_active
             FROM   users
             WHERE  email = ?",
            "s",
            [$email]
        );
    }


    // --------------------------------------------------------
    // create()
    // Insert a new user record into the database.
    //
    // Used when:
    //   - An Admin creates a new user account
    //
    // $data must contain: name, email, password (plain text),
    //                     role, phone (optional)
    //
    // The password is hashed here inside the model — never
    // hash it in the controller. The model is responsible for
    // how data is stored.
    //
    // Returns: the new user's ID, or 0 on failure.
    // --------------------------------------------------------
    public function create(array $data): int
    {
        // Hash the plain-text password using bcrypt before saving.
        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_BCRYPT
        );

        $result = $this->execute(
            "INSERT INTO users (name, email, password, role, phone)
             VALUES            (?,    ?,     ?,        ?,    ?   )",
            "sssss",
            [
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role']  ?? 'patient',
                $data['phone'] ?? null
            ]
        );

        // Return the new ID on success, or 0 on failure.
        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update()
    // Update an existing user's basic profile information.
    //
    // Used when:
    //   - An Admin edits a user's name, phone, or avatar
    //
    // $data can contain: name, phone, avatar (optional)
    //
    // Returns: true on success, false on failure.
    // --------------------------------------------------------
    public function update(int $id, array $data): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    name   = ?,
                    phone  = ?,
                    avatar = ?
             WHERE  id     = ?",
            "sssi",
            [
                $data['name'],
                $data['phone']  ?? null,
                $data['avatar'] ?? null,
                $id
            ]
        );
    }


    // --------------------------------------------------------
    // updatePassword()
    // Change a user's password only.
    //
    // This is a separate method because changing a password is
    // a sensitive operation that differs from regular updates.
    //
    // $newPassword: plain-text — hashed here before saving.
    // --------------------------------------------------------
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_BCRYPT
        );

        return (bool) $this->execute(
            "UPDATE users
             SET    password = ?
             WHERE  id       = ?",
            "si",
            [$hashedPassword, $id]
        );
    }


    // --------------------------------------------------------
    // updateAvatar()
    // Update a user's profile photo path only.
    //
    // Kept as a separate method because photo uploads happen
    // independently from the rest of the profile data.
    // --------------------------------------------------------
    public function updateAvatar(int $id, string $avatarPath): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    avatar = ?
             WHERE  id     = ?",
            "si",
            [$avatarPath, $id]
        );
    }


    // --------------------------------------------------------
    // toggleActive()
    // Flip a user's active status between enabled and disabled.
    //
    //   1 → 0 (deactivate)
    //   0 → 1 (activate)
    //
    // Using SQL's NOT operator is cleaner than reading the value
    // in PHP and then writing it back.
    // --------------------------------------------------------
    public function toggleActive(int $id): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    is_active = NOT is_active
             WHERE  id        = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // getAllPaginated()
    // Fetch a page of users with optional role and search filters.
    //
    // Used in the Admin panel to display the user list.
    //
    // $offset → from Paginator::offset()
    // $limit  → ITEMS_PER_PAGE from config
    // $role   → filter by role (optional)
    // $search → search by name or email (optional)
    //
    // The WHERE clause is built dynamically based on which
    // filters are active — empty filters are skipped.
    // --------------------------------------------------------
    public function getAllPaginated(
        int    $offset,
        int    $limit,
        string $role   = '',
        string $search = ''
    ): array {
        // Build the WHERE clause dynamically based on active filters.
        $conditions = [];
        $params     = [];
        $types      = '';

        // Filter by role if provided.
        if (!empty($role)) {
            $conditions[] = "role = ?";
            $params[]     = $role;
            $types       .= 's';
        }

        // Filter by name or email if a search term is provided.
        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[]     = "%{$search}%";
            $params[]     = "%{$search}%";
            $types       .= 'ss';
        }

        // Assemble the WHERE clause string.
        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        // Append LIMIT and OFFSET for pagination.
        $params[] = $limit;
        $params[] = $offset;
        $types   .= 'ii';

        return $this->fetchAll(
            "SELECT   id, name, email, role,
                      phone, is_active, created_at
             FROM     users
             {$where}
             ORDER BY created_at DESC
             LIMIT    ? OFFSET ?",
            $types,
            $params
        );
    }


    // --------------------------------------------------------
    // countAll()
    // Count the total number of users matching the active filters.
    //
    // Used with the Paginator to calculate total page count.
    // Applies the same filters as getAllPaginated() but without
    // LIMIT/OFFSET since we only need a total count.
    // --------------------------------------------------------
    public function countAll(
        string $role   = '',
        string $search = ''
    ): int {
        $conditions = [];
        $params     = [];
        $types      = '';

        if (!empty($role)) {
            $conditions[] = "role = ?";
            $params[]     = $role;
            $types       .= 's';
        }

        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[]     = "%{$search}%";
            $params[]     = "%{$search}%";
            $types       .= 'ss';
        }

        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM users {$where}",
            $types,
            $params
        );
    }


    // --------------------------------------------------------
    // countByRole()
    // Count users grouped by their role.
    //
    // Used in the Admin Dashboard for statistics.
    //
    // Returns an array like:
    //   ['admin' => 1, 'doctor' => 5, 'patient' => 23]
    // --------------------------------------------------------
    public function countByRole(): array
    {
        $rows = $this->fetchAll(
            "SELECT   role, COUNT(*) as total
             FROM     users
             GROUP BY role"
        );

        // Convert the result rows into a simple role => count map.
        $counts = [
            'admin'   => 0,
            'doctor'  => 0,
            'patient' => 0
        ];

        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }

        return $counts;
    }


    // --------------------------------------------------------
    // emailExists()
    // Check whether an email address is already registered.
    //
    // Used before creating a new user to show a clear error
    // message instead of a raw database duplicate-key error.
    //
    // $excludeId: when editing a user, pass their current ID
    //             so we don't flag their own email as a duplicate.
    // --------------------------------------------------------
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   users
             WHERE  email = ?
             AND    id    != ?",
            "si",
            [$email, $excludeId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // delete()
    // Permanently delete a user by their ID.
    //
    // The database uses ON DELETE CASCADE, so related records
    // (doctor profile, appointments, etc.) are removed automatically.
    //
    // Used in: Admin panel user management.
    // --------------------------------------------------------
    public function delete(int $id): bool
    {
        return (bool) $this->execute(
            "DELETE FROM users WHERE id = ?",
            "i",
            [$id]
        );
    }
}