<?php
$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    $conn = null;
}

$registeredCount = 0;
if ($conn) {
    $cntStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE LTRIM(RTRIM(STATUS)) = 'active'");
    if ($cntStmt) {
        $cntRow          = sqlsrv_fetch_array($cntStmt, SQLSRV_FETCH_ASSOC);
        $registeredCount = $cntRow ? (int)$cntRow['CNT'] : 0;
    }
}

$positions = [
    'Punong Barangay' => null,
    'Kagawad'         => [],
    'Secretary'       => null,
    'Treasurer'       => null,
];

if ($conn) {
    $staffStmt = sqlsrv_query($conn,
        "SELECT U.USER_ID, U.POSITION,
                R.FIRST_NAME, R.MIDDLE_NAME, R.LAST_NAME, R.SUFFIX, R.PROFILE_PICTURE
         FROM USERS U
         LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
         WHERE LTRIM(RTRIM(U.ROLE)) = 'staff'
           AND U.POSITION IS NOT NULL"
    );
    if ($staffStmt) {
        while ($row = sqlsrv_fetch_array($staffStmt, SQLSRV_FETCH_ASSOC)) {
            $pos = rtrim($row['POSITION'] ?? '');
            $fn  = rtrim($row['FIRST_NAME']  ?? '');
            $mn  = rtrim($row['MIDDLE_NAME'] ?? '');
            $ln  = rtrim($row['LAST_NAME']   ?? '');
            $sx  = rtrim($row['SUFFIX']      ?? '');
            if (!$pos) continue;
            $fullName = trim($fn . ($mn ? ' ' . $mn : '') . ' ' . $ln . ($sx ? ' ' . $sx : ''));
            $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
            $pic      = !empty($row['PROFILE_PICTURE']) ? htmlspecialchars(rtrim($row['PROFILE_PICTURE'])) : '';
            $entry    = [
                'name'     => htmlspecialchars($fullName),
                'initials' => $initials,
                'pic'      => $pic,
            ];
            if ($pos === 'Kagawad') {
                $positions['Kagawad'][] = $entry;
            } elseif (array_key_exists($pos, $positions)) {
                $positions[$pos] = $entry;
            }
        }
    }
}

function renderAvatar($entry) {
    if ($entry && !empty($entry['pic'])) {
        echo '<img src="' . $entry['pic'] . '" alt="' . $entry['name'] . '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
    } elseif ($entry && !empty($entry['initials'])) {
        echo '<span>' . $entry['initials'] . '</span>';
    } else {
        echo '<i class="fa-solid fa-user" style="font-size:22px;opacity:0.35;"></i>';
    }
}

function renderOfficialCard($entry, $role, $isCapt = false) {
    $cardClass = $isCapt ? 'official-card is-captain' : 'official-card';
    $roleIcon  = $isCapt ? '<i class="fa-solid fa-star me-1"></i>' : '';
    echo '<div class="' . $cardClass . '" style="height:100%;min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;">';
    echo '<div class="official-avatar">';
    renderAvatar($entry);
    echo '</div>';
    if ($entry) {
        echo '<div class="official-name" style="font-size:13px;text-align:center;line-height:1.35;">' . $entry['name'] . '</div>';
    } else {
        echo '<div class="official-name" style="opacity:0.3;font-style:italic;font-size:13px;">Unassigned</div>';
    }
    echo '<div class="official-role" style="font-size:10px;letter-spacing:1.5px;">' . $roleIcon . htmlspecialchars($role) . '</div>';
    echo '</div>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Barangay Alapan I-A</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="home.css" />
  <style>
    /* ── SERVICES GRID: 4 columns, 8 cards = 2 clean rows ── */
    .services-grid-wrap {
      display: grid !important;
      grid-template-columns: repeat(4, 1fr) !important;
      gap: 1px;
      background: rgba(5,22,80,0.12);
      overflow: visible !important;
    }
    @media (max-width: 900px) {
      .services-grid-wrap { grid-template-columns: repeat(2, 1fr) !important; }
    }
    @media (max-width: 576px) {
      .services-grid-wrap { grid-template-columns: 1fr !important; }
    }

    /* ── OFFICIALS FLEX GRID: centered last row ── */
    .officials-flex-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
    }
    .official-col {
      flex: 0 0 calc(25% - 9px);
      max-width: calc(25% - 9px);
    }
    @media (max-width: 900px) {
      .official-col { flex: 0 0 calc(50% - 6px); max-width: calc(50% - 6px); }
    }
    @media (max-width: 480px) {
      .official-col { flex: 0 0 100%; max-width: 100%; }
    }

 /* ── SERVICES: smooth slide in/out ── */
.reveal-left {
  opacity: 0;
  transform: translateX(-90px);
  transition:
    opacity 0.7s ease,
    transform 0.7s cubic-bezier(.22,1,.36,1);
  will-change: transform, opacity;
}

.reveal-left.in {
  opacity: 1;
  transform: translateX(0);
}

.reveal-left.in[data-delay="1"] { transition-delay: 0.05s; }
.reveal-left.in[data-delay="2"] { transition-delay: 0.10s; }
.reveal-left.in[data-delay="3"] { transition-delay: 0.15s; }
.reveal-left.in[data-delay="4"] { transition-delay: 0.20s; }
.reveal-left.in[data-delay="5"] { transition-delay: 0.25s; }
.reveal-left.in[data-delay="6"] { transition-delay: 0.30s; }
.reveal-left.in[data-delay="7"] { transition-delay: 0.35s; }
.reveal-left.in[data-delay="8"] { transition-delay: 0.40s; }

    /* ── SLIDE FROM BELOW: officials, labels, notices ── */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      filter: blur(5px);
      transition: opacity 0.6s ease, transform 0.6s ease, filter 0.6s ease;
    }
    .reveal.in {
      opacity: 1;
      transform: translateY(0);
      filter: blur(0);
    }
    .reveal:not(.in) { transition-delay: 0s !important; }
    .reveal.in[data-delay="1"] { transition-delay: 0.06s; }
    .reveal.in[data-delay="2"] { transition-delay: 0.12s; }
    .reveal.in[data-delay="3"] { transition-delay: 0.18s; }
    .reveal.in[data-delay="4"] { transition-delay: 0.24s; }
    .reveal.in[data-delay="5"] { transition-delay: 0.30s; }
    .reveal.in[data-delay="6"] { transition-delay: 0.36s; }
    .reveal.in[data-delay="7"] { transition-delay: 0.42s; }
    .reveal.in[data-delay="8"] { transition-delay: 0.48s; }
  </style>
</head>
<body>

<div class="modal-gate" id="authModal">
  <div class="modal-gate-box">
    <button class="modal-gate-close" onclick="closeModal()" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="modal-gate-icon"><i class="fa-solid fa-lock"></i></div>
    <h5 class="fw-bold mb-2" style="color:var(--dark)">Login Required</h5>
    <p class="text-muted mb-2" style="font-size:14px">You need a resident account to access this service.</p>
    <div class="modal-service-badge" id="modalServiceName">Barangay Clearance</div>
    <div class="d-flex flex-column gap-2">
      <a href="login.php" class="btn-modal-primary">
        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Login to My Account
      </a>
      <div class="text-muted my-1" style="font-size:13px">or</div>
      <a href="register.php" class="btn-modal-secondary">
        <i class="fa-solid fa-user-plus me-2"></i>Create a New Account
      </a>
    </div>
  </div>
</div>

<nav class="site-nav">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between">
      <a href="home.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="nav-seal">
          <img src="alapan.png" alt="Barangay Alapan I-A Logo" />
        </div>
        <div>
          <span class="nav-brgy">Barangay</span>
          <span class="nav-name">Alapan I-A</span>
        </div>
      </a>
      <ul class="nav d-none d-md-flex mb-0">
        <li class="nav-item">
          <a class="nav-link" href="#services"><i class="fa-solid fa-grip me-1"></i>Services</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#officials"><i class="fa-solid fa-users me-1"></i>Officials</a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <a href="login.php" class="btn-nav-login"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Login</a>
        <a href="register.php" class="btn-nav-cta"><i class="fa-solid fa-user-plus me-1"></i>Register</a>
      </div>
    </div>
  </div>
</nav>

<section class="hero-section">
  <video class="hero-video" autoplay loop muted playsinline>
    <source src="herobg.mp4" type="video/mp4" />
  </video>
  <div class="hero-overlay"></div>
  <div class="container-xl w-100">
    <div class="row align-items-center gy-5 py-5">
      <div class="col-lg-6">
        <div class="hero-tag mb-3">
          <i class="fa-solid fa-flag"></i>
          Birthplace of the Philippine Flag &middot; Imus, Cavite
        </div>
        <h1 class="mb-4">Barangay<br /><span>Alapan I-A</span></h1>
        <p class="hero-sub mb-4">
          Serving the residents of Alapan I-A with integrity and dedication.
          Part of Imus City — the Flag Capital of the Philippines, where the
          Philippine flag was first unfurled in the Battle of Alapan on May 28, 1898.
        </p>
        <a href="login.php" class="btn-hero">
          <i class="fa-solid fa-layer-group"></i> Access Services
        </a>
      </div>
      <div class="col-lg-6">
        <div class="d-flex flex-column gap-3">
          <div class="hero-stat-card">
            <div class="hero-stat-icon"><i class="fa-solid fa-people-group"></i></div>
            <div>
              <div class="hero-stat-label">Population (2020 Census)</div>
              <div class="hero-stat-value">14,097</div>
              <div class="hero-stat-sub">Latest official PSA count for Alapan I-A</div>
            </div>
          </div>
          <div class="hero-stat-card">
            <div class="hero-stat-icon"><i class="fa-solid fa-user-check"></i></div>
            <div>
              <div class="hero-stat-label">Registered Accounts</div>
              <div class="hero-stat-value"><?php echo number_format($registeredCount); ?></div>
              <div class="hero-stat-sub">Active accounts in the system</div>
            </div>
          </div>
          <div class="hero-stat-card">
            <div class="hero-stat-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <div class="hero-stat-label">Location</div>
              <div class="hero-stat-value" style="font-size:20px">Imus, Cavite</div>
              <div class="hero-stat-sub">CALABARZON (Region IV-A) &middot; ZIP 4103</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="services-section py-5" id="services" style="overflow-x:clip;">
  <div class="container-xl">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
      <div>
        <div class="section-label reveal" data-delay="1"><i class="fa-solid fa-grid-2"></i>What we offer</div>
        <h2 class="section-title reveal" data-delay="2">Barangay Services</h2>
      </div>
      <p class="text-muted mb-0 reveal" data-delay="3" style="max-width:340px;font-size:15px">
        All government services you need, accessible right here in your barangay.
      </p>
    </div>
    <div class="login-notice mb-4 reveal" data-delay="4">
      <i class="fa-solid fa-lock"></i>
      <span>Accessing any service requires a resident account.
        <a href="login.php">Login here</a> — or
        <a href="register.php">create a free account</a> to get started.
      </span>
    </div>
    <div class="services-grid-wrap">

      <div class="service-card reveal-left" data-delay="1" onclick="requireLogin('Barangay Clearance')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-stamp"></i></div>
        <div class="service-title">Barangay Clearance</div>
        <div class="service-desc">General purpose clearance for employment, permits, and other legal transactions.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="2" onclick="requireLogin('Certificate of Indigency')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div class="service-title">Certificate of Indigency</div>
        <div class="service-desc">For residents applying for government assistance or social benefit programs. Free of charge.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="3" onclick="requireLogin('Certificate of Residency')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-house-circle-check"></i></div>
        <div class="service-title">Certificate of Residency</div>
        <div class="service-desc">Confirms that an individual officially resides in the barangay. Required for enrollment and government transactions.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="4" onclick="requireLogin('Business Permit Clearance')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-briefcase"></i></div>
        <div class="service-title">Business Permit Clearance</div>
        <div class="service-desc">Barangay clearance required for all business establishments operating within the barangay.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="5" onclick="requireLogin('Barangay ID')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-id-card"></i></div>
        <div class="service-title">Barangay ID</div>
        <div class="service-desc">Official barangay identification card issued to registered residents of Alapan I-A.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="6" onclick="requireLogin('Certificate of Good Moral')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-award"></i></div>
        <div class="service-title">Certificate of Good Moral</div>
        <div class="service-desc">Attests to the good moral character of a resident. Required for employment or school applications.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="7" onclick="requireLogin('Solo Parent Certificate')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-person-breastfeeding"></i></div>
        <div class="service-title">Solo Parent Certificate</div>
        <div class="service-desc">For solo parents to avail of government benefits and assistance programs. Free of charge.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

      <div class="service-card reveal-left" data-delay="8" onclick="requireLogin('Senior Citizen Certificate')">
        <div class="service-lock"><i class="fa-solid fa-lock"></i> Login required</div>
        <div class="service-icon-wrap"><i class="fa-solid fa-user-clock"></i></div>
        <div class="service-title">Senior Citizen Certificate</div>
        <div class="service-desc">Certificate for senior citizens aged 60 and above. Free of charge and required for senior benefits.</div>
        <span class="service-arrow">Request now <i class="fa-solid fa-arrow-right"></i></span>
      </div>

    </div>
  </div>
</section>

<section class="officials-section py-5" id="officials">
  <div class="container-xl">
    <div class="section-label reveal" data-delay="1"><i class="fa-solid fa-star"></i>Your leaders</div>
    <h2 class="section-title reveal" data-delay="2">Barangay Officials</h2>
    <div class="officials-flex-grid" style="margin-top:2rem;">

      <div class="official-col reveal" data-delay="1">
        <?php renderOfficialCard($positions['Punong Barangay'], 'Punong Barangay', true); ?>
      </div>

      <div class="official-col reveal" data-delay="2">
        <?php renderOfficialCard($positions['Secretary'], 'Barangay Secretary'); ?>
      </div>

      <div class="official-col reveal" data-delay="3">
        <?php renderOfficialCard($positions['Treasurer'], 'Barangay Treasurer'); ?>
      </div>

      <?php
      $kagawads = $positions['Kagawad'];
      $delays   = [4, 5, 6, 7, 8, 1, 2];
      for ($k = 0; $k < 7; $k++):
      ?>
      <div class="official-col reveal" data-delay="<?php echo $delays[$k]; ?>">
        <?php renderOfficialCard($kagawads[$k] ?? null, 'Kagawad'); ?>
      </div>
      <?php endfor; ?>

    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container-xl">
    <div class="row g-4">
      <div class="col-12 col-md-6">
        <div class="nav-seal mb-3">
          <img src="alapan.png" alt="Barangay Alapan I-A Logo" />
        </div>
        <div class="footer-brand-name">Barangay Alapan I-A</div>
        <p class="footer-brand-desc">
          Serving the residents of Alapan I-A with integrity, transparency,
          and dedication to public service. Imus City, Cavite — CALABARZON, Region IV-A.
        </p>
      </div>
      <div class="col-12 col-md-6 d-flex align-items-end justify-content-md-end">
        <p class="footer-brand-desc mb-0">
          <i class="fa-solid fa-location-dot me-1"></i>
          Barangay Alapan I-A, Imus City, Cavite 4103
        </p>
      </div>
    </div>
  </div>
  <div class="footer-bottom mt-5">
    <div class="container-xl d-flex flex-column flex-md-row justify-content-between gap-1">
      <p><i class="fa-regular fa-copyright me-1"></i>2026 Barangay Alapan I-A, Imus City, Cavite. All rights reserved.</p>
      <p>Part of the City of Imus — ZIP Code 4103</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function requireLogin(serviceName) {
  document.getElementById('modalServiceName').textContent = serviceName;
  document.getElementById('authModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('authModal').classList.remove('active');
  document.body.style.overflow = '';
}
document.getElementById('authModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {

    if (entry.isIntersecting) {
      entry.target.classList.add('in');
    } else {
      entry.target.classList.remove('in');
    }

  });
}, {
  threshold: 0.12
});

document.querySelectorAll('.reveal, .reveal-left').forEach(function(el) {
  observer.observe(el);
});

document.querySelectorAll('a[href="#services"], a[href="#officials"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    var target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.pushState(null, '', this.getAttribute('href'));
    }
  });
});
</script>
</body>
</html>