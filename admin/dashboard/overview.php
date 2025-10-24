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
 
    $usersCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $productsCount = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();

    
    $ordersCount = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    
    $couponsCount = $conn->query("SELECT COUNT(*) FROM coupons")->fetchColumn();

   
    $totalRevenue = $conn->query("
        SELECT IFNULL(SUM(total_price), 0)
        FROM orders
        WHERE status = 'completed'
    ")->fetchColumn();

   
    $pendingOrders = $conn->query("
        SELECT COUNT(*) FROM orders WHERE status = 'pending'
    ")->fetchColumn();

   
    echo json_encode([
        "status" => true,
        "message" => "Dashboard data fetched successfully",
        "data" => [
            "users" => intval($usersCount),
            "products" => intval($productsCount),
            "orders" => intval($ordersCount),
            "coupons" => intval($couponsCount),
            "revenue" => floatval($totalRevenue),
            "pending_orders" => intval($pendingOrders)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}
