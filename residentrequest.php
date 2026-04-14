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

$regSql  = "SELECT FIRST_NAME, LAST_NAME, ID_PHOTO_PATH FROM REGISTRATION WHERE USER_ID = ?";
$regStmt = sqlsrv_query($conn, $regSql, [$userId]);
$regRow  = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);

$firstName      = $regRow ? htmlspecialchars(rtrim($regRow['FIRST_NAME'])) : 'Resident';
$lastName       = $regRow ? htmlspecialchars(rtrim($regRow['LAST_NAME']))  : '';
$fullName       = $firstName . ' ' . $lastName;
$profilePicture = ($regRow && $regRow['ID_PHOTO_PATH']) ? htmlspecialchars($regRow['ID_PHOTO_PATH']) : 'default_avatar.png';

$annCount = 0;
$annStmt  = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1");
$annRow   = sqlsrv_fetch_array($annStmt, SQLSRV_FETCH_ASSOC);
if ($annRow) $annCount = (int)$annRow['CNT'];

$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchQuery  = isset($_GET['search']) ? trim($_GET['search']) : '';

$statSql  = "SELECT
    COUNT(*) AS TOTAL,
    SUM(CASE WHEN STATUS = 'PENDING'  THEN 1 ELSE 0 END) AS PENDING,
    SUM(CASE WHEN STATUS = 'APPROVED' THEN 1 ELSE 0 END) AS APPROVED,
    SUM(CASE WHEN STATUS = 'REJECTED' THEN 1 ELSE 0 END) AS REJECTED
FROM DOCUMENT_REQUESTS WHERE USER_ID = ?";
$statStmt = sqlsrv_query($conn, $statSql, [$userId]);
$statRow  = sqlsrv_fetch_array($statStmt, SQLSRV_FETCH_ASSOC);

$totalReqs    = $statRow ? (int)$statRow['TOTAL']    : 0;
$pendingReqs  = $statRow ? (int)$statRow['PENDING']  : 0;
$approvedReqs = $statRow ? (int)$statRow['APPROVED'] : 0;
$rejectedReqs = $statRow ? (int)$statRow['REJECTED'] : 0;

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

$latestSql  = "SELECT TOP 3 DOCUMENT_TYPE, STATUS, STAFF_REMARKS FROM DOCUMENT_REQUESTS WHERE USER_ID = ? ORDER BY CREATED_AT DESC";
$latestStmt = sqlsrv_query($conn, $latestSql, [$userId]);
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
    $s = strtoupper(rtrim($status));
    $cls = $map[$s] ?? 'pending';
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
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="resident.css" />
  <link rel="stylesheet" href="base.css" />
</head>
<body>
<div class="container">
  <aside class="sidebar resident-community-sidebar">
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
    <nav class="menu menu--community">
      <a href="residentdashboard.php">
        <i class="fa-solid fa-house nav-icon"></i>
        <span>Dashboard</span>
      </a>
      <a href="residentrequestdocument.php">
        <i class="fa-solid fa-file-lines nav-icon"></i>
        <span>Request Documents</span>
      </a>
      <a href="residentconcern.php">
        <i class="fa-solid fa-circle-exclamation nav-icon"></i>
        <span>Concerns</span>
      </a>
      <a href="residentcommunity.php">
        <i class="fa-solid fa-users nav-icon"></i>
        <span>Community</span>
      </a>
      <a href="residentrequest.php" class="active">
        <i class="fa-solid fa-clipboard-list nav-icon"></i>
        <span>My Requests</span>
      </a>
    </nav>
    <div class="community-sidebar-section">
      <h4>Quick Access</h4>
    </div>
    <a href="home.html" class="logout">
      <i class="fa-solid fa-right-from-bracket nav-icon"></i>
      <span>Logout</span>
    </a>
  </aside>

  <main class="content">
    <div class="topbar">
      <div class="greeting-block">
        <h1>My <span class="accent-name">Requests</span></h1>
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
          <a href="residentdashboard.php" class="user-chip-bell-wrap">
            <i class="fa-solid fa-bell user-chip-bell"></i>
            <span class="user-chip-notif"><?= $annCount ?></span>
          </a>
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
        <p class="request-stat-label">Rejected</p>
        <h3><?= $rejectedReqs ?></h3>
      </div>
    </div>

    <form class="request-toolbar" method="GET" action="residentrequest.php">
      <input type="text" name="search" placeholder="Search request type..." value="<?= htmlspecialchars($searchQuery) ?>" />
      <select name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option <?= $filterStatus === 'PENDING'   ? 'selected' : '' ?>>PENDING</option>
        <option <?= $filterStatus === 'APPROVED'  ? 'selected' : '' ?>>APPROVED</option>
        <option <?= $filterStatus === 'REJECTED'  ? 'selected' : '' ?>>REJECTED</option>
        <option <?= $filterStatus === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
      </select>
    </form>

    <div class="panel request-table-panel">
      <div class="panel-header">
        <h2>Request History</h2>
        <span class="badge">Updated Today</span>
      </div>
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
          <tr>
            <td colspan="6" style="text-align:center;color:#aaa;padding:24px;">No requests found.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($requests as $req): ?>
          <tr>
            <td>REQ-<?= str_pad($req['REQUEST_ID'], 4, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars(rtrim($req['DOCUMENT_TYPE'])) ?></td>
            <td><?= htmlspecialchars(rtrim($req['PURPOSE'])) ?></td>
            <td><?= $req['CREATED_AT']->format('M d, Y') ?></td>
            <td><?= statusBadge($req['STATUS']) ?></td>
            <td><?= $req['STAFF_REMARKS'] ? htmlspecialchars(rtrim($req['STAFF_REMARKS'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="main-panels request-panels">
      <div class="panel">
        <div class="panel-header">
          <h2>Latest Request Update</h2>
        </div>
        <ul class="request-updates">
          <?php if (empty($latestRequests)): ?>
          <li><strong>No requests yet.</strong><p>Submit a document request to get started.</p></li>
          <?php else: ?>
          <?php foreach ($latestRequests as $lr): ?>
          <li>
            <strong><?= htmlspecialchars(rtrim($lr['DOCUMENT_TYPE'])) ?></strong>
            <p><?= $lr['STAFF_REMARKS'] ? htmlspecialchars(rtrim($lr['STAFF_REMARKS'])) : 'Status: ' . rtrim($lr['STATUS']) ?></p>
          </li>
          <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2>Request Guide</h2>
        </div>
        <ul class="request-guide">
          <li><span class="contact-label">Pending</span><strong>Under review</strong></li>
          <li><span class="contact-label">Approved</span><strong>Accepted by staff</strong></li>
          <li><span class="contact-label">Completed</span><strong>Already claimed</strong></li>
          <li><span class="contact-label">Rejected</span><strong>Needs correction</strong></li>
        </ul>
      </div>
    </div>
  </main>
</div>
</body>
</html>