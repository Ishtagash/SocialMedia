<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Barangay Alapan I-A</title>

<!-- Bootstrap 5.3 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>
  :root {
    --dark:       #051650;
    --dark-hover: #0a2470;
    --lime:       #ccff00;
    --white:      #ffffff;
    --red:        #e03030;
  }

  body { font-family: Arial, sans-serif; background: #eef0f8; color: var(--dark); min-height: 100vh; }

  /* ─── NAV ─── */
  .site-nav { background: var(--dark); border-bottom: 3px solid var(--lime); padding: 10px 0; }
  .nav-seal { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
  .nav-seal img { width: 50px; height: 50px; object-fit: contain; border-radius: 50%; }
  .nav-brgy { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--lime); display: block; line-height: 1.2; }
  .nav-name  { font-size: 17px; font-weight: 700; color: var(--white); line-height: 1.2; }
  .nav-back  { font-size: 13px; color: rgba(255,255,255,0.6); text-decoration: none; }
  .nav-back:hover { color: var(--lime); }

  /* ─── CARD ─── */
  .auth-card {
    background: var(--white);
    border: 1px solid rgba(5,22,80,0.12);
    border-top: 4px solid var(--dark);
    border-radius: 10px;
    padding: 40px 36px;
    box-shadow: 0 6px 30px rgba(5,22,80,0.09);
  }
  .auth-badge {
    display: inline-block;
    background: var(--lime); color: var(--dark);
    font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    padding: 4px 12px; border-radius: 4px; margin-bottom: 18px;
  }
  .auth-title { font-size: 26px; font-weight: 700; color: var(--dark); margin-bottom: 6px; }
  .auth-sub   { font-size: 14px; color: #666; line-height: 1.6; }

  /* ─── ALERT ─── */
  .alert-err {
    background: #fff0f0; border: 1px solid #f5c0c0; border-left: 4px solid var(--red);
    border-radius: 6px; padding: 11px 14px; font-size: 14px; color: var(--red);
    display: none; gap: 8px; align-items: flex-start;
  }
  .alert-err.show { display: flex; }

  /* ─── FORM ─── */
  .field-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #555; margin-bottom: 6px; display: block; }
  .field-input {
    width: 100%; background: var(--white); border: 1px solid #ccc; border-radius: 6px;
    padding: 12px 14px; color: var(--dark); font-family: Arial, sans-serif; font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .field-input:focus { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(5,22,80,.1); }
  .field-input.is-err { border-color: var(--red); box-shadow: 0 0 0 3px rgba(224,48,48,.1); }
  .field-error { font-size: 12px; color: var(--red); display: none; margin-top: 4px; }
  .field-error.show { display: block; }

  .pw-wrap { position: relative; }
  .pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 15px; color: #999; }
  .pw-toggle:hover { color: var(--dark); }

  .check-label { font-size: 13px; color: #555; cursor: pointer; user-select: none; }
  .check-label input { accent-color: var(--dark); }
  .forgot-link { font-size: 13px; font-weight: 700; color: var(--dark); text-decoration: none; }
  .forgot-link:hover { text-decoration: underline; }

  .btn-login {
    width: 100%; background: var(--dark); color: var(--white); border: none;
    padding: 14px; border-radius: 6px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
  }
  .btn-login:hover { background: var(--dark-hover); }
  .btn-login:disabled { opacity: .6; pointer-events: none; }

  .divider-line { display: flex; align-items: center; gap: 12px; color: #ccc; font-size: 12px; }
  .divider-line::before, .divider-line::after { content: ''; flex: 1; height: 1px; background: #e5e5e5; }

  .register-link-text { font-size: 14px; color: #666; text-align: center; }
  .register-link-text a { color: var(--dark); font-weight: 700; text-decoration: none; }
  .register-link-text a:hover { text-decoration: underline; }

  .auth-note { font-size: 12px; color: #bbb; text-align: center; margin-top: 16px; }

  @media (max-width: 480px) {
    .auth-card { padding: 28px 20px; }
    .site-nav .container { padding: 0 16px; }
  }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- ═══ NAV ═══ -->
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
      <a href="home.html" class="nav-back">← Back to Home</a>
    </div>
  </div>
</nav>

<!-- ═══ CONTENT ═══ -->
<div class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
  <div class="w-100" style="max-width:440px; padding: 0 16px;">

    <div class="auth-card">
      <div class="auth-badge">🏛 Resident Portal</div>
      <h1 class="auth-title">Welcome back.</h1>
      <p class="auth-sub mb-4">Login with your username or email to access barangay services and submit requests online.</p>

      <div class="alert-err mb-3" id="alertBox">
        <span>⚠️</span>
        <span id="alertMsg">Invalid credentials. Please try again.</span>
      </div>

      <form id="loginForm" novalidate>

        <div class="mb-3">
          <label class="field-label" for="identifier">Username or Email Address</label>
          <input type="text" class="field-input" id="identifier"
            placeholder="e.g. juandelacruz or juan@email.com" autocomplete="username">
          <div class="field-error" id="identifierErr">Please enter your username or email address.</div>
        </div>

        <div class="mb-3">
          <label class="field-label" for="password">Password</label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="password"
              placeholder="Enter your password" autocomplete="current-password">
            <button type="button" class="pw-toggle" onclick="togglePw('password')" title="Show/hide">👁</button>
          </div>
          <div class="field-error" id="pwErr">Password is required.</div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <label class="check-label d-flex align-items-center gap-2">
            <input type="checkbox" id="remember"> Keep me logged in
          </label>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-login" id="submitBtn">Login to My Account →</button>
      </form>

      <div class="divider-line my-4">or</div>
      <div class="register-link-text">Don't have an account? <a href="register.php">Create one for free →</a></div>
    </div>

    <div class="auth-note">🔒 Barangay Alapan I-A · Imus, Cavite · Official Portal · 2026</div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePw(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
  }

  document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const identifier = document.getElementById('identifier');
    const password   = document.getElementById('password');
    const identifierErr = document.getElementById('identifierErr');
    const pwErr      = document.getElementById('pwErr');
    const alertBox   = document.getElementById('alertBox');
    let valid = true;

    identifier.classList.remove('is-err'); identifierErr.classList.remove('show');
    password.classList.remove('is-err');   pwErr.classList.remove('show');
    alertBox.classList.remove('show');

    if (!identifier.value.trim()) {
      identifier.classList.add('is-err'); identifierErr.classList.add('show'); valid = false;
    }
    if (!password.value) {
      password.classList.add('is-err'); pwErr.classList.add('show'); valid = false;
    }
    if (!valid) return;

    const btn = document.getElementById('submitBtn');
    btn.textContent = 'Logging in…';
    btn.disabled = true;

    setTimeout(() => {
      btn.textContent = 'Login to My Account →';
      btn.disabled = false;
      alertBox.classList.add('show');
      document.getElementById('alertMsg').textContent =
        'Invalid username/email or password. Please check your credentials or register if you don\'t have an account yet.';
    }, 1400);
  });
</script>
</body>
</html>