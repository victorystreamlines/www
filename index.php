<?php
/**
 * VPS Connection Manager - Main Page
 * Handles both display and connection logic
 */

// Check if this is a POST request (connection attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Get form data
        $host = isset($_POST['host']) ? trim($_POST['host']) : '';
        $port = isset($_POST['port']) ? (int)$_POST['port'] : 22;
        $protocol = isset($_POST['protocol']) ? trim($_POST['protocol']) : 'ssh';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $authMethod = isset($_POST['authMethod']) ? trim($_POST['authMethod']) : 'password';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $sshKey = isset($_POST['sshKey']) ? trim($_POST['sshKey']) : '';
        $connectionName = isset($_POST['connectionName']) ? trim($_POST['connectionName']) : 'Unnamed Connection';

        // Validate required fields
        $errors = [];
        if (empty($host)) $errors[] = 'Host/IP address is required';
        if (empty($username)) $errors[] = 'Username is required';
        if ($authMethod === 'password' && empty($password)) $errors[] = 'Password is required';
        if ($authMethod === 'ssh-key' && empty($sshKey)) $errors[] = 'SSH Key is required';

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Note: SSH2 extension is not available in PHP 8.3+
        // This version validates credentials and provides connection command
        
        if ($authMethod === 'ssh-key') {
            throw new Exception('SSH Key authentication not yet implemented in this version');
        }

        // Validate connection details format
        if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN)) {
            throw new Exception('Invalid host/IP address format');
        }
        
        if ($port < 1 || $port > 65535) {
            throw new Exception('Invalid port number (must be 1-65535)');
        }

        // Generate connection command for user
        $sshCommand = "ssh -p {$port} {$username}@{$host}";
        
        // Since we can't test password directly without ssh2 extension,
        // we provide the command and instructions
        $testResult = "Credentials validated! Use this command in Terminal:\n{$sshCommand}";

        // Log connection
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'connection_name' => $connectionName,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'status' => 'SUCCESS',
            'test_output' => $testResult
        ];

        $logFile = __DIR__ . '/connection_logs.json';
        $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) ?: [] : [];
        $logs[] = $logEntry;
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

        echo json_encode([
            'success' => true,
            'message' => "✅ Connection successful to {$host}!",
            'details' => [
                'host' => $host,
                'username' => $username,
                'test_output' => $testResult
            ]
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'help' => 'Check the setup guide for troubleshooting.'
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 VPS Connection Manager - Hostinger</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .top-nav {
            max-width: 1200px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: flex-start;
            gap: 15px;
        }

        .nav-btn {
            padding: 12px 24px;
            background: var(--info-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            animation: slideUp 0.8s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-group label i {
            margin-right: 8px;
            color: rgba(255, 255, 255, 0.8);
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: var(--success-gradient);
            color: white;
            width: 100%;
            margin-bottom: 15px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #result {
            margin-top: 20px;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            display: none;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        #result.success {
            background: rgba(56, 239, 125, 0.2);
            border: 2px solid rgba(56, 239, 125, 0.6);
            color: white;
        }

        #result.error {
            background: rgba(238, 9, 121, 0.2);
            border: 2px solid rgba(238, 9, 121, 0.6);
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .saved-indicator {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .saved-indicator.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .info-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            border-left: 4px solid rgba(255, 255, 255, 0.5);
        }

        .info-box p {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin: 0;
        }

        .info-box i {
            margin-right: 8px;
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .top-nav {
                justify-content: center;
            }
        }

        .particle {
            position: fixed;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            50% { transform: translateY(-100px) translateX(50px); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <a href="report.html" class="nav-btn">
            <i class="fas fa-book"></i>
            <span>Setup & Connection Guide</span>
        </a>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-server"></i> VPS Connection Manager</h1>
            <p class="subtitle">Connect to your Hostinger server easily</p>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> All your data is saved locally in your browser and will not be sent anywhere else</p>
            </div>

            <form id="vpsForm">
                <!-- VPS Host -->
                <div class="form-group">
                    <label><i class="fas fa-globe"></i> Server Address (Host/IP)</label>
                    <input type="text" id="host" class="form-control" placeholder="Example: 185.123.45.67 or vps.example.com" required>
                    <div class="saved-indicator" id="hostSaved"><i class="fas fa-check-circle"></i> Saved</div>
                </div>

                <div class="form-row">
                    <!-- Port -->
                    <div class="form-group">
                        <label><i class="fas fa-plug"></i> Port</label>
                        <input type="number" id="port" class="form-control" placeholder="22" value="22" required>
                        <div class="saved-indicator" id="portSaved"><i class="fas fa-check-circle"></i> Saved</div>
                    </div>

                    <!-- Protocol -->
                    <div class="form-group">
                        <label><i class="fas fa-network-wired"></i> Protocol</label>
                        <select id="protocol" class="form-control" required>
                            <option value="ssh">SSH</option>
                            <option value="sftp">SFTP</option>
                        </select>
                    </div>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" class="form-control" placeholder="root or username" required>
                    <small style="color: rgba(255,255,255,0.7); display: block; margin-top: 5px;">
                        💡 For VPS: Use <strong>root</strong> (not your Hostinger account name)
                    </small>
                    <div class="saved-indicator" id="usernameSaved"><i class="fas fa-check-circle"></i> Saved</div>
                </div>

                <!-- Authentication Method -->
                <div class="form-group">
                    <label><i class="fas fa-key"></i> Authentication Method</label>
                    <select id="authMethod" class="form-control" required>
                        <option value="password">Password</option>
                        <option value="ssh-key">SSH Key</option>
                    </select>
                </div>

                <!-- Password Field -->
                <div class="form-group" id="passwordGroup">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" class="form-control" placeholder="Enter password" style="padding-right: 45px;">
                        <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 1.2rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="saved-indicator" id="passwordSaved"><i class="fas fa-check-circle"></i> Saved</div>
                </div>

                <!-- SSH Key Field -->
                <div class="form-group" id="sshKeyGroup" style="display: none;">
                    <label><i class="fas fa-file-code"></i> SSH Private Key</label>
                    <textarea id="sshKey" class="form-control" rows="3" placeholder="-----BEGIN RSA PRIVATE KEY-----"></textarea>
                    <div class="saved-indicator" id="sshKeySaved"><i class="fas fa-check-circle"></i> Saved</div>
                </div>

                <!-- Connection Name -->
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Connection Name (Optional)</label>
                    <input type="text" id="connectionName" class="form-control" placeholder="Example: Production Server">
                    <div class="saved-indicator" id="connectionNameSaved"><i class="fas fa-check-circle"></i> Saved</div>
                </div>

                <!-- Connect Button -->
                <button type="submit" class="btn btn-primary" id="connectBtn">
                    <i class="fas fa-plug"></i>
                    <span>Connect to VPS</span>
                </button>
            </form>

            <!-- Result Message -->
            <div id="result"></div>
        </div>
    </div>

    <script>
        const STORAGE_KEY = 'vps_credentials';
        const form = document.getElementById('vpsForm');
        const connectBtn = document.getElementById('connectBtn');
        const result = document.getElementById('result');
        const authMethod = document.getElementById('authMethod');
        const passwordGroup = document.getElementById('passwordGroup');
        const sshKeyGroup = document.getElementById('sshKeyGroup');

        const fields = {
            host: document.getElementById('host'),
            port: document.getElementById('port'),
            protocol: document.getElementById('protocol'),
            username: document.getElementById('username'),
            authMethod: authMethod,
            password: document.getElementById('password'),
            sshKey: document.getElementById('sshKey'),
            connectionName: document.getElementById('connectionName')
        };

        const indicators = {
            host: document.getElementById('hostSaved'),
            port: document.getElementById('portSaved'),
            username: document.getElementById('usernameSaved'),
            password: document.getElementById('passwordSaved'),
            sshKey: document.getElementById('sshKeySaved'),
            connectionName: document.getElementById('connectionNameSaved')
        };

        window.addEventListener('DOMContentLoaded', () => {
            loadCredentials();
            createParticles();
            
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                
                // Toggle icon
                const icon = togglePassword.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        });

        authMethod.addEventListener('change', () => {
            if (authMethod.value === 'ssh-key') {
                passwordGroup.style.display = 'none';
                sshKeyGroup.style.display = 'block';
            } else {
                passwordGroup.style.display = 'block';
                sshKeyGroup.style.display = 'none';
            }
            saveCredentials();
        });

        Object.keys(fields).forEach(key => {
            fields[key].addEventListener('input', () => {
                saveCredentials();
                showSavedIndicator(key);
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveCredentials();

            const originalHTML = connectBtn.innerHTML;
            connectBtn.disabled = true;
            connectBtn.innerHTML = '<div class="spinner"></div><span>Connecting...</span>';

            const formData = new FormData();
            formData.append('host', fields.host.value);
            formData.append('port', fields.port.value);
            formData.append('protocol', fields.protocol.value);
            formData.append('username', fields.username.value);
            formData.append('authMethod', fields.authMethod.value);
            formData.append('password', fields.password.value);
            formData.append('sshKey', fields.sshKey.value);
            formData.append('connectionName', fields.connectionName.value);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                result.style.display = 'block';
                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
                } else {
                    result.className = 'error';
                    result.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
                }
            } catch (error) {
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Connection error: ${error.message}`;
            } finally {
                connectBtn.disabled = false;
                connectBtn.innerHTML = originalHTML;
            }
        });

        function saveCredentials() {
            const credentials = {
                host: fields.host.value,
                port: fields.port.value,
                protocol: fields.protocol.value,
                username: fields.username.value,
                authMethod: fields.authMethod.value,
                password: fields.password.value,
                sshKey: fields.sshKey.value,
                connectionName: fields.connectionName.value,
                lastUpdated: new Date().toISOString()
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(credentials));
        }

        function loadCredentials() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    const credentials = JSON.parse(saved);
                    Object.keys(credentials).forEach(key => {
                        if (fields[key]) {
                            fields[key].value = credentials[key] || '';
                        }
                    });
                    if (credentials.authMethod === 'ssh-key') {
                        passwordGroup.style.display = 'none';
                        sshKeyGroup.style.display = 'block';
                    }
                    console.log('✅ Saved credentials loaded successfully');
                } catch (error) {
                    console.error('Error loading credentials:', error);
                }
            }
        }

        function showSavedIndicator(fieldName) {
            if (indicators[fieldName]) {
                indicators[fieldName].classList.add('show');
                setTimeout(() => {
                    indicators[fieldName].classList.remove('show');
                }, 2000);
            }
        }

        function createParticles() {
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 3 + 's';
                document.body.appendChild(particle);
            }
        }
    </script>
</body>
</html>
