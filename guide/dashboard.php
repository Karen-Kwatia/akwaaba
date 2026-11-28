
<?php
// =====================================================
// AKWAABA 360
// TOUR GUIDE DASHBOARD
// =====================================================

// Load the reusable authentication functions.
require_once __DIR__ . '/../includes/auth.php';

// Allow only logged-in guides to access this dashboard.
requireRole(['guide'], '../auth/login.php');

// Load the central language translation system.
require_once __DIR__ . '/../includes/language.php';

// =====================================================
// RETRIEVE GUIDE ACCOUNT INFORMATION
// =====================================================

// Get the guide's account role from the session.
$userRole = strtolower($_SESSION['user_role'] ?? 'guide');

// Get the logged-in guide's name safely.
$userName = $_SESSION['user_name'] ?? '';

// Build the translation key for the account role.
$roleTranslationKey = 'role_' . $userRole;

// Translate the account role.
// Fall back to English if the selected language has no translation.
$translatedRole = $languageTranslations[$currentLanguage][$roleTranslationKey]
    ?? $languageTranslations['en'][$roleTranslationKey]
    ?? ucfirst($userRole);

// Safely prepare the guide's name for display.
$safeUserName = htmlspecialchars(
    $userName,
    ENT_QUOTES,
    'UTF-8'
);

// Safely prepare the translated role.
$safeTranslatedRole = htmlspecialchars(
    $translatedRole,
    ENT_QUOTES,
    'UTF-8'
);

// Load the shared website header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =====================================================
   GUIDE DASHBOARD BASE
   ===================================================== */

.guide-dashboard {
    min-height: 75vh;
    padding: 55px 24px 75px;
    background: #F7F6F0;
}

.guide-container {
    width: 100%;
    max-width: 1160px;
    margin: 0 auto;
}

/* =====================================================
   GUIDE WELCOME PANEL
   ===================================================== */

.guide-welcome {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 310px;
    padding: 55px 60px;
    border-radius: 7px;
    background: #12372A;
    color: #FFFFFF;
}

/* Decorative heritage-inspired circular detail. */
.guide-welcome::before {
    content: "";
    position: absolute;
    right: -100px;
    bottom: -200px;
    width: 430px;
    height: 430px;
    border: 1px solid rgba(217, 164, 65, 0.28);
    border-radius: 50%;
    pointer-events: none;
}

.guide-welcome::after {
    content: "";
    position: absolute;
    right: 25px;
    bottom: -145px;
    width: 290px;
    height: 290px;
    border: 1px solid rgba(255, 255, 255, 0.10);
    border-radius: 50%;
    pointer-events: none;
}

/* Small heading above the welcome message. */
.guide-eyebrow {
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

/* Main dashboard heading. */
.guide-welcome h1 {
    position: relative;
    z-index: 1;
    max-width: 750px;
    margin: 0 0 18px;
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: clamp(35px, 5vw, 52px);
    font-weight: 600;
    line-height: 1.2;
}

/* Guide introduction. */
.guide-welcome-description {
    position: relative;
    z-index: 1;
    max-width: 620px;
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    line-height: 1.8;
}

/* =====================================================
   GUIDE WORKSPACE INTRODUCTION
   ===================================================== */

.guide-section {
    margin-top: 55px;
}

.guide-section-heading {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 29px;
    font-weight: 600;
}

.guide-heading-line {
    width: 48px;
    height: 3px;
    margin: 18px 0 25px;
    background: #D9A441;
}

/* =====================================================
   GUIDE INFORMATION CARDS
   ===================================================== */

.guide-information-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

/* Individual information card. */
.guide-information-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 225px;
    padding: 30px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

/* Subtle card hover effect. */
.guide-information-card:hover {
    transform: translateY(-3px);
    border-color: #2E8B70;
    box-shadow: 0 12px 28px rgba(18, 55, 42, 0.07);
}

/* Icon background. */
.guide-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 49px;
    height: 49px;
    margin-bottom: 22px;
    border-radius: 4px;
    background: #E5F2EB;
    color: #12372A;
}

/* SVG icon appearance. */
.guide-card-icon svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Card heading. */
.guide-information-card h3 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
}

/* Card description. */
.guide-information-card p {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.8;
}

/* =====================================================
   GUIDE ACCOUNT DETAILS
   ===================================================== */

.guide-account-panel {
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

.guide-account-heading {
    margin: 0 0 8px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 600;
}

.guide-account-description {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.7;
}

/* Role information display. */
.guide-role-display {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 155px;
    padding: 15px 20px;
    border-left: 3px solid #D9A441;
    background: #F7F6F0;
}

.guide-role-label {
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.guide-role-value {
    color: #12372A;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
}

/* =====================================================
   PREFERENCES ACTION
   ===================================================== */

.guide-preferences {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    margin-top: 22px;
    padding: 27px 32px;
    border: 1px solid #E3E9E4;
    border-radius: 6px;
    background: #FFFFFF;
}

.guide-preferences h3 {
    margin: 0 0 7px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    font-weight: 600;
}

.guide-preferences p {
    margin: 0;
    color: #69736C;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.7;
}

/* Preferences button. */
.guide-preferences-button {
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

.guide-preferences-button:hover {
    border-color: #2E8B70;
    background: #2E8B70;
    color: #FFFFFF;
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */

@media (max-width: 768px) {

    .guide-dashboard {
        padding: 35px 18px 55px;
    }

    .guide-welcome {
        min-height: 275px;
        padding: 38px 30px;
    }

    .guide-welcome h1 {
        font-size: 37px;
    }

    .guide-section {
        margin-top: 42px;
    }

    .guide-information-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .guide-information-card {
        min-height: 190px;
        padding: 25px;
    }

    .guide-account-panel {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px;
    }

    .guide-role-display {
        width: 100%;
        box-sizing: border-box;
    }

    .guide-preferences {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px;
    }

    .guide-preferences-button {
        width: 100%;
        box-sizing: border-box;
    }
}

@media (max-width: 480px) {

    .guide-dashboard {
        padding: 25px 14px 45px;
    }

    .guide-welcome {
        min-height: 255px;
        padding: 30px 23px;
    }

    .guide-welcome h1 {
        font-size: 31px;
    }

    .guide-welcome-description {
        font-size: 13px;
    }

    .guide-section-heading {
        font-size: 25px;
    }

    .guide-account-panel,
    .guide-preferences {
        padding: 22px;
    }
}

</style>

<!-- =====================================================
     GUIDE DASHBOARD CONTENT
     ===================================================== -->

<main class="guide-dashboard">

    <div class="guide-container">

        <!-- =================================================
             PERSONAL WELCOME PANEL
             ================================================= -->

        <section class="guide-welcome">

            <!-- Identify the guide's workspace. -->
            <p class="guide-eyebrow">
                <?php
                echo htmlspecialchars(
                    ak360_translate('guide_dashboard_title'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>

            <!-- Display a personalised greeting. -->
            <h1>
                <?php
                echo htmlspecialchars(
                    ak360_translate('guide_welcome'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                <?php echo $safeUserName; ?>!
            </h1>

            <!-- Introduce the guide's workspace. -->
            <p class="guide-welcome-description">
                Welcome to your professional guide workspace.
                This is your starting point for managing your account
                and accessing your guide-related activities on Akwaaba 360.
            </p>

        </section>

        <!-- =================================================
             GUIDE WORKSPACE INFORMATION
             ================================================= -->

        <section class="guide-section">

            <h2 class="guide-section-heading">
                Your Guide Workspace
            </h2>

            <div class="guide-heading-line"></div>

            <div class="guide-information-grid">

                <!-- Guide activities information. -->
                <article class="guide-information-card">

                    <div class="guide-card-icon">

                        <!-- Compass icon. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="m15.5 8.5-2.2 4.8-4.8 2.2 2.2-4.8 4.8-2.2Z"/>
                        </svg>

                    </div>

                    <h3>Guide Activities</h3>

                    <p>
                        Your guide dashboard is designed to provide access
                        to your tourism-related activities as the platform's
                        guide-management features are connected.
                    </p>

                </article>

                <!-- Guide account information. -->
                <article class="guide-information-card">

                    <div class="guide-card-icon">

                        <!-- Profile icon. -->
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>

                    </div>

                    <h3>Professional Profile</h3>

                    <p>
                        Your account identifies you as a registered
                        tour guide on Akwaaba 360. Keep your account
                        preferences up to date.
                    </p>

                </article>

            </div>

        </section>

        <!-- =================================================
             GUIDE ACCOUNT DETAILS
             ================================================= -->

        <section class="guide-account-panel">

            <div>

                <h2 class="guide-account-heading">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('guide_account_role'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </h2>

                <p class="guide-account-description">
                    Your current account access level on Akwaaba 360.
                </p>

            </div>

            <!-- Display the translated account role. -->
            <div class="guide-role-display">

                <span class="guide-role-label">
                    <?php
                    echo htmlspecialchars(
                        ak360_translate('guide_account_role'),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </span>

                <span class="guide-role-value">
                    <?php echo $safeTranslatedRole; ?>
                </span>

            </div>

        </section>

        <!-- =================================================
             GUIDE PREFERENCES
             ================================================= -->

        <section class="guide-preferences">

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

            <!-- Link to the existing preferences page. -->
            <a
                href="../auth/preferences.php"
                class="guide-preferences-button"
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
