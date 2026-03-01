<?php
// classes/PlatformManager.php
class PlatformManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllPlatforms() {
        return $this->db->fetchAll("SELECT * FROM platforms WHERE is_active = 1 ORDER BY name");
    }
    
    public function getPlatformById($id) {
        return $this->db->fetchOne("SELECT * FROM platforms WHERE id = ?", [$id]);
    }
    
    public function addPlatform($data, $admin_id) {
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['name'])) {
            $errors[] = 'اسم المنصة بالعربية مطلوب';
        }
        
        if (empty($data['name_en'])) {
            $errors[] = 'اسم المنصة بالإنجليزية مطلوب';
        }
        
        if (empty($data['selectors']) || !$this->validateSelectors($data['selectors'])) {
            $errors[] = 'معرفات العناصر غير صحيحة';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // التحقق من وجود المنصة
        $existing = $this->db->fetchOne("SELECT id FROM platforms WHERE name = ? OR name_en = ?", 
            [$data['name'], $data['name_en']]);
        
        if ($existing) {
            return ['success' => false, 'errors' => ['اسم المنصة موجود بالفعل']];
        }
        
        $sql = "INSERT INTO platforms (name, name_en, description, description_en, selectors, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $result = $this->db->query($sql, [
            sanitize($data['name']),
            sanitize($data['name_en']),
            sanitize($data['description']),
            sanitize($data['description_en']),
            json_encode($data['selectors'], JSON_UNESCAPED_UNICODE),
            $admin_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم إضافة المنصة بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء إضافة المنصة']];
    }
    
    public function updatePlatform($platform_id, $data) {
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['name'])) {
            $errors[] = 'اسم المنصة بالعربية مطلوب';
        }
        
        if (empty($data['name_en'])) {
            $errors[] = 'اسم المنصة بالإنجليزية مطلوب';
        }
        
        if (empty($data['selectors']) || !$this->validateSelectors($data['selectors'])) {
            $errors[] = 'معرفات العناصر غير صحيحة';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // التحقق من وجود المنصة (باستثناء المنصة الحالية)
        $existing = $this->db->fetchOne("SELECT id FROM platforms WHERE (name = ? OR name_en = ?) AND id != ?", 
            [$data['name'], $data['name_en'], $platform_id]);
        
        if ($existing) {
            return ['success' => false, 'errors' => ['اسم المنصة موجود بالفعل']];
        }
        
        $sql = "UPDATE platforms SET name = ?, name_en = ?, description = ?, description_en = ?, selectors = ? WHERE id = ?";
        $result = $this->db->query($sql, [
            sanitize($data['name']),
            sanitize($data['name_en']),
            sanitize($data['description']),
            sanitize($data['description_en']),
            json_encode($data['selectors'], JSON_UNESCAPED_UNICODE),
            $platform_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تحديث المنصة بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء تحديث المنصة']];
    }
    
    public function deletePlatform($platform_id) {
        // التحقق من وجود مواقع تستخدم هذه المنصة
        $websites_count = $this->db->fetchOne("SELECT COUNT(*) as count FROM websites WHERE platform_id = ?", 
            [$platform_id])['count'];
        
        if ($websites_count > 0) {
            return ['success' => false, 'error' => 'لا يمكن حذف المنصة لوجود مواقع تستخدمها'];
        }
        
        $result = $this->db->query("DELETE FROM platforms WHERE id = ?", [$platform_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم حذف المنصة بنجاح'];
        }
        
        return ['success' => false, 'error' => 'حدث خطأ أثناء حذف المنصة'];
    }
    
    public function togglePlatformStatus($platform_id) {
        $platform = $this->getPlatformById($platform_id);
        if (!$platform) return false;
        
        $new_status = $platform['is_active'] ? 0 : 1;
        return $this->db->query("UPDATE platforms SET is_active = ? WHERE id = ?", [$new_status, $platform_id]);
    }
    
    public function getPlatformSelectors($platform_id) {
        $platform = $this->getPlatformById($platform_id);
        if (!$platform) return [];
        
        return json_decode($platform['selectors'], true) ?: [];
    }
    
    public function validateSelectors($selectors) {
        // التحقق من أن المعرفات في تنسيق JSON صحيح
        if (is_string($selectors)) {
            $selectors = json_decode($selectors, true);
        }
        
        if (!is_array($selectors)) {
            return false;
        }
        
        // التحقق من الحقول المطلوبة
        $required_fields = ['search_box', 'add_to_cart', 'checkout', 'product_view'];
        
        foreach ($required_fields as $field) {
            if (!isset($selectors[$field])) {
                return false;
            }
            
            if (!is_array($selectors[$field]) || 
                !isset($selectors[$field]['selector']) || 
                !isset($selectors[$field]['type'])) {
                return false;
            }
        }
        
        return true;
    }
    
    public function getDefaultSelectors() {
        return [
            'search_box' => [
                'selector' => '.search-input, #search, [name="search"], [name="q"]',
                'type' => 'input',
                'description' => 'حقل البحث في الموقع'
            ],
            'add_to_cart' => [
                'selector' => '.add-to-cart, .btn-cart, [data-action="add-to-cart"]',
                'type' => 'button',
                'description' => 'زر إضافة إلى السلة'
            ],
            'checkout' => [
                'selector' => '.checkout-btn, .btn-checkout, [data-action="checkout"]',
                'type' => 'button',
                'description' => 'زر الدفع أو إتمام الطلب'
            ],
            'product_view' => [
                'selector' => '.product-item, .product-card, .product-link',
                'type' => 'link',
                'description' => 'رابط عرض المنتج'
            ],
            'newsletter_signup' => [
                'selector' => '.newsletter-form, #newsletter, [data-action="newsletter"]',
                'type' => 'form',
                'description' => 'نموذج الاشتراك في النشرة'
            ],
            'contact_form' => [
                'selector' => '.contact-form, #contact, [data-action="contact"]',
                'type' => 'form',
                'description' => 'نموذج الاتصال'
            ],
            'login_form' => [
                'selector' => '.login-form, #login, [data-action="login"]',
                'type' => 'form',
                'description' => 'نموذج تسجيل الدخول'
            ],
            'register_form' => [
                'selector' => '.register-form, #register, [data-action="register"]',
                'type' => 'form',
                'description' => 'نموذج التسجيل'
            ]
        ];
    }
    
    public function getSelectorTypes() {
        return [
            'input' => 'حقل إدخال',
            'button' => 'زر',
            'link' => 'رابط',
            'form' => 'نموذج',
            'div' => 'عنصر div',
            'span' => 'عنصر span',
            'section' => 'قسم'
        ];
    }
    
    public function buildSelectorString($selector_data) {
        if (!is_array($selector_data)) {
            return '';
        }
        
        $selectors = [];
        
        foreach ($selector_data as $key => $data) {
            if (isset($data['selector']) && !empty(trim($data['selector']))) {
                $selectors[] = trim($data['selector']);
            }
        }
        
        return implode(', ', $selectors);
    }
    
    public function testPlatformSelectors($url, $selectors) {
        // محاولة جلب محتوى الصفحة واختبار المعرفات
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; AnalyticsBot/1.0)'
                ]
            ]);
            
            $content = file_get_contents($url, false, $context);
            
            if (!$content) {
                return ['success' => false, 'error' => 'لا يمكن الوصول إلى الموقع'];
            }
            
            $found_selectors = [];
            $missing_selectors = [];
            
            foreach ($selectors as $name => $data) {
                if (empty($data['selector'])) continue;
                
                $selector_found = false;
                $selector_parts = explode(',', $data['selector']);
                
                foreach ($selector_parts as $selector) {
                    $selector = trim($selector);
                    
                    // البحث عن المعرف في المحتوى
                    if (strpos($selector, '#') === 0) {
                        // البحث عن ID
                        $id = substr($selector, 1);
                        if (preg_match('/id=["\']' . preg_quote($id, '/') . '["\']/', $content)) {
                            $selector_found = true;
                            break;
                        }
                    } elseif (strpos($selector, '.') === 0) {
                        // البحث عن Class
                        $class = substr($selector, 1);
                        if (preg_match('/class=["\'][^"\']*' . preg_quote($class, '/') . '[^"\']*["\']/', $content)) {
                            $selector_found = true;
                            break;
                        }
                    } else {
                        // البحث عن اسم العنصر أو attribute
                        if (stripos($content, $selector) !== false) {
                            $selector_found = true;
                            break;
                        }
                    }
                }
                
                if ($selector_found) {
                    $found_selectors[$name] = $data;
                } else {
                    $missing_selectors[$name] = $data;
                }
            }
            
            return [
                'success' => true,
                'found_selectors' => $found_selectors,
                'missing_selectors' => $missing_selectors,
                'found_count' => count($found_selectors),
                'total_count' => count($selectors)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'حدث خطأ أثناء اختبار المعرفات: ' . $e->getMessage()];
        }
    }
    
    public function getPlatformUsageStats() {
        return $this->db->fetchAll("
            SELECT p.name, p.name_en, COUNT(w.id) as websites_count 
            FROM platforms p 
            LEFT JOIN websites w ON p.id = w.platform_id 
            GROUP BY p.id, p.name, p.name_en 
            ORDER BY websites_count DESC
        ");
    }
}
?>