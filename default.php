<?php
/**
 * =================================================
 * UNIVERSAL BACKEND API - Default Endpoint
 * =================================================
 * Comprehensive REST API for any frontend application
 * Handles all CRUD operations, database management, and dynamic requests
 * 
 * Features:
 * - Dynamic database/table operations
 * - Full CRUD (Create, Read, Update, Delete)
 * - Table management (create, alter, drop)
 * - High performance with prepared statements
 * - Secure input validation
 * - JSON responses
 * 
 * Usage from Frontend:
 * POST request with JSON body containing 'action' parameter
 * =================================================
 */

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disabled for production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Default Database Configuration
 * Can be overridden by frontend request
 */
$DEFAULT_CONFIG = [
    'host' => 'srv1788.hstgr.io',
    'dbname' => 'u419999707_victorystreaml',
    'username' => 'u419999707_victorystreaml',
    'password' => 'P@master5007',
    'port' => '3306',
    'charset' => 'utf8mb4'
];

/**
 * ========================================
 * CORE FUNCTIONS
 * ========================================
 */

/**
 * Send JSON response and exit
 */
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
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
 * Get database connection
 */
function getConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        if (!empty($config['dbname'])) {
            $dsn .= ";dbname={$config['dbname']}";
        }
        
        // Handle empty password - PDO needs null, not empty string
        $password = ($config['password'] === '' || $config['password'] === null) ? null : $config['password'];
        
        // Create PDO connection
        if ($password === null) {
            // Connect without password
            $pdo = new PDO($dsn, $config['username']);
        } else {
            // Connect with password
            $pdo = new PDO($dsn, $config['username'], $password);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        return $pdo;
    } catch (PDOException $e) {
        jsonResponse(false, 'Database connection failed: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Sanitize table/column names (prevent SQL injection)
 */
function sanitizeName($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

/**
 * Validate required parameters
 */
function validateParams($params, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($params[$field]) || $params[$field] === '') {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        jsonResponse(false, 'Missing required parameters: ' . implode(', ', $missing), null, 400);
    }
}

/**
 * ========================================
 * DATABASE OPERATIONS
 * ========================================
 */

// Create Database
function createDatabase($pdo, $dbName) {
    $dbName = sanitizeName($dbName);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    return "Database '$dbName' created successfully";
}

// Drop Database
function dropDatabase($pdo, $dbName) {
    $dbName = sanitizeName($dbName);
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    return "Database '$dbName' dropped successfully";
}

// List Databases
function listDatabases($pdo) {
    $stmt = $pdo->query("SHOW DATABASES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get Database Info
function getDatabaseInfo($pdo, $dbName) {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $versionInfo = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbName'");
    $tableCount = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
        FROM information_schema.tables 
        WHERE table_schema = '$dbName'
    ");
    $dbSize = $stmt->fetch();
    
    return [
        'database_name' => $dbName,
        'mysql_version' => $versionInfo['version'],
        'table_count' => (int)$tableCount['table_count'],
        'database_size_mb' => $dbSize['size_mb'] ?? 0
    ];
}

/**
 * ========================================
 * TABLE OPERATIONS
 * ========================================
 */

// Create Table
function createTable($pdo, $tableName, $columns) {
    $tableName = sanitizeName($tableName);
    
    if (empty($columns) || !is_array($columns)) {
        throw new Exception("Columns definition required");
    }
    
    $columnDefs = [];
    foreach ($columns as $col) {
        $name = sanitizeName($col['name']);
        $type = $col['type'];
        $extra = $col['extra'] ?? '';
        $columnDefs[] = "`$name` $type $extra";
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(', ', $columnDefs) . ")";
    $pdo->exec($sql);
    
    return "Table '$tableName' created successfully";
}

// Drop Table
function dropTable($pdo, $tableName) {
    $tableName = sanitizeName($tableName);
    $pdo->exec("DROP TABLE IF EXISTS `$tableName`");
    return "Table '$tableName' dropped successfully";
}

// List Tables
function listTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Describe Table Structure
function describeTable($pdo, $tableName) {
    $tableName = sanitizeName($tableName);
    $stmt = $pdo->query("DESCRIBE `$tableName`");
    return $stmt->fetchAll();
}

// Truncate Table
function truncateTable($pdo, $tableName) {
    $tableName = sanitizeName($tableName);
    $pdo->exec("TRUNCATE TABLE `$tableName`");
    return "Table '$tableName' truncated successfully";
}

/**
 * ========================================
 * CRUD OPERATIONS
 * ========================================
 */

// CREATE - Insert record
function insertRecord($pdo, $tableName, $data) {
    $tableName = sanitizeName($tableName);
    
    if (empty($data)) {
        throw new Exception("No data provided for insert");
    }
    
    $columns = array_keys($data);
    $columns = array_map('sanitizeName', $columns);
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    return [
        'inserted_id' => $pdo->lastInsertId(),
        'affected_rows' => $stmt->rowCount(),
        'message' => 'Record inserted successfully'
    ];
}

// READ - Select records
function selectRecords($pdo, $tableName, $conditions = [], $limit = null, $offset = null, $orderBy = null) {
    $tableName = sanitizeName($tableName);
    
    $sql = "SELECT * FROM `$tableName`";
    $params = [];
    
    // WHERE conditions
    if (!empty($conditions)) {
        $whereClauses = [];
        foreach ($conditions as $field => $value) {
            $field = sanitizeName($field);
            $whereClauses[] = "`$field` = ?";
            $params[] = $value;
        }
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    
    // ORDER BY
    if ($orderBy) {
        $orderField = sanitizeName($orderBy['field'] ?? 'id');
        $orderDir = strtoupper($orderBy['direction'] ?? 'ASC');
        $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'ASC';
        $sql .= " ORDER BY `$orderField` $orderDir";
    }
    
    // LIMIT and OFFSET
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
        if ($offset) {
            $sql .= " OFFSET " . (int)$offset;
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

// UPDATE - Update records
function updateRecord($pdo, $tableName, $data, $conditions) {
    $tableName = sanitizeName($tableName);
    
    if (empty($data)) {
        throw new Exception("No data provided for update");
    }
    
    if (empty($conditions)) {
        throw new Exception("No conditions provided for update (safety measure)");
    }
    
    // SET clause
    $setClauses = [];
    $params = [];
    foreach ($data as $field => $value) {
        $field = sanitizeName($field);
        $setClauses[] = "`$field` = ?";
        $params[] = $value;
    }
    
    // WHERE clause
    $whereClauses = [];
    foreach ($conditions as $field => $value) {
        $field = sanitizeName($field);
        $whereClauses[] = "`$field` = ?";
        $params[] = $value;
    }
    
    $sql = "UPDATE `$tableName` SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'affected_rows' => $stmt->rowCount(),
        'message' => 'Record updated successfully'
    ];
}

// DELETE - Delete records
function deleteRecord($pdo, $tableName, $conditions) {
    $tableName = sanitizeName($tableName);
    
    if (empty($conditions)) {
        throw new Exception("No conditions provided for delete (safety measure)");
    }
    
    $whereClauses = [];
    $params = [];
    foreach ($conditions as $field => $value) {
        $field = sanitizeName($field);
        $whereClauses[] = "`$field` = ?";
        $params[] = $value;
    }
    
    $sql = "DELETE FROM `$tableName` WHERE " . implode(' AND ', $whereClauses);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'affected_rows' => $stmt->rowCount(),
        'message' => 'Record deleted successfully'
    ];
}

// Custom Query (for advanced operations)
function executeCustomQuery($pdo, $query, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // If it's a SELECT query
    if (stripos(trim($query), 'SELECT') === 0) {
        return $stmt->fetchAll();
    }
    
    return [
        'affected_rows' => $stmt->rowCount(),
        'message' => 'Query executed successfully'
    ];
}

/**
 * ========================================
 * REQUEST HANDLER
 * ========================================
 */

// Initialize config globally for error handling
$config = $DEFAULT_CONFIG;

try {
    // Get request data
    $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Merge with GET/POST for flexibility
    $requestData = array_merge($_GET, $_POST, $requestData);
    
    // Get action
    $action = $requestData['action'] ?? 'test_connection';
    
    // Get database config (use custom or default)
    if (!empty($requestData['db_host'])) $config['host'] = $requestData['db_host'];
    if (!empty($requestData['db_name'])) $config['dbname'] = $requestData['db_name'];
    if (!empty($requestData['db_user'])) $config['username'] = $requestData['db_user'];
    // Handle password - check if key exists, even if empty
    if (isset($requestData['db_pass'])) {
        $config['password'] = $requestData['db_pass'];
    }
    if (!empty($requestData['db_port'])) $config['port'] = $requestData['db_port'];
    
    // Connect to database
    $pdo = getConnection($config);
    
    // Route actions
    switch ($action) {
        
        // ===== DATABASE OPERATIONS =====
        case 'create_database':
            validateParams($requestData, ['db_name']);
            $result = createDatabase($pdo, $requestData['db_name']);
            jsonResponse(true, $result);
            break;
            
        case 'drop_database':
            validateParams($requestData, ['db_name']);
            $result = dropDatabase($pdo, $requestData['db_name']);
            jsonResponse(true, $result);
            break;
            
        case 'list_databases':
            $databases = listDatabases($pdo);
            jsonResponse(true, 'Databases retrieved', ['databases' => $databases]);
            break;
            
        case 'database_info':
            $info = getDatabaseInfo($pdo, $config['dbname']);
            jsonResponse(true, 'Database info retrieved', $info);
            break;
            
        // ===== TABLE OPERATIONS =====
        case 'create_table':
            validateParams($requestData, ['table', 'columns']);
            $result = createTable($pdo, $requestData['table'], $requestData['columns']);
            jsonResponse(true, $result);
            break;
            
        case 'drop_table':
            validateParams($requestData, ['table']);
            $result = dropTable($pdo, $requestData['table']);
            jsonResponse(true, $result);
            break;
            
        case 'list_tables':
            $tables = listTables($pdo);
            jsonResponse(true, 'Tables retrieved', ['tables' => $tables]);
            break;
            
        case 'describe_table':
            validateParams($requestData, ['table']);
            $structure = describeTable($pdo, $requestData['table']);
            jsonResponse(true, 'Table structure retrieved', ['structure' => $structure]);
            break;
            
        case 'truncate_table':
            validateParams($requestData, ['table']);
            $result = truncateTable($pdo, $requestData['table']);
            jsonResponse(true, $result);
            break;
            
        // ===== CRUD OPERATIONS =====
        case 'insert':
        case 'create':
            validateParams($requestData, ['table', 'data']);
            $result = insertRecord($pdo, $requestData['table'], $requestData['data']);
            jsonResponse(true, $result['message'], $result);
            break;
            
        case 'select':
        case 'read':
            validateParams($requestData, ['table']);
            $records = selectRecords(
                $pdo,
                $requestData['table'],
                $requestData['conditions'] ?? [],
                $requestData['limit'] ?? null,
                $requestData['offset'] ?? null,
                $requestData['orderBy'] ?? null
            );
            jsonResponse(true, 'Records retrieved', ['records' => $records, 'count' => count($records)]);
            break;
            
        case 'update':
            validateParams($requestData, ['table', 'data', 'conditions']);
            $result = updateRecord($pdo, $requestData['table'], $requestData['data'], $requestData['conditions']);
            jsonResponse(true, $result['message'], $result);
            break;
            
        case 'delete':
            validateParams($requestData, ['table', 'conditions']);
            $result = deleteRecord($pdo, $requestData['table'], $requestData['conditions']);
            jsonResponse(true, $result['message'], $result);
            break;
            
        // ===== CUSTOM QUERY =====
        case 'custom_query':
            validateParams($requestData, ['query']);
            $result = executeCustomQuery($pdo, $requestData['query'], $requestData['params'] ?? []);
            jsonResponse(true, 'Query executed', ['result' => $result]);
            break;
            
        // ===== GET COMPREHENSIVE DATABASE INFO =====
        case 'get_database_info':
            try {
                $info = getDatabaseInfo($pdo, $config['dbname']);
                
                // Add connection details
                $info['host'] = $config['host'];
                $info['port'] = $config['port'];
                $info['database'] = $config['dbname'];
                $info['username'] = $config['username'];
                $info['password'] = $config['password']; // Include password as requested
                $info['connection_status'] = 'Active ✅';
                $info['php_version'] = phpversion();
                $info['server_info'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
                $info['charset'] = $config['charset'];
                
                // Get additional server information
                try {
                    // Server variables
                    $stmt = $pdo->query("SHOW VARIABLES LIKE 'version_comment'");
                    $row = $stmt->fetch();
                    if ($row) {
                        $info['server_type'] = $row['Value'];
                    }
                    
                    // Collation
                    $stmt = $pdo->query("SHOW VARIABLES LIKE 'collation_database'");
                    $row = $stmt->fetch();
                    if ($row) {
                        $info['collation'] = $row['Value'];
                    }
                    
                    // Max connections
                    $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
                    $row = $stmt->fetch();
                    if ($row) {
                        $info['max_connections'] = $row['Value'];
                    }
                    
                    // Uptime
                    $stmt = $pdo->query("SHOW STATUS LIKE 'Uptime'");
                    $row = $stmt->fetch();
                    if ($row) {
                        $uptimeSeconds = (int)$row['Value'];
                        $days = floor($uptimeSeconds / 86400);
                        $hours = floor(($uptimeSeconds % 86400) / 3600);
                        $minutes = floor(($uptimeSeconds % 3600) / 60);
                        $info['server_uptime'] = "{$days}d {$hours}h {$minutes}m";
                        $info['server_uptime_seconds'] = $uptimeSeconds;
                    }
                    
                    // Connection ID
                    $stmt = $pdo->query("SELECT CONNECTION_ID()");
                    $info['connection_id'] = $stmt->fetchColumn();
                    
                    // Current database
                    $stmt = $pdo->query("SELECT DATABASE()");
                    $info['current_database'] = $stmt->fetchColumn();
                    
                    // Time zone
                    $stmt = $pdo->query("SELECT @@system_time_zone, @@time_zone");
                    $row = $stmt->fetch();
                    if ($row) {
                        $info['system_time_zone'] = $row['@@system_time_zone'];
                        $info['session_time_zone'] = $row['@@time_zone'];
                    }
                    
                } catch (Exception $e) {
                    // Ignore if can't get additional info
                }
                
                // Get table list (safely)
                try {
                    $info['tables'] = listTables($pdo);
                    $info['table_count'] = count($info['tables']);
                } catch (Exception $e) {
                    $info['tables'] = [];
                    $info['table_count'] = 0;
                    $info['tables_error'] = $e->getMessage();
                }
                
                // Get total rows and detailed table info
                $totalRows = 0;
                $tableDetails = [];
                $engineCount = [];
                
                if (!empty($info['tables'])) {
                    foreach ($info['tables'] as $table) {
                        try {
                            $safeTable = sanitizeName($table);
                            
                            // Get row count
                            $stmt = $pdo->query("SELECT COUNT(*) FROM `$safeTable`");
                            $count = $stmt->fetchColumn();
                            $totalRows += $count;
                            
                            // Get table info (engine, collation, size)
                            $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$safeTable'");
                            $tableInfo = $stmt->fetch();
                            
                            if ($tableInfo) {
                                $engine = $tableInfo['Engine'] ?? 'Unknown';
                                $engineCount[$engine] = ($engineCount[$engine] ?? 0) + 1;
                                
                                $tableDetails[] = [
                                    'name' => $table,
                                    'rows' => $count,
                                    'engine' => $engine,
                                    'collation' => $tableInfo['Collation'] ?? 'Unknown',
                                    'size_mb' => round(($tableInfo['Data_length'] + $tableInfo['Index_length']) / 1048576, 2)
                                ];
                            }
                        } catch (Exception $e) {
                            // Skip tables that can't be accessed
                        }
                    }
                }
                
                $info['total_rows'] = $totalRows;
                $info['table_details'] = $tableDetails;
                $info['storage_engines'] = $engineCount;
                
                // Calculate total database size
                $totalSize = array_sum(array_column($tableDetails, 'size_mb'));
                $info['total_database_size_mb'] = round($totalSize, 2);
                
                // Connection timestamp
                $info['connection_timestamp'] = date('Y-m-d H:i:s');
                $info['api_endpoint'] = $_SERVER['PHP_SELF'] ?? 'default.php';
                
                jsonResponse(true, '✅ Database connection successful!', $info);
            } catch (Exception $e) {
                jsonResponse(false, '❌ Error getting database info: ' . $e->getMessage(), [
                    'error' => $e->getMessage(),
                    'config' => [
                        'host' => $config['host'],
                        'database' => $config['dbname'],
                        'username' => $config['username']
                    ]
                ], 500);
            }
            break;
        
        // ===== TEST CONNECTION (Default) =====
        case 'test_connection':
        default:
            $info = getDatabaseInfo($pdo, $config['dbname']);
            jsonResponse(true, '✅ Database connection successful!', $info);
            break;
    }
    
} catch (PDOException $e) {
    $errorData = [
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
        'attempted_connection' => [
            'host' => $config['host'] ?? 'Not specified',
            'database' => $config['dbname'] ?? 'Not specified',
            'username' => $config['username'] ?? 'Not specified',
            'port' => $config['port'] ?? '3306'
        ]
    ];
    jsonResponse(false, 'Database error: ' . $e->getMessage(), $errorData, 500);
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), ['error_type' => get_class($e)], 400);
}
