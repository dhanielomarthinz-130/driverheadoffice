<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('locations');
$can_write = canWriteMenu('locations');
$user_role = $_SESSION['role'] ?? '';

// Specific rules for 'admin' role
$can_add = $can_write;
$can_edit = $can_write;
$can_delete = $can_write;

if ($user_role === 'admin') {
    $can_add = true;    // Admin can add
    $can_edit = $can_write;  // Admin can edit if given write permission in DB
    $can_delete = $can_write; // Admin can delete if given write permission in DB
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lokasi | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .coord-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .coord-mode-toggle {
            display: flex;
            background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .coord-mode-btn {
            flex: 1;
            padding: 0.55rem;
            border: none;
            background: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.18s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
        }

        .coord-mode-btn .material-symbols-outlined {
            font-size: 16px;
        }

        .coord-mode-btn.active {
            background: var(--primary);
            color: white;
        }

        .gmaps-input-wrap {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
        }

        .gmaps-input-wrap input {
            flex: 1;
        }

        .btn-resolve {
            padding: 0 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            white-space: nowrap;
            transition: 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-resolve:hover {
            background: var(--primary-dark);
        }

        .btn-resolve .material-symbols-outlined {
            font-size: 16px;
        }

        .btn-resolve:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .resolve-result {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            display: none;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
        }

        .resolve-result .material-symbols-outlined {
            font-size: 16px;
        }

        .resolve-ok {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .resolve-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .type-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
        }

        .type-store {
            color: #7c3aed;
        }

        .type-warehouse {
            color: #0369a1;
        }

        .type-office {
            color: #0f172a;
        }

        .type-home {
            color: #16a34a;
        }

        .type-mall {
            color: #db2777;
        }

        .type-kantor {
            color: #ea580c;
        }

        .type-others {
            color: #059669;
        }

        .addr-cell {
            font-size: 0.72rem;
            color: var(--text-muted);
            max-width: 280px;
            white-space: normal;
            line-height: 1.4;
        }

        /* Modal */
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

        /* Google Maps link in table */
        .maps-link {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #1a73e8;
            text-decoration: none;
            padding: 0.3rem 0.6rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid #d2e3fc;
            background: #e8f0fe;
            transition: all 0.18s;
            white-space: nowrap;
        }

        .maps-link:hover {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        .maps-link .material-symbols-outlined {
            font-size: 14px;
        }

        .coord-mono {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-family: monospace;
            display: block;
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
            font-size: 0.82rem;
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
            min-width: 160px;
            padding: 0 2.5rem 0 1rem;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 0.8rem;
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

        .select-filter:hover {
            border-color: var(--primary-light);
        }

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
            font-size: 0.72rem;
            font-weight: 600;
        }

        /* General Table Cell Font Size Reduction */
        table tbody td {
            font-size: 0.75rem;
            padding: 0.625rem 0.875rem !important;
        }

        table tbody td strong {
            font-size: 0.78rem;
        }

        mark {
            background: #fde68a;
            color: #92400e;
            padding: 0 2px;
            border-radius: 2px;
        }

        tr.selected-row {
            background-color: rgba(239, 68, 68, 0.06) !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">map</span>
                </div>
                <div>
                    <h1>Kelola Lokasi</h1>
                    <p>Tambah gudang, toko, dan titik pengiriman</p>
                </div>
            </div>
            <div class="page-actions" style="display:flex; gap:0.5rem; align-items:center;">
                <?php if ($can_delete): ?>
                    <button class="btn btn-ghost danger" onclick="deleteSelectedLocations()" id="bulkDeleteBtn" style="display:none; height:40px; color:#ef4444; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.05); white-space:nowrap; border-radius:8px; align-items:center; gap:6px; font-weight:600; cursor:pointer;">
                        <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                        Hapus Terpilih (<span id="bulkDeleteCount">0</span>)
                    </button>
                <?php endif; ?>
                <?php if ($can_add): ?>
                    <button class="btn btn-secondary" onclick="openTypeModal()" style="height:40px; border:1.5px solid var(--border); background:var(--surface); color:var(--text); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding:0 0.75rem; border-radius:8px; gap:6px; font-weight:600; font-size:0.8rem;" title="Kelola Tipe Lokasi">
                        <span class="material-symbols-outlined" style="font-size:20px; color:var(--primary);">category</span>
                        Tipe Lokasi
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()" style="height:40px; width:40px; border:none; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding:0; border-radius:8px;" title="Tambah Lokasi Baru">
                        <span class="material-symbols-outlined" style="font-size:20px;">add_location</span>
                    </button>
                <?php endif; ?>
                <button onclick="exportExcel()" style="height:40px; width:40px; background:#107c41; color:white; border:none; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.2s;" title="Download Excel" onmouseover="this.style.background='#0b592e'" onmouseout="this.style.background='#107c41'">
                    <span class="material-symbols-outlined" style="font-size:20px; color:white;">table_view</span>
                </button>
            </div>
        </div>

        <!-- Location Table -->
        <div class="card">
            <div class="card-header"
                style="display:flex; justify-content:space-between; align-items:center; gap:2rem; flex-wrap:nowrap; padding-bottom:1.5rem; border-bottom:1px solid var(--border); margin-bottom:1.5rem;">
                <span class="card-title" style="margin-bottom:0; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:24px;">location_on</span>
                    Daftar Lokasi
                </span>

                <div class="table-filters">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="locationSearch" placeholder="Cari nama, kota, alamat..."
                            oninput="renderLocations()">
                    </div>
                    <select id="typeFilter" class="select-filter" onchange="renderLocations()" style="margin:0;">
                        <option value="">Semua Tipe</option>
                        <option value="store">🏪 Toko (Store)</option>
                        <option value="warehouse">🏭 Gudang (Warehouse)</option>
                        <option value="office">🏢 Head Office</option>
                        <option value="home">🏠 Rumah (Home)</option>
                        <option value="mall">🛍️ Mall</option>
                        <option value="kantor">🏢 Kantor</option>
                        <option value="others">📍 Others</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php if ($can_delete): ?>
                                <th style="width:40px; text-align:center;">
                                    <input type="checkbox" id="selectAllLocs" onclick="toggleSelectAllLocs(this)" title="Pilih Semua">
                                </th>
                            <?php endif; ?>
                            <th class="no-col">No</th>
                            <th>Nama Lokasi</th>
                            <th>Type</th>
                            <th>Kota</th>
                            <th>Alamat</th>
                            <th>Koordinat</th>
                            <th>Google Maps</th>
                            <?php if ($can_edit || $can_delete): ?>
                                <th style="text-align:right;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="locationTableBody">
                        <tr>
                            <td colspan="<?php echo $can_delete ? 9 : (($can_edit || $can_delete) ? 8 : 7); ?>">
                                <div class="empty-state">
                                    <span class="material-symbols-outlined">autorenew</span>
                                    <p>Memuat data...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== ADD MODAL ===== -->
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)closeAdd()" style="display: none;">
        <div class="modal-box" style="max-width:500px;">
            <button class="modal-close" onclick="closeAdd()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">add_location</span>
                Tambah Lokasi Baru
            </div>
            <p class="modal-subtitle">Isi informasi dan koordinat lokasi baru</p>

            <form id="locationForm">
                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" name="name" required placeholder="Contoh: Alfamart Depok 1">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="form-group">
                        <label>Tipe Lokasi</label>
                        <select name="type" required>
                            <option value="store">🏪 Toko (Store)</option>
                            <option value="warehouse">🏭 Gudang (Warehouse)</option>
                            <option value="office">🏢 Head Office</option>
                            <option value="home">🏠 Rumah (Home)</option>
                            <option value="mall">🛍️ Mall</option>
                            <option value="kantor">🏢 Kantor</option>
                            <option value="others">📍 Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kota</label>
                        <input type="text" name="city" required placeholder="Contoh: Jakarta">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <input type="text" name="address" placeholder="Jl. Contoh No. 1, Kecamatan...">
                </div>

                <!-- Coordinate Input Mode -->
                <div class="form-group">
                    <label>Koordinat</label>
                    <div class="coord-mode-toggle">
                        <button type="button" class="coord-mode-btn active" id="btnModeManual"
                            onclick="setCoordMode('manual')">
                            <span class="material-symbols-outlined">edit_location</span> Manual
                        </button>
                        <button type="button" class="coord-mode-btn" id="btnModeGmaps" onclick="setCoordMode('gmaps')">
                            <span class="material-symbols-outlined">share_location</span> Google Maps Link
                        </button>
                    </div>

                    <!-- Manual mode -->
                    <div id="modeManual">
                        <div class="coord-row">
                            <div>
                                <input type="text" id="addLat" name="lat" placeholder="Latitude: -6.1234" required>
                            </div>
                            <div>
                                <input type="text" id="addLng" name="lng" placeholder="Longitude: 106.8765" required>
                            </div>
                        </div>
                    </div>

                    <!-- Google Maps Link mode -->
                    <div id="modeGmaps" style="display:none;">
                        <div class="gmaps-input-wrap">
                            <input type="text" id="gmapsUrl" placeholder="https://maps.app.goo.gl/..."
                                style="font-size:0.82rem;">
                            <button type="button" class="btn-resolve" id="btnResolve" onclick="resolveLink('add')">
                                <span class="material-symbols-outlined">my_location</span>
                                Ambil
                            </button>
                        </div>
                        <div class="resolve-result" id="resolveResult"></div>
                        <!-- Hidden fields for resolved coords -->
                        <input type="hidden" id="addLatHidden" name="lat">
                        <input type="hidden" id="addLngHidden" name="lng">
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                    <button type="button" onclick="closeAdd()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Lokasi
                    </button>
                </div>
                <div id="formMsg" style="margin-top:0.875rem; text-align:center;"></div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT MODAL ===== -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)closeEdit()" style="display: none;">
        <div class="modal-box" style="max-width:500px;">
            <button class="modal-close" onclick="closeEdit()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">edit_location</span>
                Edit Lokasi
            </div>
            <p class="modal-subtitle">Perbarui informasi dan koordinat lokasi</p>

            <form id="editForm">
                <input type="hidden" id="editId" name="id">

                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" id="editName" name="name" required placeholder="Nama lokasi">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="form-group">
                        <label>Tipe</label>
                        <select id="editType" name="type" required>
                            <option value="store">🏪 Toko</option>
                            <option value="warehouse">🏭 Gudang</option>
                            <option value="office">🏢 Head Office</option>
                            <option value="home">🏠 Rumah</option>
                            <option value="mall">🛍️ Mall</option>
                            <option value="kantor">🏢 Kantor</option>
                            <option value="others">📍 Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kota</label>
                        <input type="text" id="editCity" name="city" required placeholder="Kota">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <input type="text" id="editAddress" name="address" placeholder="Jl. Contoh No. 1...">
                </div>

                <!-- Coordinate Mode for Edit -->
                <div class="form-group">
                    <label>Koordinat</label>
                    <div class="coord-mode-toggle">
                        <button type="button" class="coord-mode-btn active" id="editBtnModeManual"
                            onclick="setEditCoordMode('manual')">
                            <span class="material-symbols-outlined">edit_location</span> Manual
                        </button>
                        <button type="button" class="coord-mode-btn" id="editBtnModeGmaps"
                            onclick="setEditCoordMode('gmaps')">
                            <span class="material-symbols-outlined">share_location</span> Google Maps Link
                        </button>
                    </div>

                    <div id="editModeManual">
                        <div class="coord-row">
                            <div>
                                <input type="text" id="editLat" name="lat" placeholder="Latitude" required>
                            </div>
                            <div>
                                <input type="text" id="editLng" name="lng" placeholder="Longitude" required>
                            </div>
                        </div>
                    </div>

                    <div id="editModeGmaps" style="display:none;">
                        <div class="gmaps-input-wrap">
                            <input type="text" id="editGmapsUrl" placeholder="https://maps.app.goo.gl/..."
                                style="font-size:0.82rem;">
                            <button type="button" class="btn-resolve" id="editBtnResolve" onclick="resolveLink('edit')">
                                <span class="material-symbols-outlined">my_location</span>
                                Ambil
                            </button>
                        </div>
                        <div class="resolve-result" id="editResolveResult"></div>
                        <input type="hidden" id="editLatHidden" name="lat">
                        <input type="hidden" id="editLngHidden" name="lng">
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                    <button type="button" onclick="closeEdit()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Perubahan
                    </button>
                </div>
                <div id="editFormMsg" style="margin-top:0.75rem; text-align:center; font-size:0.85rem;"></div>
            </form>
        </div>
    </div>

    <!-- ===== MANAGE LOCATION TYPES MODAL ===== -->
    <div id="typeModal" class="modal-overlay" onclick="if(event.target===this)closeTypeModal()" style="display: none;">
        <div class="modal-box" style="max-width:550px;">
            <button class="modal-close" onclick="closeTypeModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">category</span>
                Kelola Tipe Lokasi
            </div>
            <p class="modal-subtitle">Tambah dan edit kategori tipe lokasi custom</p>

            <form id="typeForm" onsubmit="saveLocationType(event)" style="background:var(--surface-2); padding:1rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1.25rem;">
                <input type="hidden" id="typeEditId" value="">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="form-group" style="margin-bottom:0.5rem;">
                        <label>Key Tipe (Unik, misal: booth)</label>
                        <input type="text" id="typeKeyInput" required placeholder="Contoh: pos_booth" style="font-size:0.8rem;">
                    </div>
                    <div class="form-group" style="margin-bottom:0.5rem;">
                        <label>Nama / Label Tipe</label>
                        <input type="text" id="typeNameInput" required placeholder="Contoh: 🎪 POS Booth" style="font-size:0.8rem;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-top:0.5rem;">
                    <div class="form-group" style="margin-bottom:0.5rem;">
                        <label>Pilih Icon (Material Symbol)</label>
                        <select id="typeIconInput" style="width:100%; height:38px; padding:0 0.5rem; border:1px solid var(--border); border-radius:6px; font-size:0.82rem; background:var(--surface);">
                            <option value="location_on">📍 Pin (location_on)</option>
                            <option value="pin_drop">📍 Pin Drop (pin_drop)</option>
                            <option value="storefront">🏪 Toko (storefront)</option>
                            <option value="warehouse">🏭 Gudang (warehouse)</option>
                            <option value="corporate_fare">🏢 Head Office (corporate_fare)</option>
                            <option value="business">🏢 Kantor (business)</option>
                            <option value="home">🏠 Rumah (home)</option>
                            <option value="local_mall">🛍️ Mall (local_mall)</option>
                            <option value="local_cafe">☕ Warkop / Cafe (local_cafe)</option>
                            <option value="fastfood">🍔 Restoran (fastfood)</option>
                            <option value="domain">🏬 Gedung (domain)</option>
                            <option value="event">🎪 Event / Booth (event)</option>
                            <option value="shopping_cart">🛒 Retail (shopping_cart)</option>
                            <option value="local_gas_station">⛽ SPBU (local_gas_station)</option>
                            <option value="local_hospital">🏥 Rumah Sakit (local_hospital)</option>
                            <option value="inventory_2">📦 Logistik (inventory_2)</option>
                            <option value="local_shipping">🚚 Armada / Hub (local_shipping)</option>
                            <option value="map">🗺️ Peta (map)</option>
                            <option value="sell">🏷️ POS (sell)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0.5rem;">
                        <label>Warna Tipe (Hex)</label>
                        <input type="color" id="typeColorInput" value="#6366f1" style="height:38px; width:100%; border:1px solid var(--border); border-radius:6px; cursor:pointer;">
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
                    <button type="button" id="btnCancelTypeEdit" onclick="resetTypeForm()" class="btn btn-ghost btn-sm" style="display:none;">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                        <span id="btnSaveTypeLabel">Simpan Tipe Baru</span>
                    </button>
                </div>
            </form>

            <div style="max-height:260px; overflow-y:auto; border:1px solid var(--border); border-radius:10px;">
                <table style="width:100%; font-size:0.8rem;">
                    <thead style="position:sticky; top:0; background:var(--surface); z-index:2;">
                        <tr>
                            <th style="padding:8px 12px; text-align:left;">Key & Nama Tipe</th>
                            <th style="padding:8px 12px; text-align:center;">Badge Preview</th>
                            <th style="padding:8px 12px; text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="typeListTableBody">
                        <tr><td colspan="3" style="text-align:center; padding:1.5rem;">Memuat data tipe...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const API_URL = 'api.php';
        const CAN_ADD = <?php echo $can_add ? 'true' : 'false'; ?>;
        const CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
        const CAN_DELETE = <?php echo $can_delete ? 'true' : 'false'; ?>;
        let coordMode = 'manual';
        let editCoordMode = 'manual';

        // ===== OPEN / CLOSE ADD MODAL =====
        function openAddModal() {
            // Reset form
            document.getElementById('locationForm').reset();
            document.getElementById('addLat').value = '';
            document.getElementById('addLng').value = '';
            document.getElementById('addLatHidden').value = '';
            document.getElementById('addLngHidden').value = '';
            document.getElementById('resolveResult').style.display = 'none';
            document.getElementById('formMsg').innerHTML = '';
            setCoordMode('manual');
            const m = document.getElementById('addModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function closeAdd() {
            const m = document.getElementById('addModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        // ===== COORDINATE MODE TOGGLE =====
        function setCoordMode(mode) {
            coordMode = mode;
            document.getElementById('modeManual').style.display = mode === 'manual' ? 'block' : 'none';
            document.getElementById('modeGmaps').style.display = mode === 'gmaps' ? 'block' : 'none';
            document.getElementById('btnModeManual').classList.toggle('active', mode === 'manual');
            document.getElementById('btnModeGmaps').classList.toggle('active', mode === 'gmaps');

            document.getElementById('addLat').required = mode === 'manual';
            document.getElementById('addLng').required = mode === 'manual';
            if (mode === 'manual') {
                document.getElementById('addLatHidden').name = '';
                document.getElementById('addLngHidden').name = '';
                document.getElementById('addLat').name = 'lat';
                document.getElementById('addLng').name = 'lng';
            } else {
                document.getElementById('addLat').name = '';
                document.getElementById('addLng').name = '';
                document.getElementById('addLatHidden').name = 'lat';
                document.getElementById('addLngHidden').name = 'lng';
            }
        }

        function setEditCoordMode(mode) {
            editCoordMode = mode;
            document.getElementById('editModeManual').style.display = mode === 'manual' ? 'block' : 'none';
            document.getElementById('editModeGmaps').style.display = mode === 'gmaps' ? 'block' : 'none';
            document.getElementById('editBtnModeManual').classList.toggle('active', mode === 'manual');
            document.getElementById('editBtnModeGmaps').classList.toggle('active', mode === 'gmaps');

            document.getElementById('editLat').required = mode === 'manual';
            document.getElementById('editLng').required = mode === 'manual';
            if (mode === 'manual') {
                document.getElementById('editLatHidden').name = '';
                document.getElementById('editLngHidden').name = '';
                document.getElementById('editLat').name = 'lat';
                document.getElementById('editLng').name = 'lng';
            } else {
                document.getElementById('editLat').name = '';
                document.getElementById('editLng').name = '';
                document.getElementById('editLatHidden').name = 'lat';
                document.getElementById('editLngHidden').name = 'lng';
            }
        }

        // ===== RESOLVE GOOGLE MAPS LINK =====
        async function resolveLink(context) {
            const isEdit = context === 'edit';
            const urlInput = document.getElementById(isEdit ? 'editGmapsUrl' : 'gmapsUrl');
            const resultDiv = document.getElementById(isEdit ? 'editResolveResult' : 'resolveResult');
            const btn = document.getElementById(isEdit ? 'editBtnResolve' : 'btnResolve');
            const latField = document.getElementById(isEdit ? 'editLatHidden' : 'addLatHidden');
            const lngField = document.getElementById(isEdit ? 'editLngHidden' : 'addLngHidden');
            const url = urlInput.value.trim();

            if (!url) {
                showResolveResult(resultDiv, false, 'Masukkan URL Google Maps terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px; animation:spin 0.8s linear infinite;">autorenew</span> Proses...';
            resultDiv.style.display = 'none';

            try {
                const fd = new FormData();
                fd.append('url', url);
                const res = await fetch(`${API_URL}?action=resolve_maps_link`, { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    latField.value = data.lat;
                    lngField.value = data.lng;
                    document.getElementById(isEdit ? 'editLat' : 'addLat').placeholder = data.lat;
                    document.getElementById(isEdit ? 'editLng' : 'addLng').placeholder = data.lng;
                    showResolveResult(resultDiv, true, `✓ Koordinat ditemukan: ${data.lat}, ${data.lng}`);
                } else {
                    showResolveResult(resultDiv, false, data.error);
                }
            } catch (err) {
                showResolveResult(resultDiv, false, 'Gagal terhubung ke server.');
            }

            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">my_location</span> Ambil';
        }

        function showResolveResult(div, ok, msg) {
            div.className = 'resolve-result ' + (ok ? 'resolve-ok' : 'resolve-error');
            div.innerHTML = `<span class="material-symbols-outlined">${ok ? 'check_circle' : 'error'}</span> ${msg}`;
            div.style.display = 'flex';
        }

        // ===== LOAD LOCATIONS TABLE (SMART SEARCH) =====
        let allLocations = [];
        let allLocationTypes = [];

        async function loadLocationTypes() {
            try {
                const res = await fetch(`${API_URL}?action=get_location_types`);
                const data = await res.json();
                allLocationTypes = Array.isArray(data) ? data : [];
                populateTypeSelects();
            } catch (err) {
                console.error('Load location types error:', err);
                allLocationTypes = [];
                populateTypeSelects();
            }
        }

        function getIconEmoji(icon) {
            const map = {
                'storefront': '🏪',
                'warehouse': '🏭',
                'corporate_fare': '🏢',
                'business': '🏢',
                'home': '🏠',
                'local_mall': '🛍️',
                'local_cafe': '☕',
                'fastfood': '🍔',
                'domain': '🏬',
                'event': '🎪',
                'shopping_cart': '🛒',
                'local_gas_station': '⛽',
                'local_hospital': '🏥',
                'inventory_2': '📦',
                'local_shipping': '🚚',
                'map': '🗺️',
                'sell': '🏷️',
                'pin_drop': '📍',
                'location_on': '📍'
            };
            return map[icon] || '📍';
        }

        function populateTypeSelects() {
            const filterSel = document.getElementById('typeFilter');
            const addSel = document.querySelector('#locationForm select[name="type"]');
            const editSel = document.getElementById('editType');

            const optionsHtml = Array.isArray(allLocationTypes) ? allLocationTypes.map(t => {
                const emoji = getIconEmoji(t.icon);
                return `<option value="${t.type_key}">${emoji} ${t.type_name}</option>`;
            }).join('') : '';

            if (filterSel) {
                const curVal = filterSel.value;
                filterSel.innerHTML = `<option value="">Semua Tipe</option>` + optionsHtml;
                filterSel.value = curVal;
            }
            if (addSel) {
                addSel.innerHTML = optionsHtml;
            }
            if (editSel) {
                const curVal = editSel.value;
                editSel.innerHTML = optionsHtml;
                editSel.value = curVal;
            }
        }

        async function initLocations() {
            try {
                await loadLocationTypes();
            } catch (e) {
                console.error(e);
            }
            try {
                const res = await fetch(`${API_URL}?action=get_locations`);
                const data = await res.json();
                allLocations = Array.isArray(data) ? data : [];
                renderLocations();
            } catch (err) {
                console.error('Init locations error:', err);
                const tbody = document.getElementById('locationTableBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:var(--danger);">Gagal memuat lokasi: ${err.message}</td></tr>`;
                }
            }
        }

        function renderLocations() {
            const searchTerm = document.getElementById('locationSearch').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const tbody = document.getElementById('locationTableBody');

            const filtered = allLocations.filter(l => {
                const matchesSearch = !searchTerm ||
                    l.name.toLowerCase().includes(searchTerm) ||
                    l.city.toLowerCase().includes(searchTerm) ||
                    (l.address && l.address.toLowerCase().includes(searchTerm));

                const matchesType = !typeFilter || l.type === typeFilter;
                return matchesSearch && matchesType;
            });

            const totalCols = CAN_DELETE ? 9 : ((CAN_EDIT || CAN_DELETE) ? 8 : 7);
            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${totalCols}">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">location_off</span>
                        <p>Tidak ada lokasi ditemukan.</p>
                    </div>
                </td></tr>`;
                updateBulkDeleteState();
                return;
            }

            tbody.innerHTML = filtered.map((l, index) => {
                let typeHtml = '';
                const foundType = allLocationTypes.find(t => t.type_key === l.type);
                if (foundType) {
                    typeHtml = `<span class="type-indicator" style="color:${foundType.color}; font-weight:700;"><span class="material-symbols-outlined" style="font-size:16px;">${foundType.icon || 'location_on'}</span> ${foundType.type_name}</span>`;
                } else if (l.type === 'store') {
                    typeHtml = `<span class="type-indicator type-store"><span class="material-symbols-outlined" style="font-size:16px;">storefront</span> Toko</span>`;
                } else if (l.type === 'warehouse') {
                    typeHtml = `<span class="type-indicator type-warehouse"><span class="material-symbols-outlined" style="font-size:16px;">warehouse</span> Gudang</span>`;
                } else if (l.type === 'office') {
                    typeHtml = `<span class="type-indicator type-office"><span class="material-symbols-outlined" style="font-size:16px;">corporate_fare</span> Head Office</span>`;
                } else if (l.type === 'home') {
                    typeHtml = `<span class="type-indicator type-home"><span class="material-symbols-outlined" style="font-size:16px;">home</span> Rumah</span>`;
                } else if (l.type === 'mall') {
                    typeHtml = `<span class="type-indicator type-mall"><span class="material-symbols-outlined" style="font-size:16px;">local_mall</span> Mall</span>`;
                } else if (l.type === 'kantor') {
                    typeHtml = `<span class="type-indicator type-kantor"><span class="material-symbols-outlined" style="font-size:16px;">business</span> Kantor</span>`;
                } else if (l.type === 'others' || l.type === 'Others') {
                    typeHtml = `<span class="type-indicator type-others"><span class="material-symbols-outlined" style="font-size:16px;">pin_drop</span> Others</span>`;
                } else {
                    typeHtml = `<span class="type-indicator type-unknown"><span class="material-symbols-outlined" style="font-size:16px;">location_on</span> ${l.type || 'Lainnya'}</span>`;
                }

                const highlight = (text) => {
                    if (!searchTerm || !text) return text || '-';
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    return text.replace(regex, '<mark>$1</mark>');
                };

                const addrHtml = l.address
                    ? `<span class="addr-cell" title="${l.address}">${highlight(l.address)}</span>`
                    : `<span style="color:var(--text-muted); font-size:0.78rem; font-style:italic;">-</span>`;

                const lat = parseFloat(l.lat).toFixed(6);
                const lng = parseFloat(l.lng).toFixed(6);
                const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

                return `
                    <tr onclick="handleRowClick(event, ${l.id})" style="cursor:pointer;">
                        ${CAN_DELETE ? `<td style="text-align:center;" onclick="event.stopPropagation()"><input type="checkbox" class="loc-checkbox" value="${l.id}" id="chk_${l.id}" onchange="updateBulkDeleteState()"></td>` : ''}
                        <td class="no-col">${index + 1}</td>
                        <td style="white-space:nowrap;"><strong>${highlight(l.name)}</strong></td>
                        <td style="white-space:nowrap;">${typeHtml}</td>
                        <td style="white-space:nowrap;">${highlight(l.city)}</td>
                        <td>${addrHtml}</td>
                        <td>
                            <span class="coord-mono">${lat}</span>
                            <span class="coord-mono">${lng}</span>
                        </td>
                        <td>
                            <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer" class="maps-link" title="Buka di Google Maps" onclick="event.stopPropagation()">
                                <span class="material-symbols-outlined">map</span>
                                Lihat Map
                            </a>
                        </td>
                        ${(CAN_EDIT || CAN_DELETE) ? `
                        <td style="text-align:right;" onclick="event.stopPropagation()">
                            <div style="display:inline-flex; gap:0.375rem;">
                                ${CAN_EDIT ? `
                                <button class="btn-icon" onclick="openEdit(${l.id})" title="Edit Lokasi">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                ` : ''}
                                ${CAN_DELETE ? `
                                <button class="btn-icon danger" onclick="deleteLocation(${l.id})" title="Hapus Lokasi">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                ` : ''}
                            </div>
                        </td>
                        ` : ''}
                    </tr>`;
            }).join('');
            updateBulkDeleteState();
        }

        async function loadLocations() {
            await initLocations();
        }

        // ===== ADD LOCATION =====
        document.getElementById('locationForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('formMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';

            try {
                const lat = e.target.querySelector('[name="lat"]').value;
                const lng = e.target.querySelector('[name="lng"]').value;
                if (!lat || !lng) {
                    msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">Koordinat belum diisi atau belum di-resolve dari Google Maps.</span>';
                    return;
                }

                const res = await fetch(`${API_URL}?action=add_location`, { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    msg.innerHTML = '<span style="color:var(--success); font-weight:600; display:flex; align-items:center; gap:4px; justify-content:center;"><span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> Lokasi berhasil disimpan!</span>';
                    loadLocations();
                    setTimeout(() => closeAdd(), 1500);
                } else {
                    msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">' + (data.error || 'Gagal menyimpan lokasi.') + '</span>';
                }
            } catch (err) {
                msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">Terjadi kesalahan pada server.</span>';
                console.error(err);
            }
        };

        // ===== EDIT MODAL =====
        async function openEdit(id) {
            setEditCoordMode('manual');
            document.getElementById('editResolveResult').style.display = 'none';
            document.getElementById('editGmapsUrl').value = '';
            document.getElementById('editFormMsg').innerHTML = '';

            const res = await fetch(`${API_URL}?action=get_location&id=${id}`);
            const loc = await res.json();
            if (loc.error) { alert('Gagal memuat data lokasi.'); return; }

            document.getElementById('editId').value = loc.id;
            document.getElementById('editName').value = loc.name;
            document.getElementById('editType').value = loc.type;
            document.getElementById('editCity').value = loc.city;
            document.getElementById('editAddress').value = loc.address || '';
            document.getElementById('editLat').value = loc.lat;
            document.getElementById('editLng').value = loc.lng;

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
            const msg = document.getElementById('editFormMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';

            try {
                const lat = e.target.querySelector('[name="lat"]').value;
                const lng = e.target.querySelector('[name="lng"]').value;
                if (!lat || !lng) {
                    msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">Koordinat belum diisi atau belum di-resolve.</span>';
                    return;
                }

                const res = await fetch(`${API_URL}?action=edit_location`, { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    msg.innerHTML = '<span style="color:var(--success); font-weight:600;">✓ Perubahan disimpan!</span>';
                    loadLocations();
                    setTimeout(() => closeEdit(), 1200);
                } else {
                    msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">' + (data.error || 'Gagal menyimpan perubahan.') + '</span>';
                }
            } catch (err) {
                msg.innerHTML = '<span style="color:var(--danger); font-weight:600;">Terjadi kesalahan pada server.</span>';
                console.error(err);
            }
        };

        // ===== DELETE =====
        async function deleteLocation(id) {
            if (!confirm('Hapus lokasi ini? Tindakan ini tidak bisa dibatalkan.')) return;
            const res = await fetch(`${API_URL}?action=delete_location`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            });
            const data = await res.json();
            if (data.success) {
                loadLocations();
            } else {
                alert(data.error || 'Gagal menghapus lokasi.');
            }
        }

        // ===== BULK DELETE LOGIC =====
        function handleRowClick(e, id) {
            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) return;
            const cb = document.getElementById('chk_' + id);
            if (cb) {
                cb.checked = !cb.checked;
                updateBulkDeleteState();
            }
        }

        function toggleSelectAllLocs(master) {
            const checkboxes = document.querySelectorAll('.loc-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = master.checked;
                const tr = cb.closest('tr');
                if (tr) tr.classList.toggle('selected-row', cb.checked);
            });
            updateBulkDeleteState();
        }

        function updateBulkDeleteState() {
            const selected = document.querySelectorAll('.loc-checkbox:checked');
            const btn = document.getElementById('bulkDeleteBtn');
            const count = document.getElementById('bulkDeleteCount');
            const master = document.getElementById('selectAllLocs');

            document.querySelectorAll('.loc-checkbox').forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) tr.classList.toggle('selected-row', cb.checked);
            });

            if (count) count.textContent = selected.length;
            if (btn) btn.style.display = selected.length > 0 ? 'inline-flex' : 'none';

            const totalBoxes = document.querySelectorAll('.loc-checkbox');
            if (master) {
                master.checked = totalBoxes.length > 0 && selected.length === totalBoxes.length;
            }
        }

        async function deleteSelectedLocations() {
            const selected = Array.from(document.querySelectorAll('.loc-checkbox:checked')).map(cb => cb.value);
            if (!selected.length) return alert('Pilih minimal satu lokasi untuk dihapus.');

            if (!confirm(`Hapus ${selected.length} lokasi terpilih? Tindakan ini tidak dapat dibatalkan.`)) return;

            try {
                const formData = new FormData();
                selected.forEach(id => formData.append('ids[]', id));

                const res = await fetch(`${API_URL}?action=delete_location`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const master = document.getElementById('selectAllLocs');
                    if (master) master.checked = false;
                    loadLocations();
                } else {
                    alert(data.error || 'Gagal menghapus lokasi terpilih.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi.');
            }
        }

        function exportExcel() {
            const searchTerm = document.getElementById('locationSearch').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;

            const filtered = allLocations.filter(l => {
                const matchesSearch = !searchTerm ||
                    l.name.toLowerCase().includes(searchTerm) ||
                    l.city.toLowerCase().includes(searchTerm) ||
                    (l.address && l.address.toLowerCase().includes(searchTerm));

                const matchesType = !typeFilter || l.type === typeFilter;
                return matchesSearch && matchesType;
            });

            if (!filtered.length) return alert('Tidak ada data lokasi untuk di-export.');

            const rows = [['No', 'Nama Lokasi', 'Tipe', 'Kota', 'Alamat', 'Latitude', 'Longitude', 'Link Google Maps']];
            
            const typeLabels = {
                store: 'Toko (Store)',
                warehouse: 'Gudang (Warehouse)',
                office: 'Head Office',
                home: 'Rumah (Home)',
                mall: 'Mall',
                kantor: 'Kantor'
            };

            filtered.forEach((l, i) => {
                const typeText = typeLabels[l.type] || l.type || '—';
                const latVal = l.lat !== null && l.lat !== undefined ? parseFloat(l.lat).toFixed(6) : '—';
                const lngVal = l.lng !== null && l.lng !== undefined ? parseFloat(l.lng).toFixed(6) : '—';
                const mapsLink = latVal !== '—' && lngVal !== '—' ? `https://www.google.com/maps?q=${latVal},${lngVal}` : '—';
                const row = [
                    i + 1,
                    l.name || '—',
                    typeText,
                    l.city || '—',
                    l.address || '—',
                    latVal,
                    lngVal,
                    mapsLink
                ];
                rows.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Daftar Lokasi');
            XLSX.writeFile(wb, 'Daftar_Lokasi_TMS.xlsx');
        }

        // ===== MANAGE LOCATION TYPES =====
        function openTypeModal() {
            resetTypeForm();
            renderTypesList();
            const m = document.getElementById('typeModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        function closeTypeModal() {
            const m = document.getElementById('typeModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        function resetTypeForm() {
            document.getElementById('typeForm').reset();
            document.getElementById('typeEditId').value = '';
            document.getElementById('typeKeyInput').disabled = false;
            document.getElementById('btnCancelTypeEdit').style.display = 'none';
            document.getElementById('btnSaveTypeLabel').innerText = 'Simpan Tipe Baru';
        }

        function renderTypesList() {
            const tbody = document.getElementById('typeListTableBody');
            if (!allLocationTypes || allLocationTypes.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding:1.5rem;">Belum ada tipe lokasi.</td></tr>`;
                return;
            }

            tbody.innerHTML = allLocationTypes.map(t => `
                <tr>
                    <td style="padding:8px 12px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span class="material-symbols-outlined" style="font-size:18px; color:${t.color || 'var(--primary)'};">${t.icon || 'location_on'}</span>
                            <strong style="color:var(--text);">${t.type_name}</strong>
                        </div>
                        <span style="font-size:0.72rem; color:var(--text-muted); font-family:monospace; display:block; margin-top:2px;">${t.type_key}</span>
                    </td>
                    <td style="padding:8px 12px; text-align:center;">
                        <span class="type-indicator" style="color:${t.color}; font-weight:700;">
                            <span class="material-symbols-outlined" style="font-size:16px;">${t.icon || 'location_on'}</span> ${t.type_name}
                        </span>
                    </td>
                    <td style="padding:8px 12px; text-align:right;">
                        <div style="display:inline-flex; gap:4px;">
                            <button class="btn-icon" onclick="editTypeClick(${t.id})" title="Edit Tipe"><span class="material-symbols-outlined">edit</span></button>
                            <button class="btn-icon danger" onclick="deleteTypeClick(${t.id})" title="Hapus Tipe"><span class="material-symbols-outlined">delete</span></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function editTypeClick(id) {
            const t = allLocationTypes.find(x => x.id == id);
            if (!t) return;
            document.getElementById('typeEditId').value = t.id;
            document.getElementById('typeKeyInput').value = t.type_key;
            document.getElementById('typeKeyInput').disabled = true;
            document.getElementById('typeNameInput').value = t.type_name;

            const iconSel = document.getElementById('typeIconInput');
            const iconVal = t.icon || 'location_on';
            if (iconSel && !iconSel.querySelector(`option[value="${iconVal}"]`)) {
                iconSel.innerHTML += `<option value="${iconVal}">${iconVal}</option>`;
            }
            if (iconSel) iconSel.value = iconVal;

            document.getElementById('typeColorInput').value = t.color || '#6366f1';
            document.getElementById('btnCancelTypeEdit').style.display = 'inline-flex';
            document.getElementById('btnSaveTypeLabel').innerText = 'Simpan Perubahan Tipe';
        }

        async function deleteTypeClick(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus tipe lokasi ini?')) return;
            try {
                const fd = new FormData();
                fd.append('id', id);
                const res = await fetch(`${API_URL}?action=delete_location_type`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    await loadLocationTypes();
                    renderTypesList();
                    renderLocations();
                } else {
                    alert(data.error || 'Gagal menghapus tipe lokasi');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan');
            }
        }

        async function saveLocationType(e) {
            e.preventDefault();
            const id = document.getElementById('typeEditId').value;
            const key = document.getElementById('typeKeyInput').value.trim();
            const name = document.getElementById('typeNameInput').value.trim();
            const icon = document.getElementById('typeIconInput').value.trim();
            const color = document.getElementById('typeColorInput').value;

            const fd = new FormData();
            fd.append('type_name', name);
            fd.append('icon', icon);
            fd.append('color', color);

            let action = 'add_location_type';
            if (id) {
                action = 'edit_location_type';
                fd.append('id', id);
            } else {
                fd.append('type_key', key);
            }

            try {
                const res = await fetch(`${API_URL}?action=${action}`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    resetTypeForm();
                    await loadLocationTypes();
                    renderTypesList();
                    renderLocations();
                } else {
                    alert(data.error || 'Gagal menyimpan tipe lokasi');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan');
            }
        }

        initLocations();
    </script>
</body>

</html>