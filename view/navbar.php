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
<!-- Dark Mode Stylesheet -->
<link rel="stylesheet" href="../css/dark-mode.css">

<!-- Dark Mode Script -->
<script>
    // Apply theme immediately to prevent flash
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<!-- Modern Page Transition Loader: Skeleton Alternative -->
<div id="pageSkeletonOverlay">
    <div class="container">
        <div class="sk-title sk-pulse"></div>
        <div id="skeletonStructure"></div>
    </div>
</div>
<div id="pageDimmer"></div>

<script>
// Global page transition animation (Alternative D: Skeleton Loading)
document.addEventListener('DOMContentLoaded', function() {
    const skeletonOverlay = document.getElementById('pageSkeletonOverlay');
    const skeletonStructure = document.getElementById('skeletonStructure');
    const pageDimmer = document.getElementById('pageDimmer');
    const navLinks = document.querySelectorAll('.navbar .nav-link, .navbar .dropdown-item, .dashboard-main-card, .btn-primary[href], .stat-card');
    
    function generateSkeleton(page) {
        let html = '';
        if (page.includes('dashboard')) {
            html = `
                <div class="sk-row mb-4">
                    <div class="sk-col-3 sk-stat sk-pulse"></div>
                    <div class="sk-col-3 sk-stat sk-pulse"></div>
                    <div class="sk-col-3 sk-stat sk-pulse"></div>
                    <div class="sk-col-3 sk-stat sk-pulse"></div>
                </div>
                <div class="sk-row">
                    <div class="sk-col-6 sk-card sk-pulse"></div>
                    <div class="sk-col-6 sk-card sk-pulse"></div>
                </div>`;
        } else if (page.includes('notes')) {
            html = `
                <div class="sk-row">
                    <div class="sk-col-3 sk-card sk-pulse" style="height: 220px;"></div>
                    <div class="sk-col-3 sk-card sk-pulse" style="height: 220px;"></div>
                    <div class="sk-col-3 sk-card sk-pulse" style="height: 220px;"></div>
                    <div class="sk-col-3 sk-card sk-pulse" style="height: 220px;"></div>
                </div>`;
        } else { // Todos or default
            html = `
                <div class="sk-row">
                    <div class="sk-col-6 sk-stat sk-pulse" style="height: 80px;"></div>
                    <div class="sk-col-6 sk-stat sk-pulse" style="height: 80px;"></div>
                    <div class="sk-col-6 sk-stat sk-pulse" style="height: 80px;"></div>
                    <div class="sk-col-6 sk-stat sk-pulse" style="height: 80px;"></div>
                </div>`;
        }
        return html;
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href') || (this.closest('a') ? this.closest('a').getAttribute('href') : null);
            if (this.classList.contains('dropdown-toggle') || !href || href === '#' || href.startsWith('javascript:')) {
                return;
            }
            
            if (href.includes('page=logout')) {
                return;
            }
            
            e.preventDefault();
            const targetUrl = this.href || href;
            
            if (skeletonOverlay) {
                // Morph current element feedback
                if (this.classList.contains('nav-link')) {
                    const icon = this.querySelector('i');
                    if(icon) {
                        icon.className = 'bi bi-arrow-repeat spin-animate me-2';
                    }
                }

                // Show Skeleton
                const pageType = targetUrl.toLowerCase();
                skeletonStructure.innerHTML = generateSkeleton(pageType);
                skeletonOverlay.classList.add('active');
                if(pageDimmer) pageDimmer.classList.add('active');

                // Navigate
                setTimeout(function() {
                    window.location.href = targetUrl;
                }, 600);
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
            <i class="bi bi-intersect me-2"></i>Todo Talenta
        </a>
        
        <?php if (isset($_SESSION['viewing_as_user']) && $_SESSION['viewing_as_user']): ?>
            <span class="viewing-as-badge d-none d-md-inline-block">
                <i class="bi bi-eye"></i> Viewing as <?php echo htmlspecialchars($user['username']); ?>
            </span>
        <?php endif; ?>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>" 
                        href="../index.php?page=dashboard">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'notes' ? 'active' : ''; ?>" 
                        href="../index.php?page=notes">
                        <i class="bi bi-journal-text"></i> Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'todos' ? 'active' : ''; ?>" 
                        href="../index.php?page=todos">
                        <i class="bi bi-check-circle"></i> Tasks
                    </a>
                </li>
                <?php if($user['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'users' ? 'active' : ''; ?>" 
                        href="../index.php?page=users">
                        <i class="bi bi-shield-lock"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Dark Mode Toggle Button (Standalone) -->
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <button class="btn btn-light rounded-circle p-2 border-0 shadow-sm" 
                            onclick="toggleDarkMode()" 
                            title="Toggle Dark/Light Mode"
                            id="themeToggleBtn"
                            style="width: 42px; height: 42px;">
                        <i class="bi bi-moon-stars fs-5" id="darkModeIcon"></i>
                    </button>
                </li>

                <li class="nav-item dropdown ms-lg-2 mt-2 mt-lg-0 w-100 w-lg-auto">
                    <a class="nav-link dropdown-toggle bg-light rounded-pill px-3" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span class="fw-bold"><?php echo htmlspecialchars(ucwords($user['username'])); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0">
                        <?php if (isset($_SESSION['viewing_as_user']) && $_SESSION['viewing_as_user']): ?>
                        <li><a class="dropdown-item" href="../index.php?page=users&return_as_admin=1">
                            <i class="bi bi-arrow-counterclockwise text-warning"></i> Back to Admin
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../index.php?page=change-password">
                            <i class="bi bi-key text-info"></i> Security Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../index.php?page=logout">
                            <i class="bi bi-box-arrow-right"></i> Sign Out
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    function toggleDarkMode() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        updateDarkModeUI(newTheme);
    }

    function updateDarkModeUI(theme) {
        const icon = document.getElementById('darkModeIcon');
        const btn = document.getElementById('themeToggleBtn');
        
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill fs-5 text-warning';
                if (btn) {
                    btn.classList.remove('btn-light');
                    btn.classList.add('btn-dark');
                }
            } else {
                icon.className = 'bi bi-moon-stars fs-5';
                if (btn) {
                    btn.classList.remove('btn-dark');
                    btn.classList.add('btn-light');
                }
            }
        }
    }

    // Update UI on load
    document.addEventListener('DOMContentLoaded', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateDarkModeUI(currentTheme);
    });
</script>
