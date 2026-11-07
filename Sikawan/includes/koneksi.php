<?php
/**
 * koneksi.php
 * Koneksi ke database PostgreSQL memakai PDO.
 * File ini di-require oleh setiap halaman yang butuh data dari database.
 * Variabel hasilnya: $pdo
 */
$host = "localhost";
$port = "5432";
$db   = "db_warung";
$user = "postgres";
$pass = "12345678";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       // error SQL dilempar sebagai exception
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // hasil query berupa array asosiatif
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");                          // jam transaksi sesuai WIB
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
