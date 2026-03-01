<?php
// error.php
$error_code = $_GET['code'] ?? '404';
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
$is_rtl = $lang === 'ar';

$errors = [
    '400' => [
        'ar' => ['title' => 'طلب خاطئ', 'message' => 'الطلب الذي أرسلته غير صحيح أو مُشوه.'],
        'en' => ['title' => 'Bad Request', 'message' => 'The request you sent is invalid or malformed.']
    ],
    '401' => [
        'ar' => ['title' => 'غير مصرح', 'message' => 'تحتاج إلى تسجيل الدخول للوصول إلى هذه الصفحة.'],
        'en' => ['title' => 'Unauthorized', 'message' => 'You need to login to access this page.']
    ],
    '403' => [
        'ar' => ['title' => 'ممنوع', 'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد.'],
        'en' => ['title' => 'Forbidden', 'message' => 'You don\'t have permission to access this resource.']
    ],
    '404' => [
        'ar' => ['title' => 'الصفحة غير موجودة', 'message' => 'الصفحة التي تبحث عنها غير موجودة أو تم نقلها.'],
        'en' => ['title' => 'Page Not Found', 'message' => 'The page you are looking for doesn\'t exist or has been moved.']
    ],
    '500' => [
        'ar' => ['title' => 'خطأ في الخادم', 'message' => 'حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً.'],
        'en' => ['title' => 'Server Error', 'message' => 'An internal server error occurred. Please try again later.']
    ]
];

$error = $errors[$error_code] ?? $errors['404'];
http_response_code((int)$error_code);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_code . ' - ' . $error[$lang]['title']; ?></title>
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
            --error-color: #e53e3e;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --background: #f7fafc;
            --surface: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo $is_rtl ? "'Tajawal'" : "'Roboto'"; ?>, sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
        }
        
        .error-container {
            background: var(--surface);
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: var(--error-color);
            margin-bottom: 20px;
            line-height: 1;
        }
        
        .error-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .error-message {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(49, 130, 206, 0.3);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
        }
        
        .illustration {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="illustration">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <div class="error-code"><?php echo $error_code; ?></div>
        <h1 class="error-title"><?php echo $error[$lang]['title']; ?></h1>
        <p class="error-message"><?php echo $error[$lang]['message']; ?></p>
        
        <div>
            <a href="/" class="btn">
                <i class="fas fa-home"></i>
                <?php echo $is_rtl ? 'الصفحة الرئيسية' : 'Home Page'; ?>
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                <?php echo $is_rtl ? 'العودة' : 'Go Back'; ?>
            </a>
        </div>
    </div>
</body>
</html>