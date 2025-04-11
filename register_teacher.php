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
require_once ('config.php');
require_once ('admin_sidebar.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$modalType = "";
$modalMessage = "";

// Check if the form is submitted
if (isset($_POST['submit'])) {
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
    $password = $_POST['password'];
    $confirm_password = $_POST['password_confirm'];

    $errors = [];

    // Password validation
    $password_errors = [];
    if (!preg_match('/[A-Z]/', $password)) $password_errors[] = "Include at least one uppercase letter";
    if (!preg_match('/[a-z]/', $password)) $password_errors[] = "Include at least one lowercase letter";
    if (!preg_match('/[0-9]/', $password)) $password_errors[] = "Include at least one number";
    if (!preg_match('/[@$!%*?&]/', $password)) $password_errors[] = "Include at least one special character (@$!%*?&)";
    if (strlen($password) < 8) $password_errors[] = "At least 8 characters long";

    if (!empty($password_errors)) $errors = array_merge($errors, $password_errors);
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    if (!filter_var($email_personal, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid personal email format";
    if (!filter_var($email_school, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid school email format";

    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT TeacherID FROM Teacher WHERE email_personal = ? OR email_school = ?");
        $check_stmt->bind_param("ss", $email_personal, $email_school);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $modalType = "warning";
            $modalMessage = "A teacher with one of these emails already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into the Teacher table
            $stmt = $conn->prepare("INSERT INTO Teacher (Name, Surname, Dob, Gender, Address, MedicalInfo, email_personal, email_school, PhoneNumber, ClassID, password_hash)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $name, $surname, $dob, $gender, $address, $medical_info, $email_personal, $email_school, $phone, $class_id, $hashed_password);

            if ($stmt->execute()) {
                $teacher_id = $stmt->insert_id; // Get the ID of the newly inserted teacher

                // Now insert the teacher and class relationship into the TeacherClass table
                $teacherClassStmt = $conn->prepare("INSERT INTO TeacherClass (TeacherID, ClassID) VALUES (?, ?)");
                $teacherClassStmt->bind_param("ii", $teacher_id, $class_id);
                if ($teacherClassStmt->execute()) {
                    $modalType = "success";
                    $modalMessage = "Teacher registered successfully and assigned to the class.";
                } else {
                    $modalType = "danger";
                    $modalMessage = "Error assigning teacher to class: " . $teacherClassStmt->error;
                }

                $teacherClassStmt->close();
            } else {
                $modalType = "danger";
                $modalMessage = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $modalType = "danger";
        $modalMessage = implode("<br>", $errors);
    }
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Teacher</title>
    
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        /* Password strength indicator */
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
        
        /* School email styling */
        .school-email-field {
            background-color: #f8f9fa;
            color: #495057;
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
        }
    </style>
</head>
<body>


<!-- Main Content Area -->
<div class="main-content">
    <div class="container">
        <h3>Register a Teacher</h3>
        <p>Fill in the details below to register a new teacher.</p>
        
        
        <div class="form-card">
            <form action="register_teacher.php" method="POST">

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
                <input type="date" class="form-control" id="dob" name="dob" required>
            </div>
                
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" required>
            </div>
                
            <div class="mb-3">
                <label for="medical_info" class="form-label">Medical Information</label>
                <input type="text" class="form-control" id="medical_info" name="medical_info" required>
            </div>
                
            <div class="mb-3">
                <label for="email_personal" class="form-label">Personal Email</label>
                <input type="email" class="form-control" id="email_personal" name="email_personal" required>
            </div>

            <div class="mb-3">
                <label for="email_school" class="form-label">School Email</label>
                <input type="email" class="form-control school-email-field" id="email_school" name="email_school" readonly>
                <small class="form-text text-muted">This will be automatically generated based on name and surname</small>
            </div>

            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone_number" name="phone_number" required 
                pattern="[0-9]{10}" title="Please enter a 10-digit phone number">
                <small class="form-text text-muted">(e.g., 7143256571)</small>
            </div>
                
            <div class="mb-3">
                <label for="class_id" class="form-label">Assign Class</label>
                <select class="form-select" id="class_id" name="class_id" required>
                    <option value="">Select Class</option>
                    <option value="200">Reception</option>
                    <option value="201">Year 1</option>
                    <option value="202">Year 2</option>
                    <option value="203">Year 3</option>
                    <option value="204">Year 4</option>
                    <option value="205">Year 5</option>
                    <option value="206">Year 6</option>
                    <option value="207">Year 7</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="password-requirements mt-2">
                    <div class="requirement not-met" id="length"><i class="fas fa-times"></i> At least 8 characters</div>
                    <div class="requirement not-met" id="uppercase"><i class="fas fa-times"></i> At least one uppercase letter (A-Z)</div>
                    <div class="requirement not-met" id="lowercase"><i class="fas fa-times"></i> At least one lowercase letter (a-z)</div>
                    <div class="requirement not-met" id="number"><i class="fas fa-times"></i> At least one number (0-9)</div>
                    <div class="requirement not-met" id="special"><i class="fas fa-times"></i> At least one special character (@$!%*?&)</div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                <div id="password-match" class="mt-1"></div>
            </div>
                
            <div class="row">
                <div class="col-sm-6 mb-2">
                    <button type="submit" name="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus me-2"></i>Register Teacher
                    </button>
                </div>
                <div class="col-sm-6 mb-2">
                    <a href="view_teachers.php" class="btn btn-success w-100">
                        <i class="fas fa-users me-2"></i>View Teachers
                    </a>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-<?php echo $modalType; ?> text-white">
        <h5 class="modal-title" id="feedbackModalLabel">
          <?php echo ucfirst($modalType); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo $modalMessage; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-<?php echo $modalType; ?>" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

<?php if (!empty($modalType)) : ?>
    const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    window.addEventListener('load', () => {
      feedbackModal.show();
    });
  <?php endif; ?>

    // Real-time password validation
    $(document).ready(function() {
        const passwordInput = $('#password');
        const confirmPasswordInput = $('#password_confirm');
        const lengthReq = $('#length');
        const uppercaseReq = $('#uppercase');
        const lowercaseReq = $('#lowercase');
        const numberReq = $('#number');
        const specialReq = $('#special');
        const passwordMatch = $('#password-match');
        
        // Auto-generate school email
        $('#name, #surname').on('input', function() {
            let name = $('#name').val().toLowerCase().trim();
            let surname = $('#surname').val().toLowerCase().trim();
            
            // Remove spaces and special characters
            name = name.replace(/[^\w\s]/gi, '').replace(/\s+/g, '');
            surname = surname.replace(/[^\w\s]/gi, '').replace(/\s+/g, '');
            
            if (name && surname) {
                const schoolEmail = name + '.' + surname + '@alphonsusprimary.ac.uk';
                $('#email_school').val(schoolEmail);
            } else {
                $('#email_school').val('');
            }
        });
        
        // Check password strength
        passwordInput.on('keyup', function() {
            const password = $(this).val();
            
            // Validate length
            if(password.length >= 8) {
                lengthReq.removeClass('not-met').addClass('met');
                lengthReq.html('<i class="fas fa-check"></i> At least 8 characters');
            } else {
                lengthReq.removeClass('met').addClass('not-met');
                lengthReq.html('<i class="fas fa-times"></i> At least 8 characters');
            }
            
            // Validate uppercase
            if(/[A-Z]/.test(password)) {
                uppercaseReq.removeClass('not-met').addClass('met');
                uppercaseReq.html('<i class="fas fa-check"></i> At least one uppercase letter (A-Z)');
            } else {
                uppercaseReq.removeClass('met').addClass('not-met');
                uppercaseReq.html('<i class="fas fa-times"></i> At least one uppercase letter (A-Z)');
            }
            
            // Validate lowercase
            if(/[a-z]/.test(password)) {
                lowercaseReq.removeClass('not-met').addClass('met');
                lowercaseReq.html('<i class="fas fa-check"></i> At least one lowercase letter (a-z)');
            } else {
                lowercaseReq.removeClass('met').addClass('not-met');
                lowercaseReq.html('<i class="fas fa-times"></i> At least one lowercase letter (a-z)');
            }
            
            // Validate number
            if(/[0-9]/.test(password)) {
                numberReq.removeClass('not-met').addClass('met');
                numberReq.html('<i class="fas fa-check"></i> At least one number (0-9)');
            } else {
                numberReq.removeClass('met').addClass('not-met');
                numberReq.html('<i class="fas fa-times"></i> At least one number (0-9)');
            }
            
            // Validate special character
            if(/[@$!%*?&]/.test(password)) {
                specialReq.removeClass('not-met').addClass('met');
                specialReq.html('<i class="fas fa-check"></i> At least one special character (@$!%*?&)');
            } else {
                specialReq.removeClass('met').addClass('not-met');
                specialReq.html('<i class="fas fa-times"></i> At least one special character (@$!%*?&)');
            }
            
            // Check if passwords match
            checkPasswordMatch();
        });
        
        // Check if passwords match
        confirmPasswordInput.on('keyup', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = passwordInput.val();
            const confirmPassword = confirmPasswordInput.val();
            
            if(confirmPassword.length > 0) {
                if(password === confirmPassword) {
                    passwordMatch.removeClass('text-danger').addClass('text-success');
                    passwordMatch.html('<i class="fas fa-check"></i> Passwords match');
                } else {
                    passwordMatch.removeClass('text-success').addClass('text-danger');
                    passwordMatch.html('<i class="fas fa-times"></i> Passwords do not match');
                }
            } else {
                passwordMatch.html('');
            }
        }
    });
</script>

</body>
</html>