<?php
// admin/get_data.php
require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/WebsiteManager.php';
require_once '../classes/PlatformManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$platformManager = new PlatformManager($db);

// التحقق من صلاحيات الإدارة
if (!$userManager->isLoggedIn() || !$userManager->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'pending_websites':
            $sql = "SELECT w.*, u.full_name as owner_name, p.name as platform_name, p.name_en as platform_name_en 
                    FROM websites w 
                    LEFT JOIN users u ON w.user_id = u.id 
                    LEFT JOIN platforms p ON w.platform_id = p.id 
                    WHERE w.status = 'pending' 
                    ORDER BY w.created_at DESC";
            $websites = $db->fetchAll($sql);
            echo json_encode($websites, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'all_users':
            $sql = "SELECT id, username, email, full_name, is_active, role, created_at,
                           (SELECT COUNT(*) FROM websites WHERE user_id = users.id) as websites_count
                    FROM users 
                    WHERE role != 'admin'
                    ORDER BY created_at DESC";
            $users = $db->fetchAll($sql);
            echo json_encode($users, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'all_websites':
            $sql = "SELECT w.*, u.full_name as owner_name, u.username, p.name as platform_name, p.name_en as platform_name_en 
                    FROM websites w 
                    LEFT JOIN users u ON w.user_id = u.id 
                    LEFT JOIN platforms p ON w.platform_id = p.id 
                    ORDER BY w.created_at DESC";
            $websites = $db->fetchAll($sql);
            echo json_encode($websites, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'all_platforms':
            $platforms = $platformManager->getAllPlatforms();
            
            // إضافة عدد المواقع لكل منصة
            foreach ($platforms as &$platform) {
                $count = $db->fetchOne("SELECT COUNT(*) as count FROM websites WHERE platform_id = ?", [$platform['id']]);
                $platform['websites_count'] = $count['count'];
            }
            
            echo json_encode($platforms, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'website_stats':
            $website_id = $_GET['website_id'] ?? 0;
            if ($website_id) {
                $stats = $websiteManager->getWebsiteStats($website_id, 30);
                echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['error' => 'Website ID required'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'system_stats':
            $stats = [
                'total_sessions' => $db->fetchOne("SELECT COUNT(*) as count FROM sessions")['count'],
                'total_page_views' => $db->fetchOne("SELECT COUNT(*) as count FROM page_views")['count'],
                'total_clicks' => $db->fetchOne("SELECT COUNT(*) as count FROM clicks")['count'],
                'total_events' => $db->fetchOne("SELECT COUNT(*) as count FROM custom_events")['count'],
                'active_sessions_today' => $db->fetchOne(
                    "SELECT COUNT(*) as count FROM sessions WHERE DATE(started_at) = CURDATE()"
                )['count'],
                'new_users_this_month' => $db->fetchOne(
                    "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
                )['count']
            ];
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'top_websites':
            $sql = "SELECT w.name, w.domain, u.full_name as owner, 
                           COUNT(s.id) as total_sessions,
                           COUNT(DISTINCT s.user_ip) as unique_visitors
                    FROM websites w 
                    LEFT JOIN users u ON w.user_id = u.id
                    LEFT JOIN sessions s ON w.id = s.website_id AND s.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    WHERE w.status = 'approved'
                    GROUP BY w.id, w.name, w.domain, u.full_name
                    ORDER BY total_sessions DESC 
                    LIMIT 10";
            $top_websites = $db->fetchAll($sql);
            echo json_encode($top_websites, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'recent_activities':
            $sql = "SELECT 'user_registration' as type, full_name as description, created_at 
                    FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    UNION ALL
                    SELECT 'website_added' as type, CONCAT(name, ' - ', domain) as description, created_at 
                    FROM websites 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY created_at DESC 
                    LIMIT 20";
            $activities = $db->fetchAll($sql);
            echo json_encode($activities, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'daily_stats':
            $days = $_GET['days'] ?? 30;
            $sql = "SELECT DATE(started_at) as date,
                           COUNT(*) as sessions,
                           COUNT(DISTINCT user_ip) as unique_visitors
                    FROM sessions 
                    WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    GROUP BY DATE(started_at)
                    ORDER BY date ASC";
            $daily_stats = $db->fetchAll($sql, [$days]);
            echo json_encode($daily_stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'browser_stats':
            $sql = "SELECT browser, COUNT(*) as count 
                    FROM sessions 
                    WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY browser 
                    ORDER BY count DESC 
                    LIMIT 10";
            $browser_stats = $db->fetchAll($sql);
            echo json_encode($browser_stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'device_stats':
            $sql = "SELECT device_type, COUNT(*) as count 
                    FROM sessions 
                    WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY device_type 
                    ORDER BY count DESC";
            $device_stats = $db->fetchAll($sql);
            echo json_encode($device_stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'country_stats':
            $sql = "SELECT country, COUNT(*) as count 
                    FROM sessions 
                    WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND country IS NOT NULL
                    GROUP BY country 
                    ORDER BY count DESC 
                    LIMIT 15";
            $country_stats = $db->fetchAll($sql);
            echo json_encode($country_stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'export_data':
            $export_type = $_GET['export_type'] ?? 'users';
            $format = $_GET['format'] ?? 'json';
            
            switch ($export_type) {
                case 'users':
                    $data = $db->fetchAll("SELECT username, email, full_name, is_active, created_at FROM users WHERE role != 'admin'");
                    break;
                case 'websites':
                    $data = $db->fetchAll("SELECT w.name, w.url, w.domain, w.status, w.created_at, u.username as owner FROM websites w LEFT JOIN users u ON w.user_id = u.id");
                    break;
                case 'sessions':
                    $data = $db->fetchAll("SELECT s.*, w.name as website_name FROM sessions s LEFT JOIN websites w ON s.website_id = w.id WHERE s.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 1000");
                    break;
                default:
                    echo json_encode(['error' => 'Invalid export type'], JSON_UNESCAPED_UNICODE);
                    exit;
            }
            
            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $export_type . '_export.csv"');
                
                if (!empty($data)) {
                    $output = fopen('php://output', 'w');
                    
                    // كتابة الرؤوس
                    fputcsv($output, array_keys($data[0]));
                    
                    // كتابة البيانات
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                    
                    fclose($output);
                }
            } else {
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid request type'], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    error_log('Admin data handler error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
}
?>