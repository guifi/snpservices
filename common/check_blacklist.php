<?php

function save_oips($oips) {
  $of = @fopen('/tmp/blacklist.ips','w+');
  reset($oips);
  foreach ($oips as $ip=>$v) {
    fwrite($of,",$ip,\n");
  }
  fclose($of);
}

do {

$if = @fopen('/tmp/blacklist.snmp','r');

$Iips = array();
$Oips = array();

while (!feof($if)) {
  $buf = fgets($if);
  $ips = explode(',',$buf);
  if (!isset($ips[1]))
   continue;
  $Iips[$ips[1]] = null;
}
fclose($if);

$Oips = $Iips;
$c = 0;

$ctotal = count($Iips);
$nelements = count($Iips);
echo "\n\n#############\n";
echo date('l jS \of F Y h:i:s A').": Checking a blacklist of $nelements \n";

foreach ($Iips as $ip  => $v) {
  $c++;
  $pct = intval(($c * 100) / $ctotal);
  echo "\n".$c."/".$ctotal." (".$pct."%): ".$ip;
  exec("/bin/ping -c2 -i 0.2 $ip -q -W 3",$output,$retval);
  $lline = $output[count($output)-2];
  $pos = strpos($lline, "100% packet loss"); 
  if ($pos == false) {
    echo " ---> ALIVE!: $lline";
    unset($Oips[$ip]);
    save_oips($Oips);
  }
}

system('cp /tmp/blacklist.ips /tmp/blacklist.snmp');
echo "\n\n#############\n";
echo date('l jS \of F Y h:i:s A').": $nelements checked. Going to sleep for 7 mins 30 secs\n\n";
sleep(450);
system('tac /tmp/blacklist.snmp > /tmp/blacklist.tmp');
system('cp /tmp/blacklist.tmp  /tmp/blacklist.smtp');


} while (true);

?>