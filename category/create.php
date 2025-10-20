<?php 

header("content-type:application/json");
require_once "../conn/db.php";
require_once "../vendor/autoload.php";
require_once "../auth/validate_token.php";
// $data = json_decode(file_get_contents("php://input"), true);

if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => false, "error" => "Access denied"]);
    exit;
}

 $data = json_decode(file_get_contents("php://input"), true);
if($_SERVER['REQUEST_METHOD'] === "POST") {
    if(isset($data['name'], $data['slug'])) {
        $name = htmlspecialchars(trim($data['name']));
        $slug = htmlspecialchars(trim($data['slug']));

        $stmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);

        try {
            $stmt->execute();
            http_response_code(201);
            echo json_encode(["status" => true, "message" => "Category created successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => false, "error" => "Category creation failed: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => false, "error" => "Invalid input"]);
    }
}