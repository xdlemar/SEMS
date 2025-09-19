<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);

header('Content-Type: application/json');

$role = $_GET['role'] ?? '';
$current_emp_id = (int)($_GET['current_emp_id'] ?? 0);

if (!in_array($role, ['ADMIN','HR','MANAGER','EMPLOYEE'], true)) {
  echo json_encode([]); exit;
}

$sql = "SELECT e.id, CONCAT(e.emp_no,' · ',e.fname,' ',e.lname) AS label
        FROM employees e
        LEFT JOIN users u
          ON u.employee_id = e.id AND u.role = ?
        WHERE e.active=1
          AND (u.id IS NULL OR e.id = ?)
        ORDER BY e.emp_no, e.lname, e.fname";

$st = $db->prepare($sql);
$st->execute([$role, $current_emp_id]);

$out = [];
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
  $out[] = ['id'=>(int)$r['id'], 'label'=>$r['label']];
}
echo json_encode($out);
