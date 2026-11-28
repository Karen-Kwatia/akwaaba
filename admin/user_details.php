
<?php
// =====================================================
// AKWAABA 360
// ADMINISTRATOR USER DETAILS
// =====================================================

// Load authentication and database connection.
require_once __DIR__ . '/../includes/auth.php';

// Only administrators can inspect user account details.
requireRole(['admin'], '../auth/login.php');

// Escape database values before displaying them.
$escape = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

// =====================================================
// RETRIEVE EDIT FEEDBACK MESSAGES
// =====================================================

$successMessage = $_SESSION['admin_edit_user_success'] ?? '';
$errorMessage = $_SESSION['admin_edit_user_error'] ?? '';

// Remove flash messages after retrieving them.
unset(
    $_SESSION['admin_edit_user_success'],
    $_SESSION['admin_edit_user_error']
);

// =====================================================
// VALIDATE THE SELECTED USER ID
// =====================================================

$userId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

// Reject missing or invalid IDs.
if (!$userId || $userId < 1) {
    http_response_code(400);
    exit('Invalid user account identifier.');
}

// =====================================================
// RETRIEVE USER ACCOUNT INFORMATION
// =====================================================

try {

    // Retrieve only information that is safe to display.
    // Password hashes are deliberately excluded.
    $userQuery = $pdo->prepare(
        "SELECT
            id,
            name,
            email,
            role,
            status,
            country,
            preferred_language,
            preferred_currency,
            created_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $userQuery->execute([$userId]);

    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    // Return a 404 response if the account does not exist.
    if (!$user) {
        http_response_code(404);
        exit('The requested user account could not be found.');
    }

} catch (PDOException $exception) {

    // Record technical details in the server error log.
    error_log(
        'Akwaaba 360 user details error: ' .
        $exception->getMessage()
    );

    http_response_code(500);
    exit('Unable to retrieve the requested account details.');
}

// =====================================================
// PREPARE DISPLAY VALUES
// =====================================================

$roleLabels = [
    'tourist' => 'Tourist',
    'guide' => 'Tour Guide',
    'admin' => 'Administrator'
];

$roleLabel = $roleLabels[$user['role']] ?? 'Unknown';

$statusLabel = ucfirst($user['status']);

$registrationDate = date(
    'd F Y',
    strtotime($user['created_at'])
);

$registrationTime = date(
    'h:i A',
    strtotime($user['created_at'])
);

// Include the shared website header.
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =====================================================
   USER DETAILS PAGE
   ===================================================== */

.user-details-page {
    min-height: 75vh;
    padding: 50px 24px 75px;
    background: #F7F6F0;
}

.user-details-container {
    width: 100%;
    max-width: 950px;
    margin: 0 auto;
    font-family: 'DM Sans', sans-serif;
}

/* =====================================================
   PAGE NAVIGATION
   ===================================================== */

.details-back-link {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 28px;
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.details-back-link:hover {
    color: #2E8B70;
}

/* =====================================================
   FEEDBACK MESSAGES
   ===================================================== */

.details-alert {
    margin-bottom: 22px;
    padding: 15px 18px;
    border-radius: 3px;
    font-size: 13px;
    line-height: 1.7;
}

.details-alert.success {
    border-left: 4px solid #2E8B70;
    background: #E5F2EB;
    color: #12372A;
}

.details-alert.error {
    border-left: 4px solid #B94A48;
    background: #FCEDEA;
    color: #87352F;
}

/* =====================================================
   USER DETAILS HEADER
   ===================================================== */

.details-header {
    padding: 38px 42px;
    border-radius: 5px;
    background: #12372A;
    color: #FFFFFF;
}

.details-eyebrow {
    margin: 0 0 12px;
    color: #D9A441;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.details-header h1 {
    margin: 0 0 12px;
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: clamp(30px, 4vw, 40px);
    font-weight: 600;
}

.details-header p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 13px;
    line-height: 1.8;
}

/* =====================================================
   ACCOUNT STATUS SUMMARY
   ===================================================== */

.details-status-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-top: 25px;
    padding: 22px 25px;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.details-status-label {
    display: block;
    margin-bottom: 6px;
    color: #69736C;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.details-status-description {
    margin: 0;
    color: #69736C;
    font-size: 12px;
    line-height: 1.7;
}

.details-status-badge {
    display: inline-block;
    padding: 9px 15px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
}

.details-status-badge.active {
    background: #E5F2EB;
    color: #176B45;
}

.details-status-badge.suspended {
    background: #FCEDEA;
    color: #A33E34;
}

/* =====================================================
   ACCOUNT INFORMATION PANEL
   ===================================================== */

.details-section {
    margin-top: 32px;
    padding: 30px;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.details-section h2 {
    margin: 0 0 8px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 600;
}

.details-section-description {
    margin: 0 0 25px;
    color: #69736C;
    font-size: 12px;
    line-height: 1.8;
}

.details-heading-line {
    width: 42px;
    height: 3px;
    margin-bottom: 25px;
    background: #D9A441;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 35px;
}

.details-item {
    padding: 19px 0;
    border-bottom: 1px solid #EDF0EC;
}

.details-item-label {
    display: block;
    margin-bottom: 8px;
    color: #69736C;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.details-item-value {
    display: block;
    overflow-wrap: anywhere;
    color: #12372A;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.7;
}

/* =====================================================
   ACCOUNT ROLE DISPLAY
   ===================================================== */

.details-role {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
}

.details-role.tourist {
    background: #EAF1F8;
    color: #355D85;
}

.details-role.guide {
    background: #F8F0DD;
    color: #89651D;
}

.details-role.admin {
    background: #EEEAF6;
    color: #59427F;
}

/* =====================================================
   INFORMATION NOTE
   ===================================================== */

.details-note {
    margin-top: 25px;
    padding: 17px 20px;
    border-left: 3px solid #D9A441;
    background: #FFFFFF;
    color: #69736C;
    font-size: 12px;
    line-height: 1.8;
}

/* =====================================================
   PAGE ACTIONS
   ===================================================== */

.details-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 12px;
    margin-top: 28px;
}

.details-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 22px;
    border: 1px solid #12372A;
    border-radius: 3px;
    background: #12372A;
    color: #FFFFFF;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
}

.details-button:hover {
    border-color: #2E8B70;
    background: #2E8B70;
    color: #FFFFFF;
}

.details-button.secondary {
    border-color: #DCE4DD;
    background: #FFFFFF;
    color: #12372A;
}

.details-button.secondary:hover {
    border-color: #12372A;
    background: #F7F6F0;
    color: #12372A;
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */

@media (max-width: 650px) {
    .user-details-page {
        padding: 30px 15px 50px;
    }

    .details-header {
        padding: 30px 24px;
    }

    .details-status-panel {
        align-items: flex-start;
        flex-direction: column;
        padding: 20px;
    }

    .details-section {
        padding: 23px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .details-actions {
        flex-direction: column;
    }

    .details-button {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<!-- =====================================================
     USER DETAILS CONTENT
     ===================================================== -->

<main class="user-details-page">
    <div class="user-details-container">

        <a href="users.php" class="details-back-link">
            <span aria-hidden="true">&larr;</span>
            Back to User Management
        </a>

        <!-- Display feedback after an account edit. -->
        <?php if ($successMessage): ?>
            <div class="details-alert success" role="status">
                <?php echo $escape($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="details-alert error" role="alert">
                <?php echo $escape($errorMessage); ?>
            </div>
        <?php endif; ?>

        <section class="details-header">
            <p class="details-eyebrow">
                Administrator / Account Details
            </p>

            <h1><?php echo $escape($user['name']); ?></h1>

            <p>
                Account information and registration details
                for this Akwaaba 360 user.
            </p>
        </section>

        <!-- Current account status. -->
        <section class="details-status-panel">
            <div>
                <span class="details-status-label">
                    Account Status
                </span>

                <p class="details-status-description">
                    <?php if ($user['status'] === 'active'): ?>
                        This account is currently active.
                    <?php else: ?>
                        This account is currently suspended.
                    <?php endif; ?>
                </p>
            </div>

            <span class="details-status-badge <?php echo $escape($user['status']); ?>">
                <?php echo $escape($statusLabel); ?>
            </span>
        </section>

        <!-- Account information. -->
        <section class="details-section">
            <h2>Account Information</h2>

            <p class="details-section-description">
                Personal and account information recorded in the platform database.
            </p>

            <div class="details-heading-line"></div>

            <div class="details-grid">
                <div class="details-item">
                    <span class="details-item-label">User ID</span>
                    <span class="details-item-value">
                        #<?php echo (int)$user['id']; ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Full Name</span>
                    <span class="details-item-value">
                        <?php echo $escape($user['name']); ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Email Address</span>
                    <span class="details-item-value">
                        <?php echo $escape($user['email']); ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Country</span>
                    <span class="details-item-value">
                        <?php echo $escape($user['country'] ?: 'Not provided'); ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Account Role</span>
                    <span class="details-item-value">
                        <span class="details-role <?php echo $escape($user['role']); ?>">
                            <?php echo $escape($roleLabel); ?>
                        </span>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Account Status</span>
                    <span class="details-item-value">
                        <?php echo $escape($statusLabel); ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- Preferences information. -->
        <section class="details-section">
            <h2>Account Preferences</h2>

            <p class="details-section-description">
                Language and currency preferences selected by this user.
            </p>

            <div class="details-heading-line"></div>

            <div class="details-grid">
                <div class="details-item">
                    <span class="details-item-label">Preferred Language</span>
                    <span class="details-item-value">
                        <?php echo $escape(
                            $user['preferred_language'] ?: 'Not provided'
                        ); ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Preferred Currency</span>
                    <span class="details-item-value">
                        <?php echo $escape(
                            $user['preferred_currency'] ?: 'Not provided'
                        ); ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- Registration information. -->
        <section class="details-section">
            <h2>Registration Information</h2>

            <p class="details-section-description">
                Information about when this account was created.
            </p>

            <div class="details-heading-line"></div>

            <div class="details-grid">
                <div class="details-item">
                    <span class="details-item-label">Registration Date</span>
                    <span class="details-item-value">
                        <?php echo $escape($registrationDate); ?>
                    </span>
                </div>

                <div class="details-item">
                    <span class="details-item-label">Registration Time</span>
                    <span class="details-item-value">
                        <?php echo $escape($registrationTime); ?>
                    </span>
                </div>
            </div>
        </section>

        <div class="details-note">
            <strong>Account information:</strong>
            Personal information can be edited using the button below.
            Account role and activation status are managed separately.
        </div>

        <!-- Navigation and editing actions. -->
        <div class="details-actions">
            <a
                href="edit_user.php?id=<?php echo (int)$user['id']; ?>"
                class="details-button"
            >
                Edit Information
            </a>

            <a href="users.php" class="details-button secondary">
                Return to User Management
            </a>

            <a href="dashboard.php" class="details-button secondary">
                Admin Dashboard
            </a>
        </div>

    </div>
</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
