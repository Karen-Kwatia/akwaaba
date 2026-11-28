
<?php
/*
 * AKWAABA 360
 * Forgot Password Page
 *
 * Allows users to request a secure password reset link.
 *
 * Security features:
 * 1. CSRF protection.
 * 2. Secure random reset tokens.
 * 3. Hashed tokens stored in the database.
 * 4. Reset links expire after 30 minutes.
 * 5. Previous reset tokens are invalidated.
 * 6. General responses prevent account enumeration.
 * 7. Basic session-based request throttling.
 */

// Start or resume the user's session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the reusable email-sending function.
require_once __DIR__ . '/../includes/mailer.php';

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Initialise the message and email variables.
$messageKey = '';
$messageType = '';
$email = '';

// Create a CSRF token if one does not already exist.
if (empty($_SESSION['forgot_password_csrf'])) {
    $_SESSION['forgot_password_csrf'] = bin2hex(random_bytes(32));
}

// Initialise the request history for basic session-based throttling.
if (!isset($_SESSION['password_reset_requests'])) {
    $_SESSION['password_reset_requests'] = [];
}

// Remove request timestamps older than 15 minutes.
$_SESSION['password_reset_requests'] = array_filter(
    $_SESSION['password_reset_requests'],
    function ($timestamp) {
        return $timestamp > time() - 900;
    }
);

// Process the form when the user submits an email address.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and clean the submitted email address.
    $email = trim($_POST['email'] ?? '');

    // Retrieve the submitted CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    // Validate the CSRF token before processing the request.
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['forgot_password_csrf'], $submittedToken)
    ) {

        // Display a general response without processing the request.
        $messageKey = 'password_reset_request_message';
        $messageType = 'info';

    } elseif (count($_SESSION['password_reset_requests']) >= 5) {

        // Limit repeated reset requests from the same session.
        $messageKey = 'forgot_password_rate_limit';
        $messageType = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // Display a translated error for an invalid email address.
        $messageKey = 'forgot_password_invalid_email';
        $messageType = 'error';

    } else {

        // Record this request for session-based throttling.
        $_SESSION['password_reset_requests'][] = time();

        // Use a general response regardless of whether an account exists.
        $messageKey = 'password_reset_request_message';
        $messageType = 'info';

        try {

            // Search for the account using the submitted email address.
            $query = $pdo->prepare(
                "SELECT id, name, email
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            // Execute the query using the submitted email.
            $query->execute([$email]);

            // Retrieve the matching account.
            $user = $query->fetch(PDO::FETCH_ASSOC);

            // Only generate a reset link when an account exists.
            if ($user) {

                // Generate a secure random password reset token.
                $token = bin2hex(random_bytes(32));

                // Hash the token before storing it in the database.
                $tokenHash = hash('sha256', $token);

                // Set the token to expire after 30 minutes.
                $expiresAt = date(
                    'Y-m-d H:i:s',
                    time() + (30 * 60)
                );

                // Start a database transaction for the reset request.
                $pdo->beginTransaction();

                // Remove previous reset requests for this account.
                $deleteOldTokens = $pdo->prepare(
                    "DELETE FROM password_resets
                     WHERE user_id = ?"
                );

                // Execute the deletion using the user's ID.
                $deleteOldTokens->execute([$user['id']]);

                // Prepare a query to save the new reset request.
                $saveToken = $pdo->prepare(
                    "INSERT INTO password_resets
                     (user_id, token_hash, expires_at)
                     VALUES (?, ?, ?)"
                );

                // Save the user's ID, hashed token and expiry date.
                $saveToken->execute([
                    $user['id'],
                    $tokenHash,
                    $expiresAt
                ]);

                // Commit the reset request to the database.
                $pdo->commit();

                // Build the reset link for the local Akwaaba 360 project.
                $resetLink = 'http://localhost/akwaaba/auth/reset_password.php?token='
                    . urlencode($token);

                // Send the password reset link to the user's email.
                $emailSent = sendPasswordResetEmail(
                    $user['email'],
                    $user['name'],
                    $resetLink
                );

                // Record email delivery failure for the developer.
                if (!$emailSent) {
                    error_log(
                        'Akwaaba 360: Could not send a password reset email.'
                    );
                }
            }

        } catch (Throwable $e) {

            // Roll back the database transaction if it is still active.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Record the technical error without showing it to the user.
            error_log(
                'Akwaaba 360 password reset error: ' . $e->getMessage()
            );

            // Keep the same general response to avoid revealing account details.
            $messageKey = 'password_reset_request_message';
            $messageType = 'info';
        }
    }

    // Generate a new CSRF token after processing the submission.
    $_SESSION['forgot_password_csrf'] = bin2hex(random_bytes(32));
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================
   FORGOT PASSWORD PAGE
   ========================================= */

/* Main page wrapper. */
.forgot-page {
    display: flex;
    justify-content: center;
    width: 100%;
    padding: 65px 20px 80px;
    font-family: 'DM Sans', sans-serif;
    color: #252525;
}

/* Main recovery card. */
.forgot-card {
    width: 100%;
    max-width: 590px;
    padding: 43px 45px;
    border: 1px solid #E3E9E4;
    border-radius: 12px;
    background: #FFFFFF;
    box-shadow: 0 12px 35px rgba(18, 55, 42, 0.055);
}

/* Decorative icon container. */
.forgot-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 55px;
    height: 55px;
    margin-bottom: 25px;
    border-radius: 10px;
    background: #E5F2EB;
    color: #12372A;
    font-size: 25px;
}

/* Small introductory label. */
.forgot-eyebrow {
    display: inline-block;
    margin-bottom: 12px;
    color: #2E8B70;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Main heading. */
.forgot-heading {
    margin: 0 0 13px;
    color: #12372A;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(29px, 4vw, 37px);
    font-weight: 600;
    line-height: 1.25;
}

/* Description text. */
.forgot-description {
    margin: 0 0 29px;
    color: #68746D;
    font-size: 13px;
    line-height: 1.9;
}

/* Recovery form. */
.forgot-form {
    display: flex;
    flex-direction: column;
    gap: 11px;
}

/* Email field label. */
.forgot-form label {
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
}

/* Email input. */
.forgot-form input[type="email"] {
    width: 100%;
    height: 49px;
    margin-bottom: 10px;
    padding: 0 14px;
    border: 1px solid #DCE4DC;
    border-radius: 7px;
    outline: none;
    background: #FFFFFF;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

/* Email input focus state. */
.forgot-form input[type="email"]:focus {
    border-color: #2E8B70;
    box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.12);
}

/* Submit button. */
.forgot-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 47px;
    padding: 0 20px;
    border: 1px solid #12372A;
    border-radius: 6px;
    background: #12372A;
    color: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease;
}

/* Submit button hover state. */
.forgot-submit:hover {
    border-color: #2E8B70;
    background: #2E8B70;
}

/* Message container. */
.forgot-message {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-bottom: 24px;
    padding: 14px 15px;
    border-radius: 7px;
    font-size: 12px;
    line-height: 1.7;
}

/* General information message. */
.forgot-message.info {
    border: 1px solid #D7E6DC;
    background: #F2F8F3;
    color: #285B3A;
}

/* Error message. */
.forgot-message.error {
    border: 1px solid #F0D1CC;
    background: #FFF5F3;
    color: #9B3E32;
}

/* Message icon. */
.forgot-message-icon {
    flex-shrink: 0;
    font-size: 16px;
    font-weight: 700;
}

/* Return-to-login section. */
.forgot-footer {
    display: flex;
    justify-content: center;
    margin-top: 27px;
    padding-top: 23px;
    border-top: 1px solid #E8EDE8;
}

/* Return-to-login link. */
.forgot-back {
    color: #2E8B70;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.2s ease;
}

/* Return link hover state. */
.forgot-back:hover {
    color: #12372A;
    text-decoration: underline;
}

/* Responsive mobile layout. */
@media (max-width: 520px) {
    .forgot-page {
        padding: 38px 15px 55px;
    }

    .forgot-card {
        padding: 30px 22px;
    }
}
</style>

<!-- Main password recovery page. -->
<main class="forgot-page">

    <!-- Password recovery card. -->
    <section class="forgot-card">

        <!-- Decorative recovery icon. -->
        <div class="forgot-icon" aria-hidden="true">
            ↗
        </div>

        <!-- Page introduction. -->
        <span class="forgot-eyebrow">
            Akwaaba 360 Account
        </span>

        <h1 class="forgot-heading">
            <?php echo htmlspecialchars(ak360_translate('forgot_password_title')); ?>
        </h1>

        <p class="forgot-description">
            <?php echo htmlspecialchars(ak360_translate('forgot_password_description')); ?>
        </p>

        <!-- Display feedback when available. -->
        <?php if ($messageKey !== ''): ?>

            <div
                class="forgot-message <?php echo htmlspecialchars($messageType); ?>"
                role="status"
                aria-live="polite"
            >

                <span class="forgot-message-icon" aria-hidden="true">
                    <?php echo $messageType === 'error' ? '!' : '✓'; ?>
                </span>

                <span>
                    <?php echo htmlspecialchars(ak360_translate($messageKey)); ?>
                </span>

            </div>

        <?php endif; ?>

        <!-- Collect the email address associated with the account. -->
        <form method="POST" action="" class="forgot-form">

            <!-- CSRF protection token. -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['forgot_password_csrf']); ?>"
            >

            <!-- Email field label. -->
            <label for="email">
                <?php echo htmlspecialchars(ak360_translate('email_address')); ?>
            </label>

            <!-- Registered email address input. -->
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($email); ?>"
                placeholder="Enter your registered email address"
                autocomplete="email"
                maxlength="254"
                required
            >

            <!-- Submit the password reset request. -->
            <button type="submit" class="forgot-submit">
                <?php echo htmlspecialchars(ak360_translate('request_password_reset')); ?>
            </button>

        </form>

        <!-- Allow users to return to the login page. -->
        <div class="forgot-footer">

            <a href="login.php" class="forgot-back">
                ← <?php echo htmlspecialchars(ak360_translate('back_to_login')); ?>
            </a>

        </div>

    </section>

</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
