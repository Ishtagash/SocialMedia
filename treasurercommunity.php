<?php
session_start();
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'staff' ||
    strtolower(trim($_SESSION['position'] ?? '')) !== 'treasurer'
) {
    header("Location: login.php"); exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$meRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$firstName = $meRow ? htmlspecialchars(rtrim($meRow['FIRST_NAME'])) : 'Treasurer';
$lastName  = $meRow ? htmlspecialchars(rtrim($meRow['LAST_NAME']))  : '';
$fullName  = trim("$firstName $lastName");
$gender    = $meRow ? strtolower(rtrim($meRow['GENDER'] ?? '')) : '';

if ($meRow && !empty($meRow['PROFILE_PICTURE'])) {
    $profilePicture = htmlspecialchars($meRow['PROFILE_PICTURE']);
} elseif ($gender === 'male')   { $profilePicture = 'default/male.png'; }
elseif ($gender === 'female')   { $profilePicture = 'default/female.png'; }
else                             { $profilePicture = 'default/neutral.png'; }

$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

function resolveAvatar($profilePic, $gdr) {
    if (!empty($profilePic)) return htmlspecialchars($profilePic);
    $g = strtolower(trim($gdr ?? ''));
    if ($g === 'male')   return 'default/male.png';
    if ($g === 'female') return 'default/female.png';
    return 'default/neutral.png';
}

/* ── HANDLE ACTIONS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = rtrim($_POST['action']);

    if ($action === 'post') {
        $body     = trim($_POST['body'] ?? '');
        $hasFiles = !empty($_FILES['post_images']['name'][0]);
        if (!empty($body) || $hasFiles) {
            $insertPost = sqlsrv_query($conn,
                "INSERT INTO POSTS (USER_ID, BODY, CREATED_AT) VALUES (?, ?, GETDATE())",
                [$userId, $body]);
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
                                    [$newPostId, $savePath]);
                            }
                        }
                    }
                }
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'CREATE_POST', 'Treasurer created a community post', GETDATE())",
                    [$userId]);
            }
        }
        header("Location: treasurercommunity.php"); exit();
    }

    if ($action === 'read_notif' && isset($_POST['notif_id'])) {
        $notifId = (int)$_POST['notif_id'];
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?", [$notifId, $userId]);
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]); exit();
        }
        $refId  = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;
        $anchor = $refId > 0 ? '#post-' . $refId : '';
        header("Location: treasurercommunity.php" . $anchor); exit();
    }

    if ($action === 'get_post_modal' && isset($_POST['post_id'])) {
        $pid = (int)$_POST['post_id'];
        header('Content-Type: application/json');
        $pRow = sqlsrv_fetch_array(
            sqlsrv_query($conn,
                "SELECT P.POST_ID, P.BODY, P.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE, P.USER_ID AS POSTER_ID
                 FROM POSTS P JOIN REGISTRATION R ON P.USER_ID = R.USER_ID WHERE P.POST_ID = ?", [$pid]),
            SQLSRV_FETCH_ASSOC
        );
        if (!$pRow) { echo json_encode(['ok'=>false]); exit(); }
        $imgs = [];
        $iStmt = sqlsrv_query($conn, "SELECT IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = ? ORDER BY CREATED_AT ASC", [$pid]);
        while ($ir = sqlsrv_fetch_array($iStmt, SQLSRV_FETCH_ASSOC)) $imgs[] = $ir['IMAGE_PATH'];
        $coms = [];
        $cStmt = sqlsrv_query($conn,
            "SELECT C.BODY, C.CREATED_AT, R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE
             FROM COMMENTS C JOIN REGISTRATION R ON C.USER_ID = R.USER_ID
             WHERE C.POST_ID = ? ORDER BY C.CREATED_AT ASC", [$pid]);
        while ($cr = sqlsrv_fetch_array($cStmt, SQLSRV_FETCH_ASSOC)) {
            $coms[] = [
                'body'   => rtrim($cr['BODY']),
                'time'   => $cr['CREATED_AT']->format('M d, Y g:i A'),
                'name'   => htmlspecialchars(rtrim($cr['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($cr['LAST_NAME'])),
                'avatar' => resolveAvatar($cr['PROFILE_PICTURE'], $cr['GENDER']),
            ];
        }
        $reactSumStmt = sqlsrv_query($conn,
            "SELECT REACTION_TYPE, COUNT(*) AS CNT FROM LIKES WHERE POST_ID = ? GROUP BY REACTION_TYPE ORDER BY CNT DESC", [$pid]);
        $reactSum = [];
        while ($rr = sqlsrv_fetch_array($reactSumStmt, SQLSRV_FETCH_ASSOC)) $reactSum[] = ['type'=>rtrim($rr['REACTION_TYPE']),'cnt'=>(int)$rr['CNT']];
        $gdr    = strtolower(trim($pRow['GENDER'] ?? ''));
        $avatar = resolveAvatar($pRow['PROFILE_PICTURE'], $gdr);
        echo json_encode([
            'ok'       => true, 'post_id' => $pid,
            'name'     => htmlspecialchars(rtrim($pRow['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($pRow['LAST_NAME'])),
            'body'     => rtrim($pRow['BODY']),
            'time'     => $pRow['CREATED_AT']->format('M d, Y \• g:i A'),
            'avatar'   => $avatar, 'images' => $imgs, 'comments' => $coms, 'reactions' => $reactSum,
        ]); exit();
    }

    if ($action === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: treasurercommunity.php"); exit();
    }

    if ($action === 'flag_post' && !empty($_POST['post_id'])) {
        $pid = (int)$_POST['post_id'];
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'FLAG_POST', ?, GETDATE())",
            [$userId, "Treasurer flagged Post #$pid"]);
        header("Location: treasurercommunity.php"); exit();
    }

    if ($action === 'logout') {
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'LOGOUT', 'Treasurer logged out', GETDATE())",
            [$userId]);
        session_destroy();
        header("Location: login.php"); exit();
    }
}

/* ── PENDING BADGE ── */
$pendingRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS DR
         WHERE DR.STATUS = 'APPROVED'
           AND NOT EXISTS (SELECT 1 FROM PAYMENTS P WHERE P.REQUEST_ID = DR.REQUEST_ID AND P.PAYMENT_STATUS IN ('PAID','WAIVED'))"),
    SQLSRV_FETCH_ASSOC
);
$pendingPaymentCount = $pendingRow ? (int)$pendingRow['CNT'] : 0;

/* ── NOTIFICATIONS ── */
$unreadRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM NOTIFICATIONS WHERE USER_ID = ? AND IS_READ = 0", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$unreadCount = $unreadRow ? (int)$unreadRow['CNT'] : 0;

$notifStmt = sqlsrv_query($conn,
    "SELECT TOP 15 N.NOTIFICATION_ID, N.MESSAGE, N.TYPE, N.IS_READ, N.CREATED_AT, N.REFERENCE_ID
     FROM NOTIFICATIONS N WHERE N.USER_ID = ? ORDER BY N.CREATED_AT DESC", [$userId]);
$notifications = [];
while ($row = sqlsrv_fetch_array($notifStmt, SQLSRV_FETCH_ASSOC)) {
    $refId = $row['REFERENCE_ID'] ? (int)$row['REFERENCE_ID'] : 0;
    $postPreview = null;
    if ($refId > 0 && in_array(rtrim($row['TYPE']), ['LIKE','COMMENT'])) {
        $prevRow = sqlsrv_fetch_array(
            sqlsrv_query($conn,
                "SELECT P.BODY,
                    (SELECT COUNT(*) FROM LIKES L WHERE L.POST_ID = P.POST_ID) AS LIKE_COUNT,
                    (SELECT COUNT(*) FROM COMMENTS C WHERE C.POST_ID = P.POST_ID) AS COMMENT_COUNT,
                    (SELECT TOP 1 IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = P.POST_ID ORDER BY CREATED_AT ASC) AS THUMB
                 FROM POSTS P WHERE P.POST_ID = ?", [$refId]),
            SQLSRV_FETCH_ASSOC
        );
        if ($prevRow) $postPreview = $prevRow;
    }
    $row['POST_PREVIEW'] = $postPreview;
    $notifications[] = $row;
}

/* ── POSTS ── */
$postsStmt = sqlsrv_query($conn,
    "SELECT P.POST_ID, P.USER_ID AS POSTER_ID, P.BODY, P.CREATED_AT,
            R.FIRST_NAME, R.LAST_NAME, R.GENDER, R.PROFILE_PICTURE,
            (SELECT COUNT(*) FROM LIKES L WHERE L.POST_ID = P.POST_ID) AS REACT_COUNT,
            (SELECT COUNT(*) FROM COMMENTS C WHERE C.POST_ID = P.POST_ID) AS COMMENT_COUNT,
            (SELECT TOP 1 REACTION_TYPE FROM LIKES WHERE POST_ID = P.POST_ID AND USER_ID = ?) AS MY_REACTION
     FROM POSTS P JOIN REGISTRATION R ON P.USER_ID = R.USER_ID
     ORDER BY P.CREATED_AT DESC", [$userId]);
$posts = [];
while ($row = sqlsrv_fetch_array($postsStmt, SQLSRV_FETCH_ASSOC)) $posts[] = $row;

/* ── ANNOUNCEMENTS ── */
$annsStmt = sqlsrv_query($conn,
    "SELECT TOP 5 TITLE, BODY, CREATED_AT FROM ANNOUNCEMENTS WHERE IS_ACTIVE = 1 ORDER BY CREATED_AT DESC");
$announcements = [];
while ($row = sqlsrv_fetch_array($annsStmt, SQLSRV_FETCH_ASSOC)) $announcements[] = $row;

$imageExts    = ['jpg','jpeg','png','gif','webp','bmp'];
$reactionMeta = [
    'LIKE'  => ['icon'=>'fa-solid fa-thumbs-up',    'label'=>'Like',  'color'=>'#1877f2'],
    'LOVE'  => ['icon'=>'fa-solid fa-heart',         'label'=>'Love',  'color'=>'#f33e58'],
    'HAHA'  => ['icon'=>'fa-solid fa-face-laugh',    'label'=>'Haha',  'color'=>'#f7b125'],
    'WOW'   => ['icon'=>'fa-solid fa-face-surprise', 'label'=>'Wow',   'color'=>'#f7b125'],
    'SAD'   => ['icon'=>'fa-solid fa-face-sad-tear', 'label'=>'Sad',   'color'=>'#f7b125'],
    'ANGRY' => ['icon'=>'fa-solid fa-face-angry',    'label'=>'Angry', 'color'=>'#e9710f'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Community Feed — BarangayKonek Treasurer</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="base.css"/>
<link rel="stylesheet" href="resident.css"/>
<style>
.sidebar-divider{height:1px;background:rgba(255,255,255,0.08);margin:6px 14px;}
.community-main-header{position:sticky;top:16px;z-index:50;}
.community-widgets-column{position:sticky;top:84px;max-height:calc(100vh - 100px);overflow-y:auto;scrollbar-width:none;}
.community-widgets-column::-webkit-scrollbar{display:none;}

.reaction-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:12.5px;color:var(--text-muted);padding-bottom:10px;border-bottom:1px solid var(--border);}
.reaction-bar-item{display:inline-flex;align-items:center;gap:4px;}
.reaction-btn-wrap{position:relative;display:inline-flex;justify-content:center;}
.reaction-picker{position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%) translateY(4px);background:var(--surface);border:1px solid var(--border);border-radius:999px;padding:6px 10px;display:flex;gap:2px;box-shadow:0 8px 24px rgba(5,22,80,0.18);opacity:0;pointer-events:none;transition:opacity 0.15s,transform 0.15s;z-index:10000;white-space:nowrap;}
.reaction-picker.open{opacity:1;pointer-events:all;transform:translateX(-50%) translateY(0);}
.reaction-option{display:flex;flex-direction:column;align-items:center;gap:3px;cursor:pointer;padding:5px 7px;border-radius:8px;transition:transform 0.15s,background 0.15s;border:none;background:none;font-family:inherit;}
.reaction-option:hover{transform:scale(1.35) translateY(-4px);background:rgba(5,22,80,0.04);}
.reaction-option .r-icon{font-size:20px;line-height:1;}
.reaction-option .r-label{font-size:9px;font-weight:700;color:var(--text-muted);}

.lightbox-overlay{position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.88);display:none;align-items:center;justify-content:center;padding:16px;}
.lightbox-overlay.open{display:flex;}
.lightbox-overlay img{max-width:90vw;max-height:90vh;border-radius:6px;object-fit:contain;box-shadow:0 8px 48px rgba(0,0,0,0.6);}
.lightbox-close{position:absolute;top:16px;right:20px;background:rgba(255,255,255,0.12);border:none;color:#fff;font-size:22px;width:40px;height:40px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;}
.lightbox-close:hover{background:rgba(255,255,255,0.24);}
.lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.12);border:none;color:#fff;font-size:18px;width:44px;height:44px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;}
.lightbox-nav:hover{background:rgba(255,255,255,0.24);}
.lightbox-prev{left:16px;}.lightbox-next{right:16px;}
.lightbox-counter{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.7);font-size:13px;font-weight:600;background:rgba(0,0,0,0.4);padding:4px 14px;border-radius:999px;}

.post-modal-overlay{position:fixed;inset:0;z-index:9000;background:rgba(5,22,80,0.65);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:24px;}
.post-modal-overlay.open{display:flex;}
.post-modal-box{background:#fff;border-radius:16px;max-width:600px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 16px 60px rgba(5,22,80,0.28);border-top:4px solid #ccff00;}
.post-modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eee;position:sticky;top:0;background:#fff;z-index:2;border-radius:16px 16px 0 0;}
.post-modal-header h3{font-size:15px;font-weight:700;color:#051650;}
.post-modal-close{background:rgba(5,22,80,0.06);border:none;color:#555;font-size:16px;width:34px;height:34px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;}
.post-modal-close:hover{background:rgba(5,22,80,0.12);color:#051650;}
.post-modal-body{padding:20px;}
.media-cell img{cursor:pointer;transition:opacity 0.15s;}.media-cell img:hover{opacity:0.9;}

.post-highlight{animation:highlightPost 2.5s ease;}
@keyframes highlightPost{0%{box-shadow:0 0 0 3px #ccff00;}100%{box-shadow:none;}}
.search-hidden{display:none !important;}
.community-action{background:none;border:none;cursor:pointer;font-family:inherit;font-size:13px;display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:6px;color:var(--text-muted);text-decoration:none;transition:background 0.15s;font-weight:600;}
.community-action:hover{background:rgba(5,22,80,0.06);color:var(--navy);}
.comment-section{border-top:1px solid var(--border);margin-top:8px;display:none;}
.comment-section.open{display:block;}
.comment-avatar{width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid #ccff00;}
.comment-avatar img{width:100%;height:100%;object-fit:cover;}
.poster-link{color:var(--text);text-decoration:none;font-weight:700;font-size:15px;}
.poster-link:hover{color:var(--navy);text-decoration:underline;}

.logout-confirm-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,0.65);display:none;align-items:center;justify-content:center;}
.logout-confirm-overlay.open{display:flex;}
.logout-confirm-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid #ccff00;box-shadow:0 16px 48px rgba(5,22,80,0.28);}
.logout-confirm-icon{width:56px;height:56px;border-radius:50%;background:#051650;color:#ccff00;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px;}
.logout-confirm-box h3{font-size:20px;font-weight:700;color:#051650;margin-bottom:8px;}
.logout-confirm-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6;}
.logout-confirm-btns{display:flex;gap:10px;justify-content:center;}
.btn-logout-confirm{background:#051650;color:#ccff00;border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
.btn-logout-cancel{background:transparent;color:#051650;border:1px solid rgba(5,22,80,0.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}

/* FLAG BADGE for treasurer */
.tc-flag-tag{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;background:#fef2f2;color:#991b1b;font-size:11px;font-weight:800;border:1px solid #fca5a5;cursor:pointer;transition:background 0.15s;}
.tc-flag-tag:hover{background:#fee2e2;}

/* TOPBAR badge */
.tc-topbar-badge{position:absolute;top:-6px;right:-5px;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:#ccff00;color:#051650;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
.tc-bell-icon-wrap{position:relative;display:inline-flex;}
</style>
</head>
<body>

<div class="logout-confirm-overlay" id="logoutModal">
  <div class="logout-confirm-box">
    <div class="logout-confirm-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-confirm-btns">
      <button type="button" class="btn-logout-cancel" onclick="closeLogout()">Cancel</button>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="logout"/>
        <button type="submit" class="btn-logout-confirm"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>

<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightboxOnBg(event)">
  <button class="lightbox-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)"><i class="fa-solid fa-chevron-left"></i></button>
  <img id="lightboxImg" src="" alt="Photo"/>
  <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>

<div class="post-modal-overlay" id="postModalOverlay" onclick="closePostModalOnBg(event)">
  <div class="post-modal-box" id="postModalBox">
    <div class="post-modal-header">
      <h3><i class="fa-solid fa-comment-dots" style="color:#ccff00;margin-right:8px;"></i>Post</h3>
      <div style="display:flex;align-items:center;gap:8px;">
        <a id="postModalGotoLink" href="#" onclick="goToPostInFeed(event)"
          style="display:none;font-size:12px;font-weight:700;color:#051650;text-decoration:none;background:rgba(204,255,0,0.18);padding:5px 12px;border-radius:20px;white-space:nowrap;align-items:center;gap:6px;">
          <i class="fa-solid fa-arrow-right"></i><span>Go to post in feed</span>
        </a>
        <button class="post-modal-close" onclick="closePostModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>
    <div class="post-modal-body" id="postModalBody">
      <div id="postModalLoading" style="text-align:center;padding:40px 20px;color:#888;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;margin-bottom:12px;display:block;"></i>
        Loading post...
      </div>
    </div>
    <div id="postModalCommentForm" style="display:none;padding:12px 20px 16px;border-top:1px solid #eee;">
      <div style="display:flex;gap:8px;align-items:center;">
        <div class="comment-avatar"><img src="<?= $profilePicture ?>" alt="<?= $fullName ?>"/></div>
        <input type="text" id="modalCommentInput" placeholder="Write a comment..."
          style="flex:1;border:1px solid #ddd;border-radius:20px;padding:8px 14px;font-size:13px;font-family:inherit;outline:none;"
          onkeydown="if(event.key==='Enter'){event.preventDefault();submitModalComment();}"/>
        <button type="button" onclick="submitModalComment()"
          style="background:#051650;color:#ccff00;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;"
          id="modalCommentSendBtn">Send</button>
      </div>
      <div id="modalCommentError" style="display:none;font-size:12px;color:#e03030;margin-top:6px;padding-left:44px;"></div>
    </div>
  </div>
</div>

<!-- COMPOSE MODAL -->
<div class="modal-overlay" id="composeModalOverlay" onclick="closeComposeModalOnBg(event)" style="position:fixed;inset:0;z-index:8000;background:rgba(5,22,80,0.6);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:16px;max-width:540px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 16px 60px rgba(5,22,80,0.28);border-top:4px solid #ccff00;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eee;position:sticky;top:0;background:#fff;z-index:2;border-radius:16px 16px 0 0;">
      <h3 style="font-size:15px;font-weight:700;color:#051650;margin:0;"><i class="fa-solid fa-pen-to-square" style="color:#ccff00;margin-right:8px;"></i>Create Post</h3>
      <button onclick="closeComposeModal()" style="background:rgba(5,22,80,0.06);border:none;color:#555;font-size:16px;width:34px;height:34px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="padding:20px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <img src="<?= $profilePicture ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #ccff00;" alt="<?= $fullName ?>"/>
        <div><strong style="font-size:15px;color:#051650;"><?= $fullName ?></strong><p style="font-size:12px;color:#888;margin:0;">Posting as Treasurer</p></div>
      </div>
      <form method="POST" enctype="multipart/form-data" id="composeForm">
        <input type="hidden" name="action" value="post"/>
        <textarea name="body" id="composerTextarea" rows="5"
          style="width:100%;border:1px solid #ddd;border-radius:12px;padding:12px 14px;font-size:15px;font-family:inherit;resize:vertical;outline:none;color:#333;"
          placeholder="Share an update or financial notice with the community..."></textarea>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="button" onclick="triggerFile('image')"
            style="background:rgba(5,22,80,0.06);border:none;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background 0.15s;"
            title="Add image"><i class="fa-regular fa-image" style="color:#1877f2;"></i></button>
        </div>
        <input type="file" name="post_images[]" id="singleFileInput" style="display:none;" multiple accept="image/*" onchange="handleFileSelect(this)"/>
        <div id="mediaPreviewWrap" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
        <button type="submit" id="composeSubmitBtn"
          style="width:100%;margin-top:12px;background:#051650;color:#ccff00;border:none;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background 0.2s;"
          onmouseover="this.style.background='#0a2470'" onmouseout="this.style.background='#051650'">
          <i class="fa-solid fa-paper-plane" style="margin-right:8px;"></i>Post
        </button>
      </form>
    </div>
  </div>
</div>

<div class="container">
  <aside class="sidebar resident-community-sidebar">
    <div class="sidebar-brand"><h2>BarangayKonek</h2><span>Treasurer</span></div>
    <div class="profile profile--compact">
      <div class="avatar-ring"><img src="<?= $profilePicture ?>" alt="Treasurer Photo"/></div>
      <div class="profile-meta">
        <h3><?= $fullName ?></h3>
        <p>City of Imus, Alapan 1-B</p>
        <span class="portal-badge">Treasurer Portal</span>
      </div>
    </div>
    <nav class="menu menu--community">
      <a href="treasurerdashboard.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a>
      <a href="treasurertransactions.php"><i class="fa-solid fa-coins nav-icon"></i><span>Transactions</span>
        <?php if ($pendingPaymentCount > 0): ?><span style="margin-left:auto;background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);min-width:20px;height:20px;padding:0 5px;border-radius:999px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;"><?= $pendingPaymentCount ?></span><?php endif; ?>
      </a>
      <a href="treasurerhistory.php"><i class="fa-solid fa-clock-rotate-left nav-icon"></i><span>History</span></a>
      <a href="treasurercommunity.php" class="active"><i class="fa-solid fa-users nav-icon"></i><span>Community</span></a>
    </nav>
    <div class="sidebar-divider"></div>
    <nav class="menu">
      <a href="treasurerprofile.php"><i class="fa-solid fa-gear nav-icon"></i><span>Settings</span></a>
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
          <input type="text" id="feedSearch" placeholder="Search posts, residents, or announcements..."/>
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
                <form method="POST" action="treasurercommunity.php" style="margin:0;">
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
                $iconMap  = [
                    'LIKE'=>'fa-thumbs-up','COMMENT'=>'fa-comment',
                    'ANNOUNCEMENT'=>'fa-bullhorn',
                    'PAYMENT_RECEIVED'=>'fa-coins','PAYMENT_WAIVED'=>'fa-ban',
                    'NEW_REQUEST'=>'fa-file-circle-plus','DOCUMENT_APPROVED'=>'fa-file-circle-check',
                    'FLAG_POST'=>'fa-flag',
                ];
                $icon    = $iconMap[$typeKey] ?? 'fa-bell';
                $timeAgo = $notif['CREATED_AT']->format('M d, g:i A');
                $preview = $notif['POST_PREVIEW'];
              ?>
              <button type="button" class="notif-item <?= $isUnread ? 'unread' : '' ?>"
                onclick="handleNotifClick(<?= $notifId ?>, <?= $refId ?>, '<?= $typeKey ?>', this)">
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
                  <?php if ($preview['THUMB']): ?><img src="<?= htmlspecialchars($preview['THUMB']) ?>" class="notif-post-preview-thumb" alt="Post"/><?php endif; ?>
                  <div class="notif-post-preview-info">
                    <div class="notif-post-preview-body"><?= $preview['BODY'] ? htmlspecialchars(mb_strimwidth(rtrim($preview['BODY']),0,80,'...')) : '(No text)' ?></div>
                    <div class="notif-post-preview-stats">
                      <span><i class="fa-solid fa-thumbs-up"></i> <?= (int)$preview['LIKE_COUNT'] ?></span>
                      <span><i class="fa-solid fa-comment"></i> <?= (int)$preview['COMMENT_COUNT'] ?></span>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </button>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="community-header-user">
            <span class="community-header-user-name"><?= $fullName ?></span>
            <div class="community-header-user-avatar">
              <img src="<?= $profilePicture ?>" alt="Treasurer Photo"/>
            </div>
          </div>
        </div>
      </header>

      <div class="community-board-layout">
        <section class="community-feed-column" id="feedColumn">

          <div class="community-composer-card panel">
            <div class="community-composer-top" onclick="openComposeModal()" style="cursor:pointer;">
              <img src="<?= $profilePicture ?>" alt="Treasurer" class="community-composer-avatar"/>
              <div style="flex:1;background:#f0f3f9;border-radius:999px;padding:10px 18px;font-size:14px;color:#93a0bb;border:1px solid #e4e8f0;">
                Share a financial update or announcement...
              </div>
            </div>
            <div class="community-composer-bottom" style="border-top:1px solid #eee;margin-top:12px;padding-top:10px;">
              <div class="community-composer-tools">
                <button type="button" class="composer-tool-btn" onclick="openComposeModal('image')" title="Add image"><i class="fa-regular fa-image"></i></button>
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
            $isOwnPost    = ($posterId === $userId);

            $imgStmt = sqlsrv_query($conn, "SELECT IMAGE_PATH FROM POST_IMAGES WHERE POST_ID = ? ORDER BY CREATED_AT ASC", [$postId]);
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
                   data-body="<?= strtolower(htmlspecialchars(rtrim($post['BODY']))) ?>">

            <div class="community-stream-head">
              <div class="community-stream-user">
                <div class="community-stream-avatar resident">
                  <img src="<?= $postAvatar ?>" alt="<?= $postName ?>"/>
                </div>
                <div class="community-stream-meta">
                  <span class="poster-link"><?= $postName ?></span>
                  <p style="cursor:pointer;" onclick="openPostModal(<?= $postId ?>)">
                    <?= $isOwnPost ? 'Treasurer (You)' : 'Resident Post' ?> • <?= $postTime ?>
                  </p>
                </div>
              </div>
              <!-- TREASURER FLAG PRIVILEGE -->
              <?php if (!$isOwnPost): ?>
              <form method="POST" action="treasurercommunity.php" style="margin:0;">
                <input type="hidden" name="action" value="flag_post"/>
                <input type="hidden" name="post_id" value="<?= $postId ?>"/>
                <button type="submit" class="tc-flag-tag" title="Flag this post" onclick="return confirm('Flag this post for review?')">
                  <i class="fa-solid fa-flag"></i> Flag
                </button>
              </form>
              <?php endif; ?>
            </div>

            <div class="community-stream-tagline">
              <span class="community-post-tag <?= $isOwnPost ? 'official' : 'resident' ?>">
                <?= $isOwnPost ? 'Official' : 'Resident' ?>
              </span>
            </div>

            <?php if (!empty(trim($post['BODY']))): ?>
            <div class="community-stream-body">
              <p><?= nl2br(htmlspecialchars(rtrim($post['BODY']))) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($imgCount > 0): ?>
            <div class="post-media-grid count-<?= min($imgCount,4) ?>">
              <?php
                $allImagePaths = [];
                foreach ($postImages as $pi) {
                    $ext2 = strtolower(pathinfo($pi['IMAGE_PATH'], PATHINFO_EXTENSION));
                    if (in_array($ext2, $imageExts)) $allImagePaths[] = $pi['IMAGE_PATH'];
                }
                $imgPathsJson = htmlspecialchars(json_encode($allImagePaths), ENT_QUOTES);
                $imgIdx = 0;
              ?>
              <?php foreach (array_slice($postImages,0,4) as $idx => $img):
                $ext   = strtolower(pathinfo($img['IMAGE_PATH'], PATHINFO_EXTENSION));
                $isImg = in_array($ext, $imageExts);
                $single= ($imgCount === 1) ? 'single' : '';
              ?>
              <div class="media-cell <?= $single ?>">
                <?php if ($isImg): ?>
                <img src="<?= htmlspecialchars($img['IMAGE_PATH']) ?>" alt="Post image"
                     onclick="openLightbox(<?= $imgPathsJson ?>, <?= $imgIdx ?>)" style="cursor:pointer;"/>
                <?php $imgIdx++; ?>
                <?php else: ?>
                <a href="<?= htmlspecialchars($img['IMAGE_PATH']) ?>" class="file-link" target="_blank">
                  <i class="fa-solid fa-file"></i><?= htmlspecialchars(basename($img['IMAGE_PATH'])) ?>
                </a>
                <?php endif; ?>
                <?php if ($idx === 3 && $imgCount > 4): ?><div class="more-overlay">+<?= $imgCount - 4 ?></div><?php endif; ?>
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
              <?php if ($reactCount === 0): ?><span style="color:var(--text-muted);font-size:12px;">No reactions yet</span><?php endif; ?>
              <span data-comment-toggle style="margin-left:auto;cursor:pointer;" onclick="toggleComments(<?= $postId ?>)">
                <i class="fa-regular fa-comment"></i>
                <?= $commentCount ?> <?= $commentCount === 1 ? 'comment' : 'comments' ?>
              </span>
            </div>

            <div style="display:flex;border-top:1px solid var(--border);margin-top:8px;">
              <div class="reaction-btn-wrap" id="rbw-<?= $postId ?>" style="flex:1;display:flex;justify-content:center;">
                <button type="button" id="react-btn-<?= $postId ?>"
                  data-post-id="<?= $postId ?>"
                  data-reaction="<?= htmlspecialchars($myReaction ?? '') ?>"
                  style="width:100%;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px 0;border:none;background:none;cursor:pointer;font-family:inherit;font-size:14px;font-weight:700;border-radius:0;<?= $myMeta ? 'color:'.$myMeta['color'].';' : 'color:#65676b;' ?>">
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
                  $comAvatar = resolveAvatar($com['PROFILE_PICTURE'], $com['GENDER']);
                  $comName   = htmlspecialchars(rtrim($com['FIRST_NAME'])) . ' ' . htmlspecialchars(rtrim($com['LAST_NAME']));
                ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                  <div class="comment-avatar"><img src="<?= $comAvatar ?>" alt="<?= $comName ?>"/></div>
                  <div style="background:#f5f6fa;border-radius:8px;padding:8px 12px;flex:1;">
                    <strong style="font-size:13px;color:#051650;display:block;"><?= $comName ?></strong>
                    <p style="font-size:13px;color:#555;margin:2px 0 0;"><?= nl2br(htmlspecialchars(rtrim($com['BODY']))) ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <div style="padding:10px 16px 14px;">
                <form method="POST" action="react.php" style="display:flex;gap:8px;align-items:center;">
                  <input type="hidden" name="action" value="comment"/>
                  <input type="hidden" name="post_id" value="<?= $postId ?>"/>
                  <input type="hidden" name="redirect" value="treasurercommunity.php#post-<?= $postId ?>"/>
                  <div class="comment-avatar"><img src="<?= $profilePicture ?>" alt="<?= $fullName ?>"/></div>
                  <input type="text" name="comment_body" placeholder="Write a comment…"
                    style="flex:1;border:1px solid #ddd;border-radius:20px;padding:8px 14px;font-size:13px;font-family:inherit;outline:none;" required/>
                  <button type="submit"
                    style="background:#051650;color:#ccff00;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">Send</button>
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
              style="background:#051650;color:#ccff00;border:none;border-radius:10px;padding:12px 32px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">
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
              <?php else: foreach ($announcements as $ann): ?>
              <div class="community-mini-post" data-ann-title="<?= strtolower(htmlspecialchars(rtrim($ann['TITLE']))) ?>">
                <span class="community-pinned-type official">Official</span>
                <h4><?= htmlspecialchars(rtrim($ann['TITLE'])) ?></h4>
                <p><?= htmlspecialchars(mb_strimwidth(rtrim($ann['BODY']),0,80,'...')) ?></p>
              </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
          <div class="panel community-widget-card">
            <div class="community-widget-head"><h3>Treasurer Tools</h3></div>
            <div class="community-channel-list">
              <a href="treasurertransactions.php" class="community-channel-item">
                <span class="community-channel-avatar official">TX</span><span>Pending Transactions</span>
                <?php if ($pendingPaymentCount > 0): ?><span style="margin-left:auto;background:#ccff00;color:#051650;border-radius:999px;padding:2px 8px;font-size:11px;font-weight:800;"><?= $pendingPaymentCount ?></span><?php endif; ?>
              </a>
              <a href="treasurerhistory.php" class="community-channel-item">
                <span class="community-channel-avatar event">HX</span><span>Transaction History</span>
              </a>
              <a href="treasurerfee.php" class="community-channel-item">
                <span class="community-channel-avatar alert">FE</span><span>Fee Records</span>
              </a>
            </div>
          </div>
          <div class="panel community-widget-card">
            <div class="community-widget-head"><h3>Barangay Contacts</h3></div>
            <div class="community-contact-list">
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Barangay Captain</span></div>
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Barangay Secretary</span></div>
              <div class="community-contact-item"><span class="community-contact-status"></span><span>Health Desk</span></div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </main>
</div>

<script>
/* COMPOSE MODAL */
function openComposeModal(trigger) {
  document.getElementById('composeModalOverlay').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  setTimeout(function() {
    var ta = document.getElementById('composerTextarea');
    if (ta) ta.focus();
    if (trigger === 'image') setTimeout(function(){ document.getElementById('singleFileInput').click(); }, 200);
  }, 80);
}
function closeComposeModal() {
  document.getElementById('composeModalOverlay').style.display = 'none';
  document.body.style.overflow = '';
}
function closeComposeModalOnBg(e) {
  if (e.target === document.getElementById('composeModalOverlay')) closeComposeModal();
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { closeComposeModal(); } });

/* NOTIFICATION BELL */
function toggleNotif() { document.getElementById('notifDropdown').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  var btn = document.getElementById('bellBtn');
  var dd  = document.getElementById('notifDropdown');
  if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});

function handleNotifClick(notifId, refId, typeKey, btn) {
  if (btn.classList.contains('unread')) {
    btn.classList.remove('unread');
    var dot = btn.querySelector('.notif-unread-dot');
    if (dot) dot.remove();
    var countEl = document.querySelector('.community-bell-count');
    if (countEl) {
      var cur = parseInt(countEl.textContent) - 1;
      if (cur <= 0) countEl.remove(); else countEl.textContent = cur;
    }
    var fd = new FormData();
    fd.append('action', 'read_notif');
    fd.append('notif_id', notifId);
    fd.append('ajax', '1');
    fetch('treasurercommunity.php', { method: 'POST', body: fd }).catch(function(){});
  }
  document.getElementById('notifDropdown').classList.remove('open');
  if (refId > 0 && (typeKey === 'LIKE' || typeKey === 'COMMENT')) {
    openPostModalFull(refId);
  }
}

/* COMMENTS */
function toggleComments(postId) {
  var section = document.getElementById('comments-' + postId);
  var btn     = document.getElementById('comment-btn-' + postId);
  var isOpen  = section.classList.contains('open');
  section.classList.toggle('open', !isOpen);
  if (!isOpen) section.querySelector('input[name="comment_body"]')?.focus();
}

/* REACTION PICKER */
var pickerTimers = {};
var reactionMeta = {
  LIKE: {icon:'fa-solid fa-thumbs-up',label:'Like',color:'#1877f2'},
  LOVE: {icon:'fa-solid fa-heart',label:'Love',color:'#f33e58'},
  HAHA: {icon:'fa-solid fa-face-laugh',label:'Haha',color:'#f7b125'},
  WOW:  {icon:'fa-solid fa-face-surprise',label:'Wow',color:'#f7b125'},
  SAD:  {icon:'fa-solid fa-face-sad-tear',label:'Sad',color:'#f7b125'},
  ANGRY:{icon:'fa-solid fa-face-angry',label:'Angry',color:'#e9710f'},
};

function openPicker(postId) {
  clearTimeout(pickerTimers[postId]);
  document.querySelectorAll('.reaction-picker.open').forEach(function(p) {
    if (p.dataset.postId !== String(postId)) p.classList.remove('open');
  });
  var picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
  if (picker) picker.classList.add('open');
}
function schedulClose(postId) {
  pickerTimers[postId] = setTimeout(function() {
    var picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
    if (picker) picker.classList.remove('open');
  }, 320);
}
function bindPickerEvents(wrap) {
  var postId  = wrap.closest('article')?.id?.replace('post-', '');
  if (!postId) return;
  var trigger = wrap.querySelector('#react-btn-' + postId);
  var picker  = wrap.querySelector('.reaction-picker');
  if (!trigger || !picker) return;
  trigger.addEventListener('mouseenter', function(){ openPicker(postId); });
  trigger.addEventListener('mouseleave', function(){ schedulClose(postId); });
  picker.addEventListener('mouseenter',  function(){ clearTimeout(pickerTimers[postId]); });
  picker.addEventListener('mouseleave',  function(){ schedulClose(postId); });
}
document.querySelectorAll('.reaction-btn-wrap').forEach(bindPickerEvents);

function setReaction(postId, type) {
  var picker = document.querySelector('.reaction-picker[data-post-id="' + postId + '"]');
  if (picker) picker.classList.remove('open');
  var btn = document.getElementById('react-btn-' + postId);
  var fd = new FormData();
  fd.append('post_id', postId);
  fd.append('reaction_type', type);
  fd.append('ajax', '1');
  fetch('react.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (!btn) return;
      if (data.removed) {
        btn.dataset.reaction = '';
        btn.style.color = '';
        btn.innerHTML = '<i class="fa-regular fa-thumbs-up"></i> React';
      } else {
        var meta = reactionMeta[type];
        btn.dataset.reaction = type;
        btn.style.color = meta.color;
        btn.innerHTML = '<i class="' + meta.icon + '"></i> ' + meta.label;
      }
      var bar = document.querySelector('#post-' + postId + ' .reaction-bar');
      if (bar && data.summary !== undefined) updateReactionBar(bar, data.summary, data.total);
    }).catch(function(){});
}

function updateReactionBar(bar, summary, total) {
  var commentSpan = bar.querySelector('[data-comment-toggle]');
  bar.innerHTML = '';
  if (total === 0) {
    var s = document.createElement('span');
    s.style.cssText = 'color:var(--text-muted);font-size:12px;';
    s.textContent = 'No reactions yet';
    bar.appendChild(s);
  } else {
    summary.forEach(function(r) {
      var meta = reactionMeta[r.type];
      if (!meta) return;
      var span = document.createElement('span');
      span.className = 'reaction-bar-item';
      span.innerHTML = '<i class="' + meta.icon + ' r-icon" style="color:' + meta.color + ';font-size:14px;"></i><span>' + r.cnt + '</span>';
      bar.appendChild(span);
    });
  }
  if (commentSpan) bar.appendChild(commentSpan);
}

/* POST MODAL */
var _currentModalPostId = null;
function openPostModal(postId) { openPostModalFull(postId); }
function closePostModal() {
  document.getElementById('postModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function closePostModalOnBg(e) {
  if (e.target === document.getElementById('postModalOverlay')) closePostModal();
}

function openPostModalFull(postId) {
  _currentModalPostId = postId;
  var overlay = document.getElementById('postModalOverlay');
  var body    = document.getElementById('postModalBody');
  var cfForm  = document.getElementById('postModalCommentForm');
  var gotoLink= document.getElementById('postModalGotoLink');
  body.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#888;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;margin-bottom:12px;display:block;"></i>Loading post...</div>';
  cfForm.style.display = 'none'; gotoLink.style.display = 'none';
  overlay.classList.add('open'); document.body.style.overflow = 'hidden';
  var fd = new FormData();
  fd.append('action', 'get_post_modal');
  fd.append('post_id', postId);
  fetch('treasurercommunity.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (!data.ok) { body.innerHTML = '<p style="padding:20px;color:#aaa;">Post not found.</p>'; return; }
      var imageExtsLocal = ['jpg','jpeg','png','gif','webp','bmp'];
      var html = '<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;">'
        + '<div style="width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid #ccff00;">'
        + '<img src="' + data.avatar + '" style="width:100%;height:100%;object-fit:cover;"/></div>'
        + '<div><strong style="font-size:15px;color:#051650;">' + escHtml(data.name) + '</strong>'
        + '<p style="font-size:12px;color:#888;margin-top:2px;">' + escHtml(data.time) + '</p></div></div>';
      if (data.body) html += '<div style="font-size:15px;line-height:1.7;color:#333;white-space:pre-wrap;border-top:1px solid #eee;padding-top:14px;margin-bottom:14px;">' + escHtml(data.body) + '</div>';
      if (data.images && data.images.length > 0) {
        html += '<div style="display:grid;gap:4px;border-radius:8px;overflow:hidden;margin-bottom:14px;grid-template-columns:' + (data.images.length===1?'1fr':'1fr 1fr') + ';">';
        var imgPaths = data.images.filter(function(p){ return imageExtsLocal.includes(p.split('.').pop().toLowerCase()); });
        var imgPathsJson = JSON.stringify(imgPaths);
        data.images.slice(0,4).forEach(function(src, idx) {
          var ext = src.split('.').pop().toLowerCase();
          var isImg = imageExtsLocal.includes(ext);
          html += '<div style="position:relative;background:#f0f2fa;">';
          if (isImg) html += '<img src="' + escHtml(src) + '" style="width:100%;height:180px;object-fit:cover;display:block;cursor:pointer;" onclick=\'openLightbox(' + imgPathsJson + ', ' + idx + ')\' />';
          else html += '<a href="' + escHtml(src) + '" target="_blank" style="display:flex;align-items:center;gap:10px;padding:14px;text-decoration:none;color:#051650;font-size:13px;font-weight:600;"><i class="fa-solid fa-file"></i>' + escHtml(src.split('/').pop()) + '</a>';
          html += '</div>';
        });
        html += '</div>';
      }
      if (data.reactions && data.reactions.length > 0) {
        html += '<div style="display:flex;gap:12px;flex-wrap:wrap;padding:8px 0;border-top:1px solid #eee;border-bottom:1px solid #eee;margin-bottom:12px;">';
        var iconMap={LIKE:'fa-solid fa-thumbs-up',LOVE:'fa-solid fa-heart',HAHA:'fa-solid fa-face-laugh',WOW:'fa-solid fa-face-surprise',SAD:'fa-solid fa-face-sad-tear',ANGRY:'fa-solid fa-face-angry'};
        var colorMap={LIKE:'#1877f2',LOVE:'#f33e58',HAHA:'#f7b125',WOW:'#f7b125',SAD:'#f7b125',ANGRY:'#e9710f'};
        data.reactions.forEach(function(r){
          html += '<span style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#555;"><i class="'+(iconMap[r.type]||'fa-solid fa-thumbs-up')+'" style="color:'+(colorMap[r.type]||'#888')+';font-size:15px;"></i>'+r.cnt+'</span>';
        });
        html += '</div>';
      }
      if (data.comments && data.comments.length > 0) {
        html += '<div style="font-size:13px;font-weight:700;color:#051650;margin-bottom:10px;"><i class="fa-regular fa-comment" style="margin-right:6px;"></i>' + data.comments.length + ' Comment' + (data.comments.length!==1?'s':'') + '</div>';
        html += '<div style="display:flex;flex-direction:column;gap:10px;">';
        data.comments.forEach(function(c) {
          html += '<div style="display:flex;gap:10px;align-items:flex-start;">'
            + '<div style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid #ccff00;"><img src="'+escHtml(c.avatar)+'" style="width:100%;height:100%;object-fit:cover;"/></div>'
            + '<div style="background:#f5f6fa;border-radius:8px;padding:8px 12px;flex:1;">'
            + '<strong style="font-size:13px;color:#051650;display:block;">'+escHtml(c.name)+'</strong>'
            + '<p style="font-size:13px;color:#555;margin:2px 0 0;">'+escHtml(c.body).replace(/\n/g,'<br>')+'</p>'
            + '</div></div>';
        });
        html += '</div>';
      } else {
        html += '<p style="font-size:13px;color:#aaa;text-align:center;padding:10px 0;">No comments yet.</p>';
      }
      body.innerHTML = html;
      cfForm.style.display = 'block';
      gotoLink.style.display = 'inline-flex';
      document.getElementById('modalCommentInput').value = '';
      document.getElementById('modalCommentError').style.display = 'none';
    }).catch(function(){ body.innerHTML = '<p style="padding:20px;color:#aaa;">Could not load post.</p>'; });
}

function submitModalComment() {
  var input = document.getElementById('modalCommentInput');
  var errEl = document.getElementById('modalCommentError');
  var btn   = document.getElementById('modalCommentSendBtn');
  var body  = input.value.trim();
  errEl.style.display = 'none';
  if (!body) { errEl.textContent = 'Please write a comment first.'; errEl.style.display = 'block'; return; }
  if (!_currentModalPostId) return;
  btn.disabled = true; btn.textContent = 'Sending...';
  var fd = new FormData();
  fd.append('action', 'comment');
  fd.append('post_id', _currentModalPostId);
  fd.append('comment_body', body);
  fd.append('redirect', 'treasurercommunity.php#post-' + _currentModalPostId);
  fetch('react.php', { method: 'POST', body: fd })
    .then(function() {
      closePostModal();
      setTimeout(function(){ location.reload(); }, 400);
    }).catch(function() {
      btn.disabled = false; btn.textContent = 'Send';
      errEl.textContent = 'Failed to send. Please try again.';
      errEl.style.display = 'block';
    });
}

function goToPostInFeed(e) {
  e.preventDefault(); closePostModal();
  var postId = _currentModalPostId;
  if (!postId) return;
  var target = document.getElementById('post-' + postId);
  if (target) {
    setTimeout(function() {
      toggleComments(postId);
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('post-highlight');
    }, 100);
  }
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

/* FILE HANDLING */
var selectedFiles = [];
var imageExts = ['jpg','jpeg','png','gif','webp','bmp'];
function triggerFile(type) {
  var input = document.getElementById('singleFileInput');
  input.accept = type === 'image' ? 'image/*' : '';
  input.click();
}
function handleFileSelect(input) {
  Array.from(input.files).forEach(function(file) {
    var already = selectedFiles.some(function(f){ return f.name === file.name && f.size === file.size; });
    if (!already) selectedFiles.push(file);
  });
  input.value = ''; renderPreviews(); syncFiles();
}
function renderPreviews() {
  var wrap = document.getElementById('mediaPreviewWrap');
  wrap.innerHTML = '';
  selectedFiles.forEach(function(file, idx) {
    var ext = file.name.split('.').pop().toLowerCase();
    var isImage = imageExts.includes(ext);
    var item = document.createElement('div');
    item.style.cssText = 'position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;background:#f0f2fa;';
    if (isImage) {
      var img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
      item.appendChild(img);
    } else {
      item.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:24px;"><i class="fa-solid fa-file" style="color:#051650;"></i></div>';
    }
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = '&times;';
    btn.style.cssText = 'position:absolute;top:2px;right:2px;background:rgba(0,0,0,0.6);color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;';
    btn.onclick = function(){ selectedFiles.splice(idx,1); renderPreviews(); syncFiles(); };
    item.appendChild(btn);
    wrap.appendChild(item);
  });
}
function syncFiles() {
  var dt = new DataTransfer();
  selectedFiles.forEach(function(f){ dt.items.add(f); });
  document.getElementById('singleFileInput').files = dt.files;
}

/* LOAD MORE */
var POSTS_PER_PAGE = 10;
var visiblePostCount = POSTS_PER_PAGE;
function initPostVisibility() {
  var posts = document.querySelectorAll('#feedColumn .community-stream-card');
  posts.forEach(function(post, idx) { post.style.display = idx < POSTS_PER_PAGE ? '' : 'none'; });
  var wrap = document.getElementById('loadMoreWrap');
  if (wrap) wrap.style.display = posts.length > POSTS_PER_PAGE ? '' : 'none';
}
function loadMorePosts() {
  var posts = document.querySelectorAll('#feedColumn .community-stream-card');
  var next = visiblePostCount + POSTS_PER_PAGE;
  posts.forEach(function(post, idx) { if (idx >= visiblePostCount && idx < next) post.style.display = ''; });
  visiblePostCount = next;
  var wrap = document.getElementById('loadMoreWrap');
  if (visiblePostCount >= posts.length && wrap) wrap.style.display = 'none';
}
initPostVisibility();

/* SEARCH */
document.getElementById('feedSearch').addEventListener('input', function() {
  var query = this.value.toLowerCase().trim();
  var wrap  = document.getElementById('loadMoreWrap');
  if (query !== '') {
    document.querySelectorAll('#feedColumn .community-stream-card').forEach(function(card) {
      var match = (card.dataset.poster||'').includes(query) || (card.dataset.body||'').includes(query);
      card.style.display = match ? '' : 'none';
      card.classList.toggle('search-hidden', !match);
    });
    if (wrap) wrap.style.display = 'none';
  } else {
    document.querySelectorAll('#feedColumn .community-stream-card').forEach(function(card){ card.classList.remove('search-hidden'); });
    initPostVisibility();
  }
  document.querySelectorAll('#announcementsList .community-mini-post').forEach(function(ann) {
    ann.classList.toggle('search-hidden', query !== '' && !(ann.dataset.annTitle||'').includes(query));
  });
});

/* LIGHTBOX */
var lbImages = [], lbIndex = 0;
function openLightbox(images, idx) {
  lbImages = images; lbIndex = idx; showLightboxImage();
  document.getElementById('lightboxOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function showLightboxImage() {
  document.getElementById('lightboxImg').src = lbImages[lbIndex];
  var counter = document.getElementById('lightboxCounter');
  if (lbImages.length > 1) { counter.textContent = (lbIndex+1)+' / '+lbImages.length; counter.style.display = 'block'; }
  else { counter.style.display = 'none'; }
  document.querySelector('.lightbox-prev').style.display = lbImages.length > 1 ? 'flex' : 'none';
  document.querySelector('.lightbox-next').style.display = lbImages.length > 1 ? 'flex' : 'none';
}
function lightboxNav(dir) { lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length; showLightboxImage(); }
function closeLightbox() { document.getElementById('lightboxOverlay').classList.remove('open'); document.body.style.overflow = ''; }
function closeLightboxOnBg(e) { if (e.target === document.getElementById('lightboxOverlay')) closeLightbox(); }
document.addEventListener('keydown', function(e) {
  var lb = document.getElementById('lightboxOverlay');
  if (!lb.classList.contains('open')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') lightboxNav(-1);
  if (e.key === 'ArrowRight') lightboxNav(1);
});

/* HASH NAVIGATION */
var hash = window.location.hash;
if (hash && hash.startsWith('#post-')) {
  var postId = parseInt(hash.replace('#post-', ''));
  var target = document.getElementById('post-' + postId);
  if (target) {
    document.querySelectorAll('#feedColumn .community-stream-card').forEach(function(p){ p.style.display = ''; });
    visiblePostCount = document.querySelectorAll('#feedColumn .community-stream-card').length;
    setTimeout(function() {
      toggleComments(postId);
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('post-highlight');
    }, 150);
  }
}
</script>
</body>
</html>