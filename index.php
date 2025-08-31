<?php
// Connection parameters
$host = 'localhost';
$dbname = 'naif';
$username = 'root';
$password = '';

// Step 1: Connect to MySQL server without specifying a database
$dsn_no_db = "mysql:host=$host";
try {
    $db_no_db = new PDO($dsn_no_db, $username, $password);
    // Step 2: Create the database if it does not exist
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
    $db_no_db->exec($sql);

    // Step 3: Connect to the newly created (or existing) database
    $dsn = "mysql:host=$host;dbname=$dbname";
    try {
        $db = new PDO($dsn, $username, $password);
        echo "Connection successful";
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage() . "<br>";
        echo "Retrying...<br>";
        // Step 4: Retry the connection once more
        try {
            $db = new PDO($dsn, $username, $password);
            echo "Connection successful after retry";
        } catch (PDOException $e2) {
            echo "Connection failed again: " . $e2->getMessage();
            // You can terminate the script here or handle the error as needed
        }
    }
} catch (PDOException $e) {
    echo "Failed to connect to the server or create the database: " . $e->getMessage();
}
?>