# Sikawan

Aplikasi kasir warung berbasis PHP dan PostgreSQL.

## Persyaratan

- PHP 8.0 atau lebih baru dengan ekstensi `pdo_pgsql`, `mbstring`, dan `fileinfo`.
- PostgreSQL.
- Ekstensi GD direkomendasikan untuk mengecilkan foto menu.

## Konfigurasi database

Atur variabel lingkungan berikut di konfigurasi lokal/server web. Jangan simpan kredensial dalam source code atau commit:

- `DB_HOST`
- `DB_PORT` (opsional, default `5432`)
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_SSLMODE` (opsional, default `require`; wajib untuk koneksi Neon)

Contoh PowerShell untuk sesi lokal:

```powershell
$env:DB_HOST = "hostname-database"
$env:DB_PORT = "5432"
$env:DB_NAME = "nama-database"
$env:DB_USER = "nama-user"
$env:DB_PASSWORD = "password-database"
$env:DB_SSLMODE = "require"
```

Jalankan [database/db_sikawan.sql](./database/db_sikawan.sql) satu kali pada database kosong. Skrip ini tidak menghapus tabel atau data. `CREATE TABLE IF NOT EXISTS` bukan pengganti migrasi untuk mengubah skema database yang sudah berjalan.

## Membuat akun awal

Buat hash password di lingkungan lokal dengan PHP:

```sh
php -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"
```

Masukkan hash yang dihasilkan (bukan password polos) ke database, misalnya:

```sql
INSERT INTO users (username, password, nama, role)
VALUES ('admin', '<HASH_HASIL_GENERASI>', 'Admin', 'Admin');
```

Ganti placeholder dengan hash yang dihasilkan sebelum menjalankan query. Buat akun kasir dengan cara yang sama dan gunakan password unik yang kuat. Jangan gunakan akun/password contoh untuk deployment.

## Menjalankan aplikasi

Atur document root web server ke folder `Sikawan`, pastikan folder `assets/img/menu` dapat ditulisi oleh proses PHP untuk fitur unggah foto, lalu buka aplikasi melalui web server. HTTPS wajib digunakan di lingkungan deployment.

## Alur penggunaan

Sikawan membantu Admin mengelola menu dan Kasir mencatat pembayaran pesanan warung. Data pengguna, menu, pesanan, dan item pesanan disimpan di PostgreSQL; keranjang dan opsi pesanan sementara disimpan di sesi pengguna.

### 1. Login dan beranda

1. Admin atau Kasir membuka halaman login dan mengisi username serta password.
2. Aplikasi mencocokkan username, memverifikasi password, dan memastikan peran akun adalah Admin atau Kasir.
3. Jika tidak valid, aplikasi menampilkan pesan gagal dan pengguna dapat mencoba lagi. Jika valid, aplikasi membuat sesi login dan menampilkan beranda.
4. Beranda menampilkan ringkasan penjualan hari ini, transaksi terbaru, menu terlaris, serta menu yang habis.

### 2. Pengelolaan menu oleh Admin

1. Admin membuka **Kelola Menu** untuk melihat menu yang aktif.
2. Admin dapat menambah atau mengubah nama, kategori, harga, ketersediaan, dan foto menu. Aplikasi memvalidasi data sebelum menyimpannya.
3. Menu yang ditandai tidak tersedia atau dihapus tidak dapat dipilih untuk transaksi baru. Perubahan ketersediaan juga mengeluarkannya dari keranjang yang sedang aktif.
4. Kasir tidak memiliki akses ke fitur pengelolaan menu.

### 3. Transaksi oleh Kasir

1. Kasir membuka **Mode Kasir**, lalu memilih menu yang tersedia dan mengatur jumlahnya di keranjang.
2. Kasir memilih layanan **Makan di tempat** atau **Bungkus**. Untuk makan di tempat, pilih meja; Kasir juga dapat menambahkan catatan.
3. Aplikasi menghitung subtotal, pajak 10%, dan total tagihan. Kasir memilih pembayaran **Tunai** atau **QRIS**.
4. Untuk Tunai, Kasir memasukkan uang yang diterima; jumlahnya harus berupa angka bulat dan sekurangnya sama dengan total. Kembalian ditampilkan kepada Kasir.
5. Untuk QRIS, halaman pembayaran menampilkan QRIS. Kasir mengonfirmasi pembayaran telah diterima sebelum mengirim penyelesaian transaksi.
6. Saat transaksi diselesaikan, server memeriksa kembali ketersediaan dan harga menu. Jika keranjang kosong, menu tidak lagi tersedia, atau pembayaran Tunai kurang, transaksi tidak disimpan dan Kasir diminta memperbaikinya.
7. Jika valid, aplikasi menyimpan pesanan dan rincian item serta memperbarui jumlah terjual dalam satu transaksi database. Jika salah satu penyimpanan gagal, perubahan dibatalkan.
8. Setelah berhasil, keranjang dikosongkan dan aplikasi menampilkan struk. Transaksi juga muncul di beranda dan riwayat.

> **Catatan QRIS:** Aplikasi mencatat pembayaran setelah Kasir menekan konfirmasi. Pastikan pembayaran benar-benar diterima melalui kanal pembayaran sebelum mengonfirmasi.

### 4. Riwayat dan laporan

1. Admin dan Kasir dapat melihat riwayat transaksi aktif, membuka struk, dan mencetak laporan.
2. Hanya Admin yang dapat mengosongkan riwayat dari tampilan. Tindakan ini menandai transaksi sebagai dihapus; data tetap tersimpan di database.