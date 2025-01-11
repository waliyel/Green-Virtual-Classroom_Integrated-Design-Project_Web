<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "green_virtual_classroom");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch values from URL parameters
$courseID = isset($_GET['courseID']) ? $_GET['courseID'] : '';
$courseCode = isset($_GET['courseCode']) ? $_GET['courseCode'] : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : 'Unknown';
$userID = isset($_GET['userID']) ? $_GET['userID'] : 'Unknown';
$profilePic = isset($_GET['profilePicture']) ? $_GET['profilePicture'] : 'default_profile_pic.jpg';
$role = isset($_GET['role']) ? $_GET['role'] : 'student';


// Fetch teacher's name based on course
$teacherName = '';

if (!empty($courseID)) {
    $query = "SELECT Users.name 
            FROM Courses 
            INNER JOIN Users ON Courses.teacherID = Users.userID 
            WHERE Courses.courseID = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $courseID);
        $stmt->execute();
        $stmt->bind_result($teacherName);
        if (!$stmt->fetch()) {
            $teacherName = 'Unknown';
        }
        $stmt->close();
    } else {
        $teacherName = 'Unknown';
    }
}


if (!$courseID) {
    die("Course ID not specified.");
}

// Fetch teacher details for the course
$teacherQuery = "
    SELECT u.name, u.email, u.role, u.userID, u.profilePicture
    FROM Users u
    JOIN Courses c ON u.userID = c.teacherID
    WHERE c.courseID = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $courseID);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teachers = $teacherResult->fetch_all(MYSQLI_ASSOC);

// Fetch enrolled students for the course
$studentsQuery = "
    SELECT u.name, u.email, u.role, u.userID, u.profilePicture
    FROM Users u
    JOIN Enrollments e ON u.userID = e.studentID
    WHERE e.courseID = ?";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("i", $courseID);
$stmt->execute();
$studentsResult = $stmt->get_result();
$students = $studentsResult->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT noticeID, title, contentType, content, publishedAt FROM Notices ORDER BY publishedAt DESC";
$result = $conn->query($sql);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Course Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <style>
    /* Custom CSS */
    #notification {
        text-align: center;
        /* Center the text horizontally */
        margin-bottom: 10px;
        /* Optional: Add some space below the title */
    }

    #profilePic {
        border: 2px solid #007bff;
    }

    #sidebar {
        margin-top: 70px;
        width: 250px;
        padding-top: 20px;
    }

    .content-wrapper {
        margin-top: 80px;
        margin-left: 20px;
    }

    .nav-tabs {
        margin-top: 20px;
        margin-right: 5px;
        margin-left: 10px;
    }

    .card {
        margin-bottom: 15px;
    }

    .post-form {

        top: 20px;

        background: white;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    .notifications {
        margin-top: 80px;
        margin-right: 40px;
        margin-left: 10px;
        width: 300px;
        min-width: 260px;
        height: 500px;

    }

    .hamburger-container {
        display: flex;
        align-items: center;
        padding-left: 10px;
        padding-right: 20px;
        max-width: 500px;
    }

    #stream {
        margin-top: 20px;
        /* Adjust this value based on .post-form height */
        margin-left: 30px;
        margin-right: 80px;
        padding-top: 20px;
        padding-left: 20px;
        padding-right: 20px;
        padding-bottom: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        min-width: calc(100% - 40px);
        width: auto;
        min-height: calc(100% - 30px);
        height: auto;
    }

    /* #pc {
        margin-left: 30px;
        margin-right: 80px;
        padding-top: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        min-width: calc(100% - 40px);
        width: auto;
        min-height: calc(100% - 30px);
        height: auto;
    } */

    .navbar {
        padding: 5px;
        height: 70px;
    }

    .notices-container {
        display: flex;
        gap: 20px;
        margin: 20px;
        /* Space between notices */
        flex-wrap: wrap;
        /* Allows wrapping to the next row */
        justify-content: flex-start;
        /* Align items to the start of each row */
    }


    .notice-card {

        width: 308px;
        height: auto;
        /* Let the card adjust based on its content */
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        text-align: left;
        /* Align content to the left */
        cursor: pointer;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out;
    }

    .notice-card:hover {
        transform: scale(1.05);
    }

    .notice-preview-container {
        width: 100%;
        height: 100px;
        /* Fixed height for the preview section */
        display: flex;
        justify-content: center;
        align-items: center;
        border-bottom: 1px solid #ddd;
        overflow: hidden;
        /* Crop overflowing content */
    }

    .notice-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* Ensure the image fills the container proportionally */
        border-radius: 0;
    }

    .notice-details {
        padding: 10px;
    }

    .notice-title {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        margin: 0;
        overflow: hidden;
        /* Crop overflowing text */
        white-space: wrap;
        text-overflow: ellipsis;
        /* Add ellipsis for cropped text */
    }


    .notice-meta {
        display: flex;
        flex-wrap: wrap;
        font-size: 14px;
        color: #777;
        margin-top: 15px;
        margin-bottom: 5px;

    }

    .notice-type {
        white-space: nowrap;
        /* Prevent text from wrapping */
    }

    .notice-date {
        width: 100%;
        /* Makes the date section take up a full row */
        margin-top: 5px;
        /* Optional: Add some spacing between type and date */
    }

    .notice-meta .notice-type,
    .notice-meta .notice-date {
        font-weight: normal;
    }


    /* Notice Popup Styles */
    /* Style for the background blur */
    .body-blurred {
        filter: blur(8px);
        pointer-events: none;
        /* Prevent interaction with blurred content */
    }

    /* Notice Popup Styles */
    /* Style for the background blur */
    .body-blurred {
        filter: blur(8px);
        pointer-events: none;
        /* Prevent interaction with blurred content */
    }

    /* Make sure the popup stays on top */
    /* Popup styles */
    .notice-popup {
        display: none;
        /* Hidden by default */
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10000;
        /* Ensure it's above all other content */
        background-color: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        overflow-y: auto;
        /* Makes content inside scrollable */
        height: 70%;
        width: 60%;
        max-width: 600px;
        padding: 16px;
        /* Adds space inside the popup */
    }

    .notice-pdf-viewer {
        width: 80%;
        /* Make it take the full width of its container */
        height: 500px;
        /* Set a larger height, adjust as needed */
        margin: 15px;
        border: none;
        /* Remove border if not needed */
    }

    .notice-popup.open {
        display: block;
        /* Shows the popup */
    }

    .popup-content {
        text-align: center;
    }

    /* Blur effect for the background */
    .body-blurred {
        filter: blur(8px);
    }

    /* Dim other notices to focus on the modal */
    .notice-card.disabled {
        opacity: 0.5;
    }

    /* Close button styling */
    .close-popup {
        position: absolute;
        /* Makes it stay at a fixed location within the popup */
        top: 10px;
        /* Distance from the top of the popup */
        right: 10px;
        /* Distance from the right of the popup */
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .close-popup:hover {
        background-color: #0056b3;
    }

    .pdf-icon {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 120px;
        height: 80px;
    }
    </style>
</head>

<body>
    <div class="body-contents">
        <nav class="navbar navbar-expand-lg fixed-top" style="background-color: #cce7ca;">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <!-- Left Content: Hamburger and Home Button -->
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false"
                        aria-label="Toggle sidebar">
                        ☰
                    </button>
                    <a href="home.php?name=<?php echo urlencode($name); ?>&userID=<?php echo urlencode($userID); ?>&profilePicture=<?php echo urlencode($profilePic); ?>&role=<?php echo urlencode($role); ?>"
                        class="btn btn-outline-secondary ms-3">Home</a>
                </div>

                <!-- Course Info Card -->
                <div class="card ms-3" style="width: 300px; height: 40px; margin-top: 15px;">
                    <div class="card-body" style="text-align: center; font-size: 12px; padding: 2px;">
                        <p class="card-text">
                            <strong>Course: <?php echo htmlspecialchars($courseCode); ?> -
                                <?php echo htmlspecialchars($section); ?></strong><br>
                            Teacher: <?php echo htmlspecialchars($teacherName); ?>
                        </p>
                    </div>
                </div>
                <!-- Centered Text: Green Virtual Classroom -->
                <span class="navbar-text mx-auto"
                    style="text-align: left; flex-grow: 1; padding-left: 150px; font-size: 26px;">Green Virtual
                    Classroom</span>

                <!-- Right Content: Profile Dropdown -->
                <div class="dropdown">
                    <img src="assets/profilePics/<?php echo $profilePic; ?>" alt="Profile" class="rounded-circle"
                        width="40" height="40" id="profilePic" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Logged in as: <?php echo htmlspecialchars($name); ?></a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="login.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>


        <div class="d-flex">
            <div class="bg-light p-3 collapse" id="sidebar">
                <div class="list-group">
                    <a href="#" class="list-group-item">Teaching</a>
                    <a href="#" class="list-group-item">Enrolled</a>
                    <a href="#" class="list-group-item">Archived Classes</a>
                    <a href="#" class="list-group-item">Settings</a>
                </div>
            </div>

            <div class="container-fluid content-wrapper">
                <!-- Tabs for Stream, Material, People, etc. -->
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" href="Course.php?courseID=<?php echo urlencode($courseID); ?>
                        &courseCode=<?php echo urlencode($courseCode); ?>
                        &section=<?php echo urlencode($section); ?>
                        &name=<?php echo urlencode($name); ?>
                        &userID=<?php echo urlencode($userID); ?>
                        &profilePicture=<?php echo urlencode($profilePic); ?>
                        &role=<?php echo urlencode($role); ?>">Stream</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Materials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="People.php?courseID=<?php echo urlencode($courseID); ?>
                        &courseCode=<?php echo urlencode($courseCode); ?>
                        &section=<?php echo urlencode($section); ?>
                        &name=<?php echo urlencode($name); ?>
                        &userID=<?php echo urlencode($userID); ?>
                        &profilePicture=<?php echo urlencode($profilePic); ?>
                        &role=<?php echo urlencode($role); ?>">People</a>
                    <li class="nav-item">
                        <a class="nav-link" href="Notice.php?courseID=<?php echo urlencode($courseID); ?>
                        &courseCode=<?php echo urlencode($courseCode); ?>
                        &section=<?php echo urlencode($section); ?>
                        &name=<?php echo urlencode($name); ?>
                        &userID=<?php echo urlencode($userID); ?>
                        &profilePicture=<?php echo urlencode($profilePic); ?>
                        &role=<?php echo urlencode($role); ?>">Notices</a>
                    </li>
                    <li class="nav-item ms-auto">
                        <a class="nav-link active" href="#">Discussions</a>
                    </li>
                </ul>

                <div class="notices-container">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="notice-card" onclick="openNoticePopup(<?php echo $row['noticeID']; ?>)">
                        <!-- Notice Content -->
                        <div class="notice-preview-container">
                            <?php if ($row['contentType'] == 'pdf'): ?>
                            <div class="notice-preview">
                                <img src="assets\Icons\pdf-icon-on-transparent-background-free-png.webp" alt="PDF Icon"
                                    class="pdf-icon">
                            </div>
                            <?php else: ?>
                            <img src="data:image/png;base64,<?php echo base64_encode($row['content']); ?>"
                                alt="Notice Preview" class="notice-preview-image">
                            <?php endif; ?>
                        </div>
                        <div class="notice-details">
                            <div class="notice-title">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </div>
                            <div class="notice-meta">
                                <div class="notice-type">Type: <?php echo ucfirst($row['contentType']); ?></div><br>
                                <div class="notice-date">Published:
                                    <?php echo date('F d, Y h:i A', strtotime($row['publishedAt'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Popup Modals Outside -->
    <?php 
// Reset MySQL data pointer to regenerate rows
$result->data_seek(0); 
while ($row = $result->fetch_assoc()): ?>
    <div id="notice-popup-<?php echo $row['noticeID']; ?>" class="notice-popup">
        <div class="popup-content">
            <?php if ($row['contentType'] == 'pdf'): ?>
            <iframe src="data:application/pdf;base64,<?php echo base64_encode($row['content']); ?>"
                class="notice-pdf-viewer"></iframe>
            <?php else: ?>
            <img src="data:image/png;base64,<?php echo base64_encode($row['content']); ?>" alt="Notice Image"
                class="notice-full-image">
            <?php endif; ?>
            <button class="close-popup" onclick="closeNoticePopup(<?php echo $row['noticeID']; ?>)">Close</button>
        </div>
    </div>
    <?php endwhile; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Function to open the popup
    function openNoticePopup(noticeID) {
        // Prevent opening a new popup if one is already open
        var existingPopup = document.querySelector('.notice-popup.open');
        if (existingPopup) {
            return; // If a popup is already open, do nothing
        }

        // Get the popup element by its ID
        var popup = document.getElementById("notice-popup-" + noticeID);
        if (!popup) {
            console.error("Popup element not found for noticeID:", noticeID);
            return;
        }

        // Display the popup
        popup.style.display = "block";
        popup.classList.add("open");

        // Add blur effect to the entire body to dim the background
        var noticesContainer = document.querySelector('.body-contents');
        noticesContainer.classList.add("body-blurred");
        // Disable interaction with other notices
        document.querySelectorAll('.notice-card').forEach(function(card) {
            card.classList.add('disabled'); // Dim other notices
            card.style.pointerEvents = 'none'; // Disable interaction with other notices
        });

        // Fix the popup in the center of the viewport
        popup.style.position = "fixed";
        popup.style.top = "50%";
        popup.style.left = "50%";
        popup.style.transform = "translate(-50%, -50%)";
    }

    function closeNoticePopup(noticeID) {
        // Get the popup element by its ID
        var popup = document.getElementById("notice-popup-" + noticeID);
        if (!popup) {
            console.error("Popup element not found for noticeID:", noticeID);
            return;
        }

        // Hide the popup
        popup.style.display = "none";
        popup.classList.remove("open");

        // Remove blur effect from the entire body
        var noticesContainer = document.querySelector('.body-contents');
        noticesContainer.classList.remove("body-blurred"); // Fix: Remove the blur effect
        // Re-enable interaction with other notices
        document.querySelectorAll('.notice-card').forEach(function(card) {
            card.classList.remove('disabled'); // Restore visibility for other notices
            card.style.pointerEvents = 'auto'; // Re-enable interaction with other notices
        });
    }
    </script>

</body>

</html>

<?php
$conn->close();
?>