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
    'LIKE'  => ['icon' => 'fa-solid fa-thumbs-up',   'label' => 'Like',  'color' => '#1877f2'],
    'LOVE'  => ['icon' => 'fa-solid fa-heart',        'label' => 'Love',  'color' => '#f33e58'],
    'HAHA'  => ['icon' => 'fa-solid fa-face-laugh',   'label' => 'Haha',  'color' => '#f7b125'],
    'WOW'   => ['icon' => 'fa-solid fa-face-surprise','label' => 'Wow',   'color' => '#f7b125'],
    'SAD'   => ['icon' => 'fa-solid fa-face-sad-tear','label' => 'Sad',   'color' => '#f7b125'],
    'ANGRY' => ['icon' => 'fa-solid fa-face-angry',   'label' => 'Angry', 'color' => '#e9710f'],
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

    .reaction-btn-wrap { position:relative; display:inline-flex; justify-content:center; }
    .reaction-picker {
      position:absolute;
      bottom:calc(100% + 8px);
      left:50%;
      transform:translateX(-50%) translateY(4px);
      background:var(--surface); border:1px solid var(--border); border-radius:999px;
      padding:6px 10px; display:flex; gap:2px;
      box-shadow:0 8px 24px rgba(5,22,80,0.18);
      opacity:0; pointer-events:none;
      transition:opacity 0.15s, transform 0.15s;
      z-index:10000; white-space:nowrap;
    }
    .reaction-picker.open { opacity:1; pointer-events:all; transform:translateX(-50%) translateY(0); }
    .reaction-option { display:flex; flex-direction:column; align-items:center; gap:3px; cursor:pointer; padding:5px 7px; border-radius:8px; transition:transform 0.15s, background 0.15s; border:none; background:none; font-family:inherit; }
    .reaction-option:hover { transform:scale(1.35) translateY(-4px); background:rgba(5,22,80,0.04); }
    .reaction-option .r-icon { font-size:20px; line-height:1; }
    .reaction-option .r-label { font-size:9px; font-weight:700; color:var(--text-muted); }

    .lightbox-overlay { position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.88); display:none; align-items:center; justify-content:center; padding:16px; }
    .lightbox-overlay.open { display:flex; }
    .lightbox-overlay img { max-width:90vw; max-height:90vh; border-radius:6px; object-fit:contain; box-shadow:0 8px 48px rgba(0,0,0,0.6); }
    .lightbox-close { position:absolute; top:16px; right:20px; background:rgba(255,255,255,0.12); border:none; color:#fff; font-size:22px; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.2s; }
    .lightbox-close:hover { background:rgba(255,255,255,0.24); }
    .lightbox-nav { position:absolute; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.12); border:none; color:#fff; font-size:18px; width:44px; height:44px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.2s; }
    .lightbox-nav:hover { background:rgba(255,255,255,0.24); }
    .lightbox-prev { left:16px; }
    .lightbox-next { right:16px; }
    .lightbox-counter { position:absolute; bottom:16px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,0.7); font-size:13px; font-weight:600; background:rgba(0,0,0,0.4); padding:4px 14px; border-radius:999px; }

    .post-modal-overlay { position:fixed; inset:0; z-index:9000; background:rgba(5,22,80,0.65); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; padding:24px; }
    .post-modal-overlay.open { display:flex; }
    .post-modal-box { background:#fff; border-radius:16px; max-width:600px; width:100%; max-height:88vh; overflow-y:auto; box-shadow:0 16px 60px rgba(5,22,80,0.28); border-top:4px solid #ccff00; }
    .post-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:2; border-radius:16px 16px 0 0; }
    .post-modal-header h3 { font-size:15px; font-weight:700; color:#051650; }
    .post-modal-close { background:rgba(5,22,80,0.06); border:none; color:#555; font-size:16px; width:34px; height:34px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.2s; }
    .post-modal-close:hover { background:rgba(5,22,80,0.12); color:#051650; }
    .post-modal-body { padding:20px; }
    .media-cell img { cursor:pointer; transition:opacity 0.15s; }
    .media-cell img:hover { opacity:0.9; }

    .post-highlight { animation:highlightPost 2.5s ease; }
    @keyframes highlightPost { 0%{box-shadow:0 0 0 3px #ccff00;} 100%{box-shadow:none;} }

    .search-hidden { display:none !important; }

    .community-action { background:none; border:none; cursor:pointer; font-family:inherit; font-size:13px; display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:6px; color:var(--text-muted); text-decoration:none; transition:background 0.15s; font-weight:600; }
    .community-action:hover { background:rgba(5,22,80,0.06); color:var(--navy); }
    .community-action.reacted { font-weight:700; }
    #react-btn-0, button[id^="react-btn-"], button[id^="comment-btn-"] { transition:background 0.15s, color 0.15s; border-radius:6px; }
    button[id^="react-btn-"]:hover, button[id^="comment-btn-"]:hover { background:rgba(5,22,80,0.05); color:var(--navy) !important; }

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

<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightboxOnBg(event)">
  <button class="lightbox-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)"><i class="fa-solid fa-chevron-left"></i></button>
  <img id="lightboxImg" src="" alt="Photo" />
  <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>

<div class="post-modal-overlay" id="postModalOverlay" onclick="closePostModalOnBg(event)">
  <div class="post-modal-box" id="postModalBox">
    <div class="post-modal-header">
      <h3><i class="fa-solid fa-comment-dots" style="color:#ccff00;margin-right:8px;"></i>Post</h3>
      <button class="post-modal-close" onclick="closePostModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="post-modal-body" id="postModalBody"></div>
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
            <div class="community-composer-top" onclick="openComposeModal()" style="cursor:pointer;">
              <a href="residentprofile.php?user_id=<?= $userId ?>" onclick="event.stopPropagation()">
                <img src="<?= $profilePicture ?>" alt="Resident Photo" class="community-composer-avatar" />
              </a>
              <div style="flex:1;background:#f0f3f9;border-radius:999px;padding:10px 18px;font-size:14px;color:#93a0bb;border:1px solid #e4e8f0;">
                Share an update with your barangay...
              </div>
            </div>
            <div class="community-composer-bottom" style="border-top:1px solid #eee;margin-top:12px;padding-top:10px;">
              <div class="community-composer-tools">
                <button type="button" class="composer-tool-btn" onclick="openComposeModal('image')" title="Add image"><i class="fa-regular fa-image"></i></button>
                <button type="button" class="composer-tool-btn" onclick="openComposeModal('file')" title="Attach file"><i class="fa-solid fa-paperclip"></i></button>
              </div>
            </div>
          </div>

          <div class="post-modal-overlay" id="composeModalOverlay" onclick="closeComposeModalOnBg(event)" style="z-index:9500;">
            <div class="post-modal-box" id="composeModalBox" style="max-width:540px;">
              <div class="post-modal-header">
                <h3 style="font-size:17px;"><i class="fa-solid fa-pen-to-square" style="color:#ccff00;margin-right:8px;"></i>Create Post</h3>
                <button class="post-modal-close" onclick="closeComposeModal()"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <div class="post-modal-body" style="padding:16px 20px 20px;">
                <form method="POST" action="residentcommunity.php" enctype="multipart/form-data" id="postForm">
                  <input type="hidden" name="action" value="post">
                  <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px;">
                    <img src="<?= $profilePicture ?>" alt="Resident Photo" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #ccff00;flex-shrink:0;" />
                    <div>
                      <strong style="font-size:14px;color:#051650;"><?= $fullName ?></strong>
                      <p style="font-size:12px;color:#888;margin-top:1px;">Posting to Barangay Alapan I-A</p>
                    </div>
                  </div>
                  <textarea name="body" id="composerTextarea" placeholder="What's on your mind, <?= $firstName ?>?"
                    style="width:100%;min-height:130px;border:none;outline:none;font-family:inherit;font-size:16px;color:#333;resize:none;background:transparent;"></textarea>
                  <div class="media-preview-wrap" id="mediaPreviewWrap" style="margin-top:8px;"></div>
                  <input type="file" name="post_images[]" id="singleFileInput" multiple style="display:none;" onchange="handleFileSelect(this)" />
                  <div style="border:1px solid #e4e8f0;border-radius:12px;padding:10px 14px;margin-top:12px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-size:13px;color:#888;font-weight:600;">Add to your post:</div>
                    <div style="display:flex;gap:6px;">
                      <button type="button" onclick="triggerFile('image')" title="Add image"
                        style="background:rgba(5,22,80,0.06);border:none;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:#051650;transition:background 0.15s;"
                        onmouseover="this.style.background='rgba(5,22,80,0.12)'" onmouseout="this.style.background='rgba(5,22,80,0.06)'">
                        <i class="fa-regular fa-image" style="color:#45bd62;"></i>
                      </button>
                      <button type="button" onclick="triggerFile('file')" title="Attach file"
                        style="background:rgba(5,22,80,0.06);border:none;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background 0.15s;"
                        onmouseover="this.style.background='rgba(5,22,80,0.12)'" onmouseout="this.style.background='rgba(5,22,80,0.06)'">
                        <i class="fa-solid fa-paperclip" style="color:#f7b928;"></i>
                      </button>
                    </div>
                  </div>
                  <button type="submit" id="composeSubmitBtn"
                    style="width:100%;margin-top:12px;background:#051650;color:#ccff00;border:none;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background 0.2s;"
                    onmouseover="this.style.background='#0a2470'" onmouseout="this.style.background='#051650'">
                    <i class="fa-solid fa-paper-plane" style="margin-right:8px;"></i>Post
                  </button>
                </form>
              </div>
            </div>
          </div>

          <?php foreach ($posts as $postIdx => $post):
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
                   data-post-index="<?= $postIdx ?>"
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
                  <p style="cursor:pointer;" onclick="openPostModal(<?= $postId ?>)">Resident Post • <?= $postTime ?></p>
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
              <?php
                $allImagePaths = [];
                foreach ($postImages as $pi) {
                    $ext2 = strtolower(pathinfo($pi['IMAGE_PATH'], PATHINFO_EXTENSION));
                    if (in_array($ext2, $imageExts)) $allImagePaths[] = $pi['IMAGE_PATH'];
                }
                $imgPathsJson = htmlspecialchars(json_encode($allImagePaths), ENT_QUOTES);
                $imgIdx = 0;
              ?>
              <?php foreach (array_slice($postImages, 0, 4) as $idx => $img):
                $ext    = strtolower(pathinfo($img['IMAGE_PATH'], PATHINFO_EXTENSION));
                $isImg  = in_array($ext, $imageExts);
                $single = ($imgCount === 1) ? 'single' : '';
              ?>
              <div class="media-cell <?= $single ?>">
                <?php if ($isImg): ?>
                <img src="<?= htmlspecialchars($img['IMAGE_PATH']) ?>" alt="Post image"
                     onclick="openLightbox(<?= $imgPathsJson ?>, <?= $imgIdx ?>)"
                     style="cursor:pointer;" />
                <?php $imgIdx++; ?>
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
                <i class="<?= $rMeta['icon'] ?> r-icon" style="color:<?= $rMeta['color'] ?>;font-size:14px;"></i>
                <span><?= (int)$rs['CNT'] ?></span>
              </span>
              <?php endforeach; ?>
              <?php if ($reactCount === 0): ?>
              <span style="color:var(--text-muted);font-size:12px;">No reactions yet</span>
              <?php endif; ?>
              <span data-comment-toggle style="margin-left:auto;cursor:pointer;" onclick="toggleComments(<?= $postId ?>)">
                <i class="fa-regular fa-comment"></i>
                <?= $commentCount ?> <?= $commentCount === 1 ? 'comment' : 'comments' ?>
              </span>
            </div>

            <div style="display:flex;border-top:1px solid var(--border);margin-top:8px;">
              <div class="reaction-btn-wrap" id="rbw-<?= $postId ?>" style="flex:1;display:flex;justify-content:center;">
                <button type="button"
                  id="react-btn-<?= $postId ?>"
                  data-post-id="<?= $postId ?>"
                  data-reaction="<?= htmlspecialchars($myReaction ?? '') ?>"
                  style="width:100%;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px 0;border:none;background:none;cursor:pointer;font-family:inherit;font-size:14px;font-weight:700;border-radius:0;<?= $myMeta ? 'color:' . $myMeta['color'] . ';' : 'color:#65676b;' ?>">
                  <?php if ($myMeta): ?>
                    <i class="<?= $myMeta['icon'] ?>" style="font-size:18px;"></i> <?= $myMeta['label'] ?>
                  <?php else: ?>
                    <i class="fa-regular fa-thumbs-up" style="font-size:18px;"></i> Like
                  <?php endif; ?>
                </button>
                <div class="reaction-picker" data-post-id="<?= $postId ?>">
                  <?php foreach ($reactionMeta as $rKey => $rMeta): ?>
                  <button type="button" class="reaction-option" onclick="setReaction(<?= $postId ?>, '<?= $rKey ?>')" title="<?= $rMeta['label'] ?>">
                    <i class="<?= $rMeta['icon'] ?> r-icon" style="color:<?= $rMeta['color'] ?>;"></i>
                    <span class="r-label"><?= $rMeta['label'] ?></span>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div style="width:1px;background:var(--border);margin:6px 0;"></div>
              <button type="button" id="comment-btn-<?= $postId ?>" onclick="toggleComments(<?= $postId ?>)"
                style="flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px 0;border:none;background:none;cursor:pointer;font-family:inherit;font-size:14px;font-weight:700;color:#65676b;border-radius:0;">
                <i class="fa-regular fa-comment" style="font-size:18px;"></i> Comment
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

          <?php if (count($posts) > 10): ?>
          <div id="loadMoreWrap" style="text-align:center;padding:10px 0 20px;">
            <button type="button" id="loadMoreBtn" onclick="loadMorePosts()"
              style="background:#051650;color:#ccff00;border:none;border-radius:10px;padding:12px 32px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background 0.2s;"
              onmouseover="this.style.background='#0a2470'" onmouseout="this.style.background='#051650'">
              <i class="fa-solid fa-chevron-down" style="margin-right:8px;"></i>Load more posts
            </button>
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
function openComposeModal(trigger) {
  document.getElementById('composeModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => {
    const ta = document.getElementById('composerTextarea');
    if (ta) ta.focus();
    if (trigger === 'image') setTimeout(() => document.getElementById('imageFileInput').click(), 200);
    if (trigger === 'file')  setTimeout(() => document.getElementById('attachFileInput').click(), 200);
  }, 80);
}
function closeComposeModal() {
  document.getElementById('composeModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function closeComposeModalOnBg(e) {
  if (e.target === document.getElementById('composeModalOverlay')) closeComposeModal();
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeComposeModal();
  }
});

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

const reactionMeta = {
  LIKE:  { icon: 'fa-solid fa-thumbs-up',    label: 'Like',  color: '#1877f2' },
  LOVE:  { icon: 'fa-solid fa-heart',         label: 'Love',  color: '#f33e58' },
  HAHA:  { icon: 'fa-solid fa-face-laugh',    label: 'Haha',  color: '#f7b125' },
  WOW:   { icon: 'fa-solid fa-face-surprise', label: 'Wow',   color: '#f7b125' },
  SAD:   { icon: 'fa-solid fa-face-sad-tear', label: 'Sad',   color: '#f7b125' },
  ANGRY: { icon: 'fa-solid fa-face-angry',    label: 'Angry', color: '#e9710f' },
};

function openPicker(postId) {
  clearTimeout(pickerTimers[postId]);
  document.querySelectorAll('.reaction-picker.open').forEach(p => {
    if (p.dataset.postId !== String(postId)) p.classList.remove('open');
  });
  const picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
  if (picker) picker.classList.add('open');
}

function schedulClose(postId) {
  pickerTimers[postId] = setTimeout(() => {
    const picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
    if (picker) picker.classList.remove('open');
  }, 320);
}

function bindPickerEvents(wrap) {
  const postId  = wrap.closest('article')?.id?.replace('post-', '');
  if (!postId) return;
  const trigger = wrap.querySelector('#react-btn-' + postId);
  const picker  = wrap.querySelector('.reaction-picker');
  if (!trigger || !picker) return;
  trigger.addEventListener('mouseenter', () => openPicker(postId));
  trigger.addEventListener('mouseleave', () => schedulClose(postId));
  picker.addEventListener('mouseenter',  () => clearTimeout(pickerTimers[postId]));
  picker.addEventListener('mouseleave',  () => schedulClose(postId));
}

document.querySelectorAll('.reaction-btn-wrap').forEach(bindPickerEvents);

function setReaction(postId, type) {
  const picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
  if (picker) picker.classList.remove('open');

  const btn = document.getElementById('react-btn-' + postId);
  const prevReaction = btn ? btn.dataset.reaction : '';

  const fd = new FormData();
  fd.append('post_id', postId);
  fd.append('reaction_type', type);
  fd.append('ajax', '1');

  fetch('react.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!btn) return;
      if (data.removed) {
        btn.dataset.reaction = '';
        btn.classList.remove('reacted');
        btn.style.color = '';
        btn.innerHTML = '<i class="fa-regular fa-thumbs-up"></i> React';
      } else {
        const meta = reactionMeta[type];
        btn.dataset.reaction = type;
        btn.classList.add('reacted');
        btn.style.color = meta.color;
        btn.innerHTML = '<i class="' + meta.icon + '"></i> ' + meta.label;
      }
      const bar = document.querySelector('#post-' + postId + ' .reaction-bar');
      if (bar && data.summary !== undefined) {
        updateReactionBar(bar, data.summary, data.total);
      }
    })
    .catch(() => {});
}

function updateReactionBar(bar, summary, total) {
  const commentSpan = bar.querySelector('[data-comment-toggle]');
  bar.innerHTML = '';
  if (total === 0) {
    const s = document.createElement('span');
    s.style.cssText = 'color:var(--text-muted);font-size:12px;';
    s.textContent = 'No reactions yet';
    bar.appendChild(s);
  } else {
    summary.forEach(function(r) {
      const meta = reactionMeta[r.type];
      if (!meta) return;
      const span = document.createElement('span');
      span.className = 'reaction-bar-item';
      span.innerHTML = '<i class="' + meta.icon + ' r-icon" style="color:' + meta.color + ';font-size:14px;"></i><span>' + r.cnt + '</span>';
      bar.appendChild(span);
    });
  }
  if (commentSpan) {
    bar.appendChild(commentSpan);
  }
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

function triggerFile(type) {
  const input = document.getElementById('singleFileInput');
  if (type === 'image') {
    input.accept = 'image/*';
  } else {
    input.accept = '';
  }
  input.click();
}

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
  document.getElementById('singleFileInput').files = dt.files;
}

const POSTS_PER_PAGE = 10;
let visiblePostCount = POSTS_PER_PAGE;

function initPostVisibility() {
  const posts = document.querySelectorAll('#feedColumn .community-stream-card');
  posts.forEach((post, idx) => {
    post.style.display = idx < POSTS_PER_PAGE ? '' : 'none';
  });
  const btn = document.getElementById('loadMoreBtn');
  const wrap = document.getElementById('loadMoreWrap');
  if (wrap) wrap.style.display = posts.length > POSTS_PER_PAGE ? '' : 'none';
}

function loadMorePosts() {
  const posts = document.querySelectorAll('#feedColumn .community-stream-card');
  const next = visiblePostCount + POSTS_PER_PAGE;
  posts.forEach((post, idx) => {
    if (idx >= visiblePostCount && idx < next) {
      post.style.display = '';
    }
  });
  visiblePostCount = next;
  const wrap = document.getElementById('loadMoreWrap');
  if (visiblePostCount >= posts.length && wrap) wrap.style.display = 'none';
}

initPostVisibility();

document.getElementById('feedSearch').addEventListener('input', function() {
  const query = this.value.toLowerCase().trim();
  const wrap  = document.getElementById('loadMoreWrap');
  if (query !== '') {
    document.querySelectorAll('#feedColumn .community-stream-card').forEach(card => {
      const poster = card.dataset.poster || '';
      const body   = card.dataset.body   || '';
      const match  = poster.includes(query) || body.includes(query);
      card.style.display = match ? '' : 'none';
      card.classList.toggle('search-hidden', !match);
    });
    if (wrap) wrap.style.display = 'none';
  } else {
    document.querySelectorAll('#feedColumn .community-stream-card').forEach(card => {
      card.classList.remove('search-hidden');
    });
    initPostVisibility();
  }
  document.querySelectorAll('#announcementsList .community-mini-post').forEach(ann => {
    const title = ann.dataset.annTitle || '';
    ann.classList.toggle('search-hidden', query !== '' && !title.includes(query));
  });
});

let lbImages = [], lbIndex = 0;
function openLightbox(images, idx) {
  lbImages = images;
  lbIndex  = idx;
  showLightboxImage();
  document.getElementById('lightboxOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function showLightboxImage() {
  document.getElementById('lightboxImg').src = lbImages[lbIndex];
  const counter = document.getElementById('lightboxCounter');
  if (lbImages.length > 1) {
    counter.textContent = (lbIndex + 1) + ' / ' + lbImages.length;
    counter.style.display = 'block';
  } else {
    counter.style.display = 'none';
  }
  document.querySelector('.lightbox-prev').style.display = lbImages.length > 1 ? 'flex' : 'none';
  document.querySelector('.lightbox-next').style.display = lbImages.length > 1 ? 'flex' : 'none';
}
function lightboxNav(dir) {
  lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length;
  showLightboxImage();
}
function closeLightbox() {
  document.getElementById('lightboxOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function closeLightboxOnBg(e) {
  if (e.target === document.getElementById('lightboxOverlay')) closeLightbox();
}
document.addEventListener('keydown', function(e) {
  const lb = document.getElementById('lightboxOverlay');
  if (!lb.classList.contains('open')) return;
  if (e.key === 'Escape')      closeLightbox();
  if (e.key === 'ArrowLeft')   lightboxNav(-1);
  if (e.key === 'ArrowRight')  lightboxNav(1);
});

const postModalData = {};
<?php foreach ($posts as $post):
  $pid = (int)$post['POST_ID'];
  $pName = htmlspecialchars(rtrim($post['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($post['LAST_NAME']));
  $pBody = addslashes(htmlspecialchars($post['BODY']));
  $pTime = $post['CREATED_AT']->format('M d, Y \• g:i A');
  $pAvatar = resolveAvatar($post['PROFILE_PICTURE'], $post['GENDER']);
?>
postModalData[<?= $pid ?>] = {
  name: <?= json_encode($pName) ?>,
  body: <?= json_encode(htmlspecialchars($post['BODY'])) ?>,
  time: <?= json_encode($pTime) ?>,
  avatar: <?= json_encode($pAvatar) ?>,
  posterId: <?= (int)$post['POSTER_ID'] ?>
};
<?php endforeach; ?>

function openPostModal(postId) {
  const d = postModalData[postId];
  if (!d) return;
  const body = document.getElementById('postModalBody');
  body.innerHTML = '<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;">'
    + '<div style="width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid #ccff00;">'
    + '<img src="' + d.avatar + '" style="width:100%;height:100%;object-fit:cover;" /></div>'
    + '<div><strong style="font-size:15px;color:#051650;">' + d.name + '</strong>'
    + '<p style="font-size:12px;color:#888;margin-top:2px;">' + d.time + '</p></div></div>'
    + (d.body ? '<div style="font-size:15px;line-height:1.7;color:#333;white-space:pre-wrap;border-top:1px solid #eee;padding-top:14px;">' + d.body + '</div>' : '')
    + '<div style="margin-top:16px;border-top:1px solid #eee;padding-top:12px;">'
    + '<a href="residentcommunity.php#post-' + postId + '" style="display:inline-flex;align-items:center;gap:8px;background:#051650;color:#ccff00;padding:9px 20px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700;">'
    + '<i class="fa-solid fa-arrow-right-to-bracket"></i> Go to post & comments</a></div>';
  document.getElementById('postModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closePostModal() {
  document.getElementById('postModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function closePostModalOnBg(e) {
  if (e.target === document.getElementById('postModalOverlay')) closePostModal();
}
</script>
</body>
</html>