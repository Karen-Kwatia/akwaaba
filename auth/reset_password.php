
<?php
// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Initialise a message for displaying feedback.
$message = '';

// Retrieve the reset token from the URL or submitted form.
$token = $_GET['token'] ?? $_POST['token'] ?? '';

// Initialise the reset request variable.
$resetRequest = null;

// Check whether the token has the expected format.
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {

    // Display an error for an invalid token.
    $message = 'This password reset link is invalid or incomplete.';

} else {

    // Hash the token to compare it with the database record.
    $tokenHash = hash('sha256', $token);

    // Find a matching reset request that has not expired.
    $query = $pdo->prepare(
        "SELECT id, user_id
         FROM password_resets
         WHERE token_hash = ?
         AND expires_at > NOW()"
    );

    // Execute the query using the hashed token.
    $query->execute([$tokenHash]);

    // Retrieve the matching reset request.
    $resetRequest = $query->fetch();

    // Check whether a valid reset request exists.
    if (!$resetRequest) {

        // Reject expired or unknown reset tokens.
        $message = 'This password reset link has expired or is invalid.';

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve the new password entered by the user.
        $password = $_POST['password'] ?? '';

        // Retrieve the password confirmation.
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Check that both passwords match.
        if ($password !== $confirmPassword) {

            // Display an error when the passwords differ.
            $message = 'The passwords do not match.';

        } elseif (strlen($password) < 8) {

            // Require passwords to contain at least eight characters.
            $message = 'Your password must contain at least 8 characters.';

        } else {

            // Securely hash the new password.
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Update the user's password in the database.
            $updatePassword = $pdo->prepare(
                "UPDATE users SET password_hash = ? WHERE id = ?"
            );

            // Execute the update using the new hash and account ID.
            $updatePassword->execute([
                $passwordHash,
                $resetRequest['user_id']
            ]);

            // Remove the reset token so it cannot be reused.
            $deleteToken = $pdo->prepare(
                "DELETE FROM password_resets WHERE id = ?"
            );

            // Execute the deletion using the reset request ID.
            $deleteToken->execute([$resetRequest['id']]);

            // Clear the reset request so the form is no longer displayed.
            $resetRequest = null;

            // Display a success message.
            $message = 'Your password has been reset successfully. You can now log in.';
        }
    }
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the password reset page inside a styled content card. -->
<section class="content-card">

    <!-- Display the page heading. -->
    <h1>Reset Your Password</h1>

    <!-- Display any success or error messages. -->
    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($resetRequest && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>

        <!-- Allow users with valid reset links to enter a new password. -->
        <form method="POST" action="">

            <!-- Preserve the reset token when the form is submitted. -->
            <input
                type="hidden"
                name="token"
                value="<?php echo htmlspecialchars($token); ?>"
            >

            <!-- Collect the user's new password. -->
            <label for="password">New Password:</label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <!-- Collect the password confirmation. -->
            <label for="confirm_password">Confirm New Password:</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <!-- Submit the new password using the shared button style. -->
            <button type="submit" class="btn btn-primary">
                Reset Password
            </button>

        </form>

    <?php endif; ?>

    <!-- Provide a styled link back to the login page. -->
    <p>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
