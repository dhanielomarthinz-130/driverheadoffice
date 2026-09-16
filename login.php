<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TMS | Transport Management System</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <style>
        :root {
            --bg-base: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;

            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #6366f1;
            --accent: #0284c7;

            --border-color: #e2e8f0;
            --border-focused: #6366f1;

            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;

            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #dc2626;

            --success: #10b981;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-base);
            background-image:
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(14, 165, 233, 0.06) 0%, transparent 45%);
            color: var(--text-body);
            padding: 1.5rem;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow:
                0 20px 40px -15px rgba(15, 23, 42, 0.07),
                0 1px 3px rgba(15, 23, 42, 0.04);
            padding: 2.5rem 2.25rem;
            position: relative;
            z-index: 10;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-box {
            width: 56px;
            height: 56px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
        }

        .logo-box img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.5px;
            margin-bottom: 0.35rem;
        }

        .brand-header h1 span {
            color: var(--primary);
        }

        .brand-header p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .error-alert {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 0.75rem;
            animation: form-shake 0.4s ease-in-out;
        }

        @keyframes form-shake {
            0%, 100% { transform: translateX(0); }
            25%, 75% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
        }

        .error-alert .material-symbols-outlined {
            font-size: 20px;
            color: var(--danger-text);
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-group label {
            display: block;
            font-family: 'Outfit', sans-serif;
            font-size: 0.76rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.5rem;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .input-container:hover {
            border-color: #cbd5e1;
        }

        .input-container.focused {
            border-color: #4f46e5;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1), 0 2px 8px rgba(79, 70, 229, 0.05);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
            transition: color 0.2s ease;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-container.focused .input-icon {
            color: #4f46e5;
        }

        input {
            width: 100%;
            height: 48px;
            padding: 0 1rem 0 2.85rem;
            background: transparent;
            border: none;
            outline: none !important;
            box-shadow: none !important;
            color: var(--text-heading);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 14px;
            -webkit-appearance: none;
            appearance: none;
        }

        input:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Fix Chrome/Edge/Safari Browser Autofill Blue Box */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
            transition: background-color 5000s ease-in-out 0s;
            border-radius: 14px;
        }

        input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        input.has-toggle {
            padding-right: 3rem;
        }

        .toggle-pwd-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            outline: none !important;
        }

        .toggle-pwd-btn:hover {
            color: var(--text-heading);
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px -3px rgba(79, 70, 229, 0.45);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .copyright-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
                background-image: none;
            }

            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="brand-header">
            <div class="logo-box">
                <img src="favicon.png" alt="TMS Logo">
            </div>
            <h1>TMS <span>Head Office</span></h1>
            <p>Silakan masuk ke akun TMS Anda</p>
        </div>

        <div id="errorMessage" class="error-alert">
            <span class="material-symbols-outlined">warning</span>
            <span id="errorText">Gagal melakukan verifikasi masuk</span>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="usernameInput">Username</label>
                <div class="input-container" id="usernameWrapper">
                    <span class="material-symbols-outlined input-icon">account_circle</span>
                    <input type="text" id="usernameInput" name="username" placeholder="Masukkan username" required
                        autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="passwordInput">Password</label>
                <div class="input-container" id="passwordWrapper">
                    <span class="material-symbols-outlined input-icon">lock</span>
                    <input type="password" id="passwordInput" class="has-toggle" name="password"
                        placeholder="Masukkan password" required autocomplete="current-password">
                    <button type="button" class="toggle-pwd-btn" onclick="togglePass()"
                        aria-label="Toggle password">
                        <span class="material-symbols-outlined" id="toggleIcon">visibility</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span>Masuk</span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>

        <div class="copyright-footer">
            <div>Transportation Management System</div>
            <div style="margin-top: 0.3rem; font-size: 0.7rem; color: #94a3b8;">
                Powered by &copy; Dhanielo-Marthinz IMS 2026
            </div>
        </div>
    </div>

    <script>
        // ─── Input Styling Focused State ───
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            const container = input.parentElement;
            input.addEventListener('focus', () => {
                container.classList.add('focused');
            });
            input.addEventListener('blur', () => {
                container.classList.remove('focused');
            });
        });

        // ─── Show/Hide Password Toggle ───
        function togglePass() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                pwd.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        // ─── Session Status Checker ───
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const errDiv = document.getElementById('errorMessage');
            const errText = document.getElementById('errorText');
            
            if (urlParams.get('timeout')) {
                errText.textContent = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.';
                errDiv.style.display = 'flex';
                errDiv.style.background = '#fff7ed';
                errDiv.style.border = '1px solid #fdba74';
                errDiv.style.color = '#c2410c';
            } else if (urlParams.get('inactive')) {
                errText.textContent = 'Akun Anda dinonaktifkan oleh Administrator. Silakan hubungi Admin untuk bantuan.';
                errDiv.style.display = 'flex';
                errDiv.style.background = '#fef2f2';
                errDiv.style.border = '1px solid #fecaca';
                errDiv.style.color = '#dc2626';
            } else if (urlParams.get('expired')) {
                errText.textContent = 'Akun Anda telah kadaluarsa. Silakan hubungi Management untuk perpanjangan akun.';
                errDiv.style.display = 'flex';
                errDiv.style.background = '#fef2f2';
                errDiv.style.border = '1px solid #fecaca';
                errDiv.style.color = '#dc2626';
            } else if (urlParams.get('system_expired')) {
                window.location.href = 'expired';
                return;
            }
        });

        // ─── AJAX Form Login Request ───
        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const errDiv = document.getElementById('errorMessage');
            const errText = document.getElementById('errorText');
            const origHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span><span>Memverifikasi...</span>';
            errDiv.style.display = 'none';

            try {
                const res = await fetch('./api.php?action=login', { method: 'POST', body: new FormData(e.target) });

                if (!res.ok) throw new Error('HTTP status ' + res.status);
                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<span class="material-symbols-outlined">check_circle</span><span>Berhasil! Mengalihkan...</span>';
                    btn.style.background = '#10b981';
                    btn.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.3)';

                    setTimeout(() => {
                        if (data.is_web) {
                            window.location.href = './';
                        } else {
                            window.location.href = 'mobile_home';
                        }
                    }, 800);
                } else if (data.system_expired || data.redirect === 'expired') {
                    window.location.href = 'expired';
                } else {
                    errText.textContent = data.message || 'Username atau password salah.';
                    errDiv.style.display = 'flex';

                    // Trigger alert shake animation
                    errDiv.style.animation = 'none';
                    errDiv.offsetHeight; /* trigger reflow */
                    errDiv.style.animation = 'form-shake 0.4s ease-in-out';

                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            } catch (err) {
                console.error(err);
                errText.textContent = 'Koneksi gagal. Periksa jaringan atau server Anda.';
                errDiv.style.display = 'flex';

                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        };

        // Inject helper styles for button loading spinner
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin { 100% { transform: rotate(360deg); } }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>