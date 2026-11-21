
<?php
// Determine the current user's role if they are logged in.
$userRole = $_SESSION['user_role'] ?? null;

// Determine the current user's name.
$userName = $_SESSION['user_name'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set the character encoding for the website. -->
    <meta charset="UTF-8">

    <!-- Make the website responsive on mobile devices. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the default website title. -->
    <title>Akwaaba 360 | Explore Ghana</title>

    <!-- Connect the shared stylesheet to this page. -->
    <link rel="stylesheet" href="/akwaaba/assets/css/style.css">
</head>
<body>

    <!-- Create the shared website header. -->
    <header class="site-header">

        <!-- Arrange the logo and navigation links. -->
        <div class="nav-container">

            <!-- Display the Akwaaba 360 brand name. -->
            <a href="/akwaaba/index.php" class="logo">
                Akwaaba 360
            </a>

            <!-- Display navigation links. -->
            <nav>
                <ul class="nav-links">

                    <!-- Provide access to the homepage. -->
                    <li><a href="/akwaaba/index.php">Home</a></li>

                    <!-- Provide access to tourist sites. -->
                    <li><a href="/akwaaba/tours/index.php">Explore Sites</a></li>

                    <?php if ($userRole === 'tourist'): ?>

                        <!-- Show tourist-specific navigation links. -->
                        <li><a href="/akwaaba/auth/dashboard.php">Dashboard</a></li>
                        <li><a href="/akwaaba/auth/preferences.php">Preferences</a></li>

                    <?php elseif ($userRole === 'guide'): ?>

                        <!-- Show guide-specific navigation links. -->
                        <li><a href="/akwaaba/guide/dashboard.php">Guide Dashboard</a></li>

                    <?php elseif ($userRole === 'admin'): ?>

                        <!-- Show administrator-specific navigation links. -->
                        <li><a href="/akwaaba/admin/dashboard.php">Admin Dashboard</a></li>

                    <?php endif; ?>

                    <?php if ($userRole): ?>

                        <!-- Show the user's name and logout option. -->
                        <li>
                            <span>
                                Welcome, <?php echo htmlspecialchars($userName); ?>
                            </span>
                        </li>

                        <li><a href="/akwaaba/auth/logout.php">Logout</a></li>

                    <?php else: ?>

                        <!-- Show account links to visitors who are not logged in. -->
                        <li><a href="/akwaaba/auth/login.php">Login</a></li>
                        <li><a href="/akwaaba/auth/register.php">Register</a></li>

                    <?php endif; ?>

                </ul>
            </nav>

        </div>

    </header>

    <!-- Begin the main content area of the page. -->
    <main class="container">
