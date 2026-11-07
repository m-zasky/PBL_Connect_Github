<?php
/**
 * form_menu.php
 * Form tambah / edit menu yang dipakai bersama (supaya tidak menulis HTML dua kali).
 *
 * Variabel dari halaman pemanggil:
 *   $m           -> data menu (id, nama, kategori, harga, foto, emoji, tersedia)
 *   $aksi        -> URL tujuan form (proses_tambah.php / proses_edit.php)
 *   $judul_form  -> judul kartu
 *   $teks_tombol -> teks tombol simpan
 */
?>
<section class="card md tengah" aria-labelledby="judul-form">
    <h2 id="judul-form" class="judul-seksi"><?= e($judul_form) ?></h2>

    <form action="<?= e($aksi) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if (!empty($m['id'])): ?>
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <?php endif; ?>

        <label class="fl" for="nama">Nama menu</label>
        <input id="nama" name="nama" class="fi" maxlength="40" required
               placeholder="mis. Soto Ayam" value="<?= e($m['nama']) ?>">

        <label class="fl" for="kategori">Kategori</label>
        <select id="kategori" name="kategori">
            <?php foreach (KATEGORI as $k): ?>
                <option <?= $m['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="fl" for="harga">Harga (Rp)</label>
        <input id="harga" name="harga" class="fi" type="number" min="500" max="1000000" step="500" required
               inputmode="numeric" placeholder="mis. 15000" value="<?= e($m['harga']) ?>">

        <label class="fl" for="foto">Foto (opsional)</label>
        <div class="baris">
            <?= gambar_menu($m + ['nama' => $m['nama'] ?: 'Pratinjau'], 'th') ?>
            <input id="foto" name="foto" type="file" accept="image/*" data-preview-foto style="flex:1;min-width:0">
        </div>
        <p class="sm mut">Maksimal 5 MB, otomatis dikecilkan.</p>

        <label class="cb" for="tersedia">
            <input type="checkbox" id="tersedia" name="tersedia" <?= $m['tersedia'] ? 'checked' : '' ?>>
            Tersedia untuk dipesan
        </label>

        <div class="baris mt-20" style="justify-content:flex-end">
            <a class="btn g" href="<?= url('menu/list.php') ?>">Batal</a>
            <button type="submit" class="btn"><?= e($teks_tombol) ?></button>
        </div>
    </form>
</section>
