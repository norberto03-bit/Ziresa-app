<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

// Endpoint called when an appointment is paid/completed
$user = require_auth(['admin1', 'admin2']);
$req = request_json();
require_csrf($req);
$appointment_id = $req['appointment_id'] ?? '';

if(!$appointment_id) api_error('Falta ID de cita', 400);

$appointments = db_read('appointments', []);
$clients = db_read('clients', []);
$client_id = null;
$app_found = false;

foreach($appointments as &$app) {
  if($app['id'] === $appointment_id) {
    if(($app['loyalty_processed'] ?? false)) api_error('Ya se procesó la lealtad para esta cita', 400);
    $app['loyalty_processed'] = true;
    $client_id = $app['client_id'];
    $app_found = true;
    break;
  }
}

if(!$app_found) api_error('Cita no encontrada', 404);

$response_msg = "Visita registrada.";
foreach($clients as &$c) {
  if($c['id'] === $client_id) {
    $c['visits'] = ($c['visits'] ?? 0) + 1;
    $c['points'] = ($c['points'] ?? 0) + 10; // Basic points
    
    if($c['visits'] === 5) {
      $response_msg = "¡Visita #5! Desbloqueó 5% de descuento automático.";
      $c['discounts'][] = ['type' => '5_off', 'used' => false];
    } else if($c['visits'] === 10) {
      $response_msg = "¡Visita #10! Desbloqueó 10% de descuento.";
      $c['discounts'][] = ['type' => '10_off', 'used' => false];
    }
    break;
  }
}

db_write('appointments', $appointments);
db_write('clients', $clients);

api_response(['success' => true, 'message' => $response_msg]);
?>
