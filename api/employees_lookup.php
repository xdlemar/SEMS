<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: application/json');

$attId = (int)($_GET['attendance_id'] ?? 0);
if (!$attId) { http_response_code(400); echo json_encode(['error'=>'attendance_id required']); exit; }

$st=$db->prepare("SELECT a.id, a.tap_time, a.employee_id,
                         e.emp_no, CONCAT(e.fname,' ',e.lname) AS name,
                         e.position, e.department, e.photo_path
                  FROM attendance_logs a
                  JOIN employees e ON e.id=a.employee_id
                  WHERE a.id=? LIMIT 1");
$st->execute([$attId]); $row=$st->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); echo json_encode(['error'=>'not found']); exit; }

$ph=$db->prepare("SELECT path FROM attendance_photos WHERE attendance_id=? ORDER BY id DESC LIMIT 1");
$ph->execute([$attId]); $cap=$ph->fetchColumn();

echo json_encode([
  'emp_no'        => $row['emp_no'],
  'name'          => $row['name'],
  'position'      => $row['position'],
  'department'    => $row['department'],
  'profile_photo' => $row['photo_path'] ?: null,
  'last_capture'  => $cap ?: null,
  'tap_time'      => $row['tap_time'],
]);
