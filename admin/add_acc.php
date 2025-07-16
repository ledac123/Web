<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['username'])) {
  header("Location: ../login.php");
  exit();
}

// Thêm acc
if (isset($_POST['add'])) {
  $info = mysqli_real_escape_string($conn, $_POST['info']);
  $price = intval($_POST['price']);
  $danhmuc_id = intval($_POST['danhmuc_id']);

  mysqli_query($conn, "INSERT INTO accounts (info, price, status, danhmuc_id) VALUES ('$info', $price, 'available', $danhmuc_id)");
  $msg = "✅ Đã thêm acc thành công!";
}

// Lấy danh sách danh mục
$danhmucs = mysqli_query($conn, "SELECT * FROM danhmuc");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm ACC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="text-primary mb-4">➕ Thêm ACC</h3>
  <a href="dashboard.php" class="btn btn-dark mb-4">← Về Dashboard</a>

  <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Thông tin acc</label>
      <textarea name="info" class="form-control" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Giá</label>
      <input type="number" name="price" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Chọn danh mục</label>
      <select name="danhmuc_id" class="form-select" required>
        <option value="">-- Chọn danh mục --</option>
        <?php while ($dm = mysqli_fetch_assoc($danhmucs)): ?>
          <option value="<?= $dm['id'] ?>"><?= htmlspecialchars($dm['ten']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <button name="add" class="btn btn-primary">Thêm acc</button>
  </form>
</div>
</body>
</html>
