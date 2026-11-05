<?php
/**
 * menu/edit.php?id=...
 * Halaman form edit menu; data lama diisi otomatis dari database.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM menu WHERE id = ? AND dihapus_pada IS NULL');
$st->execute([$id]);
$menu = $st->fetch();

if (!$menu) {
    flash_set('error', 'Menu tidak ditemukan.');
    redirect('menu/list.php');
}

// Isian lama (kalau validasi gagal) menimpa data dari database
$m = array_merge($menu, old_ambil());

$judul_form  = 'Edit Menu';
$aksi        = 'proses_edit.php';
$teks_tombol = 'Simpan Perubahan';

$judul_halaman = 'Edit Menu';
$menu_aktif    = 'menu';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/form_menu.php';
require __DIR__ . '/../includes/footer.php';
