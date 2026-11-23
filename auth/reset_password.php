
<?php
// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Initialise a message translation key.
$messageKey = '';

// Retrieve the reset token from the URL or submitted form.
$token = $_GET['token'] ?? $_POST['token'] ?? '';

// Initialise the reset request variable.
$resetRequest = null;

// Check whether the token has the expected format.
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {

    // Display an error for an invalid token.
    $messageKey = 'reset_invalid_token';

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
        $messageKey = 'reset_expired_token';

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve the new password entered by the user.
        $password = $_POST['password'] ?? '';

        // Retrieve the password confirmation.
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Check that both passwords match.
        if ($password !== $confirmPassword) {

            // Display an error when the passwords differ.
            $messageKey = 'reset_password_mismatch';

        } elseif (strlen($password) < 8) {

            // Require passwords to contain at least eight characters.
            $messageKey = 'reset_password_length';

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

            // Display the translated success message.
            $messageKey = 'reset_password_success';
        }
    }
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the password reset page inside a styled content card. -->
<section class="content-card">

    <!-- Display the translated page heading. -->
    <h1>
        <?php echo htmlspecialchars(ak360_translate('reset_password_title')); ?>
    </h1>

    <!-- Display any success or error messages. -->
    <?php if ($messageKey !== ''): ?>
        <p>
            <?php echo htmlspecialchars(ak360_translate($messageKey)); ?>
        </p>
    <?php endif; ?>

    <?php if ($resetRequest): ?>

        <!-- Allow users with valid reset links to enter a new password. -->
        <form method="POST" action="">

            <!-- Preserve the reset token when the form is submitted. -->
            <input
                type="hidden"
                name="token"
                value="<?php echo htmlspecialchars($token); ?>"
            >

            <!-- Collect the user's new password. -->
            <label for="password">
                <?php echo htmlspecialchars(ak360_translate('new_password')); ?>
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <!-- Collect the password confirmation. -->
            <label for="confirm_password">
                <?php echo htmlspecialchars(ak360_translate('confirm_new_password')); ?>
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <!-- Submit the new password. -->
            <button type="submit" class="btn btn-primary">
                <?php echo htmlspecialchars(ak360_translate('reset_password_button')); ?>
            </button>

        </form>

    <?php endif; ?>

    <!-- Provide a translated link back to the login page. -->
    <p>
        <a href="login.php" class="btn btn-secondary">
            <?php echo htmlspecialchars(ak360_translate('back_to_login')); ?>
        </a>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
