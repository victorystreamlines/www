<?php
// Test admin user connection and database creation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Admin User ===\n\n";

// Test 1: Connection
echo "Test 1: Admin user connection\n";
try {
    $pdo = new PDO('mysql:host=localhost', 'admin', 'Admin@5007');
    echo "SUCCESS: Connected as admin user\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Create database
echo "\nTest 2: Create database as admin\n";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS admin_test_db");
    echo "SUCCESS: Database created\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// Test 3: Use database and create table
echo "\nTest 3: Create table in database\n";
try {
    $pdo->exec("USE admin_test_db");
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(100))");
    echo "SUCCESS: Table created\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// Test 4: Insert data
echo "\nTest 4: Insert data\n";
try {
    $pdo->exec("INSERT INTO test_table (id, name) VALUES (1, 'Test Entry')");
    echo "SUCCESS: Data inserted\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// Test 5: Cleanup
echo "\nTest 5: Cleanup\n";
try {
    $pdo->exec("DROP DATABASE IF EXISTS admin_test_db");
    echo "SUCCESS: Database dropped\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Passed ===\n";
?>
