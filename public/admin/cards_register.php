<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$page_title='Register RFID Card'; require __DIR__.'/../partials/layout_head.php'; require __DIR__.'/../partials/layout_nav.php';
$employee_id=(int)($_GET['employee_id']??0);
$emps=$db->query("SELECT id, CONCAT(emp_no,' · ',fname,' ',lname) AS label FROM employees WHERE active=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-xl mx-auto p-4 md:p-6">
  <div class="bg-white rounded-xl shadow-soft border p-6 space-y-4">
    <div class="text-lg font-semibold flex items-center gap-2"><span class="material-symbols-outlined">credit_card</span> Register RFID Card</div>
    <div><label class="text-sm">Employee</label>
      <select id="employee" class="mt-2 w-full rounded-lg border-slate-300">
        <option value="">Select…</option>
        <?php foreach($emps as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $employee_id==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['label']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div><label class="text-sm">UID (hex)</label><input id="uid" class="mt-2 w-full rounded-lg border-slate-300" placeholder="04A1B2C3D4"></div>
    <div class="flex justify-end"><button id="bind" class="px-4 h-10 rounded-lg bg-emerald-600 text-white">Bind</button></div>
    <div id="msg" class="text-sm"></div>
  </div>
</div>
<script>
document.getElementById('bind').onclick=async()=>{
  const employee_id=document.getElementById('employee').value;
  const uid=document.getElementById('uid').value.trim().toUpperCase();
  if(!employee_id||!uid){ alert('Select employee and enter UID'); return; }
  const r=await fetch('/sems/api/cards_register.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({employee_id,uid_hex:uid})});
  const j=await r.json(); document.getElementById('msg').textContent = j.status==='ok'?'Card registered!':'Error: '+(j.message||'');
};
</script>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
<script>
const DEVICE = 'DEV001'; 
const uidEl  = document.getElementById('uid');
const empEl  = document.getElementById('employee');
const msgEl  = document.getElementById('msg');

let lastTs = 0;   // cursor so we only react to new taps
let lastId = 0;

function flash(el, cls='ring-2 ring-emerald-400') {
  el.classList.add(...cls.split(' '));
  setTimeout(()=>el.classList.remove(...cls.split(' ')), 600);
}

async function poll() {
  try {
    const url = `/sems/api/last_tap.php?device_id=${encodeURIComponent(DEVICE)}&since=${lastTs}&_=${Date.now()}`;
    const r = await fetch(url, {cache:'no-store', credentials:'same-origin'});
    if (!r.ok) throw new Error(r.statusText);
    const j = await r.json();

  
    if (j && j.rfid_uid && ((j.ts && j.ts > lastTs) || (j.id && j.id != lastId))) {
      lastTs = j.ts || lastTs;
      lastId = j.id || lastId;

      uidEl.value = j.rfid_uid.toUpperCase();
      flash(uidEl);

      if (j.registered) {
        msgEl.textContent = 'This UID is already registered.';
        msgEl.className = 'text-sm text-rose-600';
      } else {
        msgEl.textContent = '';
        msgEl.className = 'text-sm';
      }
    }
  } catch (e) {
   
  } finally {
    setTimeout(poll, 500); 
  }
}
poll();

document.getElementById('bind').onclick = async () => {
  const employee_id = empEl.value;
  const uid = uidEl.value.trim().toUpperCase();
  if (!employee_id || !uid) { alert('Select employee and tap a card'); return; }

  const r = await fetch('/sems/api/cards_register.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({employee_id, uid_hex: uid})
  });
  const j = await r.json();
  if (j.status === 'ok') {
    msgEl.textContent = 'Card registered!';
    msgEl.className = 'text-sm text-emerald-600';
    flash(msgEl, 'ring-2 ring-emerald-300');
  } else {
    msgEl.textContent = j.message || 'Error';
    msgEl.className = 'text-sm text-rose-600';
  }
};
</script>

