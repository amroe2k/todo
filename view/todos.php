<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(!isLoggedIn()) {
    redirect('../index.php?page=login');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

// CRUD Operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        $user_id = getCurrentUserId();
        
        switch($action) {
            case 'create':
                $task = isset($_POST['task']) ? sanitize($_POST['task']) : '';
                $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
                $priority = isset($_POST['priority']) ? sanitize($_POST['priority']) : 'medium';
                $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'pending';
                $due_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                
                // Auto-cleanup if activity log limit reached
                cleanupUserActivityLog($user_id, 'todos');
                
                $query = "INSERT INTO todos (user_id, task, description, priority, status, due_date) 
                          VALUES (:user_id, :task, :description, :priority, :status, :due_date)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':task', $task, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR);
                if($stmt->execute()) {
                    $todo_id = $conn->lastInsertId();
                    echo json_encode(['success' => true, 'id' => $todo_id, 'message' => 'Todo created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create todo']);
                }
                exit();
                
            case 'update':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $task = isset($_POST['task']) ? sanitize($_POST['task']) : '';
                $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
                $priority = isset($_POST['priority']) ? sanitize($_POST['priority']) : 'medium';
                $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'pending';
                $due_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                
                $query = "UPDATE todos SET task = :task, description = :description, 
                          priority = :priority, status = :status, due_date = :due_date 
                          WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':task', $task, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Todo updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update todo']);
                }
                exit();
                
            case 'update_status':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'pending';
                
                $query = "UPDATE todos SET status = :status WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                }
                exit();

            case 'archive':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                try {
                    $query = "UPDATE todos SET status = 'archived' WHERE id = :id AND user_id = :user_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    echo json_encode(['success' => true, 'message' => 'Todo archived']);
                } catch (PDOException $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to archive todo: ' . $e->getMessage()
                    ]);
                }
                exit();
                
            case 'delete':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                
                // First check if todo exists
                $checkQuery = "SELECT id FROM todos WHERE id = :id AND user_id = :user_id";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Todo not found or already deleted']);
                    exit();
                }
                
                $query = "DELETE FROM todos WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if($stmt->execute()) {
                    if($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Todo deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete todo. Please try again.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error. Please contact administrator.']);
                }
                exit();
        }
    }
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Pagination setup
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$per_page = 12; // 12 todos per page (3 columns x 4 rows)
$offset = ($page - 1) * $per_page;

// Get all todos for current user
// Hide archived by default unless explicitly requested
$query = "SELECT * FROM todos WHERE user_id = :user_id";

// Add filter conditions
switch($filter) {
    case 'pending':
        $query .= " AND status = 'pending'";
        break;
    case 'in_progress':
        $query .= " AND status = 'in_progress'";
        break;
    case 'completed':
        $query .= " AND status = 'completed'";
        break;
    case 'archived':
        $query .= " AND status = 'archived'";
        break;
    case 'high':
        $query .= " AND priority = 'high' AND status != 'archived'";
        break;
    case 'medium':
        $query .= " AND priority = 'medium' AND status != 'archived'";
        break;
    case 'low':
        $query .= " AND priority = 'low' AND status != 'archived'";
        break;
    case 'today':
        $query .= " AND DATE(due_date) = CURDATE() AND status != 'archived'";
        break;
    case 'overdue':
        $query .= " AND due_date < CURDATE() AND status != 'completed' AND status != 'archived'";
        break;
    case 'upcoming':
        $query .= " AND due_date > CURDATE() AND status != 'completed' AND status != 'archived'";
        break;
    default:
        $query .= " AND status != 'archived'";
}

// Add ordering: soonest due date first, push nulls last, then priority, then newest
$query .= " ORDER BY 
            CASE 
                WHEN due_date IS NULL THEN 1 
                ELSE 0 
            END,
            due_date ASC,
            CASE priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            created_at DESC";

// Get total count for pagination before adding LIMIT
$count_query = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
$count_stmt = $conn->prepare($count_query);
$current_user_id = getCurrentUserId();
$count_stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
$count_stmt->execute();
$total_todos = $count_stmt->fetchColumn();
$total_pages = ceil($total_todos / $per_page);

// Add pagination limit
$query .= " LIMIT :limit OFFSET :offset";

// Prepare and execute query
$stmt = $conn->prepare($query);
$current_user_id = getCurrentUserId();
$stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group todos by calendar week (Monday-Sunday) of due date; null due dates go to a separate bucket
$groupedTodos = [];
$groupOrder = [];
foreach ($todos as $todo) {
    if (!empty($todo['due_date'])) {
        $dueTs = strtotime($todo['due_date']);
        $weekStart = strtotime('monday this week', $dueTs);
        $weekEnd = strtotime('sunday this week', $dueTs);
        $label = 'Minggu ' . date('d M', $weekStart) . ' - ' . date('d M Y', $weekEnd);
    } else {
        $label = 'Tanpa Due Date';
    }

    if (!isset($groupedTodos[$label])) {
        $groupedTodos[$label] = [];
        $groupOrder[] = $label;
    }
    $groupedTodos[$label][] = $todo;
}

// Get stats
$query_stats = "SELECT 
                SUM(CASE WHEN status != 'archived' THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN priority = 'high' AND status != 'archived' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN priority = 'medium' AND status != 'archived' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN priority = 'low' AND status != 'archived' THEN 1 ELSE 0 END) as low
                FROM todos WHERE user_id = :user_id";
$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Initialize stats if null
if (!$stats || $stats['total'] === null) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/todos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Content -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-tasks text-primary"></i> Todo List
                </h2>
                <p class="text-muted mb-0">Manage your tasks efficiently</p>
            </div>
            <button class="btn btn-primary px-4 shadow-sm" style="border-radius: 10px; font-weight: 600; border: none; transition: all 0.2s ease;" 
                    onmouseover="this.style.backgroundColor='#0b5ed7'; this.style.transform='translateY(-1px)';" 
                    onmouseout="this.style.backgroundColor='#0d6efd'; this.style.transform='translateY(0)';"
                    data-bs-toggle="modal" data-bs-target="#addTodoModal">
                <i class="fas fa-plus-circle me-2"></i> Add New Todo
            </button>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-blue stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Total</h5>
                                <h2 class="mb-0"><?php echo (int)$stats['total']; ?></h2>
                            </div>
                            <i class="fas fa-list stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-amber stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Pending</h5>
                                <h2 class="mb-0"><?php echo (int)$stats['pending']; ?></h2>
                            </div>
                            <i class="fas fa-clock stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-cyan stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">In Progress</h5>
                                <h2 class="mb-0"><?php echo (int)$stats['in_progress']; ?></h2>
                            </div>
                            <i class="fas fa-spinner stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-green stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Completed</h5>
                                <h2 class="mb-0"><?php echo (int)$stats['completed']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="d-flex flex-wrap gap-2 mb-4" role="group">
            <a href="?page=todos&filter=all" class="btn btn-soft-slate <?php echo $filter == 'all' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group me-1"></i> All
            </a>
            <a href="?page=todos&filter=pending" class="btn btn-soft-amber <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock me-1"></i> Pending
            </a>
            <a href="?page=todos&filter=in_progress" class="btn btn-soft-cyan <?php echo $filter == 'in_progress' ? 'active' : ''; ?>">
                <i class="fas fa-spinner me-1"></i> In Progress
            </a>
            <a href="?page=todos&filter=completed" class="btn btn-soft-green <?php echo $filter == 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle me-1"></i> Completed
            </a>
            <a href="?page=todos&filter=high" class="btn btn-soft-rose <?php echo $filter == 'high' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle me-1"></i> High
            </a>
            <a href="?page=todos&filter=today" class="btn btn-soft-blue <?php echo $filter == 'today' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-day me-1"></i> Today
            </a>
            <a href="?page=todos&filter=overdue" class="btn btn-soft-slate <?php echo $filter == 'overdue' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-times me-1"></i> Overdue
            </a>
            <a href="?page=todos&filter=archived" class="btn btn-outline-secondary <?php echo $filter == 'archived' ? 'active' : ''; ?>">
                <i class="fas fa-archive me-1"></i> Archived
            </a>
        </div>
        
        <!-- Todo List -->
        <div class="card card-soft-slate">
            <div class="card-body">
                <?php if(empty($todos)): 
                    // Dynamic empty state message
                    $empty_title = "Belum ada tugas";
                    $empty_desc = "Mulai kelola produktivitas Anda dengan membuat tugas pertama.";
                    $empty_icon = "bi-clipboard2-plus";

                    switch($filter) {
                        case 'pending': $empty_title = "Tidak ada tugas tertunda"; $empty_desc = "Semua tugas Anda sudah mulai dikerjakan atau selesai. Bagus!"; break;
                        case 'in_progress': $empty_title = "Tidak ada tugas berjalan"; $empty_desc = "Pilih satu tugas dari daftar Pending untuk mulai dikerjakan."; break;
                        case 'completed': $empty_title = "Belum ada tugas selesai"; $empty_desc = "Selesaikan tugas Anda dan lihat daftar ini terisi dengan pencapaian."; break;
                        case 'high': $empty_title = "Tidak ada tugas prioritas"; $empty_desc = "Semua tugas penting Anda sudah ditangani atau belum ditandai."; break;
                        case 'today': $empty_title = "Jadwal kosong hari ini"; $empty_desc = "Nikmati waktu luang Anda atau rencanakan tugas untuk besok."; break;
                        case 'overdue': $empty_title = "Bebas tugas terlambat"; $empty_desc = "Hebat! Anda menyelesaikan semua tugas tepat waktu."; $empty_icon = "bi-check2-all"; break;
                        case 'archived': $empty_title = "Arsip kosong"; $empty_desc = "Tugas yang sudah tidak aktif akan muncul di sini jika diarsipkan."; $empty_icon = "bi-archive"; break;
                    }
                ?>
                        <div class="py-5 text-center">
                            <div class="mb-4">
                                <i class="bi <?php echo $empty_icon; ?> text-muted opacity-25" style="font-size: 4.5rem;"></i>
                                <h4 class="fw-bold mt-3" style="color: #12305b;"><?php echo $empty_title; ?></h4>
                                <p class="text-muted"><?php echo $empty_desc; ?></p>
                            </div>
                            
                            <?php if($filter !== 'archived'): ?>
                            <button class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                                    style="width: 60px; height: 60px; border: none; transition: all 0.2s ease-in-out;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addTodoModal"
                                    onmouseover="this.style.transform='scale(1.1)'; this.style.backgroundColor='#0b5ed7';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='#0d6efd';"
                                    title="Tambah Tugas Baru"
                                    aria-label="Tambah Tugas Baru">
                                <i class="bi bi-plus-lg" style="font-size: 1.5rem; color: white;"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                <?php else: ?>
                    <div class="todo-list-scroll-container">
                    <?php foreach($groupOrder as $weekLabel): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                            <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i><?php echo $weekLabel; ?></h5>
                            <span class="badge bg-light text-dark"><?php echo count($groupedTodos[$weekLabel]); ?> items</span>
                        </div>
                        <div class="row g-3">
                    <?php foreach($groupedTodos[$weekLabel] as $todo): 
                        // Determine due date class
                        $due_date_class = '';
                        $due_date_text = '';
                        if ($todo['due_date']) {
                            $due_date_timestamp = strtotime($todo['due_date']);
                            $today_timestamp = strtotime('today');
                            if ($due_date_timestamp < $today_timestamp && $todo['status'] != 'completed') {
                                $due_date_class = 'due-date-overdue';
                                $due_date_text = ' (Overdue)';
                            } elseif ($due_date_timestamp == $today_timestamp) {
                                $due_date_class = 'due-date-today';
                                $due_date_text = ' (Today)';
                            }
                        }
                    ?>
                    <div class="col-md-6">
                        <div class="todo-item card mb-3 <?php echo htmlspecialchars($todo['priority']); ?> <?php echo htmlspecialchars($todo['status']); ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 me-3">
                                        <div class="d-flex align-items-start">
                                            <div class="form-check checkbox-lg me-3">
                                                <input class="form-check-input status-checkbox" 
                                                       type="checkbox" 
                                                       data-id="<?php echo (int)$todo['id']; ?>"
                                                       <?php echo $todo['status'] == 'completed' ? 'checked' : ''; ?>>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="todo-task h5 mb-1"><?php echo htmlspecialchars($todo['task']); ?></div>
                                                <?php if($todo['description']): ?>
                                                <div class="todo-description mb-2"><?php echo nl2br(htmlspecialchars($todo['description'])); ?></div>
                                                <?php endif; ?>
                                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                                    <span class="status-badge badge bg-<?php 
                                                        switch($todo['status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'in_progress': echo 'info'; break;
                                                            case 'completed': echo 'success'; break;
                                                            case 'archived': echo 'secondary'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <i class="fas fa-<?php 
                                                            switch($todo['status']) {
                                                                case 'pending': echo 'clock'; break;
                                                                case 'in_progress': echo 'spinner'; break;
                                                                case 'completed': echo 'check-circle'; break;
                                                                case 'archived': echo 'archive'; break;
                                                                default: echo 'circle';
                                                            }
                                                        ?> me-1"></i>
                                                        <?php echo ucfirst(str_replace('_', ' ', $todo['status'])); ?>
                                                    </span>
                                                    <span class="priority-badge badge bg-<?php 
                                                        switch($todo['priority']) {
                                                            case 'high': echo 'danger'; break;
                                                            case 'medium': echo 'warning'; break;
                                                            case 'low': echo 'success'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <i class="fas fa-<?php 
                                                            switch($todo['priority']) {
                                                                case 'high': echo 'exclamation-triangle'; break;
                                                                case 'medium': echo 'equalizer'; break;
                                                                case 'low': echo 'arrow-down'; break;
                                                                default: echo 'circle';
                                                            }
                                                        ?> me-1"></i>
                                                        <?php echo ucfirst($todo['priority']); ?>
                                                    </span>
                                                    <?php if($todo['due_date']): ?>
                                                    <span class="badge bg-secondary <?php echo $due_date_class; ?>">
                                                        <i class="fas fa-calendar-alt me-1"></i> 
                                                        <?php echo date('M d, Y', strtotime($todo['due_date'])); ?>
                                                        <?php echo $due_date_text; ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-calendar-plus me-1"></i>
                                                        <?php echo date('M d', strtotime($todo['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="todo-actions">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary action-btn edit-todo"
                                                    data-id="<?php echo (int)$todo['id']; ?>"
                                                    data-task="<?php echo htmlspecialchars($todo['task']); ?>"
                                                    data-description="<?php echo htmlspecialchars($todo['description']); ?>"
                                                    data-priority="<?php echo htmlspecialchars($todo['priority']); ?>"
                                                    data-status="<?php echo htmlspecialchars($todo['status']); ?>"
                                                    data-due_date="<?php echo htmlspecialchars($todo['due_date']); ?>"
                                                    title="Edit Todo">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($todo['status'] !== 'archived'): ?>
                                            <button class="btn btn-sm btn-outline-secondary action-btn archive-todo"
                                                    data-id="<?php echo (int)$todo['id']; ?>"
                                                    title="Archive Todo">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger action-btn delete-todo"
                                                    data-id="<?php echo (int)$todo['id']; ?>"
                                                    title="Delete Todo">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing <?php echo min($offset + 1, $total_todos); ?>-<?php echo min($offset + $per_page, $total_todos); ?> of <?php echo $total_todos; ?> todos
                        </div>
                        <nav aria-label="Todo pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=todos&filter=<?php echo $filter; ?>&p=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=todos&filter=' . $filter . '&p=1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active = $i == $page ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=todos&filter=' . $filter . '&p=' . $i . '">' . $i . '</a></li>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=todos&filter=' . $filter . '&p=' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=todos&filter=<?php echo $filter; ?>&p=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Todo Modal -->
    <div class="modal fade" id="addTodoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #eef4ff, #dbe7ff); color: #12305b; border-bottom: 1px solid #c8d8ff;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i> Add New Todo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTodoForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="task" class="form-label">
                                <i class="fas fa-tasks me-1"></i> Task <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="task" name="task" required maxlength="255" placeholder="What needs to be done?">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i> Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Add details about this task..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-flag me-1"></i> Priority
                                </label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-spinner me-1"></i> Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" selected>Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i> Due Date
                            </label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   min="<?php echo date('Y-m-d'); ?>">
                            <div class="form-text">Leave empty for no due date</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;">
                            <i class="fas fa-save me-2"></i> Save Todo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Todo Modal -->
    <div class="modal fade" id="editTodoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #fff6e6, #ffe8c7); color: #5c3b0a; border-bottom: 1px solid #f3d7a2;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Edit Todo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTodoForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_task" class="form-label">
                                <i class="fas fa-tasks me-1"></i> Task <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_task" name="task" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">
                                <i class="fas fa-align-left me-1"></i> Description
                            </label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="1000"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_priority" class="form-label">
                                    <i class="fas fa-flag me-1"></i> Priority
                                </label>
                                <select class="form-select" id="edit_priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">
                                    <i class="fas fa-spinner me-1"></i> Status
                                </label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_due_date" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i> Due Date
                            </label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date">
                            <div class="form-text">Leave empty for no due date</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="color: #5c3b0a; background: linear-gradient(135deg, #fff6e6, #ffe8c7); border: 1px solid #f3d7a2;">
                            <i class="fas fa-save me-2"></i> Update Todo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/todos.js"></script>
    <script src="../js/todos.custom.js"></script>
    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>
