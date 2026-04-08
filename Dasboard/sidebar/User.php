<?php
session_start();
require_once '../../config.php';

// ✅ FIX LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../Index.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// ================= HANDLE ACTION =================
if ($_POST) {
    try {
        $action = $_POST['action'];

        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO user (nama,sebagai,`user`,password,foto,keterangan)
            VALUES (?,?,?,?,?,?)");

            $stmt->execute([
                $_POST['nama'],
                $_POST['sebagai'],
                $_POST['username'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['foto'],
                $_POST['keterangan']
            ]);
        }

        if ($action == 'edit') {
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare("UPDATE user SET nama=?, sebagai=?, `user`=?, password=?, foto=?, keterangan=? WHERE id_user=?");
                $stmt->execute([
                    $_POST['nama'],
                    $_POST['sebagai'],
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['foto'],
                    $_POST['keterangan'],
                    $_POST['id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE user SET nama=?, sebagai=?, `user`=?, foto=?, keterangan=? WHERE id_user=?");
                $stmt->execute([
                    $_POST['nama'],
                    $_POST['sebagai'],
                    $_POST['username'],
                    $_POST['foto'],
                    $_POST['keterangan'],
                    $_POST['id']
                ]);
            }
        }

        if ($action == 'delete') {
            if ($_POST['id'] == $current_user_id) {
                throw new Exception("Tidak bisa hapus diri sendiri");
            }
            $pdo->prepare("DELETE FROM user WHERE id_user=?")->execute([$_POST['id']]);
        }

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        echo "<div style='color:red'>".$e->getMessage()."</div>";
    }
}

// ================= DATA =================
$users = $pdo->query("SELECT * FROM user ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manajemen User</title>

<style>
body{
font-family:Segoe UI;
background:#eef2f7;
margin:0;padding:20px;
}
.container{
max-width:1100px;margin:auto;
background:white;padding:20px;
border-radius:12px;
box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

h2{text-align:center;margin-bottom:20px}

.btn{
background:#10b981;color:white;
padding:12px;border:none;border-radius:10px;
cursor:pointer;width:100%;margin-bottom:20px;
}

table{width:100%;border-collapse:collapse}
th{
background:#111827;color:white;
padding:15px;text-align:left;
}
td{
padding:15px;border-bottom:1px solid #eee;
}

.user-info{
display:flex;align-items:center;gap:10px;
}
.user-info img{
width:45px;height:45px;border-radius:50%;
object-fit:cover;border:2px solid #10b981;
}

.status{
padding:5px 12px;
border-radius:20px;
font-size:12px;
font-weight:bold;
display:inline-block;
}
.online{background:#d1fae5;color:#065f46}
.offline{background:#fee2e2;color:#991b1b}

.edit-btn{
background:#f59e0b;color:white;
border:none;padding:6px 12px;
border-radius:6px;cursor:pointer;
}
.delete-btn{
background:#ef4444;color:white;
border:none;padding:6px 12px;
border-radius:6px;cursor:pointer;
}

/* SIDEBAR */
.form-slide{
position:fixed;right:-400px;top:0;
width:350px;height:100%;
background:white;padding:20px;
transition:.3s;z-index:1002;
box-shadow:-5px 0 20px rgba(0,0,0,0.2);
}
.form-slide.active{right:0}

.overlay{
position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.5);
display:none;z-index:1001;
}
.overlay.active{display:block}

input,textarea{
width:100%;padding:10px;margin-bottom:10px;
border:1px solid #ccc;border-radius:8px;
}
</style>

</head>
<body>

<div class="container">

<h2>👥 Manajemen User</h2>

<button class="btn" onclick="openForm()">+ Tambah User</button>

<table>
<tr>
<th>Nama</th>
<th>Role</th>
<th>Username</th>
<th>Status</th>
<th>Info</th>
<th>Opsi</th>
</tr>

<?php foreach($users as $u): ?>
<tr>

<td>
<div class="user-info">
<img src="<?= $u['foto'] ?: 'https://via.placeholder.com/50' ?>">
<div>
<b><?= $u['nama'] ?></b><br>
<small><?= $u['keterangan'] ?></small>
</div>
</div>
</td>

<td><?= $u['sebagai'] ?></td>
<td><?= $u['user'] ?></td>

<td>
<?php if($u['id_user'] == $current_user_id): ?>
<span class="status online">Online</span>
<?php else: ?>
<span class="status offline">Offline</span>
<?php endif; ?>
</td>

<td>
<?php if($u['id_user'] == $current_user_id): ?>
Aktif sekarang
<?php else: ?>
Tidak aktif
<?php endif; ?>
</td>

<td>
<button class="edit-btn"
onclick="editUser(this)"
data-id="<?= $u['id_user'] ?>"
data-nama="<?= htmlspecialchars($u['nama']) ?>"
data-sebagai="<?= htmlspecialchars($u['sebagai']) ?>"
data-user="<?= htmlspecialchars($u['user']) ?>"
data-foto="<?= htmlspecialchars($u['foto']) ?>"
data-keterangan="<?= htmlspecialchars($u['keterangan']) ?>"
>✏️</button>

<button class="delete-btn" onclick="hapusUser(<?= $u['id_user'] ?>)">🗑️</button>
</td>

</tr>
<?php endforeach; ?>

</table>
</div>

<div class="overlay" id="overlay" onclick="closeForm()"></div>

<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah User</h3>

<form method="POST">
<input type="hidden" name="action" id="actionInput" value="add">
<input type="hidden" name="id" id="idUser">

<input name="nama" placeholder="Nama" required>
<input name="sebagai" placeholder="Role">
<input name="username" placeholder="Username" required>
<input name="password" placeholder="Password">
<input name="foto" placeholder="URL Foto">
<textarea name="keterangan" placeholder="Keterangan"></textarea>

<button class="btn">Simpan</button>
<button type="button" class="btn" style="background:#6b7280" onclick="closeForm()">Batal</button>
</form>
</div>

<script>
function openForm(){
document.getElementById('formSlide').classList.add('active');
document.getElementById('overlay').classList.add('active');
document.querySelector('form').reset();
}

function closeForm(){
document.getElementById('formSlide').classList.remove('active');
document.getElementById('overlay').classList.remove('active');
}

function editUser(btn){
openForm();
let d=btn.dataset;

document.getElementById('actionInput').value='edit';
document.getElementById('formTitle').innerText='Edit User';

idUser.value=d.id;
nama.value=d.nama;
sebagai.value=d.sebagai;
username.value=d.user;
foto.value=d.foto;
keterangan.value=d.keterangan;
}

function hapusUser(id){
if(confirm('Hapus user?')){
let f=document.createElement('form');
f.method='POST';
f.innerHTML=`<input name="action" value="delete"><input name="id" value="${id}">`;
document.body.appendChild(f);
f.submit();
}
}
</script>

</body>
</html>