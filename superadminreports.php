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

$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$docFees = [
    'Barangay Clearance'       => 100,
    'Certificate of Residency' => 50,
    'Business Clearance'       => 200,
    'Indigency Certificate'    => 0,
    'Good Moral Certificate'   => 75,
    'Certificate of Indigency' => 0,
];

$totalRequests = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ?", [$yearFilter]);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $totalRequests = (int)$row['CNT']; }

$completedRequests = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? AND STATUS = 'COMPLETED'", [$yearFilter]);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $completedRequests = (int)$row['CNT']; }

$totalConcerns = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM CONCERNS WHERE YEAR(CREATED_AT) = ?", [$yearFilter]);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $totalConcerns = (int)$row['CNT']; }

$resolvedConcerns = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM CONCERNS WHERE YEAR(CREATED_AT) = ? AND STATUS = 'RESOLVED'", [$yearFilter]);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $resolvedConcerns = (int)$row['CNT']; }

$newResidents = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE = 'resident' AND YEAR(CREATED_AT) = ?", [$yearFilter]);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $newResidents = (int)$row['CNT']; }

$docByMonth = array_fill(0, 12, 0);
$dr = sqlsrv_query($conn,
    "SELECT MONTH(CREATED_AT) AS MN, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? GROUP BY MONTH(CREATED_AT)", [$yearFilter]);
if ($dr) { while ($row = sqlsrv_fetch_array($dr, SQLSRV_FETCH_ASSOC)) { $docByMonth[(int)$row['MN']-1] = (int)$row['CNT']; } }

$concernsResolved = array_fill(0, 12, 0);
$concernsOpen     = array_fill(0, 12, 0);
$cr2 = sqlsrv_query($conn,
    "SELECT MONTH(CREATED_AT) AS MN, STATUS, COUNT(*) AS CNT FROM CONCERNS WHERE YEAR(CREATED_AT) = ? GROUP BY MONTH(CREATED_AT), STATUS", [$yearFilter]);
if ($cr2) {
    while ($row = sqlsrv_fetch_array($cr2, SQLSRV_FETCH_ASSOC)) {
        $idx = (int)$row['MN'] - 1;
        if (strtoupper(rtrim($row['STATUS'])) === 'RESOLVED') $concernsResolved[$idx] = (int)$row['CNT'];
        else $concernsOpen[$idx] = (int)$row['CNT'];
    }
}

$docTypeBreakdown = [];
$dt = sqlsrv_query($conn,
    "SELECT DOCUMENT_TYPE, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? GROUP BY DOCUMENT_TYPE ORDER BY CNT DESC", [$yearFilter]);
if ($dt) { while ($row = sqlsrv_fetch_array($dt, SQLSRV_FETCH_ASSOC)) { $docTypeBreakdown[] = $row; } }
$maxDocType = $docTypeBreakdown ? max(array_column($docTypeBreakdown, 'CNT')) : 1;

$concernBySubject = [];
$cs = sqlsrv_query($conn,
    "SELECT SUBJECT, COUNT(*) AS TOTAL,
            SUM(CASE WHEN STATUS='RESOLVED' THEN 1 ELSE 0 END) AS RESOLVED,
            SUM(CASE WHEN STATUS!='RESOLVED' THEN 1 ELSE 0 END) AS OPEN_COUNT
     FROM CONCERNS WHERE YEAR(CREATED_AT) = ? GROUP BY SUBJECT ORDER BY TOTAL DESC", [$yearFilter]);
if ($cs) { while ($row = sqlsrv_fetch_array($cs, SQLSRV_FETCH_ASSOC)) { $concernBySubject[] = $row; } }

$earningsByDocType = [];
$totalEarnings = 0;
$completedByType = sqlsrv_query($conn,
    "SELECT DOCUMENT_TYPE, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? AND STATUS = 'COMPLETED' GROUP BY DOCUMENT_TYPE", [$yearFilter]);
if ($completedByType) {
    while ($row = sqlsrv_fetch_array($completedByType, SQLSRV_FETCH_ASSOC)) {
        $docType   = rtrim($row['DOCUMENT_TYPE']);
        $cnt       = (int)$row['CNT'];
        $fee       = $docFees[$docType] ?? 50;
        $subtotal  = $cnt * $fee;
        $totalEarnings += $subtotal;
        $earningsByDocType[] = ['type' => $docType, 'count' => $cnt, 'fee' => $fee, 'subtotal' => $subtotal];
    }
}
usort($earningsByDocType, fn($a,$b) => $b['subtotal'] - $a['subtotal']);

$earningsByMonth = array_fill(0, 12, 0);
$ebm = sqlsrv_query($conn,
    "SELECT MONTH(CREATED_AT) AS MN, DOCUMENT_TYPE, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? AND STATUS = 'COMPLETED' GROUP BY MONTH(CREATED_AT), DOCUMENT_TYPE", [$yearFilter]);
if ($ebm) {
    while ($row = sqlsrv_fetch_array($ebm, SQLSRV_FETCH_ASSOC)) {
        $idx  = (int)$row['MN'] - 1;
        $fee  = $docFees[rtrim($row['DOCUMENT_TYPE'])] ?? 50;
        $earningsByMonth[$idx] += (int)$row['CNT'] * $fee;
    }
}

$pendingCount = $approvedCount = $rejectedCount = $completedCount = 0;
$sb = sqlsrv_query($conn, "SELECT STATUS, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE YEAR(CREATED_AT) = ? GROUP BY STATUS", [$yearFilter]);
if ($sb) {
    while ($row = sqlsrv_fetch_array($sb, SQLSRV_FETCH_ASSOC)) {
        $s = strtoupper(rtrim($row['STATUS']));
        if ($s === 'PENDING')   $pendingCount   = (int)$row['CNT'];
        if ($s === 'APPROVED')  $approvedCount  = (int)$row['CNT'];
        if ($s === 'REJECTED')  $rejectedCount  = (int)$row['CNT'];
        if ($s === 'COMPLETED') $completedCount = (int)$row['CNT'];
    }
}

$availableYears = [];
$yr = sqlsrv_query($conn, "SELECT DISTINCT YEAR(CREATED_AT) AS YR FROM DOCUMENT_REQUESTS ORDER BY YR DESC");
if ($yr) { while ($row = sqlsrv_fetch_array($yr, SQLSRV_FETCH_ASSOC)) { $availableYears[] = (int)$row['YR']; } }
if (!in_array($yearFilter, $availableYears)) $availableYears[] = $yearFilter;
rsort($availableYears);

$chartLabels       = json_encode(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']);
$chartDocData      = json_encode($docByMonth);
$chartResolvedData = json_encode($concernsResolved);
$chartOpenData     = json_encode($concernsOpen);
$chartEarningsData = json_encode($earningsByMonth);
$statusLabels      = json_encode(['Pending','Approved','Rejected','Completed']);
$statusData        = json_encode([$pendingCount, $approvedCount, $rejectedCount, $completedCount]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="superadmin.css" />
  <style>
    .reports-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap}
    .reports-topbar-left h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 3px}
    .reports-topbar-left p{font-size:13px;color:var(--text-muted);margin:0}
    .reports-select{height:38px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;min-width:120px}
    .reports-stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
    .reports-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:4px}
    .reports-stat-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
    .reports-stat-value{font-size:34px;font-weight:700;color:var(--navy);line-height:1;margin-bottom:4px}
    .reports-stat-note{font-size:12px;color:var(--text-muted);line-height:1.4}
    .reports-stat-card.earnings-card{border-top:4px solid var(--lime)}
    .reports-body{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:16px;align-items:start}
    .reports-col{display:flex;flex-direction:column;gap:16px;min-width:0}
    .reports-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .reports-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)}
    .reports-panel-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .reports-panel-head p{font-size:11px;color:var(--text-muted);margin:2px 0 0}
    .reports-panel-head-left{display:flex;flex-direction:column}
    .reports-panel-body{padding:20px 18px}
    .reports-chart-wrap{width:100%;position:relative}
    .reports-chart-wrap canvas{width:100%!important;display:block}
    .donut-wrap{position:relative;height:180px}
    .reports-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px}
    .reports-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);font-weight:600}
    .reports-legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
    .reports-breakdown-table{width:100%;border-collapse:collapse}
    .reports-breakdown-table thead th{text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;background:rgba(5,22,80,.02);border-bottom:1px solid var(--border)}
    .reports-breakdown-table tbody td{padding:13px 14px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);vertical-align:middle}
    .reports-breakdown-table tbody tr:last-child td{border-bottom:none}
    .reports-breakdown-table tbody tr:hover{background:rgba(204,255,0,.04)}
    .reports-bar-wrap{width:100%;height:6px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden}
    .reports-bar-fill{height:100%;border-radius:999px;background:var(--navy);opacity:.65}
    .reports-concern-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border)}
    .reports-concern-item:last-child{border-bottom:none}
    .reports-concern-left{display:flex;flex-direction:column;gap:2px}
    .reports-concern-name{font-size:13px;font-weight:700;color:var(--navy)}
    .reports-concern-sub{font-size:11px;color:var(--text-muted)}
    .reports-concern-count{font-size:18px;font-weight:700;color:var(--navy);flex-shrink:0}
    .earnings-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border)}
    .earnings-row:last-child{border-bottom:none}
    .earnings-row.total-row{background:rgba(5,22,80,.02);border-top:2px solid var(--border)}
    .earnings-doc-name strong{font-weight:700;color:var(--navy);font-size:13px}
    .earnings-doc-meta{font-size:11px;color:var(--text-muted)}
    .earnings-amount{font-size:14px;font-weight:700;color:var(--navy);white-space:nowrap}
    .earnings-amount.total{font-size:16px}
    .chart-note{font-size:11px;color:var(--text-muted);margin-top:10px;line-height:1.5}
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    @media(max-width:1200px){.reports-stat-row{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:1100px){.reports-body{grid-template-columns:1fr}}
    @media(max-width:640px){.reports-stat-row{grid-template-columns:1fr}}
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
      <p>Super Admin</p>
    </div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php" class="active">Reports</a>
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
    <div class="reports-topbar">
      <div class="reports-topbar-left">
        <h2>System Reports</h2>
        <p>Data overview for <?= $yearFilter ?></p>
      </div>
      <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <span style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;">Year</span>
        <select class="reports-select" name="year" onchange="this.form.submit()">
          <?php foreach ($availableYears as $yr): ?>
          <option value="<?= $yr ?>" <?= $yr === $yearFilter ? 'selected' : '' ?>><?= $yr ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="reports-stat-row">
      <div class="reports-stat-card">
        <span class="reports-stat-label">Total Requests</span>
        <span class="reports-stat-value"><?= $totalRequests ?></span>
        <span class="reports-stat-note">Filed in <?= $yearFilter ?></span>
      </div>
      <div class="reports-stat-card">
        <span class="reports-stat-label">Completed</span>
        <span class="reports-stat-value"><?= $completedRequests ?></span>
        <span class="reports-stat-note"><?= $totalRequests > 0 ? round(($completedRequests/$totalRequests)*100) : 0 ?>% completion rate</span>
      </div>
      <div class="reports-stat-card">
        <span class="reports-stat-label">Concerns Filed</span>
        <span class="reports-stat-value"><?= $totalConcerns ?></span>
        <span class="reports-stat-note"><?= $resolvedConcerns ?> resolved · <?= $totalConcerns - $resolvedConcerns ?> open</span>
      </div>
      <div class="reports-stat-card earnings-card">
        <span class="reports-stat-label">Estimated Earnings</span>
        <span class="reports-stat-value">₱<?= number_format($totalEarnings) ?></span>
        <span class="reports-stat-note">From completed document fees</span>
      </div>
    </div>

    <div class="reports-body">
      <div class="reports-col">

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Document Requests by Month</h4>
              <p>Total submitted per month — <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body">
            <div class="reports-chart-wrap">
              <canvas id="docChart" height="190"></canvas>
            </div>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Monthly Earnings (₱)</h4>
              <p>Revenue from completed requests — <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body">
            <div class="reports-chart-wrap">
              <canvas id="earningsChart" height="190"></canvas>
            </div>
            <p class="chart-note">Fee schedule: Barangay Clearance ₱100 · Cert. of Residency ₱50 · Business Clearance ₱200 · Good Moral ₱75 · Indigency ₱0</p>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Concerns by Month</h4>
              <p>Resolved vs open — <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body">
            <div class="reports-chart-wrap">
              <canvas id="concernChart" height="190"></canvas>
            </div>
            <div class="reports-legend">
              <div class="reports-legend-item">
                <div class="reports-legend-dot" style="background:rgba(5,22,80,.80)"></div> Resolved
              </div>
              <div class="reports-legend-item">
                <div class="reports-legend-dot" style="background:rgba(5,22,80,.30)"></div> Open / Pending
              </div>
            </div>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Document Type Breakdown</h4>
              <p>Most requested types in <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body" style="padding:0;">
            <table class="reports-breakdown-table">
              <thead>
                <tr>
                  <th>Document Type</th>
                  <th>Requests</th>
                  <th style="width:28%;">Share</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($docTypeBreakdown)): ?>
                <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">No data.</td></tr>
                <?php else: ?>
                <?php foreach ($docTypeBreakdown as $dt):
                  $pct = $maxDocType > 0 ? round(($dt['CNT']/$maxDocType)*100) : 0;
                ?>
                <tr>
                  <td><?= htmlspecialchars(rtrim($dt['DOCUMENT_TYPE'])) ?></td>
                  <td><?= $dt['CNT'] ?></td>
                  <td><div class="reports-bar-wrap"><div class="reports-bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="reports-col">

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Request Status Breakdown</h4>
              <p>All <?= $totalRequests ?> requests in <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body">
            <div class="donut-wrap">
              <canvas id="statusChart"></canvas>
            </div>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Earnings by Document Type</h4>
              <p>Fees from completed requests in <?= $yearFilter ?></p>
            </div>
          </div>
          <div>
            <?php if (empty($earningsByDocType)): ?>
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No completed requests yet.</div>
            <?php else: ?>
            <?php foreach ($earningsByDocType as $e): ?>
            <div class="earnings-row">
              <div>
                <div class="earnings-doc-name"><strong><?= htmlspecialchars($e['type']) ?></strong></div>
                <div class="earnings-doc-meta"><?= $e['count'] ?> completed × ₱<?= number_format($e['fee']) ?></div>
              </div>
              <span class="earnings-amount">₱<?= number_format($e['subtotal']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="earnings-row total-row">
              <div>
                <div class="earnings-doc-name"><strong>Total Collected</strong></div>
                <div class="earnings-doc-meta">All types — <?= $yearFilter ?></div>
              </div>
              <span class="earnings-amount total">₱<?= number_format($totalEarnings) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>Concerns by Subject</h4>
              <p>Top categories in <?= $yearFilter ?></p>
            </div>
          </div>
          <div>
            <?php if (empty($concernBySubject)): ?>
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No concerns recorded.</div>
            <?php else: ?>
            <?php foreach ($concernBySubject as $c): ?>
            <div class="reports-concern-item">
              <div class="reports-concern-left">
                <span class="reports-concern-name"><?= htmlspecialchars(rtrim($c['SUBJECT'])) ?></span>
                <span class="reports-concern-sub"><?= $c['RESOLVED'] ?> resolved · <?= $c['OPEN_COUNT'] ?> open</span>
              </div>
              <span class="reports-concern-count"><?= $c['TOTAL'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="reports-panel">
          <div class="reports-panel-head">
            <div class="reports-panel-head-left">
              <h4>New Residents</h4>
              <p>Accounts registered in <?= $yearFilter ?></p>
            </div>
          </div>
          <div class="reports-panel-body" style="text-align:center;padding:24px 18px;">
            <span style="font-size:48px;font-weight:700;color:var(--navy);display:block;line-height:1;"><?= $newResidents ?></span>
            <span style="font-size:13px;color:var(--text-muted);display:block;margin-top:8px;">New accounts in <?= $yearFilter ?></span>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const navy    = 'rgba(5,22,80,0.80)';
const navyMid = 'rgba(5,22,80,0.35)';
const labels  = <?= $chartLabels ?>;
const sharedFont    = { family: 'DM Sans, sans-serif', size: 12 };
const sharedGrid    = 'rgba(5,22,80,0.06)';
const sharedTick    = 'rgba(5,22,80,0.4)';
const sharedTooltip = { backgroundColor:'#fff', borderColor:'rgba(5,22,80,0.12)', borderWidth:1,
                        titleColor:'rgba(5,22,80,1)', bodyColor:'rgba(5,22,80,0.7)', padding:10 };
Chart.defaults.font = sharedFont;

new Chart(document.getElementById('docChart'), {
  type:'bar',
  data:{ labels, datasets:[{ label:'Requests', data:<?= $chartDocData ?>, backgroundColor:navy, borderRadius:5, borderSkipped:false }] },
  options:{ responsive:true, plugins:{ legend:{display:false}, tooltip:sharedTooltip },
    scales:{ x:{grid:{color:sharedGrid},ticks:{color:sharedTick}}, y:{beginAtZero:true,grid:{color:sharedGrid},ticks:{color:sharedTick,stepSize:5}} } }
});

new Chart(document.getElementById('earningsChart'), {
  type:'bar',
  data:{ labels, datasets:[{ label:'Earnings (₱)', data:<?= $chartEarningsData ?>, backgroundColor:'rgba(204,255,0,0.75)', borderColor:'rgba(184,232,0,1)', borderWidth:1, borderRadius:5, borderSkipped:false }] },
  options:{ responsive:true, plugins:{ legend:{display:false},
    tooltip:{ ...sharedTooltip, callbacks:{ label: ctx => ' ₱' + ctx.parsed.y.toLocaleString() } } },
    scales:{ x:{grid:{color:sharedGrid},ticks:{color:sharedTick}},
      y:{beginAtZero:true,grid:{color:sharedGrid},ticks:{color:sharedTick,callback:v=>'₱'+v.toLocaleString()}} } }
});

new Chart(document.getElementById('concernChart'), {
  type:'line',
  data:{ labels, datasets:[
    { label:'Resolved', data:<?= $chartResolvedData ?>, borderColor:navy, backgroundColor:'rgba(5,22,80,0.06)', borderWidth:2, pointRadius:4, pointBackgroundColor:navy, tension:0.35, fill:true },
    { label:'Open', data:<?= $chartOpenData ?>, borderColor:navyMid, backgroundColor:'rgba(5,22,80,0.03)', borderWidth:2, pointRadius:4, pointBackgroundColor:navyMid, tension:0.35, fill:true }
  ]},
  options:{ responsive:true, plugins:{ legend:{display:false}, tooltip:sharedTooltip },
    scales:{ x:{grid:{color:sharedGrid},ticks:{color:sharedTick}}, y:{beginAtZero:true,grid:{color:sharedGrid},ticks:{color:sharedTick,stepSize:2}} } }
});

new Chart(document.getElementById('statusChart'), {
  type:'doughnut',
  data:{
    labels:<?= $statusLabels ?>,
    datasets:[{ data:<?= $statusData ?>,
      backgroundColor:['rgba(245,158,11,0.75)','rgba(34,197,94,0.75)','rgba(239,68,68,0.75)','rgba(5,22,80,0.75)'],
      borderWidth:2, borderColor:'#fff' }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    cutout:'62%',
    plugins:{
      legend:{ position:'bottom', labels:{ font:{family:'DM Sans, sans-serif',size:11}, padding:10, boxWidth:12 } },
      tooltip:sharedTooltip
    }
  }
});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) { if (e.target===this) closeLogout(); });
</script>
</body>
</html>