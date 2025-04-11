<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <h2>Teacher Portal</h2>
    <nav>
        <a href="teacher_dashboard.php" class="<?php echo ($current_page == 'teacher_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="view_pupils.php" class="<?php echo ($current_page == 'view_pupils.php') ? 'active' : ''; ?>">
            <i class="fas fa-users me-2"></i> My Pupils
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'record_attendance.php') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check me-2"></i> Attendance
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'record_grades.php') ? 'active' : ''; ?>">
            <i class="fas fa-graduation-cap me-2"></i> Grades
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'class_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt me-2"></i> Schedule
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'teacher_messages.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope me-2"></i> Messages
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'teacher_resources.php') ? 'active' : ''; ?>">
            <i class="fas fa-book me-2"></i> Resources
        </a>
        <a href="coming_soon.php" class="<?php echo ($current_page == 'my_profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-circle me-2"></i> My Profile
        </a>
        <a href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </nav>
</div>