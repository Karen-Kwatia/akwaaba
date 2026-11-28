
<?php
// =====================================================
// AKWAABA 360
// ADMINISTRATOR DASHBOARD
// =====================================================

// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in administrators to access this page.
requireRole(['admin'], '../auth/login.php');

// Load the central language translation system.
require_once __DIR__ . '/../includes/language.php';

// =====================================================
// RETRIEVE ADMINISTRATOR ACCOUNT INFORMATION
// =====================================================

// Get the logged-in administrator's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Safely prepare the administrator's name for display.
$safeUserName = htmlspecialchars(
    $userName,
    ENT_QUOTES,
    'UTF-8'
);

// Include the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =====================================================
   ADMIN DASHBOARD BASE
   ===================================================== */

.admin-dashboard {
    min-height: 75vh;
    padding: 55px 24px 75px;
    background: #F7F6F0;
}

.admin-container {
    width: 100%;
    max-width: 1160px;
    margin: 0 auto;
}

/* =====================================================
   ADMIN WELCOME PANEL
   ===================================================== */

.admin-welcome {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 300px;
    padding: 55px 60px;
    border-radius: 7px;
    background: #12372A;
    color: #FFFFFF;
}

.admin-welcome::before {
    content: "";
    position: absolute;
    right: -100px;
    bottom: -190px;
    width: 420px;
    height: 420px;
    border: 1px solid rgba(217, 164, 65, 0.28);
    border-radius: 50%;
    pointer-events: none;
}

.admin-welcome::after {
    content: "";
    position: absolute;
    right: 25px;
    bottom: -140px;
    width: 280px;
    height: 280px;
    border: 1px solid rgba(255, 255, 255, 0.10);
    border-radius: 50%;
    pointer-events: none;
}

.admin-eyebrow {
    position: relative;
    z-index: 1;
    margin: 0 0 18px;
    color: #D9A441;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}

.admin-welcome h1 {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0 0 18px;
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: clamp(35px, 5vw, 52px);
    font-weight: 600;
    line-height: 1.2;
}

.admin-welcome-description {
    position: relative;
    z-index: 1;
    max-width: 650px;
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    line-height: 1.8;
}

/* =====================================================
   ADMIN SECTION HEADINGS
   ===================================================== */

.admin-section {
    margin-top: 55px;
}

.admin-section-heading {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 29px;
    font-weight: 600;
}

.admin-section-description {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    line-height: 1.8;
}

.admin-heading-line {
    width: 48px;
    height: 3px;
    margin: 18px 0 25px;
    background: #D9A441;
}

/* =====================================================
   ADMIN MANAGEMENT AREAS
   ===================================================== */

.admin-management-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.admin-management-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 215px;
    padding: 30px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
    color: inherit;
    text-decoration: none;
}

/* Subtle hover effect for management cards. */
.admin-management-card:hover {
    transform: translateY(-3px);
    border-color: #2E8B70;
    box-shadow: 0 12px 28px rgba(18, 55, 42, 0.07);
}

/* Keyboard accessibility for the clickable card. */
.admin-management-card:focus-visible {
    outline: 3px solid #D9A441;
    outline-offset: 4px;
}

/* Management card icon. */
.admin-card-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 49px;
    height: 49px;
    margin-bottom: 22px;
    border-radius: 4px;
    background: #E5F2EB;
    color: #12372A;
}

.admin-card-icon svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.admin-management-card h3 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
}

.admin-management-card p {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.8;
}

/* =====================================================
   ADMIN ACCOUNT PANEL
   ===================================================== */

.admin-account-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    margin-top: 55px;
    padding: 30px 32px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
}

.admin-account-panel h2 {
    margin: 0 0 8px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 600;
}

.admin-account-description {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.7;
}

.admin-role-display {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 155px;
    padding: 15px 20px;
    border-left: 3px solid #D9A441;
    background: #F7F6F0;
}

.admin-role-label {
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.admin-role-value {
    color: #12372A;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
}

/* =====================================================
   ADMIN PREFERENCES
   ===================================================== */

.admin-preferences {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    margin-top: 22px;
    padding: 27px 32px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
}

.admin-preferences h3 {
    margin: 0 0 7px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    font-weight: 600;
}

.admin-preferences p {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.7;
}

.admin-preferences-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    flex-shrink: 0;
    min-height: 44px;
    padding: 0 22px;
    border: 1px solid #12372A;
    border-radius: 3px;
    background: #12372A;
    color: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
}

.admin-preferences-button:hover {
    border-color: #2E8B70;
    background: #2E8B70;
    color: #FFFFFF;
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */

@media (max-width: 768px) {

    .admin-dashboard {
        padding: 35px 18px 55px;
    }

    .admin-welcome {
        min-height: 275px;
        padding: 38px 30px;
    }

    .admin-welcome h1 {
        font-size: 37px;
    }

    .admin-section {
        margin-top: 42px;
    }

    .admin-management-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .admin-management-card {
        min-height: 185px;
        padding: 25px;
    }

    .admin-account-panel {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px;
    }

    .admin-role-display {
        width: 100%;
        box-sizing: border-box;
    }

    .admin-preferences {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px;
    }

    .admin-preferences-button {
        width: 100%;
        box-sizing: border-box;
    }
}

@media (max-width: 480px) {

    .admin-dashboard {
        padding: 25px 14px 45px;
    }

    .admin-welcome {
        min-height: 255px;
        padding: 30px 23px;
    }

    .admin-welcome h1 {
        font-size: 31px;
    }

    .admin-welcome-description {
        font-size: 13px;
    }

    .admin-section-heading {
        font-size: 25px;
    }

    .admin-account-panel,
    .admin-preferences {
        padding: 22px;
    }
}

</style>

<!-- =====================================================
     ADMIN DASHBOARD CONTENT
     ===================================================== -->

<main class="admin-dashboard">

    <div class="admin-container">

        <!-- =================================================
             ADMINISTRATOR WELCOME PANEL
             ================================================= -->

        <section class="admin-welcome">

            <p class="admin-eyebrow">
                <?php
                echo htmlspecialchars(
                    ak360_translate('admin_dashboard_title'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>

            <h1>
                <?php
                echo htmlspecialchars(
                    ak360_translate('admin_welcome'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                <?php echo $safeUserName; ?>!
            </h1>

            <p class="admin-welcome-description">
                <?php
                echo htmlspecialchars(
                    ak360_translate('admin_description'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>

        </section>

        <!-- =================================================
             PLATFORM MANAGEMENT OVERVIEW
             ================================================= -->

        <section class="admin-section">

            <h2 class="admin-section-heading">
                Platform Management
            </h2>

            <p class="admin-section-description">
                Your administrative workspace for overseeing
                the Akwaaba 360 tourism platform.
            </p>

            <div class="admin-heading-line"></div>

            <div class="admin-management-grid">

                <!-- User management card.
                     This card now opens the User Management page. -->
                <a
                    href="users.php"
                    class="admin-management-card"
                    aria-label="Open User Management"
                >

                    <div class="admin-card-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M20 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>

                    </div>

                    <h3>User Management</h3>

                    <p>
                        An administrative area for overseeing
                        registered tourists, tour guides and
                        other platform accounts.
                    </p>

                </a>

                <!-- Destination management area. -->
                <article class="admin-management-card">

                    <div class="admin-card-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>

                    </div>

                    <h3>Destination Management</h3>

                    <p>
                        An overview of the destinations featured
                        on Akwaaba 360 and the information
                        presented to visitors.
                    </p>

                </article>

                <!-- Booking management area. -->
                <article class="admin-management-card">

                    <div class="admin-card-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path d="M16 3v4M8 3v4M3 11h18"/>
                        </svg>

                    </div>

                    <h3>Booking Oversight</h3>

                    <p>
                        An administrative area for monitoring
                        tourist reservations and guided-tour
                        booking activities.
                    </p>

                </article>

                <!-- Platform content management area. -->
                <article class="admin-management-card">

                    <div class="admin-card-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M7 8h10M7 12h10M7 16h6"/>
                        </svg>

                    </div>

                    <h3>Content Management</h3>

                    <p>
                        An overview of the tourism content,
                        destination media and visitor contributions
                        featured across the platform.
                    </p>

                </article>

            </div>

        </section>

        <!-- =================================================
             ADMINISTRATOR ACCOUNT INFORMATION
             ================================================= -->

        <section class="admin-account-panel">

            <div>

                <h2>
                    Administrator Account
                </h2>

                <p class="admin-account-description">
                    Your current account provides administrative
                    access to the Akwaaba 360 platform.
                </p>

            </div>

            <div class="admin-role-display">

                <span class="admin-role-label">
                    Account Type
                </span>

                <span class="admin-role-value">
                    Administrator
                </span>

            </div>

        </section>

        <!-- =================================================
             ADMINISTRATOR PREFERENCES
             ================================================= -->

        <section class="admin-preferences">

            <div>

                <h3>
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('preferences'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </h3>

                <p>
                    Manage your preferred website language and currency.
                </p>

            </div>

            <a
                href="../auth/preferences.php"
                class="admin-preferences-button"
            >
                <?php
                echo htmlspecialchars(
                    ak360_translate('preferences'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

                <span aria-hidden="true">&rarr;</span>
            </a>

        </section>

    </div>

</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
