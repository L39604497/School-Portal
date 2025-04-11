<?php
session_start();

// Checks if the user is not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Redirects to the login page or another page
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection and Sidebar
include ('config.php');
include ('admin_sidebar.php');

// Functions to get class details by ID
function getClassById($conn, $classId) {
    // Get class info
    $sql = "SELECT * FROM Class WHERE ClassID = $classId";
    $result = $conn->query($sql);
    $class = $result->fetch_assoc();
    
    return $class;
}

// Functions to get all teachers
function getAllTeachers($conn) {
    $sql = "SELECT TeacherID, Name, Surname FROM Teacher ORDER BY Surname, Name";
    $result = $conn->query($sql);
    $teachers = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
    }
    
    return $teachers;
}

// Functions to get teachers assigned to a class
function getAssignedTeachers($conn, $classId) {
    $sql = "SELECT TeacherID FROM teacherclass WHERE ClassID = $classId";
    $result = $conn->query($sql);
    $assignedTeachers = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $assignedTeachers[] = $row['TeacherID'];
        }
    }
    
    return $assignedTeachers;
}

// Checks if class_id is set
if(!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    // Redirect if no class_id provided
    header("location: manage_classes.php");
    exit;
}

$classId = (int)$_GET['class_id'];
$class = getClassById($conn, $classId);

// If class doesn't exist, redirects
if(!$class) {
    $_SESSION['error'] = "Class not found.";
    header("location: manage_classes.php");
    exit;
}

$allTeachers = getAllTeachers($conn);
$assignedTeachers = getAssignedTeachers($conn, $classId);

// Handles form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $className = trim($_POST['className']);
    $selectedTeachers = isset($_POST['teachers']) ? $_POST['teachers'] : [];
    
    // Validates className
    if(empty($className)) {
        $error = "Class name cannot be empty.";
    } else {
        // Checks if class name already exists for a different class
        $checkSql = "SELECT ClassID FROM Class WHERE ClassName = ? AND ClassID != ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("si", $className, $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = "A class with this name already exists.";
        } else {

            // Begins transaction
            $conn->begin_transaction();
            
            try {
                // Updates Class name
                $updateSql = "UPDATE Class SET ClassName = ? WHERE ClassID = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("si", $className, $classId);
                $stmt->execute();
                
                // Deletes existing teacher assignments
                $deleteSql = "DELETE FROM teacherclass WHERE ClassID = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->bind_param("i", $classId);
                $stmt->execute();
                
                // Inserts new teacher assignments
                if(!empty($selectedTeachers)) {
                    $insertSql = "INSERT INTO teacherclass (ClassID, TeacherID) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertSql);
                    
                    foreach($selectedTeachers as $teacherId) {
                        $stmt->bind_param("ii", $classId, $teacherId);
                        $stmt->execute();
                    }
                }
                
                // Commits transaction
                $conn->commit();
                
                // Success message
                $_SESSION['success'] = "Class updated successfully.";
                
                // Redirects back to view class
                header("location: view_class.php?class_id=$classId");
                exit;
                
            } catch (Exception $e) {

                // Error message
                $conn->rollback();
                $error = "Error updating class: " . $e->getMessage();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Select CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

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
            padding: 1.5rem;
        }

        /* Hover effect on cards */
        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        /* Form styling */
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
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

        .btn-warning {
            background-color: #FF9800;
            border-color: #FF9800;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e68a00;
            border-color: #e68a00;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
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
        
        /* Select2 custom styling */
        .bootstrap-select .dropdown-toggle {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid #ced4da;
        }
        
        .bootstrap-select .dropdown-toggle:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .bootstrap-select .dropdown-menu {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 0.5rem;
        }
        
        .bootstrap-select .dropdown-item {
            border-radius: var(--border-radius);
            padding: 0.5rem;
        }
        
        .bootstrap-select .dropdown-item.active, 
        .bootstrap-select .dropdown-item:active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>

<!-- Main Content Area -->
<div class="main-content">
    <div class="container" id="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Edit Class: <?php echo htmlspecialchars($class['ClassName']); ?></h3>
            <a href="view_class.php?class_id=<?php echo $classId; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Class Details
            </a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i> Edit Class Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="className" name="className" value="<?php echo htmlspecialchars($class['ClassName']); ?>" required>
                        <div class="form-text">Enter the unique name for this class.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="teachers" class="form-label">Assign Teachers</label>
                        <select class="form-select selectpicker" id="teachers" name="teachers[]" multiple data-live-search="true" title="Select teachers to assign">
                            <?php foreach($allTeachers as $teacher): ?>
                                <option value="<?php echo $teacher['TeacherID']; ?>" 
                                    <?php echo in_array($teacher['TeacherID'], $assignedTeachers) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['Surname'] . ', ' . $teacher['Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Select one or more teachers to assign to this class.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <a href="view_class.php?class_id=<?php echo $classId; ?>" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="toast-container"></div>

<!-- Bootstrap 5 JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Select JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Bootstrap Select
        $('.selectpicker').selectpicker();
        
        // Display toast messages if they exist
        <?php if(isset($_SESSION['success'])): ?>
            showToast("<?php echo $_SESSION['success']; ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            showToast("<?php echo $_SESSION['error']; ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    });
    
    // Function to show toast notifications
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<div class="me-2"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i></div><div>${message}</div>`;
        document.querySelector('.toast-container').appendChild(toast);
        
        // Auto-remove toast after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }
</script>

</body>
</html>