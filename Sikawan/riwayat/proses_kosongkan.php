<?php
/**
 * riwayat/proses_kosongkan.php  (khusus Admin)
 * SOFT DELETE seluruh riwayat: transaksi TIDAK dihapus dari database.
 * Kolom dihapus_pada diisi waktu sekarang, sehingga riwayat tampak kosong
 * di website, tetapi datanya tetap tersimpan untuk arsip & audit.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
wajib_post('riwayat/list.php');

$st = $pdo->prepare('UPDATE pesanan SET dihapus_pada = NOW(), dihapus_oleh = ? WHERE dihapus_pada IS NULL');
$st->execute([user_login()['id']]);

flash_set('ok', 'Riwayat dikosongkan dari tampilan (data tetap tersimpan di database).');
redirect('riwayat/list.php');
