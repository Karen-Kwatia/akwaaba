<?php
// =====================================================
// booking/book.php - CHOOSE A LANGUAGE, DATE AND TIME
// Owner: Yayra (booking system)
//
// This is the first step of booking a tour.
// A tourist arrives here from a site page, for example:
//   /akwaaba/booking/book.php?site_id=1
// They pick a language, then a date, then a time slot.
// Then they press "Continue" to choose tickets (next step).
//
// If no site is given in the address, we show a list of
// sites to choose from instead.
// =====================================================

// Load the shared login system. This starts the session,
// so the header can show whether someone is logged in.
// It also gives us $pdo, our database connection.
require_once __DIR__ . '/../includes/auth.php';

// -----------------------------------------------------
// 1. WORK OUT WHICH SITE THE TOURIST WANTS TO BOOK
// -----------------------------------------------------

// Read site_id from the address (?site_id=1).
// (int) turns it into a whole number, so text or
// harmful input becomes 0 and can't break our SQL.
$siteId = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;

// This will hold the chosen site's details, if we find it.
$site = null;

// Only look the site up if a real number was given.
if ($siteId > 0) {

    // Prepare a query to find the site with this id.
    // The ? is a placeholder: PDO fills it in safely.
    $siteQuery = $pdo->prepare(
        "SELECT id, name, region, description, opening_hours
         FROM sites
         WHERE id = ?"
    );

    // Run the query, putting $siteId in place of the ?.
    $siteQuery->execute([$siteId]);

    // Get the matching site (or false if none exists).
    $site = $siteQuery->fetch();
}

// -----------------------------------------------------
// 2. IF WE FOUND THE SITE, GET ITS PRICES AND LANGUAGES
// -----------------------------------------------------

// Start with empty lists, in case there is no site.
$prices = [];
$languages = [];

if ($site) {

    // Get the ticket prices for this site
    // (Ghanaian, foreigner, student, child).
    $priceQuery = $pdo->prepare(
        "SELECT ticket_type, price_ghs
         FROM site_prices
         WHERE site_id = ?
         ORDER BY price_ghs DESC"
    );
    $priceQuery->execute([$siteId]);

    // Store all price rows in a list.
    $prices = $priceQuery->fetchAll();

    // Get only the languages that have at least one
    // upcoming tour slot with free spaces at this site.
    // That way the tourist never picks a language
    // that has no tours available.
    $languageQuery = $pdo->prepare(
        "SELECT DISTINCT languages.id, languages.name
         FROM tour_slots
         INNER JOIN languages ON tour_slots.language_id = languages.id
         WHERE tour_slots.site_id = ?
           -- the slot must still have space left
           AND tour_slots.booked_count < tour_slots.capacity
           -- the slot must be in the future
           AND (tour_slots.tour_date > CURDATE()
                OR (tour_slots.tour_date = CURDATE()
                    AND tour_slots.start_time > CURTIME()))
         ORDER BY languages.name"
    );
    $languageQuery->execute([$siteId]);

    // Store the available languages in a list.
    $languages = $languageQuery->fetchAll();
}

// -----------------------------------------------------
// 3. IF THERE IS NO VALID SITE, GET ALL SITES TO CHOOSE
// -----------------------------------------------------

// This list is only used when no site was chosen.
$allSites = [];

if (!$site) {

    // Get every site's id, name and region, A to Z.
    // query() is fine here because there is no user input.
    $allSites = $pdo->query(
        "SELECT id, name, region FROM sites ORDER BY name"
    )->fetchAll();
}

// Show the shared header and navigation at the top of the page.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our own booking styles. The shared header only loads
     the main style.css, so each feature adds its own file here
     instead of editing the shared header. -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<?php if (!$site): ?>

    <!-- =================================================
         NO SITE CHOSEN: show a list of sites to pick from
         ================================================= -->
    <section class="content-card">

        <h1>Book a Tour</h1>

        <!-- If the address had a site_id that doesn't exist, say so. -->
        <?php if ($siteId > 0): ?>
            <p class="booking-message">Sorry, we couldn't find that site. Please choose one below.</p>
        <?php else: ?>
            <p>Choose the site you would like to visit.</p>
        <?php endif; ?>

        <!-- One link per site. Each link opens this same page
             with that site's id, e.g. book.php?site_id=2 -->
        <ul class="site-choice-list">
            <?php foreach ($allSites as $oneSite): ?>
                <li>
                    <a class="site-choice" href="book.php?site_id=<?php echo (int) $oneSite['id']; ?>">
                        <!-- htmlspecialchars() makes text safe to show,
                             so stored text can't run as code in the page. -->
                        <strong><?php echo htmlspecialchars($oneSite['name']); ?></strong>
                        <span><?php echo htmlspecialchars($oneSite['region']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

    </section>

<?php else: ?>

    <!-- =================================================
         SITE CHOSEN: show its details and the slot picker
         ================================================= -->
    <section class="content-card">

        <!-- Site name, region and opening hours. -->
        <h1>Book a Tour: <?php echo htmlspecialchars($site['name']); ?></h1>
        <p class="site-meta">
            <?php echo htmlspecialchars($site['region']); ?>
            &middot;
            <?php echo htmlspecialchars($site['opening_hours'] ?? ''); ?>
        </p>

        <!-- Ticket prices, so the tourist knows the cost before choosing. -->
        <?php if ($prices): ?>
            <h2>Ticket prices</h2>
            <ul class="price-list">
                <?php foreach ($prices as $price): ?>
                    <li>
                        <!-- ucfirst() capitalises the first letter, e.g. "student" becomes "Student". -->
                        <span><?php echo htmlspecialchars(ucfirst($price['ticket_type'])); ?></span>
                        <!-- number_format(..., 2) always shows two decimals, e.g. 20.00 -->
                        <strong>GHS <?php echo number_format((float) $price['price_ghs'], 2); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </section>

    <section class="content-card">

        <h2>Choose your tour</h2>

        <?php if (!$languages): ?>

            <!-- No upcoming slots in any language: nothing to book yet. -->
            <p class="booking-message">
                There are no tours available to book at this site right now. Please check back soon.
            </p>

        <?php else: ?>

            <!-- This form sends the chosen slot to the next step
                 (choosing tickets), which we build in the next branch. -->
            <form id="slot-form" action="details.php" method="get">

                <!-- STEP 1: LANGUAGE -->
                <label for="language">1. Tour language</label>

                <!-- data-site-id stores the site id on the element,
                     so our JavaScript knows which site to ask about. -->
                <select id="language" name="language_id" data-site-id="<?php echo (int) $site['id']; ?>" required>

                    <!-- Placeholder option shown before a choice is made. -->
                    <option value="">Choose a language</option>

                    <!-- One option per available language. -->
                    <?php foreach ($languages as $language): ?>
                        <option value="<?php echo (int) $language['id']; ?>">
                            <?php echo htmlspecialchars($language['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- STEP 2: DATE. Hidden until a language is chosen.
                     JavaScript fills in one button per available date. -->
                <div id="date-step" class="booking-step" hidden>
                    <p class="step-label">2. Choose a date</p>
                    <div id="date-options" class="option-grid"></div>
                </div>

                <!-- STEP 3: TIME. Hidden until a date is chosen.
                     JavaScript fills in one button per time slot. -->
                <div id="time-step" class="booking-step" hidden>
                    <p class="step-label">3. Choose a time</p>
                    <div id="time-options" class="option-grid"></div>
                </div>

                <!-- Area for messages such as "Loading..." or errors. -->
                <p id="slot-message" class="booking-message" aria-live="polite"></p>

                <!-- Stores the chosen slot's id. It is hidden because the
                     tourist chooses it by clicking a time button, not typing. -->
                <input type="hidden" id="slot-id" name="slot_id" value="">

                <!-- Continue button. Disabled until a time slot is chosen. -->
                <button type="submit" id="continue-button" class="btn btn-primary" disabled>
                    Continue
                </button>

            </form>

        <?php endif; ?>

    </section>

    <!-- Load the JavaScript that makes the date and time
         buttons work. It only runs on this page. -->
    <script src="/akwaaba/assets/js/booking.js"></script>

<?php endif; ?>

<?php
// Show the shared footer at the bottom of the page.
require_once __DIR__ . '/../includes/footer.php';
?>
