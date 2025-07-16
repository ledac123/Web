<?php
session_start();
include "config/db.php";
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}
$username = $_SESSION['username'];
$msg = '';

if (isset($_POST['change'])) {
  $old = md5($_POST['old']);
  $new = md5($_POST['new']);
  $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$old'");
  if (mysqli_num_rows($check) == 1) {
    mysqli_query($conn, "UPDATE users SET password='$new' WHERE username='$username'");
    $msg = "<div class='toast-msg success'>✅ Đổi mật khẩu thành công!</div>";
  } else {
    $msg = "<div class='toast-msg error'>❌ Mật khẩu cũ không đúng!</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đổi mật khẩu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #0e1a2b;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }
    .card {
      background: rgba(20, 30, 48, 0.85);
      border: none;
      border-radius: 16px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(8px);
    }
    .form-control {
      background-color: #1e2a3a;
      color: #fff;
      border: 1px solid #334;
      border-radius: 8px;
    }
    .form-control:focus {
      border-color: #4dabf7;
      box-shadow: 0 0 0 0.2rem rgba(77, 171, 247, 0.25);
    }
    .btn-primary {
      background: #4dabf7;
      border: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background: #339af0;
    }
    .btn-outline-secondary {
      border-radius: 8px;
      color: #ccc;
      border-color: #555;
    }
    .btn-outline-secondary:hover {
      background-color: #2a2f3a;
      color: #fff;
    }
    .toast-msg {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 16px;
      text-align: center;
      font-weight: bold;
    }
    .toast-msg.success {
      background-color: #198754;
      color: white;
    }
    .toast-msg.error {
      background-color: #dc3545;
      color: white;
    }
    .title-icon {
      font-size: 1.7rem;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow p-4">
          <div class="card-body">
            <h4 class="mb-4 text-center title-icon">🔐 Đổi mật khẩu</h4>
            <?= $msg ?>
            <form method="post">
              <div class="mb-3">
                <label class="form-label">Mật khẩu cũ</label>
                <input type="password" name="old" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Mật khẩu mới</label>
                <input type="password" name="new" class="form-control" required>
              </div>
              <button name="change" class="btn btn-primary w-100">✅ Cập nhật mật khẩu</button>
              <a href="index.php" class="btn btn-outline-secondary w-100 mt-3">⬅️ Quay lại Trang Chủ</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
