<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";

if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

try {
    // 🔹 تحقق من الدور
    $isAdmin = ($userData['role'] ?? 'user') === 'admin';

    // 🔹 جلب الطلبات
    if ($isAdmin) {
        $stmt = $conn->prepare("
            SELECT o.id, u.name AS user_name, o.total_price, o.status, o.created_at
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT id, total_price, status, created_at
            FROM orders
            WHERE user_id = :uid
            ORDER BY created_at DESC
        ");
        $stmt->bindParam(':uid', $userData['id']);
        $stmt->execute();
    }

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Orders fetched successfully",
        "data" => $orders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
