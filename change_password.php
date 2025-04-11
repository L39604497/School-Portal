<?php
// This page will be shown when a user logs in for the first time

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
if (!isset($conn)) {
    include ('config.php');
    
    // Function to sanitize input data if not already defined
    if (!function_exists('sanitize')) {
        function sanitize($data) {
            global $conn;
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $conn->real_escape_string($data);
        }
    }
    
    // Function to redirect user based on role
    if (!function_exists('redirectByRole')) {
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
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Process form submission
$success = $error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        // Get user info from session
        $user_id = $_SESSION['user_id'];
        $table = $_SESSION['user_table'];
        $role = $_SESSION['role'];
        $id_field = ($table === 'Admin') ? 'AdminID' : 'TeacherID';
        
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE $table SET password_hash = ?, password_change_required = 0 WHERE $id_field = ?");
        $stmt->bind_param("si", $new_password_hash, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully. Redirecting to dashboard...";
            // Redirect after 2 seconds
            header("refresh:2;url=" . ($role == 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'));
        } else {
            $error = "Error updating password: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    
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

        /* Container styles */
        .change-password-container {
            width: 100%;
            max-width: 450px;
        }

        .change-password-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            background-color: white;
            overflow: hidden;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .form-control {
            border-radius: var(--border-radius);
        }

        .form-text {
            color: var(--light-text);
        }

        .alert {
            border-radius: var(--border-radius);
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="card change-password-card">
            <div class="header">
                <h2>Change Password</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info text-center">
                    <strong>Welcome!</strong> Please change your password to continue.
                </div>
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success text-center"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3 position-relative">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary w-100">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
