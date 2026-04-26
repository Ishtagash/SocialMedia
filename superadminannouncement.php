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

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $now    = date('Y-m-d H:i:s');

    if ($action === 'post_announcement') {
        $title     = trim($_POST['title']      ?? '');
        $body      = trim($_POST['body']       ?? '');
        $category  = trim($_POST['category']   ?? 'General');
        $expiresAt = trim($_POST['expires_at'] ?? '');

        if ($title && $body) {
            $expParam = $expiresAt ? $expiresAt . ' 23:59:59' : null;

            $ins = sqlsrv_query($conn,
                "INSERT INTO ANNOUNCEMENTS (TITLE, BODY, CATEGORY, IS_ACTIVE, CREATED_BY, CREATED_AT, EXPIRES_AT)
                 VALUES (?, ?, ?, 1, ?, ?, ?)",
                [$title, $body, $category, $userId, $now, $expParam]
            );

            if ($ins) {
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Create Announcement', ?, ?)",
                    [$userId, "Posted: $title", $now]
                );
                header("Location: superadminannouncement.php?msg=" . urlencode('Announcement posted successfully.'));
                exit();
            } else {
                $errors      = sqlsrv_errors();
                $errMsg      = $errors ? $errors[0]['message'] : 'Unknown error';
                $message     = 'Failed to post announcement. DB Error: ' . htmlspecialchars($errMsg);
                $messageType = 'error';
            }
        } else {
            $message     = 'Title and body are required.';
            $messageType = 'error';
        }
    }

    if ($action === 'delete_announcement') {
        $annId = (int)($_POST['ann_id'] ?? 0);
        if ($annId) {
            sqlsrv_query($conn, "UPDATE ANNOUNCEMENTS SET IS_ACTIVE = 0 WHERE ANNOUNCEMENT_ID = ?", [$annId]);
            sqlsrv_query($conn,
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Delete Announcement', ?, ?)",
                [$userId, "Deleted ANNOUNCEMENT_ID $annId", $now]
            );
            header("Location: superadminannouncement.php?msg=" . urlencode('Announcement removed.'));
            exit();
        }
    }

    if ($action === 'edit_announcement') {
        $annId     = (int)($_POST['ann_id']     ?? 0);
        $title     = trim($_POST['title']        ?? '');
        $body      = trim($_POST['body']         ?? '');
        $category  = trim($_POST['category']     ?? 'General');
        $expiresAt = trim($_POST['expires_at']   ?? '');
        if ($annId && $title && $body) {
            $expParam = $expiresAt ? $expiresAt . ' 23:59:59' : null;
            sqlsrv_query($conn,
                "UPDATE ANNOUNCEMENTS SET TITLE = ?, BODY = ?, CATEGORY = ?, EXPIRES_AT = ? WHERE ANNOUNCEMENT_ID = ?",
                [$title, $body, $category, $expParam, $annId]
            );
            sqlsrv_query($conn,
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Update Announcement', ?, ?)",
                [$userId, "Edited ANNOUNCEMENT_ID $annId", $now]
            );
            header("Location: superadminannouncement.php?msg=" . urlencode('Announcement updated.'));
            exit();
        }
    }
}

if (isset($_GET['msg'])) { $message = htmlspecialchars($_GET['msg']); $messageType = 'success'; }

$search    = trim($_GET['search']   ?? '');
$catFilter = trim($_GET['category'] ?? '');
$editId    = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editAnn   = null;

$params = [];
$sql = "SELECT A.ANNOUNCEMENT_ID, A.TITLE, A.BODY, A.CATEGORY, A.IS_ACTIVE,
               A.CREATED_AT, A.EXPIRES_AT,
               R.FIRST_NAME, R.LAST_NAME, U.USERNAME
        FROM ANNOUNCEMENTS A
        INNER JOIN USERS U ON U.USER_ID = A.CREATED_BY
        LEFT JOIN REGISTRATION R ON R.USER_ID = A.CREATED_BY
        WHERE A.IS_ACTIVE = 1
        AND (A.EXPIRES_AT IS NULL OR A.EXPIRES_AT >= GETDATE())";

if ($search) {
    $like      = '%' . $search . '%';
    $sql      .= " AND (A.TITLE LIKE ? OR A.BODY LIKE ?)";
    $params[]  = $like;
    $params[]  = $like;
}
if ($catFilter) {
    $sql     .= " AND A.CATEGORY = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY A.CREATED_AT DESC";

$annResult     = sqlsrv_query($conn, $sql, $params ?: []);
$announcements = [];
if ($annResult) {
    while ($row = sqlsrv_fetch_array($annResult, SQLSRV_FETCH_ASSOC)) {
        $announcements[] = $row;
    }
}

if ($editId) {
    $er = sqlsrv_query($conn,
        "SELECT ANNOUNCEMENT_ID, TITLE, BODY, CATEGORY, EXPIRES_AT
         FROM ANNOUNCEMENTS WHERE ANNOUNCEMENT_ID = ? AND IS_ACTIVE = 1",
        [$editId]
    );
    if ($er) { $editAnn = sqlsrv_fetch_array($er, SQLSRV_FETCH_ASSOC); }
}

$totalPosted = 0;
$r = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1");
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $totalPosted = (int)$row['CNT']; }

$thisMonth = 0;
$r = sqlsrv_query($conn,
    "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS
     WHERE IS_ACTIVE = 1 AND MONTH(CREATED_AT) = MONTH(GETDATE()) AND YEAR(CREATED_AT) = YEAR(GETDATE())"
);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $thisMonth = (int)$row['CNT']; }

$expiringSoon = 0;
$r = sqlsrv_query($conn,
    "SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS
     WHERE IS_ACTIVE = 1 AND EXPIRES_AT IS NOT NULL
     AND EXPIRES_AT BETWEEN GETDATE() AND DATEADD(day, 7, GETDATE())"
);
if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); $expiringSoon = (int)$row['CNT']; }

$recentAnn = [];
$rr = sqlsrv_query($conn,
    "SELECT TOP 5 A.TITLE, A.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, U.USERNAME
     FROM ANNOUNCEMENTS A
     INNER JOIN USERS U ON U.USER_ID = A.CREATED_BY
     LEFT JOIN REGISTRATION R ON R.USER_ID = A.CREATED_BY
     WHERE A.IS_ACTIVE = 1 ORDER BY A.CREATED_AT DESC"
);
if ($rr) { while ($row = sqlsrv_fetch_array($rr, SQLSRV_FETCH_ASSOC)) { $recentAnn[] = $row; } }

$catBreakdown = [];
$cb = sqlsrv_query($conn,
    "SELECT CATEGORY, COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1 GROUP BY CATEGORY ORDER BY CNT DESC"
);
if ($cb) { while ($row = sqlsrv_fetch_array($cb, SQLSRV_FETCH_ASSOC)) { $catBreakdown[] = $row; } }
$maxCat = $catBreakdown ? max(array_column($catBreakdown, 'CNT')) : 1;

$categories = ['General', 'Event', 'Health', 'Reminder', 'Alert'];

function getCategoryClass($cat) {
    $map = ['General' => 'general', 'Event' => 'event', 'Health' => 'health',
            'Reminder' => 'reminder', 'Alert' => 'alert'];
    return $map[$cat] ?? 'general';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Announcements — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="superadmin.css" />
  <style>
    .announcement-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start}
    .announcement-left{display:flex;flex-direction:column;gap:16px;min-width:0}
    .announcement-right{display:flex;flex-direction:column;gap:16px}
    .announcement-topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px}
    .announcement-topbar-left h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 3px}
    .announcement-topbar-left p{font-size:13px;color:var(--text-muted);margin:0}
    .announcement-compose{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .announcement-compose-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02)}
    .announcement-compose-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .announcement-compose-body{padding:20px;display:flex;flex-direction:column;gap:14px}
    .announcement-field{display:flex;flex-direction:column;gap:6px}
    .announcement-field label{font-size:12px;font-weight:700;color:var(--navy)}
    .announcement-input{width:100%;height:42px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .2s}
    .announcement-input:focus{border-color:var(--navy)}
    .announcement-textarea{width:100%;min-height:110px;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:14px;outline:none;resize:vertical;box-sizing:border-box;transition:border-color .2s;line-height:1.6}
    .announcement-textarea:focus{border-color:var(--navy)}
    .announcement-field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .announcement-select{width:100%;height:42px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:14px;outline:none;box-sizing:border-box}
    .announcement-compose-foot{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--border);background:rgba(5,22,80,.02)}
    .announcement-filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .announcement-search-box{flex:1;min-width:200px;height:40px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .announcement-search-box i{color:var(--text-muted);font-size:13px}
    .announcement-search-box input{flex:1;border:none;outline:none;background:transparent;font-family:inherit;font-size:13px;color:var(--text)}
    .announcement-filter-select{height:40px;min-width:130px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none}
    .announcement-list{display:flex;flex-direction:column;gap:1px;background:var(--border);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .announcement-card{background:var(--surface);padding:18px 20px;display:flex;flex-direction:column;gap:10px;transition:background .15s}
    .announcement-card:hover{background:rgba(5,22,80,.02)}
    .announcement-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
    .announcement-card-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .announcement-category-tag{display:inline-flex;align-items:center;height:22px;padding:0 9px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;background:rgba(5,22,80,.07);color:var(--navy)}
    .announcement-category-tag.health{background:rgba(34,197,94,.1);color:#166534}
    .announcement-category-tag.alert{background:rgba(239,68,68,.1);color:#991b1b}
    .announcement-category-tag.event{background:rgba(59,130,246,.1);color:#1e40af}
    .announcement-category-tag.reminder{background:rgba(245,158,11,.1);color:#92400e}
    .announcement-category-tag.general{background:rgba(5,22,80,.07);color:var(--navy)}
    .expiry-tag{display:inline-flex;align-items:center;gap:4px;height:22px;padding:0 9px;border-radius:6px;font-size:11px;font-weight:600;background:rgba(245,158,11,.1);color:#92400e}
    .announcement-card-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
    .announcement-icon-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text-muted);font-size:13px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;font-family:inherit;text-decoration:none}
    .announcement-icon-btn.delete-btn:hover{background:rgba(239,68,68,.08);color:#dc2626;border-color:rgba(239,68,68,.2)}
    .announcement-icon-btn.edit-btn:hover{background:rgba(204,255,0,.1);border-color:var(--navy);color:var(--navy)}
    .announcement-card-title{font-size:15px;font-weight:700;color:var(--navy);line-height:1.3;margin:0}
    .announcement-card-body{font-size:13px;color:var(--text);line-height:1.65}
    .announcement-card-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:6px;border-top:1px solid var(--border)}
    .announcement-card-author{font-size:12px;color:var(--text-muted);font-weight:600}
    .announcement-card-date{font-size:12px;color:var(--text-muted)}
    .announcement-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;box-shadow:var(--shadow)}
    .announcement-stat-label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
    .announcement-stat-number{display:block;font-size:34px;font-weight:700;color:var(--navy);line-height:1;margin-bottom:6px}
    .announcement-stat-note{font-size:12px;color:var(--text-muted);line-height:1.4}
    .announcement-recent-panel,.announcement-breakdown-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .announcement-recent-head,.announcement-breakdown-head{padding:13px 18px;border-bottom:1px solid var(--border)}
    .announcement-recent-head h4,.announcement-breakdown-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .announcement-recent-item{display:flex;flex-direction:column;gap:3px;padding:13px 18px;border-bottom:1px solid var(--border);transition:background .15s}
    .announcement-recent-item:last-child{border-bottom:none}
    .announcement-recent-item:hover{background:rgba(5,22,80,.02)}
    .announcement-recent-title{font-size:13px;font-weight:700;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .announcement-recent-meta{font-size:11px;color:var(--text-muted)}
    .announcement-breakdown-body{padding:14px 18px;display:flex;flex-direction:column;gap:10px}
    .announcement-breakdown-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .announcement-breakdown-name{font-size:13px;font-weight:600;color:var(--text);min-width:72px}
    .announcement-breakdown-count{font-size:13px;font-weight:700;color:var(--navy)}
    .announcement-breakdown-bar-wrap{flex:1;height:5px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden}
    .announcement-breakdown-bar{height:100%;background:var(--navy);border-radius:999px;opacity:.3}
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .msg-banner{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px}
    .msg-success{background:rgba(34,197,94,.12);color:#16a34a;border:1px solid rgba(34,197,94,.25)}
    .msg-error{background:rgba(255,77,77,.1);color:var(--red);border:1px solid rgba(255,77,77,.25)}
    @media(max-width:1100px){.announcement-layout{grid-template-columns:1fr}.announcement-right{display:grid;grid-template-columns:repeat(2,1fr)}}
    @media(max-width:680px){.announcement-right{grid-template-columns:1fr}.announcement-topbar{flex-direction:column;align-items:flex-start}.announcement-field-row{grid-template-columns:1fr}}
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
      <a href="superadminannouncement.php" class="active">Announcements</a>
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

  <main class="superadmin-content">
    <?php if ($message): ?>
    <div class="msg-banner msg-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="announcement-topbar">
      <div class="announcement-topbar-left">
        <h2>Announcements</h2>
        <p>Post and manage community announcements</p>
      </div>
      <button class="superadmin-primary-btn" onclick="openCompose()">
        <i class="fa-solid fa-plus"></i> New Announcement
      </button>
    </div>

    <div class="announcement-layout">
      <div class="announcement-left">

        <div class="announcement-compose" id="composePanel" style="display:none;">
          <div class="announcement-compose-head">
            <h4><?= $editAnn ? 'Edit Announcement' : 'New Announcement' ?></h4>
            <button type="button" class="announcement-icon-btn" onclick="closeCompose()">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="<?= $editAnn ? 'edit_announcement' : 'post_announcement' ?>">
            <?php if ($editAnn): ?>
            <input type="hidden" name="ann_id" value="<?= (int)$editAnn['ANNOUNCEMENT_ID'] ?>">
            <?php endif; ?>
            <div class="announcement-compose-body">
              <div class="announcement-field">
                <label>Title *</label>
                <input class="announcement-input" type="text" name="title" required
                  placeholder="Announcement title"
                  value="<?= $editAnn ? htmlspecialchars(rtrim($editAnn['TITLE'])) : '' ?>">
              </div>
              <div class="announcement-field">
                <label>Body *</label>
                <textarea class="announcement-textarea" name="body" required
                  placeholder="Write the announcement content here..."><?= $editAnn ? htmlspecialchars(rtrim($editAnn['BODY'])) : '' ?></textarea>
              </div>
              <div class="announcement-field-row">
                <div class="announcement-field">
                  <label>Category</label>
                  <select class="announcement-select" name="category">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>"
                      <?= ($editAnn && rtrim($editAnn['CATEGORY']) === $cat) ? 'selected' : '' ?>>
                      <?= $cat ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="announcement-field">
                  <label>
                    Expiry Date
                    <span style="font-weight:400;color:var(--text-muted);"> (optional)</span>
                  </label>
                  <input class="announcement-input" type="date" name="expires_at"
                    min="<?= date('Y-m-d') ?>"
                    value="<?php
                      if ($editAnn && $editAnn['EXPIRES_AT']) {
                          $expObj = $editAnn['EXPIRES_AT'] instanceof DateTime
                            ? $editAnn['EXPIRES_AT']
                            : new DateTime($editAnn['EXPIRES_AT']);
                          echo $expObj->format('Y-m-d');
                      }
                    ?>">
                </div>
              </div>
            </div>
            <div class="announcement-compose-foot">
              <button type="button" class="superadmin-outline-btn" onclick="closeCompose()">Cancel</button>
              <button type="submit" class="superadmin-primary-btn">
                <i class="fa-solid fa-<?= $editAnn ? 'floppy-disk' : 'paper-plane' ?>"></i>
                <?= $editAnn ? 'Save Changes' : 'Post Announcement' ?>
              </button>
            </div>
          </form>
        </div>

        <form method="GET" id="filterForm">
          <div class="announcement-filter-bar">
            <div class="announcement-search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" name="search" placeholder="Search announcements..."
                value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="announcement-filter-select" name="category"
              onchange="document.getElementById('filterForm').submit()">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= $catFilter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="superadmin-primary-btn" style="min-height:40px;padding:0 16px;">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <?php if ($search || $catFilter): ?>
            <a href="superadminannouncement.php" class="superadmin-outline-btn"
               style="min-height:40px;padding:0 14px;display:inline-flex;align-items:center;">Clear</a>
            <?php endif; ?>
          </div>
        </form>

        <div class="announcement-list">
          <?php if (empty($announcements)): ?>
          <div class="announcement-card">
            <p style="color:var(--text-muted);font-size:14px;text-align:center;padding:12px 0;">
              No announcements found.
            </p>
          </div>
          <?php else: ?>
          <?php foreach ($announcements as $ann):
            $annTitle   = htmlspecialchars(rtrim($ann['TITLE']));
            $annBody    = htmlspecialchars(rtrim($ann['BODY']));
            $annCat     = rtrim($ann['CATEGORY'] ?? 'General');
            $annCatCls  = getCategoryClass($annCat);
            $annId      = (int)$ann['ANNOUNCEMENT_ID'];
            $authorName = trim(rtrim($ann['FIRST_NAME'] ?? '') . ' ' . rtrim($ann['LAST_NAME'] ?? ''))
                          ?: rtrim($ann['USERNAME']);
            $annDate    = $ann['CREATED_AT'] instanceof DateTime
              ? $ann['CREATED_AT']->format('F j, Y')
              : date('F j, Y', strtotime($ann['CREATED_AT']));
            $expiresAt  = $ann['EXPIRES_AT'];
            $expiryStr  = '';
            if ($expiresAt) {
                $expiryObj = $expiresAt instanceof DateTime ? $expiresAt : new DateTime($expiresAt);
                $expiryStr = 'Expires ' . $expiryObj->format('M j, Y');
            }
          ?>
          <div class="announcement-card">
            <div class="announcement-card-top">
              <div class="announcement-card-meta">
                <span class="announcement-category-tag <?= $annCatCls ?>"><?= htmlspecialchars($annCat) ?></span>
                <?php if ($expiryStr): ?>
                <span class="expiry-tag"><i class="fa-regular fa-clock"></i> <?= $expiryStr ?></span>
                <?php endif; ?>
              </div>
              <div class="announcement-card-actions">
                <a href="superadminannouncement.php?edit=<?= $annId ?>"
                   class="announcement-icon-btn edit-btn" title="Edit">
                  <i class="fa-solid fa-pen"></i>
                </a>
                <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete this announcement?')">
                  <input type="hidden" name="action" value="delete_announcement">
                  <input type="hidden" name="ann_id" value="<?= $annId ?>">
                  <button type="submit" class="announcement-icon-btn delete-btn" title="Delete">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </div>
            <h3 class="announcement-card-title"><?= $annTitle ?></h3>
            <p class="announcement-card-body"><?= $annBody ?></p>
            <div class="announcement-card-foot">
              <span class="announcement-card-author">Posted by <?= htmlspecialchars($authorName) ?></span>
              <span class="announcement-card-date"><?= $annDate ?> · All Residents</span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>

      <aside class="announcement-right">
        <div class="announcement-stat-card">
          <span class="announcement-stat-label">Total Active</span>
          <strong class="announcement-stat-number"><?= $totalPosted ?></strong>
          <p class="announcement-stat-note">All active announcements</p>
        </div>
        <div class="announcement-stat-card">
          <span class="announcement-stat-label">This Month</span>
          <strong class="announcement-stat-number"><?= $thisMonth ?></strong>
          <p class="announcement-stat-note">Posted in <?= date('F Y') ?></p>
        </div>
        <div class="announcement-stat-card">
          <span class="announcement-stat-label">Expiring Soon</span>
          <strong class="announcement-stat-number"><?= $expiringSoon ?></strong>
          <p class="announcement-stat-note">Posts expiring within 7 days</p>
        </div>

        <div class="announcement-recent-panel">
          <div class="announcement-recent-head"><h4>Recently Posted</h4></div>
          <?php if (empty($recentAnn)): ?>
          <div class="announcement-recent-item">
            <span class="announcement-recent-title">No recent posts.</span>
          </div>
          <?php else: ?>
          <?php foreach ($recentAnn as $ra):
            $raAuthor = trim(rtrim($ra['FIRST_NAME'] ?? '') . ' ' . rtrim($ra['LAST_NAME'] ?? ''))
                        ?: rtrim($ra['USERNAME']);
            $raDate   = $ra['CREATED_AT'] instanceof DateTime
              ? $ra['CREATED_AT']->format('M d')
              : date('M d', strtotime($ra['CREATED_AT']));
          ?>
          <div class="announcement-recent-item">
            <span class="announcement-recent-title"><?= htmlspecialchars(rtrim($ra['TITLE'])) ?></span>
            <span class="announcement-recent-meta"><?= htmlspecialchars($raAuthor) ?> · <?= $raDate ?></span>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="announcement-breakdown-panel">
          <div class="announcement-breakdown-head"><h4>By Category</h4></div>
          <div class="announcement-breakdown-body">
            <?php if (empty($catBreakdown)): ?>
            <p style="font-size:13px;color:var(--text-muted);">No data yet.</p>
            <?php else: ?>
            <?php foreach ($catBreakdown as $cb):
              $bw = $maxCat > 0 ? round(($cb['CNT'] / $maxCat) * 100) : 0;
            ?>
            <div class="announcement-breakdown-row">
              <span class="announcement-breakdown-name"><?= htmlspecialchars(rtrim($cb['CATEGORY'])) ?></span>
              <div class="announcement-breakdown-bar-wrap">
                <div class="announcement-breakdown-bar" style="width:<?= $bw ?>%;"></div>
              </div>
              <span class="announcement-breakdown-count"><?= $cb['CNT'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </main>
</div>

<script>
function openCompose() {
  var p = document.getElementById('composePanel');
  p.style.display = 'block';
  p.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function closeCompose() {
  document.getElementById('composePanel').style.display = 'none';
}
<?php if ($editAnn): ?>
document.addEventListener('DOMContentLoaded', function() { openCompose(); });
<?php endif; ?>
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>
</body>
</html>