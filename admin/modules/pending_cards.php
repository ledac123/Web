<h2>💳 Duyệt Thẻ Chờ</h2>
<?php
$result = mysqli_query($conn, "SELECT * FROM card_recharges WHERE status = 'pending' ORDER BY created_at DESC");
?>

<table class="table table-dark table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Người nạp</th>
            <th>Loại thẻ</th>
            <th>Mệnh giá</th>
            <th>Mã thẻ</th>
            <th>Seri</th>
            <th>Thời gian</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= strtoupper($row['type']) ?></td>
            <td><?= number_format($row['amount']) ?>đ</td>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['serial']) ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
                <a href="?module=approve_card&id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Duyệt thẻ này?')">Duyệt</a>
                <a href="?module=reject_card&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Từ chối thẻ này?')">Từ chối</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
