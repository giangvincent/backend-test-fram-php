-- Create the database
CREATE DATABASE IF NOT EXISTS backend_test_2;

-- Use the database
USE backend_test_2;

-- Create tables
CREATE TABLE IF NOT EXISTS employee_hierarchy (
    employee VARCHAR(255) PRIMARY KEY,
    supervisor VARCHAR(255),
    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
