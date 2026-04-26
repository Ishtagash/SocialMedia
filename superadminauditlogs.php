<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$nameRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$displayName = $nameRow
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

$filterAction = trim($_GET['action_filter'] ?? '');
$filterUser   = trim($_GET['user_filter']   ?? '');
$filterDate   = trim($_GET['date_filter']   ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$params = [];
$where  = "WHERE 1=1";
if ($filterAction) { $where .= " AND L.ACTION = ?";                       $params[] = $filterAction; }
if ($filterUser)   { $where .= " AND U.USER_ID = ?";                      $params[] = (int)$filterUser; }
if ($filterDate)   { $where .= " AND CAST(L.CREATED_AT AS DATE) = ?";     $params[] = $filterDate; }

$countSql    = "SELECT COUNT(*) AS CNT FROM AUDIT_LOGS L INNER JOIN USERS U ON U.USER_ID = L.USER_ID $where";
$countResult = sqlsrv_query($conn, $countSql, $params ?: []);
$totalCount  = 0;
if ($countResult) { $cr = sqlsrv_fetch_array($countResult, SQLSRV_FETCH_ASSOC); $totalCount = (int)$cr['CNT']; }
$totalPages = max(1, (int)ceil($totalCount / $perPage));

$logSql = "SELECT L.LOG_ID, L.ACTION, L.DETAILS, L.CREATED_AT,
                  U.USERNAME, U.EMAIL, U.ROLE, U.USER_ID,
                  R.FIRST_NAME, R.LAST_NAME
           FROM AUDIT_LOGS L
           INNER JOIN USERS U ON U.USER_ID = L.USER_ID
           LEFT JOIN REGISTRATION R ON R.USER_ID = L.USER_ID
           $where
           ORDER BY L.CREATED_AT DESC
           OFFSET $offset ROWS FETCH NEXT $perPage ROWS ONLY";

$logResult = sqlsrv_query($conn, $logSql, $params ?: []);
$logs = [];
if ($logResult) { while ($row = sqlsrv_fetch_array($logResult, SQLSRV_FETCH_ASSOC)) { $logs[] = $row; } }

$allUsers = [];
$ur = sqlsrv_query($conn,
    "SELECT U.USER_ID, U.USERNAME, R.FIRST_NAME, R.LAST_NAME
     FROM USERS U LEFT JOIN REGISTRATION R ON R.USER_ID = U.USER_ID
     ORDER BY R.LAST_NAME ASC"
);
if ($ur) { while ($row = sqlsrv_fetch_array($ur, SQLSRV_FETCH_ASSOC)) { $allUsers[] = $row; } }

$actionTypes = [];
$ar = sqlsrv_query($conn, "SELECT DISTINCT ACTION FROM AUDIT_LOGS ORDER BY ACTION ASC");
if ($ar) { while ($row = sqlsrv_fetch_array($ar, SQLSRV_FETCH_ASSOC)) { $actionTypes[] = rtrim($row['ACTION']); } }

function getActionTagClass($action) {
    $a = strtolower($action);
    if (strpos($a, 'create') !== false || strpos($a, 'add') !== false || strpos($a, 'register') !== false) return 'audit-tag-create';
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false || strpos($a, 'enable') !== false || strpos($a, 'disable') !== false) return 'audit-tag-update';
    if (strpos($a, 'login') !== false  || strpos($a, 'access') !== false || strpos($a, 'view') !== false) return 'audit-tag-access';
    return 'audit-tag-review';
}
function getInitials($first, $last) {
    return strtoupper(substr($first ?? '', 0, 1) . substr($last ?? '', 0, 1));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Audit Logs — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="base.css" />
  <link rel="stylesheet" href="superadmin.css" />
  <style>
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}

    .audit-page-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px;flex-wrap:wrap}
    .audit-page-head h2{font-size:26px;font-weight:700;color:var(--navy);margin-bottom:4px}
    .audit-page-head p{font-size:14px;color:var(--text-muted)}

    /* ── Pagination footer fix ── */
    .audit-shell-foot{
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 14px;
      padding: 16px 20px;
      border-top: 1px solid var(--border);
    }
    .audit-pager-left  { display: flex; justify-content: flex-start; }
    .audit-pager-center{ display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center; }
    .audit-pager-right { display: flex; justify-content: flex-end; }

    .audit-page-button,
    .audit-nav-button{
      min-height: 36px;
      padding: 0 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--surface);
      color: var(--navy);
      font-size: 13px;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .audit-page-button:hover,
    .audit-nav-button:hover{
      background: rgba(204,255,0,0.12);
      border-color: var(--lime-dim);
    }
    .audit-page-button.active{
      background: var(--navy);
      color: #fff;
      border-color: var(--navy);
    }
    .audit-page-button[disabled],
    .audit-nav-button[disabled]{
      opacity: 0.35;
      cursor: default;
      pointer-events: none;
    }
    .audit-page-dots{
      font-size: 13px;
      font-weight: 700;
      color: var(--text-muted);
      padding: 0 2px;
      display: inline-flex;
      align-items: center;
    }
  </style>
</head>
<body class="superadmin-body">

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="logout-btns">
      <button class="btn-cancel" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand">
      <h1>Barangay Alapan 1-A</h1>
      <p>Super Admin</p>
    </div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php">Reports</a>
      <a href="superadminauditlogs.php" class="active">Audit Logs</a>
    </nav>
    <div class="superadmin-header-right">
      <div class="superadmin-user">
        <div class="superadmin-user-info">
          <span class="superadmin-user-name"><?= $displayName ?></span>
        </div>
      </div>
      <a href="#" class="superadmin-logout" onclick="openLogout(); return false;">Logout</a>
    </div>
  </header>

  <main class="superadmin-content">
    <div class="audit-page-head">
      <div>
        <h2>Audit Logs</h2>
        <p><?= number_format($totalCount) ?> total log<?= $totalCount !== 1 ? 's' : '' ?> recorded</p>
      </div>
    </div>

    <section class="superadmin-panel audit-shell">
      <div class="audit-shell-head">
        <form method="GET" id="auditFilterForm">
          <div class="audit-tools">
            <div class="audit-tool-group">
              <label>Action</label>
              <select name="action_filter" class="audit-select"
                onchange="document.getElementById('auditFilterForm').submit()">
                <option value="">All Actions</option>
                <?php foreach ($actionTypes as $at): ?>
                <option value="<?= htmlspecialchars($at) ?>" <?= $filterAction === $at ? 'selected' : '' ?>>
                  <?= htmlspecialchars($at) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="audit-tool-group">
              <label>User</label>
              <select name="user_filter" class="audit-select"
                onchange="document.getElementById('auditFilterForm').submit()">
                <option value="">All Users</option>
                <?php foreach ($allUsers as $u): ?>
                <option value="<?= (int)$u['USER_ID'] ?>"
                  <?= $filterUser == $u['USER_ID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars(rtrim($u['FIRST_NAME'] ?? '') . ' ' . rtrim($u['LAST_NAME'] ?? $u['USERNAME'])) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="audit-tool-group">
              <label>Date</label>
              <div class="audit-date-field">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date_filter" class="audit-date-input"
                  value="<?= htmlspecialchars($filterDate) ?>"
                  onchange="document.getElementById('auditFilterForm').submit()">
              </div>
            </div>
            <div class="audit-tool-actions">
              <button type="submit" class="superadmin-primary-btn">Apply</button>
              <a href="superadminauditlogs.php" class="superadmin-outline-btn">Clear</a>
            </div>
          </div>
        </form>
      </div>

      <div class="audit-shell-body">
        <div class="audit-table-wrap">
          <table class="superadmin-table audit-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Action</th>
                <th>Timestamp</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);">
                  No log entries found.
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($logs as $log):
                $logFirst   = rtrim($log['FIRST_NAME'] ?? '');
                $logLast    = rtrim($log['LAST_NAME']  ?? '');
                $logName    = trim($logFirst . ' ' . $logLast) ?: rtrim($log['USERNAME']);
                $logEmail   = htmlspecialchars(rtrim($log['EMAIL']));
                $logRole    = ucfirst(rtrim($log['ROLE']));
                $logAction  = rtrim($log['ACTION']);
                $logDetails = htmlspecialchars(rtrim($log['DETAILS'] ?? '—'));
                $logTime    = $log['CREATED_AT'] instanceof DateTime
                  ? $log['CREATED_AT']->format('Y-m-d H:i:s')
                  : $log['CREATED_AT'];
                $initials   = getInitials($logFirst, $logLast) ?: strtoupper(substr($log['USERNAME'], 0, 2));
                $tagClass   = getActionTagClass($logAction);
              ?>
              <tr>
                <td>
                  <div class="audit-person">
                    <div class="audit-person-badge"><?= $initials ?></div>
                    <div class="audit-person-text">
                      <span class="audit-person-name"><?= htmlspecialchars($logName) ?></span>
                      <span class="audit-person-role"><?= $logRole ?></span>
                    </div>
                  </div>
                </td>
                <td><span class="audit-email"><?= $logEmail ?></span></td>
                <td><?= $logRole ?></td>
                <td><span class="audit-tag <?= $tagClass ?>"><?= htmlspecialchars($logAction) ?></span></td>
                <td style="white-space:nowrap;"><?= $logTime ?></td>
                <td><?= $logDetails ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="audit-shell-foot">
          <div class="audit-pager-left">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
               class="audit-nav-button">
              <i class="fa-solid fa-arrow-left"></i> Previous
            </a>
            <?php else: ?>
            <button class="audit-nav-button" disabled>
              <i class="fa-solid fa-arrow-left"></i> Previous
            </button>
            <?php endif; ?>
          </div>

          <div class="audit-pager-center">
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1) {
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="audit-page-button">1</a>';
                if ($start > 2) echo '<span class="audit-page-dots">…</span>';
            }
            for ($i = $start; $i <= $end; $i++) {
                $cls = $i === $page ? ' active' : '';
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i]))
                    . '" class="audit-page-button' . $cls . '">' . $i . '</a>';
            }
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span class="audit-page-dots">…</span>';
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '" class="audit-page-button">' . $totalPages . '</a>';
            }
            ?>
          </div>

          <div class="audit-pager-right">
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
               class="audit-nav-button">
              Next <i class="fa-solid fa-arrow-right"></i>
            </a>
            <?php else: ?>
            <button class="audit-nav-button" disabled>
              Next <i class="fa-solid fa-arrow-right"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

<script>
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>
</body>
</html>