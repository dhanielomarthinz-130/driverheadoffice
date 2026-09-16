<?php
require_once 'auth_check.php';
checkLogin();

// Kelola Database hanya untuk Super Admin (controller)
if (($_SESSION['role'] ?? '') !== 'controller') {
    header("Location: dashboard_summary");
    exit();
}
$can_write = true;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Database | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .db-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .db-stat-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .db-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
        }

        .db-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .db-stat-info h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
        }

        .db-stat-info p {
            font-size: 0.8rem;
            color: var(--text-sub);
            font-weight: 600;
            margin-top: 0.1rem;
        }

        /* Bulk Action Bar */
        .bulk-action-bar {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .bulk-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .table-search-box input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #ffffff;
            font-size: 0.875rem;
            outline: none;
        }

        .table-search-box .material-symbols-outlined {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 20px;
        }

        /* Checkbox styling */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #ef4444;
        }

        /* Preset Quick Actions */
        .quick-actions-bar {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .quick-actions-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .quick-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quick-btn:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.25rem;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            transition: all 0.2s ease;
        }

        .btn-danger-custom:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
        }

        .btn-danger-custom:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon" style="background:#e0e7ff; color:#4f46e5;">
                    <span class="material-symbols-outlined">database</span>
                </div>
                <div>
                    <h1>Kelola Database Table</h1>
                    <p>Manajemen & pembersihan data tabel database secara efisien</p>
                </div>
            </div>
            <div class="page-actions">
                <button class="btn btn-ghost" onclick="loadTables()">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh Data
                </button>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="db-stats-grid">
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:#e0e7ff; color:#4f46e5;">
                    <span class="material-symbols-outlined">table_chart</span>
                </div>
                <div class="db-stat-info">
                    <h3 id="statTotalTables">0</h3>
                    <p>Total Tabel Database</p>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:#dcfce7; color:#16a34a;">
                    <span class="material-symbols-outlined">format_list_bulleted</span>
                </div>
                <div class="db-stat-info">
                    <h3 id="statTotalRows">0</h3>
                    <p>Total Record Data</p>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:#e0f2fe; color:#0284c7;">
                    <span class="material-symbols-outlined">hard_drive</span>
                </div>
                <div class="db-stat-info">
                    <h3 id="statTotalSize">0 MB</h3>
                    <p>Ukuran Penyimpanan</p>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:#fef3c7; color:#d97706;">
                    <span class="material-symbols-outlined">shield_person</span>
                </div>
                <div class="db-stat-info">
                    <h3><?php echo $can_write ? 'Akses Penuh' : 'Lihat saja'; ?></h3>
                    <p>Status Izin Pengguna</p>
                </div>
            </div>
        </div>

        <?php if ($can_write): ?>
        <!-- PRESET QUICK ACTIONS -->
        <div class="quick-actions-bar">
            <div class="quick-actions-title">
                <span class="material-symbols-outlined" style="color:#ef4444;">bolt</span>
                Pembersihan Cepat (Preset Single Table)
            </div>
            <div class="quick-btn-group">
                <button type="button" class="quick-btn" onclick="executeBatchTruncate(['system_logs'])">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Kosongkan Log Sistem (system_logs)
                </button>
                <button type="button" class="quick-btn" onclick="executeBatchTruncate(['drivers_location'])">
                    <span class="material-symbols-outlined">wrong_location</span>
                    Reset Riwayat GPS Driver (drivers_location)
                </button>
                <button type="button" class="quick-btn" onclick="executeBatchTruncate(['expedisi_tasks'])">
                    <span class="material-symbols-outlined">local_shipping</span>
                    Kosongkan Tugas Ekspedisi (expedisi_tasks)
                </button>
                <button type="button" class="quick-btn" onclick="executeBatchTruncate(['pickup_requests'])">
                    <span class="material-symbols-outlined">package_2</span>
                    Reset Request Pickup (pickup_requests)
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- BULK ACTION & TABLE CONTAINER -->
        <div class="card">
            <div class="bulk-action-bar">
                <div class="bulk-left">
                    <?php if ($can_write): ?>
                        <button type="button" class="btn-danger-custom" id="btnDeleteSelected" onclick="truncateSelectedTables()" disabled>
                            <span class="material-symbols-outlined">delete_sweep</span>
                            <span>Kosongkan Tabel Terpilih (<span id="selectedCount">0</span>)</span>
                        </button>
                        <button type="button" class="btn btn-ghost" onclick="selectAllTables(true)">
                            <span class="material-symbols-outlined">select_all</span>
                            <span>Pilih Semua</span>
                        </button>
                        <button type="button" class="btn btn-ghost" onclick="selectAllTables(false)">
                            <span>Batalkan Pilihan</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="tableSearch" placeholder="Cari nama tabel..." onkeyup="filterTables()">
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php if ($can_write): ?>
                                <th style="width:42px; text-align:center;">
                                    <input type="checkbox" id="selectAllHeader" onclick="toggleSelectAllHeader(this)" title="Pilih Semua Tabel">
                                </th>
                            <?php endif; ?>
                            <th>Nama Tabel</th>
                            <th>Engine</th>
                            <th>Jumlah Record (Baris)</th>
                            <th>Ukuran Data</th>
                            <th>Auto Increment ID</th>
                            <th>Terakhir Disimpan</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dbTablesBody">
                        <tr>
                            <td colspan="<?php echo $can_write ? 8 : 7; ?>" style="text-align:center; padding:2.5rem;">
                                <span class="material-symbols-outlined" style="font-size:32px; color:var(--primary); animation:spin 1s linear infinite;">progress_activity</span>
                                <div style="margin-top:0.5rem;">Memuat daftar tabel database...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let allTables = [];
        const CAN_WRITE = <?php echo $can_write ? 'true' : 'false'; ?>;

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        async function loadTables() {
            const tbody = document.getElementById('dbTablesBody');
            const colSpan = CAN_WRITE ? 8 : 7;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" style="text-align:center; padding:2.5rem;">
                        <span class="material-symbols-outlined" style="font-size:32px; color:var(--primary); animation:spin 1s linear infinite;">progress_activity</span>
                        <div style="margin-top:0.5rem;">Memuat daftar tabel database...</div>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`${API_URL}?action=get_database_tables`);
                const data = await res.json();

                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; color:#dc2626; padding:2rem;">${data.error || 'Gagal memuat data'}</td></tr>`;
                    return;
                }

                allTables = data.tables || [];

                // Render Summary Stats
                document.getElementById('statTotalTables').innerText = data.total_tables.toLocaleString('id-ID');
                document.getElementById('statTotalRows').innerText = data.total_rows.toLocaleString('id-ID');
                document.getElementById('statTotalSize').innerText = formatBytes(data.total_size);

                renderTableList(allTables);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; color:#dc2626; padding:2rem;">Gagal terhubung ke server.</td></tr>`;
            }
        }

        function renderTableList(tables) {
            const tbody = document.getElementById('dbTablesBody');
            const colSpan = CAN_WRITE ? 8 : 7;

            if (tables.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; padding:2rem; color:var(--text-sub);">Tidak ada tabel ditemukan</td></tr>`;
                return;
            }

            tbody.innerHTML = tables.map(t => {
                const isCritical = ['users', 'roles', 'role_permissions'].includes(t.name);
                return `
                    <tr>
                        ${CAN_WRITE ? `
                            <td style="text-align:center;">
                                <input type="checkbox" class="table-cb" value="${t.name}" onchange="updateSelectedCount()">
                            </td>
                        ` : ''}
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <span class="material-symbols-outlined" style="font-size:18px; color:var(--primary);">table_rows</span>
                                <strong style="font-family:monospace; font-size:0.9rem;">${t.name}</strong>
                                ${isCritical ? '<span style="font-size:0.68rem; padding:1px 6px; background:#fef3c7; color:#b45309; border-radius:4px; font-weight:700;">Sistem Utuh</span>' : ''}
                            </div>
                        </td>
                        <td><span style="font-size:0.8rem; color:var(--text-sub);">${t.engine || 'InnoDB'}</span></td>
                        <td><span class="badge" style="background:#f1f5f9; color:var(--text-main); font-weight:700;">${t.rows.toLocaleString('id-ID')} Baris</span></td>
                        <td><span style="font-size:0.85rem; font-weight:600;">${formatBytes(t.data_size)}</span></td>
                        <td><code>${t.auto_increment ? '#' + t.auto_increment : '-'}</code></td>
                        <td><span style="font-size:0.8rem; color:var(--text-sub);">${t.update_time || t.create_time || '-'}</span></td>
                        <td style="text-align:right;">
                            ${CAN_WRITE ? `
                                <button class="btn btn-ghost" style="padding:0.35rem 0.75rem; font-size:0.8rem; color:#dc2626; border-color:#fecaca; background:#fef2f2;" onclick="executeBatchTruncate(['${t.name}'])" title="Kosongkan seluruh data tabel ini">
                                    <span class="material-symbols-outlined" style="font-size:16px;">delete_sweep</span>
                                    Kosongkan
                                </button>
                            ` : '<span style="font-size:0.75rem; color:var(--text-sub);">Hanya Lihat</span>'}
                        </td>
                    </tr>
                `;
            }).join('');

            updateSelectedCount();
        }

        function filterTables() {
            const query = document.getElementById('tableSearch').value.toLowerCase().trim();
            const filtered = allTables.filter(t => t.name.toLowerCase().includes(query));
            renderTableList(filtered);
        }

        function toggleSelectAllHeader(headerCb) {
            selectAllTables(headerCb.checked);
        }

        function selectAllTables(checked) {
            const checkboxes = document.querySelectorAll('.table-cb');
            checkboxes.forEach(cb => cb.checked = checked);
            const header = document.getElementById('selectAllHeader');
            if (header) header.checked = checked;
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.table-cb:checked');
            const count = selected.length;
            const btnDelete = document.getElementById('btnDeleteSelected');
            const countSpan = document.getElementById('selectedCount');

            if (countSpan) countSpan.innerText = count;
            if (btnDelete) btnDelete.disabled = (count === 0);

            const allCbs = document.querySelectorAll('.table-cb');
            const headerCb = document.getElementById('selectAllHeader');
            if (headerCb && allCbs.length > 0) {
                headerCb.checked = (allCbs.length === count);
            }
        }

        function truncateSelectedTables() {
            const selected = Array.from(document.querySelectorAll('.table-cb:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            executeBatchTruncate(selected);
        }

        async function executeBatchTruncate(tableNamesList) {
            if (!tableNamesList || tableNamesList.length === 0) return;

            const tableStr = tableNamesList.join(', ');
            const confirmMsg = tableNamesList.length === 1 
                ? `Apakah Anda yakin ingin MENGOSONGKAN tabel "${tableStr}"?\n\nSemua data di tabel ini akan dihapus secara permanen.` 
                : `Apakah Anda yakin ingin MENGOSONGKAN ${tableNamesList.length} tabel terpilih berikut?\n\n(${tableStr})\n\nSemua data di tabel terpilih akan dihapus secara permanen.`;

            if (!confirm(confirmMsg)) return;

            const btnDelete = document.getElementById('btnDeleteSelected');
            if (btnDelete) btnDelete.disabled = true;

            try {
                const formData = new FormData();
                tableNamesList.forEach(name => formData.append('table_names[]', name));

                const res = await fetch(`${API_URL}?action=truncate_database_table`, {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    alert(data.message || 'Proses pembersihan tabel selesai.');
                    loadTables();
                } else {
                    alert('Gagal: ' + (data.error || 'Terjadi kesalahan saat mengosongkan tabel.'));
                    if (btnDelete) btnDelete.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('Koneksi ke server gagal.');
                if (btnDelete) btnDelete.disabled = false;
            }
        }

        // Helper style for spinner
        const style = document.createElement('style');
        style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);

        loadTables();
    </script>
</body>

</html>
