<?php
require_once 'auth_check.php';
checkLogin();
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Saya | TMS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #111827;
            --text-sub: #6b7280;
            --danger: #ef4444;
            --success: #10b981;
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
            padding-bottom: 2rem;
        }

        .header {
            background: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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

        .content {
            padding: 1.5rem;
        }

        .profile-card {
            background: white;
            border-radius: 2rem;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary);
            color: white;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 32px;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .profile-role {
            display: inline-block;
            background: #eef2ff;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.3rem 0.8rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-section {
            background: white;
            border-radius: 1.5rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
        }

        .info-item:not(:last-child) {
            border-bottom: 1px solid #f1f5f9;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #f8fafc;
            color: var(--text-sub);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-detail p {
            font-size: 0.75rem;
            color: var(--text-sub);
            font-weight: 500;
        }

        .info-detail h4 {
            font-size: 0.95rem;
            font-weight: 700;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--text-sub);
            padding-left: 0.5rem;
        }

        /* Form Style */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-sub);
            margin-bottom: 0.5rem;
        }

        .input-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.95rem;
            background: #f8fafc;
            outline: none;
            transition: 0.2s;
        }

        .input-control:focus {
            border-color: var(--primary);
            background: white;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 1.25rem;
            font-size: 0.95rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
            cursor: pointer;
        }

        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 1rem 1.5rem;
            background: #1e293b;
            color: white;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: none;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>

    <header class="header">
        <a href="mobile_home.php" class="back-btn">
            <span class="material-symbols-rounded">arrow_back</span>
        </a>
        <h1>Profil Saya</h1>
    </header>

    <div class="content">
        <div class="profile-card" id="profileHeader">
            <div class="profile-avatar">
                <span class="material-symbols-rounded">person</span>
            </div>
            <h2 class="profile-name" id="dispName">...</h2>
            <span class="profile-role" id="dispRole">...</span>
        </div>

        <h3 class="section-title">INFORMASI AKUN</h3>
        <div class="info-section">
            <div class="info-item">
                <div class="info-icon"><span class="material-symbols-rounded">alternate_email</span></div>
                <div class="info-detail">
                    <p>Username</p>
                    <h4 id="dispUsername">...</h4>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><span class="material-symbols-rounded">call</span></div>
                <div class="info-detail">
                    <p>No. WhatsApp</p>
                    <h4 id="dispPhone">...</h4>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><span class="material-symbols-rounded">calendar_today</span></div>
                <div class="info-detail">
                    <p>Bergabung Sejak</p>
                    <h4 id="dispJoined">...</h4>
                </div>
            </div>
        </div>

        <h3 class="section-title">KEAMANAN</h3>
        <div class="info-section">
            <form id="passwordForm">
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="old_password" class="input-control" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" class="input-control" required
                        placeholder="Minimal 6 karakter">
                </div>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="material-symbols-rounded">lock_reset</span>
                    Ganti Password
                </button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const API_URL = 'api.php';

        function showToast(msg, color = '#1e293b') {
            const t = document.getElementById('toast');
            t.innerText = msg; t.style.background = color; t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 3000);
        }

        async function loadProfile() {
            try {
                const res = await fetch(`${API_URL}?action=get_my_profile`);
                const data = await res.json();

                document.getElementById('dispName').innerText = data.name;
                document.getElementById('dispRole').innerText = data.role.replace('_', ' ').toUpperCase();
                document.getElementById('dispUsername').innerText = '@' + data.username;
                document.getElementById('dispPhone').innerText = data.phone_number || '-';
                document.getElementById('dispJoined').innerText = data.created_at ? data.created_at.split(' ')[0] : '-';
            } catch (err) { console.error(err); }
        }

        document.getElementById('passwordForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const orig = btn.innerHTML;
            btn.disabled = true; btn.innerText = 'Memproses...';

            try {
                const res = await fetch(`${API_URL}?action=change_my_password`, {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Password berhasil diubah!', '#10b981');
                    e.target.reset();
                } else {
                    showToast('Gagal: ' + data.error, '#ef4444');
                }
            } catch (err) { showToast('Gagal terhubung ke server', '#ef4444'); }
            btn.disabled = false; btn.innerHTML = orig;
        };

        loadProfile();
    </script>
</body>

</html>