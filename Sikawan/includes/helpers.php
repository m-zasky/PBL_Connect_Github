<?php
/**
 * includes/helpers.php
 * Fungsi bantu yang dipakai banyak halaman: session, redirect, flash message,
 * dan penjaga halaman berdasarkan role (admin / kasir).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Alamat dasar proyek (mis. "/Sikawan"), dihitung otomatis dari lokasi folder ini.
$_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$_doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
define('BASE_URL', rtrim(str_replace($_doc, '', $_root), '/') . '/');
unset($_root, $_doc);

/** Pindah halaman. $path relatif terhadap root proyek, mis. 'auth/login.php'. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/** Halaman ini hanya boleh dibuka lewat form (POST). */
function wajib_post(string $kembali): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($kembali);
    }
}

/** Simpan pesan sekali-tampil (mis. error login). */
function flash_set(string $jenis, string $pesan): void
{
    $_SESSION['flash'][$jenis] = $pesan;
}

/** Ambil lalu hapus pesan sekali-tampil. */
function flash_get(string $jenis): ?string
{
    $pesan = $_SESSION['flash'][$jenis] ?? null;
    unset($_SESSION['flash'][$jenis]);
    return $pesan;
}

/** Halaman tujuan setelah login, sesuai role. */
function halaman_awal(string $role): string
{
    return $role === 'kasir' ? 'kasir/index.php' : 'index.php';
}

/** Pasang di atas halaman yang butuh login. Contoh: wajib_login(['admin']); */
function wajib_login(array $role_boleh = ['admin', 'kasir']): void
{
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        redirect('auth/login.php');
    }
    if (!in_array($user['role'], $role_boleh, true)) {
        redirect(halaman_awal($user['role']));
    }
}

/** Escape output HTML. */
function e(?string $teks): string
{
    return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
}
