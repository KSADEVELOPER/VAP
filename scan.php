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




?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'تحليلات الأخطاء والتحسينات لـ ' . htmlspecialchars($website['name']) : 'Bug & improvements Analytics for ' . htmlspecialchars($website['name']); ?></title>
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
        
   








        .loadingin {
            text-align: center;
            padding: 30px;
            display: none;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-text {
            margin-top: 10px;
            color: #666;
        }

        .results {
            padding: 40px;
            display: none;
        }

        .summary {
            background: #e8f4fd;
            border-right: 5px solid #2196F3;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .summary h2 {
            color: #1976D2;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 4px solid;
        }

        .stat-card.errors { border-top-color: #f44336; }
        .stat-card.warnings { border-top-color: #ff9800; }
        .stat-card.improvements { border-top-color: #2196F3; }
        .stat-card.security { border-top-color: #9c27b0; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-card.errors .stat-number { color: #f44336; }
        .stat-card.warnings .stat-number { color: #ff9800; }
        .stat-card.improvements .stat-number { color: #2196F3; }
        .stat-card.security .stat-number { color: #9c27b0; }

        .issues-container {
            display: grid;
            gap: 20px;
        }

        .issue-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-right: 5px solid;
            transition: transform 0.3s ease;
        }

        .issue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .issue-card.error { border-right-color: #f44336; }
        .issue-card.warning { border-right-color: #ff9800; }
        .issue-card.improvement { border-right-color: #2196F3; }
        .issue-card.security { border-right-color: #9c27b0; }

        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .issue-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .issue-card.error .issue-title { color: #f44336; }
        .issue-card.warning .issue-title { color: #ff9800; }
        .issue-card.improvement .issue-title { color: #2196F3; }
        .issue-card.security .issue-title { color: #9c27b0; }

        .severity-badge {
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .severity-high { background: #f44336; }
        .severity-medium { background: #ff9800; }
        .severity-low { background: #4caf50; }
        .severity-info { background: #2196F3; }

        .issue-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #666;
        }

        .solution {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .solution h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .solution ul {
            list-style: none;
            padding-right: 20px;
            margin-right: 20px;
        }

        .solution li {
            margin-bottom: 10px;
            position: relative;
        }

        .solution li:before {
            content: "✓";
            color: #4caf50;
            font-weight: bold;
            position: absolute;
            right: -20px;
        }

        .custom-maintenance-btn {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .custom-maintenance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .score-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 15px;
        }

        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 8px solid rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            font-weight: bold;
            position: relative;
        }

        .score-good { border-color: #4CAF50; }
        .score-average { border-color: #FF9800; }
        .score-poor { border-color: #F44336; }

        .error-message {
            background: #ffebee;
            border-right: 4px solid #f44336;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            color: #c62828;
        }

        .cors-warning {
            background: #fff3e0;
            border-right: 4px solid #ff9800;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            color: #f57500;
        }

        @media (max-width: 768px) {
            .url-input-container {
                flex-direction: column;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
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
                <a class="nav-item" href = "Analytics.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
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
                <a class="nav-item active" href = "scan.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
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
                                    <i class="fas fa-chart-bar"></i>
                                    <?php echo htmlspecialchars($website['name']); ?>
                                </h1>
                                <p>
                                    <?php echo $is_rtl ? 'تحليلات الأخطاء والتحسينات لـ '.$website['name'] : 'Bug & improvements Analytics for '.$website['name']; ?>
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
                                <button class="btn btn-primary" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i>
                                    <?php echo $is_rtl ? 'تحديث البيانات' : 'Refresh Data'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
            <!--<div class="input-section">-->
            <!--<div class="url-input-container">-->
            <!--    <input type="url" class="url-input" id="websiteUrl" placeholder="أدخل رابط الموقع المراد تحليله (مثال: https://example.com)" />-->
            <!--    <button class="analyze-btn" id="analyzeBtn">تحليل الموقع</button>-->
            <!--</div>-->
            
            <div class="cors-warning" style="display: none;" id="corsWarning">
                <strong>⚠️ تنبيه:</strong> بسبب قيود CORS في المتصفح، قد لا يتمكن النظام من الوصول لجميع المواقع. للحصول على تحليل كامل، يُنصح بتشغيل هذا النظام على خادم منفصل.
            </div>
        </div>

        <div class="loadingin" id="loadingSection">
            <div class="spinner"></div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p class="progress-text" id="progressText">بدء التحليل...</p>
        </div>

        <div class="results" id="resultsSection">
            <div class="score-section" id="scoreSection">
                <div class="score-circle" id="scoreCircle">
                    <span id="scoreValue">0</span>
                </div>
                <h3>نتيجة التحليل الإجمالية</h3>
                <p id="scoreDescription">يتم حساب النتيجة بناءً على عدد وخطورة المشاكل المكتشفة</p>
            </div>

            <div class="summary">
                <h2>📊 ملخص التحليل</h2>
                <p id="siteName">الموقع: <span></span></p>
                <p id="analysisDate">تاريخ التحليل: <span></span></p>
                <p id="loadTime">وقت التحميل: <span></span></p>
            </div>

            <div class="stats">
                <div class="stat-card errors">
                    <div class="stat-number" id="errorsCount">0</div>
                    <div>أخطاء</div>
                </div>
                <div class="stat-card warnings">
                    <div class="stat-number" id="warningsCount">0</div>
                    <div>تحذيرات</div>
                </div>
                <div class="stat-card improvements">
                    <div class="stat-number" id="improvementsCount">0</div>
                    <div>تحسينات</div>
                </div>
                <div class="stat-card security">
                    <div class="stat-number" id="securityCount">0</div>
                    <div>أمان</div>
                </div>
            </div>

            <div class="issues-container" id="issuesContainer">
                <!-- Issues will be populated here -->
            </div>
        </div>






               

                </div>
            </div>
        </main>
    </div>
    

    <script>
        // متغيرات عامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;



          

        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        function logout() {
            if (confirm(isRTL ? 'هل أنت متأكد من تسجيل الخروج؟' : 'Are you sure you want to logout?')) {
                window.location.href = 'logout.php&lang=<?php echo $lang; ?>';
            }
        }
        
        
    
    
    
    
    
    
        const issueTemplates = {
            // SEO المحسّنة
            seo: {
                missingTitle: {
                    title: 'عنوان الصفحة مفقود',
                    type: 'error',
                    severity: 'high',
                    category: 'SEO',
                    description: 'الصفحة لا تحتوي على عنوان، مما يؤثر سلباً على ترتيبها في نتائج البحث.',
                    solution: 'إضافة عنوان وصفي وجذاب للصفحة.',
                    steps: [
                        'إضافة عنصر <title> في قسم <head>',
                        'استخدام كلمات مفتاحية مهمة في بداية العنوان',
                        'الحفاظ على طول العنوان بين 30-60 حرف',
                        'جعل العنوان فريد لكل صفحة'
                    ]
                },
                longTitle: {
                    title: 'عنوان الصفحة طويل جداً',
                    type: 'warning',
                    severity: 'medium',
                    category: 'SEO',
                    description: 'عنوان الصفحة يحتوي على {LENGTH} حرف، والأمثل هو 30-60 حرف.',
                    solution: 'تقصير عنوان الصفحة ليكون أكثر فعالية.',
                    steps: [
                        'اختصار العنوان إلى 30-60 حرف',
                        'الحفاظ على الكلمات المفتاحية المهمة',
                        'جعل العنوان واضح وجذاب',
                        'تجنب الكلمات الحشو غير المفيدة'
                    ]
                },
                missingMetaDescription: {
                    title: 'وصف الصفحة (Meta Description) مفقود',
                    type: 'warning',
                    severity: 'medium',
                    category: 'SEO',
                    description: 'الصفحة لا تحتوي على وصف meta، مما يفوت فرصة تحسين نسبة النقر في نتائج البحث.',
                    solution: 'إضافة وصف جذاب ومفيد للصفحة.',
                    steps: [
                        'إضافة meta description بطول 120-160 حرف',
                        'كتابة وصف يلخص محتوى الصفحة بوضوح',
                        'تضمين دعوة للعمل (Call to Action)',
                        'استخدام كلمات مفتاحية طبيعية ومتدفقة'
                    ]
                },
                missingH1: {
                    title: 'العنوان الرئيسي H1 مفقود',
                    type: 'warning',
                    severity: 'medium',
                    category: 'SEO',
                    description: 'الصفحة لا تحتوي على عنوان H1، مما يؤثر على فهم محركات البحث للمحتوى.',
                    solution: 'إضافة عنوان H1 واضح ووصفي.',
                    steps: [
                        'إضافة عنوان H1 واحد فقط لكل صفحة',
                        'جعل H1 يصف محتوى الصفحة بدقة',
                        'استخدام كلمات مفتاحية مناسبة',
                        'ترتيب العناوين بتسلسل منطقي (H1, H2, H3...)'
                    ]
                },
                multipleH1: {
                    title: 'عدة عناوين H1 في الصفحة',
                    type: 'warning',
                    severity: 'low',
                    category: 'SEO',
                    description: 'الصفحة تحتوي على {COUNT} عناوين H1، والأفضل استخدام عنوان واحد فقط.',
                    solution: 'توحيد عناوين H1 أو تحويل الإضافية إلى H2.',
                    steps: [
                        'الحفاظ على H1 واحد فقط كعنوان رئيسي',
                        'تحويل العناوين الإضافية إلى H2 أو H3',
                        'ترتيب العناوين بشكل هرمي منطقي',
                        'التأكد من وضوح بنية المحتوى'
                    ]
                },
                missingCanonical: {
                    title: 'رابط Canonical مفقود',
                    type: 'improvement',
                    severity: 'low',
                    category: 'SEO',
                    description: 'عدم وجود رابط canonical قد يؤدي إلى مشاكل المحتوى المكرر.',
                    solution: 'إضافة رابط canonical للصفحة.',
                    steps: [
                        'إضافة <link rel="canonical" href="URL"> في <head>',
                        'استخدام الرابط الكامل والصحيح',
                        'التأكد من توافق canonical مع المحتوى',
                        'تطبيقه على جميع الصفحات بانتظام'
                    ]
                },
                missingOpenGraph: {
                    title: 'وسوم Open Graph مفقودة',
                    type: 'improvement',
                    severity: 'low',
                    category: 'SEO',
                    description: 'عدم وجود وسوم Open Graph يؤثر على مظهر الروابط في الشبكات الاجتماعية.',
                    solution: 'إضافة وسوم Open Graph الأساسية.',
                    steps: [
                        'إضافة og:title و og:description',
                        'تضمين og:image بحجم 1200×630 بكسل',
                        'إضافة og:url و og:type',
                        'اختبار المشاركة في منصات مختلفة'
                    ]
                }
            },

            // الأمان المحسّن
            security: {
                noHTTPS: {
                    title: 'الموقع لا يستخدم HTTPS',
                    type: 'error',
                    severity: 'high',
                    category: 'الأمان',
                    description: 'الموقع يستخدم HTTP غير الآمن بدلاً من HTTPS المشفر.',
                    solution: 'تفعيل شهادة SSL وإعادة توجيه جميع الطلبات إلى HTTPS.',
                    steps: [
                        'الحصول على شهادة SSL من مزود معتمد',
                        'تثبيت الشهادة على الخادم بشكل صحيح',
                        'إعداد إعادة توجيه 301 من HTTP إلى HTTPS',
                        'تحديث جميع الروابط الداخلية لاستخدام HTTPS',
                        'إضافة HSTS headers للحماية الإضافية'
                    ]
                },
                mixedContent: {
                    title: 'محتوى مختلط (Mixed Content)',
                    type: 'error',
                    severity: 'high',
                    category: 'الأمان',
                    description: '{COUNT} مورد يتم تحميله عبر HTTP غير الآمن في صفحة HTTPS.',
                    solution: 'تحويل جميع الموارد إلى HTTPS.',
                    steps: [
                        'تحديث جميع الروابط والمصادر إلى HTTPS',
                        'استخدام روابط نسبية عند الإمكان',
                        'فحص وتحديث الموارد الخارجية',
                        'تطبيق Content Security Policy',
                        'اختبار الموقع للتأكد من عدم وجود تحذيرات'
                    ]
                },
                noCSP: {
                    title: 'عدم وجود Content Security Policy',
                    type: 'warning',
                    severity: 'medium',
                    category: 'الأمان',
                    description: 'الصفحة لا تحتوي على CSP، مما يزيد مخاطر هجمات XSS.',
                    solution: 'إضافة Content Security Policy مناسب.',
                    steps: [
                        'تحديد سياسة CSP أساسية آمنة',
                        'إضافة المصادر الموثوقة فقط',
                        'اختبار السياسة في وضع report-only أولاً',
                        'مراقبة تقارير الانتهاكات بانتظام',
                        'تحديث السياسة حسب احتياجات الموقع'
                    ]
                },
                weakCookies: {
                    title: 'إعدادات الكوكيز غير آمنة',
                    type: 'warning',
                    severity: 'medium',
                    category: 'الأمان',
                    description: 'الكوكيز تفتقر لخصائص الأمان المهمة مثل Secure و HttpOnly.',
                    solution: 'تحسين إعدادات الكوكيز لزيادة الأمان.',
                    steps: [
                        'إضافة خاصية Secure لجميع الكوكيز',
                        'تفعيل HttpOnly للكوكيز الحساسة',
                        'ضبط SameSite=Lax أو Strict حسب الحاجة',
                        'تحديد مدة انتهاء مناسبة للكوكيز',
                        'مراجعة وتنظيف الكوكيز غير المستخدمة'
                    ]
                }
            },

            // الأداء المحسّن
            performance: {
                largePageSize: {
                    title: 'حجم الصفحة كبير',
                    type: 'warning',
                    severity: 'medium',
                    category: 'الأداء',
                    description: 'حجم الصفحة {SIZE} ميجا، مما قد يؤثر على سرعة التحميل خاصة على الشبكات البطيئة.',
                    solution: 'تقليل حجم الصفحة عبر تحسين وضغط المحتوى.',
                    steps: [
                        'ضغط وتحسين الصور باستخدام تنسيقات حديثة (WebP, AVIF)',
                        'تفعيل ضغط Gzip/Brotli على الخادم',
                        'تقليل وإزالة CSS و JavaScript غير المستخدم',
                        'استخدام lazy loading للصور والموارد الثانوية',
                        'تحسين وتقليل الخطوط المستخدمة',
                        'استخدام CDN لتوزيع المحتوى جغرافياً'
                    ]
                },
                tooManyRequests: {
                    title: 'عدد كبير من الموارد الخارجية',
                    type: 'warning',
                    severity: 'medium',
                    category: 'الأداء',
                    description: 'الصفحة تحتوي على {COUNT} مورد خارجي، مما قد يبطئ التحميل.',
                    solution: 'تقليل عدد الطلبات وتحسين تحميل الموارد.',
                    steps: [
                        'دمج ملفات CSS و JavaScript المتعددة',
                        'استخدام HTTP/2 أو HTTP/3 لتحسين التوازي',
                        'إزالة المكتبات والإضافات غير المستخدمة',
                        'تطبيق lazy loading للموارد غير الحرجة',
                        'استخدام preload للموارد المهمة فقط',
                        'تطبيق caching استراتيجي للموارد'
                    ]
                },
                noLazyLoading: {
                    title: 'صور بدون lazy loading',
                    type: 'improvement',
                    severity: 'low',
                    category: 'الأداء',
                    description: '{COUNT} صورة لا تستخدم lazy loading، مما يؤثر على سرعة التحميل الأولي.',
                    solution: 'تطبيق lazy loading للصور غير المرئية في البداية.',
                    steps: [
                        'إضافة loading="lazy" للصور تحت الطي',
                        'استثناء الصور المهمة فوق الطي (above the fold)',
                        'استخدام placeholder أو blurred images أثناء التحميل',
                        'تحسين أحجام الصور للأجهزة المختلفة باستخدام srcset',
                        'اختبار الأداء على شبكات بطيئة'
                    ]
                },
                slowLCP: {
                    title: 'Largest Contentful Paint بطيء',
                    type: 'error',
                    severity: 'high',
                    category: 'الأداء',
                    description: 'أكبر عنصر محتوى يستغرق وقتاً طويلاً للظهور، مما يؤثر على تجربة المستخدم.',
                    solution: 'تحسين تحميل العناصر الرئيسية في الصفحة.',
                    steps: [
                        'تحسين الصور الرئيسية وصور البطل',
                        'إزالة أو تأجيل CSS/JS غير الحرج',
                        'استخدام preload للموارد المهمة',
                        'تحسين خادم الاستضافة وقاعدة البيانات',
                        'تطبيق server-side rendering عند الإمكان'
                    ]
                },
                highCLS: {
                    title: 'تذبذب تخطيط عالي (CLS)',
                    type: 'error',
                    severity: 'high',
                    category: 'الأداء',
                    description: 'عناصر الصفحة تتحرك أثناء التحميل مما يضر بتجربة المستخدم.',
                    solution: 'تثبيت أبعاد العناصر ومنع التحرك غير المرغوب.',
                    steps: [
                        'تحديد أبعاد الصور والفيديوهات مسبقاً',
                        'حجز مساحة للإعلانات والمحتوى الديناميكي',
                        'استخدام font-display: swap للخطوط',
                        'تجنب إدراج محتوى جديد فوق المحتوى الموجود',
                        'استخدام CSS aspect-ratio للعناصر المتجاوبة'
                    ]
                }
            },

            // إمكانية الوصول المحسّنة
            accessibility: {
                missingLang: {
                    title: 'خاصية اللغة مفقودة',
                    type: 'improvement',
                    severity: 'medium',
                    category: 'إمكانية الوصول',
                    description: 'عنصر HTML لا يحتوي على خاصية lang، مما يؤثر على قارئات الشاشة.',
                    solution: 'إضافة خاصية lang مع رمز اللغة المناسب.',
                    steps: [
                        'إضافة lang="ar" للمحتوى العربي',
                        'إضافة dir="rtl" للغات التي تُقرأ من اليمين لليسار',
                        'استخدام رموز ISO 639-1 الصحيحة للغات',
                        'تحديد lang للأقسام بلغات مختلفة داخل الصفحة',
                        'اختبار قارئات الشاشة مع اللغات المختلفة'
                    ]
                },
                missingAltText: {
                    title: 'صور بدون نص بديل (Alt)',
                    type: 'improvement',
                    severity: 'medium',
                    category: 'إمكانية الوصول',
                    description: '{COUNT} من الصور لا تحتوي على نص بديل، مما يؤثر على إمكانية الوصول.',
                    solution: 'إضافة نص بديل وصفي لجميع الصور المهمة.',
                    steps: [
                        'إضافة alt attribute وصفي لكل صورة مهمة',
                        'كتابة وصف دقيق ومفيد يصف محتوى الصورة',
                        'ترك alt فارغ (alt="") للصور الزخرفية فقط',
                        'تجنب حشو الكلمات المفتاحية في النص البديل',
                        'وصف وظيفة الصورة وليس مظهرها فقط'
                    ]
                },
                poorColorContrast: {
                    title: 'تباين ألوان ضعيف',
                    type: 'warning',
                    severity: 'medium',
                    category: 'إمكانية الوصول',
                    description: 'بعض النصوص قد لا تحقق معايير التباين اللوني المطلوبة.',
                    solution: 'تحسين تباين الألوان لتلبية معايير WCAG.',
                    steps: [
                        'استخدام أدوات فحص التباين اللوني',
                        'التأكد من نسبة تباين 4.5:1 للنصوص العادية',
                        'التأكد من نسبة تباين 3:1 للنصوص الكبيرة',
                        'تجنب الاعتماد على اللون فقط لنقل المعلومات',
                        'اختبار الموقع مع محاكي عمى الألوان'
                    ]
                },
                missingFormLabels: {
                    title: 'حقول نماذج بدون تسميات',
                    type: 'warning',
                    severity: 'medium',
                    category: 'إمكانية الوصول',
                    description: '{COUNT} حقل إدخال بدون تسمية واضحة، مما يعيق قارئات الشاشة.',
                    solution: 'ربط كل حقل إدخال بتسمية واضحة ومفهومة.',
                    steps: [
                        'استخدام عنصر <label> مع كل حقل إدخال',
                        'ربط التسمية بالحقل باستخدام for و id',
                        'استخدام aria-label كبديل عند الحاجة',
                        'كتابة تسميات واضحة ومفيدة للمستخدم',
                        'إضافة تعليمات للحقول المعقدة باستخدام aria-describedby'
                    ]
                },
                missingSkipLinks: {
                    title: 'روابط التخطي مفقودة',
                    type: 'improvement',
                    severity: 'low',
                    category: 'إمكانية الوصول',
                    description: 'لا توجد روابط تخطي للتنقل السريع للمحتوى الرئيسي.',
                    solution: 'إضافة روابط تخطي لتحسين التنقل بلوحة المفاتيح.',
                    steps: [
                        'إضافة رابط "تخطي إلى المحتوى الرئيسي" في بداية الصفحة',
                        'جعل الرابط مرئي عند التركيز عليه بـ Tab',
                        'ربط الرابط بالمحتوى الرئيسي باستخدام id',
                        'إضافة روابط تخطي إضافية للأقسام المهمة',
                        'اختبار التنقل باستخدام لوحة المفاتيح فقط'
                    ]
                }
            },

            // التوافق مع الأجهزة المحمولة
            mobile: {
                missingViewport: {
                    title: 'وسم viewport مفقود أو غير صحيح',
                    type: 'error',
                    severity: 'high',
                    category: 'الأجهزة المحمولة',
                    description: 'الصفحة لا تحتوي على وسم viewport مناسب للأجهزة المحمولة.',
                    solution: 'إضافة وسم viewport صحيح للتجاوب مع الأجهزة.',
                    steps: [
                        'إضافة <meta name="viewport" content="width=device-width, initial-scale=1">',
                        'تجنب تعطيل التكبير إلا للضرورة القصوى',
                        'اختبار العرض على أجهزة مختلفة الأحجام',
                        'التأكد من عدم وجود تمرير أفقي غير مرغوب',
                        'ضبط maximum-scale بحذر إن لزم الأمر'
                    ]
                },
                smallTouchTargets: {
                    title: 'أهداف لمس صغيرة',
                    type: 'warning',
                    severity: 'medium',
                    category: 'الأجهزة المحمولة',
                    description: 'بعض الأزرار والروابط أصغر من الحد الأدنى الموصى به للمس.',
                    solution: 'تكبير مناطق اللمس وإضافة مسافات مناسبة.',
                    steps: [
                        'جعل حجم الأزرار لا يقل عن 44×44 بكسل',
                        'إضافة مساحة كافية بين العناصر التفاعلية (8px+)',
                        'استخدام مناطق لمس أكبر من العنصر المرئي',
                        'تطبيق ردود فعل واضحة عند اللمس (:active, :focus)',
                        'اختبار سهولة الاستخدام على أجهزة حقيقية'
                    ]
                },
                notResponsive: {
                    title: 'التصميم غير متجاوب',
                    type: 'error',
                    severity: 'high',
                    category: 'الأجهزة المحمولة',
                    description: 'الموقع لا يتكيف بشكل جيد مع أحجام الشاشات المختلفة.',
                    solution: 'تطبيق تصميم متجاوب شامل.',
                    steps: [
                        'استخدام CSS Media Queries للشاشات المختلفة',
                        'تطبيق Flexbox أو CSS Grid للتخطيطات المرنة',
                        'استخدام وحدات مرنة مثل %, em, rem, vw, vh',
                        'تحسين الصور للأجهزة المختلفة باستخدام srcset',
                        'اختبار شامل على أجهزة وأحجام شاشات متنوعة'
                    ]
                }
            },

            // المحتوى والجودة
            content: {
                missingHeadingStructure: {
                    title: 'بنية العناوين غير منطقية',
                    type: 'improvement',
                    severity: 'low',
                    category: 'المحتوى',
                    description: 'ترتيب العناوين (H1, H2, H3) غير متسلسل أو منطقي.',
                    solution: 'إعادة تنظيم العناوين بشكل هرمي واضح.',
                    steps: [
                        'التأكد من وجود H1 واحد فقط لكل صفحة',
                        'ترتيب العناوين بتسلسل منطقي دون تخطي مستويات',
                        'استخدام العناوين للبنية وليس للتصميم فقط',
                        'جعل كل عنوان يصف المحتوى الذي يتبعه بدقة',
                        'اختبار البنية باستخدام أدوات قارئات الشاشة'
                    ]
                },
                brokenLinks: {
                    title: 'روابط مكسورة',
                    type: 'warning',
                    severity: 'medium',
                    category: 'المحتوى',
                    description: 'تم اكتشاف {COUNT} رابط مكسور قد يؤثر على تجربة المستخدم.',
                    solution: 'إصلاح أو إزالة الروابط المكسورة.',
                    steps: [
                        'فحص جميع الروابط الداخلية والخارجية بانتظام',
                        'إصلاح الروابط المكسورة أو إعادة توجيهها',
                        'إعداد صفحات 404 مفيدة مع روابط بديلة',
                        'استخدام أدوات مراقبة للروابط المكسورة',
                        'إعداد إعادة توجيه 301 للصفحات المنقولة'
                    ]
                },
                thinContent: {
                    title: 'محتوى ضعيف أو قليل',
                    type: 'improvement',
                    severity: 'low',
                    category: 'المحتوى',
                    description: 'الصفحة تحتوي على محتوى قليل قد لا يلبي توقعات المستخدمين.',
                    solution: 'إثراء المحتوى بمعلومات قيمة ومفيدة.',
                    steps: [
                        'زيادة عدد الكلمات إلى مستوى مناسب (300+ كلمة)',
                        'إضافة أقسام FAQ مع schema markup',
                        'تضمين أمثلة عملية وحالات دراسة',
                        'إضافة صور ومخططات توضيحية',
                        'ربط المحتوى بصفحات ذات صلة داخلياً'
                    ]
                }
            },

            // التقنيات المتقدمة
            technical: {
                missingRobotsTxt: {
                    title: 'ملف robots.txt مفقود',
                    type: 'improvement',
                    severity: 'low',
                    category: 'تقني',
                    description: 'الموقع لا يحتوي على ملف robots.txt لتوجيه محركات البحث.',
                    solution: 'إنشاء ملف robots.txt مناسب.',
                    steps: [
                        'إنشاء ملف robots.txt في المجلد الجذر',
                        'تحديد الصفحات المسموح بفهرستها والممنوعة',
                        'إضافة رابط خريطة الموقع (sitemap)',
                        'اختبار الملف باستخدام Google Search Console',
                        'مراجعة وتحديث الملف عند تغيير بنية الموقع'
                    ]
                },
                missingSitemap: {
                    title: 'خريطة الموقع مفقودة',
                    type: 'improvement',
                    severity: 'low',
                    category: 'تقني',
                    description: 'لا توجد خريطة موقع XML لمساعدة محركات البحث.',
                    solution: 'إنشاء وتقديم خريطة موقع شاملة.',
                    steps: [
                        'إنشاء sitemap.xml يحتوي على جميع الصفحات المهمة',
                        'تحديث خريطة الموقع تلقائياً عند إضافة محتوى',
                        'تقديم خريطة الموقع لمحركات البحث',
                        'إنشاء sitemaps منفصلة للصور والأخبار إن أمكن',
                        'مراقبة تقارير الفهرسة في Search Console'
                    ]
                },
                slowDatabaseQueries: {
                    title: 'استعلامات قاعدة البيانات بطيئة',
                    type: 'error',
                    severity: 'high',
                    category: 'تقني',
                    description: 'استعلامات قاعدة البيانات تستغرق وقتاً طويلاً مما يؤثر على الأداء.',
                    solution: 'تحسين استعلامات قاعدة البيانات والفهارس.',
                    steps: [
                        'تحليل الاستعلامات البطيئة باستخدام أدوات المراقبة',
                        'إضافة فهارس (indexes) للحقول المستخدمة في WHERE و JOIN',
                        'تحسين بنية الجداول وتطبيع البيانات',
                        'استخدام التخزين المؤقت للاستعلامات المتكررة',
                        'تحسين استعلامات JOIN وتجنب N+1 queries'
                    ]
                }
            },

            // قوالب عامة للأخطاء غير المتوقعة
            general: {
                corsError: {
                    title: 'تعذر الوصول للموقع للتحليل الكامل',
                    type: 'warning',
                    severity: 'medium',
                    category: 'تقني',
                    description: 'لم يتمكن النظام من الوصول لمحتوى الموقع بسبب قيود CORS أو مشاكل شبكة.',
                    solution: 'للحصول على تحليل كامل، يُنصح بتشغيل النظام على خادم منفصل.',
                    steps: [
                        'التأكد من أن الموقع يعمل بشكل طبيعي',
                        'فحص إعدادات CORS على الخادم',
                        'استخدام أدوات تحليل متخصصة أخرى',
                        'تشغيل النظام على خادم للحصول على وصول كامل',
                        'فحص الموقع محلياً أو باستخدام VPN'
                    ]
                },
                fallbackIssue: {
                    title: 'مشكلة تقنية تم اكتشافها',
                    type: 'improvement',
                    severity: 'low',
                    category: 'عام',
                    description: 'تم اكتشاف مشكلة تقنية قد تحتاج للمراجعة من قبل مطور.',
                    solution: 'يُنصح بمراجعة هذا العنصر مع مطور ويب متخصص.',
                    steps: [
                        'فحص الكود المصدري للصفحة بدقة',
                        'استخدام أدوات تطوير المتصفح للتشخيص',
                        'مراجعة console للأخطاء والتحذيرات',
                        'اختبار الوظيفة على متصفحات مختلفة',
                        'استشارة مطور ويب متخصص للحصول على حل مناسب'
                    ]
                }
            }
        };

        class WebsiteAnalyzer {
            constructor() {
                this.loadingSection = document.getElementById('loadingSection');
                this.resultsSection = document.getElementById('resultsSection');
                this.progressFill = document.getElementById('progressFill');
                this.progressText = document.getElementById('progressText');
                this.corsWarning = document.getElementById('corsWarning');
                
                this.issues = [];
                this.startTime = null;
                this.endTime = null;
                this.issueTemplates = issueTemplates;
            }

     
     async startAnalysis() {
    const url = '<?php echo htmlspecialchars($website['url']); ?>'; // استخدم رابط الموقع من PHP
    
    if (!url) {
        alert('يرجى إدخال رابط الموقع');
        return;
    }

    if (!this.isValidUrl(url)) {
        alert('يرجى إدخال رابط صحيح (مثال: https://example.com)');
        return;
    }

    this.resetAnalysis();
    this.showLoading();
    this.corsWarning.style.display = 'block';

    try {
        await this.analyzeWebsite(url);
    } catch (error) {
        this.showError('حدث خطأ أثناء تحليل الموقع: ' + error.message);
    } finally {
        this.hideLoading();
    }
}


            resetAnalysis() {
                this.issues = [];
                this.resultsSection.style.display = 'none';
                this.startTime = performance.now();
            }

            showLoading() {
                this.loadingSection.style.display = 'block';
            }

            hideLoading() {
                this.loadingSection.style.display = 'none';
            }

            updateProgress(percent, text) {
                this.progressFill.style.width = percent + '%';
                this.progressText.textContent = text;
            }

            async analyzeWebsite(url) {
                this.updateProgress(10, 'بدء التحليل...');
                
                // تحليل الأساسيات
                await this.analyzeBasics(url);
                this.updateProgress(25, 'فحص بنية الصفحة...');
                
                // محاولة تحميل الصفحة
                let pageContent = null;
                try {
                    pageContent = await this.fetchPageContent(url);
                    this.updateProgress(50, 'تحليل محتوى الصفحة...');
                    
                    // تحليل SEO
                    await this.analyzeSEO(pageContent, url);
                    this.updateProgress(60, 'فحص تحسين محركات البحث...');
                    
                    // تحليل الأداء
                    await this.analyzePerformance(pageContent, url);
                    this.updateProgress(70, 'فحص الأداء...');
                    
                    // تحليل الأمان
                    await this.analyzeSecurity(pageContent, url);
                    this.updateProgress(80, 'فحص الأمان...');
                    
                    // تحليل إمكانية الوصول
                    await this.analyzeAccessibility(pageContent);
                    this.updateProgress(85, 'فحص إمكانية الوصول...');

                    // تحليل التوافق مع الأجهزة المحمولة
                    await this.analyzeMobile(pageContent);
                    this.updateProgress(90, 'فحص التوافق مع الأجهزة المحمولة...');
                    
                } catch (error) {
                    this.updateProgress(50, 'تعذر الوصول للصفحة، استكمال التحليل الأساسي...');
                    await this.analyzeBasicsOnly(url);
                }
                
                this.updateProgress(100, 'اكتمل التحليل!');
                this.endTime = performance.now();
                
                await new Promise(resolve => setTimeout(resolve, 500));
                this.displayResults(url);
            }

            async fetchPageContent(url) {
                try {
                    // محاولة استخدام proxy للتغلب على CORS
                    const proxyUrl = `https://api.allorigins.win/get?url=${encodeURIComponent(url)}`;
                    const response = await fetch(proxyUrl);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    return data.contents;
                } catch (error) {
                    // محاولة أخرى مع proxy مختلف
                    try {
                        const corsProxy = `https://cors-anywhere.herokuapp.com/${url}`;
                        const response = await fetch(corsProxy);
                        return await response.text();
                    } catch (secondError) {
                        throw new Error('تعذر الوصول للموقع بسبب قيود CORS');
                    }
                }
            }

            async analyzeBasics(url) {
                const urlObj = new URL(url);
                
                // فحص HTTPS
                if (urlObj.protocol !== 'https:') {
                    this.addIssueFromTemplate('security', 'noHTTPS');
                }

                // فحص subdomain www
                if (!urlObj.hostname.startsWith('www.') && !urlObj.hostname.includes('api.')) {
                    this.addIssue({
                        title: 'عدم وجود توحيد النطاق',
                        type: 'improvement',
                        severity: 'low',
                        category: 'SEO',
                        description: 'قد يؤدي عدم توحيد النطاق (مع أو بدون www) إلى مشاكل SEO.',
                        solution: 'إعداد إعادة توجيه 301 لتوحيد النطاق.',
                        steps: [
                            'اختيار نمط موحد (مع أو بدون www)',
                            'إعداد إعادة توجيه 301 من النمط الآخر',
                            'تحديث الروابط الداخلية للنمط المختار',
                            'إضافة canonical URL متسق'
                        ]
                    });
                }
            }

            async analyzeBasicsOnly(url) {
                this.addIssueFromTemplate('general', 'corsError');
            }

            async analyzeSEO(content, url) {
                if (!content) return;
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(content, 'text/html');
                
                // فحص title
                const title = doc.querySelector('title');
                if (!title || !title.textContent.trim()) {
                    this.addIssueFromTemplate('seo', 'missingTitle');
                } else if (title.textContent.length > 60) {
                    this.addIssueFromTemplate('seo', 'longTitle', {
                        LENGTH: title.textContent.length
                    });
                }

                // فحص meta description
                const metaDesc = doc.querySelector('meta[name="description"]');
                if (!metaDesc || !metaDesc.getAttribute('content')?.trim()) {
                    this.addIssueFromTemplate('seo', 'missingMetaDescription');
                }

                // فحص H1
                const h1Tags = doc.querySelectorAll('h1');
                if (h1Tags.length === 0) {
                    this.addIssueFromTemplate('seo', 'missingH1');
                } else if (h1Tags.length > 1) {
                    this.addIssueFromTemplate('seo', 'multipleH1', {
                        COUNT: h1Tags.length
                    });
                }

                // فحص الصور بدون alt
                const images = doc.querySelectorAll('img');
                let imagesWithoutAlt = 0;
                images.forEach(img => {
                    if (!img.getAttribute('alt')) {
                        imagesWithoutAlt++;
                    }
                });

                if (imagesWithoutAlt > 0) {
                    this.addIssueFromTemplate('accessibility', 'missingAltText', {
                        COUNT: imagesWithoutAlt
                    });
                }

                // فحص canonical
                const canonical = doc.querySelector('link[rel="canonical"]');
                if (!canonical) {
                    this.addIssueFromTemplate('seo', 'missingCanonical');
                }

                // فحص Open Graph
                const ogTitle = doc.querySelector('meta[property="og:title"]');
                const ogDescription = doc.querySelector('meta[property="og:description"]');
                const ogImage = doc.querySelector('meta[property="og:image"]');
                
                if (!ogTitle || !ogDescription || !ogImage) {
                    this.addIssueFromTemplate('seo', 'missingOpenGraph');
                }
            }

            async analyzePerformance(content, url) {
                if (!content) return;

                const parser = new DOMParser();
                const doc = parser.parseFromString(content, 'text/html');

                // فحص حجم الصفحة
                const pageSize = new Blob([content]).size;
                if (pageSize > 2 * 1024 * 1024) { // أكبر من 2 ميجا
                    this.addIssueFromTemplate('performance', 'largePageSize', {
                        SIZE: (pageSize / 1024 / 1024).toFixed(2)
                    });
                }

                // فحص الموارد الخارجية
                const externalScripts = doc.querySelectorAll('script[src]');
                const externalStyles = doc.querySelectorAll('link[rel="stylesheet"]');
                const totalExternal = externalScripts.length + externalStyles.length;

                if (totalExternal > 10) {
                    this.addIssueFromTemplate('performance', 'tooManyRequests', {
                        COUNT: totalExternal
                    });
                }

                // فحص الصور بدون lazy loading
                const images = doc.querySelectorAll('img');
                let imagesWithoutLazy = 0;
                images.forEach(img => {
                    if (!img.getAttribute('loading') || img.getAttribute('loading') !== 'lazy') {
                        imagesWithoutLazy++;
                    }
                });

                if (imagesWithoutLazy > 3) {
                    this.addIssueFromTemplate('performance', 'noLazyLoading', {
                        COUNT: imagesWithoutLazy
                    });
                }

                // فحص inline styles
                const inlineStyles = doc.querySelectorAll('[style]');
                if (inlineStyles.length > 10) {
                    this.addIssue({
                        title: 'استخدام مفرط للأنماط المضمنة',
                        type: 'improvement',
                        severity: 'low',
                        category: 'الأداء',
                        description: `${inlineStyles.length} عنصر يستخدم أنماط مضمنة، مما يزيد حجم HTML.`,
                        solution: 'نقل الأنماط المضمنة إلى ملف CSS منفصل.',
                        steps: [
                            'تجميع الأنماط المتكررة في ملف CSS',
                            'استخدام classes بدلاً من inline styles',
                            'تطبيق CSS minification لتقليل الحجم',
                            'تجنب التكرار في الأنماط',
                            'استخدام CSS variables للقيم المتكررة'
                        ]
                    });
                }
            }

            async analyzeSecurity(content, url) {
                if (!content) return;

                const urlObj = new URL(url);
                
                // فحص Mixed Content
                if (urlObj.protocol === 'https:') {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(content, 'text/html');
                    
                    const httpResources = doc.querySelectorAll('[src^="http:"], [href^="http:"]');
                    if (httpResources.length > 0) {
                        this.addIssueFromTemplate('security', 'mixedContent', {
                            COUNT: httpResources.length
                        });
                    }
                }

                // فحص عدم وجود CSP
                const parser = new DOMParser();
                const doc = parser.parseFromString(content, 'text/html');
                const csp = doc.querySelector('meta[http-equiv="Content-Security-Policy"]');
                
                if (!csp) {
                    this.addIssueFromTemplate('security', 'noCSP');
                }

                // فحص inline scripts
                const inlineScripts = doc.querySelectorAll('script:not([src])');
                if (inlineScripts.length > 0) {
                    this.addIssue({
                        title: 'استخدام JavaScript مضمن',
                        type: 'improvement',
                        severity: 'low',
                        category: 'الأمان',
                        description: `${inlineScripts.length} سكربت مضمن قد يزيد مخاطر XSS.`,
                        solution: 'نقل JavaScript إلى ملفات خارجية وتطبيق CSP.',
                        steps: [
                            'نقل الكود إلى ملفات .js منفصلة',
                            'استخدام event listeners بدلاً من onclick inline',
                            'تطبيق CSP strict لمنع inline scripts',
                            'تنظيف وفحص الكود من الثغرات',
                            'استخدام nonces أو hashes في CSP عند الحاجة'
                        ]
                    });
                }

                // فحص الكوكيز غير الآمنة (تقدير أساسي)
                if (document.cookie) {
                    this.addIssueFromTemplate('security', 'weakCookies');
                }
            }

            async analyzeAccessibility(content) {
                if (!content) return;

                const parser = new DOMParser();
                const doc = parser.parseFromString(content, 'text/html');

                // فحص lang attribute
                const html = doc.querySelector('html');
                if (!html || !html.getAttribute('lang')) {
                    this.addIssueFromTemplate('accessibility', 'missingLang');
                }

                // فحص form labels
                const inputs = doc.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea, select');
                let inputsWithoutLabels = 0;
                
                inputs.forEach(input => {
                    const id = input.getAttribute('id');
                    const ariaLabel = input.getAttribute('aria-label');
                    const ariaLabelledby = input.getAttribute('aria-labelledby');
                    const label = id ? doc.querySelector(`label[for="${id}"]`) : null;
                    
                    if (!label && !ariaLabel && !ariaLabelledby) {
                        inputsWithoutLabels++;
                    }
                });

                if (inputsWithoutLabels > 0) {
                    this.addIssueFromTemplate('accessibility', 'missingFormLabels', {
                        COUNT: inputsWithoutLabels
                    });
                }

                // فحص color contrast (فحص أساسي للألوان الشائعة)
                const elements = doc.querySelectorAll('*');
                let suspiciousContrast = false;
                
                elements.forEach(el => {
                    const style = el.getAttribute('style') || '';
                    // فحص بسيط للتركيبات الشائعة ذات التباين الضعيف
                    if ((style.includes('color: white') || style.includes('color: #fff')) && 
                        (style.includes('background: white') || style.includes('background: #fff'))) {
                        suspiciousContrast = true;
                    }
                });

                if (suspiciousContrast) {
                    this.addIssueFromTemplate('accessibility', 'poorColorContrast');
                }

                // فحص skip links
                const skipLinks = doc.querySelectorAll('a[href^="#main"], a[href^="#content"]');
                const mainContent = doc.querySelector('main, #main, .main-content, [role="main"]');
                
                if (skipLinks.length === 0 && mainContent) {
                    this.addIssueFromTemplate('accessibility', 'missingSkipLinks');
                }

                // فحص بنية العناوين
                const headings = doc.querySelectorAll('h1, h2, h3, h4, h5, h6');
                if (headings.length > 0) {
                    let previousLevel = 0;
                    let hasStructureIssue = false;
                    
                    headings.forEach(heading => {
                        const currentLevel = parseInt(heading.tagName.substring(1));
                        if (currentLevel > previousLevel + 1) {
                            hasStructureIssue = true;
                        }
                        previousLevel = currentLevel;
                    });

                    if (hasStructureIssue) {
                        this.addIssueFromTemplate('content', 'missingHeadingStructure');
                    }
                }
            }

            async analyzeMobile(content) {
                if (!content) return;

                const parser = new DOMParser();
                const doc = parser.parseFromString(content, 'text/html');

                // فحص viewport
                const viewport = doc.querySelector('meta[name="viewport"]');
                if (!viewport) {
                    this.addIssueFromTemplate('mobile', 'missingViewport');
                } else {
                    const viewportContent = viewport.getAttribute('content') || '';
                    if (!viewportContent.includes('width=device-width')) {
                        this.addIssueFromTemplate('mobile', 'missingViewport');
                    }
                }

                // فحص حجم النصوص والأزرار (تقدير أساسي)
                const buttons = doc.querySelectorAll('button, input[type="submit"], input[type="button"], .btn');
                const links = doc.querySelectorAll('a');
                
                if (buttons.length > 0 || links.length > 5) {
                    // إفتراض وجود أهداف لمس قد تحتاج تحسين
                    this.addIssueFromTemplate('mobile', 'smallTouchTargets');
                }

                // فحص التجاوب (فحص أساسي للـ media queries)
                const styles = doc.querySelectorAll('style, link[rel="stylesheet"]');
                let hasMediaQueries = false;
                
                styles.forEach(style => {
                    const content = style.textContent || '';
                    if (content.includes('@media') && (content.includes('max-width') || content.includes('min-width'))) {
                        hasMediaQueries = true;
                    }
                });

                if (!hasMediaQueries) {
                    this.addIssueFromTemplate('mobile', 'notResponsive');
                }
            }

            addIssue(issue) {
                this.issues.push(issue);
            }

            addIssueFromTemplate(category, templateKey, variables = {}) {
                const template = this.issueTemplates[category]?.[templateKey];
                if (!template) {
                    // إذا لم يوجد قالب، أضف مشكلة عامة
                    this.addIssue({
                        ...this.issueTemplates.general.fallbackIssue,
                        description: `تم اكتشاف مشكلة في ${category} - ${templateKey}. يُنصح بمراجعة هذا العنصر.`
                    });
                    return;
                }

                // نسخ القالب وتخصيصه
                const issue = JSON.parse(JSON.stringify(template));
                
                // استبدال المتغيرات في النص
                if (variables) {
                    Object.keys(variables).forEach(key => {
                        const placeholder = `{${key}}`;
                        issue.description = issue.description.replace(new RegExp(placeholder, 'g'), variables[key]);
                        issue.title = issue.title.replace(new RegExp(placeholder, 'g'), variables[key]);
                        if (issue.solution) {
                            issue.solution = issue.solution.replace(new RegExp(placeholder, 'g'), variables[key]);
                        }
                    });
                }

                this.addIssue(issue);
            }

            calculateScore() {
                if (this.issues.length === 0) return 100;
                
                let totalDeductions = 0;
                this.issues.forEach(issue => {
                    switch(issue.severity) {
                        case 'high': totalDeductions += 15; break;
                        case 'medium': totalDeductions += 8; break;
                        case 'low': totalDeductions += 3; break;
                        default: totalDeductions += 1;
                    }
                });
                
                return Math.max(0, 100 - totalDeductions);
            }

            displayResults(url) {
                this.endTime = performance.now();
                const loadTime = ((this.endTime - this.startTime) / 1000).toFixed(2);
                
                // تحديث معلومات الموقع
                document.querySelector('#siteName span').textContent = url;
                document.querySelector('#analysisDate span').textContent = new Date().toLocaleString('ar-SA');
                document.querySelector('#loadTime span').textContent = loadTime + ' ثانية';

                // حساب وعرض النتيجة
                const score = this.calculateScore();
                const scoreCircle = document.getElementById('scoreCircle');
                const scoreValue = document.getElementById('scoreValue');
                const scoreDescription = document.getElementById('scoreDescription');
                
                scoreValue.textContent = score;
                
                if (score >= 80) {
                    scoreCircle.className = 'score-circle score-good';
                    scoreDescription.textContent = '🎉 ممتاز! الموقع في حالة جيدة جداً مع مشاكل قليلة';
                } else if (score >= 60) {
                    scoreCircle.className = 'score-circle score-average';
                    scoreDescription.textContent = '⚠️ جيد، لكن يحتاج بعض التحسينات لرفع الأداء';
                } else {
                    scoreCircle.className = 'score-circle score-poor';
                    scoreDescription.textContent = '🔧 يحتاج تحسينات كبيرة لتحسين الأداء والجودة';
                }

                // حساب الإحصائيات
                const stats = {
                    errors: this.issues.filter(issue => issue.type === 'error').length,
                    warnings: this.issues.filter(issue => issue.type === 'warning').length,
                    improvements: this.issues.filter(issue => issue.type === 'improvement').length,
                    security: this.issues.filter(issue => issue.category === 'الأمان').length
                };

                // تحديث عدادات الإحصائيات
                document.getElementById('errorsCount').textContent = stats.errors;
                document.getElementById('warningsCount').textContent = stats.warnings;
                document.getElementById('improvementsCount').textContent = stats.improvements;
                document.getElementById('securityCount').textContent = stats.security;

                // عرض المشاكل
                this.displayIssues();

                // إظهار النتائج
                this.resultsSection.style.display = 'block';

            }

            displayIssues() {
                const issuesContainer = document.getElementById('issuesContainer');
                issuesContainer.innerHTML = '';

                if (this.issues.length === 0) {
                    issuesContainer.innerHTML = `
                        <div style="text-align: center; padding: 50px; background: linear-gradient(135deg, #e8f5e8, #d4edda); border-radius: 15px; color: #2e7d32; border: 2px solid #4caf50;">
                            <h3 style="font-size: 2rem; margin-bottom: 15px;">🎉 رائع!</h3>
                            <p style="font-size: 1.2rem; margin-bottom: 10px;">لم يتم اكتشاف أي مشاكل واضحة في الموقع!</p>
                            <p style="color: #4caf50; font-weight: bold;">الموقع يبدو في حالة ممتازة. واصل العمل الرائع!</p>
                        </div>
                    `;
                    return;
                }

                // ترتيب المشاكل حسب الأولوية
                this.issues.sort((a, b) => {
                    const severityOrder = { high: 3, medium: 2, low: 1 };
                    const typeOrder = { error: 4, warning: 3, security: 2, improvement: 1 };
                    
                    // ترتيب حسب الخطورة أولاً ثم النوع
                    if (severityOrder[a.severity] !== severityOrder[b.severity]) {
                        return severityOrder[b.severity] - severityOrder[a.severity];
                    }
                    return typeOrder[b.type] - typeOrder[a.type];
                });

                this.issues.forEach((issue, index) => {
                    const issueCard = this.createIssueCard(issue, index);
                    issuesContainer.appendChild(issueCard);
                });
            }

            createIssueCard(issue, index) {
                const card = document.createElement('div');
                card.className = `issue-card ${issue.type}`;

                // إضافة رموز للتوضيح حسب النوع
                const typeIcons = {
                    error: '🚨',
                    warning: '⚠️',
                    improvement: '💡',
                    security: '🔒'
                };

                const severityColors = {
                    high: '#f44336',
                    medium: '#ff9800', 
                    low: '#4caf50',
                    info: '#2196F3'
                };

                card.innerHTML = `
                    <div class="issue-header">
                        <div>
                            <div class="issue-title">
                                ${typeIcons[issue.type] || '📋'} ${issue.title}
                            </div>
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                <span>📂 ${issue.category}</span>
                                <span style="color: ${severityColors[issue.severity]};">● ${this.getSeverityText(issue.severity)}</span>
                            </div>
                        </div>
                        <span class="severity-badge severity-${issue.severity}">
                            ${this.getSeverityText(issue.severity)}
                        </span>
                    </div>
                    
                    <div class="issue-description">
                        ${issue.description}
                    </div>
                    
                    <div class="solution">
                        <h4>💡 الحل المقترح:</h4>
                        <p style="margin-bottom: 15px; color: #2c3e50; font-weight: 500;">${issue.solution}</p>
                        <div style="background: white; padding: 15px; border-radius: 8px; border-right: 3px solid #4caf50;">
                            <h5 style="color: #4caf50; margin-bottom: 10px;">خطوات التطبيق:</h5>
                            <ul style="list-style: none; padding: 0;">
                                ${issue.steps.map((step, i) => `
                                    <li style="margin-bottom: 8px; padding-right: 20px; position: relative;">
                                        <span style="position: absolute; right: 0; color: #4caf50; font-weight: bold;">${i + 1}.</span>
                                        ${step}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;">
                        <button class="custom-maintenance-btn" onclick="alert('💬 لطلب المساعدة في تطبيق هذا الحل:\\n\\n📧 يمكنك التواصل مع فريق التطوير\\n🔧 أو استشارة مطور ويب متخصص\\n📚 أو البحث عن دروس تعليمية لهذا الموضوع')">
                            🔧 تحتاج مساعدة في التطبيق؟
                        </button>
                    </div>
                `;

                return card;
            }

            getSeverityText(severity) {
                const severityTexts = {
                    high: 'عالي',
                    medium: 'متوسط',
                    low: 'منخفض',
                    info: 'معلومات'
                };
                return severityTexts[severity] || severity;
            }

            showError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `
                    <h3>⚠️ خطأ في التحليل</h3>
                    <p><strong>التفاصيل:</strong> ${message}</p>
                    <p><strong>الحلول المقترحة:</strong></p>
                    <ul style="margin: 10px 0; padding-right: 20px;">
                        <li>تأكد من صحة رابط الموقع</li>
                        <li>تحقق من اتصال الإنترنت</li>
                        <li>جرب موقع آخر للاختبار</li>
                        <li>قد يكون الموقع يحجب الوصول الآلي</li>
                    </ul>
                `;
                
                this.loadingSection.style.display = 'none';
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                document.querySelector('.input-section').appendChild(errorDiv);
                
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 8000);
            }

            isValidUrl(string) {
                try {
                    const url = new URL(string);
                    return url.protocol === 'http:' || url.protocol === 'https:';
                } catch (_) {
                    return false;
                }
            }
        }

        // تشغيل المحلل عند تحميل الصفحة
      // تشغيل المحلل عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    const analyzer = new WebsiteAnalyzer();
    analyzer.startAnalysis();
    console.log('🔍 محلل أخطاء المواقع الإلكترونية جاهز - الإصدار الكامل والمحسّن!');
    console.log('📊 يحتوي على أكثر من 25 نوع من الفحوصات والحلول');
    console.log('✨ تم تحسين واجهة المستخدم وتجربة التحليل');
});
 function refreshData() {
            location.reload();
        }
        

    

    </script>
</body>
</html>