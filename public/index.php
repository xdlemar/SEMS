<?php
require __DIR__.'/partials/auth.php'; login_required(); $u=current_user($db);
switch($u['role']){
  case 'ADMIN':   header("Location: /sems/public/admin/employees.php"); break;
  case 'HR':      header("Location: /sems/public/hr/approvals.php"); break;
  case 'MANAGER': header("Location: /sems/public/manager/dashboard.php"); break;
  default:        header("Location: /sems/public/employee/dashboard.php"); break;
}
exit;
