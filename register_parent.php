<?php
session_start();

// Check if the user is not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
require ('config.php');
require ('admin_sidebar.php');

// Enhanced connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify AUTO_INCREMENT is working
$check_auto_increment = $conn->query("SHOW COLUMNS FROM Parent WHERE Field = 'ParentID' AND Extra LIKE '%auto_increment%'");
if ($check_auto_increment->num_rows == 0) {
    die("Error: ParentID column is not set to AUTO_INCREMENT");
}

$showModal = false;
$modalType = '';
$modalMessage = '';
$parentId = null;

if (isset($_POST['submit'])) {
    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $surname = mysqli_real_escape_string($conn, trim($_POST['surname']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $dateOfBirth = mysqli_real_escape_string($conn, trim($_POST['date_of_birth']));
    $relationshipType = mysqli_real_escape_string($conn, trim($_POST['relationship_type']));
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, trim($_POST['gender'])) : '';
    $pupilID = isset($_POST['pupil_id']) && !empty($_POST['pupil_id']) ? (int)$_POST['pupil_id'] : null;

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($dateOfBirth)) $errors[] = "Date of Birth is required";
    if (empty($relationshipType)) $errors[] = "Relationship Type is required";
    if (empty($gender)) $errors[] = "Gender is required";

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Check if parent exists
            $check_stmt = $conn->prepare("SELECT ParentID FROM Parent WHERE Email = ?");
            if (!$check_stmt) throw new Exception("Prepare failed: " . $conn->error);
            
            $check_stmt->bind_param("s", $email);
            if (!$check_stmt->execute()) throw new Exception("Execute failed: " . $check_stmt->error);
            
            $check_result = $check_stmt->get_result();
            $parentExists = $check_result->num_rows > 0;
            
            if ($parentExists) {
                $row = $check_result->fetch_assoc();
                $parentId = $row['ParentID'];
            } else {
                // Insert new parent with debug info
                $insert_stmt = $conn->prepare("INSERT INTO Parent (Name, Surname, Email, Address, DateOfBirth, RelationshipType, Gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$insert_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $insert_stmt->bind_param("sssssss", $name, $surname, $email, $address, $dateOfBirth, $relationshipType, $gender);
                if (!$insert_stmt->execute()) throw new Exception("Execute failed: " . $insert_stmt->error);
                
                $parentId = $conn->insert_id;
                if ($parentId === 0) {
                    // Debugging: Check the last query and error
                    $debug_query = "SELECT LAST_INSERT_ID() as last_id";
                    $debug_result = $conn->query($debug_query);
                    $debug_row = $debug_result->fetch_assoc();
                    error_log("Debug - LAST_INSERT_ID: " . $debug_row['last_id']);
                    error_log("Debug - insert_id: " . $conn->insert_id);
                    error_log("Debug - error: " . $conn->error);
                    
                    throw new Exception("Failed to get valid ParentID after insertion");
                }
                $insert_stmt->close();
            }
            
            // Link to pupil if provided
            if ($pupilID && $parentId) {
                // Check existing links
                $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM PupilParent WHERE PupilID = ?");
                if (!$count_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $count_stmt->bind_param("i", $pupilID);
                if (!$count_stmt->execute()) throw new Exception("Execute failed: " . $count_stmt->error);
                
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                
                if ($count_row['count'] >= 2) {
                    throw new Exception("Pupil already has maximum parents/guardians");
                }
                
                // Insert relationship
                $link_stmt = $conn->prepare("INSERT INTO PupilParent (PupilID, ParentID, RelationshipType) VALUES (?, ?, ?)");
                if (!$link_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $link_stmt->bind_param("iis", $pupilID, $parentId, $relationshipType);
                if (!$link_stmt->execute()) throw new Exception("Execute failed: " . $link_stmt->error);
                $link_stmt->close();
            }
            
            $conn->commit();
            
            $showModal = true;
            $modalType = 'success';
            $modalMessage = $parentExists ? 
                "Parent already exists" . ($pupilID ? " and was linked to pupil" : "") :
                "Parent registered successfully" . ($pupilID ? " and linked to pupil" : "") . ". Parent ID: $parentId";
                
        } catch (Exception $e) {
            $conn->rollback();
            $showModal = true;
            $modalType = 'error';
            $modalMessage = "Database Error: " . $e->getMessage();
            error_log("Parent Registration Error: " . $e->getMessage());
        }
    } else {
        $showModal = true;
        $modalType = 'validation';
        $modalMessage = implode('<br>', $errors);
    }
}

// Get pupils for dropdown
$pupils_result = $conn->query("SELECT PupilID, Name, Surname FROM Pupil ORDER BY Surname, Name");
if (!$pupils_result) {
    error_log("Error fetching pupils: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Parent</title>
    
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
        
        /* Form styles */
        .form-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
            overflow: hidden;
            background-color: white;
            padding: 2rem;
        }
        
        .form-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
            text-align: center;
        }
        
        .form-title:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--secondary-color);
            transform: translateX(-50%);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .form-select {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
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
    
        /* Alert styling */
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: var(--box-shadow);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    
        /* Modal Styling */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        
        .modal-header .btn-close {
            position: absolute;
            top: 15px;
            right: 15px;
            box-shadow: none;
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 50%;
            padding: 0.5rem;
            transition: var(--transition);
        }
        
        .modal-header .btn-close:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        .modal-body {
            text-align: center;
            padding: 1.5rem 2rem 2rem;
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .modal-icon.success {
            background-color: rgba(76, 175, 80, 0.15);
            color: #4CAF50;
        }
        
        .modal-icon.error {
            background-color: rgba(244, 67, 54, 0.15);
            color: #F44336;
        }
        
        .modal-icon.warning {
            background-color: rgba(255, 152, 0, 0.15);
            color: #FF9800;
        }
        
        .modal-icon i {
            font-size: 40px;
        }
        
        .modal h4 {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .modal h4:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            height: 3px;
            width: 40px;
            background: var(--secondary-color);
            transform: translateX(-50%);
        }
        
        .modal p {
            color: var(--light-text);
            margin-bottom: 1.5rem;
        }
        
        .modal .btn {
            margin: 0 0.3rem;
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
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .modal .btn {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>


<!-- Main Content Area -->
<div class="main-content">
        <div class="container">
            <h3>Register a Parent</h3>
            <p>Fill in the details below to register a new parent.</p>
            
            <div class="form-card">
                <form action="register_parent.php" method="POST" id="parentForm">
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="Female">
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" value="Other">
                                <label class="form-check-label" for="other">Other</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="surname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="surname" name="surname" required>
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="date_of_birth" required>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" required 
                               pattern="[0-9]{10}" title="Please enter a 10-digit phone number">
                        <small class="form-text text-muted">(e.g., 7143256571)</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Relationship Type</label>
                        <div class="row">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="father" value="Father" required>
                                    <label class="form-check-label" for="father">Father</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="mother" value="Mother">
                                    <label class="form-check-label" for="mother">Mother</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="guardian" value="Guardian">
                                    <label class="form-check-label" for="guardian">Guardian</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add pupil selection dropdown -->
                    <div class="mb-3">
                        <label for="pupil_id" class="form-label">Link to Pupil (Optional)</label>
                        <select class="form-select" id="pupil_id" name="pupil_id">
                            <option value="">Select a pupil (optional)</option>
                            <?php
                            if ($pupils_result && $pupils_result->num_rows > 0) {
                                while ($row = $pupils_result->fetch_assoc()) {
                                    echo "<option value='" . $row['PupilID'] . "'>" . $row['Name'] . " " . $row['Surname'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus me-2"></i>Register & Link Parent
                            </button>
                        </div>
                    
                        <div class="col-sm-6 mb-2">
                            <a href="view_parents.php" class="btn btn-success w-100">
                                <i class="fas fa-users me-2"></i>View Parents
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals for Different Scenarios -->
    <?php if ($showModal): ?>
        <!-- Success Modal -->
        <?php if ($modalType === 'success'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='register_parent.php'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon success">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4>Parent Registered Successfully</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="register_parent.php" class="btn btn-primary me-2">
                                <i class="fas fa-user-plus me-1"></i> Register Another
                            </a>
                            <a href="view_parents.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> View Parents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Relation Modal -->
        <?php elseif ($modalType === 'existing_relation'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='register_parent.php'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon warning">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <h4>Parent Already Linked</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="register_parent.php" class="btn btn-primary me-2">
                                <i class="fas fa-redo me-1"></i> Try Again
                            </a>
                            <a href="view_parents.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> View Parents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Max Parents Modal -->
<?php elseif ($modalType === 'max_parents'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='register_parent.php'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Maximum Parents Reached</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="register_parent.php" class="btn btn-primary me-2">
                                <i class="fas fa-redo me-1"></i> Try Again
                            </a>
                            <a href="view_pupil_parents.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> Manage Pupil Parents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Modal -->
        <?php elseif ($modalType === 'error'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='register_parent.php'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon error">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h4>Error</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <a href="register_parent.php" class="btn btn-primary">
                            <i class="fas fa-redo me-1"></i> Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation Modal -->
        <?php elseif ($modalType === 'validation'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='register_parent.php'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon error">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h4>Validation Error</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <a href="register_parent.php" class="btn btn-primary">
                            <i class="fas fa-redo me-1"></i> Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap 5 JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Form validation using JavaScript
        document.getElementById('parentForm').addEventListener('submit', function(event) {
            let phoneNumber = document.getElementById('phone_number').value;
            let email = document.getElementById('email').value;
            
            // Validate phone number format
            if (!/^\d{10}$/.test(phoneNumber)) {
                alert('Phone number must be 10 digits without spaces or special characters');
                event.preventDefault();
                return false;
            }
            
            // Validate email format
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address');
                event.preventDefault();
                return false;
            }
            
            // Additional validation can be added here
            return true;
        });
        
        // Close modal functionality
        document.querySelectorAll('.btn-close, .modal-backdrop').forEach(function(element) {
            element.addEventListener('click', function() {
                document.querySelectorAll('.modal').forEach(function(modal) {
                    modal.style.display = 'none';
                });
            });
        });
        
        // Date of birth validation - prevent future dates
        document.getElementById('dob').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate > today) {
                alert('Date of birth cannot be in the future');
                this.value = '';
            }
        });
        
        // Set max date for date of birth to today
        const dobInput = document.getElementById('dob');
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        dobInput.setAttribute('max', formattedDate);
    </script>
</body>
</html>