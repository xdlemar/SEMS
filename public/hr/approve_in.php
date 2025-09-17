<?php
require __DIR__.'/../partials/auth.php'; require_role(['HR','ADMIN']);
$id=(int)($_POST['id']??0); $act=$_POST['act']??'';
$u=current_user($db);
if($id && in_array($act,['approve','reject'])){
  if($act==='approve'){
    $st=$db->prepare("UPDATE attendance_logs SET approved_by=?, approved_at=NOW(), verification_status='MATCH' WHERE id=? AND type='IN'");
  }else{
    $st=$db->prepare("UPDATE attendance_logs SET approved_by=?, approved_at=NOW(), verification_status='MISMATCH', reject_reason='Manual reject' WHERE id=? AND type='IN'");
  }
  $st->execute([$u['id'],$id]);
}
header('Location: /sems/public/hr/approvals.php');
