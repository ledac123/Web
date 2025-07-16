<h2>📦 Quản lý sản phẩm</h2>
<?php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$start = ($page - 1) * $limit;

$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC LIMIT $start, $limit");
$total = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0];
$totalPages = ceil($total / $limit);
?>

<a href="?module=add_product" class="btn btn-success mb-3">➕ Thêm sản phẩm</a>

<table class="table table-dark table-hover table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thưởng</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= number_format($row['price']) ?>đ</td>
            <td><?= $row['quantity'] ?></td>
            <td><?= number_format($row['bonus']) ?>đ</td>
            <td>
                <a href="?module=edit_product&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                <a onclick="return confirm('Xác nhận xóa?')" href="?module=delete_product&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Xóa</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<nav>
    <ul class="pagination">
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?module=products&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
