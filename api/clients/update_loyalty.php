<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/loyalty.php';

require_auth(['admin1', 'admin2']);
$req = request_json();
require_csrf($req);

$client_id = $req['client_id'] ?? '';
$visits = intval($req['visits'] ?? 0);
$points = intval($req['points'] ?? ($visits * 10));
$note = trim($req['note'] ?? '');

if($client_id === '') api_error('Falta client_id', 400);
if($visits < 0 || $visits > 999) api_error('Numero de visitas invalido', 400);
if($points < 0 || $points > 999999) api_error('Puntos invalidos', 400);

$clients = db_read('clients', []);
$updated = null;

foreach($clients as &$client) {
  if(($client['id'] ?? '') === $client_id) {
    $client['visits'] = $visits;
    $client['points'] = $points;
    $client['loyalty_note'] = $note;
    $client['loyalty_updated_at'] = date('Y-m-d H:i:s');
    $updated = $client;
    break;
  }
}

if(!$updated) api_error('Cliente no encontrado', 404);
db_write('clients', $clients);

api_response([
  'success' => true,
  'client' => public_user($updated),
  'wallet' => ziresa_loyalty_wallet($updated)
]);
?>
