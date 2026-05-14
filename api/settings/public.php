<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

$settings = db_read('settings', []);
$brand = $settings['brand'] ?? [];

api_response([
  'success' => true,
  'business_name' => $settings['business_name'] ?? 'Ziresa Studio',
  'public_app_url' => $settings['public_app_url'] ?? '',
  'business_whatsapp' => $settings['business_whatsapp'] ?? '',
  'brand' => [
    'name' => $brand['name'] ?? 'Ziresa Studio',
    'promise' => $brand['promise'] ?? '',
    'location' => $brand['location'] ?? '',
    'values' => $brand['values'] ?? []
  ],
  'media' => $settings['media'] ?? []
]);
?>
