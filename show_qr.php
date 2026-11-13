<?php
session_start();
require_once "pdo.php";

// Only logged-in students can access QR codes
if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") != "student") {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Retrieve event ID from URL
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: my_registrations.php");
    exit;
}

// Retrieve this student's registration
$sql = "SELECT
            e.event_id,
            e.event_name,
            e.event_date,
            e.event_time,
            e.location,
            e.event_status,
            er.checkin_token,
            er.attendance_status
        FROM event_registrations er
        INNER JOIN events e
        ON er.event_id = e.event_id
        WHERE er.event_id = ?
        AND er.user_id = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $id,
    $user_id
]);

$registration = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$registration) {
    http_response_code(404);
    die("Registration not found.");
}

// Check event status
$is_cancelled =
    $registration["event_status"] == "cancelled";

// Verify the existing QR token
$qr_token = $registration["checkin_token"] ?? "";

$valid_qr = is_string($qr_token) &&
    preg_match("/^[a-fA-F0-9]{64}$/", $qr_token);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Attendance QR Code - Agora</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">

    <div class="container header-content">

        <a href="dashboard.php" class="brand">
            agora<span>.</span>
        </a>

        <a href="dashboard.php" class="logout">
            Dashboard
        </a>

    </div>

</header>

<main class="container student-qr-page">

    <a href="my_registrations.php"
       class="event-form-back">
        &larr; Back to My Registrations
    </a>

    <div class="eyebrow">
        Event Attendance
    </div>

    <h1>My QR Code</h1>

    <p class="student-qr-intro">
        Show your unique attendance QR code
        to the event organizer.
    </p>

    <section class="student-qr-panel">

        <h2>
            <?php echo htmlspecialchars(
                $registration["event_name"]
            ); ?>
        </h2>

        <?php if ($is_cancelled) { ?>

            <div class="student-qr-warning">
                This event has been cancelled.
                Attendance QR code is no longer available.
            </div>

        <?php } elseif (!$valid_qr) { ?>

            <div class="student-qr-warning">
                Your attendance QR code is unavailable.
                Please contact the event organizer.
            </div>

        <?php } else { ?>

            <span class="status-badge status-active">
                Event Active
            </span>

            <div class="student-qr-code-box">

                <div id="student-qr-code"
                     aria-label="Attendance QR code">
                </div>

            </div>

            <p id="qr-message"
               class="registration-muted"
               role="status">
                Present this QR code to the organizer.
            </p>

            <div class="student-qr-instructions">
                Ask the organizer to scan this QR code
                using Agora's QR Check-in page.
                Your attendance will be recorded
                after the organizer confirms check-in.
            </div>

        <?php } ?>

        <div class="student-qr-details">

            <div>
                <span class="student-qr-detail-label">
                    Event Date
                </span>

                <span class="student-qr-detail-value">
                    <?php echo htmlspecialchars(
                        $registration["event_date"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="student-qr-detail-label">
                    Event Time
                </span>

                <span class="student-qr-detail-value">
                    <?php echo htmlspecialchars(
                        $registration["event_time"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="student-qr-detail-label">
                    Location
                </span>

                <span class="student-qr-detail-value">
                    <?php echo htmlspecialchars(
                        $registration["location"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="student-qr-detail-label">
                    Attendance Status
                </span>

                <span class="student-qr-detail-value">
                    <?php echo htmlspecialchars(
                        ucwords(
                            str_replace(
                                "_",
                                " ",
                                $registration["attendance_status"]
                            )
                        )
                    ); ?>
                </span>
            </div>

        </div>

        <div class="student-qr-actions">

            <a href="event_details.php?id=<?php
                echo $id;
            ?>"
               class="btn-secondary">
                Event Details
            </a>

            <a href="my_registrations.php"
               class="btn-primary">
                My Registrations
            </a>

        </div>

    </section>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

<?php if (!$is_cancelled && $valid_qr) { ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
if (typeof QRCode !== "undefined") {

    new QRCode(
        document.getElementById("student-qr-code"),
        {
            text: <?php echo json_encode(
                $qr_token,
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
            ); ?>,
            width: 240,
            height: 240,
            correctLevel: QRCode.CorrectLevel.H
        }
    );

} else {

    document.getElementById("qr-message").textContent =
        "QR generator could not load. " +
        "Please check your internet connection.";
}
</script>

<?php } ?>

</body>
</html>
