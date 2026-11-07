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