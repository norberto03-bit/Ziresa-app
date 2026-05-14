<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/appointments.php';

$date = $_GET['date'] ?? date('Y-m-d');
$duration = intval($_GET['duration'] ?? 60);

if(!is_valid_date_string($date)) api_error('Fecha inválida', 400);
if($duration <= 0 || $duration > 480) api_error('Duración inválida', 400);

$available_slots = get_available_slots($date, $duration);

api_response(['date' => $date, 'slots' => $available_slots]);
?>
