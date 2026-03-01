<?php
// api/track.php - API تتبع محسّن

// إعدادات CORS المحسّنة
$allowed_origins = [
    'https://...example.com',
    'https://snack-web-player.s3.us-west-1.amazonaws.com',
    // أضف النطاقات المصرح لها
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../classes/WebsiteManager.php';







// // api/track.php

// // 1) تفعيل عرض الأخطاء مؤقتاً للتصحيح
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// // 2) CORS (للتصحيح اجعلها مفتوحة مؤقتاً)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Credentials: true');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit;
// }

// header('Content-Type: application/json; charset=utf-8');



// 3) اقرأ الـ raw payload
$raw = file_get_contents('php://input');
// file_put_contents(__DIR__.'/track_debug.log',
    // date('c') . " RAW:\n" . $raw . "\n\n", FILE_APPEND);

$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_json', 'raw'=>$raw]);
    exit;
}

// 4) الحقول المطلوبة
$required = ['website_id','element_id','event_type','occurred_at'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        http_response_code(400);
        echo json_encode(['error'=>"missing_field_$f"]);
        exit;
    }
}

// 5) session_id
if (!session_id()) session_start();
$sessionId = session_id();

// 6) Insert into tracking_events
$sql = "INSERT INTO tracking_events
    (website_id, element_id, session_id, event_type, occurred_at, page_url)
  VALUES (?, ?, ?, ?, ?, ?)";
$params = [
    (int)$data['website_id'],
    (int)$data['element_id'],
    $sessionId,
    $data['event_type'],
    $data['occurred_at'],
    $data['page_url'] ?? null
];
$ok = $db->query($sql, $params);
file_put_contents(__DIR__.'/track_debug.log',
    date('c') . " INSERT_OK: " . ($ok ? 'true' : 'false') .
    " SQL: $sql | PARAMS: " . json_encode($params) . "\n\n",
    FILE_APPEND
);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['error'=>'db_insert_failed']);
    exit;
}

// 7) تحديث العدّادات
switch ($data['event_type']) {
    case 'impression':
        $db->query("UPDATE tracking_elements SET impression_count = impression_count +1 WHERE id = ?", [(int)$data['element_id']]);
        break;
    case 'hover_start':
        $db->query("UPDATE tracking_elements SET hover_count = hover_count +1 WHERE id = ?", [(int)$data['element_id']]);
        break;
    case 'submit':
        $db->query("UPDATE tracking_elements SET submit_count = submit_count +1 WHERE id = ?", [(int)$data['element_id']]);
        break;
    case 'click':
        $db->query("UPDATE tracking_elements SET click_count = click_count +1 WHERE id = ?", [(int)$data['element_id']]);
        break;
}

// 8) ردّ Debug
echo json_encode([
    'status'      => 'ok',
    'received'    => $data,
    'session_id'  => $sessionId
]);