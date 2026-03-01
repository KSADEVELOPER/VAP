# منصة تتبع سلوك الزوار وتحليل أداء المواقع
## Visitor Analytics Platform

منصة متقدمة لتتبع وتحليل سلوك الزوار في المواقع الإلكترونية مع واجهة عربية كاملة ودعم ثنائي اللغة.

Advanced platform for tracking and analyzing visitor behavior on websites with full Arabic interface and bilingual support.

---

## 🚀 المميزات الرئيسية | Main Features

### 📊 التحليلات والإحصائيات | Analytics & Statistics
- تتبع الزوار الجدد مقابل العائدين | New vs returning visitors tracking
- تحليل مشاهدات الصفحات | Page views analysis
- إحصائيات الوقت المقضي | Time spent statistics
- معدل الارتداد | Bounce rate
- تتبع التحويلات | Conversion tracking

### 🖱️ تتبع التفاعل | Interaction Tracking
- تتبع النقرات التفصيلي | Detailed click tracking
- الخريطة الحرارية للصفحات | Page heatmaps
- تتبع عمق التمرير | Scroll depth tracking
- الأحداث المخصصة | Custom events
- إعادة تشغيل الجلسات | Session replay

### 🌍 التحليل الجغرافي | Geographic Analysis
- تتبع البلدان والمدن | Countries and cities tracking
- تحليل المناطق الزمنية | Timezone analysis
- إحصائيات الموقع الجغرافي | Geographic statistics

### 📱 تحليل الأجهزة | Device Analysis
- أنواع الأجهزة (جوال، جهاز لوحي، سطح المكتب) | Device types (mobile, tablet, desktop)
- المتصفحات المستخدمة | Browser usage
- دقة الشاشة | Screen resolution
- نظام التشغيل | Operating system

### 🔧 إدارة المنصات | Platform Management
- دعم منصات التجارة الإلكترونية | E-commerce platform support
- قوالب مخصصة للمنصات | Custom platform templates
- معرفات العناصر القابلة للتخصيص | Customizable element selectors

---

## 🛠️ المتطلبات التقنية | Technical Requirements

### Server Requirements
- **PHP**: 7.4 أو أحدث | 7.4 or higher
- **MySQL**: 5.7 أو أحدث | 5.7 or higher
- **Apache/Nginx**: مع دعم mod_rewrite | with mod_rewrite support
- **SSL Certificate**: مُوصى به | Recommended

### PHP Extensions
```
- PDO
- mysqli
- json
- mbstring
- openssl
- curl
- gd (اختياري للرسوم | optional for charts)
```

---

## 📦 التثبيت | Installation

### 1. تحميل الملفات | Download Files
```bash
git clone https://github.com/your-repo/analytics-platform.git
cd analytics-platform
```

### 2. إعداد قاعدة البيانات | Database Setup
```sql
-- إنشاء قاعدة البيانات | Create database
CREATE DATABASE analytics_platform;

-- استيراد الهيكل | Import structure
mysql -u username -p analytics_platform < database/analytics_platform.sql
```

### 3. تكوين الاتصال | Configuration
قم بتحديث ملف `config/database.php`:
```php
private $host = 'localhost';
private $dbname = 'analytics_platform';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. إعداد البريد الإلكتروني | Email Configuration
قم بتحديث إعدادات SMTP في `config/database.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

### 5. تحديد الصلاحيات | Set Permissions
```bash
chmod 755 /path/to/analytics-platform
chmod 644 config/database.php
```

---

## 🎯 الاستخدام | Usage

### للمستخدمين | For Users

#### 1. التسجيل وتفعيل الحساب | Registration & Activation
1. انتقل إلى `/login.php`
2. اختر "إنشاء حساب" | Choose "Sign Up"
3. املأ البيانات المطلوبة | Fill required information
4. فعّل حسابك عبر البريد الإلكتروني | Activate via email

#### 2. إضافة موقع جديد | Adding New Website
1. سجل دخولك إلى لوحة التحكم | Login to dashboard
2. اضغط "إضافة موقع جديد" | Click "Add New Website"
3. املأ بيانات الموقع واختر المنصة | Fill website data and select platform
4. احصل على كود التتبع | Get tracking code

#### 3. تثبيت كود التتبع | Installing Tracking Code
```html
<!-- ضع هذا الكود قبل إغلاق </head> -->
<!-- Place this code before closing </head> tag -->
<script>
(function() {
    'use strict';

    // Replace with the tracking code generated for your website from Dashboard
    var TRACKING_CODE = 'AT_REPLACE_WITH_YOUR_CODE';
    // Replace with your system base URL
    var API_URL = 'https://your-domain.com/api/track.php';

    var sessionId = 'session_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    var startTime = Date.now();
    var lastActivity = startTime;
    var pageViews = 0;
    var isTracking = true;

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

        fetch(API_URL + '?action=' + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
            mode: 'cors',
            cache: 'no-cache'
        }).catch(function(error) {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Analytics tracking error:', error.message);
            }
        });
    }

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

    function initSession() {
        sendData('session', {
            is_new_session: true,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        });
    }

    function trackPageView() {
        pageViews++;
        sendData('pageview', { page_views: pageViews, scroll_depth: 0 });
    }

    function trackClick(event) {
        var target = event.target;
        var rect = target.getBoundingClientRect();
        sendData('click', {
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
        });
    }

    function trackScrollDepth() {
        var scrollPercent = Math.round((window.scrollY / Math.max(document.body.scrollHeight - window.innerHeight, 1)) * 100);
        sendData('scroll', { scroll_depth: Math.min(Math.max(scrollPercent, 0), 100) });
    }

    function trackTimeOnPage() {
        var timeOnPage = Math.round((Date.now() - startTime) / 1000);
        sendData('time_on_page', { time_on_page: timeOnPage });
    }

    function endSession() {
        var duration = Math.round((Date.now() - startTime) / 1000);
        var beaconSent = sendBeacon('session_end', { duration: duration, page_views: pageViews });
        if (!beaconSent) sendData('session_end', { duration: duration, page_views: pageViews });
    }

    function updateActivity() { lastActivity = Date.now(); }
    function detectInactivity() {
        var inactiveTime = Date.now() - lastActivity;
        if (inactiveTime > 30000) {
            isTracking = false;
            setTimeout(function() { isTracking = true; }, 10000);
        }
    }
    function sendHeartbeat() {
        if (isTracking) sendData('heartbeat', { active_time: Date.now() - lastActivity, current_scroll: window.scrollY });
    }

    function init() {
        initSession();
        trackPageView();
        document.addEventListener('click', trackClick, true);

        var scrollTimeout, lastScrollDepth = 0;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                var currentDepth = Math.round((window.scrollY / Math.max(document.body.scrollHeight - window.innerHeight, 1)) * 100);
                if (Math.abs(currentDepth - lastScrollDepth) > 10) {
                    trackScrollDepth();
                    lastScrollDepth = currentDepth;
                }
            }, 500);
            updateActivity();
        }, { passive: true });

        window.addEventListener('focus', updateActivity);
        window.addEventListener('blur', trackTimeOnPage);
        window.addEventListener('beforeunload', function() { trackTimeOnPage(); endSession(); });
        window.addEventListener('pagehide', endSession);

        document.addEventListener('mousemove', updateActivity, { passive: true });
        document.addEventListener('keypress', updateActivity, { passive: true });
        document.addEventListener('touchstart', updateActivity, { passive: true });

        setInterval(sendHeartbeat, 60000);
        setInterval(detectInactivity, 10000);
    }

    window.analyticsTracker = {
        trackEvent: function(eventName, eventData) {
            sendData('custom_event', { event_name: eventName, event_data: eventData || {} });
        },
        trackPageView: trackPageView,
        updateActivity: updateActivity,
        endSession: endSession
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
```

> Source in project: `classes/WebsiteManager.php` -> `generateTrackingScript()`

#### 4. مراقبة الإحصائيات | Monitoring Statistics
- انتقل إلى تبويب "التحليلات" | Go to "Analytics" tab
- اختر الفترة الزمنية المطلوبة | Select desired time period
- راجع التقارير والرسوم البيانية | Review reports and charts

### للمطورين | For Developers

#### API Endpoints
```
POST /api/track.php?action=session     - بدء جلسة جديدة | Start new session
POST /api/track.php?action=pageview   - تتبع مشاهدة صفحة | Track page view
POST /api/track.php?action=click      - تتبع نقرة | Track click
POST /api/track.php?action=custom_event - حدث مخصص | Custom event
```

#### مثال استخدام JavaScript | JavaScript Usage Example
```javascript
// تتبع حدث مخصص | Track custom event
window.analyticsTracker.trackEvent('purchase', {
    product_id: '123',
    value: 99.99,
    currency: 'USD'
});

// تتبع مشاهدة صفحة | Track page view
window.analyticsTracker.trackPageView();
```

---

## 👨‍💼 لوحة الإدارة | Admin Panel

### الوصول | Access
انتقل إلى `/admin/` وسجل دخولك كمدير | Navigate to `/admin/` and login as admin

### المميزات الإدارية | Admin Features
- إدارة المستخدمين | User management
- مراجعة واعتماد المواقع | Website review and approval
- إدارة المنصات | Platform management
- إحصائيات عامة | General statistics
- تصدير التقارير | Export reports

### الحساب الافتراضي | Default Account
```
Username: admin
Email: admin@analytics.com
Password: admin123 (يُنصح بتغييرها | recommended to change)
```

---

## 🔧 التخصيص | Customization

### إضافة منصة جديدة | Adding New Platform
1. انتقل إلى لوحة الإدارة | Go to admin panel
2. اختر تبويب "المنصات" | Select "Platforms" tab
3. اضغط "إضافة منصة جديدة" | Click "Add New Platform"
4. حدد معرفات العناصر | Define element selectors

### مثال معرفات المنصة | Platform Selectors Example
```json
{
  "search_box": {
    "selector": ".search-input, #search",
    "type": "input"
  },
  "add_to_cart": {
    "selector": ".add-to-cart, .btn-cart",
    "type": "button"
  },
  "checkout": {
    "selector": ".checkout-btn",
    "type": "button"
  }
}
```

---

## 🎨 التصميم والواجهة | Design & Interface

### الألوان الرئيسية | Main Colors
```css
--primary-color: #1a365d;    /* الأزرق الداكن | Dark blue */
--accent-color: #3182ce;     /* الأزرق المميز | Accent blue */
--success-color: #38a169;    /* الأخضر | Green */
--error-color: #e53e3e;      /* الأحمر | Red */
```

### الخطوط | Fonts
- **Arabic**: Tajawal (Google Fonts)
- **English**: Roboto (Google Fonts)

### دعم اللغات | Language Support
- دعم كامل للعربية (RTL) | Full Arabic support (RTL)
- دعم الإنجليزية (LTR) | English support (LTR)
- تبديل اللغة بنقرة واحدة | One-click language switching

---

## 🔒 الأمان | Security

### إجراءات الحماية | Security Measures
- تشفير كلمات المرور | Password hashing
- حماية من CSRF | CSRF protection
- التحقق من صحة النطاق | Domain validation
- تنظيف البيانات | Data sanitization
- حماية من SQL Injection | SQL injection prevention

### أفضل الممارسات | Best Practices
- استخدم HTTPS | Use HTTPS
- حديث PHP بانتظام | Update PHP regularly
- استخدم كلمات مرور قوية | Use strong passwords
- قم بعمل نسخ احتياطية منتظمة | Regular backups

---

## 📈 الأداء | Performance

### التحسينات | Optimizations
- ضغط JavaScript | JavaScript minification
- تحسين استعلامات قاعدة البيانات | Database query optimization
- تخزين مؤقت للصفحات | Page caching
- تحسين الصور | Image optimization

### مراقبة الأداء | Performance Monitoring
- تتبع أوقات الاستجابة | Response time tracking
- مراقبة استخدام الذاكرة | Memory usage monitoring
- إحصائيات قاعدة البيانات | Database statistics

---

## 🐛 استكشاف الأخطاء | Troubleshooting

### مشاكل شائعة | Common Issues

#### لا يعمل كود التتبع | Tracking Code Not Working
```javascript
// تحقق من وجود الكود في console المتصفح
// Check for code in browser console
console.log('Analytics tracker loaded:', window.analyticsTracker);
```

#### خطأ في الاتصال بقاعدة البيانات | Database Connection Error
```php
// تحقق من إعدادات الاتصال في config/database.php
// Check connection settings in config/database.php
```

#### مشكلة في إرسال البريد الإلكتروني | Email Sending Issue
```php
// تحقق من إعدادات SMTP
// Check SMTP settings
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## 📞 الدعم | Support

### التواصل | Contact
- **Name**: Yousuf Alharbi
- **Email**: z8@hotmail.com
- **Website**: [www.Youo.info](https://youo.info)

### المساهمة | Contributing
يرجى التواصل معنا.

Please contact us.

---

## 🙏 شكر وتقدير | Acknowledgments

- Font Awesome للأيقونات | for icons
- Google Fonts للخطوط | for fonts

---

## 📋 قائمة المهام | TODO

- [ ] إضافة تصدير التقارير PDF | Add PDF report export
- [ ] تطبيق الجوال | Mobile application
- [ ] تكامل مع Google Analytics | Google Analytics integration
- [ ] دعم المزيد من اللغات | Multi-language support
- [ ] API موثق بالكامل | Fully documented API
- [ ] اختبارات تلقائية | Automated testing

---

## 👨‍💻 معلومات المطور | Developer Info

- **Developer**: Yousuf Alharbi
- **Email**: z8@hotmail.com
- **Website**: [www.Youo.info](https://youo.info)

**الإصدار**: 1.0.0 | **Version**: 1.0.0

**تاريخ الإطلاق**: 2024 | **Release Date**: 2024
