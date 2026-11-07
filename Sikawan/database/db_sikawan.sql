-- database/users.sql
-- Tabel pengguna untuk login (2 role: Admin & Kasir) + akun contoh.
-- Jalankan di PostgreSQL (database db_warung).

-- pgcrypto dipakai untuk membuat hash bcrypt, yang bisa dibaca password_verify() milik PHP
CREATE EXTENSION IF NOT EXISTS pgcrypto;

DROP TABLE IF EXISTS pesanan_item CASCADE;
DROP TABLE IF EXISTS pesanan CASCADE;
DROP TABLE IF EXISTS menu CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Tabel pengguna (login Admin / Kasir)
CREATE TABLE IF NOT EXISTS users (
  id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username  VARCHAR(30)  NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,                
  nama      VARCHAR(50)  NOT NULL,
  role      VARCHAR(10)  NOT NULL DEFAULT 'Kasir' CHECK (role IN ('Admin','Kasir'))
);

-- Dummy Account
INSERT INTO users (username, password, nama, role) VALUES
  ('admin', crypt('admin123', gen_salt('bf')), 'Admin',  'Admin'),
  ('kasir', crypt('kasir123', gen_salt('bf')), 'Kasir 1', 'Kasir')
ON CONFLICT (username) DO NOTHING;

CREATE TABLE menu (
  id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nama          VARCHAR(40) NOT NULL,
  kategori      VARCHAR(10) NOT NULL CHECK (kategori IN ('Makanan','Minuman','Snack','Paket')),
  harga         INTEGER NOT NULL CHECK (harga >= 0),
  foto          VARCHAR(100),                      -- nama file di assets/img/menu/ (boleh kosong)
  emoji         VARCHAR(8) NOT NULL DEFAULT '🍛',  -- dipakai kalau menu tidak punya foto
  terjual       INTEGER NOT NULL DEFAULT 0 CHECK (terjual >= 0),
  tersedia      BOOLEAN NOT NULL DEFAULT TRUE,     -- TRUE = tersedia, FALSE = habis
  dihapus_pada  TIMESTAMP,                         -- soft delete: NULL = aktif, terisi = disembunyikan
  dihapus_oleh  INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX ux_menu_nama_aktif ON menu (LOWER(nama)) WHERE dihapus_pada IS NULL;

CREATE TABLE pesanan (
  id             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  kode           VARCHAR(12) NOT NULL UNIQUE,      -- contoh: ORD-0001
  tanggal        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipe           VARCHAR(10) NOT NULL CHECK (tipe IN ('Dine-in','Bungkus')),
  meja           VARCHAR(10),
  metode         VARCHAR(10) NOT NULL CHECK (metode IN ('Tunai','QRIS')),
  subtotal       INTEGER NOT NULL CHECK (subtotal >= 0),
  pajak          INTEGER NOT NULL CHECK (pajak >= 0),
  total          INTEGER NOT NULL CHECK (total >= 0),
  uang_diterima  INTEGER NOT NULL CHECK (uang_diterima >= 0),
  catatan        VARCHAR(255),
  kasir_id       INTEGER REFERENCES users(id) ON DELETE SET NULL,
  kasir_nama     VARCHAR(50) NOT NULL,
  dihapus_pada   TIMESTAMP,                        -- soft delete: NULL = tampil, terisi = disembunyikan
  dihapus_oleh   INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE pesanan_item (
  id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pesanan_id  INTEGER NOT NULL REFERENCES pesanan(id) ON DELETE CASCADE,
  menu_id     INTEGER REFERENCES menu(id) ON DELETE SET NULL,
  nama        VARCHAR(40) NOT NULL,
  harga       INTEGER NOT NULL CHECK (harga >= 0),
  qty         INTEGER NOT NULL CHECK (qty > 0)
);

SELECT id, nama, dihapus_pada FROM menu    WHERE dihapus_pada IS NOT NULL;
SELECT id, kode, total, dihapus_pada FROM pesanan WHERE dihapus_pada IS NOT NULL;

UPDATE menu SET dihapus_pada = NULL, dihapus_oleh = NULL WHERE id = ;

UPDATE pesanan SET dihapus_pada = NULL, dihapus_oleh = NULL WHERE dihapus_pada IS NOT NULL;
