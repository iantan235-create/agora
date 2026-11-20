<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/pdo.php';

function eventDetailsEsc($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$event_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$event_id) {
    $event_id = filter_input(
        INPUT_GET,
        'event_id',
        FILTER_VALIDATE_INT
    );
}

if (!$event_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        e.*,
        u.club_name,
        u.full_name AS organizer_name
     FROM events e
     INNER JOIN users u
        ON e.organizer_id = u.user_id
     WHERE e.event_id = ?
     LIMIT 1"
);

$stmt->execute([$event_id]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';

$is_logged_in = $user_id > 0;

$is_student = (
    $is_logged_in &&
    $user_role === 'student'
);

$is_organizer = (
    $is_logged_in &&
    $user_role === 'organizer'
);

$is_event_owner = (
    $is_organizer &&
    $user_id === (int) $event['organizer_id']
);

$is_cancelled = (
    $event['event_status'] === 'cancelled'
);

$event_timestamp = strtotime(
    $event['event_date'] . ' ' .
    $event['event_time']
);

$is_past = (
    $event_timestamp !== false &&
    $event_timestamp < time()
);

$registration = null;

if ($is_student) {

    $stmt = $pdo->prepare(
        "SELECT
            registration_id,
            attendance_status,
            registered_at
         FROM event_registrations
         WHERE event_id = ?
           AND user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $event_id,
        $user_id
    ]);

    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM event_registrations
     WHERE event_id = ?"
);

$stmt->execute([$event_id]);

$registered_count = (int) $stmt->fetchColumn();

$organizer_name = !empty($event['club_name'])
    ? $event['club_name']
    : $event['organizer_name'];

$formatted_date = date(
    'd F Y',
    strtotime($event['event_date'])
);

$formatted_time = date(
    'g:i A',
    strtotime($event['event_time'])
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= eventDetailsEsc($event['event_name']) ?> | Agora
    </title>

    <link rel="stylesheet" href="style.css">

    <style>
        .details-page {
            max-width: 1080px;
            margin: 0 auto;
            padding: 45px 20px 80px;
        }

        .details-back {
            display: inline-block;
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .details-header {
            margin-bottom: 30px;
        }

        .details-title {
            font-size: 38px;
            line-height: 1.2;
            color: #172033;
            margin: 12px 0 15px;
        }

        .details-organizer {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .details-organizer a {
            color: #2563eb;
            text-decoration: none;
        }

        .details-status {
            display: inline-block;
            font-size: 13px;
            font-weight: 600;
            border-radius: 20px;
            padding: 7px 13px;
        }

        .details-active {
            background: #dcfce7;
            color: #166534;
        }

        .details-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .details-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 1fr);
            gap: 25px;
            align-items: start;
        }

        .details-main,
        .details-sidebar {
            min-width: 0;
        }

        .details-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 27px;
            margin-bottom: 22px;
        }

        .details-card h2 {
            font-size: 21px;
            color: #172033;
            margin: 0 0 18px;
        }

        .poster-image {
            display: block;
            width: 100%;
            max-height: 650px;
            object-fit: contain;
            border-radius: 12px;
            background: #f8fafc;
        }

        .poster-empty {
            padding: 50px 20px;
            background: #f8fafc;
            border-radius: 12px;
            text-align: center;
            color: #94a3b8;
        }

        .details-description {
            font-size: 15px;
            color: #475569;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }

        .info-row {
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .info-value {
            display: block;
            font-size: 15px;
            color: #172033;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .register-btn {
            display: block;
            width: 100%;
            background: #2563eb;
            color: white;
            text-align: center;
            padding: 14px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            box-sizing: border-box;
            margin-top: 20px;
        }

        .register-btn:hover {
            background: #1d4ed8;
        }

        .secondary-btn {
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            padding: 13px 18px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #334155;
            font-weight: 600;
            box-sizing: border-box;
            margin-top: 12px;
        }

        .details-message {
            background: #eff6ff;
            color: #1e40af;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 18px;
        }

        .details-warning {
            background: #fff1f2;
            color: #9f1239;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 18px;
        }

        .details-muted {
            font-size: 14px;
            color: #64748b;
            line-height: 1.7;
        }

        .attendance-status {
            font-weight: 600;
            color: #334155;
        }

        @media (max-width: 800px) {
            .details-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .details-page {
                padding: 30px 15px 60px;
            }

            .details-title {
                font-size: 29px;
            }

            .details-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

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

<main class="details-page">

    <a href="index.php" class="details-back">
        &larr; Back to Browse Events
    </a>

    <div class="details-header">

        <?php if ($is_cancelled): ?>

            <span class="details-status details-cancelled">
                Event Cancelled
            </span>

        <?php else: ?>

            <span class="details-status details-active">
                Active Event
            </span>

        <?php endif; ?>

        <h1 class="details-title">
            <?= eventDetailsEsc(
                $event['event_name']
            ) ?>
        </h1>

        <p class="details-organizer">
            Organized by

            <a href="club_profile.php?id=<?= (int) $event['organizer_id'] ?>&organizer_id=<?= (int) $event['organizer_id'] ?>">
                <?= eventDetailsEsc($organizer_name) ?>
            </a>
        </p>

    </div>

    <div class="details-layout">

        <div class="details-main">

            <section class="details-card">

                <h2>Event Poster</h2>

                <?php if (!empty($event['poster_path'])): ?>

                    <img
                        class="poster-image"
                        src="<?= eventDetailsEsc(
                            $event['poster_path']
                        ) ?>"
                        alt="Poster for <?= eventDetailsEsc(
                            $event['event_name']
                        ) ?>"
                    >

                <?php else: ?>

                    <div class="poster-empty">
                        No event poster available.
                    </div>

                <?php endif; ?>

            </section>

            <section class="details-card">

                <h2>About This Event</h2>

                <div class="details-description">
                    <?= nl2br(
                        eventDetailsEsc(
                            $event['description'] ?? ''
                        )
                    ) ?>
                </div>

            </section>

            <?php if ($is_event_owner): ?>

                <section class="details-card">

                    <h2>Organizer Actions</h2>

                    <a
                        class="register-btn"
                        href="event_poster.php?id=<?= $event_id ?>"
                    >
                        Manage Event Poster
                    </a>

                    <?php if (!$is_cancelled): ?>

                        <a
                            class="secondary-btn"
                            href="edit.php?id=<?= $event_id ?>"
                        >
                            Edit Event
                        </a>

                    <?php endif; ?>

                    <a
                        class="secondary-btn"
                        href="participants.php?id=<?= $event_id ?>"
                    >
                        View Participants
                    </a>

                    <a
                        class="secondary-btn"
                        href="attendance_report.php?id=<?= $event_id ?>"
                    >
                        Attendance Report
                    </a>

                </section>

            <?php endif; ?>

        </div>

        <aside class="details-sidebar">

            <section class="details-card">

                <h2>Event Information</h2>

                <div class="info-row">

                    <span class="info-label">
                        Event Date
                    </span>

                    <span class="info-value">
                        <?= eventDetailsEsc(
                            $formatted_date
                        ) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Event Time
                    </span>

                    <span class="info-value">
                        <?= eventDetailsEsc(
                            $formatted_time
                        ) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Location
                    </span>

                    <span class="info-value">
                        <?= eventDetailsEsc(
                            $event['location']
                        ) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Organizer
                    </span>

                    <span class="info-value">
                        <?= eventDetailsEsc(
                            $organizer_name
                        ) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Registered Students
                    </span>

                    <span class="info-value">
                        <?= $registered_count ?>
                    </span>

                </div>

                <?php if ($is_cancelled): ?>

                    <div class="details-warning">
                        This event has been cancelled.
                        New registrations are unavailable.
                    </div>

                    <?php if ($registration): ?>

                        <a
                            class="secondary-btn"
                            href="my_registrations.php"
                        >
                            My Registrations
                        </a>

                    <?php endif; ?>

                <?php elseif ($registration): ?>

                    <div class="details-message">
                        You are registered for this event.
                    </div>

                    <div class="info-row">

                        <span class="info-label">
                            Attendance Status
                        </span>

                        <span class="info-value attendance-status">
                            <?= eventDetailsEsc(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $registration['attendance_status']
                                    )
                                )
                            ) ?>
                        </span>

                    </div>

                    <a
                        class="register-btn"
                        href="my_registrations.php"
                    >
                        View My Registration
                    </a>

                <?php elseif ($is_past): ?>

                    <div class="details-message">
                        This event has already ended.
                        Registration is closed.
                    </div>

                <?php elseif ($is_student): ?>

                    <a
                        class="register-btn"
                        href="register_event.php?id=<?= $event_id ?>&event_id=<?= $event_id ?>"
                    >
                        Register for Event
                    </a>

                <?php elseif (!$is_logged_in): ?>

                    <p class="details-muted">
                        Log in with your student account
                        to register for this event.
                    </p>

                    <a
                        class="register-btn"
                        href="login.php"
                    >
                        Login to Register
                    </a>

                <?php else: ?>

                    <div class="details-message">
                        Event registration is available
                        for student accounts only.
                    </div>

                <?php endif; ?>

            </section>

            <section class="details-card">

                <h2>About the Club</h2>

                <p class="details-muted">
                    <?= eventDetailsEsc(
                        $organizer_name
                    ) ?>
                </p>

                <a
                    class="secondary-btn"
                    href="club_profile.php?id=<?= (int) $event['organizer_id'] ?>&organizer_id=<?= (int) $event['organizer_id'] ?>"
                >
                    View Club Profile
                </a>

            </section>

        </aside>

    </div>

</main>

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
