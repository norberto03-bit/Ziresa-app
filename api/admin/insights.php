<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

require_auth(['admin1', 'admin2']);

$clients = db_read('clients', []);
$appointments = db_read('appointments', []);
$now = time();
$cadence_days = [
  'gelish' => 18,
  'acril' => 21,
  'pesta' => 42,
  'ceja' => 35,
  'default' => 28
];

function service_cadence_days($services, $cadence_days) {
  $names = strtolower(implode(' ', array_map(function($s) { return $s['name'] ?? ''; }, $services)));
  if(strpos($names, 'pesta') !== false || strpos($names, 'lifting') !== false) return $cadence_days['pesta'];
  if(strpos($names, 'ceja') !== false || strpos($names, 'laminado') !== false) return $cadence_days['ceja'];
  if(strpos($names, 'gelish') !== false) return $cadence_days['gelish'];
  if(strpos($names, 'acril') !== false) return $cadence_days['acril'];
  return $cadence_days['default'];
}

$insights = [];
foreach($clients as $client) {
  $client_apps = array_values(array_filter($appointments, function($app) use ($client) {
    return ($app['client_id'] ?? '') === ($client['id'] ?? '') && ($app['status'] ?? '') !== 'cancelada';
  }));
  usort($client_apps, function($a, $b) {
    return strcmp(($b['date'] ?? '') . ' ' . ($b['time'] ?? ''), ($a['date'] ?? '') . ' ' . ($a['time'] ?? ''));
  });
  $last = null;
  foreach($client_apps as $app) {
    if(strtotime(($app['date'] ?? '') . ' ' . ($app['time'] ?? '00:00')) <= $now) {
      $last = $app;
      break;
    }
  }
  if(!$last) continue;
  $last_ts = strtotime(($last['date'] ?? '') . ' ' . ($last['time'] ?? '00:00'));
  $days_since = floor(($now - $last_ts) / 86400);
  $cadence = service_cadence_days($last['services'] ?? [], $cadence_days);
  $due_in = $cadence - $days_since;
  $service_names = implode(', ', array_map(function($s) { return $s['name'] ?? ''; }, $last['services'] ?? []));
  $status = $due_in < -7 ? 'dormida' : ($due_in <= 5 ? 'lista_para_retoque' : 'en_ritmo');
  $message = "Hola " . ($client['name'] ?? 'hermosa') . ", en Ziresa ya te toca cuidar tu " . ($service_names ?: 'estilo') . ". Te aparto un horario esta semana?";
  $insights[] = [
    'client_id' => $client['id'] ?? '',
    'name' => $client['name'] ?? 'Clienta',
    'phone' => $client['phone'] ?? '',
    'last_service' => $service_names,
    'last_date' => $last['date'] ?? '',
    'days_since' => $days_since,
    'due_in' => $due_in,
    'status' => $status,
    'message' => $message
  ];
}

usort($insights, function($a, $b) {
  return ($a['due_in'] ?? 999) <=> ($b['due_in'] ?? 999);
});

api_response([
  'success' => true,
  'summary' => [
    'dormidas' => count(array_filter($insights, function($i) { return $i['status'] === 'dormida'; })),
    'listas_para_retoque' => count(array_filter($insights, function($i) { return $i['status'] === 'lista_para_retoque'; })),
    'en_ritmo' => count(array_filter($insights, function($i) { return $i['status'] === 'en_ritmo'; }))
  ],
  'insights' => $insights
]);
?>
