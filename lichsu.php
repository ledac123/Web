<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Hiển thị thông báo mua hàng thành công nếu có
$showPurchaseSuccess = false;
$purchaseData = [];
if (isset($_SESSION['purchase_success'])) {
    $showPurchaseSuccess = true;
    $purchaseData = $_SESSION['purchase_success'];
    unset($_SESSION['purchase_success']);
}

// Lấy lịch sử mua hàng
$username = $_SESSION['username'];
$stmt = mysqli_prepare($conn, "SELECT * FROM purchase_history WHERE username = ? ORDER BY time DESC");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$purchaseHistory = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Mua Hàng - Paww Soda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Sử dụng cùng font và style với index.php */
        :root {
            --primary-color: #0c1532;
            --secondary-color: #142253;
            --accent-color: #00e1b3;
            --text-color: #ffffff;
            --font-main: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
        }
        
        body {
            font-family: var(--font-main);
            background-color: var(--primary-color);
            color: var(--text-color);
        }
        
        .history-item {
            background-color: var(--secondary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s;
        }
        
        .history-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .history-product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .history-time {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        
        .history-price {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .history-info {
            background-color: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.95rem;
        }
        
        /* Cải thiện độ rõ của font chữ */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<!-- Phần header, sidebar giống index.php -->
<!-- ... -->

<div class="content">
    <h4 class="section-title">LỊCH SỬ MUA HÀNG</h4>
    
    <?php if ($showPurchaseSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <h5><i class="bi bi-check-circle-fill"></i> Mua hàng thành công!</h5>
            <p>Bạn đã mua <strong><?= htmlspecialchars($purchaseData['product_name']) ?></strong> với số lượng <strong><?= $purchaseData['quantity'] ?></strong></p>
            <p>Tổng tiền: <strong><?= number_format($purchaseData['total_price']) ?>đ</strong></p>
            <?php if (!empty($purchaseData['product_info'])): ?>
                <div class="mt-2 p-2 bg-light rounded">
                    <h6>Thông tin sản phẩm:</h6>
                    <p><?= nl2br(htmlspecialchars($purchaseData['product_info'])) ?></p>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($purchaseHistory)): ?>
        <div class="history-list">
            <?php foreach ($purchaseHistory as $item): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="history-product-name"><?= htmlspecialchars($item['product_name']) ?></h5>
                            <span class="history-time"><?= date("H:i d/m/Y", strtotime($item['time'])) ?></span>
                        </div>
                        <div class="text-end">
                            <div class="history-price"><?= number_format($item['price']) ?>đ</div>
                            <div class="text-muted">Số lượng: <?= $item['amount'] ?></div>
                        </div>
                    </div>
                    
                    <!-- Hiển thị thông tin sản phẩm nếu có -->
                    <?php 
                        // Lấy thêm thông tin sản phẩm từ bảng orders nếu cần
                        $orderInfo = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT info FROM orders WHERE username = '".mysqli_real_escape_string($conn, $username)."' 
                             AND product_name = '".mysqli_real_escape_string($conn, $item['product_name'])."' 
                             AND time = '".mysqli_real_escape_string($conn, $item['time'])."' LIMIT 1"));
                    ?>
                    <?php if (!empty($orderInfo['info'])): ?>
                        <div class="history-info mt-3">
                            <h6><i class="bi bi-info-circle"></i> Thông tin sản phẩm:</h6>
                            <p><?= nl2br(htmlspecialchars($orderInfo['info'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> Bạn chưa có giao dịch mua hàng nào.
        </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>