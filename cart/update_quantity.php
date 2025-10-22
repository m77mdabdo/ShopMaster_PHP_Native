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
$data = json_decode(file_get_contents("php://input"), true);


if (empty($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Product ID and quantity are required"]);
    exit;
}

$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);

if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Quantity must be at least 1"]);
    exit;
}

try {
   
    $cartStmt = $conn->prepare("SELECT id FROM carts WHERE user_id = :uid");
    $cartStmt->bindParam(':uid', $user_id);
    $cartStmt->execute();
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Cart not found"]);
        exit;
    }

    $cart_id = $cart['id'];

    
    $itemStmt = $conn->prepare("SELECT id FROM cart_items WHERE cart_id = :cart AND product_id = :pid");
    $itemStmt->execute([':cart' => $cart_id, ':pid' => $product_id]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Product not found in cart"]);
        exit;
    }

  
    $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = :q WHERE id = :id");
    $updateStmt->execute([':q' => $quantity, ':id' => $item['id']]);

    echo json_encode(["status" => true, "message" => "Product quantity updated successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}


