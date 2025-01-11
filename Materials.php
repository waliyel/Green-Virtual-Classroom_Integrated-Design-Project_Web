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
    $query = "SELECT Users.name FROM Courses INNER JOIN Users ON Courses.teacherID = Users.userID WHERE Courses.courseID = ?";
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
$teacherQuery = "SELECT u.name, u.email, u.role, u.userID, u.profilePicture FROM Users u JOIN Courses c ON u.userID = c.teacherID WHERE c.courseID = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("s", $courseID);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teachers = $teacherResult->fetch_all(MYSQLI_ASSOC);

// Fetch enrolled students for the course
$studentsQuery = "SELECT u.name, u.email, u.role, u.userID, u.profilePicture FROM Users u JOIN Enrollments e ON u.userID = e.studentID WHERE e.courseID = ?";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("s", $courseID);
$stmt->execute();
$studentsResult = $stmt->get_result();
$students = $studentsResult->fetch_all(MYSQLI_ASSOC);












//Discussion Functionality

// Handle form submission (message and/or file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $courseID = isset($_POST['courseID']) ? $_POST['courseID'] : '';
    $userID = isset($_POST['userID']) ? $_POST['userID'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';

    // Validate inputs
    if (empty($courseID) || empty($userID) || (empty($content) && !isset($_FILES['file']))) {
        echo 'Invalid input. Message content or file is required.';
        exit();
    }

    // Retrieve or create discussion
    $discussionQuery = $conn->prepare("SELECT discussionID FROM Discussions WHERE courseID = ?");
    $discussionQuery->bind_param("s", $courseID);
    $discussionQuery->execute();
    $discussionResult = $discussionQuery->get_result();
    $discussionRow = $discussionResult->fetch_assoc();

    // Create discussion if it doesn't exist
    if (!$discussionRow) {
        $insertDiscussion = $conn->prepare("INSERT INTO Discussions (courseID) VALUES (?)");
        $insertDiscussion->bind_param("s", $courseID);
        $insertDiscussion->execute();
        $discussionID = $conn->insert_id;
    } else {
        $discussionID = $discussionRow['discussionID'];
    }

    // Handle file upload (if any)
    $fileName = null;
    $fileContent = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['file']['name'];
        $fileContent = file_get_contents($_FILES['file']['tmp_name']);
    }

    // Insert the message into the Messages table
    $timestamp = date('Y-m-d H:i:s');
    $insertMessage = $conn->prepare(
        "INSERT INTO Messages (discussionID, authorID, content, fileContent, fileName, timestamp) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $insertMessage->bind_param("sissss", $discussionID, $userID, $content, $fileContent, $fileName, $timestamp);
    $insertMessage->execute();
}


if (isset($_GET['action']) && $_GET['action'] == 'download_file') {
    if (isset($_GET['id']) && isset($_GET['courseID'])) {
        $messageID = $_GET['id'];
        $courseID = $_GET['courseID'];
    
        // Fetch the file content and file name from the database based on the message ID
        $fileQuery = $conn->prepare("
        SELECT Messages.fileName, Messages.fileContent 
        FROM Messages 
        INNER JOIN Discussions ON Messages.discussionID = Discussions.discussionID 
        WHERE Messages.messageID = ? AND Discussions.courseID = ?");
    $fileQuery->bind_param("is", $messageID, $courseID);
            $fileQuery->bind_param("is", $messageID, $courseID);
        $fileQuery->execute();
        $fileResult = $fileQuery->get_result();
        $file = $fileResult->fetch_assoc();
    
        if ($file) {
            // Set the necessary headers to initiate a file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file['fileName']) . '"');
            header('Content-Length: ' . strlen($file['fileContent']));
    
            echo $file['fileContent'];
            exit();
        } else {
            echo "File not found!";
            exit();
        }
    }
}


// Fetch all messages for the course discussion
$messagesQuery = $conn->prepare(
    "SELECT Messages.messageID, Messages.content, Messages.fileName, Messages.timestamp, Users.name, Users.profilePicture " .
    "FROM Messages " .
    "INNER JOIN Users ON Messages.authorID = Users.userID " .
    "WHERE Messages.discussionID = ? " .
    "ORDER BY Messages.timestamp ASC"
);
$messagesQuery->bind_param("s", $discussionID);
$messagesQuery->execute();
$messagesResult = $messagesQuery->get_result();
$messages = [];
while ($row = $messagesResult->fetch_assoc()) {
    $messages[] = $row;
}

// Function to fetch assignments
function fetchAssignments($courseID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM Assignments WHERE courseID = ?");
    $stmt->bind_param("i", $courseID);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch assignment files
function fetchAssignmentFiles($assignmentID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM AssignmentFiles WHERE assignmentID = ?");
    $stmt->bind_param("i", $assignmentID);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch materials
function fetchMaterials($courseID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM Materials WHERE courseID = ?");
    $stmt->bind_param("i", $courseID);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch comments for a specific assignment
function fetchComments($assignmentID) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT c.*, u.name AS userName 
        FROM privateComments c 
        JOIN Users u ON c.userID = u.userID 
        WHERE c.assignmentID = ? 
        ORDER BY c.commentDate ASC"
    );
    $stmt->bind_param("i", $assignmentID);
    $stmt->execute();
    return $stmt->get_result();
}


// Function to check submission deadline
function isBeforeDeadline($assignmentID) {
    global $conn;
    $stmt = $conn->prepare("SELECT dueDate FROM Assignments WHERE assignmentID = ?");
    $stmt->bind_param("i", $assignmentID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $currentTime = new DateTime();
    $dueTime = new DateTime($result['dueDate']);
    return $currentTime <= $dueTime;
}

// Handle Assignment Upload (Teacher Only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['createAssignment']) && $role == 'Teacher') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['dueDate'];

    // Insert assignment into the database
    $stmt = $conn->prepare("INSERT INTO Assignments (title, description, dueDate, courseID, createdBy) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $title, $description, $dueDate, $courseID, $userID);
    $stmt->execute();
    $assignmentID = $stmt->insert_id;  // Get the inserted assignment ID

    // Handle file uploads
    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = $_FILES['files']['name'][$key];
        $fileTmpName = $_FILES['files']['tmp_name'][$key];

        // Check if the file exists before processing
        if (!empty($fileTmpName) && file_exists($fileTmpName)) {
            $fileContent = file_get_contents($fileTmpName);

            // Insert file data into the AssignmentFiles table
            $stmt = $conn->prepare("INSERT INTO AssignmentFiles (assignmentID, fileName, fileContent) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $assignmentID, $fileName, $fileContent);
            $stmt->execute();
        }
    }

    echo "<script>alert('Assignment created successfully!');</script>";
}



// Upload Materials (Teacher Only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['uploadMaterial']) && $role == 'Teacher') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO Materials (title, description, courseID, uploadedBy) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $title, $description, $courseID, $userID);
    $stmt->execute();
    $materialID = $stmt->insert_id;

    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = $_FILES['files']['name'][$key];
        $fileContent = file_get_contents($tmpName);

        $stmt = $conn->prepare("INSERT INTO MaterialFiles (materialID, fileName, fileContent) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $materialID, $fileName, $fileContent);
        $stmt->execute();
    }
}


// Delete Material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material']) && $role === 'Teacher') {
    $material_id = $_POST['material_id'];

    $query = "DELETE FROM materials WHERE materialID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $material_id);

    if ($stmt->execute()) {
        echo "<script type='text/javascript'>alert('Material deleted successfully.');</script>";
    } else {
        echo "<script type='text/javascript'>alert('Error deleting material: " . $conn->error . "');</script>";
    }
} else if (isset($_POST['delete_material'])) {
    echo "Unauthorized action.";
}


// Delete Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment']) && $role === 'Teacher') {
    $assignment_id = $_POST['assignment_id'];

    $query = "DELETE FROM assignments WHERE assignmentID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $assignment_id);

    if ($stmt->execute()) {
        echo "<script type='text/javascript'>alert('Assignment deleted successfully.');</script>";
    } else {
        echo "<script type='text/javascript'>alert('Error deleting assignment: " . $conn->error . "');</script>";
    }
} else if (isset($_POST['delete_assignment'])) {
    echo "Unauthorized action.";
}



$submission = null;

// Handle Submissions and Comments
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // For students
    if ($role == 'Student') {
        $assignmentID = isset($_POST['assignmentID']) ? intval($_POST['assignmentID']) : null;

        // Check if submission exists
        $stmt = $conn->prepare("SELECT * FROM Submissions WHERE assignmentID = ? AND studentID = ?");
        $stmt->bind_param("ii", $assignmentID, $userID);
        $stmt->execute();
        $submission = $stmt->get_result()->fetch_assoc();

        // Handle assignment submission
        if (isset($_POST['submitAssignment']) && !$submission) {
            // Get the due date for the assignment
            $stmt = $conn->prepare("SELECT dueDate FROM Assignments WHERE assignmentID = ?");
            $stmt->bind_param("i", $assignmentID);
            $stmt->execute();
            $dueDate = $stmt->get_result()->fetch_assoc()['dueDate'];

            // Get the current timestamp for submission date
            date_default_timezone_set('Asia/Dhaka');
            $submissionDate = date("Y-m-d H:i:s");

            // Check if submission is late
            $isLate = strtotime($submissionDate) > strtotime($dueDate);

            // Insert the submission record in Submissions table (without content)
            $stmt = $conn->prepare("INSERT INTO Submissions (assignmentID, studentID, submissionDate, isLate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $assignmentID, $userID, $submissionDate, $isLate);
            $stmt->execute();
            $submissionID = $stmt->insert_id;  // Get the submission ID of the new submission

            // Handle multiple file uploads
            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $files = $_FILES['files'];  // Array of files uploaded

                // Loop through the uploaded files
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] == 0) {  // Check if the file is uploaded without errors
                        $fileName = $files['name'][$i];
                        $fileContent = file_get_contents($files['tmp_name'][$i]);  // Read file content into a binary string

                        // Insert the file content into the SubmissionFiles table
                        $stmt = $conn->prepare("INSERT INTO SubmissionFiles (submissionID, fileName, fileContent) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $submissionID, $fileName, $fileContent);
                        $stmt->execute();
                    }
                }

                echo "<script>alert('Assignment submitted successfully with multiple files.');</script>";
            } else {
                echo "<script>alert('Please upload at least one file.');</script>";
            }
        } elseif ($submission) {
            echo "<script>alert('You have already submitted this assignment.');</script>";
        }

        // Handle comment addition
        if (isset($_POST['addComment'])) {
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            if ($assignmentID && !empty($comment)) {
                $stmt = $conn->prepare("INSERT INTO privateComments (assignmentID, userID, comment, commentDate) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $assignmentID, $userID, $comment);
                $stmt->execute();
                echo "<script>alert('Comment added successfully.');</script>";
            } else {
                echo "<script>alert('Failed to add comment. Please provide valid input.');</script>";
            }
        }
    }

    // For teachers
    if ($role == 'Teacher') {
        if (isset($_POST['addComment'])) {
            $assignmentID = isset($_POST['assignmentID']) ? intval($_POST['assignmentID']) : null;
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

            if ($assignmentID && !empty($comment)) {
                $stmt = $conn->prepare("INSERT INTO privateComments (assignmentID, userID, comment, commentDate) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $assignmentID, $userID, $comment);
                $stmt->execute();
                echo "<script>alert('Comment added successfully.');</script>";
            } else {
                echo "<script>alert('Failed to add comment. Please provide valid input.');</script>";
            }
        }
    }
}

function fetchSubmissionFiles($submissionID) {
    global $conn;  // Assuming $conn is your database connection
    $stmt = $conn->prepare("SELECT * FROM SubmissionFiles WHERE submissionID = ?");
    $stmt->bind_param("i", $submissionID);
    $stmt->execute();
    return $stmt->get_result();  // Return the result of the query
}


// Fetch Data for Display
$materials = fetchMaterials($courseID);
$assignments = fetchAssignments($courseID);
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

    /* Adjust sidebar width */
    #sidebar {
        transition: all 0.3s ease;
        margin-top: 45px;
        margin margin-left: 10px;
        width: 280px;
        height: 100px;
        border: 1px solid black;
        border-bottom-right-radius: 10px;
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

    /* #stream {
        margin-top: 20px;
        /* Adjust this value based on .post-form height 
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
    }*/

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

    /*Chatbox CSS*/

    /*body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }*/

    .chatbox-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .chatbox-header {
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        text-align: center;
    }

    .chatbox-messages {
        height: 300px;
        overflow-y: auto;
        padding: 10px;
        background: #f9f9f9;
    }

    .chatbox-messages::-webkit-scrollbar {
        width: 8px;
    }

    .chatbox-messages::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }

    .chatbox-messages::-webkit-scrollbar-thumb:hover {
        background-color: #aaa;
    }

    .chatbox-footer {
        display: flex;
        align-items: center;
        padding: 10px;
        background: #f1f1f1;
    }

    .chatbox-footer input[type="text"] {
        flex: 1;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        outline: none;
    }

    .file-icon {
        font-size: 20px;
        cursor: pointer;
        margin-right: 8px;
    }

    .send-button {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 4px;
        margin-left: 5px;
    }

    .send-button:hover {
        background-color: #45a049;
    }

    .send-button:focus {
        outline: 2px solid #45a049;
    }

    .new-message {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .chatbox-container {
            width: 100%;
            bottom: 0;
            right: 0;
            border-radius: 0;
        }
    }

    /*Materials CSS*/

    /* h1,
    h2 {
        color: #333;
        margin-bottom: 15px;
    }*/

    .mCard {
        background: #f8f9fa;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-top: 20px;
        margin-bottom: 15px;
        border: 1px solid black;
    }

    .file-list li a {
        color: #007bff;
        text-decoration: none;
    }

    .file-list li a:hover {
        text-decoration: underline;
    }

    button {
        background-color:rgb(255, 0, 0);
        border: none;
        color: white;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 3px;
    }

    button:hover {
        background-color: #0056b3;
    }

    textarea,
    input[type="text"],
    input[type="datetime-local"],
    input[type="file"] {
        width: 100%;
        margin: 10px 0;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 3px;
    }

    .CM {
        background-color: rgb(148, 193, 240);
        padding: 10px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        margin-top: 20px;
        border: 2px solid black;
    }

    .Am {
        background-color: rgb(255, 154, 92);
        padding: 10px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        margin-top: 20px;
        border: 2px solid black;
    }



    /*POP-UP*/
    /* Popup Modal Background */
    .VSPopupModal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        /* Transparent background */
        overflow: auto;
        padding-top: 50px;
        transition: all 0.3s ease;
    }

    /* Modal Content */
    .VSModalContent {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        /* Adjust width as necessary */
        max-width: 900px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    /* Close Button */
    .closePopupBtn {
        background: none;
        border: none;
        font-size: 30px;
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 20px;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .closePopupBtn:hover {
        color: #f44336;
    }

    /* Table Styles */
    #submissionsTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    #submissionsTable th,
    #submissionsTable td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    #submissionsTable th {
        background-color: #4CAF50;
        color: white;
        font-size: 16px;
    }

    #submissionsTable td {
        background-color: #f9f9f9;
        color: #333;
        font-size: 14px;
    }

    #submissionsTable td a {
        color: #007BFF;
        text-decoration: none;
    }

    #submissionsTable td a:hover {
        text-decoration: underline;
    }

    /* Table Row Hover Effect */
    #submissionsTable tr:hover {
        background-color: #f1f1f1;
    }

    /* Modal Header */
    h2 {
        text-align: center;
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .VSModalContent {
            width: 90%;
        }

        #submissionsTable th,
        #submissionsTable td {
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        h2 {
            font-size: 20px;
        }

        #submissionsTable th,
        #submissionsTable td {
            padding: 8px;
        }
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top" style="background-color: #cce7ca;">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <!-- Left Content: Hamburger and Home Button -->
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle sidebar">
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
                <img src="assets/profilePics/<?php echo $profilePic; ?>" alt="Profile" class="rounded-circle" width="40"
                    height="40" id="profilePic" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Logged in as: <?php echo htmlspecialchars($name); ?></a></li>
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
                <!-- <a href="#" class="list-group-item">Teaching</a>
                <a href="#" class="list-group-item">Enrolled</a>
                <a href="#" class="list-group-item">Archived Classes</a> -->
                <a href="#" class="list-group-item" style="margin-top:25px;">Help and Report</a>
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
                    <a class="nav-link active" href="#Materials">Materials</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="people.php?courseID=<?php echo urlencode($courseID); ?>&courseCode=<?php echo urlencode($courseCode); ?>
                        &section=<?php echo urlencode($section); ?>
                        &name=<?php echo urlencode($name); ?>
                        &userID=<?php echo urlencode($userID); ?>
                        &profilePicture=<?php echo urlencode($profilePic); ?>
                        &role=<?php echo urlencode($role); ?>">People</a>
                </li>
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
                    <a class="nav-link" href="#" id="discussionLink">Discussions</a>
                </li>
            </ul>

            <div class="container">
                <!-- Teacher Section: Upload Assignment and Materials -->
                <?php if ($role == 'Teacher'): ?>

                <!-- Upload Material Form -->
                <div class="mCard">
                    <div class="form-section">
                        <h2>Upload Material</h2>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <label for="title">Material Title:</label>
                            <input type="text" name="title" required><br>

                            <label for="description">Material Description:</label>
                            <textarea name="description" required></textarea><br>

                            <label for="files">Upload Files:</label>
                            <input type="file" name="files[]" multiple><br>

                            <button type="submit" name="uploadMaterial">Upload Material</button>
                        </form>
                    </div>
                </div>

                <!-- Create Assignment Form -->
                <div class="mCard">
                    <div class="form-section">
                        <h2>Create Assignment</h2>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <label for="title">Title:</label>
                            <input type="text" name="title" required><br>

                            <label for="description">Description:</label>
                            <textarea name="description" required></textarea><br>

                            <label for="dueDate">Due Date:</label>
                            <input type="datetime-local" name="dueDate" required><br>

                            <label for="files">Attach Files:</label>
                            <input type="file" name="files[]" multiple><br>

                            <button type="submit" name="createAssignment">Create Assignment</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Course Materials Display Section -->
                <div>
                    <h2 class="CM">Course Materials</h2>
                    <?php while ($material = $materials->fetch_assoc()): ?>
                    <div class="mCard">
                        <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($material['description'])); ?></p>
                        <h4>Files:</h4>
                        <ul class="file-list">
                            <?php 
                $stmt = $conn->prepare("SELECT * FROM MaterialFiles WHERE materialID = ?");
                $stmt->bind_param("i", $material['materialID']);
                $stmt->execute();
                $files = $stmt->get_result();
                while ($file = $files->fetch_assoc()): ?>
                            <li>
                                <a href="download_file.php?fileID=<?php echo $file['fileID']; ?>&fileType=material">
                                    <?php echo htmlspecialchars($file['fileName']); ?>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php if ($role === 'Teacher'): ?>
                        <form method="POST">
                            <input type="hidden" name="material_id" value="<?php echo $material['materialID']; ?>">
                            <button type="submit" name="delete_material">Delete Material</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>


                <div>
                    <h2 class="Am">Assignments</h2>
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                    <div class="mCard">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>

                            <!-- View Submissions Button (Only for Teachers) -->
                            <?php if ($role === 'Teacher'): ?>
                            <div style="text-align: right; margin-bottom: 10px;">
                                <button class="VSViewSubmissionsBtn"
                                    data-assignment-id="<?php echo $assignment['assignmentID']; ?>"
                                    style="background-color: #4CAF50; color: white; padding: 10px; margin-top: 10px; border: none; border-radius: 5px; cursor: pointer;">
                                    View Submissions
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo $assignment['dueDate']; ?></p>



                        <!-- Assignment Files -->
                        <h4>Assignment Files:</h4>
                        <ul>
                            <?php 
            $files = fetchAssignmentFiles($assignment['assignmentID']);
            while ($file = $files->fetch_assoc()):
            ?>
                            <li>
                                <a href="download_file.php?fileID=<?php echo $file['fileID']; ?>&fileType=assignment">
                                    <?php echo htmlspecialchars($file['fileName']); ?>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>

                        <!-- Student Submission Form -->
                        <?php if ($role == 'Student'): ?>
                        <div class="assignment-card"
                            style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;">
                            <h4>Assignment: <?php echo htmlspecialchars($assignment['title']); ?></h4>
                            <p>Deadline: <?php echo htmlspecialchars($assignment['dueDate']); ?></p>

                            <?php if (!$submission): ?>
                            <?php if (isBeforeDeadline($assignment['assignmentID'])): ?>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignmentID"
                                    value="<?php echo $assignment['assignmentID']; ?>">
                                <label for="files">Submit Assignment:</label>
                                <input type="file" name="files[]" multiple required><br>
                                <button type="submit" name="submitAssignment">Submit Assignment</button>
                            </form>
                            <?php else: ?>
                            <p style="color: red;">The deadline has passed. You can still submit, but it will be marked
                                as late.</p>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignmentID"
                                    value="<?php echo $assignment['assignmentID']; ?>">
                                <label for="files">Submit Assignment:</label>
                                <input type="file" name="files[]" multiple required><br>
                                <button type="submit" name="submitAssignment">Submit Assignment</button>
                            </form>
                            <?php endif; ?>
                            <?php else: ?>
                            <p style="color: green; font-weight: bold;">You have already submitted this assignment.</p>
                            <?php if ($submission['isLate']): ?>
                            <p style="color: orange;">Status: Late Submitted</p>
                            <?php endif; ?>
                            <div
                                style="background-color: #f9f9f9; padding: 10px; border-radius: 5px; margin-top: 10px;">
                                <p><strong>Submitted Files:</strong></p>
                                <ul>
                                    <?php 
                    $submissionFiles = fetchSubmissionFiles($submission['submissionID']);
                    while ($file = $submissionFiles->fetch_assoc()):
                    ?>
                                    <li>

                                        <a
                                            href="download_file.php?fileID=<?php echo $file['fileID']; ?>&fileType=submission">
                                            <?php echo htmlspecialchars($file['fileName']); ?>
                                        </a>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Comments Section -->
                        <h4>Comments:</h4>
                        <div class="comment-box">
                            <?php 
            $comments = fetchComments($assignment['assignmentID']);
            while ($comment = $comments->fetch_assoc()):
            ?>
                            <p><strong>
                                    <?php 
                    if ($comment['userID'] == $userID) {
                        echo 'You';
                    } else {
                        $stmt = $conn->prepare("SELECT name FROM Users WHERE userID = ?");
                        $stmt->bind_param("i", $comment['userID']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $commenter = $result->fetch_assoc();
                        echo htmlspecialchars($commenter['name']);
                    }
                ?>:
                                </strong>
                                <?php echo htmlspecialchars($comment['comment']); ?>
                                <small>(<?php echo $comment['commentDate']; ?>)</small>
                            </p>
                            <?php endwhile; ?>
                        </div>

                        <!-- Add Comment Section -->
                        <?php if ($role == 'Teacher' || $role == 'Student'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="assignmentID" value="<?php echo $assignment['assignmentID']; ?>">
                            <textarea name="comment" placeholder="Add your comment here..." required></textarea>
                            <button type="submit" name="addComment">Add Comment</button>
                        </form>
                        <?php endif; ?>

                        <!-- Delete Assignment Button -->
                        <?php if ($role === 'Teacher'): ?>
                        <form method="POST">
                            <input type="hidden" name="assignment_id"
                                value="<?php echo $assignment['assignmentID']; ?>">
                            <button type="submit" name="delete_assignment" style='margin-top:10px;'>Delete
                                Assignment</button>
                        </form>
                        <?php endif; ?>

                    </div>
                    <?php endwhile; ?>
                </div>


                <!-- Popup for Viewing Submissions -->
                <div class="VSPopupModal" id="submissionsPopup" style="display: none;">
                    <div class="VSModalContent">
                        <h2>Submissions</h2>
                        <button id="closePopupBtn" class="closePopupBtn">&times;</button>
                        <table id="submissionsTable">
                            <thead>
                                <tr>
                                    <th>Profile</th>
                                    <th>Name</th>
                                    <th>Submission Date</th>
                                    <th>Submission Status</th>
                                    <th>Files</th>
                                </tr>
                            </thead>
                            <tbody id="submissionsTableBody">
                                <!-- Dynamically Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>



            </div>

        </div>

        <div class="notifications">
            <!-- Notifications Section -->
            <h5 id="notification">Notifications</h5>
            <ul class="list-group">
                <li class="list-group-item">No pending tasks</li>
            </ul>
        </div>
    </div>

    <!-- Chatbox (Hidden Initially) -->
    <div id="chatboxContainer" style="display: none;">
        <div class="chatbox-container">
            <div class="chatbox-header">
                <h3>General Discussion</h3>
                <button id="closeChatbox"
                    style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 18px; cursor: pointer;">&times;</button>
            </div>
            <div class="chatbox-messages" id="messageContainer">
                <?php foreach ($messages as $msg): ?>
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <img src="assets/profilePics/<?php echo htmlspecialchars($msg['profilePicture']); ?>" alt="Profile"
                        style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                    <div>
                        <p><strong><?php echo htmlspecialchars($msg['name']); ?>:</strong>
                            <?php echo htmlspecialchars($msg['content']); ?></p>
                        <?php if ($msg['fileName']): ?>
                        <a
                            href="Notice.php?action=download_file&id=<?php echo $msg['messageID']; ?>&courseID=<?php echo urlencode($courseID); ?>">📎
                            <?php echo htmlspecialchars($msg['fileName']); ?></a>
                        <?php endif; ?>
                        <p style="font-size: 0.8em; color: gray;">
                            <?php echo date('Y-m-d H:i:s', strtotime($msg['timestamp'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="chatbox-footer">
                <img src="assets/profilePics/<?php echo htmlspecialchars($profilePic); ?>" id="userProfilePic"
                    alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <form id="messageForm" method="POST" enctype="multipart/form-data">
                    <div id="filePreview"></div> <!-- Preview the attached file here -->
                    <input type="text" id="messageInput" name="content" placeholder="Type your message..." />
                    <label for="fileInput" class="file-icon">📎</label>
                    <input type="file" id="fileInput" name="file" style="display: none;" />
                    <input type="hidden" name="action" value="send_message" />
                    <input type="hidden" name="courseID" value="<?php echo htmlspecialchars($courseID); ?>" />
                    <input type="hidden" name="userID" value="<?php echo htmlspecialchars($userID); ?>" />
                    <button type="submit" class="send-button">Send</button>
                </form>
            </div>
        </div>
    </div>



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


    /**Materials Functionality*/

    document.addEventListener("DOMContentLoaded", function() {
        const assignmentForms = document.querySelectorAll("form[action='submitAssignment']");

        assignmentForms.forEach(form => {
            const dueDateInput = form.querySelector("input[name='dueDate']");

            form.addEventListener("submit", (event) => {
                const currentDate = new Date();
                const dueDate = new Date(dueDateInput.value);

                if (currentDate > dueDate) {
                    alert(
                        "The deadline for this assignment has passed. Submission will be marked as late."
                    );
                }
            });
        });
    });



    // Add event listeners to delete buttons for materials
    document.querySelectorAll('.mCard button[name="delete_material"]').forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this material?')) {
                e.preventDefault(); // Prevent the form submission if user cancels
            }
        });
    });

    // Add event listeners to delete buttons for assignments
    document.querySelectorAll('.mCard button[name="delete_assignment"]').forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this assignment?')) {
                e.preventDefault(); // Prevent the form submission if user cancels
            }
        });
    });


    document.querySelectorAll('.VSViewSubmissionsBtn').forEach(button => {
        button.addEventListener('click', async function() {
            const assignmentID = this.getAttribute(
                'data-assignment-id'); // Get the assignment ID from the button
            const popup = document.getElementById(
                'submissionsPopup'); // The popup modal for submissions
            const tableBody = document.getElementById(
                'submissionsTableBody'); // The table body to display submissions

            // Clear the table body before loading new content
            tableBody.innerHTML = '';

            try {
                // Fetch submissions data from the server
                console.log('Assignment ID:', assignmentID);
                const response = await fetch(`fetch_submissions.php?assignmentID=${assignmentID}`);

                // Check if the response is valid
                if (!response.ok) {
                    throw new Error('Failed to fetch data from server');
                }

                // Get the response as text (HTML)
                const data = await response.text();

                // If the response is HTML (error or table data), update the table
                if (data.includes('<table')) {
                    // Successfully received table data, inject it into the table body
                    tableBody.innerHTML = data;
                } else {
                    // If the response is an error message, show it
                    tableBody.innerHTML =
                        `<tr><td colspan="3" style="text-align: center; padding: 20px;">${data}</td></tr>`;
                }

                // Show the popup
                popup.style.display = 'flex';
            } catch (error) {
                console.error('Error fetching submissions:', error);
                tableBody.innerHTML =
                    '<tr><td colspan="3" style="text-align: center; padding: 20px;">Failed to load submissions.</td></tr>';
                popup.style.display = 'flex';
            }
        });
    });



    // Close the popup when the close button is clicked
    document.getElementById('closePopupBtn').addEventListener('click', function() {
        document.getElementById('submissionsPopup').style.display = 'none';
    });




    /*Chat Functions*/

    // Event listener for opening the chatbox
    document.getElementById('discussionLink').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        const chatbox = document.getElementById('chatboxContainer');
        chatbox.style.display = 'block'; // Show the chatbox
        loadMessages(); // Optional: Load previous messages when the chatbox opens
    });
    // Close button functionality
    document.getElementById('closeChatbox').addEventListener('click', () => {
        document.getElementById('chatboxContainer').style.display = 'none';
    });
    // Event listener for file input change
    document.getElementById('fileInput').addEventListener('change', function() {
        const file = this.files[0];
        const fileName = file.name;

        // Display the file name as a clickable link above the input field
        const filePreviewContainer = document.getElementById('filePreview');
        filePreviewContainer.innerHTML = `<a href="#" class="file-link">${fileName}</a>`;
    });

    // Remove the file preview when the user starts typing or clears the file input
    document.getElementById('messageInput').addEventListener('input', function() {
        const filePreviewContainer = document.getElementById('filePreview');
        if (this.value === '') {
            filePreviewContainer.innerHTML = ''; // Clear preview if no message content
        }
    });
    </script>


</body>

</html>

<?php
$conn->close();
?>