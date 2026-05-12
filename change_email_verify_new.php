<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

if (
    empty($_SESSION['email_change']['new_email']) ||
    empty($_SESSION['email_change']['old_verified']) ||
    $_SESSION['email_change']['old_verified'] !== true
) {
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

$regStmt   = sqlsrv_query($conn, "SELECT FIRST_NAME FROM REGISTRATION WHERE USER_ID = ?", [$userId]);
$regRow    = sqlsrv_fetch_array($regStmt, SQLSRV_FETCH_ASSOC);
$firstName = rtrim($regRow['FIRST_NAME'] ?? '');

$errorMsg   = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify_new') {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if (strtotime($ec['new_otp_expires']) < time()) {
            $errorMsg = 'Your code has expired. Please start over.';
        } elseif ($enteredOtp !== $ec['new_otp']) {
            $errorMsg = 'Incorrect code. Please try again.';
        } else {
            $updateStmt = sqlsrv_query($conn, "UPDATE USERS SET EMAIL = ? WHERE USER_ID = ?", [$newEmail, $userId]);

            if ($updateStmt) {
                unset($_SESSION['email_change']);
                $_SESSION['email_change_done'] = true;
                header("Location: settings.php?email_updated=1");
                exit();
            } else {
                $errorMsg = 'Could not update your email. Please try again or contact support.';
            }
        }
    }

    if ($action === 'resend_new') {
        $newOtp              = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ec['new_otp']         = $newOtp;
        $ec['new_otp_expires'] = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $sent = sendOtpEmail($newEmail, $firstName, $newOtp, 'new');
        if ($sent) {
            $successMsg = 'A new code was sent to your new email address.';
        } else {
            $errorMsg = 'Could not resend the code. Please try again.';
        }
    }
}

$maskedNew = preg_replace_callback('/^(.{2})(.+?)(@.+)$/', function($m) {
    return $m[1] . str_repeat('*', strlen($m[2])) . $m[3];
}, $newEmail);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify New Email — BarangayKonek</title>
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
      width:54px; height:54px; border-radius:50%; background:var(--lime);
      display:flex; align-items:center; justify-content:center;
      color:var(--navy); font-size:22px; margin-bottom:20px;
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
    .new-email-chip {
      display:inline-flex; align-items:center; gap:6px;
      background:rgba(204,255,0,.18); border:1px solid rgba(204,255,0,.5);
      border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700;
      color:var(--navy); margin-bottom:20px;
    }
  </style>
</head>
<body>
<div class="card">
  <div class="card-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
  <h1>Verify New Email</h1>
  <p class="sub">Almost done! Enter the code we sent to your <strong>new address</strong>.</p>
  <div class="new-email-chip"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($maskedNew) ?></div>

  <div class="steps">
    <div class="step done"></div>
    <div class="step done"></div>
    <div class="step active"></div>
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

  <form method="POST" action="change_email_verify_new.php">
    <input type="hidden" name="action" value="verify_new" />
    <div class="field-group">
      <label>Verification Code</label>
      <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus />
    </div>
    <button type="submit" class="btn btn--primary"><i class="fa-solid fa-check-double"></i> Confirm &amp; Update Email</button>
  </form>

  <form method="POST" action="change_email_verify_new.php">
    <input type="hidden" name="action" value="resend_new" />
    <button type="submit" class="btn btn--ghost"><i class="fa-solid fa-rotate-right"></i> Resend code to new email</button>
  </form>

  <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel and go back</a>
</div>
</body>
</html>