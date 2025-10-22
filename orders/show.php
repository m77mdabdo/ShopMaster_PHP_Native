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

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Order ID is required"]);
    exit;
}

try {
    $isAdmin = ($userData['role'] ?? 'user') === 'admin';

  
    if ($isAdmin) {
     
        $stmt = $conn->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id
        ");
        $stmt->bindParam(':id', $order_id);
    } else {
    
        $stmt = $conn->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :uid
        ");
        $stmt->bindParam(':id', $order_id);
        $stmt->bindParam(':uid', $userData['id']);
    }

    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Order not found"]);
        exit;
    }

 
    $itemsStmt = $conn->prepare("
        SELECT oi.product_id, p.name, oi.quantity, oi.price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $itemsStmt->bindParam(':order_id', $order_id);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);


    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Order details fetched successfully",
        "order" => [
            "id" => $order['id'],
            "user_name" => $order['user_name'],
            "user_email" => $order['user_email'],
            "total_price" => $order['total_price'],
            "status" => $order['status'],
            "created_at" => $order['created_at'],
            "items" => $items
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
