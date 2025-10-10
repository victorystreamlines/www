<?php
/*
===========================================
HOSTINGER DATABASE CONFIGURATION TEMPLATE
===========================================

INSTRUCTIONS:
1. Copy this file and rename it to: hostinger_config.php
2. Fill in your Hostinger MySQL credentials below
3. Generate a strong API key (or use the one provided)
4. Upload both hostinger_config.php and hostinger_proxy.php to your Hostinger server
5. Keep this file SECURE and never share it

IMPORTANT SECURITY NOTES:
- Never commit hostinger_config.php to version control
- Use .htaccess to prevent direct access to this file
- Generate a strong unique API key
- Only share the API key with authorized users

HOW TO GET YOUR HOSTINGER MYSQL CREDENTIALS:
1. Login to Hostinger hPanel
2. Go to Websites → Your Website → Databases
3. Click on your database name
4. Copy the following information:
   - Database Name
   - Database Username
   - Database Password
   - MySQL Hostname (usually: srv####.hstgr.io)
*/

// ===========================
// HOSTINGER MYSQL CREDENTIALS
// ===========================

// Database Hostname - Get from Hostinger hPanel
// Example: srv1788.hstgr.io or 193.203.168.173
define('HOSTINGER_DB_HOST', 'srv1788.hstgr.io');

// Database Username - Get from Hostinger hPanel
define('HOSTINGER_DB_USER', 'your_database_username');

// Database Password - Get from Hostinger hPanel
define('HOSTINGER_DB_PASS', 'your_database_password');

// ===========================
// API SECURITY
// ===========================

// API Key - CHANGE THIS TO A STRONG RANDOM STRING!
// Generate a secure key here: https://www.uuidgenerator.net/
// Or use this command in terminal: php -r "echo bin2hex(random_bytes(32));"
define('HOSTINGER_API_KEY', 'CHANGE_THIS_TO_A_SECURE_RANDOM_KEY_' . md5(time()));

// Optional: IP Whitelist (leave empty to allow all IPs)
// Example: ['192.168.1.1', '10.0.0.1']
define('HOSTINGER_IP_WHITELIST', []);

// Optional: Enable Rate Limiting (requests per minute per IP)
define('HOSTINGER_RATE_LIMIT', 60);

// ===========================
// EXAMPLE FILLED VALUES
// ===========================
/*
define('HOSTINGER_DB_HOST', 'srv1788.hstgr.io');
define('HOSTINGER_DB_USER', 'u123456789_dbuser');
define('HOSTINGER_DB_PASS', 'SecureP@ssw0rd123!');
define('HOSTINGER_API_KEY', 'a8f5f167f44f4964e6c998dee827110c8f4e4ea6d2f8f9bb72b6f8c1e2c4e5f6');
define('HOSTINGER_IP_WHITELIST', []); // Allow all
define('HOSTINGER_RATE_LIMIT', 60); // 60 requests per minute
*/

// ===========================
// .HTACCESS PROTECTION
// ===========================
/*
Create a file named .htaccess in the same directory with this content:

<Files "hostinger_config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "hostinger_config_template.php">
    Order Allow,Deny
    Deny from all
</Files>
*/

?>

