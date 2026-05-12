<?php
session_start();
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'staff' ||
    strtolower(trim($_SESSION['position'] ?? '')) !== 'treasurer'
) {
    header("Location: login.php"); exit();
}

$serverName        = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => "", "CharacterSet" => "UTF-8"];
$conn              = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];

$meRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$firstName   = $meRow ? htmlspecialchars(rtrim($meRow['FIRST_NAME'])) : 'Treasurer';
$lastName    = $meRow ? htmlspecialchars(rtrim($meRow['LAST_NAME']))  : '';
$displayName = trim("$firstName $lastName") ?: 'Treasurer';
$initials    = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

function fdate($val, $fmt = 'M d, Y') {
    if ($val instanceof DateTime) return $val->format($fmt);
    return date($fmt, strtotime($val ?? 'now'));
}
function statusClass($s) {
    return match(strtoupper(trim($s))) {
        'PAID'   => 'paid',
        'WAIVED' => 'waived',
        default  => 'pending',
    };
}
function methodClass($m) {
    $m = strtolower(trim($m));
    if (str_contains($m, 'gcash') || str_contains($m, 'online')) return 'gcash';
    if (str_contains($m, 'waived')) return 'waived';
    return 'cash';
}

/* ── HANDLE ACTIONS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if (rtrim($_POST['action']) === 'logout') {
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'LOGOUT', 'Treasurer logged out', GETDATE())",
            [$userId]);
        session_destroy();
        header("Location: login.php"); exit();
    }
}

/* ── PENDING COUNT FOR SIDEBAR ── */
$pendingRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS DR
         WHERE DR.STATUS = 'APPROVED'
           AND NOT EXISTS (
               SELECT 1 FROM PAYMENTS P
               WHERE P.REQUEST_ID = DR.REQUEST_ID
                 AND P.PAYMENT_STATUS IN ('PAID','WAIVED')
           )"),
    SQLSRV_FETCH_ASSOC
);
$pendingPaymentCount = $pendingRow ? (int)$pendingRow['CNT'] : 0;

/* ── CSV EXPORT ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $expStmt = sqlsrv_query($conn,
        "SELECT P.PAYMENT_ID, R.FIRST_NAME, R.LAST_NAME,
                P.DOCUMENT_TYPE, P.AMOUNT, P.PAYMENT_METHOD, P.PAYMENT_STATUS, P.CREATED_AT
         FROM PAYMENTS P
         LEFT JOIN REGISTRATION R ON R.USER_ID = P.USER_ID
         ORDER BY P.CREATED_AT DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transaction_history_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Resident', 'Document Type', 'Amount', 'Payment Method', 'Status', 'Date']);
    while ($expStmt && $r = sqlsrv_fetch_array($expStmt, SQLSRV_FETCH_ASSOC)) {
        $fn  = rtrim($r['FIRST_NAME'] ?? '');
        $ln  = rtrim($r['LAST_NAME']  ?? '');
        fputcsv($out, [
            'TRX-' . str_pad((int)$r['PAYMENT_ID'], 4, '0', STR_PAD_LEFT),
            trim("$fn $ln"),
            rtrim($r['DOCUMENT_TYPE']),
            number_format((float)$r['AMOUNT'], 2),
            rtrim($r['PAYMENT_METHOD']),
            rtrim($r['PAYMENT_STATUS']),
            fdate($r['CREATED_AT']),
        ]);
    }
    fclose($out);
    exit();
}

/* ── FILTERS ── */
$search    = trim($_GET['search']    ?? '');
$filterDoc = trim($_GET['doc']       ?? '');
$filterSt  = strtoupper(trim($_GET['status'] ?? ''));
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

$where  = ["1=1"];
$params = [];

if ($search !== '') {
    $where[]  = "(R.FIRST_NAME LIKE ? OR R.LAST_NAME LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterDoc !== '') {
    $where[]  = "P.DOCUMENT_TYPE = ?";
    $params[] = $filterDoc;
}
if ($filterSt !== '' && $filterSt !== 'ALL') {
    $where[]  = "P.PAYMENT_STATUS = ?";
    $params[] = $filterSt;
}
if ($dateFrom !== '') {
    $where[]  = "CAST(P.CREATED_AT AS DATE) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[]  = "CAST(P.CREATED_AT AS DATE) <= ?";
    $params[] = $dateTo;
}

$whereStr = implode(' AND ', $where);

$countRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT
         FROM PAYMENTS P
         LEFT JOIN REGISTRATION R ON R.USER_ID = P.USER_ID
         WHERE $whereStr",
        $params ?: []),
    SQLSRV_FETCH_ASSOC
);
$totalRecords = $countRow ? (int)$countRow['CNT'] : 0;
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

$histStmt = sqlsrv_query($conn,
    "SELECT P.PAYMENT_ID, P.DOCUMENT_TYPE, P.AMOUNT, P.PAYMENT_METHOD, P.PAYMENT_STATUS, P.CREATED_AT,
            R.FIRST_NAME, R.LAST_NAME
     FROM PAYMENTS P
     LEFT JOIN REGISTRATION R ON R.USER_ID = P.USER_ID
     WHERE $whereStr
     ORDER BY P.CREATED_AT DESC
     OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
    array_merge($params, [$offset, $perPage])
);
$history = [];
while ($histStmt && $row = sqlsrv_fetch_array($histStmt, SQLSRV_FETCH_ASSOC)) {
    $fn = rtrim($row['FIRST_NAME'] ?? '');
    $ln = rtrim($row['LAST_NAME']  ?? '');
    $st = rtrim($row['PAYMENT_STATUS'] ?? 'PENDING');
    $mt = rtrim($row['PAYMENT_METHOD'] ?? '');
    $history[] = [
        'id'      => (int)$row['PAYMENT_ID'],
        'code'    => 'TRX-' . str_pad((int)$row['PAYMENT_ID'], 4, '0', STR_PAD_LEFT),
        'name'    => trim("$fn $ln") ?: 'Unknown',
        'initials'=> strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1)),
        'doc'     => htmlspecialchars(rtrim($row['DOCUMENT_TYPE'])),
        'amount'  => (float)$row['AMOUNT'],
        'method'  => htmlspecialchars($mt),
        'status'  => htmlspecialchars($st),
        'date'    => fdate($row['CREATED_AT']),
        'st_class'=> statusClass($st),
        'mt_class'=> methodClass($mt),
    ];
}

/* ── DISTINCT DOC TYPES ── */
$docTypeStmt = sqlsrv_query($conn,
    "SELECT DISTINCT DOCUMENT_TYPE FROM PAYMENTS ORDER BY DOCUMENT_TYPE");
$docTypes = [];
while ($docTypeStmt && $r = sqlsrv_fetch_array($docTypeStmt, SQLSRV_FETCH_ASSOC)) {
    $docTypes[] = rtrim($r['DOCUMENT_TYPE']);
}

/* ── INCOME BREAKDOWN (current month top 3) ── */
$monthNum = (int)date('m');
$yearNum  = (int)date('Y');
$breakdownStmt = sqlsrv_query($conn,
    "SELECT TOP 3 P.DOCUMENT_TYPE, ISNULL(SUM(P.AMOUNT),0) AS AMT
     FROM PAYMENTS P
     WHERE P.PAYMENT_STATUS = 'PAID'
       AND MONTH(P.CREATED_AT) = ? AND YEAR(P.CREATED_AT) = ?
     GROUP BY P.DOCUMENT_TYPE ORDER BY AMT DESC",
    [$monthNum, $yearNum]);
$breakdown = [];
while ($breakdownStmt && $r = sqlsrv_fetch_array($breakdownStmt, SQLSRV_FETCH_ASSOC)) {
    $breakdown[] = ['doc' => rtrim($r['DOCUMENT_TYPE']), 'amt' => (float)$r['AMT']];
}
$breakdownMax   = $breakdown ? max(array_column($breakdown, 'amt')) : 1;
$breakdownTotal = array_sum(array_column($breakdown, 'amt'));

/* ── TOTAL INCOME THIS MONTH ── */
$totalRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT ISNULL(SUM(AMOUNT),0) AS T FROM PAYMENTS
         WHERE PAYMENT_STATUS = 'PAID' AND MONTH(CREATED_AT)=? AND YEAR(CREATED_AT)=?",
        [$monthNum, $yearNum]),
    SQLSRV_FETCH_ASSOC
);
$totalThisMonth = $totalRow ? (float)$totalRow['T'] : 0;

/* ── BUILD QUERY STRING HELPER ── */
function buildQuery(array $override = []): string {
    $base = ['search' => $_GET['search'] ?? '', 'doc' => $_GET['doc'] ?? '',
             'status' => $_GET['status'] ?? '', 'date_from' => $_GET['date_from'] ?? '',
             'date_to' => $_GET['date_to'] ?? '', 'page' => $_GET['page'] ?? 1];
    $merged = array_merge($base, $override);
    return http_build_query(array_filter($merged, fn($v) => $v !== ''));
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Transaction History — BarangayKonek</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
<style>
:root{--navy:#051650;--navy-mid:#0a2160;--lime:#ccff00;--lime-dark:#b8e900;--surface:#ffffff;--soft-bg:#f8fafc;--border:#e1e7f0;--text:#1a2240;--muted:#7a869a;--green-bg:#f0fdf4;--green-text:#166534;--amber-bg:#fffbeb;--amber-text:#92400e;--blue-bg:#eff6ff;--blue-text:#1e40af;--red-bg:#fef2f2;--red-text:#991b1b;--gray-bg:#f1f5f9;--gray-text:#475569;--sidebar-w:228px;--sidebar-mini:64px;--topbar-h:62px;--shadow-light:0 8px 24px rgba(5,22,80,0.08);--shadow-hover:0 16px 34px rgba(5,22,80,0.13);--shadow-sidebar:6px 0 18px rgba(5,22,80,0.14);}
*{box-sizing:border-box;}
body{margin:0;font-family:"DM Sans",sans-serif;background:radial-gradient(circle at top left,rgba(204,255,0,0.1),transparent 32%),linear-gradient(135deg,#f8fbff 0%,#eef3fb 100%);color:var(--text);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;}button,input,select{font-family:inherit;}
@keyframes treasurerhistoryFadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
@keyframes treasurerhistoryBellShake{0%,100%{transform:rotate(0deg);}20%{transform:rotate(14deg);}40%{transform:rotate(-12deg);}60%{transform:rotate(8deg);}80%{transform:rotate(-6deg);}}
@keyframes treasurerhistoryPopIn{from{opacity:0;transform:scale(0.94);}to{opacity:1;transform:scale(1);}}

.treasurerhistory-container{display:flex;min-height:100vh;}
.treasurerhistory-sidebar{width:var(--sidebar-w);height:100vh;position:fixed;top:0;left:0;z-index:40;overflow:hidden;display:flex;flex-direction:column;background:radial-gradient(circle at top left,rgba(204,255,0,0.14),transparent 28%),linear-gradient(180deg,#051650 0%,#081d63 56%,#040f3b 100%);box-shadow:var(--shadow-sidebar);transition:width 0.25s ease;}
.treasurerhistory-sidebar.mini{width:var(--sidebar-mini);}
.treasurerhistory-toggle-wrap{position:fixed;top:50%;left:var(--sidebar-w);transform:translate(-50%,-50%);z-index:200;transition:left 0.25s ease;}
.treasurerhistory-toggle-wrap.mini{left:var(--sidebar-mini);}
.treasurerhistory-toggle-btn{width:30px;height:30px;border-radius:999px;background:var(--surface);border:1px solid var(--border);box-shadow:0 5px 12px rgba(5,22,80,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:background 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-toggle-btn:hover{background:var(--lime);border-color:var(--lime);transform:scale(1.05);}
.treasurerhistory-toggle-btn i{font-size:10px;color:var(--navy);transition:transform 0.25s ease;}
.treasurerhistory-toggle-wrap.mini .treasurerhistory-toggle-btn i{transform:rotate(180deg);}
.treasurerhistory-identity{height:var(--topbar-h);display:flex;align-items:center;gap:11px;padding:0 15px;border-bottom:1px solid rgba(255,255,255,0.1);white-space:nowrap;overflow:hidden;}
.treasurerhistory-identity-logo{width:38px;height:38px;border-radius:14px;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,0.12);}
.treasurerhistory-identity-logo img{width:100%;height:100%;object-fit:cover;}
.treasurerhistory-identity-name{font-size:14px;font-weight:800;color:#fff;line-height:1.2;}
.treasurerhistory-identity-chip{display:inline-flex;align-items:center;margin-top:5px;padding:4px 10px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:11px;font-weight:800;line-height:1;}
.treasurerhistory-sidebar.mini .treasurerhistory-identity-name,.treasurerhistory-sidebar.mini .treasurerhistory-identity-chip{opacity:0;width:0;pointer-events:none;}
.treasurerhistory-sidebar.mini .treasurerhistory-identity-logo{margin:0 auto;}
.treasurerhistory-menu{flex:1;padding:16px 10px;display:flex;flex-direction:column;gap:5px;overflow-y:auto;overflow-x:hidden;}
.treasurerhistory-menu::-webkit-scrollbar{width:0;}
.treasurerhistory-menu-divider{height:1px;background:rgba(255,255,255,0.08);margin:8px;flex-shrink:0;}
.treasurerhistory-menu-link{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.66);cursor:pointer;white-space:nowrap;overflow:hidden;flex-shrink:0;text-decoration:none;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-menu-link:hover{background:rgba(255,255,255,0.09);color:#fff;transform:translateX(3px);}
.treasurerhistory-menu-link.active{background:var(--lime);color:var(--navy);}
.treasurerhistory-menu-left{display:flex;align-items:center;gap:10px;min-width:0;}
.treasurerhistory-menu-left i{width:18px;text-align:center;font-size:13px;flex-shrink:0;}
.treasurerhistory-menu-label{overflow:hidden;text-overflow:ellipsis;}
.treasurerhistory-sidebar.mini .treasurerhistory-menu-label{opacity:0;width:0;pointer-events:none;}
.treasurerhistory-menu-count{font-size:10px;font-weight:800;background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);min-width:22px;height:22px;padding:0 6px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.treasurerhistory-menu-link.active .treasurerhistory-menu-count{background:rgba(5,22,80,0.18);color:var(--navy);}
.treasurerhistory-sidebar.mini .treasurerhistory-menu-count{opacity:0;pointer-events:none;}
.treasurerhistory-sidebar-footer{padding:13px 10px;border-top:1px solid rgba(255,255,255,0.08);}
.treasurerhistory-logout{display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.46);background:transparent;border:none;cursor:pointer;width:100%;text-align:left;white-space:nowrap;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-logout:hover{background:rgba(239,68,68,0.14);color:#fca5a5;transform:translateX(3px);}

.treasurerhistory-main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;min-width:0;transition:margin-left 0.25s ease;}
.treasurerhistory-main.shifted{margin-left:var(--sidebar-mini);}
.treasurerhistory-topbar{height:var(--topbar-h);background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;padding:0 24px;position:sticky;top:0;z-index:30;}
.treasurerhistory-topbar-search{flex:1;max-width:390px;height:38px;display:flex;align-items:center;gap:8px;padding:0 14px;background:var(--soft-bg);border:1px solid var(--border);border-radius:999px;transition:background 0.18s ease,border-color 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-topbar-search:focus-within{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.treasurerhistory-topbar-search i{color:var(--muted);font-size:12px;}
.treasurerhistory-topbar-search input{flex:1;border:none;outline:none;background:transparent;font-size:13px;color:var(--text);}
.treasurerhistory-topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.treasurerhistory-topbar-icon{width:38px;height:38px;border-radius:999px;background:var(--soft-bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:13px;cursor:pointer;position:relative;text-decoration:none;transition:border-color 0.18s ease,color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-topbar-icon:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.treasurerhistory-topbar-icon:hover i{animation:treasurerhistoryBellShake 0.55s ease;}
.treasurerhistory-notification-count{position:absolute;top:-6px;right:-5px;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
.treasurerhistory-profile{display:flex;align-items:center;gap:8px;padding:5px 13px 5px 5px;border:1px solid var(--border);border-radius:999px;background:var(--soft-bg);cursor:pointer;text-decoration:none;transition:border-color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-profile:hover{border-color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.treasurerhistory-avatar{width:30px;height:30px;border-radius:999px;background:var(--navy);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--lime);flex-shrink:0;}
.treasurerhistory-profile-name{font-size:13px;font-weight:700;color:var(--navy);white-space:nowrap;}
.treasurerhistory-profile-role{font-size:11px;color:var(--muted);line-height:1;}

.treasurerhistory-body{padding:24px 26px 52px;display:flex;flex-direction:column;gap:20px;}
.treasurerhistory-banner{background:linear-gradient(135deg,rgba(5,22,80,0.98),rgba(10,33,96,0.95)),radial-gradient(circle at top right,rgba(204,255,0,0.28),transparent 35%);border-radius:28px;padding:26px 30px;display:flex;align-items:center;justify-content:space-between;gap:20px;box-shadow:var(--shadow-light);position:relative;overflow:hidden;animation:treasurerhistoryFadeUp 0.28s ease both;transition:transform 0.2s ease,box-shadow 0.2s ease;}
.treasurerhistory-banner:hover{transform:translateY(-3px);box-shadow:var(--shadow-hover);}
.treasurerhistory-banner::before{content:"";position:absolute;width:210px;height:210px;right:-90px;top:-110px;background:rgba(204,255,0,0.14);border-radius:50%;}
.treasurerhistory-banner-left,.treasurerhistory-banner-right{position:relative;z-index:1;}
.treasurerhistory-banner-left h2{font-size:22px;font-weight:800;color:#fff;margin:0;}
.treasurerhistory-banner-left p{font-size:13px;color:rgba(255,255,255,0.7);margin:7px 0 0;max-width:560px;}
.treasurerhistory-banner-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.treasurerhistory-export-btn,.treasurerhistory-print-btn{min-height:38px;padding:0 14px;border-radius:999px;font-size:12px;font-weight:800;border:1px solid transparent;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-export-btn:hover,.treasurerhistory-print-btn:hover{transform:translateY(-1px);}
.treasurerhistory-export-btn{background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.18);}
.treasurerhistory-export-btn:hover{background:var(--lime);border-color:var(--lime);color:var(--navy);}
.treasurerhistory-print-btn{background:var(--lime);color:var(--navy);}
.treasurerhistory-print-btn:hover{background:#fff;border-color:#fff;color:var(--navy);}

.treasurerhistory-content{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;align-items:start;animation:treasurerhistoryFadeUp 0.34s 0.08s ease both;}
.treasurerhistory-records-container,.treasurerhistory-filter-card,.treasurerhistory-breakdown-card{background:rgba(255,255,255,0.94);border:1px solid var(--border);border-radius:28px;box-shadow:var(--shadow-light);overflow:hidden;transition:transform 0.2s ease,box-shadow 0.2s ease,border-color 0.2s ease;}
.treasurerhistory-records-container:hover,.treasurerhistory-filter-card:hover,.treasurerhistory-breakdown-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-hover);border-color:#d7deea;}
.treasurerhistory-records-head{padding:22px 24px 16px;border-bottom:1px solid var(--border);background:linear-gradient(180deg,#fff 0%,#fbfcff 100%);}
.treasurerhistory-records-head h3{font-size:18px;font-weight:800;color:var(--navy);margin-bottom:4px;}
.treasurerhistory-records-head p{font-size:12px;color:var(--muted);font-weight:600;margin-bottom:0;}
.treasurerhistory-records-count{font-size:12px;color:var(--muted);font-weight:800;white-space:nowrap;}
.treasurerhistory-toolbar{display:grid;grid-template-columns:minmax(220px,1fr) 210px auto;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid var(--border);background:#fff;}
.treasurerhistory-toolbar-search{height:44px;display:flex;align-items:center;gap:9px;padding:0 15px;background:var(--soft-bg);border:1px solid var(--border);border-radius:999px;min-width:0;}
.treasurerhistory-toolbar-search:focus-within{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.treasurerhistory-toolbar-search i{color:var(--muted);font-size:13px;}
.treasurerhistory-toolbar-search input{flex:1;border:none;outline:none;background:transparent;font-size:13px;color:var(--text);min-width:0;}
.treasurerhistory-doc-select{height:44px;padding:0 14px;border:1px solid var(--border);border-radius:999px;background:var(--soft-bg);font-size:13px;font-weight:700;color:var(--text);outline:none;cursor:pointer;width:100%;}
.treasurerhistory-doc-select:focus{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.treasurerhistory-toolbar-chips{display:flex;align-items:center;gap:8px;flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px;scrollbar-width:none;}
.treasurerhistory-toolbar-chips::-webkit-scrollbar{display:none;}
.treasurerhistory-chip,.treasurerhistory-filter-chip,.treasurerhistory-status,.treasurerhistory-method{border-radius:12px;}
.treasurerhistory-chip,.treasurerhistory-filter-chip{min-height:38px;padding:0 15px;font-size:12px;font-weight:800;border:1px solid var(--border);background:var(--soft-bg);color:var(--muted);cursor:pointer;white-space:nowrap;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-chip:hover,.treasurerhistory-filter-chip:hover{border-color:var(--navy);color:var(--navy);background:#fff;transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.06);}
.treasurerhistory-chip.on,.treasurerhistory-filter-chip.on{background:var(--navy);border-color:var(--navy);color:var(--lime);}
.treasurerhistory-table-wrap{overflow-x:auto;}
.treasurerhistory-history-table{width:100%;border-collapse:collapse;font-size:13px;}
.treasurerhistory-history-table thead th{background:#fafbfd;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--muted);padding:12px 16px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--border);}
.treasurerhistory-history-table tbody tr{border-bottom:1px solid var(--border);transition:background 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-history-table tbody tr:hover{background:#fbfdff;box-shadow:inset 4px 0 0 var(--lime);}
.treasurerhistory-history-table td{padding:13px 16px;vertical-align:middle;color:var(--text);}
.treasurerhistory-person-cell{display:flex;align-items:center;gap:10px;}
.treasurerhistory-person-avatar{width:34px;height:34px;border-radius:999px;background:var(--navy);color:var(--lime);font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-history-table tbody tr:hover .treasurerhistory-person-avatar{transform:scale(1.05);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.treasurerhistory-person-name{font-size:13px;font-weight:800;color:var(--navy);display:block;white-space:nowrap;}
.treasurerhistory-doc-type{font-size:12.5px;color:var(--text);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;font-weight:700;}
.treasurerhistory-amount{font-size:13px;font-weight:800;color:var(--navy);white-space:nowrap;}
.treasurerhistory-amount.waived{color:var(--muted);}
.treasurerhistory-date-text{font-size:12.5px;color:var(--muted);white-space:nowrap;font-weight:700;}
.treasurerhistory-status,.treasurerhistory-method{display:inline-flex;align-items:center;justify-content:center;min-height:28px;padding:0 11px;font-size:11px;font-weight:800;white-space:nowrap;border:1px solid transparent;}
.treasurerhistory-status.paid{background:var(--green-bg);color:var(--green-text);border-color:rgba(34,197,94,0.18);}
.treasurerhistory-status.waived{background:var(--gray-bg);color:var(--gray-text);border-color:rgba(71,85,105,0.12);}
.treasurerhistory-status.pending{background:var(--amber-bg);color:var(--amber-text);border-color:rgba(245,158,11,0.18);}
.treasurerhistory-method.cash{background:var(--blue-bg);color:var(--blue-text);}
.treasurerhistory-method.gcash{background:#ecfdf5;color:#065f46;}
.treasurerhistory-method.waived{background:var(--gray-bg);color:var(--gray-text);}
.treasurerhistory-view-btn{min-height:34px;min-width:68px;padding:0 13px;border-radius:999px;background:var(--navy);color:#fff;font-size:12px;font-weight:800;border:1px solid var(--navy);cursor:pointer;white-space:nowrap;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-view-btn:hover{background:var(--lime);color:var(--navy);border-color:var(--lime);transform:translateY(-1px);box-shadow:0 8px 16px rgba(204,255,0,0.18);}
.treasurerhistory-table-foot{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);background:#fafbfd;flex-wrap:wrap;gap:10px;}
.treasurerhistory-foot-text{font-size:12px;color:var(--muted);font-weight:600;}
.treasurerhistory-pager{display:flex;gap:5px;align-items:center;}
.treasurerhistory-pager-btn{min-width:31px;height:31px;border-radius:12px;border:1px solid var(--border);background:#fff;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0 7px;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-pager-btn:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);}
.treasurerhistory-pager-btn.on{background:var(--navy);color:var(--lime);border-color:var(--navy);}
.treasurerhistory-pager-btn:disabled{opacity:0.4;cursor:default;pointer-events:none;}

.treasurerhistory-right-col{display:flex;flex-direction:column;gap:16px;position:sticky;top:calc(var(--topbar-h) + 18px);}
.treasurerhistory-filter-card,.treasurerhistory-breakdown-card{padding:20px;}
.treasurerhistory-filter-head{margin-bottom:16px;}
.treasurerhistory-filter-head h4{margin:0 0 4px;font-size:17px;font-weight:800;color:var(--navy);}
.treasurerhistory-filter-head p{margin:0;font-size:12px;font-weight:600;color:var(--muted);line-height:1.5;}
.treasurerhistory-filter-group{margin-bottom:14px;}
.treasurerhistory-filter-label{display:block;margin-bottom:7px;font-size:12px;font-weight:800;color:var(--muted);}
.treasurerhistory-filter-input,.treasurerhistory-filter-select,.treasurerhistory-date-field{width:100%;height:42px;border:1px solid var(--border);border-radius:999px;background:var(--soft-bg);padding:0 14px;outline:none;font-size:13px;font-weight:700;color:var(--text);transition:background 0.18s ease,border-color 0.18s ease,box-shadow 0.18s ease;}
.treasurerhistory-filter-input:focus,.treasurerhistory-filter-select:focus,.treasurerhistory-date-field:focus{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.treasurerhistory-date-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;width:100%;}
.treasurerhistory-date-field{min-width:0;padding:0 10px;font-size:12px;}
.treasurerhistory-filter-chips{display:flex;flex-wrap:wrap;gap:8px;}
.treasurerhistory-filter-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;width:100%;margin-top:16px;}
.treasurerhistory-apply-btn,.treasurerhistory-clear-btn{width:100%;min-height:38px;border-radius:999px;font-size:12px;font-weight:800;cursor:pointer;border:1px solid transparent;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurerhistory-apply-btn{background:var(--navy);border-color:var(--navy);color:#fff;}
.treasurerhistory-apply-btn:hover{background:var(--lime);border-color:var(--lime);color:var(--navy);transform:translateY(-1px);}
.treasurerhistory-clear-btn{background:var(--soft-bg);border-color:var(--border);color:var(--muted);}
.treasurerhistory-clear-btn:hover{background:#fff;border-color:var(--navy);color:var(--navy);transform:translateY(-1px);}

.treasurerhistory-breakdown-card{overflow:hidden;padding:0;}
.treasurerhistory-breakdown-head{padding:18px 20px 14px;border-bottom:1px solid var(--border);background:linear-gradient(180deg,#fff 0%,#fbfcff 100%);}
.treasurerhistory-breakdown-head h4{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:0;}
.treasurerhistory-breakdown-head p{font-size:12px;color:var(--muted);margin-top:3px;font-weight:600;margin-bottom:0;}
.treasurerhistory-breakdown-body{padding:16px 20px 18px;display:flex;flex-direction:column;gap:12px;}
.treasurerhistory-breakdown-row{display:flex;align-items:center;gap:10px;transition:transform 0.18s ease;}
.treasurerhistory-breakdown-row:hover{transform:translateX(3px);}
.treasurerhistory-breakdown-label{font-size:12.5px;color:var(--text);font-weight:700;min-width:0;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.treasurerhistory-breakdown-bar-wrap{flex:1.4;height:8px;background:#eaf0f7;border-radius:999px;overflow:hidden;}
.treasurerhistory-breakdown-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--navy),#254ca8);}
.treasurerhistory-breakdown-val{font-size:12px;font-weight:800;color:var(--navy);white-space:nowrap;min-width:50px;text-align:right;}
.treasurerhistory-breakdown-total{display:flex;align-items:center;justify-content:space-between;padding-top:13px;border-top:1px solid var(--border);margin-top:2px;}
.treasurerhistory-breakdown-total-label,.treasurerhistory-breakdown-total-val{font-size:14px;font-weight:800;color:var(--navy);}

.treasurerhistory-backdrop{position:fixed;inset:0;z-index:500;background:rgba(5,22,80,0.46);display:none;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px);}
.treasurerhistory-backdrop.open{display:flex;}
.treasurerhistory-modal-box,.treasurerhistory-logout-box{background:var(--surface);border-radius:28px;width:100%;box-shadow:0 24px 80px rgba(5,22,80,0.26);overflow:hidden;animation:treasurerhistoryPopIn 0.24s ease both;}
.treasurerhistory-modal-box{max-width:500px;}
.treasurerhistory-logout-box{max-width:340px;}
.treasurerhistory-modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border);}
.treasurerhistory-modal-head h3{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:0;}
.treasurerhistory-modal-close{width:32px;height:32px;border-radius:12px;border:1px solid var(--border);background:var(--soft-bg);color:var(--muted);font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.treasurerhistory-modal-body{padding:22px 24px;}
.treasurerhistory-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.treasurerhistory-modal-field{display:flex;flex-direction:column;gap:5px;padding:13px;border-radius:18px;background:var(--soft-bg);border:1px solid var(--border);}
.treasurerhistory-modal-field.wide{grid-column:1/-1;}
.treasurerhistory-modal-field label{font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);}
.treasurerhistory-modal-field p{font-size:13.5px;color:var(--text);font-weight:700;line-height:1.4;margin-bottom:0;}
.treasurerhistory-modal-foot{display:flex;gap:8px;justify-content:flex-end;padding:16px 24px 20px;border-top:1px solid var(--border);}
.treasurerhistory-modal-btn{min-height:38px;padding:0 18px;border-radius:999px;font-size:13px;font-weight:800;border:none;cursor:pointer;}
.treasurerhistory-modal-btn-ghost{background:var(--soft-bg);color:var(--muted);border:1px solid var(--border);}
.treasurerhistory-modal-btn-print{background:var(--navy);color:#fff;}
.treasurerhistory-modal-btn-print:hover{background:var(--lime);color:var(--navy);}
.treasurerhistory-logout-body{padding:34px 28px 22px;text-align:center;}
.treasurerhistory-logout-title{font-size:17px;font-weight:800;color:var(--navy);}
.treasurerhistory-logout-desc{font-size:13px;color:var(--muted);line-height:1.55;margin-top:8px;margin-bottom:0;}
.treasurerhistory-logout-foot{display:flex;gap:8px;padding:4px 28px 26px;}
.treasurerhistory-logout-foot .treasurerhistory-modal-btn{flex:1;}
.treasurerhistory-modal-btn-danger{background:var(--red-text);color:#fff;}

@media(max-width:1200px){.treasurerhistory-toolbar{grid-template-columns:1fr;}.treasurerhistory-toolbar-chips{width:100%;}}
@media(max-width:1100px){.treasurerhistory-content{grid-template-columns:1fr;}.treasurerhistory-right-col{position:static;}}
@media(max-width:760px){.treasurerhistory-sidebar{width:var(--sidebar-mini);}.treasurerhistory-main{margin-left:var(--sidebar-mini);}.treasurerhistory-toggle-wrap{left:var(--sidebar-mini);}.treasurerhistory-identity-name,.treasurerhistory-identity-chip,.treasurerhistory-menu-label,.treasurerhistory-menu-count{opacity:0;width:0;pointer-events:none;}.treasurerhistory-body{padding:18px 14px 44px;}.treasurerhistory-topbar{padding:0 14px;}.treasurerhistory-profile-name,.treasurerhistory-profile-role{display:none;}.treasurerhistory-topbar-search{max-width:none;}.treasurerhistory-banner{flex-direction:column;align-items:flex-start;}.treasurerhistory-banner-right,.treasurerhistory-banner-right button{width:100%;}.treasurerhistory-modal-grid{grid-template-columns:1fr;}.treasurerhistory-modal-foot,.treasurerhistory-logout-foot{flex-direction:column;}}
@media(max-width:520px){.treasurerhistory-date-row,.treasurerhistory-filter-actions{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="treasurerhistory-backdrop" id="treasurerhistory-view-modal">
  <div class="treasurerhistory-modal-box">
    <div class="treasurerhistory-modal-head">
      <h3>Transaction Details</h3>
      <button class="treasurerhistory-modal-close" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="treasurerhistory-modal-body">
      <div class="treasurerhistory-modal-grid">
        <div class="treasurerhistory-modal-field"><label>Resident</label><p id="th-modal-resident">—</p></div>
        <div class="treasurerhistory-modal-field"><label>Document Type</label><p id="th-modal-doctype">—</p></div>
        <div class="treasurerhistory-modal-field"><label>Transaction ID</label><p id="th-modal-txnid">—</p></div>
        <div class="treasurerhistory-modal-field"><label>Amount</label><p id="th-modal-amount">—</p></div>
        <div class="treasurerhistory-modal-field"><label>Date Paid</label><p id="th-modal-date">—</p></div>
        <div class="treasurerhistory-modal-field"><label>Payment Method</label><p id="th-modal-method">—</p></div>
        <div class="treasurerhistory-modal-field wide"><label>Status</label><p id="th-modal-status">—</p></div>
      </div>
    </div>
    <div class="treasurerhistory-modal-foot">
      <button class="treasurerhistory-modal-btn treasurerhistory-modal-btn-ghost" onclick="closeViewModal()">Close</button>
      <button class="treasurerhistory-modal-btn treasurerhistory-modal-btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
  </div>
</div>

<div class="treasurerhistory-backdrop" id="treasurerhistory-logout-modal">
  <div class="treasurerhistory-logout-box">
    <div class="treasurerhistory-logout-body">
      <div class="treasurerhistory-logout-title">Log out?</div>
      <p class="treasurerhistory-logout-desc">Are you sure you want to leave the Treasurer Portal?</p>
    </div>
    <div class="treasurerhistory-logout-foot">
      <button class="treasurerhistory-modal-btn treasurerhistory-modal-btn-ghost" onclick="closeLogoutModal()">Cancel</button>
      <form method="POST" style="flex:1;">
        <input type="hidden" name="action" value="logout"/>
        <button type="submit" class="treasurerhistory-modal-btn treasurerhistory-modal-btn-danger" style="width:100%;">Log Out</button>
      </form>
    </div>
  </div>
</div>

<div class="treasurerhistory-toggle-wrap" id="treasurerhistory-toggle-wrap">
  <button class="treasurerhistory-toggle-btn" id="treasurerhistory-toggle-button" title="Collapse / Expand">
    <i class="fa-solid fa-chevron-left"></i>
  </button>
</div>

<div class="treasurerhistory-container">
  <aside class="treasurerhistory-sidebar" id="treasurerhistory-sidebar">
    <div class="treasurerhistory-identity">
      <div class="treasurerhistory-identity-logo"><img src="alapan.png" alt="Alapan logo"/></div>
      <div>
        <div class="treasurerhistory-identity-name">BarangayKonek</div>
        <span class="treasurerhistory-identity-chip">Treasurer Portal</span>
      </div>
    </div>
    <nav class="treasurerhistory-menu">
      <a href="treasurerdashboard.php" class="treasurerhistory-menu-link">
        <div class="treasurerhistory-menu-left"><i class="fa-solid fa-house"></i><span class="treasurerhistory-menu-label">Dashboard</span></div>
      </a>
      <a href="treasurertransactions.php" class="treasurerhistory-menu-link">
        <div class="treasurerhistory-menu-left"><i class="fa-solid fa-coins"></i><span class="treasurerhistory-menu-label">Transactions</span></div>
        <?php if ($pendingPaymentCount > 0): ?><span class="treasurerhistory-menu-count"><?= $pendingPaymentCount ?></span><?php endif; ?>
      </a>
      <a href="treasurerhistory.php" class="treasurerhistory-menu-link active">
        <div class="treasurerhistory-menu-left"><i class="fa-solid fa-clock-rotate-left"></i><span class="treasurerhistory-menu-label">Transaction History</span></div>
      </a>
      <a href="treasurercommunity.php" class="treasurerhistory-menu-link">
        <div class="treasurerhistory-menu-left"><i class="fa-solid fa-people-group"></i><span class="treasurerhistory-menu-label">Community</span></div>
      </a>
      <div class="treasurerhistory-menu-divider"></div>
      <a href="treasurerprofile.php" class="treasurerhistory-menu-link">
        <div class="treasurerhistory-menu-left"><i class="fa-solid fa-gear"></i><span class="treasurerhistory-menu-label">Settings</span></div>
      </a>
    </nav>
    <div class="treasurerhistory-sidebar-footer">
      <button class="treasurerhistory-logout" onclick="openLogoutModal()">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="treasurerhistory-menu-label">Log Out</span>
      </button>
    </div>
  </aside>

  <main class="treasurerhistory-main" id="treasurerhistory-main">
    <header class="treasurerhistory-topbar">
      <div class="treasurerhistory-topbar-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Search residents, transactions, records."/>
      </div>
      <div class="treasurerhistory-topbar-right">
        <a href="treasurertransactions.php" class="treasurerhistory-topbar-icon">
          <i class="fa-solid fa-bell"></i>
          <?php if ($pendingPaymentCount > 0): ?><span class="treasurerhistory-notification-count"><?= $pendingPaymentCount ?></span><?php endif; ?>
        </a>
        <a href="treasurerprofile.php" class="treasurerhistory-profile">
          <div class="treasurerhistory-avatar"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="treasurerhistory-profile-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="treasurerhistory-profile-role">Treasurer</div>
          </div>
        </a>
      </div>
    </header>

    <div class="treasurerhistory-body">
      <section class="treasurerhistory-banner">
        <div class="treasurerhistory-banner-left">
          <h2>Transaction History</h2>
          <p>Review completed payments, waived transactions, and all recorded financial collections for Barangay Alapan I-B.</p>
        </div>
        <div class="treasurerhistory-banner-right">
          <a href="treasurerhistory.php?export=csv" class="treasurerhistory-export-btn">
            <i class="fa-solid fa-file-export"></i> Export CSV
          </a>
          <button class="treasurerhistory-print-btn" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Report
          </button>
        </div>
      </section>

      <section class="treasurerhistory-content">
        <div>
          <div class="treasurerhistory-records-container">
            <div class="treasurerhistory-records-head d-flex align-items-start justify-content-between">
              <div>
                <h3>History Records</h3>
                <p>Review completed and pending transaction records.</p>
              </div>
              <span class="treasurerhistory-records-count"><?= $totalRecords ?> total record<?= $totalRecords !== 1 ? 's' : '' ?></span>
            </div>

            <form method="GET" action="treasurerhistory.php" id="filterForm">
              <div class="treasurerhistory-toolbar">
                <div class="treasurerhistory-toolbar-search">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" name="search" placeholder="Search by resident name..." value="<?= htmlspecialchars($search) ?>"/>
                </div>
                <select name="doc" class="treasurerhistory-doc-select" onchange="document.getElementById('filterForm').submit()">
                  <option value="">All Document Types</option>
                  <?php foreach ($docTypes as $dt): ?>
                  <option value="<?= htmlspecialchars($dt) ?>" <?= $filterDoc === $dt ? 'selected' : '' ?>><?= htmlspecialchars($dt) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="treasurerhistory-toolbar-chips">
                  <?php foreach ([''=>'All','PAID'=>'Paid','WAIVED'=>'Waived','PENDING'=>'Pending'] as $val => $label): ?>
                  <button type="submit" name="status" value="<?= $val ?>" class="treasurerhistory-chip <?= $filterSt === $val ? 'on' : '' ?>"><?= $label ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
            </form>

            <div class="treasurerhistory-table-wrap">
              <table class="treasurerhistory-history-table">
                <thead>
                  <tr>
                    <th>Resident</th>
                    <th>Document Type</th>
                    <th>Amount</th>
                    <th>Date Paid</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($history)): ?>
                  <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">No records found.</td>
                  </tr>
                  <?php else: foreach ($history as $h): ?>
                  <tr>
                    <td>
                      <div class="treasurerhistory-person-cell">
                        <div class="treasurerhistory-person-avatar"><?= htmlspecialchars($h['initials']) ?></div>
                        <span class="treasurerhistory-person-name"><?= htmlspecialchars($h['name']) ?></span>
                      </div>
                    </td>
                    <td><span class="treasurerhistory-doc-type"><?= $h['doc'] ?></span></td>
                    <td>
                      <span class="treasurerhistory-amount <?= $h['st_class'] === 'waived' ? 'waived' : '' ?>">
                        &#8369;<?= number_format($h['amount'], 2) ?>
                      </span>
                    </td>
                    <td><span class="treasurerhistory-date-text"><?= htmlspecialchars($h['date']) ?></span></td>
                    <td><span class="treasurerhistory-method <?= $h['mt_class'] ?>"><?= $h['method'] ?></span></td>
                    <td><span class="treasurerhistory-status <?= $h['st_class'] ?>"><?= $h['status'] ?></span></td>
                    <td>
                      <button class="treasurerhistory-view-btn"
                        onclick="openViewModal(
                          '<?= htmlspecialchars(addslashes($h['name'])) ?>',
                          '<?= htmlspecialchars(addslashes($h['doc'])) ?>',
                          '<?= htmlspecialchars(addslashes($h['code'])) ?>',
                          '&#8369;<?= number_format($h['amount'], 2) ?>',
                          '<?= htmlspecialchars(addslashes($h['date'])) ?>',
                          '<?= htmlspecialchars(addslashes($h['method'])) ?>',
                          '<?= htmlspecialchars(addslashes($h['status'])) ?>'
                        )">View</button>
                    </td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <div class="treasurerhistory-table-foot">
              <span class="treasurerhistory-foot-text">
                Showing <?= $totalRecords > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> records
              </span>
              <?php if ($totalPages > 1): ?>
              <div class="treasurerhistory-pager">
                <a href="?<?= buildQuery(['page' => max(1, $page - 1)]) ?>">
                  <button class="treasurerhistory-pager-btn" <?= $page <= 1 ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-chevron-left"></i>
                  </button>
                </a>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?<?= buildQuery(['page' => $p]) ?>">
                  <button class="treasurerhistory-pager-btn <?= $p === $page ? 'on' : '' ?>"><?= $p ?></button>
                </a>
                <?php endfor; ?>
                <a href="?<?= buildQuery(['page' => min($totalPages, $page + 1)]) ?>">
                  <button class="treasurerhistory-pager-btn" <?= $page >= $totalPages ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-chevron-right"></i>
                  </button>
                </a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <aside class="treasurerhistory-right-col">
          <div class="treasurerhistory-filter-card">
            <div class="treasurerhistory-filter-head">
              <h4>Filter Records</h4>
              <p>Search and narrow down archived transaction records.</p>
            </div>
            <form method="GET" action="treasurerhistory.php">
              <div class="treasurerhistory-filter-group">
                <label class="treasurerhistory-filter-label">Search</label>
                <input type="text" name="search" class="treasurerhistory-filter-input" placeholder="Resident or transaction..." value="<?= htmlspecialchars($search) ?>"/>
              </div>
              <div class="treasurerhistory-filter-group">
                <label class="treasurerhistory-filter-label">Document Type</label>
                <select name="doc" class="treasurerhistory-filter-select">
                  <option value="">All Document Types</option>
                  <?php foreach ($docTypes as $dt): ?>
                  <option value="<?= htmlspecialchars($dt) ?>" <?= $filterDoc === $dt ? 'selected' : '' ?>><?= htmlspecialchars($dt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="treasurerhistory-filter-group">
                <label class="treasurerhistory-filter-label">Date Range</label>
                <div class="treasurerhistory-date-row">
                  <input type="date" name="date_from" class="treasurerhistory-date-field" value="<?= htmlspecialchars($dateFrom) ?>"/>
                  <input type="date" name="date_to"   class="treasurerhistory-date-field" value="<?= htmlspecialchars($dateTo) ?>"/>
                </div>
              </div>
              <div class="treasurerhistory-filter-group">
                <label class="treasurerhistory-filter-label">Status</label>
                <div class="treasurerhistory-filter-chips">
                  <?php foreach ([''=>'All','PAID'=>'Paid','WAIVED'=>'Waived','PENDING'=>'Pending'] as $val => $label): ?>
                  <button type="submit" name="status" value="<?= $val ?>" class="treasurerhistory-filter-chip <?= $filterSt === $val ? 'on' : '' ?>"><?= $label ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="treasurerhistory-filter-actions">
                <button type="submit" class="treasurerhistory-apply-btn">Apply</button>
                <a href="treasurerhistory.php"><button type="button" class="treasurerhistory-clear-btn">Clear</button></a>
              </div>
            </form>
          </div>

          <div class="treasurerhistory-breakdown-card">
            <div class="treasurerhistory-breakdown-head">
              <h4>Income Breakdown</h4>
              <p><?= date('F Y') ?> collection summary</p>
            </div>
            <div class="treasurerhistory-breakdown-body">
              <?php if (empty($breakdown)): ?>
              <p style="font-size:13px;color:var(--muted);">No income data this month.</p>
              <?php else: foreach ($breakdown as $b):
                $pct = $breakdownMax > 0 ? round(($b['amt'] / $breakdownMax) * 100) : 0;
              ?>
              <div class="treasurerhistory-breakdown-row">
                <span class="treasurerhistory-breakdown-label"><?= htmlspecialchars($b['doc']) ?></span>
                <div class="treasurerhistory-breakdown-bar-wrap">
                  <div class="treasurerhistory-breakdown-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="treasurerhistory-breakdown-val">&#8369;<?= number_format($b['amt'], 0) ?></span>
              </div>
              <?php endforeach; endif; ?>
              <div class="treasurerhistory-breakdown-total">
                <span class="treasurerhistory-breakdown-total-label">Total Collected</span>
                <span class="treasurerhistory-breakdown-total-val">&#8369;<?= number_format($totalThisMonth, 2) ?></span>
              </div>
            </div>
          </div>
        </aside>
      </section>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var treasurerhistorySidebar      = document.getElementById("treasurerhistory-sidebar");
var treasurerhistoryMain         = document.getElementById("treasurerhistory-main");
var treasurerhistoryToggleWrap   = document.getElementById("treasurerhistory-toggle-wrap");
var treasurerhistoryToggleButton = document.getElementById("treasurerhistory-toggle-button");
var treasurerhistoryViewModal    = document.getElementById("treasurerhistory-view-modal");
var treasurerhistoryLogoutModal  = document.getElementById("treasurerhistory-logout-modal");

treasurerhistoryToggleButton.addEventListener("click", function () {
  var mini = treasurerhistorySidebar.classList.toggle("mini");
  treasurerhistoryMain.classList.toggle("shifted", mini);
  treasurerhistoryToggleWrap.classList.toggle("mini", mini);
});

function openViewModal(resident, documentType, transactionId, amount, datePaid, paymentMethod, status) {
  document.getElementById("th-modal-resident").textContent  = resident;
  document.getElementById("th-modal-doctype").textContent   = documentType;
  document.getElementById("th-modal-txnid").textContent     = transactionId;
  document.getElementById("th-modal-amount").innerHTML      = amount;
  document.getElementById("th-modal-date").textContent      = datePaid;
  document.getElementById("th-modal-method").textContent    = paymentMethod;
  document.getElementById("th-modal-status").textContent    = status;
  treasurerhistoryViewModal.classList.add("open");
}
function closeViewModal()   { treasurerhistoryViewModal.classList.remove("open"); }
function openLogoutModal()  { treasurerhistoryLogoutModal.classList.add("open"); }
function closeLogoutModal() { treasurerhistoryLogoutModal.classList.remove("open"); }

treasurerhistoryViewModal.addEventListener("click", function(e)   { if (e.target === this) closeViewModal(); });
treasurerhistoryLogoutModal.addEventListener("click", function(e) { if (e.target === this) closeLogoutModal(); });
</script>
</body>
</html>