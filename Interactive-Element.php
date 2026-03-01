<?php
// tracking_stats.php

require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
require_once 'classes/TrackingManager.php';
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';

// 1) تحقق من تسجيل الدخول
$userMgr = new UserManager($db);
if (!$userMgr->isLoggedIn()) {
    redirect('login.php');
}


$user_id = $_SESSION['user_id'];

// 2) جلب الموقع والتأكد من الملكية
$website_id = (int)($_GET['id'] ?? 0);
$siteMgr    = new WebsiteManager($db);
$website    = $siteMgr->getWebsiteById($website_id, $user_id);
if (!$website) {
    redirect('dashboard.php&lang='.$lang);
}
$user = $userMgr->getUserById($user_id);


// 3) منشئ التتبع + سكربت العناصر + قائمة العناصر
$trackMgr  = new TrackingManager($db);
$jsSnippet = $trackMgr->generateElementsScript($website_id);
$elements  = $trackMgr->getElements($website_id);

// 4) فترة التحليل
$days = (int)($_GET['days'] ?? 30);

// 5) تحديد اللغة
$is_rtl = $lang === 'ar';

// 6) نقاط نهاية AJAX
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

        // ملخص كامل لعنصر واحد
        case 'element_summary':
            $eid = (int)($_GET['element_id'] ?? 0);
            $summary = [
                'impressions'        => $trackMgr->getImpressionCount($eid, $days),
                'hovers'             => $trackMgr->getHoverCount($eid, $days),
                'avg_hover_seconds'  => $trackMgr->getAvgHoverDuration($eid, $days),
                'submits'            => $trackMgr->getSubmitCount($eid, $days),
                'unique_users'       => $trackMgr->getUniqueUsers($eid, $days),
                'ctr_percent'        => $trackMgr->getCTR($eid, $days),
                'clicks'             => count($trackMgr->getClickDetails($eid, $days)),
            ];
            echo json_encode($summary, JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}


//  1. إضافة معالجة طلب الحذف في بداية ملف PHP 
// إضافة هذا الكود بعد معالجة طلبات AJAX الموجودة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_element'])) {
    $element_id = (int)($_POST['element_id'] ?? 0);
    
    if ($element_id > 0) {
        $success = $trackMgr->deleteElement($element_id, $website_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $success ? 
                ($is_rtl ? 'تم حذف العنصر بنجاح' : 'Element deleted successfully') :
                ($is_rtl ? 'فشل في حذف العنصر' : 'Failed to delete element')
        ]);
        exit;
    }
}



?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'تحليلات المحتوى لـ ' . htmlspecialchars($website['name']) : 'Content Analytics for ' . htmlspecialchars($website['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($is_rtl): ?>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
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
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
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
        
        .filter-group select, .filter-group input {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
            transition: var(--transition);
            min-width: 150px;
        }
        
        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        /* قسم الإحصائيات */
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
        
        /* بطاقات العناصر */
        .elements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        
        .element-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-right: 4px solid var(--accent-color);
        }
        
        .element-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 50%;
            transform: translate(25px, -25px);
        }
        
        .element-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .element-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .element-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .element-selector {
            background: #0f172a;
            color: #e2e8f0;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            word-break: break-all;
        }
        
        .element-type {
            background: var(--gradient-info);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .metric-item {
            background: var(--background);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            transition: var(--transition);
        }
        
        .metric-item:hover {
            background: rgba(49, 130, 206, 0.05);
            border-color: var(--accent-color);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 4px;
        }
        
        .metric-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .element-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        /* كود التتبع */
        .code-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--warning-color);
        }
        
        .code-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .code-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .code-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .code-container {
            position: relative;
            background: #0f172a;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        
        .code-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #1e293b;
            border-bottom: 1px solid #334155;
        }
        
        .code-lang {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .copy-btn {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .copy-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.4);
        }
        
        .copy-btn.copied {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.2);
        }
        
        .code-content {
            padding: 20px;
            color: #e2e8f0;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
        }
        
        /* الجداول */
        .table-section {
            margin-bottom: 40px;
        }
        
        .table-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border-right: 4px solid var(--success-color);
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
            max-width: 800px;
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
            background: var(--error-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 600;
        }
        
        .modal-close:hover {
            background: #c53030;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        /* أدلة الاستخدام */
        .guide-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--info-color);
        }
        
        .guide-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .guide-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .guide-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .guide-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .guide-card {
            background: var(--background);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 20px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .guide-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-info);
        }
        
        .guide-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--accent-color);
        }
        
        .guide-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gradient-info);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        .guide-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .guide-card-text {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
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
        
        /* تأثيرات التحريك */
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
        
        /* توست الإشعارات */
        .toast {
            position: fixed;
            top: 20px;
            <?php echo $is_rtl ? 'left: 20px;' : 'right: 20px;'; ?>
            background: var(--success-color);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            z-index: 10001;
            transform: translateX(<?php echo $is_rtl ? '-' : ''; ?>400px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.error {
            background: var(--error-color);
        }
        
        .toast.warning {
            background: var(--warning-color);
        }
        
        /* تحسينات الاستجابة */
        @media (max-width: 1200px) {
            .elements-grid {
                grid-template-columns: 1fr;
            }
            
            .guide-cards {
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
            
            .elements-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .element-card {
                padding: 20px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group select,
            .filter-group input {
                min-width: auto;
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .element-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
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
        
        /* أيقونات الأنواع المختلفة */
        .icon-impressions { background: var(--gradient-info); }
        .icon-hovers { background: var(--gradient-success); }
        .icon-duration { background: var(--gradient-warning); }
        .icon-submits { background: var(--gradient-purple); }
        .icon-clicks { background: var(--gradient-orange); }
        .icon-users { background: var(--gradient-pink); }
        .icon-ctr { background: var(--gradient-teal); }
        
        
        
        /* تحسينات إضافية للمحددات الطويلة */
.selector-preview {
    cursor: pointer;
    transition: var(--transition);
}

.selector-preview:hover {
    background: #1e293b !important;
}

/* تحسين عرض الكود المصغر */
.code-preview-container {
    position: relative;
}

.code-preview-container::after {
    content: attr(data-lines) ' ' + (isRTL ? 'سطر' : 'lines');
    position: absolute;
    bottom: 8px;
    right: 12px;
    background: rgba(0,0,0,0.7);
    color: #94a3b8;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    pointer-events: none;
}

/* تحسين عرض النوافذ المنبثقة */
#selectorModal .modal-content,
#fullCodeModal .modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* تحسين عرض الكود في النافذة */
#fullCodeModal .code-content {
    font-size: 13px;
    line-height: 1.5;
}

#fullCodeModal .code-content pre {
    margin: 0;
    padding: 20px;
}

/* تحسين شريط التمرير */
#fullCodeModal .code-content::-webkit-scrollbar {
    width: 8px;
}

#fullCodeModal .code-content::-webkit-scrollbar-track {
    background: #1e293b;
}

#fullCodeModal .code-content::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 4px;
}

#fullCodeModal .code-content::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* تحسين أزرار الحذف */
.btn-delete {
    background: var(--error-color);
    color: white;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.btn-delete:hover {
    background: #c53030;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-delete:active {
    transform: translateY(0);
}

/* تحسين النافذة المنبثقة للحذف */
#deleteConfirmModal .modal-content {
    animation: modalBounceIn 0.3s ease-out;
}

@keyframes modalBounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(-100px);
    }
    50% {
        opacity: 1;
        transform: scale(1.05) translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* تحسين أيقونة التحذير */
.warning-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* تحسين حالة التحميل للزر */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}

/* تحسين عمود الإجراءات */
.table th:last-child,
.table td:last-child {
    text-align: center;
    width: 100px;
}

/* تحسين الاستجابة للأجهزة المحمولة */
@media (max-width: 768px) {
    #deleteConfirmModal .modal-content {
        margin: 10px;
        max-width: calc(100vw - 20px);
    }
    
    .btn-delete {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .table th:last-child,
    .table td:last-child {
        width: 80px;
    }
}

.fullwidth{
    width: -webkit-fill-available;
}
.flex{
    display:flex
}

    </style>
</head>
<body>
<body>
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $is_rtl ? 'منصة التحليلات' : 'Analytics Platform'; ?></h1>
                <p><?php echo $is_rtl ? 'تتبع العناصر التفاعلية' : 'Interactive Element Tracking'; ?></p>
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
                <a class="nav-item" href = "Analytics.php?id=<?php  echo $website_id; ?>&lang=<?php echo $lang; ?>">
                    <i class="fas fa-chart-line"></i>
                    <?php echo $is_rtl ? 'التحليلات العامة' : 'Analytics'; ?>
                    <br><span> <?php echo $is_rtl ? 'لموقع: ' : 'For site: '; ?> <?php echo htmlspecialchars($website['name']); ?></span>
                </a>
                <a class="nav-item active" href = "Interactive-Element.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>">
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
                <?php if ($userMgr->isAdmin()): ?>
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
                    <h2>
                       <?php echo $is_rtl ? 'تحليلات المحتوى التفاعلية' : 'Interactive Content Analytics'; ?>


                        </h2>
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
                                    <i class="fas fa-mouse-pointer"></i>
                                 <?php echo $is_rtl ? 'تحليلات المحتوى التفاعلية' : 'Interactive Content Analytics'; ?>
                                </h1>

                                <p>
                                    <?php echo $is_rtl ? 'تحليل مفصل لتفاعل الزوار والمستخدمين مع عناصر موقعك' : "Detailed analysis of visitor and users interaction with your site's elements"; ?>
                                </p>
                                <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="website-url">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo htmlspecialchars($website['domain']); ?>
                                </a>
                            </div>
                            
                            <div class="header-actions">
                                <a href="New-Element.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>" class="btn btn-outline">
                                    <i class="fas fa-code"></i>
                                    <?php echo $is_rtl ? 'عنصر جديد' : 'New Element'; ?>
                                </a>
                                <a href="Analytics.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>" class="btn btn-outline">
                                    <i class="fas fa-chart-line"></i>
                                    <?php echo $is_rtl ? 'التحليلات العامة' : 'Analytics'; ?>
                                </a>
                                <button class="btn btn-primary" onclick="loadStats()">
                                    <i class="fas fa-sync-alt"></i>
                                    <?php echo $is_rtl ? 'تحديث البيانات' : 'Refresh Data'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- دليل الاستخدام -->
                    <div class="guide-section slide-up">
                        <div class="guide-header">
                            <i class="fas fa-question-circle"></i>
                            <h3 class="guide-title"><?php echo $is_rtl ? 'ما هو تتبع العناصر؟' : 'What is Element Tracking?'; ?></h3>
                        </div>
                        <div class="guide-description">
                            <?php echo $is_rtl ? 'تتبع العناصر يسمح لك بمراقبة تفاعل المستخدمين مع عناصر محددة في موقعك مثل الأزرار والروابط والنماذج' : 'Element tracking allows you to monitor user interactions with specific elements on your website such as buttons, links, and forms'; ?>
                        </div>
                        
                        <div class="guide-cards">
                            <div class="guide-card">
                                <div class="guide-card-icon icon-impressions">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <h4 class="guide-card-title"><?php echo $is_rtl ? 'الظهور (Impressions)' : 'Impressions'; ?></h4>
                                <p class="guide-card-text">
                                    <?php echo $is_rtl ? 'عدد المرات التي ظهر فيها العنصر للمستخدمين' : 'Number of times the element was visible to users'; ?>
                                </p>
                            </div>
                            
                            <div class="guide-card">
                                <div class="guide-card-icon icon-clicks">
                                    <i class="fas fa-hand-pointer"></i>
                                </div>
                                <h4 class="guide-card-title"><?php echo $is_rtl ? 'النقرات (Clicks)' : 'Clicks'; ?></h4>
                                <p class="guide-card-text">
                                    <?php echo $is_rtl ? 'عدد المرات التي تم النقر على العنصر' : 'Number of times the element was clicked'; ?>
                                </p>
                            </div>
                            
                            <div class="guide-card">
                                <div class="guide-card-icon icon-hovers">
                                    <i class="fas fa-mouse"></i>
                                </div>
                                <h4 class="guide-card-title"><?php echo $is_rtl ? 'التحويم (Hovers)' : 'Hovers'; ?></h4>
                                <p class="guide-card-text">
                                    <?php echo $is_rtl ? 'عدد المرات التي حوم المستخدم فوق العنصر' : 'Number of times users hovered over the element'; ?>
                                </p>
                            </div>
                            
                            <div class="guide-card">
                                <div class="guide-card-icon icon-ctr">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <h4 class="guide-card-title"><?php echo $is_rtl ? 'معدل النقر (CTR)' : 'Click-Through Rate'; ?></h4>
                                <p class="guide-card-text">
                                    <?php echo $is_rtl ? 'نسبة النقرات إلى الظهور (النقرات ÷ الظهور × 100)' : 'Ratio of clicks to impressions (Clicks ÷ Impressions × 100)'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- فلاتر محسنة -->
                    <div class="filters slide-up">
                        <div class="filter-header">
                            <i class="fas fa-filter"></i>
                            <h3><?php echo $is_rtl ? 'خيارات التصفية والبحث' : 'Filter and Search Options'; ?></h3>
                        </div>
                        <div class="filter-description">
                            <?php echo $is_rtl ? 'استخدم الفلاتر أدناه للبحث عن عناصر محددة أو تغيير فترة التحليل' : 'Use the filters below to search for specific elements or change the analysis period'; ?>
                        </div>
                        <div class="filter-group">
                            <label style="font-weight: 600; color: var(--text-primary);">
                                <i class="fas fa-calendar"></i>
                                <?php echo $is_rtl ? 'الفترة الزمنية:' : 'Time Period:'; ?>
                            </label>
                            <select id="period">
                                <?php foreach ([7,30,90,365] as $d): ?>
                                    <option value="<?= $d ?>" <?= $d === $days ? 'selected' : '' ?>>
                                        <?php echo $is_rtl ? "آخر $d يوم" : "Last $d days"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <label style="font-weight: 600; color: var(--text-primary);">
                                <i class="fas fa-search"></i>
                                <?php echo $is_rtl ? 'البحث:' : 'Search:'; ?>
                            </label>
                            <input type="text" id="searchBox" 
                                   placeholder="<?php echo $is_rtl ? 'ابحث بالاسم أو المحدد...' : 'Search by name or selector...'; ?>"
                                   oninput="renderCards(window._statsCache)">
                            
                            <label style="font-weight: 600; color: var(--text-primary);">
                                <i class="fas fa-sort"></i>
                                <?php echo $is_rtl ? 'الترتيب:' : 'Sort by:'; ?>
                            </label>
                            <select id="sortBy" onchange="renderCards(window._statsCache)">
                                <option value="clicks_desc"><?php echo $is_rtl ? 'النقرات (تنازلي)' : 'Clicks (Descending)'; ?></option>
                                <option value="impr_desc"><?php echo $is_rtl ? 'الظهور (تنازلي)' : 'Impressions (Descending)'; ?></option>
                                <option value="ctr_desc"><?php echo $is_rtl ? 'معدل النقر (تنازلي)' : 'CTR (Descending)'; ?></option>
                                <option value="name_asc"><?php echo $is_rtl ? 'الاسم (أبجدي)' : 'Name (Alphabetical)'; ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- قسم إحصائيات العناصر -->
                    <div class="stats-section">
                        <div class="section-header">
                            <i class="fas fa-chart-bar"></i>
                            <div>
                                <h2><?php echo $is_rtl ? 'إحصائيات العناصر التفاعلية' : 'Interactive Elements Statistics'; ?></h2>
                                <div class="section-description">
                                    <?php echo $is_rtl ? 'تحليل مفصل لأداء كل عنصر تفاعلي في موقعك خلال الفترة المحددة' : 'Detailed analysis of each interactive element performance on your website during the selected period'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div id="statsCards" class="elements-grid">
                            <!-- سيتم تحميل البيانات هنا عبر JavaScript -->
                            <div class="element-card loading">
                                <div class="element-header">
                                    <div>
                                        <div class="element-title skeleton" style="height: 20px; width: 150px; margin-bottom: 8px;"></div>
                                        <div class="element-selector skeleton" style="height: 16px; width: 200px;"></div>
                                    </div>
                                    <div class="element-type skeleton" style="height: 24px; width: 60px;"></div>
                                </div>
                                <div class="metrics-grid">
                                    <?php for($i = 0; $i < 7; $i++): ?>
                                    <div class="metric-item">
                                        <div class="metric-value skeleton" style="height: 24px; width: 40px; margin-bottom: 4px;"></div>
                                        <div class="metric-label skeleton" style="height: 12px; width: 60px;"></div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- قسم العناصر المسجلة -->
                    <?php if (!empty($elements)): ?>
                    <div class="table-section">
                        <div class="section-header">
                            <i class="fas fa-list"></i>
                            <div>
                                <h2><?php echo $is_rtl ? 'العناصر المسجلة' : 'Registered Elements'; ?></h2>
                                <div class="section-description">
                                    <?php echo $is_rtl ? 'قائمة بجميع العناصر المسجلة ضمن موقع '.htmlspecialchars($website['name']) : 'List of all elements registered in '.htmlspecialchars($website['name']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-card">
                            <div class="table-header flex">
                                <div class="fullwidth">
                                <h3 class="table-title">
                                    <i class="fas fa-database"></i>
                                    <?php echo $is_rtl ? 'قاعدة بيانات العناصر' : 'Elements Database'; ?>
                                </h3>
                                <p class="table-description"><?php echo $is_rtl ? 'العناصر المتاحة للتتبع في موقعك' : 'Available elements for tracking on your website'; ?></p>
                                </div>
                                <a href="New-Element.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-code"></i>
                                    <?php echo $is_rtl ? 'عنصر جديد' : 'New Element'; ?>
                                </a>

                            </div>
<!--<div class="table-responsive">-->
<!--    <table class="table">-->
<!--        <thead>-->
<!--            <tr>-->
<!--                <th><i class="fas fa-hashtag"></i> #</th>-->
<!--                <th><i class="fas fa-tag"></i> <?php echo $is_rtl ? 'الوصف' : 'Description'; ?></th>-->
<!--                <th><i class="fas fa-code"></i> <?php echo $is_rtl ? 'المحدد' : 'Selector'; ?></th>-->
<!--                <th><i class="fas fa-calendar"></i> <?php echo $is_rtl ? 'التاريخ' : 'Date'; ?></th>-->
<!--            </tr>-->
<!--        </thead>-->
<!--        <tbody>-->
<!--            <?php foreach($elements as $el): ?>-->
<!--            <tr>-->
<!--                <td><span style="font-family: monospace; font-weight: 600;"><?= $el['id'] ?></span></td>-->
<!--                <td><strong><?= htmlspecialchars($el['name']) ?></strong></td>-->
<!--                <td>-->
<!--                    <div style="display: flex; align-items: center; gap: 8px;">-->
<!--                        <code class="selector-preview" style="background: #0f172a; color: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-->
<!--                            <?= htmlspecialchars($el['selector']) ?>-->
<!--                        </code>-->
<!--                        <?php if (strlen($el['selector']) > 30): ?>-->
<!--                        <button class="btn btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="showFullSelector('<?= htmlspecialchars($el['selector'], ENT_QUOTES) ?>', '<?= htmlspecialchars($el['name'], ENT_QUOTES) ?>')">-->
<!--                            <i class="fas fa-expand-alt"></i>-->
<!--                            <?php echo $is_rtl ? 'المزيد' : 'More'; ?>-->
<!--                        </button>-->
<!--                        <?php endif; ?>-->
<!--                    </div>-->
<!--                </td>-->
<!--                <td>-->
<!--                    <small style="color: var(--text-secondary);"><?= $el['created_at'] ?></small>-->
<!--                </td>-->
<!--            </tr>-->
<!--            <?php endforeach; ?>-->
<!--        </tbody>-->
<!--    </table>-->
<!--</div>-->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag"></i> #</th>
                <th><i class="fas fa-tag"></i> <?php echo $is_rtl ? 'الوصف' : 'Description'; ?></th>
                <th><i class="fas fa-code"></i> <?php echo $is_rtl ? 'المحدد' : 'Selector'; ?></th>
                <th><i class="fas fa-calendar"></i> <?php echo $is_rtl ? 'التاريخ' : 'Date'; ?></th>
                <th><i class="fas fa-cogs"></i> <?php echo $is_rtl ? 'إجراءات' : 'Actions'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($elements as $el): ?>
            <tr>
                <td><span style="font-family: monospace; font-weight: 600;"><?= $el['id'] ?></span></td>
                <td><strong><?= htmlspecialchars($el['name']) ?></strong></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <code class="selector-preview" style="background: #0f172a; color: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= htmlspecialchars($el['selector']) ?>
                        </code>
                        <?php if (strlen($el['selector']) > 30): ?>
                        <button class="btn btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="showFullSelector('<?= htmlspecialchars($el['selector'], ENT_QUOTES) ?>', '<?= htmlspecialchars($el['name'], ENT_QUOTES) ?>')">
                            <i class="fas fa-expand-alt"></i>
                            <?php echo $is_rtl ? 'المزيد' : 'More'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <small style="color: var(--text-secondary);"><?= $el['created_at'] ?></small>
                </td>
                <td>
                    <button class="btn btn-sm" style="background: var(--error-color); color: white; padding: 6px 10px;" 
                            onclick="confirmDeleteElement(<?= $el['id'] ?>, '<?= htmlspecialchars($el['name'], ENT_QUOTES) ?>')">
                        <i class="fas fa-trash"></i>
                        <?php echo $is_rtl ? 'حذف' : 'Delete'; ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- قسم الكود المخصص -->
<!--                    <?php //if ($jsSnippet): ?>-->
<!--                    <div class="code-section">-->
<!--                        <div class="code-header">-->
<!--                            <div class="code-title">-->
<!--                                <i class="fas fa-code"></i>-->
<!--                                <?php echo $is_rtl ? 'كود التتبع المخصص' : 'Custom Tracking Code'; ?>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                        <div class="code-description">-->
<!--                            <?php echo $is_rtl ? 'انسخ هذا الكود وضعه في موقعك لبدء تتبع العناصر التفاعلية. يجب وضع الكود قبل إغلاق تاغ &lt;/body&gt;' : 'Copy this code and place it on your website to start tracking interactive elements. The code should be placed before the closing &lt;/body&gt; tag'; ?>-->
<!--                        </div>-->
                        
<!--                        <div class="code-container">-->
<!--                            <div class="code-toolbar">-->
<!--                                <span class="code-lang">-->
<!--                                    <i class="fab fa-js-square"></i>-->
<!--                                    JavaScript-->
<!--                                </span>-->
<!--                                <button class="copy-btn" onclick="copyTrackingCode()">-->
<!--                                    <i class="fas fa-copy"></i>-->
<!--                                    <?php echo $is_rtl ? 'نسخ الكود' : 'Copy Code'; ?>-->
<!--                                </button>-->
<!--                            </div>-->
<!--                            <div class="code-content">-->
<!--                                <pre><code class="language-javascript" id="trackingCode">&lt;script&gt;-->
<!--<?= htmlspecialchars($jsSnippet) ?>-->
<!--&lt;/script&gt;</code></pre>-->
<!--                            </div>-->
<!--                        </div>-->
                        
<!--                        <div style="margin-top: 16px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border-left: 4px solid #3b82f6;">-->
<!--                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">-->
<!--                                <i class="fas fa-info-circle" style="color: #3b82f6;"></i>-->
<!--                                <strong style="color: #1e40af;"><?php echo $is_rtl ? 'معلومات مهمة' : 'Important Information'; ?></strong>-->
<!--                            </div>-->
<!--                            <p style="color: #1e40af; font-size: 14px; margin: 0;">-->
<!--                                <?php echo $is_rtl ? 'هذا الكود سيقوم بتتبع النقرات والتحويم والإرسال تلقائياً لجميع العناصر المحددة في موقعك' : 'This code will automatically track clicks, hovers, and submits for all specified elements on your website'; ?>-->
<!--                            </p>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <?php //endif; ?>-->

<!-- تعديل قسم الكود المخصص -->
<?php if ($jsSnippet): ?>
<div class="code-section">
    <div class="code-header">
        <div class="code-title">
            <i class="fas fa-code"></i>
            <?php echo $is_rtl ? 'كود التتبع المخصص' : 'Custom Tracking Code'; ?>
        </div>
    </div>
    <div class="code-description">
        <?php echo $is_rtl ? 'انسخ هذا الكود وضعه في موقعك لبدء تتبع العناصر التفاعلية. يجب وضع الكود قبل إغلاق تاغ &lt;/body&gt;' : 'Copy this code and place it on your website to start tracking interactive elements. The code should be placed before the closing &lt;/body&gt; tag'; ?>
    </div>
    
    <!-- معاينة مصغرة للكود -->
    <div class="code-preview-container" style="margin-bottom: 16px;">
        <div class="code-container" style="max-height: 120px; overflow: hidden; position: relative;">
            <div class="code-toolbar">
                <span class="code-lang">
                    <i class="fab fa-js-square"></i>
                    JavaScript
                </span>
                <div style="display: flex; gap: 8px;">
                    <button class="copy-btn" onclick="copyTrackingCode()">
                        <i class="fas fa-copy"></i>
                        <?php echo $is_rtl ? 'نسخ' : 'Copy'; ?>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="showFullCode()" style="color: white; border-color: rgba(255,255,255,0.3);">
                        <i class="fas fa-expand"></i>
                        <?php echo $is_rtl ? 'عرض' : 'View'; ?>
                    </button>
                </div>
            </div>
            <div class="code-content" style="max-height: 80px; overflow: hidden;">
                <pre><code class="language-javascript">&lt;script&gt;
<?= htmlspecialchars(substr($jsSnippet, 0, 200)) ?>...
&lt;/script&gt;</code></pre>
            </div>
            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 30px; background: linear-gradient(transparent, #0f172a); pointer-events: none;"></div>
        </div>
    </div>
    
    <div style="margin-top: 16px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border-right: 4px solid #3b82f6;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
            <strong style="color: #1e40af;"><?php echo $is_rtl ? 'معلومات مهمة' : 'Important Information'; ?></strong>
        </div>
        <p style="color: #1e40af; font-size: 14px; margin: 0;">
            <?php echo $is_rtl ? 'هذا الكود سيقوم بتتبع النقرات والتحويم والإرسال تلقائياً لجميع العناصر المحددة في موقعك' : 'This code will automatically track clicks, hovers, and submits for all specified elements on your website'; ?>
        </p>
    </div>
</div>
<?php endif; ?>

                </div>
            </div>
        </main>
    </div>
    
    <!-- نافذة تفاصيل النقرات -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-mouse-pointer"></i>
                    <?php echo $is_rtl ? 'تفاصيل النقرات' : 'Click Details'; ?>
                </h3>
                <button class="modal-close" onclick="closeModal('detailsModal')">
                    <i class="fas fa-times"></i>
                    <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="clickDetails">
                        <thead>
                            <tr>
                                <th><i class="fas fa-fingerprint"></i> <?php echo $is_rtl ? 'معرف الجلسة' : 'Session ID'; ?></th>
                                <th><i class="fas fa-clock"></i> <?php echo $is_rtl ? 'وقت النقرة' : 'Click Time'; ?></th>
                                <th><i class="fas fa-map-marker-alt"></i> <?php echo $is_rtl ? 'الموقع' : 'Location'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- سيتم تحميل البيانات هنا -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- نافذة ملخص العنصر -->
    <div id="summaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="summaryTitle">
                    <i class="fas fa-chart-pie"></i>
                    <?php echo $is_rtl ? 'ملخص العنصر' : 'Element Summary'; ?>
                </h3>
                <button class="modal-close" onclick="closeModal('summaryModal')">
                    <i class="fas fa-times"></i>
                    <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
                </button>
            </div>
            <div class="modal-body">
                <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                    <div class="metric-item">
                        <div class="metric-value" id="sm_impr" style="color: var(--info-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-eye"></i>
                            <?php echo $is_rtl ? 'الظهور' : 'Impressions'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_hover" style="color: var(--success-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-mouse"></i>
                            <?php echo $is_rtl ? 'التحويم' : 'Hovers'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_avg" style="color: var(--warning-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-stopwatch"></i>
                            <?php echo $is_rtl ? 'متوسط التحويم (ث)' : 'Avg. Hover (s)'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_sub" style="color: var(--accent-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-paper-plane"></i>
                            <?php echo $is_rtl ? 'الإرسال' : 'Submits'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_clk" style="color: var(--error-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-hand-pointer"></i>
                            <?php echo $is_rtl ? 'النقرات' : 'Clicks'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_uu" style="color: var(--primary-color);">0</div>
                        <div class="metric-label">
                            <i class="fas fa-users"></i>
                            <?php echo $is_rtl ? 'مستخدمون فريدون' : 'Unique Users'; ?>
                        </div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-value" id="sm_ctr" style="color: var(--info-color);">0%</div>
                        <div class="metric-label">
                            <i class="fas fa-percentage"></i>
                            CTR
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 16px; background: var(--background); border-radius: 8px; border: 1px solid var(--border-light);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-lightbulb" style="color: var(--warning-color);"></i>
                        <strong style="color: var(--text-primary);"><?php echo $is_rtl ? 'ملاحظة مهمة' : 'Important Note'; ?></strong>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                        <?php echo $is_rtl ? 'لاحتساب "متوسط التحويم" يجب أن يرسل الكود أحداث hover_start و hover_end. هذه الأحداث متضمنة في الكود المُحدث أعلاه.' : 'To calculate "average hover time", the code must send hover_start and hover_end events. These events are included in the updated code above.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- نافذة عرض المحدد الكامل -->
<div id="selectorModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title" id="selectorModalTitle">
                <i class="fas fa-code"></i>
                <?php echo $is_rtl ? 'المحدد الكامل' : 'Full Selector'; ?>
            </h3>
            <button class="modal-close" onclick="closeModal('selectorModal')">
                <i class="fas fa-times"></i>
                <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
            </button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px; display: block;">
                    <i class="fas fa-tag"></i>
                    <?php echo $is_rtl ? 'اسم العنصر:' : 'Element Name:'; ?>
                </label>
                <div id="selectorElementName" style="padding: 8px 12px; background: var(--background); border-radius: 6px; font-weight: 600;"></div>
            </div>
            
            <div>
                <label style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px; display: block;">
                    <i class="fas fa-code"></i>
                    <?php echo $is_rtl ? 'المحدد الكامل:' : 'Full Selector:'; ?>
                </label>
                <div class="code-container">
                    <div class="code-toolbar">
                        <span class="code-lang">CSS Selector</span>
                        <button class="copy-btn" onclick="copySelectorText()">
                            <i class="fas fa-copy"></i>
                            <?php echo $is_rtl ? 'نسخ' : 'Copy'; ?>
                        </button>
                    </div>
                    <div class="code-content">
                        <pre><code id="fullSelectorText" style="white-space: pre-wrap; word-break: break-all;"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة عرض الكود الكامل -->
<div id="fullCodeModal" class="modal">
    <div class="modal-content" style="max-width: 90vw; max-height: 90vh;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-code"></i>
                <?php echo $is_rtl ? 'كود التتبع الكامل' : 'Full Tracking Code'; ?>
            </h3>
            <button class="modal-close" onclick="closeModal('fullCodeModal')">
                <i class="fas fa-times"></i>
                <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
            </button>
        </div>
        <div class="modal-body" style="padding: 0;">
            <div class="code-container" style="border-radius: 0; margin: 0;">
                <div class="code-toolbar" style="border-radius: 0;">
                    <span class="code-lang">
                        <i class="fab fa-js-square"></i>
                        JavaScript
                    </span>
                    <button class="copy-btn" onclick="copyFullTrackingCode()">
                        <i class="fas fa-copy"></i>
                        <?php echo $is_rtl ? 'نسخ الكود الكامل' : 'Copy Full Code'; ?>
                    </button>
                </div>
                <div class="code-content" style="max-height: 70vh; overflow-y: auto;">
                    <pre><code class="language-javascript" id="fullTrackingCode">&lt;script&gt;
<?= htmlspecialchars($jsSnippet) ?>
&lt;/script&gt;</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- 3. إضافة نافذة تأكيد الحذف -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color: var(--error-color);"></i>
                <?php echo $is_rtl ? 'تأكيد الحذف' : 'Confirm Deletion'; ?>
            </h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">
                <i class="fas fa-times"></i>
                <?php echo $is_rtl ? 'إغلاق' : 'Close'; ?>
            </button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <div style="width: 80px; height: 80px; background: rgba(229, 62, 62, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-trash fa-2x" style="color: var(--error-color);"></i>
                </div>
                <h4 style="margin-bottom: 16px; color: var(--text-primary);">
                    <?php echo $is_rtl ? 'هل أنت متأكد من حذف هذا العنصر؟' : 'Are you sure you want to delete this element?'; ?>
                </h4>
                <p id="deleteElementName" style="color: var(--text-secondary); margin-bottom: 24px; font-weight: 600;"></p>
                <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 24px;">
                    <?php echo $is_rtl ? 'لا يمكن التراجع عن هذا الإجراء. سيتم حذف جميع بيانات التتبع المرتبطة بهذا العنصر.' : 'This action cannot be undone. All tracking data associated with this element will be deleted.'; ?>
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">
                        <i class="fas fa-times"></i>
                        <?php echo $is_rtl ? 'إلغاء' : 'Cancel'; ?>
                    </button>
                    <button id="confirmDeleteBtn" class="btn" style="background: var(--error-color); color: white;" onclick="deleteElement()">
                        <i class="fas fa-trash"></i>
                        <?php echo $is_rtl ? 'حذف نهائياً' : 'Delete Permanently'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- نافذة التحميل -->
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
                    <?php echo $is_rtl ? 'يرجى الانتظار بينما نجلب إحصائيات العناصر' : 'Please wait while we fetch element statistics'; ?>
                </p>
            </div>
        </div>
    </div>
</body>

    <script>
        // متغيرات عامة
        const websiteId = <?= $website_id ?>;
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        
        // كائن إدارة البيانات
        class ElementTrackingManager {
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
            
            // تحميل الإحصائيات
            async loadStats() {
                const days = document.getElementById('period').value;
                
                try {
                    this.showLoading('statsCards');
                    document.getElementById('loadingModal').classList.add('active');
                    
                    const response = await fetch(`?ajax=stats&id=${websiteId}&days=${days}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    window._statsCache = Array.isArray(data) ? data : [];
                    
                    this.renderCards(window._statsCache);
                    this.hideLoading('statsCards');
                    
                    setTimeout(() => {
                        document.getElementById('loadingModal').classList.remove('active');
                    }, 500);
                    
                } catch (error) {
                    console.error('Error loading stats:', error);
                    this.showError('statsCards', error);
                }
            }
            
            // رسم البطاقات
            renderCards(data) {
                const container = document.getElementById('statsCards');
                const searchQuery = (document.getElementById('searchBox')?.value || '').trim().toLowerCase();
                const sortBy = document.getElementById('sortBy')?.value || 'clicks_desc';
                
                let filteredData = (data || []).slice();
                
                // تطبيق البحث
                if (searchQuery) {
                    filteredData = filteredData.filter(el =>
                        (el.name || '').toLowerCase().includes(searchQuery) ||
                        (el.selector || '').toLowerCase().includes(searchQuery)
                    );
                }
                
                // تطبيق الترتيب
                filteredData.sort((a, b) => {
                    switch (sortBy) {
                        case 'impr_desc': 
                            return this.parseNumber(b.total_impressions) - this.parseNumber(a.total_impressions);
                        case 'ctr_desc': 
                            return this.parseNumber(b.ctr_percent) - this.parseNumber(a.ctr_percent);
                        case 'name_asc': 
                            return (a.name || '').localeCompare(b.name || '');
                        default: // clicks_desc
                            return this.parseNumber(b.total_clicks) - this.parseNumber(a.total_clicks);
                    }
                });
                
                if (!filteredData.length) {
                    container.innerHTML = this.getEmptyStateHTML();
                    return;
                }
                
                // رسم البطاقات
                container.innerHTML = filteredData.map(el => this.createElementCard(el)).join('');
                
                // تحميل متوسط التحويم بشكل منفصل
                this.loadAverageHoverTimes();
            }
            
            // إنشاء بطاقة عنصر
            createElementCard(element) {
                const id = Number(element.id);
                const name = this.escapeHtml(element.name || (isRTL ? 'بدون اسم' : 'Unnamed'));
                const selector = this.escapeHtml(element.selector || '');
                const type = this.escapeHtml(element.selector_type || (isRTL ? 'عنصر' : 'Element'));
                const impressions = this.parseNumber(element.total_impressions);
                const hovers = this.parseNumber(element.total_hovers);
                const submits = this.parseNumber(element.total_submits);
                const clicks = this.parseNumber(element.total_clicks);
                const uniqueUsers = this.parseNumber(element.unique_users);
                const ctr = this.formatPercent(element.ctr_percent);
                
                return `
                    <div class="element-card fade-in" data-element-id="${id}">
                        <div class="element-header">
                            <div>
                                <div class="element-title">${name}</div>
                                <div class="element-selector">${selector || '-'}</div>
                            </div>
                            <div class="element-type">${type}</div>
                        </div>
                        
                        <div class="metrics-grid">
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--info-color);">${this.formatNumber(impressions)}</div>
                                <div class="metric-label">
                                    <i class="fas fa-eye"></i>
                                    ${isRTL ? 'الظهور' : 'Impressions'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--success-color);">${this.formatNumber(hovers)}</div>
                                <div class="metric-label">
                                    <i class="fas fa-mouse"></i>
                                    ${isRTL ? 'التحويم' : 'Hovers'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" id="avg-${id}" style="color: var(--warning-color);">—</div>
                                <div class="metric-label">
                                    <i class="fas fa-stopwatch"></i>
                                    ${isRTL ? 'متوسط التحويم (ث)' : 'Avg. Hover (s)'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--accent-color);">${this.formatNumber(submits)}</div>
                                <div class="metric-label">
                                    <i class="fas fa-paper-plane"></i>
                                    ${isRTL ? 'الإرسال' : 'Submits'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--error-color);">${this.formatNumber(clicks)}</div>
                                <div class="metric-label">
                                    <i class="fas fa-hand-pointer"></i>
                                    ${isRTL ? 'النقرات' : 'Clicks'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--primary-color);">${this.formatNumber(uniqueUsers)}</div>
                                <div class="metric-label">
                                    <i class="fas fa-users"></i>
                                    ${isRTL ? 'مستخدمون فريدون' : 'Unique Users'}
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div class="metric-value" style="color: var(--info-color);">${ctr}%</div>
                                <div class="metric-label">
                                    <i class="fas fa-percentage"></i>
                                    CTR
                                </div>
                            </div>
                        </div>
                        
                        <div class="element-actions">
                            <button class="btn btn-sm btn-secondary" onclick="trackingManager.copySelector('${this.escapeAttr(selector)}')">
                                <i class="fas fa-copy"></i>
                                ${isRTL ? 'نسخ المحدد' : 'Copy Selector'}
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="trackingManager.showClicks(${id})">
                                <i class="fas fa-list"></i>
                                ${isRTL ? 'تفاصيل النقرات' : 'Click Details'}
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="trackingManager.showSummary(${id}, '${this.escapeAttr(name)}')" style="color: var(--accent-color); border-color: var(--accent-color);">
                                <i class="fas fa-chart-pie"></i>
                                ${isRTL ? 'الملخص' : 'Summary'}
                            </button>
                        </div>
                    </div>
                `;
            }
            
            // تحميل متوسط أوقات التحويم
            async loadAverageHoverTimes() {
                const days = document.getElementById('period').value;
                const cards = document.querySelectorAll('.element-card[data-element-id]');
                
                for (const card of cards) {
                    const elementId = card.getAttribute('data-element-id');
                    const avgElement = document.getElementById(`avg-${elementId}`);
                    
                    if (!avgElement || avgElement.dataset.loaded) continue;
                    
                    try {
                        const response = await fetch(`?ajax=element_summary&id=${websiteId}&element_id=${elementId}&days=${days}`);
                        const data = await response.json();
                        
                        const avgTime = this.parseNumber(data.avg_hover_seconds);
                        avgElement.textContent = avgTime.toFixed(2);
                        avgElement.dataset.loaded = '1';
                        
                    } catch (error) {
                        avgElement.textContent = '0.00';
                    }
                }
            }
            
            // عرض تفاصيل النقرات
            async showClicks(elementId) {
                const days = document.getElementById('period').value;
                
                try {
                    const response = await fetch(`?ajax=clicks&id=${websiteId}&element_id=${elementId}&days=${days}`);
                    const data = await response.json();
                    
                    const tbody = document.querySelector('#clickDetails tbody');
                    
                    if (data && data.length) {
                        tbody.innerHTML = data.map(click => `
                            <tr>
                                <td>
                                    <span style="font-family: monospace; background: var(--background); padding: 2px 6px; border-radius: 4px;">
                                        ${this.escapeHtml(click.session_id)}
                                    </span>
                                </td>
                                <td>
                                    <small style="color: var(--text-secondary);">
                                        ${this.formatDateTime(click.occurred_at)}
                                    </small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-map-marker-alt" style="color: var(--text-muted);"></i>
                                        <span>${click.page_url ? this.getPagePath(click.page_url) : (isRTL ? 'غير متوفر' : 'N/A')}</span>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                    <i class="fas fa-mouse-pointer fa-2x" style="margin-bottom: 10px; opacity: 0.3;"></i><br>
                                    ${isRTL ? 'لا توجد نقرات مسجلة' : 'No clicks recorded'}
                                </td>
                            </tr>
                        `;
                    }
                    
                    this.openModal('detailsModal');
                    
                } catch (error) {
                    console.error('Error loading click details:', error);
                    this.showToast(isRTL ? 'خطأ في تحميل تفاصيل النقرات' : 'Error loading click details', 'error');
                }
            }
            
            // عرض ملخص العنصر
            async showSummary(elementId, elementName) {
                const days = document.getElementById('period').value;
                document.getElementById('summaryTitle').innerHTML = `
                    <i class="fas fa-chart-pie"></i>
                    ${isRTL ? 'ملخص: ' : 'Summary: '}${elementName || ('#' + elementId)}
                `;
                
                try {
                    const response = await fetch(`?ajax=element_summary&id=${websiteId}&element_id=${elementId}&days=${days}`);
                    const data = await response.json();
                    
                    document.getElementById('sm_impr').textContent = this.formatNumber(data.impressions || 0);
                    document.getElementById('sm_hover').textContent = this.formatNumber(data.hovers || 0);
                    document.getElementById('sm_avg').textContent = this.parseNumber(data.avg_hover_seconds || 0).toFixed(2);
                    document.getElementById('sm_sub').textContent = this.formatNumber(data.submits || 0);
                    document.getElementById('sm_clk').textContent = this.formatNumber(data.clicks || 0);
                    document.getElementById('sm_uu').textContent = this.formatNumber(data.unique_users || 0);
                    document.getElementById('sm_ctr').textContent = this.formatPercent(data.ctr_percent || 0) + '%';
                    
                    this.openModal('summaryModal');
                    
                } catch (error) {
                    console.error('Error loading element summary:', error);
                    this.showToast(isRTL ? 'خطأ في تحميل ملخص العنصر' : 'Error loading element summary', 'error');
                }
            }
            
            // نسخ المحدد
            copySelector(selector) {
                if (!selector) return;
                
                navigator.clipboard.writeText(selector).then(() => {
                    this.showToast(isRTL ? 'تم نسخ المحدد بنجاح' : 'Selector copied successfully', 'success');
                }).catch(() => {
                    this.showToast(isRTL ? 'فشل في نسخ المحدد' : 'Failed to copy selector', 'error');
                });
            }
            
            // فتح النافذة المنبثقة
            openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                    modal.style.display = 'flex';
                }
            }
            
            // إغلاق النافذة المنبثقة
            closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                    // setTimeout(() => {
                    //     modal.style.display = 'none';
                    // }, 300);
                }
            }
            
            // عرض رسالة خطأ
            showError(containerId, error) {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>${isRTL ? 'خطأ في تحميل البيانات' : 'Error Loading Data'}</h3>
                            <p>${isRTL ? 'حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.' : 'An error occurred while loading data. Please try again.'}</p>
                            <button class="btn btn-primary" onclick="trackingManager.loadStats()">
                                <i class="fas fa-refresh"></i>
                                ${isRTL ? 'إعادة المحاولة' : 'Retry'}
                            </button>
                        </div>
                    `;
                }
                
                this.hideLoading(containerId);
                document.getElementById('loadingModal').classList.remove('active');
            }
            
            // الحصول على HTML للحالة الفارغة
            getEmptyStateHTML() {
                return `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-mouse-pointer"></i>
                        <h3>${isRTL ? 'لا توجد عناصر تفاعلية' : 'No Interactive Elements'}</h3>
                        <p>${isRTL ? 'لا توجد عناصر مطابقة لمعايير البحث المحددة' : 'No elements match the specified search criteria'}</p>
                        <button class="btn btn-primary" onclick="document.getElementById('searchBox').value=''; trackingManager.renderCards(window._statsCache);">
                            <i class="fas fa-refresh"></i>
                            ${isRTL ? 'إظهار جميع العناصر' : 'Show All Elements'}
                        </button>
                    </div>
                `;
            }
            
            // عرض رسالة توست
            showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    ${message}
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => toast.classList.add('show'), 100);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => document.body.removeChild(toast), 300);
                }, 3000);
            }
            
            // مساعدات التنسيق
            parseNumber(value) {
                const num = Number(value || 0);
                return isFinite(num) ? num : 0;
            }
            
            formatNumber(num) {
                if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + (isRTL ? 'م' : 'M');
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + (isRTL ? 'ك' : 'K');
                }
                return num.toLocaleString();
            }
            
            formatPercent(value) {
                const num = this.parseNumber(value);
                return num.toFixed(2);
            }
            
            formatDateTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString(isRTL ? 'ar-SA' : 'en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            getPagePath(url) {
                try {
                    const urlObj = new URL(url);
                    return urlObj.pathname || url;
                } catch (e) {
                    return url;
                }
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }
            
            escapeAttr(text) {
                return (text || '').replace(/['"]/g, '');
            }
        }
        
        // إنشاء مثيل من مدير التتبع
        const trackingManager = new ElementTrackingManager();
        
        // وظائف عامة
        function loadStats() {
            trackingManager.loadStats();
        }
        
        function renderCards(data) {
            trackingManager.renderCards(data);
        }
        
        function openModal(modalId) {
            trackingManager.openModal(modalId);
        }
        
        function closeModal(modalId) {
            trackingManager.closeModal(modalId);
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
            // تحميل الإحصائيات
            trackingManager.loadStats();
            
            // إضافة مستمعي الأحداث للنوافذ المنبثقة
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    trackingManager.closeModal(e.target.id);
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
            document.querySelectorAll('.guide-card, .element-card, .table-card').forEach(el => {
                observer.observe(el);
            });
            
            // إضافة مستمع لتغيير الفترة الزمنية
            document.getElementById('period').addEventListener('change', function() {
                trackingManager.loadStats();
            });
        });
        
        // تحسين الاستجابة
        window.addEventListener('resize', function() {
            // إعادة ترتيب العناصر عند تغيير حجم النافذة
            const cards = document.querySelectorAll('.element-card');
            cards.forEach(card => {
                card.style.transition = 'all 0.3s ease';
            });
        });
        
        // تحسين لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // إغلاق النوافذ المنبثقة بالضغط على Escape
            if (e.key === 'Escape') {
                const activeModals = document.querySelectorAll('.modal.active');
                activeModals.forEach(modal => {
                    trackingManager.closeModal(modal.id);
                });
            }
            
            // تركيز على مربع البحث بالضغط على Ctrl+F أو Cmd+F
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchBox = document.getElementById('searchBox');
                if (searchBox) {
                    searchBox.focus();
                    searchBox.select();
                }
            }
            
            // تحديث البيانات بالضغط على F5 أو Ctrl+R
            if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && e.key === 'r')) {
                e.preventDefault();
                trackingManager.loadStats();
            }
        });
        
        // معالجة أخطاء الشبكة العامة
        window.addEventListener('online', function() {
            trackingManager.showToast(isRTL ? 'تم استعادة الاتصال بالإنترنت' : 'Internet connection restored', 'success');
        });
        
        window.addEventListener('offline', function() {
            trackingManager.showToast(isRTL ? 'تم فقدان الاتصال بالإنترنت' : 'Internet connection lost', 'warning');
        });
        
        // تحسين أداء التمرير
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // إضافة تأثيرات عند التمرير
                const scrolled = window.pageYOffset;
                const topbar = document.querySelector('.topbar');
                
                if (scrolled > 50) {
                    topbar.style.boxShadow = 'var(--shadow-lg)';
                } else {
                    topbar.style.boxShadow = 'var(--shadow)';
                }
            }, 10);
        });
        
        // CSS إضافي للطباعة
        const printStyles = `
            <style>
                @media print {
                    .sidebar, .topbar, .btn, .modal { display: none !important; }
                    .main-content { margin-left: 0 !important; margin-right: 0 !important; }
                    .page-header { break-inside: avoid; }
                    .element-card, .table-card, .guide-card { break-inside: avoid; margin-bottom: 20px; }
                    .elements-grid { grid-template-columns: 1fr; }
                    body.printing .fade-in { animation: none; }
                    .code-container { background: #f8f9fa !important; color: #000 !important; }
                    .code-content { color: #000 !important; }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', printStyles);
        
        // تحسين الطباعة
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
        
        // تهيئة Prism.js لتنسيق الكود
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
        
        // دعم التنقل بواسطة لوحة المفاتيح في الجداول
        document.addEventListener('keydown', function(e) {
            if (e.target.closest('.table')) {
                const table = e.target.closest('.table');
                const rows = table.querySelectorAll('tbody tr');
                const currentRow = e.target.closest('tr');
                const currentIndex = Array.from(rows).indexOf(currentRow);
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex < rows.length - 1) {
                            rows[currentIndex + 1].querySelector('td').focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            rows[currentIndex - 1].querySelector('td').focus();
                        }
                        break;
                }
            }
        });
        
        // تحسين إمكانية الوصول
        document.querySelectorAll('.btn').forEach(btn => {
            if (!btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', btn.textContent.trim());
            }
        });
        
        // تحسين تجربة اللمس للأجهزة المحمولة
        if ('ontouchstart' in window) {
            document.addEventListener('touchstart', function() {}, { passive: true });
            
            // إضافة تأثيرات اللمس للبطاقات
            document.addEventListener('touchstart', function(e) {
                if (e.target.closest('.element-card')) {
                    e.target.closest('.element-card').style.transform = 'scale(0.98)';
                }
            });
            
            document.addEventListener('touchend', function(e) {
                if (e.target.closest('.element-card')) {
                    e.target.closest('.element-card').style.transform = '';
                }
            });
        }
        
        // حفظ حالة البحث والترتيب في localStorage (اختياري)
        function saveUserPreferences() {
            const prefs = {
                searchQuery: document.getElementById('searchBox').value,
                sortBy: document.getElementById('sortBy').value,
                period: document.getElementById('period').value
            };
            localStorage.setItem('elementTrackingPrefs', JSON.stringify(prefs));
        }
        
        function loadUserPreferences() {
            try {
                const prefs = JSON.parse(localStorage.getItem('elementTrackingPrefs') || '{}');
                if (prefs.searchQuery) document.getElementById('searchBox').value = prefs.searchQuery;
                if (prefs.sortBy) document.getElementById('sortBy').value = prefs.sortBy;
                if (prefs.period) document.getElementById('period').value = prefs.period;
            } catch (e) {
                // تجاهل الأخطاء في حالة عدم دعم localStorage
            }
        }
        
        // حفظ التفضيلات عند التغيير
        ['searchBox', 'sortBy', 'period'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', saveUserPreferences);
                element.addEventListener('input', saveUserPreferences);
            }
        });
        
        // تحميل التفضيلات عند بدء تشغيل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadUserPreferences();
        });
        
        // تحسين الشحن التدريجي للصور (إذا كانت موجودة)
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // إضافة تلميحات أدوات تفاعلية
        function addTooltips() {
            const tooltipElements = document.querySelectorAll('[title]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = e.target.getAttribute('title');
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #1a202c;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        z-index: 10000;
                        pointer-events: none;
                        white-space: nowrap;
                        box-shadow: var(--shadow-lg);
                    `;
                    
                    document.body.appendChild(tooltip);
                    
                    const rect = e.target.getBoundingClientRect();
                    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
                    
                    e.target.removeAttribute('title');
                    e.target.dataset.originalTitle = tooltip.textContent;
                });
                
                element.addEventListener('mouseleave', function(e) {
                    const tooltip = document.querySelector('.tooltip');
                    if (tooltip) {
                        document.body.removeChild(tooltip);
                    }
                    if (e.target.dataset.originalTitle) {
                        e.target.setAttribute('title', e.target.dataset.originalTitle);
                        delete e.target.dataset.originalTitle;
                    }
                });
            });
        }
        
        // تطبيق التلميحات عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', addTooltips);
        
        // إعادة تطبيق التلميحات عند إضافة محتوى جديد
        const contentObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    addTooltips();
                }
            });
        });
        
        contentObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        function showFullSelector(selector, elementName) {
    document.getElementById('selectorElementName').textContent = elementName || (isRTL ? 'غير محدد' : 'Not specified');
    document.getElementById('fullSelectorText').textContent = selector;
    trackingManager.openModal('selectorModal');
}

// نسخ المحدد
function copySelectorText() {
    const selectorText = document.getElementById('fullSelectorText').textContent;
    navigator.clipboard.writeText(selectorText).then(() => {
        trackingManager.showToast(isRTL ? 'تم نسخ المحدد بنجاح' : 'Selector copied successfully', 'success');
        
        // تغيير نص الزر مؤقتاً
        const copyBtn = document.querySelector('#selectorModal .copy-btn');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> ' + (isRTL ? 'تم النسخ!' : 'Copied!');
        copyBtn.classList.add('copied');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        trackingManager.showToast(isRTL ? 'فشل في نسخ المحدد' : 'Failed to copy selector', 'error');
    });
}

// إظهار الكود الكامل
function showFullCode() {
    trackingManager.openModal('fullCodeModal');
    // تفعيل تنسيق الكود إذا كان Prism متوفراً
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }
}

// نسخ الكود الكامل
function copyFullTrackingCode() {
    const fullCode = document.getElementById('fullTrackingCode').textContent;
    navigator.clipboard.writeText(fullCode).then(() => {
        trackingManager.showToast(isRTL ? 'تم نسخ الكود الكامل بنجاح' : 'Full code copied successfully', 'success');
        
        // تغيير نص الزر مؤقتاً
        const copyBtn = document.querySelector('#fullCodeModal .copy-btn');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> ' + (isRTL ? 'تم النسخ!' : 'Copied!');
        copyBtn.classList.add('copied');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        trackingManager.showToast(isRTL ? 'فشل في نسخ الكود' : 'Failed to copy code', 'error');
    });
}

// تحسين دالة نسخ الكود المصغر
function copyTrackingCode() {
    const fullCode = `<?= addslashes(htmlspecialchars($jsSnippet, ENT_QUOTES, 'UTF-8')); ?>`;
    
    navigator.clipboard.writeText(fullCode).then(() => {
        const copyBtn = document.querySelector('.code-preview-container .copy-btn');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> ' + (isRTL ? 'تم!' : 'Done!');
        copyBtn.classList.add('copied');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('copied');
        }, 2000);
        
        trackingManager.showToast(isRTL ? 'تم نسخ كود التتبع بنجاح' : 'Tracking code copied successfully', 'success');
    }).catch(() => {
        trackingManager.showToast(isRTL ? 'فشل في نسخ الكود' : 'Failed to copy code', 'error');
    });
}





      
        // متغيرات الحذف
let elementToDelete = null;

// تأكيد الحذف
function confirmDeleteElement(elementId, elementName) {
    elementToDelete = elementId;
    document.getElementById('deleteElementName').textContent = elementName;
    
    // فتح النافذة المنبثقة
    const modal = document.getElementById('deleteConfirmModal');
    modal.classList.add('active');
    modal.style.display = 'flex';
}

// إغلاق نافذة التأكيد
function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
    elementToDelete = null;
}

// تنفيذ الحذف
function deleteElement() {
    if (!elementToDelete) return;
    
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    
    // إظهار حالة التحميل
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isRTL ? 'جاري الحذف...' : 'Deleting...');
    confirmBtn.disabled = true;
    
    // إرسال طلب الحذف
    const formData = new FormData();
    formData.append('delete_element', '1');
    formData.append('element_id', elementToDelete);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // إظهار رسالة نجاح
            showDeleteToast(data.message, 'success');
            
            // إزالة الصف من الجدول
            removeElementRow(elementToDelete);
            
            // إغلاق النافذة
            closeDeleteModal();
        } else {
            // إظهار رسالة خطأ
            showDeleteToast(data.message, 'error');
            
            // إعادة تعيين الزر
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showDeleteToast(isRTL ? 'حدث خطأ أثناء الحذف' : 'An error occurred during deletion', 'error');
        
        // إعادة تعيين الزر
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// إزالة الصف من الجدول
function removeElementRow(elementId) {
    const tables = document.querySelectorAll('.table tbody');
    tables.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const idCell = row.querySelector('td:first-child span');
            if (idCell && parseInt(idCell.textContent) === elementId) {
                // تأثير إخفاء ناعم
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(' + (isRTL ? '50px' : '-50px') + ')';
                
                setTimeout(() => {
                    row.remove();
                    
                    // التحقق من وجود صفوف أخرى
                    if (tbody.children.length === 0) {
                        // إظهار حالة فارغة
                        const emptyState = `
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-mouse-pointer fa-2x" style="margin-bottom: 10px; opacity: 0.3;"></i><br>
                                    ${isRTL ? 'لا توجد عناصر مسجلة' : 'No registered elements'}
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML = emptyState;
                    }
                }, 300);
            }
        });
    });
}

// رسالة توست للحذف
function showDeleteToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        ${isRTL ? 'left: 20px;' : 'right: 20px;'}
        background: var(--${type === 'success' ? 'success' : 'error'}-color);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: var(--shadow-lg);
        z-index: 10001;
        transform: translateX(${isRTL ? '-' : ''}400px);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        toast.style.transform = `translateX(${isRTL ? '-' : ''}400px)`;
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// للصفحة الثانية: إعادة تحميل الصفحة بدلاً من AJAX
function deleteElementReload() {
    if (!elementToDelete) return;
    
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isRTL ? 'جاري الحذف...' : 'Deleting...');
    confirmBtn.disabled = true;
    
    // إنشاء نموذج مخفي وإرساله
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const deleteInput = document.createElement('input');
    deleteInput.name = 'delete_element';
    deleteInput.value = '1';
    
    const idInput = document.createElement('input');
    idInput.name = 'element_id';
    idInput.value = elementToDelete;
    
    form.appendChild(deleteInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
}

// إغلاق النافذة بالنقر خارجها
document.addEventListener('click', function(e) {
    const modal = document.getElementById('deleteConfirmModal');
    if (e.target === modal) {
        closeDeleteModal();
    }
});

// إغلاق النافذة بمفتاح Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal && modal.classList.contains('active')) {
            closeDeleteModal();
        }
    }
});
     
    

    </script>
</body>
</html>