<?php
/*
HOSTINGER PROXY API
This file handles database operations on Hostinger server
Upload this file along with hostinger_config.php to your Hostinger server
*/

// Load configuration
if (!file_exists('hostinger_config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Configuration file not found. Please create hostinger_config.php from template.'
    ]);
    exit;
}

require_once 'hostinger_config.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===========================
// SECURITY: API KEY VALIDATION
// ===========================
function validateApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
    
    if (empty($apiKey)) {
        http_response_code(401);
        sendResponse(false, 'API key is required');
    }
    
    if ($apiKey !== HOSTINGER_API_KEY) {
        http_response_code(403);
        sendResponse(false, 'Invalid API key');
    }
}

// ===========================
// SECURITY: IP WHITELIST
// ===========================
function checkIpWhitelist() {
    if (empty(HOSTINGER_IP_WHITELIST)) {
        return; // No whitelist, allow all
    }
    
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (!in_array($clientIp, HOSTINGER_IP_WHITELIST)) {
        http_response_code(403);
        sendResponse(false, 'Access denied from your IP address');
    }
}

// ===========================
// SECURITY: RATE LIMITING
// ===========================
function checkRateLimit() {
    if (HOSTINGER_RATE_LIMIT <= 0) {
        return; // Rate limiting disabled
    }
    
    $clientIp = $_SERVER['REMOTE_ADDR'];
    $cacheFile = sys_get_temp_dir() . '/ratelimit_' . md5($clientIp) . '.txt';
    
    $now = time();
    $requests = [];
    
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?? [];
    }
    
    // Remove requests older than 1 minute
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < 60;
    });
    
    // Check if limit exceeded
    if (count($requests) >= HOSTINGER_RATE_LIMIT) {
        http_response_code(429);
        sendResponse(false, 'Rate limit exceeded. Please try again later.');
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
}

// Apply security checks
validateApiKey();
checkIpWhitelist();
checkRateLimit();

// ===========================
// DATABASE FUNCTIONS
// ===========================

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    echo json_encode($response);
    exit;
}

/**
 * Validate database name
 */
function validateDatabaseName($name) {
    if (empty($name)) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        return false;
    }
    if (strlen($name) > 64) {
        return false;
    }
    return true;
}

/**
 * Sanitize database name for queries
 */
function sanitizeDatabaseName($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}

/**
 * Get database connection
 */
function getConnection($dbName = null) {
    try {
        $dsn = 'mysql:host=' . HOSTINGER_DB_HOST;
        if ($dbName) {
            $dsn .= ';dbname=' . $dbName;
        }
        $conn = new PDO($dsn, HOSTINGER_DB_USER, HOSTINGER_DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Check database server connection
 */
function checkConnection() {
    $conn = getConnection();
    if ($conn) {
        sendResponse(true, 'Successfully connected to Hostinger database server');
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to Hostinger database server');
    }
}

/**
 * List all databases
 */
function listDatabases() {
    $conn = getConnection();
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server');
    }

    try {
        $stmt = $conn->query('SHOW DATABASES');
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filter out system databases
        $systemDatabases = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        $databases = array_values(array_diff($databases, $systemDatabases));
        
        sendResponse(true, 'Databases retrieved successfully', ['databases' => $databases]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error retrieving databases: ' . $e->getMessage());
    }
}

/**
 * Connect to a specific database
 */
function connectToDatabase($dbName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    $conn = getConnection();
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server');
    }

    try {
        $stmt = $conn->query('SHOW DATABASES LIKE ' . $conn->quote($dbName));
        $exists = $stmt->fetch();
        
        if (!$exists) {
            http_response_code(404);
            sendResponse(false, 'Database not found');
        }
        
        // Try to connect
        $dbConn = getConnection($dbName);
        if ($dbConn) {
            sendResponse(true, "Successfully connected to database: $dbName", [
                'databaseInfo' => ['name' => $dbName]
            ]);
        } else {
            http_response_code(500);
            sendResponse(false, 'Failed to connect to database');
        }
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error checking database: ' . $e->getMessage());
    }
}

/**
 * Create a new database
 */
function createDatabase($dbName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name. Use only alphanumeric characters, underscores, and hyphens.');
    }

    $conn = getConnection();
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server');
    }

    try {
        // Check if database already exists
        $stmt = $conn->query('SHOW DATABASES LIKE ' . $conn->quote($dbName));
        if ($stmt->fetch()) {
            http_response_code(409);
            sendResponse(false, 'Database already exists');
        }

        // Create database
        $safeName = sanitizeDatabaseName($dbName);
        $conn->exec("CREATE DATABASE $safeName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        sendResponse(true, "Database '$dbName' created successfully on Hostinger");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error creating database: ' . $e->getMessage());
    }
}

/**
 * Delete a database
 */
function deleteDatabase($dbName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    $conn = getConnection();
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server');
    }

    try {
        // Check if database exists
        $stmt = $conn->query('SHOW DATABASES LIKE ' . $conn->quote($dbName));
        if (!$stmt->fetch()) {
            http_response_code(404);
            sendResponse(false, 'Database not found');
        }

        // Drop database
        $safeName = sanitizeDatabaseName($dbName);
        $conn->exec("DROP DATABASE $safeName");

        sendResponse(true, "Database '$dbName' deleted successfully");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error deleting database: ' . $e->getMessage());
    }
}

/**
 * Rename a database
 */
function renameDatabase($oldName, $newName) {
    if (!validateDatabaseName($oldName) || !validateDatabaseName($newName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name(s)');
    }

    if ($oldName === $newName) {
        http_response_code(400);
        sendResponse(false, 'New name must be different from old name');
    }

    $conn = getConnection();
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server');
    }

    try {
        // Check if old database exists
        $stmt = $conn->query('SHOW DATABASES LIKE ' . $conn->quote($oldName));
        if (!$stmt->fetch()) {
            http_response_code(404);
            sendResponse(false, 'Source database not found');
        }

        // Check if new database already exists
        $stmt = $conn->query('SHOW DATABASES LIKE ' . $conn->quote($newName));
        if ($stmt->fetch()) {
            http_response_code(409);
            sendResponse(false, 'Target database already exists');
        }

        // Create new database
        $safeNewName = sanitizeDatabaseName($newName);
        $conn->exec("CREATE DATABASE $safeNewName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Get all tables from old database
        $safeOldName = sanitizeDatabaseName($oldName);
        $stmt = $conn->query("SHOW TABLES FROM $safeOldName");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Rename each table to new database
        foreach ($tables as $table) {
            $safeTable = sanitizeDatabaseName($table);
            $conn->exec("RENAME TABLE $safeOldName.$safeTable TO $safeNewName.$safeTable");
        }

        // Drop old database (now empty)
        $conn->exec("DROP DATABASE $safeOldName");

        sendResponse(true, "Database renamed from '$oldName' to '$newName' successfully");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error renaming database: ' . $e->getMessage());
    }
}

/**
 * List all tables in a database
 */
function listTables($dbName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        $stmt = $conn->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendResponse(true, 'Tables retrieved successfully', ['tables' => $tables]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error retrieving tables: ' . $e->getMessage());
    }
}

/**
 * Validate table name
 */
function validateTableName($name) {
    if (empty($name)) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        return false;
    }
    if (strlen($name) > 64) {
        return false;
    }
    return true;
}

/**
 * Sanitize table name for queries
 */
function sanitizeTableName($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}

/**
 * Create a table
 */
function createTable($dbName, $tableName, $columnsJson) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name. Use only alphanumeric characters and underscores.');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        // Parse columns JSON
        $columns = json_decode($columnsJson, true);
        if (!$columns || !is_array($columns) || empty($columns)) {
            http_response_code(400);
            sendResponse(false, 'Invalid or empty columns definition');
        }

        // Build CREATE TABLE SQL
        $safeTableName = sanitizeTableName($tableName);
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $col) {
            $colName = sanitizeTableName($col['name']);
            $colType = strtoupper($col['type']);
            
            // Add length if provided
            if (!empty($col['length']) && in_array($colType, ['VARCHAR', 'CHAR', 'INT', 'DECIMAL', 'FLOAT', 'DOUBLE'])) {
                $colType .= '(' . intval($col['length']) . ')';
            }
            
            $colDef = "$colName $colType";
            
            // Add NOT NULL if specified
            if (!empty($col['nullable']) && $col['nullable'] === 'no') {
                $colDef .= ' NOT NULL';
            }
            
            // Add AUTO_INCREMENT if specified
            if (!empty($col['autoIncrement']) && $col['autoIncrement'] === 'yes') {
                $colDef .= ' AUTO_INCREMENT';
            }
            
            // Add DEFAULT value if specified
            if (isset($col['defaultValue']) && $col['defaultValue'] !== '') {
                if (strtoupper($col['defaultValue']) === 'NULL') {
                    $colDef .= ' DEFAULT NULL';
                } else {
                    $colDef .= ' DEFAULT ' . $conn->quote($col['defaultValue']);
                }
            }
            
            // Add UNIQUE if specified
            if (!empty($col['unique']) && $col['unique'] === 'yes') {
                $colDef .= ' UNIQUE';
            }
            
            $columnDefs[] = $colDef;
            
            // Track primary keys
            if (!empty($col['primaryKey']) && $col['primaryKey'] === 'yes') {
                $primaryKeys[] = $colName;
            }
        }
        
        // Add PRIMARY KEY constraint
        if (!empty($primaryKeys)) {
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }
        
        $sql = "CREATE TABLE $safeTableName (" . implode(', ', $columnDefs) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        
        $conn->exec($sql);
        
        sendResponse(true, "Table '$tableName' created successfully", ['sql' => $sql]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error creating table: ' . $e->getMessage());
    }
}

/**
 * Delete a table
 */
function deleteTable($dbName, $tableName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        $conn->exec("DROP TABLE $safeTableName");
        
        sendResponse(true, "Table '$tableName' deleted successfully");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error deleting table: ' . $e->getMessage());
    }
}

/**
 * Rename a table
 */
function renameTable($dbName, $oldName, $newName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (!validateTableName($oldName) || !validateTableName($newName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name(s)');
    }

    if ($oldName === $newName) {
        http_response_code(400);
        sendResponse(false, 'New name must be different from old name');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        $safeOldName = sanitizeTableName($oldName);
        $safeNewName = sanitizeTableName($newName);
        
        $conn->exec("RENAME TABLE $safeOldName TO $safeNewName");
        
        sendResponse(true, "Table renamed from '$oldName' to '$newName' successfully");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error renaming table: ' . $e->getMessage());
    }
}

/**
 * Alter table (add, modify, or drop column)
 */
function alterTable($dbName, $tableName, $action, $columnDataJson) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        $columnData = json_decode($columnDataJson, true);
        if (!$columnData) {
            http_response_code(400);
            sendResponse(false, 'Invalid column data');
        }

        $safeTableName = sanitizeTableName($tableName);
        
        switch ($action) {
            case 'add':
                $colName = sanitizeTableName($columnData['name']);
                $colType = strtoupper($columnData['type']);
                
                if (!empty($columnData['length'])) {
                    $colType .= '(' . intval($columnData['length']) . ')';
                }
                
                $colDef = "$colName $colType";
                
                if (!empty($columnData['nullable']) && $columnData['nullable'] === 'no') {
                    $colDef .= ' NOT NULL';
                }
                
                if (isset($columnData['defaultValue']) && $columnData['defaultValue'] !== '') {
                    if (strtoupper($columnData['defaultValue']) === 'NULL') {
                        $colDef .= ' DEFAULT NULL';
                    } else {
                        $colDef .= ' DEFAULT ' . $conn->quote($columnData['defaultValue']);
                    }
                }
                
                $sql = "ALTER TABLE $safeTableName ADD COLUMN $colDef";
                $conn->exec($sql);
                sendResponse(true, "Column added successfully");
                break;
                
            case 'modify':
                $colName = sanitizeTableName($columnData['name']);
                $colType = strtoupper($columnData['type']);
                
                if (!empty($columnData['length'])) {
                    $colType .= '(' . intval($columnData['length']) . ')';
                }
                
                $colDef = "$colName $colType";
                
                if (!empty($columnData['nullable']) && $columnData['nullable'] === 'no') {
                    $colDef .= ' NOT NULL';
                }
                
                if (isset($columnData['defaultValue']) && $columnData['defaultValue'] !== '') {
                    if (strtoupper($columnData['defaultValue']) === 'NULL') {
                        $colDef .= ' DEFAULT NULL';
                    } else {
                        $colDef .= ' DEFAULT ' . $conn->quote($columnData['defaultValue']);
                    }
                }
                
                $sql = "ALTER TABLE $safeTableName MODIFY COLUMN $colDef";
                $conn->exec($sql);
                sendResponse(true, "Column modified successfully");
                break;
                
            case 'drop':
                $colName = sanitizeTableName($columnData['name']);
                $sql = "ALTER TABLE $safeTableName DROP COLUMN $colName";
                $conn->exec($sql);
                sendResponse(true, "Column dropped successfully");
                break;
                
            default:
                http_response_code(400);
                sendResponse(false, 'Invalid alter action. Use: add, modify, or drop');
        }
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error altering table: ' . $e->getMessage());
    }
}

/**
 * Get table structure (columns info)
 */
function getTableStructure($dbName, $tableName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
    }

    $conn = getConnection($dbName);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        $stmt = $conn->query("SHOW COLUMNS FROM $safeTableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Table structure retrieved successfully', ['columns' => $columns]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error retrieving table structure: ' . $e->getMessage());
    }
}

// ===========================
// MAIN REQUEST HANDLER
// ===========================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_connection':
        checkConnection();
        break;

    case 'list_databases':
        listDatabases();
        break;

    case 'connect_database':
        $dbName = $_POST['db_name'] ?? '';
        connectToDatabase($dbName);
        break;

    case 'create_database':
        $dbName = $_POST['db_name'] ?? '';
        createDatabase($dbName);
        break;

    case 'delete_database':
        $dbName = $_POST['db_name'] ?? '';
        deleteDatabase($dbName);
        break;

    case 'rename_database':
        $oldName = $_POST['old_name'] ?? '';
        $newName = $_POST['new_name'] ?? '';
        renameDatabase($oldName, $newName);
        break;

    case 'list_tables':
        $dbName = $_POST['db_name'] ?? '';
        listTables($dbName);
        break;

    case 'create_table':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $columns = $_POST['columns'] ?? '';
        createTable($dbName, $tableName, $columns);
        break;

    case 'delete_table':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        deleteTable($dbName, $tableName);
        break;

    case 'rename_table':
        $dbName = $_POST['db_name'] ?? '';
        $oldName = $_POST['old_table_name'] ?? '';
        $newName = $_POST['new_table_name'] ?? '';
        renameTable($dbName, $oldName, $newName);
        break;

    case 'alter_table':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $action = $_POST['alter_action'] ?? '';
        $columnData = $_POST['column_data'] ?? '';
        alterTable($dbName, $tableName, $action, $columnData);
        break;

    case 'get_table_structure':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        getTableStructure($dbName, $tableName);
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action specified');
}
?>

