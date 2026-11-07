<?php
/**
 * menu/tambah.php
 * Halaman form tambah menu baru. Data dikirim ke proses_tambah.php.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

// Nilai awal form; kalau validasi sebelumnya gagal, pakai isian lama (old_ambil)
$m = array_merge(
    ['id' => 0, 'nama' => '', 'kategori' => 'Makanan', 'harga' => '', 'foto' => null, 'tersedia' => 1],
    old_ambil()
);

$judul_form  = 'Tambah Menu';
$aksi        = 'proses_tambah.php';
$teks_tombol = 'Tambah Menu';

$judul_halaman = 'Tambah Menu';
$menu_aktif    = 'menu';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/form_menu.php';
require __DIR__ . '/../includes/footer.php';
