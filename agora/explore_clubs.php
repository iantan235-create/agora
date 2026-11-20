<?php
session_start();
require_once "pdo.php";

// Retrieve approved organizer clubs
$sql = "SELECT
            user_id,
            club_name,
            club_description
        FROM users
        WHERE role = 'organizer'
        AND account_status = 'approved'
        AND club_name IS NOT NULL
        AND club_name != ''
        ORDER BY club_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Explore Clubs - Agora</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">

    <div class="container header-content">

        <a href="dashboard.php" class="brand">
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

<main class="container clubs-page">

    <div class="eyebrow">
        Campus Communities
    </div>

    <h1>Explore Clubs</h1>

    <p class="clubs-intro">
        Find your community, discover activities,
        and connect with student organizations
        at Taylor's University.
    </p>

    <div class="results-header">

        <h2>Student Clubs</h2>

        <span class="results-count">
            <?php echo count($clubs); ?> Clubs
        </span>

    </div>

    <?php if (count($clubs) == 0) { ?>

        <div class="clubs-empty">

            <h2>No Clubs Available</h2>

            <p>
                There are currently no approved
                student clubs to display.
            </p>

        </div>

    <?php } else { ?>

        <div class="clubs-grid">

            <?php foreach ($clubs as $club) { ?>

                <article class="club-card">

                    <div class="club-avatar">
                        <?php
                        echo htmlspecialchars(
                            strtoupper(
                                substr($club["club_name"], 0, 1)
                            )
                        );
                        ?>
                    </div>

                    <h2>
                        <?php echo htmlspecialchars(
                            $club["club_name"]
                        ); ?>
                    </h2>

                    <p class="club-type">
                        Taylor's University Student Club
                    </p>

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
                            Explore this club and discover
                            its upcoming campus activities.
                        </p>

                    <?php } ?>

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

</main>

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
