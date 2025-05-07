<![CDATA[<?php
require_once 'includes/db.php';
require_once 'includes/functions.php'; // For sendVerificationEmail and sanitize

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $force_verify = isset($_POST['force_verify']); // Check if the checkbox was checked

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, email, email_verified_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = "No account found with that email address.";
        } elseif ($user['email_verified_at'] !== null) {
            $errors[] = "This email address has already been verified. You can try logging in.";
        } else {
            // Email exists and is not verified. Decide whether to force verify or resend email.

            if ($force_verify) {
                // Force verification directly in the database
                $force_update_stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL, email_verification_token_expires_at = NULL WHERE id = ?");
                $force_update_stmt->bind_param("i", $user['id']);
                if ($force_update_stmt->execute()) {
                    $success_message = "Email address " . htmlspecialchars($user['email']) . " has been manually verified.";
                } else {
                    $errors[] = "Failed to force-verify the email address. Please try again.";
                    error_log("Failed to force-verify email: User ID " . $user['id'] . " - Error: " . $conn->error);
                }
                $force_update_stmt->close();
            } else {
                // Proceed to resend verification email
                $new_verification_token = bin2hex(random_bytes(32));
                $new_token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $update_stmt = $conn->prepare("UPDATE users SET email_verification_token = ?, email_verification_token_expires_at = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $new_verification_token, $new_token_expiry, $user['id']);

                if ($update_stmt->execute()) {
                    if (function_exists('sendVerificationEmail')) {
                        if (sendVerificationEmail($user['email'], $new_verification_token)) {
                            $success_message = "A new verification email has been sent to " . htmlspecialchars($user['email']) . ". Please check your inbox.";
                        } else {
                            // If email sending fails (e.g., limit exceeded), inform the user they might need to use force verify
                            $errors[] = "Failed to send a new verification email (possibly due to sending limits). Please try again later or use the 'Force Verify' option if available for testing.";
                        }
                    } else {
                        $errors[] = "Critical error: Email sending function not found. Please contact support.";
                    }
                } else {
                    $errors[] = "Failed to update verification token. Please try again.";
                    error_log("Failed to update token for resend: User ID " . $user['id'] . " - Error: " . $conn->error);
                }
                $update_stmt->close();
            }
        }
    }
} elseif (isset($_GET['email'])) {
    // Pre-fill email if coming from login page link
    $email = sanitize($_GET['email']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resend Verification Email - ChatApp</title>
    <link rel="stylesheet" href="assets/registerstyle.css"> <!-- Using same style as register/login -->
</head>
<body>
    <header><h2>Resend Verification Email</h2></header>

    <main class="container">

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)) : ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success_message)): // Only show form if no success message ?>
        <form method="POST" action="resend_verification.php" class="form-card">
            <p>If you didn't receive your verification email or it has expired, enter your email address below to send a new one.</p>
            <div class="form-group">
                <label for="email">Your Email Address:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            <div class="form-group">
                 <label for="force_verify">
                    <input type="checkbox" name="force_verify" id="force_verify" value="1">
                    Force Verify (Bypass Email - For Testing Only)
                 </label>
            </div>
            <button type="submit" class="btn-primary">Submit Request</button>
        </form>
        <?php endif; ?>
        <p class="text-center"><a href="login.php">Back to Login</a> | <a href="register.php">Register a new account</a></p>
    </main>
</body>
</html>]]>