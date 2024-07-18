<?php
$host = 'localhost';
$db = 'affiliate_system';
$user = 'root'; // Change as needed
$pass = ''; // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
