<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('pickup_form');
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Request Pickup | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        :root {
            --primary: #007AFF;
            --primary-dark: #0056b3;
            --primary-light: rgba(0, 122, 255, 0.08);
            --text: #1C1C1E;
            --text-sub: #8E8E93;
            --bg: #F2F2F7;
            --card: #FFFFFF;
            --border: rgba(0, 0, 0, 0.06);
            --danger: #FF3B30;
            --success: #34C759;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Inter", sans-serif;
            background-color: #f1f3f9;
            background-image:
                linear-gradient(rgba(99, 102, 241, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.04) 1px, transparent 1px),
                radial-gradient(at 0% 0%, rgba(143, 0, 255, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 122, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(255, 45, 85, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 149, 0, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(52, 199, 89, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            background-size: 30px 30px, 30px 30px, cover, cover, cover, cover, cover;
            color: var(--text);
            line-height: 1.5;
            padding-bottom: 3rem;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            padding: 2.5rem 1.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .back-btn:active {
            transform: scale(0.9);
            background: rgba(255, 255, 255, 0.85);
        }

        .header h1 {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .form-container {
            padding: 1.25rem;
            max-width: 500px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 750;
            color: var(--text-sub);
            margin-bottom: 0.45rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .input-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 1rem;
            font-family: inherit;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.5);
            outline: none;
            transition: all 0.25s ease;
            color: var(--text);
        }

        .input-control:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
        }

        .btn-camera {
            width: 100%;
            padding: 1.1rem;
            background: rgba(255, 255, 255, 0.55);
            border: 1px dashed rgba(0, 122, 255, 0.4);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: var(--primary);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
        }

        .btn-camera:active {
            transform: scale(0.97);
            background: rgba(0, 122, 255, 0.08);
            border-color: var(--primary);
        }

        .btn-camera .material-symbols-outlined {
            font-size: 24px;
        }

        .file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .preview-item {
            aspect-ratio: 1;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border);
            position: relative;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item .remove-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 18px;
            height: 18px;
            background: rgba(255, 59, 48, 0.9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            border: none;
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 1.5rem;
            font-size: 0.95rem;
            font-weight: 800;
            margin-top: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 122, 255, 0.25);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .submit-btn:active {
            transform: scale(0.96);
            box-shadow: 0 5px 15px rgba(0, 122, 255, 0.15);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 1rem 1.5rem;
            background: #1C1C1E;
            color: white;
            border-radius: 1.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: none;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, 100%);
                opacity: 0;
            }

            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .rotating {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        #processingOverlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            gap: 1rem;
        }

        /* Searchable Dropdown Styles */
        .searchable-group {
            position: relative;
        }

        .searchable-input {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' height='20' viewBox='0 -960 960 960' width='20' fill='%238E8E93'%3E%3Cpath d='M480-345 240-585l56-56 184 184 184-184 56 56-240 240Z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .options-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 1.25rem;
            margin-top: 6px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            z-index: 1050;
            display: none;
        }

        .options-list.show {
            display: block;
        }

        .option-item {
            padding: 14px 16px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            color: var(--text);
        }

        .option-item:last-child {
            border-bottom: none;
        }

        .option-item:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .option-item.selected {
            background: rgba(0, 122, 255, 0.08);
            color: #007AFF;
            font-weight: 700;
        }

        .select-search {
            width: calc(100% - 24px);
            margin: 12px;
            padding: 10px 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 0.75rem;
            outline: none;
            font-family: inherit;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        /* Tab Styles */
        .tab-container {
            margin-bottom: 1.25rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .segmented-control {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 1.25rem;
            display: flex;
            padding: 4px;
            gap: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .tab-button {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-sub);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-button.active {
            background: var(--card);
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.08);
        }

        .tab-button:active {
            transform: scale(0.97);
        }

        .tab-button .material-symbols-outlined {
            font-size: 20px;
        }

        /* History styles */
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .history-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.25rem;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            animation: slideUp 0.3s ease;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .sj-label {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--primary);
        }

        .date-label {
            font-size: 0.72rem;
            color: var(--text-sub);
            font-weight: 600;
        }

        .status-badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.3rem 0.7rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid rgba(194, 65, 12, 0.15);
        }

        .badge-approved {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid rgba(4, 120, 87, 0.15);
        }

        .badge-transit {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid rgba(29, 78, 216, 0.15);
        }

        .badge-completed {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid rgba(21, 128, 61, 0.15);
        }

        .badge-rejected {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid rgba(185, 28, 28, 0.15);
        }

        .route-info {
            display: flex;
            gap: 1rem;
            position: relative;
            margin-bottom: 1rem;
        }

        .route-line {
            position: absolute;
            left: 7px;
            top: 12px;
            bottom: 12px;
            width: 2px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 1px;
        }

        .route-dots {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            z-index: 1;
            padding-top: 4px;
        }

        .dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
        }

        .dot.origin {
            background: var(--primary);
        }

        .dot.dest {
            background: var(--success);
        }

        .route-text {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
        }

        .step-label {
            font-size: 0.62rem;
            color: var(--text-sub);
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .step-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text);
        }

        .history-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-pill {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-sub);
            background: rgba(0, 0, 0, 0.03);
            padding: 4px 10px;
            border-radius: 8px;
        }

        .photo-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid rgba(0, 122, 255, 0.1);
            cursor: pointer;
            transition: all 0.2s;
        }

        .photo-btn:active {
            transform: scale(0.9);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-sub);
        }

        .empty-state .material-symbols-outlined {
            font-size: 54px;
            opacity: 0.4;
            margin-bottom: 0.75rem;
        }

        /* Modal Overlay for Images */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            animation: fadeIn 0.25s ease;
        }

        .modal.show {
            display: flex;
        }

        .modal img {
            max-width: 100%;
            max-height: 75vh;
            border-radius: 1.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .close-modal {
            position: absolute;
            top: 2.5rem;
            right: 1.5rem;
            color: white;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s;
        }

        .close-modal:active {
            transform: scale(0.9);
            background: rgba(255, 255, 255, 0.2);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <a href="mobile_home.php" class="back-btn">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1>Request Pickup Baru</h1>
    </header>

    <div class="form-container">
        <div class="tab-container">
            <div class="segmented-control">
                <button type="button" id="formTabBtn" class="tab-button active" onclick="switchTab('form')">
                    <span class="material-symbols-outlined">edit_note</span>
                    Form Input
                </button>
                <button type="button" id="historyTabBtn" class="tab-button" onclick="switchTab('history')">
                    <span class="material-symbols-outlined">history</span>
                    Riwayat Hari Ini
                </button>
            </div>
        </div>

        <div id="formTabContent" class="tab-content">
            <div class="card">
                <form id="pickupForm">
                    <div class="form-group">
                        <label>Rencana Tanggal Penjemputan <span style="color:var(--danger)">*</span></label>
                        <input type="date" name="scheduled_date" id="schedDate" class="input-control" required>
                    </div>

                    <div class="form-group">
                        <label>No. Surat Jalan <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="surat_jalan" id="sjInput" class="input-control"
                            placeholder="Masukkan nomor SJ" required>
                    </div>

                    <div class="form-group searchable-group">
                        <label>Lokasi Asal <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="originDisplay" class="input-control searchable-input"
                            placeholder="Cari lokasi asal..." readonly onclick="toggleOptions('originList')">
                        <div id="originList" class="options-list"></div>
                        <input type="hidden" name="origin_name" id="originSelect" required>
                    </div>

                    <div class="form-group searchable-group">
                        <label>Lokasi Tujuan (Toko/Store) <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="destDisplay" class="input-control searchable-input"
                            placeholder="Cari lokasi tujuan..." readonly onclick="toggleOptions('destList')">
                        <div id="destList" class="options-list"></div>
                        <input type="hidden" name="destination_name" id="destSelect" required>
                    </div>

                    <div class="form-group">
                        <label>Total Koli <span style="color:var(--danger)">*</span></label>
                        <input type="number" name="total_koli" class="input-control" value="1" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Catatan (Opsional)</label>
                        <textarea name="notes" class="input-control" style="min-height: 80px; resize: none;"
                            placeholder="Tambahkan catatan jika ada..."></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Foto Surat Jalan (SJ) <span style="color:var(--danger)">*</span></label>
                            <div class="btn-camera"
                                style="flex-direction: column; gap: 0.4rem; padding: 1rem 0.5rem; height: auto;">
                                <span class="material-symbols-outlined">add_a_photo</span>
                                <span style="font-size: 0.78rem;">Ambil Foto SJ</span>
                                <input type="file" id="sjFileInput" class="file-input" accept="image/*" multiple
                                    capture="environment">
                            </div>
                            <div id="sjPreviewContainer" class="preview-grid"
                                style="grid-template-columns: repeat(2, 1fr); gap: 0.35rem; margin-top: 0.5rem;"></div>
                        </div>

                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Foto Barang / Koli <span style="color:var(--danger)">*</span></label>
                            <div class="btn-camera"
                                style="flex-direction: column; gap: 0.4rem; padding: 1rem 0.5rem; height: auto;">
                                <span class="material-symbols-outlined">inventory_2</span>
                                <span style="font-size: 0.78rem;">Ambil Foto Barang</span>
                                <input type="file" id="goodsFileInput" class="file-input" accept="image/*" multiple
                                    capture="environment">
                            </div>
                            <div id="goodsPreviewContainer" class="preview-grid"
                                style="grid-template-columns: repeat(2, 1fr); gap: 0.35rem; margin-top: 0.5rem;"></div>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="submit-btn">
                        <span class="material-symbols-outlined">send</span>
                        Kirim Permintaan
                    </button>
                </form>
            </div>
        </div>

        <div id="historyTabContent" class="tab-content" style="display: none;">
            <div class="history-list" id="historyList">
                <div class="empty-state">
                    <span class="material-symbols-outlined">history_off</span>
                    <p>Belum ada riwayat input untuk hari ini.</p>
                </div>
            </div>
        </div>
        <footer
            style="text-align: center; margin: 2.5rem 0 1rem; font-size: 0.65rem; color: var(--text-sub); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.6;">
            Powered By Dhanielo-Marthinz | IMS @ 2026
        </footer>
    </div>

    <div id="processingOverlay">
        <span class="material-symbols-outlined rotating" style="font-size:48px; color:var(--primary);">autorenew</span>
        <p style="font-weight:700; color:var(--text);">Memberi Watermark Foto...</p>
    </div>

    <div id="toast" class="toast"></div>

    <div id="imageModal" class="modal" onclick="this.classList.remove('show')">
        <span class="material-symbols-outlined close-modal">close</span>
        <img id="modalImg" src="">
    </div>

    <script>
        const API_URL = 'api.php';
        const USERNAME = <?php echo json_encode($username); ?>;

        let sjFiles = [];
        let goodsFiles = [];

        function showToast(msg, color = '#1e293b') {
            const t = document.getElementById('toast');
            t.innerText = msg; t.style.background = color; t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 3000);
        }

        let locationData = [];

        async function loadLocations() {
            try {
                const res = await fetch(`${API_URL}?action=get_locations`);
                locationData = await res.json();

                renderLocationOptions('originList', 'originSelect', 'originDisplay');
                renderLocationOptions('destList', 'destSelect', 'destDisplay');
            } catch (err) { console.error(err); }
        }

        function renderLocationOptions(listId, hiddenInputId, displayId) {
            const list = document.getElementById(listId);
            list.innerHTML = `
                <input type="text" placeholder="Ketik untuk mencari..." class="select-search" onkeyup="filterOptions(this, '${listId}')" onclick="event.stopPropagation()">
            `;

            locationData.sort((a, b) => a.name.localeCompare(b.name)).forEach(loc => {
                const div = document.createElement('div');
                div.className = 'option-item';
                div.innerHTML = `<span>${loc.name}</span>`;
                div.onclick = (e) => {
                    e.stopPropagation();
                    selectLocation(loc, listId, hiddenInputId, displayId, div);
                };
                list.appendChild(div);
            });
        }

        function toggleOptions(listId) {
            document.querySelectorAll('.options-list').forEach(l => {
                if (l.id !== listId) l.classList.remove('show');
            });
            const list = document.getElementById(listId);
            list.classList.toggle('show');

            if (list.classList.contains('show')) {
                const searchInput = list.querySelector('.select-search');
                if (searchInput) {
                    setTimeout(() => {
                        searchInput.value = '';
                        searchInput.focus();
                        filterOptions(searchInput, listId);
                    }, 50);
                }
            }
        }

        function filterOptions(input, listId) {
            const filter = input.value.toLowerCase();
            const list = document.getElementById(listId);
            const items = list.querySelectorAll('.option-item');
            items.forEach(item => {
                const txt = item.innerText.toLowerCase();
                item.style.display = txt.includes(filter) ? 'flex' : 'none';
            });
        }

        function selectLocation(loc, listId, hiddenInputId, displayId, element) {
            document.getElementById(hiddenInputId).value = loc.name;
            document.getElementById(displayId).value = loc.name;
            document.getElementById(listId).classList.remove('show');

            const list = document.getElementById(listId);
            list.querySelectorAll('.option-item').forEach(item => item.classList.remove('selected'));
            element.classList.add('selected');
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.searchable-group')) {
                document.querySelectorAll('.options-list').forEach(l => l.classList.remove('show'));
            }
        });

        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        document.getElementById('schedDate').value = `${yyyy}-${mm}-${dd}`;

        // Handle SJ Photos
        document.getElementById('sjFileInput').addEventListener('change', async function (e) {
            await handleFileSelect(e.target.files, sjFiles, 'sjPreviewContainer');
            this.value = '';
        });

        // Handle Goods Photos
        document.getElementById('goodsFileInput').addEventListener('change', async function (e) {
            await handleFileSelect(e.target.files, goodsFiles, 'goodsPreviewContainer');
            this.value = '';
        });

        async function handleFileSelect(selectedFiles, targetArray, containerId) {
            const files = Array.from(selectedFiles);
            if (!files.length) return;

            for (const file of files) {
                targetArray.push(file);
            }
            renderPreviews(targetArray, containerId);
        }

        function renderPreviews(array, containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            array.forEach((blob, idx) => {
                const url = URL.createObjectURL(blob);
                const item = document.createElement('div');
                item.className = 'preview-item';
                item.innerHTML = `
                    <img src="${url}">
                    <button type="button" class="remove-btn" onclick="removeFile('${containerId}', ${idx})">×</button>
                `;
                container.appendChild(item);
            });
        }

        window.removeFile = function (containerId, idx) {
            if (containerId === 'sjPreviewContainer') {
                sjFiles.splice(idx, 1);
                renderPreviews(sjFiles, containerId);
            } else {
                goodsFiles.splice(idx, 1);
                renderPreviews(goodsFiles, containerId);
            }
        };

        async function addWatermark(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        const maxDim = 1280;
                        let w = img.width;
                        let h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) { h = (maxDim / w) * h; w = maxDim; }
                            else { w = (maxDim / h) * w; h = maxDim; }
                        }

                        canvas.width = w;
                        canvas.height = h;
                        ctx.drawImage(img, 0, 0, w, h);

                        const fontSize = Math.max(w, h) * 0.025;
                        ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                        ctx.shadowColor = 'rgba(0,0,0,0.6)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetX = 2;
                        ctx.shadowOffsetY = 2;

                        const sj = document.getElementById('sjInput').value || '';
                        const origin = document.getElementById('originSelect').value || '';
                        const dest = document.getElementById('destSelect').value || '';
                        const date = new Date().toLocaleString('id-ID');

                        const lines = [
                            `SJ: ${sj.trim() || '-'}`,
                            `ASAL: ${origin.trim() || '-'}`,
                            `TUJUAN: ${dest.trim() || '-'}`,
                            `USER: ${USERNAME}`,
                            `WAKTU: ${date}`
                        ];

                        const padding = fontSize;
                        let y = h - (lines.length * (fontSize * 1.4)) - padding;

                        ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
                        ctx.fillRect(0, y - fontSize, w, h - y + fontSize);

                        ctx.fillStyle = 'white';
                        lines.forEach(line => {
                            ctx.fillText(line, padding, y);
                            y += fontSize * 1.3;
                        });

                        canvas.toBlob((blob) => resolve(blob), 'image/webp', 0.82);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        function switchTab(tab) {
            const formBtn = document.getElementById('formTabBtn');
            const historyBtn = document.getElementById('historyTabBtn');
            const formContent = document.getElementById('formTabContent');
            const historyContent = document.getElementById('historyTabContent');

            if (tab === 'form') {
                formBtn.classList.add('active');
                historyBtn.classList.remove('active');
                formContent.style.display = 'block';
                historyContent.style.display = 'none';
            } else {
                formBtn.classList.remove('active');
                historyBtn.classList.add('active');
                formContent.style.display = 'none';
                historyContent.style.display = 'block';
                loadTodayHistory();
            }
        }

        async function loadTodayHistory() {
            const list = document.getElementById('historyList');
            list.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined rotating" style="font-size:32px; color:var(--primary);">autorenew</span>
                    <p style="margin-top:0.5rem; font-size:0.85rem;">Memuat data riwayat...</p>
                </div>
            `;
            try {
                const today = new Date().toLocaleDateString('en-CA');
                const res = await fetch(`${API_URL}?action=get_pickup_requests&my_requests_only=1&date_from=${today}&date_to=${today}`);
                const data = await res.json();
                renderHistoryList(data);
            } catch (err) {
                console.error(err);
                list.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined" style="color:var(--danger)">error</span>
                        <p style="color:var(--danger)">Gagal memuat data riwayat.</p>
                    </div>
                `;
            }
        }

        function parsePhoto(json) {
            try {
                const p = JSON.parse(json);
                return Array.isArray(p) ? p[0] : p;
            } catch (e) { return json; }
        }

        function getStatusCls(s) {
            if (s === 'pending') return 'badge-pending';
            if (s === 'approved') return 'badge-approved';
            if (s === 'completed') return 'badge-completed';
            if (s === 'rejected') return 'badge-rejected';
            return 'badge-transit';
        }

        function getStatusTxt(s) {
            if (s === 'pending') return 'Menunggu';
            if (s === 'approved') return 'Disetujui';
            if (s === 'completed') return 'Selesai';
            if (s === 'rejected') return 'Ditolak';
            return s.toUpperCase();
        }

        function showImg(url) {
            const m = document.getElementById('imageModal');
            document.getElementById('modalImg').src = url;
            m.classList.add('show');
        }

        function renderHistoryList(data) {
            const list = document.getElementById('historyList');
            if (!data || !data.length) {
                list.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">history_off</span>
                        <p>Belum ada riwayat input untuk hari ini.</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = data.map(item => {
                const statusCls = getStatusCls(item.status);
                const statusTxt = getStatusTxt(item.status);
                const dt = new Date(item.created_at);
                const dateStr = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                const timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                let notesHtml = '';
                if (item.notes && item.notes.trim() !== '') {
                    notesHtml = `
                        <div style="margin-top: 0.75rem; padding: 0.6rem 0.8rem; background: rgba(0,0,0,0.02); border-radius: 0.75rem; border-left: 3px solid var(--primary); font-size: 0.8rem; color: var(--text);">
                            <div style="font-weight:750; font-size: 0.65rem; color: var(--text-sub); text-transform: uppercase; margin-bottom: 2px;">Catatan</div>
                            <div>${item.notes}</div>
                        </div>
                    `;
                }

                return `
                    <div class="history-card">
                        <div class="card-header">
                            <div>
                                <div class="sj-label">${item.surat_jalan}</div>
                                <div class="date-label">${dateStr} • ${timeStr}</div>
                            </div>
                            <span class="status-badge ${statusCls}">${statusTxt}</span>
                        </div>
                        <div class="route-info">
                            <div class="route-line"></div>
                            <div class="route-dots"><div class="dot origin"></div><div class="dot dest"></div></div>
                            <div class="route-text">
                                <div class="step"><span class="step-label">DARI</span><span class="step-name">${item.origin_name}</span></div>
                                <div class="step"><span class="step-label">KE</span><span class="step-name">${item.destination_name}</span></div>
                            </div>
                        </div>
                        ${notesHtml}
                        <div class="history-card-footer">
                            <div style="display:flex; gap:8px;">
                                <div class="info-pill"><span class="material-symbols-outlined" style="font-size:16px;">package_2</span>${item.total_koli} Koli</div>
                            </div>
                            <div style="display:flex; gap:6px;">
                                ${item.surat_jalan_file ? `<button type="button" class="photo-btn" onclick="showImg('uploads/${parsePhoto(item.surat_jalan_file)}')"><span class="material-symbols-outlined" style="font-size:20px;">image</span></button>` : ''}
                                ${item.goods_file ? `<button type="button" class="photo-btn" onclick="showImg('uploads/${parsePhoto(item.goods_file)}')" style="background:#ecfdf5; color:#059669; border-color: rgba(5, 150, 105, 0.1);"><span class="material-symbols-outlined" style="font-size:20px;">inventory_2</span></button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        document.getElementById('pickupForm').onsubmit = async (e) => {
            e.preventDefault();

            if (!document.getElementById('originSelect').value) {
                showToast('Lokasi Asal wajib dipilih!', '#ef4444');
                return;
            }
            if (!document.getElementById('destSelect').value) {
                showToast('Lokasi Tujuan wajib dipilih!', '#ef4444');
                return;
            }

            if (sjFiles.length === 0) {
                showToast('Foto Surat Jalan wajib diisi!', '#ef4444');
                return;
            }
            if (goodsFiles.length === 0) {
                showToast('Foto Barang wajib diisi!', '#ef4444');
                return;
            }

            const btn = document.getElementById('submitBtn');
            const origHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined rotating">autorenew</span> Mengirim...';

            document.getElementById('processingOverlay').style.display = 'flex';

            try {
                const formData = new FormData(e.target);

                const watermarkedSjFiles = [];
                for (const file of sjFiles) {
                    const watermarkedBlob = await addWatermark(file);
                    watermarkedSjFiles.push(watermarkedBlob);
                }

                const watermarkedGoodsFiles = [];
                for (const file of goodsFiles) {
                    const watermarkedBlob = await addWatermark(file);
                    watermarkedGoodsFiles.push(watermarkedBlob);
                }

                watermarkedSjFiles.forEach((blob, i) => formData.append('surat_jalan_files[]', blob, `SJ_${i}.webp`));
                watermarkedGoodsFiles.forEach((blob, i) => formData.append('goods_files[]', blob, `GOODS_${i}.webp`));

                const res = await fetch(`${API_URL}?action=request_pickup`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                document.getElementById('processingOverlay').style.display = 'none';

                if (data.success) {
                    showToast('Permintaan berhasil dikirim!', '#10b981');

                    document.getElementById('pickupForm').reset();
                    sjFiles = [];
                    goodsFiles = [];
                    document.getElementById('sjPreviewContainer').innerHTML = '';
                    document.getElementById('goodsPreviewContainer').innerHTML = '';

                    document.getElementById('originDisplay').value = '';
                    document.getElementById('originSelect').value = '';
                    document.getElementById('destDisplay').value = '';
                    document.getElementById('destSelect').value = '';

                    const now = new Date();
                    const yyyy = now.getFullYear();
                    const mm = String(now.getMonth() + 1).padStart(2, '0');
                    const dd = String(now.getDate()).padStart(2, '0');
                    document.getElementById('schedDate').value = `${yyyy}-${mm}-${dd}`;

                    switchTab('history');

                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                } else {
                    showToast('Gagal: ' + (data.error || 'Terjadi kesalahan'), '#ef4444');
                    btn.disabled = false; btn.innerHTML = origHtml;
                }
            } catch (err) {
                document.getElementById('processingOverlay').style.display = 'none';
                showToast('Gagal terhubung ke server', '#ef4444');
                btn.disabled = false; btn.innerHTML = origHtml;
            }
        };

        loadLocations();
    </script>
</body>

</html>