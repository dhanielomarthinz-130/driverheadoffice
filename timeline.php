<?php
require_once 'auth_check.php';
require_once 'db_config.php';
checkLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'controller') {
    header('Location: driver');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline Penugasan | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <style>
        .timeline-container {
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .timeline-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .timeline-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .timeline-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--border);
        }

        .driver-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* The Vertical Timeline Line */
        .timeline-flow {
            position: relative;
            padding-left: 2.5rem;
        }

        .timeline-flow::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            bottom: 0.5rem;
            width: 2px;
            background: var(--border);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -2.25rem;
            top: 0.25rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: var(--surface);
            border: 3px solid var(--border);
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timeline-dot .material-symbols-outlined {
            font-size: 12px;
            font-weight: 800;
        }

        /* Node States */
        .timeline-item.completed .timeline-dot {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .timeline-item.active .timeline-dot {
            border-color: var(--warning);
            background: var(--warning);
            color: white;
        }

        .timeline-item.pending .timeline-dot {
            border-color: var(--primary);
            background: var(--surface);
            color: var(--primary);
        }

        .timeline-item.canceled .timeline-dot {
            border-color: #ef4444;
            background: #ef4444;
            color: white;
        }

        .timeline-item.canceled .timeline-title {
            color: #ef4444;
        }

        .timeline-content {
            display: flex;
            flex-direction: column;
        }

        .timeline-time {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-sub);
            margin-bottom: 0.25rem;
        }

        .timeline-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
        }

        .timeline-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .note-box {
            background: #fffbeb;
            border-left: 4px solid var(--warning);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #92400e;
        }

        /* Filter styling */
        .timeline-filters {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 0.75rem 1.25rem;
            display: flex;
            gap: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            align-items: center;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filter-item label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .filter-item select,
        .filter-item input {
            border: none;
            background: transparent;
            font-family: inherit;
            font-weight: 600;
            color: var(--text);
            outline: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .timeline-filters {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="timeline-container">
            <div class="timeline-header">
                <div>
                    <h1 style="font-size:1.75rem; font-weight:800; letter-spacing:-0.02em; color:var(--text);">Timeline
                        Penugasan</h1>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:0.25rem;">Lacak progres tugas driver
                        secara mendetail.</p>
                </div>
                <div id="refreshIndicator"
                    style="font-size:0.75rem; color:var(--text-muted); display:flex; align-items:center; gap:0.5rem;">
                    <span class="pulse-dot"></span> Auto-update Aktif
                </div>
            </div>

            <!-- TABS removed -->

            <div class="timeline-filters">
                <div class="filter-item">
                    <label>Tanggal Jadwal</label>
                    <input type="date" id="dateFilter" onchange="loadTimeline()">
                </div>
                <div id="driverFilterGroup" class="filter-item">
                    <label id="filterLabel">Driver</label>
                    <select id="driverFilter" onchange="loadTimeline()">
                        <option value="">Semua Driver</option>
                    </select>
                </div>
                <div class="filter-item" style="margin-left:auto;">
                    <button class="btn-icon" onclick="loadTimeline()" title="Segarkan Data">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>
                </div>
            </div>

            <div id="timelineList">
                <!-- Timeline items will be injected here -->
                <div style="text-align:center; padding: 4rem;">
                    <span class="material-symbols-outlined"
                        style="font-size:48px; color:var(--border); transform: rotate(45deg);">hourglass_empty</span>
                    <p style="color:var(--text-muted); margin-top:1rem;">Memuat timeline...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let currentTab = 'driver'; // 'driver' or 'expedisi'

        // Default today
        const d = new Date();
        const today = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        document.getElementById('dateFilter').value = today;

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');

            document.getElementById('filterLabel').innerText = tab === 'driver' ? 'Driver' : 'Vendor';

            loadFilterOptions();
            loadTimeline();
        }

        async function loadFilterOptions() {
            const select = document.getElementById('driverFilter');
            select.innerHTML = `<option value="">Semua ${currentTab === 'driver' ? 'Driver' : 'Vendor'}</option>`;

            try {
                if (currentTab === 'driver') {
                    const res = await fetch(`${API_URL}?action=get_drivers`);
                    const drivers = await res.json();
                    drivers.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.user_id;
                        opt.textContent = d.driver_name;
                        select.appendChild(opt);
                    });
                } else {
                    const res = await fetch(`${API_URL}?action=get_expedisi_vendors`);
                    const vendors = await res.json();
                    vendors.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.name; // For expedisi, we filter by vendor name or id. api.php get_expedisi_tasks uses vendor_name in GET or vendor_id.
                        opt.textContent = v.name;
                        select.appendChild(opt);
                    });
                }
            } catch (e) { console.error(e); }
        }

        async function init() {
            await loadFilterOptions();
            await loadTimeline();

            // Auto refresh every 30 seconds
            setInterval(loadTimeline, 30000);
        }

        async function loadTimeline() {
            const date = document.getElementById('dateFilter').value;
            const filterVal = document.getElementById('driverFilter').value;

            const container = document.getElementById('timelineList');

            try {
                let url;
                if (currentTab === 'driver') {
                    url = `${API_URL}?action=get_deliveries&target_date=${date}&driver_id=${filterVal}`;
                } else {
                    url = `${API_URL}?action=get_expedisi_tasks&start=${date}&end=${date}&vendor_name=${filterVal}`;
                }

                const res = await fetch(url);
                const data = await res.json();

                if (!data.length) {
                    container.innerHTML = `
                        <div style="text-align:center; padding: 4rem; background:white; border-radius:var(--radius-lg); border:1px solid var(--border);">
                            <span class="material-symbols-outlined" style="font-size:48px; color:var(--border); margin-bottom:1rem; display:block;">event_busy</span>
                            <p style="color:var(--text-sub); font-weight:600;">Tidak ada aktivitas penugasan.</p>
                            <p style="color:var(--text-muted); font-size:0.85rem;">Coba pilih tanggal atau driver lain.</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.map(del => renderTimelineCard(del)).join('');

            } catch (err) {
                container.innerHTML = `<p style="color:red; text-align:center;">Gagal memuat data: ${err.message}</p>`;
            }
        }

        function formatDateTime(str) {
            if (!str) return '-';
            const dt = new Date(str);
            return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ', ' +
                dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        }

        function renderTimelineCard(del) {
            const isCompleted = del.status === 'completed';
            const isTransit = del.status === 'in_transit';

            let timelineHtml = `
                <div class="timeline-item completed">
                    <div class="timeline-dot"><span class="material-symbols-outlined">add</span></div>
                    <div class="timeline-content">
                        <div class="timeline-time">${formatDateTime(del.created_at)}</div>
                        <div class="timeline-title">Penugasan Dibuat</div>
                        <div class="timeline-desc">Admin memberikan tugas <strong>${del.task_type.toUpperCase()}</strong></div>
                    </div>
                </div>
            `;

            if (del.start_time || isTransit || isCompleted) {
                const statusClass = (isTransit || isCompleted) ? 'completed' : 'pending';
                timelineHtml += `
                    <div class="timeline-item ${statusClass}">
                        <div class="timeline-dot"><span class="material-symbols-outlined">local_shipping</span></div>
                        <div class="timeline-content">
                            <div class="timeline-time">${formatDateTime(del.start_time)} ${!del.start_time ? '(Menunggu...)' : ''}</div>
                            <div class="timeline-title">Mulai Perjalanan</div>
                            <div class="timeline-desc">Driver melakukan check-in dari <strong>${del.origin_name}</strong></div>
                        </div>
                    </div>
                `;
            }

            if (del.end_time || isCompleted || del.status === 'canceled') {
                const isCanceled = del.status === 'canceled';
                const statusClass = (isCompleted || isCanceled) ? (isCanceled ? 'canceled' : 'completed') : 'pending';
                const icon = isCanceled ? 'block' : 'check';
                const title = isCanceled ? 'Tugas Cancel' : 'Tugas Selesai';
                const desc = isCanceled ? `Cancel saat di <strong>${del.destination_name}</strong>` : `Pesanan sampai di <strong>${del.destination_name}</strong>`;

                timelineHtml += `
                    <div class="timeline-item ${statusClass}">
                        <div class="timeline-dot"><span class="material-symbols-outlined">${icon}</span></div>
                        <div class="timeline-content">
                            <div class="timeline-time">${formatDateTime(del.end_time)} ${!del.end_time ? '(Proses...)' : ''}</div>
                            <div class="timeline-title">${title}</div>
                            <div class="timeline-desc">${desc}</div>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="timeline-card">
                    <div class="timeline-card-header">
                        <div class="driver-info">
                            <div class="driver-avatar">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div>
                                <div style="font-size:0.9rem; font-weight:700;">${del.driver_name}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted);">${currentTab === 'expedisi' ? (del.vendor_name || 'Expedisi') : (del.vehicle_plate || 'No Vehicle')}</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.8rem; font-weight:800; color:var(--primary);">${currentTab === 'expedisi' ? 'EXPEDISI' : 'ORDER'} #${del.id}</div>
                            <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">${(del.task_type || del.type || '').replace('_', ' ')}</div>
                        </div>
                    </div>
                    
                    <div class="timeline-flow">
                        ${timelineHtml}
                    </div>

                    ${del.driver_notes ? `
                        <div class="note-box" style="border-left-color: ${del.status === 'canceled' ? '#ef4444' : 'var(--warning)'}; background: ${del.status === 'canceled' ? '#fef2f2' : '#fffbeb'}; color: ${del.status === 'canceled' ? '#991b1b' : '#92400e'};">
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px; font-weight:700;">
                                <span class="material-symbols-outlined" style="font-size:16px;">sticky_note_2</span> 
                                Ket. Driver:
                            </div>
                            ${del.driver_notes}
                        </div>
                    ` : ''}
                    ${del.late_reason ? `
                        <div class="note-box">
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px; font-weight:700;">
                                <span class="material-symbols-outlined" style="font-size:16px;">history_toggle_off</span> 
                                Alasan Terlambat:
                            </div>
                            ${del.late_reason}
                        </div>
                    ` : ''}
                </div>
            `;
        }

        init();
    </script>
</body>

</html>