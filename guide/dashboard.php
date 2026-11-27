
<?php
// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in guides to access this page.
requireRole(['guide'], '../auth/login.php');

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Get the guide's account role from the session.
$userRole = strtolower($_SESSION['user_role'] ?? 'guide');

// Get the logged-in guide's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Build the translation key for the account role.
$roleTranslationKey = 'role_' . $userRole;

// Translate the account role.
// If a translation is unavailable, use the English translation.
// If that is also unavailable, display the original role.
$translatedRole = $languageTranslations[$currentLanguage][$roleTranslationKey]
    ?? $languageTranslations['en'][$roleTranslationKey]
    ?? ucfirst($userRole);

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the guide dashboard inside a styled content card. -->
<section class="content-card">

    <!-- Display the guide dashboard heading. -->
    <h1>
        <?php echo htmlspecialchars(ak360_translate('guide_dashboard_title')); ?>
    </h1>

    <!-- Display the logged-in guide's name. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('guide_welcome')); ?>
        <?php echo htmlspecialchars($userName); ?>!
    </p>

    <!-- Display the guide's account role. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('guide_account_role')); ?>:
        <?php echo htmlspecialchars($translatedRole); ?>
    </p>

    <!-- Provide access to the language and currency preferences page. -->
    <a href="../auth/preferences.php" class="btn btn-primary">
        <?php echo htmlspecialchars(ak360_translate('preferences')); ?>
    </a>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
