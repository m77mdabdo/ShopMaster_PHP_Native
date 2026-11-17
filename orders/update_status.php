<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";

// Make sure the user is logged in
if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

// Admins only
if (($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied: Admins only"]);
    exit;
}

// Read the request body
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['order_id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Order ID and new status are required"]);
    exit;
}

$order_id = intval($data['order_id']);
$new_status = htmlspecialchars(trim($data['status']));

// Allow only specific statuses
$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if (!in_array($new_status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Invalid status value"]);
    exit;
}

try {
    // Make sure the order exists
    $checkStmt = $conn->prepare("SELECT id FROM orders WHERE id = :id");
    $checkStmt->bindParam(':id', $order_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Order not found"]);
        exit;
    }

    // Update the status
    $updateStmt = $conn->prepare("
        UPDATE orders 
        SET status = :status
        WHERE id = :id
    ");
    $updateStmt->bindParam(':status', $new_status);
    $updateStmt->bindParam(':id', $order_id);
    $updateStmt->execute();

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Order status updated successfully",
        "data" => [
            "order_id" => $order_id,
            "new_status" => $new_status
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
