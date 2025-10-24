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


$name = isset($data['name']) ? htmlspecialchars(trim($data['name'])) : null;
$cost = isset($data['cost']) ? floatval($data['cost']) : null;
$estimated_days = isset($data['estimated_days']) ? htmlspecialchars(trim($data['estimated_days'])) : null;
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : null;

try {
 
    $checkStmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = :id");
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    $shipping = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipping) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Shipping method not found"]);
        exit;
    }

   
    $fields = [];
    $params = [":id" => $id];

    if ($name !== null) {
        $fields[] = "name = :name";
        $params[":name"] = $name;
    }
    if ($cost !== null) {
        $fields[] = "cost = :cost";
        $params[":cost"] = $cost;
    }
    if ($estimated_days !== null) {
        $fields[] = "estimated_days = :days";
        $params[":days"] = $estimated_days;
    }
    if ($is_active !== null) {
        $fields[] = "is_active = :active";
        $params[":active"] = $is_active;
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "No fields to update"]);
        exit;
    }

    $query = "UPDATE shipping_methods SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Shipping method updated successfully",
        "updated_id" => $id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
