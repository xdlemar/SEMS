<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);
$id=(int)($_POST['id']??0);
if($id){
  $db->prepare("UPDATE employees SET active=0 WHERE id=?")->execute([$id]); // soft delete
}
header('Location: /sems/public/admin/employees.php');
