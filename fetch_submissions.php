<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "green_virtual_classroom");

if ($conn->connect_error) {
    echo "<div class='error'>Connection failed: " . $conn->connect_error . "</div>";
    exit;
}

// Get the assignment ID from the URL parameter
$assignmentID = $_GET['assignmentID'] ?? 0;

// Validate the assignment ID
if (empty($assignmentID)) {
    echo "<div class='error'>Assignment ID not provided or invalid</div>";
    exit;
}

// Prepare the SQL query to fetch submissions
$sql = "
    SELECT 
        s.submissionID, s.studentID, s.submissionDate, s.isLate,
        u.name, u.profilePicture
    FROM Submissions s
    JOIN Users u ON s.studentID = u.userID
    WHERE s.assignmentID = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<div class='error'>Failed to prepare SQL query: " . $conn->error . "</div>";
    exit;
}

$stmt->bind_param("i", $assignmentID);
if (!$stmt->execute()) {
    echo "<div class='error'>Error executing query: " . $stmt->error . "</div>";
    exit;
}

$result = $stmt->get_result();

// Fetch the submissions
if ($result->num_rows === 0) {
    echo "<div class='error'>No submissions found for Assignment ID: " . $assignmentID . "</div>";
} else {
    // If there are submissions, display them in an HTML table
    echo "<table border='1'>
            <thead>
                <tr>
                    
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $submissionID = $row['submissionID'];

        // Fetch all files associated with this submission
        $filesQuery = "
            SELECT fileID, fileName 
            FROM SubmissionFiles 
            WHERE submissionID = ?
        ";
        $fileStmt = $conn->prepare($filesQuery);
        $fileStmt->bind_param("i", $submissionID);
        $fileStmt->execute();
        $fileResult = $fileStmt->get_result();

        // Start a new row for each submission
        echo "<tr>";
        echo "<td><img src='assets/profilePics/" . htmlspecialchars($row['profilePicture'] ?: 'default-profile.png') . "' alt='Profile Picture' width='50' height='50' style='border-radius:50px;'></td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        
        // Display submission date
        echo "<td>" . htmlspecialchars($row['submissionDate']) . "</td>";

        // Display submission status based on isLate value
        if ($row['isLate'] == 1) {
            echo "<td>Late Submission</td>";
        } else {
            echo "<td>On Time</td>";
        }

        // List the files for this submission
        echo "<td>";
        $files = []; // To store all file links for this submission

        while ($fileRow = $fileResult->fetch_assoc()) {
            // Generate the file download link based on the fileID
            $downloadLink = 'download_file.php?fileID=' . $fileRow['fileID'] . '&fileType=submission';
            $files[] = "<a href='" . $downloadLink . "'>" . htmlspecialchars($fileRow['fileName']) . "</a>";
        }

        // Display all files for this submission in the table cell
        echo implode('<br>', $files); // Separate file links with a line break
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
}

// Close the database connection
$conn->close();
?>
