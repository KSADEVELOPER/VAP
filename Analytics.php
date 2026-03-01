<?php
// analytics.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';

// التحقق من تسجيل الدخول
if (!$userManager->isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$website_id = $_GET['id'] ?? 0;

// التحقق من ملكية الموقع
$website = $websiteManager->getWebsiteById($website_id, $user_id);
if (!$website) {
    redirect('dashboard.php&lang='.$lang);
}
$user = $userManager->getUserById($user_id);

// تحديد اللغة
$is_rtl = $lang === 'ar';

// الحصول على الإحصائيات
$days = $_GET['days'] ?? 30;

// معالجة طلبات AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['ajax']) {
        case 'stats':
            $stats = $websiteManager->getWebsiteStats($website_id, $days);
            echo json_encode($stats);
            break;
            
        case 'countries':
            $countries = $websiteManager->getVisitorsByCountry($website_id, $days);
            echo json_encode($countries);
            break;
            
        case 'cities':
            $cities = $websiteManager->getVisitorsByCity($website_id, $days);
            echo json_encode($cities);
            break;
            
        case 'devices':
            $devices = $websiteManager->getDeviceStats($website_id, $days);
            echo json_encode($devices);
            break;
            
        case 'browsers':
            $browsers = $websiteManager->getBrowserStats($website_id, $days);
            echo json_encode($browsers);
            break;
            
        case 'pages':
            $top_pages = $websiteManager->getTopPages($website_id, $days);
            echo json_encode($top_pages);
            break;
            
        case 'referrers':
            $referrers = $websiteManager->getReferrers($website_id, $days);
            echo json_encode($referrers);
            break;
            
        case 'hourly':
            $hourly_stats = $websiteManager->getHourlyStats($website_id, 7);
            echo json_encode($hourly_stats);
            break;
            
        case 'daily':
            $daily_stats = $websiteManager->getDailyStats($website_id, $days);
            echo json_encode($daily_stats);
            break;
            
        case 'clicks':
            $clickStats = $websiteManager->getClickStats($website_id, $days);
            echo json_encode($clickStats);
            break;
            
        case 'os':
            $osStats = $websiteManager->getOSStats($website_id, $days);
            echo json_encode($osStats);
            break;
            
        case 'sessions':
            $sessionsList = $websiteManager->getAllSessions($website_id, $days);
            echo json_encode($sessionsList);
            break;
            
        case 'weekday':
            $wd = $websiteManager->getWeekdayStats($website_id, $days);
            echo json_encode($wd);
            break;
            
        case 'pageviews':
            $pvps = $websiteManager->getPageViewsPerSession($website_id, $days);
            echo json_encode($pvps);
            break;
    }
    exit;
}


// معالجة طلبات AJAX لتصدير البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $export_type = $_POST['export_type'] ?? 'json';
    $export_data = [
        'website' => $website,
        'stats' => $websiteManager->getWebsiteStats($website_id, $days),
        'countries' => $websiteManager->getVisitorsByCountry($website_id, $days),
        'cities' => $websiteManager->getVisitorsByCity($website_id, $days),
        'devices' => $websiteManager->getDeviceStats($website_id, $days),
        'browsers' => $websiteManager->getBrowserStats($website_id, $days),
        'top_pages' => $websiteManager->getTopPages($website_id, $days),
        'referrers' => $websiteManager->getReferrers($website_id, $days),
        'period' => $days . ' days'
    ];
    
    if ($export_type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics_' . $website['domain'] . '_' . date('Y-m-d') . '.json"');
        echo json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

function formatDuration($seconds, $is_rtl) {
    $total = (int) round($seconds);
    $h = floor($total / 3600);
    $m = floor(($total % 3600) / 60);
    $s = $total % 60;
    $parts = [];

    if ($h > 0) {
        if ($is_rtl) {
            $parts[] = $h . ' ' . ($h === 1 ? 'ساعة' : 'ساعات');
        } else {
            $parts[] = $h . 'h';
        }
    }
    if ($m > 0) {
        if ($is_rtl) {
            $parts[] = $m . ' ' . ($m === 1 ? 'دقيقة' : 'دقائق');
        } else {
            $parts[] = $m . 'm';
        }
    }
    if ($s > 0 || empty($parts)) {
        if ($is_rtl) {
            $parts[] = $s . ' ' . ($s === 1 ? 'ثانية' : 'ثوانٍ');
        } else {
            $parts[] = $s . 's';
        }
    }

    return implode(' ', $parts);
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'التحليلات العامة لـ ' . htmlspecialchars($website['name']) : 'Analytics for ' . htmlspecialchars($website['name']); ?></title>
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
            --primary-light: #2d3748;
            --accent-color: #3182ce;
            --accent-hover: #2b6cb0;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --warning-color: #d69e2e;
            --info-color: #3182ce;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-elevated: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --gradient-warning: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            --gradient-info: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
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
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-<?php echo $is_rtl ? 'left' : 'right'; ?>: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            <?php echo $is_rtl ? 'right: 0;' : 'left: 0;'; ?>
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
            background: var(--gradient-primary);
            color: white;
        }
        
        .sidebar-header h1 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: block;
            padding: 12px 24px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            border-<?php echo $is_rtl ? 'left' : 'right'; ?>: 3px solid transparent;
            position: relative;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(49, 130, 206, 0.1);
            color: var(--accent-color);
            border-<?php echo $is_rtl ? 'left' : 'right'; ?>-color: var(--accent-color);
        }
        
        .nav-item i {
            width: 20px;
            margin-<?php echo $is_rtl ? 'left' : 'right'; ?>: 12px;
            text-align: center;
        }
        
        .user-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid var(--border-color);
            background: var(--surface);
        }
        
        .user-info .user-details {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .logout-btn {
            width: 100%;
            padding: 8px 12px;
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .logout-btn:hover {
            background: var(--error-color);
            color: white;
            border-color: var(--error-color);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            flex: 1;
            margin-<?php echo $is_rtl ? 'right' : 'left'; ?>: 280px;
            padding: 0;
        }
        
        .topbar {
            background: var(--surface);
            padding: 16px 32px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        
        .topbar h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .lang-switcher {
            padding: 8px 16px;
            background: var(--background);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            transition: var(--transition);
        }
        
        .lang-switcher:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
        
        .content-area {
            padding: 32px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* رأس الصفحة المحسن */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: var(--border-radius-lg);
            padding: 40px;
            margin-bottom: 40px;
            color: white;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-40px, 40px);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 1;
        }
        
        .website-info h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .website-info p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .website-url {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            color: white;
            transition: var(--transition);
        }
        
        .website-url:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* أزرار محسنة */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* فلاتر محسنة */
        .filters {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--accent-color);
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .filter-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .filter-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
            transition: var(--transition);
            min-width: 150px;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        /* حاويات الإحصائيات المحسنة */
        .stats-section {
            margin-bottom: 40px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .section-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
        }
        
        .section-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        /* بطاقات الإحصائيات المحسنة */
        .stat-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 28px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-right: 4px solid var(--accent-color);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .stat-change {
            font-size: 12px;
            margin-top: 8px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .stat-change.positive {
            color: var(--success-color);
        }
        
        .stat-change.negative {
            color: var(--error-color);
        }
        
        /* حالة التحميل */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            margin: -20px 0 0 -20px;
            border: 3px solid rgba(49, 130, 206, 0.3);
            border-radius: 50%;
            border-top-color: var(--accent-color);
            animation: spin 1s linear infinite;
            z-index: 1000;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* الرسوم البيانية المحسنة */
        .charts-section {
            margin-bottom: 40px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }
        
        .chart-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 28px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-right: 4px solid var(--info-color);
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .chart-description {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* الجداول المحسنة */
        .table-section {
            margin-bottom: 40px;
        }
        
        .table-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border-right: 4px solid var(--success-color);
            margin-bottom: 30px;
        }
        
        .table-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-light);
            background: rgba(56, 161, 105, 0.05);
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .table-description {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 16px;
            text-align: <?php echo $is_rtl ? 'right' : 'left'; ?>;
            border-bottom: 1px solid var(--border-light);
        }
        
        .table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        /* حالة فارغة محسنة */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: var(--surface);
            border-radius: var(--border-radius);
            border: 2px dashed var(--border-color);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: var(--text-muted);
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* تقسيم الشبكة */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        /* شريط التقدم */
        .progress-bar {
            background: var(--border-color);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            background: var(--gradient-primary);
            height: 100%;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 20px 20px;
            animation: move 1s linear infinite;
        }
        
        @keyframes move {
            0% { background-position: 0 0; }
            100% { background-position: 20px 20px; }
        }
        
        /* متريك آيتم */
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition);
        }
        
        .metric-item:hover {
            background: rgba(49, 130, 206, 0.05);
            margin: 0 -16px;
            padding: 16px;
            border-radius: 8px;
        }
        
        .metric-item:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            font-weight: 500;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .metric-value {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 16px;
        }
        
        /* قسم التصدير */
        .export-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 28px;
            margin-top: 40px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--warning-color);
        }
        
        .export-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .export-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .export-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        /* تحسينات الاستجابة */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(<?php echo $is_rtl ? '100%' : '-100%'; ?>);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-<?php echo $is_rtl ? 'right' : 'left'; ?>: 0;
            }
            
            .topbar {
                padding: 16px 20px;
            }
            
            .content-area {
                padding: 20px;
            }
            
            .container {
                padding: 10px;
            }
            
            .page-header {
                padding: 24px;
                margin-bottom: 24px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .website-info h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .chart-card {
                padding: 20px;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 28px;
            }
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: var(--transition);
        }
        
        .mobile-menu-btn:hover {
            background: var(--background);
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* تأثيرات الأيقونات الملونة */
        .icon-visitors { background: var(--gradient-primary); }
        .icon-sessions { background: var(--gradient-success); }
        .icon-pageviews { background: var(--gradient-info); }
        .icon-duration { background: var(--gradient-warning); }
        .icon-bounce { background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%); }
        .icon-new { background: linear-gradient(135deg, #00cec9 0%, #55a3ff 100%); }
        
        /* تحسينات إضافية */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-up {
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* أنماط النجاح والخطأ */
        .status-success {
            color: var(--success-color);
            background: rgba(56, 161, 105, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-warning {
            color: var(--warning-color);
            background: rgba(214, 158, 46, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-error {
            color: var(--error-color);
            background: rgba(229, 62, 62, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        
                /* النوافذ المنبثقة */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            transition: var(--transition);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }
        
        .modal-content {
            background: var(--surface);
            border-radius: var(--border-radius-lg);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: var(--transition);
        }
        
        .modal.active .modal-content {
            transform: scale(1);
        }
        
        .modal-header {
            padding: 24px 24px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-secondary);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .modal-close:hover {
            background: var(--background);
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        

    </style>
</head>
<body>
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $is_rtl ? SITE_NAME : SITE_NAME_EN; ?></h1>
                <p><?php echo $is_rtl ? 'منصة تحليل الزوار المتقدمة' : 'Advanced Analytics Platform'; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php?lang=<?php echo $lang; ?>" class="nav-item" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?>
                </a>
                <a href="dashboard.php#websites?lang=<?php echo $lang; ?>" class="nav-item" data-tab="websites">
                    <i class="fas fa-globe"></i>
                    <?php echo $is_rtl ? 'مواقعي' : 'My Websites'; ?>
                </a>
                <a class="nav-item active" href = "Analytics.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-chart-line"></i>
                    <?php echo $is_rtl ? 'التحليلات العامة' : 'Analytics'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href = "Interactive-Element.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-pie-chart"></i>
                    <?php echo $is_rtl ? 'تحليلات المحتوى' : 'Content Analytics'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href = "heatmap.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-fire"></i>
                    <?php echo $is_rtl ? 'الخريطة الحرارية' : 'Heatmap'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href = "scan.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-pie-chart"></i>
                    <?php echo $is_rtl ? 'تحليلات الأخطاء والتحسينات' : 'Bug & improvements Analytics'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href="security-scan.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $is_rtl ? 'الفحص الأمني المتقدم' : 'Advanced Security Scan'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>

                <a href="dashboard.php#settings?lang=<?php echo $lang; ?>" class="nav-item" data-tab="settings">
                    <i class="fas fa-cog"></i>
                    <?php echo $is_rtl ? 'الإعدادات' : 'Settings'; ?>
                </a>
                <?php if ($userManager->isAdmin()): ?>
                <a href="admin/" class="nav-item">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $is_rtl ? 'لوحة الإدارة' : 'Admin Panel'; ?>
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="user-info">
                <div class="user-details">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-role"><?php echo $is_rtl ? 'مستخدم نشط' : 'Active User'; ?></div>
                    </div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <?php echo $is_rtl ? 'تسجيل الخروج' : 'Logout'; ?>
                </button>
            </div>
        </aside>
        
        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <!-- الشريط العلوي -->
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 id="page-title"><?php echo $is_rtl ? 'تحليلات' : 'Analytics'; ?></h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&id=<?php echo $website_id; ?>&days=<?php echo $days; ?>" class="lang-switcher">
                        <i class="fas fa-language"></i>
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </header>
            
            <!-- منطقة المحتوى -->
            <div class="content-area">
                <div class="container">
                    <!-- رأس الصفحة المحسن -->
                    <div class="page-header fade-in">
                        <div class="header-content">
                            <div class="website-info">
                                <h1>
                                    <i class="fas fa-chart-bar"></i>
                                    <?php echo htmlspecialchars($website['name']); ?>
                                </h1>
                                <p>
                                    <?php echo $is_rtl ? 'تحليل لحظي وشامل لبيانات موقعك الإلكتروني' : 'Real-time and Comprehensive analysis of your website data'; ?>
                                </p>
                                <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="website-url">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo htmlspecialchars($website['domain']); ?>
                                </a>
                            </div>
                            
                            <div class="header-actions">
                                <!--<a href="dashboard.php" class="btn btn-outline">-->
                                <!--    <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>-->
                                <!--    <?php //echo $is_rtl ? 'العودة للوحة التحكم' : 'Back to Dashboard'; ?>-->
                                <!--</a>-->
                                <button class="btn btn-outline" onclick="exportData()">
                                    <i class="fas fa-download"></i>
                                    <?php echo $is_rtl ? 'تصدير البيانات' : 'Export Data'; ?>
                                </button>
                                <button class="btn btn-primary" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i>
                                    <?php echo $is_rtl ? 'تحديث البيانات' : 'Refresh Data'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- فلاتر محسنة -->
                    <div class="filters slide-up">
                        <div class="filter-header">
                            <i class="fas fa-filter"></i>
                            <h3><?php echo $is_rtl ? 'خيارات التصفية' : 'Filter Options'; ?></h3>
                        </div>
                        <div class="filter-description">
                            <?php echo $is_rtl ? 'اختر الفترة الزمنية لعرض البيانات المطلوبة' : 'Select the time period to display the required data'; ?>
                        </div>
                        <div class="filter-group">
                            <label style="font-weight: 600; color: var(--text-primary);">
                                <i class="fas fa-calendar"></i>
                                <?php echo $is_rtl ? 'الفترة الزمنية:' : 'Time Period:'; ?>
                            </label>
                            <select onchange="changePeriod(this.value)" id="periodSelect">
                                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>
                                    <?php echo $is_rtl ? 'آخر 7 أيام' : 'Last 7 days'; ?>
                                </option>
                                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>
                                    <?php echo $is_rtl ? 'آخر 30 يوم' : 'Last 30 days'; ?>
                                </option>
                                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>
                                    <?php echo $is_rtl ? 'آخر 3 أشهر' : 'Last 3 months'; ?>
                                </option>
                                <option value="365" <?php echo $days == 365 ? 'selected' : ''; ?>>
                                    <?php echo $is_rtl ? 'آخر سنة' : 'Last year'; ?>
                                </option>
                            </select>
                            <span class="status-success">
                                <i class="fas fa-check"></i>
                                <?php echo $is_rtl ? 'متصل' : 'Active'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- قسم الإحصائيات الرئيسية -->
                    <div class="stats-section">
                        <div class="section-header">
                            <i class="fas fa-chart-pie"></i>
                            <div>
                                <h2><?php echo $is_rtl ? 'الإحصائيات الرئيسية' : 'Key Statistics'; ?></h2>
                                <div class="section-description">
                                    <?php echo $is_rtl ? 'نظرة سريعة على أداء موقعك خلال الفترة المحددة' : 'Quick overview of your website performance during the selected period'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-grid" id="mainStats">
                            <!-- سيتم تحميل البيانات هنا عبر AJAX -->
                            <div class="stat-card loading">
                                <div class="stat-icon icon-sessions">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'إجمالي الجلسات' : 'Total Sessions'; ?></div>
                            </div>
                            
                            <div class="stat-card loading">
                                <div class="stat-icon icon-visitors">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'الزوار الفريدون' : 'Unique Visitors'; ?></div>
                            </div>
                            
                            <div class="stat-card loading">
                                <div class="stat-icon icon-pageviews">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'مشاهدات الصفحات' : 'Page Views'; ?></div>
                            </div>
                            
                            <div class="stat-card loading">
                                <div class="stat-icon icon-duration">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'متوسط مدة الجلسة' : 'Avg. Session Duration'; ?></div>
                            </div>
                            
                            <div class="stat-card loading">
                                <div class="stat-icon icon-bounce">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'معدل الارتداد' : 'Bounce Rate'; ?></div>
                            </div>
                            
                            <div class="stat-card loading">
                                <div class="stat-icon icon-new">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="stat-value skeleton" style="height: 36px; width: 80px;"></div>
                                <div class="stat-label"><?php echo $is_rtl ? 'الزوار الجدد' : 'New Visitors'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- قسم الرسوم البيانية -->
                    <div class="charts-section">
                        <div class="section-header">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h2><?php echo $is_rtl ? 'التحليلات المرئية' : 'Visual Analytics'; ?></h2>
                                <div class="section-description">
                                    <?php echo $is_rtl ? 'رسوم بيانية تفاعلية لفهم سلوك الزوار وأنماط الاستخدام' : 'Interactive charts to understand visitor behavior and usage patterns'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="charts-grid">
                            <!-- رسم بياني للجلسات اليومية -->
                            <div class="chart-card loading" id="dailyChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'الجلسات اليومية' : 'Daily Sessions'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'تتبع نشاط الزوار يومياً' : 'Track daily visitor activity'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="dailyChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- رسم بياني للأجهزة -->
                            <div class="chart-card loading" id="deviceChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'أنواع الأجهزة' : 'Device Types'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'توزيع الزوار حسب نوع الجهاز' : 'Visitor distribution by device type'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="deviceChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- رسم بياني لأنظمة التشغيل -->
                            <div class="chart-card loading" id="osChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'أنظمة التشغيل' : 'Operating Systems'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'أنظمة التشغيل المستخدمة' : 'Operating systems in use'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="osChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- رسم بياني للمتصفحات -->
                            <div class="chart-card loading" id="browserChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'المتصفحات' : 'Browsers'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'المتصفحات الأكثر استخداماً' : 'Most used browsers'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- رسم بياني للساعات -->
                            <div class="chart-card loading" id="hourlyChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'النشاط بالساعة' : 'Hourly Activity'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'أوقات الذروة في الاستخدام' : 'Peak usage times'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="hourlyChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- رسم بياني لأيام الأسبوع -->
                            <div class="chart-card loading" id="weekdayChartCard">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo $is_rtl ? 'النشاط حسب اليوم' : 'Activity by Weekday'; ?></h3>
                                        <p class="chart-description"><?php echo $is_rtl ? 'توزيع الزيارات حسب أيام الأسبوع' : 'Visit distribution by weekdays'; ?></p>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="weekdayChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- قسم الجداول والبيانات التفصيلية -->
                    <div class="table-section">
                        <div class="section-header">
                            <i class="fas fa-table"></i>
                            <div>
                                <h2><?php echo $is_rtl ? 'البيانات التفصيلية' : 'Detailed Data'; ?></h2>
                                <div class="section-description">
                                    <?php echo $is_rtl ? 'جداول تفصيلية للصفحات والدول ومصادر الزيارات' : 'Detailed tables for pages, countries, and traffic sources'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <!-- أعلى الدول -->
                            <div class="table-card loading" id="countriesTable">
                                <div class="table-header">
                                    <h3 class="table-title">
                                        <i class="fas fa-globe-americas"></i>
                                        <?php echo $is_rtl ? 'أعلى الدول' : 'Top Countries'; ?>
                                    </h3>
                                    <p class="table-description"><?php echo $is_rtl ? 'الدول الأكثر زيارة' : 'Most visiting countries'; ?></p>
                                </div>
                                <div class="table-responsive">
                                    <!-- سيتم تحميل البيانات هنا -->
                                </div>
                            </div>
                            
                            <!-- أعلى الصفحات -->
                            <div class="table-card loading" id="pagesTable">
                                <div class="table-header">
                                    <h3 class="table-title">
                                        <i class="fas fa-file-alt"></i>
                                        <?php echo $is_rtl ? 'أعلى الصفحات' : 'Top Pages'; ?>
                                    </h3>
                                    <p class="table-description"><?php echo $is_rtl ? 'الصفحات الأكثر زيارة' : 'Most visited pages'; ?></p>
                                </div>
                                <div class="table-responsive">
                                    <!-- سيتم تحميل البيانات هنا -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- إحصائيات النقرات -->
                        <div class="table-card loading" id="clicksTable">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-mouse-pointer"></i>
                                    <?php echo $is_rtl ? 'إحصائيات النقرات' : 'Click Statistics'; ?>
                                </h3>
                                <p class="table-description"><?php echo $is_rtl ? 'تفاعل المستخدمين مع عناصر الصفحة' : 'User interaction with page elements'; ?></p>
                            </div>
                            <div class="table-responsive">
                                <!-- سيتم تحميل البيانات هنا -->
                            </div>
                        </div>
                        
                        <!-- قائمة الجلسات -->
                        <div class="table-card loading" id="sessionsTable">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-list"></i>
                                    <?php echo $is_rtl ? 'قائمة الجلسات' : 'Sessions List'; ?>
                                </h3>
                                <p class="table-description"><?php echo $is_rtl ? 'تفاصيل الجلسات النشطة والمكتملة' : 'Details of active and completed sessions'; ?></p>
                            </div>
                            <div class="table-responsive">
                                <!-- سيتم تحميل البيانات هنا -->
                            </div>
                        </div>
                        
                        <!-- مصادر الزيارات -->
                        <div class="table-card loading" id="referrersTable">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo $is_rtl ? 'مصادر الزيارات' : 'Traffic Sources'; ?>
                                </h3>
                                <p class="table-description"><?php echo $is_rtl ? 'المواقع التي تحيل الزوار إليك' : 'Websites that refer visitors to you'; ?></p>
                            </div>
                            <div class="table-responsive">
                                <!-- سيتم تحميل البيانات هنا -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- قسم التصدير -->
                    <div class="export-section">
                        <div class="export-header">
                            <i class="fas fa-download"></i>
                            <h3><?php echo $is_rtl ? 'تصدير البيانات' : 'Export Data'; ?></h3>
                        </div>
                        <div class="export-description">
                            <?php echo $is_rtl ? 'يمكنك تصدير جميع بيانات التحليل بتنسيق JSON لاستخدامها في تطبيقات أخرى أو للحفظ كنسخة احتياطية' : 'You can export all analytics data in JSON format for use in other applications or to save as a backup'; ?>
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="export" value="1">
                                <input type="hidden" name="export_type" value="json">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-code"></i>
                                    <?php echo $is_rtl ? 'تصدير JSON' : 'Export JSON'; ?>
                                </button>
                            </form>
                            <button type="button" class="btn btn-secondary" onclick="printReport()">
                                <i class="fas fa-print"></i>
                                <?php echo $is_rtl ? 'طباعة التقرير' : 'Print Report'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- نافذة منبثقة للتحميل -->
    <div id="loadingModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-body" style="text-align: center; padding: 40px;">
                <div style="margin-bottom: 20px;">
                    <i class="fas fa-sync-alt fa-spin fa-3x" style="color: var(--accent-color);"></i>
                </div>
                <h3 style="margin-bottom: 10px; color: var(--text-primary);">
                    <?php echo $is_rtl ? 'جاري تحميل البيانات...' : 'Loading Data...'; ?>
                </h3>
                <p style="color: var(--text-secondary); margin: 0;">
                    <?php echo $is_rtl ? 'يرجى الانتظار بينما نجلب أحدث البيانات' : 'Please wait while we fetch the latest data'; ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        // متغيرات عامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const websiteId = <?php echo $website_id; ?>;
        let currentDays = <?php echo $days; ?>;
        
        // إعدادات Chart.js
        Chart.defaults.font.family = isRTL ? 'Tajawal' : 'Roboto';
        Chart.defaults.color = '#4a5568';
        Chart.defaults.plugins.legend.position = 'bottom';
        Chart.defaults.elements.arc.borderWidth = 0;
        Chart.defaults.elements.line.tension = 0.4;
        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hoverRadius = 6;
        
        // ألوان مخصصة للرسوم البيانية
        const chartColors = {
            primary: '#3182ce',
            success: '#38a169',
            warning: '#d69e2e',
            info: '#3182ce',
            danger: '#e53e3e',
            purple: '#9f7aea',
            pink: '#ed64a6',
            teal: '#319795',
            orange: '#dd6b20',
            cyan: '#0bc5ea',
            palette: [
                '#3182ce', '#38a169', '#d69e2e', '#e53e3e', '#9f7aea',
                '#ed64a6', '#319795', '#dd6b20', '#0bc5ea', '#667eea'
            ]
        };
        
        // كائن لتخزين الرسوم البيانية
        const charts = {};
        
        // كائن لتخزين البيانات
        const analyticsData = {};
        
        // تحديد عدد الطلبات المكتملة
        let completedRequests = 0;
        const totalRequests = 10;
        
        // فئة إدارة البيانات
        class AnalyticsManager {
            constructor() {
                this.loadingElements = new Set();
                this.retryCount = {};
                this.maxRetries = 3;
            }
            
            // إظهار حالة التحميل
            showLoading(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.classList.add('loading');
                    this.loadingElements.add(elementId);
                }
            }
            
            // إخفاء حالة التحميل
            hideLoading(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.classList.remove('loading');
                    this.loadingElements.delete(elementId);
                }
            }
            
            // تحميل البيانات عبر AJAX
            async loadData(type, elementId) {
                try {
                    this.showLoading(elementId);
                    
                    const response = await fetch(`?ajax=${type}&id=${websiteId}&days=${currentDays}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    analyticsData[type] = data;
                    
                    // معالجة البيانات حسب النوع
                    switch (type) {
                        case 'stats':
                            this.renderMainStats(data);
                            break;
                        case 'daily':
                            this.renderDailyChart(data);
                            break;
                        case 'devices':
                            this.renderDeviceChart(data);
                            break;
                        case 'browsers':
                            this.renderBrowserChart(data);
                            break;
                        case 'hourly':
                            this.renderHourlyChart(data);
                            break;
                        case 'os':
                            this.renderOSChart(data);
                            break;
                        case 'weekday':
                            this.renderWeekdayChart(data);
                            break;
                        case 'countries':
                            this.renderCountriesTable(data);
                            break;
                        case 'pages':
                            this.renderPagesTable(data);
                            break;
                        case 'clicks':
                            this.renderClicksTable(data);
                            break;
                        case 'sessions':
                            this.renderSessionsTable(data);
                            break;
                        case 'referrers':
                            this.renderReferrersTable(data);
                            break;
                    }
                    
                    this.hideLoading(elementId);
                    completedRequests++;
                    
                    // تحديث شريط التقدم
                    this.updateProgress();
                    
                } catch (error) {
                    console.error(`Error loading ${type}:`, error);
                    this.handleError(type, elementId, error);
                }
            }
            
            // معالجة الأخطاء
            handleError(type, elementId, error) {
                const retryKey = `${type}_${elementId}`;
                this.retryCount[retryKey] = (this.retryCount[retryKey] || 0) + 1;
                
                if (this.retryCount[retryKey] < this.maxRetries) {
                    // إعادة المحاولة بعد تأخير
                    setTimeout(() => {
                        this.loadData(type, elementId);
                    }, 2000 * this.retryCount[retryKey]);
                } else {
                    // عرض رسالة خطأ
                    this.showError(elementId, error);
                }
            }
            
            // عرض رسالة خطأ
            showError(elementId, error) {
                const element = document.getElementById(elementId);
                if (element) {
                    const errorHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>${isRTL ? 'خطأ في تحميل البيانات' : 'Error Loading Data'}</h3>
                            <p>${isRTL ? 'حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.' : 'An error occurred while loading data. Please try again.'}</p>
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh"></i>
                                ${isRTL ? 'إعادة تحميل' : 'Reload'}
                            </button>
                        </div>
                    `;
                    
                    const contentArea = element.querySelector('.table-responsive') || 
                                      element.querySelector('.chart-container') || 
                                      element;
                    
                    if (contentArea) {
                        contentArea.innerHTML = errorHTML;
                    }
                }
                
                this.hideLoading(elementId);
            }
            
            // تحديث شريط التقدم
            updateProgress() {
                const progress = (completedRequests / totalRequests) * 100;
                
                // إخفاء نافذة التحميل عند الانتهاء
                if (completedRequests >= totalRequests) {
                    setTimeout(() => {
                        const modal = document.getElementById('loadingModal');
                        if (modal) {
                            modal.classList.remove('active');
                        }
                    }, 500);
                }
            }
            
            // رسم الإحصائيات الرئيسية
            renderMainStats(data) {
                const statsContainer = document.getElementById('mainStats');
                if (!statsContainer || !data) return;
                
                const statsHTML = `
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-sessions">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value">${this.formatNumber(data.total_sessions || 0)}</div>
                        <div class="stat-label">${isRTL ? 'إجمالي الجلسات' : 'Total Sessions'}</div>
                    </div>
                    
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-visitors">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-value">${this.formatNumber(data.unique_visitors || 0)}</div>
                        <div class="stat-label">${isRTL ? 'الزوار الفريدون' : 'Unique Visitors'}</div>
                    </div>
                    
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-pageviews">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-value">${this.formatNumber(data.page_views || 0)}</div>
                        <div class="stat-label">${isRTL ? 'مشاهدات الصفحات' : 'Page Views'}</div>
                    </div>
                    
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-duration">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value">${this.formatDuration(data.avg_session_duration || 0)}</div>
                        <div class="stat-label">${isRTL ? 'متوسط مدة الجلسة' : 'Avg. Session Duration'}</div>
                    </div>
                    
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-bounce">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="stat-value">${Math.round(data.bounce_rate || 0)}%</div>
                        <div class="stat-label">${isRTL ? 'معدل الارتداد' : 'Bounce Rate'}</div>
                        <div class="stat-change ${data.bounce_rate > 70 ? 'negative' : 'positive'}">
                            ${data.bounce_rate > 70 ? (isRTL ? 'مرتفع' : 'High') : (isRTL ? 'جيد' : 'Good')}
                        </div>
                    </div>
                    
                    <div class="stat-card fade-in">
                        <div class="stat-icon icon-new">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-value">${this.formatNumber(data.new_visitors || 0)}</div>
                        <div class="stat-label">${isRTL ? 'الزوار الجدد' : 'New Visitors'}</div>
                        <div class="stat-change positive">
                            ${this.calculateNewVisitorPercentage(data)}%
                        </div>
                    </div>
                `;
                
                statsContainer.innerHTML = statsHTML;
            }
            
            // رسم الرسم البياني اليومي
            renderDailyChart(data) {
                const canvas = document.getElementById('dailyChart');
                if (!canvas || !data || data.length === 0) {
                    this.showEmptyChart('dailyChartCard', 'daily');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                // تدمير الرسم البياني السابق إن وجد
                if (charts.daily) {
                    charts.daily.destroy();
                }
                
                charts.daily = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(stat => this.formatDate(stat.date)),
                        datasets: [{
                            label: isRTL ? 'الجلسات' : 'Sessions',
                            data: data.map(stat => stat.sessions || 0),
                            borderColor: chartColors.primary,
                            backgroundColor: chartColors.primary + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: chartColors.primary,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }, {
                            label: isRTL ? 'الزوار الفريدون' : 'Unique Visitors',
                            data: data.map(stat => stat.unique_visitors || 0),
                            borderColor: chartColors.success,
                            backgroundColor: chartColors.success + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: chartColors.success,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 13,
                                        weight: '600'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: chartColors.primary,
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }
            
            // رسم رسم بياني للأجهزة
            renderDeviceChart(data) {
                const canvas = document.getElementById('deviceChart');
                if (!canvas || !data || data.length === 0) {
                    this.showEmptyChart('deviceChartCard', 'devices');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                if (charts.device) {
                    charts.device.destroy();
                }
                
                charts.device = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(device => device.device_type || (isRTL ? 'غير محدد' : 'Unknown')),
                        datasets: [{
                            data: data.map(device => device.count || 0),
                            backgroundColor: chartColors.palette.slice(0, data.length),
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverBorderWidth: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 13,
                                        weight: '600'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
            
            // رسم رسم بياني للمتصفحات
            renderBrowserChart(data) {
                const canvas = document.getElementById('browserChart');
                if (!canvas || !data || data.length === 0) {
                    this.showEmptyChart('browserChartCard', 'browsers');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                if (charts.browser) {
                    charts.browser.destroy();
                }
                
                charts.browser = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(browser => browser.browser || (isRTL ? 'غير محدد' : 'Unknown')),
                        datasets: [{
                            label: isRTL ? 'المستخدمون' : 'Users',
                            data: data.map(browser => browser.count || 0),
                            backgroundColor: chartColors.palette.slice(0, data.length),
                            borderRadius: 8,
                            borderSkipped: false
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // رسم الرسم البياني للساعات
            renderHourlyChart(data) {
                const canvas = document.getElementById('hourlyChart');
                if (!canvas) {
                    this.showEmptyChart('hourlyChartCard', 'hourly');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                const hourlyData = Array(24).fill(0);
                
                if (data && data.length > 0) {
                    data.forEach(stat => {
                        if (stat.hour >= 0 && stat.hour < 24) {
                            hourlyData[stat.hour] = stat.sessions || 0;
                        }
                    });
                }
                
                if (charts.hourly) {
                    charts.hourly.destroy();
                }
                
                charts.hourly = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Array.from({length: 24}, (_, i) => i + ':00'),
                        datasets: [{
                            label: isRTL ? 'الجلسات' : 'Sessions',
                            data: hourlyData,
                            backgroundColor: chartColors.info,
                            borderRadius: 4,
                            borderSkipped: false
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // رسم رسم بياني لأنظمة التشغيل
            renderOSChart(data) {
                const canvas = document.getElementById('osChart');
                if (!canvas || !data || data.length === 0) {
                    this.showEmptyChart('osChartCard', 'os');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                if (charts.os) {
                    charts.os.destroy();
                }
                
                charts.os = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(os => os.os || (isRTL ? 'غير محدد' : 'Unknown')),
                        datasets: [{
                            data: data.map(os => os.count || 0),
                            backgroundColor: chartColors.palette.slice(0, data.length),
                            borderWidth: 3,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 13,
                                        weight: '600'
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
            
            // رسم رسم بياني لأيام الأسبوع
            renderWeekdayChart(data) {
                const canvas = document.getElementById('weekdayChart');
                if (!canvas) {
                    this.showEmptyChart('weekdayChartCard', 'weekday');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                const weekdays = isRTL ? 
                    ['', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] :
                    ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                
                const weekdayData = Array(7).fill(0);
                const labels = [];
                
                if (data && data.length > 0) {
                    data.forEach(stat => {
                        if (stat.weekday >= 1 && stat.weekday <= 7) {
                            weekdayData[stat.weekday - 1] = stat.sessions || 0;
                            if (!labels[stat.weekday - 1]) {
                                labels[stat.weekday - 1] = weekdays[stat.weekday];
                            }
                        }
                    });
                } else {
                    for (let i = 1; i <= 7; i++) {
                        labels[i - 1] = weekdays[i];
                    }
                }
                
                if (charts.weekday) {
                    charts.weekday.destroy();
                }
                
                charts.weekday = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: isRTL ? 'الجلسات' : 'Sessions',
                            data: weekdayData,
                            backgroundColor: chartColors.purple,
                            borderRadius: 8,
                            borderSkipped: false
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // رسم جدول الدول
            renderCountriesTable(data) {
                const container = document.getElementById('countriesTable');
                const tableContainer = container.querySelector('.table-responsive');
                
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = this.getEmptyStateHTML('globe', isRTL ? 'لا توجد بيانات جغرافية' : 'No geographic data');
                    return;
                }
                
                const total = data.reduce((sum, country) => sum + (country.visitors || 0), 0);
                
                const tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-flag"></i> ${isRTL ? 'الدولة' : 'Country'}</th>
                                <th><i class="fas fa-users"></i> ${isRTL ? 'الزوار' : 'Visitors'}</th>
                                <th><i class="fas fa-chart-pie"></i> %</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(country => {
                                const percentage = total > 0 ? ((country.visitors / total) * 100).toFixed(1) : 0;
                                return `
                                    <tr>
                                        <td>${this.escapeHtml(country.country || (isRTL ? 'غير محدد' : 'Unknown'))}</td>
                                        <td><strong>${this.formatNumber(country.visitors || 0)}</strong></td>
                                        <td>
                                            <span style="color: var(--accent-color); font-weight: 600;">${percentage}%</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: ${percentage}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                `;
                
                tableContainer.innerHTML = tableHTML;
            }
            
            // رسم جدول الصفحات
            renderPagesTable(data) {
                const container = document.getElementById('pagesTable');
                const tableContainer = container.querySelector('.table-responsive');
                
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = this.getEmptyStateHTML('file-alt', isRTL ? 'لا توجد بيانات صفحات' : 'No page data');
                    return;
                }
                
                const tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-file-alt"></i> ${isRTL ? 'الصفحة' : 'Page'}</th>
                                <th><i class="fas fa-eye"></i> ${isRTL ? 'المشاهدات' : 'Views'}</th>
                                <th><i class="fas fa-clock"></i> ${isRTL ? 'متوسط الوقت' : 'Avg. Time'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(page => `
                                <tr>
                                    <td>
                                        <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;" 
                                             title="${this.escapeHtml(page.page_url || '')}">
                                            <strong>${this.escapeHtml(page.page_title || page.page_url || (isRTL ? 'بدون عنوان' : 'Untitled'))}</strong>
                                            ${page.page_url ? `<br><small style="color: var(--text-secondary);">${this.escapeHtml(page.page_url)}</small>` : ''}
                                        </div>
                                    </td>
                                    <td><strong>${this.formatNumber(page.views || 0)}</strong></td>
                                    <td>
                                        <span style="color: var(--success-color); font-weight: 600;">
                                            ${Math.round(page.avg_time || 0)}s
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                tableContainer.innerHTML = tableHTML;
            }
            
            // رسم جدول النقرات
            renderClicksTable(data) {
                const container = document.getElementById('clicksTable');
                const tableContainer = container.querySelector('.table-responsive');
                
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = this.getEmptyStateHTML('mouse-pointer', isRTL ? 'لا توجد بيانات نقرات' : 'No click data');
                    return;
                }
                
                const tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-code"></i> ${isRTL ? 'المحدد' : 'Selector'}</th>
                                <th><i class="fas fa-tag"></i> ${isRTL ? 'النوع' : 'Type'}</th>
                                <th><i class="fas fa-font"></i> ${isRTL ? 'النص' : 'Text'}</th>
                                <th><i class="fas fa-link"></i> ${isRTL ? 'الصفحة' : 'Page'}</th>
                                <th><i class="fas fa-mouse-pointer"></i> ${isRTL ? 'النقرات' : 'Clicks'}</th>
                                <th><i class="fas fa-clock"></i> ${isRTL ? 'آخر نقرة' : 'Last Click'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(click => `
                                <tr>
                                    <td>
                                        <code style="background: var(--background); padding: 2px 6px; border-radius: 4px; font-size: 12px;">
                                            ${this.escapeHtml(click.element_selector || (isRTL ? 'غير متوفر' : 'N/A'))}
                                        </code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            ${this.escapeHtml(click.element_type || (isRTL ? 'غير محدد' : 'Unknown'))}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" 
                                             title="${this.escapeHtml(click.element_text || '')}">
                                            ${this.escapeHtml(click.element_text || (isRTL ? 'غير متوفر' : 'N/A'))}
                                        </div>
                                    </td>
                                    <td>
                                        ${click.page_url ? `
                                            <a href="${this.escapeHtml(click.page_url)}" target="_blank" 
                                               style="color: var(--accent-color); text-decoration: none;">
                                                <i class="fas fa-external-link-alt"></i>
                                                ${this.escapeHtml(this.getPagePath(click.page_url))}
                                            </a>
                                        ` : (isRTL ? 'غير متوفر' : 'N/A')}
                                    </td>
                                    <td>
                                        <strong style="color: var(--accent-color);">
                                            ${this.formatNumber(click.total_clicks || 0)}
                                        </strong>
                                    </td>
                                    <td>
                                        <small style="color: var(--text-secondary);">
                                            ${click.last_click ? this.formatDateTime(click.last_click) : (isRTL ? 'غير متوفر' : 'N/A')}
                                        </small>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                tableContainer.innerHTML = tableHTML;
            }
            
            // رسم جدول الجلسات
            renderSessionsTable(data) {
                const container = document.getElementById('sessionsTable');
                const tableContainer = container.querySelector('.table-responsive');
                
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = this.getEmptyStateHTML('list', isRTL ? 'لا توجد جلسات' : 'No sessions data');
                    return;
                }
                
                // عرض أول 10 جلسات فقط
                const limitedData = data.slice(0, 10);
                
                const tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ${isRTL ? 'المعرف' : 'ID'}</th>
                                <th><i class="fas fa-play"></i> ${isRTL ? 'بدء الجلسة' : 'Session Start'}</th>
                                <th><i class="fas fa-hourglass-half"></i> ${isRTL ? 'المدة' : 'Duration'}</th>
                                <th><i class="fas fa-eye"></i> ${isRTL ? 'المشاهدات' : 'Page Views'}</th>
                                <th><i class="fas fa-globe"></i> ${isRTL ? 'الموقع' : 'Location'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${limitedData.map(session => `
                                <tr>
                                    <td>
                                        <span style="font-family: monospace; background: var(--background); padding: 2px 6px; border-radius: 4px;">
                                            #${session.id || 'N/A'}
                                        </span>
                                    </td>
                                    <td>
                                        <small style="color: var(--text-secondary);">
                                            ${session.started_at ? this.formatDateTime(session.started_at) : (isRTL ? 'غير متوفر' : 'N/A')}
                                        </small>
                                    </td>
                                    <td>
                                        <strong style="color: var(--warning-color);">
                                            ${this.formatDuration(session.duration || 0)}
                                        </strong>
                                    </td>
                                    <td>
                                        <strong style="color: var(--info-color);">
                                            ${this.formatNumber(session.page_views || 0)}
                                        </strong>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-flag"></i>
                                            ${this.escapeHtml(session.country || (isRTL ? 'غير محدد' : 'Unknown'))}
                                            ${session.city ? `<br><small style="color: var(--text-secondary);"><i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(session.city)}</small>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    ${data.length > 10 ? `
                        <div style="padding: 16px; text-align: center; background: var(--background); border-top: 1px solid var(--border-light);">
                            <small style="color: var(--text-secondary);">
                                ${isRTL ? `يتم عرض 10 من أصل ${data.length} جلسة` : `Showing 10 of ${data.length} sessions`}
                            </small>
                        </div>
                    ` : ''}
                `;
                
                tableContainer.innerHTML = tableHTML;
            }
            
            // رسم جدول المراجع
            renderReferrersTable(data) {
                const container = document.getElementById('referrersTable');
                const tableContainer = container.querySelector('.table-responsive');
                
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = this.getEmptyStateHTML('external-link-alt', isRTL ? 'لا توجد مصادر إحالة' : 'No referrer data');
                    return;
                }
                
                const total = data.reduce((sum, ref) => sum + (ref.count || 0), 0);
                
                const tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-external-link-alt"></i> ${isRTL ? 'المصدر' : 'Source'}</th>
                                <th><i class="fas fa-users"></i> ${isRTL ? 'الزيارات' : 'Visits'}</th>
                                <th><i class="fas fa-chart-pie"></i> %</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(referrer => {
                                const percentage = total > 0 ? ((referrer.count / total) * 100).toFixed(1) : 0;
                                const source = referrer.referrer || (isRTL ? 'مباشر' : 'Direct');
                                return `
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-${this.getSourceIcon(source)}"></i>
                                                <div>
                                                    <strong>${this.escapeHtml(source)}</strong>
                                                    ${this.getSourceDescription(source)}
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong>${this.formatNumber(referrer.count || 0)}</strong></td>
                                        <td>
                                            <span style="color: var(--accent-color); font-weight: 600;">${percentage}%</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: ${percentage}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                `;
                
                tableContainer.innerHTML = tableHTML;
            }
            
            // إظهار حالة فارغة للرسوم البيانية
            showEmptyChart(cardId, type) {
                const card = document.getElementById(cardId);
                const container = card.querySelector('.chart-container');
                
                if (container) {
                    container.innerHTML = this.getEmptyStateHTML('chart-line', 
                        isRTL ? 'لا توجد بيانات للعرض' : 'No data to display');
                }
            }
            
            // الحصول على HTML للحالة الفارغة
            getEmptyStateHTML(icon, message) {
                return `
                    <div class="empty-state">
                        <i class="fas fa-${icon}"></i>
                        <h3>${message}</h3>
                        <p>${isRTL ? 'لا توجد بيانات متاحة للفترة المحددة' : 'No data available for the selected period'}</p>
                    </div>
                `;
            }
            
            // تنسيق الأرقام
            formatNumber(num) {
                if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + (isRTL ? 'م' : 'M');
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + (isRTL ? 'ك' : 'K');
                }
                return num.toLocaleString();
            }
            
            // تنسيق المدة
            formatDuration(seconds) {
                const total = Math.round(seconds);
                const h = Math.floor(total / 3600);
                const m = Math.floor((total % 3600) / 60);
                const s = total % 60;
                
                if (h > 0) {
                    return isRTL ? `${h}س ${m}د` : `${h}h ${m}m`;
                } else if (m > 0) {
                    return isRTL ? `${m}د ${s}ث` : `${m}m ${s}s`;
                } else {
                    return isRTL ? `${s}ث` : `${s}s`;
                }
            }
            
            // تنسيق التاريخ
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString(isRTL ? 'ar-SA' : 'en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }
            
            // تنسيق التاريخ والوقت
            formatDateTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString(isRTL ? 'ar-SA' : 'en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            // حساب نسبة الزوار الجدد
            calculateNewVisitorPercentage(data) {
                const total = (data.new_visitors || 0) + (data.returning_visitors || 0);
                if (total === 0) return 0;
                return Math.round(((data.new_visitors || 0) / total) * 100);
            }
            
            // الحصول على مسار الصفحة
            getPagePath(url) {
                try {
                    const urlObj = new URL(url);
                    return urlObj.pathname || url;
                } catch (e) {
                    return url;
                }
            }
            
            // الحصول على أيقونة المصدر
            getSourceIcon(source) {
                const lowerSource = source.toLowerCase();
                if (lowerSource.includes('google')) return 'search';
                if (lowerSource.includes('facebook')) return 'facebook-f';
                if (lowerSource.includes('twitter')) return 'twitter';
                if (lowerSource.includes('instagram')) return 'instagram';
                if (lowerSource.includes('linkedin')) return 'linkedin';
                if (lowerSource.includes('youtube')) return 'youtube';
                if (lowerSource === 'direct' || lowerSource === 'مباشر') return 'compass';
                return 'external-link-alt';
            }
            
            // الحصول على وصف المصدر
            getSourceDescription(source) {
                const lowerSource = source.toLowerCase();
                if (lowerSource.includes('google')) {
                    return `<br><small style="color: var(--text-secondary);">${isRTL ? 'محرك بحث' : 'Search Engine'}</small>`;
                }
                if (lowerSource.includes('facebook') || lowerSource.includes('twitter') || lowerSource.includes('instagram')) {
                    return `<br><small style="color: var(--text-secondary);">${isRTL ? 'وسائل التواصل' : 'Social Media'}</small>`;
                }
                if (lowerSource === 'direct' || lowerSource === 'مباشر') {
                    return `<br><small style="color: var(--text-secondary);">${isRTL ? 'دخول مباشر' : 'Direct Access'}</small>`;
                }
                return '';
            }
            
            // تشفير HTML
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // إنشاء مثيل من مدير التحليلات
        const analyticsManager = new AnalyticsManager();
        
        // وظائف عامة
        function changePeriod(days) {
            const url = new URL(window.location);
            url.searchParams.set('days', days);
            window.location.href = url.toString();
        }
        
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
        
        function refreshData() {
            location.reload();
        }
        
        function printReport() {
            window.print();
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        function logout() {
            if (confirm(isRTL ? 'هل أنت متأكد من تسجيل الخروج؟' : 'Are you sure you want to logout?')) {
                window.location.href = 'logout.php&lang=<?php echo $lang; ?>';
            }
        }
        
        // تحميل البيانات عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // إظهار نافذة التحميل
            document.getElementById('loadingModal').classList.add('active');
            
            // تحميل البيانات بالتتابع مع تأخير بسيط لتحسين تجربة المستخدم
            const dataTypes = [
                { type: 'stats', element: 'mainStats' },
                { type: 'daily', element: 'dailyChartCard' },
                { type: 'devices', element: 'deviceChartCard' },
                { type: 'browsers', element: 'browserChartCard' },
                { type: 'hourly', element: 'hourlyChartCard' },
                { type: 'os', element: 'osChartCard' },
                { type: 'weekday', element: 'weekdayChartCard' },
                { type: 'countries', element: 'countriesTable' },
                { type: 'pages', element: 'pagesTable' },
                { type: 'clicks', element: 'clicksTable' },
                { type: 'sessions', element: 'sessionsTable' },
                { type: 'referrers', element: 'referrersTable' }
            ];
            
            // تحميل البيانات بشكل متتالي مع تأخير
            dataTypes.forEach((item, index) => {
                setTimeout(() => {
                    analyticsManager.loadData(item.type, item.element);
                }, index * 300); // تأخير 300ms بين كل طلب
            });
            
            // إضافة مستمع للنقر خارج النافذة المنبثقة لإغلاقها
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('loadingModal');
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
            
            // إضافة تأثيرات التحريك
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, { threshold: 0.1 });
            
            // مراقبة العناصر للتحريك
            document.querySelectorAll('.chart-card, .table-card, .stat-card').forEach(el => {
                observer.observe(el);
            });
        });
        
        // تحسين الاستجابة
        window.addEventListener('resize', function() {
            // إعادة رسم الرسوم البيانية عند تغيير حجم النافذة
            Object.values(charts).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        });
        
        // تحسين الطباعة
        window.addEventListener('beforeprint', function() {
            // إخفاء العناصر غير المرغوب فيها عند الطباعة
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
        
        // CSS للطباعة
        const printStyles = `
            <style>
                @media print {
                    .sidebar, .topbar, .btn, .export-section { display: none !important; }
                    .main-content { margin-left: 0 !important; margin-right: 0 !important; }
                    .page-header { break-inside: avoid; }
                    .chart-card, .table-card, .stat-card { break-inside: avoid; margin-bottom: 20px; }
                    .stats-grid { grid-template-columns: repeat(3, 1fr); }
                    .charts-grid { grid-template-columns: 1fr; }
                    body.printing .fade-in { animation: none; }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', printStyles);
    </script>
</body>
</html>