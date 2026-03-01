<?php
// index.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';

$userManager = new UserManager($db);

// إعادة توجيه المستخدمين المسجلين
if ($userManager->isLoggedIn()) {
    if ($userManager->isAdmin()) {
        redirect('admin/');
    } else {
        redirect('dashboard.php');
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
    <title><?php echo $is_rtl ? SITE_NAME . ' - منصة تحليل سلوك الزوار' : SITE_NAME_EN . ' - Visitor Analytics Platform'; ?></title>
    <meta name="description" content="<?php echo $is_rtl ? 'منصة متقدمة لتتبع وتحليل سلوك الزوار في المواقع الإلكترونية' : 'Advanced platform for tracking and analyzing visitor behavior on websites'; ?>">
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
            --primary-light: #2d3748;
            --accent-color: #3182ce;
            --accent-hover: #2b6cb0;
            --success-color: #38a169;
            --error-color: #e53e3e;
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
            font-family: <?php echo $is_rtl ? "'Tajawal'" : "'Roboto'"; ?>, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--background);
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
        
        /* القسم البطل */
        .hero {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 100px 32px 120px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(180deg, transparent, var(--background));
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 24px;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            line-height: 1.6;
        }
        
        .hero-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,255,255,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* المميزات */
        .features {
            padding: 100px 32px;
            background: var(--surface);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }
        
        .section-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .section-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            background: var(--background);
            padding: 40px;
            border-radius: var(--border-radius-lg);
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-color);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 24px;
        }
        
        .feature-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .feature-description {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* الإحصائيات */
        .stats {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 80px 32px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .stat-item {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 8px;
            display: block;
        }
        
        .stat-label {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* كيفية العمل */
        .how-it-works {
            padding: 100px 32px;
            background: var(--background);
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .step-card {
            background: var(--surface);
            padding: 40px;
            border-radius: var(--border-radius-lg);
            text-align: center;
            position: relative;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .step-number {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: white;
        }
        
        .step-title {
            font-size: 20px;
            font-weight: 700;
            margin: 20px 0 16px;
            color: var(--text-primary);
        }
        
        .step-description {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* الدعوة للعمل */
        .cta {
            background: var(--surface);
            padding: 80px 32px;
            text-align: center;
        }
        
        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .cta p {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        /* التذييل */
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 60px 32px 20px;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-logo {
            margin-bottom: 24px;
        }
        
        .footer-logo h3 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .footer-logo p {
            opacity: 0.8;
            margin-bottom: 32px;
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
        
        /* التجاوب */
        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
                flex-direction: column;
                gap: 16px;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 12px;
            }
            
            .hero {
                padding: 60px 20px 80px;
            }
            
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .features {
                padding: 60px 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .stats {
                padding: 60px 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
            
            .how-it-works {
                padding: 60px 20px;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .cta {
                padding: 60px 20px;
            }
        }
        
        /* تأثيرات بصرية */
        .animate-fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .animate-fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <!-- الرأس -->
    <header class="header">
        <nav class="navbar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="logo-text">
                    <h1><?php echo $is_rtl ? SITE_NAME : SITE_NAME_EN; ?></h1>
                    <p><?php echo $is_rtl ? 'منصة تحليل الزوار' : 'Analytics Platform'; ?></p>
                </div>
            </div>
            
            <div class="nav-links">
                <a href="#features" class="nav-link"><?php echo $is_rtl ? 'المميزات' : 'Features'; ?></a>
                <a href="#how-it-works" class="nav-link"><?php echo $is_rtl ? 'كيف يعمل' : 'How it Works'; ?></a>
                <a href="?lang=<?php echo $is_rtl ? 'en' : 'ar'; ?>" class="lang-switch">
                    <?php echo $is_rtl ? 'English' : 'العربية'; ?>
                </a>
                <a href="login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $is_rtl ? 'تسجيل الدخول' : 'Login'; ?>
                </a>
            </div>
        </nav>
    </header>
    
    <!-- القسم البطل -->
    <section class="hero">
        <div class="hero-content animate-fade-in">
            <h1><?php echo $is_rtl ? 'تتبع وتحليل زوار موقعك بذكاء' : 'Smart Website Visitor Tracking & Analytics'; ?></h1>
            <p><?php echo $is_rtl ? 'منصة متقدمة لتتبع سلوك الزوار وتحليل أداء مواقعك الإلكترونية بتقنيات حديثة وواجهة سهلة الاستخدام' : 'Advanced platform for tracking visitor behavior and analyzing your websites performance with modern technology and user-friendly interface'; ?></p>
            <div class="hero-actions">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    <?php echo $is_rtl ? 'ابدأ مجاناً الآن' : 'Get Started Free'; ?>
                </a>
                <a href="#features" class="btn btn-secondary">
                    <i class="fas fa-play"></i>
                    <?php echo $is_rtl ? 'شاهد كيف يعمل' : 'See How it Works'; ?>
                </a>
            </div>
        </div>
    </section>
    
    <!-- المميزات -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header animate-fade-in">
                <h2 class="section-title"><?php echo $is_rtl ? 'مميزات قوية لتحليل شامل' : 'Powerful Features for Comprehensive Analysis'; ?></h2>
                <p class="section-subtitle"><?php echo $is_rtl ? 'اكتشف كيف تساعدك منصتنا في فهم زوار موقعك وتحسين أدائه' : 'Discover how our platform helps you understand your visitors and improve performance'; ?></p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card animate-fade-in floating">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'تحليلات مفصلة' : 'Detailed Analytics'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'احصل على تحليلات شاملة لسلوك الزوار، المدن، الأجهزة، والصفحات الأكثر زيارة' : 'Get comprehensive analytics of visitor behavior, cities, devices, and most visited pages'; ?></p>
                </div>
                
                <div class="feature-card animate-fade-in floating" style="animation-delay: 0.2s;">
                    <div class="feature-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'تتبع النقرات' : 'Click Tracking'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'راقب كل نقرة على موقعك واعرف العناصر التي تجذب انتباه الزوار أكثر' : 'Monitor every click on your website and know which elements attract visitors attention most'; ?></p>
                </div>
                
                <div class="feature-card animate-fade-in floating" style="animation-delay: 0.4s;">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'الخريطة الحرارية' : 'Heatmaps'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'اعرض الخريطة الحرارية لموقعك واكتشف المناطق الأكثر تفاعلاً' : 'Display your websites heatmap and discover the most interactive areas'; ?></p>
                </div>
                
                <div class="feature-card animate-fade-in floating" style="animation-delay: 0.6s;">
                    <div class="feature-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'إعادة تشغيل الجلسات' : 'Session Replay'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'شاهد كيف يتنقل الزوار في موقعك وتتبع رحلتهم كاملة' : 'Watch how visitors navigate your site and track their complete journey'; ?></p>
                </div>
                
                <div class="feature-card animate-fade-in floating" style="animation-delay: 0.8s;">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'التتبع الجغرافي' : 'Geographic Tracking'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'اعرف من أين يأتي زوارك حول العالم وحلل أداءك في كل منطقة' : 'Know where your visitors come from around the world and analyze your performance in each region'; ?></p>
                </div>
                
                <div class="feature-card animate-fade-in floating" style="animation-delay: 1s;">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title"><?php echo $is_rtl ? 'تحليل الأجهزة' : 'Device Analysis'; ?></h3>
                    <p class="feature-description"><?php echo $is_rtl ? 'تحليل شامل لأنواع الأجهزة والمتصفحات التي يستخدمها زوارك' : 'Comprehensive analysis of device types and browsers used by your visitors'; ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- الإحصائيات -->
    <section class="stats">
        <div class="container">
            <div class="animate-fade-in">
                <h2 class="section-title"><?php echo $is_rtl ? 'منصة موثوقة ومجربة' : 'Trusted & Proven Platform'; ?></h2>
                <p class="section-subtitle"><?php echo $is_rtl ? 'أرقام تتحدث عن جودة خدماتنا' : 'Numbers that speak about our service quality'; ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item animate-fade-in" style="animation-delay: 0.2s;">
                    <span class="stat-number">99.9%</span>
                    <span class="stat-label"><?php echo $is_rtl ? 'وقت تشغيل الخدمة' : 'Service Uptime'; ?></span>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.4s;">
                    <span class="stat-number">1M+</span>
                    <span class="stat-label"><?php echo $is_rtl ? 'زائر يتم تتبعه' : 'Visitors Tracked'; ?></span>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.6s;">
                    <span class="stat-number">10K+</span>
                    <span class="stat-label"><?php echo $is_rtl ? 'موقع مسجل' : 'Registered Websites'; ?></span>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.8s;">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label"><?php echo $is_rtl ? 'دعم فني متواصل' : 'Technical Support'; ?></span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- كيفية العمل -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-header animate-fade-in">
                <h2 class="section-title"><?php echo $is_rtl ? 'كيف يعمل النظام؟' : 'How Does it Work?'; ?></h2>
                <p class="section-subtitle"><?php echo $is_rtl ? 'ابدأ تتبع زوار موقعك في 3 خطوات بسيطة' : 'Start tracking your website visitors in 3 simple steps'; ?></p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card animate-fade-in">
                    <div class="step-number">1</div>
                    <h3 class="step-title"><?php echo $is_rtl ? 'أنشئ حسابك' : 'Create Account'; ?></h3>
                    <p class="step-description"><?php echo $is_rtl ? 'سجل حساباً مجانياً واختر خطة التتبع المناسبة لاحتياجاتك' : 'Register a free account and choose the tracking plan that suits your needs'; ?></p>
                </div>
                
                <div class="step-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="step-number">2</div>
                    <h3 class="step-title"><?php echo $is_rtl ? 'أضف موقعك' : 'Add Website'; ?></h3>
                    <p class="step-description"><?php echo $is_rtl ? 'أضف موقعك الإلكتروني واحصل على كود JavaScript المخصص' : 'Add your website and get your custom JavaScript tracking code'; ?></p>
                </div>
                
                <div class="step-card animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="step-number">3</div>
                    <h3 class="step-title"><?php echo $is_rtl ? 'ابدأ التتبع' : 'Start Tracking'; ?></h3>
                    <p class="step-description"><?php echo $is_rtl ? 'ضع الكود في موقعك وابدأ في مراقبة وتحليل سلوك زوارك فوراً' : 'Place the code on your site and start monitoring and analyzing visitor behavior immediately'; ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- الدعوة للعمل -->
    <section class="cta">
        <div class="container">
            <div class="cta-content animate-fade-in">
                <h2><?php echo $is_rtl ? 'جاهز لفهم زوار موقعك بشكل أعمق؟' : 'Ready to Understand Your Visitors Better?'; ?></h2>
                <p><?php echo $is_rtl ? 'ابدأ رحلتك في تحليل البيانات واتخذ قرارات أكثر ذكاءً لتحسين موقعك' : 'Start your data analytics journey and make smarter decisions to improve your website'; ?></p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    <?php echo $is_rtl ? 'ابدأ الآن مجاناً' : 'Start Free Now'; ?>
                </a>
            </div>
        </div>
    </section>
    
    <!-- التذييل -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo animate-fade-in">
                <h3><?php echo $is_rtl ? SITE_NAME : SITE_NAME_EN; ?></h3>
                <p><?php echo $is_rtl ? 'منصة متقدمة لتحليل سلوك الزوار' : 'Advanced visitor behavior analytics platform'; ?></p>
            </div>
            
            <div class="footer-links animate-fade-in">
                <a href="#features" class="footer-link"><?php echo $is_rtl ? 'المميزات' : 'Features'; ?></a>
                <a href="#how-it-works" class="footer-link"><?php echo $is_rtl ? 'كيف يعمل' : 'How it Works'; ?></a>
                <a href="login.php" class="footer-link"><?php echo $is_rtl ? 'تسجيل الدخول' : 'Login'; ?></a>
                <a href="#" class="footer-link"><?php echo $is_rtl ? 'الدعم الفني' : 'Support'; ?></a>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 <?php echo $is_rtl ? SITE_NAME . '. جميع الحقوق محفوظة' : SITE_NAME_EN . '. All rights reserved'; ?>.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // تأثيرات الرسوم المتحركة عند التمرير
        function observeElements() {
            const elements = document.querySelectorAll('.animate-fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            elements.forEach(element => {
                observer.observe(element);
            });
        }
        
        // تشغيل الرسوم المتحركة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            observeElements();
            
            // تأثير الكتابة للعنوان الرئيسي
            const heroTitle = document.querySelector('.hero h1');
            if (heroTitle) {
                const text = heroTitle.textContent;
                heroTitle.textContent = '';
                heroTitle.style.borderRight = '2px solid white';
                
                let i = 0;
                const typeWriter = setInterval(() => {
                    heroTitle.textContent += text.charAt(i);
                    i++;
                    if (i >= text.length) {
                        clearInterval(typeWriter);
                        setTimeout(() => {
                            heroTitle.style.borderRight = 'none';
                        }, 500);
                    }
                }, 50);
            }
            
            // تأثير العد للإحصائيات
            const statNumbers = document.querySelectorAll('.stat-number');
            const observerStats = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateNumber(entry.target);
                    }
                });
            });
            
            statNumbers.forEach(stat => {
                observerStats.observe(stat);
            });
        });
        
        // دالة تحريك الأرقام
        function animateNumber(element) {
            const text = element.textContent;
            const hasPercent = text.includes('%');
            const hasPlus = text.includes('+');
            const hasSlash = text.includes('/');
            
            let finalNumber;
            if (hasPercent) {
                finalNumber = parseFloat(text.replace('%', ''));
            } else if (hasPlus) {
                finalNumber = parseFloat(text.replace(/[+KM]/g, '')) * (text.includes('K') ? 1000 : text.includes('M') ? 1000000 : 1);
            } else if (hasSlash) {
                return; // لا نحرك الأرقام التي تحتوي على "/"
            } else {
                finalNumber = parseFloat(text);
            }
            
            if (isNaN(finalNumber)) return;
            
            let currentNumber = 0;
            const increment = finalNumber / 50;
            const timer = setInterval(() => {
                currentNumber += increment;
                if (currentNumber >= finalNumber) {
                    currentNumber = finalNumber;
                    clearInterval(timer);
                }
                
                let displayNumber = Math.floor(currentNumber);
                
                if (hasPercent) {
                    element.textContent = displayNumber + '%';
                } else if (hasPlus) {
                    if (finalNumber >= 1000000) {
                        element.textContent = (displayNumber / 1000000).toFixed(1) + 'M+';
                    } else if (finalNumber >= 1000) {
                        element.textContent = (displayNumber / 1000).toFixed(0) + 'K+';
                    } else {
                        element.textContent = displayNumber + '+';
                    }
                } else {
                    element.textContent = displayNumber;
                }
            }, 50);
        }
        
        // التمرير السلس للروابط
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // تأثير التمرير للتنقل
        let lastScrollY = window.scrollY;
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(26, 54, 93, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
                navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = 'transparent';
                navbar.style.backdropFilter = 'none';
                navbar.style.boxShadow = 'none';
            }
            lastScrollY = window.scrollY;
        });
        
        // تحسين الأداء للجوال
        if ('ontouchstart' in window) {
            document.addEventListener('touchstart', function() {}, false);
        }
        
        // تأثيرات إضافية للتفاعل
        const featureCards = document.querySelectorAll('.feature-card');
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-12px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-8px) scale(1)';
            });
        });
        
        // تأثير المرور على الأزرار
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html> 