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
 
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');

  
    $dailySales = $conn->prepare("
        SELECT COUNT(*) AS total_orders, IFNULL(SUM(total_price), 0) AS total_revenue
        FROM orders
        WHERE DATE(created_at) = :today AND status = 'completed'
    ");
    $dailySales->execute([':today' => $today]);
    $daily = $dailySales->fetch(PDO::FETCH_ASSOC);


    $weeklySales = $conn->prepare("
        SELECT COUNT(*) AS total_orders, IFNULL(SUM(total_price), 0) AS total_revenue
        FROM orders
        WHERE DATE(created_at) >= :weekStart AND status = 'completed'
    ");
    $weeklySales->execute([':weekStart' => $weekStart]);
    $weekly = $weeklySales->fetch(PDO::FETCH_ASSOC);

 
    $monthlySales = $conn->prepare("
        SELECT COUNT(*) AS total_orders, IFNULL(SUM(total_price), 0) AS total_revenue
        FROM orders
        WHERE DATE(created_at) >= :monthStart AND status = 'completed'
    ");
    $monthlySales->execute([':monthStart' => $monthStart]);
    $monthly = $monthlySales->fetch(PDO::FETCH_ASSOC);

  
    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Sales report fetched successfully",
        "data" => [
            "today" => [
                "orders" => intval($daily['total_orders']),
                "revenue" => floatval($daily['total_revenue'])
            ],
            "this_week" => [
                "orders" => intval($weekly['total_orders']),
                "revenue" => floatval($weekly['total_revenue'])
            ],
            "this_month" => [
                "orders" => intval($monthly['total_orders']),
                "revenue" => floatval($monthly['total_revenue'])
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}
