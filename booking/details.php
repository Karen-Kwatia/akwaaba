<?php
// =====================================================
// booking/details.php - CHOOSE TICKETS AND SEE THE TOTAL
// Owner: Yayra (booking system)
//
// Second step of booking. The tourist arrives here after
// choosing a time on book.php, for example:
//   /akwaaba/booking/details.php?slot_id=3
//
// This page:
//   1. Shows a summary of the chosen tour.
//   2. Asks how many people of each ticket type.
//   3. Shows the total price, updating live (JavaScript).
//   4. Sends the choices to process_booking.php to save.
// Only logged-in tourists can book.
// =====================================================

// Load the shared login system: starts the session and
// gives us $pdo (database) and requireLogin()/requireRole().
require_once __DIR__ . '/../includes/auth.php';

// -----------------------------------------------------
// 1. READ THE CHOSEN SLOT FROM THE ADDRESS
// -----------------------------------------------------

// Read slot_id from the address (?slot_id=3).
// (int) turns it into a whole number, so bad input becomes 0.
$slotId = isset($_GET['slot_id']) ? (int) $_GET['slot_id'] : 0;

// This will hold the slot's details if we find it.
$slot = null;

if ($slotId > 0) {

    // Get everything we need to describe the tour in one query:
    // the slot, its site, its language and its guide's name.
    $slotQuery = $pdo->prepare(
        "SELECT tour_slots.id,
                tour_slots.site_id,
                tour_slots.tour_date,
                tour_slots.start_time,
                tour_slots.capacity,
                -- spaces left = maximum people minus people already booked
                (tour_slots.capacity - tour_slots.booked_count) AS spaces_left,
                sites.name      AS site_name,
                languages.name  AS language_name,
                users.name      AS guide_name
         FROM tour_slots
         INNER JOIN sites     ON tour_slots.site_id = sites.id
         INNER JOIN languages ON tour_slots.language_id = languages.id
         INNER JOIN guides    ON tour_slots.guide_id = guides.id
         INNER JOIN users     ON guides.user_id = users.id
         WHERE tour_slots.id = ?
           -- the tour must still be in the future
           AND (tour_slots.tour_date > CURDATE()
                OR (tour_slots.tour_date = CURDATE()
                    AND tour_slots.start_time > CURTIME()))"
    );

    // Run the query with the slot id filled in safely.
    $slotQuery->execute([$slotId]);

    // Get the slot (or false if it doesn't exist or is in the past).
    $slot = $slotQuery->fetch();
}

// -----------------------------------------------------
// 2. GET THE TICKET PRICES FOR THIS SITE
// -----------------------------------------------------

// Start with an empty list, in case there is no slot.
$prices = [];

if ($slot) {

    // Get each ticket type and its price for the slot's site.
    $priceQuery = $pdo->prepare(
        "SELECT ticket_type, price_ghs
         FROM site_prices
         WHERE site_id = ?
         ORDER BY price_ghs DESC"
    );
    $priceQuery->execute([$slot['site_id']]);
    $prices = $priceQuery->fetchAll();
}

// -----------------------------------------------------
// 3. WORK OUT WHO IS LOOKING AT THE PAGE
// -----------------------------------------------------

// True if someone is logged in (the login system sets user_id).
$isLoggedIn = isset($_SESSION['user_id']);

// True only if the logged-in person has a tourist account.
// Guides and admins can't book tours for themselves.
$isTourist = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'tourist';

// -----------------------------------------------------
// 4. SECURITY TOKEN FOR THE FORM (CSRF PROTECTION)
// -----------------------------------------------------

// A CSRF token is a secret random code stored in the session
// and hidden in our form. When the form is sent, we check the
// codes match. This stops other websites from secretly
// submitting a booking using the tourist's login.
if (empty($_SESSION['csrf_token'])) {

    // random_bytes(32) makes 32 random bytes; bin2hex() turns
    // them into 64 letters and numbers we can put in a form.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -----------------------------------------------------
// 5. ANY ERROR MESSAGE FROM process_booking.php
// -----------------------------------------------------

// If saving the booking failed, process_booking.php stores a
// message in the session and sends the tourist back here.
$errorMessage = $_SESSION['booking_error'] ?? '';

// Remove it so it only shows once.
unset($_SESSION['booking_error']);

// Show the shared header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our booking styles (the shared header only loads style.css). -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<?php if (!$slot): ?>

    <!-- =================================================
         SLOT NOT FOUND (wrong id, or the tour has passed)
         ================================================= -->
    <section class="content-card">
        <h1>Tour not available</h1>
        <p class="booking-message">Sorry, we couldn't find that tour, or it has already taken place.</p>

        <!-- Send them back to choose a site and time again. -->
        <a href="book.php" class="btn btn-primary">Choose a tour</a>
    </section>

<?php else: ?>

    <!-- =================================================
         SUMMARY OF THE CHOSEN TOUR
         ================================================= -->
    <section class="content-card">

        <h1>Your tour</h1>

        <!-- A simple list of the tour details. -->
        <ul class="tour-summary">
            <li><span>Site</span><strong><?php echo htmlspecialchars($slot['site_name']); ?></strong></li>

            <!-- date('l j F Y') shows e.g. "Sunday 6 December 2026". -->
            <li><span>Date</span><strong><?php echo date('l j F Y', strtotime($slot['tour_date'])); ?></strong></li>

            <!-- date('g:i A') shows e.g. "9:00 AM". -->
            <li><span>Time</span><strong><?php echo date('g:i A', strtotime($slot['start_time'])); ?></strong></li>
            <li><span>Language</span><strong><?php echo htmlspecialchars($slot['language_name']); ?></strong></li>
            <li><span>Guide</span><strong><?php echo htmlspecialchars($slot['guide_name']); ?></strong></li>
            <li><span>Spaces left</span><strong><?php echo (int) $slot['spaces_left']; ?> of <?php echo (int) $slot['capacity']; ?></strong></li>
        </ul>

        <!-- Let them go back and pick a different time. -->
        <a href="book.php?site_id=<?php echo (int) $slot['site_id']; ?>">&larr; Choose a different time</a>

    </section>

    <section class="content-card">

        <?php if ((int) $slot['spaces_left'] <= 0): ?>

            <!-- The slot filled up after they chose it. -->
            <h2>This tour is full</h2>
            <p class="booking-message">Sorry, all spaces on this tour have just been taken.</p>
            <a href="book.php?site_id=<?php echo (int) $slot['site_id']; ?>" class="btn btn-primary">Choose another time</a>

        <?php elseif (!$isLoggedIn): ?>

            <!-- Not logged in: ask them to log in or register first. -->
            <h2>Log in to book</h2>
            <p>You need an Akwaaba 360 account to book a tour. After logging in, come back to this page to finish your booking.</p>
            <a href="/akwaaba/auth/login.php" class="btn btn-primary">Log in</a>
            <a href="/akwaaba/auth/register.php" class="btn btn-secondary">Create an account</a>

        <?php elseif (!$isTourist): ?>

            <!-- Logged in, but as a guide or admin. -->
            <h2>Tourist accounts only</h2>
            <p class="booking-message">Only tourist accounts can book tours. Please log in with a tourist account.</p>

        <?php else: ?>

            <!-- =============================================
                 TICKET FORM (logged-in tourists only)
                 ============================================= -->
            <h2>Choose your tickets</h2>

            <!-- Show the error from process_booking.php, if any. -->
            <?php if ($errorMessage): ?>
                <p class="booking-error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <!-- method="post" sends the data hidden in the request,
                 not in the address bar. We always use POST for forms
                 that save or change data. -->
            <form id="ticket-form" action="process_booking.php" method="post"
                  data-spaces-left="<?php echo (int) $slot['spaces_left']; ?>">

                <!-- Hidden fields: which slot, and the security token. -->
                <input type="hidden" name="slot_id" value="<?php echo (int) $slot['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- One row per ticket type, e.g. "Foreigner - GHS 50.00 [ 0 ]". -->
                <div class="ticket-rows">
                    <?php foreach ($prices as $price): ?>
                        <?php
                        // Make a safe id for the input, e.g. "qty-foreigner".
                        $inputId = 'qty-' . strtolower($price['ticket_type']);
                        ?>
                        <div class="ticket-row">

                            <!-- Ticket name and price. -->
                            <label for="<?php echo $inputId; ?>">
                                <strong><?php echo htmlspecialchars(ucfirst($price['ticket_type'])); ?></strong>
                                <span>GHS <?php echo number_format((float) $price['price_ghs'], 2); ?> each</span>
                            </label>

                            <!-- Number box for how many tickets of this type.
                                 name="qty[Ghanaian]" makes PHP receive all
                                 the boxes together as one list called qty.
                                 data-price stores the price for the JavaScript
                                 that works out the total. -->
                            <input type="number"
                                   id="<?php echo $inputId; ?>"
                                   class="ticket-qty"
                                   name="qty[<?php echo htmlspecialchars($price['ticket_type']); ?>]"
                                   value="0"
                                   min="0"
                                   max="<?php echo (int) $slot['spaces_left']; ?>"
                                   data-price="<?php echo htmlspecialchars($price['price_ghs']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Live totals, filled in by booking-details.js. -->
                <p class="ticket-total">
                    People: <strong id="people-count">0</strong>
                    &middot;
                    Total: <strong>GHS <span id="total-price">0.00</span></strong>
                </p>

                <!-- Warnings such as "Only 5 spaces left". -->
                <p id="ticket-message" class="booking-error" aria-live="polite"></p>

                <!-- Disabled until at least one ticket is chosen. -->
                <button type="submit" id="book-button" class="btn btn-primary" disabled>
                    Confirm booking
                </button>

                <!-- Reminder that the server re-checks everything. -->
                <p class="small-print">Prices are checked again when you confirm.</p>
            </form>

            <!-- JavaScript for the live total. Only loaded when the form is shown. -->
            <script src="/akwaaba/assets/js/booking-details.js"></script>

        <?php endif; ?>

    </section>

<?php endif; ?>

<?php
// Show the shared footer.
require_once __DIR__ . '/../includes/footer.php';
?>
