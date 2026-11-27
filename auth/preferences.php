
<?php
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

// Get the user's current preferences from the session or cookies.
$language = $_SESSION['preferred_language']
    ?? $_COOKIE['preferred_language']
    ?? 'en';

$currency = $_SESSION['preferred_currency']
    ?? $_COOKIE['preferred_currency']
    ?? 'GHS';

// Retrieve the user's saved preferences from the database.
$stmt = $pdo->prepare(
    "SELECT preferred_language, preferred_currency
     FROM users
     WHERE id = ?"
);

$stmt->execute([$userId]);

// Fetch the user's saved preferences.
$userPreferences = $stmt->fetch(PDO::FETCH_ASSOC);

// Use the database preferences when available.
if ($userPreferences) {
    $language = $userPreferences['preferred_language'];
    $currency = $userPreferences['preferred_currency'];
}

// Make sure the selected language is supported.
if (!array_key_exists($language, $allowedLanguages)) {
    $language = 'en';
}

// Make sure the selected currency is supported.
if (!array_key_exists($currency, $allowedCurrencies)) {
    $currency = 'GHS';
}

// Synchronise the session with the saved preferences.
$_SESSION['preferred_language'] = $language;
$_SESSION['preferred_currency'] = $currency;

// Initialise the variable that stores the message translation key.
$messageKey = '';

// Process the preferences form when submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve the selected language.
    $selectedLanguage = $_POST['language'] ?? '';

    // Retrieve the selected currency.
    $selectedCurrency = $_POST['currency'] ?? '';

    // Check that both selections are supported.
    if (
        array_key_exists($selectedLanguage, $allowedLanguages) &&
        array_key_exists($selectedCurrency, $allowedCurrencies)
    ) {

        // Save both preferences in the database.
        $stmt = $pdo->prepare(
            "UPDATE users
             SET preferred_language = ?, preferred_currency = ?
             WHERE id = ?"
        );

        // Execute the database update using the user's selections.
        $updated = $stmt->execute([
            $selectedLanguage,
            $selectedCurrency,
            $userId
        ]);

        // Check whether the database update was successful.
        if ($updated) {

            // Set the cookie expiry to one year from now.
            $expiry = time() + (365 * 24 * 60 * 60);

            // Update the active session language.
            $_SESSION['preferred_language'] = $selectedLanguage;

            // Update the active session currency.
            $_SESSION['preferred_currency'] = $selectedCurrency;

            // Save the selected language in the browser cookie.
            setcookie('preferred_language', $selectedLanguage, [
                'expires' => $expiry,
                'path' => '/akwaaba',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Save the selected currency in the browser cookie.
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

        } else {

            // Store the translation key for the error message.
            $messageKey = 'preferences_error';
        }

    } else {

        // Store the translation key for invalid selections.
        $messageKey = 'preferences_error';
    }
}

// Display a confirmation message after preferences are saved.
if (isset($_GET['saved'])) {
    $messageKey = 'preferences_saved';
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

<!-- Display the preferences form inside a styled content card. -->
<section class="content-card">

    <!-- Display the translated page heading. -->
    <h1>
        <?php echo htmlspecialchars(ak360_translate('preferences_title')); ?>
    </h1>

    <!-- Display a translated success or error message when available. -->
    <?php if ($messageKey !== ''): ?>
        <p>
            <?php echo htmlspecialchars(ak360_translate($messageKey)); ?>
        </p>
    <?php endif; ?>

    <!-- Allow users to choose their preferred language and currency. -->
    <form method="POST" action="">

        <!-- Display the translated language selection label. -->
        <label for="language">
            <?php echo htmlspecialchars(ak360_translate('preferred_language')); ?>
        </label>

        <!-- Display the four supported language options. -->
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

        <!-- Display the translated currency selection label. -->
        <label for="currency">
            <?php echo htmlspecialchars(ak360_translate('preferred_currency')); ?>
        </label>

        <!-- Display the supported currencies using translated names. -->
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

        <!-- Save the selected preferences. -->
        <button type="submit" class="btn btn-primary">
            <?php echo htmlspecialchars(ak360_translate('save_preferences')); ?>
        </button>

    </form>

    <!-- Provide a link back to the appropriate dashboard. -->
    <p>
        <a href="<?php echo htmlspecialchars($dashboardLink); ?>" class="btn btn-secondary">
            <?php echo htmlspecialchars(ak360_translate('back_to_dashboard')); ?>
        </a>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
