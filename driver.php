<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('tasks');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver App | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: rgba(37, 99, 235, 0.08);
            --primary-glow: rgba(37, 99, 235, 0.2);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card: #FFFFFF;
            --text: #0f172a;
            --text-sub: #64748b;
            --border: rgba(0, 0, 0, 0.06);
            --ios-shadow: 0 8px 30px rgba(0, 0, 0, 0.04), 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        html {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            color: var(--text);
            min-height: 100vh;
            letter-spacing: -0.015em;
        }

        .app-container {
            max-width: 500px;
            margin: 0 auto;
            background: transparent;
            min-height: 100vh;
            position: relative;
            padding-bottom: 110px;
            /* space for floating bottom tab bar */
        }

        .app-header {
            background: transparent;
            color: var(--text);
            padding: 2.5rem 1.25rem 1rem;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .header-top h1 {
            font-size: 1.25rem;
            font-weight: 750;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .logout-btn,
        .profile-hdr-btn {
            background: rgba(255, 255, 255, 0.65) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text) !important;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .logout-btn span,
        .profile-hdr-btn span {
            color: var(--text) !important;
        }

        .logout-btn:active,
        .profile-hdr-btn:active {
            transform: scale(0.92);
            background: rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .metric-box {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 0.8rem 0.5rem;
            border-radius: 1.1rem;
            text-align: center;
            flex: 1;
            margin: 0 4px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .metric-box:active {
            transform: scale(0.95);
        }

        .metric-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.03em;
        }

        .metric-lbl {
            font-size: 0.625rem;
            color: var(--text-sub);
            text-transform: uppercase;
            margin-top: 4px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .gps-alert-box {
            background: #ffebeb;
            margin: -1.75rem 1.5rem 1.5rem;
            padding: 1rem;
            border-radius: 1.25rem;
            display: none;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 10px 25px rgba(255, 59, 48, 0.12);
            border: 1px solid rgba(255, 59, 48, 0.1);
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }

        .gps-alert-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 59, 48, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger);
        }

        .pulse-container {
            position: relative;
            width: 44px;
            height: 44px;
            background: rgba(52, 199, 89, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
        }

        .pulse {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse-anim 1.5s infinite;
        }

        @keyframes pulse-anim {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(3);
                opacity: 0;
            }
        }

        .bottom-nav {
            position: fixed;
            bottom: 24px;
            left: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            padding: 0.6rem 0.4rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            max-width: 460px;
            margin: 0 auto;
        }

        .nav-item {
            flex: 1;
            border: none;
            background: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            color: #8E8E93;
            font-size: 0.72rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-item.active {
            color: var(--primary);
        }

        .nav-pill {
            width: 44px;
            height: 28px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-bottom: 2px;
            position: relative;
        }

        .nav-item.active .nav-pill {
            background: rgba(0, 122, 255, 0.1);
        }

        .nav-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 15px;
            height: 15px;
            padding: 0 3px;
            background: var(--danger);
            color: white;
            font-size: 9px;
            font-weight: 600;
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 7.5px;
            border: 1.5px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
            pointer-events: none;
            line-height: 1;
        }

        .nav-badge span {
            display: inline-block;
            line-height: 1;
            margin-top: -0.5px;
        }

        .nav-item .material-symbols-outlined {
            font-size: 22px;
        }

        .task-container {
            padding: 0.75rem 1.25rem 6.5rem;
        }

        /* === Accordion Task Card === */
        .task-card {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.25rem;
            margin-bottom: 0.875rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }

        .task-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--card-accent, var(--primary));
            opacity: 1;
            transition: width 0.2s ease;
        }

        .task-card.expanded::before {
            width: 7px;
        }

        .task-card.expanded {
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 122, 255, 0.25);
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.82);
        }

        .task-card:active {
            transform: scale(0.985);
        }

        .task-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 1.25rem;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
            gap: 0.75rem;
        }

        .task-card-header:active {
            background: rgba(0, 0, 0, 0.01);
        }

        .task-header-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex: 1;
            min-width: 0;
        }

        .task-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.2s;
        }

        .task-header-icon.pending {
            background: rgba(255, 149, 0, 0.1);
            color: var(--warning);
        }

        .task-header-icon.transit {
            background: rgba(52, 199, 89, 0.1);
            color: var(--success);
        }

        .task-header-icon.completed {
            background: rgba(0, 122, 255, 0.1);
            color: var(--primary);
        }

        .task-header-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            min-width: 0;
        }

        .task-header-meta .order-num {
            font-size: 0.7rem;
            color: var(--text-sub);
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .dest-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1c1c1e;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .task-header-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 100px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            line-height: 1.2;
        }

        .badge-pending {
            background: rgba(255, 149, 0, 0.12);
            color: #c97500;
        }

        .badge-transit {
            background: rgba(52, 199, 89, 0.12);
            color: #248a3e;
        }

        .badge-completed {
            background: rgba(0, 122, 255, 0.12);
            color: #0056b3;
        }

        .badge-danger {
            background: rgba(255, 59, 48, 0.12);
            color: #c9221b;
        }

        .accordion-arrow {
            font-size: 20px;
            color: #c7c7cc;
            transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .task-card.expanded .accordion-arrow {
            transform: rotate(180deg);
        }

        /* Accordion Body */
        .task-card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .task-card.expanded .task-card-body {
            max-height: 1500px;
        }

        .task-card-body-inner {
            padding: 0 1.25rem 1.25rem 1.25rem;
        }

        .task-divider {
            height: 1px;
            background: rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
        }

        .task-row {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            margin-bottom: 0.85rem;
        }

        .task-row span.mat-icon {
            color: var(--primary);
            font-size: 20px;
            margin-top: 1px;
        }

        .task-row .info {
            font-size: 0.72rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .task-row .value {
            font-size: 0.925rem;
            font-weight: 600;
            color: #1c1c1e;
            line-height: 1.4;
            word-break: break-word;
        }

        .task-type-badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .task-meta-row {
            display: flex;
            gap: 1rem;
            padding: 0.875rem 0;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
        }

        .task-meta-item {
            flex: 1;
        }

        .task-meta-item .info {
            font-size: 0.72rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .task-meta-item .value {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text);
        }

        .btn-action {
            flex: 1;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: inherit;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .btn-action:active {
            transform: scale(0.95);
        }

        .btn-checkin {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 16px rgba(0, 122, 255, 0.2);
        }

        .btn-checkin:active {
            background: var(--primary-dark);
        }

        .btn-nav {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid rgba(0, 122, 255, 0.1);
        }

        .task-section-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 1.5rem 0 1rem;
        }

        /* Float button animation */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-5px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .floating-status {
            animation: float 3s ease-in-out infinite;
        }

        /* Vehicle Modal Styling */
        #vehicleModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 2000;
            justify-content: center;
            align-items: flex-end;
            /* slide up sheet on mobile */
        }

        @media (min-width: 500px) {
            #vehicleModal {
                align-items: center;
                padding: 2rem;
            }
        }

        .vehicle-modal-content {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-top-left-radius: 2rem;
            border-top-right-radius: 2rem;
            width: 100%;
            max-width: 500px;
            padding: 2.2rem 1.5rem 2.5rem;
            text-align: center;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            animation: slideUp 0.35s cubic-bezier(0.19, 1, 0.22, 1);
        }

        @media (min-width: 500px) {
            .vehicle-modal-content {
                border-radius: 2rem;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
                max-width: 420px;
                padding: 2.5rem 2rem 2rem;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .modal-close-btn {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: #f1f5f9;
            border: none;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .modal-close-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .vehicle-option {
            padding: 1.1rem 1.25rem;
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 1.2rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .vehicle-option:active {
            transform: scale(0.98);
            background: rgba(255, 255, 255, 0.8);
        }

        .vehicle-option:hover {
            border-color: var(--primary);
            background: rgba(0, 122, 255, 0.04);
        }

        .vehicle-option.selected {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        /* Clickable destination row */
        .task-row.nav-tap {
            cursor: pointer;
            border-radius: 0.75rem;
            padding: 0.5rem;
            margin: 0 -0.5rem 0.25rem;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .task-row.nav-tap:active {
            background: rgba(0, 122, 255, 0.06);
            transform: scale(0.985);
        }

        /* Horizontal Calendar Strip */
        .calendar-strip {
            display: flex;
            overflow-x: auto;
            gap: 0.5rem;
            padding: 1.25rem 1.25rem 0.5rem;
            margin: 0;
            scrollbar-width: none;
            scroll-behavior: smooth;
        }

        .calendar-strip::-webkit-scrollbar {
            display: none;
        }

        .cal-day-cell {
            flex: 0 0 auto;
            width: 52px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.6rem 0;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            color: #8E8E93;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .cal-day-cell:active {
            transform: scale(0.92);
        }

        .cal-day-cell.active {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(0, 122, 255, 0.3) !important;
            border-color: var(--primary) !important;
        }

        .cal-day-cell.active .cal-day-num {
            color: white !important;
        }

        .cal-day-name {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .cal-day-num {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1c1c1e;
        }

        .cal-dots {
            display: flex;
            gap: 3px;
            margin-top: 4px;
            height: 5px;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .cal-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
        }

        .cal-dot.pending {
            background: var(--danger);
        }

        .cal-dot.completed {
            background: var(--success);
        }

        .cal-day-cell.active .cal-dot.pending {
            background: white;
        }

        .cal-day-cell.active .cal-dot.completed {
            background: rgba(255, 255, 255, 0.6);
        }

        .cal-day-cell.has-task {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(0, 122, 255, 0.2);
        }

        .cal-day-cell.has-task .cal-day-num {
            color: var(--primary);
        }

        /* Custom Confirm Modal & Toast */
        .confirm-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.25);
            z-index: 5000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 1.5rem;
            transition: opacity 0.3s ease;
        }

        .confirm-modal-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            padding: 1.85rem;
            border-radius: 24px;
            width: 100%;
            max-width: 340px;
            text-align: center;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: iosModalZoom 0.28s cubic-bezier(0.19, 1, 0.22, 1);
        }

        @keyframes iosModalZoom {
            from {
                transform: scale(0.85);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Form styling inside modals */
        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #3a3a3c;
            display: block;
            margin-bottom: 0.4rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            font-size: 0.925rem;
            color: #1c1c1e;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
        }

        .toast {
            visibility: hidden;
            min-width: 250px;
            background-color: rgba(28, 28, 30, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #fff;
            text-align: center;
            border-radius: 9999px;
            padding: 0.75rem 1.5rem;
            position: fixed;
            z-index: 999999;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            font-size: 0.875rem;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 100px;
        }

        .toast.success {
            background-color: rgba(52, 199, 89, 0.95);
        }

        .toast.warning {
            background-color: rgba(255, 149, 0, 0.95);
        }

        .toast.danger {
            background-color: rgba(255, 59, 48, 0.95);
        }

        .active-vehicle-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.25rem;
            padding: 1.1rem 1.25rem;
            margin: 1rem 0;
            display: none;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.25);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .active-vehicle-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .av-icon {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .av-details {
            flex: 1;
            min-width: 0;
        }

        .av-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .av-name {
            font-weight: 800;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            color: #f8fafc;
        }

        .av-plate {
            font-size: 0.8rem;
            font-weight: 700;
            color: #38bdf8;
        }

        .active-vehicle-card.empty {
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.95) 0%, rgba(254, 243, 199, 0.95) 100%);
            border: 2px dashed #f59e0b;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.15);
            color: #78350f;
            cursor: pointer;
        }

        .active-vehicle-card.empty .av-icon {
            background: rgba(245, 158, 11, 0.2);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.4);
        }

        .active-vehicle-card.empty .av-label {
            color: #b45309;
        }

        .active-vehicle-card.empty .av-name {
            color: #78350f;
        }

        .active-vehicle-card.empty .av-plate {
            color: #d97706;
        }

        .active-vehicle-card.empty .release-btn {
            background: #d97706;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);
        }

        .release-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .release-btn:active {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(0.92);
        }

        .active-vehicle-card.empty {
            background: rgba(255, 255, 255, 0.7);
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            color: #0f172a;
        }

        .active-vehicle-card.empty .av-label {
            color: #64748b;
        }

        .active-vehicle-card.empty .av-name {
            color: #0f172a;
        }

        .active-vehicle-card.empty .av-icon {
            background: rgba(0, 0, 0, 0.04);
            color: rgba(0, 0, 0, 0.4);
            border: none;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <div class="app-header">
            <div class="header-top">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <a href="mobile_home.php" class="profile-hdr-btn" style="text-decoration:none;">
                        <span class="material-symbols-outlined" style="font-size:20px;">arrow_back</span>
                    </a>
                    <h1 style="font-size:1.1rem;">Halo, <?php echo explode(' ', trim($_SESSION['name']))[0]; ?>! 👋</h1>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <button class="profile-hdr-btn" onclick="openProfileModal()" title="Profil Saya">
                        <span class="material-symbols-outlined">account_circle</span>
                    </button>
                    <a href="logout.php" class="logout-btn">
                        <span class="material-symbols-outlined">logout</span>
                    </a>
                </div>
            </div>

            <div id="activeVehicleCard" class="active-vehicle-card" style="display: flex;">
                <div class="av-icon" id="vStatusIcon">
                    <span class="material-symbols-outlined">local_shipping</span>
                </div>
                <div class="av-details">
                    <div class="av-label" id="vStatusLabel">Kendaraan Aktif</div>
                    <div id="avName" class="av-name">-</div>
                    <div id="avPlate" class="av-plate">-</div>
                </div>
                <button id="vActionBtn" onclick="handleVehicleAction()" class="release-btn"
                    title="Pilih / Lepas Kendaraan">
                    <span class="material-symbols-outlined" id="vActionIcon" style="font-size: 22px;">warehouse</span>
                </button>
            </div>

            <div class="metrics-container" style="display:flex; justify-content:space-between; margin-top:0.5rem;">
                <div class="metric-box">
                    <div id="countPending" class="metric-val">0</div>
                    <div class="metric-lbl">Tugas Baru</div>
                </div>
                <div class="metric-box">
                    <div id="countInTransit" class="metric-val">0</div>
                    <div class="metric-lbl">Pengiriman</div>
                </div>
                <div class="metric-box">
                    <div id="countCompleted" class="metric-val">0</div>
                    <div class="metric-lbl">Selesai</div>
                </div>
            </div>
        </div>

        <div class="gps-alert-box" id="gpsAlertBox">
            <div class="gps-alert-icon">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">warning</span>
            </div>
            <div>
                <div style="font-weight: 800; font-size: 0.95rem; color: #991b1b;">GPS TERPUTUS!</div>
                <div style="font-size: 0.75rem; color: #ef4444; font-weight: 600;">Aktifkan GPS & Izin Lokasi untuk
                    tetap bertugas</div>
            </div>
        </div>

        <!-- Horizontal Calendar Strip -->
        <div id="calendarStrip" class="calendar-strip"></div>

        <!-- Late Reason Modal -->
        <div id="lateReasonModal" class="confirm-modal-overlay" style="z-index: 5500;">
            <div class="confirm-modal-box">
                <div
                    style="background:#fefce8; color:#eab308; width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                    <span class="material-symbols-outlined" style="font-size:32px;">history_toggle_off</span>
                </div>
                <h3 style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:0.5rem;">Penyelesaian
                    Tertunda</h3>
                <p style="color:#64748b; font-size:0.85rem; margin-bottom:1rem; line-height:1.4;">Tugas ini dari tanggal
                    lampau. Mohon tulis alasan mengapa baru diselesaikan sekarang.</p>
                <textarea id="lateReasonInput" rows="3" placeholder="Contoh: Lupa tekan selesai, hapenya lowbet..."
                    style="box-sizing:border-box; width:100%; border:1px solid #cbd5e1; border-radius:0.75rem; padding:0.75rem; font-family:inherit; font-size:0.9rem; margin-bottom:1.25rem; resize:none;"
                    required></textarea>
                <div style="display:flex; gap:0.75rem;">
                    <button onclick="closeLateReasonModal()"
                        style="flex:1; padding:0.875rem; border:none; border-radius:14px; background:rgba(0,0,0,0.05); color:#3a3a3c; font-weight:700; cursor:pointer; transition:0.2s;">Batal</button>
                    <button id="lateReasonSubmitBtn"
                        style="flex:1; padding:0.875rem; border:none; border-radius:14px; background:var(--warning); color:white; font-weight:700; cursor:pointer; transition:0.2s; box-shadow:0 4px 12px rgba(255,149,0,0.2);">Kirim</button>
                </div>
            </div>
        </div>

        <main id="taskList" class="task-container">
            <div style="text-align:center; padding: 2rem;">Memuat...</div>
        </main>

        <footer
            style="text-align: center; margin: 1.5rem 0 2rem; font-size: 0.65rem; color: var(--text-sub); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.6;">
            Powered By Dhanielo-Marthinz | IMS @ 2026
        </footer>

        <div id="activeTasks" style="display:none;"></div>
        <div id="completedTasks" style="display:none;"></div>

        <div class="bottom-nav">
            <button class="nav-item active" onclick="switchSection('pending', this)">
                <div class="nav-pill">
                    <span class="material-symbols-outlined">pending_actions</span>
                    <span id="badgePending" class="nav-badge">0</span>
                </div>
                Tugas Baru
            </button>
            <button class="nav-item" onclick="switchSection('in_transit', this)">
                <div class="nav-pill">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span id="badgeTransit" class="nav-badge">0</span>
                </div>
                Perjalanan
            </button>
            <button class="nav-item" onclick="switchSection('completed', this)">
                <div class="nav-pill">
                    <span class="material-symbols-outlined">history</span>
                    <span id="badgeCompleted" class="nav-badge">0</span>
                </div>
                Riwayat
            </button>
        </div>
    </div>

    <div id="vehicleModal">
        <div class="vehicle-modal-content">
            <button class="modal-close-btn" onclick="document.getElementById('vehicleModal').style.display='none'">
                <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
            </button>
            <h2 style="margin-bottom: 1rem;">Pilih Kendaraan</h2>
            <p style="color: var(--text-sub); margin-bottom: 1.5rem;">Pilih mobil yang Anda gunakan hari ini:</p>
            <div id="vehicleOptionsList">
                <!-- Vehicles will be listed here -->
            </div>
        </div>
    </div>

    <audio id="alarmAudio" loop preload="auto">
        <source src="https://actions.google.com/sounds/v1/alarms/doorbell.ogg" type="audio/ogg">
    </audio>

    <div id="alarmModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem; backdrop-filter:blur(5px);">
        <div
            style="background:white; border-radius:1.5rem; width:100%; max-width:400px; padding:2.5rem 2rem; text-align:center; animation: zoomIn 0.3s ease-out; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
            <div
                style="background:#fef3c7; color:#d97706; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <span class="material-symbols-outlined"
                    style="font-size:48px; animation: float 2s infinite;">notifications_active</span>
            </div>
            <h2 style="margin-bottom:0.75rem; color:#1e293b; font-size:1.5rem; font-weight:800;">Tugas Baru Masuk!</h2>
            <p style="color:#64748b; margin-bottom:2rem; line-height:1.5;">Anda mendapatkan assignment pengiriman baru
                dari Admin. Silakan cek di tab <strong>Tugas Baru</strong>.</p>
            <button onclick="stopAlarm()"
                style="background:var(--primary); color:white; width:100%; padding:0.875rem; border:none; border-radius:14px; font-size:1rem; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0, 122, 255, 0.25);">OK,
                SAYA MENGERTI</button>
        </div>
    </div>

    <div id="proofModal" class="confirm-modal-overlay" style="z-index: 6000; display:none;">
        <div class="confirm-modal-box" style="max-width: 440px; padding: 1.5rem; width:90%;">
            <div
                style="width: 56px; height: 56px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                <span class="material-symbols-outlined" style="font-size: 32px;">photo_camera</span>
            </div>
            <h3
                style="margin: 0 0 0.25rem; font-size: 1.25rem; font-weight: 800; color: var(--text); text-align:center;">
                Penyelesaian Perjalanan</h3>
            <p id="proofTargetName"
                style="margin: 0 0 1.25rem; color: var(--text-sub); font-size: 0.85rem; line-height: 1.5; text-align:center;">
            </p>

            <!-- Speedometer Akhir Section (Wajib saat Selesai) -->
            <div id="speedoEndSection"
                style="margin-bottom: 1.25rem; background: rgba(0,122,255,0.04); padding: 1rem; border-radius: 1rem; border: 1px solid rgba(0,122,255,0.15);">
                <div
                    style="font-size:0.8rem; font-weight:800; color:var(--primary); text-transform:uppercase; margin-bottom:0.75rem; display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">speed</span>
                    Speedometer Akhir (Wajib)
                </div>

                <div style="margin-bottom:0.75rem; text-align:left;">
                    <label
                        style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.3rem;">1.
                        Foto Spidometer Akhir *</label>
                    <input type="file" id="speedoEndPhoto" accept="image/*" capture="environment" style="display:none;"
                        onchange="previewSpeedoEndPhoto(this)">
                    <button type="button" onclick="document.getElementById('speedoEndPhoto').click()"
                        style="width:100%; padding:0.75rem; background:white; border:1.5px dashed var(--primary); border-radius:10px; color:var(--primary); font-weight:700; font-size:0.825rem; display:flex; align-items:center; justify-content:center; gap:6px; cursor:pointer; font-family:inherit;">
                        <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span>
                        <span id="speedoEndCamBtnText">Ambil Foto Speedometer Akhir</span>
                    </button>
                    <div id="speedoEndPreviewBox" style="display:none; margin-top:0.5rem; text-align:center;">
                        <img id="speedoEndImgPreview" src=""
                            style="width:100%; max-height:140px; object-fit:cover; border-radius:8px; border:1px solid var(--border);">
                    </div>
                </div>

                <div style="text-align:left;">
                    <label
                        style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.3rem;">2.
                        Angka Spidometer Akhir (KM) *</label>
                    <input type="number" id="speedoEndNum" placeholder="Contoh: 45280" onkeyup="validateProof()"
                        style="width:100%; padding:0.7rem 0.85rem; border:1.5px solid #e2e8f0; border-radius:10px; font-size:0.95rem; font-weight:700; font-family:inherit; box-sizing:border-box;"
                        required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem; display: none;">
                <label id="receiverLabel"
                    style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.5rem;">Nama
                    Karyawan / Penumpang (Opsional)</label>
                <input type="text" id="receiverNameInput" onkeyup="validateProof()"
                    placeholder="Contoh: Pak Budi & Tim Marketing"
                    style="width: 100%; padding: 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.9rem; font-family:inherit; box-sizing:border-box;">
            </div>

            <!-- Section: Foto Tujuan (Wajib) -->
            <div style="margin-bottom: 1rem; text-align:left;">
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.5rem;">Foto
                    Tujuan (Wajib) *</label>
                <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                    <button type="button" onclick="document.getElementById('proofCamera').click()"
                        style="flex:1; padding: 0.75rem 0.5rem; border: 1.5px dashed var(--primary); border-radius: 0.75rem; background: rgba(0,122,255,0.04); color: var(--primary); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: 0.2s; font-size:0.85rem; font-family:inherit;">
                        <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span>
                        <span>Ambil Foto Tujuan (Wajib)</span>
                    </button>
                </div>
                <div id="photoGallery"
                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.5rem;">
                </div>
                <div style="text-align:center; font-size:0.75rem; color:#94a3b8; margin-bottom:0.5rem;"
                    id="photoBtnText"></div>
            </div>

            <!-- Section: Catatan Perjalanan -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.5rem;">Catatan
                    Perjalanan (Opsional)</label>
                <textarea id="driverNoteInput" placeholder="Contoh: Perjalanan lancar / Sempat macet di tol..."
                    onkeyup="validateProof()"
                    style="width: 100%; padding: 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.9rem; font-family:inherit; box-sizing:border-box; resize:none;"
                    rows="2"></textarea>
            </div>

            <input type="file" id="proofCamera" accept="image/*" capture="environment" style="display: none;"
                onchange="handleProofChange(this)">
            <input type="file" id="proofUpload" accept="image/*" multiple style="display: none;"
                onchange="handleProofChangeMulti(this)">
            <input type="file" id="proofInput" style="display:none;">

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button onclick="closeProofModal()"
                    style="flex: 1; padding: 0.875rem; border: none; border-radius: 14px; background: rgba(0,0,0,0.05); color: #3a3a3c; font-weight: 700; cursor: pointer;">Batal</button>
                <button id="proofSubmitBtn" onclick="submitProof()" disabled
                    style="flex: 1; padding: 0.875rem; border: none; border-radius: 14px; background: var(--primary); color: white; font-weight: 700; cursor: pointer; opacity: 0.5; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);">Selesai</button>
            </div>
            <canvas id="watermarkCanvas" style="display: none;"></canvas>
        </div>
    </div>

    <div id="confirmModal" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div
                style="background:#fef2f2; color:#ef4444; width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <span class="material-symbols-outlined" style="font-size:32px;">help</span>
            </div>
            <h3 style="font-size:1.25rem; font-weight:800; color:#1e293b; margin-bottom:0.5rem;" id="confirmTitle">
                Konfirmasi</h3>
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.5rem; line-height:1.5;" id="confirmDesc">Apakah
                Anda yakin?</p>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="closeConfirmModal()"
                    style="flex:1; padding:0.875rem; border:none; border-radius:14px; background:rgba(0,0,0,0.05); color:#3a3a3c; font-weight:700; cursor:pointer; transition:0.2s;">Batal</button>
                <button id="confirmYesBtn"
                    style="flex:1; padding:0.875rem; border:none; border-radius:14px; background:var(--primary); color:white; font-weight:700; cursor:pointer; transition:0.2s; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);">Ya,
                    Selesai</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">
        <span class="material-symbols-outlined" id="toastIcon">check_circle</span>
        <span id="toastMsg">Berhasil!</span>
    </div>

    <script>
        const API_URL = 'api.php';
        let currentPos = { lat: -6.2088, lng: 106.8456 };
        let activeVehicle = null;
        let knownPendingIds = new Set();
        let expandedTaskIds = new Set();
        let isFirstLoad = true;

        async function checkVehicleAssignment() {
            const driver_id = '<?php echo $_SESSION['user_id']; ?>';
            try {
                const res = await fetch(`${API_URL}?action=get_active_vehicle&driver_id=${driver_id}`);
                const data = await res.json();

                const card = document.getElementById('activeVehicleCard');
                const nameEl = document.getElementById('avName');
                const plateEl = document.getElementById('avPlate');
                const labelEl = document.getElementById('vStatusLabel');
                const iconContainer = document.getElementById('vStatusIcon');
                const iconEl = iconContainer.querySelector('.material-symbols-outlined');
                const btnIconEl = document.getElementById('vActionIcon');

                if (data.error) {
                    activeVehicle = null;
                    card.classList.add('empty');
                    card.onclick = handleVehicleAction;
                    nameEl.innerText = 'Belum Ada Mobil';
                    plateEl.innerText = 'Ketuk di sini untuk memilih mobil';
                    labelEl.innerText = 'STATUS DRIVER';
                    iconEl.innerText = 'commute';
                    btnIconEl.innerText = 'add_circle';
                    loadTasks();
                    if (isFirstLoad) {
                        setTimeout(() => { showVehicleModal(); }, 500);
                    }
                } else {
                    activeVehicle = data;
                    card.classList.remove('empty');
                    card.onclick = null;
                    nameEl.innerText = data.vehicle_name;
                    plateEl.innerText = data.plate_number;
                    labelEl.innerText = 'KENDARAAN AKTIF';
                    iconEl.innerText = 'local_shipping';
                    btnIconEl.innerText = 'warehouse';
                    loadTasks();
                }
            } catch (err) {
                console.error("Failed to check vehicle", err);
            }
        }

        function handleVehicleAction() {
            if (activeVehicle) {
                confirmReleaseVehicle();
            } else {
                showVehicleModal();
            }
        }

        async function showVehicleModal() {
            const modal = document.getElementById('vehicleModal');
            const list = document.getElementById('vehicleOptionsList');
            const currentUserId = '<?php echo $_SESSION['user_id'] ?? ''; ?>';
            modal.style.display = 'flex';

            try {
                const res = await fetch(`${API_URL}?action=get_vehicles`);
                const vehicles = await res.json();
                list.innerHTML = '';

                if (!Array.isArray(vehicles) || vehicles.length === 0) {
                    list.innerHTML = '<p style="color:#64748b; padding:1rem;">Tidak ada kendaraan yang terdaftar dalam sistem.</p>';
                    return;
                }

                vehicles.forEach(v => {
                    const isUsedByOther = v.current_driver_name !== null && String(v.current_driver_id) !== String(currentUserId);
                    const isUsedByMe = v.current_driver_name !== null && String(v.current_driver_id) === String(currentUserId);
                    const div = document.createElement('div');
                    div.className = 'vehicle-option';

                    if (isUsedByOther) {
                        div.style.opacity = '0.6';
                        div.style.cursor = 'not-allowed';
                        div.innerHTML = `
                            <div>
                                <strong>${v.name}</strong><br>
                                <small>${v.plate_number}</small>
                                <div style="color:#ef4444; font-size:0.75rem; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:6px;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">person_off</span> Dipakai: ${v.current_driver_name}
                                </div>
                            </div>
                            <span class="material-symbols-outlined" style="color:#94a3b8;">lock</span>
                        `;
                    } else if (isUsedByMe) {
                        div.style.borderColor = 'var(--primary)';
                        div.style.background = 'rgba(0, 122, 255, 0.08)';
                        div.innerHTML = `
                            <div>
                                <strong>${v.name}</strong><br>
                                <small>${v.plate_number}</small>
                                <div style="color:var(--primary); font-size:0.75rem; font-weight:700; display:flex; align-items:center; gap:4px; margin-top:6px;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span> Mobil Anda Saat Ini
                                </div>
                            </div>
                            <span class="material-symbols-outlined" style="color:var(--primary);">check_circle</span>
                        `;
                        div.onclick = () => selectVehicle(v.id);
                    } else {
                        div.innerHTML = `
                            <div>
                                <strong>${v.name}</strong><br>
                                <small>${v.plate_number}</small>
                                <div style="color:#10b981; font-size:0.75rem; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:6px;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span> Tersedia
                                </div>
                            </div>
                            <span class="material-symbols-outlined" style="color:var(--primary);">arrow_forward</span>
                        `;
                        div.onclick = () => selectVehicle(v.id);
                    }
                    list.appendChild(div);
                });
            } catch (err) {
                list.innerHTML = '<p style="color:red">Gagal memuat daftar kendaraan.</p>';
            }
        }

        async function selectVehicle(vehicleId) {
            const driver_id = '<?php echo $_SESSION['user_id'] ?? ''; ?>';
            if (!driver_id) {
                showToast('Sesi Anda berakhir, silakan login ulang', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('driver_id', driver_id);
            formData.append('vehicle_id', vehicleId);

            try {
                const res = await fetch(`${API_URL}?action=assign_vehicle`, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('vehicleModal').style.display = 'none';
                    showToast('Mobil berhasil dipilih!', 'success');
                    checkVehicleAssignment();
                } else {
                    showToast(result.error || 'Gagal memilih kendaraan', 'error');
                }
            } catch (err) {
                showToast('Gagal memilih kendaraan', 'error');
            }
        }

        let activeFilter = 'pending';
        let activeDate = getTodayStr();
        let calendarSummary = [];

        function getTodayStr(diffDays = 0) {
            const d = new Date();
            if (diffDays !== 0) d.setDate(d.getDate() + Math.round(diffDays));
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        async function fetchCalendarSummary() {
            const driver_id = '<?php echo $_SESSION["user_id"]; ?>';
            const startD = getTodayStr(-7);
            const endD = getTodayStr(14);
            try {
                const res = await fetch(`${API_URL}?action=get_calendar_summary&driver_id=${driver_id}&start_date=${startD}&end_date=${endD}`);
                calendarSummary = await res.json();
                renderCalendar();
            } catch (e) { }
        }

        function renderCalendar() {
            const strip = document.getElementById('calendarStrip');
            if (!strip) return;
            strip.innerHTML = '';
            const todayStr = getTodayStr();
            const daysArr = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

            for (let i = -7; i <= 14; i++) {
                const d = new Date();
                d.setDate(d.getDate() + i);
                const loopDateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

                let dayName = daysArr[d.getDay()];
                if (loopDateStr === todayStr) dayName = 'Hr Ini';

                const summaryLines = calendarSummary.filter(c => c.target_date === loopDateStr);
                let hasPending = false;
                let hasCompleted = false;
                summaryLines.forEach(s => {
                    if (s.status === 'pending' || s.status === 'in_transit') hasPending = true;
                    if (s.status === 'completed') hasCompleted = true;
                });

                let dotsHtml = '';
                if (hasPending) dotsHtml += '<div class="cal-dot pending"></div>';
                if (hasCompleted) dotsHtml += '<div class="cal-dot completed"></div>';

                const cell = document.createElement('div');
                let cellClasses = ['cal-day-cell'];
                if (loopDateStr === activeDate) cellClasses.push('active');
                if (hasPending || hasCompleted) cellClasses.push('has-task');

                cell.className = cellClasses.join(' ');
                if (loopDateStr === todayStr && loopDateStr !== activeDate) cell.style.border = '1px dashed var(--primary)';

                cell.onclick = () => selectDate(loopDateStr, cell);
                cell.innerHTML = `
                    <div class="cal-day-name">${dayName}</div>
                    <div class="cal-day-num">${d.getDate()}</div>
                    <div class="cal-dots">${dotsHtml}</div>
                `;

                strip.appendChild(cell);

                // Auto scroll
                if (loopDateStr === activeDate) {
                    setTimeout(() => {
                        cell.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    }, 50);
                }
            }
        }

        function selectDate(dateStr) {
            activeDate = dateStr;
            isFirstLoad = true;
            lastDataJson = "";
            renderCalendar();
            loadTasks();
        }

        function switchSection(section, el) {
            document.querySelectorAll('.nav-item').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            activeFilter = section;
            lastDataJson = "";
            loadTasks(section);
        }

        function toggleCard(cardEl, taskId) {
            const isExpanded = cardEl.classList.toggle('expanded');
            if (isExpanded) {
                expandedTaskIds.add(taskId);
            } else {
                expandedTaskIds.delete(taskId);
            }
        }

        function formatLocationDisplay(name) {
            if (!name) return '-';
            name = name.trim();
            if (name.startsWith('http://') || name.startsWith('https://')) {
                return '📍 Link Google Maps (Klik/Navigasi)';
            }
            return name;
        }

        function navigateToDestination(lat, lng, address) {
            let url;
            const hasCoords = lat && lng && parseFloat(lat) !== 0 && parseFloat(lng) !== 0;

            if (address && (address.trim().startsWith('http://') || address.trim().startsWith('https://'))) {
                url = address.trim();
            } else if (hasCoords) {
                url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(lat)},${encodeURIComponent(lng)}&travelmode=driving`;
            } else {
                url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}&travelmode=driving`;
            }
            window.open(url, '_blank');
        }

        function showMap(address) {
            navigateToDestination('', '', address);
        }

        function updateLocation() {
            const driver_id = '<?php echo $_SESSION['user_id']; ?>';

            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    currentPos.lat = position.coords.latitude;
                    currentPos.lng = position.coords.longitude;

                    document.getElementById('gpsAlertBox').style.display = 'none';

                    try {
                        const formData = new FormData();
                        formData.append('user_id', driver_id);
                        formData.append('lat', currentPos.lat);
                        formData.append('lng', currentPos.lng);

                        await fetch(`${API_URL}?action=update_location`, {
                            method: 'POST',
                            body: formData
                        });
                    } catch (err) {
                        console.error("Gagal mengirim lokasi", err);
                    }
                }, (error) => {
                    console.error("GPS Error:", error);
                    document.getElementById('gpsAlertBox').style.display = 'flex';

                    if (error.code === error.PERMISSION_DENIED) {
                        showToast('Izin Lokasi Ditolak! Harap izinkan di Browser.', 'danger');
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        console.warn("Posisi tidak tersedia (sinyal lemah)");
                    } else if (error.code === error.TIMEOUT) {
                        console.warn("GPS Timeout - retrying later");
                    }
                }, {
                    enableHighAccuracy: false,
                    timeout: 20000,
                    maximumAge: 10000
                });
            } else {
                document.getElementById('gpsAlertBox').style.display = 'flex';
            }
        }

        let lastDataJson = "";

        function getAllTaskPhotos(task) {
            let photoItems = [];
            let typeText = task.task_type === 'antar' ? 'Delivery' : 'Penjemputan';

            // 1. Bukti Foto Sampai / Selesai (proof_file)
            if (task.proof_file_arr && Array.isArray(task.proof_file_arr) && task.proof_file_arr.length > 0) {
                task.proof_file_arr.forEach((p, idx) => {
                    if (p) photoItems.push({ path: 'uploads/' + p, label: `Bukti ${typeText} ${task.proof_file_arr.length > 1 ? (idx + 1) : ''}`.trim() });
                });
            } else if (task.proof_file) {
                try {
                    const proofs = JSON.parse(task.proof_file);
                    if (Array.isArray(proofs)) {
                        proofs.forEach((p, idx) => {
                            if (p) photoItems.push({ path: 'uploads/' + p, label: `Bukti ${typeText} ${proofs.length > 1 ? (idx + 1) : ''}`.trim() });
                        });
                    }
                } catch (e) {
                    if (typeof task.proof_file === 'string' && !task.proof_file.startsWith('[')) {
                        photoItems.push({ path: 'uploads/' + task.proof_file, label: `Bukti ${typeText}` });
                    }
                }
            }

            // 2. Speedometer Start Photo
            if (task.speedometer_start_photo) {
                photoItems.push({ path: 'uploads/' + task.speedometer_start_photo, label: 'Speedo Awal' });
            }

            // 3. Speedometer End Photo
            if (task.speedometer_end_photo) {
                photoItems.push({ path: 'uploads/' + task.speedometer_end_photo, label: 'Speedo Akhir' });
            }

            // 4. Foto Surat Jalan (sj_photo / surat_jalan_file)
            if (task.surat_jalan_file && Array.isArray(task.surat_jalan_file) && task.surat_jalan_file.length > 0) {
                task.surat_jalan_file.forEach((sj, idx) => {
                    if (sj) photoItems.push({ path: 'uploads/' + sj, label: `Surat Jalan ${task.surat_jalan_file.length > 1 ? (idx + 1) : ''}`.trim() });
                });
            } else if (task.sj_photo) {
                photoItems.push({ path: 'uploads/' + task.sj_photo, label: 'Surat Jalan' });
            }

            // 5. Foto Barang (request_goods / goods_file)
            if (task.request_goods && Array.isArray(task.request_goods) && task.request_goods.length > 0) {
                task.request_goods.forEach((g, idx) => {
                    if (g) photoItems.push({ path: 'uploads/' + g, label: `Foto Barang ${task.request_goods.length > 1 ? (idx + 1) : ''}`.trim() });
                });
            } else if (task.goods_file) {
                photoItems.push({ path: 'uploads/' + task.goods_file, label: 'Foto Barang' });
            }

            return photoItems;
        }

        async function loadTasks(filter = activeFilter) {
            const driver_id = '<?php echo $_SESSION["user_id"]; ?>';
            const container = document.getElementById('taskList');

            try {
                const target_date = activeDate;
                const res = await fetch(`${API_URL}?action=get_deliveries&driver_id=${driver_id}&target_date=${target_date}`);
                const tasks = await res.json();

                if (isFirstLoad && tasks.length === 0) {
                    container.innerHTML = '<div style="text-align:center; padding: 2rem;">Memuat...</div>';
                }

                const currentDataJson = JSON.stringify(tasks) + filter;
                if (currentDataJson === lastDataJson) {
                    return;
                }
                lastDataJson = currentDataJson;

                window.allTasks = tasks;

                let html = '';

                let countPending = 0;
                let countTransit = 0;
                let countCompleted = 0;
                let tempPendingIds = new Set();

                const todayObj = new Date();
                const todayStr = todayObj.getFullYear() + '-' + String(todayObj.getMonth() + 1).padStart(2, '0') + '-' + String(todayObj.getDate()).padStart(2, '0');

                tasks.forEach(task => {
                    const isCompleted = task.status === 'completed' || task.status === 'canceled';
                    const isTransit = task.status === 'in_transit';

                    const taskDateStr = task.target_date ? task.target_date.split(' ')[0] : todayStr;
                    const isActionable = taskDateStr <= todayStr;
                    const isLate = taskDateStr < todayStr;

                    if (task.status === 'pending') {
                        countPending++;
                        tempPendingIds.add(task.id);
                    } else if (isTransit) {
                        countTransit++;
                    } else if (isCompleted || task.status === 'canceled') {
                        countCompleted++;
                    }

                    if (filter === 'pending' && task.status !== 'pending') return;
                    if (filter === 'in_transit' && task.status !== 'in_transit') return;
                    if (filter === 'completed' && (task.status !== 'completed' && task.status !== 'canceled')) return;

                    const statusText = isTransit ? 'PERJALANAN' : (task.status === 'canceled' ? 'CANCEL' : (isCompleted ? 'SELESAI' : 'TUGAS BARU'));
                    const badgeClass = task.status === 'canceled' ? 'badge-danger' : (isCompleted ? 'badge-completed' : (isTransit ? 'badge-transit' : 'badge-pending'));
                    const iconName = task.status === 'canceled' ? 'block' : (isTransit ? 'local_shipping' : (isCompleted ? 'check_circle' : 'pending_actions'));
                    const iconClass = task.status === 'canceled' ? 'pending' : (isTransit ? 'transit' : (isCompleted ? 'completed' : 'pending'));

                    const safeOrigin = (task.origin_name || '').replace(/'/g, "\\'");
                    const safeDest = (task.destination_name || '').replace(/'/g, "\\'");

                    const isExpanded = expandedTaskIds.has(task.id);

                    const taskTime = task.target_date ? task.target_date.split(' ')[1]?.slice(0, 5) : '-';
                    let typeLabel = task.task_type === 'antar' ? '🚗 ANTAR' : '🚐 JEMPUT';
                    let timeLabel = task.task_type === 'antar' ? 'Jam Antar' : 'Jam Jemput';
                    const typeColor = task.task_type === 'antar' ? '#007AFF' : '#10b981';

                    let proofGalleryHtml = '';
                    if (isCompleted) {
                        const allImgs = getAllTaskPhotos(task);
                        if (allImgs.length > 0) {
                            proofGalleryHtml = allImgs.map(item => `
                                <div style="display:inline-flex; flex-direction:column; align-items:center; gap:4px;">
                                    <img src="${item.path}" 
                                         onclick="showImagePopup('${item.path}')" 
                                         style="width: 64px; height: 64px; object-fit: cover; border-radius: 12px; border: 2px solid #38bdf8; box-shadow: 0 4px 10px rgba(0,0,0,0.08); cursor: pointer;"
                                         alt="${item.label}">
                                    <span style="font-size:0.65rem; font-weight:700; color:#475569;">${item.label}</span>
                                </div>
                            `).join('');
                        }
                    }

                    html += `
                        <div class="task-card ${isExpanded ? 'expanded' : ''}" id="card-${task.id}" style="--card-accent: ${typeColor};">
                            <!-- HEADER (clickable to toggle) -->
                            <div class="task-card-header" onclick="toggleCard(document.getElementById('card-${task.id}'), ${task.id})">
                                <div class="task-header-left" style="flex:1; min-width:0;">
                                    <div class="task-header-icon ${iconClass}">
                                        <span class="material-symbols-outlined" style="font-size:22px;">${iconName}</span>
                                    </div>
                                    <div class="task-header-meta">
                                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                                            <span class="task-type-badge" style="background:${typeColor}15; color:${typeColor}; border:1px solid ${typeColor}30; font-weight:800; font-size:0.72rem; padding:2px 8px; border-radius:6px;">${typeLabel}</span>
                                            <span style="font-size:0.72rem; font-weight:700; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:14px; color:#64748b;">schedule</span>
                                                ${taskTime} WIB
                                            </span>
                                        </div>
                                        ${task.receiver_name ? `<div style="font-size:0.875rem; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:16px; color:#2563eb;">person</span> ${task.receiver_name}</div>` : ''}
                                        <div class="dest-name" style="font-size:0.825rem; font-weight:700; color:#475569; margin-top:1px;"><span class="material-symbols-outlined" style="font-size:15px; color:#ef4444; vertical-align:-2px;">location_on</span> ${formatLocationDisplay(task.destination_name)}</div>
                                    </div>
                                </div>
                                <div class="task-header-right">
                                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                                        <span class="status-badge ${badgeClass}">${statusText}</span>
                                    </div>
                                    <span class="material-symbols-outlined accordion-arrow">expand_more</span>
                                </div>
                            </div>

                            <!-- BODY (collapsible) -->
                            <div class="task-card-body">
                                <div class="task-card-body-inner">
                                    <div class="task-divider"></div>

                                    ${task.receiver_name ? `
                                    <div class="task-row" style="background:linear-gradient(135deg, rgba(37,99,235,0.08) 0%, rgba(29,78,216,0.04) 100%); padding:0.75rem 0.85rem; border-radius:14px; border:1px solid rgba(37,99,235,0.18); margin-bottom:0.85rem; align-items:center;">
                                        <div style="width:38px; height:38px; border-radius:10px; background:#2563eb; color:white; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 4px 10px rgba(37,99,235,0.25);">
                                            <span class="material-symbols-outlined" style="font-size:20px;">person</span>
                                        </div>
                                        <div style="flex:1; margin-left:10px;">
                                            <div style="font-size:0.65rem; font-weight:800; color:#2563eb; text-transform:uppercase; letter-spacing:0.04em;">${task.task_type === 'antar' ? 'Karyawan / Penumpang (Diantar)' : 'Karyawan / Penumpang (Dijemput)'}</div>
                                            <div class="value" style="font-weight:800; font-size:1rem; color:#0f172a;">${task.receiver_name}</div>
                                        </div>
                                    </div>
                                    ` : ''}

                                    <!-- VISUAL ROUTE TIMELINE (ASAL -> TUJUAN) -->
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:0.85rem; margin-bottom:0.85rem;">
                                        <!-- ASAL -->
                                        <div class="nav-tap" onclick="navigateToDestination('${task.origin_lat || ''}', '${task.origin_lng || ''}', '${safeOrigin}')" style="display:flex; gap:0.75rem; align-items:flex-start; cursor:pointer;" title="Ketuk untuk navigasi ke lokasi asal">
                                            <div style="width:24px; height:24px; border-radius:50%; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">store</span>
                                            </div>
                                            <div style="flex:1;">
                                                <div style="font-size:0.65rem; font-weight:800; color:#64748b; text-transform:uppercase;">LOKASI ASAL</div>
                                                <div style="font-weight:700; font-size:0.875rem; color:#1e293b; line-height:1.3;">${formatLocationDisplay(task.origin_name)}</div>
                                            </div>
                                            <span class="material-symbols-outlined" style="font-size:16px; color:#2563eb; opacity:0.7;">open_in_new</span>
                                        </div>

                                        <!-- CONNECTOR LINE -->
                                        <div style="border-left:2px dashed #cbd5e1; height:18px; margin-left:11px; margin-top:2px; margin-bottom:2px;"></div>

                                        <!-- TUJUAN -->
                                        <div class="nav-tap" onclick="navigateToDestination('${task.destination_lat || ''}', '${task.destination_lng || ''}', '${safeDest}')" style="display:flex; gap:0.75rem; align-items:flex-start; cursor:pointer;" title="Ketuk untuk navigasi ke tujuan">
                                            <div style="width:24px; height:24px; border-radius:50%; background:#fee2e2; color:#ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">location_on</span>
                                            </div>
                                            <div style="flex:1;">
                                                <div style="font-size:0.65rem; font-weight:800; color:#64748b; text-transform:uppercase;">LOKASI TUJUAN</div>
                                                <div style="font-weight:700; font-size:0.875rem; color:#0f172a; line-height:1.3;">${formatLocationDisplay(task.destination_name)}</div>
                                            </div>
                                            <span class="material-symbols-outlined" style="font-size:16px; color:#ef4444; opacity:0.7;">open_in_new</span>
                                        </div>
                                    </div>
                                    
                                    <div class="task-meta-row" style="background:rgba(37,99,235,0.04); padding:0.75rem 0.85rem; border-radius:14px; margin-bottom:1rem; border:1px dashed rgba(37,99,235,0.25);">
                                        <div style="display:flex; flex-wrap:wrap; gap: 10px; align-items:center; justify-content:space-between;">
                                            <div class="task-meta-item" style="flex:1; min-width: 140px;">
                                                <div class="info" style="font-size:0.65rem; font-weight:800; color:#64748b;">ASSIGN BY</div>
                                                <div class="value" style="font-size:0.85rem; font-weight:700; display:flex; align-items:center; gap:6px; color:#0f172a;">
                                                    <span class="material-symbols-outlined" style="font-size:16px; color:#2563eb;">person_edit</span>
                                                    ${task.creator_name || 'Admin'}
                                                </div>
                                            </div>
                                            <div class="task-meta-item">
                                                <button onclick="event.stopPropagation(); openAdminWa('${task.creator_phone || ''}', '${task.creator_name || 'Admin'}')" 
                                                        style="background:#25d366; color:white; border:none; padding:7px 14px; border-radius:10px; font-size:0.75rem; font-weight:800; display:flex; align-items:center; gap:6px; cursor:pointer; box-shadow:0 4px 12px rgba(37,211,102,0.35);">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:block;">
                                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.185-.573c.948.517 1.938.808 3.144.809 3.181 0 5.767-2.586 5.768-5.766.001-3.18-2.584-5.766-5.766-5.766zm3.333 7.828c-.144.405-.833.743-1.159.791-.326.048-.739.083-2.133-.49-1.393-.573-2.28-1.937-2.35-2.035-.07-.098-.562-.746-.562-1.435 0-.689.351-1.028.476-1.155.125-.127.272-.159.363-.159.091 0 .181.001.259.005.08.004.185-.03.29.221.105.251.362.881.393.945.031.063.051.137.01.219-.041.082-.061.133-.122.204-.041.082-.061.133-.122.204-.061.072-.128.161-.184.216-.062.062-.127.129-.055.253.072.124.322.532.691.861.475.424.877.556 1.002.618.125.062.198.052.271-.031.073-.083.313-.365.396-.489.083-.125.166-.104.281-.062.114.041.727.343.852.406.124.062.208.094.239.146.031.052.031.297-.113.702zM12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.985-1.308C8.423 21.571 10.134 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18c-1.716 0-3.313-.483-4.664-1.314l-.334-.203-2.906.763.777-2.834-.223-.353C3.655 14.731 3 13.13 3 12c0-4.963 4.037-9 9-9s9 4.037 9 9-4.037 9-9 9z"/>
                                                    </svg>
                                                    WhatsApp
                                                </button>
                                            </div>
                                        </div>
                                        
                                        ${task.notes ? `
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(37,99,235,0.25); width: 100%;">
                                                <div style="font-size:0.65rem; font-weight:800; color:#2563eb; text-transform:uppercase; margin-bottom:4px; display:flex; align-items:center; gap:4px;">
                                                    <span class="material-symbols-outlined" style="font-size:14px;">info</span> INSTRUKSI ADMIN
                                                </div>
                                                <div style="font-size:0.875rem; color:#1e293b; line-height:1.4; font-weight:600; font-style:italic;">"${task.notes}"</div>
                                            </div>
                                        ` : ''}
                                    </div>

                                    ${!isCompleted && isActionable ? `
                                        <div style="display:flex; flex-direction:column; gap:0.6rem;">
                                            <button class="btn-action btn-nav" style="width:100%;" onclick="navigateToDestination('${task.destination_lat || ''}', '${task.destination_lng || ''}', '${safeDest}')">
                                                <span class="material-symbols-outlined">navigation</span> Navigasi Ke Tujuan
                                            </button>
                                            <div style="display:flex; gap:0.6rem;">
                                                ${isTransit ? `
                                                    <button class="btn-action" onclick="updateStatus(${task.id}, 'canceled')" style="flex:1; background:#fee2e2; color:#ef4444; border:1px solid #fecaca;">
                                                        <span class="material-symbols-outlined">block</span> Cancel
                                                    </button>
                                                ` : ''}
                                                <button class="btn-action btn-checkin" style="flex:2; ${(!isTransit && !activeVehicle) ? 'opacity:0.6; cursor:not-allowed;' : ''}" 
                                                        onclick="updateStatus(${task.id}, '${isTransit ? 'completed' : 'in_transit'}', ${isLate})">
                                                    <span class="material-symbols-outlined">${isTransit ? 'check_circle' : 'play_arrow'}</span>
                                                    ${isTransit ? 'Selesai' : 'Mulai Tugas'}
                                                </button>
                                            </div>
                                        </div>
                                    ` : ''}
                                    ${!isCompleted && !isActionable ? `
                                        <div style="text-align:center; padding:0.5rem 0;">
                                            <span class="material-symbols-outlined" style="color:#f59e0b; font-size:36px;">event</span>
                                            <div style="font-size:0.85rem; color:var(--text-sub); margin-top:4px; font-weight:600;">Tugas ini dijadwalkan untuk besok</div>
                                        </div>
                                    ` : ''}
                                    ${isCompleted ? `
                                         <div style="margin-top: 1rem; padding: 1.25rem; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center;">
                                             
                                             <!-- Status Badge -->
                                             <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; ${task.status === 'canceled' ? 'background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;' : 'background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669;'} margin-bottom: 0.85rem;">
                                                 <span class="material-symbols-outlined" style="font-size: 18px;">${task.status === 'canceled' ? 'block' : 'check_circle'}</span>
                                                 Tugas ${task.status === 'canceled' ? 'Dibatalkan' : 'Selesai'}
                                             </div>

                                             <!-- Passenger Info -->
                                             ${task.status !== 'canceled' && task.receiver_name ? `
                                                 <div style="font-size: 0.9rem; color: #1e293b; font-weight: 600; margin-bottom: 0.5rem;">
                                                     Nama Penumpang: <span style="font-weight: 700; color: #0f172a;">${task.receiver_name}</span>
                                                 </div>
                                             ` : ''}

                                             <!-- Proof Gallery Preview (Menampilkan Semua Foto) -->
                                             <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:0.6rem; margin: 0.85rem 0;" id="proof-gallery-${task.id}">
                                                 ${proofGalleryHtml}
                                             </div>

                                             <!-- Notes / Late Reason -->
                                             ${task.driver_notes ? `<div style="font-size: 0.8rem; color: #475569; margin-top: 0.5rem; background: #f1f5f9; padding: 8px 14px; border-radius: 10px; display: inline-block; font-style: italic; border: 1px solid #e2e8f0;"><strong>Ket:</strong> ${task.driver_notes}</div>` : ''}
                                             ${task.late_reason ? `<div style="font-size: 0.8rem; color: #d97706; margin-top: 0.5rem; background: #fffbeb; padding: 8px 14px; border-radius: 10px; display: inline-block; border: 1px solid #fef3c7;"><strong>Alasan:</strong> ${task.late_reason}</div>` : ''}

                                             <!-- Action & Status -->
                                             <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.6rem;">
                                                 <button class="btn-action" onclick="shareTask(${task.id})" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; box-shadow: 0 6px 18px rgba(37, 211, 102, 0.35); font-size: 0.95rem; font-weight: 700; padding: 0.875rem; border-radius: 12px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                     <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                                                         <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.185-.573c.948.517 1.938.808 3.144.809 3.181 0 5.767-2.586 5.768-5.766.001-3.18-2.584-5.766-5.766-5.766zm3.333 7.828c-.144.405-.833.743-1.159.791-.326.048-.739.083-2.133-.49-1.393-.573-2.28-1.937-2.35-2.035-.07-.098-.562-.746-.562-1.435 0-.689.351-1.028.476-1.155.125-.127.272-.159.363-.159.091 0 .181.001.259.005.08.004.185-.03.29.221.105.251.362.881.393.945.031.063.051.137.01.219-.041.082-.061.133-.122.204-.041.082-.061.133-.122.204-.061.072-.128.161-.184.216-.062.062-.127.129-.055.253.072.124.322.532.691.861.475.424.877.556 1.002.618.125.062.198.052.271-.031.073-.083.313-.365.396-.489.083-.125.166-.104.281-.062.114.041.727.343.852.406.124.062.208.094.239.146.031.052.031.297-.113.702zM12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.985-1.308C8.423 21.571 10.134 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18c-1.716 0-3.313-.483-4.664-1.314l-.334-.203-2.906.763.777-2.834-.223-.353C3.655 14.731 3 13.13 3 12c0-4.963 4.037-9 9-9s9 4.037 9 9-4.037 9-9 9z"/>
                                                     </svg>
                                                     Share Laporan
                                                 </button>
                                                 ${task.is_shared == 1 ? `
                                                     <div style="display: flex; align-items: center; justify-content: center; gap: 4px; color: #10b981; font-size: 0.8rem; font-weight: 700; margin-top: 4px;">
                                                         <span class="material-symbols-outlined" style="font-size: 18px;">done_all</span> Sudah dibagikan
                                                     </div>
                                                 ` : `
                                                     <div style="color: #94a3b8; font-size: 0.75rem; font-weight: 600; margin-top: 4px;">Belum dibagikan ke siapapun</div>
                                                 `}
                                             </div>
                                         </div>
                                     ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                document.getElementById('countPending').innerText = countPending;
                document.getElementById('countInTransit').innerText = countTransit;
                document.getElementById('countCompleted').innerText = countCompleted;

                const badgePending = document.getElementById('badgePending');
                if (badgePending) {
                    badgePending.innerHTML = `<span>${countPending}</span>`;
                    badgePending.style.display = countPending > 0 ? 'flex' : 'none';
                }
                const badgeTransit = document.getElementById('badgeTransit');
                if (badgeTransit) {
                    badgeTransit.innerHTML = `<span>${countTransit}</span>`;
                    badgeTransit.style.display = countTransit > 0 ? 'flex' : 'none';
                }
                const badgeCompleted = document.getElementById('badgeCompleted');
                if (badgeCompleted) {
                    badgeCompleted.innerHTML = `<span>${countCompleted}</span>`;
                    badgeCompleted.style.display = countCompleted > 0 ? 'flex' : 'none';
                }

                if (!isFirstLoad) {
                    let hasNewTask = false;
                    for (let id of tempPendingIds) {
                        if (!knownPendingIds.has(id)) {
                            hasNewTask = true;
                            break;
                        }
                    }
                    if (hasNewTask) {
                        playAlarm();
                    }
                }

                knownPendingIds = tempPendingIds;
                isFirstLoad = false;

                container.innerHTML = html || `
                    <div style="text-align:center; padding: 4rem 2rem; color:var(--text-sub);">
                        <span class="material-symbols-outlined" style="font-size:48px; margin-bottom:1rem; display:block; opacity:0.3;">inventory_2</span>
                        <p>Tidak ada tugas untuk kategori ini.</p>
                    </div>
                `;

            } catch (err) {
                console.error(err);
                container.innerHTML = '<p style="color:red; text-align:center;">Gagal memuat tugas.</p>';
            }
        }

        let pendingConfirmCallback = null;

        function showConfirm(title, desc, callback) {
            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmDesc').innerText = desc;
            pendingConfirmCallback = callback;
            document.getElementById('confirmModal').style.display = 'flex';
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        document.getElementById('confirmYesBtn').addEventListener('click', () => {
            closeConfirmModal();
            if (pendingConfirmCallback) pendingConfirmCallback();
        });

        function showToast(msg, type = 'success', persistent = false) {
            const toast = document.getElementById("toast");
            const icon = document.getElementById("toastIcon");
            document.getElementById("toastMsg").innerText = msg;

            toast.className = "toast " + type;
            icon.innerText = type === 'success' ? 'check_circle' : (type === 'warning' ? 'warning' : 'error');

            toast.classList.add("show");

            if (window.toastTimeout) clearTimeout(window.toastTimeout);

            if (!persistent) {
                window.toastTimeout = setTimeout(() => { toast.classList.remove("show"); }, 3000);
            }
        }

        function hideToast() {
            document.getElementById("toast").classList.remove("show");
        }

        let pendingLateId = null;
        let pendingLateStatus = null;

        function closeLateReasonModal() {
            document.getElementById('lateReasonModal').style.display = 'none';
            document.getElementById('lateReasonInput').value = '';
            pendingLateId = null;
        }

        document.getElementById('lateReasonSubmitBtn').addEventListener('click', () => {
            const reason = document.getElementById('lateReasonInput').value.trim();
            if (!reason) {
                alert('Alasan keterlambatan harus diisi!');
                document.getElementById('lateReasonInput').focus();
                return;
            }
            const id = pendingLateId;
            const st = pendingLateStatus;
            closeLateReasonModal();
            openProofModal(id, st);
        });

        async function updateStatus(id, status, isLate = false) {
            if (status === 'in_transit' && !activeVehicle) {
                showToast('Belum Memilih Mobil!', 'warning');
                showVehicleModal();
                return;
            }
            if (status === 'in_transit') {
                openSpeedoModal(id);
                return;
            }

            if (status === 'completed' || status === 'canceled') {
                if (isLate && status === 'completed') {
                    pendingLateId = id;
                    pendingLateStatus = status;
                    document.getElementById('lateReasonModal').style.display = 'flex';
                } else {
                    openProofModal(id, status);
                }
            } else {
                executeUpdateStatus(id, status);
            }
        }

        async function executeUpdateStatus(id, status, late_reason = '', photoBlobs = [], receiverName = '', driver_notes = '', speedoBlob = null, speedoNum = '', speedoEndBlob = null, speedoEndNum = '') {
            const formData = new FormData();
            formData.append('delivery_id', id);
            formData.append('status', status);
            if (late_reason) formData.append('late_reason', late_reason);
            if (receiverName) formData.append('receiver_name', receiverName);
            if (driver_notes) formData.append('driver_notes', driver_notes);
            if (speedoNum) formData.append('speedometer_start_num', speedoNum);
            if (speedoBlob) formData.append('speedometer_start_photo', speedoBlob, `speedo_${id}_${Date.now()}.jpg`);
            if (speedoEndNum) formData.append('speedometer_end_num', speedoEndNum);
            if (speedoEndBlob) formData.append('speedometer_end_photo', speedoEndBlob, `speedo_end_${id}_${Date.now()}.jpg`);

            if (photoBlobs.length > 0) {
                photoBlobs.forEach((blob, i) => {
                    formData.append('surat_jalan_file[]', blob, `proof_${id}_${i}_${Date.now()}.jpg`);
                });
            }

            try {
                const res = await fetch(`${API_URL}?action=update_status`, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    if (status === 'completed') {
                        showToast('Tugas berhasil diselesaikan!', 'success');
                        closeProofModal();
                    } else if (status === 'canceled') {
                        showToast('Laporan cancel dikirim', 'warning');
                        closeProofModal();
                    } else if (status === 'in_transit') {
                        showToast('Status diperbarui: Sedang Dalam Perjalanan', 'success');
                    }
                    checkVehicleAssignment();
                    fetchCalendarSummary(); // update dots
                } else {
                    showToast('Gagal merespon server', 'error');
                }
            } catch (err) {
                showToast('Gagal memperbarui status', 'error');
            }
        }

        let currentProofTaskId = null;
        let currentStatusMode = 'completed';
        let rawProofDataUrls = [];
        let currentProofTaskData = null;
        let currentProofTaskPromise = null;

        function openProofModal(id, mode = 'completed') {
            currentProofTaskId = id;
            currentStatusMode = mode;

            let task = allTasks ? allTasks.find(t => t.id == id) : null;
            currentProofTaskData = task || null;

            const titleEl = document.querySelector('#proofModal h3');
            const receiverGroup = document.getElementById('receiverNameInput').closest('.form-group');
            const submitBtn = document.getElementById('proofSubmitBtn');

            if (mode === 'canceled') {
                titleEl.innerText = 'Laporkan Kendala / Cancel';
                receiverGroup.style.display = 'none';
                document.getElementById('speedoEndSection').style.display = 'none';
                submitBtn.innerText = 'Kirim Laporan Cancel';
                submitBtn.style.background = '#ef4444';
            } else {
                titleEl.innerText = 'Penyelesaian Perjalanan';
                receiverGroup.style.display = 'none';
                document.getElementById('speedoEndSection').style.display = 'block';
                submitBtn.innerText = 'Selesai';
                submitBtn.style.background = 'var(--primary)';

                const labelEl = document.getElementById('receiverLabel');
                const inputEl = document.getElementById('receiverNameInput');
                if (labelEl && inputEl) {
                    labelEl.innerText = 'Nama Karyawan / Penumpang (Opsional)';
                    inputEl.placeholder = 'Contoh: Pak Budi & Tim Marketing';
                }
            }

            if (task) {
                document.getElementById('proofTargetName').innerText = `Tujuan: ${formatLocationDisplay(task.destination_name)}`;
            } else {
                document.getElementById('proofTargetName').innerText = '';
            }
            document.getElementById('proofModal').style.display = 'flex';
            resetProofForm();

            currentProofTaskPromise = fetch(`${API_URL}?action=get_delivery&id=${id}`)
                .then(res => res.json())
                .then(dbTask => {
                    if (dbTask && !dbTask.error) {
                        currentProofTaskData = dbTask;
                        if (dbTask.destination_name) {
                            document.getElementById('proofTargetName').innerText = `Tujuan: ${formatLocationDisplay(dbTask.destination_name)}`;
                        }
                        const labelElDb = document.getElementById('receiverLabel');
                        const inputElDb = document.getElementById('receiverNameInput');
                        if (labelElDb && inputElDb && mode !== 'canceled') {
                            labelElDb.innerText = 'Nama Karyawan / Penumpang (Opsional)';
                            inputElDb.placeholder = 'Contoh: Pak Budi & Tim Marketing';
                        }
                    }
                    return dbTask;
                })
                .catch(err => {
                    console.error("Error fetching task details:", err);
                    return null;
                });
        }

        function closeProofModal() {
            document.getElementById('proofModal').style.display = 'none';
            resetProofForm();
        }

        function previewSpeedoEndPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('speedoEndImgPreview').src = e.target.result;
                    document.getElementById('speedoEndPreviewBox').style.display = 'block';
                    document.getElementById('speedoEndCamBtnText').innerText = 'Foto Speedometer Akhir Diambil (Foto Ulang)';
                    validateProof();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetProofForm() {
            document.getElementById('proofInput').value = '';
            document.getElementById('proofCamera').value = '';
            document.getElementById('proofUpload').value = '';
            document.getElementById('photoGallery').innerHTML = '';
            document.getElementById('receiverNameInput').value = '';
            document.getElementById('driverNoteInput').value = '';
            document.getElementById('speedoEndPhoto').value = '';
            document.getElementById('speedoEndNum').value = '';
            document.getElementById('speedoEndPreviewBox').style.display = 'none';
            document.getElementById('speedoEndCamBtnText').innerText = 'Ambil Foto Speedometer Akhir';
            document.getElementById('proofSubmitBtn').disabled = true;
            document.getElementById('proofSubmitBtn').style.opacity = '0.5';
            document.getElementById('proofSubmitBtn').innerText = currentStatusMode === 'canceled' ? 'Kirim Laporan Cancel' : 'Selesai';
            document.getElementById('photoBtnText').innerText = '';
            rawProofDataUrls = [];
        }

        async function handleProofChange(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = async (e) => {
                rawProofDataUrls.push(e.target.result);
                renderGallery();
                validateProof();
            };
            reader.readAsDataURL(file);
            input.value = ''; // allow same file
        }

        async function handleProofChangeMulti(input) {
            if (!input.files || input.files.length === 0) return;
            const files = Array.from(input.files);

            if (files.length > 1) showToast(`Memproses ${files.length} foto...`, 'info');

            for (const file of files) {
                await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = async (e) => {
                        rawProofDataUrls.push(e.target.result);
                        resolve();
                    };
                    reader.readAsDataURL(file);
                });
            }
            renderGallery();
            validateProof();
            input.value = '';
        }

        async function watermarkImage(img, receiverName, customTitle = '') {
            return new Promise((resolve) => {
                const canvas = document.getElementById('watermarkCanvas');
                const ctx = canvas.getContext('2d');

                const maxW = 1200;
                let w = img.width;
                let h = img.height;
                if (w > maxW) {
                    h = h * (maxW / w);
                    w = maxW;
                }
                canvas.width = w;
                canvas.height = h;

                ctx.drawImage(img, 0, 0, w, h);

                const overlayH = h * 0.22;
                const gradient = ctx.createLinearGradient(0, h - overlayH, 0, h);
                gradient.addColorStop(0, 'rgba(0,0,0,0)');
                gradient.addColorStop(0.3, 'rgba(0,0,0,0.65)');
                gradient.addColorStop(1, 'rgba(0,0,0,0.85)');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, h - overlayH, w, overlayH);

                const fontSize = Math.max(14, Math.round(w * 0.025));
                ctx.fillStyle = 'white';
                ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                ctx.shadowColor = 'rgba(0,0,0,0.6)';
                ctx.shadowBlur = 4;
                ctx.shadowOffsetX = 2;
                ctx.shadowOffsetY = 2;

                const task = currentProofTaskData || (window.allTasks ? window.allTasks.find(t => t.id == (currentProofTaskId || document.getElementById('speedoTaskId')?.value)) : null);
                const now = new Date();
                const dateStr = now.toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' });
                const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const driverName = '<?php echo addslashes($_SESSION["name"] ?? $_SESSION["username"] ?? "Driver"); ?>';

                const lat = currentPos && currentPos.lat ? currentPos.lat.toFixed(6) : '0.000000';
                const lng = currentPos && currentPos.lng ? currentPos.lng.toFixed(6) : '0.000000';

                const receiverLabelStr = 'PENUMPANG';

                const cleanLocName = (name) => {
                    if (!name) return '-';
                    name = name.trim();
                    if (name.startsWith('http://') || name.startsWith('https://')) return 'LINK GOOGLE MAPS';
                    return name;
                };

                const titleHeader = customTitle ? `📌 ${customTitle}` : `📌 BUKTI PENGIRIMAN / PENYELESAIAN`;

                const lines = [
                    titleHeader,
                    `🏁 DARI: ${(task && task.origin_name) ? cleanLocName(task.origin_name) : '-'}`,
                    `🏁 TUJUAN: ${(task && task.destination_name) ? cleanLocName(task.destination_name) : '-'}`,
                    `👤 DRIVER: ${driverName} ${receiverName ? '| ' + receiverLabelStr + ': ' + receiverName.trim() : ''}`,
                    `📅 ${dateStr} | 🕒 ${timeStr} | 🌍 GPS: ${lat}, ${lng}`
                ];

                let padding = fontSize * 1.2;
                lines.reverse().forEach((line, i) => {
                    ctx.fillText(line, padding, h - (padding + (i * fontSize * 1.4)));
                });

                // --- TOP-RIGHT COPYRIGHT WATERMARK (IMS) ---
                const cpFontSize = Math.max(12, Math.round(w * 0.022));
                const cpText = '© IMS';
                ctx.font = `bold ${cpFontSize}px Inter, sans-serif`;
                const textWidth = ctx.measureText(cpText).width;

                const cpPaddingX = cpFontSize * 0.8;
                const cpPaddingY = cpFontSize * 0.4;
                const rectW = textWidth + (cpPaddingX * 2);
                const rectH = cpFontSize + (cpPaddingY * 2);
                const rectX = w - rectW - (fontSize * 0.8);
                const rectY = fontSize * 0.8;
                const cornerRadius = Math.min(8, rectH / 2);

                ctx.save();
                ctx.shadowColor = 'rgba(0,0,0,0.5)';
                ctx.shadowBlur = 6;
                ctx.fillStyle = 'rgba(15, 23, 42, 0.80)';
                ctx.beginPath();
                if (ctx.roundRect) {
                    ctx.roundRect(rectX, rectY, rectW, rectH, cornerRadius);
                } else {
                    ctx.rect(rectX, rectY, rectW, rectH);
                }
                ctx.fill();

                ctx.strokeStyle = 'rgba(56, 189, 248, 0.7)';
                ctx.lineWidth = 1.5;
                ctx.stroke();

                ctx.fillStyle = '#38bdf8';
                ctx.shadowColor = 'transparent';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(cpText, rectX + cpPaddingX, rectY + (rectH / 2) + 1);
                ctx.restore();

                canvas.toBlob((blob) => {
                    resolve(blob);
                }, 'image/jpeg', 0.85);
            });
        }

        function renderGallery() {
            const container = document.getElementById('photoGallery');
            if (container) {
                container.innerHTML = rawProofDataUrls.map((url, i) => `
                    <div style="position:relative; aspect-ratio:1/1;">
                        <img src="${url}" onclick="showImagePopup('${url}')" style="width:100%; height:100%; object-fit:cover; border-radius:8px; border:1px solid var(--border); cursor:pointer;">
                        <button onclick="removePhoto(${i})" style="position:absolute; top:-4px; right:-4px; width:20px; height:20px; border-radius:50%; background:#ef4444; color:white; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10;">
                            <span class="material-symbols-outlined" style="font-size:14px;">close</span>
                        </button>
                    </div>
                `).join('');
            }
        }

        function removePhoto(index) {
            rawProofDataUrls.splice(index, 1);
            renderGallery();
            validateProof();
        }

        function validateProof() {
            const btn = document.getElementById('proofSubmitBtn');
            const note = document.getElementById('driverNoteInput').value.trim();
            const speedoEndNum = document.getElementById('speedoEndNum').value.trim();
            const speedoEndFile = document.getElementById('speedoEndPhoto').files;

            let isValid = false;
            if (currentStatusMode === 'canceled') {
                isValid = note.length >= 3;
            } else {
                isValid = speedoEndNum !== '' && speedoEndFile.length > 0 && rawProofDataUrls.length > 0;
            }

            btn.disabled = !isValid;
            btn.style.opacity = isValid ? '1' : '0.5';
            btn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }

        async function submitProof() {
            const id = currentProofTaskId;
            const status = currentStatusMode;
            const receiverName = status === 'canceled' ? '' : document.getElementById('receiverNameInput').value;
            const driverNotes = document.getElementById('driverNoteInput').value;
            const speedoEndFile = document.getElementById('speedoEndPhoto').files[0] || null;
            const speedoEndNum = document.getElementById('speedoEndNum').value.trim();

            const btn = document.getElementById('proofSubmitBtn');
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            if (currentProofTaskPromise) {
                try {
                    await currentProofTaskPromise;
                } catch (e) {
                    console.error("Error waiting for task data fetch:", e);
                }
            }

            let finalBlobs = [];
            for (const dataUrl of rawProofDataUrls) {
                const img = new Image();
                img.src = dataUrl;
                await new Promise(res => img.onload = res);
                const blob = await watermarkImage(img, receiverName, 'BUKTI PENYELESAIAN TUGAS');
                finalBlobs.push(blob);
            }

            let finalSpeedoEndBlob = speedoEndFile;
            if (speedoEndFile) {
                try {
                    const img = new Image();
                    img.src = URL.createObjectURL(speedoEndFile);
                    await new Promise(res => img.onload = res);
                    finalSpeedoEndBlob = await watermarkImage(img, receiverName, 'SPEEDOMETER AKHIR');
                } catch (e) {
                    console.warn('Watermark speedo end failed:', e);
                }
            }

            if (pendingLateId == id && status === 'completed') {
                const reason = document.getElementById('lateReasonInput').value;
                executeUpdateStatus(id, status, reason, finalBlobs, receiverName, driverNotes, null, '', finalSpeedoEndBlob, speedoEndNum);
            } else {
                executeUpdateStatus(id, status, '', finalBlobs, receiverName, driverNotes, null, '', finalSpeedoEndBlob, speedoEndNum);
            }
        }

        function calcTaskDuration(startStr, endStr, durationStr) {
            if (startStr && endStr) {
                const s = new Date(startStr.replace(/-/g, '/'));
                const e = new Date(endStr.replace(/-/g, '/'));
                if (!isNaN(s) && !isNaN(e)) {
                    const diffMs = Math.max(0, e - s);
                    const totalSec = Math.floor(diffMs / 1000);
                    const hours = Math.floor(totalSec / 3600);
                    const minutes = Math.floor((totalSec % 3600) / 60);
                    const seconds = totalSec % 60;

                    let parts = [];
                    if (hours > 0) parts.push(`${hours} Jam`);
                    if (minutes > 0 || hours > 0) parts.push(`${minutes} Menit`);
                    parts.push(`${seconds} Detik`);
                    return parts.join(' ');
                }
            }
            if (durationStr) {
                return durationStr;
            }
            return '-';
        }

        async function combinePhotosToSingleFile(photoItems) {
            if (!photoItems || photoItems.length === 0) return null;

            if (photoItems.length === 1) {
                try {
                    const res = await fetch(photoItems[0].path);
                    if (!res.ok) return null;
                    const blob = await res.blob();
                    return new File([blob], `bukti_1.jpg`, { type: 'image/jpeg' });
                } catch (e) {
                    return null;
                }
            }

            try {
                const loadedImages = [];
                for (const item of photoItems) {
                    try {
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        img.src = item.path;
                        await new Promise((resolve) => {
                            img.onload = resolve;
                            img.onerror = () => resolve(null);
                        });
                        if (img.width && img.height) {
                            loadedImages.push({ img, label: item.label || '' });
                        }
                    } catch (e) { }
                }

                if (loadedImages.length === 0) return null;
                if (loadedImages.length === 1) {
                    const res = await fetch(photoItems[0].path);
                    if (!res.ok) return null;
                    const blob = await res.blob();
                    return new File([blob], `bukti_1.jpg`, { type: 'image/jpeg' });
                }

                const targetTileWidth = 600;
                const tileMargin = 12;
                const headerHeight = 30;

                const tiles = loadedImages.map(item => {
                    const scale = targetTileWidth / item.img.width;
                    return {
                        img: item.img,
                        label: item.label,
                        w: targetTileWidth,
                        h: Math.round(item.img.height * scale)
                    };
                });

                let canvasWidth, canvasHeight;
                let positions = [];

                if (tiles.length === 2) {
                    const maxHeight = Math.max(tiles[0].h, tiles[1].h);
                    canvasWidth = (targetTileWidth * 2) + (tileMargin * 3);
                    canvasHeight = maxHeight + headerHeight + (tileMargin * 2);

                    positions = [
                        { x: tileMargin, y: tileMargin + headerHeight, w: tiles[0].w, h: tiles[0].h },
                        { x: targetTileWidth + (tileMargin * 2), y: tileMargin + headerHeight, w: tiles[1].w, h: tiles[1].h }
                    ];
                } else {
                    const cols = 2;
                    const rows = Math.ceil(tiles.length / cols);
                    let maxRowHeights = [];

                    for (let r = 0; r < rows; r++) {
                        let rH = 0;
                        for (let c = 0; c < cols; c++) {
                            const idx = r * cols + c;
                            if (tiles[idx]) rH = Math.max(rH, tiles[idx].h);
                        }
                        maxRowHeights.push(rH);
                    }

                    canvasWidth = (targetTileWidth * cols) + (tileMargin * (cols + 1));
                    let totalH = tileMargin;
                    maxRowHeights.forEach(rH => {
                        totalH += rH + headerHeight + tileMargin;
                    });
                    canvasHeight = totalH;

                    let curY = tileMargin;
                    for (let r = 0; r < rows; r++) {
                        for (let c = 0; c < cols; c++) {
                            const idx = r * cols + c;
                            if (tiles[idx]) {
                                positions.push({
                                    x: tileMargin + c * (targetTileWidth + tileMargin),
                                    y: curY + headerHeight,
                                    w: tiles[idx].w,
                                    h: tiles[idx].h
                                });
                            }
                        }
                        curY += maxRowHeights[r] + headerHeight + tileMargin;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = canvasWidth;
                canvas.height = canvasHeight;
                const ctx = canvas.getContext('2d');

                // Background
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(0, 0, canvasWidth, canvasHeight);

                tiles.forEach((tile, i) => {
                    const pos = positions[i];

                    // Card background
                    ctx.fillStyle = '#1e293b';
                    ctx.fillRect(pos.x, pos.y - headerHeight, pos.w, pos.h + headerHeight);

                    // Label text
                    ctx.fillStyle = '#38bdf8';
                    ctx.font = 'bold 15px sans-serif';
                    ctx.fillText(tile.label.toUpperCase(), pos.x + 10, pos.y - 9);

                    // Image
                    ctx.drawImage(tile.img, pos.x, pos.y, pos.w, pos.h);

                    // Border
                    ctx.strokeStyle = '#334155';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(pos.x, pos.y, pos.w, pos.h);
                });

                return new Promise(resolve => {
                    canvas.toBlob(blob => {
                        if (!blob) resolve(null);
                        else resolve(new File([blob], `bukti_combined_${Date.now()}.jpg`, { type: 'image/jpeg' }));
                    }, 'image/jpeg', 0.90);
                });
            } catch (err) {
                console.error('Error combining photos:', err);
                return null;
            }
        }

        async function fetchPhotosAsFiles(photoItems) {
            let files = [];
            for (let i = 0; i < photoItems.length; i++) {
                try {
                    const item = photoItems[i];
                    const res = await fetch(item.path);
                    const blob = await res.blob();
                    const ext = blob.type ? (blob.type.split('/')[1] || 'jpg') : 'jpg';
                    const filename = `foto_${i + 1}_${Date.now()}.${ext}`;
                    files.push(new File([blob], filename, { type: blob.type || 'image/jpeg' }));
                } catch (e) {
                    console.warn("Gagal membaca foto:", photoItems[i], e);
                }
            }
            return files;
        }

        async function shareTask(taskId) {
            const task = allTasks.find(t => t.id == taskId);
            if (!task) return;

            const driverName = '<?php echo addslashes($_SESSION["name"] ?? $_SESSION["username"] ?? "Driver"); ?>';
            const typeText = task.task_type === 'antar' ? 'ANTAR' : 'JEMPUT';
            const statusTitle = task.status === 'canceled' ? 'LAPORAN KENDALA / CANCEL' : `LAPORAN DRIVER - ${typeText}`;

            const spidoStart = task.speedometer_start_num ? Number(task.speedometer_start_num).toLocaleString('id-ID') + ' KM' : '-';
            const spidoEnd = task.speedometer_end_num ? Number(task.speedometer_end_num).toLocaleString('id-ID') + ' KM' : '-';
            const spidoDistance = (task.speedometer_start_num && task.speedometer_end_num)
                ? Number(task.speedometer_end_num - task.speedometer_start_num).toLocaleString('id-ID') + ' KM'
                : '-';
            const passengerName = task.receiver_name || task.passenger_name || '-';
            const startTime = task.start_time || '-';
            const endTime = task.end_time || '-';
            const durationText = calcTaskDuration(task.start_time, task.end_time, task.duration);

            const caption = `📌 *${statusTitle}*\n\n` +
                `🏠 *ASAL:* ${task.origin_name}\n` +
                `🏁 *TUJUAN:* ${task.destination_name}\n` +
                `👤 *PENUMPANG:* ${passengerName}\n` +
                `⏱️ *SPIDOMETER AWAL:* ${spidoStart}\n` +
                `⏱️ *SPIDOMETER AKHIR:* ${spidoEnd}\n` +
                `📊 *JARAK TEMPUH:* ${spidoDistance}\n` +
                `🟢 *WAKTU MULAI:* ${startTime}\n` +
                `🔴 *WAKTU SAMPAI:* ${endTime}\n` +
                `⏳ *DURASI:* ${durationText}\n` +
                (task.driver_notes ? `📝 *KET:* ${task.driver_notes}\n` : '') +
                `\n_Dikirim oleh Driver: ${driverName} Via IMS-Mobile_`;

            let photoItems = getAllTaskPhotos(task);

            if (navigator.share) {
                try {
                    showToast('Menyiapkan gambar...', 'info');
                    const photoFiles = await fetchPhotosAsFiles(photoItems);

                    const shareData = {
                        title: 'Laporan Driver',
                        text: caption
                    };

                    if (photoFiles.length > 0 && navigator.canShare && navigator.canShare({ files: photoFiles })) {
                        shareData.files = photoFiles;
                    }

                    await navigator.share(shareData);

                    const formData = new FormData();
                    formData.append('delivery_id', taskId);
                    await fetch(`${API_URL}?action=mark_shared`, { method: 'POST', body: formData });

                    showToast('Berhasil di-share!');
                    loadTasks();
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Error sharing:', err);
                        openWaFallback(caption, taskId);
                    }
                }
            } else {
                openWaFallback(caption, taskId);
            }
        }

        async function openWaFallback(caption, taskId) {
            const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(caption)}`;
            window.open(waUrl, '_blank');

            // Mark as shared in DB
            const formData = new FormData();
            formData.append('delivery_id', taskId);
            await fetch(`${API_URL}?action=mark_shared`, { method: 'POST', body: formData });

            showToast('Membuka WhatsApp...');
            loadTasks();
        }

        function playAlarm() {
            const modal = document.getElementById('alarmModal');
            const audio = document.getElementById('alarmAudio');
            modal.style.display = 'flex';
            audio.play().catch(e => console.log('Autoplay prevented by browser', e));
        }

        function stopAlarm() {
            const modal = document.getElementById('alarmModal');
            const audio = document.getElementById('alarmAudio');
            modal.style.display = 'none';
            audio.pause();
            audio.currentTime = 0; // Reset sound
        }

        // Initialize
        checkVehicleAssignment();
        fetchCalendarSummary();

        setInterval(() => {
            const anyModalOpen = document.querySelectorAll('.modal-overlay[style*="flex"], .confirm-modal-overlay[style*="flex"]').length > 0;
            if (!anyModalOpen) {
                checkVehicleAssignment();
                fetchCalendarSummary();
            }
        }, 20000);

        function openAdminWa(phone, name) {
            if (!phone) {
                showToast('Nomor WA Admin tidak tersedia', 'error');
                return;
            }
            let cleanPhone = phone.replace(/\D/g, '');
            if (cleanPhone.startsWith('0')) cleanPhone = '62' + cleanPhone.substring(1);
            const msg = `Halo ${name}, saya driver ingin bertanya mengenai tugas saya.`;
            window.open(`https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(msg)}`, '_blank');
        }

        function parseFile(fileStr) {
            if (!fileStr) return null;
            try {
                if (fileStr.startsWith('[') || fileStr.startsWith('{')) {
                    const parsed = JSON.parse(fileStr);
                    return Array.isArray(parsed) ? parsed[0] : (parsed.file || parsed);
                }
            } catch (e) { }
            return fileStr;
        }

        function viewSjPhoto(taskId) {
            const task = allTasks.find(t => t.id == taskId);
            if (!task) { showToast('Data tugas tidak ditemukan', 'error'); return; }

            let files = [];

            if (Array.isArray(task.surat_jalan_file) && task.surat_jalan_file.length > 0) {
                files = task.surat_jalan_file;
            }
            else if (Array.isArray(task.request_sj_file_arr) && task.request_sj_file_arr.length > 0) {
                files = task.request_sj_file_arr;
            }
            else if (task.surat_jalan_file && typeof task.surat_jalan_file === 'string') {
                try {
                    const parsed = JSON.parse(task.surat_jalan_file);
                    files = Array.isArray(parsed) ? parsed : [parsed];
                } catch (e) {
                    files = [task.surat_jalan_file];
                }
            }

            if (files.length === 0) {
                showToast('Tidak ada foto SJ', 'error');
                return;
            }

            if (files.length === 1) {
                showImagePopup('uploads/' + files[0]);
            } else {
                showSjGallery(files);
            }
        }

        function showSjGallery(files) {
            let existing = document.getElementById('sjGalleryModal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'sjGalleryModal';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:100001;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:1.5rem;backdrop-filter:blur(5px);';
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

            const header = document.createElement('div');
            header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;width:100%;max-width:480px;margin-bottom:1rem;';
            header.innerHTML = `
                <span style="color:white;font-weight:800;font-size:1rem;">📄 Foto Surat Jalan (${files.length})</span>
                <button onclick="document.getElementById('sjGalleryModal').remove()" style="background:rgba(255,255,255,0.2);border:none;color:white;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>`;

            const grid = document.createElement('div');
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;width:100%;max-width:480px;overflow-y:auto;max-height:70vh;';

            files.forEach((f, i) => {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'aspect-ratio:1/1;border-radius:12px;overflow:hidden;border:2px solid rgba(255,255,255,0.15);cursor:pointer;';
                wrapper.innerHTML = `<img src="uploads/${f}" style="width:100%;height:100%;object-fit:cover;" alt="Foto SJ ${i + 1}" onerror="this.closest('div').style.display='none'">`;
                wrapper.onclick = () => showImagePopup('uploads/' + f);
                grid.appendChild(wrapper);
            });

            modal.appendChild(header);
            modal.appendChild(grid);
            document.body.appendChild(modal);
        }


        function viewGoodsPhoto(taskId) {
            const task = allTasks.find(t => t.id == taskId);
            if (!task) { showToast('Data tugas tidak ditemukan', 'error'); return; }

            let files = [];

            if (Array.isArray(task.request_goods)) {
                files = files.concat(task.request_goods);
            }

            if (task.goods_file) {
                try {
                    const parsed = JSON.parse(task.goods_file);
                    if (Array.isArray(parsed)) {
                        files = files.concat(parsed);
                    } else {
                        files.push(parsed);
                    }
                } catch (e) {
                    files.push(task.goods_file);
                }
            }

            if (task.photo_file) {
                files.push(task.photo_file);
            }

            files = [...new Set(files)];

            if (files.length === 0) {
                showToast('Tidak ada foto barang', 'error');
                return;
            }

            if (files.length === 1) {
                showImagePopup('uploads/' + files[0]);
            } else {
                showGoodsGallery(files);
            }
        }

        function showGoodsGallery(files) {
            let existing = document.getElementById('goodsGalleryModal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'goodsGalleryModal';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:100001;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:1.5rem;backdrop-filter:blur(5px);';
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

            const header = document.createElement('div');
            header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;width:100%;max-width:480px;margin-bottom:1rem;';
            header.innerHTML = `
                <span style="color:white;font-weight:800;font-size:1rem;">📦 Foto Barang (${files.length})</span>
                <button onclick="document.getElementById('goodsGalleryModal').remove()" style="background:rgba(255,255,255,0.2);border:none;color:white;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>`;

            const grid = document.createElement('div');
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;width:100%;max-width:480px;overflow-y:auto;max-height:70vh;';

            files.forEach((f, i) => {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'aspect-ratio:1/1;border-radius:12px;overflow:hidden;border:2px solid rgba(255,255,255,0.15);cursor:pointer;';
                wrapper.innerHTML = `<img src="uploads/${f}" style="width:100%;height:100%;object-fit:cover;" alt="Foto barang ${i + 1}" onerror="this.closest('div').style.display='none'">`;
                wrapper.onclick = () => showImagePopup('uploads/' + f);
                grid.appendChild(wrapper);
            });

            modal.appendChild(header);
            modal.appendChild(grid);
            document.body.appendChild(modal);
        }


        updateLocation();
        setInterval(updateLocation, 30000); 
    </script>
    <div id="imagePopup" class="modal-overlay" onclick="if(event.target===this)closeImagePopup()"
        style="z-index: 100000; display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; backdrop-filter:blur(5px);">
        <div
            style="position:relative; max-width:90%; max-height:90%; display:flex; flex-direction:column; align-items:center;">
            <button onclick="closeImagePopup()"
                style="position:absolute; top:-40px; right:0; background:none; border:none; color:white; cursor:pointer;">
                <span class="material-symbols-outlined" style="font-size:32px;">close</span>
            </button>
            <img id="popupImg" src=""
                style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 20px 50px rgba(0,0,0,0.5); object-fit:contain;">
        </div>
    </div>

    <script>
        function showImagePopup(url) {
            const popup = document.getElementById('imagePopup');
            const img = document.getElementById('popupImg');
            if (!url || url.includes('undefined') || url.includes('null')) {
                showToast('Foto tidak tersedia', 'error');
                return;
            }
            img.src = url;
            popup.style.display = 'flex';
        }

        function closeImagePopup() {
            document.getElementById('imagePopup').style.display = 'none';
        }

        function confirmReleaseVehicle() {
            document.getElementById('releasePhotoInput').value = '';
            document.getElementById('releaseNotesInput').value = '';
            document.getElementById('releasePreviewBox').style.display = 'none';
            document.getElementById('releaseCamBtnText').innerText = 'Ambil Foto Kunci';
            document.getElementById('releaseVehicleModal').style.display = 'flex';
        }

        function previewReleasePhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('releaseImgPreview').src = e.target.result;
                    document.getElementById('releasePreviewBox').style.display = 'block';
                    document.getElementById('releaseCamBtnText').innerText = 'Foto Berhasil Diambil (Foto Ulang)';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function submitReleaseVehicle(evt) {
            const fileInput = document.getElementById('releasePhotoInput');
            const notesInput = document.getElementById('releaseNotesInput').value.trim();

            if (!fileInput.files.length) {
                showToast('Foto Penyerahan Kunci Wajib Diisi!', 'warning');
                return;
            }
            if (!notesInput) {
                showToast('Catatan Penyerahan Kunci Wajib Diisi!', 'warning');
                return;
            }

            const btn = evt ? evt.target : null;
            let origText = '';
            if (btn) {
                origText = btn.innerText;
                btn.innerText = 'Memproses...';
                btn.disabled = true;
            }

            const driver_id = '<?php echo $_SESSION['user_id']; ?>';
            const formData = new FormData();
            formData.append('driver_id', driver_id);
            formData.append('release_notes', notesInput);
            formData.append('release_photo', fileInput.files[0]);

            try {
                const res = await fetch(`${API_URL}?action=release_vehicle`, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('releaseVehicleModal').style.display = 'none';
                    showToast('Kendaraan telah dilepaskan', 'success');
                    checkVehicleAssignment();
                } else {
                    showToast(result.error || 'Gagal melepas kendaraan', 'error');
                }
            } catch (err) {
                showToast('Gagal melepas kendaraan', 'error');
            } finally {
                if (btn) {
                    btn.innerText = origText;
                    btn.disabled = false;
                }
            }
        }
    </script>
    <?php include_once __DIR__ . '/profile_modal.php'; ?>

    <!-- Release Vehicle Modal (Lepas Kendaraan & Foto Kunci) -->
    <div id="releaseVehicleModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); z-index:9999; justify-content:center; align-items:center; padding:1.5rem;">
        <div
            style="background:rgba(255,255,255,0.95); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.8); padding:1.75rem 1.5rem; border-radius:1.5rem; width:100%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.15);">
            <div
                style="width:50px; height:50px; background:rgba(239,68,68,0.1); border-radius:14px; display:flex; align-items:center; justify-content:center; color:#ef4444; margin:0 auto 0.75rem;">
                <span class="material-symbols-outlined" style="font-size:28px;">key_off</span>
            </div>
            <h3 style="margin:0 0 0.25rem; font-size:1.15rem; font-weight:800; color:#1c1c1e;">Lepas Kendaraan & Penyerahan Kunci</h3>
            <p style="margin:0 0 1.25rem; font-size:0.8rem; color:#8e8e93;">Upload foto bukti penyerahan kunci dan berikan catatan.</p>

            <div style="margin-bottom:1.25rem; text-align:left;">
                <label
                    style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.4rem;">1. Foto Penyerahan Kunci *</label>

                <input type="file" id="releasePhotoInput" accept="image/*" capture="environment" style="display:none;"
                    onchange="previewReleasePhoto(this)">

                <button type="button" onclick="document.getElementById('releasePhotoInput').click()"
                    style="width:100%; padding:0.85rem; background:rgba(239,68,68,0.08); border:1.5px dashed #ef4444; border-radius:12px; color:#ef4444; font-weight:700; font-size:0.875rem; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-family:inherit;">
                    <span class="material-symbols-outlined" style="font-size:20px;">photo_camera</span>
                    <span id="releaseCamBtnText">Ambil Foto Kunci</span>
                </button>

                <div id="releasePreviewBox" style="display:none; margin-top:0.75rem; text-align:center;">
                    <img id="releaseImgPreview" src=""
                        style="width:100%; max-height:160px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
                </div>
            </div>

            <div style="margin-bottom:1.5rem; text-align:left;">
                <label
                    style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.4rem;">2. Catatan Penyerahan Kunci *</label>
                <textarea id="releaseNotesInput" placeholder="Contoh: Kunci ditaruh di kotak kunci pool admin"
                    rows="2"
                    style="width:100%; padding:0.8rem 1rem; border:1.5px solid var(--border); border-radius:12px; font-size:0.9rem; font-weight:600; font-family:inherit; box-sizing:border-box; resize:none;"></textarea>
            </div>

            <div style="display:flex; gap:0.75rem;">
                <button type="button" onclick="document.getElementById('releaseVehicleModal').style.display='none'"
                    style="flex:1; padding:0.85rem; background:#f1f5f9; border:none; border-radius:12px; color:#64748b; font-weight:700; font-size:0.9rem; cursor:pointer; font-family:inherit;">Batal</button>
                <button type="button" onclick="submitReleaseVehicle(event)"
                    style="flex:1; padding:0.85rem; background:#ef4444; color:white; border:none; border-radius:12px; font-weight:700; font-size:0.9rem; cursor:pointer; font-family:inherit; box-shadow:0 4px 16px rgba(239,68,68,0.3);">Lepas Mobil</button>
            </div>
        </div>
    </div>

    <!-- Speedometer Modal -->
    <div id="speedoModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); z-index:9999; justify-content:center; align-items:center; padding:1.5rem;">
        <div
            style="background:rgba(255,255,255,0.95); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.8); padding:1.75rem 1.5rem; border-radius:1.5rem; width:100%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.15);">
            <div
                style="width:50px; height:50px; background:rgba(0,122,255,0.1); border-radius:14px; display:flex; align-items:center; justify-content:center; color:var(--primary); margin:0 auto 0.75rem;">
                <span class="material-symbols-outlined" style="font-size:28px;">speed</span>
            </div>
            <h3 style="margin:0 0 0.25rem; font-size:1.15rem; font-weight:800; color:#1c1c1e;">Input Speedometer Mobil
            </h3>
            <p style="margin:0 0 1.25rem; font-size:0.8rem; color:#8e8e93;">Ambil foto speedometer & input angka
                kilometer sebelum jalan</p>

            <div style="margin-bottom:1.25rem; text-align:left;">
                <label
                    style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.4rem;">1.
                    Foto Spidometer (Wajib Foto) *</label>

                <!-- Direct Camera Input -->
                <input type="file" id="speedoPhoto" accept="image/*" capture="environment" style="display:none;"
                    onchange="previewSpeedoPhoto(this)">

                <button type="button" onclick="document.getElementById('speedoPhoto').click()"
                    style="width:100%; padding:0.85rem; background:rgba(0,122,255,0.08); border:1.5px dashed var(--primary); border-radius:12px; color:var(--primary); font-weight:700; font-size:0.875rem; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-family:inherit;">
                    <span class="material-symbols-outlined" style="font-size:20px;">photo_camera</span>
                    <span id="speedoCamBtnText">Ambil Foto Speedometer</span>
                </button>

                <div id="speedoPreviewBox" style="display:none; margin-top:0.75rem; text-align:center;">
                    <img id="speedoImgPreview" src=""
                        style="width:100%; max-height:160px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
                </div>
            </div>

            <div style="margin-bottom:1.5rem; text-align:left;">
                <label
                    style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:0.4rem;">2.
                    Angka Spidometer (KM) *</label>
                <input type="number" id="speedoNum" placeholder="Contoh: 45230"
                    style="width:100%; padding:0.8rem 1rem; border:1.5px solid var(--border); border-radius:12px; font-size:1rem; font-weight:700; font-family:inherit; box-sizing:border-box;"
                    required>
            </div>

            <input type="hidden" id="speedoTaskId">

            <div style="display:flex; gap:0.75rem;">
                <button type="button" onclick="document.getElementById('speedoModal').style.display='none'"
                    style="flex:1; padding:0.85rem; background:#f1f5f9; border:none; border-radius:12px; color:#64748b; font-weight:700; font-size:0.9rem; cursor:pointer; font-family:inherit;">Batal</button>
                <button type="button" onclick="submitSpeedo()"
                    style="flex:1; padding:0.85rem; background:var(--primary); color:white; border:none; border-radius:12px; font-weight:700; font-size:0.9rem; cursor:pointer; font-family:inherit; box-shadow:0 4px 16px rgba(0,122,255,0.3);">Simpan
                    & Mulai</button>
            </div>
        </div>
    </div>

    <script>
        function openSpeedoModal(id) {
            document.getElementById('speedoTaskId').value = id;
            document.getElementById('speedoPhoto').value = '';
            document.getElementById('speedoNum').value = '';
            document.getElementById('speedoPreviewBox').style.display = 'none';
            document.getElementById('speedoCamBtnText').innerText = 'Ambil Foto Speedometer';
            document.getElementById('speedoModal').style.display = 'flex';
        }

        function previewSpeedoPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('speedoImgPreview').src = e.target.result;
                    document.getElementById('speedoPreviewBox').style.display = 'block';
                    document.getElementById('speedoCamBtnText').innerText = 'Foto Berhasil Diambil (Foto Ulang)';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function submitSpeedo() {
            const id = document.getElementById('speedoTaskId').value;
            const fileInput = document.getElementById('speedoPhoto');
            const numInput = document.getElementById('speedoNum').value;

            if (!fileInput.files.length || !numInput) {
                showToast('Foto dan Angka Speedometer Wajib Diisi!', 'warning');
                return;
            }

            const btn = event.target;
            const origText = btn.innerText;
            btn.innerText = 'Menyimpan...';
            btn.disabled = true;

            let speedoBlob = fileInput.files[0];
            try {
                const img = new Image();
                img.src = URL.createObjectURL(speedoBlob);
                await new Promise(res => img.onload = res);
                speedoBlob = await watermarkImage(img, '', 'SPEEDOMETER AWAL');
            } catch (e) {
                console.warn('Watermark speedo start failed:', e);
            }

            await executeUpdateStatus(id, 'in_transit', '', [], '', '', speedoBlob, numInput);

            document.getElementById('speedoModal').style.display = 'none';
            btn.innerText = origText;
            btn.disabled = false;
        }
    </script>

</body>

</html>