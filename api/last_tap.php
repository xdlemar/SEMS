<?php
require __DIR__.'/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$serial = trim($_GET['device_id'] ?? $_GET['serial'] ?? '');
$since  = isset($_GET['since']) ? (int)$_GET['since'] : 0; // unix epoch seconds
if ($serial===''){ http_response_code(400); echo json_encode(['error'=>'device_id required']); exit; }

if ($since > 0) {
  $st=$db->prepare("SELECT id, rfid_uid, registered, UNIX_TIMESTAMP(seen_at) AS ts
                    FROM rfid_tap_events
                    WHERE device_serial=? AND seen_at > FROM_UNIXTIME(?)
                    ORDER BY id DESC LIMIT 1");
  $st->execute([$serial, $since]);
} else {
  $st=$db->prepare("SELECT id, rfid_uid, registered, UNIX_TIMESTAMP(seen_at) AS ts
                    FROM rfid_tap_events
                    WHERE device_serial=?
                    ORDER BY id DESC LIMIT 1");
  $st->execute([$serial]);
}
$row = $st->fetch(PDO::FETCH_ASSOC);

$out = $row ?: ['id'=>null,'rfid_uid'=>null,'registered'=>0,'ts'=>null];

if ($out && $out['rfid_uid'] && (int)$out['registered'] === 1) {
  $emp = $db->prepare("SELECT e.id AS employee_id, e.emp_no,
                              CONCAT(e.fname,' ',e.lname) AS name
                       FROM rfid_cards c
                       JOIN employees e ON e.id=c.employee_id
                       WHERE c.uid_hex=? AND c.active=1
                       LIMIT 1");
  $emp->execute([$out['rfid_uid']]);
  if ($e = $emp->fetch(PDO::FETCH_ASSOC)) {
    $out['employee_id'] = (int)$e['employee_id'];
    $out['emp_no']      = $e['emp_no'];
    $out['name']        = $e['name'];

    $devId = null;
    try {
      $qd = $db->prepare("SELECT id FROM devices WHERE serial_no=? OR serial=? LIMIT 1");
      $qd->execute([$serial, $serial]);
      $devId = (int)$qd->fetchColumn();
    } catch (Throwable $__) {}

    if ($devId) {
      $ql = $db->prepare("SELECT id, type
                          FROM attendance_logs
                          WHERE employee_id=? AND device_id=? 
                            AND tap_time BETWEEN (FROM_UNIXTIME(?)-INTERVAL 3 MINUTE)
                                              AND (FROM_UNIXTIME(?)+INTERVAL 3 MINUTE)
                          ORDER BY tap_time DESC
                          LIMIT 1");
      $ql->execute([$out['employee_id'], $devId, $out['ts'], $out['ts']]);
    } else {
      $ql = $db->prepare("SELECT id, type
                          FROM attendance_logs
                          WHERE employee_id=? 
                            AND tap_time >= (FROM_UNIXTIME(?)-INTERVAL 5 MINUTE)
                          ORDER BY tap_time DESC
                          LIMIT 1");
      $ql->execute([$out['employee_id'], $out['ts']]);
    }
    if ($log = $ql->fetch(PDO::FETCH_ASSOC)) {
      $out['log_id'] = (int)$log['id'];
      $out['type']   = $log['type']; // 'IN' or 'OUT'
    }
  }
}

echo json_encode($out);
