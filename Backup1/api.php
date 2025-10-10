<?php
/*
CONTROL PANEL METADATA (Phase 2 - ENHANCED)
SERVER: localhost
DATABASE_OPERATIONS: list_databases, create_database, delete_database, rename_database, connect_database, set_database_credentials
TABLE_OPERATIONS: list_tables, create_table, edit_table, delete_table, rename_table
*/

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('DB_HOST', '127.0.0.1');
define('DB_ROOT_USER', 'root');
define('DB_ROOT_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDatabaseConnection($dbname = null, $username = null, $password = null) {
    $user = $username ?? DB_ROOT_USER;
    $pass = $password ?? DB_ROOT_PASS;
    
    try {
        $conn = new mysqli(DB_HOST, $user, $pass, $dbname);
        
        if ($conn->connect_error) {
            return false;
        }
        
        $conn->set_charset(DB_CHARSET);
        return $conn;
        
    } catch (Exception $e) {
        return false;
    }
}

function sendResponse($success, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

function sanitizeDatabaseName($name) {
    $name = trim($name);
    
    if (empty($name)) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        return false;
    }
    
    if (strlen($name) > 64) {
        return false;
    }
    
    return $name;
}

function escapeIdentifier($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    case 'check_connection':
        $conn = getDatabaseConnection();
        
        if ($conn) {
            $conn->close();
            sendResponse(true, 'Connected to database server successfully');
        } else {
            sendResponse(false, 'Failed to connect to database server. Check your database configuration.', [], 500);
        }
        break;
    
    case 'list_databases':
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database server', [], 500);
        }
        
        $result = $conn->query("SHOW DATABASES");
        
        if (!$result) {
            $conn->close();
            sendResponse(false, 'Failed to retrieve database list: ' . $conn->error, [], 500);
        }
        
        $databases = [];
        while ($row = $result->fetch_array()) {
            if (!in_array($row[0], ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
                $databases[] = $row[0];
            }
        }
        
        $result->free();
        $conn->close();
        
        sendResponse(true, 'Databases retrieved successfully', ['databases' => $databases]);
        break;
    
    case 'connect_database':
        $dbName = $_POST['db_name'] ?? '';
        $dbUsername = $_POST['db_username'] ?? null;
        $dbPassword = $_POST['db_password'] ?? null;
        
        if (empty($dbName)) {
            sendResponse(false, 'Database name is required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        if (!$dbName) {
            sendResponse(false, 'Invalid database name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName, $dbUsername, $dbPassword);
        
        if ($conn) {
            $info = [
                'databaseInfo' => [
                    'name' => $dbName,
                    'charset' => $conn->character_set_name(),
                    'server_version' => $conn->server_info
                ]
            ];
            $conn->close();
            sendResponse(true, "Connected to database '{$dbName}' successfully", $info);
        } else {
            if ($dbUsername === null) {
                sendResponse(false, 'Database requires authentication', ['requiresCredentials' => true], 401);
            } else {
                sendResponse(false, 'Connection failed. Invalid credentials or database does not exist.', [], 401);
            }
        }
        break;
    
    case 'create_database':
        $dbName = $_POST['db_name'] ?? '';
        $dbUsername = $_POST['db_username'] ?? null;
        $dbPassword = $_POST['db_password'] ?? null;
        
        if (empty($dbName)) {
            sendResponse(false, 'Database name is required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        if (!$dbName) {
            sendResponse(false, 'Invalid database name. Use only alphanumeric characters, underscores, and hyphens.', [], 400);
        }
        
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database server', [], 500);
        }
        
        $escapedName = escapeIdentifier($dbName);
        $checkResult = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($dbName) . "'");
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $conn->close();
            sendResponse(false, "Database '{$dbName}' already exists", [], 409);
        }
        
        $createQuery = "CREATE DATABASE $escapedName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if (!$conn->query($createQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to create database: $error", [], 500);
        }
        
        if ($dbUsername && $dbPassword) {
            $escapedUser = $conn->real_escape_string($dbUsername);
            $escapedPass = $conn->real_escape_string($dbPassword);
            
            $createUserQuery = "CREATE USER IF NOT EXISTS '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPass'";
            if (!$conn->query($createUserQuery)) {
                error_log("Failed to create user: " . $conn->error);
            }
            
            $grantQuery = "GRANT ALL PRIVILEGES ON $escapedName.* TO '$escapedUser'@'localhost'";
            if (!$conn->query($grantQuery)) {
                error_log("Failed to grant privileges: " . $conn->error);
            }
            
            $conn->query("FLUSH PRIVILEGES");
        }
        
        $conn->close();
        sendResponse(true, "Database '{$dbName}' created successfully");
        break;
    
    case 'delete_database':
        $dbName = $_POST['db_name'] ?? '';
        
        if (empty($dbName)) {
            sendResponse(false, 'Database name is required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        if (!$dbName) {
            sendResponse(false, 'Invalid database name', [], 400);
        }
        
        if (in_array($dbName, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
            sendResponse(false, 'Cannot delete system database', [], 403);
        }
        
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database server', [], 500);
        }
        
        $escapedName = escapeIdentifier($dbName);
        $checkResult = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($dbName) . "'");
        
        if (!$checkResult || $checkResult->num_rows === 0) {
            $conn->close();
            sendResponse(false, "Database '{$dbName}' does not exist", [], 404);
        }
        
        $dropQuery = "DROP DATABASE $escapedName";
        
        if (!$conn->query($dropQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to delete database: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Database '{$dbName}' deleted successfully");
        break;
    
    case 'rename_database':
        $oldName = $_POST['old_name'] ?? '';
        $newName = $_POST['new_name'] ?? '';
        
        if (empty($oldName) || empty($newName)) {
            sendResponse(false, 'Both old name and new name are required', [], 400);
        }
        
        $oldName = sanitizeDatabaseName($oldName);
        $newName = sanitizeDatabaseName($newName);
        
        if (!$oldName || !$newName) {
            sendResponse(false, 'Invalid database name. Use only alphanumeric characters, underscores, and hyphens.', [], 400);
        }
        
        if ($oldName === $newName) {
            sendResponse(false, 'New name must be different from old name', [], 400);
        }
        
        if (in_array($oldName, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
            sendResponse(false, 'Cannot rename system database', [], 403);
        }
        
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database server', [], 500);
        }
        
        $checkOld = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($oldName) . "'");
        if (!$checkOld || $checkOld->num_rows === 0) {
            $conn->close();
            sendResponse(false, "Database '{$oldName}' does not exist", [], 404);
        }
        
        $checkNew = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($newName) . "'");
        if ($checkNew && $checkNew->num_rows > 0) {
            $conn->close();
            sendResponse(false, "Database '{$newName}' already exists", [], 409);
        }
        
        $escapedOldName = escapeIdentifier($oldName);
        $escapedNewName = escapeIdentifier($newName);
        
        $createQuery = "CREATE DATABASE $escapedNewName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$conn->query($createQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to create new database: $error", [], 500);
        }
        
        $tablesResult = $conn->query("SHOW TABLES FROM $escapedOldName");
        
        if ($tablesResult && $tablesResult->num_rows > 0) {
            $success = true;
            $errorMsg = '';
            
            while ($row = $tablesResult->fetch_array()) {
                $tableName = $row[0];
                $escapedTableName = escapeIdentifier($tableName);
                
                $renameQuery = "RENAME TABLE $escapedOldName.$escapedTableName TO $escapedNewName.$escapedTableName";
                
                if (!$conn->query($renameQuery)) {
                    $success = false;
                    $errorMsg = $conn->error;
                    break;
                }
            }
            
            if (!$success) {
                $conn->query("DROP DATABASE $escapedNewName");
                $conn->close();
                sendResponse(false, "Failed to rename database: $errorMsg", [], 500);
            }
        }
        
        $dropQuery = "DROP DATABASE $escapedOldName";
        if (!$conn->query($dropQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Tables moved to '{$newName}' but failed to delete old database: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Database renamed from '{$oldName}' to '{$newName}' successfully");
        break;
    
    case 'set_database_credentials':
        $dbName = $_POST['db_name'] ?? '';
        $dbUsername = $_POST['db_username'] ?? '';
        $dbPassword = $_POST['db_password'] ?? '';
        
        if (empty($dbName) || empty($dbUsername) || empty($dbPassword)) {
            sendResponse(false, 'Database name, username, and password are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        if (!$dbName) {
            sendResponse(false, 'Invalid database name', [], 400);
        }
        
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database server', [], 500);
        }
        
        $checkDb = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($dbName) . "'");
        if (!$checkDb || $checkDb->num_rows === 0) {
            $conn->close();
            sendResponse(false, "Database '{$dbName}' does not exist", [], 404);
        }
        
        $escapedName = escapeIdentifier($dbName);
        $escapedUser = $conn->real_escape_string($dbUsername);
        $escapedPass = $conn->real_escape_string($dbPassword);
        
        $createUserQuery = "CREATE USER IF NOT EXISTS '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPass'";
        if (!$conn->query($createUserQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to create user: $error", [], 500);
        }
        
        $alterUserQuery = "ALTER USER '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPass'";
        if (!$conn->query($alterUserQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to update user password: $error", [], 500);
        }
        
        $grantQuery = "GRANT ALL PRIVILEGES ON $escapedName.* TO '$escapedUser'@'localhost'";
        if (!$conn->query($grantQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to grant privileges: $error", [], 500);
        }
        
        $conn->query("FLUSH PRIVILEGES");
        
        $conn->close();
        sendResponse(true, "Credentials set successfully for database '{$dbName}'");
        break;
    
    // ============================================================
    // TABLE OPERATIONS (PHASE 2 - ENHANCED)
    // ============================================================
    
    case 'list_tables':
        $dbName = $_POST['db_name'] ?? '';
        
        if (empty($dbName)) {
            sendResponse(false, 'Database name is required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        if (!$dbName) {
            sendResponse(false, 'Invalid database name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $result = $conn->query("SHOW TABLES");
        
        if (!$result) {
            $conn->close();
            sendResponse(false, 'Failed to retrieve table list: ' . $conn->error, [], 500);
        }
        
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $result->free();
        $conn->close();
        
        sendResponse(true, 'Tables retrieved successfully', ['tables' => $tables]);
        break;
    
    case 'create_table':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $columnsJson = $_POST['columns'] ?? '';
        
        if (empty($dbName) || empty($tableName) || empty($columnsJson)) {
            sendResponse(false, 'Database name, table name, and columns are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        // Decode JSON and check for errors
        $columns = json_decode($columnsJson, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            $errorMsg = 'JSON decode error: ';
            switch ($jsonError) {
                case JSON_ERROR_DEPTH:
                    $errorMsg .= 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $errorMsg .= 'Invalid or malformed JSON';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $errorMsg .= 'Control character error';
                    break;
                case JSON_ERROR_SYNTAX:
                    $errorMsg .= 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $errorMsg .= 'Malformed UTF-8 characters';
                    break;
                default:
                    $errorMsg .= 'Unknown error';
                    break;
            }
            sendResponse(false, $errorMsg, [], 400);
        }
        
        if (!$columns || !is_array($columns) || count($columns) === 0) {
            sendResponse(false, 'Invalid columns data: must be a non-empty array', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        
        // Build column definitions
        $columnDefs = [];
        $primaryKeys = [];
        $uniqueColumns = [];
        $indexColumns = [];
        
        foreach ($columns as $col) {
            // Validate required fields
            if (empty($col['name']) || empty($col['type'])) {
                $conn->close();
                sendResponse(false, 'Each column must have a name and type', [], 400);
            }
            
            $colName = escapeIdentifier($col['name']);
            $colType = strtoupper($col['type']);
            
            // Add length if specified and not empty
            if (isset($col['length']) && $col['length'] !== '' && $col['length'] !== null) {
                $colType .= '(' . $conn->real_escape_string($col['length']) . ')';
            }
            
            $def = "$colName $colType";
            
            // Nullable - default is TRUE if not specified
            $nullable = isset($col['nullable']) ? (bool)$col['nullable'] : true;
            if (!$nullable) {
                $def .= ' NOT NULL';
            }
            
            // Auto increment
            $autoIncrement = isset($col['auto_increment']) ? (bool)$col['auto_increment'] : false;
            if ($autoIncrement) {
                $def .= ' AUTO_INCREMENT';
            }
            
            // Default value - only if not empty and not auto increment
            if (!$autoIncrement && isset($col['default']) && $col['default'] !== '' && $col['default'] !== null) {
                $defaultValue = $col['default'];
                if (strtoupper($defaultValue) === 'NULL') {
                    $def .= ' DEFAULT NULL';
                } elseif (strtoupper($defaultValue) === 'CURRENT_TIMESTAMP') {
                    $def .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $def .= " DEFAULT '" . $conn->real_escape_string($defaultValue) . "'";
                }
            }
            
            $columnDefs[] = $def;
            
            // Track primary keys
            $isPrimary = isset($col['primary_key']) ? (bool)$col['primary_key'] : false;
            if ($isPrimary) {
                $primaryKeys[] = $colName;
            }
            
            // Track unique columns (not primary)
            $isUnique = isset($col['unique']) ? (bool)$col['unique'] : false;
            if ($isUnique && !$isPrimary) {
                $uniqueColumns[] = $col['name'];
            }
            
            // Track index columns (not primary and not unique)
            $isIndex = isset($col['index']) ? (bool)$col['index'] : false;
            if ($isIndex && !$isPrimary && !$isUnique) {
                $indexColumns[] = $col['name'];
            }
        }
        
        // Add primary key constraint
        if (!empty($primaryKeys)) {
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }
        
        $createQuery = "CREATE TABLE $escapedTableName (" . implode(', ', $columnDefs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($createQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to create table: $error", [], 500);
        }
        
        // Add unique indexes
        foreach ($uniqueColumns as $colName) {
            $escapedColName = escapeIdentifier($colName);
            $indexName = escapeIdentifier('idx_unique_' . $colName);
            $conn->query("ALTER TABLE $escapedTableName ADD UNIQUE INDEX $indexName ($escapedColName)");
        }
        
        // Add regular indexes
        foreach ($indexColumns as $colName) {
            $escapedColName = escapeIdentifier($colName);
            $indexName = escapeIdentifier('idx_' . $colName);
            $conn->query("ALTER TABLE $escapedTableName ADD INDEX $indexName ($escapedColName)");
        }
        
        $conn->close();
        sendResponse(true, "Table '{$tableName}' created successfully");
        break;
    
    case 'delete_table':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        
        if (empty($dbName) || empty($tableName)) {
            sendResponse(false, 'Database name and table name are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        
        // Check if table exists
        $checkResult = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
        
        if (!$checkResult || $checkResult->num_rows === 0) {
            $conn->close();
            sendResponse(false, "Table '{$tableName}' does not exist", [], 404);
        }
        
        $dropQuery = "DROP TABLE $escapedTableName";
        
        if (!$conn->query($dropQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to delete table: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Table '{$tableName}' deleted successfully");
        break;
    
    case 'rename_table':
        $dbName = $_POST['db_name'] ?? '';
        $oldTableName = $_POST['old_table_name'] ?? '';
        $newTableName = $_POST['new_table_name'] ?? '';
        
        if (empty($dbName) || empty($oldTableName) || empty($newTableName)) {
            sendResponse(false, 'Database name, old table name, and new table name are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $oldTableName = sanitizeDatabaseName($oldTableName);
        $newTableName = sanitizeDatabaseName($newTableName);
        
        if (!$dbName || !$oldTableName || !$newTableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        if ($oldTableName === $newTableName) {
            sendResponse(false, 'New table name must be different from old name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedOldName = escapeIdentifier($oldTableName);
        $escapedNewName = escapeIdentifier($newTableName);
        
        // Check if old table exists
        $checkOld = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($oldTableName) . "'");
        if (!$checkOld || $checkOld->num_rows === 0) {
            $conn->close();
            sendResponse(false, "Table '{$oldTableName}' does not exist", [], 404);
        }
        
        // Check if new table name already exists
        $checkNew = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($newTableName) . "'");
        if ($checkNew && $checkNew->num_rows > 0) {
            $conn->close();
            sendResponse(false, "Table '{$newTableName}' already exists", [], 409);
        }
        
        $renameQuery = "RENAME TABLE $escapedOldName TO $escapedNewName";
        
        if (!$conn->query($renameQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to rename table: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Table renamed from '{$oldTableName}' to '{$newTableName}' successfully");
        break;
    
    case 'get_table_structure':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        
        if (empty($dbName) || empty($tableName)) {
            sendResponse(false, 'Database name and table name are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        
        $result = $conn->query("DESCRIBE $escapedTableName");
        
        if (!$result) {
            $conn->close();
            sendResponse(false, 'Failed to get table structure: ' . $conn->error, [], 500);
        }
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        $result->free();
        $conn->close();
        
        sendResponse(true, 'Table structure retrieved successfully', ['columns' => $columns]);
        break;
    
    case 'alter_table_add_column':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $columnJson = $_POST['column'] ?? '';
        
        if (empty($dbName) || empty($tableName) || empty($columnJson)) {
            sendResponse(false, 'Database name, table name, and column data are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        // Decode JSON with error checking
        $column = json_decode($columnJson, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            sendResponse(false, 'JSON decode error: ' . json_last_error_msg(), [], 400);
        }
        
        if (!$column || !isset($column['name']) || !isset($column['type'])) {
            sendResponse(false, 'Invalid column data: name and type are required', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        // Build column definition step by step
        $columnName = trim($column['name']);
        $columnType = strtoupper(trim($column['type']));
        
        // Validate column name
        if (empty($columnName) || !preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
            $conn->close();
            sendResponse(false, 'Invalid column name. Use only alphanumeric characters and underscores.', [], 400);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        $escapedColumnName = escapeIdentifier($columnName);
        
        // Build type with length
        $typeDefinition = $columnType;
        if (isset($column['length']) && trim($column['length']) !== '') {
            $length = trim($column['length']);
            // Validate length is numeric or contains comma for DECIMAL
            if (preg_match('/^[0-9,]+$/', $length)) {
                $typeDefinition .= "($length)";
            }
        }
        
        // Start building the column definition
        $columnDefinition = "$escapedColumnName $typeDefinition";
        
        // Handle NULL/NOT NULL - default is NULL (nullable = true)
        $nullable = isset($column['nullable']) ? filter_var($column['nullable'], FILTER_VALIDATE_BOOLEAN) : true;
        $columnDefinition .= $nullable ? ' NULL' : ' NOT NULL';
        
        // Handle default value with type validation
        if (isset($column['default']) && trim($column['default']) !== '') {
            $defaultValue = trim($column['default']);
            
            // Special keywords
            if (strtoupper($defaultValue) === 'CURRENT_TIMESTAMP') {
                // Only valid for TIMESTAMP, DATETIME, and DATE types
                if (in_array($columnType, ['TIMESTAMP', 'DATETIME', 'DATE'])) {
                    $columnDefinition .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $conn->close();
                    sendResponse(false, "CURRENT_TIMESTAMP is only valid for TIMESTAMP, DATETIME, or DATE types, not $columnType", [], 400);
                }
            } elseif (strtoupper($defaultValue) === 'NULL') {
                $columnDefinition .= ' DEFAULT NULL';
            } else {
                // Validate default value based on column type
                $isValid = false;
                $errorMsg = '';
                
                switch ($columnType) {
                    case 'INT':
                    case 'INTEGER':
                    case 'TINYINT':
                    case 'SMALLINT':
                    case 'MEDIUMINT':
                    case 'BIGINT':
                        // Must be numeric
                        if (is_numeric($defaultValue) && preg_match('/^-?\d+$/', $defaultValue)) {
                            $columnDefinition .= " DEFAULT $defaultValue";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for $columnType must be an integer number";
                        }
                        break;
                    
                    case 'DECIMAL':
                    case 'FLOAT':
                    case 'DOUBLE':
                    case 'REAL':
                        // Must be numeric (can have decimals)
                        if (is_numeric($defaultValue)) {
                            $columnDefinition .= " DEFAULT $defaultValue";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for $columnType must be a numeric value";
                        }
                        break;
                    
                    case 'BOOLEAN':
                    case 'BOOL':
                        // Must be 0, 1, true, or false
                        $boolValue = strtolower($defaultValue);
                        if (in_array($boolValue, ['0', '1', 'true', 'false'])) {
                            $numValue = ($boolValue === 'true' || $boolValue === '1') ? '1' : '0';
                            $columnDefinition .= " DEFAULT $numValue";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for BOOLEAN must be 0, 1, true, or false";
                        }
                        break;
                    
                    case 'DATE':
                        // Must be valid date format YYYY-MM-DD
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultValue)) {
                            $escapedDefault = $conn->real_escape_string($defaultValue);
                            $columnDefinition .= " DEFAULT '$escapedDefault'";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for DATE must be in format YYYY-MM-DD (e.g., 2024-01-01)";
                        }
                        break;
                    
                    case 'DATETIME':
                        // Must be valid datetime format YYYY-MM-DD HH:MM:SS
                        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $defaultValue)) {
                            $escapedDefault = $conn->real_escape_string($defaultValue);
                            $columnDefinition .= " DEFAULT '$escapedDefault'";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for DATETIME must be in format YYYY-MM-DD HH:MM:SS (e.g., 2024-01-01 12:00:00)";
                        }
                        break;
                    
                    case 'TIME':
                        // Must be valid time format HH:MM:SS
                        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $defaultValue)) {
                            $escapedDefault = $conn->real_escape_string($defaultValue);
                            $columnDefinition .= " DEFAULT '$escapedDefault'";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for TIME must be in format HH:MM:SS (e.g., 12:30:00)";
                        }
                        break;
                    
                    case 'YEAR':
                        // Must be valid year (4 digits)
                        if (preg_match('/^\d{4}$/', $defaultValue)) {
                            $columnDefinition .= " DEFAULT $defaultValue";
                            $isValid = true;
                        } else {
                            $errorMsg = "Default value for YEAR must be a 4-digit year (e.g., 2024)";
                        }
                        break;
                    
                    case 'VARCHAR':
                    case 'CHAR':
                    case 'TEXT':
                    case 'TINYTEXT':
                    case 'MEDIUMTEXT':
                    case 'LONGTEXT':
                    case 'ENUM':
                    case 'SET':
                        // Text types - always valid as string
                        $escapedDefault = $conn->real_escape_string($defaultValue);
                        $columnDefinition .= " DEFAULT '$escapedDefault'";
                        $isValid = true;
                        break;
                    
                    case 'BLOB':
                    case 'TINYBLOB':
                    case 'MEDIUMBLOB':
                    case 'LONGBLOB':
                    case 'BINARY':
                    case 'VARBINARY':
                        // BLOB types cannot have default values in MySQL
                        $errorMsg = "$columnType columns cannot have default values in MySQL";
                        break;
                    
                    default:
                        // For unknown types, treat as string
                        $escapedDefault = $conn->real_escape_string($defaultValue);
                        $columnDefinition .= " DEFAULT '$escapedDefault'";
                        $isValid = true;
                        break;
                }
                
                if (!$isValid) {
                    $conn->close();
                    sendResponse(false, $errorMsg, [], 400);
                }
            }
        }
        
        // Build the ALTER TABLE query
        $alterQuery = "ALTER TABLE $escapedTableName ADD COLUMN $columnDefinition";
        
        // Execute the query
        if (!$conn->query($alterQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "SQL Error: $error | Query: $alterQuery", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Column '$columnName' added successfully to table '$tableName'");
        break;
    
    case 'alter_table_drop_column':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $columnName = $_POST['column_name'] ?? '';
        
        if (empty($dbName) || empty($tableName) || empty($columnName)) {
            sendResponse(false, 'Database name, table name, and column name are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        $escapedColumnName = escapeIdentifier($columnName);
        
        $alterQuery = "ALTER TABLE $escapedTableName DROP COLUMN $escapedColumnName";
        
        if (!$conn->query($alterQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to drop column: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Column '{$columnName}' dropped successfully");
        break;
    
    case 'alter_table_modify_column':
        $dbName = $_POST['db_name'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $oldColumnName = $_POST['old_column_name'] ?? '';
        $columnJson = $_POST['column'] ?? '';
        
        if (empty($dbName) || empty($tableName) || empty($oldColumnName) || empty($columnJson)) {
            sendResponse(false, 'Database name, table name, old column name, and column data are required', [], 400);
        }
        
        $dbName = sanitizeDatabaseName($dbName);
        $tableName = sanitizeDatabaseName($tableName);
        
        if (!$dbName || !$tableName) {
            sendResponse(false, 'Invalid database or table name', [], 400);
        }
        
        $column = json_decode($columnJson, true);
        if (!$column || !isset($column['name']) || !isset($column['type'])) {
            sendResponse(false, 'Invalid column data', [], 400);
        }
        
        $conn = getDatabaseConnection($dbName);
        
        if (!$conn) {
            sendResponse(false, 'Failed to connect to database', [], 500);
        }
        
        $escapedTableName = escapeIdentifier($tableName);
        $escapedOldName = escapeIdentifier($oldColumnName);
        $colName = escapeIdentifier($column['name']);
        $colType = strtoupper($column['type']);
        
        if (!empty($column['length'])) {
            $colType .= '(' . intval($column['length']) . ')';
        }
        
        $def = "$colType";
        
        if (isset($column['nullable']) && $column['nullable'] === false) {
            $def .= ' NOT NULL';
        } else {
            $def .= ' NULL';
        }
        
        if (isset($column['default']) && $column['default'] !== '') {
            if ($column['default'] === 'NULL') {
                $def .= ' DEFAULT NULL';
            } else {
                $def .= " DEFAULT '" . $conn->real_escape_string($column['default']) . "'";
            }
        }
        
        $alterQuery = "ALTER TABLE $escapedTableName CHANGE $escapedOldName $colName $def";
        
        if (!$conn->query($alterQuery)) {
            $error = $conn->error;
            $conn->close();
            sendResponse(false, "Failed to modify column: $error", [], 500);
        }
        
        $conn->close();
        sendResponse(true, "Column modified successfully");
        break;
    
    default:
        sendResponse(false, 'Invalid or missing action parameter', [], 400);
        break;
}

