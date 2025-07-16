<?php
session_start();
include "config/db.php";

if (isset($_POST['login'])) {
  $user = $_POST['username'];
  $pass = md5($_POST['password']);
  $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$user' AND password='$pass'");

  if (mysqli_num_rows($query) == 1) {
    $data = mysqli_fetch_assoc($query);
    if (!empty($data['locked_until']) && strtotime($data['locked_until']) > time()) {
      $error = "❌ Tài khoản bị khóa đến <b>" . date("H:i d/m/Y", strtotime($data['locked_until'])) . "</b>";
      $locked = true;
    } else {
      $_SESSION['username'] = $user;
      $_SESSION['login_success'] = true;
      header("Location: index.php");
      exit();
    }
  } else {
    $error = "⚠️ Sai tài khoản hoặc mật khẩu!";
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng Nhập</title>
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

    .login-wrapper {
      display: flex;
      width: 95%;
      max-width: 1000px;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      background-color: #1e293b;
      animation: fadeIn 0.6s ease;
    }

    .login-img {
      flex: 1;
      background: url('https://i.postimg.cc/ydJ5TMmx/main.png') no-repeat center center;
      background-size: cover;
    }

    .login-form {
      flex: 1;
      padding: 60px 40px;
      background: #1e293b;
    }

    .login-form h2 {
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

    .btn-login, .btn-register {
      padding: 14px;
      width: 100%;
      border-radius: 14px;
      font-weight: 600;
      font-size: 16px;
      transition: background 0.3s ease, color 0.3s ease;
    }

    .btn-login {
      background-color: #4f81ff;
      color: #fff;
      border: none;
      margin-bottom: 14px;
    }

    .btn-login:hover {
      background-color: #3b6ce0;
    }

    .btn-register {
      background-color: transparent;
      border: 2px solid #4f81ff;
      color: #4f81ff;
    }

    .btn-register:hover {
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
      .login-wrapper {
        flex-direction: column;
      }
      .login-img {
        display: none;
      }
      .login-form {
        padding: 40px 25px;
      }
    }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-img"></div>

  <div class="login-form">
    <h2>🔐 Đăng Nhập Tài Khoản</h2>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
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
      <button type="submit" name="login" class="btn btn-login">Đăng Nhập</button>
      <a href="register.php" class="btn btn-register text-center">📝 Đăng Ký</a>
    </form>

    <?php if (isset($locked) && $locked): ?>
      <form action="request_unlock.php" method="get" class="mt-3">
        <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
        <button class="btn btn-outline-light w-100 mt-2">🛡 Yêu cầu mở khóa</button>
      </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
