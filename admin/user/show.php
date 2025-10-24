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


if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "User ID is required"]);
    exit;
}

$user_id = intval($_GET['id']);

try {
   
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = :id");
    $stmt->bindParam(":id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "User not found"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "data" => $user
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
