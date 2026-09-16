<?php
require_once 'auth_check.php';
logActivity('LOGOUT', 'User logout');
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <style>
        :root { --primary: #4f46e5; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background: var(--bg); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            overflow: hidden; 
        }
        
        .container { text-align: center; animation: zoomIn 0.5s ease-out; }
        
        .icon-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            color: #ef4444;
        }
        
        h1 { font-size: 1.5rem; color: #1e293b; margin-bottom: 0.5rem; }
        p { color: #64748b; font-size: 1rem; }
        
        .loader {
            width: 30px;
            height: 30px;
            border: 3px solid #e2e8f0;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            margin: 2rem auto 0;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-circle">
            <span class="material-symbols-outlined" style="font-size: 48px;">logout</span>
        </div>
        <h1>Keluar dari Sistem</h1>
        <p>Mohon tunggu sebentar, sesi Anda sedang ditutup...</p>
        <div class="loader"></div>
    </div>

    <script>
        // Redirect after 1.5 seconds
        const urlParams = new URLSearchParams(window.location.search);
        const timeout = urlParams.get('timeout');
        setTimeout(() => {
            window.location.href = 'login' + (timeout ? '?timeout=1' : '');
        }, 1500);
    </script>
</body>
</html>
