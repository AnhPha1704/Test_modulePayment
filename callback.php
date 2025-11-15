<?php
// 🔑 KHAI BÁO KEY2 (phải khớp với key2 trong ZaloPay Dashboard)
$key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf"; // ⚠️ THAY BẰNG KEY2 THẬT

// Nhận raw JSON từ ZaloPay Server (POST body)
$rawInput = file_get_contents('php://input');
if (!$rawInput) {
    http_response_code(400);
    exit('Empty input');
}

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Danh sách các trường bắt buộc để verify MAC (theo đúng thứ tự trong tài liệu)
$requiredFields = [
    'app_id',
    'app_trans_id',
    'app_user',
    'amount',
    'discount_amount',
    'item',
    'bank_code',
    'description',
    'status',
    'create_time',
    'update_time'
];

// Kiểm tra đủ trường
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        error_log("[Callback] Missing field: $field");
        http_response_code(400);
        exit("Missing field: $field");
    }
}

// Tạo chuỗi để tính MAC: nối các giá trị + key2
$macInput = implode('|', array_map(function($field) use ($data) {
    return strval($data[$field]);
}, $requiredFields)) . '|' . $key2;

$calculatedMac = hash('sha256', $macInput);

// So sánh với mac trong callback
if (!hash_equals($calculatedMac, $data['mac'] ?? '')) {
    error_log("[Callback] MAC mismatch! Expected: $calculatedMac, Got: " . ($data['mac'] ?? 'N/A'));
    http_response_code(400);
    exit('MAC verification failed');
}

// ✅ Callback hợp lệ → Xử lý nghiệp vụ
$order_id = $data['app_trans_id'];
$status = (int) $data['status']; // 1: success, others: fail
$amount = (int) $data['amount'];
$zp_trans_token = $data['zp_trans_token'] ?? '';

// Ghi log callback
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/callback_' . date('Y-m-d') . '.log';
$logEntry = [
    'timestamp' => date('c'),
    'event' => 'CALLBACK_SUCCESS',
    'order_id' => $order_id,
    'status' => $status,
    'amount' => $amount,
    'zp_trans_token' => $zp_trans_token,
    'raw_data' => $data
];
file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

// CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG TRONG DATABASE CỦA BẠN TẠI ĐÂY
// Ví dụ:
// $db->updateOrder($order_id, ['status' => $status === 1 ? 'paid' : 'failed', 'paid_at' => date('Y-m-d H:i:s')]);

// Trả về phản hồi thành công cho ZaloPay (bắt buộc)
// ZaloPay yêu cầu: {"return_code": 1}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['return_code' => 1]);
?>
