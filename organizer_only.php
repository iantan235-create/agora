<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to MySQL
require_once __DIR__ . "/pdo.php";

// =====================================
// 1. Check Login Status
// =====================================

if (!isset($_SESSION["user_id"])) {

    // User is not logged in
    header("Location: login.php");
    exit;
}

// =====================================
// 2. Check Session Role
// =====================================

if (($_SESSION["role"] ?? "") !== "organizer") {

    // Students cannot access organizer pages
    header("Location: dashboard.php");
    exit;
}

// =====================================
// 3. Check Organizer Approval
// =====================================

$organizer_id = (int)$_SESSION["user_id"];

$sql = "SELECT user_id, full_name,
               role, account_status
        FROM users
        WHERE user_id = ?
        AND role = 'organizer'
        AND account_status = 'approved'
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([$organizer_id]);

$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

// =====================================
// 4. Reject Invalid Organizer Accounts
// =====================================

if (!$organizer) {

    // Clear authentication information
    $_SESSION = [];

    // Replace the old session identifier
    session_regenerate_id(true);

    // Redirect to login
    header("Location: login.php");
    exit;
}

// =====================================
// 5. Access Approved
// =====================================

// Refresh the organizer's displayed name
$_SESSION["full_name"] = $organizer["full_name"];

// The protected page can now continue
?>
