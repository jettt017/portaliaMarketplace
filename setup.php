<?php
// Setup helper to create and seed the database programmatically
// Run via CLI: php setup.php
// Or via Browser: http://localhost:8000/setup.php

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server first (without database)
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "Connecting to MySQL server... Success!<br>\n";

    // Read database.sql file
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        die("Error: database.sql file not found in " . __DIR__);
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "Reading database.sql... Success!<br>\n";
    echo "Initializing database 'portaliadb' and importing tables...<br>\n";
    
    // Execute the SQL file query by query (split by ;)
    // To handle multiple queries in PDO, we can execute the whole block directly
    $pdo->exec($sql);
    
    echo "<h3 style='color: green;'>Database setup completed successfully!</h3>\n";
    echo "The database 'portaliadb' has been created, and all mock data (users, products, categories, chats, transactions) is imported.<br><br>\n";
    echo "You can now log in using:<br>\n";
    echo "👤 Student: <strong>budi@student.ac.id</strong> / Password: <strong>password</strong><br>\n";
    echo "⚙️ Admin: <strong>admin@portalia.ac.id</strong> / Password: <strong>password</strong><br><br>\n";
    echo "<a href='marketplace/welcome.php'>Go to Welcome Screen &rarr;</a>\n";

} catch (PDOException $e) {
    die("<h3 style='color: red;'>Setup Failed:</h3>" . $e->getMessage() . "<br><br>Make sure your XAMPP or Laragon MySQL database server is running.");
}
?>
