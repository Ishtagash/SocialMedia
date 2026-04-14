<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = [
    "Database" => "SocialMedia",
    "Uid"      => "",
    "PWD"      => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$regSql  = "SELECT R.FIRST_NAME, R.LAST_NAME, R.ID_PHOTO_PATH
            FROM REGISTRATION R
            INNER JOIN USERS U ON R.USER_ID = U.USER_ID
            WHERE R.USER_ID = ?";
$regStmt = sqlsrv_query($conn, $regSql, [$userId]);
$regRow  = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);

$firstName      = $regRow ? htmlspecialchars(rtrim($regRow['FIRST_NAME'])) : 'Resident';
$lastName       = $regRow ? htmlspecialchars(rtrim($regRow['LAST_NAME']))  : '';
$fullName       = $firstName . ' ' . $lastName;
$profilePicture = ($regRow && !empty($regRow['ID_PHOTO_PATH'])) ? htmlspecialchars($regRow['ID_PHOTO_PATH']) : 'default_avatar.png';

$pendingSql  = "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? AND STATUS = 'PENDING'";
$pendingStmt = sqlsrv_query($conn, $pendingSql, [$userId]);
$pendingRow  = sqlsrv_fetch_array($pendingStmt, SQLSRV_FETCH_ASSOC);
$pendingCount = $pendingRow ? (int)$pendingRow['CNT'] : 0;

$annSql  = "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1";
$annStmt = sqlsrv_query($conn, $annSql);
$annRow  = sqlsrv_fetch_array($annStmt, SQLSRV_FETCH_ASSOC);
$announcementCount = $annRow ? (int)$annRow['CNT'] : 0;

$concernSql  = "SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE USER_ID = ? AND STATUS = 'OPEN'";
$concernStmt = sqlsrv_query($conn, $concernSql, [$userId]);
$concernRow  = sqlsrv_fetch_array($concernStmt, SQLSRV_FETCH_ASSOC);
$openConcerns = $concernRow ? (int)$concernRow['CNT'] : 0;

$completedSql  = "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE USER_ID = ? AND STATUS = 'COMPLETED'";
$completedStmt = sqlsrv_query($conn, $completedSql, [$userId]);
$completedRow  = sqlsrv_fetch_array($completedStmt, SQLSRV_FETCH_ASSOC);
$completedCount = $completedRow ? (int)$completedRow['CNT'] : 0;

$latestAnnSql  = "SELECT TOP 3 TITLE, BODY FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1 ORDER BY CREATED_AT DESC";
$latestAnnStmt = sqlsrv_query($conn, $latestAnnSql);
$announcements  = [];
while ($row = sqlsrv_fetch_array($latestAnnStmt, SQLSRV_FETCH_ASSOC)) {
    $announcements[] = $row;
}

$dotColors = ['dot--yellow', 'dot--blue', 'dot--green'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Resident Dashboard — Barangay Alapan I-A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="base.css"/>
    <link rel="stylesheet" href="resident.css"/>
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
      <a href="residentdashboard.php" class="active">
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
      <a href="residentrequest.php">
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
        <div class="content-inner">
          <div class="topbar">
            <div class="greeting-block">
              <h1>Welcome back, <span class="accent-name"><?= $firstName ?></span></h1>
              <p class="subtitle">Here are your latest updates and quick actions.</p>
            </div>
            <div class="topbar-right">
              <div class="user-chip">
                <div class="user-chip-avatar-wrap">
                  <img src="<?= $profilePicture ?>" alt="Profile Photo" class="user-chip-img" />
                </div>
                <div class="user-chip-info">
                  <span class="user-chip-name"><?= $fullName ?></span>
                  <span class="user-chip-role">Resident</span>
                </div>
                <a href="notifications.php" class="user-chip-bell-wrap">
                  <i class="fa-solid fa-bell user-chip-bell"></i>
                  <span class="user-chip-notif"><?= $announcementCount ?></span>
                </a>
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
                    <p><?= htmlspecialchars(mb_strimwidth(rtrim($ann['BODY']), 0, 60, '...')) ?></p>
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
                  <span class="contact-label">Barangay Hall</span>
                  <strong>123-4567</strong>
                </li>
                <li>
                  <span class="contact-label">Tanod Hotline</span>
                  <strong>0912-345-6789</strong>
                </li>
                <li>
                  <span class="contact-label">Medical Response</span>
                  <strong>555-1122</strong>
                </li>
                <li>
                  <span class="contact-label">Fire Station (BFP)</span>
                  <strong>160</strong>
                </li>
              </ul>
            </div>
          </div>

        </div>
      </main>
    </div>
  </body>
</html>