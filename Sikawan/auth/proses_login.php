<?php
/**
 * auth/proses_login.php
 * Memeriksa username & password dari form login.
 * Berhasil -> simpan data user ke session lalu ke Beranda.
 * Gagal    -> kembali ke login dengan pesan error.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';

wajib_post('auth/login.php');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Cari user berdasarkan username (prepared statement -> aman dari SQL injection)
$st = $pdo->prepare('SELECT id, username, password, nama, role FROM users WHERE username = ?');
$st->execute([$username]);
$user = $st->fetch();

// password_verify membandingkan password yang diketik dengan hash di database
if (!$user || !password_verify($password, $user['password'])) {
    flash_set('error', 'Username atau password salah.');
    redirect('auth/login.php');
}

session_regenerate_id(true);   // ganti ID session setelah login (cegah session fixation)
$_SESSION['user'] = [
    'id'   => (int)$user['id'],
    'nama' => $user['nama'],
    'role' => $user['role'],
];
redirect('index.php');
