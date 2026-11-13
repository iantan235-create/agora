<?php
require_once __DIR__ . "/organizer_only.php";

// =====================================
// Agora - Secure QR Code Check-in
// =====================================

$organizer_id = (int)$_SESSION["user_id"];
$event_id = (int)($_GET["id"] ?? 0);

if ($event_id <= 0) {
    header("Location: my_events.php");
    exit;
}

// Safely display text in HTML
function checkinEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

// =====================================
// 1. Verify Event Ownership
// =====================================

$stmt = $pdo->prepare(
    "SELECT event_id, event_name, event_date,
            event_time, location, event_status
     FROM events
     WHERE event_id = ?
     AND organizer_id = ?"
);

$stmt->execute([
    $event_id,
    $organizer_id
]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(403);
    exit("Event not found or access denied.");
}

// =====================================
// 2. Create CSRF Token
// =====================================

if (!isset($_SESSION["checkin_csrf"])) {
    $_SESSION["checkin_csrf"] =
        bin2hex(random_bytes(32));
}

$error = "";

$flash = $_SESSION["checkin_result"] ?? null;

unset($_SESSION["checkin_result"]);

// =====================================
// 3. Process QR Check-in
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf = $_POST["csrf_token"] ?? "";
    $qr_token = $_POST["qr_token"] ?? "";

    // Verify CSRF token
    if (!is_string($csrf) ||
        !hash_equals(
            $_SESSION["checkin_csrf"],
            $csrf
        )) {

        http_response_code(403);
        exit("Invalid security token.");
    }

    // Verify QR token format
    if (!is_string($qr_token) ||
        !preg_match(
            '/^[a-fA-F0-9]{64}$/D',
            trim($qr_token)
        )) {

        $error = "Invalid QR code. " .
                 "Scan an Agora attendance QR code.";

    } else {

        $qr_token = trim($qr_token);

        try {

            $pdo->beginTransaction();

            // =====================================
            // 4. Lock and Verify Event
            // =====================================

            $stmt = $pdo->prepare(
                "SELECT event_status
                 FROM events
                 WHERE event_id = ?
                 AND organizer_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([
                $event_id,
                $organizer_id
            ]);

            $latest_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$latest_event ||
                $latest_event["event_status"] !== "active") {

                throw new RuntimeException(
                    "This event is not open for check-in."
                );
            }

            // =====================================
            // 5. Verify Student QR Registration
            // =====================================

            $stmt = $pdo->prepare(
                "SELECT
                    er.registration_id,
                    er.attendance_status,
                    u.full_name
                 FROM event_registrations er
                 INNER JOIN users u
                 ON u.user_id = er.user_id
                 WHERE er.event_id = ?
                 AND er.checkin_token = ?
                 AND u.role = 'student'
                 AND u.account_status = 'approved'
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([
                $event_id,
                $qr_token
            ]);

            $registration = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            // =====================================
            // 6. Check Registration Status
            // =====================================

            if (!$registration) {

                $result_type = "error";

                $result_message =
                    "QR code not recognized for this event.";

            } elseif (
                $registration["attendance_status"] === "present"
            ) {

                $result_type = "warning";

                $result_message =
                    $registration["full_name"] .
                    " has already checked in.";

            } else {

                // =====================================
                // 7. Mark Student Present
                // =====================================

                $stmt = $pdo->prepare(
                    "UPDATE event_registrations
                     SET attendance_status = 'present',
                         attendance_marked_at = NOW()
                     WHERE registration_id = ?
                     AND event_id = ?"
                );

                $stmt->execute([
                    $registration["registration_id"],
                    $event_id
                ]);

                $result_type = "success";

                $result_message =
                    "Check-in successful! " .
                    $registration["full_name"] .
                    " is marked Present.";
            }

            // =====================================
            // 8. Save Transaction
            // =====================================

            $pdo->commit();

            $_SESSION["checkin_result"] = [
                "type" => $result_type,
                "message" => $result_message
            ];

            // Redirect after form submission
            header(
                "Location: checkin.php?id=" . $event_id,
                true,
                303
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Check-in was not completed. " .
                "Check the event status and try again.";
        }
    }
}

$is_cancelled =
    $event["event_status"] !== "active";

header("Cache-Control: no-store, private");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>QR Check-in - Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>

        /* =====================================
           Agora QR Check-in Design
        ===================================== */

        .qr-security-page {
            max-width: 860px;
            padding: 48px 24px 80px;
        }

        .qr-security-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 30px;
            margin-top: 24px;
        }

        .qr-security-card h2 {
            margin-top: 0;
        }

        .qr-security-note {
            padding: 15px 18px;
            background: #eff6ff;
            border-radius: 12px;
            color: #1d4ed8;
            font-size: 14px;
        }

        .qr-security-success,
        .qr-security-warning,
        .qr-security-error {
            border-radius: 12px;
            padding: 15px 18px;
            margin: 18px 0;
        }

        .qr-security-success {
            background: #ecfdf5;
            color: #047857;
        }

        .qr-security-warning {
            background: #fffbeb;
            color: #92400e;
        }

        .qr-security-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .qr-security-reader {
            max-width: 550px;
            margin: 24px auto;
        }

        #qr-reader {
            width: 100%;
            overflow: hidden;
        }

        #qr-reader video,
        #qr-reader img,
        #qr-reader canvas {
            max-width: 100%;
        }

        .qr-security-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 18px;
        }

        .qr-security-actions button {
            font: inherit;
            cursor: pointer;
        }

        .qr-security-actions button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .qr-security-status {
            color: #64748b;
            font-size: 14px;
        }

        .qr-security-details {
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 600px) {

            .qr-security-card {
                padding: 20px;
            }

            .qr-security-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .qr-security-actions button,
            .qr-security-actions a {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }

    </style>
</head>

<body>

<!-- Header -->

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

<!-- Main Content -->

<main class="container qr-security-page">

    <a class="event-form-back"
       href="my_events.php">

        &larr; Back to My Events

    </a>

    <div class="eyebrow">
        Attendance Management
    </div>

    <h1>QR Code Check-in</h1>

    <!-- Event Information -->

    <p class="qr-security-details">

        <strong>
            <?php echo checkinEsc(
                $event["event_name"]
            ); ?>
        </strong>

        —
        <?php echo checkinEsc(
            $event["event_date"]
        ); ?>,

        <?php echo checkinEsc(
            substr($event["event_time"], 0, 5)
        ); ?>

        —
        <?php echo checkinEsc(
            $event["location"]
        ); ?>

    </p>

    <!-- Check-in Result -->

    <?php if (
        is_array($flash) &&
        in_array(
            $flash["type"] ?? "",
            ["success", "warning", "error"],
            true
        )
    ): ?>

        <div
            class="qr-security-<?php
                echo checkinEsc($flash["type"]);
            ?>"
            role="status">

            <?php echo checkinEsc(
                $flash["message"] ?? ""
            ); ?>

        </div>

    <?php endif; ?>

    <!-- Error Message -->

    <?php if ($error !== ""): ?>

        <div class="qr-security-error"
             role="alert">

            <?php echo checkinEsc($error); ?>

        </div>

    <?php endif; ?>

    <!-- QR Scanner -->

    <?php if ($is_cancelled): ?>

        <div class="qr-security-error"
             role="alert">

            This event is cancelled.
            QR check-in is unavailable.

        </div>

    <?php else: ?>

        <section class="qr-security-card">

            <h2>Scan Student QR Code</h2>

            <p class="qr-security-note">

                Ask the student to open My Registrations
                and show their QR code.

                Use the camera or upload an image,
                then confirm the check-in.

            </p>

            <!-- Camera / QR Upload Scanner -->

            <div class="qr-security-reader">

                <div id="qr-reader"></div>

            </div>

            <p
                id="scan-status"
                class="qr-security-status"
                role="status"
                aria-live="polite">

                Choose a camera or upload a QR image
                to scan.

            </p>

            <!-- Check-in Confirmation Form -->

            <form
                action="checkin.php?id=<?php
                    echo $event_id;
                ?>"
                method="POST"
                id="checkin-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo checkinEsc(
                        $_SESSION["checkin_csrf"]
                    ); ?>">

                <input
                    type="hidden"
                    name="qr_token"
                    id="qr-token">

                <div class="qr-security-actions">

                    <button
                        class="btn-primary"
                        type="submit"
                        id="confirm-checkin"
                        disabled>

                        Confirm Check-in

                    </button>

                    <a
                        class="btn-secondary"
                        href="participants.php?id=<?php
                            echo $event_id;
                        ?>">

                        Manual Attendance

                    </a>

                </div>

            </form>

        </section>

    <?php endif; ?>

    <!-- Alternative Attendance Method -->

    <section class="qr-security-card">

        <h2>Need Another Attendance Method?</h2>

        <p class="qr-security-details">

            Use the Participants page if the student
            cannot display a QR code.

        </p>

        <a
            class="btn-secondary"
            href="participants.php?id=<?php
                echo $event_id;
            ?>">

            View Participants

        </a>

    </section>

</main>

<!-- Footer -->

<footer class="footer">

    <div class="container">

        Agora — Taylor's University Event Platform

    </div>

</footer>

<!-- JavaScript QR Scanner -->

<?php if (!$is_cancelled): ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>

// =====================================
// Agora - QR Scanner JavaScript
// =====================================

const scanStatus =
    document.getElementById("scan-status");

const scannedToken =
    document.getElementById("qr-token");

const confirmCheckin =
    document.getElementById("confirm-checkin");

// Check if the QR library loaded
if (typeof Html5QrcodeScanner === "undefined") {

    scanStatus.textContent =
        "Scanner library unavailable. " +
        "Check your internet connection.";

} else {

    let scanComplete = false;

    // Create scanner
    const scanner = new Html5QrcodeScanner(
        "qr-reader",
        {
            fps: 10,
            qrbox: {
                width: 220,
                height: 220
            }
        },
        false
    );

    // =====================================
    // QR Scan Success
    // =====================================

    function onScanSuccess(decodedText) {

        if (scanComplete) {
            return;
        }

        const value = decodedText.trim();

        // Validate QR token format
        if (!/^[a-fA-F0-9]{64}$/.test(value)) {

            scanStatus.textContent =
                "Invalid code format. " +
                "Scan an Agora attendance QR code.";

            return;
        }

        scanComplete = true;

        // Store the token in hidden form
        scannedToken.value = value;

        // Enable confirmation
        confirmCheckin.disabled = false;

        scanStatus.textContent =
            "QR code captured. " +
            "Click Confirm Check-in to continue.";

        // Stop scanner
        Promise.resolve(
            scanner.clear()
        ).catch(function () {

            // The QR code has already been captured.

        });
    }

    // =====================================
    // QR Scan Failure
    // =====================================

    function onScanFailure(error) {

        // Incomplete frames are normal.
        // Continue scanning.

    }

    // Start camera / upload interface
    scanner.render(
        onScanSuccess,
        onScanFailure
    );

}

</script>

<?php endif; ?>

</body>
</html>