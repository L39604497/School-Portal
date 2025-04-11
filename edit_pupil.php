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

$showModal = false;
$modalType = '';
$modalMessage = '';
$pupilId = null;

// Check if pupil ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_pupils.php");
    exit();
}

$pupilId = $_GET['id'];

// Fetch pupil data
$sql = "SELECT * FROM Pupil WHERE PupilID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pupilId);
$stmt->execute();
$result = $stmt->get_result();
$pupil = $result->fetch_assoc();

if (!$pupil) {
    header("Location: view_pupils.php?error=Pupil not found");
    exit();
}

// Fetch linked parents for this pupil
$linkedParents = [];
$parents_query = "SELECT p.ParentID, p.Name, p.Surname, pp.RelationshipType 
                 FROM PupilParent pp 
                 JOIN Parent p ON pp.ParentID = p.ParentID 
                 WHERE pp.PupilID = ?";
$parents_stmt = $conn->prepare($parents_query);
$parents_stmt->bind_param("i", $pupilId);
$parents_stmt->execute();
$parents_result = $parents_stmt->get_result();
while ($row = $parents_result->fetch_assoc()) {
    $linkedParents[] = $row;
}

// Get all available classes
$classes_query = "SELECT ClassID, ClassName FROM Class ORDER BY ClassName";
$classes_result = $conn->query($classes_query);
$classes = [];
while ($class = $classes_result->fetch_assoc()) {
    $classes[] = $class;
}

if (isset($_POST['submit'])) {
    // Sanitize and fetch form inputs
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $surname = mysqli_real_escape_string($conn, trim($_POST['surname']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $dateOfBirth = mysqli_real_escape_string($conn, trim($_POST['date_of_birth']));
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, trim($_POST['gender'])) : '';
    $medicalInfo = mysqli_real_escape_string($conn, trim($_POST['medical_info']));
    $classId = mysqli_real_escape_string($conn, trim($_POST['class_id']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    
    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($dateOfBirth)) $errors[] = "Date of Birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($classId)) $errors[] = "Class is required";
    if (empty($status)) $errors[] = "Status is required";

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Update pupil information
            $update_stmt = $conn->prepare("UPDATE Pupil SET 
                Name = ?, 
                Surname = ?, 
                Address = ?, 
                DateOfBirth = ?, 
                Gender = ?,
                MedicalInfo = ?,
                ClassID = ?,
                Status = ?
                WHERE PupilID = ?");
                
            $update_stmt->bind_param("ssssssssi", 
                $name, $surname, $address, $dateOfBirth, 
                $gender, $medicalInfo, $classId, $status, $pupilId);
                
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update pupil: " . $update_stmt->error);
            }
            
            // Handle parent relationships if needed
            // (You could add functionality here to update relationships)
            
            $conn->commit();
            
            $showModal = true;
            $modalType = 'success';
            $modalMessage = "Pupil information updated successfully.";
            
            // Refresh pupil data
            $stmt->execute();
            $result = $stmt->get_result();
            $pupil = $result->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $showModal = true;
            $modalType = 'error';
            $modalMessage = "Error updating pupil: " . $e->getMessage();
        }
    } else {
        $showModal = true;
        $modalType = 'validation';
        $modalMessage = implode('<br>', $errors);
    }
}

// Get all parents for the dropdown
$all_parents_query = "SELECT ParentID, Name, Surname FROM Parent ORDER BY Surname, Name";
$all_parents_result = $conn->query($all_parents_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pupil</title>
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

        /* Linked parents table */
        .linked-parents {
            margin-top: 2rem;
        }
        
        .linked-parents h5 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
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
            <h3>Edit Pupil Information</h3>
            <p>Update the details below for <?= htmlspecialchars($pupil['Name'] . ' ' . $pupil['Surname']) ?></p>
            
            <div class="form-card">
                <form action="edit_pupil.php?id=<?= $pupilId ?>" method="POST" id="pupilForm">
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="male" 
                                    value="Male" <?= ($pupil['Gender'] == 'Male') ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="female" 
                                    value="Female" <?= ($pupil['Gender'] == 'Female') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" 
                                    value="Other" <?= ($pupil['Gender'] == 'Other') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="other">Other</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                            value="<?= htmlspecialchars($pupil['Name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="surname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="surname" name="surname" 
                            value="<?= htmlspecialchars($pupil['Surname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="date_of_birth" 
                            value="<?= htmlspecialchars($pupil['DateOfBirth']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                            value="<?= htmlspecialchars($pupil['Address']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medical_info" class="form-label">Medical Information</label>
                        <textarea class="form-control" id="medical_info" name="medical_info" rows="3"><?= htmlspecialchars($pupil['MedicalInfo']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['ClassID'] ?>" <?= ($pupil['ClassID'] == $class['ClassID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['ClassName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <div class="row">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="enrolled" 
                                        value="Enrolled" <?= ($pupil['Status'] == 'Enrolled') ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="enrolled">Enrolled</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="suspended" 
                                        value="Suspended" <?= ($pupil['Status'] == 'Suspended') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="suspended">Suspended</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="graduated" 
                                        value="Graduated" <?= ($pupil['Status'] == 'Graduated') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="graduated">Graduated</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="withdrew" 
                                        value="Withdrew" <?= ($pupil['Status'] == 'Withdrew') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="withdrew">Withdrew</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-sm-6 mb-2">
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Pupil
                            </button>
                        </div>
                    
                        <div class="col-sm-6 mb-2">
                            <a href="view_pupils.php" class="btn btn-secondary w-100">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Linked Parents Section -->
            <?php if (!empty($linkedParents)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Linked Parents</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Parent ID</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Relationship</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedParents as $parent): ?>
                                <tr>
                                    <td><?= htmlspecialchars($parent['ParentID']) ?></td>
                                    <td><?= htmlspecialchars($parent['Name']) ?></td>
                                    <td><?= htmlspecialchars($parent['Surname']) ?></td>
                                    <td><?= htmlspecialchars($parent['RelationshipType']) ?></td>
                                    <td>
                                        <a href="view_parent.php?id=<?= $parent['ParentID'] ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="unlink_parent.php?pupil_id=<?= $pupilId ?>&parent_id=<?= $parent['ParentID'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to unlink this parent?');">
                                            <i class="fas fa-unlink"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add New Parent Link Section -->
            <div class="card">
                <div class="card-header">
                    <h5>Link to Another Parent</h5>
                </div>
                <div class="card-body">
                    <form action="link_parent.php" method="POST">
                        <input type="hidden" name="pupil_id" value="<?= $pupilId ?>">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="new_parent_id" class="form-label">Select Parent</label>
                                <select class="form-select" id="new_parent_id" name="parent_id" required>
                                    <option value="">Select a parent</option>
                                    <?php while ($row = $all_parents_result->fetch_assoc()): ?>
                                        <option value="<?= $row['ParentID'] ?>">
                                            <?= htmlspecialchars($row['Name'] . ' ' . $row['Surname']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="relationship_type" class="form-label">Relationship</label>
                                <select class="form-select" id="relationship_type" name="relationship_type" required>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Grandmother">Grandmother</option>
                                    <option value="Grandfather">Grandfather</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-link me-2"></i>Link Parent
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal handling -->
    <?php if ($showModal): ?>
        <!-- Success Modal -->
        <?php if ($modalType === 'success'): ?>
        <div class="modal fade show" id="pupilModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_pupil.php?id=<?= $pupilId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon success">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4>Update Successful</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="edit_pupil.php?id=<?= $pupilId ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Continue Editing
                            </a>
                            <a href="view_pupils.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> View Pupils
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Modal -->
        <?php elseif ($modalType === 'error' || $modalType === 'validation'): ?>
        <div class="modal fade show" id="pupilModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_pupil.php?id=<?= $pupilId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon error">
                            <i class="fas fa-times"></i>
                        </div>
                        <h4>Update Error</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="edit_pupil.php?id=<?= $pupilId ?>" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i> Try Again
                            </a>
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
</body>
</html>