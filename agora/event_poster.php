<?php
require_once __DIR__ . '/organizer_only.php';

$organizerId = (int) $_SESSION['user_id'];
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$stmt = $pdo->prepare(
    "SELECT event_id, event_name, poster_path
     FROM events
     WHERE event_id = ? AND organizer_id = ?
     LIMIT 1"
);

$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Event not found or permission denied.');
}

if (empty($_SESSION['poster_csrf'])) {
    $_SESSION['poster_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$uploadDir = __DIR__ . '/uploads/event_posters';

function deleteOldPoster(?string $path): void
{
    if (!$path) {
        return;
    }

    if (preg_match(
        '~^uploads/event_posters/[a-f0-9]{32}\.(jpg|png|webp)$~',
        $path
    )) {
        $fullPath = __DIR__ . '/' . $path;

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['poster_csrf'], $token)
    ) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {

        $action = $_POST['action'] ?? '';
        $oldPoster = $event['poster_path'];

        if ($action === 'upload') {

            $newPath = null;

            try {
                $file = $_FILES['poster'] ?? null;

                if (
                    !is_array($file) ||
                    !isset($file['error']) ||
                    is_array($file['error']) ||
                    $file['error'] !== UPLOAD_ERR_OK
                ) {
                    throw new RuntimeException(
                        'Please choose a valid image to upload.'
                    );
                }

                if (
                    $file['size'] <= 0 ||
                    $file['size'] > 5 * 1024 * 1024
                ) {
                    throw new RuntimeException(
                        'The poster must be smaller than 5 MB.'
                    );
                }

                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new RuntimeException(
                        'Invalid uploaded file.'
                    );
                }

                $mime = (new finfo(FILEINFO_MIME_TYPE))
                    ->file($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mime])) {
                    throw new RuntimeException(
                        'Only JPG, PNG and WebP images are allowed.'
                    );
                }

                if (getimagesize($file['tmp_name']) === false) {
                    throw new RuntimeException(
                        'The selected file is not a valid image.'
                    );
                }

                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0755, true)
                ) {
                    throw new RuntimeException(
                        'Cannot create the poster folder.'
                    );
                }

                $filename = bin2hex(random_bytes(16))
                    . '.' . $allowedTypes[$mime];

                $newPath = 'uploads/event_posters/' . $filename;
                $fullPath = __DIR__ . '/' . $newPath;

                if (!move_uploaded_file(
                    $file['tmp_name'],
                    $fullPath
                )) {
                    throw new RuntimeException(
                        'Unable to save the poster.'
                    );
                }

                $update = $pdo->prepare(
                    "UPDATE events
                     SET poster_path = ?
                     WHERE event_id = ?
                       AND organizer_id = ?"
                );

                $update->execute([
                    $newPath,
                    $eventId,
                    $organizerId
                ]);

                if ($oldPoster !== $newPath) {
                    deleteOldPoster($oldPoster);
                }

                header(
                    'Location: event_poster.php?id=' .
                    $eventId . '&status=saved'
                );
                exit;

            } catch (Throwable $e) {

                if ($newPath) {
                    deleteOldPoster($newPath);
                }

                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Unable to save the poster. Please try again.';
            }

        } elseif ($action === 'remove') {

            try {
                $update = $pdo->prepare(
                    "UPDATE events
                     SET poster_path = NULL
                     WHERE event_id = ?
                       AND organizer_id = ?"
                );

                $update->execute([$eventId, $organizerId]);

                deleteOldPoster($oldPoster);

                header(
                    'Location: event_poster.php?id=' .
                    $eventId . '&status=removed'
                );
                exit;

            } catch (Throwable $e) {
                $error = 'Unable to remove the poster.';
            }

        } else {
            $error = 'Unknown action.';
        }
    }
}

$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Manage Event Poster | Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .poster-container {
            max-width: 660px;
            margin: 45px auto;
            padding: 30px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .poster-preview {
            display: block;
            width: 100%;
            max-width: 420px;
            max-height: 550px;
            object-fit: contain;
            margin: 22px 0;
            border-radius: 12px;
            background: #f7f8fa;
        }

        .poster-container input[type="file"] {
            display: block;
            margin: 18px 0;
            max-width: 100%;
        }

        .poster-button {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 15px;
        }

        .remove-button {
            background: white;
            color: #b91c1c;
            border: 1px solid #b91c1c;
            border-radius: 8px;
            padding: 10px 18px;
            cursor: pointer;
        }

        .notice {
            padding: 12px;
            margin: 15px 0;
            background: #eef6ef;
            border-radius: 8px;
        }

        .error {
            color: #b91c1c;
            margin: 15px 0;
        }
    </style>
</head>

<body>

<main class="poster-container">

    <a href="my_events.php">← Back to My Events</a>

    <h1>Manage Event Poster</h1>

    <p>
        Event:
        <strong>
            <?= htmlspecialchars(
                $event['event_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>
    </p>

    <?php if ($status === 'saved'): ?>
        <p class="notice">Poster uploaded successfully.</p>
    <?php elseif ($status === 'removed'): ?>
        <p class="notice">Poster removed successfully.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($event['poster_path'])): ?>

        <h3>Current Event Poster</h3>

        <img
            class="poster-preview"
            src="<?= htmlspecialchars(
                $event['poster_path'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            alt="Current event poster"
        >

    <?php else: ?>

        <p>No poster uploaded yet.</p>

    <?php endif; ?>

    <h3>Upload or Replace Poster</h3>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['poster_csrf'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="upload"
        >

        <label for="poster">Select poster image</label>

        <input
            type="file"
            id="poster"
            name="poster"
            accept="image/jpeg,image/png,image/webp"
            required
        >

        <p>Accepted formats: JPG, PNG, WebP. Max 5 MB.</p>

        <button class="poster-button" type="submit">
            Save Poster
        </button>

    </form>

    <?php if (!empty($event['poster_path'])): ?>

        <hr style="margin: 28px 0;">

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['poster_csrf'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="remove"
            >

            <button
                class="remove-button"
                type="submit"
                onclick="return confirm(
                    'Remove this event poster?'
                );"
            >
                Remove Poster
            </button>

        </form>

    <?php endif; ?>

</main>

</body>
</html>
