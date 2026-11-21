
<?php
// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in administrators to access this page.
requireRole(['admin'], '../auth/login.php');

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Display the admin dashboard inside a styled content card. -->
<section class="content-card">

    <!-- Display the administrator dashboard heading. -->
    <h1>Akwaaba 360 Admin Dashboard</h1>

    <!-- Display the logged-in administrator's name. -->
    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
    </p>

    <!-- Display a short description of the administrator's role. -->
    <p>You have access to the administration area.</p>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
