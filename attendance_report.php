<?php
require_once "organizer_only.php";

// Get the selected event ID
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: my_events.php");
    exit;
}

// Retrieve the organizer's event
$sql = "SELECT event_id,
               event_name,
               event_date,
               event_time,
               location,
               event_status,
               cancelled_at
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

// =====================================
// Calculate Attendance Statistics
// =====================================

$sql = "SELECT
            COUNT(*) AS total_registered,

            COALESCE(SUM(
                CASE
                    WHEN attendance_status = 'present'
                    THEN 1 ELSE 0
                END
            ), 0) AS present_count,

            COALESCE(SUM(
                CASE
                    WHEN attendance_status = 'absent'
                    THEN 1 ELSE 0
                END
            ), 0) AS absent_count,

            COALESCE(SUM(
                CASE
                    WHEN attendance_status = 'not_marked'
                    THEN 1 ELSE 0
                END
            ), 0) AS not_marked_count

        FROM event_registrations
        WHERE event_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Convert results into integers
$total = (int)$stats["total_registered"];
$present = (int)$stats["present_count"];
$absent = (int)$stats["absent_count"];
$not_marked = (int)$stats["not_marked_count"];

// Calculate attendance percentage
if ($total > 0) {

    $attendance_percentage =
        round(($present / $total) * 100, 1);

} else {

    $attendance_percentage = 0;
}

// Check event cancellation status
$is_cancelled =
    $event["event_status"] == "cancelled";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Attendance Report - Agora</title>

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

<main class="container report-page">

    <a href="my_events.php" class="event-form-back">
        &larr; Back to My Events
    </a>

    <div class="eyebrow">
        Attendance Analytics
    </div>

    <h1>Attendance Report</h1>

    <p class="report-intro">
        <?php echo htmlspecialchars(
            $event["event_name"]
        ); ?>

        — Overview of student attendance.
    </p>

    <?php if ($is_cancelled) { ?>

        <div class="report-cancelled">
            This event has been cancelled.
            Historical attendance information
            is still available.
        </div>

    <?php } ?>

    <!-- Statistics Cards -->

    <section class="report-stats">

        <div class="report-stat-card">

            <div class="report-stat-number">
                <?php echo $total; ?>
            </div>

            <div class="report-stat-label">
                Total Registered
            </div>

        </div>

        <div class="report-stat-card">

            <div class="report-stat-number">
                <?php echo $present; ?>
            </div>

            <div class="report-stat-label">
                Present
            </div>

        </div>

        <div class="report-stat-card">

            <div class="report-stat-number">
                <?php echo $absent; ?>
            </div>

            <div class="report-stat-label">
                Absent
            </div>

        </div>

        <div class="report-stat-card">

            <div class="report-stat-number">
                <?php echo $not_marked; ?>
            </div>

            <div class="report-stat-label">
                Not Marked
            </div>

        </div>

    </section>

    <!-- Attendance Rate -->

    <section class="report-panel">

        <h2>Attendance Rate</h2>

        <div class="report-percentage">

            <?php echo number_format(
                $attendance_percentage, 1
            ); ?>%

        </div>

        <p class="report-caption">
            <?php echo $present; ?>
            out of
            <?php echo $total; ?>
            registered students have been
            marked Present.
        </p>

        <div class="report-progress"
             role="progressbar"
             aria-label="Attendance rate"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-valuenow="<?php
                 echo $attendance_percentage;
             ?>">

            <div class="report-progress-fill"
                 style="width: <?php
                     echo $attendance_percentage;
                 ?>%;">
            </div>

        </div>

        <p class="report-caption">
            Attendance Rate = (Present / Total Registered) × 100%.
            Students whose attendance has not been
            marked are included in the total.
        </p>

    </section>

    <!-- Event Information -->

    <section class="report-panel">

        <h2>Event Information</h2>

        <div class="report-info-grid">

            <div>
                <span class="report-info-label">
                    Event Name
                </span>

                <span class="report-info-value">
                    <?php echo htmlspecialchars(
                        $event["event_name"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="report-info-label">
                    Event Date
                </span>

                <span class="report-info-value">
                    <?php echo htmlspecialchars(
                        $event["event_date"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="report-info-label">
                    Event Time
                </span>

                <span class="report-info-value">
                    <?php echo htmlspecialchars(
                        $event["event_time"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="report-info-label">
                    Location
                </span>

                <span class="report-info-value">
                    <?php echo htmlspecialchars(
                        $event["location"]
                    ); ?>
                </span>
            </div>

            <div>
                <span class="report-info-label">
                    Event Status
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

            <?php if ($is_cancelled &&
                      !empty($event["cancelled_at"])) { ?>

                <div>
                    <span class="report-info-label">
                        Cancelled At
                    </span>

                    <span class="report-info-value">
                        <?php echo htmlspecialchars(
                            $event["cancelled_at"]
                        ); ?>
                    </span>
                </div>

            <?php } ?>

        </div>

    </section>

    <!-- Management Actions -->

    <section class="report-panel">

        <h2>Attendance Management</h2>

        <p class="report-caption">
            View detailed participant information
            or access the QR check-in function.
        </p>

        <div class="report-actions">

            <a href="participants.php?id=<?php
                echo $id;
            ?>"
               class="btn-secondary">
                View Participants
            </a>

            <?php if (!$is_cancelled) { ?>

                <a href="checkin.php?id=<?php
                    echo $id;
                ?>"
                   class="btn-primary">
                    QR Code Check-in
                </a>

            <?php } ?>

            <a href="my_events.php"
               class="btn-secondary">
                Back to My Events
            </a>

        </div>

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