<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;padding:50px;">⚠️ Silakan login</div>';
    exit;
}

ob_start();

// ================= UPLOAD FOTO =================
function uploadFoto($file) {
    if (!isset($file) || $file['name'] == '') return null;

    $folder = "../../uploads/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $namaFile = time().'_'.basename($file['name']);
    $path = $folder.$namaFile;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return "uploads/".$namaFile;
    }
    return null;
}

// ================= HANDLE POST =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    ob_clean();

    $action = $_POST['action'] ?? '';

    // ===== ADD =====
    if ($action == 'add') {

        $nama = $_POST['nama'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($password)) {
            echo "Nama dan Password wajib diisi";
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO user (nama, user, password, sebagai, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $nama,
            $_POST['username'] ?: null,
            password_hash($password, PASSWORD_DEFAULT),
            $_POST['sebagai'] ?: null,
            uploadFoto($_FILES['foto']) ?: null,
            $_POST['keterangan'] ?: null
        ]);

        echo "success";
        exit;
    }

    // ===== EDIT =====
    if ($action == 'edit') {

        $id = $_POST['id'];
        $nama = $_POST['nama'] ?? '';

        if (empty($nama)) {
            echo "Nama wajib diisi";
            exit;
        }

        $query = "UPDATE user SET nama=?";
        $params = [$nama];

        if (!empty($_POST['username'])) {
            $query .= ", user=?";
            $params[] = $_POST['username'];
        }

        if (!empty($_POST['sebagai'])) {
            $query .= ", sebagai=?";
            $params[] = $_POST['sebagai'];
        }

        if (!empty($_POST['password'])) {
            $query .= ", password=?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $foto = uploadFoto($_FILES['foto']);
        if ($foto) {
            $query .= ", foto=?";
            $params[] = $foto;
        }

        if (!empty($_POST['keterangan'])) {
            $query .= ", keterangan=?";
            $params[] = $_POST['keterangan'];
        }

        $query .= " WHERE id_user=?";
        $params[] = $id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        echo "success";
        exit;
    }

    // ===== DELETE =====
    if ($action == 'delete') {
        $id = $_POST['id'];

        if ($id == 1) {
            echo "Admin tidak bisa dihapus";
            exit;
        }

        $pdo->prepare("DELETE FROM user WHERE id_user=?")->execute([$id]);

        echo "success";
        exit;
    }
}

// ================= LOAD DATA =================
$users = $pdo->query("SELECT * FROM user ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
<h2>Manajemen User</h2>
<button class="btn" onclick="openForm()">+ Tambah</button>

<table>
<tr>
<th>Foto</th>
<th>Nama</th>
<th>Username</th>
<th>Aksi</th>
</tr>

<?php foreach($users as $u): ?>
<tr>
<td>
<img src="../<?= $u['foto'] ?: 'uploads/default.png' ?>">
</td>
<td><?= htmlspecialchars($u['nama']) ?></td>
<td><?= htmlspecialchars($u['user']) ?></td>
<td>
<button class="edit" onclick='editUser(<?= json_encode($u) ?>)'>Edit</button>
<button class="hapus" onclick="hapus(<?= $u['id_user'] ?>)">Hapus</button>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

<!-- FORM -->
<div class="overlay" id="overlay" onclick="closeForm()"></div>

<div class="formBox" id="formBox">
<form id="formUser" enctype="multipart/form-data">

<input type="hidden" name="id" id="id">
<input type="hidden" name="action" id="action">

<input name="nama" placeholder="Nama *" required>
<input name="username" placeholder="Username">
<input type="password" name="password" id="password" placeholder="Password *">
<input name="sebagai" placeholder="Role">
<input type="file" name="foto">
<textarea name="keterangan" placeholder="Keterangan"></textarea>

<button class="btn">Simpan</button>
<button type="button" onclick="closeForm()">Batal</button>

</form>
</div>

<style>
body{font-family:Segoe UI;background:#f1f5f9;}
.container{max-width:1100px;margin:auto;background:white;padding:25px;border-radius:12px;}
.btn{background:#10b981;color:white;padding:10px;border:none;border-radius:8px;cursor:pointer;}
.edit{background:#f59e0b;}
.hapus{background:#ef4444;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th{background:#1f2937;color:white;padding:12px;}
td{padding:12px;border-bottom:1px solid #eee;}
img{width:50px;height:50px;border-radius:50%;object-fit:cover;}

.formBox{position:fixed;right:-400px;top:0;width:350px;height:100%;background:white;padding:20px;transition:.3s;z-index:1500;}
.formBox.active{right:0;}

.overlay{
    position:fixed;
    left:250px;
    width:calc(100% - 250px);
    height:100%;
    background:rgba(0,0,0,0.5);
    display:none;
    z-index:1000;
}
.overlay.active{display:block;}

.sidebar{z-index:2000;position:fixed;}
</style>

<script>
function openForm(){
    formUser.reset();
    action.value='add';

    password.required = true;

    formBox.classList.add('active');
    overlay.classList.add('active');
}

function closeForm(){
    formBox.classList.remove('active');
    overlay.classList.remove('active');
}

function editUser(data){
    openForm();

    action.value='edit';
    id.value=data.id_user;

    formUser.nama.value=data.nama;
    formUser.username.value=data.user || '';
    formUser.sebagai.value=data.sebagai || '';
    formUser.keterangan.value=data.keterangan || '';

    password.required = false;
}

function hapus(id){
    if(confirm('Hapus data?')){
        let fd = new FormData();
        fd.append('action','delete');
        fd.append('id',id);

        fetch('',{method:'POST',body:fd})
        .then(r=>r.text())
        .then(res=>{
            if(res.trim()=='success'){
                loadContent('sidebar/User.php','User');
            }else{
                alert(res);
            }
        });
    }
}

formUser.onsubmit = function(e){
    e.preventDefault();

    let fd = new FormData(this);

    fetch('',{method:'POST',body:fd})
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()=='success'){
            closeForm();
            loadContent('sidebar/User.php','User');
        }else{
            alert(res);
        }
    });
}
</script>