<?php
/**
 * Database connection class for local development
 * This file provides the same interface as the production db.php
 */

class db {
    private $host;
    private $user;
    private $pass;
    private $name;
    private $connection;
    private $lastResult;

    public function __construct($host, $user, $pass, $name) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->name = $name;

        // Create connection
        $this->connection = new mysqli($host, $user, $pass, $name);

        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function query($sql, ...$params) {
        if (empty($params)) {
            $this->lastResult = $this->connection->query($sql);
        } else {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $this->lastResult = $stmt->get_result();
            $stmt->close();
        }

        if (!$this->lastResult) {
            throw new Exception("Query failed: " . $this->connection->error);
        }

        return $this->lastResult;
    }

    public function fetchArray() {
        if ($this->lastResult) {
            return $this->lastResult->fetch_assoc();
        }
        return false;
    }

    public function fetchAll() {
        if ($this->lastResult) {
            $results = [];
            while ($row = $this->lastResult->fetch_assoc()) {
                $results[] = $row;
            }
            return $results;
        }
        return [];
    }

    public function numRows() {
        if ($this->lastResult) {
            return $this->lastResult->num_rows;
        }
        return 0;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
