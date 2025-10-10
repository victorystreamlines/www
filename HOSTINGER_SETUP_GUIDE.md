# Hostinger Remote Connection Setup Guide

This guide will help you set up remote database management for your Hostinger server.

## 📋 Prerequisites

- Active Hostinger hosting account
- Database created in Hostinger hPanel
- FTP/File Manager access to your Hostinger server
- Your database credentials from Hostinger

---

## 🚀 Step-by-Step Setup

### Step 1: Enable Remote MySQL Access on Hostinger

1. Log in to your **Hostinger hPanel**
2. Navigate to: **Websites** → **Your Website** → **Databases**
3. Click on **Remote MySQL**
4. In the "Create a New Remote Database Connection" section:
   - **IP Address**: Enter your current IP address, OR
   - Check **"Any Host"** to allow connections from any IP (⚠️ less secure but more convenient)
   - **Database**: Select the database you want to access remotely
5. Click **Create**
6. **Note down** the MySQL server hostname (e.g., `srv1788.hstgr.io` or `193.203.168.173`)

### Step 2: Get Your Database Credentials

1. In Hostinger hPanel, go to **Databases**
2. Click on your database name
3. Copy the following information:
   - **Database Name** (e.g., `u123456789_mydb`)
   - **Database Username** (e.g., `u123456789_user`)
   - **Database Password**
   - **MySQL Hostname** (e.g., `srv1788.hstgr.io`)

### Step 3: Configure the Hostinger Proxy Files

1. **Open `hostinger_config_template.php`** in a text editor
2. Fill in your credentials:

```php
define('HOSTINGER_DB_HOST', 'srv1788.hstgr.io'); // Your MySQL hostname
define('HOSTINGER_DB_USER', 'u123456789_user');  // Your database username
define('HOSTINGER_DB_PASS', 'YourPassword123');   // Your database password
```

3. **Generate a secure API Key**:
   - Visit: https://www.uuidgenerator.net/
   - Copy a generated UUID
   - Replace the API key:

```php
define('HOSTINGER_API_KEY', 'paste-your-generated-uuid-here');
```

4. **Save the file as `hostinger_config.php`** (remove "_template" from filename)

### Step 4: Upload Files to Hostinger

Upload these 2 files to your Hostinger server (via FTP or File Manager):

1. **`hostinger_proxy.php`** - The API proxy file
2. **`hostinger_config.php`** - Your configuration file

**Recommended location**: `/public_html/` or `/public_html/api/`

**Example**: If you upload to `/public_html/`, your API URL will be:
```
https://yourdomain.com/hostinger_proxy.php
```

### Step 5: Secure Your Files (Optional but Recommended)

Create a `.htaccess` file in the same directory with this content:

```apache
<Files "hostinger_config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "hostinger_config_template.php">
    Order Allow,Deny
    Deny from all
</Files>
```

This prevents direct access to your configuration files.

### Step 6: Configure in Dashboard

1. Open your Database Control Panel (`dashboard.html`)
2. Click on **Settings** in the sidebar
3. In the **"Hostinger Connection Settings"** section:
   - **Hostinger API URL**: Enter your API URL
     - Example: `https://yourdomain.com/hostinger_proxy.php`
   - **API Key**: Enter the API key from your `hostinger_config.php`
4. Click **"Save Configuration"**
5. Click **"Test Connection"** to verify it works

### Step 7: Switch Between Local and Hostinger

- Look at the **sidebar header** in the Dashboard
- You'll see a button showing current server: **💻 Local** or **🌐 Hostinger**
- Click the button to toggle between servers
- The panel will automatically reconnect to the selected server

---

## 🔐 Security Best Practices

### ✅ DO:
- Use a strong, unique API key (UUID or random string)
- Keep `hostinger_config.php` secure and private
- Use HTTPS for your Hostinger API URL
- Regularly update your API key
- Use "Any Host" only if your IP changes frequently
- Test on a non-production database first

### ❌ DON'T:
- Share your API key publicly
- Commit `hostinger_config.php` to version control
- Use the default/template API key
- Use weak database passwords
- Expose configuration files publicly

---

## 🧪 Testing Your Setup

### Test Connection from Dashboard:
1. Go to **Settings** → **Hostinger Connection Settings**
2. Click **"Test Connection"**
3. You should see: ✅ "Successfully connected to Hostinger database server"

### Test Database Operations:
1. Click the **server toggle button** to switch to **🌐 Hostinger**
2. Go to **Dashboard**
3. Click **"Test Connection"** - should succeed
4. Go to **List Databases**
5. Click **"Refresh List"** - should show your Hostinger databases

---

## 🐛 Troubleshooting

### Problem: "Failed to connect to Hostinger API"
**Solutions:**
- Check that files are uploaded correctly
- Verify the API URL is correct (include https://)
- Make sure `hostinger_config.php` exists on server
- Check file permissions (should be 644)

### Problem: "Invalid API key" or "Access denied"
**Solutions:**
- Verify API key matches in both files
- Copy-paste carefully (no extra spaces)
- Regenerate a new API key if needed

### Problem: "Failed to connect to database server"
**Solutions:**
- Check database credentials in `hostinger_config.php`
- Verify Remote MySQL is enabled in Hostinger hPanel
- Check your IP is whitelisted (or use "Any Host")
- Confirm database hostname is correct

### Problem: "Database not found" or "Access denied for user"
**Solutions:**
- Verify database exists in Hostinger hPanel
- Check username has permissions to the database
- Test credentials using phpMyAdmin in hPanel

---

## 📊 Features Supported

All features work on both Local and Hostinger servers:

### Database Operations:
✅ List databases  
✅ Create database  
✅ Delete database  
✅ Rename database  
✅ Connect to database  
✅ Set credentials (Note: Limited on Hostinger shared hosting)

### Table Operations:
✅ List tables  
✅ Create table (Manual & Templates)  
✅ Delete table  
✅ Rename table  
✅ Edit table structure  
✅ Add/modify/drop columns  

---

## 📝 File Structure

After setup, your file structure should look like:

```
Local (Your Computer):
├── backend.php (Modified with forwarding logic)
├── dashboard.html (Modified with UI toggle)
├── hostinger_config_template.php (Template - keep for reference)
└── HOSTINGER_SETUP_GUIDE.md (This file)

Hostinger Server:
├── public_html/
│   ├── hostinger_proxy.php (Uploaded)
│   ├── hostinger_config.php (Uploaded - your credentials)
│   └── .htaccess (Optional - security)
```

---

## 🆘 Getting Help

### Check Files:
1. Verify all files are uploaded correctly
2. Check file permissions (should be readable)
3. Look at browser console for JavaScript errors
4. Check Hostinger error logs if available

### Common Issues:
- **CORS errors**: Make sure CORS headers are enabled in proxy
- **Timeout errors**: Increase PHP timeout in `hostinger_proxy.php`
- **Rate limiting**: Adjust `HOSTINGER_RATE_LIMIT` in config

---

## 🔄 Updating Configuration

To change your API key or credentials:

1. Edit `hostinger_config.php` on Hostinger server
2. Update the values
3. In Dashboard → Settings, update API key if changed
4. Click "Save Configuration"
5. Test connection

---

## ⚡ Quick Reference

| Action | Location | Purpose |
|--------|----------|---------|
| Toggle Server | Sidebar Header Button | Switch between Local/Hostinger |
| Configure Hostinger | Settings → Hostinger Connection | Set API URL & Key |
| Test Connection | Settings → Test Connection | Verify setup works |
| View Server Status | Sidebar (below toggle) | See which server is active |

---

## 📞 Support

If you need help:
1. Check this guide thoroughly
2. Review Hostinger documentation: https://support.hostinger.com
3. Check browser console for error messages
4. Verify all credentials are correct

---

**Last Updated**: 2025  
**Version**: 1.0  
**Compatible with**: Hostinger Shared Hosting, Business Hosting, Cloud Hosting

---

**🎉 Setup Complete! You can now manage your Hostinger databases remotely!**

