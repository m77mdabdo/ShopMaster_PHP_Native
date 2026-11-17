<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";

// The user must be logged in
if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $userData['id'];

try {
    // Fetch the user's cart
    $cartStmt = $conn->prepare("SELECT * FROM carts WHERE user_id = :uid");
    $cartStmt->bindParam(':uid', $user_id);
    $cartStmt->execute();
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        echo json_encode([
            "status" => true,
            "message" => "Cart is empty",
            "data" => [
                "items" => [],
                "subtotal" => 0,
                "discount" => 0,
                "shipping" => 0,
                "total" => 0
            ]
        ]);
        exit;
    }

    $cart_id = $cart['id'];

    // Fetch the products in the cart
    $itemsStmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            p.id AS product_id,
            p.name,
            p.price,
            ci.quantity,
            (p.price * ci.quantity) AS total_price
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = :cart_id
    ");
    $itemsStmt->bindParam(':cart_id', $cart_id);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        echo json_encode([
            "status" => true,
            "message" => "Cart is empty",
            "data" => [
                "items" => [],
                "subtotal" => 0,
                "discount" => 0,
                "shipping" => 0,
                "total" => 0
            ]
        ]);
        exit;
    }

    // Calculate the subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['total_price'];
    }

    // Calculate the discount (if there is a coupon)
    $discount = 0;
    if (!empty($cart['coupon_id'])) {
        $couponStmt = $conn->prepare("SELECT * FROM coupons WHERE id = :id AND is_active = 1 AND expires_at >= CURDATE()");
        $couponStmt->bindParam(':id', $cart['coupon_id']);
        $couponStmt->execute();
        $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {
            if ($coupon['discount_type'] === 'percent') {
                $discount = ($subtotal * $coupon['discount_value']) / 100;
            } else {
                $discount = $coupon['discount_value'];
            }
        }
    }

    // Calculate shipping (if selected)
    $shipping = 0;
    if (!empty($cart['shipping_id'])) {
        $shipStmt = $conn->prepare("SELECT cost FROM shipping_methods WHERE id = :id AND is_active = 1");
        $shipStmt->bindParam(':id', $cart['shipping_id']);
        $shipStmt->execute();
        $shippingData = $shipStmt->fetch(PDO::FETCH_ASSOC);
        $shipping = $shippingData ? $shippingData['cost'] : 0;
    }

    // Calculate the final total
    $total = $subtotal - $discount + $shipping;

    echo json_encode([
        "status" => true,
        "message" => "Cart fetched successfully",
        "data" => [
            "items" => $items,
            "subtotal" => round($subtotal, 2),
            "discount" => round($discount, 2),
            "shipping" => round($shipping, 2),
            "total" => round($total, 2)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}

