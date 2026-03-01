<?php
// tracking_dashboard.php

require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/TrackingManager.php';

$userManager     = new UserManager($db);
$trackingManager = new TrackingManager($db);

// (افتراضي: تحديد موقع المستخدم من الجلسة أو GET)
if (!$userManager->isLoggedIn()) {
    header('Location: login.php'); exit;
}
$website_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// جلب إحصائيات
$stats = $trackingManager->getStats($website_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة إحصائيات التتبع</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family:sans-serif; padding:20px; background:#fff; }
    .chart-container { width: 100%; max-width:800px; margin: auto; }
    canvas { background:#fafafa; border:1px solid #eee; }
    .no-data { text-align:center; color:#888; margin-top:40px; }
  </style>
</head>
<body>
  <h1>لوحة إحصائيات تتبع العناصر</h1>

  <?php if (empty($stats)): ?>
    <p class="no-data">لا توجد إحصائيات لعرضها.</p>
  <?php else: ?>
    <div class="chart-container">
      <canvas id="clickChart"></canvas>
    </div>

    <script>
      const data = <?= json_encode(array_map(function($r){
          return [
            'name' => $r['name'],
            'clicks' => (int)$r['clicks']
          ];
      }, $stats), JSON_UNESCAPED_UNICODE) ?>;

      new Chart(
        document.getElementById('clickChart').getContext('2d'),
        {
          type: 'bar',
          data: {
            labels: data.map(d=>d.name),
            datasets: [{
              label: 'عدد النقرات',
              data: data.map(d=>d.clicks),
              backgroundColor: 'rgba(49,130,206,0.6)',
              borderColor: 'rgba(49,130,206,1)',
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            scales: {
              x: { beginAtZero: true }
            },
            plugins: {
              legend: { display: false },
              tooltip: { callbacks: {
                label: ctx => ctx.parsed.x + ' نقرة'
              }}
            }
          }
        }
      );
    </script>
  <?php endif; ?>
</body>
</html>