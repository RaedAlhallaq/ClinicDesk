<?php
// ============================================================
// views/auth/login.php
// صفحة تسجيل الدخول
// لا تحتاج partials — صفحة مستقلة بتصميم خاص
// ============================================================

// إذا مسجل دخوله بالفعل → أعده للـ Dashboard
Auth::redirectIfLoggedIn();

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/adminlte/dist/css/adminlte.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #1a2035 0%, #1e3a5f 100%);
            min-height: 100vh;
        }

        .login-card {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #1e3a5f, #2980b9);
            padding: 35px 20px;
            text-align: center;
            color: white;
        }

        .login-header .logo-icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 1px;
        }

        .login-header p {
            margin: 5px 0 0;
            opacity: 0.8;
            font-size: 14px;
        }

        .login-body {
            padding: 35px 30px;
            background: white;
        }

        .login-body .form-control {
            height: 48px;
            border-radius: 8px;
            font-size: 15px;
        }

        .login-body .input-group-text {
            border-radius: 8px 0 0 8px;
            background: #f8f9fa;
            color: #6c757d;
            width: 48px;
            justify-content: center;
        }

        .btn-login {
            height: 48px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(135deg, #1e3a5f, #2980b9);
            border: none;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2980b9, #1e3a5f);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(41, 128, 185, 0.4);
        }

        .alert {
            border-radius: 8px;
            font-size: 14px;
        }
    </style>
</head>

<body class="hold-transition">

    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5 col-lg-4">

                <div class="login-card">

                    <!-- Header -->
                    <div class="login-header">
                        <span class="logo-icon">
                            <i class="fas fa-clinic-medical"></i>
                        </span>
                        <h1><?= APP_NAME ?></h1>
                        <p>Clinic Management Dashboard</p>
                    </div>

                    <!-- Body -->
                    <div class="login-body">

                        <!-- Flash Message -->
                        <?php
                        $flash = getFlashMessage();
                        if ($flash):
                            $alertClass = match ($flash['type']) {
                                'success' => 'alert-success',
                                'error' => 'alert-danger',
                                'warning' => 'alert-warning',
                                default => 'alert-info'
                            };
                            ?>
                            <div class="alert <?= $alertClass ?>
                                         alert-dismissible fade show">
                                <?= e($flash['message']) ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="<?= BASE_URL ?>/index.php?page=auth&action=login">

                            <?= CSRF::tokenInput() ?>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email" class="font-weight-600">
                                    Email Address
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                    </div>
                                    <input type="email" id="email" name="email" class="form-control"
                                        placeholder="Enter your email" value="<?= e($_POST['email'] ?? '') ?>" required
                                        autofocus>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label for="password" class="font-weight-600">
                                    Password
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="Enter your password" required>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-login btn-block">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Sign In
                                </button>
                            </div>

                        </form>

                        <p class="text-center text-muted mt-3 mb-0" style="font-size:13px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            No registration — accounts are created by Admin
                        </p>

                    </div>
                    <!-- End Body -->

                </div>
                <!-- End Card -->

            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/public/assets/adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>