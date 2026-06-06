<?php
// ============================================================
// views/partials/alerts.php
// عرض Flash Messages المؤقتة
//
// طريقة الاستخدام في كل View:
//  require_once __DIR__ . '/../partials/alerts.php'; 
//
// أو في كل صفحة بعد sidebar:
// <?php require_once 'views/partials/alerts.php'; 
// ============================================================

// جلب الرسالة المؤقتة وحذفها من Session
// getFlashMessage() معرّفة في core/helpers.php
$flash = getFlashMessage();

// إذا لا توجد رسالة → لا تعرض شيئًا
if (!$flash): ?>

<?php else: ?>

    <?php
    // ربط نوع الرسالة بكلاس AdminLTE/Bootstrap
    // success → alert-success (أخضر)
    // error   → alert-danger  (أحمر)
    // warning → alert-warning (أصفر)
    // info    → alert-info    (أزرق)
    $alertClass = match($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info'
    };

    // أيقونة مناسبة لكل نوع
    $icon = match($flash['type']) {
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        default   => 'ℹ️'
    };
    ?>

    <div class="alert <?= $alertClass ?> alert-dismissible fade show"
         role="alert"
         style="margin: 15px 0;">

        <?= $icon ?>
        <?= e($flash['message']) ?>

        <!-- زر إغلاق الرسالة -->
        <button type="button"
                class="close"
                data-dismiss="alert"
                aria-label="إغلاق">
            <span aria-hidden="true">&times;</span>
        </button>

    </div>

<?php endif; ?>