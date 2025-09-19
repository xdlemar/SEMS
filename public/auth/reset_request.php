<?php
require __DIR__.'/../partials/auth.php';
$page_title='Reset password';
require __DIR__.'/../partials/layout_head.php';

$sent=false; $msg=null;

function user_lookup(PDO $db, string $ident){
  // Prefer users.email if present, else employees.email, else match by username
  $sql = "SELECT u.id, u.username,
                 COALESCE(u.email, e.email) AS email
          FROM users u
          LEFT JOIN employees e ON e.id=u.employee_id
          WHERE u.username = :i OR u.email = :i OR e.email = :i
          LIMIT 1";
  try{
    $st=$db->prepare($sql); $st->execute([':i'=>$ident]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }catch(Throwable $e){
    // If users.email column doesn't exist, fallback to username + employees.email only
    $st=$db->prepare("SELECT u.id, u.username, e.email
                      FROM users u LEFT JOIN employees e ON e.id=u.employee_id
                      WHERE u.username=:i OR e.email=:i LIMIT 1");
    $st->execute([':i'=>$ident]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $ident = trim($_POST['ident'] ?? '');
  if($ident===''){ $msg='Please enter your username or email.'; }
  else{
    if($u = user_lookup($db,$ident)){
      if(empty($u['email'])){
        $msg = 'Your account has no email address on file. Please contact an administrator.';
      }else{
        $token = bin2hex(random_bytes(32));
        $exp   = date('Y-m-d H:i:s', time()+60*30); // 30 minutes

        $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)")
           ->execute([$u['id'],$token,$exp]);

        $base   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                  . '://' . $_SERVER['HTTP_HOST'];
        $link   = $base . '/sems/public/auth/reset.php?token='.$token;

        // Try to send email (works if mail() is configured). Otherwise we show the link for local dev.
        $subject = 'SEMS password reset';
        $body    = "Hi {$u['username']},\n\nClick the link to reset your password:\n{$link}\n\n"
                 . "This link expires in 30 minutes. If you didn't request this, you can ignore this email.";
        $headers = "From: no-reply@sems.local\r\n";

        $ok = @mail($u['email'], $subject, $body, $headers);
        $sent = true;
        $msg  = $ok
          ? 'If that account exists, a reset link has been sent to your email.'
          : 'Email couldn’t be sent (local dev). Use the link below to reset now.';
        if(!$ok){ $dev_link = $link; }
      }
    }else{
      // Don’t disclose account existence
      $sent = true;
      $msg  = 'If that account exists, a reset link has been sent to your email.';
    }
  }
}
?>
<div class="max-w-md mx-auto p-6">
  <div class="bg-white rounded-xl shadow-soft border p-6 space-y-4">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">key</span> Reset your password
    </div>

    <?php if($msg): ?>
      <div class="p-3 rounded-lg <?= $sent ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?> border">
        <?= htmlspecialchars($msg) ?>
        <?php if(isset($dev_link)): ?>
          <div class="mt-2 text-xs break-all"><strong>Dev link:</strong> <a class="underline" href="<?= htmlspecialchars($dev_link) ?>"><?= htmlspecialchars($dev_link) ?></a></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if(!$sent): ?>
      <form method="post" class="space-y-4">
        <div>
          <label class="text-sm">Username or Email</label>
          <input name="ident" class="mt-2 w-full rounded-lg border-slate-300 px-3 h-10" placeholder="your.username or you@company.com" required>
        </div>
        <div class="flex justify-end">
          <button class="px-4 h-10 rounded-lg bg-charcoal text-white">Send reset link</button>
        </div>
      </form>
    <?php else: ?>
      <div class="text-sm text-slate-600">You can close this page and check your inbox.</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
