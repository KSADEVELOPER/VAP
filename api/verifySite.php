<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/WebsiteManager.php';

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['site_id'])) {
  echo json_encode(['error' => 'Missing site_id']);
  exit;
}

$siteMgr = new WebsiteManager($db);
// تأكد من أن هذا الموقع يخص المستخدم الحالي (مثلاً عبر $userId من الجلسة)
$site = $siteMgr->getById($input['site_id']);
if (!$site || $site['user_id'] != $_SESSION['user_id']) {
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// قم بمحاكاة التحقق: حاول جلب ملف JS من الدومين
$domain = $site['domain'];
$url = "https://{$domain}/track.js"; // أو مسار الكود الخاص بك
$ok = false;
try {
  $ctx = stream_context_create(['http'=>['timeout'=>5]]);
  $content = @file_get_contents($url, false, $ctx);
  if (strpos($content, $site['tracking_code']) !== false) {
    $ok = true;
  }
} catch (Exception $e) { }

if ($ok) {
  $db->query("UPDATE websites SET is_verified = 1 WHERE id = ?", [$site['id']]);
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['error' => 'Verification failed']);
}