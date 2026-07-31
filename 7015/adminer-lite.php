<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// Login Process
if (isset($_POST['login'])) {
    $_SESSION['db_host'] = $_POST['host'];
    $_SESSION['db_user'] = $_POST['user'];
    $_SESSION['db_pass'] = $_POST['pass'];
    $_SESSION['db_name'] = $_POST['name'];
    header("Location: ?");
    exit;
}

// Check Login
if (!isset($_SESSION['db_host'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Adminer Lite</title>
        <style>
            body { font-family: sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
            button { width: 100%; padding: 10px; background: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2 style="text-align: center; margin-top: 0;">Adminer Lite</h2>
            <form method="POST">
                <input type="text" name="host" placeholder="Server (e.g., localhost)" value="localhost" required>
                <input type="text" name="user" placeholder="Username" required>
                <input type="password" name="pass" placeholder="Password">
                <input type="text" name="name" placeholder="Database Name">
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Connect to Database
$mysqli = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
if ($mysqli->connect_error) {
    echo "<h3 style='color:red'>Connection Failed: " . $mysqli->connect_error . "</h3>";
    echo "<a href='?logout=1'>Back to Login</a>";
    exit;
}

// Layout
?>
<!DOCTYPE html>
<html>
<head>
    <title>Adminer Lite - <?php echo htmlspecialchars($_SESSION['db_name']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; color: #333; }
        .header { background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; margin-left: 15px; }
        .container { display: flex; height: calc(100vh - 50px); }
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #ddd; overflow-y: auto; padding: 15px; }
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        ul { list-style: none; padding: 0; }
        li { margin-bottom: 8px; }
        li a { text-decoration: none; color: #007BFF; display: block; padding: 5px; }
        li a:hover { background: #f0f0f0; border-radius: 4px; }
        .btn { padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

<div class="header">
    <strong>Adminer Lite</strong>
    <div>
        <span>👤 <?php echo htmlspecialchars($_SESSION['db_user'] . '@' . $_SESSION['db_host']); ?></span>
        <a href="?sql=1">💻 SQL Command</a>
        <a href="?logout=1" style="color:#ff6b6b;">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <div class="sidebar">
        <h3>Tables</h3>
        <ul>
            <?php
            $q = $mysqli->query("SHOW TABLES");
            while ($r = $q->fetch_row()) {
                $tbl = htmlspecialchars($r[0]);
                echo "<li><a href='?table={$tbl}'>📄 {$tbl}</a></li>";
            }
            ?>
        </ul>
    </div>
    
    <div class="content">
        <?php
        function getPK($mysqli, $table) {
            $q = $mysqli->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            if ($q && $r = $q->fetch_assoc()) return $r['Column_name'];
            return null;
        }

        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            $table = $_GET['table'] ?? '';
            $cleanTable = $mysqli->real_escape_string($table);
            $pk = getPK($mysqli, $cleanTable);

            if ($action == 'delete' && isset($_GET['pk_val']) && $pk) {
                $pk_val = $mysqli->real_escape_string($_GET['pk_val']);
                try {
                    $mysqli->query("DELETE FROM `$cleanTable` WHERE `$pk` = '$pk_val'");
                    echo "<p style='color:green;'>Data deleted!</p>";
                } catch (Exception $e) {
                    echo "<p style='color:red;'>Error Deleting: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "<a href='?table=".urlencode($table)."'>Back to Table</a>";
            }
            elseif ($action == 'clone' && isset($_GET['pk_val']) && $pk) {
                $pk_val = $mysqli->real_escape_string($_GET['pk_val']);
                $q = $mysqli->query("SELECT * FROM `$cleanTable` WHERE `$pk` = '$pk_val'");
                if ($r = $q->fetch_assoc()) {
                    unset($r[$pk]); 
                    $cols = array_map(function($k) { return "`$k`"; }, array_keys($r));
                    $vals = array_map(function($v) use ($mysqli) { return "'".$mysqli->real_escape_string((string)$v)."'"; }, array_values($r));
                    try {
                        $mysqli->query("INSERT INTO `$cleanTable` (".implode(',', $cols).") VALUES (".implode(',', $vals).")");
                        echo "<p style='color:green;'>Data cloned!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:red;'>Error Cloning: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "<p style='color:#555;'><i>(Ini sering terjadi jika ada kolom UNIQUE, seperti 'nip' atau 'username' yang datanya sama persis. Tidak boleh ada data duplikat pada kolom unik.)</i></p>";
                    }
                }
                echo "<a href='?table=".urlencode($table)."'>Back to Table</a>";
            }
            elseif ($action == 'edit' && isset($_GET['pk_val']) && $pk) {
                $pk_val = $mysqli->real_escape_string($_GET['pk_val']);
                if (isset($_POST['save'])) {
                    $set = [];
                    foreach ($_POST['data'] as $k => $v) {
                        $set[] = "`$k` = '".$mysqli->real_escape_string($v)."'";
                    }
                    try {
                        $mysqli->query("UPDATE `$cleanTable` SET ".implode(',', $set)." WHERE `$pk` = '$pk_val'");
                        echo "<p style='color:green;'>Data updated!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:red;'>Error Updating: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    echo "<a href='?table=".urlencode($table)."'>Back to Table</a>";
                } else {
                    $q = $mysqli->query("SELECT * FROM `$cleanTable` WHERE `$pk` = '$pk_val'");
                    if ($r = $q->fetch_assoc()) {
                        echo "<h2>Edit Data</h2><form method='POST'>";
                        foreach ($r as $k => $v) {
                            $readonly = ($k == $pk) ? "readonly style='background:#eee;'" : "";
                            echo "<div style='margin-bottom:10px;'><b>$k</b><br><input type='text' name='data[$k]' value='".htmlspecialchars((string)$v)."' $readonly style='width:100%; max-width:500px; padding:5px;'></div>";
                        }
                        echo "<button type='submit' name='save' class='btn'>Save Changes</button> <a href='?table=".urlencode($table)."'>Cancel</a></form>";
                    }
                }
            }
            elseif ($action == 'add' && $table) {
                if (isset($_POST['add'])) {
                    $cols = []; $vals = [];
                    foreach ($_POST['data'] as $k => $v) {
                        if ($v !== '') {
                            $cols[] = "`$k`";
                            $vals[] = "'".$mysqli->real_escape_string($v)."'";
                        }
                    }
                    try {
                        $mysqli->query("INSERT INTO `$cleanTable` (".implode(',', $cols).") VALUES (".implode(',', $vals).")");
                        echo "<p style='color:green;'>Data added!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:red;'>Error Adding: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    echo "<a href='?table=".urlencode($table)."'>Back to Table</a>";
                } else {
                    $q = $mysqli->query("SHOW COLUMNS FROM `$cleanTable`");
                    echo "<h2>Add Data</h2><form method='POST'>";
                    while ($c = $q->fetch_assoc()) {
                        echo "<div style='margin-bottom:10px;'><b>{$c['Field']}</b><br><input type='text' name='data[{$c['Field']}]' style='width:100%; max-width:500px; padding:5px;'></div>";
                    }
                    echo "<button type='submit' name='add' class='btn'>Add Data</button> <a href='?table=".urlencode($table)."'>Cancel</a></form>";
                }
            }
        }
        elseif (isset($_GET['sql'])) {
            echo "<h2>Run SQL Command</h2>";
            echo "<form method='POST'>
                    <textarea name='query' rows='6' style='width:100%; font-family:monospace; padding:10px;' placeholder='SELECT * FROM ...'></textarea><br><br>
                    <button type='submit' name='run_sql' class='btn'>Execute</button>
                  </form><br>";

            if (isset($_POST['run_sql']) && trim($_POST['query']) !== "") {
                $query = trim($_POST['query']);
                echo "<div style='background:#333; color:#0f0; padding:10px; font-family:monospace; margin-bottom:15px;'>".htmlspecialchars($query)."</div>";
                
                if ($mysqli->multi_query($query)) {
                    do {
                        if ($result = $mysqli->store_result()) {
                            echo "<table><tr>";
                            $fields = $result->fetch_fields();
                            foreach ($fields as $f) echo "<th>" . htmlspecialchars($f->name) . "</th>";
                            echo "</tr>";
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                foreach ($row as $val) echo "<td>" . htmlspecialchars((string)$val) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table><br>";
                            $result->free();
                        } else {
                            if ($mysqli->errno) {
                                echo "<div style='color:red;'>Error: " . $mysqli->error . "</div>";
                            } else {
                                echo "<div style='color:green;'>Query executed successfully. Affected rows: " . $mysqli->affected_rows . "</div><br>";
                            }
                        }
                    } while ($mysqli->more_results() && $mysqli->next_result());
                } else {
                    echo "<div style='color:red;'>Error: " . $mysqli->error . "</div>";
                }
            }
        } 
        elseif (isset($_GET['table'])) {
            $table = $_GET['table'];
            $cleanTable = $mysqli->real_escape_string($table);
            $pk = getPK($mysqli, $cleanTable);
            echo "<h2>Table: " . htmlspecialchars($table) . "</h2>";
            echo "<a href='?action=add&table=".urlencode($table)."' class='btn' style='margin-bottom:15px;'>+ Tambah Data</a>";
            
            // Get data (Limit 100 for lightness)
            $q = $mysqli->query("SELECT * FROM `$cleanTable` LIMIT 100");
            if ($q) {
                echo "<table><tr>";
                $fields = $q->fetch_fields();
                foreach ($fields as $f) echo "<th>" . htmlspecialchars($f->name) . "</th>";
                if ($pk) echo "<th>Aksi</th>";
                echo "</tr>";
                
                while ($row = $q->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $val) {
                        $display = htmlspecialchars((string)$val);
                        if (strlen($display) > 100) $display = substr($display, 0, 97) . "...";
                        echo "<td>$display</td>";
                    }
                    if ($pk) {
                        $pk_val = urlencode($row[$pk]);
                        $tbl = urlencode($table);
                        echo "<td style='white-space:nowrap;'>
                                <a href='?action=edit&table=$tbl&pk_val=$pk_val' style='color:blue;'>Edit</a> | 
                                <a href='?action=clone&table=$tbl&pk_val=$pk_val' style='color:orange;'>Clone</a> | 
                                <a href='?action=delete&table=$tbl&pk_val=$pk_val' onclick='return confirm(\"Are you sure?\");' style='color:red;'>Delete</a>
                              </td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p style='color:#666; font-size:0.9em;'>Showing max 100 rows.</p>";
            } else {
                echo "<p style='color:red;'>Error reading table: " . $mysqli->error . "</p>";
            }
        } else {
            echo "<h2>Welcome to Adminer Lite</h2>";
            echo "<p>Select a table from the left sidebar to view its contents, or run an SQL command.</p>";
        }
        ?>
    </div>
</div>

</body>
</html>
