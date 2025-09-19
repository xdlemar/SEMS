<?php
require __DIR__.'/partials/auth.php'; login_required();
$id=(int)($_GET['id']??0);

$ps=$db->prepare("SELECT p.*, pr.period_start, pr.period_end, pr.run_label,
                         e.emp_no, e.fname, e.lname, e.position, e.department
                  FROM payslips p
                  JOIN payroll_runs pr ON pr.id=p.run_id
                  JOIN employees e ON e.id=p.employee_id
                  WHERE p.id=?");
$ps->execute([$id]); $p=$ps->fetch(PDO::FETCH_ASSOC);
if(!$p){ http_response_code(404); exit('Payslip not found'); }

$me=current_user($db);
$allowed = in_array($me['role'],['HR','ADMIN']) || ($me['employee_id']==$p['employee_id']);
if(!$allowed){ http_response_code(403); exit('Forbidden'); }

$page_title='Payslip · '.$p['emp_no'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($page_title)?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{ --ink:#0f172a; --muted:#64748b; }
    *{box-sizing:border-box} body{font-family:Inter,system-ui;margin:0;background:#f1f5f9;color:var(--ink)}
    .wrap{max-width:800px;margin:24px auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 4px 20px rgba(2,8,23,.06)}
    .pad{padding:24px}
    .row{display:flex;gap:16px;align-items:flex-start;justify-content:space-between}
    h1{font-size:20px;margin:0 0 8px}
    .muted{color:var(--muted)}
    table{width:100%;border-collapse:separate;border-spacing:0;margin-top:12px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    th,td{padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:left}
    thead th{background:#f8fafc;font-weight:600}
    tfoot td{font-weight:700}
    .right{text-align:right}
    .btns{display:flex;gap:8px;justify-content:flex-end}
    .btn{padding:.6rem .9rem;border-radius:10px;border:1px solid #d1d5db;background:#fff}
    .btn.primary{background:#0f172a;color:#fff;border-color:#0f172a}
    @media print{ .btns{display:none} body{background:#fff} .wrap{box-shadow:none;border:0;margin:0;border-radius:0} }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="pad">
      <div class="row">
        <div>
          <h1>Smart Employee Monitoring System</h1>
          <div class="muted">Payslip</div>
        </div>
        <div class="btns">
          <a class="btn" href="/sems/public/hr/payroll_run.php">Back</a>
          <button class="btn primary" onclick="window.print()">Print</button>
        </div>
      </div>

      <div class="row" style="margin-top:18px">
        <div>
          <div><strong>Employee:</strong> <?=htmlspecialchars($p['emp_no'].' · '.$p['fname'].' '.$p['lname'])?></div>
          <div class="muted"><?=htmlspecialchars($p['position'])?> — <?=htmlspecialchars($p['department'])?></div>
        </div>
        <div>
          <div><strong>Period:</strong> <?=htmlspecialchars($p['period_start'])?> → <?=htmlspecialchars($p['period_end'])?></div>
          <div class="muted"><?=htmlspecialchars($p['run_label'])?></div>
        </div>
      </div>

      <table>
        <thead><tr><th>Description</th><th class="right">Rate</th><th class="right">Total Hours</th><th class="right">Amount</th></tr></thead>
        <tbody>
          <tr><td>Regular Hours</td>
           <td class="right">₱<?=number_format($p['hourly_rate'],2)?></td>
              <td class="right"><?=number_format($p['total_hours'],2)?></td>
              <td class="right">₱<?=number_format($p['gross_pay'],2)?></td></tr>
          <!-- Add rows here in the future: OT, Allowances, etc. -->
        </tbody>
        <tfoot>
          <tr><td colspan="3" class="right">Deductions</td><td class="right">₱<?=number_format($p['deductions'],2)?></td></tr>
          <tr><td colspan="3" class="right">Net Pay</td><td class="right">₱<?=number_format($p['net_pay'],2)?></td></tr>
        </tfoot>
      </table>

      <div class="muted" style="margin-top:12px">Generated: <?=htmlspecialchars($p['generated_at'])?></div>
    </div>
  </div>
</body>
</html>
