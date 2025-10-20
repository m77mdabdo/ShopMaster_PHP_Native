<?php
header("content-type:application/json");
require_once "../conn/db.php";

require_once "../vendor/autoload.php";
if(isset($_GET['id'])) {
    $categoryId = intval($_GET['id']);

    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if($category) {
            echo json_encode($category);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Category not found"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch category: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Category ID is required"]);
}