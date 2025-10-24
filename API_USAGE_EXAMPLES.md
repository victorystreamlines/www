# Universal Backend API - Usage Guide

## Overview
This backend API (`default.php`) is a comprehensive REST API that handles all database operations from any frontend application using simple vanilla JavaScript.

## Features
✅ **Database Operations** - Create, drop, list databases
✅ **Table Operations** - Create, drop, truncate, describe tables
✅ **CRUD Operations** - Full Create, Read, Update, Delete
✅ **Custom Queries** - Execute any SQL query
✅ **Dynamic Configuration** - Override database credentials per request
✅ **High Performance** - Prepared statements, optimized queries
✅ **Secure** - Input validation, SQL injection prevention

---

## API Endpoint
```
POST https://yourdomain.com/default.php
Content-Type: application/json
```

---

## JavaScript Usage Examples

### 1. Test Connection
```javascript
async function testConnection() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'test_connection'
        })
    });
    const data = await response.json();
    console.log(data);
}
```

---

## DATABASE OPERATIONS

### Create Database
```javascript
async function createDatabase(dbName) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_database',
            db_name: dbName
        })
    });
    return await response.json();
}

// Usage
createDatabase('my_new_database');
```

### List All Databases
```javascript
async function listDatabases() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'list_databases'
        })
    });
    const data = await response.json();
    console.log(data.data.databases);
}
```

### Drop Database
```javascript
async function dropDatabase(dbName) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'drop_database',
            db_name: dbName
        })
    });
    return await response.json();
}
```

---

## TABLE OPERATIONS

### Create Table (Users Example)
```javascript
async function createUsersTable() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_table',
            table: 'users',
            columns: [
                { name: 'id', type: 'INT AUTO_INCREMENT PRIMARY KEY' },
                { name: 'username', type: 'VARCHAR(50)', extra: 'NOT NULL UNIQUE' },
                { name: 'email', type: 'VARCHAR(100)', extra: 'NOT NULL' },
                { name: 'password', type: 'VARCHAR(255)', extra: 'NOT NULL' },
                { name: 'created_at', type: 'TIMESTAMP', extra: 'DEFAULT CURRENT_TIMESTAMP' }
            ]
        })
    });
    return await response.json();
}
```

### Create Table (Products Example)
```javascript
async function createProductsTable() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_table',
            table: 'products',
            columns: [
                { name: 'id', type: 'INT AUTO_INCREMENT PRIMARY KEY' },
                { name: 'name', type: 'VARCHAR(100)', extra: 'NOT NULL' },
                { name: 'description', type: 'TEXT' },
                { name: 'price', type: 'DECIMAL(10,2)', extra: 'NOT NULL' },
                { name: 'stock', type: 'INT', extra: 'DEFAULT 0' },
                { name: 'created_at', type: 'TIMESTAMP', extra: 'DEFAULT CURRENT_TIMESTAMP' }
            ]
        })
    });
    return await response.json();
}
```

### List All Tables
```javascript
async function listTables() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'list_tables'
        })
    });
    const data = await response.json();
    console.log(data.data.tables);
}
```

### Describe Table Structure
```javascript
async function describeTable(tableName) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'describe_table',
            table: tableName
        })
    });
    return await response.json();
}
```

### Drop Table
```javascript
async function dropTable(tableName) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'drop_table',
            table: tableName
        })
    });
    return await response.json();
}
```

---

## CRUD OPERATIONS

### CREATE (Insert) - Add New User
```javascript
async function addUser(username, email, password) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'insert',
            table: 'users',
            data: {
                username: username,
                email: email,
                password: password
            }
        })
    });
    const result = await response.json();
    console.log('New user ID:', result.data.inserted_id);
    return result;
}

// Usage
addUser('john_doe', 'john@example.com', 'hashed_password_here');
```

### CREATE - Add New Product
```javascript
async function addProduct(name, description, price, stock) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'insert',
            table: 'products',
            data: {
                name: name,
                description: description,
                price: price,
                stock: stock
            }
        })
    });
    return await response.json();
}

// Usage
addProduct('Laptop', 'High-performance laptop', 1200.00, 15);
```

### READ (Select) - Get All Records
```javascript
async function getAllUsers() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'select',
            table: 'users'
        })
    });
    const data = await response.json();
    console.log('Users:', data.data.records);
    return data;
}
```

### READ - Get Records with Conditions
```javascript
async function getUserByUsername(username) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'select',
            table: 'users',
            conditions: {
                username: username
            }
        })
    });
    return await response.json();
}
```

### READ - Get Records with Pagination & Sorting
```javascript
async function getProductsPaginated(page = 1, perPage = 10) {
    const offset = (page - 1) * perPage;
    
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'select',
            table: 'products',
            limit: perPage,
            offset: offset,
            orderBy: {
                field: 'created_at',
                direction: 'DESC'
            }
        })
    });
    return await response.json();
}

// Usage
getProductsPaginated(1, 20); // Page 1, 20 items
```

### UPDATE - Update User Email
```javascript
async function updateUserEmail(userId, newEmail) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update',
            table: 'users',
            data: {
                email: newEmail
            },
            conditions: {
                id: userId
            }
        })
    });
    return await response.json();
}

// Usage
updateUserEmail(5, 'newemail@example.com');
```

### UPDATE - Update Product Stock
```javascript
async function updateProductStock(productId, newStock) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update',
            table: 'products',
            data: {
                stock: newStock
            },
            conditions: {
                id: productId
            }
        })
    });
    return await response.json();
}
```

### DELETE - Delete User
```javascript
async function deleteUser(userId) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            table: 'users',
            conditions: {
                id: userId
            }
        })
    });
    return await response.json();
}

// Usage
deleteUser(5);
```

---

## HTML FORM EXAMPLES

### User Registration Form
```html
<form id="registerForm">
    <input type="text" id="username" placeholder="Username" required>
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Password" required>
    <button type="submit">Register</button>
</form>

<script>
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'insert',
            table: 'users',
            data: {
                username: username,
                email: email,
                password: password // Hash this in production!
            }
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Registration successful! User ID: ' + result.data.inserted_id);
        e.target.reset();
    } else {
        alert('Error: ' + result.message);
    }
});
</script>
```

### Product Management Form
```html
<form id="productForm">
    <input type="text" id="productName" placeholder="Product Name" required>
    <textarea id="productDesc" placeholder="Description"></textarea>
    <input type="number" id="productPrice" placeholder="Price" step="0.01" required>
    <input type="number" id="productStock" placeholder="Stock" required>
    <button type="submit">Add Product</button>
</form>

<script>
document.getElementById('productForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'insert',
            table: 'products',
            data: {
                name: document.getElementById('productName').value,
                description: document.getElementById('productDesc').value,
                price: document.getElementById('productPrice').value,
                stock: document.getElementById('productStock').value
            }
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Product added successfully!');
        e.target.reset();
    }
});
</script>
```

---

## CUSTOM DATABASE CONFIGURATION

### Use Different Database
```javascript
async function queryDifferentDatabase() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'select',
            table: 'customers',
            // Override default database config
            db_host: 'localhost',
            db_name: 'my_other_database',
            db_user: 'root',
            db_pass: '',
            db_port: '3306'
        })
    });
    return await response.json();
}
```

---

## CUSTOM QUERIES (Advanced)

### Execute Custom SELECT Query
```javascript
async function customSelect() {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'custom_query',
            query: 'SELECT u.username, COUNT(o.id) as order_count FROM users u LEFT JOIN orders o ON u.id = o.user_id GROUP BY u.id',
            params: []
        })
    });
    return await response.json();
}
```

### Execute Custom Query with Parameters
```javascript
async function searchProducts(keyword) {
    const response = await fetch('default.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'custom_query',
            query: 'SELECT * FROM products WHERE name LIKE ? OR description LIKE ?',
            params: [`%${keyword}%`, `%${keyword}%`]
        })
    });
    return await response.json();
}
```

---

## COMPLETE REAL-WORLD EXAMPLE

### User Management System
```html
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
</head>
<body>
    <h1>User Management System</h1>
    
    <!-- Add User Form -->
    <div>
        <h2>Add New User</h2>
        <form id="addUserForm">
            <input type="text" id="username" placeholder="Username" required>
            <input type="email" id="email" placeholder="Email" required>
            <button type="submit">Add User</button>
        </form>
    </div>
    
    <!-- Users List -->
    <div>
        <h2>Users List</h2>
        <button onclick="loadUsers()">Refresh</button>
        <table id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    
    <script>
        const API_URL = 'default.php';
        
        // Load all users
        async function loadUsers() {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'select',
                    table: 'users'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = '';
                
                result.data.records.forEach(user => {
                    const row = `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>
                                <button onclick="deleteUser(${user.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
        
        // Add new user
        document.getElementById('addUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'insert',
                    table: 'users',
                    data: {
                        username: document.getElementById('username').value,
                        email: document.getElementById('email').value
                    }
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('User added successfully!');
                e.target.reset();
                loadUsers();
            } else {
                alert('Error: ' + result.message);
            }
        });
        
        // Delete user
        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) return;
            
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete',
                    table: 'users',
                    conditions: { id: userId }
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('User deleted successfully!');
                loadUsers();
            }
        }
        
        // Load users on page load
        loadUsers();
    </script>
</body>
</html>
```

---

## Available Actions Summary

| Action | Required Parameters | Description |
|--------|-------------------|-------------|
| `test_connection` | - | Test database connection |
| `create_database` | `db_name` | Create new database |
| `drop_database` | `db_name` | Delete database |
| `list_databases` | - | List all databases |
| `database_info` | - | Get database statistics |
| `create_table` | `table`, `columns` | Create new table |
| `drop_table` | `table` | Delete table |
| `list_tables` | - | List all tables |
| `describe_table` | `table` | Get table structure |
| `truncate_table` | `table` | Empty table |
| `insert` / `create` | `table`, `data` | Insert new record |
| `select` / `read` | `table` | Select records |
| `update` | `table`, `data`, `conditions` | Update records |
| `delete` | `table`, `conditions` | Delete records |
| `custom_query` | `query`, `params` | Execute custom SQL |

---

## Security Notes
- Always validate and sanitize user input on frontend
- Use HTTPS in production
- Hash passwords before storing
- Implement proper authentication
- Consider rate limiting
- Use environment variables for credentials

---

## Performance Tips
- Use pagination for large datasets
- Create indexes on frequently queried columns
- Use `limit` parameter to reduce data transfer
- Cache frequently accessed data
- Use prepared statements (handled automatically)

---

Made with ❤️ for easy frontend-backend integration!
