<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$id=(int)($_GET['id']??0); $isEdit=$id>0;

/** Generate a random, human-friendly unique Emp #. */
function generate_emp_no(PDO $db, string $prefix='EMP'): string {
  for ($i=0; $i<10; $i++) {
    $rand = strtoupper(str_pad(base_convert(random_int(0, 36**5-1), 10, 36), 5, '0', STR_PAD_LEFT));
    $no = sprintf('%s-%s-%s', $prefix, date('ymd'), $rand);  // e.g. EMP-250915-7KQ4Z
    $st = $db->prepare("SELECT 1 FROM employees WHERE emp_no=? LIMIT 1");
    $st->execute([$no]);
    if (!$st->fetch()) return $no; // not used yet
  }
  // Fallback (extremely unlikely)
  return $prefix.'-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
}

/** Save profile photo if provided; returns public URL or null. */
function save_photo_if_any(array $file, int $id_for_name): ?string {
  if (empty($file['name']) || $file['error']!==UPLOAD_ERR_OK) return null;
  $dir = __DIR__.'/../uploads/profile';
  if (!is_dir($dir)) mkdir($dir, 0775, true);
  $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
  $name = $id_for_name.'_'.bin2hex(random_bytes(3)).'.'.$ext;
  $path = $dir.'/'.$name;
  if (!move_uploaded_file($file['tmp_name'], $path)) return null;
  return "/sems/public/uploads/profile/".$name;
}

// ---------- handle POST (create/update) ----------
if($_SERVER['REQUEST_METHOD']==='POST'){
  $fields=['emp_no','fname','lname','position','department','email','phone','address','monthly_rate','pay_group'];
  foreach($fields as $k){ $$k=trim($_POST[$k]??''); }

  if($isEdit){
    $st=$db->prepare("UPDATE employees SET emp_no=?,fname=?,lname=?,position=?,department=?,email=?,phone=?,address=?,monthly_rate=?,pay_group=? WHERE id=?");
    $st->execute([$emp_no,$fname,$lname,$position,$department,$email,$phone,$address,$monthly_rate,$pay_group,$id]);

    if(!empty($_FILES['photo']['name']) && ($url=save_photo_if_any($_FILES['photo'],$id))){
      $db->prepare("UPDATE employees SET photo_path=? WHERE id=?")->execute([$url,$id]);
    }
    header('Location: /sems/public/admin/employees.php'); exit;

  } else {
    // Auto-assign Emp # if blank (or if someone tampered with the form)
    if ($emp_no==='' || strtoupper($emp_no)==='AUTO') $emp_no = generate_emp_no($db);

    // Insert with retry on duplicate
    for($attempt=0; $attempt<5; $attempt++){
      try{
        $st=$db->prepare("INSERT INTO employees (emp_no,fname,lname,position,department,email,phone,address,monthly_rate,pay_group)
                          VALUES (?,?,?,?,?,?,?,?,?,?)");
        $st->execute([$emp_no,$fname,$lname,$position,$department,$email,$phone,$address,$monthly_rate,$pay_group]);
        $newId = (int)$db->lastInsertId();

        if(!empty($_FILES['photo']['name']) && ($url=save_photo_if_any($_FILES['photo'],$newId))){
          $db->prepare("UPDATE employees SET photo_path=? WHERE id=?")->execute([$url,$newId]);
        }
        header('Location: /sems/public/admin/employees.php'); exit;

      } catch(PDOException $ex){
        // 1062 = duplicate key (race condition). Generate again, retry.
        if(($ex->errorInfo[1]??0)==1062){
          $emp_no = generate_emp_no($db);
          continue;
        }
        throw $ex;
      }
    }
    die('Could not generate a unique Employee #. Please try again.');
  }
}

// ---------- render form ----------
$e=['emp_no'=>'','fname'=>'','lname'=>'','position'=>'','department'=>'','email'=>'','phone'=>'','address'=>'','monthly_rate'=>'','pay_group'=>'SEMI_MONTHLY','photo_path'=>null];
if($isEdit){
  $st=$db->prepare("SELECT * FROM employees WHERE id=?");
  $st->execute([$id]); $e=$st->fetch(PDO::FETCH_ASSOC);
}else{
  // Prefill a preview Emp # for new employee (read-only). Final value is still validated on save.
  $e['emp_no'] = generate_emp_no($db);
}
$positions=['Architect','Engineer','Foreman','Site Supervisor','HR Staff','Admin','Accountant'];
$groups=['WEEKLY','SEMI_MONTHLY','MONTHLY'];

$page_title=($isEdit?'Edit':'Add').' Employee';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>
<div class="max-w-3xl mx-auto p-4 md:p-6">
<form method="post" enctype="multipart/form-data" class="bg-white rounded-xl shadow-soft border p-6 space-y-4">
  <div class="text-lg font-semibold flex items-center gap-2">
    <span class="material-symbols-outlined">badge</span> <?= htmlspecialchars($page_title) ?>
  </div>

  <div class="grid sm:grid-cols-2 gap-4">
    <div>
      <label class="text-sm">Employee #</label>
      <input name="emp_no" value="<?= htmlspecialchars($e['emp_no']) ?>"
             class="mt-2 w-full rounded-lg border-slate-300"
             <?= $isEdit ? '' : 'readonly' ?> placeholder="AUTO">
      <?php if(!$isEdit): ?>
        <p class="text-xs text-slate-500 mt-1">Generated automatically. Guaranteed unique on save.</p>
      <?php endif; ?>
    </div>

    <div>
      <label class="text-sm">Monthly Rate</label>
      <input name="monthly_rate" type="number" step="0.01" value="<?=htmlspecialchars($e['monthly_rate'])?>" placeholder="30000" class="mt-2 w-full rounded-lg border-slate-300">
    </div>

    <div><label class="text-sm">First name</label><input name="fname" value="<?=htmlspecialchars($e['fname'])?>" placeholder="Juan" class="mt-2 w-full rounded-lg border-slate-300" required></div>
    <div><label class="text-sm">Last name</label><input name="lname" value="<?=htmlspecialchars($e['lname'])?>" placeholder="Dela Cruz" class="mt-2 w-full rounded-lg border-slate-300" required></div>

    <div>
      <label class="text-sm">Position</label>
      <select name="position" class="mt-2 w-full rounded-lg border-slate-300">
        <?php foreach($positions as $p): ?><option <?= $e['position']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Pay Group</label>
      <select name="pay_group" class="mt-2 w-full rounded-lg border-slate-300">
        <?php foreach($groups as $g): ?><option <?= $e['pay_group']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?>
      </select>
    </div>

    <div><label class="text-sm">Department</label><input name="department" value="<?=htmlspecialchars($e['department'])?>" placeholder="Operations" class="mt-2 w-full rounded-lg border-slate-300"></div>
    <div><label class="text-sm">Email</label><input name="email" type="email" value="<?=htmlspecialchars($e['email'])?>" placeholder="name@company.com" class="mt-2 w-full rounded-lg border-slate-300"></div>

    <div><label class="text-sm">Phone</label><input name="phone" value="<?=htmlspecialchars($e['phone'])?>" placeholder="+63 9xx xxx xxxx" class="mt-2 w-full rounded-lg border-slate-300"></div>
    <div class="sm:col-span-2"><label class="text-sm">Address</label><textarea name="address" placeholder="Street · City · Province" class="mt-2 w-full rounded-lg border-slate-300"><?=htmlspecialchars($e['address'])?></textarea></div>

    <div class="sm:col-span-2">
      <label class="text-sm">Profile Photo (for face comparison)</label>
      <div class="mt-2 flex items-start gap-4">
        <input type="file" name="photo" accept="image/*" class="w-full rounded-lg border-slate-300">
        <?php if(!empty($e['photo_path'])): ?>
          <img src="<?= htmlspecialchars($e['photo_path']) ?>" class="w-24 h-24 rounded-lg object-cover border" alt="Current photo">
        <?php endif; ?>
      </div>
      <p class="text-xs text-slate-500 mt-1">Upload a clear front-facing photo.</p>
    </div>
  </div>

  <div class="flex justify-end gap-2">
    <a href="/sems/public/admin/employees.php" class="px-4 h-10 grid place-items-center rounded-lg border">Cancel</a>
    <button class="px-4 h-10 rounded-lg bg-charcoal text-white">Save</button>
  </div>
</form>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
