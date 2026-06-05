<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/loyalty.php';

$user = require_auth(['client']);
$client_id = $user['id'];

$clients = db_read('clients', []);
$appointments = db_read('appointments', []);
$my_client = null;

foreach($clients as $c) {
  if($c['id'] === $client_id) {
    $my_client = $c;
    break;
  }
}

if(!$my_client) api_error('Cliente no encontrado', 404);

// Get my appointments
$my_appointments = [];
$next_appointment = null;
$past_visits = [];
$services_used = [];

$now = time();

foreach($appointments as $app) {
  if($app['client_id'] === $client_id) {
    $my_appointments[] = $app;
    
    // Count services for preference
    foreach($app['services'] as $s) {
      if(!isset($services_used[$s['name']])) $services_used[$s['name']] = 0;
      $services_used[$s['name']]++;
    }
    
    $app_time = strtotime($app['date'] . ' ' . $app['time']);
    if($app_time > $now) {
      if(!$next_appointment || $app_time < strtotime($next_appointment['date'] . ' ' . $next_appointment['time'])) {
        $next_appointment = $app;
      }
    } else {
      if($app['status'] === 'completada') {
        $past_visits[] = $app;
      }
    }
  }
}

// Sort most used services
arsort($services_used);
$favorite_services = array_keys($services_used);

$gallery = $my_client['gallery'] ?? [];

api_response([
  'success' => true,
  'client' => $my_client,
  'wallet' => ziresa_loyalty_wallet($my_client),
  'next_appointment' => $next_appointment,
  'favorite_services' => $favorite_services,
  'gallery' => $gallery,
  'visits_count' => count($past_visits)
]);
?>
