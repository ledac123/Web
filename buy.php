<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['username'])) {
  die("⛔ Vui lòng đăng nhập để mua sản phẩm.");
}

$username = $_SESSION['username'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE username='$username'"));

if (!isset($_GET['id'])) {
  die("⛔ Thiếu ID sản phẩm.");
}

$id = intval($_GET['id']);
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id AND status='available'"));

if (!$product) {
  die("❌ Sản phẩm không tồn tại hoặc đã bán.");
}

if ($user['balance'] < $product['price']) {
  die("💸 Bạn không đủ tiền để mua sản phẩm này.");
}

// Trừ tiền người dùng
$newBalance = $user['balance'] - $product['price'];
mysqli_query($conn, "UPDATE users SET balance=$newBalance WHERE username='$username'");
mysqli_query($conn, "INSERT INTO orders (username, product_id, price) VALUES ('$username', $id, {$product['price']})");

// Cập nhật trạng thái sản phẩm
mysqli_query($conn, "UPDATE products SET status='sold' WHERE id=$id");

// (Tuỳ chọn) Ghi lại đơn hàng nếu có bảng orders
// mysqli_query($conn, "INSERT INTO orders (username, product_id, price, created_at) VALUES ('$username', $id, {$product['price']}, NOW())");
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Mua thành công</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="alert alert-success shadow">
    <h4 class="alert-heading">🎉 Mua thành công!</h4>
    <p><b>Tên sản phẩm:</b> <?= htmlspecialchars($product['name']) ?></p>
    <p><b>Thông tin chi tiết:</b></p>
    <pre class="bg-light p-3 border rounded"><?= htmlspecialchars($product['info']) ?></pre>
    <hr>
    <p><b>Số dư còn lại:</b> <?= number_format($newBalance) ?>đ</p>
    <a href="index.php" class="btn btn-primary mt-3">← Quay lại trang chủ</a>
  </div>
</div>

</body>
</html>
