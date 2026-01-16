<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(isLoggedIn()) {
    redirect('../index.php?page=dashboard');
}

$auth = new Auth();
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Honeypot anti-bot
    if (!empty($_POST['website_hp'])) {
        $error = 'Bot detected.';
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if($password !== $confirm_password) {
            $error = 'Passwords do not match!';
        } elseif(!$auth->validatePasswordStrength($password)) {
            $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.';
        } else {
            if($auth->register($username, $email, $password, 'user', 0, 1)) {
                $success = 'Registration successful! Your account is pending admin approval. You will receive an email once approved.';
            } else {
                $error = 'Registration failed. Username or email already exists.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <div class="split-container">
        <div class="left-side">
            <div class="brand-content">
                <div class="brand-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Todo Talenta Digital</h1>
                <p>Bergabung dan kelola tugas Anda bersama tim dengan mudah dan efisien.</p>
                <div class="features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <small>Kolaborasi Tim</small>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <small>Keamanan Data</small>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <small>Produktivitas</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-side">
            <div class="register-form-container">
                <div class="form-header">
                    <h2>Daftar Akun Baru</h2>
                    <p>Isi data di bawah untuk membuat akun Anda</p>
                </div>
                <form method="POST" action="" id="registerForm" autocomplete="off">
                                        <!-- Honeypot field, hidden from users -->
                                        <div style="display:none;">
                                            <label for="website_hp">Website</label>
                                            <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off">
                                        </div>
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Masukkan email">
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
                        <div class="mt-2">
                            <div id="passwordStrength" class="password-strength-meter"></div>
                            <div id="passwordStrengthText" class="password-strength-text"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <div class="password-input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Ulangi password">
                            <span class="password-toggle" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div id="passwordMatch" class="mt-2"></div>
                    </div>
                    <button type="submit" class="btn btn-register-main">
                        <i class="fas fa-user-plus me-2"></i> Daftar
                    </button>
                    <div class="divider">
                        <span>atau</span>
                    </div>
                    <a href="../index.php?page=login" class="btn btn-login-link">
                        <i class="fas fa-sign-in-alt me-2"></i> Kembali ke Login
                    </a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/register.js"></script>
    <script src="../js/register.custom.js"></script>
    <?php if($error || $success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if($error): ?>
            showServerMessage('error', <?php echo json_encode($error); ?>);
            <?php endif; ?>
            <?php if($success): ?>
            showServerMessage('success', <?php echo json_encode($success); ?>);
            <?php endif; ?>
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