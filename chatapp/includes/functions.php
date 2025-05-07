<?php
// Include Composer's autoloader
require_once __DIR__ . '/../../vendor/autoload.php';
function sanitize($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($userEmail, $token) {
    // Ensure config.php is loaded. If this functions.php is always included after config.php, this might not be strictly necessary here.
    // However, for robustness, especially if functions might be called in different contexts:
    if (!defined('SMTP_HOST')) {
        require_once __DIR__ . '/../config.php';
    }

    // Adjust the path to PHPMailer's autoload.php if you installed it via Composer in the project root
    // Or include the individual PHPMailer class files if installed manually.
    // This example assumes PHPMailer is in a 'vendor' directory at the project root.
    // If chatapp is the project root, then it would be '../vendor/autoload.php'
    // If FAST-NUCES-Chat is the project root, then it would be '../../vendor/autoload.php'
    // For now, let's assume a path relative to the `chatapp` directory.
    // IMPORTANT: You MUST ensure PHPMailer is installed and this path is correct.
    
    // Try to load Composer's autoloader if it exists
    $composerAutoloadPath = __DIR__ . '/../../vendor/autoload.php'; // Assuming 'vendor' is in FAST-NUCES-Chat
    if (file_exists($composerAutoloadPath)) {
        require_once $composerAutoloadPath;
    } else {
        // Fallback if Composer autoloader not found (manual include - less ideal)
        // You would need to download PHPMailer and place its src files appropriately
        // e.g., require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
        //       require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
        //       require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
        // For this example, we'll log an error if PHPMailer classes are not found.
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not found. Please install PHPMailer, preferably via Composer.");
            return false;
        }
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for troubleshooting
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE; // PHPMailer::ENCRYPTION_SMTPS or 'ssl'
        $mail->Port       = SMTP_PORT;

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        error_log("sendVerificationEmail: Attempting to send to userEmail: " . $userEmail); // DEBUG LINE
        
        // TEMPORARY: Echo the email and exit to see what's being passed.
        // echo "DEBUG sendVerificationEmail: Supposed to send to: " . htmlspecialchars($userEmail) . "<br>";
        // echo "Token: " . htmlspecialchars($token) . "<br>";
        // exit;
        // END TEMPORARY

        $mail->addAddress($userEmail);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address - ChatApp';
        // TODO: Create a proper domain/path for the verification link
        $verificationLink = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/chatapp/verify_email.php?token=" . $token;
        
        $mail->Body    = "Hi there,<br><br>Thanks for registering! Please click the link below to verify your email address:<br><a href='" . $verificationLink . "'>" . $verificationLink . "</a><br><br>If you did not register, please ignore this email.<br><br>Thanks,<br>The ChatApp Team";
        $mail->AltBody = "Hi there,\n\nThanks for registering! Please copy and paste the following link into your browser to verify your email address:\n" . $verificationLink . "\n\nIf you did not register, please ignore this email.\n\nThanks,\nThe ChatApp Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}"); // Log detailed error
        return false;
    }
}
?>