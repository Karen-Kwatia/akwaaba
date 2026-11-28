<?php
// =====================================================
// booking/my_bookings.php - A TOURIST'S LIST OF BOOKINGS
// Owner: Yayra (booking system)
//
// Shows the logged-in tourist all their bookings, split into
// "Upcoming" and "Past and cancelled". For each upcoming
// booking they can:
//   - view the ticket (confirmation.php)
//   - reschedule it to another time (reschedule.php)
//   - cancel it (cancel.php)
// Cancelling and rescheduling are only allowed up to
// 24 hours before the tour starts.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can see their bookings.
requireLogin('../auth/login.php');

// -----------------------------------------------------
// 1. SECURITY TOKEN FOR THE CANCEL BUTTONS (CSRF)
// -----------------------------------------------------

// Each Cancel button is a small form. The secret token stops
// other websites from cancelling bookings behind the tourist's back.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -----------------------------------------------------
// 2. MESSAGES FROM cancel.php OR reschedule.php
// -----------------------------------------------------

// After cancelling or rescheduling, those files store a message
// in the session and send the tourist back here to see it.
$successMessage = $_SESSION['booking_success'] ?? '';
$errorMessage = $_SESSION['booking_error'] ?? '';

// Remove the messages so they only show once.
unset($_SESSION['booking_success'], $_SESSION['booking_error']);

// -----------------------------------------------------
// 3. GET ALL OF THIS TOURIST'S BOOKINGS
// -----------------------------------------------------

$bookingQuery = $pdo->prepare(
    "SELECT bookings.id,
            bookings.num_people,
            bookings.total_ghs,
            bookings.status,
            tour_slots.tour_date,
            tour_slots.start_time,
            sites.name     AS site_name,
            languages.name AS language_name,

            -- 1 if the tour hasn't started yet, otherwise 0.
            -- TIMESTAMP() joins the date and time into one moment.
            (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW()) AS is_upcoming,

            -- 1 if the tour is more than 24 hours away, otherwise 0.
            -- Only then can it be cancelled or rescheduled.
            (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW() + INTERVAL 24 HOUR) AS can_change

     FROM bookings
     INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
     INNER JOIN sites      ON tour_slots.site_id = sites.id
     INNER JOIN languages  ON tour_slots.language_id = languages.id

     -- only this tourist's bookings
     WHERE bookings.user_id = ?

     -- soonest tour first
     ORDER BY tour_slots.tour_date, tour_slots.start_time"
);
$bookingQuery->execute([$_SESSION['user_id']]);

// Sort the bookings into two lists.
$upcoming = [];
$past = [];

foreach ($bookingQuery->fetchAll() as $booking) {

    // A booking counts as upcoming if the tour hasn't happened
    // yet and it wasn't cancelled or completed.
    $isActive = in_array($booking['status'], ['pending', 'confirmed'], true);

    if ($booking['is_upcoming'] && $isActive) {
        $upcoming[] = $booking;
    } else {
        $past[] = $booking;
    }
}

// Show the newest past bookings first (reverse the list).
$past = array_reverse($past);

// Friendly words for each status (same as the ticket page).
$statusLabels = [
    'pending'   => 'Awaiting payment',
    'confirmed' => 'Confirmed',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed',
];

// Show the shared header.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our booking styles. -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<section class="content-card">

    <h1>My bookings</h1>

    <!-- Success or error message from cancelling/rescheduling. -->
    <?php if ($successMessage): ?>
        <p class="booking-success"><?php echo htmlspecialchars($successMessage); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <p class="booking-error"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>

    <!-- ================= UPCOMING BOOKINGS ================= -->
    <h2>Upcoming</h2>

    <?php if (!$upcoming): ?>

        <!-- No upcoming bookings yet. -->
        <p>You have no upcoming tours.</p>
        <a href="book.php" class="btn btn-primary">Book a tour</a>

    <?php else: ?>

        <div class="booking-list">
            <?php foreach ($upcoming as $booking): ?>

                <!-- One card per booking. -->
                <article class="booking-card">

                    <!-- Site name and reference, e.g. AK360-000005. -->
                    <div class="booking-card-head">
                        <h3><?php echo htmlspecialchars($booking['site_name']); ?></h3>
                        <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo htmlspecialchars($statusLabels[$booking['status']] ?? $booking['status']); ?>
                        </span>
                    </div>

                    <!-- Date, time, language, people and price on one line. -->
                    <p class="booking-card-meta">
                        AK360-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
                        &middot; <?php echo date('D j M Y', strtotime($booking['tour_date'])); ?>
                        at <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
                        &middot; <?php echo htmlspecialchars($booking['language_name']); ?>
                        &middot; <?php echo (int) $booking['num_people']; ?> people
                        &middot; GHS <?php echo number_format((float) $booking['total_ghs'], 2); ?>
                    </p>

                    <!-- Action buttons. -->
                    <div class="booking-card-actions">

                        <!-- Not paid yet: show a Pay now button first. -->
                        <?php if ($booking['status'] === 'pending'): ?>
                            <a href="pay.php?booking_id=<?php echo (int) $booking['id']; ?>" class="btn btn-primary">Pay now</a>
                        <?php endif; ?>

                        <!-- View the ticket and QR code. -->
                        <a href="confirmation.php?booking_id=<?php echo (int) $booking['id']; ?>" class="btn btn-secondary">View ticket</a>

                        <?php if ($booking['can_change']): ?>

                            <!-- Reschedule: opens a page to pick another time. -->
                            <a href="reschedule.php?booking_id=<?php echo (int) $booking['id']; ?>" class="btn btn-secondary">Reschedule</a>

                            <!-- Cancel: a small form so it is sent with POST.
                                 onsubmit asks "Are you sure?" first; if they
                                 click Cancel in that box, nothing is sent. -->
                            <form action="cancel.php" method="post" class="inline-form"
                                  onsubmit="return confirm('Cancel this booking? This cannot be undone.');">
                                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button type="submit" class="btn btn-danger">Cancel booking</button>
                            </form>

                        <?php else: ?>

                            <!-- Less than 24 hours to go: explain why the buttons are missing. -->
                            <span class="small-print">This tour starts within 24 hours, so it can no longer be changed or cancelled online.</span>

                        <?php endif; ?>
                    </div>
                </article>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- ============ PAST AND CANCELLED BOOKINGS ============ -->
    <?php if ($past): ?>

        <h2>Past and cancelled</h2>

        <div class="booking-list">
            <?php foreach ($past as $booking): ?>

                <!-- Same card, but faded and without change buttons. -->
                <article class="booking-card booking-card-past">
                    <div class="booking-card-head">
                        <h3><?php echo htmlspecialchars($booking['site_name']); ?></h3>
                        <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo htmlspecialchars($statusLabels[$booking['status']] ?? $booking['status']); ?>
                        </span>
                    </div>
                    <p class="booking-card-meta">
                        AK360-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
                        &middot; <?php echo date('D j M Y', strtotime($booking['tour_date'])); ?>
                        at <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
                        &middot; <?php echo (int) $booking['num_people']; ?> people
                    </p>
                </article>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</section>

<?php
// Show the shared footer.
require_once __DIR__ . '/../includes/footer.php';
?>
