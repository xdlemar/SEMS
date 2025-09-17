<?php
require __DIR__.'/../partials/auth.php'; require_role(['HR','ADMIN']);
$page_title='Attendance History'; require __DIR__.'/../partials/layout_head.php'; require __DIR__.'/../partials/layout_nav.php';

$emp_id = isset($_GET['employee_id']) && $_GET['employee_id']!=='' ? (int)$_GET['employee_id'] : null;
$from   = $_GET['from'] ?? date('Y-m-01');
$to     = $_GET['to']   ?? date('Y-m-d');

$emps=$db->query("SELECT id, CONCAT(emp_no,' · ',fname,' ',lname) AS label FROM employees WHERE active=1 ORDER BY fname")->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT a.employee_id, CONCAT(e.emp_no,' · ',e.fname,' ',e.lname) AS emp,
             a.type,a.tap_time, a.pair_id
      FROM attendance_logs a
      JOIN employees e ON e.id=a.employee_id
      WHERE DATE(a.tap_time) BETWEEN ? AND ?".
      ($emp_id? " AND a.employee_id=".$db->quote($emp_id) : "").
      " ORDER BY e.lname, a.tap_time";
$st=$db->prepare($sql); $st->execute([$from,$to]); $rows=$st->fetchAll(PDO::FETCH_ASSOC);

$byEmp=[];
foreach($rows as $r){ $byEmp[$r['employee_id']]['name']=$r['emp']; $byEmp[$r['employee_id']]['rows'][]=$r; }

function weekly_key($date){ $d=new DateTime($date); return $d->format("o-W"); }
?>
<div class="max-w-6xl mx-auto">
  <form class="card mb-4 grid sm:grid-cols-4 gap-3 items-end">
    <div><label class="text-sm">Employee</label>
      <select name="employee_id" class="mt-2 w-full rounded-lg border-slate-300">
        <option value="">All</option>
        <?php foreach($emps as $e): ?>
          <option value="<?= (int)$e['id'] ?>" <?= $emp_id===$e['id']?'selected':'' ?>><?= htmlspecialchars($e['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label class="text-sm">From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="mt-2 w-full rounded-lg border-slate-300"></div>
    <div><label class="text-sm">To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="mt-2 w-full rounded-lg border-slate-300"></div>
    <div><button class="btn mt-8">Filter</button></div>
  </form>

  <?php if(!$byEmp): ?>
    <div class="card text-slate-500">No records for the selected range.</div>
  <?php endif; ?>

  <?php foreach($byEmp as $empId=>$data): ?>
    <div class="card mb-4">
      <div class="font-semibold mb-2 flex items-center gap-2"><span class="material-symbols-outlined">person</span> <?= htmlspecialchars($data['name']) ?></div>
      <?php
        $weeks=[];
        foreach($data['rows'] as $r){ $wk=weekly_key($r['tap_time']); $weeks[$wk][]=$r; }
        foreach($weeks as $wk=>$logs):
          $mins=0; $open=null;
          foreach($logs as $r){
            if($r['type']==='IN'){ $open=$r['tap_time']; }
            elseif($r['type']==='OUT' && $open){ $mins += (strtotime($r['tap_time'])-strtotime($open))/60; $open=null; }
          }
          $hours=round($mins/60,2);
      ?>
        <div class="mb-2 text-sm text-slate-500">Week <?= htmlspecialchars($wk) ?> • Total: <span class="font-medium text-slate-700"><?= $hours ?> h</span></div>
        <div class="overflow-x-auto mb-4">
          <table class="table text-sm">
            <thead><tr><th>Date/Time</th><th>Type</th></tr></thead>
            <tbody>
              <?php foreach($logs as $r): ?>
                <tr><td><?= htmlspecialchars($r['tap_time']) ?></td><td><?= $r['type'] ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
