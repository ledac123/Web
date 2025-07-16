<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Duyệt mở khóa
if (isset($_GET['unlock'])) {
    $user = $_GET['unlock'];
    mysqli_query($conn, "UPDATE users SET locked_until = NULL WHERE username='$user'");
    mysqli_query($conn, "DELETE FROM unlock_requests WHERE username='$user'");
    $msg = "✅ Đã mở khoá cho tài khoản '$user'";
}

$requests = mysqli_query($conn, "SELECT * FROM unlock_requests ORDER BY requested_at DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Yêu cầu mở khóa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="text-center mb-4">📥 Yêu cầu mở khoá tài khoản</h3>

  <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

  <table class="table table-bordered bg-white">
    <thead>
      <tr>
        <th>Username</th>
        <th>Lời nhắn</th>
        <th>Thời gian</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = mysqli_fetch_assoc($requests)): ?>
      <tr>
        <td><?= htmlspecialchars($r['username']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['message'])) ?></td>
        <td><?= $r['requested_at'] ?></td>
        <td>
          <a href="?unlock=<?= urlencode($r['username']) ?>" class="btn btn-sm btn-success">✔ Mở khóa</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <a href="dashboard.php" class="btn btn-dark mt-4">← Quay lại quản trị</a>
</div>
</body>
</html>
