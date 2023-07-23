<?php

namespace api;

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
        $data = json_decode($json);
        $result = $databaseManager->addEmployeeHierarchy($data);

        // Assert
        $this->assertStringContainsString('Inserted', json_encode($result));
    }

    public function testAddEmployeeHierarchy_InvalidHierarchy_ReturnsErrorMessage()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas",
            "Jonas": "Pete"
        }';

        $databaseManager = new DatabaseManager();

        // Act
        $data = json_decode($json);
        $result = $databaseManager->addEmployeeHierarchy($data);

        // Assert
        $this->assertStringContainsString('Invalid employee hierarchy', json_encode($result));
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
        $data = json_decode($json);
        $result = $databaseManager->addEmployeeHierarchy($data);

        // Assert
        $this->assertStringContainsString('Inserted', json_encode($result));

        // Now, try adding an invalid hierarchy to trigger deletion
        $invalidJson = '{
            "Jonas": "Sophie",
            "Sophie": "Nick",
            "Nick": "Pete",
            "Pete": "Barbara",
            "Barbara": "Jonas"
        }';

        $data = json_decode($invalidJson);
        $result = $databaseManager->addEmployeeHierarchy($data);

        // Assert
        $this->assertStringContainsString('Invalid employee hierarchy', json_encode($result));
        $this->assertStringContainsString('Deleted', json_encode($result));
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
        $data = json_decode($json);
        $databaseManager = new DatabaseManager();

        // Act
        $result1 = $databaseManager->addEmployeeHierarchy($data);

        // Add the same JSON again
        $result2 = $databaseManager->addEmployeeHierarchy($data);

        // Assert
        $this->assertStringContainsString('Inserted', json_encode($result1));
        $this->assertStringContainsString('No new data to insert', json_encode($result2));
    }

    public function testGetEmployeeHierarchy_ValidData_ReturnsCorrectHierarchy()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';
        $data = json_decode($json);

        $databaseManager = new DatabaseManager();
        $databaseManager->addEmployeeHierarchy($data);

        // Act
        $result = $databaseManager->getEmployeeHierarchy();

        // Assert
        $expectedResult = [
            "Sophie" => [
                "Nick" => [
                    "Pete" => [],
                    "Barbara" => []
                ]
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetEmployeeHierarchy_InvalidEmployee_ReturnsEmptyArray()
    {
        // Arrange
        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->getEmployeeHierarchy();

        // Assert
        $this->assertEmpty($result);
    }

    public function testGetSupervisorHierarchy_ValidData_ReturnsCorrectHierarchy()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';
        $data = json_decode($json);

        $databaseManager = new DatabaseManager();
        $databaseManager->addEmployeeHierarchy($data);

        // Act
        $result = $databaseManager->getSupervisorHierarchy("Pete");

        // Assert
        $expectedResult = [
            "Jonas" => [
                "Sophie" => [
                    "Nick" => []
                ]
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetSupervisorHierarchy_InvalidEmployee_ReturnsEmptyArray()
    {
        // Arrange
        $databaseManager = new DatabaseManager();

        // Act
        $result = $databaseManager->getSupervisorHierarchy("NonExistentEmployee");

        // Assert
        $this->assertEmpty($result);
    }
}
