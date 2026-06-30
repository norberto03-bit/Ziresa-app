<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/loyalty.php';

$user = require_auth(['admin1', 'admin2', 'manicurist']);
$req = request_json();
require_csrf($req);

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

// If it's a payment for an appointment, complete it and sync loyalty once.
if($appointment_id && $type === 'ingreso') {
  $appointments = db_read('appointments', []);
  foreach($appointments as &$app) {
    if($app['id'] === $appointment_id) {
      $app['status'] = 'completada';
      $app['completed_by'] = $user['id'];
      $app['updated_at'] = date('Y-m-d H:i:s');
    }
  }
  db_write('appointments', $appointments);
  ziresa_apply_completion_effects($appointment_id, $user['id']);
}

api_response(['success' => true, 'record' => $new_record]);
?>
