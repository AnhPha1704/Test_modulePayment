<?php
require_once 'db.php';

$app_trans_id = $_GET['apptransid'] ?? '';
$zp_trans_id = $_GET['zptransid'] ?? '';

$status = 'Unknown';
$message = '';
$order_details = [];

if ($app_trans_id) {
    try {
        $db = Database::getConnection();

        // Get payment and order details
        $stmt = $db->prepare("
            SELECT p.*,
                   o.TotalAmount, o.OrderDescription, o.Status AS OrderStatus, o.OrderDate,
                   c.CustomerName, c.Email
            FROM Payments p
            JOIN Orders o ON p.OrderID = o.OrderID
            JOIN Customers c ON o.CustomerID = c.CustomerID
            WHERE p.TransactionCode = ?
        ");
        $stmt->execute([$app_trans_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            $order_details = $payment;
            if ($payment['IsSuccessful'] == 1) {
                $status = 'Thành công';
                $message = 'Thanh toán đã được xử lý thành công.';
            } elseif ($payment['ReturnCode'] == 2) {
                $status = 'Thất bại';
                $message = 'Thanh toán thất bại.';
            } else {
                $status = 'Đang chờ';
                $message = 'Thanh toán đang được xử lý. Vui lòng kiểm tra lại sau.';
            }
        } else {
            $status = 'Không tìm thấy';
            $message = 'Không tìm thấy đơn hàng này.';
        }
    } catch (PDOException $e) {
        $status = 'Lỗi';
        $message = 'Có lỗi xảy ra khi kiểm tra đơn hàng.';
        error_log("Redirect error: " . $e->getMessage());
    }
} else {
    $status = 'Thiếu thông tin';
    $message = 'Thiếu thông tin đơn hàng.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="redirect.css">
</head>
<body>
    <div class="container mx-auto">
        <h1>Kết quả thanh toán</h1>
        <h2>Kết quả: <?php echo $status; ?></h2>
        <div class="alert <?php
            if ($status == 'Thành công') echo 'alert-success';
            elseif ($status == 'Thất bại') echo 'alert-danger';
            else echo 'alert-warning';
        ?>">
            <p><i class="fas <?php
                if ($status == 'Thành công') echo 'fa-check-circle';
                elseif ($status == 'Thất bại') echo 'fa-times-circle';
                else echo 'fa-clock';
            ?>"></i><?php echo $message; ?></p>
        </div>

        <?php if ($order_details): ?>
        <h3 class="mt-4">Chi tiết đơn hàng:</h3>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Mã giao dịch:</strong> <?php echo htmlspecialchars($order_details['TransactionCode']); ?></li>
            <li class="list-group-item"><strong>Zp Trans ID:</strong> <?php echo htmlspecialchars($zp_trans_id ?: 'N/A'); ?></li>
            <li class="list-group-item"><strong>Số tiền:</strong> <?php echo number_format($order_details['Amount']); ?> VND</li>
            <li class="list-group-item"><strong>Mô tả:</strong> <?php echo htmlspecialchars($order_details['OrderDescription']); ?></li>
            <li class="list-group-item"><strong>Ngày đặt hàng:</strong> <?php echo $order_details['OrderDate']; ?></li>
            <li class="list-group-item"><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order_details['CustomerName']); ?> (<?php echo htmlspecialchars($order_details['Email']); ?>)</li>
            <li class="list-group-item"><strong>Trạng thái đơn hàng:</strong> <?php echo htmlspecialchars($order_details['OrderStatus']); ?></li>
        </ul>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.html" class="btn btn-success">Quay về trang chính</a>
        </div>
    </div>
</body>
</html>
