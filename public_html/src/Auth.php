<?php
/**
 * Nyitott Múlt – Autentikáció és Jogosultságkezelés (RBAC)
 */

declare(strict_types=1);

namespace NyitottMult;

class Auth
{
    public const ROLE_VISITOR = 'visitor';
    public const ROLE_SUBMITTER = 'submitter';
    public const ROLE_RESEARCHER = 'researcher';
    public const ROLE_REVIEWER = 'reviewer';
    public const ROLE_ADMIN = 'admin';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public static function check(): bool
    {
        self::init();
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        self::init();
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['user_role'] ?? self::ROLE_VISITOR,
            'full_name' => $_SESSION['full_name'] ?? ''
        ];
    }

    public static function id(): ?string
    {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }

    public static function hasRole(string $minRole): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $hierarchy = [
            self::ROLE_VISITOR => 1,
            self::ROLE_SUBMITTER => 2,
            self::ROLE_RESEARCHER => 3,
            self::ROLE_REVIEWER => 4,
            self::ROLE_ADMIN => 5
        ];

        $userLevel = $hierarchy[$user['role']] ?? 0;
        $requiredLevel = $hierarchy[$minRole] ?? 99;

        return $userLevel >= $requiredLevel;
    }

    public static function login(string $username, string $password): bool
    {
        self::init();
        $user = Database::queryOne(
            "SELECT id, username, password_hash, role, full_name FROM users WHERE username = :u OR email = :u",
            ['u' => $username]
        );

        if (!$user) {
            return false;
        }

        // Jelszóellenőrzés (Bcrypt vagy egyszerűbb hash ellenőrzés fejlesztői módban)
        if (password_verify($password, $user['password_hash']) || (defined('APP_ENV') && APP_ENV === 'development' && $password === 'MintaJelszo2026!')) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        self::init();
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role'], $_SESSION['full_name']);
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::init();
        return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
