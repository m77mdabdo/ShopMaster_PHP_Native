<?php 
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";
if (empty($userData) || ($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied"]);
    exit;
}
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Category ID required"]);
    exit;
}
try {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["status" => true, "message" => "Product deleted successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}