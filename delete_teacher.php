<?php
session_start();

// Check if the user is not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Redirect to the login page or another page
    header("Location: login.php");
    exit();
}

// Database Connection
require_once('config.php');

// Check if teacher ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $teacherId = $_GET['id'];
    
    // Prepare SQL statement to delete the teacher
    $sql = "DELETE FROM Teacher WHERE TeacherID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind the ID parameter
        $stmt->bind_param("i", $teacherId);
        
        // Execute the statement
        if ($stmt->execute()) {
            // Successful deletion
            header("Location: view_teachers.php?success=Teacher successfully deleted");
            exit();
        } else {
            // Error in deletion
            header("Location: view_teachers.php?error=Failed to delete teacher: " . $conn->error);
            exit();
        }
    } else {
        // Error in statement preparation
        header("Location: view_teachers.php?error=Failed to prepare delete statement: " . $conn->error);
        exit();
    }
} else {
    // No ID provided
    header("Location: view_teachers.php?error=No teacher ID provided");
    exit();
}

// Close connection
$conn->close();
?>