<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('driver_kpi');
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver KPI | IMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <style>
        /* ── Filter Bar ── */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            background: var(--card);
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .filter-bar .fg {
            margin: 0;
            flex: 1;
            min-width: 160px;
        }

        .filter-bar label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--text-muted);
            display: block;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .filter-bar input,
        .filter-bar select {
            width: 100%;
            padding: .75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: .875rem;
            background: var(--bg);
            color: var(--text);
            transition: all .3s;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .1);
            background: #fff;
        }

        .btn-pill {
            border-radius: 50px !important;
            padding: 0 1.5rem !important;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            white-space: nowrap;
            height: 46px;
            transition: all 0.3s;
        }

        .btn-export {
            background: #107c41;
            color: white;
            border: none;
        }

        .btn-export:hover {
            background: #0b592e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 124, 65, 0.3);
        }

        .filter-bar .btn-primary {
            height: 42px;
            padding: 0 1.5rem;
        }

        /* ── Summary Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: .3s cubic-bezier(.4, 0, .2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .stat-val {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text);
        }

        .stat-lbl {
            font-size: .75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        /* ── Podium Top 3 ── */
        .podium-section {
            margin-bottom: 2.5rem;
            animation: fadeInUp .6s ease-out;
            display: flex;
            flex-direction: column;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .podium-section h2 {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--text);
        }

        .podium {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 1.25rem;
            padding: 0 1rem;
            min-height: 400px;
            flex-grow: 1;
        }

        .podium-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            flex: 1;
            max-width: 260px;
            position: relative;
            transition: .4s cubic-bezier(.34, 1.56, .64, 1);
            cursor: default;
            box-shadow: var(--shadow-md);
        }

        .podium-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .podium-card.rank-1 {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px #fef3c7, var(--shadow-lg);
            order: 1;
            z-index: 2;
            padding-top: 2.5rem;
            min-height: 370px;
        }

        .podium-card.rank-2 {
            order: 2;
            min-height: 320px;
        }

        .podium-card.rank-3 {
            order: 3;
            min-height: 270px;
        }

        .podium-medal {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 1.25rem;
            position: relative;
            z-index: 2;
        }

        .podium-medal::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid currentColor;
            opacity: .2;
        }

        .podium-medal.gold {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #fff;
            box-shadow: 0 8px 20px rgba(245, 158, 11, .4);
        }

        .podium-medal.silver {
            background: linear-gradient(135deg, #e2e8f0, #94a3b8);
            color: #fff;
            box-shadow: 0 8px 20px rgba(148, 163, 184, .3);
        }

        .podium-medal.bronze {
            background: linear-gradient(135deg, #f97316, #c2410c);
            color: #fff;
            box-shadow: 0 8px 20px rgba(194, 65, 12, .3);
        }

        .podium-name {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: .35rem;
            color: var(--text);
        }

        .podium-plate {
            font-size: .75rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .podium-big {
            font-size: 2.8rem;
            font-weight: 900;
            line-height: 1;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .podium-sub {
            font-size: .7rem;
            color: var(--text-muted);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 700;
        }

        .podium-chips {
            display: flex;
            justify-content: center;
            gap: .5rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }

        .chip {
            font-size: .68rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .chip-delivery {
            background: #eef2ff;
            color: #4f46e5;
        }

        .chip-pickup {
            background: #ecfdf5;
            color: #059669;
        }

        .chip-late {
            background: #fff7ed;
            color: #d97706;
        }

        .chip-canceled {
            background: #fef2f2;
            color: #dc2626;
        }

        /* ── Leaderboard Table ── */
        .leader-section h2 {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .leader-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .leader-row {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 50px 1fr auto;
            align-items: center;
            gap: 1.5rem;
            transition: .25s;
            box-shadow: var(--shadow-sm);
        }

        .leader-row:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, .03);
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .rank-1-bg {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #fff;
            box-shadow: 0 4px 12px rgba(245, 158, 11, .3);
        }

        .rank-2-bg {
            background: linear-gradient(135deg, #e2e8f0, #94a3b8);
            color: #fff;
        }

        .rank-3-bg {
            background: linear-gradient(135deg, #f97316, #c2410c);
            color: #fff;
        }

        .rank-n-bg {
            background: var(--bg);
            color: var(--text-muted);
            border: 2px solid var(--border);
        }

        .leader-info {
            min-width: 120px;
            max-width: 160px;
        }

        .leader-name {
            font-weight: 700;
            font-size: .95rem;
            margin-bottom: 2px;
            color: var(--text);
        }

        .leader-plate {
            font-size: .75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .leader-stats {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-shrink: 0;
        }

        .ls-item {
            text-align: center;
            min-width: 46px;
        }

        .ls-val {
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1;
        }

        .ls-lbl {
            font-size: .65rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: 4px;
        }

        .ls-val.clr-blue {
            color: #4f46e5;
        }

        .ls-val.clr-green {
            color: #059669;
        }

        .ls-val.clr-amber {
            color: #d97706;
        }

        .ls-val.clr-purple {
            color: #7c3aed;
        }

        .ls-val.clr-red {
            color: #ef4444;
        }

        .progress-wrap {
            min-width: 110px;
        }

        .progress-lbl {
            font-size: .7rem;
            font-weight: 800;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .progress-bar {
            height: 8px;
            background: var(--bg);
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 1s cubic-bezier(.34, 1.56, .64, 1);
        }

        .fill-100 {
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .fill-high {
            background: linear-gradient(90deg, #4f46e5, #6366f1);
        }

        .fill-mid {
            background: linear-gradient(90deg, #d97706, #f59e0b);
        }

        .fill-low {
            background: linear-gradient(90deg, #dc2626, #ef4444);
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-muted);
        }

        .empty-state .material-symbols-outlined {
            font-size: 56px;
            display: block;
            margin-bottom: 1rem;
            opacity: .3;
        }

        /* ── Loader ── */
        .loader-wrap {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .spin {
            animation: spin .9s linear infinite;
            display: inline-block;
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f1f5f9;
            padding: 0.4rem;
            border-radius: var(--radius-md);
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
        }

        .tab-link.active {
            background: var(--surface);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1.2fr 2fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .ranking-row {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
            align-items: stretch;
        }

        @media(max-width:1100px) {
            .ranking-row {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }
        }

        .chart-card {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }

        .chart-card h3 {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media(max-width:800px) {
            .charts-row {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-bar {
                grid-template-columns: 1fr !important;
                gap: .75rem !important;
            }

            .filter-bar .fg:last-child {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
            }

            .podium {
                flex-direction: column;
                align-items: center;
            }

            .podium-card {
                max-width: 100%;
                width: 100%;
            }

            .podium-card.rank-1,
            .podium-card.rank-2,
            .podium-card.rank-3 {
                order: unset;
            }

            .leader-stats {
                gap: .75rem;
            }

            .progress-wrap {
                min-width: 80px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

        <!-- Page Header -->
        <div class="page-header" style="margin-bottom: 1.5rem;">
            <div class="page-title">
                <div class="page-title-icon"><span class="material-symbols-outlined">emoji_events</span></div>
                <div>
                    <h1 id="pageTitleText">Driver KPI Leaderboard</h1>
                    <p id="pageSubtitleText">Performa & Ranking 10 Driver Terbaik</p>
                </div>
            </div>
        </div>

        <!-- TABS removed -->

        <!-- Filter -->
        <div class="card"
            style="margin-bottom:2rem; padding:1.25rem 1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); background:#fff;">
            <div class="filter-bar"
                style="margin-bottom:0; display:grid; grid-template-columns: 1.2fr 1.2fr 1.5fr auto; gap:1.25rem; align-items:flex-end; border:none; padding:0; box-shadow:none; background:transparent;">
                <div class="fg">
                    <label><span class="material-symbols-outlined"
                            style="font-size:14px; vertical-align:middle; margin-right:4px;">calendar_today</span>
                        Dari</label>
                    <input type="date" id="fFrom" onchange="loadKPI()" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="fg">
                    <label><span class="material-symbols-outlined"
                            style="font-size:14px; vertical-align:middle; margin-right:4px;">calendar_month</span>
                        Sampai</label>
                    <input type="date" id="fTo" onchange="loadKPI()" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="fg">
                    <label id="filterLabel"><span class="material-symbols-outlined"
                            style="font-size:14px; vertical-align:middle; margin-right:4px;">person</span> Pilih
                        Driver</label>
                    <select id="fDriver" onchange="loadKPI()">
                        <option value="">Semua Driver</option>
                    </select>
                </div>
                <div class="fg-btn" style="display:flex; gap:0.5rem;">
                    <button class="btn btn-pill" onclick="resetFilter()" title="Reset Filter"
                        style="background:var(--bg); color:var(--text-muted); border:1.5px solid var(--border); width:46px; padding:0 !important; justify-content:center; border-radius:12px !important;">
                        <span class="material-symbols-outlined">restart_alt</span>
                    </button>
                    <div style="width:1px; background:var(--border); margin:0 5px; height:30px; align-self:center;">
                    </div>
                    <button class="btn btn-export btn-pill" onclick="exportExcel()" title="Export Excel"
                        style="width:46px; height:46px; padding:0 !important; justify-content:center; border-radius:12px !important; display:inline-flex; align-items:center;">
                        <span class="material-symbols-outlined" style="font-size:22px;">table_view</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eef2ff;color:#6366f1"><span
                        class="material-symbols-outlined">group</span></div>
                <div>
                    <div class="stat-val" id="sTotal">—</div>
                    <div class="stat-lbl" id="lblTotal">Total Driver</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669"><span
                        class="material-symbols-outlined">task_alt</span></div>
                <div>
                    <div class="stat-val" id="sCompleted">—</div>
                    <div class="stat-lbl">Total Selesai</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef2f2;color:#dc2626"><span
                        class="material-symbols-outlined">cancel</span></div>
                <div>
                    <div class="stat-val" id="sCanceled">—</div>
                    <div class="stat-lbl">Total Cancel</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7ed;color:#d97706"><span
                        class="material-symbols-outlined">schedule</span></div>
                <div>
                    <div class="stat-val" id="sLate">—</div>
                    <div class="stat-lbl">Total Terlambat</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-row">
            <div class="chart-card" style="align-items: center; justify-content: space-between;">
                <h3>
                    <span class="material-symbols-outlined" style="color: var(--primary)">pie_chart</span>
                    Perbandingan Status Tugas
                </h3>
                <div style="position: relative; width: 100%; height: 260px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="ratioChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>
                    <span class="material-symbols-outlined" style="color: var(--primary)">stacked_line_chart</span>
                    Tren Jumlah Tugas Per Tanggal
                </h3>
                <div style="position: relative; width: 100%; height: 260px; flex-grow: 1;">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Ranking and Podium Section -->
        <div class="ranking-row">
            <!-- Top 3 Podium -->
            <div class="podium-section card" style="padding:1.5rem; margin-bottom: 0;">
                <h2 id="podiumTitle"><span class="material-symbols-outlined" style="color:#f59e0b">emoji_events</span> Top 3
                    Driver Terbaik</h2>
                <div class="podium" id="podiumArea">
                    <div class="loader-wrap"><span class="material-symbols-outlined spin">autorenew</span></div>
                </div>
            </div>

            <!-- Full Leaderboard -->
            <div class="card leader-section" style="padding:1.5rem; margin-bottom: 0;">
                <h2><span class="material-symbols-outlined" style="color:#6366f1">leaderboard</span> <span
                        id="leaderTitle">Ranking Lengkap (Top 10)</span></h2>
                <div class="leader-list" id="leaderList">
                    <div class="loader-wrap"><span class="material-symbols-outlined spin">autorenew</span></div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        const API = 'api.php';
        let kpiData = [];
        let currentTab = 'driver'; // 'driver' or 'expedisi'

        // Init dates (First day and Last day of current month) using local time
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth();

        const firstDay = new Date(y, m, 1);
        const lastDay = new Date(y, m + 1, 0);

        // Helper to format YYYY-MM-DD
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const firstDayStr = formatDate(firstDay);
        const lastDayStr = formatDate(lastDay);

        document.getElementById('fFrom').value = firstDayStr;
        document.getElementById('fTo').value = lastDayStr;

        function resetFilter() {
            document.getElementById('fFrom').value = firstDayStr;
            document.getElementById('fTo').value = lastDayStr;
            document.getElementById('fDriver').value = '';
            loadKPI();
        }

        function switchTab(tab) {
            currentTab = tab;

            // Update UI
            document.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');

            // Update Text
            const titleText = document.getElementById('pageTitleText');
            const subtitleText = document.getElementById('pageSubtitleText');
            const filterLabel = document.getElementById('filterLabel');

            if (tab === 'driver') {
                titleText.innerText = 'KPI Driver Head Office';
                subtitleText.innerText = 'Performa & Ranking 10 Driver Head Office Terbaik';
                filterLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; margin-right:4px;">person</span> Pilih Driver';
                document.getElementById('leaderTitle').innerText = 'Ranking Lengkap Driver (Top 10)';
                document.getElementById('podiumTitle').innerHTML = '<span class="material-symbols-outlined" style="color:#f59e0b">emoji_events</span> Top 3 Driver Terbaik';
                document.getElementById('lblTotal').innerText = 'Total Driver';
            } else {
                titleText.innerText = 'KPI Driver Expedisi';
                subtitleText.innerText = 'Performa & Ranking 10 Vendor Expedisi Terbaik';
                filterLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; margin-right:4px;">local_shipping</span> Pilih Vendor';
                document.getElementById('leaderTitle').innerText = 'Ranking Lengkap Vendor (Top 10)';
                document.getElementById('podiumTitle').innerHTML = '<span class="material-symbols-outlined" style="color:#f59e0b">emoji_events</span> Top 3 Vendor Terbaik';
                document.getElementById('lblTotal').innerText = 'Total Vendor';
            }

            loadFilterOptions();
            loadKPI();
        }

        async function loadKPI() {
            const from = document.getElementById('fFrom').value;
            const to = document.getElementById('fTo').value;
            const filterVal = document.getElementById('fDriver').value;

            document.getElementById('podiumArea').innerHTML = '<div class="loader-wrap"><span class="material-symbols-outlined spin">autorenew</span> Memuat...</div>';
            document.getElementById('leaderList').innerHTML = '<div class="loader-wrap"><span class="material-symbols-outlined spin">autorenew</span> Memuat...</div>';

            try {
                let action = currentTab === 'driver' ? 'get_driver_kpi' : 'get_expedisi_kpi';
                let url = `${API}?action=${action}&date_from=${from}&date_to=${to}`;

                if (filterVal) {
                    url += currentTab === 'driver' ? `&driver_id=${filterVal}` : `&vendor_id=${filterVal}`;
                }

                const res = await fetch(url);
                kpiData = await res.json();
                if (!Array.isArray(kpiData)) kpiData = [];
                renderStats(kpiData);
                renderPodium(kpiData);
                renderLeaderboard(kpiData);
                loadCharts();
            } catch (e) {
                const err = `<div class="empty-state"><span class="material-symbols-outlined">error</span>Gagal memuat data: ${e.message}</div>`;
                document.getElementById('podiumArea').innerHTML = err;
                document.getElementById('leaderList').innerHTML = err;
            }
        }

        async function loadFilterOptions() {
            try {
                const select = document.getElementById('fDriver');
                select.innerHTML = `<option value="">Semua ${currentTab === 'driver' ? 'Driver' : 'Vendor'}</option>`;

                let url = currentTab === 'driver' ? `${API}?action=get_all_users&role=driver` : `${API}?action=get_expedisi_vendors`;
                const res = await fetch(url);
                const items = await res.json();

                items.forEach(i => {
                    const opt = document.createElement('option');
                    opt.value = i.id;
                    opt.textContent = i.name;
                    select.appendChild(opt);
                });
            } catch (e) { console.error('Gagal memuat filter options:', e); }
        }

        loadFilterOptions();
        loadKPI();

        function renderStats(data) {
            const totalCompleted = data.reduce((s, r) => s + r.total_completed, 0);
            const totalCanceled = data.reduce((s, r) => s + r.total_canceled, 0);
            const totalLate = data.reduce((s, r) => s + r.total_late, 0);

            document.getElementById('sTotal').textContent = data.length;
            document.getElementById('sCompleted').textContent = totalCompleted;
            document.getElementById('sCanceled').textContent = totalCanceled;
            document.getElementById('sLate').textContent = totalLate;
        }

        function fillClass(pct) {
            if (pct >= 100) return 'fill-100';
            if (pct >= 70) return 'fill-high';
            if (pct >= 40) return 'fill-mid';
            return 'fill-low';
        }

        function rankBgClass(i) {
            if (i === 0) return 'rank-1-bg';
            if (i === 1) return 'rank-2-bg';
            if (i === 2) return 'rank-3-bg';
            return 'rank-n-bg';
        }

        function medalClass(i) {
            if (i === 0) return 'gold';
            if (i === 1) return 'silver';
            return 'bronze';
        }
        function medalEmoji(i) {
            return ['🥇', '🥈', '🥉'][i] || (i + 1);
        }

        function renderPodium(data) {
            const top3 = data.slice(0, 3);
            const el = document.getElementById('podiumArea');
            if (!top3.length) {
                el.innerHTML = '<div class="empty-state"><span class="material-symbols-outlined">inbox</span>Belum ada data driver</div>';
                return;
            }
            // Reorder: 1st, 2nd, 3rd
            const ordered = [];
            if (top3[0]) ordered.push({ ...top3[0], _origIdx: 0 });
            if (top3[1]) ordered.push({ ...top3[1], _origIdx: 1 });
            if (top3[2]) ordered.push({ ...top3[2], _origIdx: 2 });

            el.innerHTML = ordered.map(r => {
                const i = r._origIdx;
                const pct = r.progress_pct;
                const fc = fillClass(pct);
                return `
        <div class="podium-card rank-${i + 1}">
            <div class="podium-medal ${medalClass(i)}">${medalEmoji(i)}</div>
            <div class="podium-name">${esc(r.driver_name)}</div>
            <div class="podium-big">${r.total_completed}</div>
            <div class="podium-sub">Tugas Selesai</div>
            <div style="margin:1rem 0 .5rem;">
                <div class="progress-lbl"><span>Progress</span><span>${pct}%</span></div>
                <div class="progress-bar"><div class="progress-fill ${fc}" style="width:${pct}%"></div></div>
            </div>
            <div class="podium-chips">
                ${r.total_canceled ? `<span class="chip chip-canceled">✖ ${r.total_canceled} Cancel</span>` : ''}
                ${r.total_late ? `<span class="chip chip-late">⚠ ${r.total_late} Terlambat</span>` : ''}
            </div>
        </div>`;
            }).join('');
        }

        function renderLeaderboard(data) {
            const el = document.getElementById('leaderList');
            if (!data.length) {
                el.innerHTML = '<div class="empty-state"><span class="material-symbols-outlined">inbox</span>Belum ada data driver dalam periode ini</div>';
                return;
            }
            const maxTotal = Math.max(...data.map(r => r.total_all), 1);
            el.innerHTML = data.map((r, i) => {
                const pct = r.progress_pct;
                const fc = fillClass(pct);
                return `
        <div class="leader-row">
            <div class="rank-badge ${rankBgClass(i)}">${i < 3 ? medalEmoji(i) : i + 1}</div>
            <div class="leader-info">
                <div class="leader-name">${esc(r.driver_name)}</div>
            </div>
            <div class="leader-stats">
                <div class="ls-item">
                    <div class="ls-val clr-blue">${r.total_all}</div>
                    <div class="ls-lbl">Total</div>
                </div>
                <div class="ls-item">
                    <div class="ls-val clr-green">${r.total_completed}</div>
                    <div class="ls-lbl">Selesai</div>
                </div>
                <div class="ls-item">
                    <div class="ls-val clr-red">${r.total_canceled}</div>
                    <div class="ls-lbl">Cancel</div>
                </div>
                <div class="ls-item">
                    <div class="ls-val clr-amber">${r.total_pending}</div>
                    <div class="ls-lbl">Pending</div>
                </div>
                <div class="progress-wrap">
                    <div class="progress-lbl"><span>Progress</span><span>${pct}%</span></div>
                    <div class="progress-bar"><div class="progress-fill ${fc}" style="width:${pct}%"></div></div>
                </div>
            </div>
        </div>`;
            }).join('');
        }

        function esc(str) {
            if (!str) return '—';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function exportExcel() {
            if (!kpiData.length) return alert('Tidak ada data untuk di-export.');
            const rows = [['Rank', currentTab === 'driver' ? 'Nama Driver' : 'Nama Vendor', 'Total Tugas', 'Selesai', 'Cancel', 'Transit', 'Pending', 'Terlambat', 'Progress (%)']];
            kpiData.forEach((r, i) => {
                const row = [i + 1, r.driver_name, r.total_all, r.total_completed, r.total_canceled, r.total_transit, r.total_pending, r.total_late, r.progress_pct];
                rows.push(row);
            });
            const ws = XLSX.utils.aoa_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Driver KPI');
            const from = document.getElementById('fFrom').value;
            const to = document.getElementById('fTo').value;
            XLSX.writeFile(wb, `Driver_KPI_${from}_${to}.xlsx`);
        }

        let ratioChartInstance = null;
        let timelineChartInstance = null;

        async function loadCharts() {
            const from = document.getElementById('fFrom').value;
            const to = document.getElementById('fTo').value;
            const filterVal = document.getElementById('fDriver').value;

            try {
                let action = currentTab === 'driver' ? 'get_driver_kpi_charts' : 'get_expedisi_kpi_charts';
                let url = `${API}?action=${action}&date_from=${from}&date_to=${to}`;

                if (filterVal) {
                    url += currentTab === 'driver' ? `&driver_id=${filterVal}` : `&vendor_id=${filterVal}`;
                }

                const res = await fetch(url);
                const data = await res.json();

                renderRatioChart(data.summary);
                renderTimelineChart(data.daily);
            } catch (e) {
                console.error('Gagal memuat chart KPI:', e);
            }
        }

        function renderRatioChart(summary) {
            const ctx = document.getElementById('ratioChart').getContext('2d');
            const completed = summary.total_completed || 0;
            const canceled = summary.total_canceled || 0;
            const active = summary.total_active || 0;

            if (ratioChartInstance) {
                ratioChartInstance.destroy();
            }

            ratioChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Selesai', 'Cancel', 'Aktif/Pending'],
                    datasets: [{
                        data: [completed, canceled, active],
                        backgroundColor: ['#10b981', '#ef4444', '#3b82f6'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                plugins: [ChartDataLabels],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Plus Jakarta Sans', 'Inter', sans-serif",
                                    weight: 600
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw || 0;
                                    return ` ${context.label}: ${val}`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#ffffff',
                            font: {
                                family: "'Plus Jakarta Sans', 'Inter', sans-serif",
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value) => {
                                return value > 0 ? value : '';
                            },
                            textAlign: 'center'
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        function renderTimelineChart(daily) {
            const ctx = document.getElementById('timelineChart').getContext('2d');

            if (timelineChartInstance) {
                timelineChartInstance.destroy();
            }

            const labels = daily.map(d => {
                const dt = new Date(d.date);
                return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            });
            const completed = daily.map(d => d.completed);
            const total = daily.map(d => d.total);

            timelineChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tugas Selesai',
                            data: completed,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#10b981',
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Total Tugas',
                            data: total,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#3b82f6',
                            pointHoverRadius: 7
                        }
                    ]
                },
                plugins: [ChartDataLabels],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Plus Jakarta Sans', 'Inter', sans-serif",
                                    weight: 600
                                }
                            }
                        },
                        datalabels: {
                            align: 'top',
                            anchor: 'end',
                            offset: 6,
                            color: '#1e293b',
                            font: {
                                family: "'Plus Jakarta Sans', 'Inter', sans-serif",
                                weight: 'bold',
                                size: 10
                            },
                            formatter: (value, ctx) => {
                                return value > 0 ? value : '';
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        loadKPI();
    </script>
    <?php include_once __DIR__ . '/profile_modal.php'; ?>
</body>

</html>