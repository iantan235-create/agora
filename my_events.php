<?php
require_once "organizer_only.php";

$organizer_id = $_SESSION["user_id"];

$sql = "SELECT event_id,
               event_name,
               event_date,
               event_time,
               location,
               event_status
        FROM events
        WHERE organizer_id = ?
        ORDER BY event_date ASC,
                 event_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$organizer_id]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_events = count($events);
$active_events = 0;
$cancelled_events = 0;

foreach ($events as $event) {

    if ($event["event_status"] == "cancelled") {
        $cancelled_events++;
    } else {
        $active_events++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Events - Agora</title>

    <link rel="stylesheet" href="style.css">
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

<main class="container organizer-events-page">

    <div class="eyebrow">
        Event Management
    </div>

    <h1>My Events</h1>

    <p class="organizer-intro">
        Create, manage and monitor your club's events.
    </p>

    <div class="organizer-toolbar">

        <h2 class="section-title">
            Event Overview
        </h2>

        <a href="add.php" class="btn-primary">
            + Create Event
        </a>

    </div>

    <!-- Event Statistics -->

    <section class="organizer-stats">

        <div class="organizer-stat-card">

            <div class="organizer-stat-number">
                <?php echo $total_events; ?>
            </div>

            <div class="organizer-stat-label">
                Total Events
            </div>

        </div>

        <div class="organizer-stat-card">

            <div class="organizer-stat-number">
                <?php echo $active_events; ?>
            </div>

            <div class="organizer-stat-label">
                Active Events
            </div>

        </div>

        <div class="organizer-stat-card">

            <div class="organizer-stat-number">
                <?php echo $cancelled_events; ?>
            </div>

            <div class="organizer-stat-label">
                Cancelled Events
            </div>

        </div>

    </section>

    <!-- Event Listing -->

    <div class="results-header">

        <h2>Manage Events</h2>

        <span class="results-count">
            <?php echo $total_events; ?> Events
        </span>

    </div>

    <?php if ($total_events == 0) { ?>

        <div class="registration-empty">

            <h2>No Events Yet</h2>

            <p>
                Your club hasn't created any events.
                Create your first event to get started.
            </p>

            <a href="add.php" class="btn-primary">
                Create First Event
            </a>

        </div>

    <?php } else { ?>

        <div class="organizer-event-list">

            <?php foreach ($events as $event) { ?>

                <?php
                $id = (int)$event["event_id"];

                $is_cancelled =
                    $event["event_status"] == "cancelled";
                ?>

                <article class="organizer-event-card">

                    <!-- Event Name and Status -->

                    <div class="organizer-event-header">

                        <h3>
                            <?php echo htmlspecialchars(
                                $event["event_name"]
                            ); ?>
                        </h3>

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

                    <!-- Event Information -->

                    <div class="organizer-event-meta">

                        <div class="organizer-meta-item">

                            <span class="organizer-meta-label">
                                Date
                            </span>

                            <span class="organizer-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["event_date"]
                                ); ?>
                            </span>

                        </div>

                        <div class="organizer-meta-item">

                            <span class="organizer-meta-label">
                                Time
                            </span>

                            <span class="organizer-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["event_time"]
                                ); ?>
                            </span>

                        </div>

                        <div class="organizer-meta-item">

                            <span class="organizer-meta-label">
                                Location
                            </span>

                            <span class="organizer-meta-value">
                                <?php echo htmlspecialchars(
                                    $event["location"]
                                ); ?>
                            </span>

                        </div>

                        <div class="organizer-meta-item">

                            <span class="organizer-meta-label">
                                Status
                            </span>

                            <span class="organizer-meta-value">
                                <?php echo $is_cancelled
                                    ? "Cancelled"
                                    : "Active";
                                ?>
                            </span>

                        </div>

                    </div>

                    <!-- Management Actions -->

                    <div class="organizer-event-actions">

                        <a class="btn-secondary"
                           href="event_details.php?id=<?php
                               echo $id;
                           ?>">
                            Event Details
                        </a>

                        <a class="btn-secondary"
                           href="participants.php?id=<?php
                               echo $id;
                           ?>">
                            Participants
                        </a>

                        <a class="btn-secondary"
                           href="attendance_report.php?id=<?php
                               echo $id;
                           ?>">
                            Attendance Report
                        </a>

                        <?php if (!$is_cancelled) { ?>

                            <a class="btn-secondary"
                               href="edit.php?id=<?php
                                   echo $id;
                               ?>">
                                Edit Event
                            </a>

                            <a class="btn-primary"
                               href="checkin.php?id=<?php
                                   echo $id;
                               ?>">
                                QR Check-in
                            </a>

                        <?php } ?>

                    </div>

                    <!-- Cancellation -->

                    <?php if ($is_cancelled) { ?>

                        <p class="organizer-cancelled-note">
                            This event has been cancelled.
                            Its registration and attendance
                            records are preserved.
                        </p>

                    <?php } else { ?>

                        <a class="organizer-danger-link"
                           href="delete.php?id=<?php
                               echo $id;
                           ?>">
                            Cancel Event
                        </a>

                    <?php } ?>

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
