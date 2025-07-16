<?php
session_start();
include "config/db.php";

$success = $error = "";

if (isset($_POST['register'])) {
  $user = mysqli_real_escape_string($conn, $_POST['username']);
  $pass = mysqli_real_escape_string($conn, $_POST['password']);
  $hash = md5($pass); // Có thể thay bằng password_hash() nếu cần nâng cao bảo mật

  $bonus = rand(1000, 5000); // Tặng tiền khi đăng ký

  $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");
  if (mysqli_num_rows($check) > 0) {
    $error = "⚠️ Tài khoản đã tồn tại!";
  } else {
    $query = "INSERT INTO users (username, password, balance) VALUES ('$user', '$hash', $bonus)";
    if (mysqli_query($conn, $query)) {
      $_SESSION['success'] = "✅ Đăng ký thành công! Vui lòng đăng nhập.";
      header("Location: login.php");
      exit();
    } else {
      $error = "❌ Lỗi hệ thống: " . mysqli_error($conn);
    }
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng Ký</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="https://i.postimg.cc/NLvc3ss5/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      font-family: 'Urbanist', sans-serif;
    }

    body {
      margin: 0;
      background: linear-gradient(135deg, #0f172a, #1e293b);
      color: #fff;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .register-wrapper {
      display: flex;
      width: 95%;
      max-width: 1000px;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      background-color: #1e293b;
      animation: fadeIn 0.6s ease;
    }

    .register-img {
      flex: 1;
      background: url('https://i.postimg.cc/ydJ5TMmx/main.png') no-repeat center center;
      background-size: cover;
    }

    .register-form {
      flex: 1;
      padding: 60px 40px;
      background: #1e293b;
    }

    .register-form h2 {
      font-weight: 700;
      font-size: 28px;
      margin-bottom: 30px;
      text-align: center;
      color: #f1f5f9;
    }

    label {
      font-weight: 500;
      color: #cbd5e1;
      margin-bottom: 8px;
    }

    .form-control {
      background: #0f172a;
      border: 1px solid #334155;
      color: #fff;
      border-radius: 14px;
      padding: 14px;
      margin-bottom: 20px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: #4f81ff;
      box-shadow: 0 0 0 0.2rem rgba(79, 129, 255, 0.25);
    }

    .btn-register, .btn-login {
      padding: 14px;
      width: 100%;
      border-radius: 14px;
      font-weight: 600;
      font-size: 16px;
      transition: background 0.3s ease, color 0.3s ease;
    }

    .btn-register {
      background-color: #4f81ff;
      color: #fff;
      border: none;
      margin-bottom: 14px;
    }

    .btn-register:hover {
      background-color: #3b6ce0;
    }

    .btn-login {
      background-color: transparent;
      border: 2px solid #4f81ff;
      color: #4f81ff;
    }

    .btn-login:hover {
      background-color: #4f81ff;
      color: #fff;
    }

    .alert {
      text-align: center;
      font-size: 15px;
      margin-bottom: 20px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .register-wrapper {
        flex-direction: column;
      }
      .register-img {
        display: none;
      }
      .register-form {
        padding: 40px 25px;
      }
    }
  </style>
</head>
<body>

<div class="register-wrapper">
  <div class="register-img"></div>

  <div class="register-form">
    <h2>📝 Đăng Ký Tài Khoản</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label for="username">Tài khoản</label>
        <input type="text" name="username" id="username" class="form-control" placeholder="Nhập tên đăng nhập" required>
      </div>
      <div class="mb-3">
        <label for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="Nhập mật khẩu" required>
      </div>
      <button type="submit" name="register" class="btn btn-register">Đăng Ký</button>
      <a href="login.php" class="btn btn-login text-center">🔐 Đăng Nhập</a>
    </form>
  </div>
</div>

</body>
</html>
