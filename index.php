<?php

require_once 'vendor/autoload.php';
use api\EmployeeHierarchyAPI;

// Instantiate the API class and handle the request
$api = new EmployeeHierarchyAPI();
$api->handleRequest();
