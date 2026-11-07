<?php
/**
 * kasir/proses_keranjang.php
 * Menerima semua tombol dari form keranjang. Nilai "aksi" berbentuk:
 *   tambah:ID  -> jumlah menu +1
 *   kurang:ID  -> jumlah menu -1 (hilang jika 0)
 *   hapus:ID   -> buang menu dari keranjang
 *   simpan     -> hanya menyimpan opsi pesanan
 *   bayar      -> simpan opsi lalu ke halaman pembayaran
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
wajib_post('kasir/index.php');

/* ---------- 1. Simpan opsi pesanan (tipe, meja, catatan, metode) ---------- */
$opsi = opsi_pesanan();
$tipe = $_POST['tipe'] ?? $opsi['tipe'];
$opsi['tipe'] = in_array($tipe, ['Makan di tempat', 'Bungkus'], true) ? $tipe : 'Makan di tempat';

$meja = $_POST['meja'] ?? $opsi['meja'];
$opsi['meja'] = preg_match('/^Meja 0[1-8]$/', $meja) ? $meja : 'Meja 01';

$metode = $_POST['metode'] ?? $opsi['metode'];
$opsi['metode'] = in_array($metode, ['Tunai', 'QRIS'], true) ? $metode : 'Tunai';

$opsi['catatan'] = mb_substr(trim($_POST['catatan'] ?? $opsi['catatan']), 0, 255);
$_SESSION['opsi'] = $opsi;

/* ---------- 2. Jalankan aksi ---------- */
[$aksi, $id] = array_pad(explode(':', $_POST['aksi'] ?? '', 2), 2, '0');
$id = (int)$id;
$kembali = url_kembali('kasir/index.php');

switch ($aksi) {
    case 'tambah':
        // Hanya menu yang ada dan tersedia yang boleh masuk keranjang
        $st = $pdo->prepare('SELECT id FROM menu WHERE id = ? AND tersedia AND dihapus_pada IS NULL');
        $st->execute([$id]);
        if ($st->fetch()) {
            $_SESSION['keranjang'][$id] = ($_SESSION['keranjang'][$id] ?? 0) + 1;
        } else {
            flash_set('error', 'Menu tidak tersedia.');
        }
        break;

    case 'kurang':
        if (isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]--;
            if ($_SESSION['keranjang'][$id] <= 0) {
                unset($_SESSION['keranjang'][$id]);
            }
        }
        break;

    case 'hapus':
        unset($_SESSION['keranjang'][$id]);
        break;

    case 'simpan':
        flash_set('ok', 'Pesanan disimpan sementara');
        break;

    case 'bayar':
        if (empty($_SESSION['keranjang'])) {
            flash_set('error', 'Pilih menu dulu.');
            break;
        }
        redirect('kasir/pembayaran.php');
}

redirect($kembali);
