<?php
require_once 'auth_check.php';
checkLogin();
if (!canAccessMenu('report_vehicles') && !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
    header("Location: dashboard_summary.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Pakai & Lepas Mobil | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .filter-card {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-grid .form-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-grid .btn-export-wrap {
            flex: 0 0 auto;
        }

        .form-group label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            width: 100%;
            height: 42px;
            padding: 0 0.875rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.82rem;
            font-family: inherit;
            background: var(--surface);
            color: var(--text);
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background: white;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .kpi-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .kpi-lbl {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-released {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .plate-tag {
            background: #0f172a;
            color: #f8fafc;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .img-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: contain;
            background: #0f172a;
            border: 1.5px solid var(--border);
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .img-thumb:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }

        /* Image Preview Modal */
        .img-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .img-modal img {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .img-modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: white;">
                    <span class="material-symbols-outlined">directions_car</span>
                </div>
                <div>
                    <h1>Report Pakai & Lepas Mobil</h1>
                    <p>Riwayat waktu penggunaan, pelepasan kendaraan, foto kunci & catatan driver</p>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <span class="material-symbols-outlined">history</span>
                </div>
                <div>
                    <div class="kpi-val" id="kpiTotalSesi">0</div>
                    <div class="kpi-lbl">Total Sesi Pemakaian</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <span class="material-symbols-outlined">directions_car</span>
                </div>
                <div>
                    <div class="kpi-val" id="kpiAktifNow">0</div>
                    <div class="kpi-lbl">Kendaraan Sedang Aktif</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <span class="material-symbols-outlined">key_off</span>
                </div>
                <div>
                    <div class="kpi-val" id="kpiDilepasToday">0</div>
                    <div class="kpi-lbl">Dilepas Hari Ini</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Dari Tanggal</label>
                    <input type="date" id="dateFrom" class="form-control" onchange="loadReport()">
                </div>
                <div class="form-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" id="dateTo" class="form-control" onchange="loadReport()">
                </div>
                <div class="form-group">
                    <label>Driver</label>
                    <select id="driverFilter" class="form-control" onchange="loadReport()">
                        <option value="">Semua Driver</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kendaraan</label>
                    <select id="vehicleFilter" class="form-control" onchange="loadReport()">
                        <option value="">Semua Kendaraan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="statusFilter" class="form-control" onchange="loadReport()">
                        <option value="">Semua Status</option>
                        <option value="active">🟢 Sedang Aktif</option>
                        <option value="released">⚪ Sudah Dilepas</option>
                    </select>
                </div>
                <div class="form-group btn-export-wrap" style="flex: 0 0 auto; min-width: auto;">
                    <label style="visibility: hidden; user-select: none;">&nbsp;</label>
                    <button onclick="exportExcel()" style="height:42px; width:42px; background:#107c41; color:white; border:1.5px solid #107c41; border-radius:var(--radius-md); box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.2s;" title="Download Excel" onmouseover="this.style.background='#0b592e'; this.style.borderColor='#0b592e';" onmouseout="this.style.background='#107c41'; this.style.borderColor='#107c41';">
                        <span class="material-symbols-outlined" style="font-size:20px; color:white;">table_view</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Data Table -->
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; padding-bottom:1.25rem; border-bottom:1px solid var(--border); margin-bottom:1.25rem;">
                <span class="card-title" style="margin:0;">
                    <span class="material-symbols-outlined" style="font-size:22px; color:var(--primary);">badge</span>
                    Daftar Riwayat Pemakaian & Pelepasan Kendaraan
                </span>
                <span id="recordCount" style="font-size:0.78rem; font-weight:700; color:var(--text-muted);">0 Data Ditemukan</span>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">No</th>
                            <th>Waktu Pakai</th>
                            <th>Waktu Lepas</th>
                            <th>Durasi Sesi</th>
                            <th>Driver</th>
                            <th>Kendaraan</th>
                            <th style="text-align:center;">Foto Kunci</th>
                            <th>Notes Driver</th>
                            <th>Pelepas / Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <span class="material-symbols-outlined">autorenew</span>
                                    <p>Memuat data report...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="img-modal" onclick="if(event.target===this)closeImagePreview()">
        <button class="img-modal-close" onclick="closeImagePreview()">
            <span class="material-symbols-outlined">close</span>
        </button>
        <img id="imagePreviewTarget" src="" alt="Foto Penyerahan Kunci">
    </div>

    <!-- Edit Vehicle Log Modal -->
    <div id="editVehicleLogModal" class="img-modal" onclick="if(event.target===this)closeEditVehicleLogModal()" style="display:none;">
        <div style="background:white; border-radius:12px; padding:1.5rem; max-width:480px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
                <h3 style="margin:0; font-size:1.1rem; color:var(--text); display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="color:var(--primary);">edit</span> Edit Riwayat Pemakaian
                </h3>
                <button onclick="closeEditVehicleLogModal()" style="background:none; border:none; cursor:pointer; color:var(--text-muted);"><span class="material-symbols-outlined">close</span></button>
            </div>
            <form id="editVehicleLogForm" onsubmit="submitEditVehicleLog(event)">
                <input type="hidden" id="editLogId" value="">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.35rem;">Waktu Pakai (Assigned At)</label>
                    <input type="datetime-local" id="editAssignedAt" required class="form-control" style="width:100%; height:40px;">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.35rem;">Waktu Lepas (Released At - Kosongkan jika masih aktif)</label>
                    <input type="datetime-local" id="editReleasedAt" class="form-control" style="width:100%; height:40px;">
                </div>
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.35rem;">Notes Driver</label>
                    <textarea id="editReleaseNotes" class="form-control" style="width:100%; height:70px; padding:0.5rem; font-family:inherit;" placeholder="Catatan pelepasan kunci..."></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <button type="button" onclick="closeEditVehicleLogModal()" class="btn btn-ghost" style="height:38px; font-size:0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="height:38px; font-size:0.82rem;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const API_URL = 'api.php';
        let reportData = [];

        document.addEventListener('DOMContentLoaded', async () => {
            initDates();
            await loadFilterOptions();
            await loadReport();
        });

        function initDates() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            document.getElementById('dateFrom').value = formatDateIso(firstDay);
            document.getElementById('dateTo').value = formatDateIso(today);
        }

        function formatDateIso(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function setQuickDate(type) {
            const today = new Date();
            if (type === 'today') {
                document.getElementById('dateFrom').value = formatDateIso(today);
                document.getElementById('dateTo').value = formatDateIso(today);
            } else if (type === '7days') {
                const past7 = new Date();
                past7.setDate(today.getDate() - 6);
                document.getElementById('dateFrom').value = formatDateIso(past7);
                document.getElementById('dateTo').value = formatDateIso(today);
            } else if (type === 'month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                document.getElementById('dateFrom').value = formatDateIso(firstDay);
                document.getElementById('dateTo').value = formatDateIso(today);
            }
            loadReport();
        }

        async function loadFilterOptions() {
            try {
                const [dRes, vRes] = await Promise.all([
                    fetch(`${API_URL}?action=get_drivers`),
                    fetch(`${API_URL}?action=get_vehicles`)
                ]);
                const drivers = await dRes.json();
                const vehicles = await vRes.json();

                const dSel = document.getElementById('driverFilter');
                drivers.forEach(d => {
                    const id = d.user_id || d.id;
                    const name = d.driver_name || d.name;
                    if (id && name) {
                        dSel.innerHTML += `<option value="${id}">${name}</option>`;
                    }
                });

                const vSel = document.getElementById('vehicleFilter');
                vehicles.forEach(v => {
                    vSel.innerHTML += `<option value="${v.id}">${v.name} (${v.plate_number})</option>`;
                });
            } catch (err) {
                console.error('Error loading filter options:', err);
            }
        }

        async function loadReport() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const driverId = document.getElementById('driverFilter').value;
            const vehicleId = document.getElementById('vehicleFilter').value;
            const status = document.getElementById('statusFilter').value;

            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-outlined">autorenew</span><p>Memuat data report...</p></div></td></tr>`;

            try {
                const query = new URLSearchParams({
                    action: 'get_vehicle_reports',
                    date_from: dateFrom,
                    date_to: dateTo,
                    driver_id: driverId,
                    vehicle_id: vehicleId,
                    status: status
                });

                const res = await fetch(`${API_URL}?${query.toString()}`);
                reportData = await res.json();
                renderReportTable(reportData);
            } catch (err) {
                console.error('Load report error:', err);
                tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state" style="color:#ef4444;"><span class="material-symbols-outlined">error</span><p>Gagal memuat data report.</p></div></td></tr>`;
            }
        }

        function renderReportTable(data) {
            const tbody = document.getElementById('reportTableBody');

            if (!Array.isArray(data)) {
                document.getElementById('recordCount').innerText = `0 Data Ditemukan`;
                document.getElementById('kpiTotalSesi').innerText = '0';
                document.getElementById('kpiAktifNow').innerText = '0';
                document.getElementById('kpiDilepasToday').innerText = '0';
                tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state" style="color:#ef4444;"><span class="material-symbols-outlined">error</span><p>${(data && data.error) ? data.error : 'Gagal memuat data report.'}</p></div></td></tr>`;
                return;
            }

            document.getElementById('recordCount').innerText = `${data.length} Data Ditemukan`;

            // Calculate KPIs
            document.getElementById('kpiTotalSesi').innerText = data.length;
            const activeNow = data.filter(row => !row.released_at).length;
            document.getElementById('kpiAktifNow').innerText = activeNow;

            const todayStr = formatDateIso(new Date());
            const releasedToday = data.filter(row => row.released_at && row.released_at.startsWith(todayStr)).length;
            document.getElementById('kpiDilepasToday').innerText = releasedToday;

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-outlined">no_sim</span><p>Tidak ada data pemakaian kendaraan pada periode ini.</p></div></td></tr>`;
                return;
            }

            tbody.innerHTML = data.map((row, idx) => {
                const assignTimeStr = fmtDateTime(row.assigned_at);
                const releaseTimeStr = row.released_at ? fmtDateTime(row.released_at) : '<span class="badge-active"><span class="material-symbols-outlined" style="font-size:14px;">key</span> SEDANG AKTIF</span>';
                const durationStr = calcDuration(row.assigned_at, row.released_at);

                const keyPhotoHtml = row.release_photo 
                    ? `<img src="uploads/${row.release_photo}" class="img-thumb" title="Klik untuk lihat foto kunci" onclick="openImagePreview('uploads/${row.release_photo}')">`
                    : '<span style="color:var(--text-muted); font-size:0.75rem; font-style:italic;">-</span>';

                const notesHtml = row.release_notes
                    ? `<div style="font-size:0.78rem; font-weight:600; color:var(--text); max-width:240px; word-wrap:break-word;">"${row.release_notes}"</div>`
                    : '<span style="color:var(--text-muted); font-size:0.75rem; font-style:italic;">-</span>';

                const releaserHtml = row.released_at 
                    ? `<div style="font-size:0.75rem; font-weight:600;">${row.release_by_name ? 'Oleh: ' + row.release_by_name : 'Oleh Driver'}</div><span class="badge-released">⚪ Dilepas</span>`
                    : '<span class="badge-active">🟢 Aktif</span>';

                return `
                    <tr>
                        <td style="text-align:center; font-weight:700; color:var(--text-muted); font-size:0.75rem;">${idx + 1}</td>
                        <td style="font-size:0.78rem; font-weight:700;">${assignTimeStr}</td>
                        <td style="font-size:0.78rem;">${releaseTimeStr}</td>
                        <td style="font-size:0.78rem; font-weight:700; color:var(--primary);">${durationStr}</td>
                        <td style="font-size:0.8rem; font-weight:700;">${row.driver_name}</td>
                        <td>
                            <div style="font-weight:700; font-size:0.8rem; margin-bottom:2px;">${row.vehicle_name}</div>
                            <span class="plate-tag">${row.plate_number}</span>
                        </td>
                        <td style="text-align:center;">${keyPhotoHtml}</td>
                        <td>${notesHtml}</td>
                        <td style="font-size:0.75rem;">${releaserHtml}</td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; gap:4px; justify-content:flex-end;">
                                <button class="btn-icon" onclick="openEditVehicleLog(${row.id})" title="Edit Riwayat">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="btn-icon danger" onclick="deleteVehicleLog(${row.id})" title="Hapus Riwayat">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function fmtDateTime(dtStr) {
            if (!dtStr) return '-';
            const dt = new Date(dtStr.replace(/-/g, '/'));
            if (isNaN(dt)) return dtStr;
            return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                   dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
        }

        function calcDuration(startStr, endStr) {
            if (!startStr) return '-';
            const tStart = new Date(startStr.replace(/-/g, '/')).getTime();
            const tEnd = endStr ? new Date(endStr.replace(/-/g, '/')).getTime() : new Date().getTime();
            if (isNaN(tStart) || isNaN(tEnd) || tEnd < tStart) return '-';

            const diffSec = Math.floor((tEnd - tStart) / 1000);
            const hours = Math.floor(diffSec / 3600);
            const mins = Math.floor((diffSec % 3600) / 60);

            if (hours > 0) {
                return `${hours} Jam ${mins} Mnt`;
            } else {
                return `${mins} Menit`;
            }
        }

        function openImagePreview(url) {
            const cacheBuster = (url.includes('?') ? '&' : '?') + 't=' + Date.now();
            document.getElementById('imagePreviewTarget').src = url + cacheBuster;
            document.getElementById('imagePreviewModal').style.display = 'flex';
        }

        function closeImagePreview() {
            document.getElementById('imagePreviewModal').style.display = 'none';
        }

        function openEditVehicleLog(id) {
            const row = reportData.find(r => r.id == id);
            if (!row) return;

            document.getElementById('editLogId').value = row.id;
            document.getElementById('editAssignedAt').value = formatDatetimeLocal(row.assigned_at);
            document.getElementById('editReleasedAt').value = row.released_at ? formatDatetimeLocal(row.released_at) : '';
            document.getElementById('editReleaseNotes').value = row.release_notes || '';

            document.getElementById('editVehicleLogModal').style.display = 'flex';
        }

        function closeEditVehicleLogModal() {
            document.getElementById('editVehicleLogModal').style.display = 'none';
        }

        function formatDatetimeLocal(dtStr) {
            if (!dtStr) return '';
            const dt = new Date(dtStr.replace(/-/g, '/'));
            if (isNaN(dt)) return '';
            const pad = (n) => String(n).padStart(2, '0');
            return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
        }

        async function submitEditVehicleLog(e) {
            e.preventDefault();
            const id = document.getElementById('editLogId').value;
            const assigned_at = document.getElementById('editAssignedAt').value;
            const released_at = document.getElementById('editReleasedAt').value;
            const release_notes = document.getElementById('editReleaseNotes').value;

            const fd = new FormData();
            fd.append('id', id);
            fd.append('assigned_at', assigned_at.replace('T', ' ') + ':00');
            if (released_at) {
                fd.append('released_at', released_at.replace('T', ' ') + ':00');
            } else {
                fd.append('released_at', '');
            }
            fd.append('release_notes', release_notes);

            try {
                const res = await fetch(`${API_URL}?action=edit_vehicle_log`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    closeEditVehicleLogModal();
                    await loadReport();
                } else {
                    alert(data.error || 'Gagal memperbarui data');
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi');
            }
        }

        async function deleteVehicleLog(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus data riwayat pemakaian kendaraan ini?')) return;
            try {
                const fd = new FormData();
                fd.append('id', id);
                const res = await fetch(`${API_URL}?action=delete_vehicle_log`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    await loadReport();
                } else {
                    alert(data.error || 'Gagal menghapus data');
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi');
            }
        }

        function makeExcelHyperlink(fileName) {
            if (!fileName) return '-';
            const origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const url = (fileName.startsWith('http://') || fileName.startsWith('https://')) ? fileName : (origin + basePath + '/uploads/' + fileName);
            return { f: `HYPERLINK("${url}", "${url}")`, v: url };
        }

        function exportExcel() {
            if (!reportData || reportData.length === 0) {
                alert('Tidak ada data untuk diexport.');
                return;
            }

            const exportRows = reportData.map((row, idx) => ({
                'No': idx + 1,
                'Waktu Pakai': row.assigned_at || '',
                'Waktu Lepas': row.released_at || 'SEDANG AKTIF',
                'Durasi': calcDuration(row.assigned_at, row.released_at),
                'Nama Driver': row.driver_name || '',
                'Nama Kendaraan': row.vehicle_name || '',
                'No. Plat': row.plate_number || '',
                'Link Foto Kunci': makeExcelHyperlink(row.release_photo),
                'Notes Driver': row.release_notes || '',
                'Status': row.released_at ? 'Dilepas' : 'Aktif',
                'Dilepas Oleh': row.release_by_name || (row.released_at ? 'Driver' : '-')
            }));

            const ws = XLSX.utils.json_to_sheet(exportRows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Report Kendaraan");
            XLSX.writeFile(wb, `Report_Pakai_Lepas_Mobil_${document.getElementById('dateFrom').value}_to_${document.getElementById('dateTo').value}.xlsx`);
        }
    </script>
</body>

</html>
