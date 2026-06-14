<?php
// ─── Session Auth Helper ──────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false, // set true when using HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();

    if (empty($_SESSION['admin_logged_in'])) {
        return false;
    }

    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function login(string $username, string $password): bool {
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity']   = time();
        return true;
    }
    return false;
}

function logout(): void {
    startSecureSession();
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
