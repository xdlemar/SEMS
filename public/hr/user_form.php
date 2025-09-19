<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);
require __DIR__.'/../app/partials/csrf.php';

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$msg = '';

// load existing (if edit)
$uRow = ['username'=>'', 'role'=>'EMPLOYEE', 'employee_id'=>null];
if ($isEdit) {
  $st = $db->prepare("SELECT id, username, role, employee_id FROM users WHERE id=?");
  $st->execute([$id]);
  $uRow = $st->fetch(PDO::FETCH_ASSOC) ?: $uRow;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $confirm  = (string)($_POST['confirm']  ?? '');
  $role     = $_POST['role'] ?? 'EMPLOYEE';
  $employee_id = ($_POST['employee_id'] ?? '') !== '' ? (int)$_POST['employee_id'] : null;

  // basic validation
  if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
    $msg = 'Username must be 3–30 chars: letters, numbers, dot, underscore, hyphen.';
  } elseif ($password !== '' && $password !== $confirm) {
    $msg = 'Passwords do not match.';
  } else {
    // 1) duplicate username?
    $du = $db->prepare("SELECT id FROM users WHERE username=? AND id<>?");
    $du->execute([$username, $id]);
    if ($du->fetchColumn()) {
      $msg = 'That username is already taken.';
    } else {
      // 2) enforce one account per (employee, role) if employee linked
      if ($employee_id) {
        $du2 = $db->prepare("SELECT id FROM users WHERE role=? AND employee_id=? AND id<>? LIMIT 1");
        $du2->execute([$role, $employee_id, $id]);
        if ($du2->fetchColumn()) {
          $msg = "This employee already has a $role account.";
        }
      }

      if ($msg === '') {
        try {
          if ($isEdit) {
            if ($password !== '') {
              $hash = password_hash($password, PASSWORD_DEFAULT);
              $st = $db->prepare("UPDATE users SET username=?, role=?, employee_id=?, password_hash=? WHERE id=?");
              $st->execute([$username, $role, $employee_id, $hash, $id]);
            } else {
              $st = $db->prepare("UPDATE users SET username=?, role=?, employee_id=? WHERE id=?");
              $st->execute([$username, $role, $employee_id, $id]);
            }
          } else {
            $hash = password_hash($password !== '' ? $password : 'changeme123', PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO users (username,password_hash,role,employee_id) VALUES (?,?,?,?)");
            $st->execute([$username,$hash,$role,$employee_id]);
          }
          header('Location: /sems/public/admin/users.php'); exit;
        } catch (PDOException $e) {
          // catch hard DB uniques if you later add them
          if ($e->getCode() === '23000') {
            $msg = 'Duplicate detected (username or employee-role already exists).';
          } else {
            $msg = 'Error: '.$e->getMessage();
          }
        }
      }
    }
  }

  // repopulate on validation error
  $uRow = ['username'=>$username, 'role'=>$role, 'employee_id'=>$employee_id];
}

$page_title = ($isEdit?'Edit':'Add').' User';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>
<div class="max-w-3xl mx-auto p-4 md:p-6">
  <?php if ($msg): ?>
    <div class="mb-4 p-3 rounded-lg border bg-rose-50 text-rose-700"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form id="userForm" method="post" class="bg-white rounded-xl shadow-soft border p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">manage_accounts</span> <?= htmlspecialchars($page_title) ?>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium">Username</label>
        <div class="mt-2 relative">
          <input
            name="username"
            value="<?= htmlspecialchars($uRow['username']) ?>"
            placeholder="e.g. j.doe"
            autocomplete="off"
            class="w-full rounded-lg border border-slate-300 bg-white shadow-sm px-3 h-10 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
            required
          >
          <div class="absolute right-2 top-2.5 text-slate-400 material-symbols-outlined">person</div>
        </div>
        <p class="text-xs text-slate-500 mt-1">3–30 characters (letters, numbers, dot, underscore, hyphen).</p>
      </div>

      <div>
        <label class="block text-sm font-medium">Password <?= $isEdit ? '<span class="text-slate-500">(leave blank to keep)</span>' : '' ?></label>
        <input
          name="password"
          type="password"
          placeholder="<?= $isEdit ? '••••••••' : 'Set initial password' ?>"
          class="mt-2 w-full rounded-lg border border-slate-300 bg-white shadow-sm px-3 h-10 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
        <input
          name="confirm"
          type="password"
          placeholder="Confirm password"
          class="mt-2 w-full rounded-lg border border-slate-300 bg-white shadow-sm px-3 h-10 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
      </div>

      <div>
        <label class="block text-sm font-medium">Role</label>
        <select
          name="role" id="roleSel"
          class="mt-2 w-full rounded-lg border border-slate-300 bg-white shadow-sm px-3 h-10 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
          <?php foreach (['EMPLOYEE'] as $r): ?>
            <option <?= $uRow['role']===$r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">Each employee may have at most one account per role.</p>
      </div>

      <div>
        <label class="block text-sm font-medium">Link to Employee</label>
        <select
          name="employee_id" id="empSel"
          class="mt-2 w-full rounded-lg border border-slate-300 bg-white shadow-sm px-3 h-10 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
          <option value="">— none —</option>
         
        </select>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <a href="/sems/public/hr/users.php" class="px-4 h-10 rounded-lg border grid place-items-center">Cancel</a>
      <button class="px-4 h-10 rounded-lg bg-charcoal text-white">Save</button>
    </div>
  </form>
</div>

<script>
// client-side confirm check
document.getElementById('userForm').addEventListener('submit', (e)=>{
  const p = e.target.password.value.trim();
  const c = e.target.confirm.value.trim();
  if (p !== '' && p !== c) {
    e.preventDefault();
    alert('Passwords do not match.');
  }
});

// dynamic employee list filtered by role (no employee who already has that role)
async function loadEmployeesForRole(role){
  const empSel = document.getElementById('empSel');
  const currentEmpId = "<?= (int)($uRow['employee_id'] ?? 0) ?>";
  empSel.innerHTML = '<option value="">Loading…</option>';

  try{
    const url = `/sems/public/admin/eligible_employees.php?role=${encodeURIComponent(role)}&current_emp_id=${currentEmpId}`;
    const r = await fetch(url, {credentials:'same-origin'});
    const list = r.ok ? await r.json() : [];
    empSel.innerHTML = '<option value="">— none —</option>';
    for (const it of list){
      const opt = document.createElement('option');
      opt.value = it.id; opt.textContent = it.label;
      if (String(it.id) === currentEmpId) opt.selected = true;
      empSel.appendChild(opt);
    }
  }catch(_){
    empSel.innerHTML = '<option value="">— none —</option>';
  }
}
const roleSel = document.getElementById('roleSel');
roleSel.addEventListener('change', ()=> loadEmployeesForRole(roleSel.value));
loadEmployeesForRole(roleSel.value);
</script>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
