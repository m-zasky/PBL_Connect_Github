<?php
/**
 * menu/proses_tambah.php
 * Menyimpan menu baru ke database (validasi -> upload foto -> INSERT).
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/menu_helper.php';
require_admin();
wajib_post('menu/tambah.php');

$d = ambil_input_menu();

// 1) Validasi isian
$error = validasi_menu($pdo, $d);
if ($error) {
    old_set($d);
    flash_set('error', $error);
    redirect('menu/tambah.php');
}

// 2) Upload foto (opsional)
[$foto, $error] = simpan_foto($_FILES['foto'] ?? [], null);
if ($error) {
    old_set($d);
    flash_set('error', $error);
    redirect('menu/tambah.php');
}

// 3) Simpan ke database
$st = $pdo->prepare('INSERT INTO menu (nama, kategori, harga, foto, terjual, tersedia) VALUES (?, ?, ?, ?, 0, ?)');
try {
    $st->execute([$d['nama'], $d['kategori'], (int)$d['harga'], $foto, $d['tersedia'] ? 'true' : 'false']);
} catch (Throwable $ex) {
    hapus_foto($foto);
    error_log('Gagal menambahkan menu: ' . $ex->getMessage());
    flash_set('error', 'Menu gagal disimpan. Silakan coba lagi.');
    redirect('menu/tambah.php');
}

flash_set('ok', 'Menu baru ditambahkan ✔');
redirect('menu/list.php');
