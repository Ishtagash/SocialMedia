<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['email_change']['new_email'])) {
    header("Location: change_email_request.php");
    exit();
}

require_once 'email_helper.php';

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId   = $_SESSION['user_id'];
$ec       = &$_SESSION['email_change'];
$newEmail = $ec['new_email'];

$userStmt  = sqlsrv_query($conn, "SELECT EMAIL FROM USERS WHERE USER_ID = ?", [$userId]);
$userRow   = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
$oldEmail  = rtrim($userRow['EMAIL'] ?? '');

$regStmt   = sqlsrv_query($conn, "SELECT FIRST_NAME FROM REGISTRATION WHERE USER_ID = ?", [$userId]);
$regRow    = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);
$firstName = rtrim($regRow['FIRST_NAME'] ?? '');

$errorMsg   = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify_old') {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if (strtotime($ec['old_otp_expires']) < time()) {
            $errorMsg = 'Your code has expired. Please start over.';
        } elseif ($enteredOtp !== $ec['old_otp']) {
            $errorMsg = 'Incorrect code. Please try again.';
        } else {
            $ec['old_verified'] = true;

            $newOtp            = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $newExpires        = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $ec['new_otp']         = $newOtp;
            $ec['new_otp_expires'] = $newExpires;

            $sent = sendOtpEmail($newEmail, $firstName, $newOtp, 'new');

            if ($sent) {
                header("Location: change_email_verify_new.php");
                exit();
            } else {
                $ec['old_verified']    = false;
                $ec['new_otp']         = null;
                $ec['new_otp_expires'] = null;
                $errorMsg = 'Could not send verification email to your new address. Please try again.';
            }
        }
    }

    if ($action === 'resend_old') {
        $newOtp              = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ec['old_otp']         = $newOtp;
        $ec['old_otp_expires'] = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $sent = sendOtpEmail($oldEmail, $firstName, $newOtp, 'old');
        if ($sent) {
            $successMsg = 'A new code was sent to your current email.';
        } else {
            $errorMsg = 'Could not resend the code. Please try again.';
        }
    }
}

$maskedOld = preg_replace_callback('/^(.{2})(.+?)(@.+)$/', function($m) {
    return $m[1] . str_repeat('*', strlen($m[2])) . $m[3];
}, $oldEmail);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify Current Email — BarangayKonek</title>
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
    .otp-input {
      background:var(--surface); border:2px solid var(--border); border-radius:10px;
      padding:14px; font-family:'Space Mono',monospace; font-size:26px; font-weight:700;
      color:var(--navy); letter-spacing:10px; text-align:center;
      outline:none; transition:border-color .2s,box-shadow .2s; width:100%;
    }
    .otp-input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(5,22,80,.08); }
    .alert-err {
      display:flex; align-items:flex-start; gap:10px;
      background:rgba(255,77,77,.08); border:1px solid rgba(255,77,77,.25);
      border-left:4px solid var(--red); border-radius:10px;
      padding:12px 16px; font-size:13px; color:var(--red); margin-bottom:20px;
    }
    .alert-ok {
      display:flex; align-items:flex-start; gap:10px;
      background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3);
      border-left:4px solid var(--green); border-radius:10px;
      padding:12px 16px; font-size:13px; color:#15803d; margin-bottom:20px;
    }
    .btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:none; font-family:inherit; }
    .btn--primary { background:var(--navy); color:#fff; transition:background .2s; }
    .btn--primary:hover { background:#0a2470; }
    .btn--ghost { background:none; color:var(--text-muted); font-size:13px; font-weight:500; margin-top:10px; }
    .btn--ghost:hover { color:var(--navy); }
    .back-link { display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); text-decoration:none; margin-top:16px; justify-content:center; }
    .back-link:hover { color:var(--navy); }
    .steps { display:flex; gap:8px; margin-bottom:28px; }
    .step { flex:1; height:4px; border-radius:3px; background:var(--border); }
    .step.done { background:var(--lime); }
    .step.active { background:var(--navy); }
  </style>
</head>
<body>
<div class="card">
  <div class="card-icon"><i class="fa-solid fa-shield-halved"></i></div>
  <h1>Verify Current Email</h1>
  <p class="sub">We sent a 6-digit code to <strong><?= htmlspecialchars($maskedOld) ?></strong>. Enter it below to confirm it's you.</p>

  <div class="steps">
    <div class="step done"></div>
    <div class="step active"></div>
    <div class="step"></div>
  </div>

  <?php if ($errorMsg): ?>
  <div class="alert-err">
    <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
    <span><?= htmlspecialchars($errorMsg) ?></span>
  </div>
  <?php endif; ?>
  <?php if ($successMsg): ?>
  <div class="alert-ok">
    <i class="fa-solid fa-circle-check" style="margin-top:2px;flex-shrink:0;"></i>
    <span><?= htmlspecialchars($successMsg) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST" action="change_email_verify_old.php">
    <input type="hidden" name="action" value="verify_old" />
    <div class="field-group">
      <label>Verification Code</label>
      <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus />
    </div>
    <button type="submit" class="btn btn--primary"><i class="fa-solid fa-check"></i> Confirm Code</button>
  </form>

  <form method="POST" action="change_email_verify_old.php">
    <input type="hidden" name="action" value="resend_old" />
    <button type="submit" class="btn btn--ghost"><i class="fa-solid fa-rotate-right"></i> Resend code to current email</button>
  </form>

  <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel and go back</a>
</div>
</body>
</html>