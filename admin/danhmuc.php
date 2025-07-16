<?php
session_start();
include "../config/db.php";

// Chỉ cho phép admin truy cập
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
  header("Location: ../index.php");
  exit();
}

// Xử lý thêm danh mục
if (isset($_POST['add'])) {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $imageName = $_FILES['image']['name'];
  $imageTmp = $_FILES['image']['tmp_name'];

  // Di chuyển ảnh lên thư mục uploads
  $uploadDir = '../uploads/';
  $uploadPath = $uploadDir . basename($imageName);
  move_uploaded_file($imageTmp, $uploadPath);

  // Thêm vào DB
  $sql = "INSERT INTO categories (name, image) VALUES ('$name', '$uploadPath')";
  mysqli_query($conn, $sql);
  header("Location: danhmuc.php");
  exit();
}

// Xử lý xóa danh mục
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
  header("Location: danhmuc.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý danh mục</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h3 class="mb-4 text-primary fw-bold">Quản lý danh mục</h3>

  <!-- Thêm danh mục -->
  <form method="POST" enctype="multipart/form-data" class="mb-4 p-4 bg-white rounded shadow-sm">
    <div class="mb-3">
      <label>Tên danh mục</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Ảnh danh mục</label>
      <input type="file" name="image" class="form-control" accept="image/*" required>
    </div>
    <button type="submit" name="add" class="btn btn-primary">Thêm</button>
  </form>

  <!-- Danh sách danh mục -->
  <div class="table-responsive bg-white p-3 rounded shadow-sm">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Ảnh</th>
          <th>Tên</th>
          <th>Ngày tạo</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($query)):
        ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><img src="<?= $row['image'] ?>" width="80" class="rounded"></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= $row['created_at'] ?></td>
          <td>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Xác nhận xoá?')" class="btn btn-sm btn-danger">Xoá</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
