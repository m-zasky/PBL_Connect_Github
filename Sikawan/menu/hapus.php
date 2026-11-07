<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
wajib_post('menu/list.php');

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    flash_set('error', 'Menu tidak valid.');
    redirect('menu/list.php');
}

$st = $pdo->prepare(
    'UPDATE menu SET dihapus_pada = NOW(), dihapus_oleh = ?
     WHERE id = ? AND dihapus_pada IS NULL'
);
$st->execute([user_login()['id'], $id]);
if ($st->rowCount() === 0) {
    flash_set('error', 'Menu tidak ditemukan atau sudah dihapus.');
    redirect('menu/list.php');
}

unset($_SESSION['keranjang'][$id]);
flash_set('ok', 'Menu dihapus dari daftar. Data tetap tersimpan untuk arsip.');
redirect('menu/list.php');
