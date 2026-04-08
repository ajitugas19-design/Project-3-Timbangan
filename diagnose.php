<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head><title>Diagnostics</title><style>body{font-family:monospace;padding:20px;}pre{background:#f4f4f4;padding:15px;border-radius:5px;}</style></head>
<body>
<h1>🛠️ Diagnostics - Loading Issue</h1>

<h2>1. Session</h2>
<pre><?php
echo "isLoggedIn(): " . (isLoggedIn() ? '✅ YES (user_id=' . ($_SESSION['user_id'] ?? 'N/A') . ')' : '❌ NO') . "\n";
echo "Session vars:\n";
print_r($_SESSION ?? []);
?></pre>

<h2>2. DB Connection</h2>
<pre><?php
try {
  $stmt = $pdo->query("SHOW TABLES LIKE 'kendaraan'");
  $hasKendaraan = $stmt->rowCount() > 0;
  echo "Table 'kendaraan': " . ($hasKendaraan ? '✅ EXISTS' : '❌ MISSING') . "\n";
  
  $stmt = $pdo->query("SHOW TABLES LIKE 'transaksi'");
  $hasTransaksi = $stmt->rowCount() > 0;
  echo "Table 'transaksi': " . ($hasTransaksi ? '✅ EXISTS' : '❌ MISSING') . "\n";
  
  $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
  $hasUser = $stmt->rowCount() > 0;
  echo "Table 'user': " . ($hasUser ? '✅ EXISTS' : '❌ MISSING') . "\n";
  
  echo "\nAll tables:\n";
  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
  print_r($tables);
} catch(Exception $e) {
  echo "❌ DB Error: " . $e->getMessage();
}
?></pre>

<h2>3. Test API Call (kendaraan list)</h2>
<pre><?php
// Bypass auth for test
ob_start();
include 'api/kendaraan.php?action=list';
$response = ob_get_clean();
echo $response;
?></pre>

<h2>Next Steps</h2>
<ul>
<li>If session ❌: Clear cookies, relogin</li>
<li>If tables ❌: Create missing tables via phpMyAdmin</li>
<li>API error? Check console after page load</li>
<li>Test: <a href="Dasboard/Navbar.php">Dashboard</a></li>
</ul>

</body></html>

