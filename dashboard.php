<?php
// dashboard.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
require_once 'classes/PlatformManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$platformManager = new PlatformManager($db);
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';

// التحقق من تسجيل الدخول
if (!$userManager->isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = $userManager->getUserById($user_id);

// جلب المواقع الخاصة بالمستخدم
$websites = $websiteManager->getUserWebsites($user_id);

// جلب المنصات المتاحة
$platforms = $platformManager->getAllPlatforms();



$kpis = $websiteManager->getUserKpis($user_id);

// أمثلة فردية
// $totalTimeAll   = $kpis->getTotalTimeOnPageByUser($user_id);

// $totalvisit  = $kpis->getAllVisitorsByUser($user_id);
$totalTimeAll = $kpis['getAvgSessionDurationByUser'] ?? 0;
$totalvisit = $kpis['total_visitors'] ?? 0;
$totalclick = $kpis['total_clicks'] ?? 0;


        // 'total_time_on_page_seconds' => $this->getTotalTimeOnPageByUser($user_id),
        // 'total_visitors'             => $this->getAllVisitorsByUser($user_id),
        // 'total_clicks'               => $this->getAllClicksByUser($user_id),

// $totalClicks    = $kpis->getAllClicksByUser($user_id);
// $monthVisitors  = $kpis->getThisMonthVisitorsByUser($user_id);
// $monthClicks    = $kpis->getThisMonthClicksByUser($user_id);
// $monthTime      = $kpis->getThisMonthTimeOnPageByUser($user_id);
function formatDuration(int $seconds, bool $rtl = false): string {
    $total = max(0, $seconds);
    $h = intdiv($total, 3600);
    $m = intdiv($total % 3600, 60);
    $s = $total % 60;

    if ($h > 0) {
        return $rtl ? "{$h}س {$m}د" : "{$h}h {$m}m";
    } elseif ($m > 0) {
        return $rtl ? "{$m}د {$s}ث" : "{$m}m {$s}s";
    }
    return $rtl ? "{$s}ث" : "{$s}s";
}


// تحديد اللغة
$is_rtl = $lang === 'ar';

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_POST['action']) {
        case 'add_website':
            $result = $websiteManager->addWebsite($user_id, $_POST);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'delete_website':
            $result = $websiteManager->deleteWebsite($_POST['website_id'], $user_id);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'verify_website':
            $result = $websiteManager->verifyWebsite($_POST['website_id'], $user_id);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'get_tracking_code':
            $website = $websiteManager->getWebsiteById($_POST['website_id'], $user_id);
            if ($website) {
                $tracking_script = $websiteManager->generateTrackingScript($website['tracking_code']);
                echo json_encode([
                    'success' => true, 
                    'tracking_code' => $website['tracking_code'],
                    'tracking_script' => $tracking_script
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'الموقع غير موجود'], JSON_UNESCAPED_UNICODE);
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'لوحة التحكم - ' . SITE_NAME : 'Dashboard - ' . SITE_NAME_EN; ?></title>
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
            --gradient-purple: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-orange: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
            --gradient-pink: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-teal: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            border-right: 4px solid var(--accent-color);
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
               .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
              .websites-grid {
    grid-template-columns: 1fr;
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
            <?php //echo $is_rtl ? 'right: 12px;' : 'left: 12px;'; ?>
            width:60px;
            display: flex;
            background: rgba(255,255,255,0.1);
            color: #718096;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .copy-btn i {
            <?php echo $is_rtl ? 'margin-left: 5px' : 'margin-right: 5px;'; ?>
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        
        /* إضافة أنماط CSS للأحداث المخصصة */
.websites-grid {
    
      display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
    margin-top: 20px;
}

.website-card {
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--surface);
}

.website-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
}

.website-header h4 {
    margin: 0;
    color: var(--text-primary);
    font-size: 16px;
}

.website-url {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 15px;
}

.website-url i {
    margin-left: 5px;
}

.custom-events-stats {
    margin: 15px 0;
}

.stats-row {
    display: flex;
    gap: 20px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--accent-color);
}

.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
}

.website-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.examples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.example-card {
    background: rgba(49, 130, 206, 0.05);
    border: 1px solid rgba(49, 130, 206, 0.1);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.example-icon {
    width: 50px;
    height: 50px;
    background: var(--accent-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 20px;
}

.example-card h4 {
    margin: 0 0 10px 0;
    color: var(--text-primary);
    font-size: 16px;
}

.example-card p {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.4;
}

.example-code {
    background: var(--surface);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 8px;
    font-family: 'Monaco', monospace;
    font-size: 12px;
    color: var(--accent-color);
}

.help-section {
    margin-bottom: 25px;
}

.help-section h4 {
    color: var(--text-primary);
    margin-bottom: 10px;
    font-size: 16px;
}

.help-section p {
    color: var(--text-secondary);
    line-height: 1.6;
}

.event-types-list,
.selector-examples-help {
    margin-top: 15px;
}

.event-type,
.selector-example-help {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.event-type:last-child,
.selector-example-help:last-child {
    border-bottom: none;
}

.event-type strong,
.selector-example-help code {
    min-width: 120px;
    color: var(--accent-color);
}

.selector-example-help code {
    background: rgba(49, 130, 206, 0.1);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Monaco', monospace;
    font-size: 12px;
}

  .tab-content {
    display: none;
  }
  /* …وأظهر التاب الفعّال فقط */
  .tab-content.active {
    display: block;
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
                <a href="#" class="nav-item active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="websites">
                    <i class="fas fa-globe"></i>
                    <?php echo $is_rtl ? 'مواقعي' : 'Websites'; ?>
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
                    <h2 id="page-title"><?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?></h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>" class="lang-switcher">
                        <i class="fas fa-language"></i>
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </header>
            
            <!-- منطقة المحتوى -->
            <div class="content-area">
                <!-- تبويب لوحة التحكم -->
                <div id="dashboard-tab" class="tab-content active">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'مرحباً، ' . htmlspecialchars($user['full_name']) : 'Welcome, ' . htmlspecialchars($user['full_name']); ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'إليك نظرة عامة على أداء مواقعك' : 'Here\'s an overview of your websites performance'; ?></p>
                    </div>
                    
                    <!-- الإحصائيات -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="stat-value"><?php echo count($websites); ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'إجمالي المواقع' : 'Total Websites'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value">
                                <?php 
                                $approved_websites = array_filter($websites, function($w) { return $w['status'] === 'approved'; });
                                echo count($approved_websites);
                                ?>
                            </div>
                            <div class="stat-label"><?php echo $is_rtl ? 'المواقع المعتمدة' : 'Approved Websites'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="stat-value"><?php echo $totalvisit ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'إجمالي الزيارات' : 'Total Visitors'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-coffee"></i>
                            </div>
                            <div class="stat-value">
                                 <?= htmlspecialchars(
        formatDuration((int)($totalTimeAll ?? 0), $is_rtl),
        ENT_QUOTES, 'UTF-8'
     ) ?>
     </div>
                           
                                                   <div class="stat-label"></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'متوسط مدة الجلسة' : 'Avg. Session Duration'; ?></div>
                        </div>
                    </div>
                    
                    <!-- المواقع الحديثة -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'المواقع الحديثة' : 'Recent Websites'; ?></h3>
                            <button class="btn btn-primary btn-sm" onclick="showTab('websites')">
                                <?php echo $is_rtl ? 'عرض الكل' : 'View All'; ?>
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($websites)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-globe"></i>
                                    <h3><?php echo $is_rtl ? 'لا توجد مواقع بعد' : 'No websites yet'; ?></h3>
                                    <p><?php echo $is_rtl ? 'ابدأ بإضافة موقعك الأول للبدء في تتبع الزوار' : 'Start by adding your first website to track visitors'; ?></p>
                                    <button class="btn btn-primary" onclick="openAddWebsiteModal()">
                                        <i class="fas fa-plus"></i>
                                        <?php echo $is_rtl ? 'إضافة موقع جديد' : 'Add New Website'; ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?php echo $is_rtl ? 'اسم الموقع' : 'Website Name'; ?></th>
                                                <th><?php echo $is_rtl ? 'الرابط' : 'URL'; ?></th>
                                                <th><?php echo $is_rtl ? 'المنصة' : 'Platform'; ?></th>
                                                <th><?php echo $is_rtl ? 'الحالة' : 'Status'; ?></th>
                                                <th><?php echo $is_rtl ? 'تاريخ الإضافة' : 'Added Date'; ?></th>
                                                <!--<th><?php //echo $is_rtl ? 'إجراءات' : 'Action'; ?></th>-->

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($websites, 0, 5) as $website): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($website['name']); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="text-accent">
                                                        <?php echo htmlspecialchars($website['domain']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $is_rtl ? htmlspecialchars($website['platform_name']) : htmlspecialchars($website['platform_name_en']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($website['status']) {
                                                        case 'approved':
                                                            $status_class = 'badge-success';
                                                            $status_text = $is_rtl ? 'معتمد' : 'Approved';
                                                            break;
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            $status_text = $is_rtl ? 'قيد المراجعة' : 'Pending';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'badge-danger';
                                                            $status_text = $is_rtl ? 'مرفوض' : 'Rejected';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td><?php echo date('Y/m/d', strtotime($website['created_at'])); ?></td>
<!--                                                <td>-->
<!--                                                 <button class="btn btn-primary" onclick="window.location.href='Analytics.php?id=<?php //echo $website['id']; ?>&lang=<?php echo $lang; ?>'">-->
<!--                    <i class="fa fa-bar-chart"></i><?php //echo $is_rtl ? 'عرض التحليلات' : 'View Analytics'; ?>-->
<!--</button>-->
<!--                </td>-->
                                                
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                
                
                <!-- تبويب المواقع -->
                <div id="websites-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'إدارة المواقع' : 'Manage Websites'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'أضف وأدر مواقعك وتتبع أداءها' : 'Add and manage your websites and track their performance'; ?></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'مواقعي' : 'My Websites'; ?></h3>
                            <button class="btn btn-primary" onclick="openAddWebsiteModal()">
                                <i class="fas fa-plus"></i>
                                <?php echo $is_rtl ? 'إضافة موقع جديد' : 'Add New Website'; ?>
                            </button>
                        </div>
                        
                                <div class="card-body">
            <?php if (empty($websites)): ?>
                <div class="empty-state">
                    <i class="fas fa-globe"></i>
                    <h3><?php echo $is_rtl ? 'لا توجد مواقع بعد' : 'No websites yet'; ?></h3>
                    <p><?php echo $is_rtl ? 'أضف موقعك أولاً لتتمكن من إنشاء أحداث مخصصة' : 'Add your website first to create custom events'; ?></p>
                    <button class="btn btn-primary" onclick="showTab('websites')">
                        <i class="fas fa-plus"></i>
                        <?php echo $is_rtl ? 'إضافة موقع جديد' : 'Add New Website'; ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="websites-grid" id = "websites-list-cards">
                </div>
            <?php endif; ?>
        </div>



                    </div>
                </div>
            
    
    
                <!-- تبويب التحليلات -->
                <div id="analytics-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'التحليلات والإحصائيات' : 'Analytics & Statistics'; ?></h1>
                    <p class="page-subtitle"><?php echo $is_rtl ? 'أضف وأدر مواقعك وتتبع أداءها' : 'Add and manage your websites and track their performance'; ?></p>

                        <p class="page-subtitle"><?php echo $is_rtl ? 'تحليل مفصل لسلوك الزوار وأداء المواقع' : 'Detailed analysis of visitor behavior and website performance'; ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?php echo $is_rtl ? 'التحليلات ستظهر هنا بعد إضافة المواقع وتفعيل التتبع' : 'Analytics will appear here after adding websites and enabling tracking'; ?>
                    </div>
                </div>
                
        
                <!-- تبويب الإعدادات -->
                <div id="settings-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'الإعدادات' : 'Settings'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'إدارة حسابك وتفضيلاتك' : 'Manage your account and preferences'; ?></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'معلومات الحساب' : 'Account Information'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="profile-form">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $is_rtl ? 'الاسم الكامل' : 'Full Name'; ?></label>
                                    <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?php echo $is_rtl ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $is_rtl ? 'حفظ التغييرات' : 'Save Changes'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'تغيير كلمة المرور' : 'Change Password'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="password-form">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $is_rtl ? 'كلمة المرور الحالية' : 'Current Password'; ?></label>
                                    <input type="password" name="old_password" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?php echo $is_rtl ? 'كلمة المرور الجديدة' : 'New Password'; ?></label>
                                    <input type="password" name="new_password" class="form-input" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?php echo $is_rtl ? 'تأكيد كلمة المرور الجديدة' : 'Confirm New Password'; ?></label>
                                    <input type="password" name="confirm_new_password" class="form-input" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $is_rtl ? 'تغيير كلمة المرور' : 'Change Password'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                
                
                
             



            </div>
        </main>
    </div>
    
    <!-- نافذة إضافة موقع جديد -->
    <div id="add-website-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo $is_rtl ? 'إضافة موقع جديد' : 'Add New Website'; ?></h3>
                <button class="modal-close" onclick="closeModal('add-website-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-website-form">
                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'اسم الموقع' : 'Website Name'; ?></label>
                        <input type="text" name="name" class="form-input" required placeholder="<?php echo $is_rtl ? 'مثال: متجري الإلكتروني' : 'Example: My Online Store'; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'رابط الموقع' : 'Website URL'; ?></label>
                        <input type="url" name="url" class="form-input" required placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'المنصة' : 'Platform'; ?></label>
                        <select name="platform_id" class="form-select" required>
                            <option value=""><?php echo $is_rtl ? 'اختر المنصة' : 'Select Platform'; ?></option>
                            <?php foreach ($platforms as $platform): ?>
                            <option value="<?php echo $platform['id']; ?>">
                                <?php echo $is_rtl ? htmlspecialchars($platform['name']) : htmlspecialchars($platform['name_en']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('add-website-modal')">
                    <?php echo $is_rtl ? 'إلغاء' : 'Cancel'; ?>
                </button>
                <button class="btn btn-primary" onclick="addWebsite()">
                    <?php echo $is_rtl ? 'إضافة الموقع' : 'Add Website'; ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- نافذة كود التتبع -->
    <div id="tracking-code-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo $is_rtl ? 'كود التتبع' : 'Tracking Code'; ?></h3>
                <button class="modal-close" onclick="closeModal('tracking-code-modal')">&times;</button>
            </div>
            <div class="modal-body modalert">
                      <div style="
    display: flex;
    margin: auto;
">   
                <p style="width: 100%;"><?php echo $is_rtl ? 'انسخ الكود التالي والصقه قبل إغلاق وسم </head> في موقعك:' : 'Copy the following code and paste it before the closing </head> tag in your website:'; ?></p>
                <button class="copy-btn" onclick="copyTrackingCode()"><i class="fas fa-copy"></i> <?php echo $is_rtl ? ' نسخ ' : ' Copy '; ?></button></div>


<div class="tracking-code" id="tracking-code-display">
                    <!-- سيتم إدراج الكود هنا -->
                </div>
                <!--<button class="copy-btn" onclick="copyTrackingCode()">-->
                <!--    <i class="fas fa-copy"></i>-->
                <!--    <?php echo $is_rtl ? 'نسخ' : 'Copy'; ?>-->
                <!--</button>-->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('tracking-code-modal')">
                    <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
                </button>
            </div>
        </div>
    </div>
    
    




    <!-- زر الإضافة السريعة -->
    <button class="btn-floating" onclick="openAddWebsiteModal()" title="<?php echo $is_rtl ? 'إضافة موقع جديد' : 'Add New Website'; ?>">
        <i class="fas fa-plus"></i>
    </button>
    
    <script>
        // المتغيرات العامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const lang = '<?php echo $lang; ?>';
        let currentWebsites = <?php echo json_encode($websites, JSON_UNESCAPED_UNICODE); ?>;
        
        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadWebsitesCards();
            
            // إضافة مستمعي الأحداث للتنقل
            const navItems = document.querySelectorAll('.nav-item[data-tab]');
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tab = this.getAttribute('data-tab');
                    showTab(tab);
                });
            });
        });
        
  
  

/**
 * يُظهر التبويب المحدد ويخفي البقية، ويحدّث حالة التنقل والعنوان.
 *
 * @param {string} tabName – قيمة الـ data-tab من عنصر التنقل 
 */
function showTab(tabName) {
  // 1. إخفاء جميع محتويات التبويبات
  document.querySelectorAll('.tab-content').forEach(tab => {
    tab.classList.remove('active');
  });

  // 2. إزالة الفئة النشطة من جميع عناصر التنقل
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
  });

  // 3. حساب معرف التبويب (يتوافق مع id="xxx-tab") وإظهاره
  const tabId = tabName.endsWith('-tab') ? tabName : `${tabName}-tab`;
  const targetTab = document.getElementById(tabId);
  if (targetTab) {
    targetTab.classList.add('active');
  }

  // 4. تفعيل عنصر التنقل المطابق
  const activeNavItem = document.querySelector(`.nav-item[data-tab="${tabName}"]`);
  if (activeNavItem) {
    activeNavItem.classList.add('active');
  }


  // 5. تحديث عنوان الصفحة
  updatePageTitle(tabName);
  
  


}


/**
 * يحدث عنوان الصفحة (العنصر الذي يملك id="page-title") بناءً على التبويب النشط.
 *
 * @param {string} tabName – اسم التبويب (مثلاً 'dashboard', 'websites', ...)
 */



function updatePageTitle(tabName) {
  const titles = {
    'dashboard':      isRTL ? 'لوحة التحكم'          : 'Dashboard',
    'websites':      isRTL ? 'مواقعي'           : 'My Websites',
    'settings':       isRTL ? 'الإعدادات'            : 'Settings'
  };

  const pageTitle = document.getElementById('page-title');
  if (pageTitle && titles[tabName]) {
    pageTitle.textContent = titles[tabName];
  }
}

 
        // فتح/إغلاق الشريط الجانبي للجوال
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
          
      
      // تحميل المواقع (عرض كبطاقات بدل جدول)
function loadWebsitesCards() {
  const websitesList = document.getElementById('websites-list-cards');

  if (!currentWebsites || currentWebsites.length === 0) {
    websitesList.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-globe"></i>
        <h3>${isRTL ? 'لا توجد مواقع بعد' : 'No websites yet'}</h3>
        <p>${isRTL
          ? 'ابدأ بإضافة موقعك الأول للبدء في تتبع الزوار'
          : 'Start by adding your first website to track visitors'}</p>
        <button class="btn btn-primary" onclick="openAddWebsiteModal()">
          <i class="fas fa-plus"></i>
          ${isRTL ? 'إضافة موقع جديد' : 'Add New Website'}
        </button>
      </div>
    `;
    return;
  }

  // مولّد شارة الحالة
  const statusBadge = (status) => {
    switch (status) {
      case 'approved':
        return `<span class="badge badge-success">${isRTL ? 'معتمد' : 'Approved'}</span>`;
      case 'pending':
        return `<span class="badge badge-warning">${isRTL ? 'قيد المراجعة' : 'Pending'}</span>`;
      case 'rejected':
        return `<span class="badge badge-danger">${isRTL ? 'مرفوض' : 'Rejected'}</span>`;
      default:
        return `<span class="badge badge-info">${isRTL ? 'غير مُعتمد' : 'Not Approved'}</span>`;
    }
  };

  // لوحة البطاقات
  const cards = currentWebsites.map((website) => {
    const verified = Number(website.is_verified) === 1;
    const status = Number(website.status) === 1;
    const platformName = isRTL
      ? (website.platform_name || '—')
      : (website.platform_name_en || website.platform_name || 'N/A');

    // أزرار الإجراءات
    const actions = `
      <a href="Analytics.php?id=${website.id}&lang=${lang}" class="btn btn-primary btn-sm" title="${isRTL ? 'التحليلات العامة' : 'View Analytics'}">
        <i class="fas fa-bar-chart"></i>${isRTL ? ' التحليلات العامة' : ' Analytics'}
      </a>
      <a href="Interactive-Element.php?id=${website.id}&lang=${lang}" class="btn btn-primary btn-sm" title="${isRTL ? 'تحليلات المحتوى' : 'Content Analytics'}">
        <i class="fas fa-pie-chart"></i>${isRTL ? ' تحليلات المحتوى' : ' Content Analytics'}
      </a>
      <a href="heatmap.php?id=${website.id}&lang=${lang}" class="btn btn-primary btn-sm" title="${isRTL ? 'الخريطة الحرارية' : 'Heatmap'}">
        <i class="fas fa-fire"></i>${isRTL ? ' الخريطة الحرارية' : ' Heatmap'}
      </a>
      <button class="btn btn-outline btn-sm" onclick="showTrackingCode(${website.id})" title="${isRTL ? 'كود التتبع' : 'Tracking Code'}">
        <i class="fas fa-code"></i>${isRTL ? ' كود التتبع' : ' Tracking Code'}
      </button>
      ${verified ? '' : `
        <button class="btn btn-success btn-sm" onclick="verifyWebsite(${website.id})" title="${isRTL ? 'التحقق من الموقع' : 'Verify Website'}">
          <i class="fas fa-check"></i>${isRTL ? ' التحقق' : ' Verify'}
        </button>
      `}
    `;

    return `
      <div class="website-card card-hover">
        <div class="website-header" style="display:flex;align-items:center;justify-content:space-between;">
          <h4>${escapeHtml(website.name)}</h4>
    <button class="btn btn-danger btn-sm" onclick="deleteWebsite(${website.id})" title="${isRTL ? 'حذف' : 'Delete'}">
        <i class="fas fa-trash"></i>${isRTL ? ' حذف' : ' Delete'}
      </button>
        </div>

        <div class="website-url">
          <i class="fas fa-globe"></i>
          <a href="${website.url}" target="_blank" class="text-accent" style="text-decoration:none;">
            ${escapeHtml(website.domain)}
          </a>
        </div>

        <div class="custom-events-stats">
          <div class="stats-row">
            <div class="stat-item">
              <span class="stat-value">${statusBadge(website.status)}</span>
              <span class="stat-label">${isRTL ? 'الحالة' : 'Status'}</span>
            </div>
            <div class="stat-item">
              <span class="stat-value">${verified ? (isRTL ? 'مُحقق' : 'Verified') : (isRTL ? 'غير مُحقق' : 'Not Verified')}</span>
              <span class="stat-label">${isRTL ? 'التحقق' : 'Verification'}</span>
            </div>
          </div>
        </div>

        <div class="website-actions">
          ${actions}
        </div>
      </div>
    `;
  }).join('');

  websitesList.innerHTML = `
    <div class="websites-grid">
      ${cards}
    </div>
  `;
}


        // فتح نافذة إضافة موقع
        function openAddWebsiteModal() {
            openModal('add-website-modal');
        }
        
        // إضافة موقع جديد
        function addWebsite() {
            const form = document.getElementById('add-website-form');
            const formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('action', 'add_website');
            
            const submitBtn = event.target;
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> ' + (isRTL ? 'جاري الإضافة...' : 'Adding...');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal('add-website-modal');
                    form.reset();
                    // إعادة تحميل البيانات
                    location.reload();
                } else {
                    showAlert(data.errors ? data.errors.join('<br>') : 'حدث خطأ', 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }
        
        // عرض كود التتبع
        function showTrackingCode(websiteId) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_tracking_code');
            formData.append('website_id', websiteId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const codeDisplay = document.getElementById('tracking-code-display');
                    codeDisplay.innerHTML = `<pre><code>${escapeHtml(data.tracking_script)}</code></pre>`;
                    openModal('tracking-code-modal');
                } else {
                    showAlert(data.error || 'حدث خطأ', 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }
        
        // نسخ كود التتبع
        function copyTrackingCode() {
            const codeElement = document.querySelector('#tracking-code-display code');
            if (codeElement) {
                navigator.clipboard.writeText(codeElement.textContent).then(() => {
                    showMAlert(isRTL ? 'تم نسخ الكود بنجاح' : 'Code copied successfully', 'success');
                });
            }
        }
        
        // التحقق من الموقع
        function verifyWebsite(websiteId) {
            if (!confirm(isRTL ? 'هل تريد التحقق من هذا الموقع؟' : 'Do you want to verify this website?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'verify_website');
            formData.append('website_id', websiteId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    showAlert(data.error || 'حدث خطأ', 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }
        
        // حذف موقع
        function deleteWebsite(websiteId) {
            if (!confirm(isRTL ? 'هل تريد حذف هذا الموقع؟ سيتم حذف جميع البيانات المرتبطة به.' : 'Do you want to delete this website? All associated data will be deleted.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'delete_website');
            formData.append('website_id', websiteId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    showAlert(data.error || 'حدث خطأ', 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }
        
        // تسجيل الخروج
        function logout() {
            if (confirm(isRTL ? 'هل تريد تسجيل الخروج؟' : 'Do you want to logout?')) {
                window.location.href = 'logout.php&lang=<?php echo $lang; ?>';
            }
        }
        
        // فتح النوافذ المنبثقة
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        // إغلاق النوافذ المنبثقة
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }
        
        // إظهار التنبيهات
        function showAlert(message, type = 'info') {
            // إزالة التنبيهات الموجودة
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // إنشاء تنبيه جديد
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            
            // إدراج التنبيه في أعلى منطقة المحتوى
            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(alert, contentArea.firstChild);
            
            // إزالة التنبيه بعد 5 ثوانِ
            setTimeout(() => {
                alert.remove();
            }, 5000);
            
            // التمرير للأعلى لعرض التنبيه
            contentArea.scrollTop = 0;
        }
        
              // إظهار تنبيهات المودل
        function showMAlert(message, type = 'info') {
            // إزالة التنبيهات الموجودة
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // إنشاء تنبيه جديد
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            
            // إدراج التنبيه في أعلى منطقة المحتوى
            const contentArea = document.querySelector('.modalert');
            contentArea.insertBefore(alert, contentArea.firstChild);
            
            // إزالة التنبيه بعد 5 ثوانِ
            setTimeout(() => {
                alert.remove();
            }, 5000);
            
            // التمرير للأعلى لعرض التنبيه
            contentArea.scrollTop = 0;
        }
        
        // تحويل النص إلى HTML آمن
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // إغلاق النوافذ المنبثقة بالنقر خارجها
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
        
        // إغلاق الشريط الجانبي بالنقر خارجه في الجوال
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
        
        // معالجة تغيير حجم النافذة
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
        
        // معالجة نماذج الإعدادات
        document.getElementById('profile-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            // معالجة تحديث الملف الشخصي
            showAlert(isRTL ? 'تم حفظ التغييرات بنجاح' : 'Changes saved successfully', 'success');
        });
        
        document.getElementById('password-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_new_password"]').value;
            
            if (newPassword !== confirmPassword) {
                showAlert(isRTL ? 'كلمات المرور غير متطابقة' : 'Passwords do not match', 'error');
                return;
            }
            
            // معالجة تغيير كلمة المرور
            showAlert(isRTL ? 'تم تغيير كلمة المرور بنجاح' : 'Password changed successfully', 'success');
            this.reset();
        });
        
        // حفظ التبويب النشط
        document.querySelectorAll('.nav-item[data-tab]').forEach(item => {
            item.addEventListener('click', function() {
                localStorage.setItem('activeTab', this.getAttribute('data-tab'));
            });
        });
        
        // استعادة التبويب النشط
        const savedTab = localStorage.getItem('activeTab');
        if (savedTab && document.getElementById(savedTab + '-tab')) {
            showTab(savedTab);
        }
        
        
        
        document.querySelectorAll('.verify-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const siteId = this.dataset.id;
    fetch('api/verifySite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ site_id: siteId })
    })
    .then(res => res.json())
    .then(resp => {
      if (resp.success) {
        location.reload();
      } else {
        alert(resp.error || 'Failed to verify');
      }
    })
    .catch(() => alert('Network error'));
  });
});





// إظهار معاينة الأحداث لموقع معين
function showEventsPreview(websiteId) {
    // محاكاة تحميل البيانات
    const previewContent = document.getElementById('events-preview-content');
    previewContent.innerHTML = `
        <div class="loading-state" style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent-color);"></i>
            <p style="margin-top: 10px; color: var(--text-secondary);">${isRTL ? 'جاري تحميل البيانات...' : 'Loading data...'}</p>
        </div>
    `;
    
    openModal('events-preview-modal');
    
    // محاكاة تحميل البيانات بعد ثانيتين
    setTimeout(() => {
        previewContent.innerHTML = generateEventPreviewHTML(websiteId);
    }, 2000);
}

// توليد HTML معاينة الأحداث
function generateEventPreviewHTML(websiteId) {
    return `
        <div class="events-preview">
            <div class="preview-stats">
                <h4>${isRTL ? 'إحصائيات الأحداث المخصصة - آخر 7 أيام' : 'Custom Events Stats - Last 7 Days'}</h4>
                <div class="stats-grid" style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                ">
                    <div class="stat-card" style="background: rgba(56, 161, 105, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--success-color);">247</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            ${isRTL ? 'نقرات إضافة للسلة' : 'Add to Cart Clicks'}
                        </div>
                    </div>
                    <div class="stat-card" style="background: rgba(49, 130, 206, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--accent-color);">3.2</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            ${isRTL ? 'متوسط المنتجات في السلة' : 'Avg Products in Cart'}
                        </div>
                    </div>
                    <div class="stat-card" style="background: rgba(214, 158, 46, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--warning-color);">18</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            ${isRTL ? 'إرسالات نماذج الاتصال' : 'Contact Form Submissions'}
                        </div>
                    </div>
                    <div class="stat-card" style="background: rgba(229, 62, 62, 0.1); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--error-color);">89</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            ${isRTL ? 'نقرات قائمة الأمنيات' : 'Wishlist Clicks'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-events">
                <h4>${isRTL ? 'الأحداث الأخيرة' : 'Recent Events'}</h4>
                <div class="events-timeline" style="margin-top: 15px;">
                    <div class="timeline-item" style="
                        display: flex;
                        align-items: center;
                        padding: 10px 0;
                        border-bottom: 1px solid var(--border-color);
                    ">
                        <div style="
                            width: 8px;
                            height: 8px;
                            background: var(--success-color);
                            border-radius: 50%;
                            margin-left: 10px;
                        "></div>
                        <div style="flex: 1;">
                            <strong>${isRTL ? 'نقر على زر إضافة للسلة' : 'Add to Cart Button Click'}</strong>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                ${isRTL ? 'منتج: قميص قطني أزرق - منذ دقيقتين' : 'Product: Blue Cotton Shirt - 2 minutes ago'}
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item" style="
                        display: flex;
                        align-items: center;
                        padding: 10px 0;
                        border-bottom: 1px solid var(--border-color);
                    ">
                        <div style="
                            width: 8px;
                            height: 8px;
                            background: var(--accent-color);
                            border-radius: 50%;
                            margin-left: 10px;
                        "></div>
                        <div style="flex: 1;">
                            <strong>${isRTL ? 'تحديث السلة' : 'Cart Updated'}</strong>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                ${isRTL ? '3 منتجات في السلة - منذ 5 دقائق' : '3 products in cart - 5 minutes ago'}
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item" style="
                        display: flex;
                        align-items: center;
                        padding: 10px 0;
                    ">
                        <div style="
                            width: 8px;
                            height: 8px;
                            background: var(--warning-color);
                            border-radius: 50%;
                            margin-left: 10px;
                        "></div>
                        <div style="flex: 1;">
                            <strong>${isRTL ? 'إرسال نموذج اتصال' : 'Contact Form Submitted'}</strong>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                ${isRTL ? 'استفسار عن الشحن - منذ 12 دقيقة' : 'Shipping inquiry - 12 minutes ago'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}
// تحديث إحصائيات الأحداث في البطاقات
// تحديث إحصائيات الأحداث في البطاقات
function updateWebsiteEventStats() {
  // استهداف كل العناصر التي تبدأ معرفها بـ events-count-
  const elements = document.querySelectorAll('[id^="events-count-"]');
  
  elements.forEach(el => {
    // الحصول على رقم الموقع من نهاية الـ id
    const parts = el.id.split('-');
    const websiteId = parts[parts.length - 1];
    
    // محاكاة رقم عشوائي بين 1 و 10
    el.textContent = String(Math.floor(Math.random() * 10) + 1);
    
    // تحديث العنصر المقابل لعدد الـ triggers
    const triggerEl = document.getElementById(`triggers-count-${websiteId}`);
    if (triggerEl) {
      // محاكاة رقم عشوائي بين 50 و 549
      triggerEl.textContent = String(Math.floor(Math.random() * 500) + 50);
    }
  });
}

// // مثال على استدعاء دوري كل 5 ثوانٍ
// setInterval(updateWebsiteEventStats, 5000);

// // استدعاء أولي عند تحميل الصفحة
// document.addEventListener('DOMContentLoaded', updateWebsiteEventStats);

// تحديث الإحصائيات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateWebsiteEventStats();
});



function updateWebsiteEventStatsUI(websiteId, stats) {
    const eventsCountEl = document.getElementById(`events-count-${websiteId}`);
    const triggersCountEl = document.getElementById(`triggers-count-${websiteId}`);
    
    if (eventsCountEl) {
        eventsCountEl.textContent = stats.length || 0;
    }
    
    if (triggersCountEl) {
        const totalTriggers = stats.reduce((sum, stat) => sum + parseInt(stat.total_triggers || 0), 0);
        triggersCountEl.textContent = totalTriggers;
    }
}






    </script>
</body>
</html>