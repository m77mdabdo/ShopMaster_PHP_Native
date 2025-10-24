<?php
header("Content-Type: application/json");
require_once "../../conn/db.php";
require_once "../../auth/validate_token.php";
require_once "../../vendor/autoload.php";


if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $userData['id'];
$data = json_decode(file_get_contents("php://input"), true);


if (empty($data['name']) && empty($data['email']) && empty($data['password'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "No fields provided to update"]);
    exit;
}

try {
    $updateFields = [];
    $params = [':id' => $user_id];

    
    if (!empty($data['name'])) {
        $updateFields[] = "name = :name";
        $params[':name'] = htmlspecialchars(trim($data['name']));
    }

    
    if (!empty($data['email'])) {

        $check = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => $data['email'], ':id' => $user_id]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode(["status" => false, "error" => "Email already in use"]);
            exit;
        }

        $updateFields[] = "email = :email";
        $params[':email'] = htmlspecialchars(trim($data['email']));
    }

    
    if (!empty($data['password'])) {
        $updateFields[] = "password = :password";
        $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }

    
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    http_response_code(200);
    echo json_encode(["status" => true, "message" => "Profile updated successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}
