<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

if(!isLoggedIn()) {
    redirect('../index.php?page=login');
}

$auth = new Auth();
$user = $auth->getUser(getCurrentUserId());

// Fetch counts for dashboard cards
$db = new Database();
$conn = $db->getConnection();
$current_user_id = getCurrentUserId();

$todo_count = 0;
$note_count = 0;
$user_count = 0;

// Pagination for activity log
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$per_page = 4;
$offset = ($page - 1) * $per_page;

if ($conn && $current_user_id) {
    $stmtTodo = $conn->prepare("SELECT COUNT(*) FROM todos WHERE user_id = :uid");
    $stmtTodo->bindParam(':uid', $current_user_id, PDO::PARAM_INT);
    $stmtTodo->execute();
    $todo_count = (int) $stmtTodo->fetchColumn();

    $stmtNote = $conn->prepare("SELECT COUNT(*) FROM notes WHERE user_id = :uid");
    $stmtNote->bindParam(':uid', $current_user_id, PDO::PARAM_INT);
    $stmtNote->execute();
    $note_count = (int) $stmtNote->fetchColumn();

    if (isAdmin()) {
        $stmtUsers = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmtUsers->execute();
        $user_count = (int) $stmtUsers->fetchColumn();
    }
    
    // Get total activity count for pagination
    $total_activities = $note_count + $todo_count;
    $total_pages = ceil($total_activities / $per_page);
    
    // Fetch recent notes (limited by pagination)
    $stmtRecentNotes = $conn->prepare("SELECT id, title, created_at FROM notes WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmtRecentNotes->bindParam(':uid', $current_user_id, PDO::PARAM_INT);
    $stmtRecentNotes->execute();
    $recent_notes = $stmtRecentNotes->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recent todos (limited by pagination)
    $stmtRecentTodos = $conn->prepare("SELECT id, task, status, priority, created_at FROM todos WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmtRecentTodos->bindParam(':uid', $current_user_id, PDO::PARAM_INT);
    $stmtRecentTodos->execute();
    $recent_todos = $stmtRecentTodos->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Tambahkan di head section -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Navbar (shared) -->
    <?php include 'navbar.php'; ?>

    <!-- Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Welcome, <?php echo $user['username']; ?>!</h2>
                <p class="lead">Selamat datang di aplikasi Sticky Notes dan Todo List.</p>
                
                <div class="row mt-4 align-items-stretch g-3" id="sticky-notes-card">
                    <div class="col-md-4 d-flex">
                        <div class="card card-soft-blue mb-3 h-100 w-100 shadow-sm dashboard-main-card">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Sticky Notes</h5>
                                <p class="card-text">Buat catatan cepat dengan sticky notes yang bisa diatur warna dan posisinya.</p>
                                <div class="mt-auto d-flex justify-content-end align-items-center gap-2">
                                    <span class="badge bg-light text-primary">Notes: <?php echo $note_count; ?></span>
                                    <a href="?page=notes" class="btn btn-light btn-sm" title="Go to Notes">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex">
                        <div class="card card-soft-green mb-3 h-100 w-100 shadow-sm dashboard-main-card">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Todo List</h5>
                                <p class="card-text">Kelola tugas Anda dengan sistem todo list yang lengkap dengan prioritas.</p>
                                <div class="mt-auto d-flex justify-content-end align-items-center gap-2">
                                    <span class="badge bg-light text-success">Todos: <?php echo $todo_count; ?></span>
                                    <a href="?page=todos" class="btn btn-light btn-sm" title="Go to Todos">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if(isAdmin()): ?>
                    <div class="col-md-4 d-flex">
                        <div class="card card-soft-slate mb-3 h-100 w-100 shadow-sm dashboard-main-card">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">User Management</h5>
                                <p class="card-text">Kelola pengguna dan role untuk aplikasi.</p>
                                <div class="mt-auto d-flex justify-content-end align-items-center gap-2">
                                    <span class="badge bg-light text-dark">Users: <?php echo $user_count; ?></span>
                                    <a href="?page=users" class="btn btn-light btn-sm" title="Manage Users">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- User Info and Activity Log Row -->
                <div class="row mt-4 g-3" id="user-info-section">
                    <!-- User Information (1/3) -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header" style="background: linear-gradient(135deg, #eef4ff, #dbe7ff); color: #12305b; border-bottom: 1px solid #c8d8ff;">
                                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>User Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 40%;">Username</th>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td><span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Member Since</th>
                                        <td><?php echo date('d F Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Quick Stats</th>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $note_count; ?> Notes</span>
                                            <span class="badge bg-success ms-1"><?php echo $todo_count; ?> Todos</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Log (2/3) -->
                    <div class="col-md-8">
                        <div class="card h-100" id="activity-card-section">
                            <div class="card-header" style="background: linear-gradient(135deg, #f2f3f7, #e4e7ee); color: #1f2733; border-bottom: 1px solid #d4d8e0;">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
                            </div>
                            <div class="card-body p-0 position-relative">
                                <!-- Loading Overlay -->
                                <div id="activity-loading" class="activity-loading-overlay" style="display: none;">
                                    <div class="activity-loading-content">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">Loading activities...</div>
                                    </div>
                                </div>
                                <div class="activity-log">
                                    <?php
                                    // Merge and sort notes and todos by created_at
                                    $activities = [];
                                    
                                    if (!empty($recent_notes)) {
                                        foreach ($recent_notes as $note) {
                                            $activities[] = [
                                                'type' => 'note',
                                                'title' => $note['title'],
                                                'id' => $note['id'],
                                                'created_at' => $note['created_at']
                                            ];
                                        }
                                    }
                                    
                                    if (!empty($recent_todos)) {
                                        foreach ($recent_todos as $todo) {
                                            $activities[] = [
                                                'type' => 'todo',
                                                'title' => $todo['task'],
                                                'id' => $todo['id'],
                                                'status' => $todo['status'],
                                                'priority' => $todo['priority'],
                                                'created_at' => $todo['created_at']
                                            ];
                                        }
                                    }
                                    
                                    // Sort by created_at descending
                                    usort($activities, function($a, $b) {
                                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                                    });
                                    
                                    // Apply pagination
                                    $total_items = count($activities);
                                    $total_pages = ceil($total_items / $per_page);
                                    $page = min($page, max(1, $total_pages)); // Ensure page is within valid range
                                    $activities_page = array_slice($activities, $offset, $per_page);
                                    
                                    if (empty($activities)):
                                    ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                                            <p class="mt-2 mb-0">No recent activity</p>
                                            <small>Start creating notes or todos!</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($activities_page as $activity): ?>
                                            <div class="activity-item d-flex align-items-start">
                                                <div class="activity-icon <?php echo $activity['type']; ?> me-3">
                                                    <?php if ($activity['type'] === 'note'): ?>
                                                        <i class="bi bi-sticky-fill"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-check-circle-fill"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong class="d-block">
                                                                <?php if ($activity['type'] === 'note'): ?>
                                                                    <a href="?page=notes" class="text-decoration-none">
                                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="?page=todos" class="text-decoration-none">
                                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </strong>
                                                            <small class="text-muted">
                                                                <?php if ($activity['type'] === 'note'): ?>
                                                                    Created a note
                                                                <?php else: ?>
                                                                    Created a todo
                                                                    <?php
                                                                    $statusBadge = [
                                                                        'pending' => 'secondary',
                                                                        'in_progress' => 'warning',
                                                                        'completed' => 'success'
                                                                    ];
                                                                    $priorityBadge = [
                                                                        'low' => 'info',
                                                                        'medium' => 'primary',
                                                                        'high' => 'danger'
                                                                    ];
                                                                    ?>
                                                                    <span class="badge bg-<?php echo $statusBadge[$activity['status']] ?? 'secondary'; ?> ms-1" style="font-size: 0.7rem;">
                                                                        <?php echo ucfirst(str_replace('_', ' ', $activity['status'])); ?>
                                                                    </span>
                                                                    <span class="badge bg-<?php echo $priorityBadge[$activity['priority']] ?? 'secondary'; ?> ms-1" style="font-size: 0.7rem;">
                                                                        <?php echo ucfirst($activity['priority']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <span class="activity-time"><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($activities) && $total_pages > 1): ?>
                            <div class="card-footer bg-light" id="activity-log-section">
                                <nav aria-label="Activity pagination">
                                    <ul class="pagination pagination-sm mb-0 justify-content-center" id="activity-pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=dashboard&p=<?php echo $page - 1; ?>" data-page="<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=dashboard&p=1" data-page="1">1</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active = $i == $page ? 'active' : '';
                                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=dashboard&p=' . $i . '" data-page="' . $i . '">' . $i . '</a></li>';
                                        }
                                        
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?page=dashboard&p=' . $total_pages . '" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=dashboard&p=<?php echo $page + 1; ?>" data-page="<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Showing <?php echo min($offset + 1, $total_items); ?>-<?php echo min($offset + $per_page, $total_items); ?> of <?php echo $total_items; ?> activities</small>
                                    </div>
                                </nav>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/dashboard.custom.js"></script>
    <?php if (isset($_SESSION['toast_type']) && isset($_SESSION['toast_message'])): ?>
    <script>
        <?php if ($_SESSION['toast_type'] === 'success'): ?>
            showSuccessToast(<?php echo json_encode($_SESSION['toast_message']); ?>);
        <?php else: ?>
            showErrorToast(<?php echo json_encode($_SESSION['toast_message']); ?>);
        <?php endif; ?>
    </script>
    <?php unset($_SESSION['toast_type'], $_SESSION['toast_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['show_welcome_toast']) && $_SESSION['show_welcome_toast']): ?>
    <script>
        showWelcomeToast();
    </script>
    <?php unset($_SESSION['show_welcome_toast']); ?>
    <?php endif; ?>
    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>