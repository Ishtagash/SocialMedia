<?php
session_start();

$serverName = "LAPTOP-8KOIBQER\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "SocialMedia",
    "Uid" => "",
    "PWD" => "",
    "TrustServerCertificate" => true
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn == false) {
    die(print_r(sqlsrv_errors(), true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM USERS WHERE USERNAME = ?";
    $params = [$username];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && sqlsrv_has_rows($stmt)) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (rtrim($user['STATUS']) === 'PENDING') {
            $error = "Your account is pending verification by the admin.";
        } elseif (password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = strtolower(trim($user['ROLE']));

            if ($_SESSION['role'] === 'superadmin') {
                header('Location: superadmindashboard.php');
                exit();
            } elseif ($_SESSION['role'] === 'staff') {
                header('Location: staffdashboard.php');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BarangayKonek Login</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
/>

<style>
  :root {
    --dark: #051650;
    --dark-hover: #0a2470;
    --lime: #ccff00;
    --white: #ffffff;
    --red: #e03030;
    --green: #2e7d32;
  }

  * {
    box-sizing: border-box;
  }

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

  .nav-back:hover {
    color: var(--lime);
  }

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

  .field-input {
    width: 100%;
    background: var(--white);
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px 14px;
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

  .forgot-link {
    font-size: 13px;
    font-weight: 700;
    color: var(--dark);
    text-decoration: none;
    float: right;
  }

  .forgot-link:hover {
    text-decoration: underline;
  }

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

  .btn-login:hover {
    background: var(--dark-hover);
  }

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

  .register-link-text a:hover {
    text-decoration: underline;
  }

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
    .auth-card {
      padding: 28px 20px;
    }
  }
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.html" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal">
          <img src="alapan.png" alt="Barangay Alapan Logo">
        </div>

        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">BarangayKonek</span>
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

      <?php if(isset($error)): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span><?php echo $error; ?></span>
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

        <div class="mb-2">
          <label class="field-label" for="password">
            <i class="fa-solid fa-lock"></i>Password
          </label>

          <input
            type="password"
            class="field-input"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
          >
        </div>

        <div class="mb-4 text-end">
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
      BarangayKonek &middot; Official Portal &middot; 2026
    </div>
  </div>
</div>

</body>
</html>