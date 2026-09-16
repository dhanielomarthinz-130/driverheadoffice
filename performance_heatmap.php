<?php
require_once 'auth_check.php';
require_once 'db_config.php';

checkLogin();
checkAccess('performance');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Line | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <style>
        /* Modern Layout Fixes for Heatmap */
        .page-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .heatmap-wrapper {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .heatmap-scroll {
            overflow-x: auto;
            position: relative;
            scrollbar-width: thin;
            width: 100%;
        }

        table.heatmap-table {
            border-collapse: collapse;
            width: max-content;
            font-size: 0.8rem;
        }

        .heatmap-table th,
        .heatmap-table td {
            width: 44px;
            min-width: 44px;
            height: 56px;
            text-align: center;
            border: 1px solid #f1f5f9;
            padding: 0;
            position: relative;
        }

        /* Sticky Driver Column with better shadow */
        .heatmap-table th:first-child,
        .heatmap-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 50;
            width: 190px;
            min-width: 190px;
            text-align: left;
            padding: 0 1.25rem;
            background: #f8fafc;
            border-right: 2px solid var(--border);
            font-weight: 700;
            color: var(--text);
            box-shadow: 4px 0 8px -4px rgba(0, 0, 0, 0.05);
        }

        .heatmap-table thead th {
            background: #f8fafc;
            color: var(--text-sub);
            font-weight: 700;
            font-size: 0.7rem;
            padding: 1rem 0;
            border-bottom: 2px solid var(--border);
        }

        .heatmap-table thead th:first-child {
            z-index: 60;
        }

        .day-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .day-label {
            font-size: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1;
        }

        /* Cell Styling */
        .cell-box {
            width: 32px;
            height: 32px;
            margin: 0 auto;
            border-radius: 9px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            font-weight: 800;
            font-size: 0.8rem;
            color: var(--text-sub);
        }

        .cell-box:hover {
            transform: translateY(-2px) scale(1.15);
            z-index: 100;
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.15);
        }

        .cell-box.has-data.all-done {
            background: var(--success);
            color: white;
            border: none;
        }

        .cell-box.has-data.pending {
            background: var(--warning);
            color: white;
            border: none;
        }

        .cell-box.has-data.partial {
            background: #3b82f6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

        .late-marker {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 11px;
            height: 11px;
            background: var(--danger);
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .is-sunday {
            background: rgba(239, 68, 68, 0.05) !important;
            border-left: 1px solid rgba(239, 68, 68, 0.1) !important;
            border-right: 1px solid rgba(239, 68, 68, 0.1) !important;
        }

        .is-sunday-header {
            background: #fef2f2 !important;
            color: #ef4444 !important;
            border-bottom: 2px solid #fca5a5 !important;
        }

        .is-today {
            background: rgba(16, 185, 129, 0.05) !important;
            box-shadow: inset 0 0 0 1px #10b981 !important;
        }

        .is-today-header {
            background: #ecfdf5 !important;
            color: #059669 !important;
            border-bottom: 2px solid #10b981 !important;
        }

        .legend-card {
            padding: 1.5rem;
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 2rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-sub);
        }

        .legend-box {
            width: 18px;
            height: 18px;
            border-radius: 5px;
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 0;
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
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-container">
            <div class="page-header" style="margin-bottom: 0.5rem;">
                <div class="page-title">
                    <div class="page-title-icon" style="background: linear-gradient(135deg, #10b981, #06b6d4);">
                        <span class="material-symbols-outlined">grid_view</span>
                    </div>
                    <div>
                        <h1 id="pageTitleText">Time Line Driver Head Office</h1>
                        <p id="pageSubtitleText">Visualisasi aktivitas tugas driver internal Head Office</p>
                    </div>
                </div>
            </div>

            <!-- TABS removed -->

            <!-- Unified Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Pilih Periode Bulan</label>
                    <div
                        style="display:flex; align-items:center; gap:0.625rem; background:white; padding:0.4rem 0.875rem; border-radius:var(--radius-sm); border:1px solid var(--border);">
                        <span class="material-symbols-outlined"
                            style="font-size:20px; color:var(--text-muted);">calendar_month</span>
                        <input type="month" id="monthPicker" onchange="loadHeatmap()"
                            style="border:none; font-family:inherit; font-weight:700; outline:none; cursor:pointer; font-size:0.95rem;">
                    </div>
                </div>
                <div style="margin-left:auto;">
                    <span class="row-count-badge" id="refreshIndicator">
                        <span class="pulse-dot"></span> Auto-update Aktif
                    </span>
                </div>
            </div>

            <!-- Heatmap Grid Container -->
            <div class="heatmap-wrapper">
                <div class="heatmap-scroll">
                    <table class="heatmap-table" id="heatmapTable">
                        <thead>
                            <tr id="tableHeader">
                                <th>Driver</th>
                                <!-- Days 1-31 -->
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Rows will be injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Professional Legend Card -->
            <div class="legend-card">
                <div class="legend-item">
                    <div class="legend-box" style="background:#f1f5f9; border:1px solid #e2e8f0;"></div> Tidak Ada Tugas
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background:var(--warning);"></div> Tugas Belum Selesai
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background:var(--success);"></div> Semua Selesai
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background:#3b82f6;"></div> Sebagian Selesai
                </div>
                <div class="legend-item">
                    <div
                        style="position:relative; width:18px; height:18px; background:var(--success); border-radius:5px;">
                        <div class="late-marker" style="top:-2px; right:-2px; width:8px; height:8px;"></div>
                    </div>
                    <span id="legendLateText">Ada Pembatalan</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background:rgba(239, 68, 68, 0.08); border:1px solid #fecaca;"></div>
                    Hari Minggu
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background:#ecfdf5; border:1px solid #10b981;"></div>
                    Hari Ini (Sekarang)
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Detail Modal -->
    <div id="detailModal" class="modal-overlay" style="display: none;">
        <div class="modal-box" style="max-height: 90vh; display: flex; flex-direction: column;">
            <button onclick="closeModal()" class="modal-close">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div style="flex-shrink:0; margin-bottom: 1.5rem;">
                <h3 id="modalTitle" class="modal-title">
                    <span class="material-symbols-outlined">assignment</span>
                    Detail Tugas
                </h3>
                <p id="modalSubtitle" class="modal-subtitle" style="margin-bottom:0;"></p>
            </div>

            <div id="modalList"
                style="flex:1; display:flex; flex-direction:column; gap:1rem; overflow-y:auto; padding-right:0.5rem; margin-bottom:1.5rem;">
                <!-- Tasks here -->
            </div>

            <button onclick="closeModal()" class="btn btn-ghost" style="width:100%; justify-content:center;">
                Tutup Jendela Detail
            </button>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let currentTab = 'driver'; // 'driver' or 'expedisi'

        // Initial setup from SERVER (PHP)
        const initialMonth = "<?php echo date('Y-m'); ?>";
        document.getElementById('monthPicker').value = initialMonth;

        function switchTab(tab) {
            currentTab = tab;

            // Update UI
            document.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');

            // Update Text
            const titleText = document.getElementById('pageTitleText');
            const subtitleText = document.getElementById('pageSubtitleText');
            const tableHeaderFirst = document.querySelector('#tableHeader th:first-child');

            if (tab === 'driver') {
                titleText.innerText = 'Time Line Driver WH';
                subtitleText.innerText = 'Visualisasi aktivitas tugas driver internal WH';
                if (tableHeaderFirst) tableHeaderFirst.innerText = 'Driver';
                document.getElementById('legendLateText').innerText = 'Ada Pembatalan';
            } else {
                titleText.innerText = 'Timeline Expedisi';
                subtitleText.innerText = 'Visualisasi aktivitas tugas expedisi luar';
                if (tableHeaderFirst) tableHeaderFirst.innerText = 'Expedisi';
                document.getElementById('legendLateText').innerText = 'Ada Pembatalan';
            }

            loadHeatmap();
        }

        async function loadHeatmap() {
            const selectedMonth = document.getElementById('monthPicker').value;
            if (!selectedMonth) return;

            const now = new Date();
            const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

            const [year, month] = selectedMonth.split('-').map(Number);
            const daysInMonth = new Date(year, month, 0).getDate();
            const tableHeader = document.getElementById('tableHeader');
            const tableBody = document.getElementById('tableBody');

            // Generate Header
            let headerHtml = `<th>${currentTab === 'driver' ? 'Driver' : 'Expedisi'}</th>`;
            const dayNamesShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month - 1, d);
                const dayName = dayNamesShort[dateObj.getDay()];
                const isSunday = dateObj.getDay() === 0;
                const dateString = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const isToday = dateString === todayStr;

                headerHtml += `
                    <th class="${isSunday ? 'is-sunday-header' : ''} ${isToday ? 'is-today-header' : ''}">
                        <div class="day-header">
                            <span class="day-label">${dayName}</span>
                            <span>${d}</span>
                        </div>
                    </th>`;
            }
            tableHeader.innerHTML = headerHtml;

            try {
                let driversUrl = currentTab === 'driver' ? `${API_URL}?action=get_drivers` : `${API_URL}?action=get_expedisi_unique_drivers`;
                let summaryUrl = currentTab === 'driver' ? `${API_URL}?action=get_monthly_summary&month=${selectedMonth}` : `${API_URL}?action=get_expedisi_monthly_summary&month=${selectedMonth}`;

                const [driversRes, dataRes] = await Promise.all([
                    fetch(driversUrl),
                    fetch(summaryUrl)
                ]);
                const driversList = await driversRes.json();
                const summaryData = await dataRes.json();

                let bodyRows = '';
                driversList.forEach(driver => {
                    let row = `<tr><td>${driver.driver_name}</td>`;
                    for (let d = 1; d <= daysInMonth; d++) {
                        const dateString = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        const dayTasks = summaryData.find(s => s.driver_id == driver.user_id && s.target_date == dateString);

                        const curDateObs = new Date(year, month - 1, d);
                        const isSunCell = curDateObs.getDay() === 0;
                        const isToday = dateString === todayStr;

                        let cellInner = '';
                        if (dayTasks) {
                            const total = parseInt(dayTasks.total_tasks);
                            const done = parseInt(dayTasks.completed_tasks);

                            let styleClass = 'has-data ';
                            if (done === total) {
                                styleClass += 'all-done';
                            } else if (done > 0) {
                                styleClass += 'partial';
                            } else {
                                styleClass += 'pending';
                            }

                            // For Expedisi, we need a slightly different click handler or data
                            const clickHandler = currentTab === 'driver'
                                ? `showDayDetails(${driver.user_id}, '${dateString}', '${driver.driver_name}')`
                                : `showExpedisiDayDetails('${driver.user_id}', '${dateString}', '${driver.driver_name}')`;

                            cellInner = `
                                <div class="cell-box ${styleClass}" onclick="${clickHandler}">
                                    ${dayTasks.total_tasks}
                                    ${parseInt(dayTasks.late_count) > 0 ? '<div class="late-marker"></div>' : ''}
                                </div>
                            `;
                        } else {
                            cellInner = `<div class="cell-box" style="opacity:0.25; background:transparent; border:1px dashed #e2e8f0; cursor:default;"></div>`;
                        }

                        row += `<td class="${isSunCell ? 'is-sunday' : ''} ${isToday ? 'is-today' : ''}">${cellInner}</td>`;
                    }
                    row += '</tr>';
                    bodyRows += row;
                });
                tableBody.innerHTML = bodyRows;

            } catch (err) {
                console.error('Heatmap load error:', err);
                tableBody.innerHTML = '<tr><td colspan="40" style="padding:5rem; text-align:center; color:var(--danger); font-weight:700; background:white;">⚠️ Maaf, terjadi kesalahan saat memuat data. Periksa API atau database.</td></tr>';
            }
        }

        async function showDayDetails(driverId, date, driverName) {
            const modal = document.getElementById('detailModal');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const list = document.getElementById('modalList');

            title.innerText = driverName;
            subtitle.innerText = 'Riwayat Tugas Jadwal — ' + date;
            list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted); font-size:0.9rem;">Mencari detail tugas...</div>';
            modal.style.display = 'flex';

            try {
                const res = await fetch(`${API_URL}?action=get_deliveries&driver_id=${driverId}&target_date=${date}`);
                const tasks = await res.json();

                if (tasks.length) {
                    list.innerHTML = tasks.map(t => {
                        const sClass = t.status === 'completed' ? 'badge-completed' : (t.status === 'in_transit' ? 'badge-transit' : 'badge-pending');
                        const sLabel = t.status.replace('_', ' ').toUpperCase();
                        return `
                            <div class="task-detail-item">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.875rem;">
                                    <span style="font-size:0.75rem; font-weight:900; color:var(--primary); background:var(--primary-light); padding:2px 8px; border-radius:6px;">Tipe Tugas: ${t.task_type === 'antar' ? 'DELIVERY' : 'PICKUP'} #${t.id}</span>
                                    <span class="badge ${sClass}" style="margin:0; font-size:0.65rem;">${sLabel}</span>
                                </div>
                                <div style="font-weight:800; color:var(--text); margin-bottom:4px; font-size:0.95rem;">${t.destination_name}</div>
                                <div style="font-size:0.8rem; color:var(--text-sub); display:flex; align-items:center; gap:6px;">
                                    <span class="material-symbols-outlined" style="font-size:16px;">local_shipping</span> Lokasi: ${t.origin_name}
                                </div>
                                ${t.late_reason ? `
                                    <div style="margin-top:1rem; padding:0.75rem; background:#fff1f2; border-radius:10px; border:1px solid #ffe4e6; font-size:0.8rem; color:#be123c; font-weight:700; line-height:1.4;">
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            <span class="material-symbols-outlined" style="font-size:16px;">warning</span> Alasan Terlambat:
                                        </div>
                                        ${t.late_reason}
                                    </div>
                                ` : ''}
                                ${t.driver_notes ? `
                                    <div style="margin-top:0.5rem; padding:0.75rem; background:#f0f9ff; border-radius:10px; border:1px solid #bae6fd; font-size:0.8rem; color:#0369a1; font-weight:700; line-height:1.4;">
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            <span class="material-symbols-outlined" style="font-size:16px;">sticky_note_2</span> Catatan Driver:
                                        </div>
                                        ${t.driver_notes}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted);">Tidak ada data detail untuk hari ini.</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--danger);">Gagal memuat detail tugas. Silakan coba lagi.</div>';
            }
        }

        async function showExpedisiDayDetails(vendorDriver, date, displayName) {
            const modal = document.getElementById('detailModal');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const list = document.getElementById('modalList');

            title.innerText = displayName;
            subtitle.innerText = 'Riwayat Tugas Expedisi — ' + date;
            list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted); font-size:0.9rem;">Mencari detail tugas...</div>';
            modal.style.display = 'flex';

            try {
                // Now we filter only by vendor name (vendorDriver contains the vendor name)
                const res = await fetch(`${API_URL}?action=get_expedisi_tasks&start=${date}&end=${date}&vendor_name=${encodeURIComponent(vendorDriver)}`);
                const tasks = await res.json();

                if (tasks.length) {
                    list.innerHTML = tasks.map(t => {
                        const sClass = t.status === 'completed' ? 'badge-completed' : (t.status === 'in_transit' ? 'badge-transit' : 'badge-pending');
                        const sLabel = t.status.replace('_', ' ').toUpperCase();
                        return `
                            <div class="task-detail-item">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.875rem;">
                                    <span style="font-size:0.75rem; font-weight:900; color:var(--primary); background:var(--primary-light); padding:2px 8px; border-radius:6px;">Tipe Tugas: ${t.type === 'antar' ? 'DELIVERY' : 'PICKUP'} #${t.id}</span>
                                    <span class="badge ${sClass}" style="margin:0; font-size:0.65rem;">${sLabel}</span>
                                </div>
                                <div style="font-weight:800; color:var(--text); margin-bottom:4px; font-size:0.95rem;">${t.destination_name}</div>
                                <div style="font-size:0.8rem; color:var(--text-sub); display:flex; align-items:center; gap:6px;">
                                    <span class="material-symbols-outlined" style="font-size:16px;">local_shipping</span> Vendor: ${t.vendor_name || '-'} (${t.vehicle_plate})
                                </div>
                                <div style="font-size:0.8rem; color:var(--text-sub); display:flex; align-items:center; gap:6px; margin-top:4px;">
                                    <span class="material-symbols-outlined" style="font-size:16px;">location_on</span> Dari: ${t.origin_name}
                                </div>
                                ${t.receiver_name ? `
                                    <div style="margin-top:1rem; padding:0.75rem; background:var(--success-light); border-radius:10px; border:1px solid #d1fae5; font-size:0.8rem; color:var(--success); font-weight:700; line-height:1.4;">
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            <span class="material-symbols-outlined" style="font-size:16px;">person</span> Penerima:
                                        </div>
                                        ${t.receiver_name}
                                    </div>
                                ` : ''}
                                ${t.driver_notes ? `
                                    <div style="margin-top:0.5rem; padding:0.75rem; background:#f0f9ff; border-radius:10px; border:1px solid #bae6fd; font-size:0.8rem; color:#0369a1; font-weight:700; line-height:1.4;">
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            <span class="material-symbols-outlined" style="font-size:16px;">sticky_note_2</span> Catatan Driver:
                                        </div>
                                        ${t.driver_notes}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted);">Tidak ada data detail untuk hari ini.</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--danger);">Gagal memuat detail tugas. Silakan coba lagi.</div>';
            }
        }

        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        // Close modal on escape key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Click outside to close
        document.getElementById('detailModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('detailModal')) closeModal();
        });

        // Initialize and setup interval
        loadHeatmap();
        setInterval(loadHeatmap, 30000);
    </script>
</body>

</html>