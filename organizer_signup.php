<?php
session_start();
require_once "pdo.php";

// Redirect already logged-in users
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

// Escape output safely
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

// Create CSRF token
if (!isset($_SESSION["organizer_signup_token"])) {
    $_SESSION["organizer_signup_token"] =
        bin2hex(random_bytes(32));
}

// Success message after registration
$registered = !empty(
    $_SESSION["organizer_signup_success"]
);

unset($_SESSION["organizer_signup_success"]);

$error = "";

// Default form values
$full_name = "";
$club_name = "";
$email = "";
$club_contact_email = "";
$club_description = "";

// =====================================
// Process Organizer Registration
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["organizer_signup_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    // Read submitted fields
    $raw_name = $_POST["full_name"] ?? "";
    $raw_club = $_POST["club_name"] ?? "";
    $raw_email = $_POST["email"] ?? "";
    $raw_contact = $_POST["club_contact_email"] ?? "";
    $raw_description = $_POST["club_description"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm_password =
        $_POST["confirm_password"] ?? "";

    // Reject unexpected input types
    if (
        !is_string($raw_name) ||
        !is_string($raw_club) ||
        !is_string($raw_email) ||
        !is_string($raw_contact) ||
        !is_string($raw_description) ||
        !is_string($password) ||
        !is_string($confirm_password)
    ) {

        $error = "Invalid form information.";

    } else {

        $full_name = trim($raw_name);
        $club_name = trim($raw_club);
        $email = trim($raw_email);
        $club_contact_email = trim($raw_contact);
        $club_description = trim($raw_description);

        // Validate required fields
        if (
            $full_name === "" ||
            $club_name === "" ||
            $email === "" ||
            $password === "" ||
            $confirm_password === ""
        ) {

            $error = "Please fill in all required fields.";

        } elseif (
            strlen($full_name) > 100 ||
            strlen($club_name) > 100 ||
            strlen($email) > 100
        ) {

            $error = "One or more fields are too long.";

        } elseif (
            strlen($club_description) > 5000
        ) {

            $error = "Club description is too long.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error = "Please enter a valid login email.";

        } elseif (
            $club_contact_email !== "" &&
            (
                strlen($club_contact_email) > 100 ||
                !filter_var(
                    $club_contact_email,
                    FILTER_VALIDATE_EMAIL
                )
            )
        ) {

            $error = "Please enter a valid club contact email.";

        } elseif (
            strlen($password) < 8 ||
            strlen($password) > 72
        ) {

            $error = "Password must be between " .
                     "8 and 72 bytes long.";

        } elseif (
            $password !== $confirm_password
        ) {

            $error = "Passwords do not match.";

        } else {

            // Check if email is already registered
            $sql = "SELECT user_id
                    FROM users
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            $existing_user = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if ($existing_user) {

                $error = "This email address is " .
                         "already registered.";

            } else {

                try {

                    // Hash password before storage
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Create a pending organizer account
                    $sql = "INSERT INTO users
                            (
                                full_name,
                                email,
                                `password`,
                                role,
                                account_status,
                                club_name,
                                club_description,
                                club_contact_email
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $full_name,
                        $email,
                        $hashed_password,
                        "organizer",
                        "pending",
                        $club_name,
                        $club_description,
                        $club_contact_email !== ""
                            ? $club_contact_email
                            : null
                    ]);

                    // Save success message
                    $_SESSION[
                        "organizer_signup_success"
                    ] = true;

                    // Rotate registration token
                    unset(
                        $_SESSION["organizer_signup_token"]
                    );

                    // Prevent duplicate form submission
                    header(
                        "Location: organizer_signup.php",
                        true,
                        303
                    );
                    exit;

                } catch (PDOException $e) {

                    if ($e->getCode() === "23000") {

                        $error = "This email address is " .
                                 "already registered.";

                    } else {

                        $error = "Registration failed. " .
                                 "Please try again.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Organizer Sign-up - Agora</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Header -->

<header class="site-header">

    <div class="container header-content">

        <a href="index.php" class="brand">
            agora<span>.</span>
        </a>

        <a href="login.php" class="logout">
            Login
        </a>

    </div>

</header>

<main class="container auth-signup-page">

    <div class="auth-signup-layout">

        <!-- Left Information Panel -->

        <section class="auth-info">

            <div class="auth-info-logo">
                agora.
            </div>

            <div>

                <h2>
                    Bring your club<br>
                    together.
                </h2>

                <p>
                    Create campus events, manage
                    student registrations, track
                    attendance and connect with
                    your club's followers.
                </p>

            </div>

            <div class="auth-info-footer">
                Organize. Connect. Inspire.
            </div>

        </section>

        <!-- Registration Panel -->

        <section class="auth-form-area">

            <?php if ($registered) { ?>

                <!-- Registration Success Screen -->

                <h1>Registration Submitted!</h1>

                <p class="auth-subtitle">
                    Your organizer account application
                    has been received.
                </p>

                <div class="auth-notice" role="status">

                    Your application is pending approval.

                    Once your account is approved,
                    you can log in and start managing
                    your club's events.

                </div>

                <p class="auth-helper">
                    Please contact the platform
                    administrator if you need
                    information about your application.
                </p>

                <div class="auth-divider"></div>

                <a href="login.php"
                   class="btn-primary">
                    Back to Login
                </a>

            <?php } else { ?>

                <!-- Registration Form -->

                <h1>Create Organizer Account</h1>

                <p class="auth-subtitle">
                    Register your student club
                    to start organizing campus events.
                </p>

                <div class="auth-notice">
                    Organizer accounts require approval
                    before they can access Agora.
                </div>

                <!-- Error Message -->

                <?php if ($error !== "") { ?>

                    <div class="auth-error" role="alert">
                        <?php echo e($error); ?>
                    </div>

                <?php } ?>

                <form
                    class="auth-form"
                    action="organizer_signup.php"
                    method="POST">

                    <!-- CSRF Token -->

                    <input
                        type="hidden"
                        name="token"
                        value="<?php
                            echo e(
                                $_SESSION[
                                    "organizer_signup_token"
                                ]
                            );
                        ?>">

                    <div class="auth-signup-grid">

                        <!-- Organizer Full Name -->

                        <div class="auth-full-width">

                            <label for="full_name">
                                Organizer Full Name *
                            </label>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                maxlength="100"
                                value="<?php
                                    echo e($full_name);
                                ?>"
                                placeholder="Enter your full name"
                                autocomplete="name"
                                required>

                        </div>

                        <!-- Club Name -->

                        <div class="auth-full-width">

                            <label for="club_name">
                                Club Name *
                            </label>

                            <input
                                type="text"
                                id="club_name"
                                name="club_name"
                                maxlength="100"
                                value="<?php
                                    echo e($club_name);
                                ?>"
                                placeholder="e.g. Blockchain Club"
                                required>

                        </div>

                        <!-- Login Email -->

                        <div class="auth-full-width">

                            <label for="email">
                                Login Email Address *
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="100"
                                value="<?php
                                    echo e($email);
                                ?>"
                                placeholder="organizer@example.com"
                                autocomplete="email"
                                required>

                        </div>

                        <!-- Public Club Email -->

                        <div class="auth-full-width">

                            <label for="club_contact_email">
                                Public Club Contact Email
                            </label>

                            <input
                                type="email"
                                id="club_contact_email"
                                name="club_contact_email"
                                maxlength="100"
                                value="<?php
                                    echo e($club_contact_email);
                                ?>"
                                placeholder="club@example.com">

                            <p class="auth-password-hint">
                                Optional. Students will see
                                this email on your club profile.
                            </p>

                        </div>

                        <!-- Club Description -->

                        <div class="auth-full-width">

                            <label for="club_description">
                                Club Description
                            </label>

                            <textarea
                                class="form-control event-form-textarea"
                                id="club_description"
                                name="club_description"
                                rows="5"
                                maxlength="5000"
                                placeholder="Tell students about your club..."><?php
                                    echo e($club_description);
                                ?></textarea>

                            <p class="auth-password-hint">
                                Optional. Explain your club's
                                purpose, interests and activities.
                            </p>

                        </div>

                        <!-- Password -->

                        <div>

                            <label for="password">
                                Password *
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                required>

                        </div>

                        <!-- Confirm Password -->

                        <div>

                            <label for="confirm_password">
                                Confirm Password *
                            </label>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                autocomplete="new-password"
                                required>

                        </div>

                    </div>

                    <p class="auth-password-hint">
                        Use a password containing
                        at least 8 characters.
                    </p>

                    <!-- Submit Button -->

                    <button
                        type="submit"
                        class="auth-submit">
                        Submit Registration
                    </button>

                </form>

                <p class="auth-bottom-link">
                    Already have an account?
                    <a href="login.php">Sign In</a>
                </p>

                <div class="auth-divider"></div>

                <p class="auth-helper">
                    Want to join campus activities
                    as a student?
                </p>

                <div class="auth-signup-links">

                    <a href="student_signup.php">
                        Create Student Account &rarr;
                    </a>

                </div>

            <?php } ?>

        </section>

    </div>

</main>

<!-- Footer -->

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
