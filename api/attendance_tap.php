<?php
require __DIR__.'/bootstrap.php'; 

header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$uid  = strtoupper(trim($in['rfid_uid'] ?? ''));
$serial = trim($in['device_id'] ?? '');
$ts   = $in['ts'] ?? date('Y-m-d\TH:i:s');

if ($uid==='' || $serial==='') { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Missing rfid_uid/device_id']); exit; }

$st = $db->prepare("SELECT c.id AS card_id, c.employee_id, e.active AS emp_active
                    FROM rfid_cards c JOIN employees e ON e.id=c.employee_id
                    WHERE c.uid_hex=? AND c.active=1 LIMIT 1");
$st->execute([$uid]); $card = $st->fetch(PDO::FETCH_ASSOC);
$registered = $card ? 1 : 0;

$ev = $db->prepare("INSERT INTO rfid_tap_events (device_serial, rfid_uid, registered, seen_at) VALUES (?,?,?,?)");
$ev->execute([$serial, $uid, $registered, date('Y-m-d H:i:s')]);

if (!$card || !$card['emp_active']) {
  echo json_encode([
    'status'  => 'error',
    'code'    => 'UNREGISTERED',
    'message' => 'Card not registered to an active employee.'
  ]);
  exit;
}
$devId = null;
$ds = $db->prepare("SELECT id FROM devices WHERE serial_no=? AND active=1");
$ds->execute([$serial]);
if ($row=$ds->fetch(PDO::FETCH_ASSOC)) $devId = (int)$row['id'];

$empId = (int)$card['employee_id'];

$last = $db->prepare("SELECT id, type FROM attendance_logs WHERE employee_id=? ORDER BY tap_time DESC LIMIT 1");
$last->execute([$empId]); $L = $last->fetch(PDO::FETCH_ASSOC);

$type = 'IN'; $pair_id = null;
if ($L && $L['type']==='IN') {
  $type = 'OUT';
  $pair_id = (int)$L['id'];
}

$ins = $db->prepare("INSERT INTO attendance_logs
  (employee_id, device_id, rfid_uid, type, tap_time, verification_status, pair_id)
  VALUES (?,?,?,?,?, ?, ?)");
$ins->execute([
  $empId, $devId, $uid, $type, date('Y-m-d H:i:s', strtotime($ts)),
  $type==='IN' ? 'UNVERIFIED' : 'UNVERIFIED',  
  $pair_id
]);
$logId = (int)$db->lastInsertId();

echo json_encode(['status'=>'ok','type'=>$type,'log_id'=>$logId,'employee_id'=>$empId]);
