
<?php
// Start a new session or resume an existing session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the central translation system.
require_once __DIR__ . '/../includes/language.php';

// Define the languages available on the login page.
$availableLanguages = [
    'en' => 'English',
    'tw' => 'Twi',
    'fr' => 'Français',
    'es' => 'Español'
];

// Check whether the visitor selected a different language.
if (isset($_GET['language'])) {

    // Retrieve the selected language.
    $selectedPageLanguage = $_GET['language'];

    // Make sure the selected language is supported.
    if (array_key_exists($selectedPageLanguage, $availableLanguages)) {

        // Update the language used on the login page.
        $currentLanguage = $selectedPageLanguage;

        // Save the temporary language preference in the session.
        $_SESSION['preferred_language'] = $selectedPageLanguage;

        // Set the cookie expiry to one year from now.
        $expiry = time() + (365 * 24 * 60 * 60);

        // Save the selected language in the browser cookie.
        setcookie('preferred_language', $selectedPageLanguage, [
            'expires' => $expiry,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Make sure the current language is supported.
if (!array_key_exists($currentLanguage, $availableLanguages)) {
    $currentLanguage = 'en';
}

// Initialise a variable for displaying login messages.
$message = '';

// Preserve the email address after an unsuccessful login.
$email = '';

// Process the login form when it is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect the submitted email and password.
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepare a query to find the user using their email.
    // Also retrieve their saved language preference.
    $query = $pdo->prepare(
        "SELECT id, name, email, password_hash, role, preferred_language
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

        // Retrieve the user's saved language from the database.
        $savedLanguage = $user['preferred_language'] ?? 'en';

        // Use English if the saved language is not supported.
        if (!array_key_exists($savedLanguage, $availableLanguages)) {
            $savedLanguage = 'en';
        }

        // The user's saved database preference takes priority after login.
        $_SESSION['preferred_language'] = $savedLanguage;

        // Update the language cookie to match the saved account preference.
        setcookie('preferred_language', $savedLanguage, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

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

        // Display the translated error message for invalid credentials.
        $message = ak360_translate('invalid_credentials');
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage); ?>">

<head>

    <!-- Set the character encoding. -->
    <meta charset="UTF-8">

    <!-- Make the page responsive. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title using the selected language. -->
    <title>
        <?php echo htmlspecialchars(ak360_translate('login_title')); ?>
        | Akwaaba 360
    </title>

    <!-- Load the shared Akwaaba 360 stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Load the selected fonts. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>

        /* Apply consistent box sizing to all page elements. */
        * {
            box-sizing: border-box;
        }

        /* Style the overall login page using the agreed cream background. */
        body {
            margin: 0;
            min-height: 100vh;
            background: #F7F6F0;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 18px;
            color: #252525;
        }

        /* Centre the login card within the page. */
        .login-wrapper {
            width: 100%;
            max-width: 460px;
        }

        /* Style the brand name above the login card. */
        .login-brand {
            text-align: center;
            margin-bottom: 22px;
        }

        .login-brand h2 {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            color: #12372A;
            margin: 0;
        }

        .login-brand p {
            color: #555F58;
            margin-top: 7px;
            font-size: 14px;
        }

        /* Style the main login card. */
        .login-card {
            background: #FFFFFF;
            padding: 38px;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(18, 55, 42, 0.09);
            border: 1px solid #E3E9E4;
        }

        /* Style the main login heading. */
        .login-card h1 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: 29px;
            color: #12372A;
            margin: 0 0 10px;
        }

        /* Style the introductory text. */
        .login-description {
            text-align: center;
            color: #626B64;
            font-size: 14px;
            margin-bottom: 27px;
        }

        /* Style the language selector area. */
        .language-selector {
            margin-bottom: 25px;
        }

        .language-selector label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #12372A;
        }

        /* Style the language dropdown. */
        .language-selector select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #E3E9E4;
            border-radius: 8px;
            background: #F7F6F0;
            color: #12372A;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        /* Add a visible focus effect to the language selector. */
        .language-selector select:focus {
            outline: 2px solid #2E8B70;
            border-color: #2E8B70;
        }

        /* Separate each input field from the next. */
        .form-group {
            margin-bottom: 20px;
        }

        /* Style the input labels. */
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #12372A;
        }

        /* Style the input fields. */
        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #E3E9E4;
            border-radius: 8px;
            background: #F7F6F0;
            font-family: inherit;
            font-size: 14px;
            color: #252525;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* Highlight input fields when users interact with them. */
        .form-group input:focus {
            outline: none;
            border-color: #2E8B70;
            box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.13);
            background: #FFFFFF;
        }

        /* Position the password visibility button inside the field. */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 55px;
        }

        /* Style the eye icon button. */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #2E8B70;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .toggle-password:hover {
            background: #E5F2EB;
            color: #12372A;
        }

        .toggle-password:focus-visible {
            outline: 2px solid #2E8B70;
        }

        .toggle-password svg {
            width: 21px;
            height: 21px;
        }

        /* Hide the eye-off icon when the password is hidden. */
        .eye-off-icon {
            display: none;
        }

        /* Align the Remember Me checkbox and its text in one row. */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin: 5px 0 23px;
            width: 100%;
        }

        /* Keep the checkbox at its natural size and vertically centred. */
        .remember-row input[type="checkbox"] {
            width: 17px;
            height: 17px;
            margin: 0;
            padding: 0;
            flex: 0 0 17px;
            accent-color: #2E8B70;
            cursor: pointer;
        }

        /* Prevent the Remember Me text from wrapping or moving out of line. */
        .remember-row label {
            display: inline-flex;
            align-items: center;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 17px;
            font-weight: 500;
            color: #252525;
            cursor: pointer;
        }

        /* Style the login button using the primary forest green. */
        .login-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #12372A;
            color: #FFFFFF;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }

        /* Add an interactive hover effect to the login button. */
        .login-submit:hover {
            background: #0B261D;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(18, 55, 42, 0.2);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        /* Style the login error message. */
        .login-error {
            background: #FCEDEA;
            color: #9E3025;
            border: 1px solid #E8B9AE;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Style the links below the login form. */
        .login-links {
            text-align: center;
            margin-top: 23px;
            font-size: 13px;
            color: #626B64;
        }

        .login-links p {
            margin: 12px 0;
        }

        .login-links a {
            color: #2E8B70;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-links a:hover {
            color: #12372A;
            text-decoration: underline;
        }

        /* Style the bottom brand message. */
        .login-footer {
            text-align: center;
            font-size: 12px;
            color: #626B64;
            margin-top: 22px;
        }

        /* Use the gold accent for a subtle decorative brand detail. */
        .login-brand::after {
            content: "";
            display: block;
            width: 48px;
            height: 3px;
            background: #D9A441;
            margin: 12px auto 0;
            border-radius: 3px;
        }

        /* Adjust the card for smaller screens. */
        @media (max-width: 480px) {

            .login-card {
                padding: 27px 21px;
            }

            .login-card h1 {
                font-size: 25px;
            }

            .login-brand h2 {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>

    <!-- Centre the entire login interface. -->
    <div class="login-wrapper">

        <!-- Display the Akwaaba 360 brand above the form. -->
        <div class="login-brand">
            <h2>Akwaaba 360</h2>

            <!-- Display the tagline in the selected language. -->
            <p>
                <?php echo htmlspecialchars(ak360_translate('login_tagline')); ?>
            </p>
        </div>

        <!-- Display the main login card. -->
        <div class="login-card">

            <!-- Display the translated login heading. -->
            <h1>
                <?php echo htmlspecialchars(ak360_translate('login_title')); ?>
            </h1>

            <!-- Display the translated introduction. -->
            <p class="login-description">
                <?php echo htmlspecialchars(ak360_translate('login_description')); ?>
            </p>

            <!-- Allow visitors to select a language before logging in. -->
            <div class="language-selector">

                <!-- Display the translated language label. -->
                <label for="page-language">
                    <span aria-hidden="true">🌐</span>
                    <?php echo htmlspecialchars(ak360_translate('language_label')); ?>
                </label>

                <!-- Submit the selected language using a GET request. -->
                <form method="GET" action="">

                    <select
                        id="page-language"
                        name="language"
                        onchange="this.form.submit()"
                        aria-label="<?php echo htmlspecialchars(ak360_translate('language_label')); ?>"
                    >

                        <?php foreach ($availableLanguages as $code => $name): ?>

                            <option
                                value="<?php echo htmlspecialchars($code); ?>"
                                <?php echo ($currentLanguage === $code) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($name); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </form>

            </div>

            <!-- Display login feedback safely. -->
            <?php if ($message !== ''): ?>

                <div class="login-error" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <!-- Create the login form. -->
            <form method="POST" action="">

                <!-- Collect the user's email address. -->
                <div class="form-group">

                    <label for="email">
                        <?php echo htmlspecialchars(ak360_translate('email_address')); ?>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="<?php echo htmlspecialchars(ak360_translate('email_placeholder')); ?>"
                        autocomplete="email"
                        required
                    >

                </div>

                <!-- Collect the user's password. -->
                <div class="form-group">

                    <label for="password">
                        <?php echo htmlspecialchars(ak360_translate('password')); ?>
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="<?php echo htmlspecialchars(ak360_translate('password_placeholder')); ?>"
                            autocomplete="current-password"
                            required
                        >

                        <!-- Allow users to show or hide their password. -->
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="<?php echo htmlspecialchars(ak360_translate('show_password')); ?>"
                            aria-pressed="false"
                            title="<?php echo htmlspecialchars(ak360_translate('show_password')); ?>"
                        >

                            <!-- Eye icon displayed when the password is hidden. -->
                            <svg
                                class="eye-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>

                            <!-- Eye-off icon displayed when the password is visible. -->
                            <svg
                                class="eye-off-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M3 3l18 18"/>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-4 4.8"/>
                                <path d="M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"/>
                            </svg>

                        </button>

                    </div>

                </div>

                <!-- Keep the Remember Me checkbox and text together. -->
                <div class="remember-row">

                    <input
                        type="checkbox"
                        id="remember_me"
                        name="remember_me"
                        value="1"
                    >

                    <label for="remember_me">
                        <?php echo htmlspecialchars(ak360_translate('remember_me')); ?>
                    </label>

                </div>

                <!-- Submit the login form. -->
                <button type="submit" class="login-submit">
                    <?php echo htmlspecialchars(ak360_translate('login_button')); ?>
                </button>

            </form>

            <!-- Provide registration and password recovery links. -->
            <div class="login-links">

                <p>
                    <?php echo htmlspecialchars(ak360_translate('no_account')); ?>

                    <a href="register.php">
                        <?php echo htmlspecialchars(ak360_translate('create_account_link')); ?>
                    </a>
                </p>

                <p>
                    <a href="forgot_password.php">
                        <?php echo htmlspecialchars(ak360_translate('forgot_password')); ?>
                    </a>
                </p>

            </div>

        </div>

        <!-- Display the translated brand message below the card. -->
        <div class="login-footer">
            <?php echo htmlspecialchars(ak360_translate('login_footer')); ?>
        </div>

    </div>

    <script>

        // Get the password input field.
        const passwordInput = document.getElementById('password');

        // Get the password visibility button.
        const togglePasswordButton = document.getElementById('togglePassword');

        // Get the two password visibility icons.
        const eyeIcon = document.querySelector('.eye-icon');
        const eyeOffIcon = document.querySelector('.eye-off-icon');

        // Store the translated accessibility labels from PHP.
        const showPasswordText = <?php echo json_encode(ak360_translate('show_password')); ?>;
        const hidePasswordText = <?php echo json_encode(ak360_translate('hide_password')); ?>;

        // Toggle password visibility when the eye button is clicked.
        togglePasswordButton.addEventListener('click', function () {

            // Check whether the password is currently hidden.
            const isPasswordHidden = passwordInput.type === 'password';

            // Show or hide the password.
            passwordInput.type = isPasswordHidden ? 'text' : 'password';

            // Show the appropriate eye icon.
            eyeIcon.style.display = isPasswordHidden ? 'none' : 'block';
            eyeOffIcon.style.display = isPasswordHidden ? 'block' : 'none';

            // Update the button's accessibility description in the selected language.
            togglePasswordButton.setAttribute(
                'aria-label',
                isPasswordHidden ? hidePasswordText : showPasswordText
            );

            // Update the button's title in the selected language.
            togglePasswordButton.setAttribute(
                'title',
                isPasswordHidden ? hidePasswordText : showPasswordText
            );

            // Update the button's pressed state.
            togglePasswordButton.setAttribute(
                'aria-pressed',
                isPasswordHidden ? 'true' : 'false'
            );

        });

    </script>

</body>
</html>
