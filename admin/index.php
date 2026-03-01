<?php
// admin/index.php
require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/WebsiteManager.php';
require_once '../classes/PlatformManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$platformManager = new PlatformManager($db);

// التحقق من تسجيل الدخول وصلاحيات الإدارة
if (!$userManager->isLoggedIn() || !$userManager->isAdmin()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user = $userManager->getUserById($user_id);

// جلب الإحصائيات العامة
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'active_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
    'total_websites' => $db->fetchOne("SELECT COUNT(*) as count FROM websites")['count'],
    'approved_websites' => $db->fetchOne("SELECT COUNT(*) as count FROM websites WHERE status = 'approved'")['count'],
    'pending_websites' => $db->fetchOne("SELECT COUNT(*) as count FROM websites WHERE status = 'pending'")['count'],
    'total_platforms' => $db->fetchOne("SELECT COUNT(*) as count FROM platforms")['count']
];

// تحديد اللغة
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
$is_rtl = $lang === 'ar';

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_POST['action']) {
        case 'approve_website':
            $result = $websiteManager->updateWebsiteStatus($_POST['website_id'], 'approved');
            echo json_encode(['success' => $result], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'reject_website':
            $result = $websiteManager->updateWebsiteStatus($_POST['website_id'], 'rejected');
            echo json_encode(['success' => $result], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'toggle_user_status':
            $result = $userManager->toggleUserStatus($_POST['user_id']);
            echo json_encode(['success' => $result], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'delete_user':
            $result = $userManager->deleteUser($_POST['user_id']);
            echo json_encode(['success' => $result], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'add_platform':
            $result = $platformManager->addPlatform($_POST, $user_id);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'delete_platform':
            $result = $platformManager->deletePlatform($_POST['platform_id']);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'لوحة الإدارة - ' . SITE_NAME : 'Admin Panel - ' . SITE_NAME_EN; ?></title>
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
            --background: #f7fafc;
            --surface: #ffffff;
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
        
        .admin-dashboard {
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
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
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
        
        .user-details {
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
            color: var(--accent-color);
            font-weight: 600;
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
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(49, 130, 206, 0.1), transparent);
            border-radius: 0 0 0 100px;
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
            background: linear-gradient(90deg, rgba(49, 130, 206, 0.05), transparent);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* الأزرار */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
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
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--error-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* الجدول */
        .table {
            width: 100%;
            border-collapse: collapse;
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
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        /* التبويبات */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* الحالات */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
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
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><?php echo $is_rtl ? 'لوحة الإدارة' : 'Admin Panel'; ?></h1>
                <p><?php echo $is_rtl ? SITE_NAME : SITE_NAME_EN; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo $is_rtl ? 'الرئيسية' : 'Dashboard'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="users">
                    <i class="fas fa-users"></i>
                    <?php echo $is_rtl ? 'المستخدمون' : 'Users'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="websites">
                    <i class="fas fa-globe"></i>
                    <?php echo $is_rtl ? 'المواقع' : 'Websites'; ?>
                </a>
                <a href="#" class="nav-item" data-tab="platforms">
                    <i class="fas fa-layer-group"></i>
                    <?php echo $is_rtl ? 'المنصات' : 'Platforms'; ?>
                </a>
                <a href="../dashboard.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <?php echo $is_rtl ? 'لوحة المستخدم' : 'User Panel'; ?>
                </a>
            </nav>
            
            <div class="user-info">
                <div class="user-details">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-role"><?php echo $is_rtl ? 'مدير النظام' : 'Administrator'; ?></div>
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
                    <h2 id="page-title"><?php echo $is_rtl ? 'لوحة الإدارة' : 'Admin Dashboard'; ?></h2>
                </div>
                
                <div class="topbar-actions">
                    <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>" class="btn btn-sm btn-primary">
                        <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                    </a>
                </div>
            </header>
            
            <!-- منطقة المحتوى -->
            <div class="content-area">
                <!-- تبويب الرئيسية -->
                <div id="dashboard-tab" class="tab-content active">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'مرحباً بك في لوحة الإدارة' : 'Welcome to Admin Panel'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'إدارة شاملة لجميع جوانب المنصة' : 'Complete management of all platform aspects'; ?></p>
                    </div>
                    
                    <!-- الإحصائيات -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'إجمالي المستخدمين' : 'Total Users'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'المستخدمون النشطون' : 'Active Users'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['total_websites']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'إجمالي المواقع' : 'Total Websites'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['pending_websites']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'المواقع قيد المراجعة' : 'Pending Websites'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['approved_websites']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'المواقع المعتمدة' : 'Approved Websites'; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="stat-value"><?php echo $stats['total_platforms']; ?></div>
                            <div class="stat-label"><?php echo $is_rtl ? 'المنصات المتاحة' : 'Available Platforms'; ?></div>
                        </div>
                    </div>
                    
                    <!-- المواقع قيد المراجعة -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'المواقع قيد المراجعة' : 'Websites Pending Review'; ?></h3>
                        </div>
                        <div class="card-body">
                            <div id="pending-websites">
                                <!-- سيتم تحميل المواقع قيد المراجعة هنا -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- تبويب المستخدمون -->
                <div id="users-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'إدارة المستخدمين' : 'User Management'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'إدارة حسابات المستخدمين وصلاحياتهم' : 'Manage user accounts and permissions'; ?></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'جميع المستخدمين' : 'All Users'; ?></h3>
                        </div>
                        <div class="card-body">
                            <div id="users-list">
                                <!-- سيتم تحميل المستخدمين هنا -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- تبويب المواقع -->
                <div id="websites-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'إدارة المواقع' : 'Website Management'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'مراجعة واعتماد المواقع المسجلة' : 'Review and approve registered websites'; ?></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'جميع المواقع' : 'All Websites'; ?></h3>
                        </div>
                        <div class="card-body">
                            <div id="websites-list">
                                <!-- سيتم تحميل المواقع هنا -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- تبويب المنصات -->
                <div id="platforms-tab" class="tab-content">
                    <div class="page-header">
                        <h1 class="page-title"><?php echo $is_rtl ? 'إدارة المنصات' : 'Platform Management'; ?></h1>
                        <p class="page-subtitle"><?php echo $is_rtl ? 'إضافة وإدارة قوالب المنصات المختلفة' : 'Add and manage different platform templates'; ?></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $is_rtl ? 'المنصات المتاحة' : 'Available Platforms'; ?></h3>
                            <button class="btn btn-primary" onclick="openAddPlatformModal()">
                                <i class="fas fa-plus"></i>
                                <?php echo $is_rtl ? 'إضافة منصة جديدة' : 'Add New Platform'; ?>
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="platforms-list">
                                <!-- سيتم تحميل المنصات هنا -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- نافذة إضافة منصة جديدة -->
    <div id="add-platform-modal" class="modal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-content" style="background: var(--surface); border-radius: var(--border-radius-lg); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="padding: 24px 24px 0; display: flex; align-items: center; justify-content: space-between;">
                <h3 class="modal-title" style="font-size: 20px; font-weight: 700; color: var(--text-primary);"><?php echo $is_rtl ? 'إضافة منصة جديدة' : 'Add New Platform'; ?></h3>
                <button class="modal-close" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer;" onclick="closeModal('add-platform-modal')">&times;</button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="add-platform-form">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-primary); font-size: 14px;"><?php echo $is_rtl ? 'اسم المنصة (عربي)' : 'Platform Name (Arabic)'; ?></label>
                        <input type="text" name="name" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-primary); font-size: 14px;"><?php echo $is_rtl ? 'اسم المنصة (إنجليزي)' : 'Platform Name (English)'; ?></label>
                        <input type="text" name="name_en" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-primary); font-size: 14px;"><?php echo $is_rtl ? 'الوصف (عربي)' : 'Description (Arabic)'; ?></label>
                        <textarea name="description" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; min-height: 80px; resize: vertical;"></textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-primary); font-size: 14px;"><?php echo $is_rtl ? 'الوصف (إنجليزي)' : 'Description (English)'; ?></label>
                        <textarea name="description_en" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; min-height: 80px; resize: vertical;"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding: 0 24px 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal('add-platform-modal')"><?php echo $is_rtl ? 'إلغاء' : 'Cancel'; ?></button>
                <button class="btn btn-primary" onclick="addPlatform()"><?php echo $is_rtl ? 'إضافة المنصة' : 'Add Platform'; ?></button>
            </div>
        </div>
    </div>
    
    <script>
    
        // المتغيرات العامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const lang = '<?php echo $lang; ?>';
        
        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingWebsites();
            
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
        
        // إظهار التبويبات
        function showTab(tabName) {
            // إخفاء جميع التبويبات
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // إزالة الفئة النشطة من جميع عناصر التنقل
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => item.classList.remove('active'));
            
            // إظهار التبويب المحدد
            const targetTab = document.getElementById(tabName + '-tab');
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            // تفعيل عنصر التنقل المحدد
            const activeNavItem = document.querySelector(`.nav-item[data-tab="${tabName}"]`);
            if (activeNavItem) {
                activeNavItem.classList.add('active');
            }
            
            // تحديث عنوان الصفحة
            updatePageTitle(tabName);
            
            // تحميل المحتوى حسب التبويب
      
            
             switch(tabName) {
        case 'dashboard':
            loadPendingWebsites();
            break;
        case 'users':
            loadUsers();
            break;
        case 'websites':
            loadWebsites();
            break;
        case 'platforms':
            loadPlatforms();
            break;
    }

        }
        
        // تحديث عنوان الصفحة
        function updatePageTitle(tabName) {
            const titles = {
                'dashboard': isRTL ? 'لوحة الإدارة' : 'Admin Dashboard',
                'users': isRTL ? 'المستخدمون' : 'Users',
                'websites': isRTL ? 'المواقع' : 'Websites',
                'platforms': isRTL ? 'المنصات' : 'Platforms'
            };
            
            const pageTitle = document.getElementById('page-title');
            if (pageTitle && titles[tabName]) {
                pageTitle.textContent = titles[tabName];
            }
        }
        
        // تحميل المواقع قيد المراجعة
        function loadPendingWebsites() {
            fetch('get_data.php?type=pending_websites')
                .then(response => response.json())
                .then(data => {
                    displayPendingWebsites(data);
                })
                .catch(error => {
                    console.error('Error loading pending websites:', error);
                });
        }
        
        // عرض المواقع قيد المراجعة
        function displayPendingWebsites(websites) {
            const container = document.getElementById('pending-websites');
            
            if (websites.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <h3>${isRTL ? 'لا توجد مواقع قيد المراجعة' : 'No websites pending review'}</h3>
                    </div>
                `;
                return;
            }
            
            let html = '<div style="overflow-x: auto;"><table class="table"><thead><tr>';
            html += `<th>${isRTL ? 'اسم الموقع' : 'Website Name'}</th>`;
            html += `<th>${isRTL ? 'المالك' : 'Owner'}</th>`;
            html += `<th>${isRTL ? 'الرابط' : 'URL'}</th>`;
            html += `<th>${isRTL ? 'المنصة' : 'Platform'}</th>`;
            html += `<th>${isRTL ? 'تاريخ الطلب' : 'Request Date'}</th>`;
            html += `<th>${isRTL ? 'الإجراءات' : 'Actions'}</th>`;
            html += '</tr></thead><tbody>';
            
            websites.forEach(website => {
                html += `<tr>
                    <td>${website.name}</td>
                    <td>${website.owner_name}</td>
                    <td><a href="${website.url}" target="_blank" style="color: var(--accent-color);">${website.domain}</a></td>
                    <td>${isRTL ? website.platform_name : website.platform_name_en}</td>
                    <td>${new Date(website.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="approveWebsite(${website.id})" title="${isRTL ? 'اعتماد' : 'Approve'}">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectWebsite(${website.id})" title="${isRTL ? 'رفض' : 'Reject'}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        // اعتماد موقع
        function approveWebsite(websiteId) {
            if (!confirm(isRTL ? 'هل تريد اعتماد هذا الموقع؟' : 'Do you want to approve this website?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'approve_website');
            formData.append('website_id', websiteId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isRTL ? 'تم اعتماد الموقع بنجاح' : 'Website approved successfully', 'success');
                    loadPendingWebsites();
                } else {
                    showAlert(isRTL ? 'حدث خطأ أثناء اعتماد الموقع' : 'Error approving website', 'error');
                }
            });
        }
        
        // رفض موقع
        function rejectWebsite(websiteId) {
            if (!confirm(isRTL ? 'هل تريد رفض هذا الموقع؟' : 'Do you want to reject this website?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'reject_website');
            formData.append('website_id', websiteId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isRTL ? 'تم رفض الموقع' : 'Website rejected', 'success');
                    loadPendingWebsites();
                } else {
                    showAlert(isRTL ? 'حدث خطأ أثناء رفض الموقع' : 'Error rejecting website', 'error');
                }
            });
        }
  
        
        // فتح نافذة إضافة منصة
        function openAddPlatformModal() {
            document.getElementById('add-platform-modal').style.display = 'flex';
        }
        
        // إغلاق النوافذ المنبثقة
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // إضافة منصة جديدة
        function addPlatform() {
            const form = document.getElementById('add-platform-form');
            const formData = new FormData(form);
            
            // إضافة معرفات افتراضية للمنصة
            const defaultSelectors = {
                search_box: { selector: '', type: 'input' },
                add_to_cart: { selector: '', type: 'button' },
                checkout: { selector: '', type: 'button' },
                product_view: { selector: '', type: 'link' }
            };
            
            formData.append('selectors', JSON.stringify(defaultSelectors));
            formData.append('ajax', '1');
            formData.append('action', 'add_platform');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal('add-platform-modal');
                    form.reset();
                    loadPlatforms();
                } else {
                    showAlert(data.errors ? data.errors.join('<br>') : 'حدث خطأ', 'error');
                }
            });
        }
        
        // تسجيل الخروج
        function logout() {
            if (confirm(isRTL ? 'هل تريد تسجيل الخروج؟' : 'Do you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }
        
        // فتح/إغلاق الشريط الجانبي للجوال
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // إظهار التنبيهات
        function showAlert(message, type = 'info') {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            
            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(alert, contentArea.firstChild);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        // إغلاق النوافذ المنبثقة بالنقر خارجها
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal') || e.target.id.includes('-modal')) {
                if (e.target.style.display !== 'none') {
                    e.target.style.display = 'none';
                }
            }
        });
        
        
        // داخل <script>
// دالة لتحميل المستخدمين
function loadUsers() {
    fetch('get_data.php?type=all_users')
        .then(response => response.json())
        .then(data => {
            displayUsers(data);
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
}

// دالة لعرض المستخدمين
function displayUsers(users) {
    const container = document.getElementById('users-list');
    
    if (users.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <h3>${isRTL ? 'لا يوجد مستخدمون' : 'No users found'}</h3>
            </div>
        `;
        return;
    }
    
    let html = '<div style="overflow-x: auto;"><table class="table"><thead><tr>';
    html += `<th>${isRTL ? 'اسم المستخدم' : 'Username'}</th>`;
    html += `<th>${isRTL ? 'البريد الإلكتروني' : 'Email'}</th>`;
    html += `<th>${isRTL ? 'الاسم الكامل' : 'Full Name'}</th>`;
    html += `<th>${isRTL ? 'الحالة' : 'Status'}</th>`;
    html += `<th>${isRTL ? 'تاريخ التسجيل' : 'Registration Date'}</th>`;
    html += `<th>${isRTL ? 'الإجراءات' : 'Actions'}</th>`;
    html += '</tr></thead><tbody>';
    
    users.forEach(user => {
        const statusBadge = user.is_active ? 
            `<span class="badge badge-success">${isRTL ? 'نشط' : 'Active'}</span>` : 
            `<span class="badge badge-danger">${isRTL ? 'غير نشط' : 'Inactive'}</span>`;
        
        html += `<tr>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.full_name}</td>
            <td>${statusBadge}</td>
            <td>${new Date(user.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-sm ${user.is_active ? 'btn-warning' : 'btn-success'}" onclick="toggleUserStatus(${user.id})" title="${isRTL ? (user.is_active ? 'تعطيل' : 'تفعيل') : (user.is_active ? 'Deactivate' : 'Activate')}">
                    <i class="fas ${user.is_active ? 'fa-user-slash' : 'fa-user-check'}"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" title="${isRTL ? 'حذف' : 'Delete'}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// دالة لتحميل جميع المواقع
function loadWebsites() {
    fetch('get_data.php?type=all_websites')
        .then(response => response.json())
        .then(data => {
            displayWebsites(data);
        })
        .catch(error => {
            console.error('Error loading websites:', error);
        });
}

// دالة لعرض المواقع
function displayWebsites(websites) {
    const container = document.getElementById('websites-list');
    
    if (websites.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <i class="fas fa-globe-americas" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <h3>${isRTL ? 'لا توجد مواقع' : 'No websites found'}</h3>
            </div>
        `;
        return;
    }
    
    let html = '<div style="overflow-x: auto;"><table class="table"><thead><tr>';
    html += `<th>${isRTL ? 'اسم الموقع' : 'Website Name'}</th>`;
    html += `<th>${isRTL ? 'المالك' : 'Owner'}</th>`;
    html += `<th>${isRTL ? 'الرابط' : 'URL'}</th>`;
    html += `<th>${isRTL ? 'المنصة' : 'Platform'}</th>`;
    html += `<th>${isRTL ? 'الحالة' : 'Status'}</th>`;
    html += `<th>${isRTL ? 'تاريخ الإضافة' : 'Added Date'}</th>`;
    html += `<th>${isRTL ? 'الإجراءات' : 'Actions'}</th>`;
    html += '</tr></thead><tbody>';
    
    websites.forEach(website => {
        let statusBadge;
        switch (website.status) {
            case 'approved':
                statusBadge = `<span class="badge badge-success">${isRTL ? 'معتمد' : 'Approved'}</span>`;
                break;
            case 'pending':
                statusBadge = `<span class="badge badge-warning">${isRTL ? 'قيد المراجعة' : 'Pending'}</span>`;
                break;
            case 'rejected':
                statusBadge = `<span class="badge badge-danger">${isRTL ? 'مرفوض' : 'Rejected'}</span>`;
                break;
            default:
                statusBadge = '';
        }
        
        html += `<tr>
            <td>${website.name}</td>
            <td>${website.owner_name}</td>
            <td><a href="${website.url}" target="_blank" style="color: var(--accent-color);">${website.domain}</a></td>
            <td>${isRTL ? website.platform_name : website.platform_name_en}</td>
            <td>${statusBadge}</td>
            <td>${new Date(website.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewWebsite(${website.id})" title="${isRTL ? 'عرض' : 'View'}">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// دالة لتحميل المنصات
function loadPlatforms() {
    fetch('get_data.php?type=all_platforms')
        .then(response => response.json())
        .then(data => {
            displayPlatforms(data);
        })
        .catch(error => {
            console.error('Error loading platforms:', error);
        });
}

// دالة لعرض المنصات
function displayPlatforms(platforms) {
    const container = document.getElementById('platforms-list');
    
    if (platforms.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <i class="fas fa-layer-group" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <h3>${isRTL ? 'لا توجد منصات' : 'No platforms found'}</h3>
            </div>
        `;
        return;
    }
    
    let html = '<div class="platforms-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">';
    
    platforms.forEach(platform => {
        html += `
        <div class="platform-card" style="background: var(--surface); border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--shadow);">
            <div class="platform-header" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); padding: 20px; color: white;">
                <h3 style="font-size: 18px; margin-bottom: 4px;">${isRTL ? platform.name : platform.name_en}</h3>
                <p style="font-size: 14px; opacity: 0.9;">${isRTL ? platform.description : platform.description_en}</p>
            </div>
            <div class="platform-actions" style="padding: 16px; display: flex; gap: 8px; justify-content: flex-end;">
                <button class="btn btn-sm btn-warning" onclick="editPlatform(${platform.id})">
                    <i class="fas fa-edit"></i> ${isRTL ? 'تعديل' : 'Edit'}
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePlatform(${platform.id})">
                    <i class="fas fa-trash"></i> ${isRTL ? 'حذف' : 'Delete'}
                </button>
            </div>
        </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// دالة لحذف المنصة
function deletePlatform(platformId) {
    if (!confirm(isRTL ? 'هل تريد حذف هذه المنصة؟' : 'Do you want to delete this platform?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'delete_platform');
    formData.append('platform_id', platformId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadPlatforms();
        } else {
            showAlert(data.error || isRTL ? 'حدث خطأ أثناء حذف المنصة' : 'Error deleting platform', 'error');
        }
    });
}


function deleteUser(userId) {
    if (!confirm(isRTL ? 'هل تريد حذف المستخدم؟ سيتم حذف جميع بياناته ومواقعه.' : 'Do you want to delete this user? All their data and websites will be deleted.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'delete_user');
    formData.append('user_id', userId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(isRTL ? 'تم حذف المستخدم بنجاح' : 'User deleted successfully', 'success');
            loadUsers();
        } else {
            showAlert(isRTL ? 'حدث خطأ أثناء حذف المستخدم' : 'Error deleting user', 'error');
        }
    });
}

function toggleUserStatus(userId) {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'toggle_user_status');
    formData.append('user_id', userId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(isRTL ? 'تم تغيير حالة المستخدم بنجاح' : 'User status updated successfully', 'success');
            loadUsers();
        } else {
            showAlert(isRTL ? 'حدث خطأ أثناء تغيير حالة المستخدم' : 'Error updating user status', 'error');
        }
    });
}

    </script>
</body>
</html>