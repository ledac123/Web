<h2>📁 Quản lý danh mục</h2>
<?php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$start = ($page - 1) * $limit;

$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC LIMIT $start, $limit");
$total = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM categories"))[0];
$totalPages = ceil($total / $limit);
?>

<a href="?module=add_category" class="btn btn-success mb-3">➕ Thêm danh mục</a>

<table class="table table-dark table-hover table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>
                <a href="?module=edit_category&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                <a onclick="return confirm('Xác nhận xóa danh mục này?')" href="?module=delete_category&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Xóa</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<nav>
    <ul class="pagination">
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?module=categories&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
