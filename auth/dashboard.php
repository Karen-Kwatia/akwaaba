
<?php
// Load the authentication system and restore the user's session if necessary.
require_once __DIR__ . '/../includes/auth.php';

// Make sure only logged-in users can access this dashboard.
requireLogin('login.php');

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the tourist dashboard inside a styled content card. -->
<section class="content-card">

    <!-- Display the tourist dashboard heading. -->
    <h1>Welcome to Akwaaba 360!</h1>

    <!-- Welcome the logged-in tourist by name. -->
    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
    </p>

    <!-- Display the user's account role. -->
    <p>
        Your account role:
        <?php echo htmlspecialchars($_SESSION['user_role']); ?>
    </p>

    <!-- Provide a link to the user's saved preferences. -->
    <a href="preferences.php" class="btn btn-primary">
        Manage Preferences
    </a>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
