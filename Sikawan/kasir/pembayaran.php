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

<section class="card md tengah" aria-labelledby="judul-bayar">
    <h2 id="judul-bayar" class="judul-seksi">Pembayaran <?= e($metode) ?></h2>

    <!-- Ringkasan tagihan -->
    <p class="ln mt-10 mut"><span><?= $k['item'] ?> item • <?= e(label_layanan($opsi)) ?></span></p>
    <ul class="sm" style="list-style:none;margin:0;padding:0">
        <?php foreach ($k['baris'] as $b): ?>
            <li class="ln"><span><?= $b['qty'] ?> × <?= e($b['menu']['nama']) ?></span><span><?= rupiah($b['jumlah']) ?></span></li>
        <?php endforeach; ?>
    </ul>
    <p class="ln" style="align-items:center"><b>Total tagihan</b><span class="tot"><?= rupiah($total) ?></span></p>

    <form id="form-bayar" action="proses_bayar.php" method="post"
          data-total="<?= $total ?>" data-metode="<?= e($metode) ?>">
        <?= csrf_field() ?>

        <?php if ($metode === 'Tunai'): ?>
            <label class="fl" for="uang">Uang diterima</label>
            <input id="uang" name="uang_diterima" class="fi" type="number" min="0" inputmode="numeric" placeholder="0" required>

            <div class="pm" style="grid-template-columns:repeat(auto-fit,minmax(90px,1fr))">
                <?php foreach ($cepat as $n): ?>
                    <button type="button" data-uang="<?= $n ?>"><?= $n === $total ? 'Uang pas' : rupiah($n) ?></button>
                <?php endforeach; ?>
            </div>

            <p class="ln" style="font-size:16px">
                <span>Kembalian</span>
                <output id="kembalian" class="b" for="uang">-</output>
            </p>

        <?php else: ?>
            <!-- Kartu QRIS simulasi -->
            <figure class="qris-card" style="margin-left:auto;margin-right:auto">
                <div class="qris-head">
                    <b>QRIS</b>
                    <span>Quick Response Code Indonesian Standard</span>
                </div>
                <div class="qris-body">
                    <p class="qris-merchant">WARUNG SAMBEL OREK TUGU 17</p>
                    <p class="sm" style="color:#666">NMID: ID1020304050607</p>
                    <div class="qris-qr">
                        <img src="<?= url('assets/img/qris/qris.png') ?>" alt="Kode QRIS Warung Sambel Orek Tugu 17">
                    </div>
                    <p class="qris-total"><?= rupiah($total) ?></p>
                    <p class="sm" style="color:#666">
                        Berlaku <b id="qris-timer" data-detik="600">10:00</b>
                        <a id="qris-ulang" href="" hidden>Buat ulang kode</a>
                    </p>
                    <span class="qris-tanda">SIMULASI • bukan transaksi sungguhan</span>
                </div>
                <figcaption class="qris-foot">
                    Scan dengan GoPay, OVO, DANA, ShopeePay, LinkAja, atau m-banking
                </figcaption>
            </figure>
            <p class="sm mut teks-tengah">Minta pelanggan scan, lalu konfirmasi setelah pembayaran masuk.</p>
        <?php endif; ?>

        <div class="baris" style="justify-content:flex-end;margin-top:8px">
            <a class="btn g" href="index.php">Batal</a>
            <button type="submit" class="btn" id="btn-bayar">
                <?= $metode === 'QRIS' ? 'Pembayaran Diterima' : 'Selesaikan Pembayaran' ?>
            </button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
