-- database/users.sql
-- Tabel pengguna untuk login (2 role: Admin & Kasir) + akun contoh.
-- Jalankan di PostgreSQL (database db_warung).

-- pgcrypto dipakai untuk membuat hash bcrypt, yang bisa dibaca password_verify() milik PHP
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Tabel pengguna (login Admin / Kasir)
CREATE TABLE IF NOT EXISTS users (
  id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username  VARCHAR(30)  NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,                 -- disimpan dalam bentuk hash, bukan teks asli
  nama      VARCHAR(50)  NOT NULL,
  role      VARCHAR(10)  NOT NULL DEFAULT 'Kasir' CHECK (role IN ('Admin','Kasir'))
);

-- Akun contoh (GANTI password-nya sebelum dipakai sungguhan)
INSERT INTO users (username, password, nama, role) VALUES
  ('admin', crypt('admin123', gen_salt('bf')), 'Admin',  'Admin'),
  ('kasir', crypt('kasir123', gen_salt('bf')), 'Kasir 1', 'Kasir')
ON CONFLICT (username) DO NOTHING;
