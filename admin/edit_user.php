
<?php
// =====================================================
// AKWAABA 360
// ADMINISTRATOR EDIT USER INFORMATION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin'], '../auth/login.php');

$escape = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

// =====================================================
// VALIDATE THE SELECTED USER ID
// =====================================================

$userId = filter_var(
    $_GET['id'] ?? $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {
    http_response_code(400);
    exit('Invalid user account identifier.');
}

// =====================================================
// AVAILABLE LANGUAGES
// =====================================================

$languageOptions = [
    'en' => 'English',
    'tw' => 'Twi',
    'fr' => 'French',
    'es' => 'Spanish'
];

// =====================================================
// AVAILABLE CURRENCIES
// =====================================================

$currencyOptions = [
    'GHS' => 'Ghana Cedi (GHS)',
    'USD' => 'US Dollar (USD)',
    'EUR' => 'Euro (EUR)',
    'GBP' => 'British Pound (GBP)'
];

// =====================================================
// AVAILABLE COUNTRIES
// =====================================================

$countryOptions = [
    'Afghanistan',
    'Albania',
    'Algeria',
    'Angola',
    'Argentina',
    'Australia',
    'Austria',
    'Bangladesh',
    'Belgium',
    'Benin',
    'Botswana',
    'Brazil',
    'Burkina Faso',
    'Burundi',
    'Cameroon',
    'Canada',
    'Cape Verde',
    'Central African Republic',
    'Chad',
    'Chile',
    'China',
    'Colombia',
    'Comoros',
    'Democratic Republic of the Congo',
    'Denmark',
    'Djibouti',
    'Egypt',
    'Equatorial Guinea',
    'Eritrea',
    'Eswatini',
    'Ethiopia',
    'Finland',
    'France',
    'Gabon',
    'Gambia',
    'Georgia',
    'Germany',
    'Ghana',
    'Greece',
    'Guinea',
    'Guinea-Bissau',
    'India',
    'Indonesia',
    'Iran',
    'Iraq',
    'Ireland',
    'Israel',
    'Italy',
    'Ivory Coast',
    'Japan',
    'Kenya',
    'Lesotho',
    'Liberia',
    'Libya',
    'Madagascar',
    'Malawi',
    'Malaysia',
    'Mali',
    'Mauritania',
    'Mauritius',
    'Mexico',
    'Morocco',
    'Mozambique',
    'Namibia',
    'Nepal',
    'Netherlands',
    'New Zealand',
    'Niger',
    'Nigeria',
    'Norway',
    'Pakistan',
    'Philippines',
    'Poland',
    'Portugal',
    'Qatar',
    'Republic of the Congo',
    'Rwanda',
    'Saudi Arabia',
    'Senegal',
    'Seychelles',
    'Sierra Leone',
    'Singapore',
    'Somalia',
    'South Africa',
    'South Korea',
    'South Sudan',
    'Spain',
    'Sri Lanka',
    'Sudan',
    'Sweden',
    'Switzerland',
    'Tanzania',
    'Thailand',
    'Togo',
    'Tunisia',
    'Turkey',
    'Uganda',
    'Ukraine',
    'United Arab Emirates',
    'United Kingdom',
    'United States',
    'Vietnam',
    'Zambia',
    'Zimbabwe'
];

// Keep the country list alphabetically ordered.
sort($countryOptions, SORT_STRING | SORT_FLAG_CASE);

// =====================================================
// INITIALISE FORM FEEDBACK
// =====================================================

$errorMessage = '';

// =====================================================
// RETRIEVE EXISTING USER INFORMATION
// =====================================================

try {

    $userQuery = $pdo->prepare(
        "SELECT
            id,
            name,
            email,
            role,
            status,
            country,
            preferred_language,
            preferred_currency
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $userQuery->execute([$userId]);

    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        exit('The requested user account could not be found.');
    }

} catch (PDOException $exception) {

    error_log(
        'Akwaaba 360 edit user retrieval error: ' .
        $exception->getMessage()
    );

    http_response_code(500);
    exit('Unable to retrieve the selected user account.');
}

// =====================================================
// INITIALISE FORM VALUES
// =====================================================

$formData = [
    'name' => $user['name'],
    'email' => $user['email'],
    'country' => $user['country'] ?? '',
    'preferred_language' => $user['preferred_language'] ?? '',
    'preferred_currency' => $user['preferred_currency'] ?? ''
];

// =====================================================
// PROCESS FORM SUBMISSION
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['admin_edit_user_csrf'] ?? '';

    // Verify the CSRF token.
    if (
        !is_string($submittedToken) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {

        $errorMessage = 'Your form session has expired. Please reload the page and try again.';

    } else {

        // Retrieve submitted values.
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['country'] = trim($_POST['country'] ?? '');

        $formData['preferred_language'] = trim(
            $_POST['preferred_language'] ?? ''
        );

        $formData['preferred_currency'] = trim(
            $_POST['preferred_currency'] ?? ''
        );

        // =====================================================
        // VALIDATE SUBMITTED INFORMATION
        // =====================================================

        if (
            $formData['name'] === '' ||
            strlen($formData['name']) > 100
        ) {

            $errorMessage = 'Please enter a name containing no more than 100 characters.';

        } elseif (
            $formData['email'] === '' ||
            strlen($formData['email']) > 150 ||
            !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)
        ) {

            $errorMessage = 'Please enter a valid email address containing no more than 150 characters.';

        } elseif (
            $formData['country'] !== '' &&
            !in_array($formData['country'], $countryOptions, true)
        ) {

            $errorMessage = 'Please select a valid country from the list.';

        } elseif (
            $formData['preferred_language'] !== '' &&
            !array_key_exists(
                $formData['preferred_language'],
                $languageOptions
            )
        ) {

            $errorMessage = 'Please select a supported language.';

        } elseif (
            $formData['preferred_currency'] !== '' &&
            !array_key_exists(
                $formData['preferred_currency'],
                $currencyOptions
            )
        ) {

            $errorMessage = 'Please select a supported currency.';

        } else {

            // =====================================================
            // CHECK FOR DUPLICATE EMAIL ADDRESSES
            // =====================================================

            try {

                $emailQuery = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = ?
                       AND id <> ?
                     LIMIT 1"
                );

                $emailQuery->execute([
                    $formData['email'],
                    $userId
                ]);

                $existingEmail = $emailQuery->fetch(PDO::FETCH_ASSOC);

                if ($existingEmail) {

                    $errorMessage = 'Another account is already registered with this email address.';

                } else {

                    // =====================================================
                    // UPDATE USER INFORMATION
                    // =====================================================

                    $updateQuery = $pdo->prepare(
                        "UPDATE users
                         SET
                            name = :name,
                            email = :email,
                            country = :country,
                            preferred_language = :preferred_language,
                            preferred_currency = :preferred_currency
                         WHERE id = :id"
                    );

                    $updateQuery->execute([
                        ':name' => $formData['name'],
                        ':email' => $formData['email'],
                        ':country' => $formData['country'] !== ''
                            ? $formData['country']
                            : null,
                        ':preferred_language' => $formData['preferred_language'] !== ''
                            ? $formData['preferred_language']
                            : null,
                        ':preferred_currency' => $formData['preferred_currency'] !== ''
                            ? $formData['preferred_currency']
                            : 'GHS',
                        ':id' => $userId
                    ]);

                    // Rotate the CSRF token.
                    $_SESSION['admin_edit_user_csrf'] = bin2hex(
                        random_bytes(32)
                    );

                    // Set a success message.
                    $_SESSION['admin_edit_user_success'] =
                        'User information was updated successfully.';

                    // Return to the user's details page.
                    header(
                        'Location: user_details.php?id=' . (int)$userId
                    );

                    exit;
                }

            } catch (PDOException $exception) {

                error_log(
                    'Akwaaba 360 user update error: ' .
                    $exception->getMessage()
                );

                if ($exception->getCode() === '23000') {
                    $errorMessage = 'That email address is already being used by another account.';
                } else {
                    $errorMessage = 'Unable to update the user information. Please try again.';
                }
            }
        }
    }
}

// =====================================================
// GENERATE CSRF TOKEN
// =====================================================

if (
    empty($_SESSION['admin_edit_user_csrf']) ||
    !is_string($_SESSION['admin_edit_user_csrf'])
) {
    $_SESSION['admin_edit_user_csrf'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['admin_edit_user_csrf'];

// Include the shared header.
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =====================================================
   EDIT USER PAGE
   ===================================================== */

.edit-user-page {
    min-height: 75vh;
    padding: 50px 24px 75px;
    background: #F7F6F0;
}

.edit-user-container {
    width: 100%;
    max-width: 850px;
    margin: 0 auto;
    font-family: 'DM Sans', sans-serif;
}

/* Navigation link. */

.edit-back-link {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 28px;
    color: #12372A;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.edit-back-link:hover {
    color: #2E8B70;
}

/* Page heading. */

.edit-header {
    padding: 35px 40px;
    border-radius: 5px;
    background: #12372A;
    color: #FFFFFF;
}

.edit-eyebrow {
    margin: 0 0 12px;
    color: #D9A441;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.edit-header h1 {
    margin: 0 0 12px;
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 4vw, 38px);
    font-weight: 600;
}

.edit-header p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 13px;
    line-height: 1.8;
}

/* Form panel. */

.edit-form-panel {
    margin-top: 25px;
    padding: 35px;
    border: 1px solid #E3E9E4;
    border-radius: 5px;
    background: #FFFFFF;
}

.edit-form-panel h2 {
    margin: 0 0 8px;
    color: #12372A;
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    font-weight: 600;
}

.edit-form-description {
    margin: 0 0 28px;
    color: #69736C;
    font-size: 12px;
    line-height: 1.8;
}

.edit-heading-line {
    width: 42px;
    height: 3px;
    margin-bottom: 28px;
    background: #D9A441;
}

/* Form fields. */

.edit-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.edit-field {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.edit-field.full-width {
    grid-column: 1 / -1;
}

.edit-field label {
    color: #12372A;
    font-size: 12px;
    font-weight: 700;
}

.edit-field input,
.edit-field select {
    width: 100%;
    min-height: 47px;
    box-sizing: border-box;
    padding: 0 13px;
    border: 1px solid #DCE4DD;
    border-radius: 3px;
    background: #FFFFFF;
    color: #252525;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.edit-field input:focus,
.edit-field select:focus {
    border-color: #2E8B70;
    box-shadow: 0 0 0 3px rgba(46, 139, 112, 0.10);
}

.edit-field small {
    color: #69736C;
    font-size: 11px;
    line-height: 1.6;
}

/* Feedback messages. */

.edit-alert {
    margin-bottom: 22px;
    padding: 15px 18px;
    border-radius: 3px;
    font-size: 13px;
    line-height: 1.7;
}

.edit-alert.error {
    border-left: 4px solid #B94A48;
    background: #FCEDEA;
    color: #87352F;
}

/* Permissions note. */

.edit-permissions-note {
    margin-top: 28px;
    padding: 17px 20px;
    border-left: 3px solid #D9A441;
    background: #F7F6F0;
    color: #69736C;
    font-size: 12px;
    line-height: 1.8;
}

/* Form buttons. */

.edit-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
}

.edit-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 45px;
    padding: 0 23px;
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

.edit-button:hover {
    border-color: #2E8B70;
    background: #2E8B70;
    color: #FFFFFF;
}

.edit-button.secondary {
    border-color: #DCE4DD;
    background: #FFFFFF;
    color: #12372A;
}

.edit-button.secondary:hover {
    border-color: #12372A;
    background: #F7F6F0;
}

/* Responsive design. */

@media (max-width: 650px) {
    .edit-user-page {
        padding: 30px 15px 50px;
    }

    .edit-header {
        padding: 29px 23px;
    }

    .edit-form-panel {
        padding: 23px;
    }

    .edit-form-grid {
        grid-template-columns: 1fr;
    }

    .edit-field.full-width {
        grid-column: auto;
    }

    .edit-actions {
        flex-direction: column;
    }

    .edit-button {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<!-- =====================================================
     EDIT USER CONTENT
     ===================================================== -->

<main class="edit-user-page">

    <div class="edit-user-container">

        <a
            href="user_details.php?id=<?php echo (int)$userId; ?>"
            class="edit-back-link"
        >
            <span aria-hidden="true">&larr;</span>
            Back to User Details
        </a>

        <section class="edit-header">

            <p class="edit-eyebrow">
                Administrator / Edit Account
            </p>

            <h1>Edit User Information</h1>

            <p>
                Update the personal information and preferences
                associated with this Akwaaba 360 account.
            </p>

        </section>

        <section class="edit-form-panel">

            <h2><?php echo $escape($user['name']); ?></h2>

            <p class="edit-form-description">
                User ID: #<?php echo (int)$userId; ?>
                <br>
                Update the fields below and save your changes.
            </p>

            <div class="edit-heading-line"></div>

            <?php if ($errorMessage !== ''): ?>

                <div class="edit-alert error" role="alert">
                    <?php echo $escape($errorMessage); ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="edit_user.php?id=<?php echo (int)$userId; ?>"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo $escape($csrfToken); ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int)$userId; ?>"
                >

                <div class="edit-form-grid">

                    <!-- Full name. -->
                    <div class="edit-field full-width">

                        <label for="name">Full Name</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            value="<?php echo $escape($formData['name']); ?>"
                            required
                        >

                    </div>

                    <!-- Email address. -->
                    <div class="edit-field full-width">

                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            value="<?php echo $escape($formData['email']); ?>"
                            required
                        >

                    </div>

                    <!-- Country dropdown. -->
                    <div class="edit-field full-width">

                        <label for="country">Country</label>

                        <select id="country" name="country">

                            <option value="">
                                Select a country
                            </option>

                            <?php foreach ($countryOptions as $country): ?>

                                <option
                                    value="<?php echo $escape($country); ?>"
                                    <?php echo $formData['country'] === $country ? 'selected' : ''; ?>
                                >
                                    <?php echo $escape($country); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small>
                            Select the country associated with this user's account.
                        </small>

                    </div>

                    <!-- Preferred language. -->
                    <div class="edit-field">

                        <label for="preferred_language">
                            Preferred Language
                        </label>

                        <select
                            id="preferred_language"
                            name="preferred_language"
                        >

                            <option value="">
                                Not specified
                            </option>

                            <?php foreach ($languageOptions as $code => $language): ?>

                                <option
                                    value="<?php echo $escape($code); ?>"
                                    <?php echo $formData['preferred_language'] === $code ? 'selected' : ''; ?>
                                >
                                    <?php echo $escape($language); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Preferred currency. -->
                    <div class="edit-field">

                        <label for="preferred_currency">
                            Preferred Currency
                        </label>

                        <select
                            id="preferred_currency"
                            name="preferred_currency"
                        >

                            <option value="">
                                Use default currency (GHS)
                            </option>

                            <?php foreach ($currencyOptions as $code => $currency): ?>

                                <option
                                    value="<?php echo $escape($code); ?>"
                                    <?php echo $formData['preferred_currency'] === $code ? 'selected' : ''; ?>
                                >
                                    <?php echo $escape($currency); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small>
                            The default currency is GHS.
                        </small>

                    </div>

                </div>

                <div class="edit-permissions-note">
                    <strong>Access protection:</strong>
                    This form does not change the user's account role
                    or activation status. Those settings are managed
                    separately through User Management.
                </div>

                <div class="edit-actions">

                    <button type="submit" class="edit-button">
                        Save Changes
                    </button>

                    <a
                        href="user_details.php?id=<?php echo (int)$userId; ?>"
                        class="edit-button secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </div>

</main>

<?php
// Include the shared website footer.
require_once __DIR__ . '/../includes/footer.php';
?>
