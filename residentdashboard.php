<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
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
            header("Location: residentdashboard.php");
        }
        exit();
    }
    if ($_POST['action'] === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentdashboard.php");
        exit();
    }
}

$pendingStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? AND STATUS = 'PENDING'", [$userId]);
$pendingRow  = $pendingStmt ? sqlsrv_fetch_array($pendingStmt, SQLSRV_FETCH_ASSOC) : null;
$pendingCount = $pendingRow ? (int)$pendingRow['CNT'] : 0;

$annStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1");
$annRow  = $annStmt ? sqlsrv_fetch_array($annStmt, SQLSRV_FETCH_ASSOC) : null;
$announcementCount = $annRow ? (int)$annRow['CNT'] : 0;

$concernStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM CONCERNS WHERE USER_ID = ? AND STATUS = 'OPEN'", [$userId]);
$concernRow  = $concernStmt ? sqlsrv_fetch_array($concernStmt, SQLSRV_FETCH_ASSOC) : null;
$openConcerns = $concernRow ? (int)$concernRow['CNT'] : 0;

$completedStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? AND STATUS = 'COMPLETED'", [$userId]);
$completedRow  = $completedStmt ? sqlsrv_fetch_array($completedStmt, SQLSRV_FETCH_ASSOC) : null;
$completedCount = $completedRow ? (int)$completedRow['CNT'] : 0;

$latestAnnStmt = sqlsrv_query($conn, "SELECT TOP 3 TITLE, BODY FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1 ORDER BY CREATED_AT DESC");
$announcements = [];
while ($latestAnnStmt && $row = sqlsrv_fetch_array($latestAnnStmt, SQLSRV_FETCH_ASSOC)) {
    $announcements[] = $row;
}

$dotColors = ['dot--yellow', 'dot--blue', 'dot--green'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resident Dashboard — BarangayKonek</title>
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

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    .card-icon { font-size:20px; margin-bottom:14px; opacity:0.85; color:var(--navy); }
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
      <a href="residentdashboard.php" class="active"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="residentrequestdocument.php"><i class="fa-solid fa-file-lines nav-icon"></i><span>Request Documents</span></a>
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
          <h1>Welcome back, <span style="color:var(--navy);"><?= $firstName ?></span></h1>
          <p class="subtitle">Here are your latest updates and quick actions.</p>
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
                <form method="POST" action="residentdashboard.php" style="margin:0;">
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

      <div class="card-grid">
        <div class="card card--yellow">
          <p class="card-label">Pending Requests</p>
          <p class="number"><?= $pendingCount ?></p>
        </div>
        <div class="card card--blue">
          <p class="card-label">New Announcements</p>
          <p class="number"><?= $announcementCount ?></p>
        </div>
        <div class="card card--red">
          <p class="card-label">Open Concerns</p>
          <p class="number"><?= $openConcerns ?></p>
        </div>
        <div class="card card--green">
          <p class="card-label">Completed Requests</p>
          <p class="number"><?= $completedCount ?></p>
        </div>
      </div>

      <div class="main-panels">
        <div class="panel announcements">
          <div class="panel-header">
            <h2>Latest Announcements</h2>
            <?php if ($announcementCount > 0): ?>
            <span class="badge"><?= $announcementCount ?> NEW</span>
            <?php endif; ?>
          </div>
          <ul>
            <?php if (empty($announcements)): ?>
            <li>
              <div class="announcement-dot dot--blue"></div>
              <div>
                <strong>No announcements yet</strong>
                <p>Check back later for updates.</p>
              </div>
            </li>
            <?php else: ?>
            <?php foreach ($announcements as $i => $ann): ?>
            <li>
              <div class="announcement-dot <?= $dotColors[$i % 3] ?>"></div>
              <div>
                <strong><?= htmlspecialchars(rtrim($ann['TITLE'])) ?></strong>
                <p><?= htmlspecialchars(mb_strimwidth(rtrim($ann['BODY']), 0, 80, '...')) ?></p>
              </div>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <div class="panel contacts">
          <div class="panel-header">
            <h2>Emergency Contacts</h2>
          </div>
          <ul>
            <li>
              <div class="contact-icon"><i class="fa-solid fa-landmark"></i></div>
              <div>
                <span class="contact-label">Barangay Hall</span>
                <strong>046-471-0000</strong>
              </div>
            </li>
            <li>
              <div class="contact-icon"><i class="fa-solid fa-shield-halved"></i></div>
              <div>
                <span class="contact-label">Tanod Hotline</span>
                <strong>0912-345-6789</strong>
              </div>
            </li>
            <li>
              <div class="contact-icon"><i class="fa-solid fa-kit-medical"></i></div>
              <div>
                <span class="contact-label">Medical Response</span>
                <strong>0917-555-1122</strong>
              </div>
            </li>
            <li>
              <div class="contact-icon"><i class="fa-solid fa-fire-extinguisher"></i></div>
              <div>
                <span class="contact-label">Fire Station (BFP)</span>
                <strong>160</strong>
              </div>
            </li>
          </ul>
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
    fd.append('notif_type', typeKey);
    fd.append('ref_id', refId);
    fd.append('ajax', '1');
    fetch('residentdashboard.php', { method: 'POST', body: fd }).catch(() => {});
  }
  document.getElementById('notifDropdown').classList.remove('open');
  if ((typeKey === 'LIKE' || typeKey === 'COMMENT') && refId > 0) {
    window.location.href = 'residentcommunity.php#post-' + refId;
  } else if (typeKey === 'ANNOUNCEMENT') {
    window.location.href = 'residentdashboard.php';
  } else if (typeKey === 'REQUEST') {
    window.location.href = 'residentrequest.php';
  } else if (typeKey === 'CONCERN') {
    window.location.href = 'residentconcern.php';
  }
}
</script>
</body>
</html>