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

$coupon_id = (int) $data['id'];

try {
 
    $checkStmt = $conn->prepare("SELECT * FROM coupons WHERE id = :id");
    $checkStmt->bindParam(':id', $coupon_id);
    $checkStmt->execute();
    $coupon = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Coupon not found"]);
        exit;
    }

 
    $deleteStmt = $conn->prepare("DELETE FROM coupons WHERE id = :id");
    $deleteStmt->bindParam(':id', $coupon_id);
    $deleteStmt->execute();

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Coupon deleted successfully",
        "deleted_coupon" => [
            "id" => $coupon['id'],
            "code" => $coupon['code'],
            "discount_type" => $coupon['discount_type']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
