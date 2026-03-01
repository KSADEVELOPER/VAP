<?php
// // تأكد من التحقق جيداً من صلاحية URL قبل الجلب!
// $url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
// if (!$url) {
//     http_response_code(400);
//     exit('Invalid URL');
// }

// $ch = curl_init($url);
// curl_setopt_array($ch, [
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_FOLLOWLOCATION => true,
//     CURLOPT_USERAGENT      => 'AnalyticsProxy/1.0',
// ]);
// $html = curl_exec($ch);
// $info = curl_getinfo($ch);
// curl_close($ch);

// // لإبقاء الـ CSS/JS تعمل يجب تعديل روابط الموارد داخل $html لكن للبدء:
// header('Content-Type: ' . ($info['content_type'] ?? 'text/html'));
// echo $html;


// proxy.php - Enhanced version with resource handling
header('Content-Security-Policy: frame-ancestors *');
header('X-Frame-Options: ALLOWALL');

// التحقق من صحة URL
$url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if (!$url) {
    http_response_code(400);
    exit('Invalid URL');
}

// التحقق من أن URL ليس محلي (أمان)
$parsed = parse_url($url);
$host = $parsed['host'] ?? '';
if (in_array($host, ['localhost', '127.0.0.1', '::1']) || 
    preg_match('/^192\.168\./', $host) || 
    preg_match('/^10\./', $host) || 
    preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host)) {
    http_response_code(403);
    exit('Access to local resources not allowed');
}

// تهيئة cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HEADER         => true,
    CURLOPT_ENCODING       => '', // يدعم gzip, deflate
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    exit('Failed to fetch content');
}

// فصل الهيدر عن المحتوى
$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// إرسال status code الصحيح
http_response_code($httpCode);

// تحديد نوع المحتوى
$isHtml = strpos($contentType, 'text/html') !== false;

if ($isHtml) {
    // معالجة HTML لإصلاح الروابط
    $body = processHtml($body, $url);
    header('Content-Type: text/html; charset=UTF-8');
} else {
    // للملفات الأخرى (CSS, JS, صور)
    header('Content-Type: ' . $contentType);
}

echo $body;

/**
 * معالجة HTML لإصلاح الروابط النسبية
 */
function processHtml($html, $baseUrl) {
    $parsed = parse_url($baseUrl);
    $scheme = $parsed['scheme'];
    $host = $parsed['host'];
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $path = dirname($parsed['path'] ?? '/');
    
    $baseUrlWithoutFile = $scheme . '://' . $host . $port . ($path === '/' ? '' : $path);
    $fullBaseUrl = $scheme . '://' . $host . $port;
    
    // إضافة base tag لتحسين معالجة الروابط
    $baseTag = '<base href="' . $fullBaseUrl . '/" target="_parent">';
    
    if (preg_match('/<head[^>]*>/i', $html)) {
        $html = preg_replace('/(<head[^>]*>)/i', '$1' . $baseTag, $html);
    } else {
        $html = $baseTag . $html;
    }
    
    // تحويل الروابط النسبية إلى مطلقة
    $patterns = [
        // CSS files
        '/href=["\'](?!http|\/\/|mailto:|tel:|#)([^"\']*\.css[^"\']*)["\']/' => 'href="' . getCurrentScript() . '?url=' . urlencode($fullBaseUrl) . '/$1"',
        
        // JavaScript files
        '/src=["\'](?!http|\/\/|data:)([^"\']*\.js[^"\']*)["\']/' => 'src="' . getCurrentScript() . '?url=' . urlencode($fullBaseUrl) . '/$1"',
        
        // Images
        '/src=["\'](?!http|\/\/|data:)([^"\']*\.(png|jpg|jpeg|gif|svg|webp|ico)[^"\']*)["\']/' => 'src="' . getCurrentScript() . '?url=' . urlencode($fullBaseUrl) . '/$1"',
        
        // Background images in style attributes
        '/url\(["\']?(?!http|\/\/|data:)([^"\']*\.(png|jpg|jpeg|gif|svg|webp)[^"\']*)["\']?\)/' => 'url("' . getCurrentScript() . '?url=' . urlencode($fullBaseUrl) . '/$1")',
        
        // Links (but not for tracking or internal links)
        '/href=["\'](?!http|\/\/|mailto:|tel:|#)([^"\']*)["\']/' => 'href="' . getCurrentScript() . '?url=' . urlencode($fullBaseUrl) . '/$1"',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $html = preg_replace($pattern, $replacement, $html);
    }
    
    // إزالة أو تعديل scripts التي قد تسبب مشاكل
    $html = removeProblematicScripts($html);
    
    // إضافة styles لتحسين العرض في iframe
    $customStyles = '<style>
        /* تحسين العرض في iframe */
        body { margin: 0 !important; }
        * { 
            -webkit-user-select: text !important; 
            -moz-user-select: text !important; 
            user-select: text !important; 
        }
        
        /* إزالة overlay أو popup قد يحجب التفاعل */
        [style*="position: fixed"][style*="z-index"] { display: none !important; }
        .modal, .overlay, .popup { pointer-events: auto !important; }
        
        /* تحسين الروابط */
        a { cursor: pointer !important; }
        
        /* إزالة frame-busting scripts */
        iframe[src*="about:blank"] { display: none !important; }
    </style>';
    
    // إضافة الـ styles قبل إغلاق head أو في بداية body
    if (preg_match('/<\/head>/i', $html)) {
        $html = preg_replace('/<\/head>/i', $customStyles . '</head>', $html);
    } else {
        $html = $customStyles . $html;
    }
    
    return $html;
}

/**
 * إزالة أو تعديل scripts قد تسبب مشاكل
 */
function removeProblematicScripts($html) {
    // إزالة frame-busting scripts
    $html = preg_replace('/<script[^>]*>.*?if\s*\(\s*top\s*[!=]=\s*self\s*\).*?<\/script>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?if\s*\(\s*window\s*[!=]=\s*window\.top\s*\).*?<\/script>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?if\s*\(\s*self\s*[!=]=\s*top\s*\).*?<\/script>/is', '', $html);
    
    // تعديل console.log و errors لتجنب مشاكل التتبع
    $html = preg_replace('/console\.(log|error|warn)\s*\(/i', 'void(', $html);
    
    // إزالة Google Analytics أو tracking scripts أخرى قد تتداخل
    $html = preg_replace('/<script[^>]*google-analytics[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<script[^>]*gtag[^>]*>.*?<\/script>/is', '', $html);
    
    return $html;
}

/**
 * الحصول على مسار script الحالي
 */
function getCurrentScript() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    return $protocol . '://' . $host . $script;
}

/**
 * تنظيف وتحسين URL
 */
function cleanUrl($url) {
    // إزالة fragments
    $url = strtok($url, '#');
    
    // تنظيف query parameters مضاعفة
    $parts = parse_url($url);
    if (isset($parts['query'])) {
        parse_str($parts['query'], $params);
        $parts['query'] = http_build_query($params);
        $url = $parts['scheme'] . '://' . $parts['host'] . 
               (isset($parts['port']) ? ':' . $parts['port'] : '') .
               (isset($parts['path']) ? $parts['path'] : '/') . 
               (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
    
    return $url;
}
?>