
<?php
// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in guides to access this page.
requireRole(['guide'], '../auth/login.php');

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the guide dashboard inside a styled content card. -->
<section class="content-card">

    <!-- Display the guide dashboard heading. -->
    <h1>Akwaaba 360 Guide Dashboard</h1>

    <!-- Display the logged-in guide's name. -->
    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
    </p>

    <!-- Display the guide's account role. -->
    <p>
        Account role:
        <?php echo htmlspecialchars($_SESSION['user_role']); ?>
    </p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
