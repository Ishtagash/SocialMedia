<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$regSql  = "SELECT FIRST_NAME, LAST_NAME, GENDER, PROFILE_PICTURE, MOBILE_NUMBER FROM REGISTRATION WHERE USER_ID = ?";
$regStmt = sqlsrv_query($conn, $regSql, [$userId]);
$regRow  = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);

$firstName = $regRow ? htmlspecialchars(rtrim($regRow['FIRST_NAME'])) : '';
$lastName  = $regRow ? htmlspecialchars(rtrim($regRow['LAST_NAME']))  : '';
$fullName  = $firstName . ' ' . $lastName;
$gender    = $regRow ? strtolower(rtrim($regRow['GENDER'] ?? '')) : '';
$mobile    = $regRow ? htmlspecialchars(rtrim($regRow['MOBILE_NUMBER'] ?? '')) : '';

if ($regRow && !empty($regRow['PROFILE_PICTURE'])) {
    $profilePicture = htmlspecialchars($regRow['PROFILE_PICTURE']);
} elseif ($gender === 'male') {
    $profilePicture = 'default/male.png';
} elseif ($gender === 'female') {
    $profilePicture = 'default/female.png';
} else {
    $profilePicture = 'default/neutral.png';
}

$userSql  = "SELECT EMAIL, USERNAME FROM USERS WHERE USER_ID = ?";
$userStmt = sqlsrv_query($conn, $userSql, [$userId]);
$userRow  = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
$email    = $userRow ? htmlspecialchars(rtrim($userRow['EMAIL']    ?? '')) : '';
$username = $userRow ? htmlspecialchars(rtrim($userRow['USERNAME'] ?? '')) : '';

$successMsg = '';
$errorMsg   = '';
$activeTab  = 'profile';

if (isset($_GET['email_updated'])) {
    $successMsg = 'Your email address has been updated successfully.';
    $userStmt   = sqlsrv_query($conn, "SELECT EMAIL, USERNAME FROM USERS WHERE USER_ID = ?", [$userId]);
    $userRow    = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
    $email      = $userRow ? htmlspecialchars(rtrim($userRow['EMAIL'] ?? '')) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $activeTab = 'profile';
        $newMobile = trim($_POST['mobile'] ?? '');
        $newEmail  = trim($_POST['email']  ?? '');

        if (!empty($_FILES['profile_pic']['name'])) {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext     = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $newPath = $uploadDir . 'user_' . $userId . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $newPath)) {
                sqlsrv_query($conn, "UPDATE REGISTRATION SET PROFILE_PICTURE = ?, MOBILE_NUMBER = ? WHERE USER_ID = ?", [$newPath, $newMobile, $userId]);
                $profilePicture = htmlspecialchars($newPath);
            } else {
                $errorMsg = 'Failed to upload profile picture.';
            }
        } else {
            sqlsrv_query($conn, "UPDATE REGISTRATION SET MOBILE_NUMBER = ? WHERE USER_ID = ?", [$newMobile, $userId]);
        }

        if (!$errorMsg) {
            $mobile = htmlspecialchars($newMobile);
            $currentEmail = rtrim($userRow['EMAIL'] ?? '');
            if ($newEmail && strtolower($newEmail) !== strtolower($currentEmail)) {
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $errorMsg = 'The email address you entered is not valid.';
                } else {
                    header("Location: change_email_request.php?prefill=" . urlencode($newEmail));
                    exit();
                }
            } else {
                $successMsg = 'Profile updated successfully.';
            }
        }
    }

    if ($action === 'change_password') {
        $activeTab = 'security';
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $pwRow   = sqlsrv_fetch_array(
            sqlsrv_query($conn, "SELECT PASSWORD FROM USERS WHERE USER_ID = ?", [$userId]),
            SQLSRV_FETCH_ASSOC
        );
        if (!$pwRow || $current !== $pwRow['PASSWORD']) {
            $errorMsg = 'Current password is incorrect.';
        } elseif ($new !== $confirm) {
            $errorMsg = 'New passwords do not match.';
        } elseif (strlen($new) < 16 || strlen($new) > 32) {
            $errorMsg = 'Password must be between 16 and 32 characters.';
        } else {
            sqlsrv_query($conn, "UPDATE USERS SET PASSWORD = ? WHERE USER_ID = ?", [$new, $userId]);
            $successMsg = 'Password changed successfully.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings — BarangayKonek</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="resident.css" />
  <style>
    .sidebar-divider { height:1px; background:rgba(255,255,255,0.08); margin:6px 14px; }

    .logout-confirm-overlay { position:fixed; inset:0; z-index:2000; background:rgba(5,22,80,.65); display:none; align-items:center; justify-content:center; }
    .logout-confirm-overlay.open { display:flex; }
    .logout-confirm-box { background:#fff; border-radius:12px; padding:36px 32px; max-width:380px; width:90%; text-align:center; border-top:4px solid var(--lime); box-shadow:0 16px 48px rgba(5,22,80,.28); }
    .logout-confirm-icon { width:56px; height:56px; border-radius:50%; background:var(--navy); color:var(--lime); display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 16px; }
    .logout-confirm-box h3 { font-size:20px; font-weight:700; color:var(--navy); margin-bottom:8px; }
    .logout-confirm-box p  { font-size:14px; color:#666; margin-bottom:24px; line-height:1.6; }
    .logout-confirm-btns { display:flex; gap:10px; justify-content:center; }
    .btn-logout-confirm { background:var(--navy); color:var(--lime); border:none; padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-logout-cancel  { background:transparent; color:var(--navy); border:1px solid rgba(5,22,80,.25); padding:11px 28px; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }

    .settings-layout { display:grid; grid-template-columns:210px 1fr; gap:24px; align-items:start; }
    .settings-nav-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; position:sticky; top:24px; }
    .settings-nav-header { padding:16px 18px 12px; border-bottom:1px solid var(--border); }
    .settings-nav-header p { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin:0; }
    .settings-nav-list { padding:8px; display:flex; flex-direction:column; gap:2px; }
    .settings-nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--text-muted); text-decoration:none; cursor:pointer; border:none; background:none; width:100%; font-family:inherit; transition:background .15s,color .15s; }
    .settings-nav-item:hover { background:rgba(5,22,80,.05); color:var(--navy); }
    .settings-nav-item.active { background:var(--navy); color:#fff; font-weight:700; }
    .settings-nav-item i { width:16px; text-align:center; font-size:13px; }
    .settings-section { display:none; flex-direction:column; gap:20px; }
    .settings-section.active { display:flex; }
    .section-heading { font-size:17px; font-weight:700; color:var(--navy); margin-bottom:4px; display:flex; align-items:center; gap:10px; }
    .section-heading::before { content:''; display:block; width:4px; height:18px; background:var(--lime); border-radius:3px; flex-shrink:0; }
    .section-sub { font-size:13px; color:var(--text-muted); margin-bottom:20px; padding-left:14px; }
    .avatar-upload-wrap { display:flex; align-items:center; gap:20px; padding:18px; background:rgba(5,22,80,.03); border:1px solid var(--border); border-radius:12px; margin-bottom:20px; }
    .avatar-upload-img { width:76px; height:76px; border-radius:50%; object-fit:cover; border:3px solid var(--lime); flex-shrink:0; }
    .avatar-upload-info h4 { font-size:15px; font-weight:700; color:var(--navy); margin-bottom:4px; }
    .avatar-upload-info p  { font-size:12px; color:var(--text-muted); margin-bottom:10px; }
    .btn-upload { display:inline-flex; align-items:center; gap:7px; background:var(--navy); color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; transition:background .2s; }
    .btn-upload:hover { background:#0a2470; }
    .field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
    .field-group { display:flex; flex-direction:column; gap:6px; }
    .field-group label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--text-muted); }
    .field-group input,.field-group select { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:11px 14px; font-family:inherit; font-size:14px; color:var(--text); outline:none; transition:border-color .2s,box-shadow .2s; }
    .field-group input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(5,22,80,.08); }
    .field-group input[disabled] { background:rgba(5,22,80,.04); color:var(--text-muted); cursor:not-allowed; }
    .email-editable-hint { font-size:11px; color:var(--text-muted); margin-top:4px; display:flex; align-items:center; gap:5px; }
    .pw-wrap { position:relative; }
    .pw-wrap input { width:100%; padding-right:42px; }
    .pw-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:14px; transition:color .2s; }
    .pw-eye:hover { color:var(--navy); }
    .pw-bars { display:flex; gap:4px; margin:8px 0 3px; }
    .pw-bar { height:4px; flex:1; border-radius:3px; background:var(--border); transition:background .25s; }
    .pw-bar.weak   { background:var(--red); }
    .pw-bar.fair   { background:#f59e0b; }
    .pw-bar.strong { background:var(--green); }
    .pw-strength-label { font-size:11px; color:var(--text-muted); }
    .alert-ok { display:flex; align-items:flex-start; gap:10px; background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3); border-left:4px solid var(--green); border-radius:10px; padding:12px 16px; font-size:14px; color:#15803d; margin-bottom:20px; }
    .alert-err { display:flex; align-items:flex-start; gap:10px; background:rgba(255,77,77,.08); border:1px solid rgba(255,77,77,.25); border-left:4px solid var(--red); border-radius:10px; padding:12px 16px; font-size:14px; color:var(--red); margin-bottom:20px; }
    .save-bar { display:flex; justify-content:flex-end; gap:10px; padding-top:18px; border-top:1px solid var(--border); margin-top:6px; }

    @media (max-width:900px) {
      .settings-layout { grid-template-columns:1fr; }
      .settings-nav-panel { position:static; }
      .settings-nav-list { flex-direction:row; flex-wrap:wrap; }
      .field-row { grid-template-columns:1fr; }
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
      <a href="residentconcern.php"><i class="fa-solid fa-circle-exclamation nav-icon"></i><span>Concerns</span></a>
      <a href="residentcommunity.php"><i class="fa-solid fa-users nav-icon"></i><span>Community</span></a>
      <a href="residentrequest.php"><i class="fa-solid fa-clipboard-list nav-icon"></i><span>My Requests</span></a>
    </nav>
    <div class="sidebar-divider"></div>
    <nav class="menu">
      <a href="settings.php" class="active"><i class="fa-solid fa-gear nav-icon"></i><span>Settings</span></a>
    </nav>
    <div style="flex:1;"></div>
    <button type="button" class="logout" onclick="openLogout()" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;font-family:inherit;">
      <i class="fa-solid fa-right-from-bracket nav-icon"></i><span>Logout</span>
    </button>
  </aside>

  <main class="content">
    <div class="topbar">
      <div class="greeting-block">
        <h1>Account <span class="accent-name">Settings</span></h1>
        <p class="subtitle">Manage your profile and security preferences.</p>
      </div>
    </div>

    <?php if ($successMsg): ?>
    <div class="alert-ok">
      <i class="fa-solid fa-circle-check" style="margin-top:2px;flex-shrink:0;"></i>
      <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="alert-err">
      <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
      <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
    <?php endif; ?>

    <div class="settings-layout">
      <div class="settings-nav-panel">
        <div class="settings-nav-header"><p>Preferences</p></div>
        <div class="settings-nav-list">
          <button class="settings-nav-item <?= $activeTab === 'profile'  ? 'active' : '' ?>" onclick="showTab('profile',this)">
            <i class="fa-solid fa-user"></i> Profile
          </button>
          <button class="settings-nav-item <?= $activeTab === 'security' ? 'active' : '' ?>" onclick="showTab('security',this)">
            <i class="fa-solid fa-lock"></i> Security
          </button>
        </div>
      </div>

      <div>
        <div class="settings-section <?= $activeTab === 'profile' ? 'active' : '' ?>" id="tab-profile">
          <div class="panel">
            <h2 class="section-heading">Profile Information</h2>
            <p class="section-sub">Update your photo, contact details, or email address.</p>

            <form method="POST" action="settings.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_profile">

              <div class="avatar-upload-wrap">
                <img src="<?= $profilePicture ?>" id="avatarPreview" class="avatar-upload-img" alt="Profile" />
                <div class="avatar-upload-info">
                  <h4><?= $fullName ?></h4>
                  <p>JPG or PNG · max 2MB · replaces your default gender photo.</p>
                  <label class="btn-upload">
                    <i class="fa-solid fa-camera"></i> Change Photo
                    <input type="file" name="profile_pic" accept="image/*" style="display:none;" onchange="previewAvatar(this)" />
                  </label>
                </div>
              </div>

              <div class="field-row">
                <div class="field-group">
                  <label>First Name</label>
                  <input type="text" value="<?= $firstName ?>" disabled />
                </div>
                <div class="field-group">
                  <label>Last Name</label>
                  <input type="text" value="<?= $lastName ?>" disabled />
                </div>
              </div>

              <div class="field-row">
                <div class="field-group">
                  <label>Username</label>
                  <input type="text" value="<?= $username ?>" disabled />
                </div>
                <div class="field-group">
                  <label>Gender</label>
                  <input type="text" value="<?= ucfirst($gender) ?>" disabled />
                </div>
              </div>

              <div class="field-row">
                <div class="field-group">
                  <label>Mobile Number</label>
                  <input type="tel" name="mobile" value="<?= $mobile ?>" placeholder="09XX-XXX-XXXX" />
                </div>
                <div class="field-group">
                  <label>Email Address</label>
                  <input type="email" name="email" id="emailInput" value="<?= $email ?>" placeholder="Enter email address" />
                  <span class="email-editable-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    Changing this will require email verification.
                  </span>
                </div>
              </div>

              <p style="font-size:12px;color:var(--text-muted);margin-bottom:18px;">
                <i class="fa-solid fa-circle-info" style="margin-right:5px;"></i>
                Name, username, and gender can only be changed by staff.
              </p>

              <div class="save-bar">
                <button type="submit" class="btn btn--primary">
                  <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="settings-section <?= $activeTab === 'security' ? 'active' : '' ?>" id="tab-security">
          <div class="panel">
            <h2 class="section-heading">Change Password</h2>
            <p class="section-sub">Your password must be between 16 and 32 characters.</p>

            <form method="POST" action="settings.php">
              <input type="hidden" name="action" value="change_password">

              <div class="field-row" style="grid-template-columns:1fr;">
                <div class="field-group">
                  <label>Current Password</label>
                  <div class="pw-wrap">
                    <input type="password" name="current_password" id="currentPw" placeholder="Enter current password" required />
                    <button type="button" class="pw-eye" onclick="togglePw('currentPw','eyeC')"><i class="fa-solid fa-eye" id="eyeC"></i></button>
                  </div>
                </div>
              </div>
              <div class="field-row">
                <div class="field-group">
                  <label>New Password</label>
                  <div class="pw-wrap">
                    <input type="password" name="new_password" id="newPw" placeholder="Min. 16 characters" minlength="16" maxlength="32" oninput="checkStrength()" required />
                    <button type="button" class="pw-eye" onclick="togglePw('newPw','eye1')"><i class="fa-solid fa-eye" id="eye1"></i></button>
                  </div>
                  <div class="pw-bars">
                    <div class="pw-bar" id="sb1"></div><div class="pw-bar" id="sb2"></div>
                    <div class="pw-bar" id="sb3"></div><div class="pw-bar" id="sb4"></div>
                  </div>
                  <span class="pw-strength-label" id="strengthLabel">Enter a password</span>
                </div>
                <div class="field-group">
                  <label>Confirm New Password</label>
                  <div class="pw-wrap">
                    <input type="password" name="confirm_password" id="confirmPw" placeholder="Re-enter new password" minlength="16" maxlength="32" required />
                    <button type="button" class="pw-eye" onclick="togglePw('confirmPw','eye2')"><i class="fa-solid fa-eye" id="eye2"></i></button>
                  </div>
                </div>
              </div>

              <div class="save-bar">
                <button type="submit" class="btn btn--primary">
                  <i class="fa-solid fa-key"></i> Update Password
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function showTab(id, btn) {
  document.querySelectorAll('.settings-section').forEach(function(s) { s.classList.remove('active'); });
  document.querySelectorAll('.settings-nav-item').forEach(function(b) { b.classList.remove('active'); });
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
}
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    var r = new FileReader();
    r.onload = function(e) { document.getElementById('avatarPreview').src = e.target.result; };
    r.readAsDataURL(input.files[0]);
  }
}
function togglePw(inputId, iconId) {
  var el = document.getElementById(inputId);
  var ic = document.getElementById(iconId);
  el.type = el.type === 'password' ? 'text' : 'password';
  ic.className = el.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}
function checkStrength() {
  var pw = document.getElementById('newPw').value;
  var bars = ['sb1','sb2','sb3','sb4'].map(function(id) { return document.getElementById(id); });
  var score = 0;
  if (pw.length >= 16)         score++;
  if (/[A-Z]/.test(pw))        score++;
  if (/[0-9]/.test(pw))        score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  bars.forEach(function(b) { b.className = 'pw-bar'; });
  var cls = score <= 1 ? 'weak' : score <= 2 ? 'fair' : 'strong';
  for (var i = 0; i < score; i++) bars[i].classList.add(cls);
  document.getElementById('strengthLabel').textContent = score > 0 ? ['','Weak','Weak','Fair','Strong'][score] : 'Enter a password';
}
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === document.getElementById('logoutModal')) closeLogout();
});
</script>
</body>
</html>