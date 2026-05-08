<?php
include "paymongo_config.php";

$amount = 50;
$amountCentavos = $amount * 100;

$successUrl = $baseUrl . "/payment_success_test.php";
$cancelUrl = $baseUrl . "/payment_cancel_test.php";

$paymentData = array(
    "data" => array(
        "attributes" => array(
            "send_email_receipt" => true,
            "show_description" => true,
            "show_line_items" => true,
            "description" => "BarangayKonek Test Payment",
            "success_url" => $successUrl,
            "cancel_url" => $cancelUrl,
            "payment_method_types" => array(
                "gcash"
            ),
            "line_items" => array(
                array(
                    "currency" => "PHP",
                    "amount" => intval($amountCentavos),
                    "name" => "Barangay Clearance Test",
                    "quantity" => 1
                )
            )
        )
    )
);

$encodedKey = base64_encode($paymongoSecretKey . ":");

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $paymongoCheckoutUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Basic " . $encodedKey
));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));

$response = curl_exec($ch);
$curlError = curl_error($ch);

curl_close($ch);

if ($curlError) {
    echo "cURL Error: " . $curlError;
    exit();
}

$responseData = json_decode($response, true);

if (
    isset($responseData["data"]["attributes"]["checkout_url"])
) {
    $checkoutUrl = $responseData["data"]["attributes"]["checkout_url"];

    header("Location: " . $checkoutUrl);
    exit();
}

echo "<pre>";
print_r($responseData);
echo "</pre>";
?>