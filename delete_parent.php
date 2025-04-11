<?php
session_start();

// Check if the user is not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Redirect to the login page or another page
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
require_once('config.php');

// Check if ParentID is provided in the URL
if (isset($_GET['id'])) {
    $parentID = $_GET['id'];

    // First, check if the parent exists
    $checkQuery = "SELECT * FROM Parent WHERE ParentID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $parentID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        // Parent doesn't exist
        header("Location: view_parents.php?error=Parent not found");
        exit();
    }

    // Delete the parent from the database
    $deleteQuery = "DELETE FROM Parent WHERE ParentID = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $parentID);

    if ($deleteStmt->execute()) {
        // Deletion successful
        header("Location: view_parents.php?success=Parent deleted successfully");
        exit();
    } else {
        // Error in deletion
        header("Location: view_parents.php?error=Error deleting parent");
        exit();
    }
} else {
    // No ID provided
    header("Location: view_parents.php?error=No parent ID provided");
    exit();
}
?>