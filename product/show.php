<?php 

header("content-type:application/json");
require_once "../conn/db.php";
require_once "../vendor/autoload.php";

if(isset($_GET['id'])) {
    $productId = intval($_GET['id']);

    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Product not found"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch product: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Product ID is required"]);
}