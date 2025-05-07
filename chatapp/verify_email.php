<![CDATA[<?php
require_once 'includes/db.php';
require_once 'includes/functions.php'; // For sanitize if needed, though not directly used here.

$token = $_GET['token'] ?? null;

if (!$token) {
    header("Location: register.php?status=error&message=" . urlencode("Verification token not provided."));
    exit;
}

// Sanitize token just in case, though it should be a hex string
$token = sanitize($token);

$stmt = $conn->prepare("SELECT id, email, email_verified_at, email_verification_token_expires_at FROM users WHERE email_verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: register.php?status=error&message=" . urlencode("Invalid or expired verification link. Please try registering again or request a new link."));
    exit;
}

if ($user['email_verified_at'] !== null) {
    header("Location: register.php?status=error&message=" . urlencode("This email address has already been verified. You can try logging in."));
    exit;
}

$now = date('Y-m-d H:i:s');
// error_log("Email Verification Check for token: " . $token); // Temporarily disable for var_dump
// error_log("Current Server Time (\$now): " . $now);
// error_log("Token Expires At (from DB): " . $user['email_verification_token_expires_at']);
// error_log("User's email_verified_at (from DB): " . $user['email_verified_at']);

echo "<pre>Debug Info from verify_email.php:\n";
echo "Token from URL: "; var_dump($token);
echo "User data from DB: "; var_dump($user);
echo "Current Server Time (\$now): "; var_dump($now);
echo "Is token expired? (DB expiry < \$now): "; var_dump($user['email_verification_token_expires_at'] < $now);
echo "Is email_verified_at already set? "; var_dump($user['email_verified_at'] !== null);
echo "</pre>";
// exit; // UNCOMMENT THIS LINE AFTER PASTING THE OUTPUT, THEN REMOVE ALL THESE DEBUG LINES

if ($user['email_verification_token_expires_at'] < $now) {
    // error_log("Token determined to be EXPIRED for token: " . $token); // Temporarily disable for var_dump
    // Optionally, you could allow resending a new token here or guide the user.
    // For now, just an error.
    header("Location: register.php?status=error&message=" . urlencode("Verification link has expired. Please try registering again or request a new link."));
    exit;
}

// Token is valid, not expired, and email not yet verified. Proceed to verify.
$update_stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL, email_verification_token_expires_at = NULL WHERE id = ?");
$update_stmt->bind_param("i", $user['id']);

if ($update_stmt->execute()) {
    // Redirect to login page with a success message, or register page with a different success message
    header("Location: register.php?status=verified"); // This will show the "verified" message on register.php
    // Or, to redirect to login: header("Location: login.php?status=verified");
    exit;
} else {
    // Log this error server-side
    error_log("Failed to update user verification status for user ID: " . $user['id'] . " - Error: " . $conn->error);
    header("Location: register.php?status=error&message=" . urlencode("An unexpected error occurred while verifying your email. Please try again or contact support."));
    exit;
}

$update_stmt->close();
$conn->close();
?>]]>