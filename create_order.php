<?php
// Bắt lỗi nếu có
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// 🔑 THÔNG TIN APP (DÙNG DEMO — BẠN PHẢI THAY KHI DÙNG THẬT!)
$app_id = 2554;
$key1 = "sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn";
$key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf";

// Nhận dữ liệu từ frontend (script.js)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['return_code' => -99, 'return_message' => 'Invalid JSON input']);
    exit;
}

$payment_method = $input['payment_method'] ?? '';
$amount = intval($input['amount'] ?? 50000);
$description = htmlspecialchars($input['description'] ?? 'Demo thanh toán ZaloPay', ENT_QUOTES, 'UTF-8');

// ✅ Chỉ xử lý 3 phương thức — loại bỏ vietqr
$embed_data = [];
switch ($payment_method) {
    case 'zalopay_wallet':
        $embed_data = ['preferred_payment_method' => ['zalopay_wallet']];
        break;
    case 'international_card':
        $embed_data = ['preferred_payment_method' => ['international_card']];
        break;
    case 'domestic_card':
        $embed_data = ['preferred_payment_method' => ['domestic_card', 'account']];
        break;
    default:
        // Nếu không chọn → mặc định hiển thị tất cả (không khuyến khích)
        $embed_data = ['preferred_payment_method' => []];
}

// Chuẩn bị các tham số bắt buộc
$app_trans_id = date('ymd') . '_' . round(microtime(true) * 1000); // VD: 251115_1731665432123
$app_user = 'demo_user_' . substr(md5(uniqid()), 0, 8);
$app_time = round(microtime(true) * 1000);
$bank_code = ""; // Luôn rỗng khi dùng embed_data.preferred_payment_method

// ⚠️ THAY BẰNG DOMAIN THẬT CỦA BẠN (phải khớp với đăng ký trong ZaloPay Dashboard)
$callback_url = "http://localhost/test_module_payment/callback.php";
$redirect_url = "http://localhost/test_module_payment/redirect.php";

$items = json_encode([]);
$embed_data_json = json_encode($embed_data, JSON_UNESCAPED_UNICODE);

// 🔐 Tạo MAC theo spec: SHA256(data + key1)
// data = app_id|app_trans_id|app_user|amount|app_time|embed_data|bank_code|description|callback_url
$dataStr = implode('|', [
    $app_id,
    $app_trans_id,
    $app_user,
    $amount,
    $app_time,
    $embed_data_json,
    $bank_code,
    $description,
    $callback_url
]);

$mac = hash('sha256', $dataStr . $key1);

// Chuẩn bị payload gửi tới ZaloPay
$postData = [
    'app_id' => $app_id,
    'app_trans_id' => $app_trans_id,
    'app_user' => $app_user,
    'amount' => $amount,
    'app_time' => $app_time,
    'embed_data' => $embed_data_json,
    'bank_code' => $bank_code,
    'description' => $description,
    'callback_url' => $callback_url,
    'redirect_url' => $redirect_url,
    'item' => $items,
    'mac' => $mac
];

// Gọi API ZaloPay (Sandbox)
$url = 'https://sb-openapi.zalopay.vn/v2/create';
// $url = 'https://sandbox.zalopay.vn/v001/tpe/createorder';
// $url = 'https://api.zalopay.vn/v001/tpe/createorder'; // Production

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true, // ⚠️ Production nên để true
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Xử lý lỗi curl
if ($err) {
    error_log("[CURL ERROR] $err");
    echo json_encode([
        'return_code' => -1,
        'return_message' => 'Lỗi kết nối ZaloPay Server'
    ]);
    exit;
}

// Parse phản hồi
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("[JSON ERROR] Invalid JSON from ZaloPay: $response");
    echo json_encode([
        'return_code' => -2,
        'return_message' => 'Phản hồi từ ZaloPay không hợp lệ'
    ]);
    exit;
}

// Ghi log (tạo thư mục logs nếu chưa có)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/' . date('Y-m-d') . '.log';
$logEntry = [
    'timestamp' => date('c'),
    'request' => $postData,
    'response' => $result,
    'raw_response' => $response
];
file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

// Trả kết quả về cho frontend
echo json_encode($result);
?>
