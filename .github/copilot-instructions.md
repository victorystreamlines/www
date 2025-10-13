# Important and mandatory. Do not remove this file.
# This file contains critical instructions for GitHub Copilot to understand the project.
I respond in English you respond in Arabic the application is by default in English but I want you to respond in Arabic
# Do not change the language of the code or comments, only respond in Arabic






# Hostinger Database Control Panel - AI Coding Instructions

## Project Overview
A full-stack database management system for Hostinger (Shared/VPS) with dynamic multi-database connection support. Pure PHP backend + vanilla JavaScript frontend with no frameworks.

**Architecture**: Backend-Hostinger.php (API) ↔ Dashboard-Hostinger.html (SPA) ↔ Store/ (JSON persistence)

## Critical Patterns

### Dynamic Connection Model
**Every database operation requires explicit Hostinger credentials in the request** - there are NO default connections:

```php
// Backend expects these POST parameters for ALL operations:
$host = $_POST['db_host'] ?? '';      // e.g., 'srv1788.hstgr.io'
$dbName = $_POST['db_name'] ?? '';    // e.g., 'u419999707_VzarF'
$username = $_POST['db_user'] ?? '';  // e.g., 'u419999707_gRwO5'
$password = $_POST['db_pass'] ?? '';  // Password in plain text
$port = $_POST['db_port'] ?? '3306';
```

**Connection lifecycle**: Credentials stored in localStorage → Retrieved by frontend → Sent with each API call → Backend creates ephemeral PDO connection → Connection closed after response.

### API Request Pattern
All frontend operations use this standardized helper:

```javascript
async function apiRequest(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    // Auto-inject selected database credentials
    if (selectedDatabaseId) {
        const conn = connections.find(c => c.id === selectedDatabaseId);
        formData.append('db_host', conn.host);
        formData.append('db_name', conn.dbName);
        formData.append('db_user', conn.username);
        formData.append('db_pass', conn.password);
        formData.append('db_port', conn.port);
    }
    
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    const response = await fetch('http://localhost/Backend-Hostinger.php', { method: 'POST', body: formData });
    return await response.json();
}
```

### Database Migration System
**Critical multi-phase operation** (Dashboard-Hostinger.html, line ~4222):

1. **Discovery**: `list_tables` on source database
2. **Structure Migration**: `generate_table_sql` with `include_data: 'false'` → `execute_sql` on destination
3. **Data Migration**: `generate_table_sql` with `include_data: 'true'` → `execute_sql` on destination
4. **Overwrite Mode**: Drops destination tables before CREATE TABLE if `migrationType === 'overwrite'`

**Key implementation details**:
- Uses two separate SQL generations (structure-only, then data) to handle large datasets
- Batches INSERT statements (100 rows per batch) to avoid memory issues
- Migration cancellable via `migrationCancelled` flag
- Progress tracked with `updateMigrationProgress(percentage, message)`

### State Management
**No frameworks** - uses global variables + localStorage:

```javascript
// Global state (Dashboard-Hostinger.html ~line 2505)
let selectedDatabaseId = null;                    // Currently active database
let connections = [];                             // All saved connections
const HOSTINGER_CONNECTIONS_KEY = 'hostinger_connections';
const SELECTED_DATABASE_KEY = 'selected_database_id';

// Persistence
localStorage.setItem(HOSTINGER_CONNECTIONS_KEY, JSON.stringify(connections));
localStorage.setItem(SELECTED_DATABASE_KEY, connId);
```

### SQL Injection Prevention
**Always use sanitization helpers**:

```php
// Backend-Hostinger.php pattern for ALL dynamic SQL:
function sanitizeDatabaseName($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}
function sanitizeTableName($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}

// Example usage:
$safeTableName = sanitizeTableName($tableName);
$conn->exec("DROP TABLE $safeTableName");  // Safe - uses backticks

// For values, ALWAYS use PDO prepared statements:
$stmt = $conn->prepare("INSERT INTO $safeTableName (name) VALUES (?)");
$stmt->execute([$userInput]);
```

### Validation Pattern
Every backend function follows this structure:

```php
function someOperation() {
    // 1. Extract and validate credentials
    if (empty($host) || empty($dbName) || empty($username)) {
        http_response_code(400);
        sendResponse(false, 'Missing required connection parameters');
        return;
    }
    
    // 2. Validate database/table names
    if (!validateDatabaseName($dbName)) {
        http_response_code(400);
        sendResponse(false, 'Invalid database name');
        return;
    }
    
    // 3. Get connection
    $conn = getConnection($host, $dbName, $username, $password, $port);
    if (!$conn) {
        http_response_code(500);
        sendResponse(false, 'Failed to connect to database');
        return;
    }
    
    // 4. Execute operation in try-catch
    try {
        // ... PDO operations
        sendResponse(true, 'Success message', ['data' => $result]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}
```

## Key Operations

### Complete API Endpoints (Backend-Hostinger.php line ~1810)
- `check_connection` - Test database connectivity
- `list_tables` - Get all tables with count
- `create_table` - Expects `columns` JSON with structure definitions
- `delete_table`, `rename_table`, `alter_table` - Table schema operations
- `get_table_structure` - Returns SHOW COLUMNS output
- `get_table_data` - Paginated data fetch (default 50/page)
- `generate_table_sql` - Creates exportable SQL with optional data
- `generate_database_sql` - Full database dump
- `execute_sql` - Run arbitrary SQL (returns results for SELECT, affected rows for DML)
- `insert_record`, `update_record`, `delete_record` - CRUD operations
- `search_records` - Full-text search across VARCHAR/TEXT columns
- `export_connections` - Save connections to Store/*.json
- `import_connections` - Load connections from Store/*.json
- `list_import_files` - Show available exports in Store/

### Table Creation Column Schema
When calling `create_table`, columns JSON structure:

```json
[
  {
    "name": "id",
    "type": "INT",
    "length": "11",
    "nullable": "no",
    "autoIncrement": "yes",
    "primaryKey": "yes",
    "unique": "no",
    "defaultValue": ""
  },
  {
    "name": "email",
    "type": "VARCHAR",
    "length": "255",
    "nullable": "no",
    "unique": "yes",
    "defaultValue": ""
  }
]
```

### Export/Import Connection Format
Store/ directory contains connection backups (Backend-Hostinger.php ~line 1656):

```json
{
  "exported_at": "10/13/2025, 12:32:56 AM",
  "total_connections": 7,
  "connections": [
    {
      "id": "1760268466615",
      "name": "u419999707_VzarF",
      "type": "shared",
      "host": "srv1788.hstgr.io",
      "dbName": "u419999707_VzarF",
      "username": "u419999707_gRwO5",
      "password": "P@master5007",
      "port": "3306",
      "createdAt": "2025-10-12T11:27:46.615Z"
    }
  ]
}
```

## Development Workflow

### Running the Application
1. **Requires Laragon or similar PHP server** (Apache + PHP 7.4+)
2. Place files in web root (e.g., `c:\laragon\www\`)
3. Access via `http://localhost/Dashboard-Hostinger.html`
4. Backend at `http://localhost/Backend-Hostinger.php`

### Debugging
- Backend errors: Check browser Network tab → Response
- Frontend errors: Browser console
- SQL errors: Backend returns `PDOException` messages in JSON response

### Testing Database Operations
Use the built-in SQL Executor tab (Dashboard-Hostinger.html ~line 3091):
- Supports multi-statement execution
- Auto-detects query type (SELECT/INSERT/UPDATE/DELETE/DDL)
- Visual Query Builder available for SELECT/INSERT/UPDATE/DELETE

## Security Considerations

### Known Security Issues
1. **Passwords stored in localStorage in plain text** - acceptable for local tool, NOT production
2. **No CSRF protection** - CORS set to `Access-Control-Allow-Origin: *`
3. **Error messages expose database structure** - intentional for debugging
4. **No rate limiting** - assumes trusted local environment

### When Adding Features
- Always validate input with `validateDatabaseName()` / `validateTableName()`
- Never trust user input - use prepared statements for values
- Use sanitization functions for identifiers (table/column names)
- Return meaningful error messages (this is a development tool)

## Common Modifications

### Adding New API Endpoint
1. Create function in Backend-Hostinger.php:
```php
function myNewOperation() {
    $host = $_POST['db_host'] ?? '';
    // ... standard validation pattern
    $conn = getConnection($host, $dbName, $username, $password, $port);
    try {
        // ... operation
        sendResponse(true, 'Success', ['data' => $result]);
    } catch (PDOException $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}
```

2. Add to switch statement (~line 1810):
```php
case 'my_new_operation':
    myNewOperation();
    break;
```

3. Call from frontend:
```javascript
const result = await apiRequest('my_new_operation', {
    custom_param: value
});
```

### Adding UI Section
Follow sidebar navigation pattern (Dashboard-Hostinger.html ~line 1177):
1. Add menu item in `<aside class="sidebar">`
2. Create content section in `<main class="main-content">`
3. Add `showSection('sectionName')` navigation function
4. Use message system: `showMessage('messageElementId', 'text', 'success|error|info')`

## File Structure
```
c:\laragon\www\
├── Backend-Hostinger.php      # API endpoint (1929 lines)
├── Dashboard-Hostinger.html   # SPA interface (8099 lines)
└── Store/                     # Connection exports (JSON)
    └── hostinger_connections_*.json
```

No build process, no dependencies, no package.json - pure PHP + vanilla JS.
