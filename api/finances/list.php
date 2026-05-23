<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';

require_auth(['admin1', 'admin2']);

$records = db_read('finances', []);
$income = 0;
$expenses = 0;
$tips = 0;

foreach($records as $record) {
  $amount = floatval($record['amount'] ?? 0);
  $type = $record['type'] ?? 'ingreso';
  if($type === 'egreso') {
    $expenses += $amount;
  } elseif($type === 'propina') {
    $tips += $amount;
    $income += $amount;
  } else {
    $income += $amount;
  }
}

usort($records, function($a, $b) {
  return strcmp(($b['created_at'] ?? ''), ($a['created_at'] ?? ''));
});

api_response([
  'success' => true,
  'summary' => [
    'income' => $income,
    'expenses' => $expenses,
    'tips' => $tips,
    'profit' => $income - $expenses
  ],
  'records' => array_slice($records, 0, 60)
]);
?>
