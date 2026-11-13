
<?php
require_once "organizer_only.php";

// Get the selected event ID
$id = (int)($_POST["id"] ?? $_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: my_events.php");
    exit;
}

// Retrieve an event owned by this organizer
$sql = "SELECT event_id, event_name,
               event_date, event_status
        FROM events
        WHERE event_id = ?
        AND organizer_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id, $_SESSION["user_id"]]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(403);
    die("Event not found or access denied.");
}

// Create a CSRF security token
if (!isset($_SESSION["attendance_token"])) {
    $_SESSION["attendance_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// ====================================
// Process Manual Attendance
// ====================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["attendance_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    $registration_id =
        (int)($_POST["registration_id"] ?? 0);

    $status = $_POST["attendance_status"] ?? "";

    if ($registration_id <= 0 ||
        !is_string($status) ||
        !in_array(
            $status,
            ["present", "absent"],
            true
        )) {

        $error = "Please select a valid attendance status.";

    } else {

        try {

            $pdo->beginTransaction();

            // Recheck and lock the event
            $sql = "SELECT event_status
                    FROM events
                    WHERE event_id = ?
                    AND organizer_id = ?
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $id,
                $_SESSION["user_id"]
            ]);

            $current_event = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$current_event ||
                $current_event["event_status"] != "active") {

                throw new Exception(
                    "Attendance cannot be changed for this event."
                );
            }

            // Verify this registration belongs to the event
            $sql = "SELECT registration_id
                    FROM event_registrations
                    WHERE registration_id = ?
                    AND event_id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $registration_id,
                $id
            ]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                throw new Exception(
                    "Participant registration not found."
                );
            }

            // Update attendance
            $sql = "UPDATE event_registrations
                    SET attendance_status = ?,
                        attendance_marked_at = NOW()
                    WHERE registration_id = ?
                    AND event_id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $status,
                $registration_id,
                $id
            ]);

            $pdo->commit();

            header(
                "Location: participants.php?id=" .
                $id . "&updated=1",
                true,
                303
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Could not update attendance. " .
                     "Please check the event and participant.";
        }
    }
}

// ====================================
// Retrieve Participants
// ====================================

$sql = "SELECT
            er.registration_id,
            er.registered_at,
            er.attendance_status,
            er.attendance_marked_at,
            u.full_name,
            u.email,
            u.student_id
        FROM event_registrations er
        INNER JOIN users u
        ON er.user_id = u.user_id
        WHERE er.event_id = ?
        ORDER BY er.registered_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$participants = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

// Count attendance statuses
$total = count($participants);
$present = 0;
$absent = 0;
$not_marked = 0;

foreach ($participants as $person) {

    if ($person["attendance_status"] == "present") {
        $present++;

    } elseif ($person["attendance_status"] == "absent") {
        $absent++;

    } else {
        $not_marked++;
    }
}

$is_cancelled =
    $event["event_status"] == "cancelled";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Event Participants - Agora</title>

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

<main class="container participants-page">

    <a href="my_events.php" class="event-form-back">
        &larr; Back to My Events
    </a>

    <div class="eyebrow">
        Event Management
    </div>

    <h1>Event Participants</h1>

    <p class="participants-intro">
        <?php echo htmlspecialchars(
            $event["event_name"]
        ); ?>

        — Manage student attendance.
    </p>

    <?php if ($is_cancelled) { ?>

        <div class="registration-cancelled">
            This event has been cancelled.
            Attendance records are available
            for viewing but cannot be changed.
        </div>

        <br>

    <?php } ?>

    <?php if (isset($_GET["updated"])) { ?>

        <div class="participant-success">
            Attendance updated successfully!
        </div>

    <?php } ?>

    <?php if ($error != "") { ?>

        <div class="participant-error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <!-- Attendance Statistics -->

    <section class="participant-stats">

        <div class="participant-stat">

            <div class="participant-stat-number">
                <?php echo $total; ?>
            </div>

            <div class="participant-stat-label">
                Total Registered
            </div>

        </div>

        <div class="participant-stat">

            <div class="participant-stat-number">
                <?php echo $present; ?>
            </div>

            <div class="participant-stat-label">
                Present
            </div>

        </div>

        <div class="participant-stat">

            <div class="participant-stat-number">
                <?php echo $absent; ?>
            </div>

            <div class="participant-stat-label">
                Absent
            </div>

        </div>

        <div class="participant-stat">

            <div class="participant-stat-number">
                <?php echo $not_marked; ?>
            </div>

            <div class="participant-stat-label">
                Not Marked
            </div>

        </div>

    </section>

    <!-- Participant Table -->

    <section class="participant-panel">

        <div class="results-header">

            <h2>Participant List</h2>

            <span class="results-count">
                <?php echo $total; ?> Students
            </span>

        </div>

        <?php if ($total == 0) { ?>

            <p>
                No students have registered
                for this event yet.
            </p>

        <?php } else { ?>

            <div class="participant-table-wrapper">

                <table class="participant-table">

                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Registered At</th>
                            <th>Attendance</th>
                            <th>Marked At</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($participants as $person) { ?>

                            <?php
                            $status = $person["attendance_status"];

                            if ($status == "present") {
                                $badge_class = "attendance-present";

                            } elseif ($status == "absent") {
                                $badge_class = "attendance-absent";

                            } else {
                                $badge_class = "attendance-not-marked";
                            }

                            $status_label = ucwords(
                                str_replace("_", " ", $status)
                            );
                            ?>

                            <tr>

                                <td>
                                    <div class="participant-name">
                                        <?php echo htmlspecialchars(
                                            $person["full_name"]
                                        ); ?>
                                    </div>

                                    <div class="participant-email">
                                        <?php echo htmlspecialchars(
                                            $person["email"]
                                        ); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $person["student_id"] ?? "-"
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $person["registered_at"]
                                    ); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php
                                        echo $badge_class;
                                    ?>">
                                        <?php echo htmlspecialchars(
                                            $status_label
                                        ); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $person["attendance_marked_at"]
                                        ?? "-"
                                    ); ?>
                                </td>

                                <td>

                                    <?php if (!$is_cancelled) { ?>

                                        <form
                                            class="attendance-form"
                                            action="participants.php?id=<?php
                                                echo $id;
                                            ?>"
                                            method="POST">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo $id; ?>">

                                            <input
                                                type="hidden"
                                                name="registration_id"
                                                value="<?php
                                                    echo (int)$person[
                                                        "registration_id"
                                                    ];
                                                ?>">

                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?php
                                                    echo htmlspecialchars(
                                                        $_SESSION[
                                                            "attendance_token"
                                                        ],
                                                        ENT_QUOTES
                                                    );
                                                ?>">

                                            <select
                                                name="attendance_status"
                                                class="attendance-select"
                                                aria-label="Attendance for <?php
                                                    echo htmlspecialchars(
                                                        $person["full_name"],
                                                        ENT_QUOTES
                                                    );
                                                ?>"
                                                required>

                                                <option value=""
                                                    disabled
                                                    <?php
                                                    echo $status == "not_marked"
                                                        ? "selected" : "";
                                                    ?>>
                                                    Select
                                                </option>

                                                <option value="present"
                                                    <?php
                                                    echo $status == "present"
                                                        ? "selected" : "";
                                                    ?>>
                                                    Present
                                                </option>

                                                <option value="absent"
                                                    <?php
                                                    echo $status == "absent"
                                                        ? "selected" : "";
                                                    ?>>
                                                    Absent
                                                </option>

                                            </select>

                                            <button
                                                type="submit"
                                                class="attendance-save">
                                                Save
                                            </button>

                                        </form>

                                    <?php } else { ?>

                                        <span class="registration-muted">
                                            View Only
                                        </span>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

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
