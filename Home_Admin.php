<?php
// Establish a connection to the database
$conn = new mysqli("localhost", "root", "", "green_virtual_classroom"); // Replace with your database credentials

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details from URL parameters

$name = isset($_GET['name']) ? $_GET['name'] : 'Unknown';
$userID = isset($_GET['userID']) ? $_GET['userID'] : 'Unknown';
$profilePic = isset($_GET['profilePicture']) ? $_GET['profilePicture'] : 'default_profile_pic.jpg';
$role = isset($_GET['role']) ? $_GET['role'] : 'student';

// Query courses based on user role
if ($role === 'Teacher') {
    // Fetch courses the teacher is teaching
    $sql = "SELECT Courses.courseID, Courses.courseCode, Courses.title, Courses.description, Courses.semester, Courses.section, Users.name AS teacher_name
            FROM Courses
            INNER JOIN Users ON Courses.teacherID = Users.userID
            WHERE Courses.teacherID = '$userID'";
} else {
    // Fetch courses the student is enrolled in
    $sql = "SELECT Courses.courseID, Courses.courseCode, Courses.title, Courses.description, Courses.semester, Courses.section, Users.name AS teacher_name
            FROM Courses
            INNER JOIN Enrollments ON Courses.courseID = Enrollments.courseID
            INNER JOIN Users ON Courses.teacherID = Users.userID
            WHERE Enrollments.studentID = '$userID'";
}

$result = $conn->query($sql);

// Assuming the profile picture path is stored in the database as just the filename
$profilePic = isset($_GET['profilePicture']) ? $_GET['profilePicture'] : 'default_profile_pic.jpg'; // Default profile pic if not set

// Sanitize the filename
$profilePic = htmlspecialchars($profilePic);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">  

    <title>Green Virtual Classroom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>  

    <style>
    /* Additional CSS for circular profile picture with border */
    #profilePic {
        border: 2px solid #007bff;
    }

    /* Adjust sidebar width */
    #sidebar {
        transition: all 0.3s ease;
        margin-top: 45px;
        margin margin-left: 10px;
        width: 230px;
        height: 80px;
        border: 1px solid black;
        border-bottom-right-radius: 10px;


    }

    /* Style the hamburger button container */
    .hamburger-container {
        display: flex;
        align-items: center;
        padding-left: 10px;
        padding-right: 20px;
        max-width: 500px;
    }

    /* Add spacing to prevent course tiles from overlapping with the top navigation bar */
    .content-wrapper {
        margin-top: 80px;
        margin-left: 20px;
    }

    .course-tile {
        transition: transform 0.3s ease;
    }

    .course-tile:hover {
        transform: scale(1.05);
    }

    /* Modal background overlay */
    .HS-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* Modal content container */
    .HS-modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        position: relative;
        text-align: left;
    }

    /* Modal header */
    .HS-modal-header {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    /* Close button */
    .HS-close-modal-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: transparent;
        border: none;
        font-size: 18px;
        cursor: pointer;
    }

    .HS-close-modal-btn:hover {
        color: red;
    }

    /* Form styling */
    .HS-modal-body label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    .HS-modal-body textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        resize: vertical;
    }

    .HS-modal-body button {
        background-color: #007BFF;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }

    .HS-modal-body button:hover {
        background-color: #0056b3;
    }

    /* Success and error messages */
    .HS-alert {
        margin-top: 10px;
        padding: 10px;
        border-radius: 5px;
    }

    .HS-alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .HS-alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg fixed-top" style="background-color: #cce7ca;">
        <div class="container-fluid">
            <div class="hamburger-container">
                <!-- Sidebar toggle button with text and a border for better visibility -->
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle sidebar">
                    ☰
                </button>
            </div>
            <a class="navbar-brand" href="#"><i class="fas fa-home"></i></a>
            <span class="navbar-text mx-auto" style="font-size: 26px; padding-right: 90px;">Green Virtual
                Classroom</span>
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
                <a href="#" class="list-group-item" id="HS-helpLink">Help and Report</a>

                <!-- Modal -->
                <div class="HS-modal-overlay" id="HS-helpModal">
                    <div class="HS-modal-content">
                        <button class="HS-close-modal-btn" onclick="HSCloseModal()">&times;</button>
                        <div class="HS-modal-header">Help and Support</div>
                        <div class="HS-modal-body">
                            <p><b>Request ID:</b>
                                <?php echo !empty($request_id) ? $request_id : 'Will be generated after submission'; ?>
                            </p>

                            <form method="POST" action="help_support.php?userID=<?php echo $userID; ?>">
                                <label for="HS-complaint">Describe your issue</label>
                                <textarea id="HS-complaint" name="complaint" rows="4" required></textarea>
                                <button type="submit">Submit Request</button>
                            </form>

                            <?php if (!empty($success_message)): ?>
                            <div class="HS-alert HS-alert-success">
                                <?php echo $success_message; ?>
                            </div>
                            <?php elseif (!empty($error_message)): ?>
                            <div class="HS-alert HS-alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>






        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    //Help Modal
    // Open modal when the link is clicked
    document.getElementById('HS-helpLink').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default anchor behavior
        document.getElementById('HS-helpModal').style.display = 'flex';
    });

    // Close modal function
    function HSCloseModal() {
        document.getElementById('HS-helpModal').style.display = 'none';
    }
    </script>

</body>

</html>  

<?php
$conn->close(); // Close the database connection
?>