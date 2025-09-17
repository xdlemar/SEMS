<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$id=(int)($_POST['id']??0);
if($id){ $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]); }
header('Location: /sems/public/admin/users.php');
