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
$parentId = null;

// Check if parent ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_parents.php");
    exit();
}

$parentId = $_GET['id'];

// Fetch parent data
$sql = "SELECT * FROM Parent WHERE ParentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parentId);
$stmt->execute();
$result = $stmt->get_result();
$parent = $result->fetch_assoc();

if (!$parent) {
    header("Location: view_parents.php?error=Parent not found");
    exit();
}

// Fetch linked pupils for this parent
$linkedPupils = [];
$pupils_query = "SELECT p.PupilID, p.Name, p.Surname, pp.RelationshipType 
                 FROM PupilParent pp 
                 JOIN Pupil p ON pp.PupilID = p.PupilID 
                 WHERE pp.ParentID = ?";
$pupils_stmt = $conn->prepare($pupils_query);
$pupils_stmt->bind_param("i", $parentId);
$pupils_stmt->execute();
$pupils_result = $pupils_stmt->get_result();
while ($row = $pupils_result->fetch_assoc()) {
    $linkedPupils[] = $row;
}

if (isset($_POST['submit'])) {
    // Sanitize and fetch form inputs
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $surname = mysqli_real_escape_string($conn, trim($_POST['surname']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $dateOfBirth = mysqli_real_escape_string($conn, trim($_POST['date_of_birth']));
    $relationshipType = mysqli_real_escape_string($conn, trim($_POST['relationship_type']));
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, trim($_POST['gender'])) : '';
    
    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($address)) $errors[] = "Address is required";
    if (empty($dateOfBirth)) $errors[] = "Date of Birth is required";
    if (empty($relationshipType)) $errors[] = "Relationship Type is required";
    if (empty($gender)) $errors[] = "Gender is required";

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Update parent information
            $update_stmt = $conn->prepare("UPDATE Parent SET 
                Name = ?, 
                Surname = ?, 
                Email = ?, 
                Address = ?, 
                DateOfBirth = ?, 
                RelationshipType = ?, 
                Gender = ? 
                WHERE ParentID = ?");
                
            $update_stmt->bind_param("ssssssss", 
                $name, $surname, $email, $address, 
                $dateOfBirth, $relationshipType, $gender, $parentId);
                
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update parent: " . $update_stmt->error);
            }
            
            // Handle pupil relationships if needed
            // (You could add functionality here to update relationships)
            
            $conn->commit();
            
            $showModal = true;
            $modalType = 'success';
            $modalMessage = "Parent information updated successfully.";
            
            // Refresh parent data
            $stmt->execute();
            $result = $stmt->get_result();
            $parent = $result->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $showModal = true;
            $modalType = 'error';
            $modalMessage = "Error updating parent: " . $e->getMessage();
        }
    } else {
        $showModal = true;
        $modalType = 'validation';
        $modalMessage = implode('<br>', $errors);
    }
}

// Get all pupils for the dropdown
$all_pupils_query = "SELECT PupilID, Name, Surname FROM Pupil ORDER BY Surname, Name";
$all_pupils_result = $conn->query($all_pupils_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parent</title>
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

        /* Linked pupils table */
        .linked-pupils {
            margin-top: 2rem;
        }
        
        .linked-pupils h5 {
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
            <h3>Edit Parent Information</h3>
            <p>Update the details below for <?= htmlspecialchars($parent['Name'] . ' ' . $parent['Surname']) ?></p>
            
            <div class="form-card">
                <form action="edit_parent.php?id=<?= $parentId ?>" method="POST" id="parentForm">
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="male" 
                                    value="Male" <?= ($parent['Gender'] == 'Male') ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="gender" id="female" 
                                    value="Female" <?= ($parent['Gender'] == 'Female') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" 
                                    value="Other" <?= ($parent['Gender'] == 'Other') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="other">Other</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                            value="<?= htmlspecialchars($parent['Name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="surname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="surname" name="surname" 
                            value="<?= htmlspecialchars($parent['Surname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="date_of_birth" 
                            value="<?= htmlspecialchars($parent['DateOfBirth']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                            value="<?= htmlspecialchars($parent['Address']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            value="<?= htmlspecialchars($parent['Email']) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Relationship Type</label>
                        <div class="row">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="father" 
                                        value="Father" <?= ($parent['RelationshipType'] == 'Father') ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="father">Father</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="mother" 
                                        value="Mother" <?= ($parent['RelationshipType'] == 'Mother') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mother">Mother</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship_type" id="guardian" 
                                        value="Guardian" <?= ($parent['RelationshipType'] == 'Guardian') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="guardian">Guardian</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-sm-6 mb-2">
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Parent
                            </button>
                        </div>
                    
                        <div class="col-sm-6 mb-2">
                            <a href="view_parents.php" class="btn btn-secondary w-100">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Linked Pupils Section -->
            <?php if (!empty($linkedPupils)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Linked Pupils</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Pupil ID</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Relationship</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedPupils as $pupil): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pupil['PupilID']) ?></td>
                                    <td><?= htmlspecialchars($pupil['Name']) ?></td>
                                    <td><?= htmlspecialchars($pupil['Surname']) ?></td>
                                    <td><?= htmlspecialchars($pupil['RelationshipType']) ?></td>
                                    <td>
                                        <a href="view_pupil.php?id=<?= $pupil['PupilID'] ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="unlink_pupil.php?parent_id=<?= $parentId ?>&pupil_id=<?= $pupil['PupilID'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to unlink this pupil?');">
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
            
            <!-- Add New Pupil Link Section -->
            <div class="card">
                <div class="card-header">
                    <h5>Link to Another Pupil</h5>
                </div>
                <div class="card-body">
                    <form action="link_pupil.php" method="POST">
                        <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="new_pupil_id" class="form-label">Select Pupil</label>
                                <select class="form-select" id="new_pupil_id" name="pupil_id" required>
                                    <option value="">Select a pupil</option>
                                    <?php while ($row = $all_pupils_result->fetch_assoc()): ?>
                                        <option value="<?= $row['PupilID'] ?>">
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
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-link me-2"></i>Link Pupil
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
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_parent.php?id=<?= $parentId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon success">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4>Update Successful</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="edit_parent.php?id=<?= $parentId ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Continue Editing
                            </a>
                            <a href="view_parents.php" class="btn btn-success">
                                <i class="fas fa-users me-1"></i> View Parents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Modal -->
        <?php elseif ($modalType === 'error' || $modalType === 'validation'): ?>
        <div class="modal fade show" id="parentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='edit_parent.php?id=<?= $parentId ?>'"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon error">
                            <i class="fas fa-times"></i>
                        </div>
                        <h4>Update Error</h4>
                        <p><?php echo $modalMessage; ?></p>
                        <div class="d-flex justify-content-center">
                            <a href="edit_parent.php?id=<?= $parentId ?>" class="btn btn-primary">
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