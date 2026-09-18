<?php
require_once 'db_config.php';
require_once 'auth_check.php';

// Ensure tables exist
if (function_exists('ensureLicenseTablesExist')) {
    ensureLicenseTablesExist($pdo);
}

// Fetch payment settings from database
$config = [
    'bank_name' => 'BCA',
    'account_number' => '1234567890',
    'account_holder' => 'Dhanielo Marthinz',
    'price_per_month' => 150000,
    'admin_email' => 'dhanielo.marthinz@gmail.com'
];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('payment_bank_name', 'payment_account_number', 'payment_account_holder', 'payment_price_per_month', 'payment_admin_email')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] === 'payment_bank_name') $config['bank_name'] = $row['setting_value'];
        if ($row['setting_key'] === 'payment_account_number') $config['account_number'] = $row['setting_value'];
        if ($row['setting_key'] === 'payment_account_holder') $config['account_holder'] = $row['setting_value'];
        if ($row['setting_key'] === 'payment_price_per_month') $config['price_per_month'] = (int)$row['setting_value'];
        if ($row['setting_key'] === 'payment_admin_email') $config['admin_email'] = $row['setting_value'];
    }
} catch (Exception $e) {}

$basePrice = $config['price_per_month'] ?: 150000;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran & Perpanjangan Lisensi | TMS Head Office</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <style>
        :root {
            --bg-base: #0f172a;
            --card-bg: #1e293b;
            --surface: #1e293b;
            --surface-hover: #273549;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #38bdf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.12) 0%, transparent 40%);
            min-height: 100vh;
            color: var(--text-main);
            padding: 2.5rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 820px;
            width: 100%;
            margin: 0 auto;
        }

        .payment-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #6366f1, #38bdf8, #10b981);
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(56, 189, 248, 0.2));
            border: 1px solid rgba(99, 102, 241, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .brand-info h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-info p {
            font-size: 0.82rem;
            color: var(--text-sub);
            margin-top: 2px;
        }

        .badge-ai {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .step-section {
            margin-bottom: 2rem;
        }

        .step-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-number {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
        }

        /* Duration Packages Grid */
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 1rem;
        }

        .package-item {
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--border);
            border-radius: 18px;
            padding: 1.25rem 1rem;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            text-align: center;
        }

        .package-item:hover {
            border-color: rgba(99, 102, 241, 0.6);
            transform: translateY(-3px);
            background: rgba(30, 41, 59, 0.8);
        }

        .package-item.active {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.12);
            box-shadow: 0 8px 20px -4px rgba(99, 102, 241, 0.3);
        }

        .package-badge {
            position: absolute;
            top: -10px;
            right: 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 99px;
            letter-spacing: 0.5px;
        }

        .package-duration {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .package-price {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--accent);
        }

        .package-note {
            font-size: 0.72rem;
            color: var(--text-sub);
            margin-top: 4px;
        }

        /* Bank Account Info Box */
        .bank-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 1.5px solid rgba(99, 102, 241, 0.3);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }

        .bank-details {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .bank-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .bank-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
        }

        .bank-account {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
            font-family: monospace, 'Courier New';
        }

        .bank-holder {
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .btn-copy {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-copy:hover {
            background: var(--primary);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* WhatsApp Action Buttons */
        .btn-wa-help {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: #ffffff !important;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);
        }

        .btn-wa-help:hover {
            background: linear-gradient(135deg, #20ba5a, #0e7064);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.5);
            color: #ffffff;
        }

        .btn-wa-help svg, .floating-wa svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
            flex-shrink: 0;
        }

        .floating-wa {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #25D366;
            color: #ffffff !important;
            padding: 12px 20px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.45);
            z-index: 1050;
            transition: all 0.25s ease;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .floating-wa:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 14px 35px rgba(37, 211, 102, 0.6);
            background: #20ba5a;
            color: #ffffff;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 0.85rem 1.15rem;
            font-size: 0.95rem;
            color: #ffffff;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        /* Upload Area */
        .upload-dropzone {
            border: 2px dashed var(--border);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            background: rgba(15, 23, 42, 0.5);
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
        }

        .upload-dropzone:hover,
        .upload-dropzone.dragover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.08);
        }

        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .preview-container {
            display: none;
            margin-top: 1rem;
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            border: 1.5px solid var(--border);
            max-height: 260px;
            background: #000;
        }

        .preview-container img {
            width: 100%;
            height: 260px;
            object-fit: contain;
        }

        .preview-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .preview-remove:hover {
            transform: scale(1.1);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            padding: 1.05rem;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 12px 28px -6px rgba(99, 102, 241, 0.5);
            transition: all 0.25s;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px -4px rgba(99, 102, 241, 0.6);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-sub);
            font-size: 0.85rem;
            text-decoration: none;
            margin-top: 1.5rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #ffffff;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: 24px;
            max-width: 520px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
            transform: scale(0.92);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .token-display-box {
            background: rgba(15, 23, 42, 0.8);
            border: 2px dashed var(--primary);
            border-radius: 16px;
            padding: 1.25rem;
            margin: 1.5rem 0;
        }

        .token-code {
            font-family: monospace, 'Courier New';
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--accent);
            letter-spacing: 3px;
        }

        /* Toast Alert */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            z-index: 1100;
            animation: toastSlideUp 0.3s ease;
        }

        @keyframes toastSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="payment-card">
            <div class="brand-header">
                <div class="brand-info">
                    <div class="brand-logo">
                        <span class="material-symbols-outlined" style="font-size: 28px;">credit_card</span>
                    </div>
                    <div>
                        <h1>Perpanjangan Lisensi Web TMS</h1>
                        <p>Pembayaran Mandiri dengan Verifikasi Keaslian Bukti Transfer Otomatis oleh AI</p>
                    </div>
                </div>
                <div class="badge-ai">
                    <span class="material-symbols-outlined" style="font-size: 18px;">psychology</span>
                    <span>AI Vision Verified</span>
                </div>
            </div>

            <form id="paymentForm" enctype="multipart/form-data">
                <!-- STEP 1: PILIH PAKET DURASI -->
                <div class="step-section">
                    <div class="step-title">
                        <span class="step-number">1</span>
                        <span>Pilih Durasi Masa Aktif Perpanjangan (Wajib)</span>
                    </div>

                    <div class="packages-grid">
                        <div class="package-item" data-months="1" data-price="<?php echo $basePrice; ?>" onclick="selectPackage(this)">
                            <div class="package-duration">1 Bulan</div>
                            <div class="package-price">Rp <?php echo number_format($basePrice, 0, ',', '.'); ?></div>
                            <div class="package-note">Standard</div>
                        </div>

                        <div class="package-item active" data-months="3" data-price="<?php echo round($basePrice * 3 * 0.95); ?>" onclick="selectPackage(this)">
                            <div class="package-badge">HEMAT 5%</div>
                            <div class="package-duration">3 Bulan</div>
                            <div class="package-price">Rp <?php echo number_format(round($basePrice * 3 * 0.95), 0, ',', '.'); ?></div>
                            <div class="package-note">Pilihan Favorit</div>
                        </div>

                        <div class="package-item" data-months="6" data-price="<?php echo round($basePrice * 6 * 0.90); ?>" onclick="selectPackage(this)">
                            <div class="package-badge">HEMAT 10%</div>
                            <div class="package-duration">6 Bulan</div>
                            <div class="package-price">Rp <?php echo number_format(round($basePrice * 6 * 0.90), 0, ',', '.'); ?></div>
                            <div class="package-note">Setengah Tahun</div>
                        </div>

                        <div class="package-item" data-months="12" data-price="<?php echo round($basePrice * 12 * 0.83); ?>" onclick="selectPackage(this)">
                            <div class="package-badge">HEMAT 17%</div>
                            <div class="package-duration">1 Tahun</div>
                            <div class="package-price">Rp <?php echo number_format(round($basePrice * 12 * 0.83), 0, ',', '.'); ?></div>
                            <div class="package-note">Terbaik & Paling Hemat</div>
                        </div>
                    </div>
                    <input type="hidden" name="months" id="selectedMonths" value="3">
                </div>

                <!-- STEP 2: REKENING TUJUAN TRANSFER -->
                <div class="step-section">
                    <div class="step-title">
                        <span class="step-number">2</span>
                        <span>Transfer Sesuai Nominal ke Rekening Resmi</span>
                    </div>

                    <div class="bank-card">
                        <div class="bank-details">
                            <div class="bank-icon">
                                <span class="material-symbols-outlined" style="font-size: 32px;">account_balance</span>
                            </div>
                            <div>
                                <div class="bank-name">BANK <?php echo htmlspecialchars($config['bank_name']); ?></div>
                                <div class="bank-account" id="accountNumberText"><?php echo htmlspecialchars($config['account_number']); ?></div>
                                <div class="bank-holder">A.N. <?php echo htmlspecialchars($config['account_holder']); ?></div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <button type="button" class="btn-copy" onclick="copyText('<?php echo htmlspecialchars($config['account_number']); ?>', 'Nomor Rekening disalin!')">
                                <span class="material-symbols-outlined" style="font-size: 18px;">content_copy</span>
                                <span>Salin No. Rek</span>
                            </button>
                            <button type="button" class="btn-copy" id="btnCopyAmount" onclick="copyAmount()">
                                <span class="material-symbols-outlined" style="font-size: 18px;">payments</span>
                                <span>Salin Nominal</span>
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: rgba(15, 23, 42, 0.45); padding: 8px 14px; border-radius: 12px; border: 1px solid var(--border);">
                        <span style="font-size: 0.8rem; color: var(--text-sub);">Butuh konfirmasi rekening atau kendala transfer?</span>
                        <a href="https://wa.me/62822107031118?text=Halo%20Admin%20TMS,%20saya%20ingin%20konfirmasi%20pembayaran%20lisensi%20sistem." target="_blank" rel="noopener noreferrer" class="btn-wa-help">
                            <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.001.574 1.761.855 2.806.855 3.18 0 5.767-2.587 5.767-5.766.001-3.18-2.585-5.768-5.767-5.768zm3.376 8.204c-.149.418-.752.793-1.042.845-.275.048-.624.088-1.795-.398-1.503-.623-2.473-2.15-2.548-2.25-.075-.101-.611-.813-.611-1.549 0-.736.386-1.098.523-1.248.137-.149.3-.187.4-.187.1 0 .2 0 .287.005.093.004.218-.035.341.261.129.308.439 1.07.478 1.149.039.078.064.17.014.27-.05.099-.075.161-.149.248-.075.086-.157.193-.224.259-.075.074-.153.155-.066.304.087.149.387.639.83 1.033.57.507 1.05.664 1.2.738.149.075.237.062.325-.038.087-.1.374-.436.474-.585.1-.149.2-.124.336-.074.137.05.868.409 1.018.484.149.075.249.112.286.174.037.063.037.362-.112.78zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.66 1.434 5.176L2 22l4.954-1.399C8.423 21.493 10.15 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                            <span>Chat WhatsApp (0822-1070-31118)</span>
                        </a>
                    </div>
                </div>

                <!-- STEP 3: DATA PENGGUNA & UPLOAD BUKTI -->
                <div class="step-section">
                    <div class="step-title">
                        <span class="step-number">3</span>
                        <span>Masukkan Email & Unggah Bukti Transfer</span>
                    </div>

                    <div class="form-group">
                        <label for="userEmail">Email Anda (Untuk Menerima Token Aktivasi Lisensi):</label>
                        <input type="email" id="userEmail" name="email" class="form-control" placeholder="nama@perusahaan.com" required>
                        <small style="color: var(--text-sub); font-size: 0.76rem; margin-top: 5px; display: block;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: -2px; color: var(--accent);">mark_email_read</span>
                            Token aktivasi lisensi sistem akan otomatis dikirimkan ke alamat email ini setelah diverifikasi.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Foto / Screenshot Struk Bukti Transfer:</label>
                        <div class="upload-dropzone" id="dropzone" onclick="document.getElementById('proofFileInput').click()">
                            <input type="file" id="proofFileInput" name="proof_file" accept="image/jpeg,image/png,image/webp" required onchange="handleFileSelect(event)">
                            <div id="uploadPrompt">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: var(--accent); margin-bottom: 8px;">cloud_upload</span>
                                <div style="font-weight: 700; color: #ffffff; font-size: 0.95rem;">Klik untuk Unggah atau Tarik Gambar Struk ke Sini</div>
                                <div style="font-size: 0.78rem; color: var(--text-sub); margin-top: 4px;">Mendukung format JPG, PNG, WEBP (Maksimal 12MB). Pastikan status & nominal terlihat jelas.</div>
                            </div>
                        </div>

                        <div class="preview-container" id="previewContainer">
                            <img id="previewImage" src="" alt="Preview Bukti Transfer">
                            <button type="button" class="preview-remove" onclick="removeFile(event)" title="Hapus Gambar">
                                <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">
                    <span class="material-symbols-outlined">verified</span>
                    <span id="btnSubmitText">Kirim & Verifikasi Bukti Pembayaran via AI</span>
                </button>
            </form>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <a href="expired" class="back-link">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                    <span>Kembali ke Halaman Expire</span>
                </a>
                <a href="https://wa.me/62822107031118?text=Halo%20Admin%20TMS,%20saya%20ingin%20bertanya%20mengenai%20perpanjangan%20masa%20aktif%20lisensi%20sistem." target="_blank" rel="noopener noreferrer" class="btn-wa-help" style="padding: 6px 14px; font-size: 0.8rem;">
                    <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.001.574 1.761.855 2.806.855 3.18 0 5.767-2.587 5.767-5.766.001-3.18-2.585-5.768-5.767-5.768zm3.376 8.204c-.149.418-.752.793-1.042.845-.275.048-.624.088-1.795-.398-1.503-.623-2.473-2.15-2.548-2.25-.075-.101-.611-.813-.611-1.549 0-.736.386-1.098.523-1.248.137-.149.3-.187.4-.187.1 0 .2 0 .287.005.093.004.218-.035.341.261.129.308.439 1.07.478 1.149.039.078.064.17.014.27-.05.099-.075.161-.149.248-.075.086-.157.193-.224.259-.075.074-.153.155-.066.304.087.149.387.639.83 1.033.57.507 1.05.664 1.2.738.149.075.237.062.325-.038.087-.1.374-.436.474-.585.1-.149.2-.124.336-.074.137.05.868.409 1.018.484.149.075.249.112.286.174.037.063.037.362-.112.78zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.66 1.434 5.176L2 22l4.954-1.399C8.423 21.493 10.15 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                    <span>Bantuan WhatsApp</span>
                </a>
                <a href="login" class="back-link">
                    <span>Halaman Login</span>
                    <span class="material-symbols-outlined" style="font-size: 18px;">login</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL SUKSES DENGAN TOKEN -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-card">
            <div style="width: 72px; height: 72px; background: rgba(16, 185, 129, 0.15); border: 2px solid rgba(16, 185, 129, 0.4); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--success); margin-bottom: 1.25rem;">
                <span class="material-symbols-outlined" style="font-size: 42px;">verified_user</span>
            </div>

            <h2 style="font-size: 1.45rem; font-weight: 800; color: #ffffff; margin-bottom: 6px;">Pembayaran & AI Lolos Verifikasi!</h2>
            <p style="font-size: 0.88rem; color: var(--text-sub); line-height: 1.5;" id="successSummary">
                Struk transfer Anda dinyatakan valid dan asli. Token aktivasi telah dikirimkan ke email Anda.
            </p>

            <div class="token-display-box">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 6px;">
                    KODE TOKEN AKTIVASI ANDA:
                </div>
                <div class="token-code" id="modalTokenCode">TMS-XXXX-XXXX-XXXX</div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 1.5rem; flex-direction: column;">
                <button type="button" class="btn-submit" id="btnActivateNow" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <span class="material-symbols-outlined">key</span>
                    <span>Aktivasi Token Sekarang</span>
                </button>
                <button type="button" class="btn-copy" onclick="copyGeneratedToken()" style="justify-content: center; height: 44px; font-size: 0.9rem;">
                    <span class="material-symbols-outlined">content_copy</span>
                    <span>Salin Kode Token</span>
                </button>
            </div>

            <div style="font-size: 0.78rem; color: #64748b; margin-top: 1.25rem; border-top: 1px solid var(--border); padding-top: 10px;">
                Bukti pembayaran juga telah diteruskan ke administrator <strong>dhanielo.marthinz@gmail.com</strong>
                <div style="margin-top: 8px;">
                    Butuh bantuan aktivasi cepat? <a href="https://wa.me/62822107031118?text=Halo%20Admin%20TMS,%20saya%20sudah%20melakukan%20pembayaran%20dan%20mendapatkan%20token." target="_blank" rel="noopener noreferrer" style="color: #34d399; font-weight: 700; text-decoration: underline;">Chat WhatsApp Admin (0822-1070-31118)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FLOATING WHATSAPP BUTTON -->
    <a href="https://wa.me/62822107031118?text=Halo%20Admin%20TMS,%20saya%20ingin%20bertanya%20mengenai%20perpanjangan%20masa%20aktif%20lisensi%20sistem." target="_blank" rel="noopener noreferrer" class="floating-wa" title="Hubungi Admin via WhatsApp">
        <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.001.574 1.761.855 2.806.855 3.18 0 5.767-2.587 5.767-5.766.001-3.18-2.585-5.768-5.767-5.768zm3.376 8.204c-.149.418-.752.793-1.042.845-.275.048-.624.088-1.795-.398-1.503-.623-2.473-2.15-2.548-2.25-.075-.101-.611-.813-.611-1.549 0-.736.386-1.098.523-1.248.137-.149.3-.187.4-.187.1 0 .2 0 .287.005.093.004.218-.035.341.261.129.308.439 1.07.478 1.149.039.078.064.17.014.27-.05.099-.075.161-.149.248-.075.086-.157.193-.224.259-.075.074-.153.155-.066.304.087.149.387.639.83 1.033.57.507 1.05.664 1.2.738.149.075.237.062.325-.038.087-.1.374-.436.474-.585.1-.149.2-.124.336-.074.137.05.868.409 1.018.484.149.075.249.112.286.174.037.063.037.362-.112.78zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.66 1.434 5.176L2 22l4.954-1.399C8.423 21.493 10.15 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
        <span>Chat WhatsApp (0822-1070-31118)</span>
    </a>

    <script>
        let currentPrice = <?php echo round($basePrice * 3 * 0.95); ?>;
        let generatedToken = '';

        function selectPackage(el) {
            document.querySelectorAll('.package-item').forEach(item => item.classList.remove('active'));
            el.classList.add('active');
            
            const months = el.getAttribute('data-months');
            currentPrice = parseInt(el.getAttribute('data-price')) || 0;
            document.getElementById('selectedMonths').value = months;
        }

        function copyAmount() {
            copyText(currentPrice.toString(), `Nominal Rp ${currentPrice.toLocaleString('id-ID')} disalin!`);
        }

        function copyText(text, successMsg = 'Tersalin!') {
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMsg, 'check_circle');
            }).catch(() => {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                showToast(successMsg, 'check_circle');
            });
        }

        function showToast(msg, icon = 'info') {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `<span class="material-symbols-outlined" style="color:var(--accent); font-size:20px;">${icon}</span><span>${msg}</span>`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.match('image.*')) {
                showToast('Harap pilih file gambar (JPG, PNG, WEBP)', 'error');
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                document.getElementById('previewImage').src = event.target.result;
                document.getElementById('previewContainer').style.display = 'block';
                document.getElementById('uploadPrompt').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        function removeFile(e) {
            e.stopPropagation();
            document.getElementById('proofFileInput').value = '';
            document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('uploadPrompt').style.display = 'block';
        }

        function copyGeneratedToken() {
            if (generatedToken) {
                copyText(generatedToken, 'Token lisensi berhasil disalin!');
            }
        }

        // Form Submission with AI verification state
        document.getElementById('paymentForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnSubmitText');
            const origHtml = btn.innerHTML;

            const months = document.getElementById('selectedMonths').value;
            const email = document.getElementById('userEmail').value.trim();
            const fileInput = document.getElementById('proofFileInput');

            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Wajib mengunggah gambar bukti transfer.', 'warning');
                return;
            }

            // Enter loading state with AI scan animation
            btn.disabled = true;
            btn.style.background = 'linear-gradient(135deg, #4338ca, #312e81)';
            btn.innerHTML = `
                <span class="material-symbols-outlined" style="animation: spin 1.2s linear infinite;">psychology</span>
                <span>AI sedang memindai keaslian bukti transfer...</span>
            `;

            try {
                const formData = new FormData(e.target);
                const res = await fetch('./api.php?action=submit_payment', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    generatedToken = data.token;
                    document.getElementById('modalTokenCode').textContent = data.token;
                    document.getElementById('successSummary').innerHTML = `
                        Bukti transfer Anda untuk paket <strong>${data.months} Bulan</strong> berhasil diverifikasi sah oleh AI.<br>
                        Token telah dikirim ke: <strong>${data.email}</strong>.
                    `;

                    // Configure activation button shortcut
                    document.getElementById('btnActivateNow').onclick = () => {
                        window.location.href = `expired?token=${encodeURIComponent(data.token)}`;
                    };

                    document.getElementById('successModal').classList.add('active');
                } else {
                    showToast(data.error || 'Verifikasi gagal. Pastikan struk transfer jelas dan sah.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    btn.style.background = '';
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi gangguan jaringan ke server.', 'error');
                btn.disabled = false;
                btn.innerHTML = origHtml;
                btn.style.background = '';
            }
        };

        // Add spinning keyframe animation
        const style = document.createElement('style');
        style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);
    </script>
</body>

</html>
