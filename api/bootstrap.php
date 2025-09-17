<?php
$dsn = "mysql:host=127.0.0.1;dbname=sems;charset=utf8mb4";
$db  = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function json($data, $code=200){
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data); exit;
}
function device_id(PDO $db, $serial){
  $q=$db->prepare("SELECT id FROM devices WHERE serial_no=? AND active=1");
  $q->execute([$serial]);
  return $q->fetchColumn() ?: null;
}
function find_emp_by_uid(PDO $db, $uid){
  $q=$db->prepare("SELECT e.* FROM rfid_cards c JOIN employees e ON e.id=c.employee_id WHERE c.uid_hex=? AND c.active=1 AND e.active=1");
  $q->execute([$uid]);
  return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
