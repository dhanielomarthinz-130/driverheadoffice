<?php
require_once 'auth_check.php';
checkLogin();
checkAccess('assign_tasks');
$can_write = canWriteMenu('assign_tasks');
$user_role = $_SESSION['role'] ?? '';

$can_assign = $can_write;
$can_edit = $can_write;
$can_delete = $can_write;
if (in_array($user_role, ['admin', 'superadmin', 'controller'], true)) {
    $can_assign = true;
    $can_edit = true;
    $can_delete = true;
}

date_default_timezone_set('Asia/Jakarta');

// Get initial pending counts (expedisi/pickup removed)
$init_wh = 0;
$init_exp = 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Driver | TMS</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .success-msg {
            color: #16a34a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .error-msg {
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Map Picker Styles */
        .btn-pick-map {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #6366f1;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .btn-pick-map:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }

        .map-picker-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            z-index: 20000 !important;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            padding: 1rem;
        }

        .map-picker-overlay.show {
            display: flex;
        }

        .map-picker-card {
            background: #ffffff;
            width: 100%;
            max-width: 900px;
            height: 85vh;
            max-height: 700px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }

        .map-picker-header {
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }

        .map-picker-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .map-picker-search {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: #ffffff;
            display: flex;
            gap: 0.5rem;
            align-items: center;
            position: relative;
        }

        .map-picker-search input {
            flex: 1;
            padding: 0.5rem 0.85rem;
            font-size: 0.85rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            font-family: inherit;
        }

        .map-picker-search input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .map-picker-search button {
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .map-picker-search button:hover {
            background: #4338ca;
        }

        /* Smart Autocomplete Dropdown */
        .map-autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 1.25rem;
            right: 1.25rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12), 0 4px 10px rgba(0, 0, 0, 0.06);
            z-index: 2100;
            max-height: 320px;
            overflow-y: auto;
            display: none;
            margin-top: 4px;
            animation: mapDropdownSlide 0.18s ease-out;
        }

        @keyframes mapDropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .map-autocomplete-dropdown.show {
            display: block;
        }

        .map-ac-item {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 0.82rem;
            color: #1e293b;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: background 0.15s;
            border-bottom: 1px solid #f1f5f9;
        }

        .map-ac-item:last-child {
            border-bottom: none;
        }

        .map-ac-item:hover,
        .map-ac-item.active {
            background: #eef2ff;
        }

        .map-ac-item .ac-icon {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            font-size: 16px;
            margin-top: 1px;
        }

        .map-ac-item .ac-text {
            flex: 1;
            min-width: 0;
        }

        .map-ac-item .ac-name {
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .map-ac-item .ac-detail {
            font-size: 0.74rem;
            color: #64748b;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .map-ac-loading {
            padding: 14px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .map-ac-loading .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: acSpin 0.6s linear infinite;
        }

        @keyframes acSpin {
            to {
                transform: rotate(360deg);
            }
        }

        .map-ac-empty {
            padding: 14px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.82rem;
        }

        #mapPickerContainer {
            flex: 1;
            width: 100%;
            position: relative;
            background: #e2e8f0;
        }

        .map-picker-footer {
            padding: 0.85rem 1.25rem;
            border-top: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .map-picker-info {
            font-size: 0.8rem;
            color: #475569;
            flex: 1;
            min-width: 200px;
        }

        .map-picker-info strong {
            color: #0f172a;
        }

        .map-picker-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal */
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

        /* Google Maps link badge */

        .maps-link {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.74rem;
            font-weight: 600;
            color: #1a73e8;
            text-decoration: none;
            padding: 0.25rem 0.55rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid #d2e3fc;
            background: #e8f0fe;
            transition: all 0.18s;
            white-space: nowrap;
        }

        .maps-link:hover {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        .maps-link .material-symbols-outlined {
            font-size: 13px;
        }

        .dest-cell {
            font-size: 0.85rem;
        }

        .dest-cell .maps-link {
            margin-top: 0.25rem;
        }

        /* Export button (Icon Only) */
        .btn-excel {
            background: #16a34a;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-excel:hover {
            background: #15803d;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.4);
        }

        .row-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 9999px;
            padding: 0.2rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Driver Checkbox Style */
        .driver-checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        .driver-checkbox-item:hover {
            background: #f8fafc;
            border-color: var(--primary);
        }

        .driver-checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .driver-checkbox-item .driver-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .driver-checkbox-item .driver-info .name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text);
        }

        .driver-checkbox-item .driver-info .status {
            font-size: 0.7rem;
            font-weight: 600;
        }

        .driver-checkbox-item .driver-info .status.free {
            color: var(--success);
        }

        .driver-checkbox-item .driver-info .status.busy {
            color: var(--warning);
        }

        /* Toast Notification moved to global style.css */

        /* Map Container in Modal */
        #detailMap {
            height: 160px;
            width: 100%;
            border-radius: 12px;
            margin: 1rem 0;
            border: 1px solid var(--border);
            z-index: 1;
        }

        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f59e0b;
            /* Amber */
            color: white;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 99px;
            margin-left: 6px;
            min-width: 18px;
            height: 18px;
            line-height: 1;
            vertical-align: middle;
        }

        .tab-badge.badge-red {
            background: #ef4444;
            /* Red */
            animation: tabPulse 2s infinite;
        }

        @keyframes tabPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .time-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .time-label {
            font-size: 0.65rem;
            color: var(--text-sub);
            font-weight: 700;
            text-transform: uppercase;
        }

        .time-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.25rem;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .detail-table tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-table tr:last-child {
            border-bottom: none;
        }

        .detail-table td {
            padding: 10px 14px;
            font-size: 0.85rem;
            vertical-align: top;
        }

        .detail-table td:first-child {
            width: 130px;
            background: #f8fafc;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.5px;
            border-right: 1px solid #f1f5f9;
        }

        .detail-table td:last-child {
            color: var(--text);
            font-weight: 600;
        }

        .detail-badge {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* Legacy animations removed */

        /* Image Popup Lightbox */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            cursor: pointer;
        }

        /* Table Content Density Adjustment (10px) */
        #historyTable tbody td,
        #historyTable tbody td div,
        #historyTable tbody td span:not(.material-symbols-outlined),
        #historyTable tbody td strong {
            font-size: 10px !important;
        }

        #historyTable .badge,
        #historyTable .detail-badge {
            font-size: 9px !important;
            padding: 1px 6px !important;
        }

        #historyTable .material-symbols-outlined {
            font-size: 14px !important;
        }

        #historyTable .maps-link {
            font-size: 9px !important;
            padding: 1px 4px !important;
        }

        #imagePopup {
            animation: none;
        }

        #imagePopup.show {
            animation: popupFadeIn 0.2s ease-out forwards;
        }

        #imagePopup #popupImg {
            animation: popupImgScale 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes popupFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes popupImgScale {
            from {
                transform: scale(0.88);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Clickable photo thumbnails */
        .photo-thumb {
            cursor: zoom-in;
            transition: transform 0.18s, box-shadow 0.18s;
        }

        .photo-thumb:hover {
            transform: scale(1.04);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        }

        /* Tabs Styling */
        .tab-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #e2e8f0;
            padding: 0.4rem;
            border-radius: 12px;
            width: fit-content;
        }

        .tab-link {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .tab-link.active {
            background: var(--surface);
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Integrated Searchable Select */
        .searchable-group {
            position: relative;
            margin-bottom: 0.75rem;
        }

        .searchable-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' height='20' viewBox='0 -960 960 960' width='20'%3E%3Cpath d='M480-345 240-585l56-56 184 184 184-184 56 56-240 240Z'/%3E%3C/svg%3E") no-repeat right 12px center;
        }

        .searchable-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .options-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-top: 5px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            display: none;
        }

        .options-list.show {
            display: block;
        }

        .option-item {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .option-item:hover {
            background: #f1f5f9;
        }

        .option-item.selected {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
        }

        .required-star {
            color: #ef4444;
            margin-left: 2px;
        }

        /* Professional Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-top: 0.5rem;
        }

        .form-grid .form-group {
            margin-bottom: 0;
        }

        .form-grid .full-width {
            grid-column: span 2;
        }

        /* ===== Compact Location Picker Row ===== */
        .loc-picker-row {
            position: relative;
        }

        .loc-picker-row label {
            display: block;
            margin-bottom: 0.35rem;
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--text);
        }

        .loc-picker-trigger {
            display: flex;
            align-items: center;
            gap: 0;
            width: 100%;
        }

        .loc-picker-trigger .loc-display {
            flex: 1;
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-right: none;
            border-radius: 10px 0 0 10px;
            font-size: 0.85rem;
            outline: none;
            background: #fff;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .loc-picker-trigger .loc-display::placeholder {
            color: #94a3b8;
        }

        .loc-picker-trigger .loc-display:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .loc-picker-trigger .loc-btn {
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: #f8fafc;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #6366f1;
            font-family: inherit;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .loc-picker-trigger .loc-btn:hover {
            background: #eef2ff;
            color: #4f46e5;
        }

        .loc-picker-trigger .loc-btn .material-symbols-outlined {
            font-size: 18px;
        }

        .loc-picker-trigger .loc-map-btn {
            padding: 9px 12px;
            border: 1.5px solid var(--border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #fff;
            font-family: inherit;
            transition: all 0.2s;
            white-space: nowrap;
            letter-spacing: 0.02em;
        }

        .loc-picker-trigger .loc-map-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        /* When map btn exists, dropdown btn loses right radius */
        .loc-picker-trigger .loc-btn:has(+ .loc-map-btn) {
            border-radius: 0;
        }

        /* Popover Panel */
        .loc-popover {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.06);
            z-index: 1050;
            padding: 1rem;
            animation: locPopSlide 0.18s ease-out;
        }

        @keyframes locPopSlide {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loc-popover.show {
            display: block;
        }

        .loc-popover-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .loc-popover-header .pop-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .loc-popover-header .pop-close {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: none;
            background: #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: 0.15s;
        }

        .loc-popover-header .pop-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .loc-popover .searchable-group {
            margin-bottom: 0.6rem;
        }

        .loc-popover .map-gmaps-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 0.5rem;
        }

        .loc-popover .map-gmaps-row input {
            flex: 1;
            padding: 0.4rem 0.6rem;
            font-size: 0.78rem;
            border: 1px dashed var(--border);
            border-radius: 6px;
            font-family: inherit;
            outline: none;
        }

        .loc-popover .map-gmaps-row input:focus {
            border-color: #6366f1;
        }

        .modal-box.wide {
            max-width: 1250px !important;
            width: 95vw !important;
        }

        /* Image Preview Styling */
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .preview-item {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Inline Edit Styling */
        .editable-cell {
            position: relative;
            transition: background 0.2s;
            border-radius: 6px;
        }

        .editable-cell:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .inline-edit-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.2s;
            color: var(--primary);
            background: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            z-index: 5;
        }

        .editable-cell:hover .inline-edit-btn {
            opacity: 1;
            right: 12px;
        }

        .editable-cell.disabled {
            cursor: default !important;
        }

        .editable-cell.disabled:hover {
            background: transparent !important;
        }

        .preview-item {
            position: relative;
        }

        .preview-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            z-index: 2;
            transition: transform 0.1s;
        }

        .preview-remove:hover {
            transform: scale(1.1);
            background: #ef4444;
        }

        /* Custom Upload Box */
        .upload-box {
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            padding: 0.6rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--text-muted);
        }

        .upload-box:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
        }

        .upload-box .material-symbols-outlined {
            font-size: 20px;
        }

        .upload-box span {
            font-size: 0.75rem;
            font-weight: 700;
        }

        .pagination-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--surface);
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-btn {
            height: 34px;
            padding: 0 0.75rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .rows-per-page {
            height: 34px;
            padding: 0 0.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            outline: none;
        }

        /* Premium Table Sorting Styles */
        .th-content {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-start;
        }

        th.sortable {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }

        th.sortable:hover {
            color: var(--primary) !important;
        }

        th.sortable:hover .sort-indicator {
            color: var(--primary) !important;
            opacity: 1;
        }

        .sort-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px !important;
            color: #94a3b8;
            opacity: 0.6;
            transition: all 0.2s ease;
        }

        th.active-sort {
            color: var(--primary) !important;
            border-bottom: 2px solid var(--primary) !important;
        }

        /* ===== ACUMATICA EXCEL GRID STYLING ===== */
        .modal-overlay {
            z-index: 9999 !important;
        }

        .excel-modal-box {
            max-width: 98vw !important;
            width: 1550px !important;
            max-height: 94vh;
            display: flex;
            flex-direction: column;
            padding: 1.25rem 1.5rem !important;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .excel-badge-count {
            background: #e0e7ff;
            color: #4338ca;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #c7d2fe;
        }

        .excel-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .excel-toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .excel-preset-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 0.8rem;
        }

        .preset-label {
            font-weight: 700;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .excel-preset-select,
        .excel-preset-input {
            padding: 4px 8px;
            font-size: 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            outline: none;
            font-family: inherit;
        }

        .excel-grid-wrapper {
            flex: 1;
            overflow-x: auto;
            overflow-y: auto;
            max-height: 54vh;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.03);
        }

        .excel-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
        }

        .excel-grid-table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.73rem;
            letter-spacing: 0.03em;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            border-right: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .excel-grid-table td {
            padding: 0;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            background: #ffffff;
            vertical-align: middle;
        }

        .excel-grid-table tr:hover td {
            background: #f8fafc;
        }

        .excel-row-num {
            text-align: center;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc !important;
            font-size: 0.78rem;
            user-select: none;
        }

        .excel-cell-input,
        .excel-cell-select {
            width: 100%;
            height: 38px;
            border: none;
            outline: none;
            padding: 6px 8px;
            font-family: inherit;
            font-size: 0.83rem;
            background: transparent;
            box-sizing: border-box;
            color: #1e293b;
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }

        .excel-cell-input:focus,
        .excel-cell-select:focus {
            background: #eff6ff;
            box-shadow: inset 0 0 0 2px #3b82f6;
        }

        .excel-loc-cell {
            display: flex;
            align-items: center;
            width: 100%;
            height: 38px;
            padding-right: 4px;
            box-sizing: border-box;
            position: relative;
        }

        .excel-loc-cell input {
            flex: 1;
            border: none;
            outline: none;
            padding: 6px 8px;
            font-family: inherit;
            font-size: 0.83rem;
            background: transparent;
        }

        .grid-loc-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            min-width: 260px;
            max-height: 220px;
            overflow-y: auto;
            background: #ffffff;
            border: 1.5px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 12px 28px -5px rgba(0, 0, 0, 0.25);
            z-index: 99999 !important;
            margin-top: 2px;
            padding: 4px 0;
        }

        .grid-loc-item {
            padding: 8px 12px;
            font-size: 0.82rem;
            color: #1e293b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }

        .grid-loc-item:last-child {
            border-bottom: none;
        }

        .grid-loc-item:hover {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }

        .excel-loc-cell input:focus {
            background: #eff6ff;
        }

        .btn-cell-map {
            border: none;
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 0.73rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 2px;
            white-space: nowrap;
            transition: background 0.15s;
        }

        .btn-cell-map:hover {
            background: #c7d2fe;
        }

        .btn-del-row {
            border: none;
            background: transparent;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-del-row:hover {
            background: #fee2e2;
        }

        .excel-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.85rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
        }

        .excel-hint {
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
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
                    <span class="material-symbols-outlined">assignment</span>
                </div>
                <div>
                    <h1>Penugasan Driver</h1>
                    <p>Buat tugas baru dan lihat riwayat pengiriman</p>
                </div>
            </div>
        </div>

        <!-- Main View: Filter & Riwayat Penugasan List -->
        <div id="assignTasksListView">
            <!-- Filter Card -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <div class="filter-bar"
                    style="margin-bottom:0; display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
                    <div class="filter-group" style="flex: 1.5; min-width: 240px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Smart
                            Search</label>
                        <div style="position: relative; flex: 1;">
                            <span class="material-symbols-outlined"
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--primary);">search</span>
                            <input type="text" id="searchInput" placeholder="Search..."
                                oninput="onAssignSmartSearchInput()"
                                style="width: 100%; padding: 0.6rem 28px 0.6rem 35px; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                            <span id="clearAssignSearch" onclick="clearAssignSearch()" class="material-symbols-outlined"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted); cursor: pointer; display: none;">close</span>
                        </div>
                    </div>
                    <div class="filter-group" style="width: 150px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Dari</label>
                        <input type="date" id="date_from" onchange="loadHistory()" value="<?php echo date('Y-m-d'); ?>"
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                    </div>
                    <div class="filter-group" style="width: 150px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Sampai</label>
                        <input type="date" id="date_to" onchange="loadHistory()" value="<?php echo date('Y-m-d'); ?>"
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none;">
                    </div>
                    <div class="filter-group" style="width: 160px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Driver</label>
                        <select id="driverFilter" onchange="loadHistory()"
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; background: white;">
                            <option value="">Semua Driver</option>
                        </select>
                    </div>
                    <div class="filter-group" style="width: 140px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Status</label>
                        <select id="statusFilter" onchange="loadHistory()"
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; background: white;">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Cancel</option>
                        </select>
                    </div>
                    <div class="filter-group" style="width: 140px;">
                        <label
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">Layanan</label>
                        <select id="typeTaskFilter" onchange="loadHistory()"
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); outline: none; background: white;">
                            <option value="">Semua Layanan</option>
                            <option value="antar">Antar</option>
                            <option value="jemput">Jemput</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-left: auto; align-items: flex-end;">
                        <?php if ($can_assign): ?>
                            <button class="btn btn-ghost" onclick="openBulkAssignModal()" id="bulkAssignBtn"
                                style="display:none; height: 40px; border: 1px solid var(--border);">
                                <span class="material-symbols-outlined">group_add</span>
                                Bulk (<span id="bulkCount">0</span>)
                            </button>
                        <?php endif; ?>
                        <?php if ($can_write || $user_role === 'admin'): ?>
                            <button class="btn btn-primary" onclick="openAddModal()"
                                title="Buat Tugas Baru (Grid Input Excel)"
                                style="height: 40px; width: 42px; display: flex; align-items: center; justify-content: center; padding: 0; border-radius: 8px; cursor: pointer; flex-shrink: 0;">
                                <span class="material-symbols-outlined" style="font-size: 20px;">post_add</span>
                            </button>
                        <?php endif; ?>
                        <button class="btn-excel-green" onclick="exportToExcel()" id="exportBtn" title="Export Excel"
                            style="height: 40px; width: 42px; display: flex; align-items: center; justify-content: center; padding: 0; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">table_view</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-outlined">history</span>
                        Riwayat Penugasan
                        <span class="row-count-badge" id="rowCountBadge" style="display:none;">
                            <span class="material-symbols-outlined" style="font-size:14px;">table_rows</span>
                            <span id="rowCountNum">0</span> data
                        </span>
                    </span>
                </div>

                <div class="table-wrapper">
                    <table id="historyTable">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAllReqs"
                                        onclick="toggleSelectAll(this)"></th>
                                <th class="sortable" onclick="handleSort('assign_by')">
                                    <div class="th-content">
                                        Assign By
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-assign_by">swap_vert</span>
                                    </div>
                                </th>
                                <th class="sortable" onclick="handleSort('date')">
                                    <div class="th-content">
                                        Tanggal / Driver
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-date">swap_vert</span>
                                    </div>
                                </th>
                                <th class="sortable" onclick="handleSort('type')">
                                    <div class="th-content">
                                        Layanan
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-type">swap_vert</span>
                                    </div>
                                </th>
                                <th class="sortable" onclick="handleSort('route')">
                                    <div class="th-content">
                                        Rute (Asal &raquo; Tujuan)
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-route">swap_vert</span>
                                    </div>
                                </th>
                                <th class="sortable" onclick="handleSort('speedometer')">
                                    <div class="th-content">
                                        Spidometer (KM)
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-speedometer">swap_vert</span>
                                    </div>
                                </th>
                                <!-- Hidden Type -->
                                <th class="sortable" onclick="handleSort('status')">
                                    <div class="th-content">
                                        Status
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-status">swap_vert</span>
                                    </div>
                                </th>
                                <th class="sortable" onclick="handleSort('notes')">
                                    <div class="th-content">
                                        Keterangan
                                        <span class="material-symbols-outlined sort-indicator"
                                            id="sort-notes">swap_vert</span>
                                    </div>
                                </th>
                                <th style="text-align:right;">
                                    <div class="th-content" style="justify-content:flex-end; width: 100%;">
                                        Aksi
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="13">
                                    <div class="empty-state">
                                        <span class="material-symbols-outlined">autorenew</span>
                                        <p>Memuat data...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Bar -->
            <div class="pagination-bar">
                <div class="pagination-controls">
                    <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub);">Rows:</label>
                    <select class="rows-per-page" id="rowsPerPage" onchange="changeRowsPerPage()">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <button class="pagination-btn" id="btnPrev" onclick="changePage(-1)">
                        <span class="material-symbols-outlined" style="font-size:18px">chevron_left</span> Prev
                    </button>
                    <button class="pagination-btn" id="btnNext" onclick="changePage(1)">
                        Next <span class="material-symbols-outlined" style="font-size:18px">chevron_right</span>
                    </button>
                </div>
            </div>
        </div> <!-- End of assignTasksListView -->

        <!-- ===== ADD TASK SECTION (IN-PAGE INDEPENDENT VIEW) ===== -->
        <div id="addModal" class="card" style="display: none; margin-bottom: 1.5rem; padding: 1.5rem;">
            <div class="modal-title"
                style="display:flex; align-items:center; gap:0.5rem; justify-content:space-between; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="closeAdd()"
                        title="Kembali ke Riwayat Penugasan"
                        style="display:flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-weight:600;">
                        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                        Kembali ke Riwayat
                    </button>
                    <div>
                        <div style="font-size:1.2rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
                            <span class="material-symbols-outlined"
                                style="color:var(--primary); font-size: 24px;">post_add</span>
                            Buat Tugas Baru (Grid Input Excel)
                        </div>
                        <div class="modal-subtitle"
                            style="margin:0; font-size:0.8rem; font-weight:500; color:var(--text-sub, #64748b);">Input
                            tugas pengiriman cepat berbasis baris & kolom tabel Acumatica</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div class="excel-badge-count" id="excelRowCountBadge">1 Baris Tugas</div>
                </div>
            </div>

            <!-- Toolbar Header: Quick Fill / Defaults & Actions -->
            <div class="excel-toolbar">
                <div class="excel-toolbar-group">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addExcelRow()"
                        title="Tambah Baris Baru (Shortcut: Tab di cell akhir)">
                        <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                        Tambah Baris
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="triggerImportExcelToGrid()"
                        style="color:#059669; border-color:#a7f3d0; background:#ecfdf5;"
                        title="Import file Excel (.xlsx / .csv) langsung ke tabel grid">
                        <span class="material-symbols-outlined" style="font-size:18px;">upload_file</span>
                        Import Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openExcelPasteModal()"
                        title="Paste data berformat Excel / Spreadsheet">
                        <span class="material-symbols-outlined" style="font-size:18px;">content_paste</span>
                        Paste dari Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="downloadExcelTemplate()"
                        style="color:#0284c7; border-color:#bae6fd; background:#f0f9ff;"
                        title="Download File Template Excel (.xlsx)">
                        <span class="material-symbols-outlined" style="font-size:18px;">download</span>
                        Download Template
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-danger" onclick="clearExcelGrid()"
                        title="Kosongkan seluruh baris">
                        <span class="material-symbols-outlined" style="font-size:18px;">delete_sweep</span>
                        Reset Grid
                    </button>
                </div>

                <div class="excel-preset-bar">
                    <span class="preset-label"><span class="material-symbols-outlined"
                            style="font-size:16px;">bolt</span> Quick Fill Default:</span>
                    <select id="gridDefaultDriver" class="excel-preset-select">
                        <option value="">-- Driver Default --</option>
                    </select>
                    <input type="datetime-local" id="gridDefaultDate" class="excel-preset-input">
                    <select id="gridDefaultType" class="excel-preset-select">
                        <option value="antar">🚗 Antar</option>
                        <option value="kirim">🚐 Jemput</option>
                    </select>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="applyPresetToAllRows()"
                        title="Terapkan Driver & Jam default ke semua baris">
                        Terapkan ke Semua
                    </button>
                </div>
            </div>

            <!-- Grid Container Table -->
            <div class="excel-grid-wrapper">
                <table class="excel-grid-table">
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">#</th>
                            <th style="min-width:180px;">Driver <span class="required-star">*</span></th>
                            <th style="min-width:170px;">Tanggal & Jam <span class="required-star">*</span></th>
                            <th style="min-width:110px;">Layanan <span class="required-star">*</span></th>
                            <th style="min-width:190px;">Penumpang / Karyawan <span class="required-star">*</span></th>
                            <th style="min-width:210px;">Lokasi Asal <span
                                    style="font-weight:normal; color:#64748b; font-size:0.7rem;">(Opsional)</span></th>
                            <th style="min-width:210px;">Lokasi Tujuan <span
                                    style="font-weight:normal; color:#64748b; font-size:0.7rem;">(Opsional)</span></th>
                            <th style="min-width:180px;">Catatan</th>
                            <th style="width:45px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="excelGridTbody">
                        <!-- Rows rendered dynamically by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons Footer -->
            <div class="excel-footer">
                <div class="excel-hint">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">info</span>
                    Tekan <b>Enter</b> di Kolom Catatan untuk menambah baris otomatis. Gunakan tombol <b>🗺️ Peta</b>
                    untuk memilih lokasi dari peta.
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <button type="button" onclick="closeAdd()" class="btn btn-ghost">Batal</button>
                    <button type="button" onclick="submitExcelGrid()" class="btn btn-primary" id="btnSubmitGrid">
                        <span class="material-symbols-outlined">send</span>
                        Berikan Semua Tugas (<span id="btnSubmitGridCount">1</span>)
                    </button>
                </div>
            </div>
            <div id="formMsg" style="margin-top:0.5rem; text-align:center;"></div>
        </div>



        <!-- ===== PASTE EXCEL MODAL ===== -->
        <div id="excelPasteModal" class="modal-overlay" onclick="if(event.target===this)closeExcelPasteModal()"
            style="display: none; z-index: 10001;">
            <div class="modal-box" style="max-width:560px;">
                <div class="modal-title"
                    style="display:flex; align-items:center; gap:0.5rem; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span class="material-symbols-outlined" style="color:#10b981;">content_paste_go</span>
                        Paste Data dari Excel
                    </div>
                    <button class="modal-close" onclick="closeExcelPasteModal()" style="position:static;">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <p class="modal-subtitle" style="margin-bottom:0.75rem;">
                    Copy tabel dari Microsoft Excel atau Google Sheets, lalu paste (Ctrl+V) pada kotak di bawah.
                </p>
                <div
                    style="font-size:0.78rem; background:#f8fafc; border:1px dashed #cbd5e1; padding:8px 12px; border-radius:6px; margin-bottom:10px; color:#475569;">
                    <b>Format Urutan Kolom Excel (Tab-Separated):</b><br>
                    <code>Driver | Tanggal (YYYY-MM-DD HH:mm) | Layanan (antar/jemput) | Penumpang | Lokasi Asal | Lokasi Tujuan | Catatan</code>
                </div>
                <textarea id="excelPasteArea" rows="8"
                    placeholder="Paste data Excel di sini (misal: Budi Pratama [TAB] 2026-08-04 14:00 [TAB] antar [TAB] Tim Sales [TAB] Kantor HO [TAB] Outlet BSD [TAB] Catatan SJ)..."
                    style="width:100%; box-sizing:border-box; padding:10px; font-family:monospace; font-size:0.82rem; border:1.5px solid var(--border); border-radius:8px; outline:none; resize:vertical;"></textarea>
                <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeExcelPasteModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="processExcelPasteData()">
                        <span class="material-symbols-outlined">download</span> Impor ke Grid
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== CUSTOM LOCATION / GOOGLE MAPS LINK INPUT MODAL ===== -->
        <div id="customLocInputModal" class="modal-overlay" onclick="if(event.target===this)closeCustomLocModal()"
            style="display: none; z-index: 15000;">
            <div class="modal-box" style="max-width: 480px; padding: 1.5rem; border-radius: 16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                    <div style="display:flex; align-items:center; gap:0.6rem;">
                        <div
                            style="width:40px; height:40px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center;">
                            <span class="material-symbols-outlined" style="font-size:24px;">add_location_alt</span>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#1e293b;"
                                id="customLocModalTitle">Input Lokasi Manual</h3>
                            <p style="margin:0; font-size:0.78rem; color:#64748b;">Link Google Maps, Koordinat (Lat,
                                Lng), atau Alamat</p>
                        </div>
                    </div>
                    <button class="modal-close" onclick="closeCustomLocModal()" style="position:static;">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label
                        style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:6px;">Lokasi
                        / Alamat / Link Google Maps</label>
                    <textarea id="customLocModalInput" rows="3"
                        placeholder="Contoh: https://maps.app.goo.gl/... atau -6.212521, 106.626670 atau Jl. Gatot Subroto No. 12"
                        style="width:100%; box-sizing:border-box; padding:10px 12px; font-family:inherit; font-size:0.85rem; border:1.5px solid #cbd5e1; border-radius:10px; outline:none; transition:border-color 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#cbd5e1'"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeCustomLocModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomLocModal()">
                        <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Simpan
                        Lokasi
                    </button>
                </div>
            </div>
        </div>



        <!-- ===== EDIT TASK MODAL ===== -->
        <div id="editModal" class="modal-overlay" onclick="if(event.target===this)closeEdit()" style="display: none;">
            <div class="modal-box wide">
                <button class="modal-close" onclick="closeEdit()">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <div class="modal-title">
                    <span class="material-symbols-outlined">edit_note</span>
                    Edit Penugasan
                </div>
                <p class="modal-subtitle">Ubah informasi tugas pengiriman</p>

                <form id="editForm">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-grid">
                        <div class="form-group searchable-group">
                            <label>Pilih Driver <span class="required-star">*</span></label>
                            <input type="text" class="searchable-input" placeholder="Cari driver..." readonly
                                onclick="toggleOptions('editDriverList')">
                            <div id="editDriverList" class="options-list"></div>
                            <input type="hidden" name="driver_id" id="editDriverSelect" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal & Jam <span class="required-star">*</span></label>
                            <input type="datetime-local" name="target_date" id="editTargetDate" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Layanan <span class="required-star">*</span></label>
                            <select name="task_type" id="editTaskType" required>
                                <option value="antar">🚗 Antar</option>
                                <option value="kirim">🚐 Jemput</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Karyawan / Penumpang <span class="required-star">*</span></label>
                            <input type="text" name="receiver_name" id="editReceiverName"
                                placeholder="Contoh: Pak Budi & Tim Marketing" required>
                        </div>

                        <input type="hidden" name="surat_jalan" id="editSuratJalan" value="HO-TASK">

                        <div class="form-group loc-picker-row" id="editOriginPicker">
                            <label>Lokasi Asal <span class="required-star">*</span></label>
                            <div class="loc-picker-trigger">
                                <input type="text" class="loc-display" id="editOriginDisplay"
                                    placeholder="Pilih lokasi asal..." readonly
                                    onclick="toggleLocPopover('editOriginPop')">
                                <button type="button" class="loc-btn" onclick="toggleLocPopover('editOriginPop')">
                                    <span class="material-symbols-outlined">expand_more</span>
                                </button>
                                <button type="button" class="loc-map-btn" onclick="openMapPicker('editOrigin')"
                                    title="Pilih titik lokasi asal di peta">
                                    <span class="material-symbols-outlined" style="font-size:16px;">map</span> Peta
                                </button>
                            </div>
                            <input type="hidden" name="origin_id" id="editOriginSelect">
                            <input type="hidden" name="origin_name" id="edit_origin_name">
                            <input type="hidden" name="origin_lat" id="edit_origin_lat">
                            <input type="hidden" name="origin_lng" id="edit_origin_lng">
                            <div class="loc-popover" id="editOriginPop">
                                <div class="loc-popover-header">
                                    <span class="pop-title">
                                        <span class="material-symbols-outlined"
                                            style="font-size:16px; color:#6366f1;">my_location</span>
                                        Pilih Lokasi Asal
                                    </span>
                                    <button type="button" class="pop-close" onclick="closeLocPopover('editOriginPop')">
                                        <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                                    </button>
                                </div>
                                <div class="searchable-group" style="position:relative;">
                                    <input type="text" class="searchable-input"
                                        placeholder="Cari atau pilih lokasi asal..." readonly
                                        onclick="toggleOptions('editOriginList')">
                                    <div id="editOriginList" class="options-list"></div>
                                </div>
                                <div class="map-gmaps-row" style="margin-top:6px;">
                                    <span class="material-symbols-outlined"
                                        style="font-size:18px; color:#4285F4;">link</span>
                                    <input type="text" id="editOriginGmapsUrl"
                                        placeholder="Tempel link Google Maps (Opsional)..."
                                        oninput="handleGmapsUrl(this, 'editOrigin')">
                                </div>
                                <div id="editOriginGmapsBadge"
                                    style="font-size:0.72rem; color:#10b981; margin-top:4px; display:none; font-weight:600;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group loc-picker-row" id="editDestPicker">
                            <label>Lokasi Tujuan <span class="required-star">*</span></label>
                            <div class="loc-picker-trigger">
                                <input type="text" class="loc-display" id="editDestDisplay"
                                    placeholder="Pilih lokasi tujuan..." readonly
                                    onclick="toggleLocPopover('editDestPop')">
                                <button type="button" class="loc-btn" onclick="toggleLocPopover('editDestPop')">
                                    <span class="material-symbols-outlined">expand_more</span>
                                </button>
                                <button type="button" class="loc-map-btn" onclick="openMapPicker('editDest')"
                                    title="Pilih titik lokasi tujuan di peta">
                                    <span class="material-symbols-outlined" style="font-size:16px;">map</span> Peta
                                </button>
                            </div>
                            <input type="hidden" name="dest_id" id="editDestSelect">
                            <input type="hidden" name="dest_name" id="edit_dest_name">
                            <input type="hidden" name="dest_lat" id="edit_dest_lat">
                            <input type="hidden" name="dest_lng" id="edit_dest_lng">
                            <div class="loc-popover" id="editDestPop">
                                <div class="loc-popover-header">
                                    <span class="pop-title">
                                        <span class="material-symbols-outlined"
                                            style="font-size:16px; color:#ef4444;">location_on</span>
                                        Pilih Lokasi Tujuan
                                    </span>
                                    <button type="button" class="pop-close" onclick="closeLocPopover('editDestPop')">
                                        <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                                    </button>
                                </div>
                                <div class="searchable-group" style="position:relative;">
                                    <input type="text" class="searchable-input" placeholder="Cari atau pilih tujuan..."
                                        readonly onclick="toggleOptions('editDestList')">
                                    <div id="editDestList" class="options-list"></div>
                                </div>
                                <div class="map-gmaps-row" style="margin-top:6px;">
                                    <span class="material-symbols-outlined"
                                        style="font-size:18px; color:#ea4335;">link</span>
                                    <input type="text" id="editDestGmapsUrl"
                                        placeholder="Tempel link Google Maps (Opsional)..."
                                        oninput="handleGmapsUrl(this, 'editDest')">
                                </div>
                                <div id="editDestGmapsBadge"
                                    style="font-size:0.72rem; color:#10b981; margin-top:4px; display:none; font-weight:600;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status <span class="required-star">*</span></label>
                            <select name="status" id="editStatus" required>
                                <option value="pending">Pending</option>
                                <option value="in_transit">In Transit</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Cancel</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Catatan</label>
                            <textarea name="notes" id="editNotes" placeholder="Tambahkan catatan jika ada..." rows="2"
                                style="width:100%; padding:0.6rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:inherit;"></textarea>
                        </div>
                    </div>

                    <div style="display:flex; gap:0.75rem; margin-top:2rem;">
                        <button type="button" onclick="closeEdit()" class="btn btn-ghost"
                            style="flex:1; justify-content:center;">Batal</button>
                        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                            <span class="material-symbols-outlined">save</span>
                            Simpan Perubahan
                        </button>
                    </div>
                    <div id="editFormMsg" style="margin-top:0.75rem; text-align:center; font-size:0.85rem;"></div>
                </form>
            </div>
        </div>

        <!-- SheetJS for Excel export (CDN) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

        <!-- ===== ASSIGN DRIVER MODAL ===== -->
        <div id="assignModal" class="modal-overlay" onclick="if(event.target===this)closeAssignModal()"
            style="display: none;">
            <div class="modal-box" style="max-width:440px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 id="assignModalTitle" style="font-size:1.25rem; font-weight:800; margin:0;">Assign Driver</h2>
                    <button class="modal-close" onclick="closeAssignModal()" style="position:static;">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div style="display:grid; grid-template-columns:1fr; gap:0.75rem; margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label
                            style="font-size:0.8rem; font-weight:700; color:#475569; display:block; margin-bottom:0.4rem;">Tanggal
                            & Jam Jadwal</label>
                        <input type="datetime-local" id="assignTargetDate"
                            style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem; font-family:inherit; font-size:0.9rem; box-sizing:border-box;">
                    </div>
                </div>

                <p style="color:var(--text-sub); font-size:0.9rem; margin-bottom:1.25rem;">Pilih satu atau beberapa
                    driver
                    untuk tugas ini:</p>

                <div id="driverCheckboxList"
                    style="max-height:300px; overflow-y:auto; display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem; padding-right:5px;">
                    <!-- Drivers with checkboxes will be injected here -->
                </div>

                <div style="display:flex; gap:0.75rem;">
                    <button type="button" onclick="closeAssignModal()" class="btn btn-ghost"
                        style="flex:1; justify-content:center;">Batal</button>
                    <button id="submitAssignBtn" onclick="submitMultipleAssign()" class="btn btn-primary"
                        style="flex:1; justify-content:center;">
                        <span class="material-symbols-outlined">send</span>
                        Konfirmasi Penugasan
                    </button>
                </div>
            </div>
        </div><!-- END assignModal -->

        <!-- ===== QUICK EDIT MODAL (FOR ADMIN/MANAGEMENT) ===== -->
        <div id="quickEditModal" class="modal-overlay" onclick="if(event.target===this)closeQuickEdit()"
            style="display: none;">
            <div class="modal-box" style="max-width:420px; padding: 1.5rem; border-radius: 1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <div id="qeIcon" class="material-symbols-outlined"
                            style="color:var(--primary); font-size:1.5rem;">
                            edit</div>
                        <h3 id="quickEditTitle" style="font-size:1.1rem; font-weight:800; margin:0; color:var(--text);">
                            Quick Edit</h3>
                    </div>
                    <button class="modal-close" onclick="closeQuickEdit()"
                        style="position:static; background:var(--bg); border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; color:var(--text-sub);">
                        <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                    </button>
                </div>

                <form id="quickEditForm">
                    <input type="hidden" id="qe_id" name="id">
                    <input type="hidden" id="qe_field" name="field">

                    <div id="qe_content" style="display:flex; flex-direction:column; gap:1rem;">
                        <!-- Dynamic content will be injected here -->
                    </div>

                    <div style="display:flex; gap:0.75rem; margin-top:2rem;">
                        <button type="button" onclick="closeQuickEdit()" class="btn btn-ghost"
                            style="flex:1; justify-content:center; border-radius:0.75rem;">Batal</button>
                        <button type="submit" id="qe_submit_btn" class="btn btn-primary"
                            style="flex:1; justify-content:center; border-radius:0.75rem;">
                            <span class="material-symbols-outlined">save</span> Simpan
                        </button>
                    </div>
                    <div id="qeMsg" style="margin-top:0.75rem; text-align:center; font-size:0.85rem;"></div>
                </form>
            </div>
        </div><!-- END quickEditModal -->

        <!-- ===== MAP PICKER MODAL (LEAFLET.JS) ===== -->
        <div id="mapPickerModal" class="map-picker-overlay" onclick="if(event.target===this)closeMapPicker()">
            <div class="map-picker-card">
                <div class="map-picker-header">
                    <h3>
                        <span class="material-symbols-outlined" style="color:#6366f1;">map</span>
                        <span id="mapPickerTitle">Pilih Titik Lokasi di Peta</span>
                    </h3>
                    <button type="button" class="modal-close" onclick="closeMapPicker()"
                        style="position:static; background:var(--bg); border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; color:var(--text-sub); border:none; cursor:pointer;">
                        <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                    </button>
                </div>
                <div class="map-picker-search">
                    <span class="material-symbols-outlined" style="color:#94a3b8;">search</span>
                    <input type="text" id="mapSearchInput"
                        placeholder="Ketik nama jalan / kota / tempat untuk mencari..."
                        onkeydown="if(event.key==='Enter'){event.preventDefault();if(mapAcActiveIndex>=0&&mapAcResults.length>0){selectMapAcResult(mapAcActiveIndex);}else{handleMapSearch();}}"
                        oninput="handleMapAutocomplete(this.value)" autocomplete="off">
                    <button type="button" onclick="handleMapSearch()">
                        <span class="material-symbols-outlined" style="font-size:16px;">search</span> Cari Alamat
                    </button>
                    <div id="mapAutocompleteDropdown" class="map-autocomplete-dropdown"></div>
                </div>
                <div id="mapPickerContainer"></div>
                <div class="map-picker-footer">
                    <div class="map-picker-info">
                        <div><strong>Koordinat:</strong> <span id="mapPickedCoords">-</span></div>
                        <div style="margin-top:2px; font-size:0.76rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:480px;"
                            id="mapPickedAddress">Geser pin / klik di peta untuk memilih lokasi.</div>
                    </div>
                    <div class="map-picker-actions">
                        <button type="button" class="btn btn-ghost" onclick="closeMapPicker()"
                            style="border-radius:8px; padding:0.5rem 1rem;">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="confirmMapSelection()"
                            style="border-radius:8px; padding:0.5rem 1.25rem; background:#4f46e5; border-color:#4f46e5;">
                            <span class="material-symbols-outlined" style="font-size:18px;">check</span> Gunakan Lokasi
                            Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // ===== MAP PICKER LOGIC (LEAFLET.JS) =====
            let mapPickerInstance = null;
            let mapPickerMarker = null;
            let activeMapContext = null; // 'origin', 'dest', 'editOrigin', 'editDest', 'qeOrigin', 'qeDest'
            let currentPickedLat = null;
            let currentPickedLng = null;
            let currentPickedAddress = '';

            function openMapPicker(context) {
                activeMapContext = context;
                const modal = document.getElementById('mapPickerModal');
                const titleEl = document.getElementById('mapPickerTitle');

                if (context === 'origin' || context === 'editOrigin' || context === 'qeOrigin' || context === 'grid_origin') {
                    titleEl.innerText = 'Pilih Titik Lokasi Asal';
                } else {
                    titleEl.innerText = 'Pilih Titik Lokasi Tujuan';
                }

                modal.classList.add('show');

                // Find initial lat/lng from existing input or default to Jakarta (-6.2088, 106.8456)
                let initLat = -6.2088;
                let initLng = 106.8456;

                let existingLatInp, existingLngInp;
                if (context === 'grid_origin' && currentGridRowIndex !== null && excelGridRows[currentGridRowIndex]) {
                    const r = excelGridRows[currentGridRowIndex];
                    if (r.origin_lat && r.origin_lng) {
                        initLat = parseFloat(r.origin_lat);
                        initLng = parseFloat(r.origin_lng);
                    }
                } else if (context === 'grid_dest' && currentGridRowIndex !== null && excelGridRows[currentGridRowIndex]) {
                    const r = excelGridRows[currentGridRowIndex];
                    if (r.dest_lat && r.dest_lng) {
                        initLat = parseFloat(r.dest_lat);
                        initLng = parseFloat(r.dest_lng);
                    }
                } else if (context === 'origin') {
                    existingLatInp = document.getElementById('origin_lat');
                    existingLngInp = document.getElementById('origin_lng');
                } else if (context === 'dest') {
                    existingLatInp = document.getElementById('dest_lat');
                    existingLngInp = document.getElementById('dest_lng');
                } else if (context === 'editOrigin') {
                    existingLatInp = document.getElementById('edit_origin_lat');
                    existingLngInp = document.getElementById('edit_origin_lng');
                } else if (context === 'editDest') {
                    existingLatInp = document.getElementById('edit_dest_lat');
                    existingLngInp = document.getElementById('edit_dest_lng');
                } else if (context === 'qeOrigin') {
                    existingLatInp = document.getElementById('qe_origin_lat');
                    existingLngInp = document.getElementById('qe_origin_lng');
                } else if (context === 'qeDest') {
                    existingLatInp = document.getElementById('qe_dest_lat');
                    existingLngInp = document.getElementById('qe_dest_lng');
                }

                if (existingLatInp && existingLngInp && existingLatInp.value && existingLngInp.value) {
                    const lat = parseFloat(existingLatInp.value);
                    const lng = parseFloat(existingLngInp.value);
                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        initLat = lat;
                        initLng = lng;
                    }
                }

                currentPickedLat = initLat;
                currentPickedLng = initLng;

                setTimeout(() => {
                    initLeafletMap(initLat, initLng);
                    // Auto focus on search input field
                    const searchInp = document.getElementById('mapSearchInput');
                    if (searchInp) {
                        searchInp.focus();
                        searchInp.select();
                    }
                }, 150);
            }

            function closeMapPicker() {
                document.getElementById('mapPickerModal').classList.remove('show');
            }

            function initLeafletMap(lat, lng) {
                const container = document.getElementById('mapPickerContainer');
                if (!container) return;

                if (mapPickerInstance) {
                    mapPickerInstance.remove();
                    mapPickerInstance = null;
                }

                mapPickerInstance = L.map('mapPickerContainer').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(mapPickerInstance);

                const customIcon = L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                mapPickerMarker = L.marker([lat, lng], {
                    draggable: true,
                    icon: customIcon
                }).addTo(mapPickerInstance);

                updatePickerInfo(lat, lng);

                mapPickerMarker.on('dragend', function (e) {
                    const position = mapPickerMarker.getLatLng();
                    currentPickedLat = position.lat;
                    currentPickedLng = position.lng;
                    updatePickerInfo(position.lat, position.lng);
                    reverseGeocode(position.lat, position.lng);
                });

                mapPickerInstance.on('click', function (e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    mapPickerMarker.setLatLng([lat, lng]);
                    currentPickedLat = lat;
                    currentPickedLng = lng;
                    updatePickerInfo(lat, lng);
                    reverseGeocode(lat, lng);
                });

                reverseGeocode(lat, lng);
            }

            function updatePickerInfo(lat, lng) {
                document.getElementById('mapPickedCoords').innerText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }

            async function reverseGeocode(lat, lng) {
                const addressEl = document.getElementById('mapPickedAddress');
                if (addressEl) addressEl.innerText = 'Mencari alamat...';
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.display_name) {
                            currentPickedAddress = data.display_name;
                            if (addressEl) addressEl.innerText = data.display_name;
                            return;
                        }
                    }
                } catch (err) {
                    console.log('Geocoding error:', err);
                }
                currentPickedAddress = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                if (addressEl) addressEl.innerText = `Titik koordinat (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
            }

            // ===== HIGH SENSITIVITY MULTI-TIER SEARCH ENGINE =====
            async function fetchNominatimSensitive(query) {
                query = query.trim();
                if (!query) return [];

                const searchVariants = [];
                searchVariants.push(query);

                // Space / POI variants (e.g. Tangcity <-> Tang City)
                if (/tangcity/i.test(query)) {
                    searchVariants.push(query.replace(/tangcity/i, 'Tang City'));
                    searchVariants.push(query.replace(/tangcity/i, 'TangCity'));
                } else if (/tang city/i.test(query)) {
                    searchVariants.push(query.replace(/tang city/i, 'Tangcity'));
                }

                // Mall & City suffix variants
                if (/mall/i.test(query) && !/tangerang/i.test(query)) {
                    searchVariants.push(query + ' Tangerang');
                }

                const results = [];
                const seenPlaceIds = new Set();

                for (const qStr of searchVariants) {
                    const urls = [
                        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(qStr)}&limit=10&addressdetails=1&countrycodes=id`,
                        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(qStr)}&limit=10&addressdetails=1`
                    ];

                    for (const url of urls) {
                        try {
                            const res = await fetch(url);
                            if (res.ok) {
                                const data = await res.json();
                                if (Array.isArray(data) && data.length > 0) {
                                    data.forEach(item => {
                                        if (!seenPlaceIds.has(item.place_id)) {
                                            seenPlaceIds.add(item.place_id);
                                            results.push(item);
                                        }
                                    });
                                }
                            }
                        } catch (e) { }
                        if (results.length >= 8) break;
                    }
                    if (results.length >= 8) break;
                }

                return results;
            }

            // ===== SMART AUTOCOMPLETE FOR MAP SEARCH =====
            let mapAcDebounceTimer = null;
            let mapAcActiveIndex = -1;
            let mapAcResults = [];

            function handleMapAutocomplete(value) {
                const query = value.trim();
                const dropdown = document.getElementById('mapAutocompleteDropdown');

                if (mapAcDebounceTimer) clearTimeout(mapAcDebounceTimer);

                if (query.length < 2) {
                    dropdown.classList.remove('show');
                    dropdown.innerHTML = '';
                    mapAcResults = [];
                    mapAcActiveIndex = -1;
                    return;
                }

                // Show loading
                dropdown.innerHTML = '<div class="map-ac-loading"><div class="spinner"></div> Mencari lokasi...</div>';
                dropdown.classList.add('show');

                mapAcDebounceTimer = setTimeout(async () => {
                    try {
                        const data = await fetchNominatimSensitive(query);
                        mapAcResults = data;
                        mapAcActiveIndex = -1;

                        if (data.length === 0) {
                            dropdown.innerHTML = '<div class="map-ac-empty"><span class="material-symbols-outlined" style="font-size:18px; vertical-align:middle;">search_off</span> Tidak ditemukan. Coba kata kunci lain.</div>';
                            return;
                        }

                        dropdown.innerHTML = data.map((item, idx) => {
                            const parts = item.display_name.split(', ');
                            const mainName = parts[0];
                            const detail = parts.slice(1).join(', ');
                            const iconName = getLocationIcon(item.type, item.class);
                            return `<div class="map-ac-item" data-index="${idx}" onclick="selectMapAcResult(${idx})" onmouseenter="mapAcActiveIndex=${idx};highlightMapAcItem()">
                            <div class="ac-icon"><span class="material-symbols-outlined">${iconName}</span></div>
                            <div class="ac-text">
                                <div class="ac-name">${mainName}</div>
                                <div class="ac-detail">${detail}</div>
                            </div>
                        </div>`;
                        }).join('');

                    } catch (err) {
                        console.log('Autocomplete error:', err);
                        dropdown.innerHTML = '<div class="map-ac-empty">Gagal mencari. Coba lagi.</div>';
                    }
                }, 300);
            }

            function getLocationIcon(type, cls) {
                if (cls === 'building' || type === 'house' || type === 'apartments') return 'apartment';
                if (cls === 'highway' || type === 'road' || type === 'residential') return 'road';
                if (type === 'city' || type === 'town' || type === 'village') return 'location_city';
                if (type === 'administrative') return 'flag';
                if (cls === 'shop' || cls === 'amenity') return 'storefront';
                return 'location_on';
            }

            function selectMapAcResult(index) {
                const item = mapAcResults[index];
                if (!item) return;

                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);
                currentPickedLat = lat;
                currentPickedLng = lng;
                currentPickedAddress = item.display_name;

                // Update search input with selected name
                document.getElementById('mapSearchInput').value = item.display_name.split(', ')[0];

                // Close dropdown
                const dropdown = document.getElementById('mapAutocompleteDropdown');
                dropdown.classList.remove('show');
                dropdown.innerHTML = '';
                mapAcResults = [];

                // Move map
                if (mapPickerInstance && mapPickerMarker) {
                    mapPickerInstance.setView([lat, lng], 16);
                    mapPickerMarker.setLatLng([lat, lng]);
                }
                updatePickerInfo(lat, lng);
                const addressEl = document.getElementById('mapPickedAddress');
                if (addressEl) addressEl.innerText = item.display_name;
            }

            function highlightMapAcItem() {
                const dropdown = document.getElementById('mapAutocompleteDropdown');
                const items = dropdown.querySelectorAll('.map-ac-item');
                items.forEach((el, i) => {
                    el.classList.toggle('active', i === mapAcActiveIndex);
                });
            }

            // Keyboard navigation for autocomplete
            document.addEventListener('keydown', function (e) {
                const dropdown = document.getElementById('mapAutocompleteDropdown');
                if (!dropdown || !dropdown.classList.contains('show') || mapAcResults.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    mapAcActiveIndex = Math.min(mapAcActiveIndex + 1, mapAcResults.length - 1);
                    highlightMapAcItem();
                    const activeEl = dropdown.querySelector('.map-ac-item.active');
                    if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    mapAcActiveIndex = Math.max(mapAcActiveIndex - 1, 0);
                    highlightMapAcItem();
                    const activeEl = dropdown.querySelector('.map-ac-item.active');
                    if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                    mapAcActiveIndex = -1;
                }
            });

            // Close autocomplete when clicking outside
            document.addEventListener('click', function (e) {
                const dropdown = document.getElementById('mapAutocompleteDropdown');
                if (dropdown && !e.target.closest('.map-picker-search')) {
                    dropdown.classList.remove('show');
                }
            });

            async function handleMapSearch() {
                // Close autocomplete dropdown
                const dropdown = document.getElementById('mapAutocompleteDropdown');
                if (dropdown) { dropdown.classList.remove('show'); dropdown.innerHTML = ''; }

                const query = document.getElementById('mapSearchInput').value.trim();
                if (!query) return;
                const addressEl = document.getElementById('mapPickedAddress');
                if (addressEl) addressEl.innerText = 'Mencari lokasi...';

                try {
                    const data = await fetchNominatimSensitive(query);
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        currentPickedLat = lat;
                        currentPickedLng = lng;
                        currentPickedAddress = data[0].display_name;

                        if (mapPickerInstance && mapPickerMarker) {
                            mapPickerInstance.setView([lat, lng], 16);
                            mapPickerMarker.setLatLng([lat, lng]);
                        }
                        updatePickerInfo(lat, lng);
                        if (addressEl) addressEl.innerText = data[0].display_name;
                        return;
                    }
                } catch (err) {
                    console.log('Search error:', err);
                }
                showToast('Lokasi tidak ditemukan. Coba ketik nama tempat/kota lebih spesifik.', 'error');
            }

            function confirmMapSelection() {
                if (!activeMapContext || !currentPickedLat || !currentPickedLng) {
                    closeMapPicker();
                    return;
                }

                const lat = currentPickedLat.toFixed(6);
                const lng = currentPickedLng.toFixed(6);
                const locName = currentPickedAddress || `Titik Peta (${lat}, ${lng})`;

                if (activeMapContext && activeMapContext.startsWith('grid_')) {
                    const locType = activeMapContext.replace('grid_', '');
                    if (currentGridRowIndex !== null && excelGridRows[currentGridRowIndex]) {
                        if (locType === 'origin') {
                            excelGridRows[currentGridRowIndex].origin_name = locName;
                            excelGridRows[currentGridRowIndex].origin_lat = lat;
                            excelGridRows[currentGridRowIndex].origin_lng = lng;
                            excelGridRows[currentGridRowIndex].origin_id = '';
                        } else {
                            excelGridRows[currentGridRowIndex].dest_name = locName;
                            excelGridRows[currentGridRowIndex].dest_lat = lat;
                            excelGridRows[currentGridRowIndex].dest_lng = lng;
                            excelGridRows[currentGridRowIndex].dest_id = '';
                        }
                        renderExcelGrid();
                    }
                    closeMapPicker();
                    return;
                }

                let latId, lngId, nameId, displayInput, selectId, badgeId, gmapsUrlId;

                if (activeMapContext === 'origin') {
                    latId = 'origin_lat';
                    lngId = 'origin_lng';
                    nameId = 'origin_name';
                    selectId = 'originSelect';
                    badgeId = 'originGmapsBadge';
                    gmapsUrlId = 'originGmapsUrl';
                    displayInput = document.querySelector('#originList')?.parentElement?.querySelector('.searchable-input');
                } else if (activeMapContext === 'dest') {
                    latId = 'dest_lat';
                    lngId = 'dest_lng';
                    nameId = 'dest_name';
                    selectId = 'destSelect';
                    badgeId = 'destGmapsBadge';
                    gmapsUrlId = 'destGmapsUrl';
                    displayInput = document.querySelector('#destList')?.parentElement?.querySelector('.searchable-input');
                } else if (activeMapContext === 'editOrigin') {
                    latId = 'edit_origin_lat';
                    lngId = 'edit_origin_lng';
                    nameId = 'edit_origin_name';
                    selectId = 'editOriginSelect';
                    badgeId = 'editOriginGmapsBadge';
                    gmapsUrlId = 'editOriginGmapsUrl';
                    displayInput = document.querySelector('#editOriginList')?.parentElement?.querySelector('.searchable-input');
                } else if (activeMapContext === 'editDest') {
                    latId = 'edit_dest_lat';
                    lngId = 'edit_dest_lng';
                    nameId = 'edit_dest_name';
                    selectId = 'editDestSelect';
                    badgeId = 'editDestGmapsBadge';
                    gmapsUrlId = 'editDestGmapsUrl';
                    displayInput = document.querySelector('#editDestList')?.parentElement?.querySelector('.searchable-input');
                } else if (activeMapContext === 'qeOrigin') {
                    latId = 'qe_origin_lat';
                    lngId = 'qe_origin_lng';
                    nameId = 'qe_origin_name';
                    selectId = 'qe_origin_id';
                    badgeId = 'qeOriginGmapsBadge';
                    gmapsUrlId = 'qeOriginGmapsUrl';
                    displayInput = document.getElementById('qe_origin_input');
                } else if (activeMapContext === 'qeDest') {
                    latId = 'qe_dest_lat';
                    lngId = 'qe_dest_lng';
                    nameId = 'qe_dest_name';
                    selectId = 'qe_dest_id';
                    badgeId = 'qeDestGmapsBadge';
                    gmapsUrlId = 'qeDestGmapsUrl';
                    displayInput = document.getElementById('qe_dest_input');
                }

                if (latId && document.getElementById(latId)) document.getElementById(latId).value = lat;
                if (lngId && document.getElementById(lngId)) document.getElementById(lngId).value = lng;
                if (nameId && document.getElementById(nameId)) document.getElementById(nameId).value = locName;
                if (selectId && document.getElementById(selectId)) document.getElementById(selectId).value = '';
                if (displayInput) displayInput.value = locName;

                // Update compact loc-display input
                if (selectId) updateLocDisplay(selectId, locName);

                const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
                if (gmapsUrlId && document.getElementById(gmapsUrlId)) {
                    document.getElementById(gmapsUrlId).value = mapsUrl;
                }

                if (badgeId && document.getElementById(badgeId)) {
                    const badge = document.getElementById(badgeId);
                    badge.innerText = `✓ GPS Peta Terpilih: ${lat}, ${lng}`;
                    badge.style.display = 'block';
                }

                closeMapPicker();
            }

            // ===== UTILITY FUNCTIONS =====
            function formatDateTime(str) {
                if (!str) return '-';
                const dt = new Date(str);
                if (isNaN(dt.getTime())) return str;
                const date = dt.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                const time = dt.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                return `${date}, ${time} WIB`;
            }

            function formatLocationDisplay(name, lat, lng) {
                if (!name) return '-';
                name = name.trim();
                const isUrl = name.startsWith('http://') || name.startsWith('https://');

                if (isUrl) {
                    return `<a href="${name}" target="_blank" onclick="event.stopPropagation()" class="maps-link" style="display:inline-flex; align-items:center; gap:3px;"><span class="material-symbols-outlined" style="font-size:13px !important;">map</span> Google Maps</a>`;
                }

                const hasCoords = lat && lng && parseFloat(lat) !== 0 && parseFloat(lng) !== 0;
                if (hasCoords) {
                    return `${name} <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" onclick="event.stopPropagation()" class="maps-link" style="display:inline-flex; align-items:center; gap:3px; margin-left: 4px;"><span class="material-symbols-outlined" style="font-size:13px !important;">map</span> Peta</a>`;
                }

                return name;
            }

            // ===== IMAGE POPUP LIGHTBOX =====
            function showImagePopup(url) {
                const popup = document.getElementById('imagePopup');
                const img = document.getElementById('popupImg');
                const dlBtn = document.getElementById('downloadImageBtn');
                if (!popup || !img) return;

                img.src = url;
                if (dlBtn) dlBtn.href = url;

                popup.style.display = 'flex';
                setTimeout(() => popup.classList.add('show'), 10);
            }

            function closeImagePopup() {
                const popup = document.getElementById('imagePopup');
                if (!popup) return;
                popup.classList.remove('show');
                setTimeout(() => {
                    popup.style.display = 'none';
                    document.getElementById('popupImg').src = '';
                }, 200);
            }


            // Close with ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const popup = document.getElementById('imagePopup');
                    if (popup && popup.style.display === 'flex') {
                        closeImagePopup();
                    }
                }
            });

            const USER_ROLE = '<?php echo $_SESSION["role"] ?? ""; ?>';
            const CAN_WRITE = <?php echo $can_write ? 'true' : 'false'; ?>;
            const CAN_ASSIGN = <?php echo $can_assign ? 'true' : 'false'; ?>;
            const CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
            const CAN_DELETE = <?php echo $can_delete ? 'true' : 'false'; ?>;
            const API_URL = 'api.php';
            let locationData = [];
            let allDrivers = [];
            let historyData = []; // Store for export
            let currentPage = 1;
            let rowsPerPage = 25;
            let sortColumn = 'date';
            let sortDirection = 'desc';

            // ===== BULK ASSIGN LOGIC =====
            let isBulkMode = false;
            let selectedReqIds = [];

            function toggleSelectAll(masterCb) {
                const cbs = document.querySelectorAll('.req-checkbox');
                cbs.forEach(cb => cb.checked = masterCb.checked);
                updateBulkBtn();
            }

            function updateBulkBtn() {
                const selected = Array.from(document.querySelectorAll('.req-checkbox:checked')).map(cb => parseInt(cb.value));
                selectedReqIds = selected;
                const btn = document.getElementById('bulkAssignBtn');
                const countSpan = document.getElementById('bulkCount');

                if (selected.length > 0) {
                    btn.style.display = 'inline-flex';
                    countSpan.innerText = selected.length;
                } else {
                    btn.style.display = 'none';
                }
            }

            function openBulkAssignModal() {
                if (selectedReqIds.length === 0) return;
                isBulkMode = true;

                // Re-use assignModal but change title
                document.getElementById('assignModalTitle').innerText = `Bulk Assign: ${selectedReqIds.length} Tugas`;

                // Populate driver list
                const container = document.getElementById('driverCheckboxList');
                container.innerHTML = allDrivers.map(d => `
                <label class="driver-checkbox-item">
                    <input type="checkbox" name="assign_drivers" value="${d.user_id}">
                    <div class="driver-info">
                        <span class="name">${d.driver_name}</span>
                        <span class="status ${d.is_shipping ? 'busy' : 'free'}">${d.is_shipping ? 'Sedang Jalan' : 'Tersedia'}</span>
                    </div>
                </label>
            `).join('');

                // Pre-fill date (default to today for bulk)
                document.getElementById('assignTargetDate').value = todayDateTimeStr();

                document.getElementById('assignModal').classList.add('show');
            }

            // ── Helpers ─────────────────────────────────────────────────────────
            function todayStr() {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function todayDateTimeStr() {
                return "<?php echo date('Y-m-d\TH:i'); ?>";
            }

            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;

                const icons = {
                    success: 'check_circle',
                    error: 'error',
                    warning: 'warning',
                    info: 'info'
                };
                const icon = icons[type] || 'info';

                toast.innerHTML = `
                <span class="material-symbols-outlined toast-icon">${icon}</span>
                <div class="toast-content">${message}</div>
            `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            function showConfirmToast(message, onOk, btnText = 'Ya, Lanjutkan', onCancel = null) {
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
                    if (onOk) onOk();
                    toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                    setTimeout(() => toast.remove(), 300);
                };
                toast.querySelector('#confirmCancel').onclick = () => {
                    if (onCancel) onCancel();
                    toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                    setTimeout(() => toast.remove(), 300);
                };
            }

            function showPromptToast(message, defaultVal = '', placeholder = 'Alasan...', onOk = null, btnText = 'Ya, Konfirmasi') {
                const container = document.getElementById('toastContainer');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = `toast warning`;
                toast.style.minWidth = '340px';
                toast.style.maxWidth = '400px';
                toast.style.flexDirection = 'column';
                toast.style.alignItems = 'flex-start';
                toast.style.padding = '1rem';
                toast.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.25)';

                toast.innerHTML = `
                <div style="display:flex; align-items:center; gap:10px; width:100%; margin-bottom:8px;">
                    <span class="material-symbols-outlined toast-icon" style="color:#ef4444; font-size:22px;">cancel</span>
                    <div class="toast-content" style="flex:1; font-weight:700; font-size:0.88rem; color:var(--text);">${message}</div>
                </div>
                <div style="width:100%; margin-bottom:10px;">
                    <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:4px;">Alasan Pembatalan (Opsional):</label>
                    <input type="text" id="toastPromptInput" class="form-control" value="${defaultVal}" placeholder="${placeholder}" style="width:100%; height:36px; font-size:0.82rem; padding:0 10px; border-radius:6px; border:1.5px solid var(--border);">
                </div>
                <div style="display:flex; gap:8px; width:100%; justify-content:flex-end;">
                    <button class="btn btn-ghost" id="promptCancel" style="padding:4px 12px; font-size:0.75rem; height:30px; border-radius:6px;">Batal</button>
                    <button class="btn btn-primary" id="promptOk" style="padding:4px 14px; font-size:0.75rem; height:30px; border-radius:6px; background:#ef4444; color:white; border:none; font-weight:700;">${btnText}</button>
                </div>
            `;

                container.appendChild(toast);
                const inputEl = toast.querySelector('#toastPromptInput');
                setTimeout(() => { inputEl.focus(); inputEl.select(); }, 50);

                inputEl.addEventListener('keyup', (e) => {
                    if (e.key === 'Enter') {
                        toast.querySelector('#promptOk').click();
                    }
                });

                toast.querySelector('#promptOk').onclick = () => {
                    const val = inputEl.value.trim();
                    toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                    setTimeout(() => toast.remove(), 300);
                    if (onOk) onOk(val);
                };
                toast.querySelector('#promptCancel').onclick = () => {
                    toast.style.animation = 'toastFadeOut 0.3s ease-in forwards';
                    setTimeout(() => toast.remove(), 300);
                };
            }

            function resetFilters() {
                const today = todayStr();
                document.getElementById('date_from').value = today;
                document.getElementById('date_to').value = today;
                document.getElementById('driverFilter').value = '';
                document.getElementById('statusFilter').value = '';
                if (document.getElementById('typeTaskFilter')) document.getElementById('typeTaskFilter').value = '';
                currentPage = 1;
                loadHistory();
            }

            function handleGmapsUrl(input, type) {
                const val = input.value.trim();
                const badge = document.getElementById(type + 'GmapsBadge');
                if (!val) {
                    if (badge) badge.style.display = 'none';
                    return;
                }

                let nameInputId, displayInput, selectId;
                if (type === 'origin') {
                    nameInputId = 'origin_name';
                    selectId = 'originSelect';
                    displayInput = document.querySelector('#originList')?.parentElement?.querySelector('.searchable-input');
                } else if (type === 'dest') {
                    nameInputId = 'dest_name';
                    selectId = 'destSelect';
                    displayInput = document.querySelector('#destList')?.parentElement?.querySelector('.searchable-input');
                } else if (type === 'editOrigin') {
                    nameInputId = 'edit_origin_name';
                    selectId = 'editOriginSelect';
                    displayInput = document.querySelector('#editOriginList')?.parentElement?.querySelector('.searchable-input');
                } else if (type === 'editDest') {
                    nameInputId = 'edit_dest_name';
                    selectId = 'editDestSelect';
                    displayInput = document.querySelector('#editDestList')?.parentElement?.querySelector('.searchable-input');
                } else if (type === 'qeOrigin') {
                    nameInputId = 'qe_origin_name';
                    selectId = 'qe_origin_id';
                    displayInput = document.getElementById('qe_origin_input');
                } else if (type === 'qeDest') {
                    nameInputId = 'qe_dest_name';
                    selectId = 'qe_dest_id';
                    displayInput = document.getElementById('qe_dest_input');
                }

                // If it is a URL, save the URL itself as the name, clear the preset ID, and show visual feedback
                if (val.startsWith('http://') || val.startsWith('https://')) {
                    if (nameInputId && document.getElementById(nameInputId)) {
                        document.getElementById(nameInputId).value = val;
                    }
                    if (displayInput) {
                        displayInput.value = 'Link Google Maps: ' + val.substring(0, 30) + '...';
                    }
                    if (selectId && document.getElementById(selectId)) {
                        document.getElementById(selectId).value = '';
                    }
                }

                // Regex for coordinates in Google Maps URL (@lat,lng or q=lat,lng or ll=lat,lng)
                const coordRegex = /@(-?\d+\.\d+),(-?\d+\.\d+)|q=(-?\d+\.\d+),(-?\d+\.\d+)|ll=(-?\d+\.\d+),(-?\d+\.\d+)|search\/(-?\d+\.\d+),\+?(-?\d+\.\d+)/;
                const match = val.match(coordRegex);

                if (match) {
                    const lat = match[1] || match[3] || match[5] || match[7];
                    const lng = match[2] || match[4] || match[6] || match[8];

                    let latId = '', lngId = '';
                    if (type === 'dest') {
                        latId = 'dest_lat';
                        lngId = 'dest_lng';
                    } else if (type === 'editDest') {
                        latId = 'edit_dest_lat';
                        lngId = 'edit_dest_lng';
                    } else if (type === 'qeDest') {
                        latId = 'qe_dest_lat';
                        lngId = 'qe_dest_lng';
                    } else if (type === 'origin') {
                        latId = 'origin_lat';
                        lngId = 'origin_lng';
                    } else if (type === 'editOrigin') {
                        latId = 'edit_origin_lat';
                        lngId = 'edit_origin_lng';
                    } else if (type === 'qeOrigin') {
                        latId = 'qe_origin_lat';
                        lngId = 'qe_origin_lng';
                    }

                    if (latId && lngId && document.getElementById(latId) && document.getElementById(lngId)) {
                        document.getElementById(latId).value = lat;
                        document.getElementById(lngId).value = lng;
                    }
                    if (badge) {
                        badge.innerText = `✓ GPS Terbaca: ${lat}, ${lng}`;
                        badge.style.display = 'block';
                    }
                } else {
                    if (badge) {
                        badge.innerText = '✓ Link Google Maps tersimpan';
                        badge.style.display = 'block';
                    }
                }
            }

            // ===== EXCEL / ACUMATICA DATA GRID SYSTEM =====
            let excelGridRows = [];
            let currentGridRowIndex = null;
            let currentGridLocType = null;

            async function openAddModal() {
                const listView = document.getElementById('assignTasksListView');
                const section = document.getElementById('addModal');
                if (!section) return;

                excelGridRows = [];
                addExcelRow(); // Default 1 row only

                const defDateInput = document.getElementById('gridDefaultDate');
                if (defDateInput) defDateInput.value = todayDateTimeStr();

                const msgEl = document.getElementById('formMsg');
                if (msgEl) msgEl.innerHTML = '';

                if (listView) listView.style.display = 'none';
                section.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (!allDrivers || allDrivers.length === 0 || !locationData || locationData.length === 0) {
                    await loadFormData();
                }
                populateGridPresetDrivers();
                renderExcelGrid();
            }

            function closeAdd() {
                const listView = document.getElementById('assignTasksListView');
                const section = document.getElementById('addModal');
                if (section) section.style.display = 'none';
                if (listView) listView.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function populateGridPresetDrivers() {
                const sel = document.getElementById('gridDefaultDriver');
                if (!sel || !allDrivers || !Array.isArray(allDrivers)) return;
                sel.innerHTML = '<option value="">-- Driver Default --</option>';
                allDrivers.forEach(d => {
                    sel.innerHTML += `<option value="${d.user_id}">${d.driver_name}</option>`;
                });
            }

            function addExcelRow(initialData = null) {
                const defaultDate = document.getElementById('gridDefaultDate')?.value || todayDateTimeStr();
                const defaultDriver = document.getElementById('gridDefaultDriver')?.value || '';
                const defaultType = document.getElementById('gridDefaultType')?.value || 'antar';

                const newRow = initialData ? { ...initialData } : {
                    driver_id: defaultDriver,
                    target_date: defaultDate,
                    task_type: defaultType,
                    receiver_name: '',
                    origin_name: '',
                    origin_id: '',
                    origin_lat: '',
                    origin_lng: '',
                    dest_name: '',
                    dest_id: '',
                    dest_lat: '',
                    dest_lng: '',
                    notes: ''
                };
                excelGridRows.push(newRow);
                renderExcelGrid();
            }

            function deleteExcelRow(index) {
                if (excelGridRows.length <= 1) {
                    excelGridRows[0] = {
                        driver_id: '', target_date: todayDateTimeStr(), task_type: 'antar',
                        receiver_name: '', origin_name: '', origin_id: '', origin_lat: '', origin_lng: '',
                        dest_name: '', dest_id: '', dest_lat: '', dest_lng: '', notes: ''
                    };
                } else {
                    excelGridRows.splice(index, 1);
                }
                renderExcelGrid();
            }

            function clearExcelGrid() {
                showConfirmToast('Kosongkan seluruh baris di grid?', () => {
                    excelGridRows = [];
                    addExcelRow(); // Default 1 row only
                }, 'Ya, Kosongkan');
            }

            function applyPresetToAllRows() {
                const dDriver = document.getElementById('gridDefaultDriver')?.value;
                const dDate = document.getElementById('gridDefaultDate')?.value;
                const dType = document.getElementById('gridDefaultType')?.value;

                excelGridRows.forEach(r => {
                    if (dDriver) r.driver_id = dDriver;
                    if (dDate) r.target_date = dDate;
                    if (dType) r.task_type = dType;
                });
                renderExcelGrid();
            }

            function escapeHtmlAttr(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function renderExcelGrid() {
                const tbody = document.getElementById('excelGridTbody');
                if (!tbody) return;

                const driversList = (typeof allDrivers !== 'undefined' && Array.isArray(allDrivers)) ? allDrivers : [];
                const masterLocations = (typeof locationData !== 'undefined' && Array.isArray(locationData)) ? locationData : [];

                let html = '';
                excelGridRows.forEach((row, idx) => {
                    const rowNum = idx + 1;
                    const isOriginInMaster = masterLocations.some(l => l.name === row.origin_name);
                    const isDestInMaster = masterLocations.some(l => l.name === row.dest_name);

                    html += `
                    <tr data-row="${idx}">
                        <td class="excel-row-num">${rowNum}</td>
                        <td>
                            <select class="excel-cell-select" onchange="updateGridRowData(${idx}, 'driver_id', this.value)">
                                <option value="">-- Pilih Driver --</option>
                                ${driversList.map(d => `<option value="${d.user_id}" ${String(d.user_id) === String(row.driver_id) ? 'selected' : ''}>${escapeHtmlAttr(d.driver_name)}</option>`).join('')}
                            </select>
                        </td>
                        <td>
                            <input type="datetime-local" class="excel-cell-input" value="${row.target_date || ''}" onchange="updateGridRowData(${idx}, 'target_date', this.value)">
                        </td>
                        <td>
                            <select class="excel-cell-select" onchange="updateGridRowData(${idx}, 'task_type', this.value)">
                                <option value="antar" ${row.task_type === 'antar' ? 'selected' : ''}>🚗 Antar</option>
                                <option value="kirim" ${row.task_type === 'kirim' || row.task_type === 'jemput' ? 'selected' : ''}>🚐 Jemput</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="excel-cell-input" placeholder="Nama Karyawan / Penumpang" value="${escapeHtmlAttr(row.receiver_name || '')}" oninput="updateGridRowData(${idx}, 'receiver_name', this.value)">
                        </td>
                        <td>
                            <div class="excel-loc-cell">
                                <select class="excel-cell-select" onchange="onGridLocSelect(${idx}, 'origin', this.value)">
                                    <option value="">-- Pilih Lokasi Asal (Master) --</option>
                                    ${masterLocations.map(l => `<option value="${escapeHtmlAttr(l.name)}" ${row.origin_name === l.name ? 'selected' : ''}>🏢 ${escapeHtmlAttr(l.name)} ${l.city ? '(' + escapeHtmlAttr(l.city) + ')' : ''}</option>`).join('')}
                                    ${row.origin_name && !isOriginInMaster ? `<option value="${escapeHtmlAttr(row.origin_name)}" selected>📌 ${escapeHtmlAttr(row.origin_name)}</option>` : ''}
                                    <option value="__custom__">✏️ Input Link Google Maps / Koordinat / Manual...</option>
                                </select>
                                <button type="button" class="btn-cell-map" onclick="openGridMapPicker(${idx}, 'origin')" title="Pilih Titik Lokasi dari Peta">
                                    <span class="material-symbols-outlined" style="font-size:14px;">map</span> Peta
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="excel-loc-cell">
                                <select class="excel-cell-select" onchange="onGridLocSelect(${idx}, 'dest', this.value)" onkeydown="checkGridRowTab(${idx}, event)">
                                    <option value="">-- Pilih Lokasi Tujuan (Master) --</option>
                                    ${masterLocations.map(l => `<option value="${escapeHtmlAttr(l.name)}" ${row.dest_name === l.name ? 'selected' : ''}>🏢 ${escapeHtmlAttr(l.name)} ${l.city ? '(' + escapeHtmlAttr(l.city) + ')' : ''}</option>`).join('')}
                                    ${row.dest_name && !isDestInMaster ? `<option value="${escapeHtmlAttr(row.dest_name)}" selected>📌 ${escapeHtmlAttr(row.dest_name)}</option>` : ''}
                                    <option value="__custom__">✏️ Input Link Google Maps / Koordinat / Manual...</option>
                                </select>
                                <button type="button" class="btn-cell-map" onclick="openGridMapPicker(${idx}, 'dest')" title="Pilih Titik Lokasi dari Peta">
                                    <span class="material-symbols-outlined" style="font-size:14px;">map</span> Peta
                                </button>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="excel-cell-input" placeholder="Catatan (Opsional)" value="${escapeHtmlAttr(row.notes || '')}" oninput="updateGridRowData(${idx}, 'notes', this.value)" onkeydown="checkGridNotesEnter(${idx}, event)">
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-del-row" onclick="deleteExcelRow(${idx})" title="Hapus baris ini">
                                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                            </button>
                        </td>
                    </tr>
                `;
                });

                tbody.innerHTML = html;

                const totalCount = excelGridRows.length;
                const badge = document.getElementById('excelRowCountBadge');
                if (badge) badge.innerText = `${totalCount} Baris Tugas`;
                const btnCount = document.getElementById('btnSubmitGridCount');
                if (btnCount) btnCount.innerText = totalCount;
            }

            function updateGridRowData(index, field, value) {
                if (excelGridRows[index]) {
                    excelGridRows[index][field] = value;
                }
            }

            let customLocTargetRowIndex = null;
            let customLocTargetType = null;

            function openCustomLocModal(index, locType) {
                customLocTargetRowIndex = index;
                customLocTargetType = locType;
                const modal = document.getElementById('customLocInputModal');
                if (!modal) return;

                const titleEl = document.getElementById('customLocModalTitle');
                if (titleEl) {
                    titleEl.innerText = locType === 'origin' ? 'Input Lokasi Asal Manual' : 'Input Lokasi Tujuan Manual';
                }

                const currentVal = excelGridRows[index] ? (locType === 'origin' ? excelGridRows[index].origin_name : excelGridRows[index].dest_name) : '';
                const inp = document.getElementById('customLocModalInput');
                if (inp) {
                    inp.value = (currentVal && currentVal !== '-') ? currentVal : '';
                }

                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                    if (inp) inp.focus();
                }, 10);
            }

            function closeCustomLocModal() {
                const modal = document.getElementById('customLocInputModal');
                if (!modal) return;
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 200);
                renderExcelGrid();
            }

            function saveCustomLocModal() {
                const inp = document.getElementById('customLocModalInput');
                const val = inp ? inp.value.trim() : '';

                if (customLocTargetRowIndex !== null && excelGridRows[customLocTargetRowIndex]) {
                    if (customLocTargetType === 'origin') {
                        excelGridRows[customLocTargetRowIndex].origin_name = val;
                        const matched = (locationData || []).find(l => l.name && l.name.toLowerCase() === val.toLowerCase());
                        if (matched) {
                            excelGridRows[customLocTargetRowIndex].origin_id = matched.id;
                            excelGridRows[customLocTargetRowIndex].origin_lat = matched.lat;
                            excelGridRows[customLocTargetRowIndex].origin_lng = matched.lng;
                        } else {
                            excelGridRows[customLocTargetRowIndex].origin_id = '';
                            excelGridRows[customLocTargetRowIndex].origin_lat = '';
                            excelGridRows[customLocTargetRowIndex].origin_lng = '';
                        }
                    } else {
                        excelGridRows[customLocTargetRowIndex].dest_name = val;
                        const matched = (locationData || []).find(l => l.name && l.name.toLowerCase() === val.toLowerCase());
                        if (matched) {
                            excelGridRows[customLocTargetRowIndex].dest_id = matched.id;
                            excelGridRows[customLocTargetRowIndex].dest_lat = matched.lat;
                            excelGridRows[customLocTargetRowIndex].dest_lng = matched.lng;
                        } else {
                            excelGridRows[customLocTargetRowIndex].dest_id = '';
                            excelGridRows[customLocTargetRowIndex].dest_lat = '';
                            excelGridRows[customLocTargetRowIndex].dest_lng = '';
                        }
                    }
                }

                closeCustomLocModal();
                renderExcelGrid();
                if (val) {
                    showToast(`✓ Lokasi kustom/link maps berhasil disimpan`, 'success');
                }
            }

            function onGridLocSelect(index, locType, value) {
                if (!excelGridRows[index]) return;

                if (value === '__custom__') {
                    openCustomLocModal(index, locType);
                    return;
                }

                if (locType === 'origin') {
                    excelGridRows[index].origin_name = value;
                    const matched = (locationData || []).find(l => l.name && l.name.toLowerCase() === value.toLowerCase().trim());
                    if (matched) {
                        excelGridRows[index].origin_id = matched.id;
                        excelGridRows[index].origin_lat = matched.lat;
                        excelGridRows[index].origin_lng = matched.lng;
                    } else {
                        excelGridRows[index].origin_id = '';
                        excelGridRows[index].origin_lat = '';
                        excelGridRows[index].origin_lng = '';
                    }
                } else {
                    excelGridRows[index].dest_name = value;
                    const matched = (locationData || []).find(l => l.name && l.name.toLowerCase() === value.toLowerCase().trim());
                    if (matched) {
                        excelGridRows[index].dest_id = matched.id;
                        excelGridRows[index].dest_lat = matched.lat;
                        excelGridRows[index].dest_lng = matched.lng;
                    } else {
                        excelGridRows[index].dest_id = '';
                        excelGridRows[index].dest_lat = '';
                        excelGridRows[index].dest_lng = '';
                    }
                }
                renderExcelGrid();
            }

            function selectGridLocItem(index, locType, locName, locId = '', lat = '', lng = '') {
                if (!excelGridRows[index]) return;

                if (locType === 'origin') {
                    excelGridRows[index].origin_name = locName;
                    excelGridRows[index].origin_id = locId;
                    excelGridRows[index].origin_lat = lat;
                    excelGridRows[index].origin_lng = lng;
                } else {
                    excelGridRows[index].dest_name = locName;
                    excelGridRows[index].dest_id = locId;
                    excelGridRows[index].dest_lat = lat;
                    excelGridRows[index].dest_lng = lng;
                }

                renderExcelGrid();
            }

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.grid-searchable-wrap')) {
                    document.querySelectorAll('.grid-loc-dropdown-menu').forEach(m => m.style.display = 'none');
                }
            });

            function checkGridNotesEnter(index, event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (index === excelGridRows.length - 1) {
                        addExcelRow();
                    }
                    setTimeout(() => {
                        const tbody = document.getElementById('excelGridTbody');
                        if (tbody) {
                            const trs = tbody.querySelectorAll('tr');
                            const nextRowIndex = index + 1;
                            if (trs[nextRowIndex]) {
                                const firstInput = trs[nextRowIndex].querySelector('select, input');
                                if (firstInput) firstInput.focus();
                            }
                        }
                    }, 50);
                }
            }

            function checkGridRowTab(index, event) {
                if (event.key === 'Tab' && !event.shiftKey && index === excelGridRows.length - 1) {
                    addExcelRow();
                }
            }

            function openGridMapPicker(index, type) {
                currentGridRowIndex = index;
                currentGridLocType = type;
                openMapPicker('grid_' + type);
            }

            function openExcelPasteModal() {
                const modal = document.getElementById('excelPasteModal');
                if (!modal) return;
                document.getElementById('excelPasteArea').value = '';
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
            }

            function closeExcelPasteModal() {
                const modal = document.getElementById('excelPasteModal');
                if (!modal) return;
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 200);
            }

            function triggerImportExcelToGrid() {
                let input = document.getElementById('gridExcelFileInput');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'file';
                    input.id = 'gridExcelFileInput';
                    input.accept = '.xlsx, .xls, .csv';
                    input.style.display = 'none';
                    document.body.appendChild(input);
                    input.onchange = (e) => {
                        if (e.target.files && e.target.files[0]) {
                            importExcelToGrid(e.target.files[0]);
                            e.target.value = '';
                        }
                    };
                }
                input.click();
            }

            function importExcelToGrid(file) {
                if (!file) return;
                if (typeof XLSX === 'undefined') {
                    showToast('Library SheetJS belum dimuat. Silakan refresh halaman.', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                        if (!rows || rows.length < 2) {
                            showToast('File Excel kosong atau tidak memiliki data.', 'error');
                            return;
                        }

                        const headerRow = rows[0].map(h => String(h || '').toLowerCase().trim());

                        const colMap = {
                            driver: headerRow.findIndex(h => h.includes('driver') || h.includes('nama driver') || h.includes('username')),
                            target_date: headerRow.findIndex(h => h.includes('tanggal') || h.includes('target') || h.includes('waktu') || h.includes('jam')),
                            task_type: headerRow.findIndex(h => h.includes('layanan') || h.includes('tipe') || h.includes('jenis')),
                            receiver: headerRow.findIndex(h => h.includes('penumpang') || h.includes('karyawan') || h.includes('penerima') || h.includes('receiver')),
                            origin: headerRow.findIndex(h => h.includes('asal') || h.includes('origin')),
                            dest: headerRow.findIndex(h => h.includes('tujuan') || h.includes('dest')),
                            notes: headerRow.findIndex(h => h.includes('catatan') || h.includes('notes') || h.includes('keterangan'))
                        };

                        const defaultDate = document.getElementById('gridDefaultDate')?.value || todayDateTimeStr();
                        const defaultDriver = document.getElementById('gridDefaultDriver')?.value || '';
                        const defaultType = document.getElementById('gridDefaultType')?.value || 'antar';

                        const parsedRows = [];
                        for (let i = 1; i < rows.length; i++) {
                            const r = rows[i];
                            if (!r || r.length === 0 || !r.some(cell => cell !== null && cell !== '')) continue;

                            const driverInput = colMap.driver !== -1 ? String(r[colMap.driver] || '').trim() : String(r[0] || '').trim();
                            const dateInput = colMap.target_date !== -1 ? String(r[colMap.target_date] || '').trim() : String(r[1] || '').trim();
                            let typeInput = colMap.task_type !== -1 ? String(r[colMap.task_type] || '').trim() : String(r[2] || defaultType);
                            const receiverInput = colMap.receiver !== -1 ? String(r[colMap.receiver] || '').trim() : String(r[3] || '').trim();
                            const originInput = colMap.origin !== -1 ? String(r[colMap.origin] || '').trim() : String(r[4] || '').trim();
                            const destInput = colMap.dest !== -1 ? String(r[colMap.dest] || '').trim() : String(r[5] || '').trim();
                            const notesInput = colMap.notes !== -1 ? String(r[colMap.notes] || '').trim() : String(r[6] || '').trim();

                            // Match driver
                            let matchedDriverId = defaultDriver;
                            if (driverInput && allDrivers && allDrivers.length > 0) {
                                const dClean = driverInput.toLowerCase().trim();
                                const foundD = allDrivers.find(d => {
                                    const nameClean = (d.driver_name || '').toLowerCase().trim();
                                    return nameClean === dClean ||
                                        String(d.user_id) === dClean ||
                                        (nameClean && dClean && (nameClean.includes(dClean) || dClean.includes(nameClean)));
                                });
                                if (foundD) matchedDriverId = foundD.user_id;
                            }
                            if (!matchedDriverId && allDrivers && allDrivers.length === 1) {
                                matchedDriverId = allDrivers[0].user_id;
                            }

                            // Task type
                            if (typeInput.toLowerCase().includes('jemput') || typeInput.toLowerCase().includes('kirim')) {
                                typeInput = 'kirim';
                            } else {
                                typeInput = 'antar';
                            }

                            // Format date
                            let formattedDate = dateInput || defaultDate;
                            if (formattedDate && !formattedDate.includes('T')) {
                                formattedDate = formattedDate.replace(' ', 'T');
                            }

                            // Match origin location
                            let originId = '', originLat = '', originLng = '';
                            if (originInput && locationData) {
                                const foundLoc = locationData.find(l => l.name && l.name.toLowerCase().trim() === originInput.toLowerCase());
                                if (foundLoc) {
                                    originId = foundLoc.id;
                                    originLat = foundLoc.lat;
                                    originLng = foundLoc.lng;
                                }
                            }

                            // Match dest location
                            let destId = '', destLat = '', destLng = '';
                            if (destInput && locationData) {
                                const foundLoc = locationData.find(l => l.name && l.name.toLowerCase().trim() === destInput.toLowerCase());
                                if (foundLoc) {
                                    destId = foundLoc.id;
                                    destLat = foundLoc.lat;
                                    destLng = foundLoc.lng;
                                }
                            }

                            parsedRows.push({
                                driver_id: matchedDriverId,
                                target_date: formattedDate,
                                task_type: typeInput,
                                receiver_name: receiverInput,
                                origin_name: originInput,
                                origin_id: originId,
                                origin_lat: originLat,
                                origin_lng: originLng,
                                dest_name: destInput,
                                dest_id: destId,
                                dest_lat: destLat,
                                dest_lng: destLng,
                                notes: notesInput
                            });
                        }

                        if (parsedRows.length === 0) {
                            showToast('Tidak ada data yang valid ditemukan dalam Excel.', 'error');
                            return;
                        }

                        const isCurrentEmpty = excelGridRows.length === 1 && !excelGridRows[0].receiver_name && !excelGridRows[0].origin_name && !excelGridRows[0].driver_id;
                        if (isCurrentEmpty) {
                            excelGridRows = parsedRows;
                        } else {
                            excelGridRows = excelGridRows.concat(parsedRows);
                        }

                        renderExcelGrid();
                        showToast(`✓ Berhasil mengimpor ${parsedRows.length} baris dari file Excel`, 'success');
                    } catch (err) {
                        console.error('Error importing excel:', err);
                        showToast('Gagal membaca file Excel. Pastikan format file sesuai.', 'error');
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            async function downloadExcelTemplate() {
                const sampleDate = todayDateTimeStr().replace('T', ' ');

                let driverList = [];
                if (typeof allDrivers !== 'undefined' && Array.isArray(allDrivers) && allDrivers.length > 0) {
                    driverList = allDrivers.map(d => d.driver_name || d.name || d.username).filter(Boolean);
                } else {
                    try {
                        const res = await fetch(`${API_URL}?action=get_drivers`);
                        const data = await res.json();
                        if (Array.isArray(data)) {
                            driverList = data.map(d => d.driver_name || d.name || d.username).filter(Boolean);
                        }
                    } catch (e) { console.error(e); }
                }

                let originSample = 'HEAD OFFICE';
                let destSample = 'WAREHOUSE SOMETHINC-BEAUTY HAUL';
                if (typeof locationData !== 'undefined' && Array.isArray(locationData) && locationData.length > 0) {
                    if (locationData[0] && locationData[0].name) originSample = locationData[0].name;
                    if (locationData[1] && locationData[1].name) destSample = locationData[1].name;
                    else destSample = originSample;
                }

                const driver1 = driverList[0] || 'Driver HO';
                const driver2 = driverList[1] || driver1;

                if (typeof XLSX !== 'undefined') {
                    const data = [
                        ["Driver", "Tanggal & Jam", "Layanan", "Penumpang / Karyawan", "Lokasi Asal", "Lokasi Tujuan", "Catatan"],
                        [driver1, sampleDate, "antar", "Pak Budi & Tim Marketing", originSample, destSample, "JEMPUT DI LOBBY"],
                        [driver2, sampleDate, "kirim", "Tim HRD", destSample, originSample, "DOKUMEN PENTING"]
                    ];
                    const ws = XLSX.utils.aoa_to_sheet(data);
                    ws['!cols'] = [
                        { wch: 22 }, { wch: 20 }, { wch: 12 }, { wch: 25 }, { wch: 28 }, { wch: 28 }, { wch: 30 }
                    ];

                    if (driverList.length > 0) {
                        const driverValidationStr = driverList.slice(0, 50).join(',');
                        ws['!dataValidation'] = [
                            { sqref: 'A2:A500', type: 'list', values: [`"${driverValidationStr}"`] },
                            { sqref: 'C2:C500', type: 'list', values: ['"antar,kirim"'] }
                        ];
                    }

                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Template_Tugas");
                    XLSX.writeFile(wb, "Template_Import_Tugas_TMS.xlsx");
                } else {
                    const csvContent = "data:text/csv;charset=utf-8,"
                        + "Driver\tTanggal & Jam\tLayanan\tPenumpang / Karyawan\tLokasi Asal\tLokasi Tujuan\tCatatan\n"
                        + `${driver1}\t${sampleDate}\tantar\tPak Budi & Tim Marketing\t${originSample}\t${destSample}\tCATATAN SJ\n`;
                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "Template_Import_Tugas_TMS.csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }

            function processExcelPasteData() {
                const rawText = document.getElementById('excelPasteArea').value.trim();
                if (!rawText) {
                    showToast('Silakan paste data Excel terlebih dahulu.', 'warning');
                    return;
                }

                const lines = rawText.split(/\r?\n/);
                const parsedRows = [];
                const defaultDate = document.getElementById('gridDefaultDate')?.value || todayDateTimeStr();
                const defaultDriver = document.getElementById('gridDefaultDriver')?.value || '';

                lines.forEach(line => {
                    if (!line.trim()) return;
                    const cols = line.split('\t');
                    if (cols.length === 0) return;

                    // Skip header row if pasted with header
                    if (cols[0] && cols[0].trim().toLowerCase() === 'driver') return;

                    let driverInput = cols[0] ? cols[0].trim() : '';
                    let matchedDriverId = defaultDriver;
                    if (driverInput && allDrivers && allDrivers.length > 0) {
                        const dClean = driverInput.toLowerCase().trim();
                        const foundD = allDrivers.find(d => {
                            const nameClean = (d.driver_name || '').toLowerCase().trim();
                            return nameClean === dClean ||
                                String(d.user_id) === dClean ||
                                (nameClean && dClean && (nameClean.includes(dClean) || dClean.includes(nameClean)));
                        });
                        if (foundD) matchedDriverId = foundD.user_id;
                    }
                    if (!matchedDriverId && allDrivers && allDrivers.length === 1) {
                        matchedDriverId = allDrivers[0].user_id;
                    }

                    let targetDate = cols[1] ? cols[1].trim() : defaultDate;
                    let taskType = cols[2] ? cols[2].trim().toLowerCase() : 'antar';
                    if (taskType.includes('jemput') || taskType.includes('kirim')) taskType = 'kirim';
                    else taskType = 'antar';

                    let receiverName = cols[3] ? cols[3].trim() : '';
                    let originName = cols[4] ? cols[4].trim() : '-';
                    let destName = cols[5] ? cols[5].trim() : '-';
                    let notes = cols[6] ? cols[6].trim() : '';

                    parsedRows.push({
                        driver_id: matchedDriverId,
                        target_date: targetDate,
                        task_type: taskType,
                        receiver_name: receiverName,
                        origin_name: originName || '-',
                        origin_id: '',
                        origin_lat: '',
                        origin_lng: '',
                        dest_name: destName || '-',
                        dest_id: '',
                        dest_lat: '',
                        dest_lng: '',
                        notes: notes
                    });
                });

                if (parsedRows.length > 0) {
                    const isCurrentEmpty = excelGridRows.length === 1 && !excelGridRows[0].receiver_name && !excelGridRows[0].origin_name;
                    if (isCurrentEmpty) {
                        excelGridRows = parsedRows;
                    } else {
                        excelGridRows = excelGridRows.concat(parsedRows);
                    }
                    renderExcelGrid();
                    closeExcelPasteModal();
                    showToast(`✓ Berhasil mengimpor ${parsedRows.length} baris dari Excel`, 'success');
                }
            }

            async function submitExcelGrid() {
                const formMsg = document.getElementById('formMsg');
                const btnSubmit = document.getElementById('btnSubmitGrid');

                const validRows = excelGridRows.filter(r =>
                    r.driver_id || r.receiver_name || r.origin_name || r.dest_name
                );

                if (validRows.length === 0) {
                    if (formMsg) formMsg.innerHTML = `<span style="color:#ef4444; font-weight:700;">⚠ Harap isi minimal 1 baris tugas.</span>`;
                    return;
                }

                let validationError = '';
                validRows.forEach((r, idx) => {
                    const rowNum = idx + 1;
                    if (!r.driver_id) validationError += `Baris ${rowNum}: Driver wajib dipilih.<br>`;
                    if (!r.target_date) validationError += `Baris ${rowNum}: Tanggal & Jam wajib diisi.<br>`;
                    if (!r.receiver_name) validationError += `Baris ${rowNum}: Nama Penumpang/Karyawan wajib diisi.<br>`;
                    // Origin and Dest location are optional!
                });

                if (validationError) {
                    if (formMsg) formMsg.innerHTML = `<div style="color:#ef4444; font-size:0.85rem; font-weight:600; text-align:left; background:#fee2e2; padding:8px 12px; border-radius:6px;">${validationError}</div>`;
                    return;
                }

                const tasksToSend = validRows.map(r => {
                    const driverObj = (allDrivers || []).find(d => String(d.user_id) === String(r.driver_id));
                    const driverName = driverObj ? driverObj.driver_name : '';

                    return {
                        driver_id: r.driver_id,
                        driver_name: driverName,
                        target_date: r.target_date,
                        task_type: r.task_type,
                        receiver_name: r.receiver_name,
                        passenger_name: r.receiver_name,
                        origin_name: r.origin_name ? r.origin_name.trim() : '-',
                        origin_id: r.origin_id || null,
                        origin_lat: r.origin_lat || null,
                        origin_lng: r.origin_lng || null,
                        destination_name: r.dest_name ? r.dest_name.trim() : '-',
                        dest_name: r.dest_name ? r.dest_name.trim() : '-',
                        dest_id: r.dest_id || null,
                        dest_lat: r.dest_lat || null,
                        dest_lng: r.dest_lng || null,
                        notes: r.notes,
                        surat_jalan: 'HO-TASK'
                    };
                });

                try {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = `<span class="spinner"></span> Menyimpan ${tasksToSend.length} Tugas...`;
                    if (formMsg) formMsg.innerHTML = `<span style="color:#3b82f6; font-weight:600;">Mengirim data tugas ke server...</span>`;

                    const res = await fetch(`${API_URL}?action=bulk_assign_tasks`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(tasksToSend)
                    });

                    const data = await res.json();
                    if (data.success) {
                        closeAdd();
                        showToast(`✓ Berhasil menambahkan ${data.count || tasksToSend.length} tugas baru!`, 'success');
                        loadHistory();
                    } else {
                        let errText = data.error || 'Gagal menyimpan tugas.';
                        if (data.errors && data.errors.length > 0) {
                            errText += '<br>' + data.errors.join('<br>');
                        }
                        if (formMsg) formMsg.innerHTML = `<div style="color:#ef4444; font-size:0.85rem; font-weight:600; text-align:left; background:#fee2e2; padding:8px 12px; border-radius:6px;">${errText}</div>`;
                    }
                } catch (err) {
                    console.error(err);
                    if (formMsg) formMsg.innerHTML = `<span style="color:#ef4444; font-weight:700;">Terjadi kesalahan jaringan/server.</span>`;
                } finally {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = `<span class="material-symbols-outlined">send</span> Berikan Semua Tugas (${validRows.length})`;
                }
            }

            // ===== DETAIL MODAL LOGIC =====
            let detailMap = null;
            let detailMarker = null;

            function openDetail(id) {
                const del = historyData.find(d => d.id === id && !d.is_request);
                if (!del) return;

                const content = document.getElementById('detailContent');

                let proofHtml = '';
                if (del.proof_file_arr && del.proof_file_arr.length > 0) {
                    const photos = del.proof_file_arr;
                    proofHtml = `
                    <div style="margin-top: 1.25rem;">
                        <div class="time-label" style="margin-bottom:8px;">Foto Bukti Selesai (${photos.length})</div>
                        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.4rem;">
                            ${photos.map(f => `<img src="uploads/${f}" class="photo-thumb" onclick="window.showImagePopup('uploads/${f}')" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:6px; border:1px solid var(--border);" title="Klik untuk memperbesar">`).join('')}
                        </div>
                    </div>
                `;
                }

                const isReq = del.pickup_id || del.request_created_at;
                const typeLabel = del.task_type === 'antar' ? 'DELIVERY' : (isReq ? 'REQ PICKUP' : 'PICKUP');
                const typeClass = del.task_type === 'antar' ? 'badge-antar' : 'badge-kirim';
                const statusClass = del.status === 'pending' ? 'badge-pending' : (del.status === 'in_transit' ? 'badge-transit' : (del.status === 'canceled' ? 'badge-danger' : 'badge-completed'));

                content.innerHTML = `
                <table class="detail-table">
                    <tr>
                        <td>Tipe Tugas</td>
                        <td><span class="detail-badge ${typeClass}">${typeLabel}</span></td>
                    </tr>
                    ${isReq ? `
                    <tr>
                        <td>Request By</td>
                        <td style="color:var(--primary);">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">person</span>
                            ${del.requester_name || 'User'}
                        </td>
                    </tr>` : ''}
                    <tr>
                        <td>Assign By</td>
                        <td>
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; color:var(--primary);">person_edit</span>
                            ${del.creator_name || 'System'}
                        </td>
                    </tr>
                    <tr>
                        <td>Driver</td>
                        <td>${del.driver_name}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span class="detail-badge ${statusClass}">${(del.status || 'pending').replace('_', ' ').toUpperCase()}</span></td>
                    </tr>
                    <tr>
                        <td>Rute Perjalanan</td>
                        <td style="line-height:1.4;">
                            <div style="font-size:0.75rem; color:var(--text-sub);">${formatLocationDisplay(del.origin_name)}</div>
                            <div style="font-weight:700;">&raquo; ${formatLocationDisplay(del.destination_name, del.destination_lat, del.destination_lng)}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>Spidometer Awal</td>
                        <td>
                            ${del.speedometer_start_num ? `<strong>${Number(del.speedometer_start_num).toLocaleString('id-ID')} KM</strong>` : '-'}
                            ${del.speedometer_start_photo ? ` <button class="btn-link" onclick="window.showImagePopup('uploads/${del.speedometer_start_photo}')" style="font-size:0.75rem; color:var(--primary); background:none; border:none; cursor:pointer; text-decoration:underline;">[Lihat Foto Awal]</button>` : ''}
                        </td>
                    </tr>
                    <tr>
                        <td>Spidometer Akhir</td>
                        <td>
                            ${del.speedometer_end_num ? `<strong>${Number(del.speedometer_end_num).toLocaleString('id-ID')} KM</strong>` : '-'}
                            ${del.speedometer_end_photo ? ` <button class="btn-link" onclick="window.showImagePopup('uploads/${del.speedometer_end_photo}')" style="font-size:0.75rem; color:var(--primary); background:none; border:none; cursor:pointer; text-decoration:underline;">[Lihat Foto Akhir]</button>` : ''}
                        </td>
                    </tr>
                    ${(del.speedometer_start_num && del.speedometer_end_num) ? `
                    <tr>
                        <td>Jarak</td>
                        <td style="font-weight:800; color:var(--primary); font-size:0.9rem;">
                            ${Number(del.speedometer_end_num - del.speedometer_start_num).toLocaleString('id-ID')} KM
                        </td>
                    </tr>` : ''}
                    ${del.receiver_name ? `
                    <tr>
                        <td>Penumpang / Karyawan</td>
                        <td>${del.receiver_name}</td>
                    </tr>` : ''}
                    <tr>
                        <td>Jadwal</td>
                        <td>${formatDateTime(del.target_date)}</td>
                    </tr>
                    ${del.notes ? `
                    <tr>
                        <td>Catatan Admin</td>
                        <td style="font-weight:400; font-size:0.8rem; color:var(--text-sub); font-style:italic;">"${del.notes}"</td>
                    </tr>` : ''}
                    ${del.driver_notes ? `
                    <tr>
                        <td>Catatan Driver</td>
                        <td style="color:var(--success); font-weight:600;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">chat</span>
                            ${del.driver_notes}
                        </td>
                    </tr>` : ''}
                    ${del.late_reason ? `
                    <tr>
                        <td>Alasan Terlambat</td>
                        <td style="color:var(--danger); font-weight:600;">
                            <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">warning</span>
                            ${del.late_reason}
                        </td>
                    </tr>` : ''}
                </table>
                ${(() => {
                        let files = [];
                        const raw = del.surat_jalan_file; // Array from API
                        const reqRaw = del.request_sj_file_arr; // Array from API

                        if (Array.isArray(raw) && raw.length > 0) {
                            files = raw;
                        } else if (Array.isArray(reqRaw) && reqRaw.length > 0) {
                            files = reqRaw;
                        }

                        if (files.length === 0) return '';
                        return `
                        <div style="margin-top: 0.5rem;">
                            <div class="time-label" style="margin-bottom:8px;">Foto Surat Jalan (Admin/Request)</div>
                            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.4rem;">
                                ${files.map(f => `<img src="uploads/${f}" class="photo-thumb" onclick="window.showImagePopup('uploads/${f}')" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:6px; border:1px solid var(--border);" title="Klik untuk memperbesar">`).join('')}
                            </div>
                        </div>
                    `;
                    })()}

                ${(() => {
                        let allGoods = [];

                        // From Delivery
                        const rawGoods = del.goods_file_arr || del.goods_file;
                        if (Array.isArray(rawGoods)) {
                            allGoods = [...allGoods, ...rawGoods];
                        } else if (rawGoods) {
                            try {
                                const parsed = JSON.parse(rawGoods);
                                if (parsed) {
                                    allGoods = [...allGoods, ...(Array.isArray(parsed) ? parsed : [parsed])];
                                }
                            } catch (e) {
                                if (typeof rawGoods === 'string' && !rawGoods.startsWith('[') && !rawGoods.startsWith('{')) {
                                    allGoods.push(rawGoods);
                                }
                            }
                        }

                        // From Request
                        const reqRawGoods = del.request_goods;
                        if (Array.isArray(reqRawGoods)) {
                            allGoods = [...allGoods, ...reqRawGoods];
                        }

                        // Deduplicate and filter empty
                        const uniqueGoods = [...new Set(allGoods)].filter(f => f && typeof f === 'string' && f.trim() !== '');

                        if (uniqueGoods.length === 0) return '';
                        return `
                        <div style="margin-top: 0.6rem;">
                            <div class="time-label" style="margin-bottom:6px; font-size:0.6rem;">Foto Barang / Koli (${uniqueGoods.length})</div>
                            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.4rem;">
                                ${uniqueGoods.map(f => `<img src="uploads/${f}" class="photo-thumb" onclick="window.showImagePopup('uploads/${f}')" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:6px; border:1px solid var(--border);" title="Klik untuk memperbesar">`).join('')}
                            </div>
                        </div>
                    `;
                    })()}
                ${proofHtml}
            `;

                document.getElementById('detailStartTime').innerText = formatDateTime(del.start_time) || 'Belum Mulai';
                document.getElementById('detailEndTime').innerText = formatDateTime(del.end_time) || 'Belum Selesai';

                const modal = document.getElementById('detailModal');
                const mapContainer = document.getElementById('detailMap');
                if (del.status === 'completed') {
                    mapContainer.style.display = 'none';
                } else {
                    mapContainer.style.display = 'block';
                    setTimeout(() => {
                        if (!detailMap) {
                            detailMap = L.map('detailMap').setView([-6.200000, 106.816666], 13);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMap);
                        }
                        if (detailMarker) detailMap.removeLayer(detailMarker);
                        if (del.driver_lat && del.driver_lng) {
                            const latlng = [del.driver_lat, del.driver_lng];
                            detailMap.setView(latlng, 15);
                            detailMarker = L.marker(latlng).addTo(detailMap)
                                .bindPopup(`<b>${del.driver_name}</b><br>Posisi Terakhir<br><small>${del.location_updated}</small>`)
                                .openPopup();
                        } else {
                            detailMap.setView([-6.200000, 106.816666], 11);
                        }
                        detailMap.invalidateSize();
                    }, 300);
                }

                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
            }

            function closeDetail() {
                const modal = document.getElementById('detailModal');
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 200);
            }

            // (Functions moved to top)

            // Updated single Assign Modal logic
            let currentAssignReqId = null;
            function openAssignModal(id) {
                isBulkMode = false;
                currentAssignReqId = id;
                const req = historyData.find(r => r.id === id && r.is_request);
                if (!req) return;

                document.getElementById('assignModalTitle').innerText = `Assign Tugas: ${req.surat_jalan || 'Request'}`;

                // Populate driver list with checkboxes
                const container = document.getElementById('driverCheckboxList');
                container.innerHTML = allDrivers.map(d => `
                <label class="driver-checkbox-item">
                    <input type="checkbox" name="assign_drivers" value="${d.user_id}">
                    <div class="driver-info">
                        <span class="name">${d.driver_name}</span>
                        <span class="status ${d.is_shipping ? 'busy' : 'free'}">${d.is_shipping ? 'Sedang Jalan' : 'Tersedia'}</span>
                    </div>
                </label>
            `).join('');

                // Pre-fill date
                document.getElementById('assignTargetDate').value = req.scheduled_date ? req.scheduled_date.replace(' ', 'T') : todayDateTimeStr();

                const modal = document.getElementById('assignModal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
            }

            function closeAssignModal() {
                const modal = document.getElementById('assignModal');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    currentAssignReqId = null;
                }, 200);
            }

            async function submitMultipleAssign() {
                const selectedDrivers = Array.from(document.querySelectorAll('input[name="assign_drivers"]:checked')).map(cb => cb.value);
                if (selectedDrivers.length === 0) {
                    showToast('Pilih minimal satu driver!', 'error');
                    return;
                }

                const btn = document.getElementById('submitAssignBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="material-symbols-outlined rotating">autorenew</span> Memproses...';

                const targets = isBulkMode ? selectedReqIds : [currentAssignReqId];
                const targetDate = document.getElementById('assignTargetDate').value;

                try {
                    let successCount = 0;
                    for (const requestId of targets) {
                        const req = historyData.find(r => r.id === requestId);
                        if (!req) continue;

                        for (const driverId of selectedDrivers) {
                            const formData = new FormData();
                            formData.append('driver_id', driverId);
                            formData.append('pickup_id', req.id);
                            formData.append('origin_name', req.origin_name);
                            formData.append('dest_name', req.destination_name);
                            formData.append('task_type', 'kirim');
                            formData.append('surat_jalan', req.surat_jalan);
                            formData.append('total_koli', req.total_koli);
                            formData.append('notes', req.notes);
                            formData.append('target_date', targetDate);

                            if (req.surat_jalan_file) {
                                formData.append('existing_file', req.surat_jalan_file);
                            }

                            if (locationData) {
                                const loc = locationData.find(l => l.name === req.destination_name);
                                if (loc) {
                                    formData.append('dest_id', loc.id);
                                    formData.append('dest_lat', loc.lat);
                                    formData.append('dest_lng', loc.lng);
                                }
                            }

                            await fetch(`${API_URL}?action=assign_task`, { method: 'POST', body: formData });
                        }
                        successCount++;
                    }

                    closeAssignModal();
                    loadHistory();
                    if (window.updateSidebarBadge) updateSidebarBadge();
                    showToast(`Berhasil menugaskan ${successCount} permintaan ke ${selectedDrivers.length} driver.`);

                    // Reset bulk
                    isBulkMode = false;
                    selectedReqIds = [];
                    updateBulkBtn();
                    const selAll = document.getElementById('selectAllReqs');
                    if (selAll) selAll.checked = false;

                } catch (err) {
                    showToast('Terjadi kesalahan saat memproses penugasan.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined">send</span> Konfirmasi Penugasan';
                }
            }

            // ===== OPEN / CLOSE EDIT MODAL =====
            async function openEdit(id, fieldToEdit = null) {
                document.getElementById('editFormMsg').innerHTML = '';

                // First try finding task in local historyData
                let d = (typeof historyData !== 'undefined' && Array.isArray(historyData)) ? historyData.find(x => x.id == id) : null;

                if (!d) {
                    try {
                        const res = await fetch(`api.php?action=get_delivery&id=${id}`);
                        d = await res.json();
                    } catch (err) {
                        console.error('Fetch delivery error:', err);
                    }
                }

                if (!d || d.error) {
                    showToast('Gagal memuat data penugasan.', 'error');
                    return;
                }

                const editIdInput = document.getElementById('edit_id') || document.getElementById('editId');
                if (editIdInput) editIdInput.value = d.id;

                const targetDateInput = document.getElementById('editTargetDate');
                if (targetDateInput) targetDateInput.value = d.target_date ? d.target_date.replace(' ', 'T').slice(0, 16) : todayDateTimeStr();

                const taskTypeInput = document.getElementById('editTaskType');
                if (taskTypeInput) taskTypeInput.value = d.task_type || 'antar';

                const statusInput = document.getElementById('editStatus');
                if (statusInput) statusInput.value = d.status || 'pending';

                const originNameInput = document.getElementById('edit_origin_name');
                if (originNameInput) originNameInput.value = d.origin_name || '';

                const destNameInput = document.getElementById('edit_dest_name');
                if (destNameInput) destNameInput.value = d.destination_name || '';

                const destLatInput = document.getElementById('edit_dest_lat');
                if (destLatInput) destLatInput.value = d.destination_lat || '';

                const destLngInput = document.getElementById('edit_dest_lng');
                if (destLngInput) destLngInput.value = d.destination_lng || '';

                const notesInput = document.getElementById('editNotes');
                if (notesInput) notesInput.value = d.notes || '';

                const receiverNameInput = document.getElementById('editReceiverName');
                if (receiverNameInput) receiverNameInput.value = d.receiver_name || '';

                // Set Driver select
                const driverSelect = document.getElementById('editDriverSelect');
                if (driverSelect) driverSelect.value = d.driver_id || '';
                const driverInput = document.querySelector('#editDriverList')?.parentElement?.querySelector('.searchable-input');
                if (driverInput) driverInput.value = d.driver_name || '';

                // Set Origin & Dest selects
                const originSelect = document.getElementById('editOriginSelect');
                if (originSelect) originSelect.value = d.origin_id || '';
                const originInput = document.querySelector('#editOriginList')?.parentElement?.querySelector('.searchable-input');
                if (originInput) originInput.value = d.origin_name || '';

                const editOriginLat = document.getElementById('edit_origin_lat');
                if (editOriginLat) editOriginLat.value = d.origin_lat || '';
                const editOriginLng = document.getElementById('edit_origin_lng');
                if (editOriginLng) editOriginLng.value = d.origin_lng || '';

                const destSelect = document.getElementById('editDestSelect');
                if (destSelect) destSelect.value = d.dest_id || d.destination_id || '';
                const destInput = document.querySelector('#editDestList')?.parentElement?.querySelector('.searchable-input');
                if (destInput) destInput.value = d.destination_name || '';

                const editDestLat = document.getElementById('edit_dest_lat');
                if (editDestLat) editDestLat.value = d.destination_lat || d.dest_lat || '';
                const editDestLng = document.getElementById('edit_dest_lng');
                if (editDestLng) editDestLng.value = d.destination_lng || d.dest_lng || '';

                // Set Google Maps URL link fields in edit modal if they are links
                const editOriginGmapsUrl = document.getElementById('editOriginGmapsUrl');
                const editOriginGmapsBadge = document.getElementById('editOriginGmapsBadge');
                if (editOriginGmapsUrl) {
                    if (d.origin_name && (d.origin_name.startsWith('http://') || d.origin_name.startsWith('https://'))) {
                        editOriginGmapsUrl.value = d.origin_name;
                        if (editOriginGmapsBadge) {
                            if (d.origin_lat && d.origin_lng) {
                                editOriginGmapsBadge.innerText = `✓ GPS Terbaca: ${d.origin_lat}, ${d.origin_lng}`;
                            } else {
                                editOriginGmapsBadge.innerText = '✓ Link Google Maps tersimpan';
                            }
                            editOriginGmapsBadge.style.display = 'block';
                        }
                    } else if (d.origin_lat && d.origin_lng) {
                        if (editOriginGmapsBadge) {
                            editOriginGmapsBadge.innerText = `✓ GPS Terbaca: ${d.origin_lat}, ${d.origin_lng}`;
                            editOriginGmapsBadge.style.display = 'block';
                        }
                    } else {
                        editOriginGmapsUrl.value = '';
                        if (editOriginGmapsBadge) editOriginGmapsBadge.style.display = 'none';
                    }
                }

                const editDestGmapsUrl = document.getElementById('editDestGmapsUrl');
                const editDestGmapsBadge = document.getElementById('editDestGmapsBadge');
                if (editDestGmapsUrl) {
                    if (d.destination_name && (d.destination_name.startsWith('http://') || d.destination_name.startsWith('https://'))) {
                        editDestGmapsUrl.value = d.destination_name;
                        if (editDestGmapsBadge) {
                            if (d.destination_lat && d.destination_lng) {
                                editDestGmapsBadge.innerText = `✓ GPS Terbaca: ${d.destination_lat}, ${d.destination_lng}`;
                            } else {
                                editDestGmapsBadge.innerText = '✓ Link Google Maps tersimpan';
                            }
                            editDestGmapsBadge.style.display = 'block';
                        }
                    } else {
                        editDestGmapsUrl.value = '';
                        if (editDestGmapsBadge) editDestGmapsBadge.style.display = 'none';
                    }
                }

                const modal = document.getElementById('editModal');
                if (modal) {
                    modal.style.display = 'flex';
                    setTimeout(() => modal.classList.add('show'), 10);
                }
            }

            function showEditField(fieldName) {
                const groups = document.querySelectorAll('#editModal .form-group');
                let found = false;
                groups.forEach(g => {
                    const field = g.getAttribute('data-edit-field');
                    // Special case: 'driver' column can also show 'date'
                    if (field === fieldName || (fieldName === 'driver' && field === 'date')) {
                        g.style.display = 'block';
                        found = true;
                    } else {
                        g.style.display = 'none';
                    }
                });
                document.getElementById('showAllEditWrap').style.display = 'block';

                // Update title
                const titles = { 'driver': 'Edit Driver & Tanggal', 'route': 'Edit Rute Pengiriman', 'sj': 'Edit Surat Jalan & Koli', 'status': 'Edit Status' };
                document.querySelector('#editModal .modal-title').innerHTML = `<span class="material-symbols-outlined">edit_note</span> ${titles[fieldName] || 'Edit Penugasan'}`;
            }

            function showAllEditFields() {
                document.querySelectorAll('#editModal .form-group').forEach(g => g.style.display = 'block');
                document.getElementById('showAllEditWrap').style.display = 'none';
                document.querySelector('#editModal .modal-title').innerHTML = `<span class="material-symbols-outlined">edit_note</span> Edit Penugasan`;
            }

            function closeEdit() {
                const modal = document.getElementById('editModal');
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 200);

                // Reset file state
                editSelectedFiles = { 'editSjPrev': [], 'editGoodsPrev': [], 'editProofPrev': [] };
                document.getElementById('editSjExisting').value = '';
                document.getElementById('editGoodsExisting').value = '';
                document.getElementById('editProofExisting').value = '';
                document.getElementById('editSjPrev').innerHTML = '';
                document.getElementById('editGoodsPrev').innerHTML = '';
                document.getElementById('editProofPrev').innerHTML = '';
                document.getElementById('editSjInput').value = '';
                document.getElementById('editGoodsInput').value = '';
                document.getElementById('editProofInput').value = '';
            }

            // ===== LOAD FORM DATA (drivers + locations) =====
            async function loadFormData() {
                try {
                    const [dRes, lRes] = await Promise.all([
                        fetch(`${API_URL}?action=get_drivers`),
                        fetch(`${API_URL}?action=get_locations`)
                    ]);
                    allDrivers = await dRes.json();
                    locationData = await lRes.json();

                    populateDriverSelect('driverSelect');
                    populateDriverSelect('driverFilter', true);
                    populateLocationSelects('originSelect', 'destSelect');
                    populateGridPresetDrivers();
                    renderExcelGrid();
                } catch (err) { console.error(err); }
            }

            function populateDriverSelect(selectId, addAll = false) {
                const sel = document.getElementById(selectId);
                if (!sel) return;
                const cur = sel.value;
                sel.innerHTML = addAll
                    ? '<option value="">Semua Driver</option>'
                    : '<option value="">-- Pilih Driver --</option>';
                allDrivers.forEach(d => {
                    sel.innerHTML += `<option value="${d.user_id}">${d.driver_name}</option>`;
                });
                if (cur) sel.value = cur;
            }

            function populateLocationSelects(originId, destId) {
                renderLocationOptions('originList', 'originSelect', 'origin_name');
                renderLocationOptions('destList', 'destSelect', 'dest_name', true);
                renderDriverOptions('driverList', 'driverSelect');
            }

            function renderDriverOptions(listId, hiddenInputId) {
                const list = document.getElementById(listId);
                if (!list) return;
                list.innerHTML = `
                <div style="padding:10px; border-bottom:1px solid var(--border); position:sticky; top:0; background:white;">
                    <input type="text" placeholder="Cari driver..." class="select-search" onkeyup="filterOptions(this, '${listId}')" style="margin:0;">
                </div>
            `;

                allDrivers.sort((a, b) => a.driver_name.localeCompare(b.driver_name)).forEach(d => {
                    const div = document.createElement('div');
                    div.className = 'option-item';
                    div.innerHTML = `👤 ${d.driver_name} ${d.is_shipping ? '<span style="font-size:0.7rem; color:#f59e0b; margin-left:auto;">Sedang Jalan</span>' : ''}`;
                    div.onclick = () => selectDriver(d, listId, hiddenInputId);
                    list.appendChild(div);
                });
            }

            function selectDriver(driver, listId, hiddenInputId) {
                const list = document.getElementById(listId);
                const hidden = document.getElementById(hiddenInputId);
                const display = list.parentElement.querySelector('.searchable-input');

                hidden.value = driver.user_id;
                display.value = driver.driver_name;

                list.classList.remove('show');
                list.querySelectorAll('.option-item').forEach(item => item.classList.remove('selected'));
                event.currentTarget.classList.add('selected');
            }

            function renderLocationOptions(listId, hiddenInputId, nameInputId, isDest = false) {
                const list = document.getElementById(listId);
                if (!list) return;
                list.innerHTML = `
                <div style="padding:10px; border-bottom:1px solid var(--border); position:sticky; top:0; background:white;">
                    <input type="text" placeholder="Ketik untuk mencari..." class="select-search" onkeyup="filterOptions(this, '${listId}')" style="margin:0;">
                </div>
            `;

                locationData.sort((a, b) => a.name.localeCompare(b.name)).forEach(loc => {
                    const div = document.createElement('div');
                    div.className = 'option-item';
                    div.innerHTML = `${loc.name}`;
                    div.onclick = () => selectLocation(loc, listId, hiddenInputId, nameInputId, isDest);
                    list.appendChild(div);
                });
            }

            function toggleOptions(listId) {
                // Close other lists
                document.querySelectorAll('.options-list').forEach(l => {
                    if (l.id !== listId) l.classList.remove('show');
                });
                const list = document.getElementById(listId);
                list.classList.toggle('show');

                // Focus search input automatically
                if (list.classList.contains('show')) {
                    const searchInput = list.querySelector('.select-search');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.value = ''; // Clear search when opening
                            searchInput.focus();
                            // Reset filter to show all
                            filterOptions(searchInput, listId);
                        }, 50);
                    }
                }
            }

            function filterOptions(input, listId) {
                const filter = input.value.toLowerCase();
                const list = document.getElementById(listId);
                const items = list.querySelectorAll('.option-item');
                items.forEach(item => {
                    const txt = item.innerText.toLowerCase();
                    item.style.display = txt.includes(filter) ? 'flex' : 'none';
                });
            }

            function selectLocation(loc, listId, hiddenInputId, nameInputId, isDest) {
                const list = document.getElementById(listId);
                const hidden = document.getElementById(hiddenInputId);
                const nameInp = document.getElementById(nameInputId);
                const display = list.parentElement.querySelector('.searchable-input');

                hidden.value = loc.id;
                nameInp.value = loc.name;
                display.value = loc.name;

                let latId = isDest ? 'dest_lat' : 'origin_lat';
                let lngId = isDest ? 'dest_lng' : 'origin_lng';

                if (hiddenInputId.includes('edit')) {
                    latId = isDest ? 'edit_dest_lat' : 'edit_origin_lat';
                    lngId = isDest ? 'edit_dest_lng' : 'edit_origin_lng';
                } else if (hiddenInputId.startsWith('qe_')) {
                    latId = isDest ? 'qe_dest_lat' : 'qe_origin_lat';
                    lngId = isDest ? 'qe_dest_lng' : 'qe_origin_lng';
                }

                const latInp = document.getElementById(latId);
                const lngInp = document.getElementById(lngId);
                if (latInp) latInp.value = loc.lat || '';
                if (lngInp) lngInp.value = loc.lng || '';

                list.classList.remove('show');

                // Highlight selected
                list.querySelectorAll('.option-item').forEach(item => item.classList.remove('selected'));
                if (event && event.currentTarget && event.currentTarget.classList) {
                    event.currentTarget.classList.add('selected');
                }

                // Update compact loc-display and close popover
                updateLocDisplay(hiddenInputId, loc.name);
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.searchable-group')) {
                    document.querySelectorAll('.options-list').forEach(l => l.classList.remove('show'));
                }
                // Close loc-popovers when clicking outside
                if (!e.target.closest('.loc-picker-row')) {
                    document.querySelectorAll('.loc-popover.show').forEach(p => p.classList.remove('show'));
                }
            });

            // ===== LOCATION POPOVER FUNCTIONS =====
            function toggleLocPopover(popoverId) {
                // Close other popovers
                document.querySelectorAll('.loc-popover.show').forEach(p => {
                    if (p.id !== popoverId) p.classList.remove('show');
                });
                const pop = document.getElementById(popoverId);
                if (pop) pop.classList.toggle('show');
            }

            function closeLocPopover(popoverId) {
                const pop = document.getElementById(popoverId);
                if (pop) pop.classList.remove('show');
            }

            // Helper: update the compact loc-display input for a given context
            function updateLocDisplay(context, name) {
                const displayMap = {
                    'originSelect': 'addOriginDisplay',
                    'destSelect': 'addDestDisplay',
                    'editOriginSelect': 'editOriginDisplay',
                    'editDestSelect': 'editDestDisplay',
                };
                const popoverMap = {
                    'originSelect': 'addOriginPop',
                    'destSelect': 'addDestPop',
                    'editOriginSelect': 'editOriginPop',
                    'editDestSelect': 'editDestPop',
                };
                const dispId = displayMap[context];
                if (dispId) {
                    const el = document.getElementById(dispId);
                    if (el) el.value = name;
                }
                const popId = popoverMap[context];
                if (popId) closeLocPopover(popId);
            }

            function filterSelect(input, selectId) {
                // Deprecated, using filterOptions
            }

            function populateEditSelects() {
                renderDriverOptions('editDriverList', 'editDriverSelect');
            }

            function populateEditLocationSelects(selectedOriginId, selectedDestId, selectedDriverId) {
                renderLocationOptions('editOriginList', 'editOriginSelect', 'edit_origin_name');
                renderLocationOptions('editDestList', 'editDestSelect', 'edit_dest_name', true);
                renderDriverOptions('editDriverList', 'editDriverSelect');

                if (selectedDriverId) {
                    const d = allDrivers.find(drv => drv.user_id == selectedDriverId);
                    if (d) {
                        document.getElementById('editDriverSelect').value = d.user_id;
                        const container = document.querySelector('#editDriverList').parentElement;
                        const display = container.querySelector('.searchable-input');
                        if (display) display.value = d.driver_name;
                    }
                }
                if (selectedOriginId) {
                    const loc = locationData.find(l => l.id == selectedOriginId);
                    if (loc) {
                        document.getElementById('editOriginSelect').value = loc.id;
                        document.getElementById('edit_origin_name').value = loc.name;
                        const container = document.querySelector('#editOriginList').parentElement;
                        const display = container.querySelector('.searchable-input');
                        if (display) display.value = loc.name;
                        const locDisp = document.getElementById('editOriginDisplay');
                        if (locDisp) locDisp.value = loc.name;
                    }
                }
                if (selectedDestId) {
                    const loc = locationData.find(l => l.id == selectedDestId);
                    if (loc) {
                        document.getElementById('editDestSelect').value = loc.id;
                        document.getElementById('edit_dest_name').value = loc.name;
                        const container = document.querySelector('#editDestList').parentElement;
                        const display = container.querySelector('.searchable-input');
                        if (display) display.value = loc.name;
                        const locDisp = document.getElementById('editDestDisplay');
                        if (locDisp) locDisp.value = loc.name;
                    }
                }
            }

            // ===== HELPERS FOR HIDDEN FIELDS =====
            function updateOriginName(sel) {
                const loc = locationData.find(l => l.id == sel.value);
                document.getElementById('origin_name').value = loc ? loc.name : '';
            }
            function updateDestDetails(sel) {
                const loc = locationData.find(l => l.id == sel.value);
                if (loc) {
                    document.getElementById('dest_name').value = loc.name;
                    document.getElementById('dest_lat').value = loc.lat;
                    document.getElementById('dest_lng').value = loc.lng;
                }
            }
            function updateEditOriginName(sel) {
                const loc = locationData.find(l => l.id == sel.value);
                document.getElementById('edit_origin_name').value = loc ? loc.name : '';
            }

            const ADMIN_NAME = '<?php echo $_SESSION['name'] ?? "Admin"; ?>';

            // Global storage for incremental file selection
            let selectedFiles = {
                'addPreview': [],
                'addGoodsPreview': []
            };

            let editSelectedFiles = {
                'editSjPrev': [],
                'editGoodsPrev': [],
                'editProofPrev': []
            };

            async function watermarkFile(file, data) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
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

                            const overlayH = h * 0.18;
                            const gradient = ctx.createLinearGradient(0, h - overlayH, 0, h);
                            gradient.addColorStop(0, 'rgba(0,0,0,0)');
                            gradient.addColorStop(0.3, 'rgba(0,0,0,0.6)');
                            gradient.addColorStop(1, 'rgba(0,0,0,0.8)');
                            ctx.fillStyle = gradient;
                            ctx.fillRect(0, h - overlayH, w, overlayH);

                            const fontSize = Math.max(14, Math.round(w * 0.025));
                            ctx.fillStyle = 'white';
                            ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                            ctx.shadowColor = 'rgba(0,0,0,0.6)';
                            ctx.shadowBlur = 4;
                            ctx.shadowOffsetX = 2;
                            ctx.shadowOffsetY = 2;

                            const now = new Date();
                            const dateStr = now.toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' });
                            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                            const lines = [
                                `📦 SJ: ${data.surat_jalan ? data.surat_jalan.trim() : '-'}`,
                                `🏁 DARI: ${data.origin ? data.origin.trim() : '-'}`,
                                `🏁 TUJUAN: ${data.destination ? data.destination.trim() : '-'}`,
                                `👤 ADMIN: ${data.admin ? data.admin.trim() : '-'}`,
                                `📅 ${dateStr} | 🕒 ${timeStr}`
                            ];

                            let padding = fontSize * 1.2;
                            lines.reverse().forEach((line, i) => {
                                ctx.fillText(line, padding, h - (padding + (i * fontSize * 1.4)));
                            });

                            canvas.toBlob((blob) => {
                                resolve(blob);
                            }, 'image/webp', 0.82);
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            }

            async function handleImagePreview(input, containerId) {
                if (!input.files || input.files.length === 0) return;

                const files = Array.from(input.files);

                for (const file of files) {
                    // Store raw original file
                    selectedFiles[containerId].push(file);
                }

                input.value = '';
                renderPreviews(containerId);
            }

            function renderPreviews(containerId) {
                const container = document.getElementById(containerId);
                container.innerHTML = '';

                selectedFiles[containerId].forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="preview-remove" onclick="removeImage('${containerId}', ${index})">
                            <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                        </button>
                    `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function removeImage(containerId, index) {
                selectedFiles[containerId].splice(index, 1);
                renderPreviews(containerId);
            }

            function syncFilesToInput(containerId, inputId) {
                const dt = new DataTransfer();
                selectedFiles[containerId].forEach(file => dt.items.add(file));
                document.getElementById(inputId).files = dt.files;
            }

            function handleEditFileChange(input, containerId) {
                if (!input.files || input.files.length === 0) return;
                const files = Array.from(input.files);
                for (const file of files) {
                    editSelectedFiles[containerId].push(file);
                }
                input.value = '';
                const type = containerId === 'editSjPrev' ? 'sj' : (containerId === 'editGoodsPrev' ? 'goods' : 'proof');
                renderEditContainer(type);
            }

            function renderEditContainer(type) {
                const containerId = type === 'sj' ? 'editSjPrev' : (type === 'goods' ? 'editGoodsPrev' : 'editProofPrev');
                const hiddenId = type === 'sj' ? 'editSjExisting' : (type === 'goods' ? 'editGoodsExisting' : 'editProofExisting');
                const container = document.getElementById(containerId);
                const hidden = document.getElementById(hiddenId);

                container.innerHTML = '';

                // 1. Render existing files from hidden input
                let existingFiles = [];
                if (hidden.value) {
                    try {
                        const parsed = JSON.parse(hidden.value);
                        existingFiles = Array.isArray(parsed) ? parsed : [parsed];
                    } catch (e) {
                        existingFiles = [hidden.value];
                    }
                }

                existingFiles.forEach(f => {
                    if (f) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                        <img src="uploads/${f}" onclick="window.showImagePopup('uploads/${f}')" style="cursor:pointer;" title="Klik untuk memperbesar">
                        <button type="button" class="preview-remove" onclick="deleteExistingFile('${f}', '${type}', this)" style="background:#ef4444;" title="Hapus foto ini">
                            <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                        </button>
                    `;
                        container.appendChild(div);
                    }
                });

                // 2. Render newly selected files
                editSelectedFiles[containerId].forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="preview-remove" onclick="removeEditImage('${containerId}', ${index})">
                            <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                        </button>
                    `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function removeEditImage(containerId, index) {
                editSelectedFiles[containerId].splice(index, 1);
                const type = containerId === 'editSjPrev' ? 'sj' : (containerId === 'editGoodsPrev' ? 'goods' : 'proof');
                renderEditContainer(type);
            }

            function deleteExistingFile(filename, type, btn) {
                showConfirmToast('Hapus foto ini dari tugas?', () => {
                    const hiddenId = type === 'sj' ? 'editSjExisting' : (type === 'goods' ? 'editGoodsExisting' : 'editProofExisting');
                    const hidden = document.getElementById(hiddenId);
                    let files = [];
                    if (hidden.value) {
                        try {
                            const parsed = JSON.parse(hidden.value);
                            files = Array.isArray(parsed) ? parsed : [parsed];
                        } catch (e) {
                            files = [hidden.value];
                        }
                    }
                    files = files.filter(f => f !== filename);
                    hidden.value = files.length > 0 ? JSON.stringify(files) : '';

                    // Re-render
                    renderEditContainer(type);
                }, 'Ya, Hapus');
            }

            function updateEditDestDetails(sel) {
                const loc = locationData.find(l => l.id == sel.value);
                if (loc) {
                    document.getElementById('edit_dest_name').value = loc.name;
                    document.getElementById('edit_dest_lat').value = loc.lat;
                    document.getElementById('edit_dest_lng').value = loc.lng;
                }
            }

            // ===== LOAD COMBINED HISTORY TABLE =====
            async function loadHistory() {
                const tbody = document.querySelector('#historyTable tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="13" style="text-align:center; padding:3rem;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                        <span class="material-symbols-outlined rotating" style="font-size:2.5rem; color:var(--primary);">autorenew</span>
                        <div style="font-weight:600; color:var(--text-sub);">Memuat data penugasan...</div>
                    </div>
                </td></tr>`;
                }

                const dateF = document.getElementById('date_from').value;
                const dateT = document.getElementById('date_to').value;
                const driverId = document.getElementById('driverFilter').value;
                const status = document.getElementById('statusFilter').value;
                const taskType = document.getElementById('typeTaskFilter')?.value || '';
                const search = document.getElementById('searchInput').value;

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout

                    // Prepare fetch promises
                    const deliveriesPromise = fetch(`${API_URL}?action=get_deliveries&date_from=${dateF}&date_to=${dateT}&driver_id=${driverId}&status=${status}&task_type=${taskType}&search=${search}`, { signal: controller.signal })
                        .then(r => r.json());

                    let pickupPromise = Promise.resolve([]);
                    if (!driverId && (!status || status === 'pending') && (!taskType || taskType === 'jemput')) {
                        pickupPromise = fetch(`${API_URL}?action=get_pickup_requests&status=pending`, { signal: controller.signal })
                            .then(r => r.json());
                    }

                    const [deliveries, pData] = await Promise.all([deliveriesPromise, pickupPromise]);
                    clearTimeout(timeoutId);

                    if (deliveries.error) throw new Error(deliveries.error);
                    if (!Array.isArray(deliveries)) throw new Error('Data pengiriman bermasalah');

                    let pendingPickups = [];
                    if (Array.isArray(pData)) {
                        // Only show pickup requests that HAVEN'T been assigned to any driver yet
                        pendingPickups = pData
                            .filter(p => !p.assigned_drivers)
                            .map(p => ({ ...p, is_request: true, status: 'request' }));
                    }

                    // Merge and sort
                    const combined = [...pendingPickups, ...deliveries];
                    historyData = combined;
                    applySorting();

                    currentPage = 1;
                    renderHistoryTable();
                } catch (err) {
                    console.error(err);
                    const tbody = document.querySelector('#historyTable tbody');
                    if (tbody) tbody.innerHTML = `<tr><td colspan="13" style="text-align:center; padding:3rem; color:var(--danger);">${err.message}</td></tr>`;
                }
            }

            let assignSearchTimer = null;

            function applyAssignSmartSearchFilter(data) {
                const q = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
                const taskType = document.getElementById('typeTaskFilter')?.value || '';
                const clearBtn = document.getElementById('clearAssignSearch');
                if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

                let filtered = data;
                if (taskType) {
                    filtered = filtered.filter(item => {
                        if (item.is_request) {
                            return taskType === 'jemput';
                        }
                        return item.task_type === taskType;
                    });
                }

                if (!q) return filtered;

                const tokens = q.split(/\s+/).filter(Boolean);

                return filtered.filter(item => {
                    const typeText = item.task_type === 'antar' ? 'antar delivery' : 'jemput pickup';
                    const statusText = (item.status === 'completed' ? 'selesai completed' : (item.status === 'in_transit' ? 'dalam perjalanan transit' : (item.status === 'canceled' ? 'cancel batal' : 'pending')));

                    const searchableString = [
                        item.driver_name,
                        item.vehicle_plate,
                        item.receiver_name,
                        item.origin_name,
                        item.destination_name,
                        item.notes,
                        item.driver_notes,
                        item.late_reason,
                        item.creator_name,
                        item.assigned_by,
                        item.speedometer_start_num,
                        item.speedometer_end_num,
                        item.target_date,
                        item.created_at,
                        typeText,
                        statusText
                    ].filter(Boolean).join(' ').toLowerCase();

                    return tokens.every(token => searchableString.includes(token));
                });
            }

            function onAssignSmartSearchInput() {
                currentPage = 1;
                renderHistoryTable();
                clearTimeout(assignSearchTimer);
                assignSearchTimer = setTimeout(() => {
                    loadHistory();
                }, 400);
            }

            function clearAssignSearch() {
                const el = document.getElementById('searchInput');
                if (el) el.value = '';
                onAssignSmartSearchInput();
            }

            function renderHistoryTable() {
                try {
                    updateSortIndicators();
                    const combined = applyAssignSmartSearchFilter(historyData);
                    const tbody = document.querySelector('#historyTable tbody');
                    const exportBtn = document.getElementById('exportBtn');
                    const countBadge = document.getElementById('rowCountBadge');
                    const countNum = document.getElementById('rowCountNum');

                    const total = combined.length;
                    const start = (currentPage - 1) * rowsPerPage;
                    const end = start + rowsPerPage;
                    const pagedData = combined.slice(start, end);

                    // Update Pagination Buttons
                    document.getElementById('btnPrev').disabled = currentPage <= 1;
                    document.getElementById('btnNext').disabled = end >= total;

                    if (!pagedData.length && combined.length > 0 && currentPage > 1) {
                        currentPage = 1;
                        renderHistoryTable();
                        return;
                    }

                    if (!combined.length) {
                        tbody.innerHTML = `<tr><td colspan="13">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">search_off</span>
                        <p>Belum ada data penugasan untuk filter ini.</p>
                    </div>
                </td></tr>`;
                        countBadge.style.display = 'none';
                        document.querySelector('.pagination-bar').style.display = 'none';
                        return;
                    }

                    document.querySelector('.pagination-bar').style.display = 'flex';
                    countNum.textContent = combined.length;
                    countBadge.style.display = 'inline-flex';
                    exportBtn.style.display = 'inline-flex';

                    tbody.innerHTML = pagedData.map(item => {

                        const displayTime = item.request_created_at || item.created_at;
                        const dt = new Date(displayTime);
                        const dateStr = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                        const timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                        if (item.is_request) {
                            const req = item;
                            return `
                            <tr class="request-row">
                                <td>${CAN_ASSIGN ? `<input type="checkbox" class="req-checkbox" value="${req.id}">` : ''}</td>
                                <td style="color:var(--text-sub); font-size:0.8rem;">-</td>
                                <td>
                                    <div style="font-weight:700; font-size:0.85rem;">${dateStr}</div>
                                    <div style="font-size:0.7rem; color:var(--text-sub); margin-top:4px;">
                                        Start: - <br> End: -
                                    </div>
                                    <div style="font-weight:700; color:#ef4444; font-size:0.75rem; margin-top:4px;">BELUM ASSIGN</div>
                                </td>
                                <td>
                                    <div style="font-size:0.75rem; color:var(--text-sub);">${formatLocationDisplay(req.origin_name)}</div>
                                    <div style="font-weight:700; color:var(--text); font-size:0.85rem;">&raquo; ${formatLocationDisplay(req.destination_name)}</div>
                                </td>
                                <td style="font-size:0.8rem;">
                                    <strong>${req.surat_jalan || '-'}</strong><br>
                                    <span style="color:var(--text-muted);">${req.total_koli || 0} Koli</span>
                                </td>
                                <td style="font-size:0.8rem;">${req.receiver_name || '-'}</td>
                                <td><span class="badge badge-pending">REQ PICKUP</span></td>
                                <td><span class="badge badge-pending">BUTUH PROSES</span></td>
                                <td style="font-size:0.75rem; color:var(--text-muted);">-</td>
                                <td style="text-align:right;">
                                    <div style="display:flex; justify-content:flex-end; gap:5px;">
                                        ${CAN_ASSIGN ? `
                                        <button class="btn-icon" onclick="openAssignModal(${req.id})" title="Assign Driver">
                                            <span class="material-symbols-outlined">person_add</span>
                                        </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>`;
                        } else {
                            const del = item;
                            const cls = del.status === 'pending' ? 'badge-pending' : (del.status === 'in_transit' ? 'badge-transit' : (del.status === 'canceled' ? 'badge-danger' : 'badge-completed'));
                            const typeBadge = del.task_type === 'antar' ? 'badge-antar' : 'badge-kirim';

                            // Inline Edit Logic
                            const isEditable = del.status !== 'completed' && CAN_EDIT;
                            const editableClass = isEditable ? 'editable-cell' : 'disabled';
                            const editIcon = isEditable ? '<div class="inline-edit-btn"><span class="material-symbols-outlined" style="font-size:18px;">edit</span></div>' : '';
                            const editClick = isEditable ? `onclick="openEdit(${del.id})"` : '';

                            let requestDateStr = '-';
                            if (del.request_created_at) {
                                const rd = new Date(del.request_created_at);
                                requestDateStr = rd.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                            }

                            let assignDateStr = '-';
                            if (del.created_at) {
                                const ad = new Date(del.created_at);
                                assignDateStr = ad.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                            }

                            let targetDateStr = '-';
                            if (del.target_date) {
                                // Replace '-' with '/' for broad cross-browser compatibility (e.g. iOS Safari)
                                const normalizedDate = del.target_date.replace(/-/g, '/');
                                const td = new Date(normalizedDate);
                                if (!isNaN(td.getTime())) {
                                    targetDateStr = td.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                                } else {
                                    targetDateStr = assignDateStr;
                                }
                            } else {
                                targetDateStr = assignDateStr;
                            }

                            const mapsBtn = del.destination_lat ? `<a href="https://www.google.com/maps?q=${del.destination_lat},${del.destination_lng}" target="_blank" class="maps-link"><span class="material-symbols-outlined">map</span> Maps</a>` : '';

                            return `
                            <tr>
                                <td></td>
                                <td style="font-size:0.75rem; line-height: 1.4;">
                                    <div style="font-weight:700; color:var(--text);">${assignDateStr}</div>
                                    <div style="color:var(--text-sub);">${del.created_at ? new Date(del.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'}</div>
                                    <div style="font-weight:600; color:var(--text); margin-top:2px;">
                                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; color:var(--primary);">person_edit</span>
                                        ${del.creator_name || 'Admin'}
                                    </div>
                                </td>
                                <td class="${editableClass}" ${isEditable ? `onclick="openQuickEdit(${del.id}, 'driver')"` : ''}>
                                    ${editIcon}
                                    <div style="font-weight:700; font-size:0.85rem;">${targetDateStr}</div>
                                    <div style="font-weight:800; color:var(--text); font-size:0.8rem; margin-top:4px;">${del.driver_name}</div>
                                </td>
                                <td style="font-size:0.75rem; line-height:1.35; white-space:nowrap;">
                                    <span class="badge ${del.task_type === 'antar' ? 'badge-completed' : 'badge-transit'}" style="font-size:0.65rem; margin-bottom:4px; display:inline-block;">
                                        ${del.task_type === 'antar' ? 'ANTAR' : 'JEMPUT'}
                                    </span>
                                    <div style="font-size:0.7rem; color:var(--text-sub);">
                                        Start: ${del.start_time ? new Date(del.start_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'} <br>
                                        End: ${del.end_time ? new Date(del.end_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'}
                                    </div>
                                    ${(del.start_time && del.end_time) || del.duration ? `<div style="font-size:0.7rem; font-weight:700; color:var(--primary); margin-top:2px;">Durasi: ${formatDurationTime(del.start_time, del.end_time, del.duration)}</div>` : ''}
                                </td>
                                <td class="${editableClass}" ${isEditable ? `onclick="openQuickEdit(${del.id}, 'route')"` : ''}>
                                    ${editIcon}
                                    <div style="font-size:0.75rem; color:var(--text-sub);">${formatLocationDisplay(del.origin_name)}</div>
                                    <div style="font-weight:700; color:var(--text); font-size:0.85rem;">&raquo; ${formatLocationDisplay(del.destination_name, del.destination_lat, del.destination_lng)}</div>
                                    <div style="font-size:0.72rem; font-weight:700; color:var(--primary); margin-top:3px; display:flex; align-items:center; gap:3px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">person</span> ${del.receiver_name ? del.receiver_name : '<span style="color:var(--text-muted); font-weight:normal;">-</span>'}
                                    </div>
                                </td>
                                <td style="font-size:0.75rem; line-height:1.4;">
                                     <div><strong>Awal:</strong> ${del.speedometer_start_num ? Number(del.speedometer_start_num).toLocaleString('id-ID') + ' KM' : '-'}</div>
                                     <div><strong>Akhir:</strong> ${del.speedometer_end_num ? Number(del.speedometer_end_num).toLocaleString('id-ID') + ' KM' : '-'}</div>
                                     ${(del.speedometer_start_num && del.speedometer_end_num) ? `<div style="font-weight:700; color:var(--primary); margin-top:2px;">Jarak: ${Number(del.speedometer_end_num - del.speedometer_start_num).toLocaleString('id-ID')} KM</div>` : ''}
                                 </td>               
                                <td>
                                    <span class="badge ${cls}">${(del.status || 'pending').replace('_', ' ').toUpperCase()}</span>
                                </td>
                                <td style="font-size:0.75rem; color:var(--text-sub);">
                                    <div style="word-wrap: break-word; min-width: 120px; display:flex; flex-direction:column; gap:3px;">
                                        ${del.notes ? `<div style="color:var(--text); font-weight:600;"><span style="color:#6366f1; font-weight:700;">Admin:</span> "${del.notes}"</div>` : ''}
                                        ${del.driver_notes ? `<div style="color:var(--success); font-weight:600;"><span style="color:#10b981; font-weight:700;">Driver:</span> "${del.driver_notes}"</div>` : ''}
                                        ${del.late_reason ? `<div style="color:var(--danger); font-weight:600;"><span style="color:#ef4444; font-weight:700;">Kendala:</span> "${del.late_reason}"</div>` : ''}
                                        ${(!del.notes && !del.driver_notes && !del.late_reason) ? '<span style="color:var(--text-muted);">-</span>' : ''}
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; justify-content:flex-end; gap:5px;">
                                        <button class="btn-icon" onclick="openDetail(${del.id})" title="Detail"><span class="material-symbols-outlined">visibility</span></button>
                                        ${(CAN_EDIT && del.status !== 'completed') ? `
                                        <button class="btn-icon" onclick="openEdit(${del.id})" title="Edit"><span class="material-symbols-outlined">edit</span></button>
                                        ` : ''}
                                        ${(CAN_EDIT && del.status !== 'completed' && del.status !== 'canceled') ? `
                                        <button class="btn-icon danger" style="color:var(--danger);" onclick="cancelDeliveryTask(${del.id})" title="Batalkan Task"><span class="material-symbols-outlined">cancel</span></button>
                                        ` : ''}
                                        ${(del.status === 'canceled' && CAN_ASSIGN && !parseInt(del.has_reassigned_task)) ? `
                                        <button class="btn-icon" style="color:var(--primary);" onclick="openQuickEdit(${del.id}, 'reassign')" title="Reassign Driver">
                                            <span class="material-symbols-outlined">person_add</span>
                                        </button>
                                        ` : ''}
                                        ${(CAN_DELETE || CAN_WRITE) ? `
                                        <button class="btn-icon danger" style="color:var(--danger);" onclick="deleteDelivery(${del.id})" title="Hapus Task"><span class="material-symbols-outlined">delete</span></button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>`;
                        }
                    }).join('');
                } catch (err) {
                    console.error('History Load Error:', err);
                    const tbody = document.querySelector('#historyTable tbody');
                    if (tbody) {
                        let errMsg = err.message;
                        if (err.name === 'AbortError') errMsg = 'Koneksi lambat (Request Timeout). Silakan Refresh.';

                        tbody.innerHTML = `<tr><td colspan="13">
                        <div class="empty-state" style="color: #ef4444; padding: 3rem;">
                            <span class="material-symbols-outlined" style="font-size:3rem;">error</span>
                            <p style="font-weight:600; margin-top:1rem;">Gagal Memuat Data</p>
                            <p style="font-size:0.85rem; opacity:0.8;">${errMsg}</p>
                            <button onclick="loadHistory()" class="btn btn-primary btn-sm" style="margin-top:1.5rem;">
                                <span class="material-symbols-outlined">refresh</span> Coba Lagi
                            </button>
                        </div>
                    </td></tr>`;
                    }
                }
            }

            // ===== SORTING FUNCTIONS =====
            function applySorting() {
                const isAsc = sortDirection === 'asc' ? 1 : -1;

                historyData.sort((a, b) => {
                    let valA, valB;

                    switch (sortColumn) {
                        case 'assign_by':
                            valA = (a.creator_name || 'Admin').toLowerCase();
                            valB = (b.creator_name || 'Admin').toLowerCase();
                            break;
                        case 'date':
                            valA = new Date(a.target_date || a.request_created_at || a.created_at || 0).getTime();
                            valB = new Date(b.target_date || b.request_created_at || b.created_at || 0).getTime();
                            break;
                        case 'route':
                            valA = ((a.origin_name || '') + ' ' + (a.destination_name || '')).toLowerCase();
                            valB = ((b.origin_name || '') + ' ' + (b.destination_name || '')).toLowerCase();
                            break;
                        case 'sj':
                            valA = (a.surat_jalan || '').toLowerCase();
                            valB = (b.surat_jalan || '').toLowerCase();
                            break;
                        case 'receiver':
                            valA = (a.receiver_name || '').toLowerCase();
                            valB = (b.receiver_name || '').toLowerCase();
                            break;
                        case 'type':
                            const typeA = a.is_request ? 'req pickup' : (a.task_type || '');
                            const typeB = b.is_request ? 'req pickup' : (b.task_type || '');
                            valA = typeA.toLowerCase();
                            valB = typeB.toLowerCase();
                            break;
                        case 'status':
                            valA = (a.status || '').toLowerCase();
                            valB = (b.status || '').toLowerCase();
                            break;
                        case 'notes':
                            valA = (a.driver_notes || a.late_reason || '').toLowerCase();
                            valB = (b.driver_notes || b.late_reason || '').toLowerCase();
                            break;
                        default:
                            valA = 0;
                            valB = 0;
                    }

                    if (valA < valB) return -1 * isAsc;
                    if (valA > valB) return 1 * isAsc;

                    // Fallback secondary sort by creation date (newest first)
                    const dateA = new Date(a.request_created_at || a.created_at || 0).getTime();
                    const dateB = new Date(b.request_created_at || b.created_at || 0).getTime();
                    return dateB - dateA;
                });
            }

            function handleSort(column) {
                if (sortColumn === column) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = column;
                    sortDirection = (column === 'date') ? 'desc' : 'asc';
                }
                applySorting();
                currentPage = 1;
                renderHistoryTable();
            }

            function updateSortIndicators() {
                const columns = ['assign_by', 'date', 'route', 'sj', 'receiver', 'type', 'status', 'notes'];
                columns.forEach(col => {
                    const indicator = document.getElementById(`sort-${col}`);
                    if (!indicator) return;
                    const th = indicator.closest('th');
                    if (sortColumn === col) {
                        indicator.innerText = sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward';
                        if (th) th.classList.add('active-sort');
                    } else {
                        indicator.innerText = 'swap_vert';
                        if (th) th.classList.remove('active-sort');
                    }
                });
            }

            // ===== SUBMIT ADD TASK =====
            const taskFormEl = document.getElementById('taskForm');
            if (taskFormEl) {
                taskFormEl.onsubmit = async (e) => {
                    e.preventDefault();
                    const msg = document.getElementById('formMsg');

                    // Validation for custom searchable selects (predefined location or Google Maps Link)
                    const originId = document.getElementById('originSelect')?.value || '';
                    const originName = document.getElementById('origin_name')?.value || '';
                    const destId = document.getElementById('destSelect')?.value || '';
                    const destName = document.getElementById('dest_name')?.value || '';

                    if ((!originId && !originName) || (!destId && !destName)) {
                        if (msg) msg.innerHTML = '<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">warning</span> Silakan pilih lokasi asal & tujuan, atau isi link Google Maps.</span>';
                        return;
                    }

                    if (msg) msg.innerHTML = '<span style="color:var(--text-muted);"><span class="material-symbols-outlined rotating" style="font-size:18px; vertical-align:middle;">autorenew</span> Mengunggah data...</span>';
                    try {
                        const res = await fetch(`${API_URL}?action=assign_task`, { method: 'POST', body: new FormData(e.target) });
                        const data = await res.json();
                        if (data.success) {
                            if (msg) msg.innerHTML = '<span class="success-msg"><span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Tugas berhasil diberikan!</span>';
                            loadHistory();
                            setTimeout(() => {
                                closeAdd();
                                if (msg) msg.innerHTML = '';
                            }, 1500);
                        } else {
                            if (msg) msg.innerHTML = `<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">error</span> Gagal memberikan tugas: ${data.error || 'Terjadi kesalahan'}</span>`;
                        }
                    } catch (err) {
                        console.error(err);
                        if (msg) msg.innerHTML = '<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">wifi_off</span> Error koneksi.</span>';
                    }
                };
            }

            // ===== SUBMIT EDIT TASK =====
            const editFormEl = document.getElementById('editForm');
            if (editFormEl) {
                editFormEl.onsubmit = async (e) => {
                    e.preventDefault();
                    const msg = document.getElementById('editFormMsg');

                    const editOriginId = document.getElementById('editOriginSelect')?.value || '';
                    const editOriginName = document.getElementById('edit_origin_name')?.value || '';
                    const editDestId = document.getElementById('editDestSelect')?.value || '';
                    const editDestName = document.getElementById('edit_dest_name')?.value || '';

                    if ((!editOriginId && !editOriginName) || (!editDestId && !editDestName)) {
                        msg.innerHTML = '<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">warning</span> Silakan pilih lokasi asal & tujuan, atau isi link Google Maps.</span>';
                        return;
                    }

                    msg.innerHTML = '<span style="color:var(--text-muted);"><span class="material-symbols-outlined rotating" style="font-size:18px; vertical-align:middle;">autorenew</span> Menyimpan perubahan...</span>';

                    try {
                        const res = await fetch(`${API_URL}?action=edit_delivery`, { method: 'POST', body: new FormData(e.target) });
                        const data = await res.json();
                        if (data.success) {
                            msg.innerHTML = '<span class="success-msg"><span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Perubahan disimpan!</span>';
                            loadHistory();
                            setTimeout(() => {
                                closeEdit();
                                msg.innerHTML = '';
                            }, 1200);
                        } else {
                            msg.innerHTML = `<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">error</span> Gagal: ${data.error || 'Terjadi kesalahan'}</span>`;
                        }
                    } catch (err) {
                        console.error('Edit submit error:', err);
                        msg.innerHTML = '<span class="error-msg"><span class="material-symbols-outlined" style="font-size:18px;">wifi_off</span> Error koneksi.</span>';
                    }
                };
            }

            // ===== CANCEL TASK =====
            async function cancelDeliveryTask(id) {
                showPromptToast('Apakah Anda yakin ingin membatalkan (Cancel) penugasan ini?', 'Dibatalkan oleh Admin', 'Alasan pembatalan...', async (reason) => {
                    try {
                        const fd = new FormData();
                        fd.append('id', id);
                        fd.append('reason', reason || 'Dibatalkan oleh Admin');
                        const res = await fetch(`${API_URL}?action=cancel_delivery`, { method: 'POST', body: fd });
                        const data = await res.json();
                        if (data.success) {
                            showToast('Penugasan berhasil dibatalkan.', 'success');
                            loadHistory();
                        } else {
                            showToast(data.error || 'Gagal membatalkan penugasan.', 'error');
                        }
                    } catch (err) {
                        showToast('Terjadi kesalahan jaringan.', 'error');
                    }
                }, 'Ya, Batalkan');
            }

            // ===== DELETE =====
            async function deleteDelivery(id) {
                showConfirmToast('Hapus penugasan ini? Tindakan ini tidak bisa dibatalkan.', async () => {
                    const res = await fetch(`${API_URL}?action=delete_delivery`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + id
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Penugasan berhasil dihapus.', 'success');
                        loadHistory();
                    } else {
                        showToast(data.error || 'Gagal menghapus penugasan.', 'error');
                    }
                }, 'Ya, Hapus');
            }

            function fmtDatetime(d) {
                if (!d) return '-';
                const dt = new Date(d);
                if (isNaN(dt)) return '-';
                return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                    dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
            }

            function formatDurationTime(startStr, endStr, durationStr) {
                if (startStr && endStr) {
                    const s = new Date(startStr);
                    const e = new Date(endStr);
                    if (!isNaN(s) && !isNaN(e)) {
                        const diffMs = Math.max(0, e - s);
                        const totalSec = Math.floor(diffMs / 1000);
                        const hours = String(Math.floor(totalSec / 3600)).padStart(2, '0');
                        const minutes = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
                        const seconds = String(totalSec % 60).padStart(2, '0');
                        return `${hours}:${minutes}:${seconds}`;
                    }
                }
                if (durationStr) {
                    const hMatch = durationStr.match(/(\d+)\s*Jam/i);
                    const mMatch = durationStr.match(/(\d+)\s*Menit/i);
                    const sMatch = durationStr.match(/(\d+)\s*Detik/i);
                    if (hMatch || mMatch || sMatch) {
                        const h = hMatch ? String(hMatch[1]).padStart(2, '0') : '00';
                        const m = mMatch ? String(mMatch[1]).padStart(2, '0') : '00';
                        const s = sMatch ? String(sMatch[1]).padStart(2, '0') : '00';
                        return `${h}:${m}:${s}`;
                    }
                    return durationStr;
                }
                return '-';
            }

            // ===== EXPORT TO EXCEL =====
            function exportToExcel() {
                if (!historyData.length) { showToast('Tidak ada data untuk diekspor.', 'warning'); return; }

                const dateF = document.getElementById('date_from').value || 'all';
                const dateT = document.getElementById('date_to').value || 'all';

                const rows = historyData.map((del, i) => {
                    const dt = del.created_at ? new Date(del.created_at) : null;
                    const dateStr = dt ? dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                    const timeStr = dt ? dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '';

                    let targetDateStr = '-';
                    if (del.target_date) {
                        const [y, m, d] = del.target_date.split(' ')[0].split('-');
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        targetDateStr = `${d} ${months[parseInt(m) - 1]} ${y}`;
                    }

                    let sts = del.status === 'pending' ? 'Pending' : (del.status === 'in_transit' ? 'In Transit' : (del.status === 'canceled' ? 'Cancel' : 'Selesai'));
                    let tp = del.task_type === 'antar' ? 'Antar' : 'Jemput';

                    const startTimeStr = del.start_time ? fmtDatetime(del.start_time) : '-';
                    const endTimeStr = del.end_time ? fmtDatetime(del.end_time) : '-';

                    const kmStart = del.speedometer_start_num ? Number(del.speedometer_start_num) : '-';
                    const kmEnd = del.speedometer_end_num ? Number(del.speedometer_end_num) : '-';
                    const jarak = (del.speedometer_start_num && del.speedometer_end_num) ? Number(del.speedometer_end_num - del.speedometer_start_num) : '-';

                    function makeExcelHyperlink(fileProp) {
                        if (!fileProp) return '-';
                        let files = [];
                        try {
                            if (typeof fileProp === 'string' && fileProp.startsWith('[')) {
                                files = JSON.parse(fileProp);
                            } else if (Array.isArray(fileProp)) {
                                files = fileProp;
                            } else {
                                files = [fileProp];
                            }
                        } catch (e) {
                            files = [fileProp];
                        }
                        const origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
                        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                        const validFiles = files.filter(f => f && f !== '-');
                        if (validFiles.length === 0) return '-';

                        const url = (validFiles[0].startsWith('http://') || validFiles[0].startsWith('https://')) ? validFiles[0] : (origin + basePath + '/uploads/' + validFiles[0]);
                        return { f: `HYPERLINK("${url}", "${url}")`, v: url };
                    }

                    return {
                        'No': i + 1,
                        'Created By': del.creator_name || 'Admin',
                        'Waktu Dibuat': `${dateStr} ${timeStr}`,
                        'Tanggal Jadwal': targetDateStr,
                        'Driver': del.driver_name || '-',
                        'Nama / Plat Kendaraan': del.vehicle_name ? `${del.vehicle_name} (${del.vehicle_plate || '-'})` : (del.vehicle_plate || '-'),
                        'Jenis Layanan': tp,
                        'Nama Penumpang': del.receiver_name || '-',
                        'Lokasi Asal': del.origin_name || '-',
                        'Lokasi Tujuan': del.destination_name || '-',
                        'KM Start (Awal)': kmStart,
                        'KM Selesai (Akhir)': kmEnd,
                        'Jarak (KM)': jarak,
                        'Jam Start (Berangkat)': startTimeStr,
                        'Jam Selesai': endTimeStr,
                        'Durasi Perjalanan': formatDurationTime(del.start_time, del.end_time, del.duration),
                        'Status': sts,
                        'Catatan Admin': del.notes || '-',
                        'Catatan Driver': del.driver_notes || del.late_reason || '-',
                        'Link Foto Spidometer Awal': makeExcelHyperlink(del.speedometer_start_photo),
                        'Link Foto Spidometer Akhir': makeExcelHyperlink(del.speedometer_end_photo),
                        'Link Foto Bukti Selesai': makeExcelHyperlink(del.delivery_proofs || del.proof_file || del.proof_files_raw || del.proof_files)
                    };
                });

                const ws = XLSX.utils.json_to_sheet(rows);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Penugasan Driver');
                XLSX.writeFile(wb, `Penugasan_Driver_${dateF}_sd_${dateT}.xlsx`);
            }

            // INIT Default Date
            (function setDefaultDates() {
                const df = document.getElementById('date_from');
                const dt = document.getElementById('date_to');
                if (df && !df.value) df.value = todayStr();
                if (dt && !dt.value) dt.value = todayStr();
            })();

            function showDriverSelect(id) {
                document.getElementById(`assign-container-${id}`).style.display = 'none';
                document.getElementById(`driver-select-container-${id}`).style.display = 'block';
            }

            function cancelQuickAssign(id) {
                document.getElementById(`assign-container-${id}`).style.display = 'block';
                document.getElementById(`driver-select-container-${id}`).style.display = 'none';
            }

            async function quickAssign(id, driverId) {
                if (!driverId) return;

                const req = historyData.find(x => x.id === id && x.is_request);
                if (!req) return;

                showConfirmToast(`Tugaskan driver untuk Surat Jalan ${req.surat_jalan}?`, async () => {
                    const formData = new FormData();
                    formData.append('driver_id', driverId);
                    formData.append('pickup_id', id);
                    formData.append('origin_name', req.origin_name);
                    formData.append('dest_name', req.destination_name);
                    formData.append('dest_lat', 0); // Default or try to find in locationData
                    formData.append('dest_lng', 0);
                    formData.append('task_type', 'kirim');
                    formData.append('surat_jalan', req.surat_jalan);
                    formData.append('total_koli', req.total_koli);
                    formData.append('notes', req.notes);
                    formData.append('target_date', req.scheduled_date ? req.scheduled_date.replace(' ', 'T') : todayDateTimeStr());

                    // Try to find lat/lng from locationData
                    if (locationData) {
                        const loc = locationData.find(l => l.name === req.destination_name);
                        if (loc) {
                            formData.append('dest_id', loc.id);
                            formData.append('dest_lat', loc.lat);
                            formData.append('dest_lng', loc.lng);
                        }
                    }

                    try {
                        const res = await fetch(`${API_URL}?action=assign_task`, { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) {
                            loadHistory();
                            showToast('Tugas berhasil diperbarui');
                            if (window.updateSidebarBadge) updateSidebarBadge();
                        } else {
                            showToast('Gagal: ' + (data.error || 'Terjadi kesalahan'), 'error');
                        }
                    } catch (err) {
                        showToast('Error koneksi', 'error');
                    }
                }, 'Ya, Tugaskan', () => {
                    const sel = document.querySelector(`#driver-select-container-${id} select`);
                    if (sel) sel.value = '';
                });
            }

            function assignFromRequest(req) {
                openAddModal();
                // Pre-fill fields
                document.getElementById('taskType').value = 'kirim'; // Pickup
                document.getElementById('taskFormSuratJalan').value = req.surat_jalan || '';
                document.getElementById('taskFormKoli').value = req.total_koli || 1;
                document.getElementById('taskFormNotes').value = req.notes || '';
                if (req.scheduled_date) {
                    let val = req.scheduled_date.replace(' ', 'T');
                    if (val.length === 10) val += 'T00:00'; // Append midnight if date only
                    document.getElementById('taskFormTargetDate').value = val.slice(0, 16);
                }

                // Try to match origin and destination in selects
                const originSel = document.getElementById('originSelect');
                const destSel = document.getElementById('destSelect');

                // Match by name if ID is not available in pickup_request (since it stores names)
                for (let i = 0; i < originSel.options.length; i++) {
                    if (originSel.options[i].getAttribute('data-name') === req.origin_name) {
                        originSel.selectedIndex = i;
                        updateOriginName(originSel);
                        break;
                    }
                }

                for (let i = 0; i < destSel.options.length; i++) {
                    if (destSel.options[i].getAttribute('data-name') === req.destination_name) {
                        destSel.selectedIndex = i;
                        updateDestDetails(destSel);
                        break;
                    }
                }

                // Store reference to pickup request ID so we can update its status after assigning
                const form = document.getElementById('taskForm');
                let hiddenReqId = document.getElementById('link_pickup_id');
                if (!hiddenReqId) {
                    hiddenReqId = document.createElement('input');
                    hiddenReqId.type = 'hidden';
                    hiddenReqId.name = 'pickup_id';
                    hiddenReqId.id = 'link_pickup_id';
                    form.appendChild(hiddenReqId);
                }
                hiddenReqId.value = req.id;
            }

            // ===== QUICK EDIT LOGIC =====
            async function openQuickEdit(id, field) {
                const modal = document.getElementById('quickEditModal');
                const content = document.getElementById('qe_content');
                const title = document.getElementById('quickEditTitle');
                const icon = document.getElementById('qeIcon');
                const qeId = document.getElementById('qe_id');
                const qeField = document.getElementById('qe_field');
                const msg = document.getElementById('qeMsg');

                msg.innerHTML = '';
                qeId.value = id;
                qeField.value = field;

                content.innerHTML = '<div style="text-align:center; padding:1rem;"><span class="material-symbols-outlined rotating">autorenew</span> Memuat...</div>';
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);

                try {
                    const res = await fetch(`${API_URL}?action=get_delivery&id=${id}`);
                    const d = await res.json();

                    if (field !== 'reassign' && d.status === 'completed') {
                        showToast('Tugas yang sudah SELESAI (Completed) tidak dapat di-edit.', 'error');
                        closeQuickEdit();
                        return;
                    }

                    if (field === 'driver') {
                        title.innerText = 'Edit Driver & Jadwal';
                        icon.innerText = 'person_edit';
                        content.innerHTML = `
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">Pilih Driver</label>
                            <select name="driver_id" id="qe_driver_select" style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                                ${allDrivers.map(drv => `<option value="${drv.user_id}" ${drv.user_id == d.driver_id ? 'selected' : ''}>${drv.driver_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">Tanggal & Jam</label>
                            <input type="datetime-local" name="target_date" value="${d.target_date ? d.target_date.replace(' ', 'T').slice(0, 16) : todayDateTimeStr()}" style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                        </div>
                    `;
                    } else if (field === 'route') {
                        title.innerText = 'Edit Rute Asal & Tujuan';
                        icon.innerText = 'route';
                        content.innerHTML = `
                        <div class="form-group searchable-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                                <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin:0;">Lokasi Asal</label>
                                <button type="button" class="btn-pick-map" onclick="openMapPicker('qeOrigin')" title="Pilih titik lokasi asal di peta">
                                    <span class="material-symbols-outlined" style="font-size:14px;">map</span> Pilih di Peta
                                </button>
                            </div>
                            <input type="text" class="searchable-input" id="qe_origin_input" value="${d.origin_name || ''}" placeholder="Cari asal..." readonly onclick="toggleOptions('qe_origin_list')">
                            <div id="qe_origin_list" class="options-list"></div>
                            <input type="hidden" name="origin_id" id="qe_origin_id" value="${d.origin_id || ''}">
                            <input type="hidden" name="origin_name" id="qe_origin_name" value="${d.origin_name || ''}">
                            <input type="hidden" name="origin_lat" id="qe_origin_lat" value="${d.origin_lat || ''}">
                            <input type="hidden" name="origin_lng" id="qe_origin_lng" value="${d.origin_lng || ''}">
                            <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="font-size:18px; color:#4285F4;">link</span>
                                <input type="text" id="qeOriginGmapsUrl" placeholder="Tempel link Google Maps asal (Opsional)..." 
                                    value="${(d.origin_name && (d.origin_name.startsWith('http://') || d.origin_name.startsWith('https://'))) ? d.origin_name : ''}"
                                    style="flex:1; padding:0.4rem 0.6rem; font-size:0.78rem; border:1px dashed var(--border); border-radius:6px; font-family:inherit;"
                                    oninput="handleGmapsUrl(this, 'qeOrigin')">
                            </div>
                            <div id="qeOriginGmapsBadge" style="font-size:0.72rem; color:#10b981; margin-top:2px; display:${(d.origin_name && (d.origin_name.startsWith('http://') || d.origin_name.startsWith('https://'))) ? 'block' : 'none'}; font-weight:600;">
                                ${d.origin_lat && d.origin_lng ? `✓ GPS Terbaca: ${d.origin_lat}, ${d.origin_lng}` : '✓ Link Google Maps tersimpan'}
                            </div>
                        </div>
                        <div class="form-group searchable-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                                <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin:0;">Lokasi Tujuan</label>
                                <button type="button" class="btn-pick-map" onclick="openMapPicker('qeDest')" title="Pilih titik lokasi tujuan di peta">
                                    <span class="material-symbols-outlined" style="font-size:14px;">map</span> Pilih di Peta
                                </button>
                            </div>
                            <input type="text" class="searchable-input" id="qe_dest_input" value="${d.destination_name || ''}" placeholder="Cari tujuan..." readonly onclick="toggleOptions('qe_dest_list')">
                            <div id="qe_dest_list" class="options-list"></div>
                            <input type="hidden" name="dest_id" id="qe_dest_id" value="${d.destination_id || ''}">
                            <input type="hidden" name="dest_name" id="qe_dest_name" value="${d.destination_name || ''}">
                            <input type="hidden" name="dest_lat" id="qe_dest_lat" value="${d.destination_lat || ''}">
                            <input type="hidden" name="dest_lng" id="qe_dest_lng" value="${d.destination_lng || ''}">
                            <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="font-size:18px; color:#ea4335;">link</span>
                                <input type="text" id="qeDestGmapsUrl" placeholder="Tempel link Google Maps tujuan (Opsional)..." 
                                    value="${(d.destination_name && (d.destination_name.startsWith('http://') || d.destination_name.startsWith('https://'))) ? d.destination_name : ''}"
                                    style="flex:1; padding:0.4rem 0.6rem; font-size:0.78rem; border:1px dashed var(--border); border-radius:6px; font-family:inherit;"
                                    oninput="handleGmapsUrl(this, 'qeDest')">
                            </div>
                            <div id="qeDestGmapsBadge" style="font-size:0.72rem; color:#10b981; margin-top:2px; display:${(d.destination_name && (d.destination_name.startsWith('http://') || d.destination_name.startsWith('https://'))) ? 'block' : 'none'}; font-weight:600;">
                                ${d.destination_lat && d.destination_lng ? `✓ GPS Terbaca: ${d.destination_lat}, ${d.destination_lng}` : '✓ Link Google Maps tersimpan'}
                            </div>
                        </div>
                    `;
                        // Render location options
                        renderLocationOptions('qe_origin_list', 'qe_origin_id', 'qe_origin_name', false);
                        renderLocationOptions('qe_dest_list', 'qe_dest_id', 'qe_dest_name', true);
                    } else if (field === 'sj') {
                        title.innerText = 'Edit SJ & Koli';
                        icon.innerText = 'description';
                        content.innerHTML = `
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">No Surat Jalan</label>
                            <input type="text" name="surat_jalan" value="${d.surat_jalan || ''}" required oninput="this.value=this.value.toUpperCase()" style="text-transform:uppercase; width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">Total Koli</label>
                            <input type="number" name="total_koli" value="${d.total_koli || 0}" min="0" required style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                        </div>
                    `;
                    } else if (field === 'reassign') {
                        title.innerText = 'Reassign Tugas';
                        icon.innerText = 'person_add';
                        content.innerHTML = `
                        <input type="hidden" name="status" value="pending">
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">Pilih Driver Baru</label>
                            <select name="driver_id" id="qe_driver_select" style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                                ${allDrivers.map(drv => `<option value="${drv.user_id}" ${drv.user_id == d.driver_id ? 'selected' : ''}>${drv.driver_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; margin-bottom:0.5rem; display:block;">Tanggal & Jam</label>
                            <input type="datetime-local" name="target_date" value="${d.target_date ? d.target_date.replace(' ', 'T').slice(0, 16) : todayDateTimeStr()}" style="width:100%; padding:0.75rem; border:1.5px solid var(--border); border-radius:0.75rem;">
                        </div>
                    `;
                    }
                } catch (e) {
                    content.innerHTML = '<div style="color:var(--danger); padding:1rem;">Gagal memuat data.</div>';
                }
            }

            function closeQuickEdit() {
                const modal = document.getElementById('quickEditModal');
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 200);
            }

            const quickEditFormEl = document.getElementById('quickEditForm');
            if (quickEditFormEl) {
                quickEditFormEl.onsubmit = async (e) => {
                    e.preventDefault();
                    const btn = document.getElementById('qe_submit_btn');
                    const msg = document.getElementById('qeMsg');

                    btn.disabled = true;
                    btn.innerHTML = '<span class="material-symbols-outlined rotating">autorenew</span>';
                    msg.innerHTML = '';

                    const qeField = document.getElementById('qe_field').value;
                    if (qeField === 'route') {
                        const originId = document.getElementById('qe_origin_id').value;
                        const originName = document.getElementById('qe_origin_name').value;
                        const destId = document.getElementById('qe_dest_id').value;
                        const destName = document.getElementById('qe_dest_name').value;

                        if ((!originId && !originName) || (!destId && !destName)) {
                            msg.innerHTML = '<span class="error-msg">Silakan pilih lokasi asal & tujuan, atau isi link Google Maps.</span>';
                            btn.disabled = false;
                            btn.innerHTML = '<span class="material-symbols-outlined">save</span> Simpan';
                            return;
                        }
                    }

                    try {
                        const res = await fetch(`${API_URL}?action=quick_edit_delivery`, {
                            method: 'POST',
                            body: new FormData(e.target)
                        });
                        const data = await res.json();
                        if (data.success) {
                            msg.innerHTML = '<span class="success-msg">Berhasil disimpan!</span>';
                            loadHistory();
                            setTimeout(closeQuickEdit, 1000);
                        } else {
                            msg.innerHTML = `<span class="error-msg">${data.error || 'Gagal menyimpan.'}</span>`;
                        }
                    } catch (err) {
                        msg.innerHTML = '<span class="error-msg">Error koneksi.</span>';
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = '<span class="material-symbols-outlined">save</span> Simpan';
                    }
                };
            }

            function changePage(delta) {
                currentPage += delta;
                renderHistoryTable();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function changeRowsPerPage() {
                rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
                currentPage = 1;
                renderHistoryTable();
            }

            loadFormData();
            (function checkUrlParams() {
                const params = new URLSearchParams(window.location.search);
                const search = params.get('search');
                if (search) {
                    const searchInp = document.getElementById('searchInput');
                    const dateF = document.getElementById('date_from');
                    const dateT = document.getElementById('date_to');
                    if (searchInp) searchInp.value = search;
                    if (dateF) dateF.value = '';
                    if (dateT) dateT.value = '';
                }
            })();
            loadHistory();

            // ===== BULK UPLOAD DRIVER TASKS & EXCEL TEMPLATE =====
            async function downloadBulkTemplate() {
                if (typeof XLSX === 'undefined') {
                    showToast('Library SheetJS belum dimuat. Silakan reload halaman.', 'error');
                    return;
                }

                let driverList = [];
                if (typeof allDrivers !== 'undefined' && Array.isArray(allDrivers) && allDrivers.length > 0) {
                    driverList = allDrivers.map(d => d.driver_name || d.name || d.username).filter(Boolean);
                } else {
                    try {
                        const res = await fetch(`${API_URL}?action=get_drivers`);
                        const data = await res.json();
                        if (Array.isArray(data)) {
                            driverList = data.map(d => d.driver_name || d.name || d.username).filter(Boolean);
                        }
                    } catch (e) { console.error(e); }
                }

                if (driverList.length === 0) {
                    driverList = ['Daniel', 'Budi Santoso', 'Agus Setiawan'];
                }

                const firstDriver = driverList[0] || 'Daniel';
                const secondDriver = driverList[1] || firstDriver;

                const sampleData = [
                    ['Driver', 'Tanggal & Jam', 'Jenis Layanan', 'Nama Karyawan / Penumpang', 'Lokasi Asal', 'Lokasi Tujuan', 'Catatan Untuk Driver'],
                    [firstDriver, '01-08-2026 09:00', 'Antar', 'Pak Budi & Tim Marketing', 'Warehouse Somethinc Beauty Haul', 'Head Office', 'Antar meeting dengan klien'],
                    [secondDriver, '01-08-2026 14:00', 'Jemput', 'Ibu Rina', 'https://maps.app.goo.gl/w1X9Y2z3a4b5c6d7', 'https://maps.app.goo.gl/k8J7H6g5f4e3d2c1', 'Jemput dari lokasi via Google Maps Link']
                ];

                const ws = XLSX.utils.aoa_to_sheet(sampleData);
                ws['!cols'] = [
                    { wch: 22 },
                    { wch: 20 },
                    { wch: 18 },
                    { wch: 28 },
                    { wch: 32 },
                    { wch: 32 },
                    { wch: 35 }
                ];

                // Add Excel Data Validation dropdown for Column A (Driver) & Column C (Jenis Layanan)
                const driverValidationStr = driverList.slice(0, 50).join(',');
                ws['!dataValidation'] = [
                    { sqref: 'A2:A500', type: 'list', values: [`"${driverValidationStr}"`] },
                    { sqref: 'C2:C500', type: 'list', values: ['"Antar,Jemput"'] }
                ];

                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Template Bulk Upload');

                // Sheet 2: Reference List of Active Drivers
                const driverSheetRows = [['Nama Driver Active']];
                driverList.forEach(dName => driverSheetRows.push([dName]));
                const wsDrivers = XLSX.utils.aoa_to_sheet(driverSheetRows);
                wsDrivers['!cols'] = [{ wch: 30 }];
                XLSX.utils.book_append_sheet(wb, wsDrivers, 'Daftar Driver');

                XLSX.writeFile(wb, 'Template_Upload_Tugas_Driver.xlsx');
            }

            let parsedBulkRows = [];

            function openBulkUploadModal() {
                resetBulkUpload();
                const m = document.getElementById('bulkUploadModal');
                if (m) {
                    m.style.display = 'flex';
                    setTimeout(() => m.classList.add('show'), 10);
                }
            }

            function closeBulkUploadModal() {
                const m = document.getElementById('bulkUploadModal');
                if (m) {
                    m.classList.remove('show');
                    setTimeout(() => m.style.display = 'none', 300);
                }
            }

            function resetBulkUpload() {
                parsedBulkRows = [];
                const fileInput = document.getElementById('bulkExcelFileInput');
                if (fileInput) fileInput.value = '';
                const dz = document.getElementById('bulkDropZone');
                if (dz) dz.style.display = 'block';
                const info = document.getElementById('bulkUploadInfo');
                if (info) info.style.display = 'none';
                const tbody = document.getElementById('bulkUploadTableBody');
                if (tbody) tbody.innerHTML = '';
                const summary = document.getElementById('bulkValidationSummary');
                if (summary) summary.innerHTML = '';
                const msg = document.getElementById('bulkUploadMsg');
                if (msg) msg.innerHTML = '';
                const btn = document.getElementById('btnSubmitBulkUpload');
                if (btn) btn.disabled = true;
            }

            function handleBulkExcelFile(file) {
                if (!file) return;
                if (typeof XLSX === 'undefined') {
                    showToast('Library Excel belum dimuat.', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                        if (rows.length < 2) {
                            showToast('File Excel kosong atau tidak memiliki baris data.', 'warning');
                            return;
                        }

                        const headerRow = rows[0].map(h => String(h || '').toLowerCase().trim());

                        const colMap = {
                            driver: headerRow.findIndex(h => h.includes('driver') || h.includes('nama driver') || h.includes('username')),
                            target_date: headerRow.findIndex(h => h.includes('tanggal') || h.includes('target') || h.includes('waktu') || h.includes('jam')),
                            task_type: headerRow.findIndex(h => h.includes('layanan') || h.includes('tipe') || h.includes('jenis')),
                            receiver: headerRow.findIndex(h => h.includes('penumpang') || h.includes('karyawan') || h.includes('penerima') || h.includes('receiver')),
                            origin: headerRow.findIndex(h => h.includes('asal') || h.includes('origin')),
                            dest: headerRow.findIndex(h => h.includes('tujuan') || h.includes('dest')),
                            notes: headerRow.findIndex(h => h.includes('catatan') || h.includes('notes') || h.includes('keterangan'))
                        };

                        parsedBulkRows = [];
                        for (let i = 1; i < rows.length; i++) {
                            const r = rows[i];
                            if (!r || r.length === 0 || !r.some(cell => cell !== null && cell !== '')) continue;

                            const driverVal = colMap.driver !== -1 ? String(r[colMap.driver] || '').trim() : String(r[0] || '').trim();
                            const dateVal = colMap.target_date !== -1 ? String(r[colMap.target_date] || '').trim() : String(r[1] || '').trim();
                            const originVal = colMap.origin !== -1 ? String(r[colMap.origin] || '').trim() : String(r[4] || r[2] || '').trim();
                            const destVal = colMap.dest !== -1 ? String(r[colMap.dest] || '').trim() : String(r[5] || r[3] || '').trim();
                            const receiverVal = colMap.receiver !== -1 ? String(r[colMap.receiver] || '').trim() : String(r[3] || '').trim();
                            const notesVal = colMap.notes !== -1 ? String(r[colMap.notes] || '').trim() : String(r[6] || '').trim();

                            if (!driverVal && !originVal && !destVal) continue;

                            let typeVal = colMap.task_type !== -1 ? String(r[colMap.task_type] || '').trim() : String(r[2] || 'antar');
                            typeVal = (typeVal.toLowerCase().includes('jemput') || typeVal.toLowerCase().includes('kirim')) ? 'jemput' : 'antar';

                            parsedBulkRows.push({
                                driver_name: driverVal,
                                target_date: dateVal,
                                task_type: typeVal,
                                receiver_name: receiverVal,
                                origin_name: originVal,
                                dest_name: destVal,
                                notes: notesVal
                            });
                        }

                        if (parsedBulkRows.length === 0) {
                            showToast('Tidak ada data penugasan yang valid ditemukan dalam Excel.', 'warning');
                            return;
                        }

                        renderBulkUploadPreview();
                    } catch (err) {
                        console.error('Error parsing excel:', err);
                        showToast('Gagal membaca file Excel. Pastikan format file sesuai.', 'error');
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            function renderBulkUploadPreview() {
                document.getElementById('bulkDropZone').style.display = 'none';
                document.getElementById('bulkUploadInfo').style.display = 'block';
                document.getElementById('bulkUploadRowCount').textContent = parsedBulkRows.length;

                const tbody = document.getElementById('bulkUploadTableBody');
                tbody.innerHTML = parsedBulkRows.map((r, idx) => {
                    const isValid = r.driver_name && r.origin_name && r.dest_name && r.receiver_name;
                    const isOriginGmaps = r.origin_name && (r.origin_name.includes('http://') || r.origin_name.includes('https://') || r.origin_name.includes('maps.app.goo.gl'));
                    const isDestGmaps = r.dest_name && (r.dest_name.includes('http://') || r.dest_name.includes('https://') || r.dest_name.includes('maps.app.goo.gl'));

                    const originDisplay = isOriginGmaps
                        ? `<span style="color:#1a73e8; font-weight:600; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; white-space:nowrap;" title="${r.origin_name}"><span class="material-symbols-outlined" style="font-size:15px;">link</span> Link Google Maps</span>`
                        : (r.origin_name || '<em style="color:#ef4444;">Kosong</em>');

                    const destDisplay = isDestGmaps
                        ? `<span style="color:#ea4335; font-weight:600; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; white-space:nowrap;" title="${r.dest_name}"><span class="material-symbols-outlined" style="font-size:15px;">link</span> Link Google Maps</span>`
                        : (r.dest_name || '<em style="color:#ef4444;">Kosong</em>');

                    return `
                    <tr style="border-bottom: 1px solid var(--border); ${!isValid ? 'background:#fef2f2;' : ''}">
                        <td style="padding: 8px 10px; text-align:center; white-space:nowrap;">${idx + 1}</td>
                        <td style="padding: 8px 10px; font-weight:600; white-space:nowrap;">${r.driver_name || '<em style="color:#ef4444;">Kosong</em>'}</td>
                        <td style="padding: 8px 10px; white-space:nowrap;">${r.target_date || 'Hari ini'}</td>
                        <td style="padding: 8px 10px; white-space:nowrap; font-weight:600;">${r.task_type === 'antar' ? '🚗 Antar' : '🚐 Jemput'}</td>
                        <td style="padding: 8px 10px;">${r.receiver_name || '<em style="color:#ef4444;">Kosong</em>'}</td>
                        <td style="padding: 8px 10px;">${originDisplay}</td>
                        <td style="padding: 8px 10px;">${destDisplay}</td>
                        <td style="padding: 8px 10px;">${r.notes || '-'}</td>
                    </tr>`;
                }).join('');

                const validCount = parsedBulkRows.filter(r => r.driver_name && r.origin_name && r.dest_name && r.receiver_name).length;
                const summaryDiv = document.getElementById('bulkValidationSummary');
                summaryDiv.innerHTML = `<span style="color:#16a34a; font-weight:600;">✓ ${validCount} dari ${parsedBulkRows.length} baris siap diupload.</span>`;

                document.getElementById('btnSubmitBulkUpload').disabled = validCount === 0;
            }

            async function submitBulkUpload() {
                if (!parsedBulkRows || parsedBulkRows.length === 0) return;

                const btn = document.getElementById('btnSubmitBulkUpload');
                const msg = document.getElementById('bulkUploadMsg');

                btn.disabled = true;
                btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 0.8s linear infinite;">autorenew</span> Mengunggah...';
                msg.innerHTML = '<span style="color:var(--text-muted);">Memproses penugasan massal...</span>';

                try {
                    const res = await fetch(`${API_URL}?action=bulk_assign_tasks`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(parsedBulkRows)
                    });
                    const data = await res.json();

                    if (data.success) {
                        msg.innerHTML = `<span style="color:#16a34a; font-weight:700;">✓ Berhasil menyimpan ${data.count} tugas driver!</span>`;
                        setTimeout(() => {
                            closeBulkUploadModal();
                            loadHistory();
                        }, 1200);
                    } else {
                        msg.innerHTML = `<span style="color:#ef4444; font-weight:600;">${data.error || 'Gagal menyimpan penugasan.'}</span>`;
                        btn.disabled = false;
                        btn.innerHTML = '<span class="material-symbols-outlined">send</span> Proses Upload Tugas';
                    }
                } catch (err) {
                    console.error(err);
                    msg.innerHTML = '<span style="color:#ef4444; font-weight:600;">Terjadi kesalahan koneksi.</span>';
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined">send</span> Proses Upload Tugas';
                }
            }
        </script>

        <!-- ===== ALL MODALS MOVED HERE FOR STACKING CONTEXT SAFETY ===== -->

        <!-- DETAIL MODAL -->
        <div id="detailModal" class="modal-overlay" onclick="if(event.target===this)closeDetail()"
            style="display: none; z-index: 9000;">
            <div class="modal-box" style="max-width:600px;">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; position: sticky; top: -1.5rem; background: var(--surface); z-index: 10; padding-bottom: 1rem; border-bottom: 1px solid var(--border); margin-left: -1.5rem; margin-right: -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; margin-top: -0.5rem;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="material-symbols-outlined"
                            style="color:var(--primary); font-size:28px;">monitoring</span>
                        <h2 style="font-size:1.25rem; font-weight:800; margin:0;">Detail Penugasan</h2>
                    </div>
                    <button class="modal-close" onclick="closeDetail()" style="position:static;">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div id="detailContent"></div>
                <div id="detailMap"></div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
                    <div class="time-box">
                        <span class="time-label">Waktu Mulai</span>
                        <span id="detailStartTime" class="time-value">-</span>
                    </div>
                    <div class="time-box">
                        <span class="time-label">Waktu Selesai</span>
                        <span id="detailEndTime" class="time-value">-</span>
                    </div>
                </div>
                <button type="button" onclick="closeDetail()" class="btn btn-ghost"
                    style="width:100%; justify-content:center; margin-top:1.5rem;">Tutup</button>
            </div>
        </div>

        <div id="imagePopup" class="modal-overlay" onclick="if(event.target===this)closeImagePopup()"
            style="display: none; z-index: 100000;">
            <div
                style="position:relative; max-width:90%; max-height:90%; display:flex; flex-direction:column; align-items:center;">
                <div style="position:absolute; top:-45px; right:0; display:flex; gap:10px;">
                    <a id="downloadImageBtn" href="#" download class="btn btn-primary btn-sm"
                        style="height:32px; padding:0 12px; border-radius:6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        <span class="material-symbols-outlined" style="font-size:20px;">download</span>
                        Download
                    </a>
                    <button onclick="closeImagePopup()"
                        style="background:rgba(255,255,255,0.1); border:none; color:white; cursor:pointer; width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
                        <span class="material-symbols-outlined" style="font-size:24px;">close</span>
                    </button>
                </div>
                <img id="popupImg" src=""
                    style="max-width:100%; max-height:85vh; border-radius:12px; object-fit:contain; box-shadow: 0 20px 50px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1);">
            </div>
        </div>

        <canvas id="watermarkCanvas" style="display:none;"></canvas>
        <div id="toastContainer"></div>
        <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

        <!-- ===== BULK UPLOAD EXCEL MODAL ===== -->
        <div id="bulkUploadModal" class="modal-overlay" onclick="if(event.target===this)closeBulkUploadModal()"
            style="display: none; z-index: 9500;">
            <div class="modal-box wide" style="max-width: 1250px !important; width: 95vw !important;">
                <button class="modal-close" onclick="closeBulkUploadModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <div class="modal-title"
                    style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-outlined"
                            style="color:#0284c7; font-size:28px;">upload_file</span>
                        <span>Bulk Upload Tugas Driver</span>
                    </div>
                    <button type="button" onclick="downloadBulkTemplate()" class="btn-excel-green"
                        style="height:36px; padding:0 0.875rem; background:#10b981; color:white; border:none; border-radius:8px; display:flex; align-items:center; gap:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                        <span class="material-symbols-outlined" style="font-size:18px;">download</span>
                        Download Template Excel (.xlsx)
                    </button>
                </div>
                <p class="modal-subtitle" style="margin-top:4px;">Unggah file Excel (.xlsx / .csv) berisi daftar
                    penugasan driver massal.</p>

                <div style="margin-top: 1rem;">
                    <div id="bulkDropZone" class="upload-box"
                        style="padding: 2rem 1rem; border: 2px dashed #0284c7; background: #f0f9ff; border-radius: 12px; cursor: pointer; text-align: center;"
                        onclick="document.getElementById('bulkExcelFileInput').click()">
                        <span class="material-symbols-outlined"
                            style="font-size: 42px; color: #0284c7;">cloud_upload</span>
                        <div style="margin-top: 0.5rem; font-weight: 700; font-size: 0.95rem; color: #0369a1;">Pilih
                            atau Seret File Excel ke Sini</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Format yang
                            didukung: .xlsx, .xls, .csv</div>
                        <input type="file" id="bulkExcelFileInput" accept=".xlsx, .xls, .csv" style="display:none;"
                            onchange="handleBulkExcelFile(this.files[0])">
                    </div>

                    <div id="bulkUploadInfo" style="margin-top: 1rem; display: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text);">Pratinjau Data
                                Penugasan (<span id="bulkUploadRowCount">0</span> baris)</span>
                            <button type="button" onclick="resetBulkUpload()" class="btn btn-ghost"
                                style="height: 30px; font-size: 0.75rem; padding: 0 0.5rem; color: #ef4444;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">delete</span> Ganti
                                File
                            </button>
                        </div>

                        <div
                            style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                                <thead style="position: sticky; top: 0; background: var(--surface-2); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                                        <th style="padding: 8px 10px; width: 40px;">No</th>
                                        <th style="padding: 8px 10px;">Driver</th>
                                        <th style="padding: 8px 10px;">Tanggal & Jam</th>
                                        <th style="padding: 8px 10px;">Jenis Layanan</th>
                                        <th style="padding: 8px 10px;">Nama Karyawan / Penumpang</th>
                                        <th style="padding: 8px 10px;">Lokasi Asal</th>
                                        <th style="padding: 8px 10px;">Lokasi Tujuan</th>
                                        <th style="padding: 8px 10px;">Catatan Untuk Driver</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkUploadTableBody">
                                </tbody>
                            </table>
                        </div>
                        <div id="bulkValidationSummary" style="margin-top: 0.5rem; font-size: 0.8rem;"></div>
                    </div>

                    <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                        <button type="button" onclick="closeBulkUploadModal()" class="btn btn-ghost"
                            style="flex:1; justify-content:center;">Batal</button>
                        <button type="button" id="btnSubmitBulkUpload" onclick="submitBulkUpload()"
                            class="btn btn-primary"
                            style="flex:1; justify-content:center; background:#0284c7; border:none;" disabled>
                            <span class="material-symbols-outlined">send</span>
                            Proses Upload Tugas
                        </button>
                    </div>
                    <div id="bulkUploadMsg" style="margin-top:0.875rem; text-align:center;"></div>
                </div>
            </div>
        </div>
</body>

</html>