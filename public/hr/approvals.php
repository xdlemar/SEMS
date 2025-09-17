<?php
require __DIR__.'/../partials/auth.php'; require_role(['HR','ADMIN']);
$page_title='Attendance Approvals'; require __DIR__.'/../partials/layout_head.php'; require __DIR__.'/../partials/layout_nav.php';
$q=$db->query("SELECT a.id,a.employee_id,a.tap_time,a.verification_status,e.emp_no,CONCAT(e.fname,' ',e.lname) AS name,e.position,e.department
               FROM attendance_logs a JOIN employees e ON e.id=a.employee_id
               WHERE a.type='IN' AND a.approved_at IS NULL ORDER BY a.tap_time DESC LIMIT 200");
$rows=$q->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-6xl mx-auto p-4 md:p-6">
  <div class="bg-white rounded-xl shadow-soft border">
    <div class="p-4 font-semibold flex items-center gap-2"><span class="material-symbols-outlined">assignment_turned_in</span> Pending “IN” Approvals</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-y"><tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Position</th><th class="px-4 py-3">Time In</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
        <tbody><?php foreach($rows as $r): ?>
          <tr class="border-b hover:bg-slate-50">
            <td class="px-4 py-3"><?= htmlspecialchars($r['emp_no'].' · '.$r['name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($r['position']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($r['tap_time']) ?></td>
            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-amber-50 text-amber-700 border border-amber-200"><?= htmlspecialchars($r['verification_status']) ?></span></td>
            <td class="px-4 py-3">
              <button data-id="<?= (int)$r['id'] ?>" class="open-detail px-3 py-1.5 rounded-lg border">Review</button>
              <form method="post" action="/sems/public/hr/approve_in.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button name="act" value="approve" class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white">Approve</button>
              </form>
              <form method="post" action="/sems/public/hr/approve_in.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button name="act" value="reject" class="px-3 py-1.5 rounded-lg bg-rose-500 text-white">Reject</button>
              </form>
            </td>
          </tr><?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<aside id="drawer" class="fixed inset-y-0 right-0 w-full sm:w-[520px] bg-white shadow-2xl border-l transform translate-x-full transition z-50">
  <div class="h-14 px-4 border-b flex items-center justify-between">
    <div class="font-semibold flex items-center gap-2"><span class="material-symbols-outlined">rule</span> Compare & Verify</div>
    <button id="drawerClose" class="w-9 h-9 grid place-items-center rounded-lg border"><span class="material-symbols-outlined">close</span></button>
  </div>
  <div id="drawerBody" class="p-4 space-y-4"></div>
</aside>
<script>
const drawer=document.getElementById('drawer'), body=document.getElementById('drawerBody');
document.querySelectorAll('.open-detail').forEach(b=>b.addEventListener('click',async()=>{
  const id=b.dataset.id; const r=await fetch('/sems/api/employees_lookup.php?attendance_id='+id); const d=await r.json();
  body.innerHTML=`<div class="bg-slate-50 border rounded-xl p-3">
    <div class="text-sm text-slate-500 mb-1">Employee</div>
    <div class="font-medium">${d.emp_no} · ${d.name}</div>
    <div class="text-sm text-slate-600">${d.position} — ${d.department}</div></div>
    <div class="grid grid-cols-2 gap-3">
      <div class="bg-white border rounded-xl p-3"><div class="text-sm text-slate-500 mb-2">Profile Image</div>
        ${d.profile_photo?`<img src="${d.profile_photo}" class="w-full aspect-square object-cover rounded-lg border">`:`<div class="aspect-square grid place-items-center border rounded-lg text-slate-400">No profile photo</div>`}
      </div>
      <div class="bg-white border rounded-xl p-3"><div class="text-sm text-slate-500 mb-2">Captured at Time-In</div>
        ${d.last_capture?`<img src="${d.last_capture}" class="w-full aspect-square object-cover rounded-lg border">`:`<div class="aspect-square grid place-items-center border rounded-lg text-slate-400">No capture</div>`}
      </div></div>
    <div class="text-xs text-slate-500">Tapped @ ${d.tap_time}</div>`;
  drawer.classList.remove('translate-x-full');
}));
document.getElementById('drawerClose').onclick=()=>drawer.classList.add('translate-x-full');
</script>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
