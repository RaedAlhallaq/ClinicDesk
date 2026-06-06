<?php
// ============================================================
// core/Auth.php
// Manages user authentication and authorization logic.
//
// Concepts applied:
// 1. Session-Based Authentication
// 2. Session Fixation Protection
// 3. Role-Based Access Control (RBAC)
// ============================================================

class Auth
{
    // Log in the user and save their basic info in the session.
    // Called after verifying email and password in the database.
    public static function login(array $user): void
    {
        // Regenerate the session ID immediately after verifying identity
        // to prevent Session Fixation attacks.
        session_regenerate_id(true);

        // Save only essential user data in the session.
        // Never store the password here.
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
    }

    // Log out the user and destroy the session completely.
    public static function logout(): void
    {
        // Step 1: Remove all session variables from memory.
        session_unset();

        // Step 2: Destroy the session on the server.
        session_destroy();

        // Step 3: Remove the session cookie from the user's browser.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Redirect the user back to the login page.
        redirect('auth', 'login');
    }

    // Check if the user is currently logged in.
    public static function check(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    // Get the current logged-in user's data from the session.
    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    // Get the ID of the current user quickly.
    public static function id(): ?int
    {
        return isset($_SESSION['user']['id'])
            ? (int) $_SESSION['user']['id']
            : null;
    }

    // Get the role of the current user.
    public static function role(): string
    {
        return $_SESSION['user']['role'] ?? '';
    }

    // Quick helper functions to check specific roles.
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isDoctor(): bool
    {
        return self::role() === 'doctor';
    }

    public static function isPatient(): bool
    {
        return self::role() === 'patient';
    }

    // Restrict access to specific roles.
    // Should be placed at the very top of protected controller actions.
    public static function requireRole(string ...$roles): void
    {
        // First, check if the user is logged in.
        if (!self::check()) {
            flashMessage('You must log in first.', 'warning');
            redirect('auth', 'login');
            return;
        }

        // Next, check if their role matches any of the allowed roles.
        if (!in_array(self::role(), $roles, true)) {
            // If not, deny access with a 403 Forbidden page.
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit();
        }
    }

    // Restrict access to any logged-in user, regardless of their role.
    public static function requireAuth(): void
    {
        if (!self::check()) {
            flashMessage('You must log in first.', 'warning');
            redirect('auth', 'login');
        }
    }

    // If the user is already logged in, redirect them to the dashboard.
    // Used on the login page to prevent re-authentication.
    public static function redirectIfLoggedIn(): void
    {
        if (self::check()) {
            redirect('dashboard');
        }
    }
}