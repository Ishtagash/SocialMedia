<?php
session_start();

$serverName = "LAPTOP-8KOIBQER\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "SocialMedia",
    "Uid"      => "",
    "PWD"      => "",
    "TrustServerCertificate" => true
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

$dbError = false;
if ($conn == false) {
    $dbError = true;
}

$error    = null;
$urlError = isset($_GET['error']) ? trim($_GET['error']) : '';

if (!$dbError && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } else {
        $sql  = "SELECT * FROM USERS WHERE USERNAME = ?";
        $stmt = sqlsrv_query($conn, $sql, [$username]);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (rtrim($user['STATUS']) === 'PENDING') {
                header('Location: login.php?error=pending');
                exit();
            } elseif ($password === rtrim($user['PASSWORD'])) {
                $_SESSION['user_id']  = $user['USER_ID'];
                $_SESSION['username'] = $user['USERNAME'];
                $_SESSION['role']     = strtolower(trim($user['ROLE']));
                $_SESSION['position'] = strtolower(trim($user['POSITION'] ?? ''));

                if ($_SESSION['role'] === 'superadmin') {
                    header('Location: superadmindashboard.php');
                    exit();
                } elseif ($_SESSION['role'] === 'staff') {
                    $position = $_SESSION['position'];
                    if ($position === 'captain') {
                        header('Location: captaindashboard.php');
                    } elseif ($position === 'secretary') {
                        header('Location: secretarydashboard.php');
                    } elseif ($position === 'treasurer') {
                        header('Location: treasurerdashboard.php');
                    } elseif ($position === 'kagawad') {
                        header('Location: kagawaddashboard.php');
                    } else {
                        header('Location: staffdashboard.php');
                    }
                    exit();
                } else {
                    header('Location: residentdashboard.php');
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Alapan 1-A Login</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
  :root {
    --dark:      #051650;
    --dark-hover:#0a2470;
    --lime:      #ccff00;
    --white:     #ffffff;
    --red:       #e03030;
    --green:     #2e7d32;
    --orange:    #d97706;
    --blue:      #2563eb;
  }

  * { box-sizing: border-box; }

  body {
    font-family: Arial, sans-serif;
    background: #eef0f8;
    color: var(--dark);
    min-height: 100vh;
    margin: 0;
  }

  .site-nav {
    background: var(--dark);
    border-bottom: 3px solid var(--lime);
    padding: 10px 0;
  }

  .nav-seal {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
  }

  .nav-seal img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .nav-brgy {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--lime);
    display: block;
    line-height: 1.2;
  }

  .nav-name {
    font-size: 17px;
    font-weight: 700;
    color: var(--white);
    line-height: 1.2;
  }

  .nav-back {
    font-size: 13px;
    color: rgba(255,255,255,0.60);
    text-decoration: none;
    transition: color .2s;
  }

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
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: var(--lime);
    color: var(--dark);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 4px;
    margin-bottom: 18px;
  }

  .auth-title {
    font-size: 26px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
  }

  .auth-sub {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
  }

  .alert-err {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #fff0f0;
    border: 1px solid #f5c0c0;
    border-left: 4px solid var(--red);
    border-radius: 6px;
    padding: 11px 14px;
    font-size: 14px;
    color: var(--red);
    margin-bottom: 18px;
  }

  .field-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #555;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .field-label i {
    color: var(--dark);
    width: 14px;
  }

  .password-wrapper {
    position: relative;
  }

  .field-input {
    width: 100%;
    background: var(--white);
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px 44px 12px 14px;
    color: var(--dark);
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  .field-input:focus {
    border-color: var(--dark);
    box-shadow: 0 0 0 3px rgba(5,22,80,.10);
  }

  .toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: #999;
    font-size: 15px;
    line-height: 1;
    transition: color .2s;
  }

  .toggle-password:hover { color: var(--dark); }

  .forgot-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
    margin-bottom: 24px;
  }

  .forgot-link {
    font-size: 13px;
    font-weight: 700;
    color: var(--dark);
    text-decoration: none;
  }

  .forgot-link:hover { text-decoration: underline; }

  .btn-login {
    width: 100%;
    background: var(--dark);
    color: var(--white);
    border: none;
    padding: 14px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, sans-serif;
    transition: background .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
  }

  .btn-login:hover { background: var(--dark-hover); }

  .btn-google {
    width: 100%;
    background: var(--white);
    color: var(--dark);
    border: 1px solid #ccc;
    padding: 13px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, sans-serif;
    transition: background .2s, border-color .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    text-decoration: none;
  }

  .btn-google:hover {
    background: rgba(5,22,80,0.06);
    color: var(--dark);
    border-color: var(--dark);
  }

  .divider-line {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #aaa;
    font-size: 12px;
  }

  .divider-line::before,
  .divider-line::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e5e5;
  }

  .register-link-text {
    font-size: 14px;
    color: #666;
    text-align: center;
  }

  .register-link-text a {
    color: var(--dark);
    font-weight: 700;
    text-decoration: none;
  }

  .register-link-text a:hover { text-decoration: underline; }

  .auth-note {
    font-size: 12px;
    color: #888;
    text-align: center;
    margin-top: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  @media (max-width: 480px) {
    .auth-card { padding: 28px 20px; }
  }

  /* ── Modals ── */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,22,80,0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .modal-overlay.active { display: flex; }

  .modal-box {
    background: var(--white);
    border-radius: 12px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(5,22,80,0.22);
    overflow: hidden;
    animation: modalIn .22s ease;
  }

  @keyframes modalIn {
    from { transform: translateY(18px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }

  .modal-top {
    padding: 32px 32px 24px;
    text-align: center;
  }

  .modal-icon-wrap {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 26px;
  }

  .modal-icon-wrap.orange {
    background: #fff7ed;
    color: var(--orange);
    border: 2px solid #fed7aa;
  }

  .modal-icon-wrap.blue {
    background: #eff6ff;
    color: var(--blue);
    border: 2px solid #bfdbfe;
  }

  .modal-icon-wrap.red {
    background: #fff0f0;
    color: var(--red);
    border: 2px solid #f5c0c0;
  }

  .modal-title {
    font-size: 19px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
  }

  .modal-desc {
    font-size: 14px;
    color: #555;
    line-height: 1.65;
    margin: 0;
  }

  .modal-divider { height: 1px; background: #f0f0f0; }

  .modal-footer {
    padding: 18px 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .modal-btn-primary {
    width: 100%;
    background: var(--dark);
    color: var(--white);
    border: none;
    padding: 13px;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, sans-serif;
    transition: background .2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .modal-btn-primary:hover {
    background: var(--dark-hover);
    color: var(--white);
  }

  .modal-btn-secondary {
    width: 100%;
    background: transparent;
    color: #777;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, sans-serif;
    transition: border-color .2s, color .2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .modal-btn-secondary:hover {
    border-color: var(--dark);
    color: var(--dark);
  }
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<!-- MODAL: Account Pending -->
<div class="modal-overlay" id="modalPending">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-icon-wrap orange">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div class="modal-title">Account Pending Verification</div>
      <p class="modal-desc">
        Your account has been registered but is currently awaiting verification by a barangay administrator. You will be able to log in once your account has been approved.
      </p>
    </div>
    <div class="modal-divider"></div>
    <div class="modal-footer">
      <a href="login.php" class="modal-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Login
      </a>
      <a href="home.html" class="modal-btn-secondary">
        <i class="fa-solid fa-house"></i> Go to Home
      </a>
    </div>
  </div>
</div>

<!-- MODAL: Google Account Not Found -->
<div class="modal-overlay" id="modalNoGoogle">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-icon-wrap blue">
        <i class="fa-brands fa-google"></i>
      </div>
      <div class="modal-title">No Account Found</div>
      <p class="modal-desc">
        The Google account you used is not linked to any Barangay Alapan 1-A profile. Please register first before signing in with Google.
      </p>
    </div>
    <div class="modal-divider"></div>
    <div class="modal-footer">
      <a href="register.php" class="modal-btn-primary">
        <i class="fa-solid fa-user-plus"></i> Create an Account
      </a>
      <a href="login.php" class="modal-btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Login
      </a>
    </div>
  </div>
</div>

<!-- MODAL: Database Connection Error -->
<div class="modal-overlay" id="modalDbError">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-icon-wrap red">
        <i class="fa-solid fa-server"></i>
      </div>
      <div class="modal-title">Service Unavailable</div>
      <p class="modal-desc">
        We were unable to connect to the database at this time. Please try again in a few moments or contact the barangay administrator if the problem persists.
      </p>
    </div>
    <div class="modal-divider"></div>
    <div class="modal-footer">
      <a href="login.php" class="modal-btn-primary">
        <i class="fa-solid fa-rotate-right"></i> Try Again
      </a>
      <a href="home.html" class="modal-btn-secondary">
        <i class="fa-solid fa-house"></i> Go to Home
      </a>
    </div>
  </div>
</div>

<!-- MODAL: Registration Successful -->
<div class="modal-overlay" id="modalRegistered">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-icon-wrap" style="background:rgba(204,255,0,0.15);border:2px solid var(--lime);">
        <i class="fa-solid fa-circle-check" style="color:var(--dark);"></i>
      </div>
      <div class="modal-title">Account Created!</div>
      <p class="modal-desc">
        Your account has been successfully created and is now <strong>pending review</strong> by a barangay administrator. You will be notified once your account is approved and ready to use.
      </p>
    </div>
    <div class="modal-divider"></div>
    <div class="modal-footer">
      <a href="login.php" class="modal-btn-primary">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
      </a>
      <a href="home.html" class="modal-btn-secondary">
        <i class="fa-solid fa-house"></i> Go to Home
      </a>
    </div>
  </div>
</div>

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.html" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal">
          <img src="alapan.png" alt="Barangay Alapan 1-A Logo">
        </div>
        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">Alapan 1-A</span>
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

      <div class="auth-badge">
        <i class="fa-solid fa-landmark"></i>Resident Portal
      </div>

      <h1 class="auth-title">Welcome back.</h1>

      <p class="auth-sub mb-4">
        Login with your username to access barangay services and submit requests online.
      </p>

      <?php if ($error !== null): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="mb-3">
          <label class="field-label" for="username">
            <i class="fa-solid fa-user"></i>Username
          </label>
          <input
            type="text"
            class="field-input"
            id="username"
            name="username"
            placeholder="Enter your username"
            autocomplete="username"
            required
          >
        </div>

        <div class="mb-0">
          <label class="field-label" for="password">
            <i class="fa-solid fa-lock"></i>Password
          </label>
          <div class="password-wrapper">
            <input
              type="password"
              class="field-input"
              id="password"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            >
            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="forgot-row">
          <a href="forgotpassword.php" class="forgot-link">
            <i class="fa-solid fa-key me-1"></i>Forgot password?
          </a>
        </div>

        <button type="submit" class="btn-login">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          Login to My Account
        </button>
      </form>

      <div class="divider-line my-4">or</div>

      <a href="google-login.php" class="btn-google">
        <i class="fa-brands fa-google"></i>
        Continue with Google
      </a>

      <div class="register-link-text mt-4">
        Don't have an account?
        <a href="register.php">
          <i class="fa-solid fa-user-plus me-1"></i>Create one for free
        </a>
      </div>
    </div>

    <div class="auth-note">
      <i class="fa-solid fa-shield-halved"></i>
      Barangay Alapan 1-A &middot; Official Portal &middot; 2026
    </div>
  </div>
</div>

<script>
  var toggleBtn  = document.getElementById('togglePassword');
  var passwordEl = document.getElementById('password');
  var toggleIcon = document.getElementById('toggleIcon');

  toggleBtn.addEventListener('click', function() {
    if (passwordEl.type === 'password') {
      passwordEl.type = 'text';
      toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      passwordEl.type = 'password';
      toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  });

  var urlError = <?php echo json_encode($urlError); ?>;
  var dbError  = <?php echo json_encode($dbError); ?>;

  if (dbError) {
    document.getElementById('modalDbError').classList.add('active');
  } else if (urlError === 'pending') {
    document.getElementById('modalPending').classList.add('active');
  } else if (urlError === 'no_google_account') {
    document.getElementById('modalNoGoogle').classList.add('active');
  } else if (new URLSearchParams(window.location.search).get('registered') === '1') {
    document.getElementById('modalRegistered').classList.add('active');
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        overlay.classList.remove('active');
        if (window.location.search) {
          window.history.replaceState({}, document.title, 'login.php');
        }
      }
    });
  });
</script>

</body>
</html>