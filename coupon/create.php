<?php 
header("content-type:application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";
require_once "../vendor/autoload.php";

// ✅ تأكيد أن المستخدم أدمن
if (empty($userData) || ($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// ✅ التحقق من المدخلات
if (
    empty($data['code']) ||
    empty($data['discount_type']) ||
    empty($data['discount_value']) ||
    empty($data['expires_at'])
) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "All fields are required"]);
    exit;
}

$code = strtoupper(trim($data['code']));
$discount_type = strtolower(trim($data['discount_type']));
$discount_value = floatval($data['discount_value']);
$expires_at = $data['expires_at'];
$is_active = $data['is_active'] ?? 1;

// ✅ السماح فقط بالقيم المحددة
if(!in_array($discount_type, ['percent','fixed'])){
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Invalid discount type"]);
    exit;
}

// ✅ التحقق من قيمة الخصم
if(!is_numeric($discount_value) || $discount_value <= 0){
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Discount value must be a positive number"]);
    exit;
}

// ✅ تنفيذ الإدخال
try {
    $stmt = $conn->prepare("
        INSERT INTO coupons (code, discount_type, discount_value, is_active, expires_at)
        VALUES (:code, :type, :value, :active, :expires)
    ");
    $stmt->execute([
        ':code' => $code,
        ':type' => $discount_type,
        ':value' => $discount_value,
        ':active' => $is_active,
        ':expires' => $expires_at
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Coupon created successfully",
        "data" => [
            "code" => $code,
            "discount_type" => $discount_type,
            "discount_value" => $discount_value,
            "expires_at" => $expires_at
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
