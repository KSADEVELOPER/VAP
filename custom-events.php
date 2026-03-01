<?php
// custom-events.php - صفحة إدارة الأحداث المخصصة
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
// require_once 'classes/CustomEventsManager.php';

// $customEventsManager = new CustomEventsManager($db);


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

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_POST['action']) {
        case 'add_custom_event':
            $result = $websiteManager->addCustomEvent($website_id, $user_id, $_POST);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'update_custom_event':
            $result = $websiteManager->updateCustomEvent($_POST['event_id'], $website_id, $user_id, $_POST);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'delete_custom_event':
            $result = $websiteManager->deleteCustomEvent($_POST['event_id'], $website_id, $user_id);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'toggle_event_status':
            $event_id = $_POST['event_id'];
            $event = $db->fetchOne("SELECT * FROM custom_event_definitions WHERE id = ? AND website_id = ?", [$event_id, $website_id]);
            if ($event) {
                $new_status = $event['is_active'] ? 0 : 1;
                $db->query("UPDATE custom_event_definitions SET is_active = ? WHERE id = ?", [$new_status, $event_id]);
                echo json_encode(['success' => true, 'new_status' => $new_status], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'الحدث غير موجود'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            
        case 'get_event_stats':
            $stats = $websiteManager->getCustomEventStats($website_id, $_POST['days'] ?? 30);
            echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'test_selector':
            // اختبار CSS selector على الموقع
            $selector = $_POST['selector'] ?? '';
            $url = $website['url'];
            
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'Mozilla/5.0 (compatible; AnalyticsBot/1.0)'
                    ]
                ]);
                
                $content = file_get_contents($url, false, $context);
                
                if ($content) {
                    // محاكاة اختبار الـ selector
                    $found = strpos($content, 'class=') !== false || strpos($content, 'id=') !== false;
                    echo json_encode([
                        'success' => true, 
                        'found' => $found,
                        'message' => $found ? 'تم العثور على عناصر محتملة' : 'لم يتم العثور على عناصر'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'error' => 'لا يمكن الوصول للموقع'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'خطأ في الاتصال'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            
        case 'get_events_stats':
            $website_id = $_POST['website_id'] ?? 0;
            $days = $_POST['days'] ?? 30;
            $stats = $websiteManager->getCustomEventStats($website_id, $days);
            echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
            exit;
            
            
             // جلب الاقتراحات (مؤقتاً نرسل اقتراحات افتراضية)
            $suggestions = [
                [
                    'selector' => 'button[onclick*="addToCart"]',
                    'element_text' => 'أضف إلى السلة',
                    'confidence' => 95,
                    'element_type' => 'cart_buttons'
                ],
                [
                    'selector' => '.add-to-cart, .btn-cart',
                    'element_text' => 'Add to Cart',
                    'confidence' => 90,
                    'element_type' => 'cart_buttons'
                ],
                [
                    'selector' => 'button[onclick*="wishlist"], .wishlist-btn',
                    'element_text' => 'قائمة الأمنيات',
                    'confidence' => 85,
                    'element_type' => 'wishlist_buttons'
                ],
                [
                    'selector' => 'form[action*="contact"], #contact-form',
                    'element_text' => 'نموذج الاتصال',
                    'confidence' => 80,
                    'element_type' => 'contact_forms'
                ]
            ];
            
            echo json_encode(['success' => true, 'suggestions' => $suggestions], JSON_UNESCAPED_UNICODE);
            exit;

    }
}

// الحصول على الأحداث المخصصة الحالية
$custom_events = $websiteManager->getCustomEvents($website_id, $user_id);
$event_stats = $websiteManager->getCustomEventStats($website_id, 30);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'الأحداث المخصصة - ' . htmlspecialchars($website['name']) : 'Custom Events - ' . htmlspecialchars($website['name']); ?></title>
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
            padding: 12px 20px;
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
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--error-color);
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
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .card {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
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
        
        .event-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 16px;
            position: relative;
            transition: var(--transition);
        }
        
        .event-card.active {
            border-color: var(--success-color);
            background: rgba(56, 161, 105, 0.05);
        }
        
        .event-card.inactive {
            opacity: 0.6;
            border-color: var(--border-color);
        }
        
        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .event-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .event-type {
            display: inline-block;
            padding: 4px 8px;
            background: var(--accent-color);
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .event-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 8px;
        }
        
        .event-status.active {
            background: rgba(56, 161, 105, 0.2);
            color: var(--success-color);
        }
        
        .event-status.inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
        }
        
        .event-selector {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            color: var(--text-secondary);
            background: rgba(0,0,0,0.05);
            padding: 8px;
            border-radius: 4px;
            margin: 8px 0;
            word-break: break-all;
        }
        
        .event-description {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.4;
        }
        
        .event-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        
        .stat-item {
            text-align: center;
            padding: 8px;
            background: rgba(49, 130, 206, 0.1);
            border-radius: 6px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-color);
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .event-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: var(--surface);
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
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
            transition: var(--transition);
        }
        
        .modal-close:hover {
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
        
        .selector-helper {
            background: rgba(49, 130, 206, 0.1);
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
        }
        
        .selector-examples {
            margin-top: 12px;
        }
        
        .selector-examples h4 {
            font-size: 14px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .selector-example {
            display: inline-block;
            background: var(--surface);
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            font-family: 'Monaco', monospace;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .selector-example:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
        
        .test-result {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .test-result.success {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
        }
        
        .test-result.error {
            background: rgba(229, 62, 62, 0.1);
            color: var(--error-color);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .event-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .event-actions {
                justify-content: flex-start;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 10px;
                max-width: none;
            }
        }
        
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
    </style>
</head>
<body>
    <div class="container">
        <!-- الرأس -->
        <div class="header">
            <div class="header-content">
                <div class="website-info">
                    <h1><?php echo $is_rtl ? 'الأحداث المخصصة - ' : 'Custom Events - '; ?><?php echo htmlspecialchars($website['name']); ?></h1>
                    <p>
                        <i class="fas fa-globe"></i>
                        <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($website['domain']); ?>
                        </a>
                        - <?php echo $is_rtl ? 'تتبع الأحداث المخصصة والتفاعلات' : 'Track custom events and interactions'; ?>
                    </p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddEventModal()">
                        <i class="fas fa-plus"></i>
                        <?php echo $is_rtl ? 'إضافة حدث جديد' : 'Add New Event'; ?>
                    </button>
                    <a href="analytics.php?id=<?php echo $website_id; ?>" class="btn btn-outline">
                        <i class="fas fa-chart-line"></i>
                        <?php echo $is_rtl ? 'عرض التحليلات' : 'View Analytics'; ?>
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                        <?php echo $is_rtl ? 'العودة للوحة التحكم' : 'Back to Dashboard'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <?php if (!empty($event_stats)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    <?php echo $is_rtl ? 'إحصائيات الأحداث المخصصة - آخر 30 يوم' : 'Custom Events Stats - Last 30 Days'; ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="event-stats">
                    <?php 
                    $total_triggers = array_sum(array_column($event_stats, 'total_triggers'));
                    $total_sessions = array_sum(array_column($event_stats, 'unique_sessions'));
                    $active_events = count(array_filter($event_stats, function($e) { return $e['total_triggers'] > 0; }));
                    ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($total_triggers); ?></span>
                        <span class="stat-label"><?php echo $is_rtl ? 'إجمالي التفعيلات' : 'Total Triggers'; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $total_sessions; ?></span>
                        <span class="stat-label"><?php echo $is_rtl ? 'جلسات فريدة' : 'Unique Sessions'; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $active_events; ?></span>
                        <span class="stat-label"><?php echo $is_rtl ? 'أحداث نشطة' : 'Active Events'; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($custom_events); ?></span>
                        <span class="stat-label"><?php echo $is_rtl ? 'إجمالي الأحداث' : 'Total Events'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- قائمة الأحداث المخصصة -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    <?php echo $is_rtl ? 'الأحداث المخصصة' : 'Custom Events'; ?>
                </h3>
                <span class="badge" style="background: var(--accent-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                    <?php echo count($custom_events); ?> <?php echo $is_rtl ? 'حدث' : 'events'; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($custom_events)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bolt"></i>
                        <h3><?php echo $is_rtl ? 'لا توجد أحداث مخصصة بعد' : 'No custom events yet'; ?></h3>
                        <p><?php echo $is_rtl ? 'ابدأ بإضافة أول حدث مخصص لتتبع التفاعلات المهمة في موقعك' : 'Start by adding your first custom event to track important interactions on your website'; ?></p>
                        <button class="btn btn-primary" onclick="openAddEventModal()">
                            <i class="fas fa-plus"></i>
                            <?php echo $is_rtl ? 'إضافة حدث جديد' : 'Add New Event'; ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div id="events-list">
                        <?php foreach ($custom_events as $event): ?>
                            <?php 
                            $event_stat = array_filter($event_stats, function($stat) use ($event) {
                                return $stat['event_name'] === $event['event_name'];
                            });
                            $event_stat = !empty($event_stat) ? array_values($event_stat)[0] : [
                                'total_triggers' => 0,
                                'total_value' => 0,
                                'unique_sessions' => 0
                            ];
                            ?>
                            <div class="event-card <?php echo $event['is_active'] ? 'active' : 'inactive'; ?>" data-event-id="<?php echo $event['id']; ?>">
                                <div class="event-header">
                                    <div>
                                        <div class="event-name"><?php echo htmlspecialchars($event['event_display_name']); ?></div>
                                        <div style="margin: 4px 0;">
                                            <span class="event-type"><?php echo ucfirst($event['event_type']); ?></span>
                                            <span class="event-status <?php echo $event['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $event['is_active'] ? ($is_rtl ? 'نشط' : 'Active') : ($is_rtl ? 'معطل' : 'Inactive'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <?php if (!$event['is_active']): ?>
                                            <button class="btn btn-success btn-sm" onclick="toggleEventStatus(<?php echo $event['id']; ?>)" title="<?php echo $is_rtl ? 'تفعيل' : 'Activate'; ?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" onclick="toggleEventStatus(<?php echo $event['id']; ?>)" title="<?php echo $is_rtl ? 'إيقاف' : 'Deactivate'; ?>">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm" style="background: var(--warning-color); color: white;" onclick="editEvent(<?php echo $event['id']; ?>)" title="<?php echo $is_rtl ? 'تعديل' : 'Edit'; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline btn-sm" onclick="testSelector('<?php echo htmlspecialchars($event['selector'], ENT_QUOTES); ?>')" title="<?php echo $is_rtl ? 'اختبار المحدد' : 'Test Selector'; ?>">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteEvent(<?php echo $event['id']; ?>)" title="<?php echo $is_rtl ? 'حذف' : 'Delete'; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="event-selector"><?php echo htmlspecialchars($event['selector']); ?></div>
                                
                                <?php if ($event['product_selector']): ?>
                                    <div style="margin: 8px 0;">
                                        <strong style="font-size: 13px;"><?php echo $is_rtl ? 'محدد المنتجات:' : 'Product Selector:'; ?></strong>
                                        <code style="background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 3px; font-size: 12px;"><?php echo htmlspecialchars($event['product_selector']); ?></code>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($event['description']): ?>
                                    <div class="event-description"><?php echo htmlspecialchars($event['description']); ?></div>
                                <?php endif; ?>
                                
                                <div class="event-stats">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo number_format($event_stat['total_triggers']); ?></span>
                                        <span class="stat-label"><?php echo $is_rtl ? 'تفعيلات' : 'Triggers'; ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo number_format($event_stat['total_value']); ?></span>
                                        <span class="stat-label"><?php echo $is_rtl ? 'القيمة الإجمالية' : 'Total Value'; ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo $event_stat['unique_sessions']; ?></span>
                                        <span class="stat-label"><?php echo $is_rtl ? 'جلسات فريدة' : 'Unique Sessions'; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- دليل الاستخدام -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb"></i>
                    <?php echo $is_rtl ? 'أمثلة وإرشادات' : 'Examples & Guidelines'; ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div>
                        <h4><?php echo $is_rtl ? 'أمثلة على CSS Selectors:' : 'CSS Selector Examples:'; ?></h4>
                        <div style="margin-top: 10px;">
                            <div class="selector-example" onclick="showSelectorInfo(this.dataset.info)" data-info="يستهدف عناصر بكلاس معين">
                                .add-to-cart
                            </div>
                            <div class="selector-example" onclick="showSelectorInfo(this.dataset.info)" data-info="يستهدف عنصر بمعرف معين">
                                #buy-button
                            </div>
                            <div class="selector-example" onclick="showSelectorInfo(this.dataset.info)" data-info="يستهدف عناصر بخاصية معينة">
                                [data-action="purchase"]
                            </div>
                            <div class="selector-example" onclick="showSelectorInfo(this.dataset.info)" data-info="يستهدف أزرار بكلاس معين">
                                button.cart-btn
                            </div>
                            <div class="selector-example" onclick="showSelectorInfo(this.dataset.info)" data-info="يستهدف روابط تحتوي على نص معين">
                                a[href*="contact"]
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4><?php echo $is_rtl ? 'نصائح مهمة:' : 'Important Tips:'; ?></h4>
                        <ul style="margin-right: 20px; margin-top: 10px; color: var(--text-secondary);">
                            <li><?php echo $is_rtl ? 'استخدم محددات محددة لتجنب التداخل' : 'Use specific selectors to avoid conflicts'; ?></li>
                            <li><?php echo $is_rtl ? 'اختبر المحدد دائماً قبل الحفظ' : 'Always test the selector before saving'; ?></li>
                            <li><?php echo $is_rtl ? 'استخدم أسماء وصفية للأحداث' : 'Use descriptive names for events'; ?></li>
                            <li><?php echo $is_rtl ? 'راقب الأحداث بانتظام لضمان عملها' : 'Monitor events regularly to ensure they work'; ?></li>
                        </ul>
                        
                        <div id="selector-info" style="margin-top: 15px; padding: 10px; background: rgba(49, 130, 206, 0.1); border-radius: 6px; display: none;">
                            <strong><?php echo $is_rtl ? 'معلومات المحدد:' : 'Selector Info:'; ?></strong>
                            <span id="selector-info-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل حدث -->
    <div id="event-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title"><?php echo $is_rtl ? 'إضافة حدث مخصص جديد' : 'Add New Custom Event'; ?></h3>
                <button class="modal-close" onclick="closeModal('event-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="event-form">
                    <input type="hidden" name="event_id" id="event_id">
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'اسم الحدث (تقني)' : 'Event Name (Technical)'; ?> <span style="color: var(--error-color);">*</span></label>
                        <input type="text" name="event_name" id="event_name" class="form-input" required 
                               placeholder="<?php echo $is_rtl ? 'مثال: cart_button_clicks' : 'Example: cart_button_clicks'; ?>"
                               pattern="[a-zA-Z0-9_]+" title="<?php echo $is_rtl ? 'أحرف إنجليزية وأرقام وشرطة سفلية فقط' : 'Letters, numbers and underscore only'; ?>">
                        <small style="color: var(--text-secondary); font-size: 12px;">
                            <?php echo $is_rtl ? 'استخدم أحرف إنجليزية وأرقام وشرطة سفلية فقط' : 'Use letters, numbers and underscore only'; ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'الاسم المعروض' : 'Display Name'; ?> <span style="color: var(--error-color);">*</span></label>
                        <input type="text" name="event_display_name" id="event_display_name" class="form-input" required 
                               placeholder="<?php echo $is_rtl ? 'مثال: نقرات زر إضافة للسلة' : 'Example: Add to Cart Button Clicks'; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'نوع الحدث' : 'Event Type'; ?> <span style="color: var(--error-color);">*</span></label>
                        <select name="event_type" id="event_type" class="form-select" required onchange="handleEventTypeChange(this.value)">
                            <option value=""><?php echo $is_rtl ? 'اختر نوع الحدث' : 'Select Event Type'; ?></option>
                            <option value="click_count"><?php echo $is_rtl ? 'عدد النقرات' : 'Click Count'; ?></option>
                            <option value="count_products"><?php echo $is_rtl ? 'عدد المنتجات' : 'Product Count'; ?></option>
                            <option value="form_submit"><?php echo $is_rtl ? 'إرسال نموذج' : 'Form Submit'; ?></option>
                            <option value="page_time"><?php echo $is_rtl ? 'وقت الصفحة' : 'Page Time'; ?></option>
                            <option value="scroll_depth"><?php echo $is_rtl ? 'عمق التمرير' : 'Scroll Depth'; ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'محدد العنصر (CSS Selector)' : 'Element Selector (CSS)'; ?> <span style="color: var(--error-color);">*</span></label>
                        <input type="text" name="selector" id="selector" class="form-input" required 
                               placeholder="<?php echo $is_rtl ? 'مثال: .add-to-cart, #buy-button' : 'Example: .add-to-cart, #buy-button'; ?>">
                        
                        
                        <button type="button" class="btn btn-outline btn-sm" onclick="showSmartSuggestions()" style="margin-top: 10px;">
    <i class="fas fa-magic"></i>
    <?php echo $is_rtl ? 'اقتراحات ذكية' : 'Smart Suggestions'; ?>
</button>

<div id="smart-suggestions" style="display: none; margin-top: 10px;">
    <!-- سيتم ملؤها بـ JavaScript -->
</div>


                        <div class="selector-helper">
                            <h4><?php echo $is_rtl ? 'أمثلة سريعة:' : 'Quick Examples:'; ?></h4>
                            <div class="selector-examples">
                                <div class="selector-example" onclick="setSelector(this.textContent)">.add-to-cart</div>
                                <div class="selector-example" onclick="setSelector(this.textContent)">#buy-now</div>
                                <div class="selector-example" onclick="setSelector(this.textContent)">[data-action="cart"]</div>
                                <div class="selector-example" onclick="setSelector(this.textContent)">button.purchase</div>
                                <div class="selector-example" onclick="setSelector(this.textContent)">.wishlist-btn</div>
                                <div class="selector-example" onclick="setSelector(this.textContent)">form.contact</div>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="testCurrentSelector()" style="margin-top: 10px;">
                                <i class="fas fa-search"></i>
                                <?php echo $is_rtl ? 'اختبار المحدد' : 'Test Selector'; ?>
                            </button>
                        </div>
                        
                        <div id="test-result"></div>
                    </div>

                    <div class="form-group" id="product-selector-group" style="display: none;">
                        <label class="form-label"><?php echo $is_rtl ? 'محدد المنتجات' : 'Product Selector'; ?></label>
                        <input type="text" name="product_selector" id="product_selector" class="form-input" 
                               placeholder="<?php echo $is_rtl ? 'مثال: .product-item, .cart-item' : 'Example: .product-item, .cart-item'; ?>">
                        <small style="color: var(--text-secondary); font-size: 12px;">
                            <?php echo $is_rtl ? 'مطلوب عند اختيار "عدد المنتجات"' : 'Required when selecting "Product Count"'; ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $is_rtl ? 'وصف الحدث' : 'Event Description'; ?></label>
                        <textarea name="description" id="description" class="form-textarea" rows="3" 
                                  placeholder="<?php echo $is_rtl ? 'وصف مختصر لما يقوم به هذا الحدث...' : 'Brief description of what this event does...'; ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('event-modal')">
                    <?php echo $is_rtl ? 'إلغاء' : 'Cancel'; ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()" id="save-btn">
                    <i class="fas fa-save"></i>
                    <?php echo $is_rtl ? 'حفظ الحدث' : 'Save Event'; ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const websiteId = <?php echo $website_id; ?>;
        let isEditMode = false;

        // فتح نافذة إضافة حدث جديد
        function openAddEventModal() {
            document.getElementById('modal-title').textContent = isRTL ? 'إضافة حدث مخصص جديد' : 'Add New Custom Event';
            document.getElementById('event-form').reset();
            document.getElementById('event_id').value = '';
            document.getElementById('product-selector-group').style.display = 'none';
            document.getElementById('test-result').innerHTML = '';
            isEditMode = false;
            openModal('event-modal');
        }

        // فتح نافذة تعديل حدث
        function editEvent(eventId) {
            const eventCard = document.querySelector(`[data-event-id="${eventId}"]`);
            if (!eventCard) return;

            // استخراج البيانات من البطاقة (في التطبيق الحقيقي، سيتم جلبها من الخادم)
            const eventName = eventCard.querySelector('.event-name').textContent;
            const eventType = eventCard.querySelector('.event-type').textContent.toLowerCase();
            const selector = eventCard.querySelector('.event-selector').textContent;

            document.getElementById('modal-title').textContent = isRTL ? 'تعديل الحدث المخصص' : 'Edit Custom Event';
            document.getElementById('event_id').value = eventId;
            document.getElementById('event_display_name').value = eventName;
            document.getElementById('event_type').value = eventType;
            document.getElementById('selector').value = selector;
            
            handleEventTypeChange(eventType);
            isEditMode = true;
            openModal('event-modal');
        }

        // فتح/إغلاق النوافذ
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // تغيير نوع الحدث
        function handleEventTypeChange(eventType) {
            const productSelectorGroup = document.getElementById('product-selector-group');
            if (eventType === 'count_products') {
                productSelectorGroup.style.display = 'block';
                document.getElementById('product_selector').required = true;
            } else {
                productSelectorGroup.style.display = 'none';
                document.getElementById('product_selector').required = false;
            }
        }

        // تعيين محدد من الأمثلة
        function setSelector(selector) {
            document.getElementById('selector').value = selector;
        }

        // اختبار المحدد الحالي
        function testCurrentSelector() {
            const selector = document.getElementById('selector').value;
            if (!selector.trim()) {
                showTestResult('يرجى إدخال محدد CSS أولاً', 'error');
                return;
            }
            testSelector(selector);
        }

        // اختبار CSS selector
        function testSelector(selector) {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isRTL ? 'جاري الاختبار...' : 'Testing...');
            resultDiv.className = 'test-result';

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: '1',
                    action: 'test_selector',
                    selector: selector
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showTestResult(data.message, data.found ? 'success' : 'error');
                } else {
                    showTestResult(data.error, 'error');
                }
            })
            .catch(error => {
                showTestResult(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }

        // عرض نتيجة الاختبار
        function showTestResult(message, type) {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = message;
            resultDiv.className = `test-result ${type}`;
        }

function saveEvent() {
    const form = document.getElementById('event-form');
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', isEditMode ? 'update_custom_event' : 'add_custom_event');

    const saveBtn = document.getElementById('save-btn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner"></span> ' + (isRTL ? 'جاري الحفظ...' : 'Saving...');


    fetch('./custom-events.php?id=' + websiteId, {
        method: 'POST',
        credentials: 'same-origin',               // إرسال الكوكيز
        headers: {
            'X-Requested-With': 'XMLHttpRequest', // يعلِم الخادم أنّه طلب AJAX شرعي
            'Accept': 'application/json'          // نطالب بالردّ JSON
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) throw new Error(res.status);
        return res.json(); 
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('event-modal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.errors?.join('<br>') || data.error, 'error');
        }
    })
    .catch(err => {
        console.error('saveEvent error', err);
        showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}
        // تبديل حالة الحدث
        function toggleEventStatus(eventId) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: '1',
                    action: 'toggle_event_status',
                    event_id: eventId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isRTL ? 'تم تحديث حالة الحدث' : 'Event status updated', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert(data.error, 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }

        // حذف حدث
        function deleteEvent(eventId) {
            if (!confirm(isRTL ? 'هل تريد حذف هذا الحدث؟ سيتم حذف جميع البيانات المرتبطة به.' : 'Do you want to delete this event? All associated data will be deleted.')) {
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: '1',
                    action: 'delete_custom_event',
                    event_id: eventId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert(data.error, 'error');
                }
            })
            .catch(error => {
                showAlert(isRTL ? 'حدث خطأ في الاتصال' : 'Connection error', 'error');
            });
        }

        // عرض معلومات المحدد
        function showSelectorInfo(info) {
            const infoDiv = document.getElementById('selector-info');
            const textSpan = document.getElementById('selector-info-text');
            textSpan.textContent = info;
            infoDiv.style.display = 'block';
        }

        // عرض التنبيهات
        function showAlert(message, type = 'success') {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;

            document.querySelector('.container').insertBefore(alert, document.querySelector('.header').nextSibling);

            setTimeout(() => {
                alert.remove();
            }, 5000);

            document.querySelector('.container').scrollTop = 0;
        }

        // إغلاق النوافذ عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });

        // منع إرسال النموذج عند الضغط على Enter
        document.getElementById('event-form').addEventListener('submit', function(e) {
            e.preventDefault();
            saveEvent();
        });
        
        
function showSmartSuggestions() {
    // استخدم window.location.href للتأكد من الرابط الصحيح
    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            ajax: '1',
            action: 'get_smart_suggestions',
            website_id: getWebsiteIdFromUrl() // دالة للحصول على ID من الرابط
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displaySmartSuggestions(data.suggestions);
        } else {
            console.error('Error:', data.error);
            alert(data.error || 'حدث خطأ');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('حدث خطأ في الاتصال');
    });
}


// دالة للحصول على website_id من الرابط
function getWebsiteIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || 0;
}


function displaySmartSuggestions(suggestions) {
    const container = document.getElementById('smart-suggestions');
    
    if (!container) {
        console.error('Container smart-suggestions not found');
        return;
    }
    
    let html = '<h5 style="margin: 10px 0;">العناصر المكتشفة تلقائياً:</h5>';
    
    if (!suggestions || suggestions.length === 0) {
        html += '<p style="color: #666;">لا توجد اقتراحات متاحة حالياً</p>';
    } else {
        suggestions.forEach(suggestion => {
            html += `
                <div class="suggestion-item" style="
                    padding: 10px; 
                    border: 1px solid #ddd; 
                    margin: 5px 0; 
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                " 
                onclick="selectSuggestion('${suggestion.selector.replace(/'/g, "\\'")}')">
                    <code style="color: #007cba; font-weight: bold;">
                        ${suggestion.selector}
                    </code>
                    <br>
                    <small style="color: #666;">
                        "${suggestion.element_text}" - ثقة: ${suggestion.confidence}%
                        <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin-left: 5px;">
                            ${getElementTypeLabel(suggestion.element_type)}
                        </span>
                    </small>
                </div>
            `;
        });
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

function selectSuggestion(selector) {
    const selectorInput = document.getElementById('selector');
    if (selectorInput) {
        selectorInput.value = selector;
        document.getElementById('smart-suggestions').style.display = 'none';
        
        // إظهار رسالة تأكيد
        showAlert('تم تطبيق الاقتراح بنجاح!', 'success');
    }
}
function getElementTypeLabel(elementType) {
    const labels = {
        'cart_buttons': 'أزرار السلة',
        'wishlist_buttons': 'قائمة الأمنيات', 
        'contact_forms': 'نماذج الاتصال',
        'product_items': 'عناصر المنتجات'
    };
    
    return labels[elementType] || elementType;
}



    </script>
</body>
</html>