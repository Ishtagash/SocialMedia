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
    <style>
      :root {
        --dark: #051650;
        --dark-hover: #0a2470;
        --lime: #ccff00;
        --lime-dim: rgba(204,255,0,0.12);
        --lime-border: rgba(204,255,0,0.3);
        --white: #ffffff;
        --sidebar-w: 260px;
        --bg: #eef0f8;
        --border: rgba(5,22,80,0.1);
      }

      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        font-family: 'DM Sans', Arial, sans-serif;
        background: var(--bg);
        color: var(--dark);
        min-height: 100vh;
        display: flex;
      }

      .container {
        display: flex;
        width: 100%;
        min-height: 100vh;
      }

      .sidebar {
        width: var(--sidebar-w);
        background: var(--dark);
        border-right: 3px solid var(--lime);
        display: flex;
        flex-direction: column;
        padding: 0 0 24px;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 100;
      }

      .sidebar-brand {
        padding: 22px 22px 16px;
        border-bottom: 1px solid rgba(204,255,0,0.15);
        margin-bottom: 4px;
      }
      .sidebar-brand h2 {
        font-size: 18px;
        font-weight: 700;
        color: var(--white);
        letter-spacing: -0.3px;
      }
      .sidebar-brand span {
        font-size: 10px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--lime);
        font-weight: 700;
      }

      .profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 22px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        margin-bottom: 8px;
      }
      .avatar-ring {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        border: 2px solid var(--lime);
        overflow: hidden;
        flex-shrink: 0;
      }
      .avatar-ring img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .profile-meta h3 {
        font-size: 14px;
        font-weight: 700;
        color: var(--white);
        line-height: 1.3;
      }
      .profile-meta p {
        font-size: 11px;
        color: rgba(255,255,255,0.45);
        margin-top: 2px;
      }
      .portal-badge {
        display: inline-block;
        background: var(--lime);
        color: var(--dark);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 3px;
        margin-top: 5px;
      }

      .menu {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 0 12px;
        flex: 1;
      }
      .menu a {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 14px;
        border-radius: 7px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
      }
      .menu a:hover { background: rgba(255,255,255,0.07); color: var(--white); }
      .menu a.active { background: var(--lime-dim); color: var(--lime); border: 1px solid var(--lime-border); }
      .menu a .nav-icon { width: 18px; text-align: center; font-size: 14px; }

      .community-sidebar-section {
        padding: 18px 22px 6px;
      }
      .community-sidebar-section h4 {
        font-size: 10px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: rgba(255,255,255,0.3);
        font-weight: 700;
      }

      .logout {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 26px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255,255,255,0.45);
        text-decoration: none;
        transition: color 0.15s;
        margin-top: 12px;
      }
      .logout:hover { color: #ff6b6b; }

      .content {
        margin-left: var(--sidebar-w);
        flex: 1;
        min-height: 100vh;
      }
      .content-inner {
        padding: 32px 36px;
        max-width: 1100px;
      }

      .topbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 28px;
        gap: 16px;
      }
      .greeting-block h1 {
        font-size: 26px;
        font-weight: 700;
        color: var(--dark);
        line-height: 1.2;
      }
      .accent-name {
        color: var(--dark);
        position: relative;
        display: inline-block;
      }
      .accent-name::after {
        content: '';
        display: block;
        height: 3px;
        background: var(--lime);
        border-radius: 2px;
        margin-top: 1px;
      }
      .subtitle { font-size: 14px; color: #666; margin-top: 4px; }

      .topbar-right { display: flex; align-items: center; gap: 12px; }
      .user-chip {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 50px;
        padding: 6px 14px 6px 6px;
        box-shadow: 0 2px 8px rgba(5,22,80,0.07);
      }
      .user-chip-avatar-wrap {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid var(--lime);
        flex-shrink: 0;
      }
      .user-chip-img { width: 100%; height: 100%; object-fit: cover; }
      .user-chip-info { display: flex; flex-direction: column; }
      .user-chip-name { font-size: 13px; font-weight: 700; color: var(--dark); line-height: 1.2; }
      .user-chip-role { font-size: 10px; color: #999; letter-spacing: 0.5px; text-transform: uppercase; }
      .user-chip-bell-wrap {
        position: relative;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--dark);
        border-radius: 50%;
        text-decoration: none;
        margin-left: 4px;
      }
      .user-chip-bell { font-size: 13px; color: var(--lime); }
      .user-chip-notif {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #e03030;
        color: var(--white);
        font-size: 9px;
        font-weight: 700;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .card-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
      }
      .card {
        background: var(--white);
        border-radius: 10px;
        padding: 22px 20px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(5,22,80,0.06);
        border-left: 4px solid transparent;
      }
      .card--yellow { border-left-color: #f5c518; }
      .card--blue   { border-left-color: #1565c0; }
      .card--red    { border-left-color: #e03030; }
      .card--green  { border-left-color: #2e7d32; }
      .card-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #888;
        margin-bottom: 10px;
      }
      .number {
        font-size: 36px;
        font-weight: 700;
        color: var(--dark);
        line-height: 1;
        font-family: 'Space Mono', monospace;
      }

      .main-panels {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
      }
      .panel {
        background: var(--white);
        border-radius: 10px;
        border: 1px solid var(--border);
        padding: 24px;
        box-shadow: 0 2px 8px rgba(5,22,80,0.06);
      }
      .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
      }
      .panel-header h2 { font-size: 16px; font-weight: 700; color: var(--dark); }
      .badge {
        background: var(--dark);
        color: var(--lime);
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
      }

      .announcements ul, .contacts ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 14px;
      }
      .announcements li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
      }
      .announcement-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
      }
      .dot--yellow { background: #f5c518; }
      .dot--blue   { background: #1565c0; }
      .dot--green  { background: #2e7d32; }
      .announcements li strong {
        font-size: 14px;
        font-weight: 700;
        color: var(--dark);
        display: block;
      }
      .announcements li p { font-size: 13px; color: #666; margin-top: 2px; }

      .contacts li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
      }
      .contacts li:last-child { border-bottom: none; }
      .contact-label { font-size: 13px; color: #666; }
      .contacts li strong {
        font-size: 14px;
        font-weight: 700;
        color: var(--dark);
        font-family: 'Space Mono', monospace;
      }

      @media (max-width: 900px) {
        .card-grid { grid-template-columns: repeat(2, 1fr); }
        .main-panels { grid-template-columns: 1fr; }
      }
      @media (max-width: 700px) {
        .sidebar { width: 200px; }
        .content { margin-left: 200px; }
        .content-inner { padding: 20px 16px; }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <aside class="sidebar">
        <div class="sidebar-brand">
          <h2>BarangayKonek</h2>
          <span>Resident</span>
        </div>

        <div class="profile">
          <div class="avatar-ring">
            <img src="<?= $profilePicture ?>" alt="Profile Photo" />
          </div>
          <div class="profile-meta">
            <h3><?= $fullName ?></h3>
            <p>City of Imus, Alapan 1-A</p>
            <span class="portal-badge">Resident Portal</span>
          </div>
        </div>

        <nav class="menu">
          <a href="dashboard.php" class="active">
            <i class="fa-solid fa-house nav-icon"></i>
            <span>Dashboard</span>
          </a>
          <a href="request.php">
            <i class="fa-solid fa-file-lines nav-icon"></i>
            <span>Request Documents</span>
          </a>
          <a href="concerns.php">
            <i class="fa-solid fa-circle-exclamation nav-icon"></i>
            <span>Concerns</span>
          </a>
          <a href="community.php">
            <i class="fa-solid fa-users nav-icon"></i>
            <span>Community</span>
          </a>
          <a href="myrequests.php">
            <i class="fa-solid fa-clipboard-list nav-icon"></i>
            <span>My Requests</span>
          </a>
        </nav>

        <div class="community-sidebar-section">
          <h4>Quick Access</h4>
        </div>

        <a href="logout.php" class="logout">
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