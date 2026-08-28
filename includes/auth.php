<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'nama' => $_SESSION['nama'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'],
    ];
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_role($roles) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        die('Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
