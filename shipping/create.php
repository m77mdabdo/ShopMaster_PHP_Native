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

if (
    empty($data['name']) ||
    empty($data['cost']) ||
    empty($data['estimated_days'])
) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "All fields are required"]);
    exit;
}

$name = htmlspecialchars(trim($data['name']));
$cost = floatval($data['cost']);
$estimated_days = htmlspecialchars(trim($data['estimated_days']));
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

try {
   
    $stmt = $conn->prepare("
        INSERT INTO shipping_methods (name, cost, estimated_days, is_active)
        VALUES (:name, :cost, :days, :active)
    ");
    $stmt->execute([
        ':name' => $name,
        ':cost' => $cost,
        ':days' => $estimated_days,
        ':active' => $is_active
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Shipping method created successfully",
        "data" => [
            "name" => $name,
            "cost" => $cost,
            "estimated_days" => $estimated_days,
            "is_active" => $is_active
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
