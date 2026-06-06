<?php
// ============================================================
// controllers/AuthController.php
// Handles user authentication: login and logout operations.
//
// Routes:
// GET  ?page=auth&action=login  -> Display login page
// POST ?page=auth&action=login  -> Process login submission
// POST ?page=auth&action=logout -> Process user logout
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';

// Read the action from the Router ($action is defined in index.php)
$action = $action ?? 'login';

// Route to the appropriate function based on the action parameter
match($action) {
    'login'  => handleLogin(),
    'logout' => handleLogout(),
    default  => handleLogin()
};


// ============================================================
// handleLogin()
// Displays the login form or processes a POST login request.
// ============================================================
function handleLogin(): void
{
    // If the user is already logged in, redirect them directly to the Dashboard.
    Auth::redirectIfLoggedIn();

    // GET Request -> Just display the login page.
    if (isGet()) {
        require_once __DIR__ . '/../views/auth/login.php';
        return;
    }

    // POST Request -> Process the login attempt.
    if (isPost()) {

        // ================================================
        // Step 1: Validate the CSRF Token for security
        // ================================================
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request. Please try again.', 'error');
            redirect('auth', 'login');
        }

        // ================================================
        // Step 2: Read and sanitize user inputs
        // ================================================
        $email    = sanitizeEmail($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        // Ensure fields are not empty
        if (empty($email) || empty($password)) {
            flashMessage('Email and password are required.', 'error');
            redirect('auth', 'login');
        }

        // ================================================
        // Step 3: Find the user by their email address
        // ================================================
        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        // Display a generic message if the user is not found.
        // This prevents User Enumeration Attacks.
        if (!$user) {
            flashMessage('Invalid credentials. Please try again.', 'error');
            redirect('auth', 'login');
        }

        // ================================================
        // Step 4: Check if the user account is active
        // ================================================
        if ((int) $user['is_active'] !== 1) {
            flashMessage(
                'Your account has been suspended. Contact admin.',
                'warning'
            );
            redirect('auth', 'login');
        }

        // ================================================
        // Step 5: Verify the provided password
        // ================================================
        if (!password_verify($password, $user['password'])) {
            flashMessage('Invalid credentials. Please try again.', 'error');
            redirect('auth', 'login');
        }

        // ================================================
        // Step 6: All checks passed -> Log the user in
        // ================================================
        Auth::login($user);

        // Optionally, log successful logins for security auditing
        error_log("Login: user_id={$user['id']} role={$user['role']} ip={$_SERVER['REMOTE_ADDR']}");

        // Redirect the user to their Dashboard
        flashMessage("Welcome back, {$user['name']}!", 'success');
        redirect('dashboard');
    }
}


// ============================================================
// handleLogout()
// Logs out the current user. Only accepts POST requests.
// ============================================================
function handleLogout(): void
{
    // Ensure logout is always triggered via a POST request for safety.
    if (!isPost()) {
        redirect('dashboard');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        redirect('dashboard');
    }

    // Log the logout action for security auditing.
    $user = Auth::currentUser();
    if ($user) {
        error_log("Logout: user_id={$user['id']}");
    }

    // Auth::logout() destroys the session and redirects to the login page.
    Auth::logout();
}