# 🗄️ Database Control Panel - Phase 2 with Hostinger Remote Connection

A comprehensive web-based MySQL database management system with beautiful UI and remote server support.

## ✨ Features

### 🏠 Local & Remote Server Support
- **Dual-Server Architecture**: Switch between Local and Hostinger servers seamlessly
- **Secure API Proxy**: Credentials never leave Hostinger server
- **One-Click Toggle**: Easy switching with visual indicators
- **Connection Testing**: Built-in connection verification

### 💾 Database Operations
- ✅ List all databases
- ✅ Create new databases
- ✅ Delete databases
- ✅ Rename databases
- ✅ Connect to databases
- ✅ Set database credentials

### 📊 Table Operations
- ✅ List all tables in a database
- ✅ Create tables (Manual definition)
- ✅ Create tables from templates (10+ pre-built templates)
- ✅ Delete tables
- ✅ Rename tables
- ✅ Edit table structure
- ✅ Add/Modify/Drop columns
- ✅ View table structure
- ✅ SQL Preview before creation

### 🎨 Beautiful UI/UX
- **Modern Design**: Gradient sunset theme with glass-morphism effects
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Sidebar Navigation**: Easy navigation between sections
- **Real-time Feedback**: Loading spinners, success/error messages
- **Dark Theme**: Easy on the eyes with beautiful colors

### 🔐 Security Features
- **API Key Authentication**: Protect Hostinger endpoint
- **Rate Limiting**: Prevent abuse (configurable)
- **IP Whitelist**: Optional IP restrictions
- **Credential Storage**: Secure localStorage management
- **HTTPS Support**: Encrypted connections
- **Input Validation**: SQL injection prevention

---

## 📁 Project Structure

```
├── backend.php                      # Local PHP API with forwarding logic
├── dashboard.html                   # Main dashboard UI
├── hostinger_proxy.php              # Remote API for Hostinger (upload to server)
├── hostinger_config_template.php    # Configuration template
├── HOSTINGER_SETUP_GUIDE.md         # Complete setup instructions
└── README.md                        # This file
```

---

## 🚀 Quick Start

### For Local Use Only:

1. Place files in your web server directory (e.g., `htdocs`, `www`)
2. Make sure MySQL is running
3. Open `dashboard.html` in browser
4. Start managing databases!

### For Hostinger Remote Connection:

Follow the complete guide: **[HOSTINGER_SETUP_GUIDE.md](HOSTINGER_SETUP_GUIDE.md)**

**Quick Steps:**
1. Enable Remote MySQL in Hostinger hPanel
2. Fill `hostinger_config_template.php` with your credentials
3. Save as `hostinger_config.php`
4. Upload `hostinger_proxy.php` and `hostinger_config.php` to Hostinger
5. Configure in Dashboard → Settings
6. Toggle between Local/Hostinger using sidebar button

---

## 🎯 Usage

### Switching Servers

Look at the **sidebar header** - you'll see a button showing:
- **💻 Local** - Connected to local MySQL server
- **🌐 Hostinger** - Connected to Hostinger remote server

Click the button to toggle between servers. All operations work on both!

### Managing Databases

1. **List Databases**: View all available databases
2. **Create Database**: Add new database with optional credentials
3. **Delete Database**: Remove unwanted databases (⚠️ irreversible)
4. **Rename Database**: Change database name
5. **Set Credentials**: Add user/password authentication

### Managing Tables

1. **Connect to Database**: Click a database from the list
2. **List Tables**: View all tables in the selected database
3. **Create Table**:
   - **Manual**: Define columns manually
   - **Template**: Use pre-built templates (Users, Products, Blog Posts, etc.)
4. **Edit Table**: Add/modify/drop columns
5. **Rename/Delete**: Manage existing tables

### Using Templates

10+ pre-built table templates:
- 👤 Users
- 📦 Products
- 📝 Blog Posts
- 🛒 Orders
- 📁 Categories
- 💬 Comments
- 🖼️ Media
- ⚙️ Settings
- 📋 Logs
- 🔐 Sessions

---

## 🔧 Configuration

### Local Server Configuration

Edit in `backend.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Hostinger Server Configuration

Edit in `hostinger_config.php`:
```php
define('HOSTINGER_DB_HOST', 'srv1788.hstgr.io');
define('HOSTINGER_DB_USER', 'your_username');
define('HOSTINGER_DB_PASS', 'your_password');
define('HOSTINGER_API_KEY', 'your-secure-api-key');
```

---

## 🛡️ Security

### Best Practices

1. **Use Strong API Keys**: Generate UUIDs or random strings
2. **Enable HTTPS**: Always use secure connections for Hostinger
3. **IP Whitelist**: Restrict access to known IPs when possible
4. **Secure Config Files**: Use `.htaccess` to protect configuration
5. **Regular Updates**: Keep credentials and API keys updated
6. **Backup Databases**: Always backup before destructive operations

### Protecting Configuration Files

Create `.htaccess` on Hostinger:
```apache
<Files "hostinger_config.php">
    Order Allow,Deny
    Deny from all
</Files>
```

---

## 📋 Requirements

### Server Requirements:
- PHP 7.4 or higher
- MySQL 5.7 or higher
- PDO PHP Extension
- cURL PHP Extension (for remote connections)

### Browser Requirements:
- Modern browser with JavaScript enabled
- LocalStorage support
- Fetch API support

---

## 🐛 Troubleshooting

### "Failed to connect to database server"
- Check MySQL is running
- Verify credentials in `backend.php` or `hostinger_config.php`
- Check database server is accessible

### "Failed to connect to Hostinger API"
- Verify API URL is correct
- Check files are uploaded to Hostinger
- Ensure `hostinger_config.php` exists

### "Invalid API key"
- Verify API key matches in config and dashboard settings
- Check for extra spaces or characters

### Database operations fail
- Ensure you're connected to a database first
- Check user permissions
- Verify database exists

For more help, see: **[HOSTINGER_SETUP_GUIDE.md](HOSTINGER_SETUP_GUIDE.md)**

---

## 📊 Technical Details

### Architecture

**Local Mode:**
```
Browser → dashboard.html → backend.php → Local MySQL
```

**Hostinger Mode:**
```
Browser → dashboard.html → backend.php → Hostinger API (hostinger_proxy.php) → Hostinger MySQL
```

### Security Layers

1. **Frontend**: Input validation, secure storage
2. **Backend (Local)**: Request forwarding, credential management
3. **Backend (Hostinger)**: API authentication, rate limiting, IP filtering
4. **Database**: Prepared statements, SQL injection prevention

---

## 🎨 Customization

### Changing Theme Colors

Edit CSS in `dashboard.html`:
```css
background: linear-gradient(135deg, #1e3a8a 0%, #991b1b 100%);
```

### Adding Custom Table Templates

Edit JavaScript in `dashboard.html` - look for `loadTemplates()` function.

### Modifying Rate Limits

Edit `hostinger_config.php`:
```php
define('HOSTINGER_RATE_LIMIT', 60); // requests per minute
```

---

## 📈 Future Enhancements (Phase 3)

Potential features for future versions:
- 📊 View and manage table data (CRUD operations)
- 🔍 Search and filter records
- 📤 Import/Export data (CSV, JSON, SQL)
- 💾 Database backup and restore
- 🔗 Foreign key management
- 📈 Database statistics and monitoring
- 🔐 User authentication for dashboard
- 📝 SQL query editor with syntax highlighting

---

## 📄 License

This project is open source. Feel free to use, modify, and distribute.

---

## 🤝 Contributing

Contributions are welcome! Areas for improvement:
- Additional table templates
- More database operations
- Enhanced security features
- UI/UX improvements
- Bug fixes and optimizations

---

## 📞 Support

For issues or questions:
1. Check troubleshooting section
2. Review setup guide: `HOSTINGER_SETUP_GUIDE.md`
3. Check browser console for errors
4. Verify all credentials are correct

---

## 🎉 Acknowledgments

Built with:
- PHP & PDO
- MySQL
- Vanilla JavaScript
- CSS3 with modern effects
- Love and coffee ☕

---

## 📝 Version History

### Phase 2 (Current)
- ✅ Added Hostinger remote connection support
- ✅ Dual-server architecture with toggle
- ✅ Enhanced security with API key authentication
- ✅ Complete table operations
- ✅ Table templates system

### Phase 1
- ✅ Basic database operations
- ✅ Modern UI with gradient theme
- ✅ Sidebar navigation
- ✅ Local server management

---

**Made with ❤️ for easy database management**

**Current Version**: Phase 2 - Hostinger Remote Connection  
**Last Updated**: 2025

