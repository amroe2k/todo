<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(!isLoggedIn()) {
    redirect('../index.php?page=login');
}

$auth = new Auth();
$user = $auth->getUser(getCurrentUserId());

// Detect current page from URL parameter
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!-- Ensure Bootstrap Icons available for menu items -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Global Page Transition Loading -->
<div id="pageTransitionLoading">
    <div class="page-loading-content">
        <div class="page-loading-spinner"></div>
        <div class="page-loading-text">Loading...</div>
    </div>
</div>

<script>
// Global page transition animation
document.addEventListener('DOMContentLoaded', function() {
    const pageLoading = document.getElementById('pageTransitionLoading');
    const navLinks = document.querySelectorAll('.navbar .nav-link, .navbar .dropdown-item');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Skip if it's a dropdown toggle or has no href
            if (this.classList.contains('dropdown-toggle') || !this.href) {
                return;
            }
            
            // Skip if it's logout (handled separately)
            if (this.href.includes('page=logout')) {
                return;
            }
            
            // Prevent default and show loading
            e.preventDefault();
            const targetUrl = this.href;
            
            if (pageLoading) {
                pageLoading.classList.add('active');
                
                // Navigate after smooth animation
                setTimeout(function() {
                    window.location.href = targetUrl;
                }, 800);
            } else {
                window.location.href = targetUrl;
            }
        });
    });
});
</script>

<nav class="navbar navbar-expand-lg navbar-soft">
    <div class="container">
        <a class="navbar-brand" href="../index.php?page=dashboard">
            <i class="bi bi-check2-square me-2"></i>Todo Talenta Digital
        </a>
        <?php if (isset($_SESSION['viewing_as_user']) && $_SESSION['viewing_as_user']): ?>
            <span class="viewing-as-badge"><i class="bi bi-eye"></i> Viewing as <?php echo htmlspecialchars($user['username']); ?></span>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>" 
                        href="../index.php?page=dashboard">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'notes' ? 'active' : ''; ?>" 
                        href="../index.php?page=notes">
                        <i class="bi bi-sticky"></i> Sticky Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'todos' ? 'active' : ''; ?>" 
                        href="../index.php?page=todos">
                        <i class="bi bi-list-check"></i> Todo List
                    </a>
                </li>
                <?php if($user['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'users' ? 'active' : ''; ?>" 
                        href="../index.php?page=users">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(ucwords($user['username'])); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (isset($_SESSION['viewing_as_user']) && $_SESSION['viewing_as_user']): ?>
                        <li><a class="dropdown-item" href="../index.php?page=users&return_as_admin=1">
                            <i class="bi bi-arrow-counterclockwise"></i> Back to Admin
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../index.php?page=change-password">
                            <i class="bi bi-key"></i> Change Password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../index.php?page=logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>