<?php
/**
 * =================================================
 * DATABASE API ENDPOINT
 * =================================================
 * This page serves as an API for database connection verification
 * Can be used on Hostinger as an endpoint
 */

// Enable error display for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type as JSON
header('Content-Type: application/json; charset=utf-8');

// Allow CORS from any origin (for development only)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Database connection information
 */
$host = 'srv1788.hstgr.io';
$dbname = 'u419999707_victorystreaml';
$username = 'u419999707_victorystreaml';
$password = 'P@master5007';
$port = '3306';

/**
 * Function to respond in JSON format
 */
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_NAME'] ?? 'localhost'
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Attempt to connect to the database
 */
try {
    // Create DSN for connection
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // Create PDO object
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Test connection by reading MySQL version
    $stmt = $pdo->query('SELECT VERSION() as version');
    $versionInfo = $stmt->fetch();
    
    // Get number of tables in the database
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbname'");
    $tableCount = $stmt->fetch();
    
    // Get database size
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
        FROM information_schema.tables 
        WHERE table_schema = '$dbname'
    ");
    $dbSize = $stmt->fetch();
    
    // Additional connection data
    $connectionData = [
        'database_name' => $dbname,
        'database_host' => $host,
        'database_port' => $port,
        'mysql_version' => $versionInfo['version'],
        'table_count' => (int)$tableCount['table_count'],
        'database_size_mb' => $dbSize['size_mb'] ?? 0,
        'charset' => 'utf8mb4',
        'connection_status' => 'active'
    ];
    
    // Send success response
    jsonResponse(
        true,
        '✅ Database connection successful!',
        $connectionData
    );
    
} catch (PDOException $e) {
    // In case of connection failure
    $errorData = [
        'error_code' => $e->getCode(),
        'error_type' => 'PDOException',
        'attempted_host' => $host,
        'attempted_database' => $dbname
    ];
    
    jsonResponse(
        false,
        '❌ Database connection failed: ' . $e->getMessage(),
        $errorData
    );
    
} catch (Exception $e) {
    // In case of any other error
    jsonResponse(
        false,
        '❌ Unexpected error occurred: ' . $e->getMessage(),
        ['error_type' => 'Exception']
    );
}
