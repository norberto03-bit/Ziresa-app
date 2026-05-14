<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$user = require_auth(['admin1', 'admin2', 'manicurist', 'client']);
$date = $_GET['date'] ?? '';
$appointments = db_read('appointments', []);
$clients = db_read('clients', []);
$manicurists = db_read('manicurists', []);

$items = [];
foreach($appointments as $app) {
  if($date && ($app['date'] ?? '') !== $date) continue;
  if($user['type'] === 'manicurist' && ($app['manicurist_id'] ?? '') !== $user['id']) continue;
  if($user['type'] === 'client' && ($app['client_id'] ?? '') !== $user['id']) continue;

  $client = find_by_id($clients, $app['client_id'] ?? '');
  $man = find_by_id($manicurists, $app['manicurist_id'] ?? '');
  $app['client_name'] = $client['name'] ?? 'Clienta';
  $app['client_phone'] = $client['phone'] ?? '';
  $app['manicurist_name'] = $man['name'] ?? 'Manicurista';
  $items[] = $app;
}

usort($items, function($a, $b) {
  return strcmp(($a['date'] ?? '') . ' ' . ($a['time'] ?? ''), ($b['date'] ?? '') . ' ' . ($b['time'] ?? ''));
});

api_response(['success' => true, 'appointments' => $items]);
?>
