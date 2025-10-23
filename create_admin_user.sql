-- Create dedicated MySQL admin user
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Admin@5007';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;

-- Also create for 127.0.0.1
CREATE USER IF NOT EXISTS 'admin'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY 'Admin@5007';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'127.0.0.1' WITH GRANT OPTION;

FLUSH PRIVILEGES;

-- Verify
SELECT user, host, plugin FROM mysql.user WHERE user IN ('root', 'admin');
SHOW GRANTS FOR 'admin'@'localhost';
