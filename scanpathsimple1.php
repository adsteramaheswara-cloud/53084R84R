<?php
// Check if writable directory scan is requested
if (isset($_GET['scan_writable']) && $_GET['scan_writable'] == '1') {
    scanWritableDirectories();
    exit;
}

echo "<h3>Advanced Security Scanner (Legacy Compatibility Mode)</h3><hr>";

// Navigation menu
echo "<div style='background:#f0f0f0;padding:10px;margin-bottom:20px;'>";
echo "<b>Menu:</b> ";
echo "<a href='?'>Shell Scanner</a> | ";
echo "<a href='?scan_writable=1'>Writable Directory Scanner</a>";
echo "</div>";

echo "<h3>Shell Scan Result</h3><hr>";

// Fungsi manual untuk scan direktori (Pengganti RecursiveIterator)
function customScan($dir, $mode = 'shell') {
    $skipExt = array('jpg', 'png', 'gif', 'css', 'js', 'ico');
    $pattern = '/(base64_decode|eval\s*\(|gzinflate|str_rot13|shell_exec|exec\s*\(|system\s*\(|passthru|assert\s*\(|preg_replace\s*\(.*\/e|ReflectionClass|\$_(GET|POST|REQUEST))/i';

    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == "..") continue;

            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                if ($mode == 'writable' && is_writable($fullPath)) {
                    echo $fullPath . "\n";
                }
                // Rekursi ke dalam folder
                customScan($fullPath, $mode);
            } else {
                if ($mode == 'shell') {
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    if (in_array($ext, $skipExt)) continue;
                    
                    $size = filesize($fullPath);
                    if ($size > 5 * 1024 * 1024) continue;

                    $content = @file_get_contents($fullPath);
                    if ($content === false) continue;

                    // 1. Regex detection
                    if (preg_match($pattern, $content)) {
                        echo "<b style='color:red'>[DANGER]</b> {$fullPath}<br>";
                    } 
                    // 2. Small PHP files
                    elseif (in_array($ext, array('php', 'phtml')) && $size < 500) {
                        echo "<b style='color:orange'>[SUSPICIOUS (Small)]</b> {$fullPath}<br>";
                    }
                    // 3. Injected code
                    elseif ($ext !== 'php' && strpos($content, '<?php') !== false) {
                        echo "<b style='color:purple'>[INJECTED]</b> {$fullPath}<br>";
                    }
                }
            }
        }
        closedir($handle);
    }
}

// Function to scan writable directories
function scanWritableDirectories() {
    header('Content-Type: text/plain');
    $scanPath = isset($_GET['path']) ? $_GET['path'] : $_SERVER['DOCUMENT_ROOT'];
    $realPath = realpath($scanPath);
    
    if (!$realPath || !is_dir($realPath)) {
        echo "Error: Invalid path\n";
        return;
    }
    
    customScan($realPath, 'writable');
}

// Jalankan scanner shell secara default
$rootPath = realpath($_SERVER['DOCUMENT_ROOT']);
customScan($rootPath, 'shell');

?>
