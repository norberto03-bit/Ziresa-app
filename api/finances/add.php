<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$user = require_auth(['admin1', 'manicurist']);
$req = request_json();

$type = $req['type'] ?? 'ingreso'; // ingreso, egreso, propina
$amount = floatval($req['amount'] ?? 0);
$method = $req['method'] ?? 'efectivo'; // efectivo, tarjeta
$description = $req['description'] ?? '';
$appointment_id = $req['appointment_id'] ?? '';
$manicurist_id = $user['type'] === 'manicurist' ? $user['id'] : ($req['manicurist_id'] ?? '');

if($amount <= 0) api_error('Monto invalido', 400);
if(!in_array($type, ['ingreso', 'egreso', 'propina'])) api_error('Tipo de movimiento invalido', 400);
if(!in_array($method, ['efectivo', 'tarjeta', 'transferencia'])) api_error('Metodo de pago invalido', 400);

$finances = db_read('finances', []);
$new_record = [
  'id' => uniqid('fin_'),
  'type' => $type,
  'amount' => $amount,
  'method' => $method,
  'description' => $description,
  'appointment_id' => $appointment_id,
  'manicurist_id' => $manicurist_id,
  'date' => date('Y-m-d'),
  'created_at' => date('Y-m-d H:i:s'),
  'created_by' => $user['id']
];

$finances[] = $new_record;
db_write('finances', $finances);

// If it's a payment for an appointment, update appointment status
if($appointment_id && $type === 'ingreso') {
  $appointments = db_read('appointments', []);
  $client_id = null;
  foreach($appointments as &$app) {
    if($app['id'] === $appointment_id) {
      $app['status'] = 'pagada'; // or 'completada'
      if(!($app['loyalty_processed'] ?? false)) {
        $app['loyalty_processed'] = true;
        $client_id = $app['client_id'] ?? null;
      }
    }
  }
  db_write('appointments', $appointments);
  if($client_id) {
    $clients = db_read('clients', []);
    foreach($clients as &$client) {
      if(($client['id'] ?? '') === $client_id) {
        $client['visits'] = ($client['visits'] ?? 0) + 1;
        $client['points'] = ($client['points'] ?? 0) + 10;
        if($client['visits'] === 5) $client['discounts'][] = ['type' => '20_off', 'used' => false];
        if($client['visits'] === 10) $client['discounts'][] = ['type' => '50_off', 'used' => false];
        break;
      }
    }
    db_write('clients', $clients);
  }
}

api_response(['success' => true, 'record' => $new_record]);
?>
