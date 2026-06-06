<?php
// ============================================================
// This file is the Front Controller, and every request in the project goes through here.
// ============================================================


// ------------------------------------------------------------
// القسم 1: بدء الـ Session
// يجب أن يكون أول شيء قبل أي echo أو output
// ------------------------------------------------------------
session_name('clinicdesk_session');
session_start();


// ------------------------------------------------------------
// القسم 2: تحميل ملفات الإعدادات
// الترتيب مهم: config.php أولًا لأن database.php
// قد يحتاج ثوابت منه مستقبلًا
// ------------------------------------------------------------
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';


// ------------------------------------------------------------
// القسم 3: تحميل الكلاسات الأساسية (Core Classes)
// هذه الكلاسات يحتاجها كل controller وكل model
// نحمّلها مرة واحدة هنا بدل تكرارها في كل ملف
// ------------------------------------------------------------
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Paginator.php';
require_once __DIR__ . '/core/helpers.php';


// ------------------------------------------------------------
// القسم 4: قراءة معاملات الـ URL وتنظيفها
//
// مثال URL: index.php?page=appointments&action=book
//
// filter_input() أآمن من $_GET مباشرة:
// FILTER_SANITIZE_SPECIAL_CHARS → يزيل أحرف خطيرة
// ?? 'dashboard' → القيمة الافتراضية إذا لم يوجد page
// ------------------------------------------------------------
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'index';

// تأكد أن القيم تحتوي فقط على أحرف وأرقام وشرطة سفلية
// هذا يمنع أي محاولة لحقن مسارات غريبة
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);

// إذا أصبحت فارغة بعد التنظيف → أعد للقيمة الافتراضية
if (empty($page))
    $page = 'dashboard';
if (empty($action))
    $action = 'index';


// ------------------------------------------------------------
// القسم 5: الـ Router
// يربط قيمة page بملف الـ Controller المناسب
//
// لماذا match وليس switch؟
// match أحدث وأدق: يستخدم === وليس ==
// لا يحتاج break
// يرمي خطأ تلقائيًا إذا لم يجد تطابق (بدل السكوت)
// ------------------------------------------------------------
$controllerMap = [
    'auth' => __DIR__ . '/controllers/AuthController.php',
    'dashboard' => __DIR__ . '/controllers/DashboardController.php',
    'users' => __DIR__ . '/controllers/UserController.php',
    'doctors' => __DIR__ . '/controllers/DoctorController.php',
    'appointments' => __DIR__ . '/controllers/AppointmentController.php',
    'prescriptions' => __DIR__ . '/controllers/PrescriptionController.php',
    'reports' => __DIR__ . '/controllers/ReportController.php',
];

if (array_key_exists($page, $controllerMap)) {
    // ✅ الصفحة معروفة → حمّل الـ Controller
    require_once $controllerMap[$page];
} else {
    // ❌ الصفحة غير معروفة → أظهر صفحة 404
    http_response_code(404);
    require_once __DIR__ . '/views/errors/404.php';
}

