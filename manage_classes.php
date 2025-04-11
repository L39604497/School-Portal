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
include ('config.php');
include ('admin_sidebar.php');

// Function to get the Total Teachers from the school
function getTotalTeachers($conn) {
    // Use COUNT(DISTINCT) if there might be duplicates
    $sql = "SELECT COUNT(DISTINCT TeacherID) as total FROM Teacher";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
  }

// Function to get class details with teacher and student count
function getClassDetails($conn) {
    $sql = "SELECT c.ClassID, c.ClassName, 
            COUNT(DISTINCT tc.TeacherID) as TeacherCount, 
            COUNT(DISTINCT p.PupilID) as StudentCount
            FROM Class c
            LEFT JOIN teacherclass tc ON c.ClassID = tc.ClassID
            LEFT JOIN Pupil p ON c.ClassID = p.ClassID
            GROUP BY c.ClassID, c.ClassName
            ORDER BY c.ClassName";
            
    $result = $conn->query($sql);
    $classes = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    
    return $classes;
}

function getClassById($conn, $classId) {
    // Get class info
    $sql = "SELECT * FROM Class WHERE ClassID = $classId";
    $result = $conn->query($sql);
    $class = $result->fetch_assoc();
    
    // Get teachers assigned to this class
    $sql = "SELECT t.TeacherID, t.Name, t.Surname 
            FROM teacher t
            JOIN teacherclass tc ON t.TeacherID = tc.TeacherID
            WHERE tc.ClassID = $classId";
    $result = $conn->query($sql);
    $teachers = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
    }
    
    
    // Get students in this class
    $sql = "SELECT PupilID, Name, Surname, DateOfBirth, Gender, Status 
            FROM Pupil 
            WHERE ClassID = $classId
            ORDER BY Surname, Name";
    $result = $conn->query($sql);
    $students = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    return [
        'class' => $class,
        'teachers' => $teachers,
        'students' => $students,
        'student_count' => count($students)
    ];
}

// Handle the request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Process actions
$message = '';

// Get total teachers count here - add this line
$totalTeachers = getTotalTeachers($conn);

// Main logic
if ($action == 'view' && $classId > 0) {
    $classDetails = getClassById($conn, $classId);
} else {
    $classes = getClassDetails($conn);
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>

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
            padding: 0.5rem 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
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

        /* Table styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border: none;
            padding: 1rem;
        }

        .table tbody tr:nth-child(even) {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
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
        
        /* Make action buttons smaller */
        .table .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            line-height: 1.2;
        }

        /* Reduce icon size in buttons */
        .table .btn-sm i {
            font-size: 0.8rem;
        }

        /* Make buttons in table cells more compact */
        .table td .btn {
            margin-right: 2px;
            margin-bottom: 2px;
        }
        
        /* Class capacity indicator */
        .capacity-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .capacity-full {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .capacity-available {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>

<!-- Main Content Area -->
<div class="main-content">
    <div class="container" id="main-content">
        <?php if ($action == 'view' && isset($classDetails)): ?>
            <!-- Class Details View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Class Details: <?php echo htmlspecialchars($classDetails['class']['ClassName']); ?></h3>
                <div>
                    <a href="edit_class.php?class_id=<?php echo $classId; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-2"></i> Edit Class
                    </a>
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Classes
                    </a>
                </div>
            </div>
            
            <!-- Class Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle me-2"></i> Class Information</h5>
                </div>
                <div class="card-body" style="text-align: left;">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Class ID:</strong> <?php echo $classDetails['class']['ClassID']; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Class Name:</strong> <?php echo htmlspecialchars($classDetails['class']['ClassName']); ?></p>
                        </div>
                        <div class="col-md-4">
                        <p>
                            <strong>Number of Students:</strong> 
                            <?php echo $classDetails['student_count']; ?> / 10
                            <?php if ($classDetails['student_count'] >= 10): ?>
                                <span class="capacity-indicator capacity-full">FULL</span>
                                <?php else: ?>
                                    <span class="capacity-indicator capacity-available">
                                        <?php echo 10 - $classDetails['student_count']; ?> SPACES LEFT
                                    </span>
                                    <?php endif; ?>
                                </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teachers Assigned Card -->
            <div class="card mb-4">
              <div class="card-header">
                <h5><i class="fas fa-chalkboard-teacher me-2"></i> Teachers Assigned 
                <small class="text-muted">(Total Teachers in School: <?php echo $totalTeachers; ?>)</small>
              </h5>
            </div>
            <div class="card-body">
                <?php if (count($classDetails['teachers']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classDetails['teachers'] as $teacher): ?>
                                    <tr>
                                        <td><?php echo $teacher['TeacherID']; ?></td>
                                        <td><?php echo htmlspecialchars($teacher['Name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['Surname']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No teachers assigned to this class.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>


            
            <!-- Students Enrolled Card -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users me-2"></i> Students Enrolled</h5>
                </div>
                <div class="card-body">
                    <?php if (count($classDetails['students']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Surname</th>
                                        <th>Date of Birth</th>
                                        <th>Gender</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classDetails['students'] as $student): ?>
                                        <tr>
                                            <td><?php echo $student['PupilID']; ?></td>
                                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['Surname']); ?></td>
                                            <td><?php echo $student['DateOfBirth']; ?></td>
                                            <td><?php echo htmlspecialchars($student['Gender']); ?></td>
                                            <td>
                                                <?php if ($student['Status'] == 'Active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($student['Status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No students enrolled in this class.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Classes List View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Manage Classes</h3>
            </div>
            
            <!-- Class Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <i class="fas fa-school"></i>
                            <h5 class="card-title">Total Classes</h5>
                            <p class="card-text">
                                <?php echo count($classes); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <h5 class="card-title">Total Students</h5>
                            <p class="card-text">
                                <?php
                                $totalStudents = 0;
                                foreach ($classes as $class) {
                                    $totalStudents += $class['StudentCount'];
                                }
                                echo $totalStudents;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-purple text-white">
                        <div class="card-body">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h5 class="card-title">Teacher Assignments</h5>
                            <p class="card-text">
                                <?php
                                $totalTeachers = 0;
                                foreach ($classes as $class) {
                                    $totalTeachers += $class['TeacherCount'];
                                }
                                echo $totalTeachers;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Classes Table -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i> Available Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($classes) && count($classes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class ID</th>
                                        <th>Class Name</th>
                                        <th>Teachers</th>
                                        <th>Students</th>
                                        <th>Capacity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo $class['ClassID']; ?></td>
                                            <td><?php echo htmlspecialchars($class['ClassName']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $class['TeacherCount']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $class['StudentCount']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($class['StudentCount'] >= 10): ?>
                                                    <span class="capacity-indicator capacity-full">FULL</span>
                                                    <?php else: ?>
                                                        <span class="capacity-indicator capacity-available">
                                                            <?php echo 10 - $class['StudentCount']; ?> SPACES
                                                        </span>
                                                        <?php endif; ?>
                                                    </td>
                                            <td>
                                                <a href="view_class.php?class_id=<?php echo $class['ClassID']; ?>" class="btn btn-sm btn-primary mb-1">
                                                    <i class="fas fa-eye me-1"></i> View Class
                                                </a>
                                                <a href="edit_class.php?class_id=<?php echo $class['ClassID']; ?>" class="btn btn-sm btn-warning mb-1">
                                                    <i class="fas fa-edit me-1"></i> Edit Class
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No classes found. Please add a class to get started.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="toast-container"></div>

<!-- Bootstrap 5 JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>