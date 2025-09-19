<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN']);
$page_title = 'Admin Dashboard';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

/* --- Quick stats --- */
$stats = [
  'employees'         => (int)$db->query("SELECT COUNT(*) FROM employees WHERE active=1")->fetchColumn(),
  'cards_bound'       => (int)$db->query("SELECT COUNT(*) FROM rfid_cards WHERE active=1")->fetchColumn(),
  'pending_approvals' => (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE approved_at IS NULL")->fetchColumn(),
  'today_in'          => (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE type='IN'  AND DATE(tap_time)=CURDATE()")->fetchColumn(),
  'today_out'         => (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE type='OUT' AND DATE(tap_time)=CURDATE()")->fetchColumn(),
];
$stats['cards_unbound'] = max(0, $stats['employees'] - $stats['cards_bound']);

try { $stats['devices'] = (int)$db->query("SELECT COUNT(*) FROM devices")->fetchColumn(); }
catch(Throwable $__) { $stats['devices'] = 0; }

try {
  $last_run = $db->query("SELECT id, run_label, created_at FROM payroll_runs ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch(Throwable $__) { $last_run = null; }

/* --- Recent logs --- */
$recent = $db->query("
  SELECT a.id, a.type, a.tap_time,
         e.emp_no, CONCAT(e.fname,' ',e.lname) AS name
    FROM attendance_logs a
    LEFT JOIN employees e ON e.id=a.employee_id
   ORDER BY a.tap_time DESC
   LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* --- 7 day IN counts for mini chart --- */
$raw = $db->query("
  SELECT DATE(tap_time) d, SUM(type='IN') c
    FROM attendance_logs
   WHERE tap_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
   GROUP BY DATE(tap_time)
   ORDER BY d
")->fetchAll(PDO::FETCH_KEY_PAIR);

$series = [];
for($i=6;$i>=0;$i--){
  $d = date('Y-m-d', strtotime("-$i day"));
  $series[] = ['d'=>$d, 'c'=>(int)($raw[$d]??0)];
}
?>
<div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">
  <!-- KPIs -->
  <div class="grid md:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl shadow-soft border p-4">
      <div class="text-sm text-slate-500">Employees</div>
      <div class="text-3xl font-semibold mt-1"><?= number_format($stats['employees']) ?></div>
      <div class="text-xs mt-2">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
          bound: <?= number_format($stats['cards_bound']) ?>
        </span>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-50 text-slate-700 border ml-1">
          none: <?= number_format($stats['cards_unbound']) ?>
        </span>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-soft border p-4">
      <div class="text-sm text-slate-500">Pending approvals</div>
      <div class="text-3xl font-semibold mt-1"><?= number_format($stats['pending_approvals']) ?></div>
      <a href="/sems/public/hr/approvals.php" class="text-xs text-sky-700 hover:underline mt-2 inline-block">Review now</a>
    </div>

    <div class="bg-white rounded-xl shadow-soft border p-4">
      <div class="text-sm text-slate-500">Today’s taps</div>
      <div class="text-3xl font-semibold mt-1"><?= number_format($stats['today_in']+$stats['today_out']) ?></div>
      <div class="text-xs mt-2 text-slate-700">IN: <?= (int)$stats['today_in'] ?> • OUT: <?= (int)$stats['today_out'] ?></div>
    </div>

    <div class="bg-white rounded-xl shadow-soft border p-4">
      <div class="text-sm text-slate-500">Devices</div>
      <div class="text-3xl font-semibold mt-1"><?= number_format($stats['devices']) ?></div>
      <?php if($last_run): ?>
        <div class="text-xs mt-2">Last payroll: <a class="underline" href="/sems/public/hr/run_detail.php?run_id=<?= (int)$last_run['id'] ?>"><?= htmlspecialchars($last_run['run_label']) ?></a></div>
      <?php else: ?>
        <div class="text-xs mt-2 text-slate-500">No payroll runs yet</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Activity & Chart -->
  <div class="grid lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow-soft border p-4 lg:col-span-2">
      <div class="flex items-center justify-between">
        <div class="text-lg font-semibold">Last taps</div>
        <a class="text-sm text-sky-700 hover:underline" href="/sems/public/hr/attendance_history.php">View history</a>
      </div>
      <div class="mt-3 divide-y">
        <?php if(!$recent): ?>
          <div class="py-4 text-sm text-slate-500">No taps yet.</div>
        <?php else: foreach($recent as $r): ?>
          <div class="py-3 flex items-center justify-between">
            <div>
              <div class="font-medium"><?= htmlspecialchars($r['name'] ?? '—') ?></div>
              <div class="text-xs text-slate-500"><?= htmlspecialchars($r['emp_no'] ?? '—') ?></div>
            </div>
            <div class="text-xs text-slate-500"><?= date('M j, g:i:s A', strtotime($r['tap_time'])) ?></div>
            <div>
              <?php if($r['type']==='IN'): ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">IN</span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-amber-50 text-amber-700 border border-amber-200">OUT</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-soft border p-4">
      <div class="text-lg font-semibold">7-day arrivals</div>
      <canvas id="arrivals" class="mt-3" height="160"></canvas>
      <script>
        (function(){
          const data = <?= json_encode($series) ?>; // [{d:'YYYY-MM-DD', c: n}]
          const cv = document.getElementById('arrivals');
          const ctx = cv.getContext('2d');
          const W = cv.width, H = cv.height;
          // scale
          const max = Math.max(1, ...data.map(x=>x.c));
          const step = W/(data.length-1||1);
          ctx.clearRect(0,0,W,H);
          ctx.lineWidth = 2; ctx.strokeStyle = '#2563eb'; ctx.beginPath();
          data.forEach((pt,i)=>{
            const x = i*step;
            const y = H - (pt.c/max)*H*0.9 - 10;
            i?ctx.lineTo(x,y):ctx.moveTo(x,y);
          });
          ctx.stroke();
          // dots
          ctx.fillStyle = '#1d4ed8';
          data.forEach((pt,i)=>{
            const x = i*step;
            const y = H - (pt.c/max)*H*0.9 - 10;
            ctx.beginPath(); ctx.arc(x,y,3,0,Math.PI*2); ctx.fill();
          });
        })();
      </script>
      <div class="mt-2 text-xs text-slate-500">Counts of IN per day</div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
