<?php
function get_schedule_for_date($settings, $date){
  $custom = $settings['custom_schedules'][$date] ?? null;
  if(is_array($custom)) return $custom;
  $day_of_week = date('w', strtotime($date));
  return $settings['default_schedule'][(string)$day_of_week] ?? $settings['default_schedule'][$day_of_week] ?? [];
}

function ranges_overlap($start_a, $end_a, $start_b, $end_b){
  return $start_a < $end_b && $end_a > $start_b;
}

function is_manicurist_available($appointments, $manicurist_id, $date, $time, $duration, $ignore_id = ''){
  $time_start = strtotime("$date $time");
  $time_end = $time_start + (intval($duration) * 60);
  foreach($appointments as $app) {
    if(($app['id'] ?? '') === $ignore_id) continue;
    if(($app['date'] ?? '') !== $date) continue;
    if(($app['status'] ?? '') === 'cancelada') continue;
    if(($app['manicurist_id'] ?? '') !== $manicurist_id) continue;
    $app_start = strtotime("$date {$app['time']}");
    $app_end = $app_start + (intval($app['duration'] ?? 60) * 60);
    if(ranges_overlap($time_start, $time_end, $app_start, $app_end)) return false;
  }
  return true;
}

function get_available_slots($date, $duration){
  $settings = db_read('settings');
  $appointments = db_read('appointments', []);
  $manicurists = db_read('manicurists', []);
  $hours = get_schedule_for_date($settings, $date);
  $available_slots = [];

  foreach($hours as $hour) {
    if(!is_valid_time_string($hour)) continue;
    foreach($manicurists as $man) {
      if(!($man['active'] ?? true)) continue;
      if(is_manicurist_available($appointments, $man['id'], $date, $hour, $duration)) {
        $available_slots[] = [
          'time' => $hour,
          'manicurist_id' => $man['id'],
          'manicurist_name' => $man['name'] ?? 'Manicurista'
        ];
        break;
      }
    }
  }

  return $available_slots;
}

function is_bookable_slot($date, $time, $manicurist_id, $duration){
  $settings = db_read('settings');
  $hours = get_schedule_for_date($settings, $date);
  if(!in_array($time, $hours)) return false;
  $manicurists = db_read('manicurists', []);
  $man = find_by_id($manicurists, $manicurist_id);
  if(!$man || !($man['active'] ?? true)) return false;
  $appointments = db_read('appointments', []);
  return is_manicurist_available($appointments, $manicurist_id, $date, $time, $duration);
}

function build_selected_services($service_ids){
  $services = db_read('services', []);
  $total_duration = 0;
  $total_price = 0;
  $selected_services = [];

  foreach($service_ids as $sid) {
    $service = find_by_id($services, $sid);
    if(!$service) continue;
    $total_duration += intval($service['duration_minutes'] ?? 0);
    $total_price += floatval($service['price'] ?? 0);
    $selected_services[] = $service;
  }

  return [
    'services' => $selected_services,
    'duration' => $total_duration,
    'price' => $total_price
  ];
}
?>
