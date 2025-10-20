<?php
header("Content-Type: application/json");
require_once "../conn/db.php";
require_once "../vendor/autoload.php";

use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($data['email'], $data['password'])) {
        $email = htmlspecialchars(trim($data['email']));
        $password = $data['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            $secret_key = "ShopMasterSecretKey123";

          
            $payload = [
                'iss' => 'ShopMaster_API',
                'aud' => 'ShopMaster_Client',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24), 
                'data' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user'
                ]
            ];

            $jwt = JWT::encode($payload, $secret_key, 'HS256');

            http_response_code(200);
            echo json_encode([
                "status" => true,
                "token" => $jwt
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid email or password"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid input"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>
