<?php
// ============================================================
// views/errors/403.php
// 403 Forbidden Error Page
// Displayed when a user attempts to access a page
// for which they lack the required role/permissions.
// ============================================================

// Ensure the HTTP status code is set to 403
// (It might have already been set in Auth::requireRole)
if (http_response_code() !== 403) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - ممنوع الوصول | <?= APP_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: rtl;
        }

        .error-box {
            background: white;
            border-radius: 12px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 480px;
            width: 90%;
        }

        .error-code {
            font-size: 80px;
            font-weight: bold;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 10px;
        }

        .error-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 12px;
        }

        p {
            color: #7f8c8d;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            transition: background 0.2s;
        }

        .btn:hover { background: #2980b9; }

        .btn-secondary {
            background: #95a5a6;
            margin-right: 10px;
        }

        .btn-secondary:hover { background: #7f8c8d; }
    </style>
</head>
<body>
    <div class="error-box">

        <div class="error-icon">🚫</div>
        <div class="error-code">403</div>
        <h1>ممنوع الوصول</h1>

        <p>
            عذرًا، ليس لديك صلاحية للوصول إلى هذه الصفحة.<br>
            إذا كنت تعتقد أن هذا خطأ، تواصل مع المدير.
        </p>

        <?php if (isset($_SESSION['user'])): ?>
            <!-- User is logged in → offer return to Dashboard -->
            <a href="<?= BASE_URL ?>/index.php?page=dashboard"
               class="btn">
                العودة للرئيسية
            </a>
        <?php else: ?>
            <!-- User is not logged in → offer login link -->
            <a href="<?= BASE_URL ?>/index.php?page=auth&action=login"
               class="btn">
                تسجيل الدخول
            </a>
        <?php endif; ?>

    </div>
</body>
</html>