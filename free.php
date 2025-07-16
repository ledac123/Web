<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'config/db.php';

// Lấy danh sách sản phẩm free từ admin
$sql = "SELECT * FROM free_products ORDER BY time DESC";
$result = $conn->query($sql);
$free_items = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $free_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Free Sản Phẩm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #0f172a;
      color: #fff;
      font-family: 'Urbanist', sans-serif;
      padding: 40px 10px;
    }
    .container {
      max-width: 1100px;
      margin: auto;
    }
    h3 {
      color: #60a5fa;
      margin-bottom: 20px;
    }
    .free-item {
      background-color: #1e293b;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      transition: 0.3s ease;
    }
    .free-item:hover {
      background-color: #334155;
      transform: translateY(-4px);
    }
    .free-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 12px;
      margin-right: 20px;
    }
    .free-link {
      text-decoration: none;
      color: #fff;
    }
    .free-link:hover {
      text-decoration: underline;
    }
    .free-description {
      color: #94a3b8;
    }
    .back-btn {
      display: inline-block;
      padding: 12px 24px;
      background-color: #334155;
      color: #f1f5f9;
      text-decoration: none;
      border-radius: 12px;
      transition: 0.3s ease;
      margin-top: 30px;
    }
    .back-btn:hover {
      background-color: #475569;
    }
  </style>
</head>
<body>

<div class="container">
  <h3>🎁 Danh Sách Sản Phẩm Free Từ Admin</h3>

  <?php if (count($free_items) == 0): ?>
    <div class="alert alert-warning text-dark">Hiện tại chưa có sản phẩm free nào được admin cung cấp.</div>
  <?php else: ?>
    <?php foreach ($free_items as $item): ?>
      <a href="<?= htmlspecialchars($item['link']) ?>" class="free-link">
        <div class="d-flex align-items-center free-item">
          <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="Free Product">
          <div>
            <h5 class="mb-1"><?= htmlspecialchars($item['title']) ?></h5>
            <p class="free-description mb-0"><?= htmlspecialchars($item['description']) ?></p>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="text-center">
    <a href="index.php" class="back-btn">← Quay lại Home</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
