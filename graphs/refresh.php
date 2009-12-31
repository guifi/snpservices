<?php

  $rootZone = 3671;

  $minX = 999;
  $minY = 999;
  $maxX = -999;
  $maxY = -999;

  $members = array(); 

  $hlastnow = @fopen("http://guifi.net/guifi/refresh/maps", "r") or die('Error reading changes\n');
  $last_now = fgets($hlastnow);
  fclose($hlastnow);
  $hlast= @fopen("/tmp/last_update.cnml", "r");
  if (($hlast) and ($last_now == fgets($hlast))) {
    fclose($hlast);
    echo "No changes.\n";
    exit();
  }

  echo "Getting CNML file\n";
  $hcnml = @fopen("http://guifi.net/guifi/cnml/".$rootZone."/detail", "r");
  $wcnml = @fopen("guifi.cnml", "w+");
  while (!feof($hcnml)) {
       $buffer = fgets($hcnml, 4096);
       fwrite($wcnml,$buffer);
  }
  fclose($hcnml);
  fclose($wcnml);

  $hlast= @fopen("/tmp/last_update.cnml", "w+") or die('Error!');
  fwrite($hlast,$last_now);
  fclose($hlast);
?>
