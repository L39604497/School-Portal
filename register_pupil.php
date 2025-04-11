<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include('config.php');
include('admin_sidebar.php');

$showModal = false;
$modalType = '';
$modalMessage = '';
$pupilId = null;

if (isset($_POST['submit'])) {
    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $surname = mysqli_real_escape_string($conn, trim($_POST['surname']));
    $dateOfBirth = mysqli_real_escape_string($conn, trim($_POST['dob']));
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, trim($_POST['gender'])) : '';
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $medical_info = mysqli_real_escape_string($conn, trim($_POST['medical_info']));
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    
    // Parent relationships
    $parent1_id = isset($_POST['parent1_id']) && !empty($_POST['parent1_id']) ? (int)$_POST['parent1_id'] : null;
    $parent1_relation = isset($_POST['parent1_relation']) && !empty($_POST['parent1_relation']) ? mysqli_real_escape_string($conn, trim($_POST['parent1_relation'])) : null;
    $parent2_id = isset($_POST['parent2_id']) && !empty($_POST['parent2_id']) ? (int)$_POST['parent2_id'] : null;
    $parent2_relation = isset($_POST['parent2_relation']) && !empty($_POST['parent2_relation']) ? mysqli_real_escape_string($conn, trim($_POST['parent2_relation'])) : null;

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($dateOfBirth)) $errors[] = "Date of Birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($class_id)) $errors[] = "Class is required";
    
    // Validate parent relationships if IDs are provided
    if ($parent1_id && empty($parent1_relation)) $errors[] = "Relationship is required for Parent/Guardian 1";
    if ($parent2_id && empty($parent2_relation)) $errors[] = "Relationship is required for Parent/Guardian 2";

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Insert pupil
            $insert_stmt = $conn->prepare("INSERT INTO Pupil (Name, Surname, DateOfBirth, Gender, Address, MedicalInfo, ClassID, Status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Enrolled')");
            if (!$insert_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $insert_stmt->bind_param("ssssssi", $name, $surname, $dateOfBirth, $gender, $address, $medical_info, $class_id);
            if (!$insert_stmt->execute()) {
                throw new Exception("Execute failed: " . $insert_stmt->error);
            }
            
            $pupilId = $conn->insert_id;
            $insert_stmt->close();
            
            // Link to parent 1 if provided
            if ($parent1_id && $parent1_relation) {
                $link_stmt = $conn->prepare("INSERT INTO PupilParent (PupilID, ParentID, RelationshipType) VALUES (?, ?, ?)");
                if (!$link_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $link_stmt->bind_param("iis", $pupilId, $parent1_id, $parent1_relation);
                if (!$link_stmt->execute()) throw new Exception("Execute failed: " . $link_stmt->error);
                $link_stmt->close();
            }
            
            // Link to parent 2 if provided
            if ($parent2_id && $parent2_relation) {
                // Check if this would exceed 2 parents
                $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM PupilParent WHERE PupilID = ?");
                if (!$count_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $count_stmt->bind_param("i", $pupilId);
                if (!$count_stmt->execute()) throw new Exception("Execute failed: " . $count_stmt->error);
                
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                
                if ($count_row['count'] >= 2) {
                    throw new Exception("Pupil already has maximum parents/guardians");
                }
                
                $link_stmt = $conn->prepare("INSERT INTO PupilParent (PupilID, ParentID, RelationshipType) VALUES (?, ?, ?)");
                if (!$link_stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $link_stmt->bind_param("iis", $pupilId, $parent2_id, $parent2_relation);
                if (!$link_stmt->execute()) throw new Exception("Execute failed: " . $link_stmt->error);
                $link_stmt->close();
            }
            
            $conn->commit();
            
            $showModal = true;
            $modalType = 'success';
            $modalMessage = "Pupil registered successfully. Pupil ID: $pupilId";
                
        } catch (Exception $e) {
            $conn->rollback();
            $showModal = true;
            $modalType = 'error';
            $modalMessage = "Database Error: " . $e->getMessage();
            error_log("Pupil Registration Error: " . $e->getMessage());
        }
    } else {
        $showModal = true;
        $modalType = 'validation';
        $modalMessage = implode('<br>', $errors);
    }
}

// Get parents for dropdown
$parents_result = $conn->query("SELECT ParentID, CONCAT(Name, ' ', Surname) AS FullName FROM Parent ORDER BY Surname, Name");
if (!$parents_result) {
    error_log("Error fetching parents: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Pupil</title>
    
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
    
        /* Responsive layout */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .modal .btn {
                margin-bottom: 0.5rem;
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

/* Hover effect on cards */
.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}
    </style>
</head>
<body>

<!-- Main Content Area -->
<div class="main-content">
    <div class="container">
        <h3>Register a Pupil</h3>
        <p>Fill in the details below to register a new pupil.</p>
        
        <div class="form-card">
            <form action="register_pupil.php" method="POST" id="pupilForm">
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
                    <input type="text" class="form-control" id="medical_info" name="medical_info">
                </div>
                
                <div class="mb-3">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <option value="">Select a class</option>
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
                
                <!-- Parent 1 Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Parent/Guardian 1</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="parent1_id" class="form-label">Select Parent/Guardian</label>
                            <select class="form-select" id="parent1_id" name="parent1_id">
                                <option value="">-- Select Parent --</option>
                                <?php if ($parents_result && $parents_result->num_rows > 0): ?>
                                    <?php while ($parent = $parents_result->fetch_assoc()): ?>
                                        <option value="<?php echo $parent['ParentID']; ?>"><?php echo htmlspecialchars($parent['FullName']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="parent1_relation" class="form-label">Relationship</label>
                            <select class="form-select" id="parent1_relation" name="parent1_relation">
                                <option value="">-- Select Relationship --</option>
                                <option value="Mother">Mother</option>
                                <option value="Father">Father</option>
                                <option value="Guardian">Guardian</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Parent 2 Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Parent/Guardian 2 (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="parent2_id" class="form-label">Select Parent/Guardian</label>
                            <select class="form-select" id="parent2_id" name="parent2_id">
                                <option value="">-- Select Parent --</option>
                                <?php 
                                // Reset pointer to reuse the same result set
                                if ($parents_result) {
                                    $parents_result->data_seek(0);
                                    while ($parent = $parents_result->fetch_assoc()): ?>
                                        <option value="<?php echo $parent['ParentID']; ?>"><?php echo htmlspecialchars($parent['FullName']); ?></option>
                                    <?php endwhile;
                                } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="parent2_relation" class="form-label">Relationship</label>
                            <select class="form-select" id="parent2_relation" name="parent2_relation">
                                <option value="">-- Select Relationship --</option>
                                <option value="Mother">Mother</option>
                                <option value="Father">Father</option>
                                <option value="Guardian">Guardian</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <button type="submit" name="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Register Pupil
                        </button>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <a href="view_pupils.php" class="btn btn-success w-100">
                            <i class="fas fa-users me-2"></i>View Pupils
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
        <div class="modal fade show" id="pupilModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Success</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>Success!</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <a href="view_pupils.php" class="btn btn-primary">View All Pupils</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($modalType === 'error'): ?>
    <!-- Error Modal -->
    <div class="modal fade show" id="pupilModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon error">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4>Error!</h4>
                    <p><?php echo $modalMessage; ?></p>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($modalType === 'validation'): ?>
    <!-- Validation Error Modal -->
    <div class="modal fade show" id="pupilModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Validation Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Please Check Your Input</h4>
                    <p><?php echo $modalMessage; ?></p>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handle modal closing
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('pupilModal');
        if (modal) {
            var closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    document.body.classList.remove('modal-open');
                    var modalBackdrops = document.getElementsByClassName('modal-backdrop');
                    if (modalBackdrops.length > 0) {
                        document.body.removeChild(modalBackdrops[0]);
                    }
                });
            });
        }
        
        // Form validation logic
        var pupilForm = document.getElementById('pupilForm');
        if (pupilForm) {
            pupilForm.addEventListener('submit', function(event) {
                // Parent relationship validation
                var parent1Id = document.getElementById('parent1_id').value;
                var parent1Relation = document.getElementById('parent1_relation').value;
                var parent2Id = document.getElementById('parent2_id').value;
                var parent2Relation = document.getElementById('parent2_relation').value;
                
                // Check if parent1 is selected but relationship is not
                if (parent1Id && !parent1Relation) {
                    alert('Please select a relationship for Parent/Guardian 1');
                    event.preventDefault();
                    return false;
                }
                
                // Check if parent2 is selected but relationship is not
                if (parent2Id && !parent2Relation) {
                    alert('Please select a relationship for Parent/Guardian 2');
                    event.preventDefault();
                    return false;
                }
                
                return true;
            });
        }
    });
</script>
</body>
</html>