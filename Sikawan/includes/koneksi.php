<?php
/**
 * koneksi.php
 * Koneksi ke database PostgreSQL (Neon Cloud) memakai PDO.
 * File ini di-require oleh setiap halaman yang butuh data dari database.
 * Variabel hasilnya: $pdo
 */

// Konfigurasi diambil dari connection string NeonTech
// PERHATIKAN: menggunakan huruf "l" (L kecil), bukan angka "1" di bagian az2l7jlo
$host = "ep-quiet-grass-az2l7jlo-pooler.c-3.ap-southeast-1.aws.neon.tech";
$port = "5432"; 
$db   = "neondb";
$user = "neondb_owner";
$pass = "npg_JMqPpk7BeL2b";

try {
    // Parameter sslmode=require wajib ditambahkan agar koneksi ke cloud tidak ditolak
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        // Avoid stale server-side prepared plans when the PostgreSQL schema changes.
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");                          

} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>