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
    'UPDATE menu SET tersedia = NOT tersedia
     WHERE id = ? AND dihapus_pada IS NULL
     RETURNING id, tersedia'
);
$st->execute([$id]);
$menu = $st->fetch(PDO::FETCH_ASSOC);
if (!$menu) {
    flash_set('error', 'Menu tidak ditemukan.');
    redirect('menu/list.php');
}

$tersedia = in_array(strtolower((string)$menu['tersedia']), ['1', 't', 'true'], true);
if (!$tersedia) {
    unset($_SESSION['keranjang'][$id]);
}

flash_set('ok', $tersedia ? 'Menu ditandai tersedia.' : 'Menu ditandai habis.');
redirect('menu/list.php');
