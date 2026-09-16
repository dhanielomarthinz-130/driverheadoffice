<?php
// sidebar.php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$view = $_GET['view'] ?? '';
$type = $_GET['type'] ?? '';
$role = $_SESSION['role'] ?? 'driver';
$username = $_SESSION['name'] ?? 'User';

// Determine which sidebar group should be open on page load
$is_wh_open = (
    ($current_page == 'dashboard_summary.php' && $view == '') ||
    ($current_page == 'driver_kpi.php') ||
    ($current_page == 'monitor_live.php') ||
    ($current_page == 'assign_tasks.php' || $current_page == 'expedisi_tasks.php') ||
    ($current_page == 'performance_heatmap.php') ||
    ($current_page == 'report_tracking.php') ||
    ($current_page == 'report_vehicles.php') ||
    ($current_page == 'timeline.php')
);

$is_mgmt_open = (
    ($current_page == 'manage_locations.php') ||
    ($current_page == 'manage_vehicles.php') ||
    ($current_page == 'manage_users.php') ||
    ($current_page == 'manage_expedisi.php') ||
    ($current_page == 'manage_roles.php') ||
    ($current_page == 'manage_permissions.php') ||
    ($current_page == 'manage_database.php') ||
    ($current_page == 'system_logs.php')
);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
<link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />

<nav class="sidebar" id="mainSidebar">
    <button id="sidebarToggle" class="sidebar-toggle" title="Toggle Sidebar">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>
    <!-- Brand area -->
    <a href="<?php echo ($role == 'driver') ? 'driver' : 'dashboard_summary'; ?>" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <span class="material-symbols-outlined">local_shipping</span>
        </div>
        <div class="sidebar-brand-text">
            <strong>T M S - Head Office</strong>
            <small>Transportation Management System</small>
        </div>
    </a>

    <div class="sidebar-nav">
        <!-- GROUP: HEAD OFFICE OPERATION -->
        <?php if (canAccessMenu('dashboard') || canAccessMenu('monitor') || canAccessMenu('assign_tasks') || canAccessMenu('performance') || canAccessMenu('report') || canAccessMenu('driver_kpi')): ?>
            <div class="nav-group <?php echo $is_wh_open ? 'open' : ''; ?>">
                <button type="button" class="nav-group-trigger">
                    <span class="material-symbols-outlined nav-icon">inventory_2</span>
                    <span class="nav-text">Head Office Operation</span>
                    <span class="material-symbols-outlined nav-arrow">chevron_right</span>
                </button>
                <div class="nav-group-items">
                    <?php if (canAccessMenu('dashboard')): ?>
                        <a href="dashboard_summary"
                            class="nav-link <?php echo ($current_page == 'dashboard_summary.php' && $view == '') ? 'active' : ''; ?>"
                            data-tooltip="Dashboard Activity">
                            <span class="material-symbols-outlined nav-icon">dashboard</span>
                            <span class="nav-text">Dashboard Activity</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('driver_kpi')): ?>
                        <a href="driver_kpi" class="nav-link <?php echo ($current_page == 'driver_kpi.php') ? 'active' : ''; ?>"
                            data-tooltip="Dashboard KPI">
                            <span class="material-symbols-outlined nav-icon">emoji_events</span>
                            <span class="nav-text">Dashboard KPI</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('monitor')): ?>
                        <a href="monitor_live"
                            class="nav-link <?php echo ($current_page == 'monitor_live.php') ? 'active' : ''; ?>"
                            data-tooltip="Tracking Driver">
                            <span class="material-symbols-outlined nav-icon">location_on</span>
                            <span class="nav-text">Tracking Driver</span>
                            <div style="margin-left:auto; display:flex; align-items:center; gap:6px;">
                                <span id="sidebarGpsAlertBadge" class="badge badge-pending"
                                    style="display:none; padding: 2px 6px; font-size: 0.6rem; background:#ef4444; color:white; border:none; border-radius:99px; animation: pulse 2s infinite;">0</span>
                                <span class="pulse-dot"></span>
                            </div>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('assign_tasks')): ?>
                        <a href="assign_tasks"
                            class="nav-link <?php echo ($current_page == 'assign_tasks.php' || $current_page == 'expedisi_tasks.php') ? 'active' : ''; ?>"
                            data-tooltip="Penugasan Driver">
                            <span class="material-symbols-outlined nav-icon">assignment</span>
                            <span class="nav-text">Penugasan Driver</span>
                            <div style="margin-left:auto; display:flex; gap:4px; align-items:center;">
                                <span id="sidebarWHBadge" class="badge"
                                    style="display:none; padding: 2px 6px; font-size: 0.6rem; background:#ef4444; color:white; border:none; font-weight:800; border-radius:99px; animation: badgePulse 2s infinite;">0</span>
                            </div>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('performance')): ?>
                        <a href="performance_heatmap"
                            class="nav-link <?php echo ($current_page == 'performance_heatmap.php') ? 'active' : ''; ?>"
                            data-tooltip="Time Line Driver">
                            <span class="material-symbols-outlined nav-icon">grid_view</span>
                            <span class="nav-text">Time Line Driver</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('report')): ?>
                        <a href="report_tracking"
                            class="nav-link <?php echo ($current_page == 'report_tracking.php') ? 'active' : ''; ?>"
                            data-tooltip="Laporan Tracking Driver">
                            <span class="material-symbols-outlined nav-icon">analytics</span>
                            <span class="nav-text">Laporan Tracking Driver</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('report_vehicles') || canAccessMenu('vehicles') || $role === 'admin' || $role === 'controller'): ?>
                        <a href="report_vehicles"
                            class="nav-link <?php echo ($current_page == 'report_vehicles.php') ? 'active' : ''; ?>"
                            data-tooltip="Pakai & Lepas Mobil">
                            <span class="material-symbols-outlined nav-icon">directions_car</span>
                            <span class="nav-text">Pakai & Lepas Mobil</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- GROUP: REQUEST PICKUP REMOVED -->

        <!-- GROUP: SETTINGS -->
        <?php if (canAccessMenu('vehicles') || canAccessMenu('locations') || canAccessMenu('users') || canAccessMenu('roles') || canAccessMenu('database')): ?>
            <div class="nav-group <?php echo $is_mgmt_open ? 'open' : ''; ?>">
                <button type="button" class="nav-group-trigger">
                    <span class="material-symbols-outlined nav-icon">settings</span>
                    <span class="nav-text">Settings</span>
                    <span class="material-symbols-outlined nav-arrow">chevron_right</span>
                </button>
                <div class="nav-group-items">
                    <?php if (canAccessMenu('locations')): ?>
                        <a href="manage_locations"
                            class="nav-link <?php echo ($current_page == 'manage_locations.php') ? 'active' : ''; ?>"
                            data-tooltip="Lokasi POS">
                            <span class="material-symbols-outlined nav-icon">map</span>
                            <span class="nav-text">Lokasi POS</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('vehicles')): ?>
                        <a href="manage_vehicles"
                            class="nav-link <?php echo ($current_page == 'manage_vehicles.php') ? 'active' : ''; ?>"
                            data-tooltip="Kelola Kendaraan">
                            <span class="material-symbols-outlined nav-icon">local_shipping</span>
                            <span class="nav-text">Kelola Kendaraan</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('users')): ?>
                        <a href="manage_users"
                            class="nav-link <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>"
                            data-tooltip="Kelola Pengguna">
                            <span class="material-symbols-outlined nav-icon">manage_accounts</span>
                            <span class="nav-text">Kelola Pengguna</span>
                        </a>
                    <?php endif; ?>

                    <!-- Removed Kelola Expedisi -->

                    <?php if (canAccessMenu('roles')): ?>
                        <a href="manage_roles"
                            class="nav-link <?php echo ($current_page == 'manage_roles.php') ? 'active' : ''; ?>"
                            data-tooltip="Manajemen Role">
                            <span class="material-symbols-outlined nav-icon">security</span>
                            <span class="nav-text">Manajemen Role</span>
                        </a>
                        <a href="manage_permissions"
                            class="nav-link <?php echo ($current_page == 'manage_permissions.php') ? 'active' : ''; ?>"
                            data-tooltip="Settings">
                            <span class="material-symbols-outlined nav-icon">key</span>
                            <span class="nav-text">Settings</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($role === 'controller'): ?>
                        <a href="manage_database"
                            class="nav-link <?php echo ($current_page == 'manage_database.php') ? 'active' : ''; ?>"
                            data-tooltip="Kelola Database">
                            <span class="material-symbols-outlined nav-icon">database</span>
                            <span class="nav-text">Kelola Database</span>
                        </a>
                    <?php endif; ?>

                    <?php if (canAccessMenu('roles')): ?>
                        <a href="system_logs"
                            class="nav-link <?php echo ($current_page == 'system_logs.php') ? 'active' : ''; ?>"
                            data-tooltip="Log Aktivitas">
                            <span class="material-symbols-outlined nav-icon">history_edu</span>
                            <span class="nav-text">Log Aktivitas</span>
                        </a>
                    <?php endif; ?>

                    <?php if (function_exists('canManageSystemLicense') && canManageSystemLicense()): ?>
                        <a href="system_license"
                            class="nav-link <?php echo ($current_page == 'system_license.php') ? 'active' : ''; ?>"
                            data-tooltip="Lisensi System">
                            <span class="material-symbols-outlined nav-icon">verified</span>
                            <span class="nav-text">Lisensi System</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- GROUP: PERSONAL TOOLS -->
        <a href="task_notes" class="nav-link <?php echo ($current_page == 'task_notes.php') ? 'active' : ''; ?>"
            data-tooltip="Catatan Task">
            <span class="material-symbols-outlined nav-icon">checklist</span>
            <span class="nav-text">Catatan Task</span>
            <span id="sidebarTaskBadge" class="badge"
                style="display:none; margin-left:auto; padding: 2px 6px; font-size: 0.6rem; background: linear-gradient(135deg, #6366f1, #818cf8); color:white; border:none; font-weight:800; border-radius:99px; animation: badgePulse 2s infinite;"></span>
        </a>

        <!-- GROUP: MOBILE VIEW -->
        <?php if (canAccessMenu('mobile_menu')): ?>
            <a href="mobile_home" target="_blank"
                class="nav-link <?php echo ($current_page == 'mobile_home.php') ? 'active' : ''; ?>"
                data-tooltip="Menu Utama Mobile">
                <span class="material-symbols-outlined nav-icon">smartphone</span>
                <span class="nav-text">Menu Utama Mobile</span>
            </a>
        <?php endif; ?>

    </div>

    <!-- Removed sidebar-user (moved to topbar) -->
</nav>

<!-- GLOBAL TASK NOTES NOTIFICATION TOAST -->
<div class="task-notification" id="globalTaskNotification" style="display: none;">
    <div class="tn-header">
        <div class="bell-icon">
            <span class="material-symbols-outlined">notifications_active</span>
        </div>
        <div>
            <strong>🔔 Pengingat Catatan Task</strong>
            <small>Ada catatan yang dijadwalkan hari ini</small>
        </div>
    </div>
    <button class="tn-close" onclick="closeGlobalNotification()">
        <span class="material-symbols-outlined" style="font-size:18px;">close</span>
    </button>
    <ul class="tn-list" id="globalNotifList"></ul>
</div>

<style>
    /* ── Notification Toast ── */
    .task-notification {
        position: fixed;
        top: 80px;
        right: 1.5rem;
        z-index: 9999;
        max-width: 380px;
        width: 100%;
        background: var(--card, #ffffff);
        border-radius: 16px;
        border: 1px solid var(--border, #e2e8f0);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        padding: 1.25rem 1.5rem;
        transform: translateX(120%);
        transition: transform .5s cubic-bezier(.34, 1.56, .64, 1);
        overflow: hidden;
        font-family: inherit;
    }

    .task-notification::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6366f1, #f59e0b, #ef4444);
    }

    .task-notification.show {
        transform: translateX(0);
    }

    .task-notification .tn-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .task-notification .tn-header .bell-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        animation: bellRing 1s ease-in-out 3;
    }

    @keyframes bellRing {

        0%,
        100% {
            transform: rotate(0);
        }

        15% {
            transform: rotate(10deg);
        }

        30% {
            transform: rotate(-10deg);
        }

        45% {
            transform: rotate(8deg);
        }

        60% {
            transform: rotate(-6deg);
        }

        75% {
            transform: rotate(3deg);
        }
    }

    .task-notification .tn-header strong {
        font-size: 0.95rem;
        color: var(--text, #1e293b);
        display: block;
    }

    .task-notification .tn-header small {
        display: block;
        font-size: 0.75rem;
        color: var(--text-muted, #64748b);
    }

    .task-notification .tn-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: none;
        background: var(--surface, #f8fafc);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted, #64748b);
        font-size: 18px;
        transition: .2s;
    }

    .task-notification .tn-close:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    .task-notification .tn-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .task-notification .tn-list li {
        padding: 0.4rem 0;
        font-size: 0.82rem;
        color: var(--text, #1e293b);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border, #e2e8f0);
    }

    .task-notification .tn-list li:last-child {
        border-bottom: none;
    }

    .task-notification .tn-list li .material-symbols-outlined {
        font-size: 16px;
    }

    .task-notification .tn-list li .material-symbols-outlined.high {
        color: #ef4444;
    }

    .task-notification .tn-list li .material-symbols-outlined.medium {
        color: #f59e0b;
    }

    .task-notification .tn-list li .material-symbols-outlined.low {
        color: #10b981;
    }

    @media (max-width: 768px) {
        .task-notification {
            right: 0.5rem;
            left: 0.5rem;
            max-width: none;
        }
    }
</style>

<?php include_once __DIR__ . '/profile_modal.php'; ?>

<script>
    // Sidebar Minimize/Maximize logic
    (function () {
        const sidebar = document.getElementById('mainSidebar');
        const toggleBtn = document.getElementById('sidebarToggle');

        // Initial state from localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    })();

    // Sidebar Group Accordion Logic
    (function () {
        const sidebar = document.getElementById('mainSidebar');

        // Toggle group on click
        document.querySelectorAll('.nav-group-trigger').forEach(trigger => {
            trigger.addEventListener('click', () => {
                // If sidebar is collapsed, expand it first
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }

                const group = trigger.closest('.nav-group');
                const isOpen = group.classList.contains('open');

                // Accordion: close other groups
                document.querySelectorAll('.nav-group').forEach(g => {
                    if (g !== group) g.classList.remove('open');
                });

                group.classList.toggle('open', !isOpen);
            });
        });

        // Auto-expand group containing active link on load
        document.querySelectorAll('.nav-group').forEach(group => {
            if (group.querySelector('.nav-link.active')) {
                group.classList.add('open');
            }
        });
    })();

    (async function updateSidebarBadge() {
        const badgeWH = document.getElementById('sidebarWHBadge');
        const badgeExp = document.getElementById('sidebarExpBadge');
        if (!badgeWH && !badgeExp) return;

        try {
            const res = await fetch('api.php?action=get_pending_counts');
            const data = await res.json();

            if (badgeWH) {
                if (data.pickup > 0) {
                    badgeWH.textContent = data.pickup;
                    badgeWH.style.display = 'inline-block';
                } else {
                    badgeWH.style.display = 'none';
                }
            }

            if (badgeExp) {
                if (data.expedisi > 0) {
                    badgeExp.textContent = data.expedisi;
                    badgeExp.style.display = 'inline-block';
                } else {
                    badgeExp.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Sidebar badge error:', e);
        }
        setTimeout(updateSidebarBadge, 15000); // Check every 15s
    })();

    (async function updateGpsAlertBadge() {
        const badge = document.getElementById('sidebarGpsAlertBadge');
        if (!badge) return;
        try {
            const res = await fetch('api.php?action=get_gps_alerts');
            const data = await res.json();
            const count = Array.isArray(data) ? data.length : 0;
            const seenCount = parseInt(localStorage.getItem('gps_alerts_seen') || 0);

            if (count > seenCount) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
                // If count decreased, sync seenCount to the new lower total
                if (count < seenCount) {
                    localStorage.setItem('gps_alerts_seen', count);
                }
            }
        } catch (e) {
            console.error('GPS alert badge error:', e);
        }
        setTimeout(updateGpsAlertBadge, 30000); // Check every 30s
    })();

    // Task Notes Badge
    (async function updateTaskNotesBadge() {
        const badge = document.getElementById('sidebarTaskBadge');
        if (!badge) return;
        try {
            const res = await fetch('api.php?action=get_today_task_notes_count');
            const data = await res.json();
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        } catch (e) {
            console.error('Task notes badge error:', e);
        }
        setTimeout(updateTaskNotesBadge, 30000); // Refresh every 30s
    })();

    // Global notification/alarm for Task Notes
    function playNotificationChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const now = ctx.currentTime;

            // Premium chime sound
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, now); // C5
            osc1.frequency.exponentialRampToValueAtTime(880.00, now + 0.15); // A5
            gain1.gain.setValueAtTime(0.15, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'triangle';
            osc2.frequency.setValueAtTime(329.63, now); // E4
            osc2.frequency.exponentialRampToValueAtTime(659.25, now + 0.15); // E5
            gain2.gain.setValueAtTime(0.08, now);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc1.start(now);
            osc1.stop(now + 0.8);
            osc2.start(now);
            osc2.stop(now + 0.8);
        } catch (e) {
            console.warn('Audio chime could not be played:', e);
        }
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }

    window.closeGlobalNotification = function () {
        const notif = document.getElementById('globalTaskNotification');
        if (notif) notif.classList.remove('show');
        sessionStorage.setItem('task_notif_dismissed', '1');
    };

    (async function checkGlobalTodayNotification() {
        // Only run if not already dismissed in this session
        if (sessionStorage.getItem('task_notif_dismissed')) return;

        try {
            const res = await fetch('api.php?action=get_today_task_notes_count');
            const data = await res.json();

            if (data && data.count > 0) {
                // Check if any notes should trigger now based on time
                const now = new Date();
                const currentHHMM = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

                // Filter: show notes that have no time set, OR whose time <= current time
                const readyNotes = data.notes.filter(n => {
                    if (!n.note_time) return true; // no time = show immediately
                    return n.note_time.substring(0, 5) <= currentHHMM;
                });

                if (readyNotes.length === 0) return; // all notes are scheduled for later

                const notif = document.getElementById('globalTaskNotification');
                const list = document.getElementById('globalNotifList');
                if (!notif || !list) return;

                list.innerHTML = readyNotes.map(n => {
                    const icon = n.priority === 'high' ? 'priority_high' : (n.priority === 'medium' ? 'drag_handle' : 'arrow_downward');
                    const timeStr = n.note_time ? `<span style="opacity:0.7; font-size:0.8em; margin-left:4px;">⏰ ${n.note_time.substring(0, 5)}</span>` : '';
                    return `<li>
                        <span class="material-symbols-outlined ${n.priority}">${icon}</span>
                        <span style="font-weight: 500;">${escapeHtml(n.title)}${timeStr}</span>
                    </li>`;
                }).join('');

                if (data.count > readyNotes.length) {
                    const laterCount = data.count - readyNotes.length;
                    list.innerHTML += `<li style="color:var(--text-muted, #64748b); font-style:italic;">...dan ${laterCount} catatan lainnya dijadwalkan nanti</li>`;
                }

                notif.style.display = 'block';
                // Slide in with delay
                setTimeout(() => {
                    notif.classList.add('show');
                    // Play premium chime sound!
                    playNotificationChime();
                }, 1500);

                // Auto-dismiss after 12 seconds
                setTimeout(() => {
                    notif.classList.remove('show');
                }, 13500);
            }
        } catch (e) {
            console.error('Global task notification error:', e);
        }
    })();

    // Recurring time-based alarm check every 60 seconds
    setInterval(async () => {
        if (sessionStorage.getItem('task_notif_dismissed')) return;
        try {
            const res = await fetch('api.php?action=get_today_task_notes_count');
            const data = await res.json();
            if (!data || data.count === 0) return;

            const now = new Date();
            const currentHHMM = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            // Only trigger for notes whose time matches current minute exactly
            const matchingNotes = data.notes.filter(n => {
                if (!n.note_time) return false;
                return n.note_time.substring(0, 5) === currentHHMM;
            });

            if (matchingNotes.length === 0) return;

            // Check if we already showed this alarm this minute
            const alarmKey = 'task_alarm_' + currentHHMM;
            if (sessionStorage.getItem(alarmKey)) return;
            sessionStorage.setItem(alarmKey, '1');

            const notif = document.getElementById('globalTaskNotification');
            const list = document.getElementById('globalNotifList');
            if (!notif || !list) return;

            list.innerHTML = matchingNotes.map(n => {
                const icon = n.priority === 'high' ? 'priority_high' : (n.priority === 'medium' ? 'drag_handle' : 'arrow_downward');
                return `<li>
                    <span class="material-symbols-outlined ${n.priority}">${icon}</span>
                    <span style="font-weight: 500;">${escapeHtml(n.title)} <span style="opacity:0.7; font-size:0.8em;">⏰ ${n.note_time.substring(0, 5)}</span></span>
                </li>`;
            }).join('');

            notif.style.display = 'block';
            setTimeout(() => {
                notif.classList.add('show');
                playNotificationChime();
            }, 500);

            setTimeout(() => {
                notif.classList.remove('show');
            }, 14000);
        } catch (e) {
            console.error('Time-alarm check error:', e);
        }
    }, 60000);

    // Idle Timer (45 Minutes)
    (function () {
        let idleTime = 0;
        const maxIdle = 45; // 45 minutes

        const resetTimer = () => {
            idleTime = 0;
        };

        // Increment the idle time counter every minute
        const idleInterval = setInterval(() => {
            idleTime++;
            if (idleTime >= maxIdle) {
                window.location.href = 'logout.php?timeout=1';
            }
        }, 60000); // 1 minute in milliseconds

        // Reset the idle timer on any user interaction
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetTimer, true);
        });
    })();
</script>

<?php include_once __DIR__ . '/topbar.php'; ?>