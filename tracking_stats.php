<?php
// tracking_stats.php

require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
require_once 'classes/TrackingManager.php';

// 1) تحقق من تسجيل الدخول
$userMgr     = new UserManager($db);
if (!$userMgr->isLoggedIn()) {
    redirect('login.php');
}
$user_id     = $_SESSION['user_id'];

// 2) جلب الموقع والتأكد من الملكية
$website_id  = (int)($_GET['id'] ?? 0);
$siteMgr     = new WebsiteManager($db);
$website     = $siteMgr->getWebsiteById($website_id, $user_id);
if (!$website) {
    redirect('dashboard.php');
}

// 3) فترة التحليل
$days        = (int)($_GET['days'] ?? 30);
$trackMgr    = new TrackingManager($db);

// 4) نقاط نهاية AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    switch ($_GET['ajax']) {
        // إحصائيات مجمعة لكل العناصر
        case 'stats':
            echo json_encode(
                $trackMgr->getElementStats($website_id, $days),
                JSON_UNESCAPED_UNICODE
            );
            break;

        // قائمة بالعناصر نفسها
        case 'elements':
            echo json_encode(
                $trackMgr->getElements($website_id),
                JSON_UNESCAPED_UNICODE
            );
            break;

        // تفاصيل النقرات لعنصر معيّن
        case 'clicks':
            $eid = (int)($_GET['element_id'] ?? 0);
            echo json_encode(
                $trackMgr->getClickDetails($eid, $days),
                JSON_UNESCAPED_UNICODE
            );
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إحصائيات تتبع العناصر — <?= htmlspecialchars($website['name']) ?></title>
  <style>
    body { font-family: sans-serif; padding:20px; }
    table { width:100%; border-collapse: collapse; margin-top:1em }
    th,td { border:1px solid #ccc; padding:.5em; text-align:center }
    th { background:#efefef }
    button { padding:.5em 1em; margin: .5em 0; }
    #detailsModal { display:none; position:fixed; top:10%; left:10%; width:80%; background:#fff; border:1px solid #666; padding:1em; }
  </style>
</head>
<body>

  <h1>إحصائيات تتبع العناصر — <?= htmlspecialchars($website['name']) ?></h1>

  <label>الفترة (أيام): 
    <select id="period">
      <?php foreach ([7,30,90,365] as $d): ?>
        <option value="<?= $d ?>" <?= $d=== $days?'selected':'' ?>>آخر <?= $d ?> يوم</option>
      <?php endforeach; ?>
    </select>
  </label>
  <button onclick="loadStats()">تحديث</button>

  <table id="statsTable">
    <thead>
      <tr>
        <th>الاسم</th>
        <th>المحدد</th>
        <th>النوع</th>
        <th>الظهور</th>
        <th>التحويم</th>
        <th>الإرسال</th>
        <th>النقرات</th>
        <th>مستخدمون فريدون</th>
        <th>CTR %</th>
        <th>تفاصيل</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="10">جاري التحميل…</td></tr>
    </tbody>
  </table>

  <div id="detailsModal">
    <h3>تفاصيل النقرات</h3>
    <button onclick="closeModal()">× إغلاق</button>
    <table id="clickDetails">
      <thead><tr><th>معرّف الجلسة</th><th>الوقت</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>

  <script>
    const websiteId = <?= $website_id ?>;
    function loadStats(){
      const days = document.getElementById('period').value;
      fetch(`?ajax=stats&id=${websiteId}&days=${days}`)
        .then(r=>r.json())
        .then(arr=>{
          const tbody = document.querySelector('#statsTable tbody');
          if(!arr.length){
            tbody.innerHTML = '<tr><td colspan="10">لا توجد بيانات</td></tr>';
            return;
          }
          tbody.innerHTML = arr.map(el=>`
            <tr>
              <td>${el.name}</td>
              <td><code>${el.selector}</code></td>
              <td>${el.selector_type}</td>
              <td>${el.total_impressions}</td>
              <td>${el.total_hovers}</td>
              <td>${el.total_submits}</td>
              <td>${el.total_clicks}</td>
              <td>${el.unique_users}</td>
              <td>${el.ctr_percent}</td>
              <td><button onclick="showClicks(${el.id})">عرض</button></td>
            </tr>
          `).join('');
        });
    }

    function showClicks(elementId){
      const days = document.getElementById('period').value;
      fetch(`?ajax=clicks&id=${websiteId}&element_id=${elementId}&days=${days}`)
        .then(r=>r.json())
        .then(arr=>{
          const tbody = document.querySelector('#clickDetails tbody');
          tbody.innerHTML = arr.length
            ? arr.map(c=>`
                <tr>
                  <td>${c.session_id}</td>
                  <td>${c.occurred_at}</td>
                </tr>
              `).join('')
            : '<tr><td colspan="2">لا توجد نقرات</td></tr>';
          document.getElementById('detailsModal').style.display = 'block';
        });
    }

    function closeModal(){
      document.getElementById('detailsModal').style.display = 'none';
    }

    // تحميل أولي
    document.addEventListener('DOMContentLoaded', loadStats);
  </script>
</body>
</html>