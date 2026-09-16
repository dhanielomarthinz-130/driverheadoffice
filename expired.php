<?php
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If logged in as daniel, redirect back to system_license
if (isset($_SESSION['username']) && strtolower($_SESSION['username']) === 'daniel') {
    header("Location: system_license");
    exit();
}

// Check if system is actually active
try {
    $stmtLic = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_active_until'");
    $licUntil = $stmtLic ? $stmtLic->fetchColumn() : null;
    if ($licUntil && strtotime($licUntil) >= time()) {
        header("Location: login");
        exit();
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Aktif Berakhir | TMS Head Office</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <style>
        :root {
            --bg-base: #0f172a;
            --card-bg: #1e293b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --danger: #ef4444;
            --danger-light: rgba(239, 68, 68, 0.12);
            --border: #334155;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-base);
            background-image:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(239, 68, 68, 0.12) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-main);
        }

        .expired-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            max-width: 520px;
            width: 100%;
            padding: 3rem 2.25rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .expired-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #ef4444);
        }

        .icon-circle {
            width: 84px;
            height: 84px;
            background: var(--danger-light);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--danger);
            animation: pulseGlow 2s infinite ease-in-out;
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            50% {
                box-shadow: 0 0 0 16px rgba(239, 68, 68, 0);
            }
        }

        h1 {
            font-size: 1.65rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        p.subtitle {
            font-size: 0.95rem;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .info-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: left;
            margin-bottom: 2rem;
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .info-box ul {
            padding-left: 1.25rem;
            margin-top: 0.5rem;
        }

        .info-box li {
            margin-bottom: 0.4rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .btn-main {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            padding: 0.9rem 1.5rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            border: none;
            cursor: pointer;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px -4px rgba(99, 102, 241, 0.5);
        }

        .footer-note {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="expired-card">
        <div class="icon-circle">
            <span class="material-symbols-outlined" style="font-size: 44px;">lock_clock</span>
        </div>

        <h1>Masa Aktif Aplikasi Web Telah Berakhir</h1>
        <p class="subtitle">
            Layanan sistem TMS ditangguhkan sementara karena batas waktu pemakaian/lisensi sewa web telah habis.
        </p>

        <div class="info-box">
            <div style="font-weight: 700; color: #ffffff; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 18px; color: #f59e0b;">info</span>
                Informasi Penting:
            </div>
            <ul>
                <li>Seluruh data penugasan & sistem tersimpan dengan aman.</li>
                <li>Hubungi Teknisi untuk mengaktifkan kembali layanan.</li>
            </ul>
        </div>



        <div class="footer-note">
            TMS Head Office Operations System | Dhanielo-Marthinz &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>

</html>
