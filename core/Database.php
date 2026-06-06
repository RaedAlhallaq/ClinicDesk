<?php
// ============================================================
// core/Database.php
// Database connection manager using the Singleton pattern.
//
// Principles applied:
// 1. Singleton Pattern -> Ensures only one database connection per request.
// 2. Prepared Statements -> Provides full protection against SQL Injection.
// ============================================================

class Database
{
    private static ?Database $instance = null;
    private mysqli $conn;
    
    private function __construct()
    {
        // Disable default mysqli errors so we can handle them safely.
        mysqli_report(MYSQLI_REPORT_OFF);

        // Open database connection using credentials from config/database.php.
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check for connection failure.
        if ($this->conn->connect_error) {
            // IMPORTANT: Never show actual database errors to the user.
            // Log the real error securely and show a generic message.
            error_log('Database connection failed: ' . $this->conn->connect_error);
            throw new RuntimeException('Database connection failed. Please try again later.');
        }

        // Set the character encoding for the connection to support special characters.
        $this->conn->set_charset(DB_CHARSET);
    }

    private function __clone() {}

    // Get the single instance of the database connection.
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Execute a secure SQL query using prepared statements.
    public function query(string $sql, string $types = '', array $params = []): mysqli_result|bool
    {
        // Step 1: Prepare the SQL query.
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log('Query prepare failed: ' . $this->conn->error . ' | SQL: ' . $sql);
            throw new RuntimeException('Query execution failed.');
        }

        // Step 2: Bind parameters if they exist to prevent SQL injection.
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        // Step 3: Execute the prepared query.
        $stmt->execute();

        // Step 4: Return the appropriate result.
        $result = $stmt->get_result();

        // If not a SELECT query (e.g. INSERT, UPDATE, DELETE), return boolean success.
        if ($result === false) {
            return $stmt->affected_rows >= 0;
        }

        // If SELECT query, return the fetched results.
        return $result;
    }

    // Get the ID of the last inserted record.
    public function lastInsertId(): int
    {
        return $this->conn->insert_id;
    }

    // Get the number of rows affected by the last query.
    public function affectedRows(): int
    {
        return $this->conn->affected_rows;
    }

    // Close the database connection when the object is destroyed.
    public function __destruct()
    {
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            $this->conn->close();
        }
    }
}