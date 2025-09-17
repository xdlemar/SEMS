<?php
require_once __DIR__.'/auth.php';
$u = current_user($db) ?? ['name'=>'User','role'=>'EMPLOYEE','position'=>null];
$page_title = $page_title ?? 'Dashboard';

function sems_nav_sections(string $role): array {
  $sections = [];
  if ($role === 'ADMIN') {
    $sections[] = ['label'=>'Admin','items'=>[
      ['Employees','/sems/public/admin/employees.php','group'],
      ['Users','/sems/public/admin/users.php','manage_accounts'],
      ['Register Card','/sems/public/admin/cards_register.php','credit_card'],
    ]];
  }
  if ($role === 'HR' || $role === 'ADMIN') {
    $sections[] = ['label'=>'HR','items'=>[
      ['Approvals','/sems/public/hr/approvals.php','assignment_turned_in'],
      ['Payroll','/sems/public/hr/payroll_run.php','payments'],
      ['Attendance History','/sems/public/hr/attendance_history.php','history'],
    ]];
  }
  if ($role === 'MANAGER') {
    $sections[] = ['label'=>'Manager','items'=>[
      ['Dashboard','/sems/public/manager/dashboard.php','insights'],
    ]];
  }
  if ($role === 'EMPLOYEE') { // Only employees see their portal
    $sections[] = ['label'=>'Employee','items'=>[
      ['Dashboard','/sems/public/employee/dashboard.php','dashboard'],
      ['My Attendance','/sems/public/employee/attendance.php','schedule'],
      ['Payslips','/sems/public/employee/payslips.php','receipt_long'],
    ]];
  }
  $sections[] = ['label'=>'Profile','items'=>[
    ['Profile Details','/sems/public/employee/profile.php','storefront']
  ]];
  return $sections;
}
$sections = sems_nav_sections($u['role']);
?>
<nav id="sb" class="sb" aria-label="Sidebar">
  <div class="sb-head">
    <img src="/sems/public/assets/logo.png" alt="SEMS">
    <div>
      <div style="font-weight:700">SEMS</div>
      <div class="sb-role"><?= htmlspecialchars($u['role']) ?></div>
    </div>
  </div>
  <?php foreach ($sections as $section): ?>
    <div class="sb-section"><?= htmlspecialchars($section['label']) ?></div>
    <ul>
      <?php foreach ($section['items'] as [$label,$href,$icon]):
        $is = (strpos($_SERVER['REQUEST_URI'],$href)===0) ? 'active' : ''; ?>
        <li><a class="<?= $is ?>" href="<?= $href ?>"><span class="ms"><?= $icon ?></span><span><?= htmlspecialchars($label) ?></span></a></li>
      <?php endforeach; ?>
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
    <button class="menu-btn" type="button" title="Account">
     
      <span><?= htmlspecialchars($u['name'] ?? 'User') ?></span>
      <?php if(!empty($u['position'])): ?><span class="badge"><?= htmlspecialchars($u['position']) ?></span><?php endif; ?>
   
      <span class="material-symbols-outlined" style="font-size:18px;margin-left:.25rem">expand_more</span>
    </button>
    <div class="menu-items" role="menu">
      <?php if($u['role']==='EMPLOYEE'): ?>
        <a href="/sems/public/employee/dashboard.php" role="menuitem">My Dashboard</a>
      <?php endif; ?>
      <a href="/sems/public/auth/logout.php" role="menuitem">Logout</a>
    </div>
  </div>
</header>

<div id="main"><div class="container">
