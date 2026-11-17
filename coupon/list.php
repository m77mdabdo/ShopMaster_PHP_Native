<?php
header("Content-Type: application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";
require_once "../vendor/autoload.php";

// Make sure the user is an admin
if (empty($userData) || ($userData['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied: Admins only"]);
    exit;
}

// Optional filtering support
$status = $_GET['status'] ?? null; // active / expired / all
$today = date('Y-m-d');

try {
    // Build the query from the filter
    $query = "SELECT id, code, discount_type, discount_value, min_order_value, expires_at, is_active FROM coupons";
    if ($status === 'active') {
        $query .= " WHERE is_active = 1 AND expires_at >= :today";
    } elseif ($status === 'expired') {
        $query .= " WHERE expires_at < :today OR is_active = 0";
    }

    $stmt = $conn->prepare($query);

    if ($status === 'active' || $status === 'expired') {
        $stmt->bindParam(':today', $today);
    }

    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($coupons)) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "No coupons found"]);
        exit;
    }

    // Prepare the output data
    foreach ($coupons as &$coupon) {
        $coupon['status_text'] = ($coupon['is_active'] && $coupon['expires_at'] >= $today)
            ? 'Active'
            : 'Expired';
    }

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "count" => count($coupons),
        "data" => $coupons
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
