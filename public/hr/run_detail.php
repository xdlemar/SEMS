<?php
require __DIR__.'/../partials/auth.php';
require_role(['HR','ADMIN']);
$run_id = (int)($_GET['run_id'] ?? 0);
if ($run_id<=0) { http_response_code(404); exit('Run not found'); }

$page_title = 'Payroll Run';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

$run = $db->prepare("SELECT id, run_label, pay_group, period_start, period_end FROM payroll_runs WHERE id=?");
$run->execute([$run_id]);
$run = $run->fetch(PDO::FETCH_ASSOC);
if (!$run) { http_response_code(404); exit('Run not found'); }

$rows = $db->prepare("
  SELECT p.employee_id, p.total_hours, p.days_worked, p.hourly_rate, p.monthly_rate, p.gross_pay, p.deductions, p.net_pay,
         e.emp_no, CONCAT(e.fname,' ',e.lname) AS name, e.position
  FROM payslips p
  JOIN employees e ON e.id=p.employee_id
  WHERE p.run_id=?
  ORDER BY e.lname, e.fname
");
$rows->execute([$run_id]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-6xl mx-auto p-4 md:p-6">
  <div class="flex items-center justify-between mb-4">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">receipt_long</span>
      <?= htmlspecialchars($run['period_start'].' ~ '.$run['period_end'].' ('.$run['pay_group'].')'); ?>
    </div>
    <div class="flex gap-2">
      <a href="/sems/public/hr/payroll_run.php" class="px-3 h-10 grid place-items-center rounded-lg border">Back</a>
      <a class="px-3 h-10 grid place-items-center rounded-lg bg-charcoal text-white" href="/sems/public/hr/run_print.php?run_id=<?= (int)$run_id ?>"
   href="/sems/public/hr/run_print.php?run_id=<?= (int)$run_id ?>"
   target="_blank">Print Summary</a>

    </div>
  </div>

  <div class="bg-white rounded-xl shadow-soft border overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-y">
        <tr>
          <th class="px-4 py-3">Emp #</th>
          <th class="px-4 py-3">Name</th>
          <th class="px-4 py-3">Position</th>
          <th class="px-4 py-3">Hours</th>
          <th class="px-4 py-3">Hourly</th>
          <th class="px-4 py-3">Gross</th>
          <th class="px-4 py-3">Net</th>
          <th class="px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr class="border-b hover:bg-slate-50">
            <td class="px-4 py-2"><?= htmlspecialchars($r['emp_no']); ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($r['name']); ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($r['position']); ?></td>
            <td class="px-4 py-2"><?= number_format($r['total_hours'],2); ?></td>
            <td class="px-4 py-2">₱<?= number_format($r['hourly_rate'],2); ?></td>
            <td class="px-4 py-2">₱<?= number_format($r['gross_pay'],2); ?></td>
            <td class="px-4 py-2 font-medium">₱<?= number_format($r['net_pay'],2); ?></td>
            <td class="px-4 py-2">
              <a class="px-3 h-9 grid place-items-center rounded-lg border bg-blue-500 text-white"
                 href="/sems/public/hr/payslip_view.php?run_id=<?= $run_id; ?>&emp_id=<?= (int)$r['employee_id']; ?>" target="_blank">
                View / Print
              </a>
            </td>
          </tr>
        <?php endforeach; if(empty($rows)): ?>
          <tr><td colspan="8" class="px-4 py-6 text-slate-500">No payslips found for this run.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
@media print{
  header, nav{ display:none !important; }
  .max-w-6xl{ max-width:none !important; }
  .bg-white, .border, .shadow-soft{ box-shadow:none !important; }
  a[href]:after{ content:""; }
  button{ display:none !important; }
  table{ width:100%; font-size:12px; }
}
</style>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
