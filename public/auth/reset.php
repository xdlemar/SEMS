<?php
require __DIR__.'/../partials/auth.php';
$page_title='Set new password';
require __DIR__.'/../partials/layout_head.php';

$token = $_GET['token'] ?? '';
$valid = false; $row=null; $msg=null; $done=false;

if($token){
  $q=$db->prepare("SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.username
                   FROM password_resets pr
                   JOIN users u ON u.id=pr.user_id
                   WHERE pr.token=? LIMIT 1");
  $q->execute([$token]); $row=$q->fetch(PDO::FETCH_ASSOC);

  if($row){
    if((int)$row['used']===1){ $msg='This reset link was already used.'; }
    elseif(strtotime($row['expires_at']) < time()){ $msg='This reset link has expired.'; }
    else{ $valid=true; }
  }else{
    $msg='Invalid reset link.';
  }
}else{
  $msg='No token.';
}

if($valid && $_SERVER['REQUEST_METHOD']==='POST'){
  $p1 = $_POST['p1'] ?? ''; $p2 = $_POST['p2'] ?? '';
  if($p1==='' || strlen($p1)<8){ $msg='Password must be at least 8 characters.'; }
  elseif($p1 !== $p2){ $msg='Passwords do not match.'; }
  else{
    $hash = password_hash($p1, PASSWORD_DEFAULT);
    $db->beginTransaction();
    try{
      $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash,$row['user_id']]);
      $db->prepare("UPDATE password_resets SET used=1, used_at=NOW() WHERE id=?")->execute([$row['id']]);
      // (optional) invalidate other tokens for this user
      $db->prepare("UPDATE password_resets SET used=1, used_at=NOW()
                    WHERE user_id=? AND used=0 AND id<>?")->execute([$row['user_id'],$row['id']]);
      $db->commit();
      $done = true;
    }catch(Throwable $e){
      $db->rollBack(); $msg='Something went wrong. Please try again.';
    }
  }
}
?>
<div class="max-w-md mx-auto p-6">
  <div class="bg-white rounded-xl shadow-soft border p-6 space-y-4">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">vpn_key</span>
      Set a new password
    </div>

    <?php if($msg): ?>
      <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if($done): ?>
      <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">Your password was updated. You can now <a class="underline" href="/sems/public/auth/login.php">sign in</a>.</div>
    <?php elseif($valid): ?>
      <form method="post" class="space-y-4">
        <div>
          <label class="text-sm">New password</label>
          <input type="password" name="p1" class="mt-2 w-full rounded-lg border-slate-300 px-3 h-10" required>
          <div class="text-xs text-slate-500 mt-1">Minimum 8 characters.</div>
        </div>
        <div>
          <label class="text-sm">Confirm password</label>
          <input type="password" name="p2" class="mt-2 w-full rounded-lg border-slate-300 px-3 h-10" required>
        </div>
        <div class="flex justify-end">
          <button class="px-4 h-10 rounded-lg bg-charcoal text-white">Update password</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
