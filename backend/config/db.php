<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'library_management_db');
define('BASE_URL', 'http://localhost/library-management/');

// API Keys & Endpoints
define('OPEN_LIBRARY_API', 'https://openlibrary.org/api/');
define('GOOGLE_BOOKS_API', 'YOUR_API_KEY_HERE'); // Optional backup

// Connection setup with error checking
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$conn) {
    // Return structured response if included in API context or terminate
    if (defined('API_CONTEXT')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database Connection Failed: ' . mysqli_connect_error()]);
        exit;
    }
    die("Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// Development error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
