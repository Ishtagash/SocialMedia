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
} elseif ($gender === 'male')   { $profilePicture = 'default/male.png'; }
elseif ($gender === 'female')   { $profilePicture = 'default/female.png'; }
else                             { $profilePicture = 'default/neutral.png'; }

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
        } else {
            header("Location: residentconcern.php");
        }
        exit();
    }
    if ($_POST['action'] === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentconcern.php");
        exit();
    }
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($category) || empty($description)) {
        $error = 'Category and description are required.';
    } else {
        $subject = $category . ($location ? ' - ' . $location : '');

        $evidencePath = null;
        if (!empty($_FILES['evidence']['name'])) {
            $uploadDir = 'uploads/concerns/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $originalName = basename($_FILES['evidence']['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (!in_array($ext, $allowed)) {
                $error = 'Only image files (JPG, PNG, GIF, WEBP) and PDF are allowed.';
            } elseif ($_FILES['evidence']['size'] > 5 * 1024 * 1024) {
                $error = 'File size must not exceed 5MB.';
            } else {
                $newName = 'concern_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($_FILES['evidence']['tmp_name'], $destPath)) {
                    $evidencePath = $destPath;
                } else {
                    $error = 'Failed to upload the file. Please try again.';
                }
            }
        }

        if (empty($error)) {
            $sql  = "INSERT INTO CONCERNS (USER_ID, SUBJECT, DESCRIPTION, EVIDENCE_PATH, STATUS, CREATED_AT) VALUES (?, ?, ?, ?, 'OPEN', GETDATE())";
            $stmt = sqlsrv_query($conn, $sql, [$userId, $subject, $description, $evidencePath]);
            if ($stmt === false) {
                $error = 'Failed to submit concern. Please try again.';
            } else {
                $success = true;
            }
        }
    }
}

$myConcernsSql  = "SELECT TOP 5 CONCERN_ID, SUBJECT, DESCRIPTION, EVIDENCE_PATH, STATUS, STAFF_REMARKS, CREATED_AT, UPDATED_AT FROM CONCERNS WHERE USER_ID = ? ORDER BY CREATED_AT DESC";
$myConcernsStmt = sqlsrv_query($conn, $myConcernsSql, [$userId]);
$myConcerns = [];
while ($row = sqlsrv_fetch_array($myConcernsStmt, SQLSRV_FETCH_ASSOC)) {
    $myConcerns[] = $row;
}

function concernStatusBadge($status) {
    $s = strtoupper(rtrim($status));
    $map = [
        'OPEN'        => ['class' => 'pending',  'label' => 'Open'],
        'IN_PROGRESS' => ['class' => 'approved', 'label' => 'In Progress'],
        'RESOLVED'    => ['class' => 'released', 'label' => 'Resolved'],
        'CLOSED'      => ['class' => 'rejected', 'label' => 'Closed'],
    ];
    $m = $map[$s] ?? ['class' => 'pending', 'label' => $s];
    return '<span class="status-badge ' . $m['class'] . '">' . $m['label'] . '</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Report a Concern — BarangayKonek</title>
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

    .concern-page-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    .concern-left {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .concern-right {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .guide-list { list-style: none; padding: 0; margin: 0; }
    .guide-list li {
      display: flex;
      align-items: flex-start;
      gap: 13px;
      padding: 14px 0;
      border-bottom: 1px solid var(--border);
    }
    .guide-list li:last-child { border-bottom: none; }
    .guide-list-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .guide-list strong {
      display: block;
      font-size: 13.5px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 3px;
    }
    .guide-list p {
      font-size: 12.5px;
      color: var(--text-muted);
      line-height: 1.5;
      margin: 0;
    }

    .my-concerns-table {
      width: 100%;
      border-collapse: collapse;
    }
    .my-concerns-table th,
    .my-concerns-table td {
      padding: 12px 14px;
      text-align: left;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }
    .my-concerns-table th {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: var(--text-muted);
      font-weight: 600;
    }
    .my-concerns-table tr:last-child td { border-bottom: none; }
    .my-concerns-table tr:hover td { background: #f8fbff; }
    .concern-subject-cell {
      max-width: 180px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .concern-remark {
      max-width: 160px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--text-muted);
    }
    .evidence-thumb {
      width: 36px;
      height: 36px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: transform 0.2s;
    }
    .evidence-thumb:hover { transform: scale(1.08); }
    .evidence-pdf-link {
      font-size: 12px;
      color: var(--blue);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .evidence-pdf-link:hover { text-decoration: underline; }

    

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    @media (max-width: 1100px) {
      .concern-page-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 800px) {
      .form-row { grid-template-columns: 1fr; }
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
      <a href="residentdashboard.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="residentrequestdocument.php"><i class="fa-solid fa-file-lines nav-icon"></i><span>Request Documents</span></a>
      <a href="residentconcern.php" class="active"><i class="fa-solid fa-circle-exclamation nav-icon"></i><span>Concerns</span></a>
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
    <div class="topbar">
      <div class="greeting-block">
        <h1>Report a <span style="color:var(--navy);">Concern</span></h1>
        <p class="subtitle">Submit barangay-related issues for review and tracking.</p>
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
              <form method="POST" action="residentconcern.php" style="margin:0;">
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

    <?php if ($success): ?>
    <div style="background:#f0fff0;border:1px solid #b2dfb2;border-left:4px solid #2e7d32;border-radius:10px;padding:14px 18px;margin-bottom:22px;display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#2e7d32;box-shadow:var(--shadow);">
      <i class="fa-solid fa-circle-check" style="margin-top:2px;font-size:16px;"></i>
      <span>Your concern has been submitted successfully. You can track its status in the table below.</span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="background:#fff0f0;border:1px solid #f5c0c0;border-left:4px solid #e03030;border-radius:10px;padding:14px 18px;margin-bottom:22px;display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#e03030;box-shadow:var(--shadow);">
      <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;font-size:16px;"></i>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="concern-page-layout">

      <div class="concern-left">

        <div class="panel">
          <div class="panel-header">
            <h2>Concern Report Form</h2>
          </div>
          <form class="concern-form" method="POST" action="residentconcern.php" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label>Concern Category <span style="color:var(--red);">*</span></label>
                <select name="category" required>
                  <option value="">Select a category</option>
                  <option>Street Light Issue</option>
                  <option>Garbage / Sanitation</option>
                  <option>Noise Complaint</option>
                  <option>Road Damage</option>
                  <option>Flooding / Drainage</option>
                  <option>Peace and Order</option>
                  <option>Health and Sanitation</option>
                  <option>Illegal Construction</option>
                  <option>Stray Animals</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label>Location / Area</label>
                <input type="text" name="location" placeholder="e.g. Alapan 1-A, near the barangay hall" />
              </div>
            </div>

            <div class="form-group">
              <label>Describe the Concern <span style="color:var(--red);">*</span></label>
              <textarea name="description" rows="5" placeholder="Provide as much detail as possible about the issue you observed..." required></textarea>
            </div>

            <div class="form-group">
                <label>Upload Evidence <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional, max 5MB)</span></label>
                <input type="file" name="evidence" accept="image/*,.pdf" style="padding:9px 12px;" />
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn--primary"><i class="fa-solid fa-paper-plane"></i> Submit Report</button>
              <button type="reset" class="btn btn--outline"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h2>My Submitted Concerns</h2>
            <span class="badge" style="background:rgba(5,22,80,0.08);color:var(--navy);"><?= count($myConcerns) ?> recent</span>
          </div>
          <?php if (empty($myConcerns)): ?>
          <div style="padding:28px;text-align:center;color:var(--text-muted);font-size:13px;">
            <i class="fa-regular fa-folder-open" style="font-size:26px;display:block;margin-bottom:10px;color:#ccc;"></i>
            You have not submitted any concerns yet.
          </div>
          <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="my-concerns-table">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Description</th>
                  <th>Evidence</th>
                  <th>Date Filed</th>
                  <th>Last Updated</th>
                  <th>Status</th>
                  <th>Staff Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($myConcerns as $c): ?>
                <tr>
                  <td class="concern-subject-cell" title="<?= htmlspecialchars(rtrim($c['SUBJECT'])) ?>"><?= htmlspecialchars(rtrim($c['SUBJECT'])) ?></td>
                  <td class="concern-remark" title="<?= htmlspecialchars(rtrim($c['DESCRIPTION'])) ?>"><?= htmlspecialchars(mb_strimwidth(rtrim($c['DESCRIPTION']), 0, 60, '...')) ?></td>
                  <td>
                    <?php if (!empty($c['EVIDENCE_PATH'])): ?>
                      <?php $ext = strtolower(pathinfo($c['EVIDENCE_PATH'], PATHINFO_EXTENSION)); ?>
                      <?php if ($ext === 'pdf'): ?>
                        <a href="<?= htmlspecialchars($c['EVIDENCE_PATH']) ?>" target="_blank" class="evidence-pdf-link">
                          <i class="fa-solid fa-file-pdf"></i> View PDF
                        </a>
                      <?php else: ?>
                        <a href="<?= htmlspecialchars($c['EVIDENCE_PATH']) ?>" target="_blank">
                          <img src="<?= htmlspecialchars($c['EVIDENCE_PATH']) ?>" alt="Evidence" class="evidence-thumb" />
                        </a>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:#ccc;font-size:12px;">None</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space:nowrap;font-size:12px;"><?= $c['CREATED_AT']->format('M d, Y') ?></td>
                  <td style="white-space:nowrap;font-size:12px;"><?= $c['UPDATED_AT'] ? $c['UPDATED_AT']->format('M d, Y') : '—' ?></td>
                  <td><?= concernStatusBadge($c['STATUS']) ?></td>
                  <td class="concern-remark" title="<?= htmlspecialchars(rtrim($c['STAFF_REMARKS'] ?? '')) ?>"><?= $c['STAFF_REMARKS'] ? htmlspecialchars(rtrim($c['STAFF_REMARKS'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

      </div>

      <div class="concern-right">

        <div class="panel">
          <div class="panel-header">
            <h2>Reporting Guidelines</h2>
          </div>
          <ul class="guide-list">
            <li>
              <div class="guide-list-icon" style="background:rgba(59,130,246,0.1);color:var(--blue);">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div>
                <strong>Be Specific</strong>
                <p>Include the exact location, street, or sitio in Alapan where the issue was observed.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:rgba(204,255,0,0.18);color:#7d8f00;">
                <i class="fa-solid fa-camera"></i>
              </div>
              <div>
                <strong>Attach Evidence</strong>
                <p>Upload a photo or document to help barangay staff verify the concern faster.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:rgba(34,197,94,0.1);color:var(--green);">
                <i class="fa-solid fa-calendar-day"></i>
              </div>
              <div>
                <strong>Provide the Date</strong>
                <p>Indicate when the issue was observed to help with record-keeping and prioritization.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:rgba(5,22,80,0.07);color:var(--navy);">
                <i class="fa-solid fa-clock-rotate-left"></i>
              </div>
              <div>
                <strong>Track Your Concern</strong>
                <p>Check the table below the form to monitor the status and any staff remarks on your submission.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:rgba(255,77,77,0.1);color:var(--red);">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div>
                <strong>Emergency?</strong>
                <p>For urgent matters, contact the barangay emergency hotline directly rather than submitting here.</p>
              </div>
            </li>
          </ul>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h2>Status Guide</h2>
          </div>
          <ul class="guide-list">
            <li>
              <div class="guide-list-icon" style="background:#fef9c3;color:#a16207;">
                <i class="fa-solid fa-circle-dot"></i>
              </div>
              <div>
                <strong>Open</strong>
                <p>Your concern has been received and is awaiting review by barangay staff.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:#dcfce7;color:#15803d;">
                <i class="fa-solid fa-spinner"></i>
              </div>
              <div>
                <strong>In Progress</strong>
                <p>Staff is currently working to address your concern.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:#dbeafe;color:#1d4ed8;">
                <i class="fa-solid fa-circle-check"></i>
              </div>
              <div>
                <strong>Resolved</strong>
                <p>The issue has been addressed. Check staff remarks for details.</p>
              </div>
            </li>
            <li>
              <div class="guide-list-icon" style="background:#fee2e2;color:#b91c1c;">
                <i class="fa-solid fa-circle-xmark"></i>
              </div>
              <div>
                <strong>Closed</strong>
                <p>The concern was closed. This may be due to inactivity or being outside barangay jurisdiction.</p>
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
    fd.append('ajax', '1');
    fetch('residentconcern.php', { method: 'POST', body: fd }).catch(() => {});
  }
  document.getElementById('notifDropdown').classList.remove('open');
  if ((typeKey === 'LIKE' || typeKey === 'COMMENT') && refId > 0) {
    window.location.href = 'residentcommunity.php#post-' + refId;
  } else if (typeKey === 'ANNOUNCEMENT') {
    window.location.href = 'residentdashboard.php';
  } else if (typeKey === 'REQUEST') {
    window.location.href = 'residentrequest.php';
  }
}
</script>
</body>
</html>