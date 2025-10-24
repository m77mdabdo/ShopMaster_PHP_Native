<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../auth/validate_token.php";

$config = require __DIR__ . "/../payment/config.php";

// ✅ تحقق من تسجيل الدخول
if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

// بيانات من المستخدم (إجمالي الفاتورة)
$data = json_decode(file_get_contents("php://input"), true);
$total = $data['total'] ?? 0;

if ($total <= 0) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Invalid total amount"]);
    exit;
}

// إعداد الرابط المناسب
$baseUrl = $config['sandbox']
    ? "https://api.sandbox.paypal.com"
    : "https://api.paypal.com";

try {
    // 1️⃣ احصل على Access Token من PayPal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['client_id'] . ":" . $config['client_secret']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $response = curl_exec($ch);
    if (!$response) throw new Exception("Failed to get access token");
    $result = json_decode($response, true);
    $accessToken = $result['access_token'] ?? null;
    curl_close($ch);

    if (!$accessToken) throw new Exception("Invalid access token");

    // 2️⃣ إنشاء طلب دفع
    $orderData = [
        "intent" => "CAPTURE",
        "purchase_units" => [
            [
                "amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($total, 2, '.', '')
                ]
            ]
        ],
        "application_context" => [
            "return_url" => "http://localhost/ShopMaster_PHP_Native/payment/paypal_capture.php",
            "cancel_url" => "http://localhost/ShopMaster_PHP_Native/payment/paypal_cancel.php"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));

    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if (!isset($result['id'])) throw new Exception("Failed to create PayPal order");

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "PayPal order created successfully",
        "order_id" => $result['id'],
        "approval_link" => $result['links'][1]['href'] ?? null // رابط الدفع
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
