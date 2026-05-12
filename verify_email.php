<?php
session_start();

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

if (empty($_SESSION['reg'])) {
    header('Location: register.php');
    exit;
}

require_once 'email_helper.php';

$reg       = $_SESSION['reg'];
$email     = $reg['email'];
$firstName = $reg['fname'];

$maskedEmail = preg_replace_callback('/^(.{2})(.+?)(@.+)$/', function($m) {
    return $m[1] . str_repeat('*', strlen($m[2])) . $m[3];
}, $email);

$errorMsg   = '';
$successMsg = '';

if (!isset($_SESSION['reg_otp'])) {
    $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $_SESSION['reg_otp']         = $otp;
    $_SESSION['reg_otp_expires'] = $expires;
    $sent = sendOtpEmail($email, $firstName, $otp, 'register');
    if (!$sent) {
        $errorMsg = 'Could not send verification email. Please go back and try again.';
        unset($_SESSION['reg_otp'], $_SESSION['reg_otp_expires']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify') {
        $entered = trim($_POST['otp'] ?? '');

        if (empty($_SESSION['reg_otp'])) {
            $errorMsg = 'Session expired. Please go back and register again.';
        } elseif (strtotime($_SESSION['reg_otp_expires']) < time()) {
            $errorMsg = 'Your code has expired. Please resend.';
        } elseif ($entered !== $_SESSION['reg_otp']) {
            $errorMsg = 'Incorrect code. Please try again.';
        } else {
            $r = $_SESSION['reg'];

            $stmt1 = sqlsrv_query($conn,
                "INSERT INTO USERS (USERNAME, EMAIL, PASSWORD, ROLE, STATUS, EMAIL_VERIFIED, CREATED_AT)
                 VALUES (?, ?, ?, 'resident', 'PENDING', 1, GETDATE())",
                [$r['username'], $r['email'], $r['password']]
            );

            if ($stmt1 === false) {
                $errorMsg = 'Registration failed. Please try again.';
            } else {
                $idRow = sqlsrv_fetch_array(
                    sqlsrv_query($conn, "SELECT TOP 1 USER_ID FROM USERS WHERE USERNAME = ?", [$r['username']]),
                    SQLSRV_FETCH_ASSOC
                );
                $newUserId = $idRow['USER_ID'];

                $stmt2 = sqlsrv_query($conn,
                    "INSERT INTO REGISTRATION
                        (USER_ID, FIRST_NAME, MIDDLE_NAME, LAST_NAME, SUFFIX,
                         BIRTHDATE, GENDER, MOBILE_NUMBER, ID_TYPE, ID_PHOTO_PATH,
                         ADDRESS, CREATED_AT)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
                    [
                        $newUserId,
                        $r['fname'], $r['mname'], $r['lname'], $r['suffix'],
                        $r['dob'],   $r['gender'], $r['mobile'],
                        $r['idtype'], $r['id_photo'], $r['address']
                    ]
                );

                if ($stmt2 === false) {
                    $errorMsg = 'Registration failed. Please try again.';
                } else {
                    unset($_SESSION['reg'], $_SESSION['reg_otp'], $_SESSION['reg_otp_expires']);
                    header('Location: login.php?registered=1');
                    exit;
                }
            }
        }
    }

    if ($action === 'resend') {
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $_SESSION['reg_otp']         = $otp;
        $_SESSION['reg_otp_expires'] = $expires;
        $sent = sendOtpEmail($email, $firstName, $otp, 'register');
        if ($sent) {
            $successMsg = 'A new code was sent to your email.';
        } else {
            $errorMsg = 'Could not resend the code. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email — Barangay Alapan I-A</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="base.css">
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
  .email-chip {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(204,255,0,.18); border:1px solid rgba(204,255,0,.5);
    border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700;
    color:var(--navy); margin-bottom:24px;
  }
  .field-group { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
  .field-group label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--text-muted); }
  .otp-input {
    background:var(--surface); border:2px solid var(--border); border-radius:10px;
    padding:14px; font-family:'Space Mono',monospace; font-size:26px; font-weight:700;
    color:var(--navy); letter-spacing:10px; text-align:center;
    outline:none; transition:border-color .2s, box-shadow .2s; width:100%;
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
  .btn--primary:hover { background:#0a2470; color:#fff; }
  .btn--ghost { background:none; color:var(--text-muted); font-size:13px; font-weight:500; margin-top:10px; }
  .btn--ghost:hover { color:var(--navy); }
  .back-link { display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); text-decoration:none; margin-top:16px; justify-content:center; }
  .back-link:hover { color:var(--navy); }
</style>
</head>
<body>
<div class="card">

  <div class="card-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
  <h1>Verify Your Email</h1>
  <p class="sub">We sent a 6-digit code to your email address. Enter it below to activate your account.</p>
  <div class="email-chip"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($maskedEmail) ?></div>

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

  <form method="POST" action="verify_email.php">
    <input type="hidden" name="action" value="verify">
    <div class="field-group">
      <label>Verification Code</label>
      <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
    </div>
    <button type="submit" class="btn btn--primary">
      <i class="fa-solid fa-check"></i> Verify &amp; Create Account
    </button>
  </form>

  <form method="POST" action="verify_email.php">
    <input type="hidden" name="action" value="resend">
    <button type="submit" class="btn btn--ghost">
      <i class="fa-solid fa-rotate-right"></i> Resend code
    </button>
  </form>

  <a href="register.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Back to Register
  </a>

</div>
</body>
</html>