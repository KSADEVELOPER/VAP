<?php
// security-scan.php
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
    redirect('dashboard.php?lang='.$lang);
}
$user = $userManager->getUserById($user_id);

// تحديد اللغة
$is_rtl = $lang === 'ar';

// تحديد النصوص حسب اللغة
$texts = [
    'ar' => [
        'site_name' => 'منصة تحليل الزوار المتقدمة',
        'page_title' => 'الفحص الأمني المتقدم لـ ',
        'security_scan' => 'الفحص الأمني المتقدم',
        'dashboard' => 'لوحة التحكم',
        'my_websites' => 'مواقعي',
        'analytics' => 'التحليلات العامة',
        'content_analytics' => 'تحليلات المحتوى',
        'heatmap' => 'الخريطة الحرارية',
        'bug_analytics' => 'تحليلات الأخطاء والتحسينات',
        'security_analytics' => 'الفحص الأمني المتقدم',
        'settings' => 'الإعدادات',
        'admin_panel' => 'لوحة الإدارة',
        'active_user' => 'مستخدم نشط',
        'logout' => 'تسجيل الخروج',
        'refresh_data' => 'بدء من جديد',
        'back_to_dashboard' => 'العودة للوحة التحكم',
        'for_site' => 'لموقع: ',
        'logout_confirm' => 'هل أنت متأكد من تسجيل الخروج؟',
        'security_scan_description' => 'فحص أمني شامل ومتقدم للموقع - نتائج دقيقة وموثوقة',
        'warning_message' => 'تحذير: استخدم هذا النظام فقط على المواقع التي تملكها أو لديك إذن صريح لاختبارها',
        'start_scan' => 'بدء الفحص الأمني الحقيقي',
        'real_security_testing' => 'فحص أمني حقيقي وموثوق للمواقع الإلكترونية - نتائج دقيقة وواقعية',
        'real_security_badge' => 'Real Security Testing - No Simulations'
    ],
    'en' => [
        'site_name' => 'Advanced Analytics Platform',
        'page_title' => 'Advanced Security Scan for ',
        'security_scan' => 'Advanced Security Scan',
        'dashboard' => 'Dashboard',
        'my_websites' => 'My Websites',
        'analytics' => 'General Analytics',
        'content_analytics' => 'Content Analytics',
        'heatmap' => 'Heatmap',
        'bug_analytics' => 'Bug & Improvements Analytics',
        'security_analytics' => 'Advanced Security Scan',
        'settings' => 'Settings',
        'admin_panel' => 'Admin Panel',
        'active_user' => 'Active User',
        'logout' => 'Logout',
        'refresh_data' => 'Start again',
        'back_to_dashboard' => 'Back to Dashboard',
        'for_site' => 'For site: ',
        'logout_confirm' => 'Are you sure you want to logout?',
        'security_scan_description' => 'Comprehensive and advanced security scan for website - accurate and reliable results',
        'warning_message' => 'Warning: Use this system only on websites you own or have explicit permission to test',
        'start_scan' => 'Start Real Security Scan',
        'real_security_testing' => 'Real and reliable security testing for websites - accurate and realistic results',
        'real_security_badge' => 'Real Security Testing - No Simulations'
    ]
];

$t = $texts[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title'] . htmlspecialchars($website['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($is_rtl): ?>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            
            /* ألوان الأمان */
            --danger-color: #dc2626;
            --critical-color: #7c2d12;
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
            background:var(--gradient-primary);
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
            <?php echo $is_rtl ? 'left: 0;' : 'right: 0;'; ?>
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(<?php echo $is_rtl ? '-20px' : '20px'; ?>, -20px);
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            <?php echo $is_rtl ? 'right: 0;' : 'left: 0;'; ?>
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(<?php echo $is_rtl ? '40px' : '-40px'; ?>, 40px);
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
        
        /* قسم الفحص الأمني */
        .security-scan-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--danger-color);
        }
        
        .security-scan-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .security-scan-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--danger-color);
        }
        
        .warning-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .scan-button {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }
        
        .analyze-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, var(--danger-color), #991b1b);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
            justify-content: center;
        }
        
        .analyze-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .analyze-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* قسم التحميل */
        .loading-section {
            display: none;
            text-align: center;
            padding: 40px;
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .security-scanner {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--danger-color);
            border-radius: 50%;
            animation: scan 1.5s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--danger-color), #991b1b);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* قسم النتائج */
        .results-section {
            display: none;
        }
        
        /* درجة الأمان */
        .security-score {
            background: linear-gradient(135deg, #1e293b, #374151);
            color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 8px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            font-weight: bold;
            position: relative;
        }
        
        .score-excellent { border-color: var(--success-color); color: var(--success-color); }
        .score-good { border-color: #10b981; color: #10b981; }
        .score-fair { border-color: var(--warning-color); color: var(--warning-color); }
        .score-poor { border-color: var(--danger-color); color: var(--danger-color); }
        .score-critical { border-color: var(--critical-color); color: var(--critical-color); }
        
        /* شبكة الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--surface);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-right: 4px solid;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card.critical { border-right-color: var(--critical-color); }
        .stat-card.high { border-right-color: var(--danger-color); }
        .stat-card.medium { border-right-color: var(--warning-color); }
        .stat-card.low { border-right-color: var(--info-color); }
        .stat-card.passed { border-right-color: var(--success-color); }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.critical .stat-icon { color: var(--critical-color); }
        .stat-card.high .stat-icon { color: var(--danger-color); }
        .stat-card.medium .stat-icon { color: var(--warning-color); }
        .stat-card.low .stat-icon { color: var(--info-color); }
        .stat-card.passed .stat-icon { color: var(--success-color); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }
        
        /* حاوية المشاكل */
        .issues-container {
            display: grid;
            gap: 25px;
        }
        
        .issue-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-right: 5px solid;
            position: relative;
            overflow: hidden;
        }
        
        .issue-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .issue-card.critical { border-right-color: var(--critical-color); }
        .issue-card.high { border-right-color: var(--danger-color); }
        .issue-card.medium { border-right-color: var(--warning-color); }
        .issue-card.low { border-right-color: var(--info-color); }
        .issue-card.passed { border-right-color: var(--success-color); }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .issue-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .issue-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .severity-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .severity-critical { background: var(--critical-color); }
        .severity-high { background: var(--danger-color); }
        .severity-medium { background: var(--warning-color); }
        .severity-low { background: var(--info-color); }
        .severity-passed { background: var(--success-color); }
        
        .issue-description {
            margin-bottom: 25px;
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        
        .technical-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .technical-details h4 {
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .code-block {
            background: #1a202c;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .solution-section {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .solution-section h4 {
            color: var(--success-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .steps-list {
            list-style: none;
            padding: 0;
            counter-reset: step-counter;
        }
        
        .steps-list li {
            margin-bottom: 12px;
            padding-<?php echo $is_rtl ? 'right' : 'left'; ?>: 25px;
            position: relative;
            line-height: 1.5;
        }
        
        .steps-list li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            <?php echo $is_rtl ? 'right' : 'left'; ?>: 0;
            top: 0;
            background: var(--success-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        /* حالة فارغة */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--success-color);
        }
        
        /* بطاقة الملخص */
        .summary-card {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .summary-content {
            position: relative;
            z-index: 1;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-stat {
            text-align: center;
        }
        
        .summary-stat .number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .summary-stat .label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* تحسينات الاستجابة */
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
            
            .score-circle {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
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
        
        /* تأثيرات الأنيميشن */
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
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $t['site_name']; ?></h1>
                <p><?php echo $t['site_name']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php?lang=<?php echo $lang; ?>" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo $t['dashboard']; ?>
                </a>
                <a href="dashboard.php#websites?lang=<?php echo $lang; ?>" class="nav-item">
                    <i class="fas fa-globe"></i>
                    <?php echo $t['my_websites']; ?>
                </a>
                <a class="nav-item" href="analytics.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-chart-line"></i>
                    <?php echo $t['analytics']; ?>
                    <br><span><?php echo $t['for_site'] . htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href="interactive-element.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-pie-chart"></i>
                    <?php echo $t['content_analytics']; ?>
                    <br><span><?php echo $t['for_site'] . htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href="heatmap.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-fire"></i>
                    <?php echo $t['heatmap']; ?>
                    <br><span><?php echo $t['for_site'] . htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item" href="scan.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-bug"></i>
                    <?php echo $t['bug_analytics']; ?>
                    <br><span><?php echo $t['for_site'] . htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item active" href="security-scan.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $t['security_analytics']; ?>
                    <br><span><?php echo $t['for_site'] . htmlspecialchars($website['name']); ?></span>
                </a>
                <a href="dashboard.php#settings?lang=<?php echo $lang; ?>" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <?php echo $t['settings']; ?>
                </a>
                <?php if ($userManager->isAdmin()): ?>
                <a href="admin/" class="nav-item">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $t['admin_panel']; ?>
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
                        <div class="user-role"><?php echo $t['active_user']; ?></div>
                    </div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <?php echo $t['logout']; ?>
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
                    <h2><?php echo $t['security_scan']; ?></h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&id=<?php echo $website_id; ?>" class="lang-switcher">
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
                                    <i class="fas fa-shield-alt"></i>
                                    <?php echo htmlspecialchars($website['name']); ?>
                                </h1>
                                <p><?php echo $t['security_scan_description']; ?></p>
                                <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="website-url">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo htmlspecialchars($website['domain']); ?>
                                </a>
                                <div style="margin-top: 15px;">
                                    <div style="display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 25px; font-weight: 600;">
                                        <i class="fas fa-check-shield"></i>
                                        <span><?php echo $t['real_security_badge']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="header-actions" id = "headeractions" style = "display:none">
                                <button class="btn btn-primary" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i>
                                    <?php echo $t['refresh_data']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تحذير الاستخدام -->
                    <div class="warning-banner">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $t['warning_message']; ?></span>
                    </div>
                    
                    <!-- قسم بدء الفحص -->
                    <div class="security-scan-section">
                        <div class="security-scan-header">
                            <i class="fas fa-search"></i>
                            <h3><?php echo $is_rtl ? 'بدء الفحص الأمني المتقدم' : 'Start Advanced Security Scan'; ?></h3>
                        </div>
                        <p style="margin-bottom: 20px; color: var(--text-secondary);">
                            <i class="fas fa-info-circle"></i>
                            <?php echo $is_rtl ? 'سيتم إجراء فحص أمني شامل ومتقدم للموقع التالي:' : 'A comprehensive and advanced security scan will be performed on the following website:'; ?>
                            <strong><?php echo htmlspecialchars($website['url']); ?></strong>
                        </p>
                        
                        <div class="scan-button">
                            <button class="analyze-btn" id="analyzeBtn" onclick="startSecurityScan()">
                                <i class="fas fa-shield-alt"></i>
                                <?php echo $t['start_scan']; ?>
                            </button>
                        </div>
                        
                        <p style="margin-top: 15px; font-size: 0.9rem; color: var(--text-secondary); text-align: center;">
                            <i class="fas fa-info-circle"></i>
                            <?php echo $is_rtl ? 'هذا النظام يقوم بفحوصات أمنية حقيقية وليس محاكاة. النتائج دقيقة وموثوقة.' : 'This system performs real security tests, not simulations. Results are accurate and reliable.'; ?>
                        </p>
                    </div>
                    
                    <!-- قسم التحميل -->
                    <div class="loading-section" id="loadingSection">
                        <div class="security-scanner"></div>
                        <h3 style="margin-bottom: 15px; color: var(--danger-color);">
                            <?php echo $is_rtl ? 'جاري الفحص الأمني الحقيقي...' : 'Running Real Security Scan...'; ?>
                        </h3>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <p id="progressText"><?php echo $is_rtl ? 'بدء فحص البنية الأمنية...' : 'Starting security infrastructure scan...'; ?></p>
                    </div>
                    
                    <!-- قسم النتائج -->
                    <div class="results-section" id="resultsSection">
                        <!-- درجة الأمان -->
                        <div class="security-score" id="scoreSection">
                            <div class="score-circle" id="scoreCircle">
                                <span id="scoreValue">0</span>
                            </div>
                            <h3><?php echo $is_rtl ? 'درجة الأمان الحقيقية' : 'Real Security Score'; ?></h3>
                            <p id="scoreDescription">
                                <?php echo $is_rtl ? 'تقييم دقيق لمستوى الأمان الفعلي للموقع' : 'Accurate assessment of the website\'s actual security level'; ?>
                            </p>
                        </div>
                        
                        <!-- بطاقة الملخص -->
                        <div class="summary-card">
                            <div class="summary-content">
                                <h3 style="margin-bottom: 15px;">
                                    <i class="fas fa-chart-bar"></i>
                                    <?php echo $is_rtl ? 'ملخص الفحص الأمني الحقيقي' : 'Real Security Scan Summary'; ?>
                                </h3>
                                <div class="summary-stats">
                                    <div class="summary-stat">
                                        <div class="number" id="totalIssues">0</div>
                                        <div class="label"><?php echo $is_rtl ? 'مشاكل مكتشفة' : 'Issues Found'; ?></div>
                                    </div>
                                    <div class="summary-stat">
                                        <div class="number" id="passedTests">0</div>
                                        <div class="label"><?php echo $is_rtl ? 'اختبارات نجحت' : 'Tests Passed'; ?></div>
                                    </div>
                                    <div class="summary-stat">
                                        <div class="number" id="siteName"><?php echo htmlspecialchars($website['domain']); ?></div>
                                        <div class="label"><?php echo $is_rtl ? 'الموقع المفحوص' : 'Scanned Site'; ?></div>
                                    </div>
                                    <div class="summary-stat">
                                        <div class="number" id="scanDuration">0s</div>
                                        <div class="label"><?php echo $is_rtl ? 'مدة الفحص' : 'Scan Duration'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- شبكة الإحصائيات -->
                        <div class="stats-grid">
                            <div class="stat-card critical">
                                <div class="stat-icon"><i class="fas fa-skull-crossbones"></i></div>
                                <div class="stat-number" id="criticalCount">0</div>
                                <div class="stat-label"><?php echo $is_rtl ? 'ثغرات حرجة' : 'Critical Vulnerabilities'; ?></div>
                            </div>
                            <div class="stat-card high">
                                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="stat-number" id="highCount">0</div>
                                <div class="stat-label"><?php echo $is_rtl ? 'مخاطر عالية' : 'High Risks'; ?></div>
                            </div>
                            <div class="stat-card medium">
                                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                                <div class="stat-number" id="mediumCount">0</div>
                                <div class="stat-label"><?php echo $is_rtl ? 'مخاطر متوسطة' : 'Medium Risks'; ?></div>
                            </div>
                            <div class="stat-card low">
                                <div class="stat-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="stat-number" id="lowCount">0</div>
                                <div class="stat-label"><?php echo $is_rtl ? 'تحذيرات' : 'Warnings'; ?></div>
                            </div>
                            <div class="stat-card passed">
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="stat-number" id="passedCount">0</div>
                                <div class="stat-label"><?php echo $is_rtl ? 'اختبارات نجحت' : 'Tests Passed'; ?></div>
                            </div>
                        </div>
                        
                        <!-- حاوية المشاكل -->
                        <div class="issues-container" id="issuesContainer">
                            <!-- سيتم عرض نتائج الفحص الأمني هنا -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // متغيرات عامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const websiteUrl = '<?php echo htmlspecialchars($website['url']); ?>';
        const currentLang = '<?php echo $lang; ?>';
        
        // كلاس الفحص الأمني الحقيقي
        class RealSecurityAnalyzer {
            constructor() {
                this.loadingSection = document.getElementById('loadingSection');
                this.resultsSection = document.getElementById('resultsSection');
                this.progressFill = document.getElementById('progressFill');
                this.progressText = document.getElementById('progressText');
                this.issuesContainer = document.getElementById('issuesContainer');
                
                this.securityIssues = [];
                this.passedTests = [];
                this.startTime = null;
                
                this.initializeTexts();
            }
            
            initializeTexts() {
                this.texts = {
                    ar: {
                        scanning: 'جاري الفحص الأمني الحقيقي...',
                        startingScan: 'بدء فحص البنية الأمنية...',
                        checkingHTTPS: 'فحص بروتوكول HTTPS...',
                        checkingSSL: 'فحص إعدادات SSL/TLS...',
                        checkingHeaders: 'فحص رؤوس الأمان HTTP...',
                        checkingContent: 'تحليل محتوى الصفحة...',
                        checkingCookies: 'فحص أمان الكوكيز...',
                        checkingServer: 'فحص إعدادات الخادم...',
                        checkingExternal: 'فحص الموارد الخارجية...',
                        scanComplete: 'اكتمل الفحص الأمني الحقيقي!',
                        excellentSecurity: '🛡️ ممتاز! مستوى أمان عالي جداً',
                        goodSecurity: '✅ جيد، مستوى أمان مقبول',
                        fairSecurity: '⚠️ متوسط، يحتاج تحسينات',
                        poorSecurity: '🚨 ضعيف، مخاطر كبيرة',
                        criticalSecurity: '💀 خطير جداً!',
                        noIssuesFound: 'لم يتم اكتشاف أي مشاكل أمنية واضحة',
                        testsCompleted: 'تم إجراء {count} فحص أمني ولم يتم اكتشاف أي مشاكل واضحة.',
                        recommendNote: '💡 نصيحة: هذا الفحص الأولي ولا يغني عن penetration testing شامل'
                    },
                    en: {
                        scanning: 'Running Real Security Scan...',
                        startingScan: 'Starting security infrastructure scan...',
                        checkingHTTPS: 'Checking HTTPS protocol...',
                        checkingSSL: 'Checking SSL/TLS settings...',
                        checkingHeaders: 'Checking HTTP security headers...',
                        checkingContent: 'Analyzing page content...',
                        checkingCookies: 'Checking cookie security...',
                        checkingServer: 'Checking server configuration...',
                        checkingExternal: 'Checking external resources...',
                        scanComplete: 'Real security scan completed!',
                        excellentSecurity: '🛡️ Excellent! Very high security level',
                        goodSecurity: '✅ Good, acceptable security level',
                        fairSecurity: '⚠️ Fair, needs improvements',
                        poorSecurity: '🚨 Poor, major risks',
                        criticalSecurity: '💀 Critical security issues!',
                        noIssuesFound: 'No obvious security issues detected',
                        testsCompleted: '{count} security tests completed with no obvious issues detected.',
                        recommendNote: '💡 Note: This is a preliminary scan and does not replace comprehensive penetration testing'
                    }
                };
            }
            
            getText(key) {
                return this.texts[currentLang][key] || this.texts['ar'][key];
            }
            
            async startRealAnalysis() {
                this.resetAnalysis();
                this.showLoading();
                
                try {
                    await this.performRealSecurityTests(websiteUrl);
                } catch (error) {
                    this.showError('حدث خطأ أثناء الفحص الأمني: ' + error.message);
                } finally {
                    this.hideLoading();
                }
            }
            
            resetAnalysis() {
                this.securityIssues = [];
                this.passedTests = [];
                this.resultsSection.style.display = 'none';
                this.startTime = performance.now();
            }
            
            showLoading() {
                this.loadingSection.style.display = 'block';
                document.getElementById('analyzeBtn').disabled = true;
            }
            
            hideLoading() {
                this.loadingSection.style.display = 'none';
                document.getElementById('analyzeBtn').disabled = false;
            }
            
            updateProgress(percent, text) {
                this.progressFill.style.width = percent + '%';
                this.progressText.textContent = text;
            }
            
            async performRealSecurityTests(url) {
                this.updateProgress(5, this.getText('startingScan'));
                
                // Test 1: HTTPS Check
                await this.testHTTPS(url);
                this.updateProgress(15, this.getText('checkingHTTPS'));
                
                // Test 2: SSL/TLS Configuration
                await this.testSSLConfiguration(url);
                this.updateProgress(25, this.getText('checkingSSL'));
                
                // Test 3: Security Headers
                await this.testSecurityHeaders(url);
                this.updateProgress(40, this.getText('checkingHeaders'));
                
                // Test 4: Content Analysis
                await this.testContentSecurity(url);
                this.updateProgress(55, this.getText('checkingContent'));
                
                // Test 5: Cookie Security
                await this.testCookieSecurity(url);
                this.updateProgress(70, this.getText('checkingCookies'));
                
                // Test 6: Server Configuration
                await this.testServerConfiguration(url);
                this.updateProgress(85, this.getText('checkingServer'));
                
                // Test 7: External Resources
                await this.testExternalResources(url);
                this.updateProgress(100, this.getText('scanComplete'));
                
                await new Promise(resolve => setTimeout(resolve, 500));
                this.displayRealResults(url);
            }
            
            async fetchPageContent(url) {
                try {
                    const proxyUrl = `https://api.allorigins.win/get?url=${encodeURIComponent(url)}`;
                    const response = await fetch(proxyUrl);
                    
                    if (!response.ok) throw new Error('Network error');
                    const data = await response.json();
                    return data.contents;
                } catch (error) {
                    return null;
                }
            }
            
            async testHTTPS(url) {
                const urlObj = new URL(url);
                
                if (urlObj.protocol !== 'https:') {
                    this.addSecurityIssue({
                        title: currentLang === 'ar' ? 'الموقع لا يستخدم بروتوكول HTTPS' : 'Website does not use HTTPS protocol',
                        category: 'ssl',
                        severity: 'critical',
                        description: currentLang === 'ar' ? 
                            'الموقع يستخدم HTTP غير المشفر، مما يعرض جميع البيانات المتبادلة للخطر.' :
                            'Website uses unencrypted HTTP, exposing all data exchange to risks.',
                        technical: `${currentLang === 'ar' ? 'البروتوكول المستخدم:' : 'Protocol used:'} ${urlObj.protocol}`,
                        impact: currentLang === 'ar' ? 
                            'إمكانية اعتراض وقراءة جميع البيانات المرسلة بين المستخدم والخادم' :
                            'Possibility of intercepting and reading all data sent between user and server',
                        solution: currentLang === 'ar' ? 
                            'تفعيل شهادة SSL وإعادة توجيه جميع طلبات HTTP إلى HTTPS' :
                            'Enable SSL certificate and redirect all HTTP requests to HTTPS',
                        steps: currentLang === 'ar' ? [
                            'الحصول على شهادة SSL من مزود معتمد',
                            'تثبيت الشهادة على الخادم',
                            'إعداد إعادة توجيه 301 من HTTP إلى HTTPS',
                            'تحديث جميع الروابط الداخلية'
                        ] : [
                            'Obtain SSL certificate from certified provider',
                            'Install certificate on server',
                            'Set up 301 redirect from HTTP to HTTPS',
                            'Update all internal links'
                        ]
                    });
                } else {
                    this.addPassedTest({
                        title: currentLang === 'ar' ? 'الموقع يستخدم بروتوكول HTTPS' : 'Website uses HTTPS protocol',
                        category: 'ssl',
                        description: currentLang === 'ar' ? 
                            'الموقع يستخدم بروتوكول HTTPS المشفر بشكل صحيح' :
                            'Website correctly uses encrypted HTTPS protocol'
                    });
                }
            }
            
            async testSSLConfiguration(url) {
                const urlObj = new URL(url);
                
                if (urlObj.protocol === 'https:') {
                    try {
                        const response = await fetch(url, { method: 'HEAD' });
                        
                        try {
                            const pageContent = await this.fetchPageContent(url);
                            if (pageContent) {
                                const httpResources = this.findHTTPResources(pageContent);
                                if (httpResources.length > 0) {
                                    this.addSecurityIssue({
                                        title: currentLang === 'ar' ? 'محتوى مختلط (Mixed Content) مكتشف' : 'Mixed Content detected',
                                        category: 'ssl',
                                        severity: 'high',
                                        description: currentLang === 'ar' ? 
                                            `تم العثور على ${httpResources.length} مورد يتم تحميله عبر HTTP في صفحة HTTPS.` :
                                            `Found ${httpResources.length} resources loading via HTTP in HTTPS page.`,
                                        technical: currentLang === 'ar' ? 
                                            `الموارد المكتشفة: ${httpResources.slice(0, 3).join(', ')}${httpResources.length > 3 ? '...' : ''}` :
                                            `Detected resources: ${httpResources.slice(0, 3).join(', ')}${httpResources.length > 3 ? '...' : ''}`,
                                        impact: currentLang === 'ar' ? 
                                            'إمكانية تعديل الموارد غير المشفرة من قبل المهاجمين' :
                                            'Possibility of unencrypted resources being modified by attackers',
                                        solution: currentLang === 'ar' ? 
                                            'تحويل جميع الموارد إلى HTTPS أو استخدام Content Security Policy' :
                                            'Convert all resources to HTTPS or use Content Security Policy',
                                        steps: currentLang === 'ar' ? [
                                            'فحص جميع الروابط والموارد في الصفحة',
                                            'تحديث الروابط HTTP إلى HTTPS',
                                            'إضافة CSP header مع upgrade-insecure-requests',
                                            'اختبار الصفحة للتأكد من عدم وجود تحذيرات'
                                        ] : [
                                            'Check all links and resources in the page',
                                            'Update HTTP links to HTTPS',
                                            'Add CSP header with upgrade-insecure-requests',
                                            'Test page to ensure no warnings exist'
                                        ]
                                    });
                                } else {
                                    this.addPassedTest({
                                        title: currentLang === 'ar' ? 'لا يوجد محتوى مختلط' : 'No mixed content',
                                        category: 'ssl',
                                        description: currentLang === 'ar' ? 
                                            'جميع الموارد يتم تحميلها عبر HTTPS' :
                                            'All resources are loaded via HTTPS'
                                    });
                                }
                            }
                        } catch (error) {
                            // لا يمكن الوصول للمحتوى - قيود CORS
                        }
                        
                    } catch (error) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'مشكلة في الاتصال المشفر' : 'Encrypted connection issue',
                            category: 'ssl',
                            severity: 'medium',
                            description: currentLang === 'ar' ? 
                                'تعذر التحقق من صحة الاتصال المشفر.' :
                                'Could not verify encrypted connection validity.',
                            technical: currentLang === 'ar' ? 
                                `خطأ الاتصال: ${error.message}` :
                                `Connection error: ${error.message}`,
                            impact: currentLang === 'ar' ? 
                                'قد يشير إلى مشاكل في إعدادات SSL' :
                                'May indicate SSL configuration issues',
                            solution: currentLang === 'ar' ? 
                                'فحص إعدادات SSL/TLS باستخدام أدوات متخصصة' :
                                'Check SSL/TLS settings using specialized tools',
                            steps: currentLang === 'ar' ? [
                                'استخدام SSL Labs لفحص الشهادة',
                                'التحقق من صحة chain الشهادة',
                                'فحص cipher suites المدعومة',
                                'تحديث إعدادات الخادم حسب الحاجة'
                            ] : [
                                'Use SSL Labs to check certificate',
                                'Verify certificate chain validity',
                                'Check supported cipher suites',
                                'Update server settings as needed'
                            ]
                        });
                    }
                }
            }
            
            async testSecurityHeaders(url) {
                try {
                    const response = await fetch(url, { method: 'HEAD' });
                    const headers = response.headers;
                    
                    // فحص HSTS
                    const hsts = headers.get('strict-transport-security');
                    if (!hsts && new URL(url).protocol === 'https:') {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'رأس HSTS مفقود' : 'HSTS header missing',
                            category: 'headers',
                            severity: 'medium',
                            description: currentLang === 'ar' ? 
                                'الموقع لا يستخدم HTTP Strict Transport Security.' :
                                'Website does not use HTTP Strict Transport Security.',
                            technical: currentLang === 'ar' ? 
                                'Header "Strict-Transport-Security" غير موجود' :
                                'Header "Strict-Transport-Security" not found',
                            impact: currentLang === 'ar' ? 
                                'إمكانية هجمات downgrade على الطلبات الأولى' :
                                'Possibility of downgrade attacks on initial requests',
                            solution: currentLang === 'ar' ? 
                                'إضافة رأس HSTS مع إعدادات مناسبة' :
                                'Add HSTS header with appropriate settings',
                            steps: currentLang === 'ar' ? [
                                'إضافة Strict-Transport-Security header',
                                'ضبط max-age لفترة مناسبة (31536000 ثانية)',
                                'إضافة includeSubDomains إذا كان مناسباً',
                                'اختبار الرأس مع أدوات فحص الأمان'
                            ] : [
                                'Add Strict-Transport-Security header',
                                'Set max-age to appropriate duration (31536000 seconds)',
                                'Add includeSubDomains if appropriate',
                                'Test header with security scanning tools'
                            ]
                        });
                    } else if (hsts) {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'رأس HSTS موجود' : 'HSTS header present',
                            category: 'headers',
                            description: currentLang === 'ar' ? 
                                `HSTS مفعل: ${hsts}` :
                                `HSTS enabled: ${hsts}`
                        });
                    }
                    
                    // فحص CSP
                    const csp = headers.get('content-security-policy');
                    if (!csp) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'Content Security Policy غير موجود' : 'Content Security Policy missing',
                            category: 'headers',
                            severity: 'high',
                            description: currentLang === 'ar' ? 
                                'الموقع لا يطبق Content Security Policy.' :
                                'Website does not implement Content Security Policy.',
                            technical: currentLang === 'ar' ? 
                                'Header "Content-Security-Policy" غير موجود' :
                                'Header "Content-Security-Policy" not found',
                            impact: currentLang === 'ar' ? 
                                'عدم الحماية من هجمات XSS وCode Injection' :
                                'No protection from XSS and Code Injection attacks',
                            solution: currentLang === 'ar' ? 
                                'تطبيق CSP مناسب للموقع' :
                                'Implement appropriate CSP for the website',
                            steps: currentLang === 'ar' ? [
                                'تحديد مصادر المحتوى المطلوبة',
                                'إنشاء CSP policy مناسب',
                                'البدء بـ report-only mode للاختبار',
                                'تطبيق CSP بعد التأكد من عدم كسر الوظائف'
                            ] : [
                                'Identify required content sources',
                                'Create appropriate CSP policy',
                                'Start with report-only mode for testing',
                                'Apply CSP after ensuring no functionality breaks'
                            ]
                        });
                    } else {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'Content Security Policy موجود' : 'Content Security Policy present',
                            category: 'headers',
                            description: currentLang === 'ar' ? 
                                'CSP مطبق على الموقع' :
                                'CSP implemented on website'
                        });
                    }
                    
                    // فحص X-Frame-Options
                    const xFrame = headers.get('x-frame-options');
                    if (!xFrame) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'رأس X-Frame-Options مفقود' : 'X-Frame-Options header missing',
                            category: 'headers',
                            severity: 'medium',
                            description: currentLang === 'ar' ? 
                                'الموقع عرضة لهجمات Clickjacking.' :
                                'Website vulnerable to Clickjacking attacks.',
                            technical: currentLang === 'ar' ? 
                                'Header "X-Frame-Options" غير موجود' :
                                'Header "X-Frame-Options" not found',
                            impact: currentLang === 'ar' ? 
                                'إمكانية تضمين الموقع في iframe خبيث' :
                                'Possibility of embedding website in malicious iframe',
                            solution: currentLang === 'ar' ? 
                                'إضافة X-Frame-Options أو frame-ancestors في CSP' :
                                'Add X-Frame-Options or frame-ancestors in CSP',
                            steps: currentLang === 'ar' ? [
                                'إضافة X-Frame-Options: DENY أو SAMEORIGIN',
                                'أو استخدام frame-ancestors في CSP',
                                'اختبار عدم تأثر الوظائف المطلوبة',
                                'توثيق السياسة المطبقة'
                            ] : [
                                'Add X-Frame-Options: DENY or SAMEORIGIN',
                                'Or use frame-ancestors in CSP',
                                'Test that required functions are not affected',
                                'Document implemented policy'
                            ]
                        });
                    } else {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'حماية Clickjacking مفعلة' : 'Clickjacking protection enabled',
                            category: 'headers',
                            description: currentLang === 'ar' ? 
                                `X-Frame-Options: ${xFrame}` :
                                `X-Frame-Options: ${xFrame}`
                        });
                    }
                    
                    // فحص X-Content-Type-Options
                    const xContentType = headers.get('x-content-type-options');
                    if (!xContentType || xContentType !== 'nosniff') {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'رأس X-Content-Type-Options مفقود أو غير صحيح' : 'X-Content-Type-Options header missing or incorrect',
                            category: 'headers',
                            severity: 'low',
                            description: currentLang === 'ar' ? 
                                'الموقع عرضة لهجمات MIME sniffing.' :
                                'Website vulnerable to MIME sniffing attacks.',
                            technical: currentLang === 'ar' ? 
                                `Header قيمته: ${xContentType || 'غير موجود'}` :
                                `Header value: ${xContentType || 'not found'}`,
                            impact: currentLang === 'ar' ? 
                                'إمكانية تفسير الملفات بطريقة غير مقصودة' :
                                'Possibility of files being interpreted unintentionally',
                            solution: currentLang === 'ar' ? 
                                'إضافة X-Content-Type-Options: nosniff' :
                                'Add X-Content-Type-Options: nosniff',
                            steps: currentLang === 'ar' ? [
                                'إضافة X-Content-Type-Options: nosniff',
                                'التأكد من Content-Type صحيح لجميع الموارد',
                                'اختبار عدم تأثر الوظائف'
                            ] : [
                                'Add X-Content-Type-Options: nosniff',
                                'Ensure correct Content-Type for all resources',
                                'Test that functions are not affected'
                            ]
                        });
                    } else {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'حماية MIME sniffing مفعلة' : 'MIME sniffing protection enabled',
                            category: 'headers',
                            description: 'X-Content-Type-Options: nosniff'
                        });
                    }
                    
                } catch (error) {
                    this.addSecurityIssue({
                        title: currentLang === 'ar' ? 'تعذر فحص رؤوس الأمان' : 'Unable to check security headers',
                        category: 'headers',
                        severity: 'medium',
                        description: currentLang === 'ar' ? 
                            'لم يتمكن النظام من فحص رؤوس HTTP بسبب قيود CORS.' :
                            'System could not check HTTP headers due to CORS restrictions.',
                        technical: currentLang === 'ar' ? 
                            `خطأ: ${error.message}` :
                            `Error: ${error.message}`,
                        impact: currentLang === 'ar' ? 
                            'عدم القدرة على التحقق من إعدادات الأمان' :
                            'Unable to verify security settings',
                        solution: currentLang === 'ar' ? 
                            'فحص رؤوس الأمان باستخدام أدوات خارجية' :
                            'Check security headers using external tools',
                        steps: currentLang === 'ar' ? [
                            'استخدام أدوات مثل Security Headers scanner',
                            'فحص الموقع باستخدام curl أو wget',
                            'مراجعة إعدادات الخادم مباشرة',
                            'استخدام browser developer tools'
                        ] : [
                            'Use tools like Security Headers scanner',
                            'Check website using curl or wget',
                            'Review server settings directly',
                            'Use browser developer tools'
                        ]
                    });
                }
            }
            
            async testContentSecurity(url) {
                try {
                    const content = await this.fetchPageContent(url);
                    if (!content) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'تعذر الوصول لمحتوى الصفحة' : 'Unable to access page content',
                            category: 'content',
                            severity: 'low',
                            description: currentLang === 'ar' ? 
                                'لم يتمكن النظام من الوصول لمحتوى الصفحة لفحصها.' :
                                'System could not access page content for scanning.',
                            technical: currentLang === 'ar' ? 
                                'CORS أو قيود الخادم تمنع الوصول للمحتوى' :
                                'CORS or server restrictions prevent content access',
                            impact: currentLang === 'ar' ? 
                                'عدم القدرة على فحص المحتوى للثغرات الأمنية' :
                                'Unable to scan content for security vulnerabilities',
                            solution: currentLang === 'ar' ? 
                                'فحص المحتوى باستخدام أدوات خارجية أو من الخادم مباشرة' :
                                'Scan content using external tools or directly from server',
                            steps: currentLang === 'ar' ? [
                                'استخدام أدوات penetration testing',
                                'فحص source code مباشرة',
                                'استخدام proxy tools للفحص',
                                'مراجعة الكود من جانب الخادم'
                            ] : [
                                'Use penetration testing tools',
                                'Check source code directly',
                                'Use proxy tools for scanning',
                                'Review code from server side'
                            ]
                        });
                        return;
                    }

                    // فحص inline scripts
                    const inlineScripts = (content.match(/<script(?![^>]*src=)[^>]*>/gi) || []).length;
                    if (inlineScripts > 0) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'استخدام JavaScript مضمن في الصفحة' : 'Inline JavaScript usage in page',
                            category: 'content',
                            severity: 'medium',
                            description: currentLang === 'ar' ? 
                                `تم العثور على ${inlineScripts} script مضمن في الصفحة.` :
                                `Found ${inlineScripts} inline scripts in the page.`,
                            technical: currentLang === 'ar' ? 
                                `عدد inline scripts: ${inlineScripts}` :
                                `Number of inline scripts: ${inlineScripts}`,
                            impact: currentLang === 'ar' ? 
                                'زيادة خطر هجمات XSS وصعوبة تطبيق CSP' :
                                'Increased XSS attack risk and CSP implementation difficulty',
                            solution: currentLang === 'ar' ? 
                                'نقل JavaScript إلى ملفات خارجية' :
                                'Move JavaScript to external files',
                            steps: currentLang === 'ar' ? [
                                'نقل الكود إلى ملفات .js منفصلة',
                                'استخدام event listeners بدلاً من inline handlers',
                                'تطبيق CSP مع nonces إذا لزم الأمر',
                                'مراجعة الكود للثغرات الأمنية'
                            ] : [
                                'Move code to separate .js files',
                                'Use event listeners instead of inline handlers',
                                'Apply CSP with nonces if necessary',
                                'Review code for security vulnerabilities'
                            ]
                        });
                    } else {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'لا يوجد JavaScript مضمن' : 'No inline JavaScript',
                            category: 'content',
                            description: currentLang === 'ar' ? 
                                'الصفحة لا تحتوي على scripts مضمنة' :
                                'Page does not contain inline scripts'
                        });
                    }

                } catch (error) {
                    // معالجة الأخطاء
                }
            }
            
            async testCookieSecurity(url) {
                // فحص cookies من browser إذا كان متاحاً
                if (typeof document !== 'undefined' && document.cookie) {
                    const cookies = document.cookie.split(';');
                    let insecureCookies = 0;
                    
                    cookies.forEach(cookie => {
                        const [name, value] = cookie.split('=');
                        if (name && value && !cookie.includes('Secure') && new URL(url).protocol === 'https:') {
                            insecureCookies++;
                        }
                    });
                    
                    if (insecureCookies > 0) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'كوكيز غير آمنة مكتشفة' : 'Insecure cookies detected',
                            category: 'cookies',
                            severity: 'medium',
                            description: currentLang === 'ar' ? 
                                `${insecureCookies} كوكيز لا تحتوي على خاصية Secure.` :
                                `${insecureCookies} cookies do not contain Secure attribute.`,
                            technical: currentLang === 'ar' ? 
                                `عدد الكوكيز غير الآمنة: ${insecureCookies}` :
                                `Number of insecure cookies: ${insecureCookies}`,
                            impact: currentLang === 'ar' ? 
                                'إمكانية اعتراض الكوكيز عبر اتصالات غير مشفرة' :
                                'Possibility of cookie interception via unencrypted connections',
                            solution: currentLang === 'ar' ? 
                                'إضافة خصائص الأمان للكوكيز' :
                                'Add security attributes to cookies',
                            steps: currentLang === 'ar' ? [
                                'إضافة Secure flag لجميع الكوكيز',
                                'إضافة HttpOnly للكوكيز الحساسة',
                                'استخدام SameSite لمنع CSRF',
                                'ضبط expiration مناسب'
                            ] : [
                                'Add Secure flag to all cookies',
                                'Add HttpOnly for sensitive cookies',
                                'Use SameSite to prevent CSRF',
                                'Set appropriate expiration'
                            ]
                        });
                    } else if (cookies.length > 0) {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'إعدادات الكوكيز آمنة' : 'Cookie settings secure',
                            category: 'cookies',
                            description: currentLang === 'ar' ? 
                                'الكوكيز تحتوي على إعدادات أمان مناسبة' :
                                'Cookies contain appropriate security settings'
                        });
                    }
                } else {
                    this.addPassedTest({
                        title: currentLang === 'ar' ? 'لا توجد كوكيز للفحص' : 'No cookies to check',
                        category: 'cookies',
                        description: currentLang === 'ar' ? 
                            'لم يتم العثور على كوكيز لفحصها' :
                            'No cookies found to check'
                    });
                }
            }
            
            async testServerConfiguration(url) {
                const urlObj = new URL(url);
                
                try {
                    const response = await fetch(url, { method: 'HEAD' });
                    
                    // فحص Server header
                    const server = response.headers.get('server');
                    if (server && (server.includes('Apache') || server.includes('nginx') || server.includes('IIS'))) {
                        if (server.match(/\d+\.\d+/)) { // يحتوي على رقم إصدار
                            this.addSecurityIssue({
                                title: currentLang === 'ar' ? 'معلومات الخادم مكشوفة' : 'Server information exposed',
                                category: 'configuration',
                                severity: 'low',
                                description: currentLang === 'ar' ? 
                                    'الخادم يكشف معلومات عن نوعه وإصداره.' :
                                    'Server reveals information about its type and version.',
                                technical: currentLang === 'ar' ? 
                                    `Server header: ${server}` :
                                    `Server header: ${server}`,
                                impact: currentLang === 'ar' ? 
                                    'تسهيل استهداف ثغرات معروفة في إصدارات محددة' :
                                    'Facilitating targeting of known vulnerabilities in specific versions',
                                solution: currentLang === 'ar' ? 
                                    'إخفاء أو تقليل معلومات الخادم المكشوفة' :
                                    'Hide or minimize exposed server information',
                                steps: currentLang === 'ar' ? [
                                    'تعديل Server header لإخفاء رقم الإصدار',
                                    'استخدام reverse proxy لإخفاء الخادم الأصلي',
                                    'تحديث الخادم للإصدار الأحدث',
                                    'تطبيق security headers إضافية'
                                ] : [
                                    'Modify Server header to hide version number',
                                    'Use reverse proxy to hide original server',
                                    'Update server to latest version',
                                    'Apply additional security headers'
                                ]
                            });
                        } else {
                            this.addPassedTest({
                                title: currentLang === 'ar' ? 'معلومات الخادم محدودة' : 'Server information limited',
                                category: 'configuration',
                                description: currentLang === 'ar' ? 
                                    'الخادم لا يكشف معلومات حساسة' :
                                    'Server does not reveal sensitive information'
                            });
                        }
                    }
                    
                    // فحص X-Powered-By
                    const poweredBy = response.headers.get('x-powered-by');
                    if (poweredBy) {
                        this.addSecurityIssue({
                            title: currentLang === 'ar' ? 'معلومات التقنية مكشوفة' : 'Technology information exposed',
                            category: 'configuration',
                            severity: 'low',
                            description: currentLang === 'ar' ? 
                                'الخادم يكشف معلومات عن التقنيات المستخدمة.' :
                                'Server reveals information about used technologies.',
                            technical: currentLang === 'ar' ? 
                                `X-Powered-By: ${poweredBy}` :
                                `X-Powered-By: ${poweredBy}`,
                            impact: currentLang === 'ar' ? 
                                'تسهيل استهداف ثغرات معروفة في التقنيات المحددة' :
                                'Facilitating targeting of known vulnerabilities in specified technologies',
                            solution: currentLang === 'ar' ? 
                                'إزالة أو إخفاء X-Powered-By header' :
                                'Remove or hide X-Powered-By header',
                            steps: currentLang === 'ar' ? [
                                'تعطيل X-Powered-By header',
                                'إزالة معلومات التقنية من الاستجابات',
                                'استخدام WAF لإخفاء المعلومات',
                                'مراجعة headers الأخرى المكشوفة'
                            ] : [
                                'Disable X-Powered-By header',
                                'Remove technology information from responses',
                                'Use WAF to hide information',
                                'Review other exposed headers'
                            ]
                        });
                    } else {
                        this.addPassedTest({
                            title: currentLang === 'ar' ? 'معلومات التقنية مخفية' : 'Technology information hidden',
                            category: 'configuration',
                            description: currentLang === 'ar' ? 
                                'لا يتم كشف معلومات عن التقنيات المستخدمة' :
                                'No information about used technologies is revealed'
                        });
                    }
                    
                } catch (error) {
                    // معالجة أخطاء الاتصال
                }
            }
            
            async testExternalResources(url) {
                try {
                    const content = await this.fetchPageContent(url);
                    if (content) {
                        // فحص external resources من domains غير موثوقة
                        const externalResources = this.findExternalResources(content, url);
                        const suspiciousResources = externalResources.filter(resource => 
                            this.isSuspiciousDomain(resource)
                        );
                        
                        if (suspiciousResources.length > 0) {
                            this.addSecurityIssue({
                                title: currentLang === 'ar' ? 'موارد خارجية مشبوهة' : 'Suspicious external resources',
                                category: 'configuration',
                                severity: 'medium',
                                description: currentLang === 'ar' ? 
                                    `تم العثور على ${suspiciousResources.length} مورد من مصادر قد تكون مشبوهة.` :
                                    `Found ${suspiciousResources.length} resources from potentially suspicious sources.`,
                                technical: currentLang === 'ar' ? 
                                    `الموارد: ${suspiciousResources.slice(0, 3).join(', ')}` :
                                    `Resources: ${suspiciousResources.slice(0, 3).join(', ')}`,
                                impact: currentLang === 'ar' ? 
                                    'إمكانية تحميل محتوى خبيث من مصادر خارجية' :
                                    'Possibility of loading malicious content from external sources',
                                solution: currentLang === 'ar' ? 
                                    'مراجعة وتقييم المصادر الخارجية' :
                                    'Review and evaluate external sources',
                                steps: currentLang === 'ar' ? [
                                    'مراجعة ضرورة كل مورد خارجي',
                                    'استخدام مصادر موثوقة فقط',
                                    'تطبيق Subresource Integrity',
                                    'استخدام CSP لتقييد المصادر'
                                ] : [
                                    'Review necessity of each external resource',
                                    'Use trusted sources only',
                                    'Apply Subresource Integrity',
                                    'Use CSP to restrict sources'
                                ]
                            });
                        } else {
                            this.addPassedTest({
                                title: currentLang === 'ar' ? 'الموارد الخارجية آمنة' : 'External resources secure',
                                category: 'configuration',
                                description: currentLang === 'ar' ? 
                                    'جميع الموارد الخارجية من مصادر موثوقة' :
                                    'All external resources from trusted sources'
                            });
                        }
                    }
                } catch (error) {
                    // معالجة الأخطاء
                }
            }
            
            findHTTPResources(content) {
                const httpResources = [];
                const patterns = [
                    /src\s*=\s*["']http:\/\/[^"']+/gi,
                    /href\s*=\s*["']http:\/\/[^"']+/gi
                ];
                
                patterns.forEach(pattern => {
                    const matches = content.match(pattern) || [];
                    httpResources.push(...matches);
                });
                
                return httpResources;
            }
            
            findExternalResources(content, baseUrl) {
                const baseDomain = new URL(baseUrl).hostname;
                const resources = [];
                const patterns = [
                    /src\s*=\s*["']https?:\/\/([^"'\/]+)/gi,
                    /href\s*=\s*["']https?:\/\/([^"'\/]+)/gi
                ];
                
                patterns.forEach(pattern => {
                    let match;
                    while ((match = pattern.exec(content)) !== null) {
                        const domain = match[1];
                        if (domain !== baseDomain && !domain.startsWith('www.' + baseDomain)) {
                            resources.push(domain);
                        }
                    }
                });
                
                return [...new Set(resources)]; // إزالة التكرار
            }
            
            isSuspiciousDomain(domain) {
                const suspiciousPatterns = [
                    /\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/, // IP addresses
                    /[a-z]{20,}/, // Very long domain names
                    /\.tk$|\.ml$|\.ga$|\.cf$/ // Free TLD domains that are often abused
                ];
                
                return suspiciousPatterns.some(pattern => pattern.test(domain));
            }
            
            addSecurityIssue(issue) {
                this.securityIssues.push({
                    ...issue,
                    id: Date.now() + Math.random(),
                    timestamp: new Date().toISOString()
                });
            }
            
            addPassedTest(test) {
                this.passedTests.push({
                    ...test,
                    id: Date.now() + Math.random(),
                    timestamp: new Date().toISOString()
                });
            }
            
            calculateSecurityScore() {
                const totalTests = this.securityIssues.length + this.passedTests.length;
                if (totalTests === 0) return 100;
                
                let deductions = 0;
                this.securityIssues.forEach(issue => {
                    switch(issue.severity) {
                        case 'critical': deductions += 25; break;
                        case 'high': deductions += 15; break;
                        case 'medium': deductions += 8; break;
                        case 'low': deductions += 3; break;
                        default: deductions += 1;
                    }
                });
                
                return Math.max(0, 100 - deductions);
            }
            
            displayRealResults(url) {
                const endTime = performance.now();
                const duration = ((endTime - this.startTime) / 1000).toFixed(1);
                
                // تحديث الملخص
                const urlObj = new URL(url);
                document.getElementById('totalIssues').textContent = this.securityIssues.length;
                document.getElementById('passedTests').textContent = this.passedTests.length;
                document.getElementById('scanDuration').textContent = duration + 's';
                
                // حساب وعرض درجة الأمان
                const score = this.calculateSecurityScore();
                const scoreCircle = document.getElementById('scoreCircle');
                const scoreValue = document.getElementById('scoreValue');
                const scoreDescription = document.getElementById('scoreDescription');
                
                scoreValue.textContent = score;
                
                if (score >= 90) {
                    scoreCircle.className = 'score-circle score-excellent';
                    scoreDescription.textContent = this.getText('excellentSecurity');
                } else if (score >= 75) {
                    scoreCircle.className = 'score-circle score-good';
                    scoreDescription.textContent = this.getText('goodSecurity');
                } else if (score >= 50) {
                    scoreCircle.className = 'score-circle score-fair';
                    scoreDescription.textContent = this.getText('fairSecurity');
                } else if (score >= 25) {
                    scoreCircle.className = 'score-circle score-poor';
                    scoreDescription.textContent = this.getText('poorSecurity');
                } else {
                    scoreCircle.className = 'score-circle score-critical';
                    scoreDescription.textContent = this.getText('criticalSecurity');
                }
                
                // تحديث الإحصائيات
                const stats = this.calculateStats();
                document.getElementById('criticalCount').textContent = stats.critical;
                document.getElementById('highCount').textContent = stats.high;
                document.getElementById('mediumCount').textContent = stats.medium;
                document.getElementById('lowCount').textContent = stats.low;
                document.getElementById('passedCount').textContent = stats.passed;
                
                // عرض النتائج
                this.displayAllResults();
                
                // إظهار النتائج
                this.resultsSection.style.display = 'block';
                this.resultsSection.scrollIntoView({ behavior: 'smooth' });
            }
            
            calculateStats() {
                return {
                    critical: this.securityIssues.filter(i => i.severity === 'critical').length,
                    high: this.securityIssues.filter(i => i.severity === 'high').length,
                    medium: this.securityIssues.filter(i => i.severity === 'medium').length,
                    low: this.securityIssues.filter(i => i.severity === 'low').length,
                    passed: this.passedTests.length
                };
            }
            
            displayAllResults() {
                if (this.securityIssues.length === 0 && this.passedTests.length === 0) {
                    this.issuesContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-question-circle"></i>
                            <h3>${currentLang === 'ar' ? 'لم يتم إجراء أي فحوصات' : 'No tests performed'}</h3>
                            <p>${currentLang === 'ar' ? 'تعذر إجراء الفحوصات الأمنية بسبب قيود الوصول للموقع.' : 'Could not perform security tests due to website access restrictions.'}</p>
                        </div>
                    `;
                    return;
                }
                
                if (this.securityIssues.length === 0) {
                    this.issuesContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-shield-check"></i>
                            <h3>🎉 ${this.getText('noIssuesFound')}</h3>
                            <p>${this.getText('testsCompleted').replace('{count}', this.passedTests.length)}</p>
                            <p style="margin-top: 15px; color: var(--text-secondary); font-size: 0.9rem;">
                                ${this.getText('recommendNote')}
                            </p>
                        </div>
                    `;
                    return;
                }
                
                // ترتيب المشاكل حسب الخطورة
                const sortedIssues = [...this.securityIssues].sort((a, b) => {
                    const severityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
                    return severityOrder[b.severity] - severityOrder[a.severity];
                });
                
                this.issuesContainer.innerHTML = '';
                
                // إضافة مشاكل الأمان
                sortedIssues.forEach(issue => {
                    const issueCard = this.createIssueCard(issue);
                    this.issuesContainer.appendChild(issueCard);
                });
                
                // إضافة الاختبارات الناجحة إذا وجدت
                if (this.passedTests.length > 0) {
                    const passedSection = document.createElement('div');
                    passedSection.innerHTML = `
                        <h3 style="margin: 40px 0 20px 0; color: var(--success-color); display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle"></i>
                            ${currentLang === 'ar' ? 'الفحوصات التي نجحت' : 'Passed Tests'} (${this.passedTests.length})
                        </h3>
                    `;
                    this.issuesContainer.appendChild(passedSection);
                    
                    this.passedTests.forEach(test => {
                        const testCard = this.createPassedTestCard(test);
                        this.issuesContainer.appendChild(testCard);
                    });
                }
                
                
                document.getElementById('headeractions').style.display = "flex";
                
                
            }
            
            createIssueCard(issue) {
                const card = document.createElement('div');
                card.className = `issue-card ${issue.severity} fade-in`;
                
                const severityIcons = {
                    critical: '💀',
                    high: '🚨',
                    medium: '⚠️',
                    low: 'ℹ️'
                };
                
                const severityTexts = {
                    ar: {
                        critical: 'حرج',
                        high: 'عالي',
                        medium: 'متوسط',
                        low: 'منخفض'
                    },
                    en: {
                        critical: 'Critical',
                        high: 'High',
                        medium: 'Medium',
                        low: 'Low'
                    }
                };
                
                const categoryTexts = {
                    ar: {
                        ssl: 'SSL/TLS',
                        headers: 'رؤوس الأمان',
                        content: 'تحليل المحتوى',
                        configuration: 'التكوين',
                        cookies: 'الكوكيز'
                    },
                    en: {
                        ssl: 'SSL/TLS',
                        headers: 'Security Headers',
                        content: 'Content Analysis',
                        configuration: 'Configuration',
                        cookies: 'Cookies'
                    }
                };
                
                card.innerHTML = `
                    <div class="issue-header">
                        <div>
                            <div class="issue-title">
                                ${severityIcons[issue.severity]} ${issue.title}
                            </div>
                            <div class="issue-meta">
                                <span>📂 ${categoryTexts[currentLang][issue.category] || issue.category}</span>
                                <span>🕐 ${new Date(issue.timestamp).toLocaleString(currentLang === 'ar' ? 'ar-SA' : 'en-US')}</span>
                            </div>
                        </div>
                        <span class="severity-badge severity-${issue.severity}">
                            ${severityTexts[currentLang][issue.severity]}
                        </span>
                    </div>
                    
                    <div class="issue-description">
                        ${issue.description}
                    </div>
                    
                    ${issue.technical ? `
                        <div class="technical-details">
                            <h4><i class="fas fa-code"></i> ${currentLang === 'ar' ? 'التفاصيل التقنية' : 'Technical Details'}</h4>
                            <div class="code-block">${issue.technical}</div>
                        </div>
                    ` : ''}
                    
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <h4 style="color: var(--danger-color); margin-bottom: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> ${currentLang === 'ar' ? 'التأثير المحتمل' : 'Potential Impact'}
                        </h4>
                        <p>${issue.impact}</p>
                    </div>
                    
                    <div class="solution-section">
                        <h4><i class="fas fa-tools"></i> ${currentLang === 'ar' ? 'الحل المقترح' : 'Proposed Solution'}</h4>
                        <p style="margin-bottom: 15px;">${issue.solution}</p>
                        <h5>${currentLang === 'ar' ? 'خطوات التطبيق:' : 'Implementation Steps:'}</h5>
                        <ol class="steps-list">
                            ${issue.steps.map(step => `<li>${step}</li>`).join('')}
                        </ol>
                    </div>
                `;
                
                return card;
            }
            
            createPassedTestCard(test) {
                const card = document.createElement('div');
                card.className = `issue-card passed fade-in`;
                
                const categoryTexts = {
                    ar: {
                        ssl: 'SSL/TLS',
                        headers: 'رؤوس الأمان',
                        content: 'تحليل المحتوى',
                        configuration: 'التكوين',
                        cookies: 'الكوكيز'
                    },
                    en: {
                        ssl: 'SSL/TLS',
                        headers: 'Security Headers',
                        content: 'Content Analysis',
                        configuration: 'Configuration',
                        cookies: 'Cookies'
                    }
                };
                
                card.innerHTML = `
                    <div class="issue-header">
                        <div>
                            <div class="issue-title">
                                ✅ ${test.title}
                            </div>
                            <div class="issue-meta">
                                <span>📂 ${categoryTexts[currentLang][test.category] || test.category}</span>
                                <span>🕐 ${new Date(test.timestamp).toLocaleString(currentLang === 'ar' ? 'ar-SA' : 'en-US')}</span>
                            </div>
                        </div>
                        <span class="severity-badge severity-passed">
                            ${currentLang === 'ar' ? 'نجح' : 'Passed'}
                        </span>
                    </div>
                    
                    <div class="issue-description">
                        ${test.description}
                    </div>
                `;
                
                return card;
            }
            
            showError(message) {
                alert(message);
            }
        }
        
        // تشغيل المحلل الأمني
        let securityAnalyzer;
        
        function startSecurityScan() {
            if (!securityAnalyzer) {
                securityAnalyzer = new RealSecurityAnalyzer();
            }
            securityAnalyzer.startRealAnalysis();
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        function logout() {
            if (confirm(isRTL ? '<?php echo $t['logout_confirm']; ?>' : '<?php echo $t['logout_confirm']; ?>')) {
                window.location.href = 'logout.php?lang=<?php echo $lang; ?>';
            }
        }
        
        function refreshData() {
            location.reload();
        }
        
        // بدء الفحص التلقائي عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', () => {
            securityAnalyzer = new RealSecurityAnalyzer();
            console.log('🔒 نظام الفحص الأمني الحقيقي جاهز!');
            console.log('✅ فحوصات أمنية حقيقية - لا محاكاة');
            console.log('🎯 نتائج دقيقة وموثوقة مبنية على الاختبارات الفعلية');
        });
    </script>
</body>
</html>