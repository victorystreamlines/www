
<?php
$host = 'localhost';
$dbname = 'naif10';
$user = 'root';
$pass = '';

try {
    // Connect to the database using PDO
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);

    // Success message
    echo "<center><h1>Connection successful. The database name is: " . $dbname . "<br></h1></center>";

    // Fetch all databases from the server
    $databases = $db->query('SHOW DATABASES');
    $dblists = $databases->fetchAll(PDO::FETCH_COLUMN);

    // تم تصحيح السطر التالي بوضع علامات اقتباس مزدوجة حول قيمة style
    echo "<b style='color:blue'>List of databases using PDO:<br></b>";
    echo "<ol>";
    foreach($dblists as $dblist) {
        echo "<i><li>" . htmlspecialchars($dblist) . "</li></i>";
    }
    echo "</ol>";

} catch (Exception $e) {
    // Error message with details
    echo "Connection failed: " . $e->getMessage();
}
