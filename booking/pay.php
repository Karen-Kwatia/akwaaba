<?php
// =====================================================
// booking/pay.php - PAYMENT PAGE (DEMO, NO REAL MONEY)
// Owner: Yayra (booking system)
//
// Opened from the "Pay now" button on the ticket page or
// My bookings, for example:
//   /akwaaba/booking/pay.php?booking_id=5
//
// The tourist chooses how to pay:
//   - Mobile Money (MTN, Telecel or AT)
//   - Card
//   - Pay at site (pay the guide on the day)
//
// This is a SIMULATED payment for our school project: it
// checks the details look right, but no money moves and we
// don't connect to Paystack. process_payment.php does the work.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can pay.
requireLogin('../auth/login.php');

// Make sure there is a security token for our form (CSRF protection).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Read which booking to pay for. (int) makes it a safe number.
$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

// -----------------------------------------------------
// 1. GET THE BOOKING (ONLY IF IT'S THIS TOURIST'S)
// -----------------------------------------------------
$bookingQuery = $pdo->prepare(
    "SELECT bookings.id, bookings.num_people, bookings.total_ghs, bookings.status,
            tour_slots.tour_date, tour_slots.start_time,
            sites.name AS site_name,
            -- 1 if the tour hasn't started yet
            (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW()) AS is_upcoming
     FROM bookings
     INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
     INNER JOIN sites      ON tour_slots.site_id = sites.id
     WHERE bookings.id = ? AND bookings.user_id = ?"
);
$bookingQuery->execute([$bookingId, $_SESSION['user_id']]);
$booking = $bookingQuery->fetch();

// Only bookings that are still "pending" (awaiting payment)
// and haven't happened yet can be paid for.
$canPay = $booking && $booking['status'] === 'pending' && $booking['is_upcoming'];

// -----------------------------------------------------
// 2. ANY ERROR FROM process_payment.php (e.g. declined)
// -----------------------------------------------------
$errorMessage = $_SESSION['payment_error'] ?? '';
unset($_SESSION['payment_error']);

// Show the shared header.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our booking styles. -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<?php if (!$canPay): ?>

    <!-- =================================================
         NOTHING TO PAY (not found, already paid, cancelled
         or the tour has passed)
         ================================================= -->
    <section class="content-card">
        <h1>Payment not needed</h1>
        <p class="booking-message">This booking doesn't need paying for. It may already be paid, cancelled or in the past.</p>
        <a href="my_bookings.php" class="btn btn-primary">Back to my bookings</a>
    </section>

<?php else: ?>

    <section class="content-card">

        <!-- Clear warning that this is a demo. -->
        <p class="demo-banner">Demo payment: no real money is charged.</p>

        <h1>Pay for your tour</h1>

        <!-- What they are paying for. -->
        <p>
            <strong><?php echo htmlspecialchars($booking['site_name']); ?></strong>
            &middot; <?php echo date('D j M Y', strtotime($booking['tour_date'])); ?>
            at <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
            &middot; <?php echo (int) $booking['num_people']; ?> people
            &middot; AK360-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
        </p>

        <!-- The amount, big and clear. -->
        <p class="pay-amount">GHS <?php echo number_format((float) $booking['total_ghs'], 2); ?></p>

        <!-- Error from the last attempt, e.g. "Payment declined". -->
        <?php if ($errorMessage): ?>
            <p class="booking-error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <!-- The payment form. It is sent to process_payment.php. -->
        <form id="payment-form" action="process_payment.php" method="post">

            <!-- Hidden fields: which booking, and the security token. -->
            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- ============ CHOOSE A PAYMENT METHOD ============ -->
            <p class="step-label">How would you like to pay?</p>

            <!-- Three radio buttons with the same name, so only one can
                 be chosen. payment.js shows the matching fields below. -->
            <div class="pay-methods">
                <label class="pay-method">
                    <input type="radio" name="method" value="momo" checked>
                    <span><strong>Mobile Money</strong>MTN, Telecel or AT</span>
                </label>
                <label class="pay-method">
                    <input type="radio" name="method" value="card">
                    <span><strong>Card</strong>Visa or Mastercard</span>
                </label>
                <label class="pay-method">
                    <input type="radio" name="method" value="pay_at_site">
                    <span><strong>Pay at site</strong>Pay your guide on the day</span>
                </label>
            </div>

            <!-- ============ MOBILE MONEY FIELDS ============ -->
            <!-- data-method="momo" tells payment.js to show this box
                 only when Mobile Money is chosen. -->
            <div class="pay-fields" data-method="momo">

                <label for="network">Network</label>
                <select id="network" name="network">
                    <option value="MTN">MTN Mobile Money</option>
                    <option value="Telecel">Telecel Cash</option>
                    <option value="AT">AT Money</option>
                </select>

                <!-- inputmode="numeric" shows a number keypad on phones. -->
                <label for="momo-number">Mobile Money number</label>
                <input type="tel" id="momo-number" name="momo_number"
                       inputmode="numeric" maxlength="10" placeholder="024 123 4567">
                <p class="small-print">Demo tip: a number ending in 0000 is declined, to show a failed payment.</p>
            </div>

            <!-- ============ CARD FIELDS ============ -->
            <!-- hidden: payment.js shows this box only when Card is chosen. -->
            <div class="pay-fields" data-method="card" hidden>

                <label for="card-name">Name on card</label>
                <input type="text" id="card-name" name="card_name" autocomplete="cc-name">

                <label for="card-number">Card number</label>
                <input type="text" id="card-number" name="card_number"
                       inputmode="numeric" maxlength="23" placeholder="4242 4242 4242 4242" autocomplete="cc-number">

                <!-- Expiry and CVV side by side. -->
                <div class="pay-row">
                    <div>
                        <label for="card-expiry">Expiry (MM/YY)</label>
                        <input type="text" id="card-expiry" name="card_expiry"
                               inputmode="numeric" maxlength="5" placeholder="08/28" autocomplete="cc-exp">
                    </div>
                    <div>
                        <label for="card-cvv">CVV</label>
                        <input type="password" id="card-cvv" name="card_cvv"
                               inputmode="numeric" maxlength="4" placeholder="123" autocomplete="cc-csc">
                    </div>
                </div>
                <p class="small-print">Demo tip: use 4242 4242 4242 4242 to succeed, or 4000 0000 0000 0002 to see a declined card.</p>
            </div>

            <!-- ============ PAY AT SITE ============ -->
            <div class="pay-fields" data-method="pay_at_site" hidden>
                <p>Your place will be reserved. Please pay your guide in cash or Mobile Money when you arrive.</p>
            </div>

            <!-- Messages from payment.js, e.g. "Enter a 10-digit number". -->
            <p id="payment-message" class="booking-error" aria-live="polite"></p>

            <!-- Submit button. payment.js changes its text for each method. -->
            <!-- data-amount stores the price so payment.js can write
                 e.g. "Pay GHS 105.00" on the button. -->
            <button type="submit" id="pay-button" class="btn btn-primary"
                    data-amount="<?php echo number_format((float) $booking['total_ghs'], 2); ?>">
                Pay GHS <?php echo number_format((float) $booking['total_ghs'], 2); ?>
            </button>
            <a href="confirmation.php?booking_id=<?php echo (int) $booking['id']; ?>" class="btn btn-secondary">Pay later</a>
        </form>

    </section>

    <!-- JavaScript that shows the right fields and checks them. -->
    <script src="/akwaaba/assets/js/payment.js"></script>

<?php endif; ?>

<?php
// Show the shared footer.
require_once __DIR__ . '/../includes/footer.php';
?>
