<?php
include "config/db.php";
if (isset($_POST['register'])) {
  $user = $_POST['username'];
  $pass = md5($_POST['password']);
  $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");
  if (mysqli_num_rows($check) > 0) {
    echo "Tài khoản đã tồn tại!";
  } else {
    mysqli_query($conn, "INSERT INTO users (username, password) VALUES ('$user', '$pass')");
    echo "Đăng ký thành công!";
  }
}
?>
<form method="post">
  <input name="username" placeholder="Tên tài khoản">
  <input name="password" placeholder="Mật khẩu" type="password">
  <button name="register">Đăng ký</button>
</form>