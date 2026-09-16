<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('roles');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
    <style>
        .permission-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .role-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .role-item {
            padding: 1rem;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .role-item:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .role-item.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .role-item.active .material-symbols-outlined {
            color: white;
        }

        .role-item .material-symbols-outlined {
            color: var(--text-sub);
        }

        .menu-access-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .menu-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .menu-row:last-child {
            border-bottom: none;
        }

        .menu-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .menu-name {
            font-weight: 700;
            font-size: 0.95rem;
        }

        .menu-desc {
            font-size: 0.8rem;
            color: var(--text-sub);
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--primary);
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">key</span>
                </div>
                <div>
                    <h1>Settings</h1>
                    <p>Konfigurasi menu apa saja yang bisa dilihat oleh setiap Role</p>
                </div>
            </div>
        </div>

        <div class="permission-grid">
            <div class="role-list" id="roleList">
                <!-- Roles injected here -->
            </div>

            <div class="menu-access-card">
                <div id="activeRoleTitle"
                    style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--primary);">
                    Pilih role untuk mengatur akses
                </div>

                <div id="menuContainer">
                    <!-- Menus injected here -->
                    <div style="text-align:center; padding: 3rem; color: var(--text-sub);">
                        Silakan pilih salah satu role di sebelah kiri.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let currentRoleKey = null;

        const MENUS = [
            { key: 'dashboard', name: 'Dashboard (WEB)', desc: 'Monitor Delivery & Pickup (Dashboard Utama)', icon: 'dashboard' },
            { key: 'monitor', name: 'Live Tracking (WEB)', desc: 'Pelacakan driver secara real-time', icon: 'map' },
            { key: 'assign_tasks', name: 'Penugasan Driver (WEB)', desc: 'Manajemen penugasan pengiriman', icon: 'assignment' },
            { key: 'performance', name: 'Time Line (WEB)', desc: 'Analisis heatmap & urutan kejadian', icon: 'local_fire_department' },
            { key: 'report_tracking', name: 'Report Tracking Driver (WEB)', desc: 'Laporan lengkap alur penugasan & kilometer driver', icon: 'analytics' },
            { key: 'report_vehicles', name: 'Report Pakai & Lepas Mobil (WEB)', desc: 'Laporan riwayat pemakaian & pelepasan kendaraan driver', icon: 'directions_car' },
            { key: 'driver_kpi', name: 'Driver KPI Leaderboard (WEB)', desc: 'Ranking & performa driver', icon: 'emoji_events' },
            { key: 'task_notes', name: 'Catatan Task / Task Note (WEB)', desc: 'Catatan tugas & pengingat harian pengguna', icon: 'checklist' },
            { key: 'locations', name: 'Lokasi POS (WEB)', desc: 'Manajemen master data alamat', icon: 'location_on' },
            { key: 'vehicles', name: 'Kelola Kendaraan (WEB)', desc: 'Manajemen armada mobil/motor', icon: 'local_shipping' },
            { key: 'users', name: 'Kelola Pengguna (WEB)', desc: 'Manajemen akun login user', icon: 'group' },
            { key: 'roles', name: 'Manajemen Role (WEB)', desc: 'Pengaturan jenis hak akses', icon: 'security' },
            { key: 'mobile_menu', name: 'Menu Utama Mobile (App)', desc: 'Tampilan beranda menu icon di HP', icon: 'smartphone' },
            { key: 'tasks', name: 'Menu Driver (Mobile)', desc: 'Tampilan menu tugas di aplikasi HP Driver', icon: 'task' },
            { key: 'logs', name: 'Log Aktivitas Sistem (WEB)', desc: 'Riwayat catatan aktivitas seluruh pengguna', icon: 'history_edu' }
        ];

        async function loadRoles() {
            const res = await fetch(`${API_URL}?action=get_roles`);
            const roles = await res.json();
            const list = document.getElementById('roleList');

            list.innerHTML = roles.map(r => {
                // Clean up role names (replace _ with space and capitalize)
                const displayName = r.role_name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                return `<div class="role-item" id="role-${r.role_key}" onclick="selectRole('${r.role_key}', '${displayName}')">
                    <span class="material-symbols-outlined">person</span>
                    ${displayName}
                </div>`;
            }).join('');
        }

        async function selectRole(roleKey, roleName) {
            currentRoleKey = roleKey;

            // UI Toggle active
            document.querySelectorAll('.role-item').forEach(el => el.classList.remove('active'));
            document.getElementById(`role-${roleKey}`).classList.add('active');

            document.getElementById('activeRoleTitle').innerText = `Akses Menu: ${roleName}`;

            // Load permissions for this role
            const res = await fetch(`${API_URL}?action=get_permissions&role_key=${roleKey}`);
            const perms = await res.json();

            const container = document.getElementById('menuContainer');
            container.innerHTML = MENUS.map(m => {
                const p = perms.find(x => x.menu_key === m.key);
                const isAllowed = p ? p.can_access == 1 : false;
                const isWriteAllowed = p ? p.can_write == 1 : false;

                return `
                    <div class="menu-row">
                        <div class="menu-info">
                            <div class="menu-icon"><span class="material-symbols-outlined">${m.icon}</span></div>
                            <div>
                                <div class="menu-name">${m.name}</div>
                                <div class="menu-desc">${m.desc}</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-sub);">LIHAT</span>
                                <label class="switch">
                                    <input type="checkbox" id="read_${m.key}" ${isAllowed ? 'checked' : ''} onchange="toggleAccess('${m.key}')">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-sub);">EDIT</span>
                                <label class="switch">
                                    <input type="checkbox" id="write_${m.key}" ${isWriteAllowed ? 'checked' : ''} onchange="toggleAccess('${m.key}')">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function toggleAccess(menuKey) {
            const isAllowed = document.getElementById('read_' + menuKey).checked;
            const isWriteAllowed = document.getElementById('write_' + menuKey).checked;

            const formData = new FormData();
            formData.append('role_key', currentRoleKey);
            formData.append('menu_key', menuKey);
            formData.append('can_access', isAllowed ? 1 : 0);
            formData.append('can_write', isWriteAllowed ? 1 : 0);

            const res = await fetch(`${API_URL}?action=update_permission`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (!data.success) alert('Gagal memperbarui akses');
        }

        loadRoles();
    </script>
</body>

</html>