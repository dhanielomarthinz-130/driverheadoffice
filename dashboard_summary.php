<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('dashboard');
$view = $_GET['view'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view == 'antar' ? 'Monitor Delivery' : ($view == 'kirim' ? 'Monitor Pickup' : 'Dashboard'); ?> |
        TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        /* ── KPI Cards ── */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: var(--card);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
            transition: .25s cubic-bezier(.4, 0, .2, 1);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 16px 16px 0 0;
        }

        .kpi-card.kpi-blue::before {
            background: linear-gradient(90deg, #6366f1, #818cf8);
        }

        .kpi-card.kpi-green::before {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .kpi-card.kpi-amber::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .kpi-card.kpi-red::before {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .kpi-card.kpi-blue .kpi-icon {
            background: #eef2ff;
            color: #6366f1;
        }

        .kpi-card.kpi-green .kpi-icon {
            background: #ecfdf5;
            color: #10b981;
        }

        .kpi-card.kpi-amber .kpi-icon {
            background: #fffbeb;
            color: #f59e0b;
        }

        .kpi-card.kpi-red .kpi-icon {
            background: #fef2f2;
            color: #ef4444;
        }

        .kpi-val {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
            color: var(--text);
        }

        .kpi-lbl {
            font-size: .68rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-top: 4px;
        }

        /* ── Text Size Refinement ── */
        .summary-table {
            font-size: 0.7rem;
        }

        .summary-table th {
            font-size: 0.62rem !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left !important;
        }

        .summary-table td {
            padding: 0.5rem 0.6rem !important;
            text-align: left !important;
        }

        .plate-tag {
            font-size: 0.6rem !important;
            padding: 1px 5px !important;
        }

        .badge {
            font-size: 0.6rem !important;
            padding: 1.5px 5px !important;
            font-weight: 700;
        }

        .duration-pill {
            font-size: 0.65rem !important;
            padding: 1.5px 6px !important;
            white-space: nowrap;
        }

        .vehicle-card-item {
            font-size: 0.7rem !important;
            padding: 0.65rem 0.8rem !important;
        }

        .kpi-val {
            font-size: 1.4rem !important;
        }

        .kpi-card {
            padding: 0.85rem 1.1rem !important;
        }

        .card-header .card-title {
            font-size: 0.85rem !important;
        }

        /* ── Dashboard Grid ── */
        .grid-dashboard {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 1.5rem;
        }

        @media (max-width: 1100px) {
            .grid-dashboard {
                grid-template-columns: 1fr;
            }

            .kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .vehicle-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Map modal */
        #mapModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(6px);
            padding: 2rem;
        }

        .modal-map-content {
            background: white;
            padding: 1.5rem;
            border-radius: 1.25rem;
            width: 100%;
            max-width: 860px;
            height: 75vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-map-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .modal-map-header h3 {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
        }

        .modal-map-header h3 .material-symbols-outlined {
            color: var(--primary);
            font-size: 20px;
        }

        #popupMap {
            flex: 1;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
        }

        #modalDriverStatus {
            margin-top: 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* ── Table tweaks ── */
        #deliveryTable thead th,
        #summaryTableBody th {
            white-space: nowrap;
        }

        #deliveryTable tbody td {
            vertical-align: middle;
        }

        .time-cell {
            font-size: 0.78rem;
            color: var(--text-sub);
            font-weight: 500;
            white-space: nowrap;
        }

        .time-cell .time-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: block;
        }

        .time-cell .time-hour {
            font-weight: 700;
            color: var(--text);
        }

        .duration-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            border-radius: 9999px;
            padding: 0.15rem 0.6rem;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .duration-pill .material-symbols-outlined {
            font-size: 13px;
        }

        .btn-excel {
            background: #16a34a;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(22, 163, 74, .3);
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: .2s;
        }

        .btn-excel:hover {
            background: #15803d;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(22, 163, 74, .4);
        }

        .badge-canceled {
            background: #fee2e2;
            color: #991b1b;
        }

        .row-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 9999px;
            padding: .2rem .75rem;
            font-size: .75rem;
            font-weight: 700;
        }

        /* ── Vehicle Cards ── */
        .vehicle-card-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .875rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: .5rem;
            background: var(--bg);
            transition: .2s;
        }

        .vehicle-card-item:last-child {
            margin-bottom: 0;
        }

        .vehicle-card-item:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, .03);
        }

        .vehicle-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .vehicle-info {
            flex: 1;
            min-width: 0;
        }

        .vehicle-info strong {
            font-size: .88rem;
            font-weight: 700;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .vehicle-info small {
            font-size: .72rem;
            color: var(--text-muted);
        }

        .vehicle-dest {
            font-size: .72rem;
            color: var(--primary);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f1f5f9;
            padding: 0.4rem;
            border-radius: 12px;
            width: fit-content;
            border: 1px solid var(--border);
        }

        .tab-link {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-link .material-symbols-outlined {
            font-size: 18px;
        }

        .tab-link.active {
            background: #fff;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <?php
                    if ($view == 'antar')
                        echo '<span class="material-symbols-outlined">package</span>';
                    elseif ($view == 'kirim')
                        echo '<span class="material-symbols-outlined">local_shipping</span>';
                    elseif ($view == 'expedisi')
                        echo '<span class="material-symbols-outlined">local_post_office</span>';
                    else
                        echo '<span class="material-symbols-outlined">dashboard</span>';
                    ?>
                </div>
                <div>
                    <h1>
                        <?php
                        if ($view == 'antar')
                            echo 'Monitoring Delivery';
                        elseif ($view == 'kirim')
                            echo 'Monitoring Pickup';
                        elseif ($view == 'expedisi')
                            echo 'Monitoring Expedisi';
                        else
                            echo 'Dashboard Activity Hari Ini';
                        ?>
                    </h1>
                    <p>
                        <?php
                        if ($view == 'antar')
                            echo 'Pantau semua pengiriman delivery hari ini';
                        elseif ($view == 'kirim')
                            echo 'Pantau semua pengiriman pickup hari ini';
                        elseif ($view == 'expedisi')
                            echo 'Pantau semua pengiriman pihak ketiga (Expedisi)';
                        else
                            echo 'Ringkasan aktivitas armada hari ini';
                        ?>
                    </p>
                </div>
            </div>
            <?php if ($view == ''): ?>
                <button class="btn btn-primary" onclick="loadData()">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh
                </button>
            <?php endif; ?>
        </div>

        <?php if ($view != ''):
            // Determine context (from Delivery or Pickup)
            $from = $_GET['from'] ?? ($view == 'antar' ? 'antar' : 'kirim');
            ?>
            <!-- TABS removed -->
        <?php endif; ?>

        <?php if ($view != ''): ?>
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="date_from" onchange="loadData()">
                </div>
                <div class="filter-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" id="date_to" onchange="loadData()">
                </div>
                <div class="filter-group">
                    <label><?php echo $view === 'expedisi' ? 'Vendor' : 'Driver'; ?></label>
                    <select id="driver_id_filter" onchange="loadData()">
                        <option value=""><?php echo $view === 'expedisi' ? 'Semua Vendor' : 'Semua Driver'; ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="status_filter" onchange="loadData()">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_transit">In Transit</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button class="btn btn-ghost" onclick="resetFilters()">
                    <span class="material-symbols-outlined">filter_list_off</span>
                    Semua Tanggal
                </button>
            </div>
        <?php endif; ?>

        <?php if ($view == ''): ?>
            <!-- TABS removed -->

            <!-- KPI Summary Cards -->
            <div class="kpi-row" id="kpiRow">
                <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"><span class="material-symbols-outlined" id="kpiIcon1">group</span></div>
                    <div>
                        <div class="kpi-val" id="kpiDrivers">—</div>
                        <div class="kpi-lbl" id="kpiLbl1">Driver Aktif</div>
                    </div>
                </div>
                <div class="kpi-card kpi-amber">
                    <div class="kpi-icon"><span class="material-symbols-outlined">local_shipping</span></div>
                    <div>
                        <div class="kpi-val" id="kpiTransit">—</div>
                        <div class="kpi-lbl">Tugas Aktif</div>
                    </div>
                </div>
                <div class="kpi-card kpi-green">
                    <div class="kpi-icon"><span class="material-symbols-outlined">task_alt</span></div>
                    <div>
                        <div class="kpi-val" id="kpiDone">—</div>
                        <div class="kpi-lbl">Selesai Hari Ini</div>
                    </div>
                </div>
                <div class="kpi-card kpi-red">
                    <div class="kpi-icon"><span class="material-symbols-outlined" id="kpiIcon4">garage_home</span></div>
                    <div>
                        <div class="kpi-val" id="kpiIdle">—</div>
                        <div class="kpi-lbl" id="kpiLbl4">Kendaraan Ready</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid-dashboard">
                <!-- Summary Table -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-outlined" id="tableIcon">groups</span>
                            <span id="tableTitle">Aktivitas Driver (Hari Ini)</span>
                        </span>
                        <span id="autoRefreshBadge"
                            style="display:inline-flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--text-muted);">
                            <span class="pulse-dot"></span> Auto-refresh 10s
                        </span>
                    </div>
                    <div class="table-wrapper">
                        <table class="summary-table">
                            <thead>
                                <tr id="summaryTableHeader">
                                    <th>Nama Driver</th>
                                    <th>Penumpang</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Tujuan</th>
                                    <th>Mulai</th>
                                    <th>Durasi</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody">
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <span class="material-symbols-outlined">autorenew</span>
                                            <p>Memuat data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vehicles Panel (Only shown in WH Driver tab) -->
                <div class="vehicle-section" id="vehiclePanel">
                    <!-- Sedang Beroperasi -->
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <div class="card-header"
                            style="background: linear-gradient(135deg,#fef2f2,#fee2e2); border-bottom: 1px solid #fecaca; border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                            <span class="card-title" style="color:#991b1b; font-size:.9rem;">
                                <span class="material-symbols-outlined"
                                    style="color:#ef4444; font-size:18px;">local_shipping</span>
                                Sedang Beroperasi
                            </span>
                            <span id="activeCount" class="badge"
                                style="background:#ef4444; color:#fff; font-size:.68rem;">0</span>
                        </div>
                        <div id="activeVehiclesList" style="padding: .75rem 1rem;">
                            <div class="empty-state" style="padding:1.5rem;">
                                <span class="material-symbols-outlined">autorenew</span>
                                <p>Memuat...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Unit Siaga -->
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <div class="card-header"
                            style="background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-bottom: 1px solid #bbf7d0; border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                            <span class="card-title" style="color:#065f46; font-size:.9rem;">
                                <span class="material-symbols-outlined"
                                    style="color:#10b981; font-size:18px;">check_circle</span>
                                Unit Siaga (Ready)
                            </span>
                            <span id="idleCount" class="badge badge-success" style="font-size:.68rem;">0</span>
                        </div>
                        <div id="idleVehiclesList" style="padding: .75rem 1rem;">
                            <div class="empty-state" style="padding:1.5rem;">
                                <span class="material-symbols-outlined">autorenew</span>
                                <p>Memuat...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Delivery List -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-outlined">list_alt</span>
                        Daftar Pengiriman —
                        <?php
                        if ($view === 'antar')
                            echo 'Delivery';
                        elseif ($view === 'kirim')
                            echo 'Pickup';
                        else
                            echo 'Expedisi';
                        ?>
                        <span class="row-count-badge" id="rowCountBadge" style="display:none;">
                            <span class="material-symbols-outlined" style="font-size:14px;">table_rows</span>
                            <span id="rowCountNum">0</span> data
                        </span>
                    </span>
                    <button class="btn-excel" onclick="exportToExcel()" id="exportBtn" style="display:none;"
                        title="Export Data ke Excel">
                        <span class="material-symbols-outlined">table_view</span>
                    </button>
                </div>
                <div class="table-wrapper">
                    <table id="deliveryTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo $view === 'expedisi' ? 'Vendor / Driver' : 'Driver'; ?></th>
                                <th>Kendaraan</th>
                                <th>Asal</th>
                                <th>Tujuan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Durasi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="10" class="empty-state">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Map Modal -->
    <div id="mapModal" onclick="if(event.target===this)closeMapModal()">
        <div class="modal-map-content">
            <div class="modal-map-header">
                <h3>
                    <span class="material-symbols-outlined">my_location</span>
                    <span id="modalDriverName">Tracking Driver</span>
                </h3>
                <button class="modal-close" onclick="closeMapModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="popupMap"></div>
            <div id="modalDriverStatus"></div>
        </div>
    </div>

    <!-- SheetJS for Excel export (CDN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        const API_URL = 'api.php';
        let popupMap = null, mapMarker = null;
        let deliveryData = []; // store for export
        let currentDashboardTab = 'wh'; // 'wh' or 'exp'
        let isLoadingData = false; // prevent concurrent loads

        // ── Helpers ─────────────────────────────────────────────────────────
        function todayStr() {
            return "<?php echo date('Y-m-d'); ?>";
        }

        function formatDateTime(raw) {
            if (!raw || raw === '-' || raw === null) return { date: '—', time: '—' };
            const d = new Date(raw);
            if (isNaN(d)) return { date: raw, time: '' };
            const date = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return { date, time };
        }

        function timeCellHtml(raw) {
            if (!raw || raw === '-' || raw === null)
                return `<span style="color:var(--text-muted);">—</span>`;
            const { date, time } = formatDateTime(raw);
            return `<span class="time-cell"><span class="time-hour">${time}</span><span class="time-date">${date}</span></span>`;
        }

        function statusLabel(s) {
            if (!s) return '—';
            return s.replace('_', ' ').toUpperCase();
        }

        function shortenDuration(dur) {
            if (!dur) return '—';
            // Already formatted like "2 Jam 15 Menit" from SQL CASE or raw string
            return String(dur)
                .replace(' Jam ', 'j ')
                .replace(' Menit', 'm')
                .replace(' hours', 'j')
                .replace(' hour', 'j')
                .replace(' minutes', 'm')
                .replace(' minute', 'm');
        }

        // ── Tab Switcher ─────────────────────────────────────────────────────
        function switchDashboardTab(tab) {
            currentDashboardTab = tab;

            // Update Tab UI
            document.getElementById('tabWh').classList.toggle('active', tab === 'wh');
            document.getElementById('tabExp').classList.toggle('active', tab === 'exp');

            // Update Headers & UI Elements
            const vehiclePanel = document.getElementById('vehiclePanel');
            const tableTitle = document.getElementById('tableTitle');
            const tableIcon = document.getElementById('tableIcon');
            const kpiLbl1 = document.getElementById('kpiLbl1');
            const kpiIcon1 = document.getElementById('kpiIcon1');
            const kpiLbl4 = document.getElementById('kpiLbl4');
            const kpiIcon4 = document.getElementById('kpiIcon4');
            const headerRow = document.getElementById('summaryTableHeader');
            const kpiRow = document.getElementById('kpiRow');

            if (tab === 'wh') {
                document.querySelector('.grid-dashboard').style.gridTemplateColumns = window.innerWidth > 1100 ? '1fr 380px' : '1fr';
                vehiclePanel.style.display = 'flex';
                tableTitle.innerText = 'Aktivitas Driver (Hari Ini)';
                tableIcon.innerText = 'groups';
                kpiLbl1.innerText = 'Driver Aktif';
                kpiIcon1.innerText = 'group';
                kpiLbl4.innerText = 'Kendaraan Ready';
                kpiIcon4.innerText = 'garage_home';
                headerRow.innerHTML = `
                    <th>Nama Driver</th>
                    <th>Penumpang</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Tujuan</th>
                    <th>Mulai</th>
                    <th>Durasi</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                `;
            } else {
                document.querySelector('.grid-dashboard').style.gridTemplateColumns = '1fr';
                vehiclePanel.style.display = 'none';
                tableTitle.innerText = 'Aktivitas Expedisi (Hari Ini)';
                tableIcon.innerText = 'local_post_office';
                kpiLbl1.innerText = 'Total Expedisi';
                kpiIcon1.innerText = 'apartment';
                kpiLbl4.innerText = 'Tugas Pending';
                kpiIcon4.innerText = 'pending_actions';
                headerRow.innerHTML = `
                    <th>Vendor / Driver</th>
                    <th>No. SJ</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Tujuan</th>
                    <th>Mulai</th>
                    <th>Durasi</th>
                    <th>Jml Koli</th>
                    <th>Tanggal</th>
                `;
            }

            // Clear table and show loading
            document.getElementById('summaryTableBody').innerHTML = `
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <span class="material-symbols-outlined">autorenew</span>
                            <p>Memuat data...</p>
                        </div>
                    </td>
                </tr>
            `;

            isLoadingData = false; // force fresh load on tab switch
            loadData();
        }

        // ── Map modal ────────────────────────────────────────────────────────
        function openMapModal(lat, lng, name, statusHtml) {
            document.getElementById('mapModal').style.display = 'flex';
            document.getElementById('modalDriverName').innerText = name;
            document.getElementById('modalDriverStatus').innerHTML = statusHtml;
            if (!popupMap) {
                popupMap = L.map('popupMap').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(popupMap);
            } else {
                popupMap.setView([lat, lng], 15);
            }
            if (mapMarker) popupMap.removeLayer(mapMarker);
            mapMarker = L.marker([lat, lng]).addTo(popupMap).bindPopup(name).openPopup();
            setTimeout(() => popupMap.invalidateSize(), 300);
        }
        function closeMapModal() { document.getElementById('mapModal').style.display = 'none'; }

        // ── Filters ──────────────────────────────────────────────────────────
        function resetFilters() {
            const df = document.getElementById('date_from');
            const dt = document.getElementById('date_to');
            if (df) df.value = '';
            if (dt) dt.value = '';
            const drvEl = document.getElementById('driver_id_filter');
            const stEl = document.getElementById('status_filter');
            if (drvEl) drvEl.value = '';
            if (stEl) stEl.value = '';
            loadData();
        }

        // ── Load Data ────────────────────────────────────────────────────────
        async function loadData() {
            if (isLoadingData) return; // prevent race condition
            isLoadingData = true;
            try {
                const view = new URLSearchParams(window.location.search).get('view') || '';

                // Load drivers/vendors for filter dropdown (only once)
                const filterSel = document.getElementById('driver_id_filter');
                if (filterSel && filterSel.options.length <= 1) {
                    if (view === 'expedisi') {
                        const vendorRes = await fetch(`${API_URL}?action=get_expedisi_vendors`);
                        const vendors = await vendorRes.json();
                        vendors.forEach(v => {
                            filterSel.innerHTML += `<option value="${v.name}">${v.name}</option>`;
                        });
                    } else {
                        const driverRes = await fetch(`${API_URL}?action=get_drivers`);
                        const drivers = await driverRes.json();
                        drivers.forEach(d => {
                            filterSel.innerHTML += `<option value="${d.user_id}">${d.driver_name}</option>`;
                        });
                    }
                }

                // ── Dashboard main view ──────────────────────────────────────
                if (view === '') {
                    if (currentDashboardTab === 'wh') {
                        const sumRes = await fetch(`${API_URL}?action=get_driver_summary`);
                        const summary = await sumRes.json();
                        const sumBody = document.getElementById('summaryTableBody');

                        sumBody.innerHTML = summary.length === 0
                            ? `<tr><td colspan="9"><div class="empty-state">
                                <span class="material-symbols-outlined">sentiment_dissatisfied</span>
                                <p>Belum ada aktivitas driver hari ini.</p>
                               </div></td></tr>`
                            : summary.map(s => {
                                const isTransit = s.task_status === 'in_transit';
                                const isPending = s.task_status === 'pending';
                                const isActive = isTransit || isPending;
                                const statusCls = isTransit ? 'badge-transit' : (isPending ? 'badge-pending' : (s.task_status === 'completed' ? 'badge-completed' : (s.task_status === 'canceled' ? 'badge-danger' : 'badge-warning')));
                                const statusLabel = (s.task_status || 'OTW BALIK KE WH').replace('_', ' ').toUpperCase();
                                const typeLabel = s.task_type === 'antar' ? 'Antar' : 'Jemput';
                                const typeColor = s.task_type === 'antar' ? 'var(--primary)' : 'var(--warning)';
                                const typeBg = s.task_type === 'antar' ? 'rgba(99,102,241,0.1)' : 'rgba(245,158,11,0.1)';

                                return `
                                <tr>
                                    <td style="white-space:nowrap;"><strong>${s.driver_name || 'Tanpa Nama'}</strong></td>
                                    <td style="white-space:nowrap;"><strong>${s.passenger_name || '—'}</strong></td>
                                    <td>
                                        <span class="badge" style="background:${typeBg}; color:${typeColor}; border:1px solid ${typeColor}20;">
                                            ${typeLabel}
                                        </span>
                                    </td>
                                    <td><span class="badge ${statusCls}">${statusLabel}</span></td>
                                    <td style="font-size:0.8rem; font-weight:600; color:var(--primary);">
                                        ${s.current_dest ? `<a href="assign_tasks.php?search=${s.surat_jalan || ''}" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:4px;">
                                            <span class="material-symbols-outlined" style="font-size:14px;">near_me</span>${s.current_dest}
                                        </a>` : `<span style="color:var(--text-muted); font-weight:400;">—</span>`}
                                    </td>
                                    <td style="white-space:nowrap; text-align:center;">
                                        <div style="font-weight:700; color:var(--text);">${s.active_start_time ? formatDateTime(s.active_start_time).time : '—'}</div>
                                    </td>
                                    <td>
                                        ${isTransit && s.active_start_time
                                        ? `<span class="duration-pill live-timer" data-start="${s.active_start_time}"><span class="material-symbols-outlined">schedule</span><span class="timer-val">00:00:00</span></span>`
                                        : (s.task_duration ? `<span class="duration-pill"><span class="material-symbols-outlined">schedule</span>${shortenDuration(s.task_duration)}</span>` : '—')}
                                    </td>
                                    <td style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-align:center; white-space:nowrap;">
                                        ${s.active_start_time ? formatDateTime(s.active_start_time).date : '—'}
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" style="padding:0.35rem 0.75rem; font-size:0.75rem;"
                                            onclick="trackDriverByName('${(s.driver_name || '').replace(/'/g, "\\'")}')">
                                            <span class="material-symbols-outlined" style="font-size:14px;">my_location</span> Lacak
                                        </button>
                                    </td>
                                </tr>`}).join('');

                        // Vehicles (WH only)
                        const vehRes = await fetch(`${API_URL}?action=get_vehicles`);
                        const vehicles = await vehRes.json();
                        let activeHtml = '', idleHtml = '', activeCnt = 0, idleCnt = 0;

                        vehicles.forEach(v => {
                            const hasTask = v.current_dest !== null;
                            const isOnline = v.current_driver_name !== null;

                            if (hasTask || isOnline) {
                                activeCnt++;
                                const statusColor = hasTask ? '#ef4444' : '#f59e0b';
                                const bgColor = hasTask ? '#fee2e2' : '#fffbeb';

                                activeHtml += `
                                <div class="vehicle-card-item">
                                    <div class="vehicle-avatar" style="background:${bgColor}; color:${statusColor};">
                                        <span class="material-symbols-outlined">local_shipping</span>
                                    </div>
                                    <div class="vehicle-info">
                                        <strong>${v.name || 'Kendaraan'}</strong>
                                        <small>${v.plate_number}</small>
                                    </div>
                                    <div style="text-align:right; flex-shrink:0;">
                                        <div style="font-size:.75rem; font-weight:800; color:${statusColor};">${v.current_driver_name || ''}</div>
                                        ${!hasTask ? `<div style="font-size:0.6rem; font-weight:700; color:#f59e0b; margin-top:2px;">OTW BALIK KE WH</div>` : ''}
                                    </div>
                                </div>`;
                            } else {
                                idleCnt++;
                                idleHtml += `
                                <div class="vehicle-card-item">
                                    <div class="vehicle-avatar" style="background:${isOnline ? '#e0f2fe' : '#dcfce7'}; color:${isOnline ? '#0369a1' : '#10b981'};">
                                        <span class="material-symbols-outlined">${isOnline ? 'person' : 'directions_car'}</span>
                                    </div>
                                    <div class="vehicle-info">
                                        <strong>${v.name || 'Kendaraan'}</strong>
                                        <small>${v.plate_number}</small>
                                    </div>
                                    <span class="badge ${isOnline ? 'badge-siaga' : 'badge-success'}" style="font-size:.65rem; flex-shrink:0;">
                                        ${isOnline ? v.current_driver_name : 'READY'}
                                    </span>
                                </div>`;
                            }
                        });

                        document.getElementById('activeVehiclesList').innerHTML = activeHtml ||
                            `<div class="empty-state" style="padding:1.5rem;"><span class="material-symbols-outlined">garage_home</span><p>Belum ada pengiriman aktif.</p></div>`;
                        document.getElementById('idleVehiclesList').innerHTML = idleHtml ||
                            `<div class="empty-state" style="padding:1.5rem;"><span class="material-symbols-outlined">moving</span><p>Tidak ada kendaraan tersedia.</p></div>`;

                        // Update count badges
                        document.getElementById('activeCount').textContent = activeCnt;
                        document.getElementById('idleCount').textContent = idleCnt;

                        // Update KPI cards
                        const uniqueDrivers = [...new Set(summary.map(s => s.user_id))].length;
                        const transitTasks = summary.filter(s => s.task_status === 'in_transit' || s.task_status === 'pending').length;
                        const doneTasks = summary.filter(s => s.task_status === 'completed').length;

                        document.getElementById('kpiDrivers').textContent = uniqueDrivers;
                        document.getElementById('kpiTransit').textContent = transitTasks;
                        document.getElementById('kpiDone').textContent = doneTasks;
                        document.getElementById('kpiIdle').textContent = idleCnt;

                    } else {
                        // ── EXPEDISI TAB LOGIC ──────────────────────────────
                        const sumBody = document.getElementById('summaryTableBody');
                        try {
                            const expRes = await fetch(`${API_URL}?action=get_expedisi_summary`);
                            const rawText = await expRes.text();
                            let summary = [];
                            try {
                                summary = JSON.parse(rawText);
                            } catch (parseErr) {
                                sumBody.innerHTML = `<tr><td colspan="9" style="color:var(--danger); padding:1rem; font-size:0.85rem;">
                                    <strong>Error API:</strong> ${rawText.substring(0, 200)}</td></tr>`;
                                return;
                            }

                            if (!Array.isArray(summary)) {
                                sumBody.innerHTML = `<tr><td colspan="9" style="color:var(--danger); padding:1rem; font-size:0.85rem;">
                                    <strong>Response tidak valid:</strong> ${JSON.stringify(summary).substring(0, 200)}</td></tr>`;
                                return;
                            }

                            if (summary.length === 0) {
                                sumBody.innerHTML = `<tr><td colspan="9"><div class="empty-state">
                                    <span class="material-symbols-outlined">sentiment_dissatisfied</span>
                                    <p>Belum ada aktivitas expedisi hari ini.</p>
                                </div></td></tr>`;
                            } else {
                                sumBody.innerHTML = summary.map(s => {
                                    const isTransit = s.task_status === 'in_transit';
                                    const isPending = s.task_status === 'pending';
                                    const isCompleted = s.task_status === 'completed';
                                    const statusCls = isTransit ? 'badge-transit'
                                        : (isPending ? 'badge-pending'
                                            : (isCompleted ? 'badge-completed'
                                                : (s.task_status === 'canceled' ? 'badge-danger' : 'badge-warning')));
                                    const statusLbl = (s.task_status || '—').replace('_', ' ').toUpperCase();
                                    const typeLabel = (s.task_type === 'antar') ? 'Antar' : 'Jemput';
                                    const typeColor = (s.task_type === 'antar') ? 'var(--primary)' : 'var(--warning)';
                                    const typeBg = (s.task_type === 'antar') ? 'rgba(99,102,241,0.1)' : 'rgba(245,158,11,0.1)';

                                    return `
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text);">${s.vendor_name || '—'}</div>
                                            <div style="font-size:0.7rem; color:var(--text-sub);">${s.driver_name || '—'}</div>
                                        </td>
                                        <td style="font-family:monospace; font-weight:600; font-size:0.8rem;">${s.surat_jalan || '—'}</td>
                                        <td>
                                            <span class="badge" style="background:${typeBg}; color:${typeColor}; border:1px solid ${typeColor}20;">
                                                ${typeLabel}
                                            </span>
                                        </td>
                                        <td><span class="badge ${statusCls}">${statusLbl}</span></td>
                                        <td style="font-size:0.8rem; font-weight:600; color:var(--primary);">
                                            ${s.current_dest
                                            ? `<a href="expedisi_tasks.php?search=${encodeURIComponent(s.surat_jalan || '')}" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:4px;">
                                                    <span class="material-symbols-outlined" style="font-size:14px;">near_me</span>${s.current_dest}
                                                   </a>`
                                            : '—'}
                                        </td>
                                        <td style="white-space:nowrap; text-align:center;">
                                            <div style="font-weight:700; color:var(--text);">${s.active_start_time ? formatDateTime(s.active_start_time).time : '—'}</div>
                                        </td>
                                        <td>
                                            ${isTransit && s.active_start_time
                                            ? `<span class="duration-pill live-timer" data-start="${s.active_start_time}"><span class="material-symbols-outlined">schedule</span><span class="timer-val">00:00:00</span></span>`
                                            : (s.task_duration ? `<span class="duration-pill"><span class="material-symbols-outlined">schedule</span>${shortenDuration(s.task_duration)}</span>` : '—')}
                                        </td>
                                        <td><strong>${s.total_koli || 0}</strong></td>
                                        <td style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-align:center; white-space:nowrap;">
                                            ${s.active_start_time ? formatDateTime(s.active_start_time).date : '—'}
                                        </td>
                                    </tr>`;
                                }).join('');
                            }

                            // Update KPI cards for Expedisi
                            const uniqueVendors = [...new Set(summary.map(s => s.vendor_name).filter(Boolean))].length;
                            const transitTasks = summary.filter(s => s.task_status === 'in_transit').length;
                            const doneTasks = summary.filter(s => s.task_status === 'completed').length;
                            const pendingTasks = summary.filter(s => s.task_status === 'pending').length;

                            document.getElementById('kpiDrivers').textContent = uniqueVendors;
                            document.getElementById('kpiTransit').textContent = transitTasks;
                            document.getElementById('kpiDone').textContent = doneTasks;
                            document.getElementById('kpiIdle').textContent = pendingTasks;

                        } catch (expErr) {
                            sumBody.innerHTML = `<tr><td colspan="9" style="color:var(--danger); padding:1rem; font-size:0.85rem;">
                                <strong>Gagal memuat data expedisi:</strong> ${expErr.message}</td></tr>`;
                        }
                    }

                    // ── Monitoring view (antar / kirim / expedisi) ──────────────────────────
                } else {
                    const dateF = document.getElementById('date_from')?.value || '';
                    const dateT = document.getElementById('date_to')?.value || '';
                    const drvF = document.getElementById('driver_id_filter')?.value || '';
                    const statF = document.getElementById('status_filter')?.value || '';

                    let fetchUrl = '';
                    if (view === 'expedisi') {
                        fetchUrl = `${API_URL}?action=get_expedisi_tasks&start=${dateF}&end=${dateT}&vendor_name=${drvF}&status=${statF}`;
                    } else {
                        fetchUrl = `${API_URL}?action=get_deliveries&type=${view}&date_from=${dateF}&date_to=${dateT}&driver_id=${drvF}&status=${statF}`;
                    }

                    const delRes = await fetch(fetchUrl);
                    const deliveries = await delRes.json();
                    deliveryData = deliveries; // save for export

                    const tbody = document.querySelector('#deliveryTable tbody');
                    const exportBtn = document.getElementById('exportBtn');
                    const countBadge = document.getElementById('rowCountBadge');
                    const countNum = document.getElementById('rowCountNum');

                    if (!deliveries.length) {
                        tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state">
                            <span class="material-symbols-outlined">search_off</span>
                            <p>Data tidak ditemukan untuk filter ini.</p>
                        </div></td></tr>`;
                        exportBtn.style.display = 'none';
                        countBadge.style.display = 'none';
                        return;
                    }

                    countNum.textContent = deliveries.length;
                    countBadge.style.display = 'inline-flex';
                    exportBtn.style.display = 'inline-flex';

                    tbody.innerHTML = deliveries.map((del, i) => {
                        const statusCls = del.status === 'pending' ? 'badge-pending'
                            : del.status === 'in_transit' ? 'badge-transit'
                                : del.status === 'canceled' ? 'badge-canceled' : 'badge-completed';
                        const tglStr = del.created_at || del.target_date;
                        const tgl = tglStr
                            ? new Date(tglStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                            : '—';

                        // Column Mapping
                        const colDriver = view === 'expedisi'
                            ? `<strong>${del.vendor_name || '—'}</strong><br><small style="color:var(--text-muted)">${del.driver_name || '—'}</small>`
                            : `<a href="#" style="color:var(--primary); font-weight:700; text-decoration:none; white-space:nowrap;"
                                onclick="trackDriverByName('${(del.driver_name || '').replace(/'/g, "\\'")}')">
                                ${del.driver_name || '—'}
                               </a>`;

                        const colPlate = `<span class="plate-tag">${del.vehicle_plate || '—'}</span>`;
                        const colOrigin = del.origin_name || '—';
                        const colDest = del.destination_name || '—';

                        return `
                        <tr>
                            <td style="color:var(--text-muted); font-size:0.75rem; font-weight:600; text-align:center;">${i + 1}</td>
                            <td>${colDriver}</td>
                            <td style="white-space:nowrap;">${colPlate}</td>
                            <td style="font-size:0.82rem;">${colOrigin}</td>
                            <td style="font-size:0.82rem; font-weight:600; color:var(--primary);">${colDest}</td>
                            <td style="font-size:0.8rem; color:var(--text-sub); white-space:nowrap;">${tgl}</td>
                            <td><span class="badge ${statusCls}">${statusLabel(del.status)}</span></td>
                            <td>${timeCellHtml(del.start_time)}</td>
                            <td>${timeCellHtml(del.end_time)}</td>
                            <td>${del.status === 'in_transit' ?
                                (del.start_time ? `<span class="duration-pill live-timer" data-start="${del.start_time}"><span class="material-symbols-outlined" style="font-size:12px;">schedule</span><span class="timer-val">00:00:00</span></span>` : '—') :
                                (del.duration ? `<span class="duration-pill"><span class="material-symbols-outlined" style="font-size:12px;">schedule</span>${shortenDuration(del.duration)}</span>` : '—')}
                            </td>
                            <td style="font-size:0.75rem; color:var(--text-muted); font-style:italic; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${del.driver_notes || ''}">
                                ${del.driver_notes || '—'}
                            </td>
                        </tr>`;
                    }).join('');
                }
            } catch (err) {
                console.error('loadData error:', err);
            } finally {
                isLoadingData = false;
            }
        }

        // ── Track driver ─────────────────────────────────────────────────────
        async function trackDriverByName(name) {
            const res = await fetch(`${API_URL}?action=get_drivers`);
            const drivers = await res.json();
            const d = drivers.find(x => x.driver_name === name);
            if (d && d.lat && d.lng) {
                const statusHtml = d.is_shipping == '1'
                    ? `<span class="badge badge-transit"><span class="material-symbols-outlined" style="font-size:14px;">local_shipping</span> Sedang Beroperasi${d.current_destination ? ` ke ${d.current_destination}` : ''}</span>`
                    : `<span class="badge badge-danger"><span class="material-symbols-outlined" style="font-size:14px;">home</span> Sedang Istirahat</span>`;
                openMapModal(d.lat, d.lng, d.driver_name, statusHtml);
            } else {
                alert('Lokasi GPS driver belum tersedia.');
            }
        }

        // ── Duration Format ──────────────────────────────────────────────────
        function shortenDuration(str) {
            if (!str) return '—';
            return str.replace(/Jam/g, 'j').replace(/Menit/g, 'm').replace(/Detik/g, 'd').replace(/\s+/g, ' ');
        }

        // ── Export to Excel ──────────────────────────────────────────────────
        function exportToExcel() {
            if (!deliveryData.length) { alert('Tidak ada data untuk diekspor.'); return; }

            const view = new URLSearchParams(window.location.search).get('view') || 'data';
            const dateF = document.getElementById('date_from')?.value || 'all';
            const dateT = document.getElementById('date_to')?.value || 'all';

            const rows = deliveryData.map((del, i) => {
                const tgl = del.created_at
                    ? new Date(del.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                    : '-';
                const { date: sdDate, time: sdTime } = formatDateTime(del.start_time);
                const { date: edDate, time: edTime } = formatDateTime(del.end_time);
                const rowData = {
                    'No': i + 1
                };
                if (view === 'expedisi') {
                    rowData['Vendor'] = del.vendor_name || '-';
                    rowData['No. Resi'] = del.driver_name || '-';
                } else {
                    rowData['Driver'] = del.driver_name || '-';
                }
                rowData['Kendaraan'] = del.vehicle_plate || '-';
                rowData['Asal'] = del.origin_name;
                rowData['Tujuan'] = del.destination_name;
                rowData['Tanggal'] = tgl;
                rowData['Status'] = statusLabel(del.status);
                rowData['Tgl Mulai'] = sdDate;
                rowData['Waktu Mulai'] = sdTime;
                rowData['Tgl Selesai'] = edDate;
                rowData['Waktu Selesai'] = edTime;
                rowData['Durasi'] = del.duration || '-';
                rowData['Catatan'] = del.driver_notes || '-';
                return rowData;
            });

            const ws = XLSX.utils.json_to_sheet(rows);
            ws['!cols'] = [
                { wch: 5 }, { wch: 20 }, { wch: 12 }, { wch: 24 }, { wch: 24 },
                { wch: 14 }, { wch: 18 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 14 }, { wch: 16 }
            ];
            const wb = XLSX.utils.book_new();
            let sheetName = 'Monitor ';
            if (view === 'antar') sheetName += 'Delivery';
            else if (view === 'kirim') sheetName += 'Pickup';
            else sheetName += 'Expedisi';

            XLSX.utils.book_append_sheet(wb, ws, sheetName);
            XLSX.writeFile(wb, `monitoring_${view}_${dateF}_sd_${dateT}.xlsx`);
        }

        // ── Init ─────────────────────────────────────────────────────────────
        window.userRole = '<?php echo $_SESSION["role"]; ?>';

        // Default filter = hari ini (untuk halaman monitoring saja)
        (function setDefaultDates() {
            const df = document.getElementById('date_from');
            const dt = document.getElementById('date_to');
            const serverToday = "<?php echo date('Y-m-d'); ?>";
            if (df && !df.value) df.value = serverToday;
            if (dt && !dt.value) dt.value = serverToday;
        })();

        function updateLiveTimers() {
            document.querySelectorAll('.live-timer').forEach(el => {
                const startStr = el.getAttribute('data-start');
                if (!startStr) return;

                const start = new Date(startStr.replace(' ', 'T'));
                const now = new Date();
                const diff = Math.max(0, Math.floor((now - start) / 1000));

                const h = String(Math.floor(diff / 3600)).padStart(2, '0');
                const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                const s = String(diff % 60).padStart(2, '0');

                const valEl = el.querySelector('.timer-val');
                if (valEl) valEl.innerText = `${h}:${m}:${s}`;
            });
        }

        loadData();
        setInterval(loadData, 10000);
        setInterval(updateLiveTimers, 1000);
    </script>
</body>

</html>