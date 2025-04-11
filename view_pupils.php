<?php
session_start();

// Checks if the user is not logged in or not an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Redirects to the login page or another page
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
require_once ('config.php');
require_once ('admin_sidebar.php');


// Pagination setup
$rowsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Gets total number of pupils
$totalQuery = "SELECT COUNT(*) as total FROM Pupil";
$totalResult = $conn->query($totalQuery);
$totalRows = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $rowsPerPage);

// Gets data from database with pagination
$sql = "SELECT * FROM Pupil ORDER BY PupilID ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $rowsPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Error: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Pupils</title>
    
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

        /* Table styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin: 1.5rem 0;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }

        /* Status counts */
        .status-count {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 500;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--box-shadow);
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

        /* Search and filter section */
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            background-color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: var(--border-radius);
            border: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        }

        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
        }

        /* Form control styles (for select) */
        .form-select {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        }

        /* Pagination styling */
        .pagination {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .pagination .page-item .page-link {
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            margin: 0 0.25rem;
            transition: var(--transition);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white !important; /* Ensure white text on active page */
        }

        .pagination .page-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }

        /* Responsive design */
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
            
            .table {
                display: block;
                overflow-x: auto;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                max-width: 100%;
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

        /* Content container */
        .content-container {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }

        /* Page title styling */
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .page-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--secondary-color);
        }

         /* Additional status badge styling */
         .status-badge {
            display: inline-block;
            padding: 0.25em 0.5em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .status-badge-enrolled { background-color: #28a745; color: white; }
        .status-badge-graduated { background-color: #6c757d; color: white; }
        .status-badge-transferred { background-color: #ffc107; color: black; }
        .status-badge-suspended { background-color: #dc3545; color: white; }
    
    </style>
</head>
<body>


    <!-- Main Content Area -->
    <div class="main-content">
        <div class="content-container">
            <h2 class="page-title">Pupils List</h2>
            
            <div class="status-count">
                <i class="fas fa-users me-2"></i>
                <?php 
                // Shows total number of Pupils
                echo "Total number of pupils: " . $totalRows;
                ?>
                </div>
            
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search pupils...">
                </div>
                <div>
                    <select id="filterClass" class="form-select">
                        <option value="">All Classes</option>
                        <?php
                        // Fetchs unique classes from database
                        $classQuery = "SELECT DISTINCT ClassID FROM Pupil ORDER BY ClassID";
                        $classResult = $conn->query($classQuery);
                        while ($classResult && $class = $classResult->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($class['ClassID'] ?? '') . "'>" . 
                                 htmlspecialchars($class['ClassID'] ?? '') . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Pupil ID</th>
                    <th>Name</th>
                    <th>Surname</th>
                    <th>Address</th>
                    <th>Medical Info</th>
                    <th>Class ID</th>
                    <th>Parent ID</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Determines status badge class
                        $statusClass = 'status-badge status-badge-' . strtolower(str_replace(' ', '-', $row['Status']));

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['PupilID'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['Name'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['Surname'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['Address'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['MedicalInfo'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['ClassID'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['ParentID'] ?? '') . "</td>";
                        
                        // Adds Status column with custom badge
                        echo "<td><span class='" . htmlspecialchars($statusClass) . "'>" . 
                             htmlspecialchars($row['Status'] ?? 'Unknown') . "</span></td>";
                        
                        echo "<td>
                            <a href='edit_pupil.php?id=" . htmlspecialchars($row['PupilID']) . "' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></a>
                            <a href='delete_pupil.php?id=" . htmlspecialchars($row['PupilID']) . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this pupil?\");'><i class='fas fa-trash'></i></a>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>No pupils found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

            
            <nav aria-label="Pupils pagination">
                <ul class="pagination">
                    <?php
                    // Previous button
                    echo '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '">';
                    echo '<a class="page-link" href="?page=' . max(1, $currentPage - 1) . '">Previous</a>';
                    echo '</li>';
                    
                    // Page numbers
                    for ($i = 1; $i <= $totalPages; $i++) {
                        echo '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '">';
                        echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    // Next button
                    echo '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">';
                    echo '<a class="page-link" href="?page=' . min($totalPages, $currentPage + 1) . '">Next</a>';
                    echo '</li>';
                    ?>
                </ul>
            </nav>
            
            <a href="register_pupil.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Register New Pupil
            </a>
        </div>
    </div>
    
    <div class="toast-container"></div>
    
    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search input event
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', debounce(function() {
                    filterTable();
                }, 300));
            }
            
            // Class filter change
            const filterClass = document.getElementById('filterClass');
            if (filterClass) {
                filterClass.addEventListener('change', function() {
                    filterTable();
                });
            }
        });
        
        // Client-side filtering for demonstration
        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const surname = row.cells[2].textContent.toLowerCase();
                const address = row.cells[3].textContent.toLowerCase();
                const classId = row.cells[5].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchValue) || 
                                    surname.includes(searchValue) || 
                                    address.includes(searchValue);
                                    
                const matchesClass = classFilter === '' || classId === classFilter;
                
                if (matchesSearch && matchesClass) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update status count
            document.querySelector('.status-count').textContent = `Number of pupils found: ${visibleCount}`;
        }
        
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        // Functions to show toast notifications
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }
            
            // Creates toast element
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            
            // Adds to container
            toastContainer.appendChild(toast);
            
            // Removes after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>