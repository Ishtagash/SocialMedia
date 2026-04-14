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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'read_notif' && isset($_POST['notif_id'])) {
        $notifId = (int)$_POST['notif_id'];
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?", [$notifId, $userId]);
        header("Location: residentrequest.php");
        exit();
    }

    if ($action === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentrequest.php");
        exit();
    }
}

$unreadRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM NOTIFICATIONS WHERE USER_ID = ? AND IS_READ = 0", [$userId]),
    SQLSRV_FETCH_ASSOC
);
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

if ($filterStatus !== '' && $filterStatus !== 'All Status') {
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

$latestStmt = sqlsrv_query($conn,
    "SELECT TOP 5 REQUEST_ID, DOCUMENT_TYPE, STATUS, STAFF_REMARKS, CREATED_AT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? ORDER BY CREATED_AT DESC",
    [$userId]
);
$latestRequests = [];
while ($row = sqlsrv_fetch_array($latestStmt, SQLSRV_FETCH_ASSOC)) {
    $latestRequests[] = $row;
}

function statusBadge($status) {
    $map = [
        'PENDING'   => 'pending',
        'APPROVED'  => 'approved',
        'REJECTED'  => 'rejected',
        'COMPLETED' => 'released',
    ];
    $label = [
        'PENDING'   => 'Pending',
        'APPROVED'  => 'Approved',
        'REJECTED'  => 'Rejected',
        'COMPLETED' => 'Completed',
    ];
    $s   = strtoupper(rtrim($status));
    $cls = $map[$s]   ?? 'pending';
    $lbl = $label[$s] ?? $s;
    return '<span class="status-badge ' . $cls . '">' . $lbl . '</span>';
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
      box-shadow:0 8px 30px rgba(5,22,80,0.16); z-index:999; display:none; overflow:hidden;
      max-height:480px; overflow-y:auto;
    }
    .notif-dropdown.open { display:block; }
    .notif-dropdown-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px 10px; border-bottom:1px solid rgba(5,22,80,0.08);
      position:sticky; top:0; background:#fff; z-index:1;
    }
    .notif-dropdown-header h4 { font-size:14px; font-weight:700; color:#051650; }
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

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    .combined-panel { display:grid; grid-template-columns:1fr 1fr; gap:0; }
    .combined-panel .split-left  { padding:28px; border-right:1px solid var(--border); }
    .combined-panel .split-right { padding:28px; }
    @media(max-width:700px) {
      .combined-panel { grid-template-columns:1fr; }
      .combined-panel .split-left { border-right:none; border-bottom:1px solid var(--border); }
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
    <div class="sidebar-brand">
      <h2>BarangayKonek</h2>
      <span>Resident</span>
    </div>
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
        <div class="user-chip">
          <div class="user-chip-avatar-wrap">
            <img src="<?= $profilePicture ?>" alt="Resident Photo" class="user-chip-img" />
          </div>
          <div class="user-chip-info">
            <span class="user-chip-name"><?= $fullName ?></span>
            <span class="user-chip-role">Resident</span>
          </div>
          <div class="bell-wrap-pos">
            <button type="button" class="user-chip-bell-wrap" id="bellBtn" onclick="toggleNotif()">
              <i class="fa-solid fa-bell user-chip-bell"></i>
              <?php if ($unreadCount > 0): ?>
              <span class="user-chip-notif"><?= $unreadCount ?></span>
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
                $typeKey  = rtrim($notif['TYPE']);
                $iconMap  = ['LIKE'=>'fa-thumbs-up','COMMENT'=>'fa-comment','ANNOUNCEMENT'=>'fa-bullhorn','REQUEST'=>'fa-file-lines'];
                $icon     = $iconMap[$typeKey] ?? 'fa-bell';
                $timeAgo  = $notif['CREATED_AT']->format('M d, g:i A');
              ?>
              <form method="POST" action="residentrequest.php" style="display:block;margin:0;padding:0;">
                <input type="hidden" name="action" value="read_notif">
                <input type="hidden" name="notif_id" value="<?= $notifId ?>">
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
    </div>

    <div class="requests-summary">
      <div class="request-stat">
        <p class="request-stat-label">Total Requests</p>
        <h3><?= $totalReqs ?></h3>
      </div>
      <div class="request-stat">
        <p class="request-stat-label">Pending</p>
        <h3><?= $pendingReqs ?></h3>
      </div>
      <div class="request-stat">
        <p class="request-stat-label">Approved</p>
        <h3><?= $approvedReqs ?></h3>
      </div>
      <div class="request-stat">
        <p class="request-stat-label">Completed</p>
        <h3><?= $completedReqs ?></h3>
      </div>
      <div class="request-stat">
        <p class="request-stat-label">Rejected</p>
        <h3><?= $rejectedReqs ?></h3>
      </div>
    </div>

    <div class="panel" style="margin-bottom:24px;">
      <div class="combined-panel">
        <div class="split-left">
          <div class="panel-header" style="margin-bottom:16px;">
            <h2>Search Requests</h2>
          </div>
          <form method="GET" action="residentrequest.php" style="display:flex;flex-direction:column;gap:10px;">
            <input type="text" name="search" placeholder="Search request type..."
              value="<?= htmlspecialchars($searchQuery) ?>"
              style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:11px 14px;font-family:inherit;font-size:14px;color:var(--text);outline:none;" />
            <select name="status" onchange="this.form.submit()"
              style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:11px 14px;font-family:inherit;font-size:14px;color:var(--text);outline:none;">
              <option value="">All Status</option>
              <option value="PENDING"   <?= strtoupper($filterStatus) === 'PENDING'   ? 'selected' : '' ?>>Pending</option>
              <option value="APPROVED"  <?= strtoupper($filterStatus) === 'APPROVED'  ? 'selected' : '' ?>>Approved</option>
              <option value="COMPLETED" <?= strtoupper($filterStatus) === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
              <option value="REJECTED"  <?= strtoupper($filterStatus) === 'REJECTED'  ? 'selected' : '' ?>>Rejected</option>
            </select>
            <button type="submit"
              style="background:var(--navy);color:#ccff00;border:none;border-radius:10px;padding:11px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background 0.2s;"
              onmouseover="this.style.background='#0a2470'" onmouseout="this.style.background='var(--navy)'">
              <i class="fa-solid fa-magnifying-glass" style="margin-right:6px;"></i>Search
            </button>
          </form>
        </div>
        <div class="split-right">
          <div class="panel-header" style="margin-bottom:16px;">
            <h2>Latest Request Updates</h2>
          </div>
          <?php if (empty($latestRequests)): ?>
          <p style="font-size:13px;color:#aaa;">No requests yet. Submit a document request to get started.</p>
          <?php else: ?>
          <ul class="request-updates" style="list-style:none;">
            <?php foreach ($latestRequests as $lr):
              $s = strtoupper(rtrim($lr['STATUS']));
              $dotColor = ['PENDING'=>'#f7b125','APPROVED'=>'#22c55e','COMPLETED'=>'#3b82f6','REJECTED'=>'#ff4d4d'][$s] ?? '#aaa';
            ?>
            <li style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= $dotColor ?>;flex-shrink:0;margin-top:5px;"></span>
              <div>
                <strong style="display:block;font-size:14px;color:var(--text);"><?= htmlspecialchars(rtrim($lr['DOCUMENT_TYPE'])) ?></strong>
                <span style="font-size:12px;color:var(--text-muted);"><?= $lr['CREATED_AT']->format('M d, Y') ?> &middot; <?= statusBadge($lr['STATUS']) ?></span>
                <?php if (!empty(trim($lr['STAFF_REMARKS'] ?? ''))): ?>
                <p style="font-size:12px;color:#666;margin:3px 0 0;"><?= htmlspecialchars(rtrim($lr['STAFF_REMARKS'])) ?></p>
                <?php endif; ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="panel request-table-panel">
      <div class="panel-header">
        <h2>Request History</h2>
        <span class="badge"><?= $totalReqs ?> Total</span>
      </div>
      <table class="request-table">
        <thead>
          <tr>
            <th>Reference No.</th>
            <th>Request Type</th>
            <th>Purpose</th>
            <th>Date Submitted</th>
            <th>Status</th>
            <th>Staff Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($requests)): ?>
          <tr>
            <td colspan="6" style="text-align:center;color:#aaa;padding:28px;">No requests found.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($requests as $req): ?>
          <tr>
            <td style="font-family:'Space Mono',monospace;font-size:13px;">REQ-<?= str_pad($req['REQUEST_ID'], 4, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars(rtrim($req['DOCUMENT_TYPE'])) ?></td>
            <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(rtrim($req['PURPOSE'])) ?></td>
            <td><?= $req['CREATED_AT']->format('M d, Y') ?></td>
            <td><?= statusBadge($req['STATUS']) ?></td>
            <td style="color:#666;"><?= !empty(trim($req['STAFF_REMARKS'] ?? '')) ? htmlspecialchars(rtrim($req['STAFF_REMARKS'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" style="margin-top:24px;">
      <div class="panel-header"><h2>Request Guide</h2></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-top:4px;">
        <div style="background:#fff4cc;border-radius:10px;padding:14px 16px;">
          <span style="font-size:11px;font-weight:700;color:#8a6d00;text-transform:uppercase;letter-spacing:0.4px;">Pending</span>
          <p style="font-size:13px;color:#555;margin-top:4px;">Your request is under review by staff.</p>
        </div>
        <div style="background:#dff6e6;border-radius:10px;padding:14px 16px;">
          <span style="font-size:11px;font-weight:700;color:#1b7b43;text-transform:uppercase;letter-spacing:0.4px;">Approved</span>
          <p style="font-size:13px;color:#555;margin-top:4px;">Accepted. Await release or pick-up.</p>
        </div>
        <div style="background:#ddefff;border-radius:10px;padding:14px 16px;">
          <span style="font-size:11px;font-weight:700;color:#1455a0;text-transform:uppercase;letter-spacing:0.4px;">Completed</span>
          <p style="font-size:13px;color:#555;margin-top:4px;">Document has been claimed/released.</p>
        </div>
        <div style="background:#ffe2e2;border-radius:10px;padding:14px 16px;">
          <span style="font-size:11px;font-weight:700;color:#b42318;text-transform:uppercase;letter-spacing:0.4px;">Rejected</span>
          <p style="font-size:13px;color:#555;margin-top:4px;">Needs correction. Check staff notes.</p>
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
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeLogout();
});
</script>
</body>
</html>