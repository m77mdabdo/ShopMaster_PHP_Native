<?php


header("content-type:application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";

if ($userData['role'] !== 'admin') {
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
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["status" => true, "message" => "Category deleted successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}