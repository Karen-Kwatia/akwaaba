
<?php
// Start or resume the current user's session.
session_start();

// Load the database connection.
require_once __DIR__ . '/../includes/db.php';

// Check whether a remember-me cookie exists.
if (isset($_COOKIE['remember_token'])) {

    // Retrieve the token stored in the browser.
    $token = $_COOKIE['remember_token'];

    // Validate the token format before processing it.
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {

        // Hash the token to find its database record.
        $tokenHash = hash('sha256', $token);

        // Prepare a query to remove the remember-me token.
        $deleteToken = $pdo->prepare(
            "DELETE FROM remember_tokens WHERE token_hash = ?"
        );

        // Execute the deletion using the hashed token.
        $deleteToken->execute([$tokenHash]);
    }

    // Expire the remember-me cookie in the browser.
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/akwaaba',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Remove all stored session variables.
$_SESSION = [];

// Destroy the current session.
session_destroy();

// Redirect the user to the login page.
header('Location: login.php');

// Stop executing the remaining code.
exit;
?>
