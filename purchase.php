<?php
session_start();
include("config.db.php");

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập']);
    exit();
}

$username = $_SESSION['username'];
$user = $conn->query("SELECT * FROM users WHERE username = '$username'")->fetch_assoc();

$product_id = intval($_POST['id'] ?? 0);
if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID sản phẩm không hợp lệ']);
    exit();
}

// Lấy sản phẩm
$product = $conn->query("SELECT * FROM products WHERE id = $product_id AND quantity > 0")->fetch_assoc();
if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại hoặc đã hết']);
    exit();
}

if ($user['balance'] < $product['price']) {
    echo json_encode(['status' => 'error', 'message' => 'Không đủ số dư']);
    exit();
}

// Tìm key trong bảng keys
$key_row = $conn->query("SELECT * FROM product_keys WHERE product_id = $product_id AND used = 0 LIMIT 1")->fetch_assoc();
if (!$key_row) {
    echo json_encode(['status' => 'error', 'message' => 'Sản phẩm đã hết key']);
    exit();
}

// Trừ tiền và cập nhật dữ liệu
$conn->begin_transaction();

try {
    // Trừ tiền
    $conn->query("UPDATE users SET balance = balance - {$product['price']} WHERE username = '$username'");

    // Đánh dấu key đã dùng
    $conn->query("UPDATE product_keys SET used = 1, used_by = '$username', used_at = NOW() WHERE id = {$key_row['id']}");

    // Trừ số lượng
    $conn->query("UPDATE products SET quantity = quantity - 1 WHERE id = $product_id");

    // Lưu lịch sử
    $key = $key_row['key_value'];
    $conn->query("INSERT INTO purchase_history (username, product_id, key_value, price, purchased_at) 
                  VALUES ('$username', $product_id, '$key', {$product['price']}, NOW())");

    $conn->commit();

    echo json_encode(['status' => 'success', 'key' => $key]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Giao dịch thất bại']);
}
