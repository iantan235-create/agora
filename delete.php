<?php
require_once "organizer_only.php";

// Get the event ID
$id = (int)($_POST["id"] ?? $_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: my_events.php");
    exit;
}

// Retrieve event owned by this organizer
$sql = "SELECT event_id, event_name, event_date,
               event_time, location, event_status
        FROM events
        WHERE event_id = ?
        AND organizer_id = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $id,
    $_SESSION["user_id"]
]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(403);
    die("Event not found or access denied.");
}

// Create CSRF token
if (!isset($_SESSION["cancel_event_token"])) {
    $_SESSION["cancel_event_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// =====================================
// Process Event Cancellation
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["token"] ?? "";
    $action = $_POST["action"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["cancel_event_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid security token.");
    }

    if ($action !== "cancel") {

        http_response_code(400);
        die("Invalid action.");

    } elseif (
        $event["event_status"] !== "active"
    ) {

        $error = "This event has already been cancelled.";

    } else {

        try {

            $pdo->beginTransaction();

            // Lock event and verify organizer ownership
            $sql = "SELECT event_id, event_name,
                           event_status
                    FROM events
                    WHERE event_id = ?
                    AND organizer_id = ?
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $id,
                $_SESSION["user_id"]
            ]);

            $current_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$current_event ||
                $current_event["event_status"] !== "active") {

                $pdo->rollBack();

                $error = "This event cannot be cancelled.";

            } else {

                // Change event status
                // Do not permanently delete the event
                $sql = "UPDATE events
                        SET event_status = 'cancelled',
                            cancelled_at = NOW()
                        WHERE event_id = ?
                        AND organizer_id = ?
                        AND event_status = 'active'";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $id,
                    $_SESSION["user_id"]
                ]);

                // Notify all registered students
                $message =
                    "Event cancelled: " .
                    $current_event["event_name"] .
                    ". Please check your registrations.";

                $sql = "INSERT INTO notifications
                        (
                            user_id,
                            event_id,
                            notification_type,
                            message
                        )
                        SELECT
                            er.user_id,
                            ?,
                            'event_cancelled',
                            ?
                        FROM event_registrations er
                        WHERE er.event_id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $id,
                    $message,
                    $id
                ]);

                // Save cancellation and notifications
                $pdo->commit();

                // Redirect after successful cancellation
                header(
                    "Location: my_events.php",
                    true,
                    303
                );
                exit;
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to cancel this event. " .
                     "Please try again.";
        }
    }
}

$is_cancelled =
    $event["event_status"] === "cancelled";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cancel Event - Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* =====================================
           Agora - Cancel Event Page
        ===================================== */

        .cancel-event-page {
            max-width: 850px;
            padding-top: 50px;
            padding-bottom: 90px;
        }

        .cancel-event-back {
            display: inline-block;
            color: #2563eb;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 28px;
        }

        .cancel-event-back:hover {
            text-decoration: underline;
        }

        .cancel-event-page h1 {
            font-size: clamp(32px, 5vw, 44px);
            letter-spacing: -1.5px;
            margin: 10px 0;
        }

        .cancel-event-intro {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 32px;
        }

        .cancel-event-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 36px;
        }

        .cancel-event-icon {
            width: 56px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fef2f2;
            color: #dc2626;
            font-size: 28px;
            font-weight: 700;
            border-radius: 16px;
            margin-bottom: 22px;
        }

        .cancel-event-card h2 {
            font-size: 26px;
            letter-spacing: -0.5px;
            margin: 0 0 12px;
            overflow-wrap: anywhere;
        }

        .cancel-event-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 26px;
        }

        .cancel-event-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
            margin-bottom: 26px;
        }

        .cancel-detail-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .cancel-detail-value {
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .cancel-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 28px;
            font-size: 14px;
            line-height: 1.8;
        }

        .cancel-warning strong {
            display: block;
            margin-bottom: 5px;
        }

        .cancel-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .cancel-event-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .cancel-event-form {
            margin: 0;
        }

        .cancel-danger-button {
            background: #dc2626;
            color: white;
            border: 0;
            border-radius: 10px;
            padding: 13px 22px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .cancel-danger-button:hover {
            background: #b91c1c;
        }

        .cancel-danger-button:focus-visible,
        .cancel-event-back:focus-visible {
            outline: 3px solid #fca5a5;
            outline-offset: 3px;
        }

        .cancel-back-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 13px 22px;
            color: #111827;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .cancel-back-button:hover {
            background: #f8fafc;
        }

        @media (max-width: 600px) {
            .cancel-event-page {
                padding-top: 32px;
            }

            .cancel-event-card {
                padding: 22px;
            }

            .cancel-event-details {
                grid-template-columns: 1fr;
                padding: 18px;
            }

            .cancel-event-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .cancel-event-form,
            .cancel-danger-button,
            .cancel-back-button {
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

        <a href="dashboard.php" class="brand">
            agora<span>.</span>
        </a>

        <a href="dashboard.php" class="logout">
            Dashboard
        </a>

    </div>

</header>

<!-- Main Content -->

<main class="container cancel-event-page">

    <a href="my_events.php" class="cancel-event-back">
        &larr; Back to My Events
    </a>

    <div class="eyebrow">
        Event Management
    </div>

    <h1>Cancel Event</h1>

    <p class="cancel-event-intro">
        Review the event information before
        confirming cancellation.
    </p>

    <!-- Error Message -->

    <?php if ($error !== "") { ?>

        <div class="cancel-error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <!-- Cancellation Card -->

    <section class="cancel-event-card">

        <div class="cancel-event-icon">
            !
        </div>

        <h2>
            <?php echo htmlspecialchars(
                $event["event_name"]
            ); ?>
        </h2>

        <p class="cancel-event-description">
            Please review this event carefully.
            Cancelling an event will notify
            its registered students.
        </p>

        <!-- Event Details -->

        <div class="cancel-event-details">

            <div>
                <span class="cancel-detail-label">
                    Event Date
                </span>

                <span class="cancel-detail-value">
                    <?php echo htmlspecialchars(
                        $event["event_date"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="cancel-detail-label">
                    Event Time
                </span>

                <span class="cancel-detail-value">
                    <?php echo htmlspecialchars(
                        $event["event_time"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="cancel-detail-label">
                    Location
                </span>

                <span class="cancel-detail-value">
                    <?php echo htmlspecialchars(
                        $event["location"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="cancel-detail-label">
                    Current Status
                </span>

                <?php if ($is_cancelled) { ?>

                    <span class="status-badge status-cancelled">
                        Cancelled
                    </span>

                <?php } else { ?>

                    <span class="status-badge status-active">
                        Active
                    </span>

                <?php } ?>

            </div>

        </div>

        <?php if ($is_cancelled) { ?>

            <!-- Already Cancelled -->

            <div class="cancel-error">
                This event has already been cancelled.
                No further cancellation is necessary.
            </div>

            <a href="my_events.php"
               class="cancel-back-button">
                Back to My Events
            </a>

        <?php } else { ?>

            <!-- Warning -->

            <div class="cancel-warning">

                <strong>
                    Important: Please confirm your decision.
                </strong>

                Are you sure you want to cancel this event?

                Registered students will receive an
                in-website cancellation notification.

                The event will no longer accept new
                registrations or attendance check-ins.

                Existing registration and attendance
                records will be preserved.

            </div>

            <!-- Confirmation Actions -->

            <div class="cancel-event-actions">

                <form
                    class="cancel-event-form"
                    action="delete.php?id=<?php
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
                                $_SESSION["cancel_event_token"],
                                ENT_QUOTES
                            );
                        ?>">

                    <input
                        type="hidden"
                        name="action"
                        value="cancel">

                    <button
                        type="submit"
                        class="cancel-danger-button">
                        Yes, Cancel Event
                    </button>

                </form>

                <a href="my_events.php"
                   class="cancel-back-button">
                    Keep Event
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
