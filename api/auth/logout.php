<?php
require_once __DIR__ . '/../../core/auth.php';

require_csrf();
logout_user();
api_response(['success' => true]);
?>
