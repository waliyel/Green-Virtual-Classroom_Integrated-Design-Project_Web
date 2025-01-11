<?php
session_start();
include_once('db_connect.php'); // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Make sure PHPMailer is installed via Composer

$error_message = '';
$success_message = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // User's email
    
    // Sanitize email input
    $email = mysqli_real_escape_string($mysqli, $email);

    // Fetch userID using the email
    $query = "SELECT userID FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($mysqli, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $userID = $row['userID']; // Extract userID

        // Generate a unique token
        $token = mt_rand(100000, 999999); // Generates a 6-digit token

        // Step 2: Hash the token using bcrypt
        $hashedToken = password_hash($token, PASSWORD_BCRYPT);

        // Step 3: Set the token expiration time (e.g., 1 hour from now)
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Step 4: Save the hashed token and expiration time in the database
        $updateToken = "UPDATE users SET resetToken='$hashedToken', resetTokenExpiry='$token_expiry' WHERE userID='$userID'";

        if (mysqli_query($mysqli, $updateToken)) {
            // Send the reset token via email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'waliyelhasnatzaman@gmail.com';  // Your Gmail email address
                $mail->Password = 'dswgwrjpxzzgtetk';  // Your Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('no-reply@yourdomain.com', 'Your Team');
                $mail->addAddress($email);  // Add the user's email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Dear user,<br><br>Here is your password reset token: <b>$token</b><br><br>Please use this token to reset your password in Green VIrtual Classroom (GVC).<br><br>Best regards,<br>GVC Team";

                // Send the email
                $mail->send();
                $success_message = 'Password reset token has been sent to your email.';
                
                // Redirect to the reset password page after successful token sending
                
                $_SESSION['success_message'] = 'Password reset token has been sent to your email.';
                $_SESSION['userID'] = $userID; // Store userID in session
                header("refresh:0;url=reset_password.php?userID=" . $userID);
                exit;

            } catch (Exception $e) {
                $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error_message = "Failed to save reset token.";
        }
    } else {
        $error_message = "Email address not found. Please check and try again.";
    }
}
?>



<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="./assets/img/favicon.png">
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/uf-style.css">
    <title>Forget Password</title>
</head>
<body>
    <div class="uf-form-signin">
        <div class="text-center">
            <a href="https://www.green.edu.bd/" target="_blank">
                <img src="./assets/img/GUB.png" alt="" width="120" height="120">
            </a>
            <h1 class="text-deep-blue h3">Forgot Your Password?</h1>
        </div>

        <!-- Error or success message -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Form to request password reset token -->
        <form method="POST" action="forget_password.php">
            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-envelope"></span>
                <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg">Send Reset Token</button>
            </div>

            <div class="d-flex justify-content-center">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>

    <script src="./assets/js/popper.min.js"></script>
    <script src="./assets/js/bootstrap.min.js"></script>
</body>
</html>
