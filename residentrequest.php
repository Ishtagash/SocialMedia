<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$regSql  = "SELECT FIRST_NAME, LAST_NAME, GENDER, PROFILE_PICTURE FROM REGISTRATION WHERE USER_ID = ?";
$regStmt = sqlsrv_query($conn, $regSql, [$userId]);
$regRow  = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);

$firstName = $regRow ? htmlspecialchars(rtrim($regRow['FIRST_NAME'])) : 'Resident';
$lastName  = $regRow ? htmlspecialchars(rtrim($regRow['LAST_NAME']))  : '';
$fullName  = $firstName . ' ' . $lastName;
$gender    = $regRow ? strtolower(rtrim($regRow['GENDER'] ?? '')) : '';

if ($regRow && !empty($regRow['PROFILE_PICTURE'])) {
    $profilePicture = htmlspecialchars($regRow['PROFILE_PICTURE']);
} elseif ($gender === 'male')   { $profilePicture = 'default/male.png'; }
elseif ($gender === 'female')   { $profilePicture = 'default/female.png'; }
else                             { $profilePicture = 'default/neutral.png'; }

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
        $typeKey = trim($_POST['notif_type'] ?? '');
        $refId   = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;
        if (in_array($typeKey, ['LIKE', 'COMMENT']) && $refId > 0) {
            header("Location: residentcommunity.php#post-" . $refId);
        } elseif ($typeKey === 'ANNOUNCEMENT') {
            header("Location: residentdashboard.php");
        } else {
            header("Location: residentrequest.php");
        }
        exit();
    }
    if ($_POST['action'] === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentrequest.php");
        exit();
    }
}

$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchQuery  = isset($_GET['search']) ? trim($_GET['search']) : '';

$statSql  = "SELECT
    COUNT(*) AS TOTAL,
    SUM(CASE WHEN STATUS = 'PENDING'   THEN 1 ELSE 0 END) AS PENDING,
    SUM(CASE WHEN STATUS = 'APPROVED'  THEN 1 ELSE 0 END) AS APPROVED,
    SUM(CASE WHEN STATUS = 'REJECTED'  THEN 1 ELSE 0 END) AS REJECTED,
    SUM(CASE WHEN STATUS = 'COMPLETED' THEN 1 ELSE 0 END) AS COMPLETED
FROM DOCUMENT_REQUESTS WHERE USER_ID = ?";
$statStmt = sqlsrv_query($conn, $statSql, [$userId]);
$statRow  = sqlsrv_fetch_array($statStmt, SQLSRV_FETCH_ASSOC);

$totalReqs     = $statRow ? (int)$statRow['TOTAL']     : 0;
$pendingReqs   = $statRow ? (int)$statRow['PENDING']   : 0;
$approvedReqs  = $statRow ? (int)$statRow['APPROVED']  : 0;
$rejectedReqs  = $statRow ? (int)$statRow['REJECTED']  : 0;
$completedReqs = $statRow ? (int)$statRow['COMPLETED'] : 0;

$listSql    = "SELECT REQUEST_ID, DOCUMENT_TYPE, PURPOSE, STATUS, STAFF_REMARKS, CREATED_AT FROM DOCUMENT_REQUESTS WHERE USER_ID = ?";
$listParams = [$userId];
if ($filterStatus !== '') {
    $listSql    .= " AND STATUS = ?";
    $listParams[] = strtoupper($filterStatus);
}
if ($searchQuery !== '') {
    $listSql    .= " AND DOCUMENT_TYPE LIKE ?";
    $listParams[] = '%' . $searchQuery . '%';
}
$listSql .= " ORDER BY CREATED_AT DESC";
$listStmt = sqlsrv_query($conn, $listSql, $listParams);
$requests = [];
while ($row = sqlsrv_fetch_array($listStmt, SQLSRV_FETCH_ASSOC)) {
    $requests[] = $row;
}

$latestSql  = "SELECT TOP 3 DOCUMENT_TYPE, STATUS, STAFF_REMARKS, CREATED_AT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? ORDER BY CREATED_AT DESC";
$latestStmt = sqlsrv_query($conn, $latestSql, [$userId]);
$latestRequests = [];
while ($row = sqlsrv_fetch_array($latestStmt, SQLSRV_FETCH_ASSOC)) {
    $latestRequests[] = $row;
}

function statusBadge($status) {
    $map   = ['PENDING'=>'pending','APPROVED'=>'approved','REJECTED'=>'rejected','COMPLETED'=>'released'];
    $label = ['PENDING'=>'Pending','APPROVED'=>'Approved','REJECTED'=>'Rejected','COMPLETED'=>'Completed'];
    $s     = strtoupper(rtrim($status));
    return '<span class="status-badge ' . ($map[$s] ?? 'pending') . '">' . ($label[$s] ?? $s) . '</span>';
}

function statusIcon($status) {
    $s = strtoupper(rtrim($status));
    $icons = [
        'PENDING'   => ['icon' => 'fa-clock',        'color' => '#a16207', 'bg' => '#fef9c3'],
        'APPROVED'  => ['icon' => 'fa-circle-check', 'color' => '#15803d', 'bg' => '#dcfce7'],
        'REJECTED'  => ['icon' => 'fa-circle-xmark', 'color' => '#b91c1c', 'bg' => '#fee2e2'],
        'COMPLETED' => ['icon' => 'fa-box-archive',  'color' => '#1d4ed8', 'bg' => '#dbeafe'],
    ];
    return $icons[$s] ?? ['icon' => 'fa-file-lines', 'color' => '#6b7a99', 'bg' => '#f0f3f9'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Requests — BarangayKonek</title>
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

    .combined-panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      margin-bottom: 28px;
      overflow: hidden;
    }
    .combined-panel-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
    }
    .combined-panel-top h2 {
      font-size: 17px;
      font-weight: 700;
      color: var(--navy);
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }
    .combined-panel-top h2::before {
      content: "";
      display: block;
      width: 4px;
      height: 20px;
      background: var(--lime);
      border-radius: 3px;
    }
    .combined-panel-filters {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .combined-panel-filters input,
    .combined-panel-filters select {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 9px 14px;
      font-family: inherit;
      font-size: 13px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }
    .combined-panel-filters input:focus,
    .combined-panel-filters select:focus {
      border-color: var(--navy);
    }
    .combined-panel-filters input { width: 200px; }
    .combined-panel-filters button {
      background: var(--navy);
      color: #ccff00;
      border: none;
      border-radius: 10px;
      padding: 9px 18px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.2s;
    }
    .combined-panel-filters button:hover { background: #0a2470; }
    .combined-panel-filters .badge {
      background: var(--lime);
      color: var(--navy);
      font-size: 11px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
    }

    .bottom-panels {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      align-items: start;
      margin-bottom: 28px;
    }

    .updates-panel,
    .guide-panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      display: flex;
      flex-direction: column;
    }

    .bottom-panel-header {
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .bottom-panel-header h2 {
      font-size: 17px;
      font-weight: 700;
      color: var(--navy);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .bottom-panel-header h2::before {
      content: "";
      display: block;
      width: 4px;
      height: 20px;
      background: var(--lime);
      border-radius: 3px;
    }

    .updates-list {
      list-style: none;
      padding: 0 24px;
      margin: 0;
      flex: 1;
    }
    .updates-list li {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px 0;
      border-bottom: 1px solid var(--border);
    }
    .updates-list li:last-child { border-bottom: none; }

    .update-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .update-body { flex: 1; min-width: 0; }
    .update-body strong {
      display: block;
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 3px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .update-body p {
      font-size: 13px;
      color: var(--text-muted);
      line-height: 1.45;
      margin: 0;
    }
    .update-body .update-date {
      font-size: 11px;
      color: #bbb;
      margin-top: 4px;
    }
    .updates-empty {
      padding: 28px 24px;
      text-align: center;
      color: var(--text-muted);
      font-size: 13px;
    }
    .updates-empty i {
      display: block;
      font-size: 26px;
      margin-bottom: 10px;
      color: #ccc;
    }

    .guide-body {
      padding: 16px 24px 20px;
      flex: 1;
    }
    .guide-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .guide-card {
      border-radius: 12px;
      padding: 14px 14px;
      display: flex;
      align-items: flex-start;
      gap: 11px;
    }
    .guide-card-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .guide-card-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 3px; }
    .guide-card-title { font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 2px; }
    .guide-card-desc  { font-size: 12px; color: var(--text-muted); line-height: 1.4; }
    .guide-pending    { background: #fffbeb; }
    .guide-approved   { background: #f0fdf4; }
    .guide-completed  { background: #eff6ff; }
    .guide-rejected   { background: #fff1f1; }
    .guide-pending  .guide-card-icon  { background: #fef9c3; color: #a16207; }
    .guide-approved .guide-card-icon  { background: #dcfce7; color: #15803d; }
    .guide-completed .guide-card-icon { background: #dbeafe; color: #1d4ed8; }
    .guide-rejected .guide-card-icon  { background: #fee2e2; color: #b91c1c; }
    .guide-pending  .guide-card-label  { color: #a16207; }
    .guide-approved .guide-card-label  { color: #15803d; }
    .guide-completed .guide-card-label { color: #1d4ed8; }
    .guide-rejected .guide-card-label  { color: #b91c1c; }

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    @media (max-width: 900px) {
      .bottom-panels { grid-template-columns: 1fr; }
      .combined-panel-top { flex-direction: column; align-items: flex-start; }
      .combined-panel-filters input { width: 100%; }
    }
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
      <div class="avatar-ring"><img src="<?= $profilePicture ?>" alt="Resident Photo" /></div>
      <div class="profile-meta">
        <h3><?= $fullName ?></h3>
        <p>City of Imus, Alapan 1-A</p>
        <span class="portal-badge">Resident Portal</span>
      </div>
    </div>
    <nav class="menu">
      <a href="residentdashboard.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="residentrequestdocument.php"><i class="fa-solid fa-file-lines nav-icon"></i><span>Request Documents</span></a>
      <a href="residentconcern.php"><i class="fa-solid fa-circle-exclamation nav-icon"></i><span>Concerns</span></a>
      <a href="residentcommunity.php"><i class="fa-solid fa-users nav-icon"></i><span>Community</span></a>
      <a href="residentrequest.php" class="active"><i class="fa-solid fa-clipboard-list nav-icon"></i><span>My Requests</span></a>
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
    <div class="topbar">
      <div class="greeting-block">
        <h1>My <span style="color:var(--navy);">Requests</span></h1>
        <p class="subtitle">Track your submitted document requests and their current status.</p>
      </div>
      <div class="topbar-right">
        <div class="bell-wrap-pos">
          <button type="button" id="bellBtn" onclick="toggleNotif()"
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
              <form method="POST" action="residentrequest.php" style="margin:0;">
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
              $iconMap  = ['LIKE'=>'fa-thumbs-up','COMMENT'=>'fa-comment','ANNOUNCEMENT'=>'fa-bullhorn','REQUEST'=>'fa-file-lines'];
              $icon     = $iconMap[$typeKey] ?? 'fa-bell';
              $timeAgo  = $notif['CREATED_AT']->format('M d, g:i A');
            ?>
            <form method="POST" action="residentrequest.php" style="display:block;margin:0;padding:0;">
              <input type="hidden" name="action" value="read_notif">
              <input type="hidden" name="notif_id" value="<?= $notifId ?>">
              <input type="hidden" name="notif_type" value="<?= htmlspecialchars($typeKey) ?>">
              <input type="hidden" name="ref_id" value="<?= $refId ?>">
              <button type="submit" class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                <div class="notif-item-top">
                  <div class="notif-item-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                  <div class="notif-item-text">
                    <?= htmlspecialchars(rtrim($notif['MESSAGE'])) ?>
                    <div class="notif-item-time"><?= $timeAgo ?></div>
                  </div>
                  <?php if ($isUnread): ?><div class="notif-unread-dot"></div><?php endif; ?>
                </div>
              </button>
            </form>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="requests-summary">
      <div class="request-stat">
        <p class="request-stat-label">Total Requests</p>
        <h3><?= $totalReqs ?></h3>
      </div>
      <div class="request-stat" style="border-top:3px solid #f59e0b;">
        <p class="request-stat-label" style="color:#a16207;">Pending</p>
        <h3 style="color:#a16207;"><?= $pendingReqs ?></h3>
      </div>
      <div class="request-stat" style="border-top:3px solid #22c55e;">
        <p class="request-stat-label" style="color:#15803d;">Approved</p>
        <h3 style="color:#15803d;"><?= $approvedReqs ?></h3>
      </div>
      <div class="request-stat" style="border-top:3px solid #ef4444;">
        <p class="request-stat-label" style="color:#b91c1c;">Rejected</p>
        <h3 style="color:#b91c1c;"><?= $rejectedReqs ?></h3>
      </div>
    </div>

    <div class="combined-panel">
      <div class="combined-panel-top">
        <h2>Request History <span class="badge">Updated Today</span></h2>
        <form class="combined-panel-filters" method="GET" action="residentrequest.php">
          <input type="text" name="search" placeholder="Search request type..." value="<?= htmlspecialchars($searchQuery) ?>" />
          <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option <?= strtoupper($filterStatus) === 'PENDING'   ? 'selected' : '' ?> value="PENDING">Pending</option>
            <option <?= strtoupper($filterStatus) === 'APPROVED'  ? 'selected' : '' ?> value="APPROVED">Approved</option>
            <option <?= strtoupper($filterStatus) === 'REJECTED'  ? 'selected' : '' ?> value="REJECTED">Rejected</option>
            <option <?= strtoupper($filterStatus) === 'COMPLETED' ? 'selected' : '' ?> value="COMPLETED">Completed</option>
          </select>
          <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
      </div>
      <div style="overflow-x:auto;">
        <table class="request-table">
          <thead>
            <tr>
              <th>Reference No.</th>
              <th>Request Type</th>
              <th>Purpose</th>
              <th>Date Submitted</th>
              <th>Status</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($requests)): ?>
            <tr><td colspan="6" style="text-align:center;color:#aaa;padding:32px;">No requests found.</td></tr>
            <?php else: ?>
            <?php foreach ($requests as $req): ?>
            <tr>
              <td style="font-family:'Space Mono',monospace;font-size:13px;">REQ-<?= str_pad($req['REQUEST_ID'], 4, '0', STR_PAD_LEFT) ?></td>
              <td><?= htmlspecialchars(rtrim($req['DOCUMENT_TYPE'])) ?></td>
              <td><?= htmlspecialchars(rtrim($req['PURPOSE'])) ?></td>
              <td><?= $req['CREATED_AT']->format('M d, Y') ?></td>
              <td><?= statusBadge($req['STATUS']) ?></td>
              <td style="color:var(--text-muted);font-size:13px;"><?= $req['STAFF_REMARKS'] ? htmlspecialchars(rtrim($req['STAFF_REMARKS'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bottom-panels">

      <div class="updates-panel">
        <div class="bottom-panel-header">
          <h2>Latest Request Updates</h2>
        </div>
        <?php if (empty($latestRequests)): ?>
        <div class="updates-empty">
          <i class="fa-regular fa-folder-open"></i>
          No requests yet. Submit a document request to get started.
        </div>
        <?php else: ?>
        <ul class="updates-list">
          <?php foreach ($latestRequests as $lr):
            $ico = statusIcon($lr['STATUS']);
          ?>
          <li>
            <div class="update-icon" style="background:<?= $ico['bg'] ?>;color:<?= $ico['color'] ?>;">
              <i class="fa-solid <?= $ico['icon'] ?>"></i>
            </div>
            <div class="update-body">
              <strong><?= htmlspecialchars(rtrim($lr['DOCUMENT_TYPE'])) ?></strong>
              <p><?= $lr['STAFF_REMARKS'] ? htmlspecialchars(rtrim($lr['STAFF_REMARKS'])) : 'Status: ' . ucfirst(strtolower(rtrim($lr['STATUS']))) ?></p>
              <div class="update-date"><?= $lr['CREATED_AT']->format('M d, Y') ?></div>
            </div>
            <?= statusBadge($lr['STATUS']) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <div class="guide-panel">
        <div class="bottom-panel-header">
          <h2>Request Guide</h2>
        </div>
        <div class="guide-body">
          <div class="guide-grid">
            <div class="guide-card guide-pending">
              <div class="guide-card-icon"><i class="fa-solid fa-clock"></i></div>
              <div>
                <div class="guide-card-label">Pending</div>
                <div class="guide-card-title">Under Review</div>
                <div class="guide-card-desc">Your request is being processed by staff.</div>
              </div>
            </div>
            <div class="guide-card guide-approved">
              <div class="guide-card-icon"><i class="fa-solid fa-circle-check"></i></div>
              <div>
                <div class="guide-card-label">Approved</div>
                <div class="guide-card-title">Accepted</div>
                <div class="guide-card-desc">Ready for pickup at the barangay hall.</div>
              </div>
            </div>
            <div class="guide-card guide-completed">
              <div class="guide-card-icon"><i class="fa-solid fa-box-archive"></i></div>
              <div>
                <div class="guide-card-label">Completed</div>
                <div class="guide-card-title">Claimed</div>
                <div class="guide-card-desc">Document has been released to you.</div>
              </div>
            </div>
            <div class="guide-card guide-rejected">
              <div class="guide-card-icon"><i class="fa-solid fa-circle-xmark"></i></div>
              <div>
                <div class="guide-card-label">Rejected</div>
                <div class="guide-card-title">Needs Correction</div>
                <div class="guide-card-desc">Check staff remarks and resubmit.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
function toggleNotif() { document.getElementById('notifDropdown').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const btn = document.getElementById('bellBtn');
  const dd  = document.getElementById('notifDropdown');
  if (btn && dd && !btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
});
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', e => {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});
</script>
</body>
</html>