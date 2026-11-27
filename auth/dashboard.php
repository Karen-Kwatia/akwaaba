
<?php
// Load the authentication system and restore the user's session if necessary.
require_once __DIR__ . '/../includes/auth.php';

// Make sure only logged-in users can access this dashboard.
requireLogin('login.php');

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Get the user's account role from the session.
$userRole = strtolower($_SESSION['user_role'] ?? 'tourist');

// Get the logged-in user's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Build the translation key for the user's role.
$roleTranslationKey = 'role_' . $userRole;

// Translate the role if a translation exists.
// Otherwise, display the original role with its first letter capitalised.
$translatedRole = $languageTranslations[$currentLanguage][$roleTranslationKey]
    ?? $languageTranslations['en'][$roleTranslationKey]
    ?? ucfirst($userRole);

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the tourist dashboard's main welcome section. -->
<section class="hero">

    <!-- Identify the section as the user's personal travel space. -->
    <p class="section-label">
        <?php echo htmlspecialchars(ak360_translate('dashboard_welcome_label')); ?>
    </p>

    <!-- Personalise the greeting using the logged-in user's name. -->
    <h1>
        <?php echo htmlspecialchars(ak360_translate('dashboard_welcome')); ?>
        <?php echo htmlspecialchars($userName); ?>!
    </h1>

    <!-- Introduce the platform and encourage the user to explore. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('dashboard_description')); ?>
    </p>

    <!-- Provide quick access to destination exploration. -->
    <a href="../tours/index.php" class="btn btn-primary">
        <?php echo htmlspecialchars(ak360_translate('explore_destinations')); ?>
    </a>

    <!-- Provide access to the user's saved preferences. -->
    <a href="preferences.php" class="btn btn-secondary">
        <?php echo htmlspecialchars(ak360_translate('manage_preferences')); ?>
    </a>

</section>

<!-- Display the user's account information. -->
<section class="content-card">

    <!-- Display the translated account section heading. -->
    <h2>
        <?php echo htmlspecialchars(ak360_translate('your_account')); ?>
    </h2>

    <!-- Display the user's current account role. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('account_type')); ?>:
        <?php echo htmlspecialchars($translatedRole); ?>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
