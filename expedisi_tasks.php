<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('assign_tasks');
$can_write = canWriteMenu('assign_tasks');

// Get initial pending counts for tabs
$init_wh = $pdo->query("SELECT COUNT(*) FROM pickup_requests WHERE status = 'pending'")->fetchColumn();
$init_exp = $pdo->query("SELECT COUNT(*) FROM expedisi_tasks WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Expedisi | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #e2e8f0;
            padding: 0.4rem;
            border-radius: 12px;
            width: fit-content;
        }

        .tab-link {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .tab-link.active {
            background: var(--surface);
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f59e0b;
            /* Amber */
            color: white;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 99px;
            margin-left: 6px;
            min-width: 18px;
            height: 18px;
            line-height: 1;
            vertical-align: middle;
        }

        .tab-badge.badge-red {
            background: #ef4444;
            /* Red */
            animation: tabPulse 2s infinite;
        }

        @keyframes tabPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-transit {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-canceled {
            background: #fee2e2;
            color: #991b1b;
        }

        .type-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .type-antar {
            background: #e0f2fe;
            color: #0369a1;
        }

        .type-kirim {
            background: #fdf2f8;
            color: #9d174d;
        }

        /* 10px Font Size for Table Content */
        table tbody td,
        table tbody td div,
        table tbody td span:not(.material-symbols-outlined),
        table tbody td strong {
            font-size: 10px !important;
        }

        table tbody td .material-symbols-outlined {
            font-size: 20px !important;
        }

        .status-badge,
        .type-badge {
            font-size: 9px !important;
            padding: 2px 8px !important;
        }

        table thead th {
            font-size: 10px !important;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
            padding: 1.5rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .preview-item {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
            position: relative;
        }

        .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            z-index: 10;
            transition: 0.2s;
        }

        .remove-btn:hover {
            background: var(--danger);
            transform: scale(1.1);
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .filter-section {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 0.5rem;
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
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #f8fafc;
            min-width: 130px;
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
            background: var(--primary-glow);
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

        .btn-excel {
            background: #16a34a;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-excel:hover {
            background: #15803d;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }

        /* Image Viewer Popup */
        #imageViewer {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 2rem;
        }

        #imageViewer.show {
            display: flex;
        }

        #viewerImg {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
            object-fit: contain;
        }

        .viewer-controls {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .btn-viewer {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
            transition: 0.2s;
        }

        .btn-viewer.download {
            background: var(--primary);
            color: white;
        }

        .btn-viewer.close {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-viewer:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        /* Modern Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        /* Searchable Dropdown CSS */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            background: #fff;
            color: var(--text);
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-top: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1100;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .search-results.show {
            display: block;
        }

        .search-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:hover {
            background: #f8fafc;
            color: var(--primary);
        }

        .search-item.no-results {
            color: var(--text-muted);
            font-style: italic;
            cursor: default;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            font-family: inherit;
            font-size: 0.9rem;
            transition: 0.2s;
            outline: none;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .upload-box {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 0.4rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fafc;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-muted);
        }

        .upload-box:hover {
            border-color: var(--primary);
            background: var(--primary-glow);
            color: var(--primary);
        }

        .upload-box .material-symbols-outlined {
            font-size: 1.25rem;
        }

        .upload-box span:not(.material-symbols-outlined) {
            font-size: 0.7rem !important;
            font-weight: 700;
        }

        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .preview-thumb {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .modal-box {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.25rem;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .detail-table tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-table tr:last-child {
            border-bottom: none;
        }

        .detail-table td {
            padding: 10px 14px;
            font-size: 0.85rem;
            vertical-align: top;
            text-align: left;
        }

        .detail-table td:first-child {
            width: 130px;
            background: #f8fafc;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.5px;
            border-right: 1px solid #f1f5f9;
        }

        .detail-table td:last-child {
            color: var(--text);
            font-weight: 600;
        }

        .detail-badge {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .modal-close {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: #f1f5f9;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            transition: 0.2s;
        }

        .modal-close:hover {
            background: #e2e8f0;
            color: #0f172a;
            transform: rotate(90deg);
        }

        .pagination-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--surface);
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
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-glow);
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
            color: var(--text-muted);
            outline: none;
        }

        /* Premium Table Sorting Styles */
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
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">local_shipping</span>
                </div>
                <div>
                    <h1>Penugasan Pengiriman</h1>
                    <p>Kelola tugas pengiriman Warehouse dan Expedisi Luar</p>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tab-container">
            <a href="assign_tasks" class="tab-link">
                WH Operation
                <span id="tabBadgeWH" class="tab-badge badge-red" <?php echo $init_wh > 0 ? '' : 'style="display:none;"'; ?>><?php echo $init_wh; ?></span>
            </a>
            <a href="expedisi_tasks" class="tab-link active">
                Expedisi
                <span id="tabBadgeExp" class="tab-badge" <?php echo $init_exp > 0 ? '' : 'style="display:none;"'; ?>><?php echo $init_exp; ?></span>
            </a>
        </div>

        <!-- Filter Card -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <div class="filter-section"
                style="margin-bottom:0; display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
                <div class="filter-group" style="flex: 1; min-width: 200px;">
                    <label class="filter-label"
                        style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Cari
                        Data</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <div style="position: relative; flex: 1;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted);">search</span>
                            <input type="text" id="searchInput" placeholder="Search..." onkeyup="loadTasks()"
                                style="width: 100%; padding: 0.6rem 0.6rem 0.6rem 35px; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                        </div>
                        <button class="btn-excel" onclick="exportToExcel()" id="exportBtn" title="Export Excel"
                            style="height: 38px; width: 38px; margin: 0; flex-shrink: 0; box-sizing: border-box; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined">table_view</span>
                        </button>
                    </div>
                </div>
                <div class="filter-group" style="width: 150px;">
                    <label class="filter-label"
                        style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Dari</label>
                    <input type="date" id="filterStart" class="filter-input" value="<?php echo date('Y-m-d'); ?>"
                        onchange="loadTasks()"
                        style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                </div>
                <div class="filter-group" style="width: 150px;">
                    <label class="filter-label"
                        style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Sampai</label>
                    <input type="date" id="filterEnd" class="filter-input" value="<?php echo date('Y-m-d'); ?>"
                        onchange="loadTasks()"
                        style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                </div>
                <div class="filter-group" style="width: 160px;">
                    <label class="filter-label"
                        style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Vendor</label>
                    <select id="filterVendor" class="filter-input" onchange="loadTasks()"
                        style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; background: white;">
                        <option value="">Semua Vendor</option>
                    </select>
                </div>
                <div class="filter-group" style="width: 140px;">
                    <label class="filter-label"
                        style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Status</label>
                    <select id="filterStatus" class="filter-input" onchange="loadTasks()"
                        style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; background: white;">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_transit">In Transit</option>
                        <option value="completed">Completed</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                    <?php if ($can_write): ?>
                        <button class="btn btn-primary" onclick="openAddModal()"
                            style="height: 40px; white-space: nowrap; padding: 0 1rem; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">local_shipping</span>
                            Tugas Expedisi
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div style="display:flex; align-items:center;">
                    <span class="card-title" style="margin-right:0;">
                        <span class="material-symbols-outlined">history</span>
                        Riwayat Penugasan
                    </span>
                    <span class="data-badge" id="dataCount">0 data</span>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="historyTable">
                    <thead>
                        <tr>
                            <th class="sortable" onclick="handleSort('date')">
                                <div class="th-content">
                                    Tanggal
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-date">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('sj')">
                                <div class="th-content">
                                    No. SJ
                                    <span class="material-symbols-outlined sort-indicator" id="sort-sj">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('type')">
                                <div class="th-content">
                                    Tipe
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-type">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('route')">
                                <div class="th-content">
                                    Tujuan
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-route">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('vendor')">
                                <div class="th-content">
                                    Vendor
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-vendor">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('koli')">
                                <div class="th-content">
                                    Koli
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-koli">swap_vert</span>
                                </div>
                            </th>
                             <th class="sortable" onclick="handleSort('driver')">
                                <div class="th-content">
                                    No. Resi
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-driver">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('unit')">
                                <div class="th-content">
                                    Unit
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-unit">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('status')">
                                <div class="th-content">
                                    Status
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-status">swap_vert</span>
                                </div>
                            </th>
                            <th class="sortable" onclick="handleSort('notes')">
                                <div class="th-content">
                                    Keterangan
                                    <span class="material-symbols-outlined sort-indicator"
                                        id="sort-notes">swap_vert</span>
                                </div>
                            </th>
                            <th style="text-align:center;">
                                <div class="th-content" style="justify-content:center; width: 100%;">
                                    Aksi
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="taskBody">
                        <tr>
                            <td colspan="11" style="text-align:center; padding:2rem;">Memuat data...</td>
                        </tr>
                </table>
            </div>
        </div>

        <!-- Pagination Bar -->
        <div class="pagination-bar">
            <div class="pagination-controls">
                <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted);">Rows:</label>
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

    <!-- ADD MODAL -->
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)closeAdd()" style="display: none;">
        <div class="modal-box" style="max-width:800px;">
            <button class="modal-close" onclick="closeAdd()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined"
                    style="font-size: 28px; color: var(--primary);">local_shipping</span>
                Buat Tugas Expedisi
            </div>
            <p class="modal-subtitle">Input detail pengiriman untuk vendor luar</p>

            <form id="addForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tanggal Target</label>
                        <input type="date" name="target_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe Tugas</label>
                        <select name="type" required>
                            <option value="antar">📦 DELIVERY (ANTAR)</option>
                            <option value="kirim">🚚 PICKUP (AMBIL)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Vendor Expedisi</label>
                        <div class="search-container">
                            <input type="hidden" name="vendor_id" id="vendorIdInput">
                            <input type="text" id="vendorSearch" class="search-input" placeholder="Cari vendor..."
                                autocomplete="off" required>
                            <div id="vendorResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Channel</label>
                        <input type="text" name="vehicle_plate" required placeholder="MT / GT"
                            oninput="this.value=this.value.toUpperCase()" style="text-transform:uppercase;">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nomor Surat Jalan</label>
                        <input type="text" name="surat_jalan" required placeholder="Contoh: SJ-2024-ABC">
                    </div>
                    <div class="form-group">
                        <label>Total Koli</label>
                        <input type="number" name="total_koli" required value="1" min="1">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Lokasi Asal </label>
                        <div class="search-container">
                            <input type="text" name="origin_name" id="originSearch" class="search-input"
                                placeholder="Cari lokasi asal..." autocomplete="off" required
                                style="padding-right: 35px;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted); font-size: 20px;">expand_more</span>
                            <div id="originResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Tujuan</label>
                        <div class="search-container">
                            <input type="text" name="destination_name" id="destSearch" class="search-input"
                                placeholder="Cari lokasi tujuan..." autocomplete="off" required
                                style="padding-right: 35px;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted); font-size: 20px;">expand_more</span>
                            <div id="destResults" class="search-results"></div>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>No Resi</label>
                        <input type="text" name="driver_name" required placeholder="No Resi"
                            oninput="this.value=this.value.toUpperCase()" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="notes" required placeholder="Tambahkan catatan jika ada..."
                            style="height: 45px; text-transform:uppercase;"
                            oninput="this.value=this.value.toUpperCase()"></textarea>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Foto Surat Jalan</label>
                        <input type="file" id="sjFileInput" name="sj_files[]" multiple accept="image/*"
                            style="display:none;" onchange="handleImagePreview(this, 'sjPreviewGrid')" required>
                        <div class="upload-box" onclick="document.getElementById('sjFileInput').click()">
                            <span class="material-symbols-outlined">add_a_photo</span>
                            <span style="font-weight:700; font-size:0.75rem;">Tambah Foto SJ</span>
                        </div>
                        <div class="preview-container" id="sjPreviewGrid"></div>
                    </div>
                    <div class="form-group">
                        <label>Foto Kolian / Barang</label>
                        <input type="file" id="goodsFileInput" name="goods_files[]" multiple accept="image/*"
                            style="display:none;" onchange="handleImagePreview(this, 'goodsPreviewGrid')" required>
                        <div class="upload-box" onclick="document.getElementById('goodsFileInput').click()">
                            <span class="material-symbols-outlined">inventory_2</span>
                            <span style="font-weight:700; font-size:0.75rem;">Tambah Foto Barang</span>
                        </div>
                        <div class="preview-container" id="goodsPreviewGrid"></div>
                    </div>
                </div>

                <div style="display:flex; gap:1rem; margin-top:2rem;">
                    <button type="button" onclick="closeAdd()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">send</span>
                        Simpan & Berikan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)closeEdit()" style="display: none;">
        <div class="modal-box" style="max-width:800px;">
            <button class="modal-close" onclick="closeEdit()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined" style="font-size: 28px; color: var(--warning);">edit</span>
                Edit Tugas Expedisi
            </div>
            <p class="modal-subtitle">Ubah detail pengiriman vendor ekspedisi</p>

            <form id="editForm">
                <input type="hidden" name="id" id="editTaskId">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tanggal Target</label>
                        <input type="date" name="target_date" id="editTargetDate" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe Tugas</label>
                        <select name="type" id="editType" required>
                            <option value="antar">📦 DELIVERY (ANTAR)</option>
                            <option value="kirim">🚚 PICKUP (AMBIL)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Vendor Expedisi</label>
                        <div class="search-container">
                            <input type="hidden" name="vendor_id" id="editVendorIdInput">
                            <input type="text" id="editVendorSearch" class="search-input" placeholder="Cari vendor..."
                                autocomplete="off" required>
                            <div id="editVendorResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Channel</label>
                        <input type="text" name="vehicle_plate" id="editVehiclePlate" required placeholder="MT Or GT"
                            oninput="this.value=this.value.toUpperCase()" style="text-transform:uppercase;">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nomor Surat Jalan</label>
                        <input type="text" name="surat_jalan" id="editSuratJalan" required
                            placeholder="Contoh: SJ-2024-ABC">
                    </div>
                    <div class="form-group">
                        <label>Total Koli</label>
                        <input type="number" name="total_koli" id="editTotalKoli" required min="1">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Lokasi Asal </label>
                        <div class="search-container">
                            <input type="text" name="origin_name" id="editOriginSearch" class="search-input"
                                placeholder="Cari lokasi asal..." autocomplete="off" required
                                style="padding-right: 35px;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted); font-size: 20px;">expand_more</span>
                            <div id="editOriginResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Tujuan</label>
                        <div class="search-container">
                            <input type="text" name="destination_name" id="editDestSearch" class="search-input"
                                placeholder="Cari lokasi tujuan..." autocomplete="off" required
                                style="padding-right: 35px;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted); font-size: 20px;">expand_more</span>
                            <div id="editDestResults" class="search-results"></div>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>No Resi</label>
                        <input type="text" name="driver_name" id="editDriverName" required
                            placeholder="No Resi" oninput="this.value=this.value.toUpperCase()"
                            style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="notes" id="editNotes" required placeholder="Tambahkan catatan jika ada..."
                            style="height: 45px; text-transform:uppercase;"
                            oninput="this.value=this.value.toUpperCase()"></textarea>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Foto Surat Jalan Baru <small
                                style="color:var(--text-muted); font-weight:normal;">(Opsional, ganti jika
                                ada)</small></label>
                        <input type="file" id="editSjFileInput" name="sj_files[]" multiple accept="image/*"
                            style="display:none;" onchange="handleImagePreview(this, 'editSjPreviewGrid')">
                        <div class="upload-box" onclick="document.getElementById('editSjFileInput').click()">
                            <span class="material-symbols-outlined">add_a_photo</span>
                            <span style="font-weight:700; font-size:0.75rem;">Ganti / Tambah Foto SJ</span>
                        </div>
                        <div class="preview-container" id="editSjPreviewGrid"></div>
                    </div>
                    <div class="form-group">
                        <label>Foto Kolian / Barang Baru <small
                                style="color:var(--text-muted); font-weight:normal;">(Opsional, ganti jika
                                ada)</small></label>
                        <input type="file" id="editGoodsFileInput" name="goods_files[]" multiple accept="image/*"
                            style="display:none;" onchange="handleImagePreview(this, 'editGoodsPreviewGrid')">
                        <div class="upload-box" onclick="document.getElementById('editGoodsFileInput').click()">
                            <span class="material-symbols-outlined">inventory_2</span>
                            <span style="font-weight:700; font-size:0.75rem;">Ganti / Tambah Foto Barang</span>
                        </div>
                        <div class="preview-container" id="editGoodsPreviewGrid"></div>
                    </div>
                </div>

                <div style="display:flex; gap:1rem; margin-top:2rem;">
                    <button type="button" onclick="closeEdit()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FINISH MODAL -->
    <div id="finishModal" class="modal-overlay" onclick="if(event.target===this)closeFinish()" style="display: none;">
        <div class="modal-box" style="max-width:560px;">
            <button class="modal-close" onclick="closeFinish()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined"
                    style="font-size: 28px; color: var(--success);">check_circle</span>
                Selesaikan Pengiriman
            </div>
            <p class="modal-subtitle">Konfirmasi penerimaan barang di lokasi tujuan</p>

            <form id="finishForm">
                <input type="hidden" name="id" id="finishId">
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label>Nama Penerima Barang <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="receiver_name" required placeholder="Masukkan nama penerima">
                </div>
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label>Catatan / Keterangan Driver <span style="color:var(--danger);">*</span></label>
                    <textarea name="driver_notes" required placeholder="Contoh: Barang diterima aman, kondisi baik..."
                        rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Foto Bukti Selesai (POD) <span style="color:var(--danger);">*</span></label>
                    <input type="file" id="proofFileInput" name="proof_files[]" multiple accept="image/*"
                        style="display:none;" onchange="handleProofPhotos(this)">
                    <label for="proofFileInput" class="upload-box" style="cursor:pointer;">
                        <span class="material-symbols-outlined">inventory_2</span>
                        <span style="font-weight:700;">Tambah Foto Bukti</span>
                        <span style="font-size:0.75rem; font-weight:normal;">Pilih foto barang atau surat jalan</span>
                    </label>
                    <div class="image-preview-grid" id="proofPreviewGrid"></div>
                </div>

                <div style="display:flex; gap:1rem; margin-top:2rem;">
                    <button type="button" onclick="closeFinish()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">task_alt</span>
                        Konfirmasi Selesai
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div id="detailModal" class="modal-overlay" onclick="if(event.target===this)closeDetail()" style="display: none;">
        <div class="modal-box" style="max-width:600px;">
            <button class="modal-close" onclick="closeDetail()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">Detail Pengiriman Expedisi</div>
            <div id="detailContent" style="margin-top:1rem;"></div>
        </div>
    </div>

    <!-- IMAGE VIEWER POPUP -->
    <div id="imageViewer" onclick="if(event.target===this)closeViewer()">
        <img id="viewerImg" src="">
        <div class="viewer-controls">
            <button class="btn-viewer download" id="downloadBtn">
                <span class="material-symbols-outlined">download</span>
                Download Foto
            </button>
            <button class="btn-viewer close" onclick="closeViewer()">
                <span class="material-symbols-outlined">close</span>
                Tutup
            </button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const API = 'api.php';
        const CAN_WRITE = <?php echo $can_write ? 'true' : 'false'; ?>;
        const USER_ROLE = '<?php echo $_SESSION['role'] ?? ""; ?>';
        const IS_SUPERADMIN = <?php echo (in_array($_SESSION['role'] ?? '', ['superadmin', 'controller'], true)) ? 'true' : 'false'; ?>;
        const ADMIN_NAME = <?php echo json_encode($_SESSION['name'] ?? 'Admin'); ?>;
        let finishTaskId = null;
        let finishMode = 'completed';
        let taskData = [];
        let currentPage = 1;
        let rowsPerPage = 25;
        let sortColumn = 'date';
        let sortDirection = 'desc';

        async function loadTasks() {
            try {
                const start = document.getElementById('filterStart').value;
                const end = document.getElementById('filterEnd').value;
                const vendor = document.getElementById('filterVendor').value;
                const status = document.getElementById('filterStatus').value;
                const search = document.getElementById('searchInput').value;

                const query = `&start=${start}&end=${end}&vendor_id=${vendor}&status=${status}&search=${search}`;
                const res = await fetch(`${API}?action=get_expedisi_tasks${query}`);
                const data = await res.json();
                taskData = data;
                applySorting();
                currentPage = 1;
                renderTaskTable();
            } catch (e) {
                console.error(e);
            }
        }

        function renderTaskTable() {
            try {
                updateSortIndicators();
                const data = taskData;
                const body = document.getElementById('taskBody');
                const total = data.length;
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                const pagedData = data.slice(start, end);

                // Update Pagination Buttons
                document.getElementById('btnPrev').disabled = currentPage <= 1;
                document.getElementById('btnNext').disabled = end >= total;

                document.getElementById('dataCount').innerText = `${total} data`;

                if (total === 0) {
                    body.innerHTML = '<tr><td colspan="11" style="text-align:center; padding:2rem; color:var(--text-muted);">Belum ada data expedisi.</td></tr>';
                    document.querySelector('.pagination-bar').style.display = 'none';
                    return;
                }

                document.querySelector('.pagination-bar').style.display = 'flex';

                if (!pagedData.length && data.length > 0 && currentPage > 1) {
                    currentPage = 1;
                    renderTaskTable();
                    return;
                }

                body.innerHTML = pagedData.map(t => {
                    let statusClass = 'status-pending';
                    if (t.status === 'in_transit') statusClass = 'status-transit';
                    if (t.status === 'completed') statusClass = 'status-completed';
                    if (t.status === 'canceled') statusClass = 'status-canceled';

                    let actions = '';
                    if (t.status === 'pending') {
                        if (CAN_WRITE) {
                            actions = `<button class="btn-icon" style="color:var(--primary);" onclick="startTask(${t.id})" title="Mulai">
                                        <span class="material-symbols-outlined">play_arrow</span>
                                       </button>`;
                        }
                    } else if (t.status === 'in_transit') {
                        if (CAN_WRITE) {
                            actions = `
                                        <button class="btn-icon" style="color:#ef4444;" onclick="openFinishModal(${t.id}, 'canceled')" title="Cancel">
                                            <span class="material-symbols-outlined">block</span>
                                        </button>
                                        <button class="btn-icon" style="color:var(--success);" onclick="openFinishModal(${t.id}, 'completed')" title="Selesai">
                                            <span class="material-symbols-outlined">check_circle</span>
                                        </button>`;
                        }
                    } else {
                        actions = '';
                    }

                    // Format Date dd-mm-yyyy
                    const dt = new Date(t.created_at);
                    const dateFormatted = `${String(dt.getDate()).padStart(2, '0')}-${String(dt.getMonth() + 1).padStart(2, '0')}-${dt.getFullYear()}`;
                    const timeFormatted = `${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`;

                    return `
                        <tr>
                            <td style="font-size:0.8rem; font-weight:600; line-height:1.3;">
                                ${dateFormatted}<br>
                                <small style="color:var(--text-muted); font-weight:400;">${timeFormatted} WIB</small>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <strong>${t.surat_jalan}</strong>
                                    ${t.sj_files && JSON.parse(t.sj_files).length > 0 ? '<span class="material-symbols-outlined" style="font-size:16px; color:var(--primary);">image</span>' : ''}
                                </div>
                                <small style="color:var(--text-muted)">Oleh: ${t.creator_name}</small>
                            </td>
                            <td>
                                <span class="type-badge type-${t.type}">${t.type === 'antar' ? 'Delivery' : 'Pickup'}</span>
                            </td>
                            <td>
                                <div style="font-size:0.85rem;">${t.origin_name} <br> ➔ ${t.destination_name}</div>
                            </td>
                            <td><strong>${t.vendor_name || '-'}</strong></td>
                            <td>${t.total_koli}</td>
                            <td>${t.driver_name}</td>
                            <td>${t.vehicle_plate}</td>
                            <td><span class="status-badge ${statusClass}">${t.status === 'in_transit' ? 'IN TRANSIT' : (t.status === 'canceled' ? 'CANCEL' : t.status.toUpperCase())}</span></td>
                            <td style="font-size:0.75rem; color:var(--text-sub);" title="${t.driver_notes || ''}">
                                <div style="word-wrap: break-word; min-width: 120px;">
                                    ${t.driver_notes || '-'}
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; gap:0.5rem; justify-content:center; align-items:center;">
                                    <button class="btn-icon" onclick="openDetailModal('${t.id}')" title="Lihat Detail">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    ${CAN_WRITE ? `<button class="btn-icon" style="color:var(--warning);" onclick="openEditModal('${t.id}')" title="Edit Tugas">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>` : ''}
                                    ${CAN_WRITE ? (
                            (t.status === 'completed' && !IS_SUPERADMIN)
                                ? `<button class="btn-icon" style="color:var(--text-muted); opacity:0.35; cursor:not-allowed;" disabled title="Hanya Super Admin yang dapat menghapus tugas Selesai">
                                            <span class="material-symbols-outlined">delete</span>
                                           </button>`
                                : `<button class="btn-icon" style="color:var(--danger);" onclick="deleteTask('${t.id}')" title="Hapus Tugas">
                                            <span class="material-symbols-outlined">delete</span>
                                           </button>`
                        ) : ''}
                                    ${actions}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                console.error(e);
            }
        }

        // ===== SORTING FUNCTIONS =====
        function applySorting() {
            const isAsc = sortDirection === 'asc' ? 1 : -1;

            taskData.sort((a, b) => {
                let valA, valB;

                switch (sortColumn) {
                    case 'date':
                        valA = new Date(a.created_at || 0).getTime();
                        valB = new Date(b.created_at || 0).getTime();
                        break;
                    case 'sj':
                        valA = (a.surat_jalan || '').toLowerCase();
                        valB = (b.surat_jalan || '').toLowerCase();
                        break;
                    case 'type':
                        valA = (a.type || '').toLowerCase();
                        valB = (b.type || '').toLowerCase();
                        break;
                    case 'route':
                        valA = ((a.origin_name || '') + ' ' + (a.destination_name || '')).toLowerCase();
                        valB = ((b.origin_name || '') + ' ' + (b.destination_name || '')).toLowerCase();
                        break;
                    case 'vendor':
                        valA = (a.vendor_name || '').toLowerCase();
                        valB = (b.vendor_name || '').toLowerCase();
                        break;
                    case 'koli':
                        valA = parseInt(a.total_koli) || 0;
                        valB = parseInt(b.total_koli) || 0;
                        break;
                    case 'driver':
                        valA = (a.driver_name || '').toLowerCase();
                        valB = (b.driver_name || '').toLowerCase();
                        break;
                    case 'unit':
                        valA = (a.vehicle_plate || '').toLowerCase();
                        valB = (b.vehicle_plate || '').toLowerCase();
                        break;
                    case 'status':
                        valA = (a.status || '').toLowerCase();
                        valB = (b.status || '').toLowerCase();
                        break;
                    case 'notes':
                        valA = (a.driver_notes || '').toLowerCase();
                        valB = (b.driver_notes || '').toLowerCase();
                        break;
                    default:
                        valA = 0;
                        valB = 0;
                }

                if (valA < valB) return -1 * isAsc;
                if (valA > valB) return 1 * isAsc;

                // Fallback secondary sort by creation date (newest first)
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
            renderTaskTable();
        }

        function updateSortIndicators() {
            const columns = ['date', 'sj', 'type', 'route', 'vendor', 'koli', 'driver', 'unit', 'status', 'notes'];
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

        function openAddModal() {
            const modal = document.getElementById('addModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeAdd() {
            const modal = document.getElementById('addModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('addForm').reset();
                document.getElementById('vendorIdInput').value = '';
                document.getElementById('sjPreviewGrid').innerHTML = '';
                document.getElementById('goodsPreviewGrid').innerHTML = '';
                uploadedFiles.sjPreviewGrid = [];
                uploadedFiles.goodsPreviewGrid = [];
            }, 300);
        }

        function openEditModal(id) {
            const task = taskData.find(t => t.id == id);
            if (!task) return;

            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTargetDate').value = task.target_date ? task.target_date.substring(0, 10) : '';
            document.getElementById('editType').value = task.type || 'antar';
            document.getElementById('editVendorIdInput').value = task.vendor_id || '';
            document.getElementById('editVendorSearch').value = task.vendor_name || '';
            document.getElementById('editVehiclePlate').value = task.vehicle_plate || '';
            document.getElementById('editSuratJalan').value = task.surat_jalan || '';
            document.getElementById('editTotalKoli').value = task.total_koli || 1;
            document.getElementById('editOriginSearch').value = task.origin_name || '';
            document.getElementById('editDestSearch').value = task.destination_name || '';
            document.getElementById('editDriverName').value = task.driver_name || '';
            document.getElementById('editNotes').value = task.notes || '';

            // Clear previous edit uploads
            uploadedFiles.editSjPreviewGrid = [];
            uploadedFiles.editGoodsPreviewGrid = [];
            document.getElementById('editSjFileInput').value = '';
            document.getElementById('editGoodsFileInput').value = '';
            document.getElementById('editSjPreviewGrid').innerHTML = '';
            document.getElementById('editGoodsPreviewGrid').innerHTML = '';

            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeEdit() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('editForm').reset();
                document.getElementById('editVendorIdInput').value = '';
                document.getElementById('editSjPreviewGrid').innerHTML = '';
                document.getElementById('editGoodsPreviewGrid').innerHTML = '';
                uploadedFiles.editSjPreviewGrid = [];
                uploadedFiles.editGoodsPreviewGrid = [];
            }, 300);
        }

        function openFinishModal(id, mode = 'completed') {
            finishTaskId = id;
            finishMode = mode;
            document.getElementById('finishId').value = id;
            const modal = document.getElementById('finishModal');
            const title = modal.querySelector('.modal-title');
            const receiverGroup = document.querySelector('#finishForm input[name="receiver_name"]').parentElement;
            const submitBtn = modal.querySelector('button[type="submit"]');

            if (mode === 'canceled') {
                title.innerHTML = '<span class="material-symbols-outlined" style="font-size: 28px; color: var(--danger);">block</span> Laporan Kendala / Cancel';
                receiverGroup.style.display = 'none';
                document.querySelector('#finishForm input[name="receiver_name"]').required = false;
                submitBtn.innerText = 'Kirim Laporan Cancel';
                submitBtn.style.background = '#ef4444';
            } else {
                title.innerHTML = '<span class="material-symbols-outlined" style="font-size: 28px; color: var(--success);">check_circle</span> Selesaikan Tugas';
                receiverGroup.style.display = 'block';
                document.querySelector('#finishForm input[name="receiver_name"]').required = true;
                submitBtn.innerText = 'Konfirmasi Selesai';
                submitBtn.style.background = '';
            }

            modal.style.display = 'flex';

            // Reset photo state
            uploadedFiles.proofPreviewGrid = [];
            document.getElementById('proofFileInput').value = '';
            document.getElementById('proofPreviewGrid').innerHTML = '';

            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeFinish() {
            const modal = document.getElementById('finishModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('finishForm').reset();
                document.getElementById('proofPreviewGrid').innerHTML = '';
                uploadedFiles.proofPreviewGrid = [];
                proofFiles = [];
            }, 300);
        }

        // ==========================================
        // SUBMISSION HANDLERS
        // ==========================================

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            // Build FormData manually for files to ensure watermarked versions are sent
            formData.delete('sj_files[]');
            formData.delete('goods_files[]');

            showToast('Memproses watermark foto...', 'info');

            const info = {
                sj: e.target.querySelector('[name="surat_jalan"]').value,
                vendor: document.getElementById('vendorSearch').value,
                origin: document.getElementById('originSearch').value,
                dest: document.getElementById('destSearch').value,
                driver: e.target.querySelector('[name="driver_name"]').value,
                plate: e.target.querySelector('[name="vehicle_plate"]').value
            };

            const watermarkedSjFiles = [];
            for (const file of uploadedFiles.sjPreviewGrid) {
                const blob = await watermarkFile(file, info, false);
                const webpName = file.name.substring(0, file.name.lastIndexOf('.')) + '.webp';
                watermarkedSjFiles.push(new File([blob], webpName, { type: 'image/webp' }));
            }

            const watermarkedGoodsFiles = [];
            for (const file of uploadedFiles.goodsPreviewGrid) {
                const blob = await watermarkFile(file, info, false);
                const webpName = file.name.substring(0, file.name.lastIndexOf('.')) + '.webp';
                watermarkedGoodsFiles.push(new File([blob], webpName, { type: 'image/webp' }));
            }

            watermarkedSjFiles.forEach(file => formData.append('sj_files[]', file, file.name));
            watermarkedGoodsFiles.forEach(file => formData.append('goods_files[]', file, file.name));

            try {
                const res = await fetch(`${API}?action=add_expedisi_task`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast('Tugas ekspedisi berhasil ditambahkan!');
                    closeAdd();
                    loadTasks();
                } else {
                    showToast(data.error || 'Gagal menambahkan tugas', 'error');
                }
            } catch (e) {
                showToast('Kesalahan koneksi server', 'error');
            }
        };

        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            formData.delete('sj_files[]');
            formData.delete('goods_files[]');

            const info = {
                sj: e.target.querySelector('[name="surat_jalan"]').value,
                vendor: document.getElementById('editVendorSearch').value,
                origin: document.getElementById('editOriginSearch').value,
                dest: document.getElementById('editDestSearch').value,
                driver: e.target.querySelector('[name="driver_name"]').value,
                plate: e.target.querySelector('[name="vehicle_plate"]').value
            };

            if (uploadedFiles.editSjPreviewGrid.length > 0 || uploadedFiles.editGoodsPreviewGrid.length > 0) {
                showToast('Memproses watermark foto...', 'info');
            }

            for (const file of uploadedFiles.editSjPreviewGrid) {
                const blob = await watermarkFile(file, info, false);
                const webpName = file.name.substring(0, file.name.lastIndexOf('.')) + '.webp';
                formData.append('sj_files[]', new File([blob], webpName, { type: 'image/webp' }));
            }

            for (const file of uploadedFiles.editGoodsPreviewGrid) {
                const blob = await watermarkFile(file, info, false);
                const webpName = file.name.substring(0, file.name.lastIndexOf('.')) + '.webp';
                formData.append('goods_files[]', new File([blob], webpName, { type: 'image/webp' }));
            }

            try {
                const res = await fetch(`${API}?action=update_expedisi_task`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast('Tugas ekspedisi berhasil diperbarui!');
                    closeEdit();
                    loadTasks();
                } else {
                    showToast(data.error || 'Gagal memperbarui tugas', 'error');
                }
            } catch (e) {
                showToast('Kesalahan koneksi server', 'error');
            }
        };

        document.getElementById('finishForm').onsubmit = async (e) => {
            e.preventDefault();

            // Validasi foto wajib (hanya untuk mode completed)
            if (finishMode === 'completed' && uploadedFiles.proofPreviewGrid.length === 0) {
                showToast('Foto Bukti Selesai (POD) wajib diunggah!', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('id', finishTaskId);
            formData.append('status', finishMode);
            formData.append('receiver_name', document.querySelector('#finishForm [name="receiver_name"]').value);
            formData.append('driver_notes', document.querySelector('#finishForm [name="driver_notes"]').value);

            showToast('Memproses watermark bukti...', 'info');

            const task = taskData.find(t => t.id == finishTaskId);
            const info = {
                sj: task ? task.surat_jalan : '-',
                vendor: task ? task.vendor_name : '-',
                origin: task ? task.origin_name : '-',
                dest: task ? task.destination_name : '-',
                driver: task ? task.driver_name : '-',
                plate: task ? task.vehicle_plate : '-'
            };

            const watermarkedProofFiles = [];
            for (const file of uploadedFiles.proofPreviewGrid) {
                const blob = await watermarkFile(file, info, true);
                const webpName = file.name.substring(0, file.name.lastIndexOf('.')) + '.webp';
                watermarkedProofFiles.push(new File([blob], webpName, { type: 'image/webp' }));
            }

            // Append POD photos
            watermarkedProofFiles.forEach(file => {
                formData.append('proof_files[]', file, file.name);
            });

            try {
                const res = await fetch(`${API}?action=complete_expedisi_task`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(finishMode === 'completed' ? 'Tugas berhasil diselesaikan!' : 'Tugas berhasil dibatalkan');
                    closeFinish();
                    loadTasks();
                } else {
                    showToast(data.error || 'Gagal memproses tugas', 'error');
                }
            } catch (e) {
                showToast('Kesalahan koneksi server', 'error');
            }
        };

        function openDetailModal(id) {
            const task = taskData.find(t => t.id == id);
            if (!task) return;
            const content = document.getElementById('detailContent');
            const sjFiles = task.sj_files_arr || [];
            const goodsFiles = task.goods_files_arr || [];
            const proofFiles = task.proof_files_arr || [];

            let sjPhotosHtml = sjFiles.map(f => `<div class="preview-item"><img src="uploads/${f}" onclick="viewImage('uploads/${f}')"></div>`).join('');
            let goodsPhotosHtml = goodsFiles.map(f => `<div class="preview-item"><img src="uploads/${f}" onclick="viewImage('uploads/${f}')"></div>`).join('');
            let proofPhotosHtml = proofFiles.map(f => `<div class="preview-item"><img src="uploads/${f}" onclick="viewImage('uploads/${f}')"></div>`).join('');

            content.innerHTML = `
                <table class="detail-table">
                    <tr>
                        <td>No. Surat Jalan</td>
                        <td style="color:var(--primary); font-family:monospace; font-size:1.1rem; font-weight:800;">${task.surat_jalan}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span class="detail-badge status-${task.status}">${task.status.toUpperCase()}</span></td>
                    </tr>
                    <tr>
                        <td>Tipe Tugas</td>
                        <td><span class="detail-badge type-${task.type}">${task.type === 'antar' ? 'DELIVERY' : 'PICKUP'}</span></td>
                    </tr>
                    <tr>
                        <td>Rute</td>
                        <td style="line-height:1.4;">
                            <div style="font-size:0.75rem; color:var(--text-sub);">${task.origin_name}</div>
                            <div style="font-weight:700;">➔ ${task.destination_name}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>Total Koli</td>
                        <td>${task.total_koli} Box/Koli</td>
                    </tr>
                    <tr>
                        <td>Vendor & Unit</td>
                        <td>${task.vendor_name || '-'} (${task.vehicle_plate})</td>
                    </tr>
                    <tr>
                        <td>No. Resi</td>
                        <td>${task.driver_name}</td>
                    </tr>
                    <tr>
                        <td>Penerima</td>
                        <td style="font-weight:800; color:var(--primary);">${task.receiver_name || '-'}</td>
                    </tr>
                    ${task.notes ? `
                    <tr>
                        <td>Catatan Admin</td>
                        <td style="font-weight:400; font-size:0.8rem; color:var(--text-sub); font-style:italic;">"${task.notes}"</td>
                    </tr>` : ''}
                    ${task.driver_notes ? `
                    <tr>
                        <td>Ket. Driver</td>
                        <td style="color:var(--success); font-weight:600;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">chat</span>
                            ${task.driver_notes}
                        </td>
                    </tr>` : ''}
                </table>

                <div style="margin-top:1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">Foto Surat Jalan</div>
                        <div class="image-preview-grid">
                            ${sjPhotosHtml || '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">Tidak ada foto SJ</div>'}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">Foto Kolian / Barang</div>
                        <div class="image-preview-grid">
                            ${goodsPhotosHtml || '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">Tidak ada foto barang</div>'}
                        </div>
                    </div>
                </div>

                ${task.status === 'completed' || task.status === 'canceled' ? `
                    <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px dashed var(--border);">
                        <div style="font-size:0.75rem; font-weight:700; color:var(--success); margin-bottom:8px; text-transform:uppercase;">Bukti Selesai (POD)</div>
                        <div class="image-preview-grid">${proofPhotosHtml || '<p style="color:var(--text-muted);">Belum ada foto.</p>'}</div>
                    </div>
                ` : ''}
            `;
            const modal = document.getElementById('detailModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeDetail() {
            const modal = document.getElementById('detailModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // IMAGE VIEWER LOGIC
        function viewImage(url) {
            const viewer = document.getElementById('imageViewer');
            const img = document.getElementById('viewerImg');
            const dl = document.getElementById('downloadBtn');

            img.src = url;
            dl.onclick = () => {
                const a = document.createElement('a');
                a.href = url;
                a.download = url.split('/').pop();
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            };

            viewer.style.display = 'flex';
            setTimeout(() => viewer.classList.add('show'), 10);
        }

        function closeViewer() {
            const viewer = document.getElementById('imageViewer');
            viewer.classList.remove('show');
            setTimeout(() => viewer.style.display = 'none', 300);
        }

        async function startTask(id) {
            showConfirmToast('Mulai pengiriman sekarang?', async () => {
                const fd = new FormData();
                fd.append('id', id);
                try {
                    const res = await fetch(`${API}?action=start_expedisi_task`, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Tugas berhasil dimulai!');
                        loadTasks();
                    } else {
                        showToast(data.error || 'Gagal memulai tugas', 'error');
                    }
                } catch (e) {
                    showToast('Terjadi kesalahan koneksi', 'error');
                }
            }, 'Ya, Mulai');
        }

        async function deleteTask(id) {
            const task = taskData.find(t => t.id == id);
            if (task && task.status === 'completed' && !IS_SUPERADMIN) {
                showToast('Hanya Super Admin yang dapat menghapus tugas yang sudah selesai', 'error');
                return;
            }
            showConfirmToast('Yakin ingin menghapus tugas ekspedisi ini?', async () => {
                const fd = new FormData();
                fd.append('id', id);
                try {
                    const res = await fetch(`${API}?action=delete_expedisi_task`, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Tugas berhasil dihapus');
                        loadTasks();
                    } else {
                        showToast(data.error || 'Gagal menghapus tugas', 'error');
                    }
                } catch (e) {
                    showToast('Terjadi kesalahan koneksi', 'error');
                }
            }, 'Ya, Hapus');
        }

        function exportToExcel() {
            if (!taskData.length) { alert('Tidak ada data untuk diekspor.'); return; }

            const rows = taskData.map((t, i) => {
                const dt = new Date(t.created_at);
                const dateStr = `${String(dt.getDate()).padStart(2, '0')}-${String(dt.getMonth() + 1).padStart(2, '0')}-${dt.getFullYear()}`;
                const timeStr = `${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`;

                let sts = t.status === 'pending' ? 'Pending' : (t.status === 'in_transit' ? 'Dalam Perjalanan' : (t.status === 'canceled' ? 'Batal' : 'Selesai'));

                const endDt = t.end_time ? new Date(t.end_time) : null;
                const endDateStr = endDt ? `${String(endDt.getDate()).padStart(2, '0')}-${String(endDt.getMonth() + 1).padStart(2, '0')}-${endDt.getFullYear()}` : '-';
                const endTimeStr = endDt ? `${String(endDt.getHours()).padStart(2, '0')}:${String(endDt.getMinutes()).padStart(2, '0')}` : '';

                return {
                    'No': i + 1,
                    'Tipe': t.type === 'antar' ? 'Delivery' : 'Pickup',
                    'Waktu Dibuat': `${dateStr} ${timeStr}`,
                    'Tanggal Jadwal': t.target_date || dateStr,
                    'Vendor': t.vendor_name || '-',
                    'No. Resi': t.driver_name || '-',
                    'Channel': t.vehicle_plate || '-',
                    'No. Surat Jalan': t.surat_jalan,
                    'Total Koli': t.total_koli || 0,
                    'Asal': t.origin_name,
                    'Tujuan': t.destination_name,
                    'Penerima': t.receiver_name || '-',
                    'Waktu Selesai': endDt ? `${endDateStr} ${endTimeStr}` : '-',
                    'Keterangan Driver': t.driver_notes || '-',
                    'Status': sts
                };
            });

            const ws = XLSX.utils.json_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Expedisi Report");
            XLSX.writeFile(wb, `Expedisi_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        function showConfirmToast(message, onOk, okText = 'Ya, Konfirmasi') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast warning`;
            toast.style.minWidth = '320px';
            toast.style.flexDirection = 'column';
            toast.style.alignItems = 'flex-start';
            toast.style.padding = '1rem';

            toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px; width:100%;">
                    <span class="material-symbols-outlined toast-icon" style="color:var(--warning);">help</span>
                    <div class="toast-content" style="flex:1; font-weight:700;">${message}</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:12px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="confirmCancel" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px;">Batal</button>
                    <button class="btn btn-primary" id="confirmOk" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px; background:var(--primary); border:none; box-shadow:none;">${okText}</button>
                </div>
            `;

            container.appendChild(toast);

            toast.querySelector('#confirmOk').onclick = () => {
                onOk();
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
            toast.querySelector('#confirmCancel').onclick = () => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
        }

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
            }, 3000);
        }

        // PHOTO MANAGEMENT SYSTEM
        const uploadedFiles = {
            sjPreviewGrid: [],
            goodsPreviewGrid: [],
            proofPreviewGrid: [],
            editSjPreviewGrid: [],
            editGoodsPreviewGrid: []
        };

        // Simple direct photo handler for finish form (no watermark)
        function handleProofPhotos(input) {
            const files = Array.from(input.files);
            if (!files.length) return;

            // Add to array
            files.forEach(f => uploadedFiles.proofPreviewGrid.push(f));

            // Show previews
            const grid = document.getElementById('proofPreviewGrid');
            grid.innerHTML = '';
            uploadedFiles.proofPreviewGrid.forEach((file, idx) => {
                const url = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.style.cssText = 'aspect-ratio:1;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;position:relative;';
                div.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" onclick="removeProofPhoto(${idx})"
                        style="position:absolute;top:3px;right:3px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:1;">×</button>`;
                grid.appendChild(div);
            });
        }

        function removeProofPhoto(idx) {
            uploadedFiles.proofPreviewGrid.splice(idx, 1);
            // Reset input then re-render
            document.getElementById('proofFileInput').value = '';
            const grid = document.getElementById('proofPreviewGrid');
            grid.innerHTML = '';
            uploadedFiles.proofPreviewGrid.forEach((file, i) => {
                const url = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.style.cssText = 'aspect-ratio:1;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;position:relative;';
                div.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" onclick="removeProofPhoto(${i})"
                        style="position:absolute;top:3px;right:3px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:1;">×</button>`;
                grid.appendChild(div);
            });
        }

        async function watermarkFile(file, info, isFinish) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = URL.createObjectURL(file);
                img.onerror = () => reject(new Error("Gagal memuat gambar"));
                img.onload = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        const maxDim = 1200;
                        let w = img.width;
                        let h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) { h = (h / w) * maxDim; w = maxDim; }
                            else { w = (w / h) * maxDim; h = maxDim; }
                        }

                        canvas.width = w;
                        canvas.height = h;
                        ctx.drawImage(img, 0, 0, w, h);

                        const fontSize = Math.max(16, Math.floor(w / 35));
                        ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                        ctx.shadowColor = 'rgba(0,0,0,0.6)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetX = 2;
                        ctx.shadowOffsetY = 2;

                        const receiver = isFinish ? document.querySelector('#finishForm [name="receiver_name"]').value : '';

                        const lines = [
                            `NO. SJ: ${info.sj || '-'}`,
                            `VND: ${info.vendor || '-'}`,
                            `ROUTE: ${info.origin || '-'} >> ${info.dest || '-'}`,
                            `DRV: ${info.driver || '-'} (${info.plate || '-'})`,
                            `WAKTU: ${new Date().toLocaleString('id-ID')}`
                        ];

                        if (isFinish && receiver) {
                            lines.splice(4, 0, `RCV: ${receiver}`);
                        }

                        const padding = fontSize;
                        const lineSpacing = fontSize * 0.4;
                        const boxHeight = (lines.length * (fontSize + lineSpacing)) + (padding * 2);

                        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                        ctx.fillRect(0, h - boxHeight, w, boxHeight);
                        ctx.fillStyle = '#6366f1';
                        ctx.fillRect(0, h - boxHeight, 4, boxHeight);

                        ctx.fillStyle = 'white';
                        ctx.textAlign = 'left';
                        lines.forEach((line, i) => {
                            ctx.fillText(line, padding + 10, h - boxHeight + padding + (i * (fontSize + lineSpacing)) + fontSize);
                        });

                        canvas.toBlob((blob) => resolve(blob), 'image/webp', 0.82);
                    } catch (err) {
                        reject(err);
                    }
                };
            });
        }

        function renderPreviews(gridId, inputId) {
            const grid = document.getElementById(gridId);
            const input = document.getElementById(inputId);
            const files = uploadedFiles[gridId];

            grid.innerHTML = '';
            const dt = new DataTransfer();

            files.forEach((file, index) => {
                dt.items.add(file);
                const url = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${url}">
                    <button type="button" class="remove-btn" onclick="removeImage('${gridId}', '${inputId}', ${index})">&times;</button>
                `;
                grid.appendChild(div);
            });

            input.files = dt.files;
        }

        function removeImage(gridId, inputId, index) {
            uploadedFiles[gridId].splice(index, 1);
            renderPreviews(gridId, inputId);
        }

        async function handleImagePreview(input, gridId) {
            const files = Array.from(input.files);
            if (files.length === 0) return;

            for (const file of files) {
                uploadedFiles[gridId].push(file);
            }
            renderPreviews(gridId, input.id);
        }

        let allVendors = [];
        let allLocations = [];

        function initSearchableDropdown(inputId, resultsId, data, labelKey, onSelect) {
            const input = document.getElementById(inputId);
            const results = document.getElementById(resultsId);

            input.addEventListener('input', () => {
                const val = input.value.toLowerCase();
                const filtered = data.filter(item => item[labelKey].toLowerCase().includes(val));

                results.innerHTML = '';
                if (filtered.length > 0) {
                    filtered.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'search-item';
                        div.textContent = item[labelKey];
                        div.onclick = () => {
                            input.value = item[labelKey];
                            results.classList.remove('show');
                            if (onSelect) onSelect(item);
                        };
                        results.appendChild(div);
                    });
                    results.classList.add('show');
                } else {
                    results.innerHTML = '<div class="search-item no-results">Tidak ditemukan</div>';
                    results.classList.add('show');
                }
            });

            input.addEventListener('focus', () => {
                if (input.value === '') {
                    results.innerHTML = '';
                    data.slice(0, 10).forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'search-item';
                        div.textContent = item[labelKey];
                        div.onclick = () => {
                            input.value = item[labelKey];
                            results.classList.remove('show');
                            if (onSelect) onSelect(item);
                        };
                        results.appendChild(div);
                    });
                    results.classList.add('show');
                }
            });

            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !results.contains(e.target)) {
                    results.classList.remove('show');
                }
            });
        }

        async function loadFormData() {
            try {
                const [locs, vendors] = await Promise.all([
                    fetch(`${API}?action=get_locations`).then(r => r.json()),
                    fetch(`${API}?action=get_expedisi_vendors`).then(r => r.json())
                ]);

                allLocations = locs;
                allVendors = vendors;

                // Init Searchable Dropdowns
                initSearchableDropdown('vendorSearch', 'vendorResults', allVendors, 'name', (vendor) => {
                    document.getElementById('vendorIdInput').value = vendor.id;
                });

                initSearchableDropdown('originSearch', 'originResults', allLocations, 'name');
                initSearchableDropdown('destSearch', 'destResults', allLocations, 'name');

                // Init Searchable Dropdowns for Edit Form
                initSearchableDropdown('editVendorSearch', 'editVendorResults', allVendors, 'name', (vendor) => {
                    document.getElementById('editVendorIdInput').value = vendor.id;
                });

                initSearchableDropdown('editOriginSearch', 'editOriginResults', allLocations, 'name');
                initSearchableDropdown('editDestSearch', 'editDestResults', allLocations, 'name');

                const filterVendor = document.getElementById('filterVendor');
                vendors.forEach(v => {
                    const opt = `<option value="${v.id}">${v.name}</option>`;
                    filterVendor.insertAdjacentHTML('beforeend', opt);
                });
            } catch (e) { console.error('Error loading form data', e); }
        }

        function changePage(delta) {
            currentPage += delta;
            renderTaskTable();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
            currentPage = 1;
            renderTaskTable();
        }

        (function checkUrlParams() {
            const params = new URLSearchParams(window.location.search);
            const search = params.get('search');
            if (search) {
                const searchInp = document.getElementById('searchInput');
                const start = document.getElementById('filterStart');
                const end = document.getElementById('filterEnd');
                if (searchInp) searchInp.value = search;
                if (start) start.value = '';
                if (end) end.value = '';
            }
        })();
        loadTasks();
        loadFormData();
    </script>
    <div id="toastContainer"></div>
    <script>
        (async function updateTabBadges() {
            const badgeWH = document.getElementById('tabBadgeWH');
            const badgeExp = document.getElementById('tabBadgeExp');
            if (!badgeWH || !badgeExp) return;

            try {
                const res = await fetch('api.php?action=get_pending_counts');
                const data = await res.json();

                if (data.pickup > 0) {
                    badgeWH.textContent = data.pickup;
                    badgeWH.style.display = 'inline-flex';
                } else {
                    badgeWH.style.display = 'none';
                }

                if (data.expedisi > 0) {
                    badgeExp.textContent = data.expedisi;
                    badgeExp.style.display = 'inline-flex';
                } else {
                    badgeExp.style.display = 'none';
                }
            } catch (e) {
                console.error('Tab badge error:', e);
            }
            setTimeout(updateTabBadges, 15000);
        })();
    </script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
</body>

</html>