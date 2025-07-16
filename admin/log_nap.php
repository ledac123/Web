<?php
session_start();
include "../config/db.php";

// Chỉ cho phép admin
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
  header("Location: ../index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Lịch sử nạp tiền toàn hệ thống</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h4 class="mb-4 text-primary fw-bold">Lịch sử nạp thẻ toàn hệ thống</h4>

  <div class="table-responsive bg-white p-3 rounded shadow-sm">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Người dùng</th>
          <th>Loại thẻ</th>
          <th>Mệnh giá</th>
          <th>Serial</th>
          <th>Trạng thái</th>
          <th>Ghi chú</th>
          <th>Thời gian</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $q = mysqli_query($conn, "SELECT * FROM card_logs ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($q)):
        ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= strtoupper($row['telco']) ?></td>
          <td><?= number_format($row['amount']) ?>đ</td>
          <td><?= $row['serial'] ?></td>
          <td>
            <?php
              $status = $row['status'];
              if ($status == 'success') echo '<span class="badge bg-success">Thành công</span>';
              elseif ($status == 'fail') echo '<span class="badge bg-danger">Thất bại</span>';
              else echo '<span class="badge bg-warning text-dark">Đang xử lý</span>';
            ?>
          </td>
          <td><?= $row['message'] ?></td>
          <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary">← Quay lại Dashboard</a>
  </div>
</div>
</body>
</html>
