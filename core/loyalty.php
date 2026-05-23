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
?>
