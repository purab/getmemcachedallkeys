<?php
$servers = array("localhost",'192.168.2.216');
$ports = array(11211,11211);

/**
 * Taken directly from memcache PECL source
 * http://pecl.php.net/package/memcache
 * Written by Purab
 */
function sendMemcacheCommand($server,$port,$command){
        $s = @fsockopen($server,$port);
        if (!$s){
                die("Cant connect to:".$server.':'.$port);
        }
        fwrite($s, $command."\r\n");
        $buf='';
        while ((!feof($s))) {
                $buf .= fgets($s, 256);
                if (strpos($buf,"END\r\n")!==false){ // stat says end
                    break;
                }
                if (strpos($buf,"DELETED\r\n")!==false || strpos($buf,"NOT_FOUND\r\n")!==false){ // delete says these
                    break;
                }
                if (strpos($buf,"OK\r\n")!==false){ // flush_all says ok
                    break;
                }
        }
    fclose($s);
        return ($buf);
}


function ParseMecacheResult($server, $port, $string) {    
    $lines = explode("\r\n", $string);
    $slabs = array();
    foreach($lines as $line) {
            if (preg_match("/STAT items:([\d]+):number ([\d]+)/", $line, $matches)) {
                    if (isset($matches[1])) {
                            if (!in_array($matches[1], $slabs)) {
                                    $slabs[] = $matches[1];
                                    $string = sendMemcacheCommand($server, $port, "stats cachedump " . $matches[1] . " " . $matches[2]);
                                    //echo "Slab # " . $matches[1] . "<br />";
                                    preg_match_all("/ITEM (.*?) /", $string, $matches);
                                    $allkey[] =$matches[1];
                                    
                            }
                    }
            }
    }
    return $allkey;
}

function getAllMecacheKeys($servers,$ports) {
    foreach ($servers as $key => $server) {
        $string = sendMemcacheCommand($server, $ports[$key], "stats items"); 
        $allkey[]=ParseMecacheResult($server, $ports[$key], $string);
    }
    return $allkey;
}

$allkey = getAllMecacheKeys($servers,$ports);

echo '<pre>';print_r($allkey);die;

?>
