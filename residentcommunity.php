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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'post') {
        $body     = trim($_POST['body'] ?? '');
        $hasFiles = !empty($_FILES['post_images']['name'][0]);
        if (!empty($body) || $hasFiles) {
            $insertPost = sqlsrv_query($conn,
                "INSERT INTO POSTS (USER_ID, BODY, CREATED_AT) VALUES (?, ?, GETDATE())",
                [$userId, $body]
            );
            if ($insertPost !== false) {
                $newPostRow = sqlsrv_fetch_array(
                    sqlsrv_query($conn, "SELECT TOP 1 POST_ID FROM POSTS WHERE USER_ID = ? ORDER BY CREATED_AT DESC", [$userId]),
                    SQLSRV_FETCH_ASSOC
                );
                $newPostId = $newPostRow ? (int)$newPostRow['POST_ID'] : 0;
                if ($newPostId && $hasFiles) {
                    $uploadDir = 'uploads/posts/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    for ($i = 0; $i < count($_FILES['post_images']['name']); $i++) {
                        if ($_FILES['post_images']['error'][$i] === 0) {
                            $ext      = pathinfo($_FILES['post_images']['name'][$i], PATHINFO_EXTENSION);
                            $savePath = $uploadDir . uniqid('post_') . '.' . $ext;
                            if (move_uploaded_file($_FILES['post_images']['tmp_name'][$i], $savePath)) {
                                sqlsrv_query($conn,
                                    "INSERT INTO POST_IMAGES (POST_ID, IMAGE_PATH, CREATED_AT) VALUES (?, ?, GETDATE())",
                                    [$newPostId, $savePath]
                                );
                            }
                        }
                    }
                }
            }
        }
        header("Location: residentcommunity.php");
        exit();
    }

    if ($action === 'read_notif' && isset($_POST['notif_id'])) {
        $notifId = (int)$_POST['notif_id'];
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?", [$notifId, $userId]);
        $refId  = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;
        $anchor = $refId > 0 ? '#post-' . $refId : '';
        header("Location: residentcommunity.php" . $anchor);
        exit();
    }

    if ($action === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: residentcommunity.php");
        exit();
    }
}

$unreadRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM NOTIFICATIONS WHERE USER_ID = ? AND IS_READ = 0", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$unreadCount = $unreadRow ? (int)$unreadRow['CNT'] : 0;

$notifStmt = sqlsrv_query($conn,
    "SELECT TOP 15 N.NOTIFICATION_ID, N.MESSAGE, N.TYPE, N.IS_READ, N.CREATED_AT, N.REFERENCE_ID
     FROM NOTIFICATIONS N WHERE N.USER_ID = ? ORDER BY N.CREATED_AT DESC",
    [$userId]
);
$notifications = [];
while ($row = sqlsrv_fetch_array($notifStmt, SQLSRV_FETCH_ASSOC)) {
    $refId = $row['REFERENCE_ID'] ? (int)$row['REFERENCE_ID'] : 0;
    $postPreview = null;
    if ($refId > 0 && in_array(rtrim($row['TYPE']), ['LIKE', 'COMMENT'])) {
        $prevRow = sqlsrv_fetch_array(
            sqlsrv_query($conn,
                "SELECT P.BODY,
                    (SELECT COUNT(*) FROM LIKES L WHERE L.POST_ID = P.POST_ID) AS LIKE_COUNT,
                    (SELECT COUNT(*) FROM COMMENTS C WHERE C.POST_ID = P.POST_ID) AS COMMENT_COUNT,
                    (SELECT TOP 1 IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = P.POST_ID ORDER BY CREATED_AT ASC) AS THUMB
                 FROM POSTS P WHERE P.POST_ID = ?",
                [$refId]
            ),
            SQLSRV_FETCH_ASSOC
        );
        if ($prevRow) $postPreview = $prevRow;
    }
    $row['POST_PREVIEW'] = $postPreview;
    $notifications[] = $row;
}

$postsSql = "SELECT
    P.POST_ID, P.USER_ID AS POSTER_ID, P.BODY, P.CREATED_AT,
    R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE,
    (SELECT COUNT(*) FROM LIKES L WHERE L.POST_ID = P.POST_ID) AS REACT_COUNT,
    (SELECT COUNT(*) FROM COMMENTS C WHERE C.POST_ID = P.POST_ID) AS COMMENT_COUNT,
    (SELECT TOP 1 REACTION_TYPE FROM LIKES WHERE POST_ID = P.POST_ID AND USER_ID = ?) AS MY_REACTION
FROM POSTS P
JOIN REGISTRATION R ON P.USER_ID = R.USER_ID
ORDER BY P.CREATED_AT DESC";
$postsStmt = sqlsrv_query($conn, $postsSql, [$userId]);
$posts = [];
while ($row = sqlsrv_fetch_array($postsStmt, SQLSRV_FETCH_ASSOC)) {
    $posts[] = $row;
}

$annsSql  = "SELECT TOP 5 TITLE, BODY, CREATED_AT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1 ORDER BY CREATED_AT DESC";
$annsStmt = sqlsrv_query($conn, $annsSql);
$announcements = [];
while ($row = sqlsrv_fetch_array($annsStmt, SQLSRV_FETCH_ASSOC)) {
    $announcements[] = $row;
}

$imageExts = ['jpg','jpeg','png','gif','webp','bmp'];

$reactionMeta = [
    'LIKE'  => ['emoji' => '👍', 'label' => 'Like',  'color' => '#1877f2'],
    'LOVE'  => ['emoji' => '❤️',  'label' => 'Love',  'color' => '#f33e58'],
    'HAHA'  => ['emoji' => '😂', 'label' => 'Haha',  'color' => '#f7b125'],
    'WOW'   => ['emoji' => '😮', 'label' => 'Wow',   'color' => '#f7b125'],
    'SAD'   => ['emoji' => '😢', 'label' => 'Sad',   'color' => '#f7b125'],
    'ANGRY' => ['emoji' => '😡', 'label' => 'Angry', 'color' => '#e9710f'],
];

function resolveAvatar($profilePic, $gender) {
    if (!empty($profilePic)) return htmlspecialchars($profilePic);
    $g = strtolower(trim($gender ?? ''));
    if ($g === 'male')   return 'default/male.png';
    if ($g === 'female') return 'default/female.png';
    return 'default/neutral.png';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Community Feed — BarangayKonek</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="resident.css" />
  <style>
    .sidebar-divider { height:1px; background:rgba(255,255,255,0.08); margin:6px 14px; }

    .community-main-header { position:sticky; top:16px; z-index:50; }
    .community-widgets-column { position:sticky; top:84px; max-height:calc(100vh - 100px); overflow-y:auto; scrollbar-width:none; }
    .community-widgets-column::-webkit-scrollbar { display:none; }

    .bell-wrap-pos { position:relative; }
    .notif-dropdown {
      position:absolute; top:calc(100% + 10px); right:0; width:340px;
      background:#fff; border:1px solid rgba(5,22,80,0.12); border-radius:10px;
      box-shadow:0 8px 30px rgba(5,22,80,0.16); z-index:999; display:none; overflow:hidden;
      max-height:480px; overflow-y:auto;
    }
    .notif-dropdown.open { display:block; }
    .notif-dropdown-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px 10px; border-bottom:1px solid rgba(5,22,80,0.08);
      position:sticky; top:0; background:#fff; z-index:1;
    }
    .notif-dropdown-header h4 { font-size:14px; font-weight:700; color:#051650; }
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
    .notif-post-preview { margin:0 14px 10px 58px; background:#f5f7ff; border:1px solid rgba(5,22,80,0.1); border-radius:8px; padding:10px 12px; display:flex; gap:10px; align-items:flex-start; }
    .notif-post-preview-thumb { width:52px; height:52px; border-radius:6px; object-fit:cover; flex-shrink:0; }
    .notif-post-preview-info { flex:1; min-width:0; }
    .notif-post-preview-body { font-size:12px; color:#444; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:5px; }
    .notif-post-preview-stats { display:flex; gap:12px; font-size:11px; color:#888; }
    .notif-post-preview-stats span { display:flex; align-items:center; gap:4px; }
    .notif-empty { padding:28px; text-align:center; font-size:13px; color:#aaa; }

    .media-preview-wrap { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .media-preview-item { position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; border:1px solid #ddd; }
    .media-preview-item img { width:100%; height:100%; object-fit:cover; }
    .media-preview-item .file-icon { width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f0f2fa; font-size:24px; color:#051650; }
    .media-preview-item .remove-btn { position:absolute; top:2px; right:2px; width:18px; height:18px; background:rgba(0,0,0,0.55); color:#fff; border:none; border-radius:50%; font-size:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; }

    .post-media-grid { display:grid; gap:4px; margin:10px 0; border-radius:8px; overflow:hidden; }
    .post-media-grid.count-1 { grid-template-columns:1fr; }
    .post-media-grid.count-2 { grid-template-columns:1fr 1fr; }
    .post-media-grid.count-3 { grid-template-columns:1fr 1fr; }
    .post-media-grid.count-3 .media-cell:first-child { grid-column:1 / -1; }
    .post-media-grid.count-4 { grid-template-columns:1fr 1fr; }
    .media-cell { position:relative; background:#f0f2fa; }
    .media-cell img { width:100%; height:200px; object-fit:cover; display:block; }
    .media-cell.single img { height:320px; }
    .media-cell .file-link { display:flex; align-items:center; gap:10px; padding:14px 16px; text-decoration:none; color:#051650; font-size:13px; font-weight:600; }
    .media-cell .more-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; font-weight:700; }

    .reaction-bar {
      display:flex; align-items:center; gap:14px; flex-wrap:wrap;
      font-size:12.5px; color:var(--text-muted);
      padding-bottom:10px; border-bottom:1px solid var(--border);
    }
    .reaction-bar-item { display:inline-flex; align-items:center; gap:4px; }
    .reaction-bar-item .r-emoji { font-size:15px; }

    .reaction-btn-wrap { position:relative; display:inline-flex; }
    .reaction-picker {
      position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%) translateY(6px);
      background:var(--surface); border:1px solid var(--border); border-radius:999px;
      padding:6px 10px; display:flex; gap:2px;
      box-shadow:0 8px 24px rgba(5,22,80,0.18);
      opacity:0; pointer-events:none;
      transition:opacity 0.15s, transform 0.15s;
      z-index:10; white-space:nowrap;
    }
    .reaction-picker.open { opacity:1; pointer-events:all; transform:translateX(-50%) translateY(0); }
    .reaction-option { display:flex; flex-direction:column; align-items:center; gap:2px; cursor:pointer; padding:4px 6px; border-radius:8px; transition:transform 0.15s, background 0.15s; border:none; background:none; font-family:inherit; }
    .reaction-option:hover { transform:scale(1.35) translateY(-4px); background:rgba(5,22,80,0.04); }
    .reaction-option .r-emoji { font-size:22px; line-height:1; }
    .reaction-option .r-label { font-size:9px; font-weight:700; color:var(--text-muted); }

    .post-highlight { animation:highlightPost 2.5s ease; }
    @keyframes highlightPost { 0%{box-shadow:0 0 0 3px #ccff00;} 100%{box-shadow:none;} }

    .search-hidden { display:none !important; }

    .community-action { background:none; border:none; cursor:pointer; font-family:inherit; font-size:13px; display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:6px; color:var(--text-muted); text-decoration:none; transition:background 0.15s; font-weight:600; }
    .community-action:hover { background:rgba(5,22,80,0.06); color:var(--navy); }
    .community-action.reacted { font-weight:700; }

    .comment-section { border-top:1px solid var(--border); margin-top:8px; display:none; }
    .comment-section.open { display:block; }
    .comment-avatar { width:32px; height:32px; border-radius:50%; overflow:hidden; flex-shrink:0; border:2px solid #ccff00; }
    .comment-avatar img { width:100%; height:100%; object-fit:cover; }

    .poster-link { color:var(--text); text-decoration:none; font-weight:700; font-size:15px; }
    .poster-link:hover { color:var(--navy); text-decoration:underline; }

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid #ccff00; box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:#051650; color:#ccff00; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:#051650; margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:#051650; color:#ccff00; border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:#051650; border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }
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
  <aside class="sidebar resident-community-sidebar">
    <div class="sidebar-brand"><h2>BarangayKonek</h2><span>Resident</span></div>
    <div class="profile profile--compact">
      <div class="avatar-ring"><img src="<?= $profilePicture ?>" alt="Resident Photo" /></div>
      <div class="profile-meta">
        <h3><?= $fullName ?></h3>
        <p>City of Imus, Alapan 1-A</p>
        <span class="portal-badge">Resident Portal</span>
      </div>
    </div>
    <nav class="menu menu--community">
      <a href="residentdashboard.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="residentrequestdocument.php"><i class="fa-solid fa-file-lines nav-icon"></i><span>Request Documents</span></a>
      <a href="residentconcern.php"><i class="fa-solid fa-circle-exclamation nav-icon"></i><span>Concerns</span></a>
      <a href="residentcommunity.php" class="active"><i class="fa-solid fa-users nav-icon"></i><span>Community</span></a>
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

  <main class="content community-page-content">
    <div class="content-inner community-page-shell">

      <header class="community-main-header">
        <div class="community-main-search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="feedSearch" placeholder="Search posts, users, or announcements..." />
        </div>
        <div class="community-main-actions">
          <div class="bell-wrap-pos">
            <button type="button" class="community-header-icon community-bell-link" id="bellBtn" onclick="toggleNotif()">
              <i class="fa-regular fa-bell community-bell-icon"></i>
              <?php if ($unreadCount > 0): ?>
              <span class="community-bell-count"><?= $unreadCount ?></span>
              <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
              <div class="notif-dropdown-header">
                <h4>Notifications <?php if ($unreadCount > 0): ?><span style="font-size:11px;color:#888;font-weight:400;">(<?= $unreadCount ?> unread)</span><?php endif; ?></h4>
                <?php if ($unreadCount > 0): ?>
                <form method="POST" action="residentcommunity.php" style="margin:0;">
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
                $iconMap  = ['LIKE'=>'fa-thumbs-up','COMMENT'=>'fa-comment','ANNOUNCEMENT'=>'fa-bullhorn'];
                $icon     = $iconMap[$typeKey] ?? 'fa-bell';
                $timeAgo  = $notif['CREATED_AT']->format('M d, g:i A');
                $preview  = $notif['POST_PREVIEW'];
              ?>
              <form method="POST" action="residentcommunity.php" style="display:block;margin:0;padding:0;">
                <input type="hidden" name="action" value="read_notif">
                <input type="hidden" name="notif_id" value="<?= $notifId ?>">
                <input type="hidden" name="ref_id" value="<?= $refId ?>">
                <button type="submit" class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                  <div class="notif-item-top">
                    <div class="notif-item-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                    <div class="notif-item-text">
                      <?= htmlspecialchars(rtrim($notif['MESSAGE'])) ?>
                      <div class="notif-item-time"><?= $timeAgo ?></div>
                    </div>
                    <?php if ($isUnread): ?><div class="notif-unread-dot"></div><?php endif; ?>
                  </div>
                  <?php if ($preview): ?>
                  <div class="notif-post-preview">
                    <?php if ($preview['THUMB']): ?>
                    <img src="<?= htmlspecialchars($preview['THUMB']) ?>" class="notif-post-preview-thumb" alt="Post" />
                    <?php endif; ?>
                    <div class="notif-post-preview-info">
                      <div class="notif-post-preview-body"><?= $preview['BODY'] ? htmlspecialchars(mb_strimwidth(rtrim($preview['BODY']), 0, 80, '...')) : '(No text)' ?></div>
                      <div class="notif-post-preview-stats">
                        <span><i class="fa-solid fa-thumbs-up"></i> <?= (int)$preview['LIKE_COUNT'] ?></span>
                        <span><i class="fa-solid fa-comment"></i> <?= (int)$preview['COMMENT_COUNT'] ?></span>
                        <?php if ($preview['THUMB']): ?><span><i class="fa-solid fa-image"></i> Photo</span><?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>
                </button>
              </form>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="community-header-user">
            <a href="residentprofile.php?user_id=<?= $userId ?>" class="community-header-user-name" style="text-decoration:none;color:var(--text);"><?= $fullName ?></a>
            <div class="community-header-user-avatar">
              <a href="residentprofile.php?user_id=<?= $userId ?>">
                <img src="<?= $profilePicture ?>" alt="Resident Photo" />
              </a>
            </div>
          </div>
        </div>
      </header>

      <div class="community-board-layout">
        <section class="community-feed-column" id="feedColumn">

          <div class="community-composer-card panel">
            <form method="POST" action="residentcommunity.php" enctype="multipart/form-data" id="postForm">
              <input type="hidden" name="action" value="post">
              <div class="community-composer-top">
                <a href="residentprofile.php?user_id=<?= $userId ?>">
                  <img src="<?= $profilePicture ?>" alt="Resident Photo" class="community-composer-avatar" />
                </a>
                <input type="text" name="body" id="composerInput" placeholder="Share an update with your barangay..." />
              </div>
              <div class="media-preview-wrap" id="mediaPreviewWrap"></div>
              <input type="file" name="post_images[]" id="imageFileInput" multiple accept="image/*" style="display:none;" onchange="handleFileSelect(this)" />
              <input type="file" name="post_images[]" id="attachFileInput" multiple style="display:none;" onchange="handleFileSelect(this)" />
              <div class="community-composer-bottom">
                <div class="community-composer-tools">
                  <button type="button" class="composer-tool-btn" onclick="document.getElementById('imageFileInput').click()" title="Add image"><i class="fa-regular fa-image"></i></button>
                  <button type="button" class="composer-tool-btn" onclick="document.getElementById('attachFileInput').click()" title="Attach file"><i class="fa-solid fa-paperclip"></i></button>
                  <button type="button" class="composer-tool-btn" title="Add location"><i class="fa-solid fa-location-dot"></i></button>
                </div>
                <div class="community-composer-right">
                  <button class="btn btn--primary" type="submit">Post</button>
                </div>
              </div>
            </form>
          </div>

          <?php foreach ($posts as $post):
            $postId       = (int)$post['POST_ID'];
            $posterId     = (int)$post['POSTER_ID'];
            $postName     = htmlspecialchars(rtrim($post['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($post['LAST_NAME']));
            $postAvatar   = resolveAvatar($post['PROFILE_PICTURE'], $post['GENDER']);
            $reactCount   = (int)$post['REACT_COUNT'];
            $commentCount = (int)$post['COMMENT_COUNT'];
            $myReaction   = $post['MY_REACTION'] ? rtrim($post['MY_REACTION']) : null;
            $postTime     = $post['CREATED_AT']->format('M d, Y \• g:i A');
            $myMeta       = $myReaction ? ($reactionMeta[$myReaction] ?? $reactionMeta['LIKE']) : null;

            $imgStmt = sqlsrv_query($conn,
                "SELECT IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = ? ORDER BY CREATED_AT ASC", [$postId]);
            $postImages = [];
            while ($ir = sqlsrv_fetch_array($imgStmt, SQLSRV_FETCH_ASSOC)) $postImages[] = $ir;
            $imgCount = count($postImages);

            $commStmt = sqlsrv_query($conn,
                "SELECT C.BODY, C.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE
                 FROM COMMENTS C JOIN REGISTRATION R ON C.USER_ID = R.USER_ID
                 WHERE C.POST_ID = ? ORDER BY C.CREATED_AT ASC", [$postId]);
            $comments = [];
            while ($cr = sqlsrv_fetch_array($commStmt, SQLSRV_FETCH_ASSOC)) $comments[] = $cr;

            $reactSumStmt = sqlsrv_query($conn,
                "SELECT REACTION_TYPE, COUNT(*) AS CNT FROM LIKES WHERE POST_ID = ? GROUP BY REACTION_TYPE ORDER BY CNT DESC", [$postId]);
            $reactionSummary = [];
            while ($rr = sqlsrv_fetch_array($reactSumStmt, SQLSRV_FETCH_ASSOC)) $reactionSummary[] = $rr;
          ?>
          <article class="community-stream-card panel"
                   id="post-<?= $postId ?>"
                   data-poster="<?= strtolower($postName) ?>"
                   data-body="<?= strtolower(htmlspecialchars($post['BODY'])) ?>">

            <div class="community-stream-head">
              <div class="community-stream-user">
                <div class="community-stream-avatar resident">
                  <a href="residentprofile.php?user_id=<?= $posterId ?>">
                    <img src="<?= $postAvatar ?>" alt="<?= $postName ?>" />
                  </a>
                </div>
                <div class="community-stream-meta">
                  <a href="residentprofile.php?user_id=<?= $posterId ?>" class="poster-link"><?= $postName ?></a>
                  <p>Resident Post • <?= $postTime ?></p>
                </div>
              </div>
            </div>

            <div class="community-stream-tagline">
              <span class="community-post-tag resident">Resident</span>
            </div>

            <?php if (!empty(trim($post['BODY']))): ?>
            <div class="community-stream-body">
              <p><?= nl2br(htmlspecialchars($post['BODY'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($imgCount > 0): ?>
            <div class="post-media-grid count-<?= min($imgCount, 4) ?>">
              <?php foreach (array_slice($postImages, 0, 4) as $idx => $img):
                $ext    = strtolower(pathinfo($img['IMAGE_PATH'], PATHINFO_EXTENSION));
                $isImg  = in_array($ext, $imageExts);
                $single = ($imgCount === 1) ? 'single' : '';
              ?>
              <div class="media-cell <?= $single ?>">
                <?php if ($isImg): ?>
                <img src="<?= htmlspecialchars($img['IMAGE_PATH']) ?>" alt="Post image" />
                <?php else: ?>
                <a href="<?= htmlspecialchars($img['IMAGE_PATH']) ?>" class="file-link" target="_blank">
                  <i class="fa-solid fa-file"></i><?= htmlspecialchars(basename($img['IMAGE_PATH'])) ?>
                </a>
                <?php endif; ?>
                <?php if ($idx === 3 && $imgCount > 4): ?>
                <div class="more-overlay">+<?= $imgCount - 4 ?></div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="reaction-bar">
              <?php foreach ($reactionSummary as $rs):
                $rType = rtrim($rs['REACTION_TYPE']);
                $rMeta = $reactionMeta[$rType] ?? $reactionMeta['LIKE'];
              ?>
              <span class="reaction-bar-item">
                <span class="r-emoji"><?= $rMeta['emoji'] ?></span>
                <span><?= (int)$rs['CNT'] ?></span>
              </span>
              <?php endforeach; ?>
              <?php if ($reactCount === 0): ?>
              <span style="color:var(--text-muted);font-size:12px;">No reactions yet</span>
              <?php endif; ?>
              <span style="margin-left:auto;cursor:pointer;" onclick="toggleComments(<?= $postId ?>)">
                <i class="fa-regular fa-comment"></i>
                <?= $commentCount ?> <?= $commentCount === 1 ? 'comment' : 'comments' ?>
              </span>
            </div>

            <div class="community-stream-actions" style="padding-top:10px;">
              <div class="reaction-btn-wrap">
                <form method="POST" action="react.php" style="margin:0;">
                  <input type="hidden" name="post_id" value="<?= $postId ?>">
                  <input type="hidden" name="redirect" value="residentcommunity.php#post-<?= $postId ?>">
                  <input type="hidden" name="reaction_type" id="rt-<?= $postId ?>" value="<?= $myReaction ?? 'LIKE' ?>">
                  <button type="submit" class="community-action <?= $myMeta ? 'reacted' : '' ?>"
                    style="<?= $myMeta ? 'color:' . $myMeta['color'] . ';' : '' ?>">
                    <?php if ($myMeta): ?>
                      <?= $myMeta['emoji'] ?> <?= $myMeta['label'] ?>
                    <?php else: ?>
                      <i class="fa-regular fa-thumbs-up"></i> React
                    <?php endif; ?>
                  </button>
                </form>
                <div class="reaction-picker">
                  <?php foreach ($reactionMeta as $rKey => $rMeta): ?>
                  <button type="button" class="reaction-option" onclick="setReaction(<?= $postId ?>, '<?= $rKey ?>')" title="<?= $rMeta['label'] ?>">
                    <span class="r-emoji"><?= $rMeta['emoji'] ?></span>
                    <span class="r-label"><?= $rMeta['label'] ?></span>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>

              <button type="button" class="community-action" onclick="toggleComments(<?= $postId ?>)" id="comment-btn-<?= $postId ?>">
                <i class="fa-regular fa-comment"></i>
                Comment <?php if ($commentCount > 0): ?><span style="font-size:11px;color:#aaa;">(<?= $commentCount ?>)</span><?php endif; ?>
              </button>
            </div>

            <div class="comment-section" id="comments-<?= $postId ?>">
              <?php if (!empty($comments)): ?>
              <div style="padding:12px 16px 0;display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($comments as $com):
                  $comAvatar  = resolveAvatar($com['PROFILE_PICTURE'], $com['GENDER']);
                  $comName    = htmlspecialchars(rtrim($com['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($com['LAST_NAME']));
                  $comInitial = strtoupper(substr(rtrim($com['FIRST_NAME']), 0, 1));
                ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                  <div class="comment-avatar"><img src="<?= $comAvatar ?>" alt="<?= $comName ?>" /></div>
                  <div style="background:#f5f6fa;border-radius:8px;padding:8px 12px;flex:1;">
                    <strong style="font-size:13px;color:#051650;display:block;"><?= $comName ?></strong>
                    <p style="font-size:13px;color:#555;margin:2px 0 0;"><?= nl2br(htmlspecialchars($com['BODY'])) ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <div style="padding:10px 16px 14px;">
                <form method="POST" action="react.php" style="display:flex;gap:8px;align-items:center;">
                  <input type="hidden" name="action" value="comment">
                  <input type="hidden" name="post_id" value="<?= $postId ?>">
                  <input type="hidden" name="redirect" value="residentcommunity.php#post-<?= $postId ?>">
                  <div class="comment-avatar"><img src="<?= $profilePicture ?>" alt="<?= $fullName ?>" /></div>
                  <input type="text" name="comment_body" placeholder="Write a comment…"
                    style="flex:1;border:1px solid #ddd;border-radius:20px;padding:8px 14px;font-size:13px;font-family:inherit;outline:none;" required />
                  <button type="submit"
                    style="background:#051650;color:#ccff00;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
                    Send
                  </button>
                </form>
              </div>
            </div>

          </article>
          <?php endforeach; ?>

          <?php if (empty($posts)): ?>
          <div class="panel" style="padding:32px;text-align:center;color:#aaa;">
            <i class="fa-solid fa-comments" style="font-size:32px;margin-bottom:12px;display:block;"></i>
            No posts yet. Be the first to share something with your barangay!
          </div>
          <?php endif; ?>
        </section>

        <aside class="community-widgets-column">
          <div class="panel community-widget-card">
            <div class="community-widget-head"><h3>Latest Announcements</h3></div>
            <div class="community-widget-list community-widget-list--stacked" id="announcementsList">
              <?php if (empty($announcements)): ?>
              <p style="font-size:13px;color:#aaa;">No announcements at this time.</p>
              <?php else: ?>
              <?php foreach ($announcements as $ann): ?>
              <div class="community-mini-post" data-ann-title="<?= strtolower(htmlspecialchars(rtrim($ann['TITLE']))) ?>">
                <span class="community-pinned-type official">Official</span>
                <h4><?= htmlspecialchars(rtrim($ann['TITLE'])) ?></h4>
                <p><?= htmlspecialchars(mb_strimwidth(rtrim($ann['BODY']), 0, 80, '...')) ?></p>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="panel community-widget-card">
            <div class="community-widget-head"><h3>Barangay Channels</h3></div>
            <div class="community-channel-list">
              <a href="#" class="community-channel-item"><span class="community-channel-avatar official">OF</span><span>Official Announcements</span></a>
              <a href="#" class="community-channel-item"><span class="community-channel-avatar event">EV</span><span>Community Events</span></a>
              <a href="#" class="community-channel-item"><span class="community-channel-avatar alert">AL</span><span>Emergency Updates</span></a>
              <a href="#" class="community-channel-item"><span class="community-channel-avatar resident">RP</span><span>Resident Discussions</span></a>
            </div>
          </div>
          <div class="panel community-widget-card">
            <div class="community-widget-head"><h3>Online Barangay Contacts</h3></div>
            <div class="community-contact-list">
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Barangay Secretary</span></div>
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Health Desk</span></div>
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Youth Desk</span></div>
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Incident Hotline</span></div>
            </div>
          </div>
        </aside>
      </div>

    </div>
  </main>
</div>

<script>
function toggleNotif() { document.getElementById('notifDropdown').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const btn = document.getElementById('bellBtn');
  const dd  = document.getElementById('notifDropdown');
  if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', e => {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});

function toggleComments(postId) {
  const section = document.getElementById('comments-' + postId);
  const btn     = document.getElementById('comment-btn-' + postId);
  const isOpen  = section.classList.contains('open');
  section.classList.toggle('open', !isOpen);
  btn.classList.toggle('active-comment', !isOpen);
  if (!isOpen) section.querySelector('input[name="comment_body"]')?.focus();
}

const pickerTimers = {};

function openPicker(postId) {
  clearTimeout(pickerTimers[postId]);
  document.querySelectorAll('.reaction-picker.open').forEach(p => {
    if (p.dataset.postId !== String(postId)) p.classList.remove('open');
  });
  const picker = document.querySelector('#post-' + postId + ' .reaction-picker');
  if (picker) { picker.dataset.postId = postId; picker.classList.add('open'); }
}

function schedulClose(postId) {
  pickerTimers[postId] = setTimeout(() => {
    const picker = document.querySelector('#post-' + postId + ' .reaction-picker');
    if (picker) picker.classList.remove('open');
  }, 320);
}

document.querySelectorAll('.reaction-btn-wrap').forEach(wrap => {
  const postId = wrap.closest('article')?.id?.replace('post-', '');
  if (!postId) return;
  const trigger = wrap.querySelector('[type="submit"]');
  const picker  = wrap.querySelector('.reaction-picker');
  if (!trigger || !picker) return;

  trigger.addEventListener('mouseenter', () => openPicker(postId));
  trigger.addEventListener('mouseleave', () => schedulClose(postId));
  picker.addEventListener('mouseenter',  () => clearTimeout(pickerTimers[postId]));
  picker.addEventListener('mouseleave',  () => schedulClose(postId));
});

function setReaction(postId, type) {
  const picker = document.querySelector('#post-' + postId + ' .reaction-picker');
  if (picker) picker.classList.remove('open');
  document.getElementById('rt-' + postId).value = type;
  document.querySelector('#post-' + postId + ' .reaction-btn-wrap form [type="submit"]').click();
}

const hash = window.location.hash;
if (hash && hash.startsWith('#post-')) {
  const target = document.querySelector(hash);
  if (target) {
    setTimeout(() => {
      const postId = parseInt(hash.replace('#post-', ''));
      toggleComments(postId);
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('post-highlight');
    }, 150);
  }
}

let selectedFiles = [];
const imageExts   = ['jpg','jpeg','png','gif','webp','bmp'];

function handleFileSelect(input) {
  Array.from(input.files).forEach(file => {
    const already = selectedFiles.some(f => f.name === file.name && f.size === file.size);
    if (!already) selectedFiles.push(file);
  });
  input.value = '';
  renderPreviews();
  syncFiles();
}
function renderPreviews() {
  const wrap = document.getElementById('mediaPreviewWrap');
  wrap.innerHTML = '';
  selectedFiles.forEach((file, idx) => {
    const ext = file.name.split('.').pop().toLowerCase();
    const isImage = imageExts.includes(ext);
    const item = document.createElement('div');
    item.className = 'media-preview-item';
    if (isImage) {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      item.appendChild(img);
    } else {
      const icon = document.createElement('div');
      icon.className = 'file-icon';
      icon.innerHTML = '<i class="fa-solid fa-file"></i>';
      item.appendChild(icon);
    }
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'remove-btn'; btn.innerHTML = '&times;';
    btn.onclick = () => { selectedFiles.splice(idx, 1); renderPreviews(); syncFiles(); };
    item.appendChild(btn);
    wrap.appendChild(item);
  });
}
function syncFiles() {
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  document.getElementById('imageFileInput').files  = dt.files;
  document.getElementById('attachFileInput').files = dt.files;
}

document.getElementById('feedSearch').addEventListener('input', function() {
  const query = this.value.toLowerCase().trim();
  document.querySelectorAll('#feedColumn .community-stream-card').forEach(card => {
    const poster = card.dataset.poster || '';
    const body   = card.dataset.body   || '';
    card.classList.toggle('search-hidden', query !== '' && !poster.includes(query) && !body.includes(query));
  });
  document.querySelectorAll('#announcementsList .community-mini-post').forEach(ann => {
    const title = ann.dataset.annTitle || '';
    ann.classList.toggle('search-hidden', query !== '' && !title.includes(query));
  });
});
</script>
</body>
</html>