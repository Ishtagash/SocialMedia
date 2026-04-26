<?php
session_start();

$serverName = "MSI\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "SocialMedia",
    "Uid" => "",
    "PWD" => "",
    "TrustServerCertificate" => true
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn == false) {
    die(print_r(sqlsrv_errors(), true));
}

$client_id = "638015427637-v5s87krev2c9nqlai1ko56ou8fen9847.apps.googleusercontent.com";
$client_secret = "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";
$redirect_uri = "http://localhost/SocialMedia/google-callback.php";

if (!isset($_GET["code"])) {
    header("Location: login.php?error=google");
    exit();
}

if (!isset($_GET["state"]) || !isset($_SESSION["google_state"])) {
    header("Location: login.php?error=google");
    exit();
}

if ($_GET["state"] !== $_SESSION["google_state"]) {
    header("Location: login.php?error=google");
    exit();
}

$code = $_GET["code"];

$token_url = "https://oauth2.googleapis.com/token";

$post_fields = [
    "code" => $code,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error: " . curl_error($ch));
}

curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data["access_token"])) {
    header("Location: login.php?error=google");
    exit();
}

$access_token = $token_data["access_token"];

$user_url = "https://www.googleapis.com/oauth2/v3/userinfo";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$user_response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error: " . curl_error($ch));
}

curl_close($ch);

$google_user = json_decode($user_response, true);

if (!isset($google_user["email"])) {
    header("Location: login.php?error=google");
    exit();
}

$email = $google_user["email"];

$sql = "SELECT * FROM USERS WHERE EMAIL = ?";
$params = [$email];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt == false) {
    die(print_r(sqlsrv_errors(), true));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    header("Location: login.php?error=google_not_registered");
    exit();
}

if (rtrim($row["STATUS"]) === "PENDING") {
    header("Location: login.php?error=pending");
    exit();
}

$_SESSION["user_id"] = $row["USER_ID"];
$_SESSION["username"] = $row["USERNAME"];
$_SESSION["role"] = strtolower(rtrim($row["ROLE"]));

$updateSql = "UPDATE USERS SET LAST_LOGIN = GETDATE() WHERE USER_ID = ?";
$updateStmt = sqlsrv_query($conn, $updateSql, [$row["USER_ID"]]);

if ($_SESSION["role"] === "superadmin") {
    header("Location: superadmindashboard.php");
} elseif ($_SESSION["role"] === "staff") {
    header("Location: staffdashboard.php");
} else {
    header("Location: residentdashboard.php");
}

exit();
?>