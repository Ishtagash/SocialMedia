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

function sqn($conn, $sql, $p = []) {
    $r = sqlsrv_query($conn, $sql, $p ?: []);
    if ($r) { $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC); return (int)($row['CNT'] ?? 0); }
    return 0;
}

$isExport = trim($_GET['export'] ?? '');

$res_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident'");
$res_active   = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='active'");
$res_pending  = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='pending'");
$res_disabled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='inactive'");
$res_rejected = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND STATUS='rejected'");
$res_today    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$res_week     = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CREATED_AT>=DATEADD(DAY,-7,GETDATE())");
$res_month    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CREATED_AT>=DATEADD(DAY,-30,GETDATE())");

$regLabels = []; $regData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('M d', strtotime("-$i days"));
    $cnt = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='resident' AND CAST(CREATED_AT AS DATE)=?", [$date]);
    $regLabels[] = $label; $regData[] = $cnt;
}

$staff_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff'");
$staff_active   = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND STATUS='active'");
$staff_disabled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND STATUS='inactive'");
$positions = ['Punong Barangay','Secretary','Treasurer','SK Chairperson'];
$posData = [];
foreach ($positions as $p) {
    $posData[$p] = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION=?",[$p]);
}
$kagawad_filled = sqn($conn,"SELECT COUNT(*) AS CNT FROM USERS WHERE ROLE='staff' AND POSITION='Kagawad'");
$vacant = array_sum(array_map(fn($v)=>$v===0?1:0, $posData)) + max(0,7-$kagawad_filled);

$doc_total     = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS");
$doc_pending   = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='pending'");
$doc_approved  = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='approved'");
$doc_completed = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='completed'");
$doc_rejected  = sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE STATUS='rejected'");

$docTypes = []; $docTypeCounts = [];
$dtr = sqlsrv_query($conn,"SELECT TOP 5 DOCUMENT_TYPE, COUNT(*) AS CNT FROM DOCUMENT_REQUESTS GROUP BY DOCUMENT_TYPE ORDER BY CNT DESC");
if ($dtr) { while ($row=sqlsrv_fetch_array($dtr,SQLSRV_FETCH_ASSOC)) {
    $docTypes[] = rtrim($row['DOCUMENT_TYPE']??''); $docTypeCounts[] = (int)$row['CNT'];
}}

$docTrendLabels=[]; $docTrendData=[];
for ($i=6;$i>=0;$i--) {
    $date=date('Y-m-d',strtotime("-$i days"));
    $label=date('M d',strtotime("-$i days"));
    $cnt=sqn($conn,"SELECT COUNT(*) AS CNT FROM DOCUMENT_REQUESTS WHERE CAST(CREATED_AT AS DATE)=?",[$date]);
    $docTrendLabels[]=$label; $docTrendData[]=$cnt;
}

$comp_total   = sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS");
$comp_pending = sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE STATUS='pending'");
$comp_resolved= sqn($conn,"SELECT COUNT(*) AS CNT FROM COMPLAINTS WHERE STATUS='resolved'");

$post_total    = sqn($conn,"SELECT COUNT(*) AS CNT FROM POSTS");
$post_today    = sqn($conn,"SELECT COUNT(*) AS CNT FROM POSTS WHERE CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$likes_total   = sqn($conn,"SELECT COUNT(*) AS CNT FROM LIKES");
$comments_total= sqn($conn,"SELECT COUNT(*) AS CNT FROM COMMENTS");
$ann_total     = sqn($conn,"SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS");
$ann_active    = sqn($conn,"SELECT COUNT(*) AS CNT FROM ANNOUNCEMENTS WHERE STATUS='active'");

$log_total = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS");
$log_today = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CAST(CREATED_AT AS DATE)=CAST(GETDATE() AS DATE)");
$log_week  = sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CREATED_AT>=DATEADD(DAY,-7,GETDATE())");

$actionCounts=[]; $actionLabels=[];
$acr=sqlsrv_query($conn,"SELECT ACTION,COUNT(*) AS CNT FROM AUDIT_LOGS GROUP BY ACTION ORDER BY CNT DESC");
if ($acr) { while ($row=sqlsrv_fetch_array($acr,SQLSRV_FETCH_ASSOC)) {
    $actionLabels[]=rtrim($row['ACTION']??''); $actionCounts[]=(int)$row['CNT'];
}}

$logTrendLabels=[]; $logTrendData=[];
for ($i=6;$i>=0;$i--) {
    $date=date('Y-m-d',strtotime("-$i days"));
    $label=date('M d',strtotime("-$i days"));
    $cnt=sqn($conn,"SELECT COUNT(*) AS CNT FROM AUDIT_LOGS WHERE CAST(CREATED_AT AS DATE)=?",[$date]);
    $logTrendLabels[]=$label; $logTrendData[]=$cnt;
}

$compPct = $comp_total>0 ? round(($comp_resolved/$comp_total)*100) : 0;
$genDate = date('F j, Y');
$genDateFull = date('F j, Y g:i A');

/* ── EXPORT: EXCEL ── */
if ($isExport === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reports_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    $xe = function($v) { return htmlspecialchars((string)$v, ENT_XML1, 'UTF-8'); };

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:x="urn:schemas-microsoft-com:office:excel">
    <Styles>
        <Style ss:ID="header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Arial"/>
            <Interior ss:Color="#051650" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCFF00"/></Borders>
        </Style>
        <Style ss:ID="section">
            <Font ss:Bold="1" ss:Size="12" ss:FontName="Arial" ss:Color="#051650"/>
            <Interior ss:Color="#F0F3F9" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="even"><Font ss:Size="10" ss:FontName="Arial" ss:Color="#0d1b3e"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/></Borders></Style>
        <Style ss:ID="odd"><Font ss:Size="10" ss:FontName="Arial" ss:Color="#0d1b3e"/><Interior ss:Color="#F7F9FC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/></Borders></Style>
        <Style ss:ID="title"><Font ss:Bold="1" ss:Size="14" ss:FontName="Arial" ss:Color="#051650"/></Style>
        <Style ss:ID="meta"><Font ss:Size="10" ss:FontName="Arial" ss:Color="#6b7a99"/></Style>
    </Styles>
    <Worksheet ss:Name="Reports Summary">
    <Table ss:DefaultRowHeight="18">
    <Column ss:Width="200"/><Column ss:Width="120"/>
    <Row ss:Height="28"><Cell ss:MergeAcross="1" ss:StyleID="title"><Data ss:Type="String">Barangay Alapan 1-A — System Reports</Data></Cell></Row>
    <Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="meta"><Data ss:Type="String">Generated: ' . $genDateFull . '</Data></Cell></Row>
    <Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';

    $sections = [
        'RESIDENTS' => [
            ['Total Residents', $res_total],
            ['Verified / Active', $res_active],
            ['Pending Verification', $res_pending],
            ['Disabled', $res_disabled],
            ['Rejected', $res_rejected],
            ['Joined Today', $res_today],
            ['Joined This Week', $res_week],
            ['Joined This Month', $res_month],
        ],
        'STAFF' => [
            ['Total Officials', $staff_total],
            ['Active Staff', $staff_active],
            ['Disabled Staff', $staff_disabled],
            ['Vacant Slots', $vacant],
        ],
        'DOCUMENT REQUESTS' => [
            ['Total Requests', $doc_total],
            ['Pending', $doc_pending],
            ['Approved', $doc_approved],
            ['Completed', $doc_completed],
            ['Rejected', $doc_rejected],
        ],
        'COMPLAINTS' => [
            ['Total Complaints', $comp_total],
            ['Pending', $comp_pending],
            ['Resolved', $comp_resolved],
            ['Resolution Rate', $compPct . '%'],
        ],
        'COMMUNITY' => [
            ['Total Posts', $post_total],
            ['Posts Today', $post_today],
            ['Total Likes', $likes_total],
            ['Total Comments', $comments_total],
            ['Total Announcements', $ann_total],
            ['Active Announcements', $ann_active],
        ],
        'AUDIT LOGS' => [
            ['Total Log Entries', $log_total],
            ['Logged Today', $log_today],
            ['Logged This Week', $log_week],
        ],
    ];

    $rowIdx = 0;
    foreach ($sections as $secName => $rows) {
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="section"><Data ss:Type="String">' . $xe($secName) . '</Data></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:StyleID="header"><Data ss:Type="String">METRIC</Data></Cell><Cell ss:StyleID="header"><Data ss:Type="String">VALUE</Data></Cell></Row>';
        foreach ($rows as $i => $row) {
            $st = $i % 2 === 0 ? 'even' : 'odd';
            echo '<Row><Cell ss:StyleID="'.$st.'"><Data ss:Type="String">' . $xe($row[0]) . '</Data></Cell>';
            echo '<Cell ss:StyleID="'.$st.'"><Data ss:Type="String">' . $xe($row[1]) . '</Data></Cell></Row>';
        }
        echo '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';
    }

    echo '</Table></Worksheet></Workbook>';
    exit();
}

/* ── EXPORT: PDF ── */
if ($isExport === 'pdf') {
    $sections = [
        'Residents' => [
            ['Total Residents', $res_total],
            ['Verified / Active', $res_active],
            ['Pending Verification', $res_pending],
            ['Disabled', $res_disabled],
            ['Rejected', $res_rejected],
            ['Joined Today', $res_today],
            ['Joined This Week', $res_week],
            ['Joined This Month', $res_month],
        ],
        'Staff & Positions' => [
            ['Total Officials', $staff_total],
            ['Active Staff', $staff_active],
            ['Disabled Staff', $staff_disabled],
            ['Vacant Slots', $vacant],
        ],
        'Document Requests' => [
            ['Total Requests', $doc_total],
            ['Pending', $doc_pending],
            ['Approved', $doc_approved],
            ['Completed', $doc_completed],
            ['Rejected', $doc_rejected],
        ],
        'Complaints' => [
            ['Total Complaints', $comp_total],
            ['Pending', $comp_pending],
            ['Resolved', $comp_resolved],
            ['Resolution Rate', $compPct . '%'],
        ],
        'Community' => [
            ['Total Posts', $post_total],
            ['Posts Today', $post_today],
            ['Total Likes', $likes_total],
            ['Total Comments', $comments_total],
            ['Total Announcements', $ann_total],
            ['Active Announcements', $ann_active],
        ],
        'Audit Logs' => [
            ['Total Log Entries', $log_total],
            ['Logged Today', $log_today],
            ['Logged This Week', $log_week],
        ],
    ];

    $tables = '';
    foreach ($sections as $title => $rows) {
        $tables .= "<div class='sec'>
          <div class='sec-head'>$title</div>
          <table>
            <thead><tr><th>Metric</th><th>Value</th></tr></thead>
            <tbody>";
        foreach ($rows as $i => $row) {
            $bg = $i % 2 === 0 ? '#fff' : '#f9fafb';
            $tables .= "<tr style='background:$bg;'>
              <td>" . htmlspecialchars($row[0]) . "</td>
              <td style='font-weight:700;color:#051650;'>" . htmlspecialchars($row[1]) . "</td>
            </tr>";
        }
        $tables .= "</tbody></table></div>";
    }

    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>Barangay Alapan 1-A — Reports</title>
<style>
  @page { size: A4 portrait; margin: 14mm 12mm; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size:12px; color:#222; background:#fff; }
  .page-header { background:#051650; color:#fff; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
  .page-header h1 { font-size:16px; font-weight:700; }
  .page-header p { font-size:10px; opacity:.7; margin-top:3px; }
  .page-header-right { font-size:10px; opacity:.75; text-align:right; }
  .grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .sec { break-inside:avoid; }
  .sec-head { background:#051650; color:#fff; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:8px 10px; }
  table { width:100%; border-collapse:collapse; margin-bottom:4px; }
  thead th { background:#f0f3f9; font-size:10px; font-weight:700; color:#555; text-transform:uppercase; padding:6px 10px; text-align:left; border-bottom:1px solid #dde; }
  tbody td { padding:6px 10px; font-size:11px; border-bottom:1px solid #eee; }
  .footer { margin-top:16px; font-size:9px; color:#aaa; text-align:center; border-top:1px solid #eee; padding-top:8px; }
  @media print { body { -webkit-print-color-adjust:exact; print-color-adjust:exact; } }
</style>
</head>
<body>
<div class='page-header'>
  <div><h1>Barangay Alapan 1-A &mdash; System Reports</h1><p>Official summary of system-wide data</p></div>
  <div class='page-header-right'>Generated: $genDateFull</div>
</div>
<div class='grid'>$tables</div>
<div class='footer'>Barangay Alapan 1-A &mdash; Confidential &mdash; $genDateFull</div>
<script>window.onload=function(){window.print();};</script>
</body>
</html>";
    exit();
}

$excelUrl = 'superadminreports.php?export=excel';
$pdfUrl   = 'superadminreports.php?export=pdf';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reports — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <style>
    .rp-wrap{display:flex;flex-direction:column;gap:28px}

    .rp-page-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:4px}
    .rp-page-head h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 4px}
    .rp-page-head p{font-size:13px;color:var(--text-muted);margin:0}
    .rp-head-right{display:flex;align-items:center;gap:10px}
    .rp-date{font-size:12px;color:var(--text-muted);font-weight:600}

    .rp-section{display:flex;flex-direction:column;gap:14px}
    .rp-section-head{display:flex;align-items:center;gap:10px}
    .rp-section-icon{width:30px;height:30px;border-radius:8px;background:rgba(5,22,80,.07);color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
    .rp-section-head h3{font-size:13px;font-weight:700;color:var(--navy);margin:0;text-transform:uppercase;letter-spacing:.4px}
    .rp-section-line{flex:1;height:1px;background:var(--border)}

    .kpi-row{display:grid;gap:10px}
    .kpi-4{grid-template-columns:repeat(4,1fr)}
    .kpi-3{grid-template-columns:repeat(3,1fr)}
    .kpi-2{grid-template-columns:repeat(2,1fr)}

    .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px 20px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:1px;border-top:3px solid var(--navy)}
    .kpi-num{font-size:30px;font-weight:700;color:var(--navy);line-height:1;margin-top:4px}
    .kpi-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px;margin-top:4px}
    .kpi-note{font-size:11px;color:var(--text-muted);margin-top:3px}

    .chart-row{display:grid;gap:12px}
    .chart-row-2{grid-template-columns:1fr 1fr}
    .chart-row-3{grid-template-columns:2fr 1fr 1fr}

    .chart-panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column}
    .chart-panel-head{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .chart-panel-head h4{font-size:13px;font-weight:700;color:var(--navy);margin:0}
    .chart-panel-sub{font-size:11px;color:var(--text-muted)}
    .chart-panel-body{padding:16px 18px;flex:1;display:flex;align-items:center;justify-content:center}
    .chart-panel-body canvas{max-height:180px}
    .chart-panel-body.tall canvas{max-height:220px}

    .stat-table-panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);overflow:hidden}
    .stat-table-panel table{width:100%;border-collapse:collapse}
    .stat-table-panel thead th{padding:11px 16px;background:rgba(5,22,80,.03);font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.35px;border-bottom:1px solid var(--border);text-align:left}
    .stat-table-panel tbody td{padding:11px 16px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border)}
    .stat-table-panel tbody tr:last-child td{border-bottom:none}
    .stat-table-panel tbody tr:hover{background:rgba(5,22,80,.02)}
    .stat-val{font-weight:700;color:var(--navy);font-size:15px}

    .bar-track{height:6px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden;margin-top:4px}
    .bar-fill{height:100%;background:var(--navy);border-radius:999px}

    .pos-list{display:flex;flex-direction:column;gap:8px;padding:14px 18px;width:100%}
    .pos-item{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .pos-name{font-size:12px;font-weight:600;color:var(--navy)}
    .pos-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
    .pos-dot.on{background:var(--navy)}
    .pos-dot.off{background:#e5e7eb;border:1px solid #d1d5db}

    .ring-wrap{display:flex;flex-direction:column;align-items:center;gap:6px;padding:8px 0}
    .ring-meta{display:flex;gap:14px;margin-top:4px}
    .ring-meta-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted)}
    .ring-dot{width:8px;height:8px;border-radius:50%}

    .action-list{display:flex;flex-direction:column;gap:6px;width:100%;padding:0 18px 16px}
    .action-item{display:flex;flex-direction:column;gap:3px}
    .action-item-top{display:flex;justify-content:space-between;align-items:center}
    .action-item-name{font-size:11px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:72%}
    .action-item-cnt{font-size:11px;font-weight:700;color:var(--navy)}
    .action-track{height:5px;background:rgba(5,22,80,.07);border-radius:999px;overflow:hidden}
    .action-fill{height:100%;border-radius:999px;background:var(--navy)}

    .export-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:inherit;background:var(--navy);color:#fff;transition:opacity .2s}
    .export-btn:hover{opacity:.85}

    .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(5,22,80,.55);z-index:500;align-items:center;justify-content:center;padding:20px}
    .modal-backdrop.open{display:flex}
    .export-modal{background:#fff;border-radius:16px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 12px 48px rgba(5,22,80,.24)}
    .export-modal-head{padding:20px 24px 0}
    .export-modal-head h3{font-size:17px;font-weight:700;color:var(--navy);margin:0 0 6px}
    .export-modal-head p{font-size:13px;color:var(--text-muted);margin:0 0 20px;line-height:1.5}
    .export-choices{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:0 24px 24px}
    .export-choice{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 16px;border:2px solid var(--border);border-radius:12px;cursor:pointer;text-decoration:none;transition:all .18s}
    .export-choice:hover{border-color:var(--navy);background:rgba(5,22,80,.03);transform:translateY(-2px)}
    .export-choice-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
    .export-choice-icon.excel{background:rgba(5,22,80,.08);color:var(--navy)}
    .export-choice-icon.pdf{background:rgba(5,22,80,.08);color:var(--navy)}
    .export-choice-label{font-size:13px;font-weight:700;color:var(--navy)}
    .export-choice-sub{font-size:11px;color:var(--text-muted);text-align:center;line-height:1.4}
    .export-modal-foot{padding:0 24px 20px;display:flex;justify-content:center}
    .export-cancel{background:transparent;color:var(--text-muted);border:none;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;padding:8px 16px;border-radius:8px}
    .export-cancel:hover{color:var(--navy);background:rgba(5,22,80,.05)}

    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}

    @media(max-width:1100px){
      .kpi-4{grid-template-columns:repeat(2,1fr)}
      .chart-row-3{grid-template-columns:1fr 1fr}
    }
    @media(max-width:720px){
      .kpi-4,.kpi-3,.kpi-2,.chart-row-2,.chart-row-3{grid-template-columns:1fr}
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
      <button class="btn-cancel-lo" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-confirm-lo"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="exportModal">
  <div class="export-modal">
    <div class="export-modal-head">
      <h3>Export Report</h3>
      <p>Download a summary of all system-wide statistics.</p>
    </div>
    <div class="export-choices">
      <a href="<?= htmlspecialchars($excelUrl) ?>" class="export-choice" target="_blank">
        <div class="export-choice-icon excel"><i class="fa-solid fa-file-excel"></i></div>
        <span class="export-choice-label">Excel</span>
        <span class="export-choice-sub">Download as .xls — opens in Excel or Google Sheets</span>
      </a>
      <a href="<?= htmlspecialchars($pdfUrl) ?>" class="export-choice" target="_blank">
        <div class="export-choice-icon pdf"><i class="fa-solid fa-file-pdf"></i></div>
        <span class="export-choice-label">PDF</span>
        <span class="export-choice-sub">Opens print-ready page — save as PDF from your browser</span>
      </a>
    </div>
    <div class="export-modal-foot">
      <button class="export-cancel" onclick="closeExportModal()"><i class="fa-solid fa-xmark" style="margin-right:5px;"></i>Cancel</button>
    </div>
  </div>
</div>

<div class="superadmin-page">
  <header class="superadmin-header">
    <div class="superadmin-brand"><h1>Barangay Alapan 1-A</h1><p>Super Admin</p></div>
    <nav class="superadmin-nav">
      <a href="superadmindashboard.php">Dashboard</a>
      <a href="superadminstaffaccount.php">Staff Accounts</a>
      <a href="superadminresidentaccount.php">Residents</a>
      <a href="superadminannouncement.php">Announcements</a>
      <a href="superadminreports.php" class="active">Reports</a>
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
  <div class="rp-wrap">

    <div class="rp-page-head">
      <div>
        <h2>Reports &amp; Analytics</h2>
        <p>System-wide overview — data updated in real time.</p>
      </div>
      <div class="rp-head-right">
        <span class="rp-date"><i class="fa-regular fa-calendar" style="margin-right:6px;"></i><?= $genDate ?></span>
        <button class="export-btn" onclick="openExportModal()">
          <i class="fa-solid fa-file-export"></i> Export
        </button>
      </div>
    </div>

    <!-- ══ RESIDENTS ══ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon"><i class="fa-solid fa-users"></i></div>
        <h3>Residents</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card">
          <span class="kpi-label">Total Residents</span>
          <span class="kpi-num"><?= $res_total ?></span>
          <span class="kpi-note"><?= $res_month ?> joined this month</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Verified</span>
          <span class="kpi-num"><?= $res_active ?></span>
          <span class="kpi-note">Active accounts</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Pending</span>
          <span class="kpi-num"><?= $res_pending ?></span>
          <span class="kpi-note">Awaiting verification</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Disabled / Rejected</span>
          <span class="kpi-num"><?= $res_disabled + $res_rejected ?></span>
          <span class="kpi-note"><?= $res_disabled ?> disabled &middot; <?= $res_rejected ?> rejected</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>New Registrations — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $res_week ?> this week &middot; <?= $res_today ?> today</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="regTrendChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Account Status Breakdown</h4>
            <span class="chart-panel-sub"><?= $res_total ?> total accounts</span>
          </div>
          <div class="chart-panel-body"><canvas id="resStatusChart"></canvas></div>
        </div>
      </div>

      <div class="stat-table-panel">
        <table>
          <thead><tr><th>Status</th><th>Count</th><th style="width:60%;">Distribution</th></tr></thead>
          <tbody>
            <?php
              $rStats = ['Verified'=>$res_active,'Pending'=>$res_pending,'Disabled'=>$res_disabled,'Rejected'=>$res_rejected];
              $rMax = max($rStats) ?: 1;
              foreach ($rStats as $label => $val):
                $pct = round(($val/$rMax)*100);
            ?>
            <tr>
              <td><?= $label ?></td>
              <td class="stat-val"><?= $val ?></td>
              <td><div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ STAFF ══ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Staff &amp; Positions</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-3">
        <div class="kpi-card">
          <span class="kpi-label">Total Officials</span>
          <span class="kpi-num"><?= $staff_total ?></span>
          <span class="kpi-note">Assigned barangay staff</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Active</span>
          <span class="kpi-num"><?= $staff_active ?></span>
          <span class="kpi-note">Enabled accounts</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Vacant Slots</span>
          <span class="kpi-num"><?= $vacant ?></span>
          <span class="kpi-note">Unfilled positions</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Staff Account Status</h4>
            <span class="chart-panel-sub"><?= $staff_total ?> total</span>
          </div>
          <div class="chart-panel-body"><canvas id="staffStatusChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Position Occupancy</h4>
            <span class="chart-panel-sub">Key positions</span>
          </div>
          <div class="pos-list">
            <?php foreach ($positions as $pos): ?>
            <div class="pos-item">
              <span class="pos-name"><?= htmlspecialchars($pos) ?></span>
              <span class="pos-dot <?= $posData[$pos]>0 ? 'on' : 'off' ?>"></span>
            </div>
            <?php endforeach; ?>
            <div class="pos-item" style="margin-top:6px;">
              <span class="pos-name">Kagawad (<?= $kagawad_filled ?>/7)</span>
              <div style="flex:1;margin:0 10px;">
                <div class="bar-track"><div class="bar-fill" style="width:<?= round(($kagawad_filled/7)*100) ?>%;"></div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ DOCUMENTS & COMPLAINTS ══ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon"><i class="fa-solid fa-file-lines"></i></div>
        <h3>Document Requests &amp; Complaints</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card">
          <span class="kpi-label">Total Requests</span>
          <span class="kpi-num"><?= $doc_total ?></span>
          <span class="kpi-note"><?= $doc_pending ?> still pending</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Completed</span>
          <span class="kpi-num"><?= $doc_completed ?></span>
          <span class="kpi-note"><?= $doc_approved ?> approved</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Complaints</span>
          <span class="kpi-num"><?= $comp_total ?></span>
          <span class="kpi-note"><?= $comp_pending ?> unresolved</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Resolution Rate</span>
          <span class="kpi-num"><?= $compPct ?>%</span>
          <span class="kpi-note"><?= $comp_resolved ?> of <?= $comp_total ?> resolved</span>
        </div>
      </div>

      <div class="chart-row chart-row-3">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Document Requests — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $doc_total ?> total requests</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="docTrendChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head"><h4>Request Status</h4></div>
          <div class="chart-panel-body"><canvas id="docStatusChart"></canvas></div>
        </div>
        <div class="chart-panel">
          <div class="chart-panel-head"><h4>Complaint Resolution</h4></div>
          <div class="chart-panel-body">
            <div class="ring-wrap">
              <canvas id="compChart" style="max-height:130px;"></canvas>
              <div class="ring-meta">
                <div class="ring-meta-item"><span class="ring-dot" style="background:var(--navy);"></span><?= $comp_resolved ?> Resolved</div>
                <div class="ring-meta-item"><span class="ring-dot" style="background:rgba(5,22,80,.25);"></span><?= $comp_pending ?> Pending</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="stat-table-panel">
        <table>
          <thead><tr><th>Request Status</th><th>Count</th><th style="width:60%;">Distribution</th></tr></thead>
          <tbody>
            <?php
              $dStats = ['Pending'=>$doc_pending,'Approved'=>$doc_approved,'Completed'=>$doc_completed,'Rejected'=>$doc_rejected];
              $dMax = max($dStats) ?: 1;
              foreach ($dStats as $lbl => $val):
                $pct = round(($val/$dMax)*100);
            ?>
            <tr>
              <td><?= $lbl ?></td>
              <td class="stat-val"><?= $val ?></td>
              <td><div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($docTypes)): ?>
      <div class="chart-panel">
        <div class="chart-panel-head">
          <h4>Most Requested Document Types</h4>
          <span class="chart-panel-sub">Top <?= count($docTypes) ?> types</span>
        </div>
        <div class="chart-panel-body tall"><canvas id="docTypeChart"></canvas></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ COMMUNITY ══ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon"><i class="fa-solid fa-comments"></i></div>
        <h3>Community</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-4">
        <div class="kpi-card">
          <span class="kpi-label">Posts</span>
          <span class="kpi-num"><?= $post_total ?></span>
          <span class="kpi-note"><?= $post_today ?> posted today</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Total Likes</span>
          <span class="kpi-num"><?= $likes_total ?></span>
          <span class="kpi-note">Across all posts</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Comments</span>
          <span class="kpi-num"><?= $comments_total ?></span>
          <span class="kpi-note">Across all posts</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Announcements</span>
          <span class="kpi-num"><?= $ann_total ?></span>
          <span class="kpi-note"><?= $ann_active ?> currently active</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Engagement Overview</h4>
            <span class="chart-panel-sub">Posts · Likes · Comments · Announcements</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="communityChart"></canvas></div>
        </div>
        <div class="stat-table-panel" style="border-radius:12px;">
          <table>
            <thead><tr><th>Metric</th><th>Count</th><th style="width:50%;">Share</th></tr></thead>
            <tbody>
              <?php
                $cStats = ['Posts'=>$post_total,'Likes'=>$likes_total,'Comments'=>$comments_total,'Announcements'=>$ann_total];
                $cMax = max($cStats) ?: 1;
                foreach ($cStats as $lbl => $val):
                  $pct = round(($val/$cMax)*100);
              ?>
              <tr>
                <td><?= $lbl ?></td>
                <td class="stat-val"><?= $val ?></td>
                <td><div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ AUDIT LOGS ══ -->
    <div class="rp-section">
      <div class="rp-section-head">
        <div class="rp-section-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        <h3>Audit Logs</h3>
        <div class="rp-section-line"></div>
      </div>

      <div class="kpi-row kpi-3">
        <div class="kpi-card">
          <span class="kpi-label">Total Log Entries</span>
          <span class="kpi-num"><?= $log_total ?></span>
          <span class="kpi-note">All recorded actions</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">Today</span>
          <span class="kpi-num"><?= $log_today ?></span>
          <span class="kpi-note">Actions logged today</span>
        </div>
        <div class="kpi-card">
          <span class="kpi-label">This Week</span>
          <span class="kpi-num"><?= $log_week ?></span>
          <span class="kpi-note">Last 7 days</span>
        </div>
      </div>

      <div class="chart-row chart-row-2">
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Activity — Last 7 Days</h4>
            <span class="chart-panel-sub"><?= $log_week ?> actions this week</span>
          </div>
          <div class="chart-panel-body tall"><canvas id="logTrendChart"></canvas></div>
        </div>
        <?php if (!empty($actionLabels)): ?>
        <div class="chart-panel">
          <div class="chart-panel-head">
            <h4>Actions Breakdown</h4>
            <span class="chart-panel-sub"><?= count($actionLabels) ?> distinct action types</span>
          </div>
          <div class="action-list">
            <?php
              $aMax = max($actionCounts) ?: 1;
              foreach ($actionLabels as $i => $al):
                $pct = round(($actionCounts[$i]/$aMax)*100);
            ?>
            <div class="action-item">
              <div class="action-item-top">
                <span class="action-item-name" title="<?= htmlspecialchars($al) ?>"><?= htmlspecialchars($al) ?></span>
                <span class="action-item-cnt"><?= $actionCounts[$i] ?></span>
              </div>
              <div class="action-track"><div class="action-fill" style="width:<?= $pct ?>%;"></div></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
  </main>
</div>

<script>
Chart.defaults.font.family = "'DM Sans','Segoe UI',Arial,sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b7a99';

var navy = '#051650';

function lineOpts(labels, data) {
  return {
    type:'line',
    data:{
      labels:labels,
      datasets:[{
        data:data,
        borderColor:navy,
        backgroundColor:'rgba(5,22,80,0.07)',
        borderWidth:2,
        pointRadius:3,
        pointBackgroundColor:navy,
        fill:true,
        tension:0.4
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:true,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false},ticks:{maxRotation:0}},
        y:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}}
      }
    }
  };
}

function doughnutOpts(labels, data, colors) {
  return {
    type:'doughnut',
    data:{labels:labels,datasets:[{data:data,backgroundColor:colors,borderWidth:0,hoverOffset:4}]},
    options:{
      responsive:true,maintainAspectRatio:true,cutout:'68%',
      plugins:{legend:{position:'bottom',labels:{boxWidth:10,padding:10,font:{size:10}}}}
    }
  };
}

new Chart('regTrendChart', lineOpts(<?= json_encode($regLabels) ?>, <?= json_encode($regData) ?>));

new Chart('resStatusChart', doughnutOpts(
  ['Verified','Pending','Disabled','Rejected'],
  [<?= $res_active ?>,<?= $res_pending ?>,<?= $res_disabled ?>,<?= $res_rejected ?>],
  [navy,'rgba(5,22,80,.45)','rgba(5,22,80,.2)','rgba(5,22,80,.1)']
));

new Chart('staffStatusChart', doughnutOpts(
  ['Active','Disabled'],
  [<?= $staff_active ?>,<?= $staff_disabled ?>],
  [navy,'rgba(5,22,80,.15)']
));

new Chart('docTrendChart', lineOpts(<?= json_encode($docTrendLabels) ?>, <?= json_encode($docTrendData) ?>));

new Chart('docStatusChart', doughnutOpts(
  ['Pending','Approved','Completed','Rejected'],
  [<?= $doc_pending ?>,<?= $doc_approved ?>,<?= $doc_completed ?>,<?= $doc_rejected ?>],
  [navy,'rgba(5,22,80,.55)','rgba(5,22,80,.3)','rgba(5,22,80,.12)']
));

new Chart('compChart', doughnutOpts(
  ['Resolved','Pending'],
  [<?= $comp_resolved ?>,<?= $comp_pending ?>],
  [navy,'rgba(5,22,80,.2)']
));

<?php if (!empty($docTypes)): ?>
new Chart('docTypeChart', {
  type:'bar',
  data:{
    labels:<?= json_encode($docTypes) ?>,
    datasets:[{
      data:<?= json_encode($docTypeCounts) ?>,
      backgroundColor:navy,
      borderRadius:6,
      barThickness:28
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:true,indexAxis:'y',
    plugins:{legend:{display:false}},
    scales:{
      x:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}},
      y:{grid:{display:false}}
    }
  }
});
<?php endif; ?>

new Chart('communityChart', {
  type:'bar',
  data:{
    labels:['Posts','Likes','Comments','Announcements'],
    datasets:[{
      data:[<?= $post_total ?>,<?= $likes_total ?>,<?= $comments_total ?>,<?= $ann_total ?>],
      backgroundColor:[navy,'rgba(5,22,80,.6)','rgba(5,22,80,.35)','rgba(5,22,80,.15)'],
      borderRadius:6,
      barThickness:40
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:true,
    plugins:{legend:{display:false}},
    scales:{
      x:{grid:{display:false}},
      y:{grid:{color:'rgba(5,22,80,.06)'},beginAtZero:true,ticks:{precision:0}}
    }
  }
});

new Chart('logTrendChart', lineOpts(<?= json_encode($logTrendLabels) ?>, <?= json_encode($logTrendData) ?>));

function openExportModal()  { document.getElementById('exportModal').classList.add('open'); }
function closeExportModal() { document.getElementById('exportModal').classList.remove('open'); }
document.getElementById('exportModal').addEventListener('click',function(e){if(e.target===this)closeExportModal();});

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogout();});
</script>
</body>
</html>