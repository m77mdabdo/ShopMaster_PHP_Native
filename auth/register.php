<?php 
header("content-type:application/json");

require_once "../conn/db.php";


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once "../vendor/autoload.php";

$data = json_decode(file_get_contents("php://input"), true);

if($_SERVER['REQUEST_METHOD'] === "POST") {
    if(isset($data['name'], $data['email'], $data['password'])) {
        $name = htmlspecialchars(trim($data['name']));
        $email = htmlspecialchars(trim($data['email']));
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        try {
            $stmt->execute();
            http_response_code(201);
            echo json_encode(["message" => "User registered successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Registration failed: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid input"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}