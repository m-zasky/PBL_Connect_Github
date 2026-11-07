<?php
/**
 * riwayat/list.php  (Riwayat Transaksi)
 * Tabel semua transaksi (terbaru di atas) + total keseluruhan.
 * Admin bisa mengosongkan riwayat; semua user bisa mencetak laporan & membuka struk.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$daftar = $pdo->query('SELECT * FROM pesanan WHERE dihapus_pada IS NULL ORDER BY id DESC LIMIT 500')->fetchAll();
$rekap  = $pdo->query('SELECT COUNT(*) AS jml, COALESCE(SUM(total),0) AS total, COALESCE(SUM(pajak),0) AS pajak FROM pesanan WHERE dihapus_pada IS NULL')->fetch();

$judul_halaman = 'Riwayat Transaksi';
$menu_aktif    = 'riwayat';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-head" aria-labelledby="judul-riwayat">
    <div>
        <h2 id="judul-riwayat" class="t">Riwayat Transaksi</h2>
        <p class="mut">Rekapitulasi penjualan warung</p>
    </div>
    <div class="noprint">
        <?php if (adalah_admin()): ?>
            <form class="inline" action="proses_kosongkan.php" method="post"
                  data-konfirmasi="Kosongkan seluruh riwayat transaksi?">
                <?= csrf_field() ?>
                <button type="submit" class="btn o">Kosongkan Riwayat</button>
            </form>
        <?php endif; ?>
        <button type="button" class="btn g" data-cetak>🖨 Cetak Laporan</button>
    </div>
</section>

<section class="card scroll-x mt-20" aria-label="Tabel transaksi">
    <table class="rs">
        <caption class="sr-only">Daftar transaksi</caption>
        <thead>
            <tr>
                <th scope="col">Order ID</th><th scope="col">Waktu</th><th scope="col">Tipe / Metode</th>
                <th scope="col">Subtotal &amp; Pajak</th><th scope="col" class="teks-kanan">Total Akhir</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $p): ?>
            <tr>
                <td>
                    <b>#<?= e($p['kode']) ?></b><br>
                    <span class="pill">Selesai</span>
                    <a class="btn g noprint" style="padding:1px 8px;font-size:11px" href="struk.php?id=<?= (int)$p['id'] ?>">Struk</a>
                </td>
                <td>
                    <time datetime="<?= e($p['tanggal']) ?>"><?= date('j M Y', strtotime($p['tanggal'])) ?></time>
                    <div class="sm mut"><?= date('H:i', strtotime($p['tanggal'])) ?> | Kasir: <?= e($p['kasir_nama']) ?></div>
                </td>
                <td>
                    <span class="pill d"><?= e($p['tipe']) ?></span>
                    <div class="sm mut">Via: <?= e($p['metode']) ?></div>
                </td>
                <td>Sub: <?= rupiah($p['subtotal']) ?><div class="sm red">Tax: <?= rupiah($p['pajak']) ?></div></td>
                <td class="teks-kanan"><b class="grn" style="font-size:16px"><?= rupiah($p['total']) ?></b></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?>
            <tr><td colspan="5" class="mut">Belum ada transaksi.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<aside class="card mt-14 baris" style="padding:16px;background:var(--soft)">
    <span>Total <?= (int)$rekap['jml'] ?> transaksi
        <span class="sm mut" style="display:block">Termasuk pajak <?= rupiah($rekap['pajak']) ?></span>
    </span>
    <b class="tot"><?= rupiah($rekap['total']) ?></b>
</aside>

<?php require __DIR__ . '/../includes/footer.php'; ?>
