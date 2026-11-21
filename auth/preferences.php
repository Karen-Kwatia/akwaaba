
<?php
// Load the authentication system.
require_once __DIR__ . '/../includes/auth.php';

// Make sure only logged-in users can access this page.
requireLogin('login.php');

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

// Set the default language to English.
$language = 'en';

// Set the default currency to Ghanaian Cedi.
$currency = 'GHS';

// Check whether the browser already has a saved language.
if (isset($_COOKIE['preferred_language'])) {
    // Use the saved language only if it is supported.
    if (array_key_exists($_COOKIE['preferred_language'], $allowedLanguages)) {
        $language = $_COOKIE['preferred_language'];
    }
}

// Check whether the browser already has a saved currency.
if (isset($_COOKIE['preferred_currency'])) {
    // Use the saved currency only if it is supported.
    if (array_key_exists($_COOKIE['preferred_currency'], $allowedCurrencies)) {
        $currency = $_COOKIE['preferred_currency'];
    }
}

// Initialise a variable for displaying messages.
$message = '';

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

        // Set the cookie expiry to one year from now.
        $expiry = time() + (365 * 24 * 60 * 60);

        // Save the selected language in the browser.
        setcookie('preferred_language', $selectedLanguage, [
            'expires' => $expiry,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Save the selected currency in the browser.
        setcookie('preferred_currency', $selectedCurrency, [
            'expires' => $expiry,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Redirect to reload the page with the newly saved cookies.
        header('Location: preferences.php?saved=1');
        exit;

    } else {

        // Display an error if either selection is invalid.
        $message = 'Please select a valid language and currency.';
    }
}

// Display a confirmation after preferences have been saved.
if (isset($_GET['saved'])) {
    $message = 'Your preferences have been saved successfully!';
}

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the preferences form inside a styled content card. -->
<section class="content-card">

    <!-- Display the page heading. -->
    <h1>Your Preferences</h1>

    <!-- Display a success or error message. -->
    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Allow users to choose their preferred language and currency. -->
    <form method="POST" action="">

        <!-- Provide the supported language options. -->
        <label for="language">Preferred Language:</label>

        <select id="language" name="language" required>
            <?php foreach ($allowedLanguages as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>"
                    <?php echo ($language === $code) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Provide the supported currency options. -->
        <label for="currency">Preferred Currency:</label>

        <select id="currency" name="currency" required>
            <?php foreach ($allowedCurrencies as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>"
                    <?php echo ($currency === $code) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Save the selected preferences using the shared button style. -->
        <button type="submit" class="btn btn-primary">
            Save Preferences
        </button>

    </form>

    <!-- Provide a link back to the tourist dashboard. -->
    <p>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
