<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$totalResidents = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'resident'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $totalResidents = (int)$row['CNT']; }

$pendingVerification = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'resident' AND STATUS = 'pending'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $pendingVerification = (int)$row['CNT']; }

$activeStaff = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'staff' AND STATUS = 'active'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $activeStaff = (int)$row['CNT']; }

$openRequests = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS = 'PENDING'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $openRequests = (int)$row['CNT']; }

$openConcerns = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM CONCERNS WHERE STATUS = 'OPEN'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $openConcerns = (int)$row['CNT']; }

$overdueRequests = 0;
$r = sqlsrv_query($conn,
    "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS
     WHERE STATUS = 'PENDING' AND DATEDIFF(day, CREATED_AT, GETDATE()) > 3"
);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $overdueRequests = (int)$row['CNT']; }

$disabledAccounts = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'resident' AND STATUS = 'inactive'");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $disabledAccounts = (int)$row['CNT']; }

$pendingResidents = [];
$pr = sqlsrv_query($conn,
    "SELECT TOP 5 U.USER_ID, R.FIRST_NAME, R.LAST_NAME, R.ADDRESS, U.CREATED_AT
     FROM USERS U INNER JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
     WHERE U.ROLE = 'resident' AND U.STATUS = 'pending'
     ORDER BY U.CREATED_AT DESC"
);
if ($pr) { while ($row = sqlsrv_fetch_array($pr, SQLSRV_FETCH_ASSOC)) { $pendingResidents[] = $row; } }

$recentLogs = [];
$rl = sqlsrv_query($conn,
    "SELECT TOP 5 L.ACTION, L.DETAILS, L.CREATED_AT, U.USERNAME
     FROM AUDIT_LOGS L INNER JOIN USERS U ON U.USER_ID = L.USER_ID
     ORDER BY L.CREATED_AT DESC"
);
if ($rl) { while ($row = sqlsrv_fetch_array($rl, SQLSRV_FETCH_ASSOC)) { $recentLogs[] = $row; } }

$reqPending   = 0; $reqApproved  = 0; $reqRejected  = 0; $reqCompleted = 0;
$rs = sqlsrv_query($conn, "SELECT STATUS, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS GROUP BY STATUS");
if ($rs) {
    while ($row = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC)) {
        switch (strtoupper(rtrim($row['STATUS']))) {
            case 'PENDING':   $reqPending   = (int)$row['CNT']; break;
            case 'APPROVED':  $reqApproved  = (int)$row['CNT']; break;
            case 'REJECTED':  $reqRejected  = (int)$row['CNT']; break;
            case 'COMPLETED': $reqCompleted = (int)$row['CNT']; break;
        }
    }
}

$activeAnnouncements = 0;
$r = sqlsrv_query($conn,
    "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS
     WHERE IS_ACTIVE = 1 AND (EXPIRES_AT IS NULL OR EXPIRES_AT >= GETDATE())"
);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $activeAnnouncements = (int)$row['CNT']; }

$latestAnn = [];
$la = sqlsrv_query($conn,
    "SELECT TOP 3 A.TITLE, A.CATEGORY, A.CREATED_AT
     FROM ANNOUNCEMENTS A
     WHERE A.IS_ACTIVE = 1 AND (A.EXPIRES_AT IS NULL OR A.EXPIRES_AT >= GETDATE())
     ORDER BY A.CREATED_AT DESC"
);
if ($la) { while ($row = sqlsrv_fetch_array($la, SQLSRV_FETCH_ASSOC)) { $latestAnn[] = $row; } }

$totalConcernsOpen     = 0;
$totalConcernsResolved = 0;
$cr = sqlsrv_query($conn, "SELECT STATUS, COUNT(*) AS CNT FROM CONCERNS GROUP BY STATUS");
if ($cr) {
    while ($row = sqlsrv_fetch_array($cr, SQLSRV_FETCH_ASSOC)) {
        $s = strtoupper(rtrim($row['STATUS']));
        if ($s === 'OPEN')     $totalConcernsOpen     = (int)$row['CNT'];
        if ($s === 'RESOLVED') $totalConcernsResolved = (int)$row['CNT'];
    }
}

function getCatDot($cat) {
    $map = ['General' => '#051650', 'Event' => '#1e40af', 'Health' => '#166534',
            'Reminder' => '#92400e', 'Alert' => '#991b1b'];
    return $map[trim($cat)] ?? '#051650';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Dashboard — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="superadmin.css" />
  <style>
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .dashboard-btn{display:inline-flex;align-items:center;justify-content:center}

    .syssum-section-title{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;padding:14px 18px 8px;display:block}
    .syssum-req-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 14px 14px}
    .syssum-box{border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px}
    .syssum-box.pending  {background:rgba(245,158,11,.10)}
    .syssum-box.approved {background:rgba(34,197,94,.10)}
    .syssum-box.rejected {background:rgba(239,68,68,.10)}
    .syssum-box.completed{background:rgba(5,22,80,.07)}
    .syssum-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
    .syssum-box.pending  .syssum-icon{background:rgba(245,158,11,.2);color:#92400e}
    .syssum-box.approved .syssum-icon{background:rgba(34,197,94,.2);color:#166534}
    .syssum-box.rejected .syssum-icon{background:rgba(239,68,68,.2);color:#991b1b}
    .syssum-box.completed .syssum-icon{background:rgba(5,22,80,.12);color:var(--navy)}
    .syssum-num{font-size:22px;font-weight:700;color:var(--navy);line-height:1}
    .syssum-label{font-size:11px;color:var(--text-muted);font-weight:600;margin-top:2px}
    .syssum-divider{height:1px;background:var(--border);margin:0 18px}
    .syssum-meta-row{display:flex;align-items:center;justify-content:space-between;padding:10px 18px}
    .syssum-meta-row:first-child{padding-top:14px}
    .syssum-meta-row:last-child{padding-bottom:14px}
    .syssum-meta-left{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--text)}
    .syssum-meta-left i{font-size:13px;width:16px;text-align:center}
    .syssum-meta-val{font-size:13px;font-weight:700;color:var(--navy)}

    .ann-mini-item{display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid var(--border)}
    .ann-mini-item:last-child{border-bottom:none}
    .ann-mini-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .ann-mini-title{font-size:13px;font-weight:600;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
    .ann-mini-date{font-size:11px;color:var(--text-muted);white-space:nowrap}
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-btns">
      <button class="btn-cancel" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand">
      <h1>Barangay Alapan 1-A</h1>
      <p>Super Admin Dashboard</p>
    </div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php" class="active">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
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

  <main class="dashboard-wrapper">
    <div class="dashboard-topbar">
      <div class="dashboard-topbar-left">
        <h2>Dashboard Overview</h2>
        <p><?= date('l, F j, Y') ?></p>
      </div>
      <div class="dashboard-topbar-actions">
        <a href="superadminstaffaccount.php?action=add" class="superadmin-primary-btn">
          <i class="fa-solid fa-user-plus"></i> Add Staff
        </a>
        <a href="superadminreports.php" class="superadmin-outline-btn">
          <i class="fa-solid fa-chart-bar"></i> Reports
        </a>
      </div>
    </div>

    <div class="dashboard-stat-row">
      <div class="dashboard-stat-box">
        <span class="dashboard-stat-label">Total Residents</span>
        <span class="dashboard-stat-value"><?= $totalResidents ?></span>
        <span class="dashboard-stat-sub">Registered accounts</span>
      </div>
      <div class="dashboard-stat-box">
        <span class="dashboard-stat-label">Pending Verification</span>
        <span class="dashboard-stat-value"><?= $pendingVerification ?></span>
        <span class="dashboard-stat-sub">Awaiting review</span>
      </div>
      <div class="dashboard-stat-box">
        <span class="dashboard-stat-label">Active Staff</span>
        <span class="dashboard-stat-value"><?= $activeStaff ?></span>
        <span class="dashboard-stat-sub">Currently enabled</span>
      </div>
      <div class="dashboard-stat-box">
        <span class="dashboard-stat-label">Open Requests</span>
        <span class="dashboard-stat-value"><?= $openRequests ?></span>
        <span class="dashboard-stat-sub">Unresolved requests</span>
      </div>
      <div class="dashboard-stat-box">
        <span class="dashboard-stat-label">Open Concerns</span>
        <span class="dashboard-stat-value"><?= $openConcerns ?></span>
        <span class="dashboard-stat-sub">Filed concerns</span>
      </div>
    </div>

    <div class="dashboard-body">
      <div class="dashboard-col">

        <div class="dashboard-panel">
          <div class="dashboard-panel-head">
            <h4>Needs Attention Today</h4>
            <a href="superadminresidentaccount.php">View All</a>
          </div>
          <div class="dashboard-panel-body">
            <?php if ($pendingVerification > 0): ?>
            <div class="dashboard-alert-item">
              <div class="dashboard-alert-text">
                <strong><?= $pendingVerification ?> resident account<?= $pendingVerification > 1 ? 's' : '' ?> waiting for verification</strong>
                <p>Review newly submitted resident registrations.</p>
              </div>
              <a href="superadminresidentaccount.php?filter=pending" class="dashboard-btn">Review</a>
            </div>
            <?php endif; ?>
            <?php if ($overdueRequests > 0): ?>
            <div class="dashboard-alert-item">
              <div class="dashboard-alert-text">
                <strong><?= $overdueRequests ?> document request<?= $overdueRequests > 1 ? 's' : '' ?> overdue</strong>
                <p>Pending over 3 days and not yet processed.</p>
              </div>
              <a href="superadminreports.php" class="dashboard-btn dashboard-btn-ghost">Open</a>
            </div>
            <?php endif; ?>
            <?php if ($disabledAccounts > 0): ?>
            <div class="dashboard-alert-item">
              <div class="dashboard-alert-text">
                <strong><?= $disabledAccounts ?> disabled resident account<?= $disabledAccounts > 1 ? 's' : '' ?> need review</strong>
                <p>Decide whether to restore or keep account restrictions.</p>
              </div>
              <a href="superadminresidentaccount.php?filter=inactive" class="dashboard-btn dashboard-btn-ghost">Inspect</a>
            </div>
            <?php endif; ?>
            <?php if ($pendingVerification === 0 && $overdueRequests === 0 && $disabledAccounts === 0): ?>
            <div class="dashboard-alert-item">
              <div class="dashboard-alert-text">
                <strong>No urgent items today</strong>
                <p>Everything is up to date.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="dashboard-panel">
          <div class="dashboard-panel-head">
            <h4>Pending Verification</h4>
            <a href="superadminresidentaccount.php?filter=pending">Review All</a>
          </div>
          <div class="dashboard-panel-body">
            <table class="dashboard-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Address</th>
                  <th>Submitted</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pendingResidents)): ?>
                <tr>
                  <td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px 18px;">
                    No pending verifications.
                  </td>
                </tr>
                <?php else: ?>
                <?php foreach ($pendingResidents as $res):
                  $createdAt = $res['CREATED_AT'] instanceof DateTime
                    ? $res['CREATED_AT']->format('M d, Y')
                    : date('M d, Y', strtotime($res['CREATED_AT']));
                ?>
                <tr>
                  <td><?= htmlspecialchars(rtrim($res['FIRST_NAME']) . ' ' . rtrim($res['LAST_NAME'])) ?></td>
                  <td><?= htmlspecialchars(mb_strimwidth(rtrim($res['ADDRESS'] ?? '—'), 0, 30, '...')) ?></td>
                  <td><?= $createdAt ?></td>
                  <td>
                    <a href="superadminresidentaccount.php?view=<?= (int)$res['USER_ID'] ?>"
                       class="dashboard-btn">Review</a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="dashboard-col">

        <div class="dashboard-panel">
          <div class="dashboard-panel-head">
            <h4>Document Request Summary</h4>
            <a href="superadminreports.php">Full Report</a>
          </div>

          <span class="syssum-section-title">All-time status of document requests</span>
          <div class="syssum-req-grid">
            <div class="syssum-box pending">
              <div class="syssum-icon"><i class="fa-solid fa-hourglass-half"></i></div>
              <div>
                <div class="syssum-num"><?= $reqPending ?></div>
                <div class="syssum-label">Pending</div>
              </div>
            </div>
            <div class="syssum-box approved">
              <div class="syssum-icon"><i class="fa-solid fa-thumbs-up"></i></div>
              <div>
                <div class="syssum-num"><?= $reqApproved ?></div>
                <div class="syssum-label">Approved</div>
              </div>
            </div>
            <div class="syssum-box rejected">
              <div class="syssum-icon"><i class="fa-solid fa-circle-xmark"></i></div>
              <div>
                <div class="syssum-num"><?= $reqRejected ?></div>
                <div class="syssum-label">Rejected</div>
              </div>
            </div>
            <div class="syssum-box completed">
              <div class="syssum-icon"><i class="fa-solid fa-box-archive"></i></div>
              <div>
                <div class="syssum-num"><?= $reqCompleted ?></div>
                <div class="syssum-label">Completed</div>
              </div>
            </div>
          </div>

          <div class="syssum-divider"></div>

          <span class="syssum-section-title">Concerns &amp; Announcements</span>
          <div class="syssum-meta-row">
            <span class="syssum-meta-left">
              <i class="fa-solid fa-circle-exclamation" style="color:#b45309;"></i>
              Open Concerns
            </span>
            <span class="syssum-meta-val"><?= $totalConcernsOpen ?></span>
          </div>
          <div class="syssum-meta-row">
            <span class="syssum-meta-left">
              <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
              Resolved Concerns
            </span>
            <span class="syssum-meta-val"><?= $totalConcernsResolved ?></span>
          </div>
          <div class="syssum-meta-row">
            <span class="syssum-meta-left">
              <i class="fa-solid fa-bullhorn" style="color:var(--navy);"></i>
              Active Announcements
            </span>
            <span class="syssum-meta-val"><?= $activeAnnouncements ?></span>
          </div>
        </div>

        <div class="dashboard-panel">
          <div class="dashboard-panel-head">
            <h4>Latest Announcements</h4>
            <a href="superadminannouncement.php">Manage</a>
          </div>
          <div class="dashboard-panel-body" style="padding:0;">
            <?php if (empty($latestAnn)): ?>
            <div class="ann-mini-item">
              <span class="ann-mini-title" style="color:var(--text-muted);">No announcements posted yet.</span>
            </div>
            <?php else: ?>
            <?php foreach ($latestAnn as $a):
              $aDate = $a['CREATED_AT'] instanceof DateTime
                ? $a['CREATED_AT']->format('M j')
                : date('M j', strtotime($a['CREATED_AT']));
            ?>
            <div class="ann-mini-item">
              <div class="ann-mini-dot" style="background:<?= getCatDot(rtrim($a['CATEGORY'] ?? '')) ?>;"></div>
              <span class="ann-mini-title"><?= htmlspecialchars(rtrim($a['TITLE'])) ?></span>
              <span class="ann-mini-date"><?= $aDate ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="dashboard-panel">
          <div class="dashboard-panel-head">
            <h4>Recent Activity</h4>
            <a href="superadminauditlogs.php">View Logs</a>
          </div>
          <div class="dashboard-panel-body">
            <?php if (empty($recentLogs)): ?>
            <div class="dashboard-activity-item">
              <span class="dashboard-activity-user">—</span>
              <span class="dashboard-activity-desc">No recent activity found.</span>
              <span class="dashboard-activity-time"></span>
            </div>
            <?php else: ?>
            <?php foreach ($recentLogs as $log):
              $logTime = $log['CREATED_AT'] instanceof DateTime
                ? $log['CREATED_AT']->format('g:i A')
                : date('g:i A', strtotime($log['CREATED_AT']));
            ?>
            <div class="dashboard-activity-item">
              <span class="dashboard-activity-user"><?= htmlspecialchars(rtrim($log['USERNAME'])) ?></span>
              <span class="dashboard-activity-desc">
                <?= htmlspecialchars(rtrim($log['ACTION'])) ?>
                <?php if (!empty($log['DETAILS'])): ?> — <?= htmlspecialchars(rtrim($log['DETAILS'])) ?><?php endif; ?>
              </span>
              <span class="dashboard-activity-time"><?= $logTime ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

<script>
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>
</body>
</html>