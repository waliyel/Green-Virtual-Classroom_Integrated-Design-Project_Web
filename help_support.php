<?php
session_start();
include_once('db_connect.php'); // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

$error_message = '';
$success_message = '';
$email = '';
$name = 'Unknown';
$username = 'Unknown';
$studentID = 'Unknown';
$profilePic = 'default_profile_pic.jpg'; // Default profile picture
$role = 'student'; // Default role

// Function to generate a 6-character alphanumeric Request ID
function generateRequestID($length = 6) {
    return 'REQ-' . strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
}

// Fetch the user ID passed from the page
if (isset($_GET['userID'])) {
    $user_id = mysqli_real_escape_string($mysqli, $_GET['userID']);

    // Fetch user details from the users table
    $query = "SELECT name, Username, StudentID, email, profilePicture, role FROM users WHERE userID='$user_id' LIMIT 1";
    $result = mysqli_query($mysqli, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'] ?? 'Unknown';
        $username = $row['Username'] ?? 'Unknown';
        $studentID = $row['StudentID'] ?? 'Unknown';
        $email = $row['email'];
        $profilePicture = $row['profilePicture'] ?? 'default_profile_pic.jpg';
        $role = $row['role'] ?? 'student';
    } else {
        $error_message = "User not found. Please contact support.";
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = generateRequestID(); // Generate a unique ID
    $complaint_text = mysqli_real_escape_string($mysqli, $_POST['complaint']);

    // Insert the unique ID and complaint text into the database
    $insert_query = "INSERT INTO complaints (request_id, complaint_text, email, userID) VALUES ('$request_id', '$complaint_text', '$email', '$user_id')";

    if (mysqli_query($mysqli, $insert_query)) {
        // Prepare the details for the email
        $user_details = "<br><b>Name:</b> $name";
        if ($username !== 'Unknown') $user_details .= "<br><b>Username:</b> $username";
        if ($studentID !== 'Unknown') $user_details .= "<br><b>StudentID:</b> $studentID";

        // Send the request ID and complaint via email using PHPMailer
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
            $mail->setFrom('no-reply@yourdomain.com', 'Support Team');
            $mail->addAddress('it.greenuniversitybd@gmail.com');  // IT support email
            $mail->addAddress($email); // User's email

            // Content for user's acknowledgment email
            $user_email_body = "Dear $name,<br><br>Your complaint has been registered with the following details:<br>
                                <b>Request ID:</b> $request_id<br>
                                $user_details<br>
                                <b>Complaint:</b> $complaint_text<br><br>
                                We will address your issue as soon as possible.<br><br>Best regards,<br>Support Team";

            // Prepare email body for IT support (Complaint ID first)
            $it_email_body = "<b>Complaint ID:</b> $request_id<br>
                              <b>Name:</b> $name<br>
                              $user_details<br>
                              <b>Complaint:</b> $complaint_text<br>
                              <br>This complaint was submitted by the user. Please address it as soon as possible.<br><br>Best regards,<br>Support Team";

            // Send separate emails
            // For IT support
            $mail->clearAddresses(); // Clear all previously added recipients
            $mail->addAddress('it.greenuniversitybd@gmail.com');
            $mail->isHTML(true);
            $mail->Subject = 'New Complaint Submission';
            $mail->Body = $it_email_body; // IT support email body
            $mail->send();

            // For the user
            $mail->clearAddresses(); // Clear all previously added recipients
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Complaint Submission Acknowledgment';
            $mail->Body = $user_email_body; // User acknowledgment email body
            $mail->send();

            $success_message = 'Your request has been submitted successfully. An acknowledgment email has been sent.';
            
            // Redirect to Home.php with necessary details
            echo "<script>
                alert('Your request has been submitted successfully! Request ID: $request_id');
                window.location.href = 'Home.php?userID=$user_id&role=$role&profilePicture=$profilePicture&name=$name&complaintID=$request_id';
            </script>";
            exit;

        } catch (Exception $e) {
            $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error_message = "Failed to submit your request. Please try again later.";
    }
}
?>