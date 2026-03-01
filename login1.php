<?php
// config/database.php - الملف الموجود مسبقاً
require_once 'config/database.php';
require_once 'classes/UserManager.php';

// إنشاء كائن قاعدة البيانات
$database = new Database();
$userManager = new UserManager($database);

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $response = [];
        
        switch ($_POST['action']) {
            case 'register':
                $response = $userManager->register($_POST);
                break;
                
            case 'login':
                $response = $userManager->login($_POST['username'], $_POST['password']);
                break;
                
            case 'send_temp_login':
                $email = $_POST['email'];
                $user = $userManager->getUserByEmail($email);
                
                if ($user) {
                    if (!$user['is_active']) {
                        $response = ['success' => false, 'error' => 'الحساب غير مفعل. يرجى تفعيل حسابك أولاً'];
                    } else {
                        $temp_token = generateToken(20);
                        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        
                        // حفظ الرمز المؤقت في قاعدة البيانات
                        $database->query("UPDATE users SET temp_login_token = ?, temp_token_expires = ? WHERE id = ?", 
                            [$temp_token, $expires_at, $user['id']]);
                        
                        // إرسال البريد الإلكتروني
                        $subject = "رمز الدخول المؤقت - " . SITE_NAME;
                        $message = "
                        <html>
                        <body dir='rtl' style='font-family: Tajawal, Arial, sans-serif;'>
                            <h2>رمز الدخول المؤقت</h2>
                            <p>تم طلب دخول إلى حسابك باستخدام رمز مؤقت. رمز الدخول هو:</p>
                            <h1 style='font-size: 32px; letter-spacing: 5px;'>$temp_token</h1>
                            <p>ينتهي صلاحية هذا الرمز بعد 15 دقيقة.</p>
                            <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.</p>
                            <p>مع تحيات فريق " . SITE_NAME . "</p>
                        </body>
                        </html>";
                        
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= 'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>' . "\r\n";
                        
                        if (mail($email, $subject, $message, $headers)) {
                            $response = ['success' => true, 'message' => 'تم إرسال رمز الدخول المؤقت إلى بريدك الإلكتروني'];
                        } else {
                            $response = ['success' => false, 'error' => 'حدث خطأ أثناء إرسال البريد'];
                        }
                    }
                } else {
                    $response = ['success' => false, 'error' => 'البريد الإلكتروني غير مسجل'];
                }
                break;
                
            case 'temp_login':
                $token = $_POST['token'];
                $user = $database->fetchOne("SELECT * FROM users WHERE temp_login_token = ? AND temp_token_expires > NOW()", [$token]);
                
                if ($user) {
                    // تسجيل الدخول
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // مسح الرمز المؤقت
                    $database->query("UPDATE users SET temp_login_token = NULL, temp_token_expires = NULL WHERE id = ?", [$user['id']]);
                    
                    $response = ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح'];
                } else {
                    $response = ['success' => false, 'error' => 'رمز الدخول غير صحيح أو منتهي الصلاحية'];
                }
                break;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// التحقق من جلسة المستخدم
if ($userManager->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - منصة تحليل الزوار</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #1abc9c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --danger: #e74c3c;
            --success: #2ecc71;
            --warning: #f39c12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a4c 0%, #2c3e50 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,100 L0,100 Z" fill="rgba(26, 188, 156, 0.05)"/></svg>');
            background-size: cover;
            opacity: 0.3;
            z-index: -1;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .app-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .app-logo h1 {
            color: white;
            font-size: 2.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .app-logo h1 i {
            color: var(--accent);
        }
        
        .app-logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .auth-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .auth-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 35px;
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .auth-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .auth-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent), var(--secondary));
        }
        
        .tab-buttons {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: none;
            border: none;
            font-size: 1.1rem;
            font-weight: 500;
            color: #777;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn.active {
            color: var(--primary);
            font-weight: 700;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .auth-header h2 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .auth-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.2);
            outline: none;
            background: white;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .input-icon .form-control {
            padding-left: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--accent), var(--secondary));
            color: white;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #16a085, #2980b9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
            margin-top: 15px;
        }
        
        .btn-outline:hover {
            background: var(--accent);
            color: white;
        }
        
        .divider {
            text-align: center;
            position: relative;
            margin: 25px 0;
            color: #777;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #eee;
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .temp-login-form {
            display: none;
        }
        
        .temp-code-inputs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .temp-code-inputs input {
            flex: 1;
            height: 60px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .temp-code-inputs input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.2);
            outline: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: none;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.15);
            border: 1px solid var(--success);
            color: #27ae60;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.15);
            border: 1px solid var(--danger);
            color: #c0392b;
        }
        
        .alert ul {
            margin: 10px 0 0 20px;
        }
        
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 40px;
            justify-content: center;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            width: 220px;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: rgba(26, 188, 156, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
            color: white;
        }
        
        .feature-card h3 {
            color: white;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            
            .app-logo h1 {
                font-size: 2.2rem;
            }
            
            .auth-box {
                padding: 25px;
            }
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="app-logo">
            <h1><i class="fas fa-chart-network"></i> <?= SITE_NAME ?></h1>
            <p>منصة متكاملة لتحليل زوار مواقعك، تتبع السلوكيات، واكتشاف الأخطاء التقنية لتحسين تجربة المستخدم</p>
        </div>
        
        <div class="auth-container">
            <!-- تسجيل الدخول -->
            <div class="auth-box">
                <div class="auth-header">
                    <h2>تسجيل الدخول</h2>
                    <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
                </div>
                
                <div class="alert" id="login-alert"></div>
                
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="password-login">كلمة المرور</button>
                    <button class="tab-btn" data-tab="temp-login">رمز مؤقت</button>
                </div>
                
                <!-- تسجيل الدخول بكلمة المرور -->
                <form id="password-login-form" class="login-form">
                    <div class="form-group">
                        <label for="login-username">اسم المستخدم أو البريد الإلكتروني</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="login-username" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">كلمة المرور</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="login-password" class="form-control" required>
                            <button type="button" class="password-toggle" id="login-password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group text-right">
                        <a href="forgot-password.php" style="color: var(--secondary); text-decoration: none;">نسيت كلمة المرور؟</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="login-btn">
                        <span class="spinner" id="login-spinner"></span>
                        تسجيل الدخول
                    </button>
                </form>
                
                <!-- تسجيل الدخول بالرمز المؤقت -->
                <form id="temp-login-form" class="login-form temp-login-form">
                    <div class="form-group">
                        <label for="temp-email">البريد الإلكتروني</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="temp-email" class="form-control" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-outline" id="send-temp-btn">
                        <span class="spinner" id="send-temp-spinner"></span>
                        إرسال رمز الدخول المؤقت
                    </button>
                    
                    <div id="code-section" style="display: none;">
                        <div class="divider">أدخل الرمز المكون من 6 أرقام</div>
                        
                        <div class="temp-code-inputs">
                            <input type="text" maxlength="1" class="temp-code" data-index="1">
                            <input type="text" maxlength="1" class="temp-code" data-index="2">
                            <input type="text" maxlength="1" class="temp-code" data-index="3">
                            <input type="text" maxlength="1" class="temp-code" data-index="4">
                            <input type="text" maxlength="1" class="temp-code" data-index="5">
                            <input type="text" maxlength="1" class="temp-code" data-index="6">
                        </div>
                        
                        <button type="button" class="btn btn-primary" id="verify-temp-btn">
                            <span class="spinner" id="verify-temp-spinner"></span>
                            تأكيد الرمز
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- إنشاء حساب جديد -->
            <div class="auth-box">
                <div class="auth-header">
                    <h2>إنشاء حساب جديد</h2>
                    <p>انضم إلينا لبدء رحلة تحليل بياناتك</p>
                </div>
                
                <div class="alert" id="register-alert"></div>
                
                <form id="register-form">
                    <div class="form-group">
                        <label for="full-name">الاسم الكامل</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="full-name" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">اسم المستخدم</label>
                        <div class="input-icon">
                            <i class="fas fa-user-tag"></i>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">كلمة المرور</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="password-toggle" id="register-password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">يجب أن تتكون كلمة المرور من 6 أحرف على الأقل</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">تأكيد كلمة المرور</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm-password" name="confirm_password" class="form-control" required>
                            <button type="button" class="password-toggle" id="confirm-password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="agree-terms" class="form-check-input" required>
                            <label for="agree-terms" class="form-check-label">أوافق على <a href="#" style="color: var(--accent);">الشروط والأحكام</a> و <a href="#" style="color: var(--accent);">سياسة الخصوصية</a></label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="register-btn">
                        <span class="spinner" id="register-spinner"></span>
                        إنشاء حساب جديد
                    </button>
                </form>
                
                <div class="divider">أو</div>
                
                <p class="text-center">لديك حساب بالفعل؟ <a href="#" id="switch-to-login" style="color: var(--accent); font-weight: 500;">سجل دخول</a></p>
            </div>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>تحليلات متقدمة</h3>
                <p>تتبع الزوار وتحليل سلوكهم في موقعك</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3>خرائط حرارية</h3>
                <p>اكتشف المناطق الأكثر تفاعلاً في صفحاتك</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bug"></i>
                </div>
                <h3>اكتشاف الأخطاء</h3>
                <p>رصد وتتبع الأخطاء التقنية في مواقعك</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>حماية وأمان</h3>
                <p>بياناتك محمية بأفضل معايير الأمان</p>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2023 <?= SITE_NAME ?>. جميع الحقوق محفوظة</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل التبويبات في تسجيل الدخول
            const tabButtons = document.querySelectorAll('.tab-btn');
            const loginForms = document.querySelectorAll('.login-form');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    
                    // إزالة النشاط من جميع الأزرار
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    // إضافة النشاط للزر الحالي
                    this.classList.add('active');
                    
                    // إخفاء جميع النماذج
                    loginForms.forEach(form => form.style.display = 'none');
                    
                    // إظهار النموذج المحدد
                    if (tabId === 'password-login') {
                        document.getElementById('password-login-form').style.display = 'block';
                    } else {
                        document.getElementById('temp-login-form').style.display = 'block';
                    }
                });
            });
            
            // تبديل عرض كلمة المرور
            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // إدخال الرمز المؤقت
            const tempCodeInputs = document.querySelectorAll('.temp-code');
            tempCodeInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        const nextIndex = parseInt(this.dataset.index) + 1;
                        const nextInput = document.querySelector(`.temp-code[data-index="${nextIndex}"]`);
                        
                        if (nextInput) {
                            nextInput.focus();
                        }
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '') {
                        const prevIndex = parseInt(this.dataset.index) - 1;
                        const prevInput = document.querySelector(`.temp-code[data-index="${prevIndex}"]`);
                        
                        if (prevInput) {
                            prevInput.focus();
                        }
                    }
                });
            });
            
            // تسجيل الدخول بكلمة المرور
            const loginForm = document.getElementById('password-login-form');
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const username = document.getElementById('login-username').value;
                const password = document.getElementById('login-password').value;
                const alertBox = document.getElementById('login-alert');
                const spinner = document.getElementById('login-spinner');
                const button = document.getElementById('login-btn');
                
                // إظهار spinner وتعطيل الزر
                spinner.style.display = 'inline-block';
                button.disabled = true;
                
                // إرسال طلب AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || 'تم تسجيل الدخول بنجاح'}`;
                        alertBox.style.display = 'block';
                        
                        // توجيه المستخدم بعد فترة قصيرة
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1500);
                    } else {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.error || 'حدث خطأ أثناء تسجيل الدخول'}`;
                        alertBox.style.display = 'block';
                    }
                })
                .catch(error => {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> حدث خطأ في الاتصال بالخادم`;
                    alertBox.style.display = 'block';
                })
                .finally(() => {
                    spinner.style.display = 'none';
                    button.disabled = false;
                });
            });
            
            // إرسال رمز الدخول المؤقت
            const tempLoginForm = document.getElementById('temp-login-form');
            tempLoginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = document.getElementById('temp-email').value;
                const alertBox = document.getElementById('login-alert');
                const spinner = document.getElementById('send-temp-spinner');
                const button = document.getElementById('send-temp-btn');
                const codeSection = document.getElementById('code-section');
                
                // إظهار spinner وتعطيل الزر
                spinner.style.display = 'inline-block';
                button.disabled = true;
                
                // إرسال طلب AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_temp_login&email=${encodeURIComponent(email)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || 'تم إرسال الرمز بنجاح'}`;
                        alertBox.style.display = 'block';
                        
                        // إظهار قسم إدخال الرمز
                        codeSection.style.display = 'block';
                    } else {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.error || 'حدث خطأ أثناء إرسال الرمز'}`;
                        alertBox.style.display = 'block';
                    }
                })
                .catch(error => {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> حدث خطأ في الاتصال بالخادم`;
                    alertBox.style.display = 'block';
                })
                .finally(() => {
                    spinner.style.display = 'none';
                    button.disabled = false;
                });
            });
            
            // تأكيد الرمز المؤقت
            const verifyTempBtn = document.getElementById('verify-temp-btn');
            verifyTempBtn.addEventListener('click', function() {
                const tempCodes = document.querySelectorAll('.temp-code');
                let token = '';
                
                tempCodes.forEach(input => {
                    token += input.value;
                });
                
                if (token.length !== 6) {
                    const alertBox = document.getElementById('login-alert');
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> يرجى إدخال الرمز المكون من 6 أرقام`;
                    alertBox.style.display = 'block';
                    return;
                }
                
                const spinner = document.getElementById('verify-temp-spinner');
                const button = this;
                const alertBox = document.getElementById('login-alert');
                
                // إظهار spinner وتعطيل الزر
                spinner.style.display = 'inline-block';
                button.disabled = true;
                
                // إرسال طلب AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=temp_login&token=${encodeURIComponent(token)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || 'تم تسجيل الدخول بنجاح'}`;
                        alertBox.style.display = 'block';
                        
                        // توجيه المستخدم بعد فترة قصيرة
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1500);
                    } else {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.error || 'حدث خطأ أثناء تسجيل الدخول'}`;
                        alertBox.style.display = 'block';
                    }
                })
                .catch(error => {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> حدث خطأ في الاتصال بالخادم`;
                    alertBox.style.display = 'block';
                })
                .finally(() => {
                    spinner.style.display = 'none';
                    button.disabled = false;
                });
            });
            
            // تسجيل حساب جديد
            const registerForm = document.getElementById('register-form');
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const alertBox = document.getElementById('register-alert');
                const spinner = document.getElementById('register-spinner');
                const button = document.getElementById('register-btn');
                
                // إظهار spinner وتعطيل الزر
                spinner.style.display = 'inline-block';
                button.disabled = true;
                
                // تحويل FormData إلى كائن
                const data = {};
                formData.forEach((value, key) => data[key] = value);
                data['action'] = 'register';
                
                // إرسال طلب AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || 'تم إنشاء الحساب بنجاح'}`;
                        alertBox.style.display = 'block';
                        
                        // مسح النموذج
                        registerForm.reset();
                        
                        // توجيه المستخدم بعد فترة قصيرة
                        setTimeout(() => {
                            window.location.href = 'login.php?registered=true';
                        }, 3000);
                    } else {
                        let errorHtml = `<i class="fas fa-exclamation-circle"></i> حدثت بعض الأخطاء:<ul>`;
                        data.errors.forEach(error => {
                            errorHtml += `<li>${error}</li>`;
                        });
                        errorHtml += '</ul>';
                        
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = errorHtml;
                        alertBox.style.display = 'block';
                    }
                })
                .catch(error => {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> حدث خطأ في الاتصال بالخادم`;
                    alertBox.style.display = 'block';
                })
                .finally(() => {
                    spinner.style.display = 'none';
                    button.disabled = false;
                });
            });
            
            // التحقق من وجود رسالة تسجيل ناجحة في URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('registered') === 'true') {
                const alertBox = document.getElementById('login-alert');
                alertBox.className = 'alert alert-success';
                alertBox.innerHTML = `<i class="fas fa-check-circle"></i> تم إنشاء حسابك بنجاح! يرجى تفعيل حسابك عبر البريد الإلكتروني`;
                alertBox.style.display = 'block';
            }
        });
    </script>
</body>
</html>