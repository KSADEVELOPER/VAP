<?php
// api/track.php - API تتبع محسّن

// إعدادات CORS المحسّنة
$allowed_origins = [
    'https://...example.com',
    'https://snack-web-player.s3.us-west-1.amazonaws.com',
    // أضف النطاقات المصرح لها
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../classes/WebsiteManager.php';

class EnhancedAnalyticsTracker {
    private $db;
    private $websiteManager;

    public function __construct($database) {
        $this->db = $database;
        $this->websiteManager = new WebsiteManager($database);
    }

    public function track() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendResponse(['error' => 'Method not allowed'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $this->sendResponse(['error' => 'Invalid JSON data'], 400);
            }
            if (empty($data['tracking_code'])) {
                $this->sendResponse(['error' => 'Missing tracking code'], 400);
            }

            $website = $this->websiteManager->getWebsiteByTrackingCode($data['tracking_code']);
            if (!$website) {
                $this->sendResponse(['error' => 'Invalid tracking code'], 403);
            }

            $action = $_GET['action'] ?? $data['action'] ?? 'pageview';
            switch ($action) {
                case 'session':      $ok = $this->trackEnhancedSession($website['id'], $data); break;
                case 'pageview':     $ok = $this->trackEnhancedPageview($website['id'], $data); break;
                case 'click':        $ok = $this->trackEnhancedClick($website['id'], $data); break;
                case 'scroll':       $ok = $this->trackScroll($website['id'], $data); break;
                case 'time_on_page': $ok = $this->trackTimeOnPage($website['id'], $data); break;
                case 'session_end':  $ok = $this->trackSessionEnd($website['id'], $data); break;
                case 'heartbeat':    $ok = $this->trackHeartbeat($website['id'], $data); break;
                case 'detected_elements': $ok = $this->saveDetectedElements($website['id'], $data); break;
                default: $this->sendResponse(['error' => 'Unknown action'], 400);
            }

            if ($ok) {
                $this->sendResponse(['success' => true]);
            } else {
                $this->sendResponse(['error' => 'Failed to save data'], 500);
            }

        } catch (Exception $e) {
            error_log('Enhanced tracking error: ' . $e->getMessage());
            $this->sendResponse(['error' => 'Internal server error'], 500);
        }
    }

    // private function getLocationFromIP($ip) {
    //     // في التطبيق الحقيقي، يجب استخدام خدمة geolocation مثل MaxMind أو IPinfo
    //     // هنا مثال مبسط
    //     try {
    //         // يمكن استخدام خدمة مجانية مثل ipapi.co
    //         $context = stream_context_create([
    //             'http' => [
    //                 'timeout' => 5,
    //                 'user_agent' => 'AnalyticsTracker/1.0'
    //             ]
    //         ]);
            
    //         $response = file_get_contents("http://ipapi.co/{$ip}/json/", false, $context);
            
    //         if ($response) {
    //             $data = json_decode($response, true);
    //             return [
    //                 'country' => $data['country_name'] ?? null,
    //                 'city' => $data['city'] ?? null,
    //                 'region' => $data['region'] ?? null
    //             ];
    //         }
    //     } catch (Exception $e) {
    //         // في حالة الخطأ، إرجاع قيم افتراضية
    //     }
        
    //     return ['country' => null, 'city' => null, 'region' => null];
    // }

private function getLocationFromIP($ip) {
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city";
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? null,
                    'city'    => $data['city']    ?? null,
                    'region'  => $data['regionName'] ?? null,
                ];
            }
        }
    } catch (\Exception $e) {
        error_log("Geo lookup failed for {$ip}: " . $e->getMessage());
    }
    return ['country' => null, 'city' => null, 'region' => null];
}
    private function trackEnhancedSession($website_id, $data) {
        $ip      = $this->getUserIP();
        $agent   = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sid     = $data['session_id'] ?? '';
        if (!$sid) return false;

        if ($this->db->fetchOne(
            "SELECT id FROM sessions WHERE website_id = ? AND session_id = ?",
            [$website_id, $sid]
        )) return true;

        $loc    = $this->getLocationFromIP($ip);
        $dev    = $data['device_info'] ?? [];
        $type   = $dev['type'] ?? $this->getDeviceType($agent);
        $os     = $dev['os'] ?? null;
        $ver    = $dev['version'] ?? null;
        $mob    = !empty($dev['isMobile']) ? 1 : 0;
        $tab    = !empty($dev['isTablet']) ? 1 : 0;
        $browser= $this->getBrowser($agent);
        $newVis = !$this->db->fetchOne(
            "SELECT id FROM sessions WHERE website_id = ? AND user_ip = ? AND started_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$website_id, $ip]
        );

        return $this->db->query(
            "INSERT INTO sessions (
                website_id, session_id, user_ip, user_agent,
                country, city, device_type, device_os,
                device_version, is_mobile, is_tablet,
                browser, referrer, is_new_visitor
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $website_id, $sid, $ip, $agent,
                $loc['country'], $loc['city'],
                $type, $os, $ver, $mob, $tab,
                $browser, $data['referrer'] ?? '', $newVis ? 1 : 0
            ]
        );
    }

    private function trackEnhancedPageview($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;

        $ok = $this->db->query(
            "INSERT INTO page_views (
                session_id, website_id,
                page_url, page_title,
                page_content_data
            ) VALUES (?,?,?,?,?)",
            [
                $sid,
                $website_id,
                $data['page_url']   ?? '',
                $data['page_title'] ?? '',
                json_encode($data['page_content_data'] ?? [], JSON_UNESCAPED_UNICODE)
            ]
        );
        if ($ok) {
            $this->db->query(
                "UPDATE sessions SET page_views = page_views + 1 WHERE id = ?",
                [$sid]
            );
        }
        return (bool)$ok;
    }

    private function trackEnhancedClick($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;

        return $this->db->query(
            "INSERT INTO clicks (
                session_id, website_id,
                element_selector, element_text,
                element_type, action_type,
                is_product_click, product_data,
                click_x, click_y, page_url, clicked_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [
                $sid,
                $website_id,
                $data['element_selector'] ?? null,
                $data['element_text']     ?? null,
                $data['element_type']     ?? null,
                $data['action_type']      ?? 'click',
                $data['is_product_click'] ?? 0,
                $data['product_data']     ?? null,
                $data['click_x']          ?? null,
                $data['click_y']          ?? null,
                $data['page_url']         ?? null
            ]
        );
    }

    private function trackScroll($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;
        return $this->db->query(
            "UPDATE page_views
             SET scroll_depth = GREATEST(scroll_depth, ?)
             WHERE session_id = ? AND website_id = ? AND page_url = ?
             ORDER BY view_time DESC LIMIT 1",
            [
                $data['scroll_depth'] ?? 0,
                $sid,
                $website_id,
                $data['page_url'] ?? ''
            ]
        );
    }

    private function trackTimeOnPage($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;
        return $this->db->query(
            "UPDATE page_views
             SET time_on_page = ?
             WHERE session_id = ? AND website_id = ?
             ORDER BY view_time DESC LIMIT 1",
            [
                $data['time_on_page'] ?? 0,
                $sid,
                $website_id
            ]
        );
    }

    private function trackSessionEnd($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;
        return $this->db->query(
            "UPDATE sessions SET ended_at = NOW(), duration = ?, page_views = ? WHERE id = ?",
            [
                $data['duration']   ?? 0,
                $data['page_views'] ?? 1,
                $sid
            ]
        );
    }

    private function trackHeartbeat($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;
        return $this->db->query(
            "UPDATE sessions SET ended_at = NOW() WHERE id = ?",
            [$sid]
        );
    }

    private function getSessionId($website_id, $session_id_str) {
        if (!$session_id_str) return null;
        $row = $this->db->fetchOne(
            "SELECT id FROM sessions WHERE website_id = ? AND session_id = ?",
            [$website_id, $session_id_str]
        );
        return $row['id'] ?? null;
    }

    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }


    private function getDeviceType($ua) {
        if (preg_match('/tablet|ipad|playbook/i',$ua)) return 'Tablet';
        if (preg_match('/mobi|phone|android|iemobile/i',$ua)) return 'Mobile';
        return 'Desktop';
    }

    private function getBrowser($ua) {
        if (stripos($ua,'Edg')!==false)    return 'Edge';
        if (stripos($ua,'Chrome')!==false) return 'Chrome';
        if (stripos($ua,'Firefox')!==false) return 'Firefox';
        if (stripos($ua,'Safari')!==false) return 'Safari';
        return 'Unknown';
    }

    private function saveDetectedElements($website_id, $data) {
        $sid = $this->getSessionId($website_id, $data['session_id'] ?? '');
        if (!$sid) return false;
        $det = $data['detected_elements'] ?? [];
        foreach (['cart_buttons','wishlist_buttons','contact_forms'] as $type) {
            foreach ($det[$type] ?? [] as $el) {
                if (!empty($el['selector']) && ($el['confidence'] ?? 0) > 50) {
                    $this->saveElementSuggestion($website_id, $type, $el);
                }
            }
        }
        return true;
    }

    private function saveElementSuggestion($website_id, $type, $el) {
        return $this->db->query(
            "INSERT INTO element_suggestions (website_id, type, selector, confidence, created_at)
             VALUES (?,?,?,?,NOW())",
            [$website_id, $type, $el['selector'], $el['confidence']]
        );
    }

    private function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$tracker = new EnhancedAnalyticsTracker($db);
$tracker->track();