<?php
// File: product_detail.php
session_start();
require 'config/db.php';

// Lấy sản phẩm (ví dụ lấy sản phẩm id = 1 để demo)
$product_id = 1;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chi Tiết Sản Phẩm</title>
  <style>
    body { background: #0f172a; color: white; font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 600px; margin: 40px auto; padding: 20px; background: #1e293b; border-radius: 12px; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
    .btn-primary { background: #3b82f6; color: white; }
    .btn-success { background: #10b981; color: white; }

    .modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center;
      z-index: 1000;
    }
    .modal-content {
      background: #1f2a37; color: white; border-radius: 12px; padding: 20px;
      width: 600px; max-width: 90%; box-shadow: 0 0 20px rgba(0,0,0,0.5); position: relative;
      animation: fadeIn 0.3s ease-in-out;
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; }
    .modal-body img { max-width: 100%; border-radius: 8px; margin-bottom: 15px; }
    .modal-footer { text-align: right; margin-top: 20px; }
    .close-btn { font-size: 22px; cursor: pointer; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><?= $product['name'] ?></h2>
    <img src="<?= $product['image'] ?>" alt="Sản phẩm" style="width:100%;border-radius:8px">
    <p><b>Giá:</b> 10.000 VNĐ</p>
    <p><b>Mô Tả:</b> <?= $product['describe'] ?></p>
    <button class="btn btn-primary w-100" id="btn-mua" data-product-id="<?= $product['id'] ?>">🛒 Mua Ngay</button>
  </div>

  <!-- Modal -->
  <div id="popupModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Thông Tin Sản Phẩm</h3>
        <span class="close-btn" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modal-body-content">
        <!-- Nội dung load bằng JS -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" onclick="confirmMua()">Xác Nhận Mua</button>
      </div>
    </div>
  </div>

  <script>
    function closeModal() {
      document.getElementById('popupModal').style.display = 'none';
    }

    document.getElementById('btn-mua').addEventListener('click', function () {
      const productId = this.getAttribute('data-product-id');

      fetch('product_detail.php?action=info&id=' + productId)
        .then(response => response.json())
        .then(data => {
          document.getElementById('popupModal').style.display = 'flex';
          document.getElementById('modal-body-content').innerHTML = `
            <img src="${data.image}" alt="Sản phẩm">
            <p><b>Loại:</b> ${data.category}</p>
            <p><b>Người Bán:</b> ${data.seller}</p>
            <p><b>Mô Tả:</b> ${data.describe}</p>
            <p><b>Ngày Cập Nhật:</b> ${data.update}</p>
            <input type="hidden" id="hidden-product-id" value="${data.id}">
          `;
        });
    });

    function confirmMua() {
      const id = document.getElementById('hidden-product-id').value;

      fetch('product_detail.php?action=mua', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + id
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          document.getElementById('modal-body-content').innerHTML = `
            <h4 style="color: lightgreen;">🎉 Mua hàng thành công!</h4>
            <p><b>Quà của bạn:</b></p>
            <div style="background: #2d3748; padding: 10px; border-radius: 8px;">
              ${data.gift}
            </div>
          `;
          document.querySelector('.modal-footer').style.display = 'none';
        } else {
          alert("Có lỗi: " + data.message);
        }
      });
    }
  </script>
</body>
</html>

<?php
// PHẦN BACKEND CHO AJAX
if (isset($_GET['action']) && $_GET['action'] === 'info') {
  $id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'id' => $product['id'],
    'image' => $product['image'],
    'category' => $product['category'],
    'seller' => $product['seller'],
    'describe' => $product['describe'],
    'update' => $product['updated_at']
  ]);
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'mua') {
  if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
  }
  $user = $_SESSION['username'];
  $product_id = $_POST['product_id'];
  $price = 10000;

  // Lấy thông tin người dùng
  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$user]);
  $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($userInfo['balance'] < $price) {
    echo json_encode(['status' => 'error', 'message' => 'Không đủ tiền']);
    exit;
  }

  // Trừ tiền
  $conn->prepare("UPDATE users SET balance = balance - ? WHERE username = ?")->execute([$price, $user]);

  // Tạo gift code
  $gift_code = "FFGIFT-" . strtoupper(bin2hex(random_bytes(4)));
  $conn->prepare("INSERT INTO purchases (username, product_id, gift_code) VALUES (?, ?, ?)")
       ->execute([$user, $product_id, $gift_code]);

  echo json_encode(['status' => 'success', 'gift' => $gift_code]);
  exit;
}
?>
