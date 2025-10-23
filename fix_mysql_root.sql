-- Reset root password and ensure proper authentication
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'P@master5007';
ALTER USER 'root'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY 'P@master5007';

-- Grant all privileges
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify the changes
SELECT user, host, plugin FROM mysql.user WHERE user='root';
