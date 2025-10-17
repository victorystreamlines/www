#!/bin/bash
set -euo pipefail

# ============================================
# MariaDB Remote Access Setup Script
# For Ubuntu 24.04 VPS with CloudPanel
# ============================================

echo "========================================"
echo "  MariaDB Remote Access Setup"
echo "========================================"
echo ""

# --- Configuration Variables ---
PUBLIC_IP="188.236.56.94"      # Client public IP
VPS_IP="69.62.114.112"         # VPS address
DOMAIN="sharpworth.com"        # Domain on VPS
SSH_USER="naif"                # SSH username
DB_USER="appuser"              # Database username
DB_PASS="P@master5007"         # Database password
REQUIRE_SSL=true               # Enable SSL

echo "[INFO] Configuration:"
echo "  PUBLIC_IP     = $PUBLIC_IP"
echo "  VPS_IP        = $VPS_IP"
echo "  DOMAIN        = $DOMAIN"
echo "  DB_USER       = $DB_USER"
echo "  REQUIRE_SSL   = $REQUIRE_SSL"
echo ""

# ============================================
# STEP 1: Make MariaDB Listen on All Interfaces
# ============================================
echo "[STEP 1] Configuring MariaDB to listen on 0.0.0.0:3306"
echo "  Why: By default MariaDB only listens on 127.0.0.1 (localhost)"
echo "  What: Change bind-address to 0.0.0.0 in config files"
echo ""

# Backup config files first
sudo cp /etc/mysql/mariadb.conf.d/50-server.cnf /etc/mysql/mariadb.conf.d/50-server.cnf.backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true

# Update bind-address in all possible config locations
sudo sed -i 's/^\s*bind-address\s*=.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf 2>/dev/null || true
sudo sed -i 's/^\s*bind-address\s*=.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf 2>/dev/null || true

echo "  Restarting MariaDB..."
sudo systemctl restart mariadb
sleep 2

# Verify MariaDB is listening
if ! sudo ss -lntp | grep -q ':3306'; then
  echo "[ERROR] MariaDB is not listening on port 3306!" >&2
  echo "  Checking MariaDB status..." >&2
  sudo systemctl status mariadb --no-pager || true
  echo "  Recent logs:" >&2
  sudo journalctl -u mariadb --no-pager -n 50 || true
  exit 1
fi

echo "[SUCCESS] MariaDB is now listening on:"
sudo ss -lntp | grep ':3306'
echo ""

# ============================================
# STEP 2: Configure UFW Firewall
# ============================================
echo "[STEP 2] Configuring UFW firewall"
echo "  Why: Restrict port 3306 access to only your IP"
echo "  What: Allow SSH, allow 3306 from $PUBLIC_IP only"
echo ""

# Ensure SSH is always allowed
sudo ufw allow OpenSSH >/dev/null 2>&1 || true

# Remove any broad 3306 rules (best effort)
sudo ufw delete allow 3306 >/dev/null 2>&1 || true
sudo ufw delete allow to any port 3306 >/dev/null 2>&1 || true

# Add specific rule for your public IP
echo "  Adding rule: allow from $PUBLIC_IP to port 3306"
sudo ufw allow from "$PUBLIC_IP" to any port 3306 proto tcp

# Enable UFW (force to avoid interactive prompt)
sudo ufw --force enable

echo "[SUCCESS] UFW is now active with these rules:"
sudo ufw status verbose | head -30
echo ""

# ============================================
# STEP 3: Create Database User
# ============================================
echo "[STEP 3] Creating/updating database user"
echo "  Why: Need a user that can connect from your IP"
echo "  What: Create ${DB_USER}@${PUBLIC_IP} with full privileges"
echo "  Note: For production, restrict to specific databases!"
echo ""

sudo mysql <<SQL
-- Create user if doesn't exist
CREATE USER IF NOT EXISTS '${DB_USER}'@'${PUBLIC_IP}' IDENTIFIED BY '${DB_PASS}';

-- Update password (in case user already existed)
ALTER USER '${DB_USER}'@'${PUBLIC_IP}' IDENTIFIED BY '${DB_PASS}';

-- Grant all privileges (TIGHTEN THIS FOR PRODUCTION!)
GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'${PUBLIC_IP}' WITH GRANT OPTION;

-- Apply changes
FLUSH PRIVILEGES;

-- Show what we created
SELECT User, Host FROM mysql.user WHERE User='${DB_USER}';
SQL

echo "[SUCCESS] Database user created/updated"
echo "[WARNING] User has ALL PRIVILEGES on *.*"
echo "          For production: GRANT ALL ON specific_db.* instead"
echo ""

# ============================================
# STEP 4: Optional SSL Configuration
# ============================================
if [ "$REQUIRE_SSL" = true ]; then
  echo "[STEP 4] Configuring MariaDB SSL"
  echo "  Why: Encrypt database traffic over the internet"
  echo "  What: Use Let's Encrypt certs for $DOMAIN"
  echo ""
  
  CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
  KEY="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"
  
  if [ -f "$CERT" ] && [ -f "$KEY" ]; then
    echo "  Found Let's Encrypt certificates!"
    echo "  Certificate: $CERT"
    echo "  Key: $KEY"
    echo ""
    
    # Check if SSL is already configured
    if grep -q "^ssl=on" /etc/mysql/mariadb.conf.d/50-server.cnf 2>/dev/null; then
      echo "  SSL already configured in MariaDB config"
    else
      echo "  Adding SSL configuration to MariaDB..."
      
      # Add SSL configuration
      cat <<SSLCONF | sudo tee -a /etc/mysql/mariadb.conf.d/50-server.cnf >/dev/null

# SSL Configuration (added by setup script)
[mysqld]
ssl=on
ssl-cert=$CERT
ssl-key=$KEY
SSLCONF
      
      echo "  Restarting MariaDB to apply SSL..."
      sudo systemctl restart mariadb
      sleep 2
    fi
    
    # Require SSL for the user
    echo "  Requiring SSL for ${DB_USER}@${PUBLIC_IP}"
    sudo mysql -e "ALTER USER '${DB_USER}'@'${PUBLIC_IP}' REQUIRE SSL; FLUSH PRIVILEGES;"
    
    echo "[SUCCESS] MariaDB SSL enabled and required for user"
  else
    echo "[WARNING] Let's Encrypt certificates not found for ${DOMAIN}"
    echo "          Expected: $CERT"
    echo "          Skipping SSL configuration"
  fi
else
  echo "[STEP 4] SSL not required (REQUIRE_SSL=false)"
fi
echo ""

# ============================================
# STEP 5: Verification
# ============================================
echo "========================================"
echo "  VERIFICATION CHECKS"
echo "========================================"
echo ""

echo "[CHECK 1] MariaDB listening on 3306:"
sudo ss -lntp | grep ':3306' || echo "  ERROR: Not listening!"
echo ""

echo "[CHECK 2] UFW firewall rules:"
sudo ufw status | grep 3306 || echo "  No 3306 rules found"
echo ""

echo "[CHECK 3] MariaDB version:"
sudo mysql -e "SELECT VERSION() AS MariaDB_Version;"
echo ""

echo "[CHECK 4] SSL status:"
sudo mysql -e "SHOW VARIABLES LIKE '%ssl%';" | grep -E "(have_ssl|ssl_cert|ssl_key)" || echo "  SSL variables not set"
echo ""

echo "[CHECK 5] Database user:"
sudo mysql -e "SELECT User, Host, ssl_type FROM mysql.user WHERE User='${DB_USER}';"
echo ""

# ============================================
# CLIENT CONNECTION INSTRUCTIONS
# ============================================
cat <<EOT

========================================================
  CLIENT CONNECTION INSTRUCTIONS
========================================================

✅ DIRECT CONNECTION (Enabled Now):
───────────────────────────────────────────────────────
  Host:     ${DOMAIN}  (or ${VPS_IP})
  Port:     3306
  Username: ${DB_USER}
  Password: ${DB_PASS}
  SSL:      $([ "$REQUIRE_SSL" = true ] && echo "REQUIRED" || echo "Optional")

📊 TEST FROM YOUR PC (PowerShell):
───────────────────────────────────────────────────────
  # Test port connectivity
  Test-NetConnection ${DOMAIN} -Port 3306

  # Test MySQL connection (if mysql client installed)
  mysql -h ${DOMAIN} -P 3306 -u ${DB_USER} -p

🖥️ GUI CLIENTS (HeidiSQL / DBeaver / MySQL Workbench):
───────────────────────────────────────────────────────
  Host:     ${DOMAIN}
  Port:     3306
  User:     ${DB_USER}
  Password: ${DB_PASS}
  SSL:      Enable if required above
  
🔒 SSH TUNNEL (Alternative if ISP blocks 3306):
───────────────────────────────────────────────────────
  # Create tunnel
  ssh -L 3306:127.0.0.1:3306 ${SSH_USER}@${DOMAIN} -N
  
  # Then connect to
  Host:     127.0.0.1
  Port:     3306
  User:     ${DB_USER}
  Password: ${DB_PASS}

========================================================

EOT

# ============================================
# ROLLBACK COMMANDS (For Reference)
# ============================================
cat <<'ROLLBACK'

========================================================
  ROLLBACK COMMANDS (if needed)
========================================================

⚠️ Run these manually if you need to undo changes:

# 1. Remove database user
sudo mysql -e "REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'appuser'@'188.236.56.94';"
sudo mysql -e "DROP USER IF EXISTS 'appuser'@'188.236.56.94';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 2. Remove UFW rule
sudo ufw delete allow from 188.236.56.94 to any port 3306 proto tcp

# 3. Revert MariaDB to localhost only
sudo sed -i 's/^\s*bind-address\s*=.*/bind-address = 127.0.0.1/' /etc/mysql/mariadb.conf.d/50-server.cnf
sudo systemctl restart mariadb

# 4. Remove SSL configuration (if added)
sudo sed -i '/^# SSL Configuration (added by setup script)/,/^ssl-key/d' /etc/mysql/mariadb.conf.d/50-server.cnf
sudo systemctl restart mariadb

# 5. Restore backup config
# sudo cp /etc/mysql/mariadb.conf.d/50-server.cnf.backup_* /etc/mysql/mariadb.conf.d/50-server.cnf
# sudo systemctl restart mariadb

========================================================

ROLLBACK

# ============================================
# IMPORTANT REMINDERS
# ============================================
cat <<REMINDERS

========================================================
  ⚠️ IMPORTANT SECURITY REMINDERS
========================================================

1. 🔐 PASSWORD: Change DB password after testing
   sudo mysql -e "ALTER USER '${DB_USER}'@'${PUBLIC_IP}' IDENTIFIED BY 'NEW_STRONG_PASSWORD';"

2. 🔒 PRIVILEGES: Restrict to specific database(s)
   sudo mysql -e "REVOKE ALL ON *.* FROM '${DB_USER}'@'${PUBLIC_IP}';"
   sudo mysql -e "GRANT ALL ON myapp_db.* TO '${DB_USER}'@'${PUBLIC_IP}';"

3. 🌐 DYNAMIC IP: If your public IP changes:
   - Update UFW: sudo ufw allow from NEW_IP to any port 3306 proto tcp
   - Update user: CREATE USER '${DB_USER}'@'NEW_IP' IDENTIFIED BY '${DB_PASS}';

4. 🔥 FIREWALL: UFW now allows 3306 from your IP only
   - Good for security
   - May need updating if IP changes

5. 📊 MONITORING: Check logs regularly
   sudo tail -f /var/log/mysql/error.log

========================================================

REMINDERS

echo ""
echo "[COMPLETE] Setup finished! Test the connection from your PC now."
echo ""
