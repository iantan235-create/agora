<?php

// =====================================
// Agora - Student Access Protection
// =====================================

// Start session only if necessary
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to MySQL
require_once __DIR__ . "/pdo.php";

// =====================================
// 1. Check Login Status
// =====================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


if (($_SESSION["role"] ?? "") !== "student") {

    // Organizers cannot access student pages
    header("Location: dashboard.php");
    exit;
}

$student_id = (int)$_SESSION["user_id"];

$sql = "SELECT user_id,
               full_name,
               role,
               account_status
        FROM users
        WHERE user_id = ?
        AND role = 'student'
        AND account_status = 'approved'
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {

    $_SESSION = [];

    session_regenerate_id(true);

    header("Location: login.php");
    exit;
}

$_SESSION["full_name"] = $student["full_name"];

?>
