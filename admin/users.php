
<?php
// =====================================================
// AKWAABA 360
// ADMINISTRATOR USER MANAGEMENT
// =====================================================

// Load authentication and database connection.
require_once __DIR__ . '/../includes/auth.php';

// Restrict this page to administrators.
requireRole(['admin'], '../auth/login.php');

// Escape output before displaying database values.
$escape = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

// =====================================================
// CSRF PROTECTION
// =====================================================

if (empty($_SESSION['admin_users_csrf'])) {
    $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));
}

// =====================================================
// FLASH MESSAGES
// =====================================================

$successMessage = $_SESSION['admin_users_success'] ?? '';
$errorMessage = $_SESSION['admin_users_error'] ?? '';

unset(
    $_SESSION['admin_users_success'],
    $_SESSION['admin_users_error']
);

// =====================================================
// ACCOUNT STATUS MANAGEMENT
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['admin_users_csrf'], $submittedToken)
    ) {
        $_SESSION['admin_users_error'] =
            'Your security token is invalid. Please try again.';

    } else {

        $action = $_POST['action'] ?? '';

        $targetUserId = filter_var(
            $_POST['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !in_array($action, ['activate', 'suspend'], true) ||
            !$targetUserId ||
            $targetUserId < 1
        ) {
            $_SESSION['admin_users_error'] =
                'The requested account action is invalid.';

        } elseif (
            $targetUserId === (int)$_SESSION['user_id'] &&
            $action === 'suspend'
        ) {
            $_SESSION['admin_users_error'] =
                'You cannot suspend your own administrator account.';

        } else {

            try {

                $pdo->beginTransaction();

                // Lock the selected account before changing its status.
                $userQuery = $pdo->prepare(
                    "SELECT id, name, role, status
                     FROM users
                     WHERE id = ?
                     FOR UPDATE"
                );

                $userQuery->execute([$targetUserId]);
                $targetUser = $userQuery->fetch(PDO::FETCH_ASSOC);

                if (!$targetUser) {

                    throw new RuntimeException(
                        'The selected user does not exist.'
                    );

                } elseif (
                    $action === 'suspend' &&
                    $targetUser['role'] === 'admin'
                ) {

                    // Lock administrator records before checking
                    // whether another active administrator remains.
                    $adminQuery = $pdo->query(
                        "SELECT id, status
                         FROM users
                         WHERE role = 'admin'
                         FOR UPDATE"
                    );

                    $administrators = $adminQuery->fetchAll(PDO::FETCH_ASSOC);

                    $activeAdminCount = 0;

                    foreach ($administrators as $administrator) {
                        if ($administrator['status'] === 'active') {
                            $activeAdminCount++;
                        }
                    }

                    // Protect the last active administrator.
                    if (
                        $targetUser['status'] === 'active' &&
                        $activeAdminCount <= 1
                    ) {
                        throw new RuntimeException(
                            'You cannot suspend the last active administrator.'
                        );
                    }
                }

                // Determine the new account status.
                $newStatus = ($action === 'activate')
                    ? 'active'
                    : 'suspended';

                // Update the account.
                $updateQuery = $pdo->prepare(
                    "UPDATE users
                     SET status = ?
                     WHERE id = ?"
                );

                $updateQuery->execute([
                    $newStatus,
                    $targetUserId
                ]);

                // Revoke Remember Me tokens when suspending an account.
                if ($newStatus === 'suspended') {

                    $deleteTokens = $pdo->prepare(
                        "DELETE FROM remember_tokens
                         WHERE user_id = ?"
                    );

                    $deleteTokens->execute([$targetUserId]);
                }

                $pdo->commit();

                $_SESSION['admin_users_success'] =
                    $targetUser['name'] . ' has been ' .
                    ($newStatus === 'active' ? 'activated.' : 'suspended.');

            } catch (Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log(
                    'Akwaaba 360 user management error: ' .
                    $exception->getMessage()
                );

                $_SESSION['admin_users_error'] =
                    $exception instanceof RuntimeException
                        ? $exception->getMessage()
                        : 'The account could not be updated. Please try again.';
            }
        }
    }

    // Rotate the CSRF token after the form submission.
    $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));

    // Prevent the form from being submitted again on refresh.
    header('Location: users.php');
    exit;
}

// =====================================================
// SEARCH AND FILTER SETTINGS
// =====================================================

$search = trim($_GET['search'] ?? '');
$search = substr($search, 0, 100);

$allowedRoles = ['tourist', 'guide', 'admin'];

$selectedRole = $_GET['role'] ?? '';

if (!in_array($selectedRole, $allowedRoles, true)) {
    $selectedRole = '';
}

$allowedStatuses = ['active', 'suspended'];

$selectedStatus = $_GET['status'] ?? '';

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}

// =====================================================
// PAGINATION
// =====================================================

$usersPerPage = 10;

$currentPage = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

if (!$currentPage || $currentPage < 1) {
    $currentPage = 1;
}

// =====================================================
// DATABASE STATISTICS AND USER RECORDS
// =====================================================

try {

    $totalUsers = (int)$pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();

    $totalTourists = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'tourist'"
    )->fetchColumn();

    $totalGuides = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'guide'"
    )->fetchColumn();

    $totalAdmins = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin'"
    )->fetchColumn();

    // Retrieve safe account information.
    // Password hashes are deliberately excluded.
    $sql = "
        SELECT
            id,
            name,
            email,
            role,
            country,
            preferred_language,
            preferred_currency,
            status,
            created_at
        FROM users
        WHERE 1 = 1
    ";

    $parameters = [];

    // Search by name or email.
    if ($search !== '') {

        $sql .= "
            AND (
                name LIKE :search_name
                OR email LIKE :search_email
            )
        ";

        $parameters['search_name'] = '%' . $search . '%';
        $parameters['search_email'] = '%' . $search . '%';
    }

    // Filter by role.
    if ($selectedRole !== '') {
        $sql .= " AND role = :selected_role";
        $parameters['selected_role'] = $selectedRole;
    }

    // Filter by account status.
    if ($selectedStatus !== '') {
        $sql .= " AND status = :selected_status";
        $parameters['selected_status'] = $selectedStatus;
    }

    // Count matching records for pagination.
    $countSql = "
        SELECT COUNT(*)
        FROM users
        WHERE 1 = 1
    ";

    if ($search !== '') {
        $countSql .= "
            AND (
                name LIKE :search_name
                OR email LIKE :search_email
            )
        ";
    }

    if ($selectedRole !== '') {
        $countSql .= " AND role = :selected_role";
    }

    if ($selectedStatus !== '') {
        $countSql .= " AND status = :selected_status";
    }

    $countQuery = $pdo->prepare($countSql);
    $countQuery->execute($parameters);

    $filteredUsers = (int)$countQuery->fetchColumn();

    // Calculate available pages.
    $totalPages = max(
        1,
        (int)ceil($filteredUsers / $usersPerPage)
    );

    $currentPage = min($currentPage, $totalPages);

    $offset = ($currentPage - 1) * $usersPerPage;

    // Retrieve the records for the current page.
    $sql .= "
        ORDER BY created_at DESC, id DESC
        LIMIT :limit OFFSET :offset
    ";

    $usersQuery = $pdo->prepare($sql);

    foreach ($parameters as $parameter => $value) {
        $usersQuery->bindValue(':' . $parameter, $value);
    }

    $usersQuery->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
    $usersQuery->bindValue(':offset', $offset, PDO::PARAM_INT);

    $usersQuery->execute();

    $users = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {

    error_log(
        'Akwaaba 360 user listing error: ' .
        $exception->getMessage()
    );

    $users = [];
    $totalUsers = 0;
    $totalTourists = 0;
    $totalGuides = 0;
    $totalAdmins = 0;
    $filteredUsers = 0;
    $totalPages = 1;
    $currentPage = 1;

    $errorMessage = 'Unable to retrieve user records. Please try again.';
}

// Preserve filters when changing pages.
$paginationParameters = [
    'search' => $search,
    'role' => $selectedRole,
    'status' => $selectedStatus
];

// Include the shared header.
require_once __DIR__ . '/../includes/header.php';
?>

<style>

/* =====================================================
   USER MANAGEMENT PAGE
   ===================================================== */

.users-page {
    min-height: 75vh;
    padding: 50px 24px 75px;
    background: #F7F6F0;
}

.users-container {
    width: 100%;
    max-width: 1160px;
    margin: 0 auto;
    font-family: 'DM Sans', sans-serif;
}

/* =====================================================
   PAGE INTRODUCTION
   ===================================================== */

.users-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 32px;
}

.users-eyebrow {
    margin: 0 0 10px;
    color: #2E8B70;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.users-heading h1 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: clamp(32px, 4vw, 43px);
    font-weight: 600;
}

.users-heading p {
    margin: 0;
    color: #69736C;
    font-size: 14px;
    line-height: 1.8;
}

.users-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.users-back-link:hover {
    color: #2E8B70;
}

/* =====================================================
   FLASH MESSAGES
   ===================================================== */

.users-message {
    margin-bottom: 24px;
    padding: 15px 18px;
    border-radius: 4px;
    font-size: 13px;
    line-height: 1.7;
}

.users-message.success {
    border: 1px solid #B7D8C4;
    background: #E5F2EB;
    color: #12372A;
}

.users-message.error {
    border: 1px solid #E5B9B4;
    background: #FCEDEA;
    color: #8B3028;
}

/* =====================================================
   STATISTICS
   ===================================================== */

.users-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 17px;
}

.users-stat-card {
    position: relative;
    overflow: hidden;
    padding: 24px;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.users-stat-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: #D9A441;
}

.users-stat-label {
    display: block;
    margin-bottom: 14px;
    color: #69736C;
    font-size: 12px;
    font-weight: 600;
}

.users-stat-value {
    display: block;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    font-weight: 600;
}

/* =====================================================
   SECTION HEADING
   ===================================================== */

.users-section {
    margin-top: 45px;
}

.users-section-heading {
    margin: 0 0 8px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 27px;
    font-weight: 600;
}

.users-section-description {
    margin: 0 0 23px;
    color: #69736C;
    font-size: 13px;
    line-height: 1.8;
}

/* =====================================================
   SEARCH AND FILTER PANEL
   ===================================================== */

.users-filter-panel {
    display: grid;
    grid-template-columns: minmax(220px, 1.6fr) 1fr 1fr auto;
    gap: 13px;
    align-items: end;
    margin-bottom: 23px;
    padding: 22px;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.users-field label {
    display: block;
    margin-bottom: 8px;
    color: #35443B;
    font-size: 12px;
    font-weight: 700;
}

.users-field input,
.users-field select {
    width: 100%;
    height: 43px;
    box-sizing: border-box;
    padding: 0 12px;
    border: 1px solid #DCE4DD;
    border-radius: 3px;
    outline: none;
    background: #FFFFFF;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
}

.users-field input:focus,
.users-field select:focus {
    border-color: #2E8B70;
    box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.10);
}

.users-filter-actions {
    display: flex;
    gap: 8px;
}

.users-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 43px;
    padding: 0 17px;
    border: 1px solid #12372A;
    border-radius: 3px;
    background: #12372A;
    color: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}

.users-button:hover {
    border-color: #2E8B70;
    background: #2E8B70;
    color: #FFFFFF;
}

.users-clear-button {
    border-color: #DCE4DD;
    background: #FFFFFF;
    color: #12372A;
}

.users-clear-button:hover {
    border-color: #12372A;
    background: #F7F6F0;
    color: #12372A;
}

/* =====================================================
   USER TABLE
   ===================================================== */

.users-table-panel {
    overflow: hidden;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.users-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 22px 24px;
    border-bottom: 1px solid #E3E9E4;
}

.users-table-header h3 {
    margin: 0;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 600;
}

.users-result-count {
    color: #69736C;
    font-size: 12px;
}

.users-table-wrapper {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    white-space: nowrap;
}

.users-table th {
    padding: 15px 18px;
    border-bottom: 1px solid #E3E9E4;
    background: #F7F6F0;
    color: #69736C;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.users-table td {
    padding: 17px 18px;
    border-bottom: 1px solid #EDF0EC;
    color: #35443B;
    font-size: 12px;
    vertical-align: middle;
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

.users-table tbody tr:hover {
    background: #FCFCF9;
}

.user-name {
    display: block;
    margin-bottom: 4px;
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
}

.user-email {
    color: #69736C;
    font-size: 11px;
}

/* =====================================================
   ROLE AND STATUS LABELS
   ===================================================== */

.user-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 700;
    text-transform: capitalize;
}

.role-tourist {
    background: #EAF1F8;
    color: #355D85;
}

.role-guide {
    background: #F8F0DD;
    color: #89651D;
}

.role-admin {
    background: #EEEAF6;
    color: #59427F;
}

.status-active {
    background: #E5F2EB;
    color: #176B45;
}

.status-suspended {
    background: #FCEDEA;
    color: #A33E34;
}

/* =====================================================
   ACCOUNT ACTION BUTTONS
   ===================================================== */

.user-action-form {
    display: inline;
}

.user-action-button {
    min-height: 32px;
    padding: 0 11px;
    border: 1px solid;
    border-radius: 3px;
    background: #FFFFFF;
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
}

.user-suspend-button {
    border-color: #E2B7B1;
    color: #9B3B32;
}

.user-suspend-button:hover {
    background: #FCEDEA;
}

.user-activate-button {
    border-color: #A8CEB5;
    color: #176B45;
}

.user-activate-button:hover {
    background: #E5F2EB;
}

.user-self-label {
    color: #69736C;
    font-size: 11px;
    font-style: italic;
}

/* View Details link. */
.user-details-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    margin-right: 6px;
    padding: 0 11px;
    border: 1px solid #B9CFC2;
    border-radius: 3px;
    background: #FFFFFF;
    color: #12372A;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
}

.user-details-link:hover {
    border-color: #2E8B70;
    background: #E5F2EB;
    color: #12372A;
}

.user-details-link:focus-visible {
    outline: 3px solid #D9A441;
    outline-offset: 3px;
}

/* =====================================================
   EMPTY STATE
   ===================================================== */

.users-empty {
    padding: 55px 20px;
    text-align: center;
}

.users-empty h3 {
    margin: 0 0 10px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
}

.users-empty p {
    margin: 0;
    color: #69736C;
    font-size: 13px;
}

/* =====================================================
   PAGINATION
   ===================================================== */

.users-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 20px 24px;
    border-top: 1px solid #E3E9E4;
}

.users-pagination-info {
    color: #69736C;
    font-size: 12px;
}

.users-pagination-links {
    display: flex;
    align-items: center;
    gap: 7px;
}

.users-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 9px;
    border: 1px solid #DCE4DD;
    border-radius: 3px;
    color: #12372A;
    font-size: 12px;
    text-decoration: none;
}

.users-page-link:hover,
.users-page-link.current {
    border-color: #12372A;
    background: #12372A;
    color: #FFFFFF;
}

/* =====================================================
   SECURITY INFORMATION
   ===================================================== */

.users-security-note {
    margin-top: 22px;
    padding: 17px 20px;
    border-left: 3px solid #D9A441;
    background: #FFFFFF;
    color: #69736C;
    font-size: 12px;
    line-height: 1.8;
}

/* =====================================================
   RESPONSIVE DESIGN
   ===================================================== */

@media (max-width: 900px) {

    .users-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .users-filter-panel {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .users-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 600px) {

    .users-page {
        padding: 30px 15px 50px;
    }

    .users-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .users-heading h1 {
        font-size: 33px;
    }

    .users-stats {
        gap: 11px;
    }

    .users-stat-card {
        padding: 18px;
    }

    .users-stat-value {
        font-size: 29px;
    }

    .users-filter-panel {
        grid-template-columns: 1fr;
        padding: 17px;
    }

    .users-filter-actions {
        grid-column: auto;
    }

    .users-filter-actions .users-button {
        flex: 1;
    }

    .users-table-header {
        align-items: flex-start;
        flex-direction: column;
        padding: 18px;
    }

    .users-pagination {
        align-items: flex-start;
        flex-direction: column;
        padding: 18px;
    }

    .users-pagination-links {
        flex-wrap: wrap;
    }
}

</style>

<!-- =====================================================
     USER MANAGEMENT PAGE CONTENT
     ===================================================== -->

<main class="users-page">

    <div class="users-container">

        <section class="users-heading">

            <div>
                <p class="users-eyebrow">Administration / Accounts</p>

                <h1>User Management</h1>

                <p>
                    View registered accounts and manage access
                    to the Akwaaba 360 platform.
                </p>
            </div>

            <a href="dashboard.php" class="users-back-link">
                <span aria-hidden="true">&larr;</span>
                Back to Dashboard
            </a>

        </section>

        <!-- Display operation feedback. -->
        <?php if ($successMessage !== ''): ?>
            <div class="users-message success" role="status">
                <?php echo $escape($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="users-message error" role="alert">
                <?php echo $escape($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- User statistics. -->
        <section class="users-stats">

            <article class="users-stat-card">
                <span class="users-stat-label">Total Registered Users</span>
                <strong class="users-stat-value">
                    <?php echo number_format($totalUsers); ?>
                </strong>
            </article>

            <article class="users-stat-card">
                <span class="users-stat-label">Tourists</span>
                <strong class="users-stat-value">
                    <?php echo number_format($totalTourists); ?>
                </strong>
            </article>

            <article class="users-stat-card">
                <span class="users-stat-label">Tour Guides</span>
                <strong class="users-stat-value">
                    <?php echo number_format($totalGuides); ?>
                </strong>
            </article>

            <article class="users-stat-card">
                <span class="users-stat-label">Administrators</span>
                <strong class="users-stat-value">
                    <?php echo number_format($totalAdmins); ?>
                </strong>
            </article>

        </section>

        <!-- Search and filtering section. -->
        <section class="users-section">

            <h2 class="users-section-heading">Registered Accounts</h2>

            <p class="users-section-description">
                Search for users and narrow the list by account role or status.
            </p>

            <form method="GET" action="users.php" class="users-filter-panel">

                <div class="users-field">

                    <label for="user-search">Search users</label>

                    <input
                        type="search"
                        id="user-search"
                        name="search"
                        placeholder="Name or email address"
                        value="<?php echo $escape($search); ?>"
                    >

                </div>

                <div class="users-field">

                    <label for="user-role">Account role</label>

                    <select id="user-role" name="role">

                        <option value="">All roles</option>

                        <option value="tourist"
                            <?php echo $selectedRole === 'tourist' ? 'selected' : ''; ?>>
                            Tourist
                        </option>

                        <option value="guide"
                            <?php echo $selectedRole === 'guide' ? 'selected' : ''; ?>>
                            Tour Guide
                        </option>

                        <option value="admin"
                            <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>>
                            Administrator
                        </option>

                    </select>

                </div>

                <div class="users-field">

                    <label for="user-status">Account status</label>

                    <select id="user-status" name="status">

                        <option value="">All statuses</option>

                        <option value="active"
                            <?php echo $selectedStatus === 'active' ? 'selected' : ''; ?>>
                            Active
                        </option>

                        <option value="suspended"
                            <?php echo $selectedStatus === 'suspended' ? 'selected' : ''; ?>>
                            Suspended
                        </option>

                    </select>

                </div>

                <div class="users-filter-actions">

                    <button type="submit" class="users-button">
                        Search
                    </button>

                    <a href="users.php" class="users-button users-clear-button">
                        Clear
                    </a>

                </div>

            </form>

            <!-- User records table. -->
            <div class="users-table-panel">

                <div class="users-table-header">

                    <h3>Platform Users</h3>

                    <span class="users-result-count">
                        <?php echo number_format($filteredUsers); ?>
                        matching account(s)
                    </span>

                </div>

                <?php if (!empty($users)): ?>

                    <div class="users-table-wrapper">

                        <table class="users-table">

                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Country</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($users as $user): ?>

                                    <?php
                                    $roleClass = 'role-' . $user['role'];
                                    $statusClass = 'status-' . $user['status'];

                                    $registrationDate = date(
                                        'd M Y',
                                        strtotime($user['created_at'])
                                    );
                                    ?>

                                    <tr>

                                        <td>
                                            <span class="user-name">
                                                <?php echo $escape($user['name']); ?>
                                            </span>

                                            <span class="user-email">
                                                <?php echo $escape($user['email']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php echo $escape($user['country'] ?: 'Not provided'); ?>
                                        </td>

                                        <td>
                                            <span class="user-badge <?php echo $escape($roleClass); ?>">
                                                <?php
                                                echo $escape(
                                                    $user['role'] === 'admin'
                                                        ? 'Administrator'
                                                        : ($user['role'] === 'guide'
                                                            ? 'Tour Guide'
                                                            : 'Tourist')
                                                );
                                                ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php echo $escape($registrationDate); ?>
                                        </td>

                                        <td>
                                            <span class="user-badge <?php echo $escape($statusClass); ?>">
                                                <?php echo $escape($user['status']); ?>
                                            </span>
                                        </td>

                                        <td>

                                            <!-- Every account can be inspected,
                                                 including the current admin account. -->
                                            <a
                                                href="user_details.php?id=<?php echo (int)$user['id']; ?>"
                                                class="user-details-link"
                                            >
                                                View Details
                                            </a>

                                            <?php if (
                                                (int)$user['id'] === (int)$_SESSION['user_id']
                                            ): ?>

                                                <span class="user-self-label">
                                                    Your account
                                                </span>

                                            <?php elseif ($user['status'] === 'active'): ?>

                                                <form
                                                    method="POST"
                                                    action="users.php"
                                                    class="user-action-form"
                                                    onsubmit="return confirm('Are you sure you want to suspend this account?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?php echo (int)$user['id']; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="suspend"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="user-action-button user-suspend-button"
                                                    >
                                                        Suspend
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <form
                                                    method="POST"
                                                    action="users.php"
                                                    class="user-action-form"
                                                    onsubmit="return confirm('Are you sure you want to activate this account?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?php echo (int)$user['id']; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="activate"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="user-action-button user-activate-button"
                                                    >
                                                        Activate
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="users-empty">

                        <h3>No users found</h3>

                        <p>
                            Try changing your search term or filter settings.
                        </p>

                    </div>

                <?php endif; ?>

                <!-- Pagination. -->
                <?php if ($filteredUsers > 0): ?>

                    <div class="users-pagination">

                        <span class="users-pagination-info">

                            Showing
                            <?php echo number_format($offset + 1); ?>

                            to

                            <?php echo number_format(
                                min($offset + $usersPerPage, $filteredUsers)
                            ); ?>

                            of

                            <?php echo number_format($filteredUsers); ?>

                            users

                        </span>

                        <?php if ($totalPages > 1): ?>

                            <nav class="users-pagination-links" aria-label="User list pages">

                                <?php if ($currentPage > 1): ?>

                                    <?php
                                    $previousParameters = $paginationParameters;
                                    $previousParameters['page'] = $currentPage - 1;
                                    ?>

                                    <a
                                        href="users.php?<?php echo $escape(http_build_query($previousParameters)); ?>"
                                        class="users-page-link"
                                    >
                                        &laquo;
                                    </a>

                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);
                                ?>

                                <?php for ($page = $startPage; $page <= $endPage; $page++): ?>

                                    <?php
                                    $pageParameters = $paginationParameters;
                                    $pageParameters['page'] = $page;
                                    ?>

                                    <a
                                        href="users.php?<?php echo $escape(http_build_query($pageParameters)); ?>"
                                        class="users-page-link <?php echo $page === $currentPage ? 'current' : ''; ?>"
                                        <?php echo $page === $currentPage ? 'aria-current="page"' : ''; ?>
                                    >
                                        <?php echo $page; ?>
                                    </a>

                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>

                                    <?php
                                    $nextParameters = $paginationParameters;
                                    $nextParameters['page'] = $currentPage + 1;
                                    ?>

                                    <a
                                        href="users.php?<?php echo $escape(http_build_query($nextParameters)); ?>"
                                        class="users-page-link"
                                    >
                                        &raquo;
                                    </a>

                                <?php endif; ?>

                            </nav>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>

        <div class="users-security-note">
            <strong>Account security:</strong>
            Suspended accounts cannot access protected areas of the platform.
            Their Remember Me tokens are also revoked. You cannot suspend
            your own administrator account or the last active administrator.
        </div>

    </div>

</main>

<!-- Restore the scroll position after searching. -->
<script>
(function () {

    const storageKey = 'akwaaba360_users_scroll_position';

    const searchForm = document.querySelector('.users-filter-panel');

    if (searchForm) {

        searchForm.addEventListener('submit', function () {

            sessionStorage.setItem(
                storageKey,
                String(window.scrollY)
            );

        });

    }

    const savedPosition = sessionStorage.getItem(storageKey);

    if (savedPosition !== null) {

        sessionStorage.removeItem(storageKey);

        const scrollPosition = Number(savedPosition);

        if (Number.isFinite(scrollPosition)) {

            window.addEventListener('load', function () {
                window.scrollTo(0, scrollPosition);
            }, { once: true });

        }

    }

})();
</script>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
