<?php
// Database configuration - Mendeteksi otomatis Environment (Localhost XAMPP vs InfinityFree Hosting)
$is_localhost = isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

if ($is_localhost) {
    // Konfigurasi Database Localhost (XAMPP)
    $host = 'localhost';
    $db = 'if0_38464190_tms_ho_driver'; // Sesuaikan jika nama database lokal berbeda (misal: tms_ho_driver)
    $user = 'root';
    $pass = '';
} else {
    // Konfigurasi Database InfinityFree Live Hosting
    $host = 'sql202.infinityfree.com';
    $db = 'if0_38464190_tms_ho_driver';
    $user = 'if0_38464190';
    $pass = 'Dhaniel0';
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);

     // Set Global Timezone to WIB (Asia/Jakarta)
     date_default_timezone_set('Asia/Jakarta');
     $pdo->exec("SET time_zone = '+07:00'");
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
