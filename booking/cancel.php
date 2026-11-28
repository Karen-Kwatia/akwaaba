<?php
// =====================================================
// booking/cancel.php - CANCEL A BOOKING
// Owner: Yayra (booking system)
//
// Not a page people see. The "Cancel booking" button on
// my_bookings.php sends a form here. This file:
//   1. Checks the tourist is logged in and the form is genuine.
//   2. Checks the booking is theirs, still active, and the
//      tour is more than 24 hours away.
//   3. Marks the booking as cancelled, gives the spaces back to
//      the tour slot, and marks any online payment as refunded.
//   4. Sends the tourist back to my_bookings.php with a message.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can cancel bookings.
requireLogin('../auth/login.php');

// -----------------------------------------------------
// HELPER: go back to My bookings with a message
// -----------------------------------------------------

// $type is 'booking_success' or 'booking_error'.
function backToMyBookings($type, $message)
{
    // Store the message for my_bookings.php to show once.
    $_SESSION[$type] = $message;

    // Send the browser back to the list and stop.
    header('Location: my_bookings.php');
    exit;
}

// -----------------------------------------------------
// 1. BASIC CHECKS
// -----------------------------------------------------

// Only accept form submissions (POST), never a typed-in address.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_bookings.php');
    exit;
}

// Check the security token matches the one in the session.
$sentToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
    backToMyBookings('booking_error', 'Your session expired. Please try again.');
}

// Which booking to cancel. (int) makes it a safe number.
$bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;

// -----------------------------------------------------
// 2. CANCEL SAFELY INSIDE A TRANSACTION
// -----------------------------------------------------

// All changes (booking status, the slot's booked count and any
// refund) must happen together, or not at all.
try {

    // Start the transaction.
    $pdo->beginTransaction();

    // Get the booking, but only if it belongs to this tourist.
    // FOR UPDATE locks the booking row while we work on it.
    $bookingQuery = $pdo->prepare(
        "SELECT bookings.id, bookings.slot_id, bookings.num_people, bookings.status,
                -- 1 if the tour is more than 24 hours away
                (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW() + INTERVAL 24 HOUR) AS can_change
         FROM bookings
         INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
         WHERE bookings.id = ? AND bookings.user_id = ?
         FOR UPDATE"
    );
    $bookingQuery->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $bookingQuery->fetch();

    // Not found, or not this tourist's booking.
    if (!$booking) {
        $pdo->rollBack();
        backToMyBookings('booking_error', 'We could not find that booking.');
    }

    // Only pending or confirmed bookings can be cancelled.
    if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
        $pdo->rollBack();
        backToMyBookings('booking_error', 'This booking can no longer be cancelled.');
    }

    // Too close to the tour (less than 24 hours).
    if (!$booking['can_change']) {
        $pdo->rollBack();
        backToMyBookings('booking_error', 'Tours can only be cancelled more than 24 hours before they start.');
    }

    // Mark the booking as cancelled.
    $cancelBooking = $pdo->prepare(
        "UPDATE bookings SET status = 'cancelled' WHERE id = ?"
    );
    $cancelBooking->execute([$booking['id']]);

    // Give the spaces back to the slot.
    // GREATEST(..., 0) makes sure the count never goes below zero.
    $freeSpaces = $pdo->prepare(
        "UPDATE tour_slots
         SET booked_count = GREATEST(booked_count - ?, 0)
         WHERE id = ?"
    );
    $freeSpaces->execute([$booking['num_people'], $booking['slot_id']]);

    // If the booking was paid online, mark that payment as refunded.
    // (In this demo no real money moves; a real site would ask
    // Paystack to send the money back here.)
    $refundPayment = $pdo->prepare(
        "UPDATE payments SET status = 'refunded' WHERE booking_id = ? AND status = 'paid'"
    );
    $refundPayment->execute([$booking['id']]);

    // Save all the changes for real.
    $pdo->commit();

} catch (Exception $error) {

    // Something unexpected went wrong: undo any half-finished changes.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    backToMyBookings('booking_error', 'Sorry, something went wrong. Please try again.');
}

// -----------------------------------------------------
// 3. DONE: BACK TO MY BOOKINGS
// -----------------------------------------------------

// Booking reference for the message, e.g. AK360-000005.
$reference = 'AK360-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
backToMyBookings('booking_success', 'Booking ' . $reference . ' has been cancelled.');
