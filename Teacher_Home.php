<?php
// Get the attributes passed via URL
$name = isset($_GET['name']) ? $_GET['name'] : 'Unknown';
$userID = isset($_GET['userID']) ? $_GET['userID'] : 'Unknown';
$role = isset($_GET['role']) ? $_GET['role'] : 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Home</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
    <p>Your UserID is: <?php echo htmlspecialchars($userID); ?></p>
    <p>Your Role is: <?php echo htmlspecialchars($role); ?></p>

    <p>This is the Teacher Home Page.</p>
</body>
</html>
