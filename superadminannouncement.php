<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php"); exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];
$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']).' '.rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $now    = date('Y-m-d H:i:s');

    if ($action === 'create') {
        $title    = trim($_POST['title']    ?? '');
        $body     = trim($_POST['body']     ?? '');
        $category = trim($_POST['category'] ?? '');
        $duration = (int)($_POST['duration'] ?? 7);
        $isActive = 1;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$duration days"));

        if ($title && $body) {
            $q = sqlsrv_query($conn,
                "INSERT INTO ANNOUNCEMENTS (TITLE, BODY, CATEGORY, IS_ACTIVE, CREATED_BY, CREATED_AT, EXPIRES_AT)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$title, $body, $category, $isActive, $userId, $now, $expiresAt]);
            if ($q === false) {
                $errs = sqlsrv_errors();
                $message = 'DB Error: ' . ($errs[0]['message'] ?? 'Unknown'); $messageType = 'error';
            } else {
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Create Announcement', ?, ?)",
                    [$userId, "Created announcement: $title", $now]);
                $safeTitle = str_replace('"', "'", $title);
                header("Location: superadminannouncement.php?success=" . rawurlencode("Announcement posted: $safeTitle")); exit();
            }
        } else {
            $message = 'Title and body are required.'; $messageType = 'error';
        }
    }

    if ($action === 'edit' && isset($_POST['ann_id'])) {
        $annId    = (int)$_POST['ann_id'];
        $newTitle = trim($_POST['title'] ?? '');
        $newBody  = trim($_POST['body']  ?? '');
        if ($newTitle && $newBody) {
            $eq = sqlsrv_query($conn,
                "UPDATE ANNOUNCEMENTS SET TITLE=?, BODY=? WHERE ANNOUNCEMENT_ID=?",
                [$newTitle, $newBody, $annId]);
            if ($eq === false) {
                $errs = sqlsrv_errors();
                $message = 'Edit error: ' . ($errs[0]['message'] ?? 'Unknown'); $messageType = 'error';
            } else {
                sqlsrv_query($conn,
                    "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Edit Announcement', ?, ?)",
                    [$userId, "Edited announcement: $newTitle", $now]);
                header("Location: superadminannouncement.php?success=" . rawurlencode("Announcement updated successfully.")); exit();
            }
        }
    }

    if ($action === 'toggle' && isset($_POST['ann_id'])) {
        $annId    = (int)$_POST['ann_id'];
        $newActive = (int)($_POST['new_active'] ?? 1);
        sqlsrv_query($conn,
            "UPDATE ANNOUNCEMENTS SET IS_ACTIVE=? WHERE ANNOUNCEMENT_ID=?", [$newActive, $annId]);
        $label = $newActive ? 'Published' : 'Unpublished';
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Toggle Announcement', ?, ?)",
            [$userId, "$label announcement ID $annId", $now]);
        header("Location: superadminannouncement.php?success=" . rawurlencode("Announcement $label.")); exit();
    }

    if ($action === 'delete' && isset($_POST['ann_id'])) {
        $annId = (int)$_POST['ann_id'];
        sqlsrv_query($conn, "DELETE FROM ANNOUNCEMENTS WHERE ANNOUNCEMENT_ID=?", [$annId]);
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'Delete Announcement', ?, ?)",
            [$userId, "Deleted announcement ID $annId", $now]);
        header("Location: superadminannouncement.php?success=" . rawurlencode("Announcement deleted.")); exit();
    }
}

$successModal = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
if (isset($_GET['msg'])) { $message = htmlspecialchars($_GET['msg']); $messageType = 'success'; }

$filterActive = trim($_GET['filter'] ?? '');
$search       = trim($_GET['search'] ?? '');

$params = [];
$sql = "SELECT ANNOUNCEMENT_ID, TITLE, BODY, CATEGORY, IS_ACTIVE, CREATED_BY, CREATED_AT, EXPIRES_AT FROM ANNOUNCEMENTS WHERE 1=1";
if ($filterActive !== '') { $sql .= " AND IS_ACTIVE = ?"; $params[] = (int)$filterActive; }
if ($search) { $sql .= " AND TITLE LIKE ?"; $params[] = '%'.$search.'%'; }
$sql .= " ORDER BY CREATED_AT DESC";

$result = sqlsrv_query($conn, $sql, $params ?: []);
$announcements = [];
if ($result) { while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) { $announcements[] = $row; } }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Announcements — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <style>
    .ann-wrap{display:flex;flex-direction:column;gap:20px}
    .ann-page-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .ann-page-head h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 4px}
    .ann-page-head p{font-size:13px;color:var(--text-muted);margin:0}

    .ann-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
    .ann-toolbar{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02);flex-wrap:wrap}
    .ann-search{flex:1;min-width:220px;height:40px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .ann-search i{color:var(--text-muted);font-size:13px}
    .ann-search input{flex:1;border:none;outline:none;background:transparent;font-family:inherit;font-size:13px;color:var(--text)}
    .ann-filter{height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;min-width:140px}
    .ann-table-wrap{width:100%;overflow-x:auto}
    .ann-body-preview{font-size:12px;color:var(--text-muted);max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    /* CREATE FORM */
    .create-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
    .create-panel-head{padding:14px 20px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02)}
    .create-panel-head h4{font-size:14px;font-weight:700;color:var(--navy);margin:0}
    .create-panel-body{padding:20px;display:flex;flex-direction:column;gap:14px}
    .form-field{display:flex;flex-direction:column;gap:6px}
    .form-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px}
    .form-input{height:42px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;transition:border-color .2s;width:100%}
    .form-input:focus{border-color:var(--navy)}
    .form-textarea{padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;resize:vertical;min-height:110px;width:100%;transition:border-color .2s}
    .form-textarea:focus{border-color:var(--navy)}
    .form-select{height:42px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;width:100%}
    .form-row{display:grid;grid-template-columns:1fr 160px;gap:12px}
    .create-foot{display:flex;justify-content:flex-end}

    /* MODALS */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,22,80,.5);z-index:400;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.open{display:flex}
    .modal-box{background:#fff;border-radius:14px;padding:32px 28px;max-width:420px;width:90%;text-align:center;box-shadow:0 12px 48px rgba(5,22,80,.22)}
    .modal-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .modal-box h3{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .modal-box p{font-size:13px;color:var(--text-muted);margin-bottom:22px;line-height:1.6}
    .modal-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
    .modal-btn-yes{background:var(--navy);color:#fff;border:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .modal-btn-no{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .modal-btn-ok{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}

    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .msg-banner{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px}
    .msg-success{background:rgba(34,197,94,.12);color:#16a34a;border:1px solid rgba(34,197,94,.25)}
    .msg-error{background:rgba(255,77,77,.1);color:var(--red);border:1px solid rgba(255,77,77,.25)}
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-btns">
      <button class="btn-cancel-lo" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm-lo"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="modal-overlay" id="successModal">
  <div class="modal-box" style="border-top:4px solid var(--green);">
    <div class="modal-icon" style="background:rgba(34,197,94,.12);color:var(--green);"><i class="fa-solid fa-circle-check"></i></div>
    <h3>Done!</h3>
    <p id="successMsg"></p>
    <div class="modal-btns"><button class="modal-btn-ok" onclick="closeSuccessModal()">OK</button></div>
  </div>
</div>

<!-- VIEW / EDIT / DELETE MODAL -->
<div class="modal-overlay" id="viewModal">
  <div class="modal-box" style="max-width:520px;text-align:left;padding:0;border-radius:14px;overflow:hidden;">
    <div style="padding:16px 22px;border-bottom:1px solid var(--border);background:var(--navy);display:flex;align-items:center;justify-content:space-between;">
      <h3 style="font-size:15px;font-weight:700;color:#fff;margin:0;"><i class="fa-solid fa-bullhorn" style="margin-right:8px;opacity:.7;"></i>Announcement</h3>
      <button onclick="closeViewModal()" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:30px;height:30px;border-radius:7px;cursor:pointer;font-size:14px;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="viewModeBody" style="padding:22px;">
      <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Title</div>
      <div id="viewTitle" style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;"></div>
      <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Message</div>
      <div id="viewBody" style="font-size:13px;color:var(--text);line-height:1.7;white-space:pre-wrap;max-height:220px;overflow-y:auto;background:rgba(5,22,80,.03);padding:14px;border-radius:8px;border:1px solid var(--border);"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
        <button onclick="switchToEdit()" class="modal-btn-no"><i class="fa-solid fa-pen" style="margin-right:6px;"></i>Edit</button>
        <button onclick="confirmDelete()" class="modal-btn-yes" style="background:#ef4444;"><i class="fa-solid fa-trash" style="margin-right:6px;"></i>Delete</button>
      </div>
    </div>
    <div id="editModeBody" style="padding:22px;display:none;">
      <form method="POST" id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="ann_id" id="editAnnId">
        <div style="margin-bottom:14px;">
          <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px;">Title</label>
          <input type="text" name="title" id="editTitle" class="form-input" required>
        </div>
        <div style="margin-bottom:18px;">
          <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px;">Message</label>
          <textarea name="body" id="editBody" class="form-textarea" required></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" onclick="switchToView()" class="modal-btn-no">Cancel</button>
          <button type="submit" class="modal-btn-yes"><i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Save Changes</button>
        </div>
      </form>
    </div>
    <!-- Delete confirm strip (hidden by default) -->
    <div id="deleteConfirmBody" style="padding:22px;display:none;text-align:center;">
      <div class="modal-icon" style="background:rgba(239,68,68,.1);color:#b91c1c;margin-bottom:14px;"><i class="fa-solid fa-trash"></i></div>
      <p style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:6px;">Delete this announcement?</p>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">This cannot be undone.</p>
      <div style="display:flex;gap:10px;justify-content:center;">
        <button onclick="switchToView()" class="modal-btn-no">Cancel</button>
        <button onclick="submitDelete()" class="modal-btn-yes" style="background:#ef4444;">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="ann_id" id="deleteAnnId">
</form>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand"><h1>Barangay Alapan 1-A</h1><p>Super Admin</p></div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php" class="active">Announcements</a>
      <a href="superadminreports.php">Reports</a>
      <a href="superadminauditlogs.php">Audit Logs</a>
    </nav>
    <div class="superadmin-header-right">
      <div class="superadmin-user"><div class="superadmin-user-info">
        <span class="superadmin-user-name"><?= $displayName ?></span>
      </div></div>
      <a href="#" class="superadmin-logout" onclick="openLogout();return false;">Logout</a>
    </div>
  </header>

  <main class="superadmin-content">
    <?php if ($message): ?>
    <div class="msg-banner msg-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="ann-wrap">
      <div class="ann-page-head">
        <div>
          <h2>Announcements</h2>
          <p>Post and manage community announcements visible to all residents.</p>
        </div>
      </div>

      <!-- CREATE FORM -->
      <div class="create-panel">
        <div class="create-panel-head">
          <h4><i class="fa-solid fa-plus" style="margin-right:7px;"></i>Post New Announcement</h4>
        </div>
        <form method="POST" class="create-panel-body">
          <input type="hidden" name="action" value="create">
          <div class="form-row">
            <div class="form-field">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-input" placeholder="Announcement title..." required>
            </div>
            <div class="form-field">
              <label class="form-label">Category <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
              <input type="text" name="category" class="form-input" placeholder="e.g. Health, Events, Safety...">
            </div>
          </div>
          <div class="form-field">
            <label class="form-label">Message</label>
            <textarea name="body" class="form-textarea" placeholder="Write the announcement content here..." required></textarea>
          </div>
          <div class="form-row" style="grid-template-columns:1fr 200px;">
            <div class="form-field" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:10px;">
              <label class="form-label" style="margin:0;white-space:nowrap;">Expires after</label>
              <select name="duration" class="form-select" style="max-width:200px;">
                <option value="3">3 days</option>
                <option value="7" selected>1 week</option>
                <option value="14">2 weeks</option>
                <option value="30">1 month</option>
                <option value="90">3 months</option>
                <option value="365">1 year</option>
              </select>
            </div>
            <div class="create-foot" style="justify-content:flex-end;">
              <button type="submit" class="superadmin-primary-btn">
                <i class="fa-solid fa-paper-plane"></i> Post Announcement
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- LIST -->
      <div class="ann-panel">
        <form method="GET">
          <div class="ann-toolbar">
            <div class="ann-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" name="search" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="ann-filter" name="filter" onchange="this.form.submit()">
              <option value="">All</option>
              <option value="1" <?= $filterActive==='1'?'selected':'' ?>>Active</option>
              <option value="0" <?= $filterActive==='0'?'selected':'' ?>>Inactive</option>
            </select>
            <button type="submit" class="superadmin-primary-btn" style="min-height:40px;padding:0 14px;">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </div>
        </form>

        <div class="ann-table-wrap">
          <table class="superadmin-table">
            <thead>
              <tr><th>Title</th><th>Category</th><th>Preview</th><th>Status</th><th>Expires</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if (empty($announcements)): ?>
              <tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);">No announcements found.</td></tr>
              <?php else: foreach ($announcements as $ann):
                $annId       = (int)($ann['ANNOUNCEMENT_ID'] ?? 0);
                $annTitle    = htmlspecialchars(rtrim($ann['TITLE'] ?? ''));
                $annBodyRaw  = rtrim($ann['BODY'] ?? '');
                $annBody     = htmlspecialchars($annBodyRaw);
                $annCat      = htmlspecialchars(rtrim($ann['CATEGORY'] ?? ''));
                $annActive   = (int)($ann['IS_ACTIVE'] ?? 1);
                $annDate     = $ann['CREATED_AT'] instanceof DateTime
                    ? $ann['CREATED_AT']->format('M d, Y') : date('M d, Y', strtotime($ann['CREATED_AT'] ?? 'now'));
                $annExpires  = '';
                if ($ann['EXPIRES_AT'] instanceof DateTime) {
                    $annExpires = $ann['EXPIRES_AT']->format('M d, Y');
                } elseif (!empty($ann['EXPIRES_AT'])) {
                    $annExpires = date('M d, Y', strtotime($ann['EXPIRES_AT']));
                }
              ?>
              <tr>
                <td style="font-weight:700;color:var(--navy);max-width:180px;"><?= $annTitle ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= $annCat ?: '—' ?></td>
                <td><span class="ann-body-preview"><?= $annBody ?></span></td>
                <td>
                  <span class="table-status <?= $annActive ? 'active' : 'inactive' ?>">
                    <?= $annActive ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= $annExpires ?: '—' ?></td>
                <td>
                  <button type="button"
                          class="superadmin-primary-btn"
                          style="min-height:34px;padding:0 14px;font-size:13px;"
                          data-ann-id="<?= $annId ?>"
                          data-ann-title="<?= htmlspecialchars(rtrim($ann['TITLE'] ?? ''), ENT_QUOTES) ?>"
                          data-ann-body="<?= htmlspecialchars(rtrim($ann['BODY'] ?? ''), ENT_QUOTES) ?>"
                          data-ann-active="<?= $annActive ?>"
                          onclick="openViewModalFromBtn(this)">
                    <i class="fa-solid fa-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogout();});

function closeSuccessModal() { document.getElementById('successModal').classList.remove('open'); }
document.getElementById('successModal').addEventListener('click',function(e){if(e.target===this)closeSuccessModal();});

var currentAnnId = 0;
var currentAnnActive = 1;

function openViewModalFromBtn(btn) {
  var id       = parseInt(btn.getAttribute('data-ann-id'), 10);
  var title    = btn.getAttribute('data-ann-title');
  var body     = btn.getAttribute('data-ann-body');
  var isActive = parseInt(btn.getAttribute('data-ann-active'), 10);
  openViewModal(id, title, body, isActive);
}

function openViewModal(id, title, body, isActive) {
  currentAnnId     = id;
  currentAnnActive = isActive;
  document.getElementById('viewTitle').textContent = title;
  document.getElementById('viewBody').textContent  = body;
  document.getElementById('editAnnId').value = id;
  document.getElementById('editTitle').value = title;
  document.getElementById('editBody').value  = body;
  switchToView();
  document.getElementById('viewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeViewModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}
function switchToView() {
  document.getElementById('viewModeBody').style.display      = '';
  document.getElementById('editModeBody').style.display      = 'none';
  document.getElementById('deleteConfirmBody').style.display = 'none';
}
function switchToEdit() {
  document.getElementById('viewModeBody').style.display      = 'none';
  document.getElementById('editModeBody').style.display      = '';
  document.getElementById('deleteConfirmBody').style.display = 'none';
}
function confirmDelete() {
  document.getElementById('viewModeBody').style.display      = 'none';
  document.getElementById('editModeBody').style.display      = 'none';
  document.getElementById('deleteConfirmBody').style.display = '';
}
function submitDelete() {
  document.getElementById('deleteAnnId').value = currentAnnId;
  closeViewModal();
  document.getElementById('deleteForm').submit();
}
document.getElementById('viewModal').addEventListener('click',function(e){if(e.target===this)closeViewModal();});

<?php if ($successModal): ?>
window.addEventListener('DOMContentLoaded', function() {
  document.getElementById('successMsg').textContent = <?= json_encode($successModal) ?>;
  document.getElementById('successModal').classList.add('open');
});
<?php endif; ?>
</script>
</body>
</html>