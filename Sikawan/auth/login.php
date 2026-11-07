<?php
/**
 * auth/login.php
 * Halaman login. Form dikirim (POST) ke proses_login.php.
 * Kalau sudah login, langsung diarahkan ke Beranda.
 */
require_once __DIR__ . '/../includes/helpers.php';

if (sudah_login()) {
    redirect('index.php');
}
$flash = flash_ambil();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <title>Masuk - Warung Sambel Orek Tugu 17</title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script src="<?= url('assets/js/app.js') ?>" defer></script>
</head>
<body>
<main id="login">

    <!-- Sisi kiri: identitas warung -->
    <section class="hero" aria-label="Tentang warung">
        <div class="baris" style="justify-content:flex-start;gap:12px">
            <div class="logo" aria-hidden="true">👨‍🍳</div>
            <b style="font-size:18px">Warung Sambel Orek Tugu 17</b>
        </div>
        <div>
            <span class="sm" style="border:1px solid #fff6;border-radius:99px;padding:4px 12px">♡ Rasa rumahan, selalu bikin kangen.</span>
            <h1>Hangat masakannya.<br>Kangen rasanya.</h1>
            <p>Enak | Murah | Nagih</p>
            <p class="sm" style="opacity:.8">Dari pesanan pertama hingga pelanggan terakhir, kelola setiap momen dengan lebih sederhana.</p>
        </div>
    </section>

    <!-- Sisi kanan: form login -->
    <section class="lf" aria-labelledby="judul-login">
        <span class="sm b or">KASIR &amp; MANAJEMEN RESTORAN</span>
        <h2 id="judul-login">Selamat datang kembali!</h2>
        <p class="mut">Siap melayani hari ini? Masuk ke akun Anda untuk mulai mengelola warung.</p>

        <form action="proses_login.php" method="post">
            <?= csrf_field() ?>

            <label for="username">Username</label>
            <div class="inp">
                <span aria-hidden="true">👤</span>
                <input id="username" name="username" required autocomplete="username" autofocus>
            </div>

            <label for="password">Password</label>
            <div class="inp">
                <span aria-hidden="true">🔒</span>
                <input id="password" name="password" type="password" required autocomplete="current-password">
                <button type="button" class="mata" data-toggle-password="#password" aria-label="Tampilkan password">👁</button>
            </div>

            <p class="err" role="alert"><?= $flash ? e($flash['pesan']) : '' ?></p>

            <button type="submit" class="btn" style="width:100%;margin-top:6px;padding:16px">MASUK SISTEM →</button>
        </form>

        <p class="sm mut teks-tengah mt-20">Demo: admin / admin123 &nbsp;•&nbsp; Kasir1 / kasir123</p>
    </section>

</main>
</body>
</html>
