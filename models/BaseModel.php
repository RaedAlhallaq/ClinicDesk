<?php
// ============================================================
// models/BaseModel.php
// The parent class for all models in the project.
//
// Concepts applied:
// 1. Abstract Class -> Cannot be instantiated directly.
// 2. Inheritance    -> All other models extend this base class.
// 3. Encapsulation  -> Protected methods only accessible to child models.
// ============================================================

abstract class BaseModel
{
    // The shared database connection instance.
    // Protected so child models can use it (e.g. $this->db->query(...)).
    protected Database $db;

    // Initialize the database connection.
    // Called automatically when a child model is instantiated.
    public function __construct()
    {
        // Uses the Singleton pattern to ensure we don't open multiple 
        // database connections per page load.
        $this->db = Database::getInstance();
    }

    // Execute a SQL query securely using prepared statements.
    // Centralized here so all models can safely query the database.
    //
    // $sql    -> The SQL query string with '?' placeholders
    // $types  -> The data types for binding (e.g., 'i' for integer, 's' for string)
    // $params -> The actual values to bind
    protected function execute(
        string $sql,
        string $types = '',
        array  $params = []
    ): mysqli_result|bool {
        return $this->db->query($sql, $types, $params);
    }

    // Fetch multiple rows from a SELECT query as an array of associative arrays.
    protected function fetchAll(
        string $sql,
        string $types = '',
        array  $params = []
    ): array {
        $result = $this->execute($sql, $types, $params);

        if (!$result instanceof mysqli_result) {
            return [];
        }

        // fetch_all(MYSQLI_ASSOC) fetches all rows at once as key-value pairs
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch a single row from a SELECT query.
    // Useful for finding a specific record by ID or Email.
    protected function fetchOne(
        string $sql,
        string $types = '',
        array  $params = []
    ): ?array {
        $result = $this->execute($sql, $types, $params);

        if (!$result instanceof mysqli_result) {
            return null;
        }

        // fetch_assoc() returns the next row as an associative array
        $row = $result->fetch_assoc();

        return $row ?: null;
    }

    // Fetch a single column value from the first row.
    // Useful for aggregate queries like COUNT(*) or MAX().
    protected function fetchColumn(
        string $sql,
        string $types = '',
        array  $params = []
    ): mixed {
        $result = $this->execute($sql, $types, $params);

        if (!$result instanceof mysqli_result) {
            return null;
        }

        // MYSQLI_NUM returns a simple numbered array
        $row = $result->fetch_array(MYSQLI_NUM);

        // Return the first column of the first row
        return $row ? $row[0] : null;
    }

    // Get the ID generated from the most recent INSERT operation.
    protected function lastInsertId(): int
    {
        return $this->db->lastInsertId();
    }

    // Get the number of rows affected by the most recent UPDATE or DELETE.
    protected function affectedRows(): int
    {
        return $this->db->affectedRows();
    }
}