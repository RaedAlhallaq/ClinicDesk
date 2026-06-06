<?php
// ============================================================
// views/partials/alerts.php
// Displays temporary flash messages (success, error, warning, info).
//
// Include this file at the top of every content section:
//   require_once __DIR__ . '/../partials/alerts.php';
//
// The message is read from the session and immediately deleted so
// it only shows once ("flash" behavior).
// ============================================================

// Retrieve the flash message from the session and delete it immediately.
// getFlashMessage() is defined in core/helpers.php.
$flash = getFlashMessage();

// If there is no flash message, render nothing.
if (!$flash): ?>

<?php else: ?>

    <?php
    // Map the message type to the matching Bootstrap/AdminLTE CSS class.
    // success → green, error → red, warning → yellow, info → blue
    $alertClass = match($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info'
    };

    // Choose an icon that matches the message type.
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

        <!-- Dismiss button to manually close the alert -->
        <button type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>

    </div>

<?php endif; ?>