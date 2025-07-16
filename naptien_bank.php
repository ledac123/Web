<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Nạp Qua Ngân Hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      font-family: 'Urbanist', sans-serif;
    }
    body {
      background: #0f172a;
      color: #fff;
      margin: 0;
      padding: 40px 10px;
    }
    .container {
      max-width: 1100px;
      margin: auto;
    }
    .card-bank {
      background-color: #1e293b;
      border-radius: 16px;
      padding: 25px 30px;
      color: #e2e8f0;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      margin-bottom: 25px;
    }
    .card-bank i {
      font-size: 35px;
      color: #60a5fa;
      margin-right: 15px;
    }
    .card-bank h5 {
      color: #60a5fa;
      margin-bottom: 15px;
    }
    .card-bank .info-line b {
      color: #93c5fd;
    }
    .note {
      font-size: 14px;
      color: #94a3b8;
      margin-top: 20px;
    }
    .steps {
      background-color: #1e293b;
      padding: 20px 25px;
      border-radius: 16px;
      margin-top: 30px;
    }
    .steps h5 {
      color: #60a5fa;
      margin-bottom: 20px;
    }
    .steps ul {
      padding-left: 20px;
      font-size: 15px;
      color: #cbd5e1;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="row g-4">
    <!-- MB Bank -->
    <div class="col-md-4">
      <div class="card-bank">
        <h5><i class="bi bi-bank"></i> MBBank</h5>
        <p class="info-line"><b>Tên:</b> Truong Thi Dung</p>
        <p class="info-line"><b>Số TK:</b> 0375894435</p>
        <p class="info-line"><b>Ngân hàng:</b> MB Bank</p>
        <p class="info-line"><b>Nội dung:</b> NapTien <?= htmlspecialchars($_SESSION['username']) ?></p>
      </div>
    </div>

    <!-- Zalo Pay -->
    <div class="col-md-4">
      <div class="card-bank">
        <h5><i class="bi bi-phone"></i> Zalo Pay</h5>
        <p class="info-line"><b>Tên:</b> Tran Thi Mai Han</p>
        <p class="info-line"><b>SĐT:</b> 0356353712</p>
        <p class="info-line"><b>Ví:</b> Zalo Pay</p>
        <p class="info-line"><b>Ghi chú:</b> NapTien <?= htmlspecialchars($_SESSION['username']) ?></p>
      </div>
    </div>

    <!-- GạchThe1s -->
    <div class="col-md-4">
      <div class="card-bank">
        <h5><i class="bi bi-credit-card-2-back"></i> GạchThe1s</h5>
        <p class="info-line"><b>Tài khoản:</b> ngthaocuti</p>
        <p class="info-line"><b>Ví:</b> GạchThe1s</p>
        <p class="info-line"><b>Ghi chú:</b> NapTien <?= htmlspecialchars($_SESSION['username']) ?></p>
      </div>
    </div>
  </div>

  <!-- Hướng dẫn -->
  <div class="steps">
    <h5>📌 Hướng Dẫn Nạp Tiền</h5>
    <ul>
      <li>Bước 1: Mở app ngân hàng / ví điện tử để thực hiện chuyển khoản.</li>
      <li>Bước 2: Nhập đúng số tài khoản, tên người nhận và số tiền muốn nạp.</li>
      <li>Bước 3: Ghi đúng nội dung chuyển khoản theo hướng dẫn.</li>
      <li>Bước 4: Hoàn tất giao dịch và chờ hệ thống xử lý.</li>
      <li>Bước 5: Nếu chưa nhận được tiền, hãy gửi bill cho Admin để được hỗ trợ.</li>
    </ul>
    <p class="note">⚠️ Lưu ý: Nếu hệ thống không tự cộng coin, vui lòng chụp lại bill và liên hệ admin nhé!</p>
  </div>

  <!-- Nút quay lại & nạp bằng thẻ -->
  <div class="d-flex justify-content-center my-4" style="gap: 15px;">
    <a href="index.php" class="btn btn-light" style="border-radius: 12px; font-weight: 600;">
      ← Quay lại Trang Chủ
    </a>
  
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
