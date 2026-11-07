<?php
/**
 * menu/proses_toggle.php
 * Membalik status menu: tersedia <-> habis.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
wajib_post('menu/list.php');

$id = (int)($_POST['id'] ?? 0);

// NOT tersedia => true jadi false, false jadi true
$pdo->prepare('UPDATE menu SET tersedia = NOT tersedia WHERE id = ? AND dihapus_pada IS NULL')->execute([$id]);

// Jika sekarang habis, buang dari keranjang yang sedang berjalan
$st = $pdo->prepare('SELECT tersedia FROM menu WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch();
if ($row && !$row['tersedia']) {
    unset($_SESSION['keranjang'][$id]);
}

redirect('menu/list.php');
