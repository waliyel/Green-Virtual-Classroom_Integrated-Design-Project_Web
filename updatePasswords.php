<?php
// Include your DB connection
include_once('db_connect.php');

// Query to fetch all users
$sql = "SELECT userID, username, password FROM users";
$result = mysqli_query($mysqli, $sql);

// Check if the query was successful
if ($result) {
    while ($user = mysqli_fetch_assoc($result)) {
        // Hash the password using bcrypt
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);

        // Update password in the database
        $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE userID = {$user['userID']}";

        // Execute the query to update the password
        if (mysqli_query($mysqli, $updateSql)) {
            echo "Password for user {$user['username']} updated successfully.\n";
        } else {
            echo "Error updating password for user {$user['username']}: " . mysqli_error($mysqli) . "\n";
        }
    }
} else {
    echo "Error fetching users: " . mysqli_error($mysqli) . "\n";
}
?>
