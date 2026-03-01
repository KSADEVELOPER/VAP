<?php
// heatmap.php - الجزء المحدث
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

// الحصول على الإحصائيات
$days = $_GET['days'] ?? 30;

// معالجة طلبات AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['ajax']) {
        case 'heatmap_data':
            $page_url = $_GET['page_url'] ?? '';
            $view_type = $_GET['view_type'] ?? 'clicks';
            $heatmapData = getHeatmapData($db, $website_id, $days, $page_url, $view_type);
            echo json_encode($heatmapData);
            break;
            
        case 'click_patterns':
            $clickPatterns = getClickPatterns($db, $website_id, $days);
            echo json_encode($clickPatterns);
            break;
            
        case 'scroll_data':
            $scrollData = getScrollData($db, $website_id, $days);
            echo json_encode($scrollData);
            break;
            
        case 'page_preview':
            $page_url = $_GET['page_url'] ?? '';
            $pageData = getPagePreviewData($db, $website_id, $page_url, $days);
            echo json_encode($pageData);
            break;
            
        case 'real_time_data':
            $realTimeData = getRealTimeData($db, $website_id);
            echo json_encode($realTimeData);
            break;
            
        case 'pages_list':
            $pagesList = getPagesList($db, $website_id, $days);
            echo json_encode($pagesList);
            break;
    }
    exit;
}

// وظائف جلب البيانات المحدثة
function getHeatmapData($db, $website_id, $days, $page_url = '', $view_type = 'clicks') {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    if ($view_type === 'clicks') {
        $sql = "SELECT 
            c.page_url,
            c.element_selector,
            c.element_text,
            c.element_type,
            c.click_x,
            c.click_y,
            c.clicked_at,
            c.session_id,
            COUNT(*) as click_count,
            AVG(c.click_x) as avg_x,
            AVG(c.click_y) as avg_y,
            pv.page_title,
            COUNT(DISTINCT c.session_id) as unique_sessions
         FROM clicks c
         LEFT JOIN page_views pv ON c.page_url = pv.page_url AND c.website_id = pv.website_id
         WHERE c.website_id = ? AND c.clicked_at >= ?";
         
        $params = [$website_id, $start];
        
        if (!empty($page_url)) {
            $sql .= " AND c.page_url = ?";
            $params[] = $page_url;
        }
        
        $sql .= " GROUP BY c.page_url, c.element_selector, c.click_x, c.click_y
                  ORDER BY click_count DESC
                  LIMIT 1000";
                  
        return $db->fetchAll($sql, $params);
        
    } elseif ($view_type === 'scroll') {
        $sql = "SELECT 
            page_url,
            page_title,
            AVG(scroll_depth) as avg_scroll_depth,
            MAX(scroll_depth) as max_scroll_depth,
            MIN(scroll_depth) as min_scroll_depth,
            COUNT(*) as total_views,
            AVG(time_on_page) as avg_time_on_page
         FROM page_views
         WHERE website_id = ? AND view_time >= ?";
         
        $params = [$website_id, $start];
        
        if (!empty($page_url)) {
            $sql .= " AND page_url = ?";
            $params[] = $page_url;
        }
        
        $sql .= " GROUP BY page_url
                  ORDER BY avg_scroll_depth DESC";
                  
        return $db->fetchAll($sql, $params);
        
    } elseif ($view_type === 'attention') {
        // مناطق الانتباه بناءً على الوقت المقضي
        $sql = "SELECT 
            page_url,
            page_title,
            AVG(time_on_page) as attention_score,
            COUNT(*) as views,
            AVG(scroll_depth) as engagement_depth
         FROM page_views
         WHERE website_id = ? AND view_time >= ? AND time_on_page > 5";
         
        $params = [$website_id, $start];
        
        if (!empty($page_url)) {
            $sql .= " AND page_url = ?";
            $params[] = $page_url;
        }
        
        $sql .= " GROUP BY page_url
                  ORDER BY attention_score DESC";
                  
        return $db->fetchAll($sql, $params);
    }
    
    return [];
}

function getRealTimeData($db, $website_id) {
    $today = date('Y-m-d');
    $last_hour = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $last_30_minutes = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    
    // المستخدمين اليوم
    $activeUsers = $db->fetchOne(
        "SELECT COUNT(DISTINCT session_id) as count 
         FROM sessions 
         WHERE website_id = ? AND DATE(started_at) = ?",
        [$website_id, $today]
    )['count'] ?? 0;
    
    // نقرات اليوم
    $todayClicks = $db->fetchOne(
        "SELECT COUNT(*) as count 
         FROM clicks 
         WHERE website_id = ? AND DATE(clicked_at) = ?",
        [$website_id, $today]
    )['count'] ?? 0;
    
    // النقرات الحديثة - نجرب آخر ساعة أولاً، ثم اليوم
    $recentClicks = $db->fetchAll(
        "SELECT 
            element_text,
            element_selector,
            page_url,
            clicked_at,
            COUNT(*) as click_count
         FROM clicks 
         WHERE website_id = ? AND clicked_at >= ?
         GROUP BY element_text, element_selector, page_url
         ORDER BY MAX(clicked_at) DESC 
         LIMIT 10",
        [$website_id, $last_hour]
    );
    
    // إذا لم نجد نقرات في آخر ساعة، نجلب من اليوم
    if (empty($recentClicks)) {
        $recentClicks = $db->fetchAll(
            "SELECT 
                element_text,
                element_selector,
                page_url,
                clicked_at,
                COUNT(*) as click_count
             FROM clicks 
             WHERE website_id = ? AND DATE(clicked_at) = ?
             GROUP BY element_text, element_selector, page_url
             ORDER BY MAX(clicked_at) DESC 
             LIMIT 10",
            [$website_id, $today]
        );
    }
    
    // متوسط الوقت اليوم
    $avgTimeToday = $db->fetchOne(
        "SELECT AVG(time_on_page) as avg_time
         FROM page_views 
         WHERE website_id = ? AND DATE(view_time) = ? AND time_on_page > 0",
        [$website_id, $today]
    )['avg_time'] ?? 0;
    
    // الصفحة الأكثر نشاطاً اليوم
    $topPageToday = $db->fetchOne(
        "SELECT page_title, page_url, COUNT(*) as views
         FROM page_views 
         WHERE website_id = ? AND DATE(view_time) = ?
         GROUP BY page_url 
         ORDER BY views DESC LIMIT 1",
        [$website_id, $today]
    );
    
    return [
        'active_users' => $activeUsers,
        'today_clicks' => $todayClicks,
        'recent_clicks' => $recentClicks,
        'avg_time_today' => round($avgTimeToday, 1),
        'top_page_today' => $topPageToday['page_title'] ?? 'N/A'
    ];
}
function getPagesList($db, $website_id, $days) {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    return $db->fetchAll(
        "SELECT DISTINCT page_url, page_title, COUNT(*) as views
         FROM page_views 
         WHERE website_id = ? AND view_time >= ?
         GROUP BY page_url, page_title
         ORDER BY views DESC",
        [$website_id, $start]
    );
}

function getClickPatterns($db, $website_id, $days) {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    return $db->fetchAll(
        "SELECT 
            c.page_url,
            c.element_selector,
            c.element_type,
            c.element_text,
            COUNT(*) as total_clicks,
            COUNT(DISTINCT c.session_id) as unique_users,
            AVG(HOUR(c.clicked_at)) as avg_hour,
            MIN(c.clicked_at) as first_click,
            MAX(c.clicked_at) as last_click
         FROM clicks c
         WHERE c.website_id = ? AND c.clicked_at >= ?
         GROUP BY c.page_url, c.element_selector
         ORDER BY total_clicks DESC
         LIMIT 50",
        [$website_id, $start]
    );
}

function getScrollData($db, $website_id, $days) {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    return $db->fetchAll(
        "SELECT 
            page_url,
            page_title,
            AVG(scroll_depth) as avg_scroll_depth,
            MAX(scroll_depth) as max_scroll_depth,
            MIN(scroll_depth) as min_scroll_depth,
            COUNT(*) as total_views,
            AVG(time_on_page) as avg_time_on_page
         FROM page_views
         WHERE website_id = ? AND view_time >= ?
         GROUP BY page_url
         ORDER BY avg_scroll_depth DESC",
        [$website_id, $start]
    );
}

function getPagePreviewData($db, $website_id, $page_url, $days) {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $pageViews = $db->fetchOne(
        "SELECT COUNT(*) as views, AVG(time_on_page) as avg_time, AVG(scroll_depth) as avg_scroll
         FROM page_views 
         WHERE website_id = ? AND page_url = ? AND view_time >= ?",
        [$website_id, $page_url, $start]
    );
    
    $clicks = $db->fetchAll(
        "SELECT click_x, click_y, element_selector, element_text, clicked_at, COUNT(*) as count
         FROM clicks 
         WHERE website_id = ? AND page_url = ? AND clicked_at >= ?
         GROUP BY click_x, click_y, element_selector
         ORDER BY count DESC",
        [$website_id, $page_url, $start]
    );
    
    return [
        'page_stats' => $pageViews ?? [],
        'click_data' => $clicks
    ];
}
?>


<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'خريطة التفاعل الحرارية - ' . htmlspecialchars($website['name']) : 'Interactive Heatmap - ' . htmlspecialchars($website['name']); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($is_rtl): ?>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #4ade80;
            --warning: #fbbf24;
            --error: #ef4444;
            --info: #06b6d4;
            
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-tertiary: #16213e;
            --bg-card: rgba(255, 255, 255, 0.05);
            --bg-glass: rgba(255, 255, 255, 0.1);
            
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --text-muted: #718096;
            
            --border: rgba(255, 255, 255, 0.1);
            --border-light: rgba(255, 255, 255, 0.05);
            
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            --shadow-glow: 0 0 20px rgba(102, 126, 234, 0.3);
            
            --radius: 16px;
            --radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-accent: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            --gradient-warm: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            --gradient-glass: linear-gradient(145deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo $is_rtl ? "'Tajawal'" : "'Inter'"; ?>, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* خلفية متحركة */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--bg-primary);
        }
        
        .animated-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(240, 147, 251, 0.1) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }
        
        /* شريط علوي متقدم */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: var(--shadow-glow);
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        
        .site-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .site-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .top-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .control-btn {
            padding: 0.75rem 1.5rem;
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .control-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .control-btn:hover::before {
            left: 0;
        }
        
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }
        
        /* منطقة المحتوى الرئيسية */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            min-height: calc(100vh - 80px);
        }
        
        /* منطقة الخريطة الحرارية */
        .heatmap-section {
            background: var(--bg-glass);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            backdrop-filter: blur(20px);
            overflow: hidden;
            position: relative;
        }
        
        .heatmap-header {
            padding: 2rem;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .heatmap-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }
        
        .heatmap-title h2 {
            font-size: 1.8rem;
            font-weight: 800;
        }
        
        .heatmap-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            animation: iconPulse 2s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .heatmap-controls {
            display: flex;
            gap: 1rem;
        }
        
        .heatmap-viewport {
            position: relative;
            height: calc(100vh - 200px);
            overflow: hidden;
            background: var(--bg-secondary);
        }
        
        .website-preview {
            width: 100%;
            height: 100%;
            position: relative;
            transform-origin: top left;
            transition: var(--transition);
        }
        
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: none;
        }
        
        /* طبقة الخريطة الحرارية */
        .heatmap-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }
        
        .heat-point {
            position: absolute;
            border-radius: 50%;
            pointer-events: all;
            cursor: pointer;
            animation: heatPulse 2s ease-in-out infinite;
            transition: var(--transition);
        }
        
        .heat-point::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200%;
            height: 200%;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: heatRipple 3s ease-out infinite;
        }
        
        @keyframes heatPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        @keyframes heatRipple {
            0% { transform: translate(-50%, -50%) scale(0); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 0; }
        }
        
        .heat-intensity-1 { background: rgba(76, 222, 128, 0.6); }
        .heat-intensity-2 { background: rgba(251, 191, 36, 0.7); }
        .heat-intensity-3 { background: rgba(249, 115, 22, 0.8); }
        .heat-intensity-4 { background: rgba(239, 68, 68, 0.9); }
        .heat-intensity-5 { background: rgba(147, 51, 234, 1); }
        
        /* اللوحة الجانبية */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .stats-card {
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(20px);
            overflow: hidden;
            position: relative;
        }
        
        .card-header {
            padding: 1.5rem;
            background: var(--gradient-glass);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        /* إحصائيات الوقت الفعلي */
        .real-time-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: var(--gradient-primary);
            animation: statProgress 2s ease-in-out infinite;
        }
        
        @keyframes statProgress {
            0% { left: -100%; }
            50% { left: 0; }
            100% { left: 100%; }
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            animation: countUp 2s ease-out;
        }
        
        @keyframes countUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* قائمة النقرات الحديثة */
        .recent-clicks {
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-tertiary);
        }
        
        .recent-clicks::-webkit-scrollbar {
            width: 6px;
        }
        
        .recent-clicks::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
            border-radius: 3px;
        }
        
        .recent-clicks::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }
        
        .click-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            transition: var(--transition);
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .click-item:hover {
            transform: translateX(-5px);
            background: var(--bg-glass);
            border-color: var(--primary);
        }
        
        .click-indicator {
            width: 12px;
            height: 12px;
            background: var(--gradient-accent);
            border-radius: 50%;
            flex-shrink: 0;
            animation: clickBlink 1s ease-in-out infinite;
        }
        
        @keyframes clickBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .click-details {
            flex: 1;
            min-width: 0;
        }
        
        .click-element {
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .click-time {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        /* أدوات التحكم المتقدمة */
        .advanced-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .control-label {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .range-slider {
            appearance: none;
            width: 100%;
            height: 6px;
            background: var(--bg-tertiary);
            border-radius: 3px;
            outline: none;
            position: relative;
        }
        
        .range-slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            background: var(--gradient-primary);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .range-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: var(--shadow-glow);
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: var(--bg-tertiary);
            border-radius: 15px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 24px;
            height: 24px;
            background: var(--text-secondary);
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .toggle-switch.active {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .toggle-switch.active::before {
            left: calc(100% - 26px);
            background: white;
        }
        
        /* شريط الفلاتر */
        .filters-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            font-size: 0.9rem;
            min-width: 120px;
            transition: var(--transition);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .filter-select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        /* تأثيرات الحالة */
        .loading-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            flex-direction: column;
            gap: 1rem;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--bg-tertiary);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* النوافذ المنبثقة */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: var(--bg-glass);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            backdrop-filter: blur(20px);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideUp 0.3s ease-out;
        }
        
        @keyframes modalSlideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 2rem;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        /* تأثيرات الانتقال */
        .fade-in {
            animation: fadeIn 1s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .zoom-in {
            animation: zoomIn 0.6s ease-out;
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        /* الاستجابة للشاشات المختلفة */
        @media (max-width: 1200px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .sidebar {
                order: -1;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
                margin-top: 70px;
            }
            
            .top-bar {
                height: 70px;
                padding: 0 1rem;
            }
            
            .top-controls {
                gap: 0.5rem;
            }
            
            .control-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            
            .heatmap-header {
                padding: 1.5rem;
            }
            
            .heatmap-title h2 {
                font-size: 1.5rem;
            }
            
            .heatmap-viewport {
                height: 60vh;
            }
            
            .sidebar {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .top-bar {
                flex-direction: column;
                height: auto;
                padding: 1rem;
                gap: 1rem;
            }
            
            .main-container {
                margin-top: 120px;
                padding: 0.5rem;
            }
            
            .real-time-stats {
                grid-template-columns: 1fr;
            }
            
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* تأثيرات خاصة للماوس */
        .heat-point:hover {
            transform: scale(1.5);
            z-index: 100;
            box-shadow: var(--shadow-glow);
        }
        
        .heat-point.selected {
            transform: scale(1.8);
            z-index: 101;
            border: 2px solid var(--accent);
            box-shadow: 0 0 30px rgba(240, 147, 251, 0.6);
        }
        
        /* تول تيب مخصص */
        .custom-tooltip {
            position: absolute;
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            backdrop-filter: blur(20px);
            color: var(--text-primary);
            font-size: 0.9rem;
            max-width: 300px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transform: translateY(10px);
            transition: var(--transition);
        }
        
        .custom-tooltip.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .tooltip-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .tooltip-content {
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* شريط التقدم المتحرك */
        .progress-ring {
            width: 60px;
            height: 60px;
        }
        
        .progress-ring-circle {
            stroke: var(--primary);
            stroke-width: 4;
            fill: transparent;
            stroke-dasharray: 188.4;
            stroke-dashoffset: 188.4;
            transform-origin: 50% 50%;
            transform: rotate(-90deg);
            transition: stroke-dashoffset 1s ease-in-out;
        }
        
        .progress-ring-bg {
            stroke: var(--bg-tertiary);
            stroke-width: 4;
            fill: transparent;
        }
        
        /* تأثيرات الجسيمات */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.6;
            animation: particleFloat 10s linear infinite;
        }
        
        @keyframes particleFloat {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
        }
        
        /* شريط إشعارات متقدم */
        .notification-bar {
            position: fixed;
            top: 80px;
            right: 2rem;
            width: 300px;
            z-index: 9999;
        }
        
        .notification {
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 0.5rem;
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: notificationSlide 0.5s ease-out;
            transition: var(--transition);
        }
        
        @keyframes notificationSlide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .notification:hover {
            transform: translateX(-5px);
            box-shadow: var(--shadow-glow);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
        }
        
        .notification-success .notification-icon { background: var(--success); }
        .notification-warning .notification-icon { background: var(--warning); }
        .notification-error .notification-icon { background: var(--error); }
        .notification-info .notification-icon { background: var(--info); }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .notification-close:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <div class="particles" id="particles"></div>
    
    <!-- شريط علوي -->
    <header class="top-bar">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-fire"></i>
            </div>
            <div class="site-info">
                <h1><?php echo htmlspecialchars($website['name']); ?></h1>
                <p><?php echo $is_rtl ? 'خريطة التفاعل الحرارية المتقدمة' : 'Advanced Interactive Heatmap'; ?></p>
            </div>
        </div>
        
        <div class="top-controls">
            <select class="filter-select" id="timeRange" onchange="changeTimeRange(this.value)">
                <option value="24" <?php echo $days == 1 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 24 ساعة' : 'Last 24 hours'; ?></option>
                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 7 أيام' : 'Last 7 days'; ?></option>
                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 30 يوم' : 'Last 30 days'; ?></option>
                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>><?php echo $is_rtl ? 'آخر 3 أشهر' : 'Last 3 months'; ?></option>
            </select>
            
            <a href="Analytics.php?id=<?php echo $website_id; ?>&lang=<?php echo $lang; ?>" class="control-btn">
                <i class="fas fa-chart-line"></i>
                <?php echo $is_rtl ? 'التحليلات' : 'Analytics'; ?>
            </a>
            
            <button class="control-btn" onclick="toggleRealTime()">
                <i class="fas fa-broadcast-tower"></i>
                <span id="realTimeText"><?php echo $is_rtl ? 'وقت فعلي' : 'Real-time'; ?></span>
            </button>
            
            <a class="control-btn" href="dashboard.php?lang=<?php echo $lang; ?>" >
                <i class="fas fa-dashboard"></i>
                <?php echo $is_rtl ? 'لوحة التحكم' : 'Dashboard'; ?>
            </a>
        </div>
    </header>
    
    <!-- شريط الإشعارات -->
    <div class="notification-bar" id="notificationBar"></div>
    
    <!-- المحتوى الرئيسي -->
    <main class="main-container">
        <!-- قسم الخريطة الحرارية -->
        <section class="heatmap-section fade-in">
            <div class="heatmap-header">
                <div class="heatmap-title">
                    <div class="heatmap-icon">
                        <i class="fas fa-fire-flame-curved"></i>
                    </div>
                    <div>
                        <h2><?php echo $is_rtl ? 'خريطة التفاعل الحرارية' : 'Interactive Heatmap'; ?></h2>
                        <p><?php echo $is_rtl ? 'تصور تفاعلي لسلوك المستخدمين' : 'Interactive visualization of user behavior'; ?></p>
                    </div>
                </div>
                
                <div class="heatmap-controls">
                    <div class="filters-bar">
                        <select class="filter-select" id="pageFilter" onchange="changePageFilter(this.value)">
                            <option value=""><?php echo $is_rtl ? 'جميع الصفحات' : 'All Pages'; ?></option>
                        </select>
                        
                        <div class="control-group" style="flex-direction: row; align-items: center; gap: 0.5rem;">
                            <span class="control-label"><?php echo $is_rtl ? 'كثافة' : 'Intensity'; ?></span>
                            <input type="range" class="range-slider" id="intensitySlider" min="1" max="10" value="5" onchange="updateIntensity(this.value)">
                        </div>
                        
                        <div class="toggle-switch" id="animationToggle" onclick="toggleAnimation()">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="heatmap-viewport" id="heatmapViewport">
                <div class="loading-state" id="loadingState">
                    <div class="loading-spinner"></div>
                    <div class="loading-text"><?php echo $is_rtl ? 'جاري تحميل البيانات...' : 'Loading data...'; ?></div>
                </div>
                
                <div class="website-preview" id="websitePreview" style="display: none;">
                    <iframe class="preview-iframe" id="previewIframe" src=""></iframe>
                    <div class="heatmap-overlay" id="heatmapOverlay"></div>
                </div>
            </div>
        </section>
        
        <!-- الشريط الجانبي -->
        <aside class="sidebar slide-in-left">
            <!-- إحصائيات الوقت الفعلي -->
            <div class="stats-card zoom-in">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="card-title"><?php echo $is_rtl ? 'إحصائيات حية' : 'Live Stats'; ?></h3>
                </div>
                <div class="card-content">
                    <div class="real-time-stats" id="realTimeStats">
                        <div class="stat-item">
                            <div class="stat-value" id="activeUsers">0</div>
                            <div class="stat-label"><?php echo $is_rtl ? 'زوار اليوم' : 'Today\'s Visitors'; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="totalClicks">0</div>
                            <div class="stat-label"><?php echo $is_rtl ? 'نقرات اليوم' : 'Today\'s Clicks'; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="topPage">-</div>
                            <div class="stat-label"><?php echo $is_rtl ? 'الصفحة الأكثر نشاطاً' : 'Most Active Page'; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="avgTime">0s</div>
                            <div class="stat-label"><?php echo $is_rtl ? 'متوسط الوقت' : 'Avg. Time'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- النقرات الحديثة -->
            <div class="stats-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <h3 class="card-title"><?php echo $is_rtl ? 'النقرات الحديثة' : 'Recent Clicks'; ?></h3>
                </div>
                <div class="card-content">
                    <div class="recent-clicks" id="recentClicks">
                        <!-- سيتم ملء البيانات هنا عبر JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- أدوات التحكم المتقدمة -->
            <div class="stats-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <h3 class="card-title"><?php echo $is_rtl ? 'أدوات التحكم' : 'Controls'; ?></h3>
                </div>
                <div class="card-content">
                    <div class="advanced-controls">
                        <div class="control-group">
                            <label class="control-label">
                                <i class="fas fa-eye"></i>
                                <?php echo $is_rtl ? 'نوع العرض' : 'View Type'; ?>
                            </label>
                            <select class="filter-select" id="viewType" onchange="changeViewType(this.value)">
                                <option value="clicks"><?php echo $is_rtl ? 'النقرات' : 'Clicks'; ?></option>
                                <option value="scroll"><?php echo $is_rtl ? 'التمرير' : 'Scroll'; ?></option>
                                <option value="attention"><?php echo $is_rtl ? 'مناطق الانتباه' : 'Attention'; ?></option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">
                                <i class="fas fa-palette"></i>
                                <?php echo $is_rtl ? 'نمط الألوان' : 'Color Scheme'; ?>
                            </label>
                            <select class="filter-select" id="colorScheme" onchange="changeColorScheme(this.value)">
                                <option value="heat"><?php echo $is_rtl ? 'حراري' : 'Heat'; ?></option>
                                <option value="rainbow"><?php echo $is_rtl ? 'قوس قزح' : 'Rainbow'; ?></option>
                                <option value="mono"><?php echo $is_rtl ? 'أحادي' : 'Monochrome'; ?></option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">
                                <i class="fas fa-compress-arrows-alt"></i>
                                <?php echo $is_rtl ? 'مستوى التكبير' : 'Zoom Level'; ?>
                            </label>
                            <input type="range" class="range-slider" id="zoomSlider" min="50" max="200" value="100" onchange="updateZoom(this.value)">
                            <span id="zoomValue">100%</span>
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">
                                <i class="fas fa-filter"></i>
                                <?php echo $is_rtl ? 'عتبة النقرات' : 'Click Threshold'; ?>
                            </label>
                            <input type="range" class="range-slider" id="thresholdSlider" min="1" max="100" value="5" onchange="updateThreshold(this.value)">
                            <span id="thresholdValue">5</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </main>
    
    <!-- النافذة المنبثقة لتفاصيل النقرة -->
    <div class="modal" id="clickModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo $is_rtl ? 'تفاصيل النقرة' : 'Click Details'; ?></h3>
                <button class="modal-close" onclick="closeModal('clickModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="clickModalBody">
                <!-- سيتم ملء المحتوى هنا -->
            </div>
        </div>
    </div>
    
    <!-- تول تيب مخصص -->
    <div class="custom-tooltip" id="customTooltip">
        <div class="tooltip-title" id="tooltipTitle"></div>
        <div class="tooltip-content" id="tooltipContent"></div>
    </div>

<script>
        // متغيرات عامة
        const isRTL = <?php echo $is_rtl ? 'true' : 'false'; ?>;
        const websiteId = <?php echo $website_id; ?>;
        const websiteUrl = '<?php echo htmlspecialchars($website['url']); ?>';
        let currentDays = <?php echo $days; ?>;
        let isRealTime = false;
        let realTimeInterval = null;
        let heatmapData = [];
        let currentPage = '';
        let currentViewType = 'clicks';
        let animationsEnabled = true;
        let currentIntensity = 5;
        let currentZoom = 100;
        let currentThreshold = 5;
        
        // إعدادات الألوان
        const colorSchemes = {
            heat: ['#4ade80', '#fbbf24', '#f97316', '#ef4444', '#9333ea'],
            rainbow: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            mono: ['#64748b', '#475569', '#334155', '#1e293b', '#0f172a']
        };
        
        let currentColorScheme = 'heat';
        
        // فئة إدارة الخريطة الحرارية المحدثة
        class HeatmapManager {
            constructor() {
                this.overlay = document.getElementById('heatmapOverlay');
                this.iframe = document.getElementById('previewIframe');
                this.tooltip = document.getElementById('customTooltip');
                this.init();
            }
            
            async init() {
                await this.loadPagesList();
                await this.loadInitialData();
                this.setupEventListeners();
                this.createParticles();
                this.startRealTimeUpdates();
            }
            
            async loadPagesList() {
                try {
                    const response = await fetch(`?ajax=pages_list&id=${websiteId}&days=${currentDays}`);
                    const pages = await response.json();
                    this.updatePageFilter(pages);
                } catch (error) {
                    console.error('Error loading pages list:', error);
                }
            }
            
            updatePageFilter(pages) {
                const select = document.getElementById('pageFilter');
                
                // مسح الخيارات الحالية (عدا الأول)
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                pages.forEach(page => {
                    const option = document.createElement('option');
                    option.value = page.page_url;
                    option.textContent = page.page_title || page.page_url;
                    if (option.textContent.length > 40) {
                        option.textContent = option.textContent.substring(0, 40) + '...';
                    }
                    option.title = page.page_url; // إضافة tooltip
                    select.appendChild(option);
                });
            }
            
            async loadInitialData() {
                try {
                    // تحميل بيانات الخريطة الحرارية
                    const params = new URLSearchParams({
                        ajax: 'heatmap_data',
                        id: websiteId,
                        days: currentDays,
                        view_type: currentViewType
                    });
                    
                    if (currentPage) {
                        params.append('page_url', currentPage);
                    }
                    
                    const heatmapResponse = await fetch(`?${params}`);
                    heatmapData = await heatmapResponse.json();
                    
                    // تحميل صفحة الموقع
                    await this.loadWebsitePreview();
                    
                    // رسم الخريطة الحرارية
                    this.renderHeatmap();
                    
                    // تحديث الإحصائيات
                    await this.updateStats();
                    
                    // إخفاء شاشة التحميل
                    this.hideLoading();
                    
                } catch (error) {
                    console.error('Error loading initial data:', error);
                    this.showError('Failed to load heatmap data');
                }
            }
            
            async loadWebsitePreview() {
                return new Promise((resolve) => {
                    const iframe = document.getElementById('previewIframe');
                    const preview = document.getElementById('websitePreview');
                    
                    // إذا كان هناك صفحة محددة، استخدمها
                    const targetUrl = currentPage || websiteUrl;
                    iframe.src = targetUrl;
                    
                    iframe.onload = () => {
                        preview.style.display = 'block';
                        resolve();
                    };
                    
                    iframe.onerror = () => {
                        this.showError('Unable to load website preview');
                        resolve();
                    };
                    
                    // Timeout fallback
                    setTimeout(resolve, 5000);
                });
            }
            
            renderHeatmap() {
                this.overlay.innerHTML = '';
                
                if (!heatmapData || heatmapData.length === 0) {
                    this.showEmptyState();
                    return;
                }
                
                if (currentViewType === 'clicks') {
                    this.renderClickHeatmap();
                } else if (currentViewType === 'scroll') {
                    this.renderScrollHeatmap();
                } else if (currentViewType === 'attention') {
                    this.renderAttentionHeatmap();
                }
                
                // تطبيق الانيميشن
                if (animationsEnabled) {
                    this.animateHeatPoints();
                }
            }
            
            renderClickHeatmap() {
                // تجميع النقرات حسب الموقع
                const groupedClicks = this.groupClicksByPosition();
                
                groupedClicks.forEach((group, index) => {
                    const heatPoint = this.createClickHeatPoint(group, index);
                    this.overlay.appendChild(heatPoint);
                });
            }
            
            renderScrollHeatmap() {
                // عرض بيانات التمرير كشرائط أفقية
                heatmapData.forEach((data, index) => {
                    const scrollBar = this.createScrollBar(data, index);
                    this.overlay.appendChild(scrollBar);
                });
            }
            
            renderAttentionHeatmap() {
                // عرض مناطق الانتباه كدوائر متدرجة
                heatmapData.forEach((data, index) => {
                    const attentionZone = this.createAttentionZone(data, index);
                    this.overlay.appendChild(attentionZone);
                });
            }
            
            groupClicksByPosition() {
                const groups = new Map();
                const threshold = currentThreshold;
                
                heatmapData.forEach(click => {
                    // تصفية حسب العتبة
                    if (click.click_count < threshold) {
                        return;
                    }
                    
                    const key = `${Math.round(click.click_x/15)*15},${Math.round(click.click_y/15)*15}`;
                    
                    if (!groups.has(key)) {
                        groups.set(key, {
                            x: click.click_x,
                            y: click.click_y,
                            clicks: [],
                            totalCount: 0,
                            uniqueSessions: new Set(),
                            elements: new Set()
                        });
                    }
                    
                    const group = groups.get(key);
                    group.clicks.push(click);
                    group.totalCount += click.click_count;
                    if (click.session_id) {
                        group.uniqueSessions.add(click.session_id);
                    }
                    if (click.element_text) {
                        group.elements.add(click.element_text);
                    }
                });
                
                return Array.from(groups.values());
            }
            
            createClickHeatPoint(group, index) {
                const point = document.createElement('div');
                point.className = 'heat-point';
                point.style.left = group.x + 'px';
                point.style.top = group.y + 'px';
                
                // تحديد كثافة النقطة
                const intensity = this.calculateIntensity(group.totalCount);
                const size = Math.max(12, Math.min(60, intensity * 3));
                
                point.style.width = size + 'px';
                point.style.height = size + 'px';
                point.style.marginLeft = -(size/2) + 'px';
                point.style.marginTop = -(size/2) + 'px';
                
                // تطبيق الألوان
                const colorIndex = Math.min(4, Math.floor(intensity / 2));
                const colors = colorSchemes[currentColorScheme];
                point.style.background = colors[colorIndex];
                
                // إضافة كلاس الكثافة
                point.classList.add(`heat-intensity-${Math.min(5, colorIndex + 1)}`);
                
                // تأخير الانيميشن
                point.style.animationDelay = (index * 0.1) + 's';
                
                // إضافة الأحداث
                this.addHeatPointEvents(point, group);
                
                return point;
            }
            
            createScrollBar(data, index) {
                const bar = document.createElement('div');
                bar.className = 'scroll-bar';
                bar.style.position = 'absolute';
                bar.style.left = '10px';
                bar.style.top = (index * 30 + 50) + 'px';
                bar.style.width = (data.avg_scroll_depth / 100 * 300) + 'px';
                bar.style.height = '20px';
                bar.style.background = `linear-gradient(90deg, ${colorSchemes[currentColorScheme][0]}, ${colorSchemes[currentColorScheme][2]})`;
                bar.style.borderRadius = '10px';
                bar.style.opacity = '0.8';
                bar.style.cursor = 'pointer';
                
                // إضافة tooltip
                bar.title = `${data.page_title || 'Page'}: ${Math.round(data.avg_scroll_depth)}% scroll depth`;
                
                return bar;
            }
            
            createAttentionZone(data, index) {
                const zone = document.createElement('div');
                zone.className = 'attention-zone';
                zone.style.position = 'absolute';
                zone.style.left = '50px';
                zone.style.top = (index * 80 + 100) + 'px';
                
                const size = Math.max(30, Math.min(120, data.attention_score * 2));
                zone.style.width = size + 'px';
                zone.style.height = size + 'px';
                zone.style.borderRadius = '50%';
                zone.style.background = `radial-gradient(circle, ${colorSchemes[currentColorScheme][1]}40, transparent)`;
                zone.style.border = `2px solid ${colorSchemes[currentColorScheme][1]}`;
                zone.style.cursor = 'pointer';
                
                // إضافة tooltip
                zone.title = `${data.page_title || 'Page'}: ${Math.round(data.attention_score)}s attention time`;
                
                return zone;
            }
            
            calculateIntensity(clickCount) {
                if (!heatmapData || heatmapData.length === 0) return 1;
                const maxClicks = Math.max(...heatmapData.map(d => d.click_count || 0));
                if (maxClicks === 0) return 1;
                return Math.ceil((clickCount / maxClicks) * currentIntensity);
            }
            
            addHeatPointEvents(point, group) {
                // عرض التول تيب عند التمرير
                point.addEventListener('mouseenter', (e) => {
                    this.showTooltip(e, group);
                    point.classList.add('selected');
                    
                    // تأثير النبضة
                    if (typeof gsap !== 'undefined') {
                        gsap.to(point, {
                            scale: 1.5,
                            duration: 0.3,
                            ease: "back.out(1.7)"
                        });
                    }
                });
                
                point.addEventListener('mouseleave', (e) => {
                    this.hideTooltip();
                    point.classList.remove('selected');
                    
                    if (typeof gsap !== 'undefined') {
                        gsap.to(point, {
                            scale: 1,
                            duration: 0.3,
                            ease: "back.out(1.7)"
                        });
                    }
                });
                
                // فتح النافذة المنبثقة عند النقر
                point.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.showClickModal(group);
                    
                    // تأثير الانفجار
                    this.createClickExplosion(e.pageX, e.pageY);
                });
            }
            
            showTooltip(event, group) {
                const tooltip = this.tooltip;
                const title = document.getElementById('tooltipTitle');
                const content = document.getElementById('tooltipContent');
                
                title.textContent = isRTL ? `${group.totalCount} نقرة` : `${group.totalCount} Clicks`;
                content.innerHTML = `
                    <div style="margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i> 
                        ${isRTL ? `${group.uniqueSessions.size} مستخدم فريد` : `${group.uniqueSessions.size} Unique Users`}
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <i class="fas fa-mouse-pointer"></i> 
                        ${isRTL ? `الموقع: (${Math.round(group.x)}, ${Math.round(group.y)})` : `Position: (${Math.round(group.x)}, ${Math.round(group.y)})`}
                    </div>
                    <div>
                        <i class="fas fa-tags"></i> 
                        ${isRTL ? 'العناصر:' : 'Elements:'} ${Array.from(group.elements).slice(0, 2).join(', ') || (isRTL ? 'غير محدد' : 'Unknown')}
                    </div>
                `;
                
                // تحديد موقع التول تيب
                const rect = event.target.getBoundingClientRect();
                
                let left = rect.left + rect.width / 2 - 150;
                let top = rect.top - 120;
                
                // التأكد من عدم خروج التول تيب من الشاشة
                if (left < 10) left = 10;
                if (left + 300 > window.innerWidth - 10) {
                    left = window.innerWidth - 310;
                }
                if (top < 10) top = rect.bottom + 10;
                
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                tooltip.classList.add('show');
            }
            
            hideTooltip() {
                this.tooltip.classList.remove('show');
            }
            
            showClickModal(group) {
                const modal = document.getElementById('clickModal');
                const body = document.getElementById('clickModalBody');
                
                // إنشاء محتوى مفصل
                const mostClicked = group.clicks.reduce((prev, current) => 
                    (prev.click_count > current.click_count) ? prev : current
                );
                
                body.innerHTML = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="stat-item">
                            <div class="stat-value">${group.totalCount}</div>
                            <div class="stat-label">${isRTL ? 'إجمالي النقرات' : 'Total Clicks'}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${group.uniqueSessions.size}</div>
                            <div class="stat-label">${isRTL ? 'مستخدمين فريدين' : 'Unique Users'}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${Math.round(group.x)}, ${Math.round(group.y)}</div>
                            <div class="stat-label">${isRTL ? 'الإحداثيات' : 'Coordinates'}</div>
                        </div>
                    </div>
                    
                    <h4 style="margin-bottom: 1rem; color: var(--primary);">
                        <i class="fas fa-info-circle"></i>
                        ${isRTL ? 'تفاصيل العنصر' : 'Element Details'}
                    </h4>
                    
                    <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                        <div style="margin-bottom: 0.5rem;">
                            <strong>${isRTL ? 'النص:' : 'Text:'}</strong> 
                            ${mostClicked.element_text || (isRTL ? 'غير متوفر' : 'N/A')}
                        </div>
                        <div style="margin-bottom: 0.5rem;">
                            <strong>${isRTL ? 'النوع:' : 'Type:'}</strong> 
                            ${mostClicked.element_type || (isRTL ? 'غير محدد' : 'Unknown')}
                        </div>
                        <div style="margin-bottom: 0.5rem;">
                            <strong>${isRTL ? 'المحدد:' : 'Selector:'}</strong> 
                            <code style="background: var(--bg-primary); padding: 0.25rem 0.5rem; border-radius: 4px;">
                                ${mostClicked.element_selector || (isRTL ? 'غير متوفر' : 'N/A')}
                            </code>
                        </div>
                        <div>
                            <strong>${isRTL ? 'الصفحة:' : 'Page:'}</strong> 
                            <a href="${mostClicked.page_url}" target="_blank" style="color: var(--primary);">
                                ${mostClicked.page_title || mostClicked.page_url}
                            </a>
                        </div>
                    </div>
                    
                    <h4 style="margin-bottom: 1rem; color: var(--primary);">
                        <i class="fas fa-chart-line"></i>
                        ${isRTL ? 'أحدث النقرات' : 'Recent Clicks'}
                    </h4>
                    
                    <div style="max-height: 300px; overflow-y: auto;">
                        ${group.clicks.slice(0, 10).map(click => `
                            <div class="click-item" style="margin-bottom: 0.5rem;">
                                <div class="click-indicator"></div>
                                <div class="click-details">
                                    <div class="click-element">${click.element_text || (isRTL ? 'عنصر غير محدد' : 'Unknown Element')}</div>
                                    <div class="click-time">${this.formatDateTime(click.clicked_at)}</div>
                                </div>
                                <div style="font-weight: 600; color: var(--primary);">${click.click_count || 1}</div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                modal.classList.add('active');
            }
            
            formatDateTime(dateString) {
                if (!dateString) return isRTL ? 'غير محدد' : 'Unknown';
                
                try {
                    const date = new Date(dateString);
                    if (isNaN(date.getTime())) return isRTL ? 'تاريخ غير صحيح' : 'Invalid Date';
                    
                    const now = new Date();
                    const diffMs = now - date;
                    const diffSeconds = Math.floor(diffMs / 1000);
                    const diffMinutes = Math.floor(diffSeconds / 60);
                    const diffHours = Math.floor(diffMinutes / 60);
                    const diffDays = Math.floor(diffHours / 24);
                    
                    if (diffSeconds < 60) {
                        return isRTL ? `منذ ${diffSeconds} ثانية` : `${diffSeconds}s ago`;
                    } else if (diffMinutes < 60) {
                        return isRTL ? `منذ ${diffMinutes} دقيقة` : `${diffMinutes}m ago`;
                    } else if (diffHours < 24) {
                        return isRTL ? `منذ ${diffHours} ساعة` : `${diffHours}h ago`;
                    } else if (diffDays < 7) {
                        return isRTL ? `منذ ${diffDays} يوم` : `${diffDays}d ago`;
                    } else {
                        return date.toLocaleDateString(isRTL ? 'ar-SA' : 'en-US', {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                } catch (error) {
                    console.error('Error formatting date:', error);
                    return isRTL ? 'خطأ في التاريخ' : 'Date Error';
                }
            }
            
            createClickExplosion(x, y) {
                for (let i = 0; i < 8; i++) {
                    const particle = document.createElement('div');
                    particle.style.position = 'fixed';
                    particle.style.left = x + 'px';
                    particle.style.top = y + 'px';
                    particle.style.width = '6px';
                    particle.style.height = '6px';
                    particle.style.background = colorSchemes[currentColorScheme][Math.floor(Math.random() * 5)];
                    particle.style.borderRadius = '50%';
                    particle.style.pointerEvents = 'none';
                    particle.style.zIndex = '10000';
                    
                    document.body.appendChild(particle);
                    
                    const angle = (i / 8) * Math.PI * 2;
                    const distance = 50 + Math.random() * 50;
                    const targetX = x + Math.cos(angle) * distance;
                    const targetY = y + Math.sin(angle) * distance;
                    
                    if (typeof gsap !== 'undefined') {
                        gsap.to(particle, {
                            x: targetX - x,
                            y: targetY - y,
                            opacity: 0,
                            scale: 0,
                            duration: 0.8,
                            ease: "power2.out",
                            onComplete: () => {
                                document.body.removeChild(particle);
                            }
                        });
                    } else {
                        // Fallback animation without GSAP
                        setTimeout(() => {
                            if (document.body.contains(particle)) {
                                document.body.removeChild(particle);
                            }
                        }, 800);
                    }
                }
            }
            
            animateHeatPoints() {
                const points = this.overlay.querySelectorAll('.heat-point, .scroll-bar, .attention-zone');
                
                if (typeof gsap !== 'undefined') {
                    gsap.from(points, {
                        scale: 0,
                        opacity: 0,
                        duration: 0.8,
                        stagger: 0.1,
                        ease: "back.out(1.7)"
                    });
                }
            }
            
            async updateStats() {
                try {
                    const response = await fetch(`?ajax=real_time_data&id=${websiteId}`);
                    const data = await response.json();
                    
                    // تحديث الإحصائيات الحية مع التأكد من صحة البيانات
                    this.animateStatValue('activeUsers', parseInt(data.active_users) || 0);
                    this.animateStatValue('totalClicks', parseInt(data.today_clicks) || 0);
                    
                    // تحديث النقرات الحديثة
                    this.updateRecentClicks(data.recent_clicks || []);
                    
                    // تحديث الصفحة الأكثر نشاطاً
                    const topPageElement = document.getElementById('topPage');
                    if (topPageElement) {
                        topPageElement.textContent = data.top_page_today || '-';
                    }
                    
                    // تحديث متوسط الوقت
                    const avgTimeElement = document.getElementById('avgTime');
                    if (avgTimeElement) {
                        const avgTime = parseFloat(data.avg_time_today) || 0;
                        avgTimeElement.textContent = this.formatDuration(avgTime);
                    }
                    
                } catch (error) {
                    console.error('Error updating stats:', error);
                }
            }
            
            formatDuration(seconds) {
                if (!seconds || isNaN(seconds)) return '0s';
                
                const totalSeconds = Math.round(seconds);
                
                if (totalSeconds < 60) {
                    return totalSeconds + 's';
                } else if (totalSeconds < 3600) {
                    const minutes = Math.floor(totalSeconds / 60);
                    const remainingSeconds = totalSeconds % 60;
                    return remainingSeconds > 0 ? `${minutes}m ${remainingSeconds}s` : `${minutes}m`;
                } else {
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
                }
            }
            
            animateStatValue(elementId, newValue) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                const currentValue = parseInt(element.textContent) || 0;
                const targetValue = parseInt(newValue) || 0;
                
                if (typeof gsap !== 'undefined') {
                    gsap.to({value: currentValue}, {
                        value: targetValue,
                        duration: 1,
                        ease: "power2.out",
                        onUpdate: function() {
                            element.textContent = Math.round(this.targets()[0].value);
                        }
                    });
                } else {
                    // Fallback animation without GSAP
                    element.textContent = targetValue;
                }
            }
            
            updateRecentClicks(clicks) {
                const container = document.getElementById('recentClicks');
                if (!container) return;
                
                container.innerHTML = '';
                
                if (!clicks || clicks.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                            <i class="fas fa-mouse-pointer" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <div>${isRTL ? 'لا توجد نقرات حديثة' : 'No recent clicks'}</div>
                        </div>
                    `;
                    return;
                }
                
                clicks.slice(0, 10).forEach((click, index) => {
                    const clickItem = document.createElement('div');
                    clickItem.className = 'click-item';
                    clickItem.style.animationDelay = (index * 0.1) + 's';
                    
                    clickItem.innerHTML = `
                        <div class="click-indicator"></div>
                        <div class="click-details">
                            <div class="click-element">${click.element_text || (isRTL ? 'عنصر غير محدد' : 'Unknown Element')}</div>
                            <div class="click-time">${this.formatTimeAgo(click.clicked_at)}</div>
                        </div>
                    `;
                    
                    container.appendChild(clickItem);
                });
            }
            
            formatTimeAgo(timestamp) {
                if (!timestamp) return isRTL ? 'غير محدد' : 'Unknown';
                
                try {
                    const now = new Date();
                    const clickTime = new Date(timestamp);
                    
                    if (isNaN(clickTime.getTime())) {
                        return isRTL ? 'تاريخ غير صحيح' : 'Invalid Date';
                    }
                    
                    const diffSeconds = Math.floor((now - clickTime) / 1000);
                    
                    if (diffSeconds < 0) {
                        return isRTL ? 'الآن' : 'Just now';
                    } else if (diffSeconds < 60) {
                        return isRTL ? `منذ ${diffSeconds} ثانية` : `${diffSeconds}s ago`;
                    } else if (diffSeconds < 3600) {
                        const minutes = Math.floor(diffSeconds / 60);
                        return isRTL ? `منذ ${minutes} دقيقة` : `${minutes}m ago`;
                    } else if (diffSeconds < 86400) {
                        const hours = Math.floor(diffSeconds / 3600);
                        return isRTL ? `منذ ${hours} ساعة` : `${hours}h ago`;
                    } else {
                        const days = Math.floor(diffSeconds / 86400);
                        return isRTL ? `منذ ${days} يوم` : `${days}d ago`;
                    }
                } catch (error) {
                    console.error('Error formatting time ago:', error);
                    return isRTL ? 'خطأ في التاريخ' : 'Date Error';
                }
            }
            
            setupEventListeners() {
                // إغلاق النوافذ المنبثقة عند النقر خارجها
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('modal')) {
                        e.target.classList.remove('active');
                    }
                });
                
                // اختصارات لوحة المفاتيح
                document.addEventListener('keydown', (e) => {
                    switch(e.key) {
                        case 'Escape':
                            document.querySelectorAll('.modal.active').forEach(modal => {
                                modal.classList.remove('active');
                            });
                            break;
                        case 'r':
                            if (e.ctrlKey || e.metaKey) {
                                e.preventDefault();
                                this.loadInitialData();
                            }
                            break;
                    }
                });
            }
            
            createParticles() {
                const particlesContainer = document.getElementById('particles');
                if (!particlesContainer) return;
                
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 10 + 's';
                    particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                    particlesContainer.appendChild(particle);
                }
            }
            
            startRealTimeUpdates() {
                // تحديث البيانات كل 30 ثانية
                setInterval(() => {
                    if (isRealTime) {
                        this.updateStats();
                    }
                }, 30000);
            }
            
            hideLoading() {
                const loadingState = document.getElementById('loadingState');
                const websitePreview = document.getElementById('websitePreview');
                
                if (typeof gsap !== 'undefined') {
                    gsap.to(loadingState, {
                        opacity: 0,
                        duration: 0.5,
                        onComplete: () => {
                            loadingState.style.display = 'none';
                            websitePreview.style.display = 'block';
                            
                            gsap.from(websitePreview, {
                                opacity: 0,
                                scale: 0.9,
                                duration: 0.8,
                                ease: "back.out(1.7)"
                            });
                        }
                    });
                } else {
                    loadingState.style.display = 'none';
                    websitePreview.style.display = 'block';
                }
            }
            
            showError(message) {
                showNotification(message, 'error');
            }
            
            showEmptyState() {
                this.overlay.innerHTML = `
                    <div style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        text-align: center;
                        color: var(--text-secondary);
                        padding: 2rem;
                    ">
                        <i class="fas fa-mouse-pointer" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <h3 style="margin-bottom: 1rem;">${isRTL ? 'لا توجد بيانات تفاعل' : 'No Interaction Data'}</h3>
                        <p>${isRTL ? 'لم يتم تسجيل أي تفاعل للفترة المحددة' : 'No interactions recorded for the selected period'}</p>
                        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.7;">
                            ${isRTL ? `نوع العرض الحالي: ${this.getViewTypeLabel()}` : `Current view: ${this.getViewTypeLabel()}`}
                        </p>
                    </div>
                `;
            }
            
            getViewTypeLabel() {
                const labels = {
                    clicks: isRTL ? 'النقرات' : 'Clicks',
                    scroll: isRTL ? 'التمرير' : 'Scroll',
                    attention: isRTL ? 'مناطق الانتباه' : 'Attention'
                };
                return labels[currentViewType] || currentViewType;
            }
        }
        
        // الوظائف العامة المحدثة
        function changeTimeRange(days) {
            const url = new URL(window.location);
            url.searchParams.set('days', days);
            window.location.href = url.toString();
        }
        
        function changePageFilter(pageUrl) {
            currentPage = pageUrl;
            
            // تحديث الـ iframe إذا تم اختيار صفحة محددة
            if (pageUrl && heatmapManager.iframe) {
                heatmapManager.iframe.src = pageUrl;
            } else if (heatmapManager.iframe) {
                heatmapManager.iframe.src = websiteUrl;
            }
            
            // إعادة تحميل البيانات
            heatmapManager.loadInitialData();
            
            showNotification(
                isRTL ? (pageUrl ? 'تم تطبيق فلتر الصفحة' : 'تم إلغاء فلتر الصفحة') 
                      : (pageUrl ? 'Page filter applied' : 'Page filter removed'), 
                'success'
            );
        }
        
        function changeViewType(type) {
            if (currentViewType === type) return;
            
            currentViewType = type;
            
            // إعادة تحميل البيانات مع النوع الجديد
            heatmapManager.loadInitialData();
            
            const typeLabels = {
                clicks: isRTL ? 'النقرات' : 'Clicks',
                scroll: isRTL ? 'التمرير' : 'Scroll Depth',
                attention: isRTL ? 'مناطق الانتباه' : 'Attention Zones'
            };
            
            showNotification(
                isRTL ? `تم تغيير العرض إلى: ${typeLabels[type]}` : `View changed to: ${typeLabels[type]}`, 
                'info'
            );
        }
        
        function changeColorScheme(scheme) {
            currentColorScheme = scheme;
            heatmapManager.renderHeatmap();
            showNotification(isRTL ? 'تم تغيير نمط الألوان' : 'Color scheme changed', 'success');
        }
        
        function updateIntensity(value) {
            currentIntensity = parseInt(value);
            heatmapManager.renderHeatmap();
        }
        
        function updateZoom(value) {
            currentZoom = parseInt(value);
            const preview = document.getElementById('websitePreview');
            const scale = currentZoom / 100;
            
            if (typeof gsap !== 'undefined') {
                gsap.to(preview, {
                    scale: scale,
                    duration: 0.5,
                    ease: "power2.out"
                });
            } else {
                preview.style.transform = `scale(${scale})`;
            }
            
            document.getElementById('zoomValue').textContent = value + '%';
        }
        
        function updateThreshold(value) {
            currentThreshold = parseInt(value);
            document.getElementById('thresholdValue').textContent = value;
            heatmapManager.renderHeatmap();
        }
        
        function toggleAnimation() {
            const toggle = document.getElementById('animationToggle');
            animationsEnabled = !animationsEnabled;
            toggle.classList.toggle('active');
            
            if (animationsEnabled) {
                showNotification(isRTL ? 'تم تفعيل الانيميشن' : 'Animations enabled', 'success');
            } else {
                showNotification(isRTL ? 'تم إيقاف الانيميشن' : 'Animations disabled', 'info');
            }
        }
        
        function toggleRealTime() {
            isRealTime = !isRealTime;
            const button = document.querySelector('button[onclick="toggleRealTime()"]');
            const text = document.getElementById('realTimeText');
            
            if (isRealTime) {
                button.style.background = 'var(--gradient-success)';
                text.textContent = isRTL ? 'مفعل' : 'Active';
                showNotification(isRTL ? 'تم تفعيل التحديث الفوري' : 'Real-time updates enabled', 'success');
                
                // بدء التحديثات المستمرة
                realTimeInterval = setInterval(() => {
                    heatmapManager.updateStats();
                }, 5000);
            } else {
                button.style.background = '';
                text.textContent = isRTL ? 'وقت فعلي' : 'Real-time';
                showNotification(isRTL ? 'تم إيقاف التحديث الفوري' : 'Real-time updates disabled', 'info');
                
                if (realTimeInterval) {
                    clearInterval(realTimeInterval);
                    realTimeInterval = null;
                }
            }
        }
        

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function showNotification(message, type = 'info') {
            const notificationBar = document.getElementById('notificationBar');
            if (!notificationBar) return;
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const icons = {
                success: 'check-circle',
                error: 'exclamation-triangle',
                warning: 'exclamation-circle',
                info: 'info-circle'
            };
            
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-${icons[type] || 'info-circle'}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${isRTL ? 'إشعار' : 'Notification'}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            notificationBar.appendChild(notification);
            
            // إزالة الإشعار تلقائياً بعد 5 ثوان
            setTimeout(() => {
                if (notification.parentElement) {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(notification, {
                            x: 100,
                            opacity: 0,
                            duration: 0.3,
                            onComplete: () => {
                                notification.remove();
                            }
                        });
                    } else {
                        notification.remove();
                    }
                }
            }, 5000);
        }
        
        // تهيئة التطبيق
        let heatmapManager;
        
        document.addEventListener('DOMContentLoaded', function() {
            // إنشاء مدير الخريطة الحرارية
            heatmapManager = new HeatmapManager();
            
            // رسالة ترحيب
            setTimeout(() => {
                showNotification(
                    isRTL ? 'مرحباً بك في خريطة التفاعل الحرارية المتقدمة' : 'Welcome to Advanced Interactive Heatmap',
                    'success'
                );
            }, 1000);
            
            // تفعيل اختصارات لوحة المفاتيح
            setTimeout(() => {
                showNotification(
                    isRTL ? 'استخدم Ctrl+R للتحديث، Ctrl+E للتصدير، ESC للإغلاق' : 'Use Ctrl+R to refresh, Ctrl+E to export, ESC to close',
                    'info'
                );
            }, 2000);
        });
        
        // تحسين الأداء
        window.addEventListener('beforeunload', function() {
            if (realTimeInterval) {
                clearInterval(realTimeInterval);
            }
        });
        
        // تحسين الاستجابة
        window.addEventListener('resize', function() {
            // إعادة تموضع العناصر عند تغيير حجم النافذة
            if (heatmapManager) {
                setTimeout(() => {
                    heatmapManager.renderHeatmap();
                }, 100);
            }
        });
    </script>
</body>
</html>