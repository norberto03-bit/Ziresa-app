<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/appointments.php';

$user = require_auth(['client', 'admin1', 'admin2']);
$req = request_json();

$date = $req['date'] ?? '';
$time = $req['time'] ?? '';
$services_ids = $req['services'] ?? []; // Array of service ids
$manicurist_id = $req['manicurist_id'] ?? '';

if(!$date || !$time || empty($services_ids) || !$manicurist_id) {
  api_error('Faltan datos', 400);
}
if(!is_valid_date_string($date)) api_error('Fecha inválida', 400);
if(!is_valid_time_string($time)) api_error('Hora inválida', 400);

$service_bundle = build_selected_services($services_ids);
$selected_services = $service_bundle['services'];
$total_duration = $service_bundle['duration'];
$total_price = $service_bundle['price'];
if(empty($selected_services) || $total_duration <= 0) api_error('Servicios inválidos', 400);

$appointments = db_read('appointments', []);
if(!is_bookable_slot($date, $time, $manicurist_id, $total_duration)) {
  api_error('Ese horario ya no está disponible. Elige otro horario.', 409);
}
$new_app = [
  'id' => uniqid('app_'),
  'client_id' => $user['type'] === 'client' ? $user['id'] : ($req['client_id'] ?? ''),
  'manicurist_id' => $manicurist_id,
  'date' => $date,
  'time' => $time,
  'duration' => $total_duration,
  'services' => $selected_services,
  'price' => $total_price,
  'status' => 'pendiente_confirmacion_wa',
  'created_at' => date('Y-m-d H:i:s')
];

$appointments[] = $new_app;
db_write('appointments', $appointments);

api_response(['success' => true, 'appointment' => $new_app]);
?>
