<?php
require __DIR__.'/bootstrap.php'; header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$empId = (int)($in['employee_id'] ?? 0);
$uid   = strtoupper(trim($in['uid_hex'] ?? ''));

if(!$empId || $uid===''){ http_response_code(400); echo json_encode(['status'=>'error','message'=>'employee_id and uid_hex required']); exit; }

$st=$db->prepare("SELECT 1 FROM rfid_cards WHERE uid_hex=? AND active=1");
$st->execute([$uid]);
if($st->fetch()){ echo json_encode(['status'=>'error','code'=>'UID_IN_USE','message'=>'This card UID is already registered.']); exit; }

$st=$db->prepare("SELECT 1 FROM rfid_cards WHERE employee_id=? AND active=1");
$st->execute([$empId]);
if($st->fetch()){ echo json_encode(['status'=>'error','code'=>'EMP_HAS_CARD','message'=>'Employee already has an active card.']); exit; }

$ins=$db->prepare("INSERT INTO rfid_cards (employee_id, uid_hex, active, issued_at) VALUES (?,?,1, NOW())");
$ins->execute([$empId,$uid]);

echo json_encode(['status'=>'ok']);

