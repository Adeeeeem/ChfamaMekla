<?php

/**
 * Authentication Configuration
 * Session and role-based access control
 */

class Auth
{
    private static PDO $db;
    private static bool $initialized = false;

    public static function init(PDO $db): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::$db = $db;
        self::$initialized = true;
    }

    public static function check(): bool
    {
        if (!self::$initialized) {
            return false;
        }
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        $stmt = self::$db->prepare("SELECT id, email, is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public static function isEmployee(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
    }

    public static function isClient(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'client';
    }

    public static function login(string $email, string $password): bool
    {
        $stmt = self::$db->prepare("SELECT id, email, password_hash, is_admin FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['role'] = $user['is_admin'] ? 'admin' : 'client';
            
            self::logActivity($user['id'], 'login');
            return true;
        }
        
        return false;
    }

    public static function logout(): void
    {
        if (self::check()) {
            self::logActivity($_SESSION['user_id'], 'logout');
        }
        session_destroy();
    }

    public static function register(string $email, string $password, bool $isAdmin = false): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = self::$db->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, ?)");
        return $stmt->execute([$email, $hash, $isAdmin]);
    }

    private static function logActivity(int $userId, string $action): void
    {
        $stmt = self::$db->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }
    }
}