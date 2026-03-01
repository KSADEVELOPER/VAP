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
// سيتم توليد الكود تلقائياً حسب موقعك
// Code will be automatically generated for your website
</script>
```

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
- **Email**: support@analytics.com
- **Documentation**: [docs.analytics.com](https://docs.analytics.com)
- **Issues**: [GitHub Issues](https://github.com/your-repo/analytics-platform/issues)

### المساهمة | Contributing
نرحب بمساهماتكم! يرجى قراءة [CONTRIBUTING.md](CONTRIBUTING.md) للمزيد من التفاصيل.

We welcome contributions! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 📄 الترخيص | License

هذا المشروع مرخص تحت رخصة MIT - راجع ملف [LICENSE](LICENSE) للتفاصيل.

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 شكر وتقدير | Acknowledgments

- Font Awesome للأيقونات | for icons
- Google Fonts للخطوط | for fonts
- جميع المساهمين في المشروع | All project contributors

---

## 📋 قائمة المهام | TODO

- [ ] إضافة تصدير التقارير PDF | Add PDF report export
- [ ] تطبيق الجوال | Mobile application
- [ ] تكامل مع Google Analytics | Google Analytics integration
- [ ] دعم المزيد من اللغات | Multi-language support
- [ ] API موثق بالكامل | Fully documented API
- [ ] اختبارات تلقائية | Automated testing

---

**تطوير**: فريق تطوير منصة التحليلات | **Developed by**: Analytics Platform Team
  
**المطور**: Youusf Alharbi | **Developer Email**: Z8@Hotmail.Com

**الإصدار**: 1.0.0 | **Version**: 1.0.0

**تاريخ الإطلاق**: 2024 | **Release Date**: 2024
