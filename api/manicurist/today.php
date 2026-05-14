<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$user = require_auth(['manicurist']);
$today = $_GET['date'] ?? date('Y-m-d');
$appointments = db_read('appointments', []);
$clients = db_read('clients', []);
$finances = db_read('finances', []);

$my_apps = [];
foreach($appointments as $app) {
  if(($app['date'] ?? '') !== $today) continue;
  if(($app['manicurist_id'] ?? '') !== $user['id']) continue;
  if(($app['status'] ?? '') === 'cancelada') continue;
  $client = find_by_id($clients, $app['client_id'] ?? '');
  $app['client_name'] = $client['name'] ?? 'Clienta';
  $app['client_phone'] = $client['phone'] ?? '';
  $my_apps[] = $app;
}

usort($my_apps, function($a, $b) {
  return strcmp($a['time'] ?? '', $b['time'] ?? '');
});

$tips = 0;
foreach($finances as $fin) {
  if(($fin['date'] ?? '') === $today && ($fin['manicurist_id'] ?? '') === $user['id'] && ($fin['type'] ?? '') === 'propina') {
    $tips += floatval($fin['amount'] ?? 0);
  }
}

api_response(['success' => true, 'date' => $today, 'appointments' => $my_apps, 'tips_today' => $tips]);
?>
