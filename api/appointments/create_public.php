<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/appointments.php';

$req = request_json();

$date = $req['date'] ?? '';
$time = $req['time'] ?? '';
$services_ids = $req['services'] ?? [];
$manicurist_id = $req['manicurist_id'] ?? '';
$client_name = trim($req['client_name'] ?? '');
$client_phone = trim($req['client_phone'] ?? '');
$client_pin = trim($req['client_pin'] ?? '');

if(!$date || !$time || empty($services_ids) || !$manicurist_id || !$client_name || !$client_phone || !$client_pin) {
  api_error('Faltan datos para crear la cita', 400);
}
if(!is_valid_date_string($date)) api_error('Fecha inválida', 400);
if(!is_valid_time_string($time)) api_error('Hora inválida', 400);
if(strlen($client_pin) < 4) api_error('El PIN debe tener al menos 4 caracteres', 400);
$client_phone = normalize_phone($client_phone);
if(strlen($client_phone) < 10) api_error('WhatsApp inválido', 400);

// 1. Validate services and slot before creating/updating client data.
$service_bundle = build_selected_services($services_ids);
$selected_services = $service_bundle['services'];
$total_duration = $service_bundle['duration'];
$total_price = $service_bundle['price'];
if(empty($selected_services) || $total_duration <= 0) api_error('Servicios inválidos', 400);

if(!is_bookable_slot($date, $time, $manicurist_id, $total_duration)) {
  api_error('Ese horario ya no está disponible. Elige otro horario.', 409);
}

// 2. Process Client (Find or Create)
$clients = db_read('clients', []);
$client_id = null;
$client_found = false;

foreach($clients as &$c) {
  if(normalize_phone($c['phone'] ?? '') === $client_phone) {
    $client_id = $c['id'];
    $c['name'] = $client_name;
    if(empty($c['pin_hash']) && empty($c['pin'])) $c['pin_hash'] = hash_secret($client_pin);
    $client_found = true;
    break;
  }
}

if(!$client_found) {
  $client_id = uniqid('cli_');
  $clients[] = [
    'id' => $client_id,
    'phone' => $client_phone,
    'pin_hash' => hash_secret($client_pin),
    'name' => $client_name,
    'points' => 0,
    'visits' => 0,
    'created_at' => date('Y-m-d H:i:s')
  ];
}
db_write('clients', $clients);

// 3. Process Appointment
$appointments = db_read('appointments', []);
$new_app = [
  'id' => uniqid('app_'),
  'client_id' => $client_id,
  'manicurist_id' => $manicurist_id,
  'date' => $date,
  'time' => $time,
  'duration' => $total_duration,
  'services' => $selected_services,
  'price' => $total_price,
  'status' => 'pendiente_confirmacion_wa', // Ziresa will confirm
  'created_at' => date('Y-m-d H:i:s')
];

$appointments[] = $new_app;
db_write('appointments', $appointments);

api_response(['success' => true, 'appointment' => $new_app]);
?>
