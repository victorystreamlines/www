<<<<<<< HEAD
<?php
$host = 'localhost';
$dbname = 'naif10';
$user = 'root';
$pass = '';

// Fix: Proper PDO connection with error handling and charset
try {
    // Establish PDO connection with UTF-8 charset for proper Arabic text support
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    // تمت الإزالة حسب طلبك يا ابو عليوي

    // Display successful connection message
    echo "Connection successful And the database name is : " . $dbname . "<br>";
    
    // Query to get all databases from MySQL server
    $databases = $db->query('SHOW DATABASES');
    $dblists = $databases->fetchAll(PDO::FETCH_COLUMN);
    
    echo "List of Databases Using PDO :<br>";
    
    // Fix: Add line break after each database name for proper display
    foreach($dblists as $dblist) {
        echo $dblist . "<br>"; // Added <br> to display each database on new line
    }

} catch (Exception $e) {
    // Improved error message with actual error details for debugging
    echo "Connection failed: " . $c->getMessage();
}
=======
>>>>>>> 72fdcb3eaf5b05be122d4c5d432b4b9abdf08d4e
