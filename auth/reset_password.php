
<?php
// =====================================================
// AKWAABA 360
// PASSWORD RESET PAGE
// =====================================================

// Start or resume the user's session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the central language translation system.
require_once __DIR__ . '/../includes/language.php';

// =====================================================
// INITIALISE PAGE VARIABLES
// =====================================================

// Store the translation key for any success or error message.
$messageKey = '';

// Retrieve the reset token from the URL or submitted form.
$token = $_GET['token'] ?? $_POST['token'] ?? '';

// Initialise the reset request.
$resetRequest = null;

// Create a CSRF token if one does not already exist.
if (empty($_SESSION['reset_password_csrf_token'])) {
    $_SESSION['reset_password_csrf_token'] = bin2hex(random_bytes(32));
}

// =====================================================
// VALIDATE THE RESET TOKEN
// =====================================================

// Make sure the token contains exactly 64 lowercase hexadecimal characters.
if (
    !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)
) {

    // Reject invalid or incomplete reset tokens.
    $messageKey = 'reset_invalid_token';

} else {

    // Hash the token before comparing it with the database.
    $tokenHash = hash('sha256', $token);

    // Find a matching reset request that has not expired.
    $query = $pdo->prepare(
        "SELECT id, user_id
         FROM password_resets
         WHERE token_hash = ?
         AND expires_at > NOW()
         LIMIT 1"
    );

    // Execute the query using the hashed token.
    $query->execute([$tokenHash]);

    // Retrieve the matching reset request.
    $resetRequest = $query->fetch(PDO::FETCH_ASSOC);

    // Reject tokens that do not exist or have expired.
    if (!$resetRequest) {

        $messageKey = 'reset_expired_token';

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // =====================================================
        // VERIFY CSRF PROTECTION
        // =====================================================

        // Retrieve the submitted CSRF token.
        $submittedCsrfToken = $_POST['csrf_token'] ?? '';

        // Compare the submitted token with the session token.
        if (
            !is_string($submittedCsrfToken) ||
            !hash_equals(
                $_SESSION['reset_password_csrf_token'],
                $submittedCsrfToken
            )
        ) {

            // Reject requests that fail CSRF validation.
            $messageKey = 'reset_invalid_token';

        } else {

            // =====================================================
            // VALIDATE THE NEW PASSWORD
            // =====================================================

            // Retrieve the new password.
            $password = $_POST['password'] ?? '';

            // Retrieve the password confirmation.
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Check that both passwords match.
            if ($password !== $confirmPassword) {

                $messageKey = 'reset_password_mismatch';

            } elseif (strlen($password) < 8) {

                // Require at least eight characters.
                $messageKey = 'reset_password_length';

            } else {

                // =====================================================
                // UPDATE THE PASSWORD SECURELY
                // =====================================================

                try {

                    // Start a database transaction.
                    $pdo->beginTransaction();

                    // Recheck and lock the reset token.
                    // This prevents simultaneous reuse of the same token.
                    $lockToken = $pdo->prepare(
                        "SELECT id, user_id
                         FROM password_resets
                         WHERE token_hash = ?
                         AND expires_at > NOW()
                         LIMIT 1
                         FOR UPDATE"
                    );

                    // Execute the token lookup.
                    $lockToken->execute([$tokenHash]);

                    // Retrieve the currently valid reset request.
                    $lockedRequest = $lockToken->fetch(PDO::FETCH_ASSOC);

                    // Confirm that the token is still valid.
                    if (!$lockedRequest) {

                        // Cancel the transaction if the token has expired or been used.
                        $pdo->rollBack();

                        $resetRequest = null;
                        $messageKey = 'reset_expired_token';

                    } else {

                        // Securely hash the new password.
                        $passwordHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        // Update the user's password in the database.
                        $updatePassword = $pdo->prepare(
                            "UPDATE users
                             SET password_hash = ?
                             WHERE id = ?"
                        );

                        // Execute the password update.
                        $updatePassword->execute([
                            $passwordHash,
                            $lockedRequest['user_id']
                        ]);

                        // Delete all outstanding password reset tokens
                        // belonging to this user.
                        $deleteTokens = $pdo->prepare(
                            "DELETE FROM password_resets
                             WHERE user_id = ?"
                        );

                        // Execute the token deletion.
                        $deleteTokens->execute([
                            $lockedRequest['user_id']
                        ]);

                        // Commit both database changes together.
                        $pdo->commit();

                        // Remove the reset form after successful completion.
                        $resetRequest = null;

                        // Display the translated success message.
                        $messageKey = 'reset_password_success';

                        // Generate a new CSRF token.
                        $_SESSION['reset_password_csrf_token'] =
                            bin2hex(random_bytes(32));
                    }

                } catch (Throwable $error) {

                    // Roll back incomplete database operations.
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    // Record technical details in the server error log.
                    error_log(
                        'Akwaaba 360 password reset error: ' .
                        $error->getMessage()
                    );

                    // Do not expose database errors to the visitor.
                    $messageKey = 'reset_invalid_token';
                }
            }
        }
    }
}

// =====================================================
// LOAD THE SHARED WEBSITE HEADER
// =====================================================

require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =====================================================
   PASSWORD RESET PAGE DESIGN
   ===================================================== */

.reset-page {
    min-height: 75vh;
    padding: 65px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #F7F6F0;
}

/* Main password reset card. */
.reset-card {
    width: 100%;
    max-width: 510px;
    padding: 42px;
    background: #FFFFFF;
    border: 1px solid #E3E9E4;
    border-radius: 8px;
    box-shadow: 0 12px 35px rgba(18, 55, 42, 0.07);
}

/* Small introductory label. */
.reset-eyebrow {
    margin: 0 0 12px;
    color: #2E8B70;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Main heading. */
.reset-card h1 {
    margin: 0 0 12px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    font-weight: 600;
    line-height: 1.2;
}

/* Introductory description. */
.reset-description {
    margin: 0 0 28px;
    color: #626B65;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    line-height: 1.7;
}

/* Form layout. */
.reset-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Form labels. */
.reset-form label {
    margin-top: 9px;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
}

/* Password field container. */
.reset-password-wrapper {
    position: relative;
    width: 100%;
}

/* Password inputs. */
.reset-form input[type="password"],
.reset-form input[type="text"] {
    width: 100%;
    height: 48px;
    padding: 0 48px 0 14px;
    box-sizing: border-box;
    border: 1px solid #D7E0D9;
    border-radius: 4px;
    background: #FFFFFF;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

/* Highlight the field when selected. */
.reset-form input:focus {
    border-color: #2E8B70;
    box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.12);
}

/* Eye icon button. */
.reset-toggle {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #2E8B70;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}

/* Subtle background when hovering over the eye icon. */
.reset-toggle:hover {
    background: #E5F2EB;
    color: #12372A;
}

/* Eye icon dimensions. */
.reset-toggle svg {
    width: 19px;
    height: 19px;
    display: block;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Visible keyboard focus for accessibility. */
.reset-toggle:focus-visible {
    outline: 2px solid #2E8B70;
    outline-offset: 3px;
}

/* Primary submit button. */
.reset-submit {
    width: 100%;
    min-height: 49px;
    margin-top: 18px;
    padding: 12px 18px;
    border: none;
    border-radius: 4px;
    background: #12372A;
    color: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}

.reset-submit:hover {
    background: #2E8B70;
    transform: translateY(-1px);
}

/* Success and error messages. */
.reset-message {
    margin: 20px 0;
    padding: 13px 15px;
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.6;
}

/* Success message styling. */
.reset-message.success {
    border: 1px solid #B9DCC8;
    background: #E5F2EB;
    color: #12372A;
}

/* Error message styling. */
.reset-message.error {
    border: 1px solid #E8C5C0;
    background: #FBEFEC;
    color: #8B3027;
}

/* Link back to login. */
.reset-back {
    display: inline-block;
    margin-top: 24px;
    color: #2E8B70;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.reset-back:hover {
    color: #12372A;
    text-decoration: underline;
}

/* Responsive adjustments for mobile screens. */
@media (max-width: 600px) {

    .reset-page {
        min-height: auto;
        padding: 35px 15px;
    }

    .reset-card {
        padding: 30px 22px;
    }

    .reset-card h1 {
        font-size: 29px;
    }
}

</style>

<!-- =====================================================
     PASSWORD RESET PAGE CONTENT
     ===================================================== -->

<section class="reset-page">

    <div class="reset-card">

        <!-- Small page introduction. -->
        <p class="reset-eyebrow">AKWAABA 360</p>

        <!-- Display the translated page heading. -->
        <h1>
            <?php echo htmlspecialchars(
                ak360_translate('reset_password_title'),
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h1>

        <!-- Explain the purpose of the page. -->
        <p class="reset-description">
            <?php
            echo htmlspecialchars(
                ak360_translate('forgot_password_description'),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </p>

        <!-- Display success or error messages. -->
        <?php if ($messageKey !== ''): ?>

            <div class="reset-message <?php
                echo $messageKey === 'reset_password_success'
                    ? 'success'
                    : 'error';
            ?>">
                <?php
                echo htmlspecialchars(
                    ak360_translate($messageKey),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </div>

        <?php endif; ?>

        <?php if ($resetRequest): ?>

            <!-- Password reset form. -->
            <form method="POST" action="" class="reset-form">

                <!-- Preserve the reset token. -->
                <input
                    type="hidden"
                    name="token"
                    value="<?php echo htmlspecialchars(
                        $token,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                <!-- Protect the form against CSRF attacks. -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars(
                        $_SESSION['reset_password_csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                <!-- New password label. -->
                <label for="password">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('new_password'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </label>

                <!-- New password input and eye icon. -->
                <div class="reset-password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="reset-toggle"
                        data-target="password"
                        aria-label="<?php echo htmlspecialchars(
                            ak360_translate('show_password'),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        aria-pressed="false"
                        title="<?php echo htmlspecialchars(
                            ak360_translate('show_password'),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >
                        <!-- Eye icon shown while the password is hidden. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>

                </div>

                <!-- Password confirmation label. -->
                <label for="confirm_password">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('confirm_new_password'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </label>

                <!-- Password confirmation input and eye icon. -->
                <div class="reset-password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="reset-toggle"
                        data-target="confirm_password"
                        aria-label="<?php echo htmlspecialchars(
                            ak360_translate('show_password'),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        aria-pressed="false"
                        title="<?php echo htmlspecialchars(
                            ak360_translate('show_password'),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >
                        <!-- Eye icon shown while the password is hidden. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>

                </div>

                <!-- Submit the new password. -->
                <button type="submit" class="reset-submit">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('reset_password_button'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </button>

            </form>

        <?php endif; ?>

        <!-- Provide a link back to the login page. -->
        <a href="login.php" class="reset-back">
            &larr;
            <?php
            echo htmlspecialchars(
                ak360_translate('back_to_login'),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </a>

    </div>

</section>

<!-- =====================================================
     PASSWORD VISIBILITY CONTROLS
     ===================================================== -->

<script>

// Store the eye icon used when the password is hidden.
const resetEyeIcon = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
`;

// Store the crossed-out eye icon used when the password is visible.
const resetEyeOffIcon = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 3l18 18"/>
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
        <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.9"/>
        <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"/>
    </svg>
`;

// Store the translated accessibility labels.
const resetShowText = <?php
    echo json_encode(
        ak360_translate('show_password'),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
?>;

const resetHideText = <?php
    echo json_encode(
        ak360_translate('hide_password'),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
?>;

// Add show/hide functionality to both password fields.
document.querySelectorAll('.reset-toggle').forEach(function (button) {

    // Find the password field controlled by this button.
    const passwordField = document.getElementById(
        button.dataset.target
    );

    // Toggle password visibility when the eye icon is clicked.
    button.addEventListener('click', function () {

        // Check whether the password is currently hidden.
        const isHidden = passwordField.type === 'password';

        // Change the password field's input type.
        passwordField.type = isHidden ? 'text' : 'password';

        // Change the icon to match the new visibility state.
        button.innerHTML = isHidden
            ? resetEyeOffIcon
            : resetEyeIcon;

        // Update the accessible button label.
        button.setAttribute(
            'aria-label',
            isHidden ? resetHideText : resetShowText
        );

        // Update the tooltip.
        button.setAttribute(
            'title',
            isHidden ? resetHideText : resetShowText
        );

        // Update the accessibility pressed state.
        button.setAttribute(
            'aria-pressed',
            isHidden ? 'true' : 'false'
        );
    });

});

</script>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
