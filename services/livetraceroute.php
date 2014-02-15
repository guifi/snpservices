<?php
/*
 * Created on 24/09/2008
 *
 * Live traceroute CNML service
 */

// info hook
// provides a message with information about the service and how to use it
function livetraceroute_info() {
	echo "livetraceroute";
  echo "\n\n";

	echo "outputs a traceroute from the server to a device\n";
	echo "parameters:\n";
//	echo "  device=<device_id>\n";
//	echo "    device id to ping\n";
	echo "  ip=<ip_address>\n";
	echo "    ip address to ping\n";
	echo "\n";
}

// main hook
// provides the service
function livetraceroute_main() {
	if (isset($_GET['ip']))
	  $ip = $_GET['ip'];
	else {
	  echo "ERROR, don't know where to trace\n";
	  return;
	}

  header("Content-Type: text/plain");
  system(sprintf('traceroute %s',$ip));
  return;
}

?>
