<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../conn/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../auth/validate_token.php";

// 🧍‍♂️ تأكد من تسجيل الدخول
if (empty($userData)) {
    http_response_code(401);
    echo json_encode(["status" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $userData['id']; // ✅ اسم متغير موحد
$data = json_decode(file_get_contents("php://input"), true);

// ✅ التحقق من المدخلات
if (empty($data['product_id']) || empty($data['quantity'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "error" => "Product ID and quantity are required"]);
    exit;
}

$product_id = intval($data['product_id']);
$quantity   = intval($data['quantity']);

try {
    // ✅ تحقق من أن المنتج موجود
    $stmt = $conn->prepare("SELECT id, price FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(["status" => false, "error" => "Product not found"]);
        exit;
    }

    // 🛒 هل يوجد كارت للمستخدم؟
    $cartStmt = $conn->prepare("SELECT id FROM carts WHERE user_id = :uid");
    $cartStmt->bindParam(':uid', $user_id);
    $cartStmt->execute();
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        // 🧺 إنشاء كارت جديد
        $createCart = $conn->prepare("INSERT INTO carts (user_id, session_id) VALUES (:uid, :sid)");
        $session_id = session_id() ?: uniqid('sess_', true);
        $createCart->bindParam(':uid', $user_id);
        $createCart->bindParam(':sid', $session_id);
        $createCart->execute();
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }

    // ✅ هل المنتج موجود مسبقًا في العربة؟
    $itemStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = :cart AND product_id = :pid");
    $itemStmt->execute([':cart' => $cart_id, ':pid' => $product_id]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // 🔄 تحديث الكمية
        $newQuantity = $item['quantity'] + $quantity;
        $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = :q WHERE id = :id");
        $updateStmt->execute([':q' => $newQuantity, ':id' => $item['id']]);
    } else {
        // ➕ إدخال منتج جديد في الكارت
        $insertStmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart, :pid, :q)");
        $insertStmt->execute([':cart' => $cart_id, ':pid' => $product_id, ':q' => $quantity]);
    }

    http_response_code(201);
    echo json_encode(["status" => true, "message" => "Product added to cart successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => $e->getMessage()]);
}

