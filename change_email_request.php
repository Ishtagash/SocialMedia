<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

require_once 'email_helper.php';

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$userStmt = sqlsrv_query($conn, "SELECT EMAIL, USERNAME FROM USERS WHERE USER_ID = ?", [$userId]);
$userRow  = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
$oldEmail = rtrim($userRow['EMAIL'] ?? '');
$username = rtrim($userRow['USERNAME'] ?? '');

$regStmt = sqlsrv_query($conn, "SELECT FIRST_NAME FROM REGISTRATION WHERE USER_ID = ?", [$userId]);
$regRow  = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);
$firstName = rtrim($regRow['FIRST_NAME'] ?? $username);

$errorMsg = '';
$newEmail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = trim($_POST['new_email'] ?? '');

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address (e.g. you@gmail.com).';
    } elseif (strtolower($newEmail) === strtolower($oldEmail)) {
        $errorMsg = 'The new email is the same as your current email.';
    } else {
        $checkStmt = sqlsrv_query($conn, "SELECT USER_ID FROM USERS WHERE EMAIL = ?", [$newEmail]);
        $checkRow  = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if ($checkRow) {
            $errorMsg = 'That email is already used by another account.';
        } else {
            $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $_SESSION['email_change'] = [
                'new_email'       => $newEmail,
                'old_otp'         => $otp,
                'old_otp_expires' => $expires,
                'old_verified'    => false,
                'new_otp'         => null,
                'new_otp_expires' => null,
                'new_verified'    => false,
            ];

            $sent = sendOtpEmail($oldEmail, $firstName, $otp, 'old');

            if ($sent) {
                header("Location: change_email_verify_old.php");
                exit();
            } else {
                $errorMsg = 'Could not send verification email. Please try again.';
                unset($_SESSION['email_change']);
            }
        }
    }
} else {
    $newEmail = trim($_GET['prefill'] ?? '');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Change Email — BarangayKonek</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg); }
    .card {
      background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow-lg);
      border:1px solid var(--border); width:100%; max-width:460px; padding:40px 36px;
      animation:fadeUp .45s ease both;
    }
    .card-icon {
      width:54px; height:54px; border-radius:50%; background:var(--navy);
      display:flex; align-items:center; justify-content:center;
      color:var(--lime); font-size:22px; margin-bottom:20px;
    }
    h1 { font-size:22px; font-weight:800; color:var(--navy); margin-bottom:6px; }
    .sub { font-size:13px; color:var(--text-muted); margin-bottom:28px; line-height:1.6; }
    .sub strong { color:var(--navy); }
    .field-group { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
    .field-group label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--text-muted); }
    .field-group input {
      background:var(--surface); border:1px solid var(--border); border-radius:10px;
      padding:12px 14px; font-family:inherit; font-size:14px; color:var(--text);
      outline:none; transition:border-color .2s,box-shadow .2s;
    }
    .field-group input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(5,22,80,.08); }
    .hint { font-size:11px; color:var(--text-muted); margin-top:4px; display:flex; align-items:center; gap:5px; }
    .alert-err {
      display:flex; align-items:flex-start; gap:10px;
      background:rgba(255,77,77,.08); border:1px solid rgba(255,77,77,.25);
      border-left:4px solid var(--red); border-radius:10px;
      padding:12px 16px; font-size:13px; color:var(--red); margin-bottom:20px;
    }
    .btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:none; font-family:inherit; }
    .btn--primary { background:var(--navy); color:#fff; transition:background .2s; }
    .btn--primary:hover { background:#0a2470; }
    .back-link { display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); text-decoration:none; margin-top:20px; justify-content:center; }
    .back-link:hover { color:var(--navy); }
    .steps { display:flex; gap:8px; margin-bottom:28px; }
    .step { flex:1; height:4px; border-radius:3px; background:var(--border); }
    .step.done { background:var(--lime); }
    .step.active { background:var(--navy); }
  </style>
</head>
<body>
<div class="card">
  <div class="card-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
  <h1>Change Email Address</h1>
  <p class="sub">Enter your new email. We'll send a verification code to your <strong>current email</strong> first, then to the new one.</p>

  <div class="steps">
    <div class="step active"></div>
    <div class="step"></div>
    <div class="step"></div>
  </div>

  <?php if ($errorMsg): ?>
  <div class="alert-err">
    <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
    <span><?= htmlspecialchars($errorMsg) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST" action="change_email_request.php">
    <div class="field-group">
      <label>New Email Address</label>
      <input type="email" name="new_email" value="<?= htmlspecialchars($newEmail) ?>" placeholder="you@gmail.com" required autofocus />
      <span class="hint"><i class="fa-solid fa-circle-info"></i> Must be a valid email address.</span>
    </div>
    <button type="submit" class="btn btn--primary"><i class="fa-solid fa-paper-plane"></i> Send Verification Code</button>
  </form>

  <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Settings</a>
</div>
</body>
</html>