<?php
/**
 * Authentication Helper
 * Admin authentication functions
 */

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function login($username, $password) {
    $sql = "SELECT id, username, password_hash, role FROM admin_users WHERE username = ?";
    $user = dbFetchOne($sql, [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        return true;
    }
    
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    session_start();
}

function getCurrentAdmin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null,
    ];
}
?>
