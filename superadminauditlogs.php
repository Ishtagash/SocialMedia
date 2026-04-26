<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
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
    ? htmlspecialchars(rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME']))
    : 'Super Admin';

/* ── FILTERS ── */
$filterAction = trim($_GET['action'] ?? '');
$filterUser   = trim($_GET['user']   ?? '');
$filterDate   = trim($_GET['date']   ?? '');
$isExport     = trim($_GET['export'] ?? '');

/* ── BUILD QUERY ── */
$params = [];
$sql = "SELECT L.LOG_ID, L.USER_ID, L.ACTION, L.DETAILS, L.CREATED_AT,
               U.EMAIL, U.ROLE,
               R.FIRST_NAME, R.LAST_NAME, U.USERNAME
        FROM AUDIT_LOGS L
        LEFT JOIN USERS U ON U.USER_ID = L.USER_ID
        LEFT JOIN REGISTRATION R ON R.USER_ID = L.USER_ID
        WHERE 1=1";

if ($filterAction) { $sql .= " AND L.ACTION = ?";       $params[] = $filterAction; }
if ($filterUser)   { $sql .= " AND L.USER_ID = ?";      $params[] = (int)$filterUser; }
if ($filterDate)   { $sql .= " AND CAST(L.CREATED_AT AS DATE) = ?"; $params[] = $filterDate; }

$sql .= " ORDER BY L.CREATED_AT DESC";

$result = sqlsrv_query($conn, $sql, $params ?: []);
$logs   = [];
if ($result) {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $fn   = rtrim($row['FIRST_NAME'] ?? '');
        $ln   = rtrim($row['LAST_NAME']  ?? '');
        $name = trim("$fn $ln") ?: rtrim($row['USERNAME'] ?? 'Unknown');
        $role = rtrim($row['ROLE'] ?? '');
        $role = $role === 'superadmin' ? 'Superadmin' : ucfirst($role);

        $ts = '';
        if ($row['CREATED_AT'] instanceof DateTime) {
            $ts = $row['CREATED_AT']->format('Y-m-d H:i:s');
        } elseif (!empty($row['CREATED_AT'])) {
            $ts = date('Y-m-d H:i:s', strtotime($row['CREATED_AT']));
        }

        $logs[] = [
            'id'      => (int)$row['LOG_ID'],
            'userId'  => (int)$row['USER_ID'],
            'name'    => $name,
            'email'   => rtrim($row['EMAIL']   ?? ''),
            'role'    => $role,
            'action'  => rtrim($row['ACTION']  ?? ''),
            'details' => rtrim($row['DETAILS'] ?? ''),
            'ts'      => $ts,
            'initials'=> strtoupper(substr($fn ?: ($row['USERNAME'] ?? '?'), 0, 1) . substr($ln, 0, 1)),
        ];
    }
}

/* ── EXPORT: EXCEL (SpreadsheetML — proper columns) ── */
if ($isExport === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    $xe = function($v) { return htmlspecialchars((string)$v, ENT_XML1, 'UTF-8'); };

    $cols = ['#', 'Name', 'Email', 'Role', 'Action', 'Details', 'Timestamp'];
    $colWidths = [30, 120, 160, 70, 140, 300, 130];

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:x="urn:schemas-microsoft-com:office:excel">
    <Styles>
        <Style ss:ID="header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Arial"/>
            <Interior ss:Color="#051650" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCFF00"/>
            </Borders>
        </Style>
        <Style ss:ID="rowEven">
            <Font ss:Size="10" ss:FontName="Arial" ss:Color="#0d1b3e"/>
            <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
            <Alignment ss:Vertical="Top" ss:WrapText="1"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="rowOdd">
            <Font ss:Size="10" ss:FontName="Arial" ss:Color="#0d1b3e"/>
            <Interior ss:Color="#F0F3F9" ss:Pattern="Solid"/>
            <Alignment ss:Vertical="Top" ss:WrapText="1"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="rowNum">
            <Font ss:Size="10" ss:FontName="Arial" ss:Color="#6b7a99"/>
            <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="rowNumOdd">
            <Font ss:Size="10" ss:FontName="Arial" ss:Color="#6b7a99"/>
            <Interior ss:Color="#F0F3F9" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="title">
            <Font ss:Bold="1" ss:Size="14" ss:FontName="Arial" ss:Color="#051650"/>
        </Style>
        <Style ss:ID="meta">
            <Font ss:Size="10" ss:FontName="Arial" ss:Color="#6b7a99"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="Audit Logs">
        <Table ss:DefaultRowHeight="18">';

    foreach ($colWidths as $w) {
        echo '<Column ss:Width="' . $w . '"/>';
    }

    echo '<Row ss:Height="28">
            <Cell ss:MergeAcross="6" ss:StyleID="title">
                <Data ss:Type="String">Barangay Alapan 1-A — Audit Logs</Data>
            </Cell>
          </Row>
          <Row ss:Height="16">
            <Cell ss:MergeAcross="6" ss:StyleID="meta">
                <Data ss:Type="String">Generated: ' . date('F j, Y g:i A') . ' | Total Records: ' . count($logs) . '</Data>
            </Cell>
          </Row>
          <Row ss:Height="6"><Cell><Data ss:Type="String"></Data></Cell></Row>';

    echo '<Row ss:Height="22">';
    foreach ($cols as $c) {
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">' . $xe($c) . '</Data></Cell>';
    }
    echo '</Row>';

    foreach ($logs as $i => $l) {
        $isOdd   = $i % 2 !== 0;
        $numSt   = $isOdd ? 'rowNumOdd' : 'rowNum';
        $dataSt  = $isOdd ? 'rowOdd'    : 'rowEven';
        $detail  = str_replace(["\r","\n","\t"], ' ', $l['details']);
        echo '<Row ss:AutoFitHeight="1">';
        echo '<Cell ss:StyleID="'.$numSt.'"><Data ss:Type="Number">'.($i+1).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($l['name']).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($l['email']).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($l['role']).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($l['action']).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($detail).'</Data></Cell>';
        echo '<Cell ss:StyleID="'.$dataSt.'"><Data ss:Type="String">'.$xe($l['ts']).'</Data></Cell>';
        echo '</Row>';
    }

    echo '</Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <FreezePanes/>
            <FrozenNoSplit/>
            <SplitHorizontal>4</SplitHorizontal>
            <TopRowBottomPane>4</TopRowBottomPane>
            <ActivePane>2</ActivePane>
            <Print>
                <FitWidth>1</FitWidth>
                <ValidPrinterInfo/>
                <PaperSizeIndex>9</PaperSizeIndex>
                <HorizontalResolution>600</HorizontalResolution>
                <VerticalResolution>600</VerticalResolution>
            </Print>
        </WorksheetOptions>
    </Worksheet>
</Workbook>';
    exit();
}

/* ── EXPORT: PDF (auto print dialog) ── */
if ($isExport === 'pdf') {
    $title    = 'Barangay Alapan 1-A — Audit Logs';
    $genDate  = date('F j, Y g:i A');
    $total    = count($logs);

    $badgeColor = function($action) {
        $map = [
            'Assign Position'        => ['#e8f5e9','#2e7d32'],
            'Change Position'        => ['#e3f2fd','#1565c0'],
            'Revert to Resident'     => ['#fff3e0','#e65100'],
            'Remove Position'        => ['#fce4ec','#c62828'],
            'Enabled Staff Account'  => ['#e8f5e9','#2e7d32'],
            'Disabled Staff Account' => ['#fce4ec','#c62828'],
            'Verify Resident'        => ['#e8f5e9','#2e7d32'],
            'Reject Resident'        => ['#fce4ec','#c62828'],
            'Login'                  => ['#f3e5f5','#6a1b9a'],
        ];
        return $map[$action] ?? ['#f5f5f5','#333333'];
    };

    $rows = '';
    foreach ($logs as $i => $l) {
        $bg  = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
        [$badgeBg, $badgeFg] = $badgeColor($l['action']);
        $detail = htmlspecialchars($l['details']);
        $rows .= "
        <tr style='background:$bg;'>
          <td style='padding:7px 8px;color:#888;font-size:10px;text-align:center;'>" . ($i+1) . "</td>
          <td style='padding:7px 8px;'>
            <strong style='font-size:11px;color:#0d1b3e;display:block;'>" . htmlspecialchars($l['name']) . "</strong>
            <span style='font-size:10px;color:#888;'>" . htmlspecialchars($l['email']) . "</span>
          </td>
          <td style='padding:7px 8px;font-size:11px;color:#555;'>" . htmlspecialchars($l['role']) . "</td>
          <td style='padding:7px 8px;'>
            <span style='display:inline-block;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;background:$badgeBg;color:$badgeFg;white-space:nowrap;'>"
              . htmlspecialchars($l['action']) . "</span>
          </td>
          <td style='padding:7px 8px;font-size:10px;color:#444;word-break:break-word;'>$detail</td>
          <td style='padding:7px 8px;font-size:10px;color:#666;white-space:nowrap;'>" . htmlspecialchars($l['ts']) . "</td>
        </tr>";
    }

    $filterNote = '';
    if ($filterAction) $filterNote .= " &bull; Action: <strong>" . htmlspecialchars($filterAction) . "</strong>";
    if ($filterDate)   $filterNote .= " &bull; Date: <strong>" . htmlspecialchars($filterDate) . "</strong>";

    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>$title</title>
<style>
  @page { size: A4 landscape; margin: 14mm 12mm; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size:12px; color:#222; background:#fff; }
  .header { background:#051650; color:#fff; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; }
  .header-left h1 { font-size:16px; font-weight:700; }
  .header-left p  { font-size:10px; opacity:.7; margin-top:3px; }
  .header-right   { font-size:10px; opacity:.75; text-align:right; }
  .meta { padding:8px 20px; background:#f0f3f9; border-bottom:1px solid #dde; font-size:10px; color:#555; }
  table { width:100%; border-collapse:collapse; margin-top:0; }
  thead tr { background:#051650; }
  thead th { padding:8px 8px; text-align:left; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#fff; }
  tbody td { border-bottom:1px solid #eee; vertical-align:top; }
  .footer { margin-top:12px; font-size:9px; color:#aaa; text-align:center; border-top:1px solid #eee; padding-top:8px; }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class='header'>
  <div class='header-left'>
    <h1>Barangay Alapan 1-A &mdash; Audit Logs</h1>
    <p>Official system activity record</p>
  </div>
  <div class='header-right'>
    Generated: $genDate<br>
    Total Records: <strong>$total</strong>
  </div>
</div>
" . ($filterNote ? "<div class='meta'>Filters applied: $filterNote</div>" : "") . "
<table>
  <thead>
    <tr>
      <th style='width:32px;'>#</th>
      <th style='width:160px;'>User</th>
      <th style='width:70px;'>Role</th>
      <th style='width:120px;'>Action</th>
      <th>Details</th>
      <th style='width:120px;'>Timestamp</th>
    </tr>
  </thead>
  <tbody>$rows</tbody>
</table>
<div class='footer'>Barangay Alapan 1-A &mdash; Confidential &mdash; $genDate</div>
<script>
  window.onload = function() {
    window.print();
  };
</script>
</body>
</html>";
    exit();
}

/* ── DISTINCT ACTIONS FOR FILTER DROPDOWN ── */
$actionsRes = sqlsrv_query($conn, "SELECT DISTINCT ACTION FROM AUDIT_LOGS ORDER BY ACTION ASC");
$actions    = [];
if ($actionsRes) { while ($r = sqlsrv_fetch_array($actionsRes, SQLSRV_FETCH_ASSOC)) { $actions[] = rtrim($r['ACTION']); } }

/* ── USERS FOR FILTER DROPDOWN ── */
$usersRes = sqlsrv_query($conn,
    "SELECT DISTINCT L.USER_ID, R.FIRST_NAME, R.LAST_NAME, U.USERNAME
     FROM AUDIT_LOGS L
     LEFT JOIN USERS U ON U.USER_ID = L.USER_ID
     LEFT JOIN REGISTRATION R ON R.USER_ID = L.USER_ID
     ORDER BY R.LAST_NAME ASC, R.FIRST_NAME ASC"
);
$usersList = [];
if ($usersRes) {
    while ($r = sqlsrv_fetch_array($usersRes, SQLSRV_FETCH_ASSOC)) {
        $fn   = rtrim($r['FIRST_NAME'] ?? '');
        $ln   = rtrim($r['LAST_NAME']  ?? '');
        $name = trim("$fn $ln") ?: rtrim($r['USERNAME'] ?? 'User #' . $r['USER_ID']);
        $usersList[] = ['id' => (int)$r['USER_ID'], 'name' => $name];
    }
}

$totalLogs = count($logs);

/* ── ACTION BADGE COLOURS ── */
function actionBadge($action) {
    $map = [
        'Assign Position'        => 'badge-green',
        'Change Position'        => 'badge-blue',
        'Revert to Resident'     => 'badge-orange',
        'Remove Position'        => 'badge-red',
        'Enabled Staff Account'  => 'badge-green',
        'Disabled Staff Account' => 'badge-red',
        'Verify Resident'        => 'badge-green',
        'Reject Resident'        => 'badge-red',
        'Login'                  => 'badge-purple',
    ];
    return $map[$action] ?? 'badge-gray';
}

/* build export URL preserving current filters */
function exportUrl($type, $fa, $fu, $fd) {
    $p = ['export' => $type];
    if ($fa) $p['action'] = $fa;
    if ($fu) $p['user']   = $fu;
    if ($fd) $p['date']   = $fd;
    return 'superadminauditlogs.php?' . http_build_query($p);
}
$excelUrl = exportUrl('excel', $filterAction, $filterUser, $filterDate);
$pdfUrl   = exportUrl('pdf',   $filterAction, $filterUser, $filterDate);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Audit Logs — Barangay Alapan 1-A</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="base.css"/>
  <link rel="stylesheet" href="superadmin.css"/>
  <style>
    .audit-wrap{display:flex;flex-direction:column;gap:20px}
    .audit-header-row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .audit-title h2{font-size:22px;font-weight:700;color:var(--navy);margin:0 0 4px}
    .audit-title p{font-size:13px;color:var(--text-muted);margin:0}
    .audit-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
    .audit-filters{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(5,22,80,.02);flex-wrap:wrap}
    .filter-group{display:flex;flex-direction:column;gap:4px}
    .filter-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.3px}
    .filter-select,.filter-input{height:38px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;outline:none;min-width:160px}
    .filter-input[type="date"]{min-width:150px}
    .filter-btns{display:flex;gap:8px;align-items:flex-end;margin-left:auto}
    .audit-table-wrap{width:100%;overflow-x:auto}
    .audit-empty{padding:48px;text-align:center;color:var(--text-muted);font-size:14px}
    .log-user{display:flex;align-items:center;gap:10px}
    .log-avatar{width:34px;height:34px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
    .log-name{font-size:13px;font-weight:700;color:var(--navy)}
    .log-role{font-size:11px;color:var(--text-muted)}
    .log-email{font-size:12px;color:var(--text-muted)}
    .log-details{font-size:12px;color:var(--text-muted);max-width:300px;word-break:break-word}

    /* ACTION BADGES */
    .action-badge{display:inline-flex;align-items:center;height:22px;padding:0 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
    .badge-green {background:rgba(34,197,94,.12);color:#166534}
    .badge-blue  {background:rgba(59,130,246,.12);color:#1e40af}
    .badge-orange{background:rgba(245,158,11,.12);color:#92400e}
    .badge-red   {background:rgba(255,77,77,.1);color:#991b1b}
    .badge-purple{background:rgba(139,92,246,.1);color:#5b21b6}
    .badge-gray  {background:rgba(107,114,128,.1);color:#374151}

    /* EXPORT BUTTON */
    .export-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:inherit;background:var(--navy);color:#fff;transition:opacity .2s}
    .export-btn:hover{opacity:.88}

    /* EXPORT MODAL */
    .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(5,22,80,.55);z-index:500;align-items:center;justify-content:center;padding:20px}
    .modal-backdrop.open{display:flex}
    .export-modal{background:#fff;border-radius:16px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 12px 48px rgba(5,22,80,.24)}
    .export-modal-head{padding:20px 24px 0;border-bottom:none}
    .export-modal-head h3{font-size:17px;font-weight:700;color:var(--navy);margin:0 0 6px}
    .export-modal-head p{font-size:13px;color:var(--text-muted);margin:0 0 20px;line-height:1.5}
    .export-choices{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:0 24px 24px}
    .export-choice{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 16px;border:2px solid var(--border);border-radius:12px;cursor:pointer;text-decoration:none;transition:all .18s}
    .export-choice:hover{border-color:var(--navy);background:rgba(5,22,80,.03);transform:translateY(-2px)}
    .export-choice-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
    .export-choice-icon.excel{background:rgba(34,197,94,.12);color:#166534}
    .export-choice-icon.pdf  {background:rgba(239,68,68,.1);color:#991b1b}
    .export-choice-label{font-size:13px;font-weight:700;color:var(--navy)}
    .export-choice-sub{font-size:11px;color:var(--text-muted);text-align:center;line-height:1.4}
    .export-modal-foot{padding:0 24px 20px;display:flex;justify-content:center}
    .export-cancel{background:transparent;color:var(--text-muted);border:none;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;padding:8px 16px;border-radius:8px}
    .export-cancel:hover{color:var(--navy);background:rgba(5,22,80,.05)}

    /* LOGOUT */
    .logout-overlay{position:fixed;inset:0;z-index:2000;background:rgba(5,22,80,.65);display:none;align-items:center;justify-content:center}
    .logout-overlay.open{display:flex}
    .logout-box{background:#fff;border-radius:12px;padding:36px 32px;max-width:380px;width:90%;text-align:center;border-top:4px solid var(--lime);box-shadow:0 16px 48px rgba(5,22,80,.28)}
    .logout-icon{width:56px;height:56px;border-radius:50%;background:var(--navy);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px}
    .logout-box h3{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .logout-box p{font-size:14px;color:#666;margin-bottom:24px;line-height:1.6}
    .logout-btns{display:flex;gap:10px;justify-content:center}
    .btn-confirm-lo{background:var(--navy);color:var(--lime);border:none;padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-cancel-lo{background:transparent;color:var(--navy);border:1px solid rgba(5,22,80,.25);padding:11px 28px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    @media(max-width:768px){.audit-filters{flex-direction:column;align-items:stretch}.filter-btns{margin-left:0}.export-choices{grid-template-columns:1fr}}
  </style>
</head>
<body class="superadmin-body">

<!-- LOGOUT -->
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

<!-- EXPORT MODAL -->
<div class="modal-backdrop" id="exportModal">
  <div class="export-modal">
    <div class="export-modal-head">
      <h3>Export Audit Logs</h3>
      <p>Choose a format to download the current log list<?= ($filterAction || $filterDate || $filterUser) ? ' (with active filters applied)' : '' ?>.</p>
    </div>
    <div class="export-choices">
      <a href="<?= htmlspecialchars($excelUrl) ?>" class="export-choice" target="_blank">
        <div class="export-choice-icon excel"><i class="fa-solid fa-file-excel"></i></div>
        <span class="export-choice-label">Excel</span>
        <span class="export-choice-sub">Download as .xls file, opens in Excel or Google Sheets</span>
      </a>
      <a href="<?= htmlspecialchars($pdfUrl) ?>" class="export-choice" target="_blank">
        <div class="export-choice-icon pdf"><i class="fa-solid fa-file-pdf"></i></div>
        <span class="export-choice-label">PDF</span>
        <span class="export-choice-sub">Opens a print-ready page — save as PDF from your browser</span>
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
      <a href="superadminreports.php">Reports</a>
      <a href="superadminauditlogs.php" class="active">Audit Logs</a>
    </nav>
    <div class="superadmin-header-right">
      <div class="superadmin-user"><div class="superadmin-user-info">
        <span class="superadmin-user-name"><?= $displayName ?></span>
      </div></div>
      <a href="#" class="superadmin-logout" onclick="openLogout();return false;">Logout</a>
    </div>
  </header>

  <main class="superadmin-content">
    <div class="audit-wrap">

      <div class="audit-header-row">
        <div class="audit-title">
          <h2>Audit Logs</h2>
          <p><?= $totalLogs ?> total log<?= $totalLogs !== 1 ? 's' : '' ?> recorded<?= ($filterAction || $filterDate || $filterUser) ? ' (filtered)' : '' ?></p>
        </div>
        <button class="export-btn" onclick="openExportModal()">
          <i class="fa-solid fa-file-export"></i> Export
        </button>
      </div>

      <div class="audit-panel">
        <form method="GET">
          <div class="audit-filters">
            <div class="filter-group">
              <span class="filter-label">Action</span>
              <select class="filter-select" name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-group">
              <span class="filter-label">User</span>
              <select class="filter-select" name="user">
                <option value="">All Users</option>
                <?php foreach ($usersList as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)$filterUser === $u['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($u['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-group">
              <span class="filter-label">Date</span>
              <input type="date" class="filter-input" name="date" value="<?= htmlspecialchars($filterDate) ?>">
            </div>
            <div class="filter-btns">
              <button type="submit" class="superadmin-primary-btn" style="min-height:38px;padding:0 16px;">
                <i class="fa-solid fa-filter"></i> Apply
              </button>
              <?php if ($filterAction || $filterUser || $filterDate): ?>
              <a href="superadminauditlogs.php" class="superadmin-outline-btn" style="min-height:38px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                <i class="fa-solid fa-xmark"></i> Clear
              </a>
              <?php endif; ?>
            </div>
          </div>
        </form>

        <div class="audit-table-wrap">
          <?php if (empty($logs)): ?>
          <div class="audit-empty">
            <i class="fa-solid fa-clipboard-list" style="font-size:32px;opacity:.3;margin-bottom:12px;display:block;"></i>
            No audit logs found<?= ($filterAction || $filterDate || $filterUser) ? ' for the selected filters' : '' ?>.
          </div>
          <?php else: ?>
          <table class="superadmin-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Action</th>
                <th>Details</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td>
                  <div class="log-user">
                    <div class="log-avatar"><?= htmlspecialchars($log['initials']) ?></div>
                    <div>
                      <div class="log-name"><?= htmlspecialchars($log['name']) ?></div>
                      <div class="log-role"><?= htmlspecialchars($log['role']) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="log-email"><?= htmlspecialchars($log['email']) ?></span></td>
                <td><?= htmlspecialchars($log['role']) ?></td>
                <td>
                  <span class="action-badge <?= actionBadge($log['action']) ?>">
                    <?= htmlspecialchars($log['action']) ?>
                  </span>
                </td>
                <td><span class="log-details"><?= htmlspecialchars($log['details']) ?></span></td>
                <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;"><?= htmlspecialchars($log['ts']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
function openExportModal()  { document.getElementById('exportModal').classList.add('open'); }
function closeExportModal() { document.getElementById('exportModal').classList.remove('open'); }
document.getElementById('exportModal').addEventListener('click', function(e){ if(e.target===this) closeExportModal(); });

function openLogout()  { document.getElementById('logoutModal').classList.add('open'); }
function closeLogout() { document.getElementById('logoutModal').classList.remove('open'); }
document.getElementById('logoutModal').addEventListener('click', function(e){ if(e.target===this) closeLogout(); });
</script>
</body>
</html>