<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('pickup_request');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pickup | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal Overlay for Popup */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            padding: 1rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        :root {
            --primary: #6366f1;
            --primary-light: #eef2ff;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --text-sub: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            padding-bottom: 2rem;
        }

        .header {
            background: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border-radius: 12px;
            color: var(--text);
            text-decoration: none;
        }

        .header h1 {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .container {
            padding: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-box {
            background: white;
            border-radius: 1.25rem;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1.5px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text);
            background: transparent;
        }

        .request-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .sj-number {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary);
        }

        .request-date {
            font-size: 0.75rem;
            color: var(--text-sub);
            font-weight: 500;
        }

        .badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fef3c7;
        }

        .badge-transit {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .badge-completed {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #dcfce7;
        }

        .badge-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .route-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            padding: 0.5rem 0;
        }

        .route-line {
            position: absolute;
            left: 7px;
            top: 1.25rem;
            bottom: 1.25rem;
            width: 2px;
            background: #e2e8f0;
            border-radius: 1px;
        }

        .route-dots {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            z-index: 1;
        }

        .dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 1px #e2e8f0;
        }

        .dot.origin {
            background: var(--primary);
        }

        .dot.dest {
            background: var(--success);
        }

        .route-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .route-step {
            display: flex;
            flex-direction: column;
        }

        .step-label {
            font-size: 0.7rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
        }

        .step-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .koli-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-sub);
        }

        .photo-links {
            display: flex;
            gap: 0.5rem;
        }

        .btn-photo {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-sub);
        }

        .empty-state .material-symbols-outlined {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* Desktop specific: hide card layout and show table or vice versa */
        .desktop-view {
            display: none;
        }

        .mobile-view {
            display: block;
        }

        @media (min-width: 1024px) {
            .mobile-view {
                display: none;
            }

            .desktop-view {
                display: block;
            }

            body {
                padding-bottom: 0;
            }

            .header {
                display: none;
            }

            /* Hide mobile header on desktop */
            .container {
                padding: 2rem;
                max-width: 100%;
                margin: 0;
            }

            .table-card {
                background: white;
                border-radius: 1rem;
                border: 1px solid var(--border);
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.85rem;
            }

            .data-table th {
                background: #f8fafc;
                padding: 1rem;
                text-align: left;
                font-weight: 700;
                color: var(--text-sub);
                border-bottom: 2px solid #f1f5f9;
                text-transform: uppercase;
                letter-spacing: 0.025em;
            }

            .data-table td {
                padding: 1rem;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
            }

            .data-table tr:hover {
                background: #fbfcfe;
            }
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 1.1rem 1.25rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-val {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-lbl {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .btn-excel-green {
            background: #16a34a;
            color: white;
            border: none;
            padding: 0.55rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.2);
            height: 38px;
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

        .pagination-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--card);
            border-radius: 1rem;
            border: 1px solid var(--border);
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

        /* Table Sorting Styles */
        .th-content {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-start;
        }

        th.sortable {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }
        th.sortable:hover {
            color: var(--primary) !important;
        }
        th.sortable:hover .sort-indicator {
            color: var(--primary) !important;
            opacity: 1;
        }
        .sort-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px !important;
            color: #94a3b8;
            opacity: 0.6;
            transition: all 0.2s ease;
        }
        th.active-sort {
            color: var(--primary) !important;
            border-bottom: 2px solid var(--primary) !important;
        }
        th.active-sort .sort-indicator {
            color: var(--primary) !important;
            opacity: 1;
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon"><span class="material-symbols-outlined">hail</span></div>
                <div>
                    <h1>Data Request Pickup</h1>
                    <p>Monitor semua permintaan penjemputan barang</p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row" style="margin-bottom: 1.5rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eef2ff;color:#6366f1"><span
                        class="material-symbols-outlined">description</span></div>
                <div>
                    <div class="stat-val" id="statTotal">0</div>
                    <div class="stat-lbl">Total Request</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#d97706"><span
                        class="material-symbols-outlined">pending_actions</span></div>
                <div>
                    <div class="stat-val" id="statPending">0</div>
                    <div class="stat-lbl">Menunggu</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669"><span
                        class="material-symbols-outlined">local_shipping</span></div>
                <div>
                    <div class="stat-val" id="statTransit">0</div>
                    <div class="stat-lbl">Transit</div>
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

        <div class="filter-bar"
            style="margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; background: white; padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--border); display: flex; align-items: flex-end;">
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Dari
                    Tanggal</label>
                <input type="date" id="dateFrom" class="filter-input" onchange="loadRequests()"
                    value="<?php echo date('Y-m-d'); ?>"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; font-size: 0.9rem;">
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Sampai
                    Tanggal</label>
                <input type="date" id="dateTo" class="filter-input" onchange="loadRequests()"
                    value="<?php echo date('Y-m-d'); ?>"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; font-size: 0.9rem;">
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">No.
                    Surat Jalan</label>
                <input type="text" id="filterSJ" placeholder="Cari SJ..." onkeyup="loadRequests()"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; font-size: 0.9rem;">
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Penerima</label>
                <input type="text" id="filterReceiver" placeholder="Cari Penerima..." onkeyup="loadRequests()"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; font-size: 0.9rem;">
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Status</label>
                <select id="filterStatus" onchange="loadRequests()"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; font-size: 0.9rem; background: white;">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="transit">Transit</option>
                    <option value="completed">Selesai</option>
                    <option value="canceled">Cancel</option>
                </select>
            </div>
            <div class="form-group" style="flex: 0; min-width: auto;">
                <button class="btn-excel-green" onclick="exportToExcel()">
                    <span class="material-symbols-outlined">table_view</span>
                </button>
            </div>
        </div>

        <div id="mobileContainer" class="mobile-view">
            <div class="empty-state">
                <span class="material-symbols-outlined rotating">autorenew</span>
                <p>Memuat riwayat...</p>
            </div>
        </div>

        <div class="desktop-view table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" onclick="handleSort('date')">
                            <div class="th-content">
                                Waktu
                                <span class="material-symbols-outlined sort-indicator" id="sort-date">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('sj')">
                            <div class="th-content">
                                No. Surat Jalan
                                <span class="material-symbols-outlined sort-indicator" id="sort-sj">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('origin')">
                            <div class="th-content">
                                Asal
                                <span class="material-symbols-outlined sort-indicator" id="sort-origin">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('dest')">
                            <div class="th-content">
                                Tujuan
                                <span class="material-symbols-outlined sort-indicator" id="sort-dest">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('koli')">
                            <div class="th-content">
                                Koli
                                <span class="material-symbols-outlined sort-indicator" id="sort-koli">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('driver')">
                            <div class="th-content">
                                User / Driver
                                <span class="material-symbols-outlined sort-indicator" id="sort-driver">swap_vert</span>
                            </div>
                        </th>
                        <th class="sortable" onclick="handleSort('receiver')">
                            <div class="th-content">
                                Penerima
                                <span class="material-symbols-outlined sort-indicator" id="sort-receiver">swap_vert</span>
                            </div>
                        </th>
                        <th>Foto SJ</th>
                        <th>Foto Barang</th>
                        <th class="sortable" onclick="handleSort('status')">
                            <div class="th-content">
                                Status
                                <span class="material-symbols-outlined sort-indicator" id="sort-status">swap_vert</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="desktopTableBody">
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="pagination-bar">
            <div class="pagination-controls">
                <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub);">Rows:</label>
                <select class="rows-per-page" id="rowsPerPage" onchange="changeRowsPerPage()">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button class="pagination-btn" id="btnPrev" onclick="changePage(-1)">
                    <span class="material-symbols-outlined" style="font-size:18px">chevron_left</span> Prev
                </button>
                <button class="pagination-btn" id="btnNext" onclick="changePage(1)">
                    Next <span class="material-symbols-outlined" style="font-size:18px">chevron_right</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===== IMAGE POPUP OVERLAY ===== -->
    <div id="imagePopup" class="modal-overlay" onclick="if(event.target===this)closeImagePopup()"
        style="z-index: 100000; display: none;">
        <div
            style="position:relative; max-width:90%; max-height:90%; display:flex; flex-direction:column; align-items:center;">
            <div style="position:absolute; top:-45px; right:0; display:flex; gap:10px;">
                <a id="downloadImageBtn" href="#" download class="btn btn-primary btn-sm"
                    style="height:32px; padding:0 12px; border-radius:6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-decoration:none;">
                    <span class="material-symbols-outlined" style="font-size:20px;">download</span>
                    Download
                </a>
                <button onclick="closeImagePopup()"
                    style="background:rgba(255,255,255,0.1); border:none; color:white; cursor:pointer; width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
                    <span class="material-symbols-outlined" style="font-size:24px;">close</span>
                </button>
            </div>
            <img id="popupImg" src=""
                style="max-width:100%; max-height:85vh; border-radius:12px; object-fit:contain; box-shadow: 0 20px 50px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1);">
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let allRequests = [];
        let currentPage = 1;
        let rowsPerPage = 25;
        let sortColumn = 'date';
        let sortDirection = 'desc';

        // Init dates from SERVER
        const serverToday = "<?php echo date('Y-m-d'); ?>";
        const serverAgo7 = "<?php echo date('Y-m-d', strtotime('-7 days')); ?>"; // Default last 7 days for pickup

        // Handle filter=today from URL
        const urlParams = new URLSearchParams(window.location.search);
        const filterParam = urlParams.get('filter');

        if (filterParam === 'today') {
            document.getElementById('dateFrom').value = serverToday;
            document.getElementById('dateTo').value = serverToday;
        } else {
            // Default juga disetel ke hari ini (sebelumnya serverAgo7)
            document.getElementById('dateFrom').value = serverToday;
            document.getElementById('dateTo').value = serverToday;
        }

        async function loadRequests() {
            try {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                const sj = document.getElementById('filterSJ').value;
                const receiver = document.getElementById('filterReceiver').value;
                const status = document.getElementById('filterStatus').value;

                let query = `&date_from=${dateFrom}&date_to=${dateTo}&surat_jalan=${encodeURIComponent(sj)}&receiver=${encodeURIComponent(receiver)}&status=${status}`;

                const res = await fetch(`${API_URL}?action=get_pickup_requests${query}`);
                const json = await res.json();
                allRequests = Array.isArray(json) ? json : [];
                applySorting();
                currentPage = 1; // Reset to first page
                renderRequests(allRequests);
            } catch (err) {
                console.error(err);
                const msg = '<p style="text-align:center; color:red; padding:2rem;">Gagal memuat data dari server.</p>';
                if (document.getElementById('mobileContainer')) document.getElementById('mobileContainer').innerHTML = msg;
                if (document.getElementById('desktopTableBody')) document.getElementById('desktopTableBody').innerHTML = `<tr><td colspan="10" style="text-align:center;">${msg}</td></tr>`;
            }
        }

        function renderRequests(data) {
            updateSortIndicators();
            const mobileContainer = document.getElementById('mobileContainer');
            const desktopBody = document.getElementById('desktopTableBody');

            // Update Stats
            document.getElementById('statTotal').textContent = data.length;
            document.getElementById('statPending').textContent = data.filter(r => r.status === 'pending').length;
            document.getElementById('statTransit').textContent = data.filter(r => r.status === 'in_transit' || r.status === 'approved').length;
            document.getElementById('statDone').textContent = data.filter(r => r.status === 'completed').length;

            const total = data.length;
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pagedData = data.slice(start, end);

            // Update Pagination Buttons
            document.getElementById('btnPrev').disabled = currentPage <= 1;
            document.getElementById('btnNext').disabled = end >= total;

            if (!pagedData.length && data.length > 0 && currentPage > 1) {
                currentPage = 1;
                renderRequests(data);
                return;
            }

            if (!data.length) {
                const empty = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">search_off</span>
                        <p>Tidak ada data ditemukan.</p>
                    </div>`;
                mobileContainer.innerHTML = empty;
                desktopBody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:3rem; color:var(--text-sub);">Tidak ada data ditemukan.</td></tr>`;
                document.querySelector('.pagination-bar').style.display = 'none';
                return;
            }
            document.querySelector('.pagination-bar').style.display = 'flex';

            // Render Mobile Cards
            mobileContainer.innerHTML = pagedData.map(req => {
                const statusInfo = getStatusInfo(req.status);
                const dt = new Date(req.created_at);
                const dateStr = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                const sjPhotos = renderPhotoButtons(req.surat_jalan_file, 'image', '#eef2ff', '#4338ca');
                const goodsPhotos = renderPhotoButtons(req.goods_file, 'inventory_2', '#f0fdf4', '#16a34a');

                const driverNames = req.assigned_drivers ? req.assigned_drivers.split('|').map(d => d.split(':')[0]).join(', ') : '-';

                return `
                    <div class="request-card">
                        <div class="card-top">
                            <div><div class="sj-number">${req.surat_jalan}</div><div class="request-date">${dateStr}</div></div>
                            <span class="badge ${statusInfo.cls}">${statusInfo.text}</span>
                        </div>
                        <div style="font-size:0.75rem; color:var(--text-sub); margin-bottom:1rem; display:flex; gap:0.5rem; align-items:center;">
                            <span class="material-symbols-outlined" style="font-size:16px;">person</span>
                            <span>Req by: <b>${req.requester_name}</b></span>
                            ${driverNames !== '-' ? `<span>| Driver: <b>${driverNames}</b></span>` : ''}
                        </div>
                        <div class="route-info">
                            <div class="route-line"></div>
                            <div class="route-dots"><div class="dot origin"></div><div class="dot dest"></div></div>
                            <div class="route-details">
                                <div class="route-step"><span class="step-label">ASAL</span><span class="step-name">${req.origin_name}</span></div>
                                <div class="route-step"><span class="step-label">TUJUAN</span><span class="step-name">${req.destination_name}</span></div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="koli-info"><span class="material-symbols-outlined" style="font-size:18px;">package_2</span>${req.total_koli} Koli</div>
                            <div class="photo-links">${sjPhotos}${goodsPhotos} ${(!sjPhotos && !goodsPhotos) ? '<span style="font-size:0.7rem; color:var(--text-sub);">Tanpa Foto</span>' : ''}</div>
                        </div>
                    </div>
                `;
            }).join('');

            // Render Desktop Table
            desktopBody.innerHTML = pagedData.map(req => {
                const statusInfo = getStatusInfo(req.status);
                const dt = new Date(req.created_at).toLocaleString('id-ID');
                const sjPhotos = renderPhotoButtons(req.surat_jalan_file, 'image', '#eef2ff', '#4338ca');
                const goodsPhotos = renderPhotoButtons(req.goods_file, 'inventory_2', '#f0fdf4', '#16a34a');

                const driverHtml = req.assigned_drivers ? req.assigned_drivers.split('|').map(d => {
                    const [name, status] = d.split(':');
                    const color = status === 'completed' ? 'var(--success)' : 'var(--primary)';
                    return `<div style="font-size:0.75rem; color:${color}; font-weight:700;">• ${name}</div>`;
                }).join('') : '<span style="color:var(--text-sub); font-size:0.75rem;">Belum ada driver</span>';

                return `
                    <tr>
                        <td>${dt}</td>
                        <td style="font-weight:700; color:var(--primary);">${req.surat_jalan}</td>
                        <td>${req.origin_name}</td>
                        <td>${req.destination_name}</td>
                        <td style="font-weight:600;">${req.total_koli}</td>
                        <td>
                            <div style="font-size:0.8rem; font-weight:700; margin-bottom:4px;">${req.requester_name}</div>
                            ${driverHtml}
                        </td>
                        <td style="font-weight:600; color:var(--primary);">${req.receiver_name || '-'}</td>
                        <td><div style="display:flex; gap:4px;">${sjPhotos || '-'}</div></td>
                        <td><div style="display:flex; gap:4px;">${goodsPhotos || '-'}</div></td>
                        <td><span class="badge ${statusInfo.cls}">${statusInfo.text}</span></td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusInfo(status) {
            if (status === 'pending') return { cls: 'badge-pending', text: 'Menunggu' };
            if (status === 'completed') return { cls: 'badge-completed', text: 'Selesai' };
            if (status === 'canceled') return { cls: 'badge-danger', text: 'Cancel' };
            return { cls: 'badge-transit', text: 'Transit' };
        }

        function renderPhotoButtons(fileJson, icon, bg, color) {
            if (!fileJson) return '';
            try {
                if (fileJson.startsWith('[') || fileJson.startsWith('{')) {
                    const files = JSON.parse(fileJson);
                    if (Array.isArray(files) && files.length > 0) {
                        return files.map(f => `
                            <button type="button" onclick="showImagePopup('uploads/${f}')" class="btn-photo" style="background:${bg}; color:${color}; cursor:zoom-in;" title="Klik untuk memperbesar">
                                <span class="material-symbols-outlined" style="font-size:20px;">${icon}</span>
                            </button>
                        `).join('');
                    }
                }
            } catch (e) { /* fall through */ }
            // Fallback for single filename string
            return `<button type="button" onclick="showImagePopup('uploads/${fileJson}')" class="btn-photo" style="background:${bg}; color:${color}; cursor:zoom-in;" title="Klik untuk memperbesar">
                        <span class="material-symbols-outlined" style="font-size:20px;">${icon}</span>
                    </button>`;
        }

        // ===== SORTING FUNCTIONS =====
        function applySorting() {
            const isAsc = sortDirection === 'asc' ? 1 : -1;
            
            allRequests.sort((a, b) => {
                let valA, valB;
                
                switch(sortColumn) {
                    case 'date':
                        valA = new Date(a.created_at || 0).getTime();
                        valB = new Date(b.created_at || 0).getTime();
                        break;
                    case 'sj':
                        valA = (a.surat_jalan || '').toLowerCase();
                        valB = (b.surat_jalan || '').toLowerCase();
                        break;
                    case 'origin':
                        valA = (a.origin_name || '').toLowerCase();
                        valB = (b.origin_name || '').toLowerCase();
                        break;
                    case 'dest':
                        valA = (a.destination_name || '').toLowerCase();
                        valB = (b.destination_name || '').toLowerCase();
                        break;
                    case 'koli':
                        valA = parseInt(a.total_koli) || 0;
                        valB = parseInt(b.total_koli) || 0;
                        break;
                    case 'driver':
                        valA = (a.requester_name || '').toLowerCase();
                        valB = (b.requester_name || '').toLowerCase();
                        break;
                    case 'receiver':
                        valA = (a.receiver_name || '').toLowerCase();
                        valB = (b.receiver_name || '').toLowerCase();
                        break;
                    case 'status':
                        valA = (a.status || '').toLowerCase();
                        valB = (b.status || '').toLowerCase();
                        break;
                    default:
                        valA = 0;
                        valB = 0;
                }
                
                if (valA < valB) return -1 * isAsc;
                if (valA > valB) return 1 * isAsc;
                
                // Secondary sort by date (newest first)
                const dateA = new Date(a.created_at || 0).getTime();
                const dateB = new Date(b.created_at || 0).getTime();
                return dateB - dateA;
            });
        }

        function handleSort(column) {
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = (column === 'date') ? 'desc' : 'asc';
            }
            applySorting();
            currentPage = 1;
            renderRequests(allRequests);
        }

        function updateSortIndicators() {
            const columns = ['date', 'sj', 'origin', 'dest', 'koli', 'driver', 'receiver', 'status'];
            columns.forEach(col => {
                const indicator = document.getElementById(`sort-${col}`);
                if (!indicator) return;
                const th = indicator.closest('th');
                if (sortColumn === col) {
                    indicator.innerText = sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward';
                    if (th) th.classList.add('active-sort');
                } else {
                    indicator.innerText = 'swap_vert';
                    if (th) th.classList.remove('active-sort');
                }
            });
        }

        function filterRequests() {
            const query = document.getElementById('filterSJ').value.toLowerCase();
            const filtered = allRequests.filter(req =>
                req.surat_jalan.toLowerCase().includes(query) ||
                req.origin_name.toLowerCase().includes(query) ||
                req.destination_name.toLowerCase().includes(query)
            );
            currentPage = 1;
            renderRequests(filtered);
        }

        function changePage(delta) {
            currentPage += delta;
            renderRequests(allRequests);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
            currentPage = 1;
            renderRequests(allRequests);
        }

        function showImagePopup(url) {
            console.log('showImagePopup called with:', url);
            const popup = document.getElementById('imagePopup');
            const img = document.getElementById('popupImg');
            const downloadBtn = document.getElementById('downloadImageBtn');

            if (popup && img) {
                img.src = url;
                if (downloadBtn) {
                    downloadBtn.href = url;
                    const filename = url.split('/').pop();
                    downloadBtn.download = filename;
                }
                const m = document.getElementById('imagePopup');
                m.style.display = 'flex';
                setTimeout(() => m.classList.add('show'), 10);
            }
        }

        function closeImagePopup() {
            const popup = document.getElementById('imagePopup');
            if (popup) {
                popup.classList.remove('show');
                setTimeout(() => popup.style.display = 'none', 300);
            }
        }

        // Close popup with ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeImagePopup();
        });

        loadRequests();

        function exportToExcel() {
            const table = document.querySelector(".data-table");
            const rows = [["Waktu", "No. Surat Jalan", "Asal", "Tujuan", "Koli", "User / Driver", "Penerima", "Status"]];

            const trs = table.querySelectorAll("tbody tr");
            trs.forEach((tr) => {
                const tds = tr.querySelectorAll("td");
                if (tds.length < 10) return; // Skip empty/loading state

                // Get driver info text
                let driverInfo = tds[5].innerText.replace(/\n/g, " | ");

                rows.push([
                    tds[0].innerText,
                    tds[1].innerText,
                    tds[2].innerText,
                    tds[3].innerText,
                    tds[4].innerText,
                    driverInfo,
                    tds[6].innerText,
                    tds[9].innerText
                ]);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Request Pickup");
            XLSX.writeFile(wb, `Report_Pickup_Request_${new Date().toISOString().split('T')[0]}.xlsx`);
        }
        // Initial Load
        loadRequests();
    </script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
</body>

</html>