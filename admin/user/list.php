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

try {
    
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$users) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "No users found"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "count" => count($users),
        "data" => $users
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}
