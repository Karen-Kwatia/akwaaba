
<?php
// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in administrators to access this page.
requireRole(['admin'], '../auth/login.php');

// Load the language translation system.
require_once __DIR__ . '/../includes/language.php';

// Get the logged-in administrator's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the admin dashboard inside a styled content card. -->
<section class="content-card">

    <!-- Display the administrator dashboard heading. -->
    <h1>
        <?php echo htmlspecialchars(ak360_translate('admin_dashboard_title')); ?>
    </h1>

    <!-- Display the logged-in administrator's name. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('admin_welcome')); ?>
        <?php echo htmlspecialchars($userName); ?>!
    </p>

    <!-- Display a short description of the administrator's role. -->
    <p>
        <?php echo htmlspecialchars(ak360_translate('admin_description')); ?>
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
