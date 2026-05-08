<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

include "paymongo_config.php";

$userId = $_SESSION['user_id'];

function getDocumentFee($documentType) {
    $fees = array(
        "Barangay Clearance" => 50,
        "Certificate of Indigency" => 0,
        "Certificate of Residency" => 30,
        "Business Permit Clearance" => 200,
        "Barangay ID" => 100,
        "Certificate of Good Moral" => 30,
        "Solo Parent Certificate" => 0,
        "Senior Citizen Certificate" => 0
    );

    if (isset($fees[$documentType])) {
        return $fees[$documentType];
    }

    return 0;
}

function createPaymongoCheckout($paymongoSecretKey, $paymongoCheckoutUrl, $baseUrl, $paymentId, $requestId, $userId, $documentType, $amount, $paymentMethod) {
    if (!function_exists('curl_init')) {
        return array(
            "success" => false,
            "error" => "cURL is not enabled in your PHP setup."
        );
    }

    $amountCentavos = intval($amount * 100);

    $paymongoMethod = "gcash";

    if ($paymentMethod === "maya") {
        $paymongoMethod = "paymaya";
    }

    $successUrl = $baseUrl . "/payment_success.php?payment_id=" . $paymentId;
    $cancelUrl = $baseUrl . "/payment_cancel.php?payment_id=" . $paymentId;

    $paymentData = array(
        "data" => array(
            "attributes" => array(
                "send_email_receipt" => true,
                "show_description" => true,
                "show_line_items" => true,
                "description" => "BarangayKonek Document Payment",
                "success_url" => $successUrl,
                "cancel_url" => $cancelUrl,
                "payment_method_types" => array($paymongoMethod),
                "line_items" => array(
                    array(
                        "currency" => "PHP",
                        "amount" => $amountCentavos,
                        "name" => $documentType,
                        "quantity" => 1
                    )
                ),
                "metadata" => array(
                    "payment_id" => strval($paymentId),
                    "request_id" => strval($requestId),
                    "user_id" => strval($userId),
                    "document_type" => $documentType
                )
            )
        )
    );

    $encodedKey = base64_encode($paymongoSecretKey . ":");

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $paymongoCheckoutUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Basic " . $encodedKey
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        return array(
            "success" => false,
            "error" => $curlError
        );
    }

    $responseData = json_decode($response, true);

    if (isset($responseData["data"]["id"]) && isset($responseData["data"]["attributes"]["checkout_url"])) {
        return array(
            "success" => true,
            "checkout_session_id" => $responseData["data"]["id"],
            "checkout_url" => $responseData["data"]["attributes"]["checkout_url"]
        );
    }

    return array(
        "success" => false,
        "error" => $responseData
    );
}

$regSql  = "SELECT FIRST_NAME, LAST_NAME, GENDER, PROFILE_PICTURE, ADDRESS, MOBILE_NUMBER FROM REGISTRATION WHERE USER_ID = ?";
$regStmt = sqlsrv_query($conn, $regSql, [$userId]);
if ($regStmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
$regRow = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);

$firstName       = $regRow ? htmlspecialchars(rtrim($regRow['FIRST_NAME'])) : 'Resident';
$lastName        = $regRow ? htmlspecialchars(rtrim($regRow['LAST_NAME']))  : '';
$fullName        = $firstName . ' ' . $lastName;
$gender          = $regRow ? strtolower(rtrim($regRow['GENDER'] ?? '')) : '';
$residentAddress = $regRow ? htmlspecialchars(rtrim($regRow['ADDRESS'] ?? '')) : '';
$residentContact = $regRow ? htmlspecialchars(rtrim($regRow['MOBILE_NUMBER'] ?? '')) : '';

if ($regRow && !empty($regRow['PROFILE_PICTURE'])) {
    $profilePicture = htmlspecialchars($regRow['PROFILE_PICTURE']);
} elseif ($gender === 'male') {
    $profilePicture = 'default/male.png';
} elseif ($gender === 'female') {
    $profilePicture = 'default/female.png';
} else {
    $profilePicture = 'default/neutral.png';
}

$unreadRow   = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM NOTIFICATIONS WHERE USER_ID = ? AND IS_READ = 0", [$userId]), SQLSRV_FETCH_ASSOC);
$unreadCount = $unreadRow ? (int)$unreadRow['CNT'] : 0;

$notifStmt = sqlsrv_query($conn,
    "SELECT TOP 15 NOTIFICATION_ID, MESSAGE, TYPE, IS_READ, CREATED_AT, REFERENCE_ID
     FROM NOTIFICATIONS WHERE USER_ID = ? ORDER BY CREATED_AT DESC",
    [$userId]
);
$notifications = [];
while ($row = sqlsrv_fetch_array($notifStmt, SQLSRV_FETCH_ASSOC)) {
    $notifications[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'read_notif' && isset($_POST['notif_id'])) {
        $notifId = (int)$_POST['notif_id'];
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?", [$notifId, $userId]);
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit();
        }
        $typeKey = trim($_POST['notif_type'] ?? '');
        $refId   = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;
        if (in_array($typeKey, ['LIKE', 'COMMENT']) && $refId > 0) {
            header("Location: residentcommunity.php#post-" . $refId);
        } elseif ($typeKey === 'ANNOUNCEMENT') {
            header("Location: residentdashboard.php");
        } elseif ($typeKey === 'REQUEST') {
            header("Location: residentrequest.php");
        } elseif ($typeKey === 'CONCERN') {
            header("Location: residentconcern.php");
        } else {
            header("Location: residentrequestdocument.php");
        }
        exit();
    }

    if ($_POST['action'] === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentrequestdocument.php");
        exit();
    }

    if ($_POST['action'] === 'submit_request') {
        header('Content-Type: application/json');

        $documentType   = trim($_POST['document_type'] ?? '');
        $notes          = trim($_POST['notes'] ?? '');
        $deliveryMethod = trim($_POST['delivery_method'] ?? '');
        $paymentMethod  = trim($_POST['payment_method'] ?? '');

        $documentFee = getDocumentFee($documentType);
        $deliveryFee = 0;

        if ($deliveryMethod === "delivery") {
            $deliveryFee = 50;
        }

        $totalAmount = $documentFee + $deliveryFee;

        if (empty($documentType) || empty($deliveryMethod)) {
            echo json_encode(['success' => false, 'error' => 'Please complete all required fields.']);
            exit();
        }

        $purposeValue = $notes !== '' ? $notes : 'N/A';

        $sql    = "INSERT INTO DOCUMENT_REQUESTS (USER_ID, DOCUMENT_TYPE, PURPOSE, DELIVERY_METHOD, STATUS, CREATED_AT)
                   VALUES (?, ?, ?, ?, 'PENDING', GETDATE())";
        $params = [$userId, $documentType, $purposeValue, $deliveryMethod];
        $stmt   = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            echo json_encode(['success' => false, 'error' => 'Failed to submit request. ' . ($errors[0]['message'] ?? '')]);
            exit();
        }

        $idStmt = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS NEW_ID");
        $idRow  = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC);
        $newId  = $idRow ? (int)$idRow['NEW_ID'] : 0;

        if ($newId === 0) {
            $idStmt2 = sqlsrv_query($conn,
                "SELECT TOP 1 REQUEST_ID FROM DOCUMENT_REQUESTS WHERE USER_ID = ? ORDER BY REQUEST_ID DESC",
                [$userId]
            );
            $idRow2 = sqlsrv_fetch_array($idStmt2, SQLSRV_FETCH_ASSOC);
            $newId  = $idRow2 ? (int)$idRow2['REQUEST_ID'] : 0;
        }

        if ($newId > 0) {
            $uploadDir = 'uploads/request_files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'heic', 'webp'];

            $fileCount = 0;
            $fileErrors = [];

            foreach ($_FILES as $key => $fileSlot) {
                if (strpos($key, 'req_file_') !== 0) continue;
                $index = (int)str_replace('req_file_', '', $key);
                $label = trim($_POST['req_label_' . $index] ?? ('Requirement ' . ($index + 1)));

                if ($fileSlot['error'] === UPLOAD_ERR_OK) {
                    $origName = basename($fileSlot['name']);
                    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed)) {
                        $fileErrors[] = 'File type not allowed: ' . $origName;
                        continue;
                    }
                    if ($fileSlot['size'] > 5 * 1024 * 1024) {
                        $fileErrors[] = 'File too large: ' . $origName;
                        continue;
                    }

                    $safeName = $newId . '_' . $index . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $destPath = $uploadDir . $safeName;

                    if (move_uploaded_file($fileSlot['tmp_name'], $destPath)) {
                        $fileSql    = "INSERT INTO DOCUMENT_REQUEST_FILES (REQUEST_ID, FILE_LABEL, FILE_NAME, FILE_PATH, UPLOADED_AT)
                                       VALUES (?, ?, ?, ?, GETDATE())";
                        $fileParams = [$newId, $label, $origName, $destPath];
                        $fileStmt   = sqlsrv_query($conn, $fileSql, $fileParams);
                        if ($fileStmt === false) {
                            $fileErrors[] = 'DB insert failed for: ' . $origName . ' — ' . print_r(sqlsrv_errors(), true);
                        } else {
                            $fileCount++;
                        }
                    } else {
                        $fileErrors[] = 'Could not move file: ' . $origName;
                    }
                } elseif ($fileSlot['error'] !== UPLOAD_ERR_NO_FILE) {
                    $fileErrors[] = 'Upload error code ' . $fileSlot['error'] . ' for key ' . $key;
                }
            }
        }

        if ($totalAmount > 0 && ($paymentMethod === "gcash" || $paymentMethod === "maya")) {
            $paymentSql = "
                INSERT INTO PAYMENTS
                (REQUEST_ID, USER_ID, DOCUMENT_TYPE, PAYMENT_METHOD, AMOUNT, PAYMENT_STATUS)
                OUTPUT INSERTED.PAYMENT_ID
                VALUES (?, ?, ?, ?, ?, 'PENDING')
            ";

            $paymentParams = array(
                $newId,
                $userId,
                $documentType,
                $paymentMethod,
                $totalAmount
            );

            $paymentStmt = sqlsrv_query($conn, $paymentSql, $paymentParams);

            if ($paymentStmt === false) {
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Request was saved, but payment record failed. ' . print_r(sqlsrv_errors(), true)
                ));
                exit();
            }

            $paymentRow = sqlsrv_fetch_array($paymentStmt, SQLSRV_FETCH_ASSOC);
            $paymentId = $paymentRow ? (int)$paymentRow['PAYMENT_ID'] : 0;

            $checkoutResult = createPaymongoCheckout(
                $paymongoSecretKey,
                $paymongoCheckoutUrl,
                $baseUrl,
                $paymentId,
                $newId,
                $userId,
                $documentType,
                $totalAmount,
                $paymentMethod
            );

            if ($checkoutResult["success"]) {
                $updatePaymentSql = "
                    UPDATE PAYMENTS
                    SET CHECKOUT_SESSION_ID = ?
                    WHERE PAYMENT_ID = ?
                ";

                sqlsrv_query($conn, $updatePaymentSql, array(
                    $checkoutResult["checkout_session_id"],
                    $paymentId
                ));

                echo json_encode(array(
                    'success' => true,
                    'request_id' => $newId,
                    'payment_required' => true,
                    'checkout_url' => $checkoutResult["checkout_url"],
                    'files_saved' => $fileCount ?? 0,
                    'file_errors' => $fileErrors ?? array()
                ));
                exit();
            }

            echo json_encode(array(
                'success' => false,
                'error' => 'Request was saved, but PayMongo checkout failed.',
                'paymongo_error' => $checkoutResult["error"]
            ));
            exit();
        }

        echo json_encode(array(
            'success' => true,
            'request_id' => $newId,
            'payment_required' => false,
            'files_saved' => $fileCount ?? 0,
            'file_errors' => $fileErrors ?? array()
        ));
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Request Documents — BarangayKonek</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="resident.css" />
  <style>
    .sidebar-divider { height:1px; background:rgba(255,255,255,0.08); margin:6px 14px; }

    .bell-wrap-pos { position:relative; }
    .notif-dropdown {
      position:absolute; top:calc(100% + 10px); right:0; width:340px;
      background:#fff; border:1px solid rgba(5,22,80,0.12); border-radius:10px;
      box-shadow:0 8px 30px rgba(5,22,80,0.16); z-index:999; display:none;
      max-height:480px; overflow-y:auto;
    }
    .notif-dropdown.open { display:block; }
    .notif-dropdown-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px 10px; border-bottom:1px solid rgba(5,22,80,0.08);
      position:sticky; top:0; background:#fff; z-index:1;
    }
    .notif-dropdown-header h4 { font-size:14px; font-weight:700; color:#051650; margin:0; }
    .notif-mark-all { font-size:12px; color:#051650; font-weight:700; background:none; border:none; padding:0; font-family:inherit; cursor:pointer; }
    .notif-mark-all:hover { text-decoration:underline; }
    .notif-item { display:block; padding:0; border-bottom:1px solid rgba(5,22,80,0.05); background:#fff; cursor:pointer; transition:background 0.15s; width:100%; text-align:left; border-left:none; border-right:none; border-top:none; font-family:inherit; }
    .notif-item:hover { background:#f5f7ff; }
    .notif-item.unread { background:rgba(204,255,0,0.07); }
    .notif-item-top { display:flex; align-items:flex-start; gap:10px; padding:11px 14px 8px; }
    .notif-item-icon { width:34px; height:34px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
    .notif-item-text { font-size:13px; color:#333; line-height:1.45; flex:1; }
    .notif-item-time { font-size:11px; color:#aaa; margin-top:3px; }
    .notif-unread-dot { width:8px; height:8px; border-radius:50%; background:#ccff00; border:1.5px solid #051650; flex-shrink:0; margin-top:6px; }
    .notif-empty { padding:28px; text-align:center; font-size:13px; color:#aaa; }

    .req-upload-list { display:flex; flex-direction:column; gap:12px; margin-bottom:14px; }
    .req-upload-item {
      background: rgba(5,22,80,0.03);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 12px 14px;
    }
    .req-upload-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .req-upload-label .req-required { color: var(--red); }
    .req-file-input {
      display: block;
      width: 100%;
      font-size: 13px;
      font-family: inherit;
      color: var(--text);
      background: var(--surface);
      border: 1px dashed var(--border);
      border-radius: 8px;
      padding: 10px 12px;
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .req-file-input:focus { outline: none; border-color: var(--navy); }
    .req-file-hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; }
    .req-file-preview {
      display: none;
      align-items: center;
      gap: 8px;
      margin-top: 6px;
      font-size: 12px;
      color: #15803d;
      font-weight: 600;
    }
    .req-file-preview.visible { display: flex; }
    .req-confirm-note {
      font-size:12px; color:var(--text-muted); margin-top:10px; padding:10px 12px;
      background:rgba(59,130,246,0.07); border:1px solid rgba(59,130,246,0.15);
      border-radius:10px; display:flex; align-items:flex-start; gap:8px;
    }
    .req-confirm-note i { margin-top:2px; color:#3b82f6; flex-shrink:0; }

    .input-error { border-color: var(--red) !important; box-shadow: 0 0 0 3px rgba(255,77,77,0.1) !important; }
    .field-error-msg { font-size: 12px; color: var(--red); margin-top: 5px; display: none; }
    .field-error-msg.visible { display: block; }

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }
  </style>
</head>
<body>

<div class="logout-confirm-overlay" id="logoutModal">
  <div class="logout-confirm-box">
    <div class="logout-confirm-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the home page.</p>
    <div class="logout-confirm-btns">
      <button type="button" class="btn-logout-cancel" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-logout-confirm"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="container">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>BarangayKonek</h2><span>Resident</span></div>
    <div class="profile profile--compact">
      <div class="avatar-ring">
        <img src="<?= $profilePicture ?>" alt="Resident Photo" />
      </div>
      <div class="profile-meta">
        <h3><?= $fullName ?></h3>
        <p>City of Imus, Alapan 1-A</p>
        <span class="portal-badge">Resident Portal</span>
      </div>
    </div>
    <nav class="menu">
      <a href="residentdashboard.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="residentrequestdocument.php" class="active"><i class="fa-solid fa-file-lines nav-icon"></i><span>Request Documents</span></a>
      <a href="residentconcern.php"><i class="fa-solid fa-circle-exclamation nav-icon"></i><span>Concerns</span></a>
      <a href="residentcommunity.php"><i class="fa-solid fa-users nav-icon"></i><span>Community</span></a>
      <a href="residentrequest.php"><i class="fa-solid fa-clipboard-list nav-icon"></i><span>My Requests</span></a>
    </nav>
    <div class="sidebar-divider"></div>
    <nav class="menu">
      <a href="settings.php"><i class="fa-solid fa-gear nav-icon"></i><span>Settings</span></a>
    </nav>
    <div style="flex:1;"></div>
    <button type="button" class="logout" onclick="openLogout()" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;font-family:inherit;">
      <i class="fa-solid fa-right-from-bracket nav-icon"></i><span>Logout</span>
    </button>
  </aside>

  <main class="content">
    <div class="content-inner">
      <div class="topbar">
        <div class="greeting-block">
          <h1>Request <span style="color:var(--navy);">Documents</span></h1>
          <p class="subtitle">Search and request official barangay documents easily.</p>
        </div>
        <div class="topbar-right">
          <div class="bell-wrap-pos">
            <button type="button" id="bellBtn" class="community-header-icon community-bell-link" onclick="toggleNotif()"
              style="width:42px;height:42px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow);transition:all 0.2s ease;position:relative;">
              <i class="fa-regular fa-bell" style="font-size:17px;color:var(--navy);"></i>
              <?php if ($unreadCount > 0): ?>
              <span style="position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid var(--bg);"><?= $unreadCount ?></span>
              <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
              <div class="notif-dropdown-header">
                <h4>Notifications <?php if ($unreadCount > 0): ?><span style="font-size:11px;color:#888;font-weight:400;">(<?= $unreadCount ?> unread)</span><?php endif; ?></h4>
                <?php if ($unreadCount > 0): ?>
                <form method="POST" action="residentrequestdocument.php" style="margin:0;">
                  <input type="hidden" name="action" value="mark_all_read">
                  <button type="submit" class="notif-mark-all">Mark all read</button>
                </form>
                <?php endif; ?>
              </div>
              <?php if (empty($notifications)): ?>
              <div class="notif-empty"><i class="fa-regular fa-bell" style="font-size:28px;display:block;margin-bottom:8px;"></i>No notifications yet.</div>
              <?php else: ?>
              <?php foreach ($notifications as $notif):
                $isUnread = !(bool)$notif['IS_READ'];
                $notifId  = (int)$notif['NOTIFICATION_ID'];
                $refId    = $notif['REFERENCE_ID'] ? (int)$notif['REFERENCE_ID'] : 0;
                $typeKey  = rtrim($notif['TYPE']);
                $iconMap  = ['LIKE'=>'fa-thumbs-up','COMMENT'=>'fa-comment','ANNOUNCEMENT'=>'fa-bullhorn','REQUEST'=>'fa-file-lines','CONCERN'=>'fa-circle-exclamation'];
                $icon     = $iconMap[$typeKey] ?? 'fa-bell';
                $timeAgo  = $notif['CREATED_AT']->format('M d, g:i A');
              ?>
              <button type="button" class="notif-item <?= $isUnread ? 'unread' : '' ?>"
                onclick="handleNotifClick(<?= $notifId ?>, <?= $refId ?>, '<?= $typeKey ?>', this)">
                <div class="notif-item-top">
                  <div class="notif-item-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                  <div class="notif-item-text">
                    <?= htmlspecialchars(rtrim($notif['MESSAGE'])) ?>
                    <div class="notif-item-time"><?= $timeAgo ?></div>
                  </div>
                  <?php if ($isUnread): ?><div class="notif-unread-dot" id="notif-dot-<?= $notifId ?>"></div><?php endif; ?>
                </div>
              </button>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="search-section">
        <p class="search-label">Find a document</p>
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="e.g. Barangay Clearance, Indigency, Certificate of Residency…" />
        </div>
        <div class="filter-pills">
          <div class="pill active" data-filter="all">All</div>
          <div class="pill" data-filter="free">Free</div>
          <div class="pill" data-filter="paid">Paid</div>
          <div class="pill" data-filter="certificate">Certificate</div>
          <div class="pill" data-filter="clearance">Clearance</div>
          <div class="pill" data-filter="permit">Permit</div>
        </div>
      </div>

      <p class="doc-section-title" id="docSectionTitle">Available Documents</p>
      <div class="doc-grid" id="docGrid"></div>
      <div class="no-results" id="noResults">
        <i class="fa-solid fa-file-circle-question"></i>
        <p>No documents found for "<span id="noResultsQuery"></span>"</p>
      </div>
    </div>
  </main>
</div>

<div class="modal-overlay" id="modalOverlay">
  <div class="modal" id="modal">

    <div class="success-state" id="successState">
      <div class="success-icon"><i class="fa-solid fa-check"></i></div>
      <h3>Request Submitted!</h3>
      <p>Your document request has been received. You will be notified once it is ready.</p>
      <div style="margin-top:14px;font-size:13px;color:var(--text-muted);">
        Request ID: <strong id="requestIdDisplay" style="color:var(--navy);font-family:'Space Mono',monospace;"></strong>
      </div>
      <div style="margin-top:20px;padding:0 0 4px;">
        <button class="btn btn-primary" onclick="window.location.href='residentrequest.php'" style="width:100%;justify-content:center;">
          <i class="fa-solid fa-clipboard-list"></i> View My Requests
        </button>
      </div>
    </div>

    <div id="formScreen">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-doc-icon" id="modalDocIcon"></div>
          <div>
            <div class="modal-title" id="modalTitle"></div>
            <div class="modal-subtitle" id="modalSubtitle"></div>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="modal-steps">
        <div class="step-dot active" id="step1dot">1</div>
        <div class="step-line" id="line12"></div>
        <div class="step-dot" id="step2dot">2</div>
        <div class="step-line" id="line23"></div>
        <div class="step-dot" id="step3dot">3</div>
      </div>

      <div class="modal-body">

        <div id="step1">
          <div style="margin-bottom:6px;margin-top:8px;">
            <p style="font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:10px;">
              <i class="fa-solid fa-paperclip" style="color:#2563eb;margin-right:6px;"></i>
              Upload Requirements — Please attach the following documents:
            </p>
            <div class="req-upload-list" id="reqUploadList"></div>
            <div class="req-confirm-note">
              <i class="fa-solid fa-circle-info"></i>
              <span>Accepted formats: JPG, PNG, PDF, HEIC, WEBP. Max 5MB per file. All requirements must be uploaded before proceeding.</span>
            </div>
          </div>
          <div class="modal-divider" style="margin-top:14px;"></div>
          <div class="form-section">
            <label class="form-label">Additional Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
            <textarea class="form-textarea" id="notesInput" placeholder="Any special instructions, name spelling corrections, or additional information…"></textarea>
          </div>
        </div>

        <div id="step2" class="hidden">
          <div class="form-section" style="margin-top:6px;">
            <label class="form-label" style="margin-bottom:10px;">How would you like to receive your document? <span>*</span></label>
            <div class="choice-grid">
              <div class="choice-card" data-delivery="pickup" onclick="selectDelivery('pickup')">
                <div class="choice-icon"><i class="fa-solid fa-store"></i></div>
                <div class="choice-label">Pick Up</div>
                <div class="choice-sub">at Barangay Hall</div>
              </div>
              <div class="choice-card" data-delivery="delivery" onclick="selectDelivery('delivery')">
                <div class="choice-icon"><i class="fa-solid fa-motorcycle"></i></div>
                <div class="choice-label">Home Delivery</div>
                <div class="choice-sub">&#8369;50 delivery fee</div>
              </div>
            </div>
          </div>

          <div id="pickupInfoBox" class="info-box hidden">
            <i class="fa-solid fa-circle-info"></i>
            <span>You selected <strong>Pick Up</strong>. The staff will notify you once your document is ready for pickup. Please bring a valid ID when claiming.</span>
          </div>

          <div id="deliveryAddressSection" class="hidden">
            <div class="form-section">
              <label class="form-label">Delivery Address <span>*</span></label>
              <input type="text" class="form-input" id="deliveryAddress" placeholder="House No., Street, Subdivision…" />
              <p class="form-hint">Your address on file has been pre-filled. Please verify or update if needed.</p>
            </div>
            <div class="form-section">
              <label class="form-label">Contact Number <span>*</span></label>
              <input type="text" class="form-input" id="contactNumber" placeholder="09XX XXX XXXX"
                oninput="validatePhone(this)" onblur="validatePhone(this)" maxlength="11" />
              <p class="form-hint">Must be an 11-digit Philippine mobile number starting with 09.</p>
              <div class="field-error-msg" id="phoneError">Please enter a valid 11-digit number (e.g. 09171234567).</div>
            </div>
          </div>
        </div>

        <div id="step3" class="hidden">
          <div class="modal-divider" style="margin-top:6px;"></div>
          <div id="freeDocSection" class="hidden">
            <div class="info-box" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;">
              <i class="fa-solid fa-circle-check"></i>
              <span>This document is <strong>free of charge</strong>. No payment is required.</span>
            </div>
          </div>
          <div id="paidDocSection" class="hidden">
            <div class="form-section">
              <label class="form-label" style="margin-bottom:10px;">Select Payment Method <span>*</span></label>
              <div class="payment-grid">
                <div class="payment-option" data-pay="cash" onclick="selectPayment('cash')">
                  <i class="fa-solid fa-money-bills"></i>
                  <span>Cash on Pick Up</span>
                </div>
                <div class="payment-option" data-pay="gcash" onclick="selectPayment('gcash')">
                  <i class="fa-solid fa-mobile-screen-button"></i>
                  <span>GCash</span>
                </div>
                <div class="payment-option" data-pay="maya" onclick="selectPayment('maya')">
                  <i class="fa-solid fa-credit-card"></i>
                  <span>Maya</span>
                </div>
              </div>
              <div id="cashDeliveryNote" class="form-hint hidden" style="margin-top:8px;color:#dc2626;">
                <i class="fa-solid fa-triangle-exclamation"></i> Cash payment for deliveries is collected upon receipt of the document.
              </div>
            </div>
          </div>
          <div class="fee-summary">
            <p style="font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:8px;">Order Summary</p>
            <div class="fee-row">
              <span id="summaryDocName">Document</span>
              <span id="summaryDocFee">Free</span>
            </div>
            <div class="fee-row" id="deliveryFeeRow" style="display:none;">
              <span>Delivery Fee</span>
              <span>&#8369;50</span>
            </div>
            <div class="fee-row total">
              <span>Total</span>
              <span id="summaryTotal">Free</span>
            </div>
          </div>
          <div style="margin-top:12px;padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:12px;color:#92400e;display:flex;align-items:flex-start;gap:8px;">
            <i class="fa-solid fa-receipt" style="margin-top:2px;flex-shrink:0;"></i>
            <span>A receipt will be issued by the barangay staff upon approval and payment processing.</span>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" id="backBtn" onclick="prevStep()" style="display:none;">
          <i class="fa-solid fa-arrow-left"></i> Back
        </button>
        <button class="btn btn-primary" id="nextBtn" onclick="nextStep()">
          Next <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const residentAddress = <?= json_encode($residentAddress) ?>;
const residentContact = <?= json_encode($residentContact) ?>;

const documents = [
  {
    id: 1, name: "Barangay Clearance",
    desc: "General purpose clearance for residents in good standing.",
    icon: "fa-solid fa-stamp", iconClass: "icon--blue",
    fee: 50, feeLabel: "50", category: ["clearance"], popular: true,
    requirements: [
      { label: "Valid Government ID",           hint: "e.g. PhilSys, Driver's License, Passport, SSS, UMID" },
      { label: "Proof of Residency",            hint: "e.g. Utility bill, lease contract, or barangay certification" },
      { label: "Accomplished Request Form",     hint: "Signed request form from the barangay" }
    ]
  },
  {
    id: 2, name: "Certificate of Indigency",
    desc: "For residents needing assistance or applying for benefits.",
    icon: "fa-solid fa-hand-holding-heart", iconClass: "icon--green",
    fee: 0, feeLabel: "Free", category: ["certificate"], popular: false,
    requirements: [
      { label: "Valid ID",               hint: "Any government-issued ID" },
      { label: "Proof of Residency",     hint: "Utility bill or barangay certification" }
    ]
  },
  {
    id: 3, name: "Certificate of Residency",
    desc: "Confirms that an individual is a resident of the barangay.",
    icon: "fa-solid fa-house-circle-check", iconClass: "icon--teal",
    fee: 30, feeLabel: "30", category: ["certificate"], popular: false,
    requirements: [
      { label: "Valid Government ID",    hint: "Any government-issued ID" },
      { label: "Proof of Address",       hint: "Electric or water bill showing your address" }
    ]
  },
  {
    id: 4, name: "Business Permit Clearance",
    desc: "Required for businesses operating within the barangay.",
    icon: "fa-solid fa-briefcase", iconClass: "icon--yellow",
    fee: 200, feeLabel: "200", category: ["permit"], popular: false,
    requirements: [
      { label: "DTI or SEC Registration",      hint: "Business registration certificate" },
      { label: "Valid ID of Owner",             hint: "Government-issued ID of the business owner" },
      { label: "Sketch of Business Location",   hint: "Hand-drawn or printed location map" }
    ]
  },
  {
    id: 5, name: "Barangay ID",
    desc: "Official barangay identification card for residents.",
    icon: "fa-solid fa-id-card", iconClass: "icon--purple",
    fee: 100, feeLabel: "100", category: [], popular: true,
    requirements: [
      { label: "1x1 ID Picture (white background)", hint: "Recent photo with white background" },
      { label: "Proof of Residency",                hint: "Utility bill or lease contract" },
      { label: "Valid Government ID",               hint: "Any government-issued ID" }
    ]
  },
  {
    id: 6, name: "Certificate of Good Moral",
    desc: "Attests to the good moral character of the resident.",
    icon: "fa-solid fa-award", iconClass: "icon--blue",
    fee: 30, feeLabel: "30", category: ["certificate"], popular: false,
    requirements: [
      { label: "Valid Government ID", hint: "Any government-issued ID" },
      { label: "Proof of Address",    hint: "Utility bill or barangay certification" }
    ]
  },
  {
    id: 7, name: "Solo Parent Certificate",
    desc: "For solo parents availing of government benefits and assistance.",
    icon: "fa-solid fa-person-breastfeeding", iconClass: "icon--red",
    fee: 0, feeLabel: "Free", category: ["certificate"], popular: false,
    requirements: [
      { label: "Birth Certificate of Child/Children", hint: "PSA-issued birth certificate" },
      { label: "Valid ID",                            hint: "Any government-issued ID" },
      { label: "Proof of being a solo parent",        hint: "Death certificate, annulment papers, or affidavit" }
    ]
  },
  {
    id: 8, name: "Senior Citizen Certificate",
    desc: "Certificate for senior citizens (60 years old and above).",
    icon: "fa-solid fa-user-clock", iconClass: "icon--green",
    fee: 0, feeLabel: "Free", category: ["certificate"], popular: false,
    requirements: [
      { label: "Valid ID with birthdate",  hint: "Any ID showing date of birth" },
      { label: "Proof of Residency",       hint: "Utility bill or barangay record" }
    ]
  }
];

let currentDoc      = null;
let currentStep     = 1;
let selectedDelivery = '';
let selectedPayment  = '';
let activeFilter     = 'all';

function renderGrid(list) {
  const grid    = document.getElementById('docGrid');
  const noRes   = document.getElementById('noResults');
  const query   = document.getElementById('searchInput').value.trim();
  grid.innerHTML = '';
  if (!list.length) {
    noRes.classList.add('visible');
    document.getElementById('noResultsQuery').textContent = query;
    return;
  }
  noRes.classList.remove('visible');
  list.forEach(doc => {
    const feeHtml = doc.fee === 0
      ? '<span class="doc-card-fee fee--free"><i class="fa-solid fa-circle-check"></i> Free</span>'
      : '<span class="doc-card-fee fee--paid">&#8369;' + doc.fee + '</span>';
    const badgeHtml = doc.popular ? '<span class="doc-card-badge badge--popular">Popular</span>' : '';
    grid.innerHTML += `
      <div class="doc-card" onclick="openModal(${doc.id})">
        ${badgeHtml}
        <div class="doc-card-icon ${doc.iconClass}"><i class="${doc.icon}"></i></div>
        <div class="doc-card-name">${doc.name}</div>
        <div class="doc-card-desc">${doc.desc}</div>
        ${feeHtml}
      </div>`;
  });
}

function filterDocs() {
  const q    = document.getElementById('searchInput').value.trim().toLowerCase();
  let   list = documents;
  if (activeFilter === 'free')        list = list.filter(d => d.fee === 0);
  else if (activeFilter === 'paid')   list = list.filter(d => d.fee > 0);
  else if (activeFilter !== 'all')    list = list.filter(d => d.category.includes(activeFilter));
  if (q) list = list.filter(d => d.name.toLowerCase().includes(q) || d.desc.toLowerCase().includes(q));
  renderGrid(list);
}

document.getElementById('searchInput').addEventListener('input', filterDocs);
document.querySelectorAll('.pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    activeFilter = pill.dataset.filter;
    filterDocs();
  });
});

function openModal(docId) {
  currentDoc      = documents.find(d => d.id === docId);
  selectedDelivery = '';
  selectedPayment  = '';
  if (!currentDoc) return;

  document.getElementById('modalTitle').textContent    = currentDoc.name;
  document.getElementById('modalSubtitle').textContent = currentDoc.requirements.length + ' requirement(s) needed';
  document.getElementById('modalDocIcon').className    = 'modal-doc-icon ' + currentDoc.iconClass;
  document.getElementById('modalDocIcon').innerHTML    = '<i class="' + currentDoc.icon + '"></i>';
  document.getElementById('summaryDocName').textContent = currentDoc.name;
  document.getElementById('summaryDocFee').innerHTML   = currentDoc.fee === 0 ? 'Free' : '&#8369;' + currentDoc.fee;
  document.getElementById('notesInput').value          = '';

  const list = document.getElementById('reqUploadList');
  list.innerHTML = '';
  currentDoc.requirements.forEach((req, i) => {
    list.innerHTML += `
      <div class="req-upload-item">
        <div class="req-upload-label">
          <i class="fa-solid fa-file-arrow-up" style="color:#2563eb;font-size:12px;"></i>
          ${req.label} <span class="req-required">*</span>
        </div>
        <input type="file" class="req-file-input" id="req_file_${i}"
          name="req_file_${i}" accept=".jpg,.jpeg,.png,.pdf,.heic,.webp"
          onchange="onFileChange(this, ${i})" />
        <div class="req-file-hint">${req.hint}</div>
        <div class="req-file-preview" id="req_preview_${i}">
          <i class="fa-solid fa-circle-check" style="color:#15803d;"></i>
          <span id="req_preview_name_${i}"></span>
        </div>
      </div>`;
  });

  document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
  document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('selected'));
  document.getElementById('deliveryAddressSection').classList.add('hidden');
  document.getElementById('pickupInfoBox').classList.add('hidden');
  document.getElementById('successState').classList.remove('visible');
  document.getElementById('formScreen').style.display = '';

  showStep(1);
  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

function onFileChange(input, i) {
  const preview     = document.getElementById('req_preview_' + i);
  const previewName = document.getElementById('req_preview_name_' + i);
  if (input.files && input.files[0]) {
    previewName.textContent = input.files[0].name;
    preview.classList.add('visible');
    input.classList.remove('input-error');
  } else {
    preview.classList.remove('visible');
  }
}

function showStep(n) {
  currentStep = n;
  ['step1','step2','step3'].forEach((id, idx) => {
    document.getElementById(id).classList.toggle('hidden', idx + 1 !== n);
  });
  ['step1dot','step2dot','step3dot'].forEach((id, i) => {
    const dot = document.getElementById(id);
    dot.classList.remove('active','done');
    if (i < n - 1)      { dot.classList.add('done');   dot.innerHTML = '<i class="fa-solid fa-check" style="font-size:0.65rem"></i>'; }
    else if (i === n - 1){ dot.classList.add('active'); dot.textContent = i + 1; }
    else                 { dot.textContent = i + 1; }
  });
  document.getElementById('line12').classList.toggle('done', n > 1);
  document.getElementById('line23').classList.toggle('done', n > 2);
  document.getElementById('backBtn').style.display = n > 1 ? '' : 'none';
  const nextBtn = document.getElementById('nextBtn');
  if (n === 3) {
    nextBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
    nextBtn.className = 'btn btn-success';
  } else {
    nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right"></i>';
    nextBtn.className = 'btn btn-primary';
  }
  if (n === 3) {
    const isFree      = currentDoc.fee === 0;
    document.getElementById('freeDocSection').classList.toggle('hidden', !isFree);
    document.getElementById('paidDocSection').classList.toggle('hidden', isFree);
    const deliveryFee = selectedDelivery === 'delivery' ? 50 : 0;
    document.getElementById('deliveryFeeRow').style.display = deliveryFee ? '' : 'none';
    const total = currentDoc.fee + deliveryFee;
    document.getElementById('summaryTotal').innerHTML = total === 0 ? 'Free' : '&#8369;' + total;
    document.getElementById('cashDeliveryNote').classList.add('hidden');
  }
}

function validatePhone(input) {
  const val     = input.value.replace(/\s/g, '');
  const errMsg  = document.getElementById('phoneError');
  const isValid = /^09\d{9}$/.test(val);
  if (!isValid && val.length > 0) {
    input.classList.add('input-error');
    errMsg.classList.add('visible');
  } else {
    input.classList.remove('input-error');
    errMsg.classList.remove('visible');
  }
  input.value = input.value.replace(/[^0-9]/g, '');
}

function nextStep() {
  if (currentStep === 1) {
    const count       = currentDoc.requirements.length;
    let allUploaded   = true;
    for (let i = 0; i < count; i++) {
      const fileInput = document.getElementById('req_file_' + i);
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        fileInput.classList.add('input-error');
        allUploaded = false;
      } else {
        fileInput.classList.remove('input-error');
      }
    }
    if (!allUploaded) {
      alert('Please upload all required documents before proceeding.');
      return;
    }
    showStep(2);

  } else if (currentStep === 2) {
    if (!selectedDelivery) {
      alert('Please select how you would like to receive your document.');
      return;
    }
    if (selectedDelivery === 'delivery') {
      const addr    = document.getElementById('deliveryAddress').value.trim();
      const phone   = document.getElementById('contactNumber').value.replace(/\s/g, '');
      const isValid = /^09\d{9}$/.test(phone);
      if (!addr) {
        document.getElementById('deliveryAddress').focus();
        document.getElementById('deliveryAddress').classList.add('input-error');
        return;
      }
      if (!isValid) {
        document.getElementById('contactNumber').focus();
        document.getElementById('contactNumber').classList.add('input-error');
        document.getElementById('phoneError').classList.add('visible');
        return;
      }
    }
    showStep(3);

  } else if (currentStep === 3) {
    const deliveryFee  = selectedDelivery === 'delivery' ? 50 : 0;
    const needsPayment = (currentDoc.fee > 0 || deliveryFee > 0);
    if (needsPayment && !selectedPayment) {
      alert('Please select a payment method.');
      return;
    }
    submitRequest();
  }
}

function prevStep() { if (currentStep > 1) showStep(currentStep - 1); }

function selectDelivery(type) {
  selectedDelivery = type;
  document.querySelectorAll('.choice-card').forEach(c => c.classList.toggle('selected', c.dataset.delivery === type));
  document.getElementById('deliveryAddressSection').classList.toggle('hidden', type !== 'delivery');
  document.getElementById('pickupInfoBox').classList.toggle('hidden', type !== 'pickup');
  if (type === 'delivery') {
    document.getElementById('deliveryAddress').value = residentAddress;
    document.getElementById('contactNumber').value   = residentContact;
  }
}

function selectPayment(type) {
  selectedPayment = type;
  document.querySelectorAll('.payment-option').forEach(p => p.classList.toggle('selected', p.dataset.pay === type));
  document.getElementById('cashDeliveryNote').classList.toggle('hidden', !(type === 'cash' && selectedDelivery === 'delivery'));
}

function submitRequest() {
  const nextBtn     = document.getElementById('nextBtn');
  nextBtn.disabled  = true;
  nextBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';

  const data = new FormData();
  data.append('action',           'submit_request');
  data.append('document_type',    currentDoc.name);
  data.append('notes',            document.getElementById('notesInput').value);
  data.append('delivery_method',  selectedDelivery);
  data.append('delivery_address', selectedDelivery === 'delivery' ? document.getElementById('deliveryAddress').value : '');
  data.append('contact_number',   selectedDelivery === 'delivery' ? document.getElementById('contactNumber').value  : '');
  data.append('payment_method',   selectedPayment || '');

  currentDoc.requirements.forEach(function(r, i) {
    const fileInput = document.getElementById('req_file_' + i);
    if (fileInput && fileInput.files && fileInput.files[0]) {
      data.append('req_file_' + i,  fileInput.files[0]);
      data.append('req_label_' + i, r.label);
    }
  });

  fetch('residentrequestdocument.php', { method: 'POST', body: data })
    .then(function(res) { return res.json(); })
    .then(function(res) {
      if (res.success) {
        if (res.payment_required && res.checkout_url) {
          window.location.href = res.checkout_url;
          return;
        }

        document.getElementById('requestIdDisplay').textContent = res.request_id;
        document.getElementById('formScreen').style.display    = 'none';
        document.getElementById('successState').classList.add('visible');
      } else {
        let errorMessage = 'Error: ' + res.error;

        if (res.paymongo_error) {
          console.log(res.paymongo_error);
        }

        alert(errorMessage);
        nextBtn.disabled  = false;
        nextBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
      }
    })
    .catch(function() {
      alert('A network error occurred. Please try again.');
      nextBtn.disabled  = false;
      nextBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
    });
}

function toggleNotif() { document.getElementById('notifDropdown').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const btn = document.getElementById('bellBtn');
  const dd  = document.getElementById('notifDropdown');
  if (btn && dd && !btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});

function handleNotifClick(notifId, refId, typeKey, btn) {
  if (btn.classList.contains('unread')) {
    btn.classList.remove('unread');
    const dot = document.getElementById('notif-dot-' + notifId);
    if (dot) dot.remove();
    const countEl = document.querySelector('[style*="border:2px solid var(--bg)"]');
    if (countEl) {
      const cur = parseInt(countEl.textContent) - 1;
      if (cur <= 0) countEl.remove(); else countEl.textContent = cur;
    }
    const fd = new FormData();
    fd.append('action', 'read_notif');
    fd.append('notif_id', notifId);
    fd.append('ajax', '1');
    fetch('residentrequestdocument.php', { method: 'POST', body: fd }).catch(() => {});
  }
  document.getElementById('notifDropdown').classList.remove('open');
  if ((typeKey === 'LIKE' || typeKey === 'COMMENT') && refId > 0) {
    window.location.href = 'residentcommunity.php#post-' + refId;
  } else if (typeKey === 'ANNOUNCEMENT') {
    window.location.href = 'residentdashboard.php';
  } else if (typeKey === 'CONCERN') {
    window.location.href = 'residentconcern.php';
  } else if (typeKey === 'REQUEST') {
    window.location.href = 'residentrequest.php';
  }
}

renderGrid(documents);
</script>
</body>
</html>