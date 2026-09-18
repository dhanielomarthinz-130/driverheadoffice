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

$initialToken = trim($_GET['token'] ?? '');
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
            --accent: #38bdf8;
            --success: #10b981;
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
            padding: 2rem 1.25rem;
            color: var(--text-main);
        }

        .expired-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            max-width: 560px;
            width: 100%;
            padding: 3rem 2.5rem;
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
            background: linear-gradient(90deg, #ef4444, #f59e0b, #6366f1);
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
            font-size: 0.92rem;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        /* Token Input Box */
        .token-activation-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1.5px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.75rem;
            text-align: left;
        }

        .token-header {
            font-size: 0.9rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .token-sub {
            font-size: 0.78rem;
            color: var(--text-sub);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .token-input-group {
            display: flex;
            gap: 8px;
        }

        .token-input {
            flex: 1;
            background: #0b1120;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-family: monospace, 'Courier New';
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            outline: none;
            transition: all 0.2s;
        }

        .token-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .btn-activate {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            border: none;
            padding: 0 1.25rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.2s;
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
        }

        .btn-activate:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-activate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Payment Button (Primary Call-to-Action) */
        .btn-payment-cta {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            font-weight: 800;
            font-size: 0.98rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
            margin-bottom: 1.25rem;
        }

        .btn-payment-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(16, 185, 129, 0.5);
            background: linear-gradient(135deg, #059669, #047857);
        }

        .alert-box {
            display: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.82rem;
            margin-top: 10px;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .info-box {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            text-align: left;
            margin-bottom: 1.5rem;
            font-size: 0.82rem;
            color: #cbd5e1;
        }

        .info-box ul {
            padding-left: 1.25rem;
            margin-top: 0.4rem;
        }

        .info-box li {
            margin-bottom: 0.35rem;
        }

        .footer-note {
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
            Layanan sistem TMS ditangguhkan sementara karena batas waktu pemakaian/lisensi telah habis. Silakan perpanjang untuk membuka kembali sistem.
        </p>

        <!-- TOMBOL PEMBAYARAN MANDIRI -->
        <a href="payment" class="btn-payment-cta">
            <span class="material-symbols-outlined" style="font-size: 22px;">payments</span>
            <span>Perpanjang Masa Aktif / Bayar Sekarang</span>
        </a>

        <!-- INPUT TOKEN AKTIVASI DARI EMAIL -->
        <div class="token-activation-card">
            <div class="token-header">
                <span class="material-symbols-outlined" style="color: var(--accent); font-size: 20px;">vpn_key</span>
                <span>Sudah Melakukan Pembayaran? Masukkan Token:</span>
            </div>
            <p class="token-sub">
                Salin kode Token Lisensi yang telah dikirimkan ke email Anda setelah verifikasi bukti transfer:
            </p>

            <form id="tokenForm">
                <div class="token-input-group">
                    <input type="text" id="tokenInput" class="token-input" placeholder="TMS-XXXX-XXXX-XXXX" value="<?php echo htmlspecialchars($initialToken); ?>" required autocomplete="off">
                    <button type="submit" id="btnActivateToken" class="btn-activate">
                        <span class="material-symbols-outlined" style="font-size: 18px;">key</span>
                        <span>Aktivasi</span>
                    </button>
                </div>
            </form>

            <div id="alertBox" class="alert-box">
                <span class="material-symbols-outlined" id="alertIcon" style="font-size: 18px;">info</span>
                <span id="alertMsg"></span>
            </div>
        </div>

        <div class="info-box">
            <div style="font-weight: 700; color: #ffffff; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #f59e0b;">info</span>
                Informasi & Keamanan Data:
            </div>
            <ul>
                <li>Seluruh data penugasan, master lokasi, armada, dan user tetap aman tersimpan.</li>
                <li>Setelah token diaktivasi, sistem otomatis terbuka tanpa perlu instalasi ulang.</li>
                <li>Butuh bantuan teknis? Hubungi administrator di <strong>dhanielo.marthinz@gmail.com</strong></li>
            </ul>
        </div>

        <div class="footer-note">
            TMS Head Office Operations System | Dhanielo-Marthinz &copy; <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        const tokenInput = document.getElementById('tokenInput');
        const tokenForm = document.getElementById('tokenForm');
        const btnActivate = document.getElementById('btnActivateToken');
        const alertBox = document.getElementById('alertBox');
        const alertMsg = document.getElementById('alertMsg');
        const alertIcon = document.getElementById('alertIcon');

        function showAlert(msg, type = 'error') {
            alertBox.className = `alert-box alert-${type}`;
            alertBox.style.display = 'flex';
            alertMsg.textContent = msg;
            alertIcon.textContent = (type === 'success') ? 'check_circle' : 'error';
        }

        // Auto uppercase on input
        tokenInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
        });

        // Submit token redemption
        tokenForm.onsubmit = async (e) => {
            e.preventDefault();
            const token = tokenInput.value.trim();
            if (!token) return;

            btnActivate.disabled = true;
            const origContent = btnActivate.innerHTML;
            btnActivate.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite; font-size:18px;">progress_activity</span><span>Memproses...</span>';
            alertBox.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('token', token);

                const res = await fetch('./api.php?action=redeem_license_token', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    showAlert(data.message || 'Token valid! Sistem berhasil diaktifkan.', 'success');
                    btnActivate.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">done</span><span>Aktif!</span>';
                    btnActivate.style.background = '#10b981';

                    // Redirect to login after 1.5s
                    setTimeout(() => {
                        window.location.href = 'login';
                    }, 1600);
                } else {
                    showAlert(data.error || 'Token tidak valid atau sudah digunakan.', 'error');
                    btnActivate.disabled = false;
                    btnActivate.innerHTML = origContent;
                }
            } catch (err) {
                console.error(err);
                showAlert('Gagal terhubung ke server. Periksa jaringan Anda.', 'error');
                btnActivate.disabled = false;
                btnActivate.innerHTML = origContent;
            }
        };

        // Auto trigger if token is present in URL
        window.addEventListener('DOMContentLoaded', () => {
            if (tokenInput.value.trim().length >= 10) {
                tokenForm.dispatchEvent(new Event('submit'));
            }
        });

        // Add spinner animation style
        const style = document.createElement('style');
        style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);
    </script>
</body>

</html>
