<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

require_auth(['admin1', 'admin2']);

$today = date('Y-m-d');
$appointments = db_read('appointments', []);
$clients = db_read('clients', []);
$finances = db_read('finances', []);

$today_apps = array_values(array_filter($appointments, function($app) use ($today) {
  return ($app['date'] ?? '') === $today && ($app['status'] ?? '') !== 'cancelada';
}));

$projected = 0;
foreach($today_apps as $app) $projected += floatval($app['price'] ?? 0);

$new_clients = 0;
foreach($clients as $client) {
  if(substr($client['created_at'] ?? '', 0, 10) === $today) $new_clients++;
}

$income_today = 0;
foreach($finances as $fin) {
  if(($fin['date'] ?? '') === $today && ($fin['type'] ?? '') === 'ingreso') {
    $income_today += floatval($fin['amount'] ?? 0);
  }
}

api_response([
  'success' => true,
  'today' => $today,
  'appointments_today' => count($today_apps),
  'projected_income' => $projected,
  'income_today' => $income_today,
  'new_clients_today' => $new_clients,
  'pending_confirmation' => count(array_filter($appointments, function($app) {
    return ($app['status'] ?? '') === 'pendiente_confirmacion_wa';
  }))
]);
?>
