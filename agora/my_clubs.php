<?php
session_start();
require_once "pdo.php";

// Only logged-in students can access this page
if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") != "student") {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Retrieve clubs followed by the student
$sql = "SELECT
            u.user_id,
            u.club_name,
            u.club_description,
            cf.followed_at
        FROM club_follows cf
        INNER JOIN users u
        ON cf.organizer_user_id = u.user_id
        WHERE cf.student_user_id = ?
        AND u.role = 'organizer'
        AND u.account_status = 'approved'
        ORDER BY u.club_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Followed Clubs - Agora</title>

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

<main class="container followed-clubs-page">

    <div class="eyebrow">
        My Communities
    </div>

    <h1>My Followed Clubs</h1>

    <p class="followed-clubs-intro">
        Stay connected with student clubs
        and discover their upcoming activities.
    </p>

    <div class="results-header">

        <h2>Following</h2>

        <span class="results-count">
            <?php echo count($clubs); ?> Clubs
        </span>

    </div>

    <?php if (count($clubs) == 0) { ?>

        <div class="clubs-empty">

            <h2>No Followed Clubs Yet</h2>

            <p>
                You are not following any clubs.
                Explore campus communities to get started.
            </p>

            <a href="explore_clubs.php"
               class="btn-primary">
                Explore Clubs
            </a>

        </div>

    <?php } else { ?>

        <div class="clubs-grid">

            <?php foreach ($clubs as $club) { ?>

                <article class="club-card">

                    <div class="club-avatar">
                        <?php echo htmlspecialchars(
                            strtoupper(
                                substr($club["club_name"], 0, 1)
                            )
                        ); ?>
                    </div>

                    <h2>
                        <?php echo htmlspecialchars(
                            $club["club_name"]
                        ); ?>
                    </h2>

                    <span class="status-badge status-active">
                        Following
                    </span>

                    <?php if (!empty(
                        $club["club_description"]
                    )) { ?>

                        <p class="club-description">
                            <?php echo htmlspecialchars(
                                $club["club_description"]
                            ); ?>
                        </p>

                    <?php } else { ?>

                        <p class="club-description">
                            Discover this club's events
                            and activities.
                        </p>

                    <?php } ?>

                    <p class="followed-date">
                        Followed At:
                        <?php echo htmlspecialchars(
                            $club["followed_at"]
                        ); ?>
                    </p>

                    <a class="club-card-link"
                       href="club_profile.php?id=<?php
                           echo (int)$club["user_id"];
                       ?>">
                        View Club Profile &rarr;
                    </a>

                </article>

            <?php } ?>

        </div>

    <?php } ?>

    <div class="student-qr-actions">

        <a href="explore_clubs.php"
           class="btn-secondary">
            Explore More Clubs
        </a>

        <a href="dashboard.php"
           class="btn-secondary">
            Back to Dashboard
        </a>

    </div>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

</body>
</html>
