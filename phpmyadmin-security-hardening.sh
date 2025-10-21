#!/bin/bash
# phpMyAdmin Security Hardening Script
# Run this on your server: bash phpmyadmin-security-hardening.sh

echo "=== phpMyAdmin Security Hardening ==="
echo ""

# 1. Change default URL path
echo "1. Changing phpMyAdmin URL from /phpmyadmin to custom path..."
echo "   Recommended: Use a random, hard-to-guess URL"
echo "   Example: /my-secret-admin-xyz789"
echo ""
read -p "Enter new URL path (without /): " NEW_PATH

if [ ! -z "$NEW_PATH" ]; then
    sed -i "s|location /phpmyadmin|location /$NEW_PATH|g" /etc/nginx/sites-enabled/phpmyadmin.conf
    sed -i "s|location ~ \^/phpmyadmin/|location ~ ^/$NEW_PATH/|g" /etc/nginx/sites-enabled/phpmyadmin.conf
    echo "   ✓ URL changed to: /$NEW_PATH"
fi

# 2. Add IP whitelist
echo ""
echo "2. Adding IP restriction..."
echo "   Only specified IPs will be able to access phpMyAdmin"
read -p "Enter your IP address (or press Enter to skip): " YOUR_IP

if [ ! -z "$YOUR_IP" ]; then
    sed -i "/location \/phpmyadmin {/a\    allow $YOUR_IP;\n    deny all;" /etc/nginx/sites-enabled/phpmyadmin.conf
    echo "   ✓ Access restricted to: $YOUR_IP"
fi

# 3. Enable HTTPS (Let's Encrypt)
echo ""
echo "3. Setting up HTTPS with Let's Encrypt..."
read -p "Do you have a domain name pointing to this server? (y/n): " HAS_DOMAIN

if [ "$HAS_DOMAIN" = "y" ]; then
    read -p "Enter your domain name: " DOMAIN
    apt update
    apt install certbot python3-certbot-nginx -y
    certbot --nginx -d $DOMAIN
    echo "   ✓ HTTPS enabled for $DOMAIN"
fi

# 4. Add HTTP Basic Authentication
echo ""
echo "4. Adding HTTP Basic Authentication layer..."
read -p "Enter username for additional auth layer: " AUTH_USER

if [ ! -z "$AUTH_USER" ]; then
    apt install apache2-utils -y
    htpasswd -c /etc/nginx/basic-auth/.htpasswd-phpmyadmin $AUTH_USER
    
    sed -i "/location \/phpmyadmin {/a\    auth_basic \"Restricted Access\";\n    auth_basic_user_file /etc/nginx/basic-auth/.htpasswd-phpmyadmin;" /etc/nginx/sites-enabled/phpmyadmin.conf
    echo "   ✓ Basic authentication added"
fi

# 5. Disable root login via phpMyAdmin config
echo ""
echo "5. Disabling root login through phpMyAdmin..."
cat >> /usr/share/phpmyadmin/config.inc.php << 'EOF'

/* Disable root login */
$cfg['Servers'][$i]['AllowRoot'] = false;
EOF
echo "   ✓ Root login disabled"

# 6. Set up fail2ban
echo ""
echo "6. Setting up fail2ban for phpMyAdmin..."
read -p "Install and configure fail2ban? (y/n): " SETUP_FAIL2BAN

if [ "$SETUP_FAIL2BAN" = "y" ]; then
    apt install fail2ban -y
    
    cat > /etc/fail2ban/filter.d/phpmyadmin.conf << 'EOF'
[Definition]
failregex = ^<HOST> -.*POST.*/phpmyadmin/.*
ignoreregex =
EOF

    cat > /etc/fail2ban/jail.d/phpmyadmin.local << 'EOF'
[phpmyadmin]
enabled = true
port = http,https
filter = phpmyadmin
logpath = /var/log/nginx/phpmyadmin-access.log
maxretry = 3
bantime = 3600
findtime = 600
EOF

    systemctl restart fail2ban
    echo "   ✓ fail2ban configured"
fi

# 7. Restart nginx
echo ""
echo "Restarting nginx..."
nginx -t && systemctl restart nginx

echo ""
echo "=== Security Hardening Complete ==="
echo ""
echo "IMPORTANT REMINDERS:"
echo "1. Your phpMyAdmin is now accessible at: http://69.62.114.112/$NEW_PATH"
echo "2. Always use HTTPS in production"
echo "3. Keep phpMyAdmin and PHP updated"
echo "4. Monitor access logs regularly: /var/log/nginx/phpmyadmin-access.log"
echo "5. Consider using SSH tunneling instead of public access"
echo ""
