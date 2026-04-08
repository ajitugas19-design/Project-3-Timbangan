<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}

require_once '../../config.php';
date_default_timezone_set("Asia/Jakarta");

// ================= DELETE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = $_POST['id'] ?? 0;

    if ($id) {
        $del = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        $del->execute([$id]);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// ================= PARAM =================
$tgl = $_GET['tgl'] ?? date('Y-m-d');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

// ================= WHERE =================
$where = "WHERE 1=1";
$params = [];

if ($tgl) {
    $where .= " AND (DATE(wi.tanggal_in)=? OR DATE(wo.tanggal_out)=?)";
    $params[] = $tgl;
    $params[] = $tgl;
}

if ($search) {
    $where .= " AND (t.no_record LIKE ? OR k.Sopir LIKE ? OR k.Nopol LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// ================= TOTAL =================
$countStmt = $pdo->prepare("
SELECT COUNT(*) FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
$where
");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// ================= DATA (FIX LIMIT OFFSET) =================
$stmt = $pdo->prepare("
SELECT 
    t.id_transaksi, t.no_record,
    k.Sopir as sopir, k.Nopol as nopol,
    s.Nama_Supplier as supplier, 
    m.Material as muatan, 
    c.Customers as customer,
    wi.tanggal_in, wo.tanggal_out,
    t.bruto, t.tara, t.netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
$where
ORDER BY t.id_transaksi DESC
LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= STATS =================
$statsStmt = $pdo->prepare("
SELECT 
    COUNT(*) as total,
    SUM(t.netto) as total_netto,
    SUM(CASE WHEN DATE(wi.tanggal_in)=? THEN 1 ELSE 0 END) as today_count
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
");
$statsStmt->execute([$tgl]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalPages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informasi Data</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
  min-height: 100vh;
  padding: 1rem;
  color: #1e293b;
  line-height: 1.6;
}

@media (max-width: 768px) {
  body {
    padding: 0.5rem;
  }
}
.container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 20px;
  max-width: 1400px;
  margin: 0 auto;
  box-shadow: 0 25px 50px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.2);
}
.title {
  text-align: center;
  font-size: clamp(1.5rem, 4vw, 2.5rem);
  font-weight: 800;
  margin-bottom: 2rem;
  background: linear-gradient(135deg, #3b82f6, #10b981);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-box {
  background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(59,130,246,0.1) 100%);
  padding: 1.5rem;
  border-radius: 16px;
  text-align: center;
  border: 1px solid rgba(16,185,129,0.2);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-box::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #10b981, #3b82f6);
}

.stat-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(16,185,129,0.2);
}

.stat-icon {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  display: block;
}

.stat-value {
  font-size: clamp(1.5rem, 5vw, 2.5rem);
  font-weight: 800;
  background: linear-gradient(135deg, #10b981, #059669);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 0.05em;
}

.controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 2rem;
  align-items: center;
  background: rgba(248, 250, 252, 0.5);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.controls input, .controls select {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  min-width: 160px;
}

.controls input:focus, .controls select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  white-space: nowrap;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.btn:active {
  transform: translateY(0);
}
.btn-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(239,68,68,0.3);
}

.btn-danger:hover {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239,68,68,0.4);
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  background: white;
}

thead {
  position: sticky;
  top: 0;
  z-index: 10;
}

th {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  color: white;
  padding: 1rem 1.25rem;
  text-align: left;
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.025em;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

th:hover {
  background: linear-gradient(135deg, #334155 0%, #475569 100%);
  transform: translateY(-1px);
}

th::after {
  content: '↕️';
  opacity: 0.5;
  margin-left: 0.5rem;
  font-size: 0.75rem;
}

tbody tr {
  transition: all 0.2s ease;
}

tbody tr:nth-child(even) {
  background: rgba(248, 250, 252, 0.8);
}

tbody tr:hover {
  background: linear-gradient(90deg, rgba(59,130,246,0.1), rgba(16,185,129,0.1));
  transform: scale(1.01);
}

td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
}

.netto {
  color: #059669;
  font-weight: 700;
  font-size: 1.05rem;
}

@media (max-width: 768px) {
  table {
    font-size: 0.9rem;
  }
  
  th, td {
    padding: 0.75rem 0.5rem;
  }
  
  .no-mobile {
    display: none;
  }
}
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.page-btn {
  padding: 0.75rem 1rem;
  margin: 0.25rem;
  background: linear-gradient(135deg, #f8fafc, #e2e8f0);
  color: #475569;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.page-btn:hover {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(59,130,246,0.3);
}

.page-btn.active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border-color: rgba(255,255,255,0.3);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.prev-next {
  font-size: 1.1rem;
  min-width: 50px;
}
</style>
</head>

<body>
<div class="container">

<div class="title">📊 Informasi Data Transaksi</div>

<!-- STATS -->
<div class="stats">
    <div class="stat-box">
        <span class="stat-icon">📈</span>
        <div class="stat-value"><?= number_format($stats['total']) ?></div>
        <div class="stat-label">Total Data</div>
    </div>
    <div class="stat-box">
        <span class="stat-icon">📅</span>
        <div class="stat-value"><?= $stats['today_count'] ?></div>
        <div class="stat-label">Hari Ini</div>
    </div>
    <div class="stat-box">
        <span class="stat-icon">⚖️</span>
        <div class="stat-value"><?= number_format($stats['total_netto'] ?? 0) ?> kg</div>
        <div class="stat-label">Total Netto</div>
    </div>
</div>

<!-- FILTER -->
<form method="GET" class="controls">
    <input type="date" name="tgl" value="<?= $tgl ?>" placeholder="📅 Tanggal">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Cari No Record/Sopir/Nopol...">
    <select name="limit">
        <option value="10" <?= $limit==10?'selected':'' ?>>10 Baris</option>
        <option value="25" <?= $limit==25?'selected':'' ?>>25 Baris</option>
        <option value="50" <?= $limit==50?'selected':'' ?>>50 Baris</option>
    </select>
    <button class="btn" type="submit">🔍 Input Data</button>
</form>

<!-- TABLE -->
<table>
<tr>
    <th>No Record</th>
    <th>Sopir</th>
    <th class="no-mobile">Nopol</th>
    <th>Supplier</th>
    <th>Material</th>
    <th class="no-mobile">Customer</th>
    <th>Netto</th>
    <th>Aksi</th>
</tr>

<?php if (empty($data)): ?>
<tr><td colspan="8" style="padding: 4rem; text-align: center;">
    <div style="font-size: 1.2rem; color: #64748b; font-weight: 500;">
        📭 Tidak ada data untuk ditampilkan
    </div>
    <div style="color: #94a3b8; margin-top: 0.5rem;">
        Coba ubah filter tanggal atau pencarian di atas
    </div>
</td></tr>
<?php endif; ?>

<?php foreach ($data as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['no_record']) ?></td>
    <td><?= htmlspecialchars($row['sopir']) ?></td>
    <td><?= htmlspecialchars($row['nopol']) ?></td>
    <td><?= htmlspecialchars($row['supplier']) ?></td>
    <td><?= htmlspecialchars($row['muatan']) ?></td>
    <td><?= htmlspecialchars($row['customer']) ?></td>
    <td class="netto"><?= number_format($row['netto']) ?> kg</td>
    <td>
        <form method="POST" onsubmit="return confirm('Hapus data ini?')">
            <input type="hidden" name="id" value="<?= $row['id_transaksi'] ?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn-danger">Hapus</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

</table>

<!-- PAGINATION -->
<div class="pagination">
    <?php if ($page > 1): ?>
    <a class="page-btn prev-next" href="?page=<?= $page-1 ?>&tgl=<?= $tgl ?>&search=<?= $search ?>&limit=<?= $limit ?>">‹ Prev</a>
    <?php endif; ?>
    
    <?php 
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) echo '<a class="page-btn" href="?page=1&tgl='.$tgl.'&search='.$search.'&limit='.$limit.'">1</a>';
    if ($start > 2) echo '<span>...</span>';
    
    for ($i = $start; $i <= $end; $i++): ?>
        <a class="page-btn <?= $i==$page?'active':'' ?>" 
           href="?page=<?= $i ?>&tgl=<?= $tgl ?>&search=<?= $search ?>&limit=<?= $limit ?>">
            <?= $i ?>
        </a>
    <?php endfor; 
    
    if ($end < $totalPages - 1) echo '<span>...</span>';
    if ($end < $totalPages) echo '<a class="page-btn" href="?page='.$totalPages.'&tgl='.$tgl.'&search='.$search.'&limit='.$limit.'">'.$totalPages.'</a>';
    
    if ($page < $totalPages): ?>
    <a class="page-btn prev-next" href="?page=<?= $page+1 ?>&tgl=<?= $tgl ?>&search=<?= $search ?>&limit=<?= $limit ?>">Next ›</a>
    <?php endif; ?>
</div>

</div>
</body>
</html>