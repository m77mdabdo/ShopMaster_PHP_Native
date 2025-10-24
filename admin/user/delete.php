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

try {
    
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = :id");
    $checkStmt->bindParam(":id", $user_id);
    $checkStmt->execute();
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "User not found"]);
        exit;
    }

    
    if ($user_id == $userData['id']) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "You cannot delete yourself"]);
        exit;
    }


    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $deleteStmt->bindParam(":id", $user_id);
    $deleteStmt->execute();

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "User deleted successfully",
        "deleted_id" => $user_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}
