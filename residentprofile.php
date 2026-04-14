<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$myUserId   = (int)$_SESSION['user_id'];
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($viewUserId === 0) {
    header("Location: residentcommunity.php");
    exit();
}

$meRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT FIRST_NAME, LAST_NAME, GENDER, PROFILE_PICTURE FROM REGISTRATION WHERE USER_ID = ?", [$myUserId]),
    SQLSRV_FETCH_ASSOC
);
$myFirstName = $meRow ? htmlspecialchars(rtrim($meRow['FIRST_NAME'])) : 'Resident';
$myLastName  = $meRow ? htmlspecialchars(rtrim($meRow['LAST_NAME']))  : '';
$myFullName  = $myFirstName . ' ' . $myLastName;
$myGender    = $meRow ? strtolower(rtrim($meRow['GENDER'] ?? '')) : '';
if ($meRow && !empty($meRow['PROFILE_PICTURE'])) $myPic = htmlspecialchars($meRow['PROFILE_PICTURE']);
elseif ($myGender === 'male')                    $myPic = 'default/male.png';
elseif ($myGender === 'female')                  $myPic = 'default/female.png';
else                                             $myPic = 'default/neutral.png';

$profRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE, R.BIRTHDATE, R.MOBILE_NUMBER,
                U.USERNAME, U.CREATED_AT AS JOINED_AT
         FROM REGISTRATION R JOIN USERS U ON R.USER_ID = U.USER_ID
         WHERE R.USER_ID = ?",
        [$viewUserId]
    ),
    SQLSRV_FETCH_ASSOC
);
if (!$profRow) { header("Location: residentcommunity.php"); exit(); }

$profFirstName = htmlspecialchars(rtrim($profRow['FIRST_NAME']));
$profLastName  = htmlspecialchars(rtrim($profRow['LAST_NAME']));
$profFullName  = $profFirstName . ' ' . $profLastName;
$profGender    = strtolower(rtrim($profRow['GENDER'] ?? ''));
$profUsername  = htmlspecialchars(rtrim($profRow['USERNAME']));
$profJoined    = $profRow['JOINED_AT'] ? $profRow['JOINED_AT']->format('F Y') : 'Unknown';
$profBirthdate = $profRow['BIRTHDATE']     ? $profRow['BIRTHDATE']->format('F d, Y')     : null;
$profMobile    = $profRow['MOBILE_NUMBER'] ? htmlspecialchars(rtrim($profRow['MOBILE_NUMBER'])) : null;

if (!empty($profRow['PROFILE_PICTURE']))  $profPic = htmlspecialchars($profRow['PROFILE_PICTURE']);
elseif ($profGender === 'male')           $profPic = 'default/male.png';
elseif ($profGender === 'female')         $profPic = 'default/female.png';
else                                      $profPic = 'default/neutral.png';

$postCount = (int)(sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS C FROM POSTS WHERE USER_ID = ?", [$viewUserId]),
    SQLSRV_FETCH_ASSOC
)['C'] ?? 0);

$totalReacts = (int)(sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS C FROM LIKES L JOIN POSTS P ON L.POST_ID = P.POST_ID WHERE P.USER_ID = ?", [$viewUserId]),
    SQLSRV_FETCH_ASSOC
)['C'] ?? 0);

$totalComments = (int)(sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS C FROM COMMENTS C JOIN POSTS P ON C.POST_ID = P.POST_ID WHERE P.USER_ID = ?", [$viewUserId]),
    SQLSRV_FETCH_ASSOC
)['C'] ?? 0);

$imageExts = ['jpg','jpeg','png','gif','webp','bmp'];

$allImgStmt = sqlsrv_query($conn,
    "SELECT PI.IMAGE_PATH FROM POST_IMAGES PI JOIN POSTS P ON PI.POST_ID = P.POST_ID
     WHERE P.USER_ID = ? ORDER BY PI.CREATED_AT DESC",
    [$viewUserId]
);
$allPhotos = [];
$allFiles  = [];
while ($mr = sqlsrv_fetch_array($allImgStmt, SQLSRV_FETCH_ASSOC)) {
    $path = $mr['IMAGE_PATH'];
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, $imageExts)) $allPhotos[] = htmlspecialchars($path);
    else                             $allFiles[]  = htmlspecialchars($path);
}

$postsStmt = sqlsrv_query($conn,
    "SELECT P.POST_ID, P.BODY, P.CREATED_AT,
        (SELECT COUNT(*) FROM LIKES L WHERE L.POST_ID = P.POST_ID) AS REACT_COUNT,
        (SELECT COUNT(*) FROM COMMENTS C WHERE C.POST_ID = P.POST_ID) AS COMMENT_COUNT,
        (SELECT TOP 1 REACTION_TYPE FROM LIKES WHERE POST_ID = P.POST_ID AND USER_ID = ?) AS MY_REACTION
     FROM POSTS P WHERE P.USER_ID = ? ORDER BY P.CREATED_AT DESC",
    [$myUserId, $viewUserId]
);
$posts = [];
while ($row = sqlsrv_fetch_array($postsStmt, SQLSRV_FETCH_ASSOC)) $posts[] = $row;

$reactionMeta = [
    'LIKE'  => ['emoji' => '👍', 'label' => 'Like',  'color' => '#1877f2'],
    'LOVE'  => ['emoji' => '❤️',  'label' => 'Love',  'color' => '#f33e58'],
    'HAHA'  => ['emoji' => '😂', 'label' => 'Haha',  'color' => '#f7b125'],
    'WOW'   => ['emoji' => '😮', 'label' => 'Wow',   'color' => '#f7b125'],
    'SAD'   => ['emoji' => '😢', 'label' => 'Sad',   'color' => '#f7b125'],
    'ANGRY' => ['emoji' => '😡', 'label' => 'Angry', 'color' => '#e9710f'],
];

function resolveAvatar($pic, $gender) {
    if (!empty($pic)) return htmlspecialchars($pic);
    $g = strtolower(trim($gender ?? ''));
    return $g === 'male' ? 'default/male.png' : ($g === 'female' ? 'default/female.png' : 'default/neutral.png');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $profFullName ?> — BarangayKonek</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="resident.css"/>
  <style>
    :root { --lime-dim: #b8e800; }
    .sidebar-divider { height:1px; background:rgba(255,255,255,0.08); margin:6px 14px; }

    .profile-layout {
      display: grid;
      grid-template-columns: minmax(0,1fr) 340px;
      gap: 22px;
      align-items: start;
    }

    .profile-right {
      position: sticky;
      top: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    /* ── Hero ── */
    .profile-hero {
      background: var(--navy);
      border-radius: var(--radius);
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: var(--shadow-lg);
    }
    .profile-hero-banner {
      height: 160px;
      background: linear-gradient(135deg,#051650 0%,#0a2270 55%,#1a3a9e 100%);
      position: relative;
    }
    .profile-hero-banner::after {
      content:''; position:absolute; inset:0;
      background: repeating-linear-gradient(45deg,rgba(204,255,0,0.05) 0px,rgba(204,255,0,0.05) 1px,transparent 1px,transparent 28px);
    }
    .profile-hero-body { padding: 0 28px 26px; position:relative; }
    .profile-hero-avatar {
      width: 104px; height: 104px; border-radius: 50%;
      border: 4px solid var(--surface); object-fit: cover;
      margin-top: -52px; position:relative; z-index:1;
      background: var(--surface); box-shadow: 0 4px 20px rgba(5,22,80,0.25);
    }
    .profile-hero-info { margin-top: 12px; }
    .profile-hero-name  { font-size: 24px; font-weight: 800; color: var(--surface); margin-bottom: 3px; }
    .profile-hero-uname { font-size: 13px; color: rgba(255,255,255,0.5); margin-bottom: 14px; }
    .profile-hero-meta  { display:flex; flex-wrap:wrap; gap:18px; margin-bottom:16px; }
    .profile-meta-item  { display:flex; align-items:center; gap:6px; font-size:13px; color:rgba(255,255,255,0.6); }
    .profile-meta-item i { color: var(--lime); font-size:12px; width:14px; text-align:center; }
    .profile-hero-stats { display:flex; gap:28px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.1); }
    .profile-stat { text-align:center; }
    .profile-stat-num   { font-size:24px; font-weight:700; color:var(--lime); font-family:'Space Mono',monospace; line-height:1; }
    .profile-stat-label { font-size:11px; color:rgba(255,255,255,0.45); margin-top:3px; text-transform:uppercase; letter-spacing:0.5px; }

    /* ── Section label ── */
    .profile-section-label {
      font-size:14px; font-weight:800; color:var(--navy);
      margin-bottom:14px; display:flex; align-items:center; gap:8px;
    }
    .profile-section-label::before {
      content:''; display:block; width:4px; height:16px;
      background:var(--lime); border-radius:3px; flex-shrink:0;
    }
    .profile-section-label .count-tag {
      margin-left:auto; font-size:11px; font-weight:500;
      color:var(--text-muted); font-family:'Space Mono',monospace;
    }

    /* ── About panel ── */
    .about-row {
      display:flex; align-items:center; gap:12px;
      padding: 11px 0; border-bottom:1px solid var(--border);
      font-size:13.5px; color:var(--text);
    }
    .about-row:last-child { border-bottom:none; padding-bottom:0; }
    .about-icon {
      width:34px; height:34px; border-radius:8px;
      background:rgba(5,22,80,0.06); flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
    }
    .about-icon i { font-size:14px; color:var(--navy); }
    .about-label { font-size:11px; color:var(--text-muted); margin-bottom:1px; }
    .about-value { font-size:13.5px; font-weight:600; color:var(--text); }

    /* ── Photo grid ── */
    .photo-grid {
      display:grid; grid-template-columns:repeat(3,1fr); gap:5px;
      border-radius:10px; overflow:hidden;
    }
    .photo-thumb {
      position:relative; aspect-ratio:1; overflow:hidden;
      background:#f0f2fa; cursor:pointer;
    }
    .photo-thumb img {
      width:100%; height:100%; object-fit:cover; display:block;
      transition:transform 0.22s ease;
    }
    .photo-thumb:hover img { transform:scale(1.07); }
    .photo-thumb .photo-overlay {
      position:absolute; inset:0; background:rgba(5,22,80,0.5);
      display:flex; align-items:center; justify-content:center;
      color:#fff; font-size:20px; font-weight:700;
      opacity:0; transition:opacity 0.2s;
    }
    .photo-thumb:hover .photo-overlay,
    .photo-thumb.has-more .photo-overlay { opacity:1; }

    /* ── Files ── */
    .file-row {
      display:flex; align-items:center; gap:10px; padding:10px 12px;
      border:1px solid var(--border); border-radius:9px;
      text-decoration:none; color:var(--text);
      transition:background 0.15s;
    }
    .file-row:hover { background:rgba(5,22,80,0.04); }
    .file-icon-box {
      width:34px; height:34px; border-radius:7px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
    }
    .file-icon-box i { font-size:14px; }
    .file-name { font-size:12px; font-weight:600; color:var(--navy); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .file-ext  { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-top:1px; }

    /* ── Feed posts ── */
    .profile-feed-label {
      font-size:17px; font-weight:700; color:var(--navy);
      margin-bottom:16px; display:flex; align-items:center; gap:10px;
    }
    .profile-feed-label::before { content:''; display:block; width:4px; height:18px; background:var(--lime); border-radius:3px; }

    .post-media-grid { display:grid; gap:4px; margin:10px 0; border-radius:8px; overflow:hidden; }
    .post-media-grid.count-1 { grid-template-columns:1fr; }
    .post-media-grid.count-2 { grid-template-columns:1fr 1fr; }
    .post-media-grid.count-3 { grid-template-columns:1fr 1fr; }
    .post-media-grid.count-3 .media-cell:first-child { grid-column:1/-1; }
    .post-media-grid.count-4 { grid-template-columns:1fr 1fr; }
    .media-cell { position:relative; background:rgba(5,22,80,0.06); cursor:pointer; }
    .media-cell img { width:100%; height:200px; object-fit:cover; display:block; }
    .media-cell.single img { height:320px; }
    .media-cell .file-link { display:flex; align-items:center; gap:10px; padding:14px 16px; text-decoration:none; color:var(--navy); font-size:13px; font-weight:600; }
    .media-cell .more-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; font-weight:700; pointer-events:none; }

    /* ── Reaction bar & picker ── */
    .reaction-bar { display:flex; align-items:center; gap:14px; flex-wrap:wrap; font-size:12.5px; color:var(--text-muted); padding-bottom:10px; border-bottom:1px solid var(--border); }
    .reaction-bar-item { display:inline-flex; align-items:center; gap:4px; }
    .reaction-btn-wrap { position:relative; display:inline-flex; }
    .reaction-picker { position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%) translateY(6px); background:var(--surface); border:1px solid var(--border); border-radius:999px; padding:6px 10px; display:flex; gap:2px; box-shadow:0 8px 24px rgba(5,22,80,0.18); opacity:0; pointer-events:none; transition:opacity 0.15s,transform 0.15s; z-index:10; white-space:nowrap; }
    .reaction-picker.open { opacity:1; pointer-events:all; transform:translateX(-50%) translateY(0); }
    .reaction-option { display:flex; flex-direction:column; align-items:center; gap:2px; cursor:pointer; padding:4px 6px; border-radius:8px; transition:transform 0.15s,background 0.15s; border:none; background:none; font-family:inherit; }
    .reaction-option:hover { transform:scale(1.35) translateY(-4px); background:rgba(5,22,80,0.04); }
    .reaction-option .r-emoji { font-size:22px; line-height:1; }
    .reaction-option .r-label { font-size:9px; font-weight:700; color:var(--text-muted); }
    .community-action { background:none; border:none; cursor:pointer; font-family:inherit; font-size:13px; display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:6px; color:var(--text-muted); text-decoration:none; transition:background 0.15s; font-weight:600; }
    .community-action:hover { background:rgba(5,22,80,0.06); color:var(--navy); }
    .community-action.reacted { font-weight:700; }

    /* ── Comments ── */
    .comment-section { border-top:1px solid var(--border); margin-top:8px; display:none; }
    .comment-section.open { display:block; }
    .comment-avatar { width:32px; height:32px; border-radius:50%; overflow:hidden; flex-shrink:0; border:2px solid var(--lime); }
    .comment-avatar img { width:100%; height:100%; object-fit:cover; }

    /* ── Carousel modal ── */
    .carousel-overlay {
      position:fixed; inset:0; z-index:3000;
      background:rgba(0,0,0,0.92);
      display:none; align-items:center; justify-content:center;
      padding:20px;
    }
    .carousel-overlay.open { display:flex; }
    .carousel-box {
      position:relative; width:100%; max-width:860px;
      display:flex; flex-direction:column; align-items:center;
    }
    .carousel-img-wrap {
      width:100%; max-height:72vh;
      display:flex; align-items:center; justify-content:center;
      border-radius:10px; overflow:hidden; background:#111;
      position:relative;
    }
    .carousel-img-wrap img {
      max-width:100%; max-height:72vh;
      object-fit:contain; display:block;
      border-radius:10px;
      transition:opacity 0.2s;
    }
    .carousel-close {
      position:fixed; top:20px; right:24px;
      background:rgba(255,255,255,0.12); border:none; color:#fff;
      width:42px; height:42px; border-radius:50%; font-size:18px;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:background 0.2s;
    }
    .carousel-close:hover { background:rgba(255,255,255,0.24); }
    .carousel-nav {
      position:absolute; top:50%; transform:translateY(-50%);
      background:rgba(255,255,255,0.14); border:none; color:#fff;
      width:46px; height:46px; border-radius:50%; font-size:18px;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:background 0.2s; z-index:1;
    }
    .carousel-nav:hover { background:rgba(255,255,255,0.28); }
    .carousel-prev { left:-58px; }
    .carousel-next { right:-58px; }
    .carousel-counter {
      margin-top:14px; font-size:13px; color:rgba(255,255,255,0.5);
      font-family:'Space Mono',monospace;
    }
    .carousel-thumbs {
      display:flex; gap:6px; margin-top:12px;
      max-width:100%; overflow-x:auto; padding:4px 0;
      scrollbar-width:thin; scrollbar-color:rgba(255,255,255,0.2) transparent;
    }
    .carousel-thumb {
      width:54px; height:54px; border-radius:6px; object-fit:cover;
      opacity:0.5; cursor:pointer; border:2px solid transparent;
      transition:opacity 0.2s,border-color 0.2s; flex-shrink:0;
    }
    .carousel-thumb.active { opacity:1; border-color:var(--lime); }

    /* ── Own-profile note ── */
    .own-note {
      display:inline-flex; align-items:center; gap:7px;
      background:rgba(204,255,0,0.15); border:1px solid rgba(204,255,0,0.4);
      border-radius:6px; padding:6px 14px; font-size:13px;
      color:var(--navy); font-weight:600; margin-bottom:14px;
    }

    /* ── Logout modal ── */
    .logout-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,0.65); display:none; align-items:center; justify-content:center; }
    .logout-overlay.open { display:flex; }
    .logout-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid var(--lime); box-shadow:0 16px 48px rgba(5,22,80,0.28); }
    .logout-icon { width:56px; height:56px; border-radius:50%; background:var(--navy); color:var(--lime); display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-box h3 { font-size:20px; font-weight:700; color:var(--navy); margin-bottom:8px; }
    .logout-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-btns   { display:flex; gap:10px; justify-content:center; }
    .btn-logout-ok  { background:var(--navy); color:var(--lime); border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-no  { background:transparent; color:var(--navy); border:1px solid rgba(5,22,80,0.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    @media(max-width:1000px) {
      .profile-layout { grid-template-columns:1fr; }
      .profile-right  { position:static; }
      .carousel-prev { left:-50px; }
      .carousel-next { right:-50px; }
    }
  </style>
</head>
<body>

<!-- Carousel modal -->
<div class="carousel-overlay" id="carouselOverlay">
  <button class="carousel-close" onclick="closeCarousel()"><i class="fa-solid fa-xmark"></i></button>
  <div class="carousel-box">
    <div class="carousel-img-wrap" style="position:relative;">
      <button class="carousel-nav carousel-prev" onclick="carouselStep(-1)"><i class="fa-solid fa-chevron-left"></i></button>
      <img src="" id="carouselImg" alt="Photo" />
      <button class="carousel-nav carousel-next" onclick="carouselStep(1)"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
    <div class="carousel-counter" id="carouselCounter">1 / 1</div>
    <div class="carousel-thumbs" id="carouselThumbs"></div>
  </div>
</div>

<!-- Logout modal -->
<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the home page.</p>
    <div class="logout-btns">
      <button type="button" class="btn-logout-no" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-logout-ok"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="container">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>BarangayKonek</h2><span>Resident</span></div>
    <div class="profile profile--compact">
      <div class="avatar-ring"><img src="<?= $myPic ?>" alt="Me"/></div>
      <div class="profile-meta">
        <h3><?= $myFullName ?></h3>
        <p>City of Imus, Alapan 1-A</p>
        <span class="portal-badge">Resident Portal</span>
      </div>
    </div>
    <nav class="menu">
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

  <main class="content">
    <div class="content-inner" style="max-width:1200px;">

      <div style="margin-bottom:18px;">
        <a href="residentcommunity.php" style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--navy);text-decoration:none;opacity:0.65;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.65">
          <i class="fa-solid fa-arrow-left"></i> Back to Community
        </a>
      </div>

      <?php if ($viewUserId === $myUserId): ?>
      <div class="own-note">
        <i class="fa-solid fa-eye"></i> You are viewing your own profile as others see it.
      </div>
      <?php endif; ?>

      <!-- Hero card full-width -->
      <div class="profile-hero" style="margin-bottom:20px;">
        <div class="profile-hero-banner"></div>
        <div class="profile-hero-body">
          <img src="<?= $profPic ?>" class="profile-hero-avatar" alt="<?= $profFullName ?>"/>
          <div class="profile-hero-info">
            <div class="profile-hero-name"><?= $profFullName ?></div>
            <div class="profile-hero-uname">@<?= $profUsername ?></div>
            <div class="profile-hero-meta">
              <span class="profile-meta-item"><i class="fa-solid fa-location-dot"></i>Barangay Alapan I-A, Imus, Cavite</span>
              <span class="profile-meta-item"><i class="fa-solid fa-calendar-days"></i>Joined <?= $profJoined ?></span>
              <span class="profile-meta-item">
                <i class="fa-solid fa-<?= $profGender === 'male' ? 'mars' : ($profGender === 'female' ? 'venus' : 'genderless') ?>"></i>
                <?= ucfirst($profGender ?: 'Resident') ?>
              </span>
            </div>
            <div class="profile-hero-stats">
              <div class="profile-stat">
                <div class="profile-stat-num"><?= $postCount ?></div>
                <div class="profile-stat-label">Posts</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-num"><?= $totalReacts ?></div>
                <div class="profile-stat-label">Reactions</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-num"><?= $totalComments ?></div>
                <div class="profile-stat-label">Comments</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-num"><?= count($allPhotos) ?></div>
                <div class="profile-stat-label">Photos</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Two-column layout below hero -->
      <div class="profile-layout">

        <!-- LEFT: feed -->
        <div>
          <div class="profile-feed-label">
            <i class="fa-solid fa-newspaper" style="color:var(--navy);font-size:15px;"></i>
            Posts by <?= $profFirstName ?>
          </div>

          <?php if (empty($posts)): ?>
          <div class="panel" style="padding:40px;text-align:center;color:var(--text-muted);">
            <i class="fa-solid fa-inbox" style="font-size:32px;display:block;margin-bottom:12px;opacity:0.35;"></i>
            <?= $profFirstName ?> hasn't posted anything yet.
          </div>
          <?php endif; ?>

          <?php foreach ($posts as $post):
            $postId       = (int)$post['POST_ID'];
            $reactCount   = (int)$post['REACT_COUNT'];
            $commentCount = (int)$post['COMMENT_COUNT'];
            $myReaction   = $post['MY_REACTION'] ? rtrim($post['MY_REACTION']) : null;
            $postTime     = $post['CREATED_AT']->format('M d, Y \• g:i A');
            $myMeta       = $myReaction ? ($reactionMeta[$myReaction] ?? $reactionMeta['LIKE']) : null;

            $imgStmt = sqlsrv_query($conn,
                "SELECT IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = ? ORDER BY CREATED_AT ASC", [$postId]);
            $postImages = [];
            while ($ir = sqlsrv_fetch_array($imgStmt, SQLSRV_FETCH_ASSOC)) $postImages[] = htmlspecialchars($ir['IMAGE_PATH']);
            $imgCount = count($postImages);

            $imgOnlyPaths = array_filter($postImages, fn($p) => in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), $imageExts));
            $imgOnlyPaths = array_values($imgOnlyPaths);

            $commStmt = sqlsrv_query($conn,
                "SELECT C.BODY, R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE
                 FROM COMMENTS C JOIN REGISTRATION R ON C.USER_ID = R.USER_ID
                 WHERE C.POST_ID = ? ORDER BY C.CREATED_AT ASC", [$postId]);
            $comments = [];
            while ($cr = sqlsrv_fetch_array($commStmt, SQLSRV_FETCH_ASSOC)) $comments[] = $cr;

            $reactSumStmt = sqlsrv_query($conn,
                "SELECT REACTION_TYPE, COUNT(*) AS CNT FROM LIKES WHERE POST_ID = ? GROUP BY REACTION_TYPE ORDER BY CNT DESC", [$postId]);
            $reactionSummary = [];
            while ($rr = sqlsrv_fetch_array($reactSumStmt, SQLSRV_FETCH_ASSOC)) $reactionSummary[] = $rr;
          ?>
          <article class="panel community-stream-card" id="post-<?= $postId ?>" style="margin-bottom:16px;">
            <div class="community-stream-head">
              <div class="community-stream-user">
                <div class="community-stream-avatar resident">
                  <img src="<?= $profPic ?>" alt="<?= $profFullName ?>"/>
                </div>
                <div class="community-stream-meta">
                  <h3><?= $profFullName ?></h3>
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
              <?php foreach (array_slice($postImages, 0, 4) as $idx => $imgPath):
                $ext    = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
                $isImg  = in_array($ext, $imageExts);
                $single = ($imgCount === 1) ? 'single' : '';
                $startIdx = $idx < count($imgOnlyPaths) ? array_search($imgPath, $imgOnlyPaths) : 0;
              ?>
              <div class="media-cell <?= $single ?>"
                   <?= $isImg ? 'onclick="openCarousel('.json_encode($imgOnlyPaths).', '.($startIdx !== false ? $startIdx : 0).')"' : '' ?>>
                <?php if ($isImg): ?>
                <img src="<?= $imgPath ?>" alt="Post image"/>
                <?php else: ?>
                <a href="<?= $imgPath ?>" class="file-link" target="_blank">
                  <i class="fa-solid fa-file"></i><?= htmlspecialchars(basename($imgPath)) ?>
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
                $rMeta = $reactionMeta[rtrim($rs['REACTION_TYPE'])] ?? $reactionMeta['LIKE'];
              ?>
              <span class="reaction-bar-item">
                <span><?= $rMeta['emoji'] ?></span>
                <span><?= (int)$rs['CNT'] ?></span>
              </span>
              <?php endforeach; ?>
              <?php if ($reactCount === 0): ?>
              <span style="font-size:12px;">No reactions yet</span>
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
                  <input type="hidden" name="redirect" value="residentprofile.php?user_id=<?= $viewUserId ?>#post-<?= $postId ?>">
                  <input type="hidden" name="reaction_type" id="rt-<?= $postId ?>" value="<?= $myReaction ?? 'LIKE' ?>">
                  <button type="submit" class="community-action <?= $myMeta ? 'reacted' : '' ?>"
                    style="<?= $myMeta ? 'color:'.$myMeta['color'].';' : '' ?>">
                    <?= $myMeta ? $myMeta['emoji'].' '.$myMeta['label'] : '<i class="fa-regular fa-thumbs-up"></i> React' ?>
                  </button>
                </form>
                <div class="reaction-picker">
                  <?php foreach ($reactionMeta as $rKey => $rMeta): ?>
                  <button type="button" class="reaction-option" onclick="setReaction(<?= $postId ?>,'<?= $rKey ?>')" title="<?= $rMeta['label'] ?>">
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
                  $comAvatar = resolveAvatar($com['PROFILE_PICTURE'], $com['GENDER']);
                  $comName   = htmlspecialchars(rtrim($com['FIRST_NAME'])).' '.htmlspecialchars(rtrim($com['LAST_NAME']));
                ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                  <div class="comment-avatar"><img src="<?= $comAvatar ?>" alt="<?= $comName ?>"/></div>
                  <div style="background:#f5f6fa;border-radius:8px;padding:8px 12px;flex:1;">
                    <strong style="font-size:13px;color:var(--navy);display:block;"><?= $comName ?></strong>
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
                  <input type="hidden" name="redirect" value="residentprofile.php?user_id=<?= $viewUserId ?>#post-<?= $postId ?>">
                  <div class="comment-avatar"><img src="<?= $myPic ?>" alt="Me"/></div>
                  <input type="text" name="comment_body" placeholder="Write a comment…"
                    style="flex:1;border:1px solid var(--border);border-radius:20px;padding:8px 14px;font-size:13px;font-family:inherit;outline:none;" required/>
                  <button type="submit" style="background:var(--navy);color:var(--lime);border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">Send</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <!-- RIGHT: About + Photos + Files -->
        <div class="profile-right">

          <!-- About -->
          <div class="panel" style="padding:20px;">
            <div class="profile-section-label">
              <i class="fa-solid fa-circle-info" style="color:var(--navy);font-size:13px;"></i>
              About
            </div>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div>
                <div class="about-label">Location</div>
                <div class="about-value">Barangay Alapan I-A, Imus, Cavite</div>
              </div>
            </div>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-at"></i></div>
              <div>
                <div class="about-label">Username</div>
                <div class="about-value">@<?= $profUsername ?></div>
              </div>
            </div>
            <?php if ($profBirthdate): ?>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-cake-candles"></i></div>
              <div>
                <div class="about-label">Birthday</div>
                <div class="about-value"><?= $profBirthdate ?></div>
              </div>
            </div>
            <?php endif; ?>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-<?= $profGender === 'male' ? 'mars' : ($profGender === 'female' ? 'venus' : 'genderless') ?>"></i></div>
              <div>
                <div class="about-label">Gender</div>
                <div class="about-value"><?= ucfirst($profGender ?: 'Prefer not to say') ?></div>
              </div>
            </div>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-calendar-plus"></i></div>
              <div>
                <div class="about-label">Joined</div>
                <div class="about-value"><?= $profJoined ?></div>
              </div>
            </div>
            <div class="about-row">
              <div class="about-icon"><i class="fa-solid fa-newspaper"></i></div>
              <div>
                <div class="about-label">Activity</div>
                <div class="about-value"><?= $postCount ?> posts · <?= $totalReacts ?> reactions · <?= $totalComments ?> comments</div>
              </div>
            </div>
          </div>

          <!-- Photos & Media -->
          <div class="panel" style="padding:20px;">
            <div class="profile-section-label">
              <i class="fa-solid fa-images" style="color:var(--navy);font-size:13px;"></i>
              Photos &amp; Media
              <span class="count-tag"><?= count($allPhotos) ?> photo<?= count($allPhotos) !== 1 ? 's' : '' ?></span>
            </div>

            <?php if (empty($allPhotos)): ?>
            <div style="text-align:center;padding:28px 0;color:var(--text-muted);font-size:13px;">
              <i class="fa-regular fa-image" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.3;"></i>
              No photos uploaded yet.
            </div>
            <?php else: ?>
            <div class="photo-grid">
              <?php foreach (array_slice($allPhotos, 0, 9) as $idx => $photo):
                $isLast = ($idx === 8 && count($allPhotos) > 9);
              ?>
              <div class="photo-thumb <?= $isLast ? 'has-more' : '' ?>"
                   onclick="openCarousel(<?= json_encode($allPhotos) ?>, <?= $idx ?>)">
                <img src="<?= $photo ?>" alt="Photo <?= $idx + 1 ?>"/>
                <div class="photo-overlay">
                  <?= $isLast ? '+' . (count($allPhotos) - 9) : '<i class="fa-solid fa-expand"></i>' ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($allPhotos) > 9): ?>
            <button type="button"
              onclick="openCarousel(<?= json_encode($allPhotos) ?>, 0)"
              style="display:block;width:100%;margin-top:10px;padding:9px;background:rgba(5,22,80,0.05);border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;font-weight:700;color:var(--navy);cursor:pointer;transition:background 0.15s;"
              onmouseover="this.style.background='rgba(5,22,80,0.1)'" onmouseout="this.style.background='rgba(5,22,80,0.05)'">
              <i class="fa-solid fa-images" style="margin-right:6px;"></i>
              View all <?= count($allPhotos) ?> photos
            </button>
            <?php endif; ?>
            <?php endif; ?>
          </div>

          <!-- Shared Files (only if any) -->
          <?php if (!empty($allFiles)): ?>
          <div class="panel" style="padding:20px;">
            <div class="profile-section-label">
              <i class="fa-solid fa-paperclip" style="color:var(--navy);font-size:13px;"></i>
              Shared Files
              <span class="count-tag"><?= count($allFiles) ?> file<?= count($allFiles) !== 1 ? 's' : '' ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:7px;">
              <?php foreach (array_slice($allFiles, 0, 5) as $file):
                $fname = basename($file);
                $fext  = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                $fcmap = ['PDF'=>'#e03030','DOC'=>'#1565c0','DOCX'=>'#1565c0','XLS'=>'#2e7d32','XLSX'=>'#2e7d32','TXT'=>'#555'];
                $fc    = $fcmap[$fext] ?? '#051650';
              ?>
              <a href="<?= $file ?>" target="_blank" class="file-row">
                <div class="file-icon-box" style="background:<?= $fc ?>18;">
                  <i class="fa-solid fa-file" style="color:<?= $fc ?>;"></i>
                </div>
                <div style="min-width:0;flex:1;">
                  <div class="file-name"><?= htmlspecialchars($fname) ?></div>
                  <div class="file-ext"><?= $fext ?> file</div>
                </div>
                <i class="fa-solid fa-arrow-down" style="font-size:11px;color:var(--text-muted);flex-shrink:0;"></i>
              </a>
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
/* ── Carousel ── */
let carouselPhotos = [];
let carouselIndex  = 0;

function openCarousel(photos, startIdx) {
  carouselPhotos = photos;
  carouselIndex  = startIdx;
  document.getElementById('carouselOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  buildThumbs();
  showSlide(carouselIndex);
}

function closeCarousel() {
  document.getElementById('carouselOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function buildThumbs() {
  const wrap = document.getElementById('carouselThumbs');
  wrap.innerHTML = '';
  carouselPhotos.forEach((src, i) => {
    const img    = document.createElement('img');
    img.src      = src;
    img.className = 'carousel-thumb' + (i === carouselIndex ? ' active' : '');
    img.onclick  = () => { carouselIndex = i; showSlide(i); };
    wrap.appendChild(img);
  });
}

function showSlide(idx) {
  carouselIndex = (idx + carouselPhotos.length) % carouselPhotos.length;
  document.getElementById('carouselImg').src       = carouselPhotos[carouselIndex];
  document.getElementById('carouselCounter').textContent = (carouselIndex + 1) + ' / ' + carouselPhotos.length;
  document.querySelectorAll('.carousel-thumb').forEach((t, i) => {
    t.classList.toggle('active', i === carouselIndex);
  });
  const thumb = document.querySelectorAll('.carousel-thumb')[carouselIndex];
  if (thumb) thumb.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
}

function carouselStep(dir) { showSlide(carouselIndex + dir); }

document.getElementById('carouselOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeCarousel();
});

document.addEventListener('keydown', function(e) {
  if (!document.getElementById('carouselOverlay').classList.contains('open')) return;
  if (e.key === 'ArrowRight') carouselStep(1);
  if (e.key === 'ArrowLeft')  carouselStep(-1);
  if (e.key === 'Escape')     closeCarousel();
});

/* ── Reaction picker hover ── */
const pickerTimers = {};

document.querySelectorAll('.reaction-btn-wrap').forEach(wrap => {
  const postId = wrap.closest('article')?.id?.replace('post-','');
  if (!postId) return;
  const trigger = wrap.querySelector('[type="submit"]');
  const picker  = wrap.querySelector('.reaction-picker');
  if (!trigger || !picker) return;
  const open  = () => { clearTimeout(pickerTimers[postId]); picker.classList.add('open'); };
  const close = () => { pickerTimers[postId] = setTimeout(() => picker.classList.remove('open'), 300); };
  trigger.addEventListener('mouseenter', open);
  trigger.addEventListener('mouseleave', close);
  picker.addEventListener('mouseenter',  () => clearTimeout(pickerTimers[postId]));
  picker.addEventListener('mouseleave',  close);
});

function setReaction(postId, type) {
  const picker = document.querySelector('#post-' + postId + ' .reaction-picker');
  if (picker) picker.classList.remove('open');
  document.getElementById('rt-' + postId).value = type;
  document.querySelector('#post-' + postId + ' .reaction-btn-wrap form [type="submit"]').click();
}

/* ── Comments ── */
function toggleComments(postId) {
  const s = document.getElementById('comments-' + postId);
  const b = document.getElementById('comment-btn-' + postId);
  const open = s.classList.contains('open');
  s.classList.toggle('open', !open);
  if (!open) s.querySelector('input[name="comment_body"]')?.focus();
}

const hash = window.location.hash;
if (hash && hash.startsWith('#post-')) {
  const target = document.querySelector(hash);
  if (target) setTimeout(() => {
    toggleComments(parseInt(hash.replace('#post-','')));
    target.scrollIntoView({ behavior:'smooth', block:'center' });
  }, 150);
}

/* ── Logout ── */
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', e => {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});
</script>
</body>
</html>