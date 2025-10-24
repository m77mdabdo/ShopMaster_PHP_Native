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
 
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            SUM(oi.quantity) AS total_quantity_sold,
            SUM(oi.price * oi.quantity) AS total_revenue
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status = 'completed'
        GROUP BY p.id, p.name, p.price
        ORDER BY total_quantity_sold DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($topProducts)) {
        http_response_code(404);
        echo json_encode(["status" => false, "message" => "No sales data found"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Top selling products retrieved successfully",
        "data" => $topProducts
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}

