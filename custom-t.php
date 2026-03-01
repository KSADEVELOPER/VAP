<?php
// tracking_stats.php

require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';
require_once 'classes/TrackingManager.php';

// 1) تحقق من تسجيل الدخول
$userMgr = new UserManager($db);
if (!$userMgr->isLoggedIn()) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];

// 2) جلب الموقع والتأكد من الملكية
$website_id = (int)($_GET['id'] ?? 0);
$siteMgr    = new WebsiteManager($db);
$website    = $siteMgr->getWebsiteById($website_id, $user_id);
if (!$website) {
    redirect('dashboard.php');
}

// 3) منشئ التتبع + سكربت العناصر + قائمة العناصر
$trackMgr  = new TrackingManager($db);
$jsSnippet = $trackMgr->generateElementsScript($website_id);
$elements  = $trackMgr->getElements($website_id);

// 4) فترة التحليل
$days = (int)($_GET['days'] ?? 30);

// 5) نقاط نهاية AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    switch ($_GET['ajax']) {
        // إحصائيات مجمعة لكل العناصر
        case 'stats':
            echo json_encode(
                $trackMgr->getElementStats($website_id, $days),
                JSON_UNESCAPED_UNICODE
            );
            break;

        // قائمة بالعناصر نفسها
        case 'elements':
            echo json_encode(
                $trackMgr->getElements($website_id),
                JSON_UNESCAPED_UNICODE
            );
            break;

        // تفاصيل النقرات لعنصر معيّن
        case 'clicks':
            $eid = (int)($_GET['element_id'] ?? 0);
            echo json_encode(
                $trackMgr->getClickDetails($eid, $days),
                JSON_UNESCAPED_UNICODE
            );
            break;

        // ملخص كامل لعنصر واحد (يستخدم بقية الدوال)
        case 'element_summary':
            $eid = (int)($_GET['element_id'] ?? 0);
            $summary = [
                'impressions'        => $trackMgr->getImpressionCount($eid, $days),
                'hovers'             => $trackMgr->getHoverCount($eid, $days),
                'avg_hover_seconds'  => $trackMgr->getAvgHoverDuration($eid, $days),
                'submits'            => $trackMgr->getSubmitCount($eid, $days),
                'unique_users'       => $trackMgr->getUniqueUsers($eid, $days),
                'ctr_percent'        => $trackMgr->getCTR($eid, $days),
                // clicks: نستفيد من getClickDetails لتفادي إضافة دالة جديدة
                'clicks'             => count($trackMgr->getClickDetails($eid, $days)),
            ];
            echo json_encode($summary, JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إحصائيات تتبع العناصر — <?= htmlspecialchars($website['name']) ?></title>
  <style>
    body { font-family: sans-serif; padding:20px; background:#f7fafc; color:#1a202c; }
    h1,h2,h3 { margin:0 0 .5em; }
    .toolbar { display:flex; gap:10px; align-items:center; margin-bottom: 16px; }
    select, button { padding:.5em .75em; }
    table { width:100%; border-collapse: collapse; margin-top:1em; background:#fff; }
    th,td { border:1px solid #e2e8f0; padding:.65em; text-align:center }
    th { background:#edf2f7; font-weight:700; }
    code { background:#f1f5f9; padding:2px 6px; border-radius:4px; }
    .cards { display:grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin: 16px 0; }
    .card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; }
    .card .v { font-size:22px; font-weight:800; color:#2b6cb0; }
    .card .l { font-size:12px; color:#4a5568; }
    .section { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-top:16px; }
    .tracking-code { background:#0f172a; color:#e2e8f0; padding:12px; border-radius:8px; overflow:auto; margin-top:8px; }
    .actions button { margin: 0 4px; }
    #detailsModal, #summaryModal {
      display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;
      align-items:center; justify-content:center;
    }
    .modal-box { width:min(900px, 92vw); max-height:90vh; overflow:auto; background:#fff; border-radius:12px; border:1px solid #e2e8f0; }
    .modal-hd { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #e2e8f0; }
    .modal-bd { padding:12px 16px; }
    .close { background:#e53e3e; color:#fff; border:none; padding:.4em .7em; border-radius:6px; cursor:pointer; }
    
    
    
    /* شبكة البطاقات */
.el-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(280px,1fr));
  gap: 14px;
  margin-top: 10px;
}
.el-card {
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:12px 14px;
  display:flex;
  flex-direction:column;
  gap:10px;
}
.el-head {
  display:flex; gap:8px; align-items:center; justify-content:space-between;
}
.el-title {
  font-weight:800; color:#1a202c; font-size:15px; margin:0;
}
.el-sub {
  color:#64748b; font-size:12px; word-break:break-all;
}
.chip {
  background:#eef2ff; color:#3730a3; font-weight:700;
  font-size:11px; padding:3px 8px; border-radius:999px;
}
.el-metrics {
  display:grid; grid-template-columns: repeat(2,1fr);
  gap:10px;
}
.el-metric {
  border:1px dashed #e2e8f0; border-radius:10px; padding:8px;
  background:#f8fafc;
}
.el-metric .lbl { color:#4b5563; font-size:12px; margin-bottom:3px; }
.el-metric .val { color:#2b6cb0; font-size:20px; font-weight:800; }
.el-actions {
  display:flex; gap:8px; justify-content:flex-end; margin-top:2px;
}
.btn-min {
  border:1px solid #e2e8f0; background:#fff; color:#1f2937;
  padding:6px 10px; border-radius:8px; cursor:pointer;
}
.btn-min:hover { background:#f1f5f9; }
.kbd {
  background:#0f172a; color:#e2e8f0; font-size:11px; border-radius:6px;
  padding:2px 6px;
}
  </style>
</head>
<body>

  <h1>إحصائيات تتبع العناصر — <?= htmlspecialchars($website['name']) ?></h1>

  <div class="toolbar">
    <label>الفترة (أيام):
      <select id="period">
        <?php foreach ([7,30,90,365] as $d): ?>
          <option value="<?= $d ?>" <?= $d=== $days?'selected':'' ?>>آخر <?= $d ?> يوم</option>
        <?php endforeach; ?>
      </select>
    </label>
    <button onclick="loadStats()">تحديث</button>
  </div>

  <!-- جدول الإحصائيات المجمعة -->

<div class="section">
  <h3>إحصائيات مجمّعة</h3>

  <div class="toolbar" style="gap:8px;margin:8px 0 0">
    <input id="searchBox" placeholder="ابحث بالاسم أو الـ Selector"
           oninput="renderCards(window._statsCache)" 
           style="flex:1;max-width:340px;padding:.5em .75em;border:1px solid #e2e8f0;border-radius:8px;">
    <select id="sortBy" onchange="renderCards(window._statsCache)">
      <option value="clicks_desc">ترتيب بالنقرات (تنازلي)</option>
      <option value="impr_desc">ترتيب بالظهور (تنازلي)</option>
      <option value="ctr_desc">ترتيب CTR% (تنازلي)</option>
      <option value="name_asc">الاسم (ألفبائي)</option>
    </select>
  </div>

  <div id="statsCards" class="el-grid" style="margin-top:10px;">
    <!-- يتم الحقن عبر JS -->
  </div>
</div>


  <!-- قائمة العناصر كما هي (من قاعدة البيانات مباشرة) -->
  <div class="section">
    <h3>قائمة العناصر المُسجّلة</h3>
    <?php if (empty($elements)): ?>
      <p>لا توجد عناصر مسجّلة.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>#</th><th>الوصف</th><th>Selector</th><th>تاريخ</th></tr>
        </thead>
        <tbody>
        <?php foreach($elements as $el): ?>
          <tr>
            <td><?= $el['id'] ?></td>
            <td><?= htmlspecialchars($el['name']) ?></td>
            <td><code><?= htmlspecialchars($el['selector']) ?></code></td>
            <td><?= $el['created_at'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- الكود المولّد لوضعه في موقع المستخدم -->
  <div class="section">
    <h3>الكود المخصص (ضعه في موقعك)</h3>
    <?php if ($jsSnippet): ?>
      <div class="tracking-code">
        <pre><code><?= htmlspecialchars("<script>\n" . $jsSnippet . "\n</script>") ?></code></pre>
      </div>
    <?php endif; ?>
  </div>

  <!-- مودال تفاصيل النقرات -->
  <div id="detailsModal">
    <div class="modal-box">
      <div class="modal-hd">
        <h3>تفاصيل النقرات</h3>
        <button class="close" onclick="closeModal('detailsModal')">إغلاق</button>
      </div>
      <div class="modal-bd">
        <table id="clickDetails" style="width:100%">
          <thead><tr><th>معرّف الجلسة</th><th>الوقت</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- مودال ملخّص العنصر -->
  <div id="summaryModal">
    <div class="modal-box">
      <div class="modal-hd">
        <h3 id="summaryTitle">ملخّص العنصر</h3>
        <button class="close" onclick="closeModal('summaryModal')">إغلاق</button>
      </div>
      <div class="modal-bd">
        <div class="cards">
          <div class="card"><div class="v" id="sm_impr">0</div><div class="l">الظهور</div></div>
          <div class="card"><div class="v" id="sm_hover">0</div><div class="l">التحويم</div></div>
          <div class="card"><div class="v" id="sm_avg">0</div><div class="l">متوسط التحويم (ث)</div></div>
          <div class="card"><div class="v" id="sm_sub">0</div><div class="l">الإرسال</div></div>
          <div class="card"><div class="v" id="sm_clk">0</div><div class="l">النقرات</div></div>
          <div class="card"><div class="v" id="sm_uu">0</div><div class="l">مستخدمون فريدون</div></div>
          <div class="card"><div class="v" id="sm_ctr">0</div><div class="l">CTR %</div></div>
        </div>
        <p style="color:#4a5568;font-size:12px">
          * لاحتساب “متوسط التحويم” يجب أن يرسل الكود حدثي <code>hover_start</code> و<code>hover_end</code>—وقد فعلنا ذلك في السكربت المُحدّث أعلاه.
        </p>
      </div>
    </div>
  </div>

<script>
  const websiteId = <?= $website_id ?>;

  //helpers
  function n(v){ v = Number(v||0); return isFinite(v)? v:0; }
  function pct(v){ v = Number(v||0); return isFinite(v)? v.toFixed(2):'0.00'; }
  function escapeHtml(str=''){ const d=document.createElement('div'); d.textContent=str; return d.innerHTML; }
  function escapeAttr(str=''){ return (str+'').replace(/['"]/g,''); }

  function loadStats(){
    const days = document.getElementById('period').value;
    fetch(`?ajax=stats&id=${websiteId}&days=${days}`)
      .then(r=>r.json())
      .then(arr=>{
        window._statsCache = Array.isArray(arr)? arr: [];
        renderCards(window._statsCache);
      })
      .catch(()=>{
        document.getElementById('statsCards').innerHTML =
          `<div class="el-card">خطأ في الجلب</div>`;
      });
  }

  function renderCards(list){
    const box  = document.getElementById('statsCards');
    const q    = (document.getElementById('searchBox')?.value || '').trim().toLowerCase();
    const sort = document.getElementById('sortBy')?.value || 'clicks_desc';

    let data = (list || []).slice();

    // filter
    if (q) {
      data = data.filter(el =>
        (el.name||'').toLowerCase().includes(q) ||
        (el.selector||'').toLowerCase().includes(q)
      );
    }

    // sort
    data.sort((a,b)=>{
      switch (sort){
        case 'impr_desc': return n(b.total_impressions) - n(a.total_impressions);
        case 'ctr_desc' : return n(b.ctr_percent)      - n(a.ctr_percent);
        case 'name_asc' : return (a.name||'').localeCompare(b.name||'');
        default: // clicks_desc
          return n(b.total_clicks) - n(a.total_clicks);
      }
    });

    if (!data.length){
      box.innerHTML = `<div class="el-card">لا توجد بيانات</div>`;
      return;
    }

    // render
    box.innerHTML = data.map(el=>{
      const id     = Number(el.id);
      const name   = escapeHtml(el.name||'بدون اسم');
      const sel    = escapeHtml(el.selector||'');
      const type   = escapeHtml(el.selector_type||'');
      const impr   = n(el.total_impressions);
      const hov    = n(el.total_hovers);
      const sub    = n(el.total_submits);
      const clk    = n(el.total_clicks);
      const uu     = n(el.unique_users);
      const ctr    = pct(el.ctr_percent);

      return `
        <div class="el-card" data-eid="${id}">
          <div class="el-head">
            <div>
              <div class="el-title">${name}</div>
              <div class="el-sub"><span class="kbd">${sel || '-'}</span></div>
            </div>
            <div class="chip">${type || 'element'}</div>
          </div>

          <div class="el-metrics">
            <div class="el-metric"><div class="lbl">الظهور</div><div class="val">${impr}</div></div>
            <div class="el-metric"><div class="lbl">التحويم</div><div class="val">${hov}</div></div>
            <div class="el-metric"><div class="lbl">متوسط التحويم (ث)</div><div class="val" id="avg-${id}">—</div></div>
            <div class="el-metric"><div class="lbl">الإرسال</div><div class="val">${sub}</div></div>
            <div class="el-metric"><div class="lbl">النقرات</div><div class="val">${clk}</div></div>
            <div class="el-metric"><div class="lbl">مستخدمون فريدون</div><div class="val">${uu}</div></div>
            <div class="el-metric"><div class="lbl">CTR %</div><div class="val">${ctr}</div></div>
          </div>

          <div class="el-actions">
            <button class="btn-min" onclick="copyText('${escapeAttr(el.selector||'')}')">نسخ Selector</button>
            <button class="btn-min" onclick="showClicks(${id})">تفاصيل النقرات</button>
            <button class="btn-min" onclick="showSummary(${id}, '${escapeAttr(el.name||'')}')">ملخّص</button>
          </div>
        </div>
      `;
    }).join('');

    // Lazy-load متوسط التحويم لكل بطاقة
    warmAvgDurations();
  }

  function warmAvgDurations(){
    const days = document.getElementById('period').value;
    document.querySelectorAll('.el-card[data-eid]').forEach(card=>{
      const id = card.getAttribute('data-eid');
      const avgEl = document.getElementById(`avg-${id}`);
      if (!avgEl) return;
      // لو محجوز مسبقًا لا تعيد
      if (avgEl.dataset.loaded) return;

      fetch(`?ajax=element_summary&id=${websiteId}&element_id=${id}&days=${days}`)
        .then(r=>r.json())
        .then(s=>{
          avgEl.textContent = (s && typeof s.avg_hover_seconds !== 'undefined')
            ? Number(s.avg_hover_seconds||0).toFixed(2)
            : '0.00';
          avgEl.dataset.loaded = '1';
        })
        .catch(()=>{ avgEl.textContent = '0.00'; });
    });
  }

  function showClicks(elementId){
    const days = document.getElementById('period').value;
    fetch(`?ajax=clicks&id=${websiteId}&element_id=${elementId}&days=${days}`)
      .then(r=>r.json())
      .then(arr=>{
        const tbody = document.querySelector('#clickDetails tbody');
        tbody.innerHTML = (arr && arr.length)
          ? arr.map(c=>`
              <tr>
                <td>${escapeHtml(c.session_id)}</td>
                <td>${escapeHtml(c.occurred_at)}</td>
              </tr>
            `).join('')
          : '<tr><td colspan="2">لا توجد نقرات</td></tr>';
        openModal('detailsModal');
      });
  }

  function showSummary(elementId, nameText){
    const days = document.getElementById('period').value;
    document.getElementById('summaryTitle').textContent = 'ملخّص: ' + (nameText || ('#'+elementId));
    fetch(`?ajax=element_summary&id=${websiteId}&element_id=${elementId}&days=${days}`)
      .then(r=>r.json())
      .then(s=>{
        document.getElementById('sm_impr').textContent = s.impressions ?? 0;
        document.getElementById('sm_hover').textContent = s.hovers ?? 0;
        document.getElementById('sm_avg').textContent  = (s.avg_hover_seconds ?? 0).toFixed
          ? Number(s.avg_hover_seconds||0).toFixed(2)
          : s.avg_hover_seconds ?? 0;
        document.getElementById('sm_sub').textContent  = s.submits ?? 0;
        document.getElementById('sm_clk').textContent  = s.clicks ?? 0;
        document.getElementById('sm_uu').textContent   = s.unique_users ?? 0;
        document.getElementById('sm_ctr').textContent  = (s.ctr_percent ?? 0);
        openModal('summaryModal');
      });
  }

  function openModal(id){ document.getElementById(id).style.display='flex'; }
  function closeModal(id){ document.getElementById(id).style.display='none'; }
  function copyText(text){
    if (!text) return;
    navigator.clipboard.writeText(text).then(()=> {
      // خيار: اعمل Toast بسيط لو حابب
      console.debug('selector copied');
    });
  }

  document.addEventListener('DOMContentLoaded', loadStats);
</script>
</body>
</html>