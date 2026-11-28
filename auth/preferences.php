
<?php
/*
 * AKWAABA 360
 * User Preferences Page
 *
 * Allows logged-in users to:
 * 1. Change their preferred language.
 * 2. Change their preferred currency.
 * 3. Save preferences to the database.
 * 4. Synchronise preferences with sessions and cookies.
 */

// Load the authentication system.
require_once __DIR__ . '/../includes/auth.php';

// Make sure only logged-in users can access this page.
requireLogin('login.php');

// Load the reusable PDO database connection.
require_once __DIR__ . '/../includes/db.php';

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Define the languages supported by Akwaaba 360.
$allowedLanguages = [
    'en' => 'English',
    'tw' => 'Twi',
    'fr' => 'French',
    'es' => 'Spanish'
];

// Define the currencies supported by Akwaaba 360.
$allowedCurrencies = [
    'GHS' => 'Ghanaian Cedi (GHS)',
    'USD' => 'US Dollar (USD)',
    'EUR' => 'Euro (EUR)',
    'GBP' => 'British Pound (GBP)'
];

// Get the logged-in user's database ID.
$userId = $_SESSION['user_id'];

// Create a CSRF token if one does not already exist.
if (empty($_SESSION['preferences_csrf_token'])) {
    $_SESSION['preferences_csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve the user's saved preferences from the database.
$stmt = $pdo->prepare(
    "SELECT preferred_language, preferred_currency
     FROM users
     WHERE id = ?"
);

$stmt->execute([$userId]);

// Fetch the user's saved preferences.
$userPreferences = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the saved language and currency.
// Use session and cookie values as fallbacks.
$language = $userPreferences['preferred_language']
    ?? $_SESSION['preferred_language']
    ?? $_COOKIE['preferred_language']
    ?? 'en';

$currency = $userPreferences['preferred_currency']
    ?? $_SESSION['preferred_currency']
    ?? $_COOKIE['preferred_currency']
    ?? 'GHS';

// Make sure the selected language is supported.
if (!array_key_exists($language, $allowedLanguages)) {
    $language = 'en';
}

// Make sure the selected currency is supported.
if (!array_key_exists($currency, $allowedCurrencies)) {
    $currency = 'GHS';
}

// Synchronise the session with the database preferences.
$_SESSION['preferred_language'] = $language;
$_SESSION['preferred_currency'] = $currency;

// Initialise the message and status variables.
$messageKey = '';
$messageType = '';

// Process the preferences form when submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve the submitted CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    // Verify that the submitted token matches the session token.
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['preferences_csrf_token'], $submittedToken)
    ) {

        // Reject the request if the token is invalid.
        $messageKey = 'preferences_error';
        $messageType = 'error';

    } else {

        // Retrieve the selected language.
        $selectedLanguage = $_POST['language'] ?? '';

        // Retrieve the selected currency.
        $selectedCurrency = $_POST['currency'] ?? '';

        // Validate both selections against the supported options.
        if (
            is_string($selectedLanguage) &&
            is_string($selectedCurrency) &&
            array_key_exists($selectedLanguage, $allowedLanguages) &&
            array_key_exists($selectedCurrency, $allowedCurrencies)
        ) {

            try {

                // Update the user's preferences in the database.
                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET preferred_language = ?,
                         preferred_currency = ?
                     WHERE id = ?"
                );

                // Execute the update for the logged-in user.
                $stmt->execute([
                    $selectedLanguage,
                    $selectedCurrency,
                    $userId
                ]);

                // Set the cookie expiry to one year from now.
                $expiry = time() + (365 * 24 * 60 * 60);

                // Update the active session language.
                $_SESSION['preferred_language'] = $selectedLanguage;

                // Update the active session currency.
                $_SESSION['preferred_currency'] = $selectedCurrency;

                // Save the selected language in a browser cookie.
                setcookie('preferred_language', $selectedLanguage, [
                    'expires' => $expiry,
                    'path' => '/akwaaba',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                // Save the selected currency in a browser cookie.
                setcookie('preferred_currency', $selectedCurrency, [
                    'expires' => $expiry,
                    'path' => '/akwaaba',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                // Redirect to reload the page using the updated preferences.
                header('Location: preferences.php?saved=1');
                exit;

            } catch (PDOException $e) {

                // Store the error translation key if the update fails.
                $messageKey = 'preferences_error';
                $messageType = 'error';
            }

        } else {

            // Reject unsupported language or currency selections.
            $messageKey = 'preferences_error';
            $messageType = 'error';
        }
    }
}

// Display a confirmation message after a successful save.
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $messageKey = 'preferences_saved';
    $messageType = 'success';
}

// Determine the logged-in user's role.
$userRole = strtolower($_SESSION['user_role'] ?? '');

// Select the correct dashboard link according to the user's role.
if ($userRole === 'guide') {
    $dashboardLink = '../guide/dashboard.php';
} elseif ($userRole === 'admin') {
    $dashboardLink = '../admin/dashboard.php';
} else {
    $dashboardLink = 'dashboard.php';
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================
   PREFERENCES PAGE
   ========================================= */

/* Main page wrapper. */
.preferences-page {
    width: 100%;
    max-width: 1050px;
    margin: 0 auto;
    padding: 55px 24px 75px;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
}

/* Small introductory label above the heading. */
.preferences-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
    color: #2E8B70;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Decorative gold line. */
.preferences-eyebrow::before {
    content: '';
    display: inline-block;
    width: 25px;
    height: 2px;
    background: #D9A441;
}

/* Page heading. */
.preferences-heading {
    margin: 0 0 12px;
    color: #12372A;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(30px, 4vw, 43px);
    font-weight: 600;
    line-height: 1.2;
}

/* Introductory description. */
.preferences-description {
    max-width: 620px;
    margin: 0;
    color: #68746D;
    font-size: 14px;
    line-height: 1.8;
}

/* Main settings panel. */
.preferences-card {
    display: grid;
    grid-template-columns: 0.85fr 1.4fr;
    gap: 45px;
    margin-top: 38px;
    padding: 38px;
    border: 1px solid #E3E9E4;
    border-radius: 12px;
    background: #FFFFFF;
    box-shadow: 0 12px 35px rgba(18, 55, 42, 0.055);
}

/* Left information section. */
.preferences-info {
    padding: 8px 28px 8px 0;
    border-right: 1px solid #E3E9E4;
}

/* Small decorative icon. */
.preferences-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    margin-bottom: 23px;
    border-radius: 10px;
    background: #E5F2EB;
    color: #12372A;
    font-size: 22px;
}

/* Information heading. */
.preferences-info h2 {
    margin: 0 0 12px;
    color: #12372A;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 23px;
    font-weight: 600;
}

/* Information paragraph. */
.preferences-info p {
    margin: 0;
    color: #68746D;
    font-size: 13px;
    line-height: 1.9;
}

/* Preferences form. */
.preferences-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

/* Individual form field. */
.preference-field {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

/* Form labels. */
.preference-field label {
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
}

/* Form dropdowns. */
.preference-field select {
    width: 100%;
    height: 48px;
    padding: 0 14px;
    border: 1px solid #DCE4DC;
    border-radius: 7px;
    outline: none;
    background: #FFFFFF;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

/* Dropdown focus state. */
.preference-field select:focus {
    border-color: #2E8B70;
    box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.12);
}

/* Small explanation below each dropdown. */
.preference-hint {
    margin: 0;
    color: #89938C;
    font-size: 11px;
    line-height: 1.6;
}

/* Form action buttons. */
.preferences-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 5px;
}

/* Save button. */
.preferences-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 45px;
    padding: 0 23px;
    border: 1px solid #12372A;
    border-radius: 6px;
    background: #12372A;
    color: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease;
}

/* Save button hover state. */
.preferences-save:hover {
    border-color: #2E8B70;
    background: #2E8B70;
}

/* Dashboard return link. */
.preferences-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 45px;
    padding: 0 18px;
    border: 1px solid #DCE4DC;
    border-radius: 6px;
    background: #FFFFFF;
    color: #12372A;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s ease, border-color 0.2s ease;
}

/* Dashboard link hover state. */
.preferences-back:hover {
    border-color: #2E8B70;
    background: #E5F2EB;
}

/* Success and error message container. */
.preferences-message {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-top: 25px;
    padding: 14px 16px;
    border-radius: 7px;
    font-size: 13px;
    line-height: 1.6;
}

/* Success message appearance. */
.preferences-message.success {
    border: 1px solid #C8E5D2;
    background: #EFF8F1;
    color: #24623B;
}

/* Error message appearance. */
.preferences-message.error {
    border: 1px solid #F0D1CC;
    background: #FFF5F3;
    color: #9B3E32;
}

/* Message icon. */
.preferences-message-icon {
    flex-shrink: 0;
    font-size: 17px;
    font-weight: 700;
}

/* Responsive tablet layout. */
@media (max-width: 760px) {
    .preferences-page {
        padding: 40px 20px 55px;
    }

    .preferences-card {
        grid-template-columns: 1fr;
        gap: 28px;
        padding: 27px;
    }

    .preferences-info {
        padding: 0 0 25px;
        border-right: none;
        border-bottom: 1px solid #E3E9E4;
    }

    .preferences-icon {
        margin-bottom: 15px;
    }
}

/* Responsive mobile layout. */
@media (max-width: 480px) {
    .preferences-page {
        padding: 32px 15px 45px;
    }

    .preferences-card {
        gap: 24px;
        margin-top: 27px;
        padding: 22px 18px;
    }

    .preferences-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .preferences-save,
    .preferences-back {
        width: 100%;
    }
}
</style>

<!-- Main preferences page. -->
<main class="preferences-page">

    <!-- Page introduction. -->
    <div class="preferences-intro">

        <span class="preferences-eyebrow">
            Akwaaba 360 Account
        </span>

        <h1 class="preferences-heading">
            <?php echo htmlspecialchars(ak360_translate('preferences_title')); ?>
        </h1>

        <p class="preferences-description">
            Choose how you would like to experience Akwaaba 360.
            Manage your preferred language and currency in one place.
        </p>

    </div>

    <!-- Display a translated success or error message when available. -->
    <?php if ($messageKey !== ''): ?>
        <div
            class="preferences-message <?php echo htmlspecialchars($messageType); ?>"
            role="status"
            aria-live="polite"
        >
            <span class="preferences-message-icon">
                <?php echo $messageType === 'success' ? '✓' : '!'; ?>
            </span>

            <span>
                <?php echo htmlspecialchars(ak360_translate($messageKey)); ?>
            </span>
        </div>
    <?php endif; ?>

    <!-- Main preferences card. -->
    <section class="preferences-card">

        <!-- Left information section. -->
        <div class="preferences-info">

            <div class="preferences-icon" aria-hidden="true">
                ⚙
            </div>

            <h2>
                Your preferences
            </h2>

            <p>
                Personalise your experience by choosing the language
                used across the website and the currency displayed
                when viewing tourism prices.
            </p>

        </div>

        <!-- Right form section. -->
        <form method="POST" action="" class="preferences-form">

            <!-- CSRF protection token. -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['preferences_csrf_token']); ?>"
            >

            <!-- Preferred language field. -->
            <div class="preference-field">

                <label for="language">
                    <?php echo htmlspecialchars(ak360_translate('preferred_language')); ?>
                </label>

                <select id="language" name="language" required>

                    <?php foreach ($allowedLanguages as $code => $name): ?>

                        <option
                            value="<?php echo htmlspecialchars($code); ?>"
                            <?php echo ($language === $code) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($name); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <p class="preference-hint">
                    Select the language you want to use throughout Akwaaba 360.
                </p>

            </div>

            <!-- Preferred currency field. -->
            <div class="preference-field">

                <label for="currency">
                    <?php echo htmlspecialchars(ak360_translate('preferred_currency')); ?>
                </label>

                <select id="currency" name="currency" required>

                    <?php foreach ($allowedCurrencies as $code => $name): ?>

                        <option
                            value="<?php echo htmlspecialchars($code); ?>"
                            <?php echo ($currency === $code) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars(ak360_translate('currency_' . $code)); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <p class="preference-hint">
                    Choose the currency you prefer to see when browsing prices.
                </p>

            </div>

            <!-- Form action buttons. -->
            <div class="preferences-actions">

                <button type="submit" class="preferences-save">
                    <?php echo htmlspecialchars(ak360_translate('save_preferences')); ?>
                </button>

                <a
                    href="<?php echo htmlspecialchars($dashboardLink); ?>"
                    class="preferences-back"
                >
                    <?php echo htmlspecialchars(ak360_translate('back_to_dashboard')); ?>
                </a>

            </div>

        </form>

    </section>

</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
