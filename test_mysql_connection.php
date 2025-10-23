<?php
// Test MySQL connection for phpMyAdmin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing MySQL Connection ===\n\n";

// Test 1: MySQLi connection
echo "Test 1: MySQLi connection\n";
$mysqli = new mysqli('localhost', 'root', 'P@master5007');
if ($mysqli->connect_error) {
    echo "FAILED: " . $mysqli->connect_error . "\n";
} else {
    echo "SUCCESS: Connected via MySQLi\n";
    echo "Server version: " . $mysqli->server_info . "\n";
    $mysqli->close();
}
echo "\n";

// Test 2: PDO connection
echo "Test 2: PDO connection\n";
try {
    $pdo = new PDO('mysql:host=localhost', 'root', 'P@master5007');
    echo "SUCCESS: Connected via PDO\n";
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Create database test
echo "Test 3: Create database test\n";
try {
    $pdo = new PDO('mysql:host=localhost', 'root', 'P@master5007');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS test_db_temp");
    echo "SUCCESS: Database created\n";
    $pdo->exec("DROP DATABASE IF EXISTS test_db_temp");
    echo "SUCCESS: Database dropped\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
?>
