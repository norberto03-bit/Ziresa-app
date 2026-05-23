<?php
require_once __DIR__ . '/../../core/json-db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/loyalty.php';

$user = require_auth(['client']);
$clients = db_read('clients', []);
$client = null;
foreach($clients as $item) {
  if(($item['id'] ?? '') === $user['id']) {
    $client = $item;
    break;
  }
}
if(!$client) api_error('Cliente no encontrado', 404);

$wallet = ziresa_loyalty_wallet($client);
$format = $_GET['format'] ?? 'html';

if($format === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="ziresa-wallet-' . preg_replace('/[^a-z0-9\-]/i', '', $wallet['client_id']) . '.json"');
  echo json_encode([
    'wallet_type' => 'ziresa_loyalty_pass',
    'brand' => 'Ziresa Studio',
    'pass' => $wallet
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
  exit;
}

$safe_name = htmlspecialchars($wallet['name'], ENT_QUOTES, 'UTF-8');
$stamps = '';
foreach($wallet['stamps'] as $stamp) {
  $class = $stamp['reached'] ? 'stamp reached' : 'stamp';
  $stamps .= '<span class="' . $class . '">' . htmlspecialchars($stamp['emoji'], ENT_QUOTES, 'UTF-8') . '<b>' . intval($stamp['number']) . '</b></span>';
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="ziresa-wallet-' . preg_replace('/[^a-z0-9\-]/i', '', $wallet['client_id']) . '.html"');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wallet Ziresa</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:#fce4ec;font-family:Arial,sans-serif;color:#2d0a31}
    .pass{width:min(390px,calc(100vw - 28px));border-radius:30px;padding:28px;background:radial-gradient(circle at 10% 0%,rgba(255,255,255,.7),transparent 36%),linear-gradient(145deg,#5c1a70,#ff4081);color:white;box-shadow:0 24px 60px rgba(92,26,112,.28);position:relative;overflow:hidden}
    .pass:before{content:"";position:absolute;inset:14px;border:1px solid rgba(255,255,255,.28);border-radius:24px;pointer-events:none}
    .brand{font-family:Georgia,serif;font-size:34px;margin:0 0 4px}
    .label{text-transform:uppercase;font-size:12px;font-weight:800;opacity:.78;letter-spacing:.08em}
    .name{font-size:26px;font-weight:900;margin:18px 0 6px}
    .metric{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:18px 0}
    .box{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:18px;padding:14px}
    .box strong{font-size:28px;display:block}
    .progress{height:14px;background:rgba(255,255,255,.18);border-radius:99px;overflow:hidden;margin:16px 0}
    .bar{height:100%;width:<?php echo intval($wallet['progress_percent']); ?>%;background:#fff;border-radius:99px}
    .stamps{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:18px}
    .stamp{height:46px;border-radius:16px;display:grid;place-items:center;background:rgba(255,255,255,.12);border:1px dashed rgba(255,255,255,.32);position:relative;font-size:22px}
    .stamp b{position:absolute;right:6px;bottom:3px;font-size:10px;opacity:.8}
    .stamp.reached{background:white;color:#5c1a70;border-style:solid;box-shadow:0 8px 18px rgba(255,255,255,.18)}
    .footer{margin-top:20px;font-size:13px;opacity:.86}
  </style>
</head>
<body>
  <main class="pass">
    <p class="brand">Ziresa Studio</p>
    <div class="label">Tarjeta Cliente Frecuente</div>
    <div class="name"><?php echo $safe_name; ?></div>
    <div><?php echo htmlspecialchars($wallet['benefit_label'], ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="metric">
      <div class="box"><span class="label">Visitas</span><strong><?php echo intval($wallet['visits']); ?></strong></div>
      <div class="box"><span class="label">Score</span><strong><?php echo intval($wallet['score']); ?></strong></div>
    </div>
    <div class="progress"><div class="bar"></div></div>
    <div class="stamps"><?php echo $stamps; ?></div>
    <div class="footer">Actualizada: <?php echo htmlspecialchars($wallet['updated_at'], ENT_QUOTES, 'UTF-8'); ?> · reserva tu siguiente cita desde app.ziresa.mx</div>
  </main>
</body>
</html>
