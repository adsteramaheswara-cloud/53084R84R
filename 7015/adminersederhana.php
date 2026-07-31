<?php
// Konfigurasi koneksi database
$host = 'localhost'; // Ganti dengan host database Anda
$username = 'root'; // Ganti dengan username MySQL Anda
$password = ''; // Ganti dengan password MySQL Anda
$dbname = 'workshop_sample'; // Nama database yang akan dikelola

// Membuat koneksi ke database
$mysqli = new mysqli($host, $username, $password, $dbname);

// Mengecek koneksi
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Fungsi untuk menampilkan daftar tabel
function showTables($mysqli) {
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        echo "<li><a href='?table=$row[0]'>$row[0]</a></li>";
    }
}

// Fungsi untuk menampilkan data dalam tabel
function showTableData($mysqli, $table) {
    $result = $mysqli->query("SELECT * FROM $table");
    echo "<table border='1'>";
    echo "<tr>";
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "<th>Action</th>"; // Kolom untuk link Edit
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $column) {
            echo "<td>" . $column . "</td>";
        }
        echo "<td><a href='?edit=$table&id=" . $row['id'] . "'>Edit</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Fungsi untuk menampilkan formulir edit dan memproses update data
function showEditForm($mysqli, $table, $id) {
    // Ambil data dari tabel berdasarkan ID
    $result = $mysqli->query("SELECT * FROM $table WHERE id = $id");
    $row = $result->fetch_assoc();
    
    if (!$row) {
        echo "Data tidak ditemukan!";
        return;
    }

    // Menampilkan formulir untuk mengedit data
    echo "<h2>Edit Data</h2>";
    echo "<form method='POST' action=''>";
    foreach ($row as $field => $value) {
        if ($field != 'id') { // Jangan tampilkan kolom ID pada form
            echo "<label for='$field'>" . ucfirst($field) . ":</label><br>";
            echo "<input type='text' name='$field' id='$field' value='" . htmlspecialchars($value) . "'><br><br>";
        }
    }
    echo "<input type='hidden' name='table' value='$table'>";
    echo "<input type='hidden' name='id' value='$id'>";
    echo "<input type='submit' name='update' value='Update'>";
    echo "</form>";
}

// Fungsi untuk memproses pembaruan data
function updateData($mysqli, $table, $id, $data) {
    $set_values = [];
    foreach ($data as $key => $value) {
        if ($key != 'id' && $key != 'table') {
            $set_values[] = "$key = '" . $mysqli->real_escape_string($value) . "'";
        }
    }
    $set_values = implode(", ", $set_values);
    
    // Query update
    $query = "UPDATE $table SET $set_values WHERE id = $id";
    
    if ($mysqli->query($query)) {
        echo "<p>Data berhasil diperbarui!</p>";
    } else {
        echo "<p>Terjadi kesalahan saat memperbarui data: " . $mysqli->error . "</p>";
    }
}

// Memproses permintaan edit atau pembaruan
if (isset($_GET['edit'])) {
    $table = $_GET['edit'];
    $id = $_GET['id'];
    showEditForm($mysqli, $table, $id);
} elseif (isset($_POST['update'])) {
    $table = $_POST['table'];
    $id = $_POST['id'];
    $data = $_POST;
    updateData($mysqli, $table, $id, $data);
} else {
    // Menampilkan daftar tabel
    if (isset($_GET['table'])) {
        $table = $_GET['table'];
        showTableData($mysqli, $table);
    } else {
        echo "<h2>Daftar Tabel</h2><ul>";
        showTables($mysqli);
        echo "</ul>";
    }
}

// Menutup koneksi
$mysqli->close();
?>
