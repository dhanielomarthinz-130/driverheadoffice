<header class="topbar">
    <div class="topbar-content">
        <!-- User Info -->
        <div class="topbar-user" onclick="openProfileModal()" title="Klik untuk lihat profil & ganti password">
            <div class="topbar-avatar">
                <span class="material-symbols-outlined">person</span>
            </div>
            <div class="topbar-info">
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <small><?php echo ucwords(str_replace('_', ' ', $role)); ?></small>
            </div>
        </div>

        <div class="topbar-divider"></div>

        <!-- Logout Button -->
        <a href="logout" class="btn-logout-top">
            <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
        </a>
    </div>
</header>