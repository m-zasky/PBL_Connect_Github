<?php
/**
 * helpers.php
 * Kumpulan fungsi bantu yang dipakai di seluruh aplikasi:
 * session, URL, format tampilan, keamanan (CSRF), login, dan keranjang.
 */

date_default_timezone_set('Asia/Jakarta');

// Mulai session sekali saja (dipakai untuk login, keranjang, dan pesan flash)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/* ------------------------------------------------------------
   KONSTANTA
   ------------------------------------------------------------ */
define('PAJAK_PERSEN', 10);
define('KATEGORI', ['Makanan', 'Minuman', 'Snack', 'Paket']);

// BASE_URL = alamat folder proyek terhadap root web server, contoh: /warung-sambel-orek
// Dihitung otomatis supaya link tetap benar di folder mana pun proyek ditaruh.
$docroot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
$root    = str_replace('\\', '/', dirname(__DIR__));
define('BASE_PATH', $root);
define('BASE_URL', ($docroot !== '' && str_starts_with($root, $docroot)) ? substr($root, strlen($docroot)) : '');

/* ------------------------------------------------------------
   URL & REDIRECT
   ------------------------------------------------------------ */

/** Membuat URL absolut dari path relatif proyek. Contoh: url('kasir/index.php') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Pindah halaman lalu hentikan script. */
function redirect(string $path): void
{
    // Jika $path sudah diawali "/" dianggap URL penuh, selain itu relatif proyek
    header('Location: ' . (str_starts_with($path, '/') ? $path : url($path)));
    exit;
}

/**
 * Mengambil URL tujuan kembali dari form (input hidden "kembali").
 * Hanya URL di dalam proyek ini yang diterima, untuk mencegah open redirect.
 */
function url_kembali(string $default): string
{
    $k = $_POST['kembali'] ?? '';
    if ($k !== '' && str_starts_with($k, BASE_URL . '/') && !str_starts_with($k, '//') && !preg_match('/[\r\n]/', $k)) {
        return $k;
    }
    return url($default);
}

/* ------------------------------------------------------------
   FORMAT TAMPILAN
   ------------------------------------------------------------ */

/** Escape HTML supaya aman dari XSS. Selalu pakai saat mencetak data ke halaman. */
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

/** Format angka menjadi rupiah, contoh 25000 -> Rp25.000 */
function rupiah($angka): string
{
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

/** Hitung pajak dari subtotal (dibulatkan ke rupiah terdekat). */
function hitung_pajak(int $subtotal): int
{
    return (int) round($subtotal * PAJAK_PERSEN / 100);
}

/** Nama hari & tanggal dalam bahasa Indonesia dari timestamp. */
function tanggal_id(?int $ts = null): array
{
    $ts    = $ts ?? time();
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return [
        'hari'    => $hari[(int)date('w', $ts)],
        'tanggal' => date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts),
    ];
}

/** Sapaan sesuai jam: Pagi / Siang / Sore / Malam. */
function salam(): string
{
    $jam = (int)date('G');
    if ($jam < 11) return 'Pagi';
    if ($jam < 15) return 'Siang';
    if ($jam < 18) return 'Sore';
    return 'Malam';
}

/** Menampilkan foto menu, atau foto representatif untuk kategorinya. */
function gambar_menu(array $m, string $kelas = 'im'): string
{
    $fotoDefault = [
        'Makanan' => 'ayam.jpg',
        'Minuman' => 'teh.jpg',
        'Snack' => 'pisang.jpg',
        'Paket' => 'nasi Goreng.jpg',
    ];
    $foto = !empty($m['foto']) ? $m['foto'] : ($fotoDefault[$m['kategori']] ?? 'ayam.jpg');
    $src = url('assets/img/menu/' . rawurlencode($foto));
    return '<div class="' . e($kelas) . '" role="img" aria-label="' . e($m['nama']) . '" style="background-image:url(' . e($src) . ')"></div>';
}

/* ------------------------------------------------------------
   PESAN FLASH (tampil sekali lalu hilang) & ISIAN LAMA FORM
   ------------------------------------------------------------ */

/** Simpan pesan untuk ditampilkan di halaman berikutnya. $jenis: 'ok' atau 'error' */
function flash_set(string $jenis, string $pesan): void
{
    $_SESSION['flash'] = ['jenis' => $jenis, 'pesan' => $pesan];
}

/** Ambil pesan flash lalu hapus dari session. */
function flash_ambil(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Simpan isian form agar tidak hilang saat validasi gagal. */
function old_set(array $data): void
{
    $_SESSION['old'] = $data;
}

/** Ambil isian form lama (sekali pakai). */
function old_ambil(): array
{
    $o = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $o;
}

/* ------------------------------------------------------------
   KEAMANAN: CSRF
   Setiap form POST membawa token rahasia agar tidak bisa
   dikirim dari situs lain.
   ------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Cetak <input hidden> berisi token. Taruh di dalam setiap <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Pastikan request berupa POST dengan token yang benar.
 * Dipanggil di awal semua file proses_*.php.
 */
function wajib_post(string $kembali = 'index.php'): void
{
    $tokenSesi = $_SESSION['csrf'] ?? '';
    $tokenForm = $_POST['csrf'] ?? '';
    $ok = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && is_string($tokenSesi) && $tokenSesi !== ''
        && is_string($tokenForm) && $tokenForm !== ''
        && hash_equals($tokenSesi, $tokenForm);
    if (!$ok) {
        flash_set('error', 'Permintaan tidak valid atau sesi form sudah kedaluwarsa. Silakan ulangi.');
        redirect($kembali);
    }
}

/* ------------------------------------------------------------
   LOGIN & HAK AKSES
   ------------------------------------------------------------ */

/** Data user yang sedang login (array) atau null. */
function user_login(): ?array
{
    return $_SESSION['user'] ?? null;
}

function sudah_login(): bool
{
    return user_login() !== null;
}

function adalah_admin(): bool
{
    return sudah_login() && strtolower((string)user_login()['role']) === 'admin';
}

/** Halaman wajib login; kalau belum, lempar ke halaman login. */
function require_login(): void
{
    if (!sudah_login()) {
        redirect('auth/login.php');
    }
}

/** Halaman khusus Admin; Kasir akan dikembalikan ke Beranda. */
function require_admin(): void
{
    require_login();
    if (!adalah_admin()) {
        flash_set('error', 'Halaman ini hanya untuk Admin.');
        redirect('index.php');
    }
}

/** Daftar tab navigasi (dipakai header desktop dan menu bawah mobile). */
function daftar_tab(): array
{
    $tab = [
        'kasir'   => ['Kasir', 'kasir/index.php'],
        'beranda' => ['Beranda', 'index.php'],
        'riwayat' => ['Riwayat', 'riwayat/list.php'],
    ];
    if (adalah_admin()) {
        $tab['menu'] = ['Kelola Menu', 'menu/list.php'];
    }
    return $tab;
}

/** Ikon navigasi berbasis SVG agar konsisten dan tidak bergantung emoji/font. */
function ikon_tab(string $kunci): string
{
    $jalur = [
        'kasir' => '<path d="M3 4h2l2.2 11h10.9l2-8H6"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
        'beranda' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'riwayat' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'menu' => '<path d="M4 20h16M6 17h12l-1-8H7l-1 8Z"/><path d="M9 9c0-2 1-3 3-5 2 2 3 3 3 5"/>',
    ];
    return '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
        . ($jalur[$kunci] ?? '') . '</svg>';
}

/* ------------------------------------------------------------
   KERANJANG & OPSI PESANAN (disimpan di session)
   $_SESSION['keranjang'] = [ id_menu => jumlah ]
   $_SESSION['opsi']      = tipe, meja, catatan, metode
   ------------------------------------------------------------ */

/** Opsi pesanan saat ini, dengan nilai default. */
function opsi_pesanan(): array
{
    $opsi = array_merge(
        ['tipe' => 'Makan di tempat', 'meja' => 'Meja 01', 'catatan' => '', 'metode' => 'Tunai'],
        $_SESSION['opsi'] ?? []
    );
    // Metode bayar yang tersedia hanya Tunai dan QRIS
    if (!in_array($opsi['metode'], ['Tunai', 'QRIS'], true)) {
        $opsi['metode'] = 'Tunai';
    }
    return $opsi;
}

/**
 * Mengubah isi keranjang (id => qty) menjadi data lengkap + total.
 * Menu yang sudah dihapus / ditandai habis otomatis dikeluarkan.
 * Return: ['baris' => [...], 'item' => int, 'subtotal' => int, 'pajak' => int, 'total' => int]
 */
function keranjang_detail(PDO $pdo, bool $kunciBaris = false): array
{
    $keranjang = $_SESSION['keranjang'] ?? [];
    $baris = [];
    $item = 0;
    $subtotal = 0;

    if ($keranjang) {
        $ids = array_keys($keranjang);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $st  = $pdo->prepare(
            "SELECT id, nama, kategori, harga, foto, terjual, tersedia, dihapus_pada
             FROM menu WHERE id IN ($in) ORDER BY id" . ($kunciBaris ? ' FOR UPDATE' : '')
        );
        $st->execute($ids);
        $menu = [];
        foreach ($st->fetchAll() as $m) {
            $menu[(int)$m['id']] = $m;
        }

        foreach ($keranjang as $id => $qty) {
            if (!isset($menu[$id]) || !$menu[$id]['tersedia'] || $menu[$id]['dihapus_pada'] !== null) {
                unset($_SESSION['keranjang'][$id]);   // menu hilang / habis / sudah dihapus
                continue;
            }
            $jumlah   = (int)$qty * (int)$menu[$id]['harga'];
            $baris[]  = ['menu' => $menu[$id], 'qty' => (int)$qty, 'jumlah' => $jumlah];
            $item    += (int)$qty;
            $subtotal += $jumlah;
        }
    }

    $pajak = hitung_pajak($subtotal);
    return ['baris' => $baris, 'item' => $item, 'subtotal' => $subtotal, 'pajak' => $pajak, 'total' => $subtotal + $pajak];
}

/** Label lokasi pesanan: "Bungkus" atau nama meja. */
function label_layanan(array $opsi): string
{
    return $opsi['tipe'] === 'Bungkus' ? 'Bungkus' : $opsi['meja'];
}

/* ------------------------------------------------------------
   QRIS SIMULASI
   ------------------------------------------------------------ */

/**
 * Membuat gambar "QR" palsu (SVG) dari angka seed, lengkap dengan 3 kotak sudut,
 * garis timing, dan kotak alignment supaya mirip QR sungguhan.
 * Hanya untuk SIMULASI, tidak bisa discan dan bukan QRIS asli.
 */
function qris_svg(int $seed): string
{
    $s = $seed ?: 7;
    $n = 25;
    $kotak = '';
    $acak = function () use (&$s) {                        // pseudo-random sederhana
        $s = ($s * 1103515245 + 12345) % 2147483648;
        return $s / 2147483648;
    };
    $penanda = fn($x, $y) => ($x < 8 && $y < 8) || ($x > 16 && $y < 8) || ($x < 8 && $y > 16);

    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if ($penanda($x, $y)) {                         // tiga kotak sudut khas QR
                $d  = max(abs($x % 17 - 3), abs($y % 17 - 3));
                $on = $d !== 2 && $d < 4;
            } elseif ($y === 6 && $x >= 8 && $x <= 16) {    // garis timing horizontal
                $on = $x % 2 === 0;
            } elseif ($x === 6 && $y >= 8 && $y <= 16) {    // garis timing vertikal
                $on = $y % 2 === 0;
            } elseif ($x >= 18 && $x <= 22 && $y >= 18 && $y <= 22) {   // kotak alignment
                $d  = max(abs($x - 20), abs($y - 20));
                $on = $d !== 1;
            } else {
                $on = $acak() > 0.5;
            }
            if ($on) {
                $kotak .= "<rect x=\"$x\" y=\"$y\" width=\"1\" height=\"1\"/>";
            }
        }
    }
    return '<svg viewBox="-1 -1 27 27" width="220" height="220" role="img" aria-label="Kode QRIS simulasi" '
         . 'style="background:#fff" fill="#111">' . $kotak . '</svg>';
}
