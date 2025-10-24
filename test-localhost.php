<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo json_encode([
    'status' => 'PHP is working',
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mysqli' => extension_loaded('mysqli')
    ]
]);

// Test localhost connection
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\n\n✅ Localhost connection successful with root (no password)!\n";
    
    // Get databases
    $stmt = $pdo->query('SHOW DATABASES');
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Databases found: " . implode(', ', $databases);
    
} catch (PDOException $e) {
    echo "\n\n❌ Connection failed: " . $e->getMessage();
}
