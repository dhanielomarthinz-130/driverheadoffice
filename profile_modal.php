<?php
/**
 * profile_modal.php — Komponen Modal Profil User (Reusable)
 * 
 * Include file ini di setiap halaman untuk menampilkan profil user dan
 * fitur ganti password. Dipanggil via openProfileModal() dari JavaScript.
 * 
 * Requirement: Session PHP sudah aktif, Material Symbols sudah dimuat.
 */
?>
<style>
/* ===== PROFILE MODAL ===== */
#profileModal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 15, 30, 0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 1.5rem;
}
#profileModal.show { display: flex; }

.pm-box {
    background: white;
    border-radius: 1.5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 30px 70px rgba(0, 0, 0, 0.28);
    overflow: hidden;
    animation: pmSlideIn 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes pmSlideIn {
    from { opacity: 0; transform: scale(0.86) translateY(24px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Header banner */
.pm-header {
    background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
    padding: 2.25rem 1.5rem 4rem;
    text-align: center;
    position: relative;
}

.pm-close-btn {
    position: absolute;
    top: 0.875rem;
    right: 0.875rem;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: rgba(255,255,255,0.85);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}
.pm-close-btn:hover { background: rgba(255, 255, 255, 0.28); }
.pm-close-btn .material-symbols-outlined { font-size: 18px; }

.pm-avatar {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.875rem;
    border: 3px solid rgba(255, 255, 255, 0.25);
    font-size: 1.8rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.02em;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.pm-header-name {
    color: white;
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    letter-spacing: -0.01em;
}

.pm-header-username {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.8rem;
    font-weight: 500;
}

/* Body */
.pm-body {
    padding: 0 1.25rem 1.5rem;
    margin-top: -2.25rem;
}

/* Role badge floating */
.pm-role-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 1.25rem;
}
.pm-role-pill {
    padding: 0.3rem 1.1rem;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border: 1.5px solid currentColor;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.pm-role-pill.admin       { background: #fee2e2; color: #b91c1c; }
.pm-role-pill.controller  { background: #e0e7ff; color: #4338ca; }
.pm-role-pill.driver      { background: #dcfce7; color: #15803d; }

/* Info grid */
.pm-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.625rem;
    margin-bottom: 1.5rem;
}
.pm-info-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 0.75rem 0.875rem;
}
.pm-info-item .lbl {
    font-size: 0.62rem;
    color: #94a3b8;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.pm-info-item .lbl .material-symbols-outlined { font-size: 13px; }
.pm-info-item .val {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}

/* Section divider */
.pm-section-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.pm-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}
.pm-section-title .material-symbols-outlined { font-size: 14px; color: #6366f1; }

/* Message box */
#pmMsg {
    border-radius: 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    display: none;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
    padding: 0.625rem 0.875rem;
}
#pmMsg.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; display: flex; }
#pmMsg.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; display: flex; }
#pmMsg .material-symbols-outlined { font-size: 16px; flex-shrink: 0; }

/* Password form */
.pm-form-row {
    position: relative;
    margin-bottom: 0.6rem;
}
.pm-form-row input {
    width: 100%;
    padding: 0.68rem 2.5rem 0.68rem 0.875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.75rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.875rem;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    transition: 0.2s;
    -webkit-appearance: none;
}
.pm-form-row input:focus {
    border-color: #6366f1;
    background: white;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.14);
}
.pm-form-row input::placeholder { color: #94a3b8; }

.pm-pwd-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #94a3b8;
    display: flex;
    align-items: center;
    padding: 0;
    transition: color 0.2s;
}
.pm-pwd-toggle:hover { color: #6366f1; }
.pm-pwd-toggle .material-symbols-outlined { font-size: 18px; }

/* Save button */
.pm-save-btn {
    width: 100%;
    padding: 0.8rem;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    border: none;
    border-radius: 0.875rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: 0.2s;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.38);
    margin-top: 0.5rem;
}
.pm-save-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.48); }
.pm-save-btn:active:not(:disabled) { transform: scale(0.98); }
.pm-save-btn:disabled { opacity: 0.62; cursor: not-allowed; }
.pm-save-btn .material-symbols-outlined { font-size: 18px; }

/* Sidebar user — make it clickable */
.sidebar-user {
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
}
.sidebar-user:hover { background: #1e2f48; }
.sidebar-user::after {
    content: 'edit';
    font-family: 'Material Symbols Outlined';
    font-size: 15px;
    color: rgba(255,255,255,0.3);
    position: absolute;
    right: 1.1rem;
    top: 50%;
    transform: translateY(-50%);
    transition: color 0.2s;
}
.sidebar-user:hover::after { color: rgba(255,255,255,0.7); }

/* Driver page profile button */
.profile-hdr-btn {
    background: rgba(255, 255, 255, 0.2);
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: none;
    cursor: pointer;
    transition: 0.3s;
    flex-shrink: 0;
}
.profile-hdr-btn:hover { background: rgba(255, 255, 255, 0.3); }
.profile-hdr-btn .material-symbols-outlined { font-size: 22px; }
</style>

<!-- ===== Profile Modal HTML ===== -->
<div id="profileModal" onclick="if(event.target===this) closeProfileModal()">
    <div class="pm-box">
        <!-- Header -->
        <div class="pm-header">
            <button class="pm-close-btn" onclick="closeProfileModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="pm-avatar" id="pmAvatar">?</div>
            <div class="pm-header-name" id="pmName">Memuat...</div>
            <div class="pm-header-username" id="pmUsername">@...</div>
        </div>

        <!-- Body -->
        <div class="pm-body">
            <!-- Role badge -->
            <div class="pm-role-wrap">
                <span class="pm-role-pill" id="pmRoleBadge">—</span>
            </div>

            <!-- Profile Data -->
            <div class="pm-section-title">
                <span class="material-symbols-outlined">person</span>
                Data Profil
            </div>
            <div class="pm-form-row">
                <input type="text" id="pmEditName" placeholder="Nama Lengkap">
            </div>
            <div class="pm-form-row">
                <input type="text" id="pmEditUsername" placeholder="Username">
            </div>
            <button class="pm-save-btn" id="pmUpdateProfileBtn" onclick="pmUpdateProfile()" style="margin-bottom:1.5rem; background:linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">
                <span class="material-symbols-outlined">save</span>
                Simpan Profil
            </button>

            <!-- Info grid (Optional: can be kept or removed if redundant) -->
            <div class="pm-info-grid" style="display:none;">
                <div class="pm-info-item">
                    <div class="lbl">
                        <span class="material-symbols-outlined">badge</span>
                        Role
                    </div>
                    <div class="val" id="pmRoleText">—</div>
                </div>
                <div class="pm-info-item">
                    <div class="lbl">
                        <span class="material-symbols-outlined">calendar_today</span>
                        Bergabung
                    </div>
                    <div class="val" id="pmJoined">—</div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="pm-section-title">
                <span class="material-symbols-outlined">lock</span>
                Ganti Password
            </div>

            <div id="pmMsg"></div>

            <div class="pm-form-row">
                <input type="password" id="pmOldPwd" placeholder="Password lama" autocomplete="current-password">
                <button type="button" class="pm-pwd-toggle" onclick="pmTogglePwd('pmOldPwd', this)">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
            </div>
            <div class="pm-form-row">
                <input type="password" id="pmNewPwd" placeholder="Password baru (min. 6 karakter)" autocomplete="new-password">
                <button type="button" class="pm-pwd-toggle" onclick="pmTogglePwd('pmNewPwd', this)">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
            </div>
            <div class="pm-form-row">
                <input type="password" id="pmConfirmPwd" placeholder="Konfirmasi password baru" autocomplete="new-password">
                <button type="button" class="pm-pwd-toggle" onclick="pmTogglePwd('pmConfirmPwd', this)">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
            </div>
            <button class="pm-save-btn" id="pmSaveBtn" onclick="pmChangePassword()">
                <span class="material-symbols-outlined">lock_reset</span>
                Simpan Password Baru
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const PM_API = 'api.php';

    // ── Open ──────────────────────────────────────────────────────────────
    window.openProfileModal = async function () {
        const modal = document.getElementById('profileModal');
        modal.classList.add('show');

        // Reset form & message
        ['pmOldPwd', 'pmNewPwd', 'pmConfirmPwd'].forEach(id => {
            document.getElementById(id).value = '';
            document.getElementById(id).type = 'password';
        });
        document.querySelectorAll('.pm-pwd-toggle .material-symbols-outlined')
            .forEach(ic => ic.textContent = 'visibility');
        pmShowMsg('', '');

        // Load profile
        try {
            const res  = await fetch(`${PM_API}?action=get_my_profile`);
            const data = await res.json();
            if (data.error) { alert(data.error); return; }

            // Avatar initials (max 2 chars)
            const initials = data.name.trim().split(/\s+/)
                .map(w => w[0]).slice(0, 2).join('').toUpperCase();
            document.getElementById('pmAvatar').textContent  = initials;
            document.getElementById('pmName').textContent    = data.name;
            document.getElementById('pmUsername').textContent = '@' + data.username;
            
            // Populate inputs
            document.getElementById('pmEditName').value = data.name;
            document.getElementById('pmEditUsername').value = data.username;

            // Role badge
            const roleMap = {
                admin:      { cls: 'admin',      label: 'Admin' },
                controller: { cls: 'controller', label: 'Controller' },
                driver:     { cls: 'driver',     label: 'Driver' },
            };
            const rm = roleMap[data.role] || { cls: '', label: data.role };
            const badge = document.getElementById('pmRoleBadge');
            badge.textContent = rm.label.toUpperCase();
            badge.className   = 'pm-role-pill ' + rm.cls;
            document.getElementById('pmRoleText').textContent = rm.label;

            // Joined date
            if (data.created_at) {
                const d = new Date(data.created_at);
                document.getElementById('pmJoined').textContent =
                    d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        } catch (e) {
            console.error('Profile load error:', e);
        }
    };

    // ── Close ─────────────────────────────────────────────────────────────
    window.closeProfileModal = function () {
        document.getElementById('profileModal').classList.remove('show');
    };

    // ── Toggle password visibility ────────────────────────────────────────
    window.pmTogglePwd = function (inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('.material-symbols-outlined');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    };

    // ── Show message ──────────────────────────────────────────────────────
    function pmShowMsg(msg, type) {
        const el = document.getElementById('pmMsg');
        if (!msg) { el.style.display = 'none'; el.className = ''; return; }

        const icons = { success: 'check_circle', error: 'error' };
        el.innerHTML = `<span class="material-symbols-outlined">${icons[type] || 'info'}</span> ${msg}`;
        el.className = type;
        el.style.display = 'flex';

        if (type === 'success') {
            setTimeout(() => { el.style.display = 'none'; }, 3500);
        }
    }

    // ── Update Profile (Name & Username) ──────────────────────────────────
    window.pmUpdateProfile = async function() {
        const name = document.getElementById('pmEditName').value.trim();
        const username = document.getElementById('pmEditUsername').value.trim();

        if (!name || !username) {
            pmShowMsg('Nama dan Username tidak boleh kosong!', 'error');
            return;
        }

        const btn = document.getElementById('pmUpdateProfileBtn');
        btn.disabled = true;
        const origText = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Menyimpan...';

        try {
            const fd = new FormData();
            fd.append('name', name);
            fd.append('username', username);

            const res = await fetch(`${PM_API}?action=update_my_profile`, { method: 'POST', body: fd });
            const result = await res.json();

            if (result.success) {
                pmShowMsg('Profil berhasil diperbarui!', 'success');
                // Update UI Header in Modal
                document.getElementById('pmName').textContent = name;
                document.getElementById('pmUsername').textContent = '@' + username;
                
                // Optional: Update sidebar if element exists
                const sbName = document.querySelector('.sidebar-user-name');
                if(sbName) sbName.textContent = name;
                const sbUser = document.querySelector('.sidebar-user-role'); // Usually shows @username
                if(sbUser && sbUser.textContent.startsWith('@')) sbUser.textContent = '@' + username;

            } else {
                pmShowMsg(result.error || 'Gagal memperbarui profil.', 'error');
            }
        } catch (e) {
            pmShowMsg('Terjadi kesalahan koneksi.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // ── Change password ───────────────────────────────────────────────────
    window.pmChangePassword = async function () {
        const old  = document.getElementById('pmOldPwd').value.trim();
        const nw   = document.getElementById('pmNewPwd').value;
        const conf = document.getElementById('pmConfirmPwd').value;

        if (!old || !nw || !conf) {
            pmShowMsg('Semua field password harus diisi!', 'error');
            return;
        }
        if (nw.length < 6) {
            pmShowMsg('Password baru minimal 6 karakter!', 'error');
            return;
        }
        if (nw !== conf) {
            pmShowMsg('Konfirmasi password tidak cocok!', 'error');
            return;
        }

        const btn = document.getElementById('pmSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Menyimpan...';

        try {
            const fd = new FormData();
            fd.append('old_password', old);
            fd.append('new_password', nw);

            const res    = await fetch(`${PM_API}?action=change_my_password`, { method: 'POST', body: fd });
            const result = await res.json();

            if (result.success) {
                pmShowMsg('Password berhasil diubah!', 'success');
                document.getElementById('pmOldPwd').value    = '';
                document.getElementById('pmNewPwd').value    = '';
                document.getElementById('pmConfirmPwd').value = '';
            } else {
                pmShowMsg(result.error || 'Gagal mengubah password.', 'error');
            }
        } catch (e) {
            pmShowMsg('Terjadi kesalahan koneksi. Coba lagi.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">lock_reset</span> Simpan Password Baru';
        }
    };

    // ── Close on Escape key ───────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeProfileModal();
    });
})();
</script>
