<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);
$run_id = (int)($_GET['run_id'] ?? 0);

$st = $db->prepare("SELECT id, run_label, pay_group, period_start, period_end, created_at
                    FROM payroll_runs WHERE id=?");
$st->execute([$run_id]);
$run = $st->fetch(PDO::FETCH_ASSOC);
if (!$run) { http_response_code(404); exit('Run not found'); }

$ps = $db->prepare("SELECT p.employee_id, p.total_hours, p.days_worked, p.hourly_rate,
                           p.gross_pay, p.net_pay,
                           e.emp_no, CONCAT(e.fname,' ',e.lname) AS name, e.position
                    FROM payslips p
                    JOIN employees e ON e.id=p.employee_id
                    WHERE p.run_id=?
                    ORDER BY e.emp_no ASC");
$ps->execute([$run_id]);
$rows = $ps->fetchAll(PDO::FETCH_ASSOC);

$totHours = 0; $totGross = 0; $totNet = 0;
foreach ($rows as $r) { $totHours += $r['total_hours']; $totGross += $r['gross_pay']; $totNet += $r['net_pay']; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payroll Summary · <?= htmlspecialchars($run['run_label']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{ --ink:#0f172a; --muted:#94a3b8; }
    *{ box-sizing:border-box }
    body{ font-family:Inter,system-ui,Segoe UI,Roboto; margin:24px; color:var(--ink); }
    .head{ margin-bottom:18px }
    .title{ font-size:20px; font-weight:700; }
    .sub{ color:var(--muted); margin-top:2px }
    table{ width:100%; border-collapse:separate; border-spacing:0; border:1px solid #e5e7eb; }
    th,td{ padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px }
    th{ background:#f8fafc; font-weight:600 }
    tfoot td{ font-weight:700; background:#f8fafc }
    .right{ text-align:right }
    .meta{ display:flex; gap:16px; margin-top:6px; color:var(--muted); font-size:12px }
    @media print{
      @page{ margin:12mm }
      body{ margin:0 }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="head">
    <div class="title">Payroll Summary</div>
    <div class="sub"><?= htmlspecialchars($run['run_label']) ?></div>
    <div class="meta">
      <div>Pay Group: <b><?= htmlspecialchars($run['pay_group']) ?></b></div>
      <div>Printed: <?= date('Y-m-d H:i') ?></div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:120px">Emp #</th>
        <th>Name</th>
        <th style="width:160px">Position</th>
        <th class="right" style="width:90px">Hours</th>
        <th class="right" style="width:70px">Days</th>
        <th class="right" style="width:90px">Hourly</th>
        <th class="right" style="width:100px">Gross</th>
        <th class="right" style="width:100px">Net</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['emp_no']) ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['position']) ?></td>
        <td class="right"><?= number_format($r['total_hours'],2) ?></td>
        <td class="right"><?= (int)$r['days_worked'] ?></td>
        <td class="right">₱<?= number_format($r['hourly_rate'],2) ?></td>
        <td class="right">₱<?= number_format($r['gross_pay'],2) ?></td>
        <td class="right">₱<?= number_format($r['net_pay'],2) ?></td>
      </tr>
      <?php endforeach; if(!$rows): ?>
      <tr><td colspan="8" style="text-align:center; padding:28px">No payslips found for this run.</td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" class="right">Totals</td>
        <td class="right"><?= number_format($totHours,2) ?></td>
        <td class="right">—</td>
        <td></td>
        <td class="right">₱<?= number_format($totGross,2) ?></td>
        <td class="right">₱<?= number_format($totNet,2) ?></td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
