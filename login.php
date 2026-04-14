<?php
session_start();

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = [
    "Database" => "SocialMedia",
    "Uid"      => "",
    "PWD"      => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {

    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql    = "SELECT * FROM USERS WHERE (USERNAME = ? OR EMAIL = ?)";
    $params = [$user, $user];
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($row && rtrim($row['STATUS']) === 'PENDING') {
        header("Location: login.php?error=pending");
        exit();
    }

    if ($row && $pass === $row['PASSWORD']) {
        $_SESSION['user_id']  = $row['USER_ID'];
        $_SESSION['username'] = $row['USERNAME'];
        $_SESSION['role']     = strtolower(rtrim($row['ROLE']));

        $updateSql  = "UPDATE USERS SET LAST_LOGIN = GETDATE() WHERE USER_ID = ?";
        $updateStmt = sqlsrv_query($conn, $updateSql, [$row['USER_ID']]);

        if ($_SESSION['role'] === 'superadmin') {
            header("Location: superadmindashboard.php");
        } elseif ($_SESSION['role'] === 'staff') {
            header("Location: staffdashboard.php");
        } else {
            header("Location: residentdashboard.php");
        }
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'find_account') {
    $identifier = trim($_POST['identifier']);
    $sql        = "SELECT USER_ID FROM USERS WHERE USERNAME = ? OR EMAIL = ?";
    $stmt       = sqlsrv_query($conn, $sql, [$identifier, $identifier]);
    $row        = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($row) {
        header("Location: login.php?step=reset&uid=" . $row['USER_ID']);
    } else {
        header("Location: login.php?step=forgot&error=notfound");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $uid     = (int)$_POST['uid'];
    $newpass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($newpass !== $confirm) {
        header("Location: login.php?step=reset&uid=" . $uid . "&error=mismatch");
        exit();
    }

    if (strlen($newpass) < 16 || strlen($newpass) > 32) {
        header("Location: login.php?step=reset&uid=" . $uid . "&error=length");
        exit();
    }

    $sql  = "UPDATE USERS SET PASSWORD = ? WHERE USER_ID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$newpass, $uid]);

    header("Location: login.php?success=reset");
    exit();
}

$step = $_GET['step'] ?? 'login';
$uid  = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Barangay Alapan I-A</title>
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
  }

  body { font-family: Arial, sans-serif; background: #eef0f8; color: var(--dark); min-height: 100vh; }

  .site-nav { background: var(--dark); border-bottom: 3px solid var(--lime); padding: 10px 0; }
  .nav-seal { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
  .nav-seal img { width: 100%; height: 100%; object-fit: cover; }
  .nav-brgy { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--lime); display: block; line-height: 1.2; }
  .nav-name  { font-size: 17px; font-weight: 700; color: var(--white); line-height: 1.2; }
  .nav-back  { font-size: 13px; color: rgba(255,255,255,0.60); text-decoration: none; transition: color .2s; }
  .nav-back:hover { color: var(--lime); }

  .auth-card {
    background: var(--white);
    border: 1px solid rgba(5,22,80,0.12);
    border-top: 4px solid var(--dark);
    border-radius: 10px;
    padding: 40px 36px;
    box-shadow: 0 6px 30px rgba(5,22,80,0.09);
  }
  .auth-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--lime); color: var(--dark);
    font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    padding: 4px 12px; border-radius: 4px; margin-bottom: 18px;
  }
  .auth-title { font-size: 26px; font-weight: 700; color: var(--dark); margin-bottom: 6px; }
  .auth-sub   { font-size: 14px; color: #666; line-height: 1.6; }

  .alert-err {
    display: flex; align-items: flex-start; gap: 10px;
    background: #fff0f0; border: 1px solid #f5c0c0; border-left: 4px solid var(--red);
    border-radius: 6px; padding: 11px 14px; font-size: 14px; color: var(--red);
    margin-bottom: 18px;
  }
  .alert-ok {
    display: flex; align-items: flex-start; gap: 10px;
    background: #f0fff0; border: 1px solid #b2dfb2; border-left: 4px solid var(--green);
    border-radius: 6px; padding: 11px 14px; font-size: 14px; color: var(--green);
    margin-bottom: 18px;
  }

  .field-label {
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    color: #555; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
  }
  .field-label i { color: var(--dark); width: 14px; }
  .field-input {
    width: 100%; background: var(--white); border: 1px solid #ccc; border-radius: 6px;
    padding: 12px 14px; color: var(--dark); font-family: Arial, sans-serif; font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .field-input:focus { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(5,22,80,.10); }

  .pw-wrap { position: relative; }
  .pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: #bbb; font-size: 14px;
    transition: color .2s;
  }
  .pw-toggle:hover { color: var(--dark); }

  .pw-bars { display: flex; gap: 4px; margin-bottom: 3px; }
  .pw-bar { height: 4px; flex: 1; border-radius: 3px; background: #e0e0e0; transition: background .25s; }
  .pw-bar.weak   { background: var(--red); }
  .pw-bar.fair   { background: #e0a000; }
  .pw-bar.strong { background: var(--green); }
  .pw-bar-label  { font-size: 11px; color: #999; }

  .forgot-link { font-size: 13px; font-weight: 700; color: var(--dark); text-decoration: none; float: right; }
  .forgot-link:hover { text-decoration: underline; }

  .btn-login {
    width: 100%; background: var(--dark); color: var(--white); border: none;
    padding: 14px; border-radius: 6px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 9px;
  }
  .btn-login:hover { background: var(--dark-hover); }

  .btn-outline {
    width: 100%; background: transparent; color: var(--dark); border: 1px solid var(--dark);
    padding: 13px; border-radius: 6px; font-size: 14px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 9px;
    text-decoration: none;
  }
  .btn-outline:hover { background: rgba(5,22,80,0.06); }

  .divider-line { display: flex; align-items: center; gap: 12px; color: #ccc; font-size: 12px; }
  .divider-line::before, .divider-line::after { content: ''; flex: 1; height: 1px; background: #e5e5e5; }

  .register-link-text { font-size: 14px; color: #666; text-align: center; }
  .register-link-text a { color: var(--dark); font-weight: 700; text-decoration: none; }
  .register-link-text a:hover { text-decoration: underline; }

  .auth-note { font-size: 12px; color: #bbb; text-align: center; margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 6px; }

  .step-label {
    font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    color: #aaa; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
  }
  .step-label span { color: var(--dark); }

  @media (max-width: 480px) { .auth-card { padding: 28px 20px; } }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.html" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal">
          <img src="alapan.png" alt="Barangay Alapan I-A Logo">
        </div>
        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">Alapan I-A</span>
        </div>
      </a>
      <a href="home.html" class="nav-back">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
      </a>
    </div>
  </div>
</nav>

<div class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
  <div class="w-100" style="max-width:440px; padding:0 16px;">
    <div class="auth-card">

      <?php if ($step === 'login'): ?>

      <div class="auth-badge">
        <i class="fa-solid fa-landmark"></i>Resident Portal
      </div>
      <h1 class="auth-title">Welcome back.</h1>
      <p class="auth-sub mb-4">Login with your username or email to access barangay services and submit requests online.</p>

      <?php if (isset($_GET['success']) && $_GET['success'] === 'reset'): ?>
      <div class="alert-ok">
        <i class="fa-solid fa-circle-check mt-1 flex-shrink-0"></i>
        <span>Your password has been reset successfully. You can now log in.</span>
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'pending'): ?>
      <div class="alert-err">
        <i class="fa-solid fa-clock mt-1 flex-shrink-0"></i>
        <span>Your account is still pending review. Please wait for a staff member to approve your registration.</span>
      </div>
      <?php elseif (isset($_GET['error'])): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span>Invalid username/email or password. Please try again.</span>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="action" value="login">

        <div class="mb-3">
          <label class="field-label" for="username">
            <i class="fa-solid fa-user"></i>Username or Email Address
          </label>
          <input type="text" class="field-input" id="username" name="username"
            placeholder="e.g. juandelacruz or juan@email.com"
            autocomplete="username" required>
        </div>

        <div class="mb-2">
          <label class="field-label" for="password">
            <i class="fa-solid fa-lock"></i>Password
          </label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="password" name="password"
              placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('password','pwEyeIcon')" title="Show/hide">
              <i class="fa-solid fa-eye" id="pwEyeIcon"></i>
            </button>
          </div>
        </div>

        <div class="mb-4 text-end">
          <a href="login.php?step=forgot" class="forgot-link">
            <i class="fa-solid fa-key me-1"></i>Forgot password?
          </a>
        </div>

        <button type="submit" class="btn-login">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          Login to My Account
        </button>
      </form>

      <div class="divider-line my-4">or</div>
      <div class="register-link-text">
        Don't have an account?
        <a href="register.php"><i class="fa-solid fa-user-plus me-1"></i>Create one for free</a>
      </div>

      <?php elseif ($step === 'forgot'): ?>

      <div class="auth-badge">
        <i class="fa-solid fa-key"></i>Reset Password
      </div>
      <h1 class="auth-title">Forgot password?</h1>
      <p class="auth-sub mb-4">Enter your username or email address and we will let you set a new password.</p>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span>No account found with that username or email address.</span>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="action" value="find_account">

        <div class="mb-4">
          <label class="field-label" for="identifier">
            <i class="fa-solid fa-user"></i>Username or Email Address
          </label>
          <input type="text" class="field-input" id="identifier" name="identifier"
            placeholder="e.g. juandelacruz or juan@email.com" required>
        </div>

        <button type="submit" class="btn-login mb-3">
          <i class="fa-solid fa-magnifying-glass"></i>
          Find My Account
        </button>
        <a href="login.php" class="btn-outline">
          <i class="fa-solid fa-arrow-left"></i>Back to Login
        </a>
      </form>

      <?php elseif ($step === 'reset' && $uid > 0): ?>

      <div class="auth-badge">
        <i class="fa-solid fa-lock-open"></i>New Password
      </div>
      <h1 class="auth-title">Set new password.</h1>
      <p class="auth-sub mb-4">Choose a strong password between 16 and 32 characters.</p>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'mismatch'): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span>Passwords do not match. Please try again.</span>
      </div>
      <?php elseif (isset($_GET['error']) && $_GET['error'] === 'length'): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span>Password must be between 16 and 32 characters.</span>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="uid" value="<?= $uid ?>">

        <div class="mb-3">
          <label class="field-label" for="new_password">
            <i class="fa-solid fa-lock"></i>New Password
          </label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="new_password" name="new_password"
              placeholder="Min. 16 characters" minlength="16" maxlength="32"
              oninput="checkStrength()" required>
            <button type="button" class="pw-toggle" onclick="togglePw('new_password','eye1')" title="Show/hide">
              <i class="fa-solid fa-eye" id="eye1"></i>
            </button>
          </div>
          <div class="mt-2">
            <div class="pw-bars">
              <div class="pw-bar" id="b1"></div>
              <div class="pw-bar" id="b2"></div>
              <div class="pw-bar" id="b3"></div>
              <div class="pw-bar" id="b4"></div>
            </div>
            <div class="pw-bar-label" id="pw-label">Enter a password</div>
          </div>
        </div>

        <div class="mb-4">
          <label class="field-label" for="confirm_password">
            <i class="fa-solid fa-lock"></i>Confirm New Password
          </label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="confirm_password" name="confirm_password"
              placeholder="Re-enter your password" minlength="16" maxlength="32" required>
            <button type="button" class="pw-toggle" onclick="togglePw('confirm_password','eye2')" title="Show/hide">
              <i class="fa-solid fa-eye" id="eye2"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login mb-3">
          <i class="fa-solid fa-floppy-disk"></i>
          Save New Password
        </button>
        <a href="login.php" class="btn-outline">
          <i class="fa-solid fa-arrow-left"></i>Back to Login
        </a>
      </form>

      <?php endif; ?>

    </div>

    <div class="auth-note">
      <i class="fa-solid fa-shield-halved"></i>
      Barangay Alapan I-A &middot; Imus, Cavite &middot; Official Portal &middot; 2026
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePw(inputId, iconId) {
    const el   = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (el.type === 'password') {
      el.type = 'text';
      icon.className = 'fa-solid fa-eye-slash';
    } else {
      el.type = 'password';
      icon.className = 'fa-solid fa-eye';
    }
  }

  function checkStrength() {
    const pw    = document.getElementById('new_password').value;
    const bars  = ['b1','b2','b3','b4'].map(id => document.getElementById(id));
    let score   = 0;
    if (pw.length >= 16)         score++;
    if (/[A-Z]/.test(pw))        score++;
    if (/[0-9]/.test(pw))        score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    bars.forEach(b => b.className = 'pw-bar');
    const cls    = score <= 1 ? 'weak' : score <= 2 ? 'fair' : 'strong';
    const labels = ['', 'Weak', 'Weak', 'Fair', 'Strong'];
    for (let i = 0; i < score; i++) bars[i].classList.add(cls);
    document.getElementById('pw-label').textContent = score > 0 ? labels[score] : 'Enter a password';
  }
</script>
</body>
</html>