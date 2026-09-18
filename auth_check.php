<?php
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    @ini_set('session.use_only_cookies', '1');
    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
        @ini_set('session.cookie_secure', '1');
    }
    @session_start();
}

// Generate session-bound CSRF token if not already generated
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Global Defense-in-Depth Security Headers (Independent of Web Server)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
}


/**
 * Helper function for XSS sanitization
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require_once 'db_config.php';

// Session Timeout Implementation (1 Hour)
$timeout_duration = 3600; // 1 hour in seconds

if (isset($_SESSION['user_id'])) {
    $is_api = (basename($_SERVER['SCRIPT_NAME']) === 'api.php');

    // 1. Session Timeout Check
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        if ($elapsed_time > $timeout_duration) {
            session_unset();
            session_destroy();
            if ($is_api) {
                http_response_code(401);
                die(json_encode(['success' => false, 'error' => 'Sesi berakhir karena tidak aktif. Silakan login kembali.']));
            } else {
                header("Location: login?timeout=1");
                exit();
            }
        }
    }
    $_SESSION['last_activity'] = time();

    // 2. Real-time Account Status & Expiry Check
    try {
        if (isset($pdo)) {
            $stmtUser = $pdo->prepare("SELECT is_active, expires_at FROM users WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $userCheck = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$userCheck) {
                // User deleted
                session_unset();
                session_destroy();
                if ($is_api) {
                    http_response_code(401);
                    die(json_encode(['success' => false, 'error' => 'Akun tidak ditemukan. Silakan login kembali.']));
                } else {
                    header("Location: login");
                    exit();
                }
            }

            if (!$userCheck['is_active']) {
                // Account deactivated
                session_unset();
                session_destroy();
                if ($is_api) {
                    http_response_code(401);
                    die(json_encode(['success' => false, 'error' => 'Akun Anda dinonaktifkan. Silakan hubungi Admin.']));
                } else {
                    header("Location: login?inactive=1");
                    exit();
                }
            }

            if ($userCheck['expires_at'] && strtotime($userCheck['expires_at']) < time()) {
                // Account expired
                session_unset();
                session_destroy();
                if ($is_api) {
                    http_response_code(401);
                    die(json_encode(['success' => false, 'error' => 'Akun Anda sudah kadaluarsa. Silakan hubungi Admin.']));
                } else {
                    header("Location: login?expired=1");
                    exit();
                }
            }

            // 3. System License Active Period Check (Bypass for Superadmin daniel and authorized License Managers)
            if (!canManageSystemLicense()) {
                $stmtLic = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_active_until'");
                $licUntil = $stmtLic ? $stmtLic->fetchColumn() : null;
                if ($licUntil && strtotime($licUntil) < time()) {
                    session_unset();
                    session_destroy();
                    if ($is_api) {
                        http_response_code(401);
                        die(json_encode(['success' => false, 'error' => 'Masa aktif pemakaian aplikasi web ini telah berakhir. Silakan hubungi Management / System Administrator.']));
                    } else {
                        header("Location: expired");
                        exit();
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Silently continue if database is temporarily unavailable during session check
    }
}

/**
 * Auto-creates system_settings and system_license_logs tables if missing on server
 */
function ensureLicenseTablesExist($pdo) {
    static $checked = false;
    if ($checked || !$pdo) return;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `system_settings` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `setting_key` VARCHAR(100) NOT NULL UNIQUE,
              `setting_value` TEXT NULL,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
            VALUES ('app_active_until', DATE_ADD(NOW(), INTERVAL 3 MONTH))
            ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

            CREATE TABLE IF NOT EXISTS `system_license_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `extended_by` VARCHAR(100) NOT NULL,
              `extension_type` VARCHAR(100) NOT NULL,
              `previous_expiry` DATETIME NULL,
              `new_expiry` DATETIME NOT NULL,
              `payment_notes` TEXT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `system_license_tokens` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `token` VARCHAR(64) NOT NULL UNIQUE,
              `months` INT NOT NULL,
              `user_email` VARCHAR(255) NOT NULL,
              `amount` DECIMAL(15,2) DEFAULT 0,
              `payment_proof` VARCHAR(255) NULL,
              `ai_status` VARCHAR(50) DEFAULT 'verified',
              `ai_analysis` TEXT NULL,
              `status` ENUM('active', 'used', 'expired') DEFAULT 'active',
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `used_at` DATETIME NULL,
              `used_by_ip` VARCHAR(45) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
            ('payment_bank_name', 'BCA'),
            ('payment_account_number', '1234567890'),
            ('payment_account_holder', 'Dhanielo Marthinz'),
            ('payment_price_per_month', '150000'),
            ('payment_admin_email', 'dhanielo.marthinz@gmail.com')
            ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
        ");
        $checked = true;
    } catch (Exception $e) {}
}

/**
 * Helper function to check if a user is authorized to manage system license
 */
function canManageSystemLicenseByUsername($username) {
    if (!$username) return false;
    $uname = strtolower(trim($username));
    if ($uname === 'daniel') return true;

    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_license_managers'");
            $val = $stmt ? $stmt->fetchColumn() : null;
            if ($val) {
                $managers = json_decode($val, true);
                if (is_array($managers)) {
                    $managersLower = array_map('strtolower', array_map('trim', $managers));
                    if (in_array($uname, $managersLower, true)) return true;
                }
            }
        } catch (Exception $e) {
            ensureLicenseTablesExist($pdo);
        }
    }
    return false;
}

function canManageSystemLicense() {
    return isset($_SESSION['username']) ? canManageSystemLicenseByUsername($_SESSION['username']) : false;
}

// Function to check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        if (basename($_SERVER['SCRIPT_NAME']) === 'api.php') {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi. Silakan login.']));
        } else {
            header("Location: login");
            exit();
        }
    }
}

// Function to check role access (Legacy/Direct)
function checkRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: mobile_home");
        exit();
    }
}

/**
 * Check if the current user has access to a specific menu key.
 * Redirects to dashboard_summary or mobile_home if access is denied.
 */
function checkAccess($menu_key) {
    if (!canAccessMenu($menu_key)) {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'driver' || $role === 'request_pickup') {
            header("Location: mobile_home");
        } else {
            header("Location: dashboard_summary");
        }
        exit();
    }
}

/**
 * Check if the current user role can access a specific menu/module
 * @param string $menu_key
 * @return bool
 */
function canAccessMenu($menu_key) {
    global $pdo;
    if (!isset($_SESSION['role'])) return false;
    
    $role = $_SESSION['role'];
    
    // Super Admin (controller) always has full access to everything
    if ($role === 'controller') return true;
    
    // Cache permissions for the current request
    static $permissions = [];
    if (!isset($permissions[$role])) {
        try {
            $stmt = $pdo->prepare("SELECT menu_key, can_access, can_write FROM role_permissions WHERE role_key = ?");
            $stmt->execute([$role]);
            $permissions[$role] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[$role][$row['menu_key']] = [
                    'can_access' => $row['can_access'] == 1,
                    'can_write' => $row['can_write'] == 1
                ];
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // If permission is explicitly set in role_permissions table, respect it
    if (isset($permissions[$role][$menu_key])) {
        return $permissions[$role][$menu_key]['can_access'];
    }
    
    // Admin role fallback: default to true for web menus if not explicitly configured in DB
    if ($role === 'admin') return true;
    
    return false;
}

/**
 * Check if the current user role has write access to a specific menu/module
 * @param string $menu_key
 * @return bool
 */
function canWriteMenu($menu_key) {
    global $pdo;
    if (!isset($_SESSION['role'])) return false;
    
    $role = $_SESSION['role'];
    
    // Super Admin (controller) always has full write access
    if ($role === 'controller') return true;
    
    // Cache permissions for the current request
    static $permissions = [];
    if (!isset($permissions[$role])) {
        try {
            $stmt = $pdo->prepare("SELECT menu_key, can_access, can_write FROM role_permissions WHERE role_key = ?");
            $stmt->execute([$role]);
            $permissions[$role] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[$role][$row['menu_key']] = [
                    'can_access' => $row['can_access'] == 1,
                    'can_write' => $row['can_write'] == 1
                ];
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // If permission is explicitly set in role_permissions table, respect it
    if (isset($permissions[$role][$menu_key])) {
        return $permissions[$role][$menu_key]['can_write'];
    }
    
    // Admin role fallback: default to true for write access if not explicitly configured in DB
    if ($role === 'admin') return true;
    
    return false;
}

function checkRoleApi($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
    }
}

/**
 * Check if the current user has access to a specific menu key (API version).
 * Returns JSON error if access is denied.
 */
function checkAccessApi($menu_key) {
    if (!canAccessMenu($menu_key)) {
        die(json_encode(['success' => false, 'error' => 'Akses ditolak (Izin menu diperlukan)']));
    }
}

/**
 * Check if the current user has write access to a specific menu key (API version).
 * Returns JSON error if write access is denied.
 */
function checkWriteAccessApi($menu_key) {
    if (!canWriteMenu($menu_key)) {
        die(json_encode(['success' => false, 'error' => 'Akses ditolak (Izin tulis diperlukan)']));
    }
}

/**
 * Validate dan sanitize ekstensi file upload. Return ekstensi lowercase yang aman,
 * atau null kalau ekstensi tidak ada di whitelist.
 *
 * Whitelist: gambar (jpg/jpeg/png/webp/gif/heic/heif) dan dokumen (pdf).
 * Berlaku untuk semua upload SJ/Goods/Proof di api.php.
 */
function safeUploadExtension($originalName) {
    static $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'pdf'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!$ext || !in_array($ext, $allowed, true)) {
        return null;
    }
    return $ext;
}

/**
 * Log user activity into system_logs table
 */
function logActivity($action, $description) {
    global $pdo;
    if (!isset($pdo)) return;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip_address]);
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Universal Database & Sliding-Window Rate Limiter
 * Mencegah serangan nembak API, spamming, bot brute force, dan scraping data massal
 */
function enforceApiRateLimit($pdo, $action) {
    if (!$pdo || empty($action)) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $windowSize = 60; // Window 60 detik (1 menit)

    // Tentukan kategori aksi dan batasan maksimal request per menit
    if (in_array($action, ['login', 'submit_payment', 'redeem_license_token'], true)) {
        $group = 'auth_payment';
        $maxRequests = 15; // Maksimal 15 request/menit untuk aksi sensitif
    } elseif ($action === 'update_location') {
        $group = 'location';
        $maxRequests = 120; // Maksimal 120 request/menit untuk tracking GPS
    } elseif (strpos($action, 'get_') === 0) {
        $group = 'read_data';
        $maxRequests = 120; // Maksimal 120 request/menit untuk anti-scraping
    } else {
        $group = 'write_data';
        $maxRequests = 60; // Maksimal 60 request/menit untuk aksi modifikasi
    }

    $currentTime = time();
    $windowStart = $currentTime - ($currentTime % $windowSize);

    try {
        static $rateLimitTableChecked = false;
        if (!$rateLimitTableChecked) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `api_rate_limits` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `ip_address` VARCHAR(45) NOT NULL,
                    `action_group` VARCHAR(50) NOT NULL,
                    `hits` INT NOT NULL DEFAULT 1,
                    `window_start` INT NOT NULL,
                    UNIQUE KEY `idx_ip_grp_win` (`ip_address`, `action_group`, `window_start`),
                    INDEX `idx_win` (`window_start`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Pembersihan berkala data lama (>15 menit yang lalu)
            if (random_int(1, 25) === 1) {
                $oldTime = $currentTime - 900;
                $pdo->exec("DELETE FROM api_rate_limits WHERE window_start < {$oldTime}");
            }
            $rateLimitTableChecked = true;
        }

        // Catat hit ke database
        $stmt = $pdo->prepare("
            INSERT INTO api_rate_limits (ip_address, action_group, hits, window_start)
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE hits = hits + 1
        ");
        $stmt->execute([$ip, $group, $windowStart]);

        // Hitung total hits
        $stmtCount = $pdo->prepare("
            SELECT hits FROM api_rate_limits
            WHERE ip_address = ? AND action_group = ? AND window_start = ?
        ");
        $stmtCount->execute([$ip, $group, $windowStart]);
        $currentHits = (int)$stmtCount->fetchColumn();

        $remaining = max(0, $maxRequests - $currentHits);
        $resetTime = $windowStart + $windowSize;
        $retryAfter = max(1, $resetTime - $currentTime);

        // Kirim header rate limit standard
        if (!headers_sent()) {
            header("X-RateLimit-Limit: {$maxRequests}");
            header("X-RateLimit-Remaining: {$remaining}");
            header("X-RateLimit-Reset: {$resetTime}");
        }

        // Blokir jika melebihi kuota
        if ($currentHits > $maxRequests) {
            if (!headers_sent()) {
                http_response_code(429); // 429 Too Many Requests
                header("Retry-After: {$retryAfter}");
            }

            if ($currentHits === ($maxRequests + 1)) {
                logActivity('RATE_LIMIT_BLOCKED', "IP {$ip} diblokir sementara karena melebihi batas request ({$maxRequests}/menit) pada aksi: {$action}");
            }

            die(json_encode([
                'success' => false,
                'rate_limited' => true,
                'error' => 'Terlalu banyak permintaan (Rate limit exceeded). Sistem membatasi demi keamanan data. Silakan tunggu ' . $retryAfter . ' detik sebelum mencoba kembali.',
                'retry_after' => $retryAfter
            ]));
        }
    } catch (PDOException $e) {
        // Fail-safe jika tabel lock / db busy
    }
}

