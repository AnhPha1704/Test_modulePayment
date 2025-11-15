<?php
// 🔑 Thông tin app (dùng demo — thay bằng thật khi deploy)
$app_id = 2554;
$key1 = "sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn";

// Nhận app_trans_id từ POST hoặc GET (ưu tiên POST)
$app_trans_id = $_POST['app_trans_id'] ?? $_GET['app_trans_id'] ?? '';

if (!$app_trans_id) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Missing app_trans_id',
        'example_usage' => [
            'POST' => 'curl -X POST check_status.php -d "app_trans_id=251115_1731665432123"',
            'GET'  => 'check_status.php?app_trans_id=251115_1731665432123'
        ]
    ]);
    exit;
}

$app_time = round(microtime(true) * 1000);

// 🔐 Tạo MAC: SHA256(app_id|app_trans_id|app_time|key1)
$mac = hash('sha256', "$app_id|$app_trans_id|$app_time|$key1");

// Chuẩn bị dữ liệu gửi đi
$postData = [
    'app_id' => $app_id,
    'app_trans_id' => $app_trans_id,
    'app_time' => $app_time,
    'mac' => $mac
];

// Gọi API ZaloPay (Sandbox)
$url = 'https://sb-openapi.zalopay.vn/v2/query';
// $url = 'https://sandbox.zalopay.vn/v001/tpe/getstatus';
// $url = 'https://api.zalopay.vn/v001/tpe/getstatus'; // Production

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    error_log("[CheckStatus] cURL Error: $err");
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Failed to connect ZaloPay', 'details' => $err]);
    exit;
}

// Trả nguyên phản hồi từ ZaloPay
header('Content-Type: application/json; charset=utf-8');
echo $response;

// Ghi log debug (tùy chọn)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
file_put_contents($logDir . '/check_status.log', date('c') . " | $app_trans_id → HTTP $httpCode\n", FILE_APPEND);
?>