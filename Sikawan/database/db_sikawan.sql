-- Skema basis data Sikawan untuk PostgreSQL.
-- Aman dijalankan berulang kali: skrip ini tidak menghapus tabel atau data.
-- CREATE TABLE IF NOT EXISTS tidak otomatis memperbarui semua skema lama.

BEGIN;

CREATE TABLE IF NOT EXISTS users (
    id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username  VARCHAR(30)  NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL,
    nama      VARCHAR(50)  NOT NULL,
    role      VARCHAR(10)  NOT NULL DEFAULT 'Kasir'
              CHECK (role IN ('Admin', 'Kasir'))
);

CREATE TABLE IF NOT EXISTS menu (
    id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nama         VARCHAR(40) NOT NULL CHECK (BTRIM(nama) <> ''),
    kategori     VARCHAR(10) NOT NULL
                 CHECK (kategori IN ('Makanan', 'Minuman', 'Snack', 'Paket')),
    harga        INTEGER NOT NULL CHECK (harga >= 500 AND harga <= 1000000 AND harga % 500 = 0),
    foto         VARCHAR(100),
    terjual      INTEGER NOT NULL DEFAULT 0 CHECK (terjual >= 0),
    tersedia     BOOLEAN NOT NULL DEFAULT TRUE,
    dihapus_pada TIMESTAMP,
    dihapus_oleh INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_menu_nama_aktif
    ON menu (LOWER(nama))
    WHERE dihapus_pada IS NULL;

CREATE TABLE IF NOT EXISTS pesanan (
    id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    kode          VARCHAR(20) NOT NULL UNIQUE,
    tanggal       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipe          VARCHAR(10) NOT NULL CHECK (tipe IN ('Dine-in', 'Bungkus')),
    meja          VARCHAR(10),
    metode        VARCHAR(10) NOT NULL CHECK (metode IN ('Tunai', 'QRIS')),
    subtotal      INTEGER NOT NULL CHECK (subtotal >= 0),
    pajak         INTEGER NOT NULL CHECK (pajak >= 0),
    total         INTEGER NOT NULL CHECK (total = subtotal + pajak),
    uang_diterima INTEGER NOT NULL CHECK (uang_diterima >= total),
    catatan       VARCHAR(255),
    kasir_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    kasir_nama    VARCHAR(50) NOT NULL,
    dihapus_pada  TIMESTAMP,
    dihapus_oleh  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    CHECK (
        (tipe = 'Bungkus' AND meja IS NULL)
        OR (tipe = 'Dine-in' AND meja IS NOT NULL)
    )
);

-- Menyesuaikan instalasi lama yang sebelumnya memakai VARCHAR(12).
ALTER TABLE pesanan
    ALTER COLUMN kode TYPE VARCHAR(20);

CREATE INDEX IF NOT EXISTS ix_pesanan_aktif_tanggal
    ON pesanan (tanggal DESC)
    WHERE dihapus_pada IS NULL;

CREATE TABLE IF NOT EXISTS pesanan_item (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pesanan_id INTEGER NOT NULL REFERENCES pesanan(id) ON DELETE CASCADE,
    menu_id    INTEGER REFERENCES menu(id) ON DELETE SET NULL,
    nama       VARCHAR(40) NOT NULL,
    harga      INTEGER NOT NULL CHECK (harga >= 0),
    qty        INTEGER NOT NULL CHECK (qty > 0)
);

CREATE INDEX IF NOT EXISTS ix_pesanan_item_pesanan_id
    ON pesanan_item (pesanan_id);

COMMIT;

-- ============================================================
-- CONTOH LAPORAN RIWAYAT PESANAN BULAN BERJALAN
-- ============================================================

-- Contoh menampilkan daftar menu yang belum dihapus.
SELECT
    id        AS "ID Menu",
    nama      AS "Nama Menu",
    kategori  AS "Kategori",
    harga     AS "Harga",
    tersedia  AS "Tersedia",
    terjual   AS "Jumlah Terjual"
FROM menu
WHERE dihapus_pada IS NULL
ORDER BY id;

-- 1. Daftar pesanan aktif bulan ini (satu baris per pesanan).
WITH periode AS (
    SELECT
        date_trunc('month', CURRENT_DATE::timestamp) AS mulai,
        date_trunc('month', CURRENT_DATE::timestamp) + INTERVAL '1 month' AS sampai
),
pesanan_bulan_ini AS (
    SELECT p.*
    FROM pesanan AS p
    CROSS JOIN periode AS pr
    WHERE p.dihapus_pada IS NULL
      AND p.tanggal >= pr.mulai
      AND p.tanggal < pr.sampai
)
SELECT
    p.kode          AS "Kode Pesanan",
    p.tanggal       AS "Tanggal dan Waktu",
    p.kasir_nama    AS "Nama Kasir",
    p.tipe          AS "Tipe Pesanan",
    p.meja          AS "Nomor Meja",
    p.metode        AS "Metode Pembayaran",
    p.subtotal      AS "Subtotal",
    p.pajak         AS "Pajak",
    p.total         AS "Total",
    p.uang_diterima AS "Uang Diterima",
    p.catatan       AS "Catatan"
FROM pesanan_bulan_ini AS p
ORDER BY p.tanggal, p.id;

-- 2. Ringkasan jumlah dan nilai pesanan aktif bulan ini.
WITH periode AS (
    SELECT
        date_trunc('month', CURRENT_DATE::timestamp) AS mulai,
        date_trunc('month', CURRENT_DATE::timestamp) + INTERVAL '1 month' AS sampai
),
pesanan_bulan_ini AS (
    SELECT p.*
    FROM pesanan AS p
    CROSS JOIN periode AS pr
    WHERE p.dihapus_pada IS NULL
      AND p.tanggal >= pr.mulai
      AND p.tanggal < pr.sampai
)
SELECT
    COUNT(*)                   AS "Jumlah Transaksi",
    COALESCE(SUM(subtotal), 0) AS "Total Subtotal",
    COALESCE(SUM(pajak), 0)    AS "Total Pajak",
    COALESCE(SUM(total), 0)    AS "Total Omzet"
FROM pesanan_bulan_ini;

-- 3. Rincian item pesanan bulan ini (satu baris per item).
-- Total pesanan dapat berulang untuk beberapa item dalam pesanan yang sama.
WITH periode AS (
    SELECT
        date_trunc('month', CURRENT_DATE::timestamp) AS mulai,
        date_trunc('month', CURRENT_DATE::timestamp) + INTERVAL '1 month' AS sampai
),
pesanan_bulan_ini AS (
    SELECT p.*
    FROM pesanan AS p
    CROSS JOIN periode AS pr
    WHERE p.dihapus_pada IS NULL
      AND p.tanggal >= pr.mulai
      AND p.tanggal < pr.sampai
)
SELECT
    p.kode            AS "Kode Pesanan",
    p.tanggal         AS "Tanggal dan Waktu",
    p.kasir_nama      AS "Nama Kasir",
    pi.nama           AS "Nama Menu",
    pi.harga          AS "Harga Satuan",
    pi.qty            AS "Jumlah",
    pi.harga * pi.qty AS "Total Item"
FROM pesanan_bulan_ini AS p
JOIN pesanan_item AS pi ON pi.pesanan_id = p.id
ORDER BY p.tanggal, p.id, pi.id;
