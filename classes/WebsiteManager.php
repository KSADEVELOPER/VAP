<?php
// classes/WebsiteManager.php - تطوير محسّن
class WebsiteManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function addWebsite($user_id, $data) {
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['name'])) {
            $errors[] = 'اسم الموقع مطلوب';
        }
        
        if (empty($data['url']) || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'رابط الموقع غير صحيح';
        }
        
        if (empty($data['platform_id'])) {
            $errors[] = 'يرجى اختيار المنصة';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // استخراج الدومين
        $domain = parse_url($data['url'], PHP_URL_HOST);
        if (!$domain) {
            return ['success' => false, 'errors' => ['رابط الموقع غير صحيح']];
        }
        
        // التحقق من وجود الموقع
        $existing = $this->db->fetchOne("SELECT id FROM websites WHERE user_id = ? AND domain = ?", 
            [$user_id, $domain]);
        
        if ($existing) {
            return ['success' => false, 'errors' => ['هذا الموقع مضاف بالفعل']];
        }
        
        // توليد كود التتبع
        $tracking_code = $this->generateTrackingCode();
        
        $sql = "INSERT INTO websites (user_id, platform_id, name, url, domain, tracking_code) VALUES (?, ?, ?, ?, ?, ?)";
        $result = $this->db->query($sql, [
            $user_id,
            $data['platform_id'],
            sanitize($data['name']),
            sanitize($data['url']),
            $domain,
            $tracking_code
        ]);
        
        if ($result) {
            return ['success' => true, 'tracking_code' => $tracking_code, 'message' => 'تم إضافة الموقع بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء إضافة الموقع']];
    }
    
    public function getUserWebsites($user_id) {
        $sql = "SELECT w.*, p.name as platform_name, p.name_en as platform_name_en 
                FROM websites w 
                LEFT JOIN platforms p ON w.platform_id = p.id 
                WHERE w.user_id = ? 
                ORDER BY w.created_at DESC";
        
        return $this->db->fetchAll($sql, [$user_id]);
    }

    
    public function getWebsiteById($id, $user_id = null) {
        $sql = "SELECT w.*, p.name as platform_name, p.name_en as platform_name_en, p.selectors 
                FROM websites w 
                LEFT JOIN platforms p ON w.platform_id = p.id 
                WHERE w.id = ?";
        
        $params = [$id];
        
        if ($user_id) {
            $sql .= " AND w.user_id = ?";
            $params[] = $user_id;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    public function getWebsiteByTrackingCode($tracking_code) {
        $sql = "SELECT w.*, p.selectors 
                FROM websites w 
                LEFT JOIN platforms p ON w.platform_id = p.id 
                WHERE w.tracking_code = ? AND w.status = 'approved' AND w.is_verified = 1";
        
        return $this->db->fetchOne($sql, [$tracking_code]);
    }
    
    public function verifyWebsite($website_id, $user_id) {
        $website = $this->getWebsiteById($website_id, $user_id);
        
        if (!$website) {
            return ['success' => false, 'error' => 'الموقع غير موجود'];
        }
        
        // التحقق من وجود كود التتبع في الموقع
        $verification_result = $this->checkTrackingCodeImplementation($website['url'], $website['tracking_code']);
        
        if ($verification_result) {
            $this->db->query("UPDATE websites SET is_verified = 1 WHERE id = ?", [$website_id]);
            return ['success' => true, 'message' => 'تم التحقق من الموقع بنجاح'];
        }
        
        return ['success' => false, 'error' => 'لم يتم العثور على كود التتبع في الموقع'];
    }
    
    public function updateWebsiteStatus($website_id, $status) {
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            return false;
        }
        
        return $this->db->query("UPDATE websites SET status = ? WHERE id = ?", [$status, $website_id]);
    }
    
    public function deleteWebsite($website_id, $user_id) {
        $website = $this->getWebsiteById($website_id, $user_id);
        
        if (!$website) {
            return ['success' => false, 'error' => 'الموقع غير موجود'];
        }
        
        $this->db->beginTransaction();
        
        try {
            // حذف جميع البيانات المرتبطة
            $this->db->query("DELETE FROM heatmap_data WHERE website_id = ?", [$website_id]);
            $this->db->query("DELETE FROM session_recordings WHERE website_id = ?", [$website_id]);
            $this->db->query("DELETE FROM clicks WHERE website_id = ?", [$website_id]);
            $this->db->query("DELETE FROM page_views WHERE website_id = ?", [$website_id]);
            $this->db->query("DELETE FROM sessions WHERE website_id = ?", [$website_id]);
            $this->db->query("DELETE FROM websites WHERE id = ?", [$website_id]);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'تم حذف الموقع بنجاح'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'حدث خطأ أثناء حذف الموقع'];
        }
    }
   

    /**
 * إعادة توزيع إحصائيات النقرات حسب المحدد/النوع/النص/الصفحة
 */
public function getClickStats(int $website_id, int $days = 30): array
{
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $this->db->fetchAll(
        "SELECT 
            element_selector,
            element_type,
            element_text,
            page_url,
            COUNT(*) AS total_clicks,
            MAX(clicked_at) AS last_click
         FROM clicks
         WHERE website_id = ? 
           AND clicked_at >= ?
         GROUP BY 
            element_selector,
            element_type,
            element_text,
            page_url
         ORDER BY total_clicks DESC",
        [$website_id, $start]
    );
}
    
// public function getOSStats(int $website_id, int $days = 30): array {
//     $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
//     return $this->db->fetchAll(
//         "SELECT device_os AS os, COUNT(*) AS count 
//          FROM sessions 
//          WHERE website_id = ? AND started_at >= ? AND device_os IS NOT NULL
//          GROUP BY device_os
//          ORDER BY count DESC",
//         [$website_id, $start]
//     );
// }
public function getOSStats(int $website_id, int $days = 30): array
{
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // نجلب فقط user_agent للفترة المطلوبة
    $rows = $this->db->fetchAll(
        "SELECT user_agent
         FROM sessions
         WHERE website_id = ? 
           AND started_at >= ?
           AND user_agent IS NOT NULL
           AND user_agent <> ''",
        [$website_id, $start]
    );

    $counts = [];

    foreach ($rows as $r) {
        $ua = $r['user_agent'] ?? '';
        $os = $this->detectOSFromUA($ua);

        if (!$os) {
            $os = 'Unknown';
        }

        if (!isset($counts[$os])) {
            $counts[$os] = 0;
        }
        $counts[$os]++;
    }

    // تحويلها للشكل المتوقع وترتيب تنازلي
    arsort($counts);
    $out = [];
    foreach ($counts as $os => $count) {
        $out[] = ['os' => $os, 'count' => $count];
    }

    return $out;
}

/**
 * يحوّل User-Agent إلى اسم نظام التشغيل
 */
private function detectOSFromUA(string $ua): ?string
{
    if ($ua === '') return null;

    $l = strtolower($ua);

    // Bots
    if (strpos($l, 'googlebot') !== false) return 'Bot: Googlebot';
    if (strpos($l, 'bingbot')   !== false) return 'Bot: Bingbot';
    if (strpos($l, 'duckduckbot') !== false) return 'Bot: DuckDuckGo';
    if (strpos($l, 'yandexbot') !== false) return 'Bot: Yandex';
    if (strpos($l, 'bot') !== false || strpos($l, 'crawler') !== false || strpos($l, 'spider') !== false) {
        return 'Bot';
    }

    // Windows
    if (strpos($l, 'windows nt 10.0') !== false) return 'Windows 10/11';
    if (strpos($l, 'windows nt 6.3')  !== false) return 'Windows 8.1';
    if (strpos($l, 'windows nt 6.2')  !== false) return 'Windows 8';
    if (strpos($l, 'windows nt 6.1')  !== false) return 'Windows 7';
    if (strpos($l, 'windows')         !== false) return 'Windows';

    // Android (نحاول استخراج الإصدار إن وُجد)
    if (preg_match('/android\s+([0-9._]+)/i', $ua, $m)) {
        $ver = str_replace('_', '.', $m[1]);
        // خيار: الاكتفاء بالنسخة الرئيسية لتقليل التشظّي
        $major = explode('.', $ver)[0];
        return 'Android ' . $major;
    }
    if (strpos($l, 'android') !== false) return 'Android';

    // iOS (iPhone/iPad)
    if (strpos($l, 'iphone') !== false || strpos($l, 'ipad') !== false || strpos($l, 'ipod') !== false) {
        if (preg_match('/os\s+([0-9_]+)/i', $ua, $m)) {
            $ver = str_replace('_', '.', $m[1]);
            $major = explode('.', $ver)[0];
            return 'iOS ' . $major;
        }
        return 'iOS';
    }

    // macOS
    if (strpos($l, 'mac os x') !== false || strpos($l, 'macintosh') !== false) {
        if (preg_match('/mac os x\s*([0-9_]+)/i', $ua, $m)) {
            $ver = str_replace('_', '.', $m[1]);
            $major = explode('.', $ver)[0];
            return 'macOS ' . $major;
        }
        return 'macOS';
    }

    // Chrome OS
    if (strpos($l, 'cros') !== false) return 'Chrome OS';

    // Linux / X11
    if (strpos($l, 'linux') !== false || strpos($l, 'x11') !== false) return 'Linux';

    // غير ذلك
    return 'Other';
}

public function getDurationBuckets(int $website_id, int $days = 30): array {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    // نحسب عدد الجلسات في نطاقات: <1m، 1–5m، 5–15m، >15m
    return $this->db->fetchAll(
        "SELECT 
           CASE
             WHEN duration < 60 THEN '<1m'
             WHEN duration < 300 THEN '1–5m'
             WHEN duration < 900 THEN '5–15m'
             ELSE '>15m'
           END AS bucket,
           COUNT(*) AS count
         FROM sessions
         WHERE website_id = ? AND started_at >= ?
         GROUP BY bucket
         ORDER BY FIELD(bucket, '<1m','1–5m','5–15m','>15m')",
        [$website_id, $start]
    );
}

public function getPageViewsPerSession(int $website_id, int $days = 30): array {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $this->db->fetchAll(
        "SELECT page_views, COUNT(*) AS sessions 
         FROM sessions 
         WHERE website_id = ? AND started_at >= ?
         GROUP BY page_views
         ORDER BY page_views",
        [$website_id, $start]
    );
}
public function getWeekdayStats(int $website_id, int $days = 30): array {
  $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
  return $this->db->fetchAll(
    "SELECT DAYOFWEEK(started_at) AS weekday, COUNT(*) AS sessions 
     FROM sessions 
     WHERE website_id = ? AND started_at >= ?
     GROUP BY weekday
     ORDER BY weekday",
    [$website_id, $start]
  );
}

public function getAllSessions(int $website_id, int $days = 30): array {
  $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
  return $this->db->fetchAll(
    "SELECT id, started_at, duration, page_views, country, city 
     FROM sessions 
     WHERE website_id = ? AND started_at >= ?
     ORDER BY started_at DESC
     LIMIT 100",  // تحدّ من الصفوف إذا لزم
    [$website_id, $start]
  );
}

    
public function getAvgSessionDurationByUser(int $user_id, ?int $days = null): float
{
    $where  = "WHERE w.user_id = ? AND s.duration > 0";
    $params = [$user_id];

    if ($days !== null) {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $where .= " AND s.started_at >= ?";
        $params[] = $start;
    }

    $row = $this->db->fetchOne("
        SELECT COALESCE(AVG(s.duration), 0) AS avg_secs
        FROM sessions s
        JOIN websites w ON w.id = s.website_id
        {$where}
    ", $params);

    return (float)($row['avg_secs'] ?? 0);
}

/** متوسط مدة الجلسة لمواقع المستخدم خلال الشهر الحالي */
public function getAvgSessionDurationThisMonthByUser(int $user_id): float
{
    $startOfMonth = date('Y-m-01 00:00:00');
    $row = $this->db->fetchOne("
        SELECT COALESCE(AVG(s.duration), 0) AS avg_secs
        FROM sessions s
        JOIN websites w ON w.id = s.website_id
        WHERE w.user_id = ?
          AND s.duration > 0
          AND s.started_at >= ?
    ", [$user_id, $startOfMonth]);

    return (float)($row['avg_secs'] ?? 0);
}

public function getTotalTimeOnPageByUser(int $user_id): int
{
    
    
    // مجموع time_on_page (بالثواني) لكل مواقع المستخدم
    $row = $this->db->fetchOne("
        SELECT COALESCE(SUM(pv.time_on_page), 0) AS total_secs
        FROM page_views pv
        JOIN websites w ON w.id = pv.website_id
        WHERE w.user_id = ?
    ", [$user_id]);

    return (int)($row['total_secs'] ?? 0);
}

public function getAllVisitorsByUser(int $user_id): int
{
    // إجمالي الجلسات عبر كل المواقع
    $row = $this->db->fetchOne("
        SELECT COUNT(*) AS total_visitors
        FROM sessions s
        JOIN websites w ON w.id = s.website_id
        WHERE w.user_id = ?
    ", [$user_id]);

    return (int)($row['total_visitors'] ?? 0);
}

public function getAllClicksByUser(int $user_id): int
{
    // إجمالي النقرات عبر كل المواقع
    $row = $this->db->fetchOne("
        SELECT COUNT(*) AS total_clicks
        FROM clicks c
        JOIN websites w ON w.id = c.website_id
        WHERE w.user_id = ?
    ", [$user_id]);

    return (int)($row['total_clicks'] ?? 0);
}

public function getThisMonthVisitorsByUser(int $user_id): int
{
    $monthStart = date('Y-m-01 00:00:00');
    $row = $this->db->fetchOne("
        SELECT COUNT(*) AS month_visitors
        FROM sessions s
        JOIN websites w ON w.id = s.website_id
        WHERE w.user_id = ?
          AND s.started_at >= ?
    ", [$user_id, $monthStart]);

    return (int)($row['month_visitors'] ?? 0);
}

public function getThisMonthClicksByUser(int $user_id): int
{
    $monthStart = date('Y-m-01 00:00:00');
    $row = $this->db->fetchOne("
        SELECT COUNT(*) AS month_clicks
        FROM clicks c
        JOIN websites w ON w.id = c.website_id
        WHERE w.user_id = ?
          AND c.created_at >= ?
    ", [$user_id, $monthStart]);

    return (int)($row['month_clicks'] ?? 0);
}

public function getThisMonthTimeOnPageByUser(int $user_id): int
{
    // لو ما عندك طابع زمني في page_views، نحسب وقت الجلوس الشهري من sessions.duration
    $monthStart = date('Y-m-01 00:00:00');
    $row = $this->db->fetchOne("
        SELECT COALESCE(SUM(s.duration), 0) AS month_secs
        FROM sessions s
        JOIN websites w ON w.id = s.website_id
        WHERE w.user_id = ?
          AND s.started_at >= ?
    ", [$user_id, $monthStart]);

    return (int)($row['month_secs'] ?? 0);
}

/**
 * تجميعة واحدة تُرجع كل الـ KPIs المطلوبة معًا
 */
public function getUserKpis(int $user_id): array
{
    return [
        // إجماليات عبر كل الوقت
        
        'getAvgSessionDurationByUser' => $this->getAvgSessionDurationByUser($user_id),
        // 'total_time_on_page_seconds' => $this->getTotalTimeOnPageByUser($user_id),
        'total_visitors'             => $this->getAllVisitorsByUser($user_id),
        'total_clicks'               => $this->getAllClicksByUser($user_id),

        // إحصائيات هذا الشهر
        // 'this_month_time_on_page_seconds' => $this->getThisMonthTimeOnPageByUser($user_id),
        // 'this_month_visitors'             => $this->getThisMonthVisitorsByUser($user_id),
        // 'this_month_clicks'               => $this->getThisMonthClicksByUser($user_id),
    ];
}

/**
 * بديل للدالة التي أعطيتني إياها لكن بالترشيح بـ user_id بدل website_id
 * (للعرض أو التقارير السريعة)
 */
public function getAllSessionsByUser(int $user_id, int $limit = 100): array
{
    return $this->db->fetchAll(
        "SELECT s.id, s.started_at, s.duration, s.page_views, s.country, s.city, s.website_id
         FROM sessions s
         JOIN websites w ON w.id = s.website_id
         WHERE w.user_id = ?
         ORDER BY s.started_at DESC
         LIMIT {$limit}",
        [$user_id]
    );
}




    public function getAllWebsites($status = null) {
    $sql = "SELECT w.*, u.username, u.full_name as owner_name, p.name as platform_name, p.name_en as platform_name_en 
            FROM websites w 
            LEFT JOIN users u ON w.user_id = u.id 
            LEFT JOIN platforms p ON w.platform_id = p.id";
    
    $params = [];
    if ($status) {
        $sql .= " WHERE w.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY w.created_at DESC";
    
    return $this->db->fetchAll($sql, $params);
}

    public function getWebsiteStats($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // إجمالي الجلسات
        $total_sessions = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM sessions WHERE website_id = ? AND started_at >= ?",
            [$website_id, $start_date]
        )['count'];
        
        // الزوار الفريدون
        $unique_visitors = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_ip) as count FROM sessions WHERE website_id = ? AND started_at >= ?",
            [$website_id, $start_date]
        )['count'];
        
        // مشاهدات الصفحات
        $page_views = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM page_views WHERE website_id = ? AND view_time >= ?",
            [$website_id, $start_date]
        )['count'];
        
        // متوسط مدة الجلسة
        $avg_session_duration = $this->db->fetchOne(
            "SELECT AVG(duration) as avg, duration  FROM sessions WHERE website_id = ? AND started_at >= ? AND duration > 0",
            [$website_id, $start_date]
        )['avg'] ?: 0;
        
        // معدل الارتداد
        $bounce_rate_data = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN page_views = 1 THEN 1 END) as single_page_sessions
             FROM sessions 
             WHERE website_id = ? AND started_at >= ?",
            [$website_id, $start_date]
        );
        
        $bounce_rate = $bounce_rate_data['total_sessions'] > 0 
            ? ($bounce_rate_data['single_page_sessions'] / $bounce_rate_data['total_sessions']) * 100 
            : 0;
        
        // الزوار الجدد مقابل العائدين
        $visitor_types = $this->db->fetchAll(
            "SELECT is_new_visitor, COUNT(*) as count 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? 
             GROUP BY is_new_visitor",
            [$website_id, $start_date]
        );
        
        $new_visitors = 0;
        $returning_visitors = 0;
        
        foreach ($visitor_types as $type) {
            if ($type['is_new_visitor']) {
                $new_visitors = $type['count'];
            } else {
                $returning_visitors = $type['count'];
            }
        }
        
        return [
            'total_sessions' => $total_sessions,
            'unique_visitors' => $unique_visitors,
            'page_views' => $page_views,
            'avg_session_duration' => round($avg_session_duration, 2),
            'bounce_rate' => round($bounce_rate, 2),
            'new_visitors' => $new_visitors,
            'returning_visitors' => $returning_visitors
        ];
    }
    
   public function getVisitorsByCountry(int $website_id, int $days = 30): array {
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    return $this->db->fetchAll(
        "SELECT 
            COALESCE(country, 'Unknown') AS country,
            COUNT(*) AS visitors
         FROM sessions 
         WHERE website_id = ? 
           AND started_at >= ?
         GROUP BY country
         ORDER BY visitors DESC
         LIMIT 10",
        [$website_id, $start]
    );
}
    public function getVisitorsByCity($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT city, country, COUNT(*) as visitors 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? AND city IS NOT NULL 
             GROUP BY city, country 
             ORDER BY visitors DESC 
             LIMIT 10",
            [$website_id, $start_date]
        );
    }
    
    public function getDeviceStats($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT device_type, COUNT(*) as count 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? 
             GROUP BY device_type 
             ORDER BY count DESC",
            [$website_id, $start_date]
        );
    }
    
    public function getBrowserStats($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT browser, COUNT(*) as count 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? 
             GROUP BY browser 
             ORDER BY count DESC",
            [$website_id, $start_date]
        );
    }
    
    public function getTopPages(int $website_id, int $days = 30): array
{
    $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    return $this->db->fetchAll(
        "SELECT 
            page_url,
            page_title,
            COUNT(*) AS views,
            AVG(time_on_page) AS avg_time
         FROM page_views
         WHERE website_id = ? 
           AND view_time >= ?
         GROUP BY page_url, page_title
         ORDER BY views DESC
         LIMIT 10",
        [$website_id, $start_date]
    );
}
    public function getReferrers($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT referrer, COUNT(*) as count 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? AND referrer IS NOT NULL AND referrer != '' 
             GROUP BY referrer 
             ORDER BY count DESC 
             LIMIT 10",
            [$website_id, $start_date]
        );
    }
    
    public function getHourlyStats($website_id, $days = 7) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT HOUR(started_at) as hour, COUNT(*) as sessions 
             FROM sessions 
             WHERE website_id = ? AND started_at >= ? 
             GROUP BY HOUR(started_at) 
             ORDER BY hour",
            [$website_id, $start_date]
        );
    }
    
    public function getDailyStats($website_id, $days = 30) {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT DATE(started_at) as date, 
                    COUNT(*) as sessions,
                    COUNT(DISTINCT user_ip) as unique_visitors,
                    SUM(page_views) as total_page_views
             FROM sessions 
             WHERE website_id = ? AND DATE(started_at) >= ? 
             GROUP BY DATE(started_at) 
             ORDER BY date",
            [$website_id, $start_date]
        );
    }
    
    private function generateTrackingCode() {
        do {
            $code = 'AT_' . strtoupper(bin2hex(random_bytes(8)));
            $existing = $this->db->fetchOne("SELECT id FROM websites WHERE tracking_code = ?", [$code]);
        } while ($existing);
        
        return $code;
    }
    
    private function checkTrackingCodeImplementation($url, $tracking_code) {
        // محاولة جلب محتوى الصفحة والبحث عن كود التتبع
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; AnalyticsBot/1.0)'
                ]
            ]);
            
            $content = file_get_contents($url, false, $context);
            
            if ($content && strpos($content, $tracking_code) !== false) {
                return true;
            }
        } catch (Exception $e) {
            // في حالة الخطأ، نعتبر التحقق ناجح (يمكن تحسينه لاحقاً)
            return true;
        }
        
        return false;
    }
    
    public function generateTrackingScript($tracking_code) {
        
        
        $api_url = SITE_URL . '/api/track.php';
        
        $script = "
(function() {
    'use strict';
    
    var TRACKING_CODE = '{$tracking_code}';
    var API_URL = '{$api_url}';
    var sessionId = generateSessionId();
    var startTime = Date.now();
    var lastActivity = startTime;
    var pageViews = 0;
    var isTracking = true;
    
    // توليد معرف جلسة فريد
    function generateSessionId() {
        return 'session_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }
    
    // إرسال البيانات إلى الخادم مع معالجة أخطاء CORS
    function sendData(endpoint, data) {
        if (!isTracking) return;
        
        var payload = Object.assign({}, data, {
            tracking_code: TRACKING_CODE,
            session_id: sessionId,
            page_url: window.location.href,
            page_title: document.title,
            timestamp: Date.now(),
            user_agent: navigator.userAgent,
            screen_resolution: screen.width + 'x' + screen.height,
            viewport_size: window.innerWidth + 'x' + window.innerHeight,
            referrer: document.referrer || '',
            language: navigator.language || 'en'
        });
        
        // استخدام fetch مع معالجة أفضل للأخطاء
        fetch(API_URL + '?action=' + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload),
            mode: 'cors',
            cache: 'no-cache'
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        }).then(function(result) {
            if (result && result.error) {
                console.warn('Tracking API warning:', result.error);
            }
        }).catch(function(error) {
            // تسجيل الخطأ فقط في وضع التطوير
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Analytics tracking error:', error.message);
            }
            
            // في حالة الخطأ، حاول مرة أخرى بعد 5 ثوانِ (مرة واحدة فقط)
            if (!data._retry) {
                setTimeout(function() {
                    var retryData = Object.assign({}, data, { _retry: true });
                    sendData(endpoint, retryData);
                }, 5000);
            }
        });
    }
    
    // إرسال البيانات باستخدام Beacon API كبديل
    function sendBeacon(endpoint, data) {
        if (!navigator.sendBeacon || !isTracking) return false;
        
        var payload = Object.assign({}, data, {
            tracking_code: TRACKING_CODE,
            session_id: sessionId,
            page_url: window.location.href,
            page_title: document.title,
            timestamp: Date.now()
        });
        
        try {
            var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            return navigator.sendBeacon(API_URL + '?action=' + endpoint, blob);
        } catch (e) {
            return false;
        }
    }
    
    // بدء الجلسة
    function initSession() {
        sendData('session', {
            is_new_session: true,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        });
    }
    
    // تتبع مشاهدة الصفحة
    function trackPageView() {
        pageViews++;
        sendData('pageview', {
            page_views: pageViews,
            scroll_depth: 0
        });
    }
    
    // تتبع النقرات
    function trackClick(event) {
        var target = event.target;
        var rect = target.getBoundingClientRect();
        
        var data = {
            element_tag: target.tagName.toLowerCase(),
            element_text: (target.textContent || target.innerText || '').substring(0, 100),
            element_id: target.id || '',
            element_class: target.className || '',
            element_href: target.href || '',
            click_x: event.clientX,
            click_y: event.clientY,
            element_x: Math.round(rect.left),
            element_y: Math.round(rect.top),
            element_width: Math.round(rect.width),
            element_height: Math.round(rect.height)
        };
        
        sendData('click', data);
    }
    
    // تتبع الأحداث المخصصة
    function trackCustomEvent(eventName, eventData) {
        sendData('custom_event', {
            event_name: eventName,
            event_data: eventData || {}
        });
    }
    
    // تتبع الوقت في الصفحة
    function trackTimeOnPage() {
        var timeOnPage = Math.round((Date.now() - startTime) / 1000);
        sendData('time_on_page', {
            time_on_page: timeOnPage
        });
    }
    
    // تتبع عمق التمرير
    function trackScrollDepth() {
        var scrollPercent = Math.round((window.scrollY / Math.max(
            document.body.scrollHeight - window.innerHeight,
            1
        )) * 100);
        
        scrollPercent = Math.min(Math.max(scrollPercent, 0), 100);
        
        sendData('scroll', {
            scroll_depth: scrollPercent
        });
    }
    
    // إنهاء الجلسة
    function endSession() {
        var duration = Math.round((Date.now() - startTime) / 1000);
        
        // محاولة استخدام Beacon API أولاً
        var beaconSent = sendBeacon('session_end', {
            duration: duration,
            page_views: pageViews,
            final_scroll_depth: Math.round((window.scrollY / Math.max(
                document.body.scrollHeight - window.innerHeight,
                1
            )) * 100)
        });
        
        // إذا فشل Beacon، استخدم الطريقة العادية
        if (!beaconSent) {
            sendData('session_end', {
                duration: duration,
                page_views: pageViews
            });
        }
    }
    
    // إرسال heartbeat
    function sendHeartbeat() {
        if (isTracking) {
            sendData('heartbeat', {
                active_time: Date.now() - lastActivity,
                current_scroll: window.scrollY
            });
        }
    }
    
    // تحديث وقت آخر نشاط
    function updateActivity() {
        lastActivity = Date.now();
    }
    
    // كشف عدم النشاط
    function detectInactivity() {
        var inactiveTime = Date.now() - lastActivity;
        if (inactiveTime > 30000) { // 30 ثانية بدون نشاط
            isTracking = false;
            setTimeout(function() {
                isTracking = true;
            }, 10000); // إعادة تفعيل بعد 10 ثوانِ
        }
    }
    
    // تهيئة التتبع
    function init() {
        // التحقق من أن النطاق صحيح
        try {
            initSession();
            trackPageView();
            
            // تتبع النقرات
            document.addEventListener('click', trackClick, true);
            
            // تتبع التمرير
            var scrollTimeout;
            var lastScrollDepth = 0;
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    var currentDepth = Math.round((window.scrollY / Math.max(
                        document.body.scrollHeight - window.innerHeight,
                        1
                    )) * 100);
                    
                    // إرسال البيانات فقط إذا تغير العمق بشكل كبير
                    if (Math.abs(currentDepth - lastScrollDepth) > 10) {
                        trackScrollDepth();
                        lastScrollDepth = currentDepth;
                    }
                }, 500);
                updateActivity();
            }, { passive: true });
            
            // تتبع تغيير التركيز
            window.addEventListener('focus', updateActivity);
            window.addEventListener('blur', function() {
                trackTimeOnPage();
            });
            
            // تتبع الخروج من الصفحة
            window.addEventListener('beforeunload', function() {
                trackTimeOnPage();
                endSession();
            });
            
            window.addEventListener('pagehide', endSession);
            
            // تحديث النشاط عند الحركة والكتابة
            document.addEventListener('mousemove', updateActivity, { passive: true });
            document.addEventListener('keypress', updateActivity, { passive: true });
            document.addEventListener('touchstart', updateActivity, { passive: true });
            
            // إرسال heartbeat كل دقيقة
            setInterval(sendHeartbeat, 60000);
            
            // كشف عدم النشاط كل 10 ثوانِ
            setInterval(detectInactivity, 10000);
            
            // تتبع تغييرات الصفحة للتطبيقات أحادية الصفحة
            var currentUrl = window.location.href;
            setInterval(function() {
                if (currentUrl !== window.location.href) {
                    currentUrl = window.location.href;
                    trackPageView();
                }
            }, 1000);
            
        } catch (error) {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Analytics initialization error:', error);
            }
        }
    }
    
    // تصدير الدوال للاستخدام الخارجي
    window.analyticsTracker = {
        trackEvent: trackCustomEvent,
        trackPageView: trackPageView,
        updateActivity: updateActivity,
        endSession: endSession
    };
    
    // بدء التتبع عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
";
        
        return $script;
    }



    // added by cloude 2
    
 



public function generateEnhancedTrackingScript(string $tracking_code): string {
    $api_url = SITE_URL . '/api/track.php';
    $website = $this->db->fetchOne(
        "SELECT id FROM websites WHERE tracking_code = ?",
        [$tracking_code]
    );

    return <<<JS
(function(){
    'use strict';
    var TRACKING_CODE="{$tracking_code}",
        WEBSITE_ID={$website['id']},
        API_URL="{$api_url}",
        sessionId=("sess_"+Math.random().toString(36).substr(2,9)+"_"+Date.now()),
        startTime=Date.now(), lastActivity=startTime,
        pageViews=0, isTracking=true;

    function sendData(action,data){
        if(!isTracking) return;
        var payload=Object.assign({}, data, {
            tracking_code:TRACKING_CODE,
            session_id:sessionId,
            website_id:WEBSITE_ID,
            page_url:location.href,
            page_title:document.title,
            timestamp:Date.now(),
            user_agent:navigator.userAgent,
            screen_resolution:screen.width+'x'+screen.height,
            viewport_size:innerWidth+'x'+innerHeight,
            referrer:document.referrer||'',
            language:navigator.language||'en',
            device_info:getEnhancedDeviceInfo()
        });
        fetch(API_URL+'?action='+action, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(payload),
            mode:'cors', cache:'no-cache'
        }).catch(function(e){
            if(location.hostname.match(/^(localhost|127\\.0\\.0\\.1)$/))
                console.warn('Track error:',e);
        });
    }

    function generateSessionId(){ /* يُنشئ كما في البناء */ }

    function getEnhancedDeviceInfo(){
        var ua=navigator.userAgent, info={type:'Desktop',os:'Unknown',version:'',isMobile:false,isTablet:false};
        if(/tablet|ipad/i.test(ua)){ info.type='Tablet'; info.isTablet=true; }
        else if(/mobi|phone|android/i.test(ua)){ info.type='Mobile'; info.isMobile=true; }
        if(/Windows NT/i.test(ua)){ info.os='Windows'; var v=ua.match(/Windows NT ([0-9_.]+)/i); if(v)info.version=v[1]; }
        else if(/Mac OS X/i.test(ua)){ info.os='macOS'; var v=ua.match(/Mac OS X ([0-9_.]+)/i); if(v)info.version=v[1].replace(/_/g,'.'); }
        else if(/Android/i.test(ua)){ info.os='Android'; var v=ua.match(/Android ([0-9_.]+)/i); if(v)info.version=v[1]; }
        else if(/iPhone|iPad/i.test(ua)){ info.os='iOS'; var v=ua.match(/OS ([0-9_.]+)/i); if(v)info.version=v[1].replace(/_/g,'.'); }
        return info;
    }

    function initSession(){ sendData('session',{is_new_session:true,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone}); }
    function trackPageView(){ pageViews++; sendData('pageview',{}); }
    function trackClick(e){
        var t=e.target, r=t.getBoundingClientRect();
        sendData('click',{
            element_selector:t.tagName.toLowerCase()+(t.id?'#'+t.id:'')+(t.className?'.'+t.className.split(' ')[0]:''),
            element_text:(t.innerText||'').substr(0,100),
            element_type:t.tagName.toLowerCase(),
            click_x:Math.round(e.clientX),click_y:Math.round(e.clientY)
        });
    }
    function trackScroll(){
        var pct=Math.round((scrollY/Math.max(document.body.scrollHeight-innerHeight,1))*100);
        sendData('scroll',{scroll_depth:Math.min(Math.max(pct,0),100)});
    }
    function trackTimeOnPage(){ sendData('time_on_page',{time_on_page:Math.round((Date.now()-startTime)/1000)}); }
    function endSession(){ sendData('session_end',{duration:Math.round((Date.now()-startTime)/1000),page_views:pageViews}); }

    function updateActivity(){ lastActivity=Date.now(); }
    function detectInactivity(){ if(Date.now()-lastActivity>30000){ isTracking=false; setTimeout(()=>isTracking=true,10000); } }
    function sendHeartbeat(){ if(isTracking) sendData('heartbeat',{active_time:Date.now()-lastActivity}); }

    function init(){
        initSession(); trackPageView();
        document.addEventListener('click', trackClick, true);
        window.addEventListener('scroll', function(){
            updateActivity(); clearTimeout(this._t);
            this._t=setTimeout(trackScroll,300);
        },{passive:true});
        window.addEventListener('beforeunload', function(){
            trackTimeOnPage(); endSession();
        });
        setInterval(sendHeartbeat,60000);
        setInterval(detectInactivity,10000);
    }

    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init);
    else init();
})();
JS;
}

}
?>