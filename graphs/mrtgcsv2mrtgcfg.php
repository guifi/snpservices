<?php

if (file_exists("../common/config.php")) {
   include_once("../common/config.php");
} else {
  include_once("../common/config.php.template");
}

$now = time();
$mlast= @fopen("/tmp/last_mrtg", "r");
if ($mlast)
  $last = fgets($mlast);
else 
  $last = 0;
print "Last: ".date('Y/m/d H:i:s',(int)$last)."\n";
if (($last) and ($now < ($last +  (60 * 60)))) {
  fclose($mlast);
  echo "Still fresh.\n";
  exit();
}

$hlastnow = @fopen($SNPDataServer_url."/guifi/refresh/cnml", "r") or die('Error reading changes\n');
$last_now = fgets($hlastnow);
fclose($hlastnow);
$hlast= @fopen("/tmp/last_update.mrtg", "r");
if (($hlast) and ($last_now == fgets($hlast))) {
  fclose($hlast);
  echo "No changes.\n";
  $hlast= @fopen("/tmp/last_mrtg", "w+") or die('Error!');
  fwrite($hlast,$now);
  fclose($hlast);
  exit();
}
print $last_now;

$hf = @fopen($MRTGConfigSource,"r") or die('Error reading MRTG csv input\n"');
$cf = @fopen('../data/mrtg.cfg','w+');

fputs($cf,sprintf($rrdtool_header,$rrdimg_path,$rrdimg_path,$rrddb_path,$rrddb_path));

while ( $buffer = fgets($hf, 4096) ) {
	if (substr($buffer,0,1) == '#')
	  continue;
	  
//  $buffer = substr($buffer,0,-1);
  $buffer = str_replace("\n","",$buffer);
  $dev=explode(',',$buffer);

  fputs($cf,sprintf($mrtg_ping_template,
                 $dev[0],
                 $dev[1],
                 $dev[0],
                 $dev[1],
                 $dev[1],
                 $dev[2],
                 $dev[0],
                 $dev[2],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0])
       );
  if (!isset($dev[3]) or empty($dev[3]))
    continue;
  $t = explode('|',$dev[3]); 
  //print_r($t);

  foreach ($t as $k=>$r)  {
    // is the snmp Index given??
    if (is_numeric($r)) {
      $rn = $dev[0].'-'.$r;
      $trap = $r;
      $wn = 'wLan';
    } // end if numeric snmp Index
    else {
      $rn = $dev[0].'-'.$k;
      // snmp is given by interface name
      $d = explode(';',$r);
      $wn = $d[1];
      $trap = '\\'.$d[0];
    }
    fputs($cf,sprintf($mrtg_traffic_template."\n",
                   $rn,
                   $trap,
                   $dev[2],
                   $rn,
                   $dev[2],
                   $dev[1],
                   $rn, $rn,
                   $wn,
                   $dev[1],
                   $rn,
                   $wn,
                   $dev[1],
                   $dev[1],
                   $wn)
         );

  } // foreach interface
  
                 
  
}
fclose($hf);
fclose($cf);

$hlast= @fopen("/tmp/last_update.mrtg", "w+") or die('Error!');
fwrite($hlast,$last_now);
fclose($hlast);
$hlast= @fopen("/tmp/last_mrtg", "w+") or die('Error!');
fwrite($hlast,$now);
fclose($hlast);

?>
