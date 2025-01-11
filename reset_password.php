<?php
session_start();
include_once('db_connect.php'); // Include the database connection

$error_message = ''; // Error message
$success_message = ''; // Success message

// Display success message if it exists in the session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the session message
}

// Retrieve the userID from the URL
$userID = isset($_GET['userID']) ? $_GET['userID'] : '';

if (empty($userID)) {
    $error_message = 'Invalid user ID.';
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === "verify_token") {
            $input_token = $_POST['token'];

            // Retrieve the hashed token and expiry time from the database
            $sql = "SELECT resetToken, resetTokenExpiry FROM users WHERE userID=?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $hashed_token = $user['resetToken'];
                $expiry_time = $user['resetTokenExpiry'];

                // Verify the token and check expiry
                if (password_verify($input_token, $hashed_token) && strtotime($expiry_time) > time()) {
                    echo json_encode(['success' => true, 'message' => "Token verified. Please reset your password."]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid token or token has expired.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }
        }

        if ($action === "reset_password") {
            $new_password = $_POST['new_password'];
            $new_password_confirm = $_POST['new_password_confirm'];

            if ($new_password == $new_password_confirm) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Update the password in the database
                $update_sql = "UPDATE users SET password=?, resetToken=NULL, resetTokenExpiry=NULL WHERE userID=?";
                $stmt = $mysqli->prepare($update_sql);
                $stmt->bind_param("ss", $hashed_password, $userID);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Your password has been successfully reset. <a href="login.php" class="btn btn-primary">Go to Login</a>']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'There was an error updating your password. Please try again.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
                exit;
            }
        }
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
    <title>Reset Password</title>
</head>
<body>
    <div class="uf-form-signin">
        <div class="text-center">
            <a href="login.php">
                <img src="./assets/img/GUB.png" alt="" width="120" height="120">
            </a>
            <h1 class="text-deep-blue h3">Reset Password</h1>
        </div>

        <!-- Display success or error message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div id="message-container"></div>

        <!-- Token Verification Form -->
        <form id="token-form">
            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-lock"></span>
                <input type="text" class="form-control" name="token" placeholder="Enter token" required>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg">Verify Token</button>
            </div>
        </form>

        <!-- Password Reset Form -->
        <form id="password-form" style="display: none;">
            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-lock"></span>
                <input type="password" class="form-control" name="new_password" placeholder="Enter new password" required>
            </div>

            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-lock"></span>
                <input type="password" class="form-control" name="new_password_confirm" placeholder="Confirm new password" required>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg">Reset Password</button>
            </div>
        </form>
    </div>

    <script src="./assets/js/popper.min.js"></script>
    <script src="./assets/js/bootstrap.min.js"></script>
    <script>
        const tokenForm = document.getElementById('token-form');
        const passwordForm = document.getElementById('password-form');
        const messageContainer = document.getElementById('message-container');

        tokenForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(tokenForm);
            formData.append('action', 'verify_token');

            // Clear any previous messages before displaying the new one
            messageContainer.innerHTML = '';

            fetch('reset_password.php?userID=<?php echo $userID; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">${data.message}</div>`;
                
                if (data.success) {
                    // Hide the token form and show the password reset form
                    tokenForm.style.display = 'none';
                    passwordForm.style.display = 'block';
                }
            });
        });

        passwordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(passwordForm);
            formData.append('action', 'reset_password');

            fetch('reset_password.php?userID=<?php echo $userID; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">${data.message}</div>`;
            });
        });
    </script>
</body>
</html>
