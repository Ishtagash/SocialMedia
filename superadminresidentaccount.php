<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

require_once 'email_helper.php';

$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']    ?? '';
    $targetId = (int)($_POST['target_id'] ?? 0);
    $now      = date('Y-m-d H:i:s');

    if ($action === 'get_resident' && $targetId) {
        header('Content-Type: application/json');
        error_reporting(0);

        $stmt = sqlsrv_query($conn,
            "SELECT U.USER_ID, U.USERNAME, U.EMAIL, U.STATUS, U.CREATED_AT, U.LAST_LOGIN,
                    R.FIRST_NAME, R.LAST_NAME, R.MIDDLE_NAME, R.SUFFIX,
                    R.BIRTHDATE, R.GENDER, R.MOBILE_NUMBER,
                    R.ADDRESS, R.ID_TYPE, R.ID_PHOTO_PATH, R.PROFILE_PICTURE
             FROM USERS U
             LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
             WHERE U.USER_ID = ? AND U.ROLE = 'resident'",
            [$targetId]
        );

        if ($stmt === false) {
            echo json_encode(['error' => 'Query failed: ' . print_r(sqlsrv_errors(), true)]);
            exit();
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['error' => 'Resident not found in database.']);
            exit();
        }

        function nullOrDash($val) {
            $v = rtrim($val ?? '');
            return $v === '' ? '——' : $v;
        }

        $firstName = rtrim($row['FIRST_NAME']  ?? '');
        $lastName  = rtrim($row['LAST_NAME']   ?? '');
        $midName   = rtrim($row['MIDDLE_NAME'] ?? '');
        $suffix    = rtrim($row['SUFFIX']      ?? '');

        $birth = '——';
        if ($row['BIRTHDATE'] instanceof DateTime) {
            $birth = $row['BIRTHDATE']->format('F j, Y');
        } elseif (!empty($row['BIRTHDATE'])) {
            $birth = date('F j, Y', strtotime($row['BIRTHDATE']));
        }

        $regDate = '——';
        if ($row['CREATED_AT'] instanceof DateTime) {
            $regDate = $row['CREATED_AT']->format('M d, Y g:i A');
        } elseif (!empty($row['CREATED_AT'])) {
            $regDate = date('M d, Y g:i A', strtotime($row['CREATED_AT']));
        }

        $lastLogin = '——';
        if ($row['LAST_LOGIN'] instanceof DateTime) {
            $lastLogin = $row['LAST_LOGIN']->format('M d, Y g:i A');
        } elseif (!empty($row['LAST_LOGIN'])) {
            $lastLogin = date('M d, Y g:i A', strtotime($row['LAST_LOGIN']));
        }

        $fullName = trim(
            implode(' ', array_filter([$firstName, $midName, $lastName])) .
            ($suffix && rtrim($suffix) !== '' ? ' ' . rtrim($suffix) : '')
        );

        echo json_encode([
            'userId'     => (int)$row['USER_ID'],
            'username'   => nullOrDash($row['USERNAME']),
            'firstName'  => $firstName !== '' ? $firstName : '——',
            'lastName'   => $lastName  !== '' ? $lastName  : '——',
            'midName'    => $midName   !== '' ? $midName   : '——',
            'suffix'     => $suffix    !== '' ? rtrim($suffix) : '——',
            'fullName'   => $fullName  !== '' ? $fullName  : '——',
            'email'      => nullOrDash($row['EMAIL']),
            'gender'     => nullOrDash($row['GENDER']),
            'birthdate'  => $birth,
            'mobile'     => nullOrDash($row['MOBILE_NUMBER']),
            'address'    => nullOrDash($row['ADDRESS']),
            'idType'     => nullOrDash($row['ID_TYPE']),
            'idPhoto'         => rtrim($row['ID_PHOTO_PATH']   ?? ''),
            'profilePic'      => rtrim($row['PROFILE_PICTURE'] ?? ''),
            'status'     => strtolower(rtrim($row['STATUS'] ?? '')),
            'registered' => $regDate,
            'lastLogin'  => $lastLogin,
            'initials'   => strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)),
        ]);
        exit();
    }

    if ($action === 'verify' && $targetId) {
        $nr = sqlsrv_fetch_array(sqlsrv_query($conn,
            "SELECT R.FIRST_NAME, R.LAST_NAME, U.EMAIL FROM REGISTRATION R
             JOIN USERS U ON U.USER_ID = R.USER_ID WHERE R.USER_ID = ?", [$targetId]), SQLSRV_FETCH_ASSOC);
        $rName  = rtrim($nr['FIRST_NAME'] ?? '') . ' ' . rtrim($nr['LAST_NAME'] ?? '');
        $rName  = trim($rName) ?: "User #$targetId";
        $rEmail = rtrim($nr['EMAIL'] ?? '');
        sqlsrv_query($conn, "UPDATE USERS SET STATUS = 'active' WHERE USER_ID = ? AND ROLE = 'resident'", [$targetId]);
        sqlsrv_query($conn, "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Verify Resident', ?, ?)",
            [$userId, "Verified $rName — account set to active", $now]);
        if ($rEmail) { sendAccountNotification($rEmail, $rName, 'approved'); }
        header("Location: superadminresidentaccount.php?success=" . urlencode("$rName has been verified successfully.")); exit();
    }

    if ($action === 'reject' && $targetId) {
        $nr = sqlsrv_fetch_array(sqlsrv_query($conn,
            "SELECT R.FIRST_NAME, R.LAST_NAME, U.EMAIL FROM REGISTRATION R
             JOIN USERS U ON U.USER_ID = R.USER_ID WHERE R.USER_ID = ?", [$targetId]), SQLSRV_FETCH_ASSOC);
        $rName  = rtrim($nr['FIRST_NAME'] ?? '') . ' ' . rtrim($nr['LAST_NAME'] ?? '');
        $rName  = trim($rName) ?: "User #$targetId";
        $rEmail = rtrim($nr['EMAIL'] ?? '');
        sqlsrv_query($conn, "UPDATE USERS SET STATUS = 'rejected' WHERE USER_ID = ? AND ROLE = 'resident'", [$targetId]);
        sqlsrv_query($conn, "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Reject Resident', ?, ?)",
            [$userId, "Rejected $rName — account denied", $now]);
        if ($rEmail) { sendAccountNotification($rEmail, $rName, 'rejected'); }
        header("Location: superadminresidentaccount.php?success=" . urlencode("$rName's account has been rejected.")); exit();
    }

    if ($action === 'toggle_status' && $targetId) {
        $newStatus = trim($_POST['new_status'] ?? '');
        if (in_array($newStatus, ['active', 'inactive'])) {
            $nr = sqlsrv_fetch_array(sqlsrv_query($conn,
                "SELECT R.FIRST_NAME, R.LAST_NAME, U.EMAIL FROM REGISTRATION R
                 JOIN USERS U ON U.USER_ID = R.USER_ID WHERE R.USER_ID = ?", [$targetId]), SQLSRV_FETCH_ASSOC);
            $rName  = rtrim($nr['FIRST_NAME'] ?? '') . ' ' . rtrim($nr['LAST_NAME'] ?? '');
            $rName  = trim($rName) ?: "User #$targetId";
            $rEmail = rtrim($nr['EMAIL'] ?? '');
            $label  = $newStatus === 'active' ? 'Enabled' : 'Disabled';
            sqlsrv_query($conn, "UPDATE USERS SET STATUS = ? WHERE USER_ID = ? AND ROLE = 'resident'", [$newStatus, $targetId]);
            sqlsrv_query($conn, "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, ?, ?, ?)",
                [$userId, "$label Resident Account", "$label $rName's account — set to $newStatus", $now]);
            if ($rEmail) {
                sendAccountNotification($rEmail, $rName, $newStatus === 'active' ? 'enabled' : 'disabled');
            }
            header("Location: superadminresidentaccount.php?success=" . urlencode("$rName's account has been $label.")); exit();
        }
    }
}

if (isset($_GET['msg']))     { $message = htmlspecialchars($_GET['msg']);     $messageType = 'success'; }
$successModal = isset($_GET['success']) ? $_GET['success'] : '';

$search       = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['filter']  ?? '');

$filterParams = [];
$filterSql = "SELECT U.USER_ID, U.STATUS, U.CREATED_AT,
                     R.FIRST_NAME, R.LAST_NAME, R.MOBILE_NUMBER, R.ADDRESS
              FROM USERS U
              LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
              WHERE U.ROLE = 'resident'";
if ($search) {
    $like = '%' . $search . '%';
    $filterSql .= " AND (R.FIRST_NAME LIKE ? OR R.LAST_NAME LIKE ? OR U.EMAIL LIKE ? OR R.MOBILE_NUMBER LIKE ?)";
    $filterParams[] = $like; $filterParams[] = $like; $filterParams[] = $like; $filterParams[] = $like;
}
if ($filterStatus) { $filterSql .= " AND U.STATUS = ?"; $filterParams[] = $filterStatus; }
$filterSql .= " ORDER BY U.CREATED_AT DESC";

$residentResult = sqlsrv_query($conn, $filterSql, $filterParams ?: []);
$residents = [];
if ($residentResult) { while ($r = sqlsrv_fetch_array($residentResult, SQLSRV_FETCH_ASSOC)) { $residents[] = $r; } }

$pendingCount = 0;
$pcStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'resident' AND LTRIM(RTRIM(STATUS)) = 'pending'");
if ($pcStmt) {
    $pcRow = sqlsrv_fetch_array($pcStmt, SQLSRV_FETCH_ASSOC);
    $pendingCount = (int)($pcRow['CNT'] ?? 0);
}
if ($pendingCount === 0) {
    foreach ($residents as $_r) {
        if (strtolower(trim($_r['STATUS'] ?? '')) === 'pending') $pendingCount++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resident Management — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="superadmin.css" />
  <style>
    .resident-wrap{display:flex;flex-direction:column;gap:20px}
    .resident-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .resident-panel-toolbar{padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02)}
    .resident-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 18px;border-bottom:1px solid var(--border)}
    .resident-panel-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0;display:flex;align-items:center;gap:8px}
    .resident-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .resident-search{flex:1;min-width:220px;height:40px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .resident-search i{color:var(--text-muted);font-size:13px}
    .resident-search input{flex:1;border:none;outline:none;background:transparent;font-family:inherit;font-size:13px;color:var(--text)}
    .resident-select{height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;min-width:140px}
    .resident-table-wrap{width:100%;overflow-x:auto}
    .resident-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:999px;background:rgba(245,158,11,.12);color:#b45309;font-size:11px;font-weight:700;padding:0 6px}

    /* MODAL */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,22,80,.55);z-index:300;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.open{display:flex}
    .resident-modal{background:var(--surface);border-radius:16px;width:100%;max-width:680px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 12px 48px rgba(5,22,80,.24)}
    .resident-modal-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 24px;flex-shrink:0;background:var(--navy)}
    .resident-modal-header h3{font-size:15px;font-weight:700;color:#fff;margin:0;display:flex;align-items:center;gap:9px}
    .modal-close-btn{width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);cursor:pointer;font-size:15px;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .modal-close-btn:hover{background:rgba(255,255,255,.22)}
    .resident-modal-body{flex:1;overflow-y:auto;display:flex;flex-direction:column}
    .modal-loading{display:flex;align-items:center;justify-content:center;gap:10px;padding:60px 0;color:var(--text-muted);font-size:14px}

    .profile-hero{display:flex;align-items:center;gap:18px;padding:22px 24px;background:rgba(5,22,80,.025);border-bottom:1px solid var(--border);flex-shrink:0}
    .profile-avatar{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--border);flex-shrink:0}
    .profile-avatar-placeholder{width:72px;height:72px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;flex-shrink:0}
    .profile-hero-name{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:3px}
    .profile-hero-email{font-size:13px;color:var(--text-muted);margin-bottom:8px}
    .profile-hero-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

    .modal-sections{padding:20px 24px 24px;display:flex;flex-direction:column;gap:20px}
    .modal-section-block{display:flex;flex-direction:column;gap:10px}
    .modal-section-title{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;padding-bottom:8px;border-bottom:1px solid var(--border);margin-bottom:2px}
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px}
    .detail-field{display:flex;flex-direction:column;gap:3px}
    .detail-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px}
    .detail-value{font-size:14px;color:var(--text);font-weight:500;word-break:break-word}
    .detail-value.is-empty{color:var(--text-muted);font-style:italic;font-weight:400}
    .detail-full{grid-column:1/-1}
    .id-photo-img{max-width:100%;border-radius:10px;border:1px solid var(--border);display:block;margin-top:6px}
    .id-photo-none{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);padding:14px 16px;background:rgba(5,22,80,.03);border-radius:10px;border:1px dashed var(--border);margin-top:6px}

    .resident-modal-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 24px;border-top:1px solid var(--border);background:rgba(5,22,80,.02);flex-shrink:0;flex-wrap:wrap}
    .footer-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .btn-verify{background:var(--green);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
    .btn-verify:hover{opacity:.87}
    .btn-reject{background:rgba(255,77,77,.08);color:var(--red);border:1px solid rgba(255,77,77,.3);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
    .btn-reject:hover{background:rgba(255,77,77,.14)}
    .btn-disable{background:var(--surface);color:var(--navy);border:1px solid var(--border);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
    .btn-disable:hover{background:rgba(5,22,80,.05)}
    .btn-enable{background:var(--green);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
    .btn-enable:hover{opacity:.87}
    .btn-goback{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.2);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
    .btn-goback:hover{background:rgba(5,22,80,.05)}

    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-logout{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-logout{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .msg-banner{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px}
    .msg-success{background:rgba(34,197,94,.12);color:#16a34a;border:1px solid rgba(34,197,94,.25)}
    .msg-error{background:rgba(255,77,77,.1);color:var(--red);border:1px solid rgba(255,77,77,.25)}
    .success-modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,22,80,.5);z-index:600;align-items:center;justify-content:center;padding:20px}
    .success-modal-overlay.open{display:flex}
    .success-modal-box{background:#fff;border-radius:14px;padding:36px 30px;max-width:380px;width:90%;text-align:center;box-shadow:0 12px 48px rgba(5,22,80,.22);border-top:4px solid var(--green)}
    .success-modal-icon{width:56px;height:56px;border-radius:50%;background:rgba(34,197,94,.12);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .success-modal-box h3{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .success-modal-box p{font-size:13px;color:var(--text-muted);margin-bottom:22px;line-height:1.6}
    .success-modal-btn{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    @media(max-width:1100px){.resident-layout{grid-template-columns:1fr}}
    @media(max-width:560px){.detail-grid{grid-template-columns:1fr}}
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-btns">
      <button class="btn-cancel-logout" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm-logout"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="success-modal-overlay" id="successModal">
  <div class="success-modal-box">
    <div class="success-modal-icon"><i class="fa-solid fa-circle-check"></i></div>
    <h3>Action Successful</h3>
    <p id="successModalMsg"></p>
    <button class="success-modal-btn" onclick="closeSuccessModal()">Done</button>
  </div>
</div>

<div class="modal-overlay" id="viewModal">
  <div class="resident-modal">
    <div class="resident-modal-header">
      <h3><i class="fa-solid fa-user-check"></i> Resident Profile Verification</h3>
      <button class="modal-close-btn" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="resident-modal-body" id="modalBody">
      <div class="modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading resident data...</div>
    </div>
    <div class="resident-modal-footer" id="modalFooter" style="display:none;"></div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand">
      <h1>Barangay Alapan 1-A</h1>
      <p>Super Admin</p>
    </div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php" class="active">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php">Reports</a>
      <a href="superadminauditlogs.php">Audit Logs</a>
    </nav>
    <div class="superadmin-header-right">
      <div class="superadmin-user">
        <div class="superadmin-user-info">
          <span class="superadmin-user-name"><?= $displayName ?></span>
        </div>
      </div>
      <a href="#" class="superadmin-logout" onclick="openLogout(); return false;">Logout</a>
    </div>
  </header>

  <main class="superadmin-content">
    <?php if ($message): ?>
    <div class="msg-banner msg-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="resident-wrap">
      <div class="resident-panel">
          <form method="GET">
            <div class="resident-panel-toolbar">
              <div class="resident-toolbar">
                <div class="resident-search">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" name="search" placeholder="Search name, contact, or address..."
                    value="<?= htmlspecialchars($search) ?>">
                </div>
                <select class="resident-select" name="filter" onchange="this.form.submit()">
                  <option value="">All Status</option>
                  <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                  <option value="pending"  <?= $filterStatus === 'pending'  ? 'selected' : '' ?>>Pending</option>
                  <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Disabled</option>
                  <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <button type="submit" class="superadmin-primary-btn" style="min-height:40px;padding:0 16px;">
                  <i class="fa-solid fa-magnifying-glass"></i>
                </button>
              </div>
            </div>
          </form>

          <div class="resident-panel-head">
            <h4>
              Resident Accounts
              <?php if ($pendingCount > 0): ?>
              <span class="resident-count-badge"><?= $pendingCount ?> pending</span>
              <?php endif; ?>
            </h4>
          </div>

          <div class="resident-table-wrap">
            <table class="superadmin-table" style="min-width:640px;">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Address</th>
                  <th>Status</th>
                  <th>Registered</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($residents)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);">No residents found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($residents as $res):
                  $rFirst   = rtrim($res['FIRST_NAME']    ?? '');
                  $rLast    = rtrim($res['LAST_NAME']     ?? '');
                  $rName    = htmlspecialchars(trim("$rFirst $rLast") ?: '——');
                  $rContact = htmlspecialchars(rtrim($res['MOBILE_NUMBER'] ?? '') ?: '——');
                  $rRaw     = rtrim($res['ADDRESS'] ?? '');
                  $rAddress = htmlspecialchars($rRaw !== '' ? mb_strimwidth($rRaw, 0, 30, '...') : '——');
                  $rStatus  = strtolower(rtrim($res['STATUS'] ?? ''));
                  $rDate    = '';
                  if ($res['CREATED_AT'] instanceof DateTime) {
                      $rDate = $res['CREATED_AT']->format('M d, Y');
                  } elseif (!empty($res['CREATED_AT'])) {
                      $rDate = date('M d, Y', strtotime($res['CREATED_AT']));
                  } else {
                      $rDate = '——';
                  }
                  $rId = (int)$res['USER_ID'];
                ?>
                <tr>
                  <td><?= $rName ?></td>
                  <td><?= $rContact ?></td>
                  <td><?= $rAddress ?></td>
                  <td>
                    <span class="table-status <?= $rStatus === 'active' ? 'active' : 'inactive' ?>">
                      <?= ucfirst($rStatus) ?>
                    </span>
                  </td>
                  <td><?= $rDate ?></td>
                  <td>
                    <button type="button"
                            class="superadmin-primary-btn"
                            style="min-height:34px;padding:0 14px;font-size:13px;"
                            onclick="openViewModal(<?= $rId ?>)">
                      <i class="fa-solid fa-eye"></i> View
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
      </div>
    </div>
  </main>
</div>

<script>
function openViewModal(uid) {
  var modal  = document.getElementById('viewModal');
  var body   = document.getElementById('modalBody');
  var footer = document.getElementById('modalFooter');

  body.innerHTML = '<div class="modal-loading"><i class="fa-solid fa-spinner fa-spin"></i>\u2002Loading resident data...</div>';
  footer.style.display = 'none';
  footer.innerHTML = '';
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';

  var fd = new FormData();
  fd.append('action', 'get_resident');
  fd.append('target_id', uid);

  fetch('superadminresidentaccount.php', { method: 'POST', body: fd })
    .then(function(r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(function(d) {
      if (d.error) {
        body.innerHTML = '<div class="modal-loading" style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i>\u2002' + escH(d.error) + '</div>';
        return;
      }
      renderModal(d);
    })
    .catch(function(err) {
      body.innerHTML = '<div class="modal-loading" style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i>\u2002Failed to load data. (' + escH(err.message) + ')</div>';
    });
}

function renderModal(d) {
  var body   = document.getElementById('modalBody');
  var footer = document.getElementById('modalFooter');

  var statusCls   = d.status === 'active' ? 'active' : 'inactive';
  var statusLabel = d.status ? (d.status.charAt(0).toUpperCase() + d.status.slice(1)) : '\u2014';

  var avatarHtml = d.profilePic
    ? '<img src="' + escH(d.profilePic) + '" class="profile-avatar" alt="Profile photo" onerror="this.style.display=\'none\'">'
    : '<div class="profile-avatar-placeholder">' + escH(d.initials || '?') + '</div>';

  var idPhotoHtml = d.idPhoto
    ? '<img src="' + escH(d.idPhoto) + '" alt="Government ID" class="id-photo-img">'
    : '<div class="id-photo-none"><i class="fa-solid fa-id-card"></i>\u2002No ID photo uploaded</div>';

  body.innerHTML =
    '<div class="profile-hero">' +
      avatarHtml +
      '<div>' +
        '<div class="profile-hero-name">' + escH(d.fullName) + '</div>' +
        '<div class="profile-hero-email"><i class="fa-solid fa-envelope" style="margin-right:5px;font-size:11px;opacity:.6;"></i>' + escH(d.email) + '</div>' +
        '<div class="profile-hero-meta">' +
          '<span class="table-status ' + statusCls + '">' + statusLabel + '</span>' +
          '<span style="font-size:12px;color:var(--text-muted);">Registered: ' + escH(d.registered) + '</span>' +
        '</div>' +
      '</div>' +
    '</div>' +

    '<div class="modal-sections">' +

      '<div class="modal-section-block">' +
        '<div class="modal-section-title">Account Information</div>' +
        '<div class="detail-grid">' +
          dv('Username', d.username) +
          dv('Email Address', d.email) +
          dv('Last Login', d.lastLogin) +
          dv('Account Status', statusLabel) +
        '</div>' +
      '</div>' +

      '<div class="modal-section-block">' +
        '<div class="modal-section-title">Personal Information</div>' +
        '<div class="detail-grid">' +
          dv('First Name',    d.firstName) +
          dv('Last Name',     d.lastName) +
          dv('Middle Name',   d.midName) +
          dv('Suffix',        d.suffix) +
          dv('Gender',        d.gender) +
          dv('Date of Birth', d.birthdate) +
        '</div>' +
      '</div>' +

      '<div class="modal-section-block">' +
        '<div class="modal-section-title">Contact &amp; Address</div>' +
        '<div class="detail-grid">' +
          dv('Mobile Number', d.mobile) +
          '<div class="detail-field detail-full"><span class="detail-label">Address</span><span class="detail-value' + (d.address === '\u2014\u2014' ? ' is-empty' : '') + '">' + escH(d.address) + '</span></div>' +
        '</div>' +
      '</div>' +

      '<div class="modal-section-block">' +
        '<div class="modal-section-title">Government ID</div>' +
        '<div class="detail-grid">' +
          dv('ID Type', d.idType) +
        '</div>' +
        idPhotoHtml +
      '</div>' +

    '</div>';

  var leftBtns = '';
  if (d.status === 'pending') {
    leftBtns =
      '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Verify and approve this resident?\')">' +
        '<input type="hidden" name="action" value="verify">' +
        '<input type="hidden" name="target_id" value="' + d.userId + '">' +
        '<button type="submit" class="btn-verify"><i class="fa-solid fa-circle-check"></i> Verify</button>' +
      '</form>' +
      '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Reject this resident account?\')">' +
        '<input type="hidden" name="action" value="reject">' +
        '<input type="hidden" name="target_id" value="' + d.userId + '">' +
        '<button type="submit" class="btn-reject"><i class="fa-solid fa-circle-xmark"></i> Reject</button>' +
      '</form>';
  } else if (d.status === 'active') {
    leftBtns =
      '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Disable this resident account?\')">' +
        '<input type="hidden" name="action" value="toggle_status">' +
        '<input type="hidden" name="target_id" value="' + d.userId + '">' +
        '<input type="hidden" name="new_status" value="inactive">' +
        '<button type="submit" class="btn-disable"><i class="fa-solid fa-ban"></i> Disable Account</button>' +
      '</form>';
  } else if (d.status === 'inactive') {
    leftBtns =
      '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Re-enable this resident account?\')">' +
        '<input type="hidden" name="action" value="toggle_status">' +
        '<input type="hidden" name="target_id" value="' + d.userId + '">' +
        '<input type="hidden" name="new_status" value="active">' +
        '<button type="submit" class="btn-enable"><i class="fa-solid fa-circle-check"></i> Enable Account</button>' +
      '</form>';
  }

  footer.innerHTML =
    '<div class="footer-actions">' + leftBtns + '</div>' +
    '<div class="footer-actions"><button class="btn-goback" onclick="closeViewModal()"><i class="fa-solid fa-arrow-left"></i> Go Back</button></div>';
  footer.style.display = 'flex';
}

function dv(label, value) {
  var isEmpty = (!value || value === '\u2014\u2014');
  return '<div class="detail-field">' +
    '<span class="detail-label">' + escH(label) + '</span>' +
    '<span class="detail-value' + (isEmpty ? ' is-empty' : '') + '">' + escH(String(value || '\u2014\u2014')) + '</span>' +
  '</div>';
}

function escH(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeSuccessModal() {
  document.getElementById('successModal').classList.remove('open');
}
document.getElementById('successModal').addEventListener('click', function(e){
  if (e.target === this) closeSuccessModal();
});

<?php if ($successModal): ?>
window.addEventListener('DOMContentLoaded', function() {
  document.getElementById('successModalMsg').textContent = <?= json_encode($successModal) ?>;
  document.getElementById('successModal').classList.add('open');
});
<?php endif; ?>

function closeViewModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('viewModal').addEventListener('click', function(e) {
  if (e.target === this) closeViewModal();
});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>
</body>
</html>