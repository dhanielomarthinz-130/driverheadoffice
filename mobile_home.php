<?php
session_start();
require_once 'auth_check.php';
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['name'];
$user_id = $_SESSION['user_id'];
$role_label = strtoupper(str_replace('_', ' ', $role));

$task_count = 0;
$pending_count = 0;
$in_transit_count = 0;
$completed_today = 0;

try {
    if ($role === 'driver') {
        $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status IN ('pending', 'in_transit')");
        $stmtActive->execute([$user_id]);
        $task_count = (int)$stmtActive->fetchColumn();

        $stmtP = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status = 'pending'");
        $stmtP->execute([$user_id]);
        $pending_count = (int)$stmtP->fetchColumn();

        $stmtT = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status = 'in_transit'");
        $stmtT->execute([$user_id]);
        $in_transit_count = (int)$stmtT->fetchColumn();

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status = 'completed' AND (DATE(end_time) = CURDATE() OR DATE(created_at) = CURDATE())");
        $stmtC->execute([$user_id]);
        $completed_today = (int)$stmtC->fetchColumn();
    } else {
        $stmtActive = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status IN ('pending', 'in_transit')");
        $task_count = (int)$stmtActive->fetchColumn();

        $stmtP = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status = 'pending'");
        $pending_count = (int)$stmtP->fetchColumn();

        $stmtT = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status = 'in_transit'");
        $in_transit_count = (int)$stmtT->fetchColumn();

        $stmtC = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status = 'completed' AND (DATE(end_time) = CURDATE() OR DATE(created_at) = CURDATE())");
        $completed_today = (int)$stmtC->fetchColumn();
    }
} catch (Exception $e) {
    $task_count = 0;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Driver App | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0">
    <style>
        :root {
            --primary: #007AFF;
            --primary-dark: #0056b3;
            --primary-light: rgba(0, 122, 255, 0.08);
            --accent: #FF9500;
            --success: #34C759;
            --danger: #FF3B30;
            --bg: #F2F2F7;
            --surface: #FFFFFF;
            --text: #1C1C1E;
            --text-sub: #8E8E93;
            --radius-xl: 2.5rem;
            --radius-lg: 1.5rem;
            --radius-md: 1rem;
            --shadow-premium: 0 8px 32px rgba(0, 0, 0, 0.04);
        }

        html {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Inter", sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: #f1f3f9;
            background-image:
                linear-gradient(rgba(99, 102, 241, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.04) 1px, transparent 1px),
                radial-gradient(at 0% 0%, rgba(143, 0, 255, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 122, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(255, 45, 85, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 149, 0, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(52, 199, 89, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            background-size: 30px 30px, 30px 30px, cover, cover, cover, cover, cover;
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            padding-bottom: 110px;
        }

        /* ===== PREMIUM HEADER ===== */
        .header {
            background: transparent;
            padding: 2.5rem 1.5rem 2rem;
            color: var(--text);
            position: relative;
            overflow: hidden;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-pic {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: linear-gradient(135deg, #007AFF, #89c4ff);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 20px rgba(0, 122, 255, 0.15);
        }

        .name-area h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.1;
            color: var(--text);
        }

        .name-area p {
            font-size: 0.85rem;
            color: var(--text-sub);
            font-weight: 600;
        }

        .role-chip {
            background: rgba(0, 122, 255, 0.1);
            color: var(--primary);
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            text-transform: uppercase;
            border: 1px solid rgba(0, 122, 255, 0.2);
            display: inline-block;
            margin-top: 6px;
        }

        /* ===== QUICK STATS FLOATING ===== */
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 0.5rem 1.5rem 1.5rem;
            position: relative;
            z-index: 20;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.1rem 1.25rem;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .stat-card:active {
            transform: scale(0.95);
        }

        .stat-card .icon-box {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: rgba(0, 122, 255, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card .val {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .stat-card .lab {
            font-size: 0.68rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        /* ===== APP DRAWER GRID ===== */
        .main-menu {
            padding: 0 1.5rem;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 750;
            color: var(--text);
            margin: 1.5rem 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }

        .menu-list {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            margin-bottom: 2rem;
        }

        .menu-list-item {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-list-item:active {
            transform: scale(0.97);
            background: rgba(255, 255, 255, 0.85);
        }

        .menu-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            flex-shrink: 0;
        }

        /* Vibrant Gradients */
        .color-1 {
            background: linear-gradient(135deg, #007AFF, #54aaff);
        }

        .color-2 {
            background: linear-gradient(135deg, #FF9500, #ffba54);
        }

        .color-3 {
            background: linear-gradient(135deg, #5856D6, #9896ff);
        }

        .color-4 {
            background: linear-gradient(135deg, #34C759, #6ee78f);
        }

        .color-5 {
            background: linear-gradient(135deg, #FF3B30, #ff7b72);
        }

        .color-6 {
            background: linear-gradient(135deg, #30B0C7, #7cdceb);
        }

        .menu-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .menu-info h3 {
            font-size: 0.95rem;
            font-weight: 750;
            color: var(--text);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .menu-info p {
            font-size: 0.72rem;
            color: var(--text-sub);
            font-weight: 600;
            line-height: 1.2;
        }

        .menu-arrow {
            color: #C7C7CC;
            font-size: 20px;
            transition: transform 0.2s;
        }

        .menu-list-item:active .menu-arrow {
            transform: translateX(3px);
        }

        /* ===== BOTTOM NAVIGATION ===== */
        .bottom-nav {
            position: fixed;
            bottom: 24px;
            left: 20px;
            right: 20px;
            height: 72px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 1rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.6);
            max-width: 460px;
            margin: 0 auto;
        }

        .nav-link {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #8E8E93;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link.active {
            color: var(--primary);
        }

        .nav-link span {
            font-size: 24px;
        }

        .nav-link small {
            font-size: 0.65rem;
            font-weight: 600;
        }

        /* ===== BOTTOM SHEET ===== */
        .sheet-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
            display: flex;
            align-items: flex-end;
        }

        .sheet-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .bottom-sheet {
            width: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-top: 1px solid rgba(255, 255, 255, 0.5);
            border-top-left-radius: 2.5rem;
            border-top-right-radius: 2.5rem;
            padding: 2.5rem 1.5rem 3rem;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.08);
        }

        .drag-handle {
            width: 40px;
            height: 5px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin: -1.25rem auto 1.5rem;
        }

        .option-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.2s;
        }

        .option-row:active {
            background: rgba(255, 255, 255, 0.8);
            transform: scale(0.98);
        }

        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .menu-list-item {
            animation: fadeSlideUp 0.5s ease-out both;
        }

        .menu-list-item:nth-child(1) {
            animation-delay: 0.05s;
        }

        .menu-list-item:nth-child(2) {
            animation-delay: 0.1s;
        }

        .menu-list-item:nth-child(3) {
            animation-delay: 0.15s;
        }

        .refresh-text {
            text-align: center;
            color: var(--text-sub);
            font-size: 0.75rem;
            margin: 2.5rem 0 1.5rem;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-top">
            <div class="user-info">
                <div class="profile-pic">
                    <span class="material-symbols-rounded" style="font-size:32px; color:white;">person</span>
                </div>
                <div class="name-area">
                    <p>Selamat Datang,</p>
                    <h1><?php echo htmlspecialchars($username); ?></h1>
                    <span class="role-chip"><?php echo htmlspecialchars($role_label); ?></span>
                </div>
            </div>
            <a href="driver.php" class="bell-btn" title="Notifikasi Tugas Driver">
                <span class="material-symbols-rounded" style="font-size:22px; color:var(--text);">notifications</span>
                <?php if ($task_count > 0): ?>
                    <span class="bell-badge-count"><?php echo $task_count > 99 ? '99+' : $task_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div style="text-align: center; padding: 0.2rem 1.5rem 1.2rem; position: relative; z-index: 20;">
        <div id="widgetTime"
            style="font-size: 3.8rem; font-weight: 800; color: var(--text); line-height: 1; letter-spacing: -0.05em; margin-bottom: 0.35rem; font-family: 'Outfit', -apple-system, sans-serif;">
            --:--</div>
        <div id="widgetDate"
            style="font-size: 0.85rem; font-weight: 700; color: var(--text-sub); letter-spacing: -0.01em; text-transform: uppercase;">
            --</div>
    </div>

    <!-- Quick Driver Task Summary Widget -->
    <div class="stats-container">
        <div class="stat-card" onclick="window.location.href='driver.php'">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="icon-box" style="background:rgba(255,59,48,0.12); color:#FF3B30;">
                    <span class="material-symbols-rounded">pending_actions</span>
                </div>
                <?php if ($task_count > 0): ?>
                    <span class="pulse-dot"></span>
                <?php endif; ?>
            </div>
            <div class="val" style="color: <?php echo $task_count > 0 ? '#FF3B30' : 'var(--text)'; ?>;">
                <?php echo $task_count; ?>
            </div>
            <div class="lab">Tugas Belum Selesai</div>
        </div>

        <div class="stat-card" onclick="window.location.href='driver'">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="icon-box" style="background:rgba(52,199,89,0.12); color:#34C759;">
                    <span class="material-symbols-rounded">task_alt</span>
                </div>
            </div>
            <div class="val" style="color: #34C759;">
                <?php echo $completed_today; ?>
            </div>
            <div class="lab">Selesai Hari Ini</div>
        </div>
    </div>

    <main class="main-menu">
        <div class="section-title">
            <span class="material-symbols-rounded" style="font-size:20px; color:var(--primary);">grid_view</span>
            Menu Layanan
        </div>

        <div class="menu-list">
            <?php if (canAccessMenu('tasks')): ?>
                <a href="driver" class="menu-list-item">
                    <div class="menu-left">
                        <div class="menu-icon color-1">
                            <span class="material-symbols-rounded">local_shipping</span>
                        </div>
                        <div class="menu-info">
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <h3>Menu Driver</h3>
                                <?php if ($task_count > 0): ?>
                                    <span class="task-badge-pill active-task">
                                        <span class="pulse-dot"></span>
                                        <?php echo $task_count; ?> Tugas Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="task-badge-pill zero-task">0 Tugas</span>
                                <?php endif; ?>
                            </div>
                            <p>
                                <?php if ($task_count > 0): ?>
                                    <?php echo $pending_count; ?> Pending • <?php echo $in_transit_count; ?> Berjalan
                                <?php else: ?>
                                    Lihat tugas baru, perjalanan, & riwayat pengiriman
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <span class="material-symbols-rounded menu-arrow">chevron_right</span>
                </a>
            <?php endif; ?>

            <?php if (canAccessMenu('pickup_form')): ?>
                <a href="request_pickup_form" class="menu-list-item">
                    <div class="menu-left">
                        <div class="menu-icon color-2">
                            <span class="material-symbols-rounded">add_box</span>
                        </div>
                        <div class="menu-info">
                            <h3>Request Pickup</h3>
                            <p>Ajukan penjemputan barang baru ke gudang</p>
                        </div>
                    </div>
                    <span class="material-symbols-rounded menu-arrow">chevron_right</span>
                </a>
            <?php endif; ?>



            <a href="profile" class="menu-list-item">
                <div class="menu-left">
                    <div class="menu-icon color-4">
                        <span class="material-symbols-rounded">account_circle</span>
                    </div>
                    <div class="menu-info">
                        <h3>Profil Saya</h3>
                        <p>Kelola detail akun, keamanan, & keluar dari sistem</p>
                    </div>
                </div>
                <span class="material-symbols-rounded menu-arrow">chevron_right</span>
            </a>
        </div>

        <footer
            style="text-align: center; margin: 2.5rem 0 1rem; font-size: 0.65rem; color: var(--text-sub); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.6;">
            Powered By Dhanielo-Marthinz | IMS @ 2026
        </footer>
    </main>

    <nav class="bottom-nav">
        <a href="mobile_home" class="nav-link active">
            <span class="material-symbols-rounded">home</span>
            <small>Beranda</small>
        </a>

        <a href="logout" class="nav-link">
            <span class="material-symbols-rounded">logout</span>
            <small>Keluar</small>
        </a>
    </nav>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const widgetTime = document.getElementById('widgetTime');
            if (widgetTime) {
                widgetTime.innerText = `${hours}:${minutes}`;
            }

            const widgetDate = document.getElementById('widgetDate');
            if (widgetDate) {
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                widgetDate.innerText = now.toLocaleDateString('id-ID', options);
            }
        }

        function showToast(msg) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%);
                background: rgba(0,0,0,0.8); color: white; padding: 12px 24px;
                border-radius: 100px; font-size: 0.8rem; z-index: 9999;
                font-weight: 600; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                animation: fadeInOut 2s forwards;
            `;
            toast.innerText = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }

        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes fadeInOut {
                0% { opacity: 0; transform: translate(-50%, 20px); }
                15% { opacity: 1; transform: translate(-50%, 0); }
                85% { opacity: 1; transform: translate(-50%, 0); }
                100% { opacity: 0; transform: translate(-50%, -20px); }
            }
        `;
        document.head.appendChild(style);

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>