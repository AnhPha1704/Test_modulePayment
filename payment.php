<?php
require_once 'db.php';

$config = [
    "appid" => 2554,
    "key1" => "sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn",
    "key2" => "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf",
    "endpoint" => "https://sb-openapi.zalopay.vn/v2/create"
];

$embeddata = json_encode(["redirecturl" => "https://www.google.com"]); // Merchant's data

$items = '[]'; // Merchant's data
$transID = rand(0,1000000); //Random trans id

try {
    $db = Database::getConnection();

    // Insert into Orders
    $stmt = $db->prepare("INSERT INTO Orders (CustomerID, TotalAmount, OrderDescription) VALUES (1, ?, ?)");
    $amount = 50000; // Fixed for test
    $description = "Đơn hàng test #$transID";
    $stmt->execute([$amount, $description]);
    $orderId = $db->lastInsertId();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$order = [
    "app_id" => $config["appid"],
    "app_time" => round(microtime(true) * 1000), // miliseconds
    "app_trans_id" => date("ymd") . "_" . $transID, // translation missing: vi.docs.shared.sample_code.comments.app_trans_id
    "app_user" => "user123",
    "item" => $items,
    "embed_data" => $embeddata,
    "amount" => 50000,
    "description" => "Đơn hàng test #$transID",
    "bank_code" => "",
    "callback_url" => "https://unswilled-shantel-domiciliary.ngrok-free.dev/test_module_payment/callback.php",
];

try {
    // Insert into Payments
    $stmt2 = $db->prepare("INSERT INTO Payments (OrderID, Amount, MethodID, TransactionCode, Currency) VALUES (?, ?, 1, ?, 'VND')");
    $stmt2->execute([$orderId, $order["amount"], $order["app_trans_id"]]);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// appid|app_trans_id|appuser|amount|apptime|embeddata|item
$data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"]
    . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];
$order["mac"] = hash_hmac("sha256", $data, $config["key1"]);

$context = stream_context_create([
    "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "method" => "POST",
        "content" => http_build_query($order)
    ]
]);

$resp = file_get_contents($config["endpoint"], false, $context);
$result = json_decode($resp, true);

if(isset($result["return_code"]) && $result["return_code"] == 1){
    header("Location:".$result["order_url"]);
    exit;
}

foreach ($result as $key => $value) {
    echo "$key: $value<br>";
}
