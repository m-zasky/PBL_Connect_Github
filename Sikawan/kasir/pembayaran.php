<?php
/**
 * kasir/pembayaran.php
 * Form pembayaran sesuai metode yang dipilih di keranjang:
 *   Tunai -> uang diterima + kembalian otomatis (dihitung JavaScript)
 *   QRIS  -> kartu QRIS simulasi dengan hitung mundur
 * Data dikirim ke proses_bayar.php.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$k    = keranjang_detail($pdo);
$opsi = opsi_pesanan();

// Keranjang kosong -> tidak ada yang dibayar
if (!$k['item']) {
    flash_set('error', 'Keranjang masih kosong.');
    redirect('kasir/index.php');
}

$total  = $k['total'];
$metode = $opsi['metode'];

// Pilihan nominal cepat untuk pembayaran tunai: uang pas, pembulatan, dan pecahan umum
$cepat = array_values(array_unique(array_filter(
    [$total, (int)(ceil($total / 10000) * 10000), 50000, 100000, 200000],
    fn($n) => $n >= $total
)));
$cepat = array_slice($cepat, 0, 4);

$judul_halaman = 'Pembayaran';
$menu_aktif    = 'kasir';
require __DIR__ . '/../includes/header.php';
?>

<section class="payment-page <?= $metode === 'QRIS' ? 'payment-page--qris' : 'payment-page--cash' ?>" aria-labelledby="judul-bayar">
    <div class="page-header">
        <div>
            <h2 id="judul-bayar" class="page-title">Pembayaran <?= e($metode) ?></h2>
            <p class="page-subtitle"><?= $metode === 'QRIS' ? 'Tampilkan kode QR kepada pelanggan untuk menyelesaikan pembayaran.' : 'Masukkan uang yang diterima untuk menyelesaikan pembayaran.' ?></p>
        </div>
        <div class="page-meta">
            <span>♧</span>
            <span><?= $opsi['tipe'] === 'Bungkus' ? 'Bungkus' : 'Dine-in · ' . e($opsi['meja']) ?></span>
        </div>
    </div>

    <?php if ($metode === 'Tunai'): ?>
        <div class="cash-payment-card panel">
            <h2 class="cash-title">Pembayaran Tunai</h2>
            <div class="cash-subtitle"><?= $k['item'] ?> item · <?= e(label_layanan($opsi)) ?></div>

            <div class="cash-order-lines">
                <?php foreach ($k['baris'] as $b): ?>
                    <div class="cash-row">
                        <span><?= $b['qty'] ?> × <?= e($b['menu']['nama']) ?></span>
                        <strong><?= rupiah($b['jumlah']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="cash-row">
                    <span>Subtotal</span>
                    <strong><?= rupiah($k['subtotal']) ?></strong>
                </div>
                <div class="cash-row">
                    <span>Pajak <?= PAJAK_PERSEN ?>%</span>
                    <strong><?= rupiah($k['pajak']) ?></strong>
                </div>
                <div class="cash-total-row">
                    <b>Total tagihan</b>
                    <strong><?= rupiah($total) ?></strong>
                </div>
            </div>

            <form id="form-bayar" action="proses_bayar.php" method="post" data-total="<?= $total ?>" data-metode="<?= e($metode) ?>">
                <?= csrf_field() ?>
                <label class="field-label" for="uang">Uang diterima</label>
                <input id="uang" name="uang_diterima" class="amount-input" type="number" min="0" inputmode="numeric" placeholder="0" required>

                <div class="quick-pay cash-quick-pay">
                    <?php foreach ($cepat as $n): ?>
                        <button type="button" class="quick-pay__item" data-uang="<?= (int)$n ?>" aria-pressed="false"><?= $n === $total ? 'Uang pas' : rupiah($n) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="change-row">
                    <span>Kembalian</span>
                    <output id="kembalian" class="change-amount" for="uang">Rp0</output>
                </div>

                <div class="form-actions cash-actions">
                    <a class="btn btn--ghost" href="index.php">Batal</a>
                    <button type="submit" class="btn" id="btn-bayar">Selesaikan Pembayaran</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="payment-layout payment-layout--qris">
            <div class="panel panel-main">
                <div class="panel-title-row">
                    <h3>QRIS</h3>
                    <span class="qr-label">Quick Response Code Indonesian Standard</span>
                </div>

                <div class="qris-payment-card">
                    <div class="qris-merchant">WARUNG SAMBEL OREK TUGU 17</div>
                    <div class="qris-code">NMID: ID1020304050607</div>

                    <div class="qris-qr-shell">
                        <img src="<?= url('assets/img/qris/qris.png') ?>" alt="Kode QRIS Warung Sambel Orek Tugu 17">
                    </div>

                    <div class="qris-total"><?= rupiah($total) ?></div>
                </div>

                <p class="qris-note">Scan dengan GoPay, OVO, DANA, ShopeePay, LinkAja,<br>atau m-banking</p>
            </div>

            <aside class="qris-sidebar">
                <div class="panel panel-summary">
                    <div class="summary-box">
                        <div class="summary-header">
                            <span class="summary-header__title">Ringkasan pesanan</span>
                            <span class="summary-header__count"><?= $k['item'] ?> item</span>
                        </div>

                        <div class="summary-service"><?= $k['item'] ?> item · <?= e(label_layanan($opsi)) ?></div>
                        <?php foreach ($k['baris'] as $b): ?>
                            <div class="summary-line">
                                <span><?= $b['qty'] ?> × <?= e($b['menu']['nama']) ?></span>
                                <strong><?= rupiah($b['jumlah']) ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <div class="summary-line summary-line--muted">
                            <span>Subtotal</span>
                            <strong><?= rupiah($k['subtotal']) ?></strong>
                        </div>
                        <div class="summary-line summary-line--muted">
                            <span>Pajak <?= PAJAK_PERSEN ?>%</span>
                            <strong><?= rupiah($k['pajak']) ?></strong>
                        </div>
                        <div class="summary-line summary-line--total">
                            <span>Total tagihan</span>
                            <strong><?= rupiah($total) ?></strong>
                        </div>
                    </div>

                    <div class="status-box status-box--waiting">
                        <span class="status-indicator"></span>
                        <span>Menunggu pembayaran</span>
                    </div>
                    <p class="qris-status-note">Minta pelanggan scan, lalu konfirmasi setelah pembayaran masuk.</p>

                    <form id="form-bayar" action="proses_bayar.php" method="post" data-total="<?= $total ?>" data-metode="<?= e($metode) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--full" id="btn-bayar">Pembayaran Diterima</button>
                        <a class="btn btn--ghost btn--full qris-cancel" href="index.php">Batal</a>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
