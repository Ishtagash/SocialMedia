<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php"); exit();
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
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']).' '.rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';
$firstName = $nameRow ? htmlspecialchars(rtrim($nameRow['FIRST_NAME'])) : 'Admin';

function sqn($conn, $sql, $p = []) {
    $r = sqlsrv_query($conn, $sql, $p ?: []);
    if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); return (int)($row['CNT'] ?? 0); }
    return 0;
}

/* ── THINGS THAT NEED ACTION ── */
$pending_residents   = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='pending'");
$pending_complaints  = sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE STATUS='pending'");
$disabled_staff      = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND STATUS='inactive'");

/* ── VACANT POSITIONS ── */
$singlePos = ['Punong Barangay','Secretary','Treasurer','SK Chairperson'];
$vacantSingle = 0;
$vacantList   = [];
foreach ($singlePos as $p) {
    $c = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION=?",[$p]);
    if ($c === 0) { $vacantSingle++; $vacantList[] = $p; }
}
$kagawadFilled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION='Kagawad'");
$kagawadVacant = max(0, 7 - $kagawadFilled);
$totalVacant   = $vacantSingle + $kagawadVacant;

/* ── QUICK OVERVIEW ── */
$total_residents = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident'");
$total_staff     = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff'");
$total_posts     = sqn($conn,"SELECT COUNT(*) AS CNT FROM POSTS");
$active_ann      = sqn($conn,"SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE=1");
$log_today       = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");

/* ── RECENT PENDING RESIDENTS (top 5) ── */
$pendingRes = [];
$pr = sqlsrv_query($conn,
    "SELECT TOP 5 U.USER_ID, U.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, R.MOBILE_NUMBER
     FROM USERS U LEFT JOIN REGISTRATION R ON R.USER_ID=U.USER_ID
     WHERE U.ROLE='resident' AND U.STATUS='pending'
     ORDER BY U.CREATED_AT DESC");
if ($pr) { while ($row=sqlsrv_fetch_array($pr,SQLSRV_FETCH_ASSOC)) {
    $fn=rtrim($row['FIRST_NAME']??''); $ln=rtrim($row['LAST_NAME']??'');
    $d = $row['CREATED_AT'] instanceof DateTime ? $row['CREATED_AT']->format('M d, Y') : date('M d, Y',strtotime($row['CREATED_AT']??'now'));
    $pendingRes[] = ['id'=>(int)$row['USER_ID'],'name'=>trim("$fn $ln")?:('User #'.(int)$row['USER_ID']),'date'=>$d,'mobile'=>rtrim($row['MOBILE_NUMBER']??'——'),'initials'=>strtoupper(substr($fn,0,1).substr($ln,0,1))];
}}

/* ── RECENT PENDING COMPLAINTS (top 5) ── */
$pendingComplaints = [];
$pc = sqlsrv_query($conn,
    "SELECT TOP 5 C.COMPLAINT_ID, C.SUBJECT, C.CREATED_AT,
            R.FIRST_NAME, R.LAST_NAME
     FROM COMPLAINTS C
     LEFT JOIN USERS U ON U.USER_ID=C.USER_ID
     LEFT JOIN REGISTRATION R ON R.USER_ID=C.USER_ID
     WHERE C.STATUS='pending'
     ORDER BY C.CREATED_AT DESC");
if ($pc) { while ($row=sqlsrv_fetch_array($pc,SQLSRV_FETCH_ASSOC)) {
    $fn=rtrim($row['FIRST_NAME']??''); $ln=rtrim($row['LAST_NAME']??'');
    $d = $row['CREATED_AT'] instanceof DateTime ? $row['CREATED_AT']->format('M d, Y') : date('M d, Y',strtotime($row['CREATED_AT']??'now'));
    $pendingComplaints[] = ['id'=>(int)$row['COMPLAINT_ID'],'subject'=>rtrim($row['SUBJECT']??'—'),'name'=>trim("$fn $ln")?:'Unknown','date'=>$d,'initials'=>strtoupper(substr($fn,0,1).substr($ln,0,1))];
}}

/* ── RECENT AUDIT LOG (top 6) ── */
$recentLogs = [];
$rl = sqlsrv_query($conn,
    "SELECT TOP 6 L.ACTION, L.DETAILS, L.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, U.USERNAME
     FROM AUDIT_LOGS L
     LEFT JOIN USERS U ON U.USER_ID=L.USER_ID
     LEFT JOIN REGISTRATION R ON R.USER_ID=L.USER_ID
     ORDER BY L.CREATED_AT DESC");
if ($rl) { while ($row=sqlsrv_fetch_array($rl,SQLSRV_FETCH_ASSOC)) {
    $fn=rtrim($row['FIRST_NAME']??''); $ln=rtrim($row['LAST_NAME']??''); $un=rtrim($row['USERNAME']??'');
    $name=trim("$fn $ln")?:($un?:'System');
    $ts = $row['CREATED_AT'] instanceof DateTime ? $row['CREATED_AT']->format('M d, g:i A') : date('M d, g:i A',strtotime($row['CREATED_AT']??'now'));
    $recentLogs[] = ['action'=>rtrim($row['ACTION']??''),'details'=>rtrim($row['DETAILS']??''),'name'=>$name,'ts'=>$ts,'initials'=>strtoupper(substr($fn?:$un,0,1).substr($ln,0,1))];
}}

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$today = date('l, F j, Y');

$totalActions = $pending_residents + $pending_complaints + $totalVacant;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Dashboard — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <style>
    .db-wrap{display:flex;flex-direction:column;gap:24px}

    /* HERO */
    .db-hero{background:var(--navy);border-radius:16px;padding:28px 32px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .db-hero-left h2{font-size:22px;font-weight:700;color:#fff;margin:0 0 4px}
    .db-hero-left p{font-size:13px;color:rgba(255,255,255,.6);margin:0}
    .db-hero-badge{background:var(--lime);color:var(--navy);border-radius:10px;padding:10px 18px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;flex-shrink:0;position:relative;cursor:default}
    .db-hero-badge .badge-num{font-size:22px;font-weight:700;line-height:1}
    .badge-tooltip{visibility:hidden;opacity:0;position:absolute;top:calc(100% + 10px);right:0;background:var(--navy);color:#fff;border-radius:10px;padding:14px 16px;min-width:230px;box-shadow:0 8px 32px rgba(5,22,80,.25);z-index:100;font-weight:400;transition:opacity .15s ease, visibility .15s ease;transition-delay:0s}
    .badge-tooltip::before{content:'';position:absolute;top:-6px;right:20px;width:12px;height:12px;background:var(--navy);transform:rotate(45deg);border-radius:2px}
    .db-hero-badge:hover .badge-tooltip{visibility:visible;opacity:1;transition-delay:0s}
    .badge-tooltip:hover{visibility:visible;opacity:1}
    .db-hero-badge:not(:hover) .badge-tooltip:not(:hover){transition-delay:.25s}
    .tooltip-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:7px 8px;border-radius:7px;font-size:12px;text-decoration:none;color:#fff;transition:background .15s}
    .tooltip-item:hover{background:rgba(255,255,255,.12)}
    .tooltip-item-label{opacity:.85;display:flex;align-items:center;gap:6px}
    .tooltip-item-count{font-weight:700;font-size:13px;background:rgba(255,255,255,.18);border-radius:6px;padding:1px 8px;flex-shrink:0}

    /* OVERVIEW STRIP */
    .db-overview{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
    .ov-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 16px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:2px}
    .ov-num{font-size:24px;font-weight:700;color:var(--navy);line-height:1}
    .ov-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px;margin-top:4px}

    /* TWO COL LAYOUT */
    .db-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .db-col-wide{display:grid;grid-template-columns:1fr 1fr;gap:16px}

    /* ACTION PANEL */
    .action-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column}
    .action-panel-head{padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02);display:flex;align-items:center;justify-content:space-between}
    .action-panel-head h4{font-size:13px;font-weight:700;color:var(--navy);margin:0;display:flex;align-items:center;gap:8px}
    .action-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;border-radius:999px;font-size:11px;font-weight:700;padding:0 6px}
    .count-amber{background:rgba(245,158,11,.15);color:#b45309}
    .count-red  {background:rgba(239,68,68,.12);color:#b91c1c}
    .count-blue {background:rgba(59,130,246,.12);color:#1e40af}
    .count-navy {background:rgba(5,22,80,.1);color:var(--navy)}
    .action-panel-body{flex:1;overflow:hidden}
    .action-item{display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid rgba(5,22,80,.05);transition:background .15s}
    .action-item:last-child{border-bottom:none}
    .action-item:hover{background:rgba(5,22,80,.03)}
    .ai-avatar{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
    .ai-info{min-width:0;flex:1}
    .ai-name{font-size:13px;font-weight:600;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ai-sub{font-size:11px;color:var(--text-muted)}
    .ai-date{font-size:11px;color:var(--text-muted);white-space:nowrap;flex-shrink:0}
    .action-empty{padding:24px 18px;text-align:center;color:var(--text-muted);font-size:13px;display:flex;flex-direction:column;align-items:center;gap:6px}
    .action-panel-foot{padding:10px 18px;border-top:1px solid var(--border);text-align:center}
    .action-panel-foot a{font-size:12px;font-weight:700;color:var(--navy);text-decoration:none;opacity:.7}
    .action-panel-foot a:hover{opacity:1}

    /* VACANT POSITIONS */
    .vacant-list{display:flex;flex-direction:column}
    .vacant-item{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid rgba(5,22,80,.05)}
    .vacant-item:last-child{border-bottom:none}
    .vacant-pos{font-size:13px;font-weight:600;color:var(--navy)}
    .vacant-tag{display:inline-flex;align-items:center;height:20px;padding:0 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;background:rgba(245,158,11,.12);color:#b45309}

    /* ACTIVITY FEED */
    .feed-item{display:flex;align-items:flex-start;gap:11px;padding:10px 18px;border-bottom:1px solid rgba(5,22,80,.05)}
    .feed-item:last-child{border-bottom:none}
    .feed-dot{width:8px;height:8px;border-radius:50%;background:var(--navy);flex-shrink:0;margin-top:5px}
    .feed-text{font-size:12px;color:var(--text);line-height:1.5}
    .feed-name{font-weight:700;color:var(--navy)}
    .feed-ts{font-size:11px;color:var(--text-muted);display:block;margin-top:1px}

    /* QUICK NAV */
    .quick-nav{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .qn-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-decoration:none;display:flex;flex-direction:column;align-items:flex-start;gap:8px;box-shadow:var(--shadow);transition:all .18s}
    .qn-card:hover{border-color:var(--navy);transform:translateY(-2px);box-shadow:var(--shadow-lg)}
    .qn-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px}
    .qn-label{font-size:13px;font-weight:700;color:var(--navy)}
    .qn-sub{font-size:11px;color:var(--text-muted)}

    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    @media(max-width:1100px){.db-overview{grid-template-columns:repeat(3,1fr)}.db-cols{grid-template-columns:1fr}}
    @media(max-width:720px){.db-overview{grid-template-columns:repeat(2,1fr)}.quick-nav{grid-template-columns:repeat(2,1fr)}.db-col-wide{grid-template-columns:1fr}}
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-btns">
      <button class="btn-cancel-lo" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm-lo"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand"><h1>Barangay Alapan 1-A</h1><p>Super Admin</p></div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php" class="active">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
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
  <div class="db-wrap">

    <!-- HERO -->
    <div class="db-hero">
      <div class="db-hero-left">
        <h2><?= $greeting ?>, <?= $firstName ?>.</h2>
        <p><?= $today ?></p>
      </div>
      <?php if ($totalActions > 0): ?>
      <div class="db-hero-badge">
        <div>
          <div class="badge-num"><?= $totalActions ?></div>
          <div style="font-size:11px;opacity:.8;">item<?= $totalActions!==1?'s':'' ?> need attention</div>
        </div>
        <i class="fa-solid fa-circle-exclamation" style="font-size:20px;opacity:.7;"></i>
        <div class="badge-tooltip">
          <?php if ($pending_residents > 0): ?>
          <a href="superadminresidentaccount.php?filter=pending" class="tooltip-item">
            <span class="tooltip-item-label"><i class="fa-solid fa-user-clock"></i>Residents pending</span>
            <span class="tooltip-item-count"><?= $pending_residents ?></span>
          </a>
          <?php endif; ?>
          <?php if ($pending_complaints > 0): ?>
          <a href="staffcomplaints.php" class="tooltip-item">
            <span class="tooltip-item-label"><i class="fa-solid fa-circle-exclamation"></i>Complaints</span>
            <span class="tooltip-item-count"><?= $pending_complaints ?></span>
          </a>
          <?php endif; ?>
          <?php if ($totalVacant > 0): ?>
          <a href="superadminstaffaccount.php" class="tooltip-item">
            <span class="tooltip-item-label"><i class="fa-solid fa-chair"></i>Vacant positions</span>
            <span class="tooltip-item-count"><?= $totalVacant ?></span>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="db-hero-badge" style="background:rgba(255,255,255,.15);color:#fff;">
        <i class="fa-solid fa-circle-check" style="font-size:18px;"></i>
        <span>All caught up!</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- OVERVIEW STRIP -->
    <div class="db-overview" style="grid-template-columns:repeat(5,1fr);">
      <div class="ov-card">
        <span class="ov-num"><?= $total_residents ?></span>
        <span class="ov-label">Residents</span>
      </div>
      <div class="ov-card">
        <span class="ov-num"><?= $total_staff ?></span>
        <span class="ov-label">Officials</span>
      </div>
      <div class="ov-card">
        <span class="ov-num"><?= $total_posts ?></span>
        <span class="ov-label">Posts</span>
      </div>
      <div class="ov-card">
        <span class="ov-num"><?= $active_ann ?></span>
        <span class="ov-label">Announcements</span>
      </div>
      <div class="ov-card">
        <span class="ov-num"><?= $log_today ?></span>
        <span class="ov-label">Actions Today</span>
      </div>
    </div>

    <!-- QUICK NAV -->
    <div class="quick-nav">
      <a href="superadminresidentaccount.php?filter=pending" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-user-clock"></i></div>
        <span class="qn-label">Verify Residents</span>
        <span class="qn-sub"><?= $pending_residents ?> pending verification</span>
      </a>
      <a href="superadminstaffaccount.php" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-users-gear"></i></div>
        <span class="qn-label">Assign Positions</span>
        <span class="qn-sub"><?= $totalVacant ?> vacant slot<?= $totalVacant!==1?'s':'' ?></span>
      </a>
      <a href="superadminannouncement.php" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-bullhorn"></i></div>
        <span class="qn-label">Announcements</span>
        <span class="qn-sub"><?= $active_ann ?> currently live</span>
      </a>
      <a href="superadminreports.php" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-chart-bar"></i></div>
        <span class="qn-label">View Reports</span>
        <span class="qn-sub">Analytics &amp; stats</span>
      </a>
      <a href="superadminauditlogs.php" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-clipboard-list"></i></div>
        <span class="qn-label">Audit Logs</span>
        <span class="qn-sub"><?= $log_today ?> actions today</span>
      </a>
      <a href="superadminresidentaccount.php" class="qn-card">
        <div class="qn-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-users"></i></div>
        <span class="qn-label">All Residents</span>
        <span class="qn-sub"><?= $total_residents ?> registered</span>
      </a>
    </div>

    <!-- MAIN COLUMNS -->
    <div class="db-cols">

      <!-- LEFT: PENDING ITEMS -->
      <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Pending Residents -->
        <div class="action-panel">
          <div class="action-panel-head">
            <h4>
              <i class="fa-solid fa-user-clock"></i>
              Pending Resident Verifications
              <span class="action-count count-amber"><?= $pending_residents ?></span>
            </h4>
          </div>
          <div class="action-panel-body">
            <?php if (empty($pendingRes)): ?>
            <div class="action-empty">
              <i class="fa-solid fa-circle-check" style="font-size:22px;opacity:.4;"></i>
              No pending verifications
            </div>
            <?php else: foreach ($pendingRes as $r): ?>
            <div class="action-item">
              <div class="ai-avatar"><?= htmlspecialchars($r['initials']?:'?') ?></div>
              <div class="ai-info">
                <div class="ai-name"><?= htmlspecialchars($r['name']) ?></div>
                <div class="ai-sub"><?= htmlspecialchars($r['mobile']) ?></div>
              </div>
              <div class="ai-date"><?= htmlspecialchars($r['date']) ?></div>
            </div>
            <?php endforeach; endif; ?>
          </div>
          <?php if ($pending_residents > 0): ?>
          <div class="action-panel-foot">
            <a href="superadminresidentaccount.php?filter=pending">View all <?= $pending_residents ?> pending &rarr;</a>
          </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- RIGHT: VACANT POSITIONS + ACTIVITY -->
      <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Vacant Positions -->
        <div class="action-panel">
          <div class="action-panel-head">
            <h4>
              <i class="fa-solid fa-chair"></i>
              Vacant Positions
              <span class="action-count count-amber"><?= $totalVacant ?></span>
            </h4>
          </div>
          <div class="action-panel-body">
            <?php if ($totalVacant === 0): ?>
            <div class="action-empty">
              <i class="fa-solid fa-circle-check" style="font-size:22px;opacity:.4;"></i>
              All positions are filled
            </div>
            <?php else: ?>
            <div class="vacant-list">
              <?php foreach ($vacantList as $vp): ?>
              <div class="vacant-item">
                <span class="vacant-pos"><?= htmlspecialchars($vp) ?></span>
                <span class="vacant-tag">Vacant</span>
              </div>
              <?php endforeach; ?>
              <?php if ($kagawadVacant > 0): ?>
              <div class="vacant-item">
                <span class="vacant-pos">Kagawad (<?= $kagawadFilled ?>/7 filled)</span>
                <span class="vacant-tag"><?= $kagawadVacant ?> open</span>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="action-panel-foot">
            <a href="superadminstaffaccount.php">Manage positions &rarr;</a>
          </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="action-panel">
          <div class="action-panel-head">
            <h4>
              <i class="fa-solid fa-bolt" style="color:var(--navy);"></i>
              Recent Activity
              <span class="action-count count-navy"><?= $log_today ?> today</span>
            </h4>
          </div>
          <div class="action-panel-body">
            <?php if (empty($recentLogs)): ?>
            <div class="action-empty">No recent activity.</div>
            <?php else: foreach ($recentLogs as $log): ?>
            <div class="feed-item">
              <div class="feed-dot"></div>
              <div class="feed-text">
                <span class="feed-name"><?= htmlspecialchars($log['name']) ?></span>
                — <?= htmlspecialchars(mb_strimwidth($log['details'],0,60,'...')) ?>
                <span class="feed-ts"><?= htmlspecialchars($log['ts']) ?></span>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>
          <div class="action-panel-foot">
            <a href="superadminauditlogs.php">Full audit log &rarr;</a>
          </div>
        </div>

      </div>
    </div>

  </div>
  </main>
</div>

<script>
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogout();});
</script>
</body>
</html>