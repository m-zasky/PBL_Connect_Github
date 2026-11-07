-- Sikawan PostgreSQL schema.
-- Safe to run more than once: this script does not drop tables or data.
-- CREATE TABLE IF NOT EXISTS does not migrate arbitrary existing schemas.

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

-- Keep this compatible with older installations that used VARCHAR(12).
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
