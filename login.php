<?php
session_start();
include('config.php');

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to redirect user based on role
function redirectByRole($role) {
    if ($role == 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($role == 'teacher') {
        header("Location: teacher_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole($_SESSION['role']);
}

// Process login form submission
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    // Check for credentials in Admin table
    $stmt = $conn->prepare("SELECT AdminID, username, password_hash, role, status, password_change_required FROM Admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Admin found
        $user = $result->fetch_assoc();
        $table = 'Admin';
        $id_field = 'AdminID';
    } else {
        // Check for credentials in Teacher table
        $stmt = $conn->prepare("SELECT TeacherID, email_school AS username, password_hash, 'teacher' AS role, Status AS status, password_change_required FROM Teacher WHERE email_school = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Teacher found
            $user = $result->fetch_assoc();
            $table = 'Teacher';
            $id_field = 'TeacherID';
        } else {
            $error = "Invalid username or password";
        }
    }

    // If user found, verify the password
    if (empty($error)) {
        if (password_verify($password, $user['password_hash'])) {
            // Check if account is active
            if ($user['status'] == 'active' || $user['status'] == 'Active Teaching') {
                // Set session variables
                $_SESSION['user_id'] = $user[$id_field];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_table'] = $table;

                // Check if password change is required
                if ($user['password_change_required'] == 1) {
                    // Redirect to password change page
                    header("Location: change_password.php");
                    exit;
                } else {
                    // Redirect based on user role
                    redirectByRole($user['role']);
                }
            } else {
                $error = "Your account is not active. Please contact the administrator.";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphonsus Primary - Login</title>
    
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
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
        }

        /* Login form styles */
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 0;
        }

        .login-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            background-color: white;
            overflow: hidden;
        }

        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .login-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            margin-bottom: 0;
            opacity: 0.9;
        }

        .login-header:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--secondary-color);
            transform: translateX(-50%);
        }

        .login-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
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

        .login-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            transition: var(--transition);
            width: 100%;
            margin-top: 1rem;
            box-shadow: var(--box-shadow);
        }

        .login-btn:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
            transform: translateY(-2px);
        }

        .login-footer {
            text-align: center;
            padding: 1rem;
            background-color: var(--light-bg);
            color: var(--light-text);
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .login-footer a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .form-icon {
          display: flex;
          align-items: center;
          position: relative;
          width: 100%; 
        }

        .form-icon i.fa-user,
        .form-icon i.fa-lock {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            z-index: 5;
        }

        .icon-input {
            padding-left: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px; /* Keep the eye icon on the right side */
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: var(--box-shadow);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>

    <div class="login-container">

        <!-- Login Card -->
        <div class="login-card">

           <!-- Login Header -->
            <div class="login-header">
              <h2>Alphonsus Primary</h2>
            <p>School Management Portal</p>
          </div>

            
            <!-- Login Body -->
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username" class="form-label">Username / Email</label>
                        <div class="form-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control icon-input" id="username" name="username" placeholder="Enter your username or email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="form-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control icon-input" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-eye password-toggle" id="password-toggle"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary login-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
            </div>
            
            <!-- Login Footer -->
            <div class="login-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Alphonsus Primary School. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password visibility toggle
        document.getElementById('password-toggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>