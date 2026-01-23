<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';
if(isLoggedIn()) {
    redirect('../index.php?page=dashboard');
}
$auth = new Auth();
$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $login_result = $auth->login($username, $password);
    if($login_result === true) {
        $_SESSION['show_welcome_toast'] = true;
        redirect('../index.php?page=dashboard');
    } elseif($login_result === 'not_approved') {
        $error = 'Your account is pending admin approval. Please wait for approval before logging in.';
    } else {
        $error = 'Username/email or password is incorrect!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-left-info">
        <div class="login-left-content">
            <div class="login-left-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h1 class="login-left-title">Todo Talenta Digital</h1>
            <p class="login-left-desc">Kelola tugas, kolaborasi tim, dan tingkatkan produktivitas Anda setiap hari dengan platform manajemen tugas modern dan mudah digunakan.</p>
            <div class="login-left-features">
                <div class="login-left-feature">
                    <div class="login-left-feature-icon"><i class="fas fa-tasks"></i></div>
                    <div class="login-left-feature-label">Manajemen Tugas</div>
                </div>
                <div class="login-left-feature">
                    <div class="login-left-feature-icon"><i class="fas fa-users"></i></div>
                    <div class="login-left-feature-label">Kolaborasi Tim</div>
                </div>
                <div class="login-left-feature">
                    <div class="login-left-feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="login-left-feature-label">Tracking Progress</div>
                </div>
            </div>
        </div>
    </div>
    <div class="login-card">
        <!-- Judul dan deskripsi di form login dihapus, hanya icon jika ingin tetap ada -->
        <div class="brand"></div>
        
            <div class="form-header" style="display:none;">
                <h2>Selamat Datang!</h2>
                <p>Masuk untuk melanjutkan ke dashboard Anda</p>
            </div>
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Username atau Email</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username atau email">
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-password-group">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
                    <span class="password-toggle" data-target="password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                Masuk
            </button>
            <div class="divider">
                <span>atau</span>
            </div>
            <a href="../index.php?page=register" class="btn btn-register">
                Daftar Akun Baru
            </a>
            <div class="demo-badge">
                <strong>Demo:</strong> admin/password atau user/user123
            </div>
        </form>
        <?php if($error): ?>
        <script>var LOGIN_ERROR = <?php echo json_encode($error); ?>;</script>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/login.custom.js"></script>
</body>
</html>