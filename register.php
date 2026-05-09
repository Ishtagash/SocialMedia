<?php
session_start();

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$FIREBASE_API_KEY = "AIzaSyCwXnvovYNhm5QuohqBOQ1JAhu6wTT03hI";

$error = '';

$fname = $lname = $mname = $suffix = $dob = $gender = '';
$mobile = $email = $address = $idtype = $username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fname    = trim($_POST['fname']    ?? '');
    $lname    = trim($_POST['lname']    ?? '');
    $mname    = trim($_POST['mname']    ?? '');
    $suffix   = trim($_POST['suffix']   ?? '');
    $dob      = trim($_POST['dob']      ?? '');
    $gender   = trim($_POST['gender']   ?? '');
    $mobile   = trim($_POST['mobile']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $idtype   = trim($_POST['idtype']   ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$fname || !$lname || !$dob || !$gender || !$mobile ||
        !$email || !$address || !$idtype || !$username || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';

    } else {
        $checkUser = sqlsrv_query($conn,
            "SELECT TOP 1 USER_ID FROM USERS WHERE USERNAME = ?", [$username]);
        if ($checkUser === false) {
            $error = 'Database error: ' . print_r(sqlsrv_errors(), true);
        } elseif (sqlsrv_fetch_array($checkUser, SQLSRV_FETCH_ASSOC)) {
            $error = 'Username already exists. Please choose another.';
        } else {
            $checkEmail = sqlsrv_query($conn,
                "SELECT TOP 1 USER_ID FROM USERS WHERE EMAIL = ?", [$email]);
            if ($checkEmail === false) {
                $error = 'Database error: ' . print_r(sqlsrv_errors(), true);
            } elseif (sqlsrv_fetch_array($checkEmail, SQLSRV_FETCH_ASSOC)) {
                $error = 'Email already registered. Please use a different email.';
            } else {

                $ch = curl_init(
                    'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . $FIREBASE_API_KEY
                );
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS     => json_encode([
                        'email'             => $email,
                        'password'          => $password,
                        'returnSecureToken' => true
                    ]),
                ]);
                $fbResponse = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (isset($fbResponse['error'])) {
                    $fbMsg = $fbResponse['error']['message'] ?? 'Unknown error';
                    $error = $fbMsg === 'EMAIL_EXISTS'
                        ? 'This email is already registered.'
                        : 'Account creation failed: ' . $fbMsg;
                } else {
                    $uploadDir = 'uploads/ids/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext         = pathinfo($_FILES['idPhoto']['name'], PATHINFO_EXTENSION);
                    $idPhotoPath = $uploadDir . uniqid('id_') . '.' . $ext;
                    move_uploaded_file($_FILES['idPhoto']['tmp_name'], $idPhotoPath);

                    $_SESSION['reg'] = [
                        'username'    => $username,
                        'email'       => $email,
                        'password'    => $password,
                        'fname'       => $fname,
                        'lname'       => $lname,
                        'mname'       => $mname,
                        'suffix'      => $suffix,
                        'dob'         => $dob,
                        'gender'      => $gender,
                        'mobile'      => $mobile,
                        'address'     => $address,
                        'idtype'      => $idtype,
                        'id_photo'    => $idPhotoPath,
                        'firebase_uid'=> $fbResponse['localId'],
                    ];

                    header('Location: verify_email.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — Barangay Alapan I-A</title>
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
  .nav-link-light { font-size: 13px; color: rgba(255,255,255,0.60); text-decoration: none; transition: color .2s; }
  .nav-link-light:hover { color: var(--lime); }
  .auth-card {
    background: var(--white); border: 1px solid var(--border);
    border-top: 4px solid var(--dark); border-radius: 10px;
    padding: 36px 40px 40px; box-shadow: 0 6px 30px rgba(5,22,80,.09);
  }
  .form-section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--white); background: var(--dark);
    padding: 8px 14px; border-radius: 5px; margin-bottom: 20px;
  }
  .form-section-title i { font-size: 13px; color: var(--lime); }
  .field-label {
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    color: #555; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
  }
  .field-label i { color: var(--dark); width: 14px; font-size: 12px; }
  .field-label .opt { font-weight: 400; color: #bbb; letter-spacing: 0; text-transform: none; font-size: 11px; }
  .field-input {
    width: 100%; background: var(--white); border: 1px solid #ccc; border-radius: 6px;
    padding: 11px 14px; color: var(--dark); font-family: Arial, sans-serif; font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .field-input:focus { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(5,22,80,.10); }
  .field-input.is-err { border-color: var(--red); box-shadow: 0 0 0 3px rgba(224,48,48,.10); }
  .field-error { font-size: 12px; color: var(--red); display: none; margin-top: 4px; align-items: center; gap: 5px; }
  .field-error.show { display: flex; }
  .pw-wrap { position: relative; }
  .pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: #bbb; font-size: 14px; transition: color .2s;
  }
  .pw-toggle:hover { color: var(--dark); }
  .upload-zone {
    border: 2px dashed #ccc; border-radius: 6px; padding: 22px 16px; text-align: center;
    cursor: pointer; transition: border-color .2s, background .2s; position: relative;
  }
  .upload-zone:hover { border-color: var(--dark); background: #f5f7ff; }
  .upload-zone.has-file { border-color: var(--green); background: #f4fff4; }
  .upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; padding: 0; border: none;
  }
  .upload-zone-icon { font-size: 24px; color: #aaa; margin-bottom: 6px; }
  .upload-zone.has-file .upload-zone-icon { color: var(--green); }
  .upload-zone-text { font-size: 13px; color: #777; }
  .upload-zone-text strong { color: var(--dark); }
  .upload-filename { font-size: 12px; color: var(--green); font-weight: 700; display: none; margin-top: 5px; }
  .alert-err {
    display: flex; align-items: flex-start; gap: 10px;
    background: #fff0f0; border: 1px solid #f5c0c0; border-left: 4px solid var(--red);
    border-radius: 6px; padding: 11px 14px; font-size: 14px; color: var(--red); margin-bottom: 20px;
  }
  .btn-register {
    width: 100%; background: var(--dark); color: var(--white); border: none;
    padding: 15px; border-radius: 6px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: Arial, sans-serif; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 9px;
  }
  .btn-register:hover { background: var(--dark-hover); }
  .divider-line { display: flex; align-items: center; gap: 12px; color: #ccc; font-size: 12px; }
  .divider-line::before, .divider-line::after { content: ''; flex: 1; height: 1px; background: #e5e5e5; }
  .login-link-text { font-size: 14px; color: #666; text-align: center; }
  .login-link-text a { color: var(--dark); font-weight: 700; text-decoration: none; }
  .login-link-text a:hover { text-decoration: underline; }
  .auth-note { font-size: 12px; color: #bbb; text-align: center; margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 6px; }
  @media (max-width: 640px) { .auth-card { padding: 28px 20px; } }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal"><img src="alapan.png" alt="Barangay Alapan I-A Logo"></div>
        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">Alapan I-A</span>
        </div>
      </a>
      <div class="d-flex align-items-center gap-3">
        <a href="login.php" class="nav-link-light"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Already registered? Login</a>
        <a href="home.php"  class="nav-link-light"><i class="fa-solid fa-arrow-left me-1"></i>Home</a>
      </div>
    </div>
  </div>
</nav>

<div class="flex-grow-1 py-5">
  <div class="container" style="max-width:660px;">
    <div class="auth-card">

      <?php if ($error): ?>
      <div class="alert-err">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="register.php" enctype="multipart/form-data">

        <div class="form-section-title">
          <i class="fa-solid fa-user"></i>Basic Information
        </div>
        <div class="row g-3 mb-2">
          <div class="col-6">
            <label class="field-label" for="fname"><i class="fa-solid fa-pen"></i>First Name *</label>
            <input type="text" class="field-input" id="fname" name="fname" placeholder="Juan" value="<?= htmlspecialchars($fname) ?>" required>
            <div class="field-error" id="e_fname"><i class="fa-solid fa-circle-exclamation"></i>First name is required.</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="lname"><i class="fa-solid fa-pen"></i>Last Name *</label>
            <input type="text" class="field-input" id="lname" name="lname" placeholder="dela Cruz" value="<?= htmlspecialchars($lname) ?>" required>
            <div class="field-error" id="e_lname"><i class="fa-solid fa-circle-exclamation"></i>Last name is required.</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="mname"><i class="fa-solid fa-pen"></i>Middle Name <span class="opt">(optional)</span></label>
            <input type="text" class="field-input" id="mname" name="mname" placeholder="Santos" value="<?= htmlspecialchars($mname) ?>">
          </div>
          <div class="col-6">
            <label class="field-label" for="suffix"><i class="fa-solid fa-tag"></i>Suffix <span class="opt">(optional)</span></label>
            <select class="field-input" id="suffix" name="suffix">
              <option value="">None</option>
              <option <?= $suffix==='Jr.'?'selected':'' ?>>Jr.</option>
              <option <?= $suffix==='Sr.'?'selected':'' ?>>Sr.</option>
              <option <?= $suffix==='II' ?'selected':'' ?>>II</option>
              <option <?= $suffix==='III'?'selected':'' ?>>III</option>
              <option <?= $suffix==='IV' ?'selected':'' ?>>IV</option>
            </select>
          </div>
          <div class="col-6">
            <label class="field-label" for="dob"><i class="fa-solid fa-calendar"></i>Date of Birth *</label>
            <input type="date" class="field-input" id="dob" name="dob" value="<?= htmlspecialchars($dob) ?>" required>
            <div class="field-error" id="e_dob"><i class="fa-solid fa-circle-exclamation"></i>Date of birth is required.</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="gender"><i class="fa-solid fa-venus-mars"></i>Gender *</label>
            <select class="field-input" id="gender" name="gender" required>
              <option value="">Select&hellip;</option>
              <option <?= $gender==='Male'             ?'selected':'' ?>>Male</option>
              <option <?= $gender==='Female'           ?'selected':'' ?>>Female</option>
              <option <?= $gender==='Prefer not to say'?'selected':'' ?>>Prefer not to say</option>
            </select>
            <div class="field-error" id="e_gender"><i class="fa-solid fa-circle-exclamation"></i>Please select your gender.</div>
          </div>
        </div>

        <div class="form-section-title mt-4">
          <i class="fa-solid fa-phone"></i>Contact Information
        </div>
        <div class="row g-3 mb-2">
          <div class="col-6">
            <label class="field-label" for="mobile"><i class="fa-solid fa-mobile-screen"></i>Mobile Number *</label>
            <input type="tel" class="field-input" id="mobile" name="mobile" placeholder="09XX-XXX-XXXX" value="<?= htmlspecialchars($mobile) ?>" required>
            <div class="field-error" id="e_mobile"><i class="fa-solid fa-circle-exclamation"></i>Please enter a valid mobile number.</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="email"><i class="fa-solid fa-envelope"></i>Email Address *</label>
            <input type="email" class="field-input" id="email" name="email" placeholder="juan@gmail.com" value="<?= htmlspecialchars($email) ?>" required>
            <div class="field-error" id="e_email"><i class="fa-solid fa-circle-exclamation"></i>Please enter a valid email address.</div>
          </div>
          <div class="col-12">
            <label class="field-label" for="address"><i class="fa-solid fa-location-dot"></i>Home Address *</label>
            <input type="text" class="field-input" id="address" name="address"
              placeholder="e.g. 12B Maligaya St., Purok 3, Alapan I-A, Imus, Cavite"
              value="<?= htmlspecialchars($address) ?>" required>
            <div class="field-error" id="e_address"><i class="fa-solid fa-circle-exclamation"></i>Home address is required.</div>
          </div>
        </div>

        <div class="form-section-title mt-4">
          <i class="fa-solid fa-id-card"></i>Identification (For Verification)
        </div>
        <div class="row g-3 mb-2">
          <div class="col-6">
            <label class="field-label" for="idtype"><i class="fa-solid fa-list"></i>Valid ID Type *</label>
            <select class="field-input" id="idtype" name="idtype" required>
              <option value="">Select ID type&hellip;</option>
              <option <?= $idtype==='National ID'      ?'selected':'' ?>>National ID</option>
              <option <?= $idtype==='Student ID'       ?'selected':'' ?>>Student ID</option>
              <option <?= $idtype==="Driver's License" ?'selected':'' ?>>Driver's License</option>
              <option <?= $idtype==="Voter's ID"       ?'selected':'' ?>>Voter's ID</option>
              <option <?= $idtype==='PhilHealth ID'    ?'selected':'' ?>>PhilHealth ID</option>
              <option <?= $idtype==='SSS / GSIS ID'    ?'selected':'' ?>>SSS / GSIS ID</option>
              <option <?= $idtype==='Passport'         ?'selected':'' ?>>Passport</option>
            </select>
            <div class="field-error" id="e_idtype"><i class="fa-solid fa-circle-exclamation"></i>Please select your ID type.</div>
          </div>
          <div class="col-6">
            <label class="field-label"><i class="fa-solid fa-upload"></i>Upload ID Photo *</label>
            <div class="upload-zone" id="uploadZone">
              <input type="file" id="idPhoto" name="idPhoto" accept="image/*,.pdf" onchange="handleUpload(this)" required>
              <div class="upload-zone-icon"><i class="fa-solid fa-cloud-arrow-up fa-lg"></i></div>
              <div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF &middot; Max 5MB</div>
              <div class="upload-filename" id="uploadFilename"></div>
            </div>
            <div class="field-error" id="e_idphoto"><i class="fa-solid fa-circle-exclamation"></i>Please upload a photo of your valid ID.</div>
          </div>
        </div>

        <div class="form-section-title mt-4">
          <i class="fa-solid fa-key"></i>Account Credentials
        </div>
        <div class="row g-3 mb-2">
          <div class="col-12">
            <label class="field-label" for="username"><i class="fa-solid fa-at"></i>Username *</label>
            <input type="text" class="field-input" id="username" name="username"
              placeholder="e.g. juandelacruz (no spaces, min. 4 chars)"
              value="<?= htmlspecialchars($username) ?>" autocomplete="username" required>
            <div class="field-error" id="e_username"><i class="fa-solid fa-circle-exclamation"></i>Username is required (min. 4 characters, no spaces).</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="pw"><i class="fa-solid fa-lock"></i>Password *</label>
            <div class="pw-wrap">
              <input type="password" class="field-input" id="pw" name="password"
                placeholder="Min. 6 characters" autocomplete="new-password" minlength="6" required>
              <button type="button" class="pw-toggle" onclick="togglePw('pw','pwEye1')">
                <i class="fa-solid fa-eye" id="pwEye1"></i>
              </button>
            </div>
            <div class="field-error" id="e_pw"><i class="fa-solid fa-circle-exclamation"></i>Password must be at least 6 characters.</div>
          </div>
          <div class="col-6">
            <label class="field-label" for="cpw"><i class="fa-solid fa-lock"></i>Confirm Password *</label>
            <div class="pw-wrap">
              <input type="password" class="field-input" id="cpw" name="confirm"
                placeholder="Re-enter your password" autocomplete="new-password" minlength="6" required>
              <button type="button" class="pw-toggle" onclick="togglePw('cpw','pwEye2')">
                <i class="fa-solid fa-eye" id="pwEye2"></i>
              </button>
            </div>
            <div class="field-error" id="e_cpw"><i class="fa-solid fa-circle-exclamation"></i>Passwords do not match.</div>
          </div>
        </div>

        <button type="submit" class="btn-register mt-4" onclick="return clientValidate()">
          <i class="fa-solid fa-user-plus"></i>
          <span>Create My Account</span>
        </button>

      </form>

      <div class="divider-line my-4">or</div>
      <div class="login-link-text">
        Already have an account?
        <a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Login here</a>
      </div>
    </div>
    <div class="auth-note">
      <i class="fa-solid fa-shield-halved"></i>
      Barangay Alapan I-A &middot; Imus, Cavite &middot; Official Portal &middot; 2026
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function handleUpload(input) {
    if (input.files && input.files[0]) {
      document.getElementById('uploadZone').classList.add('has-file');
      const fn = document.getElementById('uploadFilename');
      fn.innerHTML = '<i class="fa-solid fa-check me-1"></i>' + input.files[0].name;
      fn.style.display = 'block';
    }
  }
  function togglePw(inputId, iconId) {
    const el = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    el.type = el.type === 'password' ? 'text' : 'password';
    icon.className = el.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
  }
  function g(id) { return document.getElementById(id); }
  function setErr(inputId, errId, hasErr) {
    g(inputId).classList.toggle('is-err', hasErr);
    g(errId).classList.toggle('show', hasErr);
    return hasErr;
  }
  function clientValidate() {
    let ok = true;
    if (!g('fname').value.trim())   { setErr('fname','e_fname',true);     ok=false; } else setErr('fname','e_fname',false);
    if (!g('lname').value.trim())   { setErr('lname','e_lname',true);     ok=false; } else setErr('lname','e_lname',false);
    if (!g('dob').value)            { setErr('dob','e_dob',true);         ok=false; } else setErr('dob','e_dob',false);
    if (!g('gender').value)         { setErr('gender','e_gender',true);   ok=false; } else setErr('gender','e_gender',false);
    const mob = g('mobile').value.replace(/\D/g,'');
    if (mob.length < 10)            { setErr('mobile','e_mobile',true);   ok=false; } else setErr('mobile','e_mobile',false);
    const em = g('email').value;
    if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { setErr('email','e_email',true); ok=false; } else setErr('email','e_email',false);
    if (!g('address').value.trim()) { setErr('address','e_address',true); ok=false; } else setErr('address','e_address',false);
    if (!g('idtype').value)         { setErr('idtype','e_idtype',true);   ok=false; } else setErr('idtype','e_idtype',false);
    if (!g('idPhoto').files || !g('idPhoto').files[0]) { setErr('idPhoto','e_idphoto',true); ok=false; } else setErr('idPhoto','e_idphoto',false);
    const un = g('username').value.trim();
    if (!un || un.length < 4 || /\s/.test(un)) { setErr('username','e_username',true); ok=false; } else setErr('username','e_username',false);
    const pw = g('pw').value;
    const cpw = g('cpw').value;
    if (!pw || pw.length < 6)  { setErr('pw','e_pw',true);   ok=false; } else setErr('pw','e_pw',false);
    if (!cpw || pw !== cpw)    { setErr('cpw','e_cpw',true); ok=false; } else setErr('cpw','e_cpw',false);
    if (!ok) document.querySelector('.is-err').scrollIntoView({ behavior: 'smooth', block: 'center' });
    return ok;
  }
</script>
</body>
</html>