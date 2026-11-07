<?php
/**
 * index.php  (Beranda - KERANGKA)
 * Halaman utama setelah login. Datanya masih DUMMY (tulisan biasa di array),
 * belum diambil dari database. Tujuan tahap ini: menyusun kerangka HTML semantic
 * dan memahami cara include header/footer serta perulangan foreach di PHP.
 */

// ---- Data dummy (nanti diganti hasil query database) ----
$statistik = [
    ['Penjualan hari ini', 'Rp0',  'Termasuk pajak 10%'],
    ['Transaksi selesai',  '0',    'Rata-rata Rp0 / transaksi'],
    ['Menu tersedia',      '12',   'Siap dipesan pelanggan'],
    ['Menu habis',         '0',    'Perlu dicek stoknya'],
];
$menu_terlaris = [
    ['Es Teh Manis',        'Minuman', 'Rp6.000',  63],
    ['Nasi Goreng Kampung', 'Makanan', 'Rp25.000', 48],
    ['Ayam Bakar Madu',     'Makanan', 'Rp28.000', 37],
];

// ---- Template ----
$judul_halaman = 'Beranda';
$base          = '';                        // halaman ini ada di root proyek
require __DIR__ . '/includes/header.php';
?>

<!-- Sambutan -->
<section aria-labelledby="judul-banner">
    <h2 id="judul-banner">Selamat datang, Admin!</h2>
    <p>Semangat melayani! Semua aktivitas warung hari ini ada di sini.</p>
    <a href="kasir/index.php">Buka Mode Kasir</a>
</section>

<!-- Kartu statistik: satu <article> per angka -->
<section aria-label="Statistik hari ini">
    <?php foreach ($statistik as [$judul, $angka, $keterangan]): ?>
        <article>
            <h3><?= $judul ?></h3>
            <p><strong><?= $angka ?></strong></p>
            <p><small><?= $keterangan ?></small></p>
        </article>
    <?php endforeach; ?>
</section>

<!-- Menu terlaris -->
<section aria-labelledby="judul-terlaris">
    <h2 id="judul-terlaris">Menu Terlaris &amp; Favorit</h2>
    <?php foreach ($menu_terlaris as [$nama, $kategori, $harga, $terjual]): ?>
        <article>
            <p><small><?= $kategori ?></small></p>
            <h3><?= $nama ?></h3>
            <p><?= $harga ?> | <?= $terjual ?> terjual</p>
        </article>
    <?php endforeach; ?>
</section>

<!-- Transaksi terbaru (tabel masih kosong) -->
<section aria-labelledby="judul-terbaru">
    <h2 id="judul-terbaru">Transaksi Terbaru</h2>
    <table border="1">
        <caption>Lima transaksi terbaru</caption>
        <thead>
            <tr>
                <th scope="col">No. pesanan</th>
                <th scope="col">Layanan</th>
                <th scope="col">Total</th>
                <th scope="col">Pembayaran</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5">Belum ada transaksi.</td></tr>
        </tbody>
    </table>
</section>

<!-- Menu habis -->
<aside aria-labelledby="judul-habis">
    <h2 id="judul-habis">Menu Habis</h2>
    <p>Semua menu tersedia.</p>
</aside>

<?php require __DIR__ . '/includes/footer.php'; ?>