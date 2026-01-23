<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(isLoggedIn()) {
    redirect('../index.php?page=dashboard');
}

$auth = new Auth();
$error = '';
$success = '';
$initial_mode = isset($_GET['page']) && $_GET['page'] === 'register' ? 'register' : 'login';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $login_result = $auth->login($username, $password);
        
        if($login_result === true) {
            $_SESSION['show_welcome_toast'] = true;
            redirect('../index.php?page=dashboard');
        } elseif($login_result === 'not_approved') {
            $error = 'Akun Anda sedang menunggu persetujuan admin.';
        } else {
            $error = 'Username/email atau password salah!';
        }
        $initial_mode = 'login';
    } elseif ($action === 'register') {
        if (!empty($_POST['website_hp'])) {
            $error = 'Bot detected.';
        } else {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if($password !== $confirm_password) {
                $error = 'Konfirmasi password tidak cocok!';
            } elseif(!$auth->validatePasswordStrength($password)) {
                $error = 'Password minimal harus 6 karakter.';
            } else {
                if($auth->register($username, $email, $password, 'user', 0, 1)) {
                    $success = 'Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan admin.';
                    $initial_mode = 'login'; // Switch to login after success
                } else {
                    $error = 'Pendaftaran gagal. Username atau email sudah terdaftar.';
                    $initial_mode = 'register';
                }
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
    <title>Authentication - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="auth-shape auth-shape-1"></div>
        <div class="auth-shape auth-shape-2"></div>
    </div>

    <div class="split-wrapper <?php echo $initial_mode === 'register' ? 'show-register' : ''; ?>" id="splitWrapper">
        <!-- Login Panel (Left) -->
        <div class="auth-panel login-panel">
            <div class="panel-content glass-panel">
                <div class="auth-header text-center mb-4">
                    <div class="auth-logo mb-3">
                        <i class="bi bi-intersect"></i>
                    </div>
                    <h2 class="auth-title">Welcome Back</h2>
                    <p class="auth-subtitle">Kelola tugas Anda sekarang</p>
                </div>

                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Username atau Email</label>
                        <div class="input-glass-group">
                            <span class="input-glass-icon"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" required placeholder="example@mail.com">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-glass-group">
                            <span class="input-glass-icon"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="login_password" name="password" required placeholder="••••••••">
                            <span class="password-toggle" data-target="login_password"><i class="bi bi-eye"></i></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-auth-primary w-100 mb-3 text-white fw-bold">Masuk Ke Akun</button>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0 text-muted d-lg-none">Belum punya akun? <a href="javascript:void(0)" onclick="setMode('register')">Daftar</a></p>
                    </div>

                    <div class="demo-hints mt-4">
                        <div class="d-flex align-items-center justify-content-center gap-2 small text-muted">
                            <i class="bi bi-info-circle text-info"></i>
                            <span>Demo: <strong>admin</strong> / <strong>password</strong></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Register Panel (Right) -->
        <div class="auth-panel register-panel">
            <div class="panel-content glass-panel">
                <div class="auth-header text-center mb-4">
                    <div class="auth-logo mb-3" style="background: linear-gradient(135deg, #10b981, #34d399);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h2 class="auth-title">Create Account</h2>
                    <p class="auth-subtitle">Mulai perjalanan produktivitas Anda</p>
                </div>

                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    <div style="display:none;"><input type="text" name="website_hp"></div>
                    
                    <div class="row g-3">
                        <div class="col-12 mb-1">
                            <label class="form-label">Username</label>
                            <div class="input-glass-group">
                                <span class="input-glass-icon"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" name="username" required placeholder="username">
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <label class="form-label">Email Address</label>
                            <div class="input-glass-group">
                                <span class="input-glass-icon"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" required placeholder="example@mail.com">
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label">Password</label>
                            <div class="input-glass-group">
                                <span class="input-glass-icon"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="reg_password" name="password" required placeholder="••••">
                                <span class="password-toggle" data-target="reg_password"><i class="bi bi-eye"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label">Confirm</label>
                            <div class="input-glass-group">
                                <span class="input-glass-icon"><i class="bi bi-shield-check"></i></span>
                                <input type="password" class="form-control" id="reg_confirm_password" name="confirm_password" required placeholder="••••">
                                <span class="password-toggle" data-target="reg_confirm_password"><i class="bi bi-eye"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 mt-2">
                        <div id="regPasswordStrength" class="password-strength-meter mb-1"></div>
                        <div id="regPasswordMatch" class="small fw-bold"></div>
                    </div>

                    <button type="submit" class="btn btn-auth-primary w-100 text-white fw-bold" style="background: linear-gradient(135deg, #10b981, #34d399);">Buat Akun Baru</button>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0 text-muted d-lg-none">Sudah punya akun? <a href="javascript:void(0)" onclick="setMode('login')">Masuk</a></p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overlay Switcher (Desktop Only) -->
        <div class="overlay-container d-none d-lg-block">
            <div class="overlay">
                <!-- Visible when showing Login form (to invite registration) -->
                <div class="overlay-panel overlay-right">
                    <h1 class="fw-bold text-white mb-3">Hello, Friend!</h1>
                    <p class="text-white-50 mb-5">Belum punya akun? Daftarkan diri Anda hari ini dan mulai kelola tugas dengan lebih profesional.</p>
                    <button class="btn btn-outline-light rounded-pill px-5 py-2 fw-bold" onclick="setMode('register')">DAFTAR SEKARANG</button>
                </div>
                <!-- Visible when showing Register form (to invite login) -->
                <div class="overlay-panel overlay-left">
                    <h1 class="fw-bold text-white mb-3">Welcome Back!</h1>
                    <p class="text-white-50 mb-5">Sudah punya akun? Tetap terhubung dengan tim Anda dan selesaikan semua tugas tepat waktu.</p>
                    <button class="btn btn-outline-light rounded-pill px-5 py-2 fw-bold" onclick="setMode('login')">MASUK KE AKUN</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        function setMode(mode) {
            const wrapper = document.getElementById('splitWrapper');
            if (mode === 'register') {
                wrapper.classList.add('show-register');
                window.history.pushState({}, "", "?page=register");
                document.title = "Register - Todo Talenta Digital";
            } else {
                wrapper.classList.remove('show-register');
                window.history.pushState({}, "", "?page=login");
                document.title = "Login - Todo Talenta Digital";
            }
        }

        <?php if($error): ?>
            Swal.fire({ icon: 'error', title: 'Oops...', text: '<?php echo $error; ?>' });
        <?php endif; ?>
        <?php if($success): ?>
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?php echo $success; ?>' });
        <?php endif; ?>

        $(function() {
            // Local fallback for password toggles to ensure clickability in Split Panel
            $(document).on('click', '.password-toggle', function(e) {
                e.preventDefault();
                const targetId = $(this).data('target');
                const input = document.getElementById(targetId);
                const icon = $(this).find('i');
                if (!input || !icon) return;

                const isPassword = input.type === "password";
                input.type = isPassword ? "text" : "password";
                
                // Toggle Bootstrap Icons
                icon.toggleClass('bi-eye bi-eye-slash');
            });

            $('#reg_password, #reg_confirm_password').on('input', function() {
                const p = $('#reg_password').val();
                const c = $('#reg_confirm_password').val();
                const target = $('#regPasswordMatch');
                
                if (typeof checkPasswordStrength === 'function') {
                    const result = checkPasswordStrength(p);
                    $('#regPasswordStrength').css({'width': result.width, 'background-color': result.color});
                }
                
                if(!c) target.html('');
                else if(p === c) target.html('<span class="text-success small fw-bold">Password cocok</span>');
                else target.html('<span class="text-danger small fw-bold">Password tidak cocok</span>');
            });
        });
    </script>
</body>
</html>
