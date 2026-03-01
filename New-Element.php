<?php
// manage_tracking_elements.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
require_once 'classes/TrackingManager.php';

$userManager    = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$trackingManager = new TrackingManager($db);

if (!$userManager->isLoggedIn()) {
    header('Location: login.php'); exit;
}

$user_id    = $_SESSION['user_id'];
$website_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$website    = $websiteManager->getWebsiteById($website_id, $user_id);
if (!$website) {
    echo "الموقع غير موجود أو غير مصرح لك بالوصول إليه."; exit;
}

$user = $userManager->getUserById($user_id);
$jsSnippet = $trackingManager->generateElementsScript($website_id);

// تحديد اللغة
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
$is_rtl = $lang === 'ar';

// استخراج الدومين فقط
$domain = parse_url($website['url'], PHP_URL_HOST);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name     = trim($_POST['name']     ?? '');
    $selector = trim($_POST['selector'] ?? '');

    if ($name === '' || $selector === '') {
        $message = $is_rtl ? 'الرجاء تعبئة الوصف وCSS selector.' : 'Please fill in the description and CSS selector.';
        $messageType = 'error';
    } else {
        // أدخل العنصر الجديد
        $sql = "INSERT INTO tracking_elements (website_id, name, selector)
                VALUES (?, ?, ?)";
        $ok  = $db->query($sql, [$website_id, $name, $selector]);

        if ($ok) {
            $lastId  = $db->lastInsertId();
            $message = $is_rtl ? '✅ تم حفظ العنصر بنجاح.' : '✅ Element saved successfully.';
            $messageType = 'success';
        } else {
            $message = $is_rtl ? '❌ حدث خطأ أثناء الحفظ.' : '❌ Error occurred while saving.';
            $messageType = 'error';
        }
    }
}

// إضافة هذا الكود بعد معالجة حفظ العنصر الجديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_element'])) {
    $element_id = (int)($_POST['element_id'] ?? 0);
    
    if ($element_id > 0) {
        $success = $trackingManager->deleteElement($element_id, $website_id);
        
        if ($success) {
            $message = $is_rtl ? '✅ تم حذف العنصر بنجاح.' : '✅ Element deleted successfully.';
            $messageType = 'success';
        } else {
            $message = $is_rtl ? '❌ فشل في حذف العنصر.' : '❌ Failed to delete element.';
            $messageType = 'error';
        }
        
        // إعادة تحديث قائمة العناصر
        $elements = $db->fetchAll(
            "SELECT id, name, selector, created_at
             FROM tracking_elements
             WHERE website_id = ?
             ORDER BY created_at DESC",
            [$website_id]
        );
    }
}



// جلب العناصر المسجلة
$elements = $db->fetchAll(
    "SELECT id, name, selector, created_at
     FROM tracking_elements
     WHERE website_id = ?
     ORDER BY created_at DESC",
    [$website_id]
);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'إدارة تتبع العناصر - ' . htmlspecialchars($website['name']) : 'Manage Element Tracking - ' . htmlspecialchars($website['name']); ?></title>
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
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .topbar {
            background: var(--surface);
            padding: 16px 32px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        
        /* منطقة العمل */
        .workspace {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* لوحة التحكم */
        .control-panel {
            width: 380px;
            background: var(--surface);
            border-<?php echo $is_rtl ? 'left' : 'right'; ?>: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        
        .panel-section {
            padding: 24px;
            border-bottom: 1px solid var(--border-light);
        }
        
        .panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .panel-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .panel-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        /* النموذج */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--surface);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .form-input.selector-input {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
        }
        
        /* الأزرار */
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
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-full {
            width: 100%;
            justify-content: center;
        }
        
        .btn.selecting {
            background: var(--error-color);
            color: #fff;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* الرسائل */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        /* جدول العناصر */
        .elements-table {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px 8px;
            text-align: <?php echo $is_rtl ? 'right' : 'left'; ?>;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
        }
        
        .table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-primary);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .table tr:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        .table code {
            background: #0f172a;
            color: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            max-width: 150px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* منطقة المعاينة */
        .preview-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--surface);
        }
        
        .preview-header {
            padding: 16px 24px;
            background: var(--background);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .preview-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .url-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        
        .url-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .preview-frame {
            flex: 1;
            border: none;
            background: white;
        }
        
        /* منطقة التمييز */
        .highlight {
            position: absolute;
            background: rgba(49, 130, 206, 0.3);
            border: 2px solid var(--accent-color);
            pointer-events: none;
            transition: all 0.1s ease;
            z-index: 10000;
            border-radius: 4px;
            box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.1);
        }
        
        .highlight::after {
            content: '';
            position: absolute;
            top: -8px;
            <?php echo $is_rtl ? 'right: -8px;' : 'left: -8px;'; ?>
            width: 8px;
            height: 8px;
            background: var(--accent-color);
            border-radius: 50%;
            box-shadow: 0 0 0 2px white;
        }
        
        /* حالة فارغة */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
            color: var(--text-muted);
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* تحسينات الاستجابة */
        @media (max-width: 1200px) {
            .control-panel {
                width: 320px;
            }
        }
        
        @media (max-width: 968px) {
            .workspace {
                flex-direction: column;
            }
            
            .control-panel {
                width: 100%;
                max-height: 50vh;
            }
            
            .preview-area {
                flex: 1;
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
            
            .panel-section {
                padding: 16px;
            }
            
            .preview-header {
                padding: 12px 16px;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
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
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(49, 130, 206, 0.3);
            border-radius: 50%;
            border-top-color: var(--accent-color);
            animation: spin 1s linear infinite;
            z-index: 1000;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* شارة الحالة */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
        }
        
        .status-selecting {
            background: rgba(229, 62, 62, 0.1);
            color: var(--error-color);
        }
        
        
        /* تحسين أزرار الحذف */
/*.btn-delete {*/
/*    background: var(--error-color);*/
/*    color: white;*/
/*    transition: var(--transition);*/
/*    position: relative;*/
/*    overflow: hidden;*/
/*}*/

/*.btn-delete:hover {*/
/*    background: #c53030;*/
/*    transform: translateY(-1px);*/
/*    box-shadow: var(--shadow-md);*/
/*}*/

/*.btn-delete:active {*/
/*    transform: translateY(0);*/
/*}*/

/* تحسين النافذة المنبثقة للحذف */
/*#deleteConfirmModal .modal-content {*/
/*    animation: modalBounceIn 0.3s ease-out;*/
/*}*/

/*@keyframes modalBounceIn {*/
/*    0% {*/
/*        opacity: 0;*/
/*        transform: scale(0.3) translateY(-100px);*/
/*    }*/
/*    50% {*/
/*        opacity: 1;*/
/*        transform: scale(1.05) translateY(-10px);*/
/*    }*/
/*    100% {*/
/*        opacity: 1;*/
/*        transform: scale(1) translateY(0);*/
/*    }*/
/*}*/

/* تحسين أيقونة التحذير */
/*.warning-icon {*/
/*    animation: pulse 2s infinite;*/
/*}*/

/*@keyframes pulse {*/
/*    0%, 100% {*/
/*        transform: scale(1);*/
/*    }*/
/*    50% {*/
/*        transform: scale(1.1);*/
/*    }*/
/*}*/

/* تحسين حالة التحميل للزر */
/*.btn:disabled {*/
/*    opacity: 0.7;*/
/*    cursor: not-allowed;*/
/*    pointer-events: none;*/
/*}*/

/* تحسين عمود الإجراءات */
/*.table th:last-child,*/
/*.table td:last-child {*/
/*    text-align: center;*/
/*    width: 100px;*/
/*}*/

/* تحسين الاستجابة للأجهزة المحمولة */
/*@media (max-width: 768px) {*/
/*    #deleteConfirmModal .modal-content {*/
/*        margin: 10px;*/
/*        max-width: calc(100vw - 20px);*/
/*    }*/
    
/*    .btn-delete {*/
/*        padding: 4px 8px;*/
/*        font-size: 11px;*/
/*    }*/
    
/*    .table th:last-child,*/
/*    .table td:last-child {*/
/*        width: 80px;*/
/*    }*/
/*}*/


/* إضافة أنماط CSS للتحسينات */
.table tbody tr.table-row {
    transition: var(--transition);
}

.table tbody tr.table-row:hover {
    background: rgba(49, 130, 206, 0.05);
}

.table .actions-cell {
    text-align: center;
    width: 100px;
    padding: 8px;
}

.table .selector-cell {
    cursor: pointer;
    position: relative;
}

.table .selector-cell:hover code {
    background: #1e293b !important;
}

.btn-delete {
    transition: var(--transition);
    border: none;
    cursor: pointer;
}

.btn-delete:hover {
    background: #c53030 !important;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-delete:active {
    transform: translateY(0);
}

/* منع تأثير النقر على الخلايا التي تحتوي على أزرار */
.actions-cell {
    cursor: default !important;
}

.actions-cell:hover {
    background: transparent !important;
}

/* تحسين مظهر الجدول */
.table tbody tr td:not(.actions-cell) {
    cursor: pointer;
}

.table tbody tr td:not(.actions-cell):hover {
    background: rgba(49, 130, 206, 0.02);
}

/* تحسين النافذة المنبثقة */
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
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: var(--transition);
    box-shadow: var(--shadow-lg);
}

.modal.active .modal-content {
    transform: scale(1);
}

.modal-header {
    padding: 24px 24px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-light);
    margin-bottom: 0;
    padding-bottom: 16px;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
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
    display: flex;
    align-items: center;
    gap: 6px;
}

.modal-close:hover {
    background: #c53030;
}

.modal-body {
    padding: 24px;
}
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $is_rtl ? 'منصة التحليلات' : 'Analytics Platform'; ?></h1>
                <p><?php echo $is_rtl ? 'إدارة تتبع العناصر' : 'Element Tracking Management'; ?></p>
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
                        <div class="user-role"><?php echo $is_rtl ? 'مدير التتبع' : 'Tracking Manager'; ?></div>
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
                        <i class="fas fa-cogs"></i>
                        <?php echo $is_rtl ? 'إدارة تتبع العناصر' : 'Manage Element Tracking'; ?>
                    </h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&id=<?php echo $website_id; ?>" class="lang-switcher">
                        <i class="fas fa-language"></i>
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </header>
            
            <!-- منطقة العمل -->
            <div class="workspace">
                <!-- لوحة التحكم -->
                <div class="control-panel">
                    <!-- قسم إضافة عنصر جديد -->
                    <div class="panel-section">
                        <div class="panel-header">
                            <i class="fas fa-plus-circle" style="color: var(--success-color);"></i>
                            <h3 class="panel-title"><?php echo $is_rtl ? 'إضافة عنصر جديد' : 'Add New Element'; ?></h3>
                        </div>
                        <p class="panel-description">
                            <?php echo $is_rtl ? 'حدد العناصر التي تريد تتبعها من خلال النقر عليها في معاينة الموقع أو كتابة المحدد يدوياً' : 'Select elements to track by clicking on them in the website preview or by writing the selector manually'; ?>
                        </p>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form id="el-form" method="POST">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    <?php echo $is_rtl ? 'وصف العنصر' : 'Element Description'; ?>
                                </label>
                                <input type="text" name="name" id="el-name" class="form-input" 
                                       placeholder="<?php echo $is_rtl ? 'مثلاً: زر أضف للسلة' : 'e.g: Add to Cart Button'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-code"></i>
                                    CSS Selector
                                </label>
                                <input type="text" name="selector" id="el-selector" class="form-input selector-input" 
                                       placeholder="<?php echo $is_rtl ? 'مثلاً: .btn-add-to-cart' : 'e.g: .btn-add-to-cart'; ?>" required>
                            </div>
                            
                            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                                <button type="button" id="start-select" class="btn btn-outline btn-full">
                                    <i class="fas fa-crosshairs"></i>
                                    <?php echo $is_rtl ? 'اختيار العنصر' : 'Select Element'; ?>
                                </button>
                            </div>
                            
                            <button type="submit" name="save" class="btn btn-success btn-full">
                                <i class="fas fa-save"></i>
                                <?php echo $is_rtl ? 'حفظ العنصر' : 'Save Element'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- قسم العناصر المسجلة -->
                    <div class="panel-section">
                        <div class="panel-header">
                            <i class="fas fa-list" style="color: var(--info-color);"></i>
                            <h3 class="panel-title"><?php echo $is_rtl ? 'العناصر المسجلة' : 'Registered Elements'; ?></h3>
                        </div>
                        <p class="panel-description">
                            <?php echo $is_rtl ? 'قائمة بجميع العناصر المسجلة للتتبع' : 'List of all registered elements for tracking'; ?>
                        </p>
                        
                        <?php if (empty($elements)): ?>
                            <div class="empty-state">
                                <i class="fas fa-mouse-pointer"></i>
                                <h3><?php echo $is_rtl ? 'لا توجد عناصر' : 'No Elements'; ?></h3>
                                <p><?php echo $is_rtl ? 'لم يتم تسجيل أي عناصر للتتبع بعد' : 'No elements have been registered for tracking yet'; ?></p>
                            </div>
                        <?php else: ?>

<div class="elements-table">
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
            <tr class="table-row" data-element-id="<?= $el['id'] ?>">
                <td>
                    <span style="font-family: monospace; font-weight: 600; color: var(--accent-color);">
                        <?= $el['id'] ?>
                    </span>
                </td>
                <td>
                    <strong><?= htmlspecialchars($el['name']) ?></strong>
                </td>
                <td class="selector-cell">
                    <code><?= htmlspecialchars($el['selector']) ?></code>
                </td>
                <td>
                    <small style="color: var(--text-secondary);">
                        <?= date('M j, Y', strtotime($el['created_at'])) ?>
                    </small>
                </td>
                <td class="actions-cell">
                    <button class="btn btn-sm btn-delete" 
                            onclick="confirmDeleteElement(<?= $el['id'] ?>, '<?= htmlspecialchars($el['name'], ENT_QUOTES) ?>')"
                            style="background: var(--error-color); color: white; padding: 6px 10px; font-size: 12px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                        <?php endif; ?>
                    </div>
                    
                    
                    <!-- قسم معلومات الموقع -->
                    <div class="panel-section">
                        <div class="panel-header">
                            <i class="fas fa-globe" style="color: var(--warning-color);"></i>
                            <h3 class="panel-title"><?php echo $is_rtl ? 'معلومات الموقع' : 'Website Info'; ?></h3>
                        </div>
                        
                        <div style="background: var(--background); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 40px; height: 40px; background: var(--gradient-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px; font-weight: 700;">
                                        <?= htmlspecialchars($website['name']) ?>
                                    </h4>
                                    <p style="margin: 0; font-size: 14px; color: var(--text-secondary);">
                                        <?= htmlspecialchars($domain) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                                <div>
                                    <span style="color: var(--text-secondary);"><?php echo $is_rtl ? 'العناصر:' : 'Elements:'; ?></span>
                                    <strong style="color: var(--accent-color);"><?= count($elements) ?></strong>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary);"><?php echo $is_rtl ? 'الحالة:' : 'Status:'; ?></span>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-circle"></i>
                                        <?php echo $is_rtl ? 'نشط' : 'Active'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="tracking_stats.php?id=<?php echo $website_id; ?>" class="btn btn-primary btn-full">
                            <i class="fas fa-chart-bar"></i>
                            <?php echo $is_rtl ? 'عرض الإحصائيات' : 'View Statistics'; ?>
                        </a>
                    </div>
                </div>
                
                   
                    
                    <iframe id="site-frame" class="preview-frame"
                            src="proxy.php?url=https://<?= urlencode($domain) ?>">
                    </iframe>
                </div>
            </div>
        </main>
    </div>

<!-- 3. إضافة نافذة تأكيد الحذف (نفس النافذة المستخدمة في الصفحة الأولى) -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color: var(--error-color);"></i>
                <?php echo $is_rtl ? 'تأكيد الحذف' : 'Confirm Deletion'; ?>
            </h3>
            <button class="modal-close" onclick="closeDeleteModal()">
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
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">
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


    <script>
        // متغيرات عامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        
        // إدارة العناصر
        class ElementTrackingManager {
            constructor() {
                this.selecting = false;
                this.highlightBox = null;
                this.iframe = document.getElementById('site-frame');
                this.selectorInput = document.getElementById('el-selector');
                this.urlInput = document.getElementById('preview-url');
                this.loadBtn = document.getElementById('load-preview');
                this.selectBtn = document.getElementById('start-select');
                this.previewArea = document.querySelector('.preview-area');
                this.statusIndicator = document.getElementById('selection-status');
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.updateButtonText();
            }
            
            setupEventListeners() {
                // زر اختيار العنصر
                this.selectBtn.addEventListener('click', () => this.toggleSelection());
                
                // زر تحديث المعاينة
                this.loadBtn.addEventListener('click', () => this.loadPreview());
                
                // تحميل الإطار
                this.iframe.addEventListener('load', () => this.setupIframeEvents());
                
                // مفاتيح سريعة
                document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
            }
            
            toggleSelection() {
                this.selecting = !this.selecting;
                this.updateButtonText();
                this.updateStatus();
                
                if (this.selecting) {
                    this.createHighlightBox();
                    this.showToast(isRTL ? 'انقر على العنصر المراد تتبعه' : 'Click on the element you want to track', 'info');
                } else {
                    this.removeHighlightBox();
                }
            }
            
            updateButtonText() {
                if (this.selecting) {
                    this.selectBtn.innerHTML = '<i class="fas fa-times"></i> ' + (isRTL ? 'إلغاء الاختيار' : 'Cancel Selection');
                    this.selectBtn.classList.add('selecting');
                } else {
                    this.selectBtn.innerHTML = '<i class="fas fa-crosshairs"></i> ' + (isRTL ? 'اختيار العنصر' : 'Select Element');
                    this.selectBtn.classList.remove('selecting');
                }
            }
            
            updateStatus() {
                if (this.selecting) {
                    this.statusIndicator.style.display = 'block';
                } else {
                    this.statusIndicator.style.display = 'none';
                }
            }
            
            createHighlightBox() {
                if (!this.highlightBox) {
                    this.highlightBox = document.createElement('div');
                    this.highlightBox.className = 'highlight';
                    this.previewArea.appendChild(this.highlightBox);
                }
            }
            
            removeHighlightBox() {
                if (this.highlightBox) {
                    this.highlightBox.remove();
                    this.highlightBox = null;
                }
            }
            
            loadPreview() {
                const url = this.urlInput.value.trim();
                if (!url) {
                    this.showToast(isRTL ? 'أدخل عنوان موقع صحيح' : 'Enter a valid website URL', 'error');
                    return;
                }
                
                const fullUrl = url.match(/^https?:\/\//) ? url : 'https://' + url;
                
                this.loadBtn.classList.add('loading');
                this.iframe.src = 'proxy.php?url=' + encodeURIComponent(fullUrl);
                
                // إزالة حالة التحميل بعد 3 ثوان
                setTimeout(() => {
                    this.loadBtn.classList.remove('loading');
                }, 3000);
            }
            
            setupIframeEvents() {
                try {
                    const doc = this.iframe.contentDocument;
                    if (!doc) return;
                    
                    // تتبع حركة الماوس
                    doc.addEventListener('mousemove', (e) => this.handleMouseMove(e));
                    
                    // تتبع النقرات
                    doc.addEventListener('click', (e) => this.handleClick(e), true);
                    
                    // منع التمرير أثناء الاختيار
                    doc.addEventListener('scroll', (e) => {
                        if (this.selecting) {
                            this.updateHighlight();
                        }
                    });
                    
                } catch (error) {
                    console.warn('Cannot access iframe content:', error);
                }
            }
            
            handleMouseMove(e) {
                if (!this.selecting || !this.highlightBox) return;
                
                const rect = e.target.getBoundingClientRect();
                const iframeRect = this.iframe.getBoundingClientRect();
                
                this.highlightBox.style.top = (rect.top + iframeRect.top) + 'px';
                this.highlightBox.style.left = (rect.left + iframeRect.left) + 'px';
                this.highlightBox.style.width = rect.width + 'px';
                this.highlightBox.style.height = rect.height + 'px';
            }
            
            handleClick(e) {
                if (!this.selecting) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const selector = this.computeSelector(e.target);
                this.selectorInput.value = selector;
                
                // إيقاف الاختيار
                this.selecting = false;
                this.updateButtonText();
                this.updateStatus();
                this.removeHighlightBox();
                
                // تركيز على حقل الوصف إذا كان فارغاً
                const nameInput = document.getElementById('el-name');
                if (!nameInput.value.trim()) {
                    nameInput.focus();
                }
                
                this.showToast(isRTL ? 'تم اختيار العنصر بنجاح' : 'Element selected successfully', 'success');
            }
            
            computeSelector(element) {
                // إذا كان للعنصر ID فريد، استخدمه
                if (element.id) {
                    return '#' + element.id;
                }
                
                // إنشاء مسار CSS selector
                let path = [];
                let current = element;
                
                while (current && current.tagName && current.tagName.toLowerCase() !== 'html') {
                    let selector = current.tagName.toLowerCase();
                    
                    // إضافة الكلاسات إن وجدت
                    if (current.classList.length > 0) {
                        selector += '.' + Array.from(current.classList).join('.');
                    }
                    
                    // حساب الفهرس nth-of-type
                    let siblingIndex = 1;
                    let sibling = current;
                    while ((sibling = sibling.previousElementSibling)) {
                        if (sibling.tagName === current.tagName) {
                            siblingIndex++;
                        }
                    }
                    
                    // إضافة nth-of-type فقط إذا لم يكن العنصر فريداً
                    const sameTagSiblings = current.parentElement ? 
                        current.parentElement.querySelectorAll(current.tagName.toLowerCase()).length : 1;
                    
                    if (sameTagSiblings > 1) {
                        selector += `:nth-of-type(${siblingIndex})`;
                    }
                    
                    path.unshift(selector);
                    current = current.parentElement;
                }
                
                return path.join(' > ');
            }
            
            handleKeyboardShortcuts(e) {
                // Escape لإلغاء الاختيار
                if (e.key === 'Escape' && this.selecting) {
                    e.preventDefault();
                    this.toggleSelection();
                }
                
                // Ctrl/Cmd + E لبدء الاختيار
                if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                    e.preventDefault();
                    if (!this.selecting) {
                        this.toggleSelection();
                    }
                }
                
                // F5 لتحديث المعاينة
                if (e.key === 'F5') {
                    e.preventDefault();
                    this.loadPreview();
                }
            }
            
            showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    ${isRTL ? 'left: 20px;' : 'right: 20px;'}
                    background: var(--${type === 'success' ? 'success' : type === 'error' ? 'error' : 'info'}-color);
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
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    ${message}
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => toast.style.transform = 'translateX(0)', 100);
                setTimeout(() => {
                    toast.style.transform = `translateX(${isRTL ? '-' : ''}400px)`;
                    setTimeout(() => document.body.removeChild(toast), 300);
                }, 3000);
            }
        }
        
        // وظائف عامة
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        function logout() {
            if (confirm(isRTL ? 'هل أنت متأكد من تسجيل الخروج؟' : 'Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // تهيئة المدير عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            const manager = new ElementTrackingManager();
            
            // إضافة تأثيرات التحريك
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, { threshold: 0.1 });
            
            // مراقبة العناصر للتحريك
            document.querySelectorAll('.panel-section, .preview-area').forEach(el => {
                observer.observe(el);
            });
            
            // تحسين تجربة النموذج
            const form = document.getElementById('el-form');
            const nameInput = document.getElementById('el-name');
            const selectorInput = document.getElementById('el-selector');
            
            // تنظيف المدخلات عند الإرسال الناجح
            if (<?php echo $messageType === 'success' ? 'true' : 'false'; ?>) {
                nameInput.value = '';
                selectorInput.value = '';
                nameInput.focus();
            }
            
            // التحقق من صحة المحدد
            selectorInput.addEventListener('input', function() {
                try {
                    if (this.value.trim()) {
                        document.querySelector(this.value);
                        this.style.borderColor = 'var(--success-color)';
                    } else {
                        this.style.borderColor = 'var(--border-color)';
                    }
                } catch (e) {
                    this.style.borderColor = 'var(--error-color)';
                }
            });
            
              // تحسين تجربة الجدول - تعديل هذا الجزء
    const tableRows = document.querySelectorAll('.table tbody tr.table-row');
    tableRows.forEach(row => {
        // إضافة مستمع النقر فقط للخلايا التي ليست أزرار إجراءات
        const selectableCells = row.querySelectorAll('td:not(.actions-cell)');
        
        selectableCells.forEach(cell => {
            cell.addEventListener('click', function(e) {
                // التأكد من أن النقرة ليست على زر
                if (e.target.closest('button')) {
                    return;
                }
                
                // إزالة التحديد من الصفوف الأخرى
                tableRows.forEach(r => r.style.background = '');
                // تحديد الصف الحالي
                row.style.background = 'rgba(49, 130, 206, 0.1)';
                
                // نسخ المحدد إلى الحافظة
                const selectorCell = row.querySelector('.selector-cell code');
                if (selectorCell) {
                    const selector = selectorCell.textContent;
                    navigator.clipboard.writeText(selector).then(() => {
                        manager.showToast(isRTL ? 'تم نسخ المحدد' : 'Selector copied', 'success');
                    }).catch(() => {
                        console.log('Could not copy selector');
                    });
                }
            });
        });
    });


   // إضافة تأثير hover للخلايا القابلة للنقر
        selectableCells.forEach(cell => {
            cell.style.cursor = 'pointer';
            cell.addEventListener('mouseenter', function() {
                if (!row.style.background) {
                    row.style.background = 'rgba(49, 130, 206, 0.02)';
                }
            });
            cell.addEventListener('mouseleave', function() {
                if (row.style.background === 'rgba(49, 130, 206, 0.02)') {
                    row.style.background = '';
                }
            });
        });

        });
        
        // تحسين الاستجابة
        window.addEventListener('resize', function() {
            // إعادة ترتيب العناصر عند تغيير حجم النافذة
            const workspace = document.querySelector('.workspace');
            if (window.innerWidth <= 968) {
                workspace.style.flexDirection = 'column';
            } else {
                workspace.style.flexDirection = 'row';
            }
        });
        
        // حفظ حالة الرابط في localStorage
        function savePreviewUrl() {
            const url = document.getElementById('preview-url').value;
            localStorage.setItem('trackingPreviewUrl', url);
        }
        
        function loadPreviewUrl() {
            const savedUrl = localStorage.getItem('trackingPreviewUrl');
            if (savedUrl) {
                document.getElementById('preview-url').value = savedUrl;
            }
        }
        
        // حفظ واستعادة الرابط
        document.getElementById('preview-url').addEventListener('change', savePreviewUrl);
        document.addEventListener('DOMContentLoaded', loadPreviewUrl);
        
        
        
        
        
        
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