<?php
session_start();
require_once "pdo.php";

// Retrieve search and date filters
$search = $_GET["search"] ?? "";
$date = $_GET["date"] ?? "";

$search = is_string($search) ? trim($search) : "";
$date = is_string($date) ? $date : "";

// Validate date
if ($date != "" &&
    (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) ||
     !checkdate(
         (int)substr($date, 5, 2),
         (int)substr($date, 8, 2),
         (int)substr($date, 0, 4)
     ))) {
    $date = "";
}

// Search for matching events
$sql = "SELECT * FROM events
        WHERE event_name LIKE ?";

$params = ["%" . $search . "%"];

if ($date != "") {
    $sql .= " AND event_date = ?";
    $params[] = $date;
}

$sql .= " ORDER BY event_date ASC, event_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Browse Events - Agora</title>

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

<main class="container">

    <section class="page-hero">

        <div class="eyebrow">
            Explore Campus
        </div>

        <h1>Discover Events</h1>

        <p>
            Find experiences, workshops and activities
            at Taylor's University.
        </p>

    </section>

    <!-- Search and Filter Form -->

    <section class="filter-panel">

        <form action="index.php" method="GET">

            <div class="filter-grid">

                <div class="form-group">

                    <label for="search">
                        Search Events
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="search"
                        name="search"
                        maxlength="100"
                        placeholder="Search events..."
                        value="<?php
                            echo htmlspecialchars(
                                $search, ENT_QUOTES
                            );
                        ?>">

                </div>

                <div class="form-group">

                    <label for="date">
                        Event Date
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        id="date"
                        name="date"
                        value="<?php
                            echo htmlspecialchars(
                                $date, ENT_QUOTES
                            );
                        ?>">

                </div>

            </div>

            <div class="filter-actions">

                <button class="btn-primary" type="submit">
                    Search Events
                </button>

                <a href="index.php" class="btn-secondary">
                    Clear Filters
                </a>

            </div>

        </form>

    </section>

    <!-- Search Results -->

    <section>

        <div class="results-header">

            <h2>Available Events</h2>

            <span class="results-count">
                <?php echo count($events); ?> Results
            </span>

        </div>

        <?php if (count($events) == 0) { ?>

            <div class="empty-state">

                <h3>No events found</h3>

                <p>
                    Try another event name or date.
                </p>

                <a href="index.php" class="btn-secondary">
                    View All Events
                </a>

            </div>

        <?php } else { ?>

            <div class="event-list">

                <?php foreach ($events as $event) { ?>

                    <article class="event-card">

                        <div class="event-card-top">

                            <span class="event-date">
                                <?php echo htmlspecialchars(
                                    $event["event_date"]
                                ); ?>
                            </span>

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

                        </div>

                        <h3>
                            <?php echo htmlspecialchars(
                                $event["event_name"]
                            ); ?>
                        </h3>

                        <p class="event-meta">
                            Time:
                            <?php echo htmlspecialchars(
                                $event["event_time"]
                            ); ?>
                        </p>

                        <p class="event-meta">
                            Location:
                            <?php echo htmlspecialchars(
                                $event["location"]
                            ); ?>
                        </p>

                        <a class="event-link"
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
