<?php
session_start();
require_once "pdo.php";

// Get the selected club ID
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: explore_clubs.php");
    exit;
}

// Retrieve approved club information
$sql = "SELECT user_id, club_name,
               club_description,
               club_contact_email
        FROM users
        WHERE user_id = ?
        AND role = 'organizer'
        AND account_status = 'approved'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    http_response_code(404);
    die("Club not found.");
}

// Check whether visitor is a student
$is_student = isset($_SESSION["user_id"]) &&
              ($_SESSION["role"] ?? "") == "student";

// Generate security token
if (!isset($_SESSION["follow_token"])) {
    $_SESSION["follow_token"] =
        bin2hex(random_bytes(32));
}

// Process Follow / Unfollow actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!$is_student) {
        http_response_code(403);
        die("Only students can follow clubs.");
    }

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["follow_token"], $token)) {

        http_response_code(403);
        die("Invalid request.");
    }

    $student_id = $_SESSION["user_id"];

    // Verify student is still approved
    $sql = "SELECT user_id FROM users
            WHERE user_id = ?
            AND role = 'student'
            AND account_status = 'approved'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(403);
        die("Student account is not approved.");
    }

    $action = $_POST["action"] ?? "";

    if ($action == "follow") {

        $sql = "INSERT IGNORE INTO club_follows
                (student_user_id, organizer_user_id)
                VALUES (?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $id]);

    } elseif ($action == "unfollow") {

        $sql = "DELETE FROM club_follows
                WHERE student_user_id = ?
                AND organizer_user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $id]);

    } else {
        http_response_code(400);
        die("Invalid action.");
    }

    // Redirect to prevent duplicate submissions
    header("Location: club_profile.php?id=" . $id, true, 303);
    exit;
}

// Check existing follow status
$is_following = false;

if ($is_student) {

    $sql = "SELECT follow_id
            FROM club_follows
            WHERE student_user_id = ?
            AND organizer_user_id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $_SESSION["user_id"],
        $id
    ]);

    $is_following = (bool)$stmt->fetch(
        PDO::FETCH_ASSOC
    );
}

// Retrieve events organized by this club
$sql = "SELECT event_id, event_name,
               event_date, event_time,
               location, event_status
        FROM events
        WHERE organizer_id = ?
        ORDER BY event_date ASC,
                 event_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Public contact email
$contact_email = $club["club_contact_email"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($club["club_name"]); ?>
        - Agora
    </title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Header -->

<header class="site-header">

    <div class="container header-content">

        <a href="explore_clubs.php" class="brand">
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

<main class="container club-profile-page">

    <a href="explore_clubs.php"
       class="club-profile-back">
        &larr; Explore Clubs
    </a>

    <div class="club-profile-layout">

        <!-- Main Club Information -->

        <div class="club-profile-main">

            <section class="club-profile-panel">

                <div class="club-profile-header">

                    <div class="club-profile-avatar">
                        <?php echo htmlspecialchars(
                            strtoupper(substr(
                                $club["club_name"], 0, 1
                            ))
                        ); ?>
                    </div>

                    <div>

                        <h1>
                            <?php echo htmlspecialchars(
                                $club["club_name"]
                            ); ?>
                        </h1>

                        <p class="club-profile-subtitle">
                            Taylor's University Student Club
                        </p>

                    </div>

                </div>

                <!-- Description -->

                <div class="club-profile-section">

                    <h2>About This Club</h2>

                    <?php if (!empty(
                        $club["club_description"]
                    )) { ?>

                        <p class="club-profile-description">
                            <?php echo nl2br(htmlspecialchars(
                                $club["club_description"]
                            )); ?>
                        </p>

                    <?php } else { ?>

                        <p class="club-profile-description">
                            This club has not added
                            a description yet.
                        </p>

                    <?php } ?>

                </div>

                <!-- Public Contact Information -->

                <div class="club-profile-section">

                    <h2>Contact Information</h2>

                    <?php if (
                        $contact_email != "" &&
                        filter_var(
                            $contact_email,
                            FILTER_VALIDATE_EMAIL
                        )
                    ) { ?>

                        <a class="club-profile-email"
                           href="mailto:<?php
                               echo htmlspecialchars(
                                   $contact_email,
                                   ENT_QUOTES
                               );
                           ?>">
                            <?php echo htmlspecialchars(
                                $contact_email
                            ); ?>
                        </a>

                    <?php } else { ?>

                        <p class="club-profile-description">
                            No public contact email available.
                        </p>

                    <?php } ?>

                </div>

            </section>

        </div>

        <!-- Follow Club Panel -->

        <aside class="club-follow-panel">

            <h2>Stay Connected</h2>

            <?php if ($is_student) { ?>

                <?php if ($is_following) { ?>

                    <div class="registration-success">
                        You are following this club.
                    </div>

                    <p>
                        You'll receive in-website
                        notifications when this
                        club creates new events.
                    </p>

                <?php } else { ?>

                    <p>
                        Follow this club to receive
                        notifications about its
                        newly published events.
                    </p>

                <?php } ?>

                <form class="club-follow-form"
                      action="club_profile.php?id=<?php
                          echo $id;
                      ?>"
                      method="POST">

                    <input type="hidden"
                           name="token"
                           value="<?php
                               echo htmlspecialchars(
                                   $_SESSION["follow_token"],
                                   ENT_QUOTES
                               );
                           ?>">

                    <?php if ($is_following) { ?>

                        <input type="hidden"
                               name="action"
                               value="unfollow">

                        <button type="submit"
                                class="btn-secondary">
                            Unfollow Club
                        </button>

                    <?php } else { ?>

                        <input type="hidden"
                               name="action"
                               value="follow">

                        <button type="submit"
                                class="btn-primary">
                            Follow Club
                        </button>

                    <?php } ?>

                </form>

                <p>
                    <a class="club-profile-email"
                       href="my_clubs.php">
                        My Followed Clubs
                    </a>
                </p>

            <?php } elseif (
                !isset($_SESSION["user_id"])
            ) { ?>

                <p>
                    Log in as a student to follow
                    this club.
                </p>

                <a href="login.php"
                   class="btn-primary">
                    Login to Follow
                </a>

            <?php } else { ?>

                <p>
                    Club following is available
                    to student accounts only.
                </p>

            <?php } ?>

        </aside>

    </div>

    <!-- Club Events -->

    <section class="club-events-section">

        <div class="results-header">

            <h2>Club Events</h2>

            <span class="results-count">
                <?php echo count($events); ?> Events
            </span>

        </div>

        <?php if (count($events) == 0) { ?>

            <div class="clubs-empty">
                <h3>No Events Available</h3>

                <p>
                    This club has not published
                    any events yet.
                </p>
            </div>

        <?php } else { ?>

            <div class="club-events-grid">

                <?php foreach ($events as $event) { ?>

                    <article class="club-event-card">

                        <?php if (
                            $event["event_status"] == "cancelled"
                        ) { ?>

                            <span class="status-badge status-cancelled">
                                Cancelled
                            </span>

                        <?php } else { ?>

                            <span class="status-badge status-active">
                                Active
                            </span>

                        <?php } ?>

                        <h3>
                            <?php echo htmlspecialchars(
                                $event["event_name"]
                            ); ?>
                        </h3>

                        <p>
                            Date:
                            <?php echo htmlspecialchars(
                                $event["event_date"]
                            ); ?>
                        </p>

                        <p>
                            Time:
                            <?php echo htmlspecialchars(
                                $event["event_time"]
                            ); ?>
                        </p>

                        <p>
                            Location:
                            <?php echo htmlspecialchars(
                                $event["location"]
                            ); ?>
                        </p>

                        <a class="club-event-link"
                           href="event_details.php?id=<?php
                               echo (int)$event["event_id"];
                           ?>">
                            View Event Details &rarr;
                        </a>

                    </article>

                <?php } ?>

            </div>

        <?php } ?>

    </section>

</main>

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
