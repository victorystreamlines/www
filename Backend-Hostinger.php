<?php
/*
HOSTINGER DATABASE CONTROL PANEL - Backend
SERVER: Hostinger (Shared/VPS)
DATABASE_OPERATIONS: Dynamic connection management with Hostinger credentials
TABLE_OPERATIONS: list_tables, create_table, delete_table, rename_table, alter_table, get_table_structure
PHASE: Hostinger Edition
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// No default database configuration - using dynamic Hostinger credentials from requests

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
    // Allow alphanumeric characters, underscores, and hyphens
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        return false;
    }
    // Check length (MySQL max is 64 characters)
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
 * Get database connection with Hostinger credentials
 */
function getConnection($host, $dbName, $username, $password, $port = '3306') {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Check database server connection with Hostinger credentials
 */
function checkConnection() {
    // Get credentials from POST
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';

    if (empty($host) || empty($dbName) || empty($username)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters (host, database name, username)');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if ($conn) {
        // Test a simple query
        try {
            $stmt = $conn->query('SELECT 1');
            sendResponse(true, 'Successfully connected to Hostinger database: ' . $dbName);
        } catch (PDOException $e) {
            http_response_code(500);
            sendResponse(false, 'Connected but query failed: ' . $e->getMessage());
        }
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database. Please check your credentials.');
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
function connectToDatabase($dbName, $username = '', $password = '') {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    // First, check if database exists
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
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error checking database: ' . $e->getMessage());
    }

    // Try to connect with provided credentials or default
    if (!empty($username) && !empty($password)) {
        // Connect with custom credentials
        $dbConn = getConnectionWithCredentials($dbName, $username, $password);
        if ($dbConn) {
            sendResponse(true, "Successfully connected to database: $dbName", [
                'databaseInfo' => ['name' => $dbName, 'authenticated' => true]
            ]);
        } else {
            http_response_code(401);
            sendResponse(false, 'Authentication failed. Invalid username or password.');
        }
    } else {
        // Try default credentials
        $dbConn = getConnection($dbName);
        if ($dbConn) {
            sendResponse(true, "Successfully connected to database: $dbName", [
                'databaseInfo' => ['name' => $dbName, 'authenticated' => false]
            ]);
        } else {
            // Connection failed, might need credentials
            sendResponse(false, 'Connection failed. This database may require authentication.', [
                'requiresCredentials' => true
            ]);
        }
    }
}

/**
 * Create a new database
 */
function createDatabase($dbName, $username = '', $password = '') {
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

        // If username and password provided, create user and grant privileges
        if (!empty($username) && !empty($password)) {
            try {
                // Create user (or update if exists)
                $userHost = 'localhost';
                $stmt = $conn->prepare("CREATE USER IF NOT EXISTS ?@? IDENTIFIED BY ?");
                $stmt->execute([$username, $userHost, $password]);

                // Grant privileges
                $conn->exec("GRANT ALL PRIVILEGES ON $safeName.* TO " . $conn->quote($username) . "@" . $conn->quote($userHost));
                $conn->exec("FLUSH PRIVILEGES");

                sendResponse(true, "Database '$dbName' created successfully with user credentials");
            } catch (PDOException $e) {
                // Database created but user creation failed
                sendResponse(true, "Database '$dbName' created but failed to set credentials: " . $e->getMessage());
            }
        } else {
            sendResponse(true, "Database '$dbName' created successfully");
        }
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
 * MySQL doesn't have RENAME DATABASE, so we create new, copy tables, drop old
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
 * Set database credentials (create user and grant privileges)
 */
function setDatabaseCredentials($dbName, $username, $password) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    if (empty($username) || empty($password)) {
        http_response_code(400);
        sendResponse(false, 'Username and password are required');
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

        // Create user or update password if exists
        $userHost = 'localhost';
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT User FROM mysql.user WHERE User = ? AND Host = ?");
        $stmt->execute([$username, $userHost]);
        $userExists = $stmt->fetch();

        if ($userExists) {
            // Update password
            $conn->exec("ALTER USER " . $conn->quote($username) . "@" . $conn->quote($userHost) . " IDENTIFIED BY " . $conn->quote($password));
        } else {
            // Create user
            $conn->exec("CREATE USER " . $conn->quote($username) . "@" . $conn->quote($userHost) . " IDENTIFIED BY " . $conn->quote($password));
        }

        // Grant privileges
        $safeName = sanitizeDatabaseName($dbName);
        $conn->exec("GRANT ALL PRIVILEGES ON $safeName.* TO " . $conn->quote($username) . "@" . $conn->quote($userHost));
        $conn->exec("FLUSH PRIVILEGES");

        sendResponse(true, "Credentials set successfully for database '$dbName'");
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error setting credentials: ' . $e->getMessage());
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

// Main request handler
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
        $username = $_POST['db_username'] ?? '';
        $password = $_POST['db_password'] ?? '';
        connectToDatabase($dbName, $username, $password);
        break;

    case 'create_database':
        $dbName = $_POST['db_name'] ?? '';
        $username = $_POST['db_username'] ?? '';
        $password = $_POST['db_password'] ?? '';
        createDatabase($dbName, $username, $password);
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

    case 'set_database_credentials':
        $dbName = $_POST['db_name'] ?? '';
        $username = $_POST['db_username'] ?? '';
        $password = $_POST['db_password'] ?? '';
        setDatabaseCredentials($dbName, $username, $password);
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
        sendResponse(false, 'Invalid action specified. Supported actions: check_connection, list_databases, connect_database, create_database, delete_database, rename_database, set_database_credentials, list_tables, create_table, delete_table, rename_table, alter_table, get_table_structure');
}
?>
