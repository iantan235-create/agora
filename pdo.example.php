<?php

$host = "localhost";
$port = "8889";
$dbname = "agora";

$username = "YOUR_MYSQL_USERNAME";
$password = "YOUR_MYSQL_PASSWORD";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Database connection failed.");
}