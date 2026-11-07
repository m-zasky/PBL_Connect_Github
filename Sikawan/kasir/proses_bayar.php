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

$opsi = opsi_pesanan();
$metode = $opsi['metode'];     // 'Tunai' atau 'QRIS'
$tipe = $opsi['tipe'] === 'Bungkus' ? 'Bungkus' : 'Dine-in';
$meja = $tipe === 'Bungkus' ? null : $opsi['meja'];
$tahapCheckout = 'memulai transaksi database';

try {
    $pdo->beginTransaction();

    // Re-read and lock menu rows so price/availability cannot change during checkout.
    $tahapCheckout = 'memvalidasi keranjang';
    $k = keranjang_detail($pdo, true);
    if (!$k['item']) {
        $pdo->rollBack();
        flash_set('error', 'Keranjang masih kosong.');
        redirect('kasir/index.php');
    }

    $total = $k['total'];
    $uang = $total;
    if ($metode === 'Tunai') {
        $uang = filter_var($_POST['uang_diterima'] ?? null, FILTER_VALIDATE_INT);
        if ($uang === false || $uang < $total) {
            $pdo->rollBack();
            flash_set('error', 'Uang diterima kurang dari total tagihan.');
            redirect('kasir/pembayaran.php');
        }
    }

    $tahapCheckout = 'menyimpan pesanan';
    $st = $pdo->prepare(
        "WITH nomor AS (
             SELECT nextval(pg_get_serial_sequence('pesanan', 'id'))::integer AS id
         )
         INSERT INTO pesanan (id, kode, tipe, meja, metode, subtotal, pajak, total, uang_diterima, catatan, kasir_id, kasir_nama)
         OVERRIDING SYSTEM VALUE
         SELECT id,
                'ORD-' || lpad(id::text, GREATEST(4, length(id::text)), '0'),
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
         FROM nomor
         RETURNING id"
    );
    $st->execute([
        $tipe, $meja, $metode,
        $k['subtotal'], $k['pajak'], $total, $uang,
        $opsi['catatan'] !== '' ? $opsi['catatan'] : null,
        user_login()['id'], user_login()['nama'],
    ]);
    $idTersimpan = $st->fetchColumn();
    if ($idTersimpan === false) {
        throw new RuntimeException('INSERT pesanan tidak mengembalikan id.');
    }
    $pesanan_id = (int)$idTersimpan;

    $tahapCheckout = 'menyimpan item pesanan';
    $item = $pdo->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, nama, harga, qty) VALUES (?, ?, ?, ?, ?)');
    $sold = $pdo->prepare('UPDATE menu SET terjual = terjual + ? WHERE id = ?');
    foreach ($k['baris'] as $b) {
        // Nama & harga disalin agar struk lama tidak berubah kalau menu diedit
        $item->execute([$pesanan_id, $b['menu']['id'], $b['menu']['nama'], $b['menu']['harga'], $b['qty']]);
        $tahapCheckout = 'memperbarui jumlah menu terjual';
        $sold->execute([$b['qty'], $b['menu']['id']]);
    }

    $tahapCheckout = 'menyelesaikan transaksi database';
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $rollbackError) {
            error_log(sprintf(
                'Rollback checkout gagal setelah exception %s: %s',
                get_class($rollbackError),
                $rollbackError->getMessage()
            ));
        }
    }
    $kodeError = strtoupper(bin2hex(random_bytes(3)));
    $sqlState = $ex instanceof PDOException ? $ex->getCode() : 'N/A';
    error_log(sprintf(
        'Transaksi pembayaran gagal [%s] pada tahap "%s", SQLSTATE %s (%s) di %s:%d: %s',
        $kodeError,
        $tahapCheckout,
        $sqlState,
        get_class($ex),
        $ex->getFile(),
        $ex->getLine(),
        $ex->getMessage()
    ));
    $pesanSqlState = $ex instanceof PDOException ? ' SQLSTATE: ' . $sqlState . '.' : '';
    $petunjukDatabase = $sqlState === '25P02'
        ? ' Transaksi database sebelumnya sudah gagal; mulai ulang Apache/PHP agar koneksi dan rencana query lama dibersihkan, lalu coba lagi.'
        : '';
    flash_set(
        'error',
        'Transaksi gagal pada tahap ' . $tahapCheckout . '. Kode: ' . $kodeError . '.' . $pesanSqlState . $petunjukDatabase . ' Periksa log PHP untuk detail.'
    );
    redirect('kasir/pembayaran.php');
}

/* ---------- 3. Bersihkan keranjang, tampilkan struk ---------- */
$_SESSION['keranjang'] = [];
$_SESSION['opsi']['catatan'] = '';
$urlStruk = 'riwayat/struk.php?id=' . $pesanan_id . '&baru=1';
if ($metode === 'QRIS') {
    $urlStruk .= '&print=1';
}
redirect($urlStruk);
