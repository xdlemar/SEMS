<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(($page_title ?? 'SEMS').' · SEMS') ?></title>

<!-- Inter + Material Symbols (icons) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;600&display=swap" rel="stylesheet">

<!-- Tailwind for page content (cards/tables/forms) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{charcoal:'#0F172A'},boxShadow:{soft:'0 6px 24px rgba(2,10,24,.06), 0 2px 6px rgba(2,10,24,.06)'}}}}</script>

<style>
:root{
  --sb-w:260px;
  --brand:#0d1117;   /* topbar */
  --sidebar:#0f1a2b; /* sidebar */
  --sidebar-hover:#14233c;
  --active:#134e4a;  /* active nav pill */
  --surface:#eef2f6; /* page bg */
  --card:#ffffff;    /* cards */
  --ink:#0f172a;
  --muted:#64748b;
  --accent:#10b981;
}
*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--surface);
  color:var(--ink);
}
/* Top bar */
.topbar{
  position:sticky; top:0; z-index:50; height:56px;
  display:flex; align-items:center; gap:.75rem;
  background:var(--brand); color:#e7eef9;
  border-bottom:1px solid #0e1520; padding-inline:14px;
  transition: padding-left .2s ease;
}
.hambtn{width:36px;height:36px;display:inline-grid;place-items:center;border-radius:.6rem;border:1px solid #1b2433;background:#101826;color:#dbe7ff;cursor:pointer}
.hambtn:hover{background:#132033}
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}

/* Profile menu */
.menu{position:relative;margin-left:auto}
.menu-btn{display:flex;align-items:center;gap:.55rem;cursor:pointer;background:#122536;color:#e5eef9;border:1px solid #0f2132;border-radius:.6rem;padding:.45rem .7rem}
.menu-btn .avatar{width:24px;height:24px;border-radius:50%;background:#273447;display:inline-grid;place-items:center;font-size:.8rem}
.menu-items{position:absolute;right:0;top:calc(100% + .4rem);min-width:200px;background:#0f172a;color:#e5eef9;border:1px solid #13243a;border-radius:.7rem;display:none;box-shadow:0 10px 30px rgba(0,0,0,.35);overflow:hidden}
.menu.open .menu-items{display:block}
.menu-items a{display:block;padding:.6rem .9rem;color:#e5eef9}
.menu-items a:hover{background:#112036}
.badge{background:#1f2937;color:#e5eef9;font-size:.75rem;border-radius:.45rem;padding:.12rem .4rem}

/* Sidebar */
.sb{position:fixed;inset:0 auto 0 0;z-index:60;width:var(--sb-w);background:var(--sidebar);color:#e7eef9;border-right:1px solid #0f2033;transform:translateX(-100%);transition:transform .2s ease;display:flex;flex-direction:column}
.sb.open{transform:translateX(0)}
.sb-head{display:flex;gap:.6rem;align-items:center;padding:14px}
.sb-head img{width:28px;height:28px;border-radius:50%;background:#2b3038}
.sb-role{font-size:.78rem;opacity:.75;margin-left:2rem}
.sb ul{list-style:none;margin:8px 8px 16px;padding:0}
.sb li{margin:.1rem 0}
.sb a{display:flex;align-items:center;gap:.6rem;color:#e7eef9;padding:10px 12px;border-radius:10px}
.sb a:hover{background:var(--sidebar-hover)}
.sb a.active{background:var(--active)}
.sb-section{padding:.6rem .9rem;color:#93a4bf;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em}
.ms{font-family:'Material Symbols Outlined';font-weight:400;font-style:normal;font-size:20px;line-height:1}

/* Overlay (mobile) */
#overlay{position:fixed;inset:0;background:#0b1220cc;z-index:55;display:none}
#overlay.show{display:block}

/* Main area (pushed when docked) */
#main{min-height:calc(100vh - 56px);transition:padding-left .2s ease;}
.container{max-width:1100px;margin:0 auto;padding:18px}

/* Docked behavior desktop */
@media (min-width: 992px){
  body.with-sb .topbar{padding-left:calc(14px + var(--sb-w));}
  body.with-sb #main{padding-left:var(--sb-w);}
}

/* Cards / table helpers for your content */
.card{background:var(--card);color:var(--ink);border-radius:14px;padding:16px;border:1px solid rgba(15,23,42,.06);box-shadow:0 6px 24px rgba(2,10,24,.06),0 2px 6px rgba(2,10,24,.06)}
.table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:14px;overflow:hidden}
.table th,.table td{padding:12px 14px;text-align:left;border-bottom:1px solid rgba(15,23,42,.06)}
.table th{font-weight:600;color:#0f172a;background:#f7fafc}
.table tr:last-child td{border-bottom:none}
.table tr:hover td{background:#fafcff}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .8rem;background:#101826;color:#e7eef9;border:1px solid #1b2433;border-radius:10px}
.btn:hover{background:#152238}
.btn-success{background:#16a34a;border-color:#0e8a3b}
.btn-success:hover{background:#12813f}
.btn-danger{background:#dc2626;border-color:#b91c1c}
</style>
</head>
<body>
