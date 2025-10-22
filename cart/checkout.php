<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";


if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $userData['id'];

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


    $itemsStmt = $conn->prepare("
        SELECT p.id AS product_id, p.price, ci.quantity
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = :cart
    ");
    $itemsStmt->bindParam(':cart', $cart_id);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Cart is empty"]);
        exit;
    }

 
    $total_price = 0;
    foreach ($items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }


    $coupon_id = $cart['coupon_id'] ?? null;

   
    $shipping_id = $cart['shipping_id'] ?? null;

    
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, total_price, coupon_id, shipping_id, status, created_at)
        VALUES (:uid, :total_price, :coupon_id, :shipping_id, 'pending', NOW())
    ");
    $orderStmt->execute([
        ':uid' => $user_id,
        ':total_price' => $total_price,
        ':coupon_id' => $coupon_id,
        ':shipping_id' => $shipping_id
    ]);
    $order_id = $conn->lastInsertId();

   
    $insertItem = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ");

    foreach ($items as $item) {
        $insertItem->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

 
    $conn->prepare("DELETE FROM cart_items WHERE cart_id = :cart")->execute([':cart' => $cart_id]);
    $conn->prepare("DELETE FROM carts WHERE id = :id")->execute([':id' => $cart_id]);

    
    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Checkout completed successfully",
        "data" => [
            "order_id" => $order_id,
            "total_price" => round($total_price, 2)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
