<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('expedisi');
$can_write = canWriteMenu('expedisi');
$user_role = $_SESSION['role'] ?? '';

// Specific rules for 'admin' role
$can_add = $can_write;
$can_edit = $can_write;
$can_delete = $can_write;

if ($user_role === 'admin') {
    $can_add = true;    // Admin can add
    $can_edit = false;  // Admin cannot edit
    $can_delete = false; // Admin cannot delete
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Vendor Expedisi | TMS</title>
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
                    <span class="material-symbols-outlined">corporate_fare</span>
                </div>
                <div>
                    <h1>Kelola Vendor Expedisi</h1>
                    <p>Daftar perusahaan ekspedisi luar untuk pengiriman</p>
                </div>
            </div>
            <?php if ($can_add): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <span class="material-symbols-outlined">add</span>
                    Tambah Vendor
                </button>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"
                style="display:flex; justify-content:space-between; align-items:center; gap:2rem; flex-wrap:nowrap; padding-bottom:1.5rem; border-bottom:1px solid var(--border); margin-bottom:1.5rem;">
                <span class="card-title" style="margin-bottom:0; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:24px;">corporate_fare</span>
                    Daftar Vendor Ekspedisi
                </span>

                <div class="table-filters">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="vendorSearch" placeholder="Cari nama, CP, alamat..."
                            oninput="renderVendors()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no-col">No</th>
                            <th>Nama Vendor</th>
                            <th>Contact Person</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <?php if ($can_edit || $can_delete): ?>
                                <th style="text-align:right;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="vendorBody">
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem;">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)closeAdd()" style="display: none;">
        <div class="modal-box" style="max-width:460px;">
            <button class="modal-close" onclick="closeAdd()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">add_circle</span>
                Tambah Vendor Baru
            </div>
            <form id="addForm">
                <div class="form-group">
                    <label>Nama Perusahaan (Vendor)</label>
                    <input type="text" name="name" required placeholder="Contoh: PT. JNE Express">
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" placeholder="Nama PIC">
                </div>
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="phone" placeholder="021-xxxx / 0812-xxxx">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" rows="3" placeholder="Alamat kantor vendor"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width:100%; justify-content:center; margin-top:1rem;">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Vendor
                </button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)closeEdit()" style="display: none;">
        <div class="modal-box" style="max-width:460px;">
            <button class="modal-close" onclick="closeEdit()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">edit_square</span>
                Edit Vendor
            </div>
            <form id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nama Perusahaan (Vendor)</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" id="editCP">
                </div>
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="phone" id="editPhone">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" id="editAddress" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width:100%; justify-content:center; margin-top:1rem;">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <script>
        const API = 'api.php';
        const CAN_ADD = <?php echo $can_add ? 'true' : 'false'; ?>;
        const CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
        const CAN_DELETE = <?php echo $can_delete ? 'true' : 'false'; ?>;

        // ===== LOAD VENDORS (SMART SEARCH) =====
        let allVendors = [];

        async function initVendors() {
            try {
                const res = await fetch(`${API}?action=get_expedisi_vendors`);
                allVendors = await res.json();
                renderVendors();
            } catch (e) { console.error(e); }
        }

        function renderVendors() {
            const searchTerm = document.getElementById('vendorSearch').value.toLowerCase();
            const body = document.getElementById('vendorBody');

            const filtered = allVendors.filter(v => {
                return !searchTerm ||
                    v.name.toLowerCase().includes(searchTerm) ||
                    (v.contact_person && v.contact_person.toLowerCase().includes(searchTerm)) ||
                    (v.address && v.address.toLowerCase().includes(searchTerm));
            });

            if (filtered.length === 0) {
                body.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">Tidak ada vendor ditemukan.</td></tr>';
                return;
            }

            const highlight = (text) => {
                if (!searchTerm || !text) return text || '-';
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                return text.replace(regex, '<mark>$1</mark>');
            };

            body.innerHTML = filtered.map((v, i) => `
                <tr>
                    <td class="no-col">${i + 1}</td>
                    <td><strong>${highlight(v.name)}</strong></td>
                    <td>${highlight(v.contact_person)}</td>
                    <td>${v.phone || '-'}</td>
                    <td style="font-size:0.85rem; color:var(--text-muted);">${highlight(v.address)}</td>
                    ${(CAN_EDIT || CAN_DELETE) ? `
                    <td style="text-align:right;">
                        <div style="display:inline-flex; gap:0.5rem;">
                            ${CAN_EDIT ? `
                            <button class="btn-icon" onclick="openEditModal(${v.id})" title="Edit">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            ` : ''}
                            ${CAN_DELETE ? `
                            <button class="btn-icon danger" onclick="deleteVendor(${v.id})" title="Hapus">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                    ` : ''}
                </tr>
            `).join('');
        }

        async function loadVendors() { await initVendors(); }

        function openAddModal() {
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
            const res = await fetch(`${API}?action=add_expedisi_vendor`, { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if (data.success) {
                closeAdd();
                initVendors();
                e.target.reset();
            }
        };

        // EDIT LOGIC
        async function openEditModal(id) {
            const res = await fetch(`${API}?action=get_expedisi_vendor&id=${id}`);
            const v = await res.json();
            if (v.error) return alert(v.error);

            document.getElementById('editId').value = v.id;
            document.getElementById('editName').value = v.name;
            document.getElementById('editCP').value = v.contact_person || '';
            document.getElementById('editPhone').value = v.phone || '';
            document.getElementById('editAddress').value = v.address || '';

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
            const res = await fetch(`${API}?action=edit_expedisi_vendor`, { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if (data.success) {
                closeEdit();
                initVendors();
            } else {
                alert('Gagal mengupdate vendor.');
            }
        };

        async function deleteVendor(id) {
            if (!confirm('Hapus vendor ini?')) return;
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${API}?action=delete_expedisi_vendor`, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) initVendors();
        }

        initVendors();
    </script>
</body>

</html>