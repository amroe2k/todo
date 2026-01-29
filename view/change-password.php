<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('../index.php?page=login');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password baru dan konfirmasi tidak sama.';
    } elseif (!$auth->validatePasswordStrength($newPassword)) {
        $error = 'Password harus minimal 6 karakter.';
    } else {
        try {
            $stmt = $conn->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error = 'Data pengguna tidak ditemukan.';
            } elseif (!password_verify($currentPassword, $row['password'])) {
                $error = 'Password saat ini tidak sesuai.';
            } else {
                if ($auth->changePassword($userId, $newPassword)) {
                    $_SESSION['toast_type'] = 'success';
                    $_SESSION['toast_message'] = 'Password berhasil diperbarui.';
                    redirect('../index.php?page=dashboard');
                } else {
                    $error = 'Gagal memperbarui password. Coba lagi.';
                }
            }
        } catch (Exception $ex) {
            $error = 'Gagal memperbarui password: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/change-password.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="glass-card p-4 p-md-5 shadow-lg position-relative overflow-hidden">
                    <!-- Premium Loading Overlay -->
                    <div id="change-password-loading" class="loading-overlay">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-3 fw-bold text-primary">Updating Security Settings...</div>
                        </div>
                    </div>

                    <div class="mb-5 text-center">
                        <div class="icon-key-glass animate__animated animate__bounceIn">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h3 class="title-glass">Ganti Password</h3>
                        <p class="text-muted small">Lindungi akun Anda dengan password yang kuat dan unik.</p>
                    </div>

                    <form method="POST" id="changePasswordForm" autocomplete="off">
                        <div class="mb-4">
                            <label class="form-label" for="current_password">
                                <i class="bi bi-key me-2 text-primary"></i> Password Saat Ini
                            </label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="current_password" id="current_password" required placeholder="••••••••" autocomplete="current-password">
                                <span class="password-toggle" data-target="current_password"><i class="bi bi-eye"></i></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="new_password">
                                <i class="bi bi-shield-check me-2 text-success"></i> Password Baru
                            </label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" required placeholder="••••••••" autocomplete="new-password">
                                <span class="password-toggle" data-target="new_password"><i class="bi bi-eye"></i></span>
                            </div>
                            <div class="mt-2">
                                <div id="passwordStrength" class="password-strength-meter"></div>
                                <div id="passwordStrengthText" class="password-strength-text small fw-bold"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="confirm_password">
                                <i class="bi bi-check2-all me-2 text-info"></i> Konfirmasi Password Baru
                            </label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required placeholder="••••••••" autocomplete="new-password">
                                <span class="password-toggle" data-target="confirm_password"><i class="bi bi-eye"></i></span>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>

                        <div class="row g-3 mt-4">
                            <div class="col-6">
                                <a href="?page=dashboard" class="btn btn-outline-secondary btn-glass w-100">
                                    Batal
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-glass w-100" id="savePasswordBtn">
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/change-password.custom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/change-password.js"></script>
    <?php echo displayToast(); ?>
    <?php if ($error): ?>
    <script>window.changePasswordError = <?php echo json_encode($error); ?>;</script>
    <?php endif; ?>
    <?php if ($success): ?>
    <script>window.changePasswordSuccess = <?php echo json_encode($success); ?>;</script>
    <?php endif; ?>

    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>