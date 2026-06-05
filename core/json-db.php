<?php
function db_path($name){
  $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
  return __DIR__ . '/../data/' . $safe . '.json';
}

function db_backup_path($name){
  $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
  return __DIR__ . '/../data/_backups/' . date('Y-m') . '/' . $safe;
}

function db_backup_max(){
  $env = getenv('ZIRESA_BACKUP_MAX_PER_FILE');
  $max = $env !== false ? intval($env) : 25;
  return max(5, min(200, $max));
}

function db_rotate_backups($backup_dir){
  if(!is_dir($backup_dir)) return;
  $files = glob($backup_dir . '/*.json');
  if(!$files || count($files) <= db_backup_max()) return;
  usort($files, function($a, $b) {
    return filemtime($a) <=> filemtime($b);
  });
  $remove_count = count($files) - db_backup_max();
  for($i = 0; $i < $remove_count; $i++) {
    @unlink($files[$i]);
  }
}

function db_read($name, $default = []){
  $path = db_path($name);
  if(!file_exists($path)) return $default;
  $raw = file_get_contents($path);
  if($raw === false || trim($raw) === '') return $default;
  $data = json_decode($raw, true);
  if(json_last_error() !== JSON_ERROR_NONE || !is_array($data)){
    // api_error() will be in auth.php
    if(function_exists('api_error')) {
      api_error($name . '.json inválido', 500, ['json_error'=>json_last_error_msg()]);
    }
    return $default;
  }
  return $data;
}

function db_write($name, $data){
  $path = db_path($name);
  $dir = dirname($path);
  if(!is_dir($dir)) mkdir($dir, 0775, true);

  $lock_path = $path . '.lock';
  $lock = fopen($lock_path, 'c');
  if(!$lock) {
    if(function_exists('api_error')) api_error('No se pudo abrir el lock de base de datos JSON', 500);
    return false;
  }

  if(!flock($lock, LOCK_EX)) {
    fclose($lock);
    if(function_exists('api_error')) api_error('No se pudo bloquear la base de datos JSON', 500);
    return false;
  }

  $encoded = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
  if($encoded === false) {
    flock($lock, LOCK_UN);
    fclose($lock);
    if(function_exists('api_error')) api_error('No se pudo serializar la base de datos JSON', 500);
    return false;
  }

  if(file_exists($path)) {
    $backup_dir = db_backup_path($name);
    if(!is_dir($backup_dir)) mkdir($backup_dir, 0775, true);
    $backup_file = $backup_dir . '/' . date('Ymd-His') . '-' . basename($path);
    @copy($path, $backup_file);
    db_rotate_backups($backup_dir);
  }

  $tmp = $path . '.' . uniqid('tmp_', true);
  if(file_put_contents($tmp, $encoded, LOCK_EX) === false) {
    flock($lock, LOCK_UN);
    fclose($lock);
    if(function_exists('api_error')) api_error('No se pudo escribir la base de datos JSON', 500);
    return false;
  }
  if(!rename($tmp, $path)) {
    @unlink($tmp);
    flock($lock, LOCK_UN);
    fclose($lock);
    if(function_exists('api_error')) api_error('No se pudo guardar la base de datos JSON', 500);
    return false;
  }
  @chmod($path, 0664);
  flock($lock, LOCK_UN);
  fclose($lock);
  return true;
}

function normalize_phone($phone){
  return preg_replace('/\D+/', '', (string)$phone);
}

function is_valid_date_string($date){
  $dt = DateTime::createFromFormat('Y-m-d', (string)$date);
  return $dt && $dt->format('Y-m-d') === $date;
}

function is_valid_time_string($time){
  return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string)$time) === 1;
}

function find_by_id($items, $id){
  foreach($items as $item) {
    if(($item['id'] ?? '') === $id) return $item;
  }
  return null;
}

function public_user($user){
  if(!is_array($user)) return $user;
  unset($user['pin'], $user['pin_hash'], $user['password'], $user['password_hash']);
  return $user;
}

function verify_secret($plain, $stored){
  if($stored === null || $stored === '') return false;
  if(strpos((string)$stored, 'sha256$') === 0) {
    $expected = substr((string)$stored, 7);
    return hash_equals($expected, hash('sha256', (string)$plain));
  }
  $info = password_get_info((string)$stored);
  if(($info['algo'] ?? 0) !== 0) return password_verify((string)$plain, (string)$stored);
  return hash_equals((string)$stored, (string)$plain);
}

function hash_secret($plain){
  return password_hash((string)$plain, PASSWORD_DEFAULT);
}

function request_json(){
  $raw = file_get_contents('php://input');
  if(trim($raw) === '') return [];
  $data = json_decode($raw, true);
  if(json_last_error() !== JSON_ERROR_NONE || !is_array($data)){
    if(function_exists('api_error')) api_error('Body JSON inválido', 400);
    return [];
  }
  return $data;
}
?>
