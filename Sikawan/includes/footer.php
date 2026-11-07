<?php
/**
 * footer.php
 * Bagian bawah semua halaman: penutup <main>, footer desktop,
 * dan navigasi bawah untuk layar mobile.
 * Memakai $menu_aktif dari halaman pemanggil.
 */
?>
</main>

<footer>
    <span>©Warung Sambel Orek Tugu 17</span>
    <span>● Sistem terhubung • Semua perubahan tersimpan</span>
</footer>

<!-- Navigasi bawah (mobile) -->
<nav class="bnav" aria-label="Menu mobile">
    <?php foreach (daftar_tab() as $kunci => [$label, $tujuan]): ?>
        <a href="<?= url($tujuan) ?>" class="<?= ($menu_aktif ?? '') === $kunci ? 'on' : '' ?>">
            <?= ikon_tab($kunci) ?><?= e($label) ?>
        </a>
    <?php endforeach; ?>
</nav>

</body>
</html>
