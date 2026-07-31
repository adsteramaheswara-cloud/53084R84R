<?php
/**
 * PHP Security Scanner with File Management
 * Dark Machine Theme
 */

class PHPSecurityScanner {
    
    private $dangerousPatterns = array(
        '/^(.*?(include|require)(_once)?\s*\(\s*[\'"]?\s*\$.*)$/' => 'include/require dengan variabel',
        '/^(.*?file_get_contents\s*\(\s*[\'"]?\s*\$.+)$/' => 'file_get_contents dengan variabel',
        '/^(.*?eval\s*\(\s*[\'"]?\s*\$.+)$/' => 'eval dengan variabel',
        '/^(.*?system\s*\(\s*[\'"]?\s*\$.+)$/' => 'system dengan variabel',
        '/^(.*?exec\s*\(\s*[\'"]?\s*\$.+)$/' => 'exec dengan variabel',
        '/^(.*?shell_exec\s*\(\s*[\'"]?\s*\$.+)$/' => 'shell_exec dengan variabel',
        '/^(.*?base64_decode\s*\(\s*[\'"]?\s*\$.+)$/' => 'base64_decode dengan variabel',
        '/^(.*?unserialize\s*\(\s*[\'"]?\s*\$.+)$/' => 'unserialize dengan variabel',
    );
    
    private $scanResults = array();
    private $suspectFiles = array();
    private $baseUrl = '';
    
    public function __construct($baseUrl = '') {
        $this->baseUrl = $baseUrl;
    }
    
    public function scanDirectory($directory, $recursive = true) {
        if (!is_dir($directory)) {
            return array('error' => 'Directory not found: ' . $directory);
        }
        
        $this->scanResults = array();
        $this->suspectFiles = array();
        
        $this->scanDir($directory, $recursive);
        
        return $this->generateReport();
    }
    
    private function scanDir($dir, $recursive) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($fullPath) && $recursive) {
                $this->scanDir($fullPath, $recursive);
            } elseif (is_file($fullPath)) {
                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($extension, array('php', 'inc', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps'))) {
                    $result = $this->analyzeFile($fullPath);
                    if (!empty($result['issues'])) {
                        $this->scanResults[] = $result;
                        $this->suspectFiles[] = $fullPath;
                    }
                }
            }
        }
    }
    
    private function analyzeFile($filePath) {
        if (!file_exists($filePath)) {
            return array('file' => $filePath, 'issues' => array(), 'error' => 'File not found');
        }
        
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return array('file' => $filePath, 'issues' => array(), 'error' => 'Cannot read file');
        }
        
        $lines = explode("\n", $content);
        $issues = array();
        $lineNumber = 0;
        
        foreach ($lines as $line) {
            $lineNumber++;
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine)) continue;
            
            foreach ($this->dangerousPatterns as $pattern => $description) {
                if (preg_match($pattern, $line, $matches)) {
                    $issues[] = array(
                        'line' => $lineNumber,
                        'description' => $description,
                        'code' => $trimmedLine,
                        'full_code' => $line
                    );
                }
            }
        }
        
        return array(
            'file' => $filePath,
            'filename' => basename($filePath),
            'directory' => dirname($filePath),
            'issues' => $issues,
            'issue_count' => count($issues),
            'web_url' => $this->getWebUrl($filePath),
            'file_size' => filesize($filePath),
            'modified_time' => filemtime($filePath)
        );
    }
    
    private function getWebUrl($filePath) {
        if (empty($this->baseUrl)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
            if ($docRoot && strpos($filePath, $docRoot) === 0) {
                $relativePath = str_replace($docRoot, '', $filePath);
                $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
                return 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . '/' . $relativePath;
            }
            return '';
        }
        
        $relativePath = str_replace(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '', '', $filePath);
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return rtrim($this->baseUrl, '/') . '/' . $relativePath;
    }
    
    private function generateReport() {
        $totalIssues = 0;
        foreach ($this->scanResults as $result) {
            $totalIssues += $result['issue_count'];
        }
        
        return array(
            'results' => $this->scanResults,
            'suspect_files' => $this->suspectFiles,
            'summary' => array(
                'total_files' => count($this->scanResults),
                'total_issues' => $totalIssues,
                'scan_time' => date('Y-m-d H:i:s')
            )
        );
    }
}

// ====================== FILE MANAGEMENT FUNCTIONS ======================

function deleteFile($filePath) {
    if (!file_exists($filePath)) {
        return array('success' => false, 'message' => 'File tidak ditemukan: ' . basename($filePath));
    }
    
    $docRoot = realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '.');
    $realFilePath = realpath($filePath);
    
    if (!$realFilePath) {
        return array('success' => false, 'message' => 'Path file tidak valid');
    }
    
    // Optional: Check if file is within document root (can be disabled if needed)
    // if (strpos($realFilePath, $docRoot) !== 0) {
    //    return array('success' => false, 'message' => 'File berada di luar document root');
    // }
    
    if (@unlink($filePath)) {
        return array('success' => true, 'message' => 'File berhasil dihapus: ' . basename($filePath));
    } else {
        return array('success' => false, 'message' => 'Gagal menghapus file. Cek permission.');
    }
}

function deleteMultipleFiles($filePaths) {
    $results = array();
    foreach ($filePaths as $filePath) {
        $result = deleteFile($filePath);
        $results[] = array(
            'file' => $filePath,
            'success' => $result['success'],
            'message' => $result['message']
        );
    }
    
    $successCount = 0;
    foreach ($results as $r) {
        if ($r['success']) $successCount++;
    }
    $failedCount = count($results) - $successCount;
    
    return array(
        'success' => $failedCount == 0,
        'message' => "Berhasil menghapus $successCount file, gagal $failedCount file",
        'details' => $results
    );
}

function backupFile($filePath) {
    if (!file_exists($filePath)) {
        return array('success' => false, 'message' => 'File tidak ditemukan');
    }
    
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/' . basename($filePath) . '_' . date('Ymd_His') . '.bak';
    
    if (@copy($filePath, $backupFile)) {
        return array('success' => true, 'message' => 'Backup berhasil: ' . basename($backupFile), 'backup_path' => $backupFile);
    } else {
        return array('success' => false, 'message' => 'Gagal membuat backup');
    }
}

function chmodFile($filePath, $mode) {
    if (!file_exists($filePath)) {
        return array('success' => false, 'message' => 'File tidak ditemukan');
    }
    
    // Validate mode (octal string)
    if (!preg_match('/^[0-7]{3,4}$/', $mode)) {
        return array('success' => false, 'message' => 'Format mode tidak valid (contoh: 0644)');
    }
    
    $octalMode = octdec($mode);
    
    if (@chmod($filePath, $octalMode)) {
        return array('success' => true, 'message' => 'Permission diubah ke ' . $mode);
    } else {
        return array('success' => false, 'message' => 'Gagal mengubah permission (chmod mungkin disable)');
    }
}

function chmodMultipleFiles($filePaths, $mode) {
    $results = array();
    foreach ($filePaths as $filePath) {
        $result = chmodFile($filePath, $mode);
        $results[] = array(
            'file' => $filePath,
            'success' => $result['success'],
            'message' => $result['message']
        );
    }
    
    $successCount = 0;
    foreach ($results as $r) {
        if ($r['success']) $successCount++;
    }
    $failedCount = count($results) - $successCount;
    
    return array(
        'success' => $failedCount == 0,
        'message' => "Berhasil chmod $successCount file, gagal $failedCount file",
        'details' => $results
    );
}

// ====================== MAIN SCANNER INTERFACE ======================

// Disable error reporting for AJAX requests to prevent JSON corruption
if (isset($_POST['action']) || isset($_GET['action'])) {
    error_reporting(0);
    @ini_set('display_errors', 0);
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$targetFile = isset($_GET['file']) ? $_GET['file'] : (isset($_POST['file']) ? $_POST['file'] : '');
$targetFiles = isset($_POST['files']) ? $_POST['files'] : array();

if ($action == 'delete' && !empty($targetFile)) {
    header('Content-Type: application/json');
    $result = deleteFile($targetFile);
    echo json_encode($result);
    exit;
}

if ($action == 'delete-multiple' && !empty($targetFiles)) {
    header('Content-Type: application/json');
    $result = deleteMultipleFiles($targetFiles);
    echo json_encode($result);
    exit;
}

if ($action == 'backup' && !empty($targetFile)) {
    header('Content-Type: application/json');
    $result = backupFile($targetFile);
    echo json_encode($result);
    exit;
}

if ($action == 'chmod' && !empty($targetFile)) {
    header('Content-Type: application/json');
    $mode = isset($_POST['mode']) ? $_POST['mode'] : '0644';
    $result = chmodFile($targetFile, $mode);
    echo json_encode($result);
    exit;
}

if ($action == 'chmod-multiple' && !empty($targetFiles)) {
    header('Content-Type: application/json');
    $mode = isset($_POST['mode']) ? $_POST['mode'] : '0644';
    $result = chmodMultipleFiles($targetFiles, $mode);
    echo json_encode($result);
    exit;
}

$scanResults = null;
if (isset($_POST['scan_path'])) {
    $baseUrl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $baseUrl .= $_SERVER['HTTP_HOST'];
    
    $scanner = new PHPSecurityScanner($baseUrl);
    $path = $_POST['scan_path'];
    
    if (is_dir($path)) {
        $scanResults = $scanner->scanDirectory($path, isset($_POST['recursive']));
    } elseif (is_file($path)) {
        $result = $scanner->scanDirectory($path, false);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ PHP Security Scanner - Dark Mode</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #00ff00;
            min-height: 100vh;
            padding: 15px;
            line-height: 1.4;
        }
        
        .scan-line {
            color: #00ff00;
            font-family: 'Courier New', monospace;
            margin-bottom: 5px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #111;
            border: 1px solid #333;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.1);
            overflow: hidden;
        }
        
        .terminal-header {
            background: #1a1a1a;
            padding: 15px 20px;
            border-bottom: 2px solid #00ff00;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .terminal-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .terminal-title h1 {
            font-size: 1.2em;
            color: #00ff00;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        
        .status-light {
            width: 12px;
            height: 12px;
            background: #00ff00;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .scan-form {
            padding: 20px;
            background: #0d0d0d;
            border-bottom: 1px solid #222;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #00ff00;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .terminal-input {
            width: 100%;
            padding: 12px 15px;
            background: #000;
            border: 1px solid #333;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 3px;
        }
        
        .terminal-input:focus {
            outline: none;
            border-color: #00ff00;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #00ff00;
        }
        
        .checkbox-group label {
            color: #00ff00;
            font-size: 0.9em;
        }
        
        .btn {
            padding: 12px 25px;
            border: 1px solid #00ff00;
            background: transparent;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            cursor: pointer;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }
        
        .btn-scan {
            background: #004400;
            border-color: #00aa00;
        }
        
        .btn-delete {
            border-color: #ff3300;
            color: #ff3300;
        }
        
        .btn-delete:hover {
            background: #ff3300;
            color: #000;
        }
        
        .btn-backup {
            border-color: #ffaa00;
            color: #ffaa00;
        }
        
        .btn-backup:hover {
            background: #ffaa00;
            color: #000;
        }
        
        .btn-chmod {
            border-color: #cc00ff;
            color: #cc00ff;
        }
        
        .btn-chmod:hover {
            background: #cc00ff;
            color: #000;
        }

        .btn-preview {
            border-color: #0088ff;
            color: #0088ff;
        }
        
        .btn-preview:hover {
            background: #0088ff;
            color: #000;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            color: #00ff00;
        }
        
        .scanning-animation {
            font-family: 'Courier New', monospace;
            white-space: pre;
            color: #00ff00;
            background: #000;
            padding: 15px;
            border: 1px solid #333;
            border-radius: 3px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .scan-line {
            animation: typewriter 0.1s steps(40) forwards;
        }
        
        @keyframes typewriter {
            from { width: 0; }
            to { width: 100%; }
        }
        
        .results-section {
            padding: 0;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 20px;
            background: #0a0a0a;
            border-bottom: 1px solid #222;
        }
        
        .stat-box {
            background: #000;
            border: 1px solid #333;
            padding: 15px;
            text-align: center;
            border-radius: 3px;
        }
        
        .stat-number {
            font-size: 1.8em;
            color: #00ff00;
            font-weight: bold;
            display: block;
            text-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        .alert-box {
            padding: 15px 20px;
            margin: 0;
            border-left: 4px solid;
            background: #0d0d0d;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-danger {
            border-left-color: #ff3300;
            color: #ff6666;
        }
        
        .alert-success {
            border-left-color: #00ff00;
            color: #99ff99;
        }
        
        .alert-warning {
            border-left-color: #ffaa00;
            color: #ffcc66;
        }
        
        .bulk-actions {
            background: #0a0a0a;
            padding: 15px 20px;
            border-bottom: 1px solid #222;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .select-all-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #00ff00;
        }
        
        .select-all-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #00ff00;
        }
        
        .selected-count {
            background: #004400;
            color: #00ff00;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            border: 1px solid #00aa00;
        }
        
        .files-container {
            padding: 20px;
        }
        
        .file-terminal {
            background: #000;
            border: 1px solid #333;
            border-radius: 3px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .file-header {
            background: #1a1a1a;
            padding: 15px;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .file-checkbox {
            margin-top: 3px;
        }
        
        .file-checkbox input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: #00ff00;
            cursor: pointer;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        
        .file-name {
            color: #00ff00;
            font-weight: bold;
            font-size: 1.05em;
        }
        
        .file-path {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 8px;
            font-family: monospace;
        }
        
        .file-meta {
            display: flex;
            gap: 15px;
            color: #888;
            font-size: 0.8em;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 12px;
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            border: 1px solid;
        }
        
        .badge-danger {
            background: #330000;
            color: #ff6666;
            border-color: #ff3300;
        }
        
        .badge-success {
            background: #003300;
            color: #99ff99;
            border-color: #00ff00;
        }
        
        .content-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            min-height: 400px;
        }
        
        .issues-terminal {
            padding: 15px;
            background: #000;
            border-right: 1px solid #333;
            overflow-y: auto;
            max-height: 500px;
        }
        
        .preview-terminal {
            padding: 15px;
            background: #000;
            display: flex;
            flex-direction: column;
        }
        
        .section-title {
            color: #00ff00;
            font-size: 0.9em;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .issues-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .issue-line {
            background: #111;
            border: 1px solid #333;
            border-left: 4px solid #ff3300;
            padding: 12px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .issue-title {
            color: #ff6666;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .issue-line-number {
            background: #333;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .issue-code {
            background: #0a0a0a;
            padding: 10px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 8px;
            border: 1px solid #222;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .code-highlight {
            background: #330000;
            color: #ff6666;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
        }
        
        .preview-container {
            flex: 1;
            border: 1px solid #333;
            border-radius: 3px;
            overflow: hidden;
            background: #000;
            display: flex;
            flex-direction: column;
        }
        
        .preview-header {
            background: #1a1a1a;
            padding: 12px 15px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .preview-title {
            color: #00ff00;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .preview-frame {
            flex: 1;
            border: none;
            width: 100%;
            background: white;
        }
        
        .preview-placeholder {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
            padding: 40px;
            text-align: center;
        }
        
        .preview-placeholder i {
            font-size: 2em;
            margin-bottom: 15px;
            color: #333;
        }
        
        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #666;
            font-family: 'Courier New', monospace;
        }
        
        .empty-state i {
            font-size: 2em;
            margin-bottom: 15px;
            color: #333;
        }
        
        .empty-state h3 {
            color: #999;
            margin-bottom: 10px;
        }
        
        .terminal-output {
            background: #000;
            border: 1px solid #333;
            padding: 15px;
            margin: 10px 0;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .output-line {
            color: #00ff00;
            font-family: 'Courier New', monospace;
            margin-bottom: 5px;
            white-space: pre-wrap;
        }
        
        .command-prompt {
            color: #00ff00;
        }
        
        .command-input {
            color: #00ff00;
            background: transparent;
            border: none;
            font-family: 'Courier New', monospace;
            width: 80%;
            padding: 5px;
        }
        
        .command-input:focus {
            outline: none;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            background: #0a0a0a;
            border-bottom: 1px solid #222;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-terminal {
            background: #111;
            border: 2px solid #00ff00;
            padding: 25px;
            border-radius: 5px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.3);
        }
        
        .modal-title {
            color: #00ff00;
            margin-bottom: 15px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-content {
            color: #ccc;
            margin-bottom: 20px;
            font-size: 0.95em;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: 1px solid;
            background: transparent;
            color: inherit;
            font-family: 'Courier New', monospace;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .modal-btn-cancel {
            border-color: #666;
            color: #666;
        }
        
        .modal-btn-cancel:hover {
            border-color: #999;
            color: #999;
        }
        
        .modal-btn-confirm {
            border-color: #ff3300;
            color: #ff3300;
        }
        
        .modal-btn-confirm:hover {
            background: #ff3300;
            color: #000;
        }
        
        @media (max-width: 1100px) {
            .content-row {
                grid-template-columns: 1fr;
            }
            
            .issues-terminal {
                border-right: none;
                border-bottom: 1px solid #333;
                max-height: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .file-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .file-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
        
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }
        
        .blink {
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Terminal Header -->
        <div class="terminal-header">
            <div class="terminal-title">
                <div class="status-light"></div>
                <h1><i class="fas fa-terminal"></i> PHP SECURITY SCANNER v1.0</h1>
            </div>
            <div class="scan-line">SYSTEM: ONLINE | TIME: <?php echo date('H:i:s'); ?></div>
        </div>
        
        <!-- Scanner Form -->
        <div class="scan-form">
            <form method="post" id="scanForm">
                <div class="form-group">
                    <div class="form-label">SCAN PATH</div>
                    <input type="text" class="terminal-input" name="scan_path" 
                           value="<?php echo htmlspecialchars(isset($_POST['scan_path']) ? $_POST['scan_path'] : (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '')); ?>" 
                           placeholder="ENTER PATH TO SCAN..." required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="recursive" name="recursive" value="1" checked>
                    <label for="recursive">RECURSIVE SCAN (SUB-DIRECTORIES)</label>
                </div>
                
                <button type="submit" class="btn btn-scan">
                    <i class="fas fa-play"></i> INITIATE SCAN
                </button>
            </form>
        </div>
        
        <!-- Loading Animation -->
        <div class="loading" id="loading">
            <div class="scanning-animation">
                <div class="scan-line">INITIALIZING SCANNER...</div>
                <div class="scan-line">LOADING SECURITY PATTERNS...</div>
                <div class="scan-line">SCANNING FILE SYSTEM<span class="blink">_</span></div>
            </div>
            <p>PLEASE WAIT - ANALYZING FILES</p>
        </div>
        
        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <?php if ($scanResults): ?>
                <?php if (isset($scanResults['error'])): ?>
                    <div class="alert-box alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>ERROR: <?php echo htmlspecialchars($scanResults['error']); ?></div>
                    </div>
                <?php else: ?>
                    <!-- Summary Stats -->
                    <div class="summary-grid">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo count($scanResults['suspect_files']); ?></span>
                            <span class="stat-label">FILES DETECTED</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $scanResults['summary']['total_issues']; ?></span>
                            <span class="stat-label">SECURITY ISSUES</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo date('H:i'); ?></span>
                            <span class="stat-label">SCAN TIME</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo date('d/m'); ?></span>
                            <span class="stat-label">DATE</span>
                        </div>
                    </div>
                    
                    <?php if (empty($scanResults['suspect_files'])): ?>
                        <!-- No Threats Found -->
                        <div class="alert-box alert-success">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>SYSTEM SECURE</strong> - NO SUSPICIOUS FILES DETECTED
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Threats Detected Alert -->
                        <div class="alert-box alert-danger">
                            <i class="fas fa-radiation"></i>
                            <div>
                                <strong>SECURITY ALERT!</strong> <?php echo count($scanResults['suspect_files']); ?> SUSPICIOUS FILES DETECTED
                            </div>
                        </div>
                        
                        <!-- Bulk Actions -->
                        <div class="bulk-actions" id="bulkActions" style="display: none;">
                            <div class="select-all-checkbox">
                                <input type="checkbox" id="selectAllBulk">
                                <label for="selectAllBulk">SELECT ALL FILES</label>
                                <span class="selected-count" id="selectedCount">0 SELECTED</span>
                            </div>
                            <button class="btn btn-backup" onclick="backupSelected()">
                                <i class="fas fa-save"></i> BACKUP SELECTED
                            </button>
                            <button class="btn btn-chmod" onclick="bulkChmod()">
                                <i class="fas fa-key"></i> CHMOD SELECTED
                            </button>
                            <button class="btn btn-delete" onclick="deleteSelected()">
                                <i class="fas fa-skull-crossbones"></i> DELETE SELECTED
                            </button>
                            <button class="btn" onclick="clearSelection()" style="border-color: #666; color: #666;">
                                <i class="fas fa-times"></i> CLEAR SELECTION
                            </button>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <button class="btn" onclick="selectAllFiles()" style="border-color: #00ff00; color: #00ff00;">
                                <i class="fas fa-check-double"></i> SELECT ALL
                            </button>
                            <button class="btn" onclick="clearAllSelection()" style="border-color: #666; color: #666;">
                                <i class="fas fa-ban"></i> CLEAR ALL
                            </button>
                        </div>
                        
                        <!-- Files Container -->
                        <div class="files-container">
                            <?php foreach ($scanResults['results'] as $index => $file): ?>
                                <!-- File Terminal -->
                                <div class="file-terminal" id="file-<?php echo $index; ?>">
                                    <!-- File Header -->
                                    <div class="file-header">
                                        <div class="file-checkbox">
                                            <input type="checkbox" class="file-checkbox-input" 
                                                   data-file="<?php echo urlencode($file['file']); ?>" 
                                                   data-index="<?php echo $index; ?>"
                                                   onchange="updateFileSelection(<?php echo $index; ?>)">
                                        </div>
                                        <div class="file-info">
                                            <div class="file-title">
                                                <span class="file-name">
                                                    <i class="fas fa-file-code" style="color: #00ff00;"></i>
                                                    <?php echo htmlspecialchars($file['filename']); ?>
                                                </span>
                                                <span class="badge badge-danger"><?php echo $file['issue_count']; ?> THREATS</span>
                                            </div>
                                            <div class="file-path">
                                                <i class="fas fa-folder" style="color: #666;"></i>
                                                <?php echo htmlspecialchars($file['directory']); ?>
                                            </div>
                                            <div class="file-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', $file['modified_time']); ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-weight-hanging"></i> <?php echo round($file['file_size'] / 1024, 1); ?> KB
                                                </span>
                                                <?php if ($file['web_url']): ?>
                                                    <span class="meta-item">
                                                        <i class="fas fa-globe"></i> WEB ACCESSIBLE
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <?php if ($file['web_url']): ?>
                                                <button class="btn btn-preview btn-sm" onclick="openPreview('<?php echo htmlspecialchars($file['web_url']); ?>', <?php echo $index; ?>)">
                                                    <i class="fas fa-eye"></i> PREVIEW
                                                </button>
                                                <a href="<?php echo htmlspecialchars($file['web_url']); ?>" target="_blank" class="btn btn-sm" style="border-color: #0088ff; color: #0088ff;">
                                                    <i class="fas fa-external-link-alt"></i> OPEN
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-backup btn-sm" onclick="backupFile('<?php echo urlencode($file['file']); ?>', <?php echo $index; ?>)">
                                                <i class="fas fa-hdd"></i> BACKUP
                                            </button>
                                            <button class="btn btn-chmod btn-sm" onclick="confirmChmod('<?php echo urlencode($file['file']); ?>', '<?php echo htmlspecialchars($file['filename']); ?>', <?php echo $index; ?>)">
                                                <i class="fas fa-key"></i> CHMOD
                                            </button>
                                            <button class="btn btn-delete btn-sm" onclick="confirmDelete('<?php echo urlencode($file['file']); ?>', '<?php echo htmlspecialchars($file['filename']); ?>', <?php echo $index; ?>)">
                                                <i class="fas fa-trash"></i> DELETE
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Content Row -->
                                    <div class="content-row">
                                        <!-- Issues Terminal -->
                                        <div class="issues-terminal">
                                            <div class="section-title">
                                                <i class="fas fa-bug"></i>
                                                DETECTED THREATS (<?php echo $file['issue_count']; ?>)
                                            </div>
                                            <div class="issues-list">
                                                <?php foreach ($file['issues'] as $issueIndex => $issue): ?>
                                                    <div class="issue-line">
                                                        <div class="issue-header">
                                                            <div class="issue-title">
                                                                <i class="fas fa-exclamation-circle"></i>
                                                                <?php echo htmlspecialchars($issue['description']); ?>
                                                            </div>
                                                            <div class="issue-line-number">LINE <?php echo $issue['line']; ?></div>
                                                        </div>
                                                        <div class="issue-code">
                                                            <?php 
                                                            $code = htmlspecialchars($issue['full_code']);
                                                            $keywords = array('include', 'require', 'eval', 'system', 'exec', 'base64_decode', 'unserialize', 'file_get_contents');
                                                            foreach ($keywords as $keyword) {
                                                                $code = preg_replace(
                                                                    '/\b' . preg_quote($keyword, '/') . '\b/i',
                                                                    '<span class="code-highlight">$0</span>',
                                                                    $code
                                                                );
                                                            }
                                                            echo $code;
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Preview Terminal -->
                                        <div class="preview-terminal">
                                            <div class="section-title">
                                                <i class="fas fa-desktop"></i>
                                                WEB PREVIEW
                                            </div>
                                            <div class="preview-container" id="preview-<?php echo $index; ?>">
                                                <div class="preview-header">
                                                    <div class="preview-title">
                                                        <?php echo htmlspecialchars($file['filename']); ?>
                                                    </div>
                                                    <?php if ($file['web_url']): ?>
                                                        <button class="btn btn-sm" style="border-color: #00ff00; color: #00ff00; padding: 4px 10px;" 
                                                                onclick="openPreview('<?php echo htmlspecialchars($file['web_url']); ?>', <?php echo $index; ?>)">
                                                            <i class="fas fa-sync-alt"></i> RELOAD
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($file['web_url']): ?>
                                                    <iframe src="<?php echo htmlspecialchars($file['web_url']); ?>" 
                                                            class="preview-frame" 
                                                            id="preview-frame-<?php echo $index; ?>"
                                                            frameborder="0"></iframe>
                                                <?php else: ?>
                                                    <div class="preview-placeholder">
                                                        <i class="fas fa-unlink"></i>
                                                        <p>PREVIEW UNAVAILABLE</p>
                                                        <p style="font-size: 0.9em; margin-top: 5px;">FILE NOT ACCESSIBLE VIA WEB</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif (isset($_POST['scan_path'])): ?>
                <!-- No Results -->
                <div class="alert-box alert-warning">
                    <i class="fas fa-search"></i>
                    <div>SCAN COMPLETE - NO SUSPICIOUS PHP FILES FOUND</div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-terminal"></i>
                    <h3>SYSTEM READY</h3>
                    <p>ENTER PATH TO INITIATE SECURITY SCAN</p>
                    <div class="terminal-output" style="margin-top: 20px;">
                        <div class="output-line"><span class="command-prompt">$</span> php_security_scanner --help</div>
                        <div class="output-line">Usage: Enter directory path and click INITIATE SCAN</div>
                        <div class="output-line">Options: [--recursive] Scan sub-directories</div>
                        <div class="output-line">Actions: Backup, Delete, Preview detected files</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-terminal">
            <div class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color: #ff3300;"></i>
                CONFIRM FILE DELETION
            </div>
            <div class="modal-content" id="deleteFileName">
                Are you sure you want to delete this file?
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> CANCEL
                </button>
                <button class="modal-btn modal-btn-confirm" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> CONFIRM DELETE
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Multiple Confirmation Modal -->
    <div class="modal" id="deleteMultipleModal">
        <div class="modal-terminal">
            <div class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color: #ff3300;"></i>
                CONFIRM BULK DELETION
            </div>
            <div class="modal-content">
                Delete <span id="fileCount" style="color: #ff3300; font-weight: bold;">0</span> selected files?
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeMultipleModal()">
                    <i class="fas fa-times"></i> CANCEL
                </button>
                <button class="modal-btn modal-btn-confirm" id="confirmDeleteMultipleBtn">
                    <i class="fas fa-trash"></i> DELETE ALL
                </button>
            </div>
        </div>
    </div>

    <!-- Chmod Modal -->
    <div class="modal" id="chmodModal">
        <div class="modal-terminal">
            <div class="modal-title">
                <i class="fas fa-key" style="color: #cc00ff;"></i>
                CHANGE PERMISSIONS (CHMOD)
            </div>
            <div class="modal-content">
                <div class="form-group">
                    <label class="form-label">ENTER PERMISSION MODE (e.g. 0644)</label>
                    <input type="text" class="terminal-input" id="chmodValue" value="0644" placeholder="0644">
                </div>
                <div id="chmodTargetName" style="margin-top: 10px; color: #666; font-size: 0.9em;"></div>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeChmodModal()">
                    <i class="fas fa-times"></i> CANCEL
                </button>
                <button class="modal-btn" style="border-color: #cc00ff; color: #cc00ff;" id="confirmChmodBtn">
                    <i class="fas fa-check"></i> APPLY
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentFileToDelete = '';
        let currentFileIndex = null;
        let selectedFiles = new Map();
        
        // Handle form submission
        document.getElementById('scanForm').addEventListener('submit', function(e) {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('resultsSection').style.display = 'none';
            
            // Terminal animation
            const animation = document.querySelector('.scanning-animation');
            let lines = animation.querySelectorAll('.scan-line');
            let delay = 100;
            
            lines.forEach((line, index) => {
                line.style.animationDelay = (index * delay) + 'ms';
            });
        });
        
        // Checkbox management
        document.addEventListener('DOMContentLoaded', function() {
            // Handle individual file checkbox
            document.querySelectorAll('.file-checkbox-input').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const filePath = decodeURIComponent(this.dataset.file);
                    const index = this.dataset.index;
                    
                    if (this.checked) {
                        selectedFiles.set(filePath, index);
                    } else {
                        selectedFiles.delete(filePath);
                    }
                    
                    updateBulkActions();
                    updateFileSelection(index);
                });
            });
            
            // Select all checkbox in bulk actions
            document.getElementById('selectAllBulk')?.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.file-checkbox-input');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    const filePath = decodeURIComponent(cb.dataset.file);
                    const index = cb.dataset.index;
                    
                    if (this.checked) {
                        selectedFiles.set(filePath, index);
                        updateFileSelection(index);
                    } else {
                        selectedFiles.delete(filePath);
                        updateFileSelection(index);
                    }
                });
                
                updateSelectedCount();
            });
        });
        
        // Update file selection visual
        function updateFileSelection(index) {
            const fileTerminal = document.getElementById(`file-${index}`);
            const checkbox = document.querySelector(`#file-${index} .file-checkbox-input`);
            
            if (checkbox && checkbox.checked) {
                fileTerminal.style.borderColor = '#00ff00';
                fileTerminal.style.boxShadow = '0 0 15px rgba(0, 255, 0, 0.3)';
            } else {
                fileTerminal.style.borderColor = '#333';
                fileTerminal.style.boxShadow = 'none';
            }
        }
        
        // Update bulk actions visibility
        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = selectedFiles.size;
            
            if (selectedCount > 0) {
                bulkActions.style.display = 'flex';
            } else {
                bulkActions.style.display = 'none';
            }
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const selectedCount = selectedFiles.size;
            document.getElementById('selectedCount').textContent = selectedCount + ' SELECTED';
            document.getElementById('fileCount').textContent = selectedCount;
        }
        
        // Select all files
        function selectAllFiles() {
            const checkboxes = document.querySelectorAll('.file-checkbox-input');
            checkboxes.forEach(cb => {
                cb.checked = true;
                const filePath = decodeURIComponent(cb.dataset.file);
                const index = cb.dataset.index;
                selectedFiles.set(filePath, index);
                updateFileSelection(index);
            });
            
            updateBulkActions();
            const selectAllBulk = document.getElementById('selectAllBulk');
            if (selectAllBulk) selectAllBulk.checked = true;
        }
        
        // Clear all selection
        function clearAllSelection() {
            const checkboxes = document.querySelectorAll('.file-checkbox-input');
            checkboxes.forEach(cb => {
                cb.checked = false;
                const index = cb.dataset.index;
                updateFileSelection(index);
            });
            
            selectedFiles.clear();
            updateBulkActions();
            const selectAllBulk = document.getElementById('selectAllBulk');
            if (selectAllBulk) selectAllBulk.checked = false;
        }
        
        // Clear selection (from bulk actions)
        function clearSelection() {
            clearAllSelection();
        }
        
        // Open preview in iframe
        function openPreview(url, index) {
            const iframe = document.getElementById(`preview-frame-${index}`);
            if (iframe) {
                iframe.src = url;
                showTerminalNotification(`Loading preview for file ${index + 1}...`, 'info');
            }
        }
        
        // Modal functions for single file deletion
        function confirmDelete(filePath, fileName, fileIndex) {
            currentFileToDelete = filePath;
            currentFileIndex = fileIndex;
            document.getElementById('deleteFileName').innerHTML = 
                `Delete file: <strong style="color: #00ff00;">${fileName}</strong>?`;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentFileToDelete = '';
            currentFileIndex = null;
        }
        
        // Modal functions for multiple file deletion
        function confirmDeleteMultiple() {
            if (selectedFiles.size === 0) {
                showTerminalNotification('No files selected!', 'warning');
                return;
            }
            
            document.getElementById('deleteMultipleModal').style.display = 'flex';
        }
        
        function closeMultipleModal() {
            document.getElementById('deleteMultipleModal').style.display = 'none';
        }
        
        // Delete file function (single)
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!currentFileToDelete) return;
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> DELETING...';
            btn.disabled = true;
            
            fetch('?action=delete&file=' + encodeURIComponent(decodeURIComponent(currentFileToDelete)))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedFiles.delete(decodeURIComponent(currentFileToDelete));
                        updateBulkActions();
                        
                        if (currentFileIndex !== null) {
                            const fileTerminal = document.getElementById('file-' + currentFileIndex);
                            if (fileTerminal) {
                                fileTerminal.style.opacity = '0.5';
                                fileTerminal.style.transition = 'all 0.3s';
                                setTimeout(() => {
                                    fileTerminal.style.height = '0';
                                    fileTerminal.style.margin = '0';
                                    fileTerminal.style.overflow = 'hidden';
                                    setTimeout(() => fileTerminal.remove(), 300);
                                }, 100);
                            }
                        }
                        
                        showTerminalNotification(data.message, 'success');
                    } else {
                        showTerminalNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showTerminalNotification('Error: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    closeModal();
                });
        });
        
        // Delete multiple files function
        document.getElementById('confirmDeleteMultipleBtn').addEventListener('click', function() {
            if (selectedFiles.size === 0) return;
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> DELETING...';
            btn.disabled = true;
            
            const fileArray = Array.from(selectedFiles.keys());
            const formData = new FormData();
            fileArray.forEach(file => {
                formData.append('files[]', file);
            });
            formData.append('action', 'delete-multiple');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectedFiles.forEach((index, filePath) => {
                        const fileTerminal = document.getElementById('file-' + index);
                        if (fileTerminal) {
                            fileTerminal.style.opacity = '0.5';
                            fileTerminal.style.transition = 'all 0.3s';
                            setTimeout(() => {
                                fileTerminal.style.height = '0';
                                fileTerminal.style.margin = '0';
                                fileTerminal.style.overflow = 'hidden';
                                setTimeout(() => fileTerminal.remove(), 300);
                            }, 100);
                        }
                    });
                    
                    selectedFiles.clear();
                    updateBulkActions();
                    const selectAllBulk = document.getElementById('selectAllBulk');
                    if (selectAllBulk) selectAllBulk.checked = false;
                    
                    setTimeout(() => {
                        const totalFiles = document.querySelectorAll('.file-terminal').length;
                        if (totalFiles === 0) {
                            location.reload();
                        }
                    }, 500);
                    
                    showTerminalNotification(data.message, 'success');
                } else {
                    showTerminalNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showTerminalNotification('Error: ' + error.message, 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                closeMultipleModal();
            });
        });
        
        // Backup file function
        function backupFile(filePath, fileIndex) {
            if (!filePath) return;
            
            showTerminalNotification('Initiating backup procedure...', 'info');
            
            setTimeout(() => {
                fetch('?action=backup&file=' + encodeURIComponent(decodeURIComponent(filePath)))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showTerminalNotification(data.message, 'success');
                            const backupBtn = document.querySelector(`#file-${fileIndex} .btn-backup`);
                            if (backupBtn) {
                                const originalHTML = backupBtn.innerHTML;
                                backupBtn.innerHTML = '<i class="fas fa-check"></i> BACKED UP';
                                backupBtn.style.borderColor = '#00ff00';
                                backupBtn.style.color = '#00ff00';
                                setTimeout(() => {
                                    backupBtn.innerHTML = originalHTML;
                                    backupBtn.style.borderColor = '';
                                    backupBtn.style.color = '';
                                }, 2000);
                            }
                        } else {
                            showTerminalNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showTerminalNotification('Backup failed: ' + error.message, 'error');
                    });
            }, 500);
        }
        
        // Backup selected files
        function backupSelected() {
            if (selectedFiles.size === 0) {
                showTerminalNotification('No files selected for backup', 'warning');
                return;
            }
            
            showTerminalNotification(`Starting backup of ${selectedFiles.size} files...`, 'info');
            
            const fileArray = Array.from(selectedFiles.keys());
            let completed = 0;
            
            fileArray.forEach((filePath, i) => {
                setTimeout(() => {
                    fetch('?action=backup&file=' + encodeURIComponent(filePath))
                        .then(response => response.json())
                        .then(data => {
                            completed++;
                            const index = selectedFiles.get(filePath);
                            const backupBtn = document.querySelector(`#file-${index} .btn-backup`);
                            if (backupBtn && data.success) {
                                backupBtn.innerHTML = '<i class="fas fa-check"></i>';
                                backupBtn.style.borderColor = '#00ff00';
                                backupBtn.style.color = '#00ff00';
                                setTimeout(() => {
                                    backupBtn.innerHTML = '<i class="fas fa-hdd"></i> BACKUP';
                                    backupBtn.style.borderColor = '';
                                    backupBtn.style.color = '';
                                }, 1500);
                            }
                            
                            if (completed === fileArray.length) {
                                showTerminalNotification(`Backup complete: ${completed} files saved`, 'success');
                            }
                        });
                }, i * 800);
            });
        }
        
        // Delete selected files
        function deleteSelected() {
            if (selectedFiles.size === 0) {
                showTerminalNotification('Select files to delete first', 'warning');
                return;
            }
            
            confirmDeleteMultiple();
        }
        
        // Terminal notification function
        function showTerminalNotification(message, type) {
            const colors = {
                'success': '#00ff00',
                'error': '#ff3300',
                'warning': '#ffaa00',
                'info': '#0088ff'
            };
            
            const icon = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: #111;
                border: 1px solid ${colors[type]};
                color: ${colors[type]};
                font-family: 'Courier New', monospace;
                font-size: 0.9em;
                border-radius: 3px;
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
                z-index: 1001;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s;
                max-width: 400px;
            `;
            
            notification.innerHTML = `
                <i class="fas ${icon[type]}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === document.getElementById('deleteModal')) closeModal();
            if (e.target === document.getElementById('deleteMultipleModal')) closeMultipleModal();
            if (e.target === document.getElementById('chmodModal')) closeChmodModal();
        });

        // ====================== CHMOD FUNCTIONS ======================
        
        let currentChmodFile = '';
        let currentChmodIndex = null;
        let isBulkChmod = false;

        function confirmChmod(filePath, fileName, index) {
            currentChmodFile = filePath;
            currentChmodIndex = index;
            isBulkChmod = false;
            
            document.getElementById('chmodTargetName').textContent = 'Target: ' + fileName;
            document.getElementById('chmodModal').style.display = 'flex';
            document.getElementById('chmodValue').focus();
        }

        function bulkChmod() {
            if (selectedFiles.size === 0) {
                showTerminalNotification('No files selected!', 'warning');
                return;
            }
            
            isBulkChmod = true;
            document.getElementById('chmodTargetName').textContent = 'Target: ' + selectedFiles.size + ' selected files';
            document.getElementById('chmodModal').style.display = 'flex';
            document.getElementById('chmodValue').focus();
        }

        function closeChmodModal() {
            document.getElementById('chmodModal').style.display = 'none';
            currentChmodFile = '';
            currentChmodIndex = null;
            isBulkChmod = false;
        }

        document.getElementById('confirmChmodBtn').addEventListener('click', function() {
            const mode = document.getElementById('chmodValue').value;
            if (!mode) return;

            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> APPLYING...';
            btn.disabled = true;

            if (isBulkChmod) {
                // Bulk Chmod
                const fileArray = Array.from(selectedFiles.keys());
                const formData = new FormData();
                fileArray.forEach(file => {
                    formData.append('files[]', file);
                });
                formData.append('action', 'chmod-multiple');
                formData.append('mode', mode);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showTerminalNotification(data.message, 'success');
                    } else {
                        showTerminalNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showTerminalNotification('Error: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    closeChmodModal();
                });

            } else {
                // Single File Chmod
                const formData = new FormData();
                formData.append('action', 'chmod');
                formData.append('file', decodeURIComponent(currentChmodFile));
                formData.append('mode', mode);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showTerminalNotification(data.message, 'success');
                    } else {
                        showTerminalNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showTerminalNotification('Error: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    closeChmodModal();
                });
            }
        });
        
        // Terminal typing effect for empty state
        document.addEventListener('DOMContentLoaded', function() {
            const scanLines = document.querySelectorAll('.scan-line');
            scanLines.forEach(line => {
                const text = line.textContent;
                line.textContent = '';
                let i = 0;
                function typeWriter() {
                    if (i < text.length) {
                        line.textContent += text.charAt(i);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                }
                // Uncomment for typing effect
                // typeWriter();
            });
            
            // Show results when page loads
            <?php if ($scanResults && !isset($scanResults['error'])): ?>
            document.getElementById('loading').style.display = 'none';
            document.getElementById('resultsSection').style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>
</html>