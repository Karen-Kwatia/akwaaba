
<?php

// Start the session before accessing session variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the reusable PDO database connection.
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

// Initialise the registration feedback message.
$message = '';

// Preserve submitted values when the page reloads.
$name = '';
$email = '';
$country = '';

// Use the current page language as the initial account language.
$language = $currentLanguage;

// Restore the country passed through the language selector.
if (isset($_GET['country'])) {
    $requestedCountry = trim($_GET['country']);

    // Accept the country only if it exists in the approved list.
    if (in_array($requestedCountry, $countries, true)) {
        $country = $requestedCountry;
    }
}

// Check whether the visitor selected a different page language.
if (isset($_GET['language'])) {

    // Retrieve the selected language.
    $selectedPageLanguage = $_GET['language'];

    // Accept only languages supported by the website.
    if (array_key_exists($selectedPageLanguage, $availableLanguages)) {

        // Update the language displayed on the page.
        $currentLanguage = $selectedPageLanguage;

        // Store the selected language in the session.
        $_SESSION['preferred_language'] = $selectedPageLanguage;

        // Save the selected language in a browser cookie.
        setcookie('preferred_language', $selectedPageLanguage, [
            'expires' => time() + (365 * 24 * 60 * 60),
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

// Use the selected page language as the initial account language.
$language = $currentLanguage;

// Process the registration form when submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and clean the submitted form values.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $language = $_POST['preferred_language'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Update the page language to match the selected account language.
    if (array_key_exists($language, $availableLanguages)) {
        $currentLanguage = $language;
        $_SESSION['preferred_language'] = $language;
    }

    // Check that all required fields have been completed.
    if (
        $name === '' ||
        $email === '' ||
        $country === '' ||
        $language === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $message = ak360_translate('registration_required');

    // Check that the selected language is supported.
    } elseif (!array_key_exists($language, $availableLanguages)) {
        $message = ak360_translate('invalid_language');

    // Check that the selected country is in the approved country list.
    } elseif (!in_array($country, $countries, true)) {
        $message = ak360_translate('registration_required');

    // Check that both password entries match.
    } elseif ($password !== $confirm_password) {
        $message = ak360_translate('password_mismatch');

    // Validate the email address.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = ak360_translate('invalid_email');

    // Require a password of at least eight characters.
    } elseif (strlen($password) < 8) {
        $message = ak360_translate('password_length');

    } else {

        // Check whether the email address already exists.
        $checkEmail = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        // Execute the email lookup.
        $checkEmail->execute([$email]);

        // Check whether an existing account was found.
        if ($checkEmail->fetch()) {

            // Display the translated duplicate-email message.
            $message = ak360_translate('email_exists');

        } else {

            // Securely hash the user's password.
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Prepare the query to create a new tourist account.
            $insertUser = $pdo->prepare(
                "INSERT INTO users
                (name, email, password_hash, role, country, preferred_language)
                VALUES (?, ?, ?, 'tourist', ?, ?)"
            );

            // Save the new user's information in the database.
            $insertUser->execute([
                $name,
                $email,
                $passwordHash,
                $country,
                $language
            ]);

            // Retrieve the ID assigned to the newly registered user.
            $newUserId = $pdo->lastInsertId();

            // Regenerate the session ID for security.
            session_regenerate_id(true);

            // Automatically log the new user into Akwaaba 360.
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'tourist';
            $_SESSION['preferred_language'] = $language;

            // Save the selected language in a browser cookie.
            setcookie('preferred_language', $language, [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/akwaaba',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Redirect the newly registered user to preferences.
            header('Location: preferences.php');

            // Stop executing the registration script.
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

    <!-- Set the character encoding for international text. -->
    <meta charset="UTF-8">

    <!-- Make the page responsive on different screen sizes. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Display the translated page title. -->
    <title>
        <?php echo htmlspecialchars(ak360_translate('register_title')); ?>
        | Akwaaba 360
    </title>

    <!-- Load the shared website stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Load the fonts used on the authentication pages. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>

        /* Apply consistent sizing to every element. */
        * {
            box-sizing: border-box;
        }

        /* Centre the registration page on the cream background. */
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

        /* Control the width of the registration interface. */
        .register-wrapper {
            width: 100%;
            max-width: 510px;
        }

        /* Style the website brand above the registration card. */
        .register-brand {
            text-align: center;
            margin-bottom: 22px;
        }

        .register-brand h2 {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            color: #12372A;
            margin: 0;
        }

        .register-brand p {
            color: #555F58;
            margin-top: 7px;
            font-size: 14px;
        }

        /* Add the gold accent beneath the brand. */
        .register-brand::after {
            content: "";
            display: block;
            width: 48px;
            height: 3px;
            background: #D9A441;
            margin: 12px auto 0;
            border-radius: 3px;
        }

        /* Style the main registration card. */
        .register-card {
            background: #FFFFFF;
            padding: 38px;
            border-radius: 16px;
            border: 1px solid #E3E9E4;
            box-shadow: 0 12px 35px rgba(18, 55, 42, 0.09);
        }

        /* Style the registration heading. */
        .register-card h1 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: 28px;
            line-height: 1.3;
            color: #12372A;
            margin: 0 0 12px;
        }

        /* Style the introductory description. */
        .register-description {
            text-align: center;
            color: #626B64;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 25px;
        }

        /* Separate the language selector from the form. */
        .language-selector {
            margin-bottom: 25px;
        }

        /* Style the language selector label. */
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

        /* Highlight the language dropdown when focused. */
        .language-selector select:focus {
            outline: 2px solid #2E8B70;
            border-color: #2E8B70;
        }

        /* Space the registration fields consistently. */
        .form-group {
            margin-bottom: 19px;
        }

        /* Style all registration field labels. */
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #12372A;
        }

        /* Style the text inputs and country dropdown. */
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #E3E9E4;
            border-radius: 8px;
            background: #F7F6F0;
            color: #252525;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* Highlight inputs and dropdowns when focused. */
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2E8B70;
            box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.13);
            background: #FFFFFF;
        }

        /* Make the country dropdown look interactive. */
        .form-group select {
            cursor: pointer;
        }

        /* Reserve space for the password visibility button. */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 55px;
        }

        /* Position and style the password visibility button. */
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

        /* Give the password button a hover effect. */
        .toggle-password:hover {
            background: #E5F2EB;
            color: #12372A;
        }

        /* Make keyboard focus visible on the password button. */
        .toggle-password:focus-visible {
            outline: 2px solid #2E8B70;
        }

        /* Set the size of the password visibility icons. */
        .toggle-password svg {
            width: 21px;
            height: 21px;
        }

        /* Hide the eye-off icons initially. */
        .eye-off-icon {
            display: none;
        }

        /* Style registration validation messages. */
        .register-error {
            background: #FCEDEA;
            color: #9E3025;
            border: 1px solid #E8B9AE;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Style the registration submit button. */
        .register-submit {
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

        /* Add an interactive hover effect to the submit button. */
        .register-submit:hover {
            background: #0B261D;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(18, 55, 42, 0.2);
        }

        /* Return the button to its normal position when clicked. */
        .register-submit:active {
            transform: translateY(0);
        }

        /* Style the link for existing users. */
        .register-links {
            text-align: center;
            margin-top: 23px;
            font-size: 13px;
            color: #626B64;
        }

        .register-links a {
            color: #2E8B70;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }

        .register-links a:hover {
            color: #12372A;
            text-decoration: underline;
        }

        /* Style the footer beneath the card. */
        .register-footer {
            text-align: center;
            font-size: 12px;
            color: #626B64;
            margin-top: 22px;
        }

        /* Adjust the layout for smaller screens. */
        @media (max-width: 480px) {
            .register-card {
                padding: 27px 21px;
            }

            .register-card h1 {
                font-size: 24px;
            }

            .register-brand h2 {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>

    <!-- Centre the complete registration interface. -->
    <div class="register-wrapper">

        <!-- Display the website brand. -->
        <div class="register-brand">
            <h2>Akwaaba 360</h2>

            <p>
                <?php echo htmlspecialchars(ak360_translate('site_description')); ?>
            </p>
        </div>

        <!-- Display the registration card. -->
        <div class="register-card">

            <!-- Display the translated registration heading. -->
            <h1>
                <?php echo htmlspecialchars(ak360_translate('register_title')); ?>
            </h1>

            <!-- Display the translated introduction. -->
            <p class="register-description">
                <?php echo htmlspecialchars(ak360_translate('site_description')); ?>
            </p>

            <!-- Allow visitors to change the page language. -->
            <div class="language-selector">

                <label for="page-language">
                    <span aria-hidden="true">🌐</span>
                    <?php echo htmlspecialchars(ak360_translate('preferred_language')); ?>
                </label>

                <!-- Preserve the selected country when changing the language. -->
                <form method="GET" action="" id="language-form">

                    <!-- This hidden field receives the current country selection. -->
                    <input
                        type="hidden"
                        name="country"
                        id="preserved-country"
                        value="<?php echo htmlspecialchars($country); ?>"
                    >

                    <!-- Reload the page using the selected language. -->
                    <select
                        id="page-language"
                        name="language"
                        onchange="preserveCountryAndChangeLanguage()"
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

            <!-- Display registration feedback when validation fails. -->
            <?php if ($message !== ''): ?>

                <div class="register-error" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <!-- Create the main registration form. -->
            <form method="POST" action="">

                <!-- Preserve the selected language during registration. -->
                <input
                    type="hidden"
                    name="preferred_language"
                    value="<?php echo htmlspecialchars($currentLanguage); ?>"
                >

                <!-- Collect the user's full name. -->
                <div class="form-group">

                    <label for="name">
                        <?php echo htmlspecialchars(ak360_translate('full_name')); ?>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                        autocomplete="name"
                        required
                    >

                </div>

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
                        autocomplete="email"
                        required
                    >

                </div>

                <!-- Allow the user to select a country from the dropdown. -->
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

                        <!-- Display an instruction until a country is selected. -->
                        <option
                            value=""
                            disabled
                            <?php echo $country === '' ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars(ak360_translate('country')); ?>
                        </option>

                        <!-- Display all available countries. -->
                        <?php foreach ($countries as $countryName): ?>

                            <option
                                value="<?php echo htmlspecialchars($countryName); ?>"
                                <?php echo $country === $countryName ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($countryName); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

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
                            autocomplete="new-password"
                            required
                        >

                        <!-- Allow the user to show or hide the password. -->
                        <button
                            type="button"
                            class="toggle-password"
                            data-password-target="password"
                            aria-label="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                            title="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                            aria-pressed="false"
                        >

                            <!-- Display the eye icon. -->
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

                            <!-- Display the eye-off icon when the password is visible. -->
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

                <!-- Ask the user to confirm their password. -->
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

                        <!-- Allow the user to show or hide the confirmation password. -->
                        <button
                            type="button"
                            class="toggle-password"
                            data-password-target="confirm_password"
                            aria-label="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                            title="<?php echo htmlspecialchars($visibilityLabels['show']); ?>"
                            aria-pressed="false"
                        >

                            <!-- Display the eye icon. -->
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

                            <!-- Display the eye-off icon when the password is visible. -->
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

            <!-- Provide a link for users who already have an account. -->
            <div class="register-links">

                <?php echo htmlspecialchars(ak360_translate('no_account')); ?>

                <a href="login.php">
                    <?php echo htmlspecialchars(ak360_translate('login')); ?>
                </a>

            </div>

        </div>

        <!-- Display the website description beneath the card. -->
        <div class="register-footer">
            <?php echo htmlspecialchars(ak360_translate('site_description')); ?>
        </div>

    </div>

    <script>

        // Preserve the selected country before changing the page language.
        function preserveCountryAndChangeLanguage() {

            // Retrieve the country currently selected in the registration form.
            const countrySelect = document.getElementById('country');

            // Retrieve the hidden country field in the language form.
            const preservedCountry = document.getElementById('preserved-country');

            // Copy the selected country into the hidden field.
            preservedCountry.value = countrySelect ? countrySelect.value : '';

            // Submit the language form and reload the page.
            document.getElementById('language-form').submit();
        }

        // Find all password visibility buttons.
        const passwordToggleButtons = document.querySelectorAll('.toggle-password');

        // Retrieve translated visibility labels from PHP.
        const showPasswordText = <?php echo json_encode($visibilityLabels['show']); ?>;
        const hidePasswordText = <?php echo json_encode($visibilityLabels['hide']); ?>;

        // Add a click event to each password visibility button.
        passwordToggleButtons.forEach(function (button) {

            // Identify the password field controlled by this button.
            const targetId = button.getAttribute('data-password-target');
            const passwordField = document.getElementById(targetId);

            // Find the two eye icons inside the button.
            const eyeIcon = button.querySelector('.eye-icon');
            const eyeOffIcon = button.querySelector('.eye-off-icon');

            // Toggle password visibility when the button is clicked.
            button.addEventListener('click', function () {

                // Check whether the password is currently hidden.
                const isHidden = passwordField.type === 'password';

                // Change the password field type.
                passwordField.type = isHidden ? 'text' : 'password';

                // Display the appropriate eye icon.
                eyeIcon.style.display = isHidden ? 'none' : 'block';
                eyeOffIcon.style.display = isHidden ? 'block' : 'none';

                // Update the accessible button description.
                button.setAttribute(
                    'aria-label',
                    isHidden ? hidePasswordText : showPasswordText
                );

                // Update the hover tooltip.
                button.setAttribute(
                    'title',
                    isHidden ? hidePasswordText : showPasswordText
                );

                // Update the accessibility pressed state.
                button.setAttribute(
                    'aria-pressed',
                    isHidden ? 'true' : 'false'
                );

            });

        });

    </script>

</body>
</html>
