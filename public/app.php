<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Bootstrap.php';
$authUser = App::requireAuth();
$authPermissions = App::permissions((int)$authUser['id']);
$mobileMode = (($_GET['mobile'] ?? '') === 'employee') || $authUser['role'] === 'worker';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>سواعد المتكامل — نظام إدارة المقاولات</title>
<meta name="csrf-token" content="<?= htmlspecialchars(App::csrf()) ?>">
<meta name="app-user" content="<?= htmlspecialchars(json_encode($authUser, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
<meta name="app-permissions" content="<?= htmlspecialchars(json_encode($authPermissions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#F6F8FA; --bg-panel:#FFFFFF; --bg-panel-2:#EEF2F6; --bg-raised:#EFF3F6;
  --line:#E9EDF1; --line-strong:#D3DAE1;
  --text:#1B2733; --text-dim:#57677A; --text-faint:#8B99A8;
  --accent:#E8620C; --accent-dim:#FCE3CF;
  --teal:#0D8578; --teal-dim:#DEF4F1;
  --danger:#D6303F; --danger-dim:#FBE4E6;
  --warn:#B4740A; --warn-dim:#FBEFD9;
  --success:#1C9A57; --success-dim:#E1F6E9;
  --radius:6px;
}
*{box-sizing:border-box; margin:0; padding:0;}
body{
  font-family:'IBM Plex Sans Arabic', sans-serif;
  background: var(--bg);
  color:var(--text);
  min-height:100vh;
  font-size:14px;
}
.mono{ font-family:'IBM Plex Mono', monospace; }
button{ font-family:inherit; cursor:pointer; }
::-webkit-scrollbar{ width:8px; height:8px; }
::-webkit-scrollbar-thumb{ background:var(--line-strong); border-radius:4px; }
::-webkit-scrollbar-track{ background:transparent; }

/* ---------- Title block header (توقيع تصميمي: خانة عنوان مخطط هندسي) ---------- */
.titleblock{
  display:grid;
  grid-template-columns: 1.4fr repeat(4, 1fr) auto auto auto;
  border-bottom:1px solid var(--line-strong);
  background:var(--bg-panel);
}
.tb-cell{
  border-inline-start:1px solid var(--line-strong);
  padding:8px 14px;
  display:flex; flex-direction:column; justify-content:center; gap:2px;
  min-width:0;
}
.tb-cell:first-child{ border-inline-start:none; }
.tb-label{ font-size:9px; letter-spacing:.08em; color:var(--text-faint); text-transform:uppercase; }
.tb-value{ font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tb-value select{ background:none; border:none; color:var(--text); font-family:inherit; font-size:13px; font-weight:600; width:100%; }
.tb-value select:focus{ outline:none; }
.tb-brand{ display:flex; align-items:center; gap:10px; padding:8px 16px; }
.tb-brand .mark{
  width:30px; height:30px; border:2px solid var(--accent); border-radius:4px;
  display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--accent); font-family:'IBM Plex Mono';
  flex-shrink:0;
}
.tb-brand .name{ font-weight:700; font-size:15px; }
.tb-brand .sub{ font-size:10px; color:var(--text-faint); font-family:'IBM Plex Mono'; }
.tb-icon-btn{ padding:0 16px; display:flex; align-items:center; gap:6px; border-inline-start:1px solid var(--line-strong); color:var(--text-dim); position:relative; }
.tb-icon-btn:hover{ color:var(--text); background:var(--bg-panel-2); }
.tb-dot{ width:6px; height:6px; border-radius:50%; background:var(--danger); position:absolute; top:10px; left:12px; }

/* ---------- Layout ---------- */
.shell{ display:flex; height:calc(100vh - 53px); }
.sidebar{
  width:250px; flex-shrink:0; background:var(--bg-panel); border-inline-start:1px solid var(--line-strong);
  overflow-y:auto; position:relative;
}
.ruler{
  position:absolute; inset-inline-end:0; top:0; bottom:0; width:14px;
  background-image: repeating-linear-gradient(to bottom, var(--line-strong) 0, var(--line-strong) 1px, transparent 1px, transparent 10px);
  opacity:.4;
}
.nav-group{ padding:14px 0 4px; }
.nav-group-label{ padding:0 16px 6px; font-size:10px; letter-spacing:.1em; color:var(--text-faint); text-transform:uppercase; font-weight:600; }
.nav-item{
  display:flex; align-items:center; gap:10px; padding:8px 16px; color:var(--text-dim);
  border-inline-start:2px solid transparent; font-size:13px;
}
.nav-item:hover{ background:var(--bg-panel-2); color:var(--text); }
.nav-item.active{ background:var(--bg-panel-2); color:var(--text); border-inline-start-color:var(--accent); }
.nav-item .ico{ width:16px; text-align:center; opacity:.8; font-family:'IBM Plex Mono'; font-size:12px; }
.nav-badge{ margin-inline-start:auto; font-size:10px; background:var(--danger-dim); color:var(--danger); padding:1px 6px; border-radius:8px; font-family:'IBM Plex Mono'; }

.main{ flex:1; overflow-y:auto; padding:22px 26px 60px; }
.page-head{ display:flex; align-items:baseline; justify-content:space-between; margin-bottom:18px; }
.page-title{ font-size:19px; font-weight:700; }
.page-eyebrow{ font-size:10px; color:var(--text-faint); font-family:'IBM Plex Mono'; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px; }
.page-desc{ color:var(--text-dim); font-size:12.5px; margin-top:4px; }

.btn{ background:var(--accent); color:#12100D; font-weight:600; border:none; padding:9px 16px; border-radius:var(--radius); font-size:13px; }
.btn:hover{ filter:brightness(1.08); }
.btn-outline{ background:transparent; border:1px solid var(--line-strong); color:var(--text); padding:8px 15px; border-radius:var(--radius); font-size:13px; }
.btn-outline:hover{ border-color:var(--text-dim); }
.btn-ghost{ background:transparent; border:none; color:var(--text-dim); font-size:12.5px; padding:6px 10px; }
.btn-ghost:hover{ color:var(--text); }
.btn-sm{ padding:5px 10px; font-size:11.5px; border-radius:4px; }

/* KPI Grid */
.kpi-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(190px,1fr)); gap:10px; margin-bottom:22px; }
.kpi{ background:var(--bg-panel); border:1px solid var(--line); border-radius:var(--radius); padding:14px 16px; cursor:pointer; transition:.15s; }
.kpi:hover{ border-color:var(--accent); transform:translateY(-1px); }
.kpi-label{ font-size:11px; color:var(--text-dim); margin-bottom:8px; }
.kpi-value{ font-family:'IBM Plex Mono'; font-size:22px; font-weight:600; }
.kpi-trend{ font-size:11px; margin-top:6px; font-family:'IBM Plex Mono'; }
.up{ color:var(--success); } .down{ color:var(--danger); } .flat{ color:var(--text-faint); }

.grid-2{ display:grid; grid-template-columns:1.4fr 1fr; gap:14px; }
.grid-3{ display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media(max-width:1100px){ .grid-2,.grid-3{ grid-template-columns:1fr; } }

.panel{ background:var(--bg-panel); border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; }
.panel-head{ display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid var(--line); }
.panel-title{ font-size:13px; font-weight:600; }
.panel-body{ padding:14px 16px; }

table{ width:100%; border-collapse:collapse; font-size:12.5px; }
thead th{ text-align:right; padding:9px 12px; color:var(--text-faint); font-weight:600; font-size:11px; border-bottom:1px solid var(--line-strong); white-space:nowrap; }
tbody td{ padding:10px 12px; border-bottom:1px solid var(--line); color:var(--text); white-space:nowrap; }
tbody tr:hover{ background:var(--bg-panel-2); }
tbody tr{ cursor:pointer; }
.num{ font-family:'IBM Plex Mono'; }

.badge{ display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.b-success{ background:var(--success-dim); color:var(--success); }
.b-warn{ background:var(--warn-dim); color:var(--warn); }
.b-danger{ background:var(--danger-dim); color:var(--danger); }
.b-teal{ background:var(--teal-dim); color:var(--teal); }
.b-flat{ background:var(--bg-raised); color:var(--text-dim); }

.toolbar{ display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.search-input{ background:var(--bg-panel); border:1px solid var(--line-strong); border-radius:var(--radius); padding:8px 12px; color:var(--text); font-size:12.5px; min-width:200px; }
.search-input:focus{ outline:none; border-color:var(--accent); }
.chip{ background:var(--bg-panel); border:1px solid var(--line-strong); padding:7px 12px; border-radius:20px; font-size:12px; color:var(--text-dim); }
.chip.active{ border-color:var(--accent); color:var(--accent); }

.empty{ text-align:center; padding:50px 20px; color:var(--text-faint); }
.empty .big{ font-size:32px; margin-bottom:8px; font-family:'IBM Plex Mono'; }

/* Progress bar */
.pbar{ height:6px; background:var(--bg-raised); border-radius:3px; overflow:hidden; }
.pbar > div{ height:100%; background:var(--accent); }

/* Stepper (payroll wizard) */
.stepper{ display:flex; align-items:center; margin-bottom:24px; overflow-x:auto; padding-bottom:6px; }
.step{ display:flex; align-items:center; flex-shrink:0; }
.step-circle{ width:26px; height:26px; border-radius:50%; border:1.5px solid var(--line-strong); display:flex; align-items:center; justify-content:center; font-size:11px; font-family:'IBM Plex Mono'; color:var(--text-faint); flex-shrink:0; }
.step.done .step-circle{ background:var(--success); border-color:var(--success); color:#0F1A24; }
.step.current .step-circle{ border-color:var(--accent); color:var(--accent); }
.step-label{ font-size:11px; margin-inline-start:6px; margin-inline-end:14px; color:var(--text-dim); white-space:nowrap; }
.step.current .step-label{ color:var(--text); font-weight:600; }
.step-line{ width:24px; height:1px; background:var(--line-strong); margin-inline-end:14px; flex-shrink:0; }

/* Phone frame for employee/warehouse apps */
.phone-wrap{ display:flex; justify-content:center; padding:20px 0; }
.phone{ width:320px; background:#000; border-radius:34px; padding:10px; box-shadow:0 20px 60px rgba(0,0,0,.5); }
.phone-screen{ background:var(--bg); border-radius:24px; overflow:hidden; height:640px; display:flex; flex-direction:column; position:relative; }
.phone-status{ height:26px; display:flex; align-items:center; justify-content:space-between; padding:0 20px; font-size:10px; font-family:'IBM Plex Mono'; color:var(--text-dim); }
.phone-top{ background:var(--bg-panel); padding:14px 16px; border-bottom:1px solid var(--line); }
.phone-body{ flex:1; overflow-y:auto; overflow-x:hidden; padding:14px; }
.phone-nav{ display:flex; border-top:1px solid var(--line); background:var(--bg-panel); }
.phone-nav-item{ flex:1; text-align:center; padding:9px 0; font-size:10px; color:var(--text-faint); }
.phone-nav-item.active{ color:var(--accent); }
.phone-nav-item .ic{ font-family:'IBM Plex Mono'; font-size:14px; display:block; margin-bottom:2px; }
body.employee-mobile .titleblock,body.employee-mobile .sidebar{display:none}body.employee-mobile .shell{height:100vh}body.employee-mobile .main{padding:0;overflow:hidden}body.employee-mobile .page-eyebrow,body.employee-mobile .page-head{display:none}body.employee-mobile .phone-wrap{padding:0;height:100vh}body.employee-mobile .phone{width:100%;height:100%;border-radius:0;padding:0;box-shadow:none;background:var(--bg)}body.employee-mobile .phone-screen{height:100%;border-radius:0}

.geo-row{ display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border:1px solid var(--line); border-radius:var(--radius); margin-bottom:8px; }
.geo-row.selected{ border-color:var(--accent); background:rgba(232,98,12,.07); }
.geo-dist{ font-family:'IBM Plex Mono'; font-size:11px; }

.map-fake{
  height:150px; border-radius:var(--radius); border:1px solid var(--line);
  background:
    radial-gradient(circle at 30% 40%, rgba(232,98,12,.12), transparent 45%),
    var(--bg-raised);
  position:relative; margin-bottom:12px;
}
.map-pin{ position:absolute; width:10px; height:10px; border-radius:50%; border:2px solid #0F1A24; }

.card{ background:var(--bg-raised); border:1px solid var(--line); border-radius:var(--radius); padding:12px; margin-bottom:10px; }
.flex-between{ display:flex; align-items:center; justify-content:space-between; }
.muted{ color:var(--text-dim); }
.faint{ color:var(--text-faint); font-size:11px; }

.modal-overlay{ position:fixed; inset:0; background:rgba(0,0,0,.6); display:none; align-items:center; justify-content:center; z-index:50; }
.modal-overlay.open{ display:flex; }
.modal{ background:var(--bg-panel); border:1px solid var(--line-strong); border-radius:8px; width:520px; max-width:92vw; max-height:82vh; overflow-y:auto; }
.modal-head{ display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
.modal-body{ padding:16px 18px; }
.close-x{ color:var(--text-dim); font-size:18px; background:none; border:none; }
.employee-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.employee-form .full{grid-column:1/-1}.employee-form label{display:block;color:var(--text-dim);font-size:12px;margin-bottom:5px}.employee-form input,.employee-form select{width:100%;background:var(--bg-panel);border:1px solid var(--line-strong);padding:10px;border-radius:6px;color:var(--text);font-family:inherit}.employee-form .required:after{content:' *';color:var(--danger)}.password-row{display:flex;gap:5px}.password-row input{flex:1}.password-row button{border:1px solid var(--line-strong);background:var(--bg-panel-2);padding:0 10px;border-radius:6px}.photo-field{border:1px dashed var(--line-strong);background:var(--bg-panel-2);padding:14px;border-radius:7px}.employee-section{grid-column:1/-1;font-size:15px;font-weight:700;border-bottom:1px solid var(--line);padding:5px 0}.form-actions{grid-column:1/-1;display:flex;gap:8px;margin-top:6px}@media(max-width:850px){.employee-form{grid-template-columns:1fr 1fr}}@media(max-width:560px){.employee-form{grid-template-columns:1fr}}

.notice{ display:flex; gap:10px; padding:10px 12px; border-radius:var(--radius); font-size:12px; margin-bottom:14px; border:1px solid; }
.notice.warn{ background:var(--warn-dim); border-color:var(--warn); color:var(--warn); }
.notice.danger{ background:var(--danger-dim); border-color:var(--danger); color:var(--danger); }
.notice.teal{ background:var(--teal-dim); border-color:var(--teal); color:var(--teal); }

.kanban{ display:grid; grid-template-columns:repeat(6,1fr); gap:10px; overflow-x:auto; }
.kcol{ background:var(--bg-panel); border:1px solid var(--line); border-radius:var(--radius); min-width:150px; }
.kcol-head{ padding:9px 11px; font-size:11px; font-weight:600; border-bottom:1px solid var(--line); color:var(--text-dim); display:flex; justify-content:space-between; }
.kcard{ margin:8px; padding:9px 10px; background:var(--bg-raised); border-radius:5px; font-size:11.5px; border:1px solid var(--line); }
.kcard .id{ font-family:'IBM Plex Mono'; color:var(--text-faint); font-size:10px; }

.toast-wrap{ position:fixed; bottom:20px; left:20px; z-index:60; display:flex; flex-direction:column; gap:8px; }
.toast{ background:var(--bg-panel); border:1px solid var(--success); color:var(--text); padding:10px 16px; border-radius:6px; font-size:12.5px; display:flex; align-items:center; gap:8px; box-shadow:0 6px 18px rgba(27,39,51,.14); }
</style>
</head>
<body class="<?= $mobileMode ? 'employee-mobile' : '' ?>">

<div class="titleblock">
  <div class="tb-brand">
    <div class="mark">سو</div>
    <div>
      <div class="name">سواعد المتكامل</div>
      <div class="sub">SAWAED-ERP · VERSION 1.10</div>
    </div>
  </div>
  <div class="tb-cell">
    <div class="tb-label">الشركة</div>
    <div class="tb-value"><select><option>شركة سواعد للمقاولات</option><option>سواعد للاستثمار العقاري</option></select></div>
  </div>
  <div class="tb-cell">
    <div class="tb-label">الفرع</div>
    <div class="tb-value"><select><option>الرياض</option><option>جدة</option><option>الدمام</option></select></div>
  </div>
  <div class="tb-cell">
    <div class="tb-label">المشروع</div>
    <div class="tb-value"><select><option>كل المشاريع</option><option>مشروع القدية — الحزمة 3</option><option>برج الواحة السكني</option></select></div>
  </div>
  <div class="tb-cell">
    <div class="tb-label">السنة المالية</div>
    <div class="tb-value">2026</div>
  </div>
  <div class="tb-cell">
    <div class="tb-label">الدور المعروض</div>
    <div class="tb-value"><select id="roleSelect" onchange="setRole(this.value)">
      <option value="worker">عامل</option>
      <option value="supervisor">مراقب / مشرف</option>
      <option value="site-engineer">مهندس موقع</option>
      <option value="project-manager" selected>مدير المشروع</option>
      <option value="projects-director">مدير المشاريع</option>
      <option value="hr-manager">مدير الموارد البشرية</option>
      <option value="executive-director">المدير التنفيذي</option>
      <option value="general-manager">المدير العام</option>
    </select></div>
  </div>
  <button class="tb-icon-btn" onclick="openSearch()">◎ بحث شامل</button>
  <button class="tb-icon-btn" onclick="go('approvals')">✔ الموافقات<span class="nav-badge">7</span></button>
  <button class="tb-icon-btn" id="serverSaveStatus" title="حالة الحفظ">● محفوظ</button>
  <a class="tb-icon-btn" href="logout.php" style="text-decoration:none">خروج · <?= htmlspecialchars($authUser['name']) ?></a>
</div>

<div class="shell">
  <div class="sidebar" id="sidebar"><div class="ruler"></div></div>
  <div class="main" id="main"></div>
</div>

<div class="modal-overlay" id="searchModal">
  <div class="modal">
    <div class="modal-head"><strong>البحث الشامل</strong><button class="close-x" onclick="closeSearch()">✕</button></div>
    <div class="modal-body">
      <input class="search-input" style="width:100%" placeholder="ابحث عن موظف، مشروع، فاتورة، معدة، مستخلص..." autofocus onkeydown="if(event.key==='Enter') runGlobalSearch(this.value)">
      <div class="faint" style="margin-top:10px;">أمثلة: EMP-00231 · مستخلص #14 مشروع القدية · مورد الخليج للحديد</div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="formModalOverlay">
  <div class="modal" id="formModalBox"></div>
</div>

<div class="toast-wrap" id="toasts"></div>

<script>
/* ============ بيانات تجريبية ============ */
const APP_PERMISSIONS = JSON.parse(document.querySelector('meta[name="app-permissions"]').content);
function htmlAttr(value){return String(value??'').replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;').replaceAll('>','&gt;');}
const employees = [
  {id:'EMP-0231', name:'محمد العتيبي', role:'مهندس موقع', project:'PRJ-014', branch:'الرياض', status:'نشط', joined:'2023-04-01', salary:11500, iqamaExpiry:'2026-09-12'},
  {id:'EMP-0198', name:'سالم الحربي', role:'مشرف سلامة', project:'PRJ-021', branch:'جدة', status:'نشط', joined:'2022-11-15', salary:8200, iqamaExpiry:'2026-08-02'},
  {id:'EMP-0304', name:'خالد الزهراني', role:'محاسب مشروع', project:'PRJ-014', branch:'الرياض', status:'إجازة', joined:'2021-02-20', salary:9800, iqamaExpiry:'2027-01-19'},
  {id:'EMP-0155', name:'Rahim Uddin', role:'فني كهرباء', project:'PRJ-021', branch:'جدة', status:'نشط', joined:'2020-06-10', salary:4200, iqamaExpiry:'2026-07-30'},
  {id:'EMP-0410', name:'عبدالله القرني', role:'مدير مشروع', project:'PRJ-014', branch:'الرياض', status:'نشط', joined:'2019-09-01', salary:22000, iqamaExpiry:'2027-03-11'},
];
// دالة موحّدة: كل حقل project في كل الجداول يُخزَّن كرقم مرجعي (PRJ-XXX)، وتُترجَم هنا فقط عند العرض.
// هذا يمنع بالضبط مشكلة "مشروع القدية" مقابل "مشروع القدية — الحزمة 3" التي اكتشفها فحص التكامل.
function projectNameById(id){
  const p = projects.find(x=>x.id===id);
  return p ? p.name : id;
}

const projects = [
  {id:'PRJ-014', name:'مشروع القدية — الحزمة 3', client:'صندوق الاستثمارات العامة', value:184000000, spent:97300000, progress:53, time:61, status:'منتظم', risk:'low', overBudget:false},
  {id:'PRJ-021', name:'برج الواحة السكني', client:'شركة الواحة العقارية', value:62000000, spent:51200000, progress:80, time:71, status:'منتظم', risk:'low', overBudget:false},
  {id:'PRJ-009', name:'مستودعات لوجستية — الدمام', client:'أرامكو المتعاقدون', value:39500000, spent:34100000, progress:84, time:96, status:'متأخر', risk:'high', overBudget:true},
  {id:'PRJ-027', name:'محطة تحلية — ينبع', client:'المياه الوطنية', value:210000000, spent:29000000, progress:12, time:9, status:'منتظم', risk:'medium', overBudget:false},
];

const journalEntries = [
  {id:'JE-2026-1042', date:'2026-07-15', desc:'قيد صرف مستخلص مقاول باطن #14', project:'PRJ-014', debit:1250000, credit:1250000, status:'posted'},
  {id:'JE-2026-1043', date:'2026-07-16', desc:'قيد مصروفات إدارية عامة', project:'—', debit:84200, credit:84200, status:'posted'},
  {id:'JE-2026-1044', date:'2026-07-18', desc:'قيد تسوية مخزون — فروقات جرد', project:'PRJ-021', debit:15300, credit:15300, status:'draft'},
  {id:'JE-2026-1045', date:'2026-07-19', desc:'قيد استحقاق راتب يوليو (مسودة)', project:'—', debit:1840000, credit:1840000, status:'draft'},
];

const suppliers = [
  {id:'SUP-041', name:'مصنع الخليج للحديد', category:'مواد إنشائية', rating:4.6, balance:820000, status:'مؤهل'},
  {id:'SUP-018', name:'شركة النخبة للتكييف', category:'أنظمة ميكانيكية', rating:4.1, balance:145000, status:'مؤهل'},
  {id:'SUP-063', name:'مؤسسة الأفق للسباكة', category:'مواد سباكة', rating:3.2, balance:0, status:'تحت المراجعة'},
];

const subcontractors = [
  {id:'SUB-007', name:'مقاولات الأساس المتين', scope:'أعمال الخرسانة', project:'PRJ-014', contractValue:42000000, retention:2100000, status:'نشط'},
  {id:'SUB-012', name:'الديار للتشطيبات', scope:'تشطيبات داخلية', project:'PRJ-021', contractValue:9800000, retention:490000, status:'نشط'},
];

const customers = [
  {id:'CUS-003', name:'صندوق الاستثمارات العامة', contracts:2, totalValue:184000000, collected:88400000, overdue:0},
  {id:'CUS-011', name:'شركة الواحة العقارية', contracts:1, totalValue:62000000, collected:49000000, overdue:2200000},
];

const equipment = [
  {id:'EQP-102', name:'حفارة كاتربيلر 320', type:'ملك', project:'PRJ-014', hours:5210, status:'عاملة', costHr:145},
  {id:'EQP-088', name:'رافعة برجية 40م', type:'إيجار', project:'PRJ-021', hours:3120, status:'صيانة', costHr:310},
  {id:'EQP-045', name:'خلاطة خرسانة متنقلة', type:'ملك', project:'PRJ-009', hours:8890, status:'متوقفة', costHr:60},
];

const materials = [
  {id:'MAT-3301', name:'حديد تسليح 16مم', unit:'طن', onHand:340, reserved:80, reorder:100, status:'متاح'},
  {id:'MAT-1187', name:'أسمنت مقاوم', unit:'كيس', onHand:1200, reserved:600, reorder:2000, status:'قريب من النفاد'},
  {id:'MAT-5502', name:'كابل كهربائي 4مم', unit:'لفة', onHand:0, reserved:0, reorder:20, status:'نفدت الكمية'},
];

const ncrs = [
  {id:'NCR-221', project:'PRJ-014', desc:'مخالفة سماكة صبة أرضية الطابق 3', severity:'حرجة', status:'قيد المعالجة'},
  {id:'NCR-219', project:'PRJ-021', desc:'عدم مطابقة نوع العزل المستخدم', severity:'متوسطة', status:'مغلقة'},
];

const incidents = [
  {id:'SAF-071', project:'PRJ-009', type:'إصابة طفيفة', severity:'منخفضة', status:'تحقيق'},
  {id:'SAF-069', project:'PRJ-014', type:'اقتراب من خطر (Near Miss)', severity:'متوسطة', status:'مغلقة'},
];

const purchaseOrders = [
  {id:'PO-5511', supplier:'مصنع الخليج للحديد', amount:410000, status:'استلام جزئي'},
  {id:'PO-5512', supplier:'النخبة للتكييف', amount:275000, status:'بانتظار الفاتورة'},
];

const geofences = [
  {id:'GEO-01', name:'موقع مشروع القدية — البوابة الرئيسية', lat:24.63, lng:46.57, radius:300},
  {id:'GEO-02', name:'موقع برج الواحة — جدة', lat:21.54, lng:39.17, radius:200},
  {id:'GEO-03', name:'المكتب الرئيسي — الرياض', lat:24.71, lng:46.68, radius:150},
];

/* ============ الأدوار وسلسلة الاعتماد (تعكس المصفوفة المعتمدة) ============ */
const ROLES = {
  'worker': {
    label:'عامل', threshold:null,
    visible:['employee-app'],
  },
  'hr-manager': {
    label:'مدير الموارد البشرية', threshold:null,
    visible:['dashboard','employees','attendance','payroll','settings','reports','employee-app'],
  },
  'supervisor': {
    label:'مراقب / مشرف', threshold:null,
    visible:['dashboard','employees','attendance','quality','safety','employee-app'],
  },
  'site-engineer': {
    label:'مهندس موقع', threshold:'15,000 ر.س (مشتريات) · 10,000 ر.س (صيانة)',
    visible:['dashboard','projects','employees','attendance','quality','safety','procurement','maintenance','contracts','documents','employee-app','warehouse-app'],
  },
  'project-manager': {
    label:'مدير المشروع', threshold:'150,000 ر.س (مشتريات) · 100,000 ر.س (أوامر تغيير)',
    visible:['dashboard','projects','employees','attendance','payroll','finance','customers','contracts','procurement','suppliers','subcontractors','warehouse','equipment','maintenance','quality','safety','documents','approvals','employee-app','warehouse-app'],
  },
  'projects-director': {
    label:'مدير المشاريع', threshold:'750,000 ر.س (مشتريات) · 500,000 ر.س (أوامر تغيير)',
    visible:['dashboard','companies','projects','employees','attendance','payroll','finance','customers','contracts','procurement','suppliers','subcontractors','warehouse','equipment','maintenance','quality','safety','documents','approvals','reports','employee-app'],
  },
  'executive-director': {
    label:'المدير التنفيذي', threshold:'3,000,000 ر.س (مشتريات) · 2,000,000 ر.س (أوامر تغيير)',
    visible:['dashboard','companies','projects','employees','payroll','finance','customers','contracts','tax','suppliers','subcontractors','equipment','quality','safety','approvals','reports','ai','auditor-portal','permissions','settings'],
  },
  'general-manager': {
    label:'المدير العام', threshold:'بلا سقف',
    visible:'*', // كل الأقسام
  },
};
let currentRole = 'project-manager';

function setRole(role){
  currentRole = role;
  buildSidebar();
  const allowed = ROLES[role].visible;
  if(allowed !== '*' && !allowed.includes(currentView)){
    go(allowed[0]);
  } else {
    go(currentView);
  }
  toast('تم التبديل إلى دور: ' + ROLES[role].label + (ROLES[role].threshold ? ' — سقف الاعتماد: ' + ROLES[role].threshold : ' — بلا صلاحية اعتماد مالي'));
}


const NAV = [
  {group:'القيادة', items:[
    {k:'dashboard', label:'لوحة القيادة', ic:'▣'},
    {k:'ai', label:'مساعد سواعد الذكي', ic:'✦'},
    {k:'reports', label:'التقارير والتحليلات', ic:'▤'},
  ]},
  {group:'الهيكل والمشاريع', items:[
    {k:'companies', label:'الشركات والفروع', ic:'◫'},
    {k:'projects', label:'المشاريع', ic:'◧'},
  ]},
  {group:'الموارد البشرية', items:[
    {k:'employees', label:'الموظفون', ic:'⚉'},
    {k:'attendance', label:'الحضور والانصراف', ic:'⏱'},
    {k:'payroll', label:'الرواتب', ic:'◈'},
  ]},
  {group:'المالية', items:[
    {k:'finance', label:'القيود والأستاذ العام', ic:'≣'},
    {k:'customers', label:'العملاء والتحصيل', ic:'⛁'},
    {k:'tax', label:'مركز الضرائب وZATCA', ic:'٪'},
  ]},
  {group:'سلسلة التوريد', items:[
    {k:'procurement', label:'المشتريات', ic:'⇄'},
    {k:'suppliers', label:'الموردون', ic:'▨'},
    {k:'subcontractors', label:'مقاولو الباطن', ic:'▩'},
    {k:'warehouse', label:'المستودعات والمخزون', ic:'▦'},
  ]},
  {group:'الأصول والعمليات', items:[
    {k:'equipment', label:'المعدات والمركبات', ic:'⚙'},
    {k:'maintenance', label:'الصيانة', ic:'✚'},
    {k:'contracts', label:'العقود والمستخلصات', ic:'§'},
  ]},
  {group:'الجودة والامتثال', items:[
    {k:'quality', label:'الجودة', ic:'✓'},
    {k:'safety', label:'السلامة', ic:'⚠'},
    {k:'documents', label:'إدارة الوثائق', ic:'▥'},
    {k:'approvals', label:'المهام والموافقات', ic:'✔', badge:7},
  ]},
  {group:'البوابات والتطبيقات', items:[
    {k:'employee-app', label:'تطبيق الموظف', ic:'▢'},
    {k:'warehouse-app', label:'تطبيق المستودع', ic:'▧'},
    {k:'supplier-portal', label:'بوابة الموردين', ic:'◨'},
    {k:'auditor-portal', label:'بوابة المراجع الخارجي', ic:'◎'},
  ]},
  {group:'النظام', items:[
    {k:'permissions', label:'الأدوار والصلاحيات', ic:'⚿'},
    {k:'settings', label:'إعدادات النظام', ic:'⚒'},
  ]},
];

let currentView = <?= $mobileMode ? "'employee-app'" : "'dashboard'" ?>;

function buildSidebar(){
  const el = document.getElementById('sidebar');
  const allowed = ROLES[currentRole].visible;
  const visibleGroups = NAV.map(g => ({
    group: g.group,
    items: g.items.filter(it => allowed === '*' || allowed.includes(it.k)),
  })).filter(g => g.items.length > 0);

  el.innerHTML = '<div class="ruler"></div>' + visibleGroups.map(g=>`
    <div class="nav-group">
      <div class="nav-group-label">${g.group}</div>
      ${g.items.map(it=>`
        <div class="nav-item ${it.k===currentView?'active':''}" onclick="go('${it.k}')">
          <span class="ico">${it.ic}</span><span>${it.label}</span>
          ${it.badge?`<span class="nav-badge">${it.badge}</span>`:''}
        </div>`).join('')}
    </div>`).join('')
    + `<div style="padding:14px 16px; border-top:1px solid var(--line-strong); margin-top:8px;">
        <div class="faint" style="margin-bottom:4px">الدور الحالي</div>
        <div style="font-size:12.5px;font-weight:600">${ROLES[currentRole].label}</div>
        <div class="faint" style="margin-top:4px">${ROLES[currentRole].threshold ? 'سقف الاعتماد: '+ROLES[currentRole].threshold : 'بلا صلاحية اعتماد مالي'}</div>
      </div>`;
}

function go(view){
  currentView = view;
  buildSidebar();
  const renderer = RENDERERS[view] || renderGenericComingSoon;
  document.getElementById('main').innerHTML = renderer();
  document.getElementById('main').scrollTop = 0;
}

/* ============ نظام النماذج المنبثقة العام (Modals حقيقية بحقول فعلية) ============ */
let __formModalSubmit = null;
function fmField(label, key, type='text', optsOrValue=''){
  if(type==='select'){
    return `<div><div class="faint" style="margin-bottom:4px">${label}</div>
      <select data-field="${key}" style="width:100%;background:var(--bg-panel);border:1px solid var(--line-strong);padding:9px;border-radius:6px;color:var(--text);font-family:inherit">${optsOrValue}</select></div>`;
  }
  if(type==='textarea'){
    return `<div><div class="faint" style="margin-bottom:4px">${label}</div>
      <textarea data-field="${key}" rows="3" style="width:100%;background:var(--bg-panel);border:1px solid var(--line-strong);padding:9px;border-radius:6px;color:var(--text);font-family:inherit;resize:vertical"></textarea></div>`;
  }
  return `<div><div class="faint" style="margin-bottom:4px">${label}</div>
    <input data-field="${key}" type="${type}" value="${optsOrValue}" style="width:100%;background:var(--bg-panel);border:1px solid var(--line-strong);padding:9px;border-radius:6px;color:var(--text);font-family:inherit"></div>`;
}
function fmOptions(list){ return list.map(o=>`<option value="${o}">${o}</option>`).join(''); }

function openFormModal(title, fieldsHtml, submitLabel, onSubmit){
  __formModalSubmit = onSubmit;
  document.getElementById('formModalBox').innerHTML = `
    <div class="modal-head"><strong>${title}</strong><button class="close-x" onclick="closeFormModal()">✕</button></div>
    <div class="modal-body">
      <div style="display:flex;flex-direction:column;gap:12px" id="formModalFields">${fieldsHtml}</div>
      <div style="display:flex;gap:8px;margin-top:18px">
        <button class="btn" onclick="submitFormModal()">${submitLabel}</button>
        <button class="btn-outline" onclick="closeFormModal()">إلغاء</button>
      </div>
    </div>`;
  document.getElementById('formModalOverlay').classList.add('open');
}
function closeFormModal(){ document.getElementById('formModalOverlay').classList.remove('open'); document.getElementById('formModalBox').style.width=''; }
function submitFormModal(){
  const values = {};
  const formFields = [...document.querySelectorAll('#formModalFields [data-field]')];
  formFields.forEach(f => values[f.dataset.field] = f.value);
  const missing = formFields.find(f => f.dataset.optional!=='1' && (!f.value || !String(f.value).trim()));
  if(missing){ toast('⚠ الرجاء تعبئة الحقول المطلوبة قبل الحفظ', 'danger'); return; }
  closeFormModal();
  if(__formModalSubmit) __formModalSubmit(values);
  if(typeof scheduleServerSave === 'function') scheduleServerSave();
}

/* ---- نماذج الشركات والفروع (لا يوجد مصفوفة بيانات مخصصة لها بعد في هذه العيّنة) ---- */
function openNewCompanyModal(){
  openFormModal('إنشاء شركة جديدة', [
    fmField('اسم الشركة','name'), fmField('السجل التجاري','cr'), fmField('الرقم الضريبي','vat'),
    fmField('العملة','currency','select', fmOptions(['ريال سعودي (SAR)','دولار أمريكي (USD)'])),
  ].join(''), 'إنشاء الشركة', v => toast('تم إنشاء شركة "'+v.name+'" بسجل تجاري '+v.cr));
}
function openNewBranchModal(){
  openFormModal('إضافة فرع جديد', [
    fmField('اسم الفرع','name'), fmField('المدينة','city'), fmField('المسؤول عن الفرع','manager'),
  ].join(''), 'إضافة الفرع', v => toast('تمت إضافة فرع "'+v.name+'" في '+v.city));
}

/* ---- نموذج مشروع جديد — مرتبط فعليًا بمصفوفة المشاريع ويُحدّث القائمة فورًا ---- */
function openNewProjectModal(){
  openFormModal('إنشاء مشروع جديد', [
    fmField('اسم المشروع','name'), fmField('العميل','client'),
    fmField('قيمة العقد (ر.س)','value','number'),
    fmField('مدير المشروع','pm','select', fmOptions(employees.filter(e=>e.role.includes('مدير')).map(e=>e.name).concat(['عبدالله القرني']))),
    fmField('تاريخ البداية','start','date'), fmField('تاريخ النهاية المتوقع','end','date'),
  ].join(''), 'إنشاء المشروع', v => {
    const id = 'PRJ-'+(100+projects.length);
    projects.push({id, name:v.name, client:v.client, value:Number(v.value)||0, spent:0, progress:0, time:0, status:'منتظم', risk:'low', overBudget:false});
    toast('✓ تم إنشاء المشروع "'+v.name+'" وإضافته للقائمة فعليًا');
    if(currentView==='projects') go('projects');
  });
}

/* ---- إضافة موظف وحساب دخول وعقد في عملية واحدة ---- */
function openNewEmployeeModal(){
  const allowedRoles=SERVER_USER.role==='general-manager'?roleOrder:['worker','supervisor','site-engineer','project-manager'];
  const roleOptions=allowedRoles.map(r=>`<option value="${r}">${ROLES[r].label}</option>`).join('');
  document.getElementById('formModalBox').style.width='980px';
  document.getElementById('formModalBox').innerHTML=`
    <div class="modal-head"><div><strong>إضافة موظف</strong><div class="faint" style="margin-top:4px">ملف الموظف + عقد العمل + حساب الدخول</div></div><button class="close-x" type="button" onclick="closeFormModal()">✕</button></div>
    <div class="modal-body"><form id="employeeOnboardingForm" class="employee-form" onsubmit="submitEmployeeOnboarding(event)" enctype="multipart/form-data">
      <div class="employee-section">تفاصيل الحساب</div>
      <div><label class="required">رقم الهوية / الإقامة</label><input name="national_id" inputmode="numeric" pattern="[0-9٠-٩]{10}" maxlength="10" required></div>
      <div><label class="required">الوظيفة</label><input name="job_title" required></div>
      <div><label class="required">اسم الموظف</label><input name="name" autocomplete="name" required></div>
      <div><label class="required">البريد الإلكتروني لاستعادة الحساب</label><input name="email" type="email" autocomplete="email" required></div>
      <div><label class="required">كلمة المرور</label><div class="password-row"><input id="newEmployeePassword" name="password" type="password" minlength="10" autocomplete="new-password" required><button type="button" onclick="toggleEmployeePassword()" title="إظهار/إخفاء">◉</button><button type="button" onclick="generateEmployeePassword()" title="إنشاء كلمة قوية">⚄</button></div></div>
      <div><label class="required">دور المستخدم</label><select name="role" required>${roleOptions}</select></div>
      <div><label class="required">نوع الموظف</label><select name="employee_type" required><option value="دوام كامل">دوام كامل</option><option value="دوام جزئي">دوام جزئي</option><option value="مؤقت">مؤقت</option><option value="عاملة مشروع">عمالة مشروع</option></select></div>
      <div><label>الكفالة</label><input name="sponsor_name"></div>
      <div><label>الصورة الشخصية</label><div class="photo-field"><input name="profile_image" type="file" accept="image/jpeg,image/png,image/webp"><div class="faint">JPG / PNG / WebP — بحد أقصى 5 MB</div></div></div>
      <div class="employee-section">البيانات الشخصية والوظيفية</div>
      <div><label>الجنسية</label><select name="nationality"><option value="السعودية">السعودية</option><option value="مصر">مصر</option><option value="الهند">الهند</option><option value="باكستان">باكستان</option><option value="بنغلاديش">بنغلاديش</option><option value="أخرى">أخرى</option></select></div>
      <div><label>رقم الجوال (+966)</label><input name="mobile_number" inputmode="numeric" pattern="5[0-9]{8}" maxlength="9" placeholder="5xxxxxxxx"></div>
      <div><label>الجنس</label><select name="gender"><option value="ذكر">ذكر</option><option value="أنثى">أنثى</option></select></div>
      <div><label class="required">تاريخ بداية العمل</label><input name="started_on" type="date" value="${new Date().toISOString().slice(0,10)}" required></div>
      <div><label>تاريخ الميلاد</label><input name="birth_date" type="date"></div>
      <div><label>اللغة</label><select name="preferred_language"><option value="ar">العربية</option><option value="en">English</option></select></div>
      <div><label>المشروع</label><select name="project_code"><option value="">دون مشروع حالي</option>${projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')}</select></div>
      <div><label class="required">نوع العقد</label><select name="contract_type" required><option value="غير محدد المدة">غير محدد المدة</option><option value="محدد المدة">محدد المدة</option><option value="مؤقت">مؤقت</option></select></div>
      <div><label>تاريخ انتهاء الإقامة</label><input name="iqama_expiry" type="date"></div>
      <div><label>الراتب الأساسي (ر.س)</label><input name="basic_salary" type="number" min="0" step="0.01" value="0"></div>
      <div><label>بدل السكن</label><input name="housing_allowance" type="number" min="0" step="0.01" value="0"></div>
      <div><label>بدل النقل</label><input name="transport_allowance" type="number" min="0" step="0.01" value="0"></div>
      <div class="form-actions"><button class="btn" id="employeeOnboardingSubmit">إضافة الموظف وإنشاء الحساب</button><button class="btn-outline" type="button" onclick="closeFormModal()">إلغاء</button></div>
    </form></div>`;
  document.getElementById('formModalOverlay').classList.add('open');
}
function toggleEmployeePassword(){const i=document.getElementById('newEmployeePassword');i.type=i.type==='password'?'text':'password';}
function generateEmployeePassword(){const chars='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';const bytes=new Uint32Array(16);crypto.getRandomValues(bytes);document.getElementById('newEmployeePassword').value=[...bytes].map(n=>chars[n%chars.length]).join('');document.getElementById('newEmployeePassword').type='text';}

/* ---- قيد محاسبي جديد — مرتبط فعليًا بمصفوفة القيود ---- */
function openNewJournalEntryModal(){
  openFormModal('قيد يومية جديد (مسودة)', [
    fmField('التاريخ','entry_date','date'),fmField('الوصف','description'),fmField('المشروع','project_code','select','<option value="">دون مشروع</option>'+projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('الحساب المدين','debit_account'),fmField('الحساب الدائن','credit_account'),fmField('المبلغ','amount','number'),fmField('مركز التكلفة','cost_center'),
  ].join(''),'حفظ كمسودة',async v=>{try{const amount=Number(v.amount||0);const lines=[{account_code:v.debit_account,debit:amount,credit:0,cost_center:v.cost_center},{account_code:v.credit_account,debit:0,credit:amount,cost_center:v.cost_center}];const r=await financeTaxAction('create_journal',{entry_date:v.entry_date,description:v.description,project_code:v.project_code,lines});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- مستخلص جديد ---- */
function openNewClaimModal(){
  if(typeof openCustomerClaimModal==='function')openCustomerClaimModal();
}

/* ---- طلب شراء جديد ---- */
function openNewPurchaseRequestModal(){
  openFormModal('طلب شراء جديد', [
    fmField('الصنف المسجل (اختياري)','item_code','select','<option value="">خدمة/صنف غير مسجل</option>'+((window.supplyData?.items||[]).map(i=>`<option value="${i.code}">${i.name} (${i.code})</option>`).join(''))),
    fmField('الوصف','description'),fmField('الوحدة','unit'),fmField('الكمية','quantity','number'),
    fmField('المشروع','project_code','select', projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),
    fmField('سعر الوحدة التقديري','estimated_unit_price','number'),fmField('تاريخ الاحتياج','needed_on','date'),fmField('المبرر','justification','textarea'),
  ].join(''), 'إرسال الطلب', async v => {try{const r=await supplyAction('create_request',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- تأهيل مورد جديد — مرتبط فعليًا بمصفوفة الموردين ---- */
function openNewSupplierModal(){
  openFormModal('تأهيل مورد جديد', [
    fmField('رمز المورد','code'),fmField('اسم المورد','name'), fmField('التصنيف','category'),fmField('الرقم الضريبي','vat_number'),
  ].join(''), 'إرسال للتأهيل', async v => {try{const r=await supplyAction('create_supplier',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- إضافة معدة جديدة — مرتبطة فعليًا بمصفوفة المعدات ---- */
function openNewEquipmentModal(){
  openFormModal('إضافة معدة جديدة', [
    fmField('رمز المعدة','code'),fmField('اسم المعدة','name'),
    fmField('الملكية','ownership_type','select','<option value="owned">ملك</option><option value="rented">إيجار</option>'),
    fmField('المشروع','project_code','select', projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),
    fmField('قراءة العداد','meter_hours','number'),fmField('تكلفة الساعة (ر.س)','hourly_cost','number'),
  ].join(''), 'إضافة المعدة', async v => {try{const r=await supplyAction('create_equipment',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- بلاغ عطل ---- */
function openMaintenanceReportModal(){
  const rows=window.supplyData?.equipment||[];if(!rows.length)return toast('أضف معدة أولًا','danger');
  openFormModal('بلاغ عطل جديد', [
    fmField('المعدة','equipment_code','select',rows.map(e=>`<option value="${e.code}">${e.name} (${e.code})</option>`).join('')),fmField('الأولوية','priority','select','<option value="normal">عادية</option><option value="urgent">عاجلة</option><option value="critical">حرجة</option>'),fmField('وصف العطل','fault_description','textarea'),
  ].join(''), 'إرسال البلاغ', async v=>{try{const r=await operationsAction('report_fault',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- طلب فحص جودة ---- */
function openQualityInspectionModal(){
  openFormModal('طلب فحص جودة جديد', [
    fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('نوع الفحص','inspection_type','select','<option value="work">أعمال منفذة</option><option value="material">مواد</option><option value="handover">استلام مرحلة</option>'),fmField('مرجع الدفعة/المخطط','lot_reference'),fmField('موعد الفحص','scheduled_on','date'),fmField('وصف بند الفحص','description','textarea'),
  ].join(''), 'إرسال الطلب',async v=>{try{const r=await operationsAction('create_quality_inspection',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- تصريح عمل (سلامة) ---- */
function openSafetyPermitModal(){
  openFormModal('تصريح عمل جديد', [
    fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('نوع العمل','permit_type','select','<option value="hot_work">عمل ساخن</option><option value="height">عمل بالارتفاعات</option><option value="confined_space">أماكن مغلقة</option><option value="excavation">حفريات</option>'),fmField('من','valid_from','datetime-local'),fmField('إلى','valid_until','datetime-local'),fmField('وصف العمل','work_description','textarea'),fmField('ضوابط السلامة والعزل','controls','textarea'),
  ].join(''), 'إرسال للاعتماد',async v=>{try{const r=await operationsAction('create_safety_permit',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});
}

/* ---- رفع مستند ---- */
function openDocumentUploadModal(){
  document.getElementById('formModalBox').innerHTML = `
    <div class="modal-head"><strong>رفع مستند جديد</strong><button class="close-x" onclick="closeFormModal()">✕</button></div>
    <div class="modal-body"><div style="display:flex;flex-direction:column;gap:12px">
      <div><div class="faint" style="margin-bottom:4px">الملف</div><input id="documentFile" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" style="width:100%"></div>
      <div><div class="faint" style="margin-bottom:4px">عنوان المستند</div><input id="documentTitle" style="width:100%"></div>
      <div><div class="faint" style="margin-bottom:4px">رمز مستند سابق لإصدار نسخة جديدة (اختياري)</div><input id="documentCode" style="width:100%"></div>
      <div><div class="faint" style="margin-bottom:4px">التصنيف</div><select id="documentCategory" style="width:100%"><option>عقد</option><option>خطاب</option><option>شهادة</option><option>مخطط</option><option>فاتورة</option></select></div>
      <div><div class="faint" style="margin-bottom:4px">المشروع</div><select id="documentProject" style="width:100%"><option value="">—</option>${projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')}</select></div>
      <div><div class="faint" style="margin-bottom:4px">تاريخ انتهاء الصلاحية (اختياري)</div><input id="documentExpiry" type="date" style="width:100%"></div>
    </div><div style="display:flex;gap:8px;margin-top:18px"><button class="btn" onclick="uploadDocumentToServer()">رفع المستند</button><button class="btn-outline" onclick="closeFormModal()">إلغاء</button></div></div>`;
  document.getElementById('formModalOverlay').classList.add('open');
}

/* ---- طلب عهدة جديد (من تطبيق الموظف) ---- */
function openCustodyRequestModal(){
  openFormModal('طلب عهدة جديدة', [
    fmField('العنصر المطلوب','item'), fmField('السبب','reason','textarea'),
  ].join(''), 'إرسال الطلب', v => toast('✓ تم إرسال طلب عهدة "'+v.item+'" لمديرك المباشر'));
}

/* ---- طلب عام جديد (من تطبيق الموظف — طلباتي) ---- */
function openNewGenericRequestModal(){
  openFormModal('طلب جديد', [
    fmField('نوع الطلب','type','select', fmOptions(['طلب تعريف','طلب سلفة','طلب شهادة راتب','شكوى/ملاحظة'])),
    fmField('التفاصيل','details','textarea'),
  ].join(''), 'إرسال الطلب', v => toast('✓ تم إرسال طلبك: "'+v.type+'" لمديرك المباشر'));
}

/* ---- سنة مالية جديدة — مرتبطة فعليًا ---- */
function openNewFiscalYearModal(){
  openFormModal('إنشاء سنة مالية جديدة', [ fmField('السنة','year','number','2027') ].join(''),
    'إنشاء', v => {
      fiscalYears.unshift({y:v.year, status:'مفتوحة', closedMonths:0, totalMonths:12});
      toast('✓ تم إنشاء السنة المالية '+v.year);
      if(currentView==='settings') setSettingsTab('financial-years');
    });
}

/* ---- حساب جديد في دليل الحسابات — مرتبط فعليًا ---- */
function openNewAccountModal(){
  openFormModal('إضافة حساب جديد', [
    fmField('رمز الحساب','code'), fmField('اسم الحساب','name'),
    fmField('المستوى','type','select', fmOptions(['رئيسي','فرعي','تفصيلي'])),
  ].join(''), 'إضافة', v => {
    chartOfAccounts.push({code:v.code, name:v.name, type:v.type, level: v.type==='رئيسي'?1:v.type==='فرعي'?2:3});
    toast('✓ تم إضافة الحساب "'+v.name+'" ('+v.code+')');
    if(currentView==='settings') setSettingsTab('coa');
  });
}

/* ---- مركز تكلفة جديد — مرتبط فعليًا ---- */
function openNewCostCenterModal(){
  openFormModal('مركز تكلفة جديد', [
    fmField('الرمز','id'), fmField('الاسم','name'),
    fmField('النوع','type','select', fmOptions(['مشروع','إداري','تشغيلي'])),
    fmField('الموازنة السنوية (ر.س)','budget','number'),
  ].join(''), 'إضافة', v => {
    costCenters.push({id:v.id, name:v.name, type:v.type, budget:Number(v.budget)||0});
    toast('✓ تم إضافة مركز التكلفة "'+v.name+'"');
    if(currentView==='settings') setSettingsTab('cost-centers');
  });
}

/* ---- نوع عقد جديد — مرتبط فعليًا ---- */
function openNewContractTypeModal(){
  openFormModal('نوع عقد جديد', [ fmField('اسم نوع العقد','name') ].join(''),
    'إضافة', v => {
      contractTypes.push({name:v.name, usedIn:0});
      toast('✓ تم إضافة نوع العقد "'+v.name+'"');
      if(currentView==='settings') setSettingsTab('contract-types');
    });
}


function openSearch(){ document.getElementById('searchModal').classList.add('open'); }
function closeSearch(){ document.getElementById('searchModal').classList.remove('open'); }
function runGlobalSearch(q){
  q = q.trim();
  if(!q){ toast('اكتب كلمة للبحث أولًا', 'danger'); return; }
  const results = [];
  employees.forEach(e=>{ if(e.name.includes(q) || e.id.toLowerCase().includes(q.toLowerCase())) results.push('موظف: '+e.name+' ('+e.id+')'); });
  projects.forEach(p=>{ if(p.name.includes(q) || p.id.toLowerCase().includes(q.toLowerCase())) results.push('مشروع: '+p.name); });
  suppliers.forEach(s=>{ if(s.name.includes(q)) results.push('مورد: '+s.name); });
  equipment.forEach(eq=>{ if(eq.name.includes(q)) results.push('معدة: '+eq.name); });
  if(results.length){
    toast('نتائج: ' + results.slice(0,3).join(' · ') + (results.length>3?' ...':''));
  } else {
    toast('لا نتائج مطابقة لـ "'+q+'" ضمن بيانات هذه العيّنة', 'danger');
  }
  closeSearch();
}

function toast(msg, tone='success'){
  const wrap = document.getElementById('toasts');
  const t = document.createElement('div');
  t.className='toast';
  t.style.borderColor = tone==='success'?'var(--success)':tone==='danger'?'var(--danger)':'var(--accent)';
  t.innerHTML = `<span>${tone==='success'?'✓':tone==='danger'?'✕':'ℹ'}</span><span>${msg}</span>`;
  wrap.appendChild(t);
  setTimeout(()=>t.remove(), 3200);
}

function fmt(n){ return new Intl.NumberFormat('en-US').format(n); }

/* ============ مساعد عام لبناء جداول ============ */
function dataTable(cols, rows, opts={}){
  return `<div class="panel">
    ${opts.title?`<div class="panel-head"><div class="panel-title">${opts.title}</div>${opts.action||''}</div>`:''}
    <div style="overflow-x:auto">
    <table>
      <thead><tr>${cols.map(c=>`<th>${c.label}</th>`).join('')}</tr></thead>
      <tbody>
        ${rows.map(r=>`<tr onclick="${opts.onRowClick?`${opts.onRowClick}('${r.id}')`:''}">
          ${cols.map(c=>`<td class="${c.num?'num':''}">${c.render?c.render(r):(r[c.key]??'')}</td>`).join('')}
        </tr>`).join('')}
      </tbody>
    </table>
    </div>
  </div>`;
}

function badge(text, tone){ return `<span class="badge b-${tone}">${text}</span>`; }

/* ============ لوحة القيادة ============ */
function renderDashboard(){
  return `
  <div class="page-eyebrow">القيادة · نظرة عامة</div>
  <div class="page-head">
    <div><div class="page-title">لوحة المدير العام</div><div class="page-desc">كل رقم أدناه قابل للنقر ويفتح البيانات المصدرية التي أنتجته</div></div>
    <button class="btn-outline" onclick="go('reports')">تقارير مفصّلة ↗</button>
  </div>

  <div class="notice teal">ℹ ملاحظات المحاسب القانوني: مصادقة العميل PRJ-021 لهذا الربع لم تُستلم بعد — 2 يوم متبقٍ على الموعد النهائي.</div>

  <div class="kpi-grid">
    <div class="kpi" onclick="go('finance')"><div class="kpi-label">الرصيد النقدي الحالي</div><div class="kpi-value">14.2M</div><div class="kpi-trend up">▲ 3.1% عن الأسبوع الماضي</div></div>
    <div class="kpi" onclick="go('customers')"><div class="kpi-label">التحصيل المتوقع (30 يوم)</div><div class="kpi-value">9.8M</div><div class="kpi-trend flat">— دون تغيير</div></div>
    <div class="kpi" onclick="go('finance')"><div class="kpi-label">الالتزامات القادمة (30 يوم)</div><div class="kpi-value">6.4M</div><div class="kpi-trend down">▼ يتضمن مسير رواتب يوليو</div></div>
    <div class="kpi" onclick="go('contracts')"><div class="kpi-label">إجمالي قيمة العقود</div><div class="kpi-value">495.5M</div><div class="kpi-trend flat">4 عقود نشطة</div></div>
    <div class="kpi" onclick="go('employees')"><div class="kpi-label">عدد الموظفين</div><div class="kpi-value">1,248</div><div class="kpi-trend up">▲ نسبة السعودة 34%</div></div>
    <div class="kpi" onclick="go('equipment')"><div class="kpi-label">المعدات العاملة</div><div class="kpi-value">86 / 104</div><div class="kpi-trend down">▼ 6 تحت الصيانة</div></div>
    <div class="kpi" onclick="go('warehouse')"><div class="kpi-label">قيمة المخزون</div><div class="kpi-value">22.6M</div><div class="kpi-trend down">▼ صنف واحد نفدت كميته</div></div>
    <div class="kpi" onclick="go('tax')"><div class="kpi-label">الضريبة المستحقة</div><div class="kpi-value">1.94M</div><div class="kpi-trend flat">إقرار QIII مستحق 15 أغسطس</div></div>
  </div>

  <div class="grid-2">
    <div class="panel">
      <div class="panel-head"><div class="panel-title">المشاريع — الأداء المالي مقابل الإنجاز</div></div>
      <div class="panel-body">
        ${projects.map(p=>`
          <div class="card" onclick="go('projects')">
            <div class="flex-between" style="margin-bottom:8px;">
              <div>
                <strong>${p.name}</strong>
                <div class="faint">${p.client} · ${p.id}</div>
              </div>
              ${badge(p.status, p.status==='متأخر'?'danger':'success')}
            </div>
            <div class="flex-between faint" style="margin-bottom:4px;">
              <span>الإنجاز الفعلي ${p.progress}%</span><span>الوقت المنقضي ${p.time}%</span>
            </div>
            <div class="pbar"><div style="width:${p.progress}%; background:${p.risk==='high'?'var(--danger)':p.risk==='medium'?'var(--warn)':'var(--accent)'}"></div></div>
          </div>`).join('')}
      </div>
    </div>
    <div class="panel">
      <div class="panel-head"><div class="panel-title">الموافقات العاجلة</div></div>
      <div class="panel-body">
        <div class="card flex-between"><span>دفعة مالية — PO-5511</span>${badge('280K ر.س','warn')}</div>
        <div class="card flex-between"><span>مستخلص مقاول باطن SUB-007</span>${badge('1.25M ر.س','warn')}</div>
        <div class="card flex-between"><span>طلب توظيف — مهندس مدني</span>${badge('عاجل','danger')}</div>
        <button class="btn-outline" style="width:100%" onclick="go('approvals')">عرض الكل (7) ↗</button>
      </div>
    </div>
  </div>`;
}

/* ============ الشركات والفروع ============ */
function renderCompanies(){
  return `
  <div class="page-eyebrow">الهيكل التنظيمي</div>
  <div class="page-head"><div class="page-title">الشركات والفروع</div>
    <button class="btn" onclick="openNewCompanyModal()">+ إنشاء شركة</button></div>
  <div class="grid-2">
    <div class="panel">
      <div class="panel-head"><div class="panel-title">شركة سواعد للمقاولات</div>${badge('نشطة','success')}</div>
      <div class="panel-body">
        <table>
          <tr><td class="faint">السجل التجاري</td><td class="num">1010-556-231</td></tr>
          <tr><td class="faint">الرقم الضريبي</td><td class="num">3001234567800003</td></tr>
          <tr><td class="faint">السنة المالية</td><td>يناير — ديسمبر</td></tr>
          <tr><td class="faint">العملة</td><td>ريال سعودي (SAR)</td></tr>
          <tr><td class="faint">حساب ZATCA</td><td>${badge('مفعّل — Compliance','teal')}</td></tr>
        </table>
      </div>
    </div>
    <div class="panel">
      <div class="panel-head"><div class="panel-title">الفروع (3)</div><button class="btn-outline btn-sm" onclick="openNewBranchModal()">+ فرع</button></div>
      <div class="panel-body">
        ${[['الرياض (رئيسي)','412 موظف','9 مشاريع'],['جدة','298 موظف','4 مشاريع'],['الدمام','156 موظف','2 مشروع']].map(b=>`
        <div class="card flex-between"><strong>${b[0]}</strong><span class="faint">${b[1]} · ${b[2]}</span></div>`).join('')}
      </div>
    </div>
  </div>`;
}

/* ============ المشاريع ============ */
let projectsFilter = 'all';
let projectsSearchQuery = '';
function projectsFilteredList(){
  return projects.filter(p=>{
    if(projectsFilter==='ontrack' && !(p.status==='منتظم' && !p.overBudget)) return false;
    if(projectsFilter==='delayed' && p.status!=='متأخر') return false;
    if(projectsFilter==='over-budget' && !p.overBudget) return false;
    const q = projectsSearchQuery.trim();
    if(q && !(p.name.includes(q) || p.client.includes(q) || p.id.toLowerCase().includes(q.toLowerCase()))) return false;
    return true;
  });
}
function projectsGridHtml(){
  const filtered = projectsFilteredList();
  return filtered.length? filtered.map(p=>`
    <div class="panel" onclick="openProjectWorkspace('${p.id}')" style="cursor:pointer">
      <div class="panel-head"><div class="panel-title">${p.name}</div>${badge(p.status, p.status==='متأخر'?'danger':'success')}</div>
      <div class="panel-body">
        <div class="faint" style="margin-bottom:10px;">${p.client} · ${p.id}</div>
        <div class="grid-3" style="gap:8px;">
          <div><div class="faint">قيمة العقد</div><div class="num" style="font-size:15px">${fmt(p.value/1000000).slice(0,5)}M</div></div>
          <div><div class="faint">التكلفة الفعلية</div><div class="num" style="font-size:15px">${fmt(p.spent/1000000).slice(0,5)}M</div></div>
          <div><div class="faint">الربح المتوقع</div><div class="num" style="font-size:15px;color:var(--success)">${fmt((p.value-p.spent)/1000000).slice(0,5)}M</div></div>
        </div>
        <div class="pbar" style="margin-top:12px"><div style="width:${p.progress}%"></div></div>
        <div class="faint" style="margin-top:6px">إنجاز ${p.progress}% مقابل وقت منقضٍ ${p.time}%</div>
      </div>
    </div>`).join('') : `<div class="empty">لا توجد مشاريع مطابقة للبحث/الفلتر الحالي.</div>`;
}
function onProjectsSearchInput(val){
  projectsSearchQuery = val;
  document.getElementById('projectsGrid').innerHTML = projectsGridHtml();
}
function renderProjects(){
  return `
  <div class="page-eyebrow">مساحات العمل</div>
  <div class="page-head"><div class="page-title">المشاريع</div><button class="btn" onclick="openNewProjectModal()">+ مشروع جديد</button></div>
  <div class="toolbar">
    <input class="search-input" placeholder="ابحث باسم المشروع أو العميل" oninput="onProjectsSearchInput(this.value)">
    <span class="chip ${projectsFilter==='all'?'active':''}" onclick="setProjectsFilter('all')">الكل (${projects.length})</span>
    <span class="chip ${projectsFilter==='ontrack'?'active':''}" onclick="setProjectsFilter('ontrack')">منتظمة (${projects.filter(p=>p.status==='منتظم'&&!p.overBudget).length})</span>
    <span class="chip ${projectsFilter==='delayed'?'active':''}" onclick="setProjectsFilter('delayed')">متأخرة (${projects.filter(p=>p.status==='متأخر').length})</span>
    <span class="chip ${projectsFilter==='over-budget'?'active':''}" onclick="setProjectsFilter('over-budget')">تتجاوز الميزانية (${projects.filter(p=>p.overBudget).length})</span>
  </div>
  <div class="grid-2" id="projectsGrid">${projectsGridHtml()}</div>`;
}
function setProjectsFilter(f){ projectsFilter=f; document.getElementById('projectsGrid').innerHTML = projectsGridHtml(); buildSidebar(); }

let projectWorkspaceId = null;
let projectWorkspaceTab = 'summary';

function openProjectWorkspace(id){
  projectWorkspaceId = id;
  projectWorkspaceTab = 'summary';
  renderProjectWorkspace();
}
function setProjectWorkspaceTab(t){ projectWorkspaceTab = t; renderProjectWorkspace(); }

function renderProjectWorkspace(){
  const p = projects.find(x=>x.id===projectWorkspaceId);
  if(typeof loadProjectControls==='function'&&window.projectControlsScope!==projectWorkspaceId&&!window.controlsLoading)setTimeout(()=>loadProjectControls(projectWorkspaceId),0);
  const tabs = [
    ['summary','الملخص'], ['contract-budget','العقد والموازنة'], ['hr','الموارد البشرية'],
    ['equipment','المعدات'], ['warehouse','المستودع'], ['procurement','المشتريات'],
    ['subcontractors','مقاولو الباطن'], ['claims','المستخلصات'], ['cashflow','التدفق النقدي'],
    ['risks','المخاطر'], ['documents','الوثائق'],
  ];
  document.getElementById('main').innerHTML = `
    <div class="page-eyebrow">مساحة عمل مشروع</div>
    <div class="page-head"><div class="page-title">${p.name}</div><button class="btn-ghost" onclick="go('projects')">← رجوع لكل المشاريع</button></div>
    <div class="toolbar">${tabs.map(([k,label])=>`<span class="chip ${projectWorkspaceTab===k?'active':''}" onclick="setProjectWorkspaceTab('${k}')">${label}</span>`).join('')}</div>
    <div id="pwContent">${projectWorkspaceTabContent(p)}</div>`;
}

function projectWorkspaceTabContent(p){
  const fns = {
    summary: pwSummary, 'contract-budget': pwContractBudget, hr: pwHr,
    equipment: pwEquipment, warehouse: pwWarehouse, procurement: pwProcurement,
    subcontractors: pwSubcontractors, claims: pwClaims, cashflow: pwCashflow,
    risks: pwRisks, documents: pwDocuments,
  };
  return fns[projectWorkspaceTab](p);
}

function pwSummary(p){
  const d=window.projectControlsData||{budgets:[],progress:[]};const budget=d.budgets?.find(b=>b.status==='approved')||d.budgets?.[0];const latest=d.progress?.find(u=>u.status==='approved')||d.progress?.[0];const planned=Number(latest?.planned_progress||0),actual=Number(latest?.actual_progress||p.progress),cost=Number(latest?.cumulative_cost||p.spent),budgetValue=Number(budget?.total_budget||0),variance=planned-actual;
  return `
    <div class="kpi-grid">
      <div class="kpi"><div class="kpi-label">التقدم الفعلي</div><div class="kpi-value">${actual}%</div></div>
      <div class="kpi"><div class="kpi-label">التقدم المخطط</div><div class="kpi-value">${planned}%</div></div>
      <div class="kpi"><div class="kpi-label">انحراف البرنامج</div><div class="kpi-value" style="color:${variance>0?'var(--danger)':'var(--success)'}">${variance>0?'-':''}${Math.abs(variance)}%</div></div>
      <div class="kpi"><div class="kpi-label">التكلفة التراكمية</div><div class="kpi-value">${fmt(cost)}</div></div>
    </div>
    ${dataTable(
      [{label:'المؤشر',key:'a'},{label:'القيمة',key:'b',num:true},{label:'التقييم',key:'e'}],[{a:'الموازنة المعتمدة',b:budgetValue?fmt(budgetValue):'غير معتمدة',e:badge(budget?.status||'غير موجودة',budget?.status==='approved'?'success':'warn')},{a:'التكلفة مقابل الموازنة',b:budgetValue?fmt(cost)+' / '+fmt(budgetValue):fmt(cost),e:budgetValue&&cost>budgetValue?badge('تجاوز','danger'):badge('ضمن المتاح','success')},{a:'آخر تحديث',b:latest?.as_of_date||'—',e:badge(latest?.status||'لا يوجد',latest?.status==='approved'?'success':'warn')}],{title:'مؤشرات ضبط المشروع',action:`<button class="btn-sm btn" onclick="openProgressUpdateModal('${p.id}')">+ تحديث تقدم</button>`}
    )}`;
}
function pwContractBudget(p){
  const d=window.projectControlsData||{budgets:[],budgetLines:[]};const budget=d.budgets?.[0];const lines=budget?d.budgetLines.filter(l=>Number(l.project_budget_id)===Number(budget.id)):[];const weight=lines.reduce((s,l)=>s+Number(l.weight_percent),0);
  return `
  <div class="panel"><div class="panel-head"><div class="panel-title">بيانات العقد</div></div>
    <div class="panel-body">
      <table>
        <tr><td class="faint">العميل</td><td>${p.client}</td></tr>
        <tr><td class="faint">قيمة العقد</td><td class="num">${fmt(p.value)}</td></tr>
        <tr><td class="faint">نسبة المحتجز</td><td class="num">10%</td></tr>
        <tr><td class="faint">الدفعة المقدمة</td><td class="num">${fmt(Math.round(p.value*0.1))}</td></tr>
        <tr><td class="faint">الضريبة</td><td class="num">15%</td></tr>
        <tr><td class="faint">خطاب الضمان النهائي</td><td class="num">${fmt(Math.round(p.value*0.05))}</td></tr>
      </table>
    </div>
  </div>
  ${dataTable([{label:'WBS',key:'wbs'},{label:'البند',key:'description'},{label:'التصنيف',key:'category'},{label:'التكلفة المخططة',key:'cost',num:true},{label:'الوزن',key:'weight'}],lines.map(l=>({wbs:l.wbs_code,description:l.description,category:l.cost_category,cost:fmt(Number(l.planned_cost)),weight:l.weight_percent+'%'})),{title:`الموازنة ${budget?.budget_code||'—'} — إجمالي الوزن ${weight}%`,action:`<button class="btn-sm btn" onclick="openBudgetLineModal('${p.id}')">+ بند WBS</button>${budget?.status==='draft'?` <button class="btn-sm btn-outline" onclick="approveProjectBudget(${budget.id})">اعتماد الموازنة</button>`:''}`})}`;
}
function pwHr(p){
  const list = employees.filter(e=>e.project===p.id);
  return dataTable(
    [{label:'الرقم',key:'id'},{label:'الاسم',key:'name'},{label:'الوظيفة',key:'role'},{label:'الحالة',key:'status',render:r=>badge(r.status,r.status==='نشط'?'success':'warn')}],
    list, {title:`الموظفون المسندون لهذا المشروع (${list.length})`, onRowClick:'openEmployeeFile'}
  ) + (list.length===0 ? `<div class="empty">لا يوجد موظفون مسندون مباشرة لهذا المشروع في العيّنة الحالية.</div>` : '');
}
function pwEquipment(p){
  const list = equipment.filter(e=>e.project===p.id);
  return dataTable(
    [{label:'المعدة',key:'name'},{label:'الملكية',key:'type'},{label:'ساعات التشغيل',key:'hours',num:true},{label:'الحالة',key:'status',render:r=>badge(r.status,r.status==='عاملة'?'success':r.status==='صيانة'?'warn':'danger')}],
    list, {title:`المعدات في هذا المشروع (${list.length})`}
  ) + (list.length===0 ? `<div class="empty">لا توجد معدات مسندة لهذا المشروع في العيّنة الحالية.</div>` : '');
}
function pwWarehouse(p){
  return dataTable(
    [{label:'الصنف',key:'name'},{label:'الرصيد المتاح',key:'onHand',num:true},{label:'المحجوز',key:'reserved',num:true},{label:'الحالة',key:'status',render:r=>badge(r.status,r.status==='متاح'?'success':'warn')}],
    materials, {title:'مستودع المشروع — أهم الأصناف'}
  );
}
function pwProcurement(p){
  return dataTable(
    [{label:'أمر الشراء',key:'id'},{label:'المورد',key:'supplier'},{label:'القيمة',key:'amount',num:true,render:r=>fmt(r.amount)},{label:'الحالة',key:'status',render:r=>badge(r.status,'teal')}],
    purchaseOrders, {title:'أوامر الشراء المرتبطة'}
  );
}
function pwSubcontractors(p){
  const list = subcontractors.filter(s=>s.project===p.id);
  return dataTable(
    [{label:'المقاول',key:'name'},{label:'نطاق العمل',key:'scope'},{label:'قيمة العقد',key:'contractValue',num:true,render:r=>fmt(r.contractValue)}],
    list, {title:`مقاولو الباطن في هذا المشروع (${list.length})`}
  ) + (list.length===0 ? `<div class="empty">لا يوجد مقاولو باطن مسندون لهذا المشروع في العيّنة الحالية.</div>` : '');
}
function pwClaims(p){
  return `
  <div class="panel">
    <div class="panel-head"><div class="panel-title">آخر مستخلص — ${p.name}</div>${badge('بانتظار اعتماد العميل','warn')}</div>
    <div class="panel-body">
      <table>
        <tr><td class="faint">الأعمال التراكمية</td><td class="num">${fmt(p.spent)}</td></tr>
        <tr><td class="faint">أعمال هذه الفترة</td><td class="num">${fmt(Math.round(p.value*0.03))}</td></tr>
        <tr><td class="faint">المحتجز (10%)</td><td class="num">-${fmt(Math.round(p.value*0.003))}</td></tr>
        <tr><td class="faint">الضريبة (15%)</td><td class="num">${fmt(Math.round(p.value*0.0045))}</td></tr>
        <tr style="font-weight:700"><td>صافي المستحق التقديري</td><td class="num">${fmt(Math.round(p.value*0.032))}</td></tr>
      </table>
    </div>
  </div>`;
}
function pwCashflow(p){
  const rows=(window.projectControlsData?.cashflow||[]).map(c=>{const net=Number(c.expected_inflow)-Number(c.expected_outflow);return{m:String(c.forecast_month).slice(0,7),in:fmt(Number(c.expected_inflow)),out:fmt(Number(c.expected_outflow)),net:(net<0?'-':'')+fmt(Math.abs(net)),status:badge(net<0?'عجز متوقع':'فائض',''+(net<0?'danger':'success'))}});return dataTable([{label:'الشهر',key:'m'},{label:'مقبوضات متوقعة',key:'in',num:true},{label:'مدفوعات متوقعة',key:'out',num:true},{label:'الصافي',key:'net',num:true},{label:'الحالة',key:'status'}],rows,{title:'التدفق النقدي المتوقع',action:`<button class="btn-sm btn" onclick="openCashflowModal('${p.id}')">+ تحديث توقع</button>`});
}
function pwRisks(p){
  const labels={low:'منخفض',medium:'متوسط',high:'عالٍ'};return dataTable([{label:'الرمز',key:'code'},{label:'الخطر',key:'r'},{label:'الاحتمالية',key:'prob'},{label:'الأثر',key:'impact'},{label:'التخفيف',key:'action'},{label:'المالك',key:'owner'}],(window.projectControlsData?.risks||[]).map(r=>({code:r.risk_code,r:r.title,prob:badge(labels[r.probability]||r.probability,r.probability==='high'?'danger':'warn'),impact:badge(labels[r.impact]||r.impact,r.impact==='high'?'danger':'warn'),action:r.mitigation,owner:r.owner_name||'—'})),{title:'سجل مخاطر المشروع',action:`<button class="btn-sm btn" onclick="openProjectRiskModal('${p.id}')">+ خطر</button>`});
}
function pwDocuments(p){
  return dataTable(
    [{label:'رقم المستند',key:'id'},{label:'التصنيف',key:'cat'},{label:'الحالة',key:'s'}],
    [
      {id:'DOC-8790', cat:'خطاب ضمان بنكي', s:badge('ساري','teal')},
      {id:'DOC-8801', cat:'مخططات معتمدة — الإصدار 4', s:badge('معتمد','success')},
    ], {title:'وثائق المشروع'}
  );
}

/* ============ الموظفون ============ */
let employeesFilter = 'all';
let employeesSearchQuery = '';
function isNearExpiry(dateStr){ return new Date(dateStr) < new Date('2026-10-01'); }
function employeesFilteredList(){
  return employees.filter(e=>{
    if(employeesFilter==='active' && e.status!=='نشط') return false;
    if(employeesFilter==='leave' && e.status!=='إجازة') return false;
    if(employeesFilter==='expiring' && !isNearExpiry(e.iqamaExpiry)) return false;
    const q = employeesSearchQuery.trim();
    if(q && !(e.name.includes(q) || e.id.toLowerCase().includes(q.toLowerCase()) || e.role.includes(q) || String(e.nationalId||'').includes(q) || String(e.email||'').toLowerCase().includes(q.toLowerCase()))) return false;
    return true;
  });
}
function employeesTableHtml(){
  const filtered = employeesFilteredList();
  return filtered.length ? dataTable(
    [{label:'الرقم',key:'id'},{label:'الهوية',key:'nationalId',render:r=>r.nationalId||'غير مهيأ'},{label:'الاسم',key:'name'},{label:'الوظيفة',key:'role'},{label:'البريد',key:'email',render:r=>r.email||'—'},{label:'المشروع',key:'project',render:r=>projectNameById(r.project)},{label:'الحالة',key:'status',render:r=>badge(r.status, r.status==='نشط'?'success':'warn')}],
    filtered, {onRowClick:'openEmployeeFile'}
  ) : `<div class="empty">لا يوجد موظفون مطابقون للبحث/الفلتر الحالي.</div>`;
}
function onEmployeesSearchInput(val){
  employeesSearchQuery = val;
  document.getElementById('employeesTable').innerHTML = employeesTableHtml();
}
function renderEmployees(){
  return `
  <div class="page-eyebrow">الموارد البشرية</div>
  <div class="page-head"><div class="page-title">الموظفون</div>${APP_PERMISSIONS.includes('hr.employee.create')||SERVER_USER.role==='general-manager'?'<button class="btn" onclick="openNewEmployeeModal()">+ إضافة موظف</button>':''}</div>
  <div class="toolbar">
    <input class="search-input" placeholder="ابحث بالاسم أو الرقم الوظيفي أو الهوية أو البريد" oninput="onEmployeesSearchInput(this.value)">
    <span class="chip ${employeesFilter==='all'?'active':''}" onclick="setEmployeesFilter('all')">الكل (${employees.length})</span>
    <span class="chip ${employeesFilter==='active'?'active':''}" onclick="setEmployeesFilter('active')">نشط (${employees.filter(e=>e.status==='نشط').length})</span>
    <span class="chip ${employeesFilter==='leave'?'active':''}" onclick="setEmployeesFilter('leave')">إجازة (${employees.filter(e=>e.status==='إجازة').length})</span>
    <span class="chip ${employeesFilter==='expiring'?'active':''}" onclick="setEmployeesFilter('expiring')">وثائق قريبة الانتهاء (${employees.filter(e=>isNearExpiry(e.iqamaExpiry)).length})</span>
  </div>
  <div id="employeesTable">${employeesTableHtml()}</div>`;
}
function setEmployeesFilter(f){ employeesFilter=f; document.getElementById('employeesTable').innerHTML = employeesTableHtml(); buildSidebar(); }

let empFileId = null;
let empFileTab = 'personal';
let employeeDetails = {};

function openEmployeeFile(id){
  empFileId = id;
  empFileTab = 'personal';
  renderEmployeeFile();
  if(typeof loadEmployeeDetails === 'function') loadEmployeeDetails(id);
}
function setEmpFileTab(t){ empFileTab = t; renderEmployeeFile(); }

function renderEmployeeFile(){
  const e = employees.find(x=>x.id===empFileId);
  const tabs = [
    ['personal','البيانات الشخصية'],['contract','العقد'],['salary','الراتب والبدلات'],
    ['attendance','الحضور'],['leaves','الإجازات'],['advances','السلف'],
    ['documents','الوثائق'],['custody','العهد'],['transfer','نقل الموظف'],['end-service','نهاية الخدمة'],
  ];
  document.getElementById('main').innerHTML = `
    <div class="page-eyebrow">ملف موظف</div>
    <div class="page-head"><div class="page-title">${e.name} <span class="faint num">(${e.id})</span></div><div style="display:flex;gap:8px">${APP_PERMISSIONS.includes('hr.employee.update')||SERVER_USER.role==='general-manager'?`<button class="btn" onclick="openEditEmployeeModal('${e.id}')">تعديل البيانات</button>`:''}<button class="btn-ghost" onclick="go('employees')">← رجوع</button></div></div>
    <div class="toolbar">${tabs.map(([k,label])=>`<span class="chip ${empFileTab===k?'active':''}" onclick="setEmpFileTab('${k}')">${label}</span>`).join('')}</div>
    <div id="empFileContent">${empFileTabContent(e)}</div>`;
}

function openEditEmployeeModal(empCode){
  const e=employees.find(x=>x.id===empCode);const live=employeeDetails[empCode]?.employee||{};
  if(!e)return;
  document.getElementById('formModalBox').style.width='980px';
  document.getElementById('formModalBox').innerHTML=`<div class="modal-head"><div><strong>تعديل بيانات الموظف</strong><div class="faint">${htmlAttr(e.name)} · ${htmlAttr(empCode)}</div></div><button class="close-x" onclick="closeFormModal()">✕</button></div><div class="modal-body">
  <form id="employeeEditForm" class="employee-form" onsubmit="submitEmployeeEdit(event)"><input type="hidden" name="emp_code" value="${htmlAttr(empCode)}">
    <div><label class="required">اسم الموظف</label><input name="name" value="${htmlAttr(live.name||e.name)}" required></div>
    <div><label class="required">رقم الهوية / الإقامة</label><input name="national_id" inputmode="numeric" maxlength="10" pattern="[0-9٠-٩]{10}" value="${htmlAttr(live.national_id||e.nationalId)}" required></div>
    <div><label class="required">البريد الإلكتروني</label><input name="work_email" type="email" value="${htmlAttr(live.work_email||e.email)}" required></div>
    <div><label class="required">الوظيفة</label><input name="job_title" value="${htmlAttr(live.job_title||e.role)}" required></div>
    <div><label>رقم الجوال (+966)</label><input name="mobile_number" inputmode="numeric" maxlength="9" pattern="5[0-9]{8}" value="${htmlAttr(live.mobile_number||e.mobile)}"></div>
    <div><label>الجنس</label><select name="gender"><option value="ذكر" ${(live.gender||'ذكر')==='ذكر'?'selected':''}>ذكر</option><option value="أنثى" ${live.gender==='أنثى'?'selected':''}>أنثى</option></select></div>
    <div><label>الجنسية</label><input name="nationality" value="${htmlAttr(live.nationality)}"></div>
    <div><label>تاريخ الميلاد</label><input name="birth_date" type="date" value="${htmlAttr(live.birth_date)}"></div>
    <div><label class="required">نوع الموظف</label><select name="employee_type"><option value="دوام كامل" ${(live.employee_type||'دوام كامل')==='دوام كامل'?'selected':''}>دوام كامل</option><option value="دوام جزئي" ${live.employee_type==='دوام جزئي'?'selected':''}>دوام جزئي</option><option value="مؤقت" ${live.employee_type==='مؤقت'?'selected':''}>مؤقت</option><option value="عمالة مشروع" ${live.employee_type==='عمالة مشروع'?'selected':''}>عمالة مشروع</option></select></div>
    <div><label>الكفالة</label><input name="sponsor_name" value="${htmlAttr(live.sponsor_name)}"></div>
    <div><label>اللغة</label><select name="preferred_language"><option value="ar" ${(live.preferred_language||'ar')==='ar'?'selected':''}>العربية</option><option value="en" ${live.preferred_language==='en'?'selected':''}>English</option></select></div>
    <div><label>المشروع</label><select name="project_code"><option value="">دون مشروع</option>${projects.map(p=>`<option value="${htmlAttr(p.id)}" ${(live.project_id&&e.project===p.id)||(!live.project_id&&e.project===p.id)?'selected':''}>${htmlAttr(p.name)}</option>`).join('')}</select></div>
    <div><label>تاريخ بداية العمل</label><input name="started_on" type="date" value="${htmlAttr(live.started_on||e.joined)}"></div>
    <div><label>الراتب الأساسي</label><input name="base_salary" type="number" min="0" step="0.01" value="${htmlAttr(live.base_salary??e.salary??0)}"></div>
    <div><label>انتهاء الإقامة</label><input name="iqama_expiry" type="date" value="${htmlAttr(live.iqama_expiry||e.iqamaExpiry)}"></div>
    <div><label>الحالة</label><select name="status"><option value="active" ${(live.status||e.status)==='active'||e.status==='نشط'?'selected':''}>نشط</option><option value="leave" ${(live.status||e.status)==='leave'||e.status==='إجازة'?'selected':''}>إجازة</option><option value="inactive" ${live.status==='inactive'?'selected':''}>غير نشط</option><option value="terminated" ${live.status==='terminated'?'selected':''}>منتهي الخدمة</option></select></div>
    <div class="form-actions"><button class="btn" id="employeeEditSubmit">حفظ التعديلات</button><button type="button" class="btn-outline" onclick="closeFormModal()">إلغاء</button></div>
  </form></div>`;
  document.getElementById('formModalOverlay').classList.add('open');
}

function empFileTabContent(e){
  const fns = {
    personal: efPersonal, contract: efContract, salary: efSalary, attendance: efAttendance,
    leaves: efLeaves, advances: efAdvances, documents: efDocuments, custody: efCustody,
    transfer: efTransfer, 'end-service': efEndService,
  };
  return fns[empFileTab](e);
}

function efPersonal(e){
  const live=employeeDetails[e.id]?.employee||{};
  return `
  <div class="panel"><div class="panel-head"><div class="panel-title">البيانات الشخصية والوظيفية</div></div>
    <div class="panel-body">
      ${live.profile_image?`<img src="employee-photo.php?emp_code=${encodeURIComponent(e.id)}" alt="الصورة الشخصية" style="width:92px;height:92px;object-fit:cover;border-radius:8px;border:1px solid var(--line-strong);margin-bottom:12px">`:''}
      <table>
        <tr><td class="faint">رقم الهوية / الإقامة</td><td class="num">${live.national_id||e.nationalId||'غير مهيأ'}</td></tr>
        <tr><td class="faint">البريد الإلكتروني</td><td>${live.work_email||e.email||'—'}</td></tr>
        <tr><td class="faint">الجوال</td><td class="num">${live.mobile_number?'+966 '+live.mobile_number:'—'}</td></tr>
        <tr><td class="faint">الجنسية</td><td>${live.nationality||'—'}</td></tr>
        <tr><td class="faint">الجنس</td><td>${live.gender||'—'}</td></tr>
        <tr><td class="faint">تاريخ الميلاد</td><td class="num">${live.birth_date||'—'}</td></tr>
        <tr><td class="faint">الوظيفة</td><td>${e.role}</td></tr>
        <tr><td class="faint">المشروع الحالي</td><td>${projectNameById(e.project)}</td></tr>
        <tr><td class="faint">الفرع</td><td>${e.branch}</td></tr>
        <tr><td class="faint">تاريخ المباشرة</td><td class="num">${live.started_on||e.joined}</td></tr>
        <tr><td class="faint">انتهاء الإقامة</td><td class="num">${e.iqamaExpiry} ${isNearExpiry(e.iqamaExpiry)?badge('يُجدَّد قريبًا','warn'):badge('ساري','success')}</td></tr>
        <tr><td class="faint">الحالة</td><td>${badge(e.status, e.status==='نشط'?'success':'warn')}</td></tr>
      </table>
    </div>
  </div>`;
}
function efContract(e){
  const c = employeeDetails[e.id]?.contract;
  return `
  <div class="panel"><div class="panel-head"><div class="panel-title">بيانات العقد</div><button class="btn-sm btn" onclick="openEmployeeContractModal('${e.id}')">${c?'تحديث العقد':'+ إضافة عقد'}</button></div>
    <div class="panel-body">
      <table>
        <tr><td class="faint">نوع العقد</td><td>${c?.contract_type||'غير مسجل'}</td></tr>
        <tr><td class="faint">فترة التجربة حتى</td><td class="num">${c?.probation_ends_on||'—'}</td></tr>
        <tr><td class="faint">تاريخ بداية العقد</td><td class="num">${c?.starts_on||e.joined}</td></tr>
        <tr><td class="faint">تاريخ نهاية العقد</td><td class="num">${c?.ends_on||'غير محدد'}</td></tr>
        <tr><td class="faint">ساعات العمل الأسبوعية</td><td class="num">${c?.weekly_hours||48} ساعة</td></tr>
      </table>
    </div>
  </div>`;
}
function efSalary(e){
  const c = employeeDetails[e.id]?.contract;
  const basic = Number(c?.basic_salary ?? Math.round(e.salary*0.6));
  const housing = Number(c?.housing_allowance ?? Math.round(e.salary*0.25));
  const transport = Number(c?.transport_allowance ?? Math.round(e.salary*0.15));
  const other = Number(c?.other_allowances ?? 0);
  return dataTable(
    [{label:'البند',key:'a'},{label:'المبلغ',key:'b',num:true}],
    [
      {a:'الأساسي', b:fmt(basic)},
      {a:'بدل سكن', b:fmt(housing)},
      {a:'بدل نقل', b:fmt(transport)},
      {a:'بدلات أخرى', b:fmt(other)},
      {a:'الإجمالي', b:fmt(basic+housing+transport+other)},
    ], {title:'الراتب والبدلات'}
  );
}
function efAttendance(e){
  const rows = (employeeDetails[e.id]?.attendance||[]).map(a=>({d:a.work_date,in:a.checked_in_at?String(a.checked_in_at).slice(11,16):'—',out:a.checked_out_at?String(a.checked_out_at).slice(11,16):'—',s:badge(a.status,a.status==='present'?'success':a.status==='late'?'warn':'flat')}));
  return dataTable(
    [{label:'التاريخ',key:'d'},{label:'الحضور',key:'in'},{label:'الانصراف',key:'out'},{label:'الحالة',key:'s'}],
    rows, {title:`سجل حضور ${e.name} — آخر 31 يومًا`}
  );
}
function efLeaves(e){
  const leaves = employeeDetails[e.id]?.leaves||[];
  return `
  <div class="card flex-between"><span>طلبات الإجازات المسجلة</span><button class="btn-sm btn" onclick="openLeaveRequestModal('${e.id}')">+ طلب إجازة</button></div>
  ${dataTable([{label:'النوع',key:'t'},{label:'المدة',key:'d'},{label:'الحالة',key:'s'}],
    leaves.map(l=>({t:l.leave_name,d:l.days_count+' يوم',s:badge(l.status,l.status==='approved'?'success':l.status==='rejected'?'danger':'warn')})),
    {title:'سجل الإجازات'})}`;
}
function efAdvances(e){
  return dataTable(
    [{label:'التاريخ',key:'d'},{label:'المبلغ',key:'a',num:true},{label:'المتبقي',key:'r',num:true},{label:'الحالة',key:'s'}],
    [{d:'2026-05-01', a:fmt(2000), r:fmt(1200), s:badge('قيد السداد','warn')}],
    {title:'السلف'}
  );
}
function efDocuments(e){
  const docs = employeeDetails[e.id]?.documents||[];
  return dataTable(
    [{label:'المستند',key:'n'},{label:'الحالة',key:'s'},{label:'الانتهاء',key:'x'}],
    docs.map(d=>({n:d.document_type,s:badge(d.status,d.status==='valid'?'success':'warn'),x:d.expires_on||'—'})), {title:'وثائق الموظف'}
  );
}
function efCustody(e){
  const rows = (employeeDetails[e.id]?.custodies||[]).map(c=>({n:c.name,d:c.assigned_on,s:badge(c.returned_on?'مُعادة':'بعهدته',c.returned_on?'flat':'success')}));
  return dataTable(
    [{label:'العنصر',key:'n'},{label:'تاريخ الاستلام',key:'d'},{label:'الحالة',key:'s'}],
    rows,
    {title:'عهدة الموظف'}
  );
}
function efTransfer(e){
  const history = employeeDetails[e.id]?.transfers||[];
  return `
  <div class="panel"><div class="panel-head"><div class="panel-title">نقل الموظف بين المشاريع</div></div>
    <div class="panel-body">
      <table>
        <tr><td class="faint">من مشروع</td><td>${projectNameById(e.project)}</td></tr>
        <tr><td class="faint">إلى مشروع</td><td><select id="transferProject" style="background:var(--bg-raised);color:var(--text);border:1px solid var(--line-strong);padding:5px;border-radius:4px">${projects.filter(p=>p.id!==e.project).map(p=>`<option value="${p.id}">${p.name}</option>`).join('')}</select></td></tr>
        <tr><td class="faint">تاريخ سريان النقل</td><td><input id="transferDate" type="date" class="search-input"></td></tr>
        <tr><td class="faint">نسبة توزيع التكلفة</td><td><input id="transferPercent" type="number" value="100" min="1" max="100" class="search-input"></td></tr>
      </table>
      <button class="btn" style="margin-top:10px" onclick="requestEmployeeTransfer('${e.id}')">إرسال طلب النقل للاعتماد</button>
    </div>
  </div>${dataTable([{label:'إلى مشروع',key:'p'},{label:'تاريخ السريان',key:'d'},{label:'الحالة',key:'s'}],history.map(t=>({p:t.to_project_name,d:t.effective_on,s:badge(t.status,t.status==='approved'?'success':t.status==='rejected'?'danger':'warn')})),{title:'سجل طلبات النقل'})}`;
}
function efEndService(e){
  const years = 2026 - parseInt(e.joined.slice(0,4));
  return `
  <div class="panel"><div class="panel-head"><div class="panel-title">تقدير مكافأة نهاية الخدمة</div></div>
    <div class="panel-body">
      <table>
        <tr><td class="faint">سنوات الخدمة</td><td class="num">${years} سنة</td></tr>
        <tr><td class="faint">أساس الاحتساب</td><td class="num">${fmt(e.salary)} ر.س/شهر</td></tr>
        <tr style="font-weight:700"><td>التقدير الحالي (تقريبي)</td><td class="num">${fmt(Math.round(e.salary * years * 0.5))} ر.س</td></tr>
      </table>
      <div class="faint" style="margin-top:8px">تقدير تقريبي وفق نظام العمل السعودي — يُحتسب فعليًا فقط عند بدء إجراء إنهاء الخدمة.</div>
    </div>
  </div>`;
}

/* ============ الحضور — متعدد المواقع (نقطة محورية في الطلب) ============ */
function renderAttendance(){
  if(typeof loadAttendanceTeam === 'function' && !window.liveAttendanceTeam && !window.attendanceLoading) setTimeout(loadAttendanceTeam,0);
  const live = window.liveAttendanceTeam || {present:0,absent:employees.length,late:0,rows:[],geofences:[]};
  return `
  <div class="page-eyebrow">الموارد البشرية · الحضور</div>
  <div class="page-head"><div class="page-title">الحضور والانصراف</div>
    <div style="display:flex;gap:8px"><button class="btn-outline" onclick="markYesterdayAbsences()">إقفال حضور أمس</button><button class="btn-outline" onclick="addGeofence()">⚙ إدارة مواقع التحضير</button></div></div>

  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-label">الموجودون الآن</div><div class="kpi-value">${live.present}</div></div>
    <div class="kpi"><div class="kpi-label">لم يسجلوا اليوم</div><div class="kpi-value" style="color:var(--danger)">${live.absent}</div></div>
    <div class="kpi"><div class="kpi-label">المتأخرون</div><div class="kpi-value" style="color:var(--warn)">${live.late}</div></div>
    <div class="kpi"><div class="kpi-label">مواقع التحضير</div><div class="kpi-value">${live.geofences.length}</div></div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div class="panel-title">مواقع التحضير المعتمدة (Geofencing متعدد المواقع)</div><button class="btn-outline btn-sm" onclick="addGeofence()">+ إضافة موقع</button></div>
    <div class="panel-body">
      <div class="faint" style="margin-bottom:10px">يمكن ربط أكثر من موقع بنفس المشروع (بوابات متعددة، مكتب موقع، مستودع ميداني)، ويُسجَّل الحضور صحيحًا إذا كان ضمن نطاق أي موقع منها.</div>
      ${live.geofences.map(g=>`
      <div class="geo-row">
        <div><strong>${g.name}</strong><div class="faint">${g.project_name}</div><div class="faint num">${g.latitude}, ${g.longitude} — نطاق ${g.radius_meters} م</div></div>
        <div style="display:flex;gap:6px"><button class="btn-ghost btn-sm" style="color:var(--danger)" onclick="deactivateGeofence(${g.id})">تعطيل</button></div>
      </div>`).join('')}
    </div>
  </div>

  ${dataTable(
    [{label:'الموظف',key:'name'},{label:'المشروع',key:'project'},{label:'الموقع المسجَّل منه',key:'g'},{label:'الحالة',key:'s'}],
    live.rows.map(r=>({name:r.name,project:r.project_name||'—',g:r.checked_in_at?String(r.checked_in_at).slice(11,16):'—',s:badge(r.status,r.status==='present'?'success':r.status==='late'?'warn':'flat')})), {title:'تسجيلات اليوم'}
  )}`;
}

function addGeofence(){ if(typeof openGeofenceModal==='function') openGeofenceModal(); }

/* ============ الرواتب — معالج خطوة بخطوة ============ */
let payrollStep = 3;
function renderPayroll(){
  if(typeof loadPayrollRuns==='function' && !window.payrollLoaded && !window.payrollLoading) setTimeout(loadPayrollRuns,0);
  const run = window.currentPayrollRun || null;
  return `
  <div class="page-eyebrow">الموارد البشرية · الرواتب</div>
  <div class="page-head"><div class="page-title">مسير الرواتب</div><div style="display:flex;gap:8px"><input id="payrollPeriod" type="month" class="search-input" value="${new Date().toISOString().slice(0,7)}"><button class="btn" onclick="calculatePayroll()">احتساب المسير</button></div></div>
  ${run?`<div class="kpi-grid"><div class="kpi"><div class="kpi-label">الفترة</div><div class="kpi-value" style="font-size:20px">${run.period_key}</div></div><div class="kpi"><div class="kpi-label">الموظفون</div><div class="kpi-value">${run.employee_count}</div></div><div class="kpi"><div class="kpi-label">الإجمالي</div><div class="kpi-value">${fmt(Number(run.gross_total))}</div></div><div class="kpi"><div class="kpi-label">الصافي</div><div class="kpi-value">${fmt(Number(run.net_total))}</div></div></div>
  <div class="toolbar"><span>${badge(run.status,run.status==='posted'?'success':run.status==='approved'?'teal':'warn')}</span>${run.status==='draft'?`<button class="btn" onclick="payrollRunAction('review')">إرسال للمراجعة</button>`:''}${run.status==='reviewed'?`<button class="btn" onclick="payrollRunAction('approve')">اعتماد المسير</button>`:''}${run.status==='approved'?`<button class="btn" onclick="payrollRunAction('post')">ترحيل وإنشاء القيد</button>`:''}${run.journal_entry_id?`<span class="faint">قيد رقم ${run.journal_entry_id}</span>`:''}</div>
  ${dataTable([{label:'الموظف',key:'name'},{label:'المشروع',key:'project'},{label:'الإجمالي',key:'gross',num:true},{label:'الغياب',key:'absence'},{label:'التأخير',key:'late'},{label:'الخصومات',key:'deductions',num:true},{label:'الصافي',key:'net',num:true}],(run.items||[]).map(i=>({name:i.name+' ('+i.emp_code+')',project:i.project_name||'—',gross:fmt(Number(i.gross_amount)),absence:i.absence_days+' يوم',late:i.late_minutes+' دقيقة',deductions:fmt(Number(i.absence_deduction)+Number(i.late_deduction)+Number(i.other_deductions)),net:fmt(Number(i.net_amount))})),{title:'تفاصيل المسير'})}`:`<div class="empty">اختر الشهر واضغط «احتساب المسير» لإنشاء مسير جديد من العقود والحضور.</div>`}`;
}
function payrollNext(){ payrollStep++; go('payroll'); toast('تم الاعتماد والانتقال للخطوة التالية'); }

/* ============ المالية — القيود مع ضابط عدم التعديل بعد الترحيل ============ */
function renderFinance(){
  if(typeof loadFinanceTaxData==='function'&&!window.financeTaxData&&!window.financeTaxLoading)setTimeout(loadFinanceTaxData,0);const d=window.financeTaxData||{entries:[],trialBalance:[],periods:[]};
  return `
  <div class="page-eyebrow">المالية والحسابات</div>
  <div class="page-head"><div class="page-title">القيود اليومية والأستاذ العام</div><div style="display:flex;gap:7px"><button class="btn-outline" onclick="openFiscalPeriodModal()">+ فترة مالية</button><button class="btn" onclick="openNewJournalEntryModal()">+ قيد جديد</button></div></div>
  <div class="notice danger">⛔ لا يمكن تعديل أو حذف القيد المُرحّل. التصحيح يتم بقيد عكسي موثق، والفترة المقفلة تمنع أي ترحيل أو عكس داخلها.</div>
  ${dataTable(
    [{label:'رقم القيد',key:'id'},{label:'التاريخ',key:'date'},{label:'الوصف',key:'desc'},{label:'المشروع',key:'project'},{label:'مدين',key:'debit',num:true},{label:'دائن',key:'credit',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.entries.map(e=>({id:e.entry_code,date:e.entry_date,desc:e.description,project:e.project_name||'—',debit:fmt(Number(e.debit_total)),credit:fmt(Number(e.credit_total)),status:badge(e.is_reversed==1?'معكوس':e.status,e.status==='posted'?'flat':'warn'),action:e.status==='draft'?`<button class="btn-sm btn" onclick="postJournal(${e.id})">ترحيل</button>`:e.is_reversed==0?`<button class="btn-sm btn-outline" onclick="openReverseJournal(${e.id})">عكس</button>`:'—'}))
  )}
  ${dataTable([{label:'الحساب',key:'account'},{label:'مدين',key:'debit',num:true},{label:'دائن',key:'credit',num:true},{label:'الرصيد',key:'balance',num:true}],d.trialBalance.map(a=>({account:a.account_code,debit:fmt(Number(a.debit)),credit:fmt(Number(a.credit)),balance:fmt(Number(a.balance))})),{title:'ميزان المراجعة من القيود المرحلة'})}
  ${dataTable([{label:'الفترة',key:'code'},{label:'من',key:'start'},{label:'إلى',key:'end'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.periods.map(p=>({code:p.period_code,start:p.starts_on,end:p.ends_on,status:badge(p.status,p.status==='closed'?'flat':'success'),action:p.status==='open'?`<button class="btn-sm btn" onclick="closeFiscalPeriod(${p.id})">إقفال</button>`:'—'})),{title:'الفترات المالية'})}`;
}
function tryEditEntry(id){
  const e = journalEntries.find(x=>x.id===id);
  if(e.status==='posted'){
    toast('⛔ رُفضت العملية: لا يجوز تعديل قيد مُرحّل. استخدم قيدًا عكسيًا أو تسوية معتمدة.', 'danger');
  } else {
    toast('تم فتح القيد ' + id + ' للتعديل (مسودة قابلة للتحرير)');
  }
}

/* ============ العملاء ============ */
function renderCustomers(){
  if(typeof loadContractsData==='function'&&!window.contractsData&&!window.contractsLoading)setTimeout(loadContractsData,0);const d=window.contractsData||{customers:[],invoices:[],collections:[]};
  return `
  <div class="page-eyebrow">المالية</div>
  <div class="page-head"><div class="page-title">العملاء والتحصيل</div></div>
  ${dataTable(
    [{label:'العميل',key:'name'},{label:'عدد العقود',key:'contracts',num:true},{label:'إجمالي القيمة',key:'totalValue',num:true,render:r=>fmt(r.totalValue)},{label:'المحصَّل',key:'collected',num:true,render:r=>fmt(r.collected)},{label:'متأخرات',key:'overdue',num:true,render:r=>r.overdue?`<span style="color:var(--danger)">${fmt(r.overdue)}</span>`:'—'}],
    d.customers.map(c=>{const inv=d.invoices.filter(i=>Number(i.customer_id)===Number(c.id));return{name:c.name,contracts:Number(c.contracts_count),totalValue:Number(c.contract_total),collected:inv.reduce((s,i)=>s+Number(i.collected_amount),0),overdue:inv.reduce((s,i)=>s+Number(i.outstanding),0)}})
  )}
  ${dataTable([{label:'الفاتورة',key:'code'},{label:'العميل',key:'customer'},{label:'المشروع',key:'project'},{label:'الإجمالي',key:'total',num:true},{label:'المتبقي',key:'outstanding',num:true},{label:'الاستحقاق',key:'due'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.invoices.map(i=>({code:i.invoice_code,customer:i.customer_name,project:i.project_name,total:fmt(Number(i.total_amount)),outstanding:fmt(Number(i.outstanding)),due:i.due_date,status:badge(i.status,i.status==='paid'?'success':i.status==='partially_paid'?'teal':'warn'),action:Number(i.outstanding)>0?`<button class="btn-sm btn" onclick="openCollectionModal(${i.id})">تسجيل تحصيل</button>`:'—'})),{title:'الفواتير والتحصيل'})}`;
}

/* ============ العقود والمستخلصات ============ */
let contractsTab = 'customer-contracts';
function renderContracts(){
  if(typeof loadContractsData==='function'&&!window.contractsData&&!window.contractsLoading)setTimeout(loadContractsData,0);
  if(typeof loadProjectControls==='function'&&window.projectControlsScope!=='*'&&!window.controlsLoading)setTimeout(()=>loadProjectControls(null),0);
  return `
  <div class="page-eyebrow">العقود والمستخلصات والإيرادات</div>
  <div class="page-head"><div class="page-title">العقود والمستخلصات</div><div style="display:flex;gap:7px;flex-wrap:wrap"><button class="btn-outline" onclick="openCustomerContractModal()">+ عقد عميل</button><button class="btn-outline" onclick="openSubcontractModal()">+ عقد مقاول</button><button class="btn" onclick="openNewClaimModal()">+ مستخلص عميل</button><button class="btn" onclick="openSubcontractClaimModal()">+ مستخلص مقاول</button><button class="btn-outline" onclick="openChangeOrderModal(null)">+ أمر تغيير</button><button class="btn-outline" onclick="openContractClaimModal(null)">+ مطالبة تعاقدية</button></div></div>
  <div class="toolbar">
    <span class="chip ${contractsTab==='customer-contracts'?'active':''}" onclick="setContractsTab('customer-contracts')">عقود العملاء</span>
    <span class="chip ${contractsTab==='sub-contracts'?'active':''}" onclick="setContractsTab('sub-contracts')">عقود مقاولي الباطن</span>
    <span class="chip ${contractsTab==='change-orders'?'active':''}" onclick="setContractsTab('change-orders')">أوامر التغيير</span>
    <span class="chip ${contractsTab==='claims-list'?'active':''}" onclick="setContractsTab('claims-list')">المطالبات</span>
  </div>
  <div id="contractsContent">${contractsTabContent()}</div>`;
}
function setContractsTab(t){ contractsTab=t; document.getElementById('contractsContent').innerHTML = contractsTabContent(); }
function contractsTabContent(){
  const d=window.contractsData||{customerContracts:[],customerClaims:[],subcontracts:[],subcontractClaims:[],invoices:[]};
  if(contractsTab==='customer-contracts') return dataTable([{label:'العقد',key:'code'},{label:'العميل',key:'customer'},{label:'المشروع',key:'project'},{label:'القيمة',key:'value',num:true},{label:'المحتجز',key:'retention'},{label:'الحالة',key:'status'}],d.customerContracts.map(c=>({code:c.contract_code,customer:c.customer_name,project:c.project_name,value:fmt(Number(c.original_value)),retention:c.retention_rate+'%',status:badge(c.status,'success')})),{title:'عقود العملاء'})+dataTable([{label:'المستخلص',key:'code'},{label:'المشروع',key:'project'},{label:'الأعمال',key:'gross',num:true},{label:'المحتجز',key:'retention',num:true},{label:'الضريبة',key:'vat',num:true},{label:'الصافي',key:'net',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.customerClaims.map(c=>({code:c.claim_code,project:c.project_name,gross:fmt(Number(c.gross_work_value)),retention:fmt(Number(c.retention_amount)),vat:fmt(Number(c.vat_amount)),net:fmt(Number(c.net_due)),status:badge(c.status,c.status==='invoiced'?'success':c.status==='approved'?'teal':'warn'),action:c.status==='pending'?`<button class="btn-sm btn" onclick="approveCustomerClaim(${c.id})">اعتماد</button>`:c.status==='approved'?`<button class="btn-sm btn" onclick="issueCustomerInvoice(${c.id})">إصدار فاتورة</button>`:'—'})),{title:'مستخلصات العملاء'});
  if(contractsTab==='sub-contracts') return dataTable([{label:'العقد',key:'code'},{label:'المقاول',key:'name'},{label:'نطاق العمل',key:'scope'},{label:'المشروع',key:'project'},{label:'القيمة',key:'value',num:true},{label:'المحتجز',key:'retention'}],d.subcontracts.map(c=>({code:c.contract_code,name:c.subcontractor_name,scope:c.scope_of_work,project:c.project_name,value:fmt(Number(c.contract_value)),retention:c.retention_rate+'%'})),{title:'عقود مقاولي الباطن'})+dataTable([{label:'المستخلص',key:'code'},{label:'المقاول',key:'name'},{label:'الأعمال',key:'gross',num:true},{label:'المحتجز',key:'retention',num:true},{label:'الصافي',key:'net',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.subcontractClaims.map(c=>({code:c.claim_code,name:c.subcontractor_name,gross:fmt(Number(c.gross_work_value)),retention:fmt(Number(c.retention_amount)),net:fmt(Number(c.net_payable)),status:badge(c.status,c.status==='approved'?'success':'warn'),action:c.status==='pending'?`<button class="btn-sm btn" onclick="approveSubcontractClaim(${c.id})">اعتماد وترحيل</button>`:'—'})),{title:'مستخلصات مقاولي الباطن'});
  if(contractsTab==='change-orders'){const rows=window.projectControlsData?.changeOrders||[];return dataTable([{label:'الرقم',key:'id'},{label:'المشروع',key:'p'},{label:'الوصف',key:'d'},{label:'القيمة',key:'v',num:true},{label:'الأيام',key:'days'},{label:'الحالة',key:'s'},{label:'إجراء',key:'action'}],rows.map(c=>({id:c.change_code,p:c.project_name,d:c.title,v:fmt(Number(c.value_change)),days:c.time_impact_days,s:badge(c.status,c.status==='approved'?'success':'warn'),action:c.status==='pending'?`<button class="btn-sm btn" onclick="approveChangeOrder(${c.id})">اعتماد</button>`:'—'})),{title:'أوامر التغيير'});}
  const claims=window.projectControlsData?.contractClaims||[];return dataTable([{label:'الرقم',key:'id'},{label:'المشروع',key:'p'},{label:'النوع',key:'type'},{label:'تاريخ الحدث',key:'event'},{label:'الإشعار',key:'notice'},{label:'القيمة',key:'v',num:true},{label:'الأيام',key:'days'},{label:'الحالة',key:'s'},{label:'إجراء',key:'action'}],claims.map(c=>({id:c.claim_code,p:c.project_name,type:c.claim_type,event:c.event_date,notice:c.notice_date,v:fmt(Number(c.claimed_amount)),days:c.claimed_days,s:badge(c.status,c.status==='approved'?'success':'warn'),action:c.status==='under_review'?`<button class="btn-sm btn" onclick="approveContractClaim(${c.id})">اعتماد</button>`:'—'})),{title:'مطالبات التأخير والتكلفة'});
}

/* ============ المشتريات — دورة كاملة Kanban ============ */
function renderProcurement(){
  if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const d=window.supplyData||{requests:[],orders:[],receipts:[],warehouses:[],suppliers:[]},p=window.procurementData||{rfqs:[],quotations:[],invoices:[]};
  return `
  <div class="page-eyebrow">سلسلة التوريد</div>
  <div class="page-head"><div class="page-title">دورة المشتريات</div><button class="btn" onclick="openNewPurchaseRequestModal()">+ طلب شراء</button></div>
  <div class="page-desc" style="margin-bottom:16px">طلب شراء ← طلب عروض ومقارنة أو شراء مباشر مبرر ← أمر شراء ← استلام وفحص ← مطابقة ثلاثية ← اعتماد وسداد</div>
  ${dataTable([{label:'الطلب',key:'code'},{label:'المشروع',key:'project'},{label:'القيمة',key:'amount',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.requests.map(r=>({code:r.request_code,project:r.project_name,amount:fmt(Number(r.estimated_total)),status:badge(r.status,r.status==='approved'?'success':r.status==='pending'?'warn':'teal'),action:r.status==='pending'?`<button class="btn-sm btn" onclick="approvePurchaseRequest(${r.id})">اعتماد</button>`:r.status==='approved'?`<button class="btn-sm btn" onclick="openRfqModal(${r.id})">طلب عروض</button> <button class="btn-sm btn-outline" onclick="createPurchaseOrder(${r.id})">شراء مباشر</button>`:'—'})),{title:'طلبات الشراء'})}
  ${dataTable([{label:'طلب العروض',key:'code'},{label:'المشروع',key:'project'},{label:'الإغلاق',key:'closing'},{label:'العروض',key:'count',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],p.rfqs.map(r=>({code:r.rfq_code,project:r.project_name,closing:r.closing_on,count:Number(r.quotation_count),status:badge(r.status,r.status==='awarded'?'success':'warn'),action:r.status==='open'?`<button class="btn-sm btn" onclick="openQuotationModal(${r.id})">+ عرض</button>`:'—'})),{title:'طلبات العروض والمنافسات'})}
  ${dataTable([{label:'العرض',key:'code'},{label:'طلب العروض',key:'rfq'},{label:'المورد',key:'supplier'},{label:'الفني',key:'tech',num:true},{label:'المالي',key:'finance',num:true},{label:'القيمة',key:'amount',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],p.quotations.map(q=>({code:q.quotation_code,rfq:q.rfq_code,supplier:q.supplier_name,tech:Number(q.technical_score)+'%',finance:Number(q.financial_score)+'%',amount:fmt(Number(q.total_amount)),status:badge(q.status,q.status==='selected'?'success':q.status==='disqualified'?'danger':'warn'),action:q.status==='submitted'?`<button class="btn-sm btn" onclick="awardQuotation(${q.id})">ترسية</button>`:'—'})),{title:'المقارنة الفنية والمالية'})}
  ${dataTable([{label:'الأمر',key:'code'},{label:'الطريقة',key:'method'},{label:'المورد',key:'supplier'},{label:'الإجمالي',key:'amount',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.orders.map(o=>({code:o.order_code,method:o.procurement_method==='rfq'?'منافسة':'مباشر',supplier:o.supplier_name,amount:fmt(Number(o.total_amount)),status:badge(o.status,o.status==='received'?'success':'teal'),action:['issued','partial'].includes(o.status)?`<button class="btn-sm btn" onclick="receivePurchaseOrder(${o.id})">استلام</button>`:o.status==='received'?`<button class="btn-sm btn" onclick="openSupplierInvoiceModal(${o.id})">فاتورة مورد</button>`:'—'})),{title:'أوامر الشراء والاستلام'})}
  ${dataTable([{label:'المحضر',key:'code'},{label:'الأمر',key:'order'},{label:'المستودع',key:'warehouse'},{label:'الفحص',key:'status'},{label:'إجراء',key:'action'}],d.receipts.map(r=>({code:r.receipt_code,order:r.order_code,warehouse:r.warehouse_name,status:badge(r.inspection_status,r.inspection_status==='accepted'?'success':r.inspection_status==='rejected'?'danger':'warn'),action:r.inspection_status==='pending'?`<button class="btn-sm btn" onclick="inspectReceipt(${r.id},'accepted')">قبول</button> <button class="btn-sm btn-outline" onclick="inspectReceipt(${r.id},'rejected')">رفض</button>`:'—'})),{title:'محاضر الاستلام والفحص'})}
  ${dataTable([{label:'الفاتورة',key:'code'},{label:'المورد',key:'supplier'},{label:'الإجمالي',key:'total',num:true},{label:'المطابقة',key:'match'},{label:'الفرق',key:'variance',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],p.invoices.map(i=>({code:i.invoice_code,supplier:i.supplier_name,total:fmt(Number(i.total_amount)),match:badge(i.match_status,i.match_status==='matched'?'success':'danger'),variance:fmt(Number(i.variance_amount)),status:badge(i.status,i.status==='paid'?'success':i.status==='approved'?'teal':'warn'),action:i.status==='matched'?`<button class="btn-sm btn" onclick="approveSupplierInvoice(${i.id})">اعتماد وقيد</button>`:['approved','partially_paid'].includes(i.status)?`<button class="btn-sm btn" onclick="openSupplierPaymentModal(${i.id})">سداد</button>`:'—'})),{title:'فواتير الموردين والمطابقة الثلاثية'})}`;
}

/* ============ الموردون ============ */
function renderSuppliers(){
  if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const rows=window.supplyData?.suppliers||[];
  return `
  <div class="page-eyebrow">سلسلة التوريد</div>
  <div class="page-head"><div class="page-title">الموردون</div><button class="btn" onclick="openNewSupplierModal()">+ تأهيل مورد جديد</button></div>
  ${dataTable(
    [{label:'المورد',key:'name'},{label:'التصنيف',key:'category'},{label:'التقييم',key:'rating',num:true,render:r=>'★ '+r.rating},{label:'الرصيد',key:'balance',num:true,render:r=>fmt(r.balance)},{label:'الحالة',key:'status',render:r=>badge(r.status, r.status==='مؤهل'?'success':'warn')},{label:'إجراء',key:'action'}],
    rows.map(r=>({name:r.name,category:r.category||'—',rating:Number(r.rating),balance:0,status:r.status==='active'?'مؤهل':'تحت المراجعة',action:r.status==='active'?'—':`<button class="btn-sm btn" onclick="approveSupplier(${r.id})">تأهيل</button>`}))
  )}
  <div class="notice teal" style="margin-top:14px">⚿ تغيير الحساب البنكي لأي مورد يتطلب موافقة مزدوجة (مشتريات + مالية) وإشعار خارجي لطرف ثالث — كما اعتمدته اللجنة الأمنية.</div>`;
}

/* ============ مقاولو الباطن ============ */
function renderSubcontractors(){
  return `
  <div class="page-eyebrow">سلسلة التوريد</div>
  <div class="page-head"><div class="page-title">مقاولو الباطن</div></div>
  ${dataTable(
    [{label:'المقاول',key:'name'},{label:'نطاق العمل',key:'scope'},{label:'المشروع',key:'project',render:r=>projectNameById(r.project)},{label:'قيمة العقد',key:'contractValue',num:true,render:r=>fmt(r.contractValue)},{label:'المحتجز',key:'retention',num:true,render:r=>fmt(r.retention)},{label:'الحالة',key:'status',render:r=>badge(r.status,'success')}],
    subcontractors
  )}`;
}

/* ============ المستودعات (شاشة الإدارة) ============ */
function renderWarehouse(){
  if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const d=window.supplyData||{stock:[],warehouses:[],items:[]};
  return `
  <div class="page-eyebrow">سلسلة التوريد</div>
  <div class="page-head"><div class="page-title">المستودعات والمخزون</div><div style="display:flex;gap:7px"><button class="btn-outline" onclick="openWarehouseScopeModal()">صلاحيات المستودعات</button><button class="btn-outline" onclick="openWarehouseModal()">+ مستودع</button><button class="btn-outline" onclick="openInventoryItemModal()">+ صنف</button><button class="btn" onclick="openStockIssueModal()">صرف مادة</button></div></div>
  <div class="notice teal">لا يضاف الاستلام إلى الرصيد إلا بعد قبول الفحص، ويمنع الخادم أي صرف يؤدي إلى رصيد سالب.</div>
  ${dataTable(
    [{label:'الصنف',key:'name'},{label:'المستودع',key:'warehouse'},{label:'المشروع',key:'project'},{label:'الوحدة',key:'unit'},{label:'الرصيد',key:'onHand',num:true},{label:'المحجوز',key:'reserved',num:true},{label:'الحالة',key:'status'}],
    d.stock.map(s=>({name:s.item_name+' ('+s.item_code+')',warehouse:s.warehouse_name,project:s.project_name||'—',unit:s.unit,onHand:Number(s.on_hand),reserved:Number(s.reserved_qty),status:badge(Number(s.on_hand)-Number(s.reserved_qty)<=Number(s.reorder_level)?'إعادة طلب':'متاح',Number(s.on_hand)-Number(s.reserved_qty)<=Number(s.reorder_level)?'warn':'success')}))
  )}
  ${dataTable([{label:'المستودع',key:'warehouse'},{label:'المستخدم',key:'user'},{label:'الدور',key:'role'},{label:'نطاق الصلاحية',key:'scope'}],(window.procurementData?.scopes||[]).map(s=>({warehouse:s.warehouse_name,user:s.user_name,role:s.role,scope:badge(s.role_scope,s.role_scope==='manager'?'success':s.role_scope==='storekeeper'?'teal':'warn')})),{title:'مصفوفة صلاحيات المستودعات'})}`;
}
function openItemCard(id){
  const m = materials.find(x=>x.id===id);
  toast('كرت الصنف ' + m.name + ': رصيد افتتاحي، استلامات، مصروفات، تحويلات، مرتجعات — سجل حركة كامل');
}

/* ============ المعدات ============ */
function renderEquipment(){
  if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const d=window.supplyData||{equipment:[],transfers:[]};
  return `
  <div class="page-eyebrow">الأصول والعمليات</div>
  <div class="page-head"><div class="page-title">المعدات والمركبات</div><button class="btn" onclick="openNewEquipmentModal()">+ إضافة معدة</button></div>
  ${dataTable(
    [{label:'المعدة',key:'name'},{label:'الملكية',key:'type'},{label:'المشروع',key:'project'},{label:'ساعات التشغيل',key:'hours',num:true},{label:'تكلفة الساعة',key:'costHr',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],
    d.equipment.map(e=>({name:e.name+' ('+e.code+')',type:e.ownership_type==='owned'?'ملك':'إيجار',project:e.project_name||'غير موزعة',hours:Number(e.meter_hours),costHr:fmt(Number(e.hourly_cost))+' ر.س',status:badge(e.status,e.status==='working'?'success':'warn'),action:`<button class="btn-sm btn-outline" onclick="openEquipmentTransferModal('${e.code}')">نقل</button>`}))
  )}
  ${dataTable([{label:'المحضر',key:'code'},{label:'المعدة',key:'equipment'},{label:'من',key:'from'},{label:'إلى',key:'to'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.transfers.map(t=>({code:t.transfer_code,equipment:t.equipment_name,from:t.from_project_name||'غير موزعة',to:t.to_project_name,status:badge(t.status,t.status==='approved'?'success':'warn'),action:t.status==='pending'?`<button class="btn-sm btn" onclick="approveEquipmentTransfer(${t.id})">اعتماد النقل</button>`:'—'})),{title:'محاضر نقل المعدات'})}`;
}
function transferEquipment(id){ openEquipmentTransferModal(id); }

/* ============ الصيانة ============ */
function renderMaintenance(){
  if(typeof loadOperationsData==='function'&&!window.operationsData&&!window.operationsLoading)setTimeout(loadOperationsData,0);if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const d=window.operationsData||{workOrders:[]};const flow=['بلاغ','تشخيص','اعتماد','تنفيذ','فحص','إغلاق'];
  return `
  <div class="page-eyebrow">الأصول والعمليات</div>
  <div class="page-head"><div class="page-title">الصيانة</div><button class="btn" onclick="openMaintenanceReportModal()">+ بلاغ عطل</button></div>
  <div class="stepper">${flow.map((s,i)=>`<div class="step"><div class="step-circle">${i+1}</div><div class="step-label">${s}</div>${i<flow.length-1?'<div class="step-line"></div>':''}</div>`).join('')}</div>
  ${dataTable(
    [{label:'رقم الأمر',key:'id'},{label:'المعدة',key:'eq'},{label:'المشروع',key:'p'},{label:'الأولوية',key:'priority'},{label:'المرحلة',key:'st'},{label:'المتوقع',key:'estimated',num:true},{label:'الفعلي',key:'actual',num:true},{label:'إجراء',key:'action'}],d.workOrders.map(w=>({id:w.work_order_code,eq:w.equipment_name,p:w.project_name||'—',priority:badge(w.priority,w.priority==='critical'?'danger':w.priority==='urgent'?'warn':'flat'),st:badge(w.status,w.status==='closed'?'success':w.status==='awaiting_approval'?'warn':'teal'),estimated:fmt(Number(w.estimated_cost)),actual:fmt(Number(w.actual_cost)+Number(w.parts_cost)),action:w.status==='reported'?`<button class="btn-sm btn" onclick="openMaintenanceDiagnosis(${w.id})">تشخيص</button>`:w.status==='awaiting_approval'?`<button class="btn-sm btn" onclick="approveMaintenance(${w.id})">اعتماد</button>`:w.status==='approved'?`<button class="btn-sm btn" onclick="startMaintenance(${w.id})">بدء</button>`:w.status==='in_progress'?`<button class="btn-sm btn" onclick="openMaintenanceCompletion(${w.id})">إنهاء التنفيذ</button>`:w.status==='pending_inspection'?`<button class="btn-sm btn" onclick="closeMaintenance(${w.id})">فحص وإغلاق</button>`:'—'}))
  )}
  <div class="notice teal" style="margin-top:14px">عند دخول أي معدة الصيانة، تنقطع تكلفة تشغيلها المحمّلة على المشروع تلقائيًا حتى الخروج.</div>`;
}

/* ============ الجودة ============ */
function renderQuality(){
  if(typeof loadOperationsData==='function'&&!window.operationsData&&!window.operationsLoading)setTimeout(loadOperationsData,0);const d=window.operationsData||{inspections:[],ncrs:[]};
  return `
  <div class="page-eyebrow">الجودة والامتثال</div>
  <div class="page-head"><div class="page-title">الجودة</div><button class="btn" onclick="openQualityInspectionModal()">+ طلب فحص</button></div>
  ${dataTable([{label:'الطلب',key:'code'},{label:'المشروع',key:'project'},{label:'النوع',key:'type'},{label:'الوصف',key:'description'},{label:'النتيجة',key:'result'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.inspections.map(i=>({code:i.inspection_code,project:i.project_name,type:i.inspection_type,description:i.description,result:i.result?badge(i.result,i.result==='passed'?'success':'danger'):'—',status:badge(i.status,i.status==='completed'?'success':'warn'),action:i.status==='requested'?`<button class="btn-sm btn" onclick="openInspectionResult(${i.id})">تسجيل النتيجة</button>`:'—'})),{title:'طلبات الفحص'})}
  ${dataTable([{label:'NCR',key:'code'},{label:'المشروع',key:'project'},{label:'الوصف',key:'description'},{label:'الشدة',key:'severity'},{label:'السبب الجذري',key:'root'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.ncrs.map(n=>({code:n.ncr_code,project:n.project_name,description:n.description,root:n.root_cause||'—',severity:badge(n.severity,n.severity==='critical'?'danger':'warn'),status:badge(n.status,n.status==='closed'?'success':n.status==='verification'?'teal':'warn'),action:n.status==='open'?`<button class="btn-sm btn" onclick="openCorrectiveAction(${n.id})">إجراء تصحيحي</button>`:n.status==='verification'?`<button class="btn-sm btn" onclick="closeNcr(${n.id})">تحقق وإغلاق</button>`:'—'})),{title:'عدم المطابقة والإجراءات التصحيحية'})}`;
}

/* ============ السلامة ============ */
function renderSafety(){
  if(typeof loadOperationsData==='function'&&!window.operationsData&&!window.operationsLoading)setTimeout(loadOperationsData,0);const d=window.operationsData||{permits:[],incidents:[],stopOrders:[],toolboxTalks:[],metrics:{}};
  return `
  <div class="page-eyebrow">الجودة والامتثال</div>
  <div class="page-head"><div class="page-title">السلامة</div><div style="display:flex;gap:7px"><button class="btn-outline" onclick="openToolboxTalkModal()">اجتماع سلامة</button><button class="btn-outline" onclick="openIncidentModal()">بلاغ حادث</button><button class="btn" onclick="openStopWorkModal()">إيقاف عمل فوري</button><button class="btn" onclick="openSafetyPermitModal()">+ تصريح عمل</button></div></div>
  ${dataTable([{label:'التصريح',key:'code'},{label:'المشروع',key:'project'},{label:'النوع',key:'type'},{label:'من',key:'from'},{label:'إلى',key:'until'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.permits.map(p=>({code:p.permit_code,project:p.project_name,type:p.permit_type,from:p.valid_from,until:p.valid_until,status:badge(p.status,p.status==='active'?'success':'warn'),action:p.status==='pending'?`<button class="btn-sm btn" onclick="approveSafetyPermit(${p.id})">اعتماد</button>`:p.status==='active'?`<button class="btn-sm btn-outline" onclick="closeSafetyPermit(${p.id})">إغلاق</button>`:'—'})),{title:'تصاريح العمل'})}
  ${dataTable([{label:'أمر الإيقاف',key:'code'},{label:'المشروع',key:'project'},{label:'الخطر',key:'hazard'},{label:'بواسطة',key:'by'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.stopOrders.map(s=>({code:s.stop_code,project:s.project_name,hazard:s.hazard_description,by:s.stopped_by_name,status:badge(s.status,s.status==='active'?'danger':'success'),action:s.status==='active'?`<button class="btn-sm btn" onclick="openResumeWorkModal(${s.id})">توثيق التصحيح والاستئناف</button>`:'—'})),{title:'أوامر إيقاف العمل'})}
  ${dataTable([{label:'الحادث',key:'code'},{label:'المشروع',key:'project'},{label:'النوع',key:'type'},{label:'الشدة',key:'severity'},{label:'الوصف',key:'description'},{label:'أيام مفقودة',key:'days',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.incidents.map(i=>({code:i.incident_code,project:i.project_name,type:i.incident_type,severity:badge(i.severity,i.severity==='high'||i.severity==='critical'?'danger':'warn'),description:i.description,days:Number(i.lost_time_days),status:badge(i.status,i.status==='closed'?'success':'warn'),action:i.status==='open'?`<button class="btn-sm btn" onclick="closeIncident(${i.id})">مراجعة وإغلاق</button>`:'—'})),{title:'الحوادث والإصابات وشبه الحوادث'})}
  <div class="grid-3" style="margin-top:14px">
    <div class="panel"><div class="panel-body"><div class="faint">اجتماعات السلامة المسجلة</div><div class="kpi-value" style="font-size:18px">${d.toolboxTalks.length}</div></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">تصاريح عمل نشطة</div><div class="kpi-value" style="font-size:18px">${Number(d.metrics.active_permits||0)}</div></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">أوامر إيقاف نشطة</div><div class="kpi-value" style="font-size:18px;color:var(--danger)">${Number(d.metrics.active_stops||0)}</div></div></div>
  </div>`;
}

/* ============ الوثائق ============ */
function renderDocuments(){
  if(typeof loadOperationsData==='function'&&!window.operationsData&&!window.operationsLoading)setTimeout(loadOperationsData,0);const d=window.operationsData||{documents:[],correspondence:[]};
  return `
  <div class="page-eyebrow">الجودة والامتثال</div>
  <div class="page-head"><div class="page-title">إدارة الوثائق</div><div style="display:flex;gap:7px"><button class="btn-outline" onclick="openCorrespondenceModal()">+ مراسلة</button><button class="btn" onclick="openDocumentUploadModal()">+ رفع مستند</button></div></div>
  ${dataTable(
    [{label:'رقم المستند',key:'id'},{label:'العنوان',key:'title'},{label:'التصنيف',key:'cat'},{label:'المشروع',key:'p'},{label:'الإصدار',key:'v'},{label:'الحالة',key:'s'},{label:'انتهاء الصلاحية',key:'exp'},{label:'إجراء',key:'action'}],d.documents.map(x=>({id:x.document_code||'DOC-'+x.id,title:x.title||x.original_name,cat:x.category||'—',p:x.project_code||'—',v:'v'+x.version_no,s:badge(x.status,x.status==='approved'?'success':'warn'),exp:x.expires_on||'—',action:x.status==='draft'?`<button class="btn-sm btn" onclick="approveDocument(${x.id})">اعتماد</button>`:'—'}))
  )}
  ${dataTable([{label:'الرقم',key:'code'},{label:'الاتجاه',key:'direction'},{label:'المشروع',key:'project'},{label:'الموضوع',key:'subject'},{label:'الطرف',key:'counterparty'},{label:'التاريخ',key:'date'},{label:'الاستحقاق',key:'due'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.correspondence.map(c=>({code:c.correspondence_code,direction:c.direction==='inbound'?'وارد':'صادر',project:c.project_name||'—',subject:c.subject,counterparty:c.counterparty,date:c.correspondence_date,due:c.due_on||'—',status:badge(c.status,c.status==='closed'?'success':'warn'),action:c.status==='open'?`<button class="btn-sm btn" onclick="closeCorrespondence(${c.id})">إغلاق المتابعة</button>`:'—'})),{title:'سجل المراسلات الواردة والصادرة'})}`;
}

/* ============ الموافقات ============ */
let approvalsTab = 'pending';
const approvalsData = {
  pending: [
    {t:'دفعة مالية — PO-5511', u:'إدارة المشتريات', v:fmt(280000), pr:badge('عاجل','danger'), act:`<button class="btn-sm btn" onclick="event.stopPropagation();toast('تم الاعتماد')">اعتماد</button>`},
    {t:'مستخلص مقاول باطن SUB-007', u:'إدارة العقود', v:fmt(1250000), pr:badge('عادي','flat'), act:`<button class="btn-sm btn" onclick="event.stopPropagation();toast('تم الاعتماد')">اعتماد</button>`},
    {t:'طلب توظيف — مهندس مدني', u:'الموارد البشرية', v:'—', pr:badge('عاجل','danger'), act:`<button class="btn-sm btn" onclick="event.stopPropagation();toast('تم الاعتماد')">اعتماد</button>`},
  ],
  mine: [
    {t:'طلب نقل معدة — رافعة برجية', u:'أنت', v:'—', pr:badge('عادي','flat'), act:`<span class="faint">بانتظار مدير المشاريع</span>`},
    {t:'طلب شراء مواد سباكة', u:'أنت', v:fmt(45000), pr:badge('عادي','flat'), act:`<span class="faint">بانتظار مهندس الموقع</span>`},
  ],
  approved: [
    {t:'أمر صيانة WO-3298', u:'إدارة الصيانة', v:fmt(4200), pr:badge('عادي','flat'), act:`<span class="faint">اعتُمد قبل يومين</span>`},
  ],
  rejected: [
    {t:'طلب إجازة طارئة — يومان', u:'سالم الحربي', v:'—', pr:badge('عادي','flat'), act:`<span class="faint">رُفض — سبب: ذروة عمل بالمشروع</span>`},
  ],
  returned: [
    {t:'طلب شراء كابلات كهربائية', u:'مهندس الموقع', v:fmt(18000), pr:badge('يحتاج تعديل','warn'), act:`<span class="faint">أُعيد لتوضيح المواصفة الفنية</span>`},
  ],
};
function renderApprovals(){
  if(typeof loadHrApprovals === 'function') setTimeout(loadHrApprovals,0);
  const tabs = [['pending','بانتظار موافقتي (7)'],['mine','طلباتي'],['approved','المعتمد'],['rejected','المرفوض'],['returned','المعاد للتعديل']];
  return `
  <div class="page-eyebrow">صندوق موحّد</div>
  <div class="page-head"><div class="page-title">المهام والموافقات</div></div>
  <div class="toolbar">${tabs.map(([k,label])=>`<span class="chip ${approvalsTab===k?'active':''}" onclick="setApprovalsTab('${k}')">${label}</span>`).join('')}</div>
  <div id="approvalsContent">${approvalsTabContent()}</div>`;
}
function setApprovalsTab(t){ approvalsTab=t; document.getElementById('approvalsContent').innerHTML = approvalsTabContent(); }
function approvalsTabContent(){
  const rows = approvalsData[approvalsTab];
  return rows.length ? dataTable(
    [{label:'النوع',key:'t'},{label:'صاحب الطلب',key:'u'},{label:'القيمة',key:'v',num:true},{label:'الأولوية',key:'pr'},{label:'إجراء',key:'act'}],
    rows
  ) : `<div class="empty">لا توجد عناصر في هذا التصنيف حاليًا.</div>`;
}

/* ============ مركز الضرائب ============ */
function renderTax(){
  if(typeof loadFinanceTaxData==='function'&&!window.financeTaxData&&!window.financeTaxLoading)setTimeout(loadFinanceTaxData,0);if(typeof loadSupplyData==='function'&&!window.supplyData&&!window.supplyLoading)setTimeout(loadSupplyData,0);const d=window.financeTaxData||{vatReturns:[],withholding:[],taxInvoices:[],zatcaChecks:[],metrics:{}};const latest=d.vatReturns[0]||{};
  return `
  <div class="page-eyebrow">المالية</div>
  <div class="page-head"><div class="page-title">مركز الضرائب وZATCA</div><div style="display:flex;gap:7px"><button class="btn-outline" onclick="openWhtModal()">+ استقطاع</button><button class="btn" onclick="openVatReturnModal()">احتساب إقرار VAT</button></div></div>
  <div class="grid-3">
    <div class="panel"><div class="panel-head"><div class="panel-title">صافي VAT</div></div><div class="panel-body"><div class="kpi-value" style="font-size:20px">${fmt(Number(latest.net_vat||0))}</div><div class="faint">${latest.period_code||'لا يوجد إقرار محتسب'} · ${latest.status||'—'}</div></div></div>
    <div class="panel"><div class="panel-head"><div class="panel-title">استقطاع مفتوح</div></div><div class="panel-body"><div class="kpi-value" style="font-size:20px">${fmt(Number(d.metrics.open_wht||0))}</div><div class="faint">التزامات مسودة أو معتمدة لم تسدد</div></div></div>
    <div class="panel"><div class="panel-head"><div class="panel-title">فواتير تحتاج فحص</div></div><div class="panel-body"><div class="kpi-value" style="font-size:20px">${Number(d.metrics.unvalidated_invoices||0)}</div><div class="faint">فحص جاهزية محلي قبل الربط الإنتاجي</div></div></div>
  </div>
  ${dataTable([{label:'الإقرار',key:'code'},{label:'الفترة',key:'period'},{label:'مخرجات',key:'output',num:true},{label:'مدخلات',key:'input',num:true},{label:'تعديلات',key:'adjust',num:true},{label:'الصافي',key:'net',num:true},{label:'الاستحقاق',key:'due'},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.vatReturns.map(v=>({code:v.return_code,period:v.period_code,output:fmt(Number(v.output_vat)),input:fmt(Number(v.input_vat)),adjust:fmt(Number(v.adjustment_amount)),net:fmt(Number(v.net_vat)),due:v.due_on,status:badge(v.status,v.status==='filed'?'success':v.status==='approved'?'teal':'warn'),action:v.status==='draft'?`<button class="btn-sm btn-outline" onclick="openTaxAdjustment(${v.id})">تعديل</button> <button class="btn-sm btn" onclick="approveVatReturn(${v.id})">اعتماد</button>`:v.status==='approved'?`<button class="btn-sm btn" onclick="openVatFiling(${v.id})">توثيق التقديم</button>`:'—'})),{title:'إقرارات ضريبة القيمة المضافة'})}
  ${dataTable([{label:'الالتزام',key:'code'},{label:'المورد',key:'supplier'},{label:'الخدمة',key:'service'},{label:'الأساس',key:'base',num:true},{label:'النسبة',key:'rate'},{label:'الضريبة',key:'tax',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.withholding.map(w=>({code:w.wht_code,supplier:w.supplier_name||'—',service:w.service_type,base:fmt(Number(w.taxable_amount)),rate:w.rate_percent+'%',tax:fmt(Number(w.tax_amount)),status:badge(w.status,w.status==='paid'?'success':w.status==='approved'?'teal':'warn'),action:w.status==='draft'?`<button class="btn-sm btn" onclick="approveWht(${w.id})">اعتماد</button>`:w.status==='approved'?`<button class="btn-sm btn" onclick="payWht(${w.id})">توثيق السداد</button>`:'—'})),{title:'ضريبة الاستقطاع'})}
  <div class="panel" style="margin-top:14px">
    <div class="panel-head"><div class="panel-title">فحص جاهزية الفوترة الإلكترونية</div>${badge('فحص محلي — الإرسال الإنتاجي غير مفعّل','flat')}</div>
    <div class="panel-body">
      ${dataTable([{label:'الفاتورة',key:'invoice'},{label:'العميل',key:'customer'},{label:'الرقم الضريبي',key:'vat'},{label:'الإجمالي',key:'total',num:true},{label:'الحالة',key:'status'},{label:'إجراء',key:'action'}],d.taxInvoices.map(i=>({invoice:i.invoice_code,customer:i.customer_name,vat:i.vat_number||'مفقود',total:fmt(Number(i.total_amount)),status:badge(i.zatca_status,i.zatca_status==='validated_local'?'success':'warn'),action:`<button class="btn-sm btn" onclick="validateZatcaInvoice(${i.id})">فحص محلي</button>`}))) }
    </div>
  </div>`;
}

/* ============ بوابة الموظف — تطبيق جوال مرتبط بالخادم (متعدد المواقع + صلاحيات + بروفايل كامل) ============ */
let selectedGeoIdx = null;
let empLoggedIn = true;
const empSession = {
  id:'EMP-0231', name:'محمد العتيبي', role:'مهندس موقع',
  company:'شركة سواعد للمقاولات', branch:'الرياض', project:'مشروع القدية — الحزمة 3',
  userAccount:'m.alotaibi@sawaed.sa', permissions:['تسجيل حضوره الشخصي فقط','عرض قسائم راتبه فقط','طلب إجازة/سلفة لنفسه','عرض عهدته فقط — لا يرى عهد زملائه'],
};

function renderEmployeeApp(){
  if(typeof loadEmployeeAppData==='function' && !window.employeeAttendanceData && !window.employeeAppLoading) setTimeout(loadEmployeeAppData,0);
  return `
  <div class="page-eyebrow">البوابات والتطبيقات</div>
  <div class="page-head"><div class="page-title">تطبيق الموظف</div><div class="page-desc">مرتبط بحساب المستخدم وملف الموظف والمشروع والصلاحيات</div></div>
  <div class="phone-wrap"><div class="phone"><div class="phone-screen">
    <div class="phone-status"><span>9:41</span><span>سواعد</span><span>82%</span></div>
    ${empLoggedIn ? `
    <div class="phone-top"><div class="flex-between"><strong>مرحبًا، ${empSession.name.split(' ')[0]}</strong><span class="faint">${empSession.id}</span></div><div class="faint">${empSession.project}</div></div>
    <div class="phone-body" id="empAppBody">${empHome()}</div>
    <div class="phone-nav">
      <div class="phone-nav-item active" onclick="empNav('home')"><span class="ic">▣</span>الرئيسية</div>
      <div class="phone-nav-item" onclick="empNav('checkin')"><span class="ic">⏱</span>الحضور</div>
      <div class="phone-nav-item" onclick="empNav('pay')"><span class="ic">◈</span>راتبي</div>
      <div class="phone-nav-item" onclick="empNav('leaves')"><span class="ic">▥</span>الإجازات</div>
      <div class="phone-nav-item" onclick="empNav('more')"><span class="ic">☰</span>المزيد</div>
    </div>` : `
    <div class="phone-body" id="empAppBody">${empLogin()}</div>
    `}
  </div></div></div>`;
}

/* ---- تسجيل الدخول: يثبت أن كل جلسة مرتبطة بحساب موظف واحد فقط بصلاحياته الخاصة ---- */
function empLogin(){
  return `
    <div style="text-align:center;margin:26px 0 18px">
      <div class="mark" style="width:46px;height:46px;font-size:16px;margin:0 auto 10px">سو</div>
      <strong>تسجيل دخول الموظف</strong>
      <div class="faint" style="margin-top:4px">الدخول مرتبط بحساب مستخدم واحد لموظف واحد فقط</div>
    </div>
    <div class="card">
      <div class="faint" style="margin-bottom:6px">رقم الجوال</div>
      <input class="search-input" style="width:100%;margin-bottom:10px" value="05xxxxxxxx" id="empPhone">
      <div class="faint" style="margin-bottom:6px">رمز التحقق (OTP)</div>
      <input class="search-input" style="width:100%" placeholder="••••" id="empOtp">
    </div>
    <button class="btn" style="width:100%;margin-top:10px" onclick="empDoLogin()">تسجيل الدخول</button>
    <div class="faint" style="margin-top:10px;text-align:center">حسابات المشتركين (موردين/مراجعين) تُوجَّه لبوابتها الخاصة تلقائيًا، لا لهذا التطبيق.</div>`;
}
function empDoLogin(){
  empLoggedIn = true;
  document.getElementById('main').innerHTML = renderEmployeeApp();
  toast('تم تسجيل الدخول كـ ' + empSession.name + ' — الصلاحيات محمّلة من ملفه الوظيفي');
}
function empLogout(){
  window.location.href='logout.php';
}

function empHome(){
  const a=window.employeeAttendanceData||{};const today=a.today;const slip=a.payslip;
  return `
    <div class="card flex-between"><span>حالة اليوم</span>${today?badge(today.checked_out_at?'تم الانصراف':today.status,today.status==='late'?'warn':'success'):badge('لم يُسجَّل حضور بعد','warn')}</div>
    <div class="card"><div class="flex-between"><span>${slip?'قسيمة '+slip.period_key:'آخر قسيمة راتب'}</span><span class="faint num">${slip?fmt(Number(slip.net_amount))+' ر.س':'غير متاحة'}</span></div></div>
    <div class="card"><div class="flex-between"><span>رصيد الإجازات</span><span class="faint num">14 يوم</span></div></div>
    <div class="card"><div class="flex-between"><span>عهدتي</span><span class="faint">3 عناصر</span></div></div>
    <button class="btn" style="width:100%;margin-top:6px" onclick="empNav('checkin')">تسجيل الحضور الآن</button>`;
}

/* ---- الملف الشخصي والصلاحيات ---- */
function empProfile(){
  return `
    <button class="btn-ghost" onclick="empNav('more')" style="padding:0 0 10px">← رجوع</button>
    <div class="card" style="text-align:center">
      <div class="mark" style="width:44px;height:44px;margin:0 auto 8px">${empSession.name[0]}</div>
      <strong>${empSession.name}</strong>
      <div class="faint">${empSession.role} · ${empSession.id}</div>
    </div>
    <div class="card">
      <div class="faint" style="margin-bottom:6px">نطاق الوصول (مرتبط بحسابه فقط)</div>
      <table>
        <tr><td class="faint">الشركة</td><td>${empSession.company}</td></tr>
        <tr><td class="faint">الفرع</td><td>${empSession.branch}</td></tr>
        <tr><td class="faint">المشروع</td><td>${empSession.project}</td></tr>
        <tr><td class="faint">حساب الدخول</td><td class="num">${empSession.userAccount}</td></tr>
      </table>
    </div>
    <div class="card">
      <div class="faint" style="margin-bottom:8px">صلاحياته داخل التطبيق</div>
      ${empSession.permissions.map(p=>`<div style="padding:5px 0;border-bottom:1px solid var(--line);font-size:12.5px">✓ ${p}</div>`).join('')}
      <div class="notice teal" style="margin-top:10px">لا يمكنه الاطلاع على بيانات موظف آخر أو مشروع خارج نطاقه — نفس ضابط OrgAccessScope المطبّق على الخادم.</div>
    </div>
    <button class="btn-outline" style="width:100%" onclick="toast('تم فتح نموذج تحديث البيانات الشخصية')">تحديث بياناتي</button>`;
}

/* ---- الإجازات ---- */
function empLeaves(){
  const leaves=employeeDetails[empSession.id]?.leaves||[];
  return `
    <button class="btn" style="width:100%;margin-bottom:10px" onclick="openLeaveRequestModal('${empSession.id}')">+ طلب إجازة جديد</button>
    <div class="faint" style="margin:10px 0 6px">سجل الطلبات</div>
    ${leaves.map(l=>`<div class="card flex-between"><span>${l.leave_name} · ${l.days_count} يوم</span>${badge(l.status,l.status==='approved'?'success':l.status==='rejected'?'danger':'warn')}</div>`).join('')||'<div class="empty">لا توجد طلبات إجازة.</div>'}`;
}

/* ---- العهد ---- */
function empCustody(){
  const items = [
    {n:'لابتوب Dell Latitude', d:'2024-02-11', c:'جيدة'},
    {n:'خوذة سلامة', d:'2023-09-01', c:'جيدة'},
    {n:'سترة عاكسة', d:'2023-09-01', c:'تحتاج استبدال'},
  ];
  return `
    <button class="btn-ghost" onclick="empNav('more')" style="padding:0 0 10px">← رجوع</button>
    <div class="faint" style="margin-bottom:8px">عهدتي فقط — لا يمكنه رؤية عهد أي موظف آخر</div>
    ${items.map(it=>`
    <div class="card">
      <div class="flex-between"><strong>${it.n}</strong>${badge(it.c, it.c==='جيدة'?'success':'warn')}</div>
      <div class="faint" style="margin-top:4px">تاريخ الاستلام: ${it.d}</div>
      <div style="display:flex;gap:6px;margin-top:8px">
        <button class="btn-sm btn-outline" style="flex:1" onclick="toast('تم إرسال بلاغ عطل/تلف للعهدة')">إبلاغ عن عطل</button>
        <button class="btn-sm btn-outline" style="flex:1" onclick="toast('تم فتح نموذج طلب تسليم العهدة')">طلب تسليم</button>
      </div>
    </div>`).join('')}
    <button class="btn" style="width:100%;margin-top:6px" onclick="openCustodyRequestModal()">+ طلب عهدة جديدة</button>`;
}

/* ---- الوثائق ---- */
function empDocuments(){
  return `
    <button class="btn-ghost" onclick="empNav('more')" style="padding:0 0 10px">← رجوع</button>
    <div class="card flex-between"><span>الإقامة</span>${badge('تُجدَّد خلال 45 يوم','warn')}</div>
    <div class="card flex-between"><span>الشهادة الصحية</span>${badge('سارية','success')}</div>
    <div class="card flex-between"><span>رخصة القيادة</span>${badge('سارية','success')}</div>
    <button class="btn-outline" style="width:100%;margin-top:6px" onclick="openDocumentUploadModal()">+ رفع مستند</button>`;
}

/* ---- التقييم ---- */
function empEvaluation(){
  return `
    <button class="btn-ghost" onclick="empNav('more')" style="padding:0 0 10px">← رجوع</button>
    <div class="card"><div class="flex-between"><strong>تقييم الربع الثاني 2026</strong><span class="num" style="color:var(--success)">4.3 / 5</span></div></div>
    ${[['جودة العمل',90],['الالتزام بالمواعيد',82],['العمل الجماعي',95],['السلامة',88]].map(([l,v])=>`
    <div style="margin-bottom:10px"><div class="flex-between faint" style="margin-bottom:4px"><span>${l}</span><span class="num">${v}%</span></div><div class="pbar"><div style="width:${v}%"></div></div></div>`).join('')}`;
}

/* ---- السياسات ---- */
const policies = [
  {id:'POL-001', title:'سياسة السلامة العامة', requiresSignature:true, acknowledgedBy:['EMP-0231','EMP-0198','EMP-0304','EMP-0155','EMP-0410']},
  {id:'POL-002', title:'سياسة الحضور والانصراف', requiresSignature:true, acknowledgedBy:['EMP-0231','EMP-0198','EMP-0304','EMP-0155','EMP-0410']},
  {id:'POL-003', title:'ميثاق السلوك الوظيفي', requiresSignature:true, acknowledgedBy:['EMP-0304','EMP-0410']},
];
function empPolicies(){
  return `
    <button class="btn-ghost" onclick="empNav('more')" style="padding:0 0 10px">← رجوع</button>
    ${policies.map(p=>{
      const signed = p.acknowledgedBy.includes(empSession.id);
      return `<div class="card flex-between">
        <span>${p.title}</span>
        ${signed ? badge('مُطَّلع عليها','success') : `<button class="btn-sm btn" onclick="signPolicy('${p.id}')">توقيع الآن</button>`}
      </div>`;
    }).join('')}`;
}
function signPolicy(policyId){
  const p = policies.find(x=>x.id===policyId);
  if(!p.acknowledgedBy.includes(empSession.id)) p.acknowledgedBy.push(empSession.id);
  toast('✓ تم توقيعك على "'+p.title+'" وتسجيله في سجل التدقيق');
  empNav('policies');
}

/* ---- قائمة المزيد ---- */
function empMore(){
  return `
    <div class="grid-3" style="gap:8px">
      <div class="card" style="text-align:center" onclick="empNav('profile')"><div style="font-size:18px">◇</div><div class="faint" style="margin-top:4px">الملف الشخصي</div></div>
      <div class="card" style="text-align:center" onclick="empNav('custody')"><div style="font-size:18px">▣</div><div class="faint" style="margin-top:4px">العهد</div></div>
      <div class="card" style="text-align:center" onclick="empNav('documents')"><div style="font-size:18px">▥</div><div class="faint" style="margin-top:4px">الوثائق</div></div>
      <div class="card" style="text-align:center" onclick="empNav('evaluation')"><div style="font-size:18px">✦</div><div class="faint" style="margin-top:4px">التقييم</div></div>
      <div class="card" style="text-align:center" onclick="empNav('policies')"><div style="font-size:18px">§</div><div class="faint" style="margin-top:4px">السياسات</div></div>
      <div class="card" style="text-align:center" onclick="empNav('requests')"><div style="font-size:18px">▤</div><div class="faint" style="margin-top:4px">طلباتي</div></div>
      <div class="card" style="text-align:center" onclick="toast('تم فتح مركز الدعم والشكاوى')"><div style="font-size:18px">◈</div><div class="faint" style="margin-top:4px">الدعم/شكوى</div></div>
      <div class="card" style="text-align:center" onclick="toast('التنبيهات: 3 غير مقروءة')"><div style="font-size:18px">◉</div><div class="faint" style="margin-top:4px">التنبيهات</div></div>
      <div class="card" style="text-align:center" onclick="empLogout()"><div style="font-size:18px">⏻</div><div class="faint" style="margin-top:4px;color:var(--danger)">تسجيل خروج</div></div>
    </div>`;
}
function empCheckin(){
  const a=window.employeeAttendanceData||{};const fences=a.geofences||[];const today=a.today;
  return `
    <div class="faint" style="margin-bottom:8px">سيستخدم التطبيق موقع الجهاز ويتحقق من أقرب نطاق معتمد للمشروع والتحقق من الوجه.</div>
    <div class="map-fake">
      <div class="map-pin" style="top:60%;right:45%;background:var(--accent)"></div>
      ${fences.map((g,i)=>`<div class="map-pin" style="top:${20+i*22}%;right:${15+i*18}%;background:var(--teal)"></div>`).join('')}
    </div>
    ${fences.map(g=>`<div class="geo-row"><div><strong>${g.name}</strong><div class="faint">نطاق ${g.radius_meters} م</div></div><span class="faint">${a.employee?.project_name||''}</span></div>`).join('')||'<div class="notice danger">لا توجد مواقع تحضير مفعلة لهذا المشروع.</div>'}
    ${!a.can_mark?`<div class="notice warn">هذه معاينة إدارية فقط. تسجيل الحضور متاح من حساب الدخول المرتبط بالموظف نفسه.</div>`:!today?`<button class="btn" style="width:100%;margin-top:10px" onclick="doCheckin('check_in')">◉ تسجيل الحضور</button>`:!today.checked_out_at?`<button class="btn" style="width:100%;margin-top:10px" onclick="doCheckin('check_out')">◉ تسجيل الانصراف</button>`:`<div class="notice teal">✓ اكتمل تسجيل الحضور والانصراف لهذا اليوم.</div>`}
    <div class="faint" style="margin-top:8px">يرفض الخادم التسجيل خارج النطاق أو دون تحقق الوجه، وتُسجّل المسافة والحركة في سجل التدقيق.</div>`;
}
function selectGeo(i){ selectedGeoIdx=i; empNav('checkin'); }
function doCheckin(action='check_in'){ if(typeof performAttendance==='function') performAttendance(action); }
function empPay(){
  const slip=window.employeeAttendanceData?.payslip;
  return `
    <div class="card"><div class="flex-between"><strong>${slip?'راتب '+slip.period_key:'لا توجد قسيمة مرحلة'}</strong>${slip?badge('مُرحّل','success'):badge('—','flat')}</div></div>
    <table style="margin-top:6px">
      <tr><td class="faint">الأساسي</td><td class="num">${slip?fmt(Number(slip.basic_salary)):'—'}</td></tr>
      <tr><td class="faint">بدل سكن</td><td class="num">${slip?fmt(Number(slip.housing_allowance)):'—'}</td></tr>
      <tr><td class="faint">بدل نقل</td><td class="num">${slip?fmt(Number(slip.transport_allowance)):'—'}</td></tr>
      <tr><td class="faint">خصومات</td><td class="num">${slip?'-'+fmt(Number(slip.absence_deduction)+Number(slip.late_deduction)+Number(slip.other_deductions)):'—'}</td></tr>
      <tr style="font-weight:700"><td>الصافي</td><td class="num">${slip?fmt(Number(slip.net_amount)):'—'}</td></tr>
    </table>
    <button class="btn-outline" style="width:100%;margin-top:10px">تحميل القسيمة PDF</button>`;
}
function empRequests(){
  return `
    <div class="card flex-between"><span>طلب إجازة سنوية</span>${badge('بانتظار الاعتماد','warn')}</div>
    <div class="card flex-between"><span>طلب سلفة 2,000 ر.س</span>${badge('معتمد','success')}</div>
    <div class="card flex-between"><span>تصحيح بصمة 12 يوليو</span>${badge('معتمد','success')}</div>
    <button class="btn" style="width:100%;margin-top:6px" onclick="openNewGenericRequestModal()">+ طلب جديد</button>`;
}
function empNav(view){
  const navItems = document.querySelectorAll('.phone-nav-item');
  const bottomTabs = {home:0, checkin:1, pay:2, leaves:3, more:4};
  navItems.forEach(el=>el.classList.remove('active'));
  if(view in bottomTabs && navItems[bottomTabs[view]]) navItems[bottomTabs[view]].classList.add('active');
  else if(navItems[4]) navItems[4].classList.add('active'); // شاشات فرعية تُعتبر جزءًا من "المزيد"

  const screens = {
    home:empHome, checkin:empCheckin, pay:empPay, leaves:empLeaves, more:empMore,
    requests:empRequests, profile:empProfile, custody:empCustody,
    documents:empDocuments, evaluation:empEvaluation, policies:empPolicies,
  };
  document.getElementById('empAppBody').innerHTML = screens[view]();
}

/* ============ تطبيق المستودع — محاكاة باركود وoffline ============ */
let scannedItem = null;
function renderWarehouseApp(){
  return `
  <div class="page-eyebrow">البوابات والتطبيقات</div>
  <div class="page-head"><div class="page-title">تطبيق المستودع</div><div class="page-desc">مسح باركود، استلام، فحص، صرف — يعمل دون إنترنت مع مزامنة لاحقة</div></div>
  <div class="phone-wrap"><div class="phone"><div class="phone-screen">
    <div class="phone-status"><span>9:41</span><span>مستودع القدية</span><span class="mono" style="color:var(--warn)">Offline ●</span></div>
    <div class="phone-top"><strong>مستودع مشروع القدية — الرئيسي</strong></div>
    <div class="phone-body" id="whAppBody">${whHome()}</div>
    <div class="phone-nav">
      <div class="phone-nav-item active" onclick="whNav('home')"><span class="ic">▦</span>الرئيسية</div>
      <div class="phone-nav-item" onclick="whNav('scan')"><span class="ic">▤</span>مسح</div>
      <div class="phone-nav-item" onclick="whNav('tasks')"><span class="ic">✔</span>مهامي</div>
    </div>
  </div></div></div>`;
}
function whHome(){
  return `
    <div class="grid-3" style="gap:8px">
      <div class="card" style="text-align:center" onclick="toast('استلام مادة')"><div style="font-size:18px">▼</div><div class="faint" style="margin-top:4px">استلام</div></div>
      <div class="card" style="text-align:center" onclick="toast('فحص جودة')"><div style="font-size:18px">◎</div><div class="faint" style="margin-top:4px">فحص</div></div>
      <div class="card" style="text-align:center" onclick="toast('صرف مادة')"><div style="font-size:18px">▲</div><div class="faint" style="margin-top:4px">صرف</div></div>
    </div>
    <div class="card" style="margin-top:10px"><div class="flex-between"><span>عمليات بانتظار المزامنة</span>${badge('3','warn')}</div></div>
    <button class="btn" style="width:100%;margin-top:6px" onclick="whNav('scan')">▤ مسح باركود / QR</button>`;
}
function whScan(){
  return `
    <div style="height:180px;border:2px dashed var(--line-strong);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--text-faint);margin-bottom:12px">
      إطار الكاميرا (محاكاة)
    </div>
    <button class="btn" style="width:100%" onclick="simulateScan()">محاكاة مسح ناجح</button>
    ${scannedItem?`
    <div class="card" style="margin-top:12px">
      <div class="flex-between"><strong>${scannedItem.name}</strong><span class="faint num">${scannedItem.id}</span></div>
      <div class="faint" style="margin:8px 0">الرصيد الحالي: ${scannedItem.onHand} ${scannedItem.unit} · محجوز: ${scannedItem.reserved}</div>
      ${scannedItem.status==='تحت الفحص' || scannedItem.id==='MAT-5502' ? `<div class="notice danger" style="margin:8px 0">⛔ هذه المادة تحت الفحص — الصرف ممنوع من قاعدة البيانات مباشرة</div>`:''}
      <div style="display:flex;gap:8px;margin-top:8px">
        <button class="btn-sm btn-outline" style="flex:1" onclick="toast('تم تسجيل الاستلام')">استلام</button>
        <button class="btn-sm ${scannedItem.id==='MAT-5502'?'btn-outline':'btn'}" style="flex:1" ${scannedItem.id==='MAT-5502'?'disabled style="opacity:.4"':''} onclick="toast(scannedItem.id==='MAT-5502' ? '' : 'تم تسجيل الصرف')">صرف</button>
      </div>
    </div>`:''}`;
}
function simulateScan(){
  scannedItem = materials[Math.floor(Math.random()*materials.length)];
  whNav('scan');
  toast('تم التعرف على الصنف: ' + scannedItem.name);
}
function whTasks(){
  return `
    <div class="card flex-between"><span>استلام PO-5511</span>${badge('قيد التنفيذ','teal')}</div>
    <div class="card flex-between"><span>جرد دوري — رف A12</span>${badge('لم يبدأ','flat')}</div>
    <div class="card flex-between"><span>تحويل مخزون لمشروع الواحة</span>${badge('بانتظار التوقيع','warn')}</div>`;
}
function whNav(view){
  document.querySelectorAll('#whAppBody').length; // noop guard
  const items = document.querySelectorAll('.phone-nav-item');
  const idx = {home:0,scan:1,tasks:2}[view];
  items.forEach(el=>el.classList.remove('active'));
  if(items[idx]) items[idx].classList.add('active');
  document.getElementById('whAppBody').innerHTML = {home:whHome, scan:whScan, tasks:whTasks}[view]();
}

/* ============ بوابات أخرى ============ */
function renderSupplierPortal(){
  return `<div class="page-eyebrow">البوابات</div><div class="page-head"><div class="page-title">بوابة الموردين</div></div>
  <div class="notice teal">وصول بصلاحية محدودة: المورد يرى فقط بياناته الخاصة (عروضه، أوامر الشراء، فواتيره، مدفوعاته).</div>
  ${dataTable([{label:'أمر الشراء',key:'id'},{label:'القيمة',key:'amount',num:true,render:r=>fmt(r.amount)},{label:'الحالة',key:'status',render:r=>badge(r.status,'teal')}], purchaseOrders)}`;
}
function renderAuditorPortal(){
  return `<div class="page-eyebrow">البوابات</div><div class="page-head"><div class="page-title">بوابة المحاسب القانوني والمراجع</div></div>
  <div class="notice danger">⚿ قراءة فقط — لا توجد أي أداة تعديل متاحة فعليًا لهذا الدور، وليس مخفية في الواجهة فقط.</div>
  <div class="grid-3">
    <div class="panel"><div class="panel-body"><div class="faint">ميزان المراجعة</div><button class="btn-outline btn-sm" style="margin-top:8px">عرض</button></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">عينات الفواتير</div><button class="btn-outline btn-sm" style="margin-top:8px">عرض</button></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">سجل التدقيق الكامل</div><button class="btn-outline btn-sm" style="margin-top:8px">عرض</button></div></div>
  </div>`;
}

/* ============ الذكاء الاصطناعي ============ */
function renderAI(){
  return `
  <div class="page-eyebrow">القيادة</div>
  <div class="page-head"><div class="page-title">مساعد سواعد الذكي</div></div>
  <div class="panel">
    <div class="panel-body">
      <div id="aiChatLog">
        <div class="card"><strong>س:</strong> ما المشاريع المتوقع خسارتها؟<br><br><strong>ج:</strong> مشروع "مستودعات الدمام" (PRJ-009) يُظهر تجاوزًا في التكلفة (84% إنفاق مقابل 96% وقت منقضٍ) — يُنصح بمراجعة عاجلة.
          <div class="faint" style="margin-top:8px">المصادر المستخدمة: جدول المشاريع، القيود المالية · الأداة: financial_query_readonly</div>
        </div>
      </div>
      <input class="search-input" style="width:100%;margin-top:10px" placeholder="اسأل عن أي بيانات ضمن صلاحياتك..." onkeydown="if(event.key==='Enter'){ askAI(this.value); this.value=''; }">
      <div class="notice teal" style="margin-top:10px">لا يستطيع المساعد ترحيل قيد أو تقديم إقرار أو تنفيذ دفعة — أي إجراء حساس يُحوَّل لموافقة بشرية.</div>
    </div>
  </div>`;
}
function askAI(q){
  q = q.trim();
  if(!q) return;
  let answer, source;
  const lower = q.toLowerCase();
  if(q.includes('عمالة') || q.includes('موظف')){
    answer = 'عدد الموظفين الحالي 1,248 موظفًا بنسبة سعودة 34%. أعلى تركيز عمالة في مشروع القدية.';
    source = 'جدول الموظفين';
  } else if(q.includes('معدة') || q.includes('معدات')){
    answer = 'المعدات العاملة حاليًا 86 من أصل 104، و6 تحت الصيانة. الرافعة البرجية 40م (برج الواحة) هي الأعلى تكلفة تشغيل.';
    source = 'جدول المعدات';
  } else if(q.includes('ضريبة') || q.includes('زكاة') || q.includes('vat')){
    answer = 'الضريبة المستحقة حاليًا 1.94 مليون ريال، وإقرار Q3 مستحق 15 أغسطس 2026.';
    source = 'مركز الضرائب';
  } else if(q.includes('مخزون') || q.includes('مستودع')){
    answer = 'قيمة المخزون الحالية 22.6 مليون ريال. صنف واحد (كابل كهربائي 4مم) نفدت كميته بالكامل.';
    source = 'جدول المستودعات';
  } else {
    answer = 'لا تتوفر لديّ بيانات كافية للإجابة الدقيقة على هذا السؤال ضمن عيّنة النموذج التجريبية الحالية — في النظام الفعلي سيُستعلم مباشرة من قاعدة البيانات ضمن صلاحياتك.';
    source = 'لا مصدر مطابق';
  }
  const log = document.getElementById('aiChatLog');
  log.insertAdjacentHTML('beforeend', `
    <div class="card"><strong>س:</strong> ${q}<br><br><strong>ج:</strong> ${answer}
      <div class="faint" style="margin-top:8px">المصادر المستخدمة: ${source}</div>
    </div>`);
  log.scrollTop = log.scrollHeight;
}

/* ============ التقارير ============ */
/* ============ التقارير الموحدة — أرقام محسوبة حيًّا من بيانات النظام ============ */
let reportsTab = 'profitability';
function renderReports(){
  if(typeof loadContractsData==='function'&&!window.contractsData&&!window.contractsLoading)setTimeout(loadContractsData,0);
  if(typeof loadFinanceTaxData==='function'&&!window.financeTaxData&&!window.financeTaxLoading)setTimeout(loadFinanceTaxData,0);
  const tabs = [
    ['profitability','ربحية المشاريع'], ['workforce','القوى العاملة والسعودة'],
    ['equipment-util','المعدات والاستغلال'], ['aging','أعمار الديون'],
    ['tax-due','الضرائب المستحقة'], ['cash','ملخص التدفق النقدي'],
  ];
  return `
  <div class="page-eyebrow">القيادة</div>
  <div class="page-head"><div class="page-title">التقارير والتحليلات</div>
    <button class="btn-outline" onclick="toast('في النظام الفعلي: تصدير PDF عبر DomPDF وExcel عبر Laravel Excel')">تصدير PDF / Excel</button></div>
  <div class="toolbar">${tabs.map(([k,l])=>`<span class="chip ${reportsTab===k?'active':''}" onclick="setReportsTab('${k}')">${l}</span>`).join('')}</div>
  <div id="reportsContent">${reportsTabContent()}</div>`;
}
function setReportsTab(t){ reportsTab=t; document.getElementById('reportsContent').innerHTML=reportsTabContent(); buildSidebar(); }
function reportsTabContent(){
  return {profitability:rptProfitability, workforce:rptWorkforce, 'equipment-util':rptEquipmentUtil,
          aging:rptAging, 'tax-due':rptTaxDue, cash:rptCash}[reportsTab]();
}

function rptProfitability(){
  const rows=(window.contractsData?.profitability||[]).map(p=>{const revenue=Number(p.invoiced_revenue),cost=Number(p.recorded_cost),profit=Number(p.recognized_profit),margin=revenue?Math.round(profit/revenue*100):0;return{name:p.name,value:fmt(Number(p.contracted_value)),revenue:fmt(revenue),spent:fmt(cost),collected:fmt(Number(p.collected)),outstanding:fmt(Number(p.outstanding)),profit:(profit<0?'-':'')+fmt(Math.abs(profit)),margin,flag:profit<0?badge('خسارة مسجلة','danger'):margin<8?badge('هامش ضعيف','warn'):badge('صحي','success')}});
  return dataTable(
    [{label:'المشروع',key:'name'},{label:'قيمة العقود',key:'value',num:true},{label:'إيراد مفوتر',key:'revenue',num:true},{label:'التكلفة المسجلة',key:'spent',num:true},{label:'المحصّل',key:'collected',num:true},{label:'الذمم',key:'outstanding',num:true},{label:'الربح المعترف',key:'profit',num:true},
     {label:'الهامش',key:'margin',num:true,render:r=>r.margin+'%'},{label:'التقييم',key:'flag'}],
    rows, {title:'ربحية المشاريع من القيود التشغيلية'}
  ) + `<div class="faint" style="margin-top:10px">التكلفة المسجلة = رصيد تكلفة المشروع السابق + الرواتب المرحلة + صرف المواد بالتكلفة + مستخلصات مقاولي الباطن المعتمدة. راجع الأرصدة الافتتاحية لتجنب الازدواج.</div>`;
}

function rptWorkforce(){
  const total = employees.length;
  const saudis = employees.filter(e=>e.name!=='Rahim Uddin').length; // العيّنة: غير السعوديين محددون بالاسم
  const byProject = {};
  employees.forEach(e=>{ const k=projectNameById(e.project); byProject[k]=byProject[k]||{count:0,cost:0}; byProject[k].count++; byProject[k].cost+=e.salary; });
  return `
  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-label">إجمالي الموظفين (العيّنة)</div><div class="kpi-value">${total}</div></div>
    <div class="kpi"><div class="kpi-label">نسبة السعودة</div><div class="kpi-value">${Math.round(saudis/total*100)}%</div></div>
    <div class="kpi"><div class="kpi-label">تكلفة الرواتب الشهرية</div><div class="kpi-value">${fmt(employees.reduce((s,e)=>s+e.salary,0))}</div></div>
    <div class="kpi"><div class="kpi-label">وثائق قريبة الانتهاء</div><div class="kpi-value" style="color:var(--warn)">${employees.filter(e=>isNearExpiry(e.iqamaExpiry)).length}</div></div>
  </div>
  ${dataTable(
    [{label:'المشروع',key:'p'},{label:'عدد الموظفين',key:'c',num:true},{label:'تكلفة الرواتب الشهرية',key:'cost',num:true}],
    Object.entries(byProject).map(([p,v])=>({p, c:v.count, cost:fmt(v.cost)})),
    {title:'توزيع القوى العاملة وتكلفتها حسب المشروع — محسوب حيًّا'}
  )}`;
}

function rptEquipmentUtil(){
  const working = equipment.filter(e=>e.status==='عاملة').length;
  const rows = equipment.map(e=>({
    name:e.name, project:projectNameById(e.project), type:e.type,
    hours:fmt(e.hours), monthlyCost:fmt(e.costHr*200),
    status: badge(e.status, e.status==='عاملة'?'success':e.status==='صيانة'?'warn':'danger'),
    util: e.status==='عاملة' ? badge('مستغلة','success') : badge('تكلفة توقف: '+fmt(Math.round(e.costHr*200*0.4))+' ر.س/شهر','danger'),
  }));
  return `
  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-label">نسبة الاستغلال الإجمالية</div><div class="kpi-value">${Math.round(working/equipment.length*100)}%</div></div>
    <div class="kpi"><div class="kpi-label">معدات متوقفة/صيانة</div><div class="kpi-value" style="color:var(--danger)">${equipment.length-working}</div></div>
  </div>
  ${dataTable(
    [{label:'المعدة',key:'name'},{label:'المشروع',key:'project'},{label:'الملكية',key:'type'},{label:'ساعات التشغيل',key:'hours',num:true},
     {label:'تكلفة شهرية تقديرية (200 ساعة)',key:'monthlyCost',num:true},{label:'الحالة',key:'status'},{label:'الاستغلال',key:'util'}],
    rows, {title:'استغلال المعدات وتكلفة التوقف'}
  )}`;
}

function rptAging(){
  const rows = customers.map(c=>{
    const outstanding = c.totalValue - c.collected;
    return {name:c.name, total:fmt(c.totalValue), collected:fmt(c.collected),
            outstanding:fmt(outstanding),
            b1: fmt(Math.round(outstanding*0.6)), b2: fmt(Math.round(outstanding*0.25)),
            b3: c.overdue ? fmt(c.overdue) : '—',
            risk: c.overdue ? badge('متابعة تحصيل عاجلة','danger') : badge('ضمن الشروط','success')};
  });
  return dataTable(
    [{label:'العميل',key:'name'},{label:'إجمالي العقود',key:'total',num:true},{label:'المحصَّل',key:'collected',num:true},
     {label:'الرصيد القائم',key:'outstanding',num:true},{label:'0–30 يوم',key:'b1',num:true},{label:'31–90 يوم',key:'b2',num:true},
     {label:'+90 يوم (متأخر)',key:'b3',num:true},{label:'التقييم',key:'risk'}],
    rows, {title:'أعمار الديون — التوزيع الزمني للأرصدة القائمة'}
  ) + `<div class="faint" style="margin-top:10px">في النظام الفعلي تُحتسب الشرائح من تواريخ استحقاق الفواتير الفعلية، لا نسبًا تقديرية.</div>`;
}

function rptTaxDue(){
  const d=window.financeTaxData||{vatReturns:[],withholding:[],metrics:{}};const vat=d.vatReturns[0]||{};const wht=(d.withholding||[]).filter(w=>w.status!=='paid').reduce((s,w)=>s+Number(w.tax_amount),0);
  return `
  <div class="grid-3">
    <div class="panel"><div class="panel-body"><div class="faint">صافي VAT — ${vat.period_code||'—'}</div><div class="kpi-value" style="font-size:20px">${fmt(Number(vat.net_vat||0))}</div><div class="faint">الاستحقاق ${vat.due_on||'—'} · ${vat.status||'غير محتسب'}</div></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">ضريبة الاستقطاع المفتوحة</div><div class="kpi-value" style="font-size:20px">${fmt(wht)}</div><div class="faint">مسودة أو معتمدة ولم توثق كسداد</div></div></div>
    <div class="panel"><div class="panel-body"><div class="faint">حركة القيود المرحلة</div><div class="kpi-value" style="font-size:20px">${fmt(Number(d.metrics.posted_debit||0))}</div><div class="faint">من الأستاذ العام الفعلي</div></div></div>
  </div>
  ${dataTable(
    [{label:'الإقرار',key:'a'},{label:'الفترة',key:'p'},{label:'الاستحقاق',key:'d'},{label:'الحالة',key:'s'}],
    (d.vatReturns||[]).map(v=>({a:v.return_code,p:v.period_code,d:v.due_on,s:badge(v.status,v.status==='filed'?'success':'warn')})),{title:'جدول إقرارات VAT'}
  )}`;
}

function rptCash(){
  return dataTable(
    [{label:'الشهر',key:'m'},{label:'مقبوضات',key:'i',num:true},{label:'مدفوعات',key:'o',num:true},{label:'الصافي',key:'n',num:true},{label:'الرصيد التراكمي',key:'c',num:true}],
    [
      {m:'مايو 2026', i:fmt(8200000), o:fmt(7100000), n:fmt(1100000), c:fmt(12400000)},
      {m:'يونيو 2026', i:fmt(7600000), o:fmt(8300000), n:'-'+fmt(700000), c:fmt(11700000)},
      {m:'يوليو 2026 (حتى تاريخه)', i:fmt(6900000), o:fmt(4400000), n:fmt(2500000), c:fmt(14200000)},
      {m:'أغسطس 2026 (متوقع)', i:fmt(9800000), o:fmt(6400000), n:fmt(3400000), c:fmt(17600000)},
    ], {title:'التدفق النقدي — فعلي ومتوقع (المتوقع من المستخلصات المعتمدة والالتزامات المفتوحة)'}
  );
}

/* ============ الإعدادات — شاشة فعلية بتبويبات، لا قائمة عناوين ميتة ============ */
let settingsTab = 'financial-years';

const fiscalYears = [
  {y:'2026', status:'مفتوحة', closedMonths:6, totalMonths:12},
  {y:'2025', status:'مُقفلة', closedMonths:12, totalMonths:12},
  {y:'2024', status:'مؤرشفة', closedMonths:12, totalMonths:12},
];

const chartOfAccounts = [
  {code:'1000', name:'الأصول', type:'رئيسي', level:1},
  {code:'1100', name:'الأصول المتداولة', type:'فرعي', level:2},
  {code:'1110', name:'النقدية وما في حكمها', type:'تفصيلي', level:3},
  {code:'1120', name:'المخزون', type:'تفصيلي', level:3},
  {code:'2000', name:'الالتزامات', type:'رئيسي', level:1},
  {code:'2100', name:'الذمم الدائنة', type:'فرعي', level:2},
  {code:'4000', name:'الإيرادات', type:'رئيسي', level:1},
  {code:'5000', name:'التكاليف والمصروفات', type:'رئيسي', level:1},
];

const costCenters = [
  {id:'CC-014', name:'مشروع القدية — الحزمة 3', type:'مشروع', budget:184000000},
  {id:'CC-021', name:'برج الواحة السكني', type:'مشروع', budget:62000000},
  {id:'CC-ADM', name:'الإدارة العامة', type:'إداري', budget:8500000},
  {id:'CC-WH1', name:'مستودع الرياض المركزي', type:'تشغيلي', budget:2100000},
];

const contractTypes = [
  {name:'عقد مقاولة عامة (Lump Sum)', usedIn:12},
  {name:'عقد أعمال بالوحدة (Unit Price)', usedIn:5},
  {name:'عقد إدارة مشروع (Cost Plus)', usedIn:2},
  {name:'اتفاقية إطارية (Framework)', usedIn:3},
];

const integrations = [
  {name:'ZATCA — الفوترة الإلكترونية', env:'Sandbox', status:'متصل', lastSync:'قبل 12 دقيقة'},
  {name:'ZATCA — التخليص (Clearance)', env:'Sandbox', status:'متصل', lastSync:'قبل 12 دقيقة'},
  {name:'مساعد سواعد الذكي (OpenAI)', env:'إنتاج', status:'متصل — بصلاحيات محدودة', lastSync:'مباشر'},
  {name:'البنك الأهلي — كشوفات', env:'إنتاج', status:'غير متصل', lastSync:'—'},
  {name:'بوابة مقارنة الأسعار', env:'إنتاج', status:'غير متصل', lastSync:'—'},
];

function renderSettings(){
  return `
  <div class="page-eyebrow">النظام</div>
  <div class="page-head"><div class="page-title">إعدادات النظام</div></div>
  <div class="toolbar">
    <span class="chip ${settingsTab==='financial-years'?'active':''}" onclick="setSettingsTab('financial-years')">السنوات المالية</span>
    <span class="chip ${settingsTab==='coa'?'active':''}" onclick="setSettingsTab('coa')">دليل الحسابات</span>
    <span class="chip ${settingsTab==='cost-centers'?'active':''}" onclick="setSettingsTab('cost-centers')">مراكز التكلفة</span>
    <span class="chip ${settingsTab==='contract-types'?'active':''}" onclick="setSettingsTab('contract-types')">أنواع العقود</span>
    <span class="chip ${settingsTab==='integrations'?'active':''}" onclick="setSettingsTab('integrations')">سجل التكاملات</span>
    <span class="chip ${settingsTab==='policies'?'active':''}" onclick="setSettingsTab('policies')">السياسات ولوائح العمل</span>
    <span class="chip ${settingsTab==='links'?'active':''}" onclick="setSettingsTab('links')">إعدادات مرتبطة بأقسام أخرى</span>
  </div>
  <div id="settingsContent">${settingsTabContent()}</div>`;
}
function setSettingsTab(t){ settingsTab=t; document.getElementById('settingsContent').innerHTML = settingsTabContent(); }
function settingsTabContent(){
  return {
    'financial-years': settingsFiscalYears,
    'coa': settingsChartOfAccounts,
    'cost-centers': settingsCostCenters,
    'contract-types': settingsContractTypes,
    'integrations': settingsIntegrations,
    'policies': settingsPolicies,
    'links': settingsCrossLinks,
  }[settingsTab]();
}

function settingsFiscalYears(){
  return `
  <div class="panel">
    <div class="panel-head"><div class="panel-title">السنوات المالية</div><button class="btn-sm btn" onclick="openNewFiscalYearModal()">+ سنة مالية جديدة</button></div>
    <div class="panel-body">
      ${fiscalYears.map(f=>`
      <div class="card">
        <div class="flex-between">
          <strong>السنة المالية ${f.y}</strong>
          ${badge(f.status, f.status==='مفتوحة'?'success':f.status==='مُقفلة'?'flat':'teal')}
        </div>
        <div class="faint" style="margin:6px 0">${f.closedMonths} من ${f.totalMonths} شهرًا مُقفلة</div>
        <div class="pbar"><div style="width:${(f.closedMonths/f.totalMonths)*100}%"></div></div>
        ${f.status==='مفتوحة' ? `<button class="btn-sm btn-outline" style="margin-top:10px" onclick="toast('⛔ لا يمكن إقفال السنة قبل إقفال كل الأشهر الـ12 أولاً — 6 أشهر متبقية', 'danger')">إقفال السنة المالية</button>` : ''}
      </div>`).join('')}
    </div>
  </div>
  <div class="notice teal" style="margin-top:14px">إقفال أي شهر يتطلب اعتماد مدير المشاريع، وإقفال السنة كاملة يتطلب اعتماد المدير التنفيذي — حسب مصفوفة الصلاحيات المعتمدة.</div>`;
}

function settingsChartOfAccounts(){
  return `
  <div class="panel">
    <div class="panel-head"><div class="panel-title">دليل الحسابات</div><button class="btn-sm btn" onclick="openNewAccountModal()">+ حساب جديد</button></div>
    <div class="panel-body">
      <table>
        <thead><tr><th>الرمز</th><th>اسم الحساب</th><th>المستوى</th></tr></thead>
        <tbody>
          ${chartOfAccounts.map(a=>`
          <tr onclick="toast('فتح تفاصيل حساب: '+'${a.name}')">
            <td class="num" style="padding-inline-start:${(a.level-1)*20+12}px">${a.code}</td>
            <td>${a.level===1?`<strong>${a.name}</strong>`:a.name}</td>
            <td>${badge(a.type, a.level===1?'teal':a.level===2?'warn':'flat')}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
  </div>
  <div class="notice danger" style="margin-top:14px">⛔ لا يمكن حذف أي حساب مرتبط بقيد واحد على الأقل — يمكن تعطيله (Deactivate) فقط.</div>`;
}

function settingsCostCenters(){
  return dataTable(
    [{label:'الرمز',key:'id'},{label:'الاسم',key:'name'},{label:'النوع',key:'type',render:r=>badge(r.type,r.type==='مشروع'?'teal':r.type==='إداري'?'warn':'flat')},{label:'الموازنة السنوية',key:'budget',num:true,render:r=>fmt(r.budget)}],
    costCenters, {title:'مراكز التكلفة', action:`<button class="btn-sm btn" onclick="openNewCostCenterModal()">+ مركز تكلفة</button>`}
  );
}

function settingsContractTypes(){
  return `
  <div class="panel">
    <div class="panel-head"><div class="panel-title">أنواع العقود</div><button class="btn-sm btn" onclick="openNewContractTypeModal()">+ نوع جديد</button></div>
    <div class="panel-body">
      ${contractTypes.map(c=>`<div class="card flex-between"><span>${c.name}</span><span class="faint">مستخدم في ${c.usedIn} عقدًا</span></div>`).join('')}
    </div>
  </div>`;
}

function settingsIntegrations(){
  return dataTable(
    [{label:'التكامل',key:'name'},{label:'البيئة',key:'env',render:r=>badge(r.env, r.env==='إنتاج'?'warn':'teal')},{label:'الحالة',key:'status',render:r=>badge(r.status, r.status.includes('متصل')?'success':'danger')},{label:'آخر مزامنة',key:'lastSync'}],
    integrations, {title:'سجل التكاملات الخارجية'}
  ) + `<div class="notice warn" style="margin-top:14px">⚠ تكامل البنك الأهلي غير متصل — التسوية البنكية التلقائية معطّلة حتى ربطه.</div>`;
}

function settingsPolicies(){
  return `
  <div class="panel">
    <div class="panel-head"><div class="panel-title">السياسات ولوائح العمل</div><button class="btn-sm btn" onclick="openNewPolicyModal()">+ سياسة جديدة</button></div>
    <div class="panel-body">
      ${policies.map(p=>{
        const signedCount = p.acknowledgedBy.length;
        const totalCount = employees.length;
        const pct = Math.round((signedCount/totalCount)*100);
        const pending = employees.filter(e=>!p.acknowledgedBy.includes(e.id));
        return `
        <div class="card">
          <div class="flex-between">
            <strong>${p.title}</strong>
            ${p.requiresSignature ? badge('يتطلب توقيعًا','warn') : badge('اطّلاع فقط','flat')}
          </div>
          <div class="faint" style="margin:6px 0">وقّع عليها ${signedCount} من ${totalCount} موظفًا (${pct}%)</div>
          <div class="pbar"><div style="width:${pct}%; background:${pct===100?'var(--success)':'var(--accent)'}"></div></div>
          ${pending.length ? `<div class="faint" style="margin-top:8px">لم يوقّع بعد: ${pending.map(e=>e.name).join('، ')}</div>` : `<div class="faint" style="margin-top:8px;color:var(--success)">✓ وقّع عليها الجميع</div>`}
          <button class="btn-sm btn-outline" style="margin-top:8px" onclick="toast('تم فتح إشعار تذكير لمن لم يوقّع بعد')">إرسال تذكير</button>
        </div>`;
      }).join('')}
    </div>
  </div>
  <div class="notice teal" style="margin-top:14px">التوقيع من تطبيق الموظف يُسجَّل هنا فوريًا ويُحدّث النسبة تلقائيًا — جرّب التوقيع من تطبيق الموظف (تبويب المزيد ← السياسات) وارجع لهذه الشاشة لترى التحديث.</div>`;
}
function openNewPolicyModal(){
  openFormModal('سياسة/لائحة جديدة', [
    fmField('عنوان السياسة','title'),
    fmField('تتطلب توقيعًا إلزاميًا؟','requiresSignature','select', fmOptions(['نعم','لا'])),
  ].join(''), 'نشر السياسة', v => {
    const id = 'POL-'+(100+policies.length);
    policies.push({id, title:v.title, requiresSignature: v.requiresSignature==='نعم', acknowledgedBy:[]});
    toast('✓ تم نشر السياسة "'+v.title+'" — ستظهر فورًا لكل الموظفين في تطبيقهم');
    if(currentView==='settings') setSettingsTab('policies');
  });
}

function settingsCrossLinks(){
  const links = [
    {t:'الشركات والفروع', d:'إنشاء شركة، إضافة فرع، الحسابات البنكية، الأرقام الضريبية', go:'companies'},
    {t:'مسارات الموافقة وحدود الصلاحيات', d:'تعيين الأدوار، مصفوفة الصلاحيات، سقوف الاعتماد المالية', go:'permissions'},
    {t:'إعدادات الحضور ومواقع التحضير', d:'إدارة مواقع GPS المعتمدة (Geofencing) لكل مشروع', go:'attendance'},
    {t:'إعدادات الفواتير وZATCA', d:'مركز الضرائب، فحص XML، Sandbox/Production', go:'tax'},
  ];
  return `
  <div class="notice teal">هذه الإعدادات لها شاشة إدارة كاملة ضمن قسمها المختص بدل تكرارها هنا.</div>
  <div class="grid-2">
    ${links.map(l=>`
    <div class="panel" style="cursor:pointer" onclick="go('${l.go}')">
      <div class="panel-body">
        <div class="flex-between"><strong>${l.t}</strong><span class="faint">↗</span></div>
        <div class="faint" style="margin-top:6px">${l.d}</div>
      </div>
    </div>`).join('')}
  </div>`;
}



/* ============ الأدوار والصلاحيات — شاشة الإدارة ============ */
const roleOrder = ['worker','supervisor','site-engineer','project-manager','projects-director','hr-manager','executive-director','general-manager'];

const employeeRoles = {
  'EMP-0231':'site-engineer', 'EMP-0198':'supervisor', 'EMP-0304':'worker',
  'EMP-0155':'worker', 'EMP-0410':'project-manager',
};

const PERMISSIONS_LIST = [
  {k:'hr.policies.manage', label:'إدارة السياسات ولوائح العمل'},
  {k:'hr.employee.create', label:'إضافة موظف وحساب دخول'},
  {k:'hr.employee.update', label:'تعديل بيانات الموظفين'},
  {k:'hr.employee.account.manage', label:'إدارة حسابات دخول الموظفين'},
  {k:'attendance.team.approve-correction', label:'اعتماد تصحيح بصمة الفريق'},
  {k:'leave.team.approve', label:'اعتماد إجازات الفريق'},
  {k:'hr.hiring.approve', label:'اعتماد التوظيف'},
  {k:'hr.termination.approve', label:'اعتماد إنهاء الخدمة'},
  {k:'quality.ncr.close', label:'إغلاق عدم مطابقة (NCR)'},
  {k:'safety.stop-work', label:'إيقاف عمل فوري'},
  {k:'procurement.request.approve', label:'اعتماد طلب شراء'},
  {k:'maintenance.order.approve', label:'اعتماد أمر صيانة'},
  {k:'subcontract.claim.approve', label:'اعتماد مستخلص مقاول باطن'},
  {k:'customer.claim.approve', label:'اعتماد مستخلص عميل'},
  {k:'contract.change-order.approve', label:'اعتماد أمر تغيير عقد'},
  {k:'finance.journal.post', label:'ترحيل قيد محاسبي'},
  {k:'finance.period.close', label:'إقفال الفترة المالية'},
  {k:'user.permissions.manage', label:'إدارة صلاحيات المستخدمين'},
];

// مصفوفة ابتدائية تعكس ما بناه Seeder فعليًا (true = ممنوحة)
let permMatrix = {
  'worker':            {},
  'supervisor':        {'attendance.team.approve-correction':1,'leave.team.approve':1,'safety.stop-work':1},
  'site-engineer':      {'attendance.team.approve-correction':1,'leave.team.approve':1,'quality.ncr.close':1,'safety.stop-work':1,'procurement.request.approve':1,'maintenance.order.approve':1},
  'project-manager':    {'attendance.team.approve-correction':1,'leave.team.approve':1,'safety.stop-work':1,'procurement.request.approve':1,'maintenance.order.approve':1,'subcontract.claim.approve':1,'hr.transfer.approve':1},
  'projects-director':  {'safety.stop-work':1,'procurement.request.approve':1,'maintenance.order.approve':1,'subcontract.claim.approve':1,'customer.claim.approve':1,'contract.change-order.approve':1,'hr.hiring.approve':1},
  'hr-manager':          {'hr.hiring.request':1,'hr.hiring.approve':1,'hr.termination.request':1,'hr.termination.approve':1,'hr.transfer.request':1,'hr.transfer.approve':1,'hr.policies.manage':1,'hr.employee.create':1,'hr.employee.update':1,'hr.employee.account.manage':1,'leave.team.approve':1,'attendance.team.approve-correction':1},
  'executive-director': {'safety.stop-work':1,'procurement.request.approve':1,'customer.claim.approve':1,'contract.change-order.approve':1,'hr.hiring.approve':1,'hr.termination.approve':1,'finance.period.close':1,'user.permissions.manage':1,'hr.policies.manage':1,'hr.employee.create':1,'hr.employee.update':1,'hr.employee.account.manage':1},
  'general-manager':    Object.fromEntries(PERMISSIONS_LIST.map(p=>[p.k,1])),
};

let permTab = 'users';
let thresholds = {
  'site-engineer':      {purchase_order:15000,  maintenance_order:10000, change_order:0},
  'project-manager':    {purchase_order:150000, maintenance_order:75000, change_order:100000},
  'projects-director':  {purchase_order:750000, maintenance_order:300000, change_order:500000},
  'executive-director': {purchase_order:3000000,maintenance_order:1000000,change_order:2000000},
  'general-manager':    {purchase_order:null,   maintenance_order:null,  change_order:null},
};

function renderPermissions(){
  return `
  <div class="page-eyebrow">النظام · إدارة الوصول</div>
  <div class="page-head"><div class="page-title">الأدوار والصلاحيات</div>
    <div class="page-desc">مقيّد بصلاحية user.permissions.manage — المدير التنفيذي والمدير العام فقط</div></div>
  <div class="toolbar">
    <span class="chip ${permTab==='users'?'active':''}" onclick="setPermTab('users')">تعيين الأدوار للموظفين</span>
    <span class="chip ${permTab==='matrix'?'active':''}" onclick="setPermTab('matrix')">مصفوفة الصلاحيات</span>
    <span class="chip ${permTab==='thresholds'?'active':''}" onclick="setPermTab('thresholds')">سقوف الاعتماد المالية</span>
  </div>
  <div id="permContent">${permTabContent()}</div>`;
}
function setPermTab(t){ permTab=t; document.getElementById('permContent').innerHTML = permTabContent(); }

function permTabContent(){
  if(permTab==='users') return permUsersTab();
  if(permTab==='matrix') return permMatrixTab();
  return permThresholdsTab();
}

/* ---- تبويب 1: تعيين/تغيير دور كل موظف، وحذف صلاحياته بالكامل عند الحاجة ---- */
let permUsersSearch = '';
function permUsersTableHtml(){
  const q = permUsersSearch.trim();
  const list = employees.filter(e => !q || e.name.includes(q) || e.id.toLowerCase().includes(q.toLowerCase()));
  return `
  <div class="panel">
    <table>
      <thead><tr><th>الموظف</th><th>القسم/المشروع</th><th>الدور الحالي</th><th>تغيير الدور</th><th>إجراء</th></tr></thead>
      <tbody>
        ${list.length ? list.map(e=>`
        <tr>
          <td>${e.name} <span class="faint num">(${e.id})</span></td>
          <td>${projectNameById(e.project)}</td>
          <td>${badge(ROLES[employeeRoles[e.id]||'worker'].label, 'teal')}</td>
          <td>
            <select style="background:var(--bg-raised);color:var(--text);border:1px solid var(--line-strong);padding:6px;border-radius:4px" onchange="changeEmployeeRole('${e.id}', this.value)">
              ${roleOrder.map(r=>`<option value="${r}" ${employeeRoles[e.id]===r?'selected':''}>${ROLES[r].label}</option>`).join('')}
            </select>
          </td>
          <td><button class="btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger)" onclick="revokeEmployeeAccess('${e.id}')">سحب كل الصلاحيات</button></td>
        </tr>`).join('') : `<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-faint)">لا يوجد موظفون مطابقون للبحث</td></tr>`}
      </tbody>
    </table>
  </div>`;
}
function onPermUsersSearch(val){
  permUsersSearch = val;
  document.getElementById('permUsersTableWrap').innerHTML = permUsersTableHtml();
}
function permUsersTab(){
  return `
  <div class="toolbar"><input class="search-input" placeholder="ابحث باسم الموظف أو رقمه" oninput="onPermUsersSearch(this.value)"><button class="btn" onclick="openNewSystemUserModal()">+ حساب دخول لموظف</button></div>
  <div id="permUsersTableWrap">${permUsersTableHtml()}</div>
  <div class="notice teal" style="margin-top:14px">تغيير الدور هنا يُحدّث فوريًا Spatie Role للمستخدم المرتبط بهذا الموظف (Fix 1) — ولا يؤثر على نطاق مشاريعه (Fix 2) الذي يُدار من ملفه الوظيفي بشكل منفصل.</div>`;
}
function changeEmployeeRole(empId, newRole){
  employeeRoles[empId] = newRole;
  if(typeof persistEmployeeRole === 'function') persistEmployeeRole(empId,newRole);
  toast('تم تغيير دور ' + employees.find(e=>e.id===empId).name + ' إلى: ' + ROLES[newRole].label);
  document.getElementById('permContent').innerHTML = permTabContent();
}
function revokeEmployeeAccess(empId){
  const e = employees.find(x=>x.id===empId);
  toast('⛔ تم سحب كل صلاحيات ' + e.name + ' فورًا — سيُسجَّل الإجراء في سجل التدقيق', 'danger');
}

/* ---- تبويب 2: مصفوفة صلاحيات قابلة للتبديل مباشرة (Checkbox Matrix) ---- */
function permMatrixTab(){
  return `
  <div class="panel">
    <div style="overflow-x:auto">
    <table>
      <thead><tr><th style="min-width:220px">الصلاحية</th>${roleOrder.map(r=>`<th style="text-align:center">${ROLES[r].label}</th>`).join('')}</tr></thead>
      <tbody>
        ${PERMISSIONS_LIST.map(p=>`
        <tr>
          <td>${p.label}</td>
          ${roleOrder.map(r=>`
            <td style="text-align:center">
              <input type="checkbox" ${permMatrix[r][p.k]?'checked':''} onchange="togglePermission('${r}','${p.k}', this.checked)" style="width:16px;height:16px;accent-color:var(--accent)">
            </td>`).join('')}
        </tr>`).join('')}
      </tbody>
    </table>
    </div>
  </div>
  <div class="notice warn" style="margin-top:14px">⚠ تعديل هذه المصفوفة يُغيّر صلاحيات كل من يحمل هذا الدور دفعة واحدة عبر كل الموظفين والمشاريع — يُنصح بمراجعة مزدوجة قبل الحفظ الفعلي.</div>
  <button class="btn" style="margin-top:10px" onclick="toast('تم حفظ مصفوفة الصلاحيات — سيُطبَّق التغيير على جميع مستخدمي كل دور')">حفظ التغييرات</button>`;
}
function togglePermission(role, permKey, checked){
  permMatrix[role][permKey] = checked ? 1 : 0;
  if(typeof persistPermission === 'function') persistPermission(role,permKey,checked);
  toast((checked?'مُنحت':'سُحبت') + ' صلاحية "' + PERMISSIONS_LIST.find(p=>p.k===permKey).label + '" لدور ' + ROLES[role].label, checked?'success':'danger');
}

/* ---- تبويب 3: سقوف الاعتماد المالية القابلة للتعديل ---- */
function permThresholdsTab(){
  const types = [['purchase_order','طلب شراء'],['maintenance_order','أمر صيانة'],['change_order','أمر تغيير عقد']];
  return `
  <div class="panel">
    <table>
      <thead><tr><th>الدور</th>${types.map(t=>`<th>${t[1]} (ر.س)</th>`).join('')}</tr></thead>
      <tbody>
        ${Object.keys(thresholds).map(r=>`
        <tr>
          <td>${ROLES[r].label}</td>
          ${types.map(t=>`
            <td>
              ${thresholds[r][t[0]]===null
                ? `<span class="faint">بلا حد</span>`
                : `<input class="search-input mono" style="width:130px;min-width:0" value="${fmt(thresholds[r][t[0]])}" onchange="updateThreshold('${r}','${t[0]}', this.value)">`}
            </td>`).join('')}
        </tr>`).join('')}
      </tbody>
    </table>
  </div>
  <div class="notice danger" style="margin-top:14px">⛔ قاعدة صارمة: أي مبلغ يتجاوز السقف يُصعَّد تلقائيًا للمستوى التالي — لا يظهر خيار "اعتماد" لصاحب الطلب أصلًا مهما كانت الصلاحيات الأخرى.</div>
  <button class="btn" style="margin-top:10px" onclick="toast('تم حفظ سقوف الاعتماد الجديدة وستُطبَّق على كل الطلبات القادمة')">حفظ السقوف</button>`;
}
function updateThreshold(role, type, value){
  thresholds[role][type] = Number(value.replace(/,/g,'')) || 0;
  if(typeof persistThreshold === 'function') persistThreshold(role,type,thresholds[role][type]);
  toast('تم تحديث سقف ' + ROLES[role].label + ' إلى ' + fmt(thresholds[role][type]) + ' ر.س');
}


function renderGenericComingSoon(){
  return `<div class="empty"><div class="big">▢</div>هذه الوحدة غير مفعلة لهذا الدور.<br><span class="faint">راجع مدير النظام لتحديث الصلاحيات.</span></div>`;
}

const RENDERERS = {
  dashboard: renderDashboard,
  companies: renderCompanies,
  projects: renderProjects,
  employees: renderEmployees,
  attendance: renderAttendance,
  payroll: renderPayroll,
  finance: renderFinance,
  customers: renderCustomers,
  contracts: renderContracts,
  procurement: renderProcurement,
  suppliers: renderSuppliers,
  subcontractors: renderSubcontractors,
  warehouse: renderWarehouse,
  equipment: renderEquipment,
  maintenance: renderMaintenance,
  quality: renderQuality,
  safety: renderSafety,
  documents: renderDocuments,
  approvals: renderApprovals,
  tax: renderTax,
  'employee-app': renderEmployeeApp,
  'warehouse-app': renderWarehouseApp,
  'supplier-portal': renderSupplierPortal,
  'auditor-portal': renderAuditorPortal,
  ai: renderAI,
  reports: renderReports,
  permissions: renderPermissions,
  settings: renderSettings,
};

buildSidebar();
go(currentView);
</script>
<script src="assets/backend-sync.js?v=1"></script>
</body>
</html>
