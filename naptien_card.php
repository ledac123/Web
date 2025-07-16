<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$err = "";
$success = "";

if (isset($_POST['submit'])) {
    $telco = trim($_POST['telco']);
    $amount = trim($_POST['amount']);
    $serial = trim($_POST['serial']);
    $code = trim($_POST['code']);

    if (empty($telco) || empty($amount) || empty($serial) || empty($code)) {
        $err = "Vui lòng điền đầy đủ thông tin.";
    } else {
        $request_id = rand(100000000, 999999999);
        $partner_id = '60933716558';
        $partner_key = '268989628e771f4a5424c86c40dbb8b5';
        $url = 'https://thesieure.com/chargingws/v2';
        $command = 'charging';

        $dataPost = [
            'request_id' => $request_id,
            'code' => $code,
            'partner_id' => $partner_id,
            'serial' => $serial,
            'telco' => $telco,
            'command' => $command
        ];

        ksort($dataPost);
        $sign = $partner_key;
        foreach ($dataPost as $item) {
            $sign .= $item;
        }

        $mysign = md5($sign);
        $dataPost['amount'] = $amount;
        $dataPost['sign'] = $mysign;

        $data = http_build_query($dataPost);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($result);

        if (isset($obj->status) && $obj->status == 99) {
            $success = "Thẻ đã gửi đi, đang chờ xử lý...";
            $stmt = $conn->prepare("INSERT INTO card_history (username, telco, serial, code, amount, thucnhan, status, request_id, created_at) VALUES (?, ?, ?, ?, ?, 0, 'pending', ?, NOW())");
            $stmt->bind_param("ssssss", $username, $telco, $serial, $code, $amount, $request_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $err = $obj->message ?? "Đã xảy ra lỗi khi gửi thẻ.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nạp Thẻ | LegitVN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0e1a2b;
            font-family: 'Montserrat', sans-serif;
            color: #fff;
        }
        .card-dark {
            background-color: #1c2d49;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .form-control, .custom-select {
            background-color: #243552;
            border: 1px solid #334d6e;
            color: #fff;
        }
        .form-control::placeholder {
            color: #aaa;
        }
        .btn-primary {
            background-color: #4e9cff;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #3b7fd8;
        }
        .table-dark {
            background-color: #1c2d49;
        }
        .table-dark th, .table-dark td {
            border-color: #2e4568;
            vertical-align: middle;
        }
        .status-pending { color: #ffc107; font-weight: 600; }
        .status-success { color: #00ff99; font-weight: 600; }
        .status-fail { color: red; font-weight: 600; }
        .alert { font-weight: 600; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card-dark">
        <h4 class="mb-3">Nạp Thẻ Cào</h4>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Loại Thẻ</label>
                    <select class="custom-select" name="telco" required>
                        <option value="">-- Chọn loại thẻ --</option>
                        <option value="VIETTEL">Viettel</option>
                        <option value="MOBIFONE">Mobifone</option>
                        <option value="VINAPHONE">Vinaphone</option>
                        <option value="ZING">Zing</option>
                        <option value="GATE">Gate</option>
                        <option value="VNMOBI">Vietnamobile</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Mệnh Giá</label>
                    <select class="custom-select" name="amount" required>
                        <option value="">-- Chọn mệnh giá --</option>
                        <option value="10000">10.000 VNĐ</option>
                        <option value="20000">20.000 VNĐ</option>
                        <option value="50000">50.000 VNĐ</option>
                        <option value="100000">100.000 VNĐ</option>
                        <option value="200000">200.000 VNĐ</option>
                        <option value="500000">500.000 VNĐ</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Seri</label>
                <input type="text" class="form-control" name="serial" placeholder="Nhập seri của thẻ" required>
            </div>
            <div class="form-group">
                <label>Mã Thẻ</label>
                <input type="text" class="form-control" name="code" placeholder="Nhập mã thẻ" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" name="submit">Nạp Ngay</button>
            
            <a href="index.php" class="btn btn-light mb-3" style="border-radius: 8px; font-weight: 600;">
    ← Quay lại Trang Chủ
</a>

        </form>
    </div>

    <!-- Thay thế phần dưới card lịch sử bằng bảng chiết khấu -->
<div class="card-dark">
    <h4 class="mb-3">📉 Bảng Chiết Khấu Thẻ Cào</h4>
    
    <div class="table-responsive mb-4">
        <h5>VIETTEL</h5>
        <table class="table table-dark text-center">
            <thead>
                <tr>
                    <th>10,000đ</th>
                    <th>20,000đ</th>
                    <th>30,000đ</th>
                    <th>50,000đ</th>
                    <th>100,000đ</th>
                    <th>200,000đ</th>
                    <th>300,000đ</th>
                    <th>500,000đ</th>
                    <th>1,000,000đ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10.5%</td>
                    <td>15.5%</td>
                    <td>15.5%</td>
                    <td>14.2%</td>
                    <td>14.2%</td>
                    <td>16.8%</td>
                    <td>17%</td>
                    <td>18%</td>
                    <td>18%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-responsive mb-4">
        <h5>MOBIFONE</h5>
        <table class="table table-dark text-center">
            <thead>
                <tr>
                    <th>10,000đ</th>
                    <th>20,000đ</th>
                    <th>30,000đ</th>
                    <th>50,000đ</th>
                    <th>100,000đ</th>
                    <th>200,000đ</th>
                    <th>300,000đ</th>
                    <th>500,000đ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>16.5%</td>
                    <td>16.5%</td>
                    <td>16.5%</td>
                    <td>16.3%</td>
                    <td>15.5%</td>
                    <td>18%</td>
                    <td>18%</td>
                    <td>17%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-responsive">
        <h5>VINAPHONE</h5>
        <table class="table table-dark text-center">
            <thead>
                <tr>
                    <th>10,000đ</th>
                    <th>20,000đ</th>
                    <th>30,000đ</th>
                    <th>50,000đ</th>
                    <th>100,000đ</th>
                    <th>200,000đ</th>
                    <th>300,000đ</th>
                    <th>500,000đ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>17.3%</td>
                    <td>17.3%</td>
                    <td>17.3%</td>
                    <td>16.5%</td>
                    <td>14.4%</td>
                    <td>17.6%</td>
                    <td>17.6%</td>
                    <td>18.3%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>
</body>
</html>
