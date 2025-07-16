<?php
session_start();
include "config/db.php";
if (!isset($_SESSION['username'])) {
  header('Location: login.php');
  exit;
}
$user = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Lịch sử nạp thẻ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <h4 class="mb-4 text-primary fw-bold">Lịch sử nạp thẻ</h4>
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Thời gian</th>
        <th>Loại thẻ</th>
        <th>Mệnh giá</th>
        <th>Serial</th>
        <th>Trạng thái</th>
        <th>Ghi chú</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $q = mysqli_query($conn, "SELECT * FROM card_logs WHERE username='$user' ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($q)) {
        echo "<tr>
          <td>{$row['created_at']}</td>
          <td>{$row['telco']}</td>
          <td>" . number_format($row['amount']) . "đ</td>
          <td>{$row['serial']}</td>
          <td><span class='badge bg-".($row['status']=='success'?'success':($row['status']=='fail'?'danger':'warning'))."'>" . strtoupper($row['status']) . "</span></td>
          <td>{$row['message']}</td>
        </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

</body>
</html>
