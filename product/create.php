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


$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['name']) ||
    empty($data['slug']) ||
    empty($data['description']) ||
    empty($data['price']) ||
    empty($data['category_id'])
) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "All fields are required"]);
    exit;
}


if (!is_numeric($data['price'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Price must be a valid number"]);
    exit;
}


$image = $data['image'] ?? 'default.png';

try {

      $check = $conn->prepare("SELECT id FROM products WHERE slug = :slug");
    $check->bindParam(':slug', $data['slug']);
    $check->execute();

    if ($check->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Slug already exists, please choose a unique one"]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO products (name, slug, description, price, category_id, image)
        VALUES (:name, :slug, :description, :price, :category_id, :image)
    ");
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':slug', $data['slug']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':category_id', $data['category_id']);
    $stmt->bindParam(':image', $image);

    $stmt->execute();

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Product created successfully"
    ]);

} catch (PDOException $e) {
    
    
        http_response_code(500);
        echo json_encode(["status" => false, "error" => $e->getMessage()]);
    
}

