<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $verification_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $insert = $conn->prepare("INSERT INTO users (name, email, password, email_verification_token, email_verification_token_expires_at) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $name, $email, $hashed, $verification_token, $token_expiry);
            
            if ($insert->execute()) {
                if (function_exists('sendVerificationEmail')) {
                    $emailSent = sendVerificationEmail($email, $verification_token);
                    if ($emailSent) {
                        header("Location: register.php?status=verification_sent&email_for_notice=" . urlencode($email));
                        exit;
                    } else {
                        $errors[] = "Registration successful, but we couldn't send a verification email. Please try registering again later or contact support if the problem persists.";
                    }
                } else {
                    $errors[] = "Critical error: Email sending function not found. Please contact support.";
                }
            } else {
                $errors[] = "Registration failed. Please try again. Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - ChatApp</title>
    <link rel="stylesheet" href="assets/registerstyle.css">
</head>
<body>
    <header><h2>Student Registration</h2></header>

    <main class="container">

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'verification_sent'): ?>
                <div class="alert alert-success">
                    ✅ Registration successful! A verification email has been sent to <strong><?php echo htmlspecialchars(urldecode($_GET['email_for_notice'] ?? 'your email')); ?></strong>. Please check your inbox (and spam folder) to complete your registration.
                </div>
            <?php elseif ($_GET['status'] == 'verified'): ?>
                <div class="alert alert-success">
                    ✅ Your email has been successfully verified! You can now <a href="login.php">log in</a>.
                </div>
            <?php elseif ($_GET['status'] == 'error' && isset($_GET['message'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="form-card">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>
        <p class="text-center">Already have an account? <a href="login.php">Login here</a>.</p>
    </main>
</body>
</html>
