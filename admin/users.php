<?php
session_start();
include "../config/db.php";

// Chỉ admin được truy cập
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
  header("Location: ../index.php");
  exit();
}

// Cộng/trừ tiền
if (isset($_POST['action']) && isset($_POST['username']) && isset($_POST['amount'])) {
  $user = mysqli_real_escape_string($conn, $_POST['username']);
  $amount = intval($_POST['amount']);

  if ($_POST['action'] == 'add') {
    mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE username = '$user'");
  } elseif ($_POST['action'] == 'subtract') {
    mysqli_query($conn, "UPDATE users SET balance = GREATEST(balance - $amount, 0) WHERE username = '$user'");
  }
  header("Location: users.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý người dùng nâng cao</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h4 class="mb-4 fw-bold text-primary">Quản lý người dùng</h4>

  <div class="table-responsive bg-white rounded shadow-sm p-3">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Số dư</th>
          <th>Tổng nạp</th>
          <th>Tổng mua</th>
          <th>Cộng / Trừ tiền</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $q = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($q)):
          $u = $row['username'];

          $tongnap = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(amount) FROM card_logs WHERE username='$u' AND status='success'"))[0] ?? 0;
          $tongmua = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price) FROM purchase_logs WHERE username='$u'"))[0] ?? 0;
        ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($u) ?></td>
          <td><?= number_format($row['balance']) ?>đ</td>
          <td class="text-success"><?= number_format($tongnap) ?>đ</td>
          <td class="text-danger"><?= number_format($tongmua) ?>đ</td>
          <td>
            <?php if ($u != 'admin'): ?>
              <form method="POST" class="d-flex" style="gap: 5px;">
                <input type="hidden" name="username" value="<?= $u ?>">
                <input type="number" name="amount" min="1000" class="form-control form-control-sm" placeholder="Số tiền" required>
                <button name="action" value="add" class="btn btn-sm btn-success">+</button>
                <button name="action" value="subtract" class="btn btn-sm btn-danger">-</button>
              </form>
            <?php else: ?>
              <span class="text-muted">Admin</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <a href="dashboard.php" class="btn btn-outline-secondary mt-4">← Về Dashboard</a>
</div>
</body>
</html>
