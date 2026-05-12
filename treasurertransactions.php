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
function ftime($val) {
    if ($val instanceof DateTime) return $val->format('g:i A');
    return date('g:i A', strtotime($val ?? 'now'));
}

/* ── HANDLE ACTIONS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $act = rtrim($_POST['action']);

    if ($act === 'mark_paid' && !empty($_POST['request_id'])) {
        $rid   = (int)$_POST['request_id'];
        $method = in_array($_POST['payment_method'] ?? '', ['Cash','GCash','Online Payment','Waived']) ? rtrim($_POST['payment_method']) : 'Cash';
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
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'MARK_PAID', ?, GETDATE())",
                [$userId, "Marked PAID for Request #$rid ($docType) via $method"]);
        }
        header("Location: treasurertransactions.php"); exit();
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
                "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'MARK_WAIVED', ?, GETDATE())",
                [$userId, "Marked WAIVED for Request #$rid ($docType)"]);
        }
        header("Location: treasurertransactions.php"); exit();
    }

    if ($act === 'logout') {
        sqlsrv_query($conn,
            "INSERT INTO AUDIT_LOGS (USER_ID, ACTION, DETAILS, CREATED_AT) VALUES (?, 'LOGOUT', 'Treasurer logged out', GETDATE())",
            [$userId]);
        session_destroy();
        header("Location: login.php"); exit();
    }
}

/* ── FILTER ── */
$filterDoc    = trim($_GET['doc'] ?? '');
$filterSort   = trim($_GET['sort'] ?? 'newest');
$searchName   = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$orderBy = match($filterSort) {
    'oldest'  => "DR.CREATED_AT ASC",
    default   => "DR.CREATED_AT DESC",
};

$whereParts = ["DR.STATUS = 'APPROVED'",
    "NOT EXISTS (SELECT 1 FROM PAYMENTS P WHERE P.REQUEST_ID = DR.REQUEST_ID AND P.PAYMENT_STATUS IN ('PAID','WAIVED'))"];
$params = [];
if ($filterDoc !== '') { $whereParts[] = "DR.DOCUMENT_TYPE = ?"; $params[] = $filterDoc; }
if ($searchName !== '') { $whereParts[] = "(R.FIRST_NAME LIKE ? OR R.LAST_NAME LIKE ?)"; $params[] = "%$searchName%"; $params[] = "%$searchName%"; }
$where = implode(' AND ', $whereParts);

$countRow = sqlsrv_fetch_array(
    sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS DR LEFT JOIN REGISTRATION R ON R.USER_ID = DR.USER_ID WHERE $where", $params ?: []),
    SQLSRV_FETCH_ASSOC
);
$totalRecords = $countRow ? (int)$countRow['CNT'] : 0;
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

$txStmt = sqlsrv_query($conn,
    "SELECT DR.REQUEST_ID, DR.DOCUMENT_TYPE, DR.PURPOSE, DR.CREATED_AT,
            R.FIRST_NAME, R.LAST_NAME
     FROM DOCUMENT_REQUESTS DR
     LEFT JOIN REGISTRATION R ON R.USER_ID = DR.USER_ID
     WHERE $where
     ORDER BY $orderBy
     OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
    array_merge($params, [$offset, $perPage])
);
$transactions = [];
while ($txStmt && $row = sqlsrv_fetch_array($txStmt, SQLSRV_FETCH_ASSOC)) {
    $fn = rtrim($row['FIRST_NAME'] ?? '');
    $ln = rtrim($row['LAST_NAME']  ?? '');
    $feeMap = ['Barangay Clearance'=>100,'Certificate of Residency'=>50,'Certificate of Good Moral'=>100,'Business Permit'=>200];
    $docType = rtrim($row['DOCUMENT_TYPE']);
    $fee = $feeMap[$docType] ?? 100;
    $transactions[] = [
        'id'       => (int)$row['REQUEST_ID'],
        'code'     => 'TRN-' . str_pad((int)$row['REQUEST_ID'], 4, '0', STR_PAD_LEFT),
        'name'     => trim("$fn $ln") ?: 'Unknown',
        'initials' => strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1)),
        'doc_type' => $docType,
        'purpose'  => htmlspecialchars(rtrim($row['PURPOSE'] ?? '—')),
        'date'     => fdate($row['CREATED_AT']),
        'time'     => ftime($row['CREATED_AT']),
        'amount'   => '&#8369;' . number_format($fee, 0),
        'fee'      => $fee,
    ];
}

/* ── SIDEBAR COUNTS ── */
$pendingPaymentCount = $totalRecords;

/* ── SUMMARY COUNTS ── */
$summaryStmt = sqlsrv_query($conn,
    "SELECT COUNT(*) AS PENDING_CNT FROM DOCUMENT_REQUESTS DR
     WHERE DR.STATUS = 'APPROVED'
       AND NOT EXISTS (SELECT 1 FROM PAYMENTS P WHERE P.REQUEST_ID = DR.REQUEST_ID AND P.PAYMENT_STATUS IN ('PAID','WAIVED'))");
$summaryRow = $summaryStmt ? sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC) : null;
$pendingCount = $summaryRow ? (int)$summaryRow['PENDING_CNT'] : 0;

$amtDueRow = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS DR
         WHERE DR.STATUS = 'APPROVED'
           AND NOT EXISTS (SELECT 1 FROM PAYMENTS P WHERE P.REQUEST_ID = DR.REQUEST_ID AND P.PAYMENT_STATUS IN ('PAID','WAIVED'))"),
    SQLSRV_FETCH_ASSOC
);
$amtDue = $amtDueRow ? (int)$amtDueRow['CNT'] : 0;

/* ── DOCUMENT SOURCES THIS WEEK ── */
$srcStmt = sqlsrv_query($conn,
    "SELECT TOP 3 P.DOCUMENT_TYPE, ISNULL(SUM(P.AMOUNT),0) AS AMT
     FROM PAYMENTS P
     WHERE P.PAYMENT_STATUS = 'PAID'
       AND P.CREATED_AT >= DATEADD(DAY, -7, GETDATE())
     GROUP BY P.DOCUMENT_TYPE ORDER BY AMT DESC");
$docSources = [];
while ($srcStmt && $row = sqlsrv_fetch_array($srcStmt, SQLSRV_FETCH_ASSOC)) {
    $docSources[] = ['doc' => rtrim($row['DOCUMENT_TYPE']), 'amt' => (float)$row['AMT']];
}
$maxSrc = $docSources ? max(array_column($docSources, 'amt')) : 1;

/* ── DISTINCT DOC TYPES FOR FILTER ── */
$docTypeStmt = sqlsrv_query($conn, "SELECT DISTINCT DOCUMENT_TYPE FROM DOCUMENT_REQUESTS WHERE STATUS = 'APPROVED' ORDER BY DOCUMENT_TYPE");
$docTypeOptions = [];
while ($docTypeStmt && $row = sqlsrv_fetch_array($docTypeStmt, SQLSRV_FETCH_ASSOC)) {
    $docTypeOptions[] = rtrim($row['DOCUMENT_TYPE']);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Ongoing Transactions — BarangayKonek</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
<style>
:root{--navy:#051650;--navy-mid:#0a2160;--lime:#ccff00;--surface:#ffffff;--soft-bg:#f8fafc;--border:#e3e7f0;--text:#1a2240;--text-muted:#7a869a;--sidebar-w:228px;--sidebar-mini:64px;--topbar-h:62px;--soft-navy-bg:#edf2ff;--amber-bg:#fffbeb;--amber-text:#92400e;--blue-bg:#eff6ff;--blue-text:#1e40af;--gray-bg:#f1f5f9;--gray-text:#475569;--green-bg:#ecfdf5;--green-text:#166534;--shadow-light:0 6px 18px rgba(5,22,80,0.08);--shadow-hover:0 14px 30px rgba(5,22,80,0.13);--shadow-sidebar:6px 0 18px rgba(5,22,80,0.14);}
*{box-sizing:border-box;}
body{margin:0;font-family:"DM Sans",sans-serif;background:radial-gradient(circle at top left,rgba(204,255,0,0.1),transparent 32%),linear-gradient(135deg,#f8fbff 0%,#eef3fb 100%);color:var(--text);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;}button,input,select,textarea{font-family:inherit;}

@keyframes treasurertransactionFadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
@keyframes treasurertransactionBellShake{0%,100%{transform:rotate(0deg);}20%{transform:rotate(14deg);}40%{transform:rotate(-12deg);}60%{transform:rotate(8deg);}80%{transform:rotate(-6deg);}}

.treasurertransaction-container{display:flex;min-height:100vh;}
.treasurertransaction-sidebar{width:var(--sidebar-w);height:100vh;position:fixed;top:0;left:0;z-index:40;overflow:hidden;display:flex;flex-direction:column;background:radial-gradient(circle at top left,rgba(204,255,0,0.14),transparent 28%),linear-gradient(180deg,#051650 0%,#081d63 56%,#040f3b 100%);box-shadow:var(--shadow-sidebar);transition:width 0.25s ease;}
.treasurertransaction-sidebar.mini{width:var(--sidebar-mini);}
.treasurertransaction-toggle-wrap{position:fixed;top:50%;left:var(--sidebar-w);transform:translate(-50%,-50%);z-index:200;transition:left 0.25s ease;}
.treasurertransaction-toggle-wrap.mini{left:var(--sidebar-mini);}
.treasurertransaction-toggle-btn{width:30px;height:30px;border-radius:999px;background:var(--surface);border:1px solid var(--border);box-shadow:0 5px 12px rgba(5,22,80,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:background 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurertransaction-toggle-btn:hover{background:var(--lime);border-color:var(--lime);transform:scale(1.05);}
.treasurertransaction-toggle-btn i{font-size:10px;color:var(--navy);transition:transform 0.25s ease;}
.treasurertransaction-toggle-wrap.mini .treasurertransaction-toggle-btn i{transform:rotate(180deg);}
.treasurertransaction-identity{height:var(--topbar-h);display:flex;align-items:center;gap:11px;padding:0 15px;border-bottom:1px solid rgba(255,255,255,0.1);white-space:nowrap;overflow:hidden;}
.treasurertransaction-identity-logo{width:38px;height:38px;border-radius:14px;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,0.12);}
.treasurertransaction-identity-logo img{width:100%;height:100%;object-fit:cover;}
.treasurertransaction-identity-name{font-size:14px;font-weight:800;color:#fff;line-height:1.2;}
.treasurertransaction-identity-chip{display:inline-flex;align-items:center;margin-top:5px;padding:4px 10px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:11px;font-weight:800;line-height:1;}
.treasurertransaction-sidebar.mini .treasurertransaction-identity-name,.treasurertransaction-sidebar.mini .treasurertransaction-identity-chip{opacity:0;width:0;pointer-events:none;}
.treasurertransaction-sidebar.mini .treasurertransaction-identity-logo{margin:0 auto;}
.treasurertransaction-menu{flex:1;padding:16px 10px;display:flex;flex-direction:column;gap:5px;overflow-y:auto;overflow-x:hidden;}
.treasurertransaction-menu-divider{height:1px;background:rgba(255,255,255,0.08);margin:8px;flex-shrink:0;}
.treasurertransaction-menu-link{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.66);cursor:pointer;white-space:nowrap;overflow:hidden;flex-shrink:0;text-decoration:none;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease;}
.treasurertransaction-menu-link:hover{background:rgba(255,255,255,0.09);color:#fff;transform:translateX(3px);}
.treasurertransaction-menu-link.active{background:var(--lime);color:var(--navy);}
.treasurertransaction-menu-left{display:flex;align-items:center;gap:10px;min-width:0;}
.treasurertransaction-menu-left i{width:18px;text-align:center;font-size:13px;flex-shrink:0;}
.treasurertransaction-menu-label{overflow:hidden;text-overflow:ellipsis;}
.treasurertransaction-sidebar.mini .treasurertransaction-menu-label{opacity:0;width:0;pointer-events:none;}
.treasurertransaction-menu-count{font-size:10px;font-weight:800;background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);min-width:22px;height:22px;padding:0 6px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.treasurertransaction-menu-link.active .treasurertransaction-menu-count{background:rgba(5,22,80,0.18);color:var(--navy);}
.treasurertransaction-sidebar.mini .treasurertransaction-menu-count{opacity:0;pointer-events:none;}
.treasurertransaction-sidebar-footer{padding:13px 10px;border-top:1px solid rgba(255,255,255,0.08);}
.treasurertransaction-logout{display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:16px;font-size:13px;font-weight:700;color:rgba(255,255,255,0.46);background:transparent;border:none;cursor:pointer;width:100%;text-align:left;white-space:nowrap;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease;}
.treasurertransaction-logout:hover{background:rgba(239,68,68,0.14);color:#fca5a5;transform:translateX(3px);}

.treasurertransaction-main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;min-width:0;transition:margin-left 0.25s ease;}
.treasurertransaction-main.shifted{margin-left:var(--sidebar-mini);}

.treasurertransaction-topbar{height:var(--topbar-h);background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;padding:0 24px;position:sticky;top:0;z-index:30;}
.treasurertransaction-topbar-search{flex:1;max-width:390px;height:38px;display:flex;align-items:center;gap:8px;padding:0 14px;background:var(--soft-bg);border:1px solid var(--border);border-radius:999px;transition:background 0.18s ease,border-color 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-topbar-search:focus-within{border-color:var(--navy);background:#fff;box-shadow:0 0 0 3px rgba(5,22,80,0.08);}
.treasurertransaction-topbar-search i{color:var(--text-muted);font-size:12px;}
.treasurertransaction-topbar-search input{flex:1;border:none;outline:none;background:transparent;font-size:13px;color:var(--text);}
.treasurertransaction-topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.treasurertransaction-topbar-icon{width:38px;height:38px;border-radius:999px;background:var(--soft-bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;cursor:pointer;position:relative;text-decoration:none;transition:border-color 0.18s ease,color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-topbar-icon:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.treasurertransaction-topbar-icon:hover i{animation:treasurertransactionBellShake 0.55s ease;}
.treasurertransaction-notification-count{position:absolute;top:-6px;right:-5px;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:var(--lime);color:var(--navy);font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
.treasurertransaction-profile{display:flex;align-items:center;gap:8px;padding:5px 13px 5px 5px;border:1px solid var(--border);border-radius:999px;background:var(--soft-bg);cursor:pointer;text-decoration:none;transition:border-color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-profile:hover{border-color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.08);}
.treasurertransaction-avatar{width:30px;height:30px;border-radius:999px;background:var(--navy);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--lime);flex-shrink:0;}
.treasurertransaction-profile-name{font-size:13px;font-weight:700;color:var(--navy);white-space:nowrap;}
.treasurertransaction-profile-role{font-size:11px;color:var(--text-muted);line-height:1;}

.treasurertransaction-body{padding:24px 26px 44px;}
.treasurertransaction-hero{background:linear-gradient(135deg,rgba(5,22,80,0.98),rgba(10,33,96,0.95)),radial-gradient(circle at top right,rgba(204,255,0,0.24),transparent 35%);border-radius:28px;padding:30px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:22px;animation:treasurertransactionFadeUp 0.28s ease both;position:relative;overflow:hidden;transition:transform 0.2s ease,box-shadow 0.2s ease;}
.treasurertransaction-hero:hover{transform:translateY(-3px);box-shadow:var(--shadow-hover);}
.treasurertransaction-hero::before{content:'';position:absolute;width:210px;height:210px;right:-90px;top:-110px;background:rgba(204,255,0,0.12);border-radius:50%;}
.treasurertransaction-hero-text,.treasurertransaction-hero-actions{position:relative;z-index:1;}
.treasurertransaction-hero-text h2{font-size:22px;font-weight:800;color:#fff;line-height:1.25;margin-bottom:0;}
.treasurertransaction-hero-text p{font-size:13px;color:rgba(255,255,255,0.68);margin-top:7px;max-width:560px;margin-bottom:0;}
.treasurertransaction-hero-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}

.treasurertransaction-btn{min-height:38px;padding:0 14px;border-radius:999px;font-size:12px;font-weight:800;border:none;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-btn:hover{transform:translateY(-1px);}
.treasurertransaction-btn-primary{background:var(--navy);color:#fff;}
.treasurertransaction-btn-primary:hover{background:var(--lime);color:var(--navy);}
.treasurertransaction-btn-lime{background:var(--lime);color:var(--navy);}
.treasurertransaction-btn-lime:hover{background:#b7e900;color:var(--navy);}
.treasurertransaction-btn-light{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.18);}
.treasurertransaction-btn-light:hover{background:rgba(255,255,255,0.18);color:#fff;}

.treasurertransaction-panel,.treasurertransaction-side-panel{background:#fff;border:1px solid var(--border);border-radius:28px;box-shadow:var(--shadow-light);animation:treasurertransactionFadeUp 0.28s ease both;transition:box-shadow 0.2s ease,transform 0.2s ease,border-color 0.2s ease;}
.treasurertransaction-panel:hover,.treasurertransaction-side-panel:hover{box-shadow:var(--shadow-hover);transform:translateY(-3px);border-color:#d7deea;}
.treasurertransaction-panel{overflow:hidden;}

.treasurertransaction-filter-panel{padding:18px 22px;border-bottom:1px solid var(--border);}
.treasurertransaction-filter-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
.treasurertransaction-search-box{flex:1;min-width:180px;height:38px;display:flex;align-items:center;gap:8px;padding:0 14px;background:var(--soft-bg);border:1px solid var(--border);border-radius:999px;}
.treasurertransaction-search-box:focus-within{border-color:var(--navy);background:#fff;}
.treasurertransaction-search-box i{color:var(--text-muted);font-size:12px;}
.treasurertransaction-search-box input{flex:1;border:none;outline:none;background:transparent;font-size:13px;}
.treasurertransaction-filter-select{height:38px;padding:0 12px;border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:700;color:var(--text);background:var(--soft-bg);outline:none;cursor:pointer;}
.treasurertransaction-chip-row{display:flex;flex-wrap:wrap;gap:8px;}
.treasurertransaction-chip{min-height:34px;padding:0 14px;border-radius:999px;border:1px solid var(--border);background:var(--soft-bg);color:var(--text-muted);font-size:12px;font-weight:800;cursor:pointer;transition:background 0.18s ease,color 0.18s ease,border-color 0.18s ease,transform 0.18s ease;}
.treasurertransaction-chip:hover{border-color:var(--navy);color:var(--navy);transform:translateY(-1px);}
.treasurertransaction-chip.on{background:var(--navy);border-color:var(--navy);color:var(--lime);}

.treasurertransaction-panel-head{padding:22px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:14px;}
.treasurertransaction-panel-title h3{font-size:18px;font-weight:800;color:var(--navy);margin-bottom:4px;}
.treasurertransaction-panel-title p{font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:0;}
.treasurertransaction-panel-link{min-height:38px;padding:0 15px;border-radius:999px;background:var(--soft-bg);border:1px solid var(--border);color:var(--navy);font-size:12px;font-weight:800;display:flex;align-items:center;white-space:nowrap;transition:background 0.18s ease,border-color 0.18s ease,transform 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-panel-link:hover{background:#fff;border-color:var(--navy);transform:translateY(-1px);box-shadow:0 8px 16px rgba(5,22,80,0.06);}

.treasurertransaction-list-head{display:grid;grid-template-columns:1.8fr 2.2fr 1fr 0.8fr 1fr 1.2fr;gap:12px;padding:11px 22px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);background:#fafbfd;border-bottom:1px solid var(--border);}
.treasurertransaction-list{display:flex;flex-direction:column;}
.treasurertransaction-item{display:grid;grid-template-columns:1.8fr 2.2fr 1fr 0.8fr 1fr 1.2fr;gap:12px;padding:16px 22px;border-bottom:1px solid var(--border);align-items:center;transition:background 0.18s ease,box-shadow 0.18s ease;}
.treasurertransaction-item:last-child{border-bottom:none;}
.treasurertransaction-item:hover{background:#fbfdff;box-shadow:inset 4px 0 0 var(--lime);}
.treasurertransaction-person-name{font-size:13px;font-weight:800;color:var(--navy);}
.treasurertransaction-code{font-size:11px;color:var(--text-muted);margin-top:2px;}
.treasurertransaction-document-name{font-size:13px;font-weight:700;color:var(--text);}
.treasurertransaction-document-note{font-size:11px;color:var(--text-muted);margin:2px 0 0;line-height:1.4;}
.treasurertransaction-date-text{font-size:12px;font-weight:700;color:var(--text);}
.treasurertransaction-time-text{font-size:11px;color:var(--text-muted);margin-top:2px;}
.treasurertransaction-amount-text{font-size:14px;font-weight:800;color:var(--navy);}
.treasurertransaction-status{display:inline-flex;align-items:center;height:24px;padding:0 10px;border-radius:999px;font-size:11px;font-weight:800;}
.treasurertransaction-status.pending{background:var(--amber-bg);color:var(--amber-text);}
.treasurertransaction-action-row{display:flex;gap:6px;flex-wrap:wrap;}
.treasurertransaction-row-btn{min-height:30px;padding:0 12px;border-radius:999px;border:1px solid var(--navy);background:var(--navy);color:#fff;font-size:11px;font-weight:800;cursor:pointer;transition:background 0.18s ease,color 0.18s ease,transform 0.18s ease;}
.treasurertransaction-row-btn:hover{background:var(--lime);border-color:var(--lime);color:var(--navy);transform:translateY(-1px);}
.treasurertransaction-row-btn.light{background:transparent;border-color:var(--border);color:var(--text-muted);}
.treasurertransaction-row-btn.light:hover{border-color:var(--navy);color:var(--navy);}
.treasurertransaction-row-btn.danger{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
.treasurertransaction-row-btn.danger:hover{background:#991b1b;border-color:#991b1b;color:#fff;}

.treasurertransaction-list-foot{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;border-top:1px solid var(--border);background:#fafbfd;flex-wrap:wrap;gap:10px;}
.treasurertransaction-list-info{font-size:12px;color:var(--text-muted);font-weight:600;}
.treasurertransaction-pager{display:flex;gap:4px;}
.treasurertransaction-pager-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:#fff;color:var(--text-muted);font-size:12px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.18s ease;}
.treasurertransaction-pager-btn:hover{border-color:var(--navy);color:var(--navy);}
.treasurertransaction-pager-btn.on{background:var(--navy);border-color:var(--navy);color:var(--lime);}
.treasurertransaction-pager-btn:disabled{opacity:0.4;cursor:default;}

.treasurertransaction-side-stack{display:flex;flex-direction:column;gap:16px;}
.treasurertransaction-side-panel{padding:20px;}
.treasurertransaction-side-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px;}
.treasurertransaction-side-head h3{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:0;}
.treasurertransaction-side-head span{font-size:12px;color:var(--text-muted);font-weight:700;}
.treasurertransaction-summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
.treasurertransaction-summary-card{border:1px solid var(--border);border-radius:22px;background:#fff;padding:14px;transition:box-shadow 0.2s ease,transform 0.2s ease,border-color 0.2s ease;}
.treasurertransaction-summary-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-3px);border-color:#d7deea;}
.treasurertransaction-summary-label{font-size:11px;font-weight:800;color:var(--text-muted);line-height:1.35;}
.treasurertransaction-summary-value{font-size:20px;font-weight:800;color:var(--navy);line-height:1;margin:6px 0;letter-spacing:-0.4px;}
.treasurertransaction-summary-note{font-size:11px;color:var(--text-muted);font-weight:600;}
.treasurertransaction-source-list{display:flex;flex-direction:column;gap:12px;}
.treasurertransaction-source-row{display:flex;flex-direction:column;gap:6px;}
.treasurertransaction-source-top{display:flex;align-items:center;justify-content:space-between;gap:12px;}
.treasurertransaction-source-name{font-size:12.5px;font-weight:700;color:var(--text);}
.treasurertransaction-source-amount{font-size:12px;font-weight:800;color:var(--navy);}
.treasurertransaction-bar-track{height:8px;background:#eaf0f7;border-radius:999px;overflow:hidden;}
.treasurertransaction-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--navy),#254ca8);}
.treasurertransaction-note-box{padding:14px;border-radius:20px;background:var(--soft-bg);border:1px solid var(--border);}
.treasurertransaction-note-title{font-size:13px;font-weight:800;color:var(--navy);margin-bottom:4px;}
.treasurertransaction-note-text{font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:0;}

.treasurertransaction-modal-content{border:none;border-radius:28px;overflow:hidden;}
.treasurertransaction-modal-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);margin-bottom:6px;}
.treasurertransaction-modal-box{background:var(--soft-bg);border:1px solid var(--border);border-radius:14px;padding:11px 14px;font-size:13px;font-weight:700;color:var(--text);min-height:42px;display:flex;align-items:center;}
.treasurertransaction-form-control{width:100%;height:42px;padding:0 14px;border:1px solid var(--border);border-radius:14px;font-size:13px;color:var(--text);background:var(--soft-bg);outline:none;}
.treasurertransaction-form-control:focus{border-color:var(--navy);background:#fff;}

.logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,0.65);display:none;align-items:center;justify-content:center;}
.logout-overlay.open{display:flex;}
.logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,0.28);}
.logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px;}
.logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px;}
.logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6;}
.logout-btns{display:flex;gap:10px;justify-content:center;}
.btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
.btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,0.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}

@media(max-width:992px){.treasurertransaction-list-head,.treasurertransaction-item{grid-template-columns:1fr 1fr;}.treasurertransaction-side-stack{margin-top:16px;}}
@media(max-width:760px){.treasurertransaction-sidebar{width:var(--sidebar-mini);}.treasurertransaction-main{margin-left:var(--sidebar-mini);}.treasurertransaction-toggle-wrap{left:var(--sidebar-mini);}.treasurertransaction-identity-name,.treasurertransaction-identity-chip,.treasurertransaction-menu-label,.treasurertransaction-menu-count{opacity:0;width:0;pointer-events:none;}.treasurertransaction-body{padding:18px 14px 36px;}.treasurertransaction-topbar{padding:0 14px;}.treasurertransaction-profile-name,.treasurertransaction-profile-role{display:none;}.treasurertransaction-list-head,.treasurertransaction-item{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="logout-overlay" id="logoutModal">
  <div class="logout-box">
    <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
    <h3>Log out?</h3>
    <p>Are you sure you want to leave the Treasurer Portal?</p>
    <div class="logout-btns">
      <button class="btn-cancel-lo" onclick="closeLogout()">Cancel</button>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="logout"/>
        <button type="submit" class="btn-confirm-lo"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>

<div class="treasurertransaction-toggle-wrap" id="treasurertransaction-toggle-wrap">
  <button class="treasurertransaction-toggle-btn" id="treasurertransaction-toggle-button" title="Collapse / Expand">
    <i class="fa-solid fa-chevron-left"></i>
  </button>
</div>

<div class="treasurertransaction-container">
  <aside class="treasurertransaction-sidebar" id="treasurertransaction-sidebar">
    <div class="treasurertransaction-identity">
      <div class="treasurertransaction-identity-logo"><img src="alapan.png" alt="Alapan logo"/></div>
      <div>
        <div class="treasurertransaction-identity-name">BarangayKonek</div>
        <span class="treasurertransaction-identity-chip">Treasurer Portal</span>
      </div>
    </div>
    <nav class="treasurertransaction-menu">
      <a href="treasurerdashboard.php" class="treasurertransaction-menu-link">
        <div class="treasurertransaction-menu-left"><i class="fa-solid fa-house"></i><span class="treasurertransaction-menu-label">Dashboard</span></div>
      </a>
      <a href="treasurertransactions.php" class="treasurertransaction-menu-link active">
        <div class="treasurertransaction-menu-left"><i class="fa-solid fa-coins"></i><span class="treasurertransaction-menu-label">Transactions</span></div>
        <?php if ($pendingPaymentCount > 0): ?><span class="treasurertransaction-menu-count"><?= $pendingPaymentCount ?></span><?php endif; ?>
      </a>
      <a href="treasurerhistory.php" class="treasurertransaction-menu-link">
        <div class="treasurertransaction-menu-left"><i class="fa-solid fa-clock-rotate-left"></i><span class="treasurertransaction-menu-label">Transaction History</span></div>
      </a>
      <a href="treasurercommunity.php" class="treasurertransaction-menu-link">
        <div class="treasurertransaction-menu-left"><i class="fa-solid fa-people-group"></i><span class="treasurertransaction-menu-label">Community</span></div>
      </a>
      <div class="treasurertransaction-menu-divider"></div>
      <a href="treasurerprofile.php" class="treasurertransaction-menu-link">
        <div class="treasurertransaction-menu-left"><i class="fa-solid fa-gear"></i><span class="treasurertransaction-menu-label">Settings</span></div>
      </a>
    </nav>
    <div class="treasurertransaction-sidebar-footer">
      <button type="button" class="treasurertransaction-logout" onclick="openLogout()">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="treasurertransaction-menu-label">Log Out</span>
      </button>
    </div>
  </aside>

  <main class="treasurertransaction-main" id="treasurertransaction-main">
    <header class="treasurertransaction-topbar">
      <div class="treasurertransaction-topbar-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Search ongoing transactions, residents..."/>
      </div>
      <div class="treasurertransaction-topbar-right">
        <a href="treasurertransactions.php" class="treasurertransaction-topbar-icon">
          <i class="fa-solid fa-bell"></i>
          <?php if ($pendingPaymentCount > 0): ?><span class="treasurertransaction-notification-count"><?= $pendingPaymentCount ?></span><?php endif; ?>
        </a>
        <a href="treasurerprofile.php" class="treasurertransaction-profile">
          <div class="treasurertransaction-avatar"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="treasurertransaction-profile-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="treasurertransaction-profile-role">Treasurer</div>
          </div>
        </a>
      </div>
    </header>

    <div class="treasurertransaction-body">
      <section class="treasurertransaction-hero">
        <div class="treasurertransaction-hero-text">
          <h2>Ongoing Transactions</h2>
          <p>Review pending payments, record document fees, and complete active transactions.</p>
        </div>
        <div class="treasurertransaction-hero-actions">
          <a href="treasurerhistory.php" class="treasurertransaction-btn treasurertransaction-btn-light">
            <i class="fa-solid fa-clock-rotate-left"></i> View History
          </a>
        </div>
      </section>

      <div class="row g-3">
        <div class="col-lg-8 col-xl-9">
          <section class="treasurertransaction-panel">
            <form method="GET" action="treasurertransactions.php">
              <div class="treasurertransaction-filter-panel">
                <div class="treasurertransaction-filter-top">
                  <div class="treasurertransaction-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search by resident name..." value="<?= htmlspecialchars($searchName) ?>"/>
                  </div>
                  <select name="doc" class="treasurertransaction-filter-select">
                    <option value="">All Documents</option>
                    <?php foreach ($docTypeOptions as $dt): ?>
                    <option value="<?= htmlspecialchars($dt) ?>" <?= $filterDoc === $dt ? 'selected' : '' ?>><?= htmlspecialchars($dt) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="sort" class="treasurertransaction-filter-select">
                    <option value="newest" <?= $filterSort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $filterSort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                  </select>
                  <button type="submit" class="treasurertransaction-btn treasurertransaction-btn-primary">Apply Filter</button>
                </div>
              </div>
            </form>

            <div class="treasurertransaction-panel-head">
              <div class="treasurertransaction-panel-title">
                <h3>Transaction Queue</h3>
                <p>Approved requests pending payment collection.</p>
              </div>
              <a href="treasurerhistory.php" class="treasurertransaction-panel-link">View History</a>
            </div>

            <div class="treasurertransaction-list-head">
              <span>Resident</span>
              <span>Document / Purpose</span>
              <span>Date</span>
              <span>Amount</span>
              <span>Status</span>
              <span>Action</span>
            </div>

            <div class="treasurertransaction-list">
              <?php if (empty($transactions)): ?>
              <div style="padding:40px;text-align:center;color:var(--text-muted);font-size:14px;">
                No pending transactions found.
              </div>
              <?php else: foreach ($transactions as $tx): ?>
              <article class="treasurertransaction-item">
                <div>
                  <div class="treasurertransaction-person-name"><?= htmlspecialchars($tx['name']) ?></div>
                  <div class="treasurertransaction-code"><?= htmlspecialchars($tx['code']) ?></div>
                </div>
                <div>
                  <div class="treasurertransaction-document-name"><?= htmlspecialchars($tx['doc_type']) ?></div>
                  <p class="treasurertransaction-document-note"><?= $tx['purpose'] ?></p>
                </div>
                <div>
                  <div class="treasurertransaction-date-text"><?= htmlspecialchars($tx['date']) ?></div>
                  <div class="treasurertransaction-time-text"><?= htmlspecialchars($tx['time']) ?></div>
                </div>
                <div>
                  <div class="treasurertransaction-amount-text"><?= $tx['amount'] ?></div>
                </div>
                <div>
                  <span class="treasurertransaction-status pending">Pending</span>
                </div>
                <div class="treasurertransaction-action-row">
                  <button type="button" class="treasurertransaction-row-btn"
                    onclick="openRecordModal(<?= $tx['id'] ?>, '<?= htmlspecialchars(addslashes($tx['name'])) ?>', '<?= htmlspecialchars(addslashes($tx['doc_type'])) ?>', '<?= $tx['amount'] ?>', '<?= $tx['code'] ?>')">
                    Record
                  </button>
                  <button type="button" class="treasurertransaction-row-btn light"
                    onclick="openViewModal(<?= $tx['id'] ?>, '<?= htmlspecialchars(addslashes($tx['name'])) ?>', '<?= htmlspecialchars(addslashes($tx['doc_type'])) ?>', '<?= $tx['amount'] ?>', '<?= $tx['code'] ?>', '<?= htmlspecialchars(addslashes($tx['purpose'])) ?>', '<?= $tx['date'] ?>')">
                    View
                  </button>
                </div>
              </article>
              <?php endforeach; endif; ?>
            </div>

            <div class="treasurertransaction-list-foot">
              <span class="treasurertransaction-list-info">
                Showing <?= min($offset + 1, $totalRecords) ?>–<?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> pending transaction<?= $totalRecords !== 1 ? 's' : '' ?>
              </span>
              <?php if ($totalPages > 1): ?>
              <div class="treasurertransaction-pager">
                <a href="?page=<?= max(1, $page - 1) ?>&doc=<?= urlencode($filterDoc) ?>&sort=<?= urlencode($filterSort) ?>&search=<?= urlencode($searchName) ?>">
                  <button class="treasurertransaction-pager-btn" <?= $page <= 1 ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-chevron-left"></i>
                  </button>
                </a>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>&doc=<?= urlencode($filterDoc) ?>&sort=<?= urlencode($filterSort) ?>&search=<?= urlencode($searchName) ?>">
                  <button class="treasurertransaction-pager-btn <?= $p === $page ? 'on' : '' ?>"><?= $p ?></button>
                </a>
                <?php endfor; ?>
                <a href="?page=<?= min($totalPages, $page + 1) ?>&doc=<?= urlencode($filterDoc) ?>&sort=<?= urlencode($filterSort) ?>&search=<?= urlencode($searchName) ?>">
                  <button class="treasurertransaction-pager-btn" <?= $page >= $totalPages ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-chevron-right"></i>
                  </button>
                </a>
              </div>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <div class="col-lg-4 col-xl-3">
          <aside class="treasurertransaction-side-stack">
            <section class="treasurertransaction-side-panel">
              <div class="treasurertransaction-side-head">
                <h3>Quick Summary</h3>
                <span>Pending</span>
              </div>
              <div class="treasurertransaction-summary-grid">
                <div class="treasurertransaction-summary-card">
                  <div class="treasurertransaction-summary-label">Pending</div>
                  <div class="treasurertransaction-summary-value"><?= $pendingCount ?></div>
                  <div class="treasurertransaction-summary-note">Needs payment</div>
                </div>
                <div class="treasurertransaction-summary-card">
                  <div class="treasurertransaction-summary-label">Total Queue</div>
                  <div class="treasurertransaction-summary-value"><?= $totalRecords ?></div>
                  <div class="treasurertransaction-summary-note">Active</div>
                </div>
              </div>
            </section>

            <?php if (!empty($docSources)): ?>
            <section class="treasurertransaction-side-panel">
              <div class="treasurertransaction-side-head">
                <h3>Document Sources</h3>
                <span>This week</span>
              </div>
              <div class="treasurertransaction-source-list">
                <?php foreach ($docSources as $src):
                  $pct = $maxSrc > 0 ? round(($src['amt'] / $maxSrc) * 100) : 0;
                ?>
                <div class="treasurertransaction-source-row">
                  <div class="treasurertransaction-source-top">
                    <span class="treasurertransaction-source-name"><?= htmlspecialchars($src['doc']) ?></span>
                    <span class="treasurertransaction-source-amount">&#8369;<?= number_format($src['amt'], 0) ?></span>
                  </div>
                  <div class="treasurertransaction-bar-track">
                    <div class="treasurertransaction-bar-fill" style="width:<?= $pct ?>%"></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </section>
            <?php endif; ?>

            <section class="treasurertransaction-side-panel">
              <div class="treasurertransaction-side-head">
                <h3>Reminder</h3>
                <span>Before closing</span>
              </div>
              <div class="treasurertransaction-note-box">
                <div class="treasurertransaction-note-title">Check pending payments</div>
                <p class="treasurertransaction-note-text">Make sure all payments are recorded before marking a document as paid or moving the transaction to history.</p>
              </div>
            </section>
          </aside>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- RECORD MODAL -->
<div class="modal fade" id="treasurertransactionRecordModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content treasurertransaction-modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary-emphasis">Record Transaction Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="mark_paid"/>
        <input type="hidden" name="request_id" id="record-request-id"/>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <div class="treasurertransaction-modal-label">Resident</div>
              <div class="treasurertransaction-modal-box" id="record-resident">—</div>
            </div>
            <div class="col-6">
              <div class="treasurertransaction-modal-label">Transaction ID</div>
              <div class="treasurertransaction-modal-box" id="record-txnid">—</div>
            </div>
            <div class="col-12">
              <div class="treasurertransaction-modal-label">Document</div>
              <div class="treasurertransaction-modal-box" id="record-document">—</div>
            </div>
            <div class="col-6">
              <div class="treasurertransaction-modal-label">Amount</div>
              <div class="treasurertransaction-modal-box" id="record-amount">—</div>
            </div>
            <div class="col-6">
              <div class="treasurertransaction-modal-label">Payment Method</div>
              <select class="treasurertransaction-form-control" name="payment_method">
                <option>Cash</option>
                <option>GCash</option>
                <option>Online Payment</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="treasurertransaction-btn" style="background:var(--soft-bg);color:var(--text-muted);border:1px solid var(--border);" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="treasurertransaction-btn treasurertransaction-btn-primary">
            <i class="fa-solid fa-check"></i> Mark as Paid
          </button>
        </div>
      </form>
      <div class="modal-footer" style="border-top:none;padding-top:0;">
        <form method="POST" style="width:100%;">
          <input type="hidden" name="action" value="mark_waived"/>
          <input type="hidden" name="request_id" id="record-waived-request-id"/>
          <button type="submit" class="treasurertransaction-row-btn danger" style="width:100%;min-height:36px;font-size:12px;">
            <i class="fa-solid fa-ban"></i> Mark as Waived
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="treasurertransactionViewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content treasurertransaction-modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary-emphasis">Transaction Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6">
            <div class="treasurertransaction-modal-label">Resident</div>
            <div class="treasurertransaction-modal-box" id="view-resident">—</div>
          </div>
          <div class="col-6">
            <div class="treasurertransaction-modal-label">Transaction ID</div>
            <div class="treasurertransaction-modal-box" id="view-txnid">—</div>
          </div>
          <div class="col-12">
            <div class="treasurertransaction-modal-label">Document</div>
            <div class="treasurertransaction-modal-box" id="view-document">—</div>
          </div>
          <div class="col-6">
            <div class="treasurertransaction-modal-label">Amount</div>
            <div class="treasurertransaction-modal-box" id="view-amount">—</div>
          </div>
          <div class="col-6">
            <div class="treasurertransaction-modal-label">Date Requested</div>
            <div class="treasurertransaction-modal-box" id="view-date">—</div>
          </div>
          <div class="col-12">
            <div class="treasurertransaction-modal-label">Purpose</div>
            <div class="treasurertransaction-modal-box" id="view-purpose">—</div>
          </div>
          <div class="col-12">
            <div class="treasurertransaction-modal-label">Status</div>
            <div class="treasurertransaction-modal-box">Pending Payment</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="treasurertransaction-btn treasurertransaction-btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var treasurertransactionSidebar      = document.getElementById("treasurertransaction-sidebar");
var treasurertransactionMainArea     = document.getElementById("treasurertransaction-main");
var treasurertransactionToggleWrap   = document.getElementById("treasurertransaction-toggle-wrap");
var treasurertransactionToggleButton = document.getElementById("treasurertransaction-toggle-button");

treasurertransactionToggleButton.addEventListener("click", function () {
  var mini = treasurertransactionSidebar.classList.toggle("mini");
  treasurertransactionMainArea.classList.toggle("shifted", mini);
  treasurertransactionToggleWrap.classList.toggle("mini", mini);
});

function openRecordModal(id, name, doc, amount, code) {
  document.getElementById("record-request-id").value        = id;
  document.getElementById("record-waived-request-id").value = id;
  document.getElementById("record-resident").textContent    = name;
  document.getElementById("record-txnid").textContent       = code;
  document.getElementById("record-document").textContent    = doc;
  document.getElementById("record-amount").textContent      = amount;
  var modal = new bootstrap.Modal(document.getElementById("treasurertransactionRecordModal"));
  modal.show();
}

function openViewModal(id, name, doc, amount, code, purpose, date) {
  document.getElementById("view-resident").textContent = name;
  document.getElementById("view-txnid").textContent    = code;
  document.getElementById("view-document").textContent = doc;
  document.getElementById("view-amount").textContent   = amount;
  document.getElementById("view-date").textContent     = date;
  document.getElementById("view-purpose").textContent  = purpose;
  var modal = new bootstrap.Modal(document.getElementById("treasurertransactionViewModal"));
  modal.show();
}

function openLogout()  { document.getElementById("logoutModal").classList.add("open"); }
function closeLogout() { document.getElementById("logoutModal").classList.remove("open"); }
document.getElementById("logoutModal").addEventListener("click", function(e) {
  if (e.target === this) closeLogout();
});
</script>
</body>
</html>