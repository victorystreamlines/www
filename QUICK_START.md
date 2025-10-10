# 🚀 Quick Start Guide

Get up and running in 5 minutes!

## 📍 You Are Here

Choose your setup:
- **[Option A: Local Only](#option-a-local-only)** - Use on your computer only (2 minutes)
- **[Option B: With Hostinger](#option-b-with-hostinger-remote)** - Connect to Hostinger server (5 minutes)

---

## Option A: Local Only

### Step 1: Setup Files
1. Copy all files to your web server directory:
   - XAMPP: `C:\xampp\htdocs\db-manager\`
   - WAMP: `C:\wamp\www\db-manager\`
   - Laragon: `C:\laragon\www\db-manager\`

### Step 2: Start Services
1. Start **Apache**
2. Start **MySQL**

### Step 3: Open Dashboard
1. Open browser
2. Navigate to: `http://localhost/db-manager/dashboard.html`
3. Click **"Test Connection"**
4. ✅ Done! Start managing databases

**That's it! You're ready to go.** 🎉

---

## Option B: With Hostinger Remote

### Prerequisites
- Hostinger hosting account
- Database already created in Hostinger hPanel
- 5 minutes of your time

### Step 1: Enable Remote MySQL (1 minute)

1. Login to **Hostinger hPanel**
2. Go to: **Websites** → **Your Site** → **Databases** → **Remote MySQL**
3. Add connection:
   - IP: Your IP address OR check **"Any Host"**
   - Database: Select your database
4. Click **Create**
5. **Copy the hostname** (e.g., `srv1788.hstgr.io`)

### Step 2: Get Database Info (30 seconds)

1. Still in hPanel, go to **Databases**
2. Click your database name
3. Copy these:
   - Database Name: `u123456789_mydb`
   - Username: `u123456789_user`
   - Password: `••••••••`
   - Hostname: `srv1788.hstgr.io`

### Step 3: Configure Files (2 minutes)

1. Open `hostinger_config_template.php`
2. Fill in your info:
```php
define('HOSTINGER_DB_HOST', 'srv1788.hstgr.io');        // Your hostname
define('HOSTINGER_DB_USER', 'u123456789_user');         // Your username
define('HOSTINGER_DB_PASS', 'YourPasswordHere');        // Your password
```

3. Generate API Key:
   - Visit: https://www.uuidgenerator.net/
   - Copy a UUID
   - Paste it:
```php
define('HOSTINGER_API_KEY', 'paste-uuid-here');
```

4. **Save as**: `hostinger_config.php` (remove _template)

### Step 4: Upload to Hostinger (1 minute)

Upload these 2 files to Hostinger via FTP or File Manager:
1. `hostinger_proxy.php`
2. `hostinger_config.php`

**Upload to**: `/public_html/` or `/public_html/api/`

Your API URL will be:
```
https://yourdomain.com/hostinger_proxy.php
```

### Step 5: Configure Dashboard (30 seconds)

1. Open `dashboard.html` in browser
2. Click **Settings** in sidebar
3. Fill in:
   - **API URL**: `https://yourdomain.com/hostinger_proxy.php`
   - **API Key**: (the UUID you generated)
4. Click **"Save Configuration"**
5. Click **"Test Connection"**
6. ✅ Should see: "Successfully connected to Hostinger database server"

### Step 6: Start Using (5 seconds)

1. Look at sidebar header
2. Click the server button: **💻 Local** → **🌐 Hostinger**
3. ✅ You're now connected to Hostinger!
4. Go to **List Databases** and see your Hostinger databases

**🎉 Congratulations! You can now manage Hostinger databases remotely!**

---

## 🎯 What Can You Do Now?

### Database Operations
- ✅ View all databases
- ✅ Create new databases
- ✅ Delete databases
- ✅ Rename databases

### Table Operations
- ✅ View all tables
- ✅ Create tables (use templates!)
- ✅ Edit table structure
- ✅ Delete tables
- ✅ Rename tables

### Switch Servers
- Click the button in sidebar: **💻 Local** ↔ **🌐 Hostinger**
- All operations work on both!

---

## 🆘 Something Not Working?

### "Connection failed" on Local
- ✅ Is MySQL running?
- ✅ Check credentials in `backend.php`

### "Failed to connect to Hostinger API"
- ✅ Are files uploaded correctly?
- ✅ Is API URL correct? (include https://)
- ✅ Does `hostinger_config.php` exist on server?

### "Invalid API key"
- ✅ Copy-paste API key carefully (no spaces)
- ✅ Make sure it matches in both config and dashboard

### "Database not found"
- ✅ Does database exist in Hostinger hPanel?
- ✅ Is Remote MySQL enabled?
- ✅ Check credentials are correct

**Still stuck?** Read the complete guide: [HOSTINGER_SETUP_GUIDE.md](HOSTINGER_SETUP_GUIDE.md)

---

## 📚 Next Steps

1. **Create your first database**: Go to "Create Database"
2. **Use a template**: Try creating a "Users" table from template
3. **Explore features**: Check out all the sidebar options
4. **Read full docs**: See [README.md](README.md) for all features

---

## 🎨 Tips

- **Use Templates**: 10+ pre-built table templates available
- **Preview SQL**: Always preview before creating tables
- **Test First**: Test on non-production databases first
- **Backup**: Hostinger has backup features - use them!

---

## ⏱️ Time Breakdown

| Task | Time |
|------|------|
| Local Setup | 2 min |
| Hostinger Setup | 5 min |
| Total | **7 min** |

---

**You're all set! Happy database managing! 🚀**

---

Need help? Check these files:
- 📖 [README.md](README.md) - Complete feature list
- 📘 [HOSTINGER_SETUP_GUIDE.md](HOSTINGER_SETUP_GUIDE.md) - Detailed Hostinger setup
- 🔧 Troubleshooting section above

**Current Version**: Phase 2 - Hostinger Remote Connection  
**Last Updated**: 2025

