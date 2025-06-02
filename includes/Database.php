<?php
require_once __DIR__.'/Config.php';

class Database {
    private $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL error: " . $this->connection->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        return $stmt->affected_rows;
    }

    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function __destruct() {
        $this->connection->close();
    }
}