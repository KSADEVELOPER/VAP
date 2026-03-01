<?php
// activate.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';

$userManager = new UserManager($db);

// إعادة توجيه إذا كان المستخدم مسجل الدخول بالفعل
if ($userManager->isLoggedIn()) {
    redirect('dashboard.php');
}

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $result = $userManager->activateAccount($_GET['token']);
    $message = $result['success'] ? $result['message'] : $result['error'];
    $success = $result['success'];
}

// تحديد اللغة
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
$is_rtl = $lang === 'ar';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_rtl ? 'تفعيل الحساب - ' . SITE_NAME : 'Account Activation - ' . SITE_NAME_EN; ?></title>
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
            --primary-color: #2c3e50;
            --primary-light: #34495e;
            --accent-color: #3498db;
            --accent-hover: #2980b9;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --background: #f8f9fa;
            --surface: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --border-color: #e1e8ed;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 20px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo $is_rtl ? "'Tajawal'" : "'Roboto'"; ?>, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
        }
        
        .activation-container {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            width: 100%;
            max-width: 500px;
            padding: 40px 35px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .activation-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
        }
        
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            color: white;
        }
        
        .icon.success {
            background: var(--success-color);
            animation: success-pulse 2s infinite;
        }
        
        .icon.error {
            background: var(--error-color);
            animation: error-shake 0.5s ease-in-out;
        }
        
        .icon.loading {
            background: var(--accent-color);
        }
        
        @keyframes success-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes error-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .message {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            font-family: inherit;
            margin: 8px;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
        }
        
        .lang-switcher {
            position: absolute;
            top: 20px;
            <?php echo $is_rtl ? 'left: 20px;' : 'right: 20px;'; ?>
        }
        
        .lang-switcher a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
        }
        
        .lang-switcher a:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        @media (max-width: 480px) {
            .activation-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .title {
                font-size: 20px;
            }
            
            .message {
                font-size: 14px;
            }
            
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&token=<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <?php echo $is_rtl ? 'English' : 'العربية'; ?>
        </a>
    </div>
    
    <div class="activation-container">
        <?php if (empty($_GET['token'])): ?>
            <!-- في حالة عدم وجود رمز التفعيل -->
            <div class="icon error">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="title">
                <?php echo $is_rtl ? 'رمز التفعيل مفقود' : 'Activation Token Missing'; ?>
            </h1>
            <p class="message">
                <?php echo $is_rtl ? 'الرابط الذي استخدمته غير صحيح أو منتهي الصلاحية. يرجى التحقق من بريدك الإلكتروني للحصول على الرابط الصحيح.' : 'The link you used is invalid or expired. Please check your email for the correct activation link.'; ?>
            </p>
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                <?php echo $is_rtl ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?>
            </a>
            
        <?php elseif ($success): ?>
            <!-- في حالة نجاح التفعيل -->
            <div class="icon success">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="title">
                <?php echo $is_rtl ? 'تم تفعيل حسابك بنجاح!' : 'Account Activated Successfully!'; ?>
            </h1>
            <p class="message">
                <?php echo htmlspecialchars($message); ?><br><br>
                <?php echo $is_rtl ? 'يمكنك الآن تسجيل الدخول والبدء في استخدام منصة تحليل الزوار.' : 'You can now login and start using the visitor analytics platform.'; ?>
            </p>
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo $is_rtl ? 'تسجيل الدخول' : 'Login Now'; ?>
            </a>
            
        <?php else: ?>
            <!-- في حالة فشل التفعيل -->
            <div class="icon error">
                <i class="fas fa-times"></i>
            </div>
            <h1 class="title">
                <?php echo $is_rtl ? 'فشل في تفعيل الحساب' : 'Account Activation Failed'; ?>
            </h1>
            <p class="message">
                <?php echo htmlspecialchars($message); ?><br><br>
                <?php echo $is_rtl ? 'إذا كنت تواجه مشاكل في التفعيل، يرجى الاتصال بالدعم الفني أو المحاولة مرة أخرى.' : 'If you are experiencing activation issues, please contact support or try again.'; ?>
            </p>
            <div>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                    <?php echo $is_rtl ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?>
                </a>
                <button class="btn btn-secondary" onclick="resendActivation()">
                    <i class="fas fa-envelope"></i>
                    <?php echo $is_rtl ? 'إعادة إرسال بريد التفعيل' : 'Resend Activation Email'; ?>
                </button>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border-color); font-size: 14px; color: var(--text-secondary);">
            <p>
                <?php echo $is_rtl ? '© 2024 ' . SITE_NAME . '. جميع الحقوق محفوظة.' : '© 2024 ' . SITE_NAME_EN . '. All rights reserved.'; ?>
            </p>
        </div>
    </div>
    
    <script>
        // إعادة إرسال بريد التفعيل
        function resendActivation() {
            // يمكن تطوير هذه الوظيفة لاحقاً
            alert('<?php echo $is_rtl ? "ستتوفر هذه الخدمة قريباً" : "This feature will be available soon"; ?>');
        }
        
        // تأثيرات بصرية عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.activation-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // تأثير النبض للأيقونة
            const icon = document.querySelector('.icon');
            if (icon) {
                setTimeout(() => {
                    icon.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        icon.style.transform = 'scale(1)';
                    }, 200);
                }, 500);
            }
        });
        
        // تحسين UX للجوال
        if ('ontouchstart' in window) {
            document.addEventListener('touchstart', function() {}, false);
        }
    </script>
</body>
</html>