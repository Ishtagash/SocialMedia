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

function sqn($conn, $sql, $p = []) {
    $r = sqlsrv_query($conn, $sql, $p ?: []);
    if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); return (int)($row['CNT'] ?? 0); }
    return 0;
}

/* ── RESIDENTS ── */
$res_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident'");
$res_active   = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='active'");
$res_pending  = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='pending'");
$res_disabled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='inactive'");
$res_rejected = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='rejected'");
$res_today    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$res_week     = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CREATED_AT>=DATEADD(DAY,-7,GETDATE())");
$res_month    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CREATED_AT>=DATEADD(DAY,-30,GETDATE())");

/* registration trend - last 7 days */
$regLabels = []; $regData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('M d', strtotime("-$i days"));
    $cnt = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CAST(CREATED_AT AS DATE)=?", [$date]);
    $regLabels[] = $label; $regData[] = $cnt;
}

/* ── STAFF ── */
$staff_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff'");
$staff_active   = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND STATUS='active'");
$staff_disabled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND STATUS='inactive'");
$positions = ['Punong Barangay','Secretary','Treasurer','SK Chairperson'];
$posData = [];
foreach ($positions as $p) {
    $posData[$p] = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION=?",[$p]);
}
$kagawad_filled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION='Kagawad'");
$vacant = array_sum(array_map(fn($v)=>$v===0?1:0, $posData)) + max(0,7-$kagawad_filled);

/* ── DOCUMENTS ── */
$doc_total     = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS");
$doc_pending   = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='pending'");
$doc_approved  = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='approved'");
$doc_completed = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='completed'");
$doc_rejected  = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='rejected'");

$docTypes = []; $docTypeCounts = [];
$dtr = sqlsrv_query($conn,"SELECT TOP 5 DOCUMENT_TYPE, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS GROUP BY DOCUMENT_TYPE ORDER BY CNT DESC");
if ($dtr) { while ($row=sqlsrv_fetch_array($dtr,SQLSRV_FETCH_ASSOC)) {
    $docTypes[] = rtrim($row['DOCUMENT_TYPE']??''); $docTypeCounts[] = (int)$row['CNT'];
}}

/* document trend - last 7 days */
$docTrendLabels=[]; $docTrendData=[];
for ($i=6;$i>=0;$i--) {
    $date=date('Y-m-d',strtotime("-$i days"));
    $label=date('M d',strtotime("-$i days"));
    $cnt=sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE CAST(CREATED_AT AS DATE)=?",[$date]);
    $docTrendLabels[]=$label; $docTrendData[]=$cnt;
}

/* ── COMPLAINTS ── */
$comp_total   = sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS");
$comp_pending = sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE STATUS='pending'");
$comp_resolved= sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE STATUS='resolved'");

/* ── COMMUNITY ── */
$post_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM POSTS");
$post_today    = sqn($conn,"SELECT COUNT(*) AS CNT FROM POSTS WHERE CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$likes_total   = sqn($conn,"SELECT COUNT(*) AS CNT FROM LIKES");
$comments_total= sqn($conn,"SELECT COUNT(*) AS CNT FROM COMMENTS");
$ann_total     = sqn($conn,"SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS");
$ann_active    = sqn($conn,"SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE STATUS='active'");

/* ── AUDIT LOGS ── */
$log_total = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS");
$log_today = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$log_week  = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CREATED_AT>=DATEADD(DAY,-7,GETDATE())");

$actionCounts=[]; $actionLabels=[];
$acr=sqlsrv_query($conn,"SELECT ACTION,COUNT(*) AS CNT FROM AUDIT_LOGS GROUP BY ACTION ORDER BY CNT DESC");
if ($acr) { while ($row=sqlsrv_fetch_array($acr,SQLSRV_FETCH_ASSOC)) {
    $actionLabels[]=rtrim($row['ACTION']??''); $actionCounts[]=(int)$row['CNT'];
}}

/* audit trend - last 7 days */
$logTrendLabels=[]; $logTrendData=[];
for ($i=6;$i>=0;$i--) {
    $date=date('Y-m-d',strtotime("-$i days"));
    $label=date('M d',strtotime("-$i days"));
    $cnt=sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CAST(CREATED_AT AS DATE)=?",[$date]);
    $logTrendLabels[]=$label; $logTrendData[]=$cnt;
}

$compPct = $comp_total>0 ? round(($comp_resolved/$comp_total)*100) : 0;
$genDate = date('F j, Y');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reports — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <style>
    .rp-wrap{display:flex;flex-direction:column;gap:32px}
    .rp-page-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .rp-page-head h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 4px}
    .rp-page-head p{font-size:13px;color:var(--text-muted);margin:0}
    .rp-date{font-size:12px;color:var(--text-muted);font-weight:600}

    /* section */
    .rp-section{display:flex;flex-direction:column;gap:14px}
    .rp-section-head{display:flex;align-items:center;gap:12px}
    .rp-section-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
    .rp-section-head h3{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .rp-section-line{flex:1;height:1px;background:var(--border)}

    /* kpi row */
    .kpi-row{display:grid;gap:12px}
    .kpi-4{grid-template-columns:repeat(4,1fr)}
    .kpi-3{grid-template-columns:repeat(3,1fr)}
    .kpi-2{grid-template-columns:repeat(2,1fr)}

    .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 18px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:2px;position:relative;overflow:hidden}
    .kpi-card::before{content:'';position:absolute;top:0;left:0;width:3px;height:100%;border-radius:2px 0 0 2px}
    .kpi-navy::before{background:var(--navy)}
    .kpi-navy::before{background:#22c55e}
    .kpi-navy::before{background:#f59e0b}
    .kpi-navy::before{background:#ef4444}
    .kpi-navy::before{background:#3b82f6}
    .kpi-navy::before{background:#8b5cf6}
    .kpi-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;margin-bottom:8px}
    .kpi-num{font-size:28px;font-weight:700;color:var(--navy);line-height:1}
    .kpi-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px;margin-top:3px}
    .kpi-note{font-size:11px;color:var(--text-muted);margin-top:4px}

    /* chart panels */
    .chart-row{display:grid;gap:14px}
    .chart-row-2{grid-template-columns:1fr 1fr}
    .chart-row-3{grid-template-columns:2fr 1fr 1fr}
    .chart-panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column}
    .chart-panel-head{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .chart-panel-head h4{font-size:13px;font-weight:700;color:var(--navy);margin:0}
    .chart-panel-sub{font-size:11px;color:var(--text-muted)}
    .chart-panel-body{padding:16px 18px;flex:1;display:flex;align-items:center;justify-content:center}
    .chart-panel-body canvas{max-height:180px}
    .chart-panel-body.tall canvas{max-height:220px}

    /* position dots */
    .pos-list{display:flex;flex-direction:column;gap:8px;padding:16px 18px;width:100%}
    .pos-item{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .pos-item-name{font-size:12px;font-weight:600;color:var(--navy)}
    .pos-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .pos-dot.on{background:var(--navy)}
    .pos-dot.off{background:#e5e7eb;border:1px solid #d1d5db}
    .pos-kagawad-bar{display:flex;align-items:center;gap:8px;margin-top:4px}
    .pos-kagawad-track{flex:1;height:6px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden}
    .pos-kagawad-fill{height:100%;background:var(--navy);border-radius:999px}
    .pos-kagawad-label{font-size:11px;color:var(--text-muted);white-space:nowrap}

    /* action breakdown list */
    .action-list{display:flex;flex-direction:column;gap:8px;width:100%;padding:0 18px 16px}
    .action-item{display:flex;flex-direction:column;gap:3px}
    .action-item-top{display:flex;justify-content:space-between;align-items:center}
    .action-item-name{font-size:11px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:70%}
    .action-item-cnt{font-size:11px;font-weight:700;color:var(--navy)}
    .action-track{height:5px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden}
    .action-fill{height:100%;border-radius:999px;background:var(--navy)}

    /* resolution ring */
    .ring-wrap{display:flex;flex-direction:column;align-items:center;gap:8px;padding:8px 0}
    .ring-label-big{font-size:28px;font-weight:700;color:var(--navy)}
    .ring-label-sub{font-size:11px;color:var(--text-muted);font-weight:600}
    .ring-meta{display:flex;gap:16px;margin-top:4px}
    .ring-meta-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted)}
    .ring-dot{width:8px;height:8px;border-radius:50%}

    /* logout */
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}

    @media(max-width:1100px){.kpi-4{grid-template-columns:repeat(2,1fr)}.chart-row-3{grid-template-columns:1fr 1fr}}
    @media(max-width:720px){.kpi-4,.kpi-3,.kpi-2,.chart-row-2,.chart-row-3{grid-template-columns:1fr}}
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
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php" class="active">Reports</a>
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
  <div class="rp-wrap">

    <div class="rp-page-head">
      <div>
        <h2>Reports &amp; Analytics</h2>
        <p>System-wide overview — data updated in real time.</p>
      </div>
      <span class="rp-date"><i class="fa-regular fa-calendar" style="margin-right:6px;"></i><?= $genDate ?></span>
    </div>

    <!-- ═══════════════ RESIDENTS ═══════════════ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-users"></i></div>
        <h3>Residents</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-users"></i></div>
          <span class="kpi-num"><?= $res_total ?></span>
          <span class="kpi-label">Total Residents</span>
          <span class="kpi-note"><?= $res_month ?> joined this month</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-circle-check"></i></div>
          <span class="kpi-num"><?= $res_active ?></span>
          <span class="kpi-label">Verified</span>
          <span class="kpi-note">Active accounts</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-clock"></i></div>
          <span class="kpi-num"><?= $res_pending ?></span>
          <span class="kpi-label">Pending</span>
          <span class="kpi-note">Awaiting verification</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-ban"></i></div>
          <span class="kpi-num"><?= $res_disabled + $res_rejected ?></span>
          <span class="kpi-label">Disabled / Rejected</span>
          <span class="kpi-note"><?= $res_disabled ?> disabled &middot; <?= $res_rejected ?> rejected</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>New Registrations — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $res_week ?> this week &middot; <?= $res_today ?> today</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="regTrendChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Account Status Breakdown</h4>
            <span class="chart-panel-sub"><?= $res_total ?> total accounts</span>
          </div>
          <div class="chart-panel-body"><canvas id="resStatusChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ STAFF ═══════════════ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Staff &amp; Positions</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-3">
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-user-tie"></i></div>
          <span class="kpi-num"><?= $staff_total ?></span>
          <span class="kpi-label">Total Officials</span>
          <span class="kpi-note">Assigned barangay staff</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-circle-check"></i></div>
          <span class="kpi-num"><?= $staff_active ?></span>
          <span class="kpi-label">Active</span>
          <span class="kpi-note">Enabled accounts</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <span class="kpi-num"><?= $vacant ?></span>
          <span class="kpi-label">Vacant Slots</span>
          <span class="kpi-note">Unfilled positions</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Staff Status</h4>
            <span class="chart-panel-sub"><?= $staff_total ?> officials total</span>
          </div>
          <div class="chart-panel-body"><canvas id="staffStatusChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Position Fill Status</h4>
            <span class="chart-panel-sub">Kagawad: <?= $kagawad_filled ?>/7 filled</span>
          </div>
          <div class="pos-list">
            <?php foreach ($positions as $p): $f = $posData[$p]>0; ?>
            <div class="pos-item">
              <span class="pos-item-name"><?= htmlspecialchars($p) ?></span>
              <span class="pos-dot <?= $f?'on':'off' ?>"></span>
            </div>
            <?php endforeach; ?>
            <div class="pos-item" style="flex-direction:column;align-items:flex-start;gap:5px;margin-top:4px;">
              <span class="pos-item-name">Kagawad (<?= $kagawad_filled ?>/7)</span>
              <div class="pos-kagawad-bar" style="width:100%;">
                <div class="pos-kagawad-track" style="flex:1;">
                  <div class="pos-kagawad-fill" style="width:<?= round(($kagawad_filled/7)*100) ?>%;"></div>
                </div>
                <span class="pos-kagawad-label"><?= round(($kagawad_filled/7)*100) ?>%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ DOCUMENTS & COMPLAINTS ═══════════════ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-file-lines"></i></div>
        <h3>Document Requests &amp; Complaints</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-file-lines"></i></div>
          <span class="kpi-num"><?= $doc_total ?></span>
          <span class="kpi-label">Total Requests</span>
          <span class="kpi-note"><?= $doc_pending ?> still pending</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-circle-check"></i></div>
          <span class="kpi-num"><?= $doc_completed ?></span>
          <span class="kpi-label">Completed</span>
          <span class="kpi-note"><?= $doc_approved ?> approved</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-circle-exclamation"></i></div>
          <span class="kpi-num"><?= $comp_total ?></span>
          <span class="kpi-label">Complaints</span>
          <span class="kpi-note"><?= $comp_pending ?> unresolved</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-handshake"></i></div>
          <span class="kpi-num"><?= $compPct ?>%</span>
          <span class="kpi-label">Resolution Rate</span>
          <span class="kpi-note"><?= $comp_resolved ?> of <?= $comp_total ?> resolved</span>
        </div>
      </div>

      <div class="chart-row chart-row-3">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Document Requests — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $doc_total ?> total requests</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="docTrendChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Request Status</h4>
          </div>
          <div class="chart-panel-body"><canvas id="docStatusChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Complaint Resolution</h4>
          </div>
          <div class="chart-panel-body">
            <div class="ring-wrap">
              <canvas id="compChart" style="max-height:130px;"></canvas>
              <div class="ring-meta">
                <div class="ring-meta-item"><span class="ring-dot" style="background:var(--navy);"></span><?= $comp_resolved ?> Resolved</div>
                <div class="ring-meta-item"><span class="ring-dot" style="background:rgba(5,22,80,.3);"></span><?= $comp_pending ?> Pending</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($docTypes)): ?>
      <div class="chart-panel">
        <div class="chart-panel-head">
          <h4>Most Requested Document Types</h4>
          <span class="chart-panel-sub">Top <?= count($docTypes) ?> types</span>
        </div>
        <div class="chart-panel-body tall"><canvas id="docTypeChart"></canvas></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══════════════ COMMUNITY ═══════════════ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-comments"></i></div>
        <h3>Community</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-rectangle-list"></i></div>
          <span class="kpi-num"><?= $post_total ?></span>
          <span class="kpi-label">Posts</span>
          <span class="kpi-note"><?= $post_today ?> posted today</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-heart"></i></div>
          <span class="kpi-num"><?= $likes_total ?></span>
          <span class="kpi-label">Total Likes</span>
          <span class="kpi-note">Across all posts</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-comment"></i></div>
          <span class="kpi-num"><?= $comments_total ?></span>
          <span class="kpi-label">Comments</span>
          <span class="kpi-note">Across all posts</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-bullhorn"></i></div>
          <span class="kpi-num"><?= $ann_total ?></span>
          <span class="kpi-label">Announcements</span>
          <span class="kpi-note"><?= $ann_active ?> currently active</span>
        </div>
      </div>

      <div class="chart-panel" style="max-width:100%;">
        <div class="chart-panel-head">
          <h4>Engagement Overview</h4>
          <span class="chart-panel-sub">Posts · Likes · Comments · Announcements</span>
        </div>
        <div class="chart-panel-body"><canvas id="communityChart"></canvas></div>
      </div>
    </div>

    <!-- ═══════════════ AUDIT LOGS ═══════════════ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-clipboard-list"></i></div>
        <h3>Audit Logs</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-3">
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-clipboard-list"></i></div>
          <span class="kpi-num"><?= $log_total ?></span>
          <span class="kpi-label">Total Log Entries</span>
          <span class="kpi-note">All recorded actions</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-calendar-day"></i></div>
          <span class="kpi-num"><?= $log_today ?></span>
          <span class="kpi-label">Today</span>
          <span class="kpi-note">Actions logged today</span>
        </div>
        <div class="kpi-card kpi-navy">
          <div class="kpi-icon" style="background:rgba(5,22,80,.07);color:var(--navy);"><i class="fa-solid fa-calendar-week"></i></div>
          <span class="kpi-num"><?= $log_week ?></span>
          <span class="kpi-label">This Week</span>
          <span class="kpi-note">Last 7 days</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Activity — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $log_week ?> actions this week</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="logTrendChart"></canvas></div>
        </div>
        <?php if (!empty($actionLabels)): ?>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Actions Breakdown</h4>
            <span class="chart-panel-sub"><?= count($actionLabels) ?> distinct action types</span>
          </div>
          <div class="action-list">
            <?php
              $aMax = max($actionCounts) ?: 1;
              foreach ($actionLabels as $i => $al):
                $pct = round(($actionCounts[$i]/$aMax)*100);
            ?>
            <div class="action-item">
              <div class="action-item-top">
                <span class="action-item-name" title="<?= htmlspecialchars($al) ?>"><?= htmlspecialchars($al) ?></span>
                <span class="action-item-cnt"><?= $actionCounts[$i] ?></span>
              </div>
              <div class="action-track"><div class="action-fill" style="width:<?= $pct ?>%;"></div></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
  </main>
</div>

<script>
Chart.defaults.font.family = "'DM Sans','Segoe UI',Arial,sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b7a99';

var navy   = '#051650';
var lime   = '#ccff00';
var green  = navy;
var amber  = navy;
var red    = navy;
var blue   = navy;
var purple = navy;
var muted  = 'rgba(5,22,80,0.07)';

function lineOpts(labels, data, color) {
  return {
    type:'line',
    data:{
      labels:labels,
      datasets:[{
        data:data,
        borderColor:navy,
        backgroundColor:(navy)+'22',
        borderWidth:2,
        pointRadius:3,
        pointBackgroundColor:navy,
        fill:true,
        tension:0.4
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:true,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false},ticks:{maxRotation:0}},
        y:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}}
      }
    }
  };
}

function doughnutOpts(labels, data, colors) {
  return {
    type:'doughnut',
    data:{labels:labels,datasets:[{data:data,backgroundColor:colors,borderWidth:0,hoverOffset:4}]},
    options:{
      responsive:true,maintainAspectRatio:true,cutout:'68%',
      plugins:{legend:{position:'bottom',labels:{boxWidth:10,padding:10,font:{size:10}}}}
    }
  };
}

/* Registration trend */
new Chart('regTrendChart', lineOpts(
  <?= json_encode($regLabels) ?>,
  <?= json_encode($regData) ?>,
  navy
));

/* Resident status doughnut */
new Chart('resStatusChart', doughnutOpts(
  ['Verified','Pending','Disabled','Rejected'],
  [<?= $res_active ?>,<?= $res_pending ?>,<?= $res_disabled ?>,<?= $res_rejected ?>],
  [navy, 'rgba(5,22,80,.4)', 'rgba(5,22,80,.2)', 'rgba(5,22,80,.1)']
));

/* Staff status doughnut */
new Chart('staffStatusChart', doughnutOpts(
  ['Active','Disabled'],
  [<?= $staff_active ?>,<?= $staff_disabled ?>],
  [navy, 'rgba(5,22,80,.15)']
));

/* Document trend */
new Chart('docTrendChart', lineOpts(
  <?= json_encode($docTrendLabels) ?>,
  <?= json_encode($docTrendData) ?>,
  purple
));

/* Document status doughnut */
new Chart('docStatusChart', doughnutOpts(
  ['Pending','Approved','Completed','Rejected'],
  [<?= $doc_pending ?>,<?= $doc_approved ?>,<?= $doc_completed ?>,<?= $doc_rejected ?>],
  [navy, 'rgba(5,22,80,.55)', 'rgba(5,22,80,.3)', 'rgba(5,22,80,.15)']
));

/* Complaint doughnut */
new Chart('compChart', doughnutOpts(
  ['Resolved','Pending'],
  [<?= $comp_resolved ?>,<?= $comp_pending ?>],
  [navy, 'rgba(5,22,80,.2)']
));

<?php if (!empty($docTypes)): ?>
/* Document types bar */
new Chart('docTypeChart', {
  type:'bar',
  data:{
    labels:<?= json_encode($docTypes) ?>,
    datasets:[{
      data:<?= json_encode($docTypeCounts) ?>,
      backgroundColor:navy,
      borderRadius:6,
      barThickness:28
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:true,indexAxis:'y',
    plugins:{legend:{display:false}},
    scales:{
      x:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}},
      y:{grid:{display:false}}
    }
  }
});
<?php endif; ?>

/* Community bar */
new Chart('communityChart', {
  type:'bar',
  data:{
    labels:['Posts','Likes','Comments','Announcements'],
    datasets:[{
      data:[<?= $post_total ?>,<?= $likes_total ?>,<?= $comments_total ?>,<?= $ann_total ?>],
      backgroundColor:[navy, 'rgba(5,22,80,.6)', 'rgba(5,22,80,.35)', 'rgba(5,22,80,.15)'],
      borderRadius:6,
      barThickness:40
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:true,
    plugins:{legend:{display:false}},
    scales:{
      x:{grid:{display:false}},
      y:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}}
    }
  }
});

/* Audit log trend */
new Chart('logTrendChart', lineOpts(
  <?= json_encode($logTrendLabels) ?>,
  <?= json_encode($logTrendData) ?>,
  amber
));

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogout();});
</script>
</body>
</html>