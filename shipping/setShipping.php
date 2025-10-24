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
if (empty($data['shipping_id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Shipping ID is required"]);
    exit;
}

$shipping_id = intval($data['shipping_id']);

try {

    $cartStmt = $conn->prepare("SELECT * FROM carts WHERE user_id = :uid");
    $cartStmt->bindParam(":uid", $user_id);
    $cartStmt->execute();
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Cart not found"]);
        exit;
    }

  
    $shipStmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = :id AND is_active = 1");
    $shipStmt->bindParam(":id", $shipping_id);
    $shipStmt->execute();
    $shipping = $shipStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipping) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Shipping method not found or inactive"]);
        exit;
    }

 
    $updateStmt = $conn->prepare("UPDATE carts SET shipping_id = :sid WHERE id = :cart_id");
    $updateStmt->execute([
        ":sid" => $shipping_id,
        ":cart_id" => $cart['id']
    ]);

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Shipping method applied successfully",
        "data" => [
            "cart_id" => $cart['id'],
            "shipping_id" => $shipping['id'],
            "shipping_name" => $shipping['name'],
            "cost" => $shipping['cost'],
            "estimated_days" => $shipping['estimated_days']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
