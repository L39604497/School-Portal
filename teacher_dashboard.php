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
    <title>Teacher Dashboard</title>

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
            background-color: #6a0dad !important;
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

        /* Table styling */
        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table thead {
            background-color: var(--primary-color);
            color: white;
        }

        .table thead th {
            border-bottom: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
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
        <?php
        // Get the teacher's name
        $teacherQuery = $conn->prepare("SELECT Name, Surname FROM Teacher WHERE TeacherID = ?");
        $teacherQuery->bind_param("i", $teacherID);
        $teacherQuery->execute();
        $teacherResult = $teacherQuery->get_result();
        $teacherData = $teacherResult->fetch_assoc();
        ?>
        <h3>Welcome, <?php echo $teacherData['Name'] . ' ' . $teacherData['Surname']; ?></h3>
        <p>View and manage your class and pupils from this dashboard.</p>

        <!-- Teacher's Classes Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Assigned Classes</h5>
                    </div>
                    <div class="card-body" style="text-align: left;">
                        <?php
                        // Get the teacher's assigned classes
                        $classQuery = $conn->prepare("
                            SELECT c.ClassID, c.ClassName 
                            FROM Class c
                            INNER JOIN teacherclass tc ON c.ClassID = tc.ClassID
                            WHERE tc.TeacherID = ?
                        ");
                        $classQuery->bind_param("i", $teacherID);
                        $classQuery->execute();
                        $classResult = $classQuery->get_result();

                        if ($classResult->num_rows > 0) {
                            echo '<div class="table-responsive">
                                  <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Class ID</th>
                                            <th>Class Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                            
                            while ($classRow = $classResult->fetch_assoc()) {
                                echo '<tr>
                                        <td>' . $classRow['ClassID'] . '</td>
                                        <td>' . $classRow['ClassName'] . '</td>
                                        <td>
                                            <a href="view_class_details.php?class_id=' . $classRow['ClassID'] . '" class="btn btn-sm btn-primary">View Details</a>
                                        </td>
                                      </tr>';
                            }
                            
                            echo '</tbody>
                                </table>
                             </div>';
                        } else {
                            echo '<div class="alert alert-info">You are not currently assigned to any classes.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Representation of Total Pupils in Teacher's Classes -->
        <div class="row mt-4">
            <?php
            // Reset the classes result
            $classQuery->execute();
            $classResult = $classQuery->get_result();
            
            // Prepare an array to store class IDs
            $classIds = [];
            while ($classRow = $classResult->fetch_assoc()) {
                $classIds[] = $classRow['ClassID'];
            }
            
            // If teacher has classes, show pupil count
            if (!empty($classIds)) {
                // Get total pupils count across all teacher's classes
                $totalPupilsQuery = $conn->prepare("
                    SELECT COUNT(*) AS total_pupils 
                    FROM Pupil 
                    WHERE ClassID IN (" . implode(',', array_fill(0, count($classIds), '?')) . ")
                ");
                
                // Bind parameters dynamically
                $types = str_repeat('i', count($classIds));
                $totalPupilsQuery->bind_param($types, ...$classIds);
                
                $totalPupilsQuery->execute();
                $totalPupilsResult = $totalPupilsQuery->get_result();
                $totalPupilsRow = $totalPupilsResult->fetch_assoc();
                $totalPupils = $totalPupilsRow['total_pupils'];
            ?>
            <!-- Total Pupils Card -->
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-users"></i>
                        <h5 class="card-title">Total Pupils</h5>
                        <p class="card-text"><?php echo $totalPupils; ?></p>
                    </div>
                </div>
            </div>

            <!-- Classes Card -->
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-chalkboard"></i>
                        <h5 class="card-title">Assigned Classes</h5>
                        <p class="card-text"><?php echo count($classIds); ?></p>
                    </div>
                </div>
            </div>
            <?php
            } else {
                // If no classes are assigned
                echo '<div class="col-md-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> You are not currently assigned any classes. Please contact the administrator.
                        </div>
                      </div>';
            }
            ?>
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
                            <li class="list-group-item">New system update available</li>
                            <li class="list-group-item">2 new pupils added to your class</li>
                            <li class="list-group-item">Upcoming parent-teacher meeting: May 15, 2025</li>
                            <li class="list-group-item">Staff meeting reminder: Next Monday at 3 PM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="row mt-4">
            <div class="col-md-4">
                <a href="view_pupils.php" class="btn btn-primary w-100">View All Pupils</a>
            </div>
            <div class="col-md-4">
                <a href="record_attendance.php" class="btn btn-success w-100">Record Attendance</a>
            </div>
            <div class="col-md-4">
                <a href="my_profile.php" class="btn btn-danger w-100">My Profile</a>
            </div>
        </div>

        <!-- Pupils List Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Pupils</h5>
                    </div>
                    <div class="card-body" style="text-align: left;">
                        <?php
                        // Only show if teacher has classes
                        if (!empty($classIds)) {
                            // Get pupils from all teacher's classes
                            $pupilsQuery = $conn->prepare("
                                SELECT PupilID, Name, Surname, ClassID 
                                FROM Pupil 
                                WHERE ClassID IN (" . implode(',', array_fill(0, count($classIds), '?')) . ")
                                ORDER BY Surname, Name
                                LIMIT 10
                            ");
                            
                            // Bind parameters dynamically
                            $types = str_repeat('i', count($classIds));
                            $pupilsQuery->bind_param($types, ...$classIds);
                            
                            $pupilsQuery->execute();
                            $pupilsResult = $pupilsQuery->get_result();

                            if ($pupilsResult->num_rows > 0) {
                                echo '<div class="table-responsive">
                                      <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Surname</th>
                                                <th>Class ID</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                                
                                while ($pupilRow = $pupilsResult->fetch_assoc()) {
                                    echo '<tr>
                                            <td>' . $pupilRow['PupilID'] . '</td>
                                            <td>' . $pupilRow['Name'] . '</td>
                                            <td>' . $pupilRow['Surname'] . '</td>
                                            <td>' . $pupilRow['ClassID'] . '</td>
                                            <td>
                                                <a href="coming_soon.php' . $pupilRow['PupilID'] . '" class="btn btn-sm btn-primary">View Details</a>
                                            </td>
                                          </tr>';
                                }
                                
                                echo '</tbody>
                                    </table>
                                </div>';
                                
                                if ($totalPupils > 10) {
                                    echo '<div class="text-center mt-3">
                                            <a href="view_pupils.php" class="btn btn-outline-primary">View All Pupils</a>
                                          </div>';
                                }
                            } else {
                                echo '<div class="alert alert-info">No pupils are currently assigned to your classes.</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
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
                            <li class="list-group-item">Staff Meeting: April 15, 2025</li>
                            <li class="list-group-item">Parent-Teacher Conference: April 20, 2025</li>
                            <li class="list-group-item">Field Trip: May 5, 2025</li>
                            <li class="list-group-item">End of Term Exams: June 10, 2025</li>
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