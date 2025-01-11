<?php
session_start();
include_once('db_connect.php'); // Include the database connection

$error_message = ''; // Variable to hold error message
$role = ''; // Role selected, to persist form values

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role']; // Role: Student or Teacher
    $identifier = $_POST['identifier']; // Student ID or Username
    $password = $_POST['password'];
 
    // Sanitize inputs
    $password = mysqli_real_escape_string($mysqli, $password);

    // SQL query based on the role (Student or Teacher)
    if ($role == 'Admin'){
        $sql = "SELECT * FROM users WHERE username='$identifier' AND role='Admin' LIMIT 1";
    }else if ($role == 'Student') {
        // Check for student using studentID
        $sql = "SELECT * FROM users WHERE studentID='$identifier' AND role='Student' LIMIT 1";
    } else {
        // Check for teacher using username
        $sql = "SELECT * FROM users WHERE username='$identifier' AND role='Teacher' LIMIT 1";
    }

    $result = mysqli_query($mysqli, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['profilePicture'] = $user['profilePicture']; // Add profile picture to session
            
            // Update the last login time
            $updateLoginTime = "UPDATE users SET lastLogin = NOW() WHERE userID = {$user['userID']}";
            mysqli_query($mysqli, $updateLoginTime);

            // Redirect based on the role
            if ($role == 'Admin'){
                header("Location: Home_Admin.php?name={$user['name']}&userID={$user['userID']}&role=Admin&profilePicture={$user['profilePicture']}");
            } else if ($role == 'Student') {
                header("Location: Home.php?name={$user['name']}&userID={$user['userID']}&role=Student&profilePicture={$user['profilePicture']}");
            } else {
                header("Location: Home.php?name={$user['name']}&userID={$user['userID']}&role=Teacher&profilePicture={$user['profilePicture']}");
            }
            exit();
        } else {
            $error_message = $role == 'Student' ? "Incorrect StudentID or Password, try again." : "Incorrect Username or Password, try again.";
        }
    } else {
        $error_message = $role == 'Student' ? "Incorrect StudentID or Password, try again." : "Incorrect Username or Password, try again.";
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
    <title>GVC Login</title>
    <style>
    /* Custom Styles */
    .dynamic-input {
        display: none;
    }

    #password {
        margin-top: 10px;
    }

    .custom-alert {
        display: block;
        color: white;
        background-color: red;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        text-align: center;
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.5;
        position: relative;
    }

    .close-btn {
        position: absolute;
        top: 5px;
        right: 10px;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }
    </style>
</head>

<body>
    <div class="uf-form-signin">
        <div class="text-center">
            <a href="https://www.green.edu.bd/" target="_blank">
                <img src="./assets/img/GUB.png" alt="" width="120" height="120">
            </a>
            <h1 class="text-deep-blue h3">Account Login</h1>
        </div>

        <!-- Custom error alert -->
        <div id="customAlert" class="custom-alert" style="display: none;">
            <span class="close-btn" onclick="closeAlert()">&times;</span>
            <strong>Warning: </strong><span id="alertMessage"></span>
        </div>

        <!-- Form for login -->
        <form class="mt-4" method="POST" action="login.php" id="loginForm">
            <div class="mb-3 d-flex justify-content-center align-items-center">
                <label for="roleSelect" class="form-label me-2">Login as:</label>
                <select id="roleSelect" name="role" onchange="toggleInputs()">
                    <option value="Admin">Admin</option>
                    <option value="Student">Student</option>
                    <option value="Teacher">Teacher</option>
                </select>
            </div>


            <!-- Admin Username input (Admin role) -->
            <div id="adminInputs" class="dynamic-input">
                <div class="input-group uf-input-group input-group-lg">
                    <span class="input-group-text fa fa-user"></span>
                    <input type="text" class="form-control" id="adminIdentifier" placeholder="Username">
                </div>
            </div>

            <!-- Faculty Username input (Teacher role) -->
            <div id="facultyInputs" class="dynamic-input">
                <div class="input-group uf-input-group input-group-lg">
                    <span class="input-group-text fa fa-user"></span>
                    <input type="text" class="form-control" id="teacherIdentifier" placeholder="Username">
                </div>
            </div>

            <!-- Student ID input (Student role) -->
            <div id="studentInputs" class="dynamic-input">
                <div class="input-group uf-input-group input-group-lg">
                    <span class="input-group-text fa fa-user"></span>
                    <input type="text" class="form-control" id="studentIdentifier" placeholder="Student ID">
                </div>
            </div>



            <!-- Password input for all roles -->
            <div id="password" class="input-group uf-input-group input-group-lg">
                <span class="input-group-text fa fa-lock"></span>
                <input type="password" class="form-control" name="password" placeholder="Password">
            </div>

            <div class="d-flex mb-3 justify-content-center">
                <a href="#" onclick="forgotPassword()">Forgot password?</a>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg" onclick="setIdentifier()">Login</button>
            </div>
        </form>
    </div>

    <script src="./assets/js/popper.min.js"></script>
    <script src="./assets/js/bootstrap.min.js"></script>
    <script>
    // Toggling between student and teacher input fields
    function toggleInputs() {
        const role = document.getElementById('roleSelect').value;
        const adminInputs = document.getElementById('adminInputs');
        const facultyInputs = document.getElementById('facultyInputs');
        const studentInputs = document.getElementById('studentInputs');

        // Hide all inputs initially
        adminInputs.style.display = 'none';
        facultyInputs.style.display = 'none';
        studentInputs.style.display = 'none';

        // Display based on selected role
        if (role === 'Admin') {
            adminInputs.style.display = 'block';
        } else if (role === 'Teacher') {
            facultyInputs.style.display = 'block';
        } else if (role === 'Student') {
            studentInputs.style.display = 'block';
        }
    }

    // Function to set the correct identifier name based on role selected
    function setIdentifier() {
        const role = document.getElementById('roleSelect').value;
        const form = document.getElementById('loginForm');
        let identifierField;

        if (role === 'Admin') {
            identifierField = document.getElementById('adminIdentifier');
            identifierField.setAttribute('name', 'identifier'); // Set correct name
        } else if (role === 'Teacher') {
            identifierField = document.getElementById('teacherIdentifier');
            identifierField.setAttribute('name', 'identifier'); // Set correct name
        } else {
            identifierField = document.getElementById('studentIdentifier');
            identifierField.setAttribute('name', 'identifier'); // Set correct name
        }
    }

    // Trigger the toggle function on page load to ensure the correct input is displayed
    window.onload = function() {
        toggleInputs(); // Set the default view on page load
    };

    // Display error message if exists using a custom alert
    <?php if (!empty($error_message)): ?>
    document.getElementById('customAlert').style.display = 'block';
    document.getElementById('alertMessage').textContent = "<?php echo $error_message; ?>";
    <?php endif; ?>

    // Function to close the alert box
    function closeAlert() {
        document.getElementById('customAlert').style.display = 'none';
    }

    // Display the forgot password form
    function forgotPassword() {
        document.getElementById('loginForm').style.display = 'none'; // Hide login form
        window.location.href = 'forget_password.php'; // Redirect to forget password page
    }
    </script>
</body>

</html>