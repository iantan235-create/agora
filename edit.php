<?php
require_once "organizer_only.php";

$id = (int)($_POST["id"] ?? $_GET["id"] ?? 0);

if ($id <= 0) {
    die("Invalid event ID.");
}

$sql = "SELECT * FROM events
        WHERE event_id = ?
        AND organizer_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id, $_SESSION["user_id"]]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(403);
    die("Event not found or access denied.");
}

// Do not allow cancelled events to be edited
if ($event["event_status"] != "active") {
    http_response_code(403);
    die("Cancelled events cannot be edited.");
}

// Generate security token
if (!isset($_SESSION["edit_event_token"])) {
    $_SESSION["edit_event_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// Existing form values
$event_name = $event["event_name"];
$event_date = $event["event_date"];
$event_time = substr($event["event_time"], 0, 5);
$location = $event["location"];
$description = $event["description"] ?? "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["edit_event_token"], $token)) {
        http_response_code(403);
        die("Invalid request.");
    }

    $event_name = trim($_POST["event_name"] ?? "");
    $event_date = trim($_POST["event_date"] ?? "");
    $event_time = trim($_POST["event_time"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Validate date
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

            // Lock and retrieve the latest event record
            $sql = "SELECT *
                    FROM events
                    WHERE event_id = ?
                    AND organizer_id = ?
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $_SESSION["user_id"]]);

            $current_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$current_event ||
                $current_event["event_status"] != "active") {
                throw new Exception(
                    "This event cannot be edited."
                );
            }

            // Check if scheduling information changed
            $details_changed =
                $current_event["event_date"] != $event_date ||
                substr($current_event["event_time"], 0, 5)
                    != $event_time ||
                $current_event["location"] != $location;

            // Save event changes
            $sql = "UPDATE events
                    SET event_name = ?,
                        event_date = ?,
                        event_time = ?,
                        location = ?,
                        description = ?
                    WHERE event_id = ?
                    AND organizer_id = ?
                    AND event_status = 'active'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $event_name,
                $event_date,
                $event_time,
                $location,
                $description,
                $id,
                $_SESSION["user_id"]
            ]);

            // Notify registered students of schedule changes
            if ($details_changed) {

                $message = "Event updated: " .
                           $event_name .
                           ". Please check the latest date, " .
                           "time and location.";

                $sql = "INSERT INTO notifications
                        (user_id, event_id,
                         notification_type, message)
                        SELECT user_id, ?,
                               'event_update', ?
                        FROM event_registrations
                        WHERE event_id = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $id,
                    $message,
                    $id
                ]);
            }

            $pdo->commit();

            header("Location: my_events.php", true, 303);
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Could not save the changes. " .
                     "Please try again.";
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

    <title>Edit Event - Agora</title>

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

    <h1>Edit Event</h1>

    <p class="event-form-intro">
        Update your event details and keep
        registered students informed.
    </p>

    <div class="event-form-note">
        Changing the event date, time or location
        will generate notifications for registered students.
    </div>

    <?php if ($error != "") { ?>
        <div class="event-form-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <section class="event-form-panel">

        <h2>Event Information</h2>

        <form action="edit.php" method="POST">

            <input type="hidden"
                   name="id"
                   value="<?php echo $id; ?>">

            <input type="hidden"
                   name="token"
                   value="<?php echo htmlspecialchars(
                       $_SESSION["edit_event_token"],
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
                           required>
                </div>

                <div class="form-group">
                    <label for="description">
                        Event Description
                    </label>

                    <textarea class="form-control event-form-textarea"
                              id="description"
                              name="description"><?php
                        echo htmlspecialchars($description);
                    ?></textarea>
                </div>

            </div>

            <div class="event-form-actions">

                <button type="submit" class="btn-primary">
                    Save Changes
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
