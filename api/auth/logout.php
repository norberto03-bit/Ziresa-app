<?php
require_once __DIR__ . '/../../core/auth.php';

logout_user();
api_response(['success' => true]);
?>
