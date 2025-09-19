<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);
require __DIR__.'/../app/partials/csrf.php';

$msg=''; $ok=false;

// Employees WITH an active card
$emps = $db->query("
  SELECT e.id, e.emp_no, CONCAT(e.fname,' ',e.lname) AS name, c.uid_hex
  FROM employees e
  JOIN rfid_cards c ON c.employee_id=e.id AND c.active=1
  WHERE e.active=1
  ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $emp_id=(int)($_POST['emp_id']??0);
  $new   = strtoupper(trim($_POST['new_uid']??''));

  if(!$emp_id || $new===''){ $msg='Select employee and enter new UID.'; }
  elseif(!preg_match('/^[0-9A-F]+$/',$new)){ $msg='UID must be HEX (0-9, A-F).'; }
  else{
    $db->beginTransaction();
    try{
      // make sure new UID not in use
      $du=$db->prepare("SELECT 1 FROM rfid_cards WHERE uid_hex=? AND active=1 LIMIT 1");
      $du->execute([$new]);
      if($du->fetch()){ throw new Exception('New UID is already registered.'); }

      // deactivate current
      $db->prepare("UPDATE rfid_cards SET active=0 WHERE employee_id=? AND active=1")->execute([$emp_id]);

      // insert new
      $db->prepare("INSERT INTO rfid_cards (employee_id, uid_hex, active, created_at) VALUES (?,?,1,NOW())")
         ->execute([$emp_id,$new]);

      $db->commit(); $ok=true; $msg='Card replaced successfully.';
      // refresh list
      $emps = $db->query("
        SELECT e.id, e.emp_no, CONCAT(e.fname,' ',e.lname) AS name, c.uid_hex
        FROM employees e
        JOIN rfid_cards c ON c.employee_id=e.id AND c.active=1
        WHERE e.active=1
        ORDER BY name
      ")->fetchAll(PDO::FETCH_ASSOC);
    }catch(Exception $ex){
      $db->rollBack(); $msg='Error: '.$ex->getMessage();
    }
  }
}

$page_title='Change RFID Card';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>

  <div class="max-w-3xl mx-auto p-4 md:p-6">
    <?php if($msg): ?>
      <div class="mb-4 p-3 rounded-lg border <?php echo $ok?'bg-emerald-50 text-emerald-700 border-emerald-200':'bg-rose-50 text-rose-700 border-rose-200'; ?>">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-soft border p-6">
      <div class="text-lg font-semibold flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined">swap_horiz</span> Change RFID Card
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Form -->
        <form method="post" class="space-y-4">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
          <div>
            <label class="text-sm">Employee (with active card)</label>
            <select id="emp" name="emp_id" required class="mt-2 w-full rounded-lg border-slate-300">
              <option value="">Select…</option>
              <?php foreach($emps as $e): ?>
                <option value="<?=$e['id']?>" data-cur="<?=htmlspecialchars($e['uid_hex'])?>">
                  <?=htmlspecialchars($e['emp_no'].' · '.$e['name'])?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="text-xs opacity-70 mt-1">Current UID: <span id="curUID" class="font-mono">—</span></div>
          </div>

          <div>
            <label class="text-sm">New UID (tap an unregistered card to auto-fill)</label>
            <input id="new_uid" name="new_uid" class="mt-2 w-full rounded-lg border-slate-300 uppercase" required>
          </div>

          <button class="px-4 h-10 rounded-lg bg-charcoal text-white">
            Replace Card
          </button>
        </form>

        <!-- Live last tap -->
        <div class="rounded-xl bg-slate-50 border p-4">
          <div class="text-sm opacity-80 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined">sensors</span> Scanner
          </div>
          <div id="tapBox" class="p-4 rounded-lg bg-white border">
            <div class="text-sm opacity-70">Waiting for card tap…</div>
          </div>
        </div>
      </div>
    </div>
  </div>


<script>
const DEVICE='DEV001';
const tapBox=document.getElementById('tapBox');
const newUID=document.getElementById('new_uid');
const empSel=document.getElementById('emp');
const curUID=document.getElementById('curUID');
empSel.addEventListener('change', ()=> curUID.textContent = empSel.selectedOptions[0]?.dataset.cur || '—');

let lastTs=0,lastId=0;
function pill(t,cls){ return `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs ${cls} border">${t}</span>`; }

async function poll(){
  try{
    const r=await fetch(`/sems/api/last_tap.php?device_id=${encodeURIComponent(DEVICE)}&since=${lastTs}&_=${Date.now()}`,{cache:'no-store'});
    if(r.ok){
      const j=await r.json();
      if(j && j.rfid_uid && ((j.ts && j.ts>lastTs) || (j.id && j.id!=lastId))){
        lastTs=j.ts||lastTs; lastId=j.id||lastId;
        const when=j.ts?new Date(j.ts*1000).toLocaleString():'—';

        if (j.registered==0) {
          newUID.value = j.rfid_uid.toUpperCase();
          tapBox.innerHTML = `
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm opacity-80">UID: ${j.rfid_uid}</div>
                <div class="text-xs opacity-70 mt-1">${when}</div>
              </div>
              ${pill('Unregistered','bg-amber-50 text-amber-800 border-amber-200')}
            </div>`;
        } else {
          tapBox.innerHTML = `
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm opacity-80">UID: ${j.rfid_uid}</div>
                <div class="text-lg font-semibold">${j.emp_no?`${j.emp_no} · `:''}${j.name||'—'}</div>
                <div class="text-xs opacity-70 mt-1">${when}</div>
              </div>
              ${pill('Registered','bg-emerald-50 text-emerald-800 border-emerald-200')}
            </div>`;
        }
      }
    }
  }catch(_){}
  setTimeout(poll,800);
}
poll();
</script>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
