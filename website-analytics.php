<?php
// website-analytics.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);

// التحقق من تسجيل الدخول
if (!$userManager->isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$website_id = $_GET['id'] ?? 0;

// التحقق من ملكية الموقع
$website = $websiteManager->getWebsiteById($website_id, $user_id);
if (!$website) {
    redirect('dashboard.php');
}

// تحديد اللغة
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
$is_rtl = $lang === 'ar';

// الحصول على الإحصائيات
$days = $_GET['days'] ?? 30;
$stats = $websiteManager->getWebsiteStats($website_id, $days);
$countries = $websiteManager->getVisitorsByCountry($website_id, $days);
$cities = $websiteManager->getVisitorsByCity($website_id, $days);
$devices = $websiteManager->getDeviceStats($website_id, $days);
$browsers = $websiteManager->getBrowserStats($website_id, $days);
$top_pages = $websiteManager->getTopPages($website_id, $days);
$referrers = $websiteManager->getReferrers($website_id, $days);
$hourly_stats = $websiteManager->getHourlyStats($website_id, 7);
$daily_stats = $websiteManager->getDailyStats($website_id, $days);

// معالجة طلبات AJAX لتصدير البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $export_type = $_POST['export_type'] ?? 'json';
    $export_data = [
        'website' => $website,
        'stats' => $stats,
        'countries' => $countries,
        'cities' => $cities,
        'devices' => $devices,
        'browsers' => $browsers,
        'top_pages' => $top_pages,
        'referrers' => $referrers,
        'period' => $days . ' days'
    ];
    
    if ($export_type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics_' . $website['domain'] . '_' . date('Y-m-d') . '.json"');
        echo json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'تحليلات ' . htmlspecialchars($website['name']) : 'Analytics for ' . htmlspecialchars($website['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($is_rtl): ?>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #1a365d;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --warning-color: #d69e2e;
            --error-color: #e53e3e;
            --background: #f7fafc;
            --surface: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo $is_rtl ? "'Tajawal'" : "'Roboto'"; ?>, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: var(--surface);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            padding: 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .website-info h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .website-info p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .filters {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .filter-group {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--accent-color);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .stat-change {
            font-size: 12px;
            margin-top: 4px;
        }
        
        .stat-change.positive {
            color: var(--success-color);
        }
        
        .stat-change.negative {
            color: var(--error-color);
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .chart-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .table-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: <?php echo $is_rtl ? 'right' : 'left'; ?>;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .table tr:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        .progress-bar {
            background: var(--border-color);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-top: 4px;
        }
        
        .progress-fill {
            background: var(--accent-color);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .metric-item:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .metric-value {
            font-weight: 700;
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .export-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 30px;
            box-shadow: var(--shadow);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- الرأس -->
        <div class="header">
            <div class="header-content">
                <div class="website-info">
                    <h1><?php echo htmlspecialchars($website['name']); ?></h1>
                    <p>
                        <i class="fas fa-globe"></i>
                        <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($website['domain']); ?>
                        </a>
                        - <?php echo $is_rtl ? 'آخر ' . $days . ' يوم' : 'Last ' . $days . ' days'; ?>
                    </p>
                </div>
                
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                        <?php echo $is_rtl ? 'العودة للوحة التحكم' : 'Back to Dashboard'; ?>
                    </a>
                    <button class="btn btn-outline" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        <?php echo $is_rtl ? 'تصدير البيانات' : 'Export Data'; ?>
                    </button>
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&id=<?php echo $website_id; ?>&days=<?php echo $days; ?>" class="btn btn-outline">
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- الفلاتر -->
        <div class="filters">
            <div class="filter-group">
                <label><?php echo $is_rtl ? 'الفترة الزمنية:' : 'Time Period:'; ?></label>
                <select onchange="changePeriod(this.value)">
                    <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 7 أيام' : 'Last 7 days'; ?></option>
                    <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 30 يوم' : 'Last 30 days'; ?></option>
                    <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 3 أشهر' : 'Last 3 months'; ?></option>
                </select>
            </div>
        </div>
        
        <!-- الإحصائيات الرئيسية -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_sessions']); ?></div>
                <div class="stat-label"><?php echo $is_rtl ? 'إجمالي الجلسات' : 'Total Sessions'; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['unique_visitors']); ?></div>
                <div class="stat-label"><?php echo $is_rtl ? 'الزوار الفريدون' : 'Unique Visitors'; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['page_views']); ?></div>
                <div class="stat-label"><?php echo $is_rtl ? 'مشاهدات الصفحات' : 'Page Views'; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['avg_session_duration']; ?>s</div>
                <div class="stat-label"><?php echo $is_rtl ? 'متوسط مدة الجلسة' : 'Avg. Session Duration'; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['bounce_rate'], 1); ?>%</div>
                <div class="stat-label"><?php echo $is_rtl ? 'معدل الارتداد' : 'Bounce Rate'; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['new_visitors']); ?></div>
                <div class="stat-label"><?php echo $is_rtl ? 'الزوار الجدد' : 'New Visitors'; ?></div>
                <div class="stat-change positive">
                    <?php 
                    $total_visitors = $stats['new_visitors'] + $stats['returning_visitors'];
                    if ($total_visitors > 0) {
                        echo round(($stats['new_visitors'] / $total_visitors) * 100, 1) . '%';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- الرسوم البيانية -->
        <div class="charts-grid">
            <!-- رسم بياني للجلسات اليومية -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><?php echo $is_rtl ? 'الجلسات اليومية' : 'Daily Sessions'; ?></h3>
                </div>
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            
            <!-- رسم بياني للأجهزة -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><?php echo $is_rtl ? 'أنواع الأجهزة' : 'Device Types'; ?></h3>
                </div>
                <div class="chart-container">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
            
            <!-- رسم بياني للمتصفحات -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><?php echo $is_rtl ? 'المتصفحات' : 'Browsers'; ?></h3>
                </div>
                <div class="chart-container">
                    <canvas id="browserChart"></canvas>
                </div>
            </div>
            
            <!-- رسم بياني للساعات -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><?php echo $is_rtl ? 'الجلسات بالساعة' : 'Sessions by Hour'; ?></h3>
                </div>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- الجداول -->
        <div class="grid-2">
            <!-- الدول الأعلى -->
            <div class="table-card">
                <h3 class="chart-title" style="margin-bottom: 20px;"><?php echo $is_rtl ? 'أعلى الدول' : 'Top Countries'; ?></h3>
                <?php if (!empty($countries)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo $is_rtl ? 'الدولة' : 'Country'; ?></th>
                                <th><?php echo $is_rtl ? 'الزوار' : 'Visitors'; ?></th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_country_visitors = array_sum(array_column($countries, 'visitors'));
                            foreach ($countries as $country): 
                                $percentage = $total_country_visitors > 0 ? ($country['visitors'] / $total_country_visitors) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($country['country']); ?></td>
                                <td><?php echo number_format($country['visitors']); ?></td>
                                <td><?php echo round($percentage, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-globe"></i>
                        <p><?php echo $is_rtl ? 'لا توجد بيانات جغرافية' : 'No geographic data'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- الصفحات الأعلى -->
            <div class="table-card">
                <h3 class="chart-title" style="margin-bottom: 20px;"><?php echo $is_rtl ? 'أعلى الصفحات' : 'Top Pages'; ?></h3>
                <?php if (!empty($top_pages)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo $is_rtl ? 'الصفحة' : 'Page'; ?></th>
                                <th><?php echo $is_rtl ? 'المشاهدات' : 'Views'; ?></th>
                                <th><?php echo $is_rtl ? 'متوسط الوقت' : 'Avg. Time'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_pages as $page): ?>
                            <tr>
                                <td title="<?php echo htmlspecialchars($page['page_url']); ?>">
                                    <?php echo htmlspecialchars($page['page_title'] ?: $page['page_url']); ?>
                                </td>
                                <td><?php echo number_format($page['views']); ?></td>
                                <td><?php echo round($page['avg_time'], 1); ?>s</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-file"></i>
                        <p><?php echo $is_rtl ? 'لا توجد بيانات صفحات' : 'No page data'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- مصادر الزيارات -->
        <?php if (!empty($referrers)): ?>
        <div class="table-card">
            <h3 class="chart-title" style="margin-bottom: 20px;"><?php echo $is_rtl ? 'مصادر الزيارات' : 'Traffic Sources'; ?></h3>
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo $is_rtl ? 'المصدر' : 'Source'; ?></th>
                        <th><?php echo $is_rtl ? 'الزيارات' : 'Visits'; ?></th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_referrer_visits = array_sum(array_column($referrers, 'count'));
                    foreach ($referrers as $referrer): 
                        $percentage = $total_referrer_visits > 0 ? ($referrer['count'] / $total_referrer_visits) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($referrer['referrer']); ?></td>
                        <td><?php echo number_format($referrer['count']); ?></td>
                        <td><?php echo round($percentage, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- قسم التصدير -->
        <div class="export-section">
            <h3 style="margin-bottom: 16px;"><?php echo $is_rtl ? 'تصدير البيانات' : 'Export Data'; ?></h3>
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                <?php echo $is_rtl ? 'يمكنك تصدير جميع بيانات التحليل لاستخدامها في تطبيقات أخرى' : 'You can export all analytics data for use in other applications'; ?>
            </p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="export" value="1">
                <input type="hidden" name="export_type" value="json">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    <?php echo $is_rtl ? 'تصدير JSON' : 'Export JSON'; ?>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // البيانات من PHP
        const dailyStats = <?php echo json_encode($daily_stats, JSON_UNESCAPED_UNICODE); ?>;
        const deviceStats = <?php echo json_encode($devices, JSON_UNESCAPED_UNICODE); ?>;
        const browserStats = <?php echo json_encode($browsers, JSON_UNESCAPED_UNICODE); ?>;
        const hourlyStats = <?php echo json_encode($hourly_stats, JSON_UNESCAPED_UNICODE); ?>;
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        
        // إعدادات الرسوم البيانية
        Chart.defaults.font.family = isRTL ? 'Tajawal' : 'Roboto';
        Chart.defaults.color = '#4a5568';
        
        // رسم بياني للجلسات اليومية
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyStats.map(stat => stat.date),
                datasets: [{
                    label: isRTL ? 'الجلسات' : 'Sessions',
                    data: dailyStats.map(stat => stat.sessions),
                    borderColor: '#3182ce',
                    backgroundColor: 'rgba(49, 130, 206, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: isRTL ? 'الزوار الفريدون' : 'Unique Visitors',
                    data: dailyStats.map(stat => stat.unique_visitors),
                    borderColor: '#38a169',
                    backgroundColor: 'rgba(56, 161, 105, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // رسم بياني للأجهزة
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceStats.map(device => device.device_type),
                datasets: [{
                    data: deviceStats.map(device => device.count),
                    backgroundColor: ['#3182ce', '#38a169', '#d69e2e', '#e53e3e', '#9f7aea'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // رسم بياني للمتصفحات
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        new Chart(browserCtx, {
            type: 'bar',
            data: {
                labels: browserStats.map(browser => browser.browser),
                datasets: [{
                    label: isRTL ? 'المستخدمون' : 'Users',
                    data: browserStats.map(browser => browser.count),
                    backgroundColor: '#3182ce',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // رسم بياني للساعات
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        const hourlyData = Array(24).fill(0);
        hourlyStats.forEach(stat => {
            hourlyData[stat.hour] = stat.sessions;
        });
        
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: isRTL ? 'الجلسات' : 'Sessions',
                    data: hourlyData,
                    backgroundColor: 'rgba(49, 130, 206, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // تغيير الفترة الزمنية
        function changePeriod(days) {
            const url = new URL(window.location);
            url.searchParams.set('days', days);
            window.location.href = url.toString();
        }
        
        // تصدير البيانات
        function exportData() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const exportInput = document.createElement('input');
            exportInput.name = 'export';
            exportInput.value = '1';
            
            const typeInput = document.createElement('input');
            typeInput.name = 'export_type';
            typeInput.value = 'json';
            
            form.appendChild(exportInput);
            form.appendChild(typeInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
</body>
</html>