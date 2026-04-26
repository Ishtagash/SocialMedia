<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("DB connection failed: " . print_r(sqlsrv_errors(), true));
}

$userId = $_SESSION['user_id'];

$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$singlePositions = ['Punong Barangay', 'Secretary', 'Treasurer', 'SK Chairperson'];
$kagawadSlots    = 7;
$message         = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $now    = date('Y-m-d H:i:s');

    if ($action === 'save_assignments') {

        $submitted = [];
        foreach ($singlePositions as $pos) {
            $key             = 'pos_' . strtolower(str_replace([' ','-'], '_', $pos));
            $submitted[$pos] = (int)($_POST[$key] ?? 0);
        }
        $kagawadIds = [];
        for ($i = 1; $i <= $kagawadSlots; $i++) {
            $kid = (int)($_POST["kagawad_$i"] ?? 0);
            if ($kid) $kagawadIds[] = $kid;
        }
        $submitted['Kagawad'] = $kagawadIds;

        $allIds = []; $dupMsg = '';
        foreach ($singlePositions as $pos) {
            $id = $submitted[$pos];
            if ($id) {
                if (in_array($id, $allIds)) { $dupMsg = "Duplicate: same person in multiple positions."; break; }
                $allIds[] = $id;
            }
        }
        foreach ($kagawadIds as $id) {
            if (in_array($id, $allIds)) { $dupMsg = "Duplicate: same person in multiple positions."; break; }
            $allIds[] = $id;
        }

        if ($dupMsg) {
            $message = $dupMsg; $messageType = 'error';
        } else {
            $resetQ = sqlsrv_query($conn,
                "UPDATE USERS SET ROLE = 'resident', POSITION = NULL WHERE ROLE = 'staff' AND USER_ID != ?",
                [$userId]);

            foreach ($singlePositions as $pos) {
                $uid = $submitted[$pos];
                if (!$uid) continue;
                sqlsrv_query($conn,
                    "UPDATE USERS SET ROLE = 'staff', STATUS = 'active', POSITION = ? WHERE USER_ID = ?",
                    [$pos, $uid]);
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Assign Position', ?, ?)",
                    [$userId, "Assigned USER_ID $uid as $pos", $now]);
            }

            foreach (array_unique($kagawadIds) as $uid) {
                sqlsrv_query($conn,
                    "UPDATE USERS SET ROLE = 'staff', STATUS = 'active', POSITION = 'Kagawad' WHERE USER_ID = ?",
                    [$uid]);
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Assign Position', ?, ?)",
                    [$userId, "Assigned USER_ID $uid as Kagawad", $now]);
            }

            header("Location: superadminstaffaccount.php?saved=1");
            exit();
        }
    }

    if ($action === 'toggle_status') {
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? '');
        if ($targetId && in_array($newStatus, ['active','inactive'])) {
            sqlsrv_query($conn,
                "UPDATE USERS SET STATUS = ? WHERE USER_ID = ? AND ROLE = 'staff'",
                [$newStatus, $targetId]);
            $label = $newStatus === 'active' ? 'Enabled' : 'Disabled';
            sqlsrv_query($conn,
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, ?, ?, ?)",
                [$userId, "$label Staff Account", "Staff USER_ID $targetId set to $newStatus", $now]);
            header("Location: superadminstaffaccount.php?msg=" . urlencode("Account $label successfully."));
            exit();
        }
    }
}

$showSavedModal = isset($_GET['saved']) && $_GET['saved'] === '1';
if (isset($_GET['msg'])) { $message = htmlspecialchars($_GET['msg']); $messageType = 'success'; }

$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$staffParams  = [];
$staffSql     = "SELECT U.USER_ID, U.EMAIL, U.USERNAME, U.STATUS, U.LAST_LOGIN, U.POSITION,
                        R.FIRST_NAME, R.LAST_NAME
                 FROM USERS U
                 LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
                 WHERE U.ROLE = 'staff'";
if ($search) {
    $like = '%' . $search . '%';
    $staffSql .= " AND (R.FIRST_NAME LIKE ? OR R.LAST_NAME LIKE ? OR U.EMAIL LIKE ? OR U.USERNAME LIKE ?)";
    $staffParams[] = $like; $staffParams[] = $like; $staffParams[] = $like; $staffParams[] = $like;
}
if ($statusFilter && in_array($statusFilter, ['active','inactive'])) {
    $staffSql .= " AND U.STATUS = ?"; $staffParams[] = $statusFilter;
}
$staffSql   .= " ORDER BY R.LAST_NAME ASC, R.FIRST_NAME ASC";
$staffResult = sqlsrv_query($conn, $staffSql, $staffParams ?: []);
$staffList   = [];
if ($staffResult) { while ($row = sqlsrv_fetch_array($staffResult, SQLSRV_FETCH_ASSOC)) { $staffList[] = $row; } }

$allPeople = [];
$apResult  = sqlsrv_query($conn,
    "SELECT U.USER_ID, U.USERNAME, U.ROLE, U.POSITION, R.FIRST_NAME, R.LAST_NAME
     FROM USERS U
     LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
     WHERE U.ROLE IN ('resident','staff') AND U.USER_ID != ?",
    [$userId]
);
if ($apResult) {
    while ($row = sqlsrv_fetch_array($apResult, SQLSRV_FETCH_ASSOC)) {
        $fn   = rtrim($row['FIRST_NAME'] ?? '');
        $ln   = rtrim($row['LAST_NAME']  ?? '');
        $un   = rtrim($row['USERNAME']   ?? '');
        $name = trim("$fn $ln");
        if ($name === '') $name = $un ?: 'User #' . $row['USER_ID'];
        $row['DISPLAY_NAME'] = $name;
        $allPeople[] = $row;
    }
}

$currentHolders = [];
$ch = sqlsrv_query($conn, "SELECT USER_ID, POSITION FROM USERS WHERE ROLE = 'staff' AND POSITION IS NOT NULL");
if ($ch) {
    while ($row = sqlsrv_fetch_array($ch, SQLSRV_FETCH_ASSOC)) {
        $pos = rtrim($row['POSITION'] ?? '');
        $uid = (int)$row['USER_ID'];
        if (!$pos) continue;
        if ($pos === 'Kagawad') { $currentHolders['Kagawad'][] = $uid; }
        else                    { $currentHolders[$pos]         = $uid; }
    }
}

$totalStaff  = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'staff'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $totalStaff = (int)$row['CNT']; }

$activeStaff = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'staff' AND STATUS = 'active'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $activeStaff = (int)$row['CNT']; }

$disabledStaff = $totalStaff - $activeStaff;

function posKey($p) { return 'pos_' . strtolower(str_replace([' ','-'],'_',$p)); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Accounts — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <style>
    .staff-page-layout{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:20px;align-items:start}
    .staff-left{display:flex;flex-direction:column;gap:20px;min-width:0}
    .staff-right{display:flex;flex-direction:column;gap:14px}
    .positions-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
    .positions-head{padding:16px 20px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02)}
    .positions-head h4{font-size:15px;font-weight:700;color:var(--navy);margin:0 0 3px}
    .positions-head p{font-size:12px;color:var(--text-muted);margin:0;line-height:1.4}
    .positions-body{padding:20px}
    .positions-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;margin-bottom:20px}
    .pos-field{display:flex;flex-direction:column;gap:8px}
    .pos-label{display:flex;align-items:center;gap:8px}
    .pos-label-text{font-size:13px;font-weight:700;color:var(--navy)}
    .pos-badge-assigned{display:inline-flex;align-items:center;height:20px;padding:0 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;background:rgba(34,197,94,.12);color:#166534}
    .pos-select{width:100%;height:42px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;transition:border-color .2s}
    .pos-select:focus{border-color:var(--navy)}
    .pos-select.has-value{border-color:rgba(34,197,94,.5);background:rgba(34,197,94,.04)}
    .kagawad-section{margin-top:4px}
    .kagawad-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .kagawad-title{font-size:13px;font-weight:700;color:var(--navy)}
    .kagawad-count{font-size:12px;color:var(--text-muted);font-weight:600}
    .kagawad-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .kagawad-slot{display:flex;flex-direction:column;gap:5px}
    .kagawad-slot-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.3px}
    .positions-divider{height:1px;background:var(--border);margin:18px 0}
    .positions-foot{display:flex;justify-content:flex-end}
    .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(5,22,80,.5);z-index:500;align-items:center;justify-content:center;padding:20px}
    .modal-backdrop.open{display:flex}
    .modal-box{background:#fff;border-radius:14px;padding:32px 28px;max-width:420px;width:90%;text-align:center;box-shadow:0 12px 48px rgba(5,22,80,.22)}
    .modal-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .modal-icon.navy{background:rgba(5,22,80,.08);color:var(--navy)}
    .modal-icon.green{background:rgba(34,197,94,.12);color:var(--green)}
    .modal-box h3{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .modal-box p{font-size:13px;color:var(--text-muted);margin-bottom:22px;line-height:1.6}
    .modal-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
    .modal-btn-yes{background:var(--navy);color:#fff;border:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .modal-btn-yes:hover{background:var(--navy-mid)}
    .modal-btn-no{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .modal-btn-ok{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .staff-main-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
    .staff-panel-toolbar{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02);flex-wrap:wrap}
    .staff-panel-search{flex:1;min-width:200px;height:40px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .staff-panel-search i{color:var(--text-muted);font-size:13px}
    .staff-panel-search input{flex:1;border:none;outline:none;background:transparent;font-family:inherit;font-size:13px;color:var(--text)}
    .staff-panel-select{height:40px;min-width:130px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none}
    .staff-panel-head{padding:13px 18px;border-bottom:1px solid var(--border)}
    .staff-panel-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .staff-table-wrap{width:100%;overflow-x:auto}
    .pos-chip-table{display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:6px;font-size:11px;font-weight:700;background:rgba(5,22,80,.07);color:var(--navy)}
    .staff-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;box-shadow:var(--shadow)}
    .staff-stat-label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
    .staff-stat-num{display:block;font-size:36px;font-weight:700;color:var(--navy);line-height:1;margin-bottom:6px}
    .staff-stat-note{font-size:12px;color:var(--text-muted);line-height:1.45}
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .msg-banner{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px}
    .msg-success{background:rgba(34,197,94,.12);color:#16a34a;border:1px solid rgba(34,197,94,.25)}
    .msg-error{background:rgba(255,77,77,.1);color:var(--red);border:1px solid rgba(255,77,77,.25)}
    @media(max-width:1200px){.staff-page-layout{grid-template-columns:1fr}.staff-right{display:grid;grid-template-columns:repeat(3,1fr)}}
    @media(max-width:768px){.positions-grid{grid-template-columns:1fr}.kagawad-grid{grid-template-columns:1fr}.staff-right{grid-template-columns:1fr}}
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <div class="logout-btns">
      <button class="btn-cancel-lo" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm-lo"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="confirmModal">
  <div class="modal-box" style="border-top:4px solid var(--navy);">
    <div class="modal-icon navy"><i class="fa-solid fa-users-gear"></i></div>
    <h3>Save Position Changes?</h3>
    <p>This will update all barangay positions. Residents removed from a role will automatically revert. All changes are logged.</p>
    <div class="modal-btns">
      <button class="modal-btn-no" onclick="closeConfirm()">Cancel</button>
      <button class="modal-btn-yes" onclick="document.getElementById('assignForm').submit()">Yes, Save</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="successModal">
  <div class="modal-box" style="border-top:4px solid var(--green);">
    <div class="modal-icon green"><i class="fa-solid fa-circle-check"></i></div>
    <h3>Positions Saved!</h3>
    <p>All barangay official positions have been updated and recorded in the audit log.</p>
    <div class="modal-btns"><button class="modal-btn-ok" onclick="window.location.href='superadminstaffaccount.php'">Done</button></div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand"><h1>Barangay Alapan 1-A</h1><p>Super Admin</p></div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php" class="active">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php">Reports</a>
      <a href="superadminauditlogs.php">Audit Logs</a>
    </nav>
    <div class="superadmin-header-right">
      <div class="superadmin-user"><div class="superadmin-user-info">
        <span class="superadmin-user-name"><?= $displayName ?></span>
      </div></div>
      <a href="#" class="superadmin-logout" onclick="openLogout();return false;">Logout</a>
    </div>
  </header>

  <main class="superadmin-content">
    <?php if ($message): ?>
    <div class="msg-banner msg-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="staff-page-layout">
      <div class="staff-left">

        <div class="positions-panel">
          <div class="positions-head">
            <h4>Barangay Official Positions</h4>
            <p>Assign residents to positions. Once selected, a person is removed from all other dropdowns automatically.</p>
          </div>

          <form method="POST" id="assignForm">
            <input type="hidden" name="action" value="save_assignments">
            <div class="positions-body">

              <div class="positions-grid">
                <?php foreach ($singlePositions as $pos):
                  $key       = posKey($pos);
                  $currentId = $currentHolders[$pos] ?? 0;
                  $isFilled  = $currentId > 0;
                ?>
                <div class="pos-field">
                  <div class="pos-label">
                    <span class="pos-label-text"><?= htmlspecialchars($pos) ?></span>
                    <?php if ($isFilled): ?><span class="pos-badge-assigned">Assigned</span><?php endif; ?>
                  </div>
                  <select name="<?= $key ?>" id="sel_<?= $key ?>"
                          class="pos-select assignment-select <?= $isFilled ? 'has-value' : '' ?>"
                          onchange="syncDropdowns(this)">
                    <option value="">— Select a resident —</option>
                    <?php foreach ($allPeople as $p):
                      $pId   = (int)$p['USER_ID'];
                      $pName = htmlspecialchars($p['DISPLAY_NAME']);
                    ?>
                    <option value="<?= $pId ?>" <?= $pId === $currentId ? 'selected' : '' ?>
                            data-name="<?= $pName ?>"><?= $pName ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="positions-divider"></div>

              <div class="kagawad-section">
                <div class="kagawad-header">
                  <span class="kagawad-title">Kagawad <span style="font-weight:400;color:var(--text-muted);">(up to <?= $kagawadSlots ?>)</span></span>
                  <span class="kagawad-count" id="kagawadCount"><?= count($currentHolders['Kagawad'] ?? []) ?> / <?= $kagawadSlots ?> assigned</span>
                </div>
                <div class="kagawad-grid">
                  <?php
                    $existingKagawads = $currentHolders['Kagawad'] ?? [];
                    for ($slot = 1; $slot <= $kagawadSlots; $slot++):
                      $slotId = $existingKagawads[$slot-1] ?? 0;
                  ?>
                  <div class="kagawad-slot">
                    <span class="kagawad-slot-label">Seat <?= $slot ?></span>
                    <select name="kagawad_<?= $slot ?>" id="sel_kagawad_<?= $slot ?>"
                            class="pos-select assignment-select kagawad-sel <?= $slotId ? 'has-value' : '' ?>"
                            onchange="syncDropdowns(this)">
                      <option value="">— Select a resident —</option>
                      <?php foreach ($allPeople as $p):
                        $pId   = (int)$p['USER_ID'];
                        $pName = htmlspecialchars($p['DISPLAY_NAME']);
                      ?>
                      <option value="<?= $pId ?>" <?= $pId === $slotId ? 'selected' : '' ?>
                              data-name="<?= $pName ?>"><?= $pName ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endfor; ?>
                </div>
              </div>

              <div class="positions-divider"></div>
              <div class="positions-foot">
                <button type="button" class="superadmin-primary-btn" onclick="openConfirm()">
                  <i class="fa-solid fa-floppy-disk"></i> Save Position Assignments
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- STAFF TABLE -->
        <div class="staff-main-panel">
          <form method="GET" id="filterForm">
            <div class="staff-panel-toolbar">
              <div class="staff-panel-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
              </div>
              <select class="staff-panel-select" name="status" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Status</option>
                <option value="active"   <?= $statusFilter==='active'  ?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Disabled</option>
              </select>
              <button type="submit" class="superadmin-primary-btn" style="min-height:40px;padding:0 14px;">
                <i class="fa-solid fa-magnifying-glass"></i>
              </button>
            </div>
          </form>
          <div class="staff-panel-head"><h4>Current Staff Members</h4></div>
          <div class="staff-table-wrap">
            <table class="superadmin-table">
              <thead><tr><th>Name</th><th>Position</th><th>Email</th><th>Status</th><th>Last Active</th><th>Action</th></tr></thead>
              <tbody>
                <?php if (empty($staffList)): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No staff accounts found.</td></tr>
                <?php else: ?>
                <?php foreach ($staffList as $s):
                  $sId     = (int)$s['USER_ID'];
                  $fn      = rtrim($s['FIRST_NAME'] ?? '');
                  $ln      = rtrim($s['LAST_NAME']  ?? '');
                  $sName   = htmlspecialchars(trim("$fn $ln") ?: rtrim($s['USERNAME'] ?? ''));
                  $sPos    = htmlspecialchars(rtrim($s['POSITION'] ?? ''));
                  $sEmail  = htmlspecialchars(rtrim($s['EMAIL'] ?? ''));
                  $sStatus = strtolower(rtrim($s['STATUS'] ?? ''));
                  $ll = '——';
                  if ($s['LAST_LOGIN'] instanceof DateTime) $ll = $s['LAST_LOGIN']->format('M d, Y');
                  elseif (!empty($s['LAST_LOGIN'])) $ll = date('M d, Y', strtotime($s['LAST_LOGIN']));
                ?>
                <tr>
                  <td><?= $sName ?></td>
                  <td><?= $sPos ? '<span class="pos-chip-table">'.$sPos.'</span>' : '<span style="color:var(--text-muted);font-size:12px;">——</span>' ?></td>
                  <td><?= $sEmail ?></td>
                  <td><span class="table-status <?= $sStatus==='active'?'active':'inactive' ?>"><?= $sStatus==='active'?'Active':'Disabled' ?></span></td>
                  <td><?= $ll ?></td>
                  <td>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="target_id" value="<?= $sId ?>">
                      <input type="hidden" name="new_status" value="<?= $sStatus==='active'?'inactive':'active' ?>">
                      <button type="submit" class="table-btn table-btn-light"><?= $sStatus==='active'?'Disable':'Enable' ?></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="staff-right">
        <div class="staff-stat-card">
          <span class="staff-stat-label">Total Staff</span>
          <strong class="staff-stat-num"><?= $totalStaff ?></strong>
          <p class="staff-stat-note">All assigned barangay officials</p>
        </div>
        <div class="staff-stat-card">
          <span class="staff-stat-label">Active Accounts</span>
          <strong class="staff-stat-num"><?= $activeStaff ?></strong>
          <p class="staff-stat-note">Currently active accounts</p>
        </div>
        <div class="staff-stat-card">
          <span class="staff-stat-label">Disabled</span>
          <strong class="staff-stat-num"><?= $disabledStaff ?></strong>
          <p class="staff-stat-note">Temporarily disabled</p>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
/* ── SYNC DROPDOWNS ──────────────────────────────────────────────
   Every time any select changes:
   1. Collect all currently-selected IDs across every select
   2. For every OTHER select, hide options whose IDs are taken
      (but keep the option in DOM so it can be shown again if freed)
   3. Update the "Assigned" badge and has-value class on the changed select
──────────────────────────────────────────────────────────────── */
function syncDropdowns(changedSel) {
  var allSelects = Array.from(document.querySelectorAll('.assignment-select'));

  /* gather taken IDs: each select contributes its current value (if any) */
  var taken = {};
  allSelects.forEach(function(sel) {
    var v = sel.value;
    if (v) taken[v] = true;
  });

  /* for every select, hide options that are taken by ANOTHER select */
  allSelects.forEach(function(sel) {
    var myVal = sel.value;
    Array.from(sel.options).forEach(function(opt) {
      if (!opt.value) return;                      /* keep the blank placeholder */
      if (opt.value === myVal) {
        opt.hidden   = false;                      /* always show own selection  */
        opt.disabled = false;
      } else if (taken[opt.value]) {
        opt.hidden   = true;                       /* hide if taken elsewhere    */
        opt.disabled = true;
      } else {
        opt.hidden   = false;                      /* free — show it             */
        opt.disabled = false;
      }
    });

    /* keep styling in sync */
    sel.classList.toggle('has-value', !!sel.value);
  });

  /* update Kagawad counter */
  var kFilled = Array.from(document.querySelectorAll('.kagawad-sel')).filter(function(s){ return !!s.value; }).length;
  document.getElementById('kagawadCount').textContent = kFilled + ' / <?= $kagawadSlots ?> assigned';
}

/* run once on load so pre-filled values hide each other immediately */
window.addEventListener('DOMContentLoaded', function() {
  syncDropdowns(null);

  <?php if ($showSavedModal): ?>
  document.getElementById('successModal').classList.add('open');
  <?php endif; ?>
});

function openConfirm()  { document.getElementById('confirmModal').classList.add('open'); }
function closeConfirm() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', function(e){ if(e.target===this) closeConfirm(); });
document.getElementById('successModal').addEventListener('click', function(e){ if(e.target===this) window.location.href='superadminstaffaccount.php'; });

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e){ if(e.target===this) closeLogout(); });
</script>
</body>
</html>