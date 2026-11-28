
<?php

// Start the session before accessing session variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the central translation system.
require_once __DIR__ . '/../includes/language.php';

// Define the languages supported by Akwaaba 360.
$availableLanguages = [
    'en' => 'English',
    'tw' => 'Twi',
    'fr' => 'Français',
    'es' => 'Español'
];

// Define the countries available in the registration dropdown.
$countries = [
    'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola',
    'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria',
    'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados',
    'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan',
    'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei',
    'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia',
    'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile',
    'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica',
    'Côte d’Ivoire', 'Croatia', 'Cuba', 'Cyprus', 'Czechia',
    'Democratic Republic of the Congo', 'Denmark', 'Djibouti', 'Dominica',
    'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador',
    'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia',
    'Fiji', 'Finland', 'France', 'Gabon', 'Gambia',
    'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada',
    'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti',
    'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia',
    'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy',
    'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya',
    'Kiribati', 'Kosovo', 'Kuwait', 'Kyrgyzstan', 'Laos',
    'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya',
    'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi',
    'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands',
    'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova',
    'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique',
    'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands',
    'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea',
    'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau',
    'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru',
    'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania',
    'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia',
    'Saint Vincent and the Grenadines', 'Samoa', 'San Marino',
    'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia',
    'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia',
    'Solomon Islands', 'Somalia', 'South Africa', 'South Korea',
    'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname',
    'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan',
    'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga',
    'Trinidad and Tobago', 'Tunisia', 'Türkiye', 'Turkmenistan',
    'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates',
    'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
    'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen',
    'Zambia', 'Zimbabwe'
];

// Define translated password visibility labels.
$passwordVisibilityText = [
    'en' => ['show' => 'Show password', 'hide' => 'Hide password'],
    'tw' => ['show' => 'Kyerɛ ahintasɛm', 'hide' => 'Fa ahintasɛm sie'],
    'fr' => ['show' => 'Afficher le mot de passe', 'hide' => 'Masquer le mot de passe'],
    'es' => ['show' => 'Mostrar contraseña', 'hide' => 'Ocultar contraseña']
];

// Initialise registration feedback and form values.
$message = '';
$name = '';
$email = '';
$country = '';

// Use the current page language as the initial account language.
$language = $currentLanguage;

// Restore the country passed through the language selector.
if (isset($_GET['country'])) {
    $requestedCountry = trim($_GET['country']);

    if (in_array($requestedCountry, $countries, true)) {
        $country = $requestedCountry;
    }
}

// Handle a language change from the registration page.
if (isset($_GET['language'])) {

    $selectedPageLanguage = $_GET['language'];

    if (array_key_exists($selectedPageLanguage, $availableLanguages)) {

        $currentLanguage = $selectedPageLanguage;
        $_SESSION['preferred_language'] = $selectedPageLanguage;

        // Save the selected language in the browser.
        setcookie('preferred_language', $selectedPageLanguage, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Ensure that the page always uses a supported language.
if (!array_key_exists($currentLanguage, $availableLanguages)) {
    $currentLanguage = 'en';
}

// Use the selected page language as the initial account language.
$language = $currentLanguage;

// Process registration when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and clean the submitted values.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $language = $_POST['preferred_language'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Update the page language if the submitted language is supported.
    if (array_key_exists($language, $availableLanguages)) {
        $currentLanguage = $language;
        $_SESSION['preferred_language'] = $language;
    }

    // Check that every required field has been completed.
    if (
        $name === '' ||
        $email === '' ||
        $country === '' ||
        $language === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $message = ak360_translate('registration_required');

    // Reject unsupported language values.
    } elseif (!array_key_exists($language, $availableLanguages)) {
        $message = ak360_translate('invalid_language');

    // Reject countries that are not in the approved list.
    } elseif (!in_array($country, $countries, true)) {
        $message = ak360_translate('registration_required');

    // Make sure both password entries match.
    } elseif ($password !== $confirm_password) {
        $message = ak360_translate('password_mismatch');

    // Validate the email address.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = ak360_translate('invalid_email');

    // Require a minimum password length of eight characters.
    } elseif (strlen($password) < 8) {
        $message = ak360_translate('password_length');

    } else {

        // Check whether the email address is already registered.
        $checkEmail = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $checkEmail->execute([$email]);

        if ($checkEmail->fetch()) {

            $message = ak360_translate('email_exists');

        } else {

            // Securely hash the password before storing it.
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Create a new tourist account.
            $insertUser = $pdo->prepare(
                "INSERT INTO users
                (name, email, password_hash, role, country, preferred_language)
                VALUES (?, ?, ?, 'tourist', ?, ?)"
            );

            $insertUser->execute([
                $name,
                $email,
                $passwordHash,
                $country,
                $language
            ]);

            // Retrieve the ID of the newly created account.
            $newUserId = $pdo->lastInsertId();

            // Regenerate the session ID for security.
            session_regenerate_id(true);

            // Automatically sign in the newly registered tourist.
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'tourist';
            $_SESSION['preferred_language'] = $language;

            // Save the account language in a browser cookie.
            setcookie('preferred_language', $language, [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/akwaaba',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Continue to the existing account preferences page.
            header('Location: preferences.php');
            exit;
        }
    }
}

// Retrieve the correct password visibility labels.
$visibilityLabels = $passwordVisibilityText[$currentLanguage]
    ?? $passwordVisibilityText['en'];

?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage); ?>">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(ak360_translate('register_title')); ?>
        | Akwaaba 360
    </title>

    <!-- Load the shared stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Load the fonts used on the authentication pages. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>

        /* Use consistent sizing throughout the registration page. */
        * {
            box-sizing: border-box;
        }

        /* Establish the page background and typography. */
        body {
            margin: 0;
            min-height: 100vh;
            background: #F7F6F0;
            color: #252525;
            font-family: 'DM Sans', sans-serif;
        }

        /* Create the split-screen registration layout. */
        .register-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(450px, 0.92fr);
            min-height: 100vh;
        }

        /* ==========================================
           LEFT PHOTOGRAPHIC PANEL
        ========================================== */

        .register-visual {
            position: relative;
            min-height: 750px;
            overflow: hidden;
            background: #0B261D;
            color: #FFFFFF;
        }

        /* Make each destination photograph fill the panel. */
        .register-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.07);
            transition: opacity 1.8s ease, transform 8s ease;
        }

        /* Reveal the active photograph. */
        .register-slide.is-active {
            opacity: 1;
            transform: scale(1);
        }

        /* Add layered overlays to keep text readable. */
        .register-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(180deg, rgba(8, 24, 17, .55), transparent 35%),
                linear-gradient(90deg, rgba(8, 24, 17, .48), rgba(8, 24, 17, .08)),
                linear-gradient(0deg, rgba(8, 24, 17, .78), transparent 65%);
        }

        /* Position the brand at the top of the photograph. */
        .register-visual-brand {
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

        /* Create the circular brand symbol. */
        .brand-symbol {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 50%;
            color: #D9A441;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
        }

        .register-visual-brand strong {
            display: block;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
        }

        .register-visual-brand small {
            display: block;
            margin-top: 2px;
            color: #E5E2D8;
            font-size: 10px;
            letter-spacing: .19em;
            text-transform: uppercase;
        }

        /* Position the welcome message near the bottom. */
        .register-visual-content {
            position: absolute;
            z-index: 2;
            left: clamp(25px, 5vw, 75px);
            right: 35px;
            bottom: 105px;
            max-width: 620px;
        }

        .visual-eyebrow {
            margin: 0 0 20px;
            color: #E8D5A9;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .3em;
            text-transform: uppercase;
        }

        .register-visual-content h1 {
            max-width: 620px;
            margin: 0;
            color: #FFFFFF;
            font-family: 'Playfair Display', serif;
            font-size: clamp(42px, 5.2vw, 76px);
            font-weight: 500;
            line-height: 1.08;
            letter-spacing: -.025em;
        }

        .register-visual-content h1 span {
            color: #D9A441;
            font-style: italic;
        }

        .visual-description {
            max-width: 430px;
            margin: 22px 0 0;
            color: #E8E5DC;
            font-size: 14px;
            line-height: 1.8;
        }

        .visual-divider {
            width: 48px;
            height: 2px;
            margin: 28px 0 16px;
            background: #D9A441;
        }

        .visual-caption {
            margin: 0;
            color: #F0E7D5;
            font-size: 11px;
            letter-spacing: .19em;
            text-transform: uppercase;
        }

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

        /* ==========================================
           RIGHT REGISTRATION PANEL
        ========================================== */

        .register-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px clamp(28px, 5vw, 80px);
            background: #F7F6F0;
        }

        /* Keep the form at a comfortable reading width. */
        .register-content {
            width: 100%;
            max-width: 460px;
            padding: 10px 0;
        }

        /* Show this brand link only on smaller screens. */
        .mobile-brand {
            display: none;
        }

        .register-heading {
            margin-bottom: 25px;
        }

        .register-heading .eyebrow {
            display: block;
            margin-bottom: 11px;
            color: #2E8B70;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .register-heading h2 {
            margin: 0;
            color: #12372A;
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 3vw, 42px);
            font-weight: 600;
            line-height: 1.2;
        }

        .register-description {
            margin: 12px 0 0;
            color: #626B64;
            font-size: 14px;
            line-height: 1.7;
        }

        /* Keep the language selector label and dropdown aligned. */
        .language-selector {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            justify-content: start;
            gap: 12px;
            width: 100%;
            margin-bottom: 23px;
        }

        .language-selector label {
            display: block;
            margin: 0;
            padding: 0;
            line-height: 1;
            white-space: nowrap;
            color: #12372A;
            font-size: 12px;
            font-weight: 600;
        }

        .language-selector form {
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .language-selector select {
            display: block;
            width: 100%;
            min-width: 135px;
            height: 38px;
            margin: 0;
            padding: 0 30px 0 11px;
            border: 1px solid #DCE4DC;
            border-radius: 7px;
            background: #FFFFFF;
            color: #12372A;
            font-family: inherit;
            font-size: 12px;
            line-height: normal;
            cursor: pointer;
        }

        /* Display registration validation feedback. */
        .register-error {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #E8B9AE;
            border-radius: 8px;
            background: #FCEDEA;
            color: #9E3025;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Space the registration fields consistently. */
        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #12372A;
            font-size: 12px;
            font-weight: 700;
        }

        /* Style the text fields and country dropdown. */
        .form-group input,
        .form-group select {
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            border: 1px solid #DCE4DC;
            border-radius: 8px;
            background: #FFFFFF;
            color: #252525;
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-group input::placeholder {
            color: #A0A79F;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2E8B70;
            box-shadow: 0 0 0 3px rgba(46, 139, 112, .12);
        }

        .form-group select {
            cursor: pointer;
        }

        /* Position the password visibility button inside its field. */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 55px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
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

        .eye-off-icon {
            display: none;
        }

        /* Style the main registration button. */
        .register-submit {
            width: 100%;
            min-height: 50px;
            margin-top: 5px;
            padding: 13px 18px;
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

        .register-submit:hover {
            background: #0B261D;
            box-shadow: 0 7px 18px rgba(18, 55, 42, .16);
            transform: translateY(-1px);
        }

        .register-submit:active {
            transform: translateY(0);
        }

        .register-submit:focus-visible {
            outline: 3px solid rgba(46, 139, 112, .4);
            outline-offset: 3px;
        }

        /* Style the link for visitors who already have an account. */
        .register-links {
            margin-top: 23px;
            color: #626B64;
            font-size: 13px;
            text-align: center;
        }

        .register-links a {
            color: #2E8B70;
            font-weight: 700;
            text-decoration: none;
        }

        .register-links a:hover {
            color: #12372A;
            text-decoration: underline;
        }

        .register-footer {
            margin-top: 30px;
            padding-top: 17px;
            border-top: 1px solid #E3E9E4;
            color: #858D85;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
        }

        /* Adjust the split layout for smaller laptops and tablets. */
        @media (max-width: 950px) {
            .register-layout {
                grid-template-columns: minmax(0, 1fr) minmax(410px, .95fr);
            }

            .register-panel {
                padding: 35px 27px;
            }

            .register-visual-content h1 {
                font-size: clamp(40px, 5vw, 58px);
            }
        }

        /* Stack the photograph and form on mobile screens. */
        @media (max-width: 720px) {
            .register-layout {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            .register-visual {
                min-height: 300px;
                height: 36vh;
                max-height: 420px;
            }

            .register-visual-brand {
                top: 22px;
                left: 23px;
            }

            .register-visual-content {
                left: 24px;
                right: 24px;
                bottom: 27px;
            }

            .register-visual-content h1 {
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

            .register-panel {
                flex: 1;
                padding: 35px 23px 28px;
            }

            .register-content {
                max-width: 460px;
            }

            .register-heading h2 {
                font-size: 34px;
            }

            .register-footer {
                margin-top: 25px;
            }
        }

        /* Make the registration form comfortable on narrow phones. */
        @media (max-width: 380px) {
            .register-panel {
                padding-right: 17px;
                padding-left: 17px;
            }

            .register-heading h2 {
                font-size: 30px;
            }

            .language-selector {
                gap: 8px;
            }

            .language-selector select {
                min-width: 115px;
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

    <!-- Main two-column registration page. -->
    <main class="register-layout">

        <!-- LEFT SIDE: Rotating photographs of Ghanaian destinations. -->
        <section class="register-visual" aria-label="Discover Ghana">

            <!-- Reuse the existing homepage photographs. -->
            <div
                class="register-slide is-active"
                data-caption="Cape Coast Castle · Central Region"
                style="background-image: url('/akwaaba/images/home/cc_hero.jpg');"
            ></div>

            <div
                class="register-slide"
                data-caption="Wli Waterfalls · Volta Region"
                style="background-image: url('/akwaaba/images/home/wl_hero.jpg');"
            ></div>

            <div
                class="register-slide"
                data-caption="Kakum National Park · Central Region"
                style="background-image: url('/akwaaba/images/home/ka_hero.jpg');"
            ></div>

            <div
                class="register-slide"
                data-caption="Nzulezo Stilt Village · Western Region"
                style="background-image: url('/akwaaba/images/home/nz_hero.jpg');"
            ></div>

            <!-- Display the brand over the photographs. -->
            <a href="/akwaaba/index.php" class="register-visual-brand">
                <span class="brand-symbol" aria-hidden="true">A</span>

                <span>
                    <strong>Akwaaba 360</strong>
                    <small>Discover Ghana</small>
                </span>
            </a>

            <!-- Display the welcome message. -->
            <div class="register-visual-content">

                <p class="visual-eyebrow">A new adventure starts here</p>

                <h1>
                    Discover more.
                    <span>Experience Ghana.</span>
                </h1>

                <p class="visual-description">
                    Create your account and explore Ghana's remarkable
                    destinations, rich heritage and unforgettable experiences.
                </p>

                <div class="visual-divider"></div>

                <p class="visual-caption" id="registerPhotoCaption">
                    Cape Coast Castle · Central Region
                </p>

            </div>

            <p class="visual-location">Experience Ghana · Akwaaba 360</p>

        </section>

        <!-- RIGHT SIDE: Registration form. -->
        <section class="register-panel">

            <div class="register-content">

                <!-- Brand link displayed on smaller screens. -->
                <a href="/akwaaba/index.php" class="mobile-brand">
                    Akwaaba 360
                </a>

                <!-- Registration heading and introduction. -->
                <div class="register-heading">

                    <span class="eyebrow">Join the journey</span>

                    <h2>
                        <?php echo htmlspecialchars(ak360_translate('register_title')); ?>
                    </h2>

                    <p class="register-description">
                        <?php echo htmlspecialchars(ak360_translate('site_description')); ?>
                    </p>

                </div>

                <!-- Language selector. -->
                <div class="language-selector">

                    <label for="page-language">
                        <?php echo htmlspecialchars(ak360_translate('preferred_language')); ?>
                    </label>

                    <!-- Preserve the selected country when changing languages. -->
                    <form method="GET" action="" id="language-form">

                        <input
                            type="hidden"
                            name="country"
                            id="preserved-country"
                            value="<?php echo htmlspecialchars($country, ENT_QUOTES, 'UTF-8'); ?>"
                        >

                        <select
                            id="page-language"
                            name="language"
                            onchange="preserveCountryAndChangeLanguage()"
                            aria-label="<?php echo htmlspecialchars(ak360_translate('preferred_language')); ?>"
                        >

                            <?php foreach ($availableLanguages as $code => $languageName): ?>

                                <option
                                    value="<?php echo htmlspecialchars($code); ?>"
                                    <?php echo $currentLanguage === $code ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($languageName); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </form>

                </div>

                <!-- Display validation feedback when necessary. -->
                <?php if ($message !== ''): ?>

                    <div class="register-error" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>

                <!-- Main registration form. -->
                <form method="POST" action="">

                    <!-- Preserve the selected language during registration. -->
                    <input
                        type="hidden"
                        name="preferred_language"
                        value="<?php echo htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <!-- Full name field. -->
                    <div class="form-group">

                        <label for="name">
                            <?php echo htmlspecialchars(ak360_translate('full_name')); ?>
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                            autocomplete="name"
                            required
                        >

                    </div>

                    <!-- Email address field. -->
                    <div class="form-group">

                        <label for="email">
                            <?php echo htmlspecialchars(ak360_translate('email_address')); ?>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <!-- Country selection field. -->
                    <div class="form-group">

                        <label for="country">
                            <?php echo htmlspecialchars(ak360_translate('country')); ?>
                        </label>

                        <select
                            id="country"
                            name="country"
                            autocomplete="country-name"
                            required
                        >

                            <option
                                value=""
                                disabled
                                <?php echo $country === '' ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars(ak360_translate('country')); ?>
                            </option>

                            <?php foreach ($countries as $countryName): ?>

                                <option
                                    value="<?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $country === $countryName ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($countryName); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Password field with visibility control. -->
                    <div class="form-group">

                        <label for="password">
                            <?php echo htmlspecialchars(ak360_translate('password')); ?>
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                data-password-target="password"
                                aria-label="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                                title="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                                aria-pressed="false"
                            >

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

                    <!-- Confirm password field with its own visibility control. -->
                    <div class="form-group">

                        <label for="confirm_password">
                            <?php echo htmlspecialchars(ak360_translate('confirm_password')); ?>
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                data-password-target="confirm_password"
                                aria-label="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                                title="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                                aria-pressed="false"
                            >

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

                    <!-- Submit the registration form. -->
                    <button type="submit" class="register-submit">
                        <?php echo htmlspecialchars(ak360_translate('registration_button')); ?>
                    </button>

                </form>

                <!-- Link existing users to the login page. -->
                <div class="register-links">

                    <?php echo htmlspecialchars(ak360_translate('no_account')); ?>

                    <a href="login.php">
                        <?php echo htmlspecialchars(ak360_translate('login')); ?>
                    </a>

                </div>

                <!-- Display the website footer message. -->
                <div class="register-footer">
                    <?php echo htmlspecialchars(ak360_translate('site_description')); ?>
                </div>

            </div>

        </section>

    </main>

    <script>

        // ==========================================
        // PRESERVE COUNTRY WHEN CHANGING LANGUAGE
        // ==========================================

        function preserveCountryAndChangeLanguage() {

            // Retrieve the country currently selected in the registration form.
            const countrySelect = document.getElementById('country');

            // Retrieve the hidden country field in the language form.
            const preservedCountry = document.getElementById('preserved-country');

            // Copy the selected country into the hidden field.
            preservedCountry.value = countrySelect ? countrySelect.value : '';

            // Submit the language form.
            document.getElementById('language-form').submit();
        }

        // ==========================================
        // PASSWORD VISIBILITY
        // ==========================================

        // Retrieve all password visibility buttons.
        const passwordToggleButtons = document.querySelectorAll('.toggle-password');

        // Retrieve the translated visibility labels from PHP.
        const showPasswordText = <?php echo json_encode($visibilityLabels['show']); ?>;
        const hidePasswordText = <?php echo json_encode($visibilityLabels['hide']); ?>;

        // Add password visibility functionality to both password fields.
        passwordToggleButtons.forEach(function (button) {

            // Identify the password field controlled by this button.
            const targetId = button.getAttribute('data-password-target');
            const passwordField = document.getElementById(targetId);

            // Retrieve the eye icons inside this button.
            const eyeIcon = button.querySelector('.eye-icon');
            const eyeOffIcon = button.querySelector('.eye-off-icon');

            button.addEventListener('click', function () {

                // Determine whether the password is currently hidden.
                const isHidden = passwordField.type === 'password';

                // Switch between hidden and visible password text.
                passwordField.type = isHidden ? 'text' : 'password';

                // Display the matching eye icon.
                eyeIcon.style.display = isHidden ? 'none' : 'block';
                eyeOffIcon.style.display = isHidden ? 'block' : 'none';

                // Update the accessibility label.
                button.setAttribute(
                    'aria-label',
                    isHidden ? hidePasswordText : showPasswordText
                );

                // Update the tooltip.
                button.setAttribute(
                    'title',
                    isHidden ? hidePasswordText : showPasswordText
                );

                // Update the button's pressed state.
                button.setAttribute(
                    'aria-pressed',
                    isHidden ? 'true' : 'false'
                );

            });

        });

        // ==========================================
        // ROTATING DESTINATION PHOTOGRAPHS
        // ==========================================

        // Retrieve all destination photographs.
        const registerSlides = document.querySelectorAll('.register-slide');

        // Retrieve the photograph caption.
        const registerPhotoCaption = document.getElementById('registerPhotoCaption');

        // Check whether the visitor prefers reduced motion.
        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        // Rotate photographs only when motion is permitted.
        if (registerSlides.length > 1 && !prefersReducedMotion) {

            // Begin with the first photograph.
            let currentSlide = 0;

            // Change photographs every seven seconds.
            setInterval(function () {

                // Hide the current photograph.
                registerSlides[currentSlide].classList.remove('is-active');

                // Move to the next photograph.
                currentSlide = (currentSlide + 1) % registerSlides.length;

                // Display the next photograph.
                registerSlides[currentSlide].classList.add('is-active');

                // Update the caption to match the photograph.
                if (registerPhotoCaption) {
                    registerPhotoCaption.textContent =
                        registerSlides[currentSlide].dataset.caption;
                }

            }, 7000);
        }

    </script>

</body>
</html>
