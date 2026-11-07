<?php
/**
 * menu_helper.php
 * Fungsi bantu khusus fitur Kelola Menu: ambil input, validasi, dan upload foto.
 * Dipakai oleh menu/proses_tambah.php dan menu/proses_edit.php.
 */

/** Mengambil & membersihkan input form menu dari $_POST. */
function ambil_input_menu(): array
{
    return [
        'nama'     => preg_replace('/\s+/', ' ', trim($_POST['nama'] ?? '')),   // spasi ganda dirapikan
        'kategori' => $_POST['kategori'] ?? '',
        'harga'    => trim($_POST['harga'] ?? ''),
        'tersedia' => isset($_POST['tersedia']) ? 1 : 0,
    ];
}

/**
 * Validasi input menu. Return string pesan error, atau null kalau semua valid.
 * $idSendiri diisi saat edit, supaya nama menu sendiri tidak dianggap duplikat.
 */
function validasi_menu(PDO $pdo, array $d, int $idSendiri = 0): ?string
{
    if ($d['nama'] === '') {
        return 'Nama menu wajib diisi.';
    }
    if (mb_strlen($d['nama']) > 40) {
        return 'Nama menu maksimal 40 karakter.';
    }
    if (!in_array($d['kategori'], KATEGORI, true)) {
        return 'Kategori tidak valid.';
    }

    // Nama tidak boleh sama dengan menu lain
    $st = $pdo->prepare('SELECT id FROM menu WHERE LOWER(nama) = LOWER(?) AND id <> ? AND dihapus_pada IS NULL');
    $st->execute([$d['nama'], $idSendiri]);
    if ($st->fetch()) {
        return 'Nama menu sudah ada, gunakan nama lain.';
    }

    if (!ctype_digit($d['harga'])) {
        return 'Harga harus berupa angka bulat.';
    }
    $harga = (int)$d['harga'];
    if ($harga < 500)       return 'Harga minimal Rp500.';
    if ($harga % 500 !== 0) return 'Harga harus kelipatan Rp500.';
    if ($harga > 1000000)   return 'Harga maksimal Rp1.000.000.';

    return null;
}

/**
 * Memproses upload foto (opsional).
 * - Tidak ada file baru  -> kembalikan nama foto lama.
 * - Ada file baru        -> validasi, kecilkan jadi 240x128 (kalau ekstensi GD aktif), simpan.
 * Return: [nama_file_atau_null, pesan_error_atau_null]
 */
function simpan_foto(array $file, ?string $fotoLama): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [$fotoLama, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [$fotoLama, 'Upload foto gagal, coba lagi.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return [$fotoLama, 'Ukuran foto maksimal 5 MB.'];
    }

    // Cek isi file benar-benar gambar (bukan sekadar ekstensi)
    $info = @getimagesize($file['tmp_name']);
    $boleh = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!$info || !isset($boleh[$info['mime']])) {
        return [$fotoLama, 'File harus berupa gambar (JPG, PNG, WEBP, atau GIF).'];
    }

    $folder = BASE_PATH . '/assets/img/menu/';
    $nama   = 'menu_' . bin2hex(random_bytes(6));

    if (function_exists('imagecreatefromstring')) {
        // Kecilkan & potong tengah (cover) menjadi 240x128, simpan sebagai JPG
        $src = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$src) {
            return [$fotoLama, 'Gambar tidak bisa dibaca.'];
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $k  = max(240 / $w, 128 / $h);
        $sw = 240 / $k;
        $sh = 128 / $k;
        $dst = imagecreatetruecolor(240, 128);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // latar putih untuk PNG transparan
        imagecopyresampled($dst, $src, 0, 0, (int)(($w - $sw) / 2), (int)(($h - $sh) / 2), 240, 128, (int)$sw, (int)$sh);
        $berhasil = imagejpeg($dst, $folder . $nama . '.jpg', 75);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$berhasil) {
            return [$fotoLama, 'Foto gagal disimpan.'];
        }
        $nama .= '.jpg';
    } else {
        // Tanpa GD: simpan apa adanya
        $nama .= '.' . $boleh[$info['mime']];
        if (!move_uploaded_file($file['tmp_name'], $folder . $nama)) {
            return [$fotoLama, 'Foto gagal disimpan.'];
        }
    }

    return [$nama, null];
}

/** Hapus file foto hasil upload. Foto bawaan (nasi.jpg dll.) tidak ikut dihapus. */
function hapus_foto(?string $nama): void
{
    if ($nama && str_starts_with($nama, 'menu_')) {
        $path = BASE_PATH . '/assets/img/menu/' . basename($nama);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
