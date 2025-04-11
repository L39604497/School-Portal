<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Database Connection and Sidebar
include ('config.php');
include ('admin_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

        /* Base styles */
        body {
          font-family: 'Poppins', sans-serif;
          background-color: #f5f7fb;
          color: var(--dark-text);
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

        /* Main content area */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .main-content h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .main-content h3:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--secondary-color);
        }

        /* Card styles */
        .card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            cursor: pointer;
            transition: var(--transition);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: none;
            padding: 1rem;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .card-body {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 1.5rem;
            padding: 1.5rem;
        }

        /* Increase size of the icon */
        .card i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        /* List group styling */
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 1rem;
            transition: var(--transition);
        }

        .list-group-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        /* Button styling */
        .btn {
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }

        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-success:hover {
            background-color: #3d8b40;
            border-color: #3d8b40;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-danger {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-danger:hover {
            background-color: #d61c7b;
            border-color: #d61c7b;
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.4);
        }

        /* Custom card backgrounds */
        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-purple {
            background-color: #6a0dad !important; /* A vibrant purple color */
        }

        .bg-success {
            background-color: #4CAF50 !important;
        }

        .bg-warning {
            background-color: #FF9800 !important;
        }

        /* Hover effect on cards */
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        /* Responsive layout */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
                padding-bottom: 20px;
            }

            .sidebar a {
                padding: 12px;
                margin: 5px;
            }

            .card i {
                font-size: 3rem;
            }
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(-20px);
            animation: slideIn 0.3s forwards;
        }

        .toast.success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .toast.error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>


<!-- Main Content Area -->
<div class="main-content">
    <div class="container" id="main-content">
        <h3>Welcome to the Admin Dashboard</h3>
        <p>Select a section from the sidebar to manage your content.</p>


        <!-- Visual Representation of Total Students and Teachers -->
        <div class="row mt-4">
            <!-- Total Pupils Card -->
            <div class="col-md-4">
                <a href="view_pupils.php" class="text-decoration-none">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <h5 class="card-title">Total Pupils</h5>
                            <p class="card-text">
                                <?php
                                // Get the total number of pupils from the database
                                $result = $conn->query("SELECT COUNT(*) AS total_pupils FROM Pupil");
                                $row = $result->fetch_assoc();
                                echo $row['total_pupils'];
                                ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Teachers Card -->
            <div class="col-md-4">
                <a href="view_teachers.php" class="text-decoration-none">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h5 class="card-title">Total Teachers</h5>
                            <p class="card-text">
                                <?php
                                // Get the total number of teachers from the database
                                $result = $conn->query("SELECT COUNT(*) AS total_teachers FROM Teacher");
                                $row = $result->fetch_assoc();
                                echo $row['total_teachers'];
                                ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            
            
            <!-- Total Teachers Card -->
             <div class="col-md-4">
                <a href="view_parents.php" class="text-decoration-none">
                    <div class="card text-white" style="background-color: #6c757d;"> <!-- grey background -->
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <h5 class="card-title">Total Parents</h5>
                            <p class="card-text">
                                <?php
                                // Get the total number of parents from the database
                                $result = $conn->query("SELECT COUNT(*) AS total_parents FROM Parent");
                                $row = $result->fetch_assoc();
                                echo $row['total_parents'];
                                ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>



        <!-- Recent Activities or Notifications Panel -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Activities</h5>
                    </div>
                    <div class="card-body" style="text-align: left;">
                        <ul class="list-group">
                            <li class="list-group-item">New pupil registered: John Doe</li>
                            <li class="list-group-item">Teacher assignment updated: Mr. Smith</li>
                            <li class="list-group-item">System update completed successfully.</li>
                            <li class="list-group-item">New message from support team.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="row mt-4">
            <div class="col-md-4">
                <a href="manage_classes.php" class="btn btn-danger w-100">Manage All Classes</a>
            </div>
            <div class="col-md-4">
                <a href="register_teacher.php" class="btn btn-primary w-100">Add New Teacher</a>
            </div>
            <div class="col-md-4">
                <a href="register_pupil.php" class="btn btn-success w-100">Add New Pupil</a>
            </div>
        </div>

        <!-- Upcoming Events or Tasks -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Upcoming Events</h5>
                    </div>
                    <div class="card-body" style="text-align: left;">
                        <ul class="list-group">
                            <li class="list-group-item">Teacher's Meeting: March 15, 2025</li>
                            <li class="list-group-item">Pupil Enrollment Deadline: April 1, 2025</li>
                            <li class="list-group-item">System Maintenance: May 10, 2025</li>
                            <li class="list-group-item">New Pupil Orientation: June 5, 2025</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container"></div>

<!-- Bootstrap 5 JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>