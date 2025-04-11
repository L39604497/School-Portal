<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_dashboard.php" class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">Dashboard</a>
    <a href="create_admin.php" class="<?= ($current_page == 'create_admin.php') ? 'active' : '' ?>">Create Admin</a>
    <a href="manage_classes.php" class="<?= ($current_page == 'manage_classes.php') ? 'active' : '' ?>">Manage Classes</a>
    <a href="register_pupil.php" class="<?= ($current_page == 'register_pupil.php') ? 'active' : '' ?>">Register Pupil</a>
    <a href="register_teacher.php" class="<?= ($current_page == 'register_teacher.php') ? 'active' : '' ?>">Register Teacher</a>
    <a href="register_parent.php" class="<?= ($current_page == 'register_parent.php') ? 'active' : '' ?>">Register Parent</a>
    <a href="view_pupils.php" class="<?= ($current_page == 'view_pupils.php') ? 'active' : '' ?>">View Pupils</a>
    <a href="view_teachers.php" class="<?= ($current_page == 'view_teachers.php') ? 'active' : '' ?>">View Teachers</a>
    <a href="view_parents.php" class="<?= ($current_page == 'view_parents.php') ? 'active' : '' ?>">View Parents</a>
    <a href="logout.php">Logout</a>
</nav>