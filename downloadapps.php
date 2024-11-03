<?php
// Check if the 'file' parameter is set in the URL
if(isset($_GET['file'])) {
    $file = basename($_GET['file']);  // Get the filename from the URL
    
    // Define the path to the file
    $filepath = 'uploads/' . $file;

    // Check if the file exists
    if(file_exists($filepath)) {
        // Send the necessary headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush(); // Flush system output buffer
        
        // Read the file and send to the user
        readfile($filepath);
        exit;
    } else {
        // If the file doesn't exist, show an error
        echo "Error: File not found!";
    }
} else {
    // If no file is specified, redirect back to the main page
    header('Location: index.php');
    exit;
}
?>