<?php
/**
 * auth/logout.php
 * Menghapus session login lalu mengarahkan kembali ke halaman login.
 */
require_once __DIR__ . '/../includes/helpers.php';

wajib_post('index.php');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax',
    ]);
}
session_destroy();
redirect('auth/login.php');
