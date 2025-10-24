<?php
header("Content-Type: application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";
require_once "../vendor/autoload.php";

try {
   
    $isAdmin = !empty($userData) && ($userData['role'] ?? 'user') === 'admin';

    if ($isAdmin) {
        $stmt = $conn->prepare("SELECT * FROM shipping_methods ORDER BY id DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY id DESC");
    }

    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$methods) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "No shipping methods found"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => true,
        "count" => count($methods),
        "data" => $methods
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
