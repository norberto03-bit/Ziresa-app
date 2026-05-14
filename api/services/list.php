<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

// Public endpoint, or just require basic auth.
$services = db_read('services', []);
api_response(['services' => $services]);
?>
