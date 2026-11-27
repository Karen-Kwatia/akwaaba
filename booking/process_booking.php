<?php
// =====================================================
// booking/process_booking.php - SAVE THE BOOKING
// Owner: Yayra (booking system)
//
// This file is not a page people see. The ticket form on
// details.php sends its data here. This file:
//   1. Checks the tourist is logged in and the form is genuine.
//   2. Checks the numbers are sensible.
//   3. Works out the price itself from the database
//      (we never trust a price sent from the browser).
//   4. Saves the booking and reserves the spaces, making sure
//      two people can't take the last space at the same time.
//   5. Sends the tourist to the confirmation page.
// If anything is wrong, it sends them back to details.php
// with a message.
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users may continue; others go to the login page.
requireLogin('../auth/login.php');

// -----------------------------------------------------
// HELPER: send the tourist back with an error message
// -----------------------------------------------------

// We need this in several places, so we write it once.
function backWithError($slotId, $message)
{
    // Store the message in the session so details.php can show it.
    $_SESSION['booking_error'] = $message;

    // Send the browser back to the ticket page for the same slot.
    header('Location: details.php?slot_id=' . (int) $slotId);

    // Stop running this file.
    exit;
}

// -----------------------------------------------------
// 1. BASIC CHECKS
// -----------------------------------------------------

// This file should only receive form submissions (POST).
// If someone types its address in the browser, send them away.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: book.php');
    exit;
}

// Read which slot the tourist chose. (int) makes it a safe number.
$slotId = isset($_POST['slot_id']) ? (int) $_POST['slot_id'] : 0;

// Only tourist accounts can book (not guides or admins).
if (($_SESSION['user_role'] ?? '') !== 'tourist') {
    backWithError($slotId, 'Only tourist accounts can book tours.');
}

// Check the security token matches the one in the session.
// hash_equals() compares them safely. If they don't match,
// the form didn't come from our own page.
$sentToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
    backWithError($slotId, 'Your session expired. Please try again.');
}

// -----------------------------------------------------
// 2. READ THE TICKET NUMBERS
// -----------------------------------------------------

// The form sends qty[Ghanaian], qty[foreigner] and so on,
// which PHP gives us as one list called $_POST['qty'].
$quantities = $_POST['qty'] ?? [];

// If it isn't a list (someone tampered with the form), treat it as empty.
if (!is_array($quantities)) {
    $quantities = [];
}

// -----------------------------------------------------
// 3. SAVE THE BOOKING SAFELY (DATABASE TRANSACTION)
// -----------------------------------------------------

// A transaction groups several database changes so they
// either ALL happen or NONE happen. If something fails
// half-way, nothing is saved.
try {

    // Start the transaction.
    $pdo->beginTransaction();

    // Get the slot and LOCK its row ("FOR UPDATE").
    // While locked, anyone else booking this same slot has to
    // wait until we finish, so two people can't both take the
    // last space at the same moment.
    $slotQuery = $pdo->prepare(
        "SELECT id, site_id, capacity, booked_count
         FROM tour_slots
         WHERE id = ?
           -- the tour must still be in the future
           AND (tour_date > CURDATE()
                OR (tour_date = CURDATE() AND start_time > CURTIME()))
         FOR UPDATE"
    );
    $slotQuery->execute([$slotId]);
    $slot = $slotQuery->fetch();

    // If the slot doesn't exist or has passed, cancel and go back.
    if (!$slot) {
        $pdo->rollBack();
        backWithError($slotId, 'This tour is no longer available.');
    }

    // Work out how many spaces are still free right now.
    $spacesLeft = (int) $slot['capacity'] - (int) $slot['booked_count'];

    // Get the real prices for this site from the database.
    $priceQuery = $pdo->prepare(
        "SELECT ticket_type, price_ghs FROM site_prices WHERE site_id = ?"
    );
    $priceQuery->execute([$slot['site_id']]);

    // Start counting people and money from zero.
    $numPeople = 0;
    $totalGhs = 0.0;

    // Go through each real ticket type for this site.
    foreach ($priceQuery->fetchAll() as $price) {

        // How many of this ticket type did the tourist ask for?
        // If the box was missing, use 0.
        $count = isset($quantities[$price['ticket_type']])
            ? (int) $quantities[$price['ticket_type']]
            : 0;

        // Negative numbers make no sense, so stop.
        if ($count < 0) {
            $pdo->rollBack();
            backWithError($slotId, 'Ticket numbers cannot be negative.');
        }

        // Add these people to the total number of people.
        $numPeople += $count;

        // Add their cost (number x price) to the total price.
        $totalGhs += $count * (float) $price['price_ghs'];
    }

    // They must book at least one ticket.
    if ($numPeople < 1) {
        $pdo->rollBack();
        backWithError($slotId, 'Please choose at least one ticket.');
    }

    // They can't book more people than there are spaces.
    if ($numPeople > $spacesLeft) {
        $pdo->rollBack();
        backWithError($slotId, 'Sorry, only ' . $spacesLeft . ' spaces are left on this tour.');
    }

    // Make a unique random code for this booking.
    // It will be shown as a QR code on the ticket, and the
    // guide scans it at the site to check the group in.
    $qrToken = bin2hex(random_bytes(16));

    // Save the booking. Status is "pending" until it is paid
    // for or confirmed (we add payment in a later step).
    $insertBooking = $pdo->prepare(
        "INSERT INTO bookings (user_id, slot_id, num_people, total_ghs, status, qr_token)
         VALUES (?, ?, ?, ?, 'pending', ?)"
    );
    $insertBooking->execute([
        $_SESSION['user_id'],   // the logged-in tourist
        $slotId,                // the chosen tour slot
        $numPeople,             // how many people
        $totalGhs,              // total price in GHS
        $qrToken,               // the random ticket code
    ]);

    // Get the id the database gave the new booking.
    $bookingId = $pdo->lastInsertId();

    // Reserve the spaces: add these people to the slot's booked count.
    $updateSlot = $pdo->prepare(
        "UPDATE tour_slots SET booked_count = booked_count + ? WHERE id = ?"
    );
    $updateSlot->execute([$numPeople, $slotId]);

    // Everything worked: save all the changes for real.
    $pdo->commit();

} catch (Exception $error) {

    // Something unexpected went wrong (for example the database
    // stopped). Undo any half-finished changes, if a transaction
    // is still open.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Tell the tourist politely instead of showing a scary error.
    backWithError($slotId, 'Sorry, something went wrong saving your booking. Please try again.');
}

// -----------------------------------------------------
// 4. GO TO THE CONFIRMATION PAGE
// -----------------------------------------------------

// Send the tourist to the confirmation page for their new booking.
// (confirmation.php is the next step we build.)
header('Location: confirmation.php?booking_id=' . (int) $bookingId);
exit;
