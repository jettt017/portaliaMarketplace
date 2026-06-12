<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=portaliadb', 'root', '');
    $stmt=$db->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    echo "Total users: " . count($users) . "\n";
    foreach($users as $u) {
        echo $u['email'] . " | " . $u['role'] . "\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
