<?php
/**
 * header.php
 * Bagian atas semua halaman setelah login: <head>, header, navigasi, dan pembuka <main>.
 *
 * Variabel yang diisi halaman pemanggil sebelum include:
 *   $judul_halaman  -> judul di tab browser
 *   $menu_aktif     -> kunci tab yang disorot (kasir / beranda / riwayat / menu)
 *   $kelas_body     -> (opsional) class tambahan untuk <body>
 */
$user        = user_login();
$tgl         = tanggal_id();
$flash       = flash_ambil();
$judul_halaman = $judul_halaman ?? 'Kasir';
$menu_aktif  = $menu_aktif ?? '';
$kelas_body  = $kelas_body ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <title><?= e($judul_halaman) ?> - Warung Sambel Orek Tugu 17</title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script src="<?= url('assets/js/app.js') ?>" defer></script>
</head>
<body class="<?= e($kelas_body) ?>">

<header>
    <a class="logo" href="<?= url('index.php') ?>" aria-label="Warung Sambel Orek Tugu 17 - Beranda">
        <img src="<?= url('assets/img/logo-warung.svg') ?>" alt="">
    </a>
    <h1>Warung Sambel Orek Tugu 17</h1>

    <!-- Navigasi utama (desktop) -->
    <nav aria-label="Menu utama">
        <?php foreach (daftar_tab() as $kunci => [$label, $tujuan]): ?>
            <a href="<?= url($tujuan) ?>" class="<?= $menu_aktif === $kunci ? 'on' : '' ?>"
               <?= $menu_aktif === $kunci ? 'aria-current="page"' : '' ?>><?= ikon_tab($kunci) ?> <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <p class="date sm mut">
        <?= e($tgl['hari']) ?><br>
        <time datetime="<?= date('Y-m-d') ?>"><b><?= e($tgl['tanggal']) ?></b></time>
    </p>

    <div class="who">
        <div>
            <b><?= e($user['nama']) ?></b>
            <div class="sm mut"><?= e($user['role']) ?></div>
        </div>
        <form action="<?= url('auth/logout.php') ?>" method="post">
            <?= csrf_field() ?>
            <button class="av" type="submit" title="Keluar" aria-label="Keluar"><?= e(mb_substr($user['nama'], 0, 1)) ?></button>
        </form>
    </div>
</header>

<main>
<?php if ($flash): ?>
    <aside class="alert <?= $flash['jenis'] === 'ok' ? 'alert-ok' : 'alert-err' ?>" role="status" data-flash>
        <?= e($flash['pesan']) ?>
    </aside>
<?php endif; ?>
