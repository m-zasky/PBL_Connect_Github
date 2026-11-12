<?php
/**
 * index.php  (Beranda)
 * (Data yang sudah di-soft-delete tidak ikut ditampilkan.)
 * Ringkasan hari ini: penjualan, jumlah transaksi, menu tersedia,
 * menu terlaris, transaksi terbaru, dan daftar menu yang habis.
 */
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Statistik penjualan hari ini
$hari = $pdo->query("SELECT COUNT(*) AS jml, COALESCE(SUM(total),0) AS omzet FROM pesanan WHERE dihapus_pada IS NULL AND tanggal::date = CURRENT_DATE")->fetch();
$jumlah_transaksi = (int)$hari['jml'];
$penjualan        = (int)$hari['omzet'];
$rata_rata        = $jumlah_transaksi ? (int)round($penjualan / $jumlah_transaksi) : 0;

// Jumlah menu tersedia dan habis
$stok = $pdo->query('SELECT COUNT(*) FILTER (WHERE tersedia) AS ada, COUNT(*) FILTER (WHERE NOT tersedia) AS habis FROM menu WHERE dihapus_pada IS NULL')->fetch();
$menu_ada   = (int)$stok['ada'];
$menu_habis = (int)$stok['habis'];

// 5 menu terlaris, 5 transaksi terbaru, daftar menu habis
$terlaris = $pdo->query(
    'SELECT nama, kategori, harga, foto, terjual
     FROM menu WHERE dihapus_pada IS NULL ORDER BY terjual DESC, nama LIMIT 6'
)->fetchAll();
$terbaru = $pdo->query(
    'SELECT kode, tanggal, tipe, total, metode
     FROM pesanan WHERE dihapus_pada IS NULL ORDER BY id DESC LIMIT 6'
)->fetchAll();
$daftar_habis = $pdo->query(
    'SELECT nama FROM menu WHERE NOT tersedia AND dihapus_pada IS NULL ORDER BY nama'
)->fetchAll();

$judul_halaman = 'Beranda';
$menu_aktif    = 'beranda';
require __DIR__ . '/includes/header.php';
?>

<!-- Banner sambutan -->
<section class="banner" aria-labelledby="judul-banner">
    <div>
        <span class="sm b or">● WARUNG BUKA • 05.30–15.30</span>
        <h2 id="judul-banner">Selamat <?= salam() ?>, <?= e(user_login()['nama']) ?>!</h2>
        <p class="mut">Semangat melayani! Semua aktivitas warung hari ini ada di sini.</p>
        <a class="btn banner-action" href="<?= url('kasir/index.php') ?>">
            <?= ikon_tab('kasir') ?> Buka Mode Kasir
        </a>
    </div>
</section>

<!-- Kartu statistik -->
<section class="stats" aria-label="Statistik hari ini">
    <article class="card stat">
        <span class="sm mut">Penjualan hari ini</span>
        <div class="n"><?= rupiah($penjualan) ?></div>
        <span class="sm mut">Termasuk pajak <?= PAJAK_PERSEN ?>%</span>
    </article>
    <article class="card stat">
        <span class="sm mut">Transaksi selesai</span>
        <div class="n"><?= $jumlah_transaksi ?></div>
        <span class="sm mut">Rata-rata <?= rupiah($rata_rata) ?> / transaksi</span>
    </article>
    <article class="card stat">
        <span class="sm mut">Menu tersedia</span>
        <div class="n"><?= $menu_ada ?></div>
        <span class="sm mut">Siap dipesan pelanggan</span>
    </article>
    <article class="card stat">
        <span class="sm mut">Menu habis</span>
        <div class="n <?= $menu_habis ? 'red' : '' ?>"><?= $menu_habis ?></div>
        <span class="sm mut">Perlu dicek stoknya</span>
    </article>
</section>

<!-- Menu terlaris -->
<section aria-labelledby="judul-terlaris">
    <h2 id="judul-terlaris" class="judul-seksi">Menu Terlaris &amp; Favorit</h2>
    <p class="sm mut">Paling sering bikin pelanggan kembali.</p>
    <div class="favs">
        <?php foreach ($terlaris as $m): ?>
            <article class="card mi">
                <?= gambar_menu($m, 'im') ?>
                <span class="tag">Menu Terlaris</span>
                <div class="bd">
                    <div class="sm mut"><?= e($m['kategori']) ?></div>
                    <b><?= e($m['nama']) ?></b>
                    <div class="baris">
                        <b class="red"><?= rupiah($m['harga']) ?></b>
                        <span class="sm mut"><?= (int)$m['terjual'] ?> terjual</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="row2">
    <!-- Transaksi terbaru -->
    <section class="card" aria-labelledby="judul-terbaru">
        <div class="baris" style="padding:18px">
            <h2 id="judul-terbaru" class="judul-kartu">Transaksi Terbaru</h2>
            <a class="link-or" href="<?= url('riwayat/list.php') ?>">Lihat riwayat ↗</a>
        </div>
        <div class="scroll-x">
            <table class="rs">
                <caption class="sr-only">Lima transaksi terbaru</caption>
                <thead>
                    <tr class="mut sm">
                        <th scope="col">No. pesanan</th><th scope="col">Layanan</th>
                        <th scope="col">Total</th><th scope="col">Pembayaran</th><th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($terbaru as $p): ?>
                    <tr>
                        <td>
                            <b>#<?= e($p['kode']) ?></b>
                            <div class="sm mut"><time datetime="<?= e($p['tanggal']) ?>"><?= date('j M Y, H:i', strtotime($p['tanggal'])) ?></time></div>
                        </td>
                        <td><?= e($p['tipe']) ?></td>
                        <td><b><?= rupiah($p['total']) ?></b></td>
                        <td><?= e($p['metode']) ?></td>
                        <td><span class="pill">Lunas</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$terbaru): ?>
                    <tr><td colspan="5" class="mut">Belum ada transaksi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Menu habis -->
    <aside class="card" aria-labelledby="judul-habis">
        <div style="padding:18px">
            <h2 id="judul-habis" class="judul-kartu">Menu Habis</h2>
        </div>
        <ul class="sm" style="list-style:none;margin:0;padding:0 18px 18px">
            <?php foreach ($daftar_habis as $m): ?>
                <li class="ln"><span><?= e($m['nama']) ?></span><span class="pill x">Habis</span></li>
            <?php endforeach; ?>
            <?php if (!$daftar_habis): ?>
                <li class="mut">Semua menu tersedia.</li>
            <?php endif; ?>
        </ul>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
