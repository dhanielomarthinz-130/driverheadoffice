<?php
require_once 'auth_check.php';
checkLogin();

// Security check using helper function
if (function_exists('canManageSystemLicense') && !canManageSystemLicense()) {
    header("Location: dashboard_summary");
    exit();
}

$current_page = 'system_license.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisensi System & Masa Aktif Web | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Fonts & Icons & Main CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-heading: #0f172a;
            --text-sub: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: #334155;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--text-sub);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.75rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            position: relative;
            overflow: hidden;
        }

        .card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-expired {
            background: #fee2e2;
            color: #b91c1c;
        }

        .expiry-date-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 0.5rem;
        }

        .remaining-days {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Quick Add Buttons */
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn-extend {
            background: #f1f5f9;
            color: #1e293b;
            border: 1.5px solid var(--border);
            padding: 0.85rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-extend:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            outline: none;
            transition: border 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .btn-submit-main {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #4338ca);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            transition: all 0.2s ease;
        }

        .btn-submit-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px -4px rgba(79, 70, 229, 0.5);
        }

        /* History Table Styling */
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .logs-table th, .logs-table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .logs-table th {
            background: #f8fafc;
            color: var(--text-sub);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .logs-table tr:hover {
            background: #f1f5f9;
        }

        .btn-print {
            padding: 4px 10px;
            background: #e0e7ff;
            color: #3730a3;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #c7d2fe;
        }
    </style>
</head>

<body>
    <div id="toastContainer"></div>

    <div class="main-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <div class="page-title-icon">
                        <span class="material-symbols-outlined" style="font-size: 24px; color: var(--primary);">verified</span>
                    </div>
                    <div>
                        <h1>Lisensi System & Masa Aktif Pemakaian Web</h1>
                        <p>Pengaturan Pengelola Lisensi - Mengelola durasi aktif aplikasi & perpanjangan masa sewa/pemakaian.</p>
                    </div>
                </div>
            </div>

            <div class="grid-cards">
                <!-- CARD STATUS LISENSI -->
                <div class="card">
                    <div class="card-title">
                        <span>Status Pemakaian Web</span>
                        <div id="statusBadge" class="status-badge status-active">
                            <span class="material-symbols-outlined" style="font-size: 16px;">sync</span>
                            <span>Memuat...</span>
                        </div>
                    </div>
                    <div class="expiry-date-text" id="displayActiveUntil">-</div>
                    <div class="remaining-days" id="displayRemainingDays">Memuat durasi...</div>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <div style="font-size: 0.78rem; color: var(--text-sub); font-weight: 600; margin-bottom: 4px;">CATATAN PEMBAYARAN TERAKHIR:</div>
                        <div id="displayLicenseNotes" style="font-size: 0.88rem; font-weight: 600; color: var(--text-heading); font-style: italic;">
                            -
                        </div>
                    </div>
                </div>

                <!-- CARD PERPANJANG INSTAN -->
                <div class="card">
                    <div class="card-title">
                        <span>Perpanjang Instan (Setelah Pembayaran)</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">add_task</span>
                    </div>

                    <p style="font-size: 0.82rem; color: var(--text-sub); margin-bottom: 1rem;">
                        Klik salah satu opsi di bawah ini untuk langsung menambahkan durasi bulan pemakaian:
                    </p>

                    <div class="btn-grid">
                        <button type="button" class="btn-extend" onclick="quickAddMonths(1)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +1 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(3)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +3 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(6)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +6 Bulan
                        </button>
                        <button type="button" class="btn-extend" onclick="quickAddMonths(12)">
                            <span class="material-symbols-outlined" style="color: var(--primary);">event_available</span>
                            +1 Tahun
                        </button>
                    </div>
                </div>
            </div>

            <!-- CARD CUSTOM DATE & OTORISASI -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-title">
                        <span>Pengaturan Tanggal Lisensi Kustom</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">edit_calendar</span>
                    </div>

                    <form id="licenseForm">
                        <div class="form-group">
                            <label for="customDate">Atur Tanggal Berakhir Pemakaian Kustom</label>
                            <input type="date" id="customDate" name="custom_date" class="form-control">
                            <small style="color: var(--text-sub); font-size: 0.75rem; margin-top: 4px; display: block;">
                                Isi jika ingin menentukan tanggal pasti aktifnya web aplikasi.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="notesInput">Catatan Pembayaran / Referensi Transfer</label>
                            <input type="text" id="notesInput" name="notes" class="form-control" placeholder="Contoh: Lunas 3 Bulan via TF BCA (Inv #1024)">
                        </div>

                        <button type="submit" id="btnSubmitForm" class="btn-submit-main">
                            <span class="material-symbols-outlined">save</span>
                            Simpan Perubahan Lisensi
                        </button>
                    </form>
                </div>

                <!-- CARD OTORISASI USER PENGELOLA LISENSI -->
                <div class="card">
                    <div class="card-title">
                        <span>Otorisasi Pengelola Lisensi</span>
                        <span class="material-symbols-outlined" style="color: var(--primary);">admin_panel_settings</span>
                    </div>

                    <p style="font-size: 0.82rem; color: var(--text-sub); margin-bottom: 1rem;">
                        Pilih user tambahan (Admin / Controller) yang diberi hak untuk mengelola lisensi & mengakses menu ini:
                    </p>

                    <form id="managersForm">
                        <div id="managersList" style="max-height: 220px; overflow-y: auto; border: 1.5px solid var(--border); border-radius: 12px; padding: 10px; margin-bottom: 1.25rem;">
                            <div style="font-size:0.82rem; color:var(--text-sub); text-align:center; padding:10px;">Memuat daftar pengguna...</div>
                        </div>

                        <button type="submit" id="btnSubmitManagers" class="btn-submit-main" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <span class="material-symbols-outlined">how_to_reg</span>
                            Simpan Hak Akses Pengelola
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD RIWAYAT PERPANJANGAN & BUKTI -->
            <div class="card">
                <div class="card-title">
                    <span>Riwayat Perpanjangan & Bukti Pembayaran</span>
                    <span class="material-symbols-outlined" style="color: var(--primary);">history</span>
                </div>



                <div style="overflow-x: auto;">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>No / Waktu Transaksi</th>
                                <th>Pengelola (Oleh)</th>
                                <th>Jenis Perpanjangan</th>
                                <th>Masa Aktif Baru</th>
                                <th>Catatan Pembayaran</th>
                                <th>Aksi (Bukti PDF)</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--text-sub); padding:16px;">Memuat riwayat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cachedLogs = [];

        // ===== TOAST NOTIFICATION HELPERS =====
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
            }, 3500);
        }

        function showPromptToast(message, defaultVal, placeholder, onOk, btnText = 'Perpanjang Masa Aktif') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const existing = container.querySelector('.toast-prompt');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'toast warning toast-prompt';
            toast.style.minWidth = '340px';
            toast.style.maxWidth = '420px';
            toast.style.flexDirection = 'column';
            toast.style.alignItems = 'flex-start';
            toast.style.padding = '1.15rem';
            toast.style.boxShadow = '0 12px 30px -5px rgba(0,0,0,0.2)';

            toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:10px; width:100%; margin-bottom:8px;">
                    <span class="material-symbols-outlined toast-icon" style="color:var(--primary); font-size:24px;">verified</span>
                    <div class="toast-content" style="flex:1; font-weight:700; font-size:0.9rem; color:var(--text-heading);">${message}</div>
                </div>
                <div style="width:100%; margin-bottom:12px;">
                    <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); display:block; margin-bottom:4px;">Catatan Pembayaran (Opsional):</label>
                    <input type="text" id="toastPromptInput" class="form-control" value="${defaultVal}" placeholder="${placeholder}" style="width:100%; height:38px; font-size:0.85rem; padding:0 12px; border-radius:8px; border:1.5px solid var(--border);">
                </div>
                <div style="display:flex; gap:8px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="promptCancel" style="padding:6px 14px; font-size:0.78rem; height:32px; border-radius:8px; cursor:pointer; background:#f1f5f9; border:none; font-weight:600; color:#475569;">Batal</button>
                    <button class="btn btn-primary" id="promptOk" style="padding:6px 16px; font-size:0.78rem; height:32px; border-radius:8px; background:var(--primary); color:white; border:none; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">${btnText}</button>
                </div>
            `;

            container.appendChild(toast);
            const inputEl = toast.querySelector('#toastPromptInput');
            setTimeout(() => { inputEl.focus(); inputEl.select(); }, 50);

            inputEl.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    toast.querySelector('#promptOk').click();
                }
            });

            toast.querySelector('#promptOk').onclick = () => {
                const val = inputEl.value.trim();
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
                if (onOk) onOk(val);
            };
            toast.querySelector('#promptCancel').onclick = () => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
        }

        // ===== DATA FETCH & ACTIONS =====
        async function fetchLicenseData() {
            try {
                const res = await fetch('./api.php?action=get_system_license');
                const data = await res.json();

                if (data.success) {
                    const activeUntil = data.active_until ? new Date(data.active_until) : null;
                    const dateStr = activeUntil ? activeUntil.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Belum diatur';
                    
                    document.getElementById('displayActiveUntil').textContent = dateStr;
                    document.getElementById('displayLicenseNotes').textContent = data.license_notes || 'Belum ada catatan.';

                    const badge = document.getElementById('statusBadge');
                    const remText = document.getElementById('displayRemainingDays');

                    if (data.is_expired) {
                        badge.className = 'status-badge status-expired';
                        badge.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">error</span> EXPIRED';
                        remText.style.color = 'var(--danger)';
                        remText.textContent = 'Masa aktif telah berakhir (Akses web diblokir untuk user biasa)';
                    } else {
                        badge.className = 'status-badge status-active';
                        badge.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> AKTIF';
                        remText.style.color = 'var(--primary)';
                        remText.textContent = `Sisa masa aktif: ${data.remaining_days} hari lagi`;
                    }

                    if (data.active_until) {
                        const yyyy = activeUntil.getFullYear();
                        const mm = String(activeUntil.getMonth() + 1).padStart(2, '0');
                        const dd = String(activeUntil.getDate()).padStart(2, '0');
                        document.getElementById('customDate').value = `${yyyy}-${mm}-${dd}`;
                    }
                } else {
                    showToast(data.error || 'Gagal memuat status lisensi', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal terhubung ke server', 'error');
            }
        }

        async function fetchLicenseManagers() {
            try {
                const res = await fetch('./api.php?action=get_license_managers');
                const data = await res.json();
                const container = document.getElementById('managersList');

                if (data.success && Array.isArray(data.users)) {
                    container.innerHTML = data.users.map(u => `
                        <label style="display:flex; align-items:center; gap:10px; padding:6px 8px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:0.85rem;">
                            <input type="checkbox" name="mgr_usernames" value="${u.username}" ${u.is_manager || u.is_owner ? 'checked' : ''} ${u.is_owner ? 'disabled' : ''} style="width:16px; height:16px; accent-color:var(--primary);">
                            <div style="flex:1;">
                                <div style="font-weight:700; color:var(--text-heading);">${u.name} <span style="font-size:0.75rem; color:var(--text-sub); font-weight:normal;">(@${u.username})</span></div>
                                <div style="font-size:0.72rem; color:var(--primary); font-weight:600;">${u.role.toUpperCase()} ${u.is_owner ? '(Pemilik Utama - Permanen)' : ''}</div>
                            </div>
                        </label>
                    `).join('');
                } else {
                    container.innerHTML = `<div style="color:var(--danger); font-size:0.8rem; text-align:center;">${data.error || 'Gagal memuat pengguna'}</div>`;
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function fetchLicenseLogs() {
            try {
                const res = await fetch('./api.php?action=get_license_logs');
                const data = await res.json();
                const tbody = document.getElementById('logsTableBody');

                if (data.success && Array.isArray(data.logs)) {
                    cachedLogs = data.logs;
                    if (data.logs.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-sub); padding:16px;">Belum ada riwayat perpanjangan.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = data.logs.map((log, idx) => {
                        const createdDate = new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                        const newExpiryDate = new Date(log.new_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const trxNo = 'INV-LIC-' + String(log.id).padStart(5, '0');

                        return `
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary);">${trxNo}</div>
                                    <div style="font-size:0.72rem; color:var(--text-sub);">${createdDate}</div>
                                </td>
                                <td><span style="font-weight:600;">@${log.extended_by}</span></td>
                                <td><span class="status-badge status-active" style="padding:2px 8px; font-size:0.75rem;">${log.extension_type}</span></td>
                                <td><span style="font-weight:700; color:var(--text-heading);">${newExpiryDate}</span></td>
                                <td><span style="font-style:italic; color:var(--text-sub);">${log.payment_notes || '-'}</span></td>
                                <td>
                                    <button type="button" class="btn-print" onclick="printReceipt(${log.id})">
                                        <span class="material-symbols-outlined" style="font-size:14px;">print</span> Cetak Bukti
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function printReceipt(logId) {
            const log = cachedLogs.find(l => l.id == logId);
            if (!log) {
                showToast('Data bukti perpanjangan tidak ditemukan', 'error');
                return;
            }

            const trxNo = 'INV-LIC-' + String(log.id).padStart(5, '0');
            const createdDate = new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
            const newExpiryDate = new Date(log.new_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            const prevExpiryDate = log.previous_expiry ? new Date(log.previous_expiry).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Sistem Baru';

            const printWin = window.open('', '_blank', 'width=800,height=700');
            printWin.document.write(`
                <!DOCTYPE html>
                <html lang="id">
                <head>
                    <title>Bukti Perpanjangan Lisensi - ${trxNo}</title>
                    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
                    <style>
                        body { font-family: 'Plus Jakarta Sans', sans-serif; padding: 40px; color: #1e293b; background: #fff; }
                        .ticket { border: 2px solid #6366f1; border-radius: 16px; padding: 30px; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
                        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 20px; margin-bottom: 20px; }
                        .title { font-size: 20px; font-weight: 800; color: #4338ca; }
                        .badge { background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 99px; text-transform: uppercase; }
                        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
                        .info-item { background: #f8fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
                        .info-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
                        .info-value { font-size: 14px; font-weight: 800; color: #0f172a; }
                        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
                        .stamp { font-size: 12px; font-weight: 700; color: #4338ca; border: 2px solid #6366f1; padding: 6px 14px; border-radius: 8px; transform: rotate(-3deg); display: inline-block; }
                        @media print { body { padding: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="no-print" style="max-width:650px; margin:0 auto 16px text-align:right;">
                        <button onclick="window.print()" style="background:#4f46e5; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer;">🖨️ Cetak / Simpan PDF</button>
                    </div>
                    <div class="ticket">
                        <div class="header">
                            <div>
                                <div class="title">BUKTI PERPANJANGAN LISENSI WEB</div>
                                <div style="font-size:12px; color:#64748b; margin-top:2px;">TMS Head Office Operations System</div>
                            </div>
                            <div class="badge">LUNAS / VERIFIED</div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">No. Transaksi</div>
                                <div class="info-value">${trxNo}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Waktu Transaksi</div>
                                <div class="info-value">${createdDate}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Jenis Tambahan Masa Aktif</div>
                                <div class="info-value" style="color:#4f46e5;">${log.extension_type}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Pengelola Operator</div>
                                <div class="info-value">@${log.extended_by}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Masa Aktif Sebelumnya</div>
                                <div class="info-value">${prevExpiryDate}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Masa Aktif Baru (Berlaku S.D)</div>
                                <div class="info-value" style="color:#10b981;">${newExpiryDate}</div>
                            </div>
                        </div>

                        <div class="info-item" style="margin-bottom: 20px;">
                            <div class="info-label">Catatan Pembayaran / Referensi Transfer</div>
                            <div class="info-value">${log.payment_notes || 'Tidak ada catatan.'}</div>
                        </div>

                        <div class="footer">
                            <div>
                                <div style="font-size:11px; color:#64748b;">Dokumen ini diterbitkan secara otomatis oleh sistem.</div>
                                <div style="font-size:11px; font-weight:700; color:#0f172a; margin-top:4px;">TMS System License Verification</div>
                            </div>
                            <div class="stamp">OFFICIAL SYSTEM RECEIPT</div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWin.document.close();
        }

        function quickAddMonths(months) {
            const labelStr = months >= 12 ? `${months / 12} Tahun` : `${months} Bulan`;
            showPromptToast(
                `Perpanjang lisensi web +${labelStr}?`,
                `Perpanjang +${labelStr}`,
                'Contoh: Lunas via Transfer BCA',
                async (notes) => {
                    const formData = new FormData();
                    formData.append('months', months);
                    formData.append('notes', notes);

                    try {
                        const res = await fetch('./api.php?action=update_system_license', { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            fetchLicenseData();
                            fetchLicenseLogs();
                            if (data.log_id) {
                                setTimeout(() => printReceipt(data.log_id), 600);
                            }
                        } else {
                            showToast(data.error || 'Gagal memperpanjang lisensi', 'error');
                        }
                    } catch (err) {
                        showToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                'Proses Perpanjangan'
            );
        }

        document.getElementById('licenseForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitForm');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            try {
                const res = await fetch('./api.php?action=update_system_license', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchLicenseData();
                    fetchLicenseLogs();
                    if (data.log_id) {
                        setTimeout(() => printReceipt(data.log_id), 600);
                    }
                } else {
                    showToast(data.error || 'Gagal memperbarui lisensi', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">save</span> Simpan Perubahan Lisensi';
            }
        };

        document.getElementById('managersForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitManagers');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Menyimpan...';

            const selected = Array.from(document.querySelectorAll('input[name="mgr_usernames"]:checked')).map(cb => cb.value);

            const formData = new FormData();
            formData.append('usernames', selected.join(','));

            try {
                const res = await fetch('./api.php?action=update_license_managers', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchLicenseManagers();
                } else {
                    showToast(data.error || 'Gagal menyimpan otorisasi', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">how_to_reg</span> Simpan Hak Akses Pengelola';
            }
        };

        window.addEventListener('DOMContentLoaded', () => {
            fetchLicenseData();
            fetchLicenseManagers();
            fetchLicenseLogs();
        });
    </script>
</body>

</html>
