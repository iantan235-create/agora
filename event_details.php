<?php
session_start();
require_once "pdo.php";

// Get the event ID from the URL
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Retrieve event and organizer information
$sql = "SELECT events.*,
               users.club_name,
               users.user_id AS club_id
        FROM events
        LEFT JOIN users
        ON events.organizer_id = users.user_id
        AND users.role = 'organizer'
        AND users.account_status = 'approved'
        WHERE events.event_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    die("Event not found.");
}

// Identify logged-in students
$is_student = isset($_SESSION["user_id"]) &&
              ($_SESSION["role"] ?? "") == "student";

// Check registration status
$is_registered = false;

if ($is_student) {

    $sql = "SELECT registration_id
            FROM event_registrations
            WHERE event_id = ?
            AND user_id = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $id,
        $_SESSION["user_id"]
    ]);

    $is_registered = (bool)$stmt->fetch(
        PDO::FETCH_ASSOC
    );
}

// Check cancellation status
$is_cancelled =
    $event["event_status"] == "cancelled";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($event["event_name"]); ?>
        - Agora
    </title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Header -->

<header class="site-header">

    <div class="container header-content">

        <a href="index.php" class="brand">
            agora<span>.</span>
        </a>

        <?php if (isset($_SESSION["user_id"])) { ?>

            <a href="dashboard.php" class="logout">
                Dashboard
            </a>

        <?php } else { ?>

            <a href="login.php" class="logout">
                Login
            </a>

        <?php } ?>

    </div>

</header>

<main class="container">

    <!-- Event Heading -->

    <section class="details-hero">

        <a href="index.php" class="details-back">
            &larr; Back to Events
        </a>

        <div class="details-heading">

            <div>

                <div class="eyebrow">
                    Event Details
                </div>

                <h1>
                    <?php echo htmlspecialchars(
                        $event["event_name"]
                    ); ?>
                </h1>

                <p>
                    Discover more about this campus event.
                </p>

            </div>

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

    </section>

    <!-- Event Content -->

    <div class="details-layout">

        <div class="details-main">

            <section class="details-panel">

                <h2>Event Information</h2>

                <div class="details-info-grid">

                    <div class="details-info-item">

                        <span class="details-label">
                            Event Date
                        </span>

                        <span class="details-value">
                            <?php echo htmlspecialchars(
                                $event["event_date"]
                            ); ?>
                        </span>

                    </div>

                    <div class="details-info-item">

                        <span class="details-label">
                            Event Time
                        </span>

                        <span class="details-value">
                            <?php echo htmlspecialchars(
                                $event["event_time"]
                            ); ?>
                        </span>

                    </div>

                    <div class="details-info-item">

                        <span class="details-label">
                            Location
                        </span>

                        <span class="details-value">
                            <?php echo htmlspecialchars(
                                $event["location"]
                            ); ?>
                        </span>

                    </div>

                    <div class="details-info-item">

                        <span class="details-label">
                            Organizer
                        </span>

                        <span class="details-value">

                            <?php
                            if (!empty($event["club_name"])) {

                                echo htmlspecialchars(
                                    $event["club_name"]
                                );

                            } else {

                                echo "Not available";
                            }
                            ?>

                        </span>

                    </div>

                </div>

                <hr class="details-divider">

                <h2>About This Event</h2>

                <div class="details-description"><?php
                    echo htmlspecialchars(
                        $event["description"] ?? ""
                    );
                ?></div>

            </section>

            <!-- Organizer Information -->

            <section class="details-panel">

                <h2>Organized By</h2>

                <?php if (!empty($event["club_name"])) { ?>

                    <h3>
                        <?php echo htmlspecialchars(
                            $event["club_name"]
                        ); ?>
                    </h3>

                    <p>
                        Taylor's University Student Club
                    </p>

                    <a class="details-club-link"
                       href="club_profile.php?id=<?php
                           echo (int)$event["club_id"];
                       ?>">
                        View Club Profile &rarr;
                    </a>

                <?php } else { ?>

                    <p>
                        Organizer information is not available.
                    </p>

                <?php } ?>

            </section>

        </div>

        <!-- Registration Panel -->

        <aside class="registration-panel">

            <h2>Event Registration</h2>

            <?php if ($is_cancelled) { ?>

                <div class="registration-cancelled">
                    This event has been cancelled.
                </div>

                <p>
                    Registration is closed because the
                    organizer cancelled this event.
                </p>

                <?php if ($is_student && $is_registered) { ?>

                    <a href="my_registrations.php"
                       class="btn-secondary registration-action">
                        View My Registrations
                    </a>

                <?php } ?>

            <?php } elseif ($is_student) { ?>

                <?php if ($is_registered) { ?>

                    <div class="registration-success">
                        You have already registered
                        for this event.
                    </div>

                    <a href="my_registrations.php"
                       class="btn-secondary registration-action">
                        View My Registrations
                    </a>

                <?php } else { ?>

                    <p>
                        Interested in this event?
                        Register to secure your participation.
                    </p>

                    <a href="register_event.php?id=<?php
                        echo $id;
                    ?>"
                       class="btn-primary registration-action">
                        Register for Event
                    </a>

                <?php } ?>

            <?php } elseif (!isset($_SESSION["user_id"])) { ?>

                <p>
                    Please log in with a student account
                    to register for this event.
                </p>

                <a href="login.php"
                   class="btn-primary registration-action">
                    Login to Register
                </a>

            <?php } else { ?>

                <p>
                    Only student accounts can
                    register for events.
                </p>

            <?php } ?>

        </aside>

    </div>

</main>

<!-- Footer -->

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
