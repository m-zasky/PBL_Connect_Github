<?php
/**
 * riwayat/struk.php?id=...
 * Menampilkan struk satu transaksi. Dipakai untuk:
 *  - struk setelah pembayaran (parameter baru=1 menampilkan kembalian)
 *  - cetak ulang struk dari Riwayat
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare(
    'SELECT id, kode, tanggal, kasir_nama, tipe, meja, subtotal, pajak, total,
            metode, uang_diterima, catatan
     FROM pesanan WHERE id = ? AND dihapus_pada IS NULL'
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash_set('error', 'Transaksi tidak ditemukan.');
    redirect('riwayat/list.php');
}

$st = $pdo->prepare(
    'SELECT id, nama, harga, qty FROM pesanan_item WHERE pesanan_id = ? ORDER BY id'
);
$st->execute([$id]);
$items = $st->fetchAll();

$baru     = isset($_GET['baru']);
$autoPrint = $baru && $p['metode'] === 'QRIS' && isset($_GET['print']);
$kembalian = max(0, (int)$p['uang_diterima'] - (int)$p['total']);

/** Satu baris struk: kiri-kanan. */
function baris_struk(string $kiri, string $kanan, string $kelas = ''): string
{
    return '<div class="r ' . $kelas . '"><span>' . e($kiri) . '</span><span>' . e($kanan) . '</span></div>';
}

$judul_halaman = 'Struk ' . $p['kode'];
$menu_aktif    = $baru ? 'kasir' : 'riwayat';
$kelas_body    = 'halaman-struk' . ($autoPrint ? ' struk-auto-print' : '');
require __DIR__ . '/../includes/header.php';
?>

<section class="payment-page payment-page--receipt receipt-screen" aria-label="Pembayaran berhasil">
    <div class="page-header">
        <div>
            <h2 class="page-title"><?= $baru ? 'Pembayaran berhasil' : 'Struk transaksi' ?></h2>
            <p class="page-subtitle">Pembayaran telah diterima dengan sukses.</p>
        </div>
        <div class="page-meta">
            <span>🧾</span>
            <span><?= e($p['kode']) ?></span>
        </div>
    </div>

    <div class="payment-layout payment-layout--receipt">
        <div class="panel panel-main">
            <div class="panel-inner panel-inner--receipt">
                <div class="receipt-card">
                    <div class="receipt-status">
                        <span class="receipt-status__icon">✓</span>
                        <div>
                            <div class="receipt-status__label">Pembayaran berhasil</div>
                            <div class="receipt-status__total"><?= rupiah($p['total']) ?></div>
                        </div>
                    </div>

                    <div class="receipt-table">
                        <div class="receipt-row"><span>Metode</span><strong><?= e($p['metode']) ?></strong></div>
                        <div class="receipt-row"><span>Nomor transaksi</span><strong><?= e($p['kode']) ?></strong></div>
                        <div class="receipt-row"><span>Kasir</span><strong><?= e($p['kasir_nama']) ?></strong></div>
                        <div class="receipt-row"><span>Tanggal</span><strong><?= date('d M Y H:i', strtotime($p['tanggal'])) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="panel panel-summary">
            <div class="summary-box">
                <div class="summary-header">
                    <span class="summary-header__title">Ringkasan transaksi</span>
                    <span class="summary-header__count"><?= e($p['metode']) ?></span>
                </div>

                <?php foreach ($items as $i): ?>
                    <div class="summary-line">
                        <span><?= e($i['nama']) ?> × <?= (int)$i['qty'] ?></span>
                        <strong><?= rupiah((int)$i['qty'] * (int)$i['harga']) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="summary-line summary-line--muted">
                    <span>Subtotal</span>
                    <strong><?= rupiah($p['subtotal']) ?></strong>
                </div>
                <div class="summary-line summary-line--muted">
                    <span>Pajak</span>
                    <strong><?= rupiah($p['pajak']) ?></strong>
                </div>
                <div class="summary-line summary-line--total">
                    <span>Total</span>
                    <strong><?= rupiah($p['total']) ?></strong>
                </div>
            </div>

            <div class="status-box status-box--success">
                <span class="status-indicator status-indicator--success"></span>
                <span><?= $p['metode'] === 'Tunai' ? 'Tunai' : 'QRIS' ?></span>
            </div>

            <div class="form-actions" style="justify-content:stretch;">
                <button type="button" class="btn btn--ghost" data-cetak style="flex:1">Cetak Struk</button>
                <a class="btn" style="flex:1" href="<?= url($baru ? 'kasir/index.php' : 'riwayat/list.php') ?>">Selesai</a>
            </div>
        </aside>
    </div>
</section>

<article class="struk struk-print" aria-label="Format struk untuk dicetak">
    <div class="teks-tengah">
        <b style="font-size:14px">WARUNG SAMBEL OREK TUGU 17</b><br>Enak | Murah | Nagih
    </div>
    <hr>
    <?= baris_struk('No', $p['kode']) ?>
    <?= baris_struk('Tanggal', date('j M Y H:i', strtotime($p['tanggal']))) ?>
    <?= baris_struk('Kasir', $p['kasir_nama']) ?>
    <?= baris_struk('Layanan', $p['tipe'] . ($p['meja'] ? ' - ' . $p['meja'] : '')) ?>
    <hr>
    <?php foreach ($items as $i): ?>
        <div><?= e($i['nama']) ?></div>
        <?= baris_struk($i['qty'] . ' x ' . rupiah($i['harga']), rupiah($i['qty'] * $i['harga'])) ?>
    <?php endforeach; ?>
    <hr>
    <?= baris_struk('Subtotal', rupiah($p['subtotal'])) ?>
    <?= baris_struk('Pajak ' . PAJAK_PERSEN . '%', rupiah($p['pajak'])) ?>
    <?= baris_struk('TOTAL', rupiah($p['total']), 'b') ?>
    <hr>
    <?= baris_struk('Metode', $p['metode']) ?>
    <?php if ($p['metode'] === 'Tunai'): ?>
        <?= baris_struk('Tunai', rupiah($p['uang_diterima'])) ?>
        <?= baris_struk('Kembali', rupiah($kembalian)) ?>
    <?php endif; ?>
    <?php if ($p['catatan']): ?>
        <hr><div>Catatan: <?= e($p['catatan']) ?></div>
    <?php endif; ?>
    <hr>
    <div class="teks-tengah">Terima kasih, ditunggu kembali!</div>
</article>

<?php require __DIR__ . '/../includes/footer.php'; ?>
