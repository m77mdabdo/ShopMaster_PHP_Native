<?php
header("Content-Type: application/json");

// تحميل مكتبة JWT
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


$secret_key = "ShopMasterSecretKey123";


$headers = getallheaders();

if (!isset($headers["Authorization"])) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Authorization header missing"]);
    exit;
}


$authHeader = trim($headers["Authorization"]);
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Invalid Authorization format"]);
    exit;
}

$token = $matches[1];


try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

   
    $userData = (array)$decoded->data;


    echo json_encode([
        "status" => true,
        "message" => "Token is valid",
        "user" => $userData
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => false,
        "error" => "Token invalid or expired",
        "details" => $e->getMessage()
    ]);
}

