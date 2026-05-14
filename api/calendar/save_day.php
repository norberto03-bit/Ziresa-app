<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$user = require_auth(['admin1']);
$req = request_json();

$date = $req['date'] ?? ''; // e.g., '2026-05-20'
$hours = $req['hours'] ?? []; // array of strings like ["10:00", "11:00", "12:00"]

if(!$date) api_error('Falta fecha', 400);
if(!is_valid_date_string($date)) api_error('Fecha inválida', 400);
foreach($hours as $hour) {
  if(!is_valid_time_string($hour)) api_error('Horario inválido: ' . $hour, 400);
}

$settings = db_read('settings', []);
if(!isset($settings['custom_schedules'])) {
  $settings['custom_schedules'] = [];
}

// Save or remove custom schedule
if(empty($hours)) {
  unset($settings['custom_schedules'][$date]);
} else {
  $settings['custom_schedules'][$date] = $hours;
}

db_write('settings', $settings);

api_response(['success' => true, 'custom_schedules' => $settings['custom_schedules']]);
?>
