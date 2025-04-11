<?php
session_start();

// Check if the user is not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Redirect to the login page or another page
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);


// Database Connection
include ('config.php');
include ('admin_sidebar.php');

$showModal = false;
$modalType = '';
$modalMessage = '';
$teacherId = null;

// Check if teacher ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_teachers.php");
    exit();
}

$teacherId = $_GET['id'];

// Fetch teacher data
$sql = "SELECT * FROM Teacher WHERE TeacherID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    header("Location: view_teachers.php?error=Teacher not found");
    exit();
}

// Fetch linked class for this teacher
$class_query = "SELECT c.ClassID, c.ClassName 
                FROM TeacherClass tc 
                JOIN Class c ON tc.ClassID = c.ClassID 
                WHERE tc.TeacherID = ?";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param("i", $teacherId);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$teacherClass = $class_result->fetch_assoc();

if (isset($_POST['submit'])) {
    // Sanitize and fetch form inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $surname = htmlspecialchars(trim($_POST['surname']));
    $dob = htmlspecialchars(trim($_POST['dob']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $address = htmlspecialchars(trim($_POST['address']));
    $medical_info = htmlspecialchars(trim($_POST['medical_info']));
    $email_personal = htmlspecialchars(trim($_POST['email_personal']));
    $email_school = htmlspecialchars(trim($_POST['email_school']));
    $phone = htmlspecialchars(trim($_POST['phone_number']));
    $class_id = htmlspecialchars(trim($_POST['class_id']));
    
    // Password fields - only process if they're not empty
    $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;
    $confirm_password = isset($_POST['password_confirm']) && !empty($_POST['password_confirm']) ? $_POST['password_confirm'] : null;
    
    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($dob)) $errors[] = "Date of Birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($medical_info)) $errors[] = "Medical information is required";
    if (empty($email_personal)) {
        $errors[] = "Personal email is required";
    } elseif (!filter_var($email_personal, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid personal email format";
    }
    if (empty($email_school)) {
        $errors[] = "School email is required";
    } elseif (!filter_var($email_school, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid school email format";
    }
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($class_id)) $errors[] = "Class is required";

    // Password validation (only if password is being updated)
    if ($password !== null) {
        $password_errors = [];
        if (!preg_match('/[A-Z]/', $password)) $password_errors[] = "Include at least one uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $password_errors[] = "Include at least one lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $password_errors[] = "Include at least one number";
        if (!preg_match('/[@$!%*?&]/', $password)) $password_errors[] = "Include at least one special character (@$!%*?&)";
        if (strlen($password) < 8) $password_errors[] = "At least 8 characters long";

        if (!empty($password_errors)) $errors = array_merge($errors, $password_errors);
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Check if updating school email conflicts with another teacher
            if ($email_school != $teacher['email_school']) {
                $check_email_stmt = $conn->prepare("SELECT TeacherID FROM Teacher WHERE email_school = ? AND TeacherID != ?");
                $check_email_stmt->bind_param("si", $email_school, $teacherId);
                $check_email_stmt->execute();
                $email_result = $check_email_stmt->get_result();
                
                if ($email_result->num_rows > 0) {
                    throw new Exception("School email is already assigned to another teacher.");
                }
            }
            
            // Check if updating personal email conflicts with another teacher
            if ($email_personal != $teacher['email_personal']) {
                $check_personal_stmt = $conn->prepare("SELECT TeacherID FROM Teacher WHERE email_personal = ? AND TeacherID != ?");
                $check_personal_stmt->bind_param("si", $email_personal, $teacherId);
                $check_personal_stmt->execute();
                $personal_result = $check_personal_stmt->get_result();
                
                if ($personal_result->num_rows > 0) {
                    throw new Exception("Personal email is already assigned to another teacher.");
                }
            }
            
            // Build update query based on whether password is being updated
            if ($password !== null) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE Teacher SET 
                    Name = ?, 
                    Surname = ?, 
                    Dob = ?, 
                    Gender = ?, 
                    Address = ?, 
                    MedicalInfo = ?, 
                    email_personal = ?, 
                    email_school = ?, 
                    PhoneNumber = ?,
                    password_hash = ?
                    WHERE TeacherID = ?");
                    
                $update_stmt->bind_param("ssssssssssi", 
                    $name, $surname, $dob, $gender, $address, 
                    $medical_info, $email_personal, $email_school, 
                    $phone, $hashed_password, $teacherId);
            } else {
                $update_stmt = $conn->prepare("UPDATE Teacher SET 
                    Name = ?, 
                    Surname = ?, 
                    Dob = ?, 
                    Gender = ?, 
                    Address = ?, 
                    MedicalInfo = ?, 
                    email_personal = ?, 
                    email_school = ?, 
                    PhoneNumber = ?
                    WHERE TeacherID = ?");
                    
                $update_stmt->bind_param("sssssssssi", 
                    $name, $surname, $dob, $gender, $address, 
                    $medical_info, $email_personal, $email_school, 
                    $phone, $teacherId);
            }
                
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update teacher: " . $update_stmt->error);
            }
            
            // Update class assignment if it has changed
            if ($teacherClass && $teacherClass['ClassID'] != $class_id) {
                // Delete current class assignment
                $delete_class_stmt = $conn->prepare("DELETE FROM TeacherClass WHERE TeacherID = ?");
                $delete_class_stmt->bind_param("i", $teacherId);
                
                if (!$delete_class_stmt->execute()) {
                    throw new Exception("Failed to update class assignment: " . $delete_class_stmt->error);
                }
                
                // Insert new class assignment
                $insert_class_stmt = $conn->prepare("INSERT INTO TeacherClass (TeacherID, ClassID) VALUES (?, ?)");
                $insert_class_stmt->bind_param("ii", $teacherId, $class_id);
                
                if (!$insert_class_stmt->execute()) {
                    throw new Exception("Failed to assign new class: " . $insert_class_stmt->error);
                }
            } elseif (!$teacherClass) {
                // If teacher doesn't have a class assignment yet, add one
                $insert_class_stmt = $conn->prepare("INSERT INTO TeacherClass (TeacherID, ClassID) VALUES (?, ?)");
                $insert_class_stmt->bind_param("ii", $teacherId, $class_id);
                
                if (!$insert_class_stmt->execute()) {
                    throw new Exception("Failed to assign class: " . $insert_class_stmt->error);
                }
            }
            
            $conn->commit();
            
            $showModal = true;
            $modalType = 'success';
            $modalMessage = "Teacher information updated successfully.";
            
            // Refresh teacher data
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher = $result->fetch_assoc();
            
            // Refresh class data
            $class_stmt->execute();
            $class_result = $class_stmt->get_result();
            $teacherClass = $class_result->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $showModal = true;
            $modalType = 'error';
            $modalMessage = "Error updating teacher: " . $e->getMessage();
        }
    } else {
        $showModal = true;
        $modalType = 'validation';
        $modalMessage = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher</title>
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

        /* Form card styling */
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

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        /* School email styling */
        .school-email-field {
            background-color: #f8f9fa;
            color: #495057;
        }

        /* Password requirements */
        .password-requirements {
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .requirement {
            margin-bottom: 3px;
            color: #6c757d;
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        .requirement.met i {
            color: #28a745;
        }
        
        .requirement.not-met {
            color: #dc3545;
        }
        
        .requirement.not-met i {
            color: #dc3545;
        }
        
        /* Modal styling */
        .modal {
            display: block;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }
        
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .modal-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
        }
        
        .modal-icon.success {
            background-color: #d4edda;
            color: #28a745;
        }
        
        .modal-icon.error {
            background-color: #f8d7da;
            color: #dc3545;
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
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h3>Edit Teacher Information</h3>
            <p>Update the details below for <?= htmlspecialchars($teacher['Name'] . ' ' . $teacher['Surname']) ?></p>
            
            <div class="form-card">
                <form action="edit_teacher.php?id=<?= $teacherId ?>" method="POST" id="teacherForm">
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="male" 
                                    value="Male" <?= ($teacher['Gender'] == 'Male') ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="female" 
                                    value="Female" <?= ($teacher['Gender'] == 'Female') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" 
                                    value="Other" <?= ($teacher['Gender'] == 'Other') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="other">Other</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                            value="<?= htmlspecialchars($teacher['Name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="surname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="surname" name="surname" 
                            value="<?= htmlspecialchars($teacher['Surname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" 
                            value="<?= htmlspecialchars($teacher['Dob']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                            value="<?= htmlspecialchars($teacher['Address']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medical_info" class="form-label">Medical Information</label>
                        <input type="text" class="form-control" id="medical_info" name="medical_info" 
                            value="<?= htmlspecialchars($teacher['MedicalInfo']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_personal" class="form-label">Personal Email</label>
                        <input type="email" class="form-control" id="email_personal" name="email_personal" 
                            value="<?= htmlspecialchars($teacher['email_personal']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email_school" class="form-label">School Email</label>
                        <input type="email" class="form-control school-email-field" id="email_school" name="email_school" 
                            value="<?= htmlspecialchars($teacher['email_school']) ?>" readonly>
                        <small class="form-text text-muted">School email is automatically generated</small>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                            value="<?= htmlspecialchars($teacher['PhoneNumber']) ?>" required 
                            pattern="[0-9]{10}" title="Please enter a 10-digit phone number">
                        <small class="form-text text-muted">(e.g., 7143256571)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Assign Class</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">Select Class</option>
                            <option value="200" <?= ($teacherClass && $teacherClass['ClassID'] == 200) ? 'selected' : '' ?>>Reception</option>
                            <option value="201" <?= ($teacherClass && $teacherClass['ClassID'] == 201) ? 'selected' : '' ?>>Year 1</option>
                            <option value="202" <?= ($teacherClass && $teacherClass['ClassID'] == 202) ? 'selected' : '' ?>>Year 2</option>
                            <option value="203" <?= ($teacherClass && $teacherClass['ClassID'] == 203) ? 'selected' : '' ?>>Year 3</option>
                            <option value="204" <?= ($teacherClass && $teacherClass['ClassID'] == 204) ? 'selected' : '' ?>>Year 4</option>
                            <option value="205" <?= ($teacherClass && $teacherClass['ClassID'] == 205) ? 'selected' : '' ?>>Year 5</option>
                            <option value="206" <?= ($teacherClass && $teacherClass['ClassID'] == 206) ? 'selected' : '' ?>>Year 6</option>
                            <option value="207" <?= ($teacherClass && $teacherClass['ClassID'] == 207) ? 'selected' : '' ?>>Year 7</option>
                        </select>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Update Password (Optional)</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Leave blank to keep the current password</p>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="password-requirements mt-2">
                                    <div class="requirement not-met" id="length"><i class="fas fa-times"></i> At least 8 characters</div>
                                    <div class="requirement not-met" id="uppercase"><i class="fas fa-times"></i> At least one uppercase letter (A-Z)</div>
                                    <div class="requirement not-met" id="lowercase"><i class="fas fa-times"></i> At least one lowercase letter (a-z)</div>
                                    <div class="requirement not-met" id="number"><i class="fas fa-times"></i> At least one number (0-9)</div>
                                    <div class="requirement not-met" id="special"><i class="fas fa-times"></i> At least one special character (@$!%*?&)</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                                <div id="password-match" class="mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-sm-6 mb-2">
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Teacher
                            </button>
                        </div>
                    
                        <div class="col-sm-6 mb-2">
                            <a href="view_teachers.php" class="btn btn-secondary w-100">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal handling -->
    <?php if ($showModal): ?>
        <!-- Success Modal -->
        <?php if ($modalType === 'success'): ?>
        <div class="modal fade show" id="teacherModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_teacher.php?id=<?= $teacherId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon success">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4>Update Successful</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center mt-3">
                            <a href="edit_teacher.php?id=<?= $teacherId ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Continue Editing
                            </a>
                            <a href="view_teachers.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> View Teachers
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Modal -->
        <?php elseif ($modalType === 'error' || $modalType === 'validation'): ?>
        <div class="modal fade show" id="teacherModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_teacher.php?id=<?= $teacherId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon error">
                            <i class="fas fa-times"></i>
                        </div>
                        <h4>Update Error</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center mt-3">
                            <button type="button" class="btn btn-danger" onclick="window.location.href='edit_teacher.php?id=<?= $teacherId ?>'">
                                <i class="fas fa-edit me-1"></i> Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password validation
        $(document).ready(function() {
            $('#password').on('input', function() {
                const password = $(this).val();
                
                // Check length
                if (password.length >= 8) {
                    $('#length').removeClass('not-met').addClass('met').html('<i class="fas fa-check"></i> At least 8 characters');
                } else {
                    $('#length').removeClass('met').addClass('not-met').html('<i class="fas fa-times"></i> At least 8 characters');
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    $('#uppercase').removeClass('not-met').addClass('met').html('<i class="fas fa-check"></i> At least one uppercase letter (A-Z)');
                } else {
                    $('#uppercase').removeClass('met').addClass('not-met').html('<i class="fas fa-times"></i> At least one uppercase letter (A-Z)');
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    $('#lowercase').removeClass('not-met').addClass('met').html('<i class="fas fa-check"></i> At least one lowercase letter (a-z)');
                } else {
                    $('#lowercase').removeClass('met').addClass('not-met').html('<i class="fas fa-times"></i> At least one lowercase letter (a-z)');
                }
                
                // Check number
                if (/[0-9]/.test(password)) {
                    $('#number').removeClass('not-met').addClass('met').html('<i class="fas fa-check"></i> At least one number (0-9)');
                } else {
                    $('#number').removeClass('met').addClass('not-met').html('<i class="fas fa-times"></i> At least one number (0-9)');
                }
                
                // Check special character
                if (/[@$!%*?&]/.test(password)) {
                    $('#special').removeClass('not-met').addClass('met').html('<i class="fas fa-check"></i> At least one special character (@$!%*?&)');
                } else {
                    $('#special').removeClass('met').addClass('not-met').html('<i class="fas fa-times"></i> At least one special character (@$!%*?&)');
                }
            });
            
            // Password match validation
            $('#password_confirm').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $(this).val();
                
                if (password && confirmPassword) {
                    if (password === confirmPassword) {
                        $('#password-match').html('<span class="text-success"><i class="fas fa-check"></i> Passwords match</span>');
                    } else {
                        $('#password-match').html('<span class="text-danger"><i class="fas fa-times"></i> Passwords do not match</span>');
                    }
                } else {
                    $('#password-match').html('');
                }
            });
            
            // Form validation
            $('#teacherForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#password_confirm').val();
                
                if (password || confirmPassword) {
                    // If password is being changed, validate it
                    if (password.length < 8) {
                        alert('Password must be at least 8 characters long');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!/[A-Z]/.test(password)) {
                        alert('Password must contain at least one uppercase letter');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!/[a-z]/.test(password)) {
                        alert('Password must contain at least one lowercase letter');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!/[0-9]/.test(password)) {
                        alert('Password must contain at least one number');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!/[@$!%*?&]/.test(password)) {
                        alert('Password must contain at least one special character (@$!%*?&)');
                        e.preventDefault();
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        alert('Passwords do not match');
                        e.preventDefault();
                        return;
                    }
                }
            });
            
            // Close modal handler
            $('.modal').click(function(e) {
                if (e.target === this) {
                    window.location.href = 'edit_teacher.php?id=<?= $teacherId ?>';
                }
            });
        });
    </script>
</body>
</html>