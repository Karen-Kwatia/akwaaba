
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

/**
 * Validate a redirect destination.
 *
 * Only safe application-relative paths are accepted.
 * External URLs and directory traversal attempts are rejected.
 */
function getSafeLoginRedirect($destination)
{
    // Reject missing, non-string or excessively long destinations.
    if (!is_string($destination) || $destination === '' || strlen($destination) > 2048) {
        return null;
    }

    // Reject control characters and backslashes.
    if (preg_match('/[\x00-\x1F\x7F\\\\]/', $destination)) {
        return null;
    }

    // Parse the supplied destination.
    $parts = parse_url($destination);

    // Reject invalid URLs.
    if ($parts === false) {
        return null;
    }

    // Reject external URLs and protocol-relative URLs.
    if (
        isset($parts['scheme']) ||
        isset($parts['host']) ||
        isset($parts['user']) ||
        isset($parts['pass']) ||
        isset($parts['port']) ||
        isset($parts['fragment'])
    ) {
        return null;
    }

    // Retrieve the destination path.
    $path = $parts['path'] ?? '';

    // Only allow non-empty relative paths.
    if (
        $path === '' ||
        str_starts_with($path, '/') ||
        str_contains($path, '//')
    ) {
        return null;
    }

    // Decode the path before checking for directory traversal.
    $decodedPath = rawurldecode($path);

    // Reject attempts to navigate outside the application directory.
    foreach (explode('/', $decodedPath) as $segment) {
        if ($segment === '..' || $segment === '.') {
            return null;
        }
    }

    // Allow only ordinary application path characters.
    if (!preg_match('/^[A-Za-z0-9._\/-]+$/', $path)) {
        return null;
    }

    // Prevent redirecting back to the login page itself.
    if (strtolower($path) === 'auth/login.php') {
        return null;
    }

    // Return the validated destination, including its query string.
    return $destination;
}

// Get the requested redirect from the current request.
$requestedRedirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';

// Validate the requested destination.
$redirectDestination = getSafeLoginRedirect($requestedRedirect);

// Prepare a safe redirect query string for related authentication links.
$redirectQuery = $redirectDestination !== null
    ? '?redirect=' . rawurlencode($redirectDestination)
    : '';

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

        // If a valid destination was supplied, return the user there.
        if ($redirectDestination !== null) {
            header('Location: ../' . $redirectDestination);

        } elseif ($user['role'] === 'admin') {
            header('Location: ../admin/dashboard.php');

        } elseif ($user['role'] === 'guide') {
            header('Location: ../guide/dashboard.php');

        } else {
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

    <!-- Set the character encoding and responsive viewport. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title using the selected language. -->
    <title>
        <?php echo htmlspecialchars(ak360_translate('login_title')); ?>
        | Akwaaba 360
    </title>

    <!-- Load the shared website stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Load the fonts used throughout the Akwaaba 360 interface. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>

        /* Use consistent sizing across all login page elements. */
        * {
            box-sizing: border-box;
        }

        /* Establish the page background and default typography. */
        body {
            margin: 0;
            min-height: 100vh;
            background: #F7F6F0;
            font-family: 'DM Sans', sans-serif;
            color: #252525;
        }

        /* Create the two-column login page layout. */
        .login-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(420px, 0.92fr);
            min-height: 100vh;
        }

        /* Keep the photographic panel separate from the form panel. */
        .login-visual {
            position: relative;
            min-height: 700px;
            overflow: hidden;
            background: #0B261D;
            color: #FFFFFF;
        }

        /* Make each photograph fill the entire left panel. */
        .login-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.07);
            transition: opacity 1.8s ease, transform 8s ease;
        }

        /* Reveal the active photograph with a gentle zoom effect. */
        .login-slide.is-active {
            opacity: 1;
            transform: scale(1);
        }

        /* Add a dark overlay so the white text remains readable. */
        .login-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(8, 24, 17, .55), transparent 35%),
                linear-gradient(90deg, rgba(8, 24, 17, .48), rgba(8, 24, 17, .08)),
                linear-gradient(0deg, rgba(8, 24, 17, .78), transparent 65%);
            z-index: 1;
        }

        /* Position the brand at the top of the photographic panel. */
        .login-visual-brand {
            position: absolute;
            z-index: 2;
            top: 42px;
            left: clamp(25px, 5vw, 75px);
            display: flex;
            align-items: center;
            gap: 13px;
            color: #FFFFFF;
            text-decoration: none;
        }

        /* Create a small decorative brand symbol. */
        .brand-symbol {
            width: 42px;
            height: 42px;
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #D9A441;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
        }

        /* Style the brand name. */
        .login-visual-brand strong {
            display: block;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: .02em;
        }

        /* Style the small brand descriptor. */
        .login-visual-brand small {
            display: block;
            margin-top: 2px;
            color: #E5E2D8;
            font-size: 10px;
            letter-spacing: .19em;
            text-transform: uppercase;
        }

        /* Position the editorial welcome text near the bottom. */
        .login-visual-content {
            position: absolute;
            z-index: 2;
            left: clamp(25px, 5vw, 75px);
            right: 35px;
            bottom: 105px;
            max-width: 620px;
        }

        /* Style the small introductory label. */
        .visual-eyebrow {
            margin: 0 0 20px;
            color: #E8D5A9;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .3em;
            text-transform: uppercase;
        }

        /* Give the welcome heading an editorial travel-magazine appearance. */
        .login-visual-content h1 {
            margin: 0;
            max-width: 620px;
            font-family: 'Playfair Display', serif;
            font-size: clamp(42px, 5.2vw, 76px);
            font-weight: 500;
            line-height: 1.08;
            letter-spacing: -.025em;
            color: #FFFFFF;
        }

        /* Use gold for the important word in the heading. */
        .login-visual-content h1 span {
            color: #D9A441;
            font-style: italic;
        }

        /* Style the supporting welcome message. */
        .visual-description {
            max-width: 430px;
            margin: 22px 0 0;
            color: #E8E5DC;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.8;
        }

        /* Display a small line above the destination caption. */
        .visual-divider {
            width: 48px;
            height: 2px;
            margin: 28px 0 16px;
            background: #D9A441;
        }

        /* Style the active photograph caption. */
        .visual-caption {
            margin: 0;
            color: #F0E7D5;
            font-size: 11px;
            letter-spacing: .19em;
            text-transform: uppercase;
        }

        /* Add a small location indicator at the bottom. */
        .visual-location {
            position: absolute;
            z-index: 2;
            right: clamp(25px, 5vw, 75px);
            bottom: 38px;
            color: rgba(255,255,255,.75);
            font-size: 10px;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        /* Keep the login form area vertically centred. */
        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px clamp(25px, 5vw, 85px);
            background: #F7F6F0;
        }

        /* Control the maximum width of the form. */
        .login-content {
            width: 100%;
            max-width: 440px;
        }

        /* Display a small brand link above the form on the form side. */
        .mobile-brand {
            display: none;
        }

        /* Style the top introduction above the form. */
        .login-heading {
            margin-bottom: 30px;
        }

        .login-heading .eyebrow {
            display: block;
            margin: 0 0 12px;
            color: #2E8B70;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        /* Style the main login heading. */
        .login-heading h2 {
            margin: 0;
            color: #12372A;
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 3vw, 42px);
            font-weight: 600;
            line-height: 1.2;
        }

        /* Style the introductory text below the heading. */
        .login-description {
            margin: 12px 0 0;
            color: #626B64;
            font-size: 14px;
            line-height: 1.7;
        }

        /* Keep the language label and dropdown perfectly aligned. */
        .language-selector {
            display: flex !important;
            flex-direction: row !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 10px !important;
            width: 100%;
            margin: 0 0 27px !important;
            padding: 0 !important;
        }

        /* Prevent the language label from stretching or moving vertically. */
        .language-selector > label {
            display: inline-flex !important;
            align-items: center !important;
            flex: 0 0 auto !important;
            width: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            color: #626B64;
            font-size: 12px;
            font-weight: 600;
            line-height: 38px !important;
            white-space: nowrap;
        }

        /* Remove unwanted spacing and layout rules from the language form. */
        .language-selector form {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            flex: 0 0 auto !important;
            width: auto !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0 !important;
        }

        /* Prevent hidden form fields from affecting alignment. */
        .language-selector form input[type="hidden"] {
            display: none !important;
        }

        /* Style the language dropdown. */
        .language-selector select {
            display: block !important;
            flex: 0 0 auto !important;
            width: auto !important;
            max-width: 145px;
            height: 38px !important;
            min-height: 38px !important;
            margin: 0 !important;
            padding: 0 30px 0 11px !important;
            border: 1px solid #DCE4DC;
            border-radius: 7px;
            background: #FFFFFF;
            color: #12372A;
            font-family: inherit;
            font-size: 12px;
            line-height: normal !important;
            cursor: pointer;
        }

        /* Make the language selector accessible by keyboard. */
        .language-selector select:focus-visible {
            outline: 2px solid #2E8B70;
            outline-offset: 2px;
        }

        /* Separate each input group from the next. */
        .form-group {
            margin-bottom: 21px;
        }

        /* Style the input labels. */
        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #12372A;
            font-size: 13px;
            font-weight: 700;
        }

        /* Style the email and password input fields. */
        .form-group input {
            width: 100%;
            min-height: 49px;
            padding: 13px 15px;
            border: 1px solid #DCE4DC;
            border-radius: 8px;
            background: #FFFFFF;
            color: #252525;
            font-family: inherit;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
        }

        /* Highlight input fields when users interact with them. */
        .form-group input:focus {
            outline: none;
            border-color: #2E8B70;
            box-shadow: 0 0 0 3px rgba(46, 139, 112, .12);
        }

        /* Give placeholder text a softer appearance. */
        .form-group input::placeholder {
            color: #A0A79F;
        }

        /* Position the password visibility button inside the field. */
        .password-wrapper {
            position: relative;
        }

        /* Leave enough space for the eye icon. */
        .password-wrapper input {
            padding-right: 55px;
        }

        /* Style the eye icon button. */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 7px;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #2E8B70;
            cursor: pointer;
        }

        .toggle-password:hover {
            background: #E5F2EB;
            color: #12372A;
        }

        .toggle-password:focus-visible {
            outline: 2px solid #2E8B70;
            outline-offset: 2px;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
        }

        /* Hide the eye-off icon until the password is visible. */
        .eye-off-icon {
            display: none;
        }

        /* Align Remember Me and Forgot Password in one row. */
        .remember-row {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            width: 100%;
            margin: 4px 0 25px !important;
            padding: 0 !important;
        }

        /* Keep the checkbox and its text together without stretching. */
        .remember-option {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            flex: 0 0 auto !important;
            width: auto !important;
            gap: 9px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Override shared input rules that could stretch the checkbox. */
        .remember-option input[type="checkbox"] {
            display: block !important;
            flex: 0 0 16px !important;
            width: 16px !important;
            min-width: 16px !important;
            max-width: 16px !important;
            height: 16px !important;
            min-height: 16px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 1px solid #AEB9AF;
            border-radius: 3px;
            accent-color: #2E8B70;
            appearance: auto;
            -webkit-appearance: checkbox;
            cursor: pointer;
        }

        /* Keep the Remember Me text vertically centred beside the checkbox. */
        .remember-option label {
            display: inline-block !important;
            flex: 0 0 auto !important;
            width: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            color: #555F58;
            font-size: 12px;
            font-weight: 400;
            line-height: 16px !important;
            white-space: nowrap;
            cursor: pointer;
        }

        /* Style the forgotten-password link. */
        .forgot-link {
            display: inline-block;
            flex: 0 0 auto;
            margin: 0;
            padding: 0;
            color: #2E8B70;
            font-size: 12px;
            font-weight: 700;
            line-height: 16px;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #12372A;
            text-decoration: underline;
        }

        /* Style the main login button. */
        .login-submit {
            width: 100%;
            min-height: 51px;
            padding: 14px 18px;
            border: 1px solid #12372A;
            border-radius: 8px;
            background: #12372A;
            color: #FFFFFF;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        /* Give the login button a restrained hover effect. */
        .login-submit:hover {
            background: #0B261D;
            box-shadow: 0 7px 18px rgba(18, 55, 42, .16);
            transform: translateY(-1px);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        .login-submit:focus-visible {
            outline: 3px solid rgba(46, 139, 112, .4);
            outline-offset: 3px;
        }

        /* Display invalid login feedback clearly. */
        .login-error {
            margin-bottom: 21px;
            padding: 13px 15px;
            border: 1px solid #E8B9AE;
            border-radius: 8px;
            background: #FCEDEA;
            color: #9E3025;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Separate the account links from the login button. */
        .login-links {
            margin-top: 27px;
            text-align: center;
            color: #626B64;
            font-size: 13px;
        }

        .login-links p {
            margin: 10px 0;
        }

        .login-links a {
            color: #2E8B70;
            font-weight: 700;
            text-decoration: none;
        }

        .login-links a:hover {
            color: #12372A;
            text-decoration: underline;
        }

        /* Add a subtle footer below the form. */
        .login-footer {
            margin-top: 38px;
            padding-top: 19px;
            border-top: 1px solid #E3E9E4;
            color: #858D85;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
        }

        /* Adjust the layout for tablets and smaller laptops. */
        @media (max-width: 950px) {
            .login-layout {
                grid-template-columns: minmax(0, 1fr) minmax(380px, .95fr);
            }

            .login-panel {
                padding: 38px 28px;
            }

            .login-visual-content h1 {
                font-size: clamp(40px, 5vw, 58px);
            }
        }

        /* Stack the panels on smaller screens. */
        @media (max-width: 720px) {
            .login-layout {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            /* Keep the photograph as a shorter welcome banner. */
            .login-visual {
                min-height: 300px;
                height: 36vh;
                max-height: 420px;
            }

            .login-visual-brand {
                top: 22px;
                left: 23px;
            }

            .login-visual-content {
                left: 24px;
                right: 24px;
                bottom: 27px;
            }

            .login-visual-content h1 {
                max-width: 500px;
                font-size: clamp(34px, 8vw, 49px);
            }

            .visual-eyebrow {
                margin-bottom: 10px;
                font-size: 9px;
            }

            .visual-description {
                display: none;
            }

            .visual-divider {
                margin: 14px 0 9px;
            }

            .visual-caption {
                font-size: 9px;
            }

            .visual-location {
                display: none;
            }

            .login-panel {
                flex: 1;
                padding: 38px 23px 30px;
            }

            .login-content {
                max-width: 460px;
            }

            .login-heading h2 {
                font-size: 34px;
            }

            .login-footer {
                margin-top: 27px;
            }
        }

        /* Make the form comfortable on very narrow phones. */
        @media (max-width: 380px) {
            .login-panel {
                padding-right: 17px;
                padding-left: 17px;
            }

            .login-heading h2 {
                font-size: 30px;
            }

            .remember-row {
                gap: 8px !important;
            }

            .remember-option {
                gap: 6px !important;
            }

            .remember-option label,
            .forgot-link {
                font-size: 11px;
            }

            .forgot-link {
                text-align: right;
            }
        }

        /* Respect visitors who prefer reduced motion. */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }

    </style>

</head>

<body>

    <!-- Main two-column login page. -->
    <main class="login-layout">

        <!-- LEFT SIDE: Rotating Ghanaian destination photographs. -->
        <section class="login-visual" aria-label="Discover Ghana">

            <!-- Load the existing homepage photographs as the background slides. -->
            <div
                class="login-slide is-active"
                data-caption="Cape Coast Castle · Central Region"
                style="background-image: url('/akwaaba/images/home/cc_hero.jpg');"
            ></div>

            <div
                class="login-slide"
                data-caption="Wli Waterfalls · Volta Region"
                style="background-image: url('/akwaaba/images/home/wl_hero.jpg');"
            ></div>

            <div
                class="login-slide"
                data-caption="Kakum National Park · Central Region"
                style="background-image: url('/akwaaba/images/home/ka_hero.jpg');"
            ></div>

            <div
                class="login-slide"
                data-caption="Nzulezo Stilt Village · Western Region"
                style="background-image: url('/akwaaba/images/home/nz_hero.jpg');"
            ></div>

            <!-- Display the Akwaaba 360 brand over the photographs. -->
            <a href="/akwaaba/index.php" class="login-visual-brand">

                <span class="brand-symbol" aria-hidden="true">A</span>

                <span>
                    <strong>Akwaaba 360</strong>
                    <small>Discover Ghana</small>
                </span>

            </a>

            <!-- Display the editorial welcome message. -->
            <div class="login-visual-content">

                <p class="visual-eyebrow">Your journey begins here</p>

                <h1>
                    Ghana is waiting.
                    <span>Come explore.</span>
                </h1>

                <p class="visual-description">
                    Discover remarkable places, meet local guides and experience
                    the stories that make Ghana unforgettable.
                </p>

                <div class="visual-divider"></div>

                <!-- Update this caption whenever the background photograph changes. -->
                <p class="visual-caption" id="loginPhotoCaption">
                    Cape Coast Castle · Central Region
                </p>

            </div>

            <!-- Display a small location signature. -->
            <p class="visual-location">Experience Ghana · Akwaaba 360</p>

        </section>

        <!-- RIGHT SIDE: Login form. -->
        <section class="login-panel">

            <div class="login-content">

                <!-- Brand link for smaller screens. -->
                <a href="/akwaaba/index.php" class="mobile-brand">
                    Akwaaba 360
                </a>

                <!-- Display the translated login heading and introduction. -->
                <div class="login-heading">

                    <span class="eyebrow">Welcome back</span>

                    <h2>
                        <?php echo htmlspecialchars(ak360_translate('login_title')); ?>
                    </h2>

                    <p class="login-description">
                        <?php echo htmlspecialchars(ak360_translate('login_description')); ?>
                    </p>

                </div>

                <!-- Allow visitors to select their preferred language. -->
                <div class="language-selector">

                    <label for="page-language">
                        <?php echo htmlspecialchars(ak360_translate('language_label')); ?>
                    </label>

                    <!-- Changing the language reloads the login page using GET. -->
                    <form method="GET" action="">

                        <!-- Preserve the requested destination when changing language. -->
                        <input
                            type="hidden"
                            name="redirect"
                            value="<?php echo htmlspecialchars($redirectDestination ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >

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

                <!-- Main login form. -->
                <form method="POST" action="">

                    <!-- Preserve the validated destination after form submission. -->
                    <input
                        type="hidden"
                        name="redirect"
                        value="<?php echo htmlspecialchars($redirectDestination ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <!-- Collect the user's email address. -->
                    <div class="form-group">

                        <label for="email">
                            <?php echo htmlspecialchars(ak360_translate('email_address')); ?>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
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

                            <!-- Allow visitors to show or hide their password. -->
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

                    <!-- Keep Remember Me and Forgot Password on one line. -->
                    <div class="remember-row">

                        <div class="remember-option">

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

                        <!-- Preserve the requested destination during password recovery. -->
                        <a
                            href="forgot_password.php<?php echo htmlspecialchars($redirectQuery, ENT_QUOTES, 'UTF-8'); ?>"
                            class="forgot-link"
                        >
                            <?php echo htmlspecialchars(ak360_translate('forgot_password')); ?>
                        </a>

                    </div>

                    <!-- Submit the login form. -->
                    <button type="submit" class="login-submit">
                        <?php echo htmlspecialchars(ak360_translate('login_button')); ?>
                    </button>

                </form>

                <!-- Provide a link for visitors who do not have an account. -->
                <div class="login-links">

                    <p>
                        <?php echo htmlspecialchars(ak360_translate('no_account')); ?>

                        <a href="register.php<?php echo htmlspecialchars($redirectQuery, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(ak360_translate('create_account_link')); ?>
                        </a>
                    </p>

                </div>

                <!-- Display the translated brand message. -->
                <div class="login-footer">
                    <?php echo htmlspecialchars(ak360_translate('login_footer')); ?>
                </div>

            </div>

        </section>

    </main>

    <script>

        // ==========================================
        // PASSWORD VISIBILITY
        // ==========================================

        // Get the password input field.
        const passwordInput = document.getElementById('password');

        // Get the password visibility button.
        const togglePasswordButton = document.getElementById('togglePassword');

        // Get the two password visibility icons.
        const eyeIcon = document.querySelector('.eye-icon');
        const eyeOffIcon = document.querySelector('.eye-off-icon');

        // Store translated accessibility labels from PHP.
        const showPasswordText = <?php echo json_encode(ak360_translate('show_password')); ?>;
        const hidePasswordText = <?php echo json_encode(ak360_translate('hide_password')); ?>;

        // Toggle password visibility when the eye button is clicked.
        togglePasswordButton.addEventListener('click', function () {

            // Check whether the password is currently hidden.
            const isPasswordHidden = passwordInput.type === 'password';

            // Show or hide the password.
            passwordInput.type = isPasswordHidden ? 'text' : 'password';

            // Display the appropriate eye icon.
            eyeIcon.style.display = isPasswordHidden ? 'none' : 'block';
            eyeOffIcon.style.display = isPasswordHidden ? 'block' : 'none';

            // Update the translated accessibility description.
            togglePasswordButton.setAttribute(
                'aria-label',
                isPasswordHidden ? hidePasswordText : showPasswordText
            );

            // Update the button tooltip.
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

        // ==========================================
        // ROTATING DESTINATION PHOTOGRAPHS
        // ==========================================

        // Find all photographs in the left-hand panel.
        const loginSlides = document.querySelectorAll('.login-slide');

        // Find the caption displayed below the welcome message.
        const loginPhotoCaption = document.getElementById('loginPhotoCaption');

        // Check whether the visitor prefers reduced motion.
        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        // Rotate the photographs only when motion is permitted.
        if (loginSlides.length > 1 && !prefersReducedMotion) {

            // Start with the first photograph.
            let currentSlide = 0;

            // Change photographs every seven seconds.
            setInterval(function () {

                // Hide the current photograph.
                loginSlides[currentSlide].classList.remove('is-active');

                // Move to the next photograph.
                currentSlide = (currentSlide + 1) % loginSlides.length;

                // Display the next photograph.
                loginSlides[currentSlide].classList.add('is-active');

                // Update the caption to match the photograph.
                if (loginPhotoCaption) {
                    loginPhotoCaption.textContent =
                        loginSlides[currentSlide].dataset.caption;
                }

            }, 7000);
        }

    </script>

</body>
</html>
