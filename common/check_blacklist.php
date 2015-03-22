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

system('sort /tmp/blacklist.snmp | uniq > /tmp/blacklist.ips');

$if = @fopen('/tmp/blacklist.ips','r');

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
  exec('./pping.sh '.$ip. " > /dev/null &");
  // exec('/bin/ping -c 5i '.$ip.' -q -W 4; if [ $? -eq 0 ] then; echo "'.$ip.'" >> /tmp/blacklist.ok fi; > /dev/null &');
  usleep(100000);
}
echo "\n\n#############\n";
echo date('l jS \of F Y h:i:s A').": $nelements checked. Creating a new blacklist without he alive IPs\n\n";
sleep(10);
system('grep -vf /tmp/blacklist.ok /tmp/blacklist.ips > /tmp/blacklist.tmp');
system('cp /tmp/blacklist.tmp /tmp/blacklist.ips');
system('cp /tmp/blacklist.tmp /tmp/blacklist.snmp');
system('rm -f /tmp/blacklist.ok /tmp/blacklist.tmp');

echo "\n\n#############\n";
echo date('l jS \of F Y h:i:s A').": Blacklist refreshed. Going to sleep for 7 mins 30 secs\n\n";
sleep(450);


} while (true);

?>
