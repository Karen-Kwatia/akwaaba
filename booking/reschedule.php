<?php
// =====================================================
// booking/reschedule.php - MOVE A BOOKING TO ANOTHER TIME
// Owner: Yayra (booking system)
//
// Opened from the "Reschedule" button on my_bookings.php:
//   /akwaaba/booking/reschedule.php?booking_id=5
//
// This one file does two jobs:
//   - GET  (opening the page): shows the booking and a list of
//          other times at the same site with enough spaces.
//   - POST (submitting the form): moves the booking to the
//          chosen time, giving the old spaces back and taking
//          the new ones.
// Same rules as cancelling: only the tourist's own active
// bookings, and only more than 24 hours before the tour.
// The price stays the same because it's the same site.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can reschedule.
requireLogin('../auth/login.php');

// Make sure there is a security token for our form (CSRF protection).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -----------------------------------------------------
// HELPER: go back to My bookings with a message
// -----------------------------------------------------
function backToMyBookings($type, $message)
{
    $_SESSION[$type] = $message;
    header('Location: my_bookings.php');
    exit;
}

// -----------------------------------------------------
// HELPER: get one of THIS tourist's bookings by id
// -----------------------------------------------------

// $lock = true adds "FOR UPDATE" to lock the row while we change it.
function findMyBooking(PDO $pdo, $bookingId, $lock = false)
{
    $query = $pdo->prepare(
        "SELECT bookings.id, bookings.slot_id, bookings.num_people, bookings.status,
                tour_slots.site_id, tour_slots.tour_date, tour_slots.start_time,
                sites.name AS site_name,
                -- 1 if the tour is more than 24 hours away
                (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW() + INTERVAL 24 HOUR) AS can_change
         FROM bookings
         INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
         INNER JOIN sites      ON tour_slots.site_id = sites.id
         WHERE bookings.id = ? AND bookings.user_id = ?"
        . ($lock ? " FOR UPDATE" : "")
    );
    $query->execute([$bookingId, $_SESSION['user_id']]);
    return $query->fetch();
}

// Which booking we're rescheduling. It comes from the address
// (GET) or from the hidden field in the form (POST).
$bookingId = (int) ($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);

// =====================================================
// PART A: THE FORM WAS SUBMITTED (POST) - MOVE THE BOOKING
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check the security token.
    $sentToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $sentToken)) {
        backToMyBookings('booking_error', 'Your session expired. Please try again.');
    }

    // The new slot the tourist picked.
    $newSlotId = isset($_POST['new_slot_id']) ? (int) $_POST['new_slot_id'] : 0;

    try {

        // All changes happen together, or not at all.
        $pdo->beginTransaction();

        // Get and lock the booking (only if it's this tourist's).
        $booking = findMyBooking($pdo, $bookingId, true);

        // Check the booking can still be changed.
        if (!$booking) {
            $pdo->rollBack();
            backToMyBookings('booking_error', 'We could not find that booking.');
        }
        if (!in_array($booking['status'], ['pending', 'confirmed'], true) || !$booking['can_change']) {
            $pdo->rollBack();
            backToMyBookings('booking_error', 'This booking can no longer be rescheduled.');
        }
        if ($newSlotId === (int) $booking['slot_id']) {
            $pdo->rollBack();
            backToMyBookings('booking_error', 'Please choose a different time.');
        }

        // Lock BOTH slots (old and new) so nobody else changes
        // their counts while we move the booking. We lock them in
        // id order, which avoids two transactions waiting on each
        // other forever (called a "deadlock").
        $lockSlots = $pdo->prepare(
            "SELECT id, site_id, capacity, booked_count,
                    (TIMESTAMP(tour_date, start_time) > NOW() + INTERVAL 24 HOUR) AS far_enough
             FROM tour_slots
             WHERE id IN (?, ?)
             ORDER BY id
             FOR UPDATE"
        );
        $lockSlots->execute([$booking['slot_id'], $newSlotId]);

        // Find the new slot among the locked rows.
        $newSlot = null;
        foreach ($lockSlots->fetchAll() as $slot) {
            if ((int) $slot['id'] === $newSlotId) {
                $newSlot = $slot;
            }
        }

        // The new slot must exist, be at the SAME site, and be
        // more than 24 hours away.
        if (!$newSlot || (int) $newSlot['site_id'] !== (int) $booking['site_id'] || !$newSlot['far_enough']) {
            $pdo->rollBack();
            backToMyBookings('booking_error', 'That time is not available. Please choose another.');
        }

        // The new slot must have room for the whole group.
        $spacesLeft = (int) $newSlot['capacity'] - (int) $newSlot['booked_count'];
        if ($spacesLeft < (int) $booking['num_people']) {
            $pdo->rollBack();
            backToMyBookings('booking_error', 'Sorry, that time no longer has enough spaces for your group.');
        }

        // Give the old slot's spaces back (never below zero).
        $freeOld = $pdo->prepare(
            "UPDATE tour_slots SET booked_count = GREATEST(booked_count - ?, 0) WHERE id = ?"
        );
        $freeOld->execute([$booking['num_people'], $booking['slot_id']]);

        // Take the spaces in the new slot.
        $takeNew = $pdo->prepare(
            "UPDATE tour_slots SET booked_count = booked_count + ? WHERE id = ?"
        );
        $takeNew->execute([$booking['num_people'], $newSlotId]);

        // Point the booking at the new slot.
        $moveBooking = $pdo->prepare(
            "UPDATE bookings SET slot_id = ? WHERE id = ?"
        );
        $moveBooking->execute([$newSlotId, $booking['id']]);

        // Save all three changes for real.
        $pdo->commit();

    } catch (Exception $error) {

        // Undo any half-finished changes.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        backToMyBookings('booking_error', 'Sorry, something went wrong. Please try again.');
    }

    // Success: back to the list with a message.
    $reference = 'AK360-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
    backToMyBookings('booking_success', 'Booking ' . $reference . ' has been moved to the new time.');
}

// =====================================================
// PART B: OPENING THE PAGE (GET) - SHOW THE CHOICES
// =====================================================

// Get the booking (no lock needed; we're only reading).
$booking = findMyBooking($pdo, $bookingId);

// If it can't be rescheduled, explain on My bookings.
if (!$booking || !in_array($booking['status'], ['pending', 'confirmed'], true) || !$booking['can_change']) {
    backToMyBookings('booking_error', 'This booking can no longer be rescheduled.');
}

// Find other times at the same site that:
//   - are more than 24 hours away
//   - have enough free spaces for the whole group
//   - are not the booking's current time
$slotQuery = $pdo->prepare(
    "SELECT tour_slots.id, tour_slots.tour_date, tour_slots.start_time,
            (tour_slots.capacity - tour_slots.booked_count) AS spaces_left,
            languages.name AS language_name,
            users.name     AS guide_name
     FROM tour_slots
     INNER JOIN languages ON tour_slots.language_id = languages.id
     INNER JOIN guides    ON tour_slots.guide_id = guides.id
     INNER JOIN users     ON guides.user_id = users.id
     WHERE tour_slots.site_id = ?
       AND tour_slots.id <> ?
       AND (tour_slots.capacity - tour_slots.booked_count) >= ?
       AND TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW() + INTERVAL 24 HOUR
     ORDER BY tour_slots.tour_date, tour_slots.start_time"
);
$slotQuery->execute([$booking['site_id'], $booking['slot_id'], $booking['num_people']]);
$slots = $slotQuery->fetchAll();

// Show the shared header.
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Load our booking styles. -->
<link rel="stylesheet" href="/akwaaba/assets/css/booking.css">

<section class="content-card">

    <h1>Reschedule your tour</h1>

    <!-- The booking's current details. -->
    <p>
        <strong><?php echo htmlspecialchars($booking['site_name']); ?></strong>
        &middot; currently <?php echo date('D j M Y', strtotime($booking['tour_date'])); ?>
        at <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
        &middot; <?php echo (int) $booking['num_people']; ?> people
    </p>

    <?php if (!$slots): ?>

        <!-- No other time has room for the group. -->
        <p class="booking-message">Sorry, there are no other times with enough spaces for your group right now.</p>
        <a href="my_bookings.php" class="btn btn-secondary">Back to my bookings</a>

    <?php else: ?>

        <!-- Form that sends the chosen time back to this same file (POST). -->
        <form action="reschedule.php" method="post">

            <!-- Hidden fields: which booking, and the security token. -->
            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <p class="step-label">Choose a new time</p>

            <!-- One radio button per available time. Radio buttons
                 with the same name let you pick only one. -->
            <div class="reschedule-list">
                <?php foreach ($slots as $slot): ?>
                    <label class="reschedule-option">
                        <input type="radio" name="new_slot_id" value="<?php echo (int) $slot['id']; ?>" required>
                        <span>
                            <strong>
                                <?php echo date('D j M Y', strtotime($slot['tour_date'])); ?>
                                at <?php echo date('g:i A', strtotime($slot['start_time'])); ?>
                            </strong>
                            <?php echo htmlspecialchars($slot['language_name']); ?>
                            &middot; Guide: <?php echo htmlspecialchars($slot['guide_name']); ?>
                            &middot; <?php echo (int) $slot['spaces_left']; ?> spaces left
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Submit, or go back without changing anything. -->
            <div class="ticket-actions">
                <button type="submit" class="btn btn-primary">Move my booking</button>
                <a href="my_bookings.php" class="btn btn-secondary">Keep current time</a>
            </div>
        </form>

    <?php endif; ?>

</section>

<?php
// Show the shared footer.
require_once __DIR__ . '/../includes/footer.php';
?>
