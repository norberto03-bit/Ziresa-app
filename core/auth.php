<?php
function api_response($data, $code = 200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function api_error($message, $code = 400, $extra = []){
  $data = ['error' => $message];
  if(!empty($extra)) $data = array_merge($data, $extra);
  api_response($data, $code);
}

function start_session_if_needed() {
  if(session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
    session_start();
  }
}

function login_user($type, $id) {
  start_session_if_needed();
  session_regenerate_id(true);
  $_SESSION['ziresa_user_type'] = $type; // 'admin1', 'admin2', 'manicurist', 'client'
  $_SESSION['ziresa_user_id'] = $id;
  csrf_token();
  return true;
}

function logout_user() {
  start_session_if_needed();
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}

function is_logged_in() {
  start_session_if_needed();
  return !empty($_SESSION['ziresa_user_type']);
}

function get_logged_user() {
  start_session_if_needed();
  if (!is_logged_in()) return null;
  return [
    'type' => $_SESSION['ziresa_user_type'],
    'id' => $_SESSION['ziresa_user_id']
  ];
}

function require_auth($allowed_types = []) {
  $user = get_logged_user();
  if (!$user) {
    api_error('No autorizado. Inicia sesión.', 401);
  }
  if (!empty($allowed_types) && !in_array($user['type'], $allowed_types)) {
    api_error('No tienes permisos para esta acción.', 403);
  }
  return $user;
}

function csrf_token() {
  start_session_if_needed();
  if(empty($_SESSION['ziresa_csrf_token'])) {
    $_SESSION['ziresa_csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['ziresa_csrf_token'];
}

function request_csrf_token($req = null) {
  $headers = function_exists('getallheaders') ? getallheaders() : [];
  foreach($headers as $name => $value) {
    if(strtolower($name) === 'x-csrf-token') return (string)$value;
  }
  if(is_array($req) && isset($req['_csrf'])) return (string)$req['_csrf'];
  return '';
}

function require_csrf($req = null) {
  $expected = csrf_token();
  $provided = request_csrf_token($req);
  if(!$provided || !hash_equals($expected, $provided)) {
    api_error('Token CSRF invalido o ausente.', 403);
  }
}

function current_session_payload() {
  $token = csrf_token();
  $user = get_logged_user();
  if(!$user) return ['authenticated' => false, 'csrf_token' => $token];
  return ['authenticated' => true, 'type' => $user['type'], 'id' => $user['id'], 'csrf_token' => $token];
}
?>
