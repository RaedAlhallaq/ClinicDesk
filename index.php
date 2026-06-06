<?php
// ============================================================
// index.php — Front Controller
// Every request in the project goes through this single file.
// It starts the session, loads all required files, reads the
// URL parameters, and routes the request to the correct controller.
// ============================================================


// ------------------------------------------------------------
// Section 1: Start the Session
// Must come before any output (echo, HTML, etc.)
// ------------------------------------------------------------
session_name('clinicdesk_session');
session_start();


// ------------------------------------------------------------
// Section 2: Load Configuration Files
// Order matters: config.php is loaded first because it defines
// constants (BASE_URL, ITEMS_PER_PAGE, etc.) used by other files.
// ------------------------------------------------------------
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';


// ------------------------------------------------------------
// Section 3: Load Core Classes
// These classes are needed by every controller and model.
// Loading them once here avoids repeating the require in each file.
// ------------------------------------------------------------
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Paginator.php';
require_once __DIR__ . '/core/helpers.php';


// ------------------------------------------------------------
// Section 4: Read and Clean URL Parameters
//
// Example URL: index.php?page=appointments&action=book
//
// filter_input() is safer than reading $_GET directly:
//   FILTER_SANITIZE_SPECIAL_CHARS → removes dangerous characters
//   ?? 'dashboard'                → default value if 'page' is missing
// ------------------------------------------------------------
$page   = filter_input(INPUT_GET, 'page',   FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'index';

// Allow only letters, numbers, and underscores — prevents path injection.
$page   = preg_replace('/[^a-zA-Z0-9_]/', '', $page);
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);

// If the values are empty after cleaning, fall back to safe defaults.
if (empty($page))
    $page = 'dashboard';
if (empty($action))
    $action = 'index';


// ------------------------------------------------------------
// Section 5: Router
// Maps each 'page' value to the corresponding controller file.
// Using an array map is more readable and maintainable than a
// long if/elseif chain.
// ------------------------------------------------------------
$controllerMap = [
    'auth'          => __DIR__ . '/controllers/AuthController.php',
    'dashboard'     => __DIR__ . '/controllers/DashboardController.php',
    'users'         => __DIR__ . '/controllers/UserController.php',
    'doctors'       => __DIR__ . '/controllers/DoctorController.php',
    'appointments'  => __DIR__ . '/controllers/AppointmentController.php',
    'prescriptions' => __DIR__ . '/controllers/PrescriptionController.php',
    'reports'       => __DIR__ . '/controllers/ReportController.php',
];

if (array_key_exists($page, $controllerMap)) {
    // Known page — load the matching controller file.
    require_once $controllerMap[$page];
} else {
    // Unknown page — show a 404 error page.
    http_response_code(404);
    require_once __DIR__ . '/views/errors/404.php';
}
