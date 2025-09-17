<?php
require __DIR__.'/../partials/auth.php'; login_required();
$u = current_user($db);
if (!$u || !$u['employee_id']) { http_response_code(403); exit('Forbidden'); }

$emp = $db->prepare("SELECT * FROM employees WHERE id=?");
$emp->execute([$u['employee_id']]);
$e = $emp->fetch(PDO::FETCH_ASSOC);

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='save_profile') {
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $address = $_POST['address'] ?? '';
  $photo_path = $e['photo_path'];

  if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error']===UPLOAD_ERR_OK){
    $dir = __DIR__.'/../uploads/profile';
    if (!is_dir($dir)) mkdir($dir,0777,true);
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $fn  = 'emp_'.$u['employee_id'].'_'.time().'.'.$ext;
    $dest= $dir.'/'.$fn;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)){
      $photo_path = '/sems/public/uploads/profile/'.$fn;
    }
  }
  $st=$db->prepare("UPDATE employees SET email=?, phone=?, address=?, photo_path=? WHERE id=?");
  $st->execute([$email,$phone,$address,$photo_path,$u['employee_id']]);
  $msg = 'Profile updated.';
  $emp->execute([$u['employee_id']]); $e=$emp->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='change_pass') {
  $cur = $_POST['current'] ?? '';
  $new = $_POST['new'] ?? '';
  $re  = $_POST['re'] ?? '';
  if ($new==='' || $new!==$re){ $msg='Passwords do not match.'; }
  else{
    $row=$db->prepare("SELECT password_hash FROM users WHERE id=?"); $row->execute([$u['id']]);
    $hash=$row->fetchColumn();
    if (!password_verify($cur, $hash)) { $msg='Current password incorrect.'; }
    else {
      $db->prepare("UPDATE users SET password_hash=? WHERE id=?")
         ->execute([password_hash($new, PASSWORD_DEFAULT),$u['id']]);
      $msg='Password changed.';
    }
  }
}

$page_title='My Profile';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>

<div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

  <?php if($msg): ?>
    <div class="p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>


  <form id="profileForm" method="post" enctype="multipart/form-data" class="bg-white rounded-xl shadow-soft border p-6 space-y-5">
    <input type="hidden" name="act" value="save_profile">
    <div class="flex items-center justify-between">
      <div class="text-lg font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined">badge</span> Profile
      </div>
      <div class="flex gap-2">
        <button type="button" id="editBtn" class="px-3 h-10 rounded-lg border">Edit</button>
        <div id="editActions" class="hidden gap-2">
          <button class="px-3 h-10 rounded-lg bg-charcoal text-white">Save</button>
          <button type="button" id="cancelBtn" class="px-3 h-10 rounded-lg border">Cancel</button>
        </div>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <div class="text-sm text-slate-500">Name</div>
        <div class="font-medium"><?= htmlspecialchars($e['fname'].' '.$e['lname']) ?></div>
        <div class="text-sm opacity-70"><?= htmlspecialchars($e['emp_no']) ?></div>
      </div>

      <div class="flex items-center gap-4">
        <img src="<?= htmlspecialchars($e['photo_path'] ?: '/sems/public/assets/user.svg') ?>"
             class="w-20 h-20 rounded-lg object-cover border">
        <div class="w-full">
          <label class="text-sm">Photo</label>
          <input data-editable type="file" name="photo" accept="image/*" class="mt-2 block text-sm" disabled>
          <div class="text-xs text-slate-500 mt-1">JPG/PNG, ≤ 5MB</div>
        </div>
      </div>

      <div>
        <label class="text-sm">Email</label>
        <div class="input-group mt-2">
          <span class="input-icon material-symbols-outlined">mail</span>
          <input data-editable name="email" value="<?= htmlspecialchars($e['email']) ?>"
                 placeholder="name@company.com" class="form-input" disabled>
        </div>
      </div>

      <div>
        <label class="text-sm">Phone</label>
        <div class="input-group mt-2">
          <span class="input-icon material-symbols-outlined">call</span>
          <input data-editable name="phone" value="<?= htmlspecialchars($e['phone']) ?>"
                 placeholder="09XXXXXXXXX" class="form-input" disabled>
        </div>
      </div>

      <div class="sm:col-span-2">
        <label class="text-sm">Address</label>
        <textarea data-editable name="address" rows="3" placeholder="House #, Street, City / Province, ZIP"
                  class="form-input mt-2 min-h-[44px]" disabled><?= htmlspecialchars($e['address']) ?></textarea>
      </div>
    </div>
  </form>

  <!-- Change password -->
  <form method="post" class="bg-white rounded-xl shadow-soft border p-6 space-y-5">
    <input type="hidden" name="act" value="change_pass">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">lock</span> Change Password
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
     
      <div>
        <label class="text-sm">Current</label>
        <div class="input-group mt-2">
          <span class="input-icon material-symbols-outlined">lock</span>
          <input id="passCur" type="password" name="current" placeholder="••••••••"
                 class="form-input pr-10" required>
          <button class="pass-toggle" type="button" data-target="passCur">
            <span class="material-symbols-outlined">visibility</span>
          </button>
        </div>
      </div>

     
      <div>
        <label class="text-sm">New</label>
        <div class="input-group mt-2">
          <span class="input-icon material-symbols-outlined">key</span>
          <input id="passNew" type="password" name="new" placeholder="At least 8 characters"
                 class="form-input pr-10" required>
          <button class="pass-toggle" type="button" data-target="passNew">
            <span class="material-symbols-outlined">visibility</span>
          </button>
        </div>
      
        <div class="mt-2 flex items-center gap-2">
          <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
            <div id="meterFill" class="h-full w-0 bg-rose-500 transition-all"></div>
          </div>
          <span id="meterLabel" class="text-xs text-slate-500 whitespace-nowrap">Weak</span>
        </div>
        <div class="text-xs text-slate-500 mt-1">Use upper & lower case, number, and symbol.</div>
      </div>

      
      <div>
        <label class="text-sm">Repeat</label>
        <div class="input-group mt-2">
          <span class="input-icon material-symbols-outlined">sync_lock</span>
          <input id="passRe" type="password" name="re" placeholder="Re-enter new password"
                 class="form-input pr-10" required>
          <button class="pass-toggle" type="button" data-target="passRe">
            <span class="material-symbols-outlined">visibility</span>
          </button>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button class="px-4 h-10 rounded-lg bg-charcoal text-white">Update Password</button>
    </div>
  </form>

</div>

<style>

.form-input{
  @apply w-full rounded-xl border border-slate-300 bg-white placeholder-slate-400
         h-11 px-3 pl-10 text-[15px] outline-none;
}
.form-input:focus{ @apply ring-2 ring-charcoal/20 border-charcoal/50; }
.form-input[disabled]{ @apply bg-slate-50 text-slate-700 cursor-default; }


.input-group{ @apply relative; }
.input-icon{
  @apply absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none;
}
.pass-toggle{
  @apply absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center
         rounded-md text-slate-500 hover:bg-slate-100;
}


.min-h-\[44px\]{ min-height:44px; }
</style>

<script>

const editBtn     = document.getElementById('editBtn');
const cancelBtn   = document.getElementById('cancelBtn');
const editActions = document.getElementById('editActions');
const editable    = [...document.querySelectorAll('[data-editable]')];

function setEditMode(on){
  editable.forEach(el => el.disabled = !on);
  editBtn?.classList.toggle('hidden', on);
  editActions?.classList.toggle('hidden', !on);
}
editBtn?.addEventListener('click', ()=> setEditMode(true));
cancelBtn?.addEventListener('click', ()=> window.location.reload());


document.querySelectorAll('.pass-toggle').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const id = btn.dataset.target;
    const input = document.getElementById(id);
    if(!input) return;
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    btn.querySelector('.material-symbols-outlined').textContent = visible ? 'visibility' : 'visibility_off';
  });
});


const passNew    = document.getElementById('passNew');
const meterFill  = document.getElementById('meterFill');
const meterLabel = document.getElementById('meterLabel');

function strengthScore(s){
  let score = 0;
  if (s.length >= 8) score++;
  if (/[a-z]/.test(s) && /[A-Z]/.test(s)) score++;
  if (/\d/.test(s)) score++;
  if (/[^A-Za-z0-9]/.test(s)) score++;
  return score;
}
function updateMeter(){
  const s = strengthScore(passNew.value);
  const w = [0,25,50,75,100][s];
  const color = ['rose','rose','amber','emerald','emerald'][s];
  meterFill.style.width = w+'%';
  meterFill.className = `h-full w-[${w}%] bg-${color}-500 transition-all`;
  meterLabel.textContent = ['Very weak','Weak','Fair','Strong','Very strong'][s];
}
passNew?.addEventListener('input', updateMeter);
updateMeter();
</script>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
