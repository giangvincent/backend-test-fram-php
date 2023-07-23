<?php

spl_autoload_register(function ($class) {
    // Convert the namespace and class name to a file path
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';

    // Check if the file exists before including it
    if (file_exists($file)) {
        require_once $file;
    }
});
