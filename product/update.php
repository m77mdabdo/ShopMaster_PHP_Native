<?php 

header("content-type:application/json");
require_once "../conn/db.php";
require_once "../auth/validate_token.php";

if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied"]);
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Category ID required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if(
    empty($data['name'])||
    empty($data['slug'])||
    empty($data['description'])||
    empty($data['price'])||
    empty($data['category_id'])
){
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "All fields are required"]);
    exit;
}
if(!is_numeric($data['price'])){
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Price must be a valid number"]);
    exit;
}
$image = $data['image'] ?? 'default.png';

try {
     $check = $conn->prepare("SELECT id FROM products WHERE id = :id");
    $check->bindParam(':id', $id);
    $check->execute();

    if ($check->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Product not found"]);
        exit;
    }
     $stmt = $conn->prepare("
        UPDATE products
        SET name = :name,
            slug = :slug,
            description = :description,
            price = :price,
            category_id = :category_id,
            image = :image
        WHERE id = :id
    ");
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':slug', $data['slug']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':category_id', $data['category_id']);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

     if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => true, "message" => "Product updated successfully"]);
    } else {
        echo json_encode(["status" => false, "message" => "No changes or product already up to date"]);
    }
}catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}

