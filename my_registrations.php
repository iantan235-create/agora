<?php
session_start();
require_once "pdo.php";

// Only students can access this page
if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") != "student") {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT
            events.event_id,
            events.event_name,
            events.event_date,
            events.event_time,
            events.location,
            events.event_status,
            events.cancelled_at,
            event_registrations.registration_id,
            event_registrations.registered_at,
            event_registrations.attendance_status
        FROM event_registrations
        INNER JOIN events
        ON event_registrations.event_id = events.event_id
        WHERE event_registrations.user_id = ?
        ORDER BY events.event_date ASC,
                 events.event_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Registrations - Agora</title>

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

<main class="container registration-page">

    <div class="eyebrow">
        My Activities
    </div>

    <h1>My Registrations</h1>

    <p class="registration-intro">
        Manage your registered events and attendance.
    </p>

    <div class="results-header">

        <h2>Registered Events</h2>

        <span class="results-count">
            <?php echo count($registrations); ?>
            Registrations
        </span>

    </div>

    <?php if (count($registrations) == 0) { ?>

        <div class="registration-empty">

            <h2>No Registrations Yet</h2>

            <p>
                You haven't registered for any events.
                Explore upcoming activities at Taylor's.
            </p>

            <a href="index.php" class="btn-primary">
                Browse Events
            </a>

        </div>

    <?php } else { ?>

        <div class="registration-list">

            <?php foreach ($registrations as $event) { ?>

                <?php
                $is_cancelled =
                    $event["event_status"] == "cancelled";

                $attendance = ucwords(
                    str_replace(
                        "_",
                        " ",
                        $event["attendance_status"]
                    )
                );
                ?>

                <article class="registration-card">

                    <div class="registration-card-header">

                        <h2>
                            <?php echo htmlspecialchars(
                                $event["event_name"]
                            ); ?>
                        </h2>

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

                    <div class="registration-meta">

                        <div class="registration-meta-item">

                            <span class="registration-meta-label">
                                Event Date
                            </span>

                            <span class="registration-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["event_date"]
                                ); ?>
                            </span>

                        </div>

                        <div class="registration-meta-item">

                            <span class="registration-meta-label">
                                Event Time
                            </span>

                            <span class="registration-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["event_time"]
                                ); ?>
                            </span>

                        </div>

                        <div class="registration-meta-item">

                            <span class="registration-meta-label">
                                Location
                            </span>

                            <span class="registration-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["location"]
                                ); ?>
                            </span>

                        </div>

                        <div class="registration-meta-item">

                            <span class="registration-meta-label">
                                Attendance
                            </span>

                            <span class="registration-meta-value">
                                <?php echo htmlspecialchars(
                                    $attendance
                                ); ?>
                            </span>

                        </div>

                    </div>

                    <?php if ($is_cancelled) { ?>

                        <div class="registration-notice">
                            This event has been cancelled
                            by the organizer.
                        </div>

                        <?php if (!empty(
                            $event["cancelled_at"]
                        )) { ?>

                            <p class="registration-muted">
                                Cancelled at:
                                <?php echo htmlspecialchars(
                                    $event["cancelled_at"]
                                ); ?>
                            </p>

                        <?php } ?>

                    <?php } else { ?>

                        <p class="registration-muted">
                            Registered at:
                            <?php echo htmlspecialchars(
                                $event["registered_at"]
                            ); ?>
                        </p>

                    <?php } ?>

                    <div class="registration-actions">

                        <a class="btn-secondary"
                           href="event_details.php?id=<?php
                               echo (int)$event["event_id"];
                           ?>">
                            Event Details
                        </a>

                        <?php if (!$is_cancelled) { ?>

                            <a class="btn-primary"
                               href="show_qr.php?id=<?php
                                   echo (int)$event["event_id"];
                               ?>">
                                Show QR Code
                            </a>

                            <a class="btn-secondary"
                               href="cancel_registration.php?id=<?php
                                   echo (int)$event["event_id"];
                               ?>">
                                Cancel Registration
                            </a>

                        <?php } ?>

                    </div>

                </article>

            <?php } ?>

        </div>

    <?php } ?>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

</body>
</html>
