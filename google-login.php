<?php
session_start();

$client_id = "638015427637-v5s87krev2c9nqlai1ko56ou8fen9847.apps.googleusercontent.com";
$redirect_uri = "http://localhost/google-callback.php";

$scope = urlencode("openid email profile");

$state = bin2hex(random_bytes(16));
$_SESSION["google_state"] = $state;

$google_url = "https://accounts.google.com/o/oauth2/v2/auth";
$google_url .= "?response_type=code";
$google_url .= "&client_id=" . urlencode($client_id);
$google_url .= "&redirect_uri=" . urlencode($redirect_uri);
$google_url .= "&scope=" . $scope;
$google_url .= "&state=" . urlencode($state);
$google_url .= "&prompt=select_account";

header("Location: " . $google_url);
exit();
?>