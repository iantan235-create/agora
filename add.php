<?php
require_once "organizer_only.php";

$organizer_id = $_SESSION["user_id"];

// Create security token
if (!isset($_SESSION["add_event_token"])) {
    $_SESSION["add_event_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

$event_name = "";
$event_date = "";
$event_time = "";
$location = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["add_event_token"], $token)) {
        http_response_code(403);
        die("Invalid request.");
    }

    $event_name = trim($_POST["event_name"] ?? "");
    $event_date = trim($_POST["event_date"] ?? "");
    $event_time = trim($_POST["event_time"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Validate date and time
    $valid_date = false;

    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $event_date)) {
        $valid_date = checkdate(
            (int)substr($event_date, 5, 2),
            (int)substr($event_date, 8, 2),
            (int)substr($event_date, 0, 4)
        );
    }

    $valid_time = preg_match(
        "/^(?:[01]\d|2[0-3]):[0-5]\d$/",
        $event_time
    );

    if ($event_name == "" || $event_date == "" ||
        $event_time == "" || $location == "") {

        $error = "Please fill in all required fields.";

    } elseif (strlen($event_name) > 100 ||
              strlen($location) > 100) {

        $error = "Event name or location is too long.";

    } elseif (!$valid_date || !$valid_time) {

        $error = "Please enter a valid date and time.";

    } else {

        try {
            $pdo->beginTransaction();

            // Create event
            $sql = "INSERT INTO events
                    (event_name, event_date, event_time,
                     location, description, organizer_id)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $event_name,
                $event_date,
                $event_time,
                $location,
                $description,
                $organizer_id
            ]);

            $new_event_id = $pdo->lastInsertId();

            // Notify the club's followers
            $message = "New event: " . $event_name .
                       ". A club you follow has " .
                       "published a new event.";

            $sql = "INSERT INTO notifications
                    (user_id, event_id,
                     notification_type, message)
                    SELECT cf.student_user_id,
                           ?, 'new_event', ?
                    FROM club_follows cf
                    INNER JOIN users u
                    ON cf.student_user_id = u.user_id
                    WHERE cf.organizer_user_id = ?
                    AND u.role = 'student'
                    AND u.account_status = 'approved'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $new_event_id,
                $message,
                $organizer_id
            ]);

            $pdo->commit();

            header("Location: my_events.php", true, 303);
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Could not create the event. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Event - Agora</title>

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

<main class="container event-form-page">

    <a href="my_events.php" class="event-form-back">
        &larr; Back to My Events
    </a>

    <div class="eyebrow">Event Management</div>

    <h1>Create New Event</h1>

    <p class="event-form-intro">
        Share an activity with the Taylor's
        student community.
    </p>

    <div class="event-form-note">
        Students following your club will receive
        an in-website notification when you
        publish this event.
    </div>

    <?php if ($error != "") { ?>
        <div class="event-form-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <section class="event-form-panel">

        <h2>Event Information</h2>

        <form action="add.php" method="POST">

            <input type="hidden"
                   name="token"
                   value="<?php echo htmlspecialchars(
                       $_SESSION["add_event_token"],
                       ENT_QUOTES
                   ); ?>">

            <div class="event-form-fields">

                <div class="form-group">
                    <label for="event_name">
                        Event Name *
                    </label>

                    <input class="form-control"
                           type="text"
                           id="event_name"
                           name="event_name"
                           maxlength="100"
                           value="<?php echo htmlspecialchars(
                               $event_name, ENT_QUOTES
                           ); ?>"
                           placeholder="e.g. Blockchain Workshop"
                           required>
                </div>

                <div class="event-form-grid">

                    <div class="form-group">
                        <label for="event_date">
                            Event Date *
                        </label>

                        <input class="form-control"
                               type="date"
                               id="event_date"
                               name="event_date"
                               value="<?php echo htmlspecialchars(
                                   $event_date, ENT_QUOTES
                               ); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="event_time">
                            Event Time *
                        </label>

                        <input class="form-control"
                               type="time"
                               id="event_time"
                               name="event_time"
                               value="<?php echo htmlspecialchars(
                                   $event_time, ENT_QUOTES
                               ); ?>"
                               required>
                    </div>

                </div>

                <div class="form-group">
                    <label for="location">
                        Location *
                    </label>

                    <input class="form-control"
                           type="text"
                           id="location"
                           name="location"
                           maxlength="100"
                           value="<?php echo htmlspecialchars(
                               $location, ENT_QUOTES
                           ); ?>"
                           placeholder="e.g. Taylor's Lakeside Campus"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">
                        Event Description
                    </label>

                    <textarea class="form-control event-form-textarea"
                              id="description"
                              name="description"
                              placeholder="Describe your event..."><?php
                        echo htmlspecialchars($description);
                    ?></textarea>

                    <p class="event-form-help">
                        Explain what students can expect
                        from this event.
                    </p>
                </div>

            </div>

            <div class="event-form-actions">

                <button type="submit" class="btn-primary">
                    Create Event
                </button>

                <a href="my_events.php" class="btn-secondary">
                    Cancel
                </a>

            </div>

        </form>

    </section>

</main>

<footer class="footer">
    <div class="container">
        Agora — Taylor's University Event Platform
    </div>
</footer>

</body>
</html>