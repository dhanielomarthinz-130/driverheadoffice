<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('roles');
$can_write = canWriteMenu('roles');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Role | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .role-preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        /* Modal Style */
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
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">security</span>
                </div>
                <div>
                    <h1>Manajemen Role</h1>
                    <p>Atur jenis pengguna dan hak akses di dalam sistem</p>
                </div>
            </div>
            <div class="page-actions">
                <?php if ($can_write): ?>
                    <button class="btn btn-primary" onclick="openAddRole()">
                        <span class="material-symbols-outlined">add_moderator</span>
                        Tambah Role Baru
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Role</th>
                            <th>Role Key (System ID)</th>
                            <th>Deskripsi</th>
                            <th>Ikon & Warna</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="roleTableBody">
                        <tr>
                            <td colspan="5" style="text-align:center;">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div id="roleModal" class="modal-overlay" onclick="if(event.target===this)closeModal()" style="display: none;">
        <div class="modal-box" style="max-width:480px;">
            <button class="modal-close" onclick="closeModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined" id="modalIcon">add_moderator</span>
                <span id="modalTitle">Tambah Role Baru</span>
            </div>

            <form id="roleForm">
                <input type="hidden" name="id" id="roleId">
                <div class="form-group">
                    <label>Nama Role (Display Name)</label>
                    <input type="text" name="role_name" id="roleName" required placeholder="Contoh: Manajer Gudang">
                </div>
                <div class="form-group">
                    <label>Role Key (ID Sistem - Unik)</label>
                    <input type="text" name="role_key" id="roleKey" required placeholder="Contoh: warehouse_manager">
                    <small>Gunakan huruf kecil dan garis bawah (underscore).</small>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="roleDesc" style="min-height:80px;"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>Icon (Material Symbol)</label>
                        <input type="text" name="icon" id="roleIcon" placeholder="Contoh: shield" value="person">
                    </div>
                    <div class="form-group">
                        <label>Warna Brand</label>
                        <input type="color" name="color" id="roleColor" value="#6366f1">
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-ghost" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan Role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        const CAN_WRITE = <?php echo $can_write ? 'true' : 'false'; ?>;

        async function loadRoles() {
            const res = await fetch(`${API_URL}?action=get_roles`);
            const roles = await res.json();
            const tbody = document.getElementById('roleTableBody');

            tbody.innerHTML = roles.map(r => `
                <tr>
                    <td><strong>${r.role_name}</strong></td>
                    <td><code>${r.role_key}</code></td>
                    <td style="color:var(--text-sub); font-size:0.85rem;">${r.description || '-'}</td>
                    <td>
                        <div class="role-preview-badge" style="background:${r.color}15; color:${r.color}; border:1px solid ${r.color}40;">
                            <span class="material-symbols-outlined" style="font-size:18px;">${r.icon || 'person'}</span>
                            ${r.role_name}
                        </div>
                    </td>
                    <td style="text-align:right;">
                        ${CAN_WRITE ? `
                        <button class="btn-icon" onclick='openEditRole(${JSON.stringify(r)})'><span class="material-symbols-outlined">edit</span></button>
                        ${['admin', 'driver', 'controller'].includes(r.role_key) ? '' : `<button class="btn-icon danger" onclick="deleteRole(${r.id})"><span class="material-symbols-outlined">delete</span></button>`}
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        }

        function openAddRole() {
            document.getElementById('roleForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('modalTitle').innerText = 'Tambah Role Baru';
            const m = document.getElementById('roleModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function openEditRole(role) {
            document.getElementById('roleId').value = role.id;
            document.getElementById('roleName').value = role.role_name;
            document.getElementById('roleKey').value = role.role_key;
            document.getElementById('roleDesc').value = role.description || '';
            document.getElementById('roleIcon').value = role.icon || 'person';
            document.getElementById('roleColor').value = role.color || '#6366f1';
            document.getElementById('modalTitle').innerText = 'Edit Role';

            const m = document.getElementById('roleModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function closeModal() {
            const m = document.getElementById('roleModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        document.getElementById('roleForm').onsubmit = async (e) => {
            e.preventDefault();
            const action = document.getElementById('roleId').value ? 'edit_role' : 'add_role';
            const res = await fetch(`${API_URL}?action=${action}`, { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if (data.success) { loadRoles(); closeModal(); } else { alert(data.error); }
        };

        async function deleteRole(id) {
            if (!confirm('Hapus role ini? User dengan role ini mungkin akan kehilangan akses.')) return;
            const res = await fetch(`${API_URL}?action=delete_role`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            });
            const data = await res.json();
            if (data.success) loadRoles();
        }

        loadRoles();
    </script>
</body>

</html>