<?php

namespace api;

class DatabaseManager
{
    private $db;

    public function __construct()
    {
        $host = '127.0.0.1:3306'; // Replace with your MySQL host address
        $user = 'admin'; // Replace with your MySQL username
        $password = 'abcd1234'; // Replace with your MySQL password
        $dbName = 'backend_test_1';

        $this->db = new \mysqli($host, $user, $password, $dbName);

        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }

        $this->createTables();
    }

    private function createTables(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS employee_hierarchy (
                    employee VARCHAR(255) PRIMARY KEY,
                    supervisor VARCHAR(255),
                    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                  )";
        if (!$this->db->query($query)) {
            die("Error creating table: " . $this->db->error);
        }
    }

    public function addEmployeeHierarchy($data): array|string
    {
        $values = [];
        $messages = [];

        $countEmptyData = 0;
        foreach ($data as $employee => $supervisor) {
            if (!$employee || !$supervisor) {
                $countEmptyData++;
                continue;
            }
            $values[] = "('$employee', '$supervisor', CURRENT_TIMESTAMP)";
        }

        if ($countEmptyData > 0) {
            $messages[] = "Found $countEmptyData empty relationship";
        }
        $valuesString = implode(', ', $values);

        $query = "INSERT IGNORE INTO employee_hierarchy (employee, supervisor, inserted_at) VALUES $valuesString";
        if (!$this->db->query($query)) {
            $messages[] = "Error storing hierarchy data.";
        }

        $insertedCount = $this->db->affected_rows;

        if ($insertedCount === 0) {
            $messages[] = "No new data to insert.";
        } else {
            $messages[] = "Inserted $insertedCount employee hierarchy data.";
        }

        // Check for loops or multiple roots
        if ($insertedCount && !$this->validateHierarchy()) {
            // If validation fails, remove the inserted data
            $query = "DELETE FROM employee_hierarchy WHERE inserted_at = CURRENT_TIMESTAMP";
            if (!$this->db->query($query)) {
                $messages[] = "Error deleting inserted data.";
            }
            $messages[] = "Invalid employee hierarchy. Loops roots found. Deleted $insertedCount employee";
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
            $stmt->bind_param('s', $employee);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $supervisor = $row['supervisor'];

                if (
                    (!isset($visited[$supervisor]) && $this->findLoops($supervisor, $visited, $recStack)) ||
                    $recStack[$supervisor]
                ) {
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
        $result = $this->db->query($query);
        $employees = [];

        while ($row = $result->fetch_assoc()) {
            $employees[] = $row['employee'];
        }

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
        $stmt->bind_param('s', $supervisor);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $employee = $row['employee'];
            $hierarchy[$employee] = $this->buildHierarchy($employee, []);
        }

        return $hierarchy;
    }

    public function getEmployeeHierarchy(): array
    {
        $query = "SELECT DISTINCT supervisor FROM employee_hierarchy WHERE supervisor NOT IN (SELECT employee FROM employee_hierarchy)";
        $result = $this->db->query($query);
        $hierarchy = [];

        while ($row = $result->fetch_assoc()) {
            $supervisor = $row['supervisor'];
            $hierarchy[$supervisor] = $this->buildHierarchy($supervisor, []);
        }

        return $hierarchy;
    }

    private function getSupervisor($employee): array
    {
        $query = "SELECT supervisor FROM employee_hierarchy WHERE employee = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $employee);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return []; // Employee not found
        }

        $row = $result->fetch_assoc();
        $supervisor = $row['supervisor'];

        if (!$supervisor) {
            return []; // No supervisor for the employee
        }

        return [$supervisor];
    }

    public function getSupervisorHierarchy($employee): array
    {
        $supervisorHierarchy = [];

        $employee = [$employee];
        while ($supervisor = $this->getSupervisor($employee[0])) {
            $supervisorHierarchy[] = $supervisor[0];
            $employee = $supervisor;
        }
        return $supervisorHierarchy;
    }
}
