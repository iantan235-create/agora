<?php
session_start();
require_once "pdo.php";

// =====================================
// 1. Student Access Control
// =====================================

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "student"
) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get event ID from URL or form
$id = (int)($_POST["id"] ?? $_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// =====================================
// 2. Retrieve Event Details
// =====================================

$sql = "SELECT
            event_id,
            event_name,
            event_date,
            event_time,
            location,
            description,
            event_status
        FROM events
        WHERE event_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    die("Event not found.");
}

// =====================================
// 3. Check Existing Registration
// =====================================

$sql = "SELECT registration_id
        FROM event_registrations
        WHERE event_id = ?
        AND user_id = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id, $user_id]);

$is_registered = (bool)$stmt->fetch(
    PDO::FETCH_ASSOC
);

// =====================================
// 4. Create Security Token
// =====================================

if (!isset($_SESSION["registration_token"])) {
    $_SESSION["registration_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// =====================================
// 5. Process Event Registration
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION["registration_token"],
            $token
        )
    ) {
        http_response_code(403);
        die("Invalid security token.");
    }

    if ($event["event_status"] !== "active") {

        $error = "This event is no longer available.";

    } elseif ($is_registered) {

        $error = "You have already registered for this event.";

    } else {

        try {

            $pdo->beginTransaction();

            // Verify the student's account is approved
            $sql = "SELECT user_id
                    FROM users
                    WHERE user_id = ?
                    AND role = 'student'
                    AND account_status = 'approved'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    "Student account is not approved."
                );
            }

            // Lock event before registration
            $sql = "SELECT event_status
                    FROM events
                    WHERE event_id = ?
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            $current_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (
                !$current_event ||
                $current_event["event_status"] !== "active"
            ) {
                throw new Exception(
                    "This event cannot accept registrations."
                );
            }

            // Check registration again inside transaction
            $sql = "SELECT registration_id
                    FROM event_registrations
                    WHERE event_id = ?
                    AND user_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $user_id]);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    "You are already registered."
                );
            }

            // Generate a unique attendance QR token
            $checkin_token = bin2hex(
                random_bytes(32)
            );

            // Save student registration
            $sql = "INSERT INTO event_registrations
                    (event_id, user_id, checkin_token)
                    VALUES (?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $id,
                $user_id,
                $checkin_token
            ]);

            // Create registration notification
            $message =
                "Registration confirmed: " .
                $event["event_name"] .
                ". You have successfully registered.";

            $sql = "INSERT INTO notifications
                    (user_id,
                     event_id,
                     notification_type,
                     message)
                    VALUES (?, ?, 'registration', ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $user_id,
                $id,
                $message
            ]);

            // Save all database changes
            $pdo->commit();

            // Prevent duplicate submission
            header(
                "Location: my_registrations.php",
                true,
                303
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Unable to complete registration. " .
                "Please check your account or " .
                "registration status and try again.";
        }
    }
}

$is_cancelled =
    $event["event_status"] !== "active";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Event Registration - Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* =================================
           Agora Event Registration Page
        ================================= */

        .register-page {
            max-width: 860px;
            padding-top: 50px;
            padding-bottom: 90px;
        }

        .register-back {
            display: inline-block;
            color: #2563eb;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .register-back:hover {
            text-decoration: underline;
        }

        .register-page h1 {
            font-size: clamp(32px, 5vw, 44px);
            letter-spacing: -1.5px;
            margin: 12px 0;
        }

        .register-intro {
            color: #64748b;
            margin-bottom: 30px;
        }

        .register-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 34px;
        }

        .register-card-header {
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }

        .register-card-header h2 {
            font-size: 27px;
            letter-spacing: -0.5px;
            margin: 14px 0 8px;
            overflow-wrap: anywhere;
        }

        .register-card-header p {
            color: #64748b;
            font-size: 14px;
        }

        .register-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 28px;
        }

        .register-info-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .register-info-value {
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .register-description {
            border-top: 1px solid #e5e7eb;
            padding-top: 24px;
            margin-bottom: 26px;
        }

        .register-description h3 {
            font-size: 18px;
            margin: 0 0 12px;
        }

        .register-description p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .register-confirm-panel {
            background: #eff6ff;
            padding: 22px;
            border-radius: 14px;
            margin-bottom: 26px;
        }

        .register-confirm-panel h3 {
            color: #1d4ed8;
            font-size: 17px;
            margin: 0 0 8px;
        }

        .register-confirm-panel p {
            color: #1d4ed8;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }

        .register-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
        }

        .register-submit {
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 10px;
            padding: 14px 24px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .register-submit:hover {
            background: #1d4ed8;
        }

        .register-submit:focus-visible {
            outline: 3px solid #93c5fd;
            outline-offset: 3px;
        }

        .register-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 13px 22px;
            font-size: 14px;
            font-weight: 700;
        }

        .register-secondary:hover {
            background: #f8fafc;
        }

        .register-success {
            background: #ecfdf5;
            color: #047857;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .register-error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        @media (max-width: 600px) {
            .register-page {
                padding-top: 32px;
            }

            .register-card {
                padding: 22px;
            }

            .register-info-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .register-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .register-actions form,
            .register-submit,
            .register-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>

</head>

<body>

<!-- Agora Header -->

<header class="site-header">

    <div class="container header-content">

        <a href="index.php" class="brand">
            agora<span>.</span>
        </a>

        <a href="dashboard.php" class="logout">
            Dashboard
        </a>

    </div>

</header>

<!-- Registration Page -->

<main class="container register-page">

    <a
        href="event_details.php?id=<?php echo $id; ?>"
        class="register-back">
        &larr; Back to Event Details
    </a>

    <div class="eyebrow">
        Event Registration
    </div>

    <h1>Confirm Registration</h1>

    <p class="register-intro">
        Review the event information
        before confirming your participation.
    </p>

    <!-- Error Message -->

    <?php if ($error !== "") { ?>

        <div class="register-error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <!-- Event Card -->

    <section class="register-card">

        <div class="register-card-header">

            <?php if ($is_cancelled) { ?>

                <span class="status-badge status-cancelled">
                    Cancelled
                </span>

            <?php } else { ?>

                <span class="status-badge status-active">
                    Open for Registration
                </span>

            <?php } ?>

            <h2>
                <?php echo htmlspecialchars(
                    $event["event_name"]
                ); ?>
            </h2>

            <p>
                Please check the event details below.
            </p>

        </div>

        <!-- Event Information -->

        <div class="register-info-grid">

            <div>
                <span class="register-info-label">
                    Event Date
                </span>

                <span class="register-info-value">
                    <?php echo htmlspecialchars(
                        $event["event_date"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="register-info-label">
                    Event Time
                </span>

                <span class="register-info-value">
                    <?php echo htmlspecialchars(
                        substr($event["event_time"], 0, 5)
                    ); ?>
                </span>
            </div>

            <div>
                <span class="register-info-label">
                    Location
                </span>

                <span class="register-info-value">
                    <?php echo htmlspecialchars(
                        $event["location"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="register-info-label">
                    Registration Status
                </span>

                <span class="register-info-value">
                    <?php
                    if ($is_registered) {
                        echo "Registered";
                    } elseif ($is_cancelled) {
                        echo "Unavailable";
                    } else {
                        echo "Not Registered";
                    }
                    ?>
                </span>
            </div>

        </div>

        <!-- Event Description -->

        <?php if (!empty($event["description"])) { ?>

            <div class="register-description">

                <h3>About This Event</h3>

                <p>
                    <?php echo nl2br(htmlspecialchars(
                        $event["description"]
                    )); ?>
                </p>

            </div>

        <?php } ?>

        <!-- Registration Actions -->

        <?php if ($is_cancelled) { ?>

            <div class="register-error">
                This event has been cancelled.
                New registrations are not available.
            </div>

            <a
                class="register-secondary"
                href="event_details.php?id=<?php
                    echo $id;
                ?>">
                Back to Event Details
            </a>

        <?php } elseif ($is_registered) { ?>

            <div class="register-success">
                You have already registered
                for this event.
            </div>

            <div class="register-actions">

                <a
                    href="my_registrations.php"
                    class="register-submit">
                    View My Registrations
                </a>

                <a
                    href="event_details.php?id=<?php
                        echo $id;
                    ?>"
                    class="register-secondary">
                    Event Details
                </a>

            </div>

        <?php } else { ?>

            <div class="register-confirm-panel">

                <h3>Ready to join this event?</h3>

                <p>
                    Confirming your registration
                    will reserve your participation
                    and create a unique attendance
                    QR code in My Registrations.
                </p>

            </div>

            <div class="register-actions">

                <form
                    action="register_event.php?id=<?php
                        echo $id;
                    ?>"
                    method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo $id; ?>">

                    <input
                        type="hidden"
                        name="token"
                        value="<?php
                            echo htmlspecialchars(
                                $_SESSION["registration_token"],
                                ENT_QUOTES
                            );
                        ?>">

                    <button
                        class="register-submit"
                        type="submit">
                        Confirm Registration
                    </button>

                </form>

                <a
                    href="event_details.php?id=<?php
                        echo $id;
                    ?>"
                    class="register-secondary">
                    Back to Event Details
                </a>

            </div>

        <?php } ?>

    </section>

</main>

<!-- Footer -->

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
