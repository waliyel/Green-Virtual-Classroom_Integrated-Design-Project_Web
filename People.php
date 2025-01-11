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
                    <a class="nav-link" href="Materials.php?courseID=<?php echo urlencode($courseID); ?>
                        &courseCode=<?php echo urlencode($courseCode); ?>
                        &section=<?php echo urlencode($section); ?>
                        &name=<?php echo urlencode($name); ?>
                        &userID=<?php echo urlencode($userID); ?>
                        &profilePicture=<?php echo urlencode($profilePic); ?>
                        &role=<?php echo urlencode($role); ?>">Materials</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#people">People</a>
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
                    <a class="nav-link" href="#">Discussions</a>
                </li>
            </ul>

            <div class="container mt-4">
                <!-- Teachers Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Teachers</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($teachers as $teacher): ?>
                        <div class="d-flex align-items-center mb-2">
                            <img src="assets/profilePics/<?php echo $teacher['profilePicture']; ?>" alt="Profile"
                                class="rounded-circle me-3" width="50" height="50" style="border: 2px solid #007bff;">
                            <span><?php echo htmlspecialchars($teacher['name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Students Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Students</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($students as $student): ?>
                        <div class="d-flex align-items-center mb-2">
                            <img src="assets/profilePics/<?php echo $student['profilePicture']; ?>" alt="Profile"
                                class="rounded-circle me-3" width="50" height="50" style="border: 2px solid #007bff;">
                            <span><?php echo htmlspecialchars($student['name']); ?></span>
                        </div>
                        <?php endforeach; ?>
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




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>