<?php
// ============================================================
// views/errors/404.php
// 404 Not Found Error Page
// Displayed when the router cannot match the requested page parameter.
// ============================================================

if (http_response_code() !== 404) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - غير موجود | <?= APP_NAME ?></title>
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
            color: #3498db;
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
        }

        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="error-box">

        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1>الصفحة غير موجودة</h1>

        <p>
            عذرًا، الصفحة التي تبحث عنها غير موجودة.<br>
            ربما تم نقلها أو حذفها أو كتبت الرابط بشكل خاطئ.
        </p>

        <?php if (isset($_SESSION['user'])): ?>
            <a href="<?= BASE_URL ?>/index.php?page=dashboard"
               class="btn">
                العودة للرئيسية
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php?page=auth&action=login"
               class="btn">
                تسجيل الدخول
            </a>
        <?php endif; ?>

    </div>
</body>
</html>