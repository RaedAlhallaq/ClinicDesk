<?php
// ============================================================
// config/config.php
// General application configuration settings.
// This file is loaded first in index.php.
// ============================================================

// ------------------------------------------------------------
// Basic Application Settings
// ------------------------------------------------------------
define('APP_NAME', 'ClinicDesk');
define('APP_VERSION', '1.0.0');

// ------------------------------------------------------------
// Base URL for the Application
// Important for redirects and CSS/JS asset paths.
// ------------------------------------------------------------
define('BASE_URL', 'http://localhost:8080/clinicdesk');

// ------------------------------------------------------------
// Pagination Settings
// Number of items to display per page in lists.
// ------------------------------------------------------------
define('ITEMS_PER_PAGE', 10);

// ------------------------------------------------------------
// File Upload Size Limits
// Define maximum allowed file sizes in bytes.
// ------------------------------------------------------------
define('MAX_AVATAR_SIZE', 1 * 1024 * 1024);        // 1MB for user avatars
define('MAX_DOCTOR_PHOTO_SIZE', 1 * 1024 * 1024);  // 1MB for doctor photos
define('MAX_PRESCRIPTION_SIZE', 3 * 1024 * 1024);  // 3MB for prescription files

// ------------------------------------------------------------
// Upload Paths
// Define absolute paths for storing uploaded files safely.
// ------------------------------------------------------------
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('AVATAR_PATH', __DIR__ . '/../public/uploads/avatars/');
define('DOCTOR_PHOTO_PATH', __DIR__ . '/../public/uploads/doctor_photos/');
define('PRESCRIPTION_PATH', __DIR__ . '/../public/uploads/prescriptions/');

// ------------------------------------------------------------
// Session Settings
// Custom session name for added security instead of default PHPSESSID.
// ------------------------------------------------------------
define('SESSION_NAME', 'clinicdesk_session');

// ------------------------------------------------------------
// Environment Settings
// 'development' -> display errors to the screen
// 'production'  -> hide errors and only log them
// ------------------------------------------------------------
define('APP_ENV', 'production');

// ------------------------------------------------------------
// Error Display Configuration
// Ensure errors are never displayed to users in production.
// ------------------------------------------------------------
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
}
