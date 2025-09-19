<?php
require __DIR__.'/../partials/auth.php'; require_role(['HR','ADMIN']);
$u=current_user($db);

function sum_hours_for_employee(PDO $db, $emp_id, $start, $end){
  $q=$db->prepare("SELECT type,tap_time,approved_at
                   FROM attendance_logs
                   WHERE employee_id=? AND tap_time BETWEEN ? AND ?
                   ORDER BY tap_time ASC");
  $q->execute([$emp_id,$start.' 00:00:00',$end.' 23:59:59']);
  $rows=$q->fetchAll(PDO::FETCH_ASSOC);
  $sec=0; $in=null;
  foreach($rows as $r){
    if($r['type']==='IN' && $r['approved_at']) $in=strtotime($r['tap_time']);
    elseif($r['type']==='OUT' && $in){ $sec += (strtotime($r['tap_time'])-$in); $in=null; }
  }
  return max(0, round($sec/3600,2));
}

$msg=''; $ok=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
  $pg    = trim($_POST['pay_group'] ?? 'SEMI_MONTHLY');
  $start = trim($_POST['start'] ?? date('Y-m-01'));
  $end   = trim($_POST['end']   ?? date('Y-m-t'));

  // Basic sanity
  if (!$pg || !$start || !$end) {
    $msg = 'Please select pay group and valid dates.';
  } elseif ($start > $end) {
    $msg = 'Start date cannot be after end date.';
  } else {
    $st = $db->prepare("SELECT id, run_label FROM payroll_runs
                        WHERE pay_group=? AND period_start=? AND period_end=? LIMIT 1");
    $st->execute([$pg,$start,$end]);
    if ($dupe = $st->fetch(PDO::FETCH_ASSOC)) {
      $msg = "A $pg payroll run for $start → $end already exists (Run #{$dupe['id']}).";
    } else {
      $ov = $db->prepare("SELECT id, run_label, period_start, period_end
                          FROM payroll_runs
                          WHERE pay_group=?
                            AND NOT (period_end < ? OR period_start > ?)
                          ORDER BY id DESC LIMIT 1");
      $ov->execute([$pg,$start,$end]);
      if ($o = $ov->fetch(PDO::FETCH_ASSOC)) {
        $msg = "Selected period overlaps with existing run “{$o['run_label']}”. Choose a different date range.";
      } else {
        $db->beginTransaction();
        try{
          $label="$start ~ $end ($pg)";
          $db->prepare("INSERT INTO payroll_runs (run_label,pay_group,period_start,period_end,created_by)
                        VALUES (?,?,?,?,?)")
             ->execute([$label,$pg,$start,$end,$u['id']]);
          $run_id = $db->lastInsertId();

          $emps=$db->prepare("SELECT id,monthly_rate,hourly_rate
                              FROM employees WHERE active=1 AND pay_group=?");
          $emps->execute([$pg]);

          $ins=$db->prepare("INSERT INTO payslips
            (run_id,employee_id,total_hours,days_worked,hourly_rate,monthly_rate,gross_pay,net_pay)
            VALUES (?,?,?,?,?,?,?,?)");

          while($e=$emps->fetch(PDO::FETCH_ASSOC)){
            $hours = sum_hours_for_employee($db, $e['id'], $start, $end);
            if($hours<=0) continue;

            $dw=$db->prepare("SELECT COUNT(*) FROM (
                                SELECT DISTINCT DATE(tap_time) d
                                  FROM attendance_logs
                                 WHERE employee_id=? AND type='OUT'
                                   AND tap_time BETWEEN ? AND ?
                              ) x");
            $dw->execute([$e['id'],$start.' 00:00:00',$end.' 23:59:59']);
            $days_worked=(int)$dw->fetchColumn();

            $hourly = $e['hourly_rate']>0 ? $e['hourly_rate']
                    : (($e['monthly_rate']>0)? ($e['monthly_rate']/(22*8)) : 0);
            $gross  = round($hourly*$hours,2);
            $net    = $gross; 

            $ins->execute([$run_id,$e['id'],$hours,$days_worked,$hourly,$e['monthly_rate'],$gross,$net]);
          }

          $db->commit();
          header("Location: /sems/public/hr/run_detail.php?run_id=".$run_id);
          exit;
        }catch(Exception $ex){
          $db->rollBack();
          $msg='Error: '.$ex->getMessage();
        }
      }
    }
  }
}

$page_title='Payroll';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>
<div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
  <?php if($msg): ?>
    <div class="p-3 rounded-lg bg-rose-50 text-rose-700 border"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl shadow-soft border p-6 space-y-4">
    <div class="text-lg font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">description</span> Run Payroll
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="text-sm">Pay Group</label>
        <select name="pay_group" class="mt-2 w-full rounded-lg border-slate-300">
          <option>WEEKLY</option>
          <option selected>SEMI_MONTHLY</option>
          <option>MONTHLY</option>
        </select>
      </div>
      <div>
        <label class="text-sm">Period Start</label>
        <input type="date" name="start" value="<?=htmlspecialchars($_POST['start']??date('Y-m-01'))?>" class="mt-2 w-full rounded-lg border-slate-300">
      </div>
      <div>
        <label class="text-sm">Period End</label>
        <input type="date" name="end" value="<?=htmlspecialchars($_POST['end']??date('Y-m-t'))?>" class="mt-2 w-full rounded-lg border-slate-300">
      </div>
    </div>
    <div class="flex justify-end">
      <button class="px-4 h-10 rounded-lg bg-emerald-600 text-white">Generate Payslips</button>
    </div>
  </form>

  <div class="bg-white rounded-xl shadow-soft border p-6">
    <div class="text-lg font-semibold mb-3">Recent Runs</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-y">
          <tr>
            <th class="px-4 py-2">Run</th>
            <th class="px-4 py-2">Pay Group</th>
            <th class="px-4 py-2">Start</th>
            <th class="px-4 py-2">End</th>
            <th class="px-4 py-2">Created</th>
            <th class="px-4 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($db->query("SELECT * FROM payroll_runs ORDER BY id DESC LIMIT 12") as $r): ?>
            <tr class="border-b hover:bg-slate-50">
              <td class="px-4 py-2"><?=htmlspecialchars($r['run_label'])?></td>
              <td class="px-4 py-2"><?=$r['pay_group']?></td>
              <td class="px-4 py-2"><?=$r['period_start']?></td>
              <td class="px-4 py-2"><?=$r['period_end']?></td>
              <td class="px-4 py-2"><?=$r['created_at']?></td>
              <td class="px-4 py-2">
                <a class="px-3 h-9 grid place-items-center rounded-lg border bg-blue-500 text-white" href="/sems/public/hr/run_detail.php?run_id=<?=$r['id']?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
