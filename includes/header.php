
<?php
// Load the central translation system.
require_once __DIR__ . '/language.php';

// Determine the current user's role if they are logged in.
$userRole = $_SESSION['user_role'] ?? null;

// Determine the current user's name.
$userName = $_SESSION['user_name'] ?? null;
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage); ?>">
<head>
    <!-- Set the character encoding for the website. -->
    <meta charset="UTF-8">

    <!-- Make the website responsive on mobile devices. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Display the website title in the selected language. -->
    <title><?php echo htmlspecialchars(ak360_translate('site_title')); ?></title>

    <!-- Display a translated description for search engines. -->
    <meta name="description" content="<?php echo htmlspecialchars(ak360_translate('site_description')); ?>">

    <!-- Connect the shared stylesheet to this page. -->
    <link rel="stylesheet" href="/akwaaba/assets/css/style.css">

    <style>
        /* Arrange navigation icons and labels horizontally. */
        .nav-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Give navigation icons a consistent appearance. */
        .nav-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            vertical-align: middle;
            transition: transform 0.2s ease;
        }

        /* Add a small movement effect when a link is hovered. */
        .nav-links a:hover .nav-icon {
            transform: translateY(-2px);
        }

        /* Style the hamburger menu button. */
        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            padding: 9px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 9px;
            background: transparent;
            color: #FFFFFF;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        /* Set the size of the hamburger and close icons. */
        .nav-toggle svg {
            width: 25px;
            height: 25px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Hide the close icon until the menu is opened. */
        .nav-close-icon {
            display: none;
        }

        /* Add a visible hover effect to the menu button. */
        .nav-toggle:hover {
            background: #2E8B70;
            border-color: #2E8B70;
        }

        /* Show a clear keyboard focus indicator. */
        .nav-toggle:focus-visible {
            outline: 3px solid #D9A441;
            outline-offset: 3px;
        }

        /* Hide text that is intended only for screen readers. */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Change the navigation into a collapsible mobile menu. */
        @media (max-width: 768px) {

            /* Keep the logo and menu button on the same row. */
            .nav-container {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                text-align: left;
                flex-wrap: wrap;
                gap: 12px;
            }

            /* Display the hamburger button on mobile screens. */
            .nav-toggle {
                display: inline-flex;
            }

            /* Hide the navigation until the button is clicked. */
            .site-navigation {
                display: none;
                width: 100%;
                flex-basis: 100%;
            }

            /* Display the navigation when the menu is opened. */
            .site-navigation.is-open {
                display: block;
            }

            /* Arrange mobile navigation links vertically. */
            .nav-links {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                justify-content: flex-start;
                flex-wrap: nowrap;
                gap: 5px;
                width: 100%;
                padding-top: 12px;
            }

            /* Make each navigation item occupy the available width. */
            .nav-links li {
                width: 100%;
            }

            /* Make each link easy to tap on a touchscreen. */
            .nav-links a {
                display: flex;
                align-items: center;
                width: 100%;
                padding: 12px 15px;
                font-size: 14px;
                border-radius: 8px;
            }

            /* Remove the desktop hover movement on mobile. */
            .nav-links a:hover {
                transform: none;
            }
        }

        /* Keep the menu comfortable on very small screens. */
        @media (max-width: 480px) {
            .logo {
                font-size: 25px;
            }

            .nav-links a {
                padding: 13px;
            }
        }

        /* Respect users who prefer reduced animation. */
        @media (prefers-reduced-motion: reduce) {
            .nav-icon,
            .nav-toggle,
            .nav-links a {
                transition: none;
            }
        }
    </style>
</head>

<body>

    <!-- Create the shared website header. -->
    <header class="site-header">

        <!-- Arrange the logo, menu button and navigation links. -->
        <div class="nav-container">

            <!-- Display the Akwaaba 360 brand name. -->
            <a href="/akwaaba/index.php" class="logo">
                Akwaaba 360
            </a>

            <!-- Provide a button for opening and closing mobile navigation. -->
            <button
                type="button"
                class="nav-toggle"
                id="nav-toggle"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="main-navigation"
            >

                <!-- Display the hamburger icon. -->
                <svg
                    class="nav-menu-icon"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M4 6h16"/>
                    <path d="M4 12h16"/>
                    <path d="M4 18h16"/>
                </svg>

                <!-- Display the close icon when the menu is open. -->
                <svg
                    class="nav-close-icon"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="m18 6-12 12"/>
                    <path d="m6 6 12 12"/>
                </svg>

                <!-- Provide additional text for screen readers. -->
                <span class="visually-hidden">Toggle navigation menu</span>

            </button>

            <!-- Display navigation links in the selected language. -->
            <nav
                class="site-navigation"
                id="main-navigation"
                aria-label="Main navigation"
            >
                <ul class="nav-links">

                    <!-- Provide access to the homepage. -->
                    <li>
                        <a href="/akwaaba/index.php">

                            <!-- Home icon. -->
                            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m3 10 9-7 9 7"/>
                                <path d="M5 9v12h14V9"/>
                                <path d="M9 21v-7h6v7"/>
                            </svg>

                            <?php echo htmlspecialchars(ak360_translate('home')); ?>
                        </a>
                    </li>

                    <!-- Provide access to tourist sites. -->
                    <li>
                        <a href="/akwaaba/tours/index.php">

                            <!-- Map pin icon. -->
                            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                <circle cx="12" cy="10" r="2.5"/>
                            </svg>

                            <?php echo htmlspecialchars(ak360_translate('explore_sites')); ?>
                        </a>
                    </li>

                    <?php if ($userRole === 'tourist'): ?>

                        <!-- Show tourist-specific navigation links. -->
                        <li>
                            <a href="/akwaaba/auth/dashboard.php">

                                <!-- Dashboard icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="3" width="8" height="8" rx="1"/>
                                    <rect x="13" y="3" width="8" height="5" rx="1"/>
                                    <rect x="13" y="10" width="8" height="11" rx="1"/>
                                    <rect x="3" y="13" width="8" height="8" rx="1"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('dashboard')); ?>
                            </a>
                        </li>

                        <!-- Allow tourists to manage their preferences. -->
                        <li>
                            <a href="/akwaaba/auth/preferences.php">

                                <!-- Settings icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.2l-.2.1h-3.4l-.1-.2a1.7 1.7 0 0 0-1.5-1l-.3-.1-1.7-2.9.1-.2a1.7 1.7 0 0 0-.3-1.8l-.1-.2v-3.4l.2-.1a1.7 1.7 0 0 0 1-1.5l.1-.3 2.9-1.7.2.1a1.7 1.7 0 0 0 1.8-.3l.2-.1h3.4l.1.2a1.7 1.7 0 0 0 1.5 1l.3.1 1.7 2.9-.1.2a1.7 1.7 0 0 0 .3 1.8l.1.2v3.4l-.2.1a1.7 1.7 0 0 0-1 1.5"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('preferences')); ?>
                            </a>
                        </li>

                    <?php elseif ($userRole === 'guide'): ?>

                        <!-- Show guide-specific navigation links. -->
                        <li>
                            <a href="/akwaaba/guide/dashboard.php">

                                <!-- Guide briefcase icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="7" width="18" height="14" rx="2"/>
                                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <path d="M3 12h18"/>
                                    <path d="M10 12v2h4v-2"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('guide_dashboard')); ?>
                            </a>
                        </li>

                    <?php elseif ($userRole === 'admin'): ?>

                        <!-- Show administrator-specific navigation links. -->
                        <li>
                            <a href="/akwaaba/admin/dashboard.php">

                                <!-- Shield icon for administration. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('admin_dashboard')); ?>
                            </a>
                        </li>

                    <?php endif; ?>

                    <?php if ($userRole): ?>

                        <!-- Show the logout option to logged-in users. -->
                        <li>
                            <a href="/akwaaba/auth/logout.php">

                                <!-- Logout icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M10 17l5-5-5-5"/>
                                    <path d="M15 12H3"/>
                                    <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('logout')); ?>
                            </a>
                        </li>

                    <?php else: ?>

                        <!-- Show account links to visitors who are not logged in. -->
                        <li>
                            <a href="/akwaaba/auth/login.php">

                                <!-- Login icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M10 17l5-5-5-5"/>
                                    <path d="M15 12H3"/>
                                    <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('login')); ?>
                            </a>
                        </li>

                        <!-- Provide a registration link to visitors. -->
                        <li>
                            <a href="/akwaaba/auth/register.php">

                                <!-- Add-user icon. -->
                                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="9" cy="8" r="4"/>
                                    <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                                    <path d="M19 8v6"/>
                                    <path d="M16 11h6"/>
                                </svg>

                                <?php echo htmlspecialchars(ak360_translate('register')); ?>
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>
            </nav>

        </div>

    </header>

    <!-- Control the opening and closing of the mobile navigation. -->
    <script>
        // Retrieve the mobile menu button.
        const navigationToggle = document.getElementById('nav-toggle');

        // Retrieve the navigation container.
        const mainNavigation = document.getElementById('main-navigation');

        // Retrieve the hamburger and close icons.
        const menuIcon = navigationToggle.querySelector('.nav-menu-icon');
        const closeIcon = navigationToggle.querySelector('.nav-close-icon');

        // Open or close the navigation menu.
        function toggleNavigation() {

            // Determine whether the menu is currently open.
            const menuIsOpen = navigationToggle.getAttribute('aria-expanded') === 'true';

            // Update the menu's visibility.
            mainNavigation.classList.toggle('is-open', !menuIsOpen);

            // Update the button's accessibility state.
            navigationToggle.setAttribute('aria-expanded', String(!menuIsOpen));

            // Update the button's accessible description.
            navigationToggle.setAttribute(
                'aria-label',
                menuIsOpen ? 'Open navigation menu' : 'Close navigation menu'
            );

            // Show the correct icon.
            menuIcon.style.display = menuIsOpen ? 'block' : 'none';
            closeIcon.style.display = menuIsOpen ? 'none' : 'block';
        }

        // Listen for clicks on the hamburger button.
        navigationToggle.addEventListener('click', toggleNavigation);

        // Close the menu when a navigation link is selected on mobile.
        mainNavigation.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    mainNavigation.classList.remove('is-open');
                    navigationToggle.setAttribute('aria-expanded', 'false');
                    navigationToggle.setAttribute('aria-label', 'Open navigation menu');

                    menuIcon.style.display = 'block';
                    closeIcon.style.display = 'none';
                }
            });
        });

        // Close the menu when the Escape key is pressed.
        document.addEventListener('keydown', function (event) {
            if (
                event.key === 'Escape' &&
                navigationToggle.getAttribute('aria-expanded') === 'true'
            ) {
                toggleNavigation();
                navigationToggle.focus();
            }
        });

        // Reset the menu when returning to the desktop screen size.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                mainNavigation.classList.remove('is-open');
                navigationToggle.setAttribute('aria-expanded', 'false');
                navigationToggle.setAttribute('aria-label', 'Open navigation menu');

                menuIcon.style.display = 'block';
                closeIcon.style.display = 'none';
            }
        });
    </script>

    <!-- Begin the main content area of the page. -->
    <main class="container">
