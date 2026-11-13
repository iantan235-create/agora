<?php
require_once "organizer_only.php";

$organizer_id = (int)$_SESSION["user_id"];

$sql = "SELECT club_name,
               club_description,
               club_contact_email
        FROM users
        WHERE user_id = ?
        AND role = 'organizer'
        AND account_status = 'approved'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$organizer_id]);

$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    http_response_code(403);
    die("Club profile not found or access denied.");
}

if (!isset($_SESSION["club_edit_token"])) {
    $_SESSION["club_edit_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

$saved = !empty($_SESSION["club_edit_saved"]);
unset($_SESSION["club_edit_saved"]);

// Existing profile values
$description = $club["club_description"] ?? "";
$contact_email = $club["club_contact_email"] ?? "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["club_edit_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    $new_description = $_POST["club_description"] ?? "";
    $new_email = $_POST["club_contact_email"] ?? "";

    if (!is_string($new_description) ||
        !is_string($new_email)) {

        $error = "Invalid form information.";

    } else {

        $description = trim($new_description);
        $contact_email = trim($new_email);

        // Validate description length
        if (strlen($description) > 5000) {

            $error = "Club description is too long. " .
                     "Maximum 5000 bytes.";

        // Validate optional public email
        } elseif (
            $contact_email != "" &&
            (
                !filter_var(
                    $contact_email,
                    FILTER_VALIDATE_EMAIL
                ) ||
                strlen($contact_email) > 100
            )
        ) {

            $error = "Please enter a valid contact email.";

        } else {

            try {

                // Update only this organizer's record
                $sql = "UPDATE users
                        SET club_description = ?,
                            club_contact_email = ?
                        WHERE user_id = ?
                        AND role = 'organizer'
                        AND account_status = 'approved'";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $description,
                    $contact_email,
                    $organizer_id
                ]);

                $_SESSION["club_edit_saved"] = true;

                // Redirect after successful update
                header(
                    "Location: edit_club.php",
                    true,
                    303
                );
                exit;

            } catch (PDOException $e) {

                $error = "Could not save the profile. " .
                         "Please try again.";
            }
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

    <title>Edit Club Profile - Agora</title>

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

    <a href="dashboard.php" class="event-form-back">
        &larr; Back to Dashboard
    </a>

    <div class="eyebrow">
        Club Management
    </div>

    <h1>Edit Club Profile</h1>

    <p class="event-form-intro">
        Manage how students discover and
        learn about your club.
    </p>

    <!-- Success Message -->

    <?php if ($saved) { ?>

        <div class="club-editor-success" role="status">
            Club profile updated successfully!
        </div>

    <?php } ?>

    <!-- Error Message -->

    <?php if ($error != "") { ?>

        <div class="event-form-error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <!-- Club Information Form -->

    <section class="event-form-panel">

        <div class="club-editor-identity">

            <div class="club-editor-avatar">
                <?php echo htmlspecialchars(
                    strtoupper(
                        substr($club["club_name"], 0, 1)
                    )
                ); ?>
            </div>

            <div>

                <h2>
                    <?php echo htmlspecialchars(
                        $club["club_name"]
                    ); ?>
                </h2>

                <p>
                    Approved Organizer Account
                </p>

            </div>

        </div>

        <form action="edit_club.php" method="POST">

            <input type="hidden"
                   name="token"
                   value="<?php echo htmlspecialchars(
                       $_SESSION["club_edit_token"],
                       ENT_QUOTES
                   ); ?>">

            <div class="event-form-fields">

                <!-- Club Name -->

                <div class="form-group">

                    <label for="club_name">
                        Club Name
                    </label>

                    <input
                        class="form-control club-editor-readonly"
                        type="text"
                        id="club_name"
                        value="<?php echo htmlspecialchars(
                            $club["club_name"],
                            ENT_QUOTES
                        ); ?>"
                        readonly>

                    <p class="event-form-help">
                        Club name cannot be changed here.
                    </p>

                </div>

                <!-- Club Description -->

                <div class="form-group">

                    <label for="club_description">
                        Club Description
                    </label>

                    <textarea
                        class="form-control event-form-textarea"
                        id="club_description"
                        name="club_description"
                        rows="6"
                        maxlength="5000"
                        placeholder="Tell students about your club..."><?php
                            echo htmlspecialchars(
                                $description,
                                ENT_QUOTES
                            );
                        ?></textarea>

                    <p class="event-form-help">
                        Explain your club's activities,
                        interests and purpose.
                    </p>

                </div>

                <!-- Public Contact Email -->

                <div class="form-group">

                    <label for="club_contact_email">
                        Public Contact Email
                    </label>

                    <input
                        class="form-control"
                        type="email"
                        id="club_contact_email"
                        name="club_contact_email"
                        maxlength="100"
                        value="<?php echo htmlspecialchars(
                            $contact_email,
                            ENT_QUOTES
                        ); ?>"
                        placeholder="club@example.com">

                    <p class="event-form-help">
                        Optional. This email will be
                        visible to students on your
                        public club profile.
                    </p>

                </div>

            </div>

            <!-- Save Buttons -->

            <div class="event-form-actions">

                <button type="submit" class="btn-primary">
                    Save Changes
                </button>

                <a href="club_profile.php?id=<?php
                    echo $organizer_id;
                ?>"
                   class="btn-secondary">
                    View Public Profile
                </a>

                <a href="dashboard.php" class="btn-secondary">
                    Back to Dashboard
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