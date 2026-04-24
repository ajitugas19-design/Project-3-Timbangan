<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");

/* ================= PARAM ================= */
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';
$limit  = $_GET['limit'] ?? 'all';

/* ================= AMBIL HTML ================= */
$html_url = 'http://localhost/Project_3/Dasboard/Laporan/Struktur.php?' . http_build_query($_GET);
$html = file_get_contents($html_url);

/* ================= CLEAN DUPLICATE HEADER ================= */
$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
$html = str_replace('onload="window.print()"', '', $html);

/* HAPUS HEADER SUPAYA TIDAK DOBEL */
$html = preg_replace('/<h2.*?>.*?<\/h2>/i', '', $html);
$html = preg_replace('/<div class="info">.*?<\/div>/is', '', $html);

/* ================= STYLE WORD SEDERHANA ================= */
$style = '
<style>
body{
    font-family:Arial, sans-serif;
    font-size:11pt;
    color:#000;
    margin:20px;
}

h2{
    text-align:center;
    font-size:15pt;
    margin-bottom:10px;
}

.info{
    font-size:10pt;
    margin-bottom:15px;
    line-height:1.5;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

th, td{
    border:1px solid #000;
    padding:4px;
    font-size:10pt;
    text-align:center;
}

th{
    font-weight:bold;
}

.left{
    text-align:left;
}

.right{
    text-align:right;
}

.ttd{
    width:100%;
    margin-top:50px;
}

.ttd td{
    border:none;
    text-align:center;
    padding-top:50px;
}
</style>
';

/* ================= WORD TEMPLATE ================= */
$word = '
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
<title>Laporan Timbangan</title>
'.$style.'
</head>
<body>

<h2>LAPORAN TRANSAKSI TIMBANGAN</h2>

'.$html.'

<table class="ttd">
<tr>
<td>Mengetahui,<br><br><br><br>(_____________)</td>
<td>Admin,<br><br><br><br>(_____________)</td>
</tr>
</table>

</body>
</html>
';

/* ================= DOWNLOAD WORD ================= */
header("Content-Type: application/msword");
header('Content-Disposition: attachment; filename="laporan_'.date('Y-m-d_H-i-s').'.doc"');
header("Cache-Control: private");
header("Pragma: public");

echo $word;
exit;
?>