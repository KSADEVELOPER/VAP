<?php
// abc.php — Interactive Heatmap
require_once 'config/database.php';
require_once 'classes/UserManager.php';
require_once 'classes/WebsiteManager.php';

$userManager    = new UserManager($db);
$websiteManager = new WebsiteManager($db);

$lang   = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';
$is_rtl = $lang === 'ar';

if (!$userManager->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id    = $_SESSION['user_id'];
$website_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$days       = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 30;

$website = $websiteManager->getWebsiteById($website_id, $user_id);
if (!$website) {
    header("Location: dashboard.php?lang=".$lang);
    exit;
}

// ---------- AJAX ENDPOINTS ----------
header_list:
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $start = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    switch ($_GET['ajax']) {
        case 'pages':
            // Top pages for website
            $rows = $db->fetchAll(
                "SELECT pv.page_url,
                        MAX(NULLIF(pv.page_title,'')) AS page_title,
                        COUNT(*) AS views,
                        ROUND(AVG(COALESCE(pv.time_on_page,0))) AS avg_time,
                        MAX(pv.view_time) AS last_view
                 FROM page_views pv
                 WHERE pv.website_id = ? AND pv.view_time >= ?
                 GROUP BY pv.page_url
                 ORDER BY views DESC
                 LIMIT 300",
                [$website_id, $start]
            );
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;

        case 'click_points':
            // inputs: page (urlencoded), device (Desktop/Mobile/Tablet/All), limit
            $page   = isset($_GET['page']) ? $_GET['page'] : '';
            $page   = urldecode($page);
            $device = isset($_GET['device']) ? $_GET['device'] : 'All';
            $limit  = isset($_GET['limit']) ? max(100, (int)$_GET['limit']) : 10000;

            $params = [$website_id, $page, $start];
            $sql = "SELECT c.click_x, c.click_y, c.page_url,
                           COALESCE(NULLIF(c.element_selector,''), NULL) AS element_selector,
                           COALESCE(NULLIF(c.element_text,''), NULL)    AS element_text,
                           c.clicked_at, s.device_type, s.id AS session_row_id
                    FROM clicks c
                    JOIN sessions s ON s.id = c.session_id
                    WHERE c.website_id = ? AND c.page_url = ? AND c.clicked_at >= ?";

            if (in_array($device, ['Desktop','Mobile','Tablet'], true)) {
                $sql .= " AND s.device_type = ?";
                $params[] = $device;
            }

            $sql .= " ORDER BY c.clicked_at DESC LIMIT {$limit}";
            $rows = $db->fetchAll($sql, $params);

            // quick stats
            $stats = [
                'total_clicks'   => count($rows),
                'unique_sessions'=> count(array_unique(array_map(fn($r)=>$r['session_row_id'], $rows))),
                'device_counts'  => ['Desktop'=>0,'Mobile'=>0,'Tablet'=>0,'Unknown'=>0]
            ];
            foreach ($rows as $r) {
                $d = $r['device_type'] ?: 'Unknown';
                if (!isset($stats['device_counts'][$d])) $d = 'Unknown';
                $stats['device_counts'][$d]++;
            }

            echo json_encode(['points'=>$rows, 'stats'=>$stats], JSON_UNESCAPED_UNICODE);
            break;

        case 'click_summary':
            $page = isset($_GET['page']) ? urldecode($_GET['page']) : '';
            $rows = $db->fetchAll(
                "SELECT 
                    CASE WHEN TRIM(COALESCE(c.element_selector,''))='' THEN '(no selector)' ELSE c.element_selector END AS selector,
                    COUNT(*) AS total_clicks,
                    MAX(c.clicked_at) AS last_click
                 FROM clicks c
                 WHERE c.website_id = ? AND c.page_url = ? AND c.clicked_at >= ?
                 GROUP BY selector
                 ORDER BY total_clicks DESC
                 LIMIT 80",
                [$website_id, $page, $start]
            );
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;

        case 'scroll_stats':
            $page = isset($_GET['page']) ? urldecode($_GET['page']) : '';
            $rows = $db->fetchAll(
                "SELECT (FLOOR(COALESCE(scroll_depth,0)/10)*10) AS bucket,
                        COUNT(*) AS views
                 FROM page_views
                 WHERE website_id = ? AND page_url = ? AND view_time >= ?
                 GROUP BY bucket
                 ORDER BY bucket",
                [$website_id, $page, $start]
            );
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;

        case 'hourly_clicks':
            $page = isset($_GET['page']) ? urldecode($_GET['page']) : '';
            $rows = $db->fetchAll(
                "SELECT HOUR(c.clicked_at) AS h, COUNT(*) AS clicks
                 FROM clicks c
                 WHERE c.website_id = ? AND c.page_url = ? AND c.clicked_at >= ?
                 GROUP BY h
                 ORDER BY h",
                [$website_id, $page, $start]
            );
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['error'=>'Unknown endpoint']);
    }
    exit;
}

// ------------- VIEW (HTML) -------------
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
    
    <!-- Heatmap.js loader with CDN fallbacks -->
<script>
  function loadHeatmapLib() {
    return new Promise((resolve, reject) => {
      if (window.h337) return resolve(); // جاهزة
      const cdns = [
        'https://unpkg.com/heatmap.js@2.0.5/build/heatmap.min.js',
        'https://cdn.jsdelivr.net/npm/heatmap.js@2.0.5/build/heatmap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/heatmap.js/2.0.5/heatmap.min.js'
      ];
      (function tryNext(i){
        if (i >= cdns.length) return reject(new Error('heatmap.js failed to load'));
        const s = document.createElement('script');
        s.src = cdns[i]; s.async = true;
        s.onload = () => resolve();
        s.onerror = () => { s.remove(); tryNext(i+1); };
        document.head.appendChild(s);
      })(0);
    });
  }
</script>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo $is_rtl ? ('خريطة الحرارة — ' . htmlspecialchars($website['name'])) : ('Heatmap — ' . htmlspecialchars($website['name'])); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if ($is_rtl): ?>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<?php else: ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<?php endif; ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/heatmap.js/2.0.5/heatmap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<style>
:root{
  --bg:#0f172a; --card:#0b1222; --muted:#94a3b8; --text:#e2e8f0; --accent:#22d3ee; --accent2:#a78bfa; --ok:#10b981; --warn:#f59e0b; --danger:#ef4444;
  --border:rgba(255,255,255,.08); --shadow:0 10px 30px rgba(0,0,0,.35);
  --radius:18px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0; background: radial-gradient(1200px 600px at 20% -10%, rgba(34,211,238,.07), transparent 40%),
            radial-gradient(1200px 600px at 120% 10%, rgba(167,139,250,.08), transparent 40%), var(--bg);
  color:var(--text); font-family: <?php echo $is_rtl ? 'Tajawal' : 'Inter'; ?>, system-ui, sans-serif;
}
.app{display:grid; grid-template-columns: 320px 1fr; gap:18px; padding:18px; min-height:100vh;}
.sidebar{
  background: linear-gradient(180deg, rgba(255,255,255,.03), transparent), rgba(255,255,255,.02);
  border:1px solid var(--border); border-radius:var(--radius); padding:16px; box-shadow: var(--shadow); position:sticky; top:18px; height: calc(100vh - 36px); overflow:auto;
}
.brand{display:flex; align-items:center; gap:10px; margin-bottom:14px}
.brand .logo{width:36px; height:36px; border-radius:12px; background:linear-gradient(135deg, var(--accent), var(--accent2)); display:grid; place-items:center; color:#001b2b; font-weight:800}
.brand .t{font-weight:800; letter-spacing:.4px}
.website-card{
  background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:12px;
}
.control{margin-top:12px; display:grid; gap:10px}
.label{font-size:12px; color:var(--muted); margin-bottom:4px}
.select, .input, .range{
  width:100%; background:#0a1020; border:1px solid var(--border); color:var(--text); padding:10px 12px; border-radius:10px; outline:none;
}
.btn{display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:#0a1020; color:var(--text); cursor:pointer}
.btn:hover{border-color:rgba(255,255,255,.18); transform:translateY(-1px)}
.btn.primary{background: linear-gradient(135deg, var(--accent), var(--accent2)); color:#081018; border:none; font-weight:700}
.stat-grid{display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:12px}
.stat{background:#0a1020; border:1px solid var(--border); border-radius:12px; padding:10px}
.stat .v{font-weight:800; font-size:18px}
.list{margin-top:12px}
.page-item{
  display:flex; gap:10px; align-items:center; padding:10px; border:1px solid var(--border); border-radius:10px; background:#0b1328; margin-bottom:8px; cursor:pointer; transition:.2s;
}
.page-item:hover{transform:translateX(<?php echo $is_rtl?'-':'+';?>4px); border-color:rgba(255,255,255,.18)}
.page-item.active{outline:2px solid var(--accent)}
.page-item small{color:var(--muted); display:block}

.main{
  display:grid; grid-template-rows: auto 1fr auto; gap:14px;
}
.topbar{
  background: linear-gradient(180deg, rgba(255,255,255,.03), transparent), rgba(255,255,255,.02);
  border:1px solid var(--border); border-radius:var(--radius); padding:12px; box-shadow: var(--shadow); display:flex; align-items:center; justify-content:space-between; gap:10px;
}
.kpis{display:flex; gap:10px; flex-wrap:wrap}
.kpi{background:#0a1020; border:1px solid var(--border); border-radius:12px; padding:10px 12px; display:flex; align-items:center; gap:8px}
.kpi i{opacity:.9}
.kpi .num{font-weight:800; font-size:16px}
.stage{
  background:#040816; border:1px solid var(--border); border-radius:16px; position:relative; overflow:hidden; min-height:520px;
}
.stage .frame-wrap{position:absolute; inset:0; display:grid}
.stage iframe{width:100%; height:100%; border:0; background:white}
.overlay{
  position:absolute; inset:0; pointer-events:none; /* pass clicks to iframe */
}
.crosshair{position:absolute; width:1px; height:1px; inset:0; pointer-events:none}
.crosshair:before, .crosshair:after{
  content:""; position:absolute; background:rgba(255,255,255,.22);
}
.crosshair:before{top:0; bottom:0; width:1px}
.crosshair:after{left:0; right:0; height:1px}
.cursor-badge{
  position:absolute; transform:translate(10px,10px); padding:6px 8px; border-radius:8px; background:rgba(2,6,23,.85); border:1px solid var(--border); font-size:12px; pointer-events:none; display:none;
}
.bottom{
  display:grid; grid-template-columns: 2fr 1fr; gap:14px;
}
.card{background:#0a1020; border:1px solid var(--border); border-radius:14px; padding:12px}
.table{width:100%; border-collapse:collapse; font-size:13px}
.table th, .table td{border-bottom:1px solid var(--border); padding:8px; text-align:<?php echo $is_rtl?'right':'left'; ?>}
.badge{padding:4px 8px; border-radius:20px; font-size:12px; background:rgba(255,255,255,.06); border:1px solid var(--border)}
.flex{display:flex; gap:8px; align-items:center; flex-wrap:wrap}
.slider{display:flex; align-items:center; gap:10px}
hr.sep{border:none; border-top:1px solid var(--border); margin:10px 0}
.toast{
  position:fixed; <?php echo $is_rtl?'left':'right'; ?>:18px; bottom:18px; background:#061022; border:1px solid var(--border); border-radius:12px; padding:10px 12px; box-shadow:var(--shadow); display:none;
}
.empty{padding:18px; text-align:center; color:var(--muted)}
</style>
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="logo"><i class="fa-solid fa-fire"></i></div>
      <div class="t"><?php echo $is_rtl?'خريطة الحرارة المتقدمة':'Advanced Heatmap'; ?></div>
    </div>

    <div class="website-card">
      <div class="label"><?php echo $is_rtl?'الموقع':'Website'; ?></div>
      <div style="font-weight:800"><?php echo htmlspecialchars($website['name']); ?></div>
      <small style="color:var(--muted)"><?php echo htmlspecialchars($website['domain']); ?></small>
    </div>

    <div class="control">
      <div>
        <div class="label"><i class="fa-regular fa-calendar"></i> <?php echo $is_rtl?'الفترة':'Period'; ?></div>
        <select id="days" class="select">
          <option value="7"  <?php echo $days==7?'selected':''; ?>><?php echo $is_rtl?'آخر 7 أيام':'Last 7 days'; ?></option>
          <option value="30" <?php echo $days==30?'selected':''; ?>><?php echo $is_rtl?'آخر 30 يوم':'Last 30 days'; ?></option>
          <option value="90" <?php echo $days==90?'selected':''; ?>><?php echo $is_rtl?'آخر 3 أشهر':'Last 3 months'; ?></option>
          <option value="365"<?php echo $days==365?'selected':''; ?>><?php echo $is_rtl?'آخر سنة':'Last year'; ?></option>
        </select>
      </div>

      <div>
        <div class="label"><i class="fa-solid fa-display"></i> <?php echo $is_rtl?'الجهاز':'Device'; ?></div>
        <select id="device" class="select">
          <option>All</option>
          <option>Desktop</option>
          <option>Mobile</option>
          <option>Tablet</option>
        </select>
      </div>

      <div>
        <div class="label"><i class="fa-solid fa-sliders"></i> <?php echo $is_rtl?'النمط والإعدادات':'Mode & Settings'; ?></div>
        <div class="flex">
          <button id="modeHeat" class="btn primary"><i class="fa-solid fa-fire"></i> <?php echo $is_rtl?'Heat':'Heat'; ?></button>
          <button id="modeDots" class="btn"><i class="fa-regular fa-circle-dot"></i> <?php echo $is_rtl?'Dots':'Dots'; ?></button>
        </div>
        <div class="slider">
          <span style="width:110px"><?php echo $is_rtl?'نطاق الحرارة':'Radius'; ?></span>
          <input id="radius" type="range" class="range" min="10" max="80" value="35">
        </div>
        <div class="slider">
          <span style="width:110px"><?php echo $is_rtl?'كثافة/شفافية':'Opacity'; ?></span>
          <input id="opacity" type="range" class="range" min="10" max="100" value="70">
        </div>
      </div>

      <hr class="sep">

      <div class="label"><i class="fa-solid fa-file-lines"></i> <?php echo $is_rtl?'الصفحات (اختر صفحة)':'Pages (pick a page)'; ?></div>
      <input id="pageSearch" class="input" placeholder="<?php echo $is_rtl?'ابحث بعنوان أو رابط':'Search by title or URL'; ?>">
      <div id="pagesList" class="list">
        <div class="empty"><?php echo $is_rtl?'جاري التحميل...':'Loading...'; ?></div>
      </div>
    </div>
  </aside>

  <section class="main">
    <div class="topbar">
      <div class="kpis">
        <div class="kpi"><i class="fa-solid fa-hand-pointer"></i> <span class="num" id="kTotal">0</span></div>
        <div class="kpi"><i class="fa-solid fa-users"></i> <span class="num" id="kSessions">0</span></div>
        <div class="kpi"><i class="fa-solid fa-display"></i> <span class="num" id="kDesktop">0</span></div>
        <div class="kpi"><i class="fa-solid fa-mobile-screen-button"></i> <span class="num" id="kMobile">0</span></div>
        <div class="kpi"><i class="fa-solid fa-tablet-screen-button"></i> <span class="num" id="kTablet">0</span></div>
      </div>

      <div class="flex">
        <button id="replayBtn" class="btn"><i class="fa-solid fa-play"></i> <?php echo $is_rtl?'تشغيل':'Replay'; ?></button>
        <button id="refreshBtn" class="btn"><i class="fa-solid fa-rotate"></i> <?php echo $is_rtl?'تحديث':'Refresh'; ?></button>
      </div>
    </div>

    <div class="stage" id="stage">
      <div class="frame-wrap">
        <iframe id="frame" src="about:blank" referrerpolicy="no-referrer"></iframe>
      </div>
      <div class="overlay" id="overlay"></div>
      <div class="crosshair" id="cross"></div>
      <div class="cursor-badge" id="badge"></div>
    </div>

    <div class="bottom">
      <div class="card">
        <div class="flex" style="justify-content:space-between">
          <div class="label"><i class="fa-solid fa-list"></i> <?php echo $is_rtl?'ملخّص العناصر الأكثر نقرًا':'Top Clicked Selectors'; ?></div>
          <div class="label" id="pageTitleLabel"></div>
        </div>
        <div style="overflow:auto; max-height:240px">
          <table class="table" id="selectorsTable">
            <thead>
              <tr>
                <th><?php echo $is_rtl?'المحدد (Selector)':'Selector'; ?></th>
                <th><?php echo $is_rtl?'النقرات':'Clicks'; ?></th>
                <th><?php echo $is_rtl?'آخر نقرة':'Last Click'; ?></th>
              </tr>
            </thead>
            <tbody><tr><td colspan="3" class="empty"><?php echo $is_rtl?'— لا بيانات —':'— No Data —'; ?></td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="label"><i class="fa-solid fa-scroll"></i> <?php echo $is_rtl?'شدة التمرير':'Scroll Heat'; ?></div>
        <canvas id="scrollChart" height="180"></canvas>
        <hr class="sep">
        <div class="label"><i class="fa-regular fa-clock"></i> <?php echo $is_rtl?'نقرات بالساعة':'Hourly Clicks'; ?></div>
        <canvas id="hourlyChart" height="140"></canvas>
      </div>
    </div>
  </section>
</div>

<div class="toast" id="toast">
  <i class="fa-solid fa-circle-info"></i>
  <span style="margin:0 8px"><?php echo $is_rtl?'التنقل داخل الإطار قد لا يتزامن تلقائيًا بسبب قيود الأمان للمتصفحات. اختر الصفحة من القائمة اليسرى.':'In-iframe navigation may not auto-sync due to browser security. Pick pages from the left list.'; ?></span>
  <button class="btn" onclick="this.parentElement.style.display='none'"><i class="fa-solid fa-xmark"></i></button>
</div>

<script>
const isRTL = <?php echo $is_rtl?'true':'false'; ?>;
const websiteId = <?php echo (int)$website_id; ?>;
let currentDays = <?php echo (int)$days; ?>;
let currentDevice = 'All';
let currentPageUrl = null;
let mode = 'heat'; // 'heat' | 'dots'
let heatmap, overlayEl, frameEl, crossEl, badgeEl;
let replayTimer = null;

const t = (ar,en)=> isRTL?ar:en;

function fmt(n){ return n.toLocaleString(isRTL?'ar-SA':'en-US'); }
function esc(s){ const d=document.createElement('div'); d.textContent = s??''; return d.innerHTML; }

document.addEventListener('DOMContentLoaded', async () => {
  try { await loadHeatmapLib(); } 
  catch (e) { console.error(e); alert('تعذّر تحميل مكتبة heatmap.js'); return; }
  init();
});
function init(){
  overlayEl = document.getElementById('overlay');
  frameEl   = document.getElementById('frame');
  crossEl   = document.getElementById('cross');
  badgeEl   = document.getElementById('badge');

  heatmap = h337.create({
    container: overlayEl,
    radius: +document.getElementById('radius').value,
    maxOpacity: +document.getElementById('opacity').value/100,
    minOpacity: 0.05,
    blur: 0.85
  });

  document.getElementById('radius').addEventListener('input', e=>{
    heatmap.configure({radius:+e.target.value});
    renderHeat();
  });
  document.getElementById('opacity').addEventListener('input', e=>{
    heatmap.configure({maxOpacity:+e.target.value/100});
    renderHeat();
  });

  document.getElementById('modeHeat').onclick = ()=>{ mode='heat'; document.getElementById('modeHeat').classList.add('primary'); document.getElementById('modeDots').classList.remove('primary'); renderHeat(); };
  document.getElementById('modeDots').onclick = ()=>{ mode='dots'; document.getElementById('modeDots').classList.add('primary'); document.getElementById('modeHeat').classList.remove('primary'); renderHeat(); };

  document.getElementById('days').addEventListener('change', e=>{
    currentDays = +e.target.value; loadPages();
  });
  document.getElementById('device').addEventListener('change', e=>{
    currentDevice = e.target.value; if(currentPageUrl) loadPageData(currentPageUrl);
  });

  document.getElementById('refreshBtn').onclick = ()=>{ if(currentPageUrl) loadPageData(currentPageUrl,true); };
  document.getElementById('replayBtn').onclick  = toggleReplay;

  overlayEl.addEventListener('mousemove', onMouseMove);
  overlayEl.addEventListener('mouseleave', ()=>{ crossEl.style.display='none'; badgeEl.style.display='none'; });
  overlayEl.addEventListener('mouseenter', ()=>{ crossEl.style.display='block'; });

  window.addEventListener('resize', ()=>{ renderHeat(); });

  loadPages();
  setTimeout(()=>document.getElementById('toast').style.display='flex', 1200);
}

let pagesCache=[];
async function loadPages(){
  const list = document.getElementById('pagesList');
  list.innerHTML = '<div class="empty">'+t('جاري التحميل...','Loading...')+'</div>';

  const res = await fetch(`?ajax=pages&id=${websiteId}&days=${currentDays}`);
  const data = await res.json();
  pagesCache = data || [];

  const search = document.getElementById('pageSearch');
  const render = ()=>{
    const q = (search.value||'').toLowerCase();
    const items = pagesCache.filter(p=>{
      return (p.page_title||'').toLowerCase().includes(q) || (p.page_url||'').toLowerCase().includes(q);
    });
    list.innerHTML = items.length? '' : `<div class="empty">${t('لا نتائج','No results')}</div>`;
    items.slice(0,200).forEach((p,i)=>{
      const el = document.createElement('div');
      el.className = 'page-item';
      el.dataset.url = p.page_url;
      el.innerHTML = `
        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:grid;place-items:center"><i class="fa-regular fa-file-lines"></i></div>
        <div style="flex:1">
          <div style="font-weight:700">${esc(p.page_title || t('بدون عنوان','Untitled'))}</div>
          <small>${esc(p.page_url)}</small>
        </div>
        <div class="badge">${fmt(+p.views||0)} ${t('زيارة','views')}</div>
      `;
      el.onclick = ()=>{
        document.querySelectorAll('.page-item').forEach(e=>e.classList.remove('active'));
        el.classList.add('active');
        pickPage(p.page_url, p.page_title);
      };
      list.appendChild(el);
      if(i===0 && !currentPageUrl){
        el.classList.add('active');
        pickPage(p.page_url, p.page_title);
      }
    });
  };
  search.oninput = render;
  render();
}

function pickPage(url, title){
  currentPageUrl = url;
  document.getElementById('pageTitleLabel').textContent = title? title : t('بدون عنوان','Untitled');
  frameEl.src = url; // note: cannot read later due to cross-origin
  loadPageData(url, true);
}

let rawPoints = [];
let scaledPoints = [];
let summaryRows = [];
let scrollRows = [];
let hourlyRows = [];

async function loadPageData(url, spin=false){
  if(spin) overlayEl.style.opacity = .4;
  try{
    const qs = `&id=${websiteId}&days=${currentDays}&page=${encodeURIComponent(url)}&device=${encodeURIComponent(currentDevice)}`;
    const [clicksRes, summaryRes, scrollRes, hourlyRes] = await Promise.all([
      fetch(`?ajax=click_points${qs}`), fetch(`?ajax=click_summary${qs}`), fetch(`?ajax=scroll_stats${qs}`), fetch(`?ajax=hourly_clicks${qs}`)
    ]);

    const clicks = await clicksRes.json();
    rawPoints   = clicks.points || [];
    updateKpis(clicks.stats || {total_clicks:0, unique_sessions:0, device_counts:{}});
    summaryRows = await summaryRes.json() || [];
    scrollRows  = await scrollRes.json() || [];
    hourlyRows  = await hourlyRes.json() || [];

    buildSelectorsTable();
    drawScrollChart();
    drawHourlyChart();
    renderHeat(true);
  }catch(e){
    console.error(e);
    overlayEl.innerHTML = `<div class="empty">${t('خطأ في تحميل البيانات','Failed to load data')}</div>`;
  }finally{
    overlayEl.style.opacity = 1;
  }
}

function updateKpis(st){
  document.getElementById('kTotal').textContent    = fmt(st.total_clicks||0);
  document.getElementById('kSessions').textContent = fmt(st.unique_sessions||0);
  document.getElementById('kDesktop').textContent  = fmt((st.device_counts||{}).Desktop||0);
  document.getElementById('kMobile').textContent   = fmt((st.device_counts||{}).Mobile||0);
  document.getElementById('kTablet').textContent   = fmt((st.device_counts||{}).Tablet||0);
}

function buildSelectorsTable(){
  const tb = document.querySelector('#selectorsTable tbody');
  if(!summaryRows.length){ tb.innerHTML = `<tr><td colspan="3" class="empty">${t('— لا بيانات —','— No Data —')}</td></tr>`; return; }
  tb.innerHTML = summaryRows.map(r=>`
    <tr>
      <td><code>${esc(r.selector)}</code></td>
      <td><b>${fmt(+r.total_clicks||0)}</b></td>
      <td><small style="color:var(--muted)">${esc(r.last_click)}</small></td>
    </tr>
  `).join('');
}

let scrollChart, hourlyChart;
function drawScrollChart(){
  const el = document.getElementById('scrollChart');
  const labels = []; const vals = [];
  if(scrollRows.length){
    scrollRows.forEach(b=>{labels.push((+b.bucket||0)+'%'); vals.push(+b.views||0);});
  }else{
    for(let i=0;i<=100;i+=10){labels.push(i+'%'); vals.push(0);}
  }
  if(scrollChart) scrollChart.destroy();
  scrollChart = new Chart(el, {
    type:'bar',
    data:{labels, datasets:[{label:t('مشاهدات','Views'), data:vals}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
  });
}
function drawHourlyChart(){
  const el = document.getElementById('hourlyChart');
  const labels = Array.from({length:24}, (_,i)=> (isRTL? i+':00' : (i<10?'0':'')+i+':00'));
  const vals = Array(24).fill(0);
  (hourlyRows||[]).forEach(r=>{ const h = +r.h; if(h>=0 && h<24) vals[h] = +r.clicks||0; });
  if(hourlyChart) hourlyChart.destroy();
  hourlyChart = new Chart(el, {
    type:'line',
    data:{labels, datasets:[{label:t('النقرات','Clicks'), data:vals}]},
    options:{plugins:{legend:{display:false}}, elements:{point:{radius:0}}, scales:{y:{beginAtZero:true}}}
  });
}

// -------- Heatmap Rendering --------
function computeScale(){
  if(!rawPoints.length) return {sx:1, sy:1};
  // use 95th percentile as virtual base to reduce outliers
  const xs = rawPoints.map(p=>+p.click_x||0).sort((a,b)=>a-b);
  const ys = rawPoints.map(p=>+p.click_y||0).sort((a,b)=>a-b);
  const px = xs[Math.floor(xs.length*0.95)] || xs[xs.length-1] || 1000;
  const py = ys[Math.floor(ys.length*0.95)] || ys[ys.length-1] || 1000;
  const w = overlayEl.clientWidth || 1200;
  const h = overlayEl.clientHeight || 800;
  return {sx: w/(px||1), sy: h/(py||1)};
}

function renderHeat(rebuild=false){
  if(rebuild){
    // transform raw->scaled with current overlay size
    const {sx, sy} = computeScale();
    scaledPoints = rawPoints.map(p=>({
      x: Math.max(0, Math.min(Math.round((+p.click_x||0)*sx), overlayEl.clientWidth)),
      y: Math.max(0, Math.min(Math.round((+p.click_y||0)*sy), overlayEl.clientHeight)),
      value: 1,
      meta: p
    }));
  }
  overlayEl.innerHTML = ''; // clear dots layer if any
  if(mode==='heat'){
    heatmap.setData({ max: Math.max(1, Math.round(scaledPoints.length*0.05)), data: scaledPoints });
  } else {
    heatmap.setData({max:1,data:[]});
    // draw dots (SVG for crisp)
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width','100%'); svg.setAttribute('height','100%'); svg.style.position='absolute';
    scaledPoints.forEach(p=>{
      const c = document.createElementNS('http://www.w3.org/2000/svg','circle');
      c.setAttribute('cx', p.x); c.setAttribute('cy', p.y); c.setAttribute('r','5');
      c.setAttribute('fill','rgba(34,211,238,0.8)');
      c.setAttribute('stroke','rgba(255,255,255,0.5)'); c.setAttribute('stroke-width','1');
      svg.appendChild(c);
    });
    overlayEl.appendChild(svg);
  }
}

function onMouseMove(e){
  const rect = overlayEl.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  crossEl.style.display='block';
  crossEl.style.left = x+'px';
  crossEl.style.top  = y+'px';
  crossEl.style.width='1px'; crossEl.style.height='1px';

  // Compute local density
  let count=0;
  for(let i=0;i<scaledPoints.length;i++){
    const dx = scaledPoints[i].x - x;
    const dy = scaledPoints[i].y - y;
    if((dx*dx + dy*dy) <= 35*35) count++;
  }
  badgeEl.style.display='block';
  badgeEl.style.left = (x+10)+'px';
  badgeEl.style.top  = (y+10)+'px';
  badgeEl.innerHTML = `<i class="fa-solid fa-bullseye"></i> ${t('الكثافة:','Density:')} <b>${fmt(count)}</b>`;
}

// -------- Replay --------
function toggleReplay(){
  const btn = document.getElementById('replayBtn');
  if(replayTimer){ clearInterval(replayTimer); replayTimer=null; btn.innerHTML = `<i class="fa-solid fa-play"></i> ${t('تشغيل','Replay')}`; renderHeat(true); return; }
  if(!scaledPoints.length) return;
  btn.innerHTML = `<i class="fa-solid fa-stop"></i> ${t('إيقاف','Stop')}`;
  heatmap.setData({max:1,data:[]});
  let i=0;
  replayTimer = setInterval(()=>{
    if(i>=scaledPoints.length){ clearInterval(replayTimer); replayTimer=null; btn.innerHTML = `<i class="fa-solid fa-play"></i> ${t('تشغيل','Replay')}`; return; }
    heatmap.addData({x: scaledPoints[i].x, y: scaledPoints[i].y, value: 1});
    i+=1;
  }, Math.max(2, Math.floor(1500/Math.max(1, scaledPoints.length))));
}
</script>
</body>
</html>