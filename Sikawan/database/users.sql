-- database/users.sql
-- Tabel pengguna untuk login (2 role: admin & kasir) + akun contoh.
-- Jalankan di PostgreSQL (database db_warung). Password di-hash dengan bcrypt
-- (pgcrypto), dan bisa diverifikasi oleh password_verify() milik PHP.

-- Tabel pengguna (login Admin / Kasir)
CREATE TABLE users (
  id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username  VARCHAR(30)  NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,                 -- disimpan dalam bentuk hash, bukan teks asli
  nama      VARCHAR(50)  NOT NULL,
  role      VARCHAR(10)  NOT NULL DEFAULT 'Kasir' CHECK (role IN ('Admin','Kasir'))
);

