<?php
// classes/TrackingManager.php

class TrackingManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }


public function deleteElement(int $element_id, int $website_id): array
{
    // تأكد أن العنصر موجود وينتمي لنفس الموقع
    $exists = $this->db->fetchOne(
        "SELECT id FROM tracking_elements WHERE id = ? AND website_id = ? LIMIT 1",
        [$element_id, $website_id]
    );
    if (!$exists) {
        return ['success' => false, 'error' => 'العنصر غير موجود أو لا يتبع هذا الموقع.'];
    }

    try {
        // بدء معاملة
        $this->db->query("START TRANSACTION");

        // احذف الأحداث المرتبطة بهذا العنصر
        $ok1 = $this->db->query(
            "DELETE FROM tracking_events WHERE website_id = ? AND element_id = ?",
            [$website_id, $element_id]
        );

        // احذف العنصر نفسه
        $ok2 = $this->db->query(
            "DELETE FROM tracking_elements WHERE website_id = ? AND id = ?",
            [$website_id, $element_id]
        );

        if (!$ok1 || !$ok2) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => 'فشل الحذف من قاعدة البيانات.'];
        }

        // إنهاء المعاملة
        $this->db->query("COMMIT");
        return ['success' => true, 'message' => 'تم حذف العنصر وكل أحداثه بنجاح.'];

    } catch (\Throwable $e) {
        // في حال أي خطأ
        $this->db->query("ROLLBACK");
        return ['success' => false, 'error' => 'استثناء أثناء الحذف: '.$e->getMessage()];
    }
}

    /**
     * جلب قائمة بالعناصر المعرَّفة
     */
    public function getElements(int $website_id): array {
        return $this->db->fetchAll(
            "SELECT id, name, selector, selector_type, description, created_at
             FROM tracking_elements
             WHERE website_id = ?
             ORDER BY created_at DESC",
            [$website_id]
        );
    }

    /**
     * إحصائيات مجمّعة لجميع العناصر
     */
    public function getElementStats(int $website_id, int $days = 30): array {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->fetchAll("
            SELECT
              t.id,
              t.name,
              t.selector,
              t.selector_type,
              t.impression_count + COALESCE(ev.impressions,0)   AS total_impressions,
              t.hover_count      + COALESCE(ev.hovers,0)        AS total_hovers,
              t.submit_count     + COALESCE(ev.submits,0)       AS total_submits,
              COALESCE(ev.clicks,0)                             AS total_clicks,
              COALESCE(ev.unique_users,0)                       AS unique_users,
              CASE
                WHEN (t.impression_count + COALESCE(ev.impressions,0)) > 0
                THEN ROUND(ev.clicks * 100.0 / (t.impression_count + COALESCE(ev.impressions,0)),2)
                ELSE 0
              END                                               AS ctr_percent
            FROM tracking_elements t
            LEFT JOIN (
              SELECT
                element_id,
                SUM(CASE WHEN event_type='impression' THEN 1 ELSE 0 END) AS impressions,
                SUM(CASE WHEN event_type='hover_start' THEN 1 ELSE 0 END) AS hovers,
                SUM(CASE WHEN event_type='submit' THEN 1 ELSE 0 END)       AS submits,
                SUM(CASE WHEN event_type='click' THEN 1 ELSE 0 END)        AS clicks,
                COUNT(DISTINCT CASE WHEN event_type='click' THEN session_id END) AS unique_users
              FROM tracking_events
              WHERE occurred_at >= ?
              GROUP BY element_id
            ) ev ON ev.element_id = t.id
            WHERE t.website_id = ?
            ORDER BY total_clicks DESC, total_impressions DESC
        ", [$start, $website_id]);
    }

    /**
     * جلب تفاصيل النقرات لكل عنصر (لعرض جدول clicks)
     */
    public function getClickDetails(int $element_id, int $days = 30): array {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->fetchAll("
            SELECT session_id, occurred_at
            FROM tracking_events
            WHERE element_id = ?
              AND event_type = 'click'
              AND occurred_at >= ?
            ORDER BY occurred_at DESC
        ", [$element_id, $start]);
    }

    /**
     * جلب عدد المرات التي ظهر فيها العنصر في الـ viewport
     */
    public function getImpressionCount(int $element_id, int $days = 30): int {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return (int)$this->db->fetchOne("
            SELECT COUNT(*) AS cnt
            FROM tracking_events
            WHERE element_id = ?
              AND event_type = 'impression'
              AND occurred_at >= ?
        ", [$element_id, $start])['cnt'];
    }

    /**
     * جلب عدد hover لكل عنصر
     */
    public function getHoverCount(int $element_id, int $days = 30): int {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return (int)$this->db->fetchOne("
            SELECT COUNT(*) AS cnt
            FROM tracking_events
            WHERE element_id = ?
              AND event_type = 'hover_start'
              AND occurred_at >= ?
        ", [$element_id, $start])['cnt'];
    }

    /**
     * متوسط مدة التحويم (hover) على العنصر
     */
    public function getAvgHoverDuration(int $element_id, int $days = 30): float {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        // نفترض أن لكل hover_start و hover_end سجّلان متتاليان بنفس session_id
        $rows = $this->db->fetchAll("
            SELECT
              h1.session_id,
              TIMESTAMPDIFF(SECOND, h1.occurred_at, h2.occurred_at) AS duration
            FROM tracking_events h1
            JOIN tracking_events h2
              ON h2.element_id = h1.element_id
             AND h2.event_type = 'hover_end'
             AND h2.session_id = h1.session_id
             AND h2.occurred_at > h1.occurred_at
            WHERE h1.element_id = ?
              AND h1.event_type = 'hover_start'
              AND h1.occurred_at >= ?
        ", [$element_id, $start]);

        if (empty($rows)) {
            return 0.0;
        }
        $total = array_sum(array_column($rows, 'duration'));
        return round($total / count($rows), 2);
    }

    /**
     * جلب عدد مرات إرسال النموذج (submit) لكل عنصر
     */
    public function getSubmitCount(int $element_id, int $days = 30): int {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return (int)$this->db->fetchOne("
            SELECT COUNT(*) AS cnt
            FROM tracking_events
            WHERE element_id = ?
              AND event_type = 'submit'
              AND occurred_at >= ?
        ", [$element_id, $start])['cnt'];
    }

    /**
     * جلب المستخدمين الفريدين الذين تفاعلوا مع العنصر
     */
    public function getUniqueUsers(int $element_id, int $days = 30): int {
        $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return (int)$this->db->fetchOne("
            SELECT COUNT(DISTINCT session_id) AS cnt
            FROM tracking_events
            WHERE element_id = ?
              AND event_type = 'click'
              AND occurred_at >= ?
        ", [$element_id, $start])['cnt'];
    }

    /**
     * حساب CTR (النقرات ÷ الظهور × 100)
     */
    public function getCTR(int $element_id, int $days = 30): float {
        $impr = $this->getImpressionCount($element_id, $days);
        if ($impr === 0) return 0.0;
        $clicks = $this->db->fetchOne("
            SELECT COUNT(*) AS cnt
            FROM tracking_events
            WHERE element_id = ? AND event_type='click' AND occurred_at >= ?
        ", [$element_id, date('Y-m-d H:i:s', strtotime("-{$days} days"))])['cnt'];
        return round($clicks * 100.0 / $impr, 2);
    }
    
 

// public function generateElementsScript(int $website_id): string
// {
//     // ١) جلب العناصر من قاعدة البيانات
//     $elements = $this->db->fetchAll(
//         "SELECT id, selector
//          FROM tracking_elements
//          WHERE website_id = ?",
//         [$website_id]
//     );

//     // ٢) تحديد عنوان الـ API
//     $apiUrl = 'https://youo.info/track/api/custom-track.php';

//     // تحويل العناصر إلى JSON مع الهروب الآمن
//     $elementsJson = json_encode($elements, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

//     // ٣) إنشاء السكربت
//     $script = <<<JS
// (function(){
//     'use strict';
//     // عنوان الـ API
//     var API_URL = '{$apiUrl}';
//     console.debug('[Tracker] API_URL =', API_URL);

//     // قائمة العناصر
//     var elements = JSON.parse('{$elementsJson}');
//     console.debug('[Tracker] Loaded elements:', elements);

//     // دالة إرسال الحدث
//     function sendEvent(elId, type){
//       var payload = {
//         website_id: {$website_id},
//         element_id: elId,
//         event_type: type,
//         occurred_at: new Date().toISOString(),
//         page_url: window.location.href
//       };
//       console.debug('[Tracker] sendEvent payload:', payload);
//       fetch(API_URL, {
//         method: 'POST',
//         headers: {'Content-Type':'application/json'},
//         body: JSON.stringify(payload),
//         mode: 'cors',
//         credentials: 'include'
//       })
//       .then(function(res){
//         console.debug('[Tracker] fetch status:', res.status);
//         return res.json();
//       })
//       .then(function(json){
//         console.debug('[Tracker] response JSON:', json);
//       })
//       .catch(function(err){
//         console.error('[Tracker] fetch error:', err);
//       });
//     }

//     // ربط كل عنصر بالأحداث
//     elements.forEach(function(el){
//       if (!el.selector) return console.warn('[Tracker] empty selector for', el);
//       var nodes = document.querySelectorAll(el.selector);
//       if (!nodes.length) {
//         console.warn('[Tracker] no nodes for selector', el.selector);
//       }
//       nodes.forEach(function(node){
//         // 1. Impression (مرة واحدة)
//         var seen = false;
//         var io = new IntersectionObserver(function(entries){
//           entries.forEach(function(entry){
//             if (entry.isIntersecting && !seen) {
//               seen = true;
//               console.debug('[Tracker] impression for', el.id);
//               sendEvent(el.id, 'impression');
//               io.disconnect();
//             }
//           });
//         }, {threshold: 0.1});
//         io.observe(node);

//         // 2. Hover start
//         node.addEventListener('mouseenter', function(){
//           console.debug('[Tracker] hover_start for', el.id);
//           sendEvent(el.id, 'hover_start');
//         });

//         // 3. Submit if inside form
//         var form = node.tagName.toLowerCase()==='form' ? node : node.closest('form');
//         if (form) {
//           form.addEventListener('submit', function(){
//             console.debug('[Tracker] submit for', el.id);
//             sendEvent(el.id, 'submit');
//           });
//         }

//         // 4. Click
//         node.addEventListener('click', function(){
//           console.debug('[Tracker] click for', el.id);
//           sendEvent(el.id, 'click');
//         });
//       });
//     });
// })();
// JS;

//     return $script;
// }
public function generateElementsScript(int $website_id): string
{
    // 1) جلب العناصر
    $elements = $this->db->fetchAll(
        "SELECT id, selector
         FROM tracking_elements
         WHERE website_id = ?
         ORDER BY id DESC",
        [$website_id]
    );

    // 2) نقطة الـ API (ثبّتها على endpoint الصحيح عندك)
    $apiUrl = rtrim(SITE_URL, '/') . '/api/custom-track.php';

    // 3) JSON آمن للحقن داخل JS
    $elementsJson = json_encode(
        $elements,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
    );

    // 4) سكربت العميل
    $script = <<<JS
(function(){
  'use strict';

  var API_URL = '{$apiUrl}';
  console.debug('[Tracker] API_URL =', API_URL);

  var elements = JSON.parse('{$elementsJson}');
  console.debug('[Tracker] Loaded elements:', elements);

  function sendEvent(elId, type){
    var payload = {
      website_id: {$website_id},
      element_id: elId,
      event_type: type,
      occurred_at: new Date().toISOString(),
      page_url: window.location.href
    };
    console.debug('[Tracker] sendEvent payload:', payload);

    fetch(API_URL, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload),
      mode: 'cors',
      credentials: 'include'
    })
    .then(function(res){
      console.debug('[Tracker] fetch status:', res.status);
      return res.text(); // نطبع النص لأي رسائل Debug ترجع من السيرفر
    })
    .then(function(txt){
      console.debug('[Tracker] response body:', txt);
    })
    .catch(function(err){
      console.error('[Tracker] fetch error:', err);
    });
  }

  elements.forEach(function(el){
    if (!el.selector) return console.warn('[Tracker] empty selector for', el);
    var nodes = document.querySelectorAll(el.selector);
    if (!nodes.length) {
      console.warn('[Tracker] no nodes for selector', el.selector);
    }
    nodes.forEach(function(node){

      // 1) Impression مرة واحدة
      var seen = false;
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting && !seen) {
            seen = true;
            console.debug('[Tracker] impression for', el.id);
            sendEvent(el.id, 'impression');
            io.disconnect();
          }
        });
      }, {threshold: 0.1});
      io.observe(node);

      // 2) Hover start / end (مهم لـ getAvgHoverDuration)
      node.addEventListener('mouseenter', function(){
        console.debug('[Tracker] hover_start for', el.id);
        sendEvent(el.id, 'hover_start');
      });
      node.addEventListener('mouseleave', function(){
        console.debug('[Tracker] hover_end for', el.id);
        sendEvent(el.id, 'hover_end');
      });

      // 3) Submit إن كان داخل فورم
      var form = node.tagName.toLowerCase()==='form' ? node : node.closest('form');
      if (form) {
        form.addEventListener('submit', function(){
          console.debug('[Tracker] submit for', el.id);
          sendEvent(el.id, 'submit');
        });
      }

      // 4) Click
      node.addEventListener('click', function(){
        console.debug('[Tracker] click for', el.id);
        sendEvent(el.id, 'click');
      });

    });
  });
})();
JS;

    return $script;
}


protected function jsonEncode(array $data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}


}