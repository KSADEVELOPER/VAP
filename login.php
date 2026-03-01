<?php
// auth.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';


$userManager = new UserManager($db);

// إذا كان المستخدم مسجل الدخول، نوجهه للصفحة الرئيسية
if ($userManager->isLoggedIn()) {
    if ($userManager->isAdmin()) {
        header('Location: admin/');
    } else {
        header('Location: dashboard.php&lang='.$lang);
    }
    exit;
}

// تحديد اللغة
$is_rtl = $lang === 'ar';

// معالجة رسائل الخطأ أو النجاح
$message = '';
$message_type = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// التحقق من النماذج المقدمة
$form_errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = $userManager->login($username, $password);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $message = $result['error'];
            $message_type = 'error';
        }
    }
    elseif ($action === 'login_temp') {
        $email = $_POST['email'] ?? '';
        
        $result = $userManager->resetPassword($email);
        
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['error'];
            $message_type = 'error';
        }
    }
    elseif ($action === 'register') {
        $data = [
            'full_name' => $_POST['fullname'] ?? '',
            'email' => $_POST['email'] ?? '',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        $result = $userManager->register($data);
        
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $form_errors = $result['errors'];
            $form_data = $data;
            $message = implode('<br>', $form_errors);
            $message_type = 'error';
        }
    }
}

// تحويل الرسالة إلى HTML
$message_html = '';
if (!empty($message)) {
    $icon = '';
    if ($message_type === 'success') $icon = 'fa-check-circle';
    if ($message_type === 'error') $icon = 'fa-exclamation-circle';
    if ($message_type === 'warning') $icon = 'fa-exclamation-triangle';
    
    $message_html = '<div class="message message-'.$message_type.'">
        <i class="fas '.$icon.'"></i>
        <span>'.$message.'</span>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة تحليل الزوار - تسجيل الدخول والتسجيل</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a365d;
            --primary-light: #2d3748;
            --accent-color: #3182ce;
            --accent-hover: #2b6cb0;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --warning-color: #dd6b20;
            --background: #f7fafc;
            --surface: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* الرأس */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><linearGradient id="a" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></linearGradient></defs><rect width="100" height="20" fill="url(%23a)"/></svg>');
            background-size: cover;
            opacity: 0.1;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 32px;
            position: relative;
            z-index: 2;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        
        .logo-text p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        .btn-login {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-login:hover {
            background: white;
            color: var(--primary-color);
        }
        
        .lang-switch {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: var(--transition);
        }
        
        .lang-switch:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* محتوى الصفحة */
        .page-content {
            display: flex;
            flex: 1;
            padding: 40px 32px;
            background: var(--background);
        }
        
        .auth-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            background: var(--surface);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .auth-illustration {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .auth-illustration::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><linearGradient id="a" x1="0" y1="0" x2="100" y2="100"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0.05"/></linearGradient></defs><rect width="100" height="100" fill="url(%23a)"/></svg>');
            background-size: cover;
            opacity: 0.2;
        }
        
        .illustration-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
        }
        
        .illustration-icon {
            font-size: 80px;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.9);
        }
        
        .illustration-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .illustration-description {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .auth-form-container {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .form-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 40px;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .auth-tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-muted);
            transition: var(--transition);
            position: relative;
        }
        
        .auth-tab.active {
            color: var(--accent-color);
        }
        
        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
            border-radius: 3px 3px 0 0;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .form-input {
            width: 100%;
            padding: 15px 45px 15px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            font-family: 'Tajawal', sans-serif;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.2);
        }
        
        .password-strength {
            margin-top: 8px;
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            background: var(--error-color);
            transition: var(--transition);
        }
        
        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: var(--text-muted);
        }
        
        .password-requirements {
            margin-top: 10px;
            padding: 0;
            list-style: none;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
            font-size: 13px;
            display: flex;
            align-items: center;
        }
        
        .password-requirements li i {
            margin-left: 5px;
            font-size: 12px;
        }
        
        .requirement-met {
            color: var(--success-color);
        }
        
        .requirement-not-met {
            color: var(--text-muted);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .form-check-input {
            margin-left: 10px;
        }
        
        .form-check-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .form-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .form-link:hover {
            text-decoration: underline;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Tajawal', sans-serif;
        }
        
        .btn-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(49, 130, 206, 0.3);
        }
        
        .btn-submit:disabled {
            background: var(--border-color);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-muted);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
        
        .divider-text {
            padding: 0 15px;
            font-size: 14px;
        }
        
        .alternative-login {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .alt-login-btn {
            flex: 1;
            padding: 12px;
            background: var(--surface);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .alt-login-btn:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        /* التذييل */
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 40px 32px 20px;
            text-align: center;
            margin-top: auto;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        
        .footer-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: white;
        }
        
        .footer-bottom {
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        
        /* الرسائل */
        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message-success {
            background: rgba(56, 161, 105, 0.1);
            border: 1px solid rgba(56, 161, 105, 0.3);
            color: var(--success-color);
        }
        
        .message-error {
            background: rgba(229, 62, 62, 0.1);
            border: 1px solid rgba(229, 62, 62, 0.3);
            color: var(--error-color);
        }
        
        .message-warning {
            background: rgba(221, 107, 32, 0.1);
            border: 1px solid rgba(221, 107, 32, 0.3);
            color: var(--warning-color);
        }
        
        /* التجاوب */
        @media (max-width: 992px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-illustration {
                padding: 30px;
            }
            
            .illustration-icon {
                font-size: 60px;
                margin-bottom: 20px;
            }
            
            .illustration-title {
                font-size: 28px;
            }
            
            .auth-form-container {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 12px;
            }
            
            .page-content {
                padding: 20px;
            }
            
            .alternative-login {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .auth-tabs {
                flex-direction: column;
            }
            
            .auth-tab {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- الرأس -->
        <header class="header">
            <nav class="navbar">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="logo-text">
                        <h1>منصة تحليل الزوار</h1>
                        <p>منصة تحليل الزوار</p>
                    </div>
                </div>
                
                <div class="nav-links">
                    <a href="#" class="lang-switch">
                    <i class="fas fa-language"></i>
                    <span class="lang-name">English</span>
                        
                    </a>
                </div>
            </nav>
        </header>
        
        <!-- محتوى الصفحة -->
        <div class="page-content">
            <div class="auth-container">
                <!-- لوحة الرسوم التوضيحية -->
                <div class="auth-illustration">
                    <div class="illustration-content">
                        <div class="illustration-icon">
                            <i class="fas fa-line-chart"></i>
                        </div>
                        <h2 class="illustration-title">تحليل. تتبع. تحسين.</h2>
                        <p class="illustration-description">
                            انضم إلى منصتنا المتقدمة لتتبع وتحليل سلوك زوار موقعك بشكل لحظي. احصل على رؤى قيمة تساعدك في تحسين تجربة المستخدم لتحقيق أهدافك.
                        </p>
                    </div>
                </div>
                
                                <!-- لوحة النماذج -->
                <div class="auth-form-container">
                    <!-- ترويسة النماذج -->
                    <h2 class="form-title"><?php echo $is_rtl ? 'مرحباً بك مرة أخرى' : 'Welcome Back'; ?></h2>
                    <p class="form-subtitle"><?php echo $is_rtl ? 'سجل الدخول إلى حسابك للمتابعة' : 'Sign in to your account to continue'; ?></p>
                    
                    <!-- علامات التبويب -->
                    <div class="auth-tabs">
                        <div class="auth-tab active" data-tab="login-password"><?php echo $is_rtl ? 'الدخول بكلمة المرور' : 'Login with Password'; ?></div>
                        <div class="auth-tab" data-tab="login-temp"><?php echo $is_rtl ? 'إستعادة كلمة المرور' : 'Reset Password'; ?></div>
                    </div>
                    
                    <!-- رسائل النظام -->
                    <?php echo $message_html; ?>
                    
                    <!-- نموذج الدخول بكلمة المرور -->
                    <form id="login-password-form" class="auth-form active" method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label class="form-label" for="email"><?php echo $is_rtl ? 'البريد الإلكتروني أو اسم المستخدم' : 'Email or Username'; ?></label>
                            <div class="input-with-icon">
                                <input type="text" id="email" name="username" class="form-input" placeholder="<?php echo $is_rtl ? 'أدخل بريدك الإلكتروني أو اسم المستخدم' : 'Enter your email or username'; ?>" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password"><?php echo $is_rtl ? 'كلمة المرور' : 'Password'; ?></label>
                            <div class="input-with-icon">
                                <input type="password" id="password" name="password" class="form-input" placeholder="<?php echo $is_rtl ? 'أدخل كلمة المرور' : 'Enter your password'; ?>">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="remember" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label"><?php echo $is_rtl ? 'تذكرني' : 'Remember me'; ?></label>
                        </div>
                        
                        <button type="submit" class="btn-submit"><?php echo $is_rtl ? 'تسجيل الدخول' : 'Login'; ?></button>
                        
                        <div class="form-footer">
                            <!--<a href="forgot-password.php?lang=<?php //echo $lang; ?>" class="form-link"><?php // echo $is_rtl ? 'نسيت كلمة المرور؟' : 'Forgot password?'; ?></a>-->
                            <!--<span> | </span>-->
                            <span><?php echo $is_rtl ? 'ليس لديك حساب؟' : 'Don\'t have an account?'; ?> </span>
                            <a href="#" id="show-register" class="form-link"><?php echo $is_rtl ? 'سجل الآن' : 'Register now'; ?></a>
                        </div>
                    </form>
                    
                    <!-- نموذج الدخول برمز مؤقت -->
                    <form id="login-temp-form" class="auth-form" method="POST">
                        <input type="hidden" name="action" value="login_temp">
                        
                        <div class="form-group">
                            <label class="form-label" for="temp-email"><?php echo $is_rtl ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                            <div class="input-with-icon">
                                <input type="email" id="temp-email" name="email" class="form-input" placeholder="<?php echo $is_rtl ? 'أدخل بريدك الإلكتروني' : 'Enter your email address'; ?>" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                <div class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit"><?php echo $is_rtl ? 'إرسال الرمز المؤقت' : 'Send Temporary Code'; ?></button>
                        
                        <div class="form-footer">
                            <!--<a href="#" id="show-login" class="form-link"><?php // echo $is_rtl ? 'الدخول بكلمة المرور' : 'Login with Password'; ?></a>-->
                        </div>
                    </form>
                    
                    <!-- نموذج التسجيل -->
                    <form id="register-form" class="auth-form" method="POST">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label class="form-label" for="fullname"><?php echo $is_rtl ? 'الاسم الكامل' : 'Full Name'; ?></label>
                            <div class="input-with-icon">
                                <input type="text" id="fullname" name="fullname" class="form-input" placeholder="<?php echo $is_rtl ? 'أدخل اسمك الكامل' : 'Enter your full name'; ?>" value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="reg-email"><?php echo $is_rtl ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                            <div class="input-with-icon">
                                <input type="email" id="reg-email" name="email" class="form-input" placeholder="<?php echo $is_rtl ? 'أدخل بريدك الإلكتروني' : 'Enter your email address'; ?>" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                <div class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="username"><?php echo $is_rtl ? 'اسم المستخدم' : 'Username'; ?></label>
                            <div class="input-with-icon">
                                <input type="text" id="username" name="username" class="form-input" placeholder="<?php echo $is_rtl ? 'اختر اسم مستخدم' : 'Choose a username'; ?>" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
                                <div class="input-icon">
                                    <i class="fas fa-at"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="reg-password"><?php echo $is_rtl ? 'كلمة المرور' : 'Password'; ?></label>
                            <div class="input-with-icon">
                                <input type="password" id="reg-password" name="password" class="form-input" placeholder="<?php echo $is_rtl ? 'أنشئ كلمة مرور قوية' : 'Create a strong password'; ?>">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                            
                            <div class="password-strength">
                                <div class="password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <div class="password-strength-text" id="password-strength-text"><?php echo $is_rtl ? 'قوة كلمة المرور: ضعيفة' : 'Password strength: Weak'; ?></div>
                            
                            <ul class="password-requirements">
                                <li id="req-length" class="requirement-not-met">
                                    <i class="fas fa-circle"></i> <?php echo $is_rtl ? '8 أحرف على الأقل' : 'At least 8 characters'; ?>
                                </li>
                                <li id="req-number" class="requirement-not-met">
                                    <i class="fas fa-circle"></i> <?php echo $is_rtl ? 'تحتوي على رقم' : 'Contains a number'; ?>
                                </li>
                                <li id="req-special" class="requirement-not-met">
                                    <i class="fas fa-circle"></i> <?php echo $is_rtl ? 'تحتوي على حرف خاص (@$!%*?&)' : 'Contains a special character (@$!%*?&)'; ?>
                                </li>
                                <li id="req-upper" class="requirement-not-met">
                                    <i class="fas fa-circle"></i> <?php echo $is_rtl ? 'تحتوي على حرف كبير' : 'Contains an uppercase letter'; ?>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm-password"><?php echo $is_rtl ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?></label>
                            <div class="input-with-icon">
                                <input type="password" id="confirm-password" name="confirm_password" class="form-input" placeholder="<?php echo $is_rtl ? 'أعد إدخال كلمة المرور' : 'Re-enter your password'; ?>">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">
                                <?php if ($is_rtl): ?>
                                    أوافق على <a href="terms.php?lang=<?php echo $lang; ?>" class="form-link">شروط الخدمة</a> و <a href="privacy.php?lang=<?php echo $lang; ?>" class="form-link">سياسة الخصوصية</a>
                                <?php else: ?>
                                    I agree to the <a href="terms.php?lang=<?php echo $lang; ?>" class="form-link">Terms of Service</a> and <a href="privacy.php?lang=<?php echo $lang; ?>" class="form-link">Privacy Policy</a>
                                <?php endif; ?>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit"><?php echo $is_rtl ? 'إنشاء حساب' : 'Create Account'; ?></button>
                        
                        <div class="form-footer">
                            <span><?php echo $is_rtl ? 'لديك حساب بالفعل؟' : 'Already have an account?'; ?> </span>
                            <a href="#" id="show-login-from-reg" class="form-link"><?php echo $is_rtl ? 'سجل الدخول' : 'Sign In'; ?></a>
                        </div>
                    </form>
                </div>


            </div>
        </div>
        
        <!-- التذييل -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="#" class="footer-link">الشروط والأحكام</a>
                    <a href="#" class="footer-link">سياسة الخصوصية</a>
                    <a href="#" class="footer-link">تواصل معنا</a>
                    <a href="#" class="footer-link">الدعم الفني</a>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; 2024 منصة تحليل الزوار. جميع الحقوق محفوظة</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // التحكم في علامات التبويب والنماذج
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // إزالة الفعالية من جميع علامات التبويب
                document.querySelectorAll('.auth-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // إضافة الفعالية للعلامة الحالية
                this.classList.add('active');
                
                // إخفاء جميع النماذج
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.classList.remove('active');
                });
                
                // إظهار النموذج المطلوب
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-form').classList.add('active');
            });
        });
        
        // التبديل بين التسجيل والدخول
        document.getElementById('show-register').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById('register-form').classList.add('active');
        });
        
        // document.getElementById('show-login').addEventListener('click', function(e) {
        //     e.preventDefault();
        //     document.querySelectorAll('.auth-form').forEach(form => {
        //         form.classList.remove('active');
        //     });
        //     document.getElementById('login-password-form').classList.add('active');
        //     document.querySelector('.auth-tab[data-tab="login-password"]').classList.add('active');
        //     document.querySelector('.auth-tab[data-tab="login-temp"]').classList.remove('active');
        // });
        
        document.getElementById('show-login-from-reg').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById('register-form').classList.remove('active');
            document.getElementById('login-password-form').classList.add('active');
            document.querySelector('.auth-tab[data-tab="login-password"]').classList.add('active');
            document.querySelector('.auth-tab[data-tab="login-temp"]').classList.remove('active');
        });
        
        // التحقق من قوة كلمة المرور
        const passwordInput = document.getElementById('reg-password');
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        
        const requirements = {
            length: document.getElementById('req-length'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special'),
            upper: document.getElementById('req-upper')
        };
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let messages = [];
            
            // إعادة تعيين المتطلبات
            Object.values(requirements).forEach(req => {
                req.classList.remove('requirement-met');
                req.classList.add('requirement-not-met');
                req.querySelector('i').className = 'fas fa-circle';
            });
            
            // التحقق من الطول
            if (password.length >= 8) {
                strength += 25;
                requirements.length.classList.remove('requirement-not-met');
                requirements.length.classList.add('requirement-met');
                requirements.length.querySelector('i').className = 'fas fa-check-circle';
            }
            
            // التحقق من وجود أرقام
            if (/\d/.test(password)) {
                strength += 25;
                requirements.number.classList.remove('requirement-not-met');
                requirements.number.classList.add('requirement-met');
                requirements.number.querySelector('i').className = 'fas fa-check-circle';
            }
            
            // التحقق من وجود حروف خاصة
            if (/[@$!%*?&]/.test(password)) {
                strength += 25;
                requirements.special.classList.remove('requirement-not-met');
                requirements.special.classList.add('requirement-met');
                requirements.special.querySelector('i').className = 'fas fa-check-circle';
            }
            
            // التحقق من وجود حروف كبيرة
            if (/[A-Z]/.test(password)) {
                strength += 25;
                requirements.upper.classList.remove('requirement-not-met');
                requirements.upper.classList.add('requirement-met');
                requirements.upper.querySelector('i').className = 'fas fa-check-circle';
            }
            
            // تحديث شريط القوة
            strengthBar.style.width = strength + '%';
            
            // تحديث نص القوة
            if (strength === 0) {
                strengthBar.style.backgroundColor = '#e53e3e';
                strengthText.textContent = '<?php echo $is_rtl ? "قوة كلمة المرور: ضعيفة" : "Password strength: Weak"; ?>';
                strengthText.style.color = '#e53e3e';
            } else if (strength < 50) {
                strengthBar.style.backgroundColor = '#dd6b20';
                strengthText.textContent = '<?php echo $is_rtl ? "قوة كلمة المرور: متوسطة" : "Password strength: Medium"; ?>';
                strengthText.style.color = '#dd6b20';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#3182ce';
                strengthText.textContent = '<?php echo $is_rtl ? "قوة كلمة المرور: جيدة" : "Password strength: Good"; ?>';
                strengthText.style.color = '#3182ce';
            } else {
                strengthBar.style.backgroundColor = '#38a169';
                strengthText.textContent = '<?php echo $is_rtl ? "قوة كلمة المرور: قوية جداً" : "Password strength: Very Strong"; ?>';
                strengthText.style.color = '#38a169';
            }
        });
        
        // التحقق من تطابق كلمة المرور
        const confirmPasswordInput = document.getElementById('confirm-password');
        
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword === '') {
                this.style.borderColor = '';
            } else if (password === confirmPassword) {
                this.style.borderColor = '#38a169';
            } else {
                this.style.borderColor = '#e53e3e';
            }
        });
        
        // تبديل اللغة
        document.querySelector('.lang-switch').addEventListener('click', function(e) {
            e.preventDefault();
            const currentLang = document.documentElement.lang;
            const newLang = currentLang === 'ar' ? 'en' : 'ar';
            const newDir = newLang === 'ar' ? 'rtl' : 'ltr';
            
            document.documentElement.lang = newLang;
            document.documentElement.dir = newDir;
            

            // في تطبيق حقيقي، ستقوم هنا بتحميل النصوص المناسبة للغة الجديدة
            // لكن في هذا المثال سنقوم فقط بتغيير بعض النصوص للتوضيح
            if (newLang === 'en') {
                document.querySelector('.lang-name').textContent = 'العربية';
                document.querySelector('.logo-text h1').textContent = 'Visitor Analytics';
                document.querySelector('.logo-text p').textContent = 'Analytics Platform';
                document.querySelector('.illustration-title').textContent = 'Analyze. Track. Improve.';
                document.querySelector('.illustration-description').textContent = 'Join our advanced platform to track and analyze your website visitor behavior. Gain valuable insights to improve user experience and increase conversions.';
                document.querySelector('.form-title').textContent = 'Welcome Back';
                document.querySelector('.form-subtitle').textContent = 'Sign in to your account to continue';
                document.querySelector('.auth-tab[data-tab="login-password"]').textContent = 'Login with Password';
                document.querySelector('.auth-tab[data-tab="login-temp"]').textContent = 'Login with Temporary Code';
                document.querySelector('#login-password-form .message span').textContent = 'Please enter your credentials to access your account';
                document.querySelector('#login-temp-form .message span').textContent = 'We will send a temporary code to your email for login';
                document.querySelector('#register-form .message span').textContent = 'Register now to access all advanced features';
                document.querySelector('.footer-bottom p').textContent = '© 2024 Visitor Analytics. All rights reserved';
            } else {
                document.querySelector('.lang-name').textContent = 'English';
                document.querySelector('.logo-text h1').textContent = 'منصة تحليل الزوار';
                document.querySelector('.logo-text p').textContent = 'منصة تحليل الزوار';
                document.querySelector('.illustration-title').textContent = 'تحليل. تتبع. تحسين.';
                document.querySelector('.illustration-description').textContent = 'انضم إلى منصتنا المتقدمة لتتبع وتحليل سلوك زوار موقعك. احصل على رؤى قيمة تساعدك في تحسين تجربة المستخدم وزيادة التحويلات.';
                document.querySelector('.form-title').textContent = 'مرحباً بك مرة أخرى';
                document.querySelector('.form-subtitle').textContent = 'سجل الدخول إلى حسابك للمتابعة';
                document.querySelector('.auth-tab[data-tab="login-password"]').textContent = 'الدخول بكلمة المرور';
                document.querySelector('.auth-tab[data-tab="login-temp"]').textContent = 'الدخول برمز مؤقت';
                document.querySelector('#login-password-form .message span').textContent = 'يرجى إدخال بياناتك للدخول إلى حسابك';
                document.querySelector('#login-temp-form .message span').textContent = 'سنرسل رمز مؤقت إلى بريدك الإلكتروني للدخول';
                document.querySelector('#register-form .message span').textContent = 'سجل الآن للوصول إلى جميع الميزات المتقدمة';
                document.querySelector('.footer-bottom p').textContent = '© 2024 منصة تحليل الزوار. جميع الحقوق محفوظة';
            }
        });
    </script>
</body>
</html>