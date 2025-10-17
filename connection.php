<?php
// ============================================
// VPS DATABASE CREDENTIALS
// Fill in your Hostinger VPS database details below
// ============================================

// Your VPS Database Host
// IMPORTANT: If this PHP script is ON the same VPS, use "localhost"
// If accessing remotely, you may need SSH tunnel or allow remote MySQL access
$db_host = "sharpworth.com";

// Your Database Name (the name of your MySQL database)
$db_name = "GeneralDB";

// Your Database Username (MySQL username)
$db_username = "victorystreamlines";

// Your Database Password (MySQL password)
$db_password = "P@master5007";

// Database Port (default is 3306)
$db_port = "3306";

// ============================================
// CONNECTION TEST LOGIC
// ============================================

$connection_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    
    // Test if port is reachable
    $port_test = @fsockopen($db_host, $db_port, $errno, $errstr, 5);
    $port_reachable = $port_test !== false;
    if ($port_test) fclose($port_test);
    
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        
        $pdo = new PDO($dsn, $db_username, $db_password, $options);
        
        // Get MySQL version
        $stmt = $pdo->query('SELECT VERSION() as version');
        $result = $stmt->fetch();
        
        $connection_result = [
            'success' => true,
            'message' => 'Connection Successful!',
            'host' => $db_host,
            'database' => $db_name,
            'version' => $result['version'],
            'port_reachable' => $port_reachable
        ];
    } catch (PDOException $e) {
        $error_message = $e->getMessage();
        $suggestions = [];
        
        // Provide specific solutions based on error
        if (strpos($error_message, '2002') !== false || strpos($error_message, 'timed out') !== false) {
            $suggestions[] = 'Port 3306 is blocked or MySQL is not accessible remotely';
            $suggestions[] = 'If your PHP script is ON the VPS, change $db_host to "localhost"';
            $suggestions[] = 'If accessing remotely, enable remote MySQL access in Hostinger panel';
            $suggestions[] = 'You may need to use SSH tunnel for remote access';
            $suggestions[] = 'Check if your IP is allowed in VPS firewall settings';
        } elseif (strpos($error_message, '1045') !== false) {
            $suggestions[] = 'Username or password is incorrect';
            $suggestions[] = 'Check your database credentials in Hostinger panel';
        } elseif (strpos($error_message, '1049') !== false) {
            $suggestions[] = 'Database name does not exist';
            $suggestions[] = 'Verify the database name in your Hostinger panel';
        }
        
        $connection_result = [
            'success' => false,
            'message' => 'Connection Failed!',
            'error' => $error_message,
            'port_reachable' => $port_reachable,
            'suggestions' => $suggestions
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPS Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .server-icon {
            text-align: center;
            font-size: 60px;
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 20px;
        }

        .test-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .test-button:active {
            transform: translateY(0);
        }

        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .result.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-left: 5px solid #0f7d6f;
        }

        .result.failure {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            border-left: 5px solid #c72a3a;
        }

        .result-icon {
            font-size: 40px;
            text-align: center;
            margin-bottom: 10px;
        }

        .result-message {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .result-details {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        .result-details p {
            margin: 5px 0;
            word-break: break-all;
        }

        .result-details strong {
            display: inline-block;
            min-width: 100px;
        }

        .credentials-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 13px;
            color: #555;
        }

        .credentials-info strong {
            color: #667eea;
        }

        .suggestions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .suggestions h4 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .suggestions ul {
            list-style: none;
            padding: 0;
        }

        .suggestions li {
            padding: 8px 0;
            padding-left: 20px;
            position: relative;
        }

        .suggestions li:before {
            content: "→";
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .port-status {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="server-icon">🖥️</div>
        <h1>VPS Connection Test</h1>
        <p class="subtitle">Test your Hostinger VPS database connection</p>

        <div class="credentials-info">
            <strong>📝 Note:</strong> Please edit the PHP credentials at the top of this file (connection.php) before testing.
        </div>

        <form method="POST">
            <button type="submit" name="test_connection" class="test-button">
                🔌 Test Connection
            </button>
        </form>

        <?php if ($connection_result !== null): ?>
            <div class="result <?php echo $connection_result['success'] ? 'success' : 'failure'; ?>">
                <div class="result-icon">
                    <?php echo $connection_result['success'] ? '✅' : '❌'; ?>
                </div>
                <div class="result-message">
                    <?php echo htmlspecialchars($connection_result['message']); ?>
                </div>
                <div class="result-details">
                    <?php if ($connection_result['success']): ?>
                        <p><strong>Host:</strong> <?php echo htmlspecialchars($connection_result['host']); ?></p>
                        <p><strong>Database:</strong> <?php echo htmlspecialchars($connection_result['database']); ?></p>
                        <p><strong>MySQL Version:</strong> <?php echo htmlspecialchars($connection_result['version']); ?></p>
                        <div class="port-status">
                            <strong>Port Status:</strong> <?php echo $connection_result['port_reachable'] ? '✅ Reachable' : '❌ Not Reachable'; ?>
                        </div>
                    <?php else: ?>
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($connection_result['error']); ?></p>
                        <div class="port-status">
                            <strong>Port 3306 Status:</strong> <?php echo $connection_result['port_reachable'] ? '✅ Reachable' : '❌ Blocked/Not Reachable'; ?>
                        </div>
                        <?php if (!empty($connection_result['suggestions'])): ?>
                            <div class="suggestions">
                                <h4>💡 Possible Solutions:</h4>
                                <ul>
                                    <?php foreach ($connection_result['suggestions'] as $suggestion): ?>
                                        <li><?php echo htmlspecialchars($suggestion); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>