<?php
/**
 * auth/login.php
 * Halaman login (username & password) untuk 2 role: admin dan kasir.
 * Role tidak dipilih di form, tapi dibaca dari database oleh proses_login.php.
 */
require_once __DIR__ . '/../includes/helpers.php';

// Sudah login? Langsung ke halaman sesuai role-nya.
if (!empty($_SESSION['user'])) {
    redirect(halaman_awal($_SESSION['user']['role']));
}

$error    = flash_get('error');
$username = $_COOKIE['ingat_username'] ?? '';   // terisi jika sebelumnya centang "Ingat saya"
$base     = '../';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Warung Sambel Orek Tugu 17</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="halaman-login">

<div class="split">

<!-- Panel kiri: foto + tagline. Ganti foto di assets/img/login-bg.jpg -->
<aside class="hero" aria-label="Warung Sambel Orek Tugu 17">
    <div class="hero__merek">
        <span class="hero__logo" aria-hidden="true">
            <svg class="ikon" viewBox="0 0 24 24"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/><path d="M10 20v-4h4v4"/></svg>
        </span>
        Warung Sambel Orek Tugu 17
    </div>

    <div class="hero__isi">
        <p class="hero__pil">
            <svg class="ikon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            Rasa rumahan, selalu bikin kangen.
        </p>
        <h2 class="hero__judul">Hangat masakannya.<br>Kangen rasanya.</h2>
        <p class="hero__sub">Warung Sambel Orek Tugu 17 - Enak | Murah | Nagih</p>
        <span class="hero__garis" aria-hidden="true"></span>
        <p class="hero__teks">Dari pesanan pertama hingga pelanggan terakhir, kelola setiap momen dengan lebih sederhana.</p>
    </div>
</aside>

<main class="login">

    <p class="login__akses">
        <svg class="ikon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        Akses khusus staf
    </p>

    <section class="login__kartu" aria-labelledby="judul-login">
        <div class="login__logo" aria-hidden="true">
            <svg class="ikon" viewBox="0 0 24 24"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/><path d="M10 20v-4h4v4"/></svg>
        </div>

        <p class="login__label">Kasir &amp; Manajemen Warung</p>
        <h1 id="judul-login">Selamat datang</h1>
        <p class="login__sub">Siap melayani hari ini? Masuk ke akun Anda untuk mulai mengelola warung.</p>

        <?php if ($error): ?>
            <p class="login__error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form action="proses_login.php" method="post">
            <div class="field">
                <label for="username">Username</label>
                <div class="input">
                    <svg class="ikon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <input id="username" name="username" type="text" value="<?= e($username) ?>"
                           placeholder="Masukkan username" autocomplete="username" required <?= $username ? '' : 'autofocus' ?>>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input">
                    <svg class="ikon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    <input id="password" name="password" type="password"
                           placeholder="Masukkan password" autocomplete="current-password" required <?= $username ? 'autofocus' : '' ?>>
                    <button type="button" class="input__mata" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg class="ikon ikon--mata" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="ikon ikon--mata-coret" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a17.7 17.7 0 0 1 4.1-4.9M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 0 1-4.2-4.2"/><path d="M2 2l20 20"/></svg>
                    </button>
                </div>
            </div>

            <div class="login__baris">
                <label class="cek">
                    <input type="checkbox" name="ingat" checked>
                    <span class="cek__kotak" aria-hidden="true"></span>
                    Ingat saya
                </label>
                <a href="#bantuan" class="link-bantuan">Lupa password?</a>
            </div>

            <button type="submit" class="tombol">
                Masuk Sistem
                <svg class="ikon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <p class="login__bantuan" id="bantuan">
            Kendala masuk? <a href="#bantuan" class="link-bantuan">Hubungi pengelola.</a>
        </p>
        <p class="login__catatan" id="catatan-bantuan" hidden>
            Silakan minta pengelola (admin) untuk mengatur ulang password Anda.
        </p>
    </section>

    <p class="login__footer"><small>©Warung Sambel Orek Tugu 17</small></p>
</main>

</div>

<script src="<?= $base ?>assets/js/app.js"></script>
</body>
</html>
