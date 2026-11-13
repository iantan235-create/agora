<?php
session_start();
require_once "pdo.php";

// Redirect users who are already logged in
if (isset($_SESSION["user_id"]) &&
    in_array(
        $_SESSION["role"] ?? "",
        ["student", "organizer"],
        true
    )) {

    header("Location: dashboard.php");
    exit;
}

// Create CSRF security token
if (!isset($_SESSION["login_token"])) {
    $_SESSION["login_token"] =
        bin2hex(random_bytes(32));
}

$error = "";
$email = "";

// Show registration success message
$registration_success =
    (($_GET["registered"] ?? "") === "1");

// =====================================
// Process Login
// =====================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST["token"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["login_token"],
            $token
        )) {

        http_response_code(403);
        die("Invalid request.");
    }

    $submitted_email = $_POST["email"] ?? "";
    $submitted_password = $_POST["password"] ?? "";

    if (!is_string($submitted_email) ||
        !is_string($submitted_password)) {

        $error = "Invalid login information.";

    } else {

        $email = trim($submitted_email);
        $password = $submitted_password;

        if ($email == "" || $password == "") {

            $error = "Please enter your email and password.";

        } elseif (!filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )) {

            $error = "Please enter a valid email address.";

        } else {

            // Retrieve the user account
            $sql = "SELECT user_id,
                           full_name,
                           email,
                           `password`,
                           role,
                           account_status
                    FROM users
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if (!$user ||
                !password_verify(
                    $password,
                    $user["password"]
                )) {

                $error = "Incorrect email or password.";

            } elseif (
                $user["account_status"] != "approved"
            ) {

                $error = "Your account is not approved yet. " .
                         "Please contact the administrator.";

            } elseif (!in_array(
                $user["role"],
                ["student", "organizer"],
                true
            )) {

                $error = "This account cannot access Agora.";

            } else {

                // Generate a new session ID
                session_regenerate_id(true);

                // Clear old session data
                $_SESSION = [];

                // Store authenticated user information
                $_SESSION["user_id"] =
                    (int)$user["user_id"];

                $_SESSION["full_name"] =
                    $user["full_name"];

                $_SESSION["role"] =
                    $user["role"];

                // Redirect to dashboard
                header(
                    "Location: dashboard.php",
                    true,
                    303
                );
                exit;
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

    <title>Login - Agora</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Header -->

<header class="site-header">

    <div class="container header-content">

        <a href="index.php" class="brand">
            agora<span>.</span>
        </a>

        <a href="index.php" class="logout">
            Browse Events
        </a>

    </div>

</header>

<!-- Login Page -->

<main class="container auth-page">

    <div class="auth-layout">

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
                    Discover events, connect with
                    student clubs and make the most
                    of campus life at Taylor's University.
                </p>

            </div>

            <div class="auth-info-footer">
                Discover. Register. Participate.
            </div>

        </section>

        <!-- Right Login Form -->

        <section class="auth-form-area">

            <h1>Welcome Back</h1>

            <p class="auth-subtitle">
                Sign in to your Agora account.
            </p>

            <!-- Registration Success Message -->

            <?php if ($registration_success) { ?>

                <div class="auth-notice" role="status">

                    Student account created successfully!
                    You can now sign in.

                </div>

            <?php } ?>

            <!-- Login Error Message -->

            <?php if ($error != "") { ?>

                <div class="auth-error" role="alert">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php } ?>

            <!-- Login Form -->

            <form class="auth-form"
                  action="login.php"
                  method="POST">

                <!-- Security Token -->

                <input
                    type="hidden"
                    name="token"
                    value="<?php echo htmlspecialchars(
                        $_SESSION["login_token"],
                        ENT_QUOTES
                    ); ?>">

                <!-- Email -->

                <div>

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars(
                            $email,
                            ENT_QUOTES
                        ); ?>"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required>

                </div>

                <!-- Password -->

                <div>

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>

                </div>

                <!-- Login Button -->

                <button
                    type="submit"
                    class="auth-submit">

                    Sign In

                </button>

            </form>

            <!-- Registration Links -->

            <div class="auth-divider"></div>

            <p class="auth-helper">
                New to Agora? Choose your account type:
            </p>

            <div class="auth-signup-links">

                <a href="student_signup.php">
                    Create Student Account &rarr;
                </a>

                <a href="organizer_signup.php">
                    Register as Club Organizer &rarr;
                </a>

            </div>

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
