
<?php
// Load the authentication system and start the session.
require_once __DIR__ . '/includes/auth.php';

// Include the shared website header and translation system.
require_once __DIR__ . '/includes/header.php';
?>

<style>

/* Give the homepage a clean, warm background without an image. */
.homepage-background {
    background: #FFF8ED;
    min-height: 100vh;
    padding: 35px 5%;
}

/* Arrange the main homepage welcome section. */
.home-hero {
    min-height: 430px;
    display: flex;
    align-items: center;
    padding: 50px 3%;
}

/* Limit the width of the welcome message. */
.home-hero-content {
    max-width: 680px;
}

/* Style the main homepage heading. */
.home-hero h1 {
    color: #073B32;
    font-size: clamp(2.5rem, 5vw, 4.5rem);
    line-height: 1.15;
    margin: 15px 0;
}

/* Style the introductory paragraph. */
.home-hero-content p:not(.section-label) {
    color: #333333;
    font-size: 1.15rem;
    line-height: 1.8;
    max-width: 600px;
}

/* Highlight the introductory label. */
.home-hero .section-label {
    color: #E66B43;
    font-weight: bold;
    letter-spacing: 2px;
}

/* Arrange the homepage feature cards. */
.home-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin: 35px 0;
}

/* Make the features heading span the full section width. */
.home-section-heading {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: 15px;
}

/* Style the features section heading. */
.home-section-heading h2 {
    color: #073B32;
}

.home-section-heading p {
    color: #444444;
}

/* Highlight the features section label. */
.home-section-heading .section-label {
    color: #E66B43;
    font-weight: bold;
    letter-spacing: 2px;
}

/* Give feature cards a solid, light background. */
.home-features .content-card {
    background: #FFFFFF;
    border: 1px solid #E8DFD2;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

/* Make feature headings dark green. */
.home-features .content-card h3 {
    color: #073B32;
}

/* Make feature descriptions readable. */
.home-features .content-card p {
    color: #333333;
    line-height: 1.7;
}

/* Adjust the homepage for mobile screens. */
@media (max-width: 768px) {

    .homepage-background {
        padding: 20px 5%;
    }

    .home-hero {
        min-height: 400px;
        padding: 35px 10px;
    }

    .home-features {
        grid-template-columns: 1fr;
    }
}

</style>

<!-- Begin the homepage content. -->
<div class="homepage-background">

    <!-- Display the main welcome section. -->
    <section class="home-hero">

        <div class="home-hero-content">

            <?php if (isset($_SESSION['user_id'])): ?>

                <!-- Welcome logged-in users personally. -->
                <p class="section-label">
                    <?php echo htmlspecialchars(ak360_translate('welcome_label')); ?>
                </p>

                <h1>
                    <?php echo htmlspecialchars(ak360_translate('welcome_back')); ?>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                </h1>

            <?php else: ?>

                <!-- Introduce Akwaaba 360 to visitors. -->
                <p class="section-label">
                    <?php echo htmlspecialchars(ak360_translate('hero_label')); ?>
                </p>

                <h1>
                    <?php echo htmlspecialchars(ak360_translate('hero_title')); ?>
                </h1>

            <?php endif; ?>

            <!-- Explain what visitors can do on the platform. -->
            <p>
                <?php echo htmlspecialchars(ak360_translate('hero_description')); ?>
            </p>

            <!-- Provide access to the destinations page. -->
            <a href="/akwaaba/tours/index.php" class="btn btn-primary">
                <?php echo htmlspecialchars(ak360_translate('explore_ghana')); ?>
            </a>

            <?php if (!isset($_SESSION['user_id'])): ?>

                <!-- Allow visitors to create an account. -->
                <a href="/akwaaba/auth/register.php" class="btn btn-secondary">
                    <?php echo htmlspecialchars(ak360_translate('create_account')); ?>
                </a>

            <?php endif; ?>

        </div>

    </section>

    <!-- Display the platform's main features. -->
    <section class="home-features">

        <!-- Introduce the features section. -->
        <div class="home-section-heading">

            <p class="section-label">
                <?php echo htmlspecialchars(ak360_translate('journey_label')); ?>
            </p>

            <h2>
                <?php echo htmlspecialchars(ak360_translate('features_title')); ?>
            </h2>

            <p>
                <?php echo htmlspecialchars(ak360_translate('features_description')); ?>
            </p>

        </div>

        <!-- Describe the virtual experiences feature. -->
        <div class="content-card">

            <h3>
                <?php echo htmlspecialchars(ak360_translate('virtual_experiences')); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars(ak360_translate('virtual_description')); ?>
            </p>

        </div>

        <!-- Describe the local tour guides feature. -->
        <div class="content-card">

            <h3>
                <?php echo htmlspecialchars(ak360_translate('local_guides')); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars(ak360_translate('guides_description')); ?>
            </p>

        </div>

        <!-- Describe the experience-sharing feature. -->
        <div class="content-card">

            <h3>
                <?php echo htmlspecialchars(ak360_translate('share_experiences')); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars(ak360_translate('share_description')); ?>
            </p>

        </div>

    </section>

</div>

<?php
// Include the shared website footer.
require_once __DIR__ . '/includes/footer.php';
?>
