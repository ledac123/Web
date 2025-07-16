<?php
$conn = mysqli_connect("sql109.infinityfree.com", "if0_39467719", "SewwDzVcl", "if0_39467719_if0_39467719");
mysqli_set_charset($conn, "utf8");
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
?>
