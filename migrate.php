<?php
/**
 * Database Migration Runner
 * Dapat dijalankan via CLI: php migrate.php
 * Atau via Web / GitHub Action: migrate.php?token=SECRET_TOKEN
 * Atau via Login Session Super Admin (controller)
 */

// Ganti token ini jika ingin lebih unik, atau gunakan default
define('MIGRATION_TOKEN', 'tms_secret_migration_key_8899');

// Cek akses keamanan
$is_cli = (php_sapi_name() === 'cli');
$has_valid_token = (isset($_GET['token']) && $_GET['token'] === MIGRATION_TOKEN) || 
                   (isset($_POST['token']) && $_POST['token'] === MIGRATION_TOKEN);

session_start();
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'controller');

if (!$is_cli && !$has_valid_token && !$is_admin) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Token tidak valid atau sesi bukan super admin.'
    ]);
    exit();
}

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json');

try {
    // 1. Buat tabel schema_migrations jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `version` VARCHAR(191) NOT NULL UNIQUE,
            `applied_at` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Ambil daftar migrasi yang sudah pernah dijalankan
    $stmt = $pdo->query("SELECT `version` FROM `schema_migrations`");
    $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Cari semua file .sql di folder migrations/
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    $executed = [];
    $skipped = [];

    foreach ($files as $file) {
        $filename = basename($file);
        if (in_array($filename, $applied)) {
            $skipped[] = $filename;
            continue;
        }

        $sql = file_get_contents($file);
        if (trim($sql) === '') {
            continue;
        }

        // Jalankan SQL migrasi
        $pdo->beginTransaction();
        try {
            // Eksekusi skrip SQL
            $pdo->exec($sql);

            // Catat ke schema_migrations
            $recordStmt = $pdo->prepare("INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES (?, NOW())");
            $recordStmt->execute([$filename]);

            $pdo->commit();
            $executed[] = $filename;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \Exception("Gagal menjalankan migrasi [$filename]: " . $e->getMessage());
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => count($executed) > 0 ? 'Migrasi database berhasil dijalankan.' : 'Database sudah up to date (tidak ada migrasi baru).',
        'applied_migrations' => $executed,
        'previously_applied' => $skipped
    ], JSON_PRETTY_PRINT);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
