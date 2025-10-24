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
 * List all databases (supports localhost with default credentials)
 */
function listDatabases() {
    // Try to use provided credentials first, fallback to localhost defaults
    $host = $_POST['db_host'] ?? 'localhost';
    $username = $_POST['db_user'] ?? 'root';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    
    // For list_databases, we connect without specifying a database
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server: ' . $e->getMessage());
        return;
    }

    try {
        $stmt = $conn->query('SHOW DATABASES');
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filter out system databases
        $systemDatabases = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        $databases = array_values(array_diff($databases, $systemDatabases));
        
        sendResponse(true, 'Databases retrieved successfully', ['databases' => $databases, 'count' => count($databases)]);
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
 * Create a new database (supports localhost with default credentials)
 */
function createDatabase($dbName, $username = '', $password = '') {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name. Use only alphanumeric characters, underscores, and hyphens.');
    }

    // Get server credentials from POST (for localhost connection)
    $serverHost = $_POST['server_host'] ?? 'localhost';
    $serverUser = $_POST['server_user'] ?? 'root';
    $serverPass = $_POST['server_pass'] ?? '';
    $serverPort = $_POST['server_port'] ?? '3306';

    // Connect to MySQL server (without specifying database)
    try {
        $dsn = "mysql:host={$serverHost};port={$serverPort};charset=utf8mb4";
        $conn = new PDO($dsn, $serverUser, $serverPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server: ' . $e->getMessage());
        return;
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
 * Delete a database (supports localhost with default credentials)
 */
function deleteDatabase($dbName) {
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    // Get server credentials from POST (for localhost connection)
    $serverHost = $_POST['server_host'] ?? 'localhost';
    $serverUser = $_POST['server_user'] ?? 'root';
    $serverPass = $_POST['server_pass'] ?? '';
    $serverPort = $_POST['server_port'] ?? '3306';

    // Connect to MySQL server (without specifying database)
    try {
        $dsn = "mysql:host={$serverHost};port={$serverPort};charset=utf8mb4";
        $conn = new PDO($dsn, $serverUser, $serverPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server: ' . $e->getMessage());
        return;
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
 * Rename a database (supports localhost with default credentials)
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

    // Get server credentials from POST (for localhost connection)
    $serverHost = $_POST['server_host'] ?? 'localhost';
    $serverUser = $_POST['server_user'] ?? 'root';
    $serverPass = $_POST['server_pass'] ?? '';
    $serverPort = $_POST['server_port'] ?? '3306';

    // Connect to MySQL server (without specifying database)
    try {
        $dsn = "mysql:host={$serverHost};port={$serverPort};charset=utf8mb4";
        $conn = new PDO($dsn, $serverUser, $serverPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database server: ' . $e->getMessage());
        return;
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
 * List all tables in a database with Hostinger credentials
 */
function listTables() {
    // Get Hostinger credentials from POST
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

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database. Please check your credentials.');
    }

    try {
        $stmt = $conn->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendResponse(true, 'Tables retrieved successfully', ['tables' => $tables, 'count' => count($tables)]);
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
 * Create a table with Hostinger credentials
 */
function createTable() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $columnsJson = $_POST['columns'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name. Use only alphanumeric characters and underscores.');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database. Please check your credentials.');
        return;
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
                } elseif (strtoupper($col['defaultValue']) === 'CURRENT_TIMESTAMP') {
                    $colDef .= ' DEFAULT CURRENT_TIMESTAMP';
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
 * Delete a table with Hostinger credentials
 */
function deleteTable() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
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
 * Rename a table with Hostinger credentials
 */
function renameTable() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $oldName = $_POST['old_table_name'] ?? '';
    $newName = $_POST['new_table_name'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($oldName) || empty($newName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    if (!validateTableName($oldName) || !validateTableName($newName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name(s)');
        return;
    }

    if ($oldName === $newName) {
        http_response_code(400);
        sendResponse(false, 'New name must be different from old name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
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
 * Alter table (add, modify, or drop column) with Hostinger credentials
 */
function alterTable() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $action = $_POST['alter_action'] ?? '';
    $columnDataJson = $_POST['column_data'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
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
                    } elseif (strtoupper($columnData['defaultValue']) === 'CURRENT_TIMESTAMP') {
                        $colDef .= ' DEFAULT CURRENT_TIMESTAMP';
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
                    } elseif (strtoupper($columnData['defaultValue']) === 'CURRENT_TIMESTAMP') {
                        $colDef .= ' DEFAULT CURRENT_TIMESTAMP';
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
 * Get table structure (columns info) with Hostinger credentials
 */
function getTableStructure() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
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

/**
 * Get table data with pagination
 */
function getTableData() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $page = intval($_POST['page'] ?? 1);
    $limit = intval($_POST['limit'] ?? 50);

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        
        // Get total rows count
        $countStmt = $conn->query("SELECT COUNT(*) as total FROM $safeTableName");
        $totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate pagination
        $totalPages = ceil($totalRows / $limit);
        $offset = ($page - 1) * $limit;
        
        // Get table columns
        $columnsStmt = $conn->query("SHOW COLUMNS FROM $safeTableName");
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get table data with limit
        $dataStmt = $conn->query("SELECT * FROM $safeTableName LIMIT $limit OFFSET $offset");
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get table info
        $infoStmt = $conn->query("SHOW TABLE STATUS WHERE Name = " . $conn->quote($tableName));
        $tableInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Table data retrieved successfully', [
            'table_name' => $tableName,
            'columns' => $columns,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_rows' => $totalRows,
                'per_page' => $limit,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $totalRows)
            ],
            'table_info' => [
                'engine' => $tableInfo['Engine'] ?? 'Unknown',
                'collation' => $tableInfo['Collation'] ?? 'Unknown',
                'rows' => $tableInfo['Rows'] ?? 0,
                'avg_row_length' => $tableInfo['Avg_row_length'] ?? 0,
                'data_length' => $tableInfo['Data_length'] ?? 0,
                'created' => $tableInfo['Create_time'] ?? null,
                'updated' => $tableInfo['Update_time'] ?? null
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error retrieving table data: ' . $e->getMessage());
    }
}

/**
 * Generate SQL for table structure
 */
function generateTableSQL() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $includeData = $_POST['include_data'] ?? 'false';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        
        // Get CREATE TABLE statement
        $createStmt = $conn->query("SHOW CREATE TABLE $safeTableName");
        $createResult = $createStmt->fetch(PDO::FETCH_ASSOC);
        $createTableSQL = $createResult['Create Table'] ?? '';
        
        // Start building the SQL
        $sql = "-- Table structure for `{$tableName}`\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n\n";
        $sql .= $createTableSQL . ";\n\n";
        
        // Get data if requested
        $dataSQL = '';
        $rowCount = 0;
        
        if ($includeData === 'true') {
            // Get all rows
            $dataStmt = $conn->query("SELECT * FROM $safeTableName");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($rows);
            
            if ($rowCount > 0) {
                // Get column names
                $columnsStmt = $conn->query("SHOW COLUMNS FROM $safeTableName");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $dataSQL = "-- Dumping data for table `{$tableName}`\n";
                $dataSQL .= "-- {$rowCount} rows\n\n";
                
                // Build INSERT statements (batch by 100 rows)
                $batchSize = 100;
                $batches = array_chunk($rows, $batchSize);
                
                foreach ($batches as $batch) {
                    $dataSQL .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                    
                    $values = [];
                    foreach ($batch as $row) {
                        $rowValues = [];
                        foreach ($columns as $col) {
                            $value = $row[$col];
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $conn->quote($value);
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    
                    $dataSQL .= implode(",\n", $values) . ";\n\n";
                }
            }
        }
        
        $fullSQL = $sql . $dataSQL;
        
        sendResponse(true, 'SQL generated successfully', [
            'sql' => $fullSQL,
            'table_name' => $tableName,
            'has_data' => $includeData === 'true',
            'row_count' => $rowCount,
            'sql_length' => strlen($fullSQL)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error generating SQL: ' . $e->getMessage());
    }
}

/**
 * Generate full database SQL dump
 */
function generateDatabaseSQL() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $includeCreateDB = $_POST['include_create_db'] ?? 'false';
    $includeData = $_POST['include_data'] ?? 'false';

    if (empty($host) || empty($dbName) || empty($username)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $sql = "-- =====================================================\n";
        $sql .= "-- Full Database SQL Dump\n";
        $sql .= "-- Database: `{$dbName}`\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Host: {$host}\n";
        $sql .= "-- =====================================================\n\n";

        // Add CREATE DATABASE if requested
        if ($includeCreateDB === 'true') {
            $sql .= "-- Create database\n";
            $sql .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
            $sql .= "USE `{$dbName}`;\n\n";
        } else {
            $sql .= "-- Make sure you're using the correct database\n";
            $sql .= "-- USE `{$dbName}`;\n\n";
        }

        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        // Get all tables
        $tablesStmt = $conn->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $totalTables = count($tables);
        $totalRows = 0;

        if ($totalTables === 0) {
            $sql .= "-- No tables found in database\n";
            sendResponse(true, 'SQL generated successfully (empty database)', [
                'sql' => $sql,
                'database_name' => $dbName,
                'total_tables' => 0,
                'total_rows' => 0,
                'sql_length' => strlen($sql),
                'has_create_db' => $includeCreateDB === 'true',
                'has_data' => false
            ]);
            return;
        }

        // Loop through each table
        foreach ($tables as $index => $table) {
            $safeTableName = sanitizeDatabaseName($table);
            
            $sql .= "-- =====================================================\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- =====================================================\n\n";
            
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n\n";
            
            // Get CREATE TABLE statement
            $createStmt = $conn->query("SHOW CREATE TABLE $safeTableName");
            $createResult = $createStmt->fetch(PDO::FETCH_ASSOC);
            $sql .= $createResult['Create Table'] . ";\n\n";
            
            // Add data if requested
            if ($includeData === 'true') {
                // Get row count
                $countStmt = $conn->query("SELECT COUNT(*) as total FROM $safeTableName");
                $rowCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                $totalRows += $rowCount;
                
                if ($rowCount > 0) {
                    $sql .= "-- Dumping data for table `{$table}`\n";
                    $sql .= "-- {$rowCount} rows\n\n";
                    
                    // Get all data
                    $dataStmt = $conn->query("SELECT * FROM $safeTableName");
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($rows)) {
                        // Get column names from first row
                        $columns = array_keys($rows[0]);
                        
                        // Build INSERT statements (batch by 100 rows)
                        $batchSize = 100;
                        $batches = array_chunk($rows, $batchSize);
                        
                        foreach ($batches as $batch) {
                            $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                            
                            $values = [];
                            foreach ($batch as $row) {
                                $rowValues = [];
                                foreach ($columns as $col) {
                                    $value = $row[$col];
                                    if ($value === null) {
                                        $rowValues[] = 'NULL';
                                    } else {
                                        $rowValues[] = $conn->quote($value);
                                    }
                                }
                                $values[] = '(' . implode(', ', $rowValues) . ')';
                            }
                            
                            $sql .= implode(",\n", $values) . ";\n\n";
                        }
                    }
                } else {
                    $sql .= "-- Table is empty\n\n";
                }
            }
            
            // Add separator between tables
            if ($index < $totalTables - 1) {
                $sql .= "\n";
            }
        }

        $sql .= "-- =====================================================\n";
        $sql .= "-- End of dump\n";
        $sql .= "-- =====================================================\n";

        sendResponse(true, 'Database SQL generated successfully', [
            'sql' => $sql,
            'database_name' => $dbName,
            'total_tables' => $totalTables,
            'total_rows' => $totalRows,
            'sql_length' => strlen($sql),
            'has_create_db' => $includeCreateDB === 'true',
            'has_data' => $includeData === 'true'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error generating database SQL: ' . $e->getMessage());
    }
}

/**
 * Insert random data into table
 */
function insertRandomData() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $recordsData = $_POST['records_data'] ?? '';
    $recordCount = intval($_POST['record_count'] ?? 10);

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        // Parse records data
        $records = json_decode($recordsData, true);
        if (!$records || !is_array($records) || empty($records)) {
            http_response_code(400);
            sendResponse(false, 'Invalid records data');
            return;
        }

        $safeTableName = sanitizeTableName($tableName);
        
        // Get column names from first record
        $columns = array_keys($records[0]);
        
        // Build and execute INSERT statements (batch by 50)
        $batchSize = 50;
        $batches = array_chunk($records, $batchSize);
        $insertedCount = 0;
        
        foreach ($batches as $batch) {
            $sql = "INSERT INTO $safeTableName (`" . implode('`, `', $columns) . "`) VALUES ";
            
            $values = [];
            foreach ($batch as $record) {
                $rowValues = [];
                foreach ($columns as $col) {
                    $value = $record[$col];
                    if ($value === null || $value === 'NULL') {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = $conn->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $sql .= implode(', ', $values);
            $conn->exec($sql);
            $insertedCount += count($batch);
        }
        
        sendResponse(true, "Successfully inserted {$insertedCount} random records into table '{$tableName}'", [
            'inserted_count' => $insertedCount
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error inserting random data: ' . $e->getMessage());
    }
}

/**
 * Insert new record into table
 */
function insertRecord() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $recordData = $_POST['record_data'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName) || empty($recordData)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $data = json_decode($recordData, true);
        if (!$data) {
            http_response_code(400);
            sendResponse(false, 'Invalid record data');
            return;
        }

        $safeTableName = sanitizeTableName($tableName);
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO $safeTableName (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($data));
        
        sendResponse(true, 'Record inserted successfully', ['insert_id' => $conn->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error inserting record: ' . $e->getMessage());
    }
}

/**
 * Update existing record
 */
function updateRecord() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $recordData = $_POST['record_data'] ?? '';
    $primaryKey = $_POST['primary_key'] ?? '';
    $primaryValue = $_POST['primary_value'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName) || empty($recordData) || empty($primaryKey) || empty($primaryValue)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $data = json_decode($recordData, true);
        if (!$data) {
            http_response_code(400);
            sendResponse(false, 'Invalid record data');
            return;
        }

        $safeTableName = sanitizeTableName($tableName);
        $safePrimaryKey = sanitizeTableName($primaryKey);
        
        $setParts = [];
        $values = [];
        foreach ($data as $col => $val) {
            $setParts[] = sanitizeTableName($col) . ' = ?';
            $values[] = $val;
        }
        $values[] = $primaryValue;
        
        $sql = "UPDATE $safeTableName SET " . implode(', ', $setParts) . " WHERE $safePrimaryKey = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);
        
        sendResponse(true, 'Record updated successfully');
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error updating record: ' . $e->getMessage());
    }
}

/**
 * Delete record from table
 */
function deleteRecord() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $primaryKey = $_POST['primary_key'] ?? '';
    $primaryValue = $_POST['primary_value'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName) || empty($primaryKey) || empty($primaryValue)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        $safePrimaryKey = sanitizeTableName($primaryKey);
        
        $sql = "DELETE FROM $safeTableName WHERE $safePrimaryKey = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$primaryValue]);
        
        sendResponse(true, 'Record deleted successfully');
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error deleting record: ' . $e->getMessage());
    }
}

/**
 * Search records in table
 */
function searchRecords() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $tableName = $_POST['table_name'] ?? '';
    $searchTerm = $_POST['search_term'] ?? '';
    $page = intval($_POST['page'] ?? 1);
    $limit = intval($_POST['limit'] ?? 50);

    if (empty($host) || empty($dbName) || empty($username) || empty($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateTableName($tableName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid table name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        $safeTableName = sanitizeTableName($tableName);
        
        // Get table columns
        $columnsStmt = $conn->query("SHOW COLUMNS FROM $safeTableName");
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($searchTerm)) {
            // No search term, return all
            $countStmt = $conn->query("SELECT COUNT(*) as total FROM $safeTableName");
            $totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $offset = ($page - 1) * $limit;
            $dataStmt = $conn->query("SELECT * FROM $safeTableName LIMIT $limit OFFSET $offset");
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Build search query (search in all text columns)
            $searchColumns = [];
            foreach ($columns as $col) {
                $type = strtoupper($col['Type']);
                if (strpos($type, 'VARCHAR') !== false || strpos($type, 'TEXT') !== false || strpos($type, 'CHAR') !== false) {
                    $searchColumns[] = sanitizeTableName($col['Field']);
                }
            }
            
            if (empty($searchColumns)) {
                sendResponse(false, 'No searchable text columns found in table');
                return;
            }
            
            $searchConditions = array_map(function($col) {
                return "$col LIKE ?";
            }, $searchColumns);
            
            $whereClause = '(' . implode(' OR ', $searchConditions) . ')';
            $searchPattern = '%' . $searchTerm . '%';
            $searchParams = array_fill(0, count($searchColumns), $searchPattern);
            
            // Count results
            $countSql = "SELECT COUNT(*) as total FROM $safeTableName WHERE $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($searchParams);
            $totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data
            $offset = ($page - 1) * $limit;
            $dataSql = "SELECT * FROM $safeTableName WHERE $whereClause LIMIT $limit OFFSET $offset";
            $dataStmt = $conn->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $totalPages = ceil($totalRows / $limit);
        
        sendResponse(true, 'Records retrieved successfully', [
            'columns' => $columns,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_rows' => $totalRows,
                'per_page' => $limit,
                'showing_from' => (($page - 1) * $limit) + 1,
                'showing_to' => min($page * $limit, $totalRows)
            ],
            'search_term' => $searchTerm
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error searching records: ' . $e->getMessage());
    }
}

/**
 * Execute custom SQL query
 */
function executeCustomSQL() {
    $host = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $sqlQuery = $_POST['sql_query'] ?? '';

    if (empty($host) || empty($dbName) || empty($username) || empty($sqlQuery)) {
        http_response_code(400);
        sendResponse(false, 'Missing required parameters');
        return;
    }

    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }

    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }

    try {
        // Trim and clean the query
        $sqlQuery = trim($sqlQuery);
        
        // Detect query type
        $queryType = 'UNKNOWN';
        $upperQuery = strtoupper(substr($sqlQuery, 0, 20));
        
        if (strpos($upperQuery, 'SELECT') === 0) {
            $queryType = 'SELECT';
        } elseif (strpos($upperQuery, 'INSERT') === 0) {
            $queryType = 'INSERT';
        } elseif (strpos($upperQuery, 'UPDATE') === 0) {
            $queryType = 'UPDATE';
        } elseif (strpos($upperQuery, 'DELETE') === 0) {
            $queryType = 'DELETE';
        } elseif (strpos($upperQuery, 'CREATE') === 0) {
            $queryType = 'CREATE';
        } elseif (strpos($upperQuery, 'DROP') === 0) {
            $queryType = 'DROP';
        } elseif (strpos($upperQuery, 'ALTER') === 0) {
            $queryType = 'ALTER';
        } elseif (strpos($upperQuery, 'SHOW') === 0) {
            $queryType = 'SHOW';
        } elseif (strpos($upperQuery, 'DESCRIBE') === 0 || strpos($upperQuery, 'DESC') === 0) {
            $queryType = 'DESCRIBE';
        }

        // Execute query
        $stmt = $conn->query($sqlQuery);
        
        $responseData = [
            'query_type' => $queryType,
            'executed_query' => $sqlQuery
        ];

        // Handle different query types
        if ($queryType === 'SELECT' || $queryType === 'SHOW' || $queryType === 'DESCRIBE') {
            // Fetch results
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($results);
            
            // Get column info if results exist
            $columns = [];
            if ($rowCount > 0) {
                $columns = array_keys($results[0]);
            }
            
            $responseData['results'] = $results;
            $responseData['columns'] = $columns;
            $responseData['row_count'] = $rowCount;
            $responseData['message'] = "Query executed successfully. {$rowCount} row(s) returned.";
        } elseif ($queryType === 'INSERT') {
            $affectedRows = $stmt->rowCount();
            $lastId = $conn->lastInsertId();
            
            $responseData['affected_rows'] = $affectedRows;
            $responseData['insert_id'] = $lastId;
            $responseData['message'] = "Query executed successfully. {$affectedRows} row(s) inserted. Last insert ID: {$lastId}";
        } elseif ($queryType === 'UPDATE' || $queryType === 'DELETE') {
            $affectedRows = $stmt->rowCount();
            
            $responseData['affected_rows'] = $affectedRows;
            $responseData['message'] = "Query executed successfully. {$affectedRows} row(s) affected.";
        } else {
            // DDL queries (CREATE, DROP, ALTER, etc.)
            $responseData['message'] = "Query executed successfully.";
        }

        sendResponse(true, $responseData['message'], $responseData);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'SQL Error: ' . $e->getMessage(), [
            'error_code' => $e->getCode(),
            'executed_query' => $sqlQuery
        ]);
    }
}

/**
 * Export connections to JSON file
 */
function exportConnections() {
    $connections = $_POST['connections'] ?? '';
    
    if (empty($connections)) {
        http_response_code(400);
        sendResponse(false, 'No connections data provided');
    }

    // Store directory path - relative to Backend-Hostinger.php file
    $storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'Store';
    
    // Create Store directory if it doesn't exist
    if (!file_exists($storeDir)) {
        if (!mkdir($storeDir, 0777, true)) {
            http_response_code(500);
            sendResponse(false, 'Failed to create Store directory: ' . $storeDir);
        }
    }

    // Generate filename with timestamp
    $filename = 'hostinger_connections_' . date('Y-m-d_H-i-s') . '.json';
    $filepath = $storeDir . DIRECTORY_SEPARATOR . $filename;

    // Validate and decode JSON
    $connectionsArray = json_decode($connections, true);
    if ($connectionsArray === null) {
        http_response_code(400);
        sendResponse(false, 'Invalid JSON data');
    }

    // Add export metadata
    $exportData = [
        'exported_at' => date('Y-m-d H:i:s'),
        'total_connections' => count($connectionsArray),
        'connections' => $connectionsArray
    ];

    // Write to file
    $jsonData = json_encode($exportData, JSON_PRETTY_PRINT);
    if (file_put_contents($filepath, $jsonData) !== false) {
        sendResponse(true, 'Connections exported successfully', [
            'filename' => $filename,
            'filepath' => $filepath,
            'total' => count($connectionsArray)
        ]);
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to write export file');
    }
}

/**
 * Import connections from JSON file
 */
function importConnections() {
    $filename = $_POST['filename'] ?? '';
    
    if (empty($filename)) {
        http_response_code(400);
        sendResponse(false, 'No filename provided');
    }

    // Store directory path - relative to Backend-Hostinger.php file
    $storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'Store';
    $filepath = $storeDir . DIRECTORY_SEPARATOR . $filename;

    // Check if file exists
    if (!file_exists($filepath)) {
        http_response_code(404);
        sendResponse(false, 'Import file not found: ' . $filename);
    }

    // Read file
    $jsonData = file_get_contents($filepath);
    if ($jsonData === false) {
        http_response_code(500);
        sendResponse(false, 'Failed to read import file');
    }

    // Parse JSON
    $importData = json_decode($jsonData, true);
    if ($importData === null) {
        http_response_code(400);
        sendResponse(false, 'Invalid JSON format in import file');
    }

    // Validate structure
    if (!isset($importData['connections']) || !is_array($importData['connections'])) {
        http_response_code(400);
        sendResponse(false, 'Invalid import file structure');
    }

    sendResponse(true, 'Connections imported successfully', [
        'connections' => $importData['connections'],
        'total' => count($importData['connections']),
        'exported_at' => $importData['exported_at'] ?? 'Unknown'
    ]);
}

/**
 * List available import files
 */
function listImportFiles() {
    // Store directory path - relative to Backend-Hostinger.php file
    $storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'Store';
    
    // Check if directory exists
    if (!file_exists($storeDir)) {
        sendResponse(true, 'No import files found', ['files' => []]);
        return;
    }

    // Get all JSON files
    $files = glob($storeDir . DIRECTORY_SEPARATOR . 'hostinger_connections_*.json');
    
    if ($files === false || empty($files)) {
        sendResponse(true, 'No import files found', ['files' => []]);
        return;
    }

    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    // Prepare file list
    $fileList = [];
    foreach ($files as $filepath) {
        $filename = basename($filepath);
        $filesize = filesize($filepath);
        $modified = date('Y-m-d H:i:s', filemtime($filepath));
        
        // Try to read file to get connection count
        $jsonData = file_get_contents($filepath);
        $data = json_decode($jsonData, true);
        $connectionCount = isset($data['connections']) ? count($data['connections']) : 0;
        
        $fileList[] = [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $filesize,
            'size_formatted' => number_format($filesize / 1024, 2) . ' KB',
            'modified' => $modified,
            'connection_count' => $connectionCount
        ];
    }

    sendResponse(true, 'Import files retrieved successfully', [
        'files' => $fileList,
        'total' => count($fileList)
    ]);
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
        listTables();
        break;

    case 'create_table':
        createTable();
        break;

    case 'delete_table':
        deleteTable();
        break;

    case 'rename_table':
        renameTable();
        break;

    case 'alter_table':
        alterTable();
        break;

    case 'get_table_structure':
        getTableStructure();
        break;

    case 'export_connections':
        exportConnections();
        break;

    case 'import_connections':
        importConnections();
        break;

    case 'list_import_files':
        listImportFiles();
        break;

    case 'get_table_data':
        getTableData();
        break;

    case 'generate_table_sql':
        generateTableSQL();
        break;

    case 'generate_database_sql':
        generateDatabaseSQL();
        break;

    case 'insert_random_data':
        insertRandomData();
        break;

    case 'insert_record':
        insertRecord();
        break;

    case 'update_record':
        updateRecord();
        break;

    case 'delete_record':
        deleteRecord();
        break;

    case 'search_records':
        searchRecords();
        break;

    case 'execute_sql':
        executeCustomSQL();
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action specified. Supported actions: check_connection, list_databases, connect_database, create_database, delete_database, rename_database, set_database_credentials, list_tables, create_table, delete_table, rename_table, alter_table, get_table_structure, export_connections, import_connections, list_import_files, get_table_data, generate_table_sql, generate_database_sql, insert_random_data, insert_record, update_record, delete_record, search_records, execute_sql');
}
?>
