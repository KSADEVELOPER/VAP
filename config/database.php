<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $dbname = 'rack_db';
    private $username = 'track_user';
    private $password = 'track_password';
    private $charset = 'utf8mb4';
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
 
}

// دوال مساعدة عامة
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken($length = 50) {
    return bin2hex(random_bytes($length / 2));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getBrowserInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser = 'Unknown';
    
    if (preg_match('/MSIE/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera/i', $user_agent)) {
        $browser = 'Opera';
    }
    
    return $browser;
}

function getDeviceType() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $user_agent)) {
        return 'Tablet';
    } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        return 'Mobile';
    } else {
        return 'Desktop';
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

function timeAgo($datetime, $lang = 'ar') {
    $time = time() - strtotime($datetime);
    
    $units = [
        'ar' => [
            'year' => ['سنة', 'سنتان', 'سنوات'],
            'month' => ['شهر', 'شهران', 'أشهر'],
            'week' => ['أسبوع', 'أسبوعان', 'أسابيع'],
            'day' => ['يوم', 'يومان', 'أيام'],
            'hour' => ['ساعة', 'ساعتان', 'ساعات'],
            'minute' => ['دقيقة', 'دقيقتان', 'دقائق'],
            'second' => ['ثانية', 'ثانيتان', 'ثوانٍ']
        ],
        'en' => [
            'year' => ['year', 'years'],
            'month' => ['month', 'months'],
            'week' => ['week', 'weeks'],
            'day' => ['day', 'days'],
            'hour' => ['hour', 'hours'],
            'minute' => ['minute', 'minutes'],
            'second' => ['second', 'seconds']
        ]
    ];
    
    if ($time < 60) return $lang == 'ar' ? 'الآن' : 'now';
    
    $times = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute'
    ];
    
    foreach ($times as $seconds => $unit) {
        $value = intval($time / $seconds);
        if ($value >= 1) {
            if ($lang == 'ar') {
                if ($value == 1) return $units[$lang][$unit][0] . ' واحد';
                if ($value == 2) return $units[$lang][$unit][1];
                return $value . ' ' . $units[$lang][$unit][2];
            } else {
                return $value . ' ' . ($value == 1 ? $units[$lang][$unit][0] : $units[$lang][$unit][1]) . ' ago';
            }
        }
    }
}

// بدء الجلسة
session_start();

// إعدادات المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// معلومات الموقع
define('SITE_NAME', 'منصة تحليل الزوار');
define('SITE_NAME_EN', 'Visitor Analytics Platform');
define('SITE_URL', 'https://youo.info');
define('ADMIN_EMAIL', 'Z8@Hotmail.Com');

// إعدادات البريد الإلكتروني (يجب تحديثها)
define('SMTP_HOST', 'your host');
define('SMTP_PORT', 587); // 465
define('SMTP_USERNAME', 'username@yourhost.com');
define('SMTP_PASSWORD', 'password');

// إنشاء اتصال قاعدة البيانات
$db = new Database();
?>