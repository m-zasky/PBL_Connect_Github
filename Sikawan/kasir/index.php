<?php
/**
 * kasir/index.php  (Halaman Kasir)
 * Kiri : daftar menu (cari, filter kategori, paginasi 6 menu per halaman).
 * Kanan: keranjang + opsi pesanan + tombol bayar.
 *
 * Semua tombol (+, -, hapus, bayar, simpan) berada di SATU form (#form-pesanan)
 * dan dikirim ke proses_keranjang.php. Nilai tombol (name="aksi") menentukan tindakannya.
 */
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

/* ---------- 1. Baca parameter filter dari URL ---------- */
$kategori  = in_array($_GET['kategori'] ?? '', KATEGORI, true) ? $_GET['kategori'] : 'Semua';
$cari      = trim($_GET['q'] ?? '');
$halaman   = max(1, (int)($_GET['hal'] ?? 1));
$ringkasan = isset($_GET['ringkasan']);        // mode mobile: tampilkan keranjang saja
$per_hal   = 6;

/* ---------- 2. Ambil menu sesuai filter ---------- */
$where  = ['dihapus_pada IS NULL'];   // menu yang di-soft-delete tidak tampil
$params = [];
if ($kategori !== 'Semua') { $where[] = 'kategori = ?'; $params[] = $kategori; }
if ($cari !== '')          { $where[] = 'nama ILIKE ?';  $params[] = '%' . $cari . '%'; }
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM menu $sql_where");
$st->execute($params);
$total_menu  = (int)$st->fetchColumn();
$total_hal   = max(1, (int)ceil($total_menu / $per_hal));
$halaman     = min($halaman, $total_hal);
$offset      = ($halaman - 1) * $per_hal;            // sudah berupa integer, aman dipakai langsung di SQL

$st = $pdo->prepare(
    "SELECT id, nama, kategori, harga, foto, tersedia
     FROM menu $sql_where ORDER BY id LIMIT $per_hal OFFSET $offset"
);
$st->execute($params);
$tampil = $st->fetchAll();

if (($_GET['ajax'] ?? '') === 'menu') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'menu' => array_map(static function (array $m): array {
            return [
                'id' => (int)$m['id'],
                'nama' => $m['nama'],
                'kategori' => $m['kategori'],
                'harga' => (int)$m['harga'],
                'tersedia' => in_array($m['tersedia'], [true, 1, '1', 't', 'true'], true),
                'jumlah_dipilih' => (int)($_SESSION['keranjang'][$m['id']] ?? 0),
                'gambar' => gambar_menu($m, 'im'),
            ];
        }, $tampil),
        'total' => $total_menu,
        'halaman' => $halaman,
        'total_halaman' => $total_hal,
        'per_halaman' => $per_hal,
    ], JSON_THROW_ON_ERROR);
    exit;
}

// Jumlah menu per kategori (angka kecil di tombol kategori)
$jumlah = ['Semua' => 0];
foreach ($pdo->query('SELECT kategori, COUNT(*) AS n FROM menu WHERE dihapus_pada IS NULL GROUP BY kategori') as $r) {
    $jumlah[$r['kategori']] = (int)$r['n'];
    $jumlah['Semua'] += (int)$r['n'];
}

/* ---------- 3. Data keranjang & opsi ---------- */
$k    = keranjang_detail($pdo);
$opsi = opsi_pesanan();

/** Membuat link dengan mempertahankan filter yang aktif. */
function link_kasir(array $ubah = []): string
{
    global $kategori, $cari, $ringkasan;
    $q = ['kategori' => $kategori === 'Semua' ? null : $kategori, 'q' => $cari ?: null, 'ringkasan' => $ringkasan ? 1 : null];
    return 'index.php?' . http_build_query(array_filter(array_merge($q, $ubah), fn($v) => $v !== null && $v !== ''));
}

$judul_halaman = 'Kasir';
$menu_aktif    = 'kasir';
require __DIR__ . '/../includes/header.php';
?>

<section class="kas <?= $ringkasan ? 'sum' : '' ?>" aria-label="Halaman kasir">

    <!-- ===================== DAFTAR MENU ===================== -->
    <section class="lstw" aria-labelledby="judul-kasir">
        <h2 id="judul-kasir" class="t">Kasir</h2>
        <p class="mut">Pilih menu, layani dengan hati.</p>

        <!-- Form pencarian (GET) -->
        <form id="cari-menu" class="inp mt-14" action="index.php" method="get" role="search">
            <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
            <label for="q" class="sr-only">Cari menu</label>
            <input id="q" type="search" name="q" placeholder="Cari nama menu favorit pelanggan..." value="<?= e($cari) ?>">
            <?php if ($kategori !== 'Semua'): ?><input type="hidden" name="kategori" value="<?= e($kategori) ?>"><?php endif; ?>
        </form>

        <!-- Filter kategori -->
        <nav class="chips" aria-label="Kategori menu">
            <?php foreach (array_merge(['Semua'], KATEGORI) as $c): ?>
                <a class="chip <?= $kategori === $c ? 'on' : '' ?>" data-kategori="<?= e($c) ?>" href="<?= e(link_kasir(['kategori' => $c === 'Semua' ? null : $c])) ?>">
                    <?= e($c) ?> <span class="sm"><?= $jumlah[$c] ?? 0 ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <p id="status-menu" class="sm mut" role="status" aria-live="polite" hidden></p>

        <!-- Kartu menu -->
        <div class="grid">
            <?php foreach ($tampil as $m):
                $qty = $_SESSION['keranjang'][$m['id']] ?? 0; ?>
                <article class="card mi lst <?= $qty ? 'sel' : '' ?> <?= $m['tersedia'] ? '' : 'off' ?>">
                    <?= gambar_menu($m, 'im') ?>
                    <?php if ($qty): ?><span class="tag" style="background:var(--or);color:#fff"><?= $qty ?> dipilih</span><?php endif; ?>
                    <div class="bd">
                        <div class="sm mut"><?= e($m['kategori']) ?><?= $m['tersedia'] ? '' : ' • Habis' ?></div>
                        <b><?= e($m['nama']) ?></b>
                        <div class="baris">
                            <b class="red"><?= rupiah($m['harga']) ?></b>
                            <?php if ($m['tersedia']): ?>
                                <!-- Tombol ini milik #form-pesanan (atribut form=) -->
                                <button type="submit" class="plus" form="form-pesanan" name="aksi"
                                        value="tambah:<?= (int)$m['id'] ?>" aria-label="Tambah <?= e($m['nama']) ?>">+</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$tampil): ?><p class="mut">Menu tidak ditemukan.</p><?php endif; ?>
        </div>

        <!-- Paginasi -->
        <nav class="pg" aria-label="Halaman menu">
            <span class="sm mut" style="flex:1;align-self:center">Menampilkan <?= count($tampil) ?> dari <?= $total_menu ?> menu</span>
            <?php for ($i = 1; $i <= $total_hal; $i++): ?>
                <a class="<?= $i === $halaman ? 'on' : '' ?>" href="<?= e(link_kasir(['hal' => $i > 1 ? $i : null])) ?>"
                   data-halaman="<?= $i ?>" <?= $i === $halaman ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    </section>

    <!-- ===================== KERANJANG ===================== -->
    <aside class="card cart <?= $ringkasan ? 'show' : '' ?>" aria-labelledby="judul-keranjang">
        <form id="form-pesanan" action="proses_keranjang.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="kembali" value="<?= e($_SERVER['REQUEST_URI']) ?>">

            <div class="baris" style="justify-content:flex-start">
                <a class="btn g back" style="padding:6px 12px" href="<?= e(link_kasir(['ringkasan' => null])) ?>" aria-label="Kembali ke daftar menu">←</a>
                <div>
                    <h2 id="judul-keranjang" class="judul-seksi"><?= $ringkasan ? 'Ringkasan pesanan' : 'Pesanan saat ini' ?></h2>
                    <div class="sm mut">Pelanggan</div>
                </div>
            </div>

            <!-- Tipe pesanan -->
            <div class="seg" role="radiogroup" aria-label="Tipe pesanan">
                <?php foreach (['Makan di tempat', 'Bungkus'] as $t): ?>
                    <label>
                        <input type="radio" name="tipe" value="<?= e($t) ?>" data-tipe <?= $opsi['tipe'] === $t ? 'checked' : '' ?>>
                        <span><?= e($t) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Pilih meja (disembunyikan JS saat Bungkus) -->
            <div id="pilih-meja" <?= $opsi['tipe'] === 'Bungkus' ? 'hidden' : '' ?>>
                <label for="meja" class="sr-only">Nomor meja</label>
                <select id="meja" name="meja">
                    <?php for ($i = 1; $i <= 8; $i++): $nm = 'Meja 0' . $i; ?>
                        <option <?= $opsi['meja'] === $nm ? 'selected' : '' ?>><?= $nm ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Daftar item -->
            <div class="ln mt-14"><b>Menu dipesan</b><span class="sm mut"><?= $k['item'] ?> item</span></div>
            <?php foreach ($k['baris'] as $b): $m = $b['menu']; ?>
                <div class="ci">
                    <?= gambar_menu($m, 'th') ?>
                    <div>
                        <b><?= e($m['nama']) ?></b>
                        <div class="sm mut"><?= rupiah($m['harga']) ?></div>
                        <span class="qty">
                            <button type="submit" name="aksi" value="kurang:<?= (int)$m['id'] ?>" aria-label="Kurangi">−</button>
                            <?= $b['qty'] ?>
                            <button type="submit" name="aksi" value="tambah:<?= (int)$m['id'] ?>" aria-label="Tambah">+</button>
                        </span>
                    </div>
                    <div class="teks-kanan">
                        <button type="submit" class="ikon" name="aksi" value="hapus:<?= (int)$m['id'] ?>" aria-label="Hapus <?= e($m['nama']) ?>">🗑</button>
                        <div class="b"><?= rupiah($b['jumlah']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$k['baris']): ?><p class="mut sm">Belum ada menu dipilih.</p><?php endif; ?>

            <label for="catatan" class="sr-only">Catatan pesanan</label>
            <textarea id="catatan" name="catatan" rows="2" maxlength="255"
                      placeholder="Catatan pesanan (mis. sambal dipisah)"><?= e($opsi['catatan']) ?></textarea>

            <!-- Rincian harga -->
            <div class="mt-14">
                <div class="ln mut"><span>Subtotal</span><span><?= rupiah($k['subtotal']) ?></span></div>
                <div class="ln mut"><span>Pajak <?= PAJAK_PERSEN ?>%</span><span><?= rupiah($k['pajak']) ?></span></div>
                <div class="ln" style="align-items:center;border-top:1px solid var(--ln);margin-top:8px;padding-top:10px">
                    <b>Total pembayaran</b><span class="tot"><?= rupiah($k['total']) ?></span>
                </div>
            </div>

            <!-- Metode pembayaran -->
            <div class="b sm mt-10" id="lbl-metode">Metode pembayaran</div>
            <div class="pm" role="radiogroup" aria-labelledby="lbl-metode">
                <?php foreach (['Tunai', 'QRIS'] as $mt): ?>
                    <label>
                        <input type="radio" name="metode" value="<?= $mt ?>" <?= $opsi['metode'] === $mt ? 'checked' : '' ?>>
                        <span><?= $mt ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn" style="width:100%;padding:15px" name="aksi" value="bayar"
                    <?= $k['item'] ? '' : 'disabled' ?>>Bayar <?= rupiah($k['total']) ?> →</button>
            <div class="teks-tengah mt-10">
                <button type="submit" class="mata sm mut" name="aksi" value="simpan">🔖 Simpan pesanan dulu</button>
            </div>
        </form>
    </aside>
</section>

<!-- Bar ringkas khusus mobile -->
<?php if (!$ringkasan): ?>
    <aside class="bar" aria-label="Ringkasan keranjang">
        <div>
            <div class="sm mut"><?= $k['item'] ?> item • <?= e(label_layanan($opsi)) ?></div>
            <b class="tot" style="font-size:20px"><?= rupiah($k['total']) ?></b>
        </div>
        <?php if ($k['item']): ?>
            <a class="btn" href="<?= e(link_kasir(['ringkasan' => 1])) ?>">Lanjut ke ringkasan →</a>
        <?php else: ?>
            <button class="btn" disabled>Pilih menu dulu</button>
        <?php endif; ?>
    </aside>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
