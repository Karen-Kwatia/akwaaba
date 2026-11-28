<?php
// =====================================================
// booking/confirmation.php - BOOKING CONFIRMATION AND TICKET
// Owner: Yayra (booking system)
//
// Third step of booking. After process_booking.php saves a
// booking, it sends the tourist here, for example:
//   /akwaaba/booking/confirmation.php?booking_id=3
//
// This page shows:
//   - a booking reference (e.g. AK360-000003)
//   - the tour details and how much it costs
//   - a QR code ticket the guide scans at the site
// Tourists can only see THEIR OWN bookings.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can see a booking; others go to login.
requireLogin('../auth/login.php');

// -----------------------------------------------------
// 1. READ WHICH BOOKING TO SHOW
// -----------------------------------------------------

// Read booking_id from the address. (int) makes it a safe number.
$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

// -----------------------------------------------------
// 2. GET THE BOOKING, BUT ONLY IF IT BELONGS TO THIS USER
// -----------------------------------------------------

// Get the booking with its slot, site, language and guide.
// "bookings.user_id = ?" makes sure people can't see someone
// else's booking just by changing the number in the address.
$bookingQuery = $pdo->prepare(
    "SELECT bookings.id,
            bookings.num_people,
            bookings.total_ghs,
            bookings.status,
            bookings.qr_token,
            bookings.created_at,
            tour_slots.tour_date,
            tour_slots.start_time,
            tour_slots.site_id,
            sites.name      AS site_name,
            sites.region    AS site_region,
            languages.name  AS language_name,
            users.name      AS guide_name
     FROM bookings
     INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
     INNER JOIN sites      ON tour_slots.site_id = sites.id
     INNER JOIN languages  ON tour_slots.language_id = languages.id
     INNER JOIN guides     ON tour_slots.guide_id = guides.id
     INNER JOIN users      ON guides.user_id = users.id
     WHERE bookings.id = ?
       AND bookings.user_id = ?"
);

// Run it with the booking id and the logged-in user's id.
$bookingQuery->execute([$bookingId, $_SESSION['user_id']]);

// Get the booking (or false if not found / not theirs).
$booking = $bookingQuery->fetch();

// -----------------------------------------------------
// 3. PREPARE THINGS TO SHOW
// -----------------------------------------------------

if ($booking) {

    // A friendly booking reference: "AK360-" plus the id padded
    // with zeros to 6 digits, e.g. booking 3 becomes AK360-000003.
    $reference = 'AK360-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);

    // A friendly label for each booking status.
    $statusLabels = [
        'pending'   => 'Awaiting payment',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ];

    // Pick this booking's label (or show the raw status if unknown).
    $statusLabel = $statusLabels[$booking['status']] ?? $booking['status'];

    // Get the latest successful (or pay-at-site) payment for this
    // booking, if there is one, to show how it was paid.
    $paymentQuery = $pdo->prepare(
        "SELECT method, status, provider_ref, created_at
         FROM payments
         WHERE booking_id = ? AND status IN ('paid', 'pending', 'refunded')
         ORDER BY id DESC
         LIMIT 1"
    );
    $paymentQuery->execute([$booking['id']]);
    $payment = $paymentQuery->fetch();

    // Friendly names for the payment methods and statuses.
    $methodLabels = ['momo' => 'Mobile Money', 'card' => 'Card', 'pay_at_site' => 'Pay at site'];
    $paymentStatusLabels = ['paid' => 'Paid', 'pending' => 'To pay at the site', 'refunded' => 'Refunded'];
}

// Show the shared header and navigation.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our booking styles. -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<?php if (!$booking): ?>

    <!-- =================================================
         BOOKING NOT FOUND (wrong id, or not this user's)
         ================================================= -->
    <section class="content-card">
        <h1>Booking not found</h1>
        <p class="booking-message">We couldn't find that booking on your account.</p>
        <a href="book.php" class="btn btn-primary">Book a tour</a>
    </section>

<?php else: ?>

    <!-- =================================================
         THE TICKET
         ================================================= -->
    <section class="content-card ticket">

        <!-- Success message at the top. -->
        <p class="ticket-success">Your booking has been received!</p>

        <!-- The booking reference, big and clear. -->
        <h1><?php echo htmlspecialchars($reference); ?></h1>

        <!-- Status badge. The class (e.g. status-pending) sets its colour in booking.css. -->
        <p>
            <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                <?php echo htmlspecialchars($statusLabel); ?>
            </span>
        </p>

        <div class="ticket-body">

            <!-- Left side: the tour details. -->
            <ul class="tour-summary">
                <li><span>Site</span><strong><?php echo htmlspecialchars($booking['site_name']); ?></strong></li>
                <li><span>Region</span><strong><?php echo htmlspecialchars($booking['site_region']); ?></strong></li>
                <li><span>Date</span><strong><?php echo date('l j F Y', strtotime($booking['tour_date'])); ?></strong></li>
                <li><span>Time</span><strong><?php echo date('g:i A', strtotime($booking['start_time'])); ?></strong></li>
                <li><span>Language</span><strong><?php echo htmlspecialchars($booking['language_name']); ?></strong></li>
                <li><span>Guide</span><strong><?php echo htmlspecialchars($booking['guide_name']); ?></strong></li>
                <li><span>People</span><strong><?php echo (int) $booking['num_people']; ?></strong></li>
                <li><span>Total</span><strong>GHS <?php echo number_format((float) $booking['total_ghs'], 2); ?></strong></li>
            </ul>

            <!-- Right side: the QR code. -->
            <div class="ticket-qr">

                <!-- qrcode.js draws the QR code inside this empty box.
                     data-token holds the booking's secret code, which
                     is what the QR code contains. Daniel's guide page
                     will scan it to check the group in. -->
                <div id="qr-code" data-token="<?php echo htmlspecialchars($booking['qr_token']); ?>"></div>

                <p class="small-print">Show this QR code to your guide at the site.</p>
            </div>
        </div>

        <!-- What to do next, depending on the status. -->
        <?php if ($booking['status'] === 'pending'): ?>

            <!-- Not paid yet: ask them to pay, with a Pay now button. -->
            <p class="booking-message">Please pay to confirm your place.</p>
            <a href="pay.php?booking_id=<?php echo (int) $booking['id']; ?>" class="btn btn-primary">Pay now</a>

        <?php elseif ($payment): ?>

            <!-- Paid (or reserved to pay at site): show the payment details. -->
            <ul class="tour-summary payment-summary">
                <li><span>Payment</span><strong><?php echo htmlspecialchars($methodLabels[$payment['method']] ?? $payment['method']); ?></strong></li>
                <li><span>Payment status</span><strong><?php echo htmlspecialchars($paymentStatusLabels[$payment['status']] ?? $payment['status']); ?></strong></li>
                <li><span>Transaction ref</span><strong><?php echo htmlspecialchars($payment['provider_ref']); ?></strong></li>
            </ul>

        <?php endif; ?>

        <!-- Buttons: print the ticket, book another tour, or see all bookings.
             window.print() opens the browser's print window, which can
             also save the ticket as a PDF. -->
        <div class="ticket-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print ticket</button>
            <a href="book.php" class="btn btn-secondary">Book another tour</a>

            <!-- Link to the list of all this tourist's bookings. -->
            <a href="my_bookings.php" class="btn btn-secondary">My bookings</a>
        </div>

    </section>

    <!-- qrcode.js: a small free library that draws QR codes.
         We load it from cdnjs, a trusted public library host. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        // Find the empty box where the QR code goes.
        const qrBox = document.getElementById('qr-code');

        // Draw a QR code containing the booking's secret token.
        new QRCode(qrBox, {
            text: qrBox.dataset.token, // what the QR code contains
            width: 180,                // size in pixels
            height: 180,
            correctLevel: QRCode.CorrectLevel.M // still scans if slightly damaged
        });
    </script>

<?php endif; ?>

<?php
// Show the shared footer.
require_once __DIR__ . '/../includes/footer.php';
?>
