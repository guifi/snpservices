<?php

  if (file_exists("config.php")) {
    include_once("config.php");
  } else {
    include_once("config.php.template");
  }

  $now = time();
  $mlast= @fopen("/tmp/last_mrtg", "r");
  if (($mlast) and ($now > (fgets($mlast) * 60 * 60))) {
    fclose($mlast);
    echo "Still fresh.\n";
    exit();
  }
  
  $hlastnow = @fopen($MRTG."/guifi/refresh/mrtg", "r") or die('Error reading changes\n');
  $last_now = fgets($hlastnow);
  fclose($hlastnow);
  $hlast= @fopen("/tmp/last_update.mrtg", "r");
  if (($hlast) and ($last_now == fgets($hlast))) {
    fclose($hlast);
    echo "No changes.\n";
    $hlast= @fopen("/tmp/last_mrtg", "w") or die('Error!');
    fwrite($hlast,$now);
    fclose($hlast);
    exit();
  }

  echo "Getting MRTG CSV file\n";
  $hcnml = @fopen($MRTGConfigSource, "r");
  $wcnml = @fopen("../data/guifi_mrtg.csv", "w");
  while (!feof($hcnml)) {
       $buffer = fgets($hcnml, 4096);
       fwrite($wcnml,$buffer);
  }
  fclose($hcnml);
  fclose($wcnml);

  $hlast= @fopen("/tmp/last_update.mrtg", "w") or die('Error!');
  fwrite($hlast,$last_now);
  fclose($hlast);
  $hlast= @fopen("/tmp/last_mrtg", "w") or die('Error!');
  fwrite($hlast,$now);
  fclose($hlast);
?>
