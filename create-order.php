<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers to allow JSON data and cross-origin access from Android
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit();
}

// 🔴 REPLACE THESE WITH YOUR ACTUAL CASHFREE KEYS
$cashfree_app_id = "667352df14424500e82f8c2307253766";
$cashfree_secret_key = "cfsk_ma_prod_fcce7fef36b367558df61a0bc68d7fca_3e106266";

// For sandbox/testing use this URL. Switch to https://api.cashfree.com/pg/orders for Live mode.
$cashfree_url = "https://sandbox.cashfree.com/pg/orders";

// Read the incoming JSON data sent by your Android app (Volley)
$input_data = file_get_contents("php://input");
$request_body = json_decode($input_data, true);

// Extract fields safely
$name = isset($request_body['name']) ? $request_body['name'] : 'Guest Customer';
$phone = isset($request_body['phone']) ? $request_body['phone'] : '9999999999';
$amount = isset($request_body['amount']) ? (float)$request_body['amount'] : 10.00;

// Generate a unique Order ID
$order_id = "TICKET_ORDER_" . time();

// Prepare the JSON payload structure that Cashfree expects
$order_payload = [
    "order_id" => $order_id,
    "order_amount" => $amount,
    "order_currency" => "INR",
    "customer_details" => [
        "customer_id" => "CUST_" . $phone,
        "customer_name" => $name,
        "customer_phone" => $phone,
        "customer_email" => "customer@example.com"
    ]
];

// Initialize cURL to send the request securely to Cashfree
$ch = curl_init($cashfree_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-client-id: " . $cashfree_app_id,
    "x-client-secret: " . $cashfree_secret_key,
    "x-api-version: 2023-08-01",
    "Content-Type: application/json"
]);

// Execute request and get response
$response_data = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "cURL Error: " . curl_error($ch)]);
    curl_close($ch);
    exit();
}
curl_close($ch);

// Parse Cashfree response
$cashfree_response = json_decode($response_data, true);

if ($http_status === 200 || $http_status === 201) {
    // Send back the required keys to Android Volley
    http_response_code(200);
    echo json_encode([
        "order_id" => $cashfree_response['order_id'],
        "payment_session_id" => $cashfree_response['payment_session_id']
    ]);
} else {
    // Return the error message directly from Cashfree if it fails
    http_response_code($http_status);
    echo json_encode([
        "error" => "Failed to create order with Cashfree",
        "details" => $cashfree_response
    ]);
}
?>