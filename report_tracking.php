<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('report');
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Tracking | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --card: #ffffff;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --border: #e2e8f0;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: flex-end;
            margin-bottom: 1rem;
        }

        .filter-bar .form-group {
            margin: 0;
            flex: 1;
            min-width: 160px;
        }

        .filter-bar label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            display: block;
            margin-bottom: 4px;
        }

        .filter-bar input,
        .filter-bar select {
            width: 100%;
            padding: 0.55rem 0.65rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #f8fafc;
            color: var(--text);
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
            line-height: 1.2;
        }

        .filter-bar select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.6rem center;
            background-size: 12px;
            padding-right: 2rem;
            cursor: pointer;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .stat-val {
            font-size: 1.1rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-lbl {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .page-header h1 {
            font-size: 1.1rem !important;
        }

        .page-header p {
            font-size: 0.7rem !important;
        }

        .page-header {
            margin-bottom: 0.5rem !important;
            padding: 0.25rem 0 !important;
        }

        .page-title-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
        }

        .page-title-icon .material-symbols-outlined {
            font-size: 16px !important;
        }

        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
        }

        .pagination-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-btn {
            height: 34px;
            padding: 0 0.75rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-sub);
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .rows-per-page {
            height: 34px;
            padding: 0 0.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-sub);
            outline: none;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        thead th {
            background: var(--bg);
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: .65rem 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: .7rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: rgba(99, 102, 241, .03);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-transit {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-completed {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .sj-tag {
            font-weight: 700;
            color: var(--primary);
            font-family: monospace;
            font-size: .85rem;
        }

        .detail-btn {
            border: none;
            background: var(--primary-light, #eef2ff);
            color: var(--primary);
            border-radius: var(--radius);
            padding: 4px 10px;
            font-size: .75rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: .15s;
        }

        .detail-btn:hover {
            background: var(--primary);
            color: #fff;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
            padding: 1rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: var(--card);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 820px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* Header and Body will handle their own areas */
            box-shadow: 0 25px 60px rgba(0, 0, 0, .18);
            animation: modalIn .2s ease;
            position: relative;
        }

        .modal-box::-webkit-scrollbar {
            width: 6px;
        }

        .modal-box::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(.96)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .modal-head {
            padding: 1.75rem 1.75rem 1.25rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            border-bottom: none !important;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
            color: #ffffff !important;
            z-index: 1000 !important;
            border-top-left-radius: var(--radius-xl) !important;
            border-top-right-radius: var(--radius-xl) !important;
            flex-shrink: 0;
        }

        .modal-body {
            padding: 0 1.75rem 2.5rem;
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        .close-btn {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        /* Proof Photos Grid in Modal */
        .modal-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 1rem;
        }

        .modal-photo-thumb {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }

        .photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-photo {
            font-size: .72rem;
            color: var(--text-muted);
            font-style: italic;
        }

        /* ===== SHOPEE-STYLE VERTICAL ROUTE ===== */
        .route-viz {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            margin-top: 1.5rem;
            /* Gap with header */
            border: 1px solid var(--border);
        }

        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .route-status-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .route-status-transit {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .route-status-done {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .route-status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Vertical track layout */
        .v-track {
            display: flex;
            gap: 1rem;
        }

        .v-col-line {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 34px;
            flex-shrink: 0;
        }

        .v-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            z-index: 2;
        }

        .v-dot.done {
            background: rgba(16, 185, 129, 0.25);
            border: 2px solid #10b981;
            color: #34d399;
        }

        .v-dot.active {
            background: rgba(99, 102, 241, 0.25);
            border: 2px solid #6366f1;
            color: #818cf8;
        }

        .v-dot.pending {
            background: #f8fafc;
            border: 2px dashed var(--border);
            color: var(--text-muted);
        }

        .v-segment {
            width: 3px;
            flex: 1;
            min-height: 64px;
            background: #f1f5f9;
            border-radius: 2px;
            position: relative;
            overflow: visible;
        }

        .v-seg-fill {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 0%;
            background: linear-gradient(180deg, #6366f1, #10b981);
            border-radius: 2px;
            transition: height 1.5s ease;
        }

        .v-truck {
            position: absolute;
            left: 50%;
            transform: translateX(-60%);
            font-size: 20px;
            z-index: 10;
            filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.9));
            transition: top 1.5s ease;
            line-height: 1;
        }

        .v-truck.moving {
            animation: vTruckBounce 0.55s ease-in-out infinite alternate;
        }

        @keyframes vTruckBounce {
            from {
                transform: translateX(-60%) translateY(-3px) scale(1.08);
            }

            to {
                transform: translateX(-60%) translateY(3px) scale(0.93);
            }
        }

        .v-col-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .v-step {
            display: flex;
            flex-direction: column;
            padding-top: 4px;
        }

        .v-step-label {
            font-size: 0.62rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .v-step-name {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text);
            margin-top: 2px;
            line-height: 1.25;
        }

        .v-step-time {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .v-step-gap {
            flex: 1;
            min-height: 64px;
        }

        /* Receiver / proof card */
        .receiver-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);
            border: 1px solid #d1fae5;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-top: 1.25rem;
            color: var(--text);
        }

        .receiver-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dcfce7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            font-size: 20px;
            flex-shrink: 0;
        }

        .proof-thumb {
            width: 72px;
            height: 72px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(99, 102, 241, 0.4);
            cursor: pointer;
            transition: 0.2s;
        }

        .proof-thumb:hover {
            transform: scale(1.06);
            border-color: #6366f1;
        }

        /* Lightbox */
        #lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #lightbox.show {
            display: flex;
        }

        #lightbox img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
        }

        #lightbox-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            background: #e2e8f0;
            padding: 0.3rem;
            border-radius: 10px;
            width: fit-content;
        }

        .tab-link {
            padding: 0.4rem 1.2rem;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-muted);
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            font-family: inherit;
        }

        .tab-link.active {
            background: var(--surface);
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .filter-section {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.6rem 0.85rem;
            margin-bottom: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .filter-label {
            font-size: 0.6rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-input {
            padding: 0.35rem 0.55rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            background: #f8fafc;
            min-width: 120px;
            color: #1e293b;
        }

        .btn-toggle-date {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            height: 38px;
        }

        .btn-toggle-date.active {
            background: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary);
        }

        .data-badge {
            background: #eef2ff;
            color: #6366f1;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }

        .btn-excel-green {
            background: #16a34a;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 0.8rem;
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.2);
        }

        .btn-excel-green:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        @media(max-width:700px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-bar .form-group {
                min-width: 130px;
            }
        }

        /* 10px Font Size for Report Table */
        #reportTable tbody td,
        #reportTable tbody td div,
        #reportTable tbody td span,
        #reportTable tbody td strong,
        #reportTable tbody td a,
        #expTable tbody td,
        #expTable tbody td div,
        #expTable tbody td span,
        #expTable tbody td strong {
            font-size: 10px !important;
        }

        #reportTable thead th,
        #expTable thead th {
            font-size: 10px !important;
        }

        .badge {
            font-size: 9px !important;
            padding: 2px 8px !important;
        }

        .sj-tag {
            font-size: 10px !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon"><span class="material-symbols-outlined">receipt_long</span></div>
                <div>
                    <h1>Laporan Tracking Driver</h1>
                    <p>Monitor semua riwayat perjalanan driver Head Office</p>
                </div>
            </div>
        </div>

        <!-- TABS Removed -->

        <!-- TAB CONTENT: WH OPERATION -->
        <div id="wh_op" class="tab-content active">
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eef2ff;color:#6366f1"><span
                            class="material-symbols-outlined">receipt_long</span></div>
                    <div>
                        <div class="stat-val" id="statTotal">0</div>
                        <div class="stat-lbl">Total Tugas</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:#d97706"><span
                            class="material-symbols-outlined">pending_actions</span></div>
                    <div>
                        <div class="stat-val" id="statPending">0</div>
                        <div class="stat-lbl">Belum Berangkat</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5;color:#059669"><span
                            class="material-symbols-outlined">local_shipping</span></div>
                    <div>
                        <div class="stat-val" id="statTransit">0</div>
                        <div class="stat-lbl">Dalam Perjalanan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e0e7ff;color:#4338ca"><span
                            class="material-symbols-outlined">check_circle</span></div>
                    <div>
                        <div class="stat-val" id="statDone">0</div>
                        <div class="stat-lbl">Selesai</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom:1.25rem; padding:1rem 1.25rem;">
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" id="fDateFrom" onchange="loadReport()" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="fDateTo" onchange="loadReport()" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="position:relative; flex: 1.5; min-width: 220px;">
                        <label style="font-weight:700;">Smart Search</label>
                        <div style="position:relative;">
                            <span class="material-symbols-outlined" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--primary);">search</span>
                            <input type="text" id="fSJ" placeholder="Search..." oninput="onReportSmartSearchInput()" style="padding-left:34px; padding-right:30px; width:100%; border-radius:8px; border:1px solid var(--border); outline:none;">
                            <span id="clearReportSearch" onclick="clearReportSearch()" class="material-symbols-outlined" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--text-muted); cursor:pointer; display:none;">close</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="fStatus" onchange="loadReport()">
                            <option value="">Semua Status</option>
                            <option value="pending">Belum Berangkat</option>
                            <option value="in_transit">Dalam Perjalanan</option>
                            <option value="completed">Selesai</option>
                            <option value="canceled">Cancel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Layanan</label>
                        <select id="fTaskType" onchange="loadReport()">
                            <option value="">Semua Layanan</option>
                            <option value="antar">Antar</option>
                            <option value="jemput">Jemput</option>
                        </select>
                    </div>
                    <button class="btn-excel-green" onclick="exportExcel()" title="Export to Excel"
                        style="height: 36px; width: 42px; justify-content: center; padding: 0;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">table_view</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="table-wrapper">
                    <table id="reportTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Layanan</th>
                                <th>Rute</th>
                                <th>Spidometer & Jarak</th>
                                <th>Driver</th>
                                <th>Status</th>
                                <th>Catatan Driver</th>
                                <th>Created</th>
                                <th style="text-align:center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportBody">
                            <tr>
                                <td colspan="11" style="text-align:center;padding:2rem;color:var(--text-muted)">
                                    <span class="material-symbols-outlined"
                                        style="font-size:36px;display:block;margin-bottom:.5rem;opacity:.4">autorenew</span>
                                    Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for WH OP -->
                <div class="pagination-bar" style="justify-content: flex-end;">
                    <div class="pagination-controls">
                        <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted);">Rows:</label>
                        <select class="rows-per-page" id="whRowsPerPage" onchange="changeRowsPerPage('wh')">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <button class="pagination-btn" id="whPrev" onclick="changePage('wh', -1)">
                            <span class="material-symbols-outlined" style="font-size:18px">chevron_left</span> Prev
                        </button>
                        <button class="pagination-btn" id="whNext" onclick="changePage('wh', 1)">
                            Next <span class="material-symbols-outlined" style="font-size:18px">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: EXPEDISI -->
        <div id="expedisi" class="tab-content">
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eef2ff;color:#6366f1"><span
                            class="material-symbols-outlined">receipt_long</span></div>
                    <div>
                        <div class="stat-val" id="expStatTotal">0</div>
                        <div class="stat-lbl">Total SJ</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:#d97706"><span
                            class="material-symbols-outlined">pending_actions</span></div>
                    <div>
                        <div class="stat-val" id="expStatPending">0</div>
                        <div class="stat-lbl">Belum Berangkat</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5;color:#059669"><span
                            class="material-symbols-outlined">local_shipping</span></div>
                    <div>
                        <div class="stat-val" id="expStatTransit">0</div>
                        <div class="stat-lbl">Dalam Perjalanan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e0e7ff;color:#4338ca"><span
                            class="material-symbols-outlined">check_circle</span></div>
                    <div>
                        <div class="stat-val" id="expStatDone">0</div>
                        <div class="stat-lbl">Selesai</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom:1.25rem; padding:1rem 1.25rem;">
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" id="expStart" onchange="loadExpedisiReport()"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="expEnd" onchange="loadExpedisiReport()"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>No. Surat Jalan</label>
                        <input type="text" id="expSJ" placeholder="Cari SJ..." onkeyup="loadExpedisiReport()">
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <select id="expVendor" onchange="loadExpedisiReport()">
                            <option value="">Semua Vendor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="expStatus" onchange="loadExpedisiReport()">
                            <option value="">Semua Status</option>
                            <option value="pending">Belum Berangkat</option>
                            <option value="in_transit">Dalam Perjalanan</option>
                            <option value="completed">Selesai</option>
                            <option value="canceled">Cancel</option>
                        </select>
                    </div>
                    <button class="btn-excel-green" onclick="exportExpedisi()" title="Export to Excel"
                        style="height: 36px; width: 42px; justify-content: center; padding: 0;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">table_view</span>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="table-wrapper">
                    <table id="expTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal/Waktu</th>
                                <th>No. Surat Jalan</th>
                                <th>Tujuan</th>
                                <th>Vendor</th>
                                <th>Koli</th>
                                <th>No. Resi</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th style="text-align:center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="expBody">
                            <tr>
                                <td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted)">
                                    <span class="material-symbols-outlined"
                                        style="font-size:36px;display:block;margin-bottom:.5rem;opacity:.4">autorenew</span>
                                    Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Expedisi -->
                <div class="pagination-bar" style="justify-content: flex-end;">
                    <div class="pagination-controls">
                        <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted);">Rows:</label>
                        <select class="rows-per-page" id="expRowsPerPage" onchange="changeRowsPerPage('exp')">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <button class="pagination-btn" id="expPrev" onclick="changePage('exp', -1)">
                            <span class="material-symbols-outlined" style="font-size:18px">chevron_left</span> Prev
                        </button>
                        <button class="pagination-btn" id="expNext" onclick="changePage('exp', 1)">
                            Next <span class="material-symbols-outlined" style="font-size:18px">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div id="detailModal" class="modal-overlay" style="display: none;">
            <div class="modal-box">
                <div class="modal-head">
                    <div>
                        <div style="font-size:1.05rem;font-weight:800;letter-spacing:-0.02em;color:white !important;"
                            id="modalSJ">—</div>
                        <div style="font-size:.85rem;color:rgba(255,255,255,0.7) !important;font-weight:500;margin-top:2px;"
                            id="modalRoute">—</div>
                    </div>
                    <button class="close-btn" onclick="closeModal()"><span
                            class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal-body">
                    <!-- Animated Shopee-Style Route Visualization -->
                    <div id="routeViz" class="route-viz" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- Map Modal -->
        <div id="mapModal" class="modal-overlay" style="display: none; z-index: 10000;">
            <div class="modal-box" style="max-width: 800px; width: 90%;">
                <div class="modal-head" style="background: var(--primary);">
                    <div>
                        <div style="font-size:1.1rem;font-weight:800;color:white !important;">Live Tracking Driver</div>
                        <div style="font-size:.8rem;color:rgba(255,255,255,0.8) !important;" id="mapModalSub">—</div>
                    </div>
                    <button class="close-btn" onclick="closeMapModal()"><span
                            class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal-body" style="padding: 0; position: relative;">
                    <div id="liveMap" style="height: 500px; width: 100%;"></div>
                    <div id="mapLoader"
                        style="position: absolute; inset: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                        <div style="text-align: center;">
                            <span class="material-symbols-outlined"
                                style="font-size: 40px; color: var(--primary); animation: spin 1s linear infinite;">autorenew</span>
                            <p style="margin-top: 10px; font-weight: 700; color: var(--text-sub);">Menghubungkan ke GPS
                                Driver...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Lightbox -->
        <div id="lightbox" style="display: none;"><span id="lightbox-close" onclick="closeLightbox()">✕</span><img
                id="lightbox-img" src="">
        </div>

        <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
        <script>
            const API = 'api.php';
            let allData = [];
            let expData = [];

            let whPage = 1;
            let whRows = 25;
            let expPage = 1;
            let expRows = 25;

            const serverToday = "<?php echo date('Y-m-d'); ?>";
            const serverAgo30 = "<?php echo date('Y-m-d', strtotime('-30 days')); ?>";

            document.getElementById('fDateFrom').value = serverToday;
            document.getElementById('fDateTo').value = serverToday;

            let currentTab = 'wh_op';
            let expAllDate = false;

            function switchTab(tab, e) {
                currentTab = tab;
                document.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                e.currentTarget.classList.add('active');
                document.getElementById(tab).classList.add('active');

                if (tab === 'wh_op') loadReport();
                else loadExpedisiReport();
            }

            let rawReportData = [];
            let reportSearchTimer = null;

            function applySmartSearchFilter(data) {
                const q = (document.getElementById('fSJ')?.value || '').trim().toLowerCase();
                const taskType = document.getElementById('fTaskType')?.value || '';
                const clearBtn = document.getElementById('clearReportSearch');
                if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

                let filtered = data;
                if (taskType) {
                    filtered = filtered.filter(r => r.task_type === taskType);
                }

                if (!q) return filtered;

                const tokens = q.split(/\s+/).filter(Boolean);

                return filtered.filter(r => {
                    const typeText = r.task_type === 'antar' ? 'antar delivery' : 'jemput pickup';
                    const statusText = (r.status === 'completed' ? 'selesai completed' : (r.status === 'in_transit' ? 'dalam perjalanan transit' : (r.status === 'canceled' ? 'cancel batal' : 'pending belum berangkat')));

                    const searchableString = [
                        r.driver_name,
                        r.vehicle_plate,
                        r.receiver_name,
                        r.origin_name,
                        r.destination_name,
                        r.notes,
                        r.driver_notes,
                        r.late_reason,
                        r.requester_name,
                        r.assigned_by,
                        r.speedometer_start_num,
                        r.speedometer_end_num,
                        r.target_date,
                        r.assign_time,
                        typeText,
                        statusText
                    ].filter(Boolean).join(' ').toLowerCase();

                    return tokens.every(token => searchableString.includes(token));
                });
            }

            function onReportSmartSearchInput() {
                if (rawReportData && rawReportData.length) {
                    allData = applySmartSearchFilter(rawReportData);
                    whPage = 1;
                    renderTable(allData);
                    updateStats(allData);
                }
                clearTimeout(reportSearchTimer);
                reportSearchTimer = setTimeout(() => {
                    loadReport();
                }, 400);
            }

            function clearReportSearch() {
                const el = document.getElementById('fSJ');
                if (el) el.value = '';
                onReportSmartSearchInput();
            }

            async function loadReport() {
                const from = document.getElementById('fDateFrom').value;
                const to = document.getElementById('fDateTo').value;
                const sj = document.getElementById('fSJ').value;
                const status = document.getElementById('fStatus').value;
                const taskType = document.getElementById('fTaskType')?.value || '';
                const tbody = document.getElementById('reportBody');
                if (!rawReportData.length) {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem"><span class="material-symbols-outlined" style="font-size:36px;display:block;margin-bottom:.5rem;opacity:.4;animation:spin 1s linear infinite">autorenew</span>Memuat...</td></tr>';
                }

                let url = `${API}?action=get_tracking_report&date_from=${from}&date_to=${to}`;
                if (sj) url += `&surat_jalan=${encodeURIComponent(sj)}`;
                if (status) url += `&status=${status}`;
                if (taskType) url += `&task_type=${encodeURIComponent(taskType)}`;

                try {
                    const res = await fetch(url);
                    const fetchedData = await res.json();
                    rawReportData = Array.isArray(fetchedData) ? fetchedData : [];
                    whPage = 1;
                    allData = applySmartSearchFilter(rawReportData);
                    renderTable(allData);
                    updateStats(allData);
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--danger)">Gagal memuat data: ${e.message}</td></tr>`;
                }
            }

            function renderTable(data) {
                const tbody = document.getElementById('reportBody');
                const total = data.length;

                // Pagination Logic
                const start = (whPage - 1) * whRows;
                const end = start + whRows;
                const pagedData = data.slice(start, end);

                document.getElementById('whPrev').disabled = whPage <= 1;
                document.getElementById('whNext').disabled = end >= total;

                if (!pagedData.length) {
                    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:2.5rem;color:var(--text-muted)"><span class="material-symbols-outlined" style="font-size:36px;display:block;margin-bottom:.5rem;opacity:.4">inbox</span>Tidak ada data ditemukan</td></tr>';
                    return;
                }
                const statusMap = { pending: 'badge-pending', in_transit: 'badge-transit', completed: 'badge-completed', canceled: 'badge-danger' };
                const statusLabel = { pending: 'Belum Berangkat', in_transit: 'Dalam Perjalanan', completed: 'Selesai', canceled: 'Cancel' };

                tbody.innerHTML = pagedData.map((r, i) => {
                    const absoluteIndex = start + i;
                    const kmStart = r.speedometer_start_num ? Number(r.speedometer_start_num).toLocaleString('id-ID') + ' KM' : '—';
                    const kmEnd = r.speedometer_end_num ? Number(r.speedometer_end_num).toLocaleString('id-ID') + ' KM' : '—';
                    const totalKm = (r.speedometer_start_num && r.speedometer_end_num) ? Number(r.speedometer_end_num - r.speedometer_start_num).toLocaleString('id-ID') + ' KM' : '—';

                    return `
                    <tr>
                        <td style="color:var(--text-muted);font-size:.72rem;font-weight:700">${absoluteIndex + 1}</td>
                        <td style="white-space:nowrap; font-weight:700; font-size:0.8rem; color:var(--text);">
                            ${fmtDate(r.target_date || r.assign_time)}
                        </td>
                        <td style="font-size:0.75rem; line-height:1.35; white-space:nowrap;">
                            <span class="badge ${r.task_type === 'antar' ? 'badge-completed' : 'badge-transit'}" style="font-size:0.65rem; margin-bottom:4px; display:inline-block;">
                                ${r.task_type === 'antar' ? 'ANTAR' : 'JEMPUT'}
                            </span>
                            <div style="font-size:0.7rem; color:var(--text-sub);">
                                Start: ${r.start_time ? new Date(r.start_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'} <br>
                                End: ${r.end_time ? new Date(r.end_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'}
                            </div>
                            ${(r.start_time && r.end_time) || r.duration ? `<div style="font-size:0.7rem; font-weight:700; color:var(--primary); margin-top:2px;">Durasi: ${formatDurationTime(r.start_time, r.end_time, r.duration)}</div>` : ''}
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:.82rem">${r.origin_name}</div>
                            <div style="color:var(--text-muted);font-size:.75rem">&raquo; ${r.destination_name}</div>
                            <div style="font-size:0.72rem; font-weight:700; color:var(--primary); margin-top:3px; display:flex; align-items:center; gap:3px;">
                                <span class="material-symbols-outlined" style="font-size:14px;">person</span> ${r.receiver_name ? r.receiver_name : '<span style="color:var(--text-muted); font-weight:normal;">—</span>'}
                            </div>
                        </td>
                        <td style="font-size:0.75rem; line-height:1.4; white-space:nowrap;">
                            <div><strong>Awal:</strong> ${kmStart}</div>
                            <div><strong>Akhir:</strong> ${kmEnd}</div>
                            ${(r.speedometer_start_num && r.speedometer_end_num) ? `<div style="font-weight:800; color:var(--primary); margin-top:2px;">Jarak: ${totalKm}</div>` : ''}
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:.82rem">${r.driver_name}</div>
                            ${r.vehicle_plate ? `<div style="color:var(--text-muted);font-size:.75rem">${r.vehicle_plate}</div>` : ''}
                        </td>
                        <td><span class="badge ${statusMap[r.status] || 'badge-pending'}">${statusLabel[r.status] || r.status}</span></td>
                        <td style="font-size:.78rem;">
                            <div style="word-wrap: break-word; min-width: 120px; display:flex; flex-direction:column; gap:3px;">
                                ${r.notes ? `<div style="color:var(--text); font-weight:600;"><span style="color:#6366f1; font-weight:700;">Admin:</span> "${r.notes}"</div>` : ''}
                                ${r.driver_notes ? `<div style="color:var(--success); font-weight:600;"><span style="color:#10b981; font-weight:700;">Driver:</span> "${r.driver_notes}"</div>` : ''}
                                ${r.late_reason ? `<div style="color:var(--danger); font-weight:600;"><span style="color:#ef4444; font-weight:700;">Kendala:</span> "${r.late_reason}"</div>` : ''}
                                ${(!r.notes && !r.driver_notes && !r.late_reason) ? '<span style="color:var(--text-muted);">—</span>' : ''}
                            </div>
                        </td>
                        <td style="font-size:.82rem">${r.requester_name || r.assigned_by || '—'}</td>
                        <td>
                            <div style="display: flex; gap: 4px; justify-content: center;">
                                <button class="detail-btn" onclick="openDetail(${absoluteIndex})" title="Detail" style="padding: 6px;">
                                    <span class="material-symbols-outlined" style="font-size:18px">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            function changePage(type, delta) {
                if (type === 'wh') {
                    whPage += delta;
                    renderTable(allData);
                } else {
                    expPage += delta;
                    renderExpTable(expData);
                }
            }

            function changeRowsPerPage(type) {
                if (type === 'wh') {
                    whRows = parseInt(document.getElementById('whRowsPerPage').value);
                    whPage = 1;
                    renderTable(allData);
                } else {
                    expRows = parseInt(document.getElementById('expRowsPerPage').value);
                    expPage = 1;
                    renderExpTable(expData);
                }
            }

            function updateStats(data) {
                document.getElementById('statTotal').textContent = data.length;
                document.getElementById('statPending').textContent = data.filter(r => r.status === 'pending').length;
                document.getElementById('statTransit').textContent = data.filter(r => r.status === 'in_transit').length;
                document.getElementById('statDone').textContent = data.filter(r => r.status === 'completed').length;
            }

            function openDetail(idx) {
                const r = (allData && allData[idx]) ? allData[idx] : null;
                if (!r) return;

                let podPhotos = [];
                try {
                    if (r.proof_files_raw) {
                        podPhotos = typeof r.proof_files_raw === 'string' ? JSON.parse(r.proof_files_raw) : r.proof_files_raw;
                        if (!Array.isArray(podPhotos)) podPhotos = [];
                    }
                } catch(e) { podPhotos = []; }

                const modalSjEl = document.getElementById('modalSJ');
                if (modalSjEl) modalSjEl.textContent = `${r.task_type === 'antar' ? 'Antar' : 'Jemput'}`;

                const modalRouteEl = document.getElementById('modalRoute');
                if (modalRouteEl) modalRouteEl.textContent = `${r.origin_name || ''} → ${r.destination_name || ''}`;

                const routeViz = document.getElementById('routeViz');
                if (!routeViz) return;
                const isTransit = r.status === 'in_transit';
                const isDone = r.status === 'completed';
                const isPending = r.status === 'pending';
                const isCanceled = r.status === 'canceled';

                if (isTransit || isDone || isPending || isCanceled) {
                    routeViz.style.display = 'block';

                    let statusBadge = '';
                    if (isPending) statusBadge = `<span class="route-status-badge route-status-pending">Belum Berangkat</span>`;
                    else if (isTransit) statusBadge = `<span class="route-status-badge route-status-transit">🚛 Dalam Perjalanan</span>`;
                    else if (isDone) statusBadge = `<span class="route-status-badge route-status-done">✓ Terkirim</span>`;
                    else if (isCanceled) statusBadge = `<span class="route-status-badge" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);">✕ Canceled</span>`;

                    routeViz.innerHTML = `
                        <div class="route-header">
                            <div>
                                <div style="color:var(--text-muted); font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Progress Status</div>
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="v-track">
                            <div class="v-col-line">
                                <!-- Step 1: Penugasan -->
                                <div class="v-dot done"><span class="material-symbols-outlined" style="font-size:16px;">add_task</span></div>
                                <div class="v-segment"><div class="v-seg-fill" style="height:100%"></div></div>

                                <!-- Step 2: Driver Assigned -->
                                <div class="v-dot done"><span class="material-symbols-outlined" style="font-size:16px;">assignment_ind</span></div>
                                <div class="v-segment"><div class="v-seg-fill" style="height:100%"></div></div>
                                
                                <!-- Step 3: Departure -->
                                <div class="v-dot ${r.start_time ? 'done' : 'active'}"><span class="material-symbols-outlined" style="font-size:16px;">directions_car</span></div>
                                <div class="v-segment" id="segDeparture">
                                    <div class="v-seg-fill" id="fillDeparture"></div>
                                    ${(isTransit || (isPending && r.assign_time)) ? `<span class="v-truck moving" id="truckMain" style="top:-8px;">🚗</span>` : ''}
                                </div>
                                
                                <!-- Step 4: Destination -->
                                <div class="v-dot ${isDone ? 'done' : (isTransit ? 'active' : 'pending')}">
                                    <span class="material-symbols-outlined" style="font-size:16px;">${isDone ? 'task_alt' : (isCanceled ? 'cancel' : 'location_on')}</span>
                                </div>
                            </div>
                            <div class="v-col-content">
                                <!-- Step 1 Info -->
                                <div class="v-step">
                                    <div class="v-step-label">Penugasan Dibuat</div>
                                    <div class="v-step-name">${r.requester_name || r.assigned_by || 'Admin'}</div>
                                    <div class="v-step-time">
                                        🕒 Waktu: ${fmtDatetime(r.request_time || r.assign_time)}
                                        ${r.receiver_name ? `<br>👤 Penumpang: <strong>${r.receiver_name}</strong>` : ''}
                                    </div>
                                </div>

                                <div class="v-step-gap" style="min-height:30px;"></div>

                                <!-- Step 2 Info -->
                                <div class="v-step">
                                    <div class="v-step-label">Driver Ditugaskan</div>
                                    <div class="v-step-name">${r.driver_name}</div>
                                    <div class="v-step-time">
                                        <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">directions_car</span> ${r.vehicle_name || ''} (${r.vehicle_plate || '—'})<br>
                                        <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">person</span> Oleh: ${r.assigned_by || '—'}<br>
                                        <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">schedule</span> ${fmtDatetime(r.assign_time)}
                                    </div>
                                </div>
                                
                                <div class="v-step-gap"></div>
                                
                                <!-- Step 3 Departure Info -->
                                <div class="v-step">
                                    <div class="v-step-label">Lokasi Asal (Keberangkatan)</div>
                                    <div class="v-step-name">${r.origin_name}</div>
                                    <div class="v-step-time">
                                        ${r.start_time ? `🕒 Berangkat: ${fmtDatetime(r.start_time)}` : '<span style="color:rgba(245,158,11,0.6);">Menunggu keberangkatan driver...</span>'}
                                        ${r.speedometer_start_num ? `<br>⏱️ Spidometer Awal: <strong>${Number(r.speedometer_start_num).toLocaleString('id-ID')} KM</strong>` : ''}
                                    </div>
                                </div>
                                
                                <div class="v-step-gap"></div>
                                
                                <!-- Step 4 Destination Info -->
                                <div class="v-step" style="padding-bottom:10px;">
                                    <div class="v-step-label" style="display:flex; align-items:center; gap:8px;">
                                        Lokasi Tujuan: ${isCanceled ? 'Tugas Dibatalkan' : (isDone ? 'Tiba di Lokasi' : 'Dalam Perjalanan')}
                                        ${(r.status === 'pending' || r.status === 'in_transit') && r.driver_id ? `
                                            <button onclick="openLiveMap(event, ${r.driver_id}, '${r.driver_name}')" 
                                                style="border:none; background:var(--primary-light); color:var(--primary); width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; z-index:100;" title="Lihat Map Live">
                                                <span class="material-symbols-outlined" style="font-size:16px;">location_on</span>
                                            </button>
                                        ` : ''}
                                    </div>
                                    <div class="v-step-name">${r.destination_name}</div>
                                    <div class="v-step-time">
                                        ${isDone ? `✅ Selesai: ${fmtDatetime(r.end_time)}` : (isCanceled ? `❌ Cancel: ${fmtDatetime(r.end_time)}` : '<span style="color:var(--text-muted); opacity:0.6;">Menuju lokasi tujuan...</span>')}
                                        ${r.speedometer_end_num ? `<br>⏱️ Spidometer Akhir: <strong>${Number(r.speedometer_end_num).toLocaleString('id-ID')} KM</strong>` : ''}
                                        ${(r.speedometer_start_num && r.speedometer_end_num) ? `<br>📊 Total Jarak Perjalanan: <strong style="color:var(--primary);">${Number(r.speedometer_end_num - r.speedometer_start_num).toLocaleString('id-ID')} KM</strong>` : ''}
                                    </div>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${isDone ? `
                            <div class="receiver-card">
                                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                                    <div class="receiver-avatar"><span class="material-symbols-outlined">how_to_reg</span></div>
                                    <div>
                                        <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Nama Penumpang</div>
                                        <div style="font-size:1.05rem; font-weight:800; color:var(--text); margin-top:2px;">${r.receiver_name || '—'}</div>
                                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">Durasi: ${r.duration || '—'}</div>
                                    </div>
                                </div>
                                ${podPhotos.length ? `
                                    <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem;">📷 Bukti Foto Diterima (${podPhotos.length})</div>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        ${podPhotos.map(f => `<img src="uploads/${f}" class="proof-thumb" onclick="openLightbox('uploads/${f}')">`).join('')}
                                    </div>
                                ` : ''}
                                ${r.late_reason ? `<div style="margin-top:10px; font-size:0.75rem; color:#fbbf24;"><b>Alasan Terlambat:</b> ${r.late_reason}</div>` : ''}
                            </div>
                        ` : ''}
                        
                        ${isCanceled ? `
                            <div class="receiver-card" style="background:rgba(239,68,68,0.05); border-color:rgba(239,68,68,0.2);">
                                <div style="font-size:0.7rem; color:#ef4444; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Alasan Pembatalan</div>
                                <div style="color:var(--text); font-size:0.9rem; font-weight:600;">${r.driver_notes || 'Tidak ada keterangan'}</div>
                                ${podPhotos.length ? `
                                    <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin: 12px 0 8px;">📷 Foto Laporan Driver (${podPhotos.length})</div>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        ${podPhotos.map(f => `<img src="uploads/${f}" class="proof-thumb" onclick="openLightbox('uploads/${f}')">`).join('')}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    `;

                    // Animation
                    setTimeout(() => {
                        const f = document.getElementById('fillDeparture');
                        const t = document.getElementById('truckMain');
                        if (f) f.style.height = isDone ? '100%' : (isTransit ? '50%' : '0%');
                        if (t && isTransit) t.style.top = '42%';
                    }, 100);
                } else {
                    routeViz.style.display = 'none';
                }

                const modal = document.getElementById('detailModal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
            }

            function closeModal() {
                const modal = document.getElementById('detailModal');
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 300);
            }
            document.getElementById('detailModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

            function openLightbox(src) {
                const lb = document.getElementById('lightbox');
                document.getElementById('lightbox-img').src = src;
                lb.style.display = 'flex';
                setTimeout(() => lb.classList.add('show'), 10);
            }
            function closeLightbox() {
                const lb = document.getElementById('lightbox');
                lb.classList.remove('show');
                setTimeout(() => lb.style.display = 'none', 300);
            }
            document.getElementById('lightbox').addEventListener('click', e => { if (e.target === e.currentTarget) closeLightbox(); });

            function fmtDate(d) {
                if (!d) return '—';
                return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            function fmtDatetime(d) {
                if (!d) return '-';
                const dt = new Date(d);
                if (isNaN(dt)) return '-';
                return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                       dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
            }
            function formatDurationTime(startStr, endStr, durationStr) {
                if (startStr && endStr) {
                    const s = new Date(startStr);
                    const e = new Date(endStr);
                    if (!isNaN(s) && !isNaN(e)) {
                        const diffMs = Math.max(0, e - s);
                        const totalSec = Math.floor(diffMs / 1000);
                        const hours = String(Math.floor(totalSec / 3600)).padStart(2, '0');
                        const minutes = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
                        const seconds = String(totalSec % 60).padStart(2, '0');
                        return `${hours}:${minutes}:${seconds}`;
                    }
                }
                if (durationStr) {
                    const hMatch = durationStr.match(/(\d+)\s*Jam/i);
                    const mMatch = durationStr.match(/(\d+)\s*Menit/i);
                    const sMatch = durationStr.match(/(\d+)\s*Detik/i);
                    if (hMatch || mMatch || sMatch) {
                        const h = hMatch ? String(hMatch[1]).padStart(2, '0') : '00';
                        const m = mMatch ? String(mMatch[1]).padStart(2, '0') : '00';
                        const s = sMatch ? String(sMatch[1]).padStart(2, '0') : '00';
                        return `${h}:${m}:${s}`;
                    }
                    return durationStr;
                }
                return '-';
            }

            function makeExcelHyperlink(fileProp) {
                if (!fileProp) return '-';
                let files = [];
                try {
                    if (typeof fileProp === 'string') {
                        let str = fileProp.trim();
                        if (str.startsWith('[')) {
                            files = JSON.parse(str);
                        } else if (str.includes(',')) {
                            files = str.split(',').map(s => s.trim());
                        } else {
                            files = [str];
                        }
                    } else if (Array.isArray(fileProp)) {
                        files = fileProp;
                    } else {
                        files = [fileProp];
                    }
                } catch(e) {
                    files = [fileProp];
                }

                if (!Array.isArray(files)) files = [files];
                const validFiles = files
                    .map(f => typeof f === 'string' ? f.trim() : f)
                    .filter(f => f && f !== '-' && f !== 'null' && f !== 'undefined');
                
                if (validFiles.length === 0) return '-';

                const origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
                const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

                const urls = validFiles.map(f => {
                    if (typeof f !== 'string') return '';
                    if (f.startsWith('http://') || f.startsWith('https://')) return f;
                    const cleanName = f.replace(/^uploads\//, '');
                    return origin + basePath + '/uploads/' + cleanName;
                }).filter(Boolean);

                if (urls.length === 0) return '-';
                if (urls.length === 1) {
                    return { f: `HYPERLINK("${urls[0]}", "${urls[0]}")`, v: urls[0] };
                }
                return urls.join(', ');
            }

            function exportExcel() {
                if (!allData || !allData.length) return alert('Tidak ada data untuk di-export.');

                const rows = [[
                    'No',
                    'Tanggal',
                    'Layanan',
                    'Lokasi Asal',
                    'Lokasi Tujuan',
                    'Nama Penumpang',
                    'KM Awal',
                    'KM Akhir',
                    'Jarak (KM)',
                    'Jam Start (Berangkat)',
                    'Jam Selesai',
                    'Durasi Perjalanan',
                    'Driver',
                    'Nama / Plat Kendaraan',
                    'Status',
                    'Catatan Admin',
                    'Catatan Driver',
                    'Created By',
                    'Link Foto Spidometer Awal',
                    'Link Foto Spidometer Akhir',
                    'Link Foto Bukti Selesai'
                ]];

                const statusLabel = {
                    pending: 'Belum Berangkat',
                    in_transit: 'Dalam Perjalanan',
                    completed: 'Selesai',
                    canceled: 'Cancel'
                };

                allData.forEach((r, i) => {
                    const kmStart = r.speedometer_start_num ? Number(r.speedometer_start_num) : '-';
                    const kmEnd = r.speedometer_end_num ? Number(r.speedometer_end_num) : '-';
                    const jarak = (r.speedometer_start_num && r.speedometer_end_num) ? Number(r.speedometer_end_num - r.speedometer_start_num) : '-';
                    const typeStr = r.task_type === 'antar' ? 'Antar' : 'Jemput';

                    const vehicleStr = r.vehicle_name ? `${r.vehicle_name} (${r.vehicle_plate || '-'})` : (r.vehicle_plate || '-');

                    rows.push([
                        i + 1,
                        fmtDate(r.target_date || r.assign_time),
                        typeStr,
                        r.origin_name || '-',
                        r.destination_name || '-',
                        r.receiver_name || '-',
                        kmStart,
                        kmEnd,
                        jarak,
                        fmtDatetime(r.start_time),
                        fmtDatetime(r.end_time),
                        formatDurationTime(r.start_time, r.end_time, r.duration),
                        r.driver_name || '-',
                        vehicleStr,
                        statusLabel[r.status] || r.status,
                        r.notes || '-',
                        r.driver_notes || r.late_reason || '-',
                        r.requester_name || r.assigned_by || '-',
                        makeExcelHyperlink(r.speedometer_start_photo),
                        makeExcelHyperlink(r.speedometer_end_photo),
                        makeExcelHyperlink(r.delivery_proofs || r.proof_files_raw || r.proof_file || r.proof_files)
                    ]);
                });

                const ws = XLSX.utils.aoa_to_sheet(rows);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Report Tracking Driver');
                XLSX.writeFile(wb, `Report_Tracking_Driver_${new Date().toISOString().split('T')[0]}.xlsx`);
            }

            // style for spin
            const style = document.createElement('style');
            style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);

            // ===== EXPEDISI REPORT LOGIC =====
            async function loadExpedisiVendors() {
                try {
                    const res = await fetch(`${API}?action=get_expedisi_vendors`);
                    const vendors = await res.json();
                    const sel = document.getElementById('expVendor');
                    vendors.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.id;
                        opt.textContent = v.name;
                        sel.appendChild(opt);
                    });
                } catch (e) { }
            }

            function toggleExpDates() {
                expAllDate = !expAllDate;
                const btn = document.getElementById('btnExpAllDate');
                const icon = document.getElementById('expToggleIcon');
                const text = document.getElementById('expAllDateText');
                if (expAllDate) {
                    btn.classList.add('active');
                    icon.innerText = 'event_busy';
                } else {
                    btn.classList.remove('active');
                    icon.innerText = 'calendar_today';
                }
                loadExpedisiReport();
            }

            async function loadExpedisiReport() {
                const start = document.getElementById('expStart').value;
                const end = document.getElementById('expEnd').value;
                const sj = document.getElementById('expSJ').value;
                const vendor = document.getElementById('expVendor').value;
                const status = document.getElementById('expStatus').value;
                const tbody = document.getElementById('expBody');

                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem">Memuat data...</td></tr>';

                let query = `&start=${start}&end=${end}&vendor_id=${vendor}&status=${status}`;
                if (sj) query += `&surat_jalan=${encodeURIComponent(sj)}`;

                try {
                    const res = await fetch(`${API}?action=get_expedisi_tasks${query}`);
                    expData = await res.json();
                    expPage = 1; // Reset on search
                    renderExpTable(expData);
                    updateExpStats(expData);
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--danger)">Error: ${e.message}</td></tr>`;
                }
            }

            function renderExpTable(data) {
                const tbody = document.getElementById('expBody');
                const total = data.length;

                // Pagination Logic
                const start = (expPage - 1) * expRows;
                const end = start + expRows;
                const pagedData = data.slice(start, end);

                document.getElementById('expPrev').disabled = expPage <= 1;
                document.getElementById('expNext').disabled = end >= total;

                if (!pagedData.length) {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted)">Tidak ada data expedisi ditemukan</td></tr>';
                    return;
                }

                const statusClass = { pending: 'badge-pending', in_transit: 'badge-transit', completed: 'badge-completed', canceled: 'badge-danger' };

                tbody.innerHTML = pagedData.map((t, i) => {
                    const absoluteIndex = start + i;
                    const dt = new Date(t.created_at);
                    const df = `${String(dt.getDate()).padStart(2, '0')}-${String(dt.getMonth() + 1).padStart(2, '0')}-${dt.getFullYear()}`;
                    const tf = `${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`;

                    const statusLabelExp = {
                        pending: 'Belum Berangkat',
                        in_transit: 'Dalam Perjalanan',
                        completed: 'Selesai',
                        canceled: 'Cancel'
                    };

                    return `
            <tr>
                <td style="color:var(--text-muted);font-weight:700;font-size:.72rem">${absoluteIndex + 1}</td>
                <td style="font-size:.78rem;line-height:1.2">
                    ${df}<br><small style="color:var(--text-muted)">${tf} WIB</small>
                </td>
                <td><span class="sj-tag">${t.surat_jalan}</span></td>
                <td>
                    <div style="font-size:.82rem;font-weight:600">${t.origin_name}</div>
                    <div style="font-size:.75rem;color:var(--text-muted)">→ ${t.destination_name}</div>
                </td>
                <td><div style="font-weight:600">${t.vendor_name || '-'}</div></td>
                <td>${t.total_koli}</td>
                <td><div style="font-size:.78rem;font-weight:600">${t.driver_name || '-'}</div></td>
                <td><div style="font-size:.78rem;color:var(--text-muted);font-weight:600">${t.vehicle_plate || '-'}</div></td>
                <td><span class="badge ${statusClass[t.status]}">${statusLabelExp[t.status] || t.status}</span></td>
                <td style="text-align:center">
                    <button class="detail-btn" onclick='showExpDetail(${JSON.stringify(t)})' title="Detail" style="padding: 6px;">
                        <span class="material-symbols-outlined" style="font-size:18px">visibility</span>
                    </button>
                </td>
            </tr>
        `;
                }).join('');
            }

            function updateExpStats(data) {
                document.getElementById('expStatTotal').innerText = data.length;
                document.getElementById('expStatPending').innerText = data.filter(t => t.status === 'pending').length;
                document.getElementById('expStatTransit').innerText = data.filter(t => t.status === 'in_transit').length;
                document.getElementById('expStatDone').innerText = data.filter(t => t.status === 'completed').length;
            }

            function showExpDetail(t) {
                document.getElementById('modalSJ').innerText = t.surat_jalan;
                document.getElementById('modalRoute').innerText = `${t.origin_name} → ${t.destination_name}`;

                const routeViz = document.getElementById('routeViz');
                const isTransit = t.status === 'in_transit';
                const isDone = t.status === 'completed';
                const isPending = t.status === 'pending';
                const isCanceled = t.status === 'canceled';

                routeViz.style.display = 'block';

                let statusBadge = '';
                if (isPending) statusBadge = `<span class="route-status-badge route-status-pending">Belum Berangkat</span>`;
                else if (isTransit) statusBadge = `<span class="route-status-badge route-status-transit">🚛 Dalam Perjalanan</span>`;
                else if (isDone) statusBadge = `<span class="route-status-badge route-status-done">✓ Terkirim</span>`;
                else if (isCanceled) statusBadge = `<span class="route-status-badge" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);">✕ Canceled</span>`;

                let proofPhotos = t.proof_files_arr || [];

                routeViz.innerHTML = `
                    <div class="route-header">
                        <div style="color:var(--text-muted); font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Progress Expedisi</div>
                        ${statusBadge}
                    </div>
                    <div class="v-track">
                        <div class="v-col-line">
                            <div class="v-dot done"><span class="material-symbols-outlined" style="font-size:16px;">add_task</span></div>
                            <div class="v-segment"><div class="v-seg-fill" style="height:100%"></div></div>
                            
                            <div class="v-dot ${t.start_time ? 'done' : 'active'}"><span class="material-symbols-outlined" style="font-size:16px;">local_shipping</span></div>
                            <div class="v-segment" id="segExpDeparture">
                                <div class="v-seg-fill" id="fillExpDeparture"></div>
                                ${isTransit ? `<span class="v-truck moving" id="truckExp" style="top:-8px;">🚛</span>` : ''}
                            </div>
                            
                            <div class="v-dot ${isDone ? 'done' : (isTransit ? 'active' : 'pending')}">
                                <span class="material-symbols-outlined" style="font-size:16px;">${isDone ? 'task_alt' : (isCanceled ? 'cancel' : 'store')}</span>
                            </div>
                        </div>
                        <div class="v-col-content">
                            <div class="v-step">
                                <div class="v-step-label">Tugas Dibuat (Expedisi)</div>
                                <div class="v-step-name">${t.vendor_name || '—'}</div>
                                <div class="v-step-time">
                                    <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">person</span> Admin: ${t.creator_name || '—'}<br>
                                    <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">confirmation_number</span> No. Resi: <strong>${t.driver_name || '—'}</strong><br>
                                    <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">directions_car</span> Plat: <strong>${t.vehicle_plate || '—'}</strong><br>
                                    <span class="material-symbols-outlined" style="font-size:11px;vertical-align:middle;">schedule</span> ${fmtDatetime(t.created_at)}
                                </div>
                                ${(() => {
                        const sjPhotos = t.sj_files_arr || [];
                        const goodsPhotos = t.goods_files_arr || [];
                        let html = '';

                        if (sjPhotos.length) {
                            html += `<div style="margin-top:8px;">
                                        <div style="font-size:0.65rem; color:var(--text-muted); margin-bottom:4px; font-weight:700;">Foto Surat Jalan:</div>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            ${sjPhotos.map(f => `<img src="uploads/${f}" class="proof-thumb" style="width:45px; height:45px;" onclick="openLightbox('uploads/${f}')" title="Foto SJ">`).join('')}
                                        </div>
                                     </div>`;
                        }

                        if (goodsPhotos.length) {
                            html += `<div style="margin-top:8px;">
                                        <div style="font-size:0.65rem; color:var(--text-muted); margin-bottom:4px; font-weight:700;">Foto Kolian / Barang:</div>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            ${goodsPhotos.map(f => `<img src="uploads/${f}" class="proof-thumb" style="width:45px; height:45px;" onclick="openLightbox('uploads/${f}')" title="Foto Barang">`).join('')}
                                        </div>
                                     </div>`;
                        }
                        return html;
                    })()}
                            </div>
                            <div class="v-step-gap"></div>
                            <div class="v-step">
                                <div class="v-step-label">Asal: Pengiriman Dimulai</div>
                                <div class="v-step-name">${t.origin_name}</div>
                                <div class="v-step-time">
                                    ${t.start_time ? `🕒 Berangkat: ${fmtDatetime(t.start_time)}` : '<span style="color:rgba(245,158,11,0.6);">Menunggu keberangkatan...</span>'}
                                </div>
                            </div>
                            <div class="v-step-gap"></div>
                            <div class="v-step">
                                <div class="v-step-label">Tujuan: ${isDone ? 'Tiba di Lokasi' : 'Estimasi Selesai'}</div>
                                <div class="v-step-name">${t.destination_name}</div>
                                <div class="v-step-time">
                                    ${isDone ? `✅ Selesai: ${fmtDatetime(t.end_time)}` : (isCanceled ? `❌ Cancel: ${fmtDatetime(t.end_time)}` : '<span style="color:var(--text-muted); opacity:0.6;">Menuju lokasi tujuan...</span>')}
                                </div>
                            </div>
                        </div>
                    </div>

                    ${isDone ? `
                        <div class="receiver-card">
                            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                                <div class="receiver-avatar"><span class="material-symbols-outlined">how_to_reg</span></div>
                                <div>
                                    <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Nama Penumpang</div>
                                    <div style="font-size:1.05rem; font-weight:800; color:var(--text); margin-top:2px;">${t.receiver_name || '—'}</div>
                                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">Selesai pada: ${fmtDatetime(t.end_time)}</div>
                                </div>
                            </div>
                            ${t.driver_notes ? `
                                <div style="margin-bottom:0.75rem;">
                                    <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">📝 Keterangan Driver</div>
                                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:8px 12px; font-size:0.8rem; color:#166534; font-weight:600;">${t.driver_notes}</div>
                                </div>
                            ` : ''}
                            ${proofPhotos.length ? `
                                <div style="font-size:0.62rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem;">📷 Bukti Foto POD (${proofPhotos.length})</div>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    ${proofPhotos.map(f => `<img src="uploads/${f}" class="proof-thumb" onclick="openLightbox('uploads/${f}')" style="cursor:pointer;">`).join('')}
                                </div>
                            ` : '<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">Belum ada foto bukti.</div>'}
                        </div>
                    ` : ''}
                    ${isCanceled ? `
                        <div class="receiver-card" style="background:rgba(239,68,68,0.08); border-color:rgba(239,68,68,0.3);">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="color:#ef4444; font-size:20px;">cancel</span>
                                <div style="font-size:0.7rem; color:#ef4444; font-weight:800; text-transform:uppercase;">Alasan Pembatalan</div>
                            </div>
                            <div style="color:var(--text); font-size:0.88rem; font-weight:600; background:rgba(239,68,68,0.05); border-radius:8px; padding:8px 12px;">${t.driver_notes || 'Tidak ada keterangan'}</div>
                        </div>
                    ` : ''}
                `;

                const modal = document.getElementById('detailModal');
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                    const f = document.getElementById('fillExpDeparture');
                    const tr = document.getElementById('truckExp');
                    if (f) f.style.height = isDone ? '100%' : (isTransit ? '50%' : '0%');
                    if (tr && isTransit) tr.style.top = '42%';
                }, 100);
            }


            function exportExpedisi() {
                if (!expData || !expData.length) return alert('Tidak ada data expedisi untuk di-export.');

                const rows = [['No', 'Tipe', 'Waktu Dibuat', 'Vendor', 'No. Resi', 'Channel', 'No. SJ', 'Koli', 'Asal', 'Tujuan', 'Penerima', 'Waktu Selesai', 'Keterangan Driver', 'Status', 'Link Foto Bukti Selesai']];
                const statusLabelExp = {
                    pending: 'Belum Berangkat',
                    in_transit: 'Dalam Perjalanan',
                    completed: 'Selesai',
                    canceled: 'Cancel'
                };

                expData.forEach((t, i) => {
                    const dt = new Date(t.created_at);
                    const createdStr = `${String(dt.getDate()).padStart(2, '0')}/${String(dt.getMonth() + 1).padStart(2, '0')}/${dt.getFullYear()} ${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`;

                    const endDt = t.end_time ? new Date(t.end_time) : null;
                    const finishedStr = endDt ? `${String(endDt.getDate()).padStart(2, '0')}/${String(endDt.getMonth() + 1).padStart(2, '0')}/${endDt.getFullYear()} ${String(endDt.getHours()).padStart(2, '0')}:${String(endDt.getMinutes()).padStart(2, '0')}` : '-';

                    rows.push([
                        i + 1,
                        t.type === 'antar' ? 'Delivery' : 'Pickup',
                        createdStr,
                        t.vendor_name || '-',
                        t.driver_name || '-',
                        t.vehicle_plate || '-',
                        t.surat_jalan,
                        t.total_koli || 0,
                        t.origin_name || '-',
                        t.destination_name || '-',
                        t.receiver_name || '-',
                        finishedStr,
                        t.driver_notes || '-',
                        statusLabelExp[t.status] || t.status,
                        makeExcelHyperlink(t.proof_files || t.proof_file || t.goods_files)
                    ]);
                });

                const ws = XLSX.utils.aoa_to_sheet(rows);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Report Expedisi");
                XLSX.writeFile(wb, `Report_Expedisi_${new Date().toISOString().split('T')[0]}.xlsx`);
            }

            loadReport();
            loadExpedisiVendors();

            let liveMapObj = null;
            let driverMarker = null;

            async function openLiveMap(e, driverId, driverName) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                const mapModal = document.getElementById('mapModal');
                mapModal.style.display = 'flex';
                document.getElementById('mapModalSub').textContent = `Memantau Driver: ${driverName}`;
                document.getElementById('mapLoader').style.display = 'flex';

                // Ensure modal is visible for Leaflet to initialize
                setTimeout(async () => {
                    try {
                        if (!liveMapObj) {
                            liveMapObj = L.map('liveMap').setView([-6.2088, 106.8456], 13);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap'
                            }).addTo(liveMapObj);
                        }

                        const res = await fetch(`${API}?action=get_driver_location&driver_id=${driverId}`);
                        const data = await res.json();

                        document.getElementById('mapLoader').style.display = 'none';

                        if (data.lat && data.lng) {
                            const pos = [parseFloat(data.lat), parseFloat(data.lng)];

                            if (driverMarker) liveMapObj.removeLayer(driverMarker);

                            driverMarker = L.marker(pos).addTo(liveMapObj)
                                .bindPopup(`<b>${driverName}</b><br>Update terakhir: ${data.last_updated}`)
                                .openPopup();

                            liveMapObj.setView(pos, 15);
                            liveMapObj.invalidateSize();
                        } else {
                            alert("Lokasi driver tidak ditemukan atau GPS tidak aktif.");
                            closeMapModal();
                        }
                    } catch (err) {
                        console.error(err);
                        alert("Gagal memuat peta: " + err.message);
                        closeMapModal();
                    }
                }, 300);
            }

            function closeMapModal() {
                document.getElementById('mapModal').style.display = 'none';
            }
        </script>

</body>

</html>