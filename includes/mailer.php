
<?php

// Load the Composer autoloader to make PHPMailer available.
require_once __DIR__ . '/../vendor/autoload.php';

// Load the database and email configuration settings.
require_once __DIR__ . '/db.php';

// Import the PHPMailer classes.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* =========================================
   REUSABLE EMAIL SENDING FUNCTION
   ========================================= */

// Create a function for sending password reset emails.
function sendPasswordResetEmail($recipientEmail, $recipientName, $resetLink)
{
    // Create a new PHPMailer object.
    $mail = new PHPMailer(true);

    try {

        // Configure PHPMailer to use Gmail SMTP.
        $mail->isSMTP();

        // Set Gmail's SMTP server.
        $mail->Host = $GLOBALS['smtpHost'];

        // Enable SMTP authentication.
        $mail->SMTPAuth = true;

        // Supply the Gmail username.
        $mail->Username = $GLOBALS['smtpUsername'];

        // Supply the Google App Password.
        $mail->Password = $GLOBALS['smtpPassword'];

        // Enable STARTTLS encryption.
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // Set the Gmail SMTP port.
        $mail->Port = $GLOBALS['smtpPort'];

        // Set the email character encoding.
        $mail->CharSet = 'UTF-8';

        // Set the sender's email address and display name.
        $mail->setFrom(
            $GLOBALS['mailFromAddress'],
            $GLOBALS['mailFromName']
        );

        // Set the recipient's email address and name.
        $mail->addAddress($recipientEmail, $recipientName);

        // Enable HTML formatting for the email.
        $mail->isHTML(true);

        // Set the email subject.
        $mail->Subject = 'Reset Your Akwaaba 360 Password';

        // Safely prepare the recipient's name for HTML.
        $safeName = htmlspecialchars(
            $recipientName,
            ENT_QUOTES,
            'UTF-8'
        );

        // Safely prepare the reset link for HTML.
        $safeLink = htmlspecialchars(
            $resetLink,
            ENT_QUOTES,
            'UTF-8'
        );

        // Build the HTML email message.
        $mail->Body = "
            <h2>Hello {$safeName},</h2>

            <p>
                We received a request to reset your
                Akwaaba 360 account password.
            </p>

            <p>
                Click the button below to create a new password:
            </p>

            <p>
                <a href='{$safeLink}'
                   style='background-color:#2E8B70;
                   color:white;
                   padding:12px 20px;
                   text-decoration:none;
                   border-radius:6px;'>
                    Reset My Password
                </a>
            </p>

            <p>
                This link expires in 30 minutes.
            </p>

            <p>
                If you did not request this change,
                you can safely ignore this email.
            </p>

            <p>
                Regards,<br>
                Akwaaba 360 Team
            </p>
        ";

        // Provide a plain-text alternative for email clients.
        $mail->AltBody =
            "Hello {$recipientName},\n\n" .
            "Use this link to reset your Akwaaba 360 password:\n" .
            $resetLink . "\n\n" .
            "This link expires in 30 minutes.";

        // Send the email.
        $mail->send();

        // Report successful delivery to the calling page.
        return true;

    } catch (Exception $e) {

        // Record the technical error without exposing it to users.
        error_log('Password reset email failed: ' . $mail->ErrorInfo);

        // Report failure to the calling page.
        return false;
    }
}

?>
