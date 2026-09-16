<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
// session_write_close() dihapus: auth_check.php membuka ulang session, baris ini redundan
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'auth_check.php';

function resolveGmapsCoordsHelper($url)
{
    $url = trim($url);
    if (empty($url))
        return null;

    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
        return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
    }
    if (preg_match('/[?&](?:q|ll)=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
        return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        return null;
    }

    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
            return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
        }
        if (preg_match('/[?&](?:q|ll)=(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
            return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
        }
        if (preg_match('/"(-?\d{1,3}\.\d{5,}),(-?\d{1,3}\.\d{5,})"/', $body, $m)) {
            return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
        }
    } catch (Exception $e) {
    }

    return null;
}

$action = $_GET['action'] ?? '';

// Auto-log non-GET actions (excluding login, which is logged manually after session is set)
if ($action && strpos($action, 'get_') !== 0 && $action !== 'update_location' && $action !== 'login') {
    // Attempt to build a somewhat descriptive message based on action
    $desc = "Aksi dieksekusi: " . str_replace('_', ' ', strtoupper($action));

    // Improved naming logic: if we only have ID, try to find a name for common entities
    $entityName = null;
    if (isset($_POST['id']) && !isset($_POST['name']) && !isset($_POST['username'])) {
        $id = $_POST['id'];
        if (strpos($action, 'user') !== false) {
            $stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtName->execute([$id]);
            $entityName = $stmtName->fetchColumn();
            if ($entityName)
                $desc .= " ($entityName)";
        } elseif (strpos($action, 'location') !== false) {
            $stmtName = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
            $stmtName->execute([$id]);
            $entityName = $stmtName->fetchColumn();
            if ($entityName)
                $desc .= " ($entityName)";
        } elseif (strpos($action, 'vehicle') !== false) {
            $stmtName = $pdo->prepare("SELECT plate_number FROM vehicles WHERE id = ?");
            $stmtName->execute([$id]);
            $entityName = $stmtName->fetchColumn();
            if ($entityName)
                $desc .= " ($entityName)";
        }
    }

    if (!$entityName && isset($_POST['id']))
        $desc .= " (ID: " . $_POST['id'] . ")";
    if (isset($_POST['username']))
        $desc .= " (Username: " . $_POST['username'] . ")";
    if (isset($_POST['name']))
        $desc .= " (Name: " . $_POST['name'] . ")";
    if (isset($_POST['surat_jalan']))
        $desc .= " (SJ: " . $_POST['surat_jalan'] . ")";

    logActivity(strtoupper($action), $desc);
}

// Release session lock for read-only GET requests to enable parallel AJAX processing
if ($action && strpos($action, 'get_') === 0 && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

switch ($action) {
    case 'assign_task':
        if (!canWriteMenu('assign_tasks') && $_SESSION['role'] !== 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        $driver_id = $_POST['driver_id'];
        $origin_id = !empty($_POST['origin_id']) ? $_POST['origin_id'] : null;
        $dest_id = !empty($_POST['dest_id']) ? $_POST['dest_id'] : null;
        $origin_name = $_POST['origin_name'];
        $origin_lat = !empty($_POST['origin_lat']) ? $_POST['origin_lat'] : null;
        $origin_lng = !empty($_POST['origin_lng']) ? $_POST['origin_lng'] : null;
        $dest_name = $_POST['dest_name'];
        $dest_lat = !empty($_POST['dest_lat']) ? $_POST['dest_lat'] : null;
        $dest_lng = !empty($_POST['dest_lng']) ? $_POST['dest_lng'] : null;
        $task_type = $_POST['task_type'] ?? 'antar';
        $target_date = $_POST['target_date'] ?? date('Y-m-d H:i:s');
        $target_date = str_replace('T', ' ', $target_date);
        $pickup_id = $_POST['pickup_id'] ?? null;

        $surat_jalan = isset($_POST['surat_jalan']) ? strtoupper(trim($_POST['surat_jalan'])) : null;
        $total_koli = $_POST['total_koli'] ?? 0;
        $notes = isset($_POST['notes']) ? strtoupper(trim($_POST['notes'])) : null;
        $receiver_name = isset($_POST['receiver_name']) ? trim($_POST['receiver_name']) : null;

        try {
            $pdo->beginTransaction();

            $filenames = [];
            if (isset($_FILES['surat_jalan_file'])) {
                $files = $_FILES['surat_jalan_file'];
                if (is_array($files['name'])) {
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = safeUploadExtension($files['name'][$i]);
                            if (!$ext)
                                continue;
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'DLV_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $filenames[] = $fname;
                            }
                        }
                    }
                } else {
                    if ($files['error'] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name']);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'];
                            $fname = 'DLV_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $filenames[] = $fname;
                            }
                        }
                    }
                }
            }

            $filename_json = !empty($filenames) ? json_encode($filenames) : null;

            $goods_filenames = [];
            if (isset($_FILES['goods_file'])) {
                $files = $_FILES['goods_file'];
                if (is_array($files['name'])) {
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = safeUploadExtension($files['name'][$i]);
                            if (!$ext)
                                continue;
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'GDS_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $goods_filenames[] = $fname;
                            }
                        }
                    }
                } else {
                    if ($files['error'] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name']);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'];
                            $fname = 'GDS_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $goods_filenames[] = $fname;
                            }
                        }
                    }
                }
            }
            $goods_filename_json = !empty($goods_filenames) ? json_encode($goods_filenames) : null;

            if ($pickup_id !== null && $pickup_id !== '') {
                $stmtReq = $pdo->prepare("SELECT surat_jalan_file, goods_file FROM pickup_requests WHERE id = ?");
                $stmtReq->execute([$pickup_id]);
                $reqData = $stmtReq->fetch();

                // If no new file uploaded, use the one from request
                if (!$filename_json && $reqData) {
                    $filename_json = $reqData['surat_jalan_file'];
                }
                if (!$goods_filename_json && $reqData) {
                    $goods_filename_json = $reqData['goods_file'];
                }

                $stmt2 = $pdo->prepare("UPDATE pickup_requests SET status = 'approved' WHERE id = ?");
                $stmt2->execute([$pickup_id]);
            }

            $sql = "INSERT INTO deliveries (driver_id, pickup_id, origin_id, destination_id, origin_name, origin_lat, origin_lng, destination_name, destination_lat, destination_lng, surat_jalan, total_koli, notes, receiver_name, surat_jalan_file, goods_file, task_type, target_date, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$driver_id, $pickup_id, $origin_id, $dest_id, $origin_name, $origin_lat, $origin_lng, $dest_name, $dest_lat, $dest_lng, $surat_jalan, $total_koli, $notes, $receiver_name, $filename_json, $goods_filename_json, $task_type, $target_date, $_SESSION['user_id']]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'bulk_assign_tasks':
        if (!canWriteMenu('assign_tasks') && $_SESSION['role'] !== 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }

        $jsonInput = file_get_contents('php://input');
        $tasksData = json_decode($jsonInput, true);
        if (!$tasksData || !is_array($tasksData)) {
            $tasksData = json_decode($_POST['tasks'] ?? '[]', true);
        }

        if (empty($tasksData) || !is_array($tasksData)) {
            die(json_encode(['success' => false, 'error' => 'Data tugas kosong atau format tidak valid']));
        }

        $driversStmt = $pdo->query("SELECT id, name, username FROM users WHERE is_active = 1");
        $allUsers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
        $userMap = [];
        foreach ($allUsers as $u) {
            if (!empty($u['name']))
                $userMap[strtolower(trim($u['name']))] = $u['id'];
            if (!empty($u['username']))
                $userMap[strtolower(trim($u['username']))] = $u['id'];
        }

        $locsStmt = $pdo->query("SELECT id, name, lat, lng FROM locations");
        $allLocs = $locsStmt->fetchAll(PDO::FETCH_ASSOC);
        $locMap = [];
        foreach ($allLocs as $l) {
            if (!empty($l['name']))
                $locMap[strtolower(trim($l['name']))] = $l;
        }

        $insertedCount = 0;
        $errors = [];

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO deliveries (driver_id, origin_id, destination_id, origin_name, origin_lat, origin_lng, destination_name, destination_lat, destination_lng, surat_jalan, total_koli, notes, receiver_name, task_type, target_date, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);

            foreach ($tasksData as $idx => $row) {
                $rowNum = $idx + 1;
                $driver_id = null;
                if (!empty($row['driver_id']) && is_numeric($row['driver_id'])) {
                    $driver_id = (int) $row['driver_id'];
                } else {
                    $driverInput = trim($row['driver_name'] ?? $row['driver'] ?? '');
                    $driverKey = strtolower($driverInput);
                    if ($driverInput && isset($userMap[$driverKey])) {
                        $driver_id = $userMap[$driverKey];
                    }
                }

                if (!$driver_id) {
                    $errors[] = "Baris $rowNum: Driver wajib dipilih.";
                    continue;
                }

                $origin_name = trim($row['origin_name'] ?? $row['origin'] ?? '');
                $dest_name = trim($row['dest_name'] ?? $row['destination'] ?? $row['dest'] ?? '');

                if (empty($origin_name))
                    $origin_name = '-';
                if (empty($dest_name))
                    $dest_name = '-';

                $originKey = strtolower($origin_name);
                $destKey = strtolower($dest_name);

                $origin_id = !empty($row['origin_id']) ? $row['origin_id'] : (isset($locMap[$originKey]) ? $locMap[$originKey]['id'] : null);
                $origin_lat = !empty($row['origin_lat']) ? $row['origin_lat'] : (isset($locMap[$originKey]) ? $locMap[$originKey]['lat'] : null);
                $origin_lng = !empty($row['origin_lng']) ? $row['origin_lng'] : (isset($locMap[$originKey]) ? $locMap[$originKey]['lng'] : null);
                $dest_id = !empty($row['dest_id']) ? $row['dest_id'] : (isset($locMap[$destKey]) ? $locMap[$destKey]['id'] : null);
                $dest_lat = !empty($row['dest_lat']) ? $row['dest_lat'] : (isset($locMap[$destKey]) ? $locMap[$destKey]['lat'] : null);
                $dest_lng = !empty($row['dest_lng']) ? $row['dest_lng'] : (isset($locMap[$destKey]) ? $locMap[$destKey]['lng'] : null);

                $task_type = strtolower(trim($row['task_type'] ?? 'antar'));
                if (strpos($task_type, 'jemput') !== false || strpos($task_type, 'kirim') !== false) {
                    $task_type = 'jemput';
                } else {
                    $task_type = 'antar';
                }

                // Parse Indonesian Date format (dd-mm-yyyy or dd/mm/yyyy) or YYYY-MM-DD
                $rawDate = trim($row['target_date'] ?? '');
                $target_date = date('Y-m-d H:i:s');
                if (!empty($rawDate)) {
                    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $rawDate, $dm)) {
                        $day = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($dm[2], 2, '0', STR_PAD_LEFT);
                        $year = $dm[3];
                        $hour = isset($dm[4]) ? str_pad($dm[4], 2, '0', STR_PAD_LEFT) : '08';
                        $min = isset($dm[5]) ? str_pad($dm[5], 2, '0', STR_PAD_LEFT) : '00';
                        $sec = isset($dm[6]) ? str_pad($dm[6], 2, '0', STR_PAD_LEFT) : '00';
                        $target_date = "$year-$month-$day $hour:$min:$sec";
                    } else if (preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDate)) {
                        if (strlen($rawDate) == 10)
                            $rawDate .= ' 08:00:00';
                        $target_date = str_replace('T', ' ', $rawDate);
                    } else {
                        $target_date = str_replace('T', ' ', $rawDate);
                    }
                }

                // Resolve Google Maps link for Origin & Destination if URL provided
                if (preg_match('/https?:\/\//i', $origin_name)) {
                    $resolvedOrigin = resolveGmapsCoordsHelper($origin_name);
                    if ($resolvedOrigin) {
                        $origin_lat = $resolvedOrigin['lat'];
                        $origin_lng = $resolvedOrigin['lng'];
                        $origin_name = "Pin Google Maps (" . $resolvedOrigin['lat'] . ", " . $resolvedOrigin['lng'] . ")";
                    }
                }

                if (preg_match('/https?:\/\//i', $dest_name)) {
                    $resolvedDest = resolveGmapsCoordsHelper($dest_name);
                    if ($resolvedDest) {
                        $dest_lat = $resolvedDest['lat'];
                        $dest_lng = $resolvedDest['lng'];
                        $dest_name = "Pin Google Maps (" . $resolvedDest['lat'] . ", " . $resolvedDest['lng'] . ")";
                    }
                }

                $surat_jalan = !empty($row['surat_jalan']) ? strtoupper(trim($row['surat_jalan'])) : 'HO-TASK';
                $total_koli = isset($row['total_koli']) && is_numeric($row['total_koli']) ? (int) $row['total_koli'] : 1;
                $notes = !empty($row['notes']) ? strtoupper(trim($row['notes'])) : null;
                $receiver_name = !empty($row['receiver_name']) ? trim($row['receiver_name']) : (!empty($row['passenger_name']) ? trim($row['passenger_name']) : null);

                $stmt->execute([
                    $driver_id,
                    $origin_id,
                    $dest_id,
                    $origin_name,
                    $origin_lat,
                    $origin_lng,
                    $dest_name,
                    $dest_lat,
                    $dest_lng,
                    $surat_jalan,
                    $total_koli,
                    $notes,
                    $receiver_name,
                    $task_type,
                    $target_date,
                    $_SESSION['user_id']
                ]);
                $insertedCount++;
            }

            if ($insertedCount > 0) {
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'count' => $insertedCount,
                    'errors' => $errors
                ]);
            } else {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'error' => !empty($errors) ? implode("<br>", $errors) : 'Tidak ada data valid yang bisa disimpan.'
                ]);
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
        break;

    case 'update_location':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        // Force user_id dari session agar driver tidak bisa spoof koordinat driver lain
        $user_id = $_SESSION['user_id'];
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
            die(json_encode(['success' => false, 'error' => 'lat & lng wajib & harus numerik']));
        }

        $sql = "INSERT INTO drivers_location (user_id, lat, lng) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), last_updated = CURRENT_TIMESTAMP";
        $pdo->prepare($sql)->execute([$user_id, $lat, $lng]);

        // Log to history
        $pdo->prepare("INSERT INTO location_logs (user_id, lat, lng) VALUES (?, ?, ?)")->execute([$user_id, $lat, $lng]);

        echo json_encode(['success' => true]);
        break;

    case 'update_status':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $delivery_id = $_POST['delivery_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $late_reason = $_POST['late_reason'] ?? null;

        if (!$delivery_id || !$status) {
            die(json_encode(['success' => false, 'error' => 'delivery_id dan status wajib diisi']));
        }
        if (!in_array($status, ['in_transit', 'completed', 'canceled'], true)) {
            die(json_encode(['success' => false, 'error' => 'Status tidak valid']));
        }

        // Ownership check: driver hanya bisa update tugasnya sendiri; admin/superadmin/controller bebas
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin', 'controller'], true)) {
            $stmtOwn = $pdo->prepare("SELECT driver_id FROM deliveries WHERE id = ?");
            $stmtOwn->execute([$delivery_id]);
            $ownerId = $stmtOwn->fetchColumn();
            if (!$ownerId) {
                die(json_encode(['success' => false, 'error' => 'Tugas tidak ditemukan']));
            }
            if ((int) $ownerId !== (int) $_SESSION['user_id']) {
                die(json_encode(['success' => false, 'error' => 'Akses ditolak: Anda bukan driver tugas ini']));
            }
        }

        if ($status == 'in_transit') {
            $speedo_num = $_POST['speedometer_start_num'] ?? null;
            $speedo_photo = null;
            if (isset($_FILES['speedometer_start_photo']) && $_FILES['speedometer_start_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['speedometer_start_photo']['name'], PATHINFO_EXTENSION);
                $speedo_photo = uniqid('speedo_') . '.' . $ext;
                move_uploaded_file($_FILES['speedometer_start_photo']['tmp_name'], __DIR__ . '/uploads/' . $speedo_photo);
            }
            // Get current vehicle assigned to this driver
            $stmtV = $pdo->prepare("SELECT vehicle_id FROM driver_active_vehicles WHERE driver_id = (SELECT driver_id FROM deliveries WHERE id = ?) LIMIT 1");
            $stmtV->execute([$delivery_id]);
            $vid = $stmtV->fetchColumn();

            $sql = "UPDATE deliveries SET status = ?, start_time = NOW(), vehicle_id = ?, speedometer_start_num = ?, speedometer_start_photo = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$status, $vid, $speedo_num, $speedo_photo, $delivery_id]);
        } elseif ($status == 'completed' || $status == 'canceled') {
            // Handle Proof Photos (Multiple)
            $filenames = [];
            $receiver_name = $_POST['receiver_name'] ?? null;

            if (isset($_FILES['surat_jalan_file'])) {
                $files = $_FILES['surat_jalan_file'];

                // Check if it's a single file or array
                if (is_array($files['name'])) {
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = safeUploadExtension($files['name'][$i]);
                            if (!$ext)
                                continue;
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'PRF_' . time() . '_' . uniqid() . '.' . $ext;

                            if (!is_dir('uploads'))
                                mkdir('uploads', 0777, true);
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $filenames[] = $fname;
                            }
                        }
                    }
                } else {
                    if ($files['error'] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name']);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'];
                            $fname = 'PRF_' . time() . '_' . uniqid() . '.' . $ext;

                            if (!is_dir('uploads'))
                                mkdir('uploads', 0777, true);
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $filenames[] = $fname;
                            }
                        }
                    }
                }
            }

            $proof_file_json = !empty($filenames) ? json_encode($filenames) : null;
            $driver_notes = $_POST['driver_notes'] ?? null;

            $speedo_end_num = $_POST['speedometer_end_num'] ?? null;
            $speedo_end_photo = null;
            if (isset($_FILES['speedometer_end_photo']) && $_FILES['speedometer_end_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['speedometer_end_photo']['name'], PATHINFO_EXTENSION);
                $speedo_end_photo = uniqid('speedo_end_') . '.' . $ext;
                if (!is_dir('uploads'))
                    mkdir('uploads', 0777, true);
                move_uploaded_file($_FILES['speedometer_end_photo']['tmp_name'], __DIR__ . '/uploads/' . $speedo_end_photo);
            }

            $sql = "UPDATE deliveries SET status = ?, end_time = NOW(), receiver_name = COALESCE(NULLIF(?, ''), receiver_name), late_reason = ?, driver_notes = ?, proof_file = ?, speedometer_end_num = ?, speedometer_end_photo = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$status, $receiver_name, $late_reason, $driver_notes, $proof_file_json, $speedo_end_num, $speedo_end_photo, $delivery_id]);

            // Calculate duration if start_time exists
            $stmt = $pdo->prepare("SELECT start_time, end_time FROM deliveries WHERE id = ?");
            $stmt->execute([$delivery_id]);
            $row = $stmt->fetch();
            if ($row && $row['start_time'] && $row['end_time']) {
                $start = new DateTime($row['start_time']);
                $end = new DateTime($row['end_time']);
                $interval = $start->diff($end);
                $duration = sprintf('%02d:%02d:%02d', $interval->h + ($interval->days * 24), $interval->i, $interval->s);
                $pdo->prepare("UPDATE deliveries SET duration = ? WHERE id = ?")->execute([$duration, $delivery_id]);
            }

            // Update linked pickup_request if any
            $stmtP = $pdo->prepare("SELECT pickup_id FROM deliveries WHERE id = ?");
            $stmtP->execute([$delivery_id]);
            $dData = $stmtP->fetch();
            if ($dData && $dData['pickup_id']) {
                // If completed, set pickup to completed. If canceled, set back to pending so it can be re-assigned.
                $newPickupStatus = ($status === 'completed') ? 'completed' : 'pending';
                $pdo->prepare("UPDATE pickup_requests SET status = ? WHERE id = ?")->execute([$newPickupStatus, $dData['pickup_id']]);
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'cancel_delivery':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $id = $_POST['id'] ?? $_POST['delivery_id'] ?? null;
        $reason = $_POST['reason'] ?? $_POST['late_reason'] ?? 'Dibatalkan oleh Admin';

        if (!$id) {
            die(json_encode(['success' => false, 'error' => 'ID tugas wajib diisi']));
        }

        try {
            $sql = "UPDATE deliveries SET status = 'canceled', end_time = NOW(), late_reason = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$reason, $id]);

            // Update linked pickup_request if any
            $stmtP = $pdo->prepare("SELECT pickup_id FROM deliveries WHERE id = ?");
            $stmtP->execute([$id]);
            $dData = $stmtP->fetch();
            if ($dData && $dData['pickup_id']) {
                $pdo->prepare("UPDATE pickup_requests SET status = 'pending' WHERE id = ?")->execute([$dData['pickup_id']]);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal membatalkan tugas: ' . $e->getMessage()]);
        }
        break;

    case 'mark_shared':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $delivery_id = $_POST['delivery_id'] ?? null;
        if (!$delivery_id) {
            die(json_encode(['success' => false, 'error' => 'delivery_id wajib diisi']));
        }
        // Ownership check: hanya driver pemilik tugas atau admin/controller yang boleh mark_shared
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin', 'controller'], true)) {
            $stmtOwn = $pdo->prepare("SELECT driver_id FROM deliveries WHERE id = ?");
            $stmtOwn->execute([$delivery_id]);
            $ownerId = $stmtOwn->fetchColumn();
            if ((int) $ownerId !== (int) $_SESSION['user_id']) {
                die(json_encode(['success' => false, 'error' => 'Akses ditolak: Anda bukan pemilik tugas ini']));
            }
        }
        $sql = "UPDATE deliveries SET is_shared = 1 WHERE id = ?";
        $pdo->prepare($sql)->execute([$delivery_id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_calendar_summary':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $driver_id = $_GET['driver_id'];
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];

        $sql = "SELECT DATE(target_date) as target_date, status, COUNT(*) as cnt 
                FROM deliveries 
                WHERE driver_id = ? 
                AND target_date >= ? AND target_date <= ?
                GROUP BY DATE(target_date), status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driver_id, $start_date, $end_date]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_monthly_summary':
        $month = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM
        $sql = "SELECT driver_id, SUBSTRING(COALESCE(target_date, created_at), 1, 10) as t_date, 
                       COUNT(*) as total_tasks,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                       SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as late_count
                FROM deliveries 
                WHERE COALESCE(target_date, created_at) LIKE ? 
                GROUP BY driver_id, SUBSTRING(COALESCE(target_date, created_at), 1, 10)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$month . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['target_date'] = substr($r['t_date'], 0, 10);
            unset($r['t_date']);
        }
        echo json_encode($rows);
        break;

    case 'get_expedisi_monthly_summary':
        $month = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM
        $sql = "SELECT ev.name as driver_id, SUBSTRING(COALESCE(e.target_date, e.created_at), 1, 10) as t_date, 
                       COUNT(*) as total_tasks,
                       SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                       SUM(CASE WHEN e.status = 'canceled' THEN 1 ELSE 0 END) as late_count
                FROM expedisi_tasks e
                JOIN expedisi_vendors ev ON e.vendor_id = ev.id
                WHERE COALESCE(e.target_date, e.created_at) LIKE ? 
                GROUP BY ev.name, SUBSTRING(COALESCE(e.target_date, e.created_at), 1, 10)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$month . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['target_date'] = substr($r['t_date'], 0, 10);
            unset($r['t_date']);
        }
        echo json_encode($rows);
        break;

    case 'get_expedisi_unique_drivers':
        $sql = "SELECT name as driver_name, 
                       name as user_id
                FROM expedisi_vendors
                ORDER BY driver_name ASC";
        echo json_encode($pdo->query($sql)->fetchAll());
        break;

    case 'get_location_history':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $user_id = $_GET['user_id'];
        $stmt = $pdo->prepare("SELECT lat, lng FROM location_logs 
                               WHERE user_id = ? 
                               AND DATE(logged_at) = (SELECT DATE(MAX(logged_at)) FROM location_logs WHERE user_id = ?) 
                               ORDER BY logged_at ASC");
        $stmt->execute([$user_id, $user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_gps_alerts':
        $sql = "SELECT u.id as user_id, u.name as driver_name, dl.last_updated, di.phone_number,
                TIMESTAMPDIFF(MINUTE, dl.last_updated, NOW()) as minutes_inactive
                FROM users u
                JOIN drivers_location dl ON u.id = dl.user_id
                LEFT JOIN drivers_info di ON u.id = di.user_id
                WHERE u.role = 'driver'
                AND TIMESTAMPDIFF(MINUTE, dl.last_updated, NOW()) >= 30
                AND (SELECT COUNT(*) FROM deliveries d 
                     WHERE d.driver_id = u.id AND d.status = 'in_transit') > 0";
        echo json_encode($pdo->query($sql)->fetchAll());
        break;

    case 'get_drivers':
        $sql = "SELECT u.id as user_id, u.name as driver_name, dl.lat, dl.lng, dl.last_updated,
                (SELECT COUNT(*) FROM deliveries d 
                 WHERE d.driver_id = u.id 
                 AND d.status IN ('pending', 'in_transit')
                 AND ((d.target_date IS NOT NULL AND d.target_date = CURDATE()) OR (d.target_date IS NULL AND d.created_at >= CURDATE() AND d.created_at < CURDATE() + INTERVAL 1 DAY))) as task_count,
                (SELECT GROUP_CONCAT(CONCAT(destination_name, '|', status) SEPARATOR '||') FROM deliveries d 
                 WHERE d.driver_id = u.id 
                 AND d.status IN ('pending', 'in_transit')
                 AND ((d.target_date IS NOT NULL AND d.target_date = CURDATE()) OR (d.target_date IS NULL AND d.created_at >= CURDATE() AND d.created_at < CURDATE() + INTERVAL 1 DAY))
                 ORDER BY d.created_at ASC) as all_destinations,
                (SELECT COUNT(*) FROM deliveries d 
                 WHERE d.driver_id = u.id 
                 AND d.status IN ('pending', 'in_transit')) as total_active_tasks,
                v.name as vehicle_name, v.plate_number as vehicle_plate
                FROM users u
                LEFT JOIN drivers_location dl ON u.id = dl.user_id 
                LEFT JOIN driver_active_vehicles dav ON u.id = dav.driver_id
                LEFT JOIN vehicles v ON dav.vehicle_id = v.id
                WHERE u.role = 'driver' AND u.is_active = 1";
        echo json_encode($pdo->query($sql)->fetchAll());
        break;


    case 'get_driver_location':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $driver_id = $_GET['driver_id'];
        $sql = "SELECT dl.lat, dl.lng, dl.last_updated, u.name as driver_name 
                FROM drivers_location dl 
                JOIN users u ON dl.user_id = u.id 
                WHERE dl.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driver_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Location not found']);
        break;

    case 'get_deliveries':
        try {
            $driver_id = $_GET['driver_id'] ?? null;
            $task_type = $_GET['task_type'] ?? $_GET['type'] ?? null;

            $sql = "SELECT d.*, u.name as driver_name, creator.name as creator_name, di_creator.phone_number as creator_phone,
                    req_user.name as requester_name,
                    COALESCE(v1.name, v2.name, v3.name) as vehicle_name, 
                    COALESCE(v1.plate_number, v2.plate_number, v3.plate_number) as vehicle_plate, 
                    p.created_at as request_created_at,
                    p.goods_file as request_goods_raw, p.surat_jalan_file as request_sj_file,
                    dl.lat as driver_lat, dl.lng as driver_lng, dl.last_updated as location_updated,
                    (SELECT COUNT(*) FROM deliveries d2 
                     WHERE d2.surat_jalan = d.surat_jalan 
                     AND d.surat_jalan != '' AND d.surat_jalan IS NOT NULL
                     AND d2.status != 'canceled' 
                     AND d2.created_at > d.created_at) as has_reassigned_task
                    FROM deliveries d 
                    JOIN users u ON d.driver_id = u.id 
                    LEFT JOIN users creator ON d.created_by = creator.id
                    LEFT JOIN drivers_info di_creator ON creator.id = di_creator.user_id
                    LEFT JOIN pickup_requests p ON d.pickup_id = p.id
                    LEFT JOIN users req_user ON p.requester_id = req_user.id
                    LEFT JOIN drivers_location dl ON d.driver_id = dl.user_id
                    LEFT JOIN vehicles v1 ON d.vehicle_id = v1.id
                    LEFT JOIN driver_active_vehicles dav ON d.driver_id = dav.driver_id
                    LEFT JOIN vehicles v2 ON dav.vehicle_id = v2.id
                    LEFT JOIN (SELECT driver_id, MAX(id) AS max_id FROM vehicle_logs GROUP BY driver_id) vl_max ON d.driver_id = vl_max.driver_id
                    LEFT JOIN vehicle_logs vl ON vl_max.max_id = vl.id
                    LEFT JOIN vehicles v3 ON vl.vehicle_id = v3.id";
            $conditions = [];
            $params = [];

            if ($task_type) {
                $conditions[] = "d.task_type = ?";
                $params[] = $task_type;
            }
            if ($driver_id !== null && $driver_id !== '') {
                $conditions[] = "d.driver_id = ?";
                $params[] = $driver_id;
            }
            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $conditions[] = "d.status = ?";
                $params[] = $_GET['status'];
            }
            if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
                $df = $_GET['date_from'];
                $conditions[] = "((d.target_date IS NOT NULL AND d.target_date >= ?) OR (d.target_date IS NULL AND d.created_at >= ?))";
                $params[] = $df . ' 00:00:00';
                $params[] = $df . ' 00:00:00';
            }
            if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
                $dt = $_GET['date_to'];
                $conditions[] = "((d.target_date IS NOT NULL AND d.target_date <= ?) OR (d.target_date IS NULL AND d.created_at <= ?))";
                $params[] = $dt . ' 23:59:59';
                $params[] = $dt . ' 23:59:59';
            }
            if (isset($_GET['target_date']) && $_GET['target_date'] !== '') {
                $tdate = $_GET['target_date'];
                $conditions[] = "((d.target_date IS NOT NULL AND d.target_date >= ? AND d.target_date <= ?) OR (d.target_date IS NULL AND d.created_at >= ? AND d.created_at <= ?))";
                $params[] = $tdate . ' 00:00:00';
                $params[] = $tdate . ' 23:59:59';
                $params[] = $tdate . ' 00:00:00';
                $params[] = $tdate . ' 23:59:59';
            }
            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $s = "%" . $_GET['search'] . "%";
                $conditions[] = "(d.surat_jalan LIKE ? OR d.origin_name LIKE ? OR d.destination_name LIKE ? OR u.name LIKE ? OR d.receiver_name LIKE ?)";
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process JSON fields into arrays
            if (is_array($rows)) {
                foreach ($rows as &$row) {
                    // Proof files
                    $proof_raw = $row['proof_file'] ?? '';
                    if ($proof_raw) {
                        $decoded = json_decode($proof_raw, true);
                        $row['proof_file_arr'] = is_array($decoded) ? $decoded : [$proof_raw];
                    } else {
                        $row['proof_file_arr'] = [];
                    }

                    // Goods files from request
                    $goods_raw = $row['request_goods_raw'] ?? '';
                    if ($goods_raw) {
                        $decoded2 = json_decode($goods_raw, true);
                        $row['request_goods'] = is_array($decoded2) ? $decoded2 : [$goods_raw];
                    } else {
                        $row['request_goods'] = [];
                    }

                    // SJ files
                    $sj_raw = $row['surat_jalan_file'] ?? '';
                    if ($sj_raw) {
                        $decoded3 = json_decode($sj_raw, true);
                        $row['surat_jalan_file'] = is_array($decoded3) ? $decoded3 : [$sj_raw];
                    } else {
                        $row['surat_jalan_file'] = [];
                    }

                    // Fallback SJ from request
                    $req_sj_raw = $row['request_sj_file'] ?? '';
                    if ($req_sj_raw) {
                        $decoded4 = json_decode($req_sj_raw, true);
                        $row['request_sj_file_arr'] = is_array($decoded4) ? $decoded4 : [$req_sj_raw];
                    } else {
                        $row['request_sj_file_arr'] = [];
                    }
                }
            }
            unset($row);

            echo json_encode($rows);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_tracking_report':
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $sj_filter = $_GET['surat_jalan'] ?? '';
        $status_f = $_GET['status'] ?? '';
        $task_type = $_GET['task_type'] ?? $_GET['type'] ?? '';

        $sql = "SELECT 
                    d.id,
                    d.driver_id,
                    d.surat_jalan,
                    d.origin_name,
                    d.destination_name,
                    d.total_koli,
                    d.notes,
                    d.status,
                    d.start_time,
                    d.end_time,
                    d.duration,
                    d.receiver_name,
                    d.driver_notes,
                    d.late_reason,
                    d.task_type,
                    d.speedometer_start_num,
                    d.speedometer_start_photo,
                    d.speedometer_end_num,
                    d.speedometer_end_photo,
                    d.created_at       AS assign_time,
                    d.target_date,
                    d.proof_file       AS proof_files_raw,
                    d.surat_jalan_file AS pickup_sj_file,
                    d.goods_file       AS delivery_goods_raw,
                    u.name             AS driver_name,
                    COALESCE(v1.plate_number, v2.plate_number, v3.plate_number) AS vehicle_plate,
                    COALESCE(v1.name, v2.name, v3.name) AS vehicle_name,
                    creator.name       AS assigned_by,
                    p.id               AS request_id,
                    p.created_at       AS request_time,
                    p.status           AS request_status,
                    p.surat_jalan_file AS request_sj_file,
                    p.goods_file       AS request_goods_raw,
                    req_user.name      AS requester_name
                FROM deliveries d
                JOIN users u ON d.driver_id = u.id
                LEFT JOIN users creator ON d.created_by = creator.id
                LEFT JOIN pickup_requests p ON d.pickup_id = p.id
                LEFT JOIN users req_user ON p.requester_id = req_user.id
                LEFT JOIN vehicles v1 ON d.vehicle_id = v1.id
                LEFT JOIN driver_active_vehicles dav ON d.driver_id = dav.driver_id
                LEFT JOIN vehicles v2 ON dav.vehicle_id = v2.id
                LEFT JOIN (SELECT driver_id, MAX(id) AS max_id FROM vehicle_logs GROUP BY driver_id) vl_max ON d.driver_id = vl_max.driver_id
                LEFT JOIN vehicle_logs vl ON vl_max.max_id = vl.id
                LEFT JOIN vehicles v3 ON vl.vehicle_id = v3.id
                WHERE DATE(COALESCE(d.target_date, d.created_at)) BETWEEN ? AND ?";

        $params = [$date_from, $date_to];

        if ($sj_filter !== '') {
            $sql .= " AND (d.surat_jalan LIKE ? OR d.origin_name LIKE ? OR d.destination_name LIKE ? OR u.name LIKE ? OR d.receiver_name LIKE ?)";
            $params[] = "%$sj_filter%";
            $params[] = "%$sj_filter%";
            $params[] = "%$sj_filter%";
            $params[] = "%$sj_filter%";
            $params[] = "%$sj_filter%";
        }
        if ($status_f !== '') {
            $sql .= " AND d.status = ?";
            $params[] = $status_f;
        }
        if ($task_type !== '') {
            $sql .= " AND d.task_type = ?";
            $params[] = $task_type;
        }

        $sql .= " ORDER BY COALESCE(d.target_date, d.created_at) DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Format proof & goods files as arrays
        foreach ($rows as &$row) {
            // Proof files
            $raw = $row['proof_files_raw'] ?? '';
            $row['proof_file'] = $raw;
            if ($raw) {
                $decoded = json_decode($raw, true);
                $row['delivery_proofs'] = is_array($decoded) ? $decoded : [$raw];
            } else {
                $row['delivery_proofs'] = [];
            }

            // Goods files (Combine from Request and Delivery)
            $goods_raw_p = $row['request_goods_raw'] ?? '';
            $goods_raw_d = $row['delivery_goods_raw'] ?? '';

            $goods_arr = [];
            if ($goods_raw_p) {
                $decoded = json_decode($goods_raw_p, true);
                $goods_arr = array_merge($goods_arr, is_array($decoded) ? $decoded : [$goods_raw_p]);
            }
            if ($goods_raw_d) {
                $decoded = json_decode($goods_raw_d, true);
                $goods_arr = array_merge($goods_arr, is_array($decoded) ? $decoded : [$goods_raw_d]);
            }
            $row['request_goods'] = array_values(array_unique($goods_arr));

            unset($row['request_goods_raw']);
            unset($row['delivery_goods_raw']);

            // SJ Request files
            $rsj_raw = $row['request_sj_file'] ?? '';
            if ($rsj_raw) {
                $decoded3 = json_decode($rsj_raw, true);
                $row['request_sj_file'] = is_array($decoded3) ? $decoded3 : [$rsj_raw];
            } else {
                $row['request_sj_file'] = [];
            }

            // SJ Pickup files
            $psj_raw = $row['pickup_sj_file'] ?? '';
            if ($psj_raw) {
                $decoded4 = json_decode($psj_raw, true);
                $row['pickup_sj_file'] = is_array($decoded4) ? $decoded4 : [$psj_raw];
            } else {
                $row['pickup_sj_file'] = [];
            }
        }
        unset($row);

        echo json_encode($rows);
        break;

    case 'get_driver_kpi':
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-t');
        $driver_id = $_GET['driver_id'] ?? '';

        $params = [$date_from, $date_to];
        $driverFilter = '';
        if ($driver_id !== '') {
            $driverFilter = " AND d.driver_id = ?";
            $params[] = $driver_id;
        }

        $sql = "SELECT 
                    u.id AS driver_id,
                    u.name AS driver_name,
                    COUNT(d.id) AS total_all,
                    SUM(d.task_type = 'antar') AS total_delivery,
                    SUM(d.task_type = 'kirim') AS total_pickup,
                    SUM(d.status = 'completed') AS total_completed,
                    SUM(d.status = 'canceled') AS total_canceled,
                    SUM(d.status = 'in_transit') AS total_transit,
                    SUM(d.status = 'pending') AS total_pending,
                    SUM(d.status = 'completed' AND d.late_reason IS NOT NULL AND d.late_reason != '') AS total_late
                FROM deliveries d
                JOIN users u ON d.driver_id = u.id
                WHERE DATE(COALESCE(d.target_date, d.created_at)) BETWEEN ? AND ?
                $driverFilter
                GROUP BY u.id, u.name
                ORDER BY total_completed DESC, total_all DESC
                LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $total = max((int) $row['total_all'], 1);
            $row['progress_pct'] = round(($row['total_completed'] / $total) * 100);
            $row['total_all'] = (int) $row['total_all'];
            $row['total_delivery'] = (int) $row['total_delivery'];
            $row['total_pickup'] = (int) $row['total_pickup'];
            $row['total_completed'] = (int) $row['total_completed'];
            $row['total_canceled'] = (int) $row['total_canceled'];
            $row['total_transit'] = (int) $row['total_transit'];
            $row['total_pending'] = (int) $row['total_pending'];
            $row['total_late'] = (int) $row['total_late'];
        }
        unset($row);
        echo json_encode($rows);
        break;

    case 'get_expedisi_kpi':
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-t');
        $vendor_id = $_GET['vendor_id'] ?? '';

        $params2 = [$date_from, $date_to];
        $vendorFilter = '';
        if ($vendor_id !== '') {
            $vendorFilter = " AND et.vendor_id = ?";
            $params2[] = $vendor_id;
        }

        $sql2 = "SELECT 
                    ev.id AS vendor_id,
                    ev.name AS driver_name,
                    COUNT(et.id) AS total_all,
                    COUNT(et.id) AS total_delivery,
                    0 AS total_pickup,
                    SUM(et.status = 'completed') AS total_completed,
                    SUM(et.status = 'canceled') AS total_canceled,
                    SUM(et.status = 'in_transit') AS total_transit,
                    SUM(et.status = 'pending') AS total_pending,
                    0 AS total_late
                FROM expedisi_tasks et
                JOIN expedisi_vendors ev ON et.vendor_id = ev.id
                WHERE DATE(COALESCE(et.target_date, et.created_at)) BETWEEN ? AND ?
                $vendorFilter
                GROUP BY ev.id, ev.name
                ORDER BY total_completed DESC, total_all DESC
                LIMIT 10";

        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $rows2 = $stmt2->fetchAll();

        foreach ($rows2 as &$row2) {
            $total2 = max((int) $row2['total_all'], 1);
            $row2['progress_pct'] = round(($row2['total_completed'] / $total2) * 100);
            $row2['total_all'] = (int) $row2['total_all'];
            $row2['total_delivery'] = (int) $row2['total_delivery'];
            $row2['total_pickup'] = (int) $row2['total_pickup'];
            $row2['total_completed'] = (int) $row2['total_completed'];
            $row2['total_canceled'] = (int) $row2['total_canceled'];
            $row2['total_transit'] = (int) $row2['total_transit'];
            $row2['total_pending'] = (int) $row2['total_pending'];
            $row2['total_late'] = (int) $row2['total_late'];
        }
        unset($row2);
        echo json_encode($rows2);
        break;

    case 'get_driver_kpi_charts':
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-t');
        $driver_id = $_GET['driver_id'] ?? '';

        $params = [$date_from, $date_to];
        $driverFilter = '';
        if ($driver_id !== '') {
            $driverFilter = " AND d.driver_id = ?";
            $params[] = $driver_id;
        }

        // 1. Total Status Summary
        $sqlSummary = "SELECT 
                           SUM(d.status = 'completed') AS total_completed,
                           SUM(d.status = 'canceled') AS total_canceled,
                           SUM(d.status = 'in_transit' OR d.status = 'pending') AS total_active
                       FROM deliveries d
                       WHERE DATE(COALESCE(d.target_date, d.created_at)) BETWEEN ? AND ?
                       $driverFilter";
        $stmtSummary = $pdo->prepare($sqlSummary);
        $stmtSummary->execute($params);
        $summary = $stmtSummary->fetch();

        // 2. Daily Status
        $sqlDaily = "SELECT 
                          DATE(COALESCE(d.target_date, d.created_at)) AS date_label,
                          SUM(d.status = 'completed') AS total_completed,
                          COUNT(d.id) AS total_all
                      FROM deliveries d
                      WHERE DATE(COALESCE(d.target_date, d.created_at)) BETWEEN ? AND ?
                      $driverFilter
                      GROUP BY date_label
                      ORDER BY date_label ASC";
        $stmtDaily = $pdo->prepare($sqlDaily);
        $stmtDaily->execute($params);
        $daily = $stmtDaily->fetchAll();

        echo json_encode([
            'summary' => [
                'total_completed' => (int) ($summary['total_completed'] ?? 0),
                'total_canceled' => (int) ($summary['total_canceled'] ?? 0),
                'total_active' => (int) ($summary['total_active'] ?? 0)
            ],
            'daily' => array_map(function ($row) {
                return [
                    'date' => $row['date_label'],
                    'completed' => (int) $row['total_completed'],
                    'total' => (int) $row['total_all']
                ];
            }, $daily)
        ]);
        break;

    case 'get_expedisi_kpi_charts':
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-t');
        $vendor_id = $_GET['vendor_id'] ?? '';

        $params = [$date_from, $date_to];
        $vendorFilter = '';
        if ($vendor_id !== '') {
            $vendorFilter = " AND et.vendor_id = ?";
            $params[] = $vendor_id;
        }

        // 1. Total Status Summary
        $sqlSummary = "SELECT 
                           SUM(et.status = 'completed') AS total_completed,
                           SUM(et.status = 'canceled') AS total_canceled,
                           SUM(et.status = 'in_transit' OR et.status = 'pending') AS total_active
                       FROM expedisi_tasks et
                       WHERE DATE(COALESCE(et.target_date, et.created_at)) BETWEEN ? AND ?
                       $vendorFilter";
        $stmtSummary = $pdo->prepare($sqlSummary);
        $stmtSummary->execute($params);
        $summary = $stmtSummary->fetch();

        // 2. Daily Status
        $sqlDaily = "SELECT 
                          DATE(COALESCE(et.target_date, et.created_at)) AS date_label,
                          SUM(et.status = 'completed') AS total_completed,
                          COUNT(et.id) AS total_all
                      FROM expedisi_tasks et
                      WHERE DATE(COALESCE(et.target_date, et.created_at)) BETWEEN ? AND ?
                      $vendorFilter
                      GROUP BY date_label
                      ORDER BY date_label ASC";
        $stmtDaily = $pdo->prepare($sqlDaily);
        $stmtDaily->execute($params);
        $daily = $stmtDaily->fetchAll();

        echo json_encode([
            'summary' => [
                'total_completed' => (int) ($summary['total_completed'] ?? 0),
                'total_canceled' => (int) ($summary['total_canceled'] ?? 0),
                'total_active' => (int) ($summary['total_active'] ?? 0)
            ],
            'daily' => array_map(function ($row) {
                return [
                    'date' => $row['date_label'],
                    'completed' => (int) $row['total_completed'],
                    'total' => (int) $row['total_all']
                ];
            }, $daily)
        ]);
        break;


    case 'get_locations':
        $type = $_GET['type'] ?? null;
        if ($type) {
            $stmt = $pdo->prepare("SELECT * FROM locations WHERE type = ?");
            $stmt->execute([$type]);
            echo json_encode($stmt->fetchAll());
        } else {
            echo json_encode($pdo->query("SELECT * FROM locations")->fetchAll());
        }
        break;

    case 'get_vehicles':
        // A vehicle is considered "In Use" only if:
        // 1. It has an active driver assignment today or driver has active tasks (pending/in_transit)
        $sql = "SELECT v.*, 
                (SELECT u.id FROM users u 
                 JOIN driver_active_vehicles dav ON u.id = dav.driver_id 
                 WHERE dav.vehicle_id = v.id 
                 AND (
                   DATE(dav.assigned_at) = CURDATE() 
                   OR EXISTS (SELECT 1 FROM deliveries d WHERE d.driver_id = dav.driver_id AND d.status IN ('pending', 'in_transit'))
                 )
                 ORDER BY dav.assigned_at DESC LIMIT 1) as current_driver_id,
                (SELECT u.name FROM users u 
                 JOIN driver_active_vehicles dav ON u.id = dav.driver_id 
                 WHERE dav.vehicle_id = v.id 
                 AND (
                   DATE(dav.assigned_at) = CURDATE() 
                   OR EXISTS (SELECT 1 FROM deliveries d WHERE d.driver_id = dav.driver_id AND d.status IN ('pending', 'in_transit'))
                 )
                 ORDER BY dav.assigned_at DESC LIMIT 1) as current_driver_name,
                (SELECT d2.destination_name FROM deliveries d2 
                 JOIN driver_active_vehicles dav2 ON d2.driver_id = dav2.driver_id
                 WHERE dav2.vehicle_id = v.id AND d2.status IN ('pending', 'in_transit') 
                 AND DATE(COALESCE(d2.target_date, d2.created_at)) = CURDATE()
                 ORDER BY dav2.assigned_at DESC, CASE WHEN d2.status = 'in_transit' THEN 0 ELSE 1 END, d2.created_at ASC LIMIT 1) as current_dest,
                (SELECT d2.start_time FROM deliveries d2 
                 JOIN driver_active_vehicles dav3 ON d2.driver_id = dav3.driver_id
                 WHERE dav3.vehicle_id = v.id AND d2.status IN ('pending', 'in_transit') 
                 AND DATE(COALESCE(d2.target_date, d2.created_at)) = CURDATE()
                 ORDER BY dav3.assigned_at DESC, CASE WHEN d2.status = 'in_transit' THEN 0 ELSE 1 END, d2.created_at ASC LIMIT 1) as active_start_time
                FROM vehicles v 
                ORDER BY v.name ASC";
        echo json_encode($pdo->query($sql)->fetchAll());
        break;

    case 'get_vehicles_raw':
        echo json_encode($pdo->query("SELECT * FROM vehicles ORDER BY name ASC")->fetchAll());
        break;

    case 'get_vehicle':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'Not found']);
        break;

    case 'add_vehicle':
        // Admin can add, others need write permission
        if ($_SESSION['role'] !== 'admin') {
            checkWriteAccessApi('vehicles');
        }
        $name = $_POST['name'];
        $plate = $_POST['plate_number'];
        $stmt = $pdo->prepare("INSERT INTO vehicles (name, plate_number) VALUES (?, ?)");
        $stmt->execute([$name, $plate]);
        echo json_encode(['success' => true]);
        break;

    case 'edit_vehicle':
        checkWriteAccessApi('vehicles');
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $plate = $_POST['plate_number'] ?? '';

        if (!$id || !$name || !$plate) {
            die(json_encode(['error' => 'Data tidak lengkap']));
        }

        try {
            $stmt = $pdo->prepare("UPDATE vehicles SET name = ?, plate_number = ? WHERE id = ?");
            $stmt->execute([$name, $plate, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Gagal mengubah: ' . $e->getMessage()]);
        }
        break;

    case 'delete_vehicle':
        checkWriteAccessApi('vehicles');
        $id = $_POST['id'] ?? null;
        if (!$id)
            die(json_encode(['error' => 'ID tidak ditemukan']));

        try {
            $pdo->beginTransaction();
            // Clear current driver associations first to avoid FK constraint errors
            $pdo->prepare("DELETE FROM driver_active_vehicles WHERE vehicle_id = ?")->execute([$id]);
            // Now delete the vehicle
            $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['error' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
        break;

    case 'assign_vehicle':
    case 'assign_driver_vehicle':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        // Driver boleh assign dirinya sendiri, admin/controller boleh assign siapapun
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : $_SESSION['user_id'];
        $vehicle_id = $_POST['vehicle_id'] ?? null;
        if (!$vehicle_id) {
            die(json_encode(['success' => false, 'error' => 'Pilih kendaraan terlebih dahulu']));
        }
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin', 'controller'], true) && (int) $driver_id !== (int) $_SESSION['user_id']) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak: Anda hanya boleh assign kendaraan untuk diri sendiri']));
        }

        // Check if the vehicle is currently occupied by another active driver
        $stmtOcc = $pdo->prepare("SELECT u.name FROM driver_active_vehicles dav 
                                  JOIN users u ON dav.driver_id = u.id 
                                  WHERE dav.vehicle_id = ? AND dav.driver_id != ? 
                                  AND (DATE(dav.assigned_at) = CURDATE() OR EXISTS (SELECT 1 FROM deliveries d WHERE d.driver_id = dav.driver_id AND d.status IN ('pending', 'in_transit')))");
        $stmtOcc->execute([$vehicle_id, $driver_id]);
        $occDriverName = $stmtOcc->fetchColumn();

        if ($occDriverName && !in_array($role, ['admin', 'superadmin', 'controller'], true)) {
            die(json_encode(['success' => false, 'error' => 'Gagal memilih: Kendaraan ini sedang digunakan oleh ' . $occDriverName]));
        }

        try {
            $pdo->beginTransaction();
            // Close any existing open vehicle_logs entry for this driver session
            $pdo->prepare("UPDATE vehicle_logs SET released_at = NOW(), release_notes = 'Ganti kendaraan' WHERE driver_id = ? AND released_at IS NULL")->execute([$driver_id]);

            $pdo->prepare("DELETE FROM driver_active_vehicles WHERE driver_id = ? OR vehicle_id = ?")->execute([$driver_id, $vehicle_id]);
            $pdo->prepare("INSERT INTO driver_active_vehicles (driver_id, vehicle_id, assigned_at) VALUES (?, ?, NOW())")->execute([$driver_id, $vehicle_id]);

            // Create a new vehicle_logs entry
            $pdo->prepare("INSERT INTO vehicle_logs (driver_id, vehicle_id, assigned_at) VALUES (?, ?, NOW())")->execute([$driver_id, $vehicle_id]);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            die(json_encode(['success' => false, 'error' => 'Gagal assign kendaraan: ' . $e->getMessage()]));
        }
        echo json_encode(['success' => true]);
        break;

    case 'release_vehicle':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $driver_id = $_POST['driver_id'] ?? $_SESSION['user_id'];
        // Driver boleh release dirinya sendiri, admin/controller boleh release siapapun
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin', 'controller'], true) && (int) $driver_id !== (int) $_SESSION['user_id']) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak: Anda hanya boleh release kendaraan sendiri']));
        }

        $release_notes = isset($_POST['release_notes']) ? trim($_POST['release_notes']) : null;
        $release_photo = null;

        if (isset($_FILES['release_photo']) && $_FILES['release_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['release_photo']['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $ext = 'jpg';
            }
            $release_photo = 'KEY_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $targetPath = $uploadDir . '/' . $release_photo;
            if (move_uploaded_file($_FILES['release_photo']['tmp_name'], $targetPath)) {
                // Photo saved cleanly without watermark
            }
        }

        try {
            $pdo->beginTransaction();
            // Update active vehicle_log for this session only
            $stmtLog = $pdo->prepare("UPDATE vehicle_logs SET released_at = NOW(), release_photo = COALESCE(?, release_photo), release_notes = COALESCE(?, release_notes), release_by = ? WHERE driver_id = ? AND released_at IS NULL ORDER BY id DESC LIMIT 1");
            $stmtLog->execute([$release_photo, $release_notes, $_SESSION['user_id'], $driver_id]);

            if ($stmtLog->rowCount() === 0 && ($release_photo || $release_notes)) {
                $stmtActiveV = $pdo->prepare("SELECT vehicle_id FROM driver_active_vehicles WHERE driver_id = ?");
                $stmtActiveV->execute([$driver_id]);
                $vId = $stmtActiveV->fetchColumn() ?: 1;
                $stmtInsertLog = $pdo->prepare("INSERT INTO vehicle_logs (driver_id, vehicle_id, assigned_at, released_at, release_photo, release_notes, release_by) VALUES (?, ?, NOW(), NOW(), ?, ?, ?)");
                $stmtInsertLog->execute([$driver_id, $vId, $release_photo, $release_notes, $_SESSION['user_id']]);
            }

            $sql = "DELETE FROM driver_active_vehicles WHERE driver_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$driver_id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Gagal melepaskan kendaraan: ' . $e->getMessage()]);
        }
        break;

    case 'get_active_vehicle':
        $driver_id = $_GET['driver_id'] ?? $_SESSION['user_id'] ?? 0;

        // Auto-release logic check
        try {
            // 1. Get assigned vehicle details
            $stmtCheck = $pdo->prepare("SELECT assigned_at, vehicle_id FROM driver_active_vehicles WHERE driver_id = ?");
            $stmtCheck->execute([$driver_id]);
            $assignedRow = $stmtCheck->fetch();

            if ($assignedRow) {
                $assigned_at = $assignedRow['assigned_at'];

                // 2. Check if driver has active/pending tasks
                $stmtTasks = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status IN ('pending', 'in_transit')");
                $stmtTasks->execute([$driver_id]);
                $activeCount = $stmtTasks->fetchColumn();

                if ($activeCount == 0) {
                    $should_release = false;

                    // Condition 1: Release if assigned on a PREVIOUS day and driver has no active tasks today
                    if (date('Y-m-d', strtotime($assigned_at)) < date('Y-m-d')) {
                        $should_release = true;
                    }

                    // Condition 2: Release at 20:00 (8 PM) or later if no active tasks
                    $current_hour = (int) date('H');
                    if ($current_hour >= 20) {
                        $should_release = true;
                    }

                    if ($should_release) {
                        $pdo->prepare("UPDATE vehicle_logs SET released_at = NOW(), release_notes = 'Otomatis dilepas sistem' WHERE driver_id = ? AND released_at IS NULL")->execute([$driver_id]);
                        $pdo->prepare("DELETE FROM driver_active_vehicles WHERE driver_id = ?")->execute([$driver_id]);
                        logActivity('auto_release_vehicle', "Sistem otomatis melepas kendaraan ID " . $assignedRow['vehicle_id'] . " karena tidak ada tugas.");
                    }
                }
            }
        } catch (Exception $e) {
            // Silently ignore errors during auto-release check
        }

        $sql = "SELECT dav.*, v.name as vehicle_name, v.plate_number 
                FROM driver_active_vehicles dav 
                JOIN vehicles v ON dav.vehicle_id = v.id 
                WHERE dav.driver_id = ? 
                ORDER BY dav.assigned_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driver_id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'No vehicle assigned']);
        break;

    case 'get_driver_summary':
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        $dateFilter = "DATE(COALESCE(d.target_date, d.created_at)) = CURDATE()";
        $params = [];
        if ($date_from && $date_to) {
            $dateFilter = "DATE(COALESCE(d.target_date, d.created_at)) BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        } elseif ($date_from) {
            $dateFilter = "DATE(COALESCE(d.target_date, d.created_at)) >= ?";
            $params[] = $date_from;
        } elseif ($date_to) {
            $dateFilter = "DATE(COALESCE(d.target_date, d.created_at)) <= ?";
            $params[] = $date_to;
        }

        $sql = "SELECT 
                    u.id as user_id, 
                    u.name as driver_name,
                    v.plate_number as vehicle_plate,
                    v.name as vehicle_name,
                    COALESCE(NULLIF(d.task_type, ''), 'antar') as task_type,
                    d.status as task_status,
                    d.destination_name as current_dest,
                    d.start_time as active_start_time,
                    d.id as task_id,
                    d.surat_jalan,
                    d.end_time as task_end_time,
                    d.duration as task_duration,
                    d.receiver_name as passenger_name,
                    COALESCE(d.total_koli, 0) as total_koli
                FROM users u 
                INNER JOIN deliveries d ON u.id = d.driver_id AND ($dateFilter)
                LEFT JOIN driver_active_vehicles dav ON u.id = dav.driver_id
                LEFT JOIN vehicles v ON (
                    CASE 
                        WHEN d.vehicle_id IS NOT NULL THEN d.vehicle_id = v.id 
                        WHEN dav.vehicle_id IS NOT NULL THEN dav.vehicle_id = v.id 
                        ELSE v.id = (
                            SELECT vl.vehicle_id FROM vehicle_logs vl 
                            WHERE vl.driver_id = d.driver_id 
                            ORDER BY vl.id DESC LIMIT 1
                        ) 
                    END
                )
                WHERE u.role = 'driver'
                ORDER BY CASE WHEN d.status = 'pending' THEN 0 WHEN d.status = 'in_transit' THEN 1 WHEN d.status = 'completed' THEN 2 WHEN d.status = 'canceled' THEN 3 ELSE 4 END ASC, u.name ASC, d.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_vehicle_reports':
        if (!canAccessMenu('report_vehicles') && !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $driver_id = $_GET['driver_id'] ?? '';
        $vehicle_id = $_GET['vehicle_id'] ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "SELECT vl.*, d.name AS driver_name, v.name AS vehicle_name, v.plate_number, u.name AS release_by_name 
                FROM vehicle_logs vl 
                JOIN users d ON vl.driver_id = d.id 
                JOIN vehicles v ON vl.vehicle_id = v.id 
                LEFT JOIN users u ON vl.release_by = u.id 
                WHERE DATE(vl.assigned_at) BETWEEN ? AND ?";
        $params = [$date_from, $date_to];

        if (!empty($driver_id)) {
            $sql .= " AND vl.driver_id = ?";
            $params[] = $driver_id;
        }
        if (!empty($vehicle_id)) {
            $sql .= " AND vl.vehicle_id = ?";
            $params[] = $vehicle_id;
        }
        if ($status === 'active') {
            $sql .= " AND vl.released_at IS NULL";
        } elseif ($status === 'released') {
            $sql .= " AND vl.released_at IS NOT NULL";
        }

        $sql .= " ORDER BY vl.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($rows);
        break;

    case 'edit_vehicle_log':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('report_vehicles');
        }
        $id = $_POST['id'] ?? '';
        $assigned_at = $_POST['assigned_at'] ?? '';
        $released_at = $_POST['released_at'] ?? null;
        $release_notes = $_POST['release_notes'] ?? null;

        if (!$id || !$assigned_at) {
            die(json_encode(['success' => false, 'error' => 'ID dan Waktu Pakai wajib diisi']));
        }

        try {
            $sql = "UPDATE vehicle_logs SET assigned_at = ?, released_at = ?, release_notes = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $assigned_at,
                ($released_at === '' ? null : $released_at),
                $release_notes,
                $id
            ]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal memperbarui data: ' . $e->getMessage()]);
        }
        break;

    case 'delete_vehicle_log':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('report_vehicles');
        }
        $id = $_POST['id'] ?? '';
        if (!$id) {
            die(json_encode(['success' => false, 'error' => 'ID tidak ditemukan']));
        }

        try {
            $pdo->prepare("DELETE FROM vehicle_logs WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
        break;

    case 'get_expedisi_summary':
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        $dateFilter = "DATE(COALESCE(e.target_date, e.created_at)) = CURDATE()";
        $params = [];
        if ($date_from && $date_to) {
            $dateFilter = "DATE(COALESCE(e.target_date, e.created_at)) BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        } elseif ($date_from) {
            $dateFilter = "DATE(COALESCE(e.target_date, e.created_at)) >= ?";
            $params[] = $date_from;
        } elseif ($date_to) {
            $dateFilter = "DATE(COALESCE(e.target_date, e.created_at)) <= ?";
            $params[] = $date_to;
        }

        try {
            $sql = "SELECT 
                        e.id, 
                        e.surat_jalan,
                        ev.name as vendor_name,
                        e.driver_name,
                        e.vehicle_plate,
                        e.type as task_type,
                        e.status as task_status,
                        e.destination_name as current_dest,
                        e.start_time as active_start_time,
                        e.end_time as task_end_time,
                        COALESCE(e.total_koli, 0) as total_koli,
                        CASE 
                            WHEN e.end_time IS NOT NULL AND e.start_time IS NOT NULL
                            THEN CONCAT(
                                FLOOR(TIMESTAMPDIFF(MINUTE, e.start_time, e.end_time) / 60), ' Jam ',
                                MOD(TIMESTAMPDIFF(MINUTE, e.start_time, e.end_time), 60), ' Menit'
                            )
                            ELSE NULL
                        END as task_duration
                    FROM expedisi_tasks e
                    LEFT JOIN expedisi_vendors ev ON e.vendor_id = ev.id
                    WHERE (
                        $dateFilter
                        OR e.status NOT IN ('completed', 'canceled')
                    )
                    ORDER BY CASE WHEN e.status = 'pending' THEN 0 WHEN e.status = 'in_transit' THEN 1 WHEN e.status = 'completed' THEN 2 WHEN e.status = 'canceled' THEN 3 ELSE 4 END ASC, e.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $ex) {
            echo json_encode(['error' => $ex->getMessage()]);
        }
        break;

    case 'get_delivery':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $id = $_GET['id'];
        $sql = "SELECT d.*, u.name as driver_name, creator.name as creator_name 
                FROM deliveries d 
                JOIN users u ON d.driver_id = u.id 
                LEFT JOIN users creator ON d.created_by = creator.id
                WHERE d.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row ?: ['error' => 'Not found']);
        break;

    case 'edit_delivery':
        if (!canWriteMenu('assign_tasks')) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        $id = $_POST['id'];

        // Enforce that only pending tasks can be edited, unless superadmin/controller
        $stmtStatus = $pdo->prepare("SELECT status FROM deliveries WHERE id = ?");
        $stmtStatus->execute([$id]);
        $currentStatus = $stmtStatus->fetchColumn();
        $isSuperAdmin = (($_SESSION['role'] ?? '') === 'superadmin' || ($_SESSION['role'] ?? '') === 'controller');
        if (!$isSuperAdmin && $currentStatus !== 'pending') {
            die(json_encode(['success' => false, 'error' => 'Hanya tugas dengan status PENDING yang dapat diubah.']));
        }

        $driver_id = $_POST['driver_id'];
        $origin_id = !empty($_POST['origin_id']) ? $_POST['origin_id'] : null;
        $dest_id = !empty($_POST['dest_id']) ? $_POST['dest_id'] : null;
        $origin_name = $_POST['origin_name'];
        $origin_lat = !empty($_POST['origin_lat']) ? $_POST['origin_lat'] : null;
        $origin_lng = !empty($_POST['origin_lng']) ? $_POST['origin_lng'] : null;
        $dest_name = $_POST['dest_name'];
        $dest_lat = !empty($_POST['dest_lat']) ? $_POST['dest_lat'] : null;
        $dest_lng = !empty($_POST['dest_lng']) ? $_POST['dest_lng'] : null;
        $task_type = $_POST['task_type'];
        $status = $_POST['status'];
        if (!in_array($status, ['pending', 'in_transit', 'completed', 'canceled'], true)) {
            die(json_encode(['success' => false, 'error' => 'Status tidak valid']));
        }
        $target_date = $_POST['target_date'] ?? date('Y-m-d H:i:s');
        $target_date = str_replace('T', ' ', $target_date);

        $surat_jalan = isset($_POST['surat_jalan']) ? strtoupper(trim($_POST['surat_jalan'])) : null;
        $total_koli = $_POST['total_koli'] ?? 0;
        $notes = isset($_POST['notes']) ? strtoupper(trim($_POST['notes'])) : null;
        $receiver_name = isset($_POST['receiver_name']) ? strtoupper(trim($_POST['receiver_name'])) : null;

        // Process Surat Jalan File
        $surat_jalan_files = [];
        if (isset($_POST['existing_surat_jalan_file'])) {
            $parsed = json_decode($_POST['existing_surat_jalan_file'], true);
            if (is_array($parsed)) {
                $surat_jalan_files = $parsed;
            } elseif (!empty($_POST['existing_surat_jalan_file'])) {
                $surat_jalan_files = [$_POST['existing_surat_jalan_file']];
            }
        }
        if (isset($_FILES['surat_jalan_file'])) {
            $files = $_FILES['surat_jalan_file'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'SJ_' . time() . '_' . uniqid() . '.' . $ext;
                            if (!is_dir('uploads'))
                                mkdir('uploads', 0777, true);
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $surat_jalan_files[] = $fname;
                            }
                        }
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name']);
                    if ($ext) {
                        $tmp_name = $files['tmp_name'];
                        $fname = 'SJ_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                            $surat_jalan_files[] = $fname;
                        }
                    }
                }
            }
        }
        $surat_jalan_file_json = !empty($surat_jalan_files) ? json_encode(array_values($surat_jalan_files)) : null;

        // Process Goods File
        $goods_files = [];
        if (isset($_POST['existing_goods_file'])) {
            $parsed = json_decode($_POST['existing_goods_file'], true);
            if (is_array($parsed)) {
                $goods_files = $parsed;
            } elseif (!empty($_POST['existing_goods_file'])) {
                $goods_files = [$_POST['existing_goods_file']];
            }
        }
        if (isset($_FILES['goods_file'])) {
            $files = $_FILES['goods_file'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'GDS_' . time() . '_' . uniqid() . '.' . $ext;
                            if (!is_dir('uploads'))
                                mkdir('uploads', 0777, true);
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $goods_files[] = $fname;
                            }
                        }
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name']);
                    if ($ext) {
                        $tmp_name = $files['tmp_name'];
                        $fname = 'GDS_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                            $goods_files[] = $fname;
                        }
                    }
                }
            }
        }
        $goods_file_json = !empty($goods_files) ? json_encode(array_values($goods_files)) : null;

        // Process Proof File
        $proof_files = [];
        if (isset($_POST['existing_proof_file'])) {
            $parsed = json_decode($_POST['existing_proof_file'], true);
            if (is_array($parsed)) {
                $proof_files = $parsed;
            } elseif (!empty($_POST['existing_proof_file'])) {
                $proof_files = [$_POST['existing_proof_file']];
            }
        }
        if (isset($_FILES['proof_file'])) {
            $files = $_FILES['proof_file'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if ($ext) {
                            $tmp_name = $files['tmp_name'][$i];
                            $fname = 'PRF_' . time() . '_' . uniqid() . '.' . $ext;
                            if (!is_dir('uploads'))
                                mkdir('uploads', 0777, true);
                            if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                                $proof_files[] = $fname;
                            }
                        }
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name']);
                    if ($ext) {
                        $tmp_name = $files['tmp_name'];
                        $fname = 'PRF_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname)) {
                            $proof_files[] = $fname;
                        }
                    }
                }
            }
        }
        $proof_file_json = !empty($proof_files) ? json_encode(array_values($proof_files)) : null;

        $pdo->prepare("UPDATE deliveries SET driver_id=?, origin_id=?, destination_id=?, origin_name=?, origin_lat=?, origin_lng=?, destination_name=?, destination_lat=?, destination_lng=?, task_type=?, target_date=?, status=?, surat_jalan=?, total_koli=?, notes=?, receiver_name=?, surat_jalan_file=?, goods_file=?, proof_file=? WHERE id=?")
            ->execute([$driver_id, $origin_id, $dest_id, $origin_name, $origin_lat, $origin_lng, $dest_name, $dest_lat, $dest_lng, $task_type, $target_date, $status, $surat_jalan, $total_koli, $notes, $receiver_name, $surat_jalan_file_json, $goods_file_json, $proof_file_json, $id]);

        // Sync pickup_request status if completed
        if ($status === 'completed') {
            $stmtP = $pdo->prepare("SELECT pickup_id FROM deliveries WHERE id = ?");
            $stmtP->execute([$id]);
            $dData = $stmtP->fetch();
            if ($dData && $dData['pickup_id']) {
                $pdo->prepare("UPDATE pickup_requests SET status = 'completed' WHERE id = ?")->execute([$dData['pickup_id']]);
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'quick_edit_delivery':
        if (!canWriteMenu('assign_tasks') && $_SESSION['role'] !== 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        $id = $_POST['id'];
        $field = $_POST['field'];

        // Enforce that only pending tasks can be quick edited (except for reassign on canceled tasks)
        if ($field !== 'reassign') {
            $stmtStatus = $pdo->prepare("SELECT status FROM deliveries WHERE id = ?");
            $stmtStatus->execute([$id]);
            $currentStatus = $stmtStatus->fetchColumn();
            if ($currentStatus !== 'pending') {
                die(json_encode(['success' => false, 'error' => 'Hanya tugas dengan status PENDING yang dapat diubah.']));
            }
        }

        if ($field === 'driver') {
            $driver_id = $_POST['driver_id'];
            $target_date = $_POST['target_date'];
            $target_date = str_replace('T', ' ', $target_date);
            $pdo->prepare("UPDATE deliveries SET driver_id = ?, target_date = ? WHERE id = ?")
                ->execute([$driver_id, $target_date, $id]);
        } else if ($field === 'reassign') {
            $driver_id = $_POST['driver_id'];
            $target_date = $_POST['target_date'];
            $target_date = str_replace('T', ' ', $target_date);
            $status = $_POST['status'] ?? 'pending';

            // Ambil data asli untuk di-clone
            $stmt = $pdo->prepare("SELECT * FROM deliveries WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($original) {
                $sql = "INSERT INTO deliveries (
                    driver_id, pickup_id, origin_id, destination_id, origin_name, origin_lat, origin_lng, destination_name, 
                    destination_lat, destination_lng, surat_jalan, total_koli, notes, 
                    surat_jalan_file, goods_file, task_type, target_date, created_by, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $pdo->prepare($sql)->execute([
                    $driver_id,
                    $original['pickup_id'],
                    $original['origin_id'],
                    $original['destination_id'],
                    $original['origin_name'],
                    $original['origin_lat'],
                    $original['origin_lng'],
                    $original['destination_name'],
                    $original['destination_lat'],
                    $original['destination_lng'],
                    $original['surat_jalan'],
                    $original['total_koli'],
                    $original['notes'],
                    $original['surat_jalan_file'],
                    $original['goods_file'],
                    $original['task_type'],
                    $target_date,
                    $_SESSION['user_id'],
                    $status
                ]);
            }
        } else if ($field === 'route') {
            $origin_id = !empty($_POST['origin_id']) ? $_POST['origin_id'] : null;
            $dest_id = !empty($_POST['dest_id']) ? $_POST['dest_id'] : null;
            $origin_name = $_POST['origin_name'];
            $origin_lat = !empty($_POST['origin_lat']) ? $_POST['origin_lat'] : null;
            $origin_lng = !empty($_POST['origin_lng']) ? $_POST['origin_lng'] : null;
            $dest_name = $_POST['dest_name'];
            $dest_lat = !empty($_POST['dest_lat']) ? $_POST['dest_lat'] : null;
            $dest_lng = !empty($_POST['dest_lng']) ? $_POST['dest_lng'] : null;
            $pdo->prepare("UPDATE deliveries SET origin_id = ?, destination_id = ?, origin_name = ?, origin_lat = ?, origin_lng = ?, destination_name = ?, destination_lat = ?, destination_lng = ? WHERE id = ?")
                ->execute([$origin_id, $dest_id, $origin_name, $origin_lat, $origin_lng, $dest_name, $dest_lat, $dest_lng, $id]);
        } else if ($field === 'sj') {
            $surat_jalan = isset($_POST['surat_jalan']) ? strtoupper(trim($_POST['surat_jalan'])) : '';
            $total_koli = $_POST['total_koli'];
            $pdo->prepare("UPDATE deliveries SET surat_jalan = ?, total_koli = ? WHERE id = ?")
                ->execute([$surat_jalan, $total_koli, $id]);
        }

        echo json_encode(['success' => true]);
        break;

    case 'delete_delivery':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('assign_tasks');
        }
        $id = $_POST['id'];

        // Bersihkan file upload sebelum delete
        $stmtFiles = $pdo->prepare("SELECT surat_jalan_file, goods_file, proof_file FROM deliveries WHERE id = ?");
        $stmtFiles->execute([$id]);
        $fileRow = $stmtFiles->fetch(PDO::FETCH_ASSOC);
        if ($fileRow) {
            foreach (['surat_jalan_file', 'goods_file', 'proof_file'] as $col) {
                $raw = $fileRow[$col] ?? '';
                if (!$raw)
                    continue;
                $list = json_decode($raw, true);
                if (!is_array($list))
                    $list = [$raw];
                foreach ($list as $fname) {
                    if (!is_string($fname) || $fname === '')
                        continue;
                    $safe = basename($fname);
                    $filePath = 'uploads/' . $safe;
                    if (file_exists($filePath))
                        @unlink($filePath);
                }
            }
        }

        $pdo->prepare("DELETE FROM deliveries WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'register':
        checkWriteAccessApi('users');
        // Hanya Super Admin yang bisa membuat Super Admin baru
        if ($_POST['role'] === 'controller' && $_SESSION['role'] !== 'controller') {
            die(json_encode(['error' => 'Akses ditolak: Hanya Super Admin yang bisa membuat akun Super Admin baru']));
        }
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone_number'] ?? '');

        if (empty($name) || empty($username) || empty($role) || empty($password)) {
            die(json_encode(['error' => 'Gagal: Data tidak lengkap']));
        }

        if (preg_match('/\s/', $username)) {
            die(json_encode(['error' => 'Gagal: Username tidak boleh mengandung spasi']));
        }

        // Cek apakah username sudah terpakai di database (termasuk yang tersembunyi seperti superadmin/controller)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            die(json_encode(['error' => 'Username sudah terpakai. Silakan gunakan username lain.']));
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Default expiry: 3 months for regular, NULL for controller (superadmin)
        $expires_at = ($role === 'controller') ? null : date('Y-m-d', strtotime('+3 months'));

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $name, $role, $expires_at]);
            $user_id = $pdo->lastInsertId();

            if ($phone !== '') {
                $stmt2 = $pdo->prepare("INSERT INTO drivers_info (user_id, phone_number) VALUES (?, ?)");
                $stmt2->execute([$user_id, $phone]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['error' => 'Gagal menyimpan data pengguna: ' . $e->getMessage()]);
        }
        break;

    case 'get_all_users':
        $role = $_GET['role'] ?? null;
        $search = $_GET['search'] ?? null;
        $currentUserRole = $_SESSION['role'] ?? '';

        $sql = "SELECT u.id, u.username, u.name, u.role, u.expires_at, u.is_active, u.created_at, di.phone_number 
                FROM users u 
                LEFT JOIN drivers_info di ON u.id = di.user_id";

        $conditions = [];
        $params = [];

        // Hide superadmins if current user is not a superadmin
        if ($currentUserRole !== 'superadmin' && $currentUserRole !== 'controller') {
            $conditions[] = "u.role != 'superadmin' AND u.role != 'controller'";
        }

        if ($role) {
            $conditions[] = "u.role = ?";
            $params[] = $role;
        }

        if ($search) {
            $conditions[] = "(u.username LIKE ? OR u.name LIKE ? OR di.phone_number LIKE ?)";
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'update_user':
        $id = $_POST['id'];
        checkWriteAccessApi('users');

        // Ambil data user yang akan diubah untuk pengecekan role
        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $targetUserRole = $check->fetchColumn();

        // Jika user yang diubah adalah Super Admin, atau role baru yang dipilih adalah Super Admin
        // Maka hanya boleh dilakukan oleh Super Admin (controller)
        $newRole = $_POST['role'] ?? null;
        if (($targetUserRole === 'controller' || $newRole === 'controller') && $_SESSION['role'] !== 'controller') {
            die(json_encode(['error' => 'Akses ditolak: Hanya Super Admin yang bisa mengubah atau memberikan akses Super Admin']));
        }
        $name = $_POST['name'];
        $role = $_POST['role'] ?? null;
        $phone = $_POST['phone_number'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;

        try {
            $pdo->beginTransaction();
            $sql = "UPDATE users SET name = ?" . ($role ? ", role = ?" : "") . ($expires_at !== null ? ", expires_at = ?" : "") . " WHERE id = ?";
            $params = [$name];
            if ($role)
                $params[] = $role;
            if ($expires_at !== null)
                $params[] = ($expires_at === '' ? null : $expires_at);
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);

            if ($phone !== null) {
                $pdo->prepare("INSERT INTO drivers_info (user_id, phone_number) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE phone_number = VALUES(phone_number)")->execute([$id, $phone]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['error' => 'Gagal memperbarui data']);
        }
        break;

    case 'toggle_user_status':
        checkWriteAccessApi('users');
        $id = $_POST['id'];
        $status = $_POST['status']; // 1 or 0

        // Prevent disabling self
        if ($id == $_SESSION['user_id']) {
            die(json_encode(['error' => 'Tidak bisa menonaktifkan akun sendiri']));
        }

        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $targetUserRole = $check->fetchColumn();

        if ($targetUserRole === 'controller' && $_SESSION['role'] !== 'controller') {
            die(json_encode(['error' => 'Akses ditolak: Hanya Super Admin yang bisa mengubah status Super Admin lainnya']));
        }

        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$status, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_user':
        checkWriteAccessApi('users');
        $id = $_POST['id'];
        if ($id == $_SESSION['user_id'])
            die(json_encode(['error' => 'Cannot delete self']));

        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $targetUserRole = $check->fetchColumn();

        if ($targetUserRole === 'controller' && $_SESSION['role'] !== 'controller') {
            die(json_encode(['error' => 'Akses ditolak: Hanya Super Admin yang bisa menghapus akun Super Admin']));
        }
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'change_password':
        checkWriteAccessApi('users');
        $id = $_POST['id'];

        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $targetUserRole = $check->fetchColumn();

        if ($targetUserRole === 'controller' && $_SESSION['role'] !== 'controller') {
            die(json_encode(['error' => 'Akses ditolak: Hanya Super Admin yang bisa mengubah password Super Admin lainnya']));
        }
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        try {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_password, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Gagal mengubah password']);
        }
        break;

    case 'bulk_delete_users':
        checkWriteAccessApi('users');
        $ids = $_POST['ids'] ?? [];
        if (empty($ids))
            die(json_encode(['error' => 'Tidak ada pengguna yang dipilih']));
        if (!is_array($ids))
            $ids = explode(',', $ids);

        // Filter out self
        $ids = array_filter($ids, function ($id) {
            return $id != $_SESSION['user_id'];
        });
        if (empty($ids))
            die(json_encode(['error' => 'Tidak bisa menghapus diri sendiri']));

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute(array_values($ids));
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Gagal menghapus beberapa pengguna: ' . $e->getMessage()]);
        }
        break;

    case 'bulk_extend_users':
        checkWriteAccessApi('users');
        $ids = $_POST['ids'] ?? [];
        $days = (int) ($_POST['days'] ?? 30);
        if (empty($ids))
            die(json_encode(['error' => 'Tidak ada pengguna yang dipilih']));
        if (!is_array($ids))
            $ids = explode(',', $ids);

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            // Extend masa aktif 30 hari dari SEKARANG
            $sql = "UPDATE users SET expires_at = DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE id IN ($placeholders)";
            $params = array_merge([$days], array_values($ids));
            $pdo->prepare($sql)->execute($params);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Gagal memperpanjang masa aktif: ' . $e->getMessage()]);
        }
        break;

    case 'add_location':
        if ($_SESSION['role'] !== 'admin') {
            checkWriteAccessApi('locations');
        }
        $name = $_POST['name'];
        $type = $_POST['type'];
        $city = $_POST['city'];
        $address = $_POST['address'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];

        $sql = "INSERT INTO locations (name, type, city, address, lat, lng) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $type, $city, $address, $lat, $lng]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_location':
        if ($_SESSION['role'] === 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Admin tidak dapat menghapus)']));
        }
        checkWriteAccessApi('locations');
        $id_param = $_POST['ids'] ?? $_POST['id'] ?? '';
        if (is_array($id_param)) {
            $ids = array_map('intval', array_filter($id_param, function ($v) {
                return is_numeric($v) && $v > 0; }));
        } else {
            $ids = array_map('intval', array_filter(explode(',', $id_param), function ($v) {
                return is_numeric($v) && $v > 0; }));
        }
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM locations WHERE id IN ($placeholders)")->execute($ids);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Gagal menghapus lokasi terpilih. Lokasi mungkin sedang digunakan dalam data transaksi/pengiriman.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Tidak ada lokasi yang dipilih untuk dihapus.']);
        }
        break;

    case 'get_location':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
        $stmt->execute([$id]);
        $loc = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($loc ?: ['error' => 'Not found']);
        break;

    case 'edit_location':
        if ($_SESSION['role'] === 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Admin tidak dapat mengedit)']));
        }
        checkWriteAccessApi('locations');
        $id = $_POST['id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        $city = $_POST['city'];
        $address = $_POST['address'] ?? '';
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $pdo->prepare("UPDATE locations SET name=?, type=?, city=?, address=?, lat=?, lng=? WHERE id=?")
            ->execute([$name, $type, $city, $address, $lat, $lng, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_location_types':
        try {
            $rows = $pdo->query("SELECT * FROM location_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(is_array($rows) ? $rows : []);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        break;

    case 'add_location_type':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('locations');
        }
        $type_key = trim($_POST['type_key'] ?? '');
        $type_name = trim($_POST['type_name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'location_on');
        $color = trim($_POST['color'] ?? '#6366f1');

        if (!$type_key || !$type_name) {
            die(json_encode(['success' => false, 'error' => 'Key dan nama tipe wajib diisi']));
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO location_types (type_key, type_name, icon, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type_key, $type_name, $icon, $color]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal menambah tipe lokasi: ' . $e->getMessage()]);
        }
        break;

    case 'edit_location_type':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('locations');
        }
        $id = $_POST['id'] ?? null;
        $type_name = trim($_POST['type_name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'location_on');
        $color = trim($_POST['color'] ?? '#6366f1');

        if (!$id || !$type_name) {
            die(json_encode(['success' => false, 'error' => 'ID dan nama tipe wajib diisi']));
        }

        try {
            $stmt = $pdo->prepare("UPDATE location_types SET type_name = ?, icon = ?, color = ? WHERE id = ?");
            $stmt->execute([$type_name, $icon, $color, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal mengedit tipe lokasi: ' . $e->getMessage()]);
        }
        break;

    case 'delete_location_type':
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'controller'], true)) {
            checkWriteAccessApi('locations');
        }
        $id = $_POST['id'] ?? null;
        if (!$id) {
            die(json_encode(['success' => false, 'error' => 'ID wajib diisi']));
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM location_types WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal menghapus tipe lokasi: ' . $e->getMessage()]);
        }
        break;

    case 'resolve_maps_link':
        $url = trim($_POST['url'] ?? '');
        if (empty($url)) {
            echo json_encode(['error' => 'URL kosong']);
            break;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            // WARNING: SSL verification dinonaktifkan. Aman untuk development/XAMPP,
            // tapi HARUS diaktifkan (true) + set CURLOPT_CAINFO di environment production.
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        // curl_close is no longer needed since PHP 8.0 as $ch is an object that auto-closes when unset

        $lat = null;
        $lng = null;

        // Pattern 1: /@lat,lng,zoom in URL path
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        }
        // Pattern 2: ?q=lat,lng or &ll=lat,lng
        if (!$lat && preg_match('/[?&](?:q|ll)=(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        }
        // Pattern 3: extract from HTML body (data-coords)
        if (!$lat && preg_match('/"(-?\d{1,3}\.\d{5,}),(-?\d{1,3}\.\d{5,})"/', $body, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        }

        if ($lat && $lng) {
            echo json_encode(['success' => true, 'lat' => (float) $lat, 'lng' => (float) $lng]);
        } else {
            echo json_encode(['error' => 'Koordinat tidak ditemukan. Coba buka link di browser dan salin koordinat dari URL (setelah tanda @).']);
        }
        break;

    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if (empty($username) || empty($password)) {
            die(json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']));
        }

        // Rate Limiting Check (Max 5 failed attempts per 15 minutes)
        try {
            $stmtRate = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR username = ?) AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmtRate->execute([$ip_address, $username]);
            $failedAttempts = $stmtRate->fetchColumn();
            if ($failedAttempts >= 5) {
                logActivity('LOGIN_BLOCKED', "Terlalu banyak percobaan login gagal untuk Username: $username (IP: $ip_address)");
                die(json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan login gagal. Akun/IP dibatasi selama 15 menit untuk alasan keamanan.']));
            }
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check status
            if (!$user['is_active']) {
                logActivity('LOGIN_FAILED', 'Gagal login: Akun dinonaktifkan (Username: ' . $username . ')');
                die(json_encode(['success' => false, 'message' => 'Akun Anda dinonaktifkan (Inactive). Silakan hubungi Admin.']));
            }

            // Check expiry
            if ($user['expires_at'] && strtotime($user['expires_at']) < time()) {
                logActivity('LOGIN_FAILED', 'Gagal login: Akun kadaluarsa (Username: ' . $username . ')');
                die(json_encode(['success' => false, 'message' => 'Akun Anda sudah kadaluarsa (Expired). Silakan hubungi Management untuk perpanjangan.']));
            }

            // Check global system license active period (Bypass for Superadmin daniel and authorized License Managers)
            if (!canManageSystemLicenseByUsername($username)) {
                try {
                    $stmtLic = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_active_until'");
                    $licUntil = $stmtLic ? $stmtLic->fetchColumn() : null;
                    if ($licUntil && strtotime($licUntil) < time()) {
                        logActivity('LOGIN_FAILED', 'Gagal login: Masa aktif sistem web berakhir (Username: ' . $username . ')');
                        die(json_encode([
                            'success' => false,
                            'system_expired' => true,
                            'redirect' => 'expired',
                            'message' => 'Masa aktif pemakaian aplikasi web ini telah berakhir. Silakan hubungi Management / System Administrator.'
                        ]));
                    }
                } catch (Exception $e) {}
            }

            // Clear failed attempts on successful login
            try {
                $stmtClear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?");
                $stmtClear->execute([$ip_address, $username]);
            } catch (Exception $e) {}

            // Prevent Session Fixation Attack
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Cek apakah role ini punya akses ke Dashboard (WEB) atau Mobile Menu
            $is_web = canAccessMenu('dashboard');
            $is_mobile = canAccessMenu('mobile_menu');

            logActivity('LOGIN', 'User berhasil login (Username: ' . $username . ')');

            echo json_encode([
                'success' => true,
                'role' => $user['role'],
                'is_web' => $is_web,
                'is_mobile' => $is_mobile
            ]);
        } else {
            // Record failed attempt
            try {
                $stmtFailed = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
                $stmtFailed->execute([$ip_address, $username]);
            } catch (Exception $e) {}

            logActivity('LOGIN_FAILED', 'Gagal login: Username atau password salah (Username: ' . $username . ')');
            echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
        }
        break;

    case 'get_my_profile':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['error' => 'Tidak terautentikasi']));
        }
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.name, u.role, u.created_at, d.phone_number 
                               FROM users u 
                               LEFT JOIN drivers_info d ON u.id = d.user_id 
                               WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get Today's Pickup Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pickup_requests WHERE requester_id = ? AND DATE(created_at) = CURDATE()");
        $countStmt->execute([$user_id]);
        $profile['today_count'] = $countStmt->fetchColumn();

        // Calculate Performance (%) - Success Rate of requests
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM pickup_requests WHERE requester_id = ?");
        $totalStmt->execute([$user_id]);
        $total = $totalStmt->fetchColumn();

        if ($total > 0) {
            $doneStmt = $pdo->prepare("SELECT COUNT(*) FROM pickup_requests WHERE requester_id = ? AND status = 'completed'");
            $doneStmt->execute([$user_id]);
            $done = $doneStmt->fetchColumn();
            $profile['performance'] = round(($done / $total) * 100);
        } else {
            $profile['performance'] = 100; // Default to 100 if no requests yet
        }

        echo json_encode($profile ?: ['error' => 'User tidak ditemukan']);
        break;


    case 'request_pickup':
        $surat_jalan = $_POST['surat_jalan'];
        $origin_name = $_POST['origin_name'];
        $destination_name = $_POST['destination_name'];
        $total_koli = $_POST['total_koli'] ?? 0;
        $notes = $_POST['notes'] ?? null;
        $scheduled_date = $_POST['scheduled_date'] ?? date('Y-m-d H:i:s');
        $scheduled_date = str_replace('T', ' ', $scheduled_date);
        $requester_id = $_SESSION['user_id'];

        $filenames_sj = [];
        if (isset($_FILES['surat_jalan_files'])) {
            $files = $_FILES['surat_jalan_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name'][$i]);
                    if (!$ext)
                        continue;
                    $tmp_name = $files['tmp_name'][$i];
                    $fname = 'SJ_' . time() . '_' . uniqid() . '.' . $ext;
                    if (!is_dir('uploads'))
                        mkdir('uploads', 0777, true);
                    if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                        $filenames_sj[] = $fname;
                }
            }
        }

        $filenames_goods = [];
        if (isset($_FILES['goods_files'])) {
            $files = $_FILES['goods_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name'][$i]);
                    if (!$ext)
                        continue;
                    $tmp_name = $files['tmp_name'][$i];
                    $fname = 'GOODS_' . time() . '_' . uniqid() . '.' . $ext;
                    if (!is_dir('uploads'))
                        mkdir('uploads', 0777, true);
                    if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                        $filenames_goods[] = $fname;
                }
            }
        }

        $sj_file_json = !empty($filenames_sj) ? json_encode($filenames_sj) : null;
        $goods_file_json = !empty($filenames_goods) ? json_encode($filenames_goods) : null;

        $sql = "INSERT INTO pickup_requests (surat_jalan, origin_name, destination_name, total_koli, notes, surat_jalan_file, goods_file, scheduled_date, requester_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surat_jalan, $origin_name, $destination_name, $total_koli, $notes, $sj_file_json, $goods_file_json, $scheduled_date, $requester_id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_pickup_requests':
        $sql = "SELECT p.*, u.name as requester_name,
                (SELECT GROUP_CONCAT(CONCAT(u2.name, ':', d.status) SEPARATOR '|') 
                 FROM deliveries d 
                 JOIN users u2 ON d.driver_id = u2.id 
                 WHERE d.pickup_id = p.id) as assigned_drivers,
                (SELECT d2.receiver_name 
                 FROM deliveries d2 
                 WHERE d2.pickup_id = p.id AND d2.receiver_name IS NOT NULL 
                 ORDER BY d2.end_time DESC LIMIT 1) as receiver_name
                FROM pickup_requests p 
                JOIN users u ON p.requester_id = u.id";

        $conditions = [];
        $params = [];

        if ($_SESSION['role'] === 'request_pickup' || isset($_GET['my_requests_only'])) {
            $conditions[] = "p.requester_id = ?";
            $params[] = $_SESSION['user_id'];
        }

        if (isset($_GET['status']) && $_GET['status'] !== '') {
            if ($_GET['status'] === 'transit') {
                $conditions[] = "p.status IN ('approved', 'in_transit')";
            } else {
                $conditions[] = "p.status = ?";
                $params[] = $_GET['status'];
            }
        }
        if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
            $conditions[] = "p.created_at >= ?";
            $params[] = $_GET['date_from'] . ' 00:00:00';
        }
        if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
            $conditions[] = "p.created_at <= ?";
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }
        if (isset($_GET['surat_jalan']) && $_GET['surat_jalan'] !== '') {
            $conditions[] = "p.surat_jalan LIKE ?";
            $params[] = "%" . $_GET['surat_jalan'] . "%";
        }
        if (isset($_GET['receiver']) && $_GET['receiver'] !== '') {
            $conditions[] = "EXISTS (SELECT 1 FROM deliveries d2 WHERE d2.pickup_id = p.id AND d2.receiver_name LIKE ?)";
            $params[] = "%" . $_GET['receiver'] . "%";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'delete_pickup_request':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        if ($_SESSION['role'] !== 'request_pickup') {
            checkWriteAccessApi('pickup_request');
        }
        $id = $_POST['id'] ?? null;
        if (!$id) {
            die(json_encode(['success' => false, 'error' => 'ID wajib diisi']));
        }

        // Ambil file info dulu untuk dibersihkan dari uploads/
        $stmtFile = $pdo->prepare("SELECT surat_jalan_file, goods_file FROM pickup_requests WHERE id = ?");
        $stmtFile->execute([$id]);
        $fileRow = $stmtFile->fetch();
        if ($fileRow) {
            foreach (['surat_jalan_file', 'goods_file'] as $col) {
                $raw = $fileRow[$col] ?? '';
                if (!$raw)
                    continue;
                $list = json_decode($raw, true);
                if (!is_array($list)) {
                    $list = [$raw]; // fallback untuk data lama yang bukan JSON
                }
                foreach ($list as $fname) {
                    if (!is_string($fname) || $fname === '')
                        continue;
                    // Cegah path traversal: hanya basename yang dipakai
                    $safe = basename($fname);
                    $filePath = 'uploads/' . $safe;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        // Only admin/controller with write access, or the owner can delete
        if ($_SESSION['role'] === 'request_pickup') {
            $stmt = $pdo->prepare("DELETE FROM pickup_requests WHERE id = ? AND requester_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM pickup_requests WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'change_my_password':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['error' => 'Tidak terautentikasi']));
        }
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        if (empty($old_password) || empty($new_password)) {
            die(json_encode(['error' => 'Password lama dan baru harus diisi']));
        }
        if (strlen($new_password) < 6) {
            die(json_encode(['error' => 'Password baru minimal 6 karakter']));
        }

        // Verify old password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($old_password, $user['password'])) {
            die(json_encode(['error' => 'Password lama tidak sesuai']));
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'update_my_profile':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['error' => 'Tidak terautentikasi']));
        }
        $user_id = $_SESSION['user_id'];
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';

        if (empty($name) || empty($username)) {
            die(json_encode(['error' => 'Nama dan Username tidak boleh kosong']));
        }

        // Cek apakah username sudah dipakai orang lain
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $user_id]);
        if ($check->fetch()) {
            die(json_encode(['error' => 'Username sudah digunakan oleh orang lain']));
        }

        try {
            $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?")->execute([$name, $username, $user_id]);
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Gagal memperbarui profil: ' . $e->getMessage()]);
        }
        break;

    case 'get_roles':
        $currentUserRole = $_SESSION['role'] ?? '';
        $sql = "SELECT * FROM roles";
        $params = [];

        if ($currentUserRole !== 'superadmin' && $currentUserRole !== 'controller') {
            $sql .= " WHERE role_key != 'superadmin' AND role_key != 'controller'";
        }

        $sql .= " ORDER BY role_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'add_role':
        checkWriteAccessApi('roles');
        $name = $_POST['role_name'];
        $key = $_POST['role_key'];
        $desc = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'person';
        $color = $_POST['color'] ?? '#6366f1';

        $stmt = $pdo->prepare("INSERT INTO roles (role_name, role_key, description, icon, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $key, $desc, $icon, $color]);
        echo json_encode(['success' => true]);
        break;

    case 'edit_role':
        checkWriteAccessApi('roles');
        $id = $_POST['id'];
        $name = $_POST['role_name'];
        $key = $_POST['role_key'];
        $desc = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'person';
        $color = $_POST['color'] ?? '#6366f1';

        $stmt = $pdo->prepare("UPDATE roles SET role_name=?, role_key=?, description=?, icon=?, color=? WHERE id=?");
        $stmt->execute([$name, $key, $desc, $icon, $color, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_role':
        checkWriteAccessApi('roles');
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_permissions':
        $role_key = $_GET['role_key'];
        $stmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role_key = ?");
        $stmt->execute([$role_key]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'update_permission':
        checkWriteAccessApi('roles');
        $role_key = $_POST['role_key'];
        $menu_key = $_POST['menu_key'];
        $can_access = $_POST['can_access']; // 1 or 0
        $can_write = $_POST['can_write'] ?? 0; // 1 or 0

        $sql = "INSERT INTO role_permissions (role_key, menu_key, can_access, can_write) VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE can_access = VALUES(can_access), can_write = VALUES(can_write)";
        $pdo->prepare($sql)->execute([$role_key, $menu_key, $can_access, $can_write]);
        echo json_encode(['success' => true]);
        break;

    case 'get_expedisi_tasks':
        try {
            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;
            $vendor_id = $_GET['vendor_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $all_date = $_GET['all_date'] ?? 0;

            $conditions = ["1=1"];
            $params = [];

            if ($all_date != 1 && $start && $end) {
                $conditions[] = "DATE(COALESCE(e.target_date, e.created_at)) BETWEEN ? AND ?";
                $params[] = $start;
                $params[] = $end;
            }

            if ($vendor_id) {
                $conditions[] = "e.vendor_id = ?";
                $params[] = $vendor_id;
            }

            if ($status) {
                $conditions[] = "e.status = ?";
                $params[] = $status;
            }

            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $s = "%" . $_GET['search'] . "%";
                $conditions[] = "(e.surat_jalan LIKE ? OR e.origin_name LIKE ? OR e.destination_name LIKE ? OR e.driver_name LIKE ?)";
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
            }

            if (isset($_GET['vendor_name']) && $_GET['vendor_name'] !== '') {
                $conditions[] = "ev.name = ?";
                $params[] = $_GET['vendor_name'];
            }

            if (isset($_GET['driver_name']) && $_GET['driver_name'] !== '') {
                $conditions[] = "e.driver_name = ?";
                $params[] = $_GET['driver_name'];
            }

            $where = implode(" AND ", $conditions);

            $sql = "SELECT e.*, u.name as creator_name, ev.name as vendor_name 
                    FROM expedisi_tasks e 
                    JOIN users u ON e.created_by = u.id 
                    LEFT JOIN expedisi_vendors ev ON e.vendor_id = ev.id
                    WHERE $where
                    ORDER BY e.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process JSON fields into arrays
            if (is_array($rows)) {
                foreach ($rows as &$row) {
                    foreach (['sj_files', 'goods_files', 'proof_files'] as $field) {
                        $raw = $row[$field] ?? '';
                        if ($raw) {
                            $decoded = json_decode($raw, true);
                            $row[$field . '_arr'] = is_array($decoded) ? $decoded : [$raw];
                        } else {
                            $row[$field . '_arr'] = [];
                        }
                    }
                }
            }
            unset($row);

            echo json_encode($rows);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'add_expedisi_task':
        checkWriteAccessApi('assign_tasks');
        try {
            $surat_jalan = isset($_POST['surat_jalan']) ? strtoupper(trim($_POST['surat_jalan'])) : '';
            $origin = $_POST['origin_name'];
            $dest = $_POST['destination_name'];
            $koli = $_POST['total_koli'] ?? 0;
            $driver = isset($_POST['driver_name']) ? strtoupper(trim($_POST['driver_name'])) : '';
            $vendor_id = $_POST['vendor_id'];
            $plate = isset($_POST['vehicle_plate']) ? strtoupper(trim($_POST['vehicle_plate'])) : '';
            $notes = isset($_POST['notes']) ? strtoupper(trim($_POST['notes'])) : '';
            $type = $_POST['type'] ?? 'antar';
            $target_date = $_POST['target_date'] ?? date('Y-m-d H:i:s');
            $created_by = $_SESSION['user_id'];

            if (empty($vendor_id)) {
                die(json_encode(['success' => false, 'error' => 'Silakan pilih vendor dari daftar pencarian']));
            }

            $sj_filenames = [];
            if (isset($_FILES['sj_files'])) {
                $files = $_FILES['sj_files'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if (!$ext)
                            continue;
                        $tmp_name = $files['tmp_name'][$i];
                        $fname = 'SJ_EXP_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                            $sj_filenames[] = $fname;
                    }
                }
            }
            $sj_json = json_encode($sj_filenames);

            $goods_filenames = [];
            if (isset($_FILES['goods_files'])) {
                $files = $_FILES['goods_files'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if (!$ext)
                            continue;
                        $tmp_name = $files['tmp_name'][$i];
                        $fname = 'GOODS_EXP_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                            $goods_filenames[] = $fname;
                    }
                }
            }
            $goods_json = json_encode($goods_filenames);

            $sql = "INSERT INTO expedisi_tasks (surat_jalan, sj_files, goods_files, origin_name, destination_name, total_koli, driver_name, vendor_id, vehicle_plate, notes, type, target_date, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $pdo->prepare($sql)->execute([$surat_jalan, $sj_json, $goods_json, $origin, $dest, $koli, $driver, $vendor_id, $plate, $notes, $type, $target_date, $created_by]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_expedisi_task':
        checkWriteAccessApi('assign_tasks');
        try {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID tugas tidak valid']);
                break;
            }

            $surat_jalan = isset($_POST['surat_jalan']) ? strtoupper(trim($_POST['surat_jalan'])) : '';
            $origin = $_POST['origin_name'] ?? '';
            $dest = $_POST['destination_name'] ?? '';
            $koli = $_POST['total_koli'] ?? 0;
            $driver = isset($_POST['driver_name']) ? strtoupper(trim($_POST['driver_name'])) : '';
            $vendor_id = $_POST['vendor_id'] ?? null;
            $plate = isset($_POST['vehicle_plate']) ? strtoupper(trim($_POST['vehicle_plate'])) : '';
            $notes = isset($_POST['notes']) ? strtoupper(trim($_POST['notes'])) : '';
            $type = $_POST['type'] ?? 'antar';
            $target_date = $_POST['target_date'] ?? date('Y-m-d H:i:s');

            if (empty($vendor_id)) {
                echo json_encode(['success' => false, 'error' => 'Silakan pilih vendor dari daftar pencarian']);
                break;
            }

            // Fetch existing task files
            $stmtExist = $pdo->prepare("SELECT sj_files, goods_files FROM expedisi_tasks WHERE id = ?");
            $stmtExist->execute([$id]);
            $existingTask = $stmtExist->fetch(PDO::FETCH_ASSOC);
            if (!$existingTask) {
                echo json_encode(['success' => false, 'error' => 'Tugas tidak ditemukan']);
                break;
            }

            $sj_json = $existingTask['sj_files'];
            if (isset($_FILES['sj_files']) && is_array($_FILES['sj_files']['name'])) {
                $sj_filenames = [];
                $files = $_FILES['sj_files'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if (!$ext)
                            continue;
                        $tmp_name = $files['tmp_name'][$i];
                        $fname = 'SJ_EXP_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                            $sj_filenames[] = $fname;
                    }
                }
                if (!empty($sj_filenames)) {
                    $sj_json = json_encode($sj_filenames);
                }
            }

            $goods_json = $existingTask['goods_files'];
            if (isset($_FILES['goods_files']) && is_array($_FILES['goods_files']['name'])) {
                $goods_filenames = [];
                $files = $_FILES['goods_files'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = safeUploadExtension($files['name'][$i]);
                        if (!$ext)
                            continue;
                        $tmp_name = $files['tmp_name'][$i];
                        $fname = 'GOODS_EXP_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads'))
                            mkdir('uploads', 0777, true);
                        if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                            $goods_filenames[] = $fname;
                    }
                }
                if (!empty($goods_filenames)) {
                    $goods_json = json_encode($goods_filenames);
                }
            }

            $sql = "UPDATE expedisi_tasks SET 
                    surat_jalan = ?, sj_files = ?, goods_files = ?, origin_name = ?, destination_name = ?, 
                    total_koli = ?, driver_name = ?, vendor_id = ?, vehicle_plate = ?, notes = ?, type = ?, target_date = ? 
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $surat_jalan,
                $sj_json,
                $goods_json,
                $origin,
                $dest,
                $koli,
                $driver,
                $vendor_id,
                $plate,
                $notes,
                $type,
                $target_date,
                $id
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'start_expedisi_task':
        checkWriteAccessApi('assign_tasks');
        $id = $_POST['id'];
        $pdo->prepare("UPDATE expedisi_tasks SET status = 'in_transit', start_time = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'complete_expedisi_task':
        checkWriteAccessApi('assign_tasks');
        $id = $_POST['id'];
        $receiver = $_POST['receiver_name'];

        $filenames = [];
        if (isset($_FILES['proof_files'])) {
            $files = $_FILES['proof_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = safeUploadExtension($files['name'][$i]);
                    if (!$ext)
                        continue;
                    $tmp_name = $files['tmp_name'][$i];
                    $fname = 'EXP_' . time() . '_' . uniqid() . '.' . $ext;
                    if (!is_dir('uploads'))
                        mkdir('uploads', 0777, true);
                    if (move_uploaded_file($tmp_name, 'uploads/' . $fname))
                        $filenames[] = $fname;
                }
            }
        }

        $proof_json = json_encode($filenames);
        $driver_notes = $_POST['driver_notes'] ?? null;
        $status = $_POST['status'] ?? 'completed';

        $sql = "UPDATE expedisi_tasks SET status = ?, end_time = CURRENT_TIMESTAMP, receiver_name = ?, driver_notes = ?, proof_files = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$status, $receiver, $driver_notes, $proof_json, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_expedisi_task':
        checkWriteAccessApi('assign_tasks');
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID tugas tidak valid']);
            break;
        }

        $stmtStatus = $pdo->prepare("SELECT status, sj_files, goods_files, proof_files FROM expedisi_tasks WHERE id = ?");
        $stmtStatus->execute([$id]);
        $task = $stmtStatus->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            echo json_encode(['success' => false, 'error' => 'Tugas tidak ditemukan']);
            break;
        }

        $userRole = $_SESSION['role'] ?? '';
        $isSuperAdmin = in_array($userRole, ['superadmin', 'controller'], true);

        if ($task['status'] === 'completed' && !$isSuperAdmin) {
            echo json_encode(['success' => false, 'error' => 'Tugas yang sudah Selesai (Completed) hanya dapat dihapus oleh Super Admin']);
            break;
        }

        foreach (['sj_files', 'goods_files', 'proof_files'] as $col) {
            $raw = $task[$col] ?? '';
            if (!$raw)
                continue;
            $list = json_decode($raw, true);
            if (!is_array($list))
                $list = [$raw];
            foreach ($list as $fname) {
                if (!is_string($fname) || $fname === '')
                    continue;
                $safe = basename($fname);
                $filePath = 'uploads/' . $safe;
                if (file_exists($filePath))
                    @unlink($filePath);
            }
        }

        $pdo->prepare("DELETE FROM expedisi_tasks WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_expedisi_vendors':
        $stmt = $pdo->query("SELECT * FROM expedisi_vendors ORDER BY name ASC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'add_expedisi_vendor':
        if ($_SESSION['role'] !== 'admin') {
            checkWriteAccessApi('expedisi');
        }
        $name = $_POST['name'];
        $cp = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO expedisi_vendors (name, contact_person, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $cp, $phone, $address]);
        echo json_encode(['success' => true]);
        break;

    case 'get_expedisi_vendor':
        if (!isset($_SESSION['user_id'])) {
            die(json_encode(['success' => false, 'error' => 'Tidak terautentikasi']));
        }
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM expedisi_vendors WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'Not found']);
        break;

    case 'edit_expedisi_vendor':
        if ($_SESSION['role'] === 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Admin tidak dapat mengedit)']));
        }
        checkWriteAccessApi('expedisi');
        $id = $_POST['id'];
        $name = $_POST['name'];
        $cp = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $stmt = $pdo->prepare("UPDATE expedisi_vendors SET name=?, contact_person=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $cp, $phone, $address, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_expedisi_vendor':
        if ($_SESSION['role'] === 'admin') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Admin tidak dapat menghapus)']));
        }
        checkWriteAccessApi('expedisi');
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM expedisi_vendors WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;



    case 'get_system_logs':
        checkAccessApi('logs');
        $search = $_GET['search'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        $sql = "SELECT l.*, u.username, u.name as user_full_name 
                FROM system_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE (l.action LIKE ? OR l.description LIKE ? OR u.username LIKE ?)";

        $params = ["%$search%", "%$search%", "%$search%"];

        if ($date_from) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $date_from;
        }
        if ($date_to) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $date_to;
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT 500";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_pending_counts':
        $p1 = $pdo->query("SELECT COUNT(*) FROM pickup_requests WHERE status = 'pending'")->fetchColumn();
        $p2 = $pdo->query("SELECT COUNT(*) FROM expedisi_tasks WHERE status = 'pending'")->fetchColumn();
        echo json_encode(['pickup' => (int) $p1, 'expedisi' => (int) $p2, 'total' => (int) $p1 + (int) $p2]);
        break;

    // =============================================
    // ========== TASK NOTES (Catatan Task) =========
    // =============================================

    case 'get_task_notes':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $month = $_GET['month'] ?? null; // format: YYYY-MM
        $date = $_GET['date'] ?? null;   // format: YYYY-MM-DD

        if ($date) {
            // Get notes for a specific date
            $stmt = $pdo->prepare("SELECT * FROM task_notes WHERE user_id = ? AND note_date = ? ORDER BY is_done ASC, priority DESC, created_at ASC");
            $stmt->execute([$user_id, $date]);
        } elseif ($month) {
            // Get notes for a full month (for calendar dots)
            $stmt = $pdo->prepare("SELECT * FROM task_notes WHERE user_id = ? AND DATE_FORMAT(note_date, '%Y-%m') = ? ORDER BY note_date ASC, is_done ASC, priority DESC");
            $stmt->execute([$user_id, $month]);
        } else {
            // Get all notes (fallback)
            $stmt = $pdo->prepare("SELECT * FROM task_notes WHERE user_id = ? ORDER BY note_date DESC, is_done ASC LIMIT 100");
            $stmt->execute([$user_id]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_task_note':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note_date = $_POST['note_date'] ?? '';
        $note_time = $_POST['note_time'] ?? null;
        $priority = $_POST['priority'] ?? 'medium';

        if (!$title || !$note_date) {
            echo json_encode(['success' => false, 'error' => 'Judul dan tanggal wajib diisi.']);
            break;
        }

        if (!in_array($priority, ['low', 'medium', 'high']))
            $priority = 'medium';
        if ($note_time === '')
            $note_time = null;

        $stmt = $pdo->prepare("INSERT INTO task_notes (user_id, title, description, note_date, note_time, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $note_date, $note_time, $priority]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'edit_task_note':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $id = $_POST['id'] ?? 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note_date = $_POST['note_date'] ?? '';
        $note_time = $_POST['note_time'] ?? null;
        $priority = $_POST['priority'] ?? 'medium';

        if (!$id || !$title || !$note_date) {
            echo json_encode(['success' => false, 'error' => 'Data tidak lengkap.']);
            break;
        }

        if (!in_array($priority, ['low', 'medium', 'high']))
            $priority = 'medium';
        if ($note_time === '')
            $note_time = null;

        // Validate ownership
        $check = $pdo->prepare("SELECT id FROM task_notes WHERE id = ? AND user_id = ?");
        $check->execute([$id, $user_id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Catatan tidak ditemukan.']);
            break;
        }

        $stmt = $pdo->prepare("UPDATE task_notes SET title = ?, description = ?, note_date = ?, note_time = ?, priority = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $note_date, $note_time, $priority, $id, $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_task_note':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $id = $_POST['id'] ?? 0;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID diperlukan.']);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM task_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;

    case 'toggle_task_note':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $id = $_POST['id'] ?? 0;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID diperlukan.']);
            break;
        }

        $stmt = $pdo->prepare("UPDATE task_notes SET is_done = NOT is_done WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_today_task_notes_count':
        checkLogin();
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_notes WHERE user_id = ? AND note_date = CURDATE() AND is_done = 0");
        $stmt->execute([$user_id]);
        $count = (int) $stmt->fetchColumn();

        // Also get the titles for popup (include note_time for time-based alarm)
        $stmtNotes = $pdo->prepare("SELECT id, title, priority, note_time FROM task_notes WHERE user_id = ? AND note_date = CURDATE() AND is_done = 0 ORDER BY note_time ASC, priority DESC LIMIT 10");
        $stmtNotes->execute([$user_id]);
        $notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['count' => $count, 'notes' => $notes]);
        break;

    case 'get_database_tables':
        checkLogin();
        if (($_SESSION['role'] ?? '') !== 'controller') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Hanya Super Admin)']));
        }
        try {
            $stmt = $pdo->query("SHOW TABLE STATUS");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_rows = 0;
            $total_size = 0;
            $tableList = [];

            foreach ($tables as $t) {
                $rows = (int) ($t['Rows'] ?? 0);
                $data_size = (int) ($t['Data_length'] ?? 0) + (int) ($t['Index_length'] ?? 0);
                $total_rows += $rows;
                $total_size += $data_size;

                $tableList[] = [
                    'name' => $t['Name'],
                    'engine' => $t['Engine'],
                    'rows' => $rows,
                    'data_size' => $data_size,
                    'auto_increment' => $t['Auto_increment'] ?? null,
                    'create_time' => $t['Create_time'] ?? null,
                    'update_time' => $t['Update_time'] ?? null,
                    'comment' => $t['Comment'] ?? ''
                ];
            }

            echo json_encode([
                'success' => true,
                'db_name' => $db,
                'total_tables' => count($tableList),
                'total_rows' => $total_rows,
                'total_size' => $total_size,
                'can_write' => true,
                'tables' => $tableList
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'truncate_database_table':
        checkLogin();
        if (($_SESSION['role'] ?? '') !== 'controller') {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak. Mengosongkan tabel hanya dapat dilakukan oleh Super Admin']));
        }

        // Support single table_name or array/comma-separated table_names
        $tableNames = [];
        if (isset($_POST['table_names'])) {
            if (is_array($_POST['table_names'])) {
                $tableNames = $_POST['table_names'];
            } else {
                $tableNames = explode(',', $_POST['table_names']);
            }
        } elseif (isset($_POST['table_name'])) {
            $tableNames = [$_POST['table_name']];
        }

        $tableNames = array_filter(array_map('trim', $tableNames));

        if (empty($tableNames)) {
            echo json_encode(['success' => false, 'error' => 'Nama tabel wajib dipilih/diisi.']);
            break;
        }

        try {
            $stmtCheck = $pdo->query("SHOW TABLES");
            $existingTables = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);

            $cleared = [];
            $errors = [];

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            foreach ($tableNames as $tableName) {
                if (!in_array($tableName, $existingTables)) {
                    $errors[] = "Tabel '$tableName' tidak ditemukan.";
                    continue;
                }

                $pdo->exec("TRUNCATE TABLE `$tableName`");
                $cleared[] = $tableName;

                // Safety Restoration for core system tables
                if ($tableName === 'users') {
                    $stmtIns = $pdo->prepare("INSERT IGNORE INTO users (id, username, password, name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtIns->execute([1, 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'controller', 1]);
                } elseif ($tableName === 'roles') {
                    $defaultRoles = [
                        ['Super Admin', 'controller', 'Akses penuh seluruh sistem', 'star', '#f59e0b'],
                        ['Admin', 'admin', 'Manajemen operasional harian', 'shield', '#6366f1'],
                        ['Admin Request Pickup', 'admin_request_pickup', 'Manajemen data pickup gudang', 'package_2', '#10b981'],
                        ['Request Pickup', 'request_pickup', 'User mobile untuk input pickup', 'smartphone', '#e11d48'],
                        ['Driver', 'driver', 'User mobile untuk pengiriman', 'local_shipping', '#0ea5e9']
                    ];
                    $stmtRole = $pdo->prepare("INSERT IGNORE INTO roles (role_name, role_key, description, icon, color) VALUES (?, ?, ?, ?, ?)");
                    foreach ($defaultRoles as $r) {
                        $stmtRole->execute($r);
                    }
                } elseif ($tableName === 'role_permissions') {
                    $critical = [
                        ['controller', 'roles', 1, 1],
                        ['controller', 'database', 1, 1],
                        ['admin', 'roles', 1, 1],
                        ['admin', 'database', 1, 1]
                    ];
                    $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_key, menu_key, can_access, can_write) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE can_access=1, can_write=1");
                    foreach ($critical as $c) {
                        $stmtPerm->execute($c);
                    }
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            if (function_exists('logActivity') && !empty($cleared)) {
                logActivity('TRUNCATE_TABLE', "Mengosongkan data pada " . count($cleared) . " tabel database: " . implode(', ', $cleared));
            }

            $countCleared = count($cleared);
            echo json_encode([
                'success' => true,
                'message' => "Berhasil mengosongkan $countCleared tabel database (" . implode(', ', $cleared) . ").",
                'cleared' => $cleared,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $ex) {
            }
            echo json_encode(['success' => false, 'error' => 'Gagal mengosongkan tabel: ' . $e->getMessage()]);
        }
        break;

    case 'get_system_license':
        checkLogin();
        if (function_exists('ensureLicenseTablesExist')) ensureLicenseTablesExist($pdo);
        if (!canManageSystemLicense()) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Khusus Pengelola Lisensi System)']));
        }
        try {
            $stmtLic = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_active_until'");
            $activeUntil = $stmtLic ? $stmtLic->fetchColumn() : null;

            $stmtNotes = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_license_notes'");
            $licenseNotes = $stmtNotes ? $stmtNotes->fetchColumn() : '';

            $remainingDays = 0;
            $isExpired = false;
            if ($activeUntil) {
                $diffSec = strtotime($activeUntil) - time();
                $remainingDays = (int) ceil($diffSec / 86400);
                if ($diffSec <= 0) $isExpired = true;
            }

            echo json_encode([
                'success' => true,
                'active_until' => $activeUntil,
                'remaining_days' => $remainingDays,
                'is_expired' => $isExpired,
                'license_notes' => $licenseNotes
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_system_license':
        checkLogin();
        if (!canManageSystemLicense()) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak (Khusus Pengelola Lisensi System)']));
        }
        try {
            $months = isset($_POST['months']) ? (int)$_POST['months'] : 0;
            $custom_date = $_POST['custom_date'] ?? null;
            $notes = trim($_POST['notes'] ?? '');

            $stmtLic = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_active_until'");
            $currentVal = $stmtLic ? $stmtLic->fetchColumn() : null;

            $baseTime = time();
            if ($currentVal && strtotime($currentVal) > time()) {
                $baseTime = strtotime($currentVal);
            }

            $newDateStr = null;
            if ($months > 0) {
                $newDateStr = date('Y-m-d 23:59:59', strtotime("+$months months", $baseTime));
            } elseif (!empty($custom_date)) {
                $newDateStr = date('Y-m-d 23:59:59', strtotime($custom_date));
            } else {
                die(json_encode(['success' => false, 'error' => 'Silakan pilih durasi bulan atau tanggal lisensi.']));
            }

            $stmtUpsert = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('app_active_until', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmtUpsert->execute([$newDateStr, $newDateStr]);

            if ($notes !== '') {
                $stmtNotes = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('app_license_notes', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmtNotes->execute([$notes, $notes]);
            }

            // Insert into system_license_logs
            $extType = ($months > 0) ? "+$months Bulan" : "Tanggal Kustom";
            $extendedBy = $_SESSION['username'] ?? 'system';
            $stmtLog = $pdo->prepare("INSERT INTO system_license_logs (extended_by, extension_type, previous_expiry, new_expiry, payment_notes) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$extendedBy, $extType, $currentVal, $newDateStr, $notes]);
            $logId = $pdo->lastInsertId();

            logActivity('UPDATE_SYSTEM_LICENSE', "Memperpanjang lisensi web hingga: $newDateStr. Catatan: $notes");

            echo json_encode([
                'success' => true,
                'message' => "Masa aktif web berhasil diperpanjang sampai " . date('d M Y', strtotime($newDateStr)),
                'active_until' => $newDateStr,
                'log_id' => $logId
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_license_logs':
        checkLogin();
        if (function_exists('ensureLicenseTablesExist')) ensureLicenseTablesExist($pdo);
        if (!canManageSystemLicense()) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        try {
            $stmt = $pdo->query("SELECT id, extended_by, extension_type, previous_expiry, new_expiry, payment_notes, created_at FROM system_license_logs ORDER BY id DESC LIMIT 50");
            $logs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_license_managers':
        checkLogin();
        if (function_exists('ensureLicenseTablesExist')) ensureLicenseTablesExist($pdo);
        if (!canManageSystemLicense()) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        try {
            $stmtM = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_license_managers'");
            $rawM = $stmtM ? $stmtM->fetchColumn() : null;
            $managers = $rawM ? json_decode($rawM, true) : ['daniel'];
            if (!is_array($managers)) $managers = ['daniel'];

            // Get non-driver active users for selection
            $stmtUsers = $pdo->query("SELECT id, username, name, role FROM users WHERE is_active = 1 AND role != 'driver' ORDER BY name ASC");
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            $managersLower = array_map('strtolower', array_map('trim', $managers));

            foreach ($users as &$u) {
                $u['is_manager'] = in_array(strtolower($u['username']), $managersLower, true);
                $u['is_owner'] = (strtolower($u['username']) === 'daniel');
            }
            unset($u);

            echo json_encode([
                'success' => true,
                'managers' => $managers,
                'users' => $users
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_license_managers':
        checkLogin();
        if (!canManageSystemLicense()) {
            die(json_encode(['success' => false, 'error' => 'Akses ditolak']));
        }
        try {
            $rawUsernames = $_POST['usernames'] ?? [];
            if (is_string($rawUsernames)) {
                $rawUsernames = explode(',', $rawUsernames);
            }
            if (!is_array($rawUsernames)) $rawUsernames = [];

            $cleanUsernames = array_values(array_unique(array_filter(array_map('trim', $rawUsernames))));

            // Always ensure 'daniel' is included
            if (!in_array('daniel', array_map('strtolower', $cleanUsernames), true)) {
                $cleanUsernames[] = 'daniel';
            }

            $jsonVal = json_encode($cleanUsernames);
            $stmtUpsert = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('app_license_managers', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmtUpsert->execute([$jsonVal, $jsonVal]);

            logActivity('UPDATE_LICENSE_MANAGERS', "Memperbarui daftar pengelola lisensi web: " . implode(', ', $cleanUsernames));

            echo json_encode([
                'success' => true,
                'message' => 'Daftar pengelola lisensi berhasil diperbarui!',
                'managers' => $cleanUsernames
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
