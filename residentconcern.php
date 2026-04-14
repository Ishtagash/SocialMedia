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

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category    = trim($_POST['category']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);
    $dateObserved = trim($_POST['date_observed']);
    $evidencePath = null;

    if (!empty($_FILES['evidence']['name'])) {
        $uploadDir = 'uploads/concerns/';
        $ext          = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
        $evidencePath = $uploadDir . uniqid('concern_') . '.' . $ext;
        move_uploaded_file($_FILES['evidence']['tmp_name'], $evidencePath);
    }

    if (empty($category) || empty($description)) {
        $error = 'Category and description are required.';
    } else {
        $sql  = "INSERT INTO COMPLAINTS (USER_ID, SUBJECT, DESCRIPTION, STATUS, CREATED_AT) VALUES (?, ?, ?, 'OPEN', GETDATE())";
        $subject = $category . ($location ? ' — ' . $location : '');
        $stmt = sqlsrv_query($conn, $sql, [$userId, $subject, $description]);
        if ($stmt === false) {
            $error = 'Failed to submit concern. Please try again.';
        } else {
            $success = true;
        }
    }
}

$annCount = 0;
$annStmt  = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1");
$annRow   = sqlsrv_fetch_array($annStmt, SQLSRV_FETCH_ASSOC);
if ($annRow) $annCount = (int)$annRow['CNT'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Report a Concern — BarangayKonek</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="resident.css" />
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
      <a href="residentconcern.php" class="active">
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
    <div class="topbar">
      <div class="greeting-block">
        <h1>Report a <span class="accent-name">Concern</span></h1>
        <p class="subtitle">Submit barangay-related issues for review.</p>
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

    <div class="concern-layout">
      <div class="panel">
        <div class="panel-header">
          <h2>Concern Report</h2>
          <span class="badge">New</span>
        </div>

        <?php if ($success): ?>
        <div style="background:#f0fff0;border:1px solid #b2dfb2;border-left:4px solid #2e7d32;border-radius:6px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#2e7d32;">
          <i class="fa-solid fa-circle-check" style="margin-top:2px;"></i>
          <span>Your concern has been submitted successfully. You can track it under <a href="residentrequest.php" style="color:#2e7d32;font-weight:700;">My Requests</a>.</span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="background:#fff0f0;border:1px solid #f5c0c0;border-left:4px solid #e03030;border-radius:6px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#e03030;">
          <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form class="concern-form" method="POST" action="residentconcern.php" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group">
              <label>Concern Category</label>
              <select name="category" required>
                <option value="">Select category</option>
                <option>Street Light Issue</option>
                <option>Garbage / Sanitation</option>
                <option>Noise Complaint</option>
                <option>Road Damage</option>
                <option>Flooding / Drainage</option>
                <option>Peace and Order</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Location</label>
              <input type="text" name="location" placeholder="Purok / Sitio / Landmark" />
            </div>
          </div>
          <div class="form-group">
            <label>Describe the Concern</label>
            <textarea name="description" rows="6" placeholder="Provide details about the issue..." required></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Date Observed</label>
              <input type="date" name="date_observed" />
            </div>
            <div class="form-group">
              <label>Upload Evidence</label>
              <input type="file" name="evidence" accept="image/*,.pdf" />
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">Submit Report</button>
            <button type="reset" class="btn btn--outline">Clear</button>
          </div>
        </form>
      </div>

      <div class="panel concern-info">
        <div class="panel-header">
          <h2>Reporting Guidelines</h2>
        </div>
        <ul class="concern-guide">
          <li>
            <strong>Be Specific</strong>
            <p>Include the exact location and details of the issue.</p>
          </li>
          <li>
            <strong>Attach Evidence</strong>
            <p>Photos or documents help barangay staff verify concerns faster.</p>
          </li>
          <li>
            <strong>Follow Up</strong>
            <p>You can check updates in the My Requests page.</p>
          </li>
          <li>
            <strong>Emergency</strong>
            <p>If urgent, contact the barangay hotline directly.</p>
          </li>
        </ul>
      </div>
    </div>
  </main>
</div>
</body>
</html>