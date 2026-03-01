<?php
// classes/CustomEventsManager.php
class CustomEventsManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function addCustomEvent($website_id, $data) {
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['event_name'])) {
            $errors[] = 'اسم الحدث مطلوب';
        }
        
        if (empty($data['event_display_name'])) {
            $errors[] = 'اسم العرض مطلوب';
        }
        
        if (empty($data['selector'])) {
            $errors[] = 'محدد العنصر مطلوب';
        }
        
        if (empty($data['event_type'])) {
            $errors[] = 'نوع الحدث مطلوب';
        }
        
        if (!in_array($data['event_type'], ['click_count', 'count_products', 'form_submit', 'page_time', 'scroll_depth'])) {
            $errors[] = 'نوع الحدث غير صحيح';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // التحقق من عدم وجود حدث بنفس الاسم
        $existing = $this->db->fetchOne(
            "SELECT id FROM custom_event_definitions WHERE website_id = ? AND event_name = ?",
            [$website_id, $data['event_name']]
        );
        
        if ($existing) {
            return ['success' => false, 'errors' => ['اسم الحدث موجود بالفعل']];
        }
        
        $sql = "INSERT INTO custom_event_definitions (website_id, event_name, event_display_name, selector, event_type, product_selector, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, [
            $website_id,
            sanitize($data['event_name']),
            sanitize($data['event_display_name']),
            sanitize($data['selector']),
            $data['event_type'],
            sanitize($data['product_selector'] ?? ''),
            sanitize($data['description'] ?? '')
        ]);
        
        if ($result) {
            return ['success' => true, 'event_id' => $this->db->lastInsertId(), 'message' => 'تم إضافة الحدث المخصص بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء إضافة الحدث']];
    }
    
    public function getWebsiteCustomEvents($website_id) {
        return $this->db->fetchAll(
            "SELECT * FROM custom_event_definitions WHERE website_id = ? ORDER BY created_at DESC",
            [$website_id]
        );
    }
    
    public function getCustomEventById($event_id, $website_id = null) {
        $sql = "SELECT * FROM custom_event_definitions WHERE id = ?";
        $params = [$event_id];
        
        if ($website_id) {
            $sql .= " AND website_id = ?";
            $params[] = $website_id;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    public function updateCustomEvent($event_id, $website_id, $data) {
        $errors = [];
        
        if (empty($data['event_display_name'])) {
            $errors[] = 'اسم العرض مطلوب';
        }
        
        if (empty($data['selector'])) {
            $errors[] = 'محدد العنصر مطلوب';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $sql = "UPDATE custom_event_definitions 
                SET event_display_name = ?, selector = ?, product_selector = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND website_id = ?";
        
        $result = $this->db->query($sql, [
            sanitize($data['event_display_name']),
            sanitize($data['selector']),
            sanitize($data['product_selector'] ?? ''),
            sanitize($data['description'] ?? ''),
            $event_id,
            $website_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تحديث الحدث بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء تحديث الحدث']];
    }
    
    public function toggleEventStatus($event_id, $website_id) {
        $event = $this->getCustomEventById($event_id, $website_id);
        if (!$event) {
            return false;
        }
        
        $new_status = $event['is_active'] ? 0 : 1;
        return $this->db->query(
            "UPDATE custom_event_definitions SET is_active = ? WHERE id = ? AND website_id = ?",
            [$new_status, $event_id, $website_id]
        );
    }
    
    public function deleteCustomEvent($event_id, $website_id) {
        $this->db->beginTransaction();
        
        try {
            // حذف البيانات المرتبطة أولاً
            $this->db->query("DELETE FROM custom_event_data WHERE event_definition_id = ?", [$event_id]);
            $this->db->query("DELETE FROM daily_custom_event_stats WHERE event_definition_id = ?", [$event_id]);
            
            // حذف تعريف الحدث
            $result = $this->db->query(
                "DELETE FROM custom_event_definitions WHERE id = ? AND website_id = ?",
                [$event_id, $website_id]
            );
            
            $this->db->commit();
            
            if ($result) {
                return ['success' => true, 'message' => 'تم حذف الحدث بنجاح'];
            }
            
            return ['success' => false, 'error' => 'الحدث غير موجود'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'حدث خطأ أثناء حذف الحدث'];
        }
    }
    
    public function recordCustomEventData($event_definition_id, $website_id, $session_id, $data) {
        $sql = "INSERT INTO custom_event_data (event_definition_id, website_id, session_id, event_value, additional_data, page_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        return $this->db->query($sql, [
            $event_definition_id,
            $website_id,
            $session_id,
            $data['event_value'] ?? 1,
            json_encode($data['additional_data'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['page_url'] ?? ''
        ]);
    }
    
    public function getCustomEventStats($website_id, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT 
                ced.id,
                ced.event_name,
                ced.event_display_name,
                ced.event_type,
                COUNT(cedata.id) as total_triggers,
                SUM(cedata.event_value) as total_value,
                COUNT(DISTINCT cedata.session_id) as unique_sessions,
                AVG(cedata.event_value) as avg_value
             FROM custom_event_definitions ced
             LEFT JOIN custom_event_data cedata ON ced.id = cedata.event_definition_id 
                AND cedata.created_at >= ?
             WHERE ced.website_id = ? AND ced.is_active = 1
             GROUP BY ced.id, ced.event_name, ced.event_display_name, ced.event_type
             ORDER BY total_triggers DESC",
            [$start_date, $website_id]
        );
    }
    
    public function getCustomEventTrends($event_definition_id, $days = 30) {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as triggers,
                SUM(event_value) as total_value,
                COUNT(DISTINCT session_id) as unique_sessions
             FROM custom_event_data 
             WHERE event_definition_id = ? AND DATE(created_at) >= ?
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$event_definition_id, $start_date]
        );
    }
    
    public function findElementSuggestions($website_id, $search_term) {
        // يمكن تطوير هذه الدالة لاحقاً لتوفير اقتراحات بناءً على النقرات السابقة
        $suggestions = [];
        
        // البحث في النقرات السابقة عن عناصر مشابهة
        $results = $this->db->fetchAll(
            "SELECT DISTINCT element_selector, element_text, COUNT(*) as usage_count
             FROM clicks 
             WHERE website_id = ? 
             AND (element_text LIKE ? OR element_selector LIKE ? OR element_class LIKE ?)
             GROUP BY element_selector, element_text
             ORDER BY usage_count DESC
             LIMIT 10",
            [$website_id, "%{$search_term}%", "%{$search_term}%", "%{$search_term}%"]
        );
        
        foreach ($results as $result) {
            if (!empty($result['element_selector'])) {
                $suggestions[] = [
                    'selector' => $result['element_selector'],
                    'text' => $result['element_text'],
                    'usage_count' => $result['usage_count'],
                    'type' => 'historical'
                ];
            }
        }
        
        // اقتراحات افتراضية شائعة
        $common_selectors = [
            ['selector' => '.btn, .button', 'text' => 'الأزرار العامة', 'type' => 'common'],
            ['selector' => '.add-to-cart, .اضافة-للسلة', 'text' => 'أزرار إضافة للسلة', 'type' => 'common'],
            ['selector' => '.product, .منتج', 'text' => 'المنتجات', 'type' => 'common'],
            ['selector' => '.cart, .سلة', 'text' => 'السلة', 'type' => 'common'],
            ['selector' => '.wishlist, .امنيات', 'text' => 'قائمة الأمنيات', 'type' => 'common'],
            ['selector' => 'header, .header', 'text' => 'الرأس', 'type' => 'common'],
            ['selector' => 'footer, .footer', 'text' => 'التذييل', 'type' => 'common'],
            ['selector' => '.menu, .قائمة', 'text' => 'القوائم', 'type' => 'common']
        ];
        
        foreach ($common_selectors as $common) {
            if (stripos($common['text'], $search_term) !== false || 
                stripos($common['selector'], $search_term) !== false) {
                $suggestions[] = $common;
            }
        }
        
        return array_slice($suggestions, 0, 15);
    }
    
    public function validateSelector($selector) {
        // التحقق من صحة CSS selector
        $valid_patterns = [
            '/^[a-zA-Z][\w-]*$/',           // class or id names
            '/^#[a-zA-Z][\w-]*$/',          // ID selector
            '/^\.[a-zA-Z][\w-]*$/',         // class selector
            '/^[a-zA-Z]+$/',                // tag name
            '/^\[[\w-]+.*\]$/',             // attribute selector
            '/^[\w\s\.,#\[\]:()-]+$/'       // complex selectors
        ];
        
        foreach ($valid_patterns as $pattern) {
            if (preg_match($pattern, trim($selector))) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getEventTypeOptions() {
        return [
            'click_count' => [
                'name_ar' => 'عدد النقرات',
                'name_en' => 'Click Count',
                'description_ar' => 'حساب عدد النقرات على العنصر المحدد',
                'description_en' => 'Count clicks on the specified element',
                'requires_product_selector' => false
            ],
            'count_products' => [
                'name_ar' => 'عدد المنتجات',
                'name_en' => 'Product Count',
                'description_ar' => 'حساب عدد المنتجات في الحاوية المحددة',
                'description_en' => 'Count products in the specified container',
                'requires_product_selector' => true
            ],
            'form_submit' => [
                'name_ar' => 'إرسال النموذج',
                'name_en' => 'Form Submit',
                'description_ar' => 'تتبع إرسال النماذج',
                'description_en' => 'Track form submissions',
                'requires_product_selector' => false
            ],
            'page_time' => [
                'name_ar' => 'وقت الصفحة',
                'name_en' => 'Page Time',
                'description_ar' => 'قياس الوقت المقضي في الصفحة',
                'description_en' => 'Measure time spent on page',
                'requires_product_selector' => false
            ],
            'scroll_depth' => [
                'name_ar' => 'عمق التمرير',
                'name_en' => 'Scroll Depth',
                'description_ar' => 'قياس عمق التمرير في الصفحة',
                'description_en' => 'Measure scroll depth on page',
                'requires_product_selector' => false
            ]
        ];
    }
    
    public function generateEventCode($event_definition) {
        $code = "
// حدث مخصص: {$event_definition['event_display_name']}
window.analyticsTracker.addCustomEvent({
    name: '{$event_definition['event_name']}',
    selector: '{$event_definition['selector']}',
    type: '{$event_definition['event_type']}'";
        
        if (!empty($event_definition['product_selector'])) {
            $code .= ",\n    product_selector: '{$event_definition['product_selector']}'";
        }
        
        $code .= "\n});";
        
        return $code;
    }
    
    public function updateDailyStats($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // تحديث إحصائيات الأحداث المخصصة اليومية
        $sql = "INSERT INTO daily_custom_event_stats (event_definition_id, website_id, stat_date, total_triggers, total_value, unique_sessions)
                SELECT 
                    ced.id,
                    ced.website_id,
                    DATE(cedata.created_at) as stat_date,
                    COUNT(*) as total_triggers,
                    SUM(cedata.event_value) as total_value,
                    COUNT(DISTINCT cedata.session_id) as unique_sessions
                FROM custom_event_definitions ced
                INNER JOIN custom_event_data cedata ON ced.id = cedata.event_definition_id
                WHERE DATE(cedata.created_at) = ?
                GROUP BY ced.id, ced.website_id, DATE(cedata.created_at)
                ON DUPLICATE KEY UPDATE
                    total_triggers = VALUES(total_triggers),
                    total_value = VALUES(total_value),
                    unique_sessions = VALUES(unique_sessions),
                    updated_at = NOW()";
        
        return $this->db->query($sql, [$date]);
    }
}
?>