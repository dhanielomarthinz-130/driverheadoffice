<?php
require_once 'auth_check.php';
checkLogin();

// Security check using helper function
if (function_exists('canManageSystemLicense') && !canManageSystemLicense()) {
    header("Location: dashboard_summary");
    exit();
}

$current_page = 'system_license.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisensi System & Masa Aktif Web | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Fonts & Icons & Main CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-heading: #0f172a;
            --text-sub: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: #334155;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--text-sub);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.75rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            position: relative;
            overflow: hidden;
        }

        .card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-expired {
            background: #fee2e2;
            color: #b91c1c;
        }

        .expiry-date-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 0.5rem;
        }

        .remaining-days {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Quick Add Buttons */
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn-extend {
            background: #f1f5f9;
            color: #1e293b;
            border: 1.5px solid var(--border);
            padding: 0.85rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-extend:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            outline: none;
            transition: border 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .btn-submit-main {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #4338ca);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            transition: all 0.2s ease;
        }

        .btn-submit-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px -4px rgba(79, 70, 229, 0.5);
        }

        /* History Table Styling */
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .logs-table th, .logs-table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .logs-table th {
            background: #f8fafc;
            color: var(--text-sub);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .logs-table tr:hover {
            background: #f1f5f9;
        }

        .btn-print {
            padding: 4px 10px;
            background: #e0e7ff;
            color: #3730a3;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #c7d2fe;
        }
    </style>
</head>

<body>
    <div id="toastContainer"></div>

    <div class="main-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <div class="page-title-icon">
                        <span class="material-symbols-outlined" style="font-size: 24px; color: var(--primary);">verified</span>
                    </div>
                    <div>
                        <h1>Lisensi System & Masa Aktif Pemakaian Web</h1>
                        <p>Pengaturan Pengelola Lisensi - Mengelola durasi aktif aplikasi & perpanjangan masa sewa/pemakaian.</p>
                    </div>
                </div>
            </div>

            <div class="grid-cards">
                <!-- CARD STATUS LISENSI -->
                <div class="card">
                    <div class="card-title">
                        <span>Status Pemakaian Web</span>
                        <div id="statusBadge" class="status-badge status-active">
                            <span class="material-symbols-outlined" style="font-size: 16px;">sync</span>
                            <span>Memuat...</span>
                        </div>
                    </div>
                    <div class="expiry-date-text" id="displayActiveUntil">-</div>
                    <div class="remaining-days" id="displayRemainingDays">Memuat durasi...</div>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <div style="font-size: 0.78rem; color: var(--text-sub); font-weight: 600; margin-bottom: 4px;">CATATAN PEMBAYARAN TERAKHIR:</div>
                        <div id="displayLicenseNotes" style="font-size: 0.88rem; font-weight: 600; color: var(--text-heading); font-style: italic;">
                            -
                        </div>
                    </div>

                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed var(--border);">
                        <button type="button" id="btnRevokeLicense" onclick="confirmRevokeLicense()" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 12px; padding: 10px 16px; font-weight: 700; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.82rem;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">block</span>
                            <span>Batalkan / Kunci Pemakaian Sistem Sekarang (Set Expired)</span>
                        </button>
                    </div>
                </div>

                <!-- CARD PERPANJANG INSTAN -->
                <div class="card">
                    <div class="card-title">
                        <span>Perpanjang Instan (Setelah Pembayaran)</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">add_task</span>
                    </div>

                    <p style="font-size: 0.82rem; color: var(--text-sub); margin-bottom: 1rem;">
                        Klik salah satu opsi di bawah ini untuk langsung menambahkan durasi bulan pemakaian:
                    </p>

                    <div class="btn-grid">
                        <button type="button" class="btn-extend" onclick="quickAddMonths(1)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +1 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(3)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +3 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(6)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +6 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(12)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +1 Tahun
                        </button>
                    </div>
                </div>
            </div>

            <!-- CARD CUSTOM DATE & OTORISASI -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-title">
                        <span>Pengaturan Tanggal Lisensi Kustom</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">edit_calendar</span>
                    </div>

                    <form id="licenseForm">
                        <div class="form-group">
                            <label for="customDate">Atur Tanggal Berakhir Pemakaian Kustom</label>
                            <input type="date" id="customDate" name="custom_date" class="form-control">
                            <small style="color: var(--text-sub); font-size: 0.75rem; margin-top: 4px; display: block;">
                                Isi jika ingin menentukan tanggal pasti aktifnya web aplikasi.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="notesInput">Catatan Pembayaran / Referensi Transfer</label>
                            <input type="text" id="notesInput" name="notes" class="form-control" placeholder="Contoh: Lunas 3 Bulan via TF BCA (Inv #1024)">
                        </div>

                        <button type="submit" id="btnSubmitForm" class="btn-submit-main">
                            <span class="material-symbols-outlined">save</span>
                            Simpan Perubahan Lisensi
                        </button>
                    </form>
                </div>

                <!-- CARD OTORISASI USER PENGELOLA LISENSI -->
                <div class="card">
                    <div class="card-title">
                        <span>Otorisasi Pengelola Lisensi</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">admin_panel_settings</span>
                    </div>

                    <p style="font-size: 0.82rem; color: var(--text-sub); margin-bottom: 1rem;">
                        Pilih user tambahan (Admin / Controller) yang diberi hak untuk mengelola lisensi & mengakses menu ini:
                    </p>

                    <form id="managersForm">
                        <div id="managersList" style="max-height: 220px; overflow-y: auto; border: 1.5px solid var(--border); border-radius: 12px; padding: 10px; margin-bottom: 1.25rem;">
                            <div style="font-size:0.82rem; color:var(--text-sub); text-align:center; padding:10px;">Memuat daftar pengguna...</div>
                        </div>

                        <button type="submit" id="btnSubmitManagers" class="btn-submit-main" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <span class="material-symbols-outlined">how_to_reg</span>
                            Simpan Hak Akses Pengelola
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD RIWAYAT PERPANJANGAN & BUKTI -->
            <div class="card">
                <div class="card-title">
                    <span>Riwayat Perpanjangan & Bukti Pembayaran</span>
                    <span class="material-symbols-outlined" style="color: var(--primary);">history</span>
                </div>



                <div style="overflow-x: auto;">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>No / Waktu Transaksi</th>
                                <th>Pengelola (Oleh)</th>
                                <th>Jenis Perpanjangan</th>
                                <th>Masa Aktif Baru</th>
                                <th>Catatan Pembayaran</th>
                                <th>Aksi (Bukti PDF)</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--text-sub); padding:16px;">Memuat riwayat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CARD PENGATURAN PEMBAYARAN, AI & EMAIL -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-title">
                        <span>Pengaturan Rekening Pembayaran & Harga</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">payments</span>
                    </div>

                    <form id="paymentConfigForm">
                        <div class="form-group">
                            <label for="cfgBankName">Nama Bank Tujuan</label>
                            <input type="text" id="cfgBankName" name="payment_bank_name" class="form-control" placeholder="Contoh: BCA, Mandiri, BRI" required>
                        </div>
                        <div class="form-group">
                            <label for="cfgAccountNum">Nomor Rekening</label>
                            <input type="text" id="cfgAccountNum" name="payment_account_number" class="form-control" placeholder="Nomor rekening transfer" required>
                        </div>
                        <div class="form-group">
                            <label for="cfgAccountHolder">Atas Nama Rekening</label>
                            <input type="text" id="cfgAccountHolder" name="payment_account_holder" class="form-control" placeholder="Nama pemilik rekening" required>
                        </div>
                        <div class="form-group">
                            <label for="cfgPrice">Tarif Lisensi / Bulan (Rp)</label>
                            <input type="number" id="cfgPrice" name="payment_price_per_month" class="form-control" placeholder="150000" min="10000" step="1000" required>
                        </div>
                        <div class="form-group">
                            <label for="cfgAdminEmail">Email Notifikasi Admin (Penerima Bukti)</label>
                            <input type="email" id="cfgAdminEmail" name="payment_admin_email" class="form-control" placeholder="dhanielo.marthinz@gmail.com" required>
                        </div>

                        <button type="submit" id="btnSavePaymentConfig" class="btn-submit-main" style="width: 100%; height: 44px; padding: 0 12px; font-size: 0.88rem; font-weight: 700; border-radius: 10px; margin-top: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                            <span>Simpan Rekening & Harga</span>
                        </button>
                    </form>
                </div>

                <!-- CARD PENGATURAN AI & SMTP -->
                <div class="card">
                    <div class="card-title">
                        <span>Integrasi AI Verifikator & SMTP Mailer</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">smart_toy</span>
                    </div>

                    <form id="aiSmtpForm">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="cfgGeminiKey" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span>Google Gemini API Key (Verifikasi Struk AI)</span>
                                <span style="font-size: 0.68rem; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">Opsional</span>
                            </label>
                            <input type="password" id="cfgGeminiKey" name="gemini_api_key" class="form-control" placeholder="AIzaSy..." style="height: 42px;">
                            <small style="color: var(--text-sub); font-size: 0.74rem; margin-top: 4px; display: block;">
                                Digunakan untuk memeriksa keaslian foto struk m-banking / ATM secara otomatis. Kosongkan jika ingin memakai heuristik bawaan.
                            </small>
                        </div>

                        <div style="border-top: 1px dashed var(--border); margin: 1.25rem 0 1rem 0;"></div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 700; font-size: 0.84rem; color: var(--text-heading);">Konfigurasi SMTP Pengiriman Email:</span>
                            <span style="font-size: 0.68rem; font-weight: 700; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 6px;">Direkomendasikan</span>
                        </div>
                        
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.77rem; line-height: 1.55;">
                            <div style="font-weight: 700; color: #166534; display: flex; align-items: center; gap: 6px; margin-bottom: 3px; font-size: 0.8rem;">
                                <span class="material-symbols-outlined" style="font-size: 17px; color: #16a34a;">verified</span>
                                Panduan Pengiriman Email via Gmail SMTP:
                            </div>
                            <div style="color: #334155; font-size: 0.75rem;">
                                Agar notifikasi bukti transfer &amp; email token aktivasi terkirim otomatis:<br>
                                &bull; <strong>Host</strong>: <code>smtp.gmail.com</code> | <strong>Port</strong>: <code>587</code> (TLS) atau <code>465</code> (SSL)<br>
                                &bull; <strong>Password</strong>: WAJIB gunakan <strong>16-Digit Sandi Aplikasi (App Password)</strong> Google, bukan password login biasa Anda.<br>
                                &bull; Buka: <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer" style="color: #0284c7; font-weight: 700; text-decoration: underline;">myaccount.google.com/apppasswords</a>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr 1.1fr; gap: 10px; margin-bottom: 12px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.75rem; margin-bottom: 4px;">SMTP Host</label>
                                <input type="text" id="cfgSmtpHost" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" style="height: 42px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.75rem; margin-bottom: 4px;">Port</label>
                                <input type="number" id="cfgSmtpPort" name="smtp_port" class="form-control" placeholder="587" style="height: 42px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.75rem; margin-bottom: 4px;">Keamanan</label>
                                <select id="cfgSmtpSecure" name="smtp_secure" class="form-control" style="height: 42px; padding: 0 10px; cursor: pointer;">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.75rem; margin-bottom: 4px;">Username / Email SMTP</label>
                                <input type="text" id="cfgSmtpUser" name="smtp_user" class="form-control" placeholder="user@gmail.com" style="height: 42px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.75rem; margin-bottom: 4px;">Password (16-Digit App Password)</label>
                                <input type="password" id="cfgSmtpPass" name="smtp_pass" class="form-control" placeholder="••••••••••••••••" style="height: 42px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px;">
                            <button type="submit" id="btnSaveAiSmtp" class="btn-submit-main" style="width: 100%; height: 44px; padding: 0 12px; background: linear-gradient(135deg, #0284c7, #0369a1); font-size: 0.85rem; font-weight: 700; border-radius: 10px; margin: 0;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                                <span>Simpan Pengaturan</span>
                            </button>
                            <button type="button" id="btnTestSmtp" class="btn-submit-main" style="width: 100%; height: 44px; padding: 0 12px; background: linear-gradient(135deg, #10b981, #059669); font-size: 0.85rem; font-weight: 700; border-radius: 10px; margin: 0;" title="Uji coba kirim email sekarang">
                                <span class="material-symbols-outlined" style="font-size: 18px;">send</span>
                                <span>Uji Kirim Email</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CARD DAFTAR TOKEN & PEMBAYARAN MASUK -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-title">
                    <span>Daftar Token Lisensi & Pembayaran Masuk (AI Verified)</span>
                    <span class="material-symbols-outlined" style="color: var(--primary);">vpn_key</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Waktu / ID</th>
                                <th>Token Lisensi</th>
                                <th>Durasi & Nominal</th>
                                <th>Email Pembeli</th>
                                <th>Bukti Transfer</th>
                                <th>Analisis AI</th>
                                <th>Status Token</th>
                                <th style="text-align: center;">Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody id="tokensTableBody">
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-sub); padding:16px;">Memuat data token...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cachedLogs = [];

        // ===== TOAST NOTIFICATION HELPERS =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: 'check_circle',
                error: 'error',
                warning: 'warning'
            };

            toast.innerHTML = `
                <span class="material-symbols-outlined toast-icon">${icons[type] || 'info'}</span>
                <div class="toast-content">${message}</div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function showPromptToast(message, defaultVal, placeholder, onOk, btnText = 'Perpanjang Masa Aktif') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const existing = container.querySelector('.toast-prompt');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'toast warning toast-prompt';
            toast.style.minWidth = '340px';
            toast.style.maxWidth = '420px';
            toast.style.flexDirection = 'column';
            toast.style.alignItems = 'flex-start';
            toast.style.padding = '1.15rem';
            toast.style.boxShadow = '0 12px 30px -5px rgba(0,0,0,0.2)';

            toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:10px; width:100%; margin-bottom:8px;">
                    <span class="material-symbols-outlined toast-icon" style="color:var(--primary); font-size:24px;">verified</span>
                    <div class="toast-content" style="flex:1; font-weight:700; font-size:0.9rem; color:var(--text-heading);">${message}</div>
                </div>
                <div style="width:100%; margin-bottom:12px;">
                    <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); display:block; margin-bottom:4px;">Catatan Pembayaran (Opsional):</label>
                    <input type="text" id="toastPromptInput" class="form-control" value="${defaultVal}" placeholder="${placeholder}" style="width:100%; height:38px; font-size:0.85rem; padding:0 12px; border-radius:8px; border:1.5px solid var(--border);">
                </div>
                <div style="display:flex; gap:8px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="promptCancel" style="padding:6px 14px; font-size:0.78rem; height:32px; border-radius:8px; cursor:pointer; background:#f1f5f9; border:none; font-weight:600; color:#475569;">Batal</button>
                    <button class="btn btn-primary" id="promptOk" style="padding:6px 16px; font-size:0.78rem; height:32px; border-radius:8px; background:var(--primary); color:white; border:none; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">${btnText}</button>
                </div>
            `;

            container.appendChild(toast);
            const inputEl = toast.querySelector('#toastPromptInput');
            setTimeout(() => { inputEl.focus(); inputEl.select(); }, 50);

            inputEl.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    toast.querySelector('#promptOk').click();
                }
            });

            toast.querySelector('#promptOk').onclick = () => {
                const val = inputEl.value.trim();
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
                if (onOk) onOk(val);
            };
            toast.querySelector('#promptCancel').onclick = () => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
        }

        // ===== CLIPBOARD COPY HELPERS =====
        function copyText(text, successMsg = 'Tersalin!') {
            if (!text) return;

            const fallbackCopy = (t) => {
                const el = document.createElement('textarea');
                el.value = t;
                el.setAttribute('readonly', '');
                el.style.position = 'fixed';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.focus();
                el.select();
                try {
                    const ok = document.execCommand('copy');
                    if (ok) {
                        showToast(successMsg, 'success');
                    } else {
                        showToast('Gagal menyalin token ke clipboard', 'error');
                    }
                } catch (err) {
                    showToast('Gagal menyalin token ke clipboard', 'error');
                }
                document.body.removeChild(el);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(successMsg, 'success');
                }).catch(() => {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        }

        function copyTokenResult() {
            const tokenEl = document.getElementById('tokenResultCode');
            const token = lastGeneratedToken || (tokenEl ? tokenEl.textContent.trim() : '');
            if (!token || token === 'TMS-XXXX-XXXX-XXXX') {
                showToast('Tidak ada token yang dapat disalin', 'error');
                return;
            }

            copyText(token, `Kode token ${token} berhasil disalin!`);

            const btn = document.getElementById('btnModalCopyToken');
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:20px;">check</span><span>Kode Token Tersalin!</span>';
                btn.style.background = '#10b981';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '#4f46e5';
                }, 2000);
            }
        }

        // ===== DATA FETCH & ACTIONS =====
        async function fetchLicenseData() {
            try {
                const res = await fetch('./api.php?action=get_system_license');
                const data = await res.json();

                if (data.success) {
                    const activeUntil = data.active_until ? new Date(data.active_until) : null;
                    const dateStr = activeUntil ? activeUntil.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Belum diatur';
                    
                    document.getElementById('displayActiveUntil').textContent = dateStr;
                    document.getElementById('displayLicenseNotes').textContent = data.license_notes || 'Belum ada catatan.';

                    const badge = document.getElementById('statusBadge');
                    const remText = document.getElementById('displayRemainingDays');

                    if (data.is_expired) {
                        badge.className = 'status-badge status-expired';
                        badge.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">error</span> EXPIRED';
                        remText.style.color = 'var(--danger)';
                        remText.textContent = 'Masa aktif telah berakhir (Akses web diblokir untuk user biasa)';
                    } else {
                        badge.className = 'status-badge status-active';
                        badge.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> AKTIF';
                        remText.style.color = 'var(--primary)';
                        remText.textContent = `Sisa masa aktif: ${data.remaining_days} hari lagi`;
                    }

                    if (data.active_until) {
                        const yyyy = activeUntil.getFullYear();
                        const mm = String(activeUntil.getMonth() + 1).padStart(2, '0');
                        const dd = String(activeUntil.getDate()).padStart(2, '0');
                        document.getElementById('customDate').value = `${yyyy}-${mm}-${dd}`;
                    }
                } else {
                    showToast(data.error || 'Gagal memuat status lisensi', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal terhubung ke server', 'error');
            }
        }

        async function fetchLicenseManagers() {
            try {
                const res = await fetch('./api.php?action=get_license_managers');
                const data = await res.json();
                const container = document.getElementById('managersList');

                if (data.success && Array.isArray(data.users)) {
                    container.innerHTML = data.users.map(u => `
                        <label style="display:flex; align-items:center; gap:10px; padding:6px 8px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:0.85rem;">
                            <input type="checkbox" name="mgr_usernames" value="${u.username}" ${u.is_manager || u.is_owner ? 'checked' : ''} ${u.is_owner ? 'disabled' : ''} style="width:16px; height:16px; accent-color:var(--primary);">
                            <div style="flex:1;">
                                <div style="font-weight:700; color:var(--text-heading);">${u.name} <span style="font-size:0.75rem; color:var(--text-sub); font-weight:normal;">(@${u.username})</span></div>
                                <div style="font-size:0.72rem; color:var(--primary); font-weight:600;">${u.role.toUpperCase()} ${u.is_owner ? '(Pemilik Utama - Permanen)' : ''}</div>
                            </div>
                        </label>
                    `).join('');
                } else {
                    container.innerHTML = `<div style="color:var(--danger); font-size:0.8rem; text-align:center;">${data.error || 'Gagal memuat pengguna'}</div>`;
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function fetchLicenseLogs() {
            try {
                const res = await fetch('./api.php?action=get_license_logs');
                const data = await res.json();
                const tbody = document.getElementById('logsTableBody');

                if (data.success && Array.isArray(data.logs)) {
                    cachedLogs = data.logs;
                    if (data.logs.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-sub); padding:16px;">Belum ada riwayat perpanjangan.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = data.logs.map((log, idx) => {
                        const createdDate = new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                        const newExpiryDate = new Date(log.new_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const trxNo = 'INV-LIC-' + String(log.id).padStart(5, '0');

                        return `
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary);">${trxNo}</div>
                                    <div style="font-size:0.72rem; color:var(--text-sub);">${createdDate}</div>
                                </td>
                                <td><span style="font-weight:600;">@${log.extended_by}</span></td>
                                <td><span class="status-badge status-active" style="padding:2px 8px; font-size:0.75rem;">${log.extension_type}</span></td>
                                <td><span style="font-weight:700; color:var(--text-heading);">${newExpiryDate}</span></td>
                                <td><span style="font-style:italic; color:var(--text-sub);">${log.payment_notes || '-'}</span></td>
                                <td>
                                    <button type="button" class="btn-print" onclick="printReceipt(${log.id})">
                                        <span class="material-symbols-outlined" style="font-size:14px;">print</span> Cetak Bukti
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function printReceipt(logId) {
            const log = cachedLogs.find(l => l.id == logId);
            if (!log) {
                showToast('Data bukti perpanjangan tidak ditemukan', 'error');
                return;
            }

            const trxNo = 'INV-LIC-' + String(log.id).padStart(5, '0');
            const createdDate = new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
            const newExpiryDate = new Date(log.new_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            const prevExpiryDate = log.previous_expiry ? new Date(log.previous_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Sistem Baru';

            const printWin = window.open('', '_blank', 'width=800,height=700');
            printWin.document.write(`
                <!DOCTYPE html>
                <html lang="id">
                <head>
                    <title>Bukti Perpanjangan Lisensi - ${trxNo}</title>
                    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
                    <style>
                        body { font-family: 'Plus Jakarta Sans', sans-serif; padding: 40px; color: #1e293b; background: #fff; }
                        .ticket { border: 2px solid #6366f1; border-radius: 16px; padding: 30px; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
                        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 20px; margin-bottom: 20px; }
                        .title { font-size: 20px; font-weight: 800; color: #4338ca; }
                        .badge { background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 99px; text-transform: uppercase; }
                        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
                        .info-item { background: #f8fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
                        .info-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
                        .info-value { font-size: 14px; font-weight: 800; color: #0f172a; }
                        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
                        .stamp { font-size: 12px; font-weight: 700; color: #4338ca; border: 2px solid #6366f1; padding: 6px 14px; border-radius: 8px; transform: rotate(-3deg); display: inline-block; }
                        @media print { body { padding: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="no-print" style="max-width:650px; margin:0 auto 16px text-align:right;">
                        <button onclick="window.print()" style="background:#4f46e5; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer;">🖨️ Cetak / Simpan PDF</button>
                    </div>
                    <div class="ticket">
                        <div class="header">
                            <div>
                                <div class="title">BUKTI PERPANJANGAN LISENSI WEB</div>
                                <div style="font-size:12px; color:#64748b; margin-top:2px;">TMS Head Office Operations System</div>
                            </div>
                            <div class="badge">LUNAS / VERIFIED</div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">No. Transaksi</div>
                                <div class="info-value">${trxNo}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Waktu Transaksi</div>
                                <div class="info-value">${createdDate}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Jenis Tambahan Masa Aktif</div>
                                <div class="info-value" style="color:#4f46e5;">${log.extension_type}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Pengelola Operator</div>
                                <div class="info-value">@${log.extended_by}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Masa Aktif Sebelumnya</div>
                                <div class="info-value">${prevExpiryDate}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Masa Aktif Baru (Berlaku S.D)</div>
                                <div class="info-value" style="color:#10b981;">${newExpiryDate}</div>
                            </div>
                        </div>

                        <div class="info-item" style="margin-bottom: 20px;">
                            <div class="info-label">Catatan Pembayaran / Referensi Transfer</div>
                            <div class="info-value">${log.payment_notes || 'Tidak ada catatan.'}</div>
                        </div>

                        <div class="footer">
                            <div>
                                <div style="font-size:11px; color:#64748b;">Dokumen ini diterbitkan secara otomatis oleh sistem.</div>
                                <div style="font-size:11px; font-weight:700; color:#0f172a; margin-top:4px;">TMS System License Verification</div>
                            </div>
                            <div class="stamp">OFFICIAL SYSTEM RECEIPT</div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWin.document.close();
        }

        function quickAddMonths(months) {
            const labelStr = months >= 12 ? `${months / 12} Tahun` : `${months} Bulan`;
            showPromptToast(
                `Perpanjang lisensi web +${labelStr}?`,
                `Perpanjang +${labelStr}`,
                'Contoh: Lunas via Transfer BCA',
                async (notes) => {
                    const formData = new FormData();
                    formData.append('months', months);
                    formData.append('notes', notes);

                    try {
                        const res = await fetch('./api.php?action=update_system_license', { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            fetchLicenseData();
                            fetchLicenseLogs();
                            if (data.log_id) {
                                setTimeout(() => printReceipt(data.log_id), 600);
                            }
                        } else {
                            showToast(data.error || 'Gagal memperpanjang lisensi', 'error');
                        }
                    } catch (err) {
                        showToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                'Proses Perpanjangan'
            );
        }

        document.getElementById('licenseForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitForm');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            try {
                const res = await fetch('./api.php?action=update_system_license', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchLicenseData();
                    fetchLicenseLogs();
                    if (data.log_id) {
                        setTimeout(() => printReceipt(data.log_id), 600);
                    }
                } else {
                    showToast(data.error || 'Gagal memperbarui lisensi', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">save</span> Simpan Perubahan Lisensi';
            }
        };

        document.getElementById('managersForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitManagers');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            const selected = Array.from(document.querySelectorAll('input[name="mgr_usernames"]:checked')).map(cb => cb.value);

            const formData = new FormData();
            formData.append('usernames', selected.join(','));

            try {
                const res = await fetch('./api.php?action=update_license_managers', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchLicenseManagers();
                } else {
                    showToast(data.error || 'Gagal menyimpan otorisasi', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">how_to_reg</span> Simpan Hak Akses Pengelola';
            }
        };

        async function fetchPaymentSettings() {
            try {
                const res = await fetch('./api.php?action=get_payment_config');
                const data = await res.json();
                if (data.success && data.config) {
                    const c = data.config;
                    document.getElementById('cfgBankName').value = c.bank_name || '';
                    document.getElementById('cfgAccountNum').value = c.account_number || '';
                    document.getElementById('cfgAccountHolder').value = c.account_holder || '';
                    document.getElementById('cfgPrice').value = c.price_per_month || 150000;
                    document.getElementById('cfgAdminEmail').value = c.admin_email || '';

                    if (c.gemini_api_key) {
                        document.getElementById('cfgGeminiKey').value = c.gemini_api_key;
                    }
                    document.getElementById('cfgSmtpHost').value = c.smtp_host || '';
                    document.getElementById('cfgSmtpPort').value = c.smtp_port || 587;
                    document.getElementById('cfgSmtpSecure').value = c.smtp_secure || (c.smtp_port == 465 ? 'ssl' : 'tls');
                    document.getElementById('cfgSmtpUser').value = c.smtp_user || '';
                    document.getElementById('cfgSmtpPass').value = c.smtp_pass || '';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function fetchLicenseTokens() {
            try {
                const res = await fetch('./api.php?action=get_license_tokens');
                const data = await res.json();
                const tbody = document.getElementById('tokensTableBody');

                if (data.success && Array.isArray(data.tokens)) {
                    if (data.tokens.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-sub); padding:16px;">Belum ada pembayaran atau token yang diterbitkan.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.tokens.map(t => {
                        const dateStr = new Date(t.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                        
                        let statusBadge = '';
                        let tokenDisplay = '';
                        let actionsHtml = '';

                        if (t.status === 'pending') {
                            statusBadge = '<span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">MENUNGGU PERSETUJUAN</span>';
                            tokenDisplay = '<span style="color:#d97706; font-style:italic; font-size:0.8rem; font-weight:700;"><span class="material-symbols-outlined" style="font-size:14px; vertical-align:-2px;">hourglass_empty</span> Menunggu Otorisasi</span>';
                            actionsHtml = `
                                <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                    <button type="button" onclick="approvePayment(${t.id}, '${t.user_email}', ${t.months})" style="background:#10b981; color:#ffffff; border:none; padding:6px 10px; border-radius:8px; font-weight:700; font-size:0.75rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px;" title="Setujui dan buatkan Token">
                                        <span class="material-symbols-outlined" style="font-size:15px;">check_circle</span>
                                        <span>Setujui & Buat Token</span>
                                    </button>
                                    <button type="button" onclick="rejectPayment(${t.id})" style="background:#fee2e2; color:#ef4444; border:1px solid #fca5a5; padding:6px 10px; border-radius:8px; font-weight:700; font-size:0.75rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px;" title="Tolak bukti transfer">
                                        <span class="material-symbols-outlined" style="font-size:15px;">cancel</span>
                                        <span>Tolak</span>
                                    </button>
                                </div>
                            `;
                        } else if (t.status === 'active') {
                            statusBadge = '<span style="background:#dcfce7; color:#15803d; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">AKTIF (BELUM DIPAKAI)</span>';
                            tokenDisplay = `<div style="font-family:monospace; font-weight:800; color:var(--primary); letter-spacing:1px;">${t.token}</div>`;
                            actionsHtml = `
                                <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                    <button type="button" onclick="copyText('${t.token}', 'Token berhasil disalin!')" style="background:#e0e7ff; color:#4338ca; border:none; padding:5px 8px; border-radius:6px; font-weight:700; font-size:0.72rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">content_copy</span>
                                        <span>Salin</span>
                                    </button>
                                    <button type="button" onclick="resendTokenEmail(${t.id})" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:5px 8px; border-radius:6px; font-weight:700; font-size:0.72rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">mail</span>
                                        <span>Kirim Ulang Email</span>
                                    </button>
                                </div>
                            `;
                        } else if (t.status === 'rejected') {
                            statusBadge = '<span style="background:#fee2e2; color:#ef4444; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">DITOLAK</span>';
                            tokenDisplay = '<span style="color:#ef4444; font-size:0.8rem;">Ditolak</span>';
                            actionsHtml = '<span style="font-size:0.75rem; color:#ef4444;">-</span>';
                        } else {
                            statusBadge = '<span style="background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">TERPAKAI</span>';
                            tokenDisplay = `<div style="font-family:monospace; font-weight:700; color:#64748b; text-decoration:line-through;">${t.token}</div>`;
                            actionsHtml = '<span style="font-size:0.75rem; color:#94a3b8;">Telah Digunakan</span>';
                        }
                        
                        const aiBadge = (t.ai_status === 'verified')
                            ? '<span style="color:#10b981; font-weight:700;">✓ Verified</span>'
                            : '<span style="color:#f59e0b; font-weight:700;">⚠ Flagged</span>';

                        const proofLink = t.payment_proof 
                            ? `<a href="./uploads/payments/${encodeURIComponent(t.payment_proof)}" target="_blank" style="color:var(--primary); font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:16px;">receipt_long</span> Lihat Struk</a>`
                            : '<span style="color:var(--text-sub);">-</span>';

                        const formattedAmount = 'Rp ' + Number(t.amount).toLocaleString('id-ID');

                        return `
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--text-heading);">#TOK-${String(t.id).padStart(4, '0')}</div>
                                    <div style="font-size:0.72rem; color:var(--text-sub);">${dateStr}</div>
                                </td>
                                <td>${tokenDisplay}</td>
                                <td>
                                    <div style="font-weight:700;">${t.months} Bulan</div>
                                    <div style="font-size:0.75rem; color:var(--text-sub);">${formattedAmount}</div>
                                </td>
                                <td>
                                    <span style="font-weight:600; color:var(--text-heading);">${t.user_email}</span>
                                </td>
                                <td>${proofLink}</td>
                                <td>${aiBadge}</td>
                                <td>${statusBadge}</td>
                                <td style="text-align: center;">${actionsHtml}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" style="color:var(--danger); text-align:center;">${data.error || 'Gagal memuat token'}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
            }
        }

        let lastGeneratedToken = '';

        async function approvePayment(id, email, months) {
            if (!confirm(`Setujui bukti pembayaran untuk ${email} (${months} Bulan)?\n\nSistem akan menerbitkan Token Lisensi resmi dan mengirimkannya langsung ke email pembeli.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);

                const res = await fetch('./api.php?action=approve_payment_token', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    lastGeneratedToken = data.token;
                    document.getElementById('tokenResultCode').textContent = data.token;
                    document.getElementById('tokenResultDesc').innerHTML = `
                        Token lisensi (<strong>${data.months} Bulan</strong>) telah berhasil diterbitkan!<br>
                        Email aktivasi otomatis dikirimkan ke: <strong>${data.email}</strong>.
                    `;

                    const waText = encodeURIComponent(`Halo, pembayaran lisensi TMS Anda (${data.months} Bulan) telah disetujui! Berikut adalah Token Aktivasi Anda:\n\n*${data.token}*\n\nSilakan masukkan token ini pada halaman sistem untuk mengaktifkan kembali.`);
                    document.getElementById('btnShareWa').href = `https://wa.me/?text=${waText}`;

                    document.getElementById('tokenResultModal').style.display = 'flex';
                    fetchLicenseTokens();
                    fetchLicenseLogs();
                } else {
                    showToast(data.error || 'Gagal menyetujui pembayaran', 'error');
                }
            } catch (err) {
                showToast('Gangguan jaringan ke server', 'error');
            }
        }

        async function rejectPayment(id) {
            if (!confirm('Apakah Anda yakin ingin MENOLAK bukti pembayaran ini?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);

                const res = await fetch('./api.php?action=reject_payment_token', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showToast('Bukti pembayaran ditolak.', 'info');
                    fetchLicenseTokens();
                } else {
                    showToast(data.error || 'Gagal menolak pembayaran', 'error');
                }
            } catch (err) {
                showToast('Gangguan jaringan ke server', 'error');
            }
        }

        async function resendTokenEmail(id) {
            if (!confirm('Kirim ulang email token aktivasi ke pembeli?')) return;
            try {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('./api.php?action=resend_token_email', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.error || 'Gagal mengirim ulang email', 'error');
                }
            } catch (err) {
                showToast('Gangguan jaringan', 'error');
            }
        }

        async function confirmRevokeLicense() {
            const reason = prompt('PERINGATAN KERAS:\nApakah Anda yakin ingin membatalkan/mengunci pemakaian sistem sekarang?\n\nSistem akan langsung berstatus EXPIRED dan seluruh staf/driver akan dialihkan ke halaman Expired.\n\nKetik "BATALKAN" (huruf besar) untuk melanjutkan:');
            
            if (reason !== 'BATALKAN') {
                if (reason !== null) alert('Konfirmasi dibatalkan (kata kunci tidak cocok).');
                return;
            }

            try {
                const res = await fetch('./api.php?action=revoke_system_license', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    fetchLicenseData();
                    fetchLicenseLogs();
                } else {
                    alert(data.error || 'Gagal membatalkan lisensi.');
                }
            } catch (err) {
                alert('Gangguan koneksi ke server.');
            }
        }

        function copyTokenResult() {
            if (lastGeneratedToken) {
                copyText(lastGeneratedToken, 'Kode token berhasil disalin!');
            }
        }

        function closeTokenResultModal() {
            document.getElementById('tokenResultModal').style.display = 'none';
        }

        document.getElementById('paymentConfigForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSavePaymentConfig');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            try {
                const res = await fetch('./api.php?action=update_payment_settings', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchPaymentSettings();
                } else {
                    showToast(data.error || 'Gagal menyimpan pengaturan', 'error');
                }
            } catch (err) {
                showToast('Terjadi gangguan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">save</span> Simpan Rekening & Harga';
            }
        };

        document.getElementById('aiSmtpForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveAiSmtp');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            try {
                const res = await fetch('./api.php?action=update_payment_settings', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchPaymentSettings();
                } else {
                    showToast(data.error || 'Gagal menyimpan pengaturan AI', 'error');
                }
            } catch (err) {
                showToast('Terjadi gangguan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">save</span><span>Simpan Pengaturan</span>';
            }
        };

        const cfgPort = document.getElementById('cfgSmtpPort');
        const cfgSecure = document.getElementById('cfgSmtpSecure');
        if (cfgPort && cfgSecure) {
            cfgPort.addEventListener('change', (e) => {
                if (e.target.value == 465) cfgSecure.value = 'ssl';
                if (e.target.value == 587) cfgSecure.value = 'tls';
            });
            cfgSecure.addEventListener('change', (e) => {
                if (e.target.value === 'ssl') cfgPort.value = 465;
                if (e.target.value === 'tls') cfgPort.value = 587;
            });
        }

        const btnTest = document.getElementById('btnTestSmtp');
        if (btnTest) {
            btnTest.onclick = async () => {
                const form = document.getElementById('aiSmtpForm');
                btnTest.disabled = true;
                btnTest.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite; font-size:18px;">progress_activity</span><span>Menguji...</span>';

                try {
                    // Auto-save settings first so test runs against latest credentials
                    await fetch('./api.php?action=update_payment_settings', { method: 'POST', body: new FormData(form) });

                    const targetEmail = document.getElementById('cfgSmtpUser').value || document.getElementById('cfgAdminEmail').value || '';
                    const testFormData = new FormData();
                    testFormData.append('target_email', targetEmail);

                    const res = await fetch('./api.php?action=test_smtp_email', { method: 'POST', body: testFormData });
                    const data = await res.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        alert("✓ " + data.message);
                    } else {
                        showToast(data.error || 'Uji kirim email gagal', 'error');
                        alert("✗ " + (data.error || 'Uji kirim email gagal'));
                    }
                } catch (err) {
                    showToast('Terjadi gangguan koneksi ke server saat menguji SMTP', 'error');
                } finally {
                    btnTest.disabled = false;
                    btnTest.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">send</span><span>Uji Kirim Email</span>';
                }
            };
        }

        window.addEventListener('DOMContentLoaded', () => {
            fetchLicenseData();
            fetchLicenseManagers();
            fetchLicenseLogs();
            fetchPaymentSettings();
            fetchLicenseTokens();
        });
    </script>
    <!-- MODAL HASIL GENERATE TOKEN ADMIN -->
    <div id="tokenResultModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.85); z-index:9999; align-items:center; justify-content:center; padding:1rem; backdrop-filter:blur(5px);">
        <div style="background:#1e293b; border:1px solid #334155; border-radius:24px; max-width:480px; width:100%; padding:2.25rem 2rem; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.6); color:#ffffff;">
            <div style="width:68px; height:68px; background:rgba(16,185,129,0.15); border:2px solid rgba(16,185,129,0.4); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#10b981; margin-bottom:1.25rem;">
                <span class="material-symbols-outlined" style="font-size:38px;">key</span>
            </div>
            <h3 style="font-size:1.35rem; font-weight:800; margin-bottom:6px;">Token Lisensi Berhasil Diterbitkan!</h3>
            <p style="font-size:0.85rem; color:#94a3b8; margin-bottom:1.25rem; line-height:1.5;" id="tokenResultDesc">
                Token lisensi telah diterbitkan dan otomatis dikirimkan ke email pembeli.
            </p>
            <div style="background:#0f172a; border:1.5px dashed #4f46e5; border-radius:14px; padding:1.25rem; margin-bottom:1.5rem;">
                <div style="font-size:0.75rem; color:#94a3b8; font-weight:700; text-transform:uppercase; margin-bottom:6px;">KODE TOKEN AKTIVASI:</div>
                <div id="tokenResultCode" style="font-family:monospace; font-size:1.5rem; font-weight:800; color:#818cf8; letter-spacing:2px;">TMS-XXXX-XXXX-XXXX</div>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <button type="button" id="btnModalCopyToken" onclick="copyTokenResult()" style="background:#4f46e5; color:#ffffff; border:none; padding:12px 16px; border-radius:12px; font-weight:700; font-size:0.9rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:20px;">content_copy</span>
                    <span>Salin Kode Token</span>
                </button>
                <a id="btnShareWa" href="#" target="_blank" rel="noopener noreferrer" style="background:linear-gradient(135deg, #25D366, #128C7E); color:#ffffff; text-decoration:none; padding:12px 16px; border-radius:12px; font-weight:700; font-size:0.9rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <svg viewBox="0 0 24 24" style="width:20px; height:20px; fill:currentColor;"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.001.574 1.761.855 2.806.855 3.18 0 5.767-2.587 5.767-5.766.001-3.18-2.585-5.768-5.767-5.768zm3.376 8.204c-.149.418-.752.793-1.042.845-.275.048-.624.088-1.795-.398-1.503-.623-2.473-2.15-2.548-2.25-.075-.101-.611-.813-.611-1.549 0-.736.386-1.098.523-1.248.137-.149.3-.187.4-.187.1 0 .2 0 .287.005.093.004.218-.035.341.261.129.308.439 1.07.478 1.149.039.078.064.17.014.27-.05.099-.075.161-.149.248-.075.086-.157.193-.224.259-.075.074-.153.155-.066.304.087.149.387.639.83 1.033.57.507 1.05.664 1.2.738.149.075.237.062.325-.038.087-.1.374-.436.474-.585.1-.149.2-.124.336-.074.137.05.868.409 1.018.484.149.075.249.112.286.174.037.063.037.362-.112.78zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.66 1.434 5.176L2 22l4.954-1.399C8.423 21.493 10.15 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                    <span>Kirim Token ke WhatsApp Pembeli</span>
                </a>
                <button type="button" onclick="closeTokenResultModal()" style="background:transparent; color:#94a3b8; border:1px solid #334155; padding:10px 16px; border-radius:12px; font-weight:600; cursor:pointer; margin-top:2px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</body>

</html>
