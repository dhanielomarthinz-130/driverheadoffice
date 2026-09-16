<?php
require_once 'auth_check.php';
checkLogin();
// Task notes is personal, every logged-in user can access it
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Task | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        /* ── Layout ── */
        .task-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1.5rem;
            min-height: calc(100vh - 180px);
        }

        @media (max-width: 1024px) {
            .task-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ── Calendar Panel ── */
        .calendar-panel {
            background: var(--card);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            position: sticky;
            top: 90px;
            height: fit-content;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .calendar-header h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .calendar-nav {
            display: flex;
            gap: 0.25rem;
        }

        .calendar-nav button {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
            color: var(--text-muted);
        }

        .calendar-nav button:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .calendar-day-name {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            padding: 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day {
            position: relative;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            cursor: pointer;
            transition: all .2s cubic-bezier(.4, 0, .2, 1);
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text);
            background: transparent;
            border: 2px solid transparent;
        }

        .calendar-day:hover {
            background: var(--surface);
            transform: scale(1.08);
        }

        .calendar-day.other-month {
            color: var(--text-muted);
            opacity: 0.35;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        .calendar-day.selected {
            border-color: var(--primary);
            background: #eef2ff;
            font-weight: 700;
            color: var(--primary);
        }

        .calendar-day.today.selected {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
            border-color: white;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.5);
        }

        .calendar-day .dot-row {
            position: absolute;
            bottom: 3px;
            display: flex;
            gap: 2px;
        }

        .calendar-day .task-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--primary);
        }

        .calendar-day.today .task-dot {
            background: white;
        }

        .calendar-day .task-dot.done {
            background: #10b981;
        }

        .calendar-day .task-dot.high {
            background: #ef4444;
        }

        /* ── Today Summary Card ── */
        .today-summary {
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #f0f9ff, #eff6ff);
            border: 1px solid #bfdbfe;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .today-summary .ts-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .today-summary .ts-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .today-summary .ts-text strong {
            color: var(--text);
            font-size: 1rem;
            display: block;
        }

        /* ── Task List Panel ── */
        .task-panel {
            background: var(--card);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .task-panel-header {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .task-panel-header .tph-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .task-panel-header .tph-left .date-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .task-panel-header .tph-left .date-icon .day-num {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1;
        }

        .task-panel-header .tph-left .date-icon .day-label {
            font-size: 0.55rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
        }

        .task-panel-header .tph-left h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text);
        }

        .task-panel-header .tph-left h2 small {
            display: block;
            font-weight: 400;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .task-list-body {
            flex: 1;
            padding: 1rem 1.25rem;
            overflow-y: auto;
            max-height: calc(100vh - 320px);
        }

        /* ── Task Item Card ── */
        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 14px;
            border: 1px solid var(--border);
            margin-bottom: 0.75rem;
            transition: all .25s cubic-bezier(.4, 0, .2, 1);
            background: var(--surface);
            position: relative;
            overflow: hidden;
        }

        .task-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 14px 0 0 14px;
        }

        .task-item.priority-high::before {
            background: linear-gradient(180deg, #ef4444, #f87171);
        }

        .task-item.priority-medium::before {
            background: linear-gradient(180deg, #f59e0b, #fbbf24);
        }

        .task-item.priority-low::before {
            background: linear-gradient(180deg, #10b981, #34d399);
        }

        .task-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--primary-light, #c7d2fe);
        }

        .task-item.done {
            opacity: 0.55;
        }

        .task-item.done .task-title {
            text-decoration: line-through;
        }

        .task-checkbox {
            margin-top: 2px;
            flex-shrink: 0;
        }

        .task-checkbox input[type="checkbox"] {
            display: none;
        }

        .task-checkbox label {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
            background: white;
        }

        .task-checkbox input:checked+label {
            background: linear-gradient(135deg, #10b981, #34d399);
            border-color: #10b981;
            color: white;
        }

        .task-checkbox label .material-symbols-outlined {
            font-size: 16px;
            opacity: 0;
            transition: .2s;
        }

        .task-checkbox input:checked+label .material-symbols-outlined {
            opacity: 1;
            color: white;
        }

        .task-content {
            flex: 1;
            min-width: 0;
        }

        .task-title {
            font-weight: 600;
            font-size: 0.92rem;
            color: var(--text);
            margin-bottom: 2px;
            word-break: break-word;
        }

        .task-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.5;
            word-break: break-word;
        }

        .task-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .priority-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-badge.high {
            background: #fef2f2;
            color: #ef4444;
        }

        .priority-badge.medium {
            background: #fffbeb;
            color: #f59e0b;
        }

        .priority-badge.low {
            background: #ecfdf5;
            color: #10b981;
        }

        .task-time-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            background: #eff6ff;
            color: #3b82f6;
            letter-spacing: 0.3px;
        }

        .task-actions {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
            opacity: 0;
            transition: .2s;
        }

        .task-item:hover .task-actions {
            opacity: 1;
        }

        .task-actions button {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            transition: .2s;
        }

        .task-actions button:hover {
            background: var(--surface);
            color: var(--primary);
        }

        .task-actions button.danger:hover {
            background: #fef2f2;
            color: #ef4444;
        }

        /* ── Empty State ── */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state .material-symbols-outlined {
            font-size: 64px;
            opacity: 0.25;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
        }

        .empty-state .add-hint {
            margin-top: 1rem;
        }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
            padding: 1.5rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: var(--card);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalSlideIn .3s cubic-bezier(.34, 1.56, .64, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: var(--surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            transition: .2s;
        }

        .modal-close:hover {
            background: #fef2f2;
            color: #ef4444;
        }

        .modal-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        .modal-title .material-symbols-outlined {
            font-size: 28px;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 0.9rem;
            color: var(--text);
            transition: .2s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Priority Selector */
        .priority-selector {
            display: flex;
            gap: 0.5rem;
        }

        .priority-selector input[type="radio"] {
            display: none;
        }

        .priority-selector label {
            flex: 1;
            padding: 0.6rem 0.75rem;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
        }

        .priority-selector label .material-symbols-outlined {
            font-size: 16px;
        }

        .priority-selector .p-low:checked+label {
            background: #ecfdf5;
            border-color: #10b981;
            color: #10b981;
        }

        .priority-selector .p-medium:checked+label {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .priority-selector .p-high:checked+label {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .task-layout {
                grid-template-columns: 1fr;
            }

            .calendar-panel {
                position: static;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">
                    <span class="material-symbols-outlined">checklist</span>
                </div>
                <div>
                    <h1>Catatan Task</h1>
                    <p>Perencanaan dan catatan tugas pribadi Anda</p>
                </div>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <span class="material-symbols-outlined">add</span>
                Tambah Catatan
            </button>
        </div>

        <div class="task-layout">
            <!-- LEFT: Calendar -->
            <div class="calendar-panel">
                <div class="calendar-header">
                    <h3 id="calendarTitle">Mei 2026</h3>
                    <div class="calendar-nav">
                        <button onclick="changeMonth(-1)" title="Bulan sebelumnya">
                            <span class="material-symbols-outlined" style="font-size:20px;">chevron_left</span>
                        </button>
                        <button onclick="goToday()" title="Hari ini"
                            style="width:auto; padding:0 12px; font-size:0.75rem; font-weight:700;">
                            Hari Ini
                        </button>
                        <button onclick="changeMonth(1)" title="Bulan berikutnya">
                            <span class="material-symbols-outlined" style="font-size:20px;">chevron_right</span>
                        </button>
                    </div>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>

                <div class="today-summary" id="todaySummary">
                    <div class="ts-icon">
                        <span class="material-symbols-outlined">notifications_active</span>
                    </div>
                    <div class="ts-text">
                        <strong id="todayCountText">-</strong>
                        <span>catatan aktif hari ini</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Task List -->
            <div class="task-panel">
                <div class="task-panel-header">
                    <div class="tph-left">
                        <div class="date-icon" id="selectedDateIcon">
                            <span class="day-num" id="selectedDay">29</span>
                            <span class="day-label" id="selectedDayLabel">Mei</span>
                        </div>
                        <h2 id="selectedDateTitle">
                            Hari Ini
                            <small id="selectedDateSub">Kamis, 29 Mei 2026</small>
                        </h2>
                    </div>
                </div>
                <div class="task-list-body" id="taskListBody">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">event_note</span>
                        <p>Memuat catatan...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div id="taskModal" class="modal-overlay" onclick="if(event.target===this)closeModal()" style="display: none;">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-title" id="modalTitle">
                <span class="material-symbols-outlined">add_circle</span>
                Tambah Catatan Baru
            </div>
            <form id="taskForm">
                <input type="hidden" name="id" id="formId">
                <div class="form-group">
                    <label>Judul Catatan *</label>
                    <input type="text" name="title" id="formTitle" required placeholder="Contoh: Meeting review bulanan">
                </div>
                <div class="form-group">
                    <label>Deskripsi / Detail</label>
                    <textarea name="description" id="formDescription" rows="3"
                        placeholder="Detail catatan (opsional)..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="note_date" id="formDate" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Alarm</label>
                        <input type="time" name="note_time" id="formTime" placeholder="HH:MM">
                    </div>
                </div>
                <div class="form-group">
                    <label>Prioritas</label>
                    <div class="priority-selector">
                        <input type="radio" name="priority" value="low" id="pLow" class="p-low">
                        <label for="pLow">
                            <span class="material-symbols-outlined">arrow_downward</span> Low
                        </label>
                        <input type="radio" name="priority" value="medium" id="pMedium" class="p-medium" checked>
                        <label for="pMedium">
                            <span class="material-symbols-outlined">remove</span> Medium
                        </label>
                        <input type="radio" name="priority" value="high" id="pHigh" class="p-high">
                        <label for="pHigh">
                            <span class="material-symbols-outlined">arrow_upward</span> High
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width:100%; justify-content:center; margin-top:0.5rem; padding:0.85rem;">
                    <span class="material-symbols-outlined">save</span>
                    <span id="modalSubmitText">Simpan Catatan</span>
                </button>
            </form>
        </div>
    </div>
    <script>
        const API = 'api.php';
        const MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const DAYS_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        const DAYS_FULL = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        let currentYear, currentMonth;
        let selectedDate = null;
        let monthNotes = [];
        let isSubmitting = false;

        const today = new Date();
        const todayStr = formatDate(today);

        function formatDate(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function init() {
            currentYear = today.getFullYear();
            currentMonth = today.getMonth();
            selectedDate = todayStr;
            renderCalendar();
            loadMonthNotes();
        }

        // ===== CALENDAR RENDERING =====
        function renderCalendar() {
            document.getElementById('calendarTitle').textContent = MONTHS_ID[currentMonth] + ' ' + currentYear;

            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            // Day names
            DAYS_SHORT.forEach(d => {
                grid.innerHTML += `<div class="calendar-day-name">${d}</div>`;
            });

            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const daysInPrev = new Date(currentYear, currentMonth, 0).getDate();

            // Previous month's trailing days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrev - i;
                grid.innerHTML += `<div class="calendar-day other-month">${day}</div>`;
            }

            // Current month days
            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                const isToday = dateStr === todayStr;
                const isSelected = dateStr === selectedDate;
                const dayNotes = monthNotes.filter(n => n.note_date === dateStr);
                
                let classes = 'calendar-day';
                if (isToday) classes += ' today';
                if (isSelected) classes += ' selected';

                let dots = '';
                if (dayNotes.length > 0) {
                    const dotItems = dayNotes.slice(0, 3).map(n => {
                        let dotClass = 'task-dot';
                        if (n.is_done == 1) dotClass += ' done';
                        else if (n.priority === 'high') dotClass += ' high';
                        return `<span class="${dotClass}"></span>`;
                    }).join('');
                    dots = `<div class="dot-row">${dotItems}</div>`;
                }

                grid.innerHTML += `<div class="${classes}" onclick="selectDate('${dateStr}')">${d}${dots}</div>`;
            }

            // Next month's leading days
            const totalCells = grid.children.length;
            const remaining = 7 - ((totalCells - 7) % 7);
            if (remaining < 7) {
                for (let i = 1; i <= remaining; i++) {
                    grid.innerHTML += `<div class="calendar-day other-month">${i}</div>`;
                }
            }
        }

        async function loadMonthNotes() {
            const monthStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0');
            try {
                const res = await fetch(`${API}?action=get_task_notes&month=${monthStr}`);
                monthNotes = await res.json();
                renderCalendar();
                loadDateTasks(selectedDate);
                updateTodaySummary();
            } catch (e) {
                console.error('Error loading notes:', e);
            }
        }

        function changeMonth(delta) {
            currentMonth += delta;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar();
            loadMonthNotes();
        }

        function goToday() {
            currentYear = today.getFullYear();
            currentMonth = today.getMonth();
            selectedDate = todayStr;
            renderCalendar();
            loadMonthNotes();
        }

        function selectDate(dateStr) {
            selectedDate = dateStr;
            renderCalendar();
            loadDateTasks(dateStr);
        }

        // ===== TASK LIST =====
        function loadDateTasks(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            const dayNum = d.getDate();
            const monthShort = MONTHS_ID[d.getMonth()].substring(0, 3);
            const dayName = DAYS_FULL[d.getDay()];
            const fullDate = dayName + ', ' + dayNum + ' ' + MONTHS_ID[d.getMonth()] + ' ' + d.getFullYear();

            const elDay = document.getElementById('selectedDay');
            if (elDay) elDay.textContent = dayNum;
            const elLabel = document.getElementById('selectedDayLabel');
            if (elLabel) elLabel.textContent = monthShort;
            const elSub = document.getElementById('selectedDateSub');
            if (elSub) elSub.textContent = fullDate;

            const elTitle = document.getElementById('selectedDateTitle');
            if (elTitle) {
                if (dateStr === todayStr) {
                    elTitle.innerHTML = 'Hari Ini<small>' + fullDate + '</small>';
                } else {
                    elTitle.innerHTML = dayName + '<small>' + fullDate + '</small>';
                }
            }

            const tasks = monthNotes.filter(n => n.note_date === dateStr);
            renderTaskList(tasks);
        }

        function renderTaskList(tasks) {
            const body = document.getElementById('taskListBody');
            if (!body) return;

            if (tasks.length === 0) {
                body.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">event_note</span>
                        <p>Belum ada catatan pada tanggal ini</p>
                    </div>`;
                return;
            }

            body.innerHTML = tasks.map(t => {
                const doneClass = t.is_done == 1 ? ' done' : '';
                const checked = t.is_done == 1 ? 'checked' : '';
                const priorityLabel = { low: 'Rendah', medium: 'Sedang', high: 'Tinggi' };
                const timeDisplay = t.note_time ? `<span class="task-time-badge"><span class="material-symbols-outlined" style="font-size:13px;">schedule</span> ${t.note_time.substring(0,5)}</span>` : '';

                return `
                <div class="task-item priority-${t.priority}${doneClass}" id="task-${t.id}">
                    <div class="task-checkbox">
                        <input type="checkbox" id="chk-${t.id}" ${checked} onchange="toggleTask(${t.id})">
                        <label for="chk-${t.id}">
                            <span class="material-symbols-outlined">check</span>
                        </label>
                    </div>
                    <div class="task-content">
                        <div class="task-title">${escapeHtml(t.title)}</div>
                        ${t.description ? `<div class="task-desc">${escapeHtml(t.description)}</div>` : ''}
                        <div class="task-meta">
                            <span class="priority-badge ${t.priority}">${priorityLabel[t.priority] || t.priority}</span>
                            ${timeDisplay}
                        </div>
                    </div>
                    <div class="task-actions">
                        <button onclick="openEditModal(${t.id})" title="Edit">
                            <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                        </button>
                        <button class="danger" onclick="deleteTask(${t.id})" title="Hapus">
                            <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }

        // ===== MODAL =====
        function openAddModal() {
            const elId = document.getElementById('formId');
            if (elId) elId.value = '';
            const elTitle = document.getElementById('formTitle');
            if (elTitle) elTitle.value = '';
            const elDesc = document.getElementById('formDescription');
            if (elDesc) elDesc.value = '';
            const elDate = document.getElementById('formDate');
            if (elDate) elDate.value = selectedDate || todayStr;
            const elTime = document.getElementById('formTime');
            if (elTime) elTime.value = '';
            const elMedium = document.getElementById('pMedium');
            if (elMedium) elMedium.checked = true;
            
            const elModalTitle = document.getElementById('modalTitle');
            if (elModalTitle) elModalTitle.innerHTML = '<span class="material-symbols-outlined">add_circle</span> Tambah Catatan Baru';
            const elSubmitText = document.getElementById('modalSubmitText');
            if (elSubmitText) elSubmitText.textContent = 'Simpan Catatan';

            const m = document.getElementById('taskModal');
            if (m) {
                m.style.display = 'flex';
                setTimeout(() => m.classList.add('show'), 10);
                setTimeout(() => {
                    const t = document.getElementById('formTitle');
                    if (t) t.focus();
                }, 300);
            }
        }

        function openEditModal(id) {
            const note = monthNotes.find(n => n.id == id);
            if (!note) return;

            const elId = document.getElementById('formId');
            if (elId) elId.value = note.id;
            const elTitle = document.getElementById('formTitle');
            if (elTitle) elTitle.value = note.title;
            const elDesc = document.getElementById('formDescription');
            if (elDesc) elDesc.value = note.description || '';
            const elDate = document.getElementById('formDate');
            if (elDate) elDate.value = note.note_date;
            const elTime = document.getElementById('formTime');
            if (elTime) elTime.value = note.note_time ? note.note_time.substring(0, 5) : '';
            
            const elRadio = document.querySelector(`input[name="priority"][value="${note.priority}"]`);
            if (elRadio) elRadio.checked = true;
            
            const elModalTitle = document.getElementById('modalTitle');
            if (elModalTitle) elModalTitle.innerHTML = '<span class="material-symbols-outlined">edit_note</span> Edit Catatan';
            const elSubmitText = document.getElementById('modalSubmitText');
            if (elSubmitText) elSubmitText.textContent = 'Simpan Perubahan';

            const m = document.getElementById('taskModal');
            if (m) {
                m.style.display = 'flex';
                setTimeout(() => m.classList.add('show'), 10);
            }
        }

        function closeModal() {
            const m = document.getElementById('taskModal');
            if (m) {
                m.classList.remove('show');
                setTimeout(() => m.style.display = 'none', 300);
            }
        }

        // ===== FORM SUBMIT =====
        const taskFormEl = document.getElementById('taskForm');
        if (taskFormEl) {
            taskFormEl.onsubmit = async (e) => {
                e.preventDefault();
                if (isSubmitting) return;
                isSubmitting = true;
                
                const submitBtn = e.target.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                const fd = new FormData(e.target);
                const id = fd.get('id');
                const action = id ? 'edit_task_note' : 'add_task_note';

                try {
                    const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: fd });
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (jsonErr) {
                        console.error("Server returned non-JSON response:", text);
                        showToast("Gagal menyimpan catatan: " + text.substring(0, 200), 'error');
                        return;
                    }

                    if (data.success) {
                        closeModal();
                        showToast(id ? 'Catatan berhasil diperbarui' : 'Catatan berhasil disimpan', 'success');
                        // Reload calendar data
                        const noteDate = fd.get('note_date');
                        const noteMonth = noteDate.substring(0, 7);
                        const currentMonthStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0');

                        if (noteMonth !== currentMonthStr) {
                            // Navigate to the month of the new/edited note
                            const parts = noteMonth.split('-');
                            currentYear = parseInt(parts[0]);
                            currentMonth = parseInt(parts[1]) - 1;
                            selectedDate = noteDate;
                        }
                        await loadMonthNotes();
                        selectDate(noteDate);
                    } else {
                        showToast(data.error || 'Gagal menyimpan catatan.', 'error');
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan jaringan: ' + err.message, 'error');
                    console.error(err);
                } finally {
                    isSubmitting = false;
                    if (submitBtn) submitBtn.disabled = false;
                }
            };
        }

        // ===== TOGGLE DONE =====
        async function toggleTask(id) {
            const fd = new FormData();
            fd.append('id', id);
            try {
                await fetch(`${API}?action=toggle_task_note`, { method: 'POST', body: fd });
                await loadMonthNotes();
            } catch (e) { console.error(e); }
        }

        // ===== DELETE =====
        function deleteTask(id) {
            showConfirmToast('Apakah Anda yakin ingin menghapus catatan ini?', async () => {
                const fd = new FormData();
                fd.append('id', id);
                try {
                    const res = await fetch(`${API}?action=delete_task_note`, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Catatan berhasil dihapus', 'success');
                        await loadMonthNotes();
                    } else {
                        showToast(data.error || 'Gagal menghapus catatan', 'error');
                    }
                } catch (e) {
                    showToast('Terjadi kesalahan jaringan', 'error');
                    console.error(e);
                }
            });
        }

        // ===== TODAY SUMMARY =====
        function updateTodaySummary() {
            const todayTasks = monthNotes.filter(n => n.note_date === todayStr && n.is_done == 0);
            const el = document.getElementById('todayCountText');
            if (el) {
                el.textContent = todayTasks.length;
            }
        }

        // ===== TOAST NOTIFICATION =====
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
            }, 3000);
        }

        function showConfirmToast(message, onOk, btnText = 'Ya, Hapus') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast warning`;
            toast.style.minWidth = '320px';
            toast.style.flexDirection = 'column';
            toast.style.alignItems = 'flex-start';
            toast.style.padding = '1rem';

            toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px; width:100%;">
                    <span class="material-symbols-outlined toast-icon" style="color:var(--warning);">help</span>
                    <div class="toast-content" style="flex:1; font-weight:700;">${message}</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:12px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="confirmCancel" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px;">Batal</button>
                    <button class="btn btn-primary" id="confirmOk" style="padding:4px 12px; font-size:0.75rem; height:28px; border-radius:6px; background:var(--primary); border:none; box-shadow:none;">${btnText}</button>
                </div>
            `;

            container.appendChild(toast);

            toast.querySelector('#confirmOk').onclick = () => {
                onOk();
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
            toast.querySelector('#confirmCancel').onclick = () => {
                toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            };
        }

        // Initialize
        init();
    </script>
    <div id="toastContainer"></div>
</body>

</html>
