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
    echo json_encode(["status" => false, "error" => "Shipping ID is required"]);
    exit;
}

$id = intval($data['id']);

try {
   
    $checkStmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = :id");
    $checkStmt->bindParam(":id", $id);
    $checkStmt->execute();
    $method = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$method) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Shipping method not found"]);
        exit;
    }

    
    $deleteStmt = $conn->prepare("DELETE FROM shipping_methods WHERE id = :id");
    $deleteStmt->bindParam(":id", $id);
    $deleteStmt->execute();

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Shipping method deleted successfully",
        "deleted_shipping" => [
            "id" => $method['id'],
            "name" => $method['name'],
            "cost" => $method['cost']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
