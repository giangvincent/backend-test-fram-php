<?php

namespace api;

use Dotenv\Dotenv;

class DatabaseManager
{
    private $db;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // Assuming the .env file is in the project root directory
        $dotenv->load();

        $dbHost = $_ENV['MYSQL_HOST'];
        $dbPort = $_ENV['MYSQL_PORT'];
        $dbName = $_ENV['MYSQL_DATABASE'];
        $dbUser = $_ENV['MYSQL_USER'];
        $dbPass = $_ENV['MYSQL_PASSWORD'];
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";

        try {
            $this->db = new \PDO($dsn, $dbUser, $dbPass);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    private function createTables(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS employee_hierarchy (
                    employee VARCHAR(255) PRIMARY KEY,
                    supervisor VARCHAR(255),
                    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                  )";
        try {
            $this->db->exec($query);
        } catch (\PDOException $e) {
            die("Error creating table: " . $e->getMessage());
        }
    }

    public function addEmployeeHierarchy($data): array
    {
        $values = [];
        $messages = [];

        $countEmptyData = 0;
        foreach ($data as $employee => $supervisor) {
            if (!$employee || !$supervisor) {
                $countEmptyData++;
                continue;
            }
            $values[] = [$employee, $supervisor];
        }

        if ($countEmptyData > 0) {
            $messages[] = "Found $countEmptyData empty relationship";
        }

        $placeholders = implode(', ', array_fill(0, count($values), '(?, ?, CURRENT_TIMESTAMP)'));
        $query = "INSERT IGNORE INTO employee_hierarchy (employee, supervisor, inserted_at) VALUES $placeholders";
        try {
            $stmt = $this->db->prepare($query);
            foreach ($values as $index => $row) {
                $stmt->bindValue(($index * 2) + 1, $row[0]);
                $stmt->bindValue(($index * 2) + 2, $row[1]);
            }
            $stmt->execute();
            $insertedCount = $stmt->rowCount();
            $stmt->closeCursor();

            if ($insertedCount === 0) {
                $messages[] = "No new data to insert.";
            } else {
                $messages[] = "Inserted $insertedCount employee hierarchy data.";
            }

            // Check for loops or multiple roots
            if ($insertedCount && !$this->validateHierarchy()) {
                // If validation fails, remove the inserted data
                $this->db->exec("DELETE FROM employee_hierarchy WHERE inserted_at = CURRENT_TIMESTAMP");
                $messages[] = "Invalid employee hierarchy. Loops roots found. Deleted $insertedCount employee";
            }
        } catch (\PDOException $e) {
            $messages[] = "Error storing hierarchy data.";
        }

        return $messages;
    }

    private function findLoops($employee, &$visited, &$recStack): bool
    {
        if (!isset($visited[$employee])) {
            $visited[$employee] = true;
            $recStack[$employee] = true;

            $query = "SELECT supervisor FROM employee_hierarchy WHERE employee = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$employee]);
            $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($result as $supervisor) {
                if ((!isset($visited[$supervisor]) && $this->findLoops($supervisor, $visited, $recStack)) || $recStack[$supervisor]) {
                    return true;
                }
            }
        }

        $recStack[$employee] = false;
        return false;
    }

    public function validateHierarchy(): bool
    {
        // Check for loops
        $query = "SELECT employee FROM employee_hierarchy";
        $stmt = $this->db->query($query);
        $employees = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $visited = [];
        $recStack = [];

        foreach ($employees as $employee) {
            if ($this->findLoops($employee, $visited, $recStack)) {
                return false;
            }
        }

        return true;
    }

    private function buildHierarchy($supervisor, $hierarchy): array
    {
        $query = "SELECT employee FROM employee_hierarchy WHERE supervisor = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$supervisor]);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($result as $employee) {
            $hierarchy[$employee] = $this->buildHierarchy($employee, []);
        }

        return $hierarchy;
    }

    public function getEmployeeHierarchy(): array
    {
        $query = "SELECT DISTINCT supervisor FROM employee_hierarchy WHERE supervisor NOT IN (SELECT employee FROM employee_hierarchy)";
        $stmt = $this->db->query($query);
        $supervisors = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $hierarchy = [];

        foreach ($supervisors as $supervisor) {
            $hierarchy[$supervisor] = $this->buildHierarchy($supervisor, []);
        }

        return $hierarchy;
    }

    private function getSupervisor($employee): array
    {
        $query = "SELECT supervisor FROM employee_hierarchy WHERE employee = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$employee]);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($result)) {
            return []; // Employee not found
        }

        return $result;
    }

    public function getSupervisorHierarchy($employee): array
    {
        $supervisorHierarchy = [];
        while ($supervisor = $this->getSupervisor($employee)) {
            $supervisorHierarchy[] = $supervisor[0];
            $employee = $supervisor[0];
        }
        return $supervisorHierarchy;
    }
}
