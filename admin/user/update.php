<?php
header("Content-Type: application/json");
require_once "../../conn/db.php";
require_once "../../auth/validate_token.php";
require_once "../../vendor/autoload.php";


if (empty($userData) || ($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied: Admins only"]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "User ID is required"]);
    exit;
}

$user_id = intval($data['id']);
$name = isset($data['name']) ? htmlspecialchars(trim($data['name'])) : null;
$email = isset($data['email']) ? htmlspecialchars(trim($data['email'])) : null;
$role = isset($data['role']) ? strtolower(trim($data['role'])) : null;


if ($role !== null && !in_array($role, ['admin', 'user'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Invalid role value"]);
    exit;
}

try {
   
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $checkStmt->bindParam(":id", $user_id);
    $checkStmt->execute();
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "User not found"]);
        exit;
    }

   
    $fields = [];
    $params = [":id" => $user_id];

    if ($name !== null) {
        $fields[] = "name = :name";
        $params[":name"] = $name;
    }
    if ($email !== null) {
        $fields[] = "email = :email";
        $params[":email"] = $email;
    }
    if ($role !== null) {
        $fields[] = "role = :role";
        $params[":role"] = $role;
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "No fields to update"]);
        exit;
    }

    $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "User updated successfully",
        "updated_id" => $user_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}
