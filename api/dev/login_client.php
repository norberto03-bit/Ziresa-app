<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

function ziresa_is_local_request(){
  $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
  $addr = $_SERVER['REMOTE_ADDR'] ?? '';
  return strpos($host, 'localhost') !== false
    || strpos($host, '127.0.0.1') !== false
    || $addr === '127.0.0.1'
    || $addr === '::1';
}

$settings = db_read('settings', []);
$dev_enabled = ziresa_is_local_request() || !empty($settings['dev_client_access_enabled']);

if(!$dev_enabled) {
  http_response_code(403);
  echo 'Acceso de prueba desactivado. Activa dev_client_access_enabled solo durante pruebas.';
  exit;
}

$requested_client_id = trim($_GET['client_id'] ?? '');
$client_id = '';

$result = db_with_locks(['clients'], function() use ($requested_client_id, &$client_id) {
  $clients = db_read('clients', []);

  if($requested_client_id !== '') {
    foreach($clients as $client) {
      if(($client['id'] ?? '') === $requested_client_id) {
        $client_id = $requested_client_id;
        return true;
      }
    }
  }

  if(!empty($clients)) {
    $client_id = $clients[0]['id'] ?? '';
    return true;
  }

  $client_id = 'cli_demo';
  $clients[] = [
    'id' => $client_id,
    'phone' => '8100000000',
    'pin_hash' => hash_secret('1234'),
    'name' => 'Clienta Demo',
    'points' => 40,
    'visits' => 4,
    'created_at' => date('Y-m-d H:i:s'),
    'loyalty_note' => 'Cuenta demo para pruebas del hub cliente'
  ];
  db_write_unlocked('clients', $clients);
  return true;
});

if(!$result || !$client_id) {
  http_response_code(500);
  echo 'No se pudo crear sesion demo.';
  exit;
}

login_user('client', $client_id);
header('Location: ../../clientes.html');
exit;
?>
