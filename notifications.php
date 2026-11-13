<?php
session_start();
require_once "pdo.php";

if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") != "student") {

    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

if (!isset($_SESSION["notification_token"])) {
    $_SESSION["notification_token"] =
        bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["notification_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    $notification_id =
        (int)($_POST["notification_id"] ?? 0);

    if ($notification_id <= 0) {
        http_response_code(400);
        die("Invalid notification.");
    }

    $sql = "UPDATE notifications
            SET is_read = 1
            WHERE notification_id = ?
            AND user_id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $notification_id,
        $user_id
    ]);

    header("Location: notifications.php", true, 303);
    exit;
}

$timezone = new DateTimeZone(
    "Asia/Kuala_Lumpur"
);

$tomorrow = (new DateTimeImmutable(
    "now",
    $timezone
))->modify("+1 day")->format("Y-m-d");

$sql = "INSERT INTO notifications
        (user_id, event_id,
         notification_type, message)
        SELECT
            er.user_id,
            e.event_id,
            'reminder',
            CONCAT(
                'Reminder: ',
                e.event_name,
                ' is scheduled for tomorrow.'
            )
        FROM event_registrations er
        INNER JOIN events e
        ON er.event_id = e.event_id
        WHERE er.user_id = ?
        AND e.event_date = ?
        AND e.event_status = 'active'
        AND NOT EXISTS (
            SELECT 1
            FROM notifications n
            WHERE n.user_id = er.user_id
            AND n.event_id = e.event_id
            AND n.notification_type = 'reminder'
        )";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id,
    $tomorrow
]);

$sql = "SELECT
            notification_id,
            event_id,
            notification_type,
            message,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC,
                 notification_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$notifications = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


$unread_count = 0;

foreach ($notifications as $notification) {
    if ($notification["is_read"] == 0) {
        $unread_count++;
    }
}


$type_labels = [
    "registration" => "Registration Confirmed",
    "reminder" => "Event Reminder",
    "event_update" => "Event Update",
    "event_cancelled" => "Event Cancellation",
    "new_event" => "New Club Event"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Notifications - Agora</title>

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

<main class="container notifications-page">

    <div class="eyebrow">
        Stay Informed
    </div>

    <h1>My Notifications</h1>

    <p class="notifications-intro">
        Stay updated on your registered events
        and followed clubs.
    </p>

    <div class="notifications-summary">

        <?php echo $unread_count; ?>

        Unread Notification<?php
            echo $unread_count == 1 ? "" : "s";
        ?>

    </div>

    <?php if (count($notifications) == 0) { ?>

        <div class="notification-empty">

            <h2>No Notifications Yet</h2>

            <p>
                You will see registration confirmations,
                reminders and club updates here.
            </p>

            <a href="index.php" class="btn-primary">
                Browse Events
            </a>

        </div>

    <?php } else { ?>

        <div class="notifications-list">

            <?php foreach ($notifications as $notification) { ?>

                <?php
                $type = $notification["notification_type"];

                $type_label =
                    $type_labels[$type] ?? "Notification";

                $is_unread =
                    $notification["is_read"] == 0;

                $card_class = "notification-card";

                if ($is_unread) {
                    $card_class .= " notification-unread";
                }

                $type_class = "notification-type";

                if ($type == "event_cancelled") {
                    $type_class .= " notification-danger";
                } elseif ($type == "reminder") {
                    $type_class .= " notification-warning";
                }
                ?>

                <article class="<?php
                    echo $card_class;
                ?>">

                    <div class="notification-top">

                        <span class="<?php
                            echo $type_class;
                        ?>">
                            <?php echo htmlspecialchars(
                                $type_label
                            ); ?>
                        </span>

                        <?php if ($is_unread) { ?>

                            <span class="notification-status
                                         notification-status-unread">
                                Unread
                            </span>

                        <?php } else { ?>

                            <span class="notification-status">
                                Read
                            </span>

                        <?php } ?>

                    </div>

                    <p class="notification-message">
                        <?php echo htmlspecialchars(
                            $notification["message"]
                        ); ?>
                    </p>

                    <div class="notification-date">
                        Received:
                        <?php echo htmlspecialchars(
                            $notification["created_at"]
                        ); ?>
                    </div>

                    <div class="notification-actions">

                        <?php if (!empty(
                            $notification["event_id"]
                        )) { ?>

                            <a class="btn-primary"
                               href="event_details.php?id=<?php
                                   echo (int)$notification["event_id"];
                               ?>">
                                View Event
                            </a>

                        <?php } ?>

                        <?php if ($is_unread) { ?>

                            <form action="notifications.php"
                                  method="POST">

                                <input type="hidden"
                                       name="notification_id"
                                       value="<?php
                                           echo (int)$notification[
                                               "notification_id"
                                           ];
                                       ?>">

                                <input type="hidden"
                                       name="token"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $_SESSION[
                                                   "notification_token"
                                               ],
                                               ENT_QUOTES
                                           );
                                       ?>">

                                <button type="submit"
                                        class="btn-secondary">
                                    Mark as Read
                                </button>

                            </form>

                        <?php } ?>

                    </div>

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
