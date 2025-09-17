<?php
require __DIR__.'/../partials/auth.php'; login_required();
$me = current_user($db);


$lt=$db->prepare("SELECT type,tap_time FROM attendance_logs WHERE employee_id=? ORDER BY id DESC LIMIT 1");
$lt->execute([$me['employee_id']]);
$last=$lt->fetch(PDO::FETCH_ASSOC);


$from=date('Y-m-01'); $to=date('Y-m-t');
$q=$db->prepare("SELECT type,tap_time FROM attendance_logs WHERE employee_id=? AND DATE(tap_time) BETWEEN ? AND ? ORDER BY tap_time ASC");
$q->execute([$me['employee_id'],$from,$to]);
$logs=$q->fetchAll(PDO::FETCH_ASSOC);
$sec=0;$in=null; foreach($logs as $r){ if($r['type']==='IN'){ $in=strtotime($r['tap_time']); } if($r['type']==='OUT' && $in){ $sec+=strtotime($r['tap_time'])-$in; $in=null; } }
$hrs=round($sec/3600,2);

$page_title='Employee Dashboard';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';
?>

  <div class="max-w-6xl mx-auto p-4 md:p-6">
    <div class="grid sm:grid-cols-3 gap-4">

      <div class="bg-white rounded-2xl shadow-soft border p-4">
        <div class="text-sm text-slate-500">Welcome</div>
        <div class="text-2xl font-semibold"><?= htmlspecialchars($me['username']) ?></div>
        <div class="mt-3 text-xs text-slate-500">Emp #</div>
        <div class="font-medium"><?= htmlspecialchars($me['emp_no'] ?? '') ?></div>
      </div>

      <a class="bg-white rounded-2xl shadow-soft border p-4 hover:shadow-md transition" href="/sems/public/employee/attendance.php">
        <div class="text-sm text-slate-500">This Month Hours</div>
        <div class="text-2xl font-semibold"><?= number_format($hrs,2) ?></div>
        <div class="mt-3 text-xs text-slate-500">Last Tap</div>
        <div class="font-medium">
          <?= $last ? ($last['type'].' @ '.date('Y-m-d H:i', strtotime($last['tap_time']))) : '—' ?>
        </div>
      </a>

      <a class="bg-white rounded-2xl shadow-soft border p-4 hover:shadow-md transition" href="/sems/public/employee/payslips.php">
        <div class="text-sm text-slate-500">My</div>
        <div class="text-2xl font-semibold">Payslips</div>
        <div class="mt-3 text-xs text-slate-500">Period</div>
        <div class="font-medium"><?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
      </a>

    </div>
  </div>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
