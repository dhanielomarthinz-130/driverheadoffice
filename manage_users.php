<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('users');
$can_write = canWriteMenu('users');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
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

        /* Password strength */
        .pw-strength-bar {
            height: 4px;
            border-radius: 99px;
            background: var(--border);
            margin-top: 0.4rem;
            overflow: hidden;
        }

        .pw-strength-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }

        .pw-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .input-eye-wrap {
            position: relative;
        }

        .input-eye-wrap input {
            padding-right: 2.5rem;
        }

        .btn-eye {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            padding: 0;
        }

        .btn-eye:hover {
            color: var(--text);
        }

        .btn-eye .material-symbols-outlined {
            font-size: 18px;
        }

        /* Role badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Role badges are now dynamic via inline styles */

        .badge-self {
            display: inline-flex;
            align-items: center;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 99px;
            background: var(--primary);
            color: white;
            margin-left: 0.4rem;
            vertical-align: middle;
            letter-spacing: 0.04em;
        }

        /* Bulk Actions Bar */
        .bulk-actions-bar {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
        }

        .bulk-actions-bar.show {
            transform: translateX(-50%) translateY(0);
        }

        .bulk-info {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            border-right: 1px solid var(--border);
            padding-right: 1.5rem;
        }

        .table-filters {
            display: flex !important;
            flex-direction: row !important;
            align-items: center;
            gap: 1rem;
            flex-wrap: nowrap;
        }

        .search-wrapper {
            position: relative;
            width: 280px;
            display: flex;
            align-items: center;
        }

        .search-wrapper input {
            padding: 0.6rem 1rem 0.6rem 2.75rem !important;
            height: 42px !important;
            font-size: 0.875rem;
            margin: 0 !important;
            border-radius: var(--radius-md) !important;
            border: 1.5px solid var(--border) !important;
            background: var(--surface-2) !important;
            transition: all 0.2s !important;
            box-shadow: var(--shadow-sm);
        }

        .search-wrapper input:focus {
            border-color: var(--primary) !important;
            background: white !important;
            box-shadow: 0 0 0 3px var(--primary-glow), var(--shadow-md) !important;
        }

        .search-wrapper .material-symbols-outlined {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 20px;
            pointer-events: none;
            z-index: 2;
            opacity: 0.7;
        }

        .select-filter {
            height: 42px !important;
            font-size: 0.875rem !important;
            min-width: 180px;
            padding: 0 1rem !important;
            border-radius: var(--radius-md) !important;
            border: 1.5px solid var(--border) !important;
            background: var(--surface-2) !important;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all 0.2s !important;
        }

        .select-filter:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow) !important;
        }

        /* Checkbox Styling */
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
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
                    <span class="material-symbols-outlined">manage_accounts</span>
                </div>
                <div>
                    <h1>Kelola Pengguna</h1>
                    <p>Tambah akun, reset password, atau hapus pengguna dari sistem</p>
                </div>
            </div>
            <div class="page-actions" style="display:flex; gap:0.5rem; align-items:center;">
                <?php if ($can_write): ?>
                    <button class="btn btn-primary" onclick="openAddModal()" style="height:40px; width:40px; border:none; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding:0; border-radius:8px;" title="Buat Akun Baru">
                        <span class="material-symbols-outlined" style="font-size:20px;">person_add</span>
                    </button>
                <?php endif; ?>
                <button onclick="exportExcel()" style="height:40px; width:40px; background:#107c41; color:white; border:none; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.2s;" title="Download Excel" onmouseover="this.style.background='#0b592e'" onmouseout="this.style.background='#107c41'">
                    <span class="material-symbols-outlined" style="font-size:20px; color:white;">table_view</span>
                </button>
            </div>
        </div>

        <!-- User Table (full width) -->
        <div class="card">
            <div class="card-header"
                style="display:flex; justify-content:space-between; align-items:center; gap:2rem; flex-wrap:nowrap; padding-bottom:1.5rem; border-bottom:1px solid var(--border); margin-bottom:1.5rem;">
                <span class="card-title" style="margin-bottom:0; flex-shrink:0; font-size:1.1rem;">
                    <span class="material-symbols-outlined" style="font-size:24px;">group</span>
                    Daftar Pengguna Sistem
                </span>

                <div class="table-filters">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="userSearch" placeholder="Cari nama, username..." oninput="renderUsers()">
                    </div>
                    <select id="roleFilter" class="select-filter" onchange="renderUsers()" style="margin:0;">
                        <option value="">Semua Role (Foe)</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">
                                <input type="checkbox" id="selectAll" class="custom-checkbox"
                                    onclick="toggleSelectAll(this)">
                            </th>
                            <th>Pengguna</th>
                            <th>Username</th>
                            <th>No. HP (WA)</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Masa Aktif</th>
                            <th>Terdaftar</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <tr>
                            <td colspan="9">
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

    <!-- Bulk Actions Bar -->
    <div id="bulkBar" class="bulk-actions-bar">
        <div class="bulk-info">
            <span id="selectedCount">0</span> pengguna terpilih
        </div>
        <div style="display:flex; gap:0.75rem;">
            <button class="btn btn-primary" onclick="bulkExtend()">
                <span class="material-symbols-outlined">calendar_add_on</span>
                Perpanjang Masa Aktif
            </button>
            <button class="btn btn-danger" onclick="bulkDelete()">
                <span class="material-symbols-outlined">delete</span>
                Hapus Terpilih
            </button>
            <button class="btn btn-ghost" onclick="clearSelection()" style="min-width:auto;">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    <!-- ===== ADD USER MODAL ===== -->
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)closeAdd()" style="display: none;">
        <div class="modal-box" style="max-width:460px;">
            <button class="modal-close" onclick="closeAdd()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">person_add</span>
                Buat Akun Baru
            </div>
            <p class="modal-subtitle">Tambah pengguna ke dalam sistem</p>

            <form id="userForm">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="form-group">
                    <label>Username (Login ID)</label>
                    <input type="text" name="username" required placeholder="Tanpa spasi, huruf kecil">
                </div>
                <div class="form-group">
                    <label>Password Akun</label>
                    <div class="input-eye-wrap">
                        <input type="password" name="password" id="addPassword" required
                            placeholder="Minimal 6 karakter"
                            oninput="checkStrength('addPassword', 'addStrengthFill', 'addPwHint')">
                        <button type="button" class="btn-eye" onclick="toggleEye('addPassword', this)">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                    <div class="pw-strength-bar">
                        <div class="pw-strength-fill" id="addStrengthFill"></div>
                    </div>
                    <div class="pw-hint" id="addPwHint">Masukkan password</div>
                </div>
                <div class="form-group">
                    <label>Pilih Role</label>
                    <select id="addRole" name="role" required>
                        <option value="">-- Pilih Role --</option>
                    </select>
                </div>

                <div class="form-group" id="addPhoneGroup">
                    <label>Nomor HP / WhatsApp</label>
                    <input type="text" name="phone_number" placeholder="Contoh: 628123456xxx">
                    <small style="color:var(--text-muted); font-size:0.7rem;">Opsional. Gunakan kode negara (62 untuk
                        Indonesia)</small>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                    <button type="button" onclick="closeAdd()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Pengguna
                    </button>
                </div>
                <div id="formMsg" style="margin-top:0.875rem; text-align:center;"></div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT USER MODAL ===== -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)closeEdit()" style="display: none;">
        <div class="modal-box" style="max-width:440px;">
            <button class="modal-close" onclick="closeEdit()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">edit</span>
                Ubah Data Pengguna
            </div>
            <p class="modal-subtitle">Update nama atau nomor telepon</p>

            <form id="editForm">
                <input type="hidden" name="id" id="editUserId">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Pilih Role</label>
                    <select id="editRole" name="role" required>
                        <option value="">-- Pilih Role --</option>
                    </select>
                </div>
                <div class="form-group" id="editPhoneGroup">
                    <label>Nomor HP / WhatsApp</label>
                    <input type="text" name="phone_number" id="editPhone" placeholder="Contoh: 628123456xxx">
                    <small style="color:var(--text-muted); font-size:0.7rem;">Gunakan format 62xxx untuk link
                        WhatsApp.</small>
                </div>
                <?php if ($_SESSION['role'] === 'controller' || $_SESSION['role'] === 'management'): ?>
                    <div class="form-group">
                        <label>Masa Aktif Akun (Expiry)</label>
                        <input type="date" name="expires_at" id="editExpires">
                        <small style="color:var(--text-muted); font-size:0.7rem;">Kosongkan untuk Lifetime (Hanya Super
                            Admin).</small>
                    </div>
                <?php endif; ?>

                <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                    <button type="button" onclick="closeEdit()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Perubahan
                    </button>
                </div>
                <div id="editFormMsg" style="margin-top:0.875rem; text-align:center;"></div>
            </form>
        </div>
    </div>

    <!-- ===== CHANGE PASSWORD MODAL ===== -->
    <div id="pwModal" class="modal-overlay" onclick="if(event.target===this)closePw()" style="display: none;">
        <div class="modal-box" style="max-width:400px;">
            <button class="modal-close" onclick="closePw()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined">lock_reset</span>
                Ganti Password
            </div>
            <p class="modal-subtitle">Ubah password untuk: <strong id="pwUserName"></strong></p>

            <form id="pwForm">
                <input type="hidden" id="pwUserId" name="id">

                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="input-eye-wrap">
                        <input type="password" name="new_password" id="newPassword" required
                            placeholder="Minimal 6 karakter"
                            oninput="checkStrength('newPassword', 'pwStrengthFill', 'pwHint')">
                        <button type="button" class="btn-eye" onclick="toggleEye('newPassword', this)">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                    <div class="pw-strength-bar">
                        <div class="pw-strength-fill" id="pwStrengthFill"></div>
                    </div>
                    <div class="pw-hint" id="pwHint">Masukkan password baru</div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-eye-wrap">
                        <input type="password" id="confirmPassword" required placeholder="Ulangi password baru"
                            oninput="checkConfirm()">
                        <button type="button" class="btn-eye" onclick="toggleEye('confirmPassword', this)">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                    <div class="pw-hint" id="confirmHint"></div>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                    <button type="button" onclick="closePw()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Simpan Password
                    </button>
                </div>
                <div id="pwFormMsg" style="margin-top:0.75rem; text-align:center; font-size:0.85rem;"></div>
            </form>
        </div>
    </div>

    <!-- ===== BULK EXTEND MODAL ===== -->
    <div id="bulkExtendModal" class="modal-overlay" onclick="if(event.target===this)closeBulkExtend()"
        style="display: none;">
        <div class="modal-box" style="max-width:420px;">
            <button class="modal-close" onclick="closeBulkExtend()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title">
                <span class="material-symbols-outlined" style="color:var(--primary);">calendar_add_on</span>
                Perpanjang Masa Aktif
            </div>
            <p class="modal-subtitle">Pilih durasi perpanjangan untuk <strong id="bulkExtendCount">0</strong> pengguna
                terpilih.</p>

            <form id="bulkExtendForm">
                <div class="form-group">
                    <label>Pilih Durasi (Kelipatan 3 Bulan)</label>
                    <select id="extendMonths" name="months" required onchange="toggleCustomDays()">
                        <option value="90">3 Bulan (90 Hari)</option>
                        <option value="180">6 Bulan (180 Hari)</option>
                        <option value="270">9 Bulan (270 Hari)</option>
                        <option value="360">12 Bulan (360 Hari)</option>
                        <option value="30">1 Bulan (30 Hari)</option>
                        <option value="custom">Kustom (Input Hari)</option>
                    </select>
                </div>

                <div id="customDaysGroup" class="form-group" style="display:none;">
                    <label>Jumlah Hari</label>
                    <input type="number" id="customDaysInput" placeholder="Masukkan jumlah hari..." min="1">
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                    <button type="button" onclick="closeBulkExtend()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" id="submitBulkExtendBtn" class="btn btn-primary"
                        style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">check_circle</span>
                        Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const API_URL = 'api.php';
        const CAN_WRITE = <?php echo $can_write ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo (int) $_SESSION['user_id']; ?>;
        const currentRole = '<?php echo $_SESSION['role']; ?>';

        // Phone field is now always visible

        // ===== OPEN / CLOSE ADD MODAL =====
        function openAddModal() {
            document.getElementById('userForm').reset();
            document.getElementById('formMsg').innerHTML = '';
            document.getElementById('addStrengthFill').style.width = '0%';
            document.getElementById('addPwHint').textContent = 'Masukkan password';
            const m = document.getElementById('addModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }
        function closeAdd() {
            const m = document.getElementById('addModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        // ===== OPEN / CLOSE EDIT MODAL =====
        function openEditModal(id, name, phone, role, expires) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editPhone').value = phone || '';
            document.getElementById('editRole').value = role;
            if (document.getElementById('editExpires')) {
                document.getElementById('editExpires').value = expires || '';
            }
            document.getElementById('editFormMsg').innerHTML = '';

            const m = document.getElementById('editModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }
        function closeEdit() {
            const m = document.getElementById('editModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        // ===== OPEN / CLOSE PW MODAL =====
        function openPwModal(id, name) {
            document.getElementById('pwUserId').value = id;
            document.getElementById('pwUserName').textContent = name;
            document.getElementById('pwForm').reset();
            document.getElementById('pwFormMsg').innerHTML = '';
            document.getElementById('pwStrengthFill').style.width = '0%';
            document.getElementById('pwHint').textContent = 'Masukkan password baru';
            document.getElementById('confirmHint').textContent = '';

            const m = document.getElementById('pwModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }
        function closePw() {
            const m = document.getElementById('pwModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }

        // ===== BULK EXTEND MODAL HELPERS =====
        function closeBulkExtend() {
            const m = document.getElementById('bulkExtendModal');
            m.classList.remove('show');
            setTimeout(() => m.style.display = 'none', 300);
        }
        function toggleCustomDays() {
            const select = document.getElementById('extendMonths');
            const group = document.getElementById('customDaysGroup');
            group.style.display = select.value === 'custom' ? 'block' : 'none';
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: 'check_circle',
                error: 'error',
                warning: 'warning'
            };

            toast.innerHTML = `
                <span class="material-symbols-outlined toast-icon">${icons[type] || 'info'}</span>
                <div class="toast-content">${message}</div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showConfirmToast(message, onOk, btnText = 'Ya, Lanjutkan') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast warning`;
            toast.style.minWidth = '320px';
            toast.style.flexDirection = 'column';
            toast.style.alignItems = 'flex-start';
            toast.style.padding = '1rem';

            toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px; width:100%;">
                    <span class="material-symbols-outlined toast-icon" style="color:var(--warning);">help</span>
                    <div class="toast-content" style="flex:1; font-weight:700;">${message}</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:12px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="confirmCancel" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px;">Batal</button>
                    <button class="btn btn-primary" id="confirmOk" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px; background:var(--primary); border:none; box-shadow:none;">${btnText}</button>
                </div>
            `;

            container.appendChild(toast);

            toast.querySelector('#confirmOk').onclick = () => {
                onOk();
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
            toast.querySelector('#confirmCancel').onclick = () => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
        }

        // ===== PASSWORD HELPERS =====
        function toggleEye(inputId, btn) {
            const inp = document.getElementById(inputId);
            const icon = btn.querySelector('.material-symbols-outlined');
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                inp.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        function checkStrength(inputId, fillId, hintId) {
            const val = document.getElementById(inputId).value;
            const fill = document.getElementById(fillId);
            const hint = document.getElementById(hintId);
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { w: '0%', color: '#e5e7eb', text: 'Masukkan password' },
                { w: '25%', color: '#ef4444', text: 'Lemah' },
                { w: '50%', color: '#f97316', text: 'Cukup' },
                { w: '75%', color: '#eab308', text: 'Baik' },
                { w: '90%', color: '#22c55e', text: 'Kuat' },
                { w: '100%', color: '#16a34a', text: 'Sangat Kuat' },
            ];
            const lvl = val.length === 0 ? 0 : Math.min(score + 1, 5);
            fill.style.width = levels[lvl].w;
            fill.style.background = levels[lvl].color;
            hint.textContent = levels[lvl].text;
            hint.style.color = lvl === 0 ? 'var(--text-muted)' : levels[lvl].color;
        }

        function checkConfirm() {
            const pw = document.getElementById('newPassword').value;
            const cf = document.getElementById('confirmPassword').value;
            const hint = document.getElementById('confirmHint');
            if (!cf) { hint.textContent = ''; return; }
            if (pw === cf) {
                hint.textContent = '✓ Password cocok';
                hint.style.color = '#16a34a';
            } else {
                hint.textContent = '✗ Password tidak cocok';
                hint.style.color = '#ef4444';
            }
        }

        // ===== LOAD USERS TABLE (SMART SEARCH) =====
        let allUsers = [];
        let roleMap = {};

        async function initUsers() {
            try {
                // Fetch Roles first for mapping
                const rolesRes = await fetch(`${API_URL}?action=get_roles`);
                const rolesData = await rolesRes.json();
                roleMap = {};
                rolesData.forEach(r => roleMap[r.role_key] = r);

                // Populate Dropdowns
                const roleSelects = [document.getElementById('addRole'), document.getElementById('editRole'), document.getElementById('roleFilter')];
                roleSelects.forEach(sel => {
                    if (!sel) return;
                    const currentVal = sel.value;
                    const isFilter = sel.id === 'roleFilter';
                    sel.innerHTML = isFilter ? '<option value="">Semua Role (Foe)</option>' : '<option value="">-- Pilih Role --</option>';
                    rolesData.forEach(r => {
                        if (r.role_key === 'controller' && currentRole !== 'controller' && !isFilter) return;
                        const opt = document.createElement('option');
                        opt.value = r.role_key;
                        opt.innerText = r.role_name;
                        sel.appendChild(opt);
                    });
                    sel.value = currentVal;
                });

                // Initial fetch from API (fetch all once for smart filtering)
                const res = await fetch(`${API_URL}?action=get_all_users`);
                allUsers = await res.json();

                renderUsers();
            } catch (err) {
                console.error('Init users error:', err);
            }
        }

        function renderUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const tbody = document.getElementById('userTableBody');

            const filtered = allUsers.filter(u => {
                const name = (u.name || '').toLowerCase();
                const username = (u.username || '').toLowerCase();
                const phone = u.phone_number || '';

                const matchesSearch = !searchTerm ||
                    name.includes(searchTerm) ||
                    username.includes(searchTerm) ||
                    phone.includes(searchTerm);

                const matchesRole = !roleFilter || u.role === roleFilter;
                return matchesSearch && matchesRole;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:3rem; color:var(--text-muted);">Tidak ada pengguna ditemukan.</td></tr>`;
                updateBulkBar();
                return;
            }

            tbody.innerHTML = filtered.map(u => {
                const rl = roleMap[u.role] || { role_name: u.role || 'NO ROLE', color: '#cbd5e1', icon: 'person' };
                const badgeStyle = `background: ${rl.color}15; color: ${rl.color}; border: 1.5px solid ${rl.color}40;`;

                const isSelf = parseInt(u.id) === currentUserId;
                const isDaniel = (u.username && u.username.toLowerCase() === 'daniel');
                const selfBadge = isSelf 
                    ? `<span class="badge-self">ANDA</span>` 
                    : (isDaniel ? `<span class="badge-self" style="background:#0f172a; color:#f8fafc; border-color:#334155;">SUPER ADMIN UTAMA</span>` : '');

                // Smart Highlighting
                const highlight = (text) => {
                    if (!text) return '-';
                    if (!searchTerm) return text;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    return String(text).replace(regex, '<mark style="background:#fde68a; color:#92400e; padding:0 2px; border-radius:2px;">$1</mark>');
                };

                const phoneVal = u.phone_number || '-';
                const waLink = u.phone_number
                    ? `<a href="https://wa.me/${u.phone_number}" target="_blank" class="btn-icon" style="color:#25D366;" title="Kirim WhatsApp">
                           <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" style="width:18px; height:18px;">
                       </a>`
                    : '';

                const deleteBtn = (isSelf || isDaniel)
                    ? `<button class="btn-icon" disabled style="opacity:0.3; cursor:not-allowed;" title="${isDaniel ? 'Akun Super Admin Daniel tidak dapat dihapus' : 'Tidak bisa hapus diri sendiri'}">
                           <span class="material-symbols-outlined">delete</span>
                       </button>`
                    : `<button class="btn-icon danger" onclick="deleteUser(${u.id})" title="Hapus Akun">
                           <span class="material-symbols-outlined">delete</span>
                       </button>`;

                const isExpired = u.expires_at && new Date(u.expires_at) < new Date();
                const expiryText = (isDaniel || !u.expires_at)
                    ? '<span style="color:var(--success); font-weight:700;">LIFETIME</span>'
                    : `<span style="color:${isExpired ? 'var(--danger)' : 'var(--text)'}; font-weight:${isExpired ? '700' : '400'};">
                        ${u.expires_at} ${isExpired ? '<br><small style="color:var(--danger);">EXPIRED</small>' : ''}
                       </span>`;

                const isActive = parseInt(u.is_active) === 1;
                const statusBadge = (CAN_WRITE && !isDaniel) ? `
                    <button onclick="toggleUserStatus(${u.id}, ${isActive ? 0 : 1})" 
                            class="btn-icon" 
                            style="width:auto; padding:2px 8px; border-radius:6px; background:${isActive ? '#dcfce7' : '#f1f5f9'}; color:${isActive ? '#16a34a' : '#94a3b8'}; border:1px solid ${isActive ? '#bbf7d0' : '#e2e8f0'}; cursor:pointer;"
                            title="${isActive ? 'Klik untuk Nonaktifkan' : 'Klik untuk Aktifkan'}">
                        <span style="font-size:0.7rem; font-weight:800; display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-outlined" style="font-size:14px;">${isActive ? 'check_circle' : 'cancel'}</span>
                            ${isActive ? 'ACTIVE' : 'INACTIVE'}
                        </span>
                    </button>` : `
                    <span style="padding:2px 8px; border-radius:6px; background:${isActive ? '#dcfce7' : '#f1f5f9'}; color:${isActive ? '#16a34a' : '#94a3b8'}; border:1px solid ${isActive ? '#bbf7d0' : '#e2e8f0'}; font-size:0.7rem; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-symbols-outlined" style="font-size:14px;">${isActive ? 'check_circle' : 'cancel'}</span>
                        ${isActive ? 'ACTIVE' : 'INACTIVE'}
                    </span>`;

                const checkbox = (isSelf || isDaniel) ? '' : `<input type="checkbox" class="custom-checkbox row-checkbox" value="${u.id}" onclick="updateBulkBar()">`;

                const canEditThisUser = CAN_WRITE && (!isDaniel || isSelf);
                const editBtn = canEditThisUser
                    ? `<button class="btn-icon" onclick="openEditModal(${u.id}, '${u.name.replace(/'/g, "\\'")}', '${u.phone_number || ''}', '${u.role}', '${u.expires_at || ''}')" title="Edit Data"><span class="material-symbols-outlined">edit</span></button>`
                    : `<button class="btn-icon" disabled style="opacity:0.3; cursor:not-allowed;" title="Akun Daniel hanya dapat diubah oleh Daniel"><span class="material-symbols-outlined">edit</span></button>`;

                const pwBtn = canEditThisUser
                    ? `<button class="btn-icon" onclick="openPwModal(${u.id}, '${u.name.replace(/'/g, "\\'")}')" title="Ganti Password"><span class="material-symbols-outlined">lock_reset</span></button>`
                    : `<button class="btn-icon" disabled style="opacity:0.3; cursor:not-allowed;" title="Password Daniel hanya dapat diubah oleh Daniel"><span class="material-symbols-outlined">lock_reset</span></button>`;

                return `
                    <tr style="${isExpired || !isActive ? 'background:rgba(239, 68, 68, 0.03);' : ''}">
                        <td style="text-align:center;">${checkbox}</td>
                        <td><strong>${highlight(u.name)}</strong>${selfBadge}</td>
                        <td style="color:var(--text-muted); font-size:0.85rem;">@${highlight(u.username)}</td>
                        <td style="font-size:0.85rem; font-weight:600;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                ${highlight(phoneVal)} ${waLink}
                            </div>
                        </td>
                        <td>
                            <span class="role-badge" style="${badgeStyle}">
                                <span class="material-symbols-outlined" style="font-size:16px;">${rl.icon}</span>
                                ${rl.role_name}
                            </span>
                        </td>
                        <td>${statusBadge}</td>
                        <td style="font-size:0.85rem;">${expiryText}</td>
                        <td style="font-size:0.8rem; color:var(--text-muted);">${u.created_at ? u.created_at.split(' ')[0] : '-'}</td>
                        <td style="text-align:right;">
                            ${CAN_WRITE ? `
                            <div style="display:inline-flex; gap:0.375rem;">
                                ${editBtn}
                                ${pwBtn}
                                ${deleteBtn}
                            </div>
                            ` : ''}
                        </td>
                    </tr>`;
            }).join('');

            updateBulkBar();
        }

        async function loadUsers() {
            // Re-fetch all and render (used after actions)
            await initUsers();
        }

        // ===== BULK ACTIONS LOGIC =====
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateBulkBar();
        }

        function updateBulkBar() {
            const selected = document.querySelectorAll('.row-checkbox:checked');
            const bar = document.getElementById('bulkBar');
            const count = document.getElementById('selectedCount');

            if (selected.length > 0) {
                count.textContent = selected.length;
                bar.classList.add('show');
            } else {
                bar.classList.remove('show');
                document.getElementById('selectAll').checked = false;
            }
        }

        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkBar();
        }

        async function bulkExtend() {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;

            document.getElementById('bulkExtendCount').textContent = selected.length;
            document.getElementById('extendMonths').value = '90';
            toggleCustomDays();

            const m = document.getElementById('bulkExtendModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('show'), 10);
        }

        document.getElementById('bulkExtendForm').onsubmit = async (e) => {
            e.preventDefault();
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            const select = document.getElementById('extendMonths');
            let days = select.value;

            if (days === 'custom') {
                days = document.getElementById('customDaysInput').value;
            }

            if (!days || days <= 0) {
                showToast('Masukkan jumlah hari yang valid!', 'error');
                return;
            }

            const btn = document.getElementById('submitBulkExtendBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined rotating">autorenew</span> Memproses...';

            const formData = new FormData();
            selected.forEach(id => formData.append('ids[]', id));
            formData.append('days', days);

            try {
                const res = await fetch(`${API_URL}?action=bulk_extend_users`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Berhasil memperpanjang ${selected.length} akun.`);
                    closeBulkExtend();
                    loadUsers();
                    clearSelection();
                } else {
                    showToast('Gagal: ' + data.error, 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Konfirmasi';
            }
        };

        async function bulkDelete() {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;

            showConfirmToast(`HAPUS PERMANEN ${selected.length} pengguna terpilih?`, async () => {
                const formData = new FormData();
                selected.forEach(id => formData.append('ids[]', id));

                try {
                    const res = await fetch(`${API_URL}?action=bulk_delete_users`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Berhasil menghapus pengguna.');
                        loadUsers();
                        clearSelection();
                    } else {
                        showToast('Gagal: ' + data.error, 'error');
                    }
                } catch (err) {
                    showToast('Error koneksi.', 'error');
                }
            });
        }

        // ===== SUBMIT ADD USER =====
        document.getElementById('userForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('formMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Memproses...</span>';
            try {
                const res = await fetch(`${API_URL}?action=register`, { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast('Pengguna berhasil dibuat!');
                    loadUsers();
                    closeAdd();
                } else {
                    showToast(`Gagal: ${data.error}`, 'error');
                }
            } catch (err) { showToast('Error koneksi.', 'error'); }
        };

        // ===== SUBMIT EDIT USER =====
        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('editFormMsg');
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';
            try {
                const res = await fetch(`${API_URL}?action=update_user`, { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast('Data berhasil diperbarui!');
                    loadUsers();
                    closeEdit();
                } else {
                    showToast(`Gagal: ${data.error}`, 'error');
                }
            } catch (err) { showToast('Error koneksi.', 'error'); }
        };

        // ===== SUBMIT CHANGE PASSWORD =====
        document.getElementById('pwForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('pwFormMsg');
            const pw = document.getElementById('newPassword').value;
            if (pw !== document.getElementById('confirmPassword').value) {
                msg.innerHTML = '<span style="color:var(--danger);">Konfirmasi password tidak cocok.</span>';
                return;
            }
            msg.innerHTML = '<span style="color:var(--text-muted);">Menyimpan...</span>';
            const res = await fetch(`${API_URL}?action=change_password`, { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if (data.success) {
                showToast('Password berhasil diubah!');
                closePw();
            } else {
                showToast(`Gagal: ${data.error}`, 'error');
            }
        };

        // ===== TOGGLE USER STATUS =====
        async function toggleUserStatus(id, newStatus) {
            const action = newStatus ? 'aktifkan' : 'nonaktifkan';

            showConfirmToast(`Apakah Anda yakin ingin ${action} akun ini?`, async () => {
                try {
                    const res = await fetch(`${API_URL}?action=toggle_user_status`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}&status=${newStatus}`
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast(`Akun berhasil di-${action}.`);
                        loadUsers();
                    } else {
                        showToast('Gagal: ' + data.error, 'error');
                    }
                } catch (err) {
                    showToast('Error koneksi.', 'error');
                }
            }, newStatus ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan');
        }

        // ===== DELETE USER =====
        async function deleteUser(id) {
            showConfirmToast('Hapus akun ini secara permanen?', async () => {
                const res = await fetch(`${API_URL}?action=delete_user`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Akun berhasil dihapus.');
                    loadUsers();
                } else {
                    showToast('Gagal menghapus: ' + data.error, 'error');
                }
            }, 'Ya, Hapus');
        }

        function exportExcel() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;

            const filtered = allUsers.filter(u => {
                const name = (u.name || '').toLowerCase();
                const username = (u.username || '').toLowerCase();
                const phone = u.phone_number || '';

                const matchesSearch = !searchTerm ||
                    name.includes(searchTerm) ||
                    username.includes(searchTerm) ||
                    phone.includes(searchTerm);

                const matchesRole = !roleFilter || u.role === roleFilter;
                return matchesSearch && matchesRole;
            });

            if (!filtered.length) return alert('Tidak ada data pengguna untuk di-export.');

            const rows = [['No', 'Nama Lengkap', 'Username', 'No. Telepon', 'Role', 'Status', 'Masa Berlaku', 'Tanggal Dibuat']];
            
            filtered.forEach((u, i) => {
                const rlName = roleMap[u.role]?.role_name || u.role || '—';
                const statusText = parseInt(u.is_active) === 1 ? 'Aktif' : 'Nonaktif';
                const expiryText = u.expires_at || 'LIFETIME';
                const row = [
                    i + 1,
                    u.name || '—',
                    u.username ? '@' + u.username : '—',
                    u.phone_number || '—',
                    rlName,
                    statusText,
                    expiryText,
                    u.created_at || '—'
                ];
                rows.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Daftar Pengguna');
            XLSX.writeFile(wb, 'Daftar_Pengguna_TMS.xlsx');
        }

        initUsers();
    </script>
</body>

</html>