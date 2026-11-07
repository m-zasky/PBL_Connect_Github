<?php
/**
 * menu/list.php  (Kelola Menu - khusus Admin)
 * Menampilkan semua menu beserta tombol: tandai habis/tersedia, edit, dan hapus.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$daftar = $pdo->query(
    'SELECT id, nama, kategori, harga, foto, terjual, tersedia
     FROM menu WHERE dihapus_pada IS NULL ORDER BY id'
)->fetchAll();
$total  = count($daftar);
$ada    = count(array_filter($daftar, fn($m) => $m['tersedia']));

$judul_halaman = 'Kelola Menu';
$menu_aktif    = 'menu';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-head" aria-labelledby="judul-menu">
    <div>
        <h2 id="judul-menu" class="t">Kelola Menu</h2>
        <p class="mut">Tambah menu baru dan atur ketersediaan menu warung hari ini</p>
    </div>
    <a class="btn" href="tambah.php">+ Tambah Menu</a>
</section>

<section class="stats tiga" aria-label="Ringkasan menu">
    <article class="card stat"><span class="sm mut">Total menu</span><div class="n"><?= $total ?></div></article>
    <article class="card stat"><span class="sm mut">Tersedia</span><div class="n grn"><?= $ada ?></div></article>
    <article class="card stat"><span class="sm mut">Habis</span><div class="n red"><?= $total - $ada ?></div></article>
</section>

<section class="card scroll-x" aria-label="Daftar menu">
    <table>
        <caption class="sr-only">Daftar menu warung</caption>
        <thead>
            <tr>
                <th scope="col">Gambar</th><th scope="col">Nama Menu</th><th scope="col">Kategori</th>
                <th scope="col">Harga</th><th scope="col">Status Saat Ini</th><th scope="col" class="teks-kanan">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $m): ?>
            <tr>
                <td><?= gambar_menu($m, 'th') ?></td>
                <td><b><?= e($m['nama']) ?></b><div class="sm mut"><?= (int)$m['terjual'] ?> terjual</div></td>
                <td><span class="pill k"><?= e($m['kategori']) ?></span></td>
                <td><b class="grn"><?= rupiah($m['harga']) ?></b></td>
                <td><span class="pill <?= $m['tersedia'] ? '' : 'x' ?>"><?= $m['tersedia'] ? 'Tersedia' : 'Habis' ?></span></td>
                <td class="teks-kanan" style="white-space:nowrap">
                    <!-- Tandai habis / tersedia -->
                    <form class="inline" action="proses_toggle.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn kecil <?= $m['tersedia'] ? 'o' : 'g' ?>">
                            <?= $m['tersedia'] ? 'Tandai Habis' : 'Tandai Tersedia' ?>
                        </button>
                    </form>

                    <a class="btn g kecil" href="edit.php?id=<?= (int)$m['id'] ?>">Edit</a>

                    <!-- Hapus = soft delete: hanya disembunyikan dari tampilan, baris di database tetap ada -->
                    <form class="inline" action="hapus.php" method="post"
                          data-konfirmasi="Hapus menu &quot;<?= e($m['nama']) ?>&quot;? (Data tetap tersimpan aman di database)">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn g kecil">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<aside class="card mt-14" style="padding:16px">
    <h3 style="margin:0;font-size:14px">Aturan pengelolaan menu</h3>
    <ul class="sm mut" style="margin:8px 0 0;padding-left:18px;line-height:1.8">
        <li>Nama menu wajib diisi dan tidak boleh sama dengan menu lain.</li>
        <li>Harga minimal Rp500 dan berupa kelipatan Rp500.</li>
        <li>Menu berstatus <b>Habis</b> tidak bisa dipilih di halaman Kasir dan otomatis keluar dari keranjang.</li>
        <li>Tombol <b>Hapus</b> hanya menyembunyikan menu dari tampilan (soft delete). Datanya tetap tersimpan di database untuk arsip dan audit.</li>
        <li>Mengubah harga hanya berlaku untuk pesanan berikutnya. Transaksi lama di Riwayat tidak berubah.</li>
        <li>Foto bersifat opsional dan akan dikecilkan otomatis.</li>
    </ul>
</aside>

<?php require __DIR__ . '/../includes/footer.php'; ?>
