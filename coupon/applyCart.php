<?php
header("Content-Type: application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";
require_once "../vendor/autoload.php";


if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $userData['id'];


$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['code'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Coupon code is required"]);
    exit;
}

$code = trim($data['code']);

try {
 
    $cartStmt = $conn->prepare("SELECT * FROM carts WHERE user_id = :uid");
    $cartStmt->bindParam(':uid', $user_id);
    $cartStmt->execute();
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Cart not found"]);
        exit;
    }

    $cart_id = $cart['id'];

 
    $subtotalStmt = $conn->prepare("
        SELECT SUM(p.price * ci.quantity) AS subtotal
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = :cart
    ");
    $subtotalStmt->bindParam(':cart', $cart_id);
    $subtotalStmt->execute();
    $subtotalData = $subtotalStmt->fetch(PDO::FETCH_ASSOC);
    $subtotal = $subtotalData['subtotal'] ?? 0;

    if ($subtotal <= 0) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Cart is empty"]);
        exit;
    }

   
    $couponStmt = $conn->prepare("
        SELECT * FROM coupons 
        WHERE code = :code 
        AND is_active = 1 
        AND expires_at >= CURDATE()
        LIMIT 1
    ");
    $couponStmt->bindParam(':code', $code);
    $couponStmt->execute();
    $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Invalid or expired coupon"]);
        exit;
    }

    if (!empty($coupon['min_order_value']) && $subtotal < $coupon['min_order_value']) {
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "error" => "Minimum order value for this coupon is " . $coupon['min_order_value']
        ]);
        exit;
    }

   
    $updateCart = $conn->prepare("UPDATE carts SET coupon_id = :cid WHERE id = :cart");
    $updateCart->execute([
        ':cid' => $coupon['id'],
        ':cart' => $cart_id
    ]);

 
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($subtotal * $coupon['discount_value']) / 100;
    } else {
        $discount = $coupon['discount_value'];
    }

    $discount = min($discount, $subtotal); // تأكد إن الخصم مش أكبر من المجموع

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Coupon applied successfully",
        "data" => [
            "coupon_code" => $coupon['code'],
            "discount_type" => $coupon['discount_type'],
            "discount_value" => $coupon['discount_value'],
            "subtotal" => round($subtotal, 2),
            "discount" => round($discount, 2),
            "total_after_discount" => round($subtotal - $discount, 2)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
