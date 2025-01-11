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

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_POST['authorID']) && isset($_POST['courseID'])) {
    $content = $conn->real_escape_string($_POST['content']);
    $authorID = $conn->real_escape_string($_POST['authorID']);
    $courseID = $conn->real_escape_string($_POST['courseID']);
    // Insert new post
    $postQuery = "INSERT INTO Posts (content, authorID, courseID) VALUES ('$content', '$authorID', '$courseID')";
    $conn->query($postQuery);
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && isset($_POST['postID']) && isset($_POST['authorID'])) {
    $commentContent = $conn->real_escape_string($_POST['comment_content']);
    $postID = $conn->real_escape_string($_POST['postID']);
    $authorID = $conn->real_escape_string($_POST['authorID']);

    // Insert new comment
    $commentQuery = "INSERT INTO Comments (content, postID, authorID) VALUES ('$commentContent', '$postID', '$authorID')";
    $conn->query($commentQuery);
}

// Handle edit post request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post_content']) && isset($_POST['edit_post_id'])) {
    $editContent = $conn->real_escape_string($_POST['edit_post_content']);
    $editPostID = $conn->real_escape_string($_POST['edit_post_id']);
    $currentUserID = $conn->real_escape_string($_POST['current_user_id']);
    $currentRole = $conn->real_escape_string($_POST['current_role']);

    $checkPostQuery = "SELECT authorID FROM Posts WHERE postID = '$editPostID'";
    $checkPostResult = $conn->query($checkPostQuery);
    if ($checkPostResult && $checkPostResult->num_rows > 0) {
        $post = $checkPostResult->fetch_assoc();
        if ($post['authorID'] == $currentUserID || $currentRole === 'teacher') {
            $conn->query("UPDATE Posts SET content = '$editContent' WHERE postID = '$editPostID'");
        }
    }
}

// Handle delete post request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $deletePostID = $conn->real_escape_string($_POST['delete_post_id']);
    $currentUserID = $conn->real_escape_string($_POST['current_user_id']);
    $currentRole = $conn->real_escape_string($_POST['current_role']);

    $checkPostQuery = "SELECT authorID FROM Posts WHERE postID = '$deletePostID'";
    $checkPostResult = $conn->query($checkPostQuery);
    if ($checkPostResult && $checkPostResult->num_rows > 0) {
        $post = $checkPostResult->fetch_assoc();
        if ($post['authorID'] == $currentUserID || $currentRole === 'teacher') {
            $conn->query("DELETE FROM Comments WHERE postID = '$deletePostID'");
            $conn->query("DELETE FROM Posts WHERE postID = '$deletePostID'");
        }
    }
}

// Handle edit comment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment_content']) && isset($_POST['edit_comment_id'])) {
    $editContent = $conn->real_escape_string($_POST['edit_comment_content']);
    $editCommentID = $conn->real_escape_string($_POST['edit_comment_id']);
    $currentUserID = $conn->real_escape_string($_POST['current_user_id']);
    $currentRole = $conn->real_escape_string($_POST['current_role']);

    $checkCommentQuery = "SELECT authorID FROM Comments WHERE commentID = '$editCommentID'";
    $checkCommentResult = $conn->query($checkCommentQuery);
    if ($checkCommentResult && $checkCommentResult->num_rows > 0) {
        $comment = $checkCommentResult->fetch_assoc();
        if ($comment['authorID'] == $currentUserID || $currentRole === 'teacher') {
            $conn->query("UPDATE Comments SET content = '$editContent' WHERE commentID = '$editCommentID'");
        }
    }
}

// Handle delete comment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $deleteCommentID = $conn->real_escape_string($_POST['delete_comment_id']);
    $currentUserID = $conn->real_escape_string($_POST['current_user_id']);
    $currentRole = $conn->real_escape_string($_POST['current_role']);

    $checkCommentQuery = "SELECT authorID FROM Comments WHERE commentID = '$deleteCommentID'";
    $checkCommentResult = $conn->query($checkCommentQuery);
    if ($checkCommentResult && $checkCommentResult->num_rows > 0) {
        $comment = $checkCommentResult->fetch_assoc();
        if ($comment['authorID'] == $currentUserID || $currentRole === 'teacher') {
            $conn->query("DELETE FROM Comments WHERE commentID = '$deleteCommentID'");
        }
    }
}

// Fetch posts and associated comments for the course
$postQuery = "
    SELECT Posts.postID, Posts.content, Users.name AS author_name, Posts.timestamp, Posts.authorID
    FROM Posts
    INNER JOIN Users ON Posts.authorID = Users.userID
    WHERE Posts.courseID = '$courseID'
    ORDER BY Posts.timestamp DESC";
$postResult = $conn->query($postQuery);
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
                    <a class="nav-link active" href="#stream">Stream</a>
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
                    <a class="nav-link" href="#">Discussions</a>
                </li>
            </ul>



            <!-- Posts and Comments -->
            <div id="stream" class="container">
                <!-- Post Form -->
                <form method="POST" class="post-form">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="2"
                            placeholder="Announce something to your class..." required></textarea>
                    </div>
                    <input type="hidden" name="authorID" value="<?php echo htmlspecialchars($userID); ?>">
                    <input type="hidden" name="courseID" value="<?php echo htmlspecialchars($courseID); ?>">
                    <input type="hidden" name="current_user_id" value="<?php echo htmlspecialchars($userID); ?>">
                    <!-- Add this line -->
                    <input type="hidden" name="current_role" value="<?php echo htmlspecialchars($role); ?>">
                    <!-- Add this line -->
                    <div class="container">
                        <button type="submit" class="btn btn-primary">Post</button>
                    </div>
                </form>

                <!-- Display Posts -->
                <?php if ($postResult && $postResult->num_rows > 0): ?>
                <?php while ($post = $postResult->fetch_assoc()): ?>
                <div class="card post-card my-3">
                    <div class="card-header post-card-header d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                        <span><?php echo htmlspecialchars($post['timestamp']); ?></span>
                    </div>
                    <div class="card-body post-card-body">
                        <p><?php echo htmlspecialchars($post['content']); ?></p>

                        <!-- Edit/Delete Post Buttons -->
                        <?php if ($post['authorID'] == $userID || ($role === 'teacher' && $post['authorID'] != $userID)): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="edit_post_id" value="<?php echo $post['postID']; ?>">
                            <input type="hidden" name="current_user_id"
                                value="<?php echo htmlspecialchars($userID); ?>"> <!-- Add this line -->
                            <input type="hidden" name="current_role" value="<?php echo htmlspecialchars($role); ?>">
                            <!-- Add this line -->
                            <textarea name="edit_post_content"
                                class="form-control mb-2"><?php echo htmlspecialchars($post['content']); ?></textarea>
                            <button type="submit" class="btn btn-outline-success">Edit</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="delete_post_id" value="<?php echo $post['postID']; ?>">
                            <input type="hidden" name="current_user_id"
                                value="<?php echo htmlspecialchars($userID); ?>"> <!-- Add this line -->
                            <input type="hidden" name="current_role" value="<?php echo htmlspecialchars($role); ?>">
                            <!-- Add this line -->
                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>

                        <!-- Comments Section -->
                        <?php
            $postID = $post['postID'];
            $commentQuery = "
                SELECT Comments.commentID, Comments.content, Users.name AS author_name, Comments.timestamp, Comments.authorID
                FROM Comments
                INNER JOIN Users ON Comments.authorID = Users.userID
                WHERE Comments.postID = $postID
                ORDER BY Comments.timestamp ASC";
            $commentResult = $conn->query($commentQuery);
            ?>
                        <?php if ($commentResult && $commentResult->num_rows > 0): ?>
                        <div class="mt-3">
                            <h6>Comments:</h6>
                            <?php while ($comment = $commentResult->fetch_assoc()): ?>
                            <div class="comment mb-2">
                                <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars($comment['timestamp']); ?></span>
                                <p><?php echo htmlspecialchars($comment['content']); ?></p>

                                <!-- Edit/Delete Comment Buttons -->
                                <?php if ($comment['authorID'] == $userID || ($role === 'teacher' && $comment['authorID'] != $userID)): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="edit_comment_id"
                                        value="<?php echo $comment['commentID']; ?>">
                                    <input type="hidden" name="current_user_id"
                                        value="<?php echo htmlspecialchars($userID); ?>"> <!-- Add this line -->
                                    <input type="hidden" name="current_role"
                                        value="<?php echo htmlspecialchars($role); ?>"> <!-- Add this line -->
                                    <textarea name="edit_comment_content"
                                        class="form-control mb-2"><?php echo htmlspecialchars($comment['content']); ?></textarea>
                                    <button type="submit" class="btn btn-outline-success">Edit</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="delete_comment_id"
                                        value="<?php echo $comment['commentID']; ?>">
                                    <input type="hidden" name="current_user_id"
                                        value="<?php echo htmlspecialchars($userID); ?>"> <!-- Add this line -->
                                    <input type="hidden" name="current_role"
                                        value="<?php echo htmlspecialchars($role); ?>"> <!-- Add this line -->
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <form method="POST" class="comment-form mt-3">
                            <div class="input-group">
                                <input type="text" name="comment_content" class="form-control"
                                    placeholder="Add a comment..." required>
                                <input type="hidden" name="postID" value="<?php echo $postID; ?>">
                                <input type="hidden" name="authorID" value="<?php echo htmlspecialchars($userID); ?>">
                                <button type="submit" class="btn btn-outline-primary">Comment</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
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