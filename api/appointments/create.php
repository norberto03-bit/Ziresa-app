<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/appointments.php';

$user = require_auth(['client', 'admin1', 'admin2']);
$req = request_json();
require_csrf($req);

$date = $req['date'] ?? '';
$time = $req['time'] ?? '';
$services_ids = $req['services'] ?? [];
$manicurist_id = $req['manicurist_id'] ?? '';

if(!$date || !$time || empty($services_ids) || !$manicurist_id) {
  api_error('Faltan datos', 400);
}
if(!is_valid_date_string($date)) api_error('Fecha invalida', 400);
if(!is_valid_time_string($time)) api_error('Hora invalida', 400);

$service_bundle = build_selected_services($services_ids);
$selected_services = $service_bundle['services'];
$total_duration = $service_bundle['duration'];
$total_price = $service_bundle['price'];
if(empty($selected_services) || $total_duration <= 0) api_error('Servicios invalidos', 400);

$new_app = db_with_locks(['appointments'], function() use ($date, $time, $manicurist_id, $total_duration, $selected_services, $total_price, $user, $req) {
  if(!is_bookable_slot_locked($date, $time, $manicurist_id, $total_duration)) {
    api_error('Ese horario ya no esta disponible. Elige otro horario.', 409);
  }

  $appointments = db_read('appointments', []);
  $new_app = [
    'id' => uniqid('app_'),
    'folio' => strtoupper(substr(uniqid('ZR'), -8)),
    'client_id' => $user['type'] === 'client' ? $user['id'] : ($req['client_id'] ?? ''),
    'manicurist_id' => $manicurist_id,
    'date' => $date,
    'time' => $time,
    'end_time' => date('H:i', strtotime("$date $time") + ($total_duration * 60)),
    'duration' => $total_duration,
    'services' => $selected_services,
    'price' => $total_price,
    'status' => 'pendiente_confirmacion_wa',
    'created_at' => date('Y-m-d H:i:s')
  ];

  $appointments[] = $new_app;
  db_write_unlocked('appointments', $appointments);
  return $new_app;
});

api_response(['success' => true, 'appointment' => $new_app]);
?>
