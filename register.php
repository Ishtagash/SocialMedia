<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — Barangay Alapan I-A</title>

<!-- Bootstrap 5.3 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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

  /* ─── NAV ─── */
  .site-nav { background: var(--dark); border-bottom: 3px solid var(--lime); padding: 10px 0; }
  .nav-seal { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
  .nav-seal img { width: 50px; height: 50px; object-fit: contain; border-radius: 50%; }
  .nav-brgy { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--lime); display: block; line-height: 1.2; }
  .nav-name  { font-size: 17px; font-weight: 700; color: var(--white); line-height: 1.2; }
  .nav-link-light { font-size: 13px; color: rgba(255,255,255,.6); text-decoration: none; }
  .nav-link-light:hover { color: var(--lime); }

  /* ─── ROLE TOGGLE ─── */
  .role-toggle {
    display: grid; grid-template-columns: 1fr 1fr;
    background: var(--white); border: 2px solid var(--dark);
    border-radius: 8px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(5,22,80,.08);
  }
  .role-btn {
    padding: 14px; font-size: 14px; font-weight: 700;
    border: none; background: var(--white); color: #999;
    cursor: pointer; font-family: Arial, sans-serif;
    transition: background .2s, color .2s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
  }
  .role-btn:first-child { border-right: 2px solid var(--dark); }
  .role-btn.active { background: var(--dark); color: var(--lime); }
  .role-btn:not(.active):hover { background: #eef0f8; }

  /* ─── ADMIN NOTICE ─── */
  .admin-notice {
    display: none;
    background: rgba(5,22,80,.05); border: 1px solid var(--border); border-left: 4px solid var(--dark);
    border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #444; line-height: 1.6;
  }
  .admin-notice.show { display: block; }

  /* ─── CARD ─── */
  .auth-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-top: 4px solid var(--dark);
    border-radius: 10px;
    padding: 36px 40px 40px;
    box-shadow: 0 6px 30px rgba(5,22,80,.09);
  }

  /* ─── SECTION HEADING ─── */
  .form-section-title {
    display: flex; align-items: center; gap: 9px;
    font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    color: var(--white); background: var(--dark);
    padding: 8px 14px; border-radius: 4px;
    margin-bottom: 18px;
  }

  /* ─── FORM FIELDS ─── */
  .field-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #555; margin-bottom: 6px; display: block; }
  .field-label .opt { font-weight: 400; color: #bbb; letter-spacing: 0; text-transform: none; font-size: 11px; }
  .field-input {
    width: 100%; background: var(--white); border: 1px solid #ccc; border-radius: 6px;
    padding: 11px 14px; color: var(--dark); font-family: Arial, sans-serif; font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .field-input:focus { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(5,22,80,.1); }
  .field-input.is-err { border-color: var(--red); box-shadow: 0 0 0 3px rgba(224,48,48,.1); }
  .field-input[readonly] { background: #f4f4f4; color: #999; cursor: not-allowed; }
  .field-error { font-size: 12px; color: var(--red); display: none; margin-top: 4px; }
  .field-error.show { display: block; }

  .pw-wrap { position: relative; }
  .pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 15px; color: #999; }
  .pw-toggle:hover { color: var(--dark); }

  /* ─── PASSWORD STRENGTH ─── */
  .pw-bars { display: flex; gap: 4px; margin-bottom: 3px; }
  .pw-bar { height: 4px; flex: 1; border-radius: 3px; background: #e0e0e0; transition: background .25s; }
  .pw-bar.weak   { background: var(--red); }
  .pw-bar.fair   { background: #e0a000; }
  .pw-bar.strong { background: var(--green); }
  .pw-bar-label  { font-size: 11px; color: #999; }

  /* ─── FILE UPLOAD ─── */
  .upload-zone {
    border: 2px dashed #ccc; border-radius: 6px; padding: 22px 16px; text-align: center;
    cursor: pointer; transition: border-color .2s, background .2s; position: relative;
  }
  .upload-zone:hover { border-color: var(--dark); background: #f5f7ff; }
  .upload-zone.has-file { border-color: var(--green); background: #f4fff4; }
  .upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; padding: 0; border: none;
  }
  .upload-zone-icon { font-size: 26px; margin-bottom: 5px; }
  .upload-zone-text { font-size: 13px; color: #777; }
  .upload-zone-text strong { color: var(--dark); }
  .upload-filename { font-size: 12px; color: var(--green); font-weight: 700; display: none; margin-top: 5px; }

  /* ─── ADMIN EXTRA ─── */
  .admin-fields { display: none; }
  .admin-fields.show { display: block; }

  /* ─── SUBMIT BTN ─── */
  .btn-register {
    width: 100%; background: var(--dark); color: var(--white); border: none;
    padding: 15px; border-radius: 6px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
  }
  .btn-register:hover { background: var(--dark-hover); }
  .btn-register:disabled { opacity: .6; pointer-events: none; }

  .divider-line { display: flex; align-items: center; gap: 12px; color: #ccc; font-size: 12px; }
  .divider-line::before, .divider-line::after { content: ''; flex: 1; height: 1px; background: #e5e5e5; }
  .login-link-text { font-size: 14px; color: #666; text-align: center; }
  .login-link-text a { color: var(--dark); font-weight: 700; text-decoration: none; }
  .login-link-text a:hover { text-decoration: underline; }

  /* ─── SUCCESS OVERLAY ─── */
  .success-overlay {
    display: none; position: fixed; inset: 0; z-index: 1060;
    background: rgba(5,22,80,.72);
    align-items: center; justify-content: center;
  }
  .success-overlay.show { display: flex; }
  .success-box {
    background: var(--white); border-radius: 12px; padding: 48px 40px; max-width: 400px; width: 90%;
    text-align: center; border-top: 5px solid var(--lime);
    box-shadow: 0 24px 64px rgba(5,22,80,.32);
  }
  .success-icon  { font-size: 52px; margin-bottom: 14px; }
  .success-title { font-size: 24px; font-weight: 700; color: var(--dark); margin-bottom: 8px; }
  .success-body  { font-size: 14px; color: #555; line-height: 1.7; }
  .success-admin-note {
    display: none; font-size: 13px; color: #888; line-height: 1.6;
    border-top: 1px solid #eee; padding-top: 12px; margin-top: 12px;
  }
  .btn-go-login {
    display: inline-block; background: var(--dark); color: var(--white);
    padding: 13px 34px; border-radius: 6px; font-size: 15px; font-weight: 700;
    text-decoration: none; margin-top: 24px; transition: background .2s;
  }
  .btn-go-login:hover { background: var(--dark-hover); color: var(--white); }

  .auth-note { font-size: 12px; color: #bbb; text-align: center; margin-top: 16px; }

  @media (max-width: 640px) {
    .auth-card { padding: 28px 20px; }
    .site-nav .container { padding: 0 16px; }
  }
  @media (max-width: 480px) {
    .nav-link-light.d-sm { display: none; }
  }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- ═══ SUCCESS OVERLAY ═══ -->
<div class="success-overlay" id="successOverlay">
  <div class="success-box">
    <div class="success-icon" id="successIcon">🎉</div>
    <div class="success-title" id="successTitle">Account Created!</div>
    <div class="success-body" id="successBody">Welcome to Barangay Alapan I-A's resident portal. Your registration is being processed. You may log in once your account is activated.</div>
    <div class="success-admin-note" id="successAdminNote">⚠️ Admin/Staff accounts require approval by the Punong Barangay before access is granted. You will be notified by email once approved.</div>
    <a href="login.php" class="btn-go-login">Proceed to Login →</a>
  </div>
</div>

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
      <div class="d-flex align-items-center gap-3">
        <a href="login.php" class="nav-link-light">Already registered? Login →</a>
        <a href="home.html" class="nav-link-light">← Home</a>
      </div>
    </div>
  </div>
</nav>

<!-- ═══ CONTENT ═══ -->
<div class="flex-grow-1 py-5">
  <div class="container" style="max-width:660px;">

    <!-- ROLE TOGGLE -->
    <div class="role-toggle mb-3">
      <button class="role-btn active" id="btnResident" onclick="setRole('resident')">👤 Resident Registration</button>
      <button class="role-btn"        id="btnAdmin"    onclick="setRole('admin')">🛡️ Admin / Staff Registration</button>
    </div>

    <!-- ADMIN NOTICE -->
    <div class="admin-notice mb-3" id="adminNotice">
      ⚠️ <strong>Admin/Staff accounts</strong> are for authorized barangay personnel only. Your registration will be reviewed and must be approved by the Punong Barangay before access is granted.
    </div>

    <div class="auth-card">

      <!-- ══ BASIC INFORMATION ══ -->
      <div class="form-section-title">👤 Basic Information</div>
      <div class="row g-3 mb-2">

        <div class="col-6">
          <label class="field-label" for="fname">First Name *</label>
          <input type="text" class="field-input" id="fname" placeholder="Juan">
          <div class="field-error" id="e_fname">First name is required.</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="lname">Last Name *</label>
          <input type="text" class="field-input" id="lname" placeholder="dela Cruz">
          <div class="field-error" id="e_lname">Last name is required.</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="mname">Middle Name <span class="opt">(optional)</span></label>
          <input type="text" class="field-input" id="mname" placeholder="Santos">
        </div>
        <div class="col-6">
          <label class="field-label" for="suffix">Suffix <span class="opt">(optional)</span></label>
          <select class="field-input" id="suffix">
            <option value="">None</option>
            <option>Jr.</option><option>Sr.</option>
            <option>II</option><option>III</option><option>IV</option>
          </select>
        </div>
        <div class="col-6">
          <label class="field-label" for="dob">Date of Birth *</label>
          <input type="date" class="field-input" id="dob">
          <div class="field-error" id="e_dob">Date of birth is required.</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="gender">Gender *</label>
          <select class="field-input" id="gender">
            <option value="">Select…</option>
            <option>Male</option><option>Female</option><option>Prefer not to say</option>
          </select>
          <div class="field-error" id="e_gender">Please select your gender.</div>
        </div>

      </div>

      <!-- ══ CONTACT INFORMATION ══ -->
      <div class="form-section-title mt-4">📞 Contact Information</div>
      <div class="row g-3 mb-2">

        <div class="col-6">
          <label class="field-label" for="mobile">Mobile Number *</label>
          <input type="tel" class="field-input" id="mobile" placeholder="09XX-XXX-XXXX">
          <div class="field-error" id="e_mobile">Please enter a valid mobile number.</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="email">Email Address *</label>
          <input type="email" class="field-input" id="email" placeholder="juan@email.com">
          <div class="field-error" id="e_email">Please enter a valid email address.</div>
        </div>

      </div>

      <!-- ══ IDENTIFICATION ══ -->
      <div class="form-section-title mt-4">🪪 Identification (For Verification)</div>
      <div class="row g-3 mb-2">

        <div class="col-6">
          <label class="field-label" for="idtype">Valid ID Type *</label>
          <select class="field-input" id="idtype">
            <option value="">Select ID type…</option>
            <option>National ID</option>
            <option>Student ID</option>
            <option>Driver's License</option>
            <option>Voter's ID</option>
            <option>PhilHealth ID</option>
            <option>SSS / GSIS ID</option>
            <option>Passport</option>
          </select>
          <div class="field-error" id="e_idtype">Please select your ID type.</div>
        </div>
        <div class="col-6">
          <label class="field-label">Upload ID Photo *</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" id="idPhoto" accept="image/*,.pdf" onchange="handleUpload(this)">
            <div class="upload-zone-icon">📎</div>
            <div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div>
            <div class="upload-filename" id="uploadFilename"></div>
          </div>
          <div class="field-error" id="e_idphoto">Please upload a photo of your valid ID.</div>
        </div>

      </div>

      <!-- ══ ADMIN EXTRA FIELDS ══ -->
      <div class="admin-fields" id="adminFields">
        <div class="form-section-title mt-4">🛡️ Staff Details</div>
        <div class="row g-3 mb-2">
          <div class="col-6">
            <label class="field-label" for="position">Position / Role *</label>
            <select class="field-input" id="position">
              <option value="">Select position…</option>
              <option>Barangay Secretary</option>
              <option>Barangay Treasurer</option>
              <option>Barangay Kagawad</option>
              <option>SK Chairperson</option>
              <option>Barangay Health Worker</option>
              <option>Barangay Tanod</option>
              <option>IT / System Staff</option>
              <option>Other Staff</option>
            </select>
          </div>
          <div class="col-6">
            <label class="field-label" for="employeeId">Employee / Staff ID <span class="opt">(if applicable)</span></label>
            <input type="text" class="field-input" id="employeeId" placeholder="e.g. BRG-2024-001">
          </div>
          <div class="col-12">
            <label class="field-label" for="approverNote">Note to Punong Barangay <span class="opt">(optional)</span></label>
            <input type="text" class="field-input" id="approverNote" placeholder="e.g. I am the newly assigned secretary…">
          </div>
        </div>
      </div>

      <!-- ══ ACCOUNT CREDENTIALS ══ -->
      <div class="form-section-title mt-4">🔑 Account Credentials</div>
      <div class="row g-3 mb-2">

        <div class="col-12">
          <label class="field-label" for="username">Username *</label>
          <input type="text" class="field-input" id="username" placeholder="e.g. juandelacruz (no spaces, min. 4 chars)" autocomplete="username">
          <div class="field-error" id="e_username">Username is required (min. 4 characters, no spaces).</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="pw">Password *</label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="pw" placeholder="Create a strong password" autocomplete="new-password" oninput="checkStrength()">
            <button type="button" class="pw-toggle" onclick="togglePw('pw')">👁</button>
          </div>
          <div class="mt-2">
            <div class="pw-bars">
              <div class="pw-bar" id="b1"></div><div class="pw-bar" id="b2"></div>
              <div class="pw-bar" id="b3"></div><div class="pw-bar" id="b4"></div>
            </div>
            <div class="pw-bar-label" id="pw-label">Enter a password</div>
          </div>
          <div class="field-error" id="e_pw">Password must be at least 8 characters.</div>
        </div>
        <div class="col-6">
          <label class="field-label" for="cpw">Confirm Password *</label>
          <div class="pw-wrap">
            <input type="password" class="field-input" id="cpw" placeholder="Re-enter your password" autocomplete="new-password">
            <button type="button" class="pw-toggle" onclick="togglePw('cpw')">👁</button>
          </div>
          <div class="field-error" id="e_cpw">Passwords do not match.</div>
        </div>

      </div>

      <!-- SUBMIT -->
      <button class="btn-register mt-4" id="submitBtn" onclick="submitForm()">Create My Account →</button>

      <div class="divider-line my-4">or</div>
      <div class="login-link-text">Already have an account? <a href="login.php">Login here →</a></div>

    </div><!-- /auth-card -->
    <div class="auth-note">🔒 Barangay Alapan I-A · Imus, Cavite · Official Portal · 2026</div>

  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let currentRole = 'resident';

  function setRole(role) {
    currentRole = role;
    document.getElementById('btnResident').classList.toggle('active', role === 'resident');
    document.getElementById('btnAdmin').classList.toggle('active', role === 'admin');
    document.getElementById('adminNotice').classList.toggle('show', role === 'admin');
    document.getElementById('adminFields').classList.toggle('show', role === 'admin');
    document.getElementById('submitBtn').textContent =
      role === 'admin' ? 'Submit for Approval →' : 'Create My Account →';
  }

  function handleUpload(input) {
    if (input.files && input.files[0]) {
      document.getElementById('uploadZone').classList.add('has-file');
      const fn = document.getElementById('uploadFilename');
      fn.textContent = '✓ ' + input.files[0].name;
      fn.style.display = 'block';
    }
  }

  function checkStrength() {
    const pw = document.getElementById('pw').value;
    const bars = ['b1','b2','b3','b4'].map(id => document.getElementById(id));
    let score = 0;
    if (pw.length >= 8)          score++;
    if (/[A-Z]/.test(pw))        score++;
    if (/[0-9]/.test(pw))        score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    bars.forEach(b => b.className = 'pw-bar');
    const cls    = score <= 1 ? 'weak' : score <= 2 ? 'fair' : 'strong';
    const labels = ['', 'Weak', 'Weak', 'Fair', 'Strong'];
    for (let i = 0; i < score; i++) bars[i].classList.add(cls);
    document.getElementById('pw-label').textContent = score > 0 ? labels[score] : 'Enter a password';
  }

  function togglePw(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
  }

  function g(id) { return document.getElementById(id); }

  function setErr(inputId, errId, hasErr) {
    g(inputId).classList.toggle('is-err', hasErr);
    g(errId).classList.toggle('show', hasErr);
    return hasErr;
  }

  function validate() {
    let ok = true;

    // Basic info
    if (!g('fname').value.trim())  { setErr('fname','e_fname',true); ok=false; }   else setErr('fname','e_fname',false);
    if (!g('lname').value.trim())  { setErr('lname','e_lname',true); ok=false; }   else setErr('lname','e_lname',false);
    if (!g('dob').value)           { setErr('dob','e_dob',true); ok=false; }        else setErr('dob','e_dob',false);
    if (!g('gender').value)        { setErr('gender','e_gender',true); ok=false; }  else setErr('gender','e_gender',false);

    // Contact
    const mobile = g('mobile').value.replace(/\D/g,'');
    if (mobile.length < 10)        { setErr('mobile','e_mobile',true); ok=false; }  else setErr('mobile','e_mobile',false);
    const email = g('email').value;
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setErr('email','e_email',true); ok=false; } else setErr('email','e_email',false);

    // ID
    if (!g('idtype').value)        { setErr('idtype','e_idtype',true); ok=false; }  else setErr('idtype','e_idtype',false);
    if (!g('idPhoto').files || !g('idPhoto').files[0]) { setErr('idPhoto','e_idphoto',true); ok=false; } else setErr('idPhoto','e_idphoto',false);

    // Credentials
    const uname = g('username').value.trim();
    if (!uname || uname.length < 4 || /\s/.test(uname)) { setErr('username','e_username',true); ok=false; } else setErr('username','e_username',false);
    if (!g('pw').value || g('pw').value.length < 8) { setErr('pw','e_pw',true); ok=false; } else setErr('pw','e_pw',false);
    if (!g('cpw').value || g('pw').value !== g('cpw').value) { setErr('cpw','e_cpw',true); ok=false; } else setErr('cpw','e_cpw',false);

    return ok;
  }

  function submitForm() {
    if (!validate()) {
      const firstErr = document.querySelector('.is-err, .field-error.show');
      if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const btn = g('submitBtn');
    btn.textContent = 'Submitting…';
    btn.disabled = true;

    setTimeout(() => {
      btn.textContent = currentRole === 'admin' ? 'Submit for Approval →' : 'Create My Account →';
      btn.disabled = false;

      if (currentRole === 'admin') {
        g('successIcon').textContent  = '📋';
        g('successTitle').textContent = 'Registration Submitted!';
        g('successBody').textContent  = 'Your Admin/Staff registration has been submitted successfully.';
        g('successAdminNote').style.display = 'block';
      } else {
        g('successIcon').textContent  = '🎉';
        g('successTitle').textContent = 'Account Created!';
        g('successBody').textContent  = "Welcome to Barangay Alapan I-A's resident portal. Your registration is being processed. You may log in once your account is activated.";
        g('successAdminNote').style.display = 'none';
      }
      g('successOverlay').classList.add('show');
    }, 1200);
  }
</script>
</body>
</html>