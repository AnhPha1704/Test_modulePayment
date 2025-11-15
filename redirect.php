<?php
// 🔑 KHAI BÁO KEY2 (phải khớp với key2 trong ZaloPay Dashboard)
$key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf"; // ⚠️ THAY BẰNG KEY2 THẬT

// Lấy các tham số từ URL (GET)
$app_trans_id = $_GET['app_trans_id'] ?? '';
$zp_trans_token = $_GET['zp_trans_token'] ?? '';
$status = intval($_GET['status'] ?? -99); // -99: missing
$mac_from_zp = $_GET['mac'] ?? '';

// ✅ Xác minh MAC (theo đúng spec tài liệu)
$mac_input = "$app_trans_id|$zp_trans_token|$status|$key2";
$calculated_mac = hash('sha256', $mac_input);

$is_valid = hash_equals($calculated_mac, $mac_from_zp);

// Ghi log
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logEntry = [
    'timestamp' => date('c'),
    'event' => 'REDIRECT',
    'app_trans_id' => $app_trans_id,
    'status' => $status,
    'mac_valid' => $is_valid,
    'raw_get' => $_GET
];
file_put_contents($logDir . '/redirect_' . date('Y-m-d') . '.log', 
    json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", 
    FILE_APPEND);

// Chuẩn bị dữ liệu hiển thị
if (!$is_valid) {
    $title = 'Lỗi xác thực';
    $message = '⚠️ Mã xác minh (MAC) không hợp lệ. Vui lòng liên hệ hỗ trợ.';
    $color = '#dc3545';
} else {
    switch ($status) {
        case 1:
            $title = '✅ Thanh toán thành công!';
            $message = 'Cảm ơn bạn đã sử dụng dịch vụ. Đơn hàng đang được xử lý.';
            $color = '#28a745';
            break;
        case 0:
            $title = '⏳ Đang chờ xử lý';
            $message = 'Giao dịch đang được xác nhận. Vui lòng không tắt trang.';
            $color = '#ffc107';
            break;
        default:
            $title = '❌ Thanh toán thất bại';
            $message = 'Rất tiếc, giao dịch không thành công. Bạn có thể thử lại.';
            $color = '#dc3545';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kết quả thanh toán - ZaloPay</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 500px;
      margin: 40px auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .header {
      background: #0084ff;
      color: white;
      padding: 24px;
      text-align: center;
    }
    .content {
      padding: 32px 24px;
      text-align: center;
    }
    .icon {
      font-size: 48px;
      margin-bottom: 16px;
    }
    .title {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 12px;
      color: <?= $color ?>;
    }
    .message {
      font-size: 16px;
      color: #495057;
      line-height: 1.5;
    }
    .detail {
      margin-top: 20px;
      font-size: 14px;
      color: #6c757d;
      background: #f8f9fa;
      padding: 12px;
      border-radius: 8px;
    }
    .btn {
      display: inline-block;
      margin-top: 24px;
      padding: 12px 28px;
      background: #0084ff;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
    }
    .btn:hover {
      background: #006cd6;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>ZaloPay</h1>
    </div>
    <div class="content">
      <div class="icon">
        <?php if ($status === 1): ?>✅
        <?php elseif ($status === 0): ?>⏳
        <?php else: ?>❌<?php endif; ?>
      </div>
      <h2 class="title"><?= htmlspecialchars($title) ?></h2>
      <p class="message"><?= htmlspecialchars($message) ?></p>

      <div class="detail">
        Mã đơn: <strong><?= htmlspecialchars($app_trans_id) ?></strong><br>
        Trạng thái: <strong><?= $status === 1 ? 'Thành công' : ($status === 0 ? 'Đang xử lý' : 'Thất bại') ?></strong>
      </div>

      <a href="index.html" class="btn">← Quay lại trang chủ</a>
    </div>
  </div>
</body>
</html>