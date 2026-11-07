<?php
/**
 * auth/proses_login.php
 * Memeriksa username & password dari form login.
 * Berhasil -> simpan data user ke session lalu menuju Beranda.
 * Gagal    -> kembali ke login dengan pesan error.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';

wajib_post('auth/login.php');

$usernameInput = $_POST['username'] ?? '';
$passwordInput = $_POST['password'] ?? '';
$username = is_string($usernameInput) ? trim($usernameInput) : '';
$password = is_string($passwordInput) ? $passwordInput : '';
$ingat    = isset($_POST['ingat']);

// Cari user berdasarkan username (prepared statement -> aman dari SQL injection)
$st = $pdo->prepare('SELECT id, username, password, nama, role FROM users WHERE username = ?');
$st->execute([$username]);
$user = $st->fetch();

// Di database role ditulis 'Admin' / 'Kasir'; di aplikasi dipakai huruf kecil ('admin' / 'kasir')
$role = $user ? strtolower($user['role']) : '';

// password_verify membandingkan password yang diketik dengan hash di database
if (!$user || !password_verify($password, $user['password'])
    || !in_array($role, ['admin', 'kasir'], true)) {
    flash_set('error', 'Username atau password salah.');
    redirect('auth/login.php');
}

session_regenerate_id(true);   // ganti ID session setelah login (cegah session fixation)
$_SESSION['user'] = [
    'id'   => (int)$user['id'],
    'nama' => $user['nama'],
    'role' => $role,
];

// "Ingat saya": simpan username saja (bukan password) selama 30 hari agar form terisi otomatis
if ($ingat) {
    setcookie('ingat_username', $user['username'], [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => BASE_URL !== '' ? BASE_URL : '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    setcookie('ingat_username', '', [
        'expires' => time() - 3600,
        'path' => BASE_URL !== '' ? BASE_URL : '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

redirect('index.php');
