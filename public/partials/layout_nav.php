<?php
require_once __DIR__.'/auth.php';

$u = current_user($db) ?? ['name'=>'User','role'=>'EMPLOYEE','position'=>null];
$page_title = $page_title ?? 'Dashboard';

function uri_starts_with(string $path): bool {
  return strpos($_SERVER['REQUEST_URI'] ?? '', $path) === 0;
}
function any_active(array $paths): bool {
  foreach ($paths as $p) if (uri_starts_with($p)) return true;
  return false;
}

$cards_paths = [
  '/sems/public/admin/cards_register.php',
  '/sems/public/admin/card_change.php',
];
$cards_open = any_active($cards_paths);

$sections = [];
if (($u['role'] ?? '') === 'ADMIN') {
  $sections[] = ['label'=>'Admin','items'=>[
    ['Dashboard','/sems/public/admin/dashboard.php','dashboard'],
    ['Users','/sems/public/admin/users.php','manage_accounts'],

  ]];
}

if (($u['role'] ?? '') === 'HR' || ($u['role'] ?? '') === 'ADMIN') {
  $sections[] = ['label'=>'HR','items'=>[
     ['Dashboard','/sems/public/hr/dashboard.php','dashboard'],
    ['Employees','/sems/public/admin/employees.php','group'],
    ['Employee Portal Users','/sems/public/hr/users.php','manage_accounts'],
    ['Approvals','/sems/public/hr/approvals.php','assignment_turned_in'],
    ['Payroll','/sems/public/hr/payroll_run.php','payments'],
    ['Attendance History','/sems/public/hr/attendance_history.php','history'],
  ]];
}

if (($u['role'] ?? '') === 'EMPLOYEE') {
  $sections[] = ['label'=>'Employee','items'=>[
    ['Dashboard','/sems/public/employee/dashboard.php','dashboard'],
    ['My Attendance','/sems/public/employee/attendance.php','schedule'],
    ['Payslips','/sems/public/employee/payslips.php','receipt_long'],
  ]];
}
$sections[] = ['label'=>'Profile','items'=>[
  ['Profile Details','/sems/public/employee/profile.php','storefront']
]];
?>
<style>
  .sb-head{
    display:flex !important; align-items:center; gap:.75rem;
    padding:.9rem .9rem; border-bottom:1px solid rgba(255,255,255,.08);
  }
  .sb-head img{width:34px;height:34px;border-radius:.5rem;object-fit:contain}
  #overlay{position:fixed; inset:0; background:rgba(2,6,23,.6);
    display:none; opacity:1; pointer-events:none; z-index:60;}
  #overlay.show{display:block; pointer-events:auto;}

  .menu{ position:relative; }
  .menu-btn{
    display:flex; align-items:center; gap:.5rem;
    padding:.42rem .6rem; border-radius:.6rem;
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12);
    cursor:pointer;
  }
  .menu-items{
    position:absolute; right:0; top:calc(100% + .4rem);
    min-width:180px; padding:.4rem; border-radius:.6rem;
    background:#0b1324; border:1px solid rgba(255,255,255,.12);
    box-shadow:0 12px 30px rgba(0,0,0,.45);
    display:none; z-index:70; /* above overlay */
  }
  .menu.open .menu-items{ display:block; }

  .sb ul li.has-children > .parent {
    display:flex; align-items:center; gap:.5rem; width:100%;
    padding:.5rem .75rem; border-radius:.5rem; cursor:pointer;
  }
  .sb ul li.has-children .chev { margin-left:auto; transition:transform .2s ease; }
  .sb ul li.has-children.open .chev { transform:rotate(180deg); }
  .sb ul li.has-children ul.children { display:none; margin:.25rem 0 .25rem 2.25rem; }
  .sb ul li.has-children.open ul.children { display:block; }
  .sb ul li.has-children ul.children a { padding:.45rem .6rem; border-radius:.5rem; display:flex; gap:.5rem; align-items:center; }
</style>

<nav id="sb" class="sb" aria-label="Sidebar">
  <div class="sb-head">
    <img src="/sems/public/assets/logo.png" alt="SEMS">
    <div>
      <div style="font-weight:700">SEMS</div>
      <div class="sb-role"><?= htmlspecialchars($u['role'] ?? '') ?></div>
    </div>
  </div>

  <?php foreach ($sections as $section): ?>
    <div class="sb-section"><?= htmlspecialchars($section['label']) ?></div>
    <ul>
      <?php foreach ($section['items'] as [$label,$href,$icon]):
        $is = uri_starts_with($href) ? 'active' : ''; ?>
        <li>
          <a class="<?= $is ?>" href="<?= $href ?>">
            <span class="ms"><?= $icon ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </a>
        </li>
      <?php endforeach; ?>

      <?php if ($section['label']==='HR'): ?>
      
        <li class="has-children <?= $cards_open ? 'open' : '' ?>">
          <button class="parent" type="button">
            <span class="ms">credit_card</span>
            <span>Cards</span>
            <span class="ms chev">expand_more</span>
          </button>
          <ul class="children">
            <li>
              <a class="<?= uri_starts_with('/sems/public/admin/cards_register.php') ? 'active' : '' ?>"
                 href="/sems/public/admin/cards_register.php">
                <span class="ms">add_card</span>
                <span>Register Card</span>
              </a>
            </li>
            <li>
              <a class="<?= uri_starts_with('/sems/public/admin/card_change.php') ? 'active' : '' ?>"
                 href="/sems/public/admin/card_change.php">
                <span class="ms">sync_alt</span>
                <span>Change Card</span>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  <?php endforeach; ?>
</nav>

<div id="overlay"></div>

<header class="topbar" role="banner">
  <button id="toggle" class="hambtn" aria-label="Toggle navigation">
    <span class="material-symbols-outlined">menu</span>
  </button>
  <div style="font-weight:600"><?= htmlspecialchars($page_title) ?></div>

 
  <div class="menu" id="profileMenu">
    <button class="menu-btn" type="button" title="Account" aria-label="Account">
      <span class="material-symbols-outlined">account_circle</span>
      <span class="material-symbols-outlined" style="font-size:18px;margin-left:.25rem">expand_more</span>
    </button>
    <div class="menu-items" role="menu">
      <?php if(($u['role'] ?? '')==='EMPLOYEE'): ?>
        <a href="/sems/public/employee/dashboard.php" role="menuitem">My Dashboard</a>
      <?php endif; ?>
      <a href="/sems/public/auth/logout.php" role="menuitem">Logout</a>
    </div>
  </div>
</header>

<div id="main"><div class="container">

<script>
document.querySelectorAll('.sb li.has-children > .parent').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    btn.parentElement.classList.toggle('open');
  });
});


const pm = document.getElementById('profileMenu');
pm?.querySelector('.menu-btn')?.addEventListener('click', (e)=>{
  e.stopPropagation();
  pm.classList.toggle('open');
});
document.addEventListener('click', (e)=>{
  if(!pm?.contains(e.target)) pm?.classList.remove('open');
});
</script>
