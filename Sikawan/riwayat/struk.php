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

$st = $pdo->prepare('SELECT * FROM pesanan WHERE id = ? AND dihapus_pada IS NULL');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash_set('error', 'Transaksi tidak ditemukan.');
    redirect('riwayat/list.php');
}

$st = $pdo->prepare('SELECT * FROM pesanan_item WHERE pesanan_id = ? ORDER BY id');
$st->execute([$id]);
$items = $st->fetchAll();

$baru     = isset($_GET['baru']);
$kembalian = max(0, (int)$p['uang_diterima'] - (int)$p['total']);

/** Satu baris struk: kiri-kanan. */
function baris_struk(string $kiri, string $kanan, string $kelas = ''): string
{
    return '<div class="r ' . $kelas . '"><span>' . e($kiri) . '</span><span>' . e($kanan) . '</span></div>';
}

$judul_halaman = 'Struk ' . $p['kode'];
$menu_aktif    = $baru ? 'kasir' : 'riwayat';
$kelas_body    = 'halaman-struk';
require __DIR__ . '/../includes/header.php';
?>

<section class="card md tengah" style="max-width:380px" aria-label="Struk pembayaran">

    <!-- Pesan sukses / kembalian (tidak ikut tercetak) -->
    <div class="teks-tengah noprint" style="margin-bottom:8px">
        <?php if ($baru && $p['metode'] === 'Tunai'): ?>
            <div class="sm mut">Kembalian</div>
            <div class="tot"><?= rupiah($kembalian) ?></div>
        <?php elseif ($baru): ?>
            <b class="grn">✔ Pembayaran berhasil</b>
        <?php endif; ?>
    </div>

    <!-- Isi struk -->
    <article class="struk">
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

    <div class="baris mt-14 noprint">
        <button type="button" class="btn g" style="flex:1" data-cetak>🖨 Cetak Struk</button>
        <a class="btn" style="flex:1" href="<?= url($baru ? 'kasir/index.php' : 'riwayat/list.php') ?>">Selesai</a>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
