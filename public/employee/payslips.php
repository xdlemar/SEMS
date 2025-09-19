<?php
require __DIR__.'/../partials/auth.php'; login_required();
$u  = current_user($db);
$ym = $_GET['ym'] ?? date('Y-m');

$ps=$db->prepare("
  SELECT p.id, p.total_hours, p.days_worked, p.hourly_rate, p.gross_pay, p.net_pay,
         pr.run_label, pr.period_start
  FROM payslips p
  JOIN payroll_runs pr ON pr.id=p.run_id
  WHERE p.employee_id=? AND DATE_FORMAT(pr.period_start,'%Y-%m')=?
  ORDER BY p.id DESC
");
$ps->execute([$u['employee_id'],$ym]);

$page_title='Payslips';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

$dt0  = strtotime($ym.'-01');
$prev = date('Y-m', strtotime('-1 month', $dt0));
$next = date('Y-m', strtotime('+1 month', $dt0));
?>

  <div class="max-w-6xl mx-auto p-4 md:p-6">

    <div class="bg-white rounded-xl shadow-soft border">
      <div class="p-4 flex items-center justify-between">
        <div class="font-semibold flex items-center gap-2"><span class="material-symbols-outlined">receipt_long</span> Payslips</div>
        <div class="flex items-center gap-2">
          <a class="px-3 h-10 rounded-lg border" href="?ym=<?= htmlspecialchars($prev) ?>">←</a>
          <form class="flex items-center gap-2">
            <input type="month" name="ym" value="<?= htmlspecialchars($ym) ?>" class="rounded-lg border-slate-300">
            <button class="px-3 h-10 rounded-lg border">Filter</button>
          </form>
          <a class="px-3 h-10 rounded-lg border" href="?ym=<?= htmlspecialchars($next) ?>">→</a>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 border-y">
            <tr>
              <th class="px-4 py-3">Run</th>
              <th class="px-4 py-3">Hours</th>
              <th class="px-4 py-3">Days</th>
              <th class="px-4 py-3">Hourly</th>
              <th class="px-4 py-3">Gross</th>
              <th class="px-4 py-3">Net</th>
              <th class="px-4 py-3 w-40">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($ps as $p): ?>
              <tr class="border-b hover:bg-slate-50">
                <td class="px-4 py-2"><?= htmlspecialchars($p['run_label']) ?></td>
                <td class="px-4 py-2"><?= number_format($p['total_hours'],2) ?></td>
                <td class="px-4 py-2"><?= (int)$p['days_worked'] ?></td>
                <td class="px-4 py-2">₱<?= number_format($p['hourly_rate'],2) ?></td>
                <td class="px-4 py-2">₱<?= number_format($p['gross_pay'],2) ?></td>
                <td class="px-4 py-2 font-medium">₱<?= number_format($p['net_pay'],2) ?></td>
                <td class="px-4 py-2">
                  <a class="px-3 h-9 grid place-items-center rounded-lg border bg-blue-500 text-white"
                     href="/sems/public/payslip.php?id=<?= (int)$p['id'] ?>" target="_blank">View / Print</a>
                </td>
              </tr>
            <?php endforeach; if($ps->rowCount()==0): ?>
              <tr><td colspan="7" class="px-4 py-6 text-slate-500">No payslips for this month.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
