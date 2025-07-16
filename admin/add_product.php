<?php
session_start();
include "../config/db.php";

// Chỉ cho phép admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
  header("Location: ../login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = $_POST['name'];
  $description = $_POST['description'];
  $price = intval($_POST['price']);
  $image_url = $_POST['image_url'];

  $sql = "INSERT INTO products (name, description, price, image_url) 
          VALUES ('$name', '$description', '$price', '$image_url')";
  if (mysqli_query($conn, $sql)) {
    $success = "✅ Thêm sản phẩm thành công!";
  } else {
    $error = "❌ Lỗi: " . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm Sản Phẩm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-dark text-white">
  <h2>🛒 Thêm sản phẩm mới</h2>

  <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Tên sản phẩm</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mô tả</label>
      <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Giá (VNĐ)</label>
      <input type="number" name="price" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Link ảnh sản phẩm</label>
      <input type="text" name="image_url" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Thêm sản phẩm</button>
    <a href="dashboard.php" class="btn btn-secondary">Quay lại Dashboard</a>
  </form>
</body>
</html>
