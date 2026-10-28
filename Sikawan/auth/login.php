<?php
/**
 * auth/login.php  (KERANGKA)
 * Halaman login berisi form username & password saja.
 * Belum ada pengecekan ke database: untuk sementara tombol Masuk
 * langsung membuka Beranda. Nanti action form diganti ke proses_login.php.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Warung Sambel Orek Tugu 17</title>
    <!-- CSS ditambahkan di tahap berikutnya -->
</head>
<body>

<main>
    <section aria-labelledby="judul-login">
        <h1>Warung Sambel Orek Tugu 17</h1>
        <h2 id="judul-login">Selamat datang kembali!</h2>
        <p>Masuk ke akun Anda untuk mulai mengelola warung.</p>

        <!-- Sementara dikirim ke Beranda. Nanti: action="proses_login.php" -->
        <form action="../index.php" method="post">
            <p>
                <label for="username">Username</label><br>
                <input id="username" name="username" required autofocus>
            </p>
            <p>
                <label for="password">Password</label><br>
                <input id="password" name="password" type="password" required>
            </p>
            <button type="submit">Masuk Sistem</button>
        </form>

        <p><small>Demo: admin / admin123</small></p>
    </section>
</main>

</body>
</html>
