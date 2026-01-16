<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(!isLoggedIn()) {
    redirect('../index.php?page=login');
}

// Allow access if admin or if currently viewing as user (has original_role = admin)
$isActualAdmin = isAdmin() || (isset($_SESSION['original_role']) && $_SESSION['original_role'] === 'admin');

if(!$isActualAdmin) {
    setToast('error', 'Access denied. Admin only.');
    redirect('../index.php?page=dashboard');
}

$auth = new Auth();

// Handle return as admin
if(isset($_GET['return_as_admin']) && $_GET['return_as_admin'] == 1) {
    if($auth->returnAsAdmin()) {
        setToast('success', 'Returned to admin account');
    } else {
        setToast('error', 'Failed to return to admin account');
    }
    redirect('../index.php?page=users');
}

// Handle AJAX actions
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    switch($action) {
        case 'create':
            $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
            $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
            $password = $_POST['password'] ?? '';
            $role = isset($_POST['role']) ? sanitize($_POST['role']) : 'user';
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1; // is_aktif

            if(!$username || !$email || !$password) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }
            if(!$auth->validatePasswordStrength($password)) {
                echo json_encode(['success' => false, 'message' => 'Password too weak.']);
                exit();
            }

            try {
                // Admin-created users: auto-approved, but can set active/inactive via is_aktif ($status)
                $success = $auth->register($username, $email, $password, $role, 1, $status);
                if($success) {
                    echo json_encode(['success' => true, 'message' => 'User created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Username atau email sudah terdaftar']);
                }
            } catch (PDOException $e) {
                if($e->getCode() === '23000') {
                    echo json_encode(['success' => false, 'message' => 'Username atau email sudah terdaftar']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]);
            }
            exit();

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
            $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
            $role = isset($_POST['role']) ? sanitize($_POST['role']) : null;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : null; // is_aktif
            if(!$id || !$username || !$email) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }
            $success = $auth->updateUser($id, $username, $email, $role, $status);
            echo json_encode(['success' => $success, 'message' => $success ? 'User updated successfully' : 'Failed to update user']);
            exit();

        case 'change_password':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = $_POST['new_password'] ?? '';
            if(!$auth->validatePasswordStrength($password)) {
                echo json_encode(['success' => false, 'message' => 'Password too weak.']);
                exit();
            }
            $success = $auth->changePassword($id, $password);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Password changed successfully' : 'Failed to change password'
            ]);
            exit();

        case 'generate_password':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = $auth->generatePassword();
            $success = $auth->changePassword($id, $password);
            echo json_encode(['success' => $success, 'password' => $password, 'message' => $success ? 'Password generated successfully' : 'Failed to generate password']);
            exit();

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if($id === getCurrentUserId()) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                exit();
            }
            $success = $auth->deleteUser($id);
            echo json_encode(['success' => $success, 'message' => $success ? 'User deleted successfully' : 'Failed to delete user']);
            exit();

        case 'approve':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $success = $auth->approveUser($id, getCurrentUserId());
            echo json_encode(['success' => $success, 'message' => $success ? 'User approved successfully' : 'Failed to approve user']);
            exit();

        case 'reject':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $success = $auth->rejectUser($id);
            echo json_encode(['success' => $success, 'message' => $success ? 'User rejected successfully' : 'Failed to reject user']);
            exit();

        case 'view_as_user':
            $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $success = $auth->viewAsUser($user_id);
            echo json_encode(['success' => $success, 'message' => $success ? 'Switched to user view' : 'Failed to switch user view']);
            exit();

        case 'import_xlsx':
            if(!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit();
            }
            
            require 'vendor/autoload.php';
            
            try {
                $file = $_FILES['xlsx_file']['tmp_name'];
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                $imported = 0;
                $errors = [];
                
                // Skip header row
                array_shift($rows);
                
                foreach($rows as $index => $row) {
                    $rowNum = $index + 2; // +2 because we skipped header and arrays are 0-indexed
                    
                    if(empty($row[0]) && empty($row[1])) {
                        continue; // Skip empty rows
                    }
                    
                    if(count($row) < 4) {
                        $errors[] = "Row $rowNum: Invalid format";
                        continue;
                    }
                    
                    $username = sanitize(trim($row[0]));
                    $email = sanitize(trim($row[1]));
                    $password = trim($row[2]);
                    $role = sanitize(trim($row[3]));
                    $status = isset($row[4]) ? (int)trim($row[4]) : 1;
                    
                    if(empty($username) || empty($email) || empty($password)) {
                        $errors[] = "Row $rowNum: Missing required fields";
                        continue;
                    }
                    
                    if(!$auth->validatePasswordStrength($password)) {
                        $errors[] = "Row $rowNum: Password too weak for $username";
                        continue;
                    }
                    
                    try {
                        $success = $auth->register($username, $email, $password, $role, 1, $status);
                        if($success) {
                            $imported++;
                        } else {
                            $errors[] = "Row $rowNum: Failed to import $username (duplicate?)";
                        }
                    } catch(Exception $e) {
                        $errors[] = "Row $rowNum: " . $e->getMessage();
                    }
                }
                
                $message = "Imported $imported users";
                if(!empty($errors)) {
                    $message .= ". Errors: " . implode(', ', array_slice($errors, 0, 5));
                    if(count($errors) > 5) {
                        $message .= " (and " . (count($errors) - 5) . " more)";
                    }
                }
                
                echo json_encode([
                    'success' => $imported > 0,
                    'message' => $message,
                    'imported' => $imported,
                    'errors' => count($errors)
                ]);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to read Excel file: ' . $e->getMessage()]);
            }
            exit();
    }
}

// Handle Export to XLSX
if(isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    require 'vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set title
    $sheet->setCellValue('A1', 'User List - Todo Talenta Digital');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Set generated date
    $sheet->setCellValue('A2', 'Generated: ' . date('d M Y H:i:s'));
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Set header row
    $headers = ['#', 'Username', 'Email', 'Role', 'Status', 'Approval', 'Created'];
    $col = 'A';
    foreach($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $col++;
    }
    
    // Style header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ];
    $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
    
    // Add data
    $all_users = $auth->getAllUsers();
    $row = 5;
    foreach($all_users as $idx => $u) {
        $sheet->setCellValue('A' . $row, ($idx + 1));
        $sheet->setCellValue('B' . $row, $u['username']);
        $sheet->setCellValue('C' . $row, $u['email']);
        $sheet->setCellValue('D' . $row, ucfirst($u['role']));
        $status = isset($u['is_aktif']) && $u['is_aktif'] == 1 ? 'Active' : 'Inactive';
        $sheet->setCellValue('E' . $row, $status);
        $approval = isset($u['is_approved']) && $u['is_approved'] == 1 ? 'Approved' : 'Pending';
        $sheet->setCellValue('F' . $row, $approval);
        $sheet->setCellValue('G' . $row, date('d M Y', strtotime($u['created_at'])));
        $row++;
    }
    
    // Auto-size columns
    foreach(range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set borders
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A4:G' . ($row - 1))->applyFromArray($styleArray);
    
    // Output file
    $filename = 'users_' . date('Y-m-d_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Handle Download XLSX Template
if(isset($_GET['download_template']) && $_GET['download_template'] == 1) {
    require 'vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = ['username', 'email', 'password', 'role', 'status'];
    $col = 'A';
    foreach($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ];
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
    
    // Add example data
    $sheet->setCellValue('A2', 'john_doe');
    $sheet->setCellValue('B2', 'john@example.com');
    $sheet->setCellValue('C2', 'Password123');
    $sheet->setCellValue('D2', 'user');
    $sheet->setCellValue('E2', '1');
    
    $sheet->setCellValue('A3', 'jane_admin');
    $sheet->setCellValue('B3', 'jane@example.com');
    $sheet->setCellValue('C3', 'AdminPass456');
    $sheet->setCellValue('D3', 'admin');
    $sheet->setCellValue('E3', '1');
    
    $sheet->setCellValue('A4', 'inactive_user');
    $sheet->setCellValue('B4', 'inactive@example.com');
    $sheet->setCellValue('C4', 'Test123456');
    $sheet->setCellValue('D4', 'user');
    $sheet->setCellValue('E4', '0');
    
    // Auto-size columns
    foreach(range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="users_import_template.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

$users = $auth->getAllUsers();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$display_users = $users;

// Apply filters
$display_users = array_values(array_filter($users, function($u) use ($search, $filter_role, $filter_status) {
    $match = true;
    
    // Search filter
    if($search !== '') {
        $match = $match && (stripos($u['username'], $search) !== false || stripos($u['email'], $search) !== false);
    }
    
    // Role filter
    if($filter_role !== '') {
        $match = $match && ($u['role'] === $filter_role);
    }
    
    // Status filter
    if($filter_status !== '') {
        $isAktif = isset($u['is_aktif']) ? (int)$u['is_aktif'] : 1;
        $match = $match && ($isAktif === (int)$filter_status);
    }
    
    return $match;
}));

// Pagination for All Users
$all_users_per_page = 5;
$all_users_total = count($display_users);
$all_users_total_pages = max(1, (int)ceil($all_users_total / $all_users_per_page));
$all_users_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$all_users_page = min($all_users_page, $all_users_total_pages);
$all_users_offset = ($all_users_page - 1) * $all_users_per_page;
$display_users_paged = array_slice($display_users, $all_users_offset, $all_users_per_page);

$pending_users = $auth->getPendingUsers();
$pending_per_page = 6;
$pending_total = count($pending_users);
$pending_total_pages = max(1, (int)ceil($pending_total / $pending_per_page));
$pending_page = isset($_GET['pending_page']) ? max(1, (int)$_GET['pending_page']) : 1;
$pending_page = min($pending_page, $pending_total_pages);
$pending_offset = ($pending_page - 1) * $pending_per_page;
$pending_users_paged = array_slice($pending_users, $pending_offset, $pending_per_page);

$approved_users = $auth->getApprovedUsers();
$approved_per_page = 6;
$approved_total = count($approved_users);
$approved_total_pages = max(1, (int)ceil($approved_total / $approved_per_page));
$approved_page = isset($_GET['approved_page']) ? max(1, (int)$_GET['approved_page']) : 1;
$approved_page = min($approved_page, $approved_total_pages);
$approved_offset = ($approved_page - 1) * $approved_per_page;
$approved_users_paged = array_slice($approved_users, $approved_offset, $approved_per_page);

// Inactive users (is_aktif = 0)
$inactive_users = $auth->getInactiveUsers();
$inactive_per_page = 6;
$inactive_total = count($inactive_users);
$inactive_total_pages = max(1, (int)ceil($inactive_total / $inactive_per_page));
$inactive_page = isset($_GET['inactive_page']) ? max(1, (int)$_GET['inactive_page']) : 1;
$inactive_page = min($inactive_page, $inactive_total_pages);
$inactive_offset = ($inactive_page - 1) * $inactive_per_page;
$inactive_users_paged = array_slice($inactive_users, $inactive_offset, $inactive_per_page);

// Determine active tab - default to 'all-users'
if(isset($_GET['pending_page'])) {
    $activeTab = 'pending';
} elseif(isset($_GET['approved_page'])) {
    $activeTab = 'approved';
} elseif(isset($_GET['inactive_page'])) {
    $activeTab = 'inactive';
} else {
    $activeTab = 'all-users';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/users.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 user-header-wrapper">
            <div>
                <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> User Management</h2>
                <p class="text-muted mb-0">Kelola pengguna, peran, dan approval registrasi</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-soft-green" id="importXlsxBtn">
                    <i class="bi bi-upload me-1"></i> Import Excel
                </button>
                <a href="?page=users&export=xlsx" class="btn btn-soft-green" target="_blank">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <button class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;" id="addUserBtn">
                    <i class="bi bi-person-plus me-1"></i> Add User
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-blue stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Users</h6>
                                <h3 class="mb-0"><?php echo count($users); ?></h3>
                            </div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-yellow stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Pending</h6>
                                <h3 class="mb-0"><?php echo count($pending_users); ?></h3>
                            </div>
                            <i class="fas fa-hourglass-half stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card card-soft-green stat-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Approved</h6>
                                <h3 class="mb-0"><?php echo count($approved_users); ?></h3>
                            </div>
                            <i class="fas fa-check-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #fff0f0, #ffe0e0); color: #7c1d1d; border: 1px solid #ffc0c0;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Admins</h6>
                                <h3 class="mb-0"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></h3>
                            </div>
                            <i class="fas fa-user-shield stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'all-users' ? 'active' : ''; ?>" id="all-users-tab" data-bs-toggle="pill" data-bs-target="#all-users" type="button" role="tab">
                    <i class="fas fa-users"></i> All Users
                    <span class="badge bg-primary"><?php echo count($users); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'pending' ? 'active' : ''; ?>" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button" role="tab">
                    <i class="fas fa-hourglass-half"></i> Pending Approvals
                    <span class="badge bg-warning text-dark"><?php echo count($pending_users); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'approved' ? 'active' : ''; ?>" id="approved-tab" data-bs-toggle="pill" data-bs-target="#approved" type="button" role="tab">
                    <i class="fas fa-check-circle"></i> Approved Users
                    <span class="badge bg-success"><?php echo count($approved_users); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'inactive' ? 'active' : ''; ?>" id="inactive-tab" data-bs-toggle="pill" data-bs-target="#inactive" type="button" role="tab">
                    <i class="fas fa-user-slash"></i> Inactive Users
                    <span class="badge bg-secondary"><?php echo count($inactive_users); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="userTabsContent">
            <!-- All Users Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'all-users' ? 'show active' : ''; ?>" id="all-users" role="tabpanel">
                <div class="card mb-3" id="users-filter-box">
                    <div class="card-body">
                        <form class="row g-2" method="GET" id="users-filter-form">
                            <input type="hidden" name="page" value="users">
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search username or email...">
                            </div>
                            <div class="col-sm-3 col-md-2">
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?php echo $filter_role === 'user' ? 'selected' : ''; ?>>User</option>
                                </select>
                            </div>
                            <div class="col-sm-3 col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $filter_status === '1' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo $filter_status === '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-sm-auto d-flex align-items-center gap-2">
                                <button type="submit" class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;"><i class="bi bi-search"></i> Filter</button>
                                <?php if($search !== '' || $filter_role !== '' || $filter_status !== ''): ?>
                                    <a href="?page=users" class="btn btn-soft-slate" id="clear-filter-btn"><i class="bi bi-x-circle"></i> Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card" id="users-table-card">
                    <div class="card-body table-responsive position-relative">
                        <!-- Loading Overlay -->
                        <div id="users-loading" class="users-loading-overlay" style="display: none;">
                            <div class="users-loading-content">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-2">Loading users data...</div>
                            </div>
                        </div>
                        <div id="users-table-wrapper">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status (Aktif)</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($display_users_paged)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($display_users_paged as $idx => $u): ?>
                                    <tr>
                                        <td><?php echo ($all_users_offset + $idx + 1); ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'info'; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                        <td>
                                            <?php $isAktif = isset($u['is_aktif']) ? (int)$u['is_aktif'] : 1; ?>
                                            <?php if($isAktif === 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <?php if(isset($u['is_approved']) && $u['is_approved'] == 0): ?>
                                                    <button class="btn btn-sm btn-soft-slate text-success approve-user-btn" 
                                                            data-id="<?php echo (int)$u['id']; ?>" 
                                                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                            title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-soft-slate edit-user" 
                                                        data-id="<?php echo (int)$u['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>" 
                                                        data-email="<?php echo htmlspecialchars($u['email']); ?>" 
                                                        data-role="<?php echo htmlspecialchars($u['role']); ?>"
                                                    data-status="<?php echo isset($u['is_aktif']) ? (int)$u['is_aktif'] : 1; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-soft-slate change-password" 
                                                        data-id="<?php echo (int)$u['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                                <button class="btn btn-sm btn-soft-slate view-as-user" 
                                                        data-id="<?php echo (int)$u['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                        title="View as this user">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-soft-slate generate-password" 
                                                        data-id="<?php echo (int)$u['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>">
                                                    <i class="bi bi-shuffle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-soft-slate text-danger delete-user" 
                                                        data-id="<?php echo (int)$u['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                <?php if($all_users_total_pages > 1): ?>
                    <nav aria-label="All users pagination" class="mt-3" id="users-pagination">
                        <ul class="pagination justify-content-center mb-0" id="users-pagination-list">
                            <li class="page-item <?php echo $all_users_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=users&p=<?php echo max(1, $all_users_page - 1); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $filter_role ? '&role='.urlencode($filter_role) : ''; ?><?php echo $filter_status !== '' ? '&status='.urlencode($filter_status) : ''; ?>" data-page="<?php echo max(1, $all_users_page - 1); ?>">Prev</a>
                            </li>
                            <?php 
                            $start = max(1, $all_users_page - 2);
                            $end = min($all_users_total_pages, $all_users_page + 2);
                            if($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=users&p=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $filter_role ? '&role='.urlencode($filter_role) : ''; ?><?php echo $filter_status !== '' ? '&status='.urlencode($filter_status) : ''; ?>" data-page="1">1</a></li>
                                <?php if($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <?php endif; ?>
                            <?php for($p = $start; $p <= $end; $p++): ?>
                                <li class="page-item <?php echo $p == $all_users_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=users&p=<?php echo $p; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $filter_role ? '&role='.urlencode($filter_role) : ''; ?><?php echo $filter_status !== '' ? '&status='.urlencode($filter_status) : ''; ?>" data-page="<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if($end < $all_users_total_pages): ?>
                                <?php if($end < $all_users_total_pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link" href="?page=users&p=<?php echo $all_users_total_pages; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $filter_role ? '&role='.urlencode($filter_role) : ''; ?><?php echo $filter_status !== '' ? '&status='.urlencode($filter_status) : ''; ?>" data-page="<?php echo $all_users_total_pages; ?>"><?php echo $all_users_total_pages; ?></a></li>
                            <?php endif; ?>
                            <li class="page-item <?php echo $all_users_page >= $all_users_total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=users&p=<?php echo min($all_users_total_pages, $all_users_page + 1); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $filter_role ? '&role='.urlencode($filter_role) : ''; ?><?php echo $filter_status !== '' ? '&status='.urlencode($filter_status) : ''; ?>" data-page="<?php echo min($all_users_total_pages, $all_users_page + 1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Pending Approvals Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'pending' ? 'show active' : ''; ?>" id="pending" role="tabpanel">
                <?php if(empty($pending_users_paged)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p class="mt-3">No pending registrations</p>
                        <p class="text-muted">All users have been reviewed!</p>
                    </div>
                <?php else: ?>
                    <div style="position: relative;">
                        <div id="pending-loading" class="users-loading-overlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    <div id="pending-cards-wrapper" class="row">
                        <?php foreach($pending_users_paged as $user): ?>
                            <div class="col-md-4 mb-3">
                                <div class="approval-card card" style="border-left: 4px solid #ffc107;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3 mb-3">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1">
                                                    <i class="fas fa-user text-primary me-2"></i><?php echo htmlspecialchars($user['username']); ?>
                                                </h5>
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar me-1"></i> Registered: <?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-soft-green approve-btn flex-fill" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-soft-slate reject-btn flex-fill" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-times me-1"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if($pending_total_pages > 1): ?>
                        <nav aria-label="Pending users pagination" class="mt-3" id="pending-pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $pending_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&pending_page=<?php echo max(1, $pending_page - 1); ?>">Prev</a>
                                </li>
                                <?php 
                                $start = max(1, $pending_page - 2);
                                $end = min($pending_total_pages, $pending_page + 2);
                                if($start > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=users&pending_page=1">1</a></li>
                                    <?php if($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <?php endif; ?>
                                <?php for($p = $start; $p <= $end; $p++): ?>
                                    <li class="page-item <?php echo $p == $pending_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=users&pending_page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if($end < $pending_total_pages): ?>
                                    <?php if($end < $pending_total_pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="?page=users&pending_page=<?php echo $pending_total_pages; ?>"><?php echo $pending_total_pages; ?></a></li>
                                <?php endif; ?>
                                <li class="page-item <?php echo $pending_page >= $pending_total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&pending_page=<?php echo min($pending_total_pages, $pending_page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Approved Users Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'approved' ? 'show active' : ''; ?>" id="approved" role="tabpanel">
                <?php if(empty($approved_users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-clock"></i>
                        <p class="mt-3">No approved users yet</p>
                        <p class="text-muted">Start approving pending registrations</p>
                    </div>
                <?php else: ?>
                    <div style="position: relative;">
                        <div id="approved-loading" class="users-loading-overlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    <div id="approved-cards-wrapper" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php foreach($approved_users_paged as $user): ?>
                            <div class="col">
                                <div class="approval-card card" style="border-left: 4px solid #28a745;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1">
                                                    <i class="fas fa-user text-success me-2"></i><?php echo htmlspecialchars($user['username']); ?>
                                                </h5>
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php if(!empty($user['approved_at'])): ?>
                                                        <i class="fas fa-check-circle text-success me-1"></i> Approved: <?php echo date('d M Y, H:i', strtotime($user['approved_at'])); ?>
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle text-success me-1"></i> Approved: -
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-success">Approved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if($approved_total_pages > 1): ?>
                        <nav aria-label="Approved users pagination" class="mt-3" id="approved-pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $approved_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&approved_page=<?php echo max(1, $approved_page - 1); ?>">Prev</a>
                                </li>
                                <?php for($p = 1; $p <= $approved_total_pages; $p++): ?>
                                    <li class="page-item <?php echo $p == $approved_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=users&approved_page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $approved_page >= $approved_total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&approved_page=<?php echo min($approved_total_pages, $approved_page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Inactive Users Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'inactive' ? 'show active' : ''; ?>" id="inactive" role="tabpanel">
                <?php if(empty($inactive_users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-check"></i>
                        <p class="mt-3">No inactive users</p>
                        <p class="text-muted">All users are currently active!</p>
                    </div>
                <?php else: ?>
                    <div style="position: relative;">
                        <div id="inactive-loading" class="users-loading-overlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    <div id="inactive-cards-wrapper" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php foreach($inactive_users_paged as $user): ?>
                            <div class="col">
                                <div class="approval-card card" style="border-left: 4px solid #6c757d;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3 mb-3">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1">
                                                    <i class="fas fa-user-slash text-secondary me-2"></i><?php echo htmlspecialchars($user['username']); ?>
                                                </h5>
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-user-tag me-1"></i> Role: <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>"><?php echo ucfirst($user['role']); ?></span>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar me-1"></i> Member Since: <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary">Inactive</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-soft-slate edit-user flex-fill"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo $user['role']; ?>"
                                                    data-status="<?php echo $user['is_aktif']; ?>">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-soft-slate text-danger delete-user flex-fill"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if($inactive_total_pages > 1): ?>
                        <nav aria-label="Inactive users pagination" class="mt-3" id="inactive-pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $inactive_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&inactive_page=<?php echo max(1, $inactive_page - 1); ?>">Prev</a>
                                </li>
                                <?php
                                $start_page = max(1, $inactive_page - 2);
                                $end_page = min($inactive_total_pages, $inactive_page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=users&inactive_page=1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active = $i == $inactive_page ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=users&inactive_page=' . $i . '">' . $i . '</a></li>';
                                }
                                
                                if ($end_page < $inactive_total_pages) {
                                    if ($end_page < $inactive_total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=users&inactive_page=' . $inactive_total_pages . '">' . $inactive_total_pages . '</a></li>';
                                }
                                ?>
                                <li class="page-item <?php echo $inactive_page >= $inactive_total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=users&inactive_page=<?php echo min($inactive_total_pages, $inactive_page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #e9f9ef, #d5f2e0); color: #19492f; border-bottom: 1px solid #c6e7d2;">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="add_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="add_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="password" id="add_password" required>
                                <span class="password-toggle" data-target="add_password"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="add_confirm_password" required>
                                <span class="password-toggle" data-target="add_confirm_password"><i class="fas fa-eye"></i></span>
                            </div>
                            <div id="addPasswordMatch" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="add_role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Aktif</label>
                            <select class="form-select" name="status" id="add_status">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-soft-green">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #eef4ff, #dbe7ff); color: #12305b; border-bottom: 1px solid #c8d8ff;">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Aktif</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #fff6e6, #ffe8c7); color: #5c3b0a; border-bottom: 1px solid #f3d7a2;">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Change Password for <span id="passwordUsername"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm">
                    <input type="hidden" name="id" id="password_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" required>
                                <span class="password-toggle" data-target="new_password"><i class="fas fa-eye"></i></span>
                            </div>
                            <div class="mt-2">
                                <div id="passwordStrength" class="password-strength-meter"></div>
                                <div id="passwordStrengthText" class="password-strength-text"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="confirm_password" required>
                                <span class="password-toggle" data-target="confirm_password"><i class="fas fa-eye"></i></span>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" style="color: #5c3b0a; background: linear-gradient(135deg, #fff6e6, #ffe8c7); border: 1px solid #f3d7a2;">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import XLSX Modal -->
    <div class="modal fade" id="importXlsxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #e9f9ef, #d5f2e0); color: #19492f; border-bottom: 1px solid #c6e7d2;">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Users from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="importXlsxForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Excel Format</h6>
                            <p class="mb-2">File Excel (.xlsx) harus memiliki kolom berikut pada row pertama:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>username</th>
                                            <th>email</th>
                                            <th>password</th>
                                            <th>role</th>
                                            <th>status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>john_doe</td>
                                            <td>john@example.com</td>
                                            <td>Pass123</td>
                                            <td>user</td>
                                            <td>1</td>
                                        </tr>
                                        <tr>
                                            <td>jane_admin</td>
                                            <td>jane@example.com</td>
                                            <td>Admin456</td>
                                            <td>admin</td>
                                            <td>1</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                            <ul class="mb-0 small">
                                <li><strong>username</strong>: Username (required)</li>
                                <li><strong>email</strong>: Email address (required)</li>
                                <li><strong>password</strong>: Password min. 6 characters (required)</li>
                                <li><strong>role</strong>: user atau admin (required)</li>
                                <li><strong>status</strong>: 1 = Active, 0 = Inactive (optional, default: 1)</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Excel File (.xlsx)</label>
                            <input type="file" class="form-control" name="xlsx_file" id="xlsx_file" accept=".xlsx,.xls" required>
                        </div>
                        <div class="mb-3">
                            <a href="?download_template=1" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download me-1"></i> Download Excel Template
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-soft-green">
                            <i class="bi bi-upload me-2"></i>Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/users.js"></script>
    <?php echo displayToast(); ?>

    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>