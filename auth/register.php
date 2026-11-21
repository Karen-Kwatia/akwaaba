
<?php

// Start the session before creating or accessing session variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the reusable database connection.
require_once __DIR__ . '/../includes/db.php';

// Initialise an empty message for registration feedback.
$message = '';

// Process the form when it is submitted using POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and clean the submitted form values.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $language = $_POST['preferred_language'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check that all required fields have been completed.
    if (
        $name === '' ||
        $country === '' ||
        $language === ''
    ) {
        $message = 'Please complete all required fields.';

    // Check that the two passwords match.
    } elseif ($password !== $confirm_password) {
        $message = 'Your passwords do not match.';

    // Validate the email address.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';

    // Check that the password is at least 8 characters long.
    } elseif (strlen($password) < 8) {
        $message = 'Your password must contain at least 8 characters.';

    } else {

        // Check that the selected language is supported.
        $allowedLanguages = ['en', 'tw', 'fr', 'es'];

        if (!in_array($language, $allowedLanguages, true)) {
            $message = 'Please select a valid language.';

        } else {

            // Check whether the email already exists in the database.
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            // Execute the query using the submitted email.
            $checkEmail->execute([$email]);

            // Check whether an existing account was found.
            if ($checkEmail->fetch()) {
                $message = 'An account with this email already exists.';

            } else {

                // Securely hash the user's password.
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Prepare the SQL statement to create a tourist account.
                $insertUser = $pdo->prepare(
                    "INSERT INTO users
                    (name, email, password_hash, role, country, preferred_language)
                    VALUES (?, ?, ?, 'tourist', ?, ?)"
                );

                // Insert the new user's information into the database.
                $insertUser->execute([
                    $name,
                    $email,
                    $passwordHash,
                    $country,
                    $language
                ]);

                // Retrieve the ID assigned to the newly registered user.
                $newUserId = $pdo->lastInsertId();

                // Generate a new session ID for security.
                session_regenerate_id(true);

                // Automatically log the new user into Akwaaba 360.
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'tourist';

                // Redirect the newly registered user to preferences.
                header('Location: preferences.php');

                // Stop executing the registration script.
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set the character encoding to support international text. -->
    <meta charset="UTF-8">

    <!-- Make the page responsive on different screen sizes. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title. -->
    <title>Create Account | Akwaaba 360</title>

    <!-- Load the shared Akwaaba 360 stylesheet. -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Display the registration page heading. -->
    <h1>Create Your Akwaaba 360 Account</h1>

    <!-- Display registration feedback safely. -->
    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Create the registration form. -->
    <form method="POST" action="">

        <!-- Collect the user's full name. -->
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" required>

        <br><br>

        <!-- Collect the user's email address. -->
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required>

        <br><br>

        <!-- Collect the user's country. -->
        <label for="country">Country:</label>
        <input type="text" id="country" name="country" required>

        <br><br>

        <!-- Allow the user to choose a preferred language. -->
        <label for="preferred_language">Preferred Language:</label>
        <select id="preferred_language" name="preferred_language" required>
            <option value="en">English</option>
            <option value="tw">Twi</option>
            <option value="fr">French</option>
            <option value="es">Spanish</option>
        </select>

        <br><br>

        <!-- Collect the user's password. -->
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <br><br>

        <!-- Ask the user to confirm their password. -->
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <br><br>

        <!-- Submit the registration form. -->
        <button type="submit" class="btn btn-primary">Create Account</button>

    </form>

</body>
</html>
