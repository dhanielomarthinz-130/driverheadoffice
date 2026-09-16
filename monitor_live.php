<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('monitor');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .live-layout {
            display: flex;
            height: calc(100vh - 5.5rem);
            gap: 1.25rem;
        }

        /* ── Side Panel ── */
        .driver-panel {
            width: 380px;
            min-width: 380px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .driver-panel-header {
            padding: 1.25rem 1.25rem 1rem;
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            color: white;
        }

        .driver-panel-header h2 {
            font-size: 1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            margin-bottom: .25rem;
        }

        .driver-panel-header p {
            font-size: 0.73rem;
            opacity: .8;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        /* ── Tabs ── */
        .monitor-tabs {
            display: flex;
            background: rgba(255, 255, 255, .15);
            padding: 4px;
            border-radius: 12px;
            margin-top: .875rem;
            border: 1px solid rgba(255, 255, 255, .2);
        }

        .monitor-tab {
            flex: 1;
            padding: 7px 8px;
            border: none;
            background: transparent;
            font-size: 0.73rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .7);
            cursor: pointer;
            border-radius: 9px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .monitor-tab.active {
            background: white;
            color: #6366f1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        }

        .monitor-tab .material-symbols-outlined {
            font-size: 16px;
        }

        .alert-badge-tab {
            background: #ef4444;
            color: white;
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 8px;
            font-weight: 800;
            display: none;
        }

        /* ── Driver List ── */
        .driver-list-scroll {
            flex: 1;
            overflow-y: auto;
            padding: .875rem;
            scrollbar-width: thin;
        }

        /* Stats mini row */
        .mini-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: .5rem;
            padding: .75rem .875rem;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
        }

        .mini-stat {
            text-align: center;
            padding: .5rem .25rem;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--border);
        }

        .mini-stat-val {
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1;
        }

        .mini-stat-lbl {
            font-size: .6rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: 2px;
        }

        /* Driver Card */
        .driver-card {
            padding: .875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: .5rem;
            cursor: pointer;
            transition: all 0.18s;
            background: var(--card);
            display: flex;
            align-items: center;
            gap: .875rem;
        }

        .driver-card:hover {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
            transform: translateX(2px);
        }

        .driver-card.active-card {
            border-color: #6366f1;
            background: linear-gradient(135deg, rgba(99, 102, 241, .05), rgba(129, 140, 248, .03));
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .driver-avatar.shipping {
            background: #fee2e2;
            color: #ef4444;
        }

        .driver-avatar.idle {
            background: #e0e7ff;
            color: #6366f1;
        }

        .driver-card-body {
            flex: 1;
            min-width: 0;
        }

        .driver-card-name {
            font-weight: 700;
            font-size: .875rem;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .driver-card-dest {
            font-size: .65rem;
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 3px;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .driver-card-dest .material-symbols-outlined {
            font-size: 13px;
        }

        .driver-card-time {
            font-size: .65rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .driver-card-vehicle {
            display: flex;
            flex-direction: column;
            gap: 0;
            margin-top: 5px;
            background: var(--surface-2);
            padding: 4px 10px;
            border-radius: 8px;
            width: fit-content;
            border: 1px solid var(--border);
            line-height: 1.2;
        }

        .driver-card-right {
            text-align: right;
            flex-shrink: 0;
        }

        /* Map */
        #map {
            flex: 1;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            z-index: 10;
        }

        /* Alert Cards */
        .alert-card {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: .625rem;
            animation: slideIn 0.3s ease;
        }

        .alert-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .alert-driver-name {
            font-weight: 800;
            color: #9f1239;
            font-size: .9rem;
        }

        .alert-time {
            background: #fb7185;
            color: white;
            font-size: .65rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 800;
        }

        .btn-wa {
            width: 100%;
            background: #25d366;
            color: white;
            border: none;
            padding: 9px;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
            box-shadow: 0 4px 12px rgba(37, 211, 102, .3);
        }

        .btn-wa:hover {
            background: #128c7e;
            transform: translateY(-2px);
        }

        /* Store Tooltip */
        .store-label {
            background: rgba(255, 255, 255, .95);
            border: 1px solid #6366f1;
            border-radius: 6px;
            padding: 2px 8px;
            font-weight: 700;
            font-size: 10px;
            color: #6366f1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Trail Control Panel ── */
        .trail-control-panel {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(8px);
            border: 1px solid var(--border);
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            color: var(--text);
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .trail-control-panel label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            color: var(--text);
            font-size: 11.5px;
        }

        .trail-control-panel input[type="checkbox"] {
            cursor: pointer;
            width: 15px;
            height: 15px;
            accent-color: #6366f1;
        }

        /* ── Selected Driver Card ── */
        .driver-card.active-card-selected {
            border-color: #6366f1 !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(129, 140, 248, 0.08)) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
            transform: translateX(4px);
        }

        /* ── Animated Marker Ring ── */
        @keyframes marker-pulse {
            0% {
                transform: scale(0.6);
                opacity: 1;
            }

            100% {
                transform: scale(1.45);
                opacity: 0;
            }
        }

        @media (max-width: 900px) {
            .live-layout {
                flex-direction: column;
            }

            .driver-panel {
                width: 100%;
                min-width: unset;
                height: 280px;
            }
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
                    <span class="material-symbols-outlined">location_on</span>
                </div>
                <div>
                    <h1>Live Tracking Driver</h1>
                    <p>Posisi real-time semua driver aktif</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:.5rem; font-size:.8rem; color:var(--text-muted);">
                <span class="pulse-dot"></span>
                Auto-update setiap 5 detik
            </div>
        </div>

        <div class="live-layout">
            <!-- Side Panel -->
            <div class="driver-panel">
                <div class="driver-panel-header">
                    <h2>
                        <span class="material-symbols-outlined">analytics</span>
                        Monitoring Live
                    </h2>
                    <p>
                        <span class="pulse-dot" style="background:rgba(255,255,255,.8);"></span>
                        Lokasi driver diperbarui otomatis
                    </p>
                    <div class="monitor-tabs">
                        <button class="monitor-tab active" id="tab-drivers" onclick="switchTab('drivers')">
                            <span class="material-symbols-outlined">group</span> Driver
                        </button>
                        <button class="monitor-tab" id="tab-alerts" onclick="switchTab('alerts')">
                            <span class="material-symbols-outlined">notifications_active</span>
                            Alert
                            <span id="alertBadge" class="alert-badge-tab">0</span>
                        </button>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="mini-stats" id="miniStats">
                    <div class="mini-stat">
                        <div class="mini-stat-val" id="statTotal" style="color:#6366f1;">—</div>
                        <div class="mini-stat-lbl">Driver</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-val" id="statShipping" style="color:#ef4444;">—</div>
                        <div class="mini-stat-lbl">Keluar WH</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-val" id="statIdle" style="color:#10b981;">—</div>
                        <div class="mini-stat-lbl">Standby</div>
                    </div>
                </div>

                <div id="driverList" class="driver-list-scroll"></div>
                <div id="alertList" class="driver-list-scroll" style="display:none;"></div>
            </div>

            <!-- Map -->
            <div id="map"></div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let map = L.map('map').setView([-6.2088, 106.8456], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let markers = {}, storeMarkers = [], polylines = {};
        let currentAlertCount = 0;
        let selectedDriverId = null;
        let showAllTrails = false;

        // Custom control panel on map for trail options
        const trailControl = L.control({ position: 'topright' });
        trailControl.onAdd = function (map) {
            const div = L.DomUtil.create('div', 'trail-control-panel');
            div.innerHTML = `
                <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; letter-spacing: 0.05em;">Opsi Rute Perjalanan</div>
                <label>
                    <input type="checkbox" id="chkShowAllTrails">
                    Tampilkan Rute Semua Driver
                </label>
                <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 500; border-top: 1px solid var(--border); padding-top: 5px; margin-top: 2px;">
                    *Klik driver untuk melihat rute detail
                </div>
            `;
            // Stop click propagation to avoid triggering map actions
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        trailControl.addTo(map);

        // Handle checkbox change
        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'chkShowAllTrails') {
                showAllTrails = e.target.checked;
                updateTrailVisibility();
            }
        });

        // Driver icon (premium animated truck marker pin)
        const driverIcon = (isShipping, isSelected = false) => L.divIcon({
            className: '',
            html: `
            <div class="driver-marker-wrapper" style="position: relative; display: flex; align-items: center; justify-content: center;">
                <!-- Pulsing outer ring -->
                <div class="marker-pulse" style="
                    position: absolute;
                    width: ${isSelected ? '48px' : '40px'};
                    height: ${isSelected ? '48px' : '40px'};
                    border-radius: 50%;
                    background: ${isShipping ? 'rgba(239, 68, 68, 0.2)' : 'rgba(99, 102, 241, 0.2)'};
                    border: 1px solid ${isShipping ? 'rgba(239, 68, 68, 0.4)' : 'rgba(99, 102, 241, 0.4)'};
                    animation: marker-pulse 1.8s infinite ease-out;
                    pointer-events: none;
                    z-index: -1;
                "></div>
                <!-- Main pin body -->
                <div style="
                    background: ${isShipping ? '#ef4444' : '#6366f1'};
                    color: white;
                    width: ${isSelected ? '38px' : '32px'};
                    height: ${isSelected ? '38px' : '32px'};
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 2px solid white;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.35);
                    transition: all 0.2s ease;
                ">
                    <span class="material-symbols-outlined" style="
                        font-size: ${isSelected ? '22px' : '18px'}; 
                        font-variation-settings: 'FILL' 1;
                    ">
                        local_shipping
                    </span>
                </div>
                <!-- Mini pointer at the bottom of the circular pin -->
                <div style="
                    width: 0;
                    height: 0;
                    border-left: 6px solid transparent;
                    border-right: 6px solid transparent;
                    border-top: 7px solid ${isShipping ? '#ef4444' : '#6366f1'};
                    position: absolute;
                    bottom: -5px;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 10;
                "></div>
            </div>`,
            iconSize: isSelected ? [48, 48] : [40, 40],
            iconAnchor: isSelected ? [24, 29] : [20, 25]
        });

        const storeIcon = L.divIcon({
            className: '',
            html: `<div style="background:white; border-radius:50%; padding:5px; box-shadow:0 3px 8px rgba(0,0,0,.2); border:2px solid #6366f1; display:flex; align-items:center; justify-content:center; color:#6366f1;">
                <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 1;">store</span>
            </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        const warehouseIcon = L.divIcon({
            className: '',
            html: `<div style="background:#fef3c7; border-radius:50%; padding:5px; box-shadow:0 3px 8px rgba(0,0,0,.2); border:2px solid #d97706; display:flex; align-items:center; justify-content:center; color:#d97706;">
                <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 1;">warehouse</span>
            </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        const officeIcon = L.divIcon({
            className: '',
            html: `<div style="background:#f3f4f6; border-radius:50%; padding:5px; box-shadow:0 3px 8px rgba(0,0,0,.2); border:2px solid #4b5563; display:flex; align-items:center; justify-content:center; color:#4b5563;">
                <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 1;">business</span>
            </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        function getLocationIcon(type) {
            if (type === 'warehouse') return warehouseIcon;
            if (type === 'office') return officeIcon;
            return storeIcon;
        }

        async function loadLocations() {
            try {
                const res = await fetch(`${API_URL}?action=get_locations`);
                const locations = await res.json();
                storeMarkers.forEach(m => map.removeLayer(m));
                storeMarkers = [];
                locations.forEach(loc => {
                    if (loc.lat && loc.lng) {
                        const m = L.marker([loc.lat, loc.lng], { icon: getLocationIcon(loc.type) })
                            .bindPopup(`<strong>${loc.name}</strong><br><small>${loc.address || ''}</small>`)
                            .addTo(map);
                        storeMarkers.push(m);
                    }
                });
            } catch (e) { console.error("Gagal memuat lokasi:", e); }
        }

        // Highlight selected driver, updates list & centers map
        function selectDriver(driverId, lat, lng) {
            selectedDriverId = driverId;

            // Focus map
            if (lat && lng) {
                map.setView([lat, lng], 16);
            }

            // Open marker popup
            if (markers[driverId]) {
                markers[driverId].openPopup();
            }

            // Redraw icons for all markers to apply selection state
            Object.keys(markers).forEach(id => {
                const marker = markers[id];
                marker.setIcon(driverIcon(marker._isShipping, id == selectedDriverId));
            });

            // Highlight in panel
            document.querySelectorAll('.driver-card').forEach(c => {
                c.classList.remove('active-card-selected');
            });
            const card = document.getElementById(`driver-card-${driverId}`);
            if (card) {
                card.classList.add('active-card-selected');
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Update trails
            updateTrailVisibility();
        }

        // Handles show/hide of path polylines based on selection & checkbox
        function updateTrailVisibility() {
            Object.keys(polylines).forEach(id => {
                const poly = polylines[id];
                if (!poly) return;

                const isSelected = (id == selectedDriverId);
                const marker = markers[id];
                const isShipping = marker ? marker._isShipping : false;

                if (isSelected) {
                    poly.setStyle({
                        weight: 6,
                        opacity: 0.95,
                        color: isShipping ? '#2563eb' : '#3b82f6',
                        dashArray: null
                    });
                    if (!map.hasLayer(poly)) {
                        poly.addTo(map);
                    }
                } else if (showAllTrails) {
                    poly.setStyle({
                        weight: 3,
                        opacity: 0.45,
                        color: isShipping ? '#60a5fa' : '#93c5fd',
                        dashArray: '6,6'
                    });
                    if (!map.hasLayer(poly)) {
                        poly.addTo(map);
                    }
                } else {
                    if (map.hasLayer(poly)) {
                        map.removeLayer(poly);
                    }
                }
            });
        }

        async function loadDrivers() {
            try {
                const res = await fetch(`${API_URL}?action=get_drivers`);
                let drivers = await res.json();

                // Gunakan tanggal lokal (bukan UTC) agar sesuai timezone Jakarta
                const now = new Date();
                const todayStr = now.getFullYear() + '-' +
                    String(now.getMonth() + 1).padStart(2, '0') + '-' +
                    String(now.getDate()).padStart(2, '0');

                const activeDrivers = drivers.filter(d => {
                    const taskCount = parseInt(d.task_count || 0);
                    const isShipping = taskCount > 0;
                    const hasVehicle = !!d.vehicle_name;
                    const isUpdatedToday = d.last_updated && d.last_updated.split(' ')[0] === todayStr;

                    // Tampilkan driver jika:
                    // 1. Punya tugas hari ini (task_count > 0)
                    // 2. Punya kendaraan aktif DAN lokasi diupdate hari ini
                    return isShipping || (hasVehicle && isUpdatedToday);
                });

                drivers = activeDrivers;

                const list = document.getElementById('driverList');

                // Update mini stats
                const shipping = drivers.filter(d => d.task_count > 0).length;
                document.getElementById('statTotal').textContent = drivers.length;
                document.getElementById('statShipping').textContent = shipping;
                document.getElementById('statIdle').textContent = drivers.length - shipping;

                if (!drivers.length) {
                    list.innerHTML = `<div class="empty-state" style="padding:2rem 1rem;">
                        <span class="material-symbols-outlined">person_off</span>
                        <p>Tidak ada driver aktif.</p>
                    </div>`;

                    // Cleanup all markers
                    Object.keys(markers).forEach(id => map.removeLayer(markers[id]));
                    Object.keys(polylines).forEach(id => map.removeLayer(polylines[id]));
                    markers = {};
                    polylines = {};
                    selectedDriverId = null;
                    return;
                }

                list.innerHTML = '';

                // Sort: Shipping -> Active Vehicle -> Standby
                drivers.sort((a, b) => {
                    const aScore = (parseInt(a.task_count || 0) > 0) ? 2 : (a.vehicle_name ? 1 : 0);
                    const bScore = (parseInt(b.task_count || 0) > 0) ? 2 : (b.vehicle_name ? 1 : 0);
                    return bScore - aScore;
                });

                const updatedDriverIds = new Set();

                drivers.forEach(d => {
                    updatedDriverIds.add(d.user_id.toString());
                    const destinations = d.all_destinations ? d.all_destinations.split('||') : [];
                    const taskCount = parseInt(d.task_count || 0);
                    const isShipping = taskCount > 0; // Hanya tugas HARI INI
                    const hasVehicle = !!d.vehicle_name;
                    const isActive = isShipping || hasVehicle;

                    let destHtml = '';
                    if (isShipping && destinations.length > 0) {
                        // Hanya tampilkan destinasi dari tugas hari ini
                        destHtml = destinations.map(dest => {
                            const [name, status] = dest.split('|');
                            const icon = status === 'in_transit' ? 'local_shipping' : 'near_me';
                            const color = status === 'in_transit' ? 'var(--danger)' : 'var(--primary)';
                            return `<div class="driver-card-dest" style="color:${color};"><span class="material-symbols-outlined" style="font-size:13px;">${icon}</span>${name}</div>`;
                        }).join('');
                    } else if (hasVehicle) {
                        destHtml = `<div class="driver-card-dest" style="color:#f59e0b;"><span class="material-symbols-outlined">move_down</span>OTW Balik / Siaga</div>`;
                    } else {
                        destHtml = `<div class="driver-card-dest" style="color:var(--text-muted);"><span class="material-symbols-outlined">home</span>Standby</div>`;
                    }

                    const card = document.createElement('div');
                    card.className = `driver-card ${isActive ? 'active-card' : ''} ${d.user_id == selectedDriverId ? 'active-card-selected' : ''}`;
                    card.id = `driver-card-${d.user_id}`;
                    card.onclick = () => {
                        if (d.lat && d.lng) {
                            selectDriver(d.user_id, d.lat, d.lng);
                        }
                    };

                    const lastUpd = d.last_updated || '';
                    let updTime = '—';

                    if (lastUpd) {
                        const [datePart, timePart] = lastUpd.split(' ');
                        if (datePart === todayStr) {
                            updTime = timePart.substring(0, 5);
                        } else {
                            const dt = new Date(datePart);
                            updTime = datePart.split('-')[2] + '/' + datePart.split('-')[1];
                        }
                    }

                    card.innerHTML = `
                        <div class="driver-avatar ${isActive ? 'shipping' : 'idle'}">
                            <span class="material-symbols-outlined">${isActive ? 'local_shipping' : 'person'}</span>
                        </div>
                        <div class="driver-card-body">
                            <div class="driver-card-name">${d.driver_name}</div>
                            ${d.vehicle_name ? `<div class="driver-card-vehicle">
                                <div style="font-size:0.7rem; font-weight:800; color:var(--text);">${d.vehicle_name}</div>
                                <div style="font-size:0.6rem; color:var(--primary); font-weight:700;">${d.vehicle_plate}</div>
                             </div>` : ''}
                            <div style="margin-top: 4px;">
                                ${destHtml}
                            </div>
                            <div class="driver-card-time">
                                <span class="material-symbols-outlined" style="font-size:11px; vertical-align:middle;">schedule</span>
                                ${updTime}
                            </div>
                        </div>
                        <div class="driver-card-right">
                            <span class="badge ${isShipping ? 'badge-danger' : (hasVehicle ? 'badge-siaga' : 'badge-success')}" style="font-size:.65rem;">
                                ${isShipping ? 'Keluar WH' : (hasVehicle ? 'Siaga' : 'Standby')}
                            </span>
                        </div>`;
                    list.appendChild(card);

                    // Map marker
                    if (d.lat && d.lng) {
                        const popupHtml = `<div style="font-family:'Inter',sans-serif; min-width:180px; padding:.25rem;">
                            <div style="font-size:1rem; font-weight:800; margin-bottom:.25rem;">${d.driver_name}</div>
                            ${d.vehicle_name ? `<div style="font-size:0.75rem; color:#475569; font-weight:600; margin-bottom:0.25rem;">🚐 ${d.vehicle_name} [${d.vehicle_plate}]</div>` : ''}
                            <div style="font-size:.8rem; font-weight:700; color:${isShipping ? '#ef4444' : (hasVehicle ? '#f59e0b' : '#10b981')}; margin-bottom:.25rem;">
                                ${isShipping ? `🚚 Keluar WH (${taskCount} Tugas)` : (hasVehicle ? '🚚 Siaga / OTW Balik' : '🏠 Standby')}
                            </div>
                            ${destinations.map(dest => {
                            const [name, status] = dest.split('|');
                            return `<div style="font-size:.78rem; color:${status === 'in_transit' ? '#ef4444' : '#6366f1'}; font-weight:600; margin-bottom:2px;">📍 ${name}</div>`;
                        }).join('')}
                            <div style="font-size:.7rem; color:#94a3b8; margin-top:.35rem;">Update: ${d.last_updated || '—'}</div>
                        </div>`;

                        if (markers[d.user_id]) {
                            markers[d.user_id].setLatLng([d.lat, d.lng]).setPopupContent(popupHtml);
                            markers[d.user_id]._isShipping = isActive;
                            // Update icon (retaining current selection scale)
                            markers[d.user_id].setIcon(driverIcon(isActive, d.user_id == selectedDriverId));
                        } else {
                            markers[d.user_id] = L.marker([d.lat, d.lng], { icon: driverIcon(isActive, d.user_id == selectedDriverId) })
                                .bindPopup(popupHtml)
                                .addTo(map);
                            markers[d.user_id]._isShipping = isActive;

                            // Map click event
                            markers[d.user_id].on('click', () => {
                                selectDriver(d.user_id, d.lat, d.lng);
                            });
                        }

                        // Route history polyline
                        fetch(`${API_URL}?action=get_location_history&user_id=${d.user_id}`)
                            .then(r => r.json())
                            .then(history => {
                                if (history && history.length > 1) {
                                    const pts = history.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
                                    if (polylines[d.user_id]) {
                                        polylines[d.user_id].setLatLngs(pts);
                                    } else {
                                        polylines[d.user_id] = L.polyline(pts, {
                                            color: isActive ? '#2563eb' : '#3b82f6',
                                            weight: 3, opacity: 0.6,
                                            dashArray: isActive ? null : '6,4',
                                            lineJoin: 'round'
                                        });
                                    }

                                    // Update trail visibility after loading
                                    updateTrailVisibility();
                                }
                            }).catch(() => { });
                    }
                });

                // Cleanup drivers that are no longer active
                Object.keys(markers).forEach(id => {
                    if (!updatedDriverIds.has(id.toString())) {
                        map.removeLayer(markers[id]);
                        delete markers[id];

                        if (polylines[id]) {
                            map.removeLayer(polylines[id]);
                            delete polylines[id];
                        }

                        if (selectedDriverId == id) {
                            selectedDriverId = null;
                        }
                    }
                });

            } catch (err) { console.error(err); }
        }

        async function loadGpsAlerts() {
            try {
                const res = await fetch(`${API_URL}?action=get_gps_alerts`);
                const alerts = await res.json();
                currentAlertCount = alerts.length;
                const list = document.getElementById('alertList');
                const badge = document.getElementById('alertBadge');

                if (currentAlertCount > 0) {
                    badge.textContent = currentAlertCount;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }

                list.innerHTML = '';
                if (alerts.length > 0) {
                    alerts.forEach(a => {
                        const card = document.createElement('div');
                        card.className = 'alert-card';
                        card.innerHTML = `
                            <div class="alert-card-top">
                                <div class="alert-driver-name">
                                    <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; color:#ef4444;">warning</span>
                                    ${a.driver_name}
                                </div>
                                <div class="alert-time">${a.minutes_inactive} Menit Off</div>
                            </div>
                            <div style="font-size:.74rem; color:#b91c1c; margin-bottom:10px; line-height:1.4;">
                                GPS tidak aktif selama lebih dari 30 menit saat pengiriman berlangsung.
                            </div>
                            <button class="btn-wa" onclick="openWhatsApp('${a.phone_number || ''}', '${a.driver_name}')">
                                <span class="material-symbols-outlined" style="font-size:18px;">chat</span>
                                Hubungi via WhatsApp
                            </button>`;
                        list.appendChild(card);
                    });
                } else {
                    list.innerHTML = `<div class="empty-state" style="padding:2rem 1rem;">
                        <span class="material-symbols-outlined" style="color:#10b981;">check_circle</span>
                        <p style="color:#059669;">Semua GPS aktif. Tidak ada kendala.</p>
                    </div>`;
                }
            } catch (e) { console.error(e); }
        }

        function switchTab(tab) {
            const driverList = document.getElementById('driverList');
            const alertList = document.getElementById('alertList');
            const miniStats = document.getElementById('miniStats');

            document.querySelectorAll('.monitor-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');

            if (tab === 'drivers') {
                driverList.style.display = 'block';
                alertList.style.display = 'none';
                miniStats.style.display = 'grid';
            } else {
                driverList.style.display = 'none';
                alertList.style.display = 'block';
                miniStats.style.display = 'none';
                localStorage.setItem('gps_alerts_seen', currentAlertCount);
            }
        }

        function openWhatsApp(phone, name) {
            if (!phone) { alert("Nomor WA tidak tersedia."); return; }
            let p = phone.replace(/\D/g, '');
            if (p.startsWith('0')) p = '62' + p.substring(1);
            const msg = `Halo ${name}, GPS Anda terdeteksi tidak aktif selama lebih dari 30 menit. Mohon aktifkan kembali GPS untuk memantau pengiriman.`;
            window.open(`https://api.whatsapp.com/send?phone=${p}&text=${encodeURIComponent(msg)}`, '_blank');
        }

        loadLocations();
        loadDrivers();
        loadGpsAlerts();
        setInterval(loadDrivers, 5000);
        setInterval(loadGpsAlerts, 30000);
        setTimeout(() => map.invalidateSize(), 400);
    </script>
    <?php include_once __DIR__ . '/profile_modal.php'; ?>
</body>

</html>