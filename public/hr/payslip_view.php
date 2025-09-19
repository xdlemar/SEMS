<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);

$run_id = (int)($_GET['run_id'] ?? 0);
$emp_id = (int)($_GET['emp_id'] ?? 0);
if (!$run_id || !$emp_id) { http_response_code(404); exit('Not found'); }

$st = $db->prepare("SELECT id FROM payslips WHERE run_id=? AND employee_id=? LIMIT 1");
$st->execute([$run_id, $emp_id]);
$pid = (int)$st->fetchColumn();

if (!$pid) { http_response_code(404); exit('Payslip not found'); }
header("Location: /sems/public/payslip.php?id=".$pid);
exit;
