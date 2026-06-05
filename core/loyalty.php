<?php
function ziresa_loyalty_wallet($client){
  $visits = max(0, intval($client['visits'] ?? 0));
  $points = max(0, intval($client['points'] ?? 0));
  $discount = 0;
  if($visits >= 10) {
    $discount = 10;
  } elseif($visits >= 5) {
    $discount = 5;
  }

  $next_goal = $visits >= 10 ? 10 : ($visits >= 5 ? 10 : 5);
  $progress_to_goal = $next_goal > 0 ? min(100, round(($visits / $next_goal) * 100)) : 100;
  $stamps = [];
  for($i = 1; $i <= 10; $i++) {
    $stamps[] = [
      'number' => $i,
      'reached' => $i <= $visits,
      'emoji' => $i <= $visits ? ($i === 5 || $i === 10 ? '💎' : '💅') : '♡'
    ];
  }

  return [
    'client_id' => $client['id'] ?? '',
    'name' => $client['name'] ?? 'Clienta Ziresa',
    'phone' => $client['phone'] ?? '',
    'visits' => $visits,
    'points' => $points,
    'discount_percent' => $discount,
    'score' => min(100, ($visits * 8) + min(20, floor($points / 25))),
    'next_goal' => $next_goal,
    'visits_to_next_goal' => max(0, $next_goal - $visits),
    'progress_percent' => $progress_to_goal,
    'stamps' => $stamps,
    'benefit_label' => $discount > 0 ? $discount . '% descuento activo' : 'Sigue acumulando visitas',
    'updated_at' => date('Y-m-d H:i:s')
  ];
}

function ziresa_points_for_appointment($appointment){
  $price = floatval($appointment['price'] ?? 0);
  if($price > 0) return max(1, intval(floor($price / 100)));

  $points = 0;
  foreach(($appointment['services'] ?? []) as $service) {
    $points += intval($service['points_reward'] ?? 0);
  }
  return max(1, $points);
}

function ziresa_apply_completion_effects($appointment_id, $actor_id = ''){
  $appointments = db_read('appointments', []);
  $clients = db_read('clients', []);
  $finances = db_read('finances', []);

  $appointment_index = null;
  $appointment = null;
  foreach($appointments as $idx => $app) {
    if(($app['id'] ?? '') === $appointment_id) {
      $appointment_index = $idx;
      $appointment = $app;
      break;
    }
  }

  if($appointment_index === null || !$appointment) {
    return ['success' => false, 'error' => 'Cita no encontrada'];
  }

  $client_id = $appointment['client_id'] ?? '';
  if(!$client_id) {
    return ['success' => false, 'error' => 'La cita no tiene clienta asignada'];
  }

  if(!($appointments[$appointment_index]['loyalty_processed'] ?? false)) {
    $points = ziresa_points_for_appointment($appointment);
    foreach($clients as &$client) {
      if(($client['id'] ?? '') === $client_id) {
        $client['visits'] = intval($client['visits'] ?? 0) + 1;
        $client['points'] = intval($client['points'] ?? 0) + $points;
        $client['loyalty_updated_at'] = date('Y-m-d H:i:s');
        if($client['visits'] === 5) $client['discounts'][] = ['type' => '5_off', 'used' => false];
        if($client['visits'] === 10) $client['discounts'][] = ['type' => '10_off', 'used' => false];
        break;
      }
    }
    $appointments[$appointment_index]['loyalty_processed'] = true;
  }

  $has_income = false;
  foreach($finances as $fin) {
    if(($fin['appointment_id'] ?? '') === $appointment_id && ($fin['type'] ?? '') === 'ingreso') {
      $has_income = true;
      break;
    }
  }

  if(!$has_income) {
    $finances[] = [
      'id' => uniqid('fin_'),
      'type' => 'ingreso',
      'amount' => floatval($appointment['price'] ?? 0),
      'method' => 'efectivo',
      'description' => 'Ingreso por cita completada',
      'appointment_id' => $appointment_id,
      'manicurist_id' => $appointment['manicurist_id'] ?? '',
      'date' => date('Y-m-d'),
      'created_at' => date('Y-m-d H:i:s'),
      'created_by' => $actor_id
    ];
  }

  db_write('appointments', $appointments);
  db_write('clients', $clients);
  db_write('finances', $finances);

  return ['success' => true];
}
?>
