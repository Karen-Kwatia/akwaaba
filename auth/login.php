
<?php
// Include the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Start a new session or resume an existing session.
session_start();

// Initialise a variable for displaying login messages.
$message = '';

// Process the login form when it is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect the submitted email and password.
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare a query to find the user using their email.
    $query = $pdo->prepare(
        "SELECT id, name, email, password_hash, role
         FROM users
         WHERE email = ?"
    );

    // Execute the query using the submitted email.
    $query->execute([$email]);

    // Retrieve the matching user record.
    $user = $query->fetch();

    // Check whether the user exists and the password is correct.
    if ($user && password_verify($password, $user['password_hash'])) {


    // Check whether this browser already has a remember-me token.
    if (isset($_COOKIE['remember_token'])) {

        // Hash the existing token before searching the database.
        $oldTokenHash = hash('sha256', $_COOKIE['remember_token']);

        // Remove the old token from the database.
        $deleteOldToken = $pdo->prepare(
            "DELETE FROM remember_tokens WHERE token_hash = ?"
        );

        // Execute the deletion using the hashed token.
        $deleteOldToken->execute([$oldTokenHash]);

        // Expire the old browser cookie.
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Check whether the user selected Remember Me.
    if (isset($_POST['remember_me'])) {

        // Generate a random token that is difficult to guess.
        $token = bin2hex(random_bytes(32));

        // Hash the token before storing it in the database.
        $tokenHash = hash('sha256', $token);

        // Set the token to expire after 30 days.
        $expiresTimestamp = time() + (30 * 24 * 60 * 60);

        // Convert the expiry timestamp into a MySQL date format.
        $expiresAt = date('Y-m-d H:i:s', $expiresTimestamp);

        // Prepare a query to save the hashed token.
        $saveToken = $pdo->prepare(
            "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, ?)"
        );

        // Save the user's ID, token hash and expiry date.
        $saveToken->execute([
            $user['id'],
            $tokenHash,
            $expiresAt
        ]);

        // Save the original token in the browser for 30 days.
        setcookie('remember_token', $token, [
            'expires' => $expiresTimestamp,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

        // Regenerate the session ID to prevent session fixation.
        session_regenerate_id(true);

        // Store the user's ID in the session.
        $_SESSION['user_id'] = $user['id'];

        // Store the user's name in the session.
        $_SESSION['user_name'] = $user['name'];

        // Store the user's email in the session.
        $_SESSION['user_email'] = $user['email'];

        // Store the user's role in the session.
        $_SESSION['user_role'] = $user['role'];


        // Check the user's role and select the appropriate dashboard.
        if ($user['role'] === 'admin') {

            // Send administrators to the admin dashboard.
            header('Location: ../admin/dashboard.php');

        } elseif ($user['role'] === 'guide') {

            // Send guides to the guide dashboard.
            header('Location: ../guide/dashboard.php');

        } else {

            // Send tourists to the general dashboard.
            header('Location: dashboard.php');
        }

        // Stop executing the remaining code after redirection.
        exit;


    } else {

        // Display a general error message for invalid credentials.
        $message = 'Invalid email address or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set the character encoding. -->
    <meta charset="UTF-8">

    <!-- Make the page responsive. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title. -->
    <title>Login | Akwaaba 360</title>

    <!-- Load the shared Akwaaba 360 stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Display the login page heading. -->
    <h1>Welcome Back to Akwaaba 360</h1>

    <!-- Display login feedback safely. -->
    <p><?php echo htmlspecialchars($message); ?></p>

    <!-- Create the login form. -->
    <form method="POST" action="">

        <!-- Collect the user's email address. -->
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required>

        <br><br>

        <!-- Collect the user's password. -->
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>


        <!-- Allow users to remain logged in on this device. -->
        <label for="remember_me">
            <input
                type="checkbox"
                id="remember_me"
                name="remember_me"
                value="1"
            >
            Remember Me
        </label>

        <br><br>

        <!-- Submit the login information. -->
       <!-- Submit the login form using the shared primary button style. -->
<button type="submit" class="btn btn-primary">Login</button>

    </form>

    <!-- Provide a link to the registration page. -->
    <p>
        Don't have an account?
        <a href="register.php">Create an account</a>
    </p>

    <!-- Allow users who forgot their passwords to request a reset. -->
    <p>
        <a href="forgot_password.php">Forgot your password?</a>
    </p>

</body>
</html>
