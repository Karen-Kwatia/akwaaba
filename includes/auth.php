
<?php
// Start the session if one has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the database connection so we can check remember-me tokens.
require_once __DIR__ . '/db.php';

// Check whether the user has an active session.
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

    // Retrieve the remember-me token from the browser.
    $token = $_COOKIE['remember_token'];

    // Confirm that the token contains exactly 64 hexadecimal characters.
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {

        // Hash the browser token to compare it with the database value.
        $tokenHash = hash('sha256', $token);

        // Find a matching, unexpired token and retrieve its user's details.
        $query = $pdo->prepare(
            "SELECT users.id, users.name, users.email, users.role
             FROM remember_tokens
             INNER JOIN users ON remember_tokens.user_id = users.id
             WHERE remember_tokens.token_hash = ?
             AND remember_tokens.expires_at > NOW()"
        );

        // Execute the query using the hashed token.
        $query->execute([$tokenHash]);

        // Retrieve the matching user account.
        $user = $query->fetch();

        // Check whether a valid user account was found.
        if ($user) {

            // Generate a new session ID for security.
            session_regenerate_id(true);

            // Restore the user's ID in the session.
            $_SESSION['user_id'] = $user['id'];

            // Restore the user's name in the session.
            $_SESSION['user_name'] = $user['name'];

            // Restore the user's email in the session.
            $_SESSION['user_email'] = $user['email'];

            // Restore the user's role in the session.
            $_SESSION['user_role'] = $user['role'];

        } else {

            // Remove expired or invalid tokens from the database.
            $deleteToken = $pdo->prepare(
                "DELETE FROM remember_tokens WHERE token_hash = ?"
            );

            // Execute the deletion using the hashed token.
            $deleteToken->execute([$tokenHash]);

            // Expire the invalid browser cookie.
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/akwaaba',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

    } else {

        // Expire a cookie that does not contain a valid token format.
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Check whether a user is logged in.
function requireLogin($loginUrl)
{
    // Redirect visitors who do not have an active login session.
    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginUrl");
        exit;
    }
}

// Check whether a logged-in user has an authorised role.
function requireRole($allowedRoles, $loginUrl)
{
    // First, make sure the user is logged in.
    requireLogin($loginUrl);

    // Check whether the user's role is in the permitted roles.
    if (!in_array($_SESSION['user_role'], $allowedRoles, true)) {

        // Return an HTTP 403 Forbidden status.
        http_response_code(403);

        // Stop execution and display an access-denied message.
        exit('Access denied. You do not have permission to view this page.');
    }
}
?>
