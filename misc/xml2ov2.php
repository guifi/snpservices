<?php

if (file_exists("../common/config.php")) {
   include_once("../common/config.php");
} else {
  include_once("../common/config.php.template");
}

header('Content-type: application/binary');
$nodes = $xml->xpath('//node');
foreach ($nodes as $node) {
  $node_attr = $node->attributes();
  print $node_attr['lon'].', '.$node_attr['lat'].',"'.$node_attr['title']."\"\n";
}

?>
