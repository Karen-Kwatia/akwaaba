
<?php
// =====================================================
// AKWAABA 360
// CENTRAL AUTHENTICATION AND ACCESS CONTROL
// =====================================================

// Start the session if one has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the database connection.
require_once __DIR__ . '/db.php';

// =====================================================
// REMEMBER-ME AUTHENTICATION
// =====================================================

// Restore a user's session when no active session exists
// but a Remember Me cookie is available.
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

    // Retrieve the browser's Remember Me token.
    $token = $_COOKIE['remember_token'];

    // Only accept a token containing 64 hexadecimal characters.
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {

        // Hash the token before comparing it with the database.
        $tokenHash = hash('sha256', $token);

        // Find the matching token and retrieve the account status.
        $query = $pdo->prepare(
            "SELECT
                users.id,
                users.name,
                users.email,
                users.role,
                users.status
             FROM remember_tokens
             INNER JOIN users ON remember_tokens.user_id = users.id
             WHERE remember_tokens.token_hash = ?
             AND remember_tokens.expires_at > NOW()
             LIMIT 1"
        );

        // Execute the query.
        $query->execute([$tokenHash]);

        // Retrieve the matching account.
        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Restore the session only if the account is active.
        if ($user && $user['status'] === 'active') {

            // Generate a new session ID for security.
            session_regenerate_id(true);

            // Restore the user's account information.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

        } else {

            // Delete the invalid or expired Remember Me token.
            $deleteToken = $pdo->prepare(
                "DELETE FROM remember_tokens WHERE token_hash = ?"
            );

            $deleteToken->execute([$tokenHash]);

            // If the account exists but is suspended,
            // revoke all its Remember Me tokens.
            if ($user && $user['status'] !== 'active') {

                $deleteAllTokens = $pdo->prepare(
                    "DELETE FROM remember_tokens WHERE user_id = ?"
                );

                $deleteAllTokens->execute([$user['id']]);
            }

            // Remove the invalid Remember Me cookie.
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/akwaaba',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

    } else {

        // Remove a cookie containing an invalid token format.
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// =====================================================
// ACTIVE ACCOUNT VERIFICATION
// =====================================================

// Check every existing session against the database.
// This ensures suspended users cannot continue browsing
// simply because they were logged in before suspension.
if (isset($_SESSION['user_id'])) {

    // Retrieve the latest account information.
    $accountQuery = $pdo->prepare(
        "SELECT id, name, email, role, status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $accountQuery->execute([$_SESSION['user_id']]);

    $account = $accountQuery->fetch(PDO::FETCH_ASSOC);

    // Reject missing or suspended accounts.
    if (!$account || $account['status'] !== 'active') {

        // Revoke all Remember Me tokens belonging to this account.
        if ($account) {
            $deleteAllTokens = $pdo->prepare(
                "DELETE FROM remember_tokens WHERE user_id = ?"
            );

            $deleteAllTokens->execute([$account['id']]);
        } else {
            // Also clean up tokens if the account no longer exists.
            $deleteAllTokens = $pdo->prepare(
                "DELETE FROM remember_tokens WHERE user_id = ?"
            );

            $deleteAllTokens->execute([$_SESSION['user_id']]);
        }

        // Remove the user's session information.
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['user_role']
        );

        // Generate a fresh session ID.
        session_regenerate_id(true);

        // Expire the Remember Me cookie.
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/akwaaba',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Remove the cookie from the current request data.
        unset($_COOKIE['remember_token']);

    } else {

        // Refresh session details in case an administrator
        // has updated the user's name, email or role.
        $_SESSION['user_name'] = $account['name'];
        $_SESSION['user_email'] = $account['email'];
        $_SESSION['user_role'] = $account['role'];
    }
}

// =====================================================
// LOGIN PROTECTION
// =====================================================

/**
 * Require an authenticated and active user.
 *
 * Users without a valid session are redirected to login.
 */
function requireLogin($loginUrl)
{
    // Redirect visitors who are not logged in.
    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginUrl");
        exit;
    }
}

// =====================================================
// ROLE-BASED ACCESS CONTROL
// =====================================================

/**
 * Restrict a page to specified user roles.
 *
 * The user must be logged in and have an authorised role.
 */
function requireRole($allowedRoles, $loginUrl)
{
    // First, verify that the user has an active session.
    requireLogin($loginUrl);

    // Check whether the user's role is authorised.
    if (!in_array($_SESSION['user_role'], $allowedRoles, true)) {

        // Return HTTP 403 Forbidden.
        http_response_code(403);

        // Stop execution and display an access-denied message.
        exit('Access denied. You do not have permission to view this page.');
    }
}

?>
