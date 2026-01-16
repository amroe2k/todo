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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="split-container">
        <div class="left-side">
            <div class="brand-content">
                <div class="brand-logo">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h1>Todo Talenta Digital</h1>
                <p>Kelola tugas Anda dengan mudah dan efisien. Tingkatkan produktivitas setiap hari.</p>
                <div class="features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <small>Manajemen Tugas</small>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <small>Kolaborasi Tim</small>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <small>Tracking Progress</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-side">
            <div class="login-form-container">
                <div class="form-header">
                    <h2>Selamat Datang!</h2>
                    <p>Masuk untuk melanjutkan ke dashboard Anda</p>
                </div>
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">Username atau Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username atau email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
                            <span class="password-toggle" data-target="password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Masuk
                    </button>
                    <div class="divider">
                        <span>atau</span>
                    </div>
                    <a href="../index.php?page=register" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i> Daftar Akun Baru
                    </a>
                    <div class="demo-badge">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Demo:</strong> admin/password atau user/user123
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/login.custom.js"></script>
    <?php if($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login gagal',
            text: <?php echo json_encode($error); ?>,
            confirmButtonText: 'OK',
            background: '#ffffff',
            color: '#1a1a1a'
        });
    </script>
    <?php endif; ?>
    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>