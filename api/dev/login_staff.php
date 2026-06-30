<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

function ziresa_dev_access_enabled(){
  $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
  $addr = $_SERVER['REMOTE_ADDR'] ?? '';
  if(strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || $addr === '127.0.0.1' || $addr === '::1') {
    return true;
  }
  $settings = db_read('settings', []);
  return !empty($settings['dev_access_enabled']) || !empty($settings['dev_staff_access_enabled']);
}

if(!ziresa_dev_access_enabled()) {
  http_response_code(403);
  echo 'Acceso staff de prueba desactivado.';
  exit;
}

$staff = db_read('manicurists', []);
$staff_id = '';

if(!empty($staff)) {
  foreach($staff as $member) {
    if($member['active'] ?? true) {
      $staff_id = $member['id'] ?? '';
      break;
    }
  }
}

if(!$staff_id) {
  db_with_locks(['manicurists'], function() use (&$staff_id) {
    $staff = db_read('manicurists', []);
    $staff_id = 'man_dev';
    $staff[] = [
      'id' => $staff_id,
      'name' => 'Staff Dev',
      'phone' => '8100000001',
      'pin_hash' => hash_secret('1234'),
      'active' => true
    ];
    db_write_unlocked('manicurists', $staff);
  });
}

login_user('manicurist', $staff_id);
header('Location: ../../manicurista.html');
exit;
?>
