<?php
// Bật báo lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/db.php";

$username = $_SESSION['username'];
$stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$userData = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!in_array($userData['role'], ['admin','mod'])) {
    die("⛔ Bạn không có quyền truy cập!");
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==== XỬ LÝ FORM ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    // Xử lý từng chức năng
    $action_success = false;
    
    try {
        if (isset($_POST['add_category'])) {
            $name = mysqli_real_escape_string($conn, $_POST['cat_name']);
            if (empty($name)) {
                throw new Exception("Tên danh mục không được để trống");
            }
            mysqli_query($conn, "INSERT INTO categories (name) VALUES ('$name')");
            $_SESSION['message'] = "✅ Đã thêm danh mục thành công!";
            $action_success = true;
        }

        if (isset($_POST['edit_category'])) {
            $id = (int)$_POST['cat_id'];
            $name = mysqli_real_escape_string($conn, $_POST['cat_name']);
            if (empty($name)) {
                throw new Exception("Tên danh mục không được để trống");
            }
            mysqli_query($conn, "UPDATE categories SET name='$name' WHERE id=$id");
            $_SESSION['message'] = "🔄 Đã cập nhật danh mục thành công!";
            $action_success = true;
        }

        if (isset($_POST['delete_category'])) {
            $id = (int)$_POST['cat_id'];
            // Kiểm tra xem danh mục có sản phẩm không
            $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id=$id"));
            if ($check['count'] > 0) {
                throw new Exception("Không thể xóa danh mục đang có sản phẩm");
            }
            mysqli_query($conn, "DELETE FROM categories WHERE id=$id");
            $_SESSION['message'] = "❌ Đã xóa danh mục thành công!";
            $action_success = true;
        }

        if (isset($_POST['add_product'])) {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $price = (int)$_POST['price'];
            $quantity = (int)$_POST['quantity'];
            $type = mysqli_real_escape_string($conn, $_POST['type']);
            $category_id = (int)$_POST['category_id'];
            $image = mysqli_real_escape_string($conn, $_POST['image']);
            $info = mysqli_real_escape_string($conn, $_POST['info']);
            $secret = mysqli_real_escape_string($conn, $_POST['secret']);
            
            // Validate dữ liệu
            if (empty($name) || $price <= 0 || $quantity < 0 || empty($secret)) {
                throw new Exception("Dữ liệu sản phẩm không hợp lệ");
            }
            
            mysqli_query($conn, "INSERT INTO products (name, price, quantity, type, category_id, image, info, secret) 
                                VALUES ('$name', $price, $quantity, '$type', $category_id, '$image', '$info', '$secret')");
            $_SESSION['message'] = "✅ Đã thêm sản phẩm thành công!";
            $action_success = true;
        }

        if (isset($_POST['edit_product'])) {
            $id = (int)$_POST['edit_product_id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $price = (int)$_POST['price'];
            $quantity = (int)$_POST['quantity'];
            $type = mysqli_real_escape_string($conn, $_POST['type']);
            $category_id = (int)$_POST['category_id'];
            $image = mysqli_real_escape_string($conn, $_POST['image']);
            $info = mysqli_real_escape_string($conn, $_POST['info']);
            $secret = mysqli_real_escape_string($conn, $_POST['secret']);
            
            // Validate dữ liệu
            if (empty($name) || $price <= 0 || $quantity < 0 || empty($secret)) {
                throw new Exception("Dữ liệu sản phẩm không hợp lệ");
            }
            
            mysqli_query($conn, "UPDATE products SET 
                                name='$name', 
                                price=$price, 
                                quantity=$quantity, 
                                type='$type', 
                                category_id=$category_id, 
                                image='$image', 
                                info='$info', 
                                secret='$secret' 
                                WHERE id=$id");
            $_SESSION['message'] = "🔄 Đã cập nhật sản phẩm thành công!";
            $action_success = true;
        }

        if (isset($_POST['delete_product'])) {
            $id = (int)$_POST['delete_product_id'];
            mysqli_query($conn, "DELETE FROM products WHERE id=$id");
            $_SESSION['message'] = "❌ Đã xóa sản phẩm thành công!";
            $action_success = true;
        }

        if (isset($_POST['add_money'])) {
            $user = mysqli_real_escape_string($conn, $_POST['username']);
            $amount = (int)$_POST['amount'];
            
            if ($amount <= 0) {
                throw new Exception("Số tiền phải lớn hơn 0");
            }
            
            mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE username='$user'");
            
            // Ghi log lịch sử
            $timeNow = date("Y-m-d H:i:s");
            $desc = "Admin cộng tiền: +".number_format($amount)."đ";
            mysqli_query($conn, "INSERT INTO account_history (username, type, amount, description, time) 
                               VALUES ('$user', '+', $amount, '$desc', '$timeNow')");
            
            $_SESSION['message'] = "✅ Đã thêm ".number_format($amount)."đ vào tài khoản $user!";
            $action_success = true;
        }

        if (isset($_POST['remove_money'])) {
            $user = mysqli_real_escape_string($conn, $_POST['username']);
            $amount = (int)$_POST['amount'];
            
            if ($amount <= 0) {
                throw new Exception("Số tiền phải lớn hơn 0");
            }
            
            // Kiểm tra số dư hiện tại
            $current_balance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE username='$user'"))['balance'];
            if ($current_balance < $amount) {
                throw new Exception("Số dư tài khoản không đủ để trừ");
            }
            
            mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE username='$user'");
            
            // Ghi log lịch sử
            $timeNow = date("Y-m-d H:i:s");
            $desc = "Admin trừ tiền: -".number_format($amount)."đ";
            mysqli_query($conn, "INSERT INTO account_history (username, type, amount, description, time) 
                               VALUES ('$user', '-', $amount, '$desc', '$timeNow')");
            
            $_SESSION['message'] = "❌ Đã trừ ".number_format($amount)."đ từ tài khoản $user!";
            $action_success = true;
        }

        if (isset($_POST['change_pass'])) {
            $user = mysqli_real_escape_string($conn, $_POST['username']);
            $newpass = $_POST['newpass'];
            
            if (strlen($newpass) < 6) {
                throw new Exception("Mật khẩu phải có ít nhất 6 ký tự");
            }
            
            $newpass = md5($newpass);
            mysqli_query($conn, "UPDATE users SET password = '$newpass' WHERE username='$user'");
            $_SESSION['message'] = "🔑 Đã đổi mật khẩu cho $user!";
            $action_success = true;
        }

        if (isset($_POST['lock_user'])) {
            $user = mysqli_real_escape_string($conn, $_POST['username']);
            $reason = mysqli_real_escape_string($conn, $_POST['reason']);
            
            if (empty($reason)) {
                throw new Exception("Vui lòng nhập lý do khóa tài khoản");
            }
            
            mysqli_query($conn, "UPDATE users SET banned = 1, reason = '$reason' WHERE username='$user'");
            $_SESSION['message'] = "🔒 Đã khóa tài khoản $user!";
            $action_success = true;
        }

        if (isset($_POST['unlock_user'])) {
            $user = mysqli_real_escape_string($conn, $_POST['username']);
            mysqli_query($conn, "UPDATE users SET banned = 0, reason = '' WHERE username='$user'");
            $_SESSION['message'] = "🔓 Đã mở khóa tài khoản $user!";
            $action_success = true;
        }

        if (isset($_POST['duyet_napthe'])) {
            $id = (int)$_POST['id'];
            $data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM napthe WHERE id=$id AND status='pending'"));
            
            if (!$data) {
                throw new Exception("Thẻ nạp không tồn tại hoặc đã được xử lý");
            }
            
            $user = $data['username'];
            $amount = $data['amount'];
            
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "UPDATE napthe SET status='success' WHERE id=$id");
                mysqli_query($conn, "UPDATE users SET balance = balance + $amount, total_deposited = total_deposited + $amount WHERE username='$user'");
                
                // Ghi log lịch sử
                $timeNow = date("Y-m-d H:i:s");
                $desc = "Nạp thẻ ".$data['telco'].": +".number_format($amount)."đ";
                mysqli_query($conn, "INSERT INTO account_history (username, type, amount, description, time) 
                                   VALUES ('$user', '+', $amount, '$desc', '$timeNow')");
                
                mysqli_commit($conn);
                $_SESSION['message'] = "✅ Đã duyệt thẻ nạp ".number_format($amount)."đ cho $user!";
                $action_success = true;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                throw new Exception("Lỗi khi duyệt thẻ: " . $e->getMessage());
            }
        }

        if (isset($_POST['huy_napthe'])) {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "UPDATE napthe SET status='error' WHERE id=$id");
            $_SESSION['message'] = "❌ Đã hủy thẻ nạp #$id!";
            $action_success = true;
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    // Chuyển hướng để tránh submit form nhiều lần
    if ($action_success) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ==== LẤY DỮ LIỆU HIỂN THỊ ====
// Thống kê
$stats = [
    'totalUsers' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'],
    'totalNap' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM napthe WHERE status='success'"))['total'] ?? 0,
    'totalOrders' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders"))['total'],
    'pendingCards' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM napthe WHERE status='pending'"))['total'],
    'totalRevenue' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(price) AS total FROM orders"))['total'] ?? 0
];

// Danh sách
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
$products = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$cards = mysqli_query($conn, "SELECT * FROM napthe WHERE status='pending' ORDER BY id DESC");
$history = mysqli_query($conn, "SELECT * FROM account_history ORDER BY id DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Paww Soda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #0c1532;
            --secondary-color: #142253;
            --accent-color: #00e1b3;
            --text-color: #ffffff;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        body {
            background-color: var(--primary-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: var(--secondary-color);
            width: var(--sidebar-width);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 15px 20px;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand h4 {
            color: var(--accent-color);
            font-weight: 700;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: var(--text-color);
            background-color: rgba(0,225,179,0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.users { background: linear-gradient(135deg, #4e73df, #224abe); }
        .stat-card.deposit { background: linear-gradient(135deg, #1cc88a, #13855c); }
        .stat-card.orders { background: linear-gradient(135deg, #36b9cc, #258391); }
        .stat-card.pending { background: linear-gradient(135deg, #f6c23e, #dda20a); }
        .stat-card.revenue { background: linear-gradient(135deg, #e74a3b, #be2617); }
        
        /* Tables */
        .custom-table {
            background-color: var(--secondary-color);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .custom-table th {
            background-color: rgba(0,225,179,0.1);
            color: var(--accent-color);
            padding: 15px;
        }
        
        .custom-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        /* Forms */
        .form-control, .form-select {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(0,225,179,0.25);
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #00c9a0;
            border-color: #00c9a0;
        }
        
        /* Modal */
        .modal-content {
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        /* Badges */
        .badge-admin {
            background-color: #6f42c1;
        }
        
        .badge-mod {
            background-color: #fd7e14;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--secondary-color);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-speedometer2"></i> ADMIN PANEL</h4>
            <small class="text-muted">Xin chào, <?= htmlspecialchars($username) ?></small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#categories">
                    <i class="bi bi-tags"></i> Danh mục
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#products">
                    <i class="bi bi-box-seam"></i> Sản phẩm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#users">
                    <i class="bi bi-people"></i> Người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#transactions">
                    <i class="bi bi-credit-card"></i> Thẻ nạp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#history">
                    <i class="bi bi-clock-history"></i> Lịch sử GD
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>
        
        <!-- Thống kê -->
        <div class="row mb-4" id="dashboard">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card users">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Người dùng</h5>
                            <h2><?= $stats['totalUsers'] ?></h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card deposit">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Tổng nạp</h5>
                            <h2><?= number_format($stats['totalNap']) ?>đ</h2>
                        </div>
                        <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card orders">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Đơn hàng</h5>
                            <h2><?= $stats['totalOrders'] ?></h2>
                        </div>
                        <i class="bi bi-cart-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card revenue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Doanh thu</h5>
                            <h2><?= number_format($stats['totalRevenue']) ?>đ</h2>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quản lý danh mục -->
        <div class="card mb-4 border-0 shadow" id="categories">
            <div class="card-header bg-transparent border-0">
                <h4 class="mb-0"><i class="bi bi-tags me-2"></i> Quản lý danh mục</h4>
            </div>
            <div class="card-body">
                <form method="post" class="input-group mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input name="cat_name" class="form-control" placeholder="Tên danh mục" required>
                    <button name="add_category" class="btn btn-primary">Thêm</button>
                </form>
                
                <div class="table-responsive custom-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($c = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td>
                                    <form method="post" class="d-flex">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                        <input name="cat_name" class="form-control form-control-sm" value="<?= htmlspecialchars($c['name']) ?>" required>
                                </td>
                                <td class="text-nowrap">
                                        <button name="edit_category" class="btn btn-sm btn-warning me-1">Sửa</button>
                                        <button name="delete_category" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Xóa danh mục này?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quản lý sản phẩm -->
        <div class="card mb-4 border-0 shadow" id="products">
            <div class="card-header bg-transparent border-0">
                <h4 class="mb-0"><i class="bi bi-box-seam me-2"></i> Quản lý sản phẩm</h4>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3 mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="col-md-4">
                        <input name="name" class="form-control" placeholder="Tên sản phẩm" required>
                    </div>
                    <div class="col-md-2">
                        <input name="price" type="number" class="form-control" placeholder="Giá" min="1000" required>
                    </div>
                    <div class="col-md-2">
                        <input name="quantity" type="number" class="form-control" placeholder="Số lượng" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-select" required>
                            <option value="acc">Tài khoản</option>
                            <option value="key">Key</option>
                            <option value="item">Vật phẩm</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="category_id" class="form-select" required>
                            <?php mysqli_data_seek($categories, 0); while($c = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <input name="image" class="form-control" placeholder="Link ảnh (URL)">
                    </div>
                    <div class="col-md-12">
                        <textarea name="info" class="form-control" placeholder="Mô tả sản phẩm" rows="3"></textarea>
                    </div>
                    <div class="col-md-12">
                        <textarea name="secret" class="form-control" placeholder="Thông tin sau khi mua (mỗi dòng 1 sản phẩm)" rows="5" required></textarea>
                    </div>
                    <div class="col-md-12">
                        <button name="add_product" class="btn btn-success">Thêm sản phẩm</button>
                    </div>
                </form>

                <hr class="my-4 bg-secondary">

                <div class="table-responsive custom-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên</th>
                                <th>Giá</th>
                                <th>SL</th>
                                <th>Loại</th>
                                <th>Danh mục</th>
                                <th>Ảnh</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= number_format($p['price']) ?>đ</td>
                                <td><?= $p['quantity'] ?></td>
                                <td>
                                    <?php 
                                        $typeBadge = [
                                            'acc' => 'primary',
                                            'key' => 'warning',
                                            'item' => 'success'
                                        ];
                                        $badgeClass = $typeBadge[$p['type']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= strtoupper($p['type']) ?></span>
                                </td>
                                <td><?= $p['category_name'] ?? 'N/A' ?></td>
                                <td>
                                    <?php if ($p['image']): ?>
                                    <img src="<?= htmlspecialchars($p['image']) ?>" width="50" class="img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal" 
                                        onclick="loadProductData(<?= htmlspecialchars(json_encode($p)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Xóa sản phẩm này?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="delete_product_id" value="<?= $p['id'] ?>">
                                        <button name="delete_product" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quản lý người dùng -->
        <div class="card mb-4 border-0 shadow" id="users">
            <div class="card-header bg-transparent border-0">
                <h4 class="mb-0"><i class="bi bi-people me-2"></i> Quản lý người dùng</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive custom-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Vai trò</th>
                                <th>Số dư</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-admin">Admin</span>
                                    <?php elseif ($u['role'] === 'mod'): ?>
                                        <span class="badge badge-mod">Mod</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($u['balance']) ?>đ</td>
                                <td>
                                    <?php if ($u['banned']): ?>
                                        <span class="badge bg-danger">Đã khóa</span>
                                        <?php if ($u['reason']): ?>
                                            <br><small><?= htmlspecialchars($u['reason']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="row g-2">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="username" value="<?= $u['username'] ?>">
                                        
                                        <div class="col-12">
                                            <div class="input-group input-group-sm">
                                                <input name="amount" type="number" class="form-control" placeholder="Số tiền" min="1000">
                                                <button name="add_money" class="btn btn-success">+₫</button>
                                                <button name="remove_money" class="btn btn-warning">-₫</button>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="input-group input-group-sm">
                                                <input name="newpass" class="form-control" placeholder="Mật khẩu mới" minlength="6">
                                                <button name="change_pass" class="btn btn-info">Đổi MK</button>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <?php if ($u['banned']): ?>
                                                <button name="unlock_user" class="btn btn-primary w-100">Mở khóa</button>
                                            <?php else: ?>
                                                <div class="input-group input-group-sm">
                                                    <input name="reason" class="form-control" placeholder="Lý do khóa" required>
                                                    <button name="lock_user" class="btn btn-danger">Khóa</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Duyệt thẻ nạp -->
        <div class="card mb-4 border-0 shadow" id="transactions">
            <div class="card-header bg-transparent border-0">
                <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i> Duyệt thẻ nạp</h4>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($cards) == 0): ?>
                    <div class="alert alert-info">Không có thẻ nào đang chờ duyệt</div>
                <?php else: ?>
                <div class="table-responsive custom-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Nhà mạng</th>
                                <th>Mệnh giá</th>
                                <th>Serial</th>
                                <th>Mã thẻ</th>
                                <th>Thời gian</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($c = mysqli_fetch_assoc($cards)): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><?= $c['username'] ?></td>
                                <td><?= $c['telco'] ?></td>
                                <td><?= number_format($c['amount']) ?>đ</td>
                                <td><?= $c['serial'] ?></td>
                                <td><?= $c['pin'] ?></td>
                                <td><?= $c['created_at'] ?></td>
                                <td class="text-nowrap">
                                    <form method="post" class="d-flex gap-1">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button name="duyet_napthe" class="btn btn-sm btn-success">Duyệt</button>
                                        <button name="huy_napthe" class="btn btn-sm btn-danger">Hủy</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lịch sử giao dịch -->
        <div class="card mb-4 border-0 shadow" id="history">
            <div class="card-header bg-transparent border-0">
                <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i> Lịch sử giao dịch gần đây</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive custom-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Loại</th>
                                <th>Số tiền</th>
                                <th>Mô tả</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($h = mysqli_fetch_assoc($history)): ?>
                            <tr>
                                <td><?= $h['id'] ?></td>
                                <td><?= $h['username'] ?></td>
                                <td>
                                    <?php if ($h['type'] === '+'): ?>
                                        <span class="badge bg-success">Nạp tiền</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Trừ tiền</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($h['amount']) ?>đ</td>
                                <td><?= htmlspecialchars($h['description']) ?></td>
                                <td><?= $h['time'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sửa sản phẩm -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #142253; border: 1px solid #00e1b3;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> SỬA SẢN PHẨM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="edit_product_id" id="editProductId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên sản phẩm</label>
                                <input name="name" id="editProductName" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Giá</label>
                                <input name="price" type="number" id="editProductPrice" class="form-control" min="1000" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Số lượng</label>
                                <input name="quantity" type="number" id="editProductQuantity" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Loại</label>
                                <select name="type" id="editProductType" class="form-select" required>
                                    <option value="acc">Tài khoản</option>
                                    <option value="key">Key</option>
                                    <option value="item">Vật phẩm</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Danh mục</label>
                                <select name="category_id" id="editProductCategory" class="form-select" required>
                                    <?php mysqli_data_seek($categories, 0); while($c = mysqli_fetch_assoc($categories)): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Link ảnh</label>
                                <input name="image" id="editProductImage" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mô tả sản phẩm</label>
                                <textarea name="info" id="editProductInfo" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Thông tin sau khi mua (mỗi dòng 1 sản phẩm)</label>
                                <textarea name="secret" id="editProductSecret" class="form-control" rows="5" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button name="edit_product" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load dữ liệu sản phẩm vào modal chỉnh sửa
        function loadProductData(product) {
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editProductName').value = product.name;
            document.getElementById('editProductPrice').value = product.price;
            document.getElementById('editProductQuantity').value = product.quantity;
            document.getElementById('editProductType').value = product.type;
            document.getElementById('editProductCategory').value = product.category_id;
            document.getElementById('editProductImage').value = product.image || '';
            document.getElementById('editProductInfo').value = product.info || '';
            document.getElementById('editProductSecret').value = product.secret || '';
        }

        // Hiển thị confirm khi thao tác quan trọng
        function confirmAction(message) {
            return confirm(message);
        }
    </script>
</body>
</html>