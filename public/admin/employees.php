<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$page_title='Employees';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

$q=$db->query("
  SELECT e.id, e.emp_no, CONCAT(e.fname,' ',e.lname) AS name,
         e.position, e.department, e.photo_path,
         (SELECT COUNT(*) FROM rfid_cards c WHERE c.employee_id=e.id AND c.active=1) AS card_cnt
  FROM employees e
  WHERE e.active=1
  ORDER BY e.id DESC
");
$rows=$q->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-6xl mx-auto p-4 md:p-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <h1 class="text-xl font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">group</span> Employees
    </h1>
    <div class="flex items-center gap-2">
      <input id="q" placeholder="Search name / emp # / position"
             class="rounded-lg border-slate-300 w-64 px-3 h-10">
      <a href="/sems/public/admin/employee_form.php"
         class="px-3 h-10 rounded-lg bg-charcoal text-white grid place-items-center">Add Employee</a>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-soft border overflow-x-auto">
    <table id="tbl" class="min-w-full text-sm">
      <thead class="bg-slate-50 border-y">
        <tr>
          <th class="px-4 py-3">Employee</th>
          <th class="px-4 py-3">Position</th>
          <th class="px-4 py-3">Dept</th>
          <th class="px-4 py-3">Card</th>
          <th class="px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr class="border-b hover:bg-slate-50">
          <td class="px-4 py-2">
            <div class="flex items-center gap-3">
              <?php if(!empty($r['photo_path'])): ?>
                <img src="<?= htmlspecialchars($r['photo_path']) ?>" class="w-9 h-9 rounded-full object-cover border">
              <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-slate-200 grid place-items-center border">
                  <span class="material-symbols-outlined text-slate-500">person</span>
                </div>
              <?php endif; ?>
              <div>
                <div class="font-medium leading-5"><?= htmlspecialchars($r['name']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['emp_no']) ?></div>
              </div>
            </div>
          </td>
          <td class="px-4 py-2"><?= htmlspecialchars($r['position']) ?></td>
          <td class="px-4 py-2"><?= htmlspecialchars($r['department']) ?></td>
          <td class="px-4 py-2">
            <?php if((int)$r['card_cnt']>0): ?>
              <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="material-symbols-outlined text-[16px]">check</span> bound
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-slate-50 text-slate-700 border">none</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-2">
            <div class="flex flex-wrap gap-2">
              <a class="px-3 py-1.5 rounded-lg border" title="Edit"
                 href="/sems/public/admin/employee_form.php?id=<?= (int)$r['id'] ?>">
                 <span class="material-symbols-outlined text-[18px]">edit</span></a>

              <a class="px-3 py-1.5 rounded-lg border" title="Register Card"
                 href="/sems/public/admin/cards_register.php?employee_id=<?= (int)$r['id'] ?>">
                 <span class="material-symbols-outlined text-[18px]">credit_card</span></a>

              <a class="px-3 py-1.5 rounded-lg border" title="Create Login"
                 href="/sems/public/admin/user_form.php?employee_id=<?= (int)$r['id'] ?>">
                 <span class="material-symbols-outlined text-[18px]">manage_accounts</span></a>

              <form method="post" action="/sems/public/admin/employee_delete.php"
                    onsubmit="return confirm('Delete this employee?')" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="px-3 py-1.5 rounded-lg border text-rose-600" title="Delete">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// simple client-side search
const q=document.getElementById('q'), rows=[...document.querySelectorAll('#tbl tbody tr')];
q?.addEventListener('input',()=>{
  const s=q.value.toLowerCase();
  rows.forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(s) ? '' : 'none';
  });
});
</script>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
