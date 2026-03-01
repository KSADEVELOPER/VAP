<?php
// analytics.php
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
$user = $userManager->getUserById($user_id);

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
$daily_stats   = $websiteManager->getDailyStats($website_id, $days);
$clickStats    = $websiteManager->getClickStats($website_id, $days);
$osStats = $websiteManager->getOSStats($website_id, $days);
$durBuckets = $websiteManager->getOSStats($website_id, $days);
$pvps = $websiteManager->getPageViewsPerSession($website_id, $days);
$wd = $websiteManager->getWeekdayStats($website_id, $days);
$sessionsList = $websiteManager->getAllSessions($website_id, $days);



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
    // إذا لم يكن هناك ساعات أو دقائق، أو بقيت ثواني
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
            --primary-light: #2d3748;
            --accent-color: #3182ce;
            --accent-hover: #2b6cb0;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --warning-color: #d69e2e;
            --info-color: #3182ce;
            --background: #f7fafc;
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
        }
        
        .sidebar-header h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: var(--text-secondary);
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
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
        }
        
        /* البطاقات */
        .card {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .card-body {
            padding: 24px;
        }
        
        .card-footer {
            padding: 16px 24px;
            background: var(--background);
            border-top: 1px solid var(--border-light);
        }
        
        /* الأزرار */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            font-family: inherit;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #2f855a;
        }
        
        .btn-danger {
            background: var(--error-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .btn-outline:hover {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .btn-lg {
            padding: 16px 32px;
            font-size: 16px;
        }
        
        /* الجدول */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: <?php echo $is_rtl ? 'right' : 'left'; ?>;
            border-bottom: 1px solid var(--border-light);
        }
        
        .table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        /* البطاقات الإحصائية */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent-color);
            transition: var(--transition);
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
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(49, 130, 206, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        /* الحالات والتسميات */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
        }
        
        .badge-warning {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning-color);
        }
        
        .badge-danger {
            background: rgba(229, 62, 62, 0.1);
            color: var(--error-color);
        }
        
        .badge-info {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info-color);
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
        
        /* النماذج */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--surface);
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        /* حالة التحميل */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* التنبيهات */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
            border-color: rgba(56, 161, 105, 0.2);
        }
        
        .alert-error {
            background: rgba(229, 62, 62, 0.1);
            color: var(--error-color);
            border-color: rgba(229, 62, 62, 0.2);
        }
        
        .alert-warning {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning-color);
            border-color: rgba(214, 158, 46, 0.2);
        }
        
        .alert-info {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info-color);
            border-color: rgba(49, 130, 206, 0.2);
        }
        
        /* حالة فارغة */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
        }
        
        /* التجاوب */
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
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .table-responsive {
                overflow-x: auto;
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
        
        /* تأثيرات متقدمة */
        .card-hover {
            transition: var(--transition);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-floating {
            position: fixed;
            bottom: 24px;
            <?php echo $is_rtl ? 'left: 24px;' : 'right: 24px;'; ?>
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            border: none;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            transition: var(--transition);
            z-index: 1000;
        }
        
        .btn-floating:hover {
            transform: scale(1.1);
            background: var(--accent-hover);
        }
        
        /* كود التتبع */
        .tracking-code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            line-height: 1.5;
            overflow-x: auto;
            margin: 16px 0;
            position: relative;
        }
        
        .tracking-code::before {
            content: 'JavaScript';
            position: absolute;
            top: 8px;
            <?php echo $is_rtl ? 'left: 8px;' : 'right: 8px;'; ?>
            background: var(--accent-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .copy-btn {
            position: absolute;
            top: 12px;
            <?php echo $is_rtl ? 'right: 12px;' : 'left: 12px;'; ?>
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
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
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $is_rtl ? SITE_NAME : SITE_NAME_EN; ?></h1>
                <p><?php echo $is_rtl ? 'منصة تحليل الزوار' : 'Analytics Platform'; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="websites">
                    <i class="fas fa-globe"></i>
                    <?php echo $is_rtl ? 'المواقع' : 'Websites'; ?>
                </a>
                <a class="nav-item active" data-tab="analytics">
                    <i class="fas fa-chart-line"></i>
                    <?php echo $is_rtl ? 'التحليلات' : 'Analytics'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="settings">
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
                        <div class="user-role"><?php echo $is_rtl ? 'مستخدم' : 'User'; ?></div>
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
                    <h2 id="page-title"><?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?></h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>" class="lang-switcher">
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </header>
            
            <!-- منطقة المحتوى -->
            <div class="content-area">
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
                <div class="stat-value"><?php 
            echo formatDuration(
                $stats['avg_session_duration'], 
                $is_rtl
            );
        ?></div>
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
            
            <div class="chart-card">
  <div class="chart-header">
    <h3 class="chart-title"><?= $is_rtl?'أنظمة التشغيل':'Operating Systems' ?></h3>
  </div>
  <div class="chart-container">
    <canvas id="osChart"></canvas>
  </div>
</div>
            <div class="chart-card">
  <div class="chart-header">
    <h3 class="chart-title"><?= $is_rtl?'مدة الجلسات':'Session Duration' ?></h3>
  </div>
  <div class="chart-container">
    <canvas id="durationChart"></canvas>
  </div>
</div>
<div class="chart-card">
  <div class="chart-header">
    <h3 class="chart-title"><?= $is_rtl?'مشاهدات الصفحة لكل جلسة':'Pageviews per Session' ?></h3>
  </div>
  <div class="chart-container">
    <canvas id="pvpsChart"></canvas>
  </div>
</div>
<div class="chart-card">
  <div class="chart-header">
    <h3 class="chart-title"><?= $is_rtl?'الجلسات حسب اليوم':'Sessions by Weekday' ?></h3>
  </div>
  <div class="chart-container">
    <canvas id="weekdayChart"></canvas>
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
        



        <!-- إحصائيات النقرات -->
       
      <div class="table-card">
    <h3 class="chart-title" style="margin-bottom: 20px;">
        <?= $is_rtl ? 'إحصائيات النقرات' : 'Click statistics' ?>
    </h3>

    <?php if (!empty($clickStats)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= $is_rtl ? 'المحدد'      : 'Selector'       ?></th>
                        <th><?= $is_rtl ? 'النوع'       : 'Type'           ?></th>
                        <th><?= $is_rtl ? 'النص'        : 'Text'           ?></th>
                        <th><?= $is_rtl ? 'الصفحة'      : 'Page'           ?></th>
                        <th><?= $is_rtl ? 'النقرات'     : 'Clicks'         ?></th>
                        <th><?= $is_rtl ? 'آخر نقرة'    : 'Last Click'     ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clickStats as $row): ?>
                        <tr>
                            <td>
                                <?php
                                if (!empty($row['element_selector'])) {
                                    echo htmlspecialchars($row['element_selector']);
                                } else {
                                    echo $is_rtl ? 'غير متوفر' : 'Not available';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($row['element_type'])) {
                                    echo htmlspecialchars($row['element_type']);
                                } else {
                                    echo $is_rtl ? 'غير متوفر' : 'Not available';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($row['element_text'])) {
                                    echo htmlspecialchars($row['element_text']);
                                } else {
                                    echo $is_rtl ? 'غير متوفر' : 'Not available';
                                }
                                ?>
                            </td>
                            <td>
    <?php
    if (!empty($row['page_url'])) {
        // يمكنك عرض الرابط كعنوان مختصر أو كامل حسب الرغبة
        echo '<a href="'.htmlspecialchars($row['page_url']).'" target="_blank">'
             . htmlspecialchars(parse_url($row['page_url'], PHP_URL_PATH) ?: $row['page_url'])
             . '</a>';
    } else {
        echo $is_rtl ? 'غير متوفر' : 'Not available';
    }
    ?>
</td>
                            <td>
                                <?= $row['total_clicks'] ?? ($is_rtl ? 'غير متوفر' : 'Not available') ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($row['last_click'])) {
                                    echo htmlspecialchars($row['last_click']);
                                } else {
                                    echo $is_rtl ? 'غير متوفر' : 'Not available';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-mouse-pointer fa-2x"></i>
            <p><?= $is_rtl ? 'لا توجد بيانات نقرات' : 'No click data' ?></p>
        </div>
    <?php endif; ?>
</div>




<div class="table-card">
  <h3 class="chart-title"><?= $is_rtl?'قائمة الجلسات':'Sessions List' ?></h3>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th><?= $is_rtl?'المعرف':'ID' ?></th>
          <th><?= $is_rtl?'بدء':'Start' ?></th>
          <th><?= $is_rtl?'مدة (ث)':'Duration (s)' ?></th>
          <th><?= $is_rtl?'مشاهدات':'Views' ?></th>
          <th><?= $is_rtl?'الدولة':'Country' ?></th>
          <th><?= $is_rtl?'المدينة':'City' ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($sessionsList as $sess): ?>
        <tr>
          <td><?= $sess['id'] ?></td>
          <td><?= $sess['started_at'] ?></td>
          <td><?= $sess['duration'] ?></td>
          <td><?= $sess['page_views'] ?></td>
          <td><?= htmlspecialchars($sess['country']?:($is_rtl?'غير متوفر':'N/A')) ?></td>
          <td><?= htmlspecialchars($sess['city']?:($is_rtl?'غير متوفر':'N/A')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
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






            </div>
        </main>
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
        
        
          const osStats = <?php echo json_encode($osStats, JSON_UNESCAPED_UNICODE); ?>;
  new Chart(
    document.getElementById('osChart').getContext('2d'),
    {
      type: 'doughnut',
      data: {
        labels: osStats.map(o => o.os),
        datasets: [{ data: osStats.map(o => o.count) }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    }
  );
  
  
            const durBuckets = <?php echo json_encode($durBuckets, JSON_UNESCAPED_UNICODE); ?>;

new Chart(
  document.getElementById('durationChart').getContext('2d'),
  {
    type: 'bar',
    data: {
      labels: durBuckets.map(b => b.bucket),
      datasets: [{
        label: isRTL?'عدد الجلسات':'Sessions',
        data: durBuckets.map(b => b.count),
        borderRadius: 4
      }]
    },
    options: { scales:{ y:{ beginAtZero: true }}, plugins:{ legend:{ display:false } } }
  }
);

            const pvps = <?php echo json_encode($pvps, JSON_UNESCAPED_UNICODE); ?>;

new Chart(
  document.getElementById('pvpsChart').getContext('2d'),
  {
    type: 'line',
    data: {
      labels: pvps.map(r => r.page_views),
      datasets: [{ label: isRTL?'جلسات':'Sessions', data: pvps.map(r=>r.sessions), fill:false }]
    },
    options: { scales:{ y:{ beginAtZero:true } } }
  }
);

const wd = <?php echo json_encode($wd, JSON_UNESCAPED_UNICODE); ?>;

const labels = ['','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
new Chart(
  document.getElementById('weekdayChart').getContext('2d'),
  {
    type: 'bar',
    data: {
      labels: wd.map(r=>labels[r.weekday]),
      datasets: [{ label: isRTL?'جلسات':'Sessions', data: wd.map(r=>r.sessions) }]
    },
    options:{ scales:{ y:{ beginAtZero:true } } }
  }
);

    </script>

</body>
</html>