
<?php

// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the reusable email-sending function.
require_once __DIR__ . '/../includes/mailer.php';

// Initialise a message for displaying feedback.
$message = '';

// Process the form when the user submits an email address.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and clean the submitted email address.
    $email = trim($_POST['email'] ?? '');

    // Check whether the submitted email address is valid.
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // Search for the account and retrieve its ID, name and email.
        $query = $pdo->prepare(
            "SELECT id, name, email FROM users WHERE email = ?"
        );

        // Execute the query using the submitted email.
        $query->execute([$email]);

        // Retrieve the matching account.
        $user = $query->fetch();

        // Check whether an account was found.
        if ($user) {

            // Generate a secure random password reset token.
            $token = bin2hex(random_bytes(32));

            // Hash the token before storing it in the database.
            $tokenHash = hash('sha256', $token);

            // Set the token to expire after 30 minutes.
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 60));

            // Remove previous reset requests for this account.
            $deleteOldTokens = $pdo->prepare(
                "DELETE FROM password_resets WHERE user_id = ?"
            );

            // Execute the deletion using the user's ID.
            $deleteOldTokens->execute([$user['id']]);

            // Prepare a query to save the new reset request.
            $saveToken = $pdo->prepare(
                "INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, ?)"
            );

            // Save the user's ID, hashed token and expiry date.
            $saveToken->execute([
                $user['id'],
                $tokenHash,
                $expiresAt
            ]);

            // Build the reset link for the local Akwaaba 360 project.
            $resetLink = 'http://localhost/akwaaba/auth/reset_password.php?token='
                . urlencode($token);

            // Send the password reset link to the user's email.
            $emailSent = sendPasswordResetEmail(
                $user['email'],
                $user['name'],
                $resetLink
            );

            // Display a general message without revealing account existence.
            $message = 'If an account exists with that email, a password reset link will be sent.';

            // Record email delivery failure for the developer.
            if (!$emailSent) {
                error_log('Could not send a password reset email.');
            }

        } else {

            // Use the same general message when no account is found.
            $message = 'If an account exists with that email, a password reset link will be sent.';
        }

    } else {

        // Display an error when the email address is invalid.
        $message = 'Please enter a valid email address.';
    }
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the password reset form inside a styled content card. -->
<section class="content-card">

    <!-- Display the page heading. -->
    <h1>Forgot Your Password?</h1>

    <!-- Explain what the user needs to do. -->
    <p>Enter your registered email address to request a password reset.</p>

    <!-- Display the result of the password reset request. -->
    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Collect the email address associated with the account. -->
    <form method="POST" action="">

        <!-- Label the email input field. -->
        <label for="email">Email Address:</label>

        <!-- Collect the user's registered email address. -->
        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <!-- Submit the password reset request. -->
        <button type="submit" class="btn btn-primary">
            Request Password Reset
        </button>

    </form>

    <!-- Allow users to return to the login page. -->
    <p>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
