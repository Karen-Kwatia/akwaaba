
<?php
// Load the authentication system to start the session.
require_once __DIR__ . '/includes/auth.php';

// Include the shared website header and navigation.
require_once __DIR__ . '/includes/header.php';
?>

<!-- Create the homepage hero section. -->
<section class="content-card">

    <!-- Introduce the platform to visitors. -->
    <h1>Discover Ghana with Akwaaba 360</h1>

    <!-- Explain the main purpose of the platform. -->
    <p>
        Explore Ghana's beautiful tourist destinations,
        experience its rich culture and connect with
        knowledgeable local tour guides.
    </p>

    <!-- Provide a link to explore tourist destinations. -->
    <a href="/akwaaba/tours/index.php" class="btn btn-primary">
        Explore Ghana
    </a>

    <!-- Allow visitors without accounts to register. -->
    <?php if (!isset($_SESSION['user_id'])): ?>

        <a href="/akwaaba/auth/register.php" class="btn btn-secondary">
            Create an Account
        </a>

    <?php endif; ?>

</section>

<!-- Introduce the platform's main features. -->
<section class="container">

    <h2>Explore What Akwaaba 360 Offers</h2>

    <!-- Display the first platform feature. -->
    <div class="content-card">
        <h3>Virtual Experiences</h3>
        <p>
            Preview tourist destinations through immersive
            videos and virtual experiences.
        </p>
    </div>

    <!-- Display the second platform feature. -->
    <div class="content-card">
        <h3>Book Local Tour Guides</h3>
        <p>
            Find knowledgeable guides and arrange tours
            that match your preferred language.
        </p>
    </div>

    <!-- Display the third platform feature. -->
    <div class="content-card">
        <h3>Share Your Experiences</h3>
        <p>
            Share travel moments, discover visitor stories
            and collect stamps in your Heritage Passport.
        </p>
    </div>

</section>

<?php
// Include the shared website footer.
require_once __DIR__ . '/includes/footer.php';
?>
