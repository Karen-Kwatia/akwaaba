<?php
// =====================================================
// booking/booking_email.php - BOOKING CONFIRMATION EMAIL
// Owner: Yayra (booking system)
//
// Gives us one function, sendBookingConfirmationEmail(),
// which process_booking.php calls after a booking is saved.
//
// It uses PHPMailer, the same email library Nkunim uses for
// password reset emails, and the same Gmail settings from
// config/config.php ($smtpHost, $smtpUsername, ...).
//
// Email is OPTIONAL: if PHPMailer isn't installed (no vendor
// folder) or the Gmail settings are missing, the function
// simply does nothing and returns false. The booking still
// works either way.
// =====================================================

// Import the PHPMailer class names so we can write
// "PHPMailer" instead of the long full name.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

function sendBookingConfirmationEmail(PDO $pdo, $bookingId)
{
    // -------------------------------------------------
    // 1. CHECK EMAIL IS SET UP ON THIS COMPUTER
    // -------------------------------------------------

    // Where Composer puts PHPMailer when you run "composer install".
    $autoloader = __DIR__ . '/../vendor/autoload.php';

    // No vendor folder means PHPMailer isn't installed: skip the email.
    if (!file_exists($autoloader)) {
        return false;
    }

    // Load PHPMailer.
    require_once $autoloader;

    // The Gmail settings must exist in config.php; if any is
    // missing or empty, skip the email.
    foreach (['smtpHost', 'smtpUsername', 'smtpPassword', 'smtpPort', 'mailFromAddress'] as $setting) {
        if (empty($GLOBALS[$setting])) {
            return false;
        }
    }

    // -------------------------------------------------
    // 2. GET THE BOOKING DETAILS FOR THE EMAIL
    // -------------------------------------------------

    // Get the tourist's name and email, plus the tour details.
    $query = $pdo->prepare(
        "SELECT bookings.id, bookings.num_people, bookings.total_ghs,
                tour_slots.tour_date, tour_slots.start_time,
                sites.name AS site_name,
                languages.name AS language_name,
                tourist.name AS tourist_name,
                tourist.email AS tourist_email
         FROM bookings
         INNER JOIN tour_slots ON bookings.slot_id = tour_slots.id
         INNER JOIN sites ON tour_slots.site_id = sites.id
         INNER JOIN languages ON tour_slots.language_id = languages.id
         INNER JOIN users AS tourist ON bookings.user_id = tourist.id
         WHERE bookings.id = ?"
    );
    $query->execute([$bookingId]);
    $booking = $query->fetch();

    // If the booking doesn't exist, there's nothing to send.
    if (!$booking) {
        return false;
    }

    // -------------------------------------------------
    // 3. PREPARE THE TEXT OF THE EMAIL
    // -------------------------------------------------

    // Booking reference, e.g. AK360-000003 (same as the confirmation page).
    $reference = 'AK360-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);

    // Friendly date and time, e.g. "Sunday 6 December 2026" and "9:00 AM".
    $date = date('l j F Y', strtotime($booking['tour_date']));
    $time = date('g:i A', strtotime($booking['start_time']));

    // Total with two decimals, e.g. "105.00".
    $total = number_format((float) $booking['total_ghs'], 2);

    // Link back to the ticket page. $_SERVER['HTTP_HOST'] is the
    // website address, e.g. "localhost" when testing on XAMPP.
    $ticketLink = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . '/akwaaba/booking/confirmation.php?booking_id=' . (int) $booking['id'];

    // Make every piece of text safe to put inside HTML.
    $safe = function ($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    };

    // -------------------------------------------------
    // 4. SEND THE EMAIL WITH PHPMAILER
    // -------------------------------------------------

    // Create the email object. "true" makes it report errors.
    $mail = new PHPMailer(true);

    try {

        // Send through Gmail's server, using the settings in config.php
        // (the same way Nkunim's password reset email works).
        $mail->isSMTP();
        $mail->Host = $GLOBALS['smtpHost'];
        $mail->SMTPAuth = true;
        $mail->Username = $GLOBALS['smtpUsername'];
        $mail->Password = $GLOBALS['smtpPassword'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $GLOBALS['smtpPort'];

        // Use UTF-8 so names in any language display correctly.
        $mail->CharSet = 'UTF-8';

        // Who the email is from and who it goes to.
        $mail->setFrom($GLOBALS['mailFromAddress'], $GLOBALS['mailFromName'] ?? 'Akwaaba 360');
        $mail->addAddress($booking['tourist_email'], $booking['tourist_name']);

        // The email is written in HTML (with a plain-text copy below).
        $mail->isHTML(true);

        // Subject line, e.g. "Your Akwaaba 360 booking AK360-000003".
        $mail->Subject = 'Your Akwaaba 360 booking ' . $reference;

        // The HTML version of the email.
        $mail->Body = "
            <h2>Akwaaba, {$safe($booking['tourist_name'])}!</h2>
            <p>Your booking has been received. Here are the details:</p>
            <table cellpadding='6'>
                <tr><td><strong>Reference</strong></td><td>{$safe($reference)}</td></tr>
                <tr><td><strong>Site</strong></td><td>{$safe($booking['site_name'])}</td></tr>
                <tr><td><strong>Date</strong></td><td>{$safe($date)}</td></tr>
                <tr><td><strong>Time</strong></td><td>{$safe($time)}</td></tr>
                <tr><td><strong>Language</strong></td><td>{$safe($booking['language_name'])}</td></tr>
                <tr><td><strong>People</strong></td><td>{$safe($booking['num_people'])}</td></tr>
                <tr><td><strong>Total</strong></td><td>GHS {$safe($total)}</td></tr>
            </table>
            <p><a href='{$safe($ticketLink)}'>View your ticket and QR code</a></p>
            <p>Please arrive 15 minutes early and show your QR code to your guide.</p>
            <p>Regards,<br>Akwaaba 360 Team</p>
        ";

        // Plain-text version, for email apps that don't show HTML.
        $mail->AltBody = "Your Akwaaba 360 booking {$reference}\n"
            . "Site: {$booking['site_name']}\n"
            . "Date: {$date} at {$time}\n"
            . "People: {$booking['num_people']}  Total: GHS {$total}\n"
            . "Ticket: {$ticketLink}";

        // Send it.
        $mail->send();

        // Tell the caller it worked.
        return true;

    } catch (MailException $error) {

        // Note the problem in the server's error log (not shown to users).
        error_log('Booking email failed: ' . $mail->ErrorInfo);

        // Tell the caller it didn't work. The booking is still saved.
        return false;
    }
}
