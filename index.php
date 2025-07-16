<?php
session_start();
include "config/db.php";

// Thêm ở đầu file
if (!$conn) {
    die("Lỗi kết nối database: " . mysqli_connect_error());
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Hiển thị thông báo đăng nhập thành công
$showLoginToast = false;
if (isset($_SESSION['login_success'])) {
    $showLoginToast = true;
    unset($_SESSION['login_success']);
}

// Lấy thông tin người dùng
$username = $_SESSION['username'] ?? '';
if (empty($username)) {
    header("Location: login.php");
    exit();
}
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

// Kiểm tra quyền admin/mod
$isAdmin = isset($user['role']) && in_array($user['role'], ['admin','mod']);
$balance = number_format($user['balance']);
$totalDeposited = number_format($user['total_deposited'] ?? 0);
$createdAt = date("d/m/Y", strtotime($user['created_at']));

// Xử lý mua hàng
if (isset($_POST['buy_product'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);

    // Lấy thông tin sản phẩm
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    // Kiểm tra điều kiện mua hàng
    if (!$product) {
        $buy_error = "❌ Sản phẩm không tồn tại.";
    } elseif ($product['quantity'] < $quantity) {
        $buy_error = "❌ Không đủ sản phẩm trong kho.";
    } elseif ($user['balance'] < $product['price'] * $quantity) {
        $buy_error = "❌ Bạn không đủ tiền.";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            $total_price = $product['price'] * $quantity;
            $new_balance = $user['balance'] - $total_price;
            $new_quantity = $product['quantity'] - $quantity;
            $timeNow = date("Y-m-d H:i:s");

            // Cập nhật số dư người dùng
            $stmt = mysqli_prepare($conn, "UPDATE users SET balance = ? WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "ds", $new_balance, $username);
            mysqli_stmt_execute($stmt);

            // Cập nhật số lượng sản phẩm
            $stmt = mysqli_prepare($conn, "UPDATE products SET quantity = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_quantity, $product_id);
            mysqli_stmt_execute($stmt);

            // Tạo đơn hàng
            $stmt = mysqli_prepare($conn, "INSERT INTO orders (username, product_name, price, info, status, created_at, secret) 
                                         VALUES (?, ?, ?, ?, 'completed', ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdsss", $username, $product['name'], $total_price, $product['info'], $timeNow, $product['secret']);
            mysqli_stmt_execute($stmt);

            // Lưu lịch sử mua hàng
            $stmt = mysqli_prepare($conn, "INSERT INTO purchase_history (username, product_name, amount, price, time) 
                                         VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdss", $username, $product['name'], $quantity, $total_price, $timeNow);
            mysqli_stmt_execute($stmt);

            // Lưu lịch sử giao dịch
            $desc = "Mua {$product['name']} x$quantity";
            $stmt = mysqli_prepare($conn, "INSERT INTO account_history (username, type, amount, description, time) 
                                         VALUES (?, '-', ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sdss", $username, $total_price, $desc, $timeNow);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            
            // Lưu thông tin mua hàng thành công
            $_SESSION['purchase_success'] = [
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'total_price' => $total_price,
                'time' => $timeNow
            ];
            
            // Chuyển hướng đến trang lịch sử
            header("Location: lichsu.php");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $buy_error = "❌ Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Phân trang sản phẩm
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

$productQuery = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT $offset, $perPage");
$totalProducts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$totalPages = ceil($totalProducts / $perPage);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paww Soda - Trang chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0c1532;
            --secondary-color: #142253;
            --accent-color: #00e1b3;
            --text-color: #ffffff;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: var(--primary-color);
            color: var(--text-color);
            font-family: 'Urbanist', sans-serif;
            min-height: 100vh;
        }
        
        /* Loading */
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--primary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loader::after {
            content: "";
            width: 50px;
            height: 50px;
            border: 5px solid var(--accent-color);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Layout */
        .topbar {
            height: 70px;
            background-color: var(--secondary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .sidebar {
            height: calc(100vh - 70px);
            width: 230px;
            background-color: var(--secondary-color);
            position: fixed;
            top: 70px;
            left: 0;
            padding-top: 20px;
            overflow-y: auto;
            transition: all 0.3s;
        }
        
        .content {
            margin-left: 230px;
            padding: 30px;
            min-height: calc(100vh - 70px);
        }
        
        /* Logo */
        .logo {
            font-family: 'Urbanist', sans-serif;
            font-weight: 700;
            font-size: 24px;
            background: linear-gradient(90deg, var(--accent-color), #00a8ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .logo:hover {
            text-shadow: 0 0 10px rgba(0, 225, 179, 0.5);
        }
        
        /* Sidebar */
        .sidebar a {
            display: block;
            padding: 15px 25px;
            margin: 5px 15px;
            border-radius: 12px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar a:hover, 
        .sidebar a.active {
            background-color: rgba(0, 225, 179, 0.1);
        }
        
        .submenu {
            display: none;
            margin-left: 20px;
        }
        
        /* Account Panel */
        #accountPanel {
            display: none;
            background-color: var(--secondary-color);
            border-radius: 15px;
            padding: 20px;
            position: absolute;
            right: 30px;
            top: 80px;
            z-index: 999;
            width: 300px;
            color: var(--text-color);
            box-shadow: 0 0 15px rgba(0,0,0,0.4);
            border: 1px solid var(--accent-color);
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        /* Product Card */
        .product-card {
            background-color: var(--secondary-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            border: 1px solid #2a3a7a;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            border-color: var(--accent-color);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--accent-color);
            margin-bottom: 15px;
        }
        
        .product-name {
            color: #f8f9fa;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 1.1rem;
            min-height: 50px;
        }
        
        .product-description {
            color: #bdc3c7;
            font-size: 0.9rem;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .product-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .stock-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        /* Buttons */
        .buy-btn {
            background-color: var(--accent-color);
            border: none;
            font-weight: 600;
            transition: all 0.3s;
            color: var(--primary-color);
        }
        
        .buy-btn:hover {
            background-color: #00c9a0;
            transform: translateY(-2px);
        }
        
        .buy-btn:disabled {
            background-color: #6c757d;
        }
        
        .detail-btn {
            transition: all 0.3s;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        .detail-btn:hover {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        /* Quantity Input */
        .input-group {
            width: 140px;
            margin: 0 auto 15px;
        }
        
        .quantity-input {
            text-align: center;
            background-color: var(--secondary-color);
            border-color: #2a3a7a;
            color: var(--text-color);
        }
        
        /* Welcome Text */
        .welcome-text {
            font-size: 18px;
            margin-bottom: 30px;
            background: linear-gradient(90deg, var(--secondary-color), #1d2d6e);
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            border-left: 4px solid var(--accent-color);
        }
        
        /* Banner */
        .banner-container {
            margin: 0 auto 30px;
            max-width: 1200px;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .banner-container img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s;
        }
        
        .banner-container:hover img {
            transform: scale(1.02);
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            margin: 40px 0 30px;
            padding-bottom: 10px;
            font-weight: 700;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), transparent);
        }
        
        /* Pagination */
        .pagination .page-item .page-link {
            background-color: var(--secondary-color);
            border-color: #2a3a7a;
            color: var(--text-color);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        /* Modal */
        .modal-content {
            background: linear-gradient(135deg, var(--secondary-color), #142253);
            border: 1px solid var(--accent-color);
        }
        
        .modal-header {
            border-bottom: 1px solid #2a3a7a;
        }
        
        .modal-footer {
            border-top: 1px solid #2a3a7a;
        }
        
        /* Confirm Dialog */
        .confirm-dialog {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .confirm-dialog.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirm-box {
            background: linear-gradient(135deg, var(--secondary-color), #142253);
            padding: 25px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--accent-color);
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .confirm-dialog.active .confirm-box {
            transform: translateY(0);
        }
        
        .confirm-title {
            color: var(--accent-color);
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .confirm-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        /* Badges */
        .badge-admin {
            background-color: #6f42c1;
        }
        
        .badge-mod {
            background-color: #fd7e14;
        }
        
        .badge-user {
            background-color: #20c997;
        }
        
        .account-status {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 10px;
        }
        
        .account-banned {
            background-color: var(--danger-color);
        }
        
        .account-active {
            background-color: var(--success-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%;
                z-index: 1000;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .content {
                margin-left: 0;
            }
            
            #accountPanel {
                right: 15px;
                width: 280px;
            }
        }
    </style>
</head>
<body>

<!-- Loading -->
<div class="loader" id="pageLoader"></div>

<!-- Topbar -->
<div class="topbar">
    <a href="index.php" class="logo">Paww Soda</a>
    <div class="topbar-icons">
        <i class="bi bi-bell" onclick="showToast('🔔 Không có thông báo mới')"></i>
        <i class="bi bi-cart" onclick="showToast('🛒 Giỏ hàng đang trống')"></i>
        <i class="bi bi-person-circle" onclick="toggleAccount()"></i>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <a href="index.php" class="active"><i class="bi bi-house-door"></i> Trang chủ</a>
    <a href="javascript:void(0);" onclick="toggleSubmenu()"><i class="bi bi-cash-stack"></i> Nạp Tiền</a>
    <div class="submenu" id="subMenu">
        <a href="naptien_card.php"><i class="bi bi-credit-card-2-back"></i> Nạp Card</a>
        <a href="naptien_bank.php"><i class="bi bi-bank"></i> Nạp Bank</a>
    </div>
    <a href="lichsu.php"><i class="bi bi-clock-history"></i> Lịch Sử</a>
    <?php if ($isAdmin): ?>
        <a href="admin/dashboard.php"><i class="bi bi-speedometer2"></i> Quản trị</a>
    <?php endif; ?>
</div>

<!-- Account Panel -->
<div id="accountPanel">
    <h6>👤 Thông tin tài khoản</h6>
    <hr>
    <p><strong>Tên:</strong> <?= htmlspecialchars($username) ?></p>
    <p><strong>Vai trò:</strong> 
        <?php if ($user['role'] === 'admin'): ?>
            <span class="badge badge-admin">Quản trị viên</span>
        <?php elseif ($user['role'] === 'mod'): ?>
            <span class="badge badge-mod">Moderator</span>
        <?php else: ?>
            <span class="badge badge-user">Thành viên</span>
        <?php endif; ?>
    </p>
    <p><strong>Số dư:</strong> <?= $balance ?>đ</p>
    <p><strong>Đã nạp:</strong> <?= $totalDeposited ?>đ</p>
    <p><strong>Trạng thái:</strong> 
        <span class="account-status <?= $user['banned'] ? 'account-banned' : 'account-active' ?>">
            <?= $user['banned'] ? 'Đã khóa' : 'Hoạt động' ?>
        </span>
    </p>
    <p><strong>Ngày tạo:</strong> <?= $createdAt ?></p>
    <hr>
    <a href="doimatkhau.php" class="btn btn-warning btn-sm w-100 mb-2">Đổi mật khẩu</a>
    <a href="logout.php" class="btn btn-danger btn-sm w-100">Đăng xuất</a>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Main Content -->
<div class="content">
    <div class="welcome-text">
        👋 Xin chào <strong><?= htmlspecialchars($username) ?></strong>, chúc bạn một ngày tuyệt vời tại Paww Soda!
    </div>

    <div class="banner-container">
        <img src="https://i.postimg.cc/ydJ5TMmx/main.png" alt="Banner" class="img-fluid rounded">
    </div>

<?php if (isset($buy_error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($buy_error) ?></div>
<?php endif; ?>
<h4 class="section-title">DANH SÁCH SẢN PHẨM</h4>

<?php if (isset($productQuery) && mysqli_num_rows($productQuery) > 0): ?>
    <div class="row">
        <?php while ($product = mysqli_fetch_assoc($productQuery)): ?>
            <?php 
            // Chuẩn bị dữ liệu sản phẩm an toàn
            $productData = [
                'id' => htmlspecialchars($product['id'] ?? '', ENT_QUOTES, 'UTF-8'),
                'name' => htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                'image' => htmlspecialchars($product['image'] ?? '', ENT_QUOTES, 'UTF-8'),
                'price' => floatval($product['price'] ?? 0),
                'quantity' => intval($product['quantity'] ?? 0),
                'category_name' => htmlspecialchars($product['category_name'] ?? '', ENT_QUOTES, 'UTF-8'),
                'type' => htmlspecialchars($product['type'] ?? '', ENT_QUOTES, 'UTF-8')
            ];
            
            // Mã hóa JSON và escape cho JavaScript
            $productJson = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            ?>
            
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="product-card">
                    <img src="<?= $productData['image'] ?>" 
                         alt="<?= $productData['name'] ?>" 
                         class="product-image"
                         onerror="this.src='https://via.placeholder.com/300'">
                    <h5 class="product-name"><?= $productData['name'] ?></h5>
                    <p class="product-description"><?= $productData['description'] ?></p>
                    
                    <div class="product-meta">
                        <span class="badge <?= $productData['quantity'] > 0 ? 'bg-success' : 'bg-danger' ?> stock-badge">
                            <?= $productData['quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                        </span>
                        <span class="product-price"><?= number_format($productData['price'], 0, ',', '.') ?>đ</span>
                    </div>
                    
                    <form method="post" class="product-actions mt-auto">
                        <input type="hidden" name="product_id" value="<?= $productData['id'] ?>">
                        <div class="input-group mb-3">
                            <button class="btn btn-outline-secondary minus-btn" type="button" <?= $productData['quantity'] <= 0 ? 'disabled' : '' ?>>-</button>
                            <input type="number" name="quantity" value="1" min="1" max="<?= $productData['quantity'] ?>" 
                                   class="form-control quantity-input text-center" required
                                   <?= $productData['quantity'] <= 0 ? 'disabled' : '' ?>>
                            <button class="btn btn-outline-secondary plus-btn" type="button" <?= $productData['quantity'] <= 0 ? 'disabled' : '' ?>>+</button>
                        </div>
                      <button type="button" class="btn btn-outline-info w-100 mb-2 detail-btn"
    onclick="showProductDetail(<?= htmlspecialchars($productJson, ENT_QUOTES, 'UTF-8') ?>)">
    <i class="bi bi-info-circle"></i> Chi tiết
</button>

<button type="button" class="btn btn-primary w-100 buy-btn" 
        onclick="confirmPurchase(<?= htmlspecialchars($productJson, ENT_QUOTES, 'UTF-8') ?>, this.form)"
        <?= $productData['quantity'] <= 0 ? 'disabled' : '' ?>>
    <i class="bi bi-cart-plus"></i> Mua ngay
</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if (isset($page) && $page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">&laquo;</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= (isset($page) && $i == $page ? 'active' : '') ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if (isset($page) && $page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info text-center">
        Hiện không có sản phẩm nào trong cửa hàng.
    </div>
<?php endif; ?>

<!-- Modal Chi tiết sản phẩm -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">THÔNG TIN CHI TIẾT SẢN PHẨM</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="productDetailContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Purchase Dialog -->
<div class="confirm-dialog" id="confirmDialog">
    <div class="confirm-box">
        <h5 class="confirm-title"><i class="bi bi-cart-check"></i> XÁC NHẬN MUA HÀNG</h5>
        <div id="confirmContent">
            <!-- Nội dung sẽ được thêm bằng JavaScript -->
        </div>
        <div class="confirm-buttons">
            <button type="button" class="btn btn-secondary confirm-btn" onclick="hideConfirm()">Hủy bỏ</button>
            <button type="button" class="btn btn-primary confirm-btn" id="confirmPurchaseBtn">Xác nhận</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Ẩn loader khi trang tải xong
    window.onload = () => {
        document.getElementById("pageLoader").style.display = "none";
        <?php if ($showLoginToast): ?>
            showToast("✅ Đăng nhập thành công!");
        <?php endif; ?>
    };

    // Toggle submenu
    function toggleSubmenu() {
        const menu = document.getElementById("subMenu");
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    }

    // Toggle account panel
    function toggleAccount() {
        const panel = document.getElementById("accountPanel");
        panel.style.display = panel.style.display === "block" ? "none" : "block";
        
        // Đóng panel khi click ra ngoài
        if (panel.style.display === "block") {
            document.addEventListener('click', function closePanel(e) {
                if (!panel.contains(e.target) && e.target.className !== 'bi bi-person-circle') {
                    panel.style.display = "none";
                    document.removeEventListener('click', closePanel);
                }
            });
        }
    }

    // Hiển thị toast thông báo
    function showToast(msg) {
        const container = document.getElementById("toastContainer");
        const toast = document.createElement("div");
        toast.className = "toast align-items-center text-white bg-dark border-0 show mb-2";
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Xử lý tăng/giảm số lượng
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.minus-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.nextElementSibling;
                if (input.value > 1) input.value--;
            });
        });

        document.querySelectorAll('.plus-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const max = parseInt(input.max);
                if (input.value < max) input.value++;
            });
        });
    });

   // Hiển thị modal chi tiết sản phẩm
function showProductDetail(product) {
    try {
        // Kiểm tra nếu product là string thì parse, không thì dùng trực tiếp
        if (typeof product === 'string') {
            product = JSON.parse(product);
        }

        if (!product) {
            throw new Error('Dữ liệu sản phẩm trống');
        }

        let html = `
        <div class="row">
            <div class="col-md-4">
                <div class="product-image-container text-center mb-3">
                    <img src="${product.image || 'https://via.placeholder.com/300'}" 
                         alt="${product.name || 'Product Image'}" 
                         class="img-fluid rounded"
                         style="max-height: 200px; width: auto; object-fit: contain;"
                         onerror="this.src='https://via.placeholder.com/300'">
                    ${(product.quantity || 0) > 0 ? 
                        '<div class="mt-2"><span class="badge bg-success">Còn hàng</span></div>' : 
                        '<div class="mt-2"><span class="badge bg-danger">Hết hàng</span></div>'}
                </div>
            </div>
            <div class="col-md-8">
                <h3 class="mb-3">${product.name || 'Không có tên'}</h3>
                
                <div class="d-flex align-items-center mb-3">
                    <span class="price" style="font-size: 1.5rem; color: #00e1b3; font-weight: bold;">
                        ${formatMoney(product.price || 0)}
                    </span>
                    <span class="ms-3 text-muted">Số lượng: ${product.quantity || 0}</span>
                </div>
                
                <div class="product-specs mb-4">
                    <h5><i class="bi bi-info-circle"></i> Thông tin cơ bản</h5>
                    <div class="row mt-2">
                        ${product.category_name ? `
                        <div class="col-6 mb-2">
                            <span class="text-muted">Danh mục:</span>
                            <span class="ms-2">${product.category_name}</span>
                        </div>` : ''}
                        
                        ${product.type ? `
                        <div class="col-6 mb-2">
                            <span class="text-muted">Loại:</span>
                            <span class="ms-2">${product.type === 'acc' ? 'Tài khoản' : 
                              product.type === 'key' ? 'Key' : 'Vật phẩm'}</span>
                        </div>` : ''}
                    </div>
                </div>
                
                <div class="product-description">
                    <h5><i class="bi bi-align-left"></i> Mô tả sản phẩm</h5>
                    <div class="mt-2 p-3 bg-dark rounded" style="max-height: 150px; overflow-y: auto;">
                        ${product.description || 'Chưa có mô tả chi tiết'}
                    </div>
                </div>
            </div>
        </div>`;

        const content = document.getElementById("productDetailContent");
        if (content) {
            content.innerHTML = html;
            
            const modalElement = document.getElementById("productDetailModal");
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
        
    } catch (error) {
        console.error('Error showing product detail:', error);
        showToast('❌ Có lỗi khi tải thông tin sản phẩm');
    }
}

// Xác nhận mua hàng
let currentForm = null;

function confirmPurchase(product, form) {
    try {
        // Kiểm tra form tồn tại
        if (!form || !(form instanceof HTMLFormElement)) {
            throw new Error('Form không hợp lệ');
        }

        // Kiểm tra dữ liệu sản phẩm
        if (typeof product === 'string') {
            product = JSON.parse(product);
        }

        if (!product || typeof product !== 'object') {
            throw new Error('Thông tin sản phẩm không đúng định dạng');
        }

        // Lấy số lượng
        const quantityInput = form.querySelector('input[name="quantity"]');
        if (!quantityInput) {
            throw new Error('Không tìm thấy trường số lượng');
        }

        const quantity = parseInt(quantityInput.value);
        if (isNaN(quantity) || quantity <= 0) {
            throw new Error('Số lượng phải là số lớn hơn 0');
        }

        const price = parseFloat(product.price) || 0;
        if (price <= 0) {
            throw new Error('Giá sản phẩm không hợp lệ');
        }

        const availableQuantity = parseInt(product.quantity) || 0;
        if (quantity > availableQuantity) {
            throw new Error(`Chỉ còn ${availableQuantity} sản phẩm trong kho`);
        }

        const totalPrice = price * quantity;

        // Tạo nội dung xác nhận
        const html = `
            <p>Bạn có chắc chắn muốn mua sản phẩm này?</p>
            <div class="alert alert-info">
                <p><strong>Sản phẩm:</strong> ${escapeHtml(product.name || 'Không có tên')}</p>
                <p><strong>Số lượng:</strong> ${quantity}</p>
                <p><strong>Đơn giá:</strong> ${formatMoney(price)}</p>
                <p><strong>Thành tiền:</strong> ${formatMoney(totalPrice)}</p>
            </div>
            <p class="text-warning">Số dư của bạn sẽ bị trừ ${formatMoney(totalPrice)} sau khi xác nhận.</p>
        `;

        // Hiển thị dialog xác nhận
        const confirmContent = document.getElementById("confirmContent");
        const confirmDialog = document.getElementById("confirmDialog");
        
        if (!confirmContent || !confirmDialog) {
            throw new Error('Không tìm thấy phần tử xác nhận');
        }

        confirmContent.innerHTML = html;
        confirmDialog.classList.add("active");
        
        // Xử lý sự kiện xác nhận
        currentForm = form;
        const confirmBtn = document.getElementById("confirmPurchaseBtn");
        
        if (confirmBtn) {
            confirmBtn.onclick = function() {
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
                this.disabled = true;
                
                if (currentForm) {
                    currentForm.submit();
                }
            };
        }
        
    } catch (error) {
        console.error('Lỗi xác nhận mua hàng:', error);
        showToast(`❌ ${error.message || 'Có lỗi khi xác nhận mua hàng'}`);
        
        const confirmBtn = document.getElementById("confirmPurchaseBtn");
        if (confirmBtn) {
            confirmBtn.innerHTML = 'Xác nhận';
            confirmBtn.disabled = false;
        }
    }
}

function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}



function hideConfirm() {
    const confirmDialog = document.getElementById("confirmDialog");
    if (confirmDialog) {
        confirmDialog.classList.remove("active");
    }
    
    if (currentForm) {
        const btn = document.getElementById("confirmPurchaseBtn");
        if (btn) {
            btn.innerHTML = 'Xác nhận';
            btn.disabled = false;
        }
    }
    currentForm = null;
}

// Định dạng tiền tệ
function formatMoney(amount) {
    amount = parseFloat(amount) || 0;
    return new Intl.NumberFormat('vi-VN', { 
        style: 'currency', 
        currency: 'VND',
        minimumFractionDigits: 0
    }).format(amount);
}

// Responsive sidebar cho mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}
</script>
</body>
</html>