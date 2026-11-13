<?php
require_once __DIR__ . '/student_only.php';

// =====================================
// Agora - Cancel Registration
// =====================================

function escapeText($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$student_id = (int)$_SESSION['user_id'];

$event_id = (int)(
    $_POST['id'] ??
    $_GET['id'] ??
    0
);

if ($event_id <= 0) {
    header('Location: my_registrations.php');
    exit;
}

// =====================================
// 1. Retrieve Student Registration
// =====================================

$stmt = $pdo->prepare(
    'SELECT
        er.registration_id,
        er.attendance_status,
        e.event_name,
        e.event_date,
        e.event_time,
        e.location,
        e.event_status
     FROM event_registrations er
     JOIN events e
        ON e.event_id = er.event_id
     WHERE er.event_id = ?
       AND er.user_id = ?
     LIMIT 1'
);

$stmt->execute([
    $event_id,
    $student_id
]);

$registration = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$registration) {
    http_response_code(404);
    exit('Registration not found.');
}

// =====================================
// 2. CSRF Protection
// =====================================

if (!isset($_SESSION['cancel_registration_token'])) {
    $_SESSION['cancel_registration_token'] =
        bin2hex(random_bytes(32));
}

$error = '';

$can_cancel =
    $registration['event_status'] === 'active' &&
    $registration['attendance_status'] === 'not_marked';

// =====================================
// 3. Process Cancellation
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION['cancel_registration_token'],
            $token
        )) {

        http_response_code(403);
        exit('Invalid security token.');
    }

    if (!$can_cancel) {

        $error =
            'This registration can no longer be cancelled.';

    } else {

        try {

            $pdo->beginTransaction();

            // Lock event and check its status
            $stmt = $pdo->prepare(
                'SELECT event_status
                 FROM events
                 WHERE event_id = ?
                 FOR UPDATE'
            );

            $stmt->execute([$event_id]);

            $latest_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$latest_event ||
                $latest_event['event_status'] !== 'active') {

                throw new RuntimeException(
                    'Event is not active.'
                );
            }

            // Verify registration ownership again
            $stmt = $pdo->prepare(
                'SELECT registration_id,
                        attendance_status
                 FROM event_registrations
                 WHERE event_id = ?
                   AND user_id = ?
                 FOR UPDATE'
            );

            $stmt->execute([
                $event_id,
                $student_id
            ]);

            $latest_registration = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$latest_registration ||
                $latest_registration['attendance_status']
                    !== 'not_marked') {

                throw new RuntimeException(
                    'Registration cannot be cancelled.'
                );
            }

            // Delete only this student's registration
            $stmt = $pdo->prepare(
                "DELETE FROM event_registrations
                 WHERE registration_id = ?
                   AND event_id = ?
                   AND user_id = ?
                   AND attendance_status = 'not_marked'"
            );

            $stmt->execute([
                $latest_registration['registration_id'],
                $event_id,
                $student_id
            ]);

            if ($stmt->rowCount() !== 1) {

                throw new RuntimeException(
                    'Cancellation failed.'
                );
            }

            $pdo->commit();

            header(
                'Location: my_registrations.php',
                true,
                303
            );
            exit;

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Unable to cancel registration. ' .
                'Please try again.';
        }
    }
}

header('Cache-Control: no-store, private');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cancel Registration - Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .cancel-page {
            max-width: 820px;
            padding-top: 45px;
            padding-bottom: 75px;
        }

        .cancel-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 30px;
        }

        .cancel-card h2 {
            margin-top: 0;
            overflow-wrap: anywhere;
        }

        .cancel-details {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 20px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
        }

        .cancel-details strong {
            display: block;
            font-size: 12px;
            color: #64748b;
        }

        .cancel-details span {
            display: block;
            overflow-wrap: anywhere;
        }

        .cancel-warning {
            background: #fffbeb;
            color: #92400e;
            padding: 17px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .cancel-error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 17px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .cancel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .cancel-danger {
            background: #dc2626;
            color: white;
            border: 0;
            border-radius: 10px;
            padding: 13px 20px;
            cursor: pointer;
            font: inherit;
        }

        .cancel-secondary {
            display: inline-block;
            padding: 13px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        @media (max-width: 600px) {
            .cancel-card {
                padding: 20px;
            }

            .cancel-details {
                grid-template-columns: 1fr;
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

<main class="container cancel-page">

    <a href="my_registrations.php"
       class="event-form-back">
        &larr; My Registrations
    </a>

    <div class="eyebrow">
        REGISTRATION MANAGEMENT
    </div>

    <h1>Cancel Registration</h1>

    <p>
        Review your event details before continuing.
    </p>

    <!-- Error Message -->

    <?php if ($error !== ''): ?>

        <div class="cancel-error" role="alert">
            <?php echo escapeText($error); ?>
        </div>

    <?php endif; ?>

    <section class="cancel-card">

        <h2>
            <?php echo escapeText(
                $registration['event_name']
            ); ?>
        </h2>

        <!-- Event Information -->

        <div class="cancel-details">

            <div>
                <strong>Event Date</strong>

                <span>
                    <?php echo escapeText(
                        $registration['event_date']
                    ); ?>
                </span>
            </div>

            <div>
                <strong>Event Time</strong>

                <span>
                    <?php echo escapeText(
                        substr(
                            $registration['event_time'],
                            0,
                            5
                        )
                    ); ?>
                </span>
            </div>

            <div>
                <strong>Location</strong>

                <span>
                    <?php echo escapeText(
                        $registration['location']
                    ); ?>
                </span>
            </div>

            <div>
                <strong>Attendance</strong>

                <span>
                    <?php echo escapeText(
                        $registration['attendance_status']
                    ); ?>
                </span>
            </div>

        </div>

        <!-- Cancellation Available -->

        <?php if ($can_cancel): ?>

            <div class="cancel-warning">

                <strong>
                    Are you sure?
                </strong>

                <p>
                    Cancelling removes your registration
                    and invalidates its QR code.
                </p>

                <p>
                    It will not cancel the event
                    or affect other students.
                </p>

            </div>

            <div class="cancel-actions">

                <form
                    action="cancel_registration.php?id=<?php
                        echo $event_id;
                    ?>"
                    method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?php
                            echo $event_id;
                        ?>">

                    <input
                        type="hidden"
                        name="token"
                        value="<?php echo escapeText(
                            $_SESSION['cancel_registration_token']
                        ); ?>">

                    <button
                        class="cancel-danger"
                        type="submit">

                        Yes, Cancel Registration

                    </button>

                </form>

                <a
                    href="my_registrations.php"
                    class="cancel-secondary">

                    Keep Registration

                </a>

            </div>

        <?php endif; ?>

        <!-- Cancellation Not Available -->

        <?php if (!$can_cancel): ?>

            <div class="cancel-error">

                <?php if (
                    $registration['event_status'] !== 'active'
                ): ?>

                    This event was cancelled.
                    Your registration record is preserved.

                <?php endif; ?>

                <?php if (
                    $registration['event_status'] === 'active'
                ): ?>

                    Attendance has already been recorded,
                    so this registration cannot be cancelled.

                <?php endif; ?>

            </div>

            <a
                href="my_registrations.php"
                class="cancel-secondary">

                Back to My Registrations

            </a>

        <?php endif; ?>

    </section>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

</body>
</html>
