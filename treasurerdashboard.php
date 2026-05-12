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

/* ── USER INFO ── */
$meRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT R.FIRST_NAME, R.LAST_NAME FROM REGISTRATION R WHERE R.USER_ID = ?", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$firstName   = $meRow ? htmlspecialchars(rtrim($meRow['FIRST_NAME'])) : 'Treasurer';
$lastName    = $meRow ? htmlspecialchars(rtrim($meRow['LAST_NAME']))  : '';
$displayName = trim("$firstName $lastName") ?: 'Treasurer';
$initials    = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

$hour      = (int)date('G');
$greeting  = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$monthNum  = (int)date('m');
$yearNum   = (int)date('Y');
$monthName = date('F Y');

function fmoney($v) {
    $v = (float)$v;
    if ($v >= 1000) return '&#8369;' . number_format($v / 1000, 1) . 'K';
    return '&#8369;' . number_format($v, 0);
}
function fdate_d($val, $fmt = 'M d, Y') {
    if ($val instanceof DateTime) return $val->format($fmt);
    return date($fmt, strtotime($val ?? 'now'));
}

/* ── HANDLE ACTIONS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $act = rtrim($_POST['action']);

    if ($act === 'mark_paid' && !empty($_POST['request_id'])) {
        $rid   = (int)$_POST['request_id'];
        $method = in_array(rtrim($_POST['payment_method'] ?? ''), ['Cash','GCash','Online Payment','Waived']) ? rtrim($_POST['payment_method']) : 'Cash';
        $drRow = sqlsrv_fetch_array(
            sqlsrv_query($conn, "SELECT DR.DOCUMENT_TYPE, DR.USER_ID FROM DOCUMENT_REQUESTS DR WHERE DR.REQUEST_ID = ?", [$rid]),
            SQLSRV_FETCH_ASSOC
        );
        if ($drRow) {
            $docType   = rtrim($drRow['DOCUMENT_TYPE']);
            $resUserId = (int)$drRow['USER_ID'];
            $feeMap    = ['Barangay Clearance'=>100,'Certificate of Residency'=>50,'Certificate of Good Moral'=>100,'Business Permit'=>200];
            $fee       = $feeMap[$docType] ?? 100;
            sqlsrv_query($conn,
                "INSERT INTO PAYMENTS (REQUEST_ID, USER_ID, DOCUMENT_TYPE, PAYMENT_METHOD, AMOUNT, PAYMENT_STATUS, CREATED_AT) VALUES (?, ?, ?, ?, ?, 'PAID', GETDATE())",
                [$rid, $resUserId, $docType, $method, $fee]);
            sqlsrv_query($conn,
                "INSERT INTO NOTIFICATIONS (USER_ID, FROM_USER_ID, TYPE, REFERENCE_ID, MESSAGE, IS_READ, CREATED_AT) VALUES (?, ?, 'PAYMENT_RECEIVED', ?, ?, 0, GETDATE())",
                [$resUserId, $userId, $rid, "Your payment for $docType has been recorded."]);
            sqlsrv_query($conn,
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'MARK_PAID', ?, GETDATE())",
                [$userId, "Marked PAID for Request #$rid ($docType) via $method"]);
        }
        header("Location: treasurerdashboard.php"); exit();
    }

    if ($act === 'mark_waived' && !empty($_POST['request_id'])) {
        $rid   = (int)$_POST['request_id'];
        $drRow = sqlsrv_fetch_array(
            sqlsrv_query($conn, "SELECT DR.DOCUMENT_TYPE, DR.USER_ID FROM DOCUMENT_REQUESTS DR WHERE DR.REQUEST_ID = ?", [$rid]),
            SQLSRV_FETCH_ASSOC
        );
        if ($drRow) {
            $docType   = rtrim($drRow['DOCUMENT_TYPE']);
            $resUserId = (int)$drRow['USER_ID'];
            sqlsrv_query($conn,
                "INSERT INTO PAYMENTS (REQUEST_ID, USER_ID, DOCUMENT_TYPE, PAYMENT_METHOD, AMOUNT, PAYMENT_STATUS, CREATED_AT) VALUES (?, ?, ?, 'Waived', 0, 'WAIVED', GETDATE())",
                [$rid, $resUserId, $docType]);
            sqlsrv_query($conn,
                "INSERT INTO NOTIFICATIONS (USER_ID, FROM_USER_ID, TYPE, REFERENCE_ID, MESSAGE, IS_READ, CREATED_AT) VALUES (?, ?, 'PAYMENT_WAIVED', ?, ?, 0, GETDATE())",
                [$resUserId, $userId, $rid, "Your payment for $docType has been waived."]);
            sqlsrv_query($conn,
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'MARK_WAIVED', ?, GETDATE())",
                [$userId, "Marked WAIVED for Request #$rid ($docType)"]);
        }
        header("Location: treasurerdashboard.php"); exit();
    }

    if ($act === 'read_notif' && !empty($_POST['notif_id'])) {
        $nid = (int)$_POST['notif_id'];
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?", [$nid, $userId]);
        if (!empty($_POST['ajax'])) { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit(); }
        header("Location: treasurerdashboard.php"); exit();
    }

    if ($act === 'mark_all_read') {
        sqlsrv_query($conn, "UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?", [$userId]);
        header("Location: treasurerdashboard.php"); exit();
    }

    if ($act === 'logout') {
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'LOGOUT', 'Treasurer logged out', GETDATE())",
            [$userId]);
        session_destroy();
        header("Location: login.php"); exit();
    }
}

/* ── NOTIFICATIONS ── */
$unreadRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM NOTIFICATIONS WHERE USER_ID = ? AND IS_READ = 0", [$userId]),
    SQLSRV_FETCH_ASSOC
);
$unreadCount = $unreadRow ? (int)$unreadRow['CNT'] : 0;

$notifStmt = sqlsrv_query($conn,
    "SELECT TOP 15 NOTIFICATION_ID, TYPE, MESSAGE, IS_READ, CREATED_AT, REFERENCE_ID
     FROM NOTIFICATIONS WHERE USER_ID = ? ORDER BY CREATED_AT DESC",
    [$userId]);
$notifications = [];
while ($notifStmt && $row = sqlsrv_fetch_array($notifStmt, SQLSRV_FETCH_ASSOC)) {
    $notifications[] = $row;
}

/* ── PENDING: DOCUMENT_REQUESTS approved but no payment yet ── */
$pendingStmt = sqlsrv_query($conn,
    "SELECT DR.REQUEST_ID, DR.DOCUMENT_TYPE, DR.PURPOSE, DR.CREATED_AT,
            R.FIRST_NAME, R.LAST_NAME
     FROM DOCUMENT_REQUESTS DR
     LEFT JOIN REGISTRATION R ON R.USER_ID = DR.USER_ID
     WHERE DR.STATUS = 'APPROVED'
       AND NOT EXISTS (
           SELECT 1 FROM PAYMENTS P
           WHERE P.REQUEST_ID = DR.REQUEST_ID
             AND P.PAYMENT_STATUS IN ('PAID','WAIVED')
       )
     ORDER BY DR.CREATED_AT ASC");
$pendingList = [];
while ($pendingStmt && $row = sqlsrv_fetch_array($pendingStmt, SQLSRV_FETCH_ASSOC)) {
    $fn = rtrim($row['FIRST_NAME'] ?? '');
    $ln = rtrim($row['LAST_NAME']  ?? '');
    $feeMap = ['Barangay Clearance'=>100,'Certificate of Residency'=>50,'Certificate of Good Moral'=>100,'Business Permit'=>200];
    $docType = rtrim($row['DOCUMENT_TYPE']);
    $pendingList[] = [
        'id'       => (int)$row['REQUEST_ID'],
        'code'     => 'TRN-' . str_pad((int)$row['REQUEST_ID'], 4, '0', STR_PAD_LEFT),
        'name'     => trim("$fn $ln") ?: 'Unknown',
        'initials' => strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1)),
        'doc_type' => $docType,
        'purpose'  => htmlspecialchars(rtrim($row['PURPOSE'] ?? '—')),
        'date'     => fdate_d($row['CREATED_AT']),
        'fee'      => $feeMap[$docType] ?? 100,
    ];
}
$pendingPaymentCount = count($pendingList);

/* ── THIS MONTH TOTAL INCOME from PAYMENTS ── */
$incomeRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT ISNULL(SUM(P.AMOUNT), 0) AS TOTAL
         FROM PAYMENTS P
         WHERE P.PAYMENT_STATUS = 'PAID'
           AND MONTH(P.CREATED_AT) = ? AND YEAR(P.CREATED_AT) = ?",
        [$monthNum, $yearNum]),
    SQLSRV_FETCH_ASSOC
);
$totalIncome = $incomeRow ? (float)$incomeRow['TOTAL'] : 0;

/* ── TOTAL PAID TRANSACTIONS THIS MONTH ── */
$issuedRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT FROM PAYMENTS
         WHERE PAYMENT_STATUS = 'PAID' AND MONTH(CREATED_AT) = ? AND YEAR(CREATED_AT) = ?",
        [$monthNum, $yearNum]),
    SQLSRV_FETCH_ASSOC
);
$totalIssued = $issuedRow ? (int)$issuedRow['CNT'] : 0;

/* ── WAIVED COUNT THIS MONTH ── */
$waivedRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT FROM PAYMENTS
         WHERE PAYMENT_STATUS = 'WAIVED' AND MONTH(CREATED_AT) = ? AND YEAR(CREATED_AT) = ?",
        [$monthNum, $yearNum]),
    SQLSRV_FETCH_ASSOC
);
$waivedCount = $waivedRow ? (int)$waivedRow['CNT'] : 0;

/* ── 12-MONTH CHART from PAYMENTS ── */
$months    = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$chartData = [];
for ($m = 1; $m <= 12; $m++) {
    $r = sqlsrv_fetch_array(
        sqlsrv_query($conn,
            "SELECT ISNULL(SUM(AMOUNT),0) AS T FROM PAYMENTS
             WHERE PAYMENT_STATUS='PAID' AND MONTH(CREATED_AT)=? AND YEAR(CREATED_AT)=?",
            [$m, $yearNum]),
        SQLSRV_FETCH_ASSOC
    );
    $chartData[] = ['month' => $months[$m-1], 'value' => $r ? (float)$r['T'] : 0, 'is_current' => ($m === $monthNum)];
}
$maxChartVal = max(array_column($chartData, 'value'));
$chartMax    = $maxChartVal > 0 ? (ceil($maxChartVal / 1000) * 1000) : 5000;

/* ── INCOME BY DOC TYPE THIS MONTH from PAYMENTS ── */
$incomeByTypeStmt = sqlsrv_query($conn,
    "SELECT P.DOCUMENT_TYPE, COUNT(*) AS ISSUED, ISNULL(SUM(P.AMOUNT),0) AS INCOME
     FROM PAYMENTS P
     WHERE P.PAYMENT_STATUS='PAID' AND MONTH(P.CREATED_AT)=? AND YEAR(P.CREATED_AT)=?
     GROUP BY P.DOCUMENT_TYPE ORDER BY INCOME DESC",
    [$monthNum, $yearNum]);
$incomeRows = [];
while ($incomeByTypeStmt && $row = sqlsrv_fetch_array($incomeByTypeStmt, SQLSRV_FETCH_ASSOC)) {
    $incomeRows[] = [
        'document_type' => htmlspecialchars(rtrim($row['DOCUMENT_TYPE'])),
        'issued'        => (int)$row['ISSUED'],
        'income'        => (float)$row['INCOME'],
    ];
}

/* ── RECENT 3 PAID TRANSACTIONS from PAYMENTS ── */
$recentTxStmt = sqlsrv_query($conn,
    "SELECT TOP 3 P.PAYMENT_ID, P.DOCUMENT_TYPE, P.AMOUNT, P.PAYMENT_METHOD, P.CREATED_AT,
                  R.FIRST_NAME, R.LAST_NAME
     FROM PAYMENTS P
     LEFT JOIN REGISTRATION R ON R.USER_ID = P.USER_ID
     WHERE P.PAYMENT_STATUS = 'PAID'
     ORDER BY P.CREATED_AT DESC");
$recentTransactions = [];
while ($recentTxStmt && $row = sqlsrv_fetch_array($recentTxStmt, SQLSRV_FETCH_ASSOC)) {
    $fn = rtrim($row['FIRST_NAME'] ?? '');
    $ln = rtrim($row['LAST_NAME']  ?? '');
    $recentTransactions[] = [
        'code'     => 'TRN-' . str_pad((int)$row['PAYMENT_ID'], 4, '0', STR_PAD_LEFT),
        'name'     => trim("$fn $ln") ?: 'Unknown',
        'initials' => strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1)),
        'doc_type' => htmlspecialchars(rtrim($row['DOCUMENT_TYPE'])),
        'amount'   => (float)$row['AMOUNT'],
        'method'   => htmlspecialchars(rtrim($row['PAYMENT_METHOD'])),
        'date'     => fdate_d($row['CREATED_AT'], 'M d'),
    ];
}

/* ── INCOME SOURCES TOP 3 (sidebar bars) ── */
$srcStmt = sqlsrv_query($conn,
    "SELECT TOP 3 P.DOCUMENT_TYPE, ISNULL(SUM(P.AMOUNT),0) AS INCOME
     FROM PAYMENTS P
     WHERE P.PAYMENT_STATUS='PAID' AND MONTH(P.CREATED_AT)=? AND YEAR(P.CREATED_AT)=?
     GROUP BY P.DOCUMENT_TYPE ORDER BY INCOME DESC",
    [$monthNum, $yearNum]);
$incomeSources = [];
while ($srcStmt && $row = sqlsrv_fetch_array($srcStmt, SQLSRV_FETCH_ASSOC)) {
    $incomeSources[] = ['document_type' => htmlspecialchars(rtrim($row['DOCUMENT_TYPE'])), 'income' => (float)$row['INCOME']];
}
$maxSource = $incomeSources ? max(array_column($incomeSources, 'income')) : 1;

/* ── FILTER CHIPS for table ── */
$docTypeStmt = sqlsrv_query($conn,
    "SELECT DISTINCT DOCUMENT_TYPE FROM PAYMENTS WHERE PAYMENT_STATUS='PAID' ORDER BY DOCUMENT_TYPE");
$docTypeFilters = [];
while ($docTypeStmt && $row = sqlsrv_fetch_array($docTypeStmt, SQLSRV_FETCH_ASSOC)) {
    $docTypeFilters[] = htmlspecialchars(rtrim($row['DOCUMENT_TYPE']));
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Treasurer Dashboard — BarangayKonek</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
<style>
:root{--navy:#051650;--navy-mid:#0a2160;--lime:#ccff00;--surface:#ffffff;--soft-bg:#f8fafc;--border:#e3e7f0;--text:#1a2240;--text-muted:#7a869a;--sidebar-w:228px;--sidebar-mini:64px;--topbar-h:62px;--soft-navy-bg:#edf2ff;--shadow-light:0 6px 18px rgba(5,22,80,0.08);--shadow-hover:0 14px 30px rgba(5,22,80,0.13);--shadow-sidebar:6px 0 18px rgba(5,22,80,0.14);--green-bg:#f0fdf4;--green-text:#166534;--amber-bg:#fffbeb;--amber-text:#92400e;}
*{box-sizing:border-box;}
body{margin:0;font-family:"DM Sans",sans-serif;background:radial-gradient(circle at top left,rgba(204,255,0,0.1),transparent 32%),linear-gradient(135deg,#f8fbff 0%,#eef3fb 100%);color:var(--text);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;}button,input,select{font-family:inherit;}
@keyframes tdFadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
@keyframes tdBellShake{0%,100%{transform:rotate(0deg);}20%{transform:rotate(14deg);}40%{transform:rotate(-12deg);}60%{transform:rotate(8deg);}80%{transform:rotate(-6deg);}}

/* SIDEBAR */
.td-container{display:flex;min-height:100vh;}
.td-sidebar{width:var(--sidebar-w);height:100vh;position:fixed;top:0;left:0;z-index:40;overflow:hidden;display:flex;flex-direction:column;background:radial-gradient(circle at top left,rgba(204,255,0,0.14),transparent 28%),linear-gradient(180deg,#051650 0%,#081d63 56%,#040f3b 100%);box-shadow:var(--shadow-sidebar);transition:width 0.25s ease;}
.td-sidebar.mini{width:var(--sidebar-mini);}
.td-toggle-wrap{position:fixed;top:50%;left:var(--sidebar-w);transform:translate(-50%,-50%);z-index:200;transition:left 0.25s ease;}
.td-toggle-wrap.mini{left:var(--sidebar-mini);}
.td-toggle-btn{width:30px;height:30px;border-radius:999px;background:var(--surface);border:1px solid var(--border);box-shadow:0 5px 12px rgba(5,22,80,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:background 0.18s,border-color 0.18s,transform 0.18s;}
.td-toggle-btn:hover{background:var(--lime);border-color:var(--lime);transform:scale(1.05);}
.td-toggle-btn i{font-size:10px;color:var(--navy);transition:transform 0.25s;}
.td-toggle-wrap.mini .td-toggle-btn i{transform:rotate(180deg);}
.td-identity{height:var(--topbar-h);display:flex;align-items:center;gap:11px;padding:0 15px;border-bottom:1px solid rgba(255,255,255,0.1);white-space:nowrap;overflow:hidden;}
.td-identity-logo{width:38px;height:38px;border-radius:14px;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,0.12);}
.td-identity-logo img{width:100%;height:100%;object-fit:cover;}
.td-identity-name{font-size:14px;font-weight:800;color:#fff;line-height:1.2;}
.td-identity-chip{display:inline-flex;align-items:center;margin-top:5px;padding:4px 10px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:11px;font-weight:800;line-height:1;}
.td-sidebar.mini .td-identity-name,.td-sidebar.mini .td-identity-chip{opacity:0;width:0;pointer-events:none;}
.td-sidebar.mini .td-identity-logo{margin:0 auto;}
.td-menu{flex:1;padding:16px 10px;display:flex;flex-direction:column;gap:5px;overflow-y:auto;overflow-x:hidden;}
.td-menu-divider{height:1px;background:rgba(255,255,255,0.08);margin:8px;flex-shrink:0;}
.td-menu-link{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.66);cursor:pointer;white-space:nowrap;overflow:hidden;flex-shrink:0;text-decoration:none;transition:background 0.18s,color 0.18s,transform 0.18s;}
.td-menu-link:hover{background:rgba(255,255,255,0.09);color:#fff;transform:translateX(3px);}
.td-menu-link.active{background:var(--lime);color:var(--navy);}
.td-menu-left{display:flex;align-items:center;gap:10px;min-width:0;}
.td-menu-left i{width:18px;text-align:center;font-size:13px;flex-shrink:0;}
.td-menu-label{overflow:hidden;text-overflow:ellipsis;}
.td-sidebar.mini .td-menu-label{opacity:0;width:0;pointer-events:none;}
.td-menu-count{font-size:10px;font-weight:800;background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);min-width:22px;height:22px;padding:0 6px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.td-menu-link.active .td-menu-count{background:rgba(5,22,80,0.18);color:var(--navy);}
.td-sidebar.mini .td-menu-count{opacity:0;pointer-events:none;}
.td-sidebar-footer{padding:13px 10px;border-top:1px solid rgba(255,255,255,0.08);}
.td-logout{display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.46);background:transparent;border:none;cursor:pointer;width:100%;text-align:left;white-space:nowrap;transition:background 0.18s,color 0.18s,transform 0.18s;}
.td-logout:hover{background:rgba(239,68,68,0.14);color:#fca5a5;transform:translateX(3px);}

/* MAIN */
.td-main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;min-width:0;transition:margin-left 0.25s;}
.td-main.shifted{margin-left:var(--sidebar-mini);}

/* TOPBAR */
.td-topbar{height:var(--topbar-h);background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;padding:0 24px;position:sticky;top:0;z-index:30;}
.td-topbar-search{flex:1;max-width:390px;height:38px;display:flex;align-items:center;gap:8px;padding:0 14px;background:var(--soft-bg);border:1px solid var(--border);border-radius:999px;transition:background 0.18s,border-color 0.18s,box-shadow 0.18s;}
.td-topbar-search:focus-within{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.td-topbar-search i{color:var(--text-muted);font-size:12px;}
.td-topbar-search input{flex:1;border:none;outline:none;background:transparent;font-size:13px;color:var(--text);}
.td-topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}

/* NOTIFICATION BELL */
.td-bell-wrap{position:relative;}
.td-bell-btn{width:38px;height:38px;border-radius:999px;background:var(--soft-bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;cursor:pointer;position:relative;transition:border-color 0.18s,color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-bell-btn:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.td-bell-btn:hover i{animation:tdBellShake 0.55s ease;}
.td-bell-count{position:absolute;top:-6px;right:-5px;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
.td-notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:340px;background:#fff;border:1px solid var(--border);border-radius:20px;box-shadow:0 16px 40px rgba(5,22,80,0.16);z-index:1000;display:none;overflow:hidden;animation:tdFadeUp 0.2s ease both;}
.td-notif-dropdown.open{display:block;}
.td-notif-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px 12px;border-bottom:1px solid var(--border);}
.td-notif-head h4{font-size:14px;font-weight:800;color:var(--navy);margin:0;}
.td-notif-mark-all{font-size:12px;font-weight:700;color:var(--navy);background:none;border:none;cursor:pointer;padding:4px 10px;border-radius:8px;transition:background 0.15s;}
.td-notif-mark-all:hover{background:var(--soft-bg);}
.td-notif-list{max-height:340px;overflow-y:auto;}
.td-notif-empty{padding:32px 20px;text-align:center;color:var(--text-muted);font-size:13px;}
.td-notif-item{display:flex;align-items:flex-start;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border);cursor:pointer;background:transparent;border-left:none;border-right:none;width:100%;text-align:left;font-family:inherit;transition:background 0.15s;}
.td-notif-item:hover{background:var(--soft-bg);}
.td-notif-item.unread{background:#f0f6ff;}
.td-notif-item.unread:hover{background:#e4eeff;}
.td-notif-icon{width:36px;height:36px;border-radius:999px;background:var(--soft-navy-bg);color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
.td-notif-icon.payment{background:#dcfce7;color:#15803d;}
.td-notif-icon.warn{background:#fef9c3;color:#a16207;}
.td-notif-msg{font-size:12.5px;color:var(--text);font-weight:600;line-height:1.4;flex:1;}
.td-notif-time{font-size:11px;color:var(--text-muted);margin-top:3px;}
.td-notif-dot{width:8px;height:8px;border-radius:50%;background:var(--lime);flex-shrink:0;margin-top:4px;}

.td-profile{display:flex;align-items:center;gap:8px;padding:5px 13px 5px 5px;border:1px solid var(--border);border-radius:999px;background:var(--soft-bg);cursor:pointer;text-decoration:none;transition:border-color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-profile:hover{border-color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.td-avatar{width:30px;height:30px;border-radius:999px;background:var(--navy);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--lime);flex-shrink:0;}
.td-profile-name{font-size:13px;font-weight:700;color:var(--navy);white-space:nowrap;}
.td-profile-role{font-size:11px;color:var(--text-muted);line-height:1;}

/* BODY */
.td-body{padding:24px 26px 44px;}

/* HERO */
.td-hero{background:linear-gradient(135deg,rgba(5,22,80,0.98),rgba(10,33,96,0.95)),radial-gradient(circle at top right,rgba(204,255,0,0.24),transparent 35%);border-radius:28px;padding:30px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:22px;animation:tdFadeUp 0.28s ease both;position:relative;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;}
.td-hero:hover{transform:translateY(-3px);box-shadow:var(--shadow-hover);}
.td-hero::before{content:'';position:absolute;width:210px;height:210px;right:-90px;top:-110px;background:rgba(204,255,0,0.12);border-radius:50%;}
.td-hero-text,.td-hero-actions{position:relative;z-index:1;}
.td-hero-text h2{font-size:22px;font-weight:800;color:#fff;line-height:1.25;margin-bottom:0;}
.td-hero-text p{font-size:13px;color:rgba(255,255,255,0.68);margin-top:7px;max-width:560px;margin-bottom:0;}
.td-hero-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}

/* BUTTONS */
.td-btn{min-height:38px;padding:0 14px;border-radius:999px;font-size:12px;font-weight:800;border:none;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:background 0.18s,color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-btn:hover{transform:translateY(-1px);}
.td-btn-lime{background:var(--lime);color:var(--navy);}
.td-btn-lime:hover{background:#b7e900;color:var(--navy);}
.td-btn-light{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.18);}
.td-btn-light:hover{background:rgba(255,255,255,0.18);color:#fff;}
.td-btn-navy{background:var(--navy);color:#fff;}
.td-btn-navy:hover{background:var(--lime);color:var(--navy);}
.td-btn-sm{min-height:30px;padding:0 12px;font-size:11px;}
.td-btn-danger{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
.td-btn-danger:hover{background:#991b1b;border-color:#991b1b;color:#fff;}

/* PANELS */
.td-panel,.td-side-panel,.td-tx-card{background:#fff;border:1px solid var(--border);border-radius:28px;box-shadow:var(--shadow-light);animation:tdFadeUp 0.28s ease both;transition:box-shadow 0.2s,transform 0.2s,border-color 0.2s;}
.td-panel:hover,.td-side-panel:hover,.td-tx-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-3px);border-color:#d7deea;}
.td-panel{overflow:hidden;}
.td-panel-head{padding:22px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:14px;}
.td-panel-head h3{font-size:18px;font-weight:800;color:var(--navy);margin-bottom:4px;}
.td-panel-head p{font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:0;}
.td-panel-link{min-height:38px;padding:0 15px;border-radius:999px;background:var(--soft-bg);border:1px solid var(--border);color:var(--navy);font-size:12px;font-weight:800;display:flex;align-items:center;white-space:nowrap;transition:background 0.18s,border-color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-panel-link:hover{background:#fff;border-color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.06);}

/* CHART */
.td-chart-area{padding:22px 24px 24px;}
.td-chart-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px;}
.td-chart-total{font-size:28px;font-weight:800;color:var(--navy);line-height:1;}
.td-chart-sub{font-size:12px;font-weight:700;color:var(--text-muted);margin-top:6px;}
.td-chart-chip{min-height:34px;padding:0 13px;border-radius:999px;background:var(--soft-navy-bg);color:var(--navy);border:1px solid var(--border);font-size:12px;font-weight:800;display:flex;align-items:center;white-space:nowrap;}
.td-chart-board{background:linear-gradient(180deg,#fbfcff 0%,#f7f9fd 100%);border:1px solid var(--border);border-radius:24px;padding:18px 18px 14px;}
.td-chart-grid{display:grid;grid-template-columns:52px 1fr;gap:12px;min-height:250px;}
.td-chart-y{display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;padding-top:2px;padding-bottom:34px;}
.td-chart-y span{font-size:11px;font-weight:700;color:var(--text-muted);}
.td-chart-plot{position:relative;display:flex;flex-direction:column;justify-content:flex-end;min-width:0;}
.td-chart-lines{position:absolute;inset:0 0 34px 0;display:flex;flex-direction:column;justify-content:space-between;pointer-events:none;}
.td-chart-lines span{display:block;width:100%;border-top:1px dashed #d8dfec;}
.td-chart-bars{height:210px;position:relative;z-index:1;display:flex;align-items:flex-end;justify-content:space-between;gap:18px;padding:10px 10px 0;}
.td-chart-group{flex:1;min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:10px;}
.td-chart-value{font-size:11px;font-weight:800;color:var(--navy);white-space:nowrap;opacity:0.9;}
.td-chart-bar-wrap{width:100%;display:flex;justify-content:center;align-items:flex-end;height:150px;}
.td-chart-bar{width:42px;max-width:100%;border-radius:18px 18px 10px 10px;background:linear-gradient(180deg,#17338a 0%,#051650 100%);box-shadow:inset 0 1px 0 rgba(255,255,255,0.18);position:relative;cursor:pointer;transition:transform 0.18s,filter 0.18s,box-shadow 0.18s;}
.td-chart-bar::after{content:'';position:absolute;left:50%;top:8px;transform:translateX(-50%);width:60%;height:4px;border-radius:999px;background:rgba(255,255,255,0.22);}
.td-chart-bar:hover{transform:translateY(-5px);filter:brightness(1.08);box-shadow:0 10px 22px rgba(5,22,80,0.14);}
.td-chart-bar.current{background:linear-gradient(180deg,#ccff00 0%,#dff76b 100%);border:2px solid var(--navy);}
.td-chart-bar.current::after{background:rgba(5,22,80,0.18);}
.td-chart-month{font-size:11px;font-weight:800;color:var(--text-muted);white-space:nowrap;}
.td-chart-empty{padding:40px;text-align:center;color:var(--text-muted);font-size:13px;font-weight:600;}

/* INCOME TABLE */
.td-filter-bar{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.td-chip{min-height:38px;padding:0 14px;border-radius:999px;border:1px solid var(--border);background:var(--soft-bg);color:var(--text-muted);font-size:12px;font-weight:800;cursor:pointer;transition:background 0.18s,color 0.18s,border-color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-chip:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);}
.td-chip.on{background:var(--navy);border-color:var(--navy);color:var(--lime);}
.td-table-wrap{overflow-x:auto;}
.td-table{width:100%;border-collapse:collapse;font-size:13px;}
.td-table thead th{background:#fafbfd;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);padding:12px 16px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--border);}
.td-table tbody tr{border-bottom:1px solid var(--border);transition:background 0.18s,box-shadow 0.18s;}
.td-table tbody tr:hover{background:#fbfdff;box-shadow:inset 4px 0 0 var(--lime);}
.td-table td{padding:13px 16px;vertical-align:middle;}
.td-doc-name{font-size:13px;font-weight:800;color:var(--navy);}
.td-money{font-size:13px;font-weight:800;color:var(--navy);}
.td-progress-wrap{display:flex;align-items:center;gap:8px;min-width:120px;}
.td-progress-track{height:8px;flex:1;background:#eaf0f7;border-radius:999px;overflow:hidden;}
.td-progress-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--navy),#254ca8);}
.td-progress-text{font-size:11px;font-weight:800;color:var(--text-muted);min-width:34px;text-align:right;}
.td-table-foot{padding:15px 22px 18px;border-top:1px solid var(--border);background:#fafbfd;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.td-foot-info{font-size:12px;color:var(--text-muted);font-weight:600;}
.td-foot-total{font-size:13px;font-weight:800;color:var(--navy);}

/* TO-DO LIST */
.td-todo-list{display:flex;flex-direction:column;gap:0;}
.td-todo-item{display:grid;grid-template-columns:1.6fr 2fr 0.8fr 0.9fr 1.4fr;gap:12px;padding:14px 22px;border-bottom:1px solid var(--border);align-items:center;transition:background 0.18s,box-shadow 0.18s;}
.td-todo-item:last-child{border-bottom:none;}
.td-todo-item:hover{background:#fbfdff;box-shadow:inset 4px 0 0 var(--lime);}
.td-todo-head{display:grid;grid-template-columns:1.6fr 2fr 0.8fr 0.9fr 1.4fr;gap:12px;padding:11px 22px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);background:#fafbfd;border-bottom:1px solid var(--border);}
.td-todo-person{font-size:13px;font-weight:800;color:var(--navy);}
.td-todo-sub{font-size:11px;color:var(--text-muted);margin-top:2px;}
.td-todo-doc{font-size:13px;font-weight:700;color:var(--text);}
.td-todo-note{font-size:11px;color:var(--text-muted);margin-top:2px;}
.td-todo-date{font-size:12px;font-weight:700;color:var(--text-muted);}
.td-todo-amt{font-size:14px;font-weight:800;color:var(--navy);}
.td-todo-actions{display:flex;gap:6px;flex-wrap:wrap;}
.td-badge-pending{display:inline-flex;align-items:center;height:24px;padding:0 10px;border-radius:999px;font-size:11px;font-weight:800;background:var(--amber-bg);color:var(--amber-text);}

/* RECENT TX CARDS */
.td-tx-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:18px;}
.td-tx-card{padding:16px;}
.td-tx-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;}
.td-tx-code{font-size:12px;font-weight:800;color:var(--navy);}
.td-tx-date{font-size:11px;font-weight:700;color:var(--text-muted);}
.td-tx-name{font-size:14px;font-weight:800;color:var(--text);margin-bottom:5px;}
.td-tx-doc{font-size:12px;color:var(--text-muted);margin-bottom:12px;}
.td-tx-amount{display:inline-flex;align-items:center;min-height:30px;padding:0 12px;border-radius:999px;background:var(--soft-navy-bg);color:var(--navy);font-size:12px;font-weight:800;}

/* SIDE PANELS */
.td-side-stack{display:flex;flex-direction:column;gap:16px;}
.td-side-panel{padding:20px;}
.td-side-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px;}
.td-side-head h3{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:0;}
.td-side-head span{font-size:12px;color:var(--text-muted);font-weight:700;}
.td-sum-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
.td-sum-card{border:1px solid var(--border);border-radius:22px;background:#fff;padding:14px;transition:box-shadow 0.2s,transform 0.2s,border-color 0.2s;}
.td-sum-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-3px);border-color:#d7deea;}
.td-sum-label{font-size:11px;font-weight:800;color:var(--text-muted);line-height:1.35;}
.td-sum-value{font-size:20px;font-weight:800;color:var(--navy);line-height:1;margin:6px 0;letter-spacing:-0.4px;}
.td-sum-note{font-size:11px;color:var(--text-muted);font-weight:600;}
.td-src-list{display:flex;flex-direction:column;gap:12px;}
.td-src-row{display:flex;flex-direction:column;gap:6px;transition:transform 0.18s;}
.td-src-row:hover{transform:translateX(3px);}
.td-src-top{display:flex;align-items:center;justify-content:space-between;gap:12px;}
.td-src-name{font-size:12.5px;font-weight:700;color:var(--text);}
.td-src-amt{font-size:12px;font-weight:800;color:var(--navy);}
.td-bar-track{height:8px;background:#eaf0f7;border-radius:999px;overflow:hidden;}
.td-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--navy),#254ca8);}
.td-note-box{padding:14px;border-radius:20px;background:var(--soft-bg);border:1px solid var(--border);transition:background 0.18s,border-color 0.18s,transform 0.18s,box-shadow 0.18s;}
.td-note-box:hover{background:#fff;border-color:#d7deea;transform:translateY(-2px);box-shadow:0 10px 22px rgba(5,22,80,0.08);}
.td-note-icon{width:42px;height:42px;border-radius:17px;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;margin-bottom:10px;}
.td-note-title{font-size:13px;font-weight:800;color:var(--navy);margin-bottom:4px;}
.td-note-text{font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:0;}

/* RECORD MODAL */
.td-modal-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,0.65);display:none;align-items:center;justify-content:center;padding:20px;}
.td-modal-overlay.open{display:flex;}
.td-modal-box{background:#fff;border-radius:28px;width:100%;max-width:460px;box-shadow:0 24px 80px rgba(5,22,80,0.26);overflow:hidden;}
.td-modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border);}
.td-modal-head h3{font-size:16px;font-weight:800;color:var(--navy);margin:0;}
.td-modal-close{width:32px;height:32px;border-radius:12px;border:1px solid var(--border);background:var(--soft-bg);color:var(--text-muted);font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.td-modal-body{padding:22px 24px;}
.td-modal-field-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);margin-bottom:6px;}
.td-modal-field-val{background:var(--soft-bg);border:1px solid var(--border);border-radius:14px;padding:11px 14px;font-size:13px;font-weight:700;color:var(--text);min-height:42px;display:flex;align-items:center;}
.td-modal-select{width:100%;height:42px;padding:0 14px;border:1px solid var(--border);border-radius:14px;font-size:13px;color:var(--text);background:var(--soft-bg);outline:none;font-family:inherit;}
.td-modal-select:focus{border-color:var(--navy);background:#fff;}
.td-modal-foot{display:flex;gap:8px;justify-content:flex-end;padding:16px 24px 20px;border-top:1px solid var(--border);}

/* LOGOUT MODAL */
.td-logout-overlay{position:fixed;inset:0;z-index:3000;background:rgba(5,22,80,0.65);display:none;align-items:center;justify-content:center;}
.td-logout-overlay.open{display:flex;}
.td-logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,0.28);}
.td-logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px;}
.td-logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px;}
.td-logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6;}
.td-logout-btns{display:flex;gap:10px;justify-content:center;}
.td-lo-confirm{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;}
.td-lo-cancel{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,0.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}

@media(max-width:992px){.td-tx-list{grid-template-columns:1fr;}.td-todo-head,.td-todo-item{grid-template-columns:1fr 1fr;}.td-sum-grid{grid-template-columns:1fr 1fr;}.td-side-stack{margin-top:16px;}.td-hero{flex-direction:column;align-items:flex-start;}.td-chart-bars{gap:12px;}.td-chart-bar{width:34px;}}
@media(max-width:760px){.td-sidebar{width:var(--sidebar-mini);}.td-main{margin-left:var(--sidebar-mini);}.td-toggle-wrap{left:var(--sidebar-mini);}.td-identity-name,.td-identity-chip,.td-menu-label,.td-menu-count{opacity:0;width:0;pointer-events:none;}.td-body{padding:18px 14px 36px;}.td-topbar{padding:0 14px;}.td-profile-name,.td-profile-role{display:none;}.td-topbar-search{max-width:none;}.td-todo-head,.td-todo-item{grid-template-columns:1fr;}.td-chart-bar{width:28px;}.td-notif-dropdown{width:280px;right:-60px;}}
</style>
</head>
<body>

<!-- LOGOUT MODAL -->
<div class="td-logout-overlay" id="logoutModal">
  <div class="td-logout-box">
    <div class="td-logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>You will be returned to the login page.</p>
    <div class="td-logout-btns">
      <button class="td-lo-cancel" onclick="closeLogout()">Cancel</button>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="logout"/>
        <button type="submit" class="td-lo-confirm"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>

<!-- RECORD PAYMENT MODAL -->
<div class="td-modal-overlay" id="recordModal">
  <div class="td-modal-box">
    <div class="td-modal-head">
      <h3>Record Payment</h3>
      <button type="button" class="td-modal-close" onclick="closeRecordModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="mark_paid"/>
      <input type="hidden" name="request_id" id="modal-request-id"/>
      <div class="td-modal-body">
        <div class="row g-3">
          <div class="col-6">
            <div class="td-modal-field-label">Resident</div>
            <div class="td-modal-field-val" id="modal-resident">—</div>
          </div>
          <div class="col-6">
            <div class="td-modal-field-label">Transaction ID</div>
            <div class="td-modal-field-val" id="modal-code">—</div>
          </div>
          <div class="col-12">
            <div class="td-modal-field-label">Document</div>
            <div class="td-modal-field-val" id="modal-doc">—</div>
          </div>
          <div class="col-6">
            <div class="td-modal-field-label">Amount</div>
            <div class="td-modal-field-val" id="modal-amount">—</div>
          </div>
          <div class="col-6">
            <div class="td-modal-field-label">Payment Method</div>
            <select name="payment_method" class="td-modal-select">
              <option>Cash</option>
              <option>GCash</option>
              <option>Online Payment</option>
            </select>
          </div>
        </div>
      </div>
      <div class="td-modal-foot">
        <button type="button" class="td-btn td-btn-sm" style="background:var(--soft-bg);color:var(--text-muted);border:1px solid var(--border);" onclick="closeRecordModal()">Cancel</button>
        <button type="submit" class="td-btn td-btn-navy td-btn-sm"><i class="fa-solid fa-check"></i> Mark as Paid</button>
      </div>
    </form>
    <div style="padding:0 24px 16px;">
      <form method="POST" id="waivedForm">
        <input type="hidden" name="action" value="mark_waived"/>
        <input type="hidden" name="request_id" id="modal-waived-id"/>
        <button type="submit" class="td-btn td-btn-sm td-btn-danger" style="width:100%;">
          <i class="fa-solid fa-ban"></i> Mark as Waived (No Payment)
        </button>
      </form>
    </div>
  </div>
</div>

<div class="td-toggle-wrap" id="td-toggle-wrap">
  <button class="td-toggle-btn" id="td-toggle-button" title="Collapse / Expand">
    <i class="fa-solid fa-chevron-left"></i>
  </button>
</div>

<div class="td-container">
  <aside class="td-sidebar" id="td-sidebar">
    <div class="td-identity">
      <div class="td-identity-logo"><img src="alapan.png" alt="Alapan"/></div>
      <div>
        <div class="td-identity-name">BarangayKonek</div>
        <span class="td-identity-chip">Treasurer Portal</span>
      </div>
    </div>
    <nav class="td-menu">
      <a href="treasurerdashboard.php" class="td-menu-link active">
        <div class="td-menu-left"><i class="fa-solid fa-house"></i><span class="td-menu-label">Dashboard</span></div>
      </a>
      <a href="treasurertransactions.php" class="td-menu-link">
        <div class="td-menu-left"><i class="fa-solid fa-coins"></i><span class="td-menu-label">Transactions</span></div>
        <?php if ($pendingPaymentCount > 0): ?><span class="td-menu-count"><?= $pendingPaymentCount ?></span><?php endif; ?>
      </a>
      <a href="treasurerhistory.php" class="td-menu-link">
        <div class="td-menu-left"><i class="fa-solid fa-clock-rotate-left"></i><span class="td-menu-label">Transaction History</span></div>
      </a>
      <a href="treasurercommunity.php" class="td-menu-link">
        <div class="td-menu-left"><i class="fa-solid fa-people-group"></i><span class="td-menu-label">Community</span></div>
      </a>
      <div class="td-menu-divider"></div>
      <a href="treasurerprofile.php" class="td-menu-link">
        <div class="td-menu-left"><i class="fa-solid fa-gear"></i><span class="td-menu-label">Settings</span></div>
      </a>
    </nav>
    <div class="td-sidebar-footer">
      <button type="button" class="td-logout" onclick="openLogout()">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="td-menu-label">Log Out</span>
      </button>
    </div>
  </aside>

  <main class="td-main" id="td-main">
    <header class="td-topbar">
      <div class="td-topbar-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Search residents, transactions, records..."/>
      </div>
      <div class="td-topbar-right">

        <!-- NOTIFICATION BELL -->
        <div class="td-bell-wrap">
          <button type="button" class="td-bell-btn" id="bellBtn" onclick="toggleNotif()">
            <i class="fa-regular fa-bell"></i>
            <?php if ($unreadCount > 0): ?><span class="td-bell-count"><?= $unreadCount ?></span><?php endif; ?>
          </button>
          <div class="td-notif-dropdown" id="notifDropdown">
            <div class="td-notif-head">
              <h4>Notifications<?php if ($unreadCount > 0): ?> <span style="font-size:11px;color:#888;font-weight:400;">(<?= $unreadCount ?> unread)</span><?php endif; ?></h4>
              <?php if ($unreadCount > 0): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="mark_all_read"/>
                <button type="submit" class="td-notif-mark-all">Mark all read</button>
              </form>
              <?php endif; ?>
            </div>
            <div class="td-notif-list">
              <?php if (empty($notifications)): ?>
              <div class="td-notif-empty"><i class="fa-regular fa-bell" style="font-size:28px;display:block;margin-bottom:8px;"></i>No notifications yet.</div>
              <?php else: foreach ($notifications as $notif):
                $isUnread = !(bool)$notif['IS_READ'];
                $nid      = (int)$notif['NOTIFICATION_ID'];
                $typeKey  = rtrim($notif['TYPE']);
                $timeAgo  = $notif['CREATED_AT'] instanceof DateTime ? $notif['CREATED_AT']->format('M d, g:i A') : date('M d, g:i A', strtotime($notif['CREATED_AT']));
                $iconClass = match($typeKey) {
                    'PAYMENT_RECEIVED','PAYMENT_WAIVED' => 'fa-coins payment',
                    'DOCUMENT_APPROVED','DOCUMENT_REJECTED' => 'fa-file-circle-check',
                    'NEW_REQUEST'  => 'fa-file-plus warn',
                    default        => 'fa-bell',
                };
                $parts = explode(' ', $iconClass);
                $iconFa    = $parts[0];
                $iconExtra = $parts[1] ?? '';
              ?>
              <button type="button" class="td-notif-item <?= $isUnread ? 'unread' : '' ?>"
                onclick="handleNotifClick(<?= $nid ?>, this)">
                <div class="td-notif-icon <?= $iconExtra ?>"><i class="fa-solid <?= $iconFa ?>"></i></div>
                <div style="flex:1;min-width:0;">
                  <div class="td-notif-msg"><?= htmlspecialchars(rtrim($notif['MESSAGE'])) ?></div>
                  <div class="td-notif-time"><?= $timeAgo ?></div>
                </div>
                <?php if ($isUnread): ?><div class="td-notif-dot"></div><?php endif; ?>
              </button>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>

        <a href="treasurerprofile.php" class="td-profile">
          <div class="td-avatar"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="td-profile-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="td-profile-role">Treasurer</div>
          </div>
        </a>
      </div>
    </header>

    <div class="td-body">
      <section class="td-hero">
        <div class="td-hero-text">
          <h2><?= $greeting ?>, Treasurer <?= htmlspecialchars($firstName) ?></h2>
          <p>Monitor income, review pending payment requests, and prepare monthly financial summaries.</p>
        </div>
        <div class="td-hero-actions">
          <a href="treasurertransactions.php" class="td-btn td-btn-lime"><i class="fa-solid fa-coins"></i> View Transactions</a>
          <a href="treasurerhistory.php" class="td-btn td-btn-light"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        </div>
      </section>

      <div class="row g-3">
        <div class="col-lg-8 col-xl-9">

          <!-- MONTHLY INCOME CHART -->
          <section class="td-panel">
            <div class="td-panel-head">
              <div>
                <h3>Monthly Income</h3>
                <p>Collected income from paid document transactions (<?= date('Y') ?>).</p>
              </div>
              <a href="treasurerhistory.php" class="td-panel-link">View History</a>
            </div>
            <div class="td-chart-area">
              <div class="td-chart-top">
                <div>
                  <div class="td-chart-total"><?= fmoney($totalIncome) ?></div>
                  <div class="td-chart-sub"><?= htmlspecialchars($monthName) ?> collected income</div>
                </div>
                <div class="td-chart-chip">Current month</div>
              </div>
              <?php if ($maxChartVal === 0): ?>
              <div class="td-chart-empty">
                <i class="fa-solid fa-chart-bar" style="font-size:32px;display:block;margin-bottom:12px;opacity:0.3;"></i>
                No paid transactions yet this year.<br/>Income will appear here once payments are recorded.
              </div>
              <?php else: ?>
              <div class="td-chart-board">
                <div class="td-chart-grid">
                  <div class="td-chart-y">
                    <?php
                    $yTop  = $chartMax; $yMid2 = round($chartMax * 0.66); $yMid1 = round($chartMax * 0.33);
                    foreach ([$yTop,$yMid2,$yMid1,0] as $yv):
                        $lbl = $yv >= 1000 ? '&#8369;'.round($yv/1000).'k' : '&#8369;0';
                    ?>
                    <span><?= $lbl ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div class="td-chart-plot">
                    <div class="td-chart-lines"><span></span><span></span><span></span><span></span></div>
                    <div class="td-chart-bars">
                      <?php foreach ($chartData as $cd):
                        $barH    = $chartMax > 0 ? max(4, round(($cd['value'] / $chartMax) * 150)) : 4;
                        $dispVal = $cd['value'] >= 1000
                            ? '&#8369;'.number_format($cd['value']/1000,1).'k'
                            : ($cd['value'] > 0 ? '&#8369;'.number_format($cd['value'],0) : '—');
                      ?>
                      <div class="td-chart-group">
                        <div class="td-chart-value"><?= $dispVal ?></div>
                        <div class="td-chart-bar-wrap">
                          <div class="td-chart-bar <?= $cd['is_current'] ? 'current' : '' ?>" style="height:<?= $barH ?>px"></div>
                        </div>
                        <div class="td-chart-month"><?= $cd['month'] ?></div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </section>

          <!-- INCOME BY DOCUMENT TYPE TABLE -->
          <?php if (!empty($incomeRows)): ?>
          <section class="td-panel mt-3">
            <div class="td-panel-head">
              <div>
                <h3>Income by Document Type</h3>
                <p>Summary of paid transactions for <?= htmlspecialchars($monthName) ?>.</p>
              </div>
              <a href="treasurerhistory.php?export=csv" class="td-btn td-btn-navy td-btn-sm"><i class="fa-solid fa-file-export"></i> Export</a>
            </div>
            <div class="td-filter-bar">
              <button class="td-chip on" data-filter="all">All</button>
              <?php foreach ($docTypeFilters as $dtf): ?>
              <button class="td-chip" data-filter="<?= htmlspecialchars($dtf) ?>"><?= htmlspecialchars($dtf) ?></button>
              <?php endforeach; ?>
            </div>
            <div class="td-table-wrap">
              <table class="td-table" id="incomeTable">
                <thead><tr><th>Document Type</th><th>Issued</th><th>Income</th><th>Share</th></tr></thead>
                <tbody>
                  <?php $totalInc = array_sum(array_column($incomeRows,'income')); ?>
                  <?php foreach ($incomeRows as $ir):
                    $share = $totalInc > 0 ? round(($ir['income']/$totalInc)*100) : 0;
                  ?>
                  <tr data-type="<?= htmlspecialchars($ir['document_type']) ?>">
                    <td><span class="td-doc-name"><?= $ir['document_type'] ?></span></td>
                    <td><?= (int)$ir['issued'] ?></td>
                    <td><span class="td-money">&#8369;<?= number_format($ir['income'],2) ?></span></td>
                    <td>
                      <div class="td-progress-wrap">
                        <div class="td-progress-track"><div class="td-progress-fill" style="width:<?= $share ?>%"></div></div>
                        <span class="td-progress-text"><?= $share ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="td-table-foot">
              <span class="td-foot-info"><?= count($incomeRows) ?> document type<?= count($incomeRows)!==1?'s':'' ?></span>
              <span class="td-foot-total">Total: &#8369;<?= number_format($totalInc,2) ?></span>
            </div>
          </section>
          <?php endif; ?>

          <!-- TO-DO LIST: PENDING PAYMENTS -->
          <section class="td-panel mt-3">
            <div class="td-panel-head">
              <div>
                <h3>Pending Payment Queue</h3>
                <p>Approved requests awaiting payment collection.</p>
              </div>
              <a href="treasurertransactions.php" class="td-panel-link">View All</a>
            </div>
            <?php if (empty($pendingList)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted);font-size:14px;">
              <i class="fa-solid fa-circle-check" style="font-size:32px;display:block;margin-bottom:12px;color:#22c55e;opacity:0.7;"></i>
              All pending transactions have been settled. Great job!
            </div>
            <?php else: ?>
            <div class="td-todo-head">
              <span>Resident</span>
              <span>Document</span>
              <span>Date</span>
              <span>Amount</span>
              <span>Action</span>
            </div>
            <div class="td-todo-list">
              <?php foreach (array_slice($pendingList, 0, 5) as $p): ?>
              <div class="td-todo-item">
                <div>
                  <div class="td-todo-person"><?= htmlspecialchars($p['name']) ?></div>
                  <div class="td-todo-sub"><?= htmlspecialchars($p['code']) ?></div>
                </div>
                <div>
                  <div class="td-todo-doc"><?= htmlspecialchars($p['doc_type']) ?></div>
                  <div class="td-todo-note"><?= $p['purpose'] ?></div>
                </div>
                <div class="td-todo-date"><?= htmlspecialchars($p['date']) ?></div>
                <div class="td-todo-amt">&#8369;<?= number_format($p['fee'],0) ?></div>
                <div class="td-todo-actions">
                  <button type="button" class="td-btn td-btn-navy td-btn-sm"
                    onclick="openRecordModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= htmlspecialchars(addslashes($p['code'])) ?>', '<?= htmlspecialchars(addslashes($p['doc_type'])) ?>', '&#8369;<?= number_format($p['fee'],0) ?>')">
                    Record
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($pendingList) > 5): ?>
            <div style="padding:14px 22px;border-top:1px solid var(--border);background:#fafbfd;font-size:12px;color:var(--text-muted);">
              Showing 5 of <?= count($pendingList) ?> pending transactions.
              <a href="treasurertransactions.php" style="color:var(--navy);font-weight:700;margin-left:4px;">View all →</a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
          </section>

          <!-- RECENT PAID TRANSACTIONS -->
          <?php if (!empty($recentTransactions)): ?>
          <section class="td-panel mt-3">
            <div class="td-panel-head">
              <div><h3>Recent Payments</h3><p>Last 3 recorded paid transactions.</p></div>
              <a href="treasurerhistory.php" class="td-panel-link">View All</a>
            </div>
            <div class="td-tx-list">
              <?php foreach ($recentTransactions as $tx): ?>
              <article class="td-tx-card">
                <div class="td-tx-top">
                  <span class="td-tx-code"><?= htmlspecialchars($tx['code']) ?></span>
                  <span class="td-tx-date"><?= htmlspecialchars($tx['date']) ?></span>
                </div>
                <div class="td-tx-name"><?= htmlspecialchars($tx['name']) ?></div>
                <div class="td-tx-doc"><?= $tx['doc_type'] ?> &middot; <?= $tx['method'] ?></div>
                <span class="td-tx-amount">&#8369;<?= number_format($tx['amount'],2) ?></span>
              </article>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

        </div>

        <!-- RIGHT SIDEBAR -->
        <div class="col-lg-4 col-xl-3">
          <div class="td-side-stack">

            <section class="td-side-panel">
              <div class="td-side-head"><h3>Quick Summary</h3><span><?= htmlspecialchars($monthName) ?></span></div>
              <div class="td-sum-grid">
                <div class="td-sum-card">
                  <div class="td-sum-label">Total Income</div>
                  <div class="td-sum-value"><?= fmoney($totalIncome) ?></div>
                  <div class="td-sum-note">This month</div>
                </div>
                <div class="td-sum-card">
                  <div class="td-sum-label">Paid Transactions</div>
                  <div class="td-sum-value"><?= $totalIssued ?></div>
                  <div class="td-sum-note">Completed</div>
                </div>
                <div class="td-sum-card">
                  <div class="td-sum-label">Pending Queue</div>
                  <div class="td-sum-value"><?= $pendingPaymentCount ?></div>
                  <div class="td-sum-note">Awaiting payment</div>
                </div>
                <div class="td-sum-card">
                  <div class="td-sum-label">Waived</div>
                  <div class="td-sum-value"><?= $waivedCount ?></div>
                  <div class="td-sum-note">No charge</div>
                </div>
              </div>
            </section>

            <?php if (!empty($incomeSources)): ?>
            <section class="td-side-panel">
              <div class="td-side-head"><h3>Income Sources</h3><span>This month</span></div>
              <div class="td-src-list">
                <?php foreach ($incomeSources as $src):
                  $barPct = $maxSource > 0 ? round(($src['income']/$maxSource)*100) : 0;
                ?>
                <div class="td-src-row">
                  <div class="td-src-top">
                    <span class="td-src-name"><?= $src['document_type'] ?></span>
                    <span class="td-src-amt">&#8369;<?= number_format($src['income'],0) ?></span>
                  </div>
                  <div class="td-bar-track"><div class="td-bar-fill" style="width:<?= $barPct ?>%"></div></div>
                </div>
                <?php endforeach; ?>
              </div>
            </section>
            <?php endif; ?>

            <section class="td-side-panel">
              <div class="td-side-head"><h3>Reminder</h3><span>Today</span></div>
              <div class="td-note-box">
                <div class="td-note-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="td-note-title">
                  <?php if ($pendingPaymentCount > 0): ?>
                  <?= $pendingPaymentCount ?> pending payment<?= $pendingPaymentCount>1?'s':'' ?> to review
                  <?php else: ?>
                  All transactions settled ✓
                  <?php endif; ?>
                </div>
                <p class="td-note-text">
                  <?php if ($pendingPaymentCount > 0): ?>
                  Record all pending payments before closing the daily report.
                  <?php else: ?>
                  No pending payments today. Great job keeping up!
                  <?php endif; ?>
                </p>
              </div>
            </section>

            <section class="td-side-panel">
              <div class="td-side-head"><h3>Community</h3></div>
              <div class="td-note-box">
                <div class="td-note-icon"><i class="fa-solid fa-people-group"></i></div>
                <div class="td-note-title">Barangay Community Board</div>
                <p class="td-note-text">View resident posts, barangay updates, and community activity.</p>
              </div>
              <a href="treasurercommunity.php" class="td-btn td-btn-navy w-100 mt-3">Open Community</a>
            </section>

          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* SIDEBAR TOGGLE */
var tdSidebar = document.getElementById("td-sidebar");
var tdMain    = document.getElementById("td-main");
var tdToggleW = document.getElementById("td-toggle-wrap");
var tdToggleB = document.getElementById("td-toggle-button");
tdToggleB.addEventListener("click", function() {
  var mini = tdSidebar.classList.toggle("mini");
  tdMain.classList.toggle("shifted", mini);
  tdToggleW.classList.toggle("mini", mini);
});

/* NOTIFICATION BELL */
function toggleNotif() { document.getElementById('notifDropdown').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  var btn = document.getElementById('bellBtn');
  var dd  = document.getElementById('notifDropdown');
  if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
});

function handleNotifClick(notifId, btn) {
  if (btn.classList.contains('unread')) {
    btn.classList.remove('unread');
    var dot = btn.querySelector('.td-notif-dot');
    if (dot) dot.remove();
    var countEl = document.querySelector('.td-bell-count');
    if (countEl) {
      var cur = parseInt(countEl.textContent) - 1;
      if (cur <= 0) countEl.remove(); else countEl.textContent = cur;
    }
    var fd = new FormData();
    fd.append('action', 'read_notif');
    fd.append('notif_id', notifId);
    fd.append('ajax', '1');
    fetch('treasurerdashboard.php', { method: 'POST', body: fd }).catch(function(){});
  }
  document.getElementById('notifDropdown').classList.remove('open');
}

/* RECORD MODAL */
function openRecordModal(id, name, code, doc, amount) {
  document.getElementById('modal-request-id').value = id;
  document.getElementById('modal-waived-id').value  = id;
  document.getElementById('modal-resident').textContent = name;
  document.getElementById('modal-code').textContent     = code;
  document.getElementById('modal-doc').textContent      = doc;
  document.getElementById('modal-amount').textContent   = amount;
  document.getElementById('recordModal').classList.add('open');
}
function closeRecordModal() { document.getElementById('recordModal').classList.remove('open'); }
document.getElementById('recordModal').addEventListener('click', function(e) {
  if (e.target === this) closeRecordModal();
});

/* LOGOUT */
function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

/* INCOME TABLE FILTER CHIPS */
var chips = document.querySelectorAll('.td-chip');
chips.forEach(function(chip) {
  chip.addEventListener('click', function() {
    chips.forEach(function(c) { c.classList.remove('on'); });
    chip.classList.add('on');
    var filter = chip.getAttribute('data-filter');
    var rows   = document.querySelectorAll('#incomeTable tbody tr');
    rows.forEach(function(row) {
      row.style.display = (filter === 'all' || row.getAttribute('data-type') === filter) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>