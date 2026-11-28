<?php
// =====================================================
// booking/process_payment.php - HANDLE A PAYMENT (DEMO)
// Owner: Yayra (booking system)
//
// Not a page people see. The form on pay.php sends its data
// here. This file:
//   1. Checks the tourist is logged in and the form is genuine.
//   2. Checks the booking is theirs and still awaiting payment.
//   3. Checks the payment details look right (SIMULATED: no
//      real money moves and we don't contact Paystack).
//   4. Saves a record in the payments table.
//   5. If it worked, marks the booking as "confirmed".
//
// We NEVER save card numbers, CVVs or phone numbers.
//
// Demo rules, so we can show both outcomes in a presentation:
//   - Mobile Money number ending in 0000  -> declined
//   - Card number ending in 0002          -> declined
//   - Anything else that looks valid      -> approved
// =====================================================

// Load the shared login system: session, $pdo and requireLogin().
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can pay.
requireLogin('../auth/login.php');

// -----------------------------------------------------
// HELPERS
// -----------------------------------------------------

// Go back to the payment page with an error message.
function backToPayment($bookingId, $message)
{
    $_SESSION['payment_error'] = $message;
    header('Location: pay.php?booking_id=' . (int) $bookingId);
    exit;
}

// Check a card number with the Luhn formula: the same quick
// check banks use to catch typing mistakes in card numbers.
// It returns true if the number could be a real card number.
function passesLuhnCheck($number)
{
    // Running total of the digits.
    $sum = 0;

    // Is this digit one we double? (Every second digit from the right.)
    $double = false;

    // Go through the digits from right to left.
    for ($i = strlen($number) - 1; $i >= 0; $i--) {

        // Turn the character into a number.
        $digit = (int) $number[$i];

        // Double every second digit. If that gives 10 or more,
        // subtract 9 (the same as adding its two digits together).
        if ($double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }

        // Add it to the total and flip for the next digit.
        $sum += $digit;
        $double = !$double;
    }

    // A valid card number's total is a multiple of 10.
    return $sum % 10 === 0;
}

// -----------------------------------------------------
// 1. BASIC CHECKS
// -----------------------------------------------------

// Only accept form submissions (POST).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_bookings.php');
    exit;
}

// Which booking is being paid for.
$bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;

// Check the security token matches the one in the session.
$sentToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
    backToPayment($bookingId, 'Your session expired. Please try again.');
}

// Which payment method was chosen. Only these three are allowed.
$method = $_POST['method'] ?? '';
if (!in_array($method, ['momo', 'card', 'pay_at_site'], true)) {
    backToPayment($bookingId, 'Please choose a payment method.');
}

// -----------------------------------------------------
// 2. CHECK THE PAYMENT DETAILS (SIMULATED)
// -----------------------------------------------------

// Assume the payment will be approved unless a check says otherwise.
$approved = true;

// The message to show if it is declined.
$declineReason = '';

if ($method === 'momo') {

    // Remove spaces the tourist may have typed, e.g. "024 123 4567".
    $momoNumber = preg_replace('/\s+/', '', $_POST['momo_number'] ?? '');

    // Ghana mobile numbers are 10 digits starting with 0, e.g. 0241234567.
    // preg_match checks the text matches that pattern.
    if (!preg_match('/^0\d{9}$/', $momoNumber)) {
        backToPayment($bookingId, 'Please enter a valid 10-digit Mobile Money number, e.g. 0241234567.');
    }

    // The network must be one of the three we offer.
    if (!in_array($_POST['network'] ?? '', ['MTN', 'Telecel', 'AT'], true)) {
        backToPayment($bookingId, 'Please choose your Mobile Money network.');
    }

    // Demo rule: numbers ending in 0000 are declined.
    if (substr($momoNumber, -4) === '0000') {
        $approved = false;
        $declineReason = 'Mobile Money payment declined: insufficient balance (demo).';
    }

} elseif ($method === 'card') {

    // Remove spaces from the card number, e.g. "4242 4242 ..." -> "4242...".
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');

    // Card numbers are 13 to 19 digits and must pass the Luhn check.
    if (!preg_match('/^\d{13,19}$/', $cardNumber) || !passesLuhnCheck($cardNumber)) {
        backToPayment($bookingId, 'Please enter a valid card number.');
    }

    // A name must be given.
    if (trim($_POST['card_name'] ?? '') === '') {
        backToPayment($bookingId, 'Please enter the name on the card.');
    }

    // Expiry must look like MM/YY, e.g. 08/28.
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $_POST['card_expiry'] ?? '', $parts)) {
        backToPayment($bookingId, 'Please enter the expiry date as MM/YY, e.g. 08/28.');
    }

    // The card must not have expired. A card is valid until the
    // end of its expiry month, so compare year and month as one
    // number, e.g. August 2028 -> 202808.
    $expiry = (int) ('20' . $parts[2] . $parts[1]);
    if ($expiry < (int) date('Ym')) {
        backToPayment($bookingId, 'This card has expired.');
    }

    // The CVV is the 3 or 4 digit code on the back of the card.
    if (!preg_match('/^\d{3,4}$/', $_POST['card_cvv'] ?? '')) {
        backToPayment($bookingId, 'Please enter the 3 or 4 digit CVV.');
    }

    // Demo rule: cards ending in 0002 are declined.
    if (substr($cardNumber, -4) === '0002') {
        $approved = false;
        $declineReason = 'Card declined by the bank (demo).';
    }
}
// "pay_at_site" needs no details: the tourist pays the guide on the day.

// -----------------------------------------------------
// 3. SAVE THE RESULT SAFELY (TRANSACTION)
// -----------------------------------------------------
try {

    // The payment record and the booking update happen together.
    $pdo->beginTransaction();

    // Get and lock the booking, only if it's this tourist's.
    $bookingQuery = $pdo->prepare(
        "SELECT bookings.id, bookings.total_ghs, bookings.status,
                (TIMESTAMP(tour_slots.tour_date, tour_slots.start_time) > NOW()) AS is_upcoming
         FROM bookings
         INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
         WHERE bookings.id = ? AND bookings.user_id = ?
         FOR UPDATE"
    );
    $bookingQuery->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $bookingQuery->fetch();

    // It must exist, still be awaiting payment and be in the future.
    // This also stops paying twice for the same booking.
    if (!$booking || $booking['status'] !== 'pending' || !$booking['is_upcoming']) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = 'This booking does not need paying for.';
        header('Location: my_bookings.php');
        exit;
    }

    // Decide the payment record's status:
    //   approved card/MoMo -> "paid"
    //   declined           -> "failed"
    //   pay at site        -> "pending" (paid to the guide later)
    if ($method === 'pay_at_site') {
        $paymentStatus = 'pending';
    } else {
        $paymentStatus = $approved ? 'paid' : 'failed';
    }

    // A made-up transaction reference, like a real payment company
    // would give, e.g. "DEMO-3F9A1C2B7D4E". It must be unique.
    $providerRef = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));

    // Save the payment record. The amount comes from the booking in
    // the database, NOT from the form, so nobody can change the price.
    $insertPayment = $pdo->prepare(
        "INSERT INTO payments (booking_id, method, amount_ghs, status, provider_ref)
         VALUES (?, ?, ?, ?, ?)"
    );
    $insertPayment->execute([$booking['id'], $method, $booking['total_ghs'], $paymentStatus, $providerRef]);

    // If paid, or reserved to pay at site, confirm the booking.
    if ($paymentStatus !== 'failed') {
        $confirmBooking = $pdo->prepare(
            "UPDATE bookings SET status = 'confirmed' WHERE id = ?"
        );
        $confirmBooking->execute([$booking['id']]);
    }

    // Save everything for real.
    $pdo->commit();

} catch (Exception $error) {

    // Undo any half-finished changes and tell the tourist politely.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    backToPayment($bookingId, 'Sorry, something went wrong with the payment. Please try again.');
}

// -----------------------------------------------------
// 4. SHOW THE RESULT
// -----------------------------------------------------

// Declined: back to the payment page to try again.
// (The failed attempt is still recorded in the payments table.)
if ($paymentStatus === 'failed') {
    backToPayment($bookingId, $declineReason);
}

// Approved or pay at site: back to the ticket, now "Confirmed".
header('Location: confirmation.php?booking_id=' . (int) $bookingId);
exit;
