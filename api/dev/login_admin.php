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
  return !empty($settings['dev_access_enabled']) || !empty($settings['dev_admin_access_enabled']);
}

if(!ziresa_dev_access_enabled()) {
  http_response_code(403);
  echo 'Acceso admin de prueba desactivado.';
  exit;
}

$admins = db_read('admins', []);
$admin_id = '';

if(!empty($admins)) {
  foreach($admins as $admin) {
    if(($admin['role'] ?? '') === 'admin1') {
      $admin_id = $admin['id'] ?? '';
      break;
    }
  }
  if(!$admin_id) $admin_id = $admins[0]['id'] ?? '';
}

if(!$admin_id) {
  db_with_locks(['admins'], function() use (&$admin_id) {
    $admins = db_read('admins', []);
    $admin_id = 'admin_dev';
    $admins[] = [
      'id' => $admin_id,
      'phone' => 'admin-dev',
      'pin_hash' => hash_secret('1234'),
      'role' => 'admin1',
      'name' => 'Admin Dev'
    ];
    db_write_unlocked('admins', $admins);
  });
}

login_user('admin1', $admin_id);
header('Location: ../../admin.html');
exit;
?>
