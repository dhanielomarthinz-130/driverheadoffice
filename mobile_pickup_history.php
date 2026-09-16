<?php
require_once 'auth_check.php';
checkLogin();
// Anyone with pickup_form or pickup_request should see this
if (!canAccessMenu('pickup_form') && !canAccessMenu('pickup_request')) {
    header("Location: mobile_home.php");
    exit();
}
$username = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Request | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #eef2ff;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #111827;
            --text-sub: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            padding-bottom: 90px;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border-radius: 12px;
            color: var(--text);
            text-decoration: none;
        }

        .header h1 {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .container {
            padding: 1rem;
        }

        .search-box {
            background: white;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.95rem;
            background: transparent;
            font-family: inherit;
        }

        .request-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .sj-label {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--primary);
        }

        .date-label {
            font-size: 0.7rem;
            color: var(--text-sub);
            font-weight: 500;
        }

        .badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .badge-approved {
            background: #ecfdf5;
            color: #047857;
        }

        .badge-transit {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-completed {
            background: #f0fdf4;
            color: #15803d;
        }

        .route-info {
            display: flex;
            gap: 1rem;
            position: relative;
            margin-bottom: 1rem;
        }

        .route-line {
            position: absolute;
            left: 7px;
            top: 12px;
            bottom: 12px;
            width: 2px;
            background: #f1f5f9;
            border-radius: 1px;
        }

        .route-dots {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            z-index: 1;
            padding-top: 4px;
        }

        .dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 1px #e2e8f0;
        }

        .dot.origin {
            background: var(--primary);
        }

        .dot.dest {
            background: var(--success);
        }

        .route-text {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
        }

        .step-label {
            font-size: 0.6rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
        }

        .step-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f8fafc;
        }

        .info-pill {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-sub);
            background: #f8fafc;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .photo-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            text-decoration: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-sub);
        }

        .empty-state .material-symbols-rounded {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            height: 75px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 1rem;
            border-top-left-radius: 2rem;
            border-top-right-radius: 2rem;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #94a3b8;
            font-size: 0.65rem;
            font-weight: 600;
            gap: 4px;
        }

        .nav-link.active {
            color: var(--primary);
        }

        .nav-link.active .material-symbols-rounded {
            font-variation-settings: 'FILL' 1;
        }

        /* Modal Overlay for Images */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(8px);
        }

        .modal.show {
            display: flex;
        }

        .modal img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 1rem;
        }

        .close-modal {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: white;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-left">
            <a href="mobile_home.php" class="back-btn">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <h1>Riwayat Request</h1>
        </div>
        <div id="filterTag" class="badge" style="background:var(--primary-light); color:var(--primary);">
            HARI INI</div>
    </header>

    <div class="container">
        <div class="search-box">
            <span class="material-symbols-rounded" style="color:var(--text-sub);">search</span>
            <input type="text" id="searchInput" placeholder="Cari No. SJ atau Lokasi..." onkeyup="filterData()">
        </div>

        <div id="historyList">
            <div class="empty-state">
                <span class="material-symbols-rounded rotating">autorenew</span>
                <p>Memuat data...</p>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="mobile_home.php" class="nav-link">
            <span class="material-symbols-rounded">home</span><span>Beranda</span>
        </a>
        <a href="request_pickup_form.php" class="nav-link">
            <span class="material-symbols-rounded"
                style="background:var(--primary); color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-top:-20px; border:4px solid var(--bg);">add</span>
            <span>Baru</span>
        </a>
        <a href="mobile_pickup_history.php" class="nav-link active">
            <span class="material-symbols-rounded">history</span><span>Riwayat</span>
        </a>
    </nav>

    <div id="imageModal" class="modal" onclick="this.classList.remove('show')">
        <span class="material-symbols-rounded close-modal">close</span>
        <img id="modalImg" src="">
    </div>

    <script>
        let allData = [];
        async function loadHistory() {
            try {
                // Get local date in YYYY-MM-DD format
                const today = new Date().toLocaleDateString('en-CA');
                const query = `?action=get_pickup_requests&date_from=${today}&date_to=${today}`;
                
                const res = await fetch('api.php' + query);
                allData = await res.json();
                renderList(allData);
            } catch (err) {
                console.error(err);
                document.getElementById('historyList').innerHTML = '<p style="text-align:center; padding:2rem; color:var(--danger);">Gagal memuat data.</p>';
            }
        }

        function renderList(data) {
            const list = document.getElementById('historyList');
            if (!data.length) {
                list.innerHTML = '<div class="empty-state"><span class="material-symbols-rounded">history_off</span><p>Tidak ada riwayat.</p></div>';
                return;
            }

            list.innerHTML = data.map(item => {
                const statusCls = getStatusCls(item.status);
                const statusTxt = getStatusTxt(item.status);
                const dt = new Date(item.created_at);
                const dateStr = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                const timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                return `
                    <div class="request-card">
                        <div class="card-header">
                            <div>
                                <div class="sj-label">${item.surat_jalan}</div>
                                <div class="date-label">${dateStr} • ${timeStr}</div>
                            </div>
                            <span class="badge ${statusCls}">${statusTxt}</span>
                        </div>
                        <div class="route-info">
                            <div class="route-line"></div>
                            <div class="route-dots"><div class="dot origin"></div><div class="dot dest"></div></div>
                            <div class="route-text">
                                <div class="step"><span class="step-label">DARI</span><span class="step-name">${item.origin_name}</span></div>
                                <div class="step"><span class="step-label">KE</span><span class="step-name">${item.destination_name}</span></div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div style="display:flex; gap:8px;">
                                <div class="info-pill"><span class="material-symbols-rounded" style="font-size:14px;">package_2</span>${item.total_koli}</div>
                                <div class="info-pill"><span class="material-symbols-rounded" style="font-size:14px;">person</span>${item.requester_name || 'User'}</div>
                            </div>
                            <div style="display:flex; gap:6px;">
                                ${item.surat_jalan_file ? `<button class="photo-btn" onclick="showImg('uploads/${parsePhoto(item.surat_jalan_file)}')"><span class="material-symbols-rounded" style="font-size:20px;">image</span></button>` : ''}
                                ${item.goods_file ? `<button class="photo-btn" onclick="showImg('uploads/${parsePhoto(item.goods_file)}')" style="background:#ecfdf5; color:#059669;"><span class="material-symbols-rounded" style="font-size:20px;">inventory_2</span></button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function parsePhoto(json) {
            try {
                const p = JSON.parse(json);
                return Array.isArray(p) ? p[0] : p;
            } catch (e) { return json; }
        }

        function getStatusCls(s) {
            if (s === 'pending') return 'badge-pending';
            if (s === 'approved') return 'badge-approved';
            if (s === 'completed') return 'badge-completed';
            return 'badge-transit';
        }

        function getStatusTxt(s) {
            if (s === 'pending') return 'Menunggu';
            if (s === 'approved') return 'Disetujui';
            if (s === 'completed') return 'Selesai';
            return s.toUpperCase();
        }

        function showImg(url) {
            const m = document.getElementById('imageModal');
            document.getElementById('modalImg').src = url;
            m.classList.add('show');
        }

        function filterData() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allData.filter(d =>
                d.surat_jalan.toLowerCase().includes(q) ||
                d.origin_name.toLowerCase().includes(q) ||
                d.destination_name.toLowerCase().includes(q)
            );
            renderList(filtered);
        }

        loadHistory();
    </script>
</body>

</html>