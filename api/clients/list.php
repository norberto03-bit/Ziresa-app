<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

require_auth(['admin1', 'admin2']);

$clients = array_map('public_user', db_read('clients', []));
usort($clients, function($a, $b) {
  return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

api_response(['success' => true, 'clients' => $clients]);
?>
