<?php
require_once __DIR__ . '/organizer_only.php';

$organizer_id = (int) $_SESSION['user_id'];

function myEventsEsc($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$stmt = $pdo->prepare(
    "SELECT
        e.event_id,
        e.event_name,
        e.event_date,
        e.event_time,
        e.location,
        e.description,
        e.event_status,
        e.poster_path,
        COALESCE(r.registered_count, 0) AS registered_count,
        COALESCE(r.present_count, 0) AS present_count,
        COALESCE(r.absent_count, 0) AS absent_count
     FROM events e
     LEFT JOIN (
         SELECT
             event_id,
             COUNT(*) AS registered_count,
             SUM(
                 CASE WHEN attendance_status = 'present'
                 THEN 1 ELSE 0 END
             ) AS present_count,
             SUM(
                 CASE WHEN attendance_status = 'absent'
                 THEN 1 ELSE 0 END
             ) AS absent_count
         FROM event_registrations
         GROUP BY event_id
     ) r ON e.event_id = r.event_id
     WHERE e.organizer_id = ?
     ORDER BY
         CASE
             WHEN e.event_status = 'active' THEN 0
             ELSE 1
         END,
         e.event_date ASC,
         e.event_time ASC"
);

$stmt->execute([$organizer_id]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_events = count($events);
$active_events = 0;
$upcoming_events = 0;
$total_registrations = 0;
$total_present = 0;

foreach ($events as $item) {
    if ($item['event_status'] === 'active') {
        $active_events++;

        if ($item['event_date'] >= date('Y-m-d')) {
            $upcoming_events++;
        }
    }

    $total_registrations += (int) $item['registered_count'];
    $total_present += (int) $item['present_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Events | Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .events-page {
            max-width: 1150px;
            margin: 0 auto;
            padding: 45px 20px 80px;
        }

        .events-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .events-top h1 {
            font-size: 34px;
            margin: 0 0 8px;
            color: #172033;
        }

        .events-subtitle {
            color: #64748b;
            margin: 0;
        }

        .create-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 10px;
            font-weight: 600;
        }

        .create-btn:hover {
            background: #1d4ed8;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 35px;
        }

        .stat-box {
            background: white;
            padding: 23px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 700;
            color: #172033;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        .section-heading {
            font-size: 23px;
            color: #172033;
            margin: 30px 0 20px;
        }

        .event-list {
            display: grid;
            gap: 22px;
        }

        .organizer-event-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 25px;
        }

        .event-card-content {
            display: grid;
            grid-template-columns: 165px 1fr;
            gap: 25px;
            align-items: start;
        }

        .event-poster-preview {
            width: 165px;
            height: 220px;
            object-fit: contain;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .no-poster {
            width: 165px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #f1f5f9;
            color: #94a3b8;
            border-radius: 12px;
            font-size: 14px;
        }

        .event-title {
            font-size: 23px;
            color: #172033;
            margin: 5px 0 12px;
        }

        .status-label {
            display: inline-block;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            margin-bottom: 8px;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-info {
            color: #475569;
            font-size: 14px;
            line-height: 1.9;
        }

        .event-description {
            color: #64748b;
            line-height: 1.7;
            margin: 14px 0;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .event-numbers {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 18px 0;
        }

        .number-item {
            background: #f8fafc;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: 14px;
            color: #334155;
        }

        .number-item strong {
            color: #172033;
        }

        .event-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .event-action {
            display: inline-block;
            text-decoration: none;
            border-radius: 9px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .event-action:hover {
            background: #e2e8f0;
        }

        .poster-action {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .poster-action:hover {
            background: #1d4ed8;
        }

        .cancel-action {
            background: white;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .empty-events {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 50px 25px;
            text-align: center;
        }

        .empty-events h2 {
            color: #172033;
        }

        .empty-events p {
            color: #64748b;
            margin-bottom: 25px;
        }

        @media (max-width: 850px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .events-page {
                padding: 28px 15px 60px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-box {
                padding: 17px;
            }

            .event-card-content {
                grid-template-columns: 1fr;
            }

            .event-poster-preview,
            .no-poster {
                width: 100%;
                max-width: 280px;
                height: 280px;
            }

            .organizer-event-card {
                padding: 20px;
            }
        }
    </style>
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

<main class="events-page">

    <div class="events-top">

        <div>
            <h1>My Events</h1>

            <p class="events-subtitle">
                Manage your club events, registrations
                and attendance.
            </p>
        </div>

        <a href="add.php" class="create-btn">
            + Create Event
        </a>

    </div>

    <div class="stats-grid">

        <div class="stat-box">
            <div class="stat-number">
                <?= $total_events ?>
            </div>

            <div class="stat-label">
                Total Events
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-number">
                <?= $upcoming_events ?>
            </div>

            <div class="stat-label">
                Upcoming Events
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-number">
                <?= $total_registrations ?>
            </div>

            <div class="stat-label">
                Total Registrations
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-number">
                <?= $total_present ?>
            </div>

            <div class="stat-label">
                Students Attended
            </div>
        </div>

    </div>

    <h2 class="section-heading">
        Your Events
    </h2>

    <?php if (empty($events)): ?>

        <div class="empty-events">

            <h2>No events created yet</h2>

            <p>
                Create your first event to start
                receiving student registrations.
            </p>

            <a href="add.php" class="create-btn">
                Create Your First Event
            </a>

        </div>

    <?php else: ?>

        <div class="event-list">

            <?php foreach ($events as $event): ?>

                <?php
                $event_id = (int) $event['event_id'];

                $is_cancelled =
                    $event['event_status'] === 'cancelled';

                $registered =
                    (int) $event['registered_count'];

                $present =
                    (int) $event['present_count'];

                $absent =
                    (int) $event['absent_count'];
                ?>

                <div class="organizer-event-card">

                    <div class="event-card-content">

                        <div>

                            <?php if (!empty($event['poster_path'])): ?>

                                <img
                                    class="event-poster-preview"
                                    src="<?= myEventsEsc(
                                        $event['poster_path']
                                    ) ?>"
                                    alt="Event Poster"
                                >

                            <?php else: ?>

                                <div class="no-poster">
                                    No Poster Uploaded
                                </div>

                            <?php endif; ?>

                        </div>

                        <div>

                            <?php if ($is_cancelled): ?>

                                <span class="status-label status-cancelled">
                                    Cancelled
                                </span>

                            <?php else: ?>

                                <span class="status-label status-active">
                                    Active
                                </span>

                            <?php endif; ?>

                            <h2 class="event-title">
                                <?= myEventsEsc(
                                    $event['event_name']
                                ) ?>
                            </h2>

                            <div class="event-info">

                                <div>
                                    <strong>Date:</strong>
                                    <?= myEventsEsc(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $event['event_date']
                                            )
                                        )
                                    ) ?>
                                </div>

                                <div>
                                    <strong>Time:</strong>
                                    <?= myEventsEsc(
                                        date(
                                            'g:i A',
                                            strtotime(
                                                $event['event_time']
                                            )
                                        )
                                    ) ?>
                                </div>

                                <div>
                                    <strong>Location:</strong>
                                    <?= myEventsEsc(
                                        $event['location']
                                    ) ?>
                                </div>

                            </div>

                            <p class="event-description">
                                <?= myEventsEsc(
                                    $event['description'] ?? ''
                                ) ?>
                            </p>

                            <div class="event-numbers">

                                <div class="number-item">
                                    <strong><?= $registered ?></strong>
                                    Registered
                                </div>

                                <div class="number-item">
                                    <strong><?= $present ?></strong>
                                    Present
                                </div>

                                <div class="number-item">
                                    <strong><?= $absent ?></strong>
                                    Absent
                                </div>

                            </div>

                            <div class="event-actions">

                                <a
                                    class="event-action"
                                    href="event_details.php?id=<?= $event_id ?>"
                                >
                                    View Event
                                </a>

                                <a
                                    class="event-action poster-action"
                                    href="event_poster.php?id=<?= $event_id ?>"
                                >
                                    Manage Poster
                                </a>

                                <?php if (!$is_cancelled): ?>

                                    <a
                                        class="event-action"
                                        href="edit.php?id=<?= $event_id ?>"
                                    >
                                        Edit Event
                                    </a>

                                <?php endif; ?>

                                <a
                                    class="event-action"
                                    href="participants.php?id=<?= $event_id ?>"
                                >
                                    Participants
                                </a>

                                <?php if (!$is_cancelled): ?>

                                    <a
                                        class="event-action"
                                        href="checkin.php?id=<?= $event_id ?>"
                                    >
                                        QR Check-in
                                    </a>

                                <?php endif; ?>

                                <a
                                    class="event-action"
                                    href="attendance_report.php?id=<?= $event_id ?>"
                                >
                                    Attendance Report
                                </a>

                                <?php if (!$is_cancelled): ?>

                                    <a
                                        class="event-action cancel-action"
                                        href="delete.php?id=<?= $event_id ?>"
                                    >
                                        Cancel Event
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

</body>
</html>
