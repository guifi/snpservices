<?php
/*
 * Created on 24/09/2008
 *
 * Live ping CNML service
 */

// info hook
// provides a message with information about the service and how to use it
function liveping_info() {
	echo "liveping";
  echo "\n\n";

	echo "outputs a ping from the server to a device\n";
	echo "parameters:\n";
//	echo "  device=<device_id>\n";
//	echo "    device id to ping\n";
	echo "  ip=<ip_address>\n";
	echo "    ip address to ping\n";
	echo "  count=<count>\n";
	echo "    number of pings (default count=5)\n";
	echo "\n";
}

// main hook
// provides the service
function liveping_main() {
	if (isset($_GET['count']))
	  $count = $_GET['count'];
	else
	  $count = 5;
	if (isset($_GET['ip']))
	  $ip = $_GET['ip'];
	else {
	  echo "ERROR, don't know where to ping\n";
	  return;
	}

  $cmd = sprintf('ping -c %d %s',$count,$ip);

  header("Content-Type: text/plain");
  system(sprintf('ping -c %d %s',$count,$ip));
  return;
}

?>
