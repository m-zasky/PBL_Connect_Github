<?php
/**
 * menu/proses_edit.php
 * Menyimpan perubahan menu (validasi -> upload foto baru kalau ada -> UPDATE).
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/menu_helper.php';
require_admin();
wajib_post('menu/list.php');

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT id, nama, kategori, harga, foto, tersedia
     FROM menu WHERE id = ? AND dihapus_pada IS NULL'
);
$st->execute([$id]);
$menu = $st->fetch();
if (!$menu) {
    flash_set('error', 'Menu tidak ditemukan.');
    redirect('menu/list.php');
}

$balik = 'menu/edit.php?id=' . $id;
$d = ambil_input_menu();

// 1) Validasi (id sendiri dikecualikan dari pengecekan nama kembar)
$error = validasi_menu($pdo, $d, $id);
if ($error) {
    old_set($d);
    flash_set('error', $error);
    redirect($balik);
}

// 2) Foto: kalau ada upload baru, foto lama diganti
[$foto, $error] = simpan_foto($_FILES['foto'] ?? [], $menu['foto']);
if ($error) {
    old_set($d);
    flash_set('error', $error);
    redirect($balik);
}

// 3) Update database
$st = $pdo->prepare('UPDATE menu SET nama = ?, kategori = ?, harga = ?, foto = ?, tersedia = ? WHERE id = ?');
try {
    $st->execute([$d['nama'], $d['kategori'], (int)$d['harga'], $foto, $d['tersedia'] ? 'true' : 'false', $id]);
} catch (Throwable $ex) {
    if ($foto !== $menu['foto']) {
        hapus_foto($foto);
    }
    error_log('Gagal memperbarui menu: ' . $ex->getMessage());
    flash_set('error', 'Perubahan menu gagal disimpan. Silakan coba lagi.');
    redirect($balik);
}

if ($foto !== $menu['foto']) {
    hapus_foto($menu['foto']);
}

// Menu yang ditandai habis otomatis keluar dari keranjang
if (!$d['tersedia']) {
    unset($_SESSION['keranjang'][$id]);
}

flash_set('ok', 'Menu berhasil diperbarui ✔');
redirect('menu/list.php');
