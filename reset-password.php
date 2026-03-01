<?php
// reset-password.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';

$userManager = new UserManager($db);

// إعادة توجيه إذا كان المستخدم مسجل الدخول بالفعل
if ($userManager->isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $error = 'كلمات المرور غير متطابقة';
    } else {
        $result = $userManager->updatePassword($token, $new_password);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
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
    <title><?php echo $is_rtl ? 'إعادة تعيين كلمة المرور - ' . SITE_NAME : 'Reset Password - ' . SITE_NAME_EN; ?></title>
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
        
        .reset-container {
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            width: 100%;
            max-width: 450px;
            padding: 40px 35px;
            position: relative;
            overflow: hidden;
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
        }
        
        .logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo i {
            font-size: 48px;
            color: var(--accent-color);
            margin-bottom: 16px;
        }
        
        .logo h1 {
            color: var(--primary-color);
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .logo p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--surface);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--text-secondary);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            font-family: inherit;
            position: relative;
            overflow: hidden;
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
        
        .btn-full {
            width: 100%;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .back-link {
            display: block;
            text-align: center;
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--accent-hover);
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
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .strength-bar {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin: 8px 0;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0;
            transition: var(--transition);
        }
        
        .strength-weak { background: var(--error-color); width: 25%; }
        .strength-fair { background: #f39c12; width: 50%; }
        .strength-good { background: #f1c40f; width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            body {
                padding: 10px;
            }
        }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading .btn-primary {
            background: var(--text-secondary);
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>&token=<?php echo htmlspecialchars($token); ?>">
            <?php echo $is_rtl ? 'English' : 'العربية'; ?>
        </a>
    </div>
    
    <div class="reset-container">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h1><?php echo $is_rtl ? 'إعادة تعيين كلمة المرور' : 'Reset Password'; ?></h1>
            <p><?php echo $is_rtl ? 'أدخل كلمة المرور الجديدة' : 'Enter your new password'; ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align: center;">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $is_rtl ? 'تسجيل الدخول' : 'Login Now'; ?>
                </a>
            </div>
        <?php elseif (empty($token)): ?>
            <div class="alert alert-error">
                <?php echo $is_rtl ? 'رمز إعادة التعيين مفقود أو منتهي الصلاحية' : 'Reset token is missing or expired'; ?>
            </div>
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                <?php echo $is_rtl ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?>
            </a>
        <?php else: ?>
            <form method="POST" onsubmit="submitForm(event)">
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $is_rtl ? 'كلمة المرور الجديدة' : 'New Password'; ?>
                    </label>
                    <input type="password" name="new_password" class="form-input" required 
                           placeholder="<?php echo $is_rtl ? 'أدخل كلمة المرور الجديدة' : 'Enter new password'; ?>"
                           minlength="6" onInput="checkPasswordStrength(this)">
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <div id="strength-text"><?php echo $is_rtl ? 'قوة كلمة المرور' : 'Password strength'; ?></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $is_rtl ? 'تأكيد كلمة المرور الجديدة' : 'Confirm New Password'; ?>
                    </label>
                    <input type="password" name="confirm_password" class="form-input" required 
                           placeholder="<?php echo $is_rtl ? 'أعد إدخال كلمة المرور' : 'Re-enter password'; ?>"
                           minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <?php echo $is_rtl ? 'تحديث كلمة المرور' : 'Update Password'; ?>
                </button>
            </form>
            
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'right' : 'left'; ?>"></i>
                <?php echo $is_rtl ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?>
            </a>
        <?php endif; ?>
    </div>
    
    <script>
        function submitForm(event) {
            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const newPassword = form.querySelector('input[name="new_password"]').value;
            const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
            
            // التحقق من تطابق كلمات المرور
            if (newPassword !== confirmPassword) {
                event.preventDefault();
                showAlert('<?php echo $is_rtl ? "كلمات المرور غير متطابقة" : "Passwords do not match"; ?>', 'error');
                return false;
            }
            
            // إضافة حالة التحميل
            form.classList.add('loading');
            submitBtn.disabled = true;
            
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> ' + '<?php echo $is_rtl ? "جاري التحديث..." : "Updating..."; ?>';
            
            // إزالة حالة التحميل بعد 5 ثوانِ (في حالة عدم الاستجابة)
            setTimeout(() => {
                form.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
        
        function checkPasswordStrength(input) {
            const password = input.value;
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            let strength = 0;
            let strengthLabel = '';
            
            // طول كلمة المرور
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // وجود أحرف كبيرة وصغيرة
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            // وجود أرقام
            if (/\d/.test(password)) strength++;
            
            // وجود رموز خاصة
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
            
            // تحديد مستوى القوة
            strengthFill.className = 'strength-fill';
            
            if (strength <= 1) {
                strengthFill.classList.add('strength-weak');
                strengthLabel = '<?php echo $is_rtl ? "ضعيفة" : "Weak"; ?>';
            } else if (strength <= 2) {
                strengthFill.classList.add('strength-fair');
                strengthLabel = '<?php echo $is_rtl ? "متوسطة" : "Fair"; ?>';
            } else if (strength <= 3) {
                strengthFill.classList.add('strength-good');
                strengthLabel = '<?php echo $is_rtl ? "جيدة" : "Good"; ?>';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthLabel = '<?php echo $is_rtl ? "قوية" : "Strong"; ?>';
            }
            
            strengthText.textContent = '<?php echo $is_rtl ? "قوة كلمة المرور: " : "Password strength: "; ?>' + strengthLabel;
        }
        
        function showAlert(message, type) {
            // إزالة الرسائل الموجودة
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // إنشاء رسالة جديدة
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            // إدراج الرسالة
            const container = document.querySelector('.reset-container');
            const logo = document.querySelector('.logo');
            container.insertBefore(alert, logo.nextSibling);
            
            // إزالة الرسالة بعد 5 ثوانِ
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        // تحسين UX للجوال
        if ('ontouchstart' in window) {
            document.addEventListener('touchstart', function() {}, false);
        }
    </script>
</body>
</html>