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


if (($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied: Admins only"]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['order_id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Order ID is required"]);
    exit;
}

$order_id = intval($data['order_id']);

try {
   
    $checkStmt = $conn->prepare("SELECT id FROM orders WHERE id = :id");
    $checkStmt->bindParam(':id', $order_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Order not found"]);
        exit;
    }

   
    $deleteItems = $conn->prepare("DELETE FROM order_items WHERE order_id = :id");
    $deleteItems->bindParam(':id', $order_id);
    $deleteItems->execute();

   
    $deleteOrder = $conn->prepare("DELETE FROM orders WHERE id = :id");
    $deleteOrder->bindParam(':id', $order_id);
    $deleteOrder->execute();

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Order deleted successfully",
        "data" => [
            "order_id" => $order_id
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
