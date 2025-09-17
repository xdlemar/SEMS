<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: application/json');

try {
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'photo file required']); exit;
  }

  $uid    = strtoupper(trim($_POST['rfid_uid'] ?? ''));
  $serial = trim($_POST['device_id'] ?? '');
  if ($uid === '' || $serial === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'rfid_uid and device_id required']); exit;
  }

  $st=$db->prepare("SELECT e.id AS employee_id
                    FROM rfid_cards c
                    JOIN employees e ON e.id=c.employee_id
                    WHERE c.uid_hex=? AND c.active=1 AND e.active=1
                    LIMIT 1");
  $st->execute([$uid]); $card=$st->fetch(PDO::FETCH_ASSOC);
  if (!$card) {
    http_response_code(409);
    echo json_encode(['status'=>'error','code'=>'UNREGISTERED','message'=>'UID not bound to an active employee']); exit;
  }
  $empId=(int)$card['employee_id'];

  $st=$db->prepare("SELECT id
                    FROM attendance_logs
                    WHERE employee_id=? AND type='IN'
                      AND tap_time >= (NOW() - INTERVAL 15 MINUTE)
                    ORDER BY tap_time DESC
                    LIMIT 1");
  $st->execute([$empId]); $log=$st->fetch(PDO::FETCH_ASSOC);
  if (!$log) {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'No recent IN log to attach photo']); exit;
  }
  $attendanceId=(int)$log['id'];


  $dir = __DIR__.'/../public/uploads/attendance';
  if (!is_dir($dir)) mkdir($dir,0775,true);

  $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION) ?: 'jpg');
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) $ext='jpg';
  $file = sprintf('%s_%s.%s', date('Ymd_His'), $uid, $ext);
  $disk = $dir.'/'.$file;

  if (!move_uploaded_file($_FILES['photo']['tmp_name'], $disk)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Failed to save file']); exit;
  }

  $url = "/sems/public/uploads/attendance/".$file;
  $ins=$db->prepare("INSERT INTO attendance_photos (attendance_id, path, captured_at) VALUES (?,?, NOW())");
  $ins->execute([$attendanceId, $url]);

  echo json_encode(['status'=>'ok','attendance_id'=>$attendanceId,'url'=>$url]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
