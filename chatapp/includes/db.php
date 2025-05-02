<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    // <<< ADDED LOGGING >>>
    error_log("DEBUG: Loaded credentials from config.php: HOST=" . DB_HOST . ", USER=" . DB_USER . ", DB=" . DB_NAME);
    // <<< END LOGGING >>>
} else {
    // Fallback or error handling if config.php is missing
    // For now, we'll use default values, but ideally, this should stop execution or use example values.
    // This part should be improved for production readiness.
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'chat_university_structure');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    // <<< MODIFIED LOGGING >>>
    error_log("ERROR: config.php not found at " . __DIR__ . "/../config.php. Falling back to default credentials.");
    // <<< END LOGGING >>>
}

try {
    // <<< ADDED LOGGING >>>
    error_log("DEBUG: Attempting DB connection with HOST=" . DB_HOST . ", USER=" . DB_USER . ", DB=" . DB_NAME);
    // <<< END LOGGING >>>
    // Use defined constants for connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        // Provide more context in the error message if possible
        throw new Exception("Connection failed (" . DB_HOST . "/" . DB_NAME . "): " . $conn->connect_error);
    }

    // Set charset to ensure proper encoding
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}
?>
