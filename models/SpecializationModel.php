<?php
// ============================================================
// models/SpecializationModel.php
// Handles all database operations on the 'specializations' table.
// Used to manage the list of medical specializations available in the system.
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class SpecializationModel extends BaseModel
{
    // --------------------------------------------------------
    // getAll()
    // Fetch all specializations ordered alphabetically.
    //
    // Used when:
    //   - Populating the dropdown when creating or editing a doctor
    //   - Listing specializations on the Admin management page
    // --------------------------------------------------------
    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT   id, name
             FROM     specializations
             ORDER BY name ASC"
        );
    }


    // --------------------------------------------------------
    // findById()
    // Fetch a single specialization by its ID.
    //
    // Used to verify a specialization exists before editing it.
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name
             FROM   specializations
             WHERE  id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // nameExists()
    // Check whether a specialization name is already in use.
    //
    // Called before creating a new specialization to prevent duplicates.
    //
    // $excludeId: when editing, pass the current specialization's ID
    //             so its own name is not flagged as a duplicate.
    // --------------------------------------------------------
    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   specializations
             WHERE  name = ?
             AND    id   != ?",
            "si",
            [$name, $excludeId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // create()
    // Insert a new specialization into the database.
    //
    // Returns: new specialization ID, or 0 on failure.
    // --------------------------------------------------------
    public function create(string $name): int
    {
        $result = $this->execute(
            "INSERT INTO specializations (name)
             VALUES                      (?)",
            "s",
            [$name]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update()
    // Update the name of an existing specialization.
    //
    // Returns: true on success, false on failure.
    // --------------------------------------------------------
    public function update(int $id, string $name): bool
    {
        return (bool) $this->execute(
            "UPDATE specializations
             SET    name = ?
             WHERE  id   = ?",
            "si",
            [$name, $id]
        );
    }


    // --------------------------------------------------------
    // isSafeToDelete()
    // Check whether a specialization can be safely deleted.
    //
    // A specialization is safe to delete only if no doctors are
    // currently assigned to it. This prevents orphaned records.
    //
    // Always call this before delete():
    //   if ($model->isSafeToDelete($id)) {
    //       $model->delete($id);
    //   } else {
    //       flashMessage('Cannot delete — doctors are using this.', 'error');
    //   }
    //
    // Returns: true if safe to delete, false if doctors are assigned.
    // --------------------------------------------------------
    public function isSafeToDelete(int $id): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   doctors
             WHERE  specialization_id = ?",
            "i",
            [$id]
        );

        // If count = 0, no doctors are linked — safe to delete.
        return (int) $count === 0;
    }


    // --------------------------------------------------------
    // getDoctorCount()
    // Return the number of doctors assigned to a specialization.
    //
    // Used to build a meaningful error message like:
    //   "Cannot delete — 5 doctor(s) use this specialization."
    // --------------------------------------------------------
    public function getDoctorCount(int $id): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   doctors
             WHERE  specialization_id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // delete()
    // Permanently delete a specialization by ID.
    //
    // ⚠️  Always call isSafeToDelete() before this method.
    //
    // Returns: true on success, false on failure.
    // --------------------------------------------------------
    public function delete(int $id): bool
    {
        return (bool) $this->execute(
            "DELETE FROM specializations
             WHERE       id = ?",
            "i",
            [$id]
        );
    }
}