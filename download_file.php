<?php
// Check if fileID and fileType are set
if (isset($_GET['fileID']) && isset($_GET['fileType'])) {
    $fileID = $_GET['fileID'];
    $fileType = $_GET['fileType'];  // This could be 'material' or 'assignment'

    // Database connection
    $conn = new mysqli("localhost", "root", "", "green_virtual_classroom");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the query depending on fileType (material or assignment)
    if ($fileType === 'material') {
        $stmt = $conn->prepare("SELECT fileName, fileContent FROM MaterialFiles WHERE fileID = ?");
    } elseif ($fileType === 'assignment') {
        $stmt = $conn->prepare("SELECT fileName, fileContent FROM AssignmentFiles WHERE fileID = ?");
    } elseif ($fileType === 'submission') {
        $stmt = $conn->prepare("SELECT fileName, fileContent FROM SubmissionFiles WHERE fileID = ?");
    }else {
        echo "<script>alert('Invalid file type.');</script>";
        exit();
    }

    // Bind parameters and execute query
    $stmt->bind_param("i", $fileID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the file exists
    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $fileName = $file['fileName'];
        $fileContent = $file['fileContent'];

        // Ensure the file content exists before attempting to download
        if ($fileContent) {
            // Set headers to force download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Content-Length: ' . strlen($fileContent));

            // Output the file content for download
            echo $fileContent;
            exit();
        } else {
            echo "<script>alert('File content is missing.');</script>";
        }
    } else {
        echo "<script>alert('File not found in database.');</script>";
    }
} else {
    echo "<script>alert('Invalid file ID or file type.');</script>";
}
?>
