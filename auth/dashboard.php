
<?php
// =====================================================
// AKWAABA 360
// TOURIST DASHBOARD
// =====================================================

// Load the authentication system and restore the user's session.
require_once __DIR__ . '/../includes/auth.php';

// Make sure only logged-in users can access this dashboard.
requireLogin('login.php');

// Load the central language translation system.
require_once __DIR__ . '/../includes/language.php';

// =====================================================
// RETRIEVE USER ACCOUNT INFORMATION
// =====================================================

// Get the user's account role from the session.
$userRole = strtolower($_SESSION['user_role'] ?? 'tourist');

// Get the logged-in user's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Build the translation key for the user's role.
$roleTranslationKey = 'role_' . $userRole;

// Translate the user's role.
// Fall back to English if the selected language has no translation.
$translatedRole = $languageTranslations[$currentLanguage][$roleTranslationKey]
    ?? $languageTranslations['en'][$roleTranslationKey]
    ?? ucfirst($userRole);

// Escape the user's name before displaying it.
$safeUserName = htmlspecialchars(
    $userName,
    ENT_QUOTES,
    'UTF-8'
);

// Escape the translated role.
$safeTranslatedRole = htmlspecialchars(
    $translatedRole,
    ENT_QUOTES,
    'UTF-8'
);

// =====================================================
// LOAD THE SHARED WEBSITE HEADER
// =====================================================

require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =====================================================
   TOURIST DASHBOARD
   ===================================================== */

/* Main dashboard background and spacing. */
.dashboard-page {
    min-height: 75vh;
    padding: 55px 24px 75px;
    background: #F7F6F0;
}

/* Keep dashboard content centred. */
.dashboard-container {
    width: 100%;
    max-width: 1160px;
    margin: 0 auto;
}

/* =====================================================
   WELCOME SECTION
   ===================================================== */

.dashboard-welcome {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 300px;
    padding: 55px 60px;
    border-radius: 8px;
    background: #12372A;
    color: #FFFFFF;
}

/* Decorative circle in the welcome section. */
.dashboard-welcome::after {
    content: "";
    position: absolute;
    right: -95px;
    bottom: -180px;
    width: 390px;
    height: 390px;
    border: 1px solid rgba(217, 164, 65, 0.25);
    border-radius: 50%;
    pointer-events: none;
}

/* Second decorative circle. */
.dashboard-welcome::before {
    content: "";
    position: absolute;
    right: 10px;
    bottom: -130px;
    width: 260px;
    height: 260px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 50%;
    pointer-events: none;
}

/* Small welcome section label. */
.dashboard-eyebrow {
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

/* Main welcome heading. */
.dashboard-welcome h1 {
    position: relative;
    z-index: 1;
    max-width: 750px;
    margin: 0 0 18px;
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: clamp(34px, 5vw, 51px);
    font-weight: 600;
    line-height: 1.18;
}

/* Welcome description. */
.dashboard-intro {
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
   DASHBOARD SECTION INTRODUCTIONS
   ===================================================== */

.dashboard-section {
    margin-top: 55px;
}

/* Section heading. */
.dashboard-section h2 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 29px;
    font-weight: 600;
}

/* Section description. */
.dashboard-section-description {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    line-height: 1.7;
}

/* Small decorative line under section headings. */
.dashboard-heading-line {
    width: 48px;
    height: 3px;
    margin: 18px 0 25px;
    background: #D9A441;
}

/* =====================================================
   DASHBOARD ACTION CARDS
   ===================================================== */

.dashboard-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

/* Individual action card. */
.dashboard-action-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 235px;
    padding: 30px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
    transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
}

/* Hover effect for action cards. */
.dashboard-action-card:hover {
    border-color: #2E8B70;
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(18, 55, 42, 0.07);
}

/* Icon container. */
.dashboard-action-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: 22px;
    border-radius: 4px;
    background: #E5F2EB;
    color: #12372A;
}

/* SVG icons. */
.dashboard-action-icon svg {
    width: 23px;
    height: 23px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Action card heading. */
.dashboard-action-card h3 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
}

/* Action card description. */
.dashboard-action-card p {
    margin: 0 0 22px;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.7;
}

/* Action card link. */
.dashboard-action-link {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-top: auto;
    color: #2E8B70;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.2s;
}

/* Action card link hover. */
.dashboard-action-link:hover {
    color: #12372A;
}

/* Arrow beside the action link. */
.dashboard-action-link span {
    font-size: 18px;
    transition: transform 0.2s;
}

.dashboard-action-link:hover span {
    transform: translateX(4px);
}

/* =====================================================
   ACCOUNT INFORMATION
   ===================================================== */

.dashboard-account {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    margin-top: 55px;
    padding: 28px 32px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
}

/* Account information heading. */
.dashboard-account h2 {
    margin: 0 0 7px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 23px;
    font-weight: 600;
}

/* Account information description. */
.dashboard-account-description {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
}

/* Account role display. */
.dashboard-role {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 150px;
    padding: 14px 19px;
    border-left: 3px solid #D9A441;
    background: #F7F6F0;
}

/* Account type label. */
.dashboard-role-label {
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Account role value. */
.dashboard-role-value {
    color: #12372A;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */

@media (max-width: 768px) {

    .dashboard-page {
        padding: 35px 18px 55px;
    }

    .dashboard-welcome {
        min-height: 270px;
        padding: 38px 30px;
    }

    .dashboard-welcome h1 {
        font-size: 36px;
    }

    .dashboard-section {
        margin-top: 42px;
    }

    .dashboard-actions {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .dashboard-action-card {
        min-height: 210px;
        padding: 25px;
    }

    .dashboard-account {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px;
    }

    .dashboard-role {
        width: 100%;
        box-sizing: border-box;
    }
}

@media (max-width: 480px) {

    .dashboard-page {
        padding: 25px 14px 45px;
    }

    .dashboard-welcome {
        min-height: 260px;
        padding: 30px 23px;
    }

    .dashboard-welcome h1 {
        font-size: 31px;
    }

    .dashboard-intro {
        font-size: 13px;
    }

    .dashboard-section h2 {
        font-size: 25px;
    }

    .dashboard-account {
        padding: 22px;
    }
}

</style>

<!-- =====================================================
     TOURIST DASHBOARD CONTENT
     ===================================================== -->

<main class="dashboard-page">

    <div class="dashboard-container">

        <!-- =================================================
             PERSONAL WELCOME SECTION
             ================================================= -->

        <section class="dashboard-welcome">

            <!-- Identify the user's personal travel space. -->
            <p class="dashboard-eyebrow">
                <?php
                echo htmlspecialchars(
                    ak360_translate('dashboard_welcome_label'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>

            <!-- Personalise the greeting using the user's name. -->
            <h1>
                <?php
                echo htmlspecialchars(
                    ak360_translate('dashboard_welcome'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                <?php echo $safeUserName; ?>!
            </h1>

            <!-- Introduce the platform and encourage exploration. -->
            <p class="dashboard-intro">
                <?php
                echo htmlspecialchars(
                    ak360_translate('dashboard_description'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>

        </section>

        <!-- =================================================
             QUICK ACTIONS
             ================================================= -->

        <section class="dashboard-section">

            <!-- Introduce the available dashboard actions. -->
            <h2>
                <?php
                echo htmlspecialchars(
                    ak360_translate('your_account'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </h2>

            <div class="dashboard-heading-line"></div>

            <!-- Display the user's available actions. -->
            <div class="dashboard-actions">

                <!-- Explore destinations action. -->
                <article class="dashboard-action-card">

                    <div class="dashboard-action-icon">
                        <!-- Location pin icon. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                    </div>

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('explore_destinations'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </h3>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('dashboard_description'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </p>

                    <a
                        href="../tours/index.php"
                        class="dashboard-action-link"
                    >
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('explore_ghana'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                </article>

                <!-- Manage preferences action. -->
                <article class="dashboard-action-card">

                    <div class="dashboard-action-icon">
                        <!-- Settings icon. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.2 1.7 1.7 0 0 0-.8 1.5v.2h-3.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.1l-.2.1-1.7-2.9.1-.1A1.7 1.7 0 0 0 7.5 15a1.7 1.7 0 0 0-1.4-1H6v-3.4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.1-1.8l-.1-.2 2.9-1.7.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.4V4h3.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.1l.2-.1 1.7 2.9-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.4 1h.2v3.4h-.2a1.7 1.7 0 0 0-1.6.2Z"/>
                        </svg>
                    </div>

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('manage_preferences'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </h3>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('preferences_title'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </p>

                    <a
                        href="preferences.php"
                        class="dashboard-action-link"
                    >
                        <?php
                        echo htmlspecialchars(
                            ak360_translate('manage_preferences'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                </article>

            </div>

        </section>

        <!-- =================================================
             ACCOUNT INFORMATION
             ================================================= -->

        <section class="dashboard-account">

            <div>
                <h2>
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('your_account'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </h2>

                <p class="dashboard-account-description">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('account_type'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </p>
            </div>

            <!-- Display the user's translated account role. -->
            <div class="dashboard-role">

                <span class="dashboard-role-label">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('account_type'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </span>

                <span class="dashboard-role-value">
                    <?php echo $safeTranslatedRole; ?>
                </span>

            </div>

        </section>

    </div>

</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
