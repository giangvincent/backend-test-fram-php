<?php

namespace api;

use api\DatabaseManager;

class EmployeeHierarchyAPI
{
    private $databaseManager;
    private $validTokens;

    public function __construct()
    {
        $this->databaseManager = new DatabaseManager();
        $this->validTokens = ["your_secret_token_here"];
    }

    private function isAuthorized($token): bool
    {
        return in_array($token, $this->validTokens);
    }

    private function validateAuthorizationHeader(): void
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo "Unauthorized: Missing Authorization header.";
            exit();
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        $token = str_replace("Bearer ", "", $authHeader);

        if (!$this->isAuthorized($token)) {
            http_response_code(401);
            echo "Unauthorized: Invalid token.";
            exit();
        }
    }


    public function handleRequest(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateAuthorizationHeader();
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (!$data) {
                return "Invalid JSON format.";
            }
            $response = $this->databaseManager->addEmployeeHierarchy($data);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
            $this->validateAuthorizationHeader();

            if ($_GET['query'] === 'hierarchy') {
                $response = $this->databaseManager->getEmployeeHierarchy();
            } elseif ($_GET['query'] === 'supervisor' && isset($_GET['employee'])) {
                $employee = $_GET['employee'];
                $response = $this->databaseManager->getSupervisorHierarchy($employee);
            } else {
                $response = "Invalid query parameter.";
            }
        } else {
            $response = "Invalid request.";
        }

        return json_encode($response);
    }
}
