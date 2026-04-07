<?php
require 'config.php';
echo "Kendaraan count: " . $pdo->query('SELECT COUNT(*) FROM kendaraan')->fetchColumn() . "\n";
$row = $pdo->query('SELECT * FROM kendaraan LIMIT 1')->fetch(PDO::FETCH_ASSOC);
print_r($row);

echo "\nCustomers count: " . $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() . "\n";
echo "Material count: " . $pdo->query('SELECT COUNT(*) FROM material')->fetchColumn() . "\n";
echo "Supplier count: " . $pdo->query('SELECT COUNT(*) FROM supplier')->fetchColumn() . "\n";
?>

