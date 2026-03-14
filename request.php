<?php
session_start();

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$profile = null;
if (isset($_SESSION['user_id'])) {
    $pRow = sqlsrv_fetch_array(
        sqlsrv_query($conn,
            "SELECT r.FIRST_NAME, r.MIDDLE_NAME, r.LAST_NAME, r.SUFFIX, r.BIRTHDATE, r.GENDER, r.MOBILE_NUMBER, u.EMAIL
             FROM REGISTRATION r JOIN USERS u ON u.USER_ID = r.USER_ID
             WHERE r.USER_ID = ?", [$_SESSION['user_id']]),
        SQLSRV_FETCH_ASSOC
    );
    if ($pRow) $profile = $pRow;
}

$fullName = $profile
    ? trim($profile['FIRST_NAME'].' '.($profile['MIDDLE_NAME'] ? $profile['MIDDLE_NAME'].' ' : '').$profile['LAST_NAME'].($profile['SUFFIX'] ? ' '.$profile['SUFFIX'] : ''))
    : ($_SESSION['username'] ?? 'Unknown');

$error = ""; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId      = $_SESSION['user_id'];
    $serviceType = trim($_POST['service_type']);
    $purpose     = trim($_POST['purpose']);
    $delivery    = trim($_POST['delivery']);
    $address     = trim($_POST['delivery_address'] ?? '');
    $payment     = trim($_POST['payment_method']);
    $notes       = trim($_POST['notes'] ?? '');

    $extra = [];
    $extra['delivery']         = $delivery;
    $extra['delivery_address'] = $delivery === 'Delivery' ? $address : 'Pickup at Barangay Hall';
    $extra['payment_method']   = $payment;

    $uploadDir = 'uploads/requests/';
    $uploadedFiles = [];
    if (!empty($_FILES)) {
        foreach ($_FILES as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dest = $uploadDir . uniqid($key.'_').'.'.$ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) $uploadedFiles[$key] = $dest;
            }
        }
    }
    $extra['uploads'] = $uploadedFiles;

    // Service-specific fields
    $fields = ['cedula_number','years_residing','full_address','monthly_income','family_members',
               'id_color','blood_type','emergency_contact','emergency_contact_num',
               'business_name','business_type','business_address',
               'respondent','incident_date','incident_place','incident_desc',
               'solo_parent_id','pwd_type','sc_osca_id','assistance_type','assistance_reason',
               'endorsement_purpose','endorsement_recipient'];
    foreach ($fields as $f) { if (isset($_POST[$f])) $extra[$f] = trim($_POST[$f]); }

    $extraJson = json_encode($extra);
    $sql  = "INSERT INTO REQUESTS (USER_ID, SERVICE_TYPE, PURPOSE, EXTRA_DATA, NOTES, STATUS, CREATED_AT) VALUES (?, ?, ?, ?, ?, 'PENDING', GETDATE())";
    $stmt = sqlsrv_query($conn, $sql, [$userId, $serviceType, $purpose, $extraJson, $notes]);
    if ($stmt === false) { $error = "Submission failed: ".print_r(sqlsrv_errors(), true); }
    else { $success = true; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request a Document — Barangay Alapan I-A</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --dark:#051650; --dark-hover:#0a2470; --lime:#ccff00;
    --white:#ffffff; --red:#e03030; --orange:#e07800;
    --green:#2e7d32; --bg:#eef0f8; --border:rgba(5,22,80,0.14);
  }
  body { font-family:Arial,sans-serif; background:var(--bg); color:var(--dark); min-height:100vh; }

  /* NAV */
  .site-nav { background:var(--dark); border-bottom:3px solid var(--lime); padding:10px 0; }
  .nav-seal { width:50px; height:50px; border-radius:50%; overflow:hidden; flex-shrink:0; }
  .nav-seal img { width:100%; height:100%; object-fit:cover; }
  .nav-brgy { font-size:10px; letter-spacing:2px; text-transform:uppercase; color:var(--lime); display:block; line-height:1.2; }
  .nav-name  { font-size:17px; font-weight:700; color:var(--white); line-height:1.2; }
  .nav-link-light { font-size:13px; color:rgba(255,255,255,0.60); text-decoration:none; transition:color .2s; }
  .nav-link-light:hover { color:var(--lime); }

  /* PAGE HEADER */
  .page-header { background:var(--dark); color:var(--white); padding:36px 0 32px; border-bottom:3px solid var(--lime); }
  .page-header .badge-label { display:inline-flex; align-items:center; gap:7px; background:var(--lime); color:var(--dark); font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:4px 12px; border-radius:4px; margin-bottom:12px; }
  .page-header h1 { font-size:28px; font-weight:900; margin-bottom:4px; }
  .page-header p  { font-size:14px; color:rgba(255,255,255,0.65); margin:0; }

  /* LAYOUT */
  .request-layout { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }
  @media(max-width:768px) { .request-layout { grid-template-columns:1fr; } }

  /* CATEGORY SIDEBAR */
  .cat-sidebar { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(5,22,80,.08); position:sticky; top:20px; }
  .cat-sidebar-title { background:var(--dark); color:var(--lime); font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:12px 16px; }
  .cat-btn { display:flex; align-items:center; gap:10px; width:100%; padding:11px 16px; border:none; background:transparent; text-align:left; font-family:Arial,sans-serif; font-size:13px; font-weight:600; color:#666; cursor:pointer; transition:background .15s,color .15s; border-left:3px solid transparent; }
  .cat-btn:hover { background:rgba(5,22,80,.04); color:var(--dark); }
  .cat-btn.active { background:rgba(204,255,0,.15); color:var(--dark); border-left-color:var(--lime); font-weight:700; }
  .cat-btn i { width:16px; text-align:center; font-size:13px; color:var(--dark); opacity:.6; }
  .cat-btn.active i { opacity:1; }
  .cat-count { margin-left:auto; background:rgba(5,22,80,.1); color:var(--dark); font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; }
  .cat-btn.active .cat-count { background:var(--dark); color:var(--lime); }

  /* SERVICE GRID */
  .service-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
  .service-card { background:var(--white); border:2px solid var(--border); border-radius:10px; padding:18px 16px; cursor:pointer; transition:border-color .2s,box-shadow .2s,transform .15s; position:relative; overflow:hidden; }
  .service-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--border); transition:background .2s; }
  .service-card:hover { border-color:var(--dark); box-shadow:0 4px 20px rgba(5,22,80,.12); transform:translateY(-2px); }
  .service-card:hover::before, .service-card.selected::before { background:var(--lime); }
  .service-card.selected { border-color:var(--dark); box-shadow:0 4px 20px rgba(5,22,80,.15); }
  .service-card.hidden { display:none; }

  .svc-icon { width:40px; height:40px; border-radius:7px; background:var(--dark); color:var(--lime); display:flex; align-items:center; justify-content:center; font-size:16px; margin-bottom:10px; }
  .svc-name { font-size:13px; font-weight:700; color:var(--dark); margin-bottom:4px; line-height:1.3; }
  .svc-desc { font-size:11px; color:#999; line-height:1.4; }
  .svc-tags { margin-top:8px; display:flex; gap:4px; flex-wrap:wrap; }
  .stag { font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; }
  .stag-fee  { background:rgba(5,22,80,.07); color:var(--dark); }
  .stag-days { background:rgba(204,255,0,.3); color:var(--dark); }
  .stag-free { background:rgba(46,125,50,.12); color:var(--green); }
  .check-badge { position:absolute; top:10px; right:10px; width:20px; height:20px; border-radius:50%; background:var(--lime); color:var(--dark); display:none; align-items:center; justify-content:center; font-size:10px; font-weight:900; }
  .service-card.selected .check-badge { display:flex; }

  /* NO RESULTS */
  .no-results { text-align:center; padding:40px 20px; color:#aaa; font-size:14px; display:none; }
  .no-results i { font-size:32px; display:block; margin-bottom:10px; }

  /* FORM PANEL */
  .form-panel { display:none; background:var(--white); border:1px solid var(--border); border-top:4px solid var(--dark); border-radius:10px; padding:36px 40px; box-shadow:0 6px 30px rgba(5,22,80,.09); margin-top:24px; }
  .form-panel.show { display:block; }

  .form-section-title { display:flex; align-items:center; gap:10px; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--white); background:var(--dark); padding:8px 14px; border-radius:5px; margin-bottom:20px; }
  .form-section-title i { color:var(--lime); }

  .field-label { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#555; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
  .field-label i { color:var(--dark); width:14px; }
  .field-input { width:100%; background:var(--white); border:1px solid #ccc; border-radius:6px; padding:11px 14px; color:var(--dark); font-family:Arial,sans-serif; font-size:14px; outline:none; transition:border-color .2s,box-shadow .2s; }
  .field-input:focus { border-color:var(--dark); box-shadow:0 0 0 3px rgba(5,22,80,.10); }
  .field-input[readonly] { background:#f4f4f4; color:#999; cursor:not-allowed; }

  /* PROFILE SUMMARY */
  .profile-summary { background:rgba(5,22,80,.04); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:20px; display:flex; align-items:center; gap:14px; }
  .profile-avatar { width:44px; height:44px; border-radius:50%; background:var(--dark); color:var(--lime); display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
  .profile-info-name { font-size:15px; font-weight:700; color:var(--dark); }
  .profile-info-sub  { font-size:12px; color:#888; }

  /* OWNERSHIP WARNING */
  .ownership-warn { display:flex; align-items:flex-start; gap:12px; background:#fffbea; border:1px solid #f5e06f; border-left:4px solid var(--orange); border-radius:6px; padding:14px 16px; margin-bottom:22px; font-size:13px; color:#7a5800; line-height:1.6; }
  .ownership-warn i { flex-shrink:0; margin-top:2px; color:var(--orange); }
  .own-check { display:flex; align-items:center; gap:8px; margin-top:10px; font-weight:700; cursor:pointer; }
  .own-check input { accent-color:var(--dark); width:16px; height:16px; }

  /* UPLOAD */
  .upload-zone { border:2px dashed #ccc; border-radius:6px; padding:16px; text-align:center; cursor:pointer; transition:border-color .2s,background .2s; position:relative; }
  .upload-zone:hover { border-color:var(--dark); background:#f5f7ff; }
  .upload-zone.has-file { border-color:var(--green); background:#f4fff4; }
  .upload-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .upload-zone-text { font-size:11px; color:#888; }
  .upload-zone-text strong { color:var(--dark); }
  .upload-filename { font-size:11px; color:var(--green); font-weight:700; display:none; margin-top:4px; }

  /* DELIVERY */
  .delivery-toggle { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
  .deliv-btn { padding:12px; border:2px solid var(--border); border-radius:8px; cursor:pointer; text-align:center; font-family:Arial,sans-serif; font-size:13px; font-weight:700; background:var(--white); color:#999; transition:all .2s; }
  .deliv-btn:hover { border-color:var(--dark); color:var(--dark); }
  .deliv-btn.active { border-color:var(--dark); background:var(--dark); color:var(--lime); }
  .deliv-btn i { display:block; font-size:18px; margin-bottom:4px; }

  /* PAYMENT */
  .payment-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; margin-bottom:6px; }
  .pay-card { border:2px solid var(--border); border-radius:8px; padding:12px 8px; cursor:pointer; text-align:center; font-size:12px; font-weight:700; color:#999; background:var(--white); transition:all .2s; }
  .pay-card:hover { border-color:var(--dark); color:var(--dark); }
  .pay-card.active { border-color:var(--dark); background:var(--dark); color:var(--lime); }
  .pay-card i { display:block; font-size:18px; margin-bottom:5px; }

  /* REQ BOX */
  .req-box { background:rgba(5,22,80,.04); border:1px solid var(--border); border-left:4px solid var(--lime); border-radius:6px; padding:14px 16px; margin-bottom:20px; }
  .req-box-title { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--dark); margin-bottom:8px; }
  .req-item { font-size:13px; color:#444; display:flex; align-items:center; gap:8px; margin-bottom:5px; }
  .req-item i { color:var(--dark); font-size:10px; flex-shrink:0; }

  /* SELECTED SUMMARY */
  .selected-summary { display:flex; align-items:center; gap:14px; background:var(--dark); color:var(--white); border-radius:8px; padding:14px 18px; margin-bottom:24px; }
  .ss-icon { width:38px; height:38px; border-radius:6px; background:var(--lime); color:var(--dark); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
  .ss-name { font-size:15px; font-weight:700; }
  .ss-sub  { font-size:12px; color:rgba(255,255,255,.6); }

  /* SEARCH */
  .search-wrap { position:relative; margin-bottom:16px; }
  .search-wrap input { width:100%; padding:10px 14px 10px 38px; border:1px solid #ccc; border-radius:7px; font-family:Arial,sans-serif; font-size:13px; outline:none; transition:border-color .2s; }
  .search-wrap input:focus { border-color:var(--dark); }
  .search-wrap i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#aaa; font-size:13px; }

  .btn-submit { width:100%; background:var(--dark); color:var(--white); border:none; padding:15px; border-radius:6px; font-size:15px; font-weight:700; cursor:pointer; font-family:Arial,sans-serif; transition:background .2s; display:flex; align-items:center; justify-content:center; gap:9px; }
  .btn-submit:hover { background:var(--dark-hover); }
  .btn-submit:disabled { opacity:.5; pointer-events:none; }

  .alert-err { display:flex; align-items:flex-start; gap:10px; background:#fff0f0; border:1px solid #f5c0c0; border-left:4px solid var(--red); border-radius:6px; padding:11px 14px; font-size:13px; color:var(--red); margin-bottom:20px; }

  .success-overlay { display:none; position:fixed; inset:0; z-index:1060; background:rgba(5,22,80,.72); align-items:center; justify-content:center; }
  .success-overlay.show { display:flex; }
  .success-box { background:var(--white); border-radius:12px; padding:48px 40px; max-width:420px; width:90%; text-align:center; border-top:5px solid var(--lime); box-shadow:0 24px 64px rgba(5,22,80,.32); }
  .success-icon-wrap { width:72px; height:72px; border-radius:50%; background:var(--lime); color:var(--dark); display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 18px; }
  .success-title { font-size:24px; font-weight:700; color:var(--dark); margin-bottom:8px; }
  .success-body  { font-size:14px; color:#555; line-height:1.7; margin-bottom:24px; }
  .btn-go-dash { display:inline-flex; align-items:center; gap:9px; background:var(--dark); color:var(--white); padding:13px 34px; border-radius:6px; font-size:15px; font-weight:700; text-decoration:none; transition:background .2s; }
  .btn-go-dash:hover { background:var(--dark-hover); color:var(--white); }
  .auth-note { font-size:12px; color:#bbb; text-align:center; margin-top:16px; display:flex; align-items:center; justify-content:center; gap:6px; }
  @media(max-width:640px) { .form-panel { padding:24px 16px; } }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php if ($success): ?>
<div class="success-overlay show">
  <div class="success-box">
    <div class="success-icon-wrap"><i class="fa-solid fa-paper-plane"></i></div>
    <div class="success-title">Request Submitted!</div>
    <div class="success-body">Your request has been received and is now pending review by barangay staff. You will be notified once it is ready.</div>
    <a href="dashboard.html" class="btn-go-dash"><i class="fa-solid fa-house"></i>Back to Dashboard</a>
  </div>
</div>
<?php endif; ?>

<nav class="site-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.html" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal"><img src="alapan.png" alt="Logo"></div>
        <div><span class="nav-brgy">Barangay</span><span class="nav-name">Alapan I-A</span></div>
      </a>
      <a href="dashboard.html" class="nav-link-light"><i class="fa-solid fa-arrow-left me-1"></i>Dashboard</a>
    </div>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div class="badge-label"><i class="fa-solid fa-file-lines me-1"></i>Transactions</div>
    <h1>Request a Document</h1>
    <p>Browse available services, select one, and fill in the required details.</p>
  </div>
</div>

<div class="flex-grow-1 py-5">
<div class="container-xl">

  <?php if ($error): ?>
  <div class="alert-err mb-4"><i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span></div>
  <?php endif; ?>

  <div class="request-layout">

    <!-- ══ CATEGORY SIDEBAR ══ -->
    <div class="cat-sidebar">
      <div class="cat-sidebar-title"><i class="fa-solid fa-layer-group me-2"></i>Categories</div>
      <button class="cat-btn active" onclick="filterCat('all', this)">
        <i class="fa-solid fa-grip"></i>All Services <span class="cat-count">18</span>
      </button>
      <button class="cat-btn" onclick="filterCat('certificates', this)">
        <i class="fa-solid fa-certificate"></i>Certificates <span class="cat-count">8</span>
      </button>
      <button class="cat-btn" onclick="filterCat('identification', this)">
        <i class="fa-solid fa-id-card"></i>Identification <span class="cat-count">1</span>
      </button>
      <button class="cat-btn" onclick="filterCat('business', this)">
        <i class="fa-solid fa-store"></i>Business <span class="cat-count">2</span>
      </button>
      <button class="cat-btn" onclick="filterCat('social', this)">
        <i class="fa-solid fa-hands-holding-child"></i>Social Services <span class="cat-count">5</span>
      </button>
      <button class="cat-btn" onclick="filterCat('legal', this)">
        <i class="fa-solid fa-scale-balanced"></i>Legal <span class="cat-count">2</span>
      </button>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div>
      <!-- Search -->
      <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search for a document or service…" oninput="searchServices()">
      </div>

      <!-- Service Grid -->
      <div class="service-grid" id="serviceGrid">

        <!-- CERTIFICATES -->
        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Barangay Clearance')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-file-circle-check"></i></div>
          <div class="svc-name">Barangay Clearance</div>
          <div class="svc-desc">Good moral standing for employment, loans, and other requirements.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱50</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of Residency')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-house-circle-check"></i></div>
          <div class="svc-name">Certificate of Residency</div>
          <div class="svc-desc">Certifies that you are a resident of Barangay Alapan I-A.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱50</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of Indigency')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
          <div class="svc-name">Certificate of Indigency</div>
          <div class="svc-desc">For residents qualifying for financial assistance programs.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of Good Moral Character')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-star"></i></div>
          <div class="svc-name">Good Moral Character</div>
          <div class="svc-desc">Certifies good standing and character within the barangay.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱50</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of No Income')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-money-bill-slash"></i></div>
          <div class="svc-name">Certificate of No Income</div>
          <div class="svc-desc">Certifies that you have no source of income.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of First Time Job Seeker')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-briefcase"></i></div>
          <div class="svc-name">First Time Job Seeker</div>
          <div class="svc-desc">Required under RA 11261 for first-time job seekers.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">Same day</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of Solo Parent')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-person-breastfeeding"></i></div>
          <div class="svc-name">Certificate of Solo Parent</div>
          <div class="svc-desc">For solo parents availing of government benefits and discounts.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="certificates" onclick="selectService(this,'Certificate of Cohabitation')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-people-roof"></i></div>
          <div class="svc-name">Certificate of Cohabitation</div>
          <div class="svc-desc">Certifies that two individuals are living together as partners.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱50</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <!-- IDENTIFICATION -->
        <div class="service-card" data-cat="identification" onclick="selectService(this,'Barangay ID')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-id-card"></i></div>
          <div class="svc-name">Barangay ID</div>
          <div class="svc-desc">Official barangay identification card for residents.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱100</span><span class="stag stag-days">3–5 days</span></div>
        </div>

        <!-- BUSINESS -->
        <div class="service-card" data-cat="business" onclick="selectService(this,'Business Permit (New)')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-store"></i></div>
          <div class="svc-name">Business Permit (New)</div>
          <div class="svc-desc">For new businesses operating within the barangay.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱200</span><span class="stag stag-days">3–5 days</span></div>
        </div>

        <div class="service-card" data-cat="business" onclick="selectService(this,'Business Permit (Renewal)')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-rotate"></i></div>
          <div class="svc-name">Business Permit (Renewal)</div>
          <div class="svc-desc">Annual renewal of existing barangay business clearance.</div>
          <div class="svc-tags"><span class="stag stag-fee">₱150</span><span class="stag stag-days">1–3 days</span></div>
        </div>

        <!-- SOCIAL SERVICES -->
        <div class="service-card" data-cat="social" onclick="selectService(this,'PWD Certificate')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-wheelchair"></i></div>
          <div class="svc-name">PWD Certificate</div>
          <div class="svc-desc">Barangay endorsement for persons with disability benefits.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="social" onclick="selectService(this,'Senior Citizen Endorsement')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-person-cane"></i></div>
          <div class="svc-name">Senior Citizen Endorsement</div>
          <div class="svc-desc">Barangay endorsement letter for senior citizen benefits.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">Same day</span></div>
        </div>

        <div class="service-card" data-cat="social" onclick="selectService(this,'4Ps / DSWD Endorsement')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-people-group"></i></div>
          <div class="svc-name">4Ps / DSWD Endorsement</div>
          <div class="svc-desc">Endorsement letter for DSWD or Pantawid Pamilya programs.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">1–2 days</span></div>
        </div>

        <div class="service-card" data-cat="social" onclick="selectService(this,'Burial Assistance Request')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-cross"></i></div>
          <div class="svc-name">Burial Assistance Request</div>
          <div class="svc-desc">Financial assistance request for bereaved indigent families.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">Same day</span></div>
        </div>

        <div class="service-card" data-cat="social" onclick="selectService(this,'Financial Assistance Request')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
          <div class="svc-name">Financial Assistance Request</div>
          <div class="svc-desc">Request for emergency financial aid from the barangay.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">2–3 days</span></div>
        </div>

        <!-- LEGAL -->
        <div class="service-card" data-cat="legal" onclick="selectService(this,'Barangay Endorsement Letter')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
          <div class="svc-name">Endorsement Letter</div>
          <div class="svc-desc">Official barangay letter for NBI, passport, court, or hospital use.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">Same day</span></div>
        </div>

        <div class="service-card" data-cat="legal" onclick="selectService(this,'Barangay Protection Order')">
          <div class="check-badge"><i class="fa-solid fa-check"></i></div>
          <div class="svc-icon"><i class="fa-solid fa-shield-heart"></i></div>
          <div class="svc-name">Barangay Protection Order</div>
          <div class="svc-desc">Emergency protection order for VAWC (violence against women and children) cases.</div>
          <div class="svc-tags"><span class="stag stag-free">Free</span><span class="stag stag-days">Same day</span></div>
        </div>

      </div><!-- /service-grid -->

      <div class="no-results" id="noResults">
        <i class="fa-solid fa-magnifying-glass"></i>No services found. Try a different search or category.
      </div>

    </div><!-- /right panel -->
  </div><!-- /request-layout -->

  <!-- ══ FORM PANEL (full width below) ══ -->
  <div class="form-panel" id="formPanel">
    <form method="POST" action="request.php" enctype="multipart/form-data">
      <input type="hidden" name="service_type"   id="serviceTypeInput">
      <input type="hidden" name="delivery"        id="deliveryInput" value="Pickup">
      <input type="hidden" name="payment_method"  id="paymentInput"  value="">

      <div class="selected-summary">
        <div class="ss-icon" id="summaryIcon"><i class="fa-solid fa-file"></i></div>
        <div>
          <div class="ss-name" id="summaryName">—</div>
          <div class="ss-sub"  id="summaryFee">—</div>
        </div>
      </div>

      <!-- OWNERSHIP WARNING -->
      <div class="ownership-warn">
        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
        <div>
          <strong>Important Notice</strong><br>
          This request will be processed under: <strong><?= htmlspecialchars($fullName) ?></strong>.
          This document is for <strong>your personal use only</strong>.
          <label class="own-check" for="ownConfirm">
            <input type="checkbox" id="ownConfirm" required>
            I confirm this document is being requested for myself.
          </label>
        </div>
      </div>

      <!-- PROFILE INFO -->
      <div class="form-section-title"><i class="fa-solid fa-user"></i>Requestor's Information</div>
      <div class="profile-summary mb-3">
        <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
        <div>
          <div class="profile-info-name"><?= htmlspecialchars($fullName) ?></div>
          <div class="profile-info-sub">
            <?= htmlspecialchars($profile['GENDER'] ?? '') ?>
            <?php if ($profile && $profile['BIRTHDATE']): ?>&nbsp;·&nbsp;<?= $profile['BIRTHDATE']->format('F d, Y') ?><?php endif; ?>
            &nbsp;·&nbsp;<?= htmlspecialchars($profile['MOBILE_NUMBER'] ?? '') ?>
            &nbsp;·&nbsp;<?= htmlspecialchars($profile['EMAIL'] ?? '') ?>
          </div>
        </div>
      </div>
      <p style="font-size:12px;color:#aaa;margin-bottom:22px;"><i class="fa-solid fa-circle-info me-1"></i>Profile details are pulled from your registration. Update your profile if any info is incorrect.</p>

      <!-- REQUIREMENTS -->
      <div class="req-box"><div class="req-box-title"><i class="fa-solid fa-clipboard-list me-2"></i>Requirements</div><div id="reqList"></div></div>

      <!-- PURPOSE -->
      <div class="form-section-title"><i class="fa-solid fa-circle-info"></i>Request Details</div>
      <div class="row g-3 mb-3">
        <div class="col-12">
          <label class="field-label"><i class="fa-solid fa-bullseye"></i>Purpose *</label>
          <input type="text" class="field-input" name="purpose" placeholder="e.g. For employment, scholarship, bank requirement…" required>
        </div>
      </div>

      <!-- SERVICE SPECIFIC FIELDS -->
      <div id="serviceSpecificFields"></div>

      <!-- DELIVERY -->
      <div class="form-section-title mt-2"><i class="fa-solid fa-truck"></i>How to Receive Your Document</div>
      <div class="delivery-toggle">
        <button type="button" class="deliv-btn active" id="btnPickup" onclick="setDelivery('Pickup')">
          <i class="fa-solid fa-building-columns"></i>Pickup at Barangay Hall
        </button>
        <button type="button" class="deliv-btn" id="btnDelivery" onclick="setDelivery('Delivery')">
          <i class="fa-solid fa-truck"></i>Home Delivery
        </button>
      </div>
      <div id="addressField" style="display:none;" class="mb-3">
        <label class="field-label"><i class="fa-solid fa-location-dot"></i>Delivery Address *</label>
        <input type="text" class="field-input" name="delivery_address" id="deliveryAddress" placeholder="House No., Street, Purok, Barangay Alapan I-A">
      </div>

      <!-- PAYMENT -->
      <div id="paymentSection">
        <div class="form-section-title mt-2"><i class="fa-solid fa-credit-card"></i>Payment Method</div>
        <div class="payment-grid">
          <div class="pay-card" id="pay-cash"   onclick="setPayment('Cash on Pickup')"><i class="fa-solid fa-money-bill-wave"></i>Cash on Pickup</div>
          <div class="pay-card" id="pay-gcash"  onclick="setPayment('GCash')"><i class="fa-solid fa-mobile-screen-button"></i>GCash</div>
          <div class="pay-card" id="pay-maya"   onclick="setPayment('Maya')"><i class="fa-solid fa-wallet"></i>Maya</div>
          <div class="pay-card" id="pay-cod"    onclick="setPayment('Cash on Delivery')"><i class="fa-solid fa-hand-holding-dollar"></i>Cash on Delivery</div>
        </div>
      </div>
      <p style="font-size:11px;color:#777;margin-bottom:22px;" id="payNote"></p>

      <!-- NOTES -->
      <div class="row g-3 mb-4">
        <div class="col-12">
          <label class="field-label"><i class="fa-solid fa-comment"></i>Additional Notes <span style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <textarea class="field-input" name="notes" rows="3" placeholder="Any other information or special instructions…" style="resize:vertical;"></textarea>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn" disabled>
        <i class="fa-solid fa-paper-plane"></i>Submit Request
      </button>
      <p style="font-size:12px;color:#aaa;text-align:center;margin-top:10px;"><i class="fa-solid fa-lock me-1"></i>Confirm ownership and select a payment method to enable submission.</p>

    </form>
  </div>

  <div class="auth-note mt-4">
    <i class="fa-solid fa-shield-halved"></i>Barangay Alapan I-A &middot; Imus, Cavite &middot; Official Portal &middot; 2026
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const services = {
  'Barangay Clearance':                 { fee:'₱50 · 1–2 days',  free:false, req:['Valid government-issued ID (from profile)','Cedula / Community Tax Certificate'], fields:'clearance' },
  'Certificate of Residency':           { fee:'₱50 · 1–2 days',  free:false, req:['Valid government-issued ID (from profile)','Proof of address (utility bill or lease contract)'], fields:'residency' },
  'Certificate of Indigency':           { fee:'Free · 1–2 days', free:true,  req:['Valid government-issued ID (from profile)','Proof of income or unemployment'], fields:'indigency' },
  'Certificate of Good Moral Character':{ fee:'₱50 · 1–2 days',  free:false, req:['Valid government-issued ID (from profile)','Cedula / Community Tax Certificate'], fields:'goodmoral' },
  'Certificate of No Income':           { fee:'Free · 1–2 days', free:true,  req:['Valid government-issued ID (from profile)','Sworn statement of no income'], fields:'noincome' },
  'Certificate of First Time Job Seeker':{ fee:'Free · Same day', free:true, req:['Valid government-issued ID (from profile)','Proof of being a first-time job seeker (diploma, school records, or affidavit)'], fields:'firstjob' },
  'Certificate of Solo Parent':         { fee:'Free · 1–2 days', free:true,  req:['Valid government-issued ID (from profile)','Solo Parent ID or proof of solo parent status'], fields:'soloparent' },
  'Certificate of Cohabitation':        { fee:'₱50 · 1–2 days',  free:false, req:['Your valid government-issued ID (from profile)','Valid ID of Partner','Proof of shared residence (utility bill or lease contract)'], fields:'cohabitation' },
  'Barangay ID':                        { fee:'₱100 · 3–5 days', free:false, req:['1x1 or 2x2 ID photo with white background','Valid government-issued ID (from profile)'], fields:'barangayid' },
  'Business Permit (New)':              { fee:'₱200 · 3–5 days', free:false, req:['Valid government-issued ID (from profile)','DTI or SEC registration','Lease contract or proof of business location','Cedula / Community Tax Certificate'], fields:'businessnew' },
  'Business Permit (Renewal)':          { fee:'₱150 · 1–3 days', free:false, req:['Valid government-issued ID (from profile)','Previous barangay business permit','Updated Cedula / Community Tax Certificate'], fields:'businessrenewal' },
  'PWD Certificate':                    { fee:'Free · 1–2 days', free:true,  req:['Valid government-issued ID (from profile)','Medical certificate or PWD diagnosis','Recent 1x1 photo'], fields:'pwd' },
  'Senior Citizen Endorsement':         { fee:'Free · Same day', free:true,  req:['Valid government-issued ID (from profile)','PSA Birth Certificate or Senior Citizen ID'], fields:'seniorcitizen' },
  '4Ps / DSWD Endorsement':             { fee:'Free · 1–2 days', free:true,  req:['Valid government-issued ID (from profile)','Proof of indigency or 4Ps membership'], fields:'dswd' },
  'Burial Assistance Request':          { fee:'Free · Same day', free:true,  req:['Valid government-issued ID (from profile)','Death certificate of the deceased','Proof of indigency'], fields:'burial' },
  'Financial Assistance Request':       { fee:'Free · 2–3 days', free:true,  req:['Valid government-issued ID (from profile)','Proof of emergency or hardship (medical bill, enrollment form, etc.)'], fields:'financial' },
  'Barangay Endorsement Letter':        { fee:'Free · Same day', free:true,  req:['Valid government-issued ID (from profile)'], fields:'endorsement' },
  'Barangay Protection Order':          { fee:'Free · Same day', free:true,  req:['Valid government-issued ID (from profile)','Written account of the incident or abuse','Supporting evidence (optional)'], fields:'bpo' }
};

// Field templates per service type
const fieldTemplates = {

  clearance: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-hashtag"></i>Cedula / CTC No. *</label><input type="text" class="field-input" name="cedula_number" placeholder="e.g. 12345678"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Cedula *</label><div class="upload-zone" id="uz-cedula"><input type="file" name="cedula_photo" accept="image/*,.pdf" onchange="markUpload(this,'uz-cedula','fn-cedula')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-cedula"></div></div></div>
    </div>`,

  residency: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-calendar"></i>Years Residing in Barangay *</label><input type="number" class="field-input" name="years_residing" placeholder="e.g. 5" min="1"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-location-dot"></i>Full Home Address *</label><input type="text" class="field-input" name="full_address" placeholder="House No., Street, Purok"></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Address *</label><div class="upload-zone" id="uz-proof-addr"><input type="file" name="proof_address" accept="image/*,.pdf" onchange="markUpload(this,'uz-proof-addr','fn-proof-addr')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Utility bill or lease contract · Max 5MB</div><div class="upload-filename" id="fn-proof-addr"></div></div></div>
    </div>`,

  indigency: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-peso-sign"></i>Monthly Household Income *</label><input type="text" class="field-input" name="monthly_income" placeholder="e.g. 5000 or None / Unemployed"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-people-group"></i>No. of Family Members *</label><input type="number" class="field-input" name="family_members" placeholder="e.g. 4" min="1"></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Income / Unemployment *</label><div class="upload-zone" id="uz-income"><input type="file" name="proof_income" accept="image/*,.pdf" onchange="markUpload(this,'uz-income','fn-income')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Pay slip, unemployment cert, or sworn statement · Max 5MB</div><div class="upload-filename" id="fn-income"></div></div></div>
    </div>`,

  goodmoral: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-hashtag"></i>Cedula / CTC No. *</label><input type="text" class="field-input" name="cedula_number" placeholder="e.g. 12345678"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Cedula *</label><div class="upload-zone" id="uz-cedula-gm"><input type="file" name="cedula_photo_gm" accept="image/*,.pdf" onchange="markUpload(this,'uz-cedula-gm','fn-cedula-gm')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-cedula-gm"></div></div></div>
    </div>`,

  noincome: `
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Sworn Statement of No Income *</label><div class="upload-zone" id="uz-sworn"><input type="file" name="sworn_statement" accept="image/*,.pdf" onchange="markUpload(this,'uz-sworn','fn-sworn')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Notarized affidavit or barangay-issued statement · Max 5MB</div><div class="upload-filename" id="fn-sworn"></div></div></div>
    </div>`,

  firstjob: `
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of First-Time Job Seeker Status *</label><div class="upload-zone" id="uz-firstjob"><input type="file" name="firstjob_proof" accept="image/*,.pdf" onchange="markUpload(this,'uz-firstjob','fn-firstjob')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Diploma, school records, or affidavit · Max 5MB</div><div class="upload-filename" id="fn-firstjob"></div></div></div>
    </div>`,

  soloparent: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-hashtag"></i>Solo Parent ID No. <span style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;">(if available)</span></label><input type="text" class="field-input" name="solo_parent_id" placeholder="e.g. SP-2024-001"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Solo Parent Status *</label><div class="upload-zone" id="uz-sp"><input type="file" name="solo_parent_proof" accept="image/*,.pdf" onchange="markUpload(this,'uz-sp','fn-sp')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-sp"></div></div></div>
    </div>`,

  cohabitation: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-user"></i>Partner's Full Name *</label><input type="text" class="field-input" name="partner_name" placeholder="e.g. Maria Santos dela Cruz"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-calendar"></i>Partner's Date of Birth *</label><input type="date" class="field-input" name="partner_dob"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Partner's Valid ID *</label><div class="upload-zone" id="uz-partner-id"><input type="file" name="partner_id" accept="image/*,.pdf" onchange="markUpload(this,'uz-partner-id','fn-partner-id')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Both sides · Max 5MB</div><div class="upload-filename" id="fn-partner-id"></div></div></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Shared Residence *</label><div class="upload-zone" id="uz-shared-res"><input type="file" name="shared_residence" accept="image/*,.pdf" onchange="markUpload(this,'uz-shared-res','fn-shared-res')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Utility bill or lease contract · Max 5MB</div><div class="upload-filename" id="fn-shared-res"></div></div></div>
    </div>`,

  barangayid: `
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-palette"></i>Preferred ID Color *</label><select class="field-input" name="id_color"><option value="">Select…</option><option>Blue</option><option>Green</option><option>Red</option></select></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-droplet"></i>Blood Type *</label><select class="field-input" name="blood_type"><option value="">Select…</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option><option>Unknown</option></select></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-phone"></i>Emergency Contact Name *</label><input type="text" class="field-input" name="emergency_contact" placeholder="Full name"></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-mobile-screen"></i>Emergency Contact No. *</label><input type="text" class="field-input" name="emergency_contact_num" placeholder="09XX-XXX-XXXX"></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-image"></i>Upload 1x1 / 2x2 ID Photo *</label><div class="upload-zone" id="uz-idphoto"><input type="file" name="id_photo" accept="image/*" onchange="markUpload(this,'uz-idphoto','fn-idphoto')"><div class="upload-zone-text"><strong>Click to upload</strong><br>White background · JPG or PNG · Max 5MB</div><div class="upload-filename" id="fn-idphoto"></div></div></div>
    </div>`,

  businessnew: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-store"></i>Business Name *</label><input type="text" class="field-input" name="business_name" placeholder="e.g. Juan's Sari-Sari Store"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-briefcase"></i>Business Type *</label><select class="field-input" name="business_type"><option value="">Select…</option><option>Sari-Sari Store</option><option>Food Stall</option><option>Repair Shop</option><option>Salon / Barbershop</option><option>Online Selling</option><option>Other</option></select></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-location-dot"></i>Business Address *</label><input type="text" class="field-input" name="business_address" placeholder="House No., Street, Purok"></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload DTI / SEC Registration *</label><div class="upload-zone" id="uz-dti"><input type="file" name="dti_reg" accept="image/*,.pdf" onchange="markUpload(this,'uz-dti','fn-dti')"><div class="upload-zone-text"><strong>Click to upload</strong><br>If applicable · Max 5MB</div><div class="upload-filename" id="fn-dti"></div></div></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-file-contract"></i>Upload Lease / Proof of Location *</label><div class="upload-zone" id="uz-lease"><input type="file" name="lease_contract" accept="image/*,.pdf" onchange="markUpload(this,'uz-lease','fn-lease')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-lease"></div></div></div>
      <div class="col-md-4"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Cedula *</label><div class="upload-zone" id="uz-cedula-biz"><input type="file" name="cedula_biz" accept="image/*,.pdf" onchange="markUpload(this,'uz-cedula-biz','fn-cedula-biz')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-cedula-biz"></div></div></div>
    </div>`,

  businessrenewal: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-store"></i>Business Name *</label><input type="text" class="field-input" name="business_name" placeholder="e.g. Juan's Sari-Sari Store"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-briefcase"></i>Business Type *</label><select class="field-input" name="business_type"><option value="">Select…</option><option>Sari-Sari Store</option><option>Food Stall</option><option>Repair Shop</option><option>Salon / Barbershop</option><option>Online Selling</option><option>Other</option></select></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-location-dot"></i>Business Address *</label><input type="text" class="field-input" name="business_address" placeholder="House No., Street, Purok"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Previous Business Permit *</label><div class="upload-zone" id="uz-prev-permit"><input type="file" name="prev_permit" accept="image/*,.pdf" onchange="markUpload(this,'uz-prev-permit','fn-prev-permit')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-prev-permit"></div></div></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Updated Cedula *</label><div class="upload-zone" id="uz-cedula-renew"><input type="file" name="cedula_renewal" accept="image/*,.pdf" onchange="markUpload(this,'uz-cedula-renew','fn-cedula-renew')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-cedula-renew"></div></div></div>
    </div>`,

  pwd: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-wheelchair"></i>Type of Disability *</label><select class="field-input" name="pwd_type"><option value="">Select…</option><option>Physical</option><option>Visual</option><option>Hearing</option><option>Speech</option><option>Intellectual</option><option>Psychosocial</option><option>Other</option></select></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Medical Certificate / PWD Diagnosis *</label><div class="upload-zone" id="uz-pwd-cert"><input type="file" name="pwd_cert" accept="image/*,.pdf" onchange="markUpload(this,'uz-pwd-cert','fn-pwd-cert')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-pwd-cert"></div></div></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-image"></i>Upload Recent 1x1 Photo *</label><div class="upload-zone" id="uz-pwd-photo"><input type="file" name="pwd_photo" accept="image/*" onchange="markUpload(this,'uz-pwd-photo','fn-pwd-photo')"><div class="upload-zone-text"><strong>Click to upload</strong><br>White background · JPG or PNG · Max 5MB</div><div class="upload-filename" id="fn-pwd-photo"></div></div></div>
    </div>`,

  seniorcitizen: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-hashtag"></i>OSCA ID No. <span style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;">(if available)</span></label><input type="text" class="field-input" name="sc_osca_id" placeholder="e.g. SC-2024-001"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload PSA Birth Certificate or Senior Citizen ID *</label><div class="upload-zone" id="uz-sc-proof"><input type="file" name="sc_proof" accept="image/*,.pdf" onchange="markUpload(this,'uz-sc-proof','fn-sc-proof')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Max 5MB</div><div class="upload-filename" id="fn-sc-proof"></div></div></div>
    </div>`,

  dswd: `
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Indigency or 4Ps Membership *</label><div class="upload-zone" id="uz-dswd-proof"><input type="file" name="dswd_proof" accept="image/*,.pdf" onchange="markUpload(this,'uz-dswd-proof','fn-dswd-proof')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-dswd-proof"></div></div></div>
    </div>`,

  burial: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-user"></i>Name of Deceased *</label><input type="text" class="field-input" name="deceased_name" placeholder="Full name of the deceased"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-people-arrows"></i>Your Relationship to Deceased *</label><input type="text" class="field-input" name="deceased_relationship" placeholder="e.g. Son, Daughter, Spouse"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Death Certificate *</label><div class="upload-zone" id="uz-death-cert"><input type="file" name="death_cert" accept="image/*,.pdf" onchange="markUpload(this,'uz-death-cert','fn-death-cert')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-death-cert"></div></div></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Proof of Indigency *</label><div class="upload-zone" id="uz-burial-ind"><input type="file" name="burial_indigency" accept="image/*,.pdf" onchange="markUpload(this,'uz-burial-ind','fn-burial-ind')"><div class="upload-zone-text"><strong>Click to upload</strong><br>JPG, PNG or PDF · Max 5MB</div><div class="upload-filename" id="fn-burial-ind"></div></div></div>
    </div>`,

  financial: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-list"></i>Type of Assistance *</label><select class="field-input" name="assistance_type"><option value="">Select…</option><option>Medical</option><option>Educational</option><option>Emergency</option><option>Livelihood</option><option>Other</option></select></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-comment-dots"></i>Reason for Request *</label><textarea class="field-input" name="assistance_reason" rows="3" placeholder="Briefly describe your situation and why you need assistance…" style="resize:vertical;"></textarea></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Supporting Document *</label><div class="upload-zone" id="uz-fin-proof"><input type="file" name="financial_proof" accept="image/*,.pdf" onchange="markUpload(this,'uz-fin-proof','fn-fin-proof')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Medical bill, enrollment form, or other supporting proof · Max 5MB</div><div class="upload-filename" id="fn-fin-proof"></div></div></div>
    </div>`,

  endorsement: `
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-building"></i>Addressed To *</label><input type="text" class="field-input" name="endorsement_recipient" placeholder="e.g. NBI, DFA Passport Office, RTC Branch 20"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-bullseye"></i>Specific Purpose *</label><input type="text" class="field-input" name="endorsement_purpose" placeholder="e.g. NBI Clearance application, Passport renewal"></div>
    </div>`,

  bpo: `
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-user-slash"></i>Name of Respondent / Abuser *</label><input type="text" class="field-input" name="respondent" placeholder="Full name of the person committing abuse"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-calendar-day"></i>Date of Last Incident *</label><input type="date" class="field-input" name="incident_date"></div>
      <div class="col-md-6"><label class="field-label"><i class="fa-solid fa-location-dot"></i>Place of Incident *</label><input type="text" class="field-input" name="incident_place" placeholder="e.g. Home address, Purok 3"></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-comment-dots"></i>Describe the Incident *</label><textarea class="field-input" name="incident_desc" rows="4" placeholder="Provide a detailed account of the abuse or violence…" style="resize:vertical;"></textarea></div>
      <div class="col-12"><label class="field-label"><i class="fa-solid fa-upload"></i>Upload Evidence <span style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;">(optional)</span></label><div class="upload-zone" id="uz-bpo-ev"><input type="file" name="bpo_evidence" accept="image/*,.pdf" onchange="markUpload(this,'uz-bpo-ev','fn-bpo-ev')"><div class="upload-zone-text"><strong>Click to upload</strong><br>Photos, screenshots, or documents · Max 5MB</div><div class="upload-filename" id="fn-bpo-ev"></div></div></div>
    </div>`
};

let currentService = '';
let paymentChosen  = false;

function filterCat(cat, btn) {
  document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.service-card').forEach(card => {
    const match = cat === 'all' || card.dataset.cat === cat;
    card.classList.toggle('hidden', !match);
  });
  applySearch();
}

function searchServices() { applySearch(); }

function applySearch() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  let visible = 0;
  document.querySelectorAll('.service-card:not(.hidden)').forEach(card => {
    const text = card.innerText.toLowerCase();
    const show = !q || text.includes(q);
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

function selectService(card, name) {
  document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  currentService = name;

  const svc = services[name];
  document.getElementById('serviceTypeInput').value  = name;
  document.getElementById('summaryName').textContent = name;
  document.getElementById('summaryFee').textContent  = svc.fee;

  // Find icon from clicked card
  const iconEl = card.querySelector('.svc-icon i');
  const iconClass = iconEl ? iconEl.className : 'fa-solid fa-file';
  document.getElementById('summaryIcon').innerHTML = `<i class="${iconClass}"></i>`;

  document.getElementById('reqList').innerHTML = svc.req
    .map(r => `<div class="req-item"><i class="fa-solid fa-circle-dot"></i>${r}</div>`).join('');

  document.getElementById('serviceSpecificFields').innerHTML = fieldTemplates[svc.fields] || '';

  setDelivery('Pickup');

  const panel = document.getElementById('formPanel');
  panel.classList.add('show');
  setTimeout(() => panel.scrollIntoView({ behavior:'smooth', block:'start' }), 100);
}

function setDelivery(mode) {
  document.getElementById('deliveryInput').value = mode;
  document.getElementById('btnPickup').classList.toggle('active',   mode === 'Pickup');
  document.getElementById('btnDelivery').classList.toggle('active', mode === 'Delivery');
  document.getElementById('addressField').style.display = mode === 'Delivery' ? 'block' : 'none';
  document.getElementById('deliveryAddress') && (document.getElementById('deliveryAddress').required = mode === 'Delivery');

  const isFree     = currentService ? services[currentService]?.free : false;
  const paySection = document.getElementById('paymentSection');
  const payNote    = document.getElementById('payNote');
  const payCash    = document.getElementById('pay-cash');
  const payGcash   = document.getElementById('pay-gcash');
  const payMaya    = document.getElementById('pay-maya');
  const payCod     = document.getElementById('pay-cod');

  paymentChosen = false;
  ['pay-cash','pay-gcash','pay-maya','pay-cod'].forEach(id => document.getElementById(id).classList.remove('active'));
  document.getElementById('paymentInput').value = '';

  if (isFree && mode === 'Pickup') {
    paySection.style.display = 'none';
    payNote.textContent = 'This document is free. No payment required. Just present your valid ID when picking up.';
    document.getElementById('paymentInput').value = 'Free';
    paymentChosen = true;
  } else if (isFree && mode === 'Delivery') {
    paySection.style.display = 'block';
    payCash.style.display = 'none'; payGcash.style.display = 'none'; payMaya.style.display = 'none'; payCod.style.display = '';
    payNote.textContent = 'This document is free. Cash on delivery covers the courier fee only.';
  } else if (mode === 'Pickup') {
    paySection.style.display = 'block';
    payCash.style.display = ''; payGcash.style.display = ''; payMaya.style.display = ''; payCod.style.display = 'none';
    payNote.textContent = '';
  } else {
    paySection.style.display = 'block';
    payCash.style.display = 'none'; payGcash.style.display = ''; payMaya.style.display = ''; payCod.style.display = '';
    payNote.textContent = '';
  }
  checkSubmit();
}

function setPayment(method) {
  document.getElementById('paymentInput').value = method;
  ['pay-cash','pay-gcash','pay-maya','pay-cod'].forEach(id => document.getElementById(id).classList.remove('active'));
  const notes = {
    'Cash on Pickup':   'Bring the exact amount when you collect at the barangay hall.',
    'GCash':            'You will receive a GCash payment request once your request is approved.',
    'Maya':             'You will receive a Maya payment link once your request is approved.',
    'Cash on Delivery': 'Pay the courier in cash when your document arrives at your address.'
  };
  if (method) {
    const map = {'Cash on Pickup':'pay-cash','GCash':'pay-gcash','Maya':'pay-maya','Cash on Delivery':'pay-cod'};
    document.getElementById(map[method]).classList.add('active');
    document.getElementById('payNote').textContent = notes[method];
    paymentChosen = true;
  } else { paymentChosen = false; }
  checkSubmit();
}

function checkSubmit() {
  const confirmed = document.getElementById('ownConfirm').checked;
  document.getElementById('submitBtn').disabled = !(confirmed && paymentChosen);
}

document.getElementById('ownConfirm').addEventListener('change', checkSubmit);

function markUpload(input, zoneId, fnId) {
  if (input.files && input.files[0]) {
    document.getElementById(zoneId).classList.add('has-file');
    const fn = document.getElementById(fnId);
    fn.innerHTML = '<i class="fa-solid fa-check me-1"></i>' + input.files[0].name;
    fn.style.display = 'block';
  }
}
</script>
</body>
</html>