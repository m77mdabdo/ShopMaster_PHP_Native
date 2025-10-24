<?php
header("Content-Type: application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";
require_once "../vendor/autoload.php";


if (empty($userData) || ($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied: Admins only"]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Coupon ID is required"]);
    exit;
}

$coupon_id = intval($data['id']);
$fields = [];
$params = [];


if (!empty($data['code'])) {
    $fields[] = "code = :code";
    $params[':code'] = strtoupper(trim($data['code']));
}
if (!empty($data['discount_type'])) {
    if (!in_array($data['discount_type'], ['percent', 'fixed'])) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Invalid discount type"]);
        exit;
    }
    $fields[] = "discount_type = :type";
    $params[':type'] = strtolower($data['discount_type']);
}
if (isset($data['discount_value'])) {
    if (!is_numeric($data['discount_value']) || $data['discount_value'] <= 0) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Discount value must be positive"]);
        exit;
    }
    $fields[] = "discount_value = :value";
    $params[':value'] = floatval($data['discount_value']);
}
if (isset($data['min_order_value'])) {
    if (!is_numeric($data['min_order_value']) || $data['min_order_value'] < 0) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Invalid minimum order value"]);
        exit;
    }
    $fields[] = "min_order_value = :min_order";
    $params[':min_order'] = floatval($data['min_order_value']);
}
if (!empty($data['expires_at'])) {
    $fields[] = "expires_at = :expires";
    $params[':expires'] = $data['expires_at'];
}
if (isset($data['is_active'])) {
    $fields[] = "is_active = :active";
    $params[':active'] = (int)$data['is_active'];
}


if (empty($fields)) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "No fields to update"]);
    exit;
}

try {
  
    $checkStmt = $conn->prepare("SELECT id FROM coupons WHERE id = :id");
    $checkStmt->bindParam(':id', $coupon_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Coupon not found"]);
        exit;
    }

  
    $sql = "UPDATE coupons SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $params[':id'] = $coupon_id;

    $stmt->execute($params);

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Coupon updated successfully",
        "updated_fields" => array_keys($params)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
