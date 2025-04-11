<?php
session_start();

// Check if the user is not logged in or not a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    // Redirect to the login page
    header("Location: login.php");
    exit();
}

// Get the teacher ID from the session
$teacherID = $_SESSION['user_id'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
include('config.php');

// Include sidebar
include('teacher_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Variables */
        :root {
          --primary-color: #4361ee;
          --primary-hover: #3a56d4;
          --secondary-color: #f72585;
          --light-bg: #f8f9fa;
          --dark-text: #333;
          --light-text: #6c757d;
          --border-radius: 8px;
          --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          --transition: all 0.3s ease;
        }

        body {
          font-family: 'Poppins', sans-serif;
          background-color: var(--light-bg);
          color: var(--dark-text);
          display: flex;
          justify-content: flex-start;
          align-items: center;
          min-height: 100vh;
          margin: 0;
          padding: 0;
        }


        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
            box-shadow: var(--box-shadow);
            z-index: 1000;
        }

        .sidebar h2 {
            color: white;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
            text-align: center;
        }

        .sidebar h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--secondary-color);
            transform: translateX(-50%);
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            transition: var(--transition);
            border-radius: var(--border-radius);
            margin: 5px 10px;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: var(--primary-hover);
            transform: translateX(5px);
        }

        .sidebar .active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--secondary-color);
        }

        .coming-soon-container {
         margin-left: 260px; /* Adjust to ensure space from the sidebar */
         text-align: center;
         padding: 2rem;
         background: white;
         border-radius: var(--border-radius);
         box-shadow: var(--box-shadow);
         max-width: 600px;
         width: 100%;
        }


        .coming-soon-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .coming-soon-text {
            font-size: 1.2rem;
            color: var(--light-text);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
    </style>
</head>
<body>  
    <!-- Coming Soon Content -->
    <div class="coming-soon-container">
        <h1 class="coming-soon-title">Coming Soon!</h1>
        <p class="coming-soon-text">
            We're working hard to bring you something amazing. Stay tuned!
        </p>
        <a href="teacher_dashboard.php" class="btn btn-primary">Back to Home</a>
    </div>
</body>

