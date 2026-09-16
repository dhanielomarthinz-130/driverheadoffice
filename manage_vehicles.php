<?php
require_once 'auth_check.php';
checkLogin();
if (!canAccessMenu('vehicles')) {
    header("Location: dashboard_summary.php");
    exit();
}
$can_write = canWriteMenu('vehicles');
$user_role = $_SESSION['role'] ?? '';

// Specific rules for 'admin' role
$can_add = $can_write;
$can_edit = $can_write;
$can_delete = $can_write;

if ($user_role === 'admin') {
    $can_add = true;    // Admin can add
    $can_edit = true;   // Admin can edit
    $can_delete = true; // Admin can delete
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kendaraan | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
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

        .modal-box {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 420px;
            position: relative;
            box-shadow: var(--shadow-lg);
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title .material-symbols-outlined {
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-muted);
        }

        .empty-state .material-symbols-outlined {
            font-size: 48px;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .plate-tag {
            display: inline-block;
            background: #1e293b;
            color: #f8fafc;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            border: 2px solid #334155;
            letter-spacing: 1px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .vehicle-name-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .vehicle-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-glow);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .vehicle-icon .material-symbols-outlined {
            font-size: 20px;
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
                    <span class="material-symbols-outlined">directions_car</span>
                </div>
                <div>
                    <h1>Kelola Kendaraan</h1>
                    <p>Manajemen data armada dan pelat nomor kendaraan</p>
                </div>
            </div>
            <div class="page-actions" style="display:flex; gap:0.5rem; align-items:center;">
                <?php if ($can_add): ?>
                    <button class="btn btn-primary" onclick="openAddModal()" style="height:40px; width:40px; border:none; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding:0; border-radius:8px;" title="Tambah Kendaraan">
                        <span class="material-symbols-outlined" style="font-size:20px;">add</span>
                    </button>
                <?php endif; ?>
                <button onclick="exportExcel()" style="height:40px; width:40px; background:#107c41; color:white; border:none; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.2s;" title="Download Excel" onmouseover="this.style.background='#0b592e'" onmouseout="this.style.background='#107c41'">
                    <span class="material-symbols-outlined" style="font-size:20px; color:white;">table_view</span>
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header"
                style="display:flex; justify-content:space-between; align-items:center; gap:2rem; flex-wrap:nowrap; padding-bottom:1.5rem; border-bottom:1px solid var(--border); margin-bottom:1.5rem;">
                <span class="card-title" style="margin-bottom:0; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:24px;">local_shipping</span>
                    Daftar Armada Kendaraan
                </span>

                <div class="table-filters">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="vehicleSearch" placeholder="Cari nama atau pelat..."
                            oninput="renderVehicles()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no-col">No</th>
                            <th>Nama Kendaraan</th>
                            <th>Pelat Nomor</th>
                            <?php if ($can_edit || $can_delete): ?>
                                <th style="width: 150px; text-align: right;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="vehicleTableBody">
                        <tr>
                            <td colspan="4" class="empty-state">
                                <span class="material-symbols-outlined">autorenew</span>
                                <p>Memuat data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span class="material-symbols-outlined">add_circle</span>
                    Tambah Kendaraan Baru
                </h3>
            </div>
            <form id="addForm">
                <div class="form-group">
                    <label>Nama Kendaraan / Tipe</label>
                    <input type="text" name="name" placeholder="Contoh: Hino Ranger, Grand Max" required>
                </div>
                <div class="form-group">
                    <label>Nomor Pelat (License Plate)</label>
                    <input type="text" name="plate_number" placeholder="Contoh: B 1234 ABC" required>
                </div>
                <div style="display:flex; gap:0.75rem; margin-top:2rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeAdd()"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan
                    </button>
                </div>
                <div id="addMsg" style="margin-top:1rem; text-align:center; font-size:0.85rem;"></div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span class="material-symbols-outlined">edit_square</span>
                    Ubah Data Kendaraan
                </h3>
            </div>
            <form id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nama Kendaraan / Tipe</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Nomor Pelat (License Plate)</label>
                    <input type="text" name="plate_number" id="editPlate" required>
                </div>
                <div style="display:flex; gap:0.75rem; margin-top:2rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeEdit()"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Perubahan
                    </button>
                </div>
                <div id="editMsg" style="margin-top:1rem; text-align:center; font-size:0.85rem;"></div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const API_URL = 'api.php';
        const CAN_ADD = <?php echo $can_add ? 'true' : 'false'; ?>;
        const CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
        const CAN_DELETE = <?php echo $can_delete ? 'true' : 'false'; ?>;

        // ===== LOAD VEHICLES TABLE (SMART SEARCH) =====
        let allVehicles = [];

        async function initVehicles() {
            try {
                const res = await fetch(`${API_URL}?action=get_vehicles_raw`);
                allVehicles = await res.json();
                renderVehicles();
            } catch (err) {
                console.error('Init vehicles error:', err);
            }
        }

        function renderVehicles() {
            const searchTerm = document.getElementById('vehicleSearch').value.toLowerCase();
            const tbody = document.getElementById('vehicleTableBody');

            const filtered = allVehicles.filter(v => {
                return !searchTerm ||
                    v.name.toLowerCase().includes(searchTerm) ||
                    v.plate_number.toLowerCase().includes(searchTerm);
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">directions_car</span>
                        <p>Tidak ada kendaraan ditemukan.</p>
                    </div>
                </td></tr>`;
                return;
            }

            tbody.innerHTML = filtered.map((v, i) => {
                const highlight = (text) => {
                    if (!searchTerm || !text) return text || '-';
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    return text.replace(regex, '<mark>$1</mark>');
                };

                return `
                    <tr>
                        <td class="no-col">${i + 1}</td>
                        <td>
                            <div class="vehicle-name-wrap">
                                <div class="vehicle-icon">
                                    <span class="material-symbols-outlined">local_shipping</span>
                                </div>
                                <strong>${highlight(v.name)}</strong>
                            </div>
                        </td>
                        <td><span class="plate-tag">${highlight(v.plate_number)}</span></td>
                        ${(CAN_EDIT || CAN_DELETE) ? `
                        <td style="text-align:right;">
                            <div style="display:inline-flex; gap:0.5rem;">
                                ${CAN_EDIT ? `
                                <button class="btn-icon" onclick="openEdit(${v.id})" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                ` : ''}
                                ${CAN_DELETE ? `
                                <button class="btn-icon danger" onclick="deleteVehicle(${v.id})" title="Hapus">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                ` : ''}
                            </div>
                        </td>
                        ` : ''}
                    </tr>`;
            }).join('');
        }

        async function loadVehicles() {
            await initVehicles();
        }

        function openAddModal() {
            document.getElementById('addForm').reset();
            document.getElementById('addMsg').innerHTML = '';
            const m = document.getElementById('addModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function closeAdd() {
            const m = document.getElementById('addModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('addMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';

            try {
                const res = await fetch(`${API_URL}?action=add_vehicle`, {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();
                if (data.success) {
                    msg.innerHTML = '<span style="color:var(--success); font-weight:600;">✓ Berhasil disimpan</span>';
                    loadVehicles();
                    setTimeout(closeAdd, 1000);
                } else {
                    msg.innerHTML = `<span style="color:var(--danger); font-weight:600;">Gagal: ${data.error || 'Terjadi kesalahan'}</span>`;
                }
            } catch (err) {
                msg.innerHTML = `<span style="color:var(--danger); font-weight:600;">Error: Respon server tidak valid</span>`;
                console.error(err);
            }
        };

        async function openEdit(id) {
            document.getElementById('editMsg').innerHTML = '';
            const res = await fetch(`${API_URL}?action=get_vehicle&id=${id}`);
            const v = await res.json();
            if (v.error) return alert(v.error);

            document.getElementById('editId').value = v.id;
            document.getElementById('editName').value = v.name;
            document.getElementById('editPlate').value = v.plate_number;

            const m = document.getElementById('editModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function closeEdit() {
            const m = document.getElementById('editModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('editMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';

            try {
                const res = await fetch(`${API_URL}?action=edit_vehicle`, {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();
                if (data.success) {
                    msg.innerHTML = '<span style="color:var(--success); font-weight:600;">✓ Perubahan disimpan</span>';
                    loadVehicles();
                    setTimeout(closeEdit, 1000);
                } else {
                    msg.innerHTML = `<span style="color:var(--danger); font-weight:600;">Gagal: ${data.error || 'Terjadi kesalahan'}</span>`;
                }
            } catch (err) {
                msg.innerHTML = `<span style="color:var(--danger); font-weight:600;">Error: Respon server tidak valid</span>`;
                console.error(err);
            }
        };

        async function deleteVehicle(id) {
            if (!confirm('Hapus kendaraan ini?')) return;
            try {
                const res = await fetch(`${API_URL}?action=delete_vehicle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                });
                const data = await res.json();
                if (data.success) loadVehicles();
                else alert(data.error || 'Gagal menghapus');
            } catch (err) {
                alert('Error: Gagal memproses penghapusan. Cek koneksi atau server.');
                console.error(err);
            }
        }

        function exportExcel() {
            const searchTerm = document.getElementById('vehicleSearch').value.toLowerCase();

            const filtered = allVehicles.filter(v => {
                return !searchTerm ||
                    v.name.toLowerCase().includes(searchTerm) ||
                    v.plate_number.toLowerCase().includes(searchTerm);
            });

            if (!filtered.length) return alert('Tidak ada data kendaraan untuk di-export.');

            const rows = [['No', 'Nama Kendaraan', 'Pelat Nomor']];
            
            filtered.forEach((v, i) => {
                const row = [
                    i + 1,
                    v.name || '—',
                    v.plate_number || '—'
                ];
                rows.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Daftar Kendaraan');
            XLSX.writeFile(wb, 'Daftar_Kendaraan_TMS.xlsx');
        }

        // Initial load
        window.onload = initVehicles;
    </script>
</body>

</html>