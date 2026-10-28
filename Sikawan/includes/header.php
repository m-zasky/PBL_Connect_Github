<?php
/**
 * includes/header.php  (KERANGKA)
 * Bagian atas halaman: pembuka HTML, <header>, navigasi, dan pembuka <main>.
 */

$judul_halaman = $judul_halaman ?? 'Beranda';
$base          = $base ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul_halaman) ?> - Warung Sambel Orek Tugu 17</title>
    <!-- CSS ditambahkan di tahap berikutnya -->
</head>
<body>

<header>
    <h1>Warung Sambel Orek Tugu 17</h1>

    <!-- Navigasi utama -->
    <nav aria-label="Menu utama">
        <ul>
            <li><a href="<?= $base ?>kasir/index.php">Kasir</a></li>
            <li><a href="<?= $base ?>index.php">Beranda</a></li>
            <li><a href="<?= $base ?>riwayat/list.php">Riwayat</a></li>
            <li><a href="<?= $base ?>menu/list.php">Kelola Menu</a></li>
        </ul>
    </nav>

    <!-- Nama user masih tulisan biasa; nanti diambil dari session setelah login -->
    <p>Halo, <strong>Admin</strong> | <a href="<?= $base ?>auth/logout.php">Keluar</a></p>
</header>

<main>
