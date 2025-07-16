<?php
$conn = mysqli_connect("sql109.infinityfree.com", "if0_39467719", "SewwDzVcl", "if0_39467719_if0_39467719");
mysqli_set_charset($conn, "utf8");
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['callback_sign'])) {
    // Ghi log để debug
    file_put_contents("log.txt", json_encode($_POST) . "\n", FILE_APPEND);

    // ⚠️ Đổi lại đúng Partner Key thật ở đây
    $partner_key = '268989628e771f4a5424c86c40dbb8b5'; // <-- NHỚ sửa lại đúng key của bạn!
    $callback_sign = md5($partner_key . $_POST['code'] . $_POST['serial']);

    if ($_POST['callback_sign'] === $callback_sign) {
        $status = $_POST['status'];
        $message = $_POST['message'];
        $request_id = $_POST['request_id'];
        $value = $_POST['value'];
        $amount = $_POST['amount'];

        $trangthai = 'pending';
        if ($status == 1) $trangthai = 'success';
        elseif ($status == 2 || $status == 3) $trangthai = 'fail';

        // Cập nhật lịch sử
        $stmt = $conn->prepare("UPDATE card_history SET status = ?, thucnhan = ? WHERE request_id = ?");
        $stmt->bind_param("sis", $trangthai, $amount, $request_id);
        $stmt->execute();
        $stmt->close();

        // Nếu thành công => cộng tiền
        if ($status == 1) {
            $res = $conn->query("SELECT username FROM card_history WHERE request_id = '$request_id' LIMIT 1");
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $user = $row['username'];
                $conn->query("UPDATE users SET balance = balance + $amount, total_nap = total_nap + $amount WHERE username = '$user'");
            }
        }

        echo "OK";
    } else {
        echo "Sai chữ ký";
    }
}
?>
