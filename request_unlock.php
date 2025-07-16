<?php
include "config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $check = mysqli_query($conn, "SELECT * FROM unlock_requests WHERE username='$user'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO unlock_requests (username, message) VALUES ('$user', '$message')");
        $msg = "✅ Yêu cầu mở khóa đã gửi!";
    } else {
        $msg = "⚠️ Bạn đã gửi yêu cầu rồi. Vui lòng chờ duyệt!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Yêu cầu mở khoá</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="text-center mb-4">
    <h4>📝 Gửi yêu cầu mở khoá</h4>
    <?php if (isset($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>
  </div>
  <form method="post" class="mx-auto" style="max-width: 400px;">
    <input type="text" name="username" placeholder="Tên tài khoản" class="form-control mb-3" required>
    <textarea name="message" placeholder="Lý do hoặc lời nhắn..." class="form-control mb-3" rows="3"></textarea>
    <button class="btn btn-primary w-100">Gửi yêu cầu</button>
    <a href="login.php" class="btn btn-link mt-3">← Quay lại đăng nhập</a>
  </form>
</div>
</body>
</html>
