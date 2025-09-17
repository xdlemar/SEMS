<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$id=(int)($_GET['id']??0); $isEdit=$id>0;

if($_SERVER['REQUEST_METHOD']==='POST'){
  $username=trim($_POST['username']??'');
  $password=$_POST['password']??'';
  $role=$_POST['role']??'EMPLOYEE';
  $employee_id=($_POST['employee_id']??'')? (int)$_POST['employee_id'] : null;

  if($isEdit){
    if($password!==''){
      $hash=password_hash($password,PASSWORD_DEFAULT);
      $st=$db->prepare("UPDATE users SET username=?, role=?, employee_id=? , password_hash=? WHERE id=?");
      $st->execute([$username,$role,$employee_id,$hash,$id]);
    }else{
      $st=$db->prepare("UPDATE users SET username=?, role=?, employee_id=? WHERE id=?");
      $st->execute([$username,$role,$employee_id,$id]);
    }
  }else{
    $hash=password_hash($password ?: 'changeme123', PASSWORD_DEFAULT);
    $st=$db->prepare("INSERT INTO users (username,password_hash,role,employee_id) VALUES (?,?,?,?)");
    $st->execute([$username,$hash,$role,$employee_id]);
  }
  header('Location: /sems/public/admin/users.php'); exit;
}

$uRow=['username'=>'','role'=>'EMPLOYEE','employee_id'=>null];
if($isEdit){ $st=$db->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); $uRow=$st->fetch(PDO::FETCH_ASSOC); }

$emps=$db->query("SELECT id, CONCAT(emp_no,' · ',fname,' ',lname) AS label FROM employees WHERE active=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$page_title=($isEdit?'Edit':'Add').' User';
require __DIR__.'/../partials/layout_head.php'; require __DIR__.'/../partials/layout_nav.php';
?>
<div class="max-w-3xl mx-auto">
  <form method="post" class="card space-y-4">
    <div class="text-lg font-semibold flex items-center gap-2"><span class="material-symbols-outlined">manage_accounts</span> <?= htmlspecialchars($page_title) ?></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm">Username</label>
        <input name="username" value="<?= htmlspecialchars($uRow['username']) ?>" placeholder="j.doe" class="mt-2 w-full rounded-lg border-slate-300" required>
      </div>
      <div>
        <label class="text-sm">Password <?= $isEdit ? '(leave blank to keep)' : '' ?></label>
        <input name="password" type="password" placeholder="<?= $isEdit ? '••••••••' : 'Set initial password' ?>" class="mt-2 w-full rounded-lg border-slate-300">
      </div>
      <div>
        <label class="text-sm">Role</label>
        <select name="role" class="mt-2 w-full rounded-lg border-slate-300">
          <?php foreach(['ADMIN','HR','MANAGER','EMPLOYEE'] as $r): ?>
            <option <?= $uRow['role']===$r?'selected':'' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm">Link to Employee (for employee portal)</label>
        <select name="employee_id" class="mt-2 w-full rounded-lg border-slate-300">
          <option value="">— none —</option>
          <?php foreach($emps as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= ((int)$uRow['employee_id']===(int)$e['id'])?'selected':'' ?>><?= htmlspecialchars($e['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="flex justify-end gap-2">
      <a href="/sems/public/admin/users.php" class="btn">Cancel</a>
      <button class="btn btn-success">Save</button>
    </div>
  </form>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
