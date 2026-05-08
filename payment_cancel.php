<?php
session_start();

$serverName = "LAPTOP-8KOIBQER\\SQLEXPRESS";
$connectionOptions = ["
Database" => "SocialMedia", 
"Uid" => "", 
"PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

if (!isset($_GET["payment_id"])) {
    die("Missing payment ID.");
}

$paymentId = (int)$_GET["payment_id"];

$sql = "
    UPDATE PAYMENTS
    SET PAYMENT_STATUS = 'CANCELLED'
    WHERE PAYMENT_ID = ?
";

$stmt = sqlsrv_query($conn, $sql, array($paymentId));

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
</head>
<body>
    <h2>Payment Cancelled</h2>
    <p>You cancelled the payment process.</p>
    <a href="residentrequestdocument.php">Go Back</a>
</body>
</html>