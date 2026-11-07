<?php
/**
 * kasir/proses_bayar.php
 * Menyelesaikan pembayaran (Tunai atau QRIS):
 *  1. validasi uang diterima untuk tunai (di server, bukan hanya di JavaScript)
 *  2. simpan transaksi + item + tambah jumlah terjual dalam SATU transaksi database
 *  3. kosongkan keranjang lalu tampilkan struk
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
wajib_post('kasir/index.php');

$k    = keranjang_detail($pdo);
$opsi = opsi_pesanan();

if (!$k['item']) {
    flash_set('error', 'Keranjang masih kosong.');
    redirect('kasir/index.php');
}

$total  = $k['total'];
$metode = $opsi['metode'];     // 'Tunai' atau 'QRIS'
$uang   = $total;              // QRIS dianggap pas

/* ---------- 1. Validasi ---------- */
if ($metode === 'Tunai') {
    $uang = (int)($_POST['uang_diterima'] ?? 0);
    if ($uang < $total) {
        flash_set('error', 'Uang diterima kurang dari total tagihan.');
        redirect('kasir/pembayaran.php');
    }
}

/* ---------- 2. Simpan transaksi ---------- */
$tipe = $opsi['tipe'] === 'Bungkus' ? 'Bungkus' : 'Dine-in';
$meja = $tipe === 'Bungkus' ? null : $opsi['meja'];

try {
    $pdo->beginTransaction();   // semua query di bawah berhasil bersama, atau batal semua

    $st = $pdo->prepare(
        "INSERT INTO pesanan (kode, tipe, meja, metode, subtotal, pajak, total, uang_diterima, catatan, kasir_id, kasir_nama)
         VALUES ('TMP', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         RETURNING id"          // PostgreSQL mengembalikan id baru lewat RETURNING
    );
    $st->execute([
        $tipe, $meja, $metode,
        $k['subtotal'], $k['pajak'], $total, $uang,
        $opsi['catatan'] !== '' ? $opsi['catatan'] : null,
        user_login()['id'], user_login()['nama'],
    ]);
    $pesanan_id = (int)$st->fetchColumn();   // id hasil RETURNING

    // Kode pesanan dibuat dari ID, contoh ID 7 -> ORD-0007
    $pdo->prepare('UPDATE pesanan SET kode = ? WHERE id = ?')->execute([sprintf('ORD-%04d', $pesanan_id), $pesanan_id]);

    $item = $pdo->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, nama, harga, qty) VALUES (?, ?, ?, ?, ?)');
    $sold = $pdo->prepare('UPDATE menu SET terjual = terjual + ? WHERE id = ?');
    foreach ($k['baris'] as $b) {
        // Nama & harga disalin agar struk lama tidak berubah kalau menu diedit
        $item->execute([$pesanan_id, $b['menu']['id'], $b['menu']['nama'], $b['menu']['harga'], $b['qty']]);
        $sold->execute([$b['qty'], $b['menu']['id']]);
    }

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', 'Transaksi gagal disimpan, silakan coba lagi.');
    redirect('kasir/pembayaran.php');
}

/* ---------- 3. Bersihkan keranjang, tampilkan struk ---------- */
$_SESSION['keranjang'] = [];
$_SESSION['opsi']['catatan'] = '';
redirect('riwayat/struk.php?id=' . $pesanan_id . '&baru=1');
