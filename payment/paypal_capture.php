<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

$config = require __DIR__ . "/../config/paypal.php";
$baseUrl = $config['sandbox']
    ? "https://api.sandbox.paypal.com"
    : "https://api.paypal.com";

$orderId = $_GET['token'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Missing PayPal token"]);
    exit;
}

try {
    // 1️⃣ احصل على Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['client_id'] . ":" . $config['client_secret']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $response = curl_exec($ch);
    $result = json_decode($response, true);
    $accessToken = $result['access_token'] ?? null;
    curl_close($ch);

    if (!$accessToken) throw new Exception("Failed to get PayPal access token");

    // 2️⃣ التقاط الدفع
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders/$orderId/capture");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ]);

    $response = curl_exec($ch);
    $captureResult = json_decode($response, true);
    curl_close($ch);

    // 3️⃣ لو الدفع تم بنجاح
    if (isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {

        // استخراج البيانات من النتيجة
        $transaction_id = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        $amount = $captureResult['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
        $currency = $captureResult['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? 'USD';

        // ⚠️ هنا هتربط العملية بالطلب الحقيقي عندك (ممكن تمرر order_id في return_url)
        $order_db_id = $_GET['order_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;

        if (!$order_db_id || !$user_id) {
            http_response_code(400);
            echo json_encode(["status" => false, "error" => "Missing local order_id or user_id"]);
            exit;
        }

        // 🧾 حفظ الدفع في قاعدة البيانات
        $stmt = $conn->prepare("
            INSERT INTO payments (user_id, order_id, payment_method, payment_status, transaction_id, amount, currency)
            VALUES (:user_id, :order_id, 'paypal', 'completed', :transaction_id, :amount, :currency)
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':order_id' => $order_db_id,
            ':transaction_id' => $transaction_id,
            ':amount' => $amount,
            ':currency' => $currency
        ]);

        // 🔁 تحديث حالة الطلب
        $update = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = :id");
        $update->execute([':id' => $order_db_id]);

        // ✅ رد النجاح
        echo json_encode([
            "status" => true,
            "message" => "Payment captured and recorded successfully",
            "paypal_order_id" => $orderId,
            "transaction_id" => $transaction_id,
            "amount" => $amount,
            "currency" => $currency
        ]);

    } else {
        echo json_encode([
            "status" => false,
            "error" => "Payment not completed",
            "details" => $captureResult
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
