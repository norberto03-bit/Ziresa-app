<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/loyalty.php';

$user = require_auth(['admin1', 'admin2', 'manicurist']);
$req = request_json();
require_csrf($req);

$appointment_id = $req['appointment_id'] ?? '';
$status = $req['status'] ?? '';
$allowed = ['pendiente_confirmacion_wa', 'confirmada', 'en_proceso', 'completada', 'cancelada'];

if(!$appointment_id) api_error('Falta appointment_id', 400);
if(!in_array($status, $allowed, true)) api_error('Estado invalido', 400);

if($user['type'] === 'manicurist' && !in_array($status, ['confirmada', 'en_proceso', 'completada'], true)) {
  api_error('No tienes permiso para ese cambio de estado', 403);
}

$appointments = db_read('appointments', []);
$found = false;
$updated_app = null;

foreach($appointments as &$app) {
  if(($app['id'] ?? '') !== $appointment_id) continue;
  if($user['type'] === 'manicurist' && ($app['manicurist_id'] ?? '') !== $user['id']) {
    api_error('Esta cita no esta asignada a tu perfil', 403);
  }
  $app['status'] = $status;
  $app['updated_at'] = date('Y-m-d H:i:s');
  if($status === 'confirmada') $app['confirmed_by'] = $user['id'];
  if($status === 'completada') $app['completed_by'] = $user['id'];
  $updated_app = $app;
  $found = true;
  break;
}

if(!$found) api_error('Cita no encontrada', 404);
db_write('appointments', $appointments);

$completion = null;
if($status === 'completada') {
  $completion = ziresa_apply_completion_effects($appointment_id, $user['id']);
  if(!($completion['success'] ?? false)) api_error($completion['error'] ?? 'No se pudo completar la cita', 500);
}

api_response(['success' => true, 'appointment' => $updated_app, 'completion' => $completion]);
?>
