
<?php
// Store the database server address.
$host = "localhost";

// Store the database username used by XAMPP.
$username = "root";

// Store the database password.
// XAMPP commonly uses an empty password by default.
$password = "";

// Store the name of the Akwaaba 360 database.
$database = "akwaaba360";

// Create a connection to the MySQL database.
$conn = new mysqli($host, $username, $password, $database);

// Check whether the database connection failed.
if ($conn->connect_error) {
    // Stop execution and display the connection error.
    die("Database connection failed: " . $conn->connect_error);
}

// Set the character encoding to support multilingual text.
$conn->set_charset("utf8mb4");
?>
