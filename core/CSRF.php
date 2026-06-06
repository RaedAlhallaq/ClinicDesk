<?php
// ============================================================
// core/CSRF.php
// Protects forms against Cross-Site Request Forgery (CSRF) attacks.
// ============================================================

class CSRF
{
    private const TOKEN_KEY = 'csrf_token';

    // Generate a secure, random CSRF token and store it in the session.
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    // Validate the provided token against the one stored in the session.
    public static function validateToken(string $token): bool
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }

        if (empty($token)) {
            return false;
        }

        // Use hash_equals to prevent timing attacks.
        return hash_equals(
            $_SESSION[self::TOKEN_KEY],
            $token
        );
    }

    // Clear the old token and generate a fresh one.
    public static function refreshToken(): string
    {
        unset($_SESSION[self::TOKEN_KEY]);
        return self::generateToken();
    }

    // Generate a hidden HTML input field containing the token for use in forms.
    public static function tokenInput(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
            . '">';
    }
}