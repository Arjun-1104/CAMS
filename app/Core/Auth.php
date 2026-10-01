<?php
/**
 * Auth.php
 * Login, logout, and "which role is this user?" checks.
 * Passwords are stored hashed (password_hash), never as plain text.
 */

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $max = (int) App::config('security.max_login_attempts', 8);
        $lock = (int) App::config('security.lockout_minutes', 15);
        $since = App::now()->modify("-{$lock} minutes")->format('Y-m-d H:i:s');

        $fails = Database::fetch(
            'SELECT COUNT(*) AS c FROM login_attempts WHERE username = ? AND success = 0 AND created_at >= ?',
            [$username, $since]
        );
        if ((int) ($fails['c'] ?? 0) >= $max) {
            App::flash('error', "Too many failed logins. Try again after {$lock} minutes.");
            return false;
        }

        $user = Database::fetch('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
        $ok = $user && password_verify($password, $user['password_hash']) && $user['status'] === 'ACTIVE';

        Database::query(
            'INSERT INTO login_attempts (username, ip_address, success) VALUES (?,?,?)',
            [$username, self::ip(), $ok ? 1 : 0]
        );

        if (!$ok) {
            Audit::log($user['id'] ?? null, 'LOGIN_FAILED', 'Username: ' . $username);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        Database::query('UPDATE users SET last_login_at = ? WHERE id = ?', [App::now()->format('Y-m-d H:i:s'), $user['id']]);
        Audit::log((int) $user['id'], 'LOGIN_SUCCESS', 'Role: ' . $user['role']);
        return true;
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id) {
            Audit::log($id, 'LOGOUT', null);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        static $cached = null;
        static $cachedId = null;
        if ($cached && $cachedId === self::id()) {
            return $cached;
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [self::id()]);
        if (!$user) {
            return null;
        }
        if ($user['role'] === 'TEACHER') {
            $profile = Database::fetch('SELECT * FROM teachers WHERE user_id = ?', [$user['id']]);
            $user['teacher'] = $profile;
        } elseif ($user['role'] === 'STUDENT') {
            $profile = Database::fetch('SELECT * FROM students WHERE user_id = ?', [$user['id']]);
            $user['student'] = $profile;
        } elseif ($user['role'] === 'ADMIN') {
            $profile = Database::fetch('SELECT * FROM admins WHERE user_id = ?', [$user['id']]);
            $user['admin'] = $profile;
        }
        $cached = $user;
        $cachedId = self::id();
        return $user;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function requireRole(string ...$roles): void
    {
        if (!self::check()) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . App::url('/login'));
            exit;
        }
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo 'Forbidden.';
            exit;
        }
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
