# 🚀 Quick Test Guide - Verify phpMyAdmin Works

## ✅ Test 1: Login to phpMyAdmin

1. **Open Browser:** http://69.62.114.112/phpmyadmin

2. **Login with Root:**
   ```
   Username: root
   Password: P@master5007
   ```

3. **Or Login with Admin (Recommended):**
   ```
   Username: admin
   Password: Admin@5007
   ```

---

## ✅ Test 2: Create Database Visually

1. After logging in, click the **"Databases"** tab at the top

2. In the "Create database" section:
   - **Database name:** `test_db_visual_check`
   - **Collation:** `utf8mb4_general_ci` (or leave as default)

3. Click **"Create"** button

4. **Expected Result:**
   - ✅ Green success message: "Database test_db_visual_check has been created"
   - ✅ Database appears in the left sidebar
   - ❌ NO "Access denied" error

---

## ✅ Test 3: Create Table in Database

1. Click on `test_db_visual_check` in the left sidebar

2. In the "Create table" section:
   - **Name:** `users`
   - **Number of columns:** `3`
   - Click **"Go"**

3. Fill in columns:
   - Column 1: `id`, Type: `INT`, Check `A_I` (Auto Increment), Primary Key
   - Column 2: `name`, Type: `VARCHAR`, Length: `100`
   - Column 3: `email`, Type: `VARCHAR`, Length: `100`

4. Click **"Save"**

5. **Expected Result:**
   - ✅ Table created successfully
   - ✅ No errors

---

## ✅ Test 4: Insert Data

1. Click on the `users` table in the left sidebar

2. Click the **"Insert"** tab

3. Fill in data:
   - **name:** `John Doe`
   - **email:** `john@example.com`

4. Click **"Go"**

5. **Expected Result:**
   - ✅ Row inserted successfully
   - ✅ You can see the data in "Browse" tab

---

## ✅ Test 5: Cleanup

1. Click **"Databases"** tab

2. Find `test_db_visual_check` in the list

3. Click the **"Drop"** (trash icon) next to it

4. Confirm deletion

5. **Expected Result:**
   - ✅ Database deleted successfully

---

## 🎯 If Everything Works:

**YOU'RE ALL SET!** ✅

You can now:
- Create databases visually
- Create tables
- Insert/Update/Delete data
- Manage users
- Import/Export databases
- Run SQL queries

No more "Access denied" errors! 🎉

---

## ❌ If You Still Get Errors:

### Run these commands on your server:

```bash
# SSH into server
ssh root@69.62.114.112

# Test MySQL connection
mysql -u root -pP@master5007 -e "SELECT 'MySQL Working' as status;"

# Check MySQL is running
systemctl status mysql

# Check nginx is running
systemctl status nginx

# View error logs
tail -20 /var/log/mysql/error.log
tail -20 /var/log/nginx/phpmyadmin-error.log

# Restart services
systemctl restart mysql
systemctl restart nginx
```

---

## 📧 Credentials Summary

### Root User (Full Admin)
```
URL: http://69.62.114.112/phpmyadmin
Username: root
Password: P@master5007
```

### Admin User (Recommended for Daily Use)
```
URL: http://69.62.114.112/phpmyadmin
Username: admin
Password: Admin@5007
```

Both users have full privileges to:
- Create/Drop databases
- Create/Alter/Drop tables
- Insert/Update/Delete data
- Manage users
- Import/Export

---

**Happy Database Managing! 🎉**
