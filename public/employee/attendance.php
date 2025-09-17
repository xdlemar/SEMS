<?php
require __DIR__.'/../partials/auth.php'; login_required();
$me = current_user($db);

// month filter (YYYY-MM)
$ym  = $_GET['ym'] ?? date('Y-m');
$dt0 = strtotime($ym.'-01');
$from = date('Y-m-01', $dt0);
$to   = date('Y-m-t',  $dt0);

// pull logs in period
$st=$db->prepare("SELECT type, tap_time
                  FROM attendance_logs
                  WHERE employee_id=? AND DATE(tap_time) BETWEEN ? AND ?
                  ORDER BY tap_time ASC");
$st->execute([$me['employee_id'],$from,$to]);
$logs=$st->fetchAll(PDO::FETCH_ASSOC);

// group by date
$days=[]; foreach($logs as $r){ $d=substr($r['tap_time'],0,10); $days[$d][]=$r; }

function day_hours($rows){
  $sec=0; $in=null;
  foreach($rows as $r){
    if($r['type']==='IN'){ $in=strtotime($r['tap_time']); }
    if($r['type']==='OUT' && $in){ $sec += (strtotime($r['tap_time'])-$in); $in=null; }
  }
  return round($sec/3600,2);
}
$totalHours = 0; foreach($days as $rows){ $totalHours += day_hours($rows); }

$page_title='My Attendance';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

// prev/next month links
$prev = date('Y-m', strtotime('-1 month', $dt0));
$next = date('Y-m', strtotime('+1 month', $dt0));
?>

  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-4">

    <div class="bg-white rounded-xl shadow-soft border">
      <div class="p-4 flex items-center justify-between">
        <div class="font-semibold flex items-center gap-2">
          <span class="material-symbols-outlined">history</span> My Attendance
        </div>
        <div class="flex items-center gap-2">
          <a class="px-3 h-10 rounded-lg border" href="?ym=<?= htmlspecialchars($prev) ?>">←</a>
          <form class="flex items-center gap-2">
            <input type="month" name="ym" value="<?= htmlspecialchars($ym) ?>" class="rounded-lg border-slate-300">
            <button class="px-3 h-10 rounded-lg border">Go</button>
          </form>
          <a class="px-3 h-10 rounded-lg border" href="?ym=<?= htmlspecialchars($next) ?>">→</a>
        </div>
      </div>

      <div class="px-4 pb-4">
        <div class="grid sm:grid-cols-3 gap-3">
          <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Period</div>
            <div class="font-medium"><?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
          </div>
          <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Total Days</div>
            <div class="font-medium"><?= count($days) ?></div>
          </div>
          <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Total Hours</div>
            <div class="font-medium"><?= number_format($totalHours,2) ?></div>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 border-y">
            <tr><th class="px-4 py-3 w-40">Date</th><th class="px-4 py-3">IN / OUT</th><th class="px-4 py-3 w-28 text-right">Hours</th></tr>
          </thead>
          <tbody>
          <?php foreach(array_keys($days) as $d): $rows=$days[$d]; $h=day_hours($rows); ?>
            <tr class="border-b align-top">
              <td class="px-4 py-3 font-medium"><?= htmlspecialchars($d) ?></td>
              <td class="px-4 py-3">
                <?php foreach($rows as $r): ?>
                  <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 border text-xs mr-1 mb-1">
                    <?= $r['type'] ?> · <?= date('H:i', strtotime($r['tap_time'])) ?>
                  </span>
                <?php endforeach; ?>
              </td>
              <td class="px-4 py-3 text-right"><?= number_format($h,2) ?></td>
            </tr>
          <?php endforeach; if(empty($days)): ?>
            <tr><td colspan="3" class="px-4 py-6 text-slate-500">No records for this month.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
