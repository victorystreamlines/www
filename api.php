<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * API Backend - CRUD Operations for users1 Table
 * ═══════════════════════════════════════════════════════════════════════════
 * Description: RESTful API for managing users1 table with full CRUD operations
 * Database: u419999707_Mohamed (Hostinger)
 * Table: users1
 * Architecture: JSON API responses with CORS support
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════════════
// CORS & HEADERS CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ═══════════════════════════════════════════════════════════════════════════
// DATABASE CONNECTION
// ═══════════════════════════════════════════════════════════════════════════
$host = 'srv1788.hstgr.io';
$port = '3306';
$dbname = 'u419999707_Mohamed';
$username = 'u419999707_Abuammar';
$password = 'P@master5007';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendResponse(false, 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage(), null, 500);
    exit();
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Send JSON response with consistent format
 */
function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Validate required fields
 */
function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            return "الحقل '$field' مطلوب";
        }
    }
    return null;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ═══════════════════════════════════════════════════════════════════════════
// GET ACTION FROM REQUEST
// ═══════════════════════════════════════════════════════════════════════════
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    sendResponse(false, 'لم يتم تحديد الإجراء المطلوب', null, 400);
}

// ═══════════════════════════════════════════════════════════════════════════
// API ACTIONS ROUTER
// ═══════════════════════════════════════════════════════════════════════════
switch ($action) {
    
    // ───────────────────────────────────────────────────────────────────────
    // LIST ALL USERS (with pagination and sorting)
    // ───────────────────────────────────────────────────────────────────────
    case 'list_users1':
        listUsers();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // GET SINGLE USER BY ID
    // ───────────────────────────────────────────────────────────────────────
    case 'get_users1':
        getUser();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // CREATE NEW USER
    // ───────────────────────────────────────────────────────────────────────
    case 'create_users1':
        createUser();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // UPDATE EXISTING USER
    // ───────────────────────────────────────────────────────────────────────
    case 'update_users1':
        updateUser();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // DELETE USER
    // ───────────────────────────────────────────────────────────────────────
    case 'delete_users1':
        deleteUser();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // SEARCH USERS (Ajax live search)
    // ───────────────────────────────────────────────────────────────────────
    case 'search_users1':
        searchUsers();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // GET TABLE STATISTICS
    // ───────────────────────────────────────────────────────────────────────
    case 'stats_users1':
        getStatistics();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // BULK DELETE
    // ───────────────────────────────────────────────────────────────────────
    case 'bulk_delete_users1':
        bulkDeleteUsers();
        break;
    
    // ───────────────────────────────────────────────────────────────────────
    // UNKNOWN ACTION
    // ───────────────────────────────────────────────────────────────────────
    default:
        sendResponse(false, "الإجراء '$action' غير معروف", null, 400);
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: LIST ALL USERS (with pagination)
// ═══════════════════════════════════════════════════════════════════════════
function listUsers() {
    global $pdo;
    
    try {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get sorting parameters
        $orderBy = $_GET['orderBy'] ?? 'created_at';
        $orderDir = $_GET['orderDir'] ?? 'DESC';
        
        // Validate sort column
        $allowedColumns = ['id', 'username', 'email', 'created_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }
        
        // Validate sort direction
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM users1");
        $totalRecords = $countStmt->fetch()['total'];
        
        // Get records with pagination
        $sql = "SELECT id, username, email, created_at 
                FROM users1 
                ORDER BY $orderBy $orderDir 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll();
        
        sendResponse(true, 'تم جلب البيانات بنجاح', [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $limit)
            ]
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في جلب البيانات: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: GET SINGLE USER
// ═══════════════════════════════════════════════════════════════════════════
function getUser() {
    global $pdo;
    
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (empty($id)) {
        sendResponse(false, 'معرف المستخدم مطلوب', null, 400);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse(false, 'المستخدم غير موجود', null, 404);
        }
        
        sendResponse(true, 'تم جلب بيانات المستخدم بنجاح', $user);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في جلب البيانات: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: CREATE NEW USER
// ═══════════════════════════════════════════════════════════════════════════
function createUser() {
    global $pdo;
    
    // Get and sanitize input
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    $error = validateRequired(['username', 'email', 'password'], [
        'username' => $username,
        'email' => $email,
        'password' => $password
    ]);
    
    if ($error) {
        sendResponse(false, $error, null, 400);
    }
    
    // Validate email format
    if (!validateEmail($email)) {
        sendResponse(false, 'صيغة البريد الإلكتروني غير صحيحة', null, 400);
    }
    
    // Validate username length
    if (strlen($username) < 3 || strlen($username) > 50) {
        sendResponse(false, 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف', null, 400);
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        sendResponse(false, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل', null, 400);
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            sendResponse(false, 'اسم المستخدم موجود بالفعل', null, 409);
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            sendResponse(false, 'البريد الإلكتروني موجود بالفعل', null, 409);
        }
        
        // Check if ID already exists (if provided)
        if ($id !== null) {
            $stmt = $pdo->prepare("SELECT id FROM users1 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetch()) {
                sendResponse(false, 'المعرف موجود بالفعل، سيتم إنشاء معرف تلقائي', null, 409);
            }
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        if ($id !== null) {
            $sql = "INSERT INTO users1 (id, username, email, password) VALUES (:id, :username, :email, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);
        } else {
            $sql = "INSERT INTO users1 (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);
            $id = $pdo->lastInsertId();
        }
        
        sendResponse(true, 'تم إنشاء المستخدم بنجاح', ['id' => $id], 201);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في إنشاء المستخدم: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: UPDATE USER
// ═══════════════════════════════════════════════════════════════════════════
function updateUser() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    
    if (empty($id)) {
        sendResponse(false, 'معرف المستخدم مطلوب', null, 400);
    }
    
    // Get and sanitize input
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    $error = validateRequired(['username', 'email'], [
        'username' => $username,
        'email' => $email
    ]);
    
    if ($error) {
        sendResponse(false, $error, null, 400);
    }
    
    // Validate email format
    if (!validateEmail($email)) {
        sendResponse(false, 'صيغة البريد الإلكتروني غير صحيحة', null, 400);
    }
    
    // Validate username length
    if (strlen($username) < 3 || strlen($username) > 50) {
        sendResponse(false, 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف', null, 400);
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'المستخدم غير موجود', null, 404);
        }
        
        // Check if username is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE username = :username AND id != :id");
        $stmt->execute(['username' => $username, 'id' => $id]);
        if ($stmt->fetch()) {
            sendResponse(false, 'اسم المستخدم موجود بالفعل', null, 409);
        }
        
        // Check if email is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) {
            sendResponse(false, 'البريد الإلكتروني موجود بالفعل', null, 409);
        }
        
        // Update user
        if (!empty($password)) {
            // Validate password length
            if (strlen($password) < 6) {
                sendResponse(false, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل', null, 400);
            }
            
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users1 SET username = :username, email = :email, password = :password WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'id' => $id
            ]);
        } else {
            $sql = "UPDATE users1 SET username = :username, email = :email WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'id' => $id
            ]);
        }
        
        sendResponse(true, 'تم تحديث المستخدم بنجاح', ['id' => $id]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في تحديث المستخدم: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: DELETE USER
// ═══════════════════════════════════════════════════════════════════════════
function deleteUser() {
    global $pdo;
    
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (empty($id)) {
        sendResponse(false, 'معرف المستخدم مطلوب', null, 400);
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'المستخدم غير موجود', null, 404);
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        sendResponse(true, 'تم حذف المستخدم بنجاح', ['id' => $id]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في حذف المستخدم: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: SEARCH USERS (Ajax live search)
// ═══════════════════════════════════════════════════════════════════════════
function searchUsers() {
    global $pdo;
    
    $query = sanitizeInput($_GET['query'] ?? $_POST['query'] ?? '');
    
    if (empty($query)) {
        listUsers(); // Return all users if no search query
        return;
    }
    
    try {
        $searchTerm = "%$query%";
        
        // Search in username, email, and id
        $sql = "SELECT id, username, email, created_at 
                FROM users1 
                WHERE username LIKE :query 
                   OR email LIKE :query 
                   OR CAST(id AS CHAR) LIKE :query
                ORDER BY created_at DESC
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['query' => $searchTerm]);
        $users = $stmt->fetchAll();
        
        sendResponse(true, 'تم البحث بنجاح', [
            'users' => $users,
            'query' => $query,
            'count' => count($users)
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في البحث: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: GET STATISTICS
// ═══════════════════════════════════════════════════════════════════════════
function getStatistics() {
    global $pdo;
    
    try {
        // Total users
        $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM users1");
        $total = $totalStmt->fetch()['total'];
        
        // Users created today
        $todayStmt = $pdo->query("SELECT COUNT(*) as total FROM users1 WHERE DATE(created_at) = CURDATE()");
        $today = $todayStmt->fetch()['total'];
        
        // Users created this week
        $weekStmt = $pdo->query("SELECT COUNT(*) as total FROM users1 WHERE YEARWEEK(created_at) = YEARWEEK(NOW())");
        $week = $weekStmt->fetch()['total'];
        
        // Users created this month
        $monthStmt = $pdo->query("SELECT COUNT(*) as total FROM users1 WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $month = $monthStmt->fetch()['total'];
        
        sendResponse(true, 'تم جلب الإحصائيات بنجاح', [
            'total_users' => $total,
            'today' => $today,
            'this_week' => $week,
            'this_month' => $month
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في جلب الإحصائيات: ' . $e->getMessage(), null, 500);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNCTION: BULK DELETE USERS
// ═══════════════════════════════════════════════════════════════════════════
function bulkDeleteUsers() {
    global $pdo;
    
    $ids = $_POST['ids'] ?? null;
    
    if (empty($ids)) {
        sendResponse(false, 'لم يتم تحديد المستخدمين للحذف', null, 400);
    }
    
    // Parse IDs (comma-separated string or JSON array)
    if (is_string($ids)) {
        $ids = json_decode($ids, true) ?? explode(',', $ids);
    }
    
    if (!is_array($ids) || empty($ids)) {
        sendResponse(false, 'صيغة المعرفات غير صحيحة', null, 400);
    }
    
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM users1 WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        
        $deletedCount = $stmt->rowCount();
        
        sendResponse(true, "تم حذف $deletedCount مستخدم بنجاح", [
            'deleted_count' => $deletedCount,
            'ids' => $ids
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, 'خطأ في الحذف الجماعي: ' . $e->getMessage(), null, 500);
    }
}
