<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('logs');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Sistem | IMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .log-action {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .action-add { background: #dcfce7; color: #166534; }
        .action-edit { background: #fef3c7; color: #92400e; }
        .action-delete { background: #fee2e2; color: #991b1b; }
        .action-login { background: #dbeafe; color: #1e40af; }
        .action-default { background: #f1f5f9; color: #475569; }

        .log-time {
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .log-user {
            font-weight: 600;
            color: var(--primary);
        }
        .log-desc {
            font-size: 0.85rem;
            color: var(--text-sub);
        }

        /* Unified Filter Styles */
        .table-filters {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: nowrap;
        }

        .search-wrapper {
            position: relative;
            width: 280px;
        }

        .search-wrapper input {
            width: 100%;
            height: 42px;
            padding: 0 1rem 0 2.75rem;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .search-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: white;
        }

        .search-wrapper .material-symbols-outlined {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 20px;
            pointer-events: none;
            opacity: 0.8;
        }

        .select-filter {
            height: 42px;
            min-width: 150px;
            padding: 0 2.5rem 0 1rem;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .select-filter:hover { border-color: var(--primary-light); }
        .select-filter:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        /* Numbering Column */
        .no-col {
            width: 50px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
        }

        mark {
            background: #fde68a;
            color: #92400e;
            padding: 0 2px;
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">history_edu</span>
                </div>
                <div>
                    <h1>Log Aktivitas</h1>
                    <p>Catatan 500 riwayat aktivitas terakhir di dalam sistem</p>
                </div>
            </div>
            <div class="page-actions">
                <button class="btn btn-ghost" onclick="initLogs()">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh Log
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:nowrap; padding-bottom:1.5rem; border-bottom:1px solid var(--border); margin-bottom:1.5rem;">
                <span class="card-title" style="margin-bottom:0; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:24px;">list_alt</span>
                    Filter Log
                </span>

                <div class="table-filters">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="logSearch" placeholder="Cari deskripsi, IP..." oninput="renderLogs()">
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="date" id="logDateFrom" class="select-filter" style="min-width:140px; padding:0 0.75rem;" onchange="initLogs()">
                        <span style="color:var(--text-muted); font-weight:700;">-</span>
                        <input type="date" id="logDateTo" class="select-filter" style="min-width:140px; padding:0 0.75rem;" onchange="initLogs()">
                    </div>
                    <select id="actionFilter" class="select-filter" onchange="renderLogs()" style="margin:0;">
                        <option value="">Semua Aksi</option>
                    </select>
                    <select id="userFilter" class="select-filter" onchange="renderLogs()" style="margin:0;">
                        <option value="">Semua Pengguna</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no-col">No</th>
                            <th style="width: 180px;">Waktu</th>
                            <th style="width: 150px;">Aksi</th>
                            <th style="width: 150px;">Pengguna</th>
                            <th>Deskripsi</th>
                            <th style="width: 130px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 3rem;">
                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.2; display: block; margin-bottom: 1rem;">sync</span>
                                Memuat data log...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // ===== LOAD LOGS (SMART SEARCH) =====
        let allLogs = [];

        async function initLogs() {
            const tbody = document.getElementById('logTableBody');
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 3rem;"><span class="material-symbols-outlined" style="font-size: 48px; animation: spin 1s linear infinite;">sync</span><br>Memuat data...</td></tr>`;
            
            try {
                const search = document.getElementById('logSearch').value;
                const from = document.getElementById('logDateFrom').value;
                const to = document.getElementById('logDateTo').value;
                
                const res = await fetch(`api.php?action=get_system_logs&search=${encodeURIComponent(search)}&date_from=${from}&date_to=${to}`);
                allLogs = await res.json();
                
                populateFilters();
                renderLogs();
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color: var(--danger); padding: 2rem;">Gagal memuat data.</td></tr>`;
            }
        }

        function populateFilters() {
            const actionFilter = document.getElementById('actionFilter');
            const userFilter = document.getElementById('userFilter');
            
            // Get unique actions and users
            const actions = [...new Set(allLogs.map(l => l.action))].sort();
            const users = [...new Set(allLogs.map(l => l.user_full_name || 'System'))].sort();
            
            actionFilter.innerHTML = '<option value="">Semua Aksi</option>';
            actions.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a;
                opt.innerText = a;
                actionFilter.appendChild(opt);
            });
            
            userFilter.innerHTML = '<option value="">Semua Pengguna</option>';
            users.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u;
                opt.innerText = u;
                userFilter.appendChild(opt);
            });
        }

        function renderLogs() {
            const searchTerm = document.getElementById('logSearch').value.toLowerCase();
            const actionSel = document.getElementById('actionFilter').value;
            const userSel = document.getElementById('userFilter').value;
            const tbody = document.getElementById('logTableBody');

            const filtered = allLogs.filter(log => {
                const matchesSearch = !searchTerm || 
                    log.description.toLowerCase().includes(searchTerm) || 
                    log.ip_address.toLowerCase().includes(searchTerm) ||
                    (log.username && log.username.toLowerCase().includes(searchTerm));
                
                const matchesAction = !actionSel || log.action === actionSel;
                const matchesUser = !userSel || (log.user_full_name || 'System') === userSel;
                
                return matchesSearch && matchesAction && matchesUser;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 3rem; color: var(--text-muted);">Tidak ada log ditemukan.</td></tr>`;
                return;
            }

            const highlight = (text) => {
                if (!searchTerm || !text) return text || '-';
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                return text.replace(regex, '<mark>$1</mark>');
            };

            tbody.innerHTML = filtered.map((log, i) => {
                let actionClass = 'action-default';
                const act = log.action.toLowerCase();
                if (act.includes('add') || act.includes('create') || act.includes('assign')) actionClass = 'action-add';
                if (act.includes('edit') || act.includes('update')) actionClass = 'action-edit';
                if (act.includes('delete') || act.includes('remove')) actionClass = 'action-delete';
                if (act.includes('login')) actionClass = 'action-login';

                return `
                    <tr>
                        <td class="no-col">${i + 1}</td>
                        <td class="log-time">${log.created_at}</td>
                        <td><span class="log-action ${actionClass}">${log.action}</span></td>
                        <td>
                            <div class="log-user">${log.user_full_name || 'System'}</div>
                            <small style="color: var(--text-muted)">@${log.username || 'system'}</small>
                        </td>
                        <td class="log-desc">${highlight(log.description)}</td>
                        <td style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">${highlight(log.ip_address)}</td>
                    </tr>
                `;
            }).join('');
        }

        // Add spin animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        window.onload = initLogs;
    </script>
</body>
</html>
