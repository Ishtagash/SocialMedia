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

$reg   = $_SESSION['reg'];
$email = $reg['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_mark_verified'])) {
    header('Content-Type: application/json');

    if (empty($_SESSION['reg'])) {
        echo json_encode(['ok' => false, 'error' => 'Session expired.']);
        exit;
    }

    $r = $_SESSION['reg'];

    $sql1  = "INSERT INTO USERS
                (USERNAME, EMAIL, PASSWORD, ROLE, STATUS, EMAIL_VERIFIED, CREATED_AT)
              VALUES (?, ?, ?, 'resident', 'PENDING', 1, GETDATE())";
    $stmt1 = sqlsrv_query($conn, $sql1, [
        $r['username'], $r['email'], $r['password']
    ]);

    if ($stmt1 === false) {
        echo json_encode(['ok' => false, 'error' => 'User insert failed: ' . print_r(sqlsrv_errors(), true)]);
        exit;
    }

    $idRow = sqlsrv_fetch_array(
        sqlsrv_query($conn, "SELECT TOP 1 USER_ID FROM USERS WHERE USERNAME = ?", [$r['username']]),
        SQLSRV_FETCH_ASSOC
    );
    $newUserId = $idRow['USER_ID'];

    $sql2  = "INSERT INTO REGISTRATION
                (USER_ID, FIRST_NAME, MIDDLE_NAME, LAST_NAME, SUFFIX,
                 BIRTHDATE, GENDER, MOBILE_NUMBER, ID_TYPE, ID_PHOTO_PATH,
                 ADDRESS, CREATED_AT)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
    $stmt2 = sqlsrv_query($conn, $sql2, [
        $newUserId,
        $r['fname'], $r['mname'], $r['lname'], $r['suffix'],
        $r['dob'],   $r['gender'], $r['mobile'],
        $r['idtype'], $r['id_photo'], $r['address']
    ]);

    if ($stmt2 === false) {
        echo json_encode(['ok' => false, 'error' => 'Registration insert failed: ' . print_r(sqlsrv_errors(), true)]);
        exit;
    }

    unset($_SESSION['reg']);
    echo json_encode(['ok' => true]);
    exit;
}

$tempPw = $reg['password'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email — Barangay Alapan I-A</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --dark:       #051650;
    --dark-hover: #0a2470;
    --lime:       #ccff00;
    --white:      #ffffff;
    --red:        #e03030;
    --green:      #2e7d32;
    --border:     rgba(5,22,80,0.14);
  }
  body { font-family: Arial, sans-serif; background: #eef0f8; color: var(--dark); min-height: 100vh; }
  .site-nav { background: var(--dark); border-bottom: 3px solid var(--lime); padding: 10px 0; }
  .nav-seal { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
  .nav-seal img { width: 100%; height: 100%; object-fit: cover; }
  .nav-brgy { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--lime); display: block; line-height: 1.2; }
  .nav-name  { font-size: 17px; font-weight: 700; color: var(--white); line-height: 1.2; }
  .verify-card {
    background: var(--white); border: 1px solid var(--border);
    border-top: 4px solid var(--dark); border-radius: 10px;
    padding: 48px 44px; box-shadow: 0 6px 30px rgba(5,22,80,.09);
    max-width: 460px; margin: 60px auto; text-align: center;
  }
  .verify-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: #eef0f8; color: var(--dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; margin: 0 auto 22px; border: 2px solid var(--border);
  }
  .verify-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
  .verify-sub   { font-size: 14px; color: #666; margin-bottom: 28px; line-height: 1.6; }
  .verify-sub strong { color: var(--dark); }
  .spinner {
    width: 40px; height: 40px; border: 3px solid #e0e0e0;
    border-top-color: var(--dark); border-radius: 50%;
    animation: spin .7s linear infinite; margin: 0 auto 14px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .alert-err {
    display: flex; align-items: flex-start; gap: 10px;
    background: #fff0f0; border: 1px solid #f5c0c0; border-left: 4px solid var(--red);
    border-radius: 6px; padding: 11px 14px; font-size: 14px; color: var(--red);
    margin-bottom: 20px; text-align: left;
  }
  .btn-verify {
    width: 100%; background: var(--dark); color: var(--white); border: none;
    padding: 14px; border-radius: 6px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 14px;
  }
  .btn-verify:hover { background: var(--dark-hover); }
  .btn-verify:disabled { opacity: .6; pointer-events: none; }
  .btn-resend {
    background: none; border: none; color: var(--dark); font-weight: 700;
    font-size: 14px; cursor: pointer; text-decoration: underline; padding: 0;
  }
  .btn-resend:hover { color: var(--dark-hover); }
  .btn-resend:disabled { opacity: .5; pointer-events: none; text-decoration: none; }
  .success-icon-wrap {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--lime); color: var(--dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; margin: 0 auto 18px;
  }
  .success-title { font-size: 24px; font-weight: 700; color: var(--dark); margin-bottom: 8px; }
  .success-body  { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 24px; }
  .btn-go-login {
    display: inline-flex; align-items: center; gap: 9px;
    background: var(--dark); color: var(--white);
    padding: 13px 34px; border-radius: 6px; font-size: 15px; font-weight: 700;
    text-decoration: none; transition: background .2s;
  }
  .btn-go-login:hover { background: var(--dark-hover); color: var(--white); }
  .auth-note { font-size: 12px; color: #bbb; text-align: center; margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 6px; }
  .hint-txt { font-size: 12px; color: #999; margin-top: 10px; }
  #loadingState, #errorState, #verifyView, #successView { display: none; }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center">
      <a href="home.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal"><img src="alapan.png" alt="Barangay Alapan I-A Logo"></div>
        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">Alapan I-A</span>
        </div>
      </a>
    </div>
  </div>
</nav>

<div class="flex-grow-1">
  <div class="container">
    <div class="verify-card">

      <div id="loadingState">
        <div class="spinner"></div>
        <p style="color:#999;font-size:14px;margin:0;">Sending verification email…</p>
      </div>

      <div id="errorState">
        <div class="verify-icon" style="background:#fff0f0;border-color:#f5c0c0;">
          <i class="fa-solid fa-triangle-exclamation" style="color:var(--red);"></i>
        </div>
        <div class="verify-title" style="color:var(--red);">Something went wrong</div>
        <p id="errorStateMsg" style="font-size:14px;color:#666;margin-bottom:20px;line-height:1.6;"></p>
        <a href="register.php" style="color:var(--dark);font-weight:700;font-size:14px;">
          <i class="fa-solid fa-arrow-left me-1"></i>Back to Register
        </a>
      </div>

      <div id="verifyView">
        <div class="verify-icon">
          <i class="fa-solid fa-envelope-open-text"></i>
        </div>
        <div class="verify-title">Check Your Email</div>
        <div class="verify-sub">
          A verification link was sent to<br>
          <strong><?= htmlspecialchars($email) ?></strong><br>
          Click the link in your inbox, then press the button below.
        </div>

        <div id="inlineErrorBox" class="alert-err" style="display:none;">
          <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
          <span id="inlineErrorMsg"></span>
        </div>

        <button class="btn-verify" id="checkBtn" onclick="checkVerification()">
          <i class="fa-solid fa-shield-check"></i>
          I've Verified My Email
        </button>

        <button class="btn-resend" id="resendBtn" onclick="resendEmail()">
          Resend verification email
        </button>
        <div class="hint-txt" id="resendTimer"></div>
      </div>

      <div id="successView">
        <div class="success-icon-wrap"><i class="fa-solid fa-check"></i></div>
        <div class="success-title">Email Verified!</div>
        <div class="success-body">
          Your account has been created successfully.<br>
          It is pending barangay staff review.<br>
          You will be notified once it is approved.
        </div>
        <a href="login.php" class="btn-go-login">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>Go to Login
        </a>
      </div>

    </div>
    <div class="auth-note">
      <i class="fa-solid fa-shield-halved"></i>
      Barangay Alapan I-A &middot; Imus, Cavite &middot; Official Portal &middot; 2026
    </div>
  </div>
</div>

<script type="module">
  import { initializeApp }                       from "https://www.gstatic.com/firebasejs/12.13.0/firebase-app.js";
  import { getAuth, sendEmailVerification,
           reload, signInWithEmailAndPassword,
           signOut }                             from "https://www.gstatic.com/firebasejs/12.13.0/firebase-auth.js";

  const firebaseConfig = {
    apiKey:            "AIzaSyCwXnvovYNhm5QuohqBOQ1JAhu6wTT03hI",
    authDomain:        "barangaykonek-fcb6b.firebaseapp.com",
    projectId:         "barangaykonek-fcb6b",
    storageBucket:     "barangaykonek-fcb6b.firebasestorage.app",
    messagingSenderId: "346298718713",
    appId:             "1:346298718713:web:4372068ab0819104648528",
    measurementId:     "G-TXH6H6GQN9"
  };

  const app  = initializeApp(firebaseConfig);
  const auth = getAuth(app);

  const EMAIL   = <?= json_encode($email) ?>;
  const TEMP_PW = <?= json_encode($tempPw) ?>;

  let firebaseUser     = null;
  let resendOnCooldown = false;

  function show(id) {
    ['loadingState','errorState','verifyView','successView']
      .forEach(s => document.getElementById(s).style.display = s === id ? 'block' : 'none');
  }

  function showInlineError(msg) {
    document.getElementById('inlineErrorMsg').textContent = msg;
    document.getElementById('inlineErrorBox').style.display = 'flex';
  }

  function hideInlineError() {
    document.getElementById('inlineErrorBox').style.display = 'none';
  }

  async function init() {
    show('loadingState');
    try {
      const cred   = await signInWithEmailAndPassword(auth, EMAIL, TEMP_PW);
      firebaseUser = cred.user;
      if (!firebaseUser.emailVerified) {
        await sendEmailVerification(firebaseUser);
      }
      show('verifyView');
    } catch (err) {
      document.getElementById('errorStateMsg').textContent =
        'Could not send verification email. Please go back and try again. (' + err.message + ')';
      show('errorState');
    }
  }

  window.checkVerification = async function () {
    hideInlineError();
    const btn = document.getElementById('checkBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking…';

    try {
      await reload(firebaseUser);

      if (!firebaseUser.emailVerified) {
        showInlineError('Your email is not verified yet. Please click the link in your inbox first, then try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> I\'ve Verified My Email';
        return;
      }

      const res  = await fetch('verify_email.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'ajax_mark_verified=1'
      });
      const data = await res.json();

      if (data.ok) {
        await signOut(auth);
        show('successView');
      } else {
        showInlineError('Server error: ' + (data.error ?? 'Unknown error.'));
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> I\'ve Verified My Email';
      }
    } catch (err) {
      showInlineError('An error occurred. Please try again.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> I\'ve Verified My Email';
    }
  };

  window.resendEmail = async function () {
    if (resendOnCooldown) return;
    hideInlineError();
    try {
      await sendEmailVerification(firebaseUser);
      resendOnCooldown = true;
      let secs     = 60;
      const btn    = document.getElementById('resendBtn');
      const txt    = document.getElementById('resendTimer');
      btn.disabled = true;
      const tick   = setInterval(() => {
        secs--;
        txt.textContent = 'You can resend in ' + secs + 's';
        if (secs <= 0) {
          clearInterval(tick);
          btn.disabled     = false;
          resendOnCooldown = false;
          txt.textContent  = '';
        }
      }, 1000);
    } catch (err) {
      showInlineError('Could not resend. Please wait a moment and try again.');
    }
  };

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>