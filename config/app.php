<?php
// config/app.php - إعدادات التطبيق العامة
// App Configuration - General application settings

// معلومات التطبيق | Application Information
define('APP_NAME', 'Analytics Platform');
define('APP_NAME_AR', 'منصة تحليل الزوار');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Advanced visitor behavior analytics platform');
define('APP_DESCRIPTION_AR', 'منصة متقدمة لتحليل سلوك الزوار');

// معلومات الشركة | Company Information
define('COMPANY_NAME', 'Analytics Pro');
define('COMPANY_NAME_AR', 'أناليتكس برو');
define('COMPANY_EMAIL', 'info@analytics-pro.com');
define('COMPANY_PHONE', '+966-11-123-4567');
define('COMPANY_ADDRESS', 'الرياض، المملكة العربية السعودية');
define('COMPANY_ADDRESS_EN', 'Riyadh, Saudi Arabia');

// إعدادات البيئة | Environment Settings
define('APP_ENV', 'production'); // development, staging, production
define('APP_DEBUG', false); // تفعيل وضع التطوير | Enable debug mode
define('APP_LOG_LEVEL', 'error'); // debug, info, warning, error

// إعدادات قاعدة البيانات | Database Settings
define('DB_CONNECTION_TIMEOUT', 10);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_general_ci');

// إعدادات الأمان | Security Settings
define('SESSION_LIFETIME', 7200); // مدة الجلسة بالثواني | Session lifetime in seconds
define('SESSION_NAME', 'ANALYTICS_SESSION');
define('CSRF_TOKEN_NAME', '_token');
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 دقيقة | 15 minutes

// إعدادات التشفير | Encryption Settings
define('APP_KEY', 'base64:' . base64_encode('analytics-platform-secret-key-2024'));
define('HASH_ALGO', 'sha256');

// إعدادات البريد الإلكتروني | Email Settings
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_FROM_ADDRESS', 'noreply@analytics-platform.com');
define('MAIL_REPLY_TO', 'support@analytics-platform.com');

// إعدادات الملفات | File Settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('STORAGE_PATH', __DIR__ . '/../storage/');
define('PUBLIC_PATH', __DIR__ . '/../public/');

// إعدادات API | API Settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 1000); // طلبات في الساعة | Requests per hour
define('API_RATE_LIMIT_WINDOW', 3600); // نافذة زمنية بالثواني | Time window in seconds
define('API_TIMEOUT', 30); // ثواني | seconds

// إعدادات التخزين المؤقت | Cache Settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // ساعة واحدة | 1 hour
define('CACHE_PREFIX', 'analytics_');

// إعدادات الجلسات | Session Settings
define('SESSION_CLEANUP_PROBABILITY', 1); // 1%
define('SESSION_MAX_INACTIVE_TIME', 1800); // 30 دقيقة | 30 minutes

// إعدادات التتبع | Tracking Settings
define('TRACKING_CODE_LENGTH', 16);
define('TRACKING_CODE_PREFIX', 'AT_');
define('MAX_TRACKING_EVENTS_PER_SESSION', 1000);
define('TRACKING_DATA_RETENTION_DAYS', 365); // سنة واحدة | 1 year

// إعدادات التحليلات | Analytics Settings
define('DEFAULT_TIMEZONE', 'Asia/Riyadh');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('MAX_CHART_DATA_POINTS', 100);

// إعدادات الأداء | Performance Settings
define('QUERY_CACHE_TIME', 300); // 5 دقائق | 5 minutes
define('MAX_RESULTS_PER_PAGE', 50);
define('GZIP_COMPRESSION', true);

// إعدادات الواجهة | UI Settings
define('DEFAULT_LANGUAGE', 'ar');
define('SUPPORTED_LANGUAGES', ['ar', 'en']);
define('RTL_LANGUAGES', ['ar']);
define('ITEMS_PER_PAGE', 20);

// رسائل النظام | System Messages
$system_messages = [
    'ar' => [
        'welcome' => 'مرحباً بك في منصة تحليل الزوار',
        'login_success' => 'تم تسجيل الدخول بنجاح',
        'logout_success' => 'تم تسجيل الخروج بنجاح',
        'access_denied' => 'ليس لديك صلاحية للوصول',
        'data_saved' => 'تم حفظ البيانات بنجاح',
        'data_deleted' => 'تم حذف البيانات بنجاح',
        'error_occurred' => 'حدث خطأ أثناء العملية',
        'invalid_request' => 'طلب غير صحيح',
        'session_expired' => 'انتهت صلاحية الجلسة',
        'maintenance_mode' => 'الموقع في وضع الصيانة'
    ],
    'en' => [
        'welcome' => 'Welcome to Analytics Platform',
        'login_success' => 'Login successful',
        'logout_success' => 'Logout successful',
        'access_denied' => 'Access denied',
        'data_saved' => 'Data saved successfully',
        'data_deleted' => 'Data deleted successfully',
        'error_occurred' => 'An error occurred',
        'invalid_request' => 'Invalid request',
        'session_expired' => 'Session expired',
        'maintenance_mode' => 'Site under maintenance'
    ]
];

// إعدادات وسائل التواصل الاجتماعي | Social Media Settings
define('SOCIAL_FACEBOOK', 'https://facebook.com/analytics-platform');
define('SOCIAL_TWITTER', 'https://twitter.com/analytics_platform');
define('SOCIAL_LINKEDIN', 'https://linkedin.com/company/analytics-platform');
define('SOCIAL_GITHUB', 'https://github.com/KSADEVELOPER/VAP');

// إعدادات التحليلات الخارجية | External Analytics
define('GOOGLE_ANALYTICS_ID', ''); // GA4 Measurement ID
define('GOOGLE_TAG_MANAGER_ID', ''); // GTM Container ID

// إعدادات الخرائط والموقع | Maps and Location Settings
define('GOOGLE_MAPS_API_KEY', '');
define('DEFAULT_COUNTRY', 'SA');
define('DEFAULT_CURRENCY', 'SAR');

// إعدادات التنبيهات | Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS_NOTIFICATIONS', false);
define('NOTIFICATION_QUEUE_ENABLED', true);

// حدود النظام | System Limits
define('MAX_WEBSITES_PER_USER', 10);
define('MAX_SESSIONS_PER_WEBSITE', 1000000);
define('MAX_API_REQUESTS_PER_MINUTE', 60);

// إعدادات النسخ الاحتياطي | Backup Settings
define('BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily'); // daily, weekly, monthly
define('BACKUP_RETENTION_DAYS', 30);
define('BACKUP_PATH', STORAGE_PATH . 'backups/');

// إعدادات السجلات | Logging Settings
define('LOG_ENABLED', true);
define('LOG_PATH', STORAGE_PATH . 'logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_MAX_FILES', 30);

// إعدادات الصيانة | Maintenance Settings
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE_AR', 'الموقع تحت الصيانة. سنعود قريباً!');
define('MAINTENANCE_MESSAGE_EN', 'Site under maintenance. We\'ll be back soon!');
define('MAINTENANCE_ALLOWED_IPS', ['127.0.0.1', '::1']);

// إعدادات المحتوى | Content Settings
define('ENABLE_USER_REGISTRATION', true);
define('REQUIRE_EMAIL_VERIFICATION', true);
define('ENABLE_PASSWORD_RESET', true);
define('ENABLE_REMEMBER_ME', true);

// إعدادات الشبكات الاجتماعية | Social Login Settings
define('ENABLE_GOOGLE_LOGIN', false);
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

// إعدادات CDN | CDN Settings
define('CDN_ENABLED', false);
define('CDN_URL', 'https://cdn.analytics-platform.com');
define('STATIC_ASSETS_VERSION', time()); // لإجبار تحديث الملفات | Force asset refresh

// إعدادات البحث | Search Settings
define('SEARCH_ENABLED', true);
define('SEARCH_MIN_LENGTH', 3);
define('SEARCH_MAX_RESULTS', 50);

// إعدادات الشات والدعم | Chat and Support Settings
define('ENABLE_LIVE_CHAT', false);
define('SUPPORT_EMAIL', 'support@analytics-platform.com');
define('SUPPORT_PHONE', '+966-11-123-4567');

// قائمة الدول المدعومة | Supported Countries
$supported_countries = [
    'SA' => ['name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia', 'code' => '+966'],
    'AE' => ['name_ar' => 'الإمارات', 'name_en' => 'UAE', 'code' => '+971'],
    'KW' => ['name_ar' => 'الكويت', 'name_en' => 'Kuwait', 'code' => '+965'],
    'QA' => ['name_ar' => 'قطر', 'name_en' => 'Qatar', 'code' => '+974'],
    'BH' => ['name_ar' => 'البحرين', 'name_en' => 'Bahrain', 'code' => '+973'],
    'OM' => ['name_ar' => 'عمان', 'name_en' => 'Oman', 'code' => '+968'],
    'JO' => ['name_ar' => 'الأردن', 'name_en' => 'Jordan', 'code' => '+962'],
    'LB' => ['name_ar' => 'لبنان', 'name_en' => 'Lebanon', 'code' => '+961'],
    'EG' => ['name_ar' => 'مصر', 'name_en' => 'Egypt', 'code' => '+20'],
    'MA' => ['name_ar' => 'المغرب', 'name_en' => 'Morocco', 'code' => '+212']
];

// المناطق الزمنية المدعومة | Supported Timezones
$supported_timezones = [
    'Asia/Riyadh' => 'الرياض (GMT+3)',
    'Asia/Dubai' => 'دبي (GMT+4)',
    'Asia/Kuwait' => 'الكويت (GMT+3)',
    'Asia/Qatar' => 'قطر (GMT+3)',
    'Asia/Bahrain' => 'البحرين (GMT+3)',
    'Asia/Muscat' => 'مسقط (GMT+4)',
    'Asia/Amman' => 'عمان (GMT+3)',
    'Asia/Beirut' => 'بيروت (GMT+2)',
    'Africa/Cairo' => 'القاهرة (GMT+2)',
    'Africa/Casablanca' => 'الدار البيضاء (GMT+1)'
];

// خطط الاشتراك | Subscription Plans
$subscription_plans = [
    'free' => [
        'name_ar' => 'المجاني',
        'name_en' => 'Free',
        'price' => 0,
        'max_websites' => 1,
        'max_sessions_per_month' => 10000,
        'features' => ['basic_analytics', 'limited_history']
    ],
    'basic' => [
        'name_ar' => 'الأساسي',
        'name_en' => 'Basic',
        'price' => 99,
        'max_websites' => 5,
        'max_sessions_per_month' => 100000,
        'features' => ['advanced_analytics', 'heatmaps', 'email_reports']
    ],
    'pro' => [
        'name_ar' => 'الاحترافي',
        'name_en' => 'Professional',
        'price' => 299,
        'max_websites' => 25,
        'max_sessions_per_month' => 1000000,
        'features' => ['all_features', 'api_access', 'priority_support']
    ],
    'enterprise' => [
        'name_ar' => 'المؤسسات',
        'name_en' => 'Enterprise',
        'price' => 999,
        'max_websites' => -1, // unlimited
        'max_sessions_per_month' => -1, // unlimited
        'features' => ['custom_features', 'dedicated_support', 'white_label']
    ]
];

// دوال مساعدة | Helper Functions

/**
 * الحصول على رسالة النظام
 * Get system message
 */
function getSystemMessage($key, $lang = 'ar') {
    global $system_messages;
    return $system_messages[$lang][$key] ?? $key;
}

/**
 * التحقق من وضع الصيانة
 * Check maintenance mode
 */
function isMaintenanceMode() {
    if (!MAINTENANCE_MODE) {
        return false;
    }
    
    $user_ip = getUserIP();
    return !in_array($user_ip, MAINTENANCE_ALLOWED_IPS);
}

/**
 * الحصول على معلومات الدولة
 * Get country information
 */
function getCountryInfo($country_code) {
    global $supported_countries;
    return $supported_countries[$country_code] ?? null;
}

/**
 * تنسيق التاريخ حسب اللغة
 * Format date according to language
 */
function formatDate($date, $lang = 'ar', $format = null) {
    if (!$format) {
        $format = $lang === 'ar' ? 'Y/m/d' : 'd/m/Y';
    }
    
    return date($format, strtotime($date));
}

/**
 * تنسيق الوقت
 * Format time
 */
function formatTime($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return round($seconds / 60, 1) . 'm';
    } else {
        return round($seconds / 3600, 1) . 'h';
    }
}

/**
 * تنسيق الأرقام
 * Format numbers
 */
function formatNumber($number, $lang = 'ar') {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . ($lang === 'ar' ? 'م' : 'M');
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . ($lang === 'ar' ? 'ألف' : 'K');
    }
    return number_format($number);
}

/**
 * تحويل البايتات إلى وحدة قابلة للقراءة
 * Convert bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * إنشاء مفتاح التخزين المؤقت
 * Generate cache key
 */
function generateCacheKey($prefix, $params = []) {
    $key = CACHE_PREFIX . $prefix;
    if (!empty($params)) {
        $key .= '_' . md5(serialize($params));
    }
    return $key;
}

/**
 * تسجيل الأخطاء
 * Log errors
 */
function logError($message, $context = []) {
    if (!LOG_ENABLED) {
        return;
    }
    
    $log_file = LOG_PATH . 'error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] ERROR: $message";
    
    if (!empty($context)) {
        $log_entry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $log_entry .= PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * تسجيل المعلومات
 * Log information
 */
function logInfo($message, $context = []) {
    if (!LOG_ENABLED || APP_LOG_LEVEL === 'error') {
        return;
    }
    
    $log_file = LOG_PATH . 'info_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] INFO: $message";
    
    if (!empty($context)) {
        $log_entry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $log_entry .= PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * التحقق من صحة البريد الإلكتروني
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من قوة كلمة المرور
 * Check password strength
 */
function getPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    // طول كلمة المرور
    if (strlen($password) >= 8) $score++;
    else $feedback[] = 'استخدم 8 أحرف على الأقل';
    
    // وجود أحرف كبيرة وصغيرة
    if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score++;
    else $feedback[] = 'استخدم أحرف كبيرة وصغيرة';
    
    // وجود أرقام
    if (preg_match('/\d/', $password)) $score++;
    else $feedback[] = 'أضف أرقام';
    
    // وجود رموز خاصة
    if (preg_match('/[^a-zA-Z\d]/', $password)) $score++;
    else $feedback[] = 'أضف رموز خاصة';
    
    $strength_levels = ['ضعيف جداً', 'ضعيف', 'متوسط', 'قوي', 'قوي جداً'];
    
    return [
        'score' => $score,
        'level' => $strength_levels[$score] ?? 'ضعيف جداً',
        'feedback' => $feedback
    ];
}

/**
 * إنشاء رمز مميز آمن
 * Generate secure token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * تشفير البيانات
 * Encrypt data
 */
function encryptData($data, $key = null) {
    $key = $key ?: APP_KEY;
    $cipher = 'aes-256-gcm';
    $iv = random_bytes(16);
    
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv, $tag);
    
    return base64_encode($iv . $tag . $encrypted);
}

/**
 * فك تشفير البيانات
 * Decrypt data
 */
function decryptData($encrypted_data, $key = null) {
    $key = $key ?: APP_KEY;
    $cipher = 'aes-256-gcm';
    
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv, $tag);
}

/**
 * التحقق من معدل الطلبات
 * Check rate limit
 */
function checkRateLimit($identifier, $limit = null, $window = null) {
    $limit = $limit ?: API_RATE_LIMIT;
    $window = $window ?: API_RATE_LIMIT_WINDOW;
    
    $cache_key = generateCacheKey('rate_limit', [$identifier]);
    $current_time = time();
    
    // في تطبيق حقيقي، يجب استخدام Redis أو Memcached
    // For real application, should use Redis or Memcached
    
    return true; // مبسط للمثال | Simplified for example
}

// تحميل إعدادات إضافية حسب البيئة
// Load additional settings based on environment
if (file_exists(__DIR__ . '/env/' . APP_ENV . '.php')) {
    require_once __DIR__ . '/env/' . APP_ENV . '.php';
}

// تحديد المنطقة الزمنية
// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// إعداد معالج الأخطاء
// Setup error handler
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php_errors.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// تسجيل بدء التطبيق
// Log application start
logInfo('Application started', [
    'version' => APP_VERSION,
    'environment' => APP_ENV,
    'user_ip' => getUserIP(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);
?>