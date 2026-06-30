<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$req = request_json();
require_csrf($req);
$phone = trim($req['phone'] ?? '');
$pin = trim($req['pin'] ?? '');

if(!$phone || !$pin) {
  api_error('Teléfono y PIN son requeridos', 400);
}

// 1. Check Admins
$admins = db_read('admins');
foreach($admins as &$admin) {
  $stored = $admin['pin_hash'] ?? $admin['pin'] ?? '';
  if($admin['phone'] === $phone && verify_secret($pin, $stored)) {
    if(empty($admin['pin_hash'])) {
      $admin['pin_hash'] = hash_secret($pin);
      unset($admin['pin']);
      db_write('admins', $admins);
    }
    login_user($admin['role'], $admin['id']);
    api_response(['success' => true, 'role' => $admin['role'], 'user' => public_user($admin)]);
  }
}

// 2. Check Manicurists
$manicurists = db_read('manicurists');
foreach($manicurists as &$man) {
  $stored = $man['pin_hash'] ?? $man['pin'] ?? '';
  if(normalize_phone($man['phone'] ?? '') === normalize_phone($phone) && verify_secret($pin, $stored)) {
    if(!($man['active'] ?? true)) api_error('Cuenta inactiva', 403);
    if(empty($man['pin_hash'])) {
      $man['pin_hash'] = hash_secret($pin);
      unset($man['pin']);
      db_write('manicurists', $manicurists);
    }
    login_user('manicurist', $man['id']);
    api_response(['success' => true, 'role' => 'manicurist', 'user' => public_user($man)]);
  }
}

// 3. Check Clients
$clients = db_read('clients');
$client_found = false;
foreach($clients as &$client) {
  if(normalize_phone($client['phone'] ?? '') === normalize_phone($phone)) {
    $client_found = true;
    $stored = $client['pin_hash'] ?? $client['pin'] ?? '';
    if(verify_secret($pin, $stored)) {
      if(empty($client['pin_hash'])) {
        $client['pin_hash'] = hash_secret($pin);
        unset($client['pin']);
        db_write('clients', $clients);
      }
      login_user('client', $client['id']);
      api_response(['success' => true, 'role' => 'client', 'user' => public_user($client)]);
    } else {
      api_error('PIN incorrecto', 401);
    }
  }
}

// If client not found, we could register them here (Auto-register for loyalty apps is common)
if(!$client_found) {
  $new_client = [
    'id' => uniqid('cli_'),
    'phone' => normalize_phone($phone),
    'pin_hash' => hash_secret($pin),
    'name' => 'Nueva Clienta',
    'points' => 0,
    'visits' => 0,
    'created_at' => date('Y-m-d H:i:s')
  ];
  $clients[] = $new_client;
  db_write('clients', $clients);
  login_user('client', $new_client['id']);
  api_response(['success' => true, 'role' => 'client', 'user' => public_user($new_client), 'is_new' => true]);
}

api_error('Error desconocido', 500);
?>
