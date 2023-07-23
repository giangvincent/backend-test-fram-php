<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use api\DatabaseManager;

class DatabaseManagerTest extends TestCase
{
    public function testAddEmployeeHierarchy_ValidJSON_InsertsData()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';

        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->addEmployeeHierarchy($json);

        // Assert
        $this->assertStringContainsString('Inserted', $result);
    }

    public function testAddEmployeeHierarchy_InvalidJSON_ReturnsErrorMessage()
    {
        // Arrange
        $invalidJson = 'This is not a valid JSON';

        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->addEmployeeHierarchy($invalidJson);

        // Assert
        $this->assertStringContainsString('Invalid JSON format', $result);
    }

    public function testAddEmployeeHierarchy_InvalidHierarchy_ReturnsErrorMessage()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas",
            "Jonas": "Pete" // Adding loop to create an invalid hierarchy
        }';

        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->addEmployeeHierarchy($json);

        // Assert
        $this->assertStringContainsString('Invalid employee hierarchy', $result);
    }

    public function testAddEmployeeHierarchy_ValidHierarchy_DeletesInsertedDataOnFailure()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';

        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->addEmployeeHierarchy($json);

        // Assert
        $this->assertStringContainsString('Inserted', $result);

        // Now, try adding an invalid hierarchy to trigger deletion
        $invalidJson = '{
            "Jonas": "Sophie",
            "Sophie": "Nick",
            "Nick": "Pete",
            "Pete": "Barbara",
            "Barbara": "Jonas" // Adding loop to create an invalid hierarchy
        }';

        $result = $databaseManager->addEmployeeHierarchy($invalidJson);

        // Assert
        $this->assertStringContainsString('Invalid employee hierarchy', $result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testAddEmployeeHierarchy_DuplicateData_IgnoresDuplicates()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';

        $databaseManager = new DatabaseManager();

        // Act
        $result1 = $databaseManager->addEmployeeHierarchy($json);

        // Add the same JSON again
        $result2 = $databaseManager->addEmployeeHierarchy($json);

        // Assert
        $this->assertStringContainsString('Inserted', $result1);
        $this->assertStringContainsString('No new data to insert', $result2);
    }
}
