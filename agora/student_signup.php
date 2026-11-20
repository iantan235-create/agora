<?php
session_start();
require_once "pdo.php";

// Redirect users who are already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

// Create CSRF protection token
if (!isset($_SESSION["student_signup_token"])) {
    $_SESSION["student_signup_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// Form values
$full_name = "";
$student_id = "";
$email = "";

// =====================================
// Process Student Registration
// =====================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verify security token
    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["student_signup_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    // Retrieve submitted values
    $raw_name = $_POST["full_name"] ?? "";
    $raw_id = $_POST["student_id"] ?? "";
    $raw_email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm_password =
        $_POST["confirm_password"] ?? "";

    // Reject unexpected array inputs
    if (!is_string($raw_name) ||
        !is_string($raw_id) ||
        !is_string($raw_email) ||
        !is_string($password) ||
        !is_string($confirm_password)) {

        $error = "Invalid form information.";

    } else {

        $full_name = trim($raw_name);
        $student_id = trim($raw_id);
        $email = trim($raw_email);

        // Validate required fields
        if ($full_name == "" ||
            $student_id == "" ||
            $email == "" ||
            $password == "" ||
            $confirm_password == "") {

            $error = "Please fill in all required fields.";

        } elseif (
            strlen($full_name) > 100 ||
            strlen($student_id) > 30 ||
            strlen($email) > 100
        ) {

            $error = "One or more fields are too long.";

        } elseif (!filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )) {

            $error = "Please enter a valid email address.";

        } elseif (
            strlen($password) < 8 ||
            strlen($password) > 72
        ) {

            $error = "Password must be between " .
                     "8 and 72 bytes long.";

        } elseif ($password !== $confirm_password) {

            $error = "Passwords do not match.";

        } else {

            // Check whether email is already registered
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

                    // Securely hash password
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Student accounts are approved
                    // automatically in this prototype
                    $sql = "INSERT INTO users
                            (full_name,
                             student_id,
                             email,
                             `password`,
                             role,
                             account_status)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $full_name,
                        $student_id,
                        $email,
                        $hashed_password,
                        "student",
                        "approved"
                    ]);

                    // Redirect to login
                    header(
                        "Location: login.php?registered=1",
                        true,
                        303
                    );
                    exit;

                } catch (PDOException $e) {

                    if ($e->getCode() == "23000") {

                        $error = "This account information " .
                                 "is already registered.";

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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Student Sign-up - Agora</title>

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
                    Your campus.<br>
                    Your community.
                </h2>

                <p>
                    Discover campus events, register
                    for activities, follow student
                    clubs and record your participation
                    through Agora.
                </p>

            </div>

            <div class="auth-info-footer">
                Discover. Register. Participate.
            </div>

        </section>

        <!-- Right Registration Form -->

        <section class="auth-form-area">

            <h1>Create Student Account</h1>

            <p class="auth-subtitle">
                Join Agora and discover new opportunities
                at Taylor's University.
            </p>

            <!-- Error Message -->

            <?php if ($error != "") { ?>

                <div class="auth-error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>

            <form
                class="auth-form"
                action="student_signup.php"
                method="POST">

                <!-- CSRF Token -->

                <input
                    type="hidden"
                    name="token"
                    value="<?php echo htmlspecialchars(
                        $_SESSION["student_signup_token"],
                        ENT_QUOTES
                    ); ?>">

                <div class="auth-signup-grid">

                    <!-- Full Name -->

                    <div class="auth-full-width">

                        <label for="full_name">
                            Full Name
                            <span class="auth-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            maxlength="100"
                            value="<?php echo htmlspecialchars(
                                $full_name,
                                ENT_QUOTES
                            ); ?>"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required>
                    </div>

                    <!-- Student ID -->

                    <div class="auth-full-width">

                        <label for="student_id">
                            Student ID
                            <span class="auth-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="student_id"
                            name="student_id"
                            maxlength="30"
                            value="<?php echo htmlspecialchars(
                                $student_id,
                                ENT_QUOTES
                            ); ?>"
                            placeholder="Enter your student ID"
                            required>
                    </div>

                    <!-- Email Address -->

                    <div class="auth-full-width">

                        <label for="email">
                            Email Address
                            <span class="auth-required">*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="100"
                            value="<?php echo htmlspecialchars(
                                $email,
                                ENT_QUOTES
                            ); ?>"
                            placeholder="student@example.com"
                            autocomplete="email"
                            required>
                    </div>

                    <!-- Password -->

                    <div>

                        <label for="password">
                            Password
                            <span class="auth-required">*</span>
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
                            Confirm Password
                            <span class="auth-required">*</span>
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
                    Use a password containing at least
                    8 characters. Longer passwords
                    are generally more secure.
                </p>

                <!-- Submit Button -->

                <button
                    type="submit"
                    class="auth-submit">
                    Create Account
                </button>

            </form>

            <p class="auth-bottom-link">
                Already have an account?
                <a href="login.php">Sign In</a>
            </p>

            <div class="auth-divider"></div>

            <p class="auth-helper">
                Registering on behalf of a club?
            </p>

            <div class="auth-signup-links">
                <a href="organizer_signup.php">
                    Create Organizer Account &rarr;
                </a>
            </div>

        </section>

    </div>

</main>

<footer class="footer">

    <div class="container">
        Agora — Taylor's University Event Platform
    </div>

</footer>

</body>
</html>
