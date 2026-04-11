<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Request Documents – BarangayKonek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />

    <style>
      /* ── RESET & BASE ── */
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      body {
        font-family: 'DM Sans', sans-serif;
        background: #f0f2f5;
        color: #1a2236;
        min-height: 100vh;
      }

      /* ── LAYOUT ── */
      .container { display: flex; min-height: 100vh; }

      /* ── SIDEBAR ── */
      .sidebar {
        width: 240px;
        min-height: 100vh;
        background: #0f1b35;
        display: flex;
        flex-direction: column;
        padding: 0 0 24px;
        position: sticky;
        top: 0;
        flex-shrink: 0;
      }
      .sidebar-brand {
        padding: 28px 24px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin-bottom: 8px;
      }
      .sidebar-brand h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.3px;
      }
      .sidebar-brand span {
        font-size: 0.7rem;
        font-weight: 700;
        color: #b4ff39;
        text-transform: uppercase;
        letter-spacing: 1.5px;
      }

      /* Profile compact */
      .profile--compact {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 20px;
        margin: 4px 12px;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
      }
      .avatar-ring {
        width: 42px; height: 42px;
        border-radius: 50%;
        border: 2px solid #b4ff39;
        overflow: hidden;
        flex-shrink: 0;
        background: #1e3060;
        display: flex; align-items: center; justify-content: center;
      }
      .avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
      .avatar-placeholder { color: #b4ff39; font-size: 1.1rem; }
      .profile-meta h3 { font-size: 0.82rem; font-weight: 600; color: #fff; }
      .profile-meta p { font-size: 0.7rem; color: #8899bb; margin-top: 1px; }
      .portal-badge {
        display: inline-block;
        margin-top: 4px;
        background: #b4ff39;
        color: #0f1b35;
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 2px 7px;
        border-radius: 20px;
      }

      /* Nav */
      .menu { display: flex; flex-direction: column; gap: 2px; padding: 12px 12px 0; flex: 1; }
      .menu a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        color: #8899bb;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.18s, color 0.18s;
      }
      .menu a:hover { background: rgba(255,255,255,0.07); color: #fff; }
      .menu a.active { background: #b4ff39; color: #0f1b35; font-weight: 700; }
      .menu a.active .nav-icon { color: #0f1b35; }
      .nav-icon { font-size: 0.95rem; width: 18px; text-align: center; color: #8899bb; }
      .menu a.active .nav-icon, .menu a:hover .nav-icon { color: inherit; }

      .community-sidebar-section {
        padding: 20px 24px 6px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #4a5a7a;
      }

      .logout {
        display: flex; align-items: center; gap: 10px;
        margin: 8px 12px 0;
        padding: 10px 14px;
        border-radius: 10px;
        color: #ff6b6b;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        background: rgba(255,107,107,0.08);
        transition: background 0.18s;
      }
      .logout:hover { background: rgba(255,107,107,0.18); }

      /* ── MAIN CONTENT ── */
      .content { flex: 1; display: flex; flex-direction: column; }
      .content-inner { padding: 32px 36px; flex: 1; }

      /* Topbar */
      .topbar {
        display: flex; align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 28px;
      }
      .greeting-block h1 { font-size: 1.75rem; font-weight: 700; color: #1a2236; }
      .accent-name { color: #2563eb; }
      .subtitle { color: #667085; font-size: 0.9rem; margin-top: 4px; }

      /* User chip */
      .user-chip {
        display: flex; align-items: center; gap: 10px;
        background: #0f1b35;
        border-radius: 14px;
        padding: 10px 16px 10px 10px;
      }
      .user-chip-avatar-wrap {
        width: 38px; height: 38px; border-radius: 50%;
        overflow: hidden; border: 2px solid #b4ff39;
        background: #1e3060;
        display: flex; align-items: center; justify-content: center;
      }
      .user-chip-img { width: 100%; height: 100%; object-fit: cover; }
      .user-chip-info { display: flex; flex-direction: column; }
      .user-chip-name { font-size: 0.85rem; font-weight: 600; color: #fff; }
      .user-chip-role { font-size: 0.72rem; color: #8899bb; }
      .user-chip-bell-wrap {
        position: relative; margin-left: 6px;
        color: #b4ff39; font-size: 1rem; text-decoration: none;
      }
      .user-chip-notif {
        position: absolute; top: -6px; right: -7px;
        background: #ef4444; color: #fff;
        font-size: 0.58rem; font-weight: 700;
        width: 15px; height: 15px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        border: 1.5px solid #0f1b35;
      }

      /* ── SEARCH ── */
      .search-section { margin-bottom: 24px; }
      .search-label { font-size: 0.8rem; font-weight: 600; color: #667085; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; }
      .search-bar {
        display: flex; align-items: center; gap: 0;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        transition: border-color 0.18s, box-shadow 0.18s;
      }
      .search-bar:focus-within { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
      .search-bar i { padding: 0 14px; color: #94a3b8; font-size: 0.95rem; }
      .search-bar input {
        flex: 1; border: none; outline: none;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.95rem; color: #1a2236;
        padding: 14px 0;
        background: transparent;
      }
      .search-bar input::placeholder { color: #94a3b8; }

      /* Filter pills */
      .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
      .pill {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: #667085;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
      }
      .pill:hover { border-color: #2563eb; color: #2563eb; }
      .pill.active { background: #2563eb; color: #fff; border-color: #2563eb; }

      /* ── DOCUMENT GRID ── */
      .doc-section-title {
        font-size: 1rem; font-weight: 700; color: #1a2236;
        margin-bottom: 14px; margin-top: 4px;
      }
      .doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
      }
      .doc-card {
        background: #fff;
        border-radius: 14px;
        border: 1.5px solid #e2e8f0;
        padding: 20px 18px 16px;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        position: relative;
        overflow: hidden;
      }
      .doc-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(37,99,235,0.1);
        border-color: #2563eb;
      }
      .doc-card-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
        margin-bottom: 12px;
      }
      .icon--blue { background: #eff6ff; color: #2563eb; }
      .icon--green { background: #f0fdf4; color: #16a34a; }
      .icon--yellow { background: #fefce8; color: #ca8a04; }
      .icon--red { background: #fff1f2; color: #dc2626; }
      .icon--purple { background: #faf5ff; color: #7c3aed; }
      .icon--teal { background: #f0fdfa; color: #0d9488; }

      .doc-card-name { font-size: 0.88rem; font-weight: 700; color: #1a2236; margin-bottom: 4px; }
      .doc-card-desc { font-size: 0.76rem; color: #667085; line-height: 1.4; }
      .doc-card-fee {
        margin-top: 12px;
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.75rem; font-weight: 700;
        padding: 3px 10px; border-radius: 20px;
      }
      .fee--free { background: #f0fdf4; color: #16a34a; }
      .fee--paid { background: #eff6ff; color: #2563eb; }

      .doc-card-badge {
        position: absolute; top: 12px; right: 12px;
        font-size: 0.62rem; font-weight: 700;
        background: #f1f5f9; color: #64748b;
        padding: 2px 7px; border-radius: 20px;
        text-transform: uppercase; letter-spacing: 0.5px;
      }
      .badge--popular { background: #fef9c3; color: #92400e; }

      /* ── MODAL OVERLAY ── */
      .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(15,27,53,0.55);
        backdrop-filter: blur(4px);
        z-index: 100;
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        opacity: 0; pointer-events: none;
        transition: opacity 0.22s;
      }
      .modal-overlay.open { opacity: 1; pointer-events: all; }

      .modal {
        background: #fff;
        border-radius: 20px;
        width: 100%; max-width: 540px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 64px rgba(15,27,53,0.22);
        transform: translateY(16px) scale(0.98);
        transition: transform 0.22s;
      }
      .modal-overlay.open .modal { transform: translateY(0) scale(1); }

      .modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 22px 24px 0;
        position: sticky; top: 0; background: #fff; z-index: 2;
        border-radius: 20px 20px 0 0;
      }
      .modal-header-left { display: flex; align-items: center; gap: 12px; }
      .modal-doc-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
      }
      .modal-title { font-size: 1.05rem; font-weight: 700; color: #1a2236; }
      .modal-subtitle { font-size: 0.78rem; color: #667085; margin-top: 2px; }
      .modal-close {
        width: 34px; height: 34px; border-radius: 50%;
        border: none; background: #f1f5f9; color: #64748b;
        font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
      }
      .modal-close:hover { background: #e2e8f0; color: #1a2236; }

      /* Steps */
      .modal-steps {
        display: flex; align-items: center; gap: 0;
        padding: 18px 24px 0;
      }
      .step-dot {
        width: 28px; height: 28px; border-radius: 50%;
        border: 2px solid #e2e8f0; background: #fff;
        color: #94a3b8; font-size: 0.75rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all 0.2s;
      }
      .step-dot.active { border-color: #2563eb; background: #2563eb; color: #fff; }
      .step-dot.done { border-color: #16a34a; background: #16a34a; color: #fff; }
      .step-line { flex: 1; height: 2px; background: #e2e8f0; transition: background 0.2s; }
      .step-line.done { background: #16a34a; }

      .modal-body { padding: 20px 24px 0; }

      /* Form elements */
      .form-section { margin-bottom: 20px; }
      .form-label {
        display: block; font-size: 0.8rem; font-weight: 600;
        color: #374151; margin-bottom: 6px;
      }
      .form-label span { color: #ef4444; }
      .form-input, .form-select, .form-textarea {
        width: 100%; padding: 11px 14px;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem; color: #1a2236;
        outline: none; background: #fff;
        transition: border-color 0.18s, box-shadow 0.18s;
      }
      .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
      }
      .form-textarea { resize: vertical; min-height: 80px; }
      .form-hint { font-size: 0.73rem; color: #94a3b8; margin-top: 4px; }

      /* Choice cards (pickup/delivery / payment) */
      .choice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
      .choice-card {
        border: 1.5px solid #e2e8f0; border-radius: 12px;
        padding: 14px; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        text-align: center; transition: all 0.18s;
        background: #fff;
      }
      .choice-card:hover { border-color: #2563eb; background: #eff6ff; }
      .choice-card.selected { border-color: #2563eb; background: #eff6ff; }
      .choice-card.selected .choice-icon { color: #2563eb; }
      .choice-icon { font-size: 1.4rem; color: #94a3b8; transition: color 0.18s; }
      .choice-label { font-size: 0.82rem; font-weight: 700; color: #1a2236; }
      .choice-sub { font-size: 0.7rem; color: #667085; }

      /* Payment methods */
      .payment-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
      .payment-option {
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        padding: 12px 8px; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        transition: all 0.15s; background: #fff;
      }
      .payment-option:hover { border-color: #2563eb; background: #eff6ff; }
      .payment-option.selected { border-color: #2563eb; background: #eff6ff; }
      .payment-option i { font-size: 1.2rem; color: #94a3b8; }
      .payment-option.selected i { color: #2563eb; }
      .payment-option span { font-size: 0.72rem; font-weight: 600; color: #374151; text-align: center; }

      /* Fee summary */
      .fee-summary {
        background: #f8fafc; border-radius: 12px;
        padding: 14px 16px; margin-top: 8px;
      }
      .fee-row {
        display: flex; justify-content: space-between;
        font-size: 0.82rem; color: #374151;
        padding: 4px 0;
      }
      .fee-row.total {
        border-top: 1.5px solid #e2e8f0;
        margin-top: 6px; padding-top: 8px;
        font-weight: 700; color: #1a2236; font-size: 0.9rem;
      }
      .free-tag { color: #16a34a; font-weight: 700; }

      /* Alert / info box */
      .info-box {
        background: #eff6ff; border: 1px solid #bfdbfe;
        border-radius: 10px; padding: 10px 14px;
        font-size: 0.78rem; color: #1d4ed8;
        display: flex; gap: 8px; align-items: flex-start;
        margin-bottom: 16px;
      }
      .info-box i { margin-top: 2px; flex-shrink: 0; }

      /* Requirements list */
      .req-list { list-style: none; display: flex; flex-direction: column; gap: 6px; }
      .req-list li {
        display: flex; align-items: center; gap: 8px;
        font-size: 0.82rem; color: #374151;
        background: #f8fafc; border-radius: 8px;
        padding: 8px 12px;
      }
      .req-list li i { color: #94a3b8; font-size: 0.75rem; }

      /* Divider */
      .modal-divider { height: 1px; background: #f1f5f9; margin: 4px 0 16px; }

      /* Modal footer */
      .modal-footer {
        padding: 16px 24px 22px;
        display: flex; gap: 10px; justify-content: flex-end;
        position: sticky; bottom: 0; background: #fff;
        border-radius: 0 0 20px 20px;
      }
      .btn {
        padding: 10px 22px; border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem; font-weight: 700;
        cursor: pointer; border: none; transition: all 0.15s;
        display: flex; align-items: center; gap: 7px;
      }
      .btn-secondary {
        background: #f1f5f9; color: #374151;
      }
      .btn-secondary:hover { background: #e2e8f0; }
      .btn-primary {
        background: #2563eb; color: #fff;
        box-shadow: 0 2px 8px rgba(37,99,235,0.25);
      }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
      .btn-success {
        background: #16a34a; color: #fff;
        box-shadow: 0 2px 8px rgba(22,163,74,0.25);
      }
      .btn-success:hover { background: #15803d; transform: translateY(-1px); }

      /* Success state */
      .success-state {
        text-align: center; padding: 32px 24px 20px;
        display: none;
      }
      .success-state.visible { display: block; }
      .success-icon {
        width: 70px; height: 70px;
        background: #f0fdf4; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.8rem; color: #16a34a;
      }
      .success-state h3 { font-size: 1.15rem; font-weight: 700; color: #1a2236; margin-bottom: 6px; }
      .success-state p { font-size: 0.85rem; color: #667085; line-height: 1.5; }
      .ref-tag {
        display: inline-block; margin-top: 14px;
        background: #eff6ff; color: #2563eb;
        font-size: 0.78rem; font-weight: 700;
        padding: 6px 16px; border-radius: 20px;
        letter-spacing: 0.5px;
      }

      /* No results */
      .no-results {
        text-align: center; padding: 48px 24px;
        color: #94a3b8;
        display: none;
      }
      .no-results.visible { display: block; }
      .no-results i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
      .no-results p { font-size: 0.9rem; }

      /* Hidden class */
      .hidden { display: none !important; }
    </style>
  </head>

  <body>
    <div class="container">
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-brand">
          <h2>BarangayKonek</h2>
          <span>Resident</span>
        </div>

        <div class="profile profile--compact">
          <div class="avatar-ring">
            <i class="fa-solid fa-user avatar-placeholder"></i>
          </div>
          <div class="profile-meta">
            <h3>Corbin Gutierrez</h3>
            <p>City of Imus, Alapan 1-A</p>
            <span class="portal-badge">Resident Portal</span>
          </div>
        </div>

        <nav class="menu">
          <a href="dashboard.php">
            <i class="fa-solid fa-house nav-icon"></i>
            <span>Dashboard</span>
          </a>
          <a href="request.php">
            <i class="fa-solid fa-file-lines nav-icon"></i>
            <span>Request Documents</span>
          </a>
          <a href="residentconcern.html">
            <i class="fa-solid fa-circle-exclamation nav-icon"></i>
            <span>Concerns</span>
          </a>
          <a href="residentcommunity.html">
            <i class="fa-solid fa-users nav-icon"></i>
            <span>Community</span>
          </a>
          <a href="residentrequest.html" class="active">
            <i class="fa-solid fa-clipboard-list nav-icon"></i>
            <span>My Requests</span>
          </a>
        </nav>

        <div class="community-sidebar-section">
          <h4>Quick Access</h4>
        </div>

        <a href="home.html" class="logout">
          <i class="fa-solid fa-right-from-bracket nav-icon"></i>
          <span>Logout</span>
        </a>
      </aside>

      <!-- MAIN -->
      <main class="content">
        <div class="content-inner">

          <!-- Topbar -->
          <div class="topbar">
            <div class="greeting-block">
              <h1>Request <span class="accent-name">Documents</span></h1>
              <p class="subtitle">Search and request official barangay documents easily.</p>
            </div>
            <div class="topbar-right">
              <div class="user-chip">
                <div class="user-chip-avatar-wrap">
                  <i class="fa-solid fa-user" style="color:#b4ff39;font-size:1rem;"></i>
                </div>
                <div class="user-chip-info">
                  <span class="user-chip-name">Corbin Gutierrez</span>
                  <span class="user-chip-role">Resident</span>
                </div>
                <a href="notifications.html" class="user-chip-bell-wrap">
                  <i class="fa-solid fa-bell user-chip-bell"></i>
                  <span class="user-chip-notif">2</span>
                </a>
              </div>
            </div>
          </div>

          <!-- Search -->
          <div class="search-section">
            <p class="search-label">Find a document</p>
            <div class="search-bar">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="e.g. Barangay Clearance, Indigency, Certificate of Residency…" />
            </div>
            <div class="filter-pills">
              <div class="pill active" data-filter="all">All</div>
              <div class="pill" data-filter="free">Free</div>
              <div class="pill" data-filter="paid">Paid</div>
              <div class="pill" data-filter="certificate">Certificate</div>
              <div class="pill" data-filter="clearance">Clearance</div>
              <div class="pill" data-filter="permit">Permit</div>
            </div>
          </div>

          <!-- Document Grid -->
          <p class="doc-section-title" id="docSectionTitle">Available Documents</p>
          <div class="doc-grid" id="docGrid"></div>
          <div class="no-results" id="noResults">
            <i class="fa-solid fa-file-circle-question"></i>
            <p>No documents found for "<span id="noResultsQuery"></span>"</p>
          </div>

        </div>
      </main>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modalOverlay">
      <div class="modal" id="modal">

        <!-- Success screen -->
        <div class="success-state" id="successState">
          <div class="success-icon"><i class="fa-solid fa-check"></i></div>
          <h3>Request Submitted!</h3>
          <p>Your document request has been received. You will be notified once it is ready.</p>
          <div class="ref-tag" id="refTag"></div>
          <div style="margin-top:20px;padding:0 0 4px;">
            <button class="btn btn-primary" onclick="closeModal()" style="width:100%;justify-content:center;">
              <i class="fa-solid fa-house"></i> Back to Dashboard
            </button>
          </div>
        </div>

        <!-- Form screen -->
        <div id="formScreen">
          <div class="modal-header">
            <div class="modal-header-left">
              <div class="modal-doc-icon" id="modalDocIcon"></div>
              <div>
                <div class="modal-title" id="modalTitle"></div>
                <div class="modal-subtitle" id="modalSubtitle"></div>
              </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <!-- Steps -->
          <div class="modal-steps">
            <div class="step-dot active" id="step1dot">1</div>
            <div class="step-line" id="line12"></div>
            <div class="step-dot" id="step2dot">2</div>
            <div class="step-line" id="line23"></div>
            <div class="step-dot" id="step3dot">3</div>
          </div>

          <div class="modal-body">

            <!-- STEP 1: Requirements -->
            <div id="step1">
              <div style="margin-bottom:14px;margin-top:6px;">
                <p style="font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:10px;">
                  <i class="fa-solid fa-list-check" style="color:#2563eb;margin-right:6px;"></i>Requirements Needed
                </p>
                <ul class="req-list" id="reqList"></ul>
              </div>
              <div class="modal-divider"></div>
              <div class="form-section">
                <label class="form-label">Purpose of Request <span>*</span></label>
                <select class="form-select" id="purposeSelect">
                  <option value="">Select purpose…</option>
                  <option>Employment</option>
                  <option>Scholarship Application</option>
                  <option>Bank / Financial Transactions</option>
                  <option>Government Transactions</option>
                  <option>Travel / Visa Application</option>
                  <option>School Enrollment</option>
                  <option>Legal Purposes</option>
                  <option>Others</option>
                </select>
              </div>
              <div class="form-section">
                <label class="form-label">Additional Notes</label>
                <textarea class="form-textarea" id="notesInput" placeholder="Any special instructions or additional information…"></textarea>
              </div>
            </div>

            <!-- STEP 2: Delivery -->
            <div id="step2" class="hidden">
              <div class="form-section" style="margin-top:6px;">
                <label class="form-label" style="margin-bottom:10px;">How would you like to receive your document? <span>*</span></label>
                <div class="choice-grid">
                  <div class="choice-card" data-delivery="pickup" onclick="selectDelivery('pickup')">
                    <div class="choice-icon"><i class="fa-solid fa-store"></i></div>
                    <div class="choice-label">Pick Up</div>
                    <div class="choice-sub">at Barangay Hall</div>
                  </div>
                  <div class="choice-card" data-delivery="delivery" onclick="selectDelivery('delivery')">
                    <div class="choice-icon"><i class="fa-solid fa-motorcycle"></i></div>
                    <div class="choice-label">Home Delivery</div>
                    <div class="choice-sub">₱50 delivery fee</div>
                  </div>
                </div>
              </div>
              <div id="deliveryAddressSection" class="hidden">
                <div class="form-section">
                  <label class="form-label">Delivery Address <span>*</span></label>
                  <input type="text" class="form-input" id="deliveryAddress" placeholder="House No., Street, Subdivision…" />
                  <p class="form-hint">Please provide your complete address for accurate delivery.</p>
                </div>
                <div class="form-section">
                  <label class="form-label">Contact Number <span>*</span></label>
                  <input type="text" class="form-input" id="contactNumber" placeholder="09XX XXX XXXX" />
                </div>
              </div>
              <div id="pickupInfoBox" class="info-box hidden">
                <i class="fa-solid fa-circle-info"></i>
                <span>You may pick up your document at the <strong>Barangay Hall</strong> during office hours: <strong>Monday – Friday, 8:00 AM – 5:00 PM</strong>. Please bring a valid ID.</span>
              </div>
            </div>

            <!-- STEP 3: Payment -->
            <div id="step3" class="hidden">
              <div class="modal-divider" style="margin-top:6px;"></div>
              <div id="freeDocSection" class="hidden">
                <div class="info-box" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;">
                  <i class="fa-solid fa-circle-check"></i>
                  <span>This document is <strong>free of charge</strong>. No payment is required.</span>
                </div>
              </div>
              <div id="paidDocSection" class="hidden">
                <div class="form-section">
                  <label class="form-label" style="margin-bottom:10px;">Select Payment Method <span>*</span></label>
                  <div class="payment-grid">
                    <div class="payment-option" data-pay="cash" onclick="selectPayment('cash')">
                      <i class="fa-solid fa-money-bills"></i>
                      <span>Cash on Pick Up</span>
                    </div>
                    <div class="payment-option" data-pay="gcash" onclick="selectPayment('gcash')">
                      <i class="fa-solid fa-mobile-screen-button"></i>
                      <span>GCash</span>
                    </div>
                    <div class="payment-option" data-pay="paymaya" onclick="selectPayment('paymaya')">
                      <i class="fa-solid fa-credit-card"></i>
                      <span>Maya</span>
                    </div>
                  </div>
                  <div id="cashDeliveryNote" class="form-hint hidden" style="margin-top:8px;color:#dc2626;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Cash payment for deliveries is collected upon receipt.
                  </div>
                </div>
              </div>
              <div class="fee-summary">
                <p style="font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:8px;">Order Summary</p>
                <div class="fee-row">
                  <span id="summaryDocName">Document</span>
                  <span id="summaryDocFee">₱0</span>
                </div>
                <div class="fee-row" id="deliveryFeeRow" style="display:none;">
                  <span>Delivery Fee</span>
                  <span>₱50</span>
                </div>
                <div class="fee-row total">
                  <span>Total</span>
                  <span id="summaryTotal">₱0</span>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" id="backBtn" onclick="prevStep()" style="display:none;">
              <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <button class="btn btn-primary" id="nextBtn" onclick="nextStep()">
              Next <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

      </div>
    </div>

    <script>
      /* ── DATA ── */
      const documents = [
        {
          id: 1, name: "Barangay Clearance",
          desc: "General purpose clearance for residents in good standing.",
          icon: "fa-solid fa-stamp", iconClass: "icon--blue",
          fee: 50, feeLabel: "₱50", category: ["clearance"],
          popular: true,
          requirements: ["Valid Government ID", "Proof of Residency", "Accomplished Request Form"],
        },
        {
          id: 2, name: "Certificate of Indigency",
          desc: "For residents needing assistance or applying for benefits.",
          icon: "fa-solid fa-hand-holding-heart", iconClass: "icon--green",
          fee: 0, feeLabel: "Free", category: ["certificate", "free"],
          requirements: ["Valid ID", "Proof of Residency (Utility Bill)"],
        },
        {
          id: 3, name: "Certificate of Residency",
          desc: "Confirms that an individual is a resident of the barangay.",
          icon: "fa-solid fa-house-circle-check", iconClass: "icon--teal",
          fee: 30, feeLabel: "₱30", category: ["certificate"],
          requirements: ["Valid Government ID", "Proof of Address (Electric/Water Bill)"],
        },
        {
          id: 4, name: "Business Permit Clearance",
          desc: "Required for businesses operating within the barangay.",
          icon: "fa-solid fa-briefcase", iconClass: "icon--yellow",
          fee: 200, feeLabel: "₱200", category: ["permit"],
          requirements: ["DTI or SEC Registration", "Valid ID of Owner", "Sketch of Business Location"],
        },
        {
          id: 5, name: "Barangay ID",
          desc: "Official barangay identification card for residents.",
          icon: "fa-solid fa-id-card", iconClass: "icon--purple",
          fee: 100, feeLabel: "₱100", category: [],
          popular: true,
          requirements: ["1x1 ID Picture (white background)", "Proof of Residency", "Valid ID"],
        },
        {
          id: 6, name: "Certificate of Good Moral",
          desc: "Attests to the good moral character of the resident.",
          icon: "fa-solid fa-award", iconClass: "icon--blue",
          fee: 30, feeLabel: "₱30", category: ["certificate"],
          requirements: ["Valid Government ID", "Proof of Address"],
        },
        {
          id: 7, name: "Solo Parent Certificate",
          desc: "For solo parents availing of government benefits and assistance.",
          icon: "fa-solid fa-person-breastfeeding", iconClass: "icon--red",
          fee: 0, feeLabel: "Free", category: ["certificate", "free"],
          requirements: ["Birth Certificate of Child/Children", "Valid ID", "DSWD Solo Parent Card (if existing)"],
        },
        {
          id: 8, name: "Death Certificate Request",
          desc: "Barangay certification related to a deceased resident.",
          icon: "fa-solid fa-file-circle-xmark", iconClass: "icon--red",
          fee: 0, feeLabel: "Free", category: ["certificate", "free"],
          requirements: ["PSA Death Certificate", "Valid ID of Requester", "Proof of Relation"],
        },
        {
          id: 9, name: "Fencing Permit",
          desc: "Required before constructing or renovating a fence.",
          icon: "fa-solid fa-fence", iconClass: "icon--yellow",
          fee: 150, feeLabel: "₱150", category: ["permit"],
          requirements: ["Lot Title or Tax Declaration", "Property Sketch / Plan", "Valid ID of Owner"],
        },
        {
          id: 10, name: "Barangay Blotter Request",
          desc: "Official record for incidents or complaints filed in the barangay.",
          icon: "fa-solid fa-book-open", iconClass: "icon--teal",
          fee: 0, feeLabel: "Free", category: ["clearance", "free"],
          requirements: ["Valid Government ID", "Incident Details / Written Statement"],
        },
      ];

      /* ── STATE ── */
      let currentDoc = null;
      let currentStep = 1;
      let selectedDelivery = null;
      let selectedPayment = null;
      let activeFilter = 'all';

      /* ── RENDER GRID ── */
      function renderGrid(docs) {
        const grid = document.getElementById('docGrid');
        const noResults = document.getElementById('noResults');
        grid.innerHTML = '';
        if (docs.length === 0) {
          noResults.classList.add('visible');
          return;
        }
        noResults.classList.remove('visible');
        docs.forEach(doc => {
          const isFree = doc.fee === 0;
          const card = document.createElement('div');
          card.className = 'doc-card';
          card.innerHTML = `
            ${doc.popular ? '<div class="doc-card-badge badge--popular">⭐ Popular</div>' : ''}
            <div class="doc-card-icon ${doc.iconClass}"><i class="${doc.icon}"></i></div>
            <div class="doc-card-name">${doc.name}</div>
            <div class="doc-card-desc">${doc.desc}</div>
            <div class="doc-card-fee ${isFree ? 'fee--free' : 'fee--paid'}">
              <i class="fa-solid ${isFree ? 'fa-circle-check' : 'fa-peso-sign'}"></i>
              ${doc.feeLabel}
            </div>
          `;
          card.addEventListener('click', () => openModal(doc));
          grid.appendChild(card);
        });
      }

      function filterDocs() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();
        let filtered = documents;
        if (activeFilter !== 'all') {
          if (activeFilter === 'free') filtered = filtered.filter(d => d.fee === 0);
          else if (activeFilter === 'paid') filtered = filtered.filter(d => d.fee > 0);
          else filtered = filtered.filter(d => d.category.includes(activeFilter));
        }
        if (query) {
          filtered = filtered.filter(d =>
            d.name.toLowerCase().includes(query) || d.desc.toLowerCase().includes(query)
          );
          document.getElementById('noResultsQuery').textContent = query;
        }
        const title = query
          ? `Results for "${query}" (${filtered.length})`
          : `Available Documents (${filtered.length})`;
        document.getElementById('docSectionTitle').textContent = title;
        renderGrid(filtered);
      }

      document.getElementById('searchInput').addEventListener('input', filterDocs);
      document.querySelectorAll('.pill').forEach(pill => {
        pill.addEventListener('click', () => {
          document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          activeFilter = pill.dataset.filter;
          filterDocs();
        });
      });

      /* ── MODAL ── */
      function openModal(doc) {
        currentDoc = doc;
        currentStep = 1;
        selectedDelivery = null;
        selectedPayment = null;

        // Header
        document.getElementById('modalTitle').textContent = doc.name;
        document.getElementById('modalSubtitle').textContent = doc.desc;
        const iconEl = document.getElementById('modalDocIcon');
        iconEl.className = `modal-doc-icon ${doc.iconClass}`;
        iconEl.innerHTML = `<i class="${doc.icon}"></i>`;

        // Requirements
        const reqList = document.getElementById('reqList');
        reqList.innerHTML = doc.requirements.map(r =>
          `<li><i class="fa-solid fa-circle-dot"></i>${r}</li>`
        ).join('');

        // Reset form
        document.getElementById('purposeSelect').value = '';
        document.getElementById('notesInput').value = '';
        document.getElementById('deliveryAddress').value = '';
        document.getElementById('contactNumber').value = '';
        document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('selected'));
        document.getElementById('deliveryAddressSection').classList.add('hidden');
        document.getElementById('pickupInfoBox').classList.add('hidden');

        // Summary
        document.getElementById('summaryDocName').textContent = doc.name;
        document.getElementById('summaryDocFee').textContent = doc.fee === 0 ? 'Free' : `₱${doc.fee}`;

        // Show/hide sections
        document.getElementById('successState').classList.remove('visible');
        document.getElementById('formScreen').style.display = '';

        showStep(1);
        document.getElementById('modalOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function closeModal() {
        document.getElementById('modalOverlay').classList.remove('open');
        document.body.style.overflow = '';
      }

      document.getElementById('modalOverlay').addEventListener('click', e => {
        if (e.target === document.getElementById('modalOverlay')) closeModal();
      });

      function showStep(n) {
        currentStep = n;
        [1,2,3].forEach(i => {
          document.getElementById(`step${i}`).classList.toggle('hidden', i !== n);
          const dot = document.getElementById(`step${i}dot`);
          dot.classList.remove('active','done');
          if (i < n) dot.classList.add('done'), dot.innerHTML = '<i class="fa-solid fa-check" style="font-size:0.65rem"></i>';
          else if (i === n) dot.classList.add('active'), dot.textContent = i;
          else dot.textContent = i;
        });
        document.getElementById('line12').classList.toggle('done', n > 1);
        document.getElementById('line23').classList.toggle('done', n > 2);
        document.getElementById('backBtn').style.display = n > 1 ? '' : 'none';

        const nextBtn = document.getElementById('nextBtn');
        if (n === 3) {
          nextBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
          nextBtn.className = 'btn btn-success';
        } else {
          nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right"></i>';
          nextBtn.className = 'btn btn-primary';
        }

        // Step 3 setup
        if (n === 3) {
          const isFree = currentDoc.fee === 0;
          document.getElementById('freeDocSection').classList.toggle('hidden', !isFree);
          document.getElementById('paidDocSection').classList.toggle('hidden', isFree);
          const deliveryFee = selectedDelivery === 'delivery' ? 50 : 0;
          document.getElementById('deliveryFeeRow').style.display = deliveryFee ? '' : 'none';
          const docFee = currentDoc.fee;
          const total = docFee + deliveryFee;
          document.getElementById('summaryTotal').textContent = total === 0 ? 'Free' : `₱${total}`;
          // Show/hide cash note
          const cashNote = document.getElementById('cashDeliveryNote');
          cashNote.classList.add('hidden');
        }
      }

      function nextStep() {
        if (currentStep === 1) {
          if (!document.getElementById('purposeSelect').value) {
            document.getElementById('purposeSelect').style.borderColor = '#ef4444';
            document.getElementById('purposeSelect').focus();
            setTimeout(() => document.getElementById('purposeSelect').style.borderColor = '', 1500);
            return;
          }
          showStep(2);
        } else if (currentStep === 2) {
          if (!selectedDelivery) {
            alert('Please select how you would like to receive your document.');
            return;
          }
          if (selectedDelivery === 'delivery') {
            if (!document.getElementById('deliveryAddress').value.trim()) {
              document.getElementById('deliveryAddress').focus();
              return;
            }
            if (!document.getElementById('contactNumber').value.trim()) {
              document.getElementById('contactNumber').focus();
              return;
            }
          }
          showStep(3);
        } else if (currentStep === 3) {
          const isFree = currentDoc.fee === 0;
          const deliveryFee = selectedDelivery === 'delivery' ? 50 : 0;
          const needsPayment = (currentDoc.fee > 0 || deliveryFee > 0);
          if (needsPayment && !selectedPayment) {
            alert('Please select a payment method.');
            return;
          }
          submitRequest();
        }
      }

      function prevStep() {
        if (currentStep > 1) showStep(currentStep - 1);
      }

      function selectDelivery(type) {
        selectedDelivery = type;
        document.querySelectorAll('.choice-card').forEach(c => {
          c.classList.toggle('selected', c.dataset.delivery === type);
        });
        document.getElementById('deliveryAddressSection').classList.toggle('hidden', type !== 'delivery');
        document.getElementById('pickupInfoBox').classList.toggle('hidden', type !== 'pickup');
      }

      function selectPayment(type) {
        selectedPayment = type;
        document.querySelectorAll('.payment-option').forEach(p => {
          p.classList.toggle('selected', p.dataset.pay === type);
        });
        // Show note if cash + delivery
        const cashNote = document.getElementById('cashDeliveryNote');
        cashNote.classList.toggle('hidden', !(type === 'cash' && selectedDelivery === 'delivery'));
      }

      function submitRequest() {
        const ref = 'BRY-' + Date.now().toString().slice(-6);
        document.getElementById('refTag').textContent = `Reference No: ${ref}`;
        document.getElementById('formScreen').style.display = 'none';
        document.getElementById('successState').classList.add('visible');
      }

      /* ── INIT ── */
      renderGrid(documents);
    </script>
  </body>
</html>